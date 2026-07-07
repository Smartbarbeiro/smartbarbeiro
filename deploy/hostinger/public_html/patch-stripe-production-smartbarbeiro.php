<?php

/**
 * Patches laravel/.env with Stripe TEST keys (client plan subscriptions).
 * Visit once: https://www.smartbarbeiro.com.br/patch-stripe-production-smartbarbeiro.php
 * DELETE this file immediately after.
 *
 * Test mode only: STRIPE_KEY=pk_test_..., STRIPE_SECRET=sk_test_...
 * Webhook: create in Stripe TEST mode → https://dashboard.stripe.com/test/webhooks
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$laravelRoot = dirname(__DIR__).'/laravel';
if (! is_dir($laravelRoot) && is_dir(dirname(__DIR__).'/laravel/laravel')) {
    $laravelRoot = dirname(__DIR__).'/laravel/laravel';
}

$envPath = $laravelRoot.'/.env';

if (! is_file($envPath)) {
    exit(".env not found at {$envPath}\nRun env-setup-smartbarbeiro.php first.\n");
}

// Set STRIPE_KEY in hPanel before running, or edit this line with pk_test_... from Stripe Dashboard.
$updates = [
    'STRIPE_KEY' => '',
    'STRIPE_SECRET' => '',
    'STRIPE_CURRENCY' => 'brl',
    'STRIPE_MERCHANT_DISPLAY_NAME' => '"Smart Barbeiro"',
    'STRIPE_GOOGLE_PAY_TEST_ENV' => 'true',
];

$content = file_get_contents($envPath);
if ($content === false) {
    exit("Could not read {$envPath}\n");
}

foreach ($updates as $key => $value) {
    $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
    $line = $key.'='.$value;

    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, $line, $content) ?? $content;
        echo "updated {$key}\n";
    } else {
        $content = rtrim($content)."\n".$line."\n";
        echo "added {$key}\n";
    }
}

if (file_put_contents($envPath, $content) === false) {
    exit("Could not write {$envPath}\n");
}

if ($updates['STRIPE_KEY'] === '' || $updates['STRIPE_SECRET'] === '') {
    echo "\nWARNING: Set STRIPE_KEY=pk_test_... and STRIPE_SECRET=sk_test_... in laravel/.env\n";
    echo "Stripe Dashboard: https://dashboard.stripe.com/test/apikeys\n\n";
}

echo "\n.env patched.\n\n";
echo "Stripe webhook (after first deploy + webhook secret in .env):\n";
echo "  https://www.smartbarbeiro.com.br/webhooks/stripe\n\n";
echo "Next:\n";
echo "1. Set STRIPE_KEY=pk_test_... in laravel/.env if not set above\n";
echo "2. clear-cache-smartbarbeiro.php\n";
echo "3. Test: GET /api/v1/barbearias/{username} → stripe_configured: true\n";
echo "\nDELETE patch-stripe-production-smartbarbeiro.php when done.\n";
