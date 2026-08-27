<?php

/**
 * Creates laravel/.env — no query string needed.
 * Visit: https://www.tesora.com.br/env-setup-tesora.php
 * DELETE this file immediately after.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/_tesora-paths.php';

$laravelRoot = tesora_laravel_root();

if (! is_dir($laravelRoot)) {
    exit('laravel/ folder not found. Upload and extract laravel.zip first.');
}

$envPath = tesora_env_path();
$publicPath = tesora_public_path();

if (is_file($envPath)) {
    echo ".env already exists at:\n{$envPath}\n\n";
    echo "Edit it in hPanel File Manager if needed.\n";
    exit;
}

$env = <<<ENV
APP_NAME="Tesora"
APP_ENV=production
APP_KEY=base64:n+kciz3h3RwkMwTm+ZDH0Z0YSkyD7IEqheMwG1q/hT8=
APP_DEBUG=false
APP_URL=https://www.tesora.com.br

APP_TIMEZONE=America/Sao_Paulo
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR

PUBLIC_PATH={$publicPath}

ADMIN_EMAIL=fulviolopescatto@gmail.com
ADMIN_PASSWORD=

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fulvio54_tesora
DB_USERNAME=fulvio54_admin
DB_PASSWORD=fcatto1950

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

LOG_CHANNEL=stack
LOG_LEVEL=error

MERCADOPAGO_CURRENCY=BRL
MERCADOPAGO_RUNTIME=server
MERCADOPAGO_BACK_URL=https://www.tesora.com.br
ENV;

if (file_put_contents($envPath, $env) === false) {
    exit('Could not write .env. Set laravel/ permissions to 755 in File Manager.');
}

echo "SUCCESS — .env created at:\n{$envPath}\n\n";
echo "Next: open https://www.tesora.com.br/setup-tesora.php\n";
echo "Then DELETE env-setup-tesora.php and setup-tesora.php\n";
