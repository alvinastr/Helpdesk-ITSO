# 📋 Checklist Deployment Windows PC

**Project:** Helpdesk ITSO  
**Target:** PC Windows (Non-Server)  
**Tanggal:** _______________  
**Dilakukan oleh:** _______________

---

## ✅ Phase 1: Persiapan Software

### Software yang Dibutuhkan

- [ ] **XAMPP** atau **Laragon** (pilih salah satu)
  - [ ] PHP 8.2 atau lebih tinggi
  - [ ] MySQL 8.0 atau lebih tinggi
  - [ ] Apache Web Server
  
- [ ] **Composer** (PHP Package Manager)
  - [ ] Versi 2.x atau lebih tinggi
  
- [ ] **Git** (Optional, untuk update)

- [ ] **Text Editor**
  - [ ] Notepad++ / VSCode / Sublime (untuk edit .env)

### Verifikasi Instalasi

```cmd
php -v
# Output harus: PHP 8.2.x atau lebih tinggi

composer --version
# Output harus: Composer version 2.x

git --version
# Output harus: git version x.x.x
```

**Hasil Verifikasi:**
- PHP Version: _______________
- Composer Version: _______________
- Git Version: _______________ (optional)

---

## ✅ Phase 2: Setup Database

### phpMyAdmin

- [ ] Buka: http://localhost/phpmyadmin
- [ ] Login berhasil (root / no password)
- [ ] Buat database baru:
  - [ ] Nama: `helpdesk_itso`
  - [ ] Collation: `utf8mb4_unicode_ci`

### (Optional) Buat User Database Khusus

- [ ] Buat user: `helpdesk_user`
- [ ] Set password: _______________
- [ ] Grant privileges ke database `helpdesk_itso`

**Database Info:**
- Database Name: _______________
- Username: _______________
- Password: _______________

---

## ✅ Phase 3: Setup Aplikasi

### Copy Project

- [ ] Project dicopy ke folder yang benar:
  - XAMPP: `C:\xampp\htdocs\helpdesk-itso`
  - Laragon: `C:\laragon\www\helpdesk-itso`

**Lokasi Project:** _______________

### Install Dependencies

```cmd
cd [lokasi-project]
composer install --optimize-autoloader
```

- [ ] Composer install berhasil tanpa error
- [ ] Folder `vendor` sudah ada

### Setup Environment

