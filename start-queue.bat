@echo off
echo ================================================
echo    HELPDESK ITSO - Starting Queue Worker
echo ================================================
echo.

REM Check if running in correct directory
if not exist "artisan" (
    echo ERROR: File artisan tidak ditemukan!
    echo Pastikan Anda menjalankan script ini di folder root project.
    pause
    exit /b 1
)

echo Queue worker untuk memproses:
echo   - WhatsApp notifications
echo   - Email notifications
echo   - Background jobs
echo.
echo Tekan Ctrl+C untuk menghentikan queue worker
echo.
echo ================================================
echo.

php artisan queue:work --tries=3 --timeout=90
