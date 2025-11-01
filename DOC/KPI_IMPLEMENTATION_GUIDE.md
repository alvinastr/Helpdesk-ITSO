# Panduan Implementasi KPI pada Pembuatan Tiket

## 📋 Daftar Isi
1. [Cara Kerja Sistem KPI](#cara-kerja-sistem-kpi)
2. [Skenario Pembuatan Tiket](#skenario-pembuatan-tiket)
3. [Field KPI yang Dicatat](#field-kpi-yang-dicatat)
4. [Perhitungan Otomatis](#perhitungan-otomatis)
5. [Contoh Kasus](#contoh-kasus)

---

## 🎯 Cara Kerja Sistem KPI

Sistem KPI di helpdesk ITSO bekerja secara **OTOMATIS** untuk melacak performa penanganan tiket. Berikut alur kerjanya:

### 1. **Saat Membuat Tiket (Admin)**
- Admin membuat tiket melalui form `/admin/tickets/create`
- Jika channel = **Email**, muncul field tambahan: **"Waktu Email Diterima"**
- Admin mengisi kapan email keluhan ASLI diterima
- Sistem mencatat `email_received_at` dan menghitung **Ticket Creation Delay**

### 2. **Saat Admin Membalas Tiket**
- Admin memberikan response pertama melalui form reply
- Sistem **OTOMATIS** mencatat `first_response_at` (timestamp pertama kali admin reply)
- Sistem menghitung **Response Time** = `first_response_at - email_received_at` atau `first_response_at - created_at`

### 3. **Saat Tiket Diselesaikan**
- Admin mengubah status tiket menjadi **"Resolved"**
- Sistem **OTOMATIS** mencatat `resolved_at`
- Sistem menghitung **Resolution Time** = `resolved_at - email_received_at` atau `resolved_at - created_at`

---

## 📝 Skenario Pembuatan Tiket

### Skenario A: Email Masuk ke Admin
**Situasi:** User mengirim email keluhan ke helpdesk

**Langkah Admin:**
1. Buka form: `/admin/tickets/create`
2. Isi data pelapor (NIP, Nama, Email, Telepon, Departemen)
3. Pilih **Channel = Email**
4. Field **"Waktu Email Diterima"** akan muncul secara otomatis
5. Isi tanggal & waktu sesuai kapan email diterima (contoh: `2025-10-21 13:00`)
6. Isi Subject dan Deskripsi keluhan
7. Klik **Buat Tiket**

**Hasil:**
```php
Ticket #TKT-20251025-0001
- email_received_at: 2025-10-21 13:00:00
- created_at: 2025-10-25 10:00:00
- ticket_creation_delay_minutes: 5,640 menit (94 jam)
```

### Skenario B: WhatsApp/Telepon ke Admin
**Situasi:** User menghubungi via WhatsApp atau telepon

**Langkah Admin:**
1. Buka form: `/admin/tickets/create`
2. Isi data pelapor
3. Pilih **Channel = WhatsApp** atau **Call**
4. Field **"Waktu Email Diterima"** TIDAK muncul (karena bukan email)
5. Isi Subject dan Deskripsi
6. Klik **Buat Tiket**

**Hasil:**
```php
Ticket #TKT-20251025-0002
- email_received_at: NULL
- created_at: 2025-10-25 10:00:00
- ticket_creation_delay_minutes: NULL (tidak ada delay)
```

### Skenario C: User Langsung dari Portal
**Situasi:** User membuat tiket sendiri dari portal

**Hasil:**
```php
Ticket #TKT-20251025-0003
- email_received_at: NULL
- created_at: 2025-10-25 10:00:00
- ticket_creation_delay_minutes: NULL
```

---

## 📊 Field KPI yang Dicatat

### 1. **email_received_at** (TIMESTAMP)
- **Kapan dicatat:** Saat admin membuat tiket dengan channel Email
- **Cara:** Admin input manual saat pembuatan tiket
- **Fungsi:** Titik awal untuk menghitung response time & resolution time
- **Nullable:** Ya (hanya untuk email)

### 2. **first_response_at** (TIMESTAMP)
- **Kapan dicatat:** Saat admin pertama kali membalas tiket
- **Cara:** Otomatis oleh sistem
- **Fungsi:** Mengukur seberapa cepat admin merespons
- **SLA Target:** ≤ 30 menit

### 3. **resolved_at** (TIMESTAMP)
- **Kapan dicatat:** Saat status tiket diubah ke "Resolved"
- **Cara:** Otomatis oleh sistem
- **Fungsi:** Mengukur total waktu penyelesaian
- **SLA Target:** ≤ 48 jam (2880 menit)

### 4. **response_time_minutes** (INTEGER)
- **Kapan dicatat:** Otomatis saat `first_response_at` tercatat
- **Rumus:** 
  ```
  IF email_received_at IS NOT NULL:
      response_time = first_response_at - email_received_at
  ELSE:
      response_time = first_response_at - created_at
  ```
- **Satuan:** Menit

### 5. **resolution_time_minutes** (INTEGER)
- **Kapan dicatat:** Otomatis saat status = "Resolved"
- **Rumus:**
  ```
  IF email_received_at IS NOT NULL:
      resolution_time = resolved_at - email_received_at
  ELSE:
      resolution_time = resolved_at - created_at
  ```
- **Satuan:** Menit

### 6. **ticket_creation_delay_minutes** (INTEGER)
- **Kapan dicatat:** Otomatis saat tiket dibuat dengan `email_received_at`
- **Rumus:** `created_at - email_received_at`
- **Fungsi:** Mengukur delay admin dalam membuat tiket setelah menerima email
- **Satuan:** Menit

---

## ⚙️ Perhitungan Otomatis

### Lokasi Kode Perhitungan
File: `app/Services/TicketService.php`

```php
// Saat admin membuat tiket dengan email_received_at
public function createTicketByAdmin(array $data): Ticket
{
    $ticket = Ticket::create([
        // ... data lain
        'email_received_at' => $data['email_received_at'] ?? null,
    ]);
    
    // Hitung KPI jika email_received_at ada
    if ($ticket->email_received_at) {
        $this->kpiService->updateTicketKpiMetrics($ticket);
    }
    
    return $ticket;
}

// Saat admin membalas tiket (first response)
public function addReply(Ticket $ticket, array $data): TicketThread
{
    // Catat first_response_at jika belum ada
    if (!$ticket->first_response_at && $data['sender_type'] === 'admin') {
        $ticket->first_response_at = now();
        $ticket->calculateResponseTime(); // Hitung response_time_minutes
        $ticket->save();
    }
    
    return $thread;
}

// Saat status berubah ke Resolved
public function updateStatus(Ticket $ticket, string $newStatus): void
{
    if ($newStatus === 'resolved' && !$ticket->resolved_at) {
        $ticket->resolved_at = now();
        $ticket->calculateResolutionTime(); // Hitung resolution_time_minutes
        $ticket->save();
    }
}
```

### Metode di Model Ticket
File: `app/Models/Ticket.php`

```php
// Hitung response time
public function calculateResponseTime(): void
{
    if (!$this->first_response_at) return;
    
    $startTime = $this->email_received_at ?? $this->created_at;
    $this->response_time_minutes = $startTime->diffInMinutes($this->first_response_at);
}

// Hitung resolution time
public function calculateResolutionTime(): void
{
    if (!$this->resolved_at) return;
    
    $startTime = $this->email_received_at ?? $this->created_at;
    $this->resolution_time_minutes = $startTime->diffInMinutes($this->resolved_at);
}

// Hitung ticket creation delay
public function calculateTicketCreationDelay(): void
{
    if (!$this->email_received_at) return;
    
    $this->ticket_creation_delay_minutes = $this->email_received_at->diffInMinutes($this->created_at);
}

// Cek apakah response time dalam target SLA
public function isResponseTimeWithinTarget(): bool
{
    return $this->response_time_minutes && $this->response_time_minutes <= 30;
}

// Cek apakah resolution time dalam target SLA
public function isResolutionTimeWithinTarget(): bool
{
    return $this->resolution_time_minutes && $this->resolution_time_minutes <= 2880; // 48 jam
}
```

---

## 💡 Contoh Kasus

### Kasus 1: Email Diterima 21 Oktober, Ticket Dibuat 25 Oktober

**Timeline:**
- 📧 **21 Okt 13:00** - Email diterima di inbox helpdesk
- 📝 **25 Okt 10:00** - Admin membuat tiket di sistem
- 💬 **25 Okt 10:15** - Admin memberikan response pertama
- ✅ **27 Okt 10:00** - Tiket diselesaikan (resolved)

**Form Input:**
```
Channel: Email
Waktu Email Diterima: 2025-10-21 13:00
Subject: Laptop tidak bisa connect WiFi
Deskripsi: ...
```

**Hasil KPI:**
```php
email_received_at: 2025-10-21 13:00:00
created_at: 2025-10-25 10:00:00
first_response_at: 2025-10-25 10:15:00
resolved_at: 2025-10-27 10:00:00

// PERHITUNGAN:
ticket_creation_delay_minutes: 5,640 menit (94 jam) ❌ Terlambat input
response_time_minutes: 8,655 menit (144.25 jam) ❌ Melebihi SLA (target: 30 menit)
resolution_time_minutes: 11,520 menit (192 jam) ❌ Melebihi SLA (target: 2880 menit/48 jam)
```

**Analisis:**
- ❌ Admin terlambat 94 jam membuat tiket setelah email diterima
- ❌ Response time melebihi SLA (seharusnya 30 menit, aktual 144 jam dari email diterima)
- ❌ Resolution time melebihi SLA (seharusnya 48 jam, aktual 192 jam dari email diterima)

### Kasus 2: WhatsApp Langsung Diproses

**Timeline:**
- 💬 **25 Okt 10:00** - User WA admin, langsung dibuatkan tiket
- 💬 **25 Okt 10:05** - Admin reply
- ✅ **25 Okt 14:00** - Selesai

**Form Input:**
```
Channel: WhatsApp
(Tidak ada field Waktu Email Diterima)
Subject: Printer error
Deskripsi: ...
```

**Hasil KPI:**
```php
email_received_at: NULL
created_at: 2025-10-25 10:00:00
first_response_at: 2025-10-25 10:05:00
resolved_at: 2025-10-25 14:00:00

// PERHITUNGAN:
ticket_creation_delay_minutes: NULL (tidak ada delay)
response_time_minutes: 5 menit ✅ Dalam SLA
resolution_time_minutes: 240 menit (4 jam) ✅ Dalam SLA
```

**Analisis:**
- ✅ Response time sangat baik (5 menit)
- ✅ Resolution time sangat baik (4 jam)
- ✅ Tidak ada ticket creation delay karena langsung diproses

### Kasus 3: Email Langsung Diproses

**Timeline:**
- 📧 **25 Okt 09:00** - Email diterima
- 📝 **25 Okt 09:10** - Admin buat tiket (delay 10 menit)
- 💬 **25 Okt 09:25** - Admin reply
- ✅ **26 Okt 10:00** - Selesai

**Form Input:**
```
Channel: Email
Waktu Email Diterima: 2025-10-25 09:00
Subject: Akun login bermasalah
Deskripsi: ...
```

**Hasil KPI:**
```php
email_received_at: 2025-10-25 09:00:00
created_at: 2025-10-25 09:10:00
first_response_at: 2025-10-25 09:25:00
resolved_at: 2025-10-26 10:00:00

// PERHITUNGAN:
ticket_creation_delay_minutes: 10 menit ✅ Baik
response_time_minutes: 25 menit ✅ Dalam SLA (target: 30 menit)
resolution_time_minutes: 1,500 menit (25 jam) ✅ Dalam SLA (target: 2880 menit/48 jam)
```

**Analisis:**
- ✅ Admin cepat membuat tiket (delay 10 menit)
- ✅ Response time dalam SLA (25 menit)
- ✅ Resolution time dalam SLA (25 jam)
- 🏆 **EXCELLENT PERFORMANCE!**

---

## 📈 Melihat Hasil KPI

### 1. **Dashboard KPI**
URL: `/admin/kpi`

Dashboard menampilkan:
- Total tiket
- Response rate
- Rata-rata response time dengan badge SLA
- Rata-rata resolution time dengan badge SLA
- Chart trend KPI
- Tabel tiket dengan masalah SLA

### 2. **Detail Tiket**
URL: `/tickets/{id}`

Setiap halaman detail tiket menampilkan **KPI Card** dengan:
- ⏱️ Response Time (dengan badge hijau/merah)
- ⏰ Resolution Time (dengan badge hijau/merah)
- 📅 Ticket Creation Delay (jika ada)
- Timeline visual semua kejadian

### 3. **Export Data**
- Export CSV: `/admin/kpi/export?format=csv`
- Export JSON: `/admin/kpi/export?format=json`

---

## 🎓 Tips Best Practice

### Untuk Admin:
1. **Segera buat tiket** setelah menerima email keluhan
2. **Isi waktu email diterima dengan akurat** - ini penting untuk KPI yang benar
3. **Balas tiket sesegera mungkin** - target 30 menit
4. **Selesaikan tiket dalam 48 jam** - target SLA resolution

### Untuk Monitoring:
1. Cek dashboard KPI setiap hari
2. Perhatikan tiket dengan badge merah (melebihi SLA)
3. Identifikasi pola: kategori/prioritas mana yang sering melebihi SLA
4. Export data untuk laporan bulanan

### Untuk Improvement:
1. Jika banyak **ticket_creation_delay** tinggi → Latih admin untuk lebih cepat input
2. Jika **response_time** sering melebihi SLA → Tambah staff atau improve workflow
3. Jika **resolution_time** tinggi → Review proses penyelesaian masalah

---

## 🔧 Troubleshooting

### Q: Field "Waktu Email Diterima" tidak muncul
**A:** Pastikan Anda memilih **Channel = Email** dulu. Field ini hanya muncul untuk channel Email.

### Q: KPI tidak terhitung
**A:** Pastikan:
- Untuk response time: Admin sudah membalas tiket
- Untuk resolution time: Status tiket sudah diubah ke "Resolved"
- Cek database: `response_time_minutes` dan `resolution_time_minutes` terisi

### Q: Dashboard KPI kosong
**A:** Pastikan:
- Ada tiket dengan `email_received_at` yang terisi
- Ada tiket yang sudah direspons/resolved
- Filter tanggal tidak terlalu sempit

### Q: Cara menghitung ulang KPI
**A:** Jalankan command:
```bash
php artisan tinker
$ticket = Ticket::find(1);
$ticket->calculateResponseTime();
$ticket->calculateResolutionTime();
$ticket->calculateTicketCreationDelay();
$ticket->save();
```

---

## 📚 File Terkait

- **Migration:** `database/migrations/2025_10_29_204448_add_kpi_fields_to_tickets_table.php`
- **Model:** `app/Models/Ticket.php`
- **Service:** `app/Services/KpiCalculationService.php`
- **Service:** `app/Services/TicketService.php`
- **Controller:** `app/Http/Controllers/KpiDashboardController.php`
- **View Form:** `resources/views/admin/create-ticket.blade.php`
- **View Dashboard:** `resources/views/kpi/dashboard.blade.php`
- **View Detail:** `resources/views/tickets/show.blade.php`

---

**Dokumentasi dibuat:** 29 Oktober 2025  
**Versi sistem:** Laravel 12.32.5 | PHP 8.4.13
