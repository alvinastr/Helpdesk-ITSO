@echo off
echo ================================================
echo    HELPDESK ITSO - Production Optimization
echo ================================================
echo.

REM Check if running in correct directory
if not exist "artisan" (
    echo ERROR: File artisan tidak ditemukan!
    echo Pastikan Anda menjalankan script ini di folder root project.
    pause
    exit /b 1
)

echo PERINGATAN: Script ini akan mengoptimasi aplikasi untuk production.
echo Pastikan:
echo   1. File .env sudah dikonfigurasi untuk production (APP_ENV=production, APP_DEBUG=false)
echo   2. Database sudah di-migrate
echo   3. Testing sudah selesai
echo.
echo Lanjutkan? (Y/N)
set /p CONFIRM=
if /i not "%CONFIRM%"=="Y" (
    echo Dibatalkan.
    pause
    exit /b 0
)

echo.
echo Optimizing application...
echo.

echo [1/5] Clearing old caches...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo.
echo [2/5] Optimizing Composer autoloader...
composer install --optimize-autoloader --no-dev

echo.
echo [3/5] Caching configuration...
php artisan config:cache

echo.
echo [4/5] Caching routes...
php artisan route:cache

echo.
echo [5/5] Caching views...
php artisan view:cache

echo.
echo ================================================
echo Production optimization completed!
echo ================================================
echo.
echo Aplikasi sekarang dioptimasi untuk production.
echo.
echo CATATAN:
echo - Jika ada perubahan di .env, jalankan: php artisan config:cache
echo - Jika ada perubahan route, jalankan: php artisan route:cache
echo - Untuk development lagi, jalankan clear-cache.bat
echo.
pause
