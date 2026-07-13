<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BarbershopMembership;
use App\Models\User;
use App\Rules\UniqueTaxDocument;
use App\Services\BarbershopPlatformCheckoutService;
use App\Services\SocialAuthService;
use App\Services\UsernameGenerator;
use App\Support\TaxDocument;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OAuthRegistrationController extends Controller
{
    use RedirectsAfterAuth;

    public function create(Request $request, SocialAuthService $socialAuth): Response|RedirectResponse
    {
        if (! $socialAuth->isGoogleEnabled()) {
            return redirect()->route('register');
        }

        $oauth = $request->session()->get('oauth.registration');

        if (! is_array($oauth) || ! isset($oauth['provider'], $oauth['provider_id'], $oauth['email'])) {
            return redirect()
                ->route('register')
                ->withErrors(['oauth_google' => __('auth.oauth_session_expired')]);
        }

        return Inertia::render('Auth/OAuthCompleteRegistration', [
            'oauthUser' => [
                'name' => $oauth['name'] ?? '',
                'email' => $oauth['email'],
            ],
            'isCustomerSignup' => (bool) ($oauth['is_customer'] ?? false),
            'redirect' => $oauth['redirect'] ?? null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $oauth = $request->session()->get('oauth.registration');

        if (! is_array($oauth) || ! isset($oauth['provider'], $oauth['provider_id'], $oauth['email'])) {
            return redirect()
                ->route('register')
                ->withErrors(['oauth_google' => __('auth.oauth_session_expired')]);
        }

        $isCustomerSignup = (bool) ($oauth['is_customer'] ?? false);

        $name = trim((string) ($oauth['name'] ?? ''));

        if ($name === '') {
            $request->session()->forget('oauth.registration');

            return redirect()
                ->route('register')
                ->withErrors(['oauth_google' => __('auth.oauth_name_required')]);
        }

        $rules = [];

        if ($isCustomerSignup) {
            $rules['cpf'] = ['required', 'string', 'cpf', new UniqueTaxDocument];
        } else {
            $rules['cpf_cnpj'] = ['required', 'string', 'cpf_ou_cnpj', new UniqueTaxDocument];
            $rules['username'] = [
                'required',
                'string',
                'min:3',
                'max:30',
                'alpha_dash',
                Rule::unique(User::class),
            ];
        }

        $validated = $request->validate($rules);

        $email = strtolower((string) $oauth['email']);

        if (User::query()->where('email', $email)->exists()) {
            $request->session()->forget('oauth.registration');

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('auth.oauth_account_exists_use_password')]);
        }

        $taxDocument = TaxDocument::normalize(
            $isCustomerSignup ? $validated['cpf'] : $validated['cpf_cnpj'],
        );

        if ($isCustomerSignup) {
            $user = User::create([
                'name' => $name,
                'username' => null,
                'email' => $email,
                'tax_document' => $taxDocument,
                'password' => null,
                'oauth_provider' => $oauth['provider'],
                'oauth_id' => $oauth['provider_id'],
                'email_verified_at' => now(),
                'is_barbershop' => false,
            ]);

            $request->merge(['redirect' => $oauth['redirect'] ?? null]);
            $this->attachCustomerToBarbershop($user, $request);
        } else {
            $username = app(UsernameGenerator::class)->uniqueFrom(
                $name,
                $validated['username'],
            );

            $user = User::create([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'tax_document' => $taxDocument,
                'password' => null,
                'oauth_provider' => $oauth['provider'],
                'oauth_id' => $oauth['provider_id'],
                'email_verified_at' => now(),
                'is_barbershop' => true,
            ]);

            app(BarbershopPlatformCheckoutService::class)->ensurePendingSubscription($user);
        }

        $request->session()->forget('oauth.registration');

        event(new Registered($user));

        Auth::login($user, remember: true);

        if (! $isCustomerSignup) {
            $request->session()->put(
                'registration.redirect_to',
                route('platform.subscribe', absolute: false),
            );

            return redirect()->route('register.celebration');
        }

        $request->merge(['redirect' => $oauth['redirect'] ?? null]);

        return redirect($this->redirectAfterAuth($request));
    }

    private function attachCustomerToBarbershop(User $user, Request $request): void
    {
        $barbershopUsername = $this->barbershopUsernameFromRedirect($request);

        if ($barbershopUsername === null) {
            return;
        }

        $barbershop = User::query()
            ->where('username', $barbershopUsername)
            ->where('is_barbershop', true)
            ->first();

        if ($barbershop === null) {
            return;
        }

        BarbershopMembership::firstOrCreate([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $user->id,
        ]);
    }
}
