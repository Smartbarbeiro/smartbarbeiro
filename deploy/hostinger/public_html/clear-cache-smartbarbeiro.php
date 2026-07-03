<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$laravel = dirname(__DIR__).'/laravel';
if (! is_file($laravel.'/vendor/autoload.php') && is_file(dirname(__DIR__).'/laravel/laravel/vendor/autoload.php')) {
    $laravel = dirname(__DIR__).'/laravel/laravel';
}

$cache = $laravel.'/bootstrap/cache';
foreach (glob($cache.'/*') ?: [] as $file) {
    if (is_file($file)) {
        unlink($file);
        echo 'Deleted '.basename($file)."\n";
    }
}

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reset\n";
}

$routes = $laravel.'/routes/web.php';
echo "\nweb.php has uploads route: ".(is_file($routes) && str_contains(file_get_contents($routes), 'uploads.public') ? 'yes' : 'no')."\n";
echo "Done.\n";
