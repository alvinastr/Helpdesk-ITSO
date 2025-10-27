# 🧪 MANUAL TESTING - TICKET INPUT DATA

## 📋 Overview
Dokumentasi ini berisi dummy data untuk testing manual sistem Helpdesk ITSO.
- **1 Data VALID** ✅ (Expected: Success)
- **4 Data INVALID** ❌ (Expected: Validation Failed/Error)

---

## 🚀 Cara Testing

### **Metode 1: Via Web Interface (Recommended)**
```bash
# 1. Akses HTML Test Page
http://localhost:8000/test-ticket-input.html

# 2. Klik tombol "Copy" pada test case
# 3. Paste ke form create ticket
# 4. Submit dan cek hasilnya
```

### **Metode 2: Via PHP Script**
```bash
# Run di terminal
php test-ticket-data.php

# Output akan menampilkan hasil testing semua case
```

### **Metode 3: Via API/Postman**
```bash
# Endpoint
POST http://localhost:8000/api/v1/tickets

# Headers
Content-Type: application/json
Accept: application/json

# Body: Copy JSON dari test case
```

---

## ✅ TEST 1: DATA VALID

### Expected Result
- ✅ Ticket berhasil dibuat
- ✅ Status = `pending_review`
- ✅ Validasi PASSED
- ✅ Data pelapor tersimpan lengkap

### Test Data
```json
{
    "reporter_nip": "198501012020",
    "reporter_name": "Budi Santoso",
    "reporter_email": "budi.santoso@company.com",
    "reporter_phone": "081234567890",
    "reporter_department": "IT Support",
    "subject": "Laptop tidak bisa connect ke WiFi",
    "description": "Laptop saya tiba-tiba tidak bisa connect ke WiFi kantor. Sudah coba restart laptop dan router tetapi tetap tidak bisa. Password sudah benar. Error message: \"Can't connect to this network\"",
    "category": "Technical",
    "priority": "high",
    "channel": "portal",
    "input_method": "manual"
}
```

### Validasi yang Dilalui
- ✅ NIP: ada & valid
- ✅ Nama: ada & tidak kosong
- ✅ Email: format valid (budi.santoso@company.com)
- ✅ Phone: 12 digit (081234567890 → 62812345678)
- ✅ Subject: 5+ karakter
- ✅ Description: 10+ karakter
- ✅ Tidak ada spam keywords

---

## ❌ TEST 2: EMAIL FORMAT SALAH

### Expected Result
- ❌ Validasi FAILED
- ❌ Error: "Format email tidak valid"

### Test Data
```json
{
    "reporter_nip": "198501012021",
    "reporter_name": "Siti Nurhaliza",
    "reporter_email": "siti.nurhaliza@invalid",
    "reporter_phone": "081234567891",
    "reporter_department": "Finance",
    "subject": "Request akses sistem",
    "description": "Mohon bantuan untuk akses ke sistem SAP karena saya tidak bisa login",
    "category": "Access Request",
    "priority": "medium",
    "channel": "email",
    "input_method": "email"
}
```

### Issue yang Dideteksi
- ❌ **Email: `siti.nurhaliza@invalid`**
  - Missing domain extension (.com, .co.id, .net, etc)
  - Tidak lolos `FILTER_VALIDATE_EMAIL`

---

## ❌ TEST 3: DATA TIDAK LENGKAP

### Expected Result
- ❌ Validasi FAILED
- ❌ Error: "Data tidak lengkap. Mohon lengkapi nama, email, subjek, dan deskripsi"

### Test Data
```json
{
    "reporter_nip": "198501012022",
    "reporter_name": "",
    "reporter_email": "john.doe@company.com",
    "reporter_phone": "081234567892",
    "reporter_department": "HR",
    "subject": "Test",
    "description": "Short",
    "category": "General",
    "priority": "low",
    "channel": "portal",
    "input_method": "manual"
}
```

### Issue yang Dideteksi
- ❌ **Nama: Kosong** (empty string)
- ❌ **Subject: "Test"** (< 5 karakter, minimum 5)
- ❌ **Description: "Short"** (< 10 karakter, minimum 10)

---

## ❌ TEST 4: NOMOR TELEPON INVALID

### Expected Result
- ❌ Validation FAILED atau Error saat create
- ❌ Error: "Nomor telepon minimal 10 karakter"

### Test Data
```json
{
    "reporter_nip": "198501012023",
    "reporter_name": "Ahmad Yani",
    "reporter_email": "ahmad.yani@company.com",
    "reporter_phone": "0812345",
    "reporter_department": "Operations",
    "subject": "Printer tidak berfungsi dengan baik",
    "description": "Printer di lantai 3 tidak bisa print berwarna, hanya bisa print hitam putih saja.",
    "category": "Hardware",
    "priority": "medium",
    "channel": "call",
    "input_method": "manual"
}
```

### Issue yang Dideteksi
- ❌ **Phone: "0812345"** (7 digit, minimum 10)
  - Validasi di `TicketRequest`: `min:10`

---

## ❌ TEST 5: SPAM PATTERN

### Expected Result
- ❌ Validasi FAILED
- ❌ Error: "Spam pattern detected"

### Test Data
```json
{
    "reporter_nip": "198501012024",
    "reporter_name": "Test User",
    "reporter_email": "test@test.com",
    "reporter_phone": "081234567894",
    "reporter_department": "Testing",
    "subject": "test test test",
    "description": "testing aaaa xxxx testing testing",
    "category": "General",
    "priority": "low",
    "channel": "portal",
    "input_method": "manual"
}
```

