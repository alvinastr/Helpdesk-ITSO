# 🌐 Setup Local Network Deployment - Lengkap

**Solusi untuk deployment di local network tanpa koneksi internet**

---

## 📋 Daftar Masalah & Solusi

### ✅ Masalah 1: CDN Assets Tidak Load

**Problem:**
- Bootstrap CSS/JS tidak load
- Font Awesome icons tidak muncul
- Chart.js tidak render
- Fonts tidak tampil
- Tampilan aplikasi berantakan

**Solusi: Download & Host Assets Lokal**

#### Quick Setup:

```bash
# 1. Download semua CDN assets
download-cdn-assets.bat

# 2. Enable offline mode
toggle-offline-mode.bat
# Pilih: [1] Enable Offline Mode

# 3. Restart server
php artisan serve
```

#### File-file yang Dibuat:

1. `download-cdn-assets.bat` - Download semua assets dari CDN
2. `download-cdn-assets.ps1` - PowerShell version (lebih reliable)
3. `toggle-offline-mode.bat` - Switch between online/offline mode
4. `app-offline.blade.php` - Layout khusus offline mode

#### Manual Setup (Alternatif):

Jika script tidak jalan, download manual:

**Bootstrap:**
- CSS: https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css
- JS: https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js
- Save ke: `public/vendor/bootstrap/`

**Font Awesome:**
- CSS: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css
- Fonts: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/
- Save ke: `public/vendor/fontawesome/`

**Chart.js:**
- JS: https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js
- Save ke: `public/vendor/chartjs/`

---

### ✅ Masalah 2: WhatsApp API Butuh Internet

**Problem:**
- WhatsApp Business API butuh webhook dari internet
- Callback URL tidak bisa akses IP lokal (192.168.x.x)
- Webhook verification fail

**Solusi: Multiple Options**

Lihat panduan lengkap: `WHATSAPP_LOCAL_NETWORK_GUIDE.md`

#### Quick Decision Tree:

```
Apakah WhatsApp notification CRITICAL?
│
├─ NO → Disable WhatsApp
│        Setting: WHATSAPP_ENABLED=false
│        ✅ Paling mudah
│
└─ YES → Apakah bisa install Node.js?
         │
         ├─ YES → WhatsApp Web.js (RECOMMENDED)
         │         FREE, local, reliable
         │
         └─ NO → Email notification only
                  atau Ngrok (temporary)
```

---

## 🚀 Setup Lengkap Step-by-Step

### Phase 1: Download Assets (Butuh Internet - ONE TIME)

Koneksikan PC ke internet dulu untuk download assets.

**Option A: Automatic (Recommended)**

```batch
# Jalankan script
download-cdn-assets.bat

# Tunggu sampai selesai (5-10 menit tergantung koneksi)
```

**Option B: PowerShell (Jika batch gagal)**

```powershell
# Buka PowerShell as Administrator
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\download-cdn-assets.ps1
```

**Verify:**
Check folder `public/vendor/` harus ada:
- bootstrap/
- fontawesome/
- chartjs/

---

### Phase 2: Enable Offline Mode

```batch
# Jalankan script
toggle-offline-mode.bat

# Pilih: [1] Enable Offline Mode
```

**Manual (jika script gagal):**

1. Edit `.env`, tambahkan di bagian bawah:
```env
# Offline Mode - Local Network Deployment
OFFLINE_MODE=true
USE_LOCAL_ASSETS=true
```

2. Clear cache:
```bash
php artisan config:clear
php artisan config:cache
```

---

### Phase 3: Setup WhatsApp (Optional)

**Pilih salah satu:**

#### Option A: Disable WhatsApp (EASIEST)

Edit `.env`:
```env
WHATSAPP_ENABLED=false
```

#### Option B: Setup WhatsApp Web.js Bot

Ikuti: `WHATSAPP_LOCAL_NETWORK_GUIDE.md` - Section "WhatsApp Web.js"

Quick steps:
1. Install Node.js
2. Setup bot dengan script provided
3. Scan QR code
4. Update `.env`

---

### Phase 4: Setup Email (Local SMTP)

Untuk email notification di local network:

