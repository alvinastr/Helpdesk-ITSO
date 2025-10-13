<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_ticket()
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post('/tickets', [
                'subject' => 'Test Ticket',
                'description' => 'Deskripsi pengujian minimal 10 karakter',
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
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)
            ->post("/tickets/{$ticket->id}/reply", [
                'message' => 'Ini balasan user.'
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('ticket_threads', [
            'ticket_id' => $ticket->id,
            'message' => 'Ini balasan user.'
        ]);
    }

    /** @test */
    public function admin_can_approve_ticket()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create(['status' => 'pending_review']);
        $this->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/approve")
            ->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'open'
        ]);
    }

    /** @test */
    public function user_can_give_feedback_on_closed_ticket()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'status' => 'closed']);
        $this->actingAs($user)
            ->post("/tickets/{$ticket->id}/feedback", [
                'rating' => 5,
                'feedback' => 'Sangat membantu!'
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'rating' => 5,
            'feedback' => 'Sangat membantu!'
        ]);
    }
}