- [ ] File `.env` sudah dibuat dari `.env.example`
- [ ] Edit `.env`:
  - [ ] `APP_NAME` diisi
  - [ ] `APP_ENV=production`
  - [ ] `APP_DEBUG=false`
  - [ ] `APP_URL` diisi (http://localhost:8000)
  - [ ] Database credentials benar
  - [ ] `MAIL_*` dikonfigurasi (jika pakai email)
  
- [ ] Generate app key: `php artisan key:generate`
  - APP_KEY: _______________ (otomatis terisi)

### Database Migration

```cmd
php artisan migrate --force
```

- [ ] Migration berhasil tanpa error
- [ ] Semua tabel terbuat di database
- [ ] Check di phpMyAdmin: table `users`, `tickets`, dll ada

### Seed Admin User

```cmd
php artisan db:seed --class=AdminSeeder
```

- [ ] Seeding berhasil
- [ ] Admin user terbuat:
  - Email: `admin@itso.com`
  - Password: `password123` (ganti nanti!)

### Setup Storage

```cmd
php artisan storage:link
```

- [ ] Storage link berhasil dibuat
- [ ] Folder `public/storage` ada

### Optimization

```cmd
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

- [ ] Config cached
- [ ] Routes cached
- [ ] Views cached

---

## ✅ Phase 4: Testing Lokal

### Start Server

**Option A: PHP Built-in Server**
```cmd
php artisan serve --host=0.0.0.0 --port=8000
```

**Option B: Apache (Virtual Host)**
- [ ] Virtual host dikonfigurasi
- [ ] Apache restarted

- [ ] Server berjalan tanpa error

### Test Akses

- [ ] Buka browser: http://localhost:8000
- [ ] Homepage muncul tanpa error
- [ ] Login page bisa diakses
- [ ] Login dengan admin berhasil
  - Email: `admin@itso.com`
  - Password: `password123`
- [ ] Dashboard admin tampil dengan benar

### Test Fitur Utama

- [ ] **User Management**
  - [ ] List users tampil
  - [ ] Create user baru berhasil
  - [ ] Edit user berhasil
  
- [ ] **Ticket Creation**
  - [ ] User bisa create ticket
  - [ ] Upload file berhasil
  - [ ] Ticket muncul di admin dashboard
  
- [ ] **Ticket Approval**
  - [ ] Admin bisa approve ticket
  - [ ] Admin bisa reject ticket
  - [ ] Status update berhasil
  
- [ ] **Ticket Management**
  - [ ] View ticket detail
  - [ ] Update ticket status
  - [ ] Add comments/replies
  - [ ] Close ticket

- [ ] **KPI Dashboard**
  - [ ] KPI metrics tampil
  - [ ] Statistics benar
  - [ ] Charts render

### Check Logs

- [ ] Tidak ada error di `storage/logs/laravel.log`
- [ ] Tidak ada error di Apache log (jika pakai Apache)

**Notes (jika ada issue):**
_______________________________________________
_______________________________________________
_______________________________________________

---

## ✅ Phase 5: Network Setup

### Get IP Address

```cmd
ipconfig
```

**IP Address PC:** _______________

### Set Static IP (Recommended)

- [ ] Network adapter properties opened
- [ ] Static IP configured:
  - IP: _______________
  - Subnet: 255.255.255.0
  - Gateway: _______________
  - DNS: 8.8.8.8 / 8.8.4.4

### Configure Firewall

**Option A: Allow Program**
- [ ] PHP.exe allowed di firewall
- [ ] httpd.exe allowed di firewall (jika pakai Apache)

**Option B: Open Port**
- [ ] Port 8000 open (jika pakai artisan serve)
- [ ] Port 80 open (jika pakai Apache)

### Update APP_URL

- [ ] Edit `.env`: `APP_URL=http://[IP-ADDRESS]:8000`
- [ ] Clear config: `php artisan config:clear`
- [ ] Cache config: `php artisan config:cache`

---

## ✅ Phase 6: Testing dari PC Lain

### Network Connectivity

Dari PC lain di network yang sama:

- [ ] Ping ke server: `ping [IP-ADDRESS]` berhasil
- [ ] Telnet ke port: `telnet [IP-ADDRESS] 8000` connect

### Application Access

- [ ] Buka browser: http://[IP-ADDRESS]:8000
- [ ] Homepage muncul
- [ ] Login berhasil
- [ ] Buat ticket berhasil
- [ ] Upload file berhasil

**Test dari PC:**
- PC 1 (IP: _______________): [ ] OK / [ ] FAIL
- PC 2 (IP: _______________): [ ] OK / [ ] FAIL
- PC 3 (IP: _______________): [ ] OK / [ ] FAIL

**Notes:**
_______________________________________________
_______________________________________________

---

## ✅ Phase 7: Security & Production

### Update Credentials

- [ ] Admin password diganti dari default
- [ ] Database password set (jika belum)
- [ ] `.env` permissions restricted

### Production Settings

- [ ] `.env` check:
  ```env
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=[URL-Production]
  ```

- [ ] Cache optimization:
  ```cmd
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

### Backup Setup

- [ ] Buat folder backup: `mkdir backups`
- [ ] Test manual backup: `backup-database.bat`
- [ ] Setup automated backup:
  - [ ] Windows Task Scheduler configured
  - [ ] Schedule: Daily, ___ jam
  - [ ] Test run berhasil

**Backup Location:** _______________

---

## ✅ Phase 8: Optional Features

### Queue Worker (untuk WhatsApp/Email)

- [ ] WhatsApp API configured di `.env`
- [ ] Email SMTP configured di `.env`
- [ ] Queue worker tested: `start-queue.bat`
- [ ] Queue worker running as service (NSSM)

### Auto-Start Setup

- [ ] NSSM installed
- [ ] Server service created
- [ ] Queue service created (jika perlu)
- [ ] Services start on boot
- [ ] Test restart PC - aplikasi auto-start

---

## ✅ Phase 9: Documentation

### User Documentation

- [ ] User guide tersedia
- [ ] Admin guide tersedia
- [ ] Troubleshooting guide tersedia
- [ ] Contact support info tersedia

### Technical Documentation

- [ ] Server specifications documented:
  - PC Model: _______________
  - CPU: _______________
  - RAM: _______________
  - Storage: _______________
  - OS: Windows ___ (Home/Pro)
  
- [ ] Network configuration documented:
  - IP Address: _______________
  - Subnet: _______________
  - Gateway: _______________
  - DNS: _______________
  
- [ ] Application info documented:
  - Project Path: _______________
  - Database Name: _______________
  - Access URL: _______________
  - Admin Email: _______________

### Handover

- [ ] Admin credentials documented (securely)
- [ ] Server access documented
- [ ] Emergency contact list
- [ ] Training completed (jika perlu)

---

## ✅ Phase 10: Final Checks

### Performance

- [ ] Page load time < 3 seconds
- [ ] Upload file berhasil (test with different sizes)
- [ ] Database queries efficient
- [ ] No memory leaks

### Monitoring

- [ ] Check disk space: > 5GB free
- [ ] Check memory usage: < 80%
- [ ] Check CPU usage: normal
- [ ] Application logs clean

### Maintenance Schedule

- [ ] Daily: Check application running
- [ ] Weekly: Check backup files
- [ ] Monthly: Update dependencies (jika perlu)
- [ ] Quarterly: Full system check

**Next Maintenance Date:** _______________

---

## 📝 Sign-Off

**Deployment Completed by:**

Name: _______________
Signature: _______________
Date: _______________

**Verified by:**

Name: _______________
Signature: _______________
Date: _______________

**Status:**
- [ ] ✅ PRODUCTION READY
- [ ] ⚠️ PRODUCTION WITH ISSUES (explain below)
- [ ] ❌ NOT READY (explain below)

**Notes / Issues:**
_______________________________________________
_______________________________________________
_______________________________________________
_______________________________________________

---

## 📞 Emergency Contacts

**Technical Support:**
- Name: _______________
- Phone: _______________
- Email: _______________

**Database Admin:**
- Name: _______________
- Phone: _______________
- Email: _______________

**IT Manager:**
- Name: _______________
- Phone: _______________
- Email: _______________

---

## 🔗 Quick Reference

**Application URL:** http://_______________:8000

**Admin Login:** 
- Email: _______________
- Password: (see secure document)

**phpMyAdmin:** http://localhost/phpmyadmin

**Server Location:** _______________

**Backup Location:** _______________

**Important Files:**
- `.env` configuration
- `storage/logs/laravel.log`
- `backups/` folder

---

**End of Checklist**

Print this checklist and fill it during deployment!
