<?php

namespace App\Services;

use Carbon\Carbon;

class EmailParserService
{
    /**
     * Parse raw email content dan extract data
     * 
     * @param string $rawEmail - Raw email content yang di-copy paste
     * @return array - Parsed data siap untuk form
     */
    public function parseEmailContent(string $rawEmail): array
    {
        $parsed = [
            'emails' => [],
            'reporter' => [],
            'metadata' => [],
            'timestamps' => [],
        ];

        // Split email menjadi multiple messages (by From: pattern)
        $messages = $this->splitEmailMessages($rawEmail);

        $totalMessages = count($messages);
        
        foreach ($messages as $index => $message) {
            $emailData = $this->parseEmailMessage($message);
            
            // First email = user complaint (original report)
            if ($index === 0) {
                $parsed['reporter'] = [
                    'name' => $emailData['from_name'] ?? '',
                    'email' => $emailData['from_email'] ?? '',
                    'nip' => $this->extractNIP($emailData['body']) ?? '',
                    'department' => $this->extractDepartment($emailData['body']) ?? '',
                ];
                
                $parsed['metadata']['email_from'] = $emailData['from_email'] ?? '';
                $parsed['metadata']['email_to'] = $emailData['to'] ?? '';
                $parsed['metadata']['email_cc'] = $emailData['cc'] ?? '';
                $parsed['metadata']['subject'] = $emailData['subject'] ?? '';
                
                $parsed['timestamps']['email_received_at'] = $emailData['date'] ?? null;
                
                $parsed['emails'][] = [
                    'type' => 'user_complaint',
                    'from' => $emailData['from_email'] ?? '',
                    'from_name' => $emailData['from_name'] ?? '',
                    'to' => $emailData['to'] ?? '',
                    'cc' => $emailData['cc'] ?? '',
                    'subject' => $emailData['subject'] ?? '',
                    'body' => $emailData['body'] ?? '',
                    'timestamp' => $emailData['date'] ?? '',
                    'index' => $index + 1,
                ];
            }
            // Second email = first response (usually admin)
            elseif ($index === 1) {
                $parsed['timestamps']['first_response_at'] = $emailData['date'] ?? null;
                
                // Determine if it's admin or user response
                $isAdminResponse = $this->isAdminEmail($emailData['from_email'] ?? '');
                
                $parsed['emails'][] = [
                    'type' => $isAdminResponse ? 'admin_response' : 'user_reply',
                    'from' => $emailData['from_email'] ?? '',
                    'from_name' => $emailData['from_name'] ?? '',
                    'to' => $emailData['to'] ?? '',
                    'cc' => $emailData['cc'] ?? '',
                    'subject' => $emailData['subject'] ?? '',
                    'body' => $emailData['body'] ?? '',
                    'timestamp' => $emailData['date'] ?? '',
                    'index' => $index + 1,
                ];
            }
            // All subsequent emails = continuation of thread
            else {
                // Determine sender type
                $isAdminResponse = $this->isAdminEmail($emailData['from_email'] ?? '');
                $isLastEmail = ($index === $totalMessages - 1);
                
                // Check if this is resolution email (contains resolution keywords)
                $isResolution = $this->isResolutionEmail($emailData['subject'] ?? '', $emailData['body'] ?? '');
                
                // Set type
                if ($isResolution && $isLastEmail) {
                    $type = 'resolution';
                    $parsed['timestamps']['resolved_at'] = $emailData['date'] ?? null;
                } elseif ($isAdminResponse) {
                    $type = 'admin_reply';
                } else {
                    $type = 'user_reply';
                }
                
                $parsed['emails'][] = [
                    'type' => $type,
                    'from' => $emailData['from_email'] ?? '',
                    'from_name' => $emailData['from_name'] ?? '',
                    'to' => $emailData['to'] ?? '',
                    'cc' => $emailData['cc'] ?? '',
                    'subject' => $emailData['subject'] ?? '',
                    'body' => $emailData['body'] ?? '',
                    'timestamp' => $emailData['date'] ?? '',
                    'index' => $index + 1,
                ];
            }
        }

        // Extract subject untuk ticket
        if (!empty($parsed['metadata']['subject'])) {
            $subject = $parsed['metadata']['subject'];
            // Remove "Re:", "FW:", etc
            $subject = preg_replace('/^(Re|FW|Fwd):\s*/i', '', $subject);
            $parsed['ticket_subject'] = trim($subject);
        }

        // Build final return data with consistent structure
        $firstEmail = $parsed['emails'][0] ?? [];
        
        return [
            'subject' => $parsed['metadata']['subject'] ?? '',
            'from' => $parsed['metadata']['email_from'] ?? '',
            'from_name' => $parsed['reporter']['name'] ?? 'Unknown',
            'to' => $parsed['metadata']['email_to'] ?? '',
            'cc' => $parsed['metadata']['email_cc'] ?? null,
            'date' => $parsed['timestamps']['email_received_at'] ?? now()->toIso8601String(),
            'description' => $firstEmail['body'] ?? '',
            'parsed_emails' => $parsed['emails'], // Array of all emails in thread
            'reporter_name' => $parsed['reporter']['name'] ?? 'Unknown',
            'reporter_email' => $parsed['metadata']['email_from'] ?? '',
            'reporter_nip' => $parsed['reporter']['nip'] ?? null,
            'reporter_department' => $parsed['reporter']['department'] ?? null,
            'first_response_at' => $parsed['timestamps']['first_response_at'] ?? null,
            'resolved_at' => $parsed['timestamps']['resolved_at'] ?? null,
        ];
    }

