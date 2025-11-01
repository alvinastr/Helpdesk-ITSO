# Testing Guide - KPI System

## 📋 Daftar Isi
1. [Running Seeder](#running-seeder)
2. [Using Artisan Command](#using-artisan-command)
3. [Running Unit Tests](#running-unit-tests)
4. [Expected Results](#expected-results)

---

## 🌱 Running Seeder

### Generate Test Data
```bash
php artisan db:seed --class=KpiTestDataSeeder
```

**Output yang diharapkan:**
```
🎯 Generating KPI Test Data...
🧹 Cleaning old test data...

📊 Creating Scenario 1: Excellent Performance
  ✅ #TKT-20251029-0001 - Response: 15 min | Resolution: 1.5 hours

📊 Creating Scenario 2: Good Response, Delayed Resolution
  ⚠️  #TKT-20251029-0002 - Response: 15 min ✅ | Resolution: 72 hours ❌

📊 Creating Scenario 3: Slow Response
  ❌ #TKT-20251029-0003 - Response: 2 hours ❌ | Resolution: 2.8 hours ✅

📊 Creating Scenario 4: Severe Delay (Real Case)
  🔴 #TKT-20251029-0004 - Creation Delay: 94 hours | Response: 144 hours | Resolution: 192 hours

📊 Creating Scenario 5: WhatsApp Channel (No Email)
  💬 #TKT-20251029-0005 - Response: 5 min ✅ | Resolution: 60 min ✅

📊 Creating Scenario 6: Open Ticket (No Response)
  🕐 #TKT-20251029-0006 - Waiting for response (45 min elapsed) ⚠️

📊 Creating Scenario 7: In Progress
  🔄 #TKT-20251029-0007 - Response: 10 min ✅ | In progress for 24 hours

📊 Creating Scenario 8: Various Categories
  📁 #TKT-20251029-0008 - Category: Billing
  📁 #TKT-20251029-0009 - Category: General
  📁 #TKT-20251029-0010 - Category: Technical

============================================================
✅ KPI Test Data Generation Complete!
============================================================
+----------------------+-------+
| Metric               | Count |
+----------------------+-------+
| Total Tickets        | 10    |
| Resolved             | 8     |
| In Progress          | 2     |
| With Email Tracking  | 7     |
+----------------------+-------+

📊 View KPI Dashboard: http://localhost:8000/admin/kpi
🔍 Filter by '[TEST KPI]' to see only test data
```

**Skenario yang Dibuat:**

1. **Excellent Performance** ✅
   - Response: 15 menit (dalam SLA)
   - Resolution: 1.5 jam (dalam SLA)
   - Status: Resolved

2. **Good Response, Delayed Resolution** ⚠️
   - Response: 15 menit (dalam SLA)
   - Resolution: 72 jam (melebihi SLA 48 jam)
   - Status: Resolved

3. **Slow Response** ❌
   - Response: 2 jam (melebihi SLA 30 menit)
   - Resolution: 2.8 jam (dalam SLA)
   - Status: Resolved

4. **Severe Delay** 🔴 (Kasus Real User)
   - Email diterima: 21 Okt 13:00
   - Ticket dibuat: 25 Okt 10:00 (delay 94 jam)
   - Response: 25 Okt 10:15 (144 jam dari email)
   - Resolved: 27 Okt 10:00 (192 jam dari email)
   - Status: Resolved

5. **WhatsApp Channel** 💬
   - Tidak ada email_received_at
   - Response: 5 menit
   - Resolution: 60 menit
   - Status: Resolved

6. **Open Ticket** 🕐
   - Belum ada response (sudah 45 menit)
   - Status: Pending Review

7. **In Progress** 🔄
   - Response: 10 menit (dalam SLA)
   - Belum resolved (sudah 24 jam)
   - Status: In Progress

8. **Various Categories** 📁
   - Billing, General, Technical
   - Mix resolved & in progress

---

## 🔧 Using Artisan Command

### 1. Show KPI Summary
```bash
php artisan kpi:test summary
```

**Output:**
```
🎯 KPI Test Command - Action: SUMMARY
============================================================

📊 KPI SUMMARY

+-------------------------+---------------------------+
| Metric                  | Value                     |
+-------------------------+---------------------------+
| Total Tickets           | 10                        |
| Tickets with Response   | 8 (80%)                   |
| Tickets Resolved        | 8                         |
| Avg Response Time       | 25 menit (25 min)         |
| Avg Resolution Time     | 1 hari 12 jam (2160 min)  |
| Avg Creation Delay      | 20 jam (1200 min)         |
+-------------------------+---------------------------+

🎯 SLA COMPLIANCE

+------------------+----------------+------------+--------+
| Metric           | Target         | Compliance | Status |
+------------------+----------------+------------+--------+
| Response Time    | ≤ 30 minutes   | 75%        | ❌ FAIL|
| Resolution Time  | ≤ 48 hours     | 62%        | ❌ FAIL|
+------------------+----------------+------------+--------+

⚠️  TICKETS WITH SLA ISSUES (Top 5)

+-------------------+--------------------------------+----------+-------------------------+
| Ticket            | Subject                        | Priority | Issues                  |
+-------------------+--------------------------------+----------+-------------------------+
| TKT-20251029-0004 | [TEST KPI] Request akses da... | medium   | Response: 144 hours...  |
| TKT-20251029-0003 | [TEST KPI] Akun email tidak... | critical | Response: 2 hours       |
| TKT-20251029-0002 | [TEST KPI] Printer tidak bi... | medium   | Resolution: 72 hours    |
+-------------------+--------------------------------+----------+-------------------------+
```

### 2. Analyze KPI Trends
```bash
php artisan kpi:test analyze
```

**Output:**
```
📈 KPI ANALYSIS

BY PRIORITY:
+----------+-------+---------------+-----------------+------------------+
| Priority | Total | Avg Response  | Avg Resolution  | SLA Status       |
+----------+-------+---------------+-----------------+------------------+
| critical | 2     | 1 jam 5 menit | 1 hari 2 jam    | ⚠️  Attention    |
| high     | 2     | 15 menit ✅   | 12 jam ✅       | ✅ Good          |
| medium   | 4     | 2 jam ❌      | 2 hari ❌       | ⚠️  Attention    |
| low      | 2     | 10 menit ✅   | 2 jam ✅        | ✅ Good          |
+----------+-------+---------------+-----------------+------------------+

BY CATEGORY:
+-----------+-------+---------------+-----------------+
| Category  | Total | Avg Response  | Avg Resolution  |
+-----------+-------+---------------+-----------------+
| Technical | 6     | 25 menit      | 1 hari 8 jam    |
| Billing   | 2     | 30 menit      | 6 jam           |
| General   | 2     | 1 jam 30 men  | 3 hari          |
+-----------+-------+---------------+-----------------+

💡 RECOMMENDATIONS:

⚠️  Critical priority tickets have slow response time (1 jam 5 menit)
   → Consider implementing automated escalation for critical tickets

⚠️  3 tickets have creation delay > 1 hour
   → Train admin staff to create tickets immediately upon receiving emails

⚠️  2 tickets older than 30 minutes have no response yet
   → Review ticket assignment and notification system
```

### 3. Validate KPI Calculations
```bash
php artisan kpi:test validate
```

**Output (jika tidak ada masalah):**
```
🔍 VALIDATING KPI CALCULATIONS

✅ All KPI calculations are valid!
```

**Output (jika ada masalah):**
```
🔍 VALIDATING KPI CALCULATIONS

3 validation issues found:

+-------------------+--------------------------------+------------------------------------------+
| Ticket            | Issue                          | Details                                  |
+-------------------+--------------------------------+------------------------------------------+
| TKT-20251029-0008 | Missing response_time_minutes  | Has first_response_at but no calc time   |
| TKT-20251029-0009 | Invalid timestamps             | email_received_at is after created_at    |
+-------------------+--------------------------------+------------------------------------------+

Do you want to fix these issues? (yes/no) [no]:
```

### 4. Recalculate Single Ticket
```bash
php artisan kpi:test calculate --ticket=1
```

**Output:**
```
🔄 Recalculating KPI for Ticket #TKT-20251029-0001

BEFORE:
+-----------------------------+---------------------------+
| Field                       | Value                     |
+-----------------------------+---------------------------+
| Ticket Number               | TKT-20251029-0001         |
| Response Time               | 15 min (15 menit)         |
| Resolution Time             | 90 min (1 jam 30 menit)   |
| Response SLA                | ✅ Met                     |
| Resolution SLA              | ✅ Met                     |
+-----------------------------+---------------------------+

AFTER:
[Same as before if no changes needed]

✅ Recalculation complete!
```

### 5. Recalculate All Tickets
```bash
php artisan kpi:test calculate --all
```

**Output:**
```
 10/10 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

✅ Processed 10 tickets, updated 3 tickets.
```

---

## 🧪 Running Unit Tests

### Run All KPI Tests
```bash
php artisan test --filter=KpiCalculationTest
```

**Expected Output:**
```
   PASS  Tests\Unit\KpiCalculationTest
  ✓ ticket creation delay is calculated correctly
  ✓ response time calculated from email received at
  ✓ response time calculated from created at when no email
  ✓ resolution time calculated from email received at
  ✓ resolution time calculated from created at when no email
  ✓ response time within sla target
  ✓ resolution time within sla target
  ✓ response time formatted correctly
  ✓ kpi summary calculates correctly
  ✓ kpi by priority aggregates correctly
  ✓ kpi by category aggregates correctly
  ✓ filter by date range
  ✓ filter by category
  ✓ filter by priority
  ✓ kpi handles null values correctly
  ✓ sla boundary conditions

  Tests:  16 passed
  Time:   0.52s
```

### Run Specific Test
```bash
php artisan test --filter=test_response_time_within_sla_target
```

### Run with Detailed Output
```bash
php artisan test --filter=KpiCalculationTest --testdox
```

**Output:**
```
 KpiCalculation
 ✓ Ticket creation delay is calculated correctly
 ✓ Response time calculated from email received at
 ✓ Response time calculated from created at when no email
 ✓ Resolution time calculated from email received at
 ✓ Resolution time calculated from created at when no email
 ✓ Response time within sla target
 ✓ Resolution time within sla target
 ✓ Response time formatted correctly
 ✓ Kpi summary calculates correctly
 ✓ Kpi by priority aggregates correctly
 ✓ Kpi by category aggregates correctly
 ✓ Filter by date range
 ✓ Filter by category
 ✓ Filter by priority
 ✓ Kpi handles null values correctly
 ✓ Sla boundary conditions
```

### Run Tests with Coverage (if PHPUnit is configured)
```bash
php artisan test --filter=KpiCalculationTest --coverage
```

---

## 📊 Expected Results

### After Running Seeder

1. **Database:**
   - 10 test tickets created dengan prefix `[TEST KPI]`
   - Berbagai skenario: excellent, delayed, slow, severe
   - Mix status: resolved, in_progress, pending_review

2. **Dashboard KPI:**
   - Visit: `http://localhost:8000/admin/kpi`
   - Lihat summary cards dengan data test
   - Lihat chart trends
   - Lihat tabel dengan badge merah/hijau

3. **Detail Tickets:**
   - Buka salah satu ticket test
   - Lihat KPI Card dengan metrics lengkap
   - Lihat timeline events

### Test Coverage

**Unit tests mencakup:**
- ✅ Ticket creation delay calculation
- ✅ Response time calculation (from email & from created_at)
- ✅ Resolution time calculation (from email & from created_at)
- ✅ SLA compliance checking
- ✅ Formatted time display
- ✅ KPI summary aggregation
- ✅ Filter by date, category, priority
- ✅ Null value handling
- ✅ Edge cases (boundary conditions)

### Command Features

**Artisan command dapat:**
- 📊 Show comprehensive KPI summary
- 🔄 Recalculate KPI for single/all tickets
- ✅ Validate KPI calculations
- 📈 Analyze trends and provide recommendations
- 💡 Suggest improvements

---

## 🎯 Quick Testing Workflow

### Step 1: Generate Data
```bash
php artisan db:seed --class=KpiTestDataSeeder
```

### Step 2: Run Tests
```bash
php artisan test --filter=KpiCalculationTest
```

### Step 3: Check Summary
```bash
php artisan kpi:test summary
```

### Step 4: View Dashboard
```
Open: http://localhost:8000/admin/kpi
```

### Step 5: Analyze Results
```bash
php artisan kpi:test analyze
```

---

## 🐛 Troubleshooting

### Issue: Seeder fails with "User not found"
**Solution:**
```bash
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=KpiTestDataSeeder
```

### Issue: Tests fail with database error
**Solution:**
```bash
php artisan migrate:fresh
php artisan test --filter=KpiCalculationTest
```

### Issue: KPI not calculating
**Solution:**
```bash
php artisan kpi:test calculate --all
```

### Issue: Dashboard shows no data
**Solution:**
```bash
# Check if tickets exist
php artisan tinker
>>> Ticket::count()

# If zero, run seeder
>>> exit
php artisan db:seed --class=KpiTestDataSeeder
```

---

## 📝 Notes

- Test data menggunakan prefix `[TEST KPI]` untuk mudah dibedakan
- Seeder dapat dijalankan berulang kali (akan hapus data lama dulu)
- Command `kpi:test` aman dijalankan di production (read-only kecuali calculate)
- Unit tests menggunakan `RefreshDatabase` jadi tidak akan merusak data real

---

**Last Updated:** 29 Oktober 2025
