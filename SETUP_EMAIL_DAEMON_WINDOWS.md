# Setup Email Auto-Fetch Daemon di Windows

## Cara Kerja:
Daemon adalah program yang berjalan **terus-menerus** di background, cek email setiap 5 menit secara otomatis.

---

## 🚀 Cara Setup (SUPER MUDAH):

### Opsi 1: Double-Click File BAT (PALING MUDAH)

1. **Buka Windows Explorer** → Navigate ke `C:\laragon\www\ITSO`

2. **Double-click file:** `run-email-daemon.bat`

3. **Window Command Prompt akan muncul** dengan output seperti ini:
   ```
   ======================================
     ITSO Helpdesk - Email Auto-Fetch
   ======================================
   
   Starting daemon process...
   Press Ctrl+C to stop
   
   🚀 Email Fetch Daemon started!
   ⏱️  Checking for new emails every 300 seconds (5 menit)
   🛑 Press Ctrl+C to stop
   
   ─────────────────────────────────────────────
   📬 Fetch #1 - 2025-11-27 10:30:00
   ✅ Success: 3 tickets created
   ⏳ Next check at 10:35:00
   ```

4. **BIARKAN WINDOW TETAP TERBUKA** - daemon akan jalan terus
   - Minimize aja window-nya, jangan di-close
   - Setiap 5 menit akan otomatis fetch email

5. **Untuk Stop:** Tekan `Ctrl+C` di window Command Prompt

---

### Opsi 2: Run via PowerShell

```powershell
# Pindah ke direktori
cd C:\laragon\www\ITSO

# Jalankan daemon (5 menit interval)
php artisan emails:fetch-daemon --interval=300

# Atau custom interval (contoh: 10 menit = 600 detik)
php artisan emails:fetch-daemon --interval=600
```

---

## 🎯 Kelebihan Daemon vs Task Scheduler:

| Fitur | Daemon | Task Scheduler |
|-------|--------|----------------|
| **Setup** | ✅ Super mudah (1 double-click) | ❌ Kompleks (banyak setting) |
| **Debugging** | ✅ Lihat output real-time | ❌ Susah debug (hidden errors) |
| **Reliability** | ✅ Jalan terus, tidak skip | ⚠️ Kadang skip/miss schedule |
| **Control** | ✅ Stop/start kapan saja | ❌ Harus lewat Task Scheduler UI |
| **Visual Feedback** | ✅ Live log di console | ❌ Harus cek log file |
| **Permissions** | ✅ Run as current user | ⚠️ Permission issues |

---

## 🔧 Opsi Interval:

Edit `run-email-daemon.bat` untuk ganti interval:

```batch
:: Setiap 3 menit (180 detik)
php artisan emails:fetch-daemon --interval=180

:: Setiap 5 menit (300 detik) - DEFAULT
php artisan emails:fetch-daemon --interval=300

:: Setiap 10 menit (600 detik)
php artisan emails:fetch-daemon --interval=600

:: Setiap 1 menit (60 detik) - untuk testing
php artisan emails:fetch-daemon --interval=60
```

---

## 📊 Monitoring Real-Time:

Daemon menampilkan output langsung di console:

```
─────────────────────────────────────────────
📬 Fetch #1 - 2025-11-27 10:30:00
✅ Success: 3 tickets created
⏳ Next check at 10:35:00

─────────────────────────────────────────────
📬 Fetch #2 - 2025-11-27 10:35:00
📭 No new emails to process
⏭️  Skipped: 2 (already processed)
⏳ Next check at 10:40:00

─────────────────────────────────────────────
📬 Fetch #3 - 2025-11-27 10:40:00
❌ Failed: 1
   → Cannot connect to IMAP server
⏳ Next check at 10:45:00
```

Dengan ini Anda bisa **langsung lihat** apakah ada masalah atau tidak.

---

## 🛡️ Auto-Start saat PC Restart:

### Opsi A: Shortcut di Startup Folder (RECOMMENDED)