    /**
     * Split raw email menjadi array of individual messages
     */
    protected function splitEmailMessages(string $rawEmail): array
    {
        // Split by "From:" pattern (email header)
        $pattern = '/From:\s+(.+?)(?=From:|$)/si';
        preg_match_all($pattern, $rawEmail, $matches);
        
        if (!empty($matches[0])) {
            return $matches[0];
        }

        // Fallback: treat as single message
        return [$rawEmail];
    }

    /**
     * Parse single email message
     */
    protected function parseEmailMessage(string $message): array
    {
        $data = [];

        // Extract From
        if (preg_match('/From:\s*(.+?)(?:<(.+?)>)?(?:\n|$)/i', $message, $matches)) {
            $data['from_full'] = trim($matches[1]);
            $data['from_email'] = trim($matches[2] ?? $matches[1]);
            $data['from_name'] = trim(str_replace('<' . $data['from_email'] . '>', '', $matches[1]));
        }

        // Extract To
        if (preg_match('/To:\s*(.+?)(?:\n|$)/i', $message, $matches)) {
            $data['to'] = trim($matches[1]);
        }

        // Extract Cc
        if (preg_match('/Cc:\s*(.+?)(?:\n|$)/i', $message, $matches)) {
            $data['cc'] = trim($matches[1]);
        }

        // Extract Date/Time
        if (preg_match('/Date:\s*(.+?)(?:\n|$)/i', $message, $matches)) {
            $dateStr = trim($matches[1]);
            try {
                $data['date'] = Carbon::parse($dateStr)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $data['date'] = null;
            }
        }

        // Extract Subject
        if (preg_match('/Subject:\s*(.+?)(?:\n\n|\n(?=From:)|$)/si', $message, $matches)) {
            $data['subject'] = trim($matches[1]);
        }

        // Extract Body (after all headers)
        // Body starts after first blank line
        if (preg_match('/\n\n(.+)$/s', $message, $matches)) {
            $data['body'] = trim($matches[1]);
        } elseif (preg_match('/Subject:.+?\n(.+)$/si', $message, $matches)) {
            $data['body'] = trim($matches[1]);
        }

        return $data;
    }

