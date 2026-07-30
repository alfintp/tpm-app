<?php

namespace App\Console\Commands;

use App\Services\ScheduleOccurrenceGenerator;
use Illuminate\Console\Command;

class GenerateScheduleOccurrences extends Command
{
    protected $signature = 'schedules:generate-occurrences';

    protected $description = 'Generate canonical maintenance schedule occurrences (due dates) for the current and next month, per city.';

    public function handle(ScheduleOccurrenceGenerator $generator)
    {
        $generator->generateUpcoming();
        $this->info('Schedule occurrences generated for the current and next month.');
        return self::SUCCESS;
    }
}
