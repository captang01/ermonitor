@echo off
setlocal EnableExtensions EnableDelayedExpansion

echo ============================================
echo ERMonitor - Windows LAN setup
 echo ============================================
echo.

echo [1/3] Network addresses:
ipconfig | findstr /R /C:"IPv4 Address" /C:"Alamat IPv4"
echo.

echo [2/3] Apache firewall rule...
netsh advfirewall firewall show rule name="ERMonitor Apache HTTP" >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo Rule already exists.
) else (
    netsh advfirewall firewall add rule name="ERMonitor Apache HTTP" dir=in action=allow protocol=TCP localport=80 profile=private
    if %ERRORLEVEL% EQU 0 (echo Firewall rule created.) else (echo FAILED: run this file as Administrator.)
)
echo.

echo [3/3] Test Apache on this PC...
curl.exe -I --max-time 5 http://127.0.0.1/ermonitor/ 2>nul | findstr /R /C:"HTTP/"
echo.
echo From another device on the same Wi-Fi/LAN, open:
echo   http://YOUR-PC-IP/ermonitor/
echo.
echo Example: http://192.168.1.20/ermonitor/
echo.
echo IMPORTANT: keep MySQL 3306 and Prometheus 9090 private.
echo Do not port-forward XAMPP directly to the public internet.
echo.
pause
endlocal
