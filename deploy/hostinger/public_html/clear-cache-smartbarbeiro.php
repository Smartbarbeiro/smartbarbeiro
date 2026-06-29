<?php
header('Content-Type: text/plain; charset=utf-8');
$cache = dirname(__DIR__).'/laravel/bootstrap/cache';
foreach (glob($cache.'/*.php') ?: [] as $file) {
    @unlink($file);
    echo 'Deleted '.basename($file)."\n";
}
echo "Done. Run boot-step-smartbarbeiro.php\n";
