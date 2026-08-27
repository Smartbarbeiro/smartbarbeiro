<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$laravel = dirname(__DIR__).'/laravel';

echo "=== Google OAuth diagnostics ===\n\n";

$envPath = $laravel.'/.env';
if (! is_file($envPath)) {
    echo "FAIL: .env missing\n";
    exit;
}

$env = file_get_contents($envPath) ?: '';

foreach (['APP_URL', 'GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_REDIRECT_URI'] as $key) {
    if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $env, $m)) {
        echo "{$key}: MISSING\n";
        continue;
    }

    $value = trim($m[1], " \t\"'");

    if ($key === 'GOOGLE_CLIENT_SECRET') {
        echo $key.': '.(strlen($value) > 0 ? 'SET ('.strlen($value).' chars)' : 'EMPTY')."\n";
        continue;
    }

    if ($key === 'GOOGLE_CLIENT_ID') {
        $masked = strlen($value) > 12
            ? substr($value, 0, 12).'...'.substr($value, -12)
            : $value;
        echo "{$key}: {$masked}\n";
        continue;
    }

    echo "{$key}: {$value}\n";
}

echo "\nExpected redirect URI:\n";
echo "  https://www.tesora.com.br/oauth-google-return.php\n";
echo "  (legacy) https://www.tesora.com.br/auth/google/callback\n";

$log = $laravel.'/storage/logs/laravel.log';
if (is_readable($log)) {
    $lines = file($log, FILE_IGNORE_NEW_LINES) ?: [];
    $oauthLines = array_values(array_filter($lines, static function ($line) {
        return stripos($line, 'oauth') !== false
            || stripos($line, 'google') !== false
            || stripos($line, 'Socialite') !== false
            || stripos($line, 'redirect_uri') !== false;
    }));

    echo "\n--- recent oauth/google log lines ---\n";
    echo implode("\n", array_slice($oauthLines, -30))."\n";
}

echo "\nDELETE this file when done.\n";
