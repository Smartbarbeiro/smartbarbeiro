<?php
// Rewrites a clean, valid laravel/.env (HostGator)
// https://www.tesora.com.br/fix-env-format-tesora.php

header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/_tesora-paths.php';

$envFile = tesora_env_path();
$laravelRoot = tesora_laravel_root();
$publicPath = tesora_public_path();

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
DB_PASSWORD="fcatto1950"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

LOG_CHANNEL=stack
LOG_LEVEL=error

MERCADOPAGO_RUNTIME=server
MERCADOPAGO_BACK_URL=https://www.tesora.com.br
ENV;

file_put_contents($envFile, $env);

echo ".env rewritten with valid format.\n\n";

require $laravelRoot.'/vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable($laravelRoot);
    $dotenv->load();
    echo "Dotenv parse: OK\n\n";
    echo "Next steps:\n";
    echo "1. clear-cache-tesora.php\n";
    echo "2. setup-tesora.php\n";
    echo "3. migrate-tesora.php\n";
    echo "\nDELETE fix-env-format-tesora.php when done.\n";
} catch (Throwable $e) {
    echo 'Dotenv FAIL: '.$e->getMessage()."\n";
}
