# Troubleshooting Auto-Fetch di PC Windows

## Langkah Diagnosa:

### 1. Cek Task Scheduler Status
```powershell
# Cek apakah task ada dan enabled
schtasks /query /tn "Laravel Email Fetch" /fo LIST /v

# Cek last run time dan result
schtasks /query /tn "Laravel Email Fetch" /fo LIST /v | findstr "Last Run Time" 
schtasks /query /tn "Laravel Email Fetch" /fo LIST /v | findstr "Last Result"
```

**Last Result Code:**
- `0x0` = Success ✅
- `0x1` = Failed ❌
- `0x41301` = Task is currently running
- Other codes = Error (lihat detail)

---

### 2. Cek PHP Path di Task
```powershell
# Lihat command yang di-run oleh task
schtasks /query /tn "Laravel Email Fetch" /fo LIST /v | findstr "Task To Run"
```

**Pastikan:**
- PHP path benar (sesuai Laragon): `C:\laragon\bin\php\php-8.3.26\php.exe`
- Artisan path benar: `C:\laragon\www\ITSO\artisan`

---

### 3. Test Manual Execution
```powershell
# Pindah ke direktori project
cd C:\laragon\www\ITSO

# Test command yang sama dengan Task Scheduler
C:\laragon\bin\php\php-8.3.26\php.exe artisan emails:fetch

# Cek apakah ada error
echo $LASTEXITCODE
```

---

### 4. Cek Log File
```powershell
# Lihat log terbaru
cd C:\laragon\www\ITSO
Get-Content storage\logs\laravel.log -Tail 100 | Select-String "Email Fetch"

# Atau cek keseluruhan log hari ini
$today = Get-Date -Format "yyyy-MM-dd"
Get-Content "storage\logs\laravel-$today.log" -Tail 200
```

---

### 5. Cek Database Fetch Logs
```powershell
php artisan tinker --execute="
\$logs = \App\Models\EmailFetchLog::orderBy('fetch_started_at', 'desc')->take(10)->get();
foreach (\$logs as \$log) {
    echo \$log->fetch_started_at . ' | Status: ' . \$log->status . ' | Fetched: ' . (\$log->total_fetched ?? 0) . ' | Success: ' . (\$log->successful ?? 0) . ' | Failed: ' . (\$log->failed ?? 0) . '\n';
    if (\$log->error_message) {
        echo '  Error: ' . \$log->error_message . '\n';
    }
}
"
```

---

### 6. Cek IMAP Connection
```powershell
php artisan tinker --execute="
try {
    echo 'Testing IMAP connection...\n';
    echo 'Host: ' . config('mail.imap.host') . '\n';
    echo 'Port: ' . config('mail.imap.port') . '\n';
    echo 'Username: ' . config('mail.imap.username') . '\n';
    
    \$service = app(\App\Services\EmailFetcherService::class);
    echo 'EmailFetcherService loaded\n';
    
    echo '\nNote: Full connection test requires running emails:fetch command\n';
} catch (\Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . '\n';
}
"
```

---

### 7. Cek Working Directory di Task
**Problem umum:** Task Scheduler tidak set working directory dengan benar

**Fix:**
1. Buka Task Scheduler → Find "Laravel Email Fetch"
2. Klik kanan → Properties
3. Tab "Actions" → Edit action
4. Set **"Start in (optional)"** ke: `C:\laragon\www\ITSO`

---

### 8. Cek Permissions
```powershell
# Cek apakah user yang menjalankan task punya akses
whoami

# Test write permission ke log directory
echo "test" > storage\logs\test.txt
del storage\logs\test.txt
```

---

### 9. Enable Task Scheduler History
```powershell
# Enable history untuk debugging
wevtutil sl Microsoft-Windows-TaskScheduler/Operational /e:true

# Lihat history
Get-WinEvent -LogName Microsoft-Windows-TaskScheduler/Operational -MaxEvents 20 | Where-Object {$_.Message -like "*Laravel Email Fetch*"}
```

---

### 10. Manual Trigger untuk Testing
```powershell
# Trigger task secara manual dan lihat hasilnya
schtasks /run /tn "Laravel Email Fetch"

# Wait 10 detik lalu cek status
Start-Sleep -Seconds 10
schtasks /query /tn "Laravel Email Fetch" /fo LIST /v | findstr "Status"
```

---

## Common Issues:

### Issue 1: Path tidak ketemu
**Symptom:** Last Result = `0x1` atau error "file not found"
**Solution:**
- Pastikan PHP path absolute: `C:\laragon\bin\php\php-8.3.26\php.exe`
- Pastikan working directory set: `C:\laragon\www\ITSO`

### Issue 2: Permission denied
**Symptom:** Error saat write ke log atau database
**Solution:**
- Run task sebagai user yang punya akses ke folder
- Atau run dengan "Run with highest privileges"

### Issue 3: Environment variables tidak loaded
**Symptom:** Config kosong, "IMAP_HOST is not configured"
**Solution:**
- Pastikan file .env ada di `C:\laragon\www\ITSO\.env`
- Jalankan `php artisan config:clear` di PC Windows

### Issue 4: Database connection failed
**Symptom:** "SQLSTATE[HY000] [2002] Connection refused"
**Solution:**
- Pastikan MySQL service running di Laragon
- Cek DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD di .env

### Issue 5: IMAP connection timeout
**Symptom:** "Cannot connect to IMAP server"
**Solution:**
- Cek apakah PC Windows bisa ping ke 10.103.7.18
- Pastikan firewall tidak block port 993
- Test dengan: `telnet 10.103.7.18 993`

---

## Quick Fix Script

Jalankan script ini untuk quick diagnosis:

```powershell
Write-Host "=== Auto-Fetch Diagnosis ===" -ForegroundColor Cyan

# 1. Check Task
Write-Host "`n1. Checking Task Scheduler..." -ForegroundColor Yellow
schtasks /query /tn "Laravel Email Fetch" /fo LIST /v | Select-String "Task Name|Status|Last Run Time|Last Result|Next Run Time"

# 2. Check PHP
Write-Host "`n2. Checking PHP..." -ForegroundColor Yellow
C:\laragon\bin\php\php-8.3.26\php.exe -v

# 3. Check Project Path
Write-Host "`n3. Checking Project Files..." -ForegroundColor Yellow
if (Test-Path "C:\laragon\www\ITSO\artisan") {
    Write-Host "✓ artisan file found" -ForegroundColor Green
} else {
    Write-Host "✗ artisan file NOT found" -ForegroundColor Red
}

if (Test-Path "C:\laragon\www\ITSO\.env") {
    Write-Host "✓ .env file found" -ForegroundColor Green
} else {
    Write-Host "✗ .env file NOT found" -ForegroundColor Red
}

# 4. Check Database
Write-Host "`n4. Checking Recent Fetch Logs..." -ForegroundColor Yellow
cd C:\laragon\www\ITSO
php artisan tinker --execute="echo 'Last 3 fetch attempts:\n'; \$logs = \App\Models\EmailFetchLog::orderBy('fetch_started_at', 'desc')->take(3)->get(['fetch_started_at', 'status', 'total_fetched']); foreach (\$logs as \$log) { echo \$log->fetch_started_at . ' | ' . \$log->status . '\n'; }"

# 5. Manual Test
Write-Host "`n5. Running Manual Test..." -ForegroundColor Yellow
php artisan emails:fetch --limit=5

Write-Host "`n=== Diagnosis Complete ===" -ForegroundColor Cyan
```

Simpan sebagai `diagnose-autofetch.ps1` dan jalankan di PowerShell.

