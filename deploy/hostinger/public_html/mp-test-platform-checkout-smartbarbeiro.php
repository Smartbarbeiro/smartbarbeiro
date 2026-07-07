<?php

/**
 * Test platform checkout URL generation (no browser).
 * Visit: https://www.smartbarbeiro.com.br/mp-test-platform-checkout-smartbarbeiro.php?user_id=3
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

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;

$user = $userId
    ? App\Models\User::query()->find($userId)
    : App\Models\User::query()->where('is_barbershop', true)->latest()->first();

if (! $user) {
    exit("Barbershop user not found.\n");
}

$plan = App\Models\BarbershopPlatformPlan::current();
$mp = app(App\Services\MercadoPagoService::class);

echo "=== Platform checkout test ===\n\n";
echo 'user_id: '.$user->id."\n";
echo 'email: '.$user->email."\n";
echo 'username: '.($user->username ?? 'none')."\n";
echo 'plan_mp_id: '.($plan->mercadopago_preapproval_plan_id ?? 'none')."\n";
echo 'plan_price: R$ '.number_format((float) $plan->monthly_amount, 2, ',', '.')."\n\n";

try {
    $result = app(App\Services\BarbershopPlatformCheckoutService::class)->startCheckout($user);
} catch (Throwable $exception) {
    echo 'checkout_error: '.$exception->getMessage()."\n";

    if ($exception instanceof MercadoPago\Exceptions\MPApiException) {
        echo 'mp_response: '.json_encode($exception->getApiResponse()?->getContent(), JSON_UNESCAPED_UNICODE)."\n";
    }

    exit;
}

echo 'mp_preapproval_id: '.($result['subscription']->mercadopago_preapproval_id ?? 'none')."\n";
echo 'checkout_url: '.($result['checkout_url'] ?? 'NULL')."\n";
echo 'checkout_url_empty: '.(filled($result['checkout_url'] ?? null) ? 'no' : 'YES — button would do nothing')."\n";

echo "\nDELETE mp-test-platform-checkout-smartbarbeiro.php when done.\n";
