# Full production deploy to tesora.com.br via FTP + browser helpers.
# Usage:
#   $env:HOSTINGER_FTP_PASSWORD = 'your-ftp-password'
#   .\scripts\deploy-hostinger-production.ps1
#   .\scripts\deploy-hostinger-production.ps1 -SkipPrepare

param(
    [switch]$SkipPrepare,
    [string]$SiteBase = ""
)

$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path $PSScriptRoot -Parent
$OutputDir = Join-Path $ProjectRoot "deploy\hostinger\output"
$PrepareScript = Join-Path $PSScriptRoot "prepare-hostinger-deploy.ps1"

$EnvFile = Join-Path $ProjectRoot ".env"

function Get-EnvValue([string]$Key) {
    if (-not (Test-Path $EnvFile)) { return $null }
    foreach ($line in Get-Content $EnvFile) {
        if ($line -match "^$([regex]::Escape($Key))=(.*)$") {
            return $Matches[1].Trim().Trim('"')
        }
    }
    return $null
}

$FtpHost = if ($env:HOSTINGER_FTP_HOST) { $env:HOSTINGER_FTP_HOST } elseif (Get-EnvValue 'HOSTINGER_FTP_HOST') { Get-EnvValue 'HOSTINGER_FTP_HOST' } else { "ftp.fulviolopescatto1787174444000.0970020.meusitehostgator.com.br" }
$FtpUser = if (Get-EnvValue 'HOSTINGER_FTP_USER') { Get-EnvValue 'HOSTINGER_FTP_USER' } elseif ($env:HOSTINGER_FTP_USER) { $env:HOSTINGER_FTP_USER } else { "fulvio@fulviolopescatto1787174444000.0970020.meusitehostgator.com.br" }
$FtpPass = Get-EnvValue 'HOSTINGER_FTP_PASSWORD'
if (-not $FtpPass) { $FtpPass = $env:HOSTINGER_FTP_PASSWORD }
if (-not $FtpPass) {
    throw "Set HOSTINGER_FTP_PASSWORD in .env or your shell before running this script."
}
$RemoteBase = if ($env:HOSTINGER_FTP_REMOTE_BASE) { $env:HOSTINGER_FTP_REMOTE_BASE } elseif (Get-EnvValue 'HOSTINGER_FTP_REMOTE_BASE') { Get-EnvValue 'HOSTINGER_FTP_REMOTE_BASE' } else { "" }
if (-not $SiteBase) {
    $SiteBase = if ($env:TESORA_SITE_BASE) { $env:TESORA_SITE_BASE } elseif (Get-EnvValue 'TESORA_SITE_BASE') { Get-EnvValue 'TESORA_SITE_BASE' } else { "https://www.tesora.com.br" }
}
if ($SiteBase -notmatch '^https://') {
    $SiteBase = 'https://www.tesora.com.br'
    Write-Host "Note: TESORA_SITE_BASE must use https://www.tesora.com.br — using default."
}

function Join-RemotePath([string[]]$Parts) {
    $all = @()
    if ($RemoteBase) { $all += $RemoteBase.Trim('/') }
    $all += $Parts | ForEach-Object { $_.Trim('/') }
    return ($all -join '/')
}