### Issue yang Dideteksi
- ❌ **Subject & Description contains spam keywords:**
  - `test` (multiple occurrences)
  - `aaaa` (repetitive pattern)
  - `xxxx` (repetitive pattern)
  - `testing` (spam keyword)

### Spam Keywords yang Terdeteksi
```php
$spamKeywords = ['test', 'testing', 'aaaa', 'xxxx'];
```

---

## 🔍 Cara Verifikasi Hasil Testing

### 1. Cek di Database
```sql
-- Login ke MySQL
mysql -u root -p

-- Pilih database
USE helpdesk_itso;

-- Lihat tickets terbaru
SELECT 
    id,
    ticket_number,
    reporter_name,
    reporter_email,
    subject,
    status,
    created_at
FROM tickets
ORDER BY id DESC
LIMIT 10;

-- Cek validation failures (jika ada log)
SELECT * FROM tickets WHERE status = 'rejected' ORDER BY id DESC;
```

### 2. Cek di Admin Dashboard
1. Login sebagai admin: http://localhost:8000/admin/login
2. Akses Dashboard: http://localhost:8000/admin/dashboard
3. Cek menu "Pending Review"
4. Verifikasi:
   - ✅ TEST 1 ada di list
   - ❌ TEST 2-5 tidak ada (karena validasi failed)

### 3. Cek di Terminal Output (jika pakai script PHP)
```bash
php test-ticket-data.php

# Output expected:
# ✅ TEST 1: SUCCESS - Ticket Created (TKT-20251027-XXXX)
# ❌ TEST 2: VALIDATION FAILED - Format email tidak valid
# ❌ TEST 3: VALIDATION FAILED - Data tidak lengkap
# ❌ TEST 4: ERROR - Nomor telepon minimal 10 karakter
# ❌ TEST 5: VALIDATION FAILED - Spam pattern detected
```

---

## 📊 Expected Results Summary

| Test Case | Expected Result | Key Validation |
|-----------|----------------|----------------|
| **TEST 1** | ✅ Success | All validations passed |
| **TEST 2** | ❌ Failed | Email format invalid |
| **TEST 3** | ❌ Failed | Incomplete data (name, subject, description) |
| **TEST 4** | ❌ Failed | Phone number too short (< 10 digits) |
| **TEST 5** | ❌ Failed | Spam keywords detected |

---

## 🎯 What to Check

### ✅ For VALID Data (TEST 1):
- [ ] Ticket created successfully
- [ ] Ticket number generated (TKT-YYYYMMDD-XXXX)
- [ ] Status = `pending_review`
- [ ] Reporter data saved correctly
- [ ] Phone normalized (081234567890 → 62812345678)
- [ ] Email lowercase (if uppercase provided)
- [ ] Thread message created (initial complaint)
- [ ] Email notification sent (optional, if configured)

### ❌ For INVALID Data (TEST 2-5):
- [ ] Validation error returned
- [ ] Appropriate error message displayed
- [ ] Ticket NOT created in database
- [ ] No notification sent
- [ ] Error logged (if logging enabled)

---

## 🛠️ Troubleshooting

### Issue: Script Error "Class Not Found"
```bash
# Solution: Run composer autoload
composer dump-autoload
```

### Issue: Database Connection Error
```bash
# Check .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpdesk_itso
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Issue: Validation Not Working
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Issue: Email Tidak Terkirim
```bash
# Check mail configuration di .env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
```

---

## 📝 Notes

### Phone Number Normalization
```
Input:  081234567890
Output: 62812345678

Input:  +6281234567890
Output: 62812345678

Input:  62812345678
Output: 62812345678
```

### Email Normalization
```
Input:  BUDI.SANTOSO@COMPANY.COM
Output: budi.santoso@company.com
```

### Status Flow
```
create → pending_review → [approve] → open → in_progress → resolved → closed
                       ↘ [reject] → rejected
                       ↘ [revisi] → pending_revision
```

---

## 🎓 Learning Points

### Validation Checks Performed:
1. **Data Completeness** - Nama, email, subject, description
2. **Email Format** - FILTER_VALIDATE_EMAIL
3. **Phone Format** - Min 10 digits
4. **Subject Length** - Min 5 characters
5. **Description Length** - Min 10 characters
6. **Duplicate Detection** - Same email + similar content in 48 hours
7. **Spam Detection** - Keywords: test, testing, aaaa, xxxx
8. **Required Fields** - Email OR Phone (at least one)

### Files Involved:
- `app/Http/Requests/TicketRequest.php` - Form validation rules
- `app/Services/ValidationService.php` - Business logic validation
- `app/Services/TicketService.php` - Data processing
- `app/Models/Ticket.php` - Database model

---

## ✅ Checklist Setelah Testing

- [ ] TEST 1 (Valid) berhasil create ticket
- [ ] TEST 2 (Email) gagal validasi dengan pesan error yang benar
- [ ] TEST 3 (Incomplete) gagal validasi dengan pesan error yang benar
- [ ] TEST 4 (Phone) gagal validasi dengan pesan error yang benar
- [ ] TEST 5 (Spam) terdeteksi sebagai spam
- [ ] Data pelapor tampil dengan benar di admin dashboard
- [ ] Ticket number ter-generate dengan format yang benar
- [ ] Status history tercatat
- [ ] Thread message tercatat

---

**Happy Testing! 🚀**
