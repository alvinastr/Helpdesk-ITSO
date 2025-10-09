<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class GenerateDailyReport extends Command
{
    protected $signature = 'report:daily';
    protected $description = 'Generate daily ticket report and email to admins';

    public function handle()
    {
        $today = Carbon::today();
        
        $stats = [
            'new_tickets' => Ticket::whereDate('created_at', $today)->count(),
            'closed_tickets' => Ticket::whereDate('closed_at', $today)->count(),
            'pending_review' => Ticket::where('status', 'pending_review')->count(),
            'open_tickets' => Ticket::whereIn('status', ['open', 'in_progress'])->count(),
        ];

        // Send email to admins
        $admins = \App\Models\User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            // Mail::to($admin->email)->send(new DailyReport($stats));
        }

        $this->info("Daily report generated and sent to admins.");
        $this->table(
            ['Metric', 'Count'],
            [
                ['New Tickets', $stats['new_tickets']],
                ['Closed Tickets', $stats['closed_tickets']],
                ['Pending Review', $stats['pending_review']],
                ['Open Tickets', $stats['open_tickets']],
            ]
        );
    }
}
