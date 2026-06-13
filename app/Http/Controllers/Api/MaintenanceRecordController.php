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
            'technician_id' => 'nullable|exists:users,id',
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

        // Always use the authenticated user as the technician
        if ($request->user()) {
            $validated['technician_id'] = $request->user()->id;
        }

        // City-based access check for technicians
        $authUser = $request->user();
        if ($authUser && $authUser->role === 'technician' && ($authUser->city ?? 'both') !== 'both') {
            $machine = Machine::find($validated['machine_id']);
            if ($machine && $machine->kota !== $authUser->city) {
                return response()->json([
                    'error' => 'Akses ditolak. Anda tidak dapat membuat laporan untuk mesin di luar kota yang ditugaskan kepada Anda.'
                ], 403);
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

            $record = MaintenanceRecord::create($recordData);

            $machine = Machine::find($record->machine_id);
            $machineName = $machine ? $machine->name : 'Mesin';
            ActivityLog::log('Kirim Laporan', "Mengirimkan laporan maintenance untuk mesin: {$machineName} dengan status: " . strtoupper($record->status));

            if (!empty($validated['actions'])) {
                foreach ($validated['actions'] as $actionData) {
                    $record->actions()->create($actionData);
                    // NOTE: component and machine condition updates are deferred until approval
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
