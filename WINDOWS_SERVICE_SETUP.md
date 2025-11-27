# Setup Email Auto-Fetch sebagai Windows Service

## 🎯 Kenapa Windows Service?

**Dengan Windows Service:**
✅ Auto-start saat PC nyala (bahkan sebelum login!)
✅ Jalan di background tanpa window
✅ Auto-restart kalau crash
✅ Bisa diatur lewat Services Manager
✅ Tidak terpengaruh user logout
✅ Production-ready

---

## 📥 Persiapan: Install NSSM

NSSM (Non-Sucking Service Manager) adalah tool untuk membuat Windows Service dari aplikasi apapun.

### Cara 1: Manual Download

1. **Download NSSM:** https://nssm.cc/download
   - Download `nssm-2.24.zip` (atau versi terbaru)

2. **Extract ke C:\nssm**
   - Extract file zip
   - Copy folder `nssm-2.24` ke `C:\`
   - Rename jadi `C:\nssm`
   - Pastikan ada file `C:\nssm\nssm.exe`

### Cara 2: PowerShell (Otomatis)

```powershell
# Jalankan PowerShell sebagai Administrator
# Klik kanan PowerShell > Run as Administrator

# Download dan extract NSSM
Invoke-WebRequest -Uri https://nssm.cc/release/nssm-2.24.zip -OutFile $env:TEMP\nssm.zip
Expand-Archive $env:TEMP\nssm.zip -DestinationPath C:\ -Force

# Rename folder
if (Test-Path "C:\nssm-2.24") {
    if (Test-Path "C:\nssm") { Remove-Item "C:\nssm" -Recurse -Force }
    Rename-Item "C:\nssm-2.24" "C:\nssm"
}

# Verify
if (Test-Path "C:\nssm\nssm.exe") {
    Write-Host "✅ NSSM installed successfully!" -ForegroundColor Green
} else {
    Write-Host "❌ Installation failed" -ForegroundColor Red
}
```

---

## 🚀 Instalasi Service

### **Langkah 1: Klik Kanan > Run as Administrator**

File: `install-email-service.bat`

**PENTING:** Harus dijalankan sebagai Administrator!

```batch
# Klik kanan file install-email-service.bat
# Pilih: "Run as administrator"
```

### **Langkah 2: Ikuti Proses Instalasi**

Script akan otomatis:
1. ✅ Check NSSM terinstall
2. ✅ Check project path benar
3. ✅ Check PHP terinstall
4. ✅ Install service ke Windows
5. ✅ Start service otomatis

Output:
```
========================================
  ITSO Email Fetch Service Installer
========================================

[1/5] Checking NSSM installation...
[OK] NSSM found at C:\nssm\nssm.exe

[2/5] Checking project path...
[OK] Project found

[3/5] Checking PHP...
[OK] PHP found

[4/5] Installing service...
[OK] Service installed

[5/5] Starting service...
SERVICE_RUNNING

========================================
  Installation Complete!
========================================

Service Name: ITSOEmailFetch
Status: Running

Service akan AUTO-START setiap kali PC restart
```

---

## 🎮 Mengelola Service

### Via Command Prompt (CMD):

```cmd
:: Start service
net start ITSOEmailFetch

:: Stop service
net stop ITSOEmailFetch

:: Restart service
net stop ITSOEmailFetch && net start ITSOEmailFetch

:: Check status
sc query ITSOEmailFetch
```

### Via PowerShell:

```powershell
# Start
Start-Service ITSOEmailFetch

# Stop
Stop-Service ITSOEmailFetch

# Restart
Restart-Service ITSOEmailFetch

# Status
Get-Service ITSOEmailFetch

# Detail info
Get-Service ITSOEmailFetch | Format-List *
```

### Via Windows Services Manager (GUI):

1. **Tekan Windows + R**
2. **Ketik:** `services.msc` → Enter
3. **Cari:** "ITSO Email Auto-Fetch Service"
4. **Klik kanan** untuk Start/Stop/Restart/Properties

**Properties yang bisa diatur:**
- **Startup type:** Automatic (default), Manual, Disabled
- **Recovery:** Apa yang terjadi kalau service crash
- **Log On:** User account untuk menjalankan service

---

## 📊 Monitoring Service

### Check Status Real-time:

```powershell
# Status service
Get-Service ITSOEmailFetch | Select-Object Status, DisplayName

