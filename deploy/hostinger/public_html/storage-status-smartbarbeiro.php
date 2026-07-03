<?php

/**
 * Read-only storage / profile photo diagnostics.
 * Visit: https://www.smartbarbeiro.com.br/storage-status-smartbarbeiro.php
 * DELETE immediately after.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$laravelRoot = dirname(__DIR__).'/laravel';
if (! is_file($laravelRoot.'/vendor/autoload.php') && is_file(dirname(__DIR__).'/laravel/laravel/vendor/autoload.php')) {
    $laravelRoot = dirname(__DIR__).'/laravel/laravel';
}

require $laravelRoot.'/vendor/autoload.php';

$app = require $laravelRoot.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$publicRoot = __DIR__;
$storagePublic = $laravelRoot.'/storage/app/public';
$publicLink = $publicRoot.'/storage';

echo "=== Storage status ===\n\n";
echo 'public_path: '.public_path()."\n";
echo 'storage_public: '.$storagePublic."\n";
echo 'public_html/storage exists: '.(file_exists($publicLink) ? 'yes' : 'no')."\n";
echo 'public_html/storage is symlink: '.(is_link($publicLink) ? 'yes' : 'no')."\n";
echo 'storage/app/public writable: '.(is_writable($storagePublic) ? 'yes' : 'no')."\n";
echo 'upload_max_filesize: '.ini_get('upload_max_filesize')."\n";
echo 'post_max_size: '.ini_get('post_max_size')."\n";

if (is_link($publicLink)) {
    echo 'symlink target: '.readlink($publicLink)."\n";
}

$barbershopsWithPhotos = App\Models\User::query()
    ->where('is_barbershop', true)
    ->whereNotNull('profile_photo_path')
    ->count();

echo 'barbershops_with_photo_path: '.$barbershopsWithPhotos."\n";

$latest = App\Models\User::query()
    ->where('is_barbershop', true)
    ->whereNotNull('profile_photo_path')
    ->latest('updated_at')
    ->first();

if ($latest) {
    echo 'latest_photo_user: '.$latest->username."\n";
    echo 'latest_photo_path: '.$latest->profile_photo_path."\n";
    echo 'latest_photo_url: '.$latest->profile_photo_url."\n";
    $diskPath = $storagePublic.'/'.ltrim($latest->profile_photo_path, '/');
    echo 'file_on_disk: '.(is_file($diskPath) ? 'yes' : 'no')."\n";
    if (is_file($diskPath)) {
        echo 'file_size_bytes: '.filesize($diskPath)."\n";
    }
    echo 'storage_disk_exists: '.(Illuminate\Support\Facades\Storage::disk('public')->exists($latest->profile_photo_path) ? 'yes' : 'no')."\n";
}

echo 'route_storage_public: '.(Illuminate\Support\Facades\Route::has('storage.public') ? 'yes' : 'no')."\n";

echo "\nIf file_on_disk=yes but avatar broken, run fix-storage-link-smartbarbeiro.php\n";
echo "DELETE storage-status-smartbarbeiro.php when done.\n";
