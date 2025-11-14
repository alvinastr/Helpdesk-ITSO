# 🔧 Migration Error Fix Guide

## Error yang Terjadi

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'rating' in 'tickets'
```

**Migration yang error:** `2025_11_11_101709_add_kpi_tracking_fields_to_tickets_table.php`

---

## 🔍 Penyebab

Migration mencoba menambahkan kolom `satisfaction_rating` **setelah** kolom `rating`:
```php
$table->integer('satisfaction_rating')->nullable()->after('rating')
```

**Masalah:** Kolom `rating` sudah dihapus oleh migration sebelumnya:
- Migration `2025_10_15_083514_remove_rating_feedback_from_tickets_table.php` sudah menghapus kolom `rating`
- Migration yang error dibuat **setelah** penghapusan, tapi referensi kolom yang sudah tidak ada

---

## ✅ Solusi (Sudah Diterapkan)

**Fixed:** Changed `after('rating')` to `after('sla_deadline')`

```php
// BEFORE (ERROR):
$table->integer('satisfaction_rating')->nullable()->after('rating')->comment('1-5 scale');

// AFTER (FIXED):
$table->integer('satisfaction_rating')->nullable()->after('sla_deadline')->comment('1-5 scale');
```

---

## 🚀 Cara Menjalankan Migration

### Jika Database Masih Fresh/Kosong:
```bash
# Reset dan run semua migrations dari awal
php artisan migrate:fresh

# Atau dengan seeder
php artisan migrate:fresh --seed
```

### Jika Database Sudah Ada Data:

#### Option 1: Rollback Migration yang Error
```bash
# Rollback hanya migration terakhir yang error
php artisan migrate:rollback --step=1

# Setelah fix, run lagi
php artisan migrate
```

#### Option 2: Rollback Sampai Migration Tertentu
```bash
# Rollback sampai sebelum migration yang error
php artisan migrate:rollback --step=5  # Adjust number sesuai berapa migration mau rollback

# Run lagi setelah fix
php artisan migrate
```

#### Option 3: Fresh Migrate (⚠️ HATI-HATI - HAPUS SEMUA DATA)
```bash
# WARNING: Ini akan DROP semua table dan data!
php artisan migrate:fresh

# Dengan seeder
php artisan migrate:fresh --seed
```

---

## 🔧 Cara Fix Migration Error Secara Umum

### Step 1: Identifikasi Error
Lihat error message untuk tahu:
- ❌ Column apa yang tidak ditemukan
- 📄 Migration file mana yang error
- 🔍 Line berapa yang bermasalah

### Step 2: Check Migration History
```bash
# Lihat migration mana yang sudah jalan
php artisan migrate:status
```

### Step 3: Investigasi
```bash
# Check struktur table di database
php artisan tinker
>>> Schema::getColumnListing('tickets');
>>> Schema::hasColumn('tickets', 'rating');  // false = tidak ada
```

### Step 4: Fix Migration File
Edit migration file yang error:
- Ganti referensi kolom yang tidak ada
- Atau hapus `.after('kolom_yang_tidak_ada')`
- Gunakan kolom yang pasti ada

### Step 5: Rollback & Re-run
```bash
php artisan migrate:rollback --step=1
php artisan migrate
```

---

## 📋 Common Migration Errors & Solutions

### 1. "Column not found" Error
**Penyebab:** Referensi kolom yang sudah dihapus atau belum ada

**Solusi:**
```php
// BAD:
$table->string('new_column')->after('deleted_column');

// GOOD:
$table->string('new_column')->after('existing_column');
// Or simply:
$table->string('new_column');  // Tanpa after()
```

### 2. "Table already exists" Error
**Penyebab:** Migration sudah pernah dijalankan

**Solusi:**
```bash
# Check status
php artisan migrate:status

