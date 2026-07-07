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

    public const MOBILE_OAUTH_SCHEME = 'smartbarbeiro://oauth/callback';

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
                $token = $user->createToken('smartbarbeiro-mobile')->plainTextToken;

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
