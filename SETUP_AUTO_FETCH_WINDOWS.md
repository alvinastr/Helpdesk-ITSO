# Setup Auto-Fetch Email di Windows PC

Email auto-fetch akan berjalan otomatis setiap 5 menit menggunakan Laravel Task Scheduler.

## ✅ Setup di Windows Task Scheduler

### 1. Buka Task Scheduler
- Tekan `Win + R`
- Ketik `taskschd.msc` dan tekan Enter

### 2. Buat Task Baru
- Klik **"Create Basic Task..."** di panel kanan
- Name: `ITSO Email Auto-Fetch`
- Description: `Fetch email dari Zimbra setiap 5 menit dan buat ticket otomatis`

### 3. Trigger
- When do you want the task to start? → **Daily**
- Start: **Pilih tanggal hari ini**
- Recur every: **1 days**
- Klik **Next**

### 4. Action
- What action do you want the task to perform? → **Start a program**
- Program/script: `C:\xampp\php\php.exe` *(sesuaikan path PHP)*
- Add arguments: `artisan schedule:run`
- Start in: `C:\path\to\ITSO` *(path folder project)*
- Klik **Next** → **Finish**

### 5. Edit Task (Important!)
- Klik kanan task yang baru dibuat → **Properties**
- Tab **Triggers** → Edit trigger:
  - ✅ Check **"Repeat task every"**: **1 minute**
  - For a duration of: **Indefinitely**
  - ✅ Check **"Enabled"**
- Tab **General**:
  - ✅ Check **"Run whether user is logged on or not"**
  - ✅ Check **"Run with highest privileges"**
  - Configure for: **Windows 10** atau **Windows Server 2019**
- Tab **Settings**:
  - ✅ Check **"Run task as soon as possible after a scheduled start is missed"**
  - ✅ Check **"If the task fails, restart every"**: **1 minute**, up to **3 times**
  - If the running task does not end when requested: **"Stop the existing instance"**
- Klik **OK**

### 6. Test Task
- Klik kanan task → **Run**
- Cek log: `storage/logs/scheduler.log` atau `storage/logs/laravel.log`
- Lihat apakah ada tiket baru di dashboard

---

## 📝 Alternatif: Run Scheduler Langsung (Development Only)

Untuk testing di laptop/development, bisa jalankan:

```bash
php artisan schedule:work
```

Ini akan menjalankan scheduler terus-menerus dan auto-fetch setiap 5 menit.

---

## 🔧 Konfigurasi Fetch Interval

Edit file `app/Console/Kernel.php` untuk ubah interval:

```php
// Setiap 5 menit (default)
$schedule->command('emails:fetch')->everyFiveMinutes();

// Setiap 10 menit
$schedule->command('emails:fetch')->everyTenMinutes();

// Setiap 15 menit
$schedule->command('emails:fetch')->everyFifteenMinutes();

// Setiap 1 menit (tidak direkomendasikan)
$schedule->command('emails:fetch')->everyMinute();
```

---

## ✅ Verifikasi Auto-Fetch Berjalan

### 1. Cek Log Scheduler
```bash
tail -f storage/logs/scheduler.log
```

### 2. Cek Log Laravel
```bash
tail -f storage/logs/laravel.log
```

### 3. Monitor Dashboard
- Buka dashboard ITSO
- Lihat apakah tiket baru muncul setiap 5 menit

---

## 🚨 Troubleshooting

### Task tidak jalan di Task Scheduler
1. Cek path PHP sudah benar
2. Cek path project sudah benar
3. Pastikan user yang run task punya akses ke folder project
4. Cek log error di `storage/logs/`

### Scheduler jalan tapi tidak fetch email
1. Cek koneksi IMAP: `php artisan imap:diagnose`
2. Cek konfigurasi `.env`
3. Test manual: `php artisan emails:fetch`
4. Cek log: `storage/logs/laravel.log`

### Email di-fetch tapi tidak jadi ticket
1. Cek filter di `.env` (blacklist/whitelist)
2. Test dengan debug: `php artisan emails:debug`
3. Lihat log untuk email yang difilter

---

## 📊 Monitoring

### Manual Fetch (untuk testing)
```bash
php artisan emails:fetch
```

### Debug Mode (lihat email yang difilter)
```bash
php artisan emails:debug
```

### Test IMAP Connection
```bash
php artisan imap:diagnose
```

### Lihat statistik tiket
```sql
SELECT COUNT(*) FROM tickets WHERE DATE(created_at) = CURDATE();
```

---

## ⚙️ Konfigurasi Email Filter

Edit file `.env` untuk filter email:

```env
# Blacklist sender (tidak akan diproses)
IMAP_BLACKLIST_SENDERS=application.monitor@bankmega.com

# Blacklist subject keywords
IMAP_BLACKLIST_SUBJECTS=confidential,outofoffice,automaticreply

# Whitelist recipient (hanya email ke alamat ini yang diproses)
IMAP_VALID_RECIPIENTS=itso@bankmega.com,it.support@bankmega.com

# Fetch limit per run
IMAP_FETCH_LIMIT=150
```

Setelah edit `.env`, jalankan:
```bash
php artisan config:clear
```

---

✅ **Setup selesai!** Email akan di-fetch otomatis setiap 5 menit tanpa perlu jalankan command manual.
