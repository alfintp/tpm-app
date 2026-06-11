<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceAction;
use App\Models\Machine;
use App\Models\MachineComponent;
use App\Models\MaintenanceSchedule;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaintenanceRecordController extends Controller
{
    public function index()
    {
        $records = MaintenanceRecord::with(['machine', 'technician', 'actions.component', 'approval'])->get();
        return response()->json($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'technician_id' => 'required|exists:users,id',
            'schedule_id' => 'nullable|exists:maintenance_schedules,id',
            'maintenance_date' => 'required|date',
            'notes' => 'nullable|string',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'actions' => 'nullable|array',
            'actions.*.action_type' => 'required|in:repair,replace,inspect,clean,lubricate',
            'actions.*.machine_component_id' => 'required|exists:machine_components,id',
            'actions.*.condition_before_pct' => 'nullable|numeric|min:0|max:100',
            'actions.*.condition_after_pct' => 'nullable|numeric|min:0|max:100',
            'actions.*.description' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Calculate overall before/after from actions
            $beforeVals = collect($validated['actions'] ?? [])->pluck('condition_before_pct')->filter();
            $afterVals = collect($validated['actions'] ?? [])->pluck('condition_after_pct')->filter();

            $recordData = collect($validated)->except('actions')->toArray();
            $recordData['condition_before_pct'] = $beforeVals->count() ? round($beforeVals->avg(), 1) : null;
            $recordData['condition_after_pct'] = $afterVals->count() ? round($afterVals->avg(), 1) : null;

            $record = MaintenanceRecord::create($recordData);

            $machine = Machine::find($record->machine_id);
            $machineName = $machine ? $machine->name : 'Mesin';
            ActivityLog::log('Kirim Laporan', "Mengirimkan laporan maintenance untuk mesin: {$machineName} dengan status: " . strtoupper($record->status));

            if (!empty($validated['actions'])) {
                foreach ($validated['actions'] as $actionData) {
                    $record->actions()->create($actionData);

                    // Update component's last condition
                    if (!empty($actionData['condition_after_pct'])) {
                        $component = MachineComponent::find($actionData['machine_component_id']);
                        if ($component) {
                            $component->update([
                                'last_condition_pct' => $actionData['condition_after_pct'],
                                'last_replaced_at' => in_array($actionData['action_type'], ['replace']) ? Carbon::parse($validated['maintenance_date']) : $component->last_replaced_at,
                            ]);
                            // This triggers the booted observer to update machine avg
                        }
                    }
                }
            }

            // Update machine status if completed
            if ($record->status === 'completed') {
                $machine = Machine::find($record->machine_id);
                if ($machine) {
                    $machine->update(['status' => 'active']);
                }

                // Update schedule next_due_date
                if ($record->schedule_id) {
                    $schedule = MaintenanceSchedule::find($record->schedule_id);
                    if ($schedule) {
                        $schedule->update([
                            'next_due_date' => Carbon::parse($record->maintenance_date)->addDays($schedule->interval_days)
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json($record->load(['actions.component']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create record: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $record = MaintenanceRecord::with(['machine', 'technician', 'actions.component', 'approval', 'schedule'])->findOrFail($id);
        return response()->json($record);
    }
}
