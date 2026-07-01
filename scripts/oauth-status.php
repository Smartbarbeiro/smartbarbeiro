<?php

/**
 * Read-only Google OAuth integration status.
 */
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$socialAuth = app(App\Services\SocialAuthService::class);

echo '=== Google OAuth status ==='.PHP_EOL;
echo 'configured: '.($socialAuth->isGoogleEnabled() ? 'yes' : 'no').PHP_EOL;
echo 'client_id: '.(config('services.google.client_id') ? 'set' : 'missing').PHP_EOL;
echo 'client_secret: '.(config('services.google.client_secret') ? 'set' : 'missing').PHP_EOL;
echo 'redirect_env: '.(config('services.google.redirect') ?: 'auto').PHP_EOL;
echo 'mercadopago_back_url: '.config('mercadopago.back_url').PHP_EOL;

if (! $app->runningInConsole()) {
    echo 'callback_dynamic: '.url('/auth/google/callback').PHP_EOL;
} else {
    $base = rtrim((string) config('app.url'), '/');

    echo PHP_EOL.'Add these Authorized redirect URIs in Google Cloud Console:'.PHP_EOL;
    echo "  {$base}/auth/google/callback".PHP_EOL;
    echo '  http://127.0.0.1:8000/auth/google/callback'.PHP_EOL;
    echo '  http://localhost:8000/auth/google/callback'.PHP_EOL;
    echo PHP_EOL.'Add these Authorized JavaScript origins:'.PHP_EOL;
    echo "  {$base}".PHP_EOL;
    echo '  http://127.0.0.1:8000'.PHP_EOL;
    echo '  http://localhost:8000'.PHP_EOL;
    echo PHP_EOL.'Mercado Pago webhook URL:'.PHP_EOL;
    echo "  {$base}/webhooks/mercadopago".PHP_EOL;
}
