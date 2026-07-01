# Push payment hotfix + helpers to Hostinger via FTP, then run server checks.
# Usage: .\scripts\push-hostinger-hotfix.ps1

$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path $PSScriptRoot -Parent
$HelpersDir = Join-Path $ProjectRoot "deploy\hostinger\public_html"
$OutputDir = Join-Path $ProjectRoot "deploy\hostinger\output\hotfix"
$EnvFile = Join-Path $ProjectRoot ".env"

$FtpHost = if ($env:HOSTINGER_FTP_HOST) { $env:HOSTINGER_FTP_HOST } else { "smartbarbeiro.com.br" }
$FtpUser = if ($env:HOSTINGER_FTP_USER) { $env:HOSTINGER_FTP_USER } else { "u379350398" }
$FtpPass = $env:HOSTINGER_FTP_PASSWORD
if (-not $FtpPass) {
    throw "Set HOSTINGER_FTP_PASSWORD before running this script."
}
$RemoteBase = "domains/smartbarbeiro.com.br"

function Get-EnvValue([string]$Key) {
    if (-not (Test-Path $EnvFile)) { return $null }
    foreach ($line in Get-Content $EnvFile) {
        if ($line -match "^$([regex]::Escape($Key))=(.*)$") {
            return $Matches[1].Trim().Trim('"')
        }
    }
    return $null
}

function Send-FtpFile([string]$LocalPath, [string]$RemotePath) {
    $uri = "ftp://$FtpHost/$RemotePath"
    $request = [System.Net.FtpWebRequest]::Create($uri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $request.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
    $request.UseBinary = $true
    $request.UsePassive = $true
    $bytes = [System.IO.File]::ReadAllBytes($LocalPath)
    $request.ContentLength = $bytes.Length
    $stream = $request.GetRequestStream()
    $stream.Write($bytes, 0, $bytes.Length)
    $stream.Close()
    $response = $request.GetResponse()
    Write-Host "  uploaded -> $RemotePath ($($response.StatusDescription.Trim()))"
    $response.Close()
}

New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null

$mpKeys = @(
    "MERCADOPAGO_ACCESS_TOKEN",
    "MERCADOPAGO_PUBLIC_KEY",
    "MERCADOPAGO_CLIENT_ID",
    "MERCADOPAGO_CLIENT_SECRET",
    "MERCADOPAGO_WEBHOOK_SECRET"
)

$phpUpdates = @()
foreach ($key in $mpKeys) {
    $value = Get-EnvValue $key
    if ($value) {
        $escaped = $value.Replace("\", "\\").Replace("'", "\'")
        $phpUpdates += "    '$key' => '$escaped',"
    }
}

$secretsPhp = @"
<?php

/**
 * Patches MERCADOPAGO_* secrets in laravel/.env from local deploy.
 * Visit once: https://www.smartbarbeiro.com.br/patch-mp-secrets-smartbarbeiro.php
 * DELETE immediately after.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

`$laravelRoot = dirname(__DIR__).'/laravel';
if (! is_file(`$laravelRoot.'/vendor/autoload.php') && is_file(dirname(__DIR__).'/laravel/laravel/vendor/autoload.php')) {
    `$laravelRoot = dirname(__DIR__).'/laravel/laravel';
}

`$envPath = `$laravelRoot.'/.env';

if (! is_file(`$envPath)) {
    exit(".env not found at {`$envPath}\n");
}

`$updates = [
$($phpUpdates -join "`n")
];

`$content = file_get_contents(`$envPath);
if (`$content === false) {
    exit("Could not read {`$envPath}\n");
}

foreach (`$updates as `$key => `$value) {
    `$pattern = '/^'.preg_quote(`$key, '/').'=.*$/m';
    `$line = `$key.'='.`$value;

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

echo "\nMercado Pago secrets patched.\n";
echo "Next: clear-cache-smartbarbeiro.php\n";
echo "DELETE patch-mp-secrets-smartbarbeiro.php when done.\n";
"@

$secretsPath = Join-Path $OutputDir "patch-mp-secrets-smartbarbeiro.php"
$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText($secretsPath, $secretsPhp, $utf8NoBom)

$laravelFiles = @(
    "app\Services\MercadoPagoService.php",
    "app\Http\Controllers\MercadoPagoWebhookController.php",
    "routes\web.php"
)

$helperFiles = @(
    "mp-status-smartbarbeiro.php",
    "sync-platform-plan-smartbarbeiro.php",
    "patch-mp-production-smartbarbeiro.php",
    "clear-cache-smartbarbeiro.php",
    "extract-deploy-smartbarbeiro.php",
    "patch-mp-secrets-smartbarbeiro.php"
)

Write-Host "==> Uploading Laravel hotfix files..."
foreach ($rel in $laravelFiles) {
    $local = Join-Path $ProjectRoot $rel
    if (-not (Test-Path $local)) { throw "Missing $local" }
    $remote = "$RemoteBase/laravel/$($rel -replace '\\','/')"
    Send-FtpFile $local $remote
}

Write-Host "==> Uploading public_html helpers..."
foreach ($name in $helperFiles) {
    $local = if ($name -eq "patch-mp-secrets-smartbarbeiro.php") {
        $secretsPath
    } else {
        Join-Path $HelpersDir $name
    }
    if (-not (Test-Path $local)) { throw "Missing $local" }
    Send-FtpFile $local "$RemoteBase/public_html/$name"
}

$steps = @(
    "https://www.smartbarbeiro.com.br/patch-mp-production-smartbarbeiro.php",
    "https://www.smartbarbeiro.com.br/patch-mp-secrets-smartbarbeiro.php",
    "https://www.smartbarbeiro.com.br/clear-cache-smartbarbeiro.php",
    "https://www.smartbarbeiro.com.br/sync-platform-plan-smartbarbeiro.php",
    "https://www.smartbarbeiro.com.br/mp-status-smartbarbeiro.php"
)

Write-Host ""
Write-Host "==> Running server setup URLs..."
foreach ($url in $steps) {
    Write-Host ""
    Write-Host "--- $url ---"
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 120
        Write-Host $response.Content
    } catch {
        Write-Host "ERROR: $($_.Exception.Message)"
        if ($_.Exception.Response) {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            Write-Host $reader.ReadToEnd()
        }
    }
}

Write-Host ""
Write-Host "Done. Test checkout: https://www.smartbarbeiro.com.br/assinatura/plataforma"
