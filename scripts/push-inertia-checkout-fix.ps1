# Upload Inertia checkout redirect fix to Hostinger.
$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path $PSScriptRoot -Parent

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
$FtpUser = if ($env:HOSTINGER_FTP_USER) { $env:HOSTINGER_FTP_USER } elseif (Get-EnvValue 'HOSTINGER_FTP_USER') { Get-EnvValue 'HOSTINGER_FTP_USER' } else { "fulvio@fulviolopescatto1787174444000.0970020.meusitehostgator.com.br" }
$FtpPass = $env:HOSTINGER_FTP_PASSWORD
if (-not $FtpPass) { $FtpPass = Get-EnvValue 'HOSTINGER_FTP_PASSWORD' }
if (-not $FtpPass) { throw "Set HOSTINGER_FTP_PASSWORD in .env or your shell" }
$RemoteBase = if ($env:HOSTINGER_FTP_REMOTE_BASE) { $env:HOSTINGER_FTP_REMOTE_BASE } elseif (Get-EnvValue 'HOSTINGER_FTP_REMOTE_BASE') { Get-EnvValue 'HOSTINGER_FTP_REMOTE_BASE' } else { "" }

function Join-RemotePath([string[]]$Parts) {
    $all = @()
    if ($RemoteBase) { $all += $RemoteBase.Trim('/') }
    $all += $Parts | ForEach-Object { $_.Trim('/') }
    return ($all -join '/')
}

function Send-FtpFile([string]$LocalPath, [string]$RemotePath) {
    $uri = "ftp://${FtpHost}/${RemotePath}"
    & curl.exe -S --ftp-pasv -T $LocalPath --user "${FtpUser}:${FtpPass}" $uri
    if ($LASTEXITCODE -ne 0) {
        throw "FTP upload failed for $RemotePath"
    }
    Write-Host "  uploaded -> $RemotePath"
}

$laravelFiles = @(
    "app\Http\Controllers\BarbershopPlatformSubscribeController.php",
    "app\Http\Controllers\ProfileSubscribeController.php",
    "app\Http\Controllers\ServicePlanSubscribeController.php"
)

Write-Host "==> Uploading checkout controllers..."
foreach ($rel in $laravelFiles) {
    $local = Join-Path $ProjectRoot $rel
    Send-FtpFile $local (Join-RemotePath @('laravel', ($rel -replace '\\','/')))
}

$patch = Join-Path $ProjectRoot "deploy\hostinger\public_html\patch-inertia-checkout-redirect-tesora.php"
Send-FtpFile $patch (Join-RemotePath @('public_html', 'patch-inertia-checkout-redirect-tesora.php'))

Write-Host "==> Clearing cache..."
try {
    $r = Invoke-WebRequest -Uri "https://www.tesora.com.br/clear-cache-tesora.php" -UseBasicParsing -TimeoutSec 60
    Write-Host $r.Content
} catch {
    Write-Host "clear-cache error: $($_.Exception.Message)"
}

Write-Host "Done."
