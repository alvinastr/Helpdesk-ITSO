<?php

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create(['role' => 'user']);
});

// ============ REPORT ACCESS ============

test('admin can access reports page', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.reports'));

    $response->assertStatus(200)
        ->assertViewIs('admin.reports.index');
});

test('regular user cannot access reports', function () {
    $response = $this->actingAs($this->user)->get(route('admin.reports'));

    $response->assertStatus(403);
});

test('guest cannot access reports', function () {
    $response = $this->get(route('admin.reports'));

    $response->assertRedirect(route('login'));
});

// ============ REPORT DATA DISPLAY ============

test('reports show default 30 days data', function () {
    Ticket::factory()->count(5)->create([
        'created_at' => now()->subDays(10),
    ]);
    
    Ticket::factory()->count(2)->create([
        'created_at' => now()->subDays(40),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reports'));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->count() === 5;
        });
});

test('reports can filter by date range', function () {
    Ticket::factory()->create([
        'created_at' => Carbon::parse('2024-01-01'),
    ]);
    
    Ticket::factory()->create([
        'created_at' => Carbon::parse('2024-02-01'),
    ]);
    
    Ticket::factory()->create([
        'created_at' => Carbon::parse('2024-03-01'),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reports', [
        'start_date' => '2024-02-01',
        'end_date' => '2024-02-28',
    ]));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->count() === 1;
        });
});

// ============ REPORT STATISTICS ============

test('reports show total tickets count', function () {
    Ticket::factory()->count(15)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.reports'));

    $response->assertStatus(200)
        ->assertViewHas('totalTickets', 15);
});

test('reports show tickets by status', function () {
    Ticket::factory()->count(5)->create(['status' => 'open']);
    Ticket::factory()->count(3)->create(['status' => 'closed']);
    Ticket::factory()->count(2)->create(['status' => 'in_progress']);

    $response = $this->actingAs($this->admin)->get(route('admin.reports'));

    $response->assertStatus(200)
        ->assertViewHas('ticketsByStatus');
});

test('reports show tickets by category', function () {
    Ticket::factory()->create(['category' => 'Technical']);
    Ticket::factory()->create(['category' => 'Billing']);
    Ticket::factory()->create(['category' => 'General']);

    $response = $this->actingAs($this->admin)->get(route('admin.reports'));

    $response->assertStatus(200)
        ->assertViewHas('ticketsByCategory');
});

test('reports show tickets by channel', function () {
    Ticket::factory()->count(3)->create(['channel' => 'email']);
    Ticket::factory()->count(2)->create(['channel' => 'portal']);
    Ticket::factory()->count(1)->create(['channel' => 'phone']);

    $response = $this->actingAs($this->admin)->get(route('admin.reports'));

    $response->assertStatus(200)
        ->assertViewHas('ticketsByChannel');
});

test('reports show tickets by priority', function () {
    Ticket::factory()->count(4)->create(['priority' => 'high']);
    Ticket::factory()->count(6)->create(['priority' => 'medium']);
    Ticket::factory()->count(2)->create(['priority' => 'low']);

    $response = $this->actingAs($this->admin)->get(route('admin.reports'));

    $response->assertStatus(200)
        ->assertViewHas('ticketsByPriority');
});

// ============ EXPORT FUNCTIONALITY ============

test('admin can export report to excel', function () {
    Ticket::factory()->count(5)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.reports.excel'));

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('admin can export report to pdf', function () {
    Ticket::factory()->count(5)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.reports.pdf'));

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/pdf');
});

test('exported excel has correct filename', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.reports.excel'));

    $response->assertHeader('Content-Disposition', function ($value) {
        return str_contains($value, 'tickets_report') && str_contains($value, '.xlsx');
    });
});

test('exported pdf has correct filename', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.reports.pdf'));

    $response->assertHeader('Content-Disposition', function ($value) {
        return str_contains($value, 'tickets_report') && str_contains($value, '.pdf');
    });
});

// ============ REPORT FILTERING ============

test('reports can filter by status', function () {
    Ticket::factory()->count(5)->create(['status' => 'open']);
    Ticket::factory()->count(3)->create(['status' => 'closed']);

    $response = $this->actingAs($this->admin)->get(route('admin.reports', [
        'status' => 'open',
    ]));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->count() === 5 &&
                   $tickets->every(fn($t) => $t->status === 'open');
        });
});

