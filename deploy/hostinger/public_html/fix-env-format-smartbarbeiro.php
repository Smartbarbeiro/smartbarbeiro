<?php
// Rewrites a clean, valid laravel/.env
// https://www.smartbarbeiro.com.br/fix-env-format-smartbarbeiro.php

header('Content-Type: text/plain; charset=utf-8');

$envFile = dirname(__DIR__).'/laravel/.env';

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
ADMIN_PASSWORD="Fcatto1950!"

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

file_put_contents($envFile, $env);

echo ".env rewritten with valid format.\n";
echo "Password is quoted (required for ! character).\n\n";

// Validate with dotenv
require dirname(__DIR__).'/laravel/vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__).'/laravel');
    $dotenv->load();
    echo "Dotenv parse: OK\n\n";
    echo "Next steps:\n";
    echo "1. clear-cache-smartbarbeiro.php (upload if needed)\n";
    echo "2. boot-step-smartbarbeiro.php\n";
    echo "3. setup-smartbarbeiro.php\n";
    echo "4. https://www.smartbarbeiro.com.br\n";
    echo "\nDELETE fix-env-format-smartbarbeiro.php when done.\n";
} catch (Throwable $e) {
    echo 'Dotenv FAIL: '.$e->getMessage()."\n";
}
