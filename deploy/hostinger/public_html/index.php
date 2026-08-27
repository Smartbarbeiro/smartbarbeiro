<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$debug = isset($_GET['debug']) && $_GET['debug'] === 'tesora-setup-2026';

if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

$laravelRoot = dirname(__DIR__).'/laravel';

// Fix common mistake: laravel.zip extracted into laravel/laravel/
if (! is_file($laravelRoot.'/vendor/autoload.php') && is_file($laravelRoot.'/laravel/vendor/autoload.php')) {
    $laravelRoot = $laravelRoot.'/laravel';
}

try {
    if (! is_file($laravelRoot.'/vendor/autoload.php')) {
        throw new RuntimeException('vendor/autoload.php not found. Re-upload laravel.zip and extract.');
    }

    if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
        require $maintenance;
    }

    require $laravelRoot.'/vendor/autoload.php';

    /** @var Application $app */
    $app = require_once $laravelRoot.'/bootstrap/app.php';

    $app->handleRequest(Request::capture());
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');

    if ($debug) {
        echo "ERROR: ".$e->getMessage()."\n\n";
        echo $e->getFile().':'.$e->getLine()."\n\n";
        echo $e->getTraceAsString();
        exit;
    }

    echo 'Server error. For details upload check.php or open /?debug=tesora-setup-2026';
}
