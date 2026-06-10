<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function index()
    {
        $machines = Machine::with(['schedules', 'components', 'picMesin'])->get();
        return response()->json($machines);
    }

    public function show($id)
    {
        $machine = Machine::with(['schedules', 'components', 'records.actions.component', 'records.technician', 'records.approval', 'picMesin'])->findOrFail($id);
        return response()->json($machine);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition_pct' => 'required|numeric|min:0|max:100',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,maintenance',
            'pic_mesin_id' => 'nullable|uuid|exists:users,id',
            'maintenance_duration' => 'nullable|integer|min:1',
            'maintenance_start_date' => 'nullable|date',
        ]);

        $machine = Machine::create($validated);
        return response()->json($machine, 201);
    }

    public function update(Request $request, $id)
    {
        $machine = Machine::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'condition_pct' => 'sometimes|required|numeric|min:0|max:100',
            'location' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:active,inactive,maintenance',
            'pic_mesin_id' => 'nullable|uuid|exists:users,id',
            'maintenance_duration' => 'nullable|integer|min:1',
            'maintenance_start_date' => 'nullable|date',
        ]);

        $machine->update($validated);
        return response()->json($machine);
    }

    public function destroy($id)
    {
        $machine = Machine::findOrFail($id);
        $machine->delete();
        return response()->json(null, 204);
    }

    public function getUsers()
    {
        $users = User::select('id', 'full_name', 'role')->get();
        return response()->json($users);
    }
}
