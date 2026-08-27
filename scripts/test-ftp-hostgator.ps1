# Quick FTP test for HostGator deploy.
# Set HOSTINGER_FTP_HOST in .env to brXXXX.hostgator.com.br from cPanel → Contas FTP.
# Usage: .\scripts\test-ftp-hostgator.ps1

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
$FtpPass = if ($env:HOSTINGER_FTP_PASSWORD) { $env:HOSTINGER_FTP_PASSWORD } else { Get-EnvValue 'HOSTINGER_FTP_PASSWORD' }
if (-not $FtpPass) { throw "Set HOSTINGER_FTP_PASSWORD in .env" }

Write-Host "Testing FTP: $FtpHost as $FtpUser"
curl.exe -S --connect-timeout 20 --ftp-pasv --user "${FtpUser}:${FtpPass}" "ftp://${FtpHost}/" --list-only
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "FTP failed. In cPanel open Contas FTP → Configurar cliente de FTP"
    Write-Host "and set HOSTINGER_FTP_HOST to the brXXXX.hostgator.com.br server shown there."
    exit $LASTEXITCODE
}

Write-Host ""
Write-Host "FTP OK. Run: .\scripts\deploy-hostinger-production.ps1 -SkipPrepare"
