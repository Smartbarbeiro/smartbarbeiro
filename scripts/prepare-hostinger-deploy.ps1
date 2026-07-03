# Prepare Hostinger FTP upload folders + zip archives.
# Run from repo root: .\scripts\prepare-hostinger-deploy.ps1

$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path $PSScriptRoot -Parent
$DeployRoot = Join-Path $ProjectRoot "deploy\hostinger\output"
$LaravelOut = Join-Path $DeployRoot "laravel"
$PublicOut = Join-Path $DeployRoot "public_html"

Write-Host "==> Building frontend assets..."
Push-Location $ProjectRoot
npm ci --silent
npm run build
Write-Host "==> Installing PHP dependencies (production)..."
php composer.phar install --no-dev --optimize-autoloader --no-interaction --quiet
Pop-Location

if (Test-Path $DeployRoot) {
    Remove-Item $DeployRoot -Recurse -Force
}
New-Item -ItemType Directory -Path $LaravelOut -Force | Out-Null
New-Item -ItemType Directory -Path $PublicOut -Force | Out-Null

$excludeDirs = @(
    "node_modules", ".git", ".cursor", ".codex", ".idea", ".vscode", ".nova", ".zed",
    ".phpunit.cache", "tests", "deploy", "tmp"
)

Write-Host "==> Copying Laravel app to $LaravelOut"
Get-ChildItem $ProjectRoot -Force | Where-Object {
    $_.Name -notin $excludeDirs -and $_.Name -ne "public"
} | ForEach-Object {
    Copy-Item $_.FullName -Destination $LaravelOut -Recurse -Force
}

# Never ship local secrets.
Remove-Item (Join-Path $LaravelOut ".env") -ErrorAction SilentlyContinue
Remove-Item (Join-Path $LaravelOut ".env.backup") -ErrorAction SilentlyContinue
Remove-Item (Join-Path $LaravelOut ".env.production") -ErrorAction SilentlyContinue

Get-ChildItem (Join-Path $LaravelOut "bootstrap\cache\*.php") -ErrorAction SilentlyContinue | Remove-Item -Force

Write-Host "==> Copying public/ to $PublicOut"
Copy-Item (Join-Path $ProjectRoot "public\*") $PublicOut -Recurse -Force
Get-ChildItem (Join-Path $ProjectRoot "deploy\hostinger\public_html\*.php") | ForEach-Object {
    Copy-Item $_.FullName (Join-Path $PublicOut $_.Name) -Force
}
Copy-Item (Join-Path $ProjectRoot "deploy\hostinger\public_html\storage.htaccess.template") (Join-Path $PublicOut "storage.htaccess.template") -Force

$zipLaravel = Join-Path $DeployRoot "laravel.zip"
$zipPublic = Join-Path $DeployRoot "public_html.zip"
if (Test-Path $zipLaravel) { Remove-Item $zipLaravel -Force }
if (Test-Path $zipPublic) { Remove-Item $zipPublic -Force }

Write-Host "==> Creating zip archives..."
Compress-Archive -Path (Join-Path $LaravelOut "*") -DestinationPath $zipLaravel -CompressionLevel Optimal
Compress-Archive -Path (Join-Path $PublicOut "*") -DestinationPath $zipPublic -CompressionLevel Optimal

Write-Host ""
Write-Host "Done. Upload via FTP:"
Write-Host "  laravel.zip     -> /domains/smartbarbeiro.com.br/laravel/"
Write-Host "  public_html.zip -> /domains/smartbarbeiro.com.br/public_html/"
Write-Host ""
Write-Host "FTP host: ftp.smartbarbeiro.com.br (or smartbarbeiro.com.br)"
Write-Host "FTP user: u379350398"
Write-Host "FTP port: 21"
Write-Host ""
Write-Host "Redeploy (safe - user uploads preserved):"
Write-Host "  1. Upload laravel.zip + public_html.zip via FTP"
Write-Host "  2. Visit extract-deploy-smartbarbeiro.php (skips laravel/storage/app/public/,"
Write-Host "     backs up + restores public_html/images/)"
Write-Host "  3. patch-mp-production-smartbarbeiro.php, clear-cache-smartbarbeiro.php, setup-smartbarbeiro.php"
Write-Host ""
Write-Host "After first deploy, create .env in laravel/ from deploy/hostinger/env.production.example"
Write-Host "No SSH? Upload helper scripts from deploy/hostinger/public_html/"
Write-Host "  extract-deploy-smartbarbeiro.php, fix-env-format-smartbarbeiro.php, fix-vite-smartbarbeiro.php"
Write-Host "  setup-smartbarbeiro.php, patch-mp-production-smartbarbeiro.php"
Write-Host "Delete all *-smartbarbeiro.php helpers from public_html after deploy."
