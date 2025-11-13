<?php

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Regular User',
        'email' => 'user@example.com',
        'role' => 'user',
    ]);
    
    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'role' => 'admin',
    ]);
});

// ============ TICKET LISTING ============

test('user can view their dashboard', function () {
    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertStatus(200)
        ->assertViewIs('dashboard')
        ->assertSee('Dashboard');
});

test('user can view their tickets list', function () {
    Ticket::factory()->count(3)->create([
        'user_id' => $this->user->id,
    ]);

    Ticket::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.index'));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->count() === 3;
        });
});

test('user cannot view other users tickets', function () {
    $otherUser = User::factory()->create();
    $otherTicket = Ticket::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.show', $otherTicket));

    $response->assertStatus(403);
});

// ============ TICKET CREATION ============

test('user can view create ticket form', function () {
    $response = $this->actingAs($this->user)->get(route('tickets.create'));

    $response->assertStatus(200)
        ->assertViewIs('tickets.create')
        ->assertSee('Buat Tiket'); // Indonesian translation
});

test('user can create a new ticket', function () {
    $ticketData = [
        'reporter_nip' => '123456',
        'reporter_name' => 'John Doe',
        'reporter_email' => 'john@example.com',
        'reporter_phone' => '081234567890',
        'reporter_department' => 'IT Department',
        'input_method' => 'manual',
        'channel' => 'portal',
        'subject' => 'Test Ticket Subject',
        'description' => 'This is a detailed description of the test ticket',
        'category' => 'Technical',
        'priority' => 'medium',
    ];

    $response = $this->actingAs($this->user)->post(route('tickets.store'), $ticketData);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tickets', [
        'user_id' => $this->user->id,
        'subject' => 'Test Ticket Subject',
        'category' => 'Technical',
        'status' => 'pending_keluhan',
    ]);
});

test('user cannot create ticket without required fields', function () {
    $response = $this->actingAs($this->user)->post(route('tickets.store'), []);

    $response->assertSessionHasErrors(['reporter_nip', 'reporter_name', 'reporter_department', 'reporter_email', 'subject', 'description', 'input_method', 'channel']);
});

test('user cannot create ticket with invalid email', function () {
    $ticketData = [
        'reporter_nip' => '123456',
        'reporter_name' => 'John Doe',
        'reporter_email' => 'invalid-email',
        'reporter_department' => 'IT Department',
        'input_method' => 'manual',
        'channel' => 'portal',
        'subject' => 'Test Subject',
        'description' => 'Test description for validation',
    ];

    $response = $this->actingAs($this->user)->post(route('tickets.store'), $ticketData);

    $response->assertSessionHasErrors('reporter_email');
});

test('ticket is created with correct default values', function () {
    $ticketData = [
        'reporter_nip' => '123456',
        'reporter_name' => 'John Doe',
        'reporter_email' => 'john@example.com',
        'reporter_phone' => '081234567890',
        'reporter_department' => 'IT Department',
        'input_method' => 'manual',
        'channel' => 'portal',
        'subject' => 'Test Subject',
        'description' => 'This is a detailed test description',
        'category' => 'Technical',
        'priority' => 'medium',
    ];

    $this->actingAs($this->user)->post(route('tickets.store'), $ticketData);

    $ticket = Ticket::latest()->first();

    expect($ticket->status)->toBe('pending_keluhan')
        ->and($ticket->user_id)->toBe($this->user->id)
        ->and($ticket->input_method)->toBe('manual')
        ->and($ticket->ticket_number)->toStartWith('TKT-');
});

// ============ TICKET VIEWING ============

test('user can view their own ticket', function () {
    $ticket = Ticket::factory()->create([
        'user_id' => $this->user->id,
        'subject' => 'My Ticket',
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.show', $ticket));

    $response->assertStatus(200)
        ->assertSee('My Ticket')
        ->assertSee($ticket->ticket_number);
});

test('ticket shows correct status badge', function () {
    $ticket = Ticket::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'open',
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.show', $ticket));

    $response->assertStatus(200)
        ->assertSee('open');
});

// ============ TICKET SEARCH & FILTER ============

test('user can search tickets by keyword', function () {
    Ticket::factory()->create([
        'user_id' => $this->user->id,
        'subject' => 'Network Issue',
    ]);
    
    Ticket::factory()->create([
        'user_id' => $this->user->id,
        'subject' => 'Email Problem',
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.index', ['search' => 'Network']));

    $response->assertStatus(200)
        ->assertSee('Network Issue')
        ->assertDontSee('Email Problem');
});

test('user can filter tickets by status', function () {
    Ticket::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'open',
    ]);
    
    Ticket::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'closed',
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.index', ['status' => 'open']));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->every(fn($ticket) => $ticket->status === 'open');
        });
});

test('user can filter tickets by category', function () {
    Ticket::factory()->create([
        'user_id' => $this->user->id,
        'category' => 'Technical',
    ]);
    
    Ticket::factory()->create([
        'user_id' => $this->user->id,
        'category' => 'Billing',
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.index', ['category' => 'Technical']));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->every(fn($ticket) => $ticket->category === 'Technical');
        });
});

// ============ GUEST ACCESS ============

test('guest cannot access tickets page', function () {
    $response = $this->get(route('tickets.index'));

    $response->assertRedirect(route('login'));
});

test('guest cannot create ticket', function () {
    $response = $this->get(route('tickets.create'));

    $response->assertRedirect(route('login'));
});

test('guest is redirected to login from homepage', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

// ============ PAGINATION ============

test('tickets are paginated', function () {
    Ticket::factory()->count(25)->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.index'));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets instanceof \Illuminate\Pagination\LengthAwarePaginator;
        });
});

// ============ TICKET EDIT ============

test('user can view edit form for their ticket', function () {
    $ticket = Ticket::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'pending_keluhan',
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.edit', $ticket));

    $response->assertStatus(200)
        ->assertSee($ticket->subject);
});

test('user can update their ticket', function () {
    $ticket = Ticket::factory()->create([
        'user_id' => $this->user->id,
        'subject' => 'Old Subject',
        'status' => 'pending_keluhan',
    ]);

    $response = $this->actingAs($this->user)->put(route('tickets.update', $ticket), [
        'user_name' => $ticket->user_name,
        'user_email' => $ticket->user_email,
        'user_phone' => $ticket->user_phone,
        'subject' => 'Updated Subject',
        'description' => 'Updated description with enough content',
        'category' => $ticket->category,
        'priority' => $ticket->priority,
    ]);

    $response->assertRedirect();
    
    $ticket->refresh();
    expect($ticket->subject)->toBe('Updated Subject');
});

test('user cannot edit other users ticket', function () {
    $otherTicket = Ticket::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('tickets.edit', $otherTicket));

    $response->assertStatus(403);
});
