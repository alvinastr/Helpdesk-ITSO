# 🧪 Panduan Testing Reply Function

## Overview
Dokumen ini menjelaskan cara testing fitur reply di sistem Helpdesk ITSO, baik manual maupun automated testing.

---

## 1️⃣ Manual Testing via Browser

### Persiapan
```bash
# Start development server
php artisan serve

# Buka browser: http://localhost:8000
```

### Step-by-Step Testing

#### **Test Case 1: User Reply ke Ticket Sendiri**

**Precondition:**
- User sudah login
- Ada minimal 1 ticket dengan status OPEN

**Steps:**
1. Login sebagai user biasa
   - Email: user@example.com (sesuai seeder Anda)
   
2. Pergi ke halaman "My Tickets"
   - URL: `http://localhost:8000/tickets`
   
3. Klik salah satu ticket yang status-nya OPEN/IN_PROGRESS
   
4. Scroll ke bagian bawah (Reply Form)
   
5. Isi form reply:
   ```
   Message: "Saya sudah coba restart tapi masih error. 
            Error code: 0x80070005"
   Attachments: [opsional] Upload screenshot
   ```
   
6. Klik "Send Reply"

**Expected Result:**
✅ Redirect kembali ke halaman ticket
✅ Muncul alert sukses: "Reply berhasil ditambahkan!"
✅ Thread baru muncul di conversation
✅ Sender: nama user
✅ Timestamp terbaru
✅ Pesan terlihat di thread

**Database Check:**
```sql
SELECT * FROM ticket_threads 
WHERE ticket_id = [ID_TICKET] 
ORDER BY created_at DESC 
LIMIT 1;

-- Should show:
-- sender_type: 'user'
-- sender_name: [nama user]
-- message_type: 'reply'
-- message: [text yang diinput]
```

---

#### **Test Case 2: Admin Reply ke Ticket User**

**Precondition:**
- Admin sudah login
- Ada ticket dari user dengan status OPEN

**Steps:**
1. Login sebagai admin
   - Email: admin@example.com
   
2. Pergi ke admin dashboard
   - URL: `http://localhost:8000/admin/dashboard`
   
3. Klik salah satu ticket dari list
   
4. Scroll ke bagian reply form
   
5. Isi form reply:
   ```
   Message: "Silakan coba langkah berikut:
            1. Buka Control Panel
            2. Uninstall program X
            3. Restart komputer
            4. Install ulang dari link ini: [link]
            
            Mohon konfirmasi hasilnya."
   ```
   
6. Klik "Send Reply"

**Expected Result:**
✅ Reply tersimpan
✅ Thread muncul dengan label "Admin"
✅ Background thread berwarna berbeda (bg-light)
✅ Icon admin muncul

**Notification Check:**
✅ User menerima email notification
✅ Subject: "Update Ticket - TKT-XXXXXXXX-XXXX"
✅ Body berisi pesan dari admin

---

#### **Test Case 3: Reply dengan File Attachment**

**Steps:**
1. Login sebagai user atau admin
2. Buka ticket
3. Isi message dan upload file:
   ```
   File types: .jpg, .png, .pdf, .docx
   Max size: 5MB per file
   Multiple files: Ya (bisa multiple)
   ```
4. Submit

**Expected Result:**
✅ File terupload ke `storage/app/public/ticket-attachments/`
✅ Link download muncul di thread
✅ Format JSON di database: 
```json
[
  {
    "filename": "screenshot.png",
    "path": "ticket-attachments/abc123.png"
  }
]
```

---

#### **Test Case 4: Reply Mengubah Status (Auto-Update)**

**Scenario:** User reply setelah ticket di-resolve

**Precondition:**
- Ticket status: RESOLVED

**Steps:**
1. Login sebagai user (owner ticket)
2. Buka ticket yang status RESOLVED
3. Reply dengan: "Masih belum bisa, error masih muncul"
4. Submit

**Expected Result:**
✅ Reply tersimpan
✅ Status otomatis berubah: RESOLVED → IN_PROGRESS
✅ Status history tercatat
✅ Admin dapat notifikasi bahwa user reply after resolution

**Code Reference:**
```php
// Auto-update status if needed
if ($ticket->status === 'resolved' && Auth::user()->role !== 'admin') {
    $this->ticketService->updateStatus($ticket, 'in_progress', 
        'User replied after resolution');
}
```

---

#### **Test Case 5: Reply di Ticket yang Closed (Negative Test)**

**Precondition:**
- Ticket status: CLOSED

**Steps:**
1. Buka ticket dengan status CLOSED
2. Cek apakah reply form muncul

**Expected Result:**
❌ Reply form TIDAK muncul
✅ Hanya bisa view thread (readonly)
✅ Pesan: "Ticket ini sudah ditutup"

**Code Reference:**
```php
@if(!in_array($ticket->status, ['closed', 'rejected']))
    <!-- Reply form -->
@endif
```

---

#### **Test Case 6: Unauthorized Access (Security Test)**

**Scenario:** User A mencoba reply ke ticket milik User B

