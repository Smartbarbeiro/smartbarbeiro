<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/_tesora-paths.php';

$envPath = tesora_env_path();
if (! is_file($envPath)) {
    exit(".env not found at {$envPath}\n");
}

$updates = [
    'APP_NAME' => 'Tesora',
    'MOBILE_APP_NAME' => 'Tesora',
    'MAIL_FROM_NAME' => 'Tesora',
    'STRIPE_MERCHANT_DISPLAY_NAME' => 'Tesora',
    'MERCADOPAGO_MERCHANT_NAME' => 'Tesora',
];

$content = file_get_contents($envPath);
foreach ($updates as $key => $value) {
    $line = $key.'="'.$value.'"';
    $pattern = '/^'.preg_quote($key, '/').'=.*/m';
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, $line, $content, 1);
    } else {
        $content = rtrim($content)."\n".$line."\n";
    }
    echo "Set {$key}\n";
}

file_put_contents($envPath, $content);
echo "OK brand env updated at {$envPath}\n";
echo "DELETE patch-brand-tesora.php when done.\n";