# Rollback jika perlu
php artisan migrate:rollback
```

### 3. "SQLSTATE[23000]: Integrity constraint violation"
**Penyebab:** Foreign key constraint atau unique constraint dilanggar

**Solusi:**
```php
// Disable foreign key checks sementara
Schema::disableForeignKeyConstraints();
// Your migration code
Schema::enableForeignKeyConstraints();
```

### 4. "Column already exists"
**Penyebab:** Migration mencoba menambahkan kolom yang sudah ada

**Solusi:**
```php
// Gunakan check sebelum add
if (!Schema::hasColumn('tickets', 'column_name')) {
    $table->string('column_name');
}
```

---

## 🎯 Best Practices untuk Migration

### 1. Selalu Check Column Exists
```php
public function up(): void
{
    Schema::table('tickets', function (Blueprint $table) {
        // GOOD PRACTICE
        if (!Schema::hasColumn('tickets', 'new_field')) {
            $table->string('new_field')->nullable();
        }
    });
}
```

### 2. Jangan Referensi Kolom yang Tidak Stabil
```php
// AVOID:
$table->string('new_field')->after('might_not_exist');

// PREFER:
$table->string('new_field');  // Laravel will add at end
// Or use timestamp:
$table->string('new_field')->after('updated_at');  // timestamps always exist
```

### 3. Test Migration Before Deploy
```bash
# On development:
php artisan migrate:fresh
# Test thoroughly
# Then commit
```

### 4. Document Migration Dependencies
```php
/**
 * Run the migrations.
 * 
 * NOTE: This migration requires 'email_received_at' column to exist
 * (added in 2025_10_29_204448_add_kpi_fields_to_tickets_table.php)
 */
public function up(): void
{
    // ...
}
```

---

## 🔄 Recovery Commands

### If Migration Stuck/Failed:

```bash
# 1. Check current migration status
php artisan migrate:status

# 2. Rollback failed migration
php artisan migrate:rollback --step=1

# 3. Fix the migration file

# 4. Try again
php artisan migrate

# 5. If still fails, check database directly
php artisan tinker
>>> DB::select('SHOW TABLES');
>>> DB::select('DESCRIBE tickets');
```

### Nuclear Option (Last Resort):

```bash
# ⚠️ WARNING: This DELETES ALL DATA!
# Only use in development or if you have backups

# Drop all tables and re-migrate
php artisan migrate:fresh

# With seeder
php artisan migrate:fresh --seed
```

---

## 📝 Fix Log untuk Migration Ini

### File Fixed:
`database/migrations/2025_11_11_101709_add_kpi_tracking_fields_to_tickets_table.php`

### Change Made:
```diff
- $table->integer('satisfaction_rating')->nullable()->after('rating')->comment('1-5 scale');
+ $table->integer('satisfaction_rating')->nullable()->after('sla_deadline')->comment('1-5 scale');
```

### Reason:
Column `rating` was removed in earlier migration `2025_10_15_083514_remove_rating_feedback_from_tickets_table.php`, so we changed the reference to `sla_deadline` which is added just before in the same migration.

---

## 🚀 After Fix - Run Migration

### For Development (Safe to reset):
```bash
php artisan migrate:fresh --seed
```

### For Production (If already has data):
```bash
# Rollback only the failed migration
php artisan migrate:rollback --step=1

# Run migration again
php artisan migrate
```

---

## ✅ Verification

After successful migration:

```bash
# Check migration status
php artisan migrate:status
# All should show "Ran"

# Verify columns exist
php artisan tinker
>>> Schema::hasColumn('tickets', 'satisfaction_rating');  # Should return true
>>> Schema::hasColumn('tickets', 'sla_deadline');  # Should return true
```

---

## 💡 Quick Reference

| Command | Description |
|---------|-------------|
| `php artisan migrate` | Run pending migrations |
| `php artisan migrate:status` | Check which migrations ran |
| `php artisan migrate:rollback` | Rollback last batch |
| `php artisan migrate:rollback --step=1` | Rollback last migration only |
| `php artisan migrate:fresh` | Drop all tables and re-migrate |
| `php artisan migrate:fresh --seed` | Fresh migrate with seeders |
| `php artisan migrate:reset` | Rollback all migrations |
| `php artisan make:migration name` | Create new migration |

---

**Status:** ✅ FIXED  
**Date:** November 13, 2025  
**Tested:** Migration now runs successfully
