# Starts a public HTTPS tunnel to the local Laravel dev server (port 8000)
# and prints the URL to set as MERCADOPAGO_BACK_URL in .env
#
# Usage (PowerShell):
#   .\scripts\start-dev-tunnel.ps1          # ngrok (recommended if authtoken configured)
#   .\scripts\start-dev-tunnel.ps1 -Cloudflare
#
# Requires: ngrok (winget install Ngrok.Ngrok) or cloudflared

param(
    [switch]$Cloudflare
)

$ErrorActionPreference = "Stop"

$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" +
    [System.Environment]::GetEnvironmentVariable("Path", "User")

Write-Host "Make sure Laravel is running: php artisan serve" -ForegroundColor Yellow
Write-Host ""

if (-not $Cloudflare) {
    if (-not (Get-Command ngrok -ErrorAction SilentlyContinue)) {
        Write-Host "ngrok not found. Install with: winget install Ngrok.Ngrok" -ForegroundColor Red
        exit 1
    }

    Write-Host "Starting ngrok -> http://127.0.0.1:8000" -ForegroundColor Cyan

    $process = Start-Process -FilePath "ngrok" `
        -ArgumentList "http", "8000", "--log=stdout" `
        -PassThru `
        -WindowStyle Hidden

    $publicUrl = $null
    for ($i = 0; $i -lt 20; $i++) {
        Start-Sleep -Seconds 1
        try {
            $tunnels = Invoke-RestMethod -Uri "http://127.0.0.1:4040/api/tunnels" -TimeoutSec 2
            $publicUrl = ($tunnels.tunnels | Where-Object { $_.proto -eq "https" } | Select-Object -First 1).public_url
            if ($publicUrl) { break }
        } catch {
            # ngrok API not ready yet
        }
    }

    if (-not $publicUrl) {
        Write-Host "Could not read ngrok URL. Open http://127.0.0.1:4040" -ForegroundColor Red
        exit 1
    }

    Write-Host "Public URL: $publicUrl" -ForegroundColor Green
    Write-Host "MERCADOPAGO_BACK_URL=$publicUrl"
    Write-Host "Signup: $publicUrl/registrar"
    Write-Host "Tunnel PID: $($process.Id)" -ForegroundColor DarkGray
    exit 0
}

if (-not (Get-Command cloudflared -ErrorAction SilentlyContinue)) {
    Write-Host "cloudflared not found. Install with: winget install Cloudflare.cloudflared" -ForegroundColor Red
    exit 1
}

Write-Host "Starting Cloudflare tunnel -> http://127.0.0.1:8000" -ForegroundColor Cyan

$logFile = Join-Path $PSScriptRoot "..\storage\logs\cloudflared-tunnel.err.log"
$process = Start-Process -FilePath "cloudflared" `
    -ArgumentList "tunnel", "--url", "http://127.0.0.1:8000" `
    -RedirectStandardError $logFile `
    -PassThru `
    -WindowStyle Hidden

$publicUrl = $null
for ($i = 0; $i -lt 30; $i++) {
    Start-Sleep -Seconds 1
    if (Test-Path $logFile) {
        $content = Get-Content $logFile -Raw -ErrorAction SilentlyContinue
        if ($content -match "(https://[a-z0-9-]+\.trycloudflare\.com)") {
            $publicUrl = $Matches[1]
            break
        }
    }
}

if (-not $publicUrl) {
    Write-Host "Could not read tunnel URL. Check $logFile" -ForegroundColor Red
    exit 1
}

Write-Host "Public URL: $publicUrl" -ForegroundColor Green
Write-Host "MERCADOPAGO_BACK_URL=$publicUrl"
Write-Host "Signup: $publicUrl/registrar"
Write-Host "Tunnel PID: $($process.Id)" -ForegroundColor DarkGray
