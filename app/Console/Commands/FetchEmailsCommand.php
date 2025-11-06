<?php

namespace App\Console\Commands;

use App\Services\EmailFetcherService;
use Illuminate\Console\Command;

class FetchEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:fetch {--limit=50 : Maximum emails to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch emails from IMAP mailbox and create tickets automatically';

    protected $emailFetcher;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(EmailFetcherService $emailFetcher)
    {
        parent::__construct();
        $this->emailFetcher = $emailFetcher;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 Starting email fetch process...');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            // Fetch and process emails
            $results = $this->emailFetcher->fetchAndProcessEmails();

            // Display results
            $this->info("\n📊 Fetch Results:");
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            
            $this->info("✅ Success: {$results['success']} tickets created");
            
            if ($results['skipped'] > 0) {
                $this->warn("⏭️  Skipped: {$results['skipped']} emails (already processed)");
            }
            
            if ($results['failed'] > 0) {
                $this->error("❌ Failed: {$results['failed']} emails");
            }

            // Show errors if any
            if (!empty($results['errors'])) {
                $this->error("\n⚠️  Errors encountered:");
                foreach ($results['errors'] as $error) {
                    $this->error("  • {$error}");
                }
            }

            $this->info("\n✨ Email fetch process completed!");
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("\n❌ Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            
            return Command::FAILURE;
        }
    }
}
