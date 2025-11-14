# 🪟 Deployment ke Windows PC - Helpdesk ITSO

**Panduan lengkap untuk deployment aplikasi Helpdesk ITSO di PC Windows biasa (bukan Windows Server)**

---

## 📚 Dokumentasi Tersedia

| File | Deskripsi | Gunakan Untuk |
|------|-----------|---------------|
| **QUICK_START_WINDOWS.md** | Panduan singkat 5 menit | Jika sudah familiar dengan Laravel |
| **DEPLOYMENT_WINDOWS_PC.md** | Panduan lengkap dan detail | Panduan utama, step-by-step lengkap |
| **CHECKLIST_DEPLOYMENT_WINDOWS.md** | Checklist deployment | Memastikan tidak ada yang terlewat |
| **apache-vhost-config.txt** | Konfigurasi Apache | Setup virtual host Apache/XAMPP |
| **windows-firewall-config.txt** | Konfigurasi Firewall | Allow akses dari network |

## 🚀 Quick Start

### 1. Install Software

**Pilih salah satu:**

**XAMPP** (Recommended untuk pemula)
```
1. Download: https://www.apachefriends.org/download.html
2. Install dengan PHP 8.2+
3. Download Composer: https://getcomposer.org/download/
```

**Laragon** (All-in-one, lebih modern)
```
1. Download Laragon Full: https://laragon.org/download/
2. Install (sudah include semua)
```

### 2. Setup Project

```batch
# Copy project ke:
C:\xampp\htdocs\helpdesk-itso

# Jalankan setup otomatis:
setup-windows.bat
```

### 3. Jalankan Aplikasi

```batch
start-server.bat
```

Akses: **http://localhost:8000**

Login:
- Email: `admin@itso.com`
- Password: `password123`

---

## 📦 Script Helper Yang Tersedia

| Script | Fungsi |
|--------|--------|
| `setup-windows.bat` | Setup awal lengkap (install, migrate, seed) |
| `start-server.bat` | Jalankan development server |
| `start-queue.bat` | Jalankan queue worker (WhatsApp/Email) |
| `backup-database.bat` | Backup database otomatis |
| `clear-cache.bat` | Clear semua cache (untuk development) |
| `optimize-production.bat` | Optimize untuk production |

**Cara Pakai:** Double-click file `.bat` yang diinginkan

---

## 🔧 Struktur Deployment

```
C:\xampp\
├── htdocs\
│   └── helpdesk-itso\          ← Project folder
│       ├── .env                ← Configuration file
│       ├── artisan             ← Laravel CLI
│       ├── public\             ← Web root
│       ├── storage\            ← Logs & uploads
│       ├── backups\            ← Database backups
│       ├── setup-windows.bat   ← Setup script
│       ├── start-server.bat    ← Server script
│       └── ...
├── php\                        ← PHP executable
├── mysql\                      ← MySQL server
└── apache\                     ← Apache web server
```

---

## 🌐 Akses dari PC Lain (Network)

### Quick Steps:

1. **Cari IP Address:**
   ```cmd
   ipconfig
   ```
   Contoh: `192.168.1.100`

2. **Allow Firewall:**
   - Windows Security → Firewall
   - Allow "php.exe" dan "httpd.exe"
   - Atau open port 8000/80

3. **Akses dari PC lain:**
   ```
   http://192.168.1.100:8000
   ```

**Detail:** Lihat `windows-firewall-config.txt`

---

## 🔐 Keamanan

### Production Checklist:

- [ ] Ganti password admin default
- [ ] Set `APP_DEBUG=false` di `.env`
- [ ] Set `APP_ENV=production` di `.env`
- [ ] Setup static IP untuk PC server
- [ ] Configure firewall dengan benar
- [ ] Setup backup database otomatis
- [ ] Restrict `.env` file permissions

---

## 💾 Backup Database

### Manual:
```batch
backup-database.bat
```

### Otomatis dengan Task Scheduler:

1. Buka Task Scheduler Windows
2. Create Basic Task
3. Trigger: Daily
4. Action: Start program → pilih `backup-database.bat`
5. Finish

Backup akan tersimpan di folder `backups/`

---

## 🚨 Troubleshooting

