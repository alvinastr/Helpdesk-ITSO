# Setup Task Scheduler untuk Laragon

## Panduan Lengkap Auto-Fetch Email dengan Laragon

### 📋 Prasyarat

1. ✅ Laragon sudah terinstall
2. ✅ PHP dari Laragon (versi 7.4 atau lebih tinggi)
3. ✅ Project ITSO sudah di folder Laragon (contoh: `C:\laragon\www\ITSO`)

---

## 🔧 Langkah 1: Konfigurasi Batch File

### 1.1 Cek Lokasi PHP Laragon Anda

Buka Laragon, klik kanan pada ikon Laragon di system tray, pilih **PHP** → **Version** untuk melihat versi PHP yang aktif.

Lokasi default PHP Laragon biasanya:
```
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe
C:\laragon\bin\php\php-8.0.25-Win32-vs16-x64\php.exe
```

**Cara mudah cek path PHP:**
1. Buka **Command Prompt** atau **Terminal** di Laragon (klik **Menu** → **Terminal**)
2. Ketik: `where php`
3. Copy path yang muncul

### 1.2 Edit File `scheduler-laragon.bat`

Buka file `scheduler-laragon.bat` dengan text editor (Notepad++, VS Code, atau Notepad biasa).

**Sesuaikan 2 baris ini:**

```bat
SET PHP_PATH=C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe
SET PROJECT_PATH=C:\laragon\www\ITSO
```

**Contoh:**
- Jika PHP Anda versi 8.1.10: `SET PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe`
- Jika project di `D:\Projects\ITSO`: `SET PROJECT_PATH=D:\Projects\ITSO`

### 1.3 Test Batch File

1. Double-click file `scheduler-laragon.bat`
2. Jika berhasil, tidak ada error muncul
3. Cek file `storage\logs\scheduler.log` - harus ada log baru

**Jika ada error:**
- `ERROR: PHP tidak ditemukan` → PHP_PATH salah, cek lagi lokasi PHP
- `ERROR: Project Laravel tidak ditemukan` → PROJECT_PATH salah, cek folder project
- `artisan: command not found` → Pastikan di folder project ada file `artisan`

---

## ⏰ Langkah 2: Setup Windows Task Scheduler

### 2.1 Buka Task Scheduler

**Cara 1 - Search:**
1. Tekan `Windows Key`
2. Ketik: `Task Scheduler`
3. Klik **Task Scheduler** app

**Cara 2 - Run:**
1. Tekan `Windows Key + R`
2. Ketik: `taskschd.msc`
3. Enter

### 2.2 Create Basic Task

1. Di Task Scheduler, klik **Action** → **Create Basic Task**

2. **Name and Description:**
   - Name: `Laravel ITSO Email Auto-Fetch`
   - Description: `Menjalankan Laravel scheduler setiap menit untuk auto-fetch email dari Zimbra`
   - Klik **Next**

3. **Trigger - When do you want the task to start?**
   - Pilih: **Daily**
   - Klik **Next**

4. **Daily Settings:**
   - Start: Pilih tanggal hari ini, jam sekarang
   - Recur every: `1 days`
   - Klik **Next**

5. **Action - What action do you want the task to perform?**
   - Pilih: **Start a program**
   - Klik **Next**

6. **Start a Program:**
   - **Program/script:** Browse dan pilih file `scheduler-laragon.bat` Anda
     
     Contoh: `C:\laragon\www\ITSO\scheduler-laragon.bat`
   
   - **Start in (optional):** Kosongkan atau isi dengan folder project
     
     Contoh: `C:\laragon\www\ITSO`
   
   - Klik **Next**

7. **Summary:**
   - ☑️ Centang: **Open the Properties dialog for this task when I click Finish**
   - Klik **Finish**

### 2.3 Advanced Settings (PENTING!)

Setelah klik Finish, akan muncul **Properties dialog**. Lakukan setting berikut:

#### Tab: General
- ☑️ **Run whether user is logged on or not**
- ☑️ **Run with highest privileges**
- Configure for: **Windows 10** (atau sesuai OS Anda)

