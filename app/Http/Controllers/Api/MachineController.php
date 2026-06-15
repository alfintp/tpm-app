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
    public function index(Request $request)
    {
        $authUser = auth('sanctum')->user();

        $query = Machine::with(['schedules', 'components', 'picMesin', 'records.actions']);

        // Technicians can only see machines in their assigned city (unless city is 'both')
        if ($authUser && $authUser->role === 'technician' && isset($authUser->city) && $authUser->city !== 'both') {
            $query->where('kota', $authUser->city);
        }

        return response()->json($query->get());
    }

    /**
     * Check if the authenticated user is allowed to access a machine in a given city.
     * Admins/managers have full access. Technicians are restricted by their city.
     */
    private function canAccessMachineCity(?string $machineKota): bool
    {
        $user = auth('sanctum')->user();
        if (!$user || in_array($user->role, ['admin', 'manager'])) {
            return true;
        }
        // Technician: city 'both' means unrestricted
        if ($user->city === 'both') {
            return true;
        }
        return $user->city === $machineKota;
    }

    public function show(Request $request, $id)
    {
        $machine = Machine::with(['schedules', 'components', 'records.actions.component', 'records.technician', 'records.approval', 'picMesin'])->findOrFail($id);

        if (!$this->canAccessMachineCity($machine->kota)) {
            return response()->json(['message' => 'Akses ditolak. Mesin ini berada di luar kota yang ditugaskan kepada Anda.'], 403);
        }

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
            'kode' => 'required|string|max:100|unique:machines,kode',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition_pct' => 'required|numeric|min:0|max:100',
            'location' => 'nullable|string|max:255',
            'kota' => 'required|string|in:pasuruan,sby',
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

    public function bulkStore(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'machines' => 'required|array',
            'machines.*.kode' => 'required|string|max:100',
            'machines.*.name' => 'required|string|max:255',
            'machines.*.description' => 'nullable|string',
            'machines.*.condition_pct' => 'required|numeric|min:0|max:100',
            'machines.*.location' => 'nullable|string|max:255',
            'machines.*.kota' => 'required|string|in:pasuruan,sby',
            'machines.*.status' => 'required|in:active,inactive,maintenance',
            'machines.*.pic_email' => 'nullable|string|email',
            'machines.*.maintenance_duration' => 'nullable|integer|min:1',
            'machines.*.maintenance_start_date' => 'nullable|date',
        ]);

        $kodes = collect($request->machines)->pluck('kode')->toArray();
        // Check for duplicates in the uploaded array
        if (count($kodes) !== count(array_unique($kodes))) {
            return response()->json(['message' => 'Gagal mengimpor. Ada kode mesin ganda di dalam file Excel.'], 422);
        }

        // Check if any code already exists in DB
        $existingCodes = Machine::whereIn('kode', $kodes)->pluck('kode')->toArray();
        if (!empty($existingCodes)) {
            return response()->json([
                'message' => 'Gagal mengimpor. Kode mesin berikut sudah terdaftar di database: ' . implode(', ', $existingCodes),
                'existing_codes' => $existingCodes
            ], 422);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $createdCount = 0;
            foreach ($request->machines as $item) {
                // Find PIC by email
                $picId = null;
                if (!empty($item['pic_email'])) {
                    $picUser = User::where('email', $item['pic_email'])->first();
                    if ($picUser) {
                        $picId = $picUser->id;
                    }
                }

                $machine = Machine::create([
                    'kode' => $item['kode'],
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'condition_pct' => $item['condition_pct'],
                    'location' => $item['location'] ?? null,
                    'kota' => $item['kota'],
                    'status' => $item['status'],
                    'pic_mesin_id' => $picId,
                    'maintenance_duration' => $item['maintenance_duration'] ?? null,
                    'maintenance_start_date' => $item['maintenance_start_date'] ?? null,
                ]);

                // Automatically create maintenance schedule if duration and start date are provided
                if (!empty($item['maintenance_duration']) && !empty($item['maintenance_start_date'])) {
                    MaintenanceSchedule::create([
                        'machine_id' => $machine->id,
                        'schedule_type' => 'preventive',
                        'interval_days' => $item['maintenance_duration'],
                        'next_due_date' => $item['maintenance_start_date'],
                        'status' => 'pending',
                    ]);
                }
                $createdCount++;
            }

            ActivityLog::log('Import Mesin', "Mengimpor {$createdCount} data mesin baru melalui Excel");
            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => "Berhasil mengimpor {$createdCount} mesin.",
                'count' => $createdCount
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal mengimpor data: ' . $e->getMessage()], 500);
        }
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
            'kode' => 'sometimes|required|string|max:100|unique:machines,kode,' . $machine->id,
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'condition_pct' => 'sometimes|required|numeric|min:0|max:100',
            'location' => 'nullable|string|max:255',
            'kota' => 'sometimes|required|string|in:pasuruan,sby',
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

    public function checkKode(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|max:100',
        ]);

        $exists = Machine::where('kode', $request->kode)->exists();

        return response()->json([
            'exists' => $exists,
            'kode' => $request->kode,
        ]);
    }
}
