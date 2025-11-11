<?php

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'role' => 'admin',
    ]);
    
    $this->user = User::factory()->create([
        'name' => 'Regular User',
        'email' => 'user@example.com',
        'role' => 'user',
    ]);
});

// ============ ADMIN DASHBOARD ACCESS ============

test('admin can access admin dashboard', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertStatus(200)
        ->assertViewIs('admin.dashboard');
});

test('regular user cannot access admin dashboard', function () {
    $response = $this->actingAs($this->user)->get(route('admin.dashboard'));

    $response->assertStatus(403);
});

test('guest cannot access admin dashboard', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('login'));
});

test('admin dashboard shows statistics', function () {
    Ticket::factory()->count(5)->create(['status' => 'open']);
    Ticket::factory()->count(3)->create(['status' => 'closed']);
    Ticket::factory()->count(2)->create(['status' => 'pending_keluhan']);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertStatus(200)
        ->assertViewHas('totalTickets')
        ->assertViewHas('openTickets')
        ->assertViewHas('closedTickets');
});

// ============ PENDING TICKETS ============

test('admin can view pending tickets page', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.pending-review'));

    $response->assertStatus(200)
        ->assertViewIs('admin.pending-review');
});

test('pending tickets page shows only pending tickets', function () {
    Ticket::factory()->count(3)->create(['status' => 'pending_keluhan']);
    Ticket::factory()->count(2)->create(['status' => 'open']);
    Ticket::factory()->count(1)->create(['status' => 'closed']);

    $response = $this->actingAs($this->admin)->get(route('admin.pending-review'));

    $response->assertStatus(200)
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->count() === 3 && 
                   $tickets->every(fn($t) => $t->status === 'pending_keluhan');
        });
});

// ============ ADMIN TICKET CREATION ============

test('admin can view create ticket form', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.tickets.create'));

    $response->assertStatus(200)
        ->assertSee('Create Ticket');
});

test('admin can create ticket manually', function () {
    $ticketData = [
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'user_phone' => '081234567890',
        'channel' => 'portal',
        'subject' => 'Admin Created Ticket',
        'description' => 'This ticket was created by admin',
        'category' => 'Technical',
        'priority' => 'high',
        'status' => 'open',
    ];

    $response = $this->actingAs($this->admin)->post(route('admin.tickets.store'), $ticketData);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tickets', [
        'subject' => 'Admin Created Ticket',
        'status' => 'open',
    ]);
});

test('admin created ticket bypasses validation', function () {
    $ticketData = [
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'subject' => 'Quick Ticket',
        'description' => 'Short desc', // Short description
        'category' => 'Technical',
        'priority' => 'high',
        'status' => 'open',
        'validation_status' => 'auto_approved',
    ];

    $response = $this->actingAs($this->admin)->post(route('admin.tickets.store'), $ticketData);

    $response->assertRedirect();

    $ticket = Ticket::latest()->first();
    expect($ticket)->not->toBeNull()
        ->and($ticket->validation_status)->toBe('auto_approved');
});

// ============ TICKET VIEWING ============

test('admin can view any ticket', function () {
    $userTicket = Ticket::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.tickets.show', $userTicket));

    $response->assertStatus(200)
        ->assertSee($userTicket->subject);
});