**Steps:**
1. Login sebagai User A
2. Manually akses URL:
   ```
   POST http://localhost:8000/tickets/[ID_TICKET_USER_B]/reply
   ```

**Expected Result:**
❌ HTTP 403 Forbidden
✅ Error: "Unauthorized access"

**Code Reference:**
```php
// Authorization check
if (Auth::user()->role !== 'admin' && $ticket->user_id !== Auth::id()) {
    abort(403, 'Unauthorized access');
}
```

---

## 2️⃣ Testing via Tinker (Command Line)

```bash
php artisan tinker
```

### Test 1: Create Reply via Service

```php
// Load ticket
$ticket = \App\Models\Ticket::where('status', 'open')->first();

// Load service
$service = app(\App\Services\TicketService::class);

// Add reply
$service->addThreadMessage($ticket, [
    'sender_type' => 'user',
    'sender_id' => $ticket->user_id,
    'sender_name' => $ticket->user_name,
    'message_type' => 'reply',
    'message' => 'Testing reply via tinker'
]);

// Verify
$ticket->threads()->latest()->first();
```

**Expected Output:**
```php
=> App\Models\TicketThread {
     id: xxx,
     ticket_id: xxx,
     sender_type: "user",
     sender_name: "...",
     message: "Testing reply via tinker",
     created_at: "...",
}
```

### Test 2: Check Thread Count

```php
$ticket = \App\Models\Ticket::find(1);
$ticket->threads()->count();  // Should be > 1 (initial + replies)

// Is this a reply thread?
$ticket->isReply();  // true if threads > 1
```

### Test 3: Simulate Auto Status Change

```php
// Set ticket to resolved
$ticket = \App\Models\Ticket::first();
$ticket->update(['status' => 'resolved']);

// User replies (should change to in_progress)
$service = app(\App\Services\TicketService::class);
$service->addThreadMessage($ticket, [
    'sender_type' => 'user',
    'sender_id' => $ticket->user_id,
    'sender_name' => $ticket->user_name,
    'message_type' => 'reply',
    'message' => 'Masih error pak'
]);

// Check if status changed (manual check in controller)
$ticket->fresh()->status;  // Should still be 'resolved' (auto-change is in controller)
```

---

## 3️⃣ Automated Testing (PHPUnit)

### Run Existing Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/TicketFeatureTest.php

# Run specific test
php artisan test --filter=user_can_reply_to_ticket
```

### Test Output yang Diharapkan:

```
PASS  Tests\Feature\TicketFeatureTest
✓ user can reply to ticket                    0.45s
```

### Manual Test Execution

```bash
# Run test for reply functionality
php artisan test tests/Feature/FlowchartWorkflowTest.php --filter=ticket_workflow_reply_update_thread
```

**Expected:**
```
PASS  Tests\Feature\FlowchartWorkflowTest
✓ ticket workflow reply update thread         0.52s
```

---

## 4️⃣ API Testing (Postman/cURL)

### Setup
```bash
# Generate API token (if using Sanctum/Passport)
# Or use session-based auth
```

### Test Reply via API

```bash
# Login first to get session
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}' \
  -c cookies.txt

# Post reply
curl -X POST http://localhost:8000/tickets/1/reply \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "message": "Ini test reply via API"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Reply berhasil ditambahkan!"
}
```

---

## 5️⃣ Testing Checklist

### ✅ Functional Testing

- [ ] User dapat reply ke ticket sendiri
- [ ] Admin dapat reply ke semua ticket
- [ ] User tidak bisa reply ke ticket user lain
- [ ] Reply muncul di thread dengan timestamp
- [ ] Sender name dan type benar
- [ ] File attachment bisa diupload
- [ ] Multiple files bisa diupload
- [ ] Reply dengan message kosong ditolak (validation)
- [ ] Reply dengan message < 5 char ditolak
- [ ] File > 5MB ditolak
- [ ] Reply di ticket closed/rejected tidak bisa
- [ ] Auto status change works (resolved → in_progress)

### ✅ UI/UX Testing

- [ ] Reply form hanya muncul di status yang tepat
- [ ] Thread ditampilkan ascending (oldest first)
- [ ] User thread dan admin thread beda background
- [ ] System message punya icon robot
- [ ] Attachment link bisa diklik
- [ ] Success message muncul setelah reply
- [ ] Error message muncul jika validation fail
- [ ] Loading indicator muncul saat submit

### ✅ Security Testing

- [ ] CSRF token validation
- [ ] Authorization check (user vs ticket owner)
- [ ] File upload validation (type, size)
- [ ] XSS prevention di message
- [ ] SQL injection prevention
- [ ] Unauthorized access blocked

### ✅ Notification Testing

- [ ] Email notification sent to user when admin replies
- [ ] Email notification sent to admin when user replies
- [ ] WhatsApp notification (jika configured)
- [ ] Notification content correct
- [ ] Ticket link in email works

---

## 6️⃣ Common Issues & Troubleshooting

### Issue 1: Reply tidak tersimpan

**Symptoms:**
- Form submit tapi thread tidak muncul
- Redirect tapi tidak ada data baru

**Check:**
```php
// Check validation errors
dd($request->validate([...]));

