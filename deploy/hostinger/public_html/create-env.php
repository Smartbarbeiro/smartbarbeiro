<?php

/**
 * Create laravel/.env on the server without terminal.
 * Visit once: /create-env.php?key=smartbarbeiro-setup-2026
 * DELETE this file immediately after.
 */

declare(strict_types=1);

$secret = 'smartbarbeiro-setup-2026';

if (! isset($_GET['key']) || ! hash_equals($secret, (string) $_GET['key'])) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Forbidden</h1>';
    echo '<p>Use this exact link (copy all of it):</p>';
    echo '<p><a href="?key=smartbarbeiro-setup-2026">create-env.php?key=smartbarbeiro-setup-2026</a></p>';
    echo '<p>Or upload <strong>env-setup-smartbarbeiro.php</strong> and open it (no key needed).</p>';
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

$laravelRoot = dirname(__DIR__).'/laravel';
if (! is_dir($laravelRoot)) {
    exit('laravel/ folder not found at '.$laravelRoot);
}

$envPath = $laravelRoot.'/.env';

if (is_file($envPath)) {
    exit('.env already exists. Edit it in File Manager if needed.');
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
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u379350398_smart_tables
DB_USERNAME=u379350398_smart_tables
DB_PASSWORD=Fcatto1950!

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

LOG_CHANNEL=stack
LOG_LEVEL=debug

MERCADOPAGO_RUNTIME=server
MERCADOPAGO_BACK_URL=https://www.smartbarbeiro.com.br
ENV;

if (file_put_contents($envPath, $env) === false) {
    exit('Could not write .env — set laravel/ folder permissions to 755.');
}

echo ".env created at {$envPath}\n";
echo "APP_DEBUG=true temporarily so errors are visible.\n";
echo "Set APP_DEBUG=false in .env after the site works.\n";
echo "DELETE create-env.php now.\n";