#### Option A: Mailtrap (Testing)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
```

#### Option B: Gmail SMTP

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

**Note:** Untuk Gmail, gunakan App Password, bukan password biasa.

#### Option C: Log Only (No Email)

```env
MAIL_MAILER=log
```

Email akan disimpan di `storage/logs/laravel.log` saja.

---

### Phase 5: Final Testing

#### Test 1: Assets Load

1. Jalankan server:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

2. Disconnect internet (cabut LAN/WiFi)

3. Akses: http://localhost:8000

4. Verify:
- ✅ Layout tampil dengan baik
- ✅ Icons Font Awesome muncul
- ✅ Bootstrap styling bekerja
- ✅ No error di browser console

#### Test 2: Network Access

1. Dari PC lain di network:
```
http://192.168.1.100:8000
```

2. Verify:
- ✅ Halaman load sempurna
- ✅ Icons dan styling OK
- ✅ Bisa login
- ✅ Bisa create ticket

#### Test 3: Notifications

1. Create ticket baru
2. Check:
- ✅ Email terkirim (jika enabled)
- ✅ WhatsApp terkirim (jika enabled)
- ✅ Atau skip jika disabled

---

## 🔧 Troubleshooting

### Problem: Assets masih load dari CDN

**Solution:**

1. Check `.env`:
```bash
findstr OFFLINE .env
```

Should show:
```
OFFLINE_MODE=true
USE_LOCAL_ASSETS=true
```

2. Clear cache:
```bash
php artisan config:clear
php artisan config:cache
```

3. Hard refresh browser: Ctrl+F5

---

### Problem: Icons tidak muncul

**Solution:**

1. Check files exist:
```bash
dir public\vendor\fontawesome
```

Should have:
- all.min.css
- webfonts folder

2. Check CSS content:
Open `public/vendor/fontawesome/all.min.css`
Font paths should be: `./webfonts/` (not `../webfonts/`)

3. Re-download if needed:
```bash
download-cdn-assets.bat
```

---

### Problem: Chart.js tidak render

**Solution:**

1. Check file:
```bash
dir public\vendor\chartjs
```

Should have: `chart.umd.min.js`

2. Check views using Chart.js:
Should use local path:
```blade
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
```

Not CDN:
```blade
<!-- Wrong: -->
<script src="https://cdn.jsdelivr.net/..."></script>
```

---

### Problem: WhatsApp bot error "Conflict: session duplicated"

**Solution:**

Hanya bisa 1 device per WhatsApp number.

1. Logout dari WhatsApp Web (jika ada)
2. Hapus session bot:
```bash
cd C:\whatsapp-bot
rmdir /s .wwebjs_auth
```
3. Restart bot dan scan QR lagi

---

### Problem: Fonts tidak load

**Solution:**

Fonts tidak critical, aplikasi tetap jalan tanpa custom fonts.

Jika ingin fix:
1. Download Nunito font dari Google Fonts
2. Save ke `public/vendor/fonts/`
3. Update CSS path

---

## 📊 Checklist Deployment Local Network

### Pre-Deployment (Dengan Internet)

- [ ] Download CDN assets: `download-cdn-assets.bat`
- [ ] Verify assets downloaded:
  - [ ] Bootstrap files exist
  - [ ] Font Awesome files exist
  - [ ] Chart.js file exist
- [ ] Setup WhatsApp bot (if needed)
- [ ] Test dengan internet ON dulu

### Post-Deployment (Tanpa Internet)

- [ ] Enable offline mode: `toggle-offline-mode.bat`
- [ ] Disconnect internet
- [ ] Test localhost access
- [ ] Test network access dari PC lain
- [ ] Test create ticket
- [ ] Test notifications
- [ ] Verify all features working

---

## 🎯 Configuration Summary

**For Local Network WITHOUT Internet:**

`.env` configuration:
```env
# Application
APP_NAME="Helpdesk ITSO"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.100:8000

# Database (local MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpdesk_itso
DB_USERNAME=root
DB_PASSWORD=

# Offline Mode
OFFLINE_MODE=true
USE_LOCAL_ASSETS=true

# WhatsApp (choose one)
# Option 1: Disabled
WHATSAPP_ENABLED=false

# Option 2: Local Bot
WHATSAPP_ENABLED=true
WHATSAPP_API_URL=http://192.168.1.100:3000/send

# Email (choose one)
# Option 1: Log only (no real email)
MAIL_MAILER=log

# Option 2: Gmail (needs internet for SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls

# Queue
QUEUE_CONNECTION=database
```

---

## 📝 Files Created

All files sudah ada di project root:

| File | Purpose |
|------|---------|
| `LOCAL_NETWORK_SOLUTION.md` | Overview masalah & solusi |
| `WHATSAPP_LOCAL_NETWORK_GUIDE.md` | Panduan lengkap WhatsApp setup |
| `download-cdn-assets.bat` | Script download assets (Windows) |
| `download-cdn-assets.ps1` | Script download assets (PowerShell) |
| `toggle-offline-mode.bat` | Switch online/offline mode |
| `resources/views/layouts/app-offline.blade.php` | Layout untuk offline mode |
| `LOCAL_NETWORK_SETUP_COMPLETE.md` | File ini - panduan lengkap |

---

## 🎓 Training Users

Untuk users yang akan menggunakan aplikasi:

**Beri tahu mereka:**

1. **Access URL:**
   - "Buka browser, ketik: http://192.168.1.100:8000"
   - Bookmark URL ini

2. **Important Notes:**
   - PC server harus menyala
   - Harus di network yang sama
   - Jika tidak bisa akses, check:
     - PC server menyala?
     - Server running? (check CMD window)
     - Network connection OK?

3. **Notifications:**
   - Email: [Akan/Tidak akan] terkirim
   - WhatsApp: [Akan/Tidak akan] terkirim
   - In-app: Selalu ada

---

## 🚨 Emergency Procedures

### Server Down

1. Check server PC power
2. Check XAMPP Control Panel - MySQL & Apache running?
3. Check CMD window - php artisan serve masih jalan?
4. Restart: `start-server.bat`

### Assets Tidak Load

1. Run: `toggle-offline-mode.bat`
2. Choose: [1] Enable Offline Mode
3. Restart server

### Database Error

1. Check MySQL running di XAMPP
2. Check `.env` credentials
3. Test di phpMyAdmin

---

## ✅ Success Criteria

Deployment berhasil jika:

- ✅ Aplikasi bisa diakses tanpa internet
- ✅ Tampilan sempurna (icons, styling, charts)
- ✅ Bisa diakses dari PC lain di network
- ✅ Create ticket berhasil
- ✅ Upload file berhasil
- ✅ Notifications working (sesuai config)
- ✅ All features functional

---

## 📞 Need Help?

Check documentation:
1. `LOCAL_NETWORK_SOLUTION.md` - Overview
2. `WHATSAPP_LOCAL_NETWORK_GUIDE.md` - WhatsApp setup
3. `FAQ_DEPLOYMENT_WINDOWS.md` - Common issues
4. `DEPLOYMENT_WINDOWS_PC.md` - Full deployment guide

---

**Setup Complete! Aplikasi siap untuk local network deployment! 🎉**
