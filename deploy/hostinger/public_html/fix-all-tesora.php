<?php
// Run all fixes in one go.
// https://www.tesora.com.br/fix-all-tesora.php

header('Content-Type: text/plain; charset=utf-8');

$laravel = dirname(__DIR__).'/laravel';
if (! is_file($laravel.'/vendor/autoload.php') && is_file($laravel.'/laravel/vendor/autoload.php')) {
    $laravel .= '/laravel';
}

echo "=== Fix all ===\n\n";

// 1. Clear bootstrap cache
$cache = $laravel.'/bootstrap/cache';
foreach (glob($cache.'/*.php') ?: [] as $file) {
    @unlink($file);
    echo 'Deleted cache: '.basename($file)."\n";
}

// 2. Patch .env
$envFile = $laravel.'/.env';
if (is_file($envFile)) {
    $env = file_get_contents($envFile);
    $env = str_replace([
        'SESSION_DRIVER=database',
        'CACHE_STORE=database',
        'QUEUE_CONNECTION=database',
        'APP_DEBUG=false',
    ], [
        'SESSION_DRIVER=file',
        'CACHE_STORE=file',
        'QUEUE_CONNECTION=sync',
        'APP_DEBUG=true',
    ], $env);
    file_put_contents($envFile, $env);
    echo "Patched .env (file session/cache)\n";
}

// 3. Ensure storage dirs
$dirs = [
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'storage/app/public',
    'bootstrap/cache',
];
foreach ($dirs as $dir) {
    $path = $laravel.'/'.$dir;
    if (! is_dir($path)) {
        mkdir($path, 0755, true);
        echo "Created {$dir}\n";
    }
    @chmod($path, 0755);
}

// 4. Raw DB test (5s timeout)
if (is_file($envFile)) {
    preg_match('/^DB_HOST=(.+)$/m', file_get_contents($envFile), $h);
    preg_match('/^DB_DATABASE=(.+)$/m', file_get_contents($envFile), $d);
    preg_match('/^DB_USERNAME=(.+)$/m', file_get_contents($envFile), $u);
    preg_match('/^DB_PASSWORD=(.*)$/m', file_get_contents($envFile), $p);
    $host = trim($h[1] ?? 'localhost', " \t\"'");
    $db = trim($d[1] ?? '', " \t\"'");
    $user = trim($u[1] ?? '', " \t\"'");
    $pass = trim($p[1] ?? '', " \t\"'");

    echo "\nDB test ({$host})...\n";
    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$db};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "Database: OK\n";
    } catch (Throwable $e) {
        echo 'Database FAIL: '.$e->getMessage()."\n";
    }
}

echo "\nDone. Open boot-step-tesora.php next.\n";
echo "DELETE fix-all-tesora.php when finished.\n";
