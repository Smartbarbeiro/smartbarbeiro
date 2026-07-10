<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$log = dirname(__DIR__).'/laravel/storage/logs/laravel.log';

if (! is_readable($log)) {
    echo "Log not readable\n";
    exit;
}

$lines = file($log, FILE_IGNORE_NEW_LINES) ?: [];
$tail = array_slice($lines, -80);

echo implode("\n", $tail);
