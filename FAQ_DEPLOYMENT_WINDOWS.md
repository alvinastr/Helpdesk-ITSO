# ❓ FAQ - Deployment Windows PC

**Pertanyaan yang sering ditanyakan tentang deployment Helpdesk ITSO di PC Windows**

---

## 📦 Instalasi & Setup

### Q: Apa bedanya XAMPP dan Laragon? Mana yang lebih baik?

**A:** 
- **XAMPP**: Lebih populer, banyak tutorial, cocok untuk pemula, tapi setup manual lebih banyak
- **Laragon**: Lebih modern, auto-setup virtual host, include lebih banyak tools, lebih user-friendly

**Rekomendasi**: Laragon untuk kemudahan, XAMPP jika Anda sudah familiar dengannya.

---

### Q: Apakah harus menggunakan PHP 8.2? Bisakah pakai versi lain?

**A:** 
- **Minimum**: PHP 8.2
- **Recommended**: PHP 8.2 atau 8.3
- **Tidak bisa**: PHP 7.x atau PHP 8.0/8.1 (Laravel 12 requirement)

---

### Q: Apakah bisa tanpa Composer?

**A:** Tidak. Composer adalah package manager yang wajib untuk Laravel. Tanpa Composer, dependencies aplikasi tidak bisa di-install.

---

### Q: Apakah perlu install Node.js / npm?

**A:** Tidak wajib untuk deployment production. Node.js hanya diperlukan jika Anda ingin:
- Compile frontend assets (CSS/JS) dari source
- Development dengan hot reload

Untuk production deployment biasa, tidak perlu Node.js.

---

## 🗄️ Database

### Q: Bisakah pakai database selain MySQL?

**A:** Secara teknis bisa (PostgreSQL, SQLite, SQL Server), tapi:
- Aplikasi ini sudah ditest dengan MySQL
- Migrations sudah disesuaikan dengan MySQL
- Untuk kemudahan, gunakan MySQL/MariaDB

---

### Q: Apakah database harus bernama 'helpdesk_itso'?

**A:** Tidak. Anda bisa gunakan nama apapun, asal sesuaikan di file `.env`:
```env
DB_DATABASE=nama_database_anda
```

---

### Q: Kenapa tidak bisa connect ke database?

**A:** Cek hal berikut:
1. MySQL sudah running? (Check di XAMPP/Laragon Control Panel)
2. Credentials di `.env` benar?
3. Database sudah dibuat di phpMyAdmin?
4. Test connection di phpMyAdmin dengan username/password yang sama

---

### Q: Bagaimana cara reset database?

**A:**
```cmd
# Rollback semua migrations
php artisan migrate:fresh

# Atau rollback + re-seed
php artisan migrate:fresh --seed
```

⚠️ **WARNING**: Ini akan HAPUS semua data!

---

## 🌐 Network & Akses

### Q: Kenapa tidak bisa diakses dari PC lain?

**A:** Kemungkinan penyebab:
1. **Firewall**: Allow port 80/8000 di Windows Firewall
2. **Server tidak running**: Pastikan server berjalan (`start-server.bat`)
3. **Network berbeda**: Kedua PC harus di network yang sama
4. **IP salah**: Gunakan IP yang benar (cek dengan `ipconfig`)

Test dengan:
```cmd
# Dari PC client
ping 192.168.1.100
telnet 192.168.1.100 8000
```

---

### Q: IP address PC saya selalu berubah saat restart. Bagaimana?

**A:** Set **Static IP**:
1. Control Panel → Network Connections
2. Klik kanan network adapter → Properties
3. Double-click "Internet Protocol Version 4 (TCP/IPv4)"
4. Pilih "Use the following IP address"
5. Isi:
   - IP address: 192.168.1.100 (sesuaikan)
   - Subnet mask: 255.255.255.0
   - Default gateway: 192.168.1.1 (IP router)
   - DNS: 8.8.8.8

---

### Q: Bisakah diakses dari internet (dari luar network)?

