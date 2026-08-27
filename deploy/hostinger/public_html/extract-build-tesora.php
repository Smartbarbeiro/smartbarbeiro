<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);

$zipPath = __DIR__.'/build.zip';
$dest = __DIR__;

echo "zip exists: ".(is_file($zipPath) ? 'yes' : 'no')."\n";
echo "zip size: ".(is_file($zipPath) ? filesize($zipPath) : 0)."\n";
echo "dest writable: ".(is_writable($dest) ? 'yes' : 'no')."\n";

if (! class_exists(ZipArchive::class)) {
    exit("ZipArchive missing\n");
}

$zip = new ZipArchive();
$open = $zip->open($zipPath);
echo "zip open code: {$open}\n";
if ($open !== true) {
    exit("Cannot open build.zip\n");
}

echo "entries: ".$zip->numFiles."\n";
for ($i = 0; $i < min(5, $zip->numFiles); $i++) {
    echo ' - '.$zip->getNameIndex($i)."\n";
}

$ok = $zip->extractTo($dest);
echo 'extractTo: '.($ok ? 'true' : 'false')."\n";
if (! $ok) {
    echo "zip status: ".$zip->getStatusString()."\n";
}
$zip->close();

$manifest = $dest.'/build/manifest.json';
echo 'manifest exists: '.(is_file($manifest) ? 'yes' : 'no')."\n";
if (is_dir($dest.'/build')) {
    echo 'build file count: '.count(glob($dest.'/build/**/*', GLOB_BRACE) ?: [])."\n";
}

require __DIR__.'/_tesora-paths.php';

$envPath = tesora_env_path();
if (is_file($envPath)) {
    $env = file_get_contents($envPath);
    $line = 'PUBLIC_PATH='.tesora_public_path();
    if (preg_match('/^PUBLIC_PATH=.*$/m', $env)) {
        $env = preg_replace('/^PUBLIC_PATH=.*$/m', $line, $env);
    } else {
        $env = rtrim($env)."\n".$line."\n";
    }
    file_put_contents($envPath, $env);
    echo "PUBLIC_PATH set\n";
}

foreach (glob(dirname(__DIR__).'/laravel/bootstrap/cache/*.php') ?: [] as $file) {
    @unlink($file);
}
