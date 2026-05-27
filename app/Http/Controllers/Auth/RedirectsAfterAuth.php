<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;

trait RedirectsAfterAuth
{
    protected function redirectAfterAuth(Request $request): string
    {
        $redirect = $this->barbershopRedirect($request);

        if ($redirect === null) {
            return route('dashboard', absolute: false);
        }

        return $redirect;
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
