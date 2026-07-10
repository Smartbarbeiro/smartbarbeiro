<?php

/**
 * Re-extract laravel.zip with Linux-safe paths (fixes missing controllers).
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(600);

$domainRoot = dirname(__DIR__);
$laravelDir = $domainRoot.'/laravel';
$zipPath = $laravelDir.'/laravel.zip';
$tempDir = $laravelDir.'/.sync-laravel-tmp';

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

try {
    if (! is_file($zipPath)) {
        throw new RuntimeException('Missing laravel/laravel.zip');
    }

    if (! class_exists(ZipArchive::class)) {
        throw new RuntimeException('ZipArchive PHP extension is not available.');
    }

    $zip = new ZipArchive();

    if ($zip->open($zipPath) !== true) {
        throw new RuntimeException('Cannot open laravel.zip');
    }

    echo "Extracting laravel.zip to temp...\n";
    deletePath($tempDir);

    if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
        throw new RuntimeException("Cannot create {$tempDir}");
    }

    $extracted = 0;

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = normalizeRelativePath((string) $zip->getNameIndex($index));

        if ($name === '' || str_ends_with($name, '/')) {
            continue;
        }

        $target = $tempDir.'/'.$name;
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

        $extracted++;
    }

    $zip->close();
    echo "Extracted {$extracted} files to temp.\n";

    echo "Copying into laravel/ (preserving storage/app/public)...\n";
    copyDirectory($tempDir, $laravelDir, ['storage/app/public']);
    deletePath($tempDir);

    $profileController = $laravelDir.'/app/Http/Controllers/ProfileController.php';
    echo 'ProfileController: '.(is_file($profileController) ? 'OK' : 'MISSING')."\n";

    echo "\nNext: clear-cache-smartbarbeiro.php\n";
    echo "DELETE sync-laravel-smartbarbeiro.php when done.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: '.$e->getMessage()."\n";
}
