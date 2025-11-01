# KPI Case Study & Testing Guide

## Case Study: Ticket dari Email Keluhan

### Skenario
Seorang user mengirim email keluhan yang kemudian direkap oleh admin menjadi ticket di sistem.

### Timeline Events

#### 1. Email Keluhan Masuk
- **Tanggal**: 21 Oktober 2025
- **Waktu**: 13:00 WIB
- **From**: john.doe@company.com
- **Subject**: Laptop tidak bisa nyala
- **Content**: 
  ```
  Dear IT Support,
  
  Laptop saya tidak bisa menyala sejak kemarin sore. 
  Saya sudah coba charge selama semalam tapi tetap tidak ada tanda-tanda kehidupan.
  Lampu indikator charger juga tidak menyala.
  
  Mohon bantuannya karena saya butuh laptop untuk presentasi besok.
  
  Best regards,
  John Doe
  Sales Department
  ```

#### 2. Admin Pertama Kali Membaca & Merespon Email
- **Tanggal**: 21 Oktober 2025
- **Waktu**: 13:15 WIB (15 menit setelah email masuk)
- **Response**: 
  ```
  Dear John,
  
  Terima kasih sudah menghubungi IT Support.
  Kami akan segera menindaklanjuti keluhan Anda.
  
  Bisa tolong infokan:
  - Tipe/model laptop?
  - Nomor aset laptop (sticker di belakang laptop)?
  - Lokasi Anda saat ini?
  
  Kami akan kirim teknisi untuk pengecekan.
  
  Best regards,
  IT Support Team
  ```

#### 3. Masalah Selesai Diresolve
- **Tanggal**: 23 Oktober 2025
- **Waktu**: 13:00 WIB (2 hari setelah email masuk)
- **Resolution**: 
  - Teknisi datang dan cek laptop
  - Ternyata charger rusak
  - Ganti charger baru
  - Laptop sudah bisa menyala normal

#### 4. Ticket Dibuat di Sistem
- **Tanggal**: 25 Oktober 2025
- **Waktu**: 10:00 WIB (4 hari setelah email masuk)
- **Catatan**: Admin baru sempat merekap email ke sistem

---

## KPI Metrics dari Case Study

### Calculated Metrics

1. **Response Time**
   - Formula: `first_response_at - email_received_at`
   - Nilai: `13:15 - 13:00 = 15 menit`
   - Status: ✅ **MEMENUHI SLA** (Target: ≤ 30 menit)
   - Grade: **Excellent**

2. **Resolution Time**
   - Formula: `resolved_at - email_received_at`
   - Nilai: `(23 Okt 13:00) - (21 Okt 13:00) = 48 jam = 2880 menit`
   - Status: ✅ **MEMENUHI SLA** (Target: ≤ 48 jam)
   - Grade: **Good** (tepat di batas SLA)

3. **Ticket Creation Delay**
   - Formula: `created_at - email_received_at`
   - Nilai: `(25 Okt 10:00) - (21 Okt 13:00) = 3 hari 21 jam = 5700 menit`
   - Status: ⚠️ **PERLU PERBAIKAN**
   - Grade: **Poor** (delay terlalu lama)
   - **Rekomendasi**: Ticket seharusnya dibuat segera saat email masuk

---

## Testing Guide

### Step 1: Buat Ticket dengan KPI Data

```php
// Di Tinker atau seeder
use App\Services\TicketService;
use Carbon\Carbon;

$ticketService = app(TicketService::class);

$ticket = $ticketService->createTicketByAdmin([
    'reporter_nip' => '1234567890',
    'reporter_name' => 'John Doe',
    'reporter_email' => 'john.doe@company.com',
    'reporter_phone' => '081234567890',
    'reporter_department' => 'Sales',
    'channel' => 'email',
    'input_method' => 'manual',
    'subject' => 'Laptop tidak bisa nyala',
    'description' => 'Laptop saya tidak bisa menyala sejak kemarin sore. Saya sudah coba charge selama semalam tapi tetap tidak ada tanda-tanda kehidupan.',
    'category' => 'Technical',
    'priority' => 'high',
    'created_by_admin' => 1, // Admin ID
    
    // KPI Data - PENTING!
    'email_received_at' => Carbon::parse('2025-10-21 13:00:00'),
]);

// Ticket akan dibuat dengan:
// - email_received_at: 21 Okt 2025, 13:00
// - created_at: sekarang (25 Okt 2025, 10:00)
// - ticket_creation_delay_minutes: auto-calculated
```

### Step 2: Simulasi First Response

