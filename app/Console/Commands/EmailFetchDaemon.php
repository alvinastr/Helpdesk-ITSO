<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailFetcherService;

class EmailFetchDaemon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:fetch-daemon
                            {--interval=300 : Interval waktu antar fetch dalam detik (default: 300 = 5 menit)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run email fetcher as daemon process (terus berjalan di background)';

    protected $emailFetcher;

    public function __construct(EmailFetcherService $emailFetcher)
    {
        parent::__construct();
        $this->emailFetcher = $emailFetcher;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = (int) $this->option('interval');
        
        $this->info("🚀 Email Fetch Daemon started!");
        $this->info("⏱️  Checking for new emails every {$interval} seconds ({$this->formatInterval($interval)})");
        $this->info("🛑 Press Ctrl+C to stop\n");

        $fetchCount = 0;

        while (true) {
            $fetchCount++;
            $timestamp = now()->format('Y-m-d H:i:s');
            
            $this->line("─────────────────────────────────────────────");
            $this->info("📬 Fetch #{$fetchCount} - {$timestamp}");
            
            try {
                // Fetch emails
                $results = $this->emailFetcher->fetchAndProcessEmails();
                
                // Display results
                if ($results['success'] > 0) {
                    $this->info("✅ Success: {$results['success']} tickets created");
                } else {
                    $this->comment("📭 No new emails to process");
                }
                
                if ($results['skipped'] > 0) {
                    $this->comment("⏭️  Skipped: {$results['skipped']} (already processed)");
                }
                
                if ($results['failed'] > 0) {
                    $this->warn("❌ Failed: {$results['failed']}");
                    foreach ($results['errors'] as $error) {
                        $this->error("   → {$error}");
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("💥 Error: " . $e->getMessage());
                $this->error("Stack trace logged to storage/logs/laravel.log");
            }
            
            $nextRun = now()->addSeconds($interval)->format('H:i:s');
            $this->comment("⏳ Next check at {$nextRun}");
            
            // Sleep until next interval
            sleep($interval);
        }
    }

    /**
     * Format interval menjadi human readable
     */
    protected function formatInterval($seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} detik";
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($remainingSeconds > 0) {
            return "{$minutes} menit {$remainingSeconds} detik";
        }
        
        return "{$minutes} menit";
    }
}
