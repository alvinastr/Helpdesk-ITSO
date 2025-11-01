# KPI (Key Performance Indicator) System Documentation

## Overview
Sistem KPI ini dirancang untuk mengukur performa helpdesk dalam menangani ticket, khususnya untuk merekap data email keluhan yang masuk dan mengukur waktu response serta resolution.

## Latar Belakang
Sistem helpdesk ini berfungsi untuk merekap data-data atau keluhan yang masuk melalui email. Dalam prakteknya, terdapat delay antara:
- **Waktu email keluhan masuk** (email_received_at)
- **Waktu admin merespon pertama kali** (first_response_at)
- **Waktu masalah selesai diresolve** (resolved_at)
- **Waktu ticket dibuat di sistem** (created_at)

## Case Study
Contoh kasus yang terjadi:
1. **Email keluhan masuk**: 21 Oktober 2025, 13:00
2. **Admin merespon pertama**: 21 Oktober 2025, 13:15 (Response Time: **15 menit**)
3. **Masalah selesai diresolve**: 23 Oktober 2025, 13:00 (Resolution Time: **2 hari**)
4. **Ticket dibuat di sistem**: 25 Oktober 2025, 10:00 (Creation Delay: **4 hari**)

Dari case ini terlihat ada delay signifikan antara email diterima dengan pembuatan ticket di sistem.

## KPI Metrics

### 1. Email Received Time (email_received_at)
- **Deskripsi**: Waktu email/keluhan pertama kali diterima
- **Tipe**: Timestamp
- **Digunakan untuk**: Titik awal perhitungan semua KPI

### 2. First Response Time (first_response_at)
- **Deskripsi**: Waktu respon pertama dari admin/staff
- **Tipe**: Timestamp
- **Target SLA**: ≤ 30 menit
- **Digunakan untuk**: Mengukur kecepatan respon tim

### 3. Resolved Time (resolved_at)
- **Deskripsi**: Waktu keluhan selesai diresolve
- **Tipe**: Timestamp
- **Target SLA**: ≤ 48 jam (2 hari)
- **Digunakan untuk**: Mengukur kecepatan penyelesaian masalah

### 4. Response Time (response_time_minutes)
- **Deskripsi**: Durasi dari email diterima hingga respon pertama
- **Tipe**: Integer (dalam menit)
- **Rumus**: `first_response_at - email_received_at`
- **Target**: ≤ 30 menit

### 5. Resolution Time (resolution_time_minutes)
- **Deskripsi**: Durasi dari email diterima hingga masalah selesai
- **Tipe**: Integer (dalam menit)
- **Rumus**: `resolved_at - email_received_at`
- **Target**: ≤ 2880 menit (48 jam)

### 6. Ticket Creation Delay (ticket_creation_delay_minutes)
- **Deskripsi**: Durasi dari email diterima hingga ticket dibuat
- **Tipe**: Integer (dalam menit)
- **Rumus**: `created_at - email_received_at`
- **Catatan**: Semakin kecil semakin baik, idealnya 0 (langsung dibuat)

## Database Schema

### Migration: add_kpi_fields_to_tickets_table
```sql
ALTER TABLE tickets ADD COLUMN (
    email_received_at TIMESTAMP NULL COMMENT 'Waktu email/keluhan pertama kali diterima',
    first_response_at TIMESTAMP NULL COMMENT 'Waktu respon pertama dari admin/staff',
    resolved_at TIMESTAMP NULL COMMENT 'Waktu keluhan selesai diresolve',
    response_time_minutes INT NULL COMMENT 'Durasi dari email_received_at ke first_response_at (menit)',
    resolution_time_minutes INT NULL COMMENT 'Durasi dari email_received_at ke resolved_at (menit)',
    ticket_creation_delay_minutes INT NULL COMMENT 'Durasi dari email_received_at ke created_at ticket (menit)'
);
```

## Cara Penggunaan

### 1. Saat Admin Membuat Ticket dari Email

Ketika admin merekap email keluhan dan membuat ticket, **wajib** mengisi field `email_received_at`:

