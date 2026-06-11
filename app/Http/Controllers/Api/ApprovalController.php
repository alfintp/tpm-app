<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\MaintenanceRecord;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->query('user_id');
        $role = $request->query('role', 'technician');

        if ($role === 'manager') {
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
        $validated = $request->validate([
            'approver_id' => 'required|exists:users,id',
            'decision'    => 'required|in:approved,rejected',
            'notes'       => 'nullable|string',
        ]);

        $record = MaintenanceRecord::findOrFail($recordId);

        $approval = Approval::updateOrCreate(
            ['record_id' => $recordId],
            [
                'approver_id' => $validated['approver_id'],
                'decision'    => $validated['decision'],
                'notes'       => $validated['notes'] ?? null,
                'decided_at'  => Carbon::now(),
            ]
        );

        $machineName = $record->machine ? $record->machine->name : 'Mesin';
        $statusWord = $validated['decision'] === 'approved' ? 'Menyetujui' : 'Menolak';
        ActivityLog::log("Decision Laporan ({$validated['decision']})", "{$statusWord} laporan maintenance mesin: {$machineName}");

        return response()->json($approval->load('approver'));
    }
}
