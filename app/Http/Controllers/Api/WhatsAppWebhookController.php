<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle WhatsApp webhook from bot
     */
    public function receive(Request $request)
    {
        // Log incoming webhook
        Log::info('WhatsApp Webhook Received', [
            'sender' => $request->input('sender'),
            'phone' => $request->input('phone'),
            'category' => $request->input('category'),
            'priority' => $request->input('priority'),
        ]);

        // Validate incoming data
        $validated = $request->validate([
            'sender' => 'required|string',
            'phone' => 'required|string',
            'body' => 'required|string',
            'originalBody' => 'nullable|string',
            'timestamp' => 'required|integer',
            'category' => 'required|string|in:network,hardware,software,account,email,security,other',
            'priority' => 'required|string|in:normal,high,urgent',
            'source' => 'required|string',
            'hasMedia' => 'nullable|boolean',
            'type' => 'nullable|string',
            'chat' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Create ticket
            $ticket = WhatsAppTicket::create([
                'ticket_number' => WhatsAppTicket::generateTicketNumber(),
                'sender_wa_id' => $validated['sender'],
                'sender_phone' => $validated['phone'],
                'sender_name' => $validated['chat']['name'] ?? 'Unknown',
                'subject' => $this->generateSubject($validated),
                'description' => $validated['body'],
                'original_message' => $validated['originalBody'] ?? $validated['body'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'source' => $validated['source'],
                'status' => 'open',
                'is_group' => $validated['chat']['isGroup'] ?? false,
                'has_media' => $validated['hasMedia'] ?? false,
                'message_type' => $validated['type'] ?? 'chat',
                'raw_data' => $validated,
                'wa_timestamp' => \Carbon\Carbon::createFromTimestamp($validated['timestamp']),
            ]);

            // Auto-assign based on category (optional)
            $this->autoAssign($ticket);

            DB::commit();

            Log::info('WhatsApp Ticket Created', [
                'ticket_id' => $ticket->ticket_number,
                'category' => $ticket->category,
                'priority' => $ticket->priority,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket created successfully',
                'ticketId' => $ticket->ticket_number,
                'data' => [
                    'id' => $ticket->ticket_number,
                    'status' => $ticket->status,
                    'category' => $ticket->category,
                    'priority' => $ticket->priority,
                    'assignee' => $ticket->assignedTo?->name,
                    'created_at' => $ticket->created_at->toIso8601String(),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create WhatsApp ticket', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated ?? $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create ticket',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Generate ticket subject
     */
    private function generateSubject($data)
    {
        $category = ucfirst($data['category']);
        $priority = strtoupper($data['priority']);
        $body = mb_substr($data['body'], 0, 80);
        
        if ($data['priority'] === 'urgent') {
            return "[{$priority}] [{$category}] {$body}";
        }
        
        return "[{$category}] {$body}";
    }

    /**
     * Auto-assign ticket based on category
     */
    private function autoAssign($ticket)
    {
        // Example: Auto-assign berdasarkan kategori
        $assignments = [
            'network' => 1, // User ID untuk network team
            'hardware' => 2, // User ID untuk hardware team
            'software' => 3, // User ID untuk software team
        ];

        if (isset($assignments[$ticket->category])) {
            $ticket->assignTo($assignments[$ticket->category]);
        }
    }

    /**
     * Send notification (implement sesuai kebutuhan)
     */
    private function sendNotification($ticket)
    {
        // TODO: Implement notification
    }

    /**
     * Health check
     */
    public function health()
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'ITSO Helpdesk API',
            'environment' => config('app.env'),
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
            'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
            'whatsapp_integration' => 'active',
        ]);
    }

    /**
     * Check if user has recent open ticket (for threading)
     */
    public function checkRecentTicket(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        // Normalize phone number
        $phone = $this->normalizePhone($request->phone);

        // Check for recent open ticket (within 24 hours)
        $ticket = WhatsAppTicket::where(function($query) use ($phone) {
                $query->where('sender_phone', $phone)
                      ->orWhere('sender_phone', 'LIKE', '%' . substr($phone, -10)); // Last 10 digits
            })
            ->whereIn('status', ['open', 'in_progress', 'pending'])
            ->where('created_at', '>', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($ticket) {
            return response()->json([
                'has_open_ticket' => true,
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'ticket_status' => $ticket->status,
                'category' => $ticket->category,
                'priority' => $ticket->priority,
                'created_at' => $ticket->created_at->toIso8601String(),
                'created_ago' => $ticket->created_at->diffForHumans(),
                'assigned_to' => $ticket->assignedTo?->name,
            ]);
        }

        return response()->json([
            'has_open_ticket' => false,
            'message' => 'No recent open ticket found',
        ]);
    }

    /**
     * Append message to existing ticket (for threading)
     */
    public function appendMessage(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:whatsapp_tickets,id',
            'message' => 'required|string',
            'sender_name' => 'nullable|string',
        ]);

        try {
            $ticket = WhatsAppTicket::findOrFail($validated['ticket_id']);

            // Add response as customer message
            $response = $ticket->responses()->create([
                'message' => $validated['message'],
                'type' => 'reply', // Customer reply
                'user_id' => null, // From customer, not admin
                'metadata' => [
                    'source' => 'whatsapp_bot',
                    'appended_at' => now()->toIso8601String(),
                    'sender_name' => $validated['sender_name'] ?? $ticket->sender_name,
                ],
            ]);

            Log::info('Message appended to ticket', [
                'ticket_id' => $ticket->ticket_number,
                'response_id' => $response->id,
                'message_length' => strlen($validated['message']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message appended to ticket',
                'ticket_number' => $ticket->ticket_number,
                'ticket_status' => $ticket->status,
                'response_id' => $response->id,
                'total_responses' => $ticket->responses()->count(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to append message to ticket', [
                'ticket_id' => $validated['ticket_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to append message',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Normalize phone number format
     */
    private function normalizePhone($phone)
    {
        // Remove all non-digit characters
        $phone = preg_replace('/\D/', '', $phone);
        
        // Convert 08xxx to 628xxx
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        // Ensure starts with 62
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }
}