# Lihat log output
Get-Content C:\laragon\www\ITSO\storage\logs\email-daemon.log -Tail 50

# Monitor live (auto-refresh setiap 2 detik)
Get-Content C:\laragon\www\ITSO\storage\logs\email-daemon.log -Wait

# Check error log
Get-Content C:\laragon\www\ITSO\storage\logs\email-daemon-error.log -Tail 50
```

### Check Fetch History di Database:

```powershell
cd C:\laragon\www\ITSO

php artisan tinker --execute="
echo '=== Last 10 Fetch Attempts ===\n\n';
\$logs = \App\Models\EmailFetchLog::orderBy('fetch_started_at', 'desc')->take(10)->get();
foreach (\$logs as \$log) {
    echo \$log->fetch_started_at->format('Y-m-d H:i:s') . ' | ';
    echo 'Status: ' . str_pad(\$log->status, 10) . ' | ';
    echo 'Fetched: ' . str_pad(\$log->total_fetched ?? 0, 3) . ' | ';
    echo 'Success: ' . str_pad(\$log->successful ?? 0, 3) . ' | ';
    echo 'Failed: ' . (\$log->failed ?? 0) . '\n';
    if (\$log->error_message) {
        echo '    Error: ' . \$log->error_message . '\n';
    }
}
"
```

---

## 🔧 Konfigurasi Advanced

### Ubah Interval Fetch:

Edit file `install-email-service.bat`, cari baris:

```batch
C:\nssm\nssm.exe set ITSOEmailFetch AppParameters "artisan emails:fetch-daemon --interval=300"
```

Ganti `300` dengan interval yang diinginkan (dalam detik):
- `60` = 1 menit
- `180` = 3 menit
- `300` = 5 menit (default)
- `600` = 10 menit

Lalu **jalankan ulang** installer atau update manual:

```cmd
C:\nssm\nssm.exe set ITSOEmailFetch AppParameters "artisan emails:fetch-daemon --interval=180"
net stop ITSOEmailFetch
net start ITSOEmailFetch
```

### Ubah Auto-Restart Delay:

Saat ini service auto-restart 5 detik setelah crash. Untuk ubah:

```cmd
:: Restart setelah 10 detik
C:\nssm\nssm.exe set ITSOEmailFetch AppRestartDelay 10000

:: Restart setelah 30 detik
C:\nssm\nssm.exe set ITSOEmailFetch AppRestartDelay 30000
```

### Ubah Log File Location:

```cmd
C:\nssm\nssm.exe set ITSOEmailFetch AppStdout "D:\logs\email-daemon.log"
C:\nssm\nssm.exe set ITSOEmailFetch AppStderr "D:\logs\email-daemon-error.log"
```

---

## 🗑️ Uninstall Service

Jalankan file: `uninstall-email-service.bat` (as Administrator)

Atau manual via CMD:

```cmd
:: Stop service
net stop ITSOEmailFetch

:: Remove service
C:\nssm\nssm.exe remove ITSOEmailFetch confirm
```

---

## ❓ Troubleshooting

### Service Tidak Bisa Start

**Check 1: Path PHP benar?**
```cmd
C:\nssm\nssm.exe get ITSOEmailFetch Application
```

Jika path salah, update:
```cmd
C:\nssm\nssm.exe set ITSOEmailFetch Application "C:\laragon\bin\php\php-8.3.26\php.exe"
```

**Check 2: Working directory benar?**
```cmd
C:\nssm\nssm.exe get ITSOEmailFetch AppDirectory
```

**Check 3: Lihat error log**
```cmd
type C:\laragon\www\ITSO\storage\logs\email-daemon-error.log
```

---

### Service Running tapi Tidak Fetch Email

**Check 1: Lihat output log**
```cmd
type C:\laragon\www\ITSO\storage\logs\email-daemon.log
```

**Check 2: Test command manual**
```cmd
cd C:\laragon\www\ITSO
php artisan emails:fetch-daemon --interval=60
```

Jika manual berhasil tapi service tidak, mungkin masalah permissions.

---

### Error: "IMAP_HOST is not configured"

Service tidak load `.env` file. **Solution:**

```cmd
cd C:\laragon\www\ITSO
php artisan config:cache

