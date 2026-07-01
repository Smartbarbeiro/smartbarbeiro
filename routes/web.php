<?php

use App\Http\Controllers\AcrylicQrOrderController;
use App\Http\Controllers\Admin\BarbershopPlatformPlanController;
use App\Http\Controllers\Admin\AcrylicQrOrderController as AdminAcrylicQrOrderController;
use App\Http\Controllers\Admin\AdminBroadcastMessageController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BarbershopPlatformSubscribeController;
use App\Http\Controllers\BarbershopMembershipController;
use App\Http\Controllers\BarbershopPreferredHaircutDayController;
use App\Http\Controllers\BarbershopServicePlanController;
use App\Http\Controllers\ClientHaircutPhotoController;
use App\Http\Controllers\CepLookupController;
use App\Http\Controllers\ServicePlanSubscribeController;
use App\Http\Controllers\ServicePlanSubscriptionController;
use App\Http\Controllers\ServicePlanSubscriptionPaymentController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSubscribeController;
use App\Http\Controllers\ProfileSubscriptionController;
use App\Http\Controllers\ProfileSubscriptionPlanController;
use App\Http\Controllers\PlatformMessageController;
use App\Http\Controllers\PublicProfileController;
use App\Models\User;
use App\Services\BarbershopClientAudienceService;
use App\Services\BarbershopScheduleForecastService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::match(['get', 'post'], '/webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->name('webhooks.mercadopago');

Route::post('/webhooks/stripe', StripeWebhookController::class)
    ->name('webhooks.stripe');

Route::get('/barbearias/{username}', [PublicProfileController::class, 'show'])
    ->name('profile.public');

Route::post('/barbearias/{username}/service-plans/subscribe-register', [ServicePlanSubscribeController::class, 'registerAndStore'])
    ->name('service-plan.subscribe.register');

Route::middleware(['auth', 'not_frozen'])->group(function () {
    Route::get('/assinatura/plataforma', [BarbershopPlatformSubscribeController::class, 'show'])
        ->name('platform.subscribe');
    Route::post('/assinatura/plataforma', [BarbershopPlatformSubscribeController::class, 'store'])
        ->name('platform.subscribe.store');
    Route::get('/assinatura/plataforma/retorno', [BarbershopPlatformSubscribeController::class, 'return'])
        ->name('platform.subscribe.return');
});

Route::middleware(['auth', 'not_frozen', 'barbershop_subscribed'])->group(function () {
    Route::post('/barbearias/{username}/subscribe', [ProfileSubscribeController::class, 'store'])
        ->name('profile.subscribe');
    Route::post('/barbearias/{username}/signup', [BarbershopMembershipController::class, 'store'])
        ->name('barbershop.signup');
    Route::patch('/barbearias/{username}/preferred-haircut-day', [BarbershopPreferredHaircutDayController::class, 'update'])
        ->name('barbershop.preferred-haircut-day.update');
    Route::get('/barbearias/{username}/subscription/return', [ProfileSubscribeController::class, 'return'])
        ->name('profile.subscribe.return');
    Route::post('/barbearias/{username}/service-plans/subscribe', [ServicePlanSubscribeController::class, 'store'])
        ->name('service-plan.subscribe');
    Route::get('/barbearias/{username}/service-plans/return', [ServicePlanSubscribeController::class, 'return'])
        ->name('service-plan.subscribe.return');
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::get('/dashboard', function (
    BarbershopScheduleForecastService $scheduleForecast,
    BarbershopClientAudienceService $clientAudience,
) {
    $user = auth()->user()->load([
        'subscriptionPlan',
        'barbershopSignups.barbershop:id,name,username',
    ]);

    if (! $user->isBarbershop() && ! $user->isAdmin()) {
        $barbershop = $user->primaryBarbershop();

        if ($barbershop !== null) {
            return redirect()->route('profile.public', $barbershop->username);
        }
    }

    $props = [
        'isBarbershop' => $user->isBarbershop(),
        'isAdmin' => $user->isAdmin(),
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

    if ($user->isAdmin()) {
        $props['barbershopAccountsCount'] = User::countBarbershopAccounts();
    }

    if ($user->isBarbershop()) {
        $hasSubscribers = $clientAudience->clientsFor($user)->isNotEmpty();

        $props = [
            ...$props,
            'hasSubscribers' => $hasSubscribers,
            'schedule' => $scheduleForecast->dashboardPayload($user),
            'acrylicQrOrder' => app(\App\Services\AcrylicQrOrderService::class)
                ->activeOrderPayloadFor($user),
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
})->middleware(['auth', 'verified', 'not_frozen', 'barbershop_subscribed'])->name('dashboard');

Route::middleware(['auth', 'not_frozen'])->group(function () {
    Route::get('/assinatura/plataforma', [BarbershopPlatformSubscribeController::class, 'show'])
        ->name('platform.subscribe');
    Route::post('/assinatura/plataforma', [BarbershopPlatformSubscribeController::class, 'store'])
        ->name('platform.subscribe.store');
    Route::get('/assinatura/plataforma/retorno', [BarbershopPlatformSubscribeController::class, 'return'])
        ->name('platform.subscribe.return');
});

Route::middleware(['auth', 'not_frozen', 'barbershop_subscribed'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/profile/subscription-plan', [ProfileSubscriptionPlanController::class, 'update'])
        ->name('profile.subscription-plan.update');
    Route::put('/profile/service-plans', [BarbershopServicePlanController::class, 'update'])
        ->name('profile.service-plans.update');
    Route::post('/profile/acrylic-qr-orders', [AcrylicQrOrderController::class, 'store'])
        ->name('profile.acrylic-qr-orders.store');
    Route::get('/cep/{postalCode}', CepLookupController::class)
        ->where('postalCode', '[0-9\-]+')
        ->name('cep.lookup');

    Route::get('/subscriptions', [ProfileSubscriptionController::class, 'index'])
        ->name('subscriptions.index');
    Route::delete('/subscriptions/{subscription}', [ProfileSubscriptionController::class, 'destroy'])
        ->name('subscriptions.destroy');
    Route::delete('/service-plan-subscriptions/{servicePlanSubscription}', [ServicePlanSubscriptionController::class, 'destroy'])
        ->name('service-plan-subscriptions.destroy');
    Route::get('/service-plan-payments/{payment}/nota-fiscal', [ServicePlanSubscriptionPaymentController::class, 'downloadNotaFiscal'])
        ->name('service-plan-payments.nota-fiscal');

    Route::get('/cortes', [ClientHaircutPhotoController::class, 'index'])
        ->name('haircuts.index');
    Route::post('/cortes', [ClientHaircutPhotoController::class, 'store'])
        ->name('haircuts.store');
    Route::delete('/cortes/{clientHaircutPhoto}', [ClientHaircutPhotoController::class, 'destroy'])
        ->name('haircuts.destroy');

    Route::patch('/platform-messages/{recipient}/dismiss', [PlatformMessageController::class, 'dismiss'])
        ->name('platform-messages.dismiss');
    // Route::get('/messages', [BarbershopMessageController::class, 'inbox'])->name('messages.inbox');
    // Route::get('/messages/compose', [BarbershopMessageController::class, 'compose'])->name('messages.compose');
    // Route::post('/messages', [BarbershopMessageController::class, 'store'])->name('messages.store');
    // Route::get('/messages/{message}', [BarbershopMessageController::class, 'show'])->name('messages.show');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('paineldecontrole')
    ->name('admin.')
    ->group(function () {
        Route::get('/pedidos-qrcode', [AdminAcrylicQrOrderController::class, 'index'])
            ->name('acrylic-qr-orders.index');
        Route::get('/pedidos-qrcode/{acrylicQrOrder}/pdf', [AdminAcrylicQrOrderController::class, 'downloadPdf'])
            ->name('acrylic-qr-orders.pdf');
        Route::patch('/pedidos-qrcode/{acrylicQrOrder}', [AdminAcrylicQrOrderController::class, 'update'])
            ->name('acrylic-qr-orders.update');
        Route::get('/mensagens', [AdminBroadcastMessageController::class, 'index'])
            ->name('messages.index');
        Route::post('/mensagens', [AdminBroadcastMessageController::class, 'store'])
            ->name('messages.store');
        Route::get('/plano-barbearia', [BarbershopPlatformPlanController::class, 'edit'])
            ->name('platform-plan.edit');
        Route::patch('/plano-barbearia', [BarbershopPlatformPlanController::class, 'update'])
            ->name('platform-plan.update');
        Route::get('/', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/{user}/qrcode.pdf', [AdminUserController::class, 'downloadQrPdf'])
            ->name('users.qrcode.pdf');
        Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::patch('/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::patch('/{user}/freeze', [AdminUserController::class, 'toggleFreeze'])->name('users.freeze');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    });

require __DIR__.'/auth.php';
