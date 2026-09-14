@echo off
setlocal
cd /d "%~dp0.."
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
set "SQL=%~dp0..\database\upgrade_step7.sql"

echo ========================================
echo ERMonitor Step 7 - Database Upgrade
echo ========================================
if not exist "%MYSQL%" (
  echo ERROR: mysql.exe tidak ditemukan di %MYSQL%
  echo Sesuaikan lokasi XAMPP/MySQL pada file ini jika perlu.
  pause
  exit /b 1
)
if not exist "%SQL%" (
  echo ERROR: File upgrade_step7.sql tidak ditemukan.
  pause
  exit /b 1
)

echo Menjalankan upgrade database ermonitor...
"%MYSQL%" -u root ermonitor < "%SQL%"
if errorlevel 1 (
  echo.
  echo UPGRADE GAGAL. Pastikan MySQL/XAMPP aktif dan database ermonitor sudah ada.
  pause
  exit /b 1
)

echo.
echo UPGRADE BERHASIL.
echo Tabel audit_logs dan alert_events sudah tersedia.
echo Jalankan tools\verify-step7.bat untuk memeriksa hasil migrasi.
pause
endlocal
