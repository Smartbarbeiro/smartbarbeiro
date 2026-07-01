<?php

/**
 * Extracts laravel.zip + public_html.zip uploaded via FTP, and removes
 * mistaken Laravel app files from public_html.
 * Visit once: https://www.smartbarbeiro.com.br/extract-deploy-smartbarbeiro.php
 * DELETE immediately after.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(600);

$domainRoot = dirname(__DIR__);
$publicRoot = __DIR__;

function deletePath(string $path): void
{
    if (! file_exists($path)) {
        return;
    }

    if (is_file($path) || is_link($path)) {
        unlink($path);

        return;
    }

    $items = scandir($path);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        deletePath($path.DIRECTORY_SEPARATOR.$item);
    }

    rmdir($path);
}

function extractZip(string $zipPath, string $destDir): void
{
    if (! is_file($zipPath)) {
        throw new RuntimeException("Missing {$zipPath}");
    }

    if (! class_exists(ZipArchive::class)) {
        throw new RuntimeException('ZipArchive PHP extension is not available.');
    }

    $zip = new ZipArchive();

    if ($zip->open($zipPath) !== true) {
        throw new RuntimeException("Cannot open {$zipPath}");
    }

    if (! is_dir($destDir) && ! mkdir($destDir, 0755, true) && ! is_dir($destDir)) {
        throw new RuntimeException("Cannot create {$destDir}");
    }

    $zip->extractTo($destDir);
    $zip->close();
}

try {
    foreach ([
        'app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'tests',
        'vendor', 'deploy', 'node_modules', '.git', 'public', 'scripts', 'tmp',
    ] as $name) {
        $path = $publicRoot.'/'.$name;

        if (file_exists($path)) {
            deletePath($path);
            echo "removed public_html/{$name}\n";
        }
    }

    foreach ([
        'artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
        'phpunit.xml', 'vite.config.js', 'tailwind.config.js', 'postcss.config.js',
        'jsconfig.json', 'README.md', '.editorconfig', '.gitattributes', '.gitignore',
        '.npmrc', '.env.example', 'tmp-dashboard.html', 'tmp-pen.html', 'tmp-pen.js',
        'tmp-pen.css', 'composer.phar',
    ] as $file) {
        $path = $publicRoot.'/'.$file;

        if (is_file($path)) {
            unlink($path);
            echo "removed public_html/{$file}\n";
        }
    }

    echo "\nExtracting laravel.zip...\n";
    extractZip($domainRoot.'/laravel/laravel.zip', $domainRoot.'/laravel/');
    echo "laravel.zip OK\n";

    echo "\nExtracting public_html.zip...\n";
    extractZip($publicRoot.'/public_html.zip', $publicRoot);
    echo "public_html.zip OK\n";

    echo "\nDeploy extract complete.\n";
    echo "Next:\n";
    echo "1. patch-mp-production-smartbarbeiro.php\n";
    echo "2. clear-cache-smartbarbeiro.php\n";
    echo "3. setup-smartbarbeiro.php\n";
    echo "\nDELETE extract-deploy-smartbarbeiro.php when done.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: '.$e->getMessage()."\n";
}
