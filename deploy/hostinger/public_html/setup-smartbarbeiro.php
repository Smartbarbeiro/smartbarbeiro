<?php

/**
 * Laravel setup with visible errors.
 * https://www.smartbarbeiro.com.br/setup-smartbarbeiro.php
 * DELETE after success.
 */

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

function resolveLaravelRoot(): string
{
    $root = dirname(__DIR__).'/laravel';

    if (! is_file($root.'/vendor/autoload.php') && is_file($root.'/laravel/vendor/autoload.php')) {
        return $root.'/laravel';
    }

    return $root;
}

echo "=== Smart Barbeiro setup ===\n\n";

try {
    $laravelRoot = resolveLaravelRoot();
    echo "Laravel root: {$laravelRoot}\n";

    if (! is_file($laravelRoot.'/vendor/autoload.php')) {
        throw new RuntimeException('vendor/autoload.php not found. Re-upload laravel.zip.');
    }

    if (! is_file($laravelRoot.'/.env')) {
        throw new RuntimeException('.env missing. Run env-setup-smartbarbeiro.php first.');
    }

    if (! is_writable($laravelRoot.'/storage')) {
        throw new RuntimeException('storage/ is not writable. Set permissions to 755 in File Manager.');
    }

    require $laravelRoot.'/vendor/autoload.php';

    /** @var \Illuminate\Foundation\Application $app */
    $app = require_once $laravelRoot.'/bootstrap/app.php';

    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    echo "Bootstrap: OK\n\n";

    $commands = [
        'storage:link',
        'config:cache',
        'route:cache',
        'view:cache',
    ];

    foreach ($commands as $command) {
        echo "==> php artisan {$command}\n";
        try {
            $status = $kernel->call($command);
            $output = trim($kernel->output());
            if ($output !== '') {
                echo $output."\n";
            }
            echo $status === 0 ? "OK\n\n" : "Skipped (exit {$status})\n\n";
        } catch (Throwable $commandError) {
            echo 'WARN: '.$commandError->getMessage()."\n\n";
        }
    }

    echo "Setup finished.\n";
    echo "Test: https://www.smartbarbeiro.com.br\n";
    echo "If the homepage still fails, open:\n";
    echo "https://www.smartbarbeiro.com.br/?debug=smartbarbeiro-setup-2026\n";
    echo "\nDELETE setup-smartbarbeiro.php when done.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "SETUP FAILED\n\n";
    echo $e::class.': '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n\n";

    $log = resolveLaravelRoot().'/storage/logs/laravel.log';
    if (is_readable($log)) {
        echo "--- laravel.log (last 30 lines) ---\n";
        $lines = file($log, FILE_IGNORE_NEW_LINES);
        echo implode("\n", array_slice($lines ?: [], -30))."\n";
    }
}
