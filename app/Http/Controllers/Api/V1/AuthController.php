<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BarbershopMembership;
use App\Models\User;
use App\Rules\UniqueTaxDocument;
use App\Services\SocialAuthService;
use App\Support\TaxDocument;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->where('email', strtolower($credentials['email']))
            ->first();

        if ($user === null || blank($user->password) || ! Hash::check($credentials['password'], $user->password)) {
            if ($user !== null && blank($user->password) && filled($user->oauth_provider)) {
                throw ValidationException::withMessages([
                    'email' => [__('auth.oauth_use_google')],
                ]);
            }

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->isFrozen()) {
            throw ValidationException::withMessages([
                'email' => [__('auth.frozen')],
            ]);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'cpf', new UniqueTaxDocument],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'barbershop_username' => ['required', 'string', 'exists:users,username'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $barbershop = User::query()
            ->where('username', $validated['barbershop_username'])
            ->where('is_barbershop', true)
            ->where('is_frozen', false)
            ->firstOrFail();

        $user = User::create([
            'name' => $validated['name'],
            'username' => null,
            'email' => $validated['email'],
            'tax_document' => TaxDocument::normalize($validated['cpf']),
            'password' => Hash::make($validated['password']),
            'is_barbershop' => false,
        ]);

        BarbershopMembership::firstOrCreate([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $user->id,
        ]);

        event(new Registered($user));

        $token = $user->createToken($validated['device_name'] ?? 'mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'ok']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function googleConfig(SocialAuthService $socialAuth): JsonResponse
    {
        return response()->json([
            'enabled' => $socialAuth->isGoogleEnabled(),
            'client_id' => config('services.google.client_id'),
        ]);
    }

    public function googleLogin(Request $request, SocialAuthService $socialAuth): JsonResponse
    {
        abort_unless($socialAuth->isGoogleEnabled(), 404);

        $validated = $request->validate([
            'access_token' => ['required_without:id_token', 'string'],
            'id_token' => ['required_without:access_token', 'string'],
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

        $user = $socialAuth->findOrLinkUser('google', $socialUser);

        if ($user === null) {
            return response()->json([
                'status' => 'registration_required',
                'google_user' => [
                    'name' => $socialUser->getName() ?? __('auth.oauth_default_name'),
                    'email' => Str::lower((string) $socialUser->getEmail()),
                ],
            ], 422);
        }

        if ($user->isFrozen()) {
            throw ValidationException::withMessages([
                'google' => [__('auth.frozen')],
            ]);
        }

        $token = $user->createToken($validated['device_name'] ?? 'mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function googleRegister(Request $request, SocialAuthService $socialAuth): JsonResponse
    {
        abort_unless($socialAuth->isGoogleEnabled(), 404);

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

        $email = Str::lower((string) $socialUser->getEmail());

        if ($email === '') {
            throw ValidationException::withMessages([
                'google' => [__('auth.oauth_email_required')],
            ]);
        }

        if ($socialAuth->findOrLinkUser('google', $socialUser) !== null) {
            throw ValidationException::withMessages([
                'email' => [__('auth.oauth_account_exists_use_password')],
            ]);
        }

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => [__('auth.oauth_account_exists_use_password')],
            ]);
        }

        $barbershop = User::query()
            ->where('username', $validated['barbershop_username'])
            ->where('is_barbershop', true)
            ->where('is_frozen', false)
            ->firstOrFail();

        $user = User::create([
            'name' => $validated['name'],
            'username' => null,
            'email' => $email,
            'tax_document' => TaxDocument::normalize($validated['cpf']),
            'password' => null,
            'oauth_provider' => 'google',
            'oauth_id' => (string) $socialUser->getId(),
            'email_verified_at' => now(),
            'is_barbershop' => false,
        ]);

        BarbershopMembership::firstOrCreate([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $user->id,
        ]);

        event(new Registered($user));

        $token = $user->createToken($validated['device_name'] ?? 'mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'is_barbershop' => $user->isBarbershop(),
            'profile_photo_url' => $user->profile_photo_url,
            'primary_barbershop_username' => $user->isBarbershop()
                ? null
                : $user->primaryBarbershop()?->username,
        ];
    }
}
