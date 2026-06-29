<?php
// Fixes broken DB_HOST line in laravel/.env
// https://www.smartbarbeiro.com.br/fix-db-smartbarbeiro.php

header('Content-Type: text/plain; charset=utf-8');

$envFile = dirname(__DIR__).'/laravel/.env';

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
    'DB_DATABASE' => 'u379350398_smart_tables',
    'DB_USERNAME' => 'u379350398_smart_tables',
    'DB_PASSWORD' => '"Fcatto1950!"',
    'DB_CONNECTION' => 'mysql',
];

$seen = [];
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
        $seen[$key] = true;
        continue; // drop old broken line
    }
    $out[] = $line;
}

// Insert DB block after DB_CONNECTION or at end
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
    echo "  {$k}=".($k === 'DB_PASSWORD' ? '***' : $v)."\n";
}

echo "\nTesting connection...\n";
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=u379350398_smart_tables;charset=utf8mb4',
        'u379350398_smart_tables',
        'Fcatto1950!',
        [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Database: OK\n\n";
    echo "Next: boot-step-smartbarbeiro.php\n";
    echo "DELETE fix-db-smartbarbeiro.php when done.\n";
} catch (Throwable $e) {
    echo 'Database still failing: '.$e->getMessage()."\n";
    echo "Try DB_HOST=srv1940.hstgr.io in File Manager if 127.0.0.1 fails.\n";
}
