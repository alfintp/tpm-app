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
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'can_approve' => $validated['can_approve'],
            'can_report' => $validated['can_report'],
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
            'is_active' => 'required|boolean',
        ]);

        $role->update($validated);

        return response()->json($role);
    }

    public function destroy(Request $request, $id)
    {
        $authUser = $request->user();
        if (!$authUser || $authUser->role !== 'admin') {
            return response()->json(['message' => 'Hanya admin yang dapat mengelola role.'], 403);
        }

        $role = Role::findOrFail($id);

        // Prevent deletion of built-in system roles
        if (in_array($role->name, ['admin', 'technician', 'manager'])) {
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