### Port 80 sudah digunakan
```batch
# Ganti port Apache di httpd.conf
Listen 8080
```

### Permission Error
```cmd
icacls storage /grant Everyone:F /T
icacls bootstrap\cache /grant Everyone:F /T
```

### MySQL Connection Error
1. Pastikan MySQL running di XAMPP
2. Check `.env` credentials
3. Test di phpMyAdmin: http://localhost/phpmyadmin

### 500 Internal Server Error
```cmd
# Check log
type storage\logs\laravel.log

# Clear cache
clear-cache.bat
```

### Tidak bisa akses dari PC lain
1. Check firewall settings
2. Ping server: `ping [IP-ADDRESS]`
3. Test port: `telnet [IP-ADDRESS] 8000`
4. Lihat `windows-firewall-config.txt`

---

## 📱 Optional Features

### WhatsApp Notifications

1. Daftar WhatsApp Business API
2. Edit `.env`:
   ```env
   WHATSAPP_PHONE_ID=your_phone_id
   WHATSAPP_TOKEN=your_access_token
   ```
3. Jalankan: `start-queue.bat`

### Email Notifications

Edit `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

---

## 🔄 Update Aplikasi

Saat ada update dari development:

```cmd
git pull origin main
composer install
php artisan migrate --force
clear-cache.bat
optimize-production.bat
```

---

## 💻 System Requirements

### Minimum:
- Windows 10/11 (Home/Pro)
- RAM 4GB
- Storage 5GB free
- Network connection (untuk instalasi)

### Recommended:
- Windows 10/11 Pro
- RAM 8GB+
- SSD dengan 10GB+ free
- Dedicated ethernet connection

---

## 📊 Default Credentials

**Admin Panel:**
- Email: `admin@itso.com`
- Password: `password123`

**phpMyAdmin:**
- Username: `root`
- Password: (kosong)

⚠️ **PENTING:** Ganti password setelah login pertama!

---

## 🎯 Panduan Lengkap

Untuk panduan step-by-step yang sangat detail, baca:

📖 **DEPLOYMENT_WINDOWS_PC.md**

Panduan tersebut mencakup:
- Instalasi software lengkap dengan screenshots
- Troubleshooting untuk setiap masalah
- Konfigurasi advanced (service, task scheduler, dll)
- Security best practices
- Network setup detail

---

## ✅ Deployment Checklist

Gunakan file **CHECKLIST_DEPLOYMENT_WINDOWS.md** untuk memastikan:

- ✅ Semua software terinstall
- ✅ Database setup benar
- ✅ Aplikasi berjalan
- ✅ Test dari PC lain berhasil
- ✅ Backup configured
- ✅ Security measures applied
- ✅ Documentation complete

---

## 🆘 Butuh Bantuan?

### Log Files:

**Application:**
```
storage/logs/laravel.log
```

**Apache:**
```
C:\xampp\apache\logs\error.log
```

**MySQL:**
```
C:\xampp\mysql\data\mysql_error.log
```

### Debug Mode (Development Only):

Edit `.env`:
```env
APP_DEBUG=true
APP_ENV=local
```

Kemudian:
```cmd
clear-cache.bat
```

⚠️ Jangan lupa set kembali ke `production` dan `false` setelah debugging!

---

## 📞 Support

Jika mengalami masalah:

1. Check log files di atas
2. Lihat Troubleshooting section
3. Baca dokumentasi lengkap
4. Check `windows-firewall-config.txt` untuk network issues

---

## 🎉 Selamat!

Jika semua langkah diikuti dengan benar, aplikasi Helpdesk ITSO sekarang sudah:

- ✅ Berjalan di PC Windows
- ✅ Bisa diakses dari network
- ✅ Siap untuk production use
- ✅ Backup configured
- ✅ Secure dan optimized

**Happy Deploying! 🚀**

---

## 📝 License & Credits

**Aplikasi:** Helpdesk ITSO  
**Framework:** Laravel 12  
**PHP Version:** 8.2+  
**Database:** MySQL 8.0+

---

*Dokumentasi ini dibuat untuk memudahkan deployment di PC Windows biasa (non-server environment). Untuk production server deployment (Linux/Windows Server), lihat DEPLOYMENT_GUIDE.md*
