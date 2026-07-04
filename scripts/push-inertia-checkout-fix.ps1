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

$FtpHost = if ($env:HOSTINGER_FTP_HOST) { $env:HOSTINGER_FTP_HOST } elseif (Get-EnvValue 'HOSTINGER_FTP_HOST') { Get-EnvValue 'HOSTINGER_FTP_HOST' } else { "smartbarbeiro.com.br" }
$FtpUser = if ($env:HOSTINGER_FTP_USER) { $env:HOSTINGER_FTP_USER } else { "u379350398" }
$FtpPass = $env:HOSTINGER_FTP_PASSWORD
if (-not $FtpPass) { $FtpPass = Get-EnvValue 'HOSTINGER_FTP_PASSWORD' }
if (-not $FtpPass) { throw "Set HOSTINGER_FTP_PASSWORD in .env or your shell" }
$RemoteBase = "domains/smartbarbeiro.com.br"

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
    Write-Host "  uploaded -> $RemotePath"
    $response.Close()
}

$laravelFiles = @(
    "app\Http\Controllers\BarbershopPlatformSubscribeController.php",
    "app\Http\Controllers\ProfileSubscribeController.php",
    "app\Http\Controllers\ServicePlanSubscribeController.php"
)

Write-Host "==> Uploading checkout controllers..."
foreach ($rel in $laravelFiles) {
    $local = Join-Path $ProjectRoot $rel
    Send-FtpFile $local "$RemoteBase/laravel/$($rel -replace '\\','/')"
}

$patch = Join-Path $ProjectRoot "deploy\hostinger\public_html\patch-inertia-checkout-redirect-smartbarbeiro.php"
Send-FtpFile $patch "$RemoteBase/public_html/patch-inertia-checkout-redirect-smartbarbeiro.php"

Write-Host "==> Clearing cache..."
try {
    $r = Invoke-WebRequest -Uri "https://www.smartbarbeiro.com.br/clear-cache-smartbarbeiro.php" -UseBasicParsing -TimeoutSec 60
    Write-Host $r.Content
} catch {
    Write-Host "clear-cache error: $($_.Exception.Message)"
}

Write-Host "Done."
