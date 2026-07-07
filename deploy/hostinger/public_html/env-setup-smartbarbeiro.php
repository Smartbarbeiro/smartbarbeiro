<?php

/**
 * Creates laravel/.env — no query string needed.
 * Visit: https://www.smartbarbeiro.com.br/env-setup-smartbarbeiro.php
 * DELETE this file immediately after.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$laravelRoot = dirname(__DIR__).'/laravel';

if (! is_dir($laravelRoot) && is_dir(dirname(__DIR__).'/laravel/laravel')) {
    $laravelRoot = dirname(__DIR__).'/laravel/laravel';
}

if (! is_dir($laravelRoot)) {
    exit('laravel/ folder not found. Upload and extract laravel.zip first.');
}

$envPath = $laravelRoot.'/.env';

if (is_file($envPath)) {
    echo ".env already exists at:\n{$envPath}\n\n";
    echo "Edit it in hPanel File Manager if needed.\n";
    exit;
}

$env = <<<'ENV'
APP_NAME="Smart Barbeiro"
APP_ENV=production
APP_KEY=base64:n+kciz3h3RwkMwTm+ZDH0Z0YSkyD7IEqheMwG1q/hT8=
APP_DEBUG=true
APP_URL=https://www.smartbarbeiro.com.br

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR

PUBLIC_PATH=/home/u379350398/domains/smartbarbeiro.com.br/public_html

ADMIN_EMAIL=fulviolopescatto@gmail.com
ADMIN_PASSWORD=Fcatto1950!

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u379350398_smart_tables
DB_USERNAME=u379350398_smart_tables
DB_PASSWORD="Fcatto1950!"

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file

LOG_CHANNEL=stack
LOG_LEVEL=debug

MERCADOPAGO_RUNTIME=server
MERCADOPAGO_BACK_URL=https://www.smartbarbeiro.com.br
ENV;

if (file_put_contents($envPath, $env) === false) {
    exit('Could not write .env. Set laravel/ permissions to 755 in File Manager.');
}

echo "SUCCESS — .env created at:\n{$envPath}\n\n";
echo "Next: open https://www.smartbarbeiro.com.br/setup-smartbarbeiro.php\n";
echo "Then DELETE env-setup-smartbarbeiro.php and setup-smartbarbeiro.php\n";
