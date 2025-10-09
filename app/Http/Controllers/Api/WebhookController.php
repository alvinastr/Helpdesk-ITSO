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
                'from_email' => 'required|email',
                'from_name' => 'required|string',
                'subject' => 'required|string',
                'body' => 'required|string',
                'message_id' => 'required|string',
            ]);

            // Check if this is a reply to existing ticket
            $isReply = $this->ticketService->checkIfEmailReply($emailData['subject']);
            
            if ($isReply) {
                // Add thread to existing ticket
                $ticket = $this->ticketService->addEmailReply($emailData);
            } else {
                // Create new ticket
                $ticket = $this->ticketService->createTicketFromEmail($emailData);
            }

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
     * Handle incoming WhatsApp webhook
     */
    public function handleWhatsApp(Request $request)
    {
        try {
            // Validate incoming WhatsApp data
            $whatsappData = $request->validate([
                'from_number' => 'required|string',
                'from_name' => 'string',
                'message' => 'required|string',
                'message_id' => 'required|string',
            ]);

            // Check if this is a reply to existing ticket
            $isReply = $this->ticketService->checkIfWhatsAppReply($whatsappData['from_number']);
            
            if ($isReply) {
                // Add thread to existing ticket
                $ticket = $this->ticketService->addWhatsAppReply($whatsappData);
            } else {
                // Create new ticket
                $ticket = $this->ticketService->createTicketFromWhatsApp($whatsappData);
            }

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
}