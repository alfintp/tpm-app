<?php

namespace App\Console\Commands;

use App\Services\ScheduleOccurrenceGenerator;
use Illuminate\Console\Command;

class GenerateScheduleOccurrences extends Command
{
    protected $signature = 'schedules:generate-occurrences {--fresh : Delete all existing occurrences and regenerate from scratch}';

    protected $description = 'Generate canonical maintenance schedule occurrences (due dates) for the current and next 5 months (6-month window), per city.';

    public function handle(ScheduleOccurrenceGenerator $generator)
    {
        if ($this->option('fresh')) {
            $generator->regenerateAll();
            $this->info('All schedule occurrences deleted and regenerated for the 6-month window.');
        } else {
            $generator->generateUpcoming();
            $this->info('Schedule occurrences generated for the 6-month window (current + next 5 months).');
        }
        return self::SUCCESS;
    }
}
