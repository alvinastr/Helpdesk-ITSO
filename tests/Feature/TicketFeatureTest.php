<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class TicketFeatureTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    /** @test */
    public function user_can_create_ticket()
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post('/tickets', [
                'subject' => 'Test Ticket',
                'description' => 'Deskripsi pengujian minimal 10 karakter untuk memenuhi validasi',
                'reporter_nip' => '123456789',
                'reporter_name' => 'Test User',
                'reporter_email' => 'test@example.com',
                'reporter_department' => 'IT Department',
                'input_method' => 'manual',
                'channel' => 'portal',
                'category' => 'general',
                'priority' => 'medium'
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'subject' => 'Test Ticket',
            'user_id' => $user->id
        ]);
    }

    /** @test */
    public function user_can_reply_to_ticket()
    {
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        
        $this->actingAs($user)
            ->post("/tickets/{$ticket->id}/reply", [
                'message' => 'Ini balasan user yang lebih panjang untuk memenuhi validasi minimal 5 karakter.'
            ])
            ->assertRedirect();
            
        $this->assertDatabaseHas('ticket_threads', [
            'ticket_id' => $ticket->id,
            'message' => 'Ini balasan user yang lebih panjang untuk memenuhi validasi minimal 5 karakter.'
        ]);
    }

    /** @test */
    public function admin_can_approve_ticket()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create(['status' => 'pending_keluhan']);
        $this->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/approve")
            ->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'open'
        ]);
    }

}