function Send-FtpFile([string]$LocalPath, [string]$RemotePath) {
    if (-not (Test-Path $LocalPath)) {
        throw "Missing file: $LocalPath"
    }

    $uri = "ftp://${FtpHost}/${RemotePath}"
    $sizeMb = [math]::Round((Get-Item $LocalPath).Length / 1MB, 1)
    Write-Host "  uploading $sizeMb MB -> $RemotePath ..."

    # HostGator: FTPS explicit (AUTH TLS) on port 21. Prefer shared IP / brXXXX.hostgator.com.br
    # if the ftp.* hostname resolves through Cloudflare (port 21 will time out).
    & curl.exe -S -k --connect-timeout 120 --max-time 900 --ftp-ssl --ftp-pasv `
        -T $LocalPath `
        --user "${FtpUser}:${FtpPass}" `
        $uri

    if ($LASTEXITCODE -ne 0) {
        throw "FTP upload failed for $RemotePath (curl exit $LASTEXITCODE)"
    }

    Write-Host "  uploaded $sizeMb MB -> $RemotePath"
}

function Invoke-DeployUrl([string]$Path) {
    $url = "$SiteBase/$Path"
    Write-Host ""
    Write-Host "--- $url ---"
    # Prefer curl so we can force Host/IP when DNS is still propagating.
    $resolveHost = ([Uri]$SiteBase).Host
    $args = @(
        '-sS', '--connect-timeout', '60', '--max-time', '600',
        '-H', "Host: $resolveHost"
    )
    if ($env:TESORA_DEPLOY_IP) {
        $port = if (([Uri]$SiteBase).Scheme -eq 'https') { 443 } else { 80 }
        $args += @('--resolve', "${resolveHost}:${port}:$($env:TESORA_DEPLOY_IP)")
    }
    $tmp = [System.IO.Path]::GetTempFileName()
    & curl.exe @args -o $tmp -w "%{http_code}" $url
    $code = $LASTEXITCODE
    $body = Get-Content $tmp -Raw -ErrorAction SilentlyContinue
    Remove-Item $tmp -Force -ErrorAction SilentlyContinue
    if ($body) { Write-Host $body }
    if ($code -ne 0) {
        Write-Host "ERROR: curl exit $code"
        return $false
    }
    return $true
}

function Escape-PhpSingleQuoted([string]$Value) {
    return $Value.Replace('\', '\\').Replace("'", "\'")
}

function New-ProductionSecretsPatch([string]$OutPath) {
    $secretKeys = @(
        'APP_KEY', 'APP_URL', 'ADMIN_EMAIL', 'ADMIN_PASSWORD',
        'DB_CONNECTION', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
        'MAIL_MAILER', 'MAIL_SCHEME', 'MAIL_HOST', 'MAIL_PORT',
        'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
        'MERCADOPAGO_ACCESS_TOKEN', 'MERCADOPAGO_PUBLIC_KEY', 'MERCADOPAGO_CLIENT_ID',
        'MERCADOPAGO_CLIENT_SECRET', 'MERCADOPAGO_WEBHOOK_SECRET', 'MERCADOPAGO_CURRENCY',
        'MERCADOPAGO_RUNTIME', 'MERCADOPAGO_BACK_URL',
        'GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_REDIRECT_URI',
        'STRIPE_KEY', 'STRIPE_SECRET', 'STRIPE_WEBHOOK_SECRET', 'STRIPE_CURRENCY',
        'STRIPE_MERCHANT_DISPLAY_NAME', 'STRIPE_GOOGLE_PAY_TEST_ENV', 'STRIPE_PIX_ENABLED',
        'STRIPE_CONNECT_ENABLED', 'STRIPE_CONNECT_COUNTRY', 'STRIPE_APPLICATION_FEE_PERCENT'
    )

    $phpUpdates = @()
    foreach ($key in $secretKeys) {
        $value = Get-EnvValue $key
        if ($value) {
            $escaped = Escape-PhpSingleQuoted $value
            $phpUpdates += "    '$key' => '$escaped',"
        }
    }

    $phpUpdates += "    'APP_DEBUG' => 'false',"
    $phpUpdates += "    'DB_HOST' => '127.0.0.1',"

    $php = @"
<?php

declare(strict_types=1);

/**
 * Patches production secrets in laravel/.env from local deploy machine.
 * Generated by scripts/deploy-hostinger-production.ps1 — DELETE after use.
 */

header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/_tesora-paths.php';

`$envPath = tesora_env_path();

if (! is_file(`$envPath)) {
    exit(".env not found at {`$envPath}\nRun env-setup-tesora.php first.\n");
}

`$updates = [
$($phpUpdates -join "`n")
    'PUBLIC_PATH' => tesora_public_path(),
];

`$content = file_get_contents(`$envPath);
if (`$content === false) {
    exit("Could not read {`$envPath}\n");
}

