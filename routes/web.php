<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BarbershopMembershipController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSubscribeController;
use App\Http\Controllers\ProfileSubscriptionController;
use App\Http\Controllers\ProfileSubscriptionPlanController;
use App\Http\Controllers\PublicProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post('/webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->name('webhooks.mercadopago');

Route::get('/barbearias/{username}', [PublicProfileController::class, 'show'])
    ->name('profile.public');

Route::middleware(['auth', 'not_frozen'])->group(function () {
    Route::post('/barbearias/{username}/subscribe', [ProfileSubscribeController::class, 'store'])
        ->name('profile.subscribe');
    Route::post('/barbearias/{username}/signup', [BarbershopMembershipController::class, 'store'])
        ->name('barbershop.signup');
    Route::get('/barbearias/{username}/subscription/return', [ProfileSubscribeController::class, 'return'])
        ->name('profile.subscribe.return');
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $user = auth()->user()->load([
        'subscriptionPlan',
        'barbershopSignups.barbershop:id,name,username',
    ]);

    $props = [
        'isBarbershop' => $user->isBarbershop(),
        'profileUrl' => $user->profileUrl(),
        'subscribeUrl' => $user->subscribeUrl(),
        'barbershopMemberships' => $user->barbershopSignups->map(fn ($membership) => [
            'id' => $membership->id,
            'barbershop' => [
                'name' => $membership->barbershop->name,
                'username' => $membership->barbershop->username,
                'profile_url' => $membership->barbershop->profileUrl(),
            ],
        ]),
    ];

    if ($user->isBarbershop()) {
        $props = [
            ...$props,
            'storagePath' => 'storage/app/users/'.$user->id,
            'subscriptionPlan' => $user->subscriptionPlan ? [
                'is_enabled' => $user->subscriptionPlan->is_enabled,
                'formatted_price' => $user->subscriptionPlan->formattedPrice(),
            ] : null,
            'activeSubscribersCount' => $user->subscribers()
                ->whereIn('status', \App\Models\ProfileSubscription::activeStatuses())
                ->count(),
        ];
    }

    return Inertia::render('Dashboard', $props);
})->middleware(['auth', 'verified', 'not_frozen'])->name('dashboard');

Route::middleware(['auth', 'not_frozen'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/profile/subscription-plan', [ProfileSubscriptionPlanController::class, 'update'])
        ->name('profile.subscription-plan.update');

    Route::get('/subscriptions', [ProfileSubscriptionController::class, 'index'])
        ->name('subscriptions.index');
    Route::delete('/subscriptions/{subscription}', [ProfileSubscriptionController::class, 'destroy'])
        ->name('subscriptions.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('paineldecontrole')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::patch('/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::patch('/{user}/freeze', [AdminUserController::class, 'toggleFreeze'])->name('users.freeze');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    });

require __DIR__.'/auth.php';
