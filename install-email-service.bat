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
if not exist "C:\nssm\nssm.exe" (
    echo.
    echo NSSM belum terinstall!
    echo.
    echo Download NSSM dari: https://nssm.cc/download
    echo Extract ke: C:\nssm\
    echo.
    echo Atau jalankan PowerShell sebagai Admin:
    echo   Invoke-WebRequest -Uri https://nssm.cc/release/nssm-2.24.zip -OutFile nssm.zip
    echo   Expand-Archive nssm.zip -DestinationPath C:\
    echo   Rename-Item C:\nssm-2.24 C:\nssm
    echo.
    pause
    exit /b 1
)

echo [OK] NSSM found at C:\nssm\nssm.exe
echo.

echo [2/5] Checking project path...
if not exist "C:\laragon\www\ITSO\artisan" (
    echo ERROR: Project tidak ditemukan di C:\laragon\www\ITSO
    echo Pastikan path project sudah benar!
    pause
    exit /b 1
)
echo [OK] Project found
echo.

echo [3/5] Checking PHP...
if not exist "C:\laragon\bin\php\php-8.3.26\php.exe" (
    echo ERROR: PHP tidak ditemukan!
    echo Update path di script ini sesuai versi PHP Anda
    pause
    exit /b 1
)
echo [OK] PHP found
echo.

echo [4/5] Installing service...
C:\nssm\nssm.exe stop ITSOEmailFetch >nul 2>&1
C:\nssm\nssm.exe remove ITSOEmailFetch confirm >nul 2>&1

C:\nssm\nssm.exe install ITSOEmailFetch "C:\laragon\bin\php\php-8.3.26\php.exe"
C:\nssm\nssm.exe set ITSOEmailFetch AppDirectory "C:\laragon\www\ITSO"
C:\nssm\nssm.exe set ITSOEmailFetch AppParameters "artisan emails:fetch-daemon --interval=300"
C:\nssm\nssm.exe set ITSOEmailFetch DisplayName "ITSO Email Auto-Fetch Service"
C:\nssm\nssm.exe set ITSOEmailFetch Description "Automatically fetch emails and create tickets for ITSO Helpdesk"
C:\nssm\nssm.exe set ITSOEmailFetch Start SERVICE_AUTO_START
C:\nssm\nssm.exe set ITSOEmailFetch AppStdout "C:\laragon\www\ITSO\storage\logs\email-daemon.log"
C:\nssm\nssm.exe set ITSOEmailFetch AppStderr "C:\laragon\www\ITSO\storage\logs\email-daemon-error.log"

:: Auto-restart on failure
C:\nssm\nssm.exe set ITSOEmailFetch AppExit Default Restart
C:\nssm\nssm.exe set ITSOEmailFetch AppRestartDelay 5000

echo [OK] Service installed
echo.

echo [5/5] Starting service...
C:\nssm\nssm.exe start ITSOEmailFetch

timeout /t 3 >nul

:: Check status
C:\nssm\nssm.exe status ITSOEmailFetch
echo.

echo ========================================
echo   Installation Complete!
echo ========================================
echo.
echo Service Name: ITSOEmailFetch
echo Status: Running
echo.
echo Logs tersimpan di:
echo   - C:\laragon\www\ITSO\storage\logs\email-daemon.log
echo   - C:\laragon\www\ITSO\storage\logs\email-daemon-error.log
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
