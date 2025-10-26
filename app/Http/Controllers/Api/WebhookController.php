<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Handle incoming email webhook
     */
    public function handleEmail(Request $request)
    {
        try {
            // Validate incoming email data
            $emailData = $request->validate([
                'from' => 'required|email',
                'subject' => 'required|string',
                'body' => 'required|string',
                'message_id' => 'required|string',
                'in_reply_to' => 'nullable|string',
            ]);

            // Check for duplicate message_id by checking existing tickets from same email with same message
            $duplicateCheck = Ticket::where('user_email', $emailData['from'])
                ->where('original_message', $emailData['body'])
                ->where('created_at', '>', now()->subMinutes(5)) // Within last 5 minutes
                ->first();
                
            if ($duplicateCheck) {
                return response()->json([
                    'success' => true,
                    'action' => 'duplicate_ignored',
                    'ticket_id' => $duplicateCheck->id,
                    'ticket_number' => $duplicateCheck->ticket_number
                ]);
            }

            // Check if this is a reply to an existing ticket
            if (!empty($emailData['in_reply_to'])) {
                $existingTicket = Ticket::where('ticket_number', $emailData['in_reply_to'])
                    ->orWhere('user_email', $emailData['from'])
                    ->first();
                
                if ($existingTicket) {
                    // Add thread to existing ticket
                    $this->ticketService->addThreadMessage($existingTicket, [
                        'message' => $emailData['body'],
                        'sender_type' => 'user',
                        'sender_name' => $this->extractNameFromEmail($emailData['from'])
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'action' => 'thread_added',
                        'ticket_id' => $existingTicket->id,
                        'ticket_number' => $existingTicket->ticket_number
                    ]);
                }
            }

            // Create new ticket
            $ticketData = [
                'user_name' => $this->extractNameFromEmail($emailData['from']),
                'user_email' => $emailData['from'],
                'subject' => $emailData['subject'],
                'description' => $emailData['body'],
                'channel' => 'email',
                'input_method' => 'email',
                'original_message' => $emailData['body'],
                'priority' => 'medium',
                'category' => 'general'
            ];
            $ticket = $this->ticketService->createTicket($ticketData);

            return response()->json([
                'success' => true,
                'action' => 'ticket_created',
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Handle incoming WhatsApp webhook
     */
    public function handleWhatsApp(Request $request)
    {
        try {
            // Validate incoming WhatsApp data
            $whatsappData = $request->validate([
                'from' => 'required|string',
                'message' => 'required_without:body|string',
                'body' => 'required_without:message|string',
                'message_id' => 'required|string',
            ]);

            // Get the message content (either from 'message' or 'body' field)
            $messageContent = $whatsappData['message'] ?? $whatsappData['body'];

            // Create new ticket
            $ticketData = [
                'user_name' => $this->extractNameFromPhone($whatsappData['from']),
                'user_email' => $this->generateEmailFromPhone($whatsappData['from']),
                'user_phone' => $whatsappData['from'],
                'subject' => 'WhatsApp: ' . substr($messageContent, 0, 50) . (strlen($messageContent) > 50 ? '...' : ''),
                'description' => $messageContent,
                'channel' => 'whatsapp',
                'input_method' => 'whatsapp',
                'original_message' => $messageContent,
                'priority' => 'medium',
                'category' => 'general'
            ];
            $ticket = $this->ticketService->createTicket($ticketData);

            return response()->json([
                'success' => true,
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Extract name from email address
     */
    private function extractNameFromEmail($email)
    {
        $localPart = explode('@', $email)[0];
        
        // Convert dots and underscores to spaces and capitalize
        $name = str_replace(['.', '_', '-'], ' ', $localPart);
        $name = ucwords($name);
        
        return $name ?: 'Unknown User';
    }

    /**
     * Extract name from phone number
     */
    private function extractNameFromPhone($phone)
    {
        // For phone numbers, we'll use a generic name
        // In a real system, you might look up the contact in a database
        return 'WhatsApp User (' . substr($phone, -4) . ')';
    }

    /**
     * Generate email from phone number for validation purposes
     */
    private function generateEmailFromPhone($phone)
    {
        // Remove all non-digits and generate a dummy email
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        return 'whatsapp.' . $cleanPhone . '@system.local';
    }
}