foreach (`$updates as `$key => `$value) {
    `$pattern = '/^'.preg_quote(`$key, '/').'=.*$/m';
    `$needsQuotes = in_array(`$key, ['ADMIN_PASSWORD', 'DB_PASSWORD', 'MAIL_PASSWORD'], true)
        && `$value !== ''
        && ! str_starts_with(`$value, '"');
    `$line = `$needsQuotes ? `$key.'="'.`$value.'"' : `$key.'='.`$value;

    if (preg_match(`$pattern, `$content)) {
        `$content = preg_replace(`$pattern, `$line, `$content) ?? `$content;
        echo "updated {`$key}\n";
    } else {
        `$content = rtrim(`$content)."\n".`$line."\n";
        echo "added {`$key}\n";
    }
}

if (file_put_contents(`$envPath, `$content) === false) {
    exit("Could not write {`$envPath}\n");
}

echo "\nProduction secrets patched.\n";
echo "DELETE patch-production-secrets-tesora.php when done.\n";
"@

    $utf8NoBom = New-Object System.Text.UTF8Encoding $false
    [System.IO.File]::WriteAllText($OutPath, $php, $utf8NoBom)
}

Write-Host "==> Building deploy packages..."
if (-not $SkipPrepare) {
    & $PrepareScript
} else {
    Write-Host "  (skipped - using existing zips in deploy/hostinger/output/)"
}

$laravelZip = Join-Path $OutputDir "laravel.zip"
$publicZip = Join-Path $OutputDir "public_html.zip"
$secretsPatch = Join-Path $OutputDir "patch-production-secrets-tesora.php"

Write-Host ""
Write-Host "==> Generating production secrets patch..."
New-ProductionSecretsPatch $secretsPatch

Write-Host ""
Write-Host "==> Uploading zip archives + secrets patch..."
Send-FtpFile $laravelZip (Join-RemotePath @('laravel', 'laravel.zip'))
Send-FtpFile $publicZip (Join-RemotePath @('public_html', 'public_html.zip'))
Send-FtpFile $secretsPatch (Join-RemotePath @('public_html', 'patch-production-secrets-tesora.php'))

Write-Host ""
Write-Host "==> Extracting on server (preserves uploads + images)..."
if (-not (Invoke-DeployUrl "extract-deploy-tesora.php")) {
    throw "Extract step failed."
}

Write-Host ""
Write-Host "==> Creating laravel/.env (if missing)..."
Invoke-DeployUrl "env-setup-tesora.php" | Out-Null

Write-Host ""
Write-Host "==> Patching production secrets from local .env..."
Invoke-DeployUrl "patch-production-secrets-tesora.php" | Out-Null

Write-Host ""
Write-Host "==> Patching production URLs..."
Invoke-DeployUrl "patch-mp-production-tesora.php" | Out-Null

Write-Host ""
Write-Host "==> Syncing Laravel app from zip (Linux-safe paths)..."
if (-not (Invoke-DeployUrl "sync-laravel-tesora.php")) {
    throw "Laravel sync failed."
}

Write-Host ""
Write-Host "==> Patching Stripe Connect env..."
Invoke-DeployUrl "patch-stripe-production-tesora.php" | Out-Null

Write-Host ""
Write-Host "==> Running Laravel setup (storage link, caches)..."
Invoke-DeployUrl "setup-tesora.php" | Out-Null

Write-Host ""
Write-Host "==> Running migrations..."
if (-not (Invoke-DeployUrl "migrate-tesora.php")) {
    throw "Migration step failed."
}

Write-Host ""
Write-Host "==> Clearing Laravel cache..."
Invoke-DeployUrl "clear-cache-tesora.php" | Out-Null

Write-Host ""
Write-Host "==> Syncing Vite build assets..."
if (-not (Invoke-DeployUrl "sync-build-tesora.php")) {
    throw "Vite build sync failed."
}

Write-Host ""
Write-Host "==> Verifying Vite assets..."
Invoke-DeployUrl "fix-vite-tesora.php" | Out-Null

Write-Host ""
Write-Host "==> Fixing storage symlink (profile photos)..."
Invoke-DeployUrl "fix-storage-link-tesora.php" | Out-Null

Write-Host ""
Write-Host "==> Storage diagnostics..."
Invoke-DeployUrl "storage-status-tesora.php" | Out-Null

Write-Host ""
Write-Host "==> Health check..."
try {
    $homeResponse = Invoke-WebRequest -Uri $SiteBase -UseBasicParsing -TimeoutSec 60
    Write-Host "Homepage: HTTP $($homeResponse.StatusCode)"
} catch {
    Write-Host "Homepage check failed: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "Deploy complete: $SiteBase"
Write-Host "Delete *-tesora.php helpers from public_html when finished."
