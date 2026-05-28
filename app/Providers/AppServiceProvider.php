<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Policies\AdminUserPolicy;
use Illuminate\Support\Facades\Gate;
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
        User::observe(UserObserver::class);

        Gate::policy(User::class, AdminUserPolicy::class);

        Vite::prefetch(concurrency: 3);

        \Illuminate\Support\Facades\Date::setLocale(config('app.locale'));
    }
}
