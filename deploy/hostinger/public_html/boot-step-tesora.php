<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);
set_time_limit(120);
ini_set('memory_limit', '256M');

ob_start();

header('Content-Type: text/plain; charset=utf-8');

function step(string $msg): void
{
    echo $msg."\n";
}

$laravel = dirname(__DIR__).'/laravel';
if (! is_file($laravel.'/vendor/autoload.php') && is_file($laravel.'/laravel/vendor/autoload.php')) {
    $laravel .= '/laravel';
}

require $laravel.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require $laravel.'/bootstrap/app.php';

$bootstrappers = [
    Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
    Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
    Illuminate\Foundation\Bootstrap\HandleExceptions::class,
    Illuminate\Foundation\Bootstrap\RegisterFacades::class,
    Illuminate\Foundation\Bootstrap\RegisterProviders::class,
];

step('=== Bootstrap steps (before provider boot) ===');

foreach ($bootstrappers as $class) {
    step("-> {$class}");
    try {
        (new $class)->bootstrap($app);
        step('   OK');
    } catch (Throwable $e) {
        step('   FAIL: '.$e->getMessage());
        ob_end_flush();
        exit;
    }
}

step('');
step('Binding HTTP request (like index.php does)...');
$app->instance('request', Illuminate\Http\Request::capture());
step('request: OK');

step('');
step('=== Provider boot (one by one) ===');

$ref = new ReflectionClass($app);
$prop = $ref->getProperty('serviceProviders');
$prop->setAccessible(true);
/** @var array<int, Illuminate\Support\ServiceProvider> $providers */
$providers = $prop->getValue($app);

$bootMethod = $ref->getMethod('bootProvider');
$bootMethod->setAccessible(true);

foreach ($providers as $provider) {
    $name = $provider::class;
    step("-> {$name}");
    try {
        $bootMethod->invoke($app, $provider);
        step('   OK');
    } catch (Throwable $e) {
        step('   FAIL: '.$e->getMessage());
        step('   '.$e->getFile().':'.$e->getLine());
        exit;
    }
}

step('');
step('All boot steps passed.');

ob_end_flush();
