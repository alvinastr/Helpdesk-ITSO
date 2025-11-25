<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DebugEmailFetchCommand extends Command
{
    protected $signature = 'emails:debug';
    protected $description = 'Debug email fetch process with detailed output';

    public function handle()
    {
        $this->info('🔍 Email Fetch Debugging');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Check configuration
        $this->info("\n1. Configuration Check:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        $host = config('mail.imap.host');
        $port = config('mail.imap.port');
        $username = config('mail.imap.username');
        
        $this->table(['Setting', 'Value'], [
            ['IMAP_HOST', $host ?: '(not set)'],
            ['IMAP_PORT', $port],
            ['IMAP_USERNAME', $username ?: '(not set)'],
            ['IMAP_ENCRYPTION', config('mail.imap.encryption', 'ssl')],
            ['IMAP_VALIDATE_CERT', config('mail.imap.validate_cert') ? 'true' : 'false'],
            ['IMAP_FETCH_LIMIT', config('mail.imap.fetch_limit', 50)],
        ]);

        // Check IMAP connection
        $this->info("\n2. Testing IMAP Connection:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        try {
            $mailbox = $this->connectToMailbox();
            
            if (!$mailbox) {
                $this->error("❌ Failed to connect to mailbox");
                return Command::FAILURE;
            }
            
            $this->info("✅ Connected successfully");

            // Get mailbox status
            $this->info("\n3. Mailbox Status:");
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            
            $check = imap_check($mailbox);
            $this->line("Total messages: " . $check->Nmsgs);
            $this->line("Recent messages: " . $check->Recent);
            
            // Search for unread emails
            $unreadEmails = imap_search($mailbox, 'UNSEEN');
            $unreadCount = $unreadEmails ? count($unreadEmails) : 0;
            $this->line("Unread messages: " . $unreadCount);

            if ($unreadCount === 0) {
                $this->warn("\n⚠️  No unread emails found!");
                $this->line("Possible reasons:");
                $this->line("  1. All emails already marked as read");
                $this->line("  2. No new emails in inbox");
                $this->line("  3. Emails already processed before");
                
                // Check all emails (including read ones)
                $allEmails = imap_search($mailbox, 'ALL');
                $allCount = $allEmails ? count($allEmails) : 0;
                $this->line("\nTotal emails in mailbox: " . $allCount);
                
                if ($allCount > 0) {
                    $this->info("\n💡 Showing last 3 emails (including read ones):");
                    $recentIds = array_slice($allEmails, -3);
                    
                    foreach ($recentIds as $emailId) {
                        $header = imap_headerinfo($mailbox, $emailId);
                        $flags = imap_fetch_overview($mailbox, $emailId)[0];
                        
                        $from = $header->from[0];
                        $fromEmail = $from->mailbox . '@' . $from->host;
                        $subject = isset($header->subject) ? $this->decodeEmailText($header->subject) : '(No Subject)';
                        $seen = $flags->seen ? '✅ Read' : '📧 Unread';
                        
                        $this->line("\n  Email ID: {$emailId} [{$seen}]");
                        $this->line("  From: {$fromEmail}");
                        $this->line("  Subject: " . substr($subject, 0, 60) . (strlen($subject) > 60 ? '...' : ''));
                        $this->line("  Date: {$header->date}");
                    }
                }
                
                imap_close($mailbox);
                return Command::SUCCESS;
            }

            // Process unread emails with detailed output
            $this->info("\n4. Processing Unread Emails:");
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            
            $limit = config('mail.imap.fetch_limit', 50);
            $emailsToProcess = array_slice($unreadEmails, 0, min($limit, 5)); // Max 5 for debugging
            
            $this->line("Processing " . count($emailsToProcess) . " emails...\n");

            foreach ($emailsToProcess as $index => $emailId) {
                $num = $index + 1;
                $this->line("────────────────────────────────────────────────────────────────");
                $this->info("📧 Email #{$num} (ID: {$emailId})");
                
                try {
                    $header = imap_headerinfo($mailbox, $emailId);
                    $structure = imap_fetchstructure($mailbox, $emailId);
                    $body = $this->getEmailBody($mailbox, $emailId, $structure);

                    // Extract email data
                    $from = $header->from[0];
                    $fromEmail = $from->mailbox . '@' . $from->host;
                    $fromName = isset($from->personal) ? $this->decodeEmailText($from->personal) : $fromEmail;
                    $subject = isset($header->subject) ? $this->decodeEmailText($header->subject) : '(No Subject)';
                    $messageId = isset($header->message_id) ? $header->message_id : "email-{$emailId}-" . time();

                    // Get TO and CC
                    $to = isset($header->to[0]) ? $header->to[0]->mailbox . '@' . $header->to[0]->host : '';
                    $cc = '';
                    if (isset($header->cc)) {
                        $ccAddresses = array_map(function($c) {
                            return $c->mailbox . '@' . $c->host;
                        }, $header->cc);
                        $cc = implode(', ', $ccAddresses);
                    }

                    $this->line("From: {$fromName} <{$fromEmail}>");
                    $this->line("To: {$to}");
                    if (!empty($cc)) {
                        $this->line("Cc: {$cc}");
                    }
                    $this->line("Subject: {$subject}");
                    $this->line("Message ID: {$messageId}");
                    $this->line("Body length: " . strlen($body) . " chars");

                    // Check filters
                    $emailData = [
                        'from' => $fromEmail,
                        'to' => $to,
                        'cc' => $cc,
                        'subject' => $subject,
                        'body' => $body,
                        'message_id' => $messageId,
                    ];

                    // Check if already processed
                    $alreadyProcessed = \App\Models\Ticket::where('email_message_id', $messageId)->exists();
                    if ($alreadyProcessed) {
                        $this->warn("  ⏭️  SKIPPED: Already processed (found in database)");
                        continue;
                    }

                    // Check filters
                    $filterResults = $this->checkFilters($emailData);
                    
                    if (!$filterResults['passed']) {
                        $this->warn("  ❌ FILTERED OUT: " . $filterResults['reason']);
                    } else {
                        $this->info("  ✅ PASSED all filters - should create ticket");
                    }

                } catch (\Exception $e) {
                    $this->error("  ❌ Error: " . $e->getMessage());
                }
            }

            imap_close($mailbox);

            $this->info("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("✨ Debug completed!");
            
            $this->warn("\n💡 Next Steps:");
            $this->line("  1. If emails are filtered, adjust .env settings");
            $this->line("  2. If emails passed filters but no ticket created, check logs:");
            $this->line("     tail -f storage/logs/laravel.log");
            $this->line("  3. Try actual fetch: php artisan emails:fetch");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function connectToMailbox()
    {
        $host = config('mail.imap.host');
        $port = config('mail.imap.port', 993);
        $username = config('mail.imap.username');
        $password = config('mail.imap.password');
        $validateCert = config('mail.imap.validate_cert', true);

        $certValidation = $validateCert ? '/validate-cert' : '/novalidate-cert';
        $connectionString = "{{$host}:{$port}/imap/ssl{$certValidation}}INBOX";

        $mailbox = @imap_open($connectionString, $username, $password);

        return $mailbox;
    }

    protected function getEmailBody($mailbox, $emailId, $structure): string
    {
        $body = '';

        if (isset($structure->parts) && count($structure->parts)) {
            foreach ($structure->parts as $partNum => $part) {
                if ($part->subtype == 'PLAIN' || $part->subtype == 'HTML') {
                    $partBody = imap_fetchbody($mailbox, $emailId, $partNum + 1);
                    
                    if ($part->encoding == 3) {
                        $partBody = base64_decode($partBody);
                    } elseif ($part->encoding == 4) {
                        $partBody = quoted_printable_decode($partBody);
                    }
                    
                    $body .= $partBody;
                    
                    if ($part->subtype == 'PLAIN') {
                        break;
                    }
                }
            }
        } else {
            $body = imap_body($mailbox, $emailId);
            
            if ($structure->encoding == 3) {
                $body = base64_decode($body);
            } elseif ($structure->encoding == 4) {
                $body = quoted_printable_decode($body);
            }
        }

        return trim($body);
    }

    protected function decodeEmailText($text): string
    {
        $elements = imap_mime_header_decode($text);
        $decoded = '';
        
        foreach ($elements as $element) {
            $decoded .= $element->text;
        }
        
        return $decoded;
    }

    protected function checkFilters($emailData): array
    {
        // Check blacklist sender (PRIORITY)
        $blacklistSenders = config('mail.imap.blacklist_sender_emails', []);
        if (!empty($blacklistSenders)) {
            $email = strtolower($emailData['from']);
            $blacklistSenders = array_map('strtolower', $blacklistSenders);
            if (in_array($email, $blacklistSenders)) {
                return ['passed' => false, 'reason' => "Sender '{$emailData['from']}' is blacklisted"];
            }
        }

        // Check blacklist subject (PRIORITY)
        $blacklistKeywords = config('mail.imap.blacklist_subject_keywords', []);
        if (!empty($blacklistKeywords)) {
            $subject = strtolower($emailData['subject']);
            foreach ($blacklistKeywords as $keyword) {
                if (stripos($subject, strtolower($keyword)) !== false) {
                    return ['passed' => false, 'reason' => "Subject contains blacklisted keyword: '{$keyword}'"];
                }
            }
        }

        // Check sender domain
        $allowedDomains = config('mail.imap.allowed_sender_domains', []);
        if (!empty($allowedDomains)) {
            $domain = substr(strrchr($emailData['from'], '@'), 1);
            if (!in_array($domain, $allowedDomains)) {
                return ['passed' => false, 'reason' => "Sender domain '{$domain}' not in allowed list"];
            }
        }

        // Check valid recipients
        $validRecipients = config('mail.imap.valid_recipients', []);
        if (!empty($validRecipients)) {
            $to = isset($emailData['to']) ? strtolower($emailData['to']) : '';
            $cc = isset($emailData['cc']) ? strtolower($emailData['cc']) : '';
            $allRecipients = $to . ',' . $cc;
            
            $found = false;
            foreach ($validRecipients as $validRecipient) {
                if (stripos($allRecipients, strtolower($validRecipient)) !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return ['passed' => false, 'reason' => "Not sent to valid recipients"];
            }
        }

        // Check spam/auto-reply
        $spamKeywords = ['out of office', 'automatic reply', 'auto-reply', 'noreply', 'no-reply', 'mailer-daemon'];
        $content = strtolower($emailData['subject'] . ' ' . $emailData['body']);
        
        foreach ($spamKeywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                return ['passed' => false, 'reason' => "Detected as spam/auto-reply (keyword: {$keyword})"];
            }
        }

        // Check content length
        $minLength = config('mail.imap.min_content_length', 10);
        $cleanBody = trim($emailData['body']);
        
        if (strlen($cleanBody) < $minLength) {
            return ['passed' => false, 'reason' => "Content too short (" . strlen($cleanBody) . " chars, min: {$minLength})"];
        }

        // Check subject keywords (if configured)
        $requiredKeywords = config('mail.imap.required_subject_keywords', []);
        if (!empty($requiredKeywords)) {
            $found = false;
            foreach ($requiredKeywords as $keyword) {
                if (stripos($emailData['subject'], $keyword) !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return ['passed' => false, 'reason' => "Subject doesn't contain required keywords"];
            }
        }

        return ['passed' => true, 'reason' => ''];
    }
}
