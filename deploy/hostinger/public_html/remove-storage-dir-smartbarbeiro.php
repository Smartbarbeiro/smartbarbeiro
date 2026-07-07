<?php

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

$link = __DIR__.'/storage';

if (file_exists($link)) {
    deletePath($link);
    echo "Removed public_html/storage\n";
} else {
    echo "public_html/storage already absent\n";
}

echo "Uploads are served via root .htaccess from laravel/storage/app/public\n";
echo "DELETE remove-storage-dir-smartbarbeiro.php when done.\n";
