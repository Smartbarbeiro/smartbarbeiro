<?php

/**
 * Patches Hostinger SMTP settings in laravel/.env.
 *
 * Visit once:
 *   https://www.smartbarbeiro.com.br/patch-mail-smtp-smartbarbeiro.php?key=smartbarbeiro-mail-2026&password=YOUR_MAILBOX_PASSWORD
 *
 * Optional overrides:
 *   &email=sac@smartbarbeiro.com.br
 *   &from_name=Smart%20Barbeiro
 *
 * DELETE immediately after success.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$expectedKey = 'smartbarbeiro-mail-2026';
$key = (string) ($_GET['key'] ?? '');
$password = (string) ($_GET['password'] ?? '');
$email = trim((string) ($_GET['email'] ?? 'sac@smartbarbeiro.com.br'));
$fromName = trim((string) ($_GET['from_name'] ?? 'Smart Barbeiro'));

if (! hash_equals($expectedKey, $key) || $password === '' || $email === '') {
    http_response_code(403);
    exit("Forbidden. Use ?key=...&password=MAILBOX_PASSWORD\n");
}

$laravelRoot = dirname(__DIR__).'/laravel';
if (! is_file($laravelRoot.'/vendor/autoload.php') && is_file(dirname(__DIR__).'/laravel/laravel/vendor/autoload.php')) {
    $laravelRoot = dirname(__DIR__).'/laravel/laravel';
}

$envPath = $laravelRoot.'/.env';

if (! is_file($envPath)) {
    exit(".env not found at {$envPath}\n");
}

$content = file_get_contents($envPath);
if ($content === false) {
    exit("Could not read {$envPath}\n");
}

$updates = [
    'MAIL_MAILER' => 'smtp',
    'MAIL_SCHEME' => 'smtps',
    'MAIL_HOST' => 'smtp.hostinger.com',
    'MAIL_PORT' => '465',
    'MAIL_USERNAME' => $email,
    'MAIL_PASSWORD' => $password,
    'MAIL_FROM_ADDRESS' => '"'.$email.'"',
    'MAIL_FROM_NAME' => '"'.$fromName.'"',
];

foreach ($updates as $envKey => $value) {
    $pattern = '/^'.preg_quote($envKey, '/').'=.*$/m';
    $line = $envKey.'='.$value;

    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, $line, $content) ?? $content;
        echo "updated {$envKey}\n";
    } else {
        $content = rtrim($content)."\n".$line."\n";
        echo "added {$envKey}\n";
    }
}

if (file_put_contents($envPath, $content) === false) {
    exit("Could not write {$envPath}\n");
}

echo "\nSMTP configured for {$email} via smtp.hostinger.com:465 (smtps).\n";
echo "Next:\n";
echo "1. https://www.smartbarbeiro.com.br/clear-cache-smartbarbeiro.php\n";
echo "2. https://www.smartbarbeiro.com.br/test-mail-smartbarbeiro.php?key=smartbarbeiro-mail-2026&to=YOUR_INBOX\n";
echo "DELETE patch-mail-smtp-smartbarbeiro.php when done.\n";
