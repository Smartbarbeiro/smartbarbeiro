<?php
// Patches laravel/.env to use file session/cache (avoids DB hang on boot).
// https://www.tesora.com.br/fix-env-tesora.php

header('Content-Type: text/plain; charset=utf-8');

$envFile = dirname(__DIR__).'/laravel/.env';

if (! is_file($envFile)) {
    exit('.env not found');
}

$env = file_get_contents($envFile);

$replacements = [
    'SESSION_DRIVER=database' => 'SESSION_DRIVER=file',
    'CACHE_STORE=database' => 'CACHE_STORE=file',
    'QUEUE_CONNECTION=database' => 'QUEUE_CONNECTION=sync',
    'APP_DEBUG=false' => 'APP_DEBUG=true',
];

foreach ($replacements as $from => $to) {
    $env = str_replace($from, $to, $env);
}

file_put_contents($envFile, $env);

echo "Updated .env:\n";
echo "  SESSION_DRIVER=file\n";
echo "  CACHE_STORE=file\n";
echo "  QUEUE_CONNECTION=sync\n";
echo "  APP_DEBUG=true\n\n";
echo "Run clear-cache-tesora.php then test-tesora.php\n";
echo "DELETE fix-env-tesora.php when done.\n";
