# Panduan Unit Testing dengan PEST

## 📋 Daftar Isi
- [Instalasi](#instalasi)
- [Menjalankan Test](#menjalankan-test)
- [Struktur Test](#struktur-test)
- [Test Coverage](#test-coverage)
- [Contoh Test](#contoh-test)

## 🚀 Instalasi

PEST sudah terinstall di project ini. Dependensi yang digunakan:
- `pestphp/pest` v3.8.4
- `pestphp/pest-plugin-laravel` v3.2.0
- `pestphp/pest-plugin-arch` v3.1.1

## ▶️ Menjalankan Test

### Menjalankan Semua Test
```bash
./vendor/bin/pest
```

### Menjalankan Test Tertentu
```bash
# Jalankan semua unit tests
./vendor/bin/pest tests/Unit/

# Jalankan semua feature tests
./vendor/bin/pest tests/Feature/

# Jalankan file test spesifik
./vendor/bin/pest tests/Unit/WhatsAppTicketTest.php
```

### Filter Test by Name
```bash
# Jalankan test yang mengandung kata "WhatsApp"
./vendor/bin/pest --filter=WhatsApp

# Jalankan beberapa test dengan pattern
./vendor/bin/pest --filter="WhatsApp|Kpi|Validation"
```

### Output Format
```bash
# Compact view (ringkas)
./vendor/bin/pest --compact

# Verbose (detail)
./vendor/bin/pest --verbose

# Stop saat ada failure pertama
./vendor/bin/pest --stop-on-failure
```

### Coverage
```bash
# Generate coverage report
./vendor/bin/pest --coverage

# Coverage dengan minimum threshold
./vendor/bin/pest --coverage --min=80
```

## 📁 Struktur Test

```
tests/
├── Feature/               # Integration/Feature tests
│   ├── WhatsAppMonitorTest.php
│   ├── KpiDashboardTest.php
│   └── ...
├── Unit/                  # Unit tests
│   ├── WhatsAppTicketTest.php
│   ├── KpiCalculationServiceTest.php
│   ├── ValidationServiceTest.php
│   └── ...
├── Pest.php              # Konfigurasi PEST
└── TestCase.php          # Base test case
```

## ✅ Test Coverage

### Unit Tests yang Tersedia:

#### 1. **WhatsAppTicketTest.php** (13 tests)
- Model creation dan attributes
- Relationships (User, Responses)
- Data casting (dates, booleans, arrays)
- Scopes (open, urgent, byCategory, unassigned, assignedTo)

#### 2. **KpiCalculationServiceTest.php** (13 tests)
- Perhitungan response time
- Perhitungan resolution time
- Perhitungan ticket creation delay
- Set first response time
- Set resolved time
- Average calculations
- Edge cases handling

#### 3. **ValidationServiceTest.php** (14 tests)
- Data completeness validation
- Email format validation
- Duplicate detection
- Spam detection
- Edge cases dan boundary testing

### Feature Tests:
- WhatsAppMonitorTest.php (11 tests)
- Dan test lainnya yang sudah ada

## 📝 Contoh Test

### Unit Test Example (PEST Style)

```php
<?php

use App\Models\WhatsAppTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create whatsapp ticket', function () {
    $ticket = WhatsAppTicket::create([
        'ticket_number' => 'WA-TEST-001',
        'sender_phone' => '628123456789',
        'sender_name' => 'John Doe',
        'subject' => 'Test Ticket',
        'description' => 'This is a test',
        'original_message' => 'Original message',
        'category' => 'network',
        'priority' => 'normal',
        'status' => 'open',
        'source' => 'whatsapp',
    ]);

    expect($ticket)->toBeInstanceOf(WhatsAppTicket::class)
        ->and($ticket->ticket_number)->toBe('WA-TEST-001')
        ->and($ticket->status)->toBe('open');
});

test('whatsapp ticket has correct fillable attributes', function () {
    $fillable = (new WhatsAppTicket())->getFillable();

    expect($fillable)->toContain('ticket_number')
        ->and($fillable)->toContain('sender_phone')
        ->and($fillable)->toContain('category');
});
```

### Feature Test Example

```php
<?php

use App\Models\User;
use App\Models\WhatsAppTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('admin can view whatsapp monitor page', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('whatsapp.monitor'));

    $response->assertStatus(200)
        ->assertViewIs('whatsapp.monitor')
        ->assertViewHas('botStatus');
});
```

### Service Test Example

```php
<?php

use App\Services\KpiCalculationService;
use App\Models\Ticket;
use Carbon\Carbon;

beforeEach(function () {
    $this->kpiService = new KpiCalculationService();
});

test('can calculate response time for ticket', function () {
    $ticket = Ticket::factory()->create([
        'email_received_at' => Carbon::parse('2024-01-01 10:00:00'),
        'first_response_at' => Carbon::parse('2024-01-01 10:30:00'),
    ]);

    $this->kpiService->updateTicketKpiMetrics($ticket);
    
    $ticket->refresh();

    expect($ticket->response_time_minutes)->toBe(30);
});
```

## 🎯 Best Practices

### 1. Gunakan `uses()` untuk traits
```php
uses(RefreshDatabase::class);
```

### 2. Gunakan `beforeEach()` untuk setup
```php
beforeEach(function () {
    $this->admin = User::factory()->create();
});
```

### 3. Gunakan Expectations Chain
```php
expect($user)->toBeInstanceOf(User::class)
    ->and($user->email)->toContain('@')
    ->and($user->role)->toBe('admin');
```

### 4. Test Names yang Descriptive
```php
// ✅ Good
test('admin can approve pending ticket');

// ❌ Bad
test('test1');
```

### 5. Arrange-Act-Assert Pattern
```php
test('creates ticket with correct status', function () {
    // Arrange
    $data = ['status' => 'open'];
    
    // Act
    $ticket = Ticket::create($data);
    
    // Assert
    expect($ticket->status)->toBe('open');
});
```

## 🔧 Konfigurasi

### Pest.php
```php
pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit');
```

### phpunit.xml
- Database: SQLite in-memory
- Cache: Array
- Session: Array
- Queue: Sync

## 📊 Statistik Test Saat Ini

- **Total Unit Tests**: 39 tests
- **Total Assertions**: 83+ assertions
- **Success Rate**: 100% ✅
- **Duration**: ~0.8s

## 🐛 Debugging

### Dump & Die
```php
test('example', function () {
    $data = ['key' => 'value'];
    dd($data); // Dump and die
});
```

### View Output
```php
test('example', function () {
    dump($ticket); // Output to console
    expect($ticket)->toBeInstanceOf(Ticket::class);
});
```

### Test Isolation
```php
// Jalankan test secara parallel
./vendor/bin/pest --parallel

// Jalankan dengan isolation
./vendor/bin/pest --process-isolation
```

## 📚 Resources

- [PEST Documentation](https://pestphp.com)
- [PEST Laravel Plugin](https://pestphp.com/docs/plugins/laravel)
- [Expectation API](https://pestphp.com/docs/expectations)
- [Pest Best Practices](https://pestphp.com/docs/guides/best-practices)

## 🎓 Tips & Tricks

1. **Gunakan factories** untuk data testing yang konsisten
2. **Isolasi test** - setiap test harus independent
3. **Test edge cases** - bukan hanya happy path
4. **Readable test names** - test adalah dokumentasi
5. **Keep tests fast** - gunakan database in-memory untuk speed

## 🚦 CI/CD Integration

Untuk menjalankan test di CI/CD:

```yaml
# .github/workflows/tests.yml
- name: Run Tests
  run: ./vendor/bin/pest --ci
```

---

**Happy Testing! 🎉**
