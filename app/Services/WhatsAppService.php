<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiUrl;
    protected $apiToken;
    protected $fromNumber;
    protected $botUrl;
    protected $useBot;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->apiToken = config('services.whatsapp.token');
        $this->fromNumber = config('services.whatsapp.from_number');
        $this->botUrl = config('services.whatsapp.bot_url', 'http://localhost:3000');
        
        // Use bot if URL is configured
        $this->useBot = config('services.whatsapp.use_bot', true);
    }

    /**
     * Send WhatsApp message
     */
    public function sendMessage($to, $message, $ticketNumber = null)
    {
        // Try bot first if enabled
        if ($this->useBot) {
            return $this->sendViaBot($to, $message, $ticketNumber);
        }
        
        // Fallback to WhatsApp Business API
        return $this->sendViaBusinessAPI($to, $message);
    }
    
    /**
     * Send message via Node.js Bot
     */
    protected function sendViaBot($to, $message, $ticketNumber = null)
    {
        try {
            // Use /reply endpoint (recommended) jika ada ticket number
            $endpoint = $ticketNumber ? '/reply' : '/send';
            
            $payload = $ticketNumber ? [
                'phone' => $to,
                'message' => $message,
                'ticketId' => $ticketNumber
            ] : [
                'number' => $this->normalizePhoneNumber($to),
                'message' => $message
            ];
            
            $response = Http::timeout(10)
                ->post($this->botUrl . $endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("WhatsApp message sent via Bot", [
                    'to' => $to,
                    'endpoint' => $endpoint,
                    'ticket_id' => $ticketNumber,
                    'message_id' => $data['data']['messageId'] ?? null
                ]);
                return true;
            } else {
                Log::warning("Bot send failed, trying Business API fallback: " . $response->body());
                // Fallback to Business API
                return $this->sendViaBusinessAPI($to, $message);
            }
        } catch (\Exception $e) {
            Log::error("Bot sending failed: {$e->getMessage()}, trying Business API fallback");
            // Fallback to Business API
            return $this->sendViaBusinessAPI($to, $message);
        }
    }
    
    /**
     * Send message via WhatsApp Business API (Meta)
     */
    protected function sendViaBusinessAPI($to, $message)
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->post($this->apiUrl . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $this->normalizePhoneNumber($to),
                    'type' => 'text',
                    'text' => [
                        'body' => $message
                    ]
                ]);

            if ($response->successful()) {
                Log::info("WhatsApp message sent via Business API to {$to}");
                return true;
            } else {
                Log::error("WhatsApp Business API error: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("WhatsApp Business API sending failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send WhatsApp template message
     */
    public function sendTemplateMessage($to, $templateName, $parameters = [])
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->post($this->apiUrl . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $this->normalizePhoneNumber($to),
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => [
                            'code' => 'id' // Indonesian
                        ],
                        'components' => [
                            [
                                'type' => 'body',
                                'parameters' => $parameters
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                Log::info("WhatsApp template sent to {$to}");
                return true;
            } else {
                Log::error("WhatsApp template error: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("WhatsApp template sending failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Normalize phone number to international format
     */
    protected function normalizePhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present
        if (substr($phone, 0, 2) === '08') {
            $phone = '62' . substr($phone, 1);
        } elseif (substr($phone, 0, 1) === '8') {
            $phone = '62' . $phone;
        } elseif (substr($phone, 0, 1) !== '6') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }

    /**
     * Send ticket notification via WhatsApp
     */
    public function sendTicketNotification($ticket, $type)
    {
        if (!$ticket->user_phone) {
            return false;
        }

        $message = $this->buildTicketMessage($ticket, $type);
        return $this->sendMessage($ticket->user_phone, $message);
    }

    /**
     * Build WhatsApp message for ticket
     */
    protected function buildTicketMessage($ticket, $type)
    {
        $messages = [
            'received' => "✅ *Ticket Diterima*\n\nHalo {$ticket->user_name},\n\nTicket Anda telah diterima:\n📋 ID: {$ticket->ticket_number}\n📝 Subject: {$ticket->subject}\n⏳ Status: PENDING REVIEW\n\nAnda akan mendapat update segera.",
            
            'approved' => "✅ *Ticket Approved*\n\nHalo {$ticket->user_name},\n\nTicket Anda telah disetujui:\n📋 ID: {$ticket->ticket_number}\n📝 Subject: {$ticket->subject}\n✅ Status: OPEN\n🔧 Sedang ditangani tim kami.\n\nEstimasi: 2-3 hari kerja.",
            
            'rejected' => "❌ *Ticket Ditolak*\n\nHalo {$ticket->user_name},\n\nTicket Anda ditolak:\n📋 ID: {$ticket->ticket_number}\n📝 Subject: {$ticket->subject}\n❌ Alasan: {$ticket->rejection_reason}\n\nSilakan hubungi kami untuk info lebih lanjut.",
            
            'in_progress' => "⚙️ *Update Ticket*\n\nHalo {$ticket->user_name},\n\nTicket Anda sedang dikerjakan:\n📋 ID: {$ticket->ticket_number}\n📝 Subject: {$ticket->subject}\n⚙️ Status: IN PROGRESS\n\nAnda akan diupdate saat ada perkembangan.",
            
            'resolved' => "✅ *Ticket Resolved*\n\nHalo {$ticket->user_name},\n\nTicket Anda telah diselesaikan:\n📋 ID: {$ticket->ticket_number}\n📝 Subject: {$ticket->subject}\n✅ Status: RESOLVED\n\nMohon konfirmasi apakah masalah sudah teratasi.",
            
            'closed' => "✅ *Ticket Closed*\n\nHalo {$ticket->user_name},\n\nTicket Anda telah ditutup:\n📋 ID: {$ticket->ticket_number}\n📝 Subject: {$ticket->subject}\n✅ Status: CLOSED\n\nTerima kasih telah menggunakan layanan kami.",
        ];

        return $messages[$type] ?? "Update untuk ticket {$ticket->ticket_number}";
    }
}