```php
use App\Services\TicketService;
use Carbon\Carbon;

$ticketService = app(TicketService::class);
$ticket = Ticket::where('ticket_number', 'TKT-20251025-0001')->first();

// Admin reply (akan trigger first_response_at)
$ticketService->addThreadMessage($ticket, [
    'sender_type' => 'admin',
    'sender_id' => 1,
    'sender_name' => 'IT Support Team',
    'message_type' => 'reply',
    'message' => 'Terima kasih sudah menghubungi IT Support. Kami akan segera menindaklanjuti keluhan Anda.',
]);

// Manually set first_response_at to match case study
$ticket->update([
    'first_response_at' => Carbon::parse('2025-10-21 13:15:00')
]);

// Recalculate KPI
app(KpiCalculationService::class)->updateTicketKpiMetrics($ticket);

// Check result
echo "Response Time: " . $ticket->getResponseTimeFormatted(); // "15 menit"
echo "\nWithin Target: " . ($ticket->isResponseTimeWithinTarget() ? 'YES' : 'NO'); // YES
```

### Step 3: Simulasi Resolution

```php
use App\Services\TicketService;
use Carbon\Carbon;

$ticketService = app(TicketService::class);
$ticket = Ticket::where('ticket_number', 'TKT-20251025-0001')->first();

// Update status ke resolved
$ticketService->updateStatus($ticket, 'resolved', 'Charger sudah diganti, laptop normal');

// Manually set resolved_at to match case study
$ticket->update([
    'resolved_at' => Carbon::parse('2025-10-23 13:00:00')
]);

// Recalculate KPI
app(KpiCalculationService::class)->updateTicketKpiMetrics($ticket);

// Check results
echo "Resolution Time: " . $ticket->getResolutionTimeFormatted(); // "2 hari"
echo "\nWithin Target: " . ($ticket->isResolutionTimeWithinTarget() ? 'YES' : 'NO'); // YES
echo "\nDelay: " . $ticket->getTicketCreationDelayFormatted(); // "3 hari 21 jam"
```

### Step 4: Check KPI Metrics

```php
$ticket->refresh();

echo "=== KPI METRICS ===\n";
echo "Email Received: " . $ticket->email_received_at->format('d M Y, H:i') . "\n";
echo "First Response: " . ($ticket->first_response_at ? $ticket->first_response_at->format('d M Y, H:i') : '-') . "\n";
echo "Resolved: " . ($ticket->resolved_at ? $ticket->resolved_at->format('d M Y, H:i') : '-') . "\n";
echo "Ticket Created: " . $ticket->created_at->format('d M Y, H:i') . "\n";
echo "\n";
echo "Response Time: " . $ticket->getResponseTimeFormatted() . "\n";
echo "Resolution Time: " . $ticket->getResolutionTimeFormatted() . "\n";
echo "Creation Delay: " . $ticket->getTicketCreationDelayFormatted() . "\n";
echo "\n";
echo "SLA Response: " . ($ticket->isResponseTimeWithinTarget() ? '✅ PASS' : '❌ FAIL') . "\n";
echo "SLA Resolution: " . ($ticket->isResolutionTimeWithinTarget() ? '✅ PASS' : '❌ FAIL') . "\n";
```

Expected Output:
```
=== KPI METRICS ===
Email Received: 21 Oct 2025, 13:00
First Response: 21 Oct 2025, 13:15
Resolved: 23 Oct 2025, 13:00
Ticket Created: 25 Oct 2025, 10:00

Response Time: 15 menit
Resolution Time: 2 hari
Creation Delay: 3 hari 21 jam

SLA Response: ✅ PASS
SLA Resolution: ✅ PASS
```

---

## Seeder untuk Testing Data

