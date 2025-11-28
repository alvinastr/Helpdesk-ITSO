@echo off
:: ============================================
:: Install ITSO Email Fetch sebagai Windows Service
:: Menggunakan NSSM (Non-Sucking Service Manager)
:: ============================================

echo.
echo ========================================
echo   ITSO Email Fetch Service Installer
echo ========================================
echo.

:: Check if running as Administrator
net session >nul 2>&1
if %errorLevel% NEQ 0 (
    echo ERROR: Installer ini harus dijalankan sebagai Administrator!
    echo.
    echo Cara: Klik kanan file ini ^> Run as Administrator
    echo.
    pause
    exit /b 1
)

echo [1/5] Checking NSSM installation...
if exist "C:\nssm\nssm.exe" (
    echo [OK] NSSM found at C:\nssm\nssm.exe
    set NSSM_PATH=C:\nssm\nssm.exe
    goto :nssm_ok
)

:: Try System32
where nssm.exe >nul 2>&1
if %errorLevel% EQU 0 (
    echo [OK] NSSM found in System32
    set NSSM_PATH=nssm.exe
    goto :nssm_ok
)

:: NSSM not found
echo.
echo NSSM belum terinstall!
echo.
echo Download NSSM dari: https://nssm.cc/download
echo.
echo Setelah extract ZIP, akan ada folder: win32, win64, win64-arm64
echo Pilih sesuai Windows Anda:
echo   - Windows 64-bit: win64\nssm.exe  (paling umum)
echo   - Windows 32-bit: win32\nssm.exe
echo.
echo Copy file nssm.exe ke: C:\nssm\nssm.exe
echo.
pause
exit /b 1

:nssm_ok
echo.

echo [2/5] Checking project path...
if not exist "C:\laragon\www\services-itso\artisan" (
    echo ERROR: Project tidak ditemukan di C:\laragon\www\services-itso
    echo Pastikan path project sudah benar!
    pause
    exit /b 1
)
echo [OK] Project found
echo.

echo [3/5] Checking PHP...
if not exist "C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" (
    echo ERROR: PHP tidak ditemukan!
    echo Update path di script ini sesuai versi PHP Anda
    pause
    exit /b 1
)
echo [OK] PHP found
echo.

echo [4/5] Installing service...
%NSSM_PATH% stop ITSOEmailFetch >nul 2>&1
%NSSM_PATH% remove ITSOEmailFetch confirm >nul 2>&1

%NSSM_PATH% install ITSOEmailFetch "C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe"
%NSSM_PATH% set ITSOEmailFetch AppDirectory "C:\laragon\www\services-itso"
%NSSM_PATH% set ITSOEmailFetch AppParameters "artisan emails:fetch-daemon --interval=300"
%NSSM_PATH% set ITSOEmailFetch DisplayName "ITSO Email Auto-Fetch Service"
%NSSM_PATH% set ITSOEmailFetch Description "Automatically fetch emails and create tickets for ITSO Helpdesk"
%NSSM_PATH% set ITSOEmailFetch Start SERVICE_AUTO_START
%NSSM_PATH% set ITSOEmailFetch AppStdout "C:\laragon\www\services-itso\storage\logs\email-daemon.log"
%NSSM_PATH% set ITSOEmailFetch AppStderr "C:\laragon\www\services-itso\storage\logs\email-daemon-error.log"

:: Auto-restart on failure
%NSSM_PATH% set ITSOEmailFetch AppExit Default Restart
%NSSM_PATH% set ITSOEmailFetch AppRestartDelay 5000

echo [OK] Service installed
echo.

echo [5/5] Starting service...
%NSSM_PATH% start ITSOEmailFetch

timeout /t 3 >nul

:: Check status
%NSSM_PATH% status ITSOEmailFetch
echo.

echo ========================================
echo   Installation Complete!
echo ========================================
echo.
echo Service Name: ITSOEmailFetch
echo Status: Running
echo.
echo Logs tersimpan di:
echo   - C:\laragon\www\services-itso\storage\logs\email-daemon.log
echo   - C:\laragon\www\services-itso\storage\logs\email-daemon-error.log
echo.
echo Service akan AUTO-START setiap kali PC restart
echo (bahkan sebelum user login!)
echo.
echo Commands:
echo   Start:   net start ITSOEmailFetch
echo   Stop:    net stop ITSOEmailFetch
echo   Restart: net stop ITSOEmailFetch ^&^& net start ITSOEmailFetch
echo   Status:  sc query ITSOEmailFetch
echo.
pause
