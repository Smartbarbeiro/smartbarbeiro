<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$laravel = dirname(__DIR__).'/laravel';
if (! is_file($laravel.'/vendor/autoload.php')) {
    $laravel = dirname(__DIR__).'/laravel/laravel';
}

echo "laravel: {$laravel}\n";
echo 'web.php mentions storage.public: '.(str_contains(file_get_contents($laravel.'/routes/web.php'), 'storage.public') ? 'yes' : 'no')."\n";
echo 'PublicStorageController file: '.(is_file($laravel.'/app/Http/Controllers/PublicStorageController.php') ? 'yes' : 'no')."\n";
echo 'class exists: '.(class_exists('App\\Http\\Controllers\\PublicStorageController') ? 'no until autoload' : 'n/a')."\n";

require $laravel.'/vendor/autoload.php';
echo 'class exists after autoload: '.(class_exists(App\Http\Controllers\PublicStorageController::class) ? 'yes' : 'no')."\n";

$app = require $laravel.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$path = 'profile-photos/2/2Vm07UuBgT46RAlAdShuvnkC42gij1U3rqrXG26g.png';
echo 'storage exists: '.(Illuminate\Support\Facades\Storage::disk('public')->exists($path) ? 'yes' : 'no')."\n";
echo 'route has storage.public: '.(Illuminate\Support\Facades\Route::has('storage.public') ? 'yes' : 'no')."\n";

$names = collect(Illuminate\Support\Facades\Route::getRoutes())
    ->map(fn ($route) => $route->uri().' ['.($route->getName() ?? '-').']')
    ->filter(fn ($line) => str_contains($line, 'storage'))
    ->values();

echo "storage routes:\n";
foreach ($names as $line) {
    echo "  {$line}\n";
}

$request = Illuminate\Http\Request::create('/storage/'.$path, 'GET');

try {
    $matched = Illuminate\Support\Facades\Route::getRoutes()->match($request);
    echo 'matched route: '.$matched->getName()."\n";
} catch (Throwable $e) {
    echo 'match error: '.$e->getMessage()."\n";
}
