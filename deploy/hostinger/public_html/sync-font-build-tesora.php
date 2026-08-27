<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
$zipPath = __DIR__.'/font-build.zip';
if (!is_file($zipPath)) { exit("missing font-build.zip\n"); }
$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) { exit("cannot open zip\n"); }
echo "zip entries=".$zip->numFiles."\n";
for ($i = 0; $i < min(5, $zip->numFiles); $i++) {
  echo " entry[$i]=".$zip->getNameIndex($i)."\n";
}
$tmp = sys_get_temp_dir().'/font-build-'.getmypid();
@mkdir($tmp, 0755, true);
if (!$zip->extractTo($tmp)) { exit("extract failed\n"); }
$zip->close();
echo "extracted to $tmp\n";
passthru('ls -la '.escapeshellarg($tmp).' 2>&1 | head -20');
$copied = 0;
$buildSrc = $tmp.'/build';
if (!is_dir($buildSrc)) {
  // fallback: find build dir
  foreach (glob($tmp.'/*') ?: [] as $p) {
    if (is_dir($p.'/build')) { $buildSrc = $p.'/build'; break; }
    if (basename($p) === 'build') { $buildSrc = $p; break; }
  }
}
echo "buildSrc=$buildSrc exists=".(is_dir($buildSrc)?'yes':'no')."\n";
if (is_dir($buildSrc)) {
  $dest = __DIR__.'/build';
  @mkdir($dest.'/assets', 0755, true);
  if (is_file($buildSrc.'/manifest.json')) {
    copy($buildSrc.'/manifest.json', $dest.'/manifest.json');
    echo "manifest ok\n";
  }
  foreach (glob($buildSrc.'/assets/*') ?: [] as $file) {
    if (!is_file($file)) continue;
    if (copy($file, $dest.'/assets/'.basename($file))) $copied++;
  }
  echo "assets copied: $copied\n";
  echo "welcome exists=".(is_file($dest.'/assets/Welcome-tdYrpfKW.js')?'yes':'no')."\n";
}
if (is_file($tmp.'/fonts/kadwa-latin-400-normal.woff2')) {
  @mkdir(__DIR__.'/fonts', 0755, true);
  copy($tmp.'/fonts/kadwa-latin-400-normal.woff2', __DIR__.'/fonts/kadwa-latin-400-normal.woff2');
  echo "kadwa ok\n";
}
$blade = $tmp.'/app.blade.php';
$bladeDest = dirname(__DIR__).'/laravel/resources/views/app.blade.php';
if (is_file($blade)) {
  copy($blade, $bladeDest);
  echo "blade ok\n";
}
$laravel = dirname(__DIR__).'/laravel';
if (is_file($laravel.'/artisan')) {
  passthru('cd '.escapeshellarg($laravel).' && php artisan view:clear && php artisan config:clear && php artisan cache:clear 2>&1');
}
@unlink($zipPath);
echo "DONE\n";