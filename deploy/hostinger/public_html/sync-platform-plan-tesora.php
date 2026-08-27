<?php

/**
 * Syncs the platform plan to Mercado Pago (creates preapproval_plan_id if missing).
 * Visit once: https://www.tesora.com.br/sync-platform-plan-tesora.php
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

$plan = App\Models\BarbershopPlatformPlan::current();
$service = app(App\Services\BarbershopPlatformPlanService::class);

echo "=== Sync platform plan to Mercado Pago ===\n\n";
echo 'plan: '.$plan->title."\n";
echo 'amount: R$ '.number_format((float) $plan->monthly_amount, 2, ',', '.')."\n";
echo 'current_mp_plan_id: '.($plan->mercadopago_preapproval_plan_id ?? 'none')."\n\n";

try {
    $service->update($plan, [
        'title' => $plan->title,
        'description' => $plan->description,
        'monthly_amount' => (float) $plan->monthly_amount,
        'currency_id' => $plan->currency_id,
        'is_active' => (bool) $plan->is_active,
    ]);
    $plan->refresh();
    echo "OK — mercadopago_preapproval_plan_id: ".($plan->mercadopago_preapproval_plan_id ?? 'none')."\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: '.$e->getMessage()."\n";
    if ($e instanceof MercadoPago\Exceptions\MPApiException) {
        $content = $e->getApiResponse()?->getContent();
        echo 'MP response: '.json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
    }
    exit(1);
}

echo "\nNext: test checkout at ".url('/assinatura/plataforma')."\n";
echo "DELETE sync-platform-plan-tesora.php when done.\n";
