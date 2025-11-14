@echo off
echo ================================================
echo    Download CDN Assets - Helpdesk ITSO
echo ================================================
echo.

REM Check if running in correct directory
if not exist "artisan" (
    echo ERROR: File artisan tidak ditemukan!
    echo Pastikan Anda menjalankan script ini di folder root project.
    pause
    exit /b 1
)

echo Script ini akan mendownload semua CDN assets ke folder lokal.
echo Anda membutuhkan koneksi internet untuk download.
echo.
echo CATATAN: Setelah download, aplikasi bisa jalan tanpa internet!
echo.
pause

echo.
echo [1/7] Membuat folder vendor...
if not exist "public\vendor\bootstrap" mkdir public\vendor\bootstrap
if not exist "public\vendor\fontawesome\webfonts" mkdir public\vendor\fontawesome\webfonts
if not exist "public\vendor\chartjs" mkdir public\vendor\chartjs
if not exist "public\vendor\fonts" mkdir public\vendor\fonts
echo     Folder dibuat!

echo.
echo [2/7] Downloading Bootstrap...
powershell -Command "Invoke-WebRequest -Uri 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' -OutFile 'public\vendor\bootstrap\bootstrap.min.css'"
powershell -Command "Invoke-WebRequest -Uri 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js' -OutFile 'public\vendor\bootstrap\bootstrap.bundle.min.js'"
echo     Bootstrap downloaded!

echo.
echo [3/7] Downloading Font Awesome CSS...
powershell -Command "$css = Invoke-WebRequest -Uri 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'; $content = $css.Content -replace '\.\./webfonts/', './webfonts/'; Set-Content -Path 'public\vendor\fontawesome\all.min.css' -Value $content"
echo     Font Awesome CSS downloaded!

echo.
echo [4/7] Downloading Font Awesome Fonts...
powershell -Command "Invoke-WebRequest -Uri 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2' -OutFile 'public\vendor\fontawesome\webfonts\fa-solid-900.woff2'"
powershell -Command "Invoke-WebRequest -Uri 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff2' -OutFile 'public\vendor\fontawesome\webfonts\fa-regular-400.woff2'"
powershell -Command "Invoke-WebRequest -Uri 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff2' -OutFile 'public\vendor\fontawesome\webfonts\fa-brands-400.woff2'"
echo     Font Awesome fonts downloaded!

echo.
echo [5/7] Downloading Chart.js...
powershell -Command "Invoke-WebRequest -Uri 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js' -OutFile 'public\vendor\chartjs\chart.umd.min.js'"
echo     Chart.js downloaded!

echo.
echo [6/7] Downloading Axios...
powershell -Command "Invoke-WebRequest -Uri 'https://cdn.jsdelivr.net/npm/axios@1.6.0/dist/axios.min.js' -OutFile 'public\vendor\axios.min.js'"
echo     Axios downloaded!

echo.
echo [7/7] Creating local fonts CSS...
echo @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700^&display=swap'); > public\vendor\fonts\nunito.css
echo     Font CSS created!

echo.
echo ================================================
echo    Download Selesai!
echo ================================================
echo.
echo Assets tersimpan di:
echo   - public\vendor\bootstrap\
echo   - public\vendor\fontawesome\
echo   - public\vendor\chartjs\
echo   - public\vendor\fonts\
echo.
echo Langkah selanjutnya:
echo 1. Buka file: config\app.php
echo 2. Set: 'use_local_assets' =^> true
echo 3. Restart server
echo.
pause
