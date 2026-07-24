<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\MaintenanceSchedule;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('maintenance:balance-schedules {--apply} {--month=}', function () {
    $apply = $this->option('apply');
    $monthInput = $this->option('month');
    $month = $monthInput ? Carbon::parse($monthInput)->startOfMonth() : Carbon::now()->startOfMonth();
    $year = $month->year;
    $monthNum = $month->month;
    $lastDay = $month->daysInMonth;
    $today = Carbon::today();

    $schedules = MaintenanceSchedule::with('machine')
        ->where('is_active', true)
        ->whereNotNull('interval_days')
        ->get();

    if ($schedules->isEmpty()) {
        $this->warn('Tidak ada jadwal aktif.');
        return;
    }

    $byCity = $schedules->groupBy(fn ($s) => strtolower($s->machine?->kota ?? 'unknown'));
    $changes = [];

    foreach ($byCity as $kota => $items) {
        $load = array_fill(1, $lastDay, 0);
        $twoX = $items->where('interval_days', '<=', 14)->sortBy('machine_id')->values();
        $oneX = $items->where('interval_days', '>', 14)->sortBy('machine_id')->values();

        foreach ($twoX as $schedule) {
            $interval = (int) $schedule->interval_days;
            $bestS = null;
            $bestScore = PHP_INT_MAX;
            $maxStart = $lastDay - $interval;
            // Prefer starts >= 4 so the month does not end up with 3 occurrences in 31-day months
            for ($s = 4; $s <= $maxStart; $s++) {
                $s2 = $s + $interval;
                if (Carbon::create($year, $monthNum, $s)->isSunday()) continue;
                if (Carbon::create($year, $monthNum, $s2)->isSunday()) continue;
                $score = $load[$s] + $load[$s2];
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestS = $s;
                }
            }
            if ($bestS === null) {
                for ($s = 1; $s <= $maxStart; $s++) {
                    $s2 = $s + $interval;
                    if (Carbon::create($year, $monthNum, $s)->isSunday()) continue;
                    if (Carbon::create($year, $monthNum, $s2)->isSunday()) continue;
                    $score = $load[$s] + $load[$s2];
                    if ($score < $bestScore) {
                        $bestScore = $score;
                        $bestS = $s;
                    }
                }
            }
            if ($bestS === null) {
                $bestS = 1;
            }
            $second = $bestS + $interval;
            $load[$bestS]++;
            $load[$second]++;
            $first = Carbon::create($year, $monthNum, $bestS);
            $nextDue = $first->copy();
            while ($nextDue->lt($today) || $nextDue->isSunday()) {
                $nextDue = $nextDue->addDays($interval);
            }
            $changes[] = [
                'schedule' => $schedule,
                'city' => $kota,
                'old' => $schedule->next_due_date?->toDateString(),
                'new' => $nextDue->toDateString(),
                'type' => '2x/bulan',
                'targets' => [
                    $first->toDateString(),
                    Carbon::create($year, $monthNum, $second)->toDateString(),
                ],
            ];
        }

        foreach ($oneX as $schedule) {
            $interval = (int) $schedule->interval_days;
            $bestD = null;
            $bestScore = PHP_INT_MAX;
            for ($d = 1; $d <= $lastDay; $d++) {
                if (Carbon::create($year, $monthNum, $d)->isSunday()) continue;
                if ($load[$d] < $bestScore) {
                    $bestScore = $load[$d];
                    $bestD = $d;
                }
            }
            if ($bestD === null) $bestD = 1;
            $load[$bestD]++;
            $target = Carbon::create($year, $monthNum, $bestD);
            $nextDue = $target->copy();
            while ($nextDue->lt($today) || $nextDue->isSunday()) {
                $nextDue = $nextDue->addDays($interval);
            }
            $changes[] = [
                'schedule' => $schedule,
                'city' => $kota,
                'old' => $schedule->next_due_date?->toDateString(),
                'new' => $nextDue->toDateString(),
                'type' => '1x/bulan',
                'targets' => [$target->toDateString()],
            ];
        }
    }

    if ($apply) {
        foreach ($changes as $c) {
            $c['schedule']->next_due_date = $c['new'];
            $c['schedule']->save();
        }
        $this->info('Jadwal berhasil diperbarui.');
    } else {
        $this->warn('Mode dry-run. Tambahkan --apply untuk menyimpan perubahan.');
    }

    // Daily load summary per city (using displayed target dates, not the future next_due_date)
    $summary = [];
    foreach (array_unique(array_column($changes, 'city')) as $kota) {
        $dayCounts = array_fill(1, $lastDay, 0);
        foreach ($changes as $c) {
            if ($c['city'] !== $kota) continue;
            foreach ($c['targets'] ?? [] as $t) {
                $day = (int) Carbon::parse($t)->format('j');
                $dayCounts[$day]++;
            }
        }
        for ($d = 1; $d <= $lastDay; $d++) {
            $summary[] = [
                'kota' => $kota,
                'tanggal' => $d,
                'jumlah_mesin' => $dayCounts[$d],
            ];
        }
    }

    $this->newLine();
    $this->info('Ringkasan jumlah mesin per tanggal (hasil dry-run):');
    $this->table(
        ['Kota', 'Tanggal', 'Jumlah Mesin'],
        $summary
    );

    $this->newLine();
    $this->table(
        ['Kota', 'Mesin', 'Tipe', 'next_due_date lama', 'next_due_date baru'],
        collect($changes)->map(fn ($c) => [
            $c['city'],
            $c['schedule']->machine?->name ?? '-',
            $c['type'],
            $c['old'] ?? '-',
            $c['new'],
        ])->toArray()
    );
})->purpose('Redistribusi jadwal maintenance aktif agar merata per kota (dry-run default).');
