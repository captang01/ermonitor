# ERMonitor - HTTPS readiness check
# Run in PowerShell on the Windows/XAMPP server.
$ErrorActionPreference = "SilentlyContinue"

Write-Host "=== ERMonitor HTTPS readiness ===" -ForegroundColor Cyan

$apache = Get-NetTCPConnection -LocalPort 80 -State Listen
$https  = Get-NetTCPConnection -LocalPort 443 -State Listen

if ($apache) {
  Write-Host "[OK] Apache is listening on TCP 80." -ForegroundColor Green
} else {
  Write-Host "[WARN] Nothing is listening on TCP 80." -ForegroundColor Yellow
}

if ($https) {
  Write-Host "[OK] Something is listening on TCP 443." -ForegroundColor Green
} else {
  Write-Host "[INFO] TCP 443 is not listening. This is expected when HTTPS is terminated by Cloudflare Tunnel." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Local application:" -ForegroundColor Cyan
Write-Host "  http://127.0.0.1/ermonitor/"

Write-Host ""
Write-Host "Recommended internet deployment:" -ForegroundColor Cyan
Write-Host "  Public HTTPS -> Cloudflare -> cloudflared -> http://127.0.0.1:80/ermonitor/"
Write-Host "  Keep MySQL 3306, Prometheus 9090 and windows_exporter 9182 private."

Write-Host ""
Write-Host "If using a normal Apache HTTPS vhost instead, configure TCP 443 and a trusted certificate for your real domain."
