<?php

use App\Models\WhatsAppTicket;
use App\Models\User;
use App\Models\WhatsAppTicketResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create whatsapp ticket', function () {
    $ticket = WhatsAppTicket::create([
        'ticket_number' => 'WA-TEST-001',
        'sender_wa_id' => '628123456789@c.us',
        'sender_phone' => '628123456789',
        'sender_name' => 'John Doe',
        'subject' => 'Test Ticket',
        'description' => 'This is a test ticket',
        'original_message' => 'This is the original message',
        'category' => 'network',
        'priority' => 'normal',
        'status' => 'open',
        'source' => 'whatsapp',
    ]);

    expect($ticket)->toBeInstanceOf(WhatsAppTicket::class)
        ->and($ticket->ticket_number)->toBe('WA-TEST-001')
        ->and($ticket->sender_name)->toBe('John Doe')
        ->and($ticket->status)->toBe('open');
});

test('whatsapp ticket has correct fillable attributes', function () {
    $fillable = (new WhatsAppTicket())->getFillable();

    expect($fillable)->toContain('ticket_number')
        ->and($fillable)->toContain('sender_phone')
        ->and($fillable)->toContain('category')
        ->and($fillable)->toContain('priority')
        ->and($fillable)->toContain('status');
});

test('whatsapp ticket casts dates correctly', function () {
    $ticket = WhatsAppTicket::factory()->create([
        'wa_timestamp' => '2024-01-01 10:00:00',
        'resolved_at' => '2024-01-01 12:00:00',
    ]);

    expect($ticket->wa_timestamp)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($ticket->resolved_at)->toBeInstanceOf(Carbon\Carbon::class);
});

test('whatsapp ticket casts booleans correctly', function () {
    $ticket = WhatsAppTicket::factory()->create([
        'is_group' => true,
        'has_media' => false,
        'sla_breached' => true,
    ]);

    expect($ticket->is_group)->toBeBool()->toBeTrue()
        ->and($ticket->has_media)->toBeBool()->toBeFalse()
        ->and($ticket->sla_breached)->toBeBool()->toBeTrue();
});

test('whatsapp ticket belongs to user', function () {
    $user = User::factory()->create();
    $ticket = WhatsAppTicket::factory()->create([
        'assigned_to' => $user->id,
    ]);

    expect($ticket->assignedTo)->toBeInstanceOf(User::class)
        ->and($ticket->assignedTo->id)->toBe($user->id);
});

test('whatsapp ticket has many responses', function () {
    $ticket = WhatsAppTicket::factory()->create();
    
    WhatsAppTicketResponse::factory()->count(3)->create([
        'ticket_id' => $ticket->id,
    ]);

    expect($ticket->responses)->toHaveCount(3)
        ->and($ticket->responses->first())->toBeInstanceOf(WhatsAppTicketResponse::class);
});

test('scope open returns only open tickets', function () {
    WhatsAppTicket::factory()->create(['status' => 'open']);
    WhatsAppTicket::factory()->create(['status' => 'closed']);
    WhatsAppTicket::factory()->create(['status' => 'open']);

    $openTickets = WhatsAppTicket::open()->get();

    expect($openTickets)->toHaveCount(2)
        ->and($openTickets->every(fn($ticket) => $ticket->status === 'open'))->toBeTrue();
});

test('scope urgent returns only urgent tickets', function () {
    WhatsAppTicket::factory()->create(['priority' => 'urgent']);
    WhatsAppTicket::factory()->create(['priority' => 'normal']);
    WhatsAppTicket::factory()->create(['priority' => 'urgent']);

    $urgentTickets = WhatsAppTicket::urgent()->get();

    expect($urgentTickets)->toHaveCount(2)
        ->and($urgentTickets->every(fn($ticket) => $ticket->priority === 'urgent'))->toBeTrue();
});

test('scope by category filters by category', function () {
    WhatsAppTicket::factory()->create(['category' => 'network']);
    WhatsAppTicket::factory()->create(['category' => 'hardware']);
    WhatsAppTicket::factory()->create(['category' => 'network']);

    $networkTickets = WhatsAppTicket::byCategory('network')->get();

    expect($networkTickets)->toHaveCount(2)
        ->and($networkTickets->every(fn($ticket) => $ticket->category === 'network'))->toBeTrue();
});

test('scope unassigned returns tickets without assigned users', function () {
    WhatsAppTicket::factory()->create(['assigned_to' => null]);
    WhatsAppTicket::factory()->create(['assigned_to' => User::factory()->create()->id]);
    WhatsAppTicket::factory()->create(['assigned_to' => null]);

    $unassignedTickets = WhatsAppTicket::unassigned()->get();

    expect($unassignedTickets)->toHaveCount(2)
        ->and($unassignedTickets->every(fn($ticket) => $ticket->assigned_to === null))->toBeTrue();
});

test('scope assigned to returns tickets for specific user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    WhatsAppTicket::factory()->create(['assigned_to' => $user1->id]);
    WhatsAppTicket::factory()->create(['assigned_to' => $user2->id]);
    WhatsAppTicket::factory()->create(['assigned_to' => $user1->id]);

    $userTickets = WhatsAppTicket::where('assigned_to', $user1->id)->get();

    expect($userTickets)->toHaveCount(2)
        ->and($userTickets->every(fn($ticket) => $ticket->assigned_to === $user1->id))->toBeTrue();
});

test('raw data is cast to array', function () {
    $rawData = ['key' => 'value', 'nested' => ['data' => 'test']];
    
    $ticket = WhatsAppTicket::factory()->create([
        'raw_data' => $rawData,
    ]);

    expect($ticket->raw_data)->toBeArray()
        ->and($ticket->raw_data)->toBe($rawData);
});

test('time tracking is cast to array', function () {
    $timeTracking = [
        'started' => '2024-01-01 10:00:00',
        'paused' => '2024-01-01 11:00:00',
    ];
    
    $ticket = WhatsAppTicket::factory()->create([
        'time_tracking' => $timeTracking,
    ]);

    expect($ticket->time_tracking)->toBeArray()
        ->and($ticket->time_tracking)->toBe($timeTracking);
});
