<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBarbershopHasPlatformSubscription
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->isAdmin() || ! $user->isBarbershopAccount()) {
            return $next($request);
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('barbershop_platform_subscriptions')) {
            return $next($request);
        }

        if ($user->hasActivePlatformSubscription()) {
            return $next($request);
        }

        if ($request->routeIs(
            'platform.subscribe',
            'platform.subscribe.store',
            'platform.subscribe.return',
            'register.celebration',
            'logout',
        )) {
            return $next($request);
        }

        return redirect()->route('platform.subscribe');
    }
}
