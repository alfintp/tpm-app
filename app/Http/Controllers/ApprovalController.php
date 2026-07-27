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
            'reporter_role' => $s->reporter_role,
            'role' => $s->role,
            'step_order' => $s->step_order,
            'is_active' => $s->is_active,
        ]);

        $roles = Role::active()->orderBy('display_name')->get()->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'display_name' => $r->display_name,
            'can_approve' => $r->can_approve,
            'can_report' => $r->can_report,
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
            'steps' => 'present|array',
            'steps.*.reporter_role' => 'required|string|max:50',
            'steps.*.role' => 'required|in:' . implode(',', $approvableRoles),
        ]);

        DB::beginTransaction();
        try {
            // Before changing the flow, freeze the current active flow into all existing
            // reports that do not already have a snapshot. New reports will snapshot the
            // new flow when they are created.
            $currentSteps = ApprovalFlowStep::active()->ordered()->get();
            if ($currentSteps->isNotEmpty()) {
                $snapshotsByReporter = $currentSteps->groupBy('reporter_role')->map(fn ($steps) =>
                    $steps->map(fn ($s) => [
                        'id' => $s->id,
                        'reporter_role' => $s->reporter_role,
                        'role' => $s->role,
                        'step_order' => $s->step_order,
                        'is_active' => $s->is_active,
                    ])->values()->toArray()
                );

                MaintenanceRecord::whereNull('approval_flow_snapshot')
                    ->chunkById(100, function ($records) use ($snapshotsByReporter) {
                        foreach ($records as $record) {
                            $role = $record->technician?->role ?? 'technician';
                            $snapshot = $snapshotsByReporter[$role]
                                ?? ($snapshotsByReporter['technician'] ?? []);
                            if (!empty($snapshot)) {
                                $record->update(['approval_flow_snapshot' => $snapshot]);
                            }
                        }
                    });
            }

            // Remove old flow steps so step_order can be reused without unique conflicts
            ApprovalFlowStep::query()->delete();

            // Group input steps by reporter_role to assign step_order sequentially
            $groupedSteps = [];
            foreach ($validated['steps'] as $step) {
                $groupedSteps[$step['reporter_role']][] = $step['role'];
            }

            foreach ($groupedSteps as $reporterRole => $approverRoles) {
                $order = 1;
                foreach ($approverRoles as $role) {
                    ApprovalFlowStep::create([
                        'reporter_role' => $reporterRole,
                        'role' => $role,
                        'step_order' => $order++,
                        'is_active' => true,
                    ]);
                }
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
            'machine.components',
            'machine.schedules',
            'schedule',
            'technician',
            'actions.component',
            'actions.indicatorValues.indicator',
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
                'machine_location'=> $record->machine?->location,
                'machine_kota'    => $record->machine?->kota,
                'technician_id'   => $record->technician_id,
                'technician_name' => $record->technician?->full_name,
                'maintenance_date'=> $record->maintenance_date,
                'created_at'      => $record->created_at,
                'start_time'      => $record->start_time,
                'end_time'        => $record->end_time,
                'duration_minutes'=> $record->duration_minutes,
                'is_unscheduled'  => (bool) $record->is_unscheduled,
                'is_late'         => (bool) $record->is_late,
                'schedule_id'     => $record->schedule_id,
                'scheduled_period_date' => $record->scheduled_period_date,
                'notes'           => $record->notes,
                'actions_count'   => $record->actions->count(),
                'actions'         => $record->actions->map(fn($a) => [
                    'component_name'     => $a->component?->name,
                    'component_spec'     => $a->component?->specification,
                    'component_difficulty' => $a->component?->difficulty,
                    'action_type'        => $a->action_type,
                    'condition_before'   => $a->condition_before_pct,
                    'condition_after'    => $a->condition_after_pct,
                    'description'        => $a->description,
                    'indicator_values'   => $a->indicatorValues->map(fn($iv) => [
                        'indicator_name'        => $iv->indicator?->name,
                        'indicator_description' => $iv->indicator?->description,
                        'value'                 => (bool) $iv->value,
                    ])->values(),
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
                'component_stats'   => $this->computeComponentStats($record),
                'monthly_progress'  => $this->computeMonthlyProgress($record),
            ];
        });

        return response()->json($records);
    }

    public function show($recordId)
    {
        $record = MaintenanceRecord::with([
            'machine.components',
            'machine.schedules',
            'schedule',
            'technician',
            'actions.component',
            'actions.indicatorValues.indicator',
            'approvals.approver',
        ])->findOrFail($recordId);

        $flowSteps = ApprovalFlowStep::active()->ordered()->get();
        $state = $this->recordApprovalState($record, $flowSteps);

        return response()->json([
            'record_id'       => $record->id,
            'machine_name'    => $record->machine?->name,
            'machine_id'      => $record->machine?->id,
            'machine_location'=> $record->machine?->location,
            'machine_kota'    => $record->machine?->kota,
            'technician_id'   => $record->technician_id,
            'technician_name' => $record->technician?->full_name,
            'maintenance_date'=> $record->maintenance_date,
            'created_at'      => $record->created_at,
            'start_time'      => $record->start_time,
            'end_time'        => $record->end_time,
            'duration_minutes'=> $record->duration_minutes,
            'is_unscheduled'  => (bool) $record->is_unscheduled,
            'is_late'         => (bool) $record->is_late,
            'schedule_id'     => $record->schedule_id,
            'scheduled_period_date' => $record->scheduled_period_date,
            'notes'           => $record->notes,
            'actions_count'   => $record->actions->count(),
            'actions'         => $record->actions->map(fn($a) => [
                'component_name'     => $a->component?->name,
                'component_spec'     => $a->component?->specification,
                'component_difficulty' => $a->component?->difficulty,
                'action_type'        => $a->action_type,
                'condition_before'   => $a->condition_before_pct,
                'condition_after'    => $a->condition_after_pct,
                'description'        => $a->description,
                'indicator_values'   => $a->indicatorValues->map(fn($iv) => [
                    'indicator_name'        => $iv->indicator?->name,
                    'indicator_description' => $iv->indicator?->description,
                    'value'                 => (bool) $iv->value,
                ])->values(),
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
            'component_stats'   => $this->computeComponentStats($record),
            'monthly_progress'  => $this->computeMonthlyProgress($record),
        ]);
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

        $record = MaintenanceRecord::with(['actions', 'machine', 'approvals'])->findOrFail($recordId);

        $reporterRole = $record->technician?->role ?? 'technician';
        $flowSteps = ApprovalFlowStep::active()
            ->where('reporter_role', $reporterRole)
            ->ordered()
            ->get();

        if ($flowSteps->isEmpty() && $reporterRole !== 'technician') {
            $flowSteps = ApprovalFlowStep::active()
                ->where('reporter_role', 'technician')
                ->ordered()
                ->get();
        }

        $state = $this->recordApprovalState($record, $flowSteps);
        $steps = $state['flow_steps'] ?? collect();

        if ($steps->isEmpty()) {
            return response()->json(['message' => 'Alur approval belum dikonfigurasi untuk role pembuat laporan.'], 400);
        }

        if ($state['approval_status'] === 'rejected') {
            return response()->json(['message' => 'Laporan ini sudah ditolak.'], 409);
        }
        if ($state['approval_status'] === 'approved') {
            return response()->json(['message' => 'Laporan ini sudah disetujui sepenuhnya.'], 409);
        }

        $currentStep = $state['current_step'];
        $stepItem = $steps->firstWhere('step_order', $currentStep);
        $currentRole = is_array($stepItem) ? ($stepItem['role'] ?? null) : ($stepItem?->role);

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

            $isLastStep = $currentStep >= $steps->count();

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

                if ($record->schedule_id && $record->scheduled_period_date) {
                    $schedule = MaintenanceSchedule::find($record->schedule_id);
                    $completedComponentIds = MaintenanceRecord::query()
                        ->where('machine_id', $record->machine_id)
                        ->where('schedule_id', $record->schedule_id)
                        ->whereDate('scheduled_period_date', $record->scheduled_period_date)
                        ->where('is_unscheduled', false)
                        ->where('status', 'completed')
                        ->with('actions:id,record_id,machine_component_id')
                        ->get()
                        ->flatMap(fn ($periodRecord) => $periodRecord->actions->pluck('machine_component_id'))
                        ->unique();
                    $componentCount = MachineComponent::where('machine_id', $record->machine_id)->count();

                    if ($schedule && $componentCount > 0 && $completedComponentIds->count() >= $componentCount) {
                        $currentDue = Carbon::parse($schedule->next_due_date)->startOfDay();
                        $periodDue = Carbon::parse($record->scheduled_period_date)->startOfDay();
                        if ($currentDue->lessThanOrEqualTo($periodDue)) {
                            $newDue = $currentDue->addDays($schedule->interval_days);
                            // Clamp to end of that month so maintenance never spills into the following month
                            $endOfMonth = $newDue->copy()->endOfMonth()->startOfDay();
                            if ($newDue->gt($endOfMonth)) {
                                $newDue = $endOfMonth;
                            }
                            $schedule->update(['next_due_date' => $newDue]);
                        }
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
        $reporterRole = $record->technician?->role ?? 'technician';

        // Prefer the snapshot taken at report creation; fallback to current active flow
        $snapshot = $record->approval_flow_snapshot;
        if (!empty($snapshot)) {
            $steps = collect($snapshot)
                ->filter(fn ($s) => ($s['reporter_role'] ?? null) === $reporterRole)
                ->sortBy('step_order')
                ->values();
            if ($steps->isEmpty() && $reporterRole !== 'technician') {
                $steps = collect($snapshot)
                    ->filter(fn ($s) => ($s['reporter_role'] ?? null) === 'technician')
                    ->sortBy('step_order')
                    ->values();
            }
        }

        if (empty($steps) || $steps->isEmpty()) {
            $steps = $flowSteps->filter(fn ($s) => $s->reporter_role === $reporterRole)
                ->sortBy('step_order')
                ->values();
            if ($steps->isEmpty() && $reporterRole !== 'technician') {
                $steps = $flowSteps->filter(fn ($s) => $s->reporter_role === 'technician')
                    ->sortBy('step_order')
                    ->values();
            }
        }

        $totalSteps = $steps->count();
        $decisions = $record->approvals->sortBy('step_order');

        $stepRole = fn ($step) => $step ? (is_array($step) ? ($step['role'] ?? null) : $step->role) : null;

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

        $pendingRole = $stepRole($steps->firstWhere('step_order', $currentStep));

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

    private function computeComponentStats(MaintenanceRecord $record)
    {
        $allComponents = $record->machine?->components ?? collect();
        $totalComponents = $allComponents->count();

        // Get all unique component IDs that were reported in this record
        $reportedComponentIds = $record->actions->pluck('machine_component_id')->unique();

        // Group components by difficulty: ringan vs everything else (sedang, berat, null, empty)
        $ringanComponents = $allComponents->filter(fn ($comp) => ($comp->difficulty ?? null) === 'ringan');
        $beratComponents = $allComponents->filter(fn ($comp) => ($comp->difficulty ?? null) !== 'ringan');

        $makeStat = function ($key, $displayName, $components) use ($reportedComponentIds) {
            $ids = $components->pluck('id');
            $reported = $reportedComponentIds->intersect($ids)->count();

            return [
                'role'         => $key,
                'display_name' => $displayName,
                'reported'     => $reported,
                'total'        => $components->count(),
            ];
        };

        $roleStats = collect([
            $makeStat('berat', 'Berat', $beratComponents),
            $makeStat('ringan', 'Ringan', $ringanComponents),
        ])->filter(fn ($s) => $s['total'] > 0)->values();

        return [
            'total_components' => $totalComponents,
            'roles'            => $roleStats,
        ];
    }

    private function computeMonthlyProgress(MaintenanceRecord $record)
    {
        $machine = $record->machine;
        $schedule = $record->schedule;
        if (!$machine || !$schedule || $record->is_unscheduled || !$record->scheduled_period_date) {
            return null;
        }

        $allComponents = $machine->components ?? collect();
        if ($allComponents->isEmpty()) {
            return null;
        }

        $intervalDays = (int) $schedule->interval_days;
        if ($intervalDays <= 0) {
            return null;
        }

        // Anchor to the report's scheduled period so Week 1 / Week 2 both match.
        $baseDate = Carbon::parse($record->scheduled_period_date)->startOfDay();
        $monthStart = $baseDate->copy()->startOfMonth();
        $monthEnd = $baseDate->copy()->endOfMonth();
        $monthStartTs = $monthStart->getTimestamp();
        $monthEndTs = $monthEnd->getTimestamp();

        $intervalMs = $intervalDays * 86400000;
        $raw = $baseDate->getTimestamp();
        $safety = 0;
        while ($raw > $monthStartTs && $safety++ < 200) {
            $raw -= $intervalMs;
        }
        if ($raw < $monthStartTs) {
            $raw += $intervalMs;
        }

        $periodLimit = $intervalDays <= 14 ? 2 : 1;
        $dueTimestamps = [];
        $safety = 0;
        while ($raw <= $monthEndTs && $safety++ < 200 && count($dueTimestamps) < $periodLimit) {
            $dueTimestamps[] = $raw;
            $raw += $intervalMs;
        }

        if (empty($dueTimestamps)) {
            return null;
        }

        $periods = [];
        foreach ($dueTimestamps as $idx => $dueTs) {
            $due = Carbon::createFromTimestamp($dueTs)->startOfDay();
            $prevDue = $idx === 0 ? $monthStart->copy()->subDay() : Carbon::createFromTimestamp($dueTimestamps[$idx - 1])->startOfDay();
            $periods[] = [
                'label' => count($dueTimestamps) > 1 ? 'Week ' . ($idx + 1) : 'Bulan Ini',
                'due_date' => $due->toDateString(),
                'start' => $prevDue->copy()->addDay(),
                'end' => $due,
            ];
        }

        $ringanIds = $allComponents->filter(fn ($c) => ($c->difficulty ?? null) === 'ringan')->pluck('id')->all();
        $beratIds = $allComponents->filter(fn ($c) => ($c->difficulty ?? null) !== 'ringan')->pluck('id')->all();

        $firstStart = $periods[0]['start'];
        $lastEnd = $periods[count($periods) - 1]['end'];
        $monthRecords = MaintenanceRecord::with(['actions', 'latestApproval'])
            ->where('machine_id', $machine->id)
            ->where('status', 'completed')
            ->whereBetween('maintenance_date', [$firstStart->toDateString(), $lastEnd->toDateString()])
            ->get();

        $reportScheduleDate = $baseDate;
        $result = [];
        foreach ($periods as $period) {
            $reportedIds = collect();
            foreach ($monthRecords as $rec) {
                if ($rec->latestApproval && $rec->latestApproval->decision === 'rejected') {
                    continue;
                }
                $recDate = Carbon::parse($rec->maintenance_date)->startOfDay();
                if ($recDate->lt($period['start']) || $recDate->gt($period['end'])) {
                    continue;
                }
                foreach ($rec->actions as $action) {
                    if ($action->machine_component_id) {
                        $reportedIds->add($action->machine_component_id);
                    }
                }
            }
            $reported = $reportedIds->all();
            $result[] = [
                'label' => $period['label'],
                'due_date' => $period['due_date'],
                'is_report_period' => $reportScheduleDate->equalTo($period['end']),
                'ringan' => [
                    'reported' => count(array_intersect($reported, $ringanIds)),
                    'total' => count($ringanIds),
                ],
                'berat' => [
                    'reported' => count(array_intersect($reported, $beratIds)),
                    'total' => count($beratIds),
                ],
            ];
        }

        return [
            'total_components' => $allComponents->count(),
            'periods' => $result,
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
