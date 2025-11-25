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
            Log::info('=== Starting Email Fetch Process ===');
            Log::info('IMAP Config', [
                'host' => config('mail.imap.host'),
                'port' => config('mail.imap.port'),
            ]);

            // Connect ke IMAP
            $mailbox = $this->connectToMailbox();

            if (!$mailbox) {
                throw new \Exception('Failed to connect to mailbox');
            }

            Log::info('Connected to mailbox successfully');

            // Get unread emails
            $emails = $this->getUnreadEmails($mailbox);

            Log::info('Found unread emails', ['count' => count($emails)]);

            foreach ($emails as $emailId => $emailData) {
                try {
                    // Check if already processed (by message-id or unique identifier)
                    if ($this->isEmailAlreadyProcessed($emailData['message_id'])) {
                        Log::info('Email already processed', ['message_id' => $emailData['message_id']]);
                        $results['skipped']++;
                        continue;
                    }

                    // Create ticket from email
                    $ticket = $this->createTicketFromEmail($emailData);

                    if ($ticket) {
                        // Mark email as read
                        $this->markEmailAsRead($mailbox, $emailId);

                        Log::info('Created ticket from email', ['ticket_number' => $ticket->ticket_number, 'message_id' => $emailData['message_id']]);
                        $results['success']++;
                    } else {
                        $errorMsg = "Failed to create ticket from: {$emailData['subject']}";
                        $results['failed']++;
                        $results['errors'][] = $errorMsg;
                        Log::error($errorMsg);
                    }
                } catch (\Exception $e) {
                    $errorMsg = "Error processing '{$emailData['subject']}': " . $e->getMessage();
                    Log::error('Failed to process email', ['error' => $e->getMessage(), 'subject' => $emailData['subject']]);
                    $results['failed']++;
                    $results['errors'][] = $errorMsg;
                }
            }

            // Close connection
            $this->closeMailbox($mailbox);
        } catch (\Exception $e) {
            Log::error('Email fetch error', ['error' => $e->getMessage()]);
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

        // Validasi konfigurasi dengan pesan error yang jelas
        if (empty($host)) {
            throw new \Exception('IMAP_HOST is not configured in .env file');
        }
        
        if (empty($username)) {
            throw new \Exception('IMAP_USERNAME is not configured in .env file');
        }
        
        if (empty($password)) {
            throw new \Exception('IMAP_PASSWORD is not configured in .env file');
        }

        // Build IMAP connection string dengan berbagai fallback options
        $certValidation = $validateCert ? '/validate-cert' : '/novalidate-cert';
        
        // Coba berbagai kombinasi connection string untuk compatibility
        $connectionOptions = [
            // Option 1: SSL dengan novalidate-cert
            ['string' => "{{$host}:{$port}/imap/ssl{$certValidation}}INBOX", 'desc' => 'SSL with novalidate-cert'],
            
            // Option 2: SSL tanpa INBOX
            ['string' => "{{$host}:{$port}/imap/ssl{$certValidation}}", 'desc' => 'SSL to root mailbox'],
            
            // Option 3: SSL dengan readonly flag
            ['string' => "{{$host}:{$port}/imap/ssl{$certValidation}/readonly}INBOX", 'desc' => 'SSL readonly'],
            
            // Option 4: TLS dengan STARTTLS
            ['string' => "{{$host}:{$port}/imap/tls{$certValidation}}INBOX", 'desc' => 'TLS with STARTTLS'],
            
            // Option 5: Plain IMAP tanpa encryption
            ['string' => "{{$host}:{$port}/imap{$certValidation}}INBOX", 'desc' => 'Plain IMAP no encryption'],
            
            // Option 6: Port 143 dengan TLS
            ['string' => "{{$host}:143/imap/tls{$certValidation}}INBOX", 'desc' => 'Port 143 with TLS'],
            
            // Option 7: Port 143 plain
            ['string' => "{{$host}:143/imap{$certValidation}}INBOX", 'desc' => 'Port 143 plain'],
            
            // Option 8: SSL dengan notls flag
            ['string' => "{{$host}:{$port}/imap/ssl{$certValidation}/notls}INBOX", 'desc' => 'SSL with notls'],
        ];

        // Log connection attempt (tanpa password untuk security)
        Log::info("🔌 Attempting IMAP connection to {$host}:{$port}", [
            'username' => $username,
            'encryption' => $encryption,
            'validate_cert' => $validateCert ? 'true' : 'false',
        ]);
        
        // Test basic connectivity dulu
        Log::info("Testing basic connectivity...");
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        if (!$fp) {
            throw new \Exception("Cannot connect to {$host}:{$port} - Error #{$errno}: {$errstr}");
        }
        fclose($fp);
        Log::info("✓ Port {$port} is reachable");

        $lastError = null;
        $connectionAttempts = [];
        
        // Try each connection option
        foreach ($connectionOptions as $index => $option) {
            $connectionString = $option['string'];
            $desc = $option['desc'];
            
            try {
                Log::info("🔄 Trying option " . ($index + 1) . "/" . count($connectionOptions) . ": {$desc}");
                Log::info("   Connection string: " . $connectionString);
                
                // Clear previous IMAP errors
                @imap_errors();
                @imap_alerts();
                
                // Enable error reporting temporarily for debugging
                $errorReporting = error_reporting();
                error_reporting(E_ALL);
                
                // Attempt connection with longer timeout (3 retries, 5 second timeout each)
                $mailbox = @imap_open($connectionString, $username, $password, 0, 3, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
                
                // Restore error reporting
                error_reporting($errorReporting);
                
                if ($mailbox) {
                    Log::info("✅ SUCCESS! Connected to IMAP server using option " . ($index + 1) . ": {$desc}");
                    return $mailbox;
                }
                
                // Jika gagal, catat error detail
                $errors = imap_errors();
                $alerts = imap_alerts();
                $lastError = imap_last_error();
                
                $errorDetail = $lastError ?: 'No error message from PHP IMAP';
                if ($errors && is_array($errors)) {
                    $errorDetail .= ' | ' . implode(' | ', $errors);
                }
                
                $connectionAttempts[] = "Option " . ($index + 1) . " ({$desc}): {$errorDetail}";
                
                Log::warning("❌ Option " . ($index + 1) . " failed: {$errorDetail}");
                if ($alerts && is_array($alerts)) {
                    Log::warning("   Alerts: " . implode(', ', $alerts));
                }
                
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                $connectionAttempts[] = "Option " . ($index + 1) . " ({$desc}): Exception - {$errorMsg}";
                Log::warning("❌ Option " . ($index + 1) . " threw exception: {$errorMsg}");
            }
            
            // Small delay between attempts
            usleep(100000); // 0.1 second
        }

        // Semua opsi gagal
        $errors = imap_errors();
        $alerts = imap_alerts();
        
        $errorMessage = "IMAP connection failed after trying " . count($connectionOptions) . " connection methods";
        $errorMessage .= "\n\nServer: {$host}:{$port}";
        $errorMessage .= "\nUsername: {$username}";
        $errorMessage .= "\n\nAttempt results:\n" . implode("\n", $connectionAttempts);
        
        if ($errors) {
            $errorMessage .= "\n\nRecent IMAP errors: " . implode(', ', array_slice($errors, -3));
        }
        if ($alerts) {
            $errorMessage .= "\n\nIMAP alerts: " . implode(', ', $alerts);
        }
        
        $errorMessage .= "\n\n💡 Troubleshooting tips:";
        $errorMessage .= "\n1. Verify server is reachable: telnet {$host} {$port}";
        $errorMessage .= "\n2. Check if username/password is correct";
        $errorMessage .= "\n3. Try connecting with desktop email client (Thunderbird/Outlook)";
        $errorMessage .= "\n4. Verify PHP IMAP extension is properly installed: php -m | grep imap";
        $errorMessage .= "\n5. Check if server requires different port (143 for non-SSL)";
        
        Log::error("✗ All IMAP connection attempts failed");
        Log::error($errorMessage);
        
        throw new \Exception($errorMessage);
    }

    /**
     * Get unread emails from mailbox
     */
    protected function getUnreadEmails($mailbox): array
    {
        $emails = [];

        // Build search criteria dengan filter
        $searchCriteria = $this->buildSearchCriteria();
        
        Log::info("Search criteria: {$searchCriteria}");
        
        // Search for emails matching criteria
        $emailIds = imap_search($mailbox, $searchCriteria);

        if (!$emailIds) {
            Log::info("⚠ No unread emails found in mailbox");
            return [];
        }

        Log::info("Found " . count($emailIds) . " unread emails from IMAP search");

        // Limit processing (agar tidak overload)
        $limit = config('mail.imap.fetch_limit', 50);
        $emailIds = array_slice($emailIds, 0, $limit);
        
        Log::info("Processing " . count($emailIds) . " emails (limit: {$limit})");

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
        return Ticket::where('email_message_id', $messageId)->exists();
    }

    /**
     * Create ticket from email data
     */
    protected function createTicketFromEmail(array $emailData): ?Ticket
    {
        try {
            $autoNip = 'AUTO-' . substr(md5($emailData['from']), 0, 8);
            $emailHeaders = json_encode(array(
                'from' => $emailData['from'],
                'to' => $emailData['to'],
                'cc' => $emailData['cc'],
                'subject' => $emailData['subject'],
                'message_id' => $emailData['message_id']
            ));
            
            $ticketData = array();
            $ticketData['reporter_name'] = $emailData['from_name'];
            $ticketData['reporter_email'] = $emailData['from'];
            $ticketData['reporter_nip'] = $autoNip;
            $ticketData['reporter_phone'] = '-';
            $ticketData['reporter_department'] = 'Auto-created from Email';
            $ticketData['user_name'] = $emailData['from_name'];
            $ticketData['user_email'] = $emailData['from'];
            $ticketData['user_phone'] = '-';
            $ticketData['subject'] = $emailData['subject'];
            $ticketData['description'] = $emailData['body'];
            $ticketData['channel'] = 'email';
            $ticketData['input_method'] = 'email_auto';
            $ticketData['priority'] = 'medium';
            $ticketData['category'] = 'general';
            $ticketData['created_by_admin'] = 1;
            $ticketData['email_from'] = $emailData['from'];
            $ticketData['email_to'] = $emailData['to'];
            $ticketData['email_cc'] = $emailData['cc'];
            $ticketData['email_subject'] = $emailData['subject'];
            $ticketData['email_body_original'] = $emailData['body'];
            $ticketData['original_message'] = $emailData['body'];
            $ticketData['email_message_id'] = $emailData['message_id'];
            $ticketData['sender_email'] = $emailData['from'];
            $ticketData['email_received_at'] = $emailData['date'];
            $ticketData['email_headers'] = $emailHeaders;

            $ticket = $this->ticketService->createTicketByAdmin($ticketData);

            return $ticket;

        } catch (\Exception $e) {
            $errorData = array();
            $errorData['email_subject'] = isset($emailData['subject']) ? $emailData['subject'] : 'N/A';
            $errorData['email_from'] = isset($emailData['from']) ? $emailData['from'] : 'N/A';
            $errorData['trace'] = $e->getTraceAsString();
            Log::error("Failed to create ticket from email: " . $e->getMessage(), $errorData);
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

    /**
     * Test network connectivity ke IMAP host sebelum koneksi
     */
    protected function testNetworkConnectivity(string $host, int $port): void
    {
        Log::info("Testing network connectivity to {$host}:{$port}...");
        
        // Test 1: Ping host (DNS resolution)
        $dnsResolved = gethostbyname($host);
        if ($dnsResolved === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            Log::warning("⚠ DNS resolution failed for {$host}");
            throw new \Exception("Cannot resolve hostname: {$host}. Check if IMAP_HOST is correct.");
        }
        Log::info("✓ DNS resolved: {$host} -> {$dnsResolved}");
        
        // Test 2: Port connectivity
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        if (!$connection) {
            Log::error("✗ Cannot connect to {$host}:{$port} - Error #{$errno}: {$errstr}");
            throw new \Exception(
                "Cannot connect to IMAP server at {$host}:{$port}. " .
                "Possible causes: " .
                "1) Server is down or unreachable, " .
                "2) Firewall blocking port {$port}, " .
                "3) Wrong host/port configuration, " .
                "4) Not connected to VPN/internal network (if required). " .
                "Error: {$errstr}"
            );
        }
        fclose($connection);
        Log::info("✓ Port {$port} is reachable on {$host}");
    }
}

