<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceAction;
use App\Models\MaintenanceActionIndicator;
use App\Models\Machine;
use App\Models\MachineComponent;
use App\Models\ComponentIndicator;
use App\Models\MaintenanceSchedule;
use App\Models\ApprovalFlowStep;
use App\Models\ActivityLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaintenanceRecordController extends Controller
{
    public function index()
    {
        $records = MaintenanceRecord::with(['machine', 'technician', 'actions.component', 'approvals'])->get();
        return response()->json($records);
    }

    public function store(Request $request)
    {
        $authUser = $request->user();
        if ($authUser && $authUser->role !== 'admin') {
            $canReport = Role::active()->where('name', $authUser->role)->value('can_report');
            if (!$canReport) {
                return response()->json(['error' => 'Role Anda tidak memiliki izin untuk mengirim laporan.'], 403);
            }
        }

        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'technician_id' => 'nullable|exists:users,id',
            'schedule_id' => 'nullable|exists:maintenance_schedules,id',
            'scheduled_period_date' => 'nullable|date',
            'is_unscheduled' => 'nullable|boolean',
            'maintenance_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'duration_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'actions' => 'nullable|array',
            'actions.*.action_type' => 'required|in:repair,replace,inspect,clean,lubricate',
            'actions.*.machine_component_id' => 'required|exists:machine_components,id',
            'actions.*.condition_before_pct' => 'nullable|numeric|min:0|max:100',
            'actions.*.condition_after_pct' => 'nullable|numeric|min:0|max:100',
            'actions.*.description' => 'nullable|string',
            'actions.*.indicator_values' => 'nullable|array',
            'actions.*.indicator_values.*.component_indicator_id' => 'required|exists:component_indicators,id',
            'actions.*.indicator_values.*.value' => 'required|boolean',
        ]);

        // Always use the authenticated user as the technician
        if ($request->user()) {
            $validated['technician_id'] = $request->user()->id;
        }

        // City-based access check
        $authUser = $request->user();
        $machine = Machine::with(['components', 'records' => function($q) {
            $q->where('status', 'completed');
        }])->find($validated['machine_id']);

        if (!$machine) {
            return response()->json(['error' => 'Mesin tidak ditemukan.'], 404);
        }

        if ($authUser && ($authUser->city ?? 'both') !== 'both') {
            if ($machine->kota !== $authUser->city) {
                return response()->json([
                    'error' => 'Akses ditolak. Anda tidak dapat membuat laporan untuk mesin di luar kota yang ditugaskan kepada Anda.'
                ], 403);
            }
        }

        // Machine Locking Logic
        if ($authUser && $authUser->role !== 'admin') {
            $isOpen = $machine->isOpenForMaintenance();
            
            if (!$isOpen) {
                // Check if this is a continuation of a partially checked machine
                // A machine is "partially checked" if some components are already completed for the current/nearest period
                $dueDate = Carbon::parse($validated['scheduled_period_date'] ?? now())->startOfDay();
                $periodStart = $dueDate->copy()->startOfMonth();
                
                $periodRecords = $machine->records()
                    ->where('status', 'completed')
                    ->whereDate('maintenance_date', '>=', $periodStart)
                    ->whereDate('maintenance_date', '<=', Carbon::today())
                    ->get();

                $checkedIds = [];
                foreach ($periodRecords as $r) {
                    foreach ($r->actions as $a) {
                        if ($a->machine_component_id) $checkedIds[] = $a->machine_component_id;
                    }
                }
                $checkedIds = array_unique($checkedIds);
                $totalComponents = $machine->components()->count();
                $checkedCount = count($checkedIds);
                
                $isPartiallyDone = ($checkedCount > 0 && $checkedCount < $totalComponents);

                if (!$isPartiallyDone) {
                    return response()->json([
                        'error' => 'Mesin sedang terkunci. Laporan hanya dapat dibuat dalam periode jadwal maintenance yang ditentukan.'
                    ], 403);
                }
            }
        }

        DB::beginTransaction();

        try {
            // Calculate overall before/after from actions
            $beforeVals = collect($validated['actions'] ?? [])->pluck('condition_before_pct')->filter();
            $afterVals = collect($validated['actions'] ?? [])->pluck('condition_after_pct')->filter();

            $recordData = collect($validated)->except('actions')->toArray();
            $recordData['condition_before_pct'] = $beforeVals->count() ? round($beforeVals->avg(), 1) : null;
            $recordData['condition_after_pct'] = $afterVals->count() ? round($afterVals->avg(), 1) : null;

            $recordData['is_unscheduled'] = (bool) ($recordData['is_unscheduled'] ?? false);
            if ($recordData['is_unscheduled']) {
                $recordData['schedule_id'] = null;
                $recordData['scheduled_period_date'] = null;
            } elseif (empty($recordData['schedule_id']) || empty($recordData['scheduled_period_date'])) {
                return response()->json(['error' => 'Pilih periode jadwal untuk laporan terjadwal.'], 422);
            }

            // Calculate lateness against the selected immutable schedule period,
            // respecting the admin-configured grace period after the due date (H+y).
            $isLate = false;
            if (!$recordData['is_unscheduled']) {
                $window = \App\Models\MaintenanceWindowSetting::current();
                $dueDate = Carbon::parse($recordData['scheduled_period_date'])->startOfDay();
                $lateAfterDate = $dueDate->copy()->addDays($window->days_after);
                $maintenanceDate = Carbon::parse($recordData['maintenance_date'])->startOfDay();
                $isLate = $maintenanceDate->greaterThan($lateAfterDate);
            }
            $recordData['is_late'] = $isLate;

            // Snapshot approval flow for this report so future flow changes only affect new reports
            $reporterRole = $authUser?->role ?? 'technician';
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
            $recordData['approval_flow_snapshot'] = $flowSteps->map(fn ($s) => [
                'id' => $s->id,
                'reporter_role' => $s->reporter_role,
                'role' => $s->role,
                'step_order' => $s->step_order,
                'is_active' => $s->is_active,
            ])->values()->toArray();

            $record = MaintenanceRecord::create($recordData);

            $machine = Machine::find($record->machine_id);
            $machineName = $machine ? $machine->name : 'Mesin';
            ActivityLog::log('Kirim Laporan', "Mengirimkan laporan maintenance untuk mesin: {$machineName} dengan status: " . strtoupper($record->status));

            if (!empty($validated['actions'])) {
                foreach ($validated['actions'] as $actionData) {
                    $indicatorValues = $actionData['indicator_values'] ?? [];
                    unset($actionData['indicator_values']);

                    // Auto-calculate condition_after_pct from indicator values if provided
                    if (!empty($indicatorValues)) {
                        $trueCount = collect($indicatorValues)->where('value', true)->count();
                        $totalCount = count($indicatorValues);
                        $actionData['condition_after_pct'] = $totalCount > 0
                            ? round(($trueCount / $totalCount) * 100, 1)
                            : ($actionData['condition_after_pct'] ?? null);
                    }

                    $action = $record->actions()->create($actionData);

                    foreach ($indicatorValues as $indicatorValue) {
                        $action->indicatorValues()->create($indicatorValue);
                    }

                    // NOTE: component and machine condition updates are deferred until approval
                }
            }

            // Reset unlock status if progress is now 100%
            $progress = $machine->maintenance_progress;
            if ($progress >= 100) {
                $machine->update([
                    'unlock_status' => 'none',
                    'unlock_expires_at' => null,
                ]);
            }

            DB::commit();

            return response()->json($record->load(['actions.component', 'actions.indicatorValues.indicator']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create record: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $record = MaintenanceRecord::with([
            'machine', 'technician', 'actions.component', 'actions.indicatorValues.indicator', 'approvals', 'schedule'
        ])->findOrFail($id);
        return response()->json($record);
    }
}
