<?php

namespace App\Providers;

use App\Models\AdminBroadcastMessageRecipient;
use App\Models\User;
use App\Observers\UserObserver;
use App\Policies\AdminBroadcastMessageRecipientPolicy;
use App\Policies\AdminUserPolicy;
use App\Listeners\SendBarbershopWelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
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
        if ($publicPath = config('app.public_path')) {
            $this->app->usePublicPath($publicPath);
        }
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

        if (! $this->app->runningInConsole() && $this->app->bound('request')) {
            $request = $this->app->make('request');

            if ($request->header('X-Forwarded-Proto') === 'https') {
                URL::forceScheme('https');
                config(['session.secure' => true]);
            }

            if (filled(config('services.google.client_id'))) {
                // Keep the registered Google redirect URI stable. Using the
                // current request host (www vs bare domain) causes Google 400
                // redirect_uri_mismatch errors.
                $redirect = config('services.google.redirect');

                if (! filled($redirect)) {
                    config([
                        'services.google.redirect' => rtrim((string) config('app.url'), '/').'/auth/google/callback',
                    ]);
                }

                if (class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
                    \Laravel\Socialite\Facades\Socialite::forgetDrivers();
                }
            }
        }

        \Illuminate\Support\Facades\Date::setLocale(config('app.locale'));

        Event::listen(Registered::class, SendBarbershopWelcomeEmail::class);

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $expireMinutes = config(
                'auth.passwords.'.config('auth.defaults.passwords').'.expire',
                60,
            );

            return (new MailMessage)
                ->subject('Redefinir sua senha — '.config('app.name'))
                ->markdown('mail.password-reset', [
                    'url' => $url,
                    'userName' => $notifiable->name ?? null,
                    'expireMinutes' => $expireMinutes,
                ]);
        });
    }
}
