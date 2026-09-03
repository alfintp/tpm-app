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
        // Schedule generation is now triggered on-demand when users access the
        // Dashboard (via /api/schedules/notifications). The
        // ScheduleOccurrenceGenerator::ensureGenerated() method uses a 6-hour
        // cache flag to avoid redundant checks.
        // The artisan command is still available manually if needed:
        //   php artisan schedules:generate-occurrences
        // $schedule->command('schedules:generate-occurrences')->monthlyOn(1, '00:00');
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