1. **Buka Run** (Windows + R) → Ketik: `shell:startup` → Enter

2. **Buat Shortcut:**
   - Klik kanan di folder Startup → New → Shortcut
   - Target: `C:\laragon\www\ITSO\run-email-daemon.bat`
   - Name: `ITSO Email Auto-Fetch`

3. **Selesai!** Setiap kali PC restart, daemon akan auto-start

### Opsi B: Windows Service (Advanced)

Jika mau lebih advanced, bisa pakai NSSM (Non-Sucking Service Manager):

```powershell
# 1. Download NSSM
# https://nssm.cc/download

# 2. Install service
nssm install ITSOEmailFetch "C:\laragon\bin\php\php-8.3.26\php.exe"
nssm set ITSOEmailFetch AppDirectory "C:\laragon\www\ITSO"
nssm set ITSOEmailFetch AppParameters "artisan emails:fetch-daemon --interval=300"

# 3. Start service
nssm start ITSOEmailFetch
```

---

## ❓ FAQ:

### Q: Kalau tutup Command Prompt window, daemon berhenti?
**A:** Ya. Solusinya:
- Minimize aja, jangan close
- Atau pakai Windows Service (NSSM) agar jalan di background tanpa window

### Q: Kalau restart PC, harus start manual lagi?
**A:** Ya, kecuali sudah setup di Startup Folder atau sebagai Service

### Q: Bisa lihat history fetch sebelumnya?
**A:** Ya, cek database:
```powershell
php artisan tinker --execute="\$logs = \App\Models\EmailFetchLog::orderBy('fetch_started_at', 'desc')->take(10)->get(['fetch_started_at', 'status', 'successful', 'failed']); foreach (\$logs as \$log) { echo \$log->fetch_started_at . ' | ' . \$log->status . ' | Success: ' . \$log->successful . '\n'; }"
```

### Q: Daemon crash, auto-restart sendiri?
**A:** Tidak. Kalau crash, harus start manual lagi. Atau gunakan NSSM service yang bisa auto-restart on failure.

### Q: Bisa run di Mac?
**A:** Ya! Ganti file .bat dengan script bash:
```bash
#!/bin/bash
cd /Users/alvin/Documents/Proj/ITSO
php artisan config:clear
php artisan emails:fetch-daemon --interval=300
```
Simpan sebagai `run-email-daemon.sh`, chmod +x, dan jalankan: `./run-email-daemon.sh`

---

## 🎬 Quick Start (TL;DR):

1. Double-click `run-email-daemon.bat`
2. Lihat output di console
3. Minimize window (jangan close)
4. Done! ✅

**Untuk auto-start saat PC restart:**
1. Windows + R → `shell:startup`
2. Buat shortcut ke `run-email-daemon.bat`
3. Done! ✅

---

## 🐛 Troubleshooting:

### Error: "Could not open input file: artisan"
**Solution:** Path salah. Edit `run-email-daemon.bat`, pastikan `cd /d C:\laragon\www\ITSO` benar.

### Error: "IMAP_HOST is not configured"
**Solution:** File `.env` tidak ada atau config cache lama. Jalankan:
```powershell
cd C:\laragon\www\ITSO
php artisan config:clear
```

### Error: "Cannot connect to IMAP server"
**Solution:** 
- Cek koneksi network ke 10.103.7.18
- Pastikan firewall tidak block port 993
- Test: `telnet 10.103.7.18 993`

### Daemon berhenti sendiri
**Solution:** Lihat error di console. Biasanya karena:
- MySQL service mati
- Network disconnect
- PHP crash (cek php_error.log)

---

## 📝 Logs:

- **Real-time:** Lihat di console window
- **Laravel Log:** `C:\laragon\www\ITSO\storage\logs\laravel.log`
- **Database Log:** Table `email_fetch_logs`

---

Selamat mencoba! Daemon ini **jauh lebih mudah** dan reliable daripada Task Scheduler. 🎉
