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
    public function user_can_view_ticket_list()
    {
        $user = User::factory()->create();
        Ticket::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/tickets');
        $response->assertStatus(200);
        $response->assertViewIs('tickets.index');
    }

    /** @test */
    public function user_can_view_single_ticket()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/tickets/{$ticket->id}");
        $response->assertStatus(200);
        $response->assertViewIs('tickets.show');
        $response->assertSee($ticket->subject);
    }

    /** @test */
    public function user_can_view_create_ticket_form()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/tickets/create');
        $response->assertStatus(200);
        $response->assertViewIs('tickets.create');
    }

    /** @test */
    public function user_cannot_create_ticket_without_required_fields()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->post('/tickets', []);
        
        $response->assertSessionHasErrors(['subject', 'description']);
    }

    /** @test */
    public function user_can_search_tickets()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'subject' => 'Unique Search Term'
        ]);

        $response = $this->actingAs($user)
            ->get('/tickets?search=Unique');
        
        $response->assertStatus(200);
        $response->assertSee('Unique Search Term');
    }

    /** @test */
    public function user_can_filter_tickets_by_status()
    {
        $user = User::factory()->create();
        Ticket::factory()->create([
            'user_id' => $user->id,
            'status' => 'open'
        ]);
        Ticket::factory()->create([
            'user_id' => $user->id,
            'status' => 'closed'
        ]);

        $response = $this->actingAs($user)
            ->get('/tickets?status=open');
        
        $response->assertStatus(200);
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
    public function user_cannot_view_other_users_tickets()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->get("/tickets/{$ticket->id}");
        // Should be forbidden or redirected
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }

    /** @test */
    public function ticket_has_correct_default_status()
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
                'category' => 'general',
                'priority' => 'medium'
            ]);

        $ticket = Ticket::latest()->first();
        $this->assertNotNull($ticket);
        $this->assertContains($ticket->status, ['pending_keluhan', 'open', 'pending_review']);
    }
}