test('admin can see ticket management actions', function () {
    $ticket = Ticket::factory()->create([
        'status' => 'pending_keluhan',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.tickets.show', $ticket));

    $response->assertStatus(200)
        ->assertSee('Approve')
        ->assertSee('Reject');
});

// ============ TICKET APPROVAL ============

test('admin can approve pending ticket', function () {
    $ticket = Ticket::factory()->create([
        'status' => 'pending_keluhan',
        'validation_status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.tickets.approve', $ticket));

    $response->assertRedirect();

    $ticket->refresh();
    expect($ticket->validation_status)->toBe('approved')
        ->and($ticket->status)->toBe('open');
});

test('admin can reject ticket with reason', function () {
    $ticket = Ticket::factory()->create([
        'status' => 'pending_keluhan',
        'validation_status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.tickets.reject', $ticket), [
            'rejection_reason' => 'Invalid information provided',
        ]);

    $response->assertRedirect();

    $ticket->refresh();
    expect($ticket->validation_status)->toBe('rejected')
        ->and($ticket->rejection_reason)->toBe('Invalid information provided');
});

test('rejection requires reason', function () {
    $ticket = Ticket::factory()->create([
        'status' => 'pending_keluhan',
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.tickets.reject', $ticket), []);

    $response->assertSessionHasErrors('rejection_reason');
});

// ============ TICKET ASSIGNMENT ============

test('admin can assign ticket to another admin', function () {
    $assignee = User::factory()->create(['role' => 'admin']);
    
    $ticket = Ticket::factory()->create([
        'status' => 'open',
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.tickets.assign', $ticket), [
            'assigned_to' => $assignee->id,
        ]);

    $response->assertRedirect();

    $ticket->refresh();
    expect($ticket->assigned_to)->toBe($assignee->id);
});

test('ticket shows assigned admin name', function () {
    $assignee = User::factory()->create([
        'name' => 'Support Admin',
        'role' => 'admin',
    ]);
    
    $ticket = Ticket::factory()->create([
        'assigned_to' => $assignee->id,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.tickets.show', $ticket));

    $response->assertStatus(200)
        ->assertSee('Support Admin');
});

// ============ STATUS UPDATES ============

test('admin can update ticket status', function () {
    $ticket = Ticket::factory()->create([
        'status' => 'open',
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.tickets.update-status', $ticket), [
            'status' => 'in_progress',
        ]);

    $response->assertRedirect();

    $ticket->refresh();
    expect($ticket->status)->toBe('in_progress');
});

test('admin can close ticket with resolution', function () {
    $ticket = Ticket::factory()->create([
        'status' => 'open',
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.tickets.close', $ticket), [
            'resolution_notes' => 'Issue has been resolved successfully',
        ]);

    $response->assertRedirect();

    $ticket->refresh();
    expect($ticket->status)->toBe('closed')
        ->and($ticket->resolution_notes)->toBe('Issue has been resolved successfully')
        ->and($ticket->closed_at)->not->toBeNull();
});

// ============ INTERNAL NOTES ============

test('admin can add internal note to ticket', function () {
    $ticket = Ticket::factory()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('admin.tickets.add-note', $ticket), [
            'note' => 'Internal investigation note',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('ticket_status_histories', [
        'ticket_id' => $ticket->id,
        'note' => 'Internal investigation note',
    ]);
});

// ============ REQUEST REVISION ============

test('admin can request ticket revision', function () {
    $ticket = Ticket::factory()->create([
        'status' => 'pending_keluhan',
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.tickets.revision', $ticket), [
            'revision_notes' => 'Please provide more details about the issue',
        ]);

    $response->assertRedirect();

    $ticket->refresh();
    expect($ticket->validation_status)->toBe('needs_revision')
        ->and($ticket->revision_notes)->toBe('Please provide more details about the issue');
});

// ============ DASHBOARD STATISTICS ============

test('dashboard shows correct ticket counts by status', function () {
    Ticket::factory()->count(3)->create(['status' => 'open']);
    Ticket::factory()->count(2)->create(['status' => 'in_progress']);
    Ticket::factory()->count(4)->create(['status' => 'closed']);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertStatus(200)
        ->assertViewHas('openTickets', 3)
        ->assertViewHas('closedTickets', 4);
});

test('dashboard shows recent tickets', function () {
    Ticket::factory()->count(10)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertStatus(200)
        ->assertViewHas('recentTickets', function ($tickets) {
            return $tickets->count() > 0;
        });
});

test('dashboard shows tickets by category', function () {
    Ticket::factory()->create(['category' => 'Technical']);
    Ticket::factory()->create(['category' => 'Billing']);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertStatus(200)
        ->assertViewHas('ticketsByCategory');
});

// ============ AUTHORIZATION ============

test('regular user cannot approve tickets', function () {
    $ticket = Ticket::factory()->create(['status' => 'pending_keluhan']);

    $response = $this->actingAs($this->user)
        ->post(route('admin.tickets.approve', $ticket));

    $response->assertStatus(403);
});

test('regular user cannot reject tickets', function () {
    $ticket = Ticket::factory()->create(['status' => 'pending_keluhan']);

    $response = $this->actingAs($this->user)
        ->post(route('admin.tickets.reject', $ticket), [
            'rejection_reason' => 'Invalid',
        ]);

    $response->assertStatus(403);
});

test('regular user cannot assign tickets', function () {
    $ticket = Ticket::factory()->create();

    $response = $this->actingAs($this->user)
        ->post(route('admin.tickets.assign', $ticket), [
            'assigned_to' => $this->admin->id,
        ]);

    $response->assertStatus(403);
});