```php
// Di AdminTicketController atau form pembuatan ticket
$ticketService->createTicketByAdmin([
    'reporter_nip' => '123456',
    'reporter_name' => 'John Doe',
    'reporter_email' => 'john@example.com',
    'reporter_phone' => '081234567890',
    'reporter_department' => 'IT',
    'channel' => 'email',
    'input_method' => 'manual',
    'subject' => 'Laptop tidak bisa nyala',
    'description' => 'Laptop saya tidak bisa menyala sejak kemarin...',
    'category' => 'Technical',
    'priority' => 'high',
    'created_by_admin' => Auth::id(),
    
    // PENTING: Isi dengan waktu email diterima
    'email_received_at' => '2025-10-21 13:00:00', // Dari timestamp email
]);
```

### 2. Automatic KPI Tracking

Sistem akan otomatis menghitung KPI:

#### a. First Response Time
Akan ter-track otomatis saat admin pertama kali reply ke ticket:
```php
// Otomatis dijalankan di TicketService::addThreadMessage()
// Ketika sender_type === 'admin' dan first_response_at masih null
```

#### b. Resolution Time
Akan ter-track otomatis saat status berubah menjadi 'resolved':
```php
// Otomatis dijalankan di TicketService::updateStatus()
// Ketika newStatus === 'resolved'
```

#### c. Ticket Creation Delay
Akan ter-hitung otomatis saat ticket dibuat dengan email_received_at terisi:
```php
// Otomatis dijalankan di TicketService::createTicketByAdmin()
// Menggunakan KpiCalculationService::updateTicketKpiMetrics()
```

### 3. Manual KPI Update

Jika perlu update manual:
```php
use App\Services\KpiCalculationService;

$kpiService = app(KpiCalculationService::class);

// Update semua KPI metrics
$kpiService->updateTicketKpiMetrics($ticket);

// Set first response time secara manual
$kpiService->setFirstResponseTime($ticket, Carbon::parse('2025-10-21 13:15:00'));

// Set resolved time secara manual
$kpiService->setResolvedTime($ticket, Carbon::parse('2025-10-23 13:00:00'));
```

## KPI Dashboard

### Akses Dashboard
- **URL**: `/admin/kpi`
- **Permission**: Admin only
- **Route Name**: `kpi.dashboard`

### Fitur Dashboard
1. **KPI Summary Cards**
   - Total Tickets
   - Response Rate
   - Average Response Time
   - Average Resolution Time

2. **Additional Metrics**
   - Resolution Rate
   - Ticket Creation Delay
   - Response Time Range (Min/Max)

3. **Filter Options**
   - Date Range (From - To)
   - Category
   - Priority
   - Status
   - Assigned To

4. **KPI Breakdown**
   - Per Category
   - Per Priority
   - Daily/Weekly/Monthly Trends

5. **Export Options**
   - CSV Export
   - JSON Export

### Example Filter Usage
```
GET /admin/kpi?date_from=2025-10-01&date_to=2025-10-31&priority=high
```

## API Endpoints

### 1. Get KPI Summary
```
GET /api/kpi/summary?date_from=2025-10-01&date_to=2025-10-31
```

Response:
```json
{
    "total_tickets": 150,
    "tickets_with_response": 145,
    "tickets_resolved": 130,
    "response_rate": 96.67,
    "resolution_rate": 86.67,
    "avg_response_time_minutes": 25.5,
    "avg_response_time_formatted": "25 menit",
    "avg_resolution_time_minutes": 2450.0,
    "avg_resolution_time_formatted": "1 hari 16 jam 50 menit",
    "avg_creation_delay_minutes": 120.0,
    "avg_creation_delay_formatted": "2 jam",
    "sla_response_compliance": 85.5,
    "sla_resolution_compliance": 78.3
}
```

### 2. Get KPI Trends
```
GET /api/kpi/trends?period=daily&date_from=2025-10-01&date_to=2025-10-31
```

Response:
```json
[
    {
        "period": "2025-10-21",
        "total_tickets": 12,
        "tickets_with_response": 11,
        "tickets_resolved": 10,
        "avg_response_time": 28.5,
        "avg_response_time_formatted": "28 menit",
        "avg_resolution_time": 2340.0,
        "avg_resolution_time_formatted": "1 hari 15 jam"
    }
]
```

## SLA (Service Level Agreement) Targets

### Default Targets
```php
// Response Time Target
$responseTarget = 30; // minutes

// Resolution Time Target  
$resolutionTarget = 2880; // minutes (48 hours / 2 days)
```

