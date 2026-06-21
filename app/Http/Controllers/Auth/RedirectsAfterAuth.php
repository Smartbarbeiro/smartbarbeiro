<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;

trait RedirectsAfterAuth
{
    /**
     * @return list<string>
     */
    protected function guestAuthPaths(): array
    {
        return [
            '/login',
            '/register',
            '/registrar',
            '/forgot-password',
            '/confirm-password',
            '/verify-email',
        ];
    }

    protected function normalizeAuthPath(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $normalized = '/'.trim($path, '/');

        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }

    protected function isGuestAuthPath(?string $path): bool
    {
        $normalized = $this->normalizeAuthPath($path);

        if ($normalized === null) {
            return false;
        }

        foreach ($this->guestAuthPaths() as $guestPath) {
            if ($normalized === $guestPath) {
                return true;
            }
        }

        return str_starts_with($normalized, '/reset-password');
    }

    protected function forgetGuestAuthIntendedUrl(Request $request): void
    {
        $intended = $request->session()->get('url.intended');

        if (! is_string($intended) || $intended === '') {
            return;
        }

        $path = parse_url($intended, PHP_URL_PATH) ?? $intended;

        if ($this->isGuestAuthPath($path)) {
            $request->session()->forget('url.intended');
        }
    }

    protected function resolvePostLoginDestination(Request $request): string
    {
        $this->forgetGuestAuthIntendedUrl($request);

        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && $intended !== '') {
            $path = parse_url($intended, PHP_URL_PATH) ?? $intended;

            if (! $this->isGuestAuthPath($path)) {
                return $intended;
            }
        }

        return $this->redirectAfterAuth($request);
    }

    protected function redirectAfterAuth(Request $request): string
    {
        $redirect = $this->barbershopRedirect($request);

        if ($redirect !== null) {
            return $redirect;
        }

        $user = $request->user();

        if ($user && ! $user->isBarbershop() && ! $user->isAdmin()) {
            $barbershop = $user->primaryBarbershop();

            if ($barbershop !== null) {
                return route('profile.public', [
                    'username' => $barbershop->username,
                ], absolute: false);
            }
        }

        return route('dashboard', absolute: false);
    }

    protected function isCustomerSignup(Request $request): bool
    {
        return $this->barbershopRedirect($request) !== null;
    }

    protected function barbershopUsernameFromRedirect(Request $request): ?string
    {
        $redirect = $this->barbershopRedirect($request);

        if ($redirect === null) {
            return null;
        }

        $username = trim(str_replace('/barbearias/', '', $redirect), '/');

        return $username !== '' ? $username : null;
    }

    protected function barbershopRedirect(Request $request): ?string
    {
        $redirect = $request->input('redirect') ?? $request->query('redirect');

        if (! is_string($redirect) || $redirect === '') {
            return null;
        }

        if (! str_starts_with($redirect, '/barbearias/')) {
            return null;
        }

        return $redirect;
    }
}
