<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use App\Models\ScheduleOccurrence;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Generates canonical maintenance due dates ("occurrences") for each machine's active
 * schedule, one calendar month at a time, independently per city (sby / pasuruan).
 *
 * Core principle: machines are distributed evenly across WORKING DAYS ONLY.
 * Sundays and national holidays never get assignments — machines that would
 * have fallen on those days are wrapped to the next available working day in
 * the round-robin cycle, keeping every working day's load as balanced as possible.
 */
class ScheduleOccurrenceGenerator
{
    /**
     * Generate occurrences for the current month and the following month
     * (2-month rolling window), for every city. Safe to run repeatedly —
     * machine/month pairs that already have occurrences are skipped.
     */
    public function generateUpcoming(): void
    {
        $now = Carbon::now();
        $this->generateForMonth($now->year, $now->month);

        $next = $now->copy()->addMonthNoOverflow();
        $this->generateForMonth($next->year, $next->month);
    }

    /**
     * Generate the current + next month's occurrences for a single city only.
     * Used right after an Excel import so newly added machines are scheduled
     * immediately without waiting for the daily cron.
     */
    public function generateUpcomingForCity(string $kota): void
    {
        $now = Carbon::now();
        $this->generateForCityMonth($kota, $now->year, $now->month);

        $next = $now->copy()->addMonthNoOverflow();
        $this->generateForCityMonth($kota, $next->year, $next->month);
    }

    /**
     * Delete ALL occurrences and regenerate from scratch for current + next month.
     * Use after changing the algorithm or when a full re-balance is needed.
     */
    public function regenerateAll(): void
    {
        ScheduleOccurrence::query()->delete();
        $this->generateUpcoming();
    }

    /**
     * Delete future (today onward) occurrences for a single schedule and
     * regenerate its current + next month occurrences. Used when a schedule's
     * interval or active state changes so stale assignments don't linger.
     */
    public function regenerateForSchedule(MaintenanceSchedule $schedule): void
    {
        $machine = $schedule->machine;
        if (!$machine) {
            return;
        }

        // Delete all occurrences for this machine's city for current + next month
        // so the round-robin can rebalance properly.
        $now = Carbon::now();
        $this->deleteCityMonth($machine->kota, $now->year, $now->month);
        $next = $now->copy()->addMonthNoOverflow();
        $this->deleteCityMonth($machine->kota, $next->year, $next->month);

        $this->generateUpcomingForCity($machine->kota);
    }

    protected function deleteCityMonth(string $kota, int $year, int $month): void
    {
        ScheduleOccurrence::whereHas('machine', fn ($q) => $q->where('kota', $kota))
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->delete();
    }

    protected function generateForMonth(int $year, int $month): void
    {
        foreach (['sby', 'pasuruan'] as $kota) {
            $this->generateForCityMonth($kota, $year, $month);
        }
    }

    protected function generateForCityMonth(string $kota, int $year, int $month): void
    {
        $schedules = MaintenanceSchedule::with('machine')
            ->where('is_active', true)
            ->whereHas('machine', fn ($q) => $q->where('kota', $kota))
            ->get()
            ->filter(fn ($s) => $s->machine !== null)
            ->sortBy(fn ($s) => $s->machine->import_order ?? PHP_INT_MAX)
            ->values();

        if ($schedules->isEmpty()) {
            return;
        }

        // Skip schedules that already have occurrences generated for this month.
        $pending = $schedules->filter(function ($s) use ($year, $month) {
            return !ScheduleOccurrence::where('schedule_id', $s->id)
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->exists();
        })->values();

        if ($pending->isEmpty()) {
            return;
        }

        $holidays = $this->getHolidaySet($year);

        // Build the list of all working days in this month (Mon-Sat, no holidays).
        $workingDays = [];
        $date = Carbon::create($year, $month, 1);
        $daysInMonth = $date->daysInMonth;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $day = Carbon::create($year, $month, $d);
            if (!$day->isSunday() && !isset($holidays[$day->toDateString()])) {
                $workingDays[] = $day;
            }
        }

