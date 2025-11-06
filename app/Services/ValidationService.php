<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Validator;

class ValidationService
{
    /**
     * Validate ticket data.
     */
    public function validate(Ticket $ticket)
    {
        // Bypass validation for email auto-fetch (already validated by email filter)
        if ($ticket->input_method === 'email_auto_fetch') {
            return ['valid' => true];
        }
        
        // 1. Check Data completeness
        if (empty($ticket->user_name) || empty($ticket->user_email) || 
            empty($ticket->subject) || strlen($ticket->description) < 10) {
            return [
                'valid' => false,
                'reason' => 'Data tidak lengkap. Mohon lengkapi nama, email, subjek, dan deskripsi.'
            ];
        }

        // 2. Validate email format
        if (!filter_var($ticket->user_email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'reason' => 'Format email tidak valid.'
            ];
        }

        // 3. Check For Duplicate
        $duplicate = Ticket::where('user_email', $ticket->user_email)
            ->where('id', '!=', $ticket->id)
            ->where('created_at', '>=', now()->subHours(48))
            ->where(function($q) use ($ticket) {
                $q->where('subject', 'LIKE', '%' . $ticket->subject . '%')
                  ->orWhere('description', 'LIKE', '%' . substr($ticket->description, 0, 50) . '%');
            })
            ->first();

        if ($duplicate) {
            return [
                'valid' => false,
                'reason' => "Anda sudah membuat ticket serupa ({$duplicate->ticket_number}). Mohon gunakan ticket tersebut untuk follow-up."
            ];
        }

        // 4. Check for spam patterns
        $spamKeywords = ['test', 'testing', 'aaaa', 'xxxx'];
        $text = strtolower($ticket->subject . ' ' . $ticket->description);
        
        foreach ($spamKeywords as $spam) {
            if (str_contains($text, $spam) && strlen($ticket->description) < 30) {
                return [
                    'valid' => false,
                    'reason' => 'Ticket terdeteksi sebagai spam atau tidak valid.'
                ];
            }
        }

        return ['valid' => true];
    }
}