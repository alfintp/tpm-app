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
 * Rules implemented:
 * - Each machine's position in this month's assignment cycle comes from its Excel
 *   import row order (machines.import_order), reset separately per city.
 * - 1x/month schedules (interval_days >= 21): day-of-month cycles with row order
 *   (row 1 -> day 1, row 2 -> day 2, ... wraps back to day 1 after the last day
 *   of the month).
 * - 2x/month schedules (interval_days between 10 and 20, e.g. 14): always land on
 *   the same week-pair every month — odd row order -> weeks 1 & 3, even row order
 *   -> weeks 2 & 4 — so the cadence never drifts.
 * - <10 day schedules (e.g. weekly): occur once every week (weeks 1-4) on a fixed
 *   weekday derived from row order.
 * - If an ideal date falls on a Sunday/national holiday, or a day is already at
 *   capacity, the occurrence is pushed forward to the next available working day
 *   so no single day (e.g. the day right after a holiday) gets overloaded —
 *   pushing the date later only ever gives a machine MORE time before it's
 *   considered late, never less.
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

        ScheduleOccurrence::where('schedule_id', $schedule->id)
            ->whereDate('due_date', '>=', Carbon::today())
            ->delete();

        $this->generateUpcomingForCity($machine->kota);
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

        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $holidays = $this->getHolidaySet($year);

        $isWorkingDay = function (Carbon $date) use ($holidays) {
            return !$date->isSunday() && !isset($holidays[$date->toDateString()]);
        };

        // Build each pending schedule's ideal (unshifted) due date(s) for the month.
        $ideal = [];
        $fallbackOrder = 0;
        foreach ($pending as $schedule) {
            $order = $schedule->machine->import_order ?? (100000 + ++$fallbackOrder);
            $interval = (int) $schedule->interval_days;

            foreach ($this->idealDatesForSchedule($order, $interval, $year, $month, $daysInMonth) as $date) {
                $ideal[] = ['schedule' => $schedule, 'date' => $date];
            }
        }

        // Process chronologically so earlier-in-month occurrences get first pick of days.
        usort($ideal, fn ($a, $b) => $a['date']->timestamp <=> $b['date']->timestamp);

        $workingDaysCount = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            if ($isWorkingDay(Carbon::create($year, $month, $d))) {
                $workingDaysCount++;
            }
        }
        $workingDaysCount = max(1, $workingDaysCount);
        // Small buffer above the perfectly even average so a couple of days can
        // absorb overflow without every holiday clustering onto a single day.
        $threshold = (int) ceil(count($ideal) / $workingDaysCount) + 1;

        $dayCounts = [];
        foreach ($ideal as $item) {
            $date = $item['date']->copy();
            $original = $date->copy();

            $safety = 0;
            while ($safety++ < 45) {
                $key = $date->toDateString();
                $withinCapacity = ($dayCounts[$key] ?? 0) < $threshold;
                if ($isWorkingDay($date) && $withinCapacity) {
                    break;
                }
                $date->addDay();
            }

            $key = $date->toDateString();
            $dayCounts[$key] = ($dayCounts[$key] ?? 0) + 1;

            ScheduleOccurrence::create([
                'schedule_id' => $item['schedule']->id,
                'machine_id' => $item['schedule']->machine_id,
                'period_year' => $year,
                'period_month' => $month,
                'due_date' => $date->toDateString(),
                'original_date' => $original->toDateString(),
                'is_shifted' => !$date->isSameDay($original),
            ]);
        }

        // Keep the schedule's next_due_date roughly in sync (earliest occurrence
        // generated this run) for any legacy code path that still reads it directly.
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
     * @return Carbon[]
     */
    protected function idealDatesForSchedule(int $order, int $interval, int $year, int $month, int $daysInMonth): array
    {
        // 1x/month: cycle day-of-month by row order, wrapping around the month length.
        if ($interval >= 21) {
            $day = (($order - 1) % $daysInMonth) + 1;
            return [Carbon::create($year, $month, $day)];
        }

        // 2x/month: fixed week-pair by row-order parity, fixed weekday by row order,
        // so the cadence (e.g. always week 1 & 3) never drifts month to month.
        if ($interval >= 10) {
            $weekPair = ($order % 2 === 1) ? [1, 3] : [2, 4];
            $weekdayOffset = ($order - 1) % 6; // 0..5 -> Monday..Saturday
            return array_map(
                fn ($week) => $this->dateForWeek($year, $month, $daysInMonth, $week, $weekdayOffset),
                $weekPair
            );
        }

        // <10 days (e.g. weekly): occur every week on a fixed weekday from row order.
        $weekdayOffset = ($order - 1) % 6;
        $dates = [];
        for ($week = 1; $week <= 4; $week++) {
            $dates[] = $this->dateForWeek($year, $month, $daysInMonth, $week, $weekdayOffset);
        }
        return $dates;
    }

    protected function dateForWeek(int $year, int $month, int $daysInMonth, int $week, int $weekdayOffset): Carbon
    {
        $weekStart = ($week - 1) * 7 + 1;
        $day = min($weekStart + $weekdayOffset, $daysInMonth);
        return Carbon::create($year, $month, $day);
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
