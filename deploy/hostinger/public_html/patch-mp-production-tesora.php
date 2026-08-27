<?php

/**
 * Patches laravel/.env with production Mercado Pago + domain settings.
 * Visit once: https://www.tesora.com.br/patch-mp-production-tesora.php
 * DELETE this file immediately after.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/_tesora-paths.php';

$laravelRoot = tesora_laravel_root();
$envPath = tesora_env_path();

if (! is_file($envPath)) {
    exit(".env not found at {$envPath}\nRun env-setup-tesora.php first.\n");
}

$updates = [
    'APP_URL' => 'https://www.tesora.com.br',
    'APP_TIMEZONE' => 'America/Sao_Paulo',
    'MERCADOPAGO_CURRENCY' => 'BRL',
    'MERCADOPAGO_RUNTIME' => 'server',
    'MERCADOPAGO_BACK_URL' => 'https://www.tesora.com.br',
    'GOOGLE_REDIRECT_URI' => 'https://www.tesora.com.br/oauth-google-return.php',
    'PUBLIC_PATH' => tesora_public_path(),
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

echo "\n.env patched (URLs and runtime only).\n";
echo "Edit laravel/.env in hPanel for MERCADOPAGO_* and GOOGLE_* secrets.\n";
echo "Include MERCADOPAGO_WEBHOOK_SECRET (assinatura secreta from MP Webhooks panel).\n\n";
echo "Mercado Pago webhook (set in MP Developers panel):\n";
echo "  https://www.tesora.com.br/webhooks/mercadopago\n\n";
echo "Next:\n";
echo "1. clear-cache-tesora.php\n";
echo "2. https://www.tesora.com.br\n";
echo "\nDELETE patch-mp-production-tesora.php when done.\n";
