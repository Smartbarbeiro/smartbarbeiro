<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$zipPath = __DIR__.'/public_html.zip';

function normalizeZipEntryPath(string $name): string
{
    $name = str_replace('\\', '/', $name);

    while (str_starts_with($name, './')) {
        $name = substr($name, 2);
    }

    return $name;
}

if (! is_file($zipPath)) {
    echo "Missing public_html.zip\n";
    exit;
}

$zip = new ZipArchive();
$zip->open($zipPath);

echo 'Zip entries: '.$zip->numFiles."\n\n";

for ($index = 0; $index < min($zip->numFiles, 40); $index++) {
    echo $index.': '.$zip->getNameIndex($index)."\n";
}

$buildCount = 0;

for ($index = 0; $index < $zip->numFiles; $index++) {
    $name = normalizeZipEntryPath((string) $zip->getNameIndex($index));

    if (str_starts_with($name, 'build/')) {
        $buildCount++;
    }
}

echo "\nbuild/* entries: {$buildCount}\n";
echo 'zip size bytes: '.filesize($zipPath)."\n";

$zip->close();
