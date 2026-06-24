<?php

namespace App\Providers;

use App\Models\AdminBroadcastMessageRecipient;
use App\Models\User;
use App\Observers\UserObserver;
use App\Policies\AdminBroadcastMessageRecipientPolicy;
use App\Policies\AdminUserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('local')) {
            $caBundle = storage_path('certs/cacert.pem');

            if (is_file($caBundle)) {
                ini_set('curl.cainfo', $caBundle);
                ini_set('openssl.cafile', $caBundle);
            }
        }

        User::observe(UserObserver::class);

        Gate::policy(User::class, AdminUserPolicy::class);
        Gate::policy(AdminBroadcastMessageRecipient::class, AdminBroadcastMessageRecipientPolicy::class);

        Vite::prefetch(concurrency: 3);

        if (! $this->app->runningInConsole()) {
            $request = request();

            if ($request->header('X-Forwarded-Proto') === 'https') {
                URL::forceScheme('https');
                config(['session.secure' => true]);
            }

            if (filled(config('services.google.client_id'))) {
                config(['services.google.redirect' => url('/auth/google/callback')]);

                if (class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
                    \Laravel\Socialite\Facades\Socialite::forgetDrivers();
                }
            }
        }

        \Illuminate\Support\Facades\Date::setLocale(config('app.locale'));
    }
}
