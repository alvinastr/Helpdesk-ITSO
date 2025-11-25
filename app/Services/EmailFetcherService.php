<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\EmailFetchLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmailFetcherService
{
    protected $emailParser;
    protected $ticketService;
    protected $fetchLog;
    
    public function __construct(EmailParserService $emailParser, TicketService $ticketService)
    {
        $this->emailParser = $emailParser;
        $this->ticketService = $ticketService;
    }

    /**
     * Fetch emails dari IMAP mailbox dan create tickets otomatis
     */
    public function fetchAndProcessEmails($includeRead = false): array
    {
        // Create fetch log entry
        $this->fetchLog = EmailFetchLog::create([
            'fetch_started_at' => now(),
            'status' => 'running',
        ]);

        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'filtered' => 0,
            'errors' => [],
        ];

        try {
            Log::info('=== Starting Email Fetch Process ===', ['log_id' => $this->fetchLog->id]);
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

            // Get emails (unread atau semua)
            $emails = $includeRead ? $this->getAllEmails($mailbox) : $this->getUnreadEmails($mailbox);

            Log::info('Found emails', ['count' => count($emails), 'include_read' => $includeRead]);

            foreach ($emails as $emailId => $emailData) {
                try {
                    // Check if already processed (by message-id or unique identifier)
                    if ($this->isEmailAlreadyProcessed($emailData['message_id'])) {
                        Log::info('Email already processed', ['message_id' => $emailData['message_id']]);
                        $results['skipped']++;
                        continue;
                    }

                    // Check if this is a reply to existing ticket
                    $existingTicket = $this->findRelatedTicket($emailData);
                    
                    if ($existingTicket) {
                        // Add comment to existing ticket
                        $this->addCommentToTicket($existingTicket, $emailData);
                        Log::info('Added comment to existing ticket', [
                            'ticket_number' => $existingTicket->ticket_number,
                            'from' => $emailData['from'],
                            'subject' => $emailData['subject']
                        ]);
                        $results['success']++;
                    } else {
                        // Create new ticket from email
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

            // Update fetch log with success
            $this->fetchLog->update([
                'fetch_completed_at' => now(),
                'total_fetched' => count($emails),
                'successful' => $results['success'],
                'failed' => $results['failed'],
                'duplicates' => $results['skipped'],
                'status' => 'completed',
                'error_message' => !empty($results['errors']) ? implode("\n", $results['errors']) : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Email fetch error', ['error' => $e->getMessage()]);
            $results['errors'][] = $e->getMessage();

            // Update fetch log with error
            if ($this->fetchLog) {
                $this->fetchLog->update([
                    'fetch_completed_at' => now(),
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
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
     * Get email body (handle multipart with better parsing)
     */
    protected function getEmailBody($mailbox, $emailId, $structure): string
    {
        $body = '';
        $plainBody = '';
        $htmlBody = '';

        // Check if multipart
        if (isset($structure->parts) && count($structure->parts)) {
            // Multipart email - process all parts recursively
            $this->processEmailParts($mailbox, $emailId, $structure->parts, '', $plainBody, $htmlBody);
            
            // Prefer plain text, fallback to HTML
            if (!empty($plainBody)) {
                $body = $plainBody;
            } elseif (!empty($htmlBody)) {
                // Strip HTML tags dari HTML body
                $body = strip_tags($htmlBody);
            }
        } else {
            // Simple email (not multipart)
            $body = imap_body($mailbox, $emailId);
            
            // Decode if needed
            if (isset($structure->encoding)) {
                if ($structure->encoding == 3) {
                    $body = base64_decode($body);
                } elseif ($structure->encoding == 4) {
                    $body = quoted_printable_decode($body);
                } elseif ($structure->encoding == 1) {
                    $body = imap_8bit($body);
                }
            }
        }

        // Clean and normalize body
        $body = $this->cleanEmailBody($body);
        
        // Fallback: jika body masih kosong, ambil subject sebagai body
        if (empty($body)) {
            $header = imap_headerinfo($mailbox, $emailId);
            $subject = isset($header->subject) ? $this->decodeEmailText($header->subject) : '';
            $body = "[Email with subject: {$subject}]";
            Log::warning("Email {$emailId} has no body content, using subject as fallback");
        }

        return $body;
    }

    /**
     * Clean and normalize email body text
     */
    protected function cleanEmailBody($body): string
    {
        // Decode quoted-printable if still encoded
        if (strpos($body, '=') !== false) {
            $body = quoted_printable_decode($body);
        }
        
        // Convert HTML entities
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove soft line breaks (=\n)
        $body = str_replace("=\n", '', $body);
        $body = str_replace("=\r\n", '', $body);
        
        // Normalize line breaks
        $body = str_replace("\r\n", "\n", $body);
        $body = str_replace("\r", "\n", $body);
        
        // Remove multiple consecutive line breaks (max 2)
        $body = preg_replace("/\n{3,}/", "\n\n", $body);
        
        // Trim whitespace
        $body = trim($body);
        
        return $body;
    }

    /**
     * Recursively process email parts (untuk handle nested multipart)
     */
    protected function processEmailParts($mailbox, $emailId, $parts, $prefix, &$plainBody, &$htmlBody)
    {
        foreach ($parts as $partNum => $part) {
            $currentPrefix = $prefix . ($partNum + 1);
            
            // Check if this part has sub-parts (nested multipart)
            if (isset($part->parts) && count($part->parts)) {
                $this->processEmailParts($mailbox, $emailId, $part->parts, $currentPrefix . '.', $plainBody, $htmlBody);
                continue;
            }
            
            // Get part body
            $partBody = imap_fetchbody($mailbox, $emailId, $currentPrefix);
            
            if (empty($partBody)) {
                continue;
            }
            
            // Decode based on encoding
            if (isset($part->encoding)) {
                if ($part->encoding == 3) {
                    $partBody = base64_decode($partBody);
                } elseif ($part->encoding == 4) {
                    $partBody = quoted_printable_decode($partBody);
                } elseif ($part->encoding == 1) {
                    $partBody = imap_8bit($partBody);
                }
            }
            
            // Handle charset conversion
            $charset = 'UTF-8';
            if (isset($part->parameters)) {
                foreach ($part->parameters as $param) {
                    if (strtolower($param->attribute) == 'charset') {
                        $charset = $param->value;
                        break;
                    }
                }
            }
            
            // Convert to UTF-8 if needed
            if (strtoupper($charset) != 'UTF-8') {
                $partBody = @iconv($charset, 'UTF-8//IGNORE', $partBody);
            }
            
            // Collect plain text or HTML
            if (isset($part->subtype)) {
                if ($part->subtype == 'PLAIN') {
                    $plainBody .= $partBody . "\n";
                } elseif ($part->subtype == 'HTML') {
                    $htmlBody .= $partBody;
                }
            }
        }
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
    /**
     * Find related ticket from email subject or reference
     */
    protected function findRelatedTicket(array $emailData): ?Ticket
    {
        $subject = $emailData['subject'];
        
        // Remove common email prefixes and status markers
        $cleanedSubject = $subject;
        
        // Remove Re:, Fwd:, etc
        $cleanedSubject = preg_replace('/^(Re|RE|Fwd|FW|Fw):\s*/i', '', $cleanedSubject);
        
        // Remove status markers like [Resolved], [In Progress], [Closed], etc
        $cleanedSubject = preg_replace('/^\[.*?\]\s*/i', '', $cleanedSubject);
        
        $cleanedSubject = trim($cleanedSubject);
        
        // If no cleaning happened, this is not a reply/follow-up
        if ($cleanedSubject === $subject) {
            return null;
        }
        
        Log::info("Looking for related ticket", [
            'original_subject' => $subject,
            'cleaned_subject' => $cleanedSubject
        ]);
        
        // Try multiple strategies to find related ticket
        $ticket = null;
        
        // Strategy 1: Exact match on cleaned subject
        $ticket = Ticket::where('subject', $cleanedSubject)
            ->orderBy('created_at', 'desc')
            ->first();
            
        // Strategy 2: Match with status markers removed from both sides
        if (!$ticket) {
            $ticket = Ticket::where(function($query) use ($cleanedSubject) {
                $query->where('subject', 'LIKE', '%' . $cleanedSubject . '%')
                      ->orWhereRaw('REPLACE(REPLACE(subject, "[Resolved] ", ""), "[In Progress] ", "") LIKE ?', ['%' . $cleanedSubject . '%']);
            })
            ->orderBy('created_at', 'desc')
            ->first();
        }
        
        // Strategy 3: Remove common ID patterns and try fuzzy match
        if (!$ticket) {
            // Remove patterns like "ICON#72Wc3A" or "(SID 01000011202)"
            $coreSubject = preg_replace('/\s*\(.*?\)\s*|\s*ICON#\w+\s*/', '', $cleanedSubject);
            $coreSubject = trim($coreSubject);
            
            if (strlen($coreSubject) > 10) { // Only if we have meaningful text left
                $ticket = Ticket::where(function($query) use ($coreSubject) {
                    $query->where('subject', 'LIKE', '%' . $coreSubject . '%')
                          ->orWhereRaw('REPLACE(REPLACE(subject, "[Resolved] ", ""), "[In Progress] ", "") LIKE ?', ['%' . $coreSubject . '%']);
                })
                ->orderBy('created_at', 'desc')
                ->first();
            }
        }
            
        if ($ticket) {
            Log::info("Found related ticket for reply", [
                'cleaned_subject' => $cleanedSubject,
                'ticket_number' => $ticket->ticket_number,
                'ticket_subject' => $ticket->subject
            ]);
        } else {
            Log::info("No related ticket found", [
                'cleaned_subject' => $cleanedSubject
            ]);
        }
        
        return $ticket;
    }

    /**
     * Add comment/update to existing ticket
     */
    protected function addCommentToTicket(Ticket $ticket, array $emailData): void
    {
        try {
            // Get existing email thread or initialize
            $emailThread = $ticket->email_thread ?? array();
            
            // Determine reply type based on sender
            $replyType = 'user_reply';
            if (stripos($emailData['from'], 'it.support') !== false || 
                stripos($emailData['from'], 'itsupport') !== false) {
                $replyType = 'admin_reply';
            }
            
            // Add new email to thread
            $newThread = array(
                'type' => $replyType,
                'timestamp' => $emailData['date']->toIso8601String(),
                'from' => $emailData['from'],
                'from_name' => $emailData['from_name'],
                'to' => $emailData['to'],
                'cc' => $emailData['cc'],
                'subject' => $emailData['subject'],
                'body' => $emailData['body'],
                'sender_name' => $emailData['from_name'],
                'index' => count($emailThread) + 1
            );
            
            $emailThread[] = $newThread;
            
            // Save updated thread
            $ticket->email_thread = $emailThread;
            $ticket->save();
            
            Log::info("Added reply to ticket email_thread", [
                'ticket_number' => $ticket->ticket_number,
                'reply_type' => $replyType,
                'thread_count' => count($emailThread)
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to add reply to ticket: " . $e->getMessage(), [
                'ticket_number' => $ticket->ticket_number,
                'error' => $e->getMessage()
            ]);
        }
    }

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
            $ticketData['input_method'] = 'email';
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
     * Get ALL emails (including read) - untuk test ulang
     */
    protected function getAllEmails($mailbox): array
    {
        $emails = [];

        // Search untuk SEMUA email (tidak pakai UNSEEN)
        $searchCriteria = 'ALL';
        
        // Optional: filter by date
        $daysBack = config('mail.imap.fetch_days_back');
        if ($daysBack) {
            $date = Carbon::now()->subDays($daysBack)->format('d-M-Y');
            $searchCriteria = "SINCE \"{$date}\"";
        }
        
        Log::info("Search criteria (ALL): {$searchCriteria}");
        
        // Search for emails matching criteria
        $emailIds = imap_search($mailbox, $searchCriteria);

        if (!$emailIds) {
            Log::info("⚠ No emails found in mailbox");
            return [];
        }

        Log::info("Found " . count($emailIds) . " total emails from IMAP search");

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
                    'message_id' => $messageId,
                    'from' => $fromEmail,
                    'from_name' => $fromName,
                    'to' => $to,
                    'cc' => $cc,
                    'subject' => $subject,
                    'body' => $body,
                    'date' => $date,
                ];

                // Validasi email sebelum simpan
                if ($this->isValidEmail($emailData)) {
                    $emails[$emailId] = $emailData;
                } else {
                    Log::info('Email skipped (did not pass validation)', [
                        'from' => $fromEmail,
                        'to' => $to,
                        'subject' => $subject,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to parse email', ['email_id' => $emailId, 'error' => $e->getMessage()]);
            }
        }

        return $emails;
    }

    /**
     * Validasi apakah email sesuai kriteria untuk diproses
     */
    protected function isValidEmail(array $emailData): bool
    {
        // 1. Check blacklist sender email (exact match) - PRIORITY!
        if ($this->isBlacklistedSender($emailData['from'])) {
            Log::info("Email filtered: Sender in blacklist ({$emailData['from']})");
            return false;
        }

        // 2. Check blacklist subject keywords - PRIORITY!
        if ($this->hasBlacklistedSubjectKeywords($emailData['subject'])) {
            Log::info("Email filtered: Subject contains blacklisted keyword");
            return false;
        }

        // 3. Filter by sender domain
        if (!$this->isValidSenderDomain($emailData['from'])) {
            Log::info("Email filtered: Invalid sender domain ({$emailData['from']})");
            return false;
        }

        // 4. Filter by recipient (pastikan dikirim ke inbox yang benar)
        if (!$this->isValidRecipient($emailData['to'], $emailData['cc'])) {
            Log::info("Email filtered: Not sent to valid recipient");
            return false;
        }

        // 5. Filter spam/auto-reply
        if ($this->isSpamOrAutoReply($emailData['subject'], $emailData['body'])) {
            Log::info("Email filtered: Detected as spam/auto-reply");
            return false;
        }

        // 6. Filter by subject keywords (optional)
        if (!$this->hasValidSubjectKeywords($emailData['subject'])) {
            Log::info("Email filtered: Subject doesn't match keywords");
            return false;
        }

        // 7. Filter by minimum content length
        if (!$this->hasValidContentLength($emailData['body'])) {
            Log::info("Email filtered: Content too short");
            return false;
        }

        // Semua validasi passed
        return true;
    }

    /**
     * Check apakah sender email valid
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
     * Check apakah sender email ada di blacklist (exact match)
     */
    protected function isBlacklistedSender(string $email): bool
    {
        $blacklistSenders = config('mail.imap.blacklist_sender_emails', []);
        
        // Jika tidak ada blacklist, allow all
        if (empty($blacklistSenders)) {
            return false;
        }

        // Case-insensitive comparison
        $email = strtolower($email);
        $blacklistSenders = array_map('strtolower', $blacklistSenders);

        return in_array($email, $blacklistSenders);
    }

    /**
     * Check apakah subject mengandung blacklisted keywords
     */
    protected function hasBlacklistedSubjectKeywords(string $subject): bool
    {
        $blacklistKeywords = config('mail.imap.blacklist_subject_keywords', []);
        
        // Jika tidak ada blacklist, allow all
        if (empty($blacklistKeywords)) {
            return false;
        }

        $subject = strtolower($subject);

        // Check if any blacklisted keyword in subject
        foreach ($blacklistKeywords as $keyword) {
            if (stripos($subject, strtolower($keyword)) !== false) {
                return true; // Found blacklisted keyword
            }
        }

        return false;
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
        
        // Jika min_content_length = 0, skip validation (allow all)
        if ($minLength == 0) {
            return true;
        }
        
        // Remove whitespace dan HTML tags untuk akurat
        $cleanBody = trim(strip_tags($body));
        
        // Remove multiple spaces/newlines
        $cleanBody = preg_replace('/\s+/', ' ', $cleanBody);
        
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

