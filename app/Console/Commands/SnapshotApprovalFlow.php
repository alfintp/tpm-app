<?php

namespace App\Console\Commands;

use App\Models\ApprovalFlowStep;
use App\Models\MaintenanceRecord;
use Illuminate\Console\Command;

class SnapshotApprovalFlow extends Command
{
    protected $signature = 'maintenance-records:snapshot-flow {--all : Override existing snapshots as well}';

    protected $description = 'Snapshot the current approval flow for maintenance records missing a snapshot.';

    public function handle(): int
    {
        $currentSteps = ApprovalFlowStep::active()->ordered()->get();

        if ($currentSteps->isEmpty()) {
            $this->error('Tidak ada alur approval aktif untuk di-snapshot.');
            return self::FAILURE;
        }

        $snapshotsByReporter = $currentSteps->groupBy('reporter_role')->map(fn ($steps) =>
            $steps->map(fn ($s) => [
                'id' => $s->id,
                'reporter_role' => $s->reporter_role,
                'role' => $s->role,
                'step_order' => $s->step_order,
                'is_active' => $s->is_active,
            ])->values()->toArray()
        );

        $query = MaintenanceRecord::query();
        if (!$this->option('all')) {
            $query->whereNull('approval_flow_snapshot');
        }

        $count = 0;
        $query->chunkById(100, function ($records) use ($snapshotsByReporter, &$count) {
            foreach ($records as $record) {
                $role = $record->technician?->role ?? 'technician';
                $snapshot = $snapshotsByReporter[$role]
                    ?? ($snapshotsByReporter['technician'] ?? []);

                if (empty($snapshot)) {
                    continue;
                }

                $record->update(['approval_flow_snapshot' => $snapshot]);
                $count++;
            }
        });

        $this->info("Snapshot alur approval berhasil diterapkan pada {$count} laporan.");
        $this->warn('Catatan: laporan akan mengikuti alur approval yang aktif SAAT command ini dijalankan.');
        return self::SUCCESS;
    }
}
