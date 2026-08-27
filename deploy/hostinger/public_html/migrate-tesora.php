<?php

/**
 * Run pending Laravel migrations on production.
 * https://www.tesora.com.br/migrate-tesora.php
 * DELETE after success.
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

$laravelRoot = dirname(__DIR__).'/laravel';

require $laravelRoot.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $laravelRoot.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Running migrations...\n\n";
$status = $kernel->call('migrate', ['--force' => true]);
echo trim($kernel->output())."\n\n";
echo $status === 0 ? "Done.\n" : "Failed with exit {$status}\n";
