@echo off
title ERMonitor - HTTPS / Cloudflare readiness
echo ==========================================
echo ERMonitor HTTPS / Internet Readiness
echo ==========================================
echo.
echo [1] Checking Apache port 80...
powershell -NoProfile -Command "if (Get-NetTCPConnection -LocalPort 80 -State Listen -ErrorAction SilentlyContinue) { Write-Host 'OK: TCP 80 is listening' -ForegroundColor Green } else { Write-Host 'WARN: TCP 80 is not listening' -ForegroundColor Yellow }"
echo.
echo [2] Checking HTTPS port 443...
powershell -NoProfile -Command "if (Get-NetTCPConnection -LocalPort 443 -State Listen -ErrorAction SilentlyContinue) { Write-Host 'OK: TCP 443 is listening' -ForegroundColor Green } else { Write-Host 'INFO: TCP 443 is not listening (Cloudflare Tunnel can terminate HTTPS)' -ForegroundColor Yellow }"
echo.
echo [3] Local application test...
powershell -NoProfile -Command "try { $r=Invoke-WebRequest -UseBasicParsing http://127.0.0.1/ermonitor/ -TimeoutSec 5; Write-Host ('OK: HTTP ' + $r.StatusCode) -ForegroundColor Green } catch { Write-Host ('WARN: ' + $_.Exception.Message) -ForegroundColor Yellow }"
echo.
echo Recommended:
echo   Public HTTPS -> Cloudflare -> cloudflared -> http://127.0.0.1:80
echo   Do NOT expose MySQL 3306, Prometheus 9090 or exporter 9182 publicly.
echo.
pause