#### Tab: Triggers
1. Double-click trigger yang sudah dibuat
2. Klik **Edit...**
3. ☑️ Centang: **Repeat task every:** → Pilih **1 minute**
4. **For a duration of:** → Pilih **Indefinitely**
5. ☑️ Centang: **Enabled**
6. Klik **OK**

#### Tab: Settings
- ☑️ **Allow task to be run on demand**
- ☑️ **Run task as soon as possible after a scheduled start is missed**
- ☑️ **If the task fails, restart every:** `1 minute`, **Attempt to restart up to:** `3 times`
- **If the running task does not end when requested, force it to stop**
- **If the task is already running, then the following rule applies:** → **Do not start a new instance**

Klik **OK** untuk save.

**Jika diminta password:**
- Masukkan password Windows Anda
- Ini diperlukan untuk "Run whether user is logged on or not"

---

## ✅ Langkah 3: Testing

### 3.1 Test Manual Task

1. Di Task Scheduler, cari task **Laravel ITSO Email Auto-Fetch**
2. Klik kanan → **Run**
3. Tunggu beberapa detik
4. Cek hasilnya:

**Cara 1 - Via Dashboard:**
```
http://localhost/ITSO/public/admin/dashboard
```
Lihat widget **Email Auto-Fetch Statistics**

**Cara 2 - Via Log File:**
Buka file: `C:\laragon\www\ITSO\storage\logs\scheduler.log`

Isi log yang benar:
```
Running scheduled command: Illuminate\Queue\Console\WorkCommand...
```

### 3.2 Test Auto-Run (tunggu 1 menit)

1. Tunggu 1 menit setelah setup
2. Task akan otomatis run setiap menit
3. Cek **Last Run Time** di Task Scheduler - harus update setiap menit
4. Cek **Last Run Result** - harus: `The operation completed successfully. (0x0)`

### 3.3 Monitoring

**Cek Email Fetch Logs:**
```bash
# Di terminal Laragon
php artisan tinker --execute="
$logs = App\Models\EmailFetchLog::latest()->take(5)->get();
foreach ($logs as $log) {
    echo $log->fetch_started_at . ' - ' . $log->status . ' - ' . $log->successful . ' tickets' . PHP_EOL;
}
"
```

**Cek Database:**
```bash
php artisan tinker --execute="
echo 'Total Tickets: ' . App\Models\Ticket::count() . PHP_EOL;
echo 'Total Fetches: ' . App\Models\EmailFetchLog::count() . PHP_EOL;
"
```

---

## 🔍 Troubleshooting

### Task tidak jalan

**1. Cek Task Status**
- Buka Task Scheduler
- Klik **Task Scheduler Library**
- Cari task Anda, lihat kolom **Status** (harus: Ready)
- Lihat **Last Run Result** (harus: 0x0)

**2. Cek History**
- Klik kanan pada task → **Properties**
- Tab **History** (jika tidak ada, enable dulu via **Action** → **Enable All Tasks History**)
- Lihat error messages

**3. Common Errors:**

| Error Code | Meaning | Solution |
|------------|---------|----------|
| 0x1 | Incorrect function | Cek path di batch file |
| 0x2 | File not found | PHP_PATH atau PROJECT_PATH salah |
| 0x41301 | Task is currently running | Normal, tunggu task sebelumnya selesai |
| 0x41303 | Task has not yet run | Task belum sempat dijalankan |

### Email tidak ter-fetch

**1. Cek IMAP Connection**
```bash
php artisan imap:diagnose
```

**2. Cek Email Fetch Manual**
```bash
php artisan emails:fetch
```

**3. Cek Log Errors**
Buka: `storage\logs\laravel.log`
Cari error messages terbaru

**4. Cek .env Configuration**
Pastikan semua setting IMAP sudah benar:
```env
IMAP_HOST=10.103.7.18
IMAP_PORT=993
IMAP_USERNAME=itsupport.monitor
IMAP_PASSWORD=M0n1tor.Support
IMAP_VALID_RECIPIENTS=itso@bankmega.com,it.support@bankmega.com
```

### Scheduler.log tidak ter-update

**1. Cek Permission Folder**
- Folder `storage\logs` harus writable
- Klik kanan → Properties → Security → Edit → Allow "Write"