### Check SLA Compliance
```php
// Check individual ticket
if ($ticket->isResponseTimeWithinTarget()) {
    echo "Response time memenuhi SLA";
}

if ($ticket->isResolutionTimeWithinTarget()) {
    echo "Resolution time memenuhi SLA";
}
```

### Modify SLA Targets
Untuk mengubah target SLA, edit di `app/Services/KpiCalculationService.php`:

```php
public function getKpiSummary(array $filters = [])
{
    // ...
    
    // Ubah nilai ini sesuai kebutuhan
    $responseTarget = 30; // minutes
    $resolutionTarget = 2880; // minutes (48 hours)
    
    // ...
}
```

## Helper Methods di Ticket Model

```php
// Calculate KPI
$ticket->calculateResponseTime(); // Returns minutes
$ticket->calculateResolutionTime(); // Returns minutes
$ticket->calculateTicketCreationDelay(); // Returns minutes

// Get formatted time
$ticket->getResponseTimeFormatted(); // "25 menit" atau "1 jam 30 menit"
$ticket->getResolutionTimeFormatted(); // "1 hari 15 jam 30 menit"
$ticket->getTicketCreationDelayFormatted(); // "2 hari"

// Check SLA
$ticket->isResponseTimeWithinTarget(); // true/false
$ticket->isResponseTimeWithinTarget(15); // Custom target: 15 minutes
$ticket->isResolutionTimeWithinTarget(); // true/false
$ticket->isResolutionTimeWithinTarget(1440); // Custom target: 24 hours
```

## Form Input untuk Email Received Time

Tambahkan field di form pembuatan ticket admin:

```html
<div class="form-group">
    <label for="email_received_at">Waktu Email Diterima *</label>
    <input type="datetime-local" 
           class="form-control" 
           id="email_received_at" 
           name="email_received_at" 
           required>
    <small class="form-text text-muted">
        Isi dengan waktu email keluhan pertama kali diterima
    </small>
</div>
```

## Best Practices

### 1. Selalu Isi Email Received Time
Ketika admin merekap email dan membuat ticket, **WAJIB** mengisi `email_received_at` dengan timestamp dari email asli.

### 2. Response Cepat
Usahakan first response dalam waktu < 30 menit untuk memenuhi SLA.

### 3. Minimize Ticket Creation Delay
Idealnya ticket dibuat segera setelah email diterima. Jika ada delay, catat alasannya.

### 4. Update Status dengan Benar
- Set status ke 'resolved' saat masalah sudah selesai diperbaiki
- Set status ke 'closed' saat ticket benar-benar ditutup dan user sudah puas

### 5. Monitor Dashboard Secara Berkala
Review KPI dashboard minimal 1x per minggu untuk:
- Identifikasi bottleneck
- Monitor compliance terhadap SLA
- Identifikasi kategori/prioritas yang butuh perhatian lebih

## Troubleshooting

### KPI Tidak Ter-calculate
**Penyebab**: `email_received_at` tidak diisi
**Solusi**: Pastikan field `email_received_at` terisi saat membuat ticket

### First Response Time Tidak Ter-track
**Penyebab**: Reply pertama bukan dari admin (sender_type bukan 'admin')
**Solusi**: Pastikan saat admin reply, gunakan `sender_type: 'admin'`

### Resolution Time Tidak Ter-track
**Penyebab**: Status tidak diubah ke 'resolved'
**Solusi**: Update status ticket menjadi 'resolved' saat masalah selesai

## Future Improvements

1. **Notifikasi SLA Breach**
   - Email notifikasi otomatis jika mendekati/melanggar SLA
   
2. **Advanced Analytics**
   - Heatmap response time per hari/jam
   - Trend analysis dengan machine learning
   
3. **Team Performance**
   - KPI per staff/admin
   - Leaderboard tim support
   
4. **Custom SLA per Priority**
   - Critical: 15 menit
   - High: 30 menit
   - Medium: 1 jam
   - Low: 4 jam

## Support

Untuk pertanyaan atau issues terkait KPI system, silakan:
1. Check dokumentasi ini terlebih dahulu
2. Review code di `app/Services/KpiCalculationService.php`
3. Contact development team

---

**Last Updated**: 29 Oktober 2025
**Version**: 1.0
