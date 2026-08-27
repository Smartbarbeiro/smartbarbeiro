<?php
// Upload to public_html/check.php — no Laravel needed.
// Open: /check.php?key=tesora-setup-2026
header('Content-Type: text/plain; charset=utf-8');
if (($_GET['key'] ?? '') !== 'tesora-setup-2026') {
    http_response_code(403);
    exit('Forbidden');
}

echo "PHP: ".PHP_VERSION."\n\n";

$root = dirname(__DIR__);
$laravel = $root.'/laravel';

foreach ([
    'laravel dir' => is_dir($laravel),
    'vendor' => is_file($laravel.'/vendor/autoload.php'),
    '.env' => is_file($laravel.'/.env'),
    'storage writable' => is_writable($laravel.'/storage'),
    'bootstrap/cache writable' => is_writable($laravel.'/bootstrap/cache'),
] as $k => $v) {
    echo ($v ? 'OK  ' : 'FAIL')." $k\n";
}

$nested = $laravel.'/laravel/vendor/autoload.php';
if (is_file($nested)) {
    echo "\nWARNING: files are inside laravel/laravel/ — move contents up one level.\n";
}

if (is_file($laravel.'/.env')) {
    $env = file_get_contents($laravel.'/.env');
    echo "\nAPP_KEY: ".(preg_match('/^APP_KEY=base64:.+/m', $env) ? 'OK' : 'MISSING')."\n";
}

$log = $laravel.'/storage/logs/laravel.log';
if (is_readable($log)) {
    echo "\n--- laravel.log (last 25 lines) ---\n";
    $lines = file($log, FILE_IGNORE_NEW_LINES);
    echo implode("\n", array_slice($lines ?: [], -25));
}
