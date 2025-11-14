@echo off
echo ================================================
echo    Switch ke Offline Mode
echo ================================================
echo.

REM Check if running in correct directory
if not exist "artisan" (
    echo ERROR: File artisan tidak ditemukan!
    pause
    exit /b 1
)

REM Check if vendor assets exist
if not exist "public\vendor\bootstrap\bootstrap.min.css" (
    echo ================================================
    echo ERROR: Local assets belum di-download!
    echo ================================================
    echo.
    echo Silakan jalankan terlebih dahulu:
    echo   download-cdn-assets.bat
    echo.
    pause
    exit /b 1
)

echo Mode saat ini:
findstr /C:"OFFLINE_MODE" .env >nul 2>&1
if %errorlevel% neq 0 (
    echo   - OFFLINE_MODE: belum diset
    echo   - USE_LOCAL_ASSETS: belum diset
) else (
    findstr /C:"OFFLINE_MODE=true" .env >nul 2>&1
    if %errorlevel% equ 0 (
        echo   - OFFLINE_MODE: ENABLED
    ) else (
        echo   - OFFLINE_MODE: DISABLED
    )
)

echo.
echo Pilih mode:
echo [1] Enable Offline Mode (gunakan local assets)
echo [2] Disable Offline Mode (gunakan CDN)
echo [3] Cancel
echo.
set /p MODE="Pilih (1/2/3): "

if "%MODE%"=="1" (
    echo.
    echo Mengaktifkan Offline Mode...
    
    REM Check if settings exist in .env
    findstr /C:"OFFLINE_MODE" .env >nul 2>&1
    if %errorlevel% neq 0 (
        REM Add settings if not exist
        echo. >> .env
        echo # Offline Mode - Local Network Deployment >> .env
        echo OFFLINE_MODE=true >> .env
        echo USE_LOCAL_ASSETS=true >> .env
    ) else (
        REM Update existing settings
        powershell -Command "(Get-Content .env) -replace 'OFFLINE_MODE=false', 'OFFLINE_MODE=true' | Set-Content .env"
        powershell -Command "(Get-Content .env) -replace 'USE_LOCAL_ASSETS=false', 'USE_LOCAL_ASSETS=true' | Set-Content .env"
    )
    
    echo     ✓ .env updated
    
    REM Clear config cache
    php artisan config:clear >nul 2>&1
    php artisan config:cache >nul 2>&1
    echo     ✓ Config cache cleared
    
    echo.
    echo ================================================
    echo Offline Mode ENABLED!
    echo ================================================
    echo.
    echo Aplikasi sekarang menggunakan local assets.
    echo Tidak membutuhkan koneksi internet.
    
) else if "%MODE%"=="2" (
    echo.
    echo Menonaktifkan Offline Mode...
    
    REM Update settings to false
    powershell -Command "(Get-Content .env) -replace 'OFFLINE_MODE=true', 'OFFLINE_MODE=false' | Set-Content .env"
    powershell -Command "(Get-Content .env) -replace 'USE_LOCAL_ASSETS=true', 'USE_LOCAL_ASSETS=false' | Set-Content .env"
    
    echo     ✓ .env updated
    
    REM Clear config cache
    php artisan config:clear >nul 2>&1
    php artisan config:cache >nul 2>&1
    echo     ✓ Config cache cleared
    
    echo.
    echo ================================================
    echo Offline Mode DISABLED!
    echo ================================================
    echo.
    echo Aplikasi sekarang menggunakan CDN.
    echo Membutuhkan koneksi internet.
    
) else (
    echo.
    echo Cancelled.
)

echo.
pause
