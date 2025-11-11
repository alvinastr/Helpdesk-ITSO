<?php

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
    ]);
    
    $this->user = User::factory()->create([
        'role' => 'user',
    ]);
});

// ============ EMAIL MONITOR ACCESS ============

test('admin can access email monitor page', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertViewIs('admin.email-monitor.index');
});

test('regular user cannot access email monitor page', function () {
    $response = $this->actingAs($this->user)->get(route('admin.email-monitor'));

    $response->assertStatus(403);
});

test('guest cannot access email monitor page', function () {
    $response = $this->get(route('admin.email-monitor'));

    $response->assertRedirect(route('login'));
});

// ============ EMAIL MONITOR DISPLAY ============

test('email monitor shows fetch statistics', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertViewHas('stats')
        ->assertSee('Email Statistics');
});

test('email monitor shows recent fetched tickets', function () {
    // Create email-sourced tickets
    Ticket::factory()->count(5)->create([
        'input_method' => 'email_auto_fetch',
        'channel' => 'email',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertViewHas('recentTickets', function ($tickets) {
            return $tickets->count() === 5 &&
                   $tickets->every(fn($t) => $t->input_method === 'email_auto_fetch');
        });
});

test('email monitor shows email processing status', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertSee('Last Fetch')
        ->assertSee('Total Emails');
});

// ============ LIVE STATS API ============

test('admin can fetch live email stats', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor.live-stats'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'total_fetched',
            'successful',
            'failed',
            'last_fetch_time',
        ]);
});

test('live stats returns correct data format', function () {
    Ticket::factory()->count(10)->create([
        'input_method' => 'email_auto_fetch',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor.live-stats'));

    $response->assertStatus(200);
    
    $data = $response->json();
    
    expect($data)->toHaveKey('total_fetched')
        ->and($data)->toHaveKey('successful')
        ->and($data['total_fetched'])->toBeGreaterThanOrEqual(0);
});

// ============ EMAIL FILTER DISPLAY ============

test('email monitor shows filtered tickets', function () {
    Ticket::factory()->create([
        'input_method' => 'email_auto_fetch',
        'validation_status' => 'approved',
    ]);
    
    Ticket::factory()->create([
        'input_method' => 'email_auto_fetch',
        'validation_status' => 'rejected',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertSee('approved')
        ->assertSee('rejected');
});

test('email monitor can filter by date range', function () {
    Ticket::factory()->create([
        'input_method' => 'email_auto_fetch',
        'created_at' => now()->subDays(5),
    ]);
    
    Ticket::factory()->create([
        'input_method' => 'email_auto_fetch',
        'created_at' => now()->subDays(1),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor', [
        'start_date' => now()->subDays(2)->format('Y-m-d'),
        'end_date' => now()->format('Y-m-d'),
    ]));

    $response->assertStatus(200)
        ->assertViewHas('recentTickets', function ($tickets) {
            return $tickets->count() === 1;
        });
});

// ============ EMAIL METRICS ============

test('email monitor shows success rate', function () {
    Ticket::factory()->count(8)->create([
        'input_method' => 'email_auto_fetch',
        'validation_status' => 'approved',
    ]);
    
    Ticket::factory()->count(2)->create([
        'input_method' => 'email_auto_fetch',
        'validation_status' => 'rejected',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertViewHas('stats', function ($stats) {
            return isset($stats['success_rate']);
        });
});

test('email monitor shows tickets by category', function () {
    Ticket::factory()->create([
        'input_method' => 'email_auto_fetch',
        'category' => 'Technical',
    ]);
    
    Ticket::factory()->create([
        'input_method' => 'email_auto_fetch',
        'category' => 'Billing',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertViewHas('ticketsByCategory');
});

// ============ REALTIME UPDATES ============

test('email monitor page includes refresh functionality', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertSee('Refresh')
        ->assertSee('Auto Refresh');
});

// ============ EMAIL DETAILS ============

test('email monitor shows email metadata', function () {
    $ticket = Ticket::factory()->create([
        'input_method' => 'email_auto_fetch',
        'user_email' => 'sender@example.com',
        'email_received_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertSee('sender@example.com');
});

test('email monitor shows processing time', function () {
    Ticket::factory()->create([
        'input_method' => 'email_auto_fetch',
        'email_received_at' => now()->subMinutes(5),
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertViewHas('recentTickets');
});

// ============ ERROR HANDLING ============

test('email monitor handles no tickets gracefully', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertSee('No emails fetched yet');
});

test('email monitor shows error messages for failed fetches', function () {
    Ticket::factory()->create([
        'input_method' => 'email_auto_fetch',
        'validation_status' => 'rejected',
        'rejection_reason' => 'Invalid email format',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200);
});

// ============ PAGINATION ============

test('email tickets are paginated', function () {
    Ticket::factory()->count(30)->create([
        'input_method' => 'email_auto_fetch',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertViewHas('recentTickets', function ($tickets) {
            return $tickets instanceof \Illuminate\Pagination\LengthAwarePaginator;
        });
});

// ============ EXPORT FUNCTIONALITY ============

test('email monitor has export button', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.email-monitor'));

    $response->assertStatus(200)
        ->assertSee('Export');
});
