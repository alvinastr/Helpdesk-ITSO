<?php

namespace App\Services;

use App\Models\Ticket;
use App\Mail\TicketReceived;
use App\Mail\TicketApproved;
use App\Mail\TicketRejected;
use App\Mail\TicketClosed;
use App\Mail\RevisionRequest;
use App\Mail\TicketUpdate;
use App\Jobs\SendTicketNotification;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send notification when ticket is received
     */
    public function sendTicketReceived(Ticket $ticket)
    {
        SendTicketNotification::dispatch($ticket, 'received');
    }

    /**
     * Send notification when ticket is approved
     */
    public function sendTicketApproved(Ticket $ticket)
    {
        SendTicketNotification::dispatch($ticket, 'approved');
    }

    /**
     * Send notification when ticket is rejected
     */
    public function sendTicketRejected(Ticket $ticket)
    {
        SendTicketNotification::dispatch($ticket, 'rejected');
    }

    /**
     * Send notification when ticket is closed
     */
    public function sendTicketClosed(Ticket $ticket)
    {
        SendTicketNotification::dispatch($ticket, 'closed');
    }

    /**
     * Send revision request
     */
    public function sendRevisionRequest(Ticket $ticket, $message)
    {
        SendTicketNotification::dispatch($ticket, 'revision', ['message' => $message]);
    }

    /**
     * Send progress update
     */
    public function sendProgressUpdate(Ticket $ticket, $message)
    {
        SendTicketNotification::dispatch($ticket, 'progress', ['message' => $message]);
    }

    /**
     * Send WhatsApp notification (integration required)
     */
    public function sendWhatsAppNotification(Ticket $ticket, $message)
    {
        // Integration with WhatsApp API (e.g., Twilio, WA Business API)
        // Example implementation:
        
        if (!$ticket->user_phone) {
            return false;
        }

        try {
            // Using Twilio (example)
            /*
            $client = new \Twilio\Rest\Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );
            
            $client->messages->create(
                "whatsapp:{$ticket->user_phone}",
                [
                    "from" => config('services.twilio.whatsapp_number'),
                    "body" => $message
                ]
            );
            */

            // Or using WhatsApp Business API
            /*
            $response = Http::withToken(config('services.whatsapp.token'))
                ->post(config('services.whatsapp.api_url') . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $ticket->user_phone,
                    'type' => 'text',
                    'text' => [
                        'body' => $message
                    ]
                ]);
            */

            return true;
        } catch (\Exception $e) 
        {
            \Log::error('WhatsApp notification failed: ' . $e->getMessage());
            return false;
        }
    }
}