Buat file `database/seeders/KpiTestDataSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use App\Services\KpiCalculationService;
use Carbon\Carbon;

class KpiTestDataSeeder extends Seeder
{
    public function run()
    {
        $ticketService = app(TicketService::class);
        $kpiService = app(KpiCalculationService::class);
        $admin = User::where('role', 'admin')->first();

        // Case 1: Good Performance (memenuhi semua SLA)
        $ticket1 = $ticketService->createTicketByAdmin([
            'reporter_nip' => '1234567890',
            'reporter_name' => 'John Doe',
            'reporter_email' => 'john.doe@company.com',
            'reporter_phone' => '081234567890',
            'reporter_department' => 'Sales',
            'channel' => 'email',
            'input_method' => 'manual',
            'subject' => 'Laptop tidak bisa nyala',
            'description' => 'Laptop tidak bisa menyala, sudah coba charge semalam.',
            'category' => 'Technical',
            'priority' => 'high',
            'created_by_admin' => $admin->id,
            'email_received_at' => Carbon::parse('2025-10-21 13:00:00'),
        ]);

        $ticket1->update([
            'first_response_at' => Carbon::parse('2025-10-21 13:15:00'),
            'resolved_at' => Carbon::parse('2025-10-23 13:00:00'),
        ]);
        $kpiService->updateTicketKpiMetrics($ticket1);

        // Case 2: Slow Response (melebihi SLA response)
        $ticket2 = $ticketService->createTicketByAdmin([
            'reporter_nip' => '0987654321',
            'reporter_name' => 'Jane Smith',
            'reporter_email' => 'jane.smith@company.com',
            'reporter_phone' => '082234567890',
            'reporter_department' => 'Marketing',
            'channel' => 'email',
            'input_method' => 'manual',
            'subject' => 'Printer macet',
            'description' => 'Printer kantor macet, kertas tersangkut di dalam.',
            'category' => 'Technical',
            'priority' => 'medium',
            'created_by_admin' => $admin->id,
            'email_received_at' => Carbon::parse('2025-10-22 09:00:00'),
        ]);

        $ticket2->update([
            'first_response_at' => Carbon::parse('2025-10-22 11:00:00'), // 2 jam = 120 menit (melebihi 30 menit)
            'resolved_at' => Carbon::parse('2025-10-23 10:00:00'),
        ]);
        $kpiService->updateTicketKpiMetrics($ticket2);

        // Case 3: Slow Resolution (melebihi SLA resolution)
        $ticket3 = $ticketService->createTicketByAdmin([
            'reporter_nip' => '1122334455',
            'reporter_name' => 'Bob Wilson',
            'reporter_email' => 'bob.wilson@company.com',
            'reporter_phone' => '083234567890',
            'reporter_department' => 'Finance',
            'channel' => 'email',
            'input_method' => 'manual',
            'subject' => 'Tidak bisa akses server',
            'description' => 'Tidak bisa remote akses ke server finance.',
            'category' => 'Technical',
            'priority' => 'critical',
            'created_by_admin' => $admin->id,
            'email_received_at' => Carbon::parse('2025-10-20 14:00:00'),
        ]);

        $ticket3->update([
            'first_response_at' => Carbon::parse('2025-10-20 14:10:00'), // 10 menit (good)
            'resolved_at' => Carbon::parse('2025-10-24 10:00:00'), // 3+ hari (melebihi 48 jam)
        ]);
        $kpiService->updateTicketKpiMetrics($ticket3);

        $this->command->info('KPI test data created successfully!');
    }
}
```

Run seeder:
```bash
php artisan db:seed --class=KpiTestDataSeeder
```

---

## Dashboard Testing Checklist

### 1. Access Dashboard
- [ ] Buka `/admin/kpi`
- [ ] Pastikan hanya admin yang bisa akses
- [ ] Dashboard load tanpa error

### 2. Check Summary Cards
- [ ] Total Tickets menampilkan jumlah yang benar
- [ ] Response Rate dihitung dengan benar
- [ ] Avg Response Time ditampilkan
- [ ] Avg Resolution Time ditampilkan

### 3. Check Filters
- [ ] Filter by date range
- [ ] Filter by category
- [ ] Filter by priority
- [ ] Reset filter works

### 4. Check Data Tables
- [ ] KPI per Category ditampilkan
- [ ] KPI per Priority ditampilkan
- [ ] Recent tickets dengan KPI data ditampilkan

### 5. Check Export
- [ ] Export CSV berfungsi
- [ ] Export JSON berfungsi
- [ ] Data di export sesuai dengan dashboard

### 6. Check SLA Indicators
- [ ] Badge hijau untuk within SLA
- [ ] Badge merah untuk exceed SLA
- [ ] Persentase compliance dihitung dengan benar

---

## Common Issues & Solutions

### Issue 1: KPI tidak ter-calculate
**Symptom**: `response_time_minutes` null padahal `email_received_at` dan `first_response_at` terisi

**Solution**:
```php
// Manual recalculate
$ticket = Ticket::find($ticketId);
app(KpiCalculationService::class)->updateTicketKpiMetrics($ticket);
```

### Issue 2: First response tidak ter-track
**Symptom**: `first_response_at` tetap null setelah admin reply

**Solution**: Pastikan sender_type adalah 'admin'
```php
$ticketService->addThreadMessage($ticket, [
    'sender_type' => 'admin', // PENTING!
    'sender_name' => Auth::user()->name,
    'message' => 'Reply from admin',
]);
```

### Issue 3: Dashboard menampilkan 0 tickets
**Symptom**: Dashboard kosong padahal ada tickets

**Probable Cause**: Tickets tidak punya `email_received_at`

**Solution**: Tambahkan `email_received_at` pada tickets yang ada
```php
// Update existing tickets
Ticket::whereNull('email_received_at')
    ->where('channel', 'email')
    ->update(['email_received_at' => DB::raw('created_at')]);
```

---

**Last Updated**: 29 Oktober 2025