    /**
     * Extract NIP dari email body
     */
    protected function extractNIP(string $body): ?string
    {
        // Pattern: NIP : 04014247 atau NIP: 04014247
        if (preg_match('/NIP\s*:?\s*(\d{8,})/i', $body, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract Department dari signature/body
     */
    protected function extractDepartment(string $body): ?string
    {
        // Common patterns
        $patterns = [
            '/Department:\s*(.+?)(?:\n|$)/i',
            '/Dept:\s*(.+?)(?:\n|$)/i',
            '/Division:\s*(.+?)(?:\n|$)/i',
            // Pattern dari signature (last line biasanya)
            '/\n([A-Z\s]+)\n*$/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $matches)) {
                $dept = trim($matches[1]);
                // Filter out common signature elements
                if (!in_array(strtolower($dept), ['best regards', 'regards', 'thank you', 'thanks'])) {
                    return $dept;
                }
            }
        }

        // Check for known departments in signature
        $knownDepts = ['CUST', 'IT Infrastructure', 'ITSO', 'Finance', 'HR', 'Operations'];
        foreach ($knownDepts as $dept) {
            if (stripos($body, $dept) !== false) {
                return $dept;
            }
        }

        return null;
    }

    /**
     * Extract IP address dari body (useful for technical issues)
     */
    public function extractIPAddress(string $body): ?string
    {
        if (preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $body, $matches)) {
            return $matches[0];
        }
        return null;
    }

    /**
     * Auto-detect category dari subject/body
     */
    public function detectCategory(string $subject, string $body): string
    {
        $text = strtolower($subject . ' ' . $body);

        $categories = [
            'Technical' => ['pc', 'komputer', 'laptop', 'jaringan', 'network', 'internet', 'wifi', 'koneksi', 'login', 'password', 'sistem', 'error', 'bug'],
            'Hardware' => ['printer', 'mouse', 'keyboard', 'monitor', 'cpu', 'ram', 'hardisk', 'rusak'],
            'Software' => ['aplikasi', 'software', 'program', 'install', 'update', 'upgrade'],
            'Account' => ['akun', 'account', 'user', 'email', 'password reset', 'lupa password'],
            'Request' => ['request', 'permintaan', 'butuh', 'perlu', 'minta'],
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'Other';
    }

    /**
     * Auto-detect priority dari subject/body
     */
    public function detectPriority(string $subject, string $body): string
    {
        $text = strtolower($subject . ' ' . $body);

        // Critical keywords
        if (preg_match('/\b(urgent|critical|emergency|asap|segera|mendesak|penting sekali)\b/i', $text)) {
            return 'critical';
        }

        // High priority keywords
        if (preg_match('/\b(high|important|penting|mohon segera)\b/i', $text)) {
            return 'high';
        }

        // Low priority keywords
        if (preg_match('/\b(low|minor|kecil|tidak mendesak)\b/i', $text)) {
            return 'low';
        }

        return 'medium';
    }

    /**
     * Format parsed data untuk form input
     */
    public function formatForForm(array $parsed): array
    {
        $formData = [];

        // Reporter data
        if (!empty($parsed['reporter'])) {
            $formData['reporter_name'] = $parsed['reporter']['name'] ?? '';
            $formData['reporter_email'] = $parsed['reporter']['email'] ?? '';
            $formData['reporter_nip'] = $parsed['reporter']['nip'] ?? '';
            $formData['reporter_department'] = $parsed['reporter']['department'] ?? '';
        }

        // Ticket data
        $formData['subject'] = $parsed['ticket_subject'] ?? '';
        $formData['channel'] = 'email';
        $formData['input_method'] = 'email';

        // Auto-detect category & priority
        $allText = implode(' ', array_column($parsed['emails'], 'body'));
        $formData['category'] = $this->detectCategory($formData['subject'], $allText);
        $formData['priority'] = $this->detectPriority($formData['subject'], $allText);

        // Email metadata
        if (!empty($parsed['metadata'])) {
            $formData['email_from'] = $parsed['metadata']['email_from'] ?? '';
            $formData['email_to'] = $parsed['metadata']['email_to'] ?? '';
            $formData['email_cc'] = $parsed['metadata']['email_cc'] ?? '';
            $formData['email_subject'] = $parsed['metadata']['subject'] ?? '';
        }

        // Timestamps
        if (!empty($parsed['timestamps'])) {
            if (!empty($parsed['timestamps']['email_received_at'])) {
                $formData['email_received_at'] = Carbon::parse($parsed['timestamps']['email_received_at'])->format('Y-m-d\TH:i');
            }
            if (!empty($parsed['timestamps']['first_response_at'])) {
                $formData['first_response_at'] = Carbon::parse($parsed['timestamps']['first_response_at'])->format('Y-m-d\TH:i');
            }
            if (!empty($parsed['timestamps']['resolved_at'])) {
                $formData['resolved_at'] = Carbon::parse($parsed['timestamps']['resolved_at'])->format('Y-m-d\TH:i');
            }
        }

        // Email bodies
        if (!empty($parsed['emails'])) {
            foreach ($parsed['emails'] as $email) {
                if ($email['type'] === 'user_complaint') {
                    $formData['email_body_original'] = $email['body'];
                    $formData['description'] = substr($email['body'], 0, 500); // First 500 chars
                } elseif ($email['type'] === 'admin_response') {
                    $formData['email_response_admin'] = $email['body'];
                } elseif ($email['type'] === 'resolution') {
                    $formData['email_resolution_message'] = $email['body'];
                }
            }
        }

        return $formData;
    }

    /**
     * Check if email is from admin/support
     */
    protected function isAdminEmail(string $email): bool
    {
        $adminDomains = config('mail.imap.admin_domains', ['support', 'helpdesk', 'it.infrastructure', 'admin']);
        $adminEmails = config('mail.imap.admin_emails', []);
        
        // Check full email match
        if (in_array(strtolower($email), array_map('strtolower', $adminEmails))) {
            return true;
        }
        
        // Check if email contains admin keywords
        foreach ($adminDomains as $keyword) {
            if (stripos($email, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if email is resolution/closing email
     */
    protected function isResolutionEmail(string $subject, string $body): bool
    {
        $resolutionKeywords = [
            // Subject patterns
            'resolved', 'closed', 'completed', 'selesai', 'ditutup', 
            '[resolved]', '[closed]', '[completed]',
            
            // Body patterns
            'masalah telah diselesaikan',
            'issue has been resolved',
            'ticket is now closed',
            'terima kasih telah menggunakan layanan',
            'thank you for using our service',
            'case is closed',
            'sudah selesai',
            'telah terselesaikan',
        ];
        
        $content = strtolower($subject . ' ' . $body);
        
        foreach ($resolutionKeywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
