<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$path = dirname(__DIR__).'/laravel/app/Http/Controllers/BarbershopPlatformSubscribeController.php';
$content = file_get_contents($path);

echo str_contains($content, 'Inertia::location') ? "uses Inertia::location: yes\n" : "uses Inertia::location: NO\n";
echo str_contains($content, 'redirect()->away') ? "uses redirect()->away: yes (BUG)\n" : "uses redirect()->away: no\n";

if (preg_match('/return (Inertia::location|redirect\(\)->away).+checkout/s', $content, $m)) {
    echo 'checkout redirect line: '.$m[0]."\n";
}
