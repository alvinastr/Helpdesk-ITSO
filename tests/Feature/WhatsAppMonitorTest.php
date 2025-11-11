<?php

use App\Models\User;
use App\Models\WhatsAppTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@example.com',
        'role' => 'admin',
    ]);
});

test('admin can view whatsapp monitor page', function () {
    $response = $this->actingAs($this->admin)->get(route('whatsapp.monitor'));

    $response->assertStatus(200)
        ->assertViewIs('whatsapp.monitor')
        ->assertViewHas('botStatus')
        ->assertViewHas('recentTickets');
});

test('monitor page displays bot status', function () {
    $response = $this->actingAs($this->admin)->get(route('whatsapp.monitor'));

    $response->assertStatus(200)
        ->assertSee('WhatsApp Bot Monitoring')
        ->assertSee('Bot Status');
});

test('monitor page displays ticket statistics', function () {
    // Create some tickets
    WhatsAppTicket::factory()->count(5)->create([
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('whatsapp.monitor'));

    $response->assertStatus(200)
        ->assertSee('Tickets Overview')
        ->assertViewHas('ticketsToday');
});

test('monitor page displays recent tickets table', function () {
    $tickets = WhatsAppTicket::factory()->count(3)->create();

    $response = $this->actingAs($this->admin)->get(route('whatsapp.monitor'));

    $response->assertStatus(200)
        ->assertSee('Recent Tickets');
    
    foreach ($tickets as $ticket) {
        $response->assertSee($ticket->ticket_number);
    }
});

test('monitor page shows tickets grouped by category', function () {
    WhatsAppTicket::factory()->create(['category' => 'network']);
    WhatsAppTicket::factory()->create(['category' => 'hardware']);
    WhatsAppTicket::factory()->create(['category' => 'software']);

    $response = $this->actingAs($this->admin)->get(route('whatsapp.monitor'));

    $response->assertStatus(200)
        ->assertViewHas('ticketsByCategory')
        ->assertSee('Tickets by Category');
});

test('monitor page shows tickets grouped by priority', function () {
    WhatsAppTicket::factory()->create(['priority' => 'urgent']);
    WhatsAppTicket::factory()->create(['priority' => 'high']);
    WhatsAppTicket::factory()->create(['priority' => 'normal']);

    $response = $this->actingAs($this->admin)->get(route('whatsapp.monitor'));

    $response->assertStatus(200)
        ->assertViewHas('ticketsByPriority')
        ->assertSee('Tickets by Priority');
});

test('guest cannot access whatsapp monitor page', function () {
    $response = $this->get(route('whatsapp.monitor'));

    $response->assertRedirect(route('login'));
});

test('monitor page displays queue statistics', function () {
    $response = $this->actingAs($this->admin)->get(route('whatsapp.monitor'));

    $response->assertStatus(200)
        ->assertSee('Queue Status')
        ->assertViewHas('queueStats');
});

test('monitor page shows empty state when no tickets', function () {
    $response = $this->actingAs($this->admin)->get(route('whatsapp.monitor'));

    $response->assertStatus(200)
        ->assertSee('No tickets found');
});

test('refresh button is present on monitor page', function () {
    $response = $this->actingAs($this->admin)->get(route('whatsapp.monitor'));

    $response->assertStatus(200)
        ->assertSee('Refresh');
});

test('monitor page displays ticket details correctly', function () {
    $ticket = WhatsAppTicket::factory()->create([
        'ticket_number' => 'WA-TEST-001',
        'sender_name' => 'John Doe',
        'sender_phone' => '628123456789',
        'category' => 'network',
        'priority' => 'urgent',
        'status' => 'open',
    ]);

    $response = $this->actingAs($this->admin)->get(route('whatsapp.monitor'));

    $response->assertStatus(200)
        ->assertSee('WA-TEST-001')
        ->assertSee('John Doe')
        ->assertSee('628123456789');
});
