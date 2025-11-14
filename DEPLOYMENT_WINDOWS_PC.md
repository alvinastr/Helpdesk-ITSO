# 🪟 Panduan Deployment di PC Windows Biasa

**Aplikasi:** Helpdesk ITSO  
**Target:** PC Windows (Bukan Windows Server)  
**Status:** Ready for Local/Intranet Deployment

---

## 📋 Daftar Isi

1. [Persiapan Awal](#persiapan-awal)
2. [Instalasi Software yang Dibutuhkan](#instalasi-software)
3. [Setup Database](#setup-database)
4. [Setup Aplikasi Laravel](#setup-aplikasi)
5. [Konfigurasi untuk Production](#konfigurasi-production)
6. [Cara Menjalankan Aplikasi](#menjalankan-aplikasi)
7. [Akses dari Komputer Lain](#akses-dari-komputer-lain)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 Persiapan Awal

### Spesifikasi Minimum PC Windows

- **OS:** Windows 10/11 (Home/Pro)
- **RAM:** Minimum 4GB (Recommended 8GB+)
- **Storage:** Minimum 5GB free space
- **Network:** Koneksi internet untuk instalasi (setelah itu bisa offline)

### ⚠️ Hal Penting yang Perlu Diketahui

1. **PC harus selalu menyala** saat aplikasi digunakan
2. **IP Address** PC bisa berubah jika restart (kecuali set static IP)
3. **Firewall** Windows harus dikonfigurasi untuk akses network
4. **Tidak seperti server hosting** - jika PC mati, aplikasi tidak bisa diakses

---

## 💻 Instalasi Software yang Dibutuhkan

### Option 1: Menggunakan XAMPP (PALING MUDAH - RECOMMENDED)

XAMPP adalah paket lengkap yang sudah include Apache, MySQL, dan PHP.

#### 1. Download dan Install XAMPP

1. Download XAMPP untuk Windows (PHP 8.2+):
   - Link: https://www.apachefriends.org/download.html
   - Pilih versi dengan **PHP 8.2** atau **PHP 8.3**

2. Install XAMPP:
   - Double click installer
   - Install di `C:\xampp` (default)
   - Centang: Apache, MySQL, PHP
   - Ikuti wizard hingga selesai

3. Jalankan XAMPP Control Panel:
   - Buka XAMPP Control Panel
   - Start **Apache**
   - Start **MySQL**

#### 2. Install Composer (PHP Package Manager)

1. Download Composer:
   - Link: https://getcomposer.org/download/
   - Download `Composer-Setup.exe`

2. Install Composer:
   - Jalankan installer
   - Saat diminta PHP path, arahkan ke: `C:\xampp\php\php.exe`
   - Selesaikan instalasi

3. Verifikasi instalasi:
   - Buka Command Prompt (CMD) atau PowerShell
   - Ketik: `composer --version`
   - Harus muncul versi Composer

#### 3. Install Git (Optional tapi Recommended)

1. Download Git:
   - Link: https://git-scm.com/download/win
   - Download versi Windows

2. Install Git:
   - Jalankan installer
   - Gunakan default settings
   - Selesaikan instalasi

---

### Option 2: Menggunakan Laragon (ALTERNATIF - JUGA MUDAH)

Laragon adalah alternatif XAMPP yang lebih modern dan mudah untuk Laravel.

#### 1. Download dan Install Laragon

1. Download Laragon Full:
   - Link: https://laragon.org/download/
   - Pilih **Laragon Full** (sudah include everything)

2. Install Laragon:
   - Jalankan installer
   - Install di `C:\laragon` (default)
   - Selesaikan instalasi

3. Jalankan Laragon:
   - Buka Laragon
   - Klik **Start All**

Laragon sudah include: Apache, MySQL, PHP, Composer, Git, Node.js

---

## 🗄️ Setup Database

### Menggunakan phpMyAdmin (Include di XAMPP/Laragon)

#### 1. Akses phpMyAdmin

- Buka browser
- Ketik: `http://localhost/phpmyadmin`
- Login:
  - Username: `root`
  - Password: (kosong - tekan enter)

#### 2. Buat Database Baru

1. Klik tab **"Databases"** di atas
2. Di "Create database":
   - Nama database: `helpdesk_itso`
   - Collation: `utf8mb4_unicode_ci`
3. Klik **"Create"**

#### 3. (Optional) Buat User Database Khusus

Untuk keamanan lebih baik, buat user khusus:

1. Klik tab **"User accounts"**
2. Klik **"Add user account"**
3. Isi form:
   - User name: `helpdesk_user`
   - Host name: `localhost`
   - Password: (buat password yang kuat)
   - Re-type: (ulangi password)
4. Di bagian "Database for user account":
   - Centang: "Grant all privileges on database helpdesk_itso"
5. Klik **"Go"**

---

## 📦 Setup Aplikasi Laravel

### 1. Copy Project ke Folder yang Tepat

#### Jika menggunakan XAMPP:

```cmd
# Copy folder project ke:
C:\xampp\htdocs\helpdesk-itso
```

#### Jika menggunakan Laragon:

```cmd
# Copy folder project ke:
C:\laragon\www\helpdesk-itso
```

**CARA COPY:**
1. Copy seluruh folder project Anda yang sekarang
2. Paste ke lokasi di atas
3. Rename folder jadi `helpdesk-itso` (tanpa spasi)

### 2. Install Dependencies

1. Buka **Command Prompt** atau **PowerShell**
2. Masuk ke folder project:

```cmd
# Untuk XAMPP:
cd C:\xampp\htdocs\helpdesk-itso

# Untuk Laragon:
cd C:\laragon\www\helpdesk-itso
```

3. Install dependencies dengan Composer:

```cmd
composer install --optimize-autoloader
```

**Note:** Proses ini akan memakan waktu 5-10 menit tergantung koneksi internet.

### 3. Setup File Environment (.env)

1. Di folder project, copy file `.env.example`:

```cmd
copy .env.example .env
```

2. Edit file `.env` dengan text editor (Notepad++, VSCode, atau Notepad):

**Buka:** `C:\xampp\htdocs\helpdesk-itso\.env`

3. Edit bagian-bagian penting:

```env
# Aplikasi
APP_NAME="Helpdesk ITSO"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

# Database - sesuaikan dengan yang Anda buat tadi
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpdesk_itso
DB_USERNAME=root
DB_PASSWORD=

# Jika buat user khusus:
# DB_USERNAME=helpdesk_user
# DB_PASSWORD=password_yang_anda_buat

# Queue
QUEUE_CONNECTION=database

# Mail Configuration (untuk kirim email notifikasi)
# Jika tidak pakai email, skip dulu
MAIL_MAILER=log
MAIL_FROM_ADDRESS="helpdesk@yourcompany.com"
MAIL_FROM_NAME="${APP_NAME}"

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

4. Generate Application Key:

```cmd
php artisan key:generate
```

### 4. Setup Database Tables

Jalankan migrations untuk membuat semua tabel:

```cmd
php artisan migrate --force
```

**Output yang benar:**
```
Migration table created successfully.
Migrating: 2014_10_12_000000_create_users_table
Migrated:  2014_10_12_000000_create_users_table (XX.XXms)
...
```

### 5. Buat Admin User Pertama

```cmd
php artisan db:seed --class=AdminSeeder
```

Ini akan membuat user admin default:
- **Email:** `admin@itso.com`
- **Password:** `password123`

⚠️ **PENTING:** Ganti password ini setelah login pertama kali!

### 6. Setup Storage

```cmd
php artisan storage:link
```

### 7. Optimize untuk Production

```cmd
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🚀 Cara Menjalankan Aplikasi

### Option A: Menggunakan PHP Built-in Server (SIMPLE)

Cara paling mudah untuk testing/penggunaan internal:

1. Buka Command Prompt/PowerShell
2. Masuk ke folder project:

```cmd
cd C:\xampp\htdocs\helpdesk-itso
```

3. Jalankan server:

```cmd
php artisan serve --host=0.0.0.0 --port=8000
```

**Output:**
```
INFO  Server running on [http://0.0.0.0:8000].
```

4. Akses aplikasi:
   - Dari PC ini: http://localhost:8000
   - Dari PC lain: http://IP_PC_ANDA:8000

**⚠️ CATATAN:** 
- CMD/PowerShell harus tetap terbuka
- Jika tutup, aplikasi akan mati
- Cocok untuk testing atau penggunaan sementara

---

### Option B: Menggunakan XAMPP Apache (RECOMMENDED)

Cara ini lebih stabil dan otomatis jalan saat XAMPP start.

#### 1. Konfigurasi Virtual Host

1. Buka file `httpd-vhosts.conf`:
   - **Lokasi:** `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
   - Buka dengan Notepad++ atau text editor

2. Tambahkan di paling bawah:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/helpdesk-itso/public"
    ServerName helpdesk.local
    
    <Directory "C:/xampp/htdocs/helpdesk-itso/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/helpdesk-error.log"
    CustomLog "logs/helpdesk-access.log" common
</VirtualHost>
```

3. Simpan file

#### 2. Edit File Hosts

1. Buka Notepad sebagai **Administrator** (klik kanan > Run as administrator)

2. File > Open > Ketik path ini:
   ```
   C:\Windows\System32\drivers\etc\hosts
   ```

3. Tambahkan di paling bawah:
   ```
   127.0.0.1 helpdesk.local
   ```

4. Simpan file

#### 3. Restart Apache

1. Buka XAMPP Control Panel
2. Stop Apache
3. Start Apache lagi

#### 4. Akses Aplikasi

- Buka browser
- Ketik: `http://helpdesk.local`

---

### Option C: Menggunakan Laragon (PALING MUDAH)

Jika pakai Laragon, ini otomatis:

1. Pastikan Laragon running
2. Akses: `http://helpdesk-itso.test`

Laragon otomatis setup virtual host!

---

## 🌐 Akses dari Komputer Lain (Network)

Agar komputer lain di jaringan yang sama bisa akses:

### 1. Cari IP Address PC Anda

1. Buka Command Prompt
2. Ketik: `ipconfig`
3. Cari "IPv4 Address" di bagian network adapter yang aktif
   - Contoh: `192.168.1.100`

### 2. Konfigurasi Windows Firewall

#### Option A: Allow PHP/Apache di Firewall (Recommended)

1. Buka **Windows Defender Firewall**
2. Klik **"Allow an app or feature through Windows Defender Firewall"**
3. Klik **"Change settings"**
4. Klik **"Allow another app..."**
5. Browse dan pilih:
   - `C:\xampp\php\php.exe` (jika pakai php artisan serve)
   - `C:\xampp\apache\bin\httpd.exe` (jika pakai Apache)
6. Centang **Private** dan **Public**
7. Klik **OK**

#### Option B: Buka Port Spesifik

1. Buka **Windows Defender Firewall**
2. Klik **"Advanced settings"**
3. Klik **"Inbound Rules"** > **"New Rule..."**
4. Pilih **"Port"** > Next
5. Pilih **"TCP"** > Specific local ports: `80` (atau `8000` jika pakai artisan serve)
6. Pilih **"Allow the connection"** > Next
7. Centang semua profile (Domain, Private, Public) > Next
8. Name: "Helpdesk ITSO" > Finish

### 3. Test Akses dari Komputer Lain

Dari komputer lain di jaringan yang sama:
- Buka browser
- Ketik: `http://192.168.1.100` (ganti dengan IP PC Anda)
- Atau: `http://192.168.1.100:8000` (jika pakai php artisan serve)

---

## 🔐 Keamanan untuk Production

### 1. Ganti Password Admin Default

1. Login dengan admin default
2. Klik profile/settings
3. Ganti password

### 2. Disable Debug Mode

Pastikan di `.env`:
```env
APP_DEBUG=false
APP_ENV=production
```

### 3. Set Static IP (Recommended)

Agar IP tidak berubah saat restart:

1. Buka **Control Panel** > **Network and Internet** > **Network Connections**
2. Klik kanan network adapter Anda > **Properties**
3. Double-click **"Internet Protocol Version 4 (TCP/IPv4)"**
4. Pilih **"Use the following IP address"**
5. Isi:
   - IP address: `192.168.1.100` (sesuaikan dengan range network Anda)
   - Subnet mask: `255.255.255.0`
   - Default gateway: `192.168.1.1` (biasanya IP router)
   - Preferred DNS: `8.8.8.8`
   - Alternate DNS: `8.8.4.4`
6. Klik **OK**

### 4. Backup Database Rutin

Buat scheduled task untuk backup otomatis:

1. Buat file `backup-database.bat`:

```batch
@echo off
set TIMESTAMP=%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%

C:\xampp\mysql\bin\mysqldump.exe -u root helpdesk_itso > C:\backups\helpdesk_%TIMESTAMP%.sql

echo Backup completed: helpdesk_%TIMESTAMP%.sql
```

2. Buat folder `C:\backups`
3. Buat scheduled task di Windows untuk jalankan file ini setiap hari

---

## 🔧 Troubleshooting

### Problem 1: "Composer command not found"

**Solusi:**
1. Restart Command Prompt/PowerShell
2. Atau add manual ke PATH:
   - Search "Environment Variables"
   - Edit "Path" variable
   - Add: `C:\ProgramData\ComposerSetup\bin`

### Problem 2: "Port 80 already in use"

**Penyebab:** Ada program lain pakai port 80 (Skype, IIS, etc)

**Solusi:**
1. Stop program yang menggunakan port 80
2. Atau ganti port Apache:
   - Edit `C:\xampp\apache\conf\httpd.conf`
   - Cari: `Listen 80`
   - Ganti: `Listen 8080`
   - Restart Apache

### Problem 3: "Access denied for user 'root'@'localhost'"

**Solusi:**
1. Reset MySQL password:
   - Stop MySQL di XAMPP
   - Edit `C:\xampp\mysql\bin\my.ini`
   - Add: `skip-grant-tables` di bawah `[mysqld]`
   - Start MySQL
   - Buka CMD:
     ```
     C:\xampp\mysql\bin\mysql.exe -u root
     FLUSH PRIVILEGES;
     ALTER USER 'root'@'localhost' IDENTIFIED BY '';
     exit
     ```
   - Remove `skip-grant-tables` dari my.ini
   - Restart MySQL

### Problem 4: "500 Internal Server Error"

**Solusi:**
1. Check log: `storage/logs/laravel.log`
2. Pastikan permissions folder storage dan bootstrap/cache:
   ```cmd
   icacls storage /grant Everyone:F /T
   icacls bootstrap/cache /grant Everyone:F /T
   ```
3. Clear cache:
   ```cmd
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

### Problem 5: Tidak bisa akses dari PC lain

**Solusi:**
1. Check firewall (lihat section di atas)
2. Pastikan kedua PC di network yang sama
3. Ping dari PC lain:
   ```cmd
   ping 192.168.1.100
   ```
4. Jika tidak reply, ada masalah network/firewall

### Problem 6: "Class not found"

**Solusi:**
```cmd
composer dump-autoload
php artisan clear-compiled
php artisan config:clear
```

---

## 📱 Setup Queue Worker (Optional - untuk WhatsApp/Email)

Jika menggunakan fitur WhatsApp atau Email notification:

### 1. Buat File Batch untuk Queue Worker

Buat file `start-queue.bat`:

```batch
@echo off
cd C:\xampp\htdocs\helpdesk-itso
php artisan queue:work --tries=3
```

### 2. Jalankan Queue Worker

Double-click `start-queue.bat` atau jalankan:
```cmd
php artisan queue:work
```

**Note:** Window ini harus tetap terbuka agar queue berjalan.

### 3. (Advanced) Setup sebagai Windows Service

Untuk queue jalan otomatis di background:

1. Download NSSM (Non-Sucking Service Manager):
   - Link: https://nssm.cc/download

2. Extract dan buka CMD as Administrator

3. Install service:
```cmd
nssm install HelpdeskQueue "C:\xampp\php\php.exe" "artisan queue:work --tries=3"
nssm set HelpdeskQueue AppDirectory "C:\xampp\htdocs\helpdesk-itso"
nssm start HelpdeskQueue
```

---

## 🎯 Quick Start Summary

**Untuk mulai cepat (setelah install XAMPP + Composer):**

```cmd
# 1. Masuk ke folder project
cd C:\xampp\htdocs\helpdesk-itso

# 2. Install dependencies
composer install

# 3. Setup environment
copy .env.example .env
php artisan key:generate

# 4. Edit .env (sesuaikan DB_DATABASE, DB_USERNAME, DB_PASSWORD)

# 5. Setup database
php artisan migrate --force
php artisan db:seed --class=AdminSeeder

# 6. Setup storage
php artisan storage:link

# 7. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Jalankan
php artisan serve --host=0.0.0.0 --port=8000
```

**Akses:** http://localhost:8000

**Login Admin:**
- Email: `admin@itso.com`
- Password: `password123`

---

## 📞 Support

Jika ada masalah saat deployment:
1. Check `storage/logs/laravel.log`
2. Check Apache error log: `C:\xampp\apache\logs\error.log`
3. Check MySQL log: `C:\xampp\mysql\data\mysql_error.log`

---

## 🔄 Update Aplikasi

Saat ada update dari development:

```cmd
# 1. Pull changes (jika pakai Git)
git pull origin main

# 2. Update dependencies
composer install

# 3. Run migrations (jika ada)
php artisan migrate --force

# 4. Clear & cache lagi
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

**🎉 Selamat! Aplikasi Helpdesk ITSO sudah berjalan di PC Windows Anda!**
