<?php

namespace App\Http\Controllers;

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

        if ($request->has('qty')) {
            $request->merge(['qty' => $request->qty !== null ? (string)$request->qty : null]);
        }

        $validated = $request->validate([
            'category' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'specification' => 'nullable|string|max:500',
            'qty' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:50',
            'last_replaced_at' => 'nullable|date',
            'last_condition_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        // Set default values if not provided
        if (!isset($validated['last_condition_pct'])) {
            $validated['last_condition_pct'] = 100;
        }

        $component = $machine->components()->create($validated);

        ActivityLog::log('Tambah Komponen', "Menambahkan komponen baru: {$component->name} ({$component->category}) pada mesin: {$machine->name}");

        return response()->json($component, 201);
    }

    public function bulkStore(Request $request, $machineId)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $machine = Machine::findOrFail($machineId);

        // Cast qty ke string sebelum validasi
        $components = $request->input('components', []);
        foreach ($components as $key => $item) {
            if (array_key_exists('qty', $item)) {
                $components[$key]['qty'] = $item['qty'] !== null ? (string)$item['qty'] : null;
            }
        }
        $request->merge(['components' => $components]);

        $request->validate([
            'components' => 'required|array',
            'components.*.category' => 'nullable|string|max:100',
            'components.*.name' => 'required|string|max:255',
            'components.*.specification' => 'nullable|string|max:500',
            'components.*.qty' => 'nullable|string|max:50',
            'components.*.unit' => 'nullable|string|max:50',
            'components.*.last_condition_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $createdCount = 0;
            foreach ($request->components as $item) {
                $machine->components()->create([
                    'category' => $item['category'] ?? null,
                    'name' => $item['name'],
                    'specification' => $item['specification'] ?? null,
                    'qty' => $item['qty'] ?? null,
                    'unit' => $item['unit'] ?? null,
                    'last_condition_pct' => $item['last_condition_pct'] ?? 100,
                ]);
                $createdCount++;
            }

            ActivityLog::log('Import Komponen', "Mengimpor {$createdCount} komponen baru untuk mesin: {$machine->name} melalui Excel");
            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => "Berhasil mengimpor {$createdCount} komponen.",
                'count' => $createdCount
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal mengimpor komponen: ' . $e->getMessage()], 500);
        }
    }

    public function bulkStoreGlobal(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Cast qty ke string sebelum validasi
        $components = $request->input('components', []);
        foreach ($components as $key => $item) {
            if (array_key_exists('qty', $item)) {
                $components[$key]['qty'] = $item['qty'] !== null ? (string)$item['qty'] : null;
            }
        }
        $request->merge(['components' => $components]);

        $request->validate([
            'components' => 'required|array',
            'components.*.machine_code' => 'required|string',
            'components.*.category' => 'nullable|string|max:100',
            'components.*.name' => 'required|string|max:255',
            'components.*.specification' => 'nullable|string|max:500',
            'components.*.qty' => 'nullable|string|max:50',
            'components.*.unit' => 'nullable|string|max:50',
            'components.*.last_condition_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        // Validate all machine codes exist first
        $machineCodes = collect($request->components)->pluck('machine_code')->unique()->toArray();
        $existingMachines = Machine::whereIn('kode', $machineCodes)->get()->keyBy('kode');

        $missingCodes = [];
        foreach ($machineCodes as $code) {
            if (!$existingMachines->has($code)) {
                $missingCodes[] = $code;
            }
        }

        if (!empty($missingCodes)) {
            return response()->json([
                'message' => 'Gagal mengimpor. Kode mesin berikut tidak ditemukan di database: ' . implode(', ', $missingCodes),
                'missing_codes' => $missingCodes
            ], 422);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $createdCount = 0;
            foreach ($request->components as $item) {
                $machine = $existingMachines->get($item['machine_code']);
                $machine->components()->create([
                    'category' => $item['category'] ?? null,
                    'name' => $item['name'],
                    'specification' => $item['specification'] ?? null,
                    'qty' => $item['qty'] ?? null,
                    'unit' => $item['unit'] ?? null,
                    'last_condition_pct' => $item['last_condition_pct'] ?? 100,
                ]);
                $createdCount++;
            }

            ActivityLog::log('Import Komponen', "Mengimpor {$createdCount} komponen berdasarkan kode mesin melalui Excel");
            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => "Berhasil mengimpor {$createdCount} komponen untuk berbagai mesin.",
                'count' => $createdCount
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal mengimpor komponen: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $component = MachineComponent::findOrFail($id);

        if ($request->has('qty')) {
            $request->merge(['qty' => $request->qty !== null ? (string)$request->qty : null]);
        }

        $validated = $request->validate([
            'category' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'specification' => 'nullable|string|max:500',
            'qty' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:50',
            'last_replaced_at' => 'nullable|date',
            'last_condition_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        // Set default value if not provided
        if (!isset($validated['last_condition_pct'])) {
            $validated['last_condition_pct'] = 100;
        }

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

        $query = \App\Models\MaintenanceAction::with(['record.technician', 'record.approval'])
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
