<?php

namespace App\Console\Commands;

use App\Services\MockEmailFetcherService;
use Illuminate\Console\Command;

class TestMockEmailFetchCommand extends Command
{
    protected $signature = 'test:mock-fetch';
    protected $description = 'Test email auto-fetch menggunakan mock data (tanpa IMAP)';

    public function handle()
    {
        $this->info('╔════════════════════════════════════════════════════════╗');
        $this->info('║   Mock Email Auto-Fetch Testing                        ║');
        $this->info('║   Testing tanpa akses IMAP - Menggunakan Sample Data  ║');
        $this->info('╚════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        // Warning
        $this->warn('⚠️  PERHATIAN: Ini adalah MODE TESTING');
        $this->warn('    Data yang dibuat adalah SAMPLE data untuk testing');
        $this->warn('    Tidak menggunakan koneksi IMAP ke email server asli');
        $this->newLine();
        
        if (!$this->confirm('Lanjutkan dengan mock testing?', true)) {
            $this->info('Testing dibatalkan.');
            return 0;
        }
        
        $this->newLine();
        $this->info('🎭 Memulai mock email fetch...');
        $this->newLine();
        
        $bar = $this->output->createProgressBar(4);
        $bar->start();
        
        try {
            // Initialize mock service
            $mockService = app(MockEmailFetcherService::class);
            $bar->advance();
            
            // Fetch and process mock emails
            $results = $mockService->fetchAndProcessEmails();
            $bar->advance();
            
            // Display results
            $bar->finish();
            $this->newLine(2);
            
            $this->displayResults($results);
            
            if ($results['total_created'] > 0) {
                $this->newLine();
                $this->info('✅ Mock testing berhasil!');
                $this->info('💡 Silakan cek di dashboard untuk melihat ticket yang dibuat');
                $this->newLine();
                
                // Show created tickets
                $this->info('📋 Tickets yang dibuat:');
                $this->table(
                    ['Ticket Number', 'Subject', 'Thread Count'],
                    array_map(function($ticket) {
                        return [
                            $ticket['ticket_number'],
                            $ticket['subject'],
                            $ticket['thread_count'],
                        ];
                    }, $results['tickets'])
                );
            }
            
            // Show errors if any
            if (!empty($results['errors'])) {
                $this->newLine();
                $this->error('❌ Errors encountered:');
                foreach ($results['errors'] as $error) {
                    $this->line("   • $error");
                }
            }
            
        } catch (\Exception $e) {
            $this->newLine(2);
            $this->error('❌ Error during mock fetch:');
            $this->error($e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
    
    protected function displayResults(array $results): void
    {
        $this->info('╔════════════════════════════════════════════════════════╗');
        $this->info('║               MOCK FETCH RESULTS                       ║');
        $this->info('╚════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['📧 Total Emails Fetched', $results['total_fetched']],
                ['✅ Emails Processed', $results['total_processed']],
                ['🎫 Tickets Created', $results['total_created']],
                ['🚫 Emails Filtered', $results['total_filtered']],
                ['❌ Errors', count($results['errors'])],
            ]
        );
    }
}
