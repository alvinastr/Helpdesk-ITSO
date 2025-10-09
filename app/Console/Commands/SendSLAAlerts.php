<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendSLAAlerts extends Command
{
    protected $signature = 'tickets:sla-check';
    protected $description = 'Check tickets approaching SLA deadline and send alerts';

    public function handle()
    {
        // Check tickets open more than 24 hours
        $tickets = Ticket::whereIn('status', ['open', 'in_progress'])
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->get();

        foreach ($tickets as $ticket) {
            $hours = $ticket->created_at->diffInHours(now());
            
            if ($hours > 48) {
                // Critical: Send alert to admin
                $this->warn("CRITICAL: Ticket {$ticket->ticket_number} open for {$hours} hours!");
                // Send notification to admin
            } elseif ($hours > 36) {
                // Warning: Approaching SLA
                $this->info("WARNING: Ticket {$ticket->ticket_number} open for {$hours} hours");
            }
        }

        $this->info("SLA check completed. Found {$tickets->count()} tickets to monitor.");
    }
}