// Check service method
dd($this->ticketService->addThreadMessage(...));

// Check database
DB::enableQueryLog();
// ... submit form ...
dd(DB::getQueryLog());
```

**Fix:**
- Pastikan validation pass
- Check database connection
- Lihat error log: `storage/logs/laravel.log`

---

### Issue 2: File upload gagal

**Symptoms:**
- Error: "The file could not be uploaded"
- File tidak muncul di storage

**Check:**
```bash
# Check storage permissions
ls -la storage/app/public

# Create symlink if not exists
php artisan storage:link

# Check file size limit in php.ini
php -i | grep upload_max_filesize
```

**Fix:**
```bash
# Set permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Update php.ini
upload_max_filesize = 10M
post_max_size = 10M
```

---

### Issue 3: Unauthorized access untuk admin

**Symptoms:**
- Admin mendapat 403 saat reply

**Check:**
```php
// Di TicketController::reply
dd(Auth::user()->role);  // Should be 'admin'
dd($ticket->user_id);
dd(Auth::id());
```

**Fix:**
- Pastikan admin role di database = 'admin'
- Check middleware authentication

---

### Issue 4: Auto status change tidak jalan

**Symptoms:**
- User reply tapi status tetap 'resolved'

**Check:**
```php
// Di TicketController::reply (line 146-148)
if ($ticket->status === 'resolved' && Auth::user()->role !== 'admin') {
    // This should fire
    dd('Should update status');
}
```

**Fix:**
- Pastikan logika kondisi benar
- Check user role
- Check current ticket status

---

## 7️⃣ Quick Test Script

Buat file: `tests/ManualTests/test-reply.php`

```php
<?php

require __DIR__.'/../../vendor/autoload.php';

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Test 1: Load ticket
$ticket = \App\Models\Ticket::where('status', 'open')->first();
if (!$ticket) {
    echo "❌ No open ticket found\n";
    exit(1);
}
echo "✅ Found ticket: {$ticket->ticket_number}\n";

// Test 2: Add reply
$service = app(\App\Services\TicketService::class);
$service->addThreadMessage($ticket, [
    'sender_type' => 'user',
    'sender_id' => $ticket->user_id,
    'sender_name' => $ticket->user_name,
    'message_type' => 'reply',
    'message' => 'Auto test reply at ' . now()
]);
echo "✅ Reply added\n";

// Test 3: Verify
$latestThread = $ticket->threads()->latest()->first();
echo "✅ Latest thread ID: {$latestThread->id}\n";
echo "✅ Message: {$latestThread->message}\n";

// Test 4: Count threads
$count = $ticket->threads()->count();
echo "✅ Total threads: {$count}\n";

echo "\n🎉 All tests passed!\n";
```

**Run:**
```bash
php tests/ManualTests/test-reply.php
```

---

## 8️⃣ Performance Testing

### Load Testing Reply Endpoint

```bash
# Install Apache Bench
brew install apache2  # macOS
apt-get install apache2-utils  # Linux

# Test 100 requests, 10 concurrent
ab -n 100 -c 10 -p reply-data.json -T application/json \
   -H "Cookie: laravel_session=xxx" \
   http://localhost:8000/tickets/1/reply
```

**Expected:**
- Response time < 200ms
- 0% failed requests
- Memory usage reasonable

---

## 9️⃣ Database Testing

### Check Thread Integrity

```sql
-- Check for orphaned threads
SELECT * FROM ticket_threads 
WHERE ticket_id NOT IN (SELECT id FROM tickets);

-- Check thread count per ticket
SELECT t.ticket_number, COUNT(tt.id) as thread_count
FROM tickets t
LEFT JOIN ticket_threads tt ON t.id = tt.ticket_id
GROUP BY t.id
ORDER BY thread_count DESC;

-- Check latest replies
SELECT t.ticket_number, tt.message, tt.created_at, tt.sender_name
FROM ticket_threads tt
JOIN tickets t ON tt.ticket_id = t.id
ORDER BY tt.created_at DESC
LIMIT 10;
```

---

## 🎯 Summary

**Quick Start Testing:**
```bash
# 1. Start server
php artisan serve

# 2. Open browser
open http://localhost:8000

# 3. Login & test manually
# Email: user@example.com
# Pass: password

# 4. Run automated tests
php artisan test --filter=reply

# 5. Check database
php artisan tinker
>>> \App\Models\Ticket::first()->threads()->get()
```

**Priority Test Cases:**
1. ✅ User dapat reply (happy path)
2. ✅ Admin dapat reply
3. ✅ Authorization (security)
4. ✅ File upload
5. ✅ Auto status change

---

## 📞 Need Help?

Jika ada error atau pertanyaan:
1. Check `storage/logs/laravel.log`
2. Gunakan `dd()` atau `dump()` untuk debug
3. Run `php artisan test` untuk automated check
4. Check browser console untuk JS errors

---

**Document Version:** 1.0  
**Last Updated:** Oktober 26, 2025  
**Author:** Helpdesk ITSO Team
