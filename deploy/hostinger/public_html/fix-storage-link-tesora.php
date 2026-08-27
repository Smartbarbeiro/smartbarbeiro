<?php

/**
 * Ensures public_html/storage points at laravel/storage/app/public (profile photos, uploads).
 * Visit once: https://www.tesora.com.br/fix-storage-link-tesora.php
 * DELETE immediately after.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

function deletePath(string $path): void
{
    if (! file_exists($path)) {
        return;
    }

    if (is_file($path) || is_link($path)) {
        unlink($path);

        return;
    }

    foreach (scandir($path) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        deletePath($path.DIRECTORY_SEPARATOR.$item);
    }

    rmdir($path);
}

function copyDirectory(string $src, string $dest): void
{
    if (! is_dir($src)) {
        return;
    }

    if (! is_dir($dest) && ! mkdir($dest, 0755, true) && ! is_dir($dest)) {
        throw new RuntimeException("Cannot create {$dest}");
    }

    foreach (scandir($src) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $srcPath = $src.DIRECTORY_SEPARATOR.$item;
        $destPath = $dest.DIRECTORY_SEPARATOR.$item;

        if (is_dir($srcPath)) {
            copyDirectory($srcPath, $destPath);

            continue;
        }

        if (! is_file($destPath)) {
            copy($srcPath, $destPath);
            echo "Merged {$item} into laravel storage\n";
        }
    }
}

try {
    $publicRoot = __DIR__;
    $laravelRoot = dirname(__DIR__).'/laravel';

    if (! is_file($laravelRoot.'/vendor/autoload.php') && is_file(dirname(__DIR__).'/laravel/laravel/vendor/autoload.php')) {
        $laravelRoot = dirname(__DIR__).'/laravel/laravel';
    }

    $target = $laravelRoot.'/storage/app/public';
    $link = $publicRoot.'/storage';

    echo "=== Fix storage link ===\n\n";
    echo "Laravel root: {$laravelRoot}\n";
    echo "Target: {$target}\n";
    echo "Link: {$link}\n\n";

    if (! is_dir($target) && ! mkdir($target, 0755, true) && ! is_dir($target)) {
        throw new RuntimeException("Could not create {$target}");
    }

    echo 'Target writable: '.(is_writable($target) ? 'yes' : 'no')."\n";

    if (is_link($link)) {
        if (realpath($link) === realpath($target)) {
            echo "Symlink already correct.\n";
            exit(0);
        }

        unlink($link);
        echo "Removed incorrect symlink.\n";
    }

    if (is_dir($link)) {
        echo "Merging public_html/storage into laravel/storage/app/public...\n";
        copyDirectory($link, $target);
        deletePath($link);
        echo "Removed public_html/storage directory.\n";
    } elseif (is_file($link)) {
        unlink($link);
    }

    $absoluteTarget = realpath($target);

    if ($absoluteTarget === false) {
        throw new RuntimeException('Target path does not resolve');
    }

    if (function_exists('symlink') && @symlink($absoluteTarget, $link)) {
        echo "Symlink created: storage -> {$absoluteTarget}\n";
    } else {
        echo "Symlink unavailable on this host (symlink() disabled).\n";
        echo "Uploads are served via public_html/.htaccess from laravel/storage/app/public.\n";
        echo "Do not create a public_html/storage folder on this host.\n";
    }

    echo "\nChecks:\n";
    echo '  link is symlink: '.(is_link($link) ? 'yes' : 'no')."\n";
    echo '  link resolves correctly: '.(realpath($link) === $absoluteTarget ? 'yes' : 'no')."\n";

    echo "\nDone. Profile photos load at /uploads/profile-photos/...\n";
    echo "DELETE fix-storage-link-tesora.php when done.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: '.$e->getMessage()."\n";
}
