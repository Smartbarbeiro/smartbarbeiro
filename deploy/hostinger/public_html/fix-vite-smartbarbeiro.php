<?php
// Fixes Vite manifest path on Hostinger split layout.
// https://www.smartbarbeiro.com.br/fix-vite-smartbarbeiro.php

header('Content-Type: text/plain; charset=utf-8');

$laravel = dirname(__DIR__).'/laravel';
$publicHtml = dirname(__DIR__).'/public_html';

echo "=== Fix Vite manifest ===\n\n";

// 1. Clear config cache (env() stops working after config:cache)
foreach (glob($laravel.'/bootstrap/cache/*.php') ?: [] as $file) {
    @unlink($file);
    echo 'Deleted cache: '.basename($file)."\n";
}

// 2. Ensure build/manifest exists in public_html
$manifest = $publicHtml.'/build/manifest.json';
if (! is_file($manifest)) {
    echo "\nFAIL: {$manifest} not found.\n";
    echo "Re-upload public_html.zip and extract (must include build/ folder).\n";
    exit;
}
echo "\nmanifest.json: OK ({$manifest})\n";

$imagesDir = $publicHtml.'/images';
if (! is_dir($imagesDir) || ! is_file($imagesDir.'/logo.png')) {
    echo "\nWARN: public_html/images/ is missing. Upload public/images via FTP.\n";
}

// 3. Patch config/app.php
$configFile = $laravel.'/config/app.php';
$config = file_get_contents($configFile);

if (! str_contains($config, "'public_path'")) {
    $config = preg_replace(
        '/\n\];\s*$/',
        "\n\n    'public_path' => env('PUBLIC_PATH'),\n];\n",
        $config,
        1,
        $count
    );
    if ($count) {
        file_put_contents($configFile, $config);
        echo "Patched config/app.php (public_path)\n";
    } else {
        echo "Could not patch config/app.php — upload config/app.php via FTP\n";
    }
} else {
    echo "config/app.php already has public_path\n";
}

// 4. Patch AppServiceProvider
$providerFile = $laravel.'/app/Providers/AppServiceProvider.php';
$provider = file_get_contents($providerFile);

$provider = str_replace(
    "env('PUBLIC_PATH')",
    "config('app.public_path')",
    $provider
);

$provider = str_replace(
    "if (! \$this->app->runningInConsole()) {\n            \$request = request();",
    "if (! \$this->app->runningInConsole() && \$this->app->bound('request')) {\n            \$request = \$this->app->make('request');",
    $provider
);

file_put_contents($providerFile, $provider);
echo "Patched AppServiceProvider.php\n";

// 5. Fallback: also link/copy manifest into laravel/public/build
$fallbackDir = $laravel.'/public/build';
if (! is_dir($fallbackDir)) {
    mkdir($fallbackDir, 0755, true);
}
$fallbackManifest = $fallbackDir.'/manifest.json';
if (! is_file($fallbackManifest)) {
    copy($manifest, $fallbackManifest);
    echo "Copied manifest to laravel/public/build/ (fallback)\n";
}

echo "\nDone. Open https://www.smartbarbeiro.com.br\n";
echo "DELETE fix-vite-smartbarbeiro.php when the site works.\n";
