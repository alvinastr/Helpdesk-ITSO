<?php

namespace App\Services;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmailFetcherService
{
    protected $emailParser;
    protected $ticketService;
    
    public function __construct(EmailParserService $emailParser, TicketService $ticketService)
    {
        $this->emailParser = $emailParser;
        $this->ticketService = $ticketService;
    }

    /**
     * Fetch emails dari IMAP mailbox dan create tickets otomatis
     */
    public function fetchAndProcessEmails(): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            // Connect ke IMAP
            $mailbox = $this->connectToMailbox();
            
            if (!$mailbox) {
                throw new \Exception('Failed to connect to mailbox');
            }

            // Get unread emails
            $emails = $this->getUnreadEmails($mailbox);
            
            Log::info("Found " . count($emails) . " unread emails");

            foreach ($emails as $emailId => $emailData) {
                try {
                    // Check if already processed (by message-id or unique identifier)
                    if ($this->isEmailAlreadyProcessed($emailData['message_id'])) {
                        Log::info("Email {$emailData['message_id']} already processed, skipping");
                        $results['skipped']++;
                        continue;
                    }

                    // Create ticket from email
                    $ticket = $this->createTicketFromEmail($emailData);
                    
                    if ($ticket) {
                        // Mark email as read
                        $this->markEmailAsRead($mailbox, $emailId);
                        
                        Log::info("Created ticket {$ticket->ticket_number} from email {$emailData['message_id']}");
                        $results['success']++;
                    } else {
                        $results['failed']++;
                    }

                } catch (\Exception $e) {
                    Log::error("Failed to process email: " . $e->getMessage());
                    $results['failed']++;
                    $results['errors'][] = $e->getMessage();
                }
            }

