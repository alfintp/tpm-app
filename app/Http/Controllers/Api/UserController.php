<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Require manager or admin role
        if (!in_array($request->user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json(User::orderBy('full_name')->get());
    }

    public function updateRole(Request $request, $id)
    {
        // Require manager or admin role
        if (!in_array($request->user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'role' => 'required|string|in:admin,technician,manager',
        ]);

        $user = User::findOrFail($id);
        
        // Prevent changing own role if that would lock them out of admin
        if ($user->id === $request->user()->id && $request->role !== 'admin' && $request->user()->role === 'admin') {
            // Count other admins
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => 'Tidak dapat mengubah role Anda sendiri karena Anda adalah satu-satunya Admin.'], 400);
            }
        }

        $oldRole = $user->role;
        $user->role = $request->role;
        $user->save();

        ActivityLog::log('Ubah Role User', "Mengubah role user {$user->full_name} ({$user->email}) dari " . strtoupper($oldRole) . " menjadi " . strtoupper($user->role));

        return response()->json([
            'message' => 'Role user berhasil diperbarui.',
            'user' => $user
        ]);
    }

    public function destroy(Request $request, $id)
    {
        // Require manager or admin role
        if (!in_array($request->user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Tidak dapat menghapus diri sendiri.'], 400);
        }

        $user->delete();

        ActivityLog::log('Hapus User', "Menghapus akun user: {$user->full_name} ({$user->email}) dengan role: " . strtoupper($user->role));

        return response()->json([
            'message' => 'User berhasil dihapus.'
        ]);
    }

    public function activityLogs(Request $request)
    {
        // Require admin role
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $logs = ActivityLog::with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }
}
