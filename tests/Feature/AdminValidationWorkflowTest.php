<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketThread;
use App\Models\TicketStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class AdminValidationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    /**
     * Make admin POST request without CSRF but with Admin middleware
     */
    protected function adminPost($uri, $data = [])
    {
        return $this->withoutMiddleware([
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class
            ])
            ->actingAs($this->admin)->post($uri, $data);
    }

    /** @test */
    public function admin_can_approve_pending_ticket()
    {
        // Create ticket in pending_review status (after VALIDASI SISTEM)
        $ticket = Ticket::factory()->create([
            'status' => 'pending_review',
            'user_id' => $this->user->id
        ]);

        // Test VALIDASI ADMIN - Approve path
        $response = $this->adminPost("/admin/tickets/{$ticket->id}/approve");

        $response->assertRedirect();
        
        // Verify ticket status changed to 'open'
        $ticket->refresh();
        $this->assertEquals('open', $ticket->status);
        $this->assertEquals($this->admin->id, $ticket->approved_by);
        $this->assertNotNull($ticket->approved_at);

        // Verify status history is recorded
        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'new_status' => 'open',
            'changed_by' => $this->admin->id
        ]);
    }

    /** @test */
    public function admin_can_reject_pending_ticket()
    {
        // Create ticket in pending_keluhan status
        $ticket = Ticket::factory()->create([
            'status' => 'pending_keluhan',
            'user_id' => $this->user->id
        ]);

        $rejectionReason = 'Informasi tidak lengkap. Harap sertakan screenshot error.';

        // Test VALIDASI ADMIN - Reject path
        $response = $this->adminPost("/admin/tickets/{$ticket->id}/reject", [
            'reason' => $rejectionReason
        ]);

        $response->assertRedirect();
        
        // Verify ticket status changed to 'rejected'
        $ticket->refresh();
        $this->assertEquals('rejected', $ticket->status);
        $this->assertEquals($rejectionReason, $ticket->rejection_reason);

        // Verify status history
        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'new_status' => 'rejected',
            'changed_by' => $this->admin->id
        ]);

        // Should trigger EMAIL/WA REJECT notification
        // (This would be tested in notification tests)
    }

    /** @test */
    public function admin_can_request_revision()
    {
        // Create approved ticket
        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'user_id' => $this->user->id,
            'approved_by' => $this->admin->id
        ]);

        $revisionNotes = 'Mohon tambahkan informasi: 1) Nomor seri perangkat 2) Langkah yang sudah dicoba';

        // Test REQUEST REVISION flow
        $response = $this->adminPost("/admin/tickets/{$ticket->id}/revision", [
            'message' => $revisionNotes
        ]);

        $response->assertRedirect();
        
        // Verify ticket status changed to pending_revision
        $ticket->refresh();
        $this->assertEquals('pending_revision', $ticket->status);

        // Verify revision request is recorded as thread
        $this->assertDatabaseHas('ticket_threads', [
            'ticket_id' => $ticket->id,
            'sender_type' => 'admin',
            'sender_id' => $this->admin->id
        ]);
    }

    /** @test */
    public function admin_can_update_ticket_status()
    {
        // Create open ticket
        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'user_id' => $this->user->id,
            'approved_by' => $this->admin->id
        ]);

        // Test UPDATE STATUS TICKET step
        $response = $this->adminPost("/admin/tickets/{$ticket->id}/update-status", [
            'status' => 'in_progress'
        ]);

        $response->assertRedirect();
        
        $ticket->refresh();
        $this->assertEquals('in_progress', $ticket->status);

        // Test moving to closed
        $response = $this->actingAs($this->admin)
            ->post("/admin/tickets/{$ticket->id}/update-status", [
                'status' => 'closed'
            ]);

        $ticket->refresh();
        $this->assertEquals('closed', $ticket->status);
        $this->assertNotNull($ticket->closed_at);
    }

    /** @test */
    public function admin_can_close_ticket_with_resolution()
    {
        // Create in_progress ticket
        $ticket = Ticket::factory()->create([
            'status' => 'in_progress',
            'user_id' => $this->user->id,
            'approved_by' => $this->admin->id
        ]);

        $resolutionNotes = 'Masalah telah diselesaikan. Driver printer telah diupdate ke versi terbaru.';

        // Test ISSUE RESOLVED - SET STATUS: CLOSED
        $response = $this->adminPost("/admin/tickets/{$ticket->id}/close", [
            'resolution_notes' => $resolutionNotes
        ]);

        $response->assertRedirect();
        
        $ticket->refresh();
        $this->assertEquals('closed', $ticket->status);
        $this->assertEquals($resolutionNotes, $ticket->resolution_notes);
        $this->assertNotNull($ticket->closed_at);

        // Verify status history
        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'new_status' => 'closed',
            'changed_by' => $this->admin->id
        ]);
    }

    /** @test */
    public function admin_can_assign_ticket_to_other_admin()
    {
        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'user_id' => $this->user->id
        ]);

        $assigneeAdmin = User::factory()->create(['role' => 'admin']);

        $response = $this->adminPost("/admin/tickets/{$ticket->id}/assign", [
            'user_id' => $assigneeAdmin->id
        ]);

        $response->assertRedirect();
        
        $ticket->refresh();
        $this->assertEquals($assigneeAdmin->id, $ticket->assigned_to);
    }

    /** @test */
    public function admin_can_view_pending_tickets()
    {
        // Create tickets in different statuses
        Ticket::factory()->create(['status' => 'pending_keluhan']);
        Ticket::factory()->create(['status' => 'pending_keluhan']);
        Ticket::factory()->create(['status' => 'open']);
        Ticket::factory()->create(['status' => 'closed']);

        $response = $this->actingAs($this->admin)
            ->get('/admin/pending-review');

        $response->assertStatus(200);
        
        // Should only show pending tickets for VALIDASI ADMIN step
        $response->assertSee('pending_keluhan');
    }

    /** @test */
    public function admin_dashboard_shows_ticket_statistics()
    {
        // Create tickets with various statuses
        Ticket::factory()->create(['status' => 'pending_review']);
        Ticket::factory()->create(['status' => 'open']);
        Ticket::factory()->create(['status' => 'in_progress']);
        Ticket::factory()->create(['status' => 'closed']);
        Ticket::factory()->create(['status' => 'rejected']);

        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        
        // Should display statistics for monitoring workflow
        $response->assertSee('Dashboard');
    }

    /** @test */
    public function regular_user_cannot_access_admin_functions()
    {
        // Create a ticket
        $ticket = Ticket::factory()->create([
            'status' => 'pending',
            'user_id' => $this->user->id
        ]);

        // Debug: Check user role
        $this->assertEquals('user', $this->user->role);

        // Try to access admin dashboard first (simpler route)
        $response = $this->actingAs($this->user)
            ->get("/admin/dashboard");
            
        // Should get 403 for admin dashboard
        $response->assertStatus(403);

        // Then try admin ticket operation
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-CSRF-TOKEN' => csrf_token()])
            ->post("/admin/tickets/{$ticket->id}/approve");

        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function admin_can_add_internal_notes()
    {
        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'user_id' => $this->user->id
        ]);

        // Test UPDATE THREAD - Admin response using simplified approach
        $response = $this->actingAs($this->admin)
            ->post("/tickets/{$ticket->id}/reply", [
                'message' => 'Tim teknis akan menangani masalah ini dalam 1x24 jam.',
                'is_internal' => false // Public response
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ticket_threads', [
            'ticket_id' => $ticket->id,
            'message' => 'Tim teknis akan menangani masalah ini dalam 1x24 jam.',
            'sender_type' => 'admin',
            'sender_id' => $this->admin->id
        ]);
    }

    /** @test */
    public function workflow_generates_proper_ticket_numbers()
    {
        // Test GENERATE TIKET ID from flowchart
        $ticket = Ticket::factory()->create();
        
        $this->assertNotNull($ticket->ticket_number);
        $this->assertStringStartsWith('TKT-', $ticket->ticket_number);
        $this->assertStringContainsString(date('Ymd'), $ticket->ticket_number);
    }

    /** @test */
    public function system_tracks_complete_workflow_history()
    {
        $ticket = Ticket::factory()->create(['status' => 'pending_keluhan']);

        // Simulate complete workflow
        
        // 1. Admin approves
        $this->adminPost("/admin/tickets/{$ticket->id}/approve");

        // 2. Update to in_progress
        $this->adminPost("/admin/tickets/{$ticket->id}/update-status", [
            'status' => 'in_progress'
        ]);

        // 3. Close ticket
        $this->adminPost("/admin/tickets/{$ticket->id}/close", [
            'resolution_notes' => 'Issue resolved successfully'
        ]);

        // Verify complete history is tracked
        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'new_status' => 'open'
        ]);
        
        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'new_status' => 'in_progress'
        ]);
        
        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'new_status' => 'closed'
        ]);
    }
}