:: Restart service
net stop ITSOEmailFetch
net start ITSOEmailFetch
```

---

### Service Crash Terus (Restart Loop)

**Check error log:**
```cmd
type C:\laragon\www\ITSO\storage\logs\email-daemon-error.log
```

**Common causes:**
1. MySQL service tidak running → Start MySQL di Laragon
2. Network disconnect → Check koneksi ke IMAP server
3. PHP crash → Check PHP error log
4. Memory limit → Increase `memory_limit` di `php.ini`

**Disable auto-restart sementara untuk debug:**
```cmd
C:\nssm\nssm.exe set ITSOEmailFetch AppExit Default Exit
net start ITSOEmailFetch
:: Service akan stop kalau crash, tidak restart otomatis
```

---

### Ubah User Account yang Menjalankan Service

Default service jalan sebagai SYSTEM account. Kalau perlu ganti:

```cmd
:: Run as Local System (default)
C:\nssm\nssm.exe set ITSOEmailFetch ObjectName LocalSystem

:: Run as current user
C:\nssm\nssm.exe set ITSOEmailFetch ObjectName ".\%USERNAME%" YourPassword

:: Run as domain user
C:\nssm\nssm.exe set ITSOEmailFetch ObjectName "DOMAIN\Username" Password
```

---

## 📋 Service Info Lengkap

Lihat semua konfigurasi service:

```cmd
:: Semua parameter
C:\nssm\nssm.exe dump ITSOEmailFetch

:: Specific parameter
C:\nssm\nssm.exe get ITSOEmailFetch Application
C:\nssm\nssm.exe get ITSOEmailFetch AppDirectory
C:\nssm\nssm.exe get ITSOEmailFetch AppParameters
C:\nssm\nssm.exe get ITSOEmailFetch AppStdout
C:\nssm\nssm.exe get ITSOEmailFetch AppStderr
```

---

## ✅ Verifikasi Service Berjalan Baik

Checklist:

```powershell
# 1. Service running?
Get-Service ITSOEmailFetch | Select-Object Status
# Expected: Status = Running

# 2. Ada log output?
if (Test-Path "C:\laragon\www\ITSO\storage\logs\email-daemon.log") {
    Write-Host "✓ Log file exists" -ForegroundColor Green
    Get-Content C:\laragon\www\ITSO\storage\logs\email-daemon.log -Tail 10
} else {
    Write-Host "✗ No log file" -ForegroundColor Red
}

# 3. Database log ada entry baru?
cd C:\laragon\www\ITSO
php artisan tinker --execute="echo 'Last fetch: ' . \App\Models\EmailFetchLog::latest()->first()->fetch_started_at;"

# 4. Service auto-start?
Get-Service ITSOEmailFetch | Select-Object StartType
# Expected: StartType = Automatic
```

---

## 🎉 Keuntungan Menggunakan Service

| Feature | Service | Task Scheduler | Manual Run |
|---------|---------|----------------|------------|
| Auto-start saat boot | ✅ Ya | ⚠️ Hanya saat login | ❌ Tidak |
| Jalan tanpa login | ✅ Ya | ❌ Tidak | ❌ Tidak |
| Auto-restart on crash | ✅ Ya | ❌ Tidak | ❌ Tidak |
| Background (no window) | ✅ Ya | ⚠️ Bisa | ❌ Harus ada window |
| Easy management | ✅ GUI + CMD | ⚠️ GUI saja | ❌ Manual |
| Production ready | ✅ Ya | ⚠️ Kadang skip | ❌ Tidak |

---

## 📞 Support

Jika ada masalah:

1. Check error log: `C:\laragon\www\ITSO\storage\logs\email-daemon-error.log`
2. Check laravel log: `C:\laragon\www\ITSO\storage\logs\laravel.log`
3. Check service status: `sc query ITSOEmailFetch`
4. Test manual: `php artisan emails:fetch-daemon --interval=60`

---

Selamat! Email auto-fetch sekarang berjalan sebagai Windows Service yang reliable dan production-ready! 🚀
