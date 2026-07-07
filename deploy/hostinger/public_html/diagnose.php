<?php

/**
 * Temporary Hostinger diagnostics — DELETE after fixing.
 * Visit: /diagnose.php?key=smartbarbeiro-setup-2026
 */

declare(strict_types=1);

$secret = 'smartbarbeiro-setup-2026';

if (! isset($_GET['key']) || ! hash_equals($secret, (string) $_GET['key'])) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== Smart Barbeiro deploy diagnostics ===\n\n";

echo 'PHP version: '.PHP_VERSION."\n";
echo 'PHP >= 8.3 required: '.(version_compare(PHP_VERSION, '8.3.0', '>=') ? 'YES' : 'NO — upgrade in hPanel PHP Configuration')."\n";

$requiredExtensions = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'bcmath'];
foreach ($requiredExtensions as $ext) {
    echo 'ext-'.$ext.': '.(extension_loaded($ext) ? 'OK' : 'MISSING')."\n";
}
echo "\n";

$domainRoot = dirname(__DIR__);
$laravelRoot = $domainRoot.'/laravel';

echo "Domain root: {$domainRoot}\n";
echo "Laravel root: {$laravelRoot}\n\n";

$checks = [
    'laravel folder' => is_dir($laravelRoot),
    'vendor/autoload.php' => is_file($laravelRoot.'/vendor/autoload.php'),
    'bootstrap/app.php' => is_file($laravelRoot.'/bootstrap/app.php'),
    '.env file' => is_file($laravelRoot.'/.env'),
    'storage writable' => is_writable($laravelRoot.'/storage'),
    'bootstrap/cache writable' => is_writable($laravelRoot.'/bootstrap/cache'),
    'index.php (this dir)' => is_file(__DIR__.'/index.php'),
    '.htaccess (this dir)' => is_file(__DIR__.'/.htaccess'),
];

foreach ($checks as $label => $ok) {
    echo ($ok ? '[OK] ' : '[FAIL] ').$label."\n";
}

echo "\n";

if (is_file($laravelRoot.'/.env')) {
    $env = file_get_contents($laravelRoot.'/.env');
    echo 'APP_KEY set: '.(preg_match('/^APP_KEY=base64:.+/m', $env) ? 'YES' : 'NO — add APP_KEY')."\n";
    echo 'DB_HOST: '.(preg_match('/^DB_HOST=(.+)$/m', $env, $m) ? trim($m[1]) : 'missing')."\n";
    echo 'PUBLIC_PATH set: '.(preg_match('/^PUBLIC_PATH=/m', $env) ? 'YES' : 'NO')."\n\n";
}

$logFile = $laravelRoot.'/storage/logs/laravel.log';
if (is_file($logFile) && is_readable($logFile)) {
    echo "=== Last 40 lines of laravel.log ===\n";
    $lines = file($logFile, FILE_IGNORE_NEW_LINES);
    echo implode("\n", array_slice($lines ?: [], -40))."\n\n";
} else {
    echo "laravel.log not readable yet (storage may not be writable).\n\n";
}

if (! is_file($laravelRoot.'/vendor/autoload.php')) {
    exit("Fix: re-upload/extract laravel.zip so vendor/ exists.\n");
}

echo "=== Laravel bootstrap test ===\n";

try {
    if (is_file($laravelRoot.'/.env')) {
        // Load .env for this test only.
        foreach (file($laravelRoot.'/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value, " \t\"'");
            putenv(trim($name).'='.trim($value, " \t\"'"));
        }
    }

    require $laravelRoot.'/vendor/autoload.php';
    $app = require_once $laravelRoot.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "Bootstrap: OK\n";
    echo 'App env: '.$app->environment()."\n";
} catch (Throwable $e) {
    echo "Bootstrap FAILED:\n";
    echo $e::class.': '.$e->getMessage()."\n";
    echo "at ".$e->getFile().':'.$e->getLine()."\n\n";
    echo $e->getTraceAsString()."\n";
}

echo "\nDELETE diagnose.php after fixing.\n";
