# 📘 Panduan User - Sistem Helpdesk ITSO

## Daftar Isi
1. [Gambaran Umum Sistem](#gambaran-umum-sistem)
2. [Alur Kerja Sistem (Sesuai Flowchart)](#alur-kerja-sistem)
3. [Panduan untuk User Biasa](#panduan-untuk-user-biasa)
4. [Panduan untuk Admin](#panduan-untuk-admin)
5. [Status Ticket dan Artinya](#status-ticket-dan-artinya)
6. [Notifikasi yang Diterima](#notifikasi-yang-diterima)
7. [FAQ](#faq)

---

## Gambaran Umum Sistem

Sistem Helpdesk ITSO adalah aplikasi manajemen tiket yang dirancang untuk mengelola laporan masalah/keluhan dari user dengan alur kerja yang terstruktur. Sistem ini memiliki beberapa fitur utama:

- ✅ **Pembuatan Tiket** - User dapat membuat laporan masalah
- 🔍 **Validasi Otomatis** - Sistem memvalidasi kelengkapan data
- 👨‍💼 **Review Admin** - Admin mereview dan menyetujui tiket
- 📊 **Tracking Status** - User dapat melacak progress tiket mereka
- 📧 **Notifikasi Otomatis** - Email & WhatsApp untuk setiap update
- 📈 **Dashboard & Laporan** - Monitoring performa helpdesk

---

## Alur Kerja Sistem (Sesuai Flowchart)

### 🎯 **FASE 1: PEMBUATAN TIKET**

#### Step 1: Start - User Report
**Penjelasan:**
- User memulai dengan melaporkan masalah/keluhan
- Bisa dilakukan melalui:
  - Portal web (login terlebih dahulu)
  - Email (diteruskan ke sistem)
  - WhatsApp (diinput manual oleh admin)
  - Telepon (diinput manual oleh admin)

**Cara User Melakukan:**
```
1. Login ke sistem helpdesk
2. Klik tombol "Create Ticket" atau "Buat Laporan"
3. Akan masuk ke form input data
```

---

#### Step 2: Input Data
**Penjelasan:**
User mengisi formulir dengan informasi lengkap tentang masalah mereka.

**Data yang Harus Diisi:**
- **Subject/Judul** - Ringkasan singkat masalah (contoh: "Email tidak bisa login")
- **Description/Deskripsi** - Penjelasan detail masalah yang dialami
- **Priority/Prioritas** - Tingkat urgensi:
  - 🟢 **Low** - Tidak mendesak, tidak mengganggu pekerjaan
  - 🟡 **Medium** - Perlu ditangani, mengganggu tapi ada workaround
  - 🟠 **High** - Mengganggu pekerjaan, perlu segera ditangani
  - 🔴 **Critical** - Sistem down/tidak bisa bekerja sama sekali
- **Channel** - Dari mana laporan ini (Portal/Email/WhatsApp/Call)
- **Attachment** (opsional) - Screenshot atau dokumen pendukung

**Tips untuk User:**
```
✅ DO (Lakukan):
- Jelaskan masalah sejelas mungkin
- Sertakan langkah-langkah yang sudah dicoba
- Upload screenshot jika ada error message
- Pilih prioritas sesuai dampak ke pekerjaan

❌ DON'T (Jangan):
- Menulis "tolong bantu" saja tanpa detail
- Upload file yang tidak relevan
- Memilih prioritas "Critical" untuk masalah kecil
```

---

#### Step 3: Standarisasi Data
**Penjelasan:**
Sistem otomatis memproses dan membersihkan data yang diinput.

**Proses Otomatis yang Terjadi:**
- Generate **Ticket Number** unik (format: TKT-YYYYMMDD-XXXX)
  - Contoh: TKT-20251026-0001
- Standarisasi format email dan nomor telepon
- Menghapus spasi berlebih
- Validasi format data

**User tidak perlu melakukan apa-apa di step ini** - semua otomatis!

---

#### Step 4: Cek Data (Email/WA) - Decision Point 🔴

**Penjelasan:**
Sistem mengecek apakah data input adalah **REPLY** (balasan dari tiket lama) atau **NEW** (tiket baru).

**Dua Kemungkinan:**

**A. Jika REPLY ke Thread Lama:**
```
Sistem akan:
✓ Mendeteksi ID tiket dari subject email/pesan
✓ Menambahkan pesan sebagai balasan di thread yang ada
✓ Update status tiket jika perlu
✓ TIDAK membuat tiket baru
✓ Kembali ke monitoring tiket yang sudah ada
```

**B. Jika NEW (Tiket Baru):**
```
Sistem akan:
✓ Lanjut ke proses generate tiket baru
✓ Membuat entry baru di database
✓ Lanjut ke validasi sistem
```

**Indikator untuk Sistem:**
- Cek subject line ada kode tiket (TKT-XXXXXXXX-XXXX)
- Cek reference ID di email header
- Jika tidak ada → Buat tiket baru

---

### 🎯 **FASE 2: VALIDASI SISTEM**

#### Step 5: Generate Tiket
**Penjelasan:**
Sistem membuat tiket baru di database dengan semua informasi yang sudah distandarisasi.

**Yang Terjadi:**
- Tiket disimpan dengan status: `pending_review`
- Timestamp dicatat
- User menerima notifikasi pertama
- Tiket muncul di dashboard user

**Notifikasi ke User:**
```
📧 Email: "Tiket Anda Berhasil Dibuat"
Isi:
- Nomor tiket: TKT-20251026-0001
- Subject: [subject yang diinput]
- Status: Menunggu Review
- Estimasi: Akan direview dalam 1-2 jam kerja
- Link: [URL ke detail tiket]
```

---

#### Step 6: Validasi Sistem - Decision Point 🔴

**Penjelasan:**
Sistem melakukan validasi otomatis untuk memastikan data lengkap dan valid.

**Kriteria Validasi:**

**1. Kelengkapan Data:**
- Subject tidak kosong (minimal 5 karakter)
- Description tidak kosong (minimal 20 karakter)
- Email valid (format xxx@yyy.zzz)
- Nomor HP valid (jika diisi)

**2. Format Email:**
- Harus format email yang benar
- Domain email harus valid

**3. Format No HP:**
- Dimulai dengan +62 atau 08
- Minimal 10 digit
- Hanya angka

**Dua Kemungkinan:**

**A. ❌ VALIDASI GAGAL → SET STATUS: REJECTED**

Jika ada yang tidak valid:

```
Status Tiket: REJECTED
Yang Terjadi:
1. Status otomatis berubah ke "rejected"
2. Reason dicatat di sistem
3. Kirim EMAIL/WA REJECT ke user

Contoh Alasan Reject:
- "Email format tidak valid"
- "Nomor HP tidak valid"
- "Deskripsi terlalu pendek (minimal 20 karakter)"
- "Subject tidak boleh kosong"
```

**Email/WA yang Diterima User:**
```
🚫 TICKET REJECTED

Halo [Nama User],

Maaf, tiket Anda tidak dapat diproses karena:
[Alasan reject]

Silakan buat tiket baru dengan data yang benar.

Tiket ID: TKT-20251026-0001
```

**Apa yang Harus User Lakukan:**
```
1. Baca alasan reject dengan teliti
2. Perbaiki data yang salah
3. Buat tiket baru dengan data yang benar
4. Pastikan semua field diisi dengan lengkap
```

---

**B. ✅ VALIDASI BERHASIL → SET STATUS: PENDING REVIEW**

Jika semua validasi lolos:

```
Status Tiket: PENDING_REVIEW
Yang Terjadi:
1. Status set ke "pending_review"
2. Tiket masuk antrian review admin
3. Auto-categorize berdasarkan keyword
4. Kirim notifikasi ke user bahwa tiket sedang direview

Kategori Otomatis:
- Technical: kata kunci "error", "bug", "crash", "tidak bisa", "gagal"
- Billing: kata kunci "tagihan", "pembayaran", "invoice", "bayar"
- General: selain di atas
```

**Notifikasi ke User:**
```
📩 TICKET RECEIVED

Halo [Nama User],

Tiket Anda telah diterima dan sedang menunggu review:

Ticket ID: TKT-20251026-0001
Subject: [subject]
Status: PENDING REVIEW
Kategori: [Technical/Billing/General]
Priority: [priority]

Tim kami akan mereview dalam 1-2 jam kerja.
Anda akan diupdate via email & WhatsApp.
```

---

### 🎯 **FASE 3: VALIDASI ADMIN**

#### Step 7: Validasi Admin - Decision Point 🔴

**Penjelasan:**
Admin mereview tiket secara manual untuk memastikan masalah bisa ditangani.

**Tampilan untuk Admin:**
Dashboard admin menampilkan:
- Daftar tiket dengan status "Pending Review"
- Diurutkan berdasarkan priority (Critical → High → Medium → Low)
- Info lengkap: User, Subject, Description, Priority, Waktu

**Admin Punya 3 Pilihan:**

---

##### **Pilihan 1: ✅ APPROVE (Setujui)**

**Kapan Admin Approve:**
- Data lengkap dan jelas
- Masalah valid dan bisa ditangani
- Tidak perlu klarifikasi tambahan
- Dalam scope layanan helpdesk

**Proses:**
```
1. Admin klik tombol "Approve"
2. [Opsional] Admin bisa assign ke staff tertentu
3. Status berubah: PENDING_REVIEW → OPEN
4. Kirim EMAIL/WA NOTIFY ke user
```

**Notifikasi ke User:**
```
✅ TICKET APPROVED

Halo [Nama User],

Kabar baik! Tiket Anda telah disetujui dan sedang ditangani.

Ticket ID: TKT-20251026-0001
Subject: [subject]
Status: OPEN
Category: [kategori]
Priority: [priority]
Handler: [nama staff yang ditunjuk, jika ada]

Estimasi penyelesaian: 2-3 hari kerja (tergantung kompleksitas)

Anda akan mendapat update saat ada perkembangan.

[Tombol: View Ticket]
```

**Yang Bisa User Lakukan Setelah Approve:**
- Melihat detail tiket di portal
- Menambahkan informasi tambahan via reply
- Memantau progress
- Mengupload file tambahan jika diperlukan

---

##### **Pilihan 2: 🔄 REQUEST REVISI DATA**

**Kapan Admin Request Revisi:**
- Deskripsi kurang jelas/detail
- Perlu informasi tambahan
- Perlu screenshot/dokumen pendukung
- Perlu klarifikasi masalah

**Proses:**
```
1. Admin klik "Request Revision"
2. Admin menulis pesan apa yang perlu dilengkapi
3. Status berubah: PENDING_REVIEW → PENDING_REVISION
4. Kirim notifikasi ke user
```

**Email/WA ke User:**
```
🔄 REVISION NEEDED

Halo [Nama User],

Tiket Anda perlu dilengkapi informasi:

Ticket ID: TKT-20251026-0001
Subject: [subject]

Pesan dari Admin:
"[Contoh: Mohon sertakan screenshot error message dan jelaskan 
kapan error ini mulai terjadi. Apakah ada perubahan sistem 
sebelumnya?]"

Silakan reply dengan informasi yang diminta.

[Tombol: Reply to Ticket]
```

**Apa yang Harus User Lakukan:**
```
1. Baca pesan dari admin dengan teliti
2. Siapkan informasi/dokumen yang diminta
3. Buka halaman detail tiket
4. Reply dengan informasi lengkap
5. Upload file jika diperlukan
6. Submit reply
```

**Setelah User Reply:**
```
- Status berubah: PENDING_REVISION → PENDING_REVIEW
- Tiket kembali masuk antrian review admin
- Admin akan review lagi
- Proses validasi admin diulang
```

---

##### **Pilihan 3: ❌ REJECT (Tolak)**

**Kapan Admin Reject:**
- Bukan masalah IT/di luar scope helpdesk
- Masalah sudah resolved/duplicate
- Data tidak lengkap dan user tidak responsif
- Permintaan tidak sesuai policy

**Proses:**
```
1. Admin klik "Reject"
2. Admin wajib isi alasan reject (minimal 10 karakter)
3. Status berubah: PENDING_REVIEW → REJECTED
4. Kirim EMAIL/WA NOTIFY ke user
```

**Email/WA ke User:**
```
🚫 TICKET REJECTED

Halo [Nama User],

Maaf, tiket Anda tidak dapat diproses.

Ticket ID: TKT-20251026-0001
Subject: [subject]
Status: REJECTED

Alasan:
"[Contoh: Masalah ini terkait dengan aplikasi pihak ketiga. 
Mohon hubungi vendor aplikasi untuk support lebih lanjut. 
Untuk masalah IT internal, silakan buat tiket baru.]"

Jika Anda merasa ini adalah kesalahan, silakan hubungi 
admin helpdesk atau buat tiket baru dengan informasi yang 
lebih lengkap.
```

**Apa yang Bisa User Lakukan:**
```
1. Baca alasan reject
2. Jika tidak setuju, hubungi admin via email/telepon
3. Jika setuju/paham, perbaiki dan buat tiket baru
4. Untuk masalah di luar scope, hubungi support yang tepat
```

---

### 🎯 **FASE 4: PENANGANAN TIKET**

#### Step 8: SET STATUS: OPEN

**Penjelasan:**
Setelah admin approve, tiket masuk tahap penanganan aktif.

**Status:** `OPEN`

**Yang Terjadi:**
- Tiket sudah di-approve
- Staff/handler mulai menganalisa masalah
- User bisa berkomunikasi via thread
- Status history tercatat

**Tampilan untuk User:**
```
Status: OPEN
Handler: [Nama Staff]
Last Update: [Timestamp]
Thread/Conversation: [Tersedia untuk reply]
```

---

#### Step 9: UPDATE THREAD

**Penjelasan:**
Komunikasi dua arah antara user dan handler terjadi di sini.

**Yang Bisa Dilakukan:**

**Oleh Handler/Admin:**
- Meminta informasi tambahan
- Memberikan solusi sementara
- Update progress penanganan
- Upload dokumentasi
- Memberikan instruksi troubleshooting

**Oleh User:**
- Menjawab pertanyaan handler
- Memberikan update hasil troubleshooting
- Upload screenshot/dokumen
- Mengkonfirmasi masalah

**Format Thread:**
```
[DD/MM/YYYY HH:mm] - Handler: "Kami sudah identifikasi masalahnya..."
[DD/MM/YYYY HH:mm] - User: "Saya sudah coba langkah yang diberikan..."
[DD/MM/YYYY HH:mm] - Handler: "Silakan coba restart aplikasi..."
[DD/MM/YYYY HH:mm] - User: "Sudah berhasil, terima kasih!"
```

**Notifikasi:**
- Setiap ada reply, pihak lain mendapat notifikasi email/WA
- User bisa set preference notifikasi di profile

---

### 🎯 **FASE 5: UPDATE STATUS PROGRESS**

#### Step 10: Update Status Ticket (OPEN → IN_PROGRESS → CLOSED)

**Penjelasan:**
Handler mengupdate status berdasarkan progress penanganan.

**Status yang Tersedia:**

##### **A. Status: IN_PROGRESS** 🔄
```
Arti: Masalah sedang dikerjakan aktif

Kapan Digunakan:
- Handler sudah mulai troubleshooting
- Sedang menunggu akses/approval
- Memerlukan koordinasi dengan pihak lain
- Problem solving sedang berlangsung

Notifikasi ke User:
"⚙️ UPDATE: Tiket Anda sedang ditangani
Handler sedang aktif menyelesaikan masalah Anda.
Estimasi: [waktu estimasi]"
```

##### **B. Status: RESOLVED** ✅
```
Arti: Masalah sudah diselesaikan, menunggu konfirmasi user

Kapan Digunakan:
- Handler sudah menyelesaikan masalah
- Solusi sudah diimplementasikan
- Menunggu konfirmasi dari user
- User perlu test/verifikasi

Notifikasi ke User:
"✅ TICKET RESOLVED
Masalah Anda telah diselesaikan.
Mohon konfirmasi apakah sudah berfungsi dengan baik.
Silakan reply jika masih ada masalah.
Tiket akan otomatis closed dalam 3 hari tanpa respon."
```

**Apa yang Harus User Lakukan:**
```
1. Buka detail tiket
2. Baca solusi yang diberikan
3. Test apakah masalah sudah teratasi
4. Reply dengan konfirmasi:
   - Jika OK: "Sudah berfungsi normal, terima kasih!"
   - Jika masih error: "Masih ada masalah: [jelaskan]"
```

**Jika User Reply "Masih Ada Masalah":**
```
- Status otomatis kembali ke IN_PROGRESS
- Handler akan review lagi
- Proses troubleshooting dilanjutkan
```

---

#### Step 11: Issue Resolved? - Decision Point 🔴

**Penjelasan:**
Checkpoint untuk memastikan masalah benar-benar selesai.

**Dua Kemungkinan:**

##### **A. ✅ YES - Issue Resolved → SET STATUS: CLOSED + NOTES**

**Kapan Masuk Sini:**
- User konfirmasi masalah selesai
- User tidak respon dalam 3 hari (auto-close)
- Handler verify solusi berhasil

**Proses Close Ticket:**
```
1. Admin/Handler klik "Close Ticket"
2. WAJIB isi "Resolution Notes" (minimal 20 karakter)
3. Status berubah: RESOLVED → CLOSED
4. Timestamp closed_at tercatat
5. Kirim notifikasi ke user
```

**Resolution Notes (Contoh):**
```
"Masalah login email sudah resolved dengan:
1. Reset password user
2. Clear cache browser
3. Update email client ke versi terbaru
Sudah dikonfirmasi user email berfungsi normal.
Case closed."
```

**Email/WA ke User:**
```
🎉 TICKET CLOSED

Halo [Nama User],

Tiket Anda telah diselesaikan dan ditutup.

Ticket ID: TKT-20251026-0001
Subject: [subject]
Status: CLOSED
Closed Date: [timestamp]

Resolution:
"[isi resolution notes]"

Rating & Feedback:
Bagaimana pengalaman Anda dengan layanan kami?
[Link Survey] - Opsional tapi sangat membantu!

Jika ada masalah serupa, silakan buat tiket baru.

Terima kasih telah menggunakan Helpdesk ITSO!
```

**Setelah Ticket Closed:**
- Tiket tidak bisa diubah lagi
- Thread masih bisa dibaca
- Data masuk ke laporan/analisa
- User bisa buat tiket baru jika ada masalah lain

---

##### **B. ❌ NO - Issue Not Resolved → LOOP BACK**

**Kapan Terjadi:**
- User konfirmasi masalah belum selesai
- Solusi tidak berhasil
- Muncul masalah baru terkait
- Perlu troubleshooting tambahan

**Proses:**
```
1. User reply di thread: "Masih ada masalah..."
2. Status auto-update: RESOLVED → IN_PROGRESS
3. Handler notified
4. Loop kembali ke "UPDATE THREAD"
5. Proses troubleshooting dilanjutkan
```

**Siklus Loop:**
```
UPDATE THREAD → UPDATE STATUS → IN_PROGRESS → RESOLVED 
→ Issue Resolved? NO → Kembali ke UPDATE THREAD
```

**Tips untuk User:**
```
Jika masalah belum selesai, jelaskan:
✓ Apa yang sudah dicoba
✓ Hasil dari troubleshooting
✓ Error message yang masih muncul
✓ Screenshot jika perlu
✓ Kapan masalah terjadi lagi
```

---

### 🎯 **FASE 6: PENUTUP**

#### Step 12: END - Generate Report & Dashboard

**Penjelasan:**
Sistem mengumpulkan semua data untuk reporting dan analisa.

**Report yang Dihasilkan:**

**1. Dashboard Admin:**
```
Statistik Real-time:
- Total tickets: [angka]
- Pending Review: [angka]
- Open/In Progress: [angka]
- Resolved Today: [angka]
- Closed Today: [angka]
- Average Response Time: [waktu]
- Average Resolution Time: [waktu]

Grafik:
- Ticket by Category (pie chart)
- Ticket by Priority (bar chart)
- Ticket by Status (donut chart)
- Daily/Weekly/Monthly trend (line chart)
```

**2. Dashboard User:**
```
My Tickets:
- Active Tickets: [daftar dengan status]
- Closed Tickets: [history]
- Filter by: Status, Date, Priority

Quick Stats:
- Total my tickets: [angka]
- Currently open: [angka]
- Average resolution time: [waktu]
```

**3. Report yang Bisa Di-Export:**
```
- Ticket Summary Report
- Performance Report
- SLA Compliance Report
- User Activity Report
- Category Analysis Report

Format: PDF, Excel, CSV
Period: Daily, Weekly, Monthly, Custom
```

**Akses Report:**
- Admin: Full access ke semua report
- User: Hanya report tiket mereka sendiri

---

## Status Ticket dan Artinya

### 📊 Lifecycle Status Ticket

| Status | Icon | Arti | Action User | Action Admin |
|--------|------|------|-------------|--------------|
| **pending_review** | ⏳ | Tiket baru masuk, menunggu review admin | Tunggu review | Review & approve/reject/revisi |
| **pending_revision** | 🔄 | Admin minta data tambahan | Lengkapi data & reply | Tunggu user reply |
| **rejected** | ❌ | Tiket ditolak admin | Baca alasan, buat tiket baru jika perlu | - |
| **open** | 🟢 | Tiket approved, siap ditangani | Tunggu/berikan info jika diminta | Assign handler, mulai analisa |
| **in_progress** | ⚙️ | Sedang dikerjakan | Respon jika ada pertanyaan | Troubleshoot & update progress |
| **resolved** | ✅ | Sudah diselesaikan, menunggu konfirmasi | Konfirmasi OK/NOT OK | Tunggu feedback |
| **closed** | 🔒 | Selesai dan ditutup | - (readonly) | - |

---

## Notifikasi yang Diterima

### 📧 Email Notifications

**User Menerima Email Saat:**
1. ✅ Tiket berhasil dibuat (Ticket Received)
2. ✅ Tiket di-approve (Ticket Approved)
3. ❌ Tiket di-reject (Ticket Rejected)
4. 🔄 Admin minta revisi (Revision Requested)
5. 💬 Ada reply baru dari handler (Ticket Update)
6. ⚙️ Status berubah (Status Update)
7. ✅ Tiket resolved (Ticket Resolved)
8. 🔒 Tiket closed (Ticket Closed)

**Admin Menerima Email Saat:**
1. 📥 Tiket baru masuk (New Ticket)
2. 💬 User reply/update tiket (Ticket Reply)
3. ⚠️ Tiket priority tinggi masuk (High Priority Alert)
4. 🔄 User update tiket yang pending revision (Revision Completed)

### 📱 WhatsApp Notifications

**User Menerima WA untuk:**
- Tiket penting (Priority: High/Critical)
- Status change penting (Approved, Rejected, Resolved, Closed)
- Reply dari handler yang memerlukan action

**Format WA Message:**
```
🎫 HELPDESK ITSO

[Status Icon] [Status Name]

Halo [Name],

[Message content]

Ticket ID: [TKT-XXX]
Status: [Status]

[Link jika perlu]
```

---

## FAQ

### ❓ Pertanyaan Umum User

**Q: Berapa lama tiket saya akan direspon?**
```
A: 
- Review pertama: 1-2 jam kerja
- Resolusi: 2-3 hari kerja (tergantung complexity)
- Urgent/Critical: Prioritas tertinggi, bisa lebih cepat
```

**Q: Kenapa tiket saya di-reject?**
```
A: Beberapa alasan umum:
- Data tidak lengkap
- Bukan masalah IT/di luar scope
- Duplikat tiket yang sudah ada
- Masalah sudah resolved sebelumnya
Baca email reject untuk detail, lalu perbaiki dan submit lagi.
```

**Q: Bagaimana cara update informasi di tiket?**
```
A:
1. Buka detail tiket
2. Scroll ke bagian bawah (Thread/Conversation)
3. Tulis pesan di text box
4. Upload file jika perlu
5. Klik "Send Reply"
```

**Q: Bisakah saya cancel/delete tiket?**
```
A: Tidak. Tapi Anda bisa:
- Reply dengan "Tolong cancel, sudah resolved"
- Admin akan close dengan notes
- Semua tiket tetap tercatat untuk tracking
```

**Q: Saya tidak terima notifikasi email/WA?**
```
A:
1. Cek spam/junk folder email
2. Verifikasi nomor WA Anda di profile
3. Cek setting notifikasi di profile
4. Hubungi admin jika masih bermasalah
```

**Q: Bagaimana cara melihat history tiket saya?**
```
A:
1. Login ke portal
2. Menu "My Tickets"
3. Filter by status "Closed" untuk lihat history
4. Klik tiket untuk lihat detail lengkap
```

---

### ❓ Pertanyaan Umum Admin

**Q: Apa kriteria untuk approve/reject tiket?**
```
A: 
APPROVE jika:
✓ Data lengkap dan jelas
✓ Masalah valid dan dalam scope
✓ Bisa ditangani oleh tim
✓ Tidak duplikat

REJECT jika:
✗ Di luar scope layanan helpdesk
✗ Duplikat tiket existing
✗ Spam atau tidak jelas
✗ User tidak responsif setelah request revisi

REQUEST REVISION jika:
? Perlu klarifikasi detail
? Butuh screenshot/dokumen tambahan
? Deskripsi kurang lengkap
```

**Q: Bagaimana assign tiket ke handler?**
```
A:
1. Buka detail tiket
2. Klik "Assign"
3. Pilih staff dari dropdown
4. Staff akan dapat notifikasi
5. Tiket muncul di dashboard staff
```

**Q: Apa yang harus diisi di Resolution Notes?**
```
A: Minimal harus ada:
- Root cause masalah
- Langkah-langkah yang dilakukan
- Hasil akhir
- Rekomendasi (jika ada)

Contoh:
"Masalah disebabkan outdated driver. 
Sudah update driver versi 3.2.1.
Test login berhasil. User diminta restart 
sistem setiap update Windows."
```

**Q: Kapan sebaiknya close tiket?**
```
A: Close jika:
✓ User konfirmasi masalah selesai
✓ User tidak respon 3 hari setelah resolved
✓ Solusi sudah diverifikasi berhasil
✓ Tidak ada follow-up yang dibutuhkan
```

**Q: Bagaimana handle tiket priority Critical?**
```
A:
1. Langsung review (< 30 menit)
2. Langsung approve jika valid
3. Assign ke senior staff/specialist
4. Update progress setiap 2-4 jam
5. Koordinasi dengan user real-time
6. Dokumentasi lengkap untuk future reference
```

---

## Tips Best Practices

### 👤 Untuk User:

```
✅ DO:
1. Jelaskan masalah sejelas dan sedetail mungkin
2. Sertakan screenshot jika ada error
3. Pilih priority sesuai impact ke pekerjaan
4. Respon cepat jika diminta informasi tambahan
5. Konfirmasi jika masalah sudah resolved
6. Berikan feedback di survey (membantu kami improve)

❌ DON'T:
1. Buat multiple tiket untuk masalah yang sama
2. Pilih priority Critical untuk masalah kecil
3. Gunakan bahasa yang tidak sopan
4. Upload file yang tidak relevan/terlalu besar
5. Reply dengan "ok" atau "thanks" saja tanpa konfirmasi
6. Ignore permintaan informasi tambahan dari handler
```

### 👨‍💼 Untuk Admin:

```
✅ DO:
1. Review tiket dalam 1-2 jam kerja (working hours)
2. Prioritaskan berdasarkan urgency yang sebenarnya
3. Berikan feedback yang clear dan actionable
4. Update status secara berkala
5. Dokumentasi lengkap di resolution notes
6. Follow-up tiket yang pending lama
7. Analisa pattern masalah untuk preventive action

❌ DON'T:
1. Reject tiket tanpa alasan yang jelas
2. Let tiket pending review terlalu lama
3. Assign tiket tanpa briefing ke handler
4. Close tiket tanpa konfirmasi user
5. Abaikan tiket priority tinggi
6. Lupa update status progress
7. Resolution notes terlalu singkat
```

---

## Kontak Support

Jika Anda memiliki pertanyaan atau mengalami kesulitan:

**Email:** helpdesk@itso.com  
**WhatsApp:** +62-XXX-XXXX-XXXX  
**Portal:** https://helpdesk.itso.com  
**Jam Operasional:** Senin-Jumat, 08:00-17:00 WIB

---

## Changelog

**v1.0 - Oktober 2025**
- Initial release documentation
- Panduan lengkap sesuai flowchart
- FAQ dan troubleshooting guide

---

*Dokumen ini dibuat untuk membantu user memahami cara kerja sistem Helpdesk ITSO. Untuk pertanyaan lebih lanjut, silakan hubungi admin helpdesk.*

**© 2025 Helpdesk ITSO - All Rights Reserved**
