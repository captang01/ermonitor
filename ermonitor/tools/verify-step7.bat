@echo off
setlocal
cd /d "%~dp0.."
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
if not exist "%MYSQL%" (
  echo ERROR: mysql.exe tidak ditemukan di %MYSQL%
  pause
  exit /b 1
)
echo ========================================
echo ERMonitor Step 7 - Database Verification
echo ========================================
echo.
"%MYSQL%" -u root ermonitor -e "SELECT DATABASE() AS db; SHOW TABLES LIKE 'audit_logs'; SHOW TABLES LIKE 'alert_events';"
echo.
if errorlevel 1 (
  echo VERIFIKASI GAGAL. Pastikan MySQL aktif dan database ermonitor ada.
  pause
  exit /b 1
)
echo Jika kedua nama tabel tampil, database Step 7 sudah siap.
pause
endlocal
