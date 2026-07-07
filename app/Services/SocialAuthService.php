<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthService
{
    /** @var list<string> */
    public const PROVIDERS = ['google'];

    public function isProviderSupported(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true);
    }

    public function isProviderConfigured(string $provider): bool
    {
        return match ($provider) {
            'google' => filled(config('services.google.client_id'))
                && filled(config('services.google.client_secret')),
            default => false,
        };
    }

    public function isGoogleEnabled(): bool
    {
        return $this->isProviderConfigured('google');
    }

    public function resolveDisplayName(SocialiteUser $socialUser): string
    {
        $name = trim((string) ($socialUser->getName() ?? ''));

        if ($name !== '') {
            return $name;
        }

        $raw = $socialUser->getRaw();

        if (is_array($raw)) {
            $given = trim((string) ($raw['given_name'] ?? ''));
            $family = trim((string) ($raw['family_name'] ?? ''));
            $combined = trim($given.' '.$family);

            if ($combined !== '') {
                return $combined;
            }
        }

        $nickname = trim((string) ($socialUser->getNickname() ?? ''));

        if ($nickname !== '') {
            return $nickname;
        }

        return __('auth.oauth_default_name');
    }

    public function findOrLinkUser(string $provider, SocialiteUser $socialUser): ?User
    {
        $providerId = (string) $socialUser->getId();
        $email = Str::lower((string) $socialUser->getEmail());

        $user = User::query()
            ->where('oauth_provider', $provider)
            ->where('oauth_id', $providerId)
            ->first();

        if ($user !== null) {
            return $user;
        }

        if ($email === '') {
            return null;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return null;
        }

        if (
            filled($user->oauth_provider)
            && ($user->oauth_provider !== $provider || $user->oauth_id !== $providerId)
        ) {
            return null;
        }

        $user->forceFill([
            'oauth_provider' => $provider,
            'oauth_id' => $providerId,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }

    public function resolveGoogleUserFromAccessToken(string $accessToken): SocialiteUser
    {
        return Socialite::driver('google')->stateless()->userFromToken($accessToken);
    }

    /**
     * @return array{id: string, email: string, name: string|null, email_verified: bool}
     */
    public function resolveGoogleUserFromIdToken(string $idToken): array
    {
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (! $response->ok()) {
            throw new \InvalidArgumentException(__('auth.oauth_failed'));
        }

        $payload = $response->json();
        $clientId = (string) config('services.google.client_id');
        $audience = $payload['aud'] ?? null;
        $audienceMatches = $audience === $clientId
            || (is_array($audience) && in_array($clientId, $audience, true));

        if (
            ! is_array($payload)
            || ! $audienceMatches
            || ! filled($payload['sub'] ?? null)
        ) {
            throw new \InvalidArgumentException(__('auth.oauth_failed'));
        }

        return [
            'id' => (string) $payload['sub'],
            'email' => Str::lower((string) ($payload['email'] ?? '')),
            'name' => $payload['name'] ?? null,
            'email_verified' => filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOL),
        ];
    }

    public function socialiteUserFromIdTokenPayload(array $payload): SocialiteUser
    {
        return new class ($payload) implements SocialiteUser
        {
            public function __construct(private array $payload) {}

            public function getId(): string
            {
                return $this->payload['id'];
            }

            public function getNickname(): ?string
            {
                return null;
            }

            public function getName(): ?string
            {
                return $this->payload['name'];
            }

            public function getEmail(): ?string
            {
                return $this->payload['email'] ?: null;
            }

            public function getAvatar(): ?string
            {
                return null;
            }

            public function getRaw(): array
            {
                return $this->payload;
            }

            public function setToken($token): void {}

            public function setRefreshToken($refreshToken): void {}

            public function setExpiresIn($expiresIn): void {}

            public function offsetExists($offset): bool
            {
                return isset($this->payload[$offset]);
            }

            public function offsetGet($offset): mixed
            {
                return $this->payload[$offset] ?? null;
            }

            public function offsetSet($offset, $value): void
            {
                $this->payload[$offset] = $value;
            }

            public function offsetUnset($offset): void
            {
                unset($this->payload[$offset]);
            }
        };
    }

    public function resolveGoogleUser(?string $accessToken, ?string $idToken): SocialiteUser
    {
        // Prefer ID token (native apps / OpenID). Audience must be the web client ID.
        if (filled($idToken)) {
            $payload = $this->resolveGoogleUserFromIdToken($idToken);

            if (! $payload['email_verified'] || $payload['email'] === '') {
                throw new \InvalidArgumentException(__('auth.oauth_email_required'));
            }

            return $this->socialiteUserFromIdTokenPayload($payload);
        }

        if (filled($accessToken)) {
            return $this->resolveGoogleUserFromAccessToken($accessToken);
        }

        throw new \InvalidArgumentException(__('auth.oauth_failed'));
    }
}
