<?php

namespace App\Jobs;

use App\Services\EmailFetcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(EmailFetcherService $emailFetcher)
    {
        Log::info('Starting scheduled email fetch job...');

        try {
            $results = $emailFetcher->fetchAndProcessEmails();

            Log::info('Email fetch job completed', [
                'success' => $results['success'],
                'failed' => $results['failed'],
                'skipped' => $results['skipped'],
            ]);

            if (!empty($results['errors'])) {
                Log::warning('Email fetch job had errors', [
                    'errors' => $results['errors'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Email fetch job failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Rethrow untuk retry mechanism
        }
    }
}
