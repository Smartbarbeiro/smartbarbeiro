<?php

/**
 * Read-only Mercado Pago integration status (no API writes).
 */
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$mp = app(App\Services\MercadoPagoService::class);
$plan = App\Models\BarbershopPlatformPlan::current();

echo '=== Mercado Pago status ==='.PHP_EOL;
echo 'configured: '.($mp->isConfigured() ? 'yes' : 'no').PHP_EOL;
echo 'runtime: '.config('mercadopago.runtime_environment').PHP_EOL;
echo 'app_url: '.config('app.url').PHP_EOL;
echo 'back_url: '.config('mercadopago.back_url').PHP_EOL;
echo 'back_url_https: '.(str_starts_with((string) config('mercadopago.back_url'), 'https://') ? 'yes' : 'no').PHP_EOL;
echo 'ssl_ca_bundle: '.(is_file(storage_path('certs/cacert.pem')) ? 'present' : 'missing').PHP_EOL;
echo 'plan_mp_id: '.($plan->mercadopago_preapproval_plan_id ?? 'none').PHP_EOL;

$pending = App\Models\BarbershopPlatformSubscription::query()
    ->where('status', App\Models\BarbershopPlatformSubscription::STATUS_PENDING)
    ->count();
$withMp = App\Models\BarbershopPlatformSubscription::query()
    ->whereNotNull('mercadopago_preapproval_id')
    ->count();

echo 'pending_subscriptions: '.$pending.PHP_EOL;
echo 'subscriptions_with_mp_id: '.$withMp.PHP_EOL;

$latest = App\Models\BarbershopPlatformSubscription::query()->latest()->first();
if ($latest) {
    echo 'latest_subscription_email: '.$latest->payer_email.PHP_EOL;
    echo 'latest_is_test_user_email: '.(str_ends_with(strtolower((string) $latest->payer_email), '@testuser.com') ? 'yes' : 'no').PHP_EOL;
}

$token = config('mercadopago.access_token');
if ($token) {
    $http = Illuminate\Support\Facades\Http::withOptions(['verify' => storage_path('certs/cacert.pem')])
        ->withToken($token)
        ->acceptJson()
        ->get('https://api.mercadopago.com/users/me');

    echo 'token_valid: '.($http->successful() ? 'yes' : 'no').PHP_EOL;
    echo 'token_check_status: '.$http->status().PHP_EOL;

    if ($http->successful()) {
        $body = $http->json();
        echo 'mp_user_id: '.($body['id'] ?? 'unknown').PHP_EOL;
        echo 'mp_site_id: '.($body['site_id'] ?? 'unknown').PHP_EOL;
    } elseif (is_array($http->json()) && isset($http->json()['message'])) {
        echo 'token_error: '.$http->json()['message'].PHP_EOL;
    }
}
