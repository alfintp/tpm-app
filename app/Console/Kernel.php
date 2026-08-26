<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Idempotent — only generates months that don't have occurrences yet, so this
        // safely keeps the 6-month rolling schedule window always populated.
        // Runs monthly on the 1st since the 6-month window provides ample buffer.
        // New machines get scheduled immediately via generateUpcomingForCity() on creation.
        $schedule->command('schedules:generate-occurrences')->monthlyOn(1, '00:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
