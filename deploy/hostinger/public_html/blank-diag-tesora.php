<?php
header("Content-Type: text/plain; charset=utf-8");
error_reporting(E_ALL);
ini_set("display_errors", "1");
echo "php ok\n";
$laravel = "/home1/fulvio54/laravel";
echo "blade exists=".(is_file($laravel."/resources/views/app.blade.php")?"yes":"no")."\n";
$blade = @file_get_contents($laravel."/resources/views/app.blade.php");
echo "blade has Inter=".(str_contains((string)$blade, "fonts.googleapis.com")?"yes":"no")."\n";
echo "blade has vite=".(str_contains((string)$blade, "@vite")?"yes":"no")."\n";
$manifest = "/home1/fulvio54/public_html/build/manifest.json";
echo "manifest=".(is_file($manifest)?"yes":"no")." size=".(is_file($manifest)?filesize($manifest):0)."\n";
if (is_file($manifest)) {
  $m = json_decode(file_get_contents($manifest), true);
  echo "manifest keys=".count((array)$m)."\n";
  echo "has app.js entry=".(isset($m["resources/js/app.js"])?"yes":"no")."\n";
  if (isset($m["resources/js/app.js"]["css"][0])) echo "css=".$m["resources/js/app.js"]["css"][0]."\n";
  if (isset($m["resources/js/app.js"]["file"])) echo "js=".$m["resources/js/app.js"]["file"]."\n";
}
$log = $laravel."/storage/logs/laravel.log";
if (is_readable($log)) {
  $lines = file($log);
  echo "\n=== last 30 log lines ===\n";
  echo implode("", array_slice($lines, -30));
}