<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\User;
use App\Models\MaintenanceSchedule;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function index()
    {
        $machines = Machine::with(['schedules', 'components', 'picMesin', 'records.actions'])->get();
        return response()->json($machines);
    }

    public function show($id)
    {
        $machine = Machine::with(['schedules', 'components', 'records.actions.component', 'records.technician', 'records.approval', 'picMesin'])->findOrFail($id);
        return response()->json($machine);
    }

    public function store(Request $request)
    {
        // Convert empty strings to null
        $input = $request->all();
        foreach (['pic_mesin_id', 'maintenance_duration', 'maintenance_start_date'] as $field) {
            if (isset($input[$field]) && $input[$field] === '') {
                $input[$field] = null;
            }
        }

        $validated = validator($input, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition_pct' => 'required|numeric|min:0|max:100',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,maintenance',
            'pic_mesin_id' => 'nullable|uuid|exists:users,id',
            'maintenance_duration' => 'nullable|integer|min:1',
            'maintenance_start_date' => 'nullable|date',
        ])->validate();

        $machine = Machine::create($validated);

        ActivityLog::log('Tambah Mesin', "Menambahkan mesin baru: {$machine->name} di lokasi: " . ($machine->location ?? '-'));

        // Automatically create maintenance schedule if duration and start date are provided
        if (!empty($validated['maintenance_duration']) && !empty($validated['maintenance_start_date'])) {
            MaintenanceSchedule::create([
                'machine_id' => $machine->id,
                'schedule_type' => 'preventive',
                'interval_days' => $validated['maintenance_duration'],
                'next_due_date' => $validated['maintenance_start_date'],
                'status' => 'pending',
            ]);
        }

        return response()->json($machine, 201);
    }

    public function update(Request $request, $id)
    {
        $machine = Machine::findOrFail($id);

        // Convert empty strings to null
        $input = $request->all();
        foreach (['pic_mesin_id', 'maintenance_duration', 'maintenance_start_date'] as $field) {
            if (isset($input[$field]) && $input[$field] === '') {
                $input[$field] = null;
            }
        }

        $validated = validator($input, [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'condition_pct' => 'sometimes|required|numeric|min:0|max:100',
            'location' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:active,inactive,maintenance',
            'pic_mesin_id' => 'nullable|uuid|exists:users,id',
            'maintenance_duration' => 'nullable|integer|min:1',
            'maintenance_start_date' => 'nullable|date',
        ])->validate();

        $machine->update($validated);

        ActivityLog::log('Edit Mesin', "Memperbarui data mesin: {$machine->name}");

        // Automatically update/create/delete maintenance schedule if duration and start date are modified
        if (!empty($validated['maintenance_duration']) && !empty($validated['maintenance_start_date'])) {
            $schedule = MaintenanceSchedule::where('machine_id', $machine->id)
                ->where('schedule_type', 'preventive')
                ->first();

            if ($schedule) {
                $schedule->update([
                    'interval_days' => $validated['maintenance_duration'],
                    'next_due_date' => $validated['maintenance_start_date'],
                ]);
            } else {
                MaintenanceSchedule::create([
                    'machine_id' => $machine->id,
                    'schedule_type' => 'preventive',
                    'interval_days' => $validated['maintenance_duration'],
                    'next_due_date' => $validated['maintenance_start_date'],
                    'status' => 'pending',
                ]);
            }
        } else if (array_key_exists('maintenance_duration', $validated) || array_key_exists('maintenance_start_date', $validated)) {
            // If explicitly set to null/empty, we can remove the preventive schedule
            MaintenanceSchedule::where('machine_id', $machine->id)
                ->where('schedule_type', 'preventive')
                ->delete();
        }

        return response()->json($machine);
    }

    public function destroy($id)
    {
        $machine = Machine::findOrFail($id);
        
        ActivityLog::log('Hapus Mesin', "Menghapus mesin: {$machine->name}");

        $machine->delete();
        return response()->json(null, 204);
    }

    public function getUsers()
    {
        $users = User::select('id', 'full_name', 'role')->get();
        return response()->json($users);
    }
}
