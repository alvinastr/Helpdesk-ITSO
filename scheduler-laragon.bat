@echo off
REM ========================================
REM Laravel Task Scheduler for Laragon
REM ========================================
REM File: scheduler-laragon.bat
REM Description: Menjalankan Laravel scheduler menggunakan PHP dari Laragon
REM Usage: Setup di Windows Task Scheduler untuk run setiap menit

REM Path ke Laragon PHP (sesuaikan dengan versi PHP Anda)
REM Contoh lokasi default Laragon:
REM C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe
REM C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe

SET PHP_PATH=C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe
SET PROJECT_PATH=C:\laragon\www\ITSO

REM Cek apakah PHP path ada
IF NOT EXIST "%PHP_PATH%" (
    echo ERROR: PHP tidak ditemukan di %PHP_PATH%
    echo Silakan sesuaikan PHP_PATH di file ini dengan lokasi PHP Laragon Anda
    pause
    exit /b 1
)

REM Cek apakah project path ada
IF NOT EXIST "%PROJECT_PATH%\artisan" (
    echo ERROR: Project Laravel tidak ditemukan di %PROJECT_PATH%
    echo Silakan sesuaikan PROJECT_PATH di file ini
    pause
    exit /b 1
)

REM Pindah ke directory project
cd /d "%PROJECT_PATH%"

REM Jalankan Laravel scheduler
"%PHP_PATH%" artisan schedule:run >> storage\logs\scheduler.log 2>&1

REM Exit tanpa error
exit /b 0
