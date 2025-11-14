@echo off
echo ================================================
echo    HELPDESK ITSO - Clear All Cache
echo ================================================
echo.

REM Check if running in correct directory
if not exist "artisan" (
    echo ERROR: File artisan tidak ditemukan!
    echo Pastikan Anda menjalankan script ini di folder root project.
    pause
    exit /b 1
)

echo Clearing all caches...
echo.

echo [1/6] Clearing application cache...
php artisan cache:clear

echo [2/6] Clearing configuration cache...
php artisan config:clear

echo [3/6] Clearing route cache...
php artisan route:clear

echo [4/6] Clearing view cache...
php artisan view:clear

echo [5/6] Clearing compiled files...
php artisan clear-compiled

echo [6/6] Optimizing autoloader...
composer dump-autoload

echo.
echo ================================================
echo All caches cleared successfully!
echo ================================================
echo.
pause
