<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$routesDir = dirname(__DIR__).'/laravel/routes';
$source = $routesDir.'/web-version6.php';
$target = $routesDir.'/web.php';

if (! is_file($source)) {
    echo "Missing source: {$source}\n";
    exit(1);
}

if (! copy($source, $target)) {
    echo "Failed to copy routes file.\n";
    exit(1);
}

echo "Routes updated from web-version6.php\n";
