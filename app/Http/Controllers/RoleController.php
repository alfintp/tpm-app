<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(Role::orderBy('display_name')->get()->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'display_name' => $r->display_name,
            'can_approve' => $r->can_approve,
            'can_report' => $r->can_report,
            'is_manager' => $r->is_manager,
            'can_approve_unlock' => $r->can_approve_unlock,
            'can_add_data' => $r->can_add_data,
            'can_delete_data' => $r->can_delete_data,
            'required_difficulties' => $r->required_difficulties ?? [],
            'is_active' => $r->is_active,
        ]));
    }

    public function store(Request $request)
    {
        $authUser = $request->user();
        if (!$authUser || $authUser->role !== 'admin') {
            return response()->json(['message' => 'Hanya admin yang dapat mengelola role.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50|alpha_dash|unique:roles,name',
            'display_name' => 'required|string|max:100',
            'can_approve' => 'required|boolean',
            'can_report' => 'required|boolean',
            'can_approve_unlock' => 'nullable|boolean',
            'can_add_data' => 'nullable|boolean',
            'can_delete_data' => 'nullable|boolean',
            'required_difficulties' => 'nullable|array',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'can_approve' => $validated['can_approve'],
            'can_report' => $validated['can_report'],
            'can_approve_unlock' => $validated['can_approve_unlock'] ?? false,
            'can_add_data' => $validated['can_add_data'] ?? false,
            'can_delete_data' => $validated['can_delete_data'] ?? false,
            'required_difficulties' => $this->sanitizeDifficulties($validated['required_difficulties'] ?? []),
            'is_active' => true,
        ]);

        return response()->json($role, 201);
    }

    public function update(Request $request, $id)
    {
        $authUser = $request->user();
        if (!$authUser || $authUser->role !== 'admin') {
            return response()->json(['message' => 'Hanya admin yang dapat mengelola role.'], 403);
        }

        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('roles')->ignore($role->id)],
            'display_name' => 'required|string|max:100',
            'can_approve' => 'required|boolean',
            'can_report' => 'required|boolean',
            'required_difficulties' => 'nullable|array',
            'is_active' => 'required|boolean',
        ]);

        $validated['required_difficulties'] = $this->sanitizeDifficulties($validated['required_difficulties'] ?? []);

        $role->update($validated);

        return response()->json($role);
    }

    public function bulkUpdate(Request $request)
    {
        $authUser = $request->user();
        if (!$authUser || $authUser->role !== 'admin') {
            return response()->json(['message' => 'Hanya admin yang dapat mengelola role.'], 403);
        }

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*.id' => 'required|string|exists:roles,id',
            'roles.*.name' => 'required|string|max:50|alpha_dash',
            'roles.*.display_name' => 'required|string|max:100',
            'roles.*.can_approve' => 'required|boolean',
            'roles.*.can_report' => 'required|boolean',
            'roles.*.can_approve_unlock' => 'nullable|boolean',
            'roles.*.can_add_data' => 'nullable|boolean',
            'roles.*.can_delete_data' => 'nullable|boolean',
            'roles.*.required_difficulties' => 'nullable|array',
        ]);

        $systemRoles = ['admin', 'technician'];
        $submittedNames = [];

        foreach ($validated['roles'] as $roleData) {
            $name = strtolower($roleData['name']);
            $id = $roleData['id'];

            if (in_array($name, $submittedNames, true)) {
                return response()->json(['message' => "Nama role '{$roleData['name']}' duplikat dalam data yang dikirim."], 422);
            }
            $submittedNames[] = $name;

            $role = Role::find($id);
            if (!$role) continue;

            if (in_array($role->name, $systemRoles, true) && $role->name !== $name) {
                return response()->json(['message' => "Role bawaan sistem '{$role->name}' tidak dapat diubah namanya."], 422);
            }

            if (Role::where('name', $name)->where('id', '!=', $id)->exists()) {
                return response()->json(['message' => "Nama role '{$roleData['name']}' sudah digunakan."], 422);
            }
        }

        DB::transaction(function () use ($validated) {
            foreach ($validated['roles'] as $roleData) {
                $role = Role::find($roleData['id']);
                if (!$role) continue;

                $oldName = $role->name;
                $newName = strtolower($roleData['name']);

                if ($oldName !== $newName) {
                    DB::table('users')->where('role', $oldName)->update(['role' => $newName]);
                    DB::table('approval_flow_steps')->where('role', $oldName)->update(['role' => $newName]);
                    DB::table('approval_flow_steps')->where('reporter_role', $oldName)->update(['reporter_role' => $newName]);
                }

                $role->update([
                    'name' => $newName,
                    'display_name' => $roleData['display_name'],
                    'can_approve' => $roleData['can_approve'],
                    'can_report' => $roleData['can_report'],
                    'can_approve_unlock' => $roleData['can_approve_unlock'] ?? false,
                    'can_add_data' => $roleData['can_add_data'] ?? false,
                    'can_delete_data' => $roleData['can_delete_data'] ?? false,
                    'required_difficulties' => $this->sanitizeDifficulties($roleData['required_difficulties'] ?? []),
                ]);
            }
        });

        return response()->json(['message' => 'Semua role berhasil disimpan.']);
    }

    private function sanitizeDifficulties(array $items): array
    {
        $allowed = ['berat', 'sedang', 'ringan', 'none'];
        return array_values(array_filter($items, fn ($v) => in_array($v, $allowed, true)));
    }

    public function destroy(Request $request, $id)
    {
        $authUser = $request->user();
        if (!$authUser || $authUser->role !== 'admin') {
            return response()->json(['message' => 'Hanya admin yang dapat mengelola role.'], 403);
        }

        $role = Role::findOrFail($id);

        // Prevent deletion of built-in system roles
        if (in_array($role->name, ['admin', 'technician']) || $role->is_manager) {
            return response()->json(['message' => 'Role bawaan sistem tidak dapat dihapus.'], 422);
        }

        // Prevent deletion if role is still assigned to users or used in flow steps
        $userCount = DB::table('users')->where('role', $role->name)->count();
        if ($userCount > 0) {
            return response()->json(['message' => 'Role masih digunakan oleh ' . $userCount . ' user.'], 422);
        }

        $flowStepCount = DB::table('approval_flow_steps')->where('role', $role->name)->where('is_active', true)->count();
        if ($flowStepCount > 0) {
            return response()->json(['message' => 'Role masih digunakan di alur approval.'], 422);
        }

        $role->delete();
        return response()->json(['message' => 'Role berhasil dihapus.']);
    }
}
