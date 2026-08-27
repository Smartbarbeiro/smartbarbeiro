<?php

/**
 * Patches mobile Google OAuth (browser redirect back to app).
 * Visit once: https://www.tesora.com.br/patch-mobile-oauth-tesora.php
 * Then: clear-cache-tesora.php
 * DELETE this file after success.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$laravelRoot = dirname(__DIR__).'/laravel';
if (! is_file($laravelRoot.'/vendor/autoload.php') && is_file(dirname(__DIR__).'/laravel/laravel/vendor/autoload.php')) {
    $laravelRoot = dirname(__DIR__).'/laravel/laravel';
}

$socialAuthPath = $laravelRoot.'/app/Http/Controllers/Auth/SocialAuthController.php';
$apiAuthPath = $laravelRoot.'/app/Http/Controllers/Api/V1/AuthController.php';

if (! is_file($socialAuthPath) || ! is_file($apiAuthPath)) {
    exit("Missing controller files under {$laravelRoot}\n");
}

$socialAuthContent = <<<'PHP'
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    use RedirectsAfterAuth;

    public const MOBILE_OAUTH_SCHEME = 'tesora://oauth/callback';

    public function redirect(Request $request, string $provider, SocialAuthService $socialAuth): RedirectResponse
    {
        abort_unless($socialAuth->isProviderSupported($provider), 404);
        abort_unless($socialAuth->isProviderConfigured($provider), 404);

        $request->session()->put('oauth.intent', $request->query('intent', 'login'));
        $request->session()->put('oauth.redirect', $request->query('redirect'));
        $request->session()->put('oauth.is_customer', $request->boolean('customer'));
        $request->session()->put('oauth.mobile', $request->boolean('mobile'));

        return Socialite::driver($provider)->redirect();
    }

    public function callback(
        Request $request,
        string $provider,
        SocialAuthService $socialAuth,
    ): RedirectResponse {
        abort_unless($socialAuth->isProviderSupported($provider), 404);
        abort_unless($socialAuth->isProviderConfigured($provider), 404);

        $intent = (string) $request->session()->get('oauth.intent', 'login');
        $isMobile = (bool) $request->session()->get('oauth.mobile', false);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $exception) {
            Log::warning('OAuth callback failed', [
                'provider' => $provider,
                'message' => $exception->getMessage(),
                'exception' => $exception::class,
                'redirect_uri' => config('services.google.redirect'),
                'mobile' => $isMobile,
            ]);

            $message = match (true) {
                str_contains($exception->getMessage(), 'Invalid state') => __('auth.oauth_session_expired'),
                str_contains(strtolower($exception->getMessage()), 'redirect_uri_mismatch') => __('auth.oauth_redirect_mismatch'),
                $intent === 'register' => __('auth.oauth_register_failed'),
                default => __('auth.oauth_failed'),
            };

            if ($isMobile) {
                return $this->mobileRedirect([
                    'status' => 'error',
                    'message' => $message,
                ]);
            }

            return redirect()
                ->route($intent === 'register' ? 'register' : 'login')
                ->withErrors([
                    $intent === 'register' ? 'oauth_google' : 'email' => $message,
                ]);
        }

        $user = $socialAuth->findOrLinkUser($provider, $socialUser);

        if ($user !== null) {
            if ($user->isFrozen()) {
                if ($isMobile) {
                    return $this->mobileRedirect([
                        'status' => 'error',
                        'message' => __('auth.frozen'),
                    ]);
                }

                return redirect()
                    ->route('login')
                    ->withErrors(['email' => __('auth.frozen')]);
            }

            if ($isMobile) {
                $token = $user->createToken('tesora-mobile')->plainTextToken;

                return $this->mobileRedirect([
                    'status' => 'authenticated',
                    'token' => $token,
                ]);
            }

            Auth::login($user, remember: true);
            $request->session()->regenerate();

            return redirect()->to($this->resolvePostLoginDestination($request));
        }

        $intent = (string) $request->session()->pull('oauth.intent', 'login');

        if ($intent !== 'register') {
            if ($isMobile) {
                return $this->mobileRedirect([
                    'status' => 'error',
                    'message' => __('auth.oauth_no_account'),
                ]);
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('auth.oauth_no_account')]);
        }

        $email = Str::lower((string) $socialUser->getEmail());

        if ($email === '') {
            if ($isMobile) {
                return $this->mobileRedirect([
                    'status' => 'error',
                    'message' => __('auth.oauth_email_required'),
                ]);
            }

            return redirect()
                ->route('register')
                ->withErrors(['oauth_google' => __('auth.oauth_email_required')]);
        }

        if (User::query()->where('email', $email)->exists()) {
            if ($isMobile) {
                return $this->mobileRedirect([
                    'status' => 'error',
                    'message' => __('auth.oauth_account_exists_use_password'),
                ]);
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('auth.oauth_account_exists_use_password')]);
        }

        $name = $socialAuth->resolveDisplayName($socialUser);

        if ($isMobile) {
            $code = Str::random(40);

            Cache::put('mobile_oauth_registration:'.$code, [
                'provider' => $provider,
                'provider_id' => (string) $socialUser->getId(),
                'name' => $name,
                'email' => $email,
            ], now()->addMinutes(20));

            return $this->mobileRedirect([
                'status' => 'registration_required',
                'code' => $code,
                'name' => $name,
                'email' => $email,
            ]);
        }

        $request->session()->put('oauth.registration', [
            'provider' => $provider,
            'provider_id' => (string) $socialUser->getId(),
            'name' => $name,
            'email' => $email,
            'redirect' => $request->session()->pull('oauth.redirect'),
            'is_customer' => (bool) $request->session()->pull('oauth.is_customer'),
        ]);

        return redirect()->route('register.oauth.complete');
    }

    /**
     * @param  array<string, string>  $params
     */
    private function mobileRedirect(array $params): RedirectResponse
    {
        return redirect()->away(self::MOBILE_OAUTH_SCHEME.'?'.http_build_query($params));
    }
}
PHP;

