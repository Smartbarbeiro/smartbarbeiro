<?php
// Patches AppServiceProvider on server (fixes request() during boot).
// https://www.tesora.com.br/patch-app-provider-tesora.php

header('Content-Type: text/plain; charset=utf-8');

$file = dirname(__DIR__).'/laravel/app/Providers/AppServiceProvider.php';

if (! is_file($file)) {
    exit('AppServiceProvider.php not found');
}

$code = file_get_contents($file);

$old = <<<'PHP'
        if (! $this->app->runningInConsole()) {
            $request = request();
PHP;

$new = <<<'PHP'
        if (! $this->app->runningInConsole() && $this->app->bound('request')) {
            $request = $this->app->make('request');
PHP;

if (str_contains($code, $new)) {
    echo "Already patched.\n";
} elseif (str_contains($code, $old)) {
    file_put_contents($file, str_replace($old, $new, $code));
    echo "AppServiceProvider patched.\n";
} else {
    echo "Could not patch automatically. Upload app/Providers/AppServiceProvider.php via FTP.\n";
    exit;
}

echo "\nNext:\n";
echo "1. clear-cache-tesora.php\n";
echo "2. setup-tesora.php\n";
echo "3. https://www.tesora.com.br\n";
echo "\nDELETE patch-app-provider-tesora.php when done.\n";
