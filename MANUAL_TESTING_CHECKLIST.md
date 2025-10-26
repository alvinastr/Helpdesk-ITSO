# Manual Testing Checklist - Helpdesk ITSO
## Berdasarkan Flowchart Workflow

### ✅ PERSIAPAN TESTING

1. **Setup Environment**
   - [ ] Server Laravel berjalan di http://localhost:8000
   - [ ] Database sudah di-migrate dan di-seed
   - [ ] User admin dan user biasa sudah ada
   - [ ] Email/WhatsApp testing tools ready

2. **Login Credentials**
   ```
   Admin: admin@test.com / password
   User:  user@test.com / password
   ```

---

### 🔄 TESTING FLOW 1: NEW TICKET WORKFLOW

#### Step 1: INPUT DATA
- [ ] **Test via Web Form**
  - Login sebagai user
  - Buat ticket baru di `/tickets/create`
  - Isi data lengkap: subject, description, category, priority
  - Submit form

- [ ] **Test via Email Webhook**
  ```bash
  curl -X POST http://localhost:8000/api/v1/webhooks/email \
    -H "Content-Type: application/json" \
    -d '{
      "from": "test@example.com",
      "subject": "Test Email Ticket",
      "body": "Laptop saya tidak bisa menyala",
      "message_id": "email-test-001"
    }'
  ```

- [ ] **Test via WhatsApp Webhook**
  ```bash
  curl -X POST http://localhost:8000/api/v1/webhooks/whatsapp \
    -H "Content-Type: application/json" \
    -d '{
      "from": "+6281234567890",
      "message": "Help! Komputer error blue screen",
      "message_id": "wa-test-001"
    }'
  ```

#### Step 2: STANDARDISASI DATA
- [ ] Verifikasi ticket number ter-generate (format: TCK-YYYYMMDD-XXX)
- [ ] Verifikasi data ter-standardisasi (email lowercase, phone format, dll)
- [ ] Verifikasi channel tersimpan dengan benar (web/email/whatsapp)

#### Step 3: CEK DATA (EMAIL/WA)
- [ ] Test ticket baru → langsung ke Generate Ticket
- [ ] Test reply email → update thread existing ticket
- [ ] Test reply WA → update thread existing ticket

#### Step 4: GENERATE TICKET
- [ ] Verifikasi ticket tersimpan di database
- [ ] Status awal = 'pending_review'
- [ ] User dapat melihat ticket di dashboard

#### Step 5: VALIDASI SISTEM
- [ ] **Test Valid Data**
  - Subject tidak kosong
  - Description minimal 10 karakter
  - Email format valid
  - Phone format valid
  - → Lanjut ke Pending Review

- [ ] **Test Invalid Data**
  - Subject kosong → Error validation
  - Description terlalu pendek → Error validation
  - Email invalid → Error validation
  - → SET STATUS: REJECTED

#### Step 6: VALIDASI ADMIN
Login sebagai admin, buka `/admin/pending-review`

- [ ] **Test APPROVE**
  - Klik approve pada ticket
  - Verifikasi status berubah ke 'open'
  - Verifikasi approved_by dan approved_at tersimpan

- [ ] **Test REJECT**
  - Klik reject pada ticket
  - Isi rejection reason
  - Verifikasi status berubah ke 'rejected'
  - Verifikasi EMAIL/WA REJECT dikirim

- [ ] **Test REQUEST REVISION**
  - Klik request revision
  - Isi revision notes
  - Verifikasi status berubah ke 'pending_revision'
  - Verifikasi thread baru ter-create

---

### 🔄 TESTING FLOW 2: UPDATE THREAD WORKFLOW

#### User Reply
- [ ] Login sebagai user
- [ ] Buka ticket yang sudah open
- [ ] Reply dengan message baru
- [ ] Verifikasi thread ter-update, bukan ticket baru

#### Admin Response
- [ ] Login sebagai admin
- [ ] Buka ticket dari user
- [ ] Reply dengan response
- [ ] Verifikasi thread ter-update

---

### 🔄 TESTING FLOW 3: STATUS UPDATE WORKFLOW

#### Update Status Ticket
- [ ] **Open → In Progress**
  - Admin update status ke 'in_progress'
  - Verifikasi status tersimpan

- [ ] **In Progress → Closed**
  - Admin update status ke 'closed'
  - Isi resolution notes
  - Verifikasi closed_at timestamp

#### Issue Resolved
- [ ] Admin close ticket dengan resolution notes
- [ ] Verifikasi status = 'closed'
- [ ] Verifikasi resolution_notes tersimpan
- [ ] User dapat melihat ticket sudah closed

---

### 🔄 TESTING FLOW 4: REJECTION WORKFLOW

#### System Rejection
- [ ] Submit ticket dengan data invalid
- [ ] Verifikasi validation error muncul
- [ ] Verifikasi ticket tidak tersimpan

#### Admin Rejection
- [ ] Admin reject ticket dengan reason
- [ ] Verifikasi status = 'rejected'
- [ ] Verifikasi rejection_reason tersimpan
- [ ] Verifikasi email notification dikirim

---

### 📊 TESTING REPORTS & DASHBOARD

#### Admin Dashboard
- [ ] Statistik ticket per status
- [ ] Recent tickets list
- [ ] Pending review count

#### Reports
- [ ] Export Excel
- [ ] Export PDF
- [ ] Filter by date range
- [ ] Filter by status

---

### 🔍 TESTING ERROR HANDLING

#### Invalid Input
- [ ] Empty POST requests
- [ ] Malformed JSON
- [ ] Missing required fields
- [ ] Invalid field formats

#### Permission Tests
- [ ] Regular user access admin pages → 403
- [ ] Unauthenticated access → redirect login
- [ ] Invalid ticket ID → 404

---

### 📱 TESTING NOTIFICATIONS

#### Email Notifications
- [ ] Ticket received
- [ ] Ticket approved
- [ ] Ticket rejected
- [ ] Ticket updated
- [ ] Ticket closed

#### WhatsApp Notifications (if implemented)
- [ ] Similar to email notifications

---

### 📋 TESTING CHECKLIST SUMMARY

**Critical Paths:**
- [ ] New ticket creation → approval → closure
- [ ] New ticket creation → rejection
- [ ] Reply thread updates
- [ ] Email/WA webhook processing
- [ ] Admin workflow validation

**Edge Cases:**
- [ ] Duplicate message handling
- [ ] Long text handling
- [ ] Special characters in input
- [ ] Concurrent updates
- [ ] Missing user data

**Performance:**
- [ ] Response time < 2 seconds
- [ ] Bulk operations
- [ ] Database query optimization

---

### 🐛 BUG TRACKING

| Test Case | Status | Issue | Notes |
|-----------|--------|-------|-------|
| New ticket creation | ✅ | - | Working |
| Email webhook | ⚠️ | Timeout | Check config |
| Admin approval | ❌ | 500 Error | Fix controller |

---

### 📝 TESTING NOTES

**Environment:**
- PHP Version: 8.x
- Laravel Version: 11.x
- Database: MySQL/SQLite
- Testing Framework: PHPUnit

**Test Data:**
- Admin: admin@test.com
- User: user@test.com
- Test emails: test1@example.com, test2@example.com
- Test phones: +6281234567890, +6281234567891