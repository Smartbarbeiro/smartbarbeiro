<?php
// Visit: https://www.tesora.com.br/check-tesora.php
header('Content-Type: text/plain; charset=utf-8');

echo "PHP: ".PHP_VERSION." (need 8.3+)\n\n";

$root = dirname(__DIR__);
$laravel = $root.'/laravel';

if (! is_file($laravel.'/vendor/autoload.php') && is_file($laravel.'/laravel/vendor/autoload.php')) {
    $laravel = $laravel.'/laravel';
    echo "NOTE: using nested laravel/laravel/ folder\n\n";
}

foreach ([
    'laravel folder' => is_dir($laravel),
    'vendor' => is_file($laravel.'/vendor/autoload.php'),
    '.env' => is_file($laravel.'/.env'),
    'storage writable' => is_writable($laravel.'/storage'),
] as $label => $ok) {
    echo ($ok ? 'OK  ' : 'FAIL')." {$label}\n";
}

if (is_file($laravel.'/.env')) {
    $env = file_get_contents($laravel.'/.env');
    echo "\nAPP_KEY: ".(preg_match('/^APP_KEY=base64:.+/m', $env) ? 'OK' : 'MISSING')."\n";
}

$log = $laravel.'/storage/logs/laravel.log';
if (is_readable($log)) {
    echo "\n--- laravel.log (last 20 lines) ---\n";
    $lines = file($log, FILE_IGNORE_NEW_LINES);
    echo implode("\n", array_slice($lines ?: [], -20));
}
