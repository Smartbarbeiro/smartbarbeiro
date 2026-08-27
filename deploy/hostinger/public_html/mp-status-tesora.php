<?php

/**
 * Read-only Mercado Pago + payment readiness on production.
 * Visit: https://www.tesora.com.br/mp-status-tesora.php
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

$mp = app(App\Services\MercadoPagoService::class);
$stripe = app(App\Services\StripeServicePlanService::class);
$plan = App\Models\BarbershopPlatformPlan::current();

echo "=== Tesora payment status ===\n\n";

echo "--- Mercado Pago (plataforma + perfis pagos) ---\n";
echo 'configured: '.($mp->isConfigured() ? 'yes' : 'no')."\n";
echo 'public_key: '.($mp->publicKey() ? 'set' : 'missing')."\n";
echo 'runtime: '.config('mercadopago.runtime_environment')."\n";
echo 'back_url: '.config('mercadopago.back_url')."\n";
echo 'back_url_https: '.(str_starts_with((string) config('mercadopago.back_url'), 'https://') ? 'yes' : 'no')."\n";
echo 'webhook_url: '.url('/webhooks/mercadopago')."\n";
echo 'webhook_url_alt: '.url('/mp-notify.php')."\n";
echo 'webhook_ping: '.url('/mp-ping.php')."\n";
echo 'webhook_secret: '.(filled(config('mercadopago.webhook_secret')) ? 'set' : 'missing')."\n";
echo 'platform_checkout_url: '.url('/assinatura/plataforma')."\n";
echo 'platform_plan_active: '.($plan->is_active ? 'yes' : 'no')."\n";
echo 'platform_plan_price: R$ '.number_format((float) $plan->monthly_amount, 2, ',', '.')."\n";
echo 'platform_plan_mp_id: '.($plan->mercadopago_preapproval_plan_id ?? 'none')."\n";

$token = config('mercadopago.access_token');
if ($token) {
    $http = Illuminate\Support\Facades\Http::withOptions([
        'verify' => is_file(storage_path('certs/cacert.pem')) ? storage_path('certs/cacert.pem') : true,
    ])
        ->withToken($token)
        ->acceptJson()
        ->get('https://api.mercadopago.com/users/me');

    echo 'token_valid: '.($http->successful() ? 'yes' : 'no')."\n";
    if ($http->successful()) {
        $body = $http->json();
        $tags = $body['tags'] ?? [];
        echo 'mp_user_id: '.($body['id'] ?? 'unknown')."\n";
        echo 'mp_email: '.($body['email'] ?? 'unknown')."\n";
        echo 'mp_is_test_seller: '.(in_array('test_user', $tags, true) ? 'yes' : 'no')."\n";
        echo 'credentials_mode: '.($mp->usesTestCredentials() ? 'TEST token' : 'production APP_USR')."\n";
    } elseif (is_array($http->json()) && isset($http->json()['message'])) {
        echo 'token_error: '.$http->json()['message']."\n";
    }
}

echo "\n--- Stripe (planos de serviço na barbearia) ---\n";
echo 'configured: '.($stripe->isConfigured() ? 'yes' : 'no')."\n";
echo 'currency: '.config('stripe.currency', 'brl')."\n";
echo 'webhook_url: '.url('/webhooks/stripe')."\n";

echo "\n--- Subscriptions ---\n";
echo 'platform_pending: '.App\Models\BarbershopPlatformSubscription::query()
    ->where('status', App\Models\BarbershopPlatformSubscription::STATUS_PENDING)->count()."\n";
echo 'platform_active: '.App\Models\BarbershopPlatformSubscription::query()
    ->where('status', App\Models\BarbershopPlatformSubscription::STATUS_AUTHORIZED)->count()."\n";
echo 'platform_with_mp_id: '.App\Models\BarbershopPlatformSubscription::query()
    ->whereNotNull('mercadopago_preapproval_id')->count()."\n";

$latest = App\Models\BarbershopPlatformSubscription::query()->latest()->first();
if ($latest) {
    echo 'latest_platform_email: '.$latest->payer_email."\n";
    echo 'latest_platform_status: '.$latest->status."\n";
    echo 'latest_platform_mp_id: '.($latest->mercadopago_preapproval_id ?? 'none')."\n";
}

echo "\n--- How to test Mercado Pago (plataforma R$ 150/mês) ---\n";
echo "1. Login or register a barbershop account\n";
echo "2. Open: ".url('/assinatura/plataforma')."\n";
echo "3. Click \"Assinar com Mercado Pago\" (real charge with production credentials)\n";
echo "4. After payment, you should land on: ".url('/assinatura/plataforma/retorno')."\n";
echo "5. Configure MP webhook in Developers panel:\n";
echo '   '.url('/webhooks/mercadopago')."\n";
echo "\nDELETE mp-status-tesora.php when done.\n";
