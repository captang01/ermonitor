@echo off
setlocal

echo ============================================
echo ERMonitor - LAN diagnostics
 echo ============================================
echo.
echo [IPv4]
ipconfig | findstr /R /C:"IPv4 Address" /C:"Alamat IPv4"
echo.
echo [Apache :80]
netstat -ano | findstr ":80" | findstr "LISTENING"
echo.
echo [MySQL :3306]
netstat -ano | findstr ":3306" | findstr "LISTENING"
echo.
echo [Prometheus :9090]
netstat -ano | findstr ":9090" | findstr "LISTENING"
echo.
echo [Local HTTP test]
curl.exe -I --max-time 5 http://127.0.0.1/ermonitor/ 2>nul | findstr /R /C:"HTTP/"
echo.
echo If another device cannot connect, check Windows Firewall and make sure
 echo Apache is listening on 0.0.0.0:80 (or the LAN address), not only 127.0.0.1.
echo.
pause
endlocal
