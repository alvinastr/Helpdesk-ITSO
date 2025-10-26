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
                'user_id' => $data['user_id'] ?? Auth::id(),
                'user_name' => $standardizedData['user_name'],
                'user_email' => $standardizedData['user_email'],
                'user_phone' => $standardizedData['user_phone'] ?? null,
                
                // Reporter data
                'reporter_nip' => $data['reporter_nip'] ?? null,
                'reporter_name' => $data['reporter_name'] ?? null,
                'reporter_email' => $data['reporter_email'] ?? null,
                'reporter_phone' => isset($data['reporter_phone']) ? $this->normalizePhone($data['reporter_phone']) : null,
                'reporter_department' => $data['reporter_department'] ?? null,
                'reporter_position' => $data['reporter_position'] ?? null,
                
                'channel' => $standardizedData['channel'],
                'input_method' => $data['input_method'] ?? 'manual',
                'subject' => $standardizedData['subject'],
                'description' => $standardizedData['description'],
                'original_message' => $data['original_message'] ?? null,
                'category' => $data['category'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by_admin' => $data['created_by_admin'] ?? null,
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
     * Create ticket by admin (with reporter data)
     */
    public function createTicketByAdmin(array $data): Ticket
    {
        return DB::transaction(function () use ($data) {
            // Step 1: Generate Ticket ID
            $ticketNumber = $this->generateTicketNumber();

            // Step 2: Standardize data
            $standardizedData = $this->standardizeData($data);

            // Step 3: Create ticket with reporter data
            $ticket = Ticket::create([
                'ticket_number' => $ticketNumber,
                'user_id' => $data['user_id'] ?? null, // External user
                'user_name' => $standardizedData['user_name'],
                'user_email' => $standardizedData['user_email'],
                'user_phone' => $standardizedData['user_phone'] ?? null,
                
                // Reporter data
                'reporter_nip' => $data['reporter_nip'],
                'reporter_name' => $data['reporter_name'],
                'reporter_email' => $data['reporter_email'] ?? null,
                'reporter_phone' => isset($data['reporter_phone']) ? $this->normalizePhone($data['reporter_phone']) : null,
                'reporter_department' => $data['reporter_department'],
                'reporter_position' => null, // Tidak digunakan lagi
                
                'channel' => $data['channel'],
                'input_method' => $data['input_method'],
                'subject' => $standardizedData['subject'],
                'description' => $standardizedData['description'],
                'original_message' => $data['original_message'] ?? null,
                'category' => $data['category'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'assigned_to' => null, // Tidak digunakan lagi
                'created_by_admin' => $data['created_by_admin'],
                'status' => 'pending_review' // Skip validation for admin-created tickets
            ]);

            // Step 4: Create initial thread
            $this->addThreadMessage($ticket, [
                'sender_type' => 'admin',
                'sender_name' => Auth::user()->name,
                'message_type' => 'note',
                'message' => "Ticket dibuat oleh admin atas nama: {$data['reporter_name']} (NIP: {$data['reporter_nip']})"
            ]);

            // Step 5: Create reporter message thread
            $this->addThreadMessage($ticket, [
                'sender_type' => 'user',
                'sender_name' => $data['reporter_name'],
                'message_type' => 'complaint',
                'message' => $standardizedData['description']
            ]);

            // Step 6: Auto-categorize if not set
            if (!$ticket->category) {
                $this->autoCategorize($ticket);
            }

            // Step 7: Send notification
            $this->notificationService->sendTicketReceived($ticket);

            return $ticket;
        });
    }

    /**
     * Standardize input data
     */
    protected function standardizeData(array $data): array
    {
        $standardized = [
            'user_name' => trim($data['user_name'] ?? $data['reporter_name'] ?? ''),
            'user_email' => strtolower(trim($data['user_email'] ?? $data['reporter_email'] ?? '')),
            'user_phone' => $this->normalizePhone($data['user_phone'] ?? $data['reporter_phone'] ?? null),
            'channel' => $data['channel'] ?? 'portal',
            'subject' => trim($data['subject']),
            'description' => trim($data['description'])
        ];

        return $standardized;
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
            'sender_id' => $data['sender_id'] ?? Auth::id(),
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

        // Prepare update data
        $updateData = ['status' => $newStatus];
        
        // Add specific fields based on status
        if ($newStatus === 'closed') {
            $updateData['closed_at'] = now();
            $updateData['closed_by'] = Auth::id();
        }

        $ticket->update($updateData);

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
    public function rejectTicket(Ticket $ticket, string $reason, $admin = null): Ticket
    {
        $adminId = $admin ? $admin->id : Auth::id();
        $adminName = $admin ? $admin->name : Auth::user()->name;

        DB::transaction(function () use ($ticket, $reason, $adminId, $adminName) {
            // Update status using updateStatus method (this will create status history)
            $this->updateStatus($ticket, 'rejected', 'Ticket rejected by admin');

            // Update additional fields
            $ticket->update([
                'rejection_reason' => $reason,
                'rejected_by' => $adminId,
                'rejected_at' => now()
            ]);

            // Refresh ticket to ensure we have the latest data
            $ticket->refresh();

            // Add thread message
            $this->addThreadMessage($ticket, [
                'sender_type' => 'system',
                'sender_id' => $adminId,
                'sender_name' => $adminName,
                'message_type' => 'note',
                'message' => 'Ticket has been rejected. Reason: ' . $reason
            ]);

            $this->notificationService->sendTicketRejected($ticket);
        });

        return $ticket;
    }

    /**
     * Admin approve ticket
     */
    public function approveTicket(Ticket $ticket, $admin = null, ?int $assignedTo = null): Ticket
    {
        $adminId = $admin ? $admin->id : Auth::id();
        $adminName = $admin ? $admin->name : Auth::user()->name;

        DB::transaction(function () use ($ticket, $assignedTo, $adminId, $adminName) {
            // Update status using updateStatus method (this will create status history)
            $this->updateStatus($ticket, 'open', 'Ticket approved by admin');

            // Update additional fields
            $ticket->update([
                'approved_by' => $adminId,
                'approved_at' => now(),
                'assigned_to' => $assignedTo
            ]);

            // Refresh ticket to ensure we have the latest data
            $ticket->refresh();

            // Then add thread message
            $this->addThreadMessage($ticket, [
                'sender_type' => 'system',
                'sender_id' => $adminId,
                'sender_name' => $adminName,
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
    public function requestRevision(Ticket $ticket, string $message, $admin = null): Ticket
    {
        $adminId = $admin ? $admin->id : Auth::id();
        $adminName = $admin ? $admin->name : Auth::user()->name;

        DB::transaction(function () use ($ticket, $message, $adminId, $adminName) {
            // Update status using updateStatus method (this will create status history)
            $this->updateStatus($ticket, 'pending_revision', 'Revision requested by admin');

            // Add thread message
            $this->addThreadMessage($ticket, [
                'sender_type' => 'admin',
                'sender_id' => $adminId,
                'sender_name' => $adminName,
                'message_type' => 'note',
                'message' => "Revision requested: {$message}"
            ]);

            $this->notificationService->sendRevisionRequest($ticket, $message);
        });

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
    public function closeTicket(Ticket $ticket, string $resolutionNotes, $admin = null): Ticket
    {
        $adminId = $admin ? $admin->id : Auth::id();
        $adminName = $admin ? $admin->name : Auth::user()->name;

        DB::transaction(function () use ($ticket, $resolutionNotes, $adminId, $adminName) {
            // Update status using updateStatus method (this will create status history)
            $this->updateStatus($ticket, 'closed', 'Ticket closed by admin');

            // Update additional fields
            $ticket->update([
                'closed_at' => now(),
                'resolution_notes' => $resolutionNotes,
                'closed_by' => $adminId
            ]);

            // Refresh ticket to ensure we have the latest data
            $ticket->refresh();

            // Add thread message
            $this->addThreadMessage($ticket, [
                'sender_type' => 'system',
                'sender_id' => $adminId,
                'sender_name' => $adminName,
                'message_type' => 'resolution',
                'message' => "Ticket closed. Resolution: {$resolutionNotes}"
            ]);

            $this->notificationService->sendTicketClosed($ticket);
        });

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

    /**
     * Assign ticket to another admin
     */
    public function assignTicket(Ticket $ticket, $assignee, $admin): Ticket
    {
        $ticket->update(['assigned_to' => $assignee->id]);

        // Add thread message
        $this->addThreadMessage($ticket, [
            'sender_type' => 'admin',
            'sender_name' => $admin->name,
            'sender_id' => $admin->id,
            'message_type' => 'note',
            'message' => "Ticket di-assign ke {$assignee->name}"
        ]);

        // Record status history
        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'status' => $ticket->status,
            'notes' => "Assigned to {$assignee->name}",
            'changed_by' => $admin->id
        ]);

        return $ticket;
    }
}