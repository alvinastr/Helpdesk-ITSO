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

:: Find NSSM
if exist "C:\nssm\nssm.exe" (
    set NSSM_PATH=C:\nssm\nssm.exe
) else (
    where nssm.exe >nul 2>&1
    if %errorLevel% EQU 0 (
        set NSSM_PATH=nssm.exe
    ) else (
        echo ERROR: NSSM not found!
        pause
        exit /b 1
    )
)

echo Stopping service...
%NSSM_PATH% stop ITSOEmailFetch

echo Removing service...
%NSSM_PATH% remove ITSOEmailFetch confirm

echo.
echo ========================================
echo   Service Removed Successfully
echo ========================================
echo.
pause
