<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\MachineComponent;
use App\Models\ComponentIndicator;
use App\Models\ActivityLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MachineComponentController extends Controller
{
    public function index($machineId)
    {
        $machine = Machine::findOrFail($machineId);
        return response()->json($machine->components()->with('indicators')->orderBy('category')->orderBy('name')->get());
    }

    public function allComponents(Request $request)
    {
        $query = MachineComponent::has('machine')->with(['machine', 'indicators']);

        if ($request->has('difficulty') && $request->difficulty !== 'all') {
            if ($request->difficulty === 'none') {
                $query->whereNull('difficulty');
            } else {
                $query->where('difficulty', $request->difficulty);
            }
        }

        if ($request->has('category') && $request->category !== 'all') {
            if ($request->category === 'none') {
                $query->whereNull('category')->orWhere('category', '');
            } else {
                $query->where('category', $request->category);
            }
        }

        return response()->json($query->orderBy('name')->get());
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
            'difficulty' => 'nullable|string|in:ringan,sedang,berat',
            'last_replaced_at' => 'nullable|date',
            'last_condition_pct' => 'nullable|numeric|min:0|max:100',
            'indicators' => 'nullable|array',
            'indicators.*.name' => 'required|string|max:255',
            'indicators.*.description' => 'nullable|string|max:500',
            'indicators.*.sort_order' => 'nullable|integer',
        ]);

        // Set default values if not provided
        if (!isset($validated['last_condition_pct'])) {
            $validated['last_condition_pct'] = 100;
        }

        $indicators = $validated['indicators'] ?? [];
        unset($validated['indicators']);

        $component = $machine->components()->create($validated);

        foreach ($indicators as $index => $indicator) {
            $component->indicators()->create([
                'name' => $indicator['name'],
                'description' => $indicator['description'] ?? null,
                'sort_order' => $indicator['sort_order'] ?? $index,
            ]);
        }

        $component->load('indicators');

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
            'components.*.difficulty' => 'nullable|string|in:ringan,sedang,berat',
            'components.*.last_condition_pct' => 'nullable|numeric|min:0|max:100',
            'components.*.indicators' => 'nullable|array',
            'components.*.indicators.*.name' => 'required|string|max:255',
            'components.*.indicators.*.description' => 'nullable|string|max:500',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $createdCount = 0;
            foreach ($request->components as $item) {
                $component = $machine->components()->create([
                    'category' => $item['category'] ?? null,
                    'name' => $item['name'],
                    'specification' => $item['specification'] ?? null,
                    'qty' => $item['qty'] ?? null,
                    'unit' => $item['unit'] ?? null,
                    'difficulty' => $item['difficulty'] ?? null,
                    'last_condition_pct' => $item['last_condition_pct'] ?? 100,
                ]);

                foreach ($item['indicators'] ?? [] as $index => $indicator) {
                    $component->indicators()->create([
                        'name' => $indicator['name'],
                        'description' => $indicator['description'] ?? null,
                        'sort_order' => $index,
                    ]);
                }

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
            'components.*.difficulty' => 'nullable|string|in:ringan,sedang,berat',
            'components.*.last_condition_pct' => 'nullable|numeric|min:0|max:100',
            'components.*.indicators' => 'nullable|array',
            'components.*.indicators.*.name' => 'required|string|max:255',
            'components.*.indicators.*.description' => 'nullable|string|max:500',
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
                $component = $machine->components()->create([
                    'category' => $item['category'] ?? null,
                    'name' => $item['name'],
                    'specification' => $item['specification'] ?? null,
                    'qty' => $item['qty'] ?? null,
                    'unit' => $item['unit'] ?? null,
                    'difficulty' => $item['difficulty'] ?? null,
                    'last_condition_pct' => $item['last_condition_pct'] ?? 100,
                ]);

                foreach ($item['indicators'] ?? [] as $index => $indicator) {
                    $component->indicators()->create([
                        'name' => $indicator['name'],
                        'description' => $indicator['description'] ?? null,
                        'sort_order' => $index,
                    ]);
                }

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
            'difficulty' => 'nullable|string|in:ringan,sedang,berat',
            'last_replaced_at' => 'nullable|date',
            'last_condition_pct' => 'nullable|numeric|min:0|max:100',
            'indicators' => 'nullable|array',
            'indicators.*.id' => 'nullable|uuid',
            'indicators.*.name' => 'required|string|max:255',
            'indicators.*.description' => 'nullable|string|max:500',
            'indicators.*.sort_order' => 'nullable|integer',
        ]);

        // Set default value if not provided
        if (!isset($validated['last_condition_pct'])) {
            $validated['last_condition_pct'] = 100;
        }

        $indicators = $validated['indicators'] ?? [];
        unset($validated['indicators']);

        $component->update($validated);

        $this->syncIndicators($component, $indicators);
        $component->load('indicators');

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

        $query = \App\Models\MaintenanceAction::with(['record.technician', 'record.approvals.approver', 'indicatorValues.indicator'])
            ->where('machine_component_id', $id)
            ->orderBy('created_at', 'desc');

        if ($request->has('month') && $request->month) {
            $query->whereMonth('created_at', $request->month);
        }
        if ($request->has('year') && $request->year) {
            $query->whereYear('created_at', $request->year);
        }

        $allFlowSteps = \App\Models\ApprovalFlowStep::active()->ordered()->get();

        $history = $query->get()->map(function ($action) use ($allFlowSteps) {
            $record = $action->record;
            $approvalState = $this->computeApprovalState($record, $allFlowSteps);
            $action->record->setAttribute('approval_state', $approvalState);
            $action->record->setAttribute('approval_status', $approvalState['approval_status'] ?? 'pending');
            $action->record->setAttribute('approval_notes', $approvalState['approval_notes'] ?? null);
            $action->record->setAttribute('approved_by', $approvalState['approved_by'] ?? null);
            $action->record->setAttribute('decided_at', $approvalState['decided_at'] ?? null);
            $action->record->setAttribute('pending_role', $approvalState['pending_role'] ?? null);
            $action->record->setAttribute('current_step', $approvalState['current_step'] ?? 1);
            $action->record->setAttribute('completed_steps', $approvalState['completed_steps'] ?? 0);
            $action->record->setAttribute('total_steps', $approvalState['total_steps'] ?? 0);
            return $action;
        });

        return response()->json([
            'component' => $component,
            'history' => $history,
        ]);
    }

    private function syncIndicators($component, $indicators)
    {
        if (empty($indicators)) {
            $component->indicators()->delete();
            return;
        }

        $existingIds = $component->indicators()->pluck('id')->toArray();
        $keptIds = [];

        foreach ($indicators as $index => $indicator) {
            $data = [
                'name' => $indicator['name'],
                'description' => $indicator['description'] ?? null,
                'sort_order' => $indicator['sort_order'] ?? $index,
            ];

            if (!empty($indicator['id']) && in_array($indicator['id'], $existingIds)) {
                $component->indicators()->where('id', $indicator['id'])->update($data);
                $keptIds[] = $indicator['id'];
            } else {
                $created = $component->indicators()->create($data);
                $keptIds[] = $created->id;
            }
        }

        $component->indicators()->whereNotIn('id', $keptIds)->delete();
    }

    public function bulkImportIndicators(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.machine_code' => 'required|string',
            'items.*.component_name' => 'required|string',
            'items.*.indicators' => 'required|array',
            'items.*.indicators.*.name' => 'required|string|max:255',
            'items.*.indicators.*.description' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $machineCodes = collect($request->items)->pluck('machine_code')->unique()->toArray();
            $machines = Machine::whereIn('kode', $machineCodes)->get()->keyBy('kode');

            $missingCodes = [];
            foreach ($machineCodes as $code) {
                if (!$machines->has($code)) {
                    $missingCodes[] = $code;
                }
            }
            if (!empty($missingCodes)) {
                return response()->json([
                    'message' => 'Kode mesin tidak ditemukan: ' . implode(', ', $missingCodes),
                    'missing_codes' => $missingCodes,
                ], 422);
            }

            $componentNamesByMachine = collect($request->items)->groupBy('machine_code')->map(function ($group) {
                return $group->pluck('component_name')->unique()->toArray();
            });

            $componentsByKey = [];
            foreach ($machines as $code => $machine) {
                $names = $componentNamesByMachine[$code] ?? [];
                $machineComponents = $machine->components()
                    ->whereIn('name', $names)
                    ->with('indicators')
                    ->get()
                    ->keyBy('name');
                foreach ($machineComponents as $name => $component) {
                    $componentsByKey[$code . '|' . $name] = $component;
                }
            }

            $missingComponents = [];
            $importedCount = 0;

            foreach ($request->items as $item) {
                $key = $item['machine_code'] . '|' . $item['component_name'];
                if (!isset($componentsByKey[$key])) {
                    $missingComponents[] = $item['machine_code'] . ' - ' . $item['component_name'];
                    continue;
                }

                $component = $componentsByKey[$key];
                $this->syncIndicators($component, $item['indicators']);
                $importedCount += count($item['indicators']);
            }

            DB::commit();

            return response()->json([
                'message' => "Berhasil mengimpor {$importedCount} indikator.",
                'count' => $importedCount,
                'missing_components' => array_unique($missingComponents),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mengimpor indikator: ' . $e->getMessage()], 500);
        }
    }

    private function computeApprovalState($record, $allFlowSteps)
    {
        if (!$record) {
            return ['status' => 'pending', 'pending_role' => null];
        }

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
            $steps = $allFlowSteps->filter(fn ($s) => $s->reporter_role === $reporterRole)
                ->sortBy('step_order')
                ->values();

            if ($steps->isEmpty() && $reporterRole !== 'technician') {
                $steps = $allFlowSteps->filter(fn ($s) => $s->reporter_role === 'technician')
                    ->sortBy('step_order')
                    ->values();
            }
        }

        $totalSteps = $steps->count();
        $decisions = $record->approvals->sortBy('step_order');

        $stepRole = fn ($step) => $step ? (is_array($step) ? ($step['role'] ?? null) : $step->role) : null;

        // Map flow steps to include role display_name
        $roleDisplayMap = Role::pluck('display_name', 'name')->toArray();
        $steps = $steps->map(function ($step) use ($roleDisplayMap) {
            $roleName = is_array($step) ? ($step['role'] ?? null) : $step->role;
            $stepData = is_array($step) ? $step : $step->toArray();
            $stepData['role_display'] = $roleDisplayMap[$roleName] ?? $roleName;
            return $stepData;
        });

        $approvalTrail = $decisions->map(function ($approval) use ($steps, $stepRole, $roleDisplayMap) {
            $step = $steps->firstWhere('step_order', $approval->step_order);
            $roleName = $stepRole($step) ?? $approval->approver?->role;
            return [
                'step_order' => $approval->step_order,
                'role' => $roleName,
                'role_display' => $roleDisplayMap[$roleName] ?? $roleName,
                'decision' => $approval->decision,
                'approver' => $approval->approver?->full_name ?? '-',
                'notes' => $approval->notes,
                'decided_at' => $approval->decided_at,
            ];
        })->values();

        $rejected = $decisions->firstWhere('decision', 'rejected');
        if ($rejected) {
            return [
                'approval_status' => 'rejected',
                'approval_notes' => $rejected->notes,
                'approved_by' => $rejected->approver?->full_name,
                'decided_at' => $rejected->decided_at,
                'pending_role' => null,
                'pending_role_display' => null,
                'current_step' => $rejected->step_order,
                'completed_steps' => $decisions->where('decision', 'approved')->count(),
                'total_steps' => $totalSteps,
                'approvals' => $approvalTrail,
                'flow_steps' => $steps,
            ];
        }

        $approvedCount = $decisions->where('decision', 'approved')->count();
        if ($approvedCount >= $totalSteps && $totalSteps > 0) {
            $last = $decisions->where('decision', 'approved')->sortByDesc('step_order')->first();
            return [
                'approval_status' => 'approved',
                'approval_notes' => $last?->notes,
                'approved_by' => $last?->approver?->full_name,
                'decided_at' => $last?->decided_at,
                'pending_role' => null,
                'pending_role_display' => null,
                'current_step' => $totalSteps,
                'completed_steps' => $approvedCount,
                'total_steps' => $totalSteps,
                'approvals' => $approvalTrail,
                'flow_steps' => $steps,
            ];
        }

        $currentStep = $approvedCount + 1;
        $pendingStep = $steps->firstWhere('step_order', $currentStep);
        $pendingRole = $stepRole($pendingStep);
        $pendingRoleDisplay = is_array($pendingStep) ? ($pendingStep['role_display'] ?? null) : null;

        return [
            'approval_status' => 'pending',
            'approval_notes' => null,
            'approved_by' => null,
            'decided_at' => null,
            'pending_role' => $pendingRole,
            'pending_role_display' => $pendingRoleDisplay,
            'current_step' => $currentStep,
            'completed_steps' => $approvedCount,
            'total_steps' => $totalSteps,
            'approvals' => $approvalTrail,
            'flow_steps' => $steps,
        ];
    }
}
