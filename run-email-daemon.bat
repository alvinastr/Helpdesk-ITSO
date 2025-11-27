@echo off
:: ============================================
:: Email Fetch Daemon Launcher untuk Windows
:: ============================================

title Email Auto-Fetch Daemon - ITSO Helpdesk

echo.
echo ======================================
echo   ITSO Helpdesk - Email Auto-Fetch
echo ======================================
echo.
echo Starting daemon process...
echo Press Ctrl+C to stop
echo.

:: Pindah ke direktori project
cd /d C:\laragon\www\ITSO

:: Clear cache dulu (sekali saja saat start)
php artisan config:clear

:: Jalankan daemon (interval 300 detik = 5 menit)
php artisan emails:fetch-daemon --interval=300

pause
