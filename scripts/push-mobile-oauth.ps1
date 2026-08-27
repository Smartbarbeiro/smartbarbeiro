# Push mobile Google OAuth patch to Hostinger.
# Usage:
#   $env:HOSTINGER_FTP_PASSWORD = 'your-ftp-password'
#   .\scripts\push-mobile-oauth.ps1

$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path $PSScriptRoot -Parent
$HelpersDir = Join-Path $ProjectRoot "deploy\hostinger\public_html"
$RemoteBase = if ($env:HOSTINGER_FTP_REMOTE_BASE) { $env:HOSTINGER_FTP_REMOTE_BASE } else { "" }

function Join-RemotePath([string[]]$Parts) {
    $all = @()
    if ($RemoteBase) { $all += $RemoteBase.Trim('/') }
    $all += $Parts | ForEach-Object { $_.Trim('/') }
    return ($all -join '/')
}

$FtpHost = if ($env:HOSTINGER_FTP_HOST) { $env:HOSTINGER_FTP_HOST } else { "ftp.fulviolopescatto1787174444000.0970020.meusitehostgator.com.br" }
$FtpUser = if ($env:HOSTINGER_FTP_USER) { $env:HOSTINGER_FTP_USER } else { "fulvio@fulviolopescatto1787174444000.0970020.meusitehostgator.com.br" }
$FtpPass = $env:HOSTINGER_FTP_PASSWORD
if (-not $FtpPass) {
    throw "Set HOSTINGER_FTP_PASSWORD before running this script."
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

$files = @(
    @{
        Local = Join-Path $ProjectRoot "app\Http\Controllers\Auth\SocialAuthController.php"
        Remote = Join-RemotePath @('laravel', 'app/Http/Controllers/Auth/SocialAuthController.php')
    },
    @{
        Local = Join-Path $ProjectRoot "app\Http\Controllers\Api\V1\AuthController.php"
        Remote = Join-RemotePath @('laravel', 'app/Http/Controllers/Api/V1/AuthController.php')
    },
    @{
        Local = Join-Path $HelpersDir "patch-mobile-oauth-tesora.php"
        Remote = Join-RemotePath @('public_html', 'patch-mobile-oauth-tesora.php')
    },
    @{
        Local = Join-Path $HelpersDir "clear-cache-tesora.php"
        Remote = Join-RemotePath @('public_html', 'clear-cache-tesora.php')
    }
)

Write-Host "==> Uploading mobile OAuth files..."
foreach ($file in $files) {
    if (-not (Test-Path $file.Local)) { throw "Missing $($file.Local)" }
    Send-FtpFile $file.Local $file.Remote
}

$steps = @(
    "https://www.tesora.com.br/patch-mobile-oauth-tesora.php",
    "https://www.tesora.com.br/clear-cache-tesora.php"
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
    }
}

Write-Host ""
Write-Host "Done. Test Google login on the phone app."
