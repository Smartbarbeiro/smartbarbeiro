<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$plan = App\Models\BarbershopPlatformPlan::current();
$planService = app(App\Services\BarbershopPlatformPlanService::class);

echo "Mercado Pago configured: ".(app(App\Services\MercadoPagoService::class)->isConfigured() ? 'yes' : 'no').PHP_EOL;

$token = config('mercadopago.access_token');
$testUserResponse = Illuminate\Support\Facades\Http::withOptions(['verify' => storage_path('certs/cacert.pem')])
    ->withToken($token)
    ->acceptJson()
    ->post('https://api.mercadopago.com/users/test_user', [
        'site_id' => 'MLB',
        'description' => 'Platform checkout test buyer',
    ]);

if (! $testUserResponse->successful()) {
    echo 'Failed to create MP test buyer: '.$testUserResponse->status().PHP_EOL;
    echo json_encode($testUserResponse->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit(1);
}

$email = $testUserResponse->json('email');
echo 'Using fresh MP test buyer: '.$email.PHP_EOL;

try {
    $planService->update($plan, [
        'title' => $plan->title,
        'description' => $plan->description,
        'monthly_amount' => (float) $plan->monthly_amount,
        'is_active' => true,
    ]);
    $plan->refresh();
    echo 'Platform plan synced. MP preapproval plan id: '.($plan->mercadopago_preapproval_plan_id ?? 'none').PHP_EOL;
} catch (Throwable $e) {
    echo 'Plan sync failed: '.$e->getMessage().PHP_EOL;
    exit(1);
}

$user = App\Models\User::query()->where('email', $email)->first();

if ($user === null) {
    $user = App\Models\User::factory()->create([
        'email' => $email,
        'username' => 'mp-test-'.time(),
    ]);
}

app(App\Services\BarbershopPlatformCheckoutService::class)->ensurePendingSubscription($user);
$user->platformSubscription()->update([
    'status' => App\Models\BarbershopPlatformSubscription::STATUS_PENDING,
    'payer_email' => $email,
]);

$checkout = app(App\Services\BarbershopPlatformCheckoutService::class);

try {
    $result = $checkout->startCheckout($user);
    echo 'Checkout started for: '.$email.PHP_EOL;
    echo 'Subscription status: '.$result['subscription']->status.PHP_EOL;
    echo 'MP preapproval id: '.($result['subscription']->mercadopago_preapproval_id ?? 'none').PHP_EOL;
    echo 'Checkout URL: '.($result['checkout_url'] ?? 'none').PHP_EOL;
} catch (Throwable $e) {
    echo 'Checkout failed: '.$e->getMessage().PHP_EOL;
    if ($e instanceof MercadoPago\Exceptions\MPApiException) {
        $content = $e->getApiResponse()?->getContent();
        echo 'MP response: '.json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    }
    exit(1);
}
