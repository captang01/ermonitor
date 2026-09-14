@echo off
setlocal
set "DB_NAME=ermonitor"
set "OUT_DIR=%~dp0..\backups"
if not exist "%OUT_DIR%" mkdir "%OUT_DIR%"
for /f "tokens=1-3 delims=/- " %%a in ("%date%") do set "D=%%c-%%b-%%a"
for /f "tokens=1-2 delims=: " %%a in ("%time%") do set "T=%%a%%b"
set "T=%T: =0%"
set "OUT_FILE=%OUT_DIR%\%DB_NAME%_%D%_%T%.sql"
if exist "C:\xampp\mysql\bin\mysqldump.exe" (
  "C:\xampp\mysql\bin\mysqldump.exe" --single-transaction --routines --triggers "%DB_NAME%" > "%OUT_FILE%"
) else (
  echo mysqldump.exe tidak ditemukan di C:\xampp\mysql\bin.
  exit /b 1
)
if errorlevel 1 (
  echo Backup gagal.
  exit /b 1
)
echo Backup berhasil: %OUT_FILE%
endlocal
