<?php

/**
 * Mercado Pago webhook entrypoint (avoids /webhooks/ path WAF false positives).
 * Configure in MP panel:
 *   https://www.smartbarbeiro.com.br/mp-notify.php
 */

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$laravelRoot = dirname(__DIR__).'/laravel';
if (! is_file($laravelRoot.'/vendor/autoload.php') && is_file($laravelRoot.'/laravel/vendor/autoload.php')) {
    $laravelRoot = $laravelRoot.'/laravel';
}

try {
    if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
        require $maintenance;
    }

    require $laravelRoot.'/vendor/autoload.php';

    /** @var Application $app */
    $app = require_once $laravelRoot.'/bootstrap/app.php';

    // Keep query string (data.id, type, topic) and route to the Laravel webhook.
    $query = $_SERVER['QUERY_STRING'] ?? '';
    $_SERVER['REQUEST_URI'] = '/webhooks/mercadopago'.($query !== '' ? '?'.$query : '');
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';

    $app->handleRequest(Request::capture());
} catch (Throwable $e) {
    // Still acknowledge so Mercado Pago panel does not show failure.
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo "ok\n";

    $logDir = $laravelRoot.'/storage/logs';
    if (is_dir($logDir) && is_writable($logDir)) {
        @file_put_contents(
            $logDir.'/mp-notify-error.log',
            date('c').' '.$e->getMessage()."\n".$e->getTraceAsString()."\n\n",
            FILE_APPEND,
        );
    }
}
