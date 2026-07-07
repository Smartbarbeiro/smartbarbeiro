<?php

/**
 * Patches MERCADOPAGO_WEBHOOK_SECRET in laravel/.env.
 * Visit once with: ?key=smartbarbeiro-mp-webhook-2026&secret=YOUR_SECRET
 * DELETE immediately after.
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

$expectedKey = 'smartbarbeiro-mp-webhook-2026';
$key = (string) ($_GET['key'] ?? '');
$secret = trim((string) ($_GET['secret'] ?? ''));

if (! hash_equals($expectedKey, $key) || $secret === '') {
    http_response_code(403);
    exit("Forbidden\n");
}

$laravelRoot = dirname(__DIR__).'/laravel';
if (! is_file($laravelRoot.'/vendor/autoload.php') && is_file(dirname(__DIR__).'/laravel/laravel/vendor/autoload.php')) {
    $laravelRoot = dirname(__DIR__).'/laravel/laravel';
}

$envPath = $laravelRoot.'/.env';

if (! is_file($envPath)) {
    exit(".env not found at {$envPath}\n");
}

$content = file_get_contents($envPath);
if ($content === false) {
    exit("Could not read {$envPath}\n");
}

$line = 'MERCADOPAGO_WEBHOOK_SECRET='.$secret;
$pattern = '/^MERCADOPAGO_WEBHOOK_SECRET=.*$/m';

if (preg_match($pattern, $content)) {
    $content = preg_replace($pattern, $line, $content) ?? $content;
    echo "updated MERCADOPAGO_WEBHOOK_SECRET\n";
} else {
    $content = rtrim($content)."\n".$line."\n";
    echo "added MERCADOPAGO_WEBHOOK_SECRET\n";
}

if (file_put_contents($envPath, $content) === false) {
    exit("Could not write {$envPath}\n");
}

echo "OK\n";
echo "Next: clear-cache-smartbarbeiro.php\n";
echo "DELETE patch-mp-webhook-secret-smartbarbeiro.php when done.\n";