        if (empty($workingDays)) {
            return;
        }

        // Split working days into first half (weeks 1-2) and second half (weeks 3-4+)
        // for 2x/month schedules.  The split point is the middle working day.
        $midPoint = intdiv(count($workingDays), 2);

        $fallbackOrder = 0;
        foreach ($pending as $schedule) {
            $order = $schedule->machine->import_order ?? (100000 + ++$fallbackOrder);
            $interval = (int) $schedule->interval_days;
            $dueDates = $this->idealDatesForSchedule(
                $order, $interval, $workingDays, $midPoint
            );

            foreach ($dueDates as $date) {
                ScheduleOccurrence::create([
                    'schedule_id' => $schedule->id,
                    'machine_id'  => $schedule->machine_id,
                    'period_year' => $year,
                    'period_month' => $month,
                    'due_date'    => $date->toDateString(),
                    'original_date' => $date->toDateString(),
                    'is_shifted'  => false,
                ]);
            }
        }

        // Keep the schedule's next_due_date in sync with the earliest occurrence.
        foreach ($pending as $schedule) {
            $earliest = ScheduleOccurrence::where('schedule_id', $schedule->id)
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->orderBy('due_date')
                ->first();

            if ($earliest) {
                MaintenanceSchedule::where('id', $schedule->id)->update([
                    'next_due_date' => $earliest->due_date,
                ]);
            }
        }
    }

    /**
     * Assign due date(s) for a schedule based on import order, using only working days.
     *
     * @param Carbon[] $workingDays  all working days in the month (sorted ascending)
     * @param int      $midPoint     index splitting first half / second half
     * @return Carbon[]
     */
    protected function idealDatesForSchedule(int $order, int $interval, array $workingDays, int $midPoint): array
    {
        $count = count($workingDays);

        // 1x/month (interval >= 21): round-robin across all working days.
        // Row 1 -> working day 1, row 2 -> working day 2, ..., wraps around.
        if ($interval >= 21) {
            $idx = ($order - 1) % $count;
            return [$workingDays[$idx]];
        }

        // 2x/month (interval 10-20): one date in the first half, one in the second half.
        // Odd row order -> first half slot + second half slot (consistent week-pair).
        // Even row order -> offset by 1 in each half so machines don't all land on the same day.
        if ($interval >= 10) {
            $firstHalf = array_slice($workingDays, 0, $midPoint);
            $secondHalf = array_slice($workingDays, $midPoint);

            if (empty($firstHalf)) $firstHalf = [$workingDays[0]];
            if (empty($secondHalf)) $secondHalf = [end($workingDays)];

            $idx1 = ($order - 1) % count($firstHalf);
            $idx2 = ($order - 1) % count($secondHalf);

            return [$firstHalf[$idx1], $secondHalf[$idx2]];
        }

        // <10 days (e.g. weekly): 4 occurrences spread evenly across the working days.
        $quarter = max(1, intdiv($count, 4));
        $dates = [];
        for ($i = 0; $i < 4; $i++) {
            $idx = min(($i * $quarter + ($order - 1)) % $count, $count - 1);
            $dates[] = $workingDays[$idx];
        }
        return $dates;
    }

    /**
     * @return array<string, bool> map of 'YYYY-MM-DD' => true for national holidays
     */
    protected function getHolidaySet(int $year): array
    {
        $cacheKey = "holidays_{$year}";
        $list = Cache::remember($cacheKey, now()->addHours(24), function () use ($year) {
            try {
                $response = Http::timeout(10)->get("https://api-hari-libur.vercel.app/api?year={$year}");
                if ($response->successful()) {
                    return $response->json('data', []);
                }
            } catch (\Exception $e) {
                // Ignore — fall back to no known holidays if the API is unreachable.
            }
            return [];
        });

        $map = [];
        foreach ((array) $list as $holiday) {
            $date = is_array($holiday) ? ($holiday['date'] ?? null) : ($holiday->date ?? null);
            if ($date) {
                $map[$date] = true;
            }
        }
        return $map;
    }
}