if (file_put_contents($socialAuthPath, $socialAuthContent) === false) {
    exit("Could not write {$socialAuthPath}\n");
}

echo "Updated SocialAuthController.php\n";

$apiAuthContent = file_get_contents($apiAuthPath);
if ($apiAuthContent === false) {
    exit("Could not read {$apiAuthPath}\n");
}

$oldRegisterBlock = <<<'PHP'
        $validated = $request->validate([
            'access_token' => ['required_without:id_token', 'string'],
            'id_token' => ['required_without:access_token', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'cpf', new UniqueTaxDocument],
            'barbershop_username' => ['required', 'string', 'exists:users,username'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $socialUser = $socialAuth->resolveGoogleUser(
                $validated['access_token'] ?? null,
                $validated['id_token'] ?? null,
            );
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'google' => [$exception->getMessage()],
            ]);
        }
PHP;

$newRegisterBlock = <<<'PHP'
        $validated = $request->validate([
            'oauth_code' => ['required_without_all:access_token,id_token', 'string', 'size:40'],
            'access_token' => ['required_without_all:oauth_code,id_token', 'string'],
            'id_token' => ['required_without_all:oauth_code,access_token', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'cpf', new UniqueTaxDocument],
            'barbershop_username' => ['required', 'string', 'exists:users,username'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $socialUser = $this->resolveGoogleRegistrationIdentity(
                $validated['oauth_code'] ?? null,
                $validated['access_token'] ?? null,
                $validated['id_token'] ?? null,
                $socialAuth,
            );
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'google' => [$exception->getMessage()],
            ]);
        }
PHP;

if (! str_contains($apiAuthContent, $oldRegisterBlock) && ! str_contains($apiAuthContent, 'resolveGoogleRegistrationIdentity')) {
    exit("AuthController.php googleRegister block not found (already patched or unexpected format).\n");
}

if (str_contains($apiAuthContent, $oldRegisterBlock)) {
    $apiAuthContent = str_replace($oldRegisterBlock, $newRegisterBlock, $apiAuthContent);
}

if (! str_contains($apiAuthContent, 'resolveGoogleRegistrationIdentity')) {
    $helperMethod = <<<'PHP'

    private function resolveGoogleRegistrationIdentity(
        ?string $oauthCode,
        ?string $accessToken,
        ?string $idToken,
        SocialAuthService $socialAuth,
    ): SocialiteUser {
        if ($oauthCode !== null) {
            $cached = Cache::pull('mobile_oauth_registration:'.$oauthCode);

            if (! is_array($cached) || ($cached['provider'] ?? null) !== 'google') {
                throw new \InvalidArgumentException(__('auth.oauth_session_expired'));
            }

            return new class($cached) implements SocialiteUser
            {
                public function __construct(private array $data) {}

                public function getId(): string
                {
                    return (string) ($this->data['provider_id'] ?? '');
                }

                public function getNickname(): ?string
                {
                    return null;
                }

                public function getName(): ?string
                {
                    return $this->data['name'] ?? null;
                }

                public function getEmail(): ?string
                {
                    return $this->data['email'] ?? null;
                }

                public function getAvatar(): ?string
                {
                    return null;
                }
            };
        }

        return $socialAuth->resolveGoogleUser($accessToken, $idToken);
    }
}
PHP;

    $apiAuthContent = preg_replace('/\n}\s*$/', $helperMethod, $apiAuthContent, 1);
}

if (file_put_contents($apiAuthPath, $apiAuthContent) === false) {
    exit("Could not write {$apiAuthPath}\n");
}

echo "Updated Api/V1/AuthController.php\n";
echo "\nMobile OAuth patch complete.\n";
echo "Next: clear-cache-tesora.php\n";
echo "DELETE patch-mobile-oauth-tesora.php when done.\n";
