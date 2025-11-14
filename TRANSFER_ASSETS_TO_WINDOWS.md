# 📦 Cara Transfer Assets ke PC Windows

## ✅ Status: Assets Sudah Di-Download!

Assets CDN sudah di-download di Mac/laptop Anda dan sudah di-zip.

**Lokasi file:** 
```
/Users/alvin/Documents/Proj/ITSO/public/vendor.zip
```

**Size:** ~1.5 MB (compressed)

**Isi:**
- ✅ Bootstrap CSS & JS
- ✅ Font Awesome Icons & Fonts
- ✅ Chart.js
- ✅ Axios
- ✅ Fonts CSS

---

## 📋 Langkah Transfer ke Windows PC

### Step 1: Copy File ke Windows

**Option A: USB Flash Drive**
1. Copy file `vendor.zip` ke USB
2. Colok USB ke PC Windows
3. Copy ke PC Windows (lokasi sementara, misal Desktop)

**Option B: Network Share** (jika Mac & PC di network yang sama)
1. Mac: System Preferences → Sharing → File Sharing (ON)
2. Di PC Windows, akses Mac via Network
3. Copy `vendor.zip`

**Option C: Cloud Storage** (Google Drive, Dropbox, dll)
1. Upload `vendor.zip` dari Mac
2. Download di PC Windows

**Option D: Email** (karena cuma 1.5MB)
1. Email ke diri sendiri dengan attachment
2. Download di PC Windows

---

### Step 2: Extract di PC Windows

1. Pastikan project sudah ada di PC Windows:
   ```
   C:\xampp\htdocs\helpdesk-itso\
   ```

2. Copy `vendor.zip` ke folder `public`:
   ```
   C:\xampp\htdocs\helpdesk-itso\public\vendor.zip
   ```

3. Extract (klik kanan → Extract Here)

4. Verify struktur folder:
   ```
   C:\xampp\htdocs\helpdesk-itso\public\
   ├── vendor\
   │   ├── bootstrap\
   │   │   ├── bootstrap.min.css
   │   │   └── bootstrap.bundle.min.js
   │   ├── fontawesome\
   │   │   ├── all.min.css
   │   │   └── webfonts\
   │   │       ├── fa-solid-900.woff2
   │   │       ├── fa-regular-400.woff2
   │   │       └── fa-brands-400.woff2
   │   ├── chartjs\
   │   │   └── chart.umd.min.js
   │   ├── fonts\
   │   │   └── nunito.css
   │   └── axios.min.js
   ```

5. Delete `vendor.zip` (sudah tidak perlu)

---

### Step 3: Enable Offline Mode di Windows

1. Buka Command Prompt di folder project:
   ```cmd
   cd C:\xampp\htdocs\helpdesk-itso
   ```

2. Jalankan script:
   ```cmd
   toggle-offline-mode.bat
   ```

3. Pilih: **[1] Enable Offline Mode**

4. Atau manual, edit `.env`, tambahkan:
   ```env
   # Offline Mode
   OFFLINE_MODE=true
   USE_LOCAL_ASSETS=true
   ```

5. Clear cache:
   ```cmd
   php artisan config:clear
   php artisan config:cache
   ```

---

### Step 4: Test Aplikasi

1. Start server:
   ```cmd
   php artisan serve --host=0.0.0.0 --port=8000
   ```

2. Buka browser: http://localhost:8000

3. **Test tanpa internet:**
   - Cabut kabel LAN / disconnect WiFi
   - Refresh halaman
   - Verify:
     - ✅ Layout tampil sempurna
     - ✅ Icons muncul
     - ✅ Styling bekerja
     - ✅ Charts render (di halaman KPI/dashboard)

4. **Test dari PC lain** (reconnect internet/LAN):
   - Dari PC lain: http://192.168.1.100:8000
   - Verify semua fitur OK

---

## 🔧 Troubleshooting

### Problem: Assets masih load dari CDN

**Check:**
1. Folder `public/vendor` exists?
   ```cmd
   dir C:\xampp\htdocs\helpdesk-itso\public\vendor
   ```

2. `.env` setting benar?
   ```cmd
   findstr OFFLINE .env
   ```
   
   Should show:
   ```
   OFFLINE_MODE=true
   USE_LOCAL_ASSETS=true
   ```

3. Cache cleared?
   ```cmd
   php artisan config:clear
   php artisan config:cache
   ```

4. Hard refresh browser: **Ctrl + F5**

---

### Problem: Icons tidak muncul

**Solution:**
1. Check font files:
   ```cmd
   dir C:\xampp\htdocs\helpdesk-itso\public\vendor\fontawesome\webfonts
   ```
   
   Should have: `fa-solid-900.woff2`, etc.

2. Check CSS file:
   ```cmd
   type C:\xampp\htdocs\helpdesk-itso\public\vendor\fontawesome\all.min.css
   ```
   
   Font paths should be: `./webfonts/` (not `../webfonts/`)

3. If still error, re-extract `vendor.zip`

---

### Problem: Chart tidak render

**Check:**
1. Chart.js file exists:
   ```cmd
   dir C:\xampp\htdocs\helpdesk-itso\public\vendor\chartjs
   ```

2. Browser console (F12) for errors

3. Clear browser cache: Ctrl + Shift + Delete

---

## ✅ Success Checklist

Setelah transfer dan setup, verify:

- [ ] Folder `public/vendor` ada dengan semua subfolder
- [ ] File CSS (Bootstrap, Font Awesome) ada
- [ ] File JS (Bootstrap, Chart.js, Axios) ada  
- [ ] Font files (.woff2) ada
- [ ] `.env` setting `OFFLINE_MODE=true`
- [ ] Config cache di-clear
- [ ] Test localhost - tampilan OK
- [ ] Test tanpa internet - masih OK
- [ ] Test dari PC lain - bisa akses

---

## 📝 Quick Commands Windows

```cmd
# Navigate to project
cd C:\xampp\htdocs\helpdesk-itso

# Check vendor folder
dir public\vendor

# Enable offline mode
toggle-offline-mode.bat

# Clear cache
php artisan config:clear
php artisan config:cache

# Start server
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 💾 Backup for Future

**Simpan `vendor.zip` untuk:**
- Re-deployment
- Setup di PC lain
- Restore jika ada masalah

**Lokasi saat ini:**
- Mac: `/Users/alvin/Documents/Proj/ITSO/public/vendor.zip`
- Backup juga ke cloud/external drive!

---

## 🎯 Next Steps

Setelah assets berhasil di-transfer:

1. ✅ Test aplikasi offline
2. ✅ Setup firewall untuk network access
3. ✅ Setup WhatsApp (pilih: disable atau bot lokal)
4. ✅ Setup backup automation
5. ✅ Training untuk users

---

**File ini:** `TRANSFER_ASSETS_TO_WINDOWS.md`

Panduan lainnya:
- `LOCAL_NETWORK_SETUP_COMPLETE.md` - Setup lengkap
- `WHATSAPP_LOCAL_NETWORK_GUIDE.md` - WhatsApp setup
- `DEPLOYMENT_WINDOWS_PC.md` - Full deployment guide