            // Close connection
            $this->closeMailbox($mailbox);

        } catch (\Exception $e) {
            Log::error("Email fetch error: " . $e->getMessage());
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Connect ke IMAP mailbox
     */
    protected function connectToMailbox()
    {
        $host = config('mail.imap.host');
        $port = config('mail.imap.port', 993);
        $username = config('mail.imap.username');
        $password = config('mail.imap.password');
        $encryption = config('mail.imap.encryption', 'ssl');
        $validateCert = config('mail.imap.validate_cert', true);

        if (!$host || !$username || !$password) {
            throw new \Exception('IMAP configuration is missing');
        }

        // Build IMAP connection string
        $certValidation = $validateCert ? '/validate-cert' : '/novalidate-cert';
        $connectionString = "{{$host}:{$port}/imap/{$encryption}{$certValidation}}INBOX";

        try {
            $mailbox = imap_open($connectionString, $username, $password);
            
            if (!$mailbox) {
                $error = imap_last_error();
                throw new \Exception("IMAP connection failed: {$error}");
            }

            return $mailbox;

        } catch (\Exception $e) {
            Log::error("IMAP connection error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get unread emails from mailbox
     */
    protected function getUnreadEmails($mailbox): array
    {
        $emails = [];

        // Build search criteria dengan filter
        $searchCriteria = $this->buildSearchCriteria();
        
        // Search for emails matching criteria
        $emailIds = imap_search($mailbox, $searchCriteria);

        if (!$emailIds) {
            return [];
        }

        // Limit processing (agar tidak overload)
        $limit = config('mail.imap.fetch_limit', 50);
        $emailIds = array_slice($emailIds, 0, $limit);

        foreach ($emailIds as $emailId) {
            try {
                $header = imap_headerinfo($mailbox, $emailId);
                $structure = imap_fetchstructure($mailbox, $emailId);
                $body = $this->getEmailBody($mailbox, $emailId, $structure);

                // Extract email data
                $from = $header->from[0];
                $fromEmail = $from->mailbox . '@' . $from->host;
                $fromName = isset($from->personal) ? $this->decodeEmailText($from->personal) : $fromEmail;

                $to = isset($header->to[0]) ? $header->to[0]->mailbox . '@' . $header->to[0]->host : '';
                
                $cc = '';
                if (isset($header->cc)) {
                    $ccAddresses = array_map(function($c) {
                        return $c->mailbox . '@' . $c->host;
                    }, $header->cc);
                    $cc = implode(', ', $ccAddresses);
                }

                $subject = isset($header->subject) ? $this->decodeEmailText($header->subject) : '(No Subject)';
                $date = isset($header->date) ? Carbon::parse($header->date) : Carbon::now();
                $messageId = isset($header->message_id) ? $header->message_id : "email-{$emailId}-" . time();

                $emailData = [
                    'from' => $fromEmail,
                    'from_name' => $fromName,
                    'to' => $to,
                    'cc' => $cc,
                    'subject' => $subject,
                    'body' => $body,
                    'date' => $date,
                    'message_id' => $messageId,
                ];

                // ✅ FILTER: Validasi email sebelum diproses
                if (!$this->isValidEmail($emailData)) {
                    Log::info("Email {$emailId} filtered out (tidak sesuai kriteria)");
                    continue;
                }

                $emails[$emailId] = $emailData;

            } catch (\Exception $e) {
                Log::error("Failed to parse email ID {$emailId}: " . $e->getMessage());
            }
        }

        return $emails;
    }

    /**
     * Get email body (handle multipart)
     */
    protected function getEmailBody($mailbox, $emailId, $structure): string
    {
        $body = '';

        // Check if multipart
        if (isset($structure->parts) && count($structure->parts)) {
            // Multipart email
            foreach ($structure->parts as $partNum => $part) {
                // Get text/plain or text/html
                if ($part->subtype == 'PLAIN' || $part->subtype == 'HTML') {
                    $partBody = imap_fetchbody($mailbox, $emailId, $partNum + 1);
                    
                    // Decode if needed
                    if ($part->encoding == 3) {
                        $partBody = base64_decode($partBody);
                    } elseif ($part->encoding == 4) {
                        $partBody = quoted_printable_decode($partBody);
                    }
                    
                    $body .= $partBody;
                    
                    // Prefer plain text, so break if found
                    if ($part->subtype == 'PLAIN') {
                        break;
                    }
                }
            }
        } else {
            // Simple email
            $body = imap_body($mailbox, $emailId);
            
            // Decode if needed
            if ($structure->encoding == 3) {
                $body = base64_decode($body);
            } elseif ($structure->encoding == 4) {
                $body = quoted_printable_decode($body);
            }
        }

        return trim($body);
    }

    /**
     * Decode email text (handle encoding)
     */
    protected function decodeEmailText($text): string
    {
        $elements = imap_mime_header_decode($text);
        $decoded = '';
        
        foreach ($elements as $element) {
            $decoded .= $element->text;
        }
        
        return $decoded;
    }

    /**
     * Check if email already processed
     */
    protected function isEmailAlreadyProcessed($messageId): bool
    {
        return Ticket::where('email_metadata->message_id', $messageId)->exists();
    }

    /**
     * Create ticket from email data
     */
    protected function createTicketFromEmail(array $emailData): ?Ticket
    {
        try {
            // Build raw email format untuk parser
            $rawEmail = $this->buildRawEmailFormat($emailData);
            
            // Parse email using existing parser
            $parsed = $this->emailParser->parseEmailContent($rawEmail);
            
            // Prepare ticket data
            $ticketData = [
                // Reporter info
                'reporter_name' => $parsed['reporter']['name'] ?: $emailData['from_name'],
                'reporter_email' => $parsed['reporter']['email'] ?: $emailData['from'],
                'reporter_nip' => $parsed['reporter']['nip'] ?: 'AUTO-' . substr(md5($emailData['from']), 0, 8),
                'reporter_phone' => '-',
                'reporter_department' => $parsed['reporter']['department'] ?: 'Unknown',
                
                // Ticket info
                'subject' => $parsed['ticket_subject'] ?: $emailData['subject'],
                'description' => !empty($parsed['emails']) ? $parsed['emails'][0]['body'] : $emailData['body'],
                'channel' => 'email',
                'input_method' => 'email_auto',
                'priority' => 'medium', // Could be auto-detected
                'category' => 'general', // Could be auto-detected
                
                // Email metadata
                'email_from' => $emailData['from'],
                'email_to' => $emailData['to'],
                'email_cc' => $emailData['cc'],
                'email_subject' => $emailData['subject'],
                'email_body_original' => $emailData['body'],
                
                // KPI timestamps
                'email_received_at' => $emailData['date'],
                
                // Store message_id untuk prevent duplicate
                'email_metadata' => [
                    'message_id' => $emailData['message_id'],
                    'auto_created' => true,
                    'created_via' => 'imap_fetch',
                ],
            ];

            // Create ticket via service
            $ticket = $this->ticketService->createTicketByAdmin($ticketData);

            return $ticket;

        } catch (\Exception $e) {
            Log::error("Failed to create ticket from email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Build raw email format for parser
     */
    protected function buildRawEmailFormat(array $emailData): string
    {
        $raw = "From: {$emailData['from_name']} <{$emailData['from']}>\n";
        $raw .= "To: {$emailData['to']}\n";
        
        if (!empty($emailData['cc'])) {
            $raw .= "Cc: {$emailData['cc']}\n";
        }
        
        $raw .= "Date: {$emailData['date']->format('l, d F Y H:i')}\n";
        $raw .= "Subject: {$emailData['subject']}\n\n";
        $raw .= $emailData['body'];
        
        return $raw;
    }

    /**
     * Mark email as read
     */
    protected function markEmailAsRead($mailbox, $emailId): bool
    {
        return imap_setflag_full($mailbox, $emailId, "\\Seen");
    }

    /**
     * Close mailbox connection
     */
    protected function closeMailbox($mailbox): void
    {
        if ($mailbox) {
            imap_close($mailbox);
        }
    }

    /**
     * Build IMAP search criteria dengan filter
     */
    protected function buildSearchCriteria(): string
    {
        $criteria = ['UNSEEN']; // Base: unread emails

        // Filter by date (optional)
        $daysBack = config('mail.imap.fetch_days_back');
        if ($daysBack) {
            $date = Carbon::now()->subDays($daysBack)->format('d-M-Y');
            $criteria[] = "SINCE \"{$date}\"";
        }

        // Filter by sender domain (optional)
        $allowedDomains = config('mail.imap.allowed_sender_domains', []);
        if (!empty($allowedDomains)) {
            // Note: IMAP search tidak support OR untuk multiple FROM
            // Jadi kita filter di PHP level (lihat isValidEmail method)
        }

        return implode(' ', $criteria);
    }

    /**
     * Validasi apakah email sesuai kriteria untuk diproses
     */
    protected function isValidEmail(array $emailData): bool
    {
        // 1. Filter by sender domain
        if (!$this->isValidSenderDomain($emailData['from'])) {
            Log::info("Email filtered: Invalid sender domain ({$emailData['from']})");
            return false;
        }

        // 2. Filter by recipient (pastikan dikirim ke inbox yang benar)
        if (!$this->isValidRecipient($emailData['to'], $emailData['cc'])) {
            Log::info("Email filtered: Not sent to valid recipient");
            return false;
        }

        // 3. Filter spam/auto-reply
        if ($this->isSpamOrAutoReply($emailData['subject'], $emailData['body'])) {
            Log::info("Email filtered: Detected as spam/auto-reply");
            return false;
        }

        // 4. Filter by subject keywords (optional)
        if (!$this->hasValidSubjectKeywords($emailData['subject'])) {
            Log::info("Email filtered: Subject doesn't match keywords");
            return false;
        }

        // 5. Filter by minimum content length
        if (!$this->hasValidContentLength($emailData['body'])) {
            Log::info("Email filtered: Content too short");
            return false;
        }

        // Semua validasi passed
        return true;
    }

    /**
     * Check apakah sender domain valid
     */
    protected function isValidSenderDomain(string $email): bool
    {
        $allowedDomains = config('mail.imap.allowed_sender_domains', []);
        
        // Jika tidak ada filter domain, allow all
        if (empty($allowedDomains)) {
            return true;
        }

        // Extract domain dari email
        $domain = substr(strrchr($email, '@'), 1);

        // Check if domain in whitelist
        return in_array($domain, $allowedDomains);
    }

    /**
     * Check apakah recipient valid
     */
    protected function isValidRecipient(string $to, string $cc): bool
    {
        $validRecipients = config('mail.imap.valid_recipients', []);
        
        // Jika tidak ada filter recipient, allow all
        if (empty($validRecipients)) {
            return true;
        }

        // Combine TO and CC
        $allRecipients = strtolower($to . ',' . $cc);

        // Check if any valid recipient in TO or CC
        foreach ($validRecipients as $validRecipient) {
            if (stripos($allRecipients, strtolower($validRecipient)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect spam atau auto-reply
     */
    protected function isSpamOrAutoReply(string $subject, string $body): bool
    {
        $spamKeywords = [
            // Auto-reply indicators
            'out of office',
            'automatic reply',
            'auto-reply',
            'away from office',
            'vacation',
            'do not reply',
            'noreply',
            'no-reply',
            'mailer-daemon',
            'delivery failure',
            'undelivered',
            'returned mail',
            
            // Spam indicators
            'viagra',
            'casino',
            'lottery',
            'winner',
            'congratulations you won',
            'click here now',
            'limited time offer',
            'act now',
            'buy now',
        ];

        $content = strtolower($subject . ' ' . $body);

        foreach ($spamKeywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check apakah subject mengandung keywords yang valid
     */
    protected function hasValidSubjectKeywords(string $subject): bool
    {
        $requiredKeywords = config('mail.imap.required_subject_keywords', []);
        
        // Jika tidak ada required keywords, allow all
        if (empty($requiredKeywords)) {
            return true;
        }

        $subject = strtolower($subject);

        // Check if any required keyword in subject
        foreach ($requiredKeywords as $keyword) {
            if (stripos($subject, strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check apakah email content memiliki panjang minimal
     */
    protected function hasValidContentLength(string $body): bool
    {
        $minLength = config('mail.imap.min_content_length', 10);
        
        // Remove whitespace untuk akurat
        $cleanBody = trim($body);
        
        return strlen($cleanBody) >= $minLength;
    }
}
