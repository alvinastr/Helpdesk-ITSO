@echo off
echo ========================================
echo Email Fetch Quick Diagnostic
echo ========================================

echo.
echo [1] Clearing cache...
php artisan config:clear
php artisan cache:clear

echo.
echo [2] Running diagnostic...
php artisan emails:debug

echo.
echo [3] Press any key to try email fetch...
pause
php artisan emails:fetch

echo.
echo Done!
pause