<?php

/**
 * Patches HostGator SMTP settings in laravel/.env.
 *
 * Visit once:
 *   https://www.tesora.com.br/patch-mail-smtp-tesora.php?key=tesora-mail-2026&password=YOUR_MAILBOX_PASSWORD
 *
 * Optional overrides:
 *   &email=atendimento@tesora.com.br
 *   &from_name=Tesora
 *
 * DELETE immediately after success.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/_tesora-paths.php';

$expectedKey = 'tesora-mail-2026';
$key = (string) ($_GET['key'] ?? '');
$password = (string) ($_GET['password'] ?? '');
$email = trim((string) ($_GET['email'] ?? 'atendimento@tesora.com.br'));
$fromName = trim((string) ($_GET['from_name'] ?? 'Tesora'));
$mailHost = trim((string) ($_GET['host'] ?? 'mail.tesora.com.br'));

if (! hash_equals($expectedKey, $key) || $password === '' || $email === '') {
    http_response_code(403);
    exit("Forbidden. Use ?key=...&password=MAILBOX_PASSWORD\n");
}

$envPath = tesora_env_path();

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
    'MAIL_HOST' => $mailHost,
    'MAIL_PORT' => '465',
    'MAIL_USERNAME' => $email,
    'MAIL_PASSWORD' => $password,
    'MAIL_FROM_ADDRESS' => '"'.$email.'"',
    'MAIL_FROM_NAME' => '"'.$fromName.'"',
];

foreach ($updates as $envKey => $value) {
    $pattern = '/^'.preg_quote($envKey, '/').'=.*$/m';
    $needsQuotes = $envKey === 'MAIL_PASSWORD' && $value !== '' && ! str_starts_with($value, '"');
    $line = $needsQuotes ? $envKey.'="'.$value.'"' : $envKey.'='.$value;

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

echo "\nSMTP configured for {$email} via {$mailHost}:465 (smtps).\n";
echo "Next:\n";
echo "1. https://www.tesora.com.br/clear-cache-tesora.php\n";
echo "2. https://www.tesora.com.br/test-mail-tesora.php?key=tesora-mail-2026&to=YOUR_INBOX\n";
echo "DELETE patch-mail-smtp-tesora.php when done.\n";
