# Full production deploy to smartbarbeiro.com.br via FTP + browser helpers.
# Usage:
#   $env:HOSTINGER_FTP_PASSWORD = 'your-ftp-password'
#   .\scripts\deploy-hostinger-production.ps1
#   .\scripts\deploy-hostinger-production.ps1 -SkipPrepare

param(
    [switch]$SkipPrepare
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

$FtpHost = if ($env:HOSTINGER_FTP_HOST) { $env:HOSTINGER_FTP_HOST } elseif (Get-EnvValue 'HOSTINGER_FTP_HOST') { Get-EnvValue 'HOSTINGER_FTP_HOST' } else { "smartbarbeiro.com.br" }
$FtpUser = if (Get-EnvValue 'HOSTINGER_FTP_USER') { Get-EnvValue 'HOSTINGER_FTP_USER' } elseif ($env:HOSTINGER_FTP_USER) { $env:HOSTINGER_FTP_USER } else { "u379350398" }
$FtpPass = Get-EnvValue 'HOSTINGER_FTP_PASSWORD'
if (-not $FtpPass) { $FtpPass = $env:HOSTINGER_FTP_PASSWORD }
if (-not $FtpPass) {
    throw "Set HOSTINGER_FTP_PASSWORD in .env or your shell before running this script."
}
$RemoteBase = "domains/smartbarbeiro.com.br"
$SiteBase = "https://www.smartbarbeiro.com.br"

function Send-FtpFile([string]$LocalPath, [string]$RemotePath) {
    if (-not (Test-Path $LocalPath)) {
        throw "Missing file: $LocalPath"
    }

    $uri = "ftp://${FtpHost}/${RemotePath}"
    $sizeMb = [math]::Round((Get-Item $LocalPath).Length / 1MB, 1)
    Write-Host "  uploading $sizeMb MB -> $RemotePath ..."

    # curl handles Hostinger FTP auth more reliably than .NET FtpWebRequest.
    & curl.exe -S --connect-timeout 120 --max-time 900 --ftp-pasv `
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
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 300
        Write-Host $response.Content
        return $true
    } catch {
        Write-Host "ERROR: $($_.Exception.Message)"
        if ($_.Exception.Response) {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $body = $reader.ReadToEnd()
            if ($body) { Write-Host $body }
        }
        return $false
    }
}

Write-Host "==> Building deploy packages..."
if (-not $SkipPrepare) {
    & $PrepareScript
} else {
    Write-Host "  (skipped - using existing zips in deploy/hostinger/output/)"
}

$laravelZip = Join-Path $OutputDir "laravel.zip"
$publicZip = Join-Path $OutputDir "public_html.zip"

Write-Host ""
Write-Host "==> Uploading zip archives..."
Send-FtpFile $laravelZip "$RemoteBase/laravel/laravel.zip"
Send-FtpFile $publicZip "$RemoteBase/public_html/public_html.zip"

Write-Host ""
Write-Host "==> Extracting on server (preserves uploads + images)..."
if (-not (Invoke-DeployUrl "extract-deploy-smartbarbeiro.php")) {
    throw "Extract step failed."
}

Write-Host ""
Write-Host "==> Patching production URLs..."
Invoke-DeployUrl "patch-mp-production-smartbarbeiro.php" | Out-Null

Write-Host ""
Write-Host "==> Clearing Laravel cache..."
Invoke-DeployUrl "clear-cache-smartbarbeiro.php" | Out-Null

Write-Host ""
Write-Host "==> Syncing Laravel app from zip (Linux-safe paths)..."
if (-not (Invoke-DeployUrl "sync-laravel-smartbarbeiro.php")) {
    throw "Laravel sync failed."
}

Write-Host ""
Write-Host "==> Syncing Vite build assets..."
if (-not (Invoke-DeployUrl "sync-build-smartbarbeiro.php")) {
    throw "Vite build sync failed."
}

Write-Host ""
Write-Host "==> Verifying Vite assets..."
Invoke-DeployUrl "fix-vite-smartbarbeiro.php" | Out-Null

Write-Host ""
Write-Host "==> Fixing storage symlink (profile photos)..."
Invoke-DeployUrl "fix-storage-link-smartbarbeiro.php" | Out-Null

Write-Host ""
Write-Host "==> Storage diagnostics..."
Invoke-DeployUrl "storage-status-smartbarbeiro.php" | Out-Null

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
Write-Host "Delete *-smartbarbeiro.php helpers from public_html when finished."
