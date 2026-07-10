<?php

/**
 * Extract only public_html/build/ from public_html.zip (Hostinger-safe).
 * Visit once after FTP upload of public_html.zip, then delete.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(600);

$publicRoot = __DIR__;
$zipPath = $publicRoot.'/public_html.zip';
$buildDir = $publicRoot.'/build';

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

function normalizeZipEntryPath(string $name): string
{
    $name = str_replace('\\', '/', $name);

    while (str_starts_with($name, './')) {
        $name = substr($name, 2);
    }

    return $name;
}

try {
    if (! is_file($zipPath)) {
        throw new RuntimeException('Missing public_html.zip — upload it via FTP first.');
    }

    if (! class_exists(ZipArchive::class)) {
        throw new RuntimeException('ZipArchive PHP extension is not available.');
    }

    $zip = new ZipArchive();

    if ($zip->open($zipPath) !== true) {
        throw new RuntimeException('Cannot open public_html.zip');
    }

    echo "Removing existing build/ ...\n";
    deletePath($buildDir);

    $extracted = 0;

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = normalizeZipEntryPath((string) $zip->getNameIndex($index));

        if ($name === '' || ! str_starts_with($name, 'build/')) {
            continue;
        }

        if (str_ends_with($name, '/')) {
            continue;
        }

        $target = $publicRoot.'/'.$name;
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

    $manifest = $buildDir.'/manifest.json';

    if (! is_file($manifest)) {
        throw new RuntimeException('build/manifest.json missing after extract.');
    }

    echo "Extracted {$extracted} build entries.\n";
    echo 'manifest.json bytes: '.filesize($manifest)."\n";
    echo "\nNext: fix-vite-smartbarbeiro.php, clear-cache-smartbarbeiro.php\n";
    echo "DELETE sync-build-smartbarbeiro.php when done.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: '.$e->getMessage()."\n";
}
