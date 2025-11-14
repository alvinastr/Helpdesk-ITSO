@echo off
echo ================================================
echo    HELPDESK ITSO - Create Admin User
echo ================================================
echo.

REM Check if running in correct directory
if not exist "artisan" (
    echo ERROR: File artisan tidak ditemukan!
    echo Pastikan Anda menjalankan script ini di folder root project.
    pause
    exit /b 1
)

echo Pilih metode pembuatan admin:
echo [1] Gunakan seeder default (admin@itso.com / admin123)
echo [2] Buat admin dengan data custom
echo.
set /p METHOD="Pilih (1 atau 2): "

if "%METHOD%"=="1" (
    echo.
    echo Membuat admin user default...
    php artisan db:seed --class=AdminUserSeeder
    echo.
    echo ================================================
    echo Admin user telah dibuat!
    echo.
    echo Login credentials:
    echo   Email: admin@itso.com
    echo   Password: admin123
    echo.
    echo Test user:
    echo   Email: user@itso.com
    echo   Password: user123
    echo ================================================
) else if "%METHOD%"=="2" (
    echo.
    echo Masukkan data admin baru:
    echo.
    set /p ADMIN_NAME="Nama lengkap: "
    set /p ADMIN_EMAIL="Email: "
    set /p ADMIN_PASSWORD="Password: "
    
    echo.
    echo Membuat admin user...
    
    php artisan tinker --execute="use App\Models\User; use Illuminate\Support\Facades\Hash; User::create(['name' => '%ADMIN_NAME%', 'email' => '%ADMIN_EMAIL%', 'password' => Hash::make('%ADMIN_PASSWORD%'), 'role' => 'admin', 'email_verified_at' => now()]); echo 'Admin user created successfully!';"
    
    echo.
    echo ================================================
    echo Admin user telah dibuat!
    echo.
    echo Login credentials:
    echo   Email: %ADMIN_EMAIL%
    echo   Password: %ADMIN_PASSWORD%
    echo ================================================
) else (
    echo.
    echo Pilihan tidak valid!
)

echo.
pause
