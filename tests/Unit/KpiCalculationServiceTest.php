<?php

use App\Models\Ticket;
use App\Services\KpiCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->kpiService = new KpiCalculationService();
});

test('can calculate response time for ticket', function () {
    $emailReceived = Carbon::parse('2024-01-01 10:00:00');
    $firstResponse = Carbon::parse('2024-01-01 10:30:00');
    
    $ticket = Ticket::factory()->create([
        'email_received_at' => $emailReceived,
        'first_response_at' => $firstResponse,
    ]);

    $this->kpiService->updateTicketKpiMetrics($ticket);
    
    $ticket->refresh();

    expect($ticket->response_time_minutes)->toBe(30);
});

test('can calculate resolution time for ticket', function () {
    $emailReceived = Carbon::parse('2024-01-01 10:00:00');
    $resolved = Carbon::parse('2024-01-01 14:00:00');
    
    $ticket = Ticket::factory()->create([
        'email_received_at' => $emailReceived,
        'resolved_at' => $resolved,
    ]);

    $this->kpiService->updateTicketKpiMetrics($ticket);
    
    $ticket->refresh();

    expect($ticket->resolution_time_minutes)->toBe(240);
});

test('can calculate ticket creation delay', function () {
    $emailReceived = Carbon::parse('2024-01-01 10:00:00');
    $created = Carbon::parse('2024-01-01 10:15:00');
    
    $ticket = Ticket::factory()->create([
        'email_received_at' => $emailReceived,
        'created_at' => $created,
    ]);

    $this->kpiService->updateTicketKpiMetrics($ticket);
    
    $ticket->refresh();

    expect($ticket->ticket_creation_delay_minutes)->toBe(15);
});

test('sets first response time if not exists', function () {
    $ticket = Ticket::factory()->create([
        'first_response_at' => null,
    ]);

    $responseTime = Carbon::parse('2024-01-01 11:00:00');
    $this->kpiService->setFirstResponseTime($ticket, $responseTime);
    
    $ticket->refresh();

    expect($ticket->first_response_at)->not->toBeNull()
        ->and($ticket->first_response_at->toDateTimeString())->toBe('2024-01-01 11:00:00');
});

test('does not override existing first response time', function () {
    $existingResponseTime = Carbon::parse('2024-01-01 10:00:00');
    
    $ticket = Ticket::factory()->create([
        'first_response_at' => $existingResponseTime,
    ]);

    $newResponseTime = Carbon::parse('2024-01-01 12:00:00');
    $this->kpiService->setFirstResponseTime($ticket, $newResponseTime);
    
    $ticket->refresh();

    expect($ticket->first_response_at->toDateTimeString())->toBe('2024-01-01 10:00:00');
});

test('sets resolved time', function () {
    $ticket = Ticket::factory()->create([
        'resolved_at' => null,
    ]);

    $resolvedTime = Carbon::parse('2024-01-01 15:00:00');
    $this->kpiService->setResolvedTime($ticket, $resolvedTime);
    
    $ticket->refresh();

    expect($ticket->resolved_at)->not->toBeNull()
        ->and($ticket->resolved_at->toDateTimeString())->toBe('2024-01-01 15:00:00');
});

test('calculates average response time', function () {
    // Create tickets with different response times
    Ticket::factory()->create(['response_time_minutes' => 30]);
    Ticket::factory()->create(['response_time_minutes' => 60]);
    Ticket::factory()->create(['response_time_minutes' => 90]);

    $average = $this->kpiService->getAverageResponseTime();

    expect($average)->toBe(60.0);
});

test('calculates average resolution time', function () {
    // Create tickets with different resolution times
    Ticket::factory()->create(['resolution_time_minutes' => 120]);
    Ticket::factory()->create(['resolution_time_minutes' => 240]);
    Ticket::factory()->create(['resolution_time_minutes' => 180]);

    $average = $this->kpiService->getAverageResolutionTime();

    expect($average)->toBe(180.0);
});

test('update kpi metrics handles missing dates gracefully', function () {
    $ticket = Ticket::factory()->create([
        'email_received_at' => null,
        'first_response_at' => null,
        'resolved_at' => null,
    ]);

    $this->kpiService->updateTicketKpiMetrics($ticket);
    
    $ticket->refresh();

    expect($ticket->response_time_minutes)->toBeNull()
        ->and($ticket->resolution_time_minutes)->toBeNull()
        ->and($ticket->ticket_creation_delay_minutes)->toBeNull();
});

test('first response time uses current time if not provided', function () {
    Carbon::setTestNow('2024-01-01 12:00:00');
    
    $ticket = Ticket::factory()->create([
        'first_response_at' => null,
    ]);

    $this->kpiService->setFirstResponseTime($ticket);
    
    $ticket->refresh();

    expect($ticket->first_response_at->toDateTimeString())->toBe('2024-01-01 12:00:00');
    
    Carbon::setTestNow();
});

test('resolved time uses current time if not provided', function () {
    Carbon::setTestNow('2024-01-01 16:00:00');
    
    $ticket = Ticket::factory()->create([
        'resolved_at' => null,
    ]);

    $this->kpiService->setResolvedTime($ticket);
    
    $ticket->refresh();

    expect($ticket->resolved_at->toDateTimeString())->toBe('2024-01-01 16:00:00');
    
    Carbon::setTestNow();
});

test('calculates all kpi metrics in one update', function () {
    $emailReceived = Carbon::parse('2024-01-01 10:00:00');
    $firstResponse = Carbon::parse('2024-01-01 10:45:00');
    $resolved = Carbon::parse('2024-01-01 13:00:00');
    $created = Carbon::parse('2024-01-01 10:05:00');
    
    $ticket = Ticket::factory()->create([
        'email_received_at' => $emailReceived,
        'first_response_at' => $firstResponse,
        'resolved_at' => $resolved,
        'created_at' => $created,
    ]);

    $this->kpiService->updateTicketKpiMetrics($ticket);
    
    $ticket->refresh();

    expect($ticket->response_time_minutes)->toBe(45)
        ->and($ticket->resolution_time_minutes)->toBe(180)
        ->and($ticket->ticket_creation_delay_minutes)->toBe(5);
});
