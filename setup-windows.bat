@echo off
echo ================================================
echo    HELPDESK ITSO - Windows Setup Script
echo ================================================
echo.

REM Check if running in correct directory
if not exist "artisan" (
    echo ERROR: File artisan tidak ditemukan!
    echo Pastikan Anda menjalankan script ini di folder root project.
    pause
    exit /b 1
)

echo [1/8] Checking Composer...
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Composer tidak ditemukan!
    echo Silakan install Composer terlebih dahulu dari https://getcomposer.org/download/
    pause
    exit /b 1
)
echo     Composer: OK

echo.
echo [2/8] Installing dependencies...
call composer install --optimize-autoloader
if %errorlevel% neq 0 (
    echo ERROR: Gagal install dependencies!
    pause
    exit /b 1
)

echo.
echo [3/8] Setting up environment file...
if not exist ".env" (
    if exist ".env.example" (
        copy .env.example .env
        echo     File .env berhasil dibuat dari .env.example
    ) else (
        echo ERROR: File .env.example tidak ditemukan!
        pause
        exit /b 1
    )
) else (
    echo     File .env sudah ada, skip...
)

echo.
echo [4/8] Generating application key...
php artisan key:generate
if %errorlevel% neq 0 (
    echo ERROR: Gagal generate application key!
    pause
    exit /b 1
)

echo.
echo [5/8] Creating storage link...
php artisan storage:link
if %errorlevel% neq 0 (
    echo WARNING: Storage link mungkin sudah ada, melanjutkan...
)

echo.
echo ================================================
echo SETUP DATABASE
echo ================================================
echo.
echo Sebelum melanjutkan, pastikan:
echo 1. MySQL sudah running (XAMPP/Laragon)
echo 2. Database sudah dibuat (misal: helpdesk_itso)
echo 3. File .env sudah dikonfigurasi dengan benar
echo.
echo Edit file .env sekarang? (Y/N)
set /p EDIT_ENV=
if /i "%EDIT_ENV%"=="Y" (
    if exist "C:\Program Files\Notepad++\notepad++.exe" (
        start "" "C:\Program Files\Notepad++\notepad++.exe" .env
    ) else (
        start notepad .env
    )
    echo.
    echo Setelah selesai edit, tutup editor dan tekan tombol apapun untuk melanjutkan...
    pause >nul
)

echo.
echo [6/8] Running database migrations...
php artisan migrate --force
if %errorlevel% neq 0 (
    echo ERROR: Gagal menjalankan migrations!
    echo Pastikan database sudah dibuat dan kredensial di .env benar.
    pause
    exit /b 1
)

echo.
echo [7/8] Seeding admin user...
echo Membuat user admin default (admin@itso.com / password123)
php artisan db:seed --class=AdminSeeder
if %errorlevel% neq 0 (
    echo WARNING: Admin seeder mungkin sudah dijalankan sebelumnya, melanjutkan...
)

echo.
echo [8/8] Optimizing for production...
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo.
echo ================================================
echo    SETUP SELESAI!
echo ================================================
echo.
echo Aplikasi siap dijalankan!
echo.
echo Untuk menjalankan aplikasi:
echo   php artisan serve --host=0.0.0.0 --port=8000
echo.
echo Akses aplikasi di:
echo   http://localhost:8000
echo.
echo Login admin:
echo   Email: admin@itso.com
echo   Password: password123
echo.
echo PENTING: Ganti password admin setelah login pertama kali!
echo.
pause
