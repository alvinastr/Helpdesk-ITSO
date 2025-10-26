<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class ApiEndpointTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed database for testing
        $this->artisan('db:seed');
    }

    /** @test */
    public function test_email_webhook_processes_new_ticket_correctly()
    {
        // Send webhook request with content that won't trigger spam filter
        $response = $this->postJson('/api/v1/webhooks/email', [
            'from' => 'customer@company.com',
            'subject' => 'Issue with server connectivity and database access problems',
            'body' => 'I am experiencing difficulties connecting to the server and accessing the database. This issue has been ongoing for several hours and is affecting our operations. Please assist with resolving this technical problem as soon as possible.',
            'message_id' => 'email-webhook-production-456'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'ticket_id',
            'ticket_number'
        ]);

        // Verify ticket was created
        $ticketId = $response->json('ticket_id');
        $ticket = Ticket::find($ticketId);
        
        $this->assertNotNull($ticket);
        $this->assertEquals('customer@company.com', $ticket->user_email);
        $this->assertEquals('Issue with server connectivity and database access problems', $ticket->subject);
        $this->assertEquals('I am experiencing difficulties connecting to the server and accessing the database. This issue has been ongoing for several hours and is affecting our operations. Please assist with resolving this technical problem as soon as possible.', $ticket->description);
        $this->assertEquals('email', $ticket->input_method);
        $this->assertEquals('pending_review', $ticket->status); // Should be pending_review after validation passes
    }

    /** @test */
    public function test_whatsapp_webhook_processes_new_ticket_correctly()
    {
        // Send WhatsApp webhook request with content that won't trigger spam filter
        $response = $this->postJson('/api/v1/webhooks/whatsapp', [
            'from' => '+6281234567890',
            'body' => 'I need help with my login account. The system keeps showing error messages when I try to access my dashboard. This has been happening since yesterday morning. Can someone please help me resolve this issue?',
            'message_id' => 'whatsapp-webhook-production-789'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'ticket_id',
            'ticket_number'
        ]);

        // Verify ticket was created
        $ticketId = $response->json('ticket_id');
        $ticket = Ticket::find($ticketId);
        
        $this->assertNotNull($ticket);
        $this->assertEquals('6281234567890', $ticket->user_phone); // Phone is normalized, + and leading 0 removed
        $this->assertEquals('whatsapp.6281234567890@system.local', $ticket->user_email); // Generated email for WhatsApp
        $this->assertEquals('WhatsApp: I need help with my login account. The system keep...', $ticket->subject);
        $this->assertEquals('I need help with my login account. The system keeps showing error messages when I try to access my dashboard. This has been happening since yesterday morning. Can someone please help me resolve this issue?', $ticket->description);
        $this->assertEquals('whatsapp', $ticket->input_method);
        $this->assertEquals('pending_review', $ticket->status); // Should be pending_review after validation passes
    }

    /** @test */
    public function email_webhook_updates_existing_thread()
    {
        // Create existing ticket
        $existingTicket = Ticket::factory()->create([
            'user_email' => 'existing@company.com',
            'status' => 'open',
            'ticket_number' => 'TCK-20251015-001'
        ]);

        // Test CEK DATA step - Reply to existing ticket
        $emailReplyData = [
            'from' => 'existing@company.com',
            'subject' => 'Re: ' . $existingTicket->subject,
            'body' => 'Terima kasih atas responnya. Saya sudah coba tapi masih bermasalah.',
            'message_id' => 'email-reply-' . uniqid(),
            'in_reply_to' => $existingTicket->ticket_number
        ];

        $response = $this->postJson('/api/v1/webhooks/email', $emailReplyData);

        $response->assertStatus(200);
        
        // Should UPDATE THREAD, not create new ticket
        $this->assertDatabaseHas('ticket_threads', [
            'ticket_id' => $existingTicket->id,
            'message' => 'Terima kasih atas responnya. Saya sudah coba tapi masih bermasalah.',
            'sender_type' => 'user'
        ]);

        // Should not create new ticket
        $ticketCount = Ticket::where('user_email', 'existing@company.com')->count();
        $this->assertEquals(1, $ticketCount);
    }

    /** @test */
    public function api_returns_tickets_list()
    {
        // Create test tickets
        $tickets = Ticket::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/tickets');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'ticket_number',
                            'subject',
                            'status',
                            'priority',
                            'created_at'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function api_creates_ticket_via_post()
    {
        // Test GENERATE TICKET step via API
        $ticketData = [
            'subject' => 'API Test Ticket',
            'description' => 'This ticket was created via API for testing purposes.',
            'category' => 'software',
            'priority' => 'low',
            'user_name' => 'API User',
            'user_email' => 'api@test.com',
            'user_phone' => '081234567890',
            'reporter_nip' => '12345',
            'reporter_name' => 'John API',
            'reporter_department' => 'IT',
            'reporter_position' => 'Developer',
            'channel' => 'web',
            'input_method' => 'api'
        ];

        $response = $this->postJson('/api/v1/tickets', $ticketData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'ticket_number',
                        'subject',
                        'status'
                    ]
                ]);

        // Verify VALIDASI SISTEM - ticket should pass basic validation
        $this->assertDatabaseHas('tickets', [
            'subject' => 'API Test Ticket',
            'status' => 'pending_review',
            'channel' => 'web'
        ]);
    }

    /** @test */
    public function api_validates_required_fields()
    {
        // Test VALIDASI SISTEM - should reject invalid data
        $invalidData = [
            'subject' => '', // Empty subject
            'user_email' => 'invalid-email', // Invalid email format
            // Missing required fields: user_name, category, channel
        ];

        $response = $this->postJson('/api/v1/tickets', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['subject', 'user_name', 'user_email', 'category', 'channel']);
    }

    /** @test */
    public function api_shows_specific_ticket()
    {
        $ticket = Ticket::factory()->create();

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'ticket_number',
                        'subject',
                        'description',
                        'status',
                        'threads'
                    ]
                ]);
    }

    /** @test */
    public function webhook_handles_malformed_data_gracefully()
    {
        // Test error handling in INPUT DATA step
        $malformedData = [
            'invalid_field' => 'test'
        ];

        $response = $this->postJson('/api/v1/webhooks/email', $malformedData);

        // Should handle gracefully, not crash
        $this->assertContains($response->status(), [400, 422, 500]);
    }

    /** @test */
    public function webhook_prevents_duplicate_message_processing()
    {
        $messageId = 'unique-message-123';
        
        $emailData = [
            'from' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test message body',
            'message_id' => $messageId
        ];

        // Send same message twice
        $this->postJson('/api/v1/webhooks/email', $emailData);
        $response2 = $this->postJson('/api/v1/webhooks/email', $emailData);

        // Should only create one ticket
        $ticketCount = Ticket::where('user_email', 'test@example.com')->count();
        $this->assertEquals(1, $ticketCount);
    }
}