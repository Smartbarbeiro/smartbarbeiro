<?php

/**
 * One-time Laravel setup for Hostinger (no SSH).
 * 1. Upload to public_html/
 * 2. Visit https://www.smartbarbeiro.com.br/setup-once.php?key=YOUR_SECRET
 * 3. Delete this file immediately after success.
 */

declare(strict_types=1);

$secret = 'smartbarbeiro-setup-2026';

if (! isset($_GET['key']) || ! hash_equals($secret, (string) $_GET['key'])) {
    http_response_code(403);
    exit('Forbidden. Add ?key=... to the URL.');
}

$laravelRoot = dirname(__DIR__).'/laravel';

if (! is_file($laravelRoot.'/vendor/autoload.php')) {
    http_response_code(500);
    exit('Laravel not found at '.$laravelRoot);
}

require $laravelRoot.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $laravelRoot.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$commands = [
    'storage:link',
    'config:cache',
    'route:cache',
    'view:cache',
];

foreach ($commands as $command) {
    echo "==> php artisan {$command}\n";
    $status = $kernel->call($command);
    echo trim($kernel->output())."\n";
    echo $status === 0 ? "OK\n\n" : "FAILED (exit {$status})\n\n";
}

echo "Done. DELETE public_html/setup-once.php now.\n";