**2. Buat Manual Log File**
```bash
cd C:\laragon\www\ITSO
echo. > storage\logs\scheduler.log
```

**3. Cek Batch File Execution**
- Double-click `scheduler-laragon.bat`
- Harus tidak ada error popup

---

## 📊 Expected Behavior

Setelah setup berhasil:

✅ **Task Scheduler:**
- Task run setiap 1 menit
- Last Run Result: `(0x0)`
- Next Run Time: Update setiap menit

✅ **Email Fetch:**
- Cek email baru setiap 5 menit (sesuai schedule di `app/Console/Kernel.php`)
- Create ticket otomatis jika ada email baru yang valid
- Skip duplicate emails (same message-id)
- Thread reply emails ke ticket yang sama

✅ **Dashboard:**
- Widget "Email Auto-Fetch Statistics" update otomatis
- Last Fetch time ter-update
- Success rate naik jika fetch berhasil
- Today's stats bertambah setiap fetch

✅ **Logs:**
- `storage\logs\scheduler.log` bertambah setiap menit
- `storage\logs\laravel.log` mencatat email fetch activities
- Database table `email_fetch_logs` terisi dengan fetch history

---

## 🎯 Optimization Tips

### 1. Adjust Fetch Frequency

Jika ingin ubah dari 5 menit ke interval lain:

**Edit file:** `app/Console/Kernel.php`

```php
// Setiap 1 menit
$schedule->command('emails:fetch')->everyMinute();

// Setiap 10 menit
$schedule->command('emails:fetch')->everyTenMinutes();

// Setiap 30 menit
$schedule->command('emails:fetch')->everyThirtyMinutes();

// Setiap jam
$schedule->command('emails:fetch')->hourly();
```

Jangan lupa:
```bash
php artisan config:clear
php artisan cache:clear
```

### 2. Log Rotation

Agar log tidak terlalu besar, tambahkan log rotation:

**Edit file:** `scheduler-laragon.bat`

Ganti baris:
```bat
"%PHP_PATH%" artisan schedule:run >> storage\logs\scheduler.log 2>&1
```

Dengan:
```bat
REM Delete log jika lebih dari 10MB
FOR %%A IN (storage\logs\scheduler.log) DO IF %%~zA GTR 10485760 DEL storage\logs\scheduler.log

"%PHP_PATH%" artisan schedule:run >> storage\logs\scheduler.log 2>&1
```

### 3. Email Notification on Failure

Tambahkan notifikasi jika fetch gagal:

**Edit:** `app/Console/Kernel.php`

```php
$schedule->command('emails:fetch')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        // Send email notification
        // \Mail::to('admin@bankmega.com')->send(...);
    });
```

---

## 📝 Notes

- Task Scheduler akan run **setiap 1 menit**, tapi Laravel scheduler akan cek schedule dan execute command sesuai yang didefinisikan (setiap 5 menit untuk `emails:fetch`)
- Pastikan PC tidak sleep/hibernate jika ingin auto-fetch 24/7
- Untuk production server, gunakan Windows Server dengan Task Scheduler yang lebih reliable
- Log file akan bertambah besar seiring waktu, lakukan log rotation secara berkala

---

## ✨ Success Checklist

- [ ] `scheduler-laragon.bat` sudah disesuaikan dengan PHP_PATH dan PROJECT_PATH
- [ ] Test double-click `scheduler-laragon.bat` berhasil tanpa error
- [ ] Task Scheduler task sudah dibuat dengan nama "Laravel ITSO Email Auto-Fetch"
- [ ] Trigger diset **repeat every 1 minute** indefinitely
- [ ] Settings: "Run whether user is logged on or not" + "Run with highest privileges"
- [ ] Test run manual task berhasil (Last Run Result: 0x0)
- [ ] `storage\logs\scheduler.log` ter-create dan ter-update
- [ ] Dashboard widget "Email Auto-Fetch Statistics" muncul dan update
- [ ] Email baru otomatis jadi ticket dalam 5 menit

---

**Jika semua checklist ✅, sistem sudah production-ready!** 🎉
