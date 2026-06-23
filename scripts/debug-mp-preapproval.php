<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = config('mercadopago.access_token');
MercadoPago\MercadoPagoConfig::setAccessToken($token);
MercadoPago\MercadoPagoConfig::setRuntimeEnviroment(MercadoPago\MercadoPagoConfig::LOCAL);

$http = Illuminate\Support\Facades\Http::withToken($token)
    ->acceptJson()
    ->post('https://api.mercadopago.com/users/test_user', [
        'site_id' => 'MLB',
        'description' => 'Checkout debug buyer',
    ]);

echo 'Create test user status: '.$http->status().PHP_EOL;
$body = $http->json();
echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;

if (! $http->successful()) {
    exit(1);
}

$email = $body['email'] ?? null;
if (! $email) {
    exit(1);
}

$payload = [
    'reason' => 'Plano Único',
    'payer_email' => $email,
    'external_reference' => 'debug-'.time(),
    'back_url' => rtrim((string) config('mercadopago.back_url'), '/').'/assinatura/plataforma/retorno',
    'status' => 'pending',
    'auto_recurring' => [
        'frequency' => 1,
        'frequency_type' => 'months',
        'transaction_amount' => 49.90,
        'currency_id' => 'BRL',
    ],
];

try {
    $client = new MercadoPago\Client\PreApproval\PreApprovalClient;
    $result = $client->create($payload);
    echo PHP_EOL.'Preapproval OK'.PHP_EOL;
    echo 'id='.$result->id.PHP_EOL;
    echo 'init_point='.($result->init_point ?? 'null').PHP_EOL;
    echo 'sandbox_init_point='.($result->sandbox_init_point ?? 'null').PHP_EOL;
} catch (MercadoPago\Exceptions\MPApiException $e) {
    echo PHP_EOL.'Preapproval FAIL: '.json_encode($e->getApiResponse()?->getContent(), JSON_UNESCAPED_UNICODE).PHP_EOL;
}
