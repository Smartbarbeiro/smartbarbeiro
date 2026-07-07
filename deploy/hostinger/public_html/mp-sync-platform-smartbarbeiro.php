<?php

/**
 * Sync platform subscription status from Mercado Pago preapproval API.
 * Visit: https://www.smartbarbeiro.com.br/mp-sync-platform-smartbarbeiro.php
 * Optional: ?mp_id=PREAPPROVAL_ID
 * DELETE immediately after.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$laravelRoot = dirname(__DIR__).'/laravel';
if (! is_file($laravelRoot.'/vendor/autoload.php') && is_file(dirname(__DIR__).'/laravel/laravel/vendor/autoload.php')) {
    $laravelRoot = dirname(__DIR__).'/laravel/laravel';
}

require $laravelRoot.'/vendor/autoload.php';

$app = require $laravelRoot.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$mpId = isset($_GET['mp_id']) ? trim((string) $_GET['mp_id']) : null;

$subscription = null;

if ($mpId) {
    $subscription = App\Models\BarbershopPlatformSubscription::query()
        ->where('mercadopago_preapproval_id', $mpId)
        ->first();
} else {
    $subscription = App\Models\BarbershopPlatformSubscription::query()
        ->whereNotNull('mercadopago_preapproval_id')
        ->latest()
        ->first();
}

if (! $subscription) {
    exit("No platform subscription with mercadopago_preapproval_id found.\n");
}

echo "=== Sync platform subscription ===\n\n";
echo 'local_id: '.$subscription->id."\n";
echo 'barbershop_user_id: '.$subscription->barbershop_user_id."\n";
echo 'payer_email: '.$subscription->payer_email."\n";
echo 'mp_id: '.$subscription->mercadopago_preapproval_id."\n";
echo 'status_before: '.$subscription->status."\n\n";

try {
    $synced = app(App\Services\BarbershopPlatformSubscriptionSyncService::class)
        ->syncByMercadoPagoId((string) $subscription->mercadopago_preapproval_id);
} catch (Throwable $exception) {
    exit('sync_error: '.$exception->getMessage()."\n");
}

if (! $synced) {
    exit("sync returned null (preapproval not matched).\n");
}

$synced->refresh();

echo 'status_after: '.$synced->status."\n";
echo 'is_active: '.($synced->isActive() ? 'yes' : 'no')."\n";
echo 'next_payment_date: '.($synced->next_payment_date?->toDateTimeString() ?? 'none')."\n";

$barbershop = $synced->barbershop;
if ($barbershop) {
    echo 'barbershop: '.$barbershop->name.' (@'.$barbershop->username.")\n";
    echo 'has_active_platform_subscription: '.($barbershop->hasActivePlatformSubscription() ? 'yes' : 'no')."\n";
    echo 'dashboard_url: '.url('/dashboard')."\n";
}

echo "\nDELETE mp-sync-platform-smartbarbeiro.php when done.\n";