test('reports can filter by category', function () {
    Ticket::factory()->count(4)->create(['category' => 'Technical']);
    Ticket::factory()->count(2)->create(['category' => 'Billing']);

    $response = $this->actingAs($this->admin)->get(route('admin.reports', [
        'category' => 'Technical',
    ]));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->count() === 4 &&
                   $tickets->every(fn($t) => $t->category === 'Technical');
        });
});

test('reports can filter by priority', function () {
    Ticket::factory()->count(3)->create(['priority' => 'high']);
    Ticket::factory()->count(5)->create(['priority' => 'medium']);

    $response = $this->actingAs($this->admin)->get(route('admin.reports', [
        'priority' => 'high',
    ]));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->count() === 3 &&
                   $tickets->every(fn($t) => $t->priority === 'high');
        });
});

test('reports can filter by channel', function () {
    Ticket::factory()->count(6)->create(['channel' => 'email']);
    Ticket::factory()->count(2)->create(['channel' => 'portal']);

    $response = $this->actingAs($this->admin)->get(route('admin.reports', [
        'channel' => 'email',
    ]));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->count() === 6 &&
                   $tickets->every(fn($t) => $t->channel === 'email');
        });
});

// ============ EMPTY STATE ============

test('reports handle empty data gracefully', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.reports'));

    $response->assertStatus(200)
        ->assertSee('No tickets found');
});

test('reports show message when no tickets in date range', function () {
    Ticket::factory()->count(5)->create([
        'created_at' => now()->subMonths(6),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reports', [
        'start_date' => now()->subDays(30)->format('Y-m-d'),
        'end_date' => now()->format('Y-m-d'),
    ]));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->count() === 0;
        });
});

// ============ DATE VALIDATION ============

test('reports show correct date range in header', function () {
    $startDate = now()->subDays(7);
    $endDate = now();

    $response = $this->actingAs($this->admin)->get(route('admin.reports', [
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
    ]));

    $response->assertStatus(200)
        ->assertSee($startDate->format('d M Y'))
        ->assertSee($endDate->format('d M Y'));
});

test('reports only count tickets within date range', function () {
    Ticket::factory()->create([
        'created_at' => Carbon::parse('2024-01-15'),
    ]);
    
    Ticket::factory()->create([
        'created_at' => Carbon::parse('2024-01-20'),
    ]);
    
    Ticket::factory()->create([
        'created_at' => Carbon::parse('2024-01-25'),
    ]);
    
    Ticket::factory()->create([
        'created_at' => Carbon::parse('2024-02-01'),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reports', [
        'start_date' => '2024-01-15',
        'end_date' => '2024-01-31',
    ]));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->count() === 3;
        });
});

// ============ CHARTS & VISUALIZATIONS ============

test('reports page includes chart data', function () {
    Ticket::factory()->count(10)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.reports'));

    $response->assertStatus(200)
        ->assertViewHas('ticketsByStatus')
        ->assertViewHas('ticketsByCategory')
        ->assertViewHas('ticketsByChannel');
});

// ============ PERFORMANCE METRICS ============

test('reports show average resolution time', function () {
    Ticket::factory()->create([
        'status' => 'closed',
        'resolution_time_minutes' => 60,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reports'));

    $response->assertStatus(200)
        ->assertViewHas('avgResolutionTime');
});

test('reports show tickets resolved vs pending', function () {
    Ticket::factory()->count(8)->create(['status' => 'closed']);
    Ticket::factory()->count(2)->create(['status' => 'open']);

    $response = $this->actingAs($this->admin)->get(route('admin.reports'));

    $response->assertStatus(200)
        ->assertViewHas('closedTickets', 8)
        ->assertViewHas('openTickets', 2);
});

// ============ EXPORT WITH FILTERS ============

test('excel export respects date filter', function () {
    Ticket::factory()->count(5)->create([
        'created_at' => now()->subDays(5),
    ]);
    
    Ticket::factory()->count(3)->create([
        'created_at' => now()->subDays(40),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reports.excel', [
        'start_date' => now()->subDays(7)->format('Y-m-d'),
        'end_date' => now()->format('Y-m-d'),
    ]));

    $response->assertStatus(200);
});

test('pdf export respects category filter', function () {
    Ticket::factory()->count(4)->create(['category' => 'Technical']);
    Ticket::factory()->count(2)->create(['category' => 'Billing']);

    $response = $this->actingAs($this->admin)->get(route('admin.reports.pdf', [
        'category' => 'Technical',
    ]));

    $response->assertStatus(200);
});
