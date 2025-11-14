# 🚀 Quick Start - Deployment Windows PC

**Panduan cepat untuk deployment Helpdesk ITSO di PC Windows biasa**

---

## ⚡ Langkah Cepat (5 Menit Setup)

### 1. Install Software (Pilih Salah Satu)

**Option A: XAMPP** (Recommended untuk pemula)
- Download: https://www.apachefriends.org/download.html
- Install dengan PHP 8.2+
- Download Composer: https://getcomposer.org/download/

**Option B: Laragon** (Lebih modern, all-in-one)
- Download Laragon Full: https://laragon.org/download/
- Sudah include semua yang dibutuhkan

### 2. Setup Project

```batch
# Copy project ke:
C:\xampp\htdocs\helpdesk-itso
# atau
C:\laragon\www\helpdesk-itso

# Jalankan setup otomatis:
setup-windows.bat
```

Script akan otomatis:
- ✅ Install dependencies
- ✅ Setup .env file
- ✅ Generate app key
- ✅ Setup database
- ✅ Buat admin user
- ✅ Optimize aplikasi

### 3. Edit Database di .env

Buka file `.env` dan sesuaikan:
```env
DB_DATABASE=helpdesk_itso
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Jalankan Aplikasi

```batch
# Double-click file:
start-server.bat
```

### 5. Akses Aplikasi

- Browser: http://localhost:8000
- Login: `admin@itso.com` / `password123`

---

## 📁 Script Helper yang Tersedia

| File | Fungsi |
|------|--------|
| `setup-windows.bat` | Setup awal project (install, migrate, seed) |
| `start-server.bat` | Jalankan development server |
| `start-queue.bat` | Jalankan queue worker (untuk WhatsApp/Email) |
| `backup-database.bat` | Backup database ke folder backups/ |
| `clear-cache.bat` | Clear semua cache (untuk development) |
| `optimize-production.bat` | Optimize untuk production |

---

## 🌐 Akses dari PC Lain

### 1. Cari IP Address PC Anda

```cmd
ipconfig
```
Contoh hasilnya: `192.168.1.100`

### 2. Allow Firewall

**Cara Cepat:**
1. Windows Security > Firewall & network protection
2. Allow an app through firewall
3. Cari "php.exe" dan centang Private + Public

### 3. Akses dari PC Lain

Browser di PC lain: `http://192.168.1.100:8000`

---

## 🔧 Command Manual (Jika Tidak Pakai Script)

### Setup Awal
```cmd
cd C:\xampp\htdocs\helpdesk-itso
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=AdminSeeder
php artisan storage:link
```

### Jalankan Server
```cmd
php artisan serve --host=0.0.0.0 --port=8000
```

### Clear Cache
```cmd
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Optimize Production
```cmd
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🛠️ Troubleshooting Cepat

### Port 80 sudah digunakan
```cmd
# Edit C:\xampp\apache\conf\httpd.conf
# Ganti: Listen 80
# Jadi: Listen 8080
```

### Permission Error
```cmd
icacls storage /grant Everyone:F /T
icacls bootstrap\cache /grant Everyone:F /T
```

### MySQL Error
- Pastikan MySQL running di XAMPP/Laragon
- Check kredensial di `.env`
- Test connection di phpMyAdmin: http://localhost/phpmyadmin

### 500 Error
1. Check log: `storage/logs/laravel.log`
2. Set `APP_DEBUG=true` di `.env` (untuk debugging)
3. Clear cache: `clear-cache.bat`

---

## 📊 Default Credentials

**Admin:**
- Email: `admin@itso.com`
- Password: `password123`

**phpMyAdmin:**
- Username: `root`
- Password: (kosong)

⚠️ **PENTING:** Ganti password setelah login pertama!

---

## 🔄 Update Aplikasi

Jika ada update dari development:

```cmd
git pull origin main
composer install
php artisan migrate --force
clear-cache.bat
optimize-production.bat
```

---

## 💾 Backup Database

### Manual
```cmd
backup-database.bat
```

### Otomatis (Windows Task Scheduler)
1. Buka Task Scheduler
2. Create Basic Task
3. Trigger: Daily, pilih waktu
4. Action: Start a program
5. Program: `C:\xampp\htdocs\helpdesk-itso\backup-database.bat`

---

## 🖥️ Menjalankan sebagai Service (Advanced)

Agar aplikasi jalan otomatis saat Windows start:

### 1. Download NSSM
- Link: https://nssm.cc/download
- Extract ke folder seperti `C:\nssm`

### 2. Install Server Service
```cmd
# Buka CMD as Administrator
cd C:\nssm\win64
nssm install HelpdeskServer "C:\xampp\php\php.exe" "artisan serve --host=0.0.0.0 --port=8000"
nssm set HelpdeskServer AppDirectory "C:\xampp\htdocs\helpdesk-itso"
nssm start HelpdeskServer
```

### 3. Install Queue Service (Jika Pakai WhatsApp/Email)
```cmd
nssm install HelpdeskQueue "C:\xampp\php\php.exe" "artisan queue:work --tries=3"
nssm set HelpdeskQueue AppDirectory "C:\xampp\htdocs\helpdesk-itso"
nssm start HelpdeskQueue
```

---

## 📱 Setup WhatsApp (Optional)

Jika ingin menggunakan notifikasi WhatsApp:

1. Daftar WhatsApp Business API
2. Edit `.env`:
```env
WHATSAPP_PHONE_ID=your_phone_id
WHATSAPP_TOKEN=your_access_token
WHATSAPP_VERIFY_TOKEN=your_verify_token
```
3. Jalankan queue worker: `start-queue.bat`

---

## 📧 Setup Email (Optional)

Jika ingin notifikasi email:

### Gmail
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

### Mailtrap (Testing)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
```

---

## 🎯 Checklist Production

- [ ] Database sudah di-backup
- [ ] `.env` set `APP_ENV=production` dan `APP_DEBUG=false`
- [ ] Password admin sudah diganti
- [ ] Static IP sudah di-set (optional tapi recommended)
- [ ] Firewall sudah dikonfigurasi
- [ ] Backup otomatis sudah di-setup
- [ ] Test akses dari PC lain
- [ ] Test semua fitur utama berjalan
- [ ] Queue worker running (jika pakai WhatsApp/Email)

---

## 📞 Kontak Support

Jika ada masalah:
1. Check `storage/logs/laravel.log`
2. Check Apache log: `C:\xampp\apache\logs\error.log`
3. Check MySQL log di XAMPP/Laragon folder

---

**Dokumentasi lengkap:** Lihat `DEPLOYMENT_WINDOWS_PC.md`

**Selamat menggunakan Helpdesk ITSO! 🎉**
