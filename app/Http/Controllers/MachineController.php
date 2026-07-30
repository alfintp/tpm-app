<?php

namespace App\Http\Controllers;

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

        $query = Machine::with(['schedules.occurrences', 'components.indicators', 'picMesin', 'records.actions.indicatorValues']);

        // Filter machines by user's assigned city (unless city is 'both')
        if ($authUser && isset($authUser->city) && $authUser->city !== 'both') {
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
        if (!$user) {
            return true;
        }
        // city 'both' means unrestricted
        if (($user->city ?? 'both') === 'both') {
            return true;
        }
        return $user->city === $machineKota;
    }

    public function show(Request $request, $id)
    {
        $machine = Machine::with([
            'schedules.occurrences',
            'components.indicators',
            'records.actions.component.indicators',
            'records.actions.indicatorValues',
            'records.technician',
            'records.approvals.approver',
            'records.latestApproval',
            'records.machine',
            'picMesin'
        ])->findOrFail($id);

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
            'is_locked' => 'nullable|boolean',
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

        // Continue the import-order counter per city so newly imported rows are
        // appended after whatever machines already exist for that city.
        $counters = Machine::selectRaw('kota, MAX(import_order) as max_order')
            ->groupBy('kota')
            ->pluck('max_order', 'kota')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $createdCount = 0;
            $affectedCities = [];
            foreach ($request->machines as $item) {
                // Find PIC by email
                $picId = null;
                if (!empty($item['pic_email'])) {
                    $picUser = User::where('email', $item['pic_email'])->first();
                    if ($picUser) {
                        $picId = $picUser->id;
                    }
                }

                $counters[$item['kota']] = ($counters[$item['kota']] ?? 0) + 1;
                $affectedCities[$item['kota']] = true;

                $machine = Machine::create([
                    'kode' => $item['kode'],
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'condition_pct' => $item['condition_pct'],
                    'location' => $item['location'] ?? null,
                    'kota' => $item['kota'],
                    'import_order' => $counters[$item['kota']],
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

            $generator = app(\App\Services\ScheduleOccurrenceGenerator::class);
            foreach (array_keys($affectedCities) as $kota) {
                $generator->generateUpcomingForCity($kota);
            }

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
            'is_locked' => 'nullable|boolean',
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

    public function requestUnlock(Request $request, $id)
    {
        $machine = Machine::findOrFail($id);
        
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'requested_period' => 'nullable|string|max:100',
        ]);

        $machine->update([
            'unlock_status' => 'pending',
            'unlock_reason' => $validated['reason'],
            'unlock_requested_period' => $validated['requested_period'] ?? null,
            'unlock_requested_by_id' => $request->user()->id,
            'last_unlock_request_at' => now(),
        ]);

        $periodInfo = $validated['requested_period'] ? " untuk jadwal: {$validated['requested_period']}" : "";
        ActivityLog::log('Pengajuan Unlock', "Mengajukan buka kunci untuk mesin: {$machine->name}{$periodInfo} dengan alasan: {$validated['reason']}");

        return response()->json([
            'message' => 'Pengajuan buka kunci berhasil dikirim. Menunggu persetujuan Factory Manager.',
            'machine' => $machine
        ]);
    }

    public function approveUnlock(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin' && !$user->is_manager) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $machine = Machine::findOrFail($id);
        
        $validated = $request->validate([
            'decision' => 'required|in:approved,rejected',
        ]);

        if ($validated['decision'] === 'approved') {
            $machine->update([
                'unlock_status' => 'approved',
                'unlock_expires_at' => now()->addHours(24), // Unlock valid for 24 hours
                'is_locked' => false,
            ]);
            $action = 'Menyetujui';
        } else {
            $machine->update([
                'unlock_status' => 'rejected',
                'unlock_expires_at' => null,
            ]);
            $action = 'Menolak';
        }

        ActivityLog::log('Persetujuan Unlock', "{$action} pengajuan buka kunci untuk mesin: {$machine->name}");

        return response()->json([
            'message' => "Pengajuan buka kunci berhasil di-{$validated['decision']}.",
            'machine' => $machine
        ]);
    }

    public function unlockHistory(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin' && !$user->is_manager) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $machines = Machine::with('unlockRequester')
            ->whereNotIn('unlock_status', ['none'])
            ->whereNotNull('last_unlock_request_at')
            ->orderByDesc('last_unlock_request_at')
            ->get()
            ->map(function ($m) {
                return [
                    'id'                       => $m->id,
                    'name'                     => $m->name,
                    'kode'                     => $m->kode,
                    'location'                 => $m->location,
                    'kota'                     => $m->kota,
                    'unlock_status'            => $m->unlock_status,
                    'unlock_status_label'      => $m->unlock_status_label,
                    'unlock_reason'            => $m->unlock_reason,
                    'unlock_requested_period'  => $m->unlock_requested_period,
                    'last_unlock_request_at'   => $m->last_unlock_request_at,
                    'unlock_expires_at'        => $m->unlock_expires_at,
                    'unlock_requested_by_id'   => $m->unlock_requested_by_id,
                    'requester_name'           => $m->unlockRequester?->name ?? '-',
                    'requester_role'           => $m->unlockRequester?->role ?? '-',
                ];
            });

        return response()->json($machines);
    }
}
