<?php

/**
 * Sends a test email using production mail config.
 *
 * Visit once:
 *   https://www.tesora.com.br/test-mail-tesora.php?key=tesora-mail-2026&to=you@example.com
 *
 * DELETE immediately after success.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$expectedKey = 'tesora-mail-2026';
$key = (string) ($_GET['key'] ?? '');
$to = trim((string) ($_GET['to'] ?? ''));

if (! hash_equals($expectedKey, $key) || $to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
    http_response_code(403);
    exit("Forbidden. Use ?key=...&to=you@example.com\n");
}

$laravelRoot = dirname(__DIR__).'/laravel';
if (! is_file($laravelRoot.'/vendor/autoload.php') && is_file(dirname(__DIR__).'/laravel/laravel/vendor/autoload.php')) {
    $laravelRoot = dirname(__DIR__).'/laravel/laravel';
}

require $laravelRoot.'/vendor/autoload.php';
/** @var \Illuminate\Foundation\Application $app */
$app = require $laravelRoot.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "mailer: ".config('mail.default')."\n";
echo "host: ".config('mail.mailers.smtp.host')."\n";
echo "port: ".config('mail.mailers.smtp.port')."\n";
echo "scheme: ".(config('mail.mailers.smtp.scheme') ?: 'null')."\n";
echo "username: ".config('mail.mailers.smtp.username')."\n";
echo "from: ".config('mail.from.address')." (".config('mail.from.name').")\n";
echo "to: {$to}\n\n";

try {
    Illuminate\Support\Facades\Mail::raw(
        "Teste SMTP Tesora em ".now()->toDateTimeString().".\n\nSe você recebeu este e-mail, o SMTP está configurado corretamente.",
        function ($message) use ($to) {
            $message->to($to)->subject('Tesora — teste SMTP');
        },
    );

    echo "OK: test email accepted by mailer.\n";
    echo "Check inbox (and spam) for: {$to}\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "FAIL: ".$e->getMessage()."\n";
}

echo "\nDELETE test-mail-tesora.php when done.\n";
