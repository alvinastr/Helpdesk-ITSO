@echo off
:: ============================================
:: Uninstall ITSO Email Fetch Service
:: ============================================

echo.
echo ========================================
echo   Uninstall ITSO Email Fetch Service
echo ========================================
echo.

:: Check if running as Administrator
net session >nul 2>&1
if %errorLevel% NEQ 0 (
    echo ERROR: Script ini harus dijalankan sebagai Administrator!
    echo.
    echo Cara: Klik kanan ^> Run as Administrator
    echo.
    pause
    exit /b 1
)

echo Stopping service...
C:\nssm\nssm.exe stop ITSOEmailFetch

echo Removing service...
C:\nssm\nssm.exe remove ITSOEmailFetch confirm

echo.
echo ========================================
echo   Service Removed Successfully
echo ========================================
echo.
pause