**A:** Bisa, tapi **TIDAK DIREKOMENDASIKAN** untuk PC biasa karena:
- Security risks (PC tidak se-secure server)
- Dynamic IP dari ISP (bisa berubah)
- Uptime tidak terjamin (PC bisa mati)
- Bandwidth terbatas

Jika benar-benar perlu:
1. Port forwarding di router (port 80/8000 → IP PC)
2. Dynamic DNS (untuk IP yang berubah)
3. Setup SSL certificate
4. Hardening security

**Better solution**: Gunakan VPS/Cloud hosting untuk akses internet.

---

### Q: Bagaimana cara pakai domain custom (misal: helpdesk.mycompany.com)?

**A:** 

**Untuk akses lokal saja:**
Edit `C:\Windows\System32\drivers\etc\hosts` (as admin):
```
192.168.1.100 helpdesk.mycompany.com
```

**Untuk domain real:**
- Butuh domain yang di-purchase
- Setup DNS A record pointing ke IP publik Anda
- Port forwarding di router
- SSL certificate (Let's Encrypt)
- **NOT RECOMMENDED untuk PC biasa**

---

## 🚀 Performance

### Q: Aplikasi lambat. Bagaimana cara mempercepat?

**A:** 

**1. Optimize Laravel:**
```cmd
optimize-production.bat
```

**2. Enable PHP opcache:**
Edit `C:\xampp\php\php.ini`:
```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

**3. Upgrade hardware:**
- RAM: Minimal 8GB
- Storage: Gunakan SSD

**4. Clean logs:**
```cmd
# Hapus old logs
del storage\logs\laravel.log
```

---

### Q: Berapa banyak user yang bisa dilayani PC Windows?

**A:** Tergantung specs PC:

**Low-end PC** (4GB RAM, HDD):
- 5-10 concurrent users

**Mid-range PC** (8GB RAM, SSD):
- 20-50 concurrent users

**High-end PC** (16GB+ RAM, SSD):
- 50-100 concurrent users

Untuk lebih dari 100 users, pertimbangkan dedicated server.

---

## 🔒 Security

### Q: Apakah aman untuk production?

**A:** Aman untuk **internal/intranet use** jika:
- ✅ PC di ruangan yang secure
- ✅ Network internal (tidak exposed ke internet)
- ✅ Firewall properly configured
- ✅ Strong passwords
- ✅ Regular backups
- ✅ `APP_DEBUG=false`

**Tidak aman** untuk:
- ❌ Public internet access
- ❌ Sensitive/critical data tanpa encryption
- ❌ High-availability requirements

---

### Q: Bagaimana cara ganti password admin?

**A:** 

**Via aplikasi:**
1. Login sebagai admin
2. Pergi ke Profile/Settings
3. Change Password

**Via database:**
```sql
-- Di phpMyAdmin
UPDATE users 
SET password = '$2y$10$...' 
WHERE email = 'admin@itso.com';
```
(Tapi harus hash password dulu dengan bcrypt)

**Via Artisan:**
```cmd
php artisan tinker
>>> $user = User::where('email', 'admin@itso.com')->first();
>>> $user->password = Hash::make('new_password');
>>> $user->save();
```

---

### Q: File .env apakah harus di-protect?

**A:** Ya! File `.env` berisi credentials penting. Laravel sudah protect secara default:
- File `.env` tidak bisa diakses via web (ada `.htaccess` protection)
- Jangan commit `.env` ke Git (sudah ada di `.gitignore`)
- Set file permissions (jika di Linux/shared folder)

---

## 💾 Backup & Recovery

### Q: Seberapa sering harus backup?

**A:** 

**Minimum:**
- Daily backup (jika data penting)

**Recommended:**
- Before major changes (sebelum update/maintenance)
- Daily automated backup
- Weekly offsite backup (external drive)

---

### Q: Bagaimana cara restore dari backup?

**A:**

**1. Via phpMyAdmin:**
1. Login ke phpMyAdmin
2. Pilih database `helpdesk_itso`
3. Tab "Import"
4. Choose file (backup SQL file)
5. Click "Go"

**2. Via Command Line:**
```cmd
C:\xampp\mysql\bin\mysql.exe -u root helpdesk_itso < backups\backup_20250113.sql
```

---

### Q: Apakah file uploaded users juga di-backup?

**A:** Script `backup-database.bat` hanya backup **database**. 

Untuk backup **files**, copy folder:
```
storage\app\public\
```

**Better approach**: Backup full folder project secara berkala.

---

## 🔧 Maintenance

### Q: Bagaimana cara update aplikasi saat ada versi baru?

**A:**

```cmd
# 1. Backup dulu
backup-database.bat

# 2. Pull changes (jika pakai Git)
git pull origin main

# 3. Update dependencies
composer install

# 4. Run migrations (jika ada)
php artisan migrate --force

# 5. Clear cache
clear-cache.bat

# 6. Optimize
optimize-production.bat
```

---

### Q: Bagaimana kalau PC harus restart/mati?

**A:** 

**Untuk restart terencana:**
1. Informasikan ke users
2. Stop server gracefully
3. Restart PC
4. Start server lagi

**Untuk auto-start after reboot:**
Setup aplikasi sebagai Windows Service menggunakan NSSM (lihat dokumentasi lengkap).

---

### Q: Bagaimana cara cek log errors?

**A:**

**Laravel log:**
```cmd
type storage\logs\laravel.log
```

**Apache log:**
```cmd
type C:\xampp\apache\logs\error.log
```

**MySQL log:**
```cmd
type C:\xampp\mysql\data\mysql_error.log
```

---

## 📱 Features

### Q: Apakah fitur WhatsApp wajib?

**A:** Tidak. WhatsApp notification adalah optional. Aplikasi tetap jalan tanpa WhatsApp, hanya notifikasi via WhatsApp yang tidak akan terkirim.

---

### Q: Bagaimana cara setup email notification?

**A:** Edit `.env`:

**Gmail:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

**Note**: Untuk Gmail, gunakan "App Password", bukan password biasa.

---

### Q: Queue worker itu apa? Wajib dijalankan?

**A:** 

Queue worker memproses background jobs seperti:
- Sending WhatsApp notifications
- Sending email notifications
- Other async tasks

**Wajib** jika pakai WhatsApp/Email notifications.  
**Tidak wajib** jika tidak pakai fitur notifikasi.

Jalankan dengan: `start-queue.bat`

---

## ⚠️ Troubleshooting

### Q: Error "Port 80 already in use"

**A:** 

**Cek program yang pakai port 80:**
```cmd
netstat -ano | findstr :80
```

**Solusi:**
1. Stop program yang pakai port 80 (Skype, IIS, etc)
2. Atau ganti port Apache ke 8080
3. Atau pakai `php artisan serve --port=8000`

---

### Q: Error "Class not found"

**A:**
```cmd
composer dump-autoload
clear-cache.bat
```

---

### Q: Error "Permission denied" pada storage

**A:**
```cmd
icacls storage /grant Everyone:F /T
icacls bootstrap\cache /grant Everyone:F /T
```

---

### Q: Error "SQLSTATE[HY000] [1049] Unknown database"

**A:** Database belum dibuat. Buat database di phpMyAdmin dulu dengan nama yang sama seperti di `.env` (default: `helpdesk_itso`).

---

### Q: Error "No application encryption key has been specified"

**A:**
```cmd
php artisan key:generate
php artisan config:clear
```

---

### Q: Page tampil tapi tanpa CSS/JS (no styling)

**A:**

**1. Clear cache:**
```cmd
php artisan cache:clear
php artisan view:clear
```

**2. Check APP_URL:**
Pastikan `APP_URL` di `.env` sesuai dengan URL yang Anda akses.

**3. Check public folder:**
Pastikan web server pointing ke folder `public/`.

---

### Q: Error "500 Internal Server Error"

**A:**

**1. Enable debug mode (temporary):**
Edit `.env`:
```env
APP_DEBUG=true
```

Kemudian:
```cmd
clear-cache.bat
```

Refresh browser, akan muncul error message detail.

**2. Check log:**
```cmd
type storage\logs\laravel.log
```

**3. Setelah fixed, jangan lupa disable debug:**
```env
APP_DEBUG=false
```

---

## 💡 Best Practices

### Q: Apa yang harus dilakukan setiap hari?

**A:**

**Daily checklist:**
- [ ] Check aplikasi masih running
- [ ] Check log for errors
- [ ] Check backup berhasil
- [ ] Check disk space
- [ ] Monitor user complaints

---

### Q: Apa yang harus dilakukan setiap minggu?

**A:**

**Weekly checklist:**
- [ ] Review backup files
- [ ] Check system resources (CPU, RAM, Disk)
- [ ] Clear old logs
- [ ] Test restore dari backup
- [ ] Review security logs

---

### Q: Apa yang harus dilakukan setiap bulan?

**A:**

**Monthly checklist:**
- [ ] Update dependencies (composer update)
- [ ] Windows updates
- [ ] XAMPP/Laragon updates
- [ ] Review user accounts
- [ ] Database optimization
- [ ] Full system backup to external drive

---

## 🎓 Training & Documentation

### Q: Apakah ada user manual?

**A:** Ya, lihat file:
- `USER_GUIDE.md` - Untuk end users
- `DEPLOYMENT_WINDOWS_PC.md` - Untuk admin/IT
- `KPI_DASHBOARD_USER_GUIDE.md` - Untuk KPI features

---

### Q: Bagaimana cara training user?

**A:** 

**Training outline:**
1. **Basic navigation** (15 menit)
   - Login
   - Dashboard overview
   - Menu navigation

2. **Create ticket** (30 menit)
   - Fill form
   - Upload files
   - Submit ticket

3. **Track ticket** (15 menit)
   - View ticket status
   - Add comments
   - Close ticket

4. **Reports & KPI** (20 menit)
   - View statistics
   - Generate reports
   - Export data

**Total**: ~1.5 jam

---

## 📞 Getting Help

### Q: Di mana saya bisa mendapat bantuan?

**A:**

**Dokumentasi:**
- `README_DEPLOYMENT_WINDOWS.md` - Overview
- `DEPLOYMENT_WINDOWS_PC.md` - Detailed guide
- `QUICK_START_WINDOWS.md` - Quick reference
- File ini (FAQ)

**Log files:**
- `storage/logs/laravel.log`
- `C:\xampp\apache\logs\error.log`

**Testing:**
- Test dengan `APP_DEBUG=true` (hati-hati, jangan di production!)

---

### Q: Apakah ada support berbayar?

**A:** Ini tergantung vendor/developer aplikasi. Kontak developer untuk support berbayar atau konsultasi.

---

## 🔮 Advanced

### Q: Bisakah multi-server / load balancing?

**A:** Secara teknis bisa, tapi kompleks dan overkill untuk PC Windows deployment. Jika butuh high-availability, gunakan proper server/cloud infrastructure.

---

### Q: Bisakah pakai Redis untuk cache/queue?

**A:** Bisa. Install Redis di Windows, lalu edit `.env`:
```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Tapi untuk deployment PC Windows sederhana, `file` cache sudah cukup.

---

### Q: Bisakah dijalankan sebagai Windows Service?

**A:** Ya, menggunakan NSSM (Non-Sucking Service Manager). Lihat panduan lengkap di `DEPLOYMENT_WINDOWS_PC.md` section "Setup sebagai Windows Service".

---

### Q: Bagaimana cara monitoring uptime?

**A:**

**Simple monitoring:**
- Buat scheduled task untuk ping aplikasi setiap 5 menit
- Log hasilnya ke file
- Review log setiap hari

**Advanced monitoring:**
- Install Uptime Kuma (open source monitoring tool)
- Setup email alerts
- Dashboard monitoring

---

**Masih ada pertanyaan?** Buat issue di repository atau kontak developer!

---

*Last updated: 2025-01-13*
