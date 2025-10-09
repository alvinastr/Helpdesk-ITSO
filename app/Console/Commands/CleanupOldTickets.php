<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupOldTickets extends Command
{
    protected $signature = 'tickets:cleanup {--days=365 : Days to keep}';
    protected $description = 'Archive or delete old closed tickets';

    public function handle()
    {
        $days = $this->option('days');
        $date = Carbon::now()->subDays($days);

        $count = Ticket::where('status', 'closed')
            ->where('closed_at', '<', $date)
            ->delete();

        $this->info("Cleaned up {$count} old tickets.");
    }
}
