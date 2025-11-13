<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    /** @test */
    public function api_can_list_tickets()
    {
        Ticket::factory()->count(5)->create();
        
        $response = $this->getJson('/api/v1/tickets');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'ticket_number',
                    'subject',
                    'status'
                ]
            ]
        ]);
    }

    /** @test */
    public function api_can_filter_tickets_by_status()
    {
        Ticket::factory()->create(['status' => 'open']);
        Ticket::factory()->create(['status' => 'closed']);
        
        $response = $this->getJson('/api/v1/tickets?status=open');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function api_can_filter_tickets_by_date_range()
    {
        $response = $this->getJson('/api/v1/tickets?date_from=2024-01-01&date_to=2024-12-31');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function api_can_search_tickets()
    {
        Ticket::factory()->create(['subject' => 'Unique Search Term']);
        
        $response = $this->getJson('/api/v1/tickets?search=Unique');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function api_can_create_ticket()
    {
        $ticketData = [
            'subject' => 'API Created Ticket',
            'description' => 'This ticket was created via API endpoint for testing',
            'user_name' => 'API Test User',
            'user_email' => 'apitest@example.com',
            'user_phone' => '081234567890',
            'category' => 'technical',
            'priority' => 'medium',
            'channel' => 'web' // API accepts: email, whatsapp, web
        ];
        
        $response = $this->postJson('/api/v1/tickets', $ticketData);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('tickets', [
            'subject' => 'API Created Ticket',
            'user_email' => 'apitest@example.com'
        ]);
    }

    /** @test */
    public function api_cannot_create_ticket_without_required_fields()
    {
        $response = $this->postJson('/api/v1/tickets', [
            'subject' => 'Incomplete Ticket'
        ]);
        
        $response->assertStatus(422); // Validation error
    }

    /** @test */
    public function api_can_show_single_ticket()
    {
        $ticket = Ticket::factory()->create();
        
        $response = $this->getJson("/api/v1/tickets/{$ticket->id}");
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'ticket_number',
                'subject',
                'description',
                'status'
            ]
        ]);
    }

    /** @test */
    public function api_returns_404_for_nonexistent_ticket()
    {
        $response = $this->getJson('/api/v1/tickets/99999');
        
        $response->assertStatus(404);
    }

    /** @test */
    public function email_parser_api_can_parse_email()
    {
        $emailData = [
            'from' => 'user@example.com',
            'subject' => 'Email Subject Test',
            'body' => 'This is the email body content for testing email parser API',
            'date' => now()->toISOString()
        ];
        
        $response = $this->postJson('/api/v1/parse-email', $emailData);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
    }

    /** @test */
    public function email_parser_extracts_correct_information()
    {
        $emailData = [
            'from' => 'test@example.com',
            'subject' => '[Ticket] Issue with Login',
            'body' => 'NIP: 123456789\nNama: Test User\nDepartment: IT\n\nIssue description here',
            'date' => now()->toISOString()
        ];
        
        $response = $this->postJson('/api/v1/parse-email', $emailData);
        
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('reporter_email', $data);
    }

    /** @test */
    public function webhook_can_receive_email()
    {
        $webhookData = [
            'from' => 'webhook@example.com',
            'to' => 'support@company.com',
            'subject' => 'Webhook Test Email',
            'text' => 'Email content from webhook',
            'timestamp' => now()->timestamp
        ];
        
        $response = $this->postJson('/api/v1/webhooks/email', $webhookData);
        
        // Should process and create ticket
        $response->assertStatus(200);
    }

    /** @test */
    public function webhook_can_receive_whatsapp()
    {
        $webhookData = [
            'from' => '628123456789',
            'message' => 'WhatsApp message for testing webhook',
            'timestamp' => now()->timestamp
        ];
        
        $response = $this->postJson('/api/v1/webhooks/whatsapp', $webhookData);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function api_validates_email_format()
    {
        $ticketData = [
            'subject' => 'Test Ticket',
            'description' => 'Testing email validation in API endpoint',
            'reporter_nip' => '123456789',
            'reporter_name' => 'Test User',
            'reporter_email' => 'invalid-email-format',
            'reporter_department' => 'IT',
            'category' => 'general',
            'priority' => 'medium'
        ];
        
        $response = $this->postJson('/api/v1/tickets', $ticketData);
        
        $response->assertStatus(422);
    }

    /** @test */
    public function api_ticket_list_is_paginated()
    {
        Ticket::factory()->count(25)->create();
        
        $response = $this->getJson('/api/v1/tickets');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links',
            'meta'
        ]);
    }

    /** @test */
    public function api_handles_invalid_json_gracefully()
    {
        $response = $this->post('/api/v1/tickets', [], [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]);
        
        $response->assertStatus(422);
    }
}
