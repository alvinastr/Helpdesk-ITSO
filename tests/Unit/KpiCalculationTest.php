<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Ticket;
use App\Models\User;
use App\Services\KpiCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class KpiCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected $kpiService;
    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->kpiService = new KpiCalculationService();
        
        // Create test users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    /**
     * Test ticket creation delay calculation
     */
    public function test_ticket_creation_delay_is_calculated_correctly()
    {
        $emailReceivedAt = Carbon::parse('2025-10-21 13:00:00');
        $createdAt = Carbon::parse('2025-10-25 10:00:00');

        $ticket = Ticket::factory()->create([
            'email_received_at' => $emailReceivedAt,
            'created_at' => $createdAt,
        ]);

        $ticket->calculateTicketCreationDelay();

        // Expected: ~94 hours (allow 1 hour tolerance for DST/timezone)
        $this->assertEqualsWithDelta(5640, $ticket->ticket_creation_delay_minutes, 60);
    }

    /**
     * Test response time calculation from email_received_at
     */
    public function test_response_time_calculated_from_email_received_at()
    {
        $emailReceivedAt = Carbon::parse('2025-10-25 09:00:00');
        $firstResponseAt = Carbon::parse('2025-10-25 09:25:00');

        $ticket = Ticket::factory()->create([
            'email_received_at' => $emailReceivedAt,
            'first_response_at' => $firstResponseAt,
            'created_at' => Carbon::parse('2025-10-25 09:10:00'),
        ]);

        $ticket->calculateResponseTime();

        // Expected: 25 minutes from email_received_at
        $this->assertEquals(25, $ticket->response_time_minutes);
    }

    /**
     * Test response time calculation from created_at when no email_received_at
     */
    public function test_response_time_calculated_from_created_at_when_no_email()
    {
        $createdAt = Carbon::parse('2025-10-25 10:00:00');
        $firstResponseAt = Carbon::parse('2025-10-25 10:15:00');

        $ticket = Ticket::factory()->create([
            'email_received_at' => null,
            'first_response_at' => $firstResponseAt,
            'created_at' => $createdAt,
        ]);

        $ticket->calculateResponseTime();

        // Expected: 15 minutes from created_at
        $this->assertEquals(15, $ticket->response_time_minutes);
    }

    /**
     * Test resolution time calculation from email_received_at
     */
    public function test_resolution_time_calculated_from_email_received_at()
    {
        $emailReceivedAt = Carbon::parse('2025-10-25 09:00:00');
        $resolvedAt = Carbon::parse('2025-10-26 10:00:00');

        $ticket = Ticket::factory()->create([
            'email_received_at' => $emailReceivedAt,
            'resolved_at' => $resolvedAt,
            'created_at' => Carbon::parse('2025-10-25 09:10:00'),
        ]);

        $ticket->calculateResolutionTime();

        // Expected: 25 hours = 1500 minutes
        $this->assertEquals(1500, $ticket->resolution_time_minutes);
    }

    /**
     * Test resolution time calculation from created_at when no email_received_at
     */
    public function test_resolution_time_calculated_from_created_at_when_no_email()
    {
        $createdAt = Carbon::parse('2025-10-25 10:00:00');
        $resolvedAt = Carbon::parse('2025-10-25 14:00:00');

        $ticket = Ticket::factory()->create([
            'email_received_at' => null,
            'resolved_at' => $resolvedAt,
            'created_at' => $createdAt,
        ]);

        $ticket->calculateResolutionTime();

        // Expected: 4 hours = 240 minutes
        $this->assertEquals(240, $ticket->resolution_time_minutes);
    }

    /**
     * Test SLA compliance check for response time
     */
    public function test_response_time_within_sla_target()
    {
        // Within SLA: 25 minutes (target: 30 minutes)
        $ticketGood = Ticket::factory()->create([
            'response_time_minutes' => 25,
        ]);
        $this->assertTrue($ticketGood->isResponseTimeWithinTarget());

        // Outside SLA: 45 minutes
        $ticketBad = Ticket::factory()->create([
            'response_time_minutes' => 45,
        ]);
        $this->assertFalse($ticketBad->isResponseTimeWithinTarget());

        // Exactly at SLA: 30 minutes
        $ticketExact = Ticket::factory()->create([
            'response_time_minutes' => 30,
        ]);
        $this->assertTrue($ticketExact->isResponseTimeWithinTarget());
    }

    /**
     * Test SLA compliance check for resolution time
     */
    public function test_resolution_time_within_sla_target()
    {
        // Within SLA: 24 hours = 1440 minutes (target: 48 hours = 2880 minutes)
        $ticketGood = Ticket::factory()->create([
            'resolution_time_minutes' => 1440,
        ]);
        $this->assertTrue($ticketGood->isResolutionTimeWithinTarget());

        // Outside SLA: 72 hours = 4320 minutes
        $ticketBad = Ticket::factory()->create([
            'resolution_time_minutes' => 4320,
        ]);
        $this->assertFalse($ticketBad->isResolutionTimeWithinTarget());

        // Exactly at SLA: 48 hours = 2880 minutes
        $ticketExact = Ticket::factory()->create([
            'resolution_time_minutes' => 2880,
        ]);
        $this->assertTrue($ticketExact->isResolutionTimeWithinTarget());
    }

    /**
     * Test formatted time display
     */
    public function test_response_time_formatted_correctly()
    {
        // Less than 1 hour
        $ticket1 = Ticket::factory()->create(['response_time_minutes' => 45]);
        $this->assertEquals('45 menit', $ticket1->getResponseTimeFormatted());

        // Exactly 1 hour
        $ticket2 = Ticket::factory()->create(['response_time_minutes' => 60]);
        $this->assertEquals('1 jam 0 menit', $ticket2->getResponseTimeFormatted());

        // 2.5 hours
        $ticket3 = Ticket::factory()->create(['response_time_minutes' => 150]);
        $this->assertEquals('2 jam 30 menit', $ticket3->getResponseTimeFormatted());

        // More than 24 hours
        $ticket4 = Ticket::factory()->create(['resolution_time_minutes' => 1500]);
        $this->assertEquals('1 hari 1 jam 0 menit', $ticket4->getResolutionTimeFormatted());
    }

    /**
     * Test KPI summary calculation
     */
    public function test_kpi_summary_calculates_correctly()
    {
        // Create 5 tickets with various KPI data
        Ticket::factory()->create([
            'first_response_at' => now(),
            'response_time_minutes' => 20, // Within SLA
            'resolved_at' => now(),
            'resolution_time_minutes' => 1440, // Within SLA
        ]);

        Ticket::factory()->create([
            'first_response_at' => now(),
            'response_time_minutes' => 50, // Outside SLA
            'resolved_at' => now(),
            'resolution_time_minutes' => 3000, // Outside SLA
        ]);

        Ticket::factory()->create([
            'first_response_at' => now(),
            'response_time_minutes' => 15,
            'resolved_at' => null, // Not resolved yet
            'resolution_time_minutes' => null,
        ]);

        Ticket::factory()->create([
            'first_response_at' => null, // No response yet
            'resolved_at' => null,
        ]);

        Ticket::factory()->create([
            'first_response_at' => now(),
            'response_time_minutes' => 25,
            'resolved_at' => now(),
            'resolution_time_minutes' => 2000,
        ]);

        $summary = $this->kpiService->getKpiSummary([]);

        $this->assertEquals(5, $summary['total_tickets']);
        $this->assertEquals(4, $summary['tickets_with_response']);
        $this->assertEquals(80, $summary['response_rate']); // 4/5 = 80%
        $this->assertEquals(3, $summary['tickets_resolved']);

        // SLA Compliance: 3 out of 4 responded tickets meet response SLA = 75%
        $this->assertEquals(75, $summary['sla_response_compliance']);

        // SLA Compliance: 2 out of 3 resolved tickets meet resolution SLA = 66.67%
        $this->assertEqualsWithDelta(67, $summary['sla_resolution_compliance'], 0.5);
    }

    /**
     * Test KPI by priority aggregation
     */
    public function test_kpi_by_priority_aggregates_correctly()
    {
        // Create tickets with different priorities
        Ticket::factory()->create([
            'priority' => 'critical',
            'response_time_minutes' => 10,
            'resolution_time_minutes' => 100,
        ]);

        Ticket::factory()->create([
            'priority' => 'critical',
            'response_time_minutes' => 20,
            'resolution_time_minutes' => 200,
        ]);

        Ticket::factory()->create([
            'priority' => 'high',
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 300,
        ]);

        $byPriority = $this->kpiService->getKpiByPriority([]);

        // Check critical priority
        $critical = collect($byPriority)->firstWhere('priority', 'critical');
        $this->assertEquals(2, $critical['total_tickets']);
        $this->assertEquals(15, $critical['avg_response_time']); // (10 + 20) / 2
        $this->assertEquals(150, $critical['avg_resolution_time']); // (100 + 200) / 2

        // Check high priority
        $high = collect($byPriority)->firstWhere('priority', 'high');
        $this->assertEquals(1, $high['total_tickets']);
        $this->assertEquals(30, $high['avg_response_time']);
    }

    /**
     * Test KPI by category aggregation
     */
    public function test_kpi_by_category_aggregates_correctly()
    {
        Ticket::factory()->create([
            'category' => 'Technical',
            'response_time_minutes' => 20,
            'resolution_time_minutes' => 200,
        ]);

        Ticket::factory()->create([
            'category' => 'Technical',
            'response_time_minutes' => 40,
            'resolution_time_minutes' => 400,
        ]);

        Ticket::factory()->create([
            'category' => 'Billing',
            'response_time_minutes' => 50,
            'resolution_time_minutes' => 500,
        ]);

        $byCategory = $this->kpiService->getKpiByCategory([]);

        $technical = collect($byCategory)->firstWhere('category', 'Technical');
        $this->assertEquals(2, $technical['total_tickets']);
        $this->assertEquals(30, $technical['avg_response_time']); // (20 + 40) / 2
        $this->assertEquals(300, $technical['avg_resolution_time']); // (200 + 400) / 2

        $billing = collect($byCategory)->firstWhere('category', 'Billing');
        $this->assertEquals(1, $billing['total_tickets']);
        $this->assertEquals(50, $billing['avg_response_time']);
    }

    /**
     * Test filter by date range
     */
    public function test_kpi_filters_by_date_range()
    {
        // Old ticket
        Ticket::factory()->create([
            'created_at' => Carbon::parse('2025-10-01'),
            'response_time_minutes' => 10,
        ]);

        // Recent ticket
        Ticket::factory()->create([
            'created_at' => Carbon::parse('2025-10-25'),
            'response_time_minutes' => 20,
        ]);

        $filters = [
            'date_from' => '2025-10-20',
            'date_to' => '2025-10-31',
        ];

        $summary = $this->kpiService->getKpiSummary($filters);

        // Should only count the recent ticket
        $this->assertEquals(1, $summary['total_tickets']);
    }

    /**
     * Test filter by category
     */
    public function test_kpi_filters_by_category()
    {
        Ticket::factory()->create([
            'category' => 'Technical',
            'response_time_minutes' => 10,
        ]);

        Ticket::factory()->create([
            'category' => 'Billing',
            'response_time_minutes' => 20,
        ]);

        $filters = ['category' => 'Technical'];
        $summary = $this->kpiService->getKpiSummary($filters);

        $this->assertEquals(1, $summary['total_tickets']);
    }

    /**
     * Test filter by priority
     */
    public function test_kpi_filters_by_priority()
    {
        Ticket::factory()->create([
            'priority' => 'critical',
            'response_time_minutes' => 10,
        ]);

        Ticket::factory()->create([
            'priority' => 'low',
            'response_time_minutes' => 50,
        ]);

        $filters = ['priority' => 'critical'];
        $summary = $this->kpiService->getKpiSummary($filters);

        $this->assertEquals(1, $summary['total_tickets']);
    }

    /**
     * Test null handling
     */
    public function test_kpi_handles_null_values_correctly()
    {
        $ticket = Ticket::factory()->create([
            'email_received_at' => null,
            'first_response_at' => null,
            'resolved_at' => null,
        ]);

        $ticket->calculateTicketCreationDelay();
        $ticket->calculateResponseTime();
        $ticket->calculateResolutionTime();

        $this->assertNull($ticket->ticket_creation_delay_minutes);
        $this->assertNull($ticket->response_time_minutes);
        $this->assertNull($ticket->resolution_time_minutes);
    }

    /**
     * Test edge case: response at exactly SLA boundary
     */
    public function test_sla_boundary_conditions()
    {
        // Exactly 30 minutes should PASS
        $ticket30 = Ticket::factory()->create(['response_time_minutes' => 30]);
        $this->assertTrue($ticket30->isResponseTimeWithinTarget());

        // 31 minutes should FAIL
        $ticket31 = Ticket::factory()->create(['response_time_minutes' => 31]);
        $this->assertFalse($ticket31->isResponseTimeWithinTarget());

        // Exactly 2880 minutes (48 hours) should PASS
        $ticket2880 = Ticket::factory()->create(['resolution_time_minutes' => 2880]);
        $this->assertTrue($ticket2880->isResolutionTimeWithinTarget());

        // 2881 minutes should FAIL
        $ticket2881 = Ticket::factory()->create(['resolution_time_minutes' => 2881]);
        $this->assertFalse($ticket2881->isResolutionTimeWithinTarget());
    }
}
