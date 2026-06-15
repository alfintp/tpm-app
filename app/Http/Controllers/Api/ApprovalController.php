<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\MaintenanceRecord;
use App\Models\MachineComponent;
use App\Models\MaintenanceSchedule;
use App\Models\Machine;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $role = $authUser?->role ?? 'technician';
        $userId = $authUser?->id;

        if (in_array($role, ['manager', 'admin'])) {
            // Manager: see all records that have no approval yet (pending), plus all existing approvals
            $records = MaintenanceRecord::with([
                'machine',
                'technician',
                'actions.component',
                'approval.approver',
            ])
            ->where('status', 'completed')
            ->orderByDesc('maintenance_date')
            ->get()
            ->map(function ($record) {
                return [
                    'record_id'       => $record->id,
                    'machine_name'    => $record->machine?->name,
                    'machine_id'      => $record->machine?->id,
                    'technician_name' => $record->technician?->full_name,
                    'maintenance_date'=> $record->maintenance_date,
                    'notes'           => $record->notes,
                    'actions_count'   => $record->actions->count(),
                    'actions'         => $record->actions->map(fn($a) => [
                        'component_name'     => $a->component?->name,
                        'action_type'        => $a->action_type,
                        'condition_before'   => $a->condition_before_pct,
                        'condition_after'    => $a->condition_after_pct,
                        'description'        => $a->description,
                    ]),
                    'approval_id'     => $record->approval?->id,
                    'approval_status' => $record->approval?->decision ?? 'pending',
                    'approval_notes'  => $record->approval?->notes,
                    'approved_by'     => $record->approval?->approver?->full_name,
                    'decided_at'      => $record->approval?->decided_at,
                ];
            });

            return response()->json($records);
        }

        // Technician: see only their own records + approval status
        $records = MaintenanceRecord::with([
            'machine',
            'technician',
            'actions.component',
            'approval.approver',
        ])
        ->where('status', 'completed')
        ->when($userId, fn($q) => $q->where('technician_id', $userId))
        ->orderByDesc('maintenance_date')
        ->get()
        ->map(function ($record) {
            return [
                'record_id'       => $record->id,
                'machine_name'    => $record->machine?->name,
                'machine_id'      => $record->machine?->id,
                'technician_name' => $record->technician?->full_name,
                'maintenance_date'=> $record->maintenance_date,
                'notes'           => $record->notes,
                'actions_count'   => $record->actions->count(),
                'actions'         => $record->actions->map(fn($a) => [
                    'component_name'     => $a->component?->name,
                    'action_type'        => $a->action_type,
                    'condition_before'   => $a->condition_before_pct,
                    'condition_after'    => $a->condition_after_pct,
                    'description'        => $a->description,
                ]),
                'approval_id'     => $record->approval?->id,
                'approval_status' => $record->approval?->decision ?? 'pending',
                'approval_notes'  => $record->approval?->notes,
                'approved_by'     => $record->approval?->approver?->full_name,
                'decided_at'      => $record->approval?->decided_at,
            ];
        });

        return response()->json($records);
    }

    public function decide(Request $request, $recordId)
    {
        $authUser = $request->user();
        if (!$authUser || !in_array($authUser->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Hanya Admin atau Manager yang dapat memberi keputusan approval.'], 403);
        }

        $validated = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'notes'    => 'nullable|string',
        ]);

        $record = MaintenanceRecord::with(['actions', 'machine'])->findOrFail($recordId);

        // Prevent double-deciding an already-decided approval
        $existing = Approval::where('record_id', $recordId)->first();
        if ($existing && in_array($existing->decision, ['approved', 'rejected'])) {
            return response()->json(['message' => 'Laporan ini sudah mendapatkan keputusan sebelumnya.'], 409);
        }

        DB::beginTransaction();
        try {
            $approval = Approval::updateOrCreate(
                ['record_id' => $recordId],
                [
                    'approver_id' => $authUser->id,
                    'decision'    => $validated['decision'],
                    'notes'       => $validated['notes'] ?? null,
                    'decided_at'  => Carbon::now(),
                ]
            );

            if ($validated['decision'] === 'approved') {
                // Apply component and machine updates that were deferred at record creation
                foreach ($record->actions as $action) {
                    if ($action->condition_after_pct !== null) {
                        $component = MachineComponent::find($action->machine_component_id);
                        if ($component) {
                            $component->update([
                                'last_condition_pct' => $action->condition_after_pct,
                                'last_replaced_at'   => $action->action_type === 'replace'
                                    ? Carbon::parse($record->maintenance_date)
                                    : $component->last_replaced_at,
                            ]);
                            // booted observer on MachineComponent will update machine avg
                        }
                    }
                }

                // Update machine status to active
                if ($record->machine) {
                    $record->machine->update(['status' => 'active']);
                }

                // Advance maintenance schedule next_due_date
                if ($record->schedule_id) {
                    $schedule = MaintenanceSchedule::find($record->schedule_id);
                    if ($schedule) {
                        // Calculate next due date starting from the current planned next_due_date
                        $schedule->update([
                            'next_due_date' => Carbon::parse($schedule->next_due_date)->addDays($schedule->interval_days),
                        ]);
                    }
                }
            }
            // On rejection: no data changes — component/machine stay as-is

            $machineName = $record->machine?->name ?? 'Mesin';
            $statusWord  = $validated['decision'] === 'approved' ? 'Menyetujui' : 'Menolak';
            ActivityLog::log(
                "Decision Laporan ({$validated['decision']})",
                "{$statusWord} laporan maintenance mesin: {$machineName}"
            );

            DB::commit();
            return response()->json($approval->load('approver'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal memproses keputusan: ' . $e->getMessage()], 500);
        }
    }
}
