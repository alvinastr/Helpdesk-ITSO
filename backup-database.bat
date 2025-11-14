@echo off
setlocal enabledelayedexpansion

echo ================================================
echo    HELPDESK ITSO - Database Backup
echo ================================================
echo.

REM Check if running in correct directory
if not exist "artisan" (
    echo ERROR: File artisan tidak ditemukan!
    echo Pastikan Anda menjalankan script ini di folder root project.
    pause
    exit /b 1
)

REM Read database config from .env
if not exist ".env" (
    echo ERROR: File .env tidak ditemukan!
    pause
    exit /b 1
)

REM Parse .env file
for /f "tokens=1,2 delims==" %%a in ('findstr /r "^DB_" .env') do (
    set %%a=%%b
)

REM Create backup directory if not exists
if not exist "backups" mkdir backups

REM Create timestamp
for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set mydate=%%c%%a%%b)
for /f "tokens=1-2 delims=/: " %%a in ('time /t') do (set mytime=%%a%%b)
set TIMESTAMP=%mydate%_%mytime%

set BACKUP_FILE=backups\backup_%TIMESTAMP%.sql

echo Database: %DB_DATABASE%
echo User: %DB_USERNAME%
echo Backup file: %BACKUP_FILE%
echo.

REM Find mysqldump
set MYSQLDUMP_PATH=

if exist "C:\xampp\mysql\bin\mysqldump.exe" (
    set MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe
)

if exist "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe" (
    set MYSQLDUMP_PATH=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe
)

if "%MYSQLDUMP_PATH%"=="" (
    echo ERROR: mysqldump tidak ditemukan!
    echo Pastikan XAMPP atau Laragon sudah terinstall.
    pause
    exit /b 1
)

echo Membuat backup...

if "%DB_PASSWORD%"=="" (
    "%MYSQLDUMP_PATH%" -u %DB_USERNAME% %DB_DATABASE% > "%BACKUP_FILE%"
) else (
    "%MYSQLDUMP_PATH%" -u %DB_USERNAME% -p%DB_PASSWORD% %DB_DATABASE% > "%BACKUP_FILE%"
)

if %errorlevel% equ 0 (
    echo.
    echo ================================================
    echo Backup berhasil!
    echo File: %BACKUP_FILE%
    echo ================================================
) else (
    echo.
    echo ERROR: Backup gagal!
    echo Periksa kredensial database di file .env
)

echo.
pause
