<?php
// Fixes broken DB_* lines in laravel/.env (HostGator)
// https://www.tesora.com.br/fix-db-tesora.php

header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/_tesora-paths.php';

$envFile = tesora_env_path();

if (! is_file($envFile)) {
    exit('.env not found');
}

$raw = file_get_contents($envFile);
// Normalize line endings
$raw = str_replace("\r\n", "\n", $raw);
$raw = str_replace("\r", "\n", $raw);

$lines = explode("\n", $raw);
$out = [];
$keys = [
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_DATABASE' => 'fulvio54_tesora',
    'DB_USERNAME' => 'fulvio54_admin',
    'DB_PASSWORD' => '"fcatto1950"',
    'DB_CONNECTION' => 'mysql',
];

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        $out[] = $line;
        continue;
    }
    if (! str_contains($line, '=')) {
        continue;
    }
    [$key] = explode('=', $line, 2);
    $key = trim($key);
    if (isset($keys[$key])) {
        continue; // drop old broken line
    }
    $out[] = $line;
}

$dbBlock = [];
foreach ($keys as $k => $v) {
    $dbBlock[] = $k.'='.$v;
}

$final = implode("\n", $out);
if (! str_contains($final, 'DB_CONNECTION=mysql')) {
    $final .= "\n\n".implode("\n", $dbBlock);
} else {
    $final = preg_replace(
        '/DB_CONNECTION=mysql/',
        implode("\n", $dbBlock),
        $final,
        1
    );
}

file_put_contents($envFile, $final."\n");

echo "Fixed .env database lines:\n";
foreach ($keys as $k => $v) {
    echo '  '.$k.'='.($k === 'DB_PASSWORD' ? '***' : $v)."\n";
}

echo "\nTesting connection...\n";
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=fulvio54_tesora;charset=utf8mb4',
        'fulvio54_admin',
        'fcatto1950',
        [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Database: OK\n\n";
    echo "Next: boot-step-tesora.php\n";
    echo "DELETE fix-db-tesora.php when done.\n";
} catch (Throwable $e) {
    echo 'Database still failing: '.$e->getMessage()."\n";
    echo "Confirm MySQL user is assigned to fulvio54_tesora in cPanel.\n";
}
