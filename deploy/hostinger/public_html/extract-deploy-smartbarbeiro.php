<?php

/**
 * Extracts laravel.zip + public_html.zip uploaded via FTP, and removes
 * mistaken Laravel app files from public_html.
 *
 * Preserves on redeploy (never overwritten by zip contents):
 *   - laravel/storage/app/public/  (user uploads → /storage/...)
 *   - public_html/images/          (marketing assets + any server-only files)
 *
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

function normalizeRelativePath(string $path): string
{
    $path = str_replace('\\', '/', $path);

    while (str_starts_with($path, './')) {
        $path = substr($path, 2);
    }

    return $path;
}

function shouldExcludePath(string $relativePath, array $excludePrefixes): bool
{
    $relativePath = normalizeRelativePath($relativePath);

    foreach ($excludePrefixes as $prefix) {
        $prefix = normalizeRelativePath(trim($prefix, '/'));

        if ($relativePath === $prefix || str_starts_with($relativePath, $prefix.'/')) {
            return true;
        }
    }

    return false;
}

function copyDirectory(string $src, string $dest, array $excludePrefixes = [], ?string $baseSrc = null): void
{
    if (! is_dir($src)) {
        return;
    }

    $baseSrc ??= $src;

    if (! is_dir($dest) && ! mkdir($dest, 0755, true) && ! is_dir($dest)) {
        throw new RuntimeException("Cannot create {$dest}");
    }

    $items = scandir($src);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $srcPath = $src.DIRECTORY_SEPARATOR.$item;
        $destPath = $dest.DIRECTORY_SEPARATOR.$item;
        $relativePath = normalizeRelativePath(substr($srcPath, strlen($baseSrc) + 1));

        if ($excludePrefixes !== [] && shouldExcludePath($relativePath, $excludePrefixes)) {
            continue;
        }

        if (is_dir($srcPath)) {
            copyDirectory($srcPath, $destPath, $excludePrefixes, $baseSrc);

            continue;
        }

        $destDir = dirname($destPath);
        if (! is_dir($destDir) && ! mkdir($destDir, 0755, true) && ! is_dir($destDir)) {
            throw new RuntimeException("Cannot create {$destDir}");
        }

        if (! copy($srcPath, $destPath)) {
            throw new RuntimeException("Cannot copy {$srcPath} to {$destPath}");
        }
    }
}

function backupDirectory(string $src, string $backup): void
{
    if (! is_dir($src)) {
        return;
    }

    deletePath($backup);
    copyDirectory($src, $backup);
}

function restoreDirectory(string $backup, string $dest): void
{
    if (! is_dir($backup)) {
        return;
    }

    copyDirectory($backup, $dest);
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

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = str_replace('\\', '/', (string) $zip->getNameIndex($index));

        if ($name === '' || str_ends_with($name, '/')) {
            continue;
        }

        $target = $destDir.'/'.$name;
        $targetDir = dirname($target);

        if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
            throw new RuntimeException("Cannot create {$targetDir}");
        }

        $contents = $zip->getFromIndex($index);

        if ($contents === false) {
            throw new RuntimeException("Cannot read {$name} from zip");
        }

        if (file_put_contents($target, $contents) === false) {
            throw new RuntimeException("Cannot write {$target}");
        }
    }

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

    $laravelDir = $domainRoot.'/laravel';
    $laravelTemp = $laravelDir.'/.deploy-extract-tmp';

    echo "\nExtracting laravel.zip...\n";
    deletePath($laravelTemp);
    extractZip($laravelDir.'/laravel.zip', $laravelTemp);
    echo "Preserving laravel/storage/app/public/ (user uploads)...\n";
    copyDirectory($laravelTemp, $laravelDir, ['storage/app/public']);
    deletePath($laravelTemp);
    echo "laravel.zip OK\n";

    $imagesBackup = $publicRoot.'/.deploy-preserve-images';

    echo "\nExtracting public_html.zip...\n";
    echo "Backing up public_html/images/ ...\n";
    backupDirectory($publicRoot.'/images', $imagesBackup);
    echo "Removing stale public_html/build/ (Vite assets)...\n";
    deletePath($publicRoot.'/build');
    extractZip($publicRoot.'/public_html.zip', $publicRoot);
    echo "Restoring public_html/images/ from backup...\n";
    restoreDirectory($imagesBackup, $publicRoot.'/images');
    deletePath($imagesBackup);
    echo "public_html.zip OK\n";

    echo "\nDeploy extract complete.\n";
    echo "Preserved: laravel/storage/app/public/, public_html/images/\n";
    echo "Next:\n";
    echo "1. patch-mp-production-smartbarbeiro.php\n";
    echo "2. clear-cache-smartbarbeiro.php\n";
    echo "3. setup-smartbarbeiro.php\n";
    echo "\nDELETE extract-deploy-smartbarbeiro.php when done.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: '.$e->getMessage()."\n";
}
