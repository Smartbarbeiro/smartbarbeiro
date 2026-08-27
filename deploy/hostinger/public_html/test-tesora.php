<?php

/**
 * Step-by-step bootstrap test.
 * https://www.tesora.com.br/test-tesora.php
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

function step(string $msg): void
{
    echo $msg."\n";
    if (function_exists('flush')) {
        flush();
    }
}

step('PHP '.PHP_VERSION);

if (version_compare(PHP_VERSION, '8.5.0', '>=')) {
    step('');
    step('WARNING: PHP 8.5 is too new for Laravel 13.');
    step('Fix: hPanel → PHP Configuration → select PHP 8.3 (or 8.4).');
    step('');
}

$laravel = dirname(__DIR__).'/laravel';
if (! is_file($laravel.'/vendor/autoload.php') && is_file($laravel.'/laravel/vendor/autoload.php')) {
    $laravel .= '/laravel';
}

step('Path: '.$laravel);
step('');

foreach (['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'bcmath'] as $ext) {
    step('ext-'.$ext.': '.(extension_loaded($ext) ? 'OK' : 'MISSING'));
}

step('');
step('Step 1: loading autoload...');

try {
    require $laravel.'/vendor/autoload.php';
    step('autoload: OK');
} catch (Throwable $e) {
    step('autoload FAILED: '.$e->getMessage());
    exit;
}

step('Step 2: loading bootstrap/app.php...');

try {
    $app = require $laravel.'/bootstrap/app.php';
    step('app: OK');
} catch (Throwable $e) {
    step('app FAILED: '.$e->getMessage());
    step($e->getFile().':'.$e->getLine());
    exit;
}

step('Step 3: kernel bootstrap...');
set_time_limit(120);

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        echo "\nFATAL: {$error['message']}\n{$error['file']}:{$error['line']}\n";
    }
});

try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    step('kernel: OK');
    step('Environment: '.$app->environment());
} catch (Throwable $e) {
    step('kernel FAILED: '.$e->getMessage());
    step($e->getFile().':'.$e->getLine());
    exit;
}

step('Step 4: database...');

try {
    $app->make('db')->connection()->getPdo();
    step('database: OK');
} catch (Throwable $e) {
    step('database FAILED: '.$e->getMessage());
    exit;
}

step('');
step('All checks passed. Run setup-tesora.php then open the homepage.');
