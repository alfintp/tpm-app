<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\MachineComponent;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class MachineComponentController extends Controller
{
    public function index($machineId)
    {
        $machine = Machine::findOrFail($machineId);
        return response()->json($machine->components()->orderBy('category')->orderBy('name')->get());
    }

    public function store(Request $request, $machineId)
    {
        $machine = Machine::findOrFail($machineId);

        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'specification' => 'nullable|string|max:500',
            'qty' => 'required|integer|min:1',
            'unit' => 'required|string|max:50',
            'last_replaced_at' => 'nullable|date',
            'last_condition_pct' => 'required|numeric|min:0|max:100',
            'maintenance_schedule' => 'nullable|string|max:50',
        ]);

        $component = $machine->components()->create($validated);

        ActivityLog::log('Tambah Komponen', "Menambahkan komponen baru: {$component->name} ({$component->category}) pada mesin: {$machine->name}");

        return response()->json($component, 201);
    }

    public function update(Request $request, $id)
    {
        $component = MachineComponent::findOrFail($id);

        $validated = $request->validate([
            'category' => 'sometimes|required|string|max:100',
            'name' => 'sometimes|required|string|max:255',
            'specification' => 'nullable|string|max:500',
            'qty' => 'sometimes|required|integer|min:1',
            'unit' => 'sometimes|required|string|max:50',
            'last_replaced_at' => 'nullable|date',
            'last_condition_pct' => 'sometimes|required|numeric|min:0|max:100',
            'maintenance_schedule' => 'nullable|string|max:50',
        ]);

        $component->update($validated);

        $machineName = $component->machine ? $component->machine->name : 'Mesin';
        ActivityLog::log('Edit Komponen', "Memperbarui komponen: {$component->name} ({$component->category}) pada mesin: {$machineName}");

        return response()->json($component);
    }

    public function destroy($id)
    {
        $component = MachineComponent::findOrFail($id);
        $machineName = $component->machine ? $component->machine->name : 'Mesin';
        
        ActivityLog::log('Hapus Komponen', "Menghapus komponen: {$component->name} ({$component->category}) dari mesin: {$machineName}");

        $component->delete();

        return response()->json(['message' => 'Component deleted']);
    }

    public function history($id, Request $request)
    {
        $component = MachineComponent::with(['machine'])->findOrFail($id);

        $query = \App\Models\MaintenanceAction::with(['record.technician'])
            ->where('machine_component_id', $id)
            ->orderBy('created_at', 'desc');

        if ($request->has('month') && $request->month) {
            $query->whereMonth('created_at', $request->month);
        }
        if ($request->has('year') && $request->year) {
            $query->whereYear('created_at', $request->year);
        }

        $history = $query->get();

        return response()->json([
            'component' => $component,
            'history' => $history,
        ]);
    }
}
