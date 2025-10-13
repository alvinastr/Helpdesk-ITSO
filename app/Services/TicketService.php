<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketThread;
use App\Models\TicketStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TicketService
{
    protected $validationService;
    protected $notificationService;

    public function __construct(
        ValidationService $validationService,
        NotificationService $notificationService
    ) {
        $this->validationService = $validationService;
        $this->notificationService = $notificationService;
    }

    /**
     * Generate unique ticket number
     */
    public function generateTicketNumber(): string
    {
        $date = now()->format('Ymd');
        $lastTicket = Ticket::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastTicket ? 
            (int) substr($lastTicket->ticket_number, -4) + 1 : 1;
        
        return sprintf('TKT-%s-%04d', $date, $sequence);
    }

    /**
     * Check if message is a reply to existing ticket
     */
    public function checkIfReply($email, string $subject, string $userEmail): ?Ticket
    {
        // Check from subject (Re: TKT-XXXXXX)
        if (preg_match('/TKT-\d{8}-\d{4}/', $subject, $matches)) {
            $ticketNumber = $matches[0];
            return Ticket::where('ticket_number', $ticketNumber)->first();
        }

        // Check from email headers (In-Reply-To)
        if ($email && isset($email['in_reply_to'])) {
            // Parse In-Reply-To header to find ticket
            // Implementation depends on email parsing logic
        }

        // Check recent tickets from same user (within 7 days)
        return Ticket::where('user_email', $userEmail)
            ->where('status', '!=', 'closed')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Create new ticket
     */
    public function createTicket(array $data): Ticket
    {
        return DB::transaction(function () use ($data) {
            // Step 1: Generate Ticket ID
            $ticketNumber = $this->generateTicketNumber();

            // Step 2: Standardize data
            $standardizedData = $this->standardizeData($data);

            // Step 3: Create ticket
            $ticket = Ticket::create([
                'ticket_number' => $ticketNumber,
                'user_id' => $data['user_id'] ?? Auth::id(), // Allow external users with null user_id
                'user_name' => $standardizedData['user_name'],
                'user_email' => $standardizedData['user_email'],
                'user_phone' => $standardizedData['user_phone'] ?? null,
                'channel' => $standardizedData['channel'],
                'subject' => $standardizedData['subject'],
                'description' => $standardizedData['description'],
                'status' => 'pending_keluhan'
            ]);

            // Step 4: Create initial thread
            $this->addThreadMessage($ticket, [
                'sender_type' => 'user',
                'sender_name' => $standardizedData['user_name'],
                'message_type' => 'complaint',
                'message' => $standardizedData['description']
            ]);

            // Step 5: Validate
            $validationResult = $this->validationService->validate($ticket);

            if (!$validationResult['valid']) {
                // Set as rejected
                $this->rejectTicket($ticket, $validationResult['reason']);
                return $ticket;
            }

            // Step 6: Set to pending review
            $this->updateStatus($ticket, 'pending_review');

            // Step 7: Auto-categorize
            $this->autoCategorize($ticket);

            // Step 8: Send notification
            $this->notificationService->sendTicketReceived($ticket);

            return $ticket;
        });
    }

    /**
     * Standardize input data
     */
    protected function standardizeData(array $data): array
    {
        return [
            'user_name' => trim($data['user_name']),
            'user_email' => strtolower(trim($data['user_email'])),
            'user_phone' => $this->normalizePhone($data['user_phone'] ?? null),
            'channel' => $data['channel'] ?? 'portal',
            'subject' => trim($data['subject']),
            'description' => trim($data['description'])
        ];
    }

    /**
     * Normalize phone number to international format
     */
    protected function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;
        
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        return $phone;
    }

    /**
     * Add message to thread
     */
    public function addThreadMessage(Ticket $ticket, array $data): TicketThread
    {
        return TicketThread::create([
            'ticket_id' => $ticket->id,
            'sender_type' => $data['sender_type'] ?? 'user',
            'sender_id' => $data['sender_id'] ?? Auth::id(), // Fixed: Use Auth facade
            'sender_name' => $data['sender_name'],
            'message_type' => $data['message_type'] ?? 'reply',
            'message' => $data['message'],
            'attachments' => $data['attachments'] ?? null
        ]);
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Ticket $ticket, string $newStatus, ?string $notes = null): Ticket
    {
        $oldStatus = $ticket->status;

        $ticket->update(['status' => $newStatus]);

        // Record status history
        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => Auth::id(), // Fixed: Use Auth facade
            'notes' => $notes
        ]);

        // Add system message to thread
        $this->addThreadMessage($ticket, [
            'sender_type' => 'system',
            'sender_name' => 'System',
            'message_type' => 'note',
            'message' => "Status changed from {$oldStatus} to {$newStatus}"
        ]);

        return $ticket;
    }

    /**
     * Auto-categorize ticket using keywords
     */
    protected function autoCategorize(Ticket $ticket): void
    {
        $text = strtolower($ticket->subject . ' ' . $ticket->description);

        // Simple keyword-based categorization
        $categories = [
            'Technical' => ['error', 'bug', 'crash', 'tidak bisa', 'gagal', 'loading'],
            'Billing' => ['tagihan', 'pembayaran', 'invoice', 'bayar', 'harga'],
            'Feature Request' => ['fitur', 'feature', 'tambah', 'request', 'saran'],
            'Complaint' => ['komplain', 'kecewa', 'lambat', 'buruk', 'jelek']
        ];

        $priority = 'medium';
        $category = 'General';

        foreach ($categories as $cat => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($text, $keyword)) {
                    $category = $cat;
                    break 2;
                }
            }
        }

        // Check for urgent keywords
        $urgentKeywords = ['urgent', 'segera', 'penting', 'critical', 'down'];
        foreach ($urgentKeywords as $keyword) {
            if (Str::contains($text, $keyword)) {
                $priority = 'high';
                break;
            }
        }

        $ticket->update([
            'category' => $category,
            'priority' => $priority
        ]);
    }

    /**
     * Reject ticket
     */
    public function rejectTicket(Ticket $ticket, string $reason): Ticket
    {
        $ticket->update([
            'status' => 'rejected',
            'rejection_reason' => $reason
        ]);

        $this->notificationService->sendTicketRejected($ticket);

        return $ticket;
    }

    /**
     * Admin approve ticket
     */
    public function approveTicket(Ticket $ticket, ?int $assignedTo = null): Ticket
    {
        DB::transaction(function () use ($ticket, $assignedTo) {
            $ticket->update([
                'status' => 'open',
                'approved_by' => Auth::id(), // Fixed: Use Auth facade
                'approved_at' => now(),
                'assigned_to' => $assignedTo
            ]);

            $this->addThreadMessage($ticket, [
                'sender_type' => 'system',
                'sender_name' => 'Admin',
                'message_type' => 'note',
                'message' => 'Ticket has been approved and is now open.'
            ]);

            $this->notificationService->sendTicketApproved($ticket);
        });

        return $ticket;
    }

    /**
     * Request revision from user
     */
    public function requestRevision(Ticket $ticket, string $message): Ticket
    {
        // Fixed: Add null check for auth()->user()
        $userName = Auth::check() ? Auth::user()->name : 'Admin';

        $this->addThreadMessage($ticket, [
            'sender_type' => 'admin',
            'sender_name' => $userName,
            'message_type' => 'note',
            'message' => "Revision requested: {$message}"
        ]);

        $this->notificationService->sendRevisionRequest($ticket, $message);

        return $ticket;
    }

    /**
     * Update ticket progress
     */
    public function updateProgress(Ticket $ticket, string $status, string $message): Ticket
    {
        $this->updateStatus($ticket, $status, $message);

        // Fixed: Add null check for auth()->user()
        $userName = Auth::check() ? Auth::user()->name : 'Admin';

        $this->addThreadMessage($ticket, [
            'sender_type' => 'admin',
            'sender_name' => $userName,
            'message_type' => 'note',
            'message' => $message
        ]);

        $this->notificationService->sendProgressUpdate($ticket, $message);

        return $ticket;
    }

    /**
     * Close ticket
     */
    public function closeTicket(Ticket $ticket, string $resolutionNotes): Ticket
    {
        DB::transaction(function () use ($ticket, $resolutionNotes) {
            $ticket->update([
                'status' => 'closed',
                'closed_at' => now(),
                'resolution_notes' => $resolutionNotes
            ]);

            $this->addThreadMessage($ticket, [
                'sender_type' => 'system',
                'sender_name' => 'System',
                'message_type' => 'resolution',
                'message' => "Ticket closed. Resolution: {$resolutionNotes}"
            ]);

            $this->notificationService->sendTicketClosed($ticket);
        });

        return $ticket;
    }

    /**
     * Add feedback
     */
    public function addFeedback(Ticket $ticket, int $rating, ?string $feedback = null): Ticket
    {
        $ticket->update([
            'rating' => $rating,
            'feedback' => $feedback
        ]);

        return $ticket;
    }

    /**
     * Check if email is a reply to existing ticket
     */
    public function checkIfEmailReply(string $subject): bool
    {
        return preg_match('/TKT-\d{8}-\d{4}/', $subject) === 1;
    }

    /**
     * Check if WhatsApp message is a reply to existing ticket
     */
    public function checkIfWhatsAppReply(string $fromNumber): bool
    {
        $normalizedPhone = $this->normalizePhone($fromNumber);
        
        return Ticket::where('user_phone', $normalizedPhone)
            ->where('status', '!=', 'closed')
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();
    }

    /**
     * Create ticket from email
     */
    public function createTicketFromEmail(array $emailData): Ticket
    {
        $ticketData = [
            'user_id' => null, // External user, no user_id
            'user_name' => $emailData['from_name'],
            'user_email' => $emailData['from_email'],
            'subject' => $emailData['subject'],
            'description' => $emailData['body'],
            'channel' => 'email'
        ];

        return $this->createTicket($ticketData);
    }

    /**
     * Create ticket from WhatsApp
     */
    public function createTicketFromWhatsApp(array $whatsappData): Ticket
    {
        $ticketData = [
            'user_id' => null, // External user, no user_id
            'user_name' => $whatsappData['from_name'] ?? 'WhatsApp User',
            'user_email' => '', // Will need to be collected later
            'user_phone' => $whatsappData['from_number'],
            'subject' => 'WhatsApp Support Request',
            'description' => $whatsappData['message'],
            'channel' => 'whatsapp'
        ];

        return $this->createTicket($ticketData);
    }

    /**
     * Add email reply to existing ticket
     */
    public function addEmailReply(array $emailData): Ticket
    {
        $ticketNumber = null;
        if (preg_match('/TKT-\d{8}-\d{4}/', $emailData['subject'], $matches)) {
            $ticketNumber = $matches[0];
        }

        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();

        $this->addThreadMessage($ticket, [
            'sender_type' => 'user',
            'sender_name' => $emailData['from_name'],
            'message_type' => 'reply',
            'message' => $emailData['body']
        ]);

        // Update ticket as active
        if ($ticket->status === 'closed') {
            $this->updateStatus($ticket, 'open');
        }

        return $ticket;
    }

    /**
     * Add WhatsApp reply to existing ticket
     */
    public function addWhatsAppReply(array $whatsappData): Ticket
    {
        $normalizedPhone = $this->normalizePhone($whatsappData['from_number']);
        
        $ticket = Ticket::where('user_phone', $normalizedPhone)
            ->where('status', '!=', 'closed')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->firstOrFail();

        $this->addThreadMessage($ticket, [
            'sender_type' => 'user',
            'sender_name' => $whatsappData['from_name'] ?? 'WhatsApp User',
            'message_type' => 'reply',
            'message' => $whatsappData['message']
        ]);

        // Update ticket as active
        if ($ticket->status === 'closed') {
            $this->updateStatus($ticket, 'open');
        }

        return $ticket;
    }
}