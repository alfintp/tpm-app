<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ApprovalFlowStep;
use App\Models\MaintenanceRecord;
use App\Models\MachineComponent;
use App\Models\MaintenanceSchedule;
use App\Models\Machine;
use App\Models\User;
use App\Models\Role;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ApprovalController extends Controller
{
    public function flowConfig()
    {
        $steps = ApprovalFlowStep::active()->ordered()->get()->map(fn ($s) => [
            'id' => $s->id,
            'role' => $s->role,
            'step_order' => $s->step_order,
            'is_active' => $s->is_active,
        ]);

        $roles = Role::active()->orderBy('display_name')->get()->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'display_name' => $r->display_name,
            'can_approve' => $r->can_approve,
            'is_active' => $r->is_active,
        ]);

        return response()->json([
            'roles' => $roles,
            'steps' => $steps,
        ]);
    }

    public function updateFlowConfig(Request $request)
    {
        $authUser = $request->user();
        if (!$authUser || $authUser->role !== 'admin') {
            return response()->json(['message' => 'Hanya admin yang dapat mengatur alur approval.'], 403);
        }

        $approvableRoles = Role::active()->canApprove()->pluck('name')->toArray();
        if (empty($approvableRoles)) {
            return response()->json(['message' => 'Tidak ada role yang dapat melakukan approval.'], 400);
        }

        $validated = $request->validate([
            'steps' => 'required|array|min:1|max:10',
            'steps.*.role' => 'required|in:' . implode(',', $approvableRoles),
        ]);

        DB::beginTransaction();
        try {
            // Remove old flow steps so step_order can be reused without unique conflicts
            ApprovalFlowStep::query()->delete();

            $order = 1;
            foreach ($validated['steps'] as $step) {
                ApprovalFlowStep::create([
                    'role' => $step['role'],
                    'step_order' => $order++,
                    'is_active' => true,
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Alur approval berhasil diperbarui.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal memperbarui alur approval: ' . $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        $authUser = $request->user();
        $role = $authUser?->role ?? 'technician';
        $userId = $authUser?->id;

        $flowSteps = ApprovalFlowStep::active()->ordered()->get();
        $approvingRoles = Role::active()->canApprove()->pluck('name')->toArray();
        $isApprover = $role === 'admin' || in_array($role, $approvingRoles);

        $baseQuery = MaintenanceRecord::with([
            'machine',
            'technician',
            'actions.component',
            'approvals.approver',
        ])
        ->where('status', 'completed')
        ->orderByDesc('maintenance_date');

        if (!$isApprover && $role !== 'admin') {
            $baseQuery->when($userId, fn($q) => $q->where('technician_id', $userId));
        }

        $records = $baseQuery->get()->map(function ($record) use ($flowSteps) {
            $state = $this->recordApprovalState($record, $flowSteps);
            return [
                'record_id'       => $record->id,
                'machine_name'    => $record->machine?->name,
                'machine_id'      => $record->machine?->id,
                'technician_id'   => $record->technician_id,
                'technician_name' => $record->technician?->full_name,
                'maintenance_date'=> $record->maintenance_date,
                'start_time'      => $record->start_time,
                'end_time'        => $record->end_time,
                'duration_minutes'=> $record->duration_minutes,
                'notes'           => $record->notes,
                'actions_count'   => $record->actions->count(),
                'actions'         => $record->actions->map(fn($a) => [
                    'component_name'     => $a->component?->name,
                    'action_type'        => $a->action_type,
                    'condition_before'   => $a->condition_before_pct,
                    'condition_after'    => $a->condition_after_pct,
                    'description'        => $a->description,
                ]),
                'approval_status' => $state['approval_status'],
                'approval_id'     => $state['approval_id'],
                'approval_notes'  => $state['approval_notes'],
                'approved_by'     => $state['approved_by'],
                'decided_at'      => $state['decided_at'],
                'current_step'    => $state['current_step'],
                'completed_steps' => $state['completed_steps'],
                'total_steps'     => $state['total_steps'],
                'pending_role'    => $state['pending_role'],
                'approvals'       => $state['approvals'],
                'flow_steps'        => $state['flow_steps'],
            ];
        });

        return response()->json($records);
    }

    public function decide(Request $request, $recordId)
    {
        $authUser = $request->user();
        if (!$authUser) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $roleCanApprove = $authUser->role === 'admin' || Role::active()->where('name', $authUser->role)->value('can_approve');
        if (!$roleCanApprove) {
            return response()->json(['message' => 'Role Anda tidak memiliki akses approval.'], 403);
        }

        $validated = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'notes'    => $request->input('decision') === 'rejected' ? 'required|string|min:3' : 'nullable|string',
        ], [
            'notes.required' => 'Alasan penolakan wajib diisi.',
            'notes.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        $flowSteps = ApprovalFlowStep::active()->ordered()->get();
        if ($flowSteps->isEmpty()) {
            return response()->json(['message' => 'Alur approval belum dikonfigurasi.'], 400);
        }

        $record = MaintenanceRecord::with(['actions', 'machine', 'approvals'])->findOrFail($recordId);
        $state = $this->recordApprovalState($record, $flowSteps);

        if ($state['approval_status'] === 'rejected') {
            return response()->json(['message' => 'Laporan ini sudah ditolak.'], 409);
        }
        if ($state['approval_status'] === 'approved') {
            return response()->json(['message' => 'Laporan ini sudah disetujui sepenuhnya.'], 409);
        }

        $currentStep = $state['current_step'];
        $currentRole = $flowSteps->firstWhere('step_order', $currentStep)?->role;

        if (!$currentRole) {
            return response()->json(['message' => 'Tahap approval tidak valid.'], 400);
        }

        // Admin can approve any step; assigned-role users can approve their own step
        if ($authUser->role !== 'admin' && $authUser->role !== $currentRole) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk tahap approval ini.'], 403);
        }

        // Prevent duplicate decision at this step
        if ($record->approvals->where('step_order', $currentStep)->whereIn('decision', ['approved', 'rejected'])->isNotEmpty()) {
            return response()->json(['message' => 'Tahap ini sudah diputuskan.'], 409);
        }

        DB::beginTransaction();
        try {
            $approval = Approval::create([
                'record_id'   => $recordId,
                'approver_id' => $authUser->id,
                'step_order'  => $currentStep,
                'decision'    => $validated['decision'],
                'notes'       => $validated['notes'] ?? null,
                'decided_at'  => Carbon::now(),
            ]);

            $isLastStep = $currentStep >= $flowSteps->count();

            if ($validated['decision'] === 'rejected') {
                // No data changes on rejection
            } elseif ($isLastStep) {
                // Final approval: apply deferred component and machine updates
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
                        }
                    }
                }

                if ($record->machine) {
                    $record->machine->update(['status' => 'active']);
                }

                if ($record->schedule_id) {
                    $schedule = MaintenanceSchedule::find($record->schedule_id);
                    if ($schedule) {
                        $schedule->update([
                            'next_due_date' => Carbon::parse($schedule->next_due_date)->addDays($schedule->interval_days),
                        ]);
                    }
                }
            }

            $machineName = $record->machine?->name ?? 'Mesin';
            $statusWord  = $validated['decision'] === 'approved' ? 'Menyetujui' : 'Menolak';
            ActivityLog::log(
                "Decision Laporan ({$validated['decision']})",
                "{$statusWord} laporan maintenance mesin: {$machineName} pada tahap {$currentStep}"
            );

            DB::commit();
            return response()->json($approval->load('approver'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal memproses keputusan: ' . $e->getMessage()], 500);
        }
    }

    private function recordApprovalState(MaintenanceRecord $record, $flowSteps)
    {
        $steps = $flowSteps->sortBy('step_order')->values();
        $totalSteps = $steps->count();
        $decisions = $record->approvals->sortBy('step_order');

        $rejected = $decisions->firstWhere('decision', 'rejected');
        if ($rejected) {
            return [
                'approval_status' => 'rejected',
                'approval_id' => $rejected->id,
                'approval_notes' => $rejected->notes,
                'approved_by' => $rejected->approver?->full_name,
                'decided_at' => $rejected->decided_at,
                'current_step' => $rejected->step_order,
                'completed_steps' => $decisions->where('decision', 'approved')->count(),
                'total_steps' => $totalSteps,
                'pending_role' => null,
                'approvals' => $this->mapApprovals($decisions),
                'flow_steps' => $steps,
            ];
        }

        $approvedOrders = $decisions->where('decision', 'approved')->pluck('step_order')->sort()->values();
        $completedSteps = $approvedOrders->count();
        $currentStep = $completedSteps + 1;
        $isFullyApproved = $completedSteps >= $totalSteps && $totalSteps > 0;

        if ($isFullyApproved) {
            $last = $decisions->where('decision', 'approved')->sortByDesc('step_order')->first();
            return [
                'approval_status' => 'approved',
                'approval_id' => $last?->id,
                'approval_notes' => $last?->notes,
                'approved_by' => $last?->approver?->full_name,
                'decided_at' => $last?->decided_at,
                'current_step' => $totalSteps,
                'completed_steps' => $completedSteps,
                'total_steps' => $totalSteps,
                'pending_role' => null,
                'approvals' => $this->mapApprovals($decisions),
                'flow_steps' => $steps,
            ];
        }

        $pendingRole = $steps->firstWhere('step_order', $currentStep)?->role;

        return [
            'approval_status' => 'pending',
            'approval_id' => null,
            'approval_notes' => null,
            'approved_by' => null,
            'decided_at' => null,
            'current_step' => $currentStep,
            'completed_steps' => $completedSteps,
            'total_steps' => $totalSteps,
            'pending_role' => $pendingRole,
            'approvals' => $this->mapApprovals($decisions),
            'flow_steps' => $steps,
        ];
    }

    private function mapApprovals($decisions)
    {
        return $decisions->map(fn ($a) => [
            'step_order' => $a->step_order,
            'decision' => $a->decision,
            'notes' => $a->notes,
            'decided_at' => $a->decided_at,
            'approver' => $a->approver?->full_name,
        ])->values();
    }
}
