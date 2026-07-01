<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$laravel = dirname(__DIR__).'/laravel';

foreach (glob($laravel.'/bootstrap/cache/*.php') ?: [] as $file) {
    @unlink($file);
    echo 'Deleted '.basename($file)."\n";
}

echo "Done.\n";
