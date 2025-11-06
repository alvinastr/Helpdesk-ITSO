<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Fetch emails from IMAP every 5 minutes
        $schedule->command('emails:fetch')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Run SLA check every hour
        $schedule->command('tickets:sla-check')->hourly();

        // Generate daily report at 8 AM
        $schedule->command('report:daily')->dailyAt('08:00');

        // Cleanup old tickets every month
        $schedule->command('tickets:cleanup --days=365')->monthly();

        // Other scheduled tasks...
        $schedule->command('queue:work --stop-when-empty')
                 ->everyMinute()
                 ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}