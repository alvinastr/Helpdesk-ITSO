<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestImapConnectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imap:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test IMAP connection and display mailbox info';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔧 Testing IMAP Connection...');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Check if IMAP extension is loaded
        if (!extension_loaded('imap')) {
            $this->error('❌ PHP IMAP extension is not loaded!');
            $this->error('   Install it first:');
            $this->error('   - macOS: brew install php@8.4-imap');
            $this->error('   - Ubuntu: sudo apt-get install php-imap');
            return Command::FAILURE;
        }

        $this->info('✅ PHP IMAP extension is loaded');

        // Get config
        $host = config('mail.imap.host');
        $port = config('mail.imap.port');
        $username = config('mail.imap.username');
        $password = config('mail.imap.password');
        $encryption = config('mail.imap.encryption');
        $validateCert = config('mail.imap.validate_cert');

        // Display config
        $this->info("\n📋 Configuration:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("Host: {$host}");
        $this->line("Port: {$port}");
        $this->line("Username: {$username}");
        $this->line("Password: " . (empty($password) ? '(not set)' : str_repeat('*', 16)));
        $this->line("Encryption: {$encryption}");
        $this->line("Validate Cert: " . ($validateCert ? 'Yes' : 'No'));

        // Check if credentials are set
        if (empty($host) || empty($username) || empty($password)) {
            $this->error("\n❌ IMAP configuration is incomplete!");
            $this->error("   Please set IMAP_* variables in .env file");
            return Command::FAILURE;
        }

        // Build connection string
        $certValidation = $validateCert ? '/validate-cert' : '/novalidate-cert';
        $connectionString = "{{$host}:{$port}/imap/{$encryption}{$certValidation}}INBOX";

        $this->info("\n🔌 Connecting to mailbox...");
        $this->line("Connection string: {$connectionString}");

        try {
            // Attempt connection
            $mailbox = @imap_open($connectionString, $username, $password);

            if (!$mailbox) {
                $error = imap_last_error();
                throw new \Exception($error ?: 'Unknown connection error');
            }

            $this->info("\n✅ Successfully connected to mailbox!");

            // Get mailbox info
            $info = imap_status($mailbox, $connectionString, SA_ALL);
            
            $this->info("\n📊 Mailbox Statistics:");
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->line("Total messages: {$info->messages}");
            $this->line("Recent messages: {$info->recent}");
            $this->line("Unread messages: {$info->unseen}");

            // Get a few recent emails (for preview)
            $unreadEmails = imap_search($mailbox, 'UNSEEN');
            
            if ($unreadEmails) {
                $count = count($unreadEmails);
                $this->info("\n📧 Found {$count} unread email(s)");
                
                if ($this->confirm('Do you want to see details of recent unread emails?', true)) {
                    $previewCount = min(5, $count);
                    $this->info("\nShowing {$previewCount} most recent unread email(s):");
                    $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                    
                    foreach (array_slice($unreadEmails, 0, $previewCount) as $emailId) {
                        $header = imap_headerinfo($mailbox, $emailId);
                        
                        $from = $header->from[0];
                        $fromEmail = $from->mailbox . '@' . $from->host;
                        $fromName = isset($from->personal) ? imap_utf8($from->personal) : $fromEmail;
                        
                        $subject = isset($header->subject) ? imap_utf8($header->subject) : '(No Subject)';
                        $date = $header->date ?? 'Unknown date';
                        
                        $this->line("\n📨 Email #{$emailId}");
                        $this->line("   From: {$fromName} <{$fromEmail}>");
                        $this->line("   Subject: {$subject}");
                        $this->line("   Date: {$date}");
                    }
                }
            } else {
                $this->warn("\n⚠️  No unread emails found");
                $this->line("   Send a test email to {$username} and run this command again");
            }

            // Close connection
            imap_close($mailbox);

            $this->info("\n✨ Connection test completed successfully!");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("\n💡 Next steps:");
            $this->line("   1. Run: php artisan emails:fetch");
            $this->line("   2. Setup cron job for automatic fetching");
            $this->line("   3. Check documentation: DOC/EMAIL_AUTO_FETCH_GUIDE.md");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("\n❌ Connection failed!");
            $this->error("Error: " . $e->getMessage());
            
            $this->warn("\n💡 Common issues:");
            $this->line("   1. Wrong credentials (check IMAP_USERNAME and IMAP_PASSWORD)");
            $this->line("   2. IMAP not enabled on email server");
            $this->line("   3. Firewall blocking port {$port}");
            $this->line("   4. Gmail: Need to use App Password (not regular password)");
            $this->line("   5. Certificate validation issue (try IMAP_VALIDATE_CERT=false)");

            return Command::FAILURE;
        }
    }
}
