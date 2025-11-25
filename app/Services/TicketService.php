<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketThread;
use App\Models\TicketStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TicketService
{
    protected $validationService;
    protected $notificationService;
    protected $kpiService;

    public function __construct(
        ValidationService $validationService,
        NotificationService $notificationService,
        KpiCalculationService $kpiService
    ) {
        $this->validationService = $validationService;
        $this->notificationService = $notificationService;
        $this->kpiService = $kpiService;
    }

    /**
     * Generate unique ticket number
     */
    public function generateTicketNumber(): string
    {
        $date = now()->format('Ymd');
        
        // Get last ticket with standard format (not custom numbers like TEST)
        // Use LIKE for SQLite compatibility - only match 4-digit suffixes
        $lastTicket = Ticket::whereDate('created_at', today())
            ->where('ticket_number', 'LIKE', 'TKT-' . $date . '-%')
            ->orderBy('id', 'desc')
            ->get()
            ->first(function ($ticket) {
                // Filter only standard 4-digit format
                $suffix = substr($ticket->ticket_number, -4);
                return preg_match('/^\d{4}$/', $suffix);
            });
        
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
                'status' => 'pending_keluhan',
                
                // KPI Timestamps (untuk email auto-fetch)
                'email_received_at' => $data['email_received_at'] ?? null,
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

            // Step 6: Set to pending keluhan (waiting for admin validation)
            $this->updateStatus($ticket, 'pending_keluhan');

            // Step 7: Auto-categorize
            $this->autoCategorize($ticket);
            
            // Step 7.5: Build email thread if parsed_emails provided
            Log::info("Checking for parsed_emails in data. Keys: " . implode(', ', array_keys($data)));
            if (isset($data['parsed_emails']) && !empty($data['parsed_emails'])) {
                Log::info("parsed_emails found, calling buildEmailThread");
                $this->buildEmailThread($ticket, $data);
            } else {
                Log::info("parsed_emails NOT found or empty");
            }

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
                'status' => 'pending_review', // Skip validation for admin-created tickets
                
                // KPI: Set email_received_at jika ada
                'email_received_at' => $data['email_received_at'] ?? null,
                
                // KPI Manual: Set first_response_at dan resolved_at jika ada
                'first_response_at' => $data['first_response_at'] ?? null,
                'resolved_at' => $data['resolved_at'] ?? null,
                
                // Email Content Fields
                'email_subject' => $data['email_subject'] ?? null,
                'email_body_original' => $data['email_body_original'] ?? null,
                'email_response_admin' => $data['email_response_admin'] ?? null,
                'email_resolution_message' => $data['email_resolution_message'] ?? null,
                'email_from' => $data['email_from'] ?? null,
                'email_to' => $data['email_to'] ?? null,
                'email_cc' => $data['email_cc'] ?? null,
            ]);
            
            // Build email_thread JSON dari konten email
            $this->buildEmailThread($ticket, $data);
            
            // Auto-set status jika resolved_at sudah diisi
            if ($ticket->resolved_at) {
                $ticket->status = 'resolved';
                $ticket->save();
            }
            
            // Calculate all KPI metrics if email_received_at is set
            if ($ticket->email_received_at) {
                $this->kpiService->updateTicketKpiMetrics($ticket);
            }

            // Step 4: Create initial thread
            $adminName = Auth::check() ? Auth::user()->name : 'System Auto-Fetch';
            $this->addThreadMessage($ticket, [
                'sender_type' => 'admin',
                'sender_name' => $adminName,
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
        $thread = TicketThread::create([
            'ticket_id' => $ticket->id,
            'sender_type' => $data['sender_type'] ?? 'user',
            'sender_id' => $data['sender_id'] ?? Auth::id(),
            'sender_name' => $data['sender_name'],
            'message_type' => $data['message_type'] ?? 'reply',
            'message' => $data['message'],
            'attachments' => $data['attachments'] ?? null
        ]);
        
        // KPI: Track first response from admin/staff
        if ($data['sender_type'] === 'admin' && !$ticket->first_response_at) {
            $this->kpiService->setFirstResponseTime($ticket);
        }
        
        return $thread;
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
        
        // KPI: Set resolved_at when status changes to resolved
        if ($newStatus === 'resolved' && !$ticket->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        $ticket->update($updateData);
        
        // KPI: Update metrics when status changes
        if ($newStatus === 'resolved' || $newStatus === 'closed') {
            $this->kpiService->updateTicketKpiMetrics($ticket);
        }

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
    public function rejectTicket(Ticket $ticket, ?string $reason = null, $admin = null): Ticket
    {
        $adminId = $admin ? $admin->id : Auth::id();
        $adminName = $admin ? $admin->name : (Auth::user()?->name ?? 'System');
        
        // Use default reason if not provided
        $reason = $reason ?: 'Ticket ditolak oleh admin';

        DB::transaction(function () use ($ticket, $reason, $adminId, $adminName) {
            $oldStatus = $ticket->status;
            
            // Update all fields in a single call to avoid conflicts
            $ticket->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'rejected_by' => $adminId,
                'rejected_at' => now(),
                'validation_status' => 'rejected'
            ]);

            // Record status history
            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => 'rejected',
                'changed_by' => $adminId,
                'notes' => 'Ticket rejected by admin'
            ]);

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

        // Refresh ticket AFTER transaction to get persisted changes
        $ticket->refresh();

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
            $oldStatus = $ticket->status;
            
            // Update all fields in a single call
            $ticket->update([
                'status' => 'open',
                'approved_by' => $adminId,
                'approved_at' => now(),
                'assigned_to' => $assignedTo,
                'validation_status' => 'approved'
            ]);

            // Record status history
            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => 'open',
                'changed_by' => $adminId,
                'notes' => 'Ticket approved by admin'
            ]);

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

        // Refresh ticket AFTER transaction to get persisted changes
        $ticket->refresh();

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
            $oldStatus = $ticket->status;
            
            // Update all fields in a single call
            $ticket->update([
                'status' => 'pending_revision',
                'validation_status' => 'needs_revision',
                'revision_notes' => $message,
                'revision_count' => ($ticket->revision_count ?? 0) + 1
            ]);

            // Record status history
            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => 'pending_revision',
                'changed_by' => $adminId,
                'notes' => 'Revision requested by admin'
            ]);

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

        // Refresh ticket AFTER transaction to get persisted changes
        $ticket->refresh();

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
    public function closeTicket(Ticket $ticket, ?string $resolutionNotes = null, $admin = null): Ticket
    {
        $adminId = $admin ? $admin->id : Auth::id();
        $adminName = $admin ? $admin->name : Auth::user()->name;
        
        // Use default resolution notes if not provided
        $resolutionNotes = $resolutionNotes ?: 'Masalah telah diselesaikan';

        DB::transaction(function () use ($ticket, $resolutionNotes, $adminId, $adminName) {
            $oldStatus = $ticket->status;
            
            // Update all fields in a single call
            $ticket->update([
                'status' => 'closed',
                'closed_at' => now(),
                'resolution_notes' => $resolutionNotes,
                'closed_by' => $adminId
            ]);

            // Record status history
            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => 'closed',
                'changed_by' => $adminId,
                'notes' => 'Ticket closed by admin'
            ]);

            // Add thread message
            $this->addThreadMessage($ticket, [
                'sender_type' => 'system',
                'sender_id' => $adminId,
                'sender_name' => $adminName,
                'message_type' => 'resolution',
                'message' => "Ticket closed. Resolution: {$resolutionNotes}"
            ]);

            $this->notificationService->sendTicketClosed($ticket);
            
            // Update KPI metrics
            if (isset($this->kpiService)) {
                $this->kpiService->updateTicketKpiMetrics($ticket);
            }
        });

        // Refresh ticket AFTER transaction to get persisted changes
        $ticket->refresh();

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
     * Build email thread JSON dari konten email yang diinput
     * Format: array of email messages dengan timestamp
     * Support unlimited email threads (not limited to 3)
     */
    protected function buildEmailThread(Ticket $ticket, array $data): void
    {
        Log::info("buildEmailThread called for ticket #{$ticket->ticket_number}");
        Log::info("Data keys: " . implode(', ', array_keys($data)));
        
        $emailThread = [];
        
        // Method 1: If data comes from EmailParser (parsed array)
        if (isset($data['parsed_emails']) && is_array($data['parsed_emails'])) {
            // Extract KPI timestamps dari email thread
            $kpiUpdates = [];
            
            // Use parsed email data directly (supports unlimited threads)
            foreach ($data['parsed_emails'] as $email) {
                $emailThread[] = [
                    'type' => $email['type'] ?? 'reply',
                    'timestamp' => $email['timestamp'] ?? now()->toIso8601String(),
                    'from' => $email['from'] ?? 'Unknown',
                    'from_name' => $email['from_name'] ?? 'Unknown',
                    'to' => $email['to'] ?? '',
                    'cc' => $email['cc'] ?? null,
                    'subject' => $email['subject'] ?? '',
                    'body' => $email['body'] ?? '',
                    'sender_name' => $email['from_name'] ?? 'Unknown',
                    'index' => $email['index'] ?? 0,
                ];
                
                // Extract KPI timestamps
                $type = $email['type'] ?? '';
                $timestamp = $email['timestamp'] ?? null;
                
                // First email = email_received_at (already set during ticket creation)
                // But we still verify it
                if ($type === 'user_complaint' && $timestamp && !$ticket->email_received_at) {
                    $kpiUpdates['email_received_at'] = $timestamp;
                }
                
                // First admin response = first_response_at
                if (($type === 'admin_response' || $type === 'admin_reply') && $timestamp && !isset($kpiUpdates['first_response_at'])) {
                    $kpiUpdates['first_response_at'] = $timestamp;
                }
                
                // Resolution email = resolved_at
                if ($type === 'resolution' && $timestamp) {
                    $kpiUpdates['resolved_at'] = $timestamp;
                }
            }
            
            // Update KPI fields if found
            if (!empty($kpiUpdates)) {
                $ticket->update($kpiUpdates);
                $ticket->refresh();
                Log::info("KPI timestamps updated for ticket #{$ticket->ticket_number}: " . json_encode($kpiUpdates));
            }
        }
        // Method 2: Legacy - from manual form input (backward compatibility)
        else {
            // 1. Email Keluhan Pertama (dari User)
            if (!empty($data['email_body_original'])) {
                $emailThread[] = [
                    'type' => 'user_complaint',
                    'timestamp' => $ticket->email_received_at?->toIso8601String() ?? now()->toIso8601String(),
                    'from' => $data['email_from'] ?? $ticket->reporter_email ?? 'Unknown',
                    'from_name' => $ticket->reporter_name ?? 'Unknown',
                    'to' => $data['email_to'] ?? 'support@example.com',
                    'cc' => $data['email_cc'] ?? null,
                    'subject' => $data['email_subject'] ?? $ticket->subject,
                    'body' => $data['email_body_original'],
                    'sender_name' => $ticket->reporter_name,
                    'index' => 1,
                ];
            }
            
            // 2. Email Response Admin
            if (!empty($data['email_response_admin'])) {
                $emailThread[] = [
                    'type' => 'admin_response',
                    'timestamp' => $ticket->first_response_at?->toIso8601String() ?? now()->toIso8601String(),
                    'from' => $data['email_to'] ?? 'support@example.com',
                    'from_name' => Auth::user()?->name ?? 'Admin',
                    'to' => $data['email_from'] ?? $ticket->reporter_email ?? 'Unknown',
                    'cc' => $data['email_cc'] ?? null,
                    'subject' => 'Re: ' . ($data['email_subject'] ?? $ticket->subject),
                    'body' => $data['email_response_admin'],
                    'sender_name' => Auth::user()?->name ?? 'Admin',
                    'index' => 2,
                ];
            }
            
            // 3. Email Resolution
            if (!empty($data['email_resolution_message'])) {
                $emailThread[] = [
                    'type' => 'resolution',
                    'timestamp' => $ticket->resolved_at?->toIso8601String() ?? now()->toIso8601String(),
                    'from' => $data['email_to'] ?? 'support@example.com',
                    'from_name' => Auth::user()?->name ?? 'Admin',
                    'to' => $data['email_from'] ?? $ticket->reporter_email ?? 'Unknown',
                    'cc' => $data['email_cc'] ?? null,
                    'subject' => 'Re: [RESOLVED] ' . ($data['email_subject'] ?? $ticket->subject),
                    'body' => $data['email_resolution_message'],
                    'sender_name' => Auth::user()?->name ?? 'Admin',
                    'index' => 3,
                ];
            }
        }
        
        // Save email thread jika ada konten
        if (!empty($emailThread)) {
            $ticket->update(['email_thread' => $emailThread]);
            $ticket->refresh();
            Log::info("Email thread saved for ticket #{$ticket->ticket_number}: " . count($emailThread) . " emails");
            
            // Calculate KPI metrics after timestamps are set
            $this->calculateKpiMetrics($ticket);
        }
    }
    
    /**
     * Calculate KPI metrics (response_time and resolution_time)
     */
    protected function calculateKpiMetrics(Ticket $ticket): void
    {
        $updates = [];
        
        // Calculate response_time_minutes
        if ($ticket->email_received_at && $ticket->first_response_at) {
            $responseTime = \Carbon\Carbon::parse($ticket->email_received_at)
                ->diffInMinutes(\Carbon\Carbon::parse($ticket->first_response_at));
            $updates['response_time_minutes'] = $responseTime;
        }
        
        // Calculate resolution_time_minutes
        if ($ticket->email_received_at && $ticket->resolved_at) {
            $resolutionTime = \Carbon\Carbon::parse($ticket->email_received_at)
                ->diffInMinutes(\Carbon\Carbon::parse($ticket->resolved_at));
            $updates['resolution_time_minutes'] = $resolutionTime;
        }
        
        // Update if there are calculated metrics
        if (!empty($updates)) {
            $ticket->update($updates);
            $responseTimeMsg = $updates['response_time_minutes'] ?? 'N/A';
            $resolutionTimeMsg = $updates['resolution_time_minutes'] ?? 'N/A';
            Log::info("KPI metrics calculated for ticket #{$ticket->ticket_number}: response_time={$responseTimeMsg} min, resolution_time={$resolutionTimeMsg} min");
        }
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

        // Refresh ticket to get persisted changes
        $ticket->refresh();

        return $ticket;
    }
}