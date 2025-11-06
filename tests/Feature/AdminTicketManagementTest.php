<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class AdminTicketManagementTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    /** @test */
    public function admin_can_access_admin_dashboard()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    /** @test */
    public function admin_can_view_pending_tickets()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Ticket::factory()->count(3)->create(['status' => 'pending_review']);
        
        $response = $this->actingAs($admin)->get('/admin/pending-review');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_approve_ticket()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create(['status' => 'pending_keluhan']);
        
        $response = $this->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/approve");
        
        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'open',
            'approved_by' => $admin->id
        ]);
    }

    /** @test */
    public function admin_can_reject_ticket()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create(['status' => 'pending_review']);
        
        $response = $this->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/reject", [
                'reason' => 'Ticket tidak memenuhi kriteria yang diperlukan untuk diproses'
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'rejected'
        ]);
    }

    /** @test */
    public function admin_can_request_ticket_revision()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create(['status' => 'pending_review']);
        
        $response = $this->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/revision", [
                'reason' => 'Mohon lengkapi informasi tambahan mengenai masalah yang dihadapi'
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'need_revision'
        ]);
    }

    /** @test */
    public function admin_can_assign_ticket_to_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $assignee = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create(['status' => 'open']);
        
        $response = $this->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/assign", [
                'assigned_to' => $assignee->id
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_to' => $assignee->id
        ]);
    }

    /** @test */
    public function admin_can_update_ticket_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create(['status' => 'open']);
        
        $response = $this->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/update-status", [
                'status' => 'in_progress',
                'notes' => 'Sedang dalam proses penanganan oleh tim teknis'
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress'
        ]);
    }

    /** @test */
    public function admin_can_close_ticket()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create(['status' => 'resolved']);
        
        $response = $this->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/close", [
                'resolution_notes' => 'Masalah telah selesai diselesaikan dengan baik'
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'closed'
        ]);
        
        $ticket->refresh();
        $this->assertNotNull($ticket->closed_at);
    }

    /** @test */
    public function admin_can_add_note_to_ticket()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create();
        
        $response = $this->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/add-note", [
                'note' => 'Catatan internal untuk tim mengenai penanganan ticket ini'
            ]);
        
        $response->assertRedirect();
    }

    /** @test */
    public function admin_can_create_ticket_manually()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)
            ->get('/admin/tickets/create');
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.tickets.create');
    }

    /** @test */
    public function admin_can_store_manual_ticket()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)
            ->post('/admin/tickets', [
                'subject' => 'Manual Ticket dari Admin',
                'description' => 'Deskripsi ticket yang dibuat secara manual oleh admin',
                'reporter_nip' => '987654321',
                'reporter_name' => 'Reporter Name',
                'reporter_email' => 'reporter@example.com',
                'reporter_department' => 'Finance Department',
                'reporter_phone' => '081234567890',
                'category' => 'technical',
                'priority' => 'high',
                'channel' => 'phone',
                'input_method' => 'manual'
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'subject' => 'Manual Ticket dari Admin',
            'input_method' => 'manual'
        ]);
    }

    /** @test */
    public function regular_user_cannot_access_admin_functions()
    {
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->create();
        
        $response = $this->actingAs($user)
            ->post("/admin/tickets/{$ticket->id}/approve");
        
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_dashboard_shows_statistics()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create various tickets
        Ticket::factory()->create(['status' => 'pending_review']);
        Ticket::factory()->create(['status' => 'open']);
        Ticket::factory()->create(['status' => 'resolved']);
        Ticket::factory()->create(['status' => 'closed', 'closed_at' => now()]);
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewHas('stats');
    }

    /** @test */
    public function admin_can_view_all_tickets_regardless_of_owner()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $ticket1 = Ticket::factory()->create(['user_id' => $user1->id]);
        $ticket2 = Ticket::factory()->create(['user_id' => $user2->id]);
        
        $response = $this->actingAs($admin)->get("/admin/tickets/{$ticket1->id}");
        $response->assertStatus(200);
        
        $response = $this->actingAs($admin)->get("/admin/tickets/{$ticket2->id}");
        $response->assertStatus(200);
    }
}
