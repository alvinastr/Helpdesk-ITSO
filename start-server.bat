@echo off
echo ================================================
echo    HELPDESK ITSO - Starting Development Server
echo ================================================
echo.

REM Check if running in correct directory
if not exist "artisan" (
    echo ERROR: File artisan tidak ditemukan!
    echo Pastikan Anda menjalankan script ini di folder root project.
    pause
    exit /b 1
)

REM Get local IP address
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set LOCAL_IP=%%a
)
set LOCAL_IP=%LOCAL_IP: =%

echo Server akan dijalankan di:
echo   - Local:   http://localhost:8000
echo   - Network: http://%LOCAL_IP%:8000
echo.
echo Pastikan firewall mengizinkan koneksi di port 8000
echo.
echo Tekan Ctrl+C untuk menghentikan server
echo.
echo ================================================
echo.

php artisan serve --host=0.0.0.0 --port=8000
