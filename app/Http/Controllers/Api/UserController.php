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

        $query = User::orderBy('full_name');

        // Manager cannot see admin users
        if ($request->user()->role === 'manager') {
            $query->where('role', '!=', 'admin');
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        // Only Admin can add users
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Hanya Admin yang dapat menambahkan user.'], 403);
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:admin,manager,technician',
            'city' => 'nullable|string|in:pasuruan,sby,both',
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'city' => $validated['city'] ?? 'both',
        ]);

        ActivityLog::log('Tambah User', "Admin menambahkan user baru: {$user->full_name} ({$user->email}) dengan role: " . strtoupper($user->role) . ", kota: " . strtoupper($user->city));

        return response()->json([
            'message' => 'User berhasil ditambahkan.',
            'user' => $user
        ], 201);
    }

    public function updatePassword(Request $request, $id)
    {
        // Only Admin can change passwords
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Hanya Admin yang dapat mengubah password user.'], 403);
        }

        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user = User::findOrFail($id);
        $user->password = bcrypt($request->password);
        $user->save();

        ActivityLog::log('Ubah Password User', "Admin mengubah password untuk user: {$user->full_name} ({$user->email})");

        return response()->json(['message' => 'Password user berhasil diubah.']);
    }

    public function updateCity(Request $request, $id)
    {
        // Only Admin can change city
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Hanya Admin yang dapat mengubah kota user.'], 403);
        }

        $request->validate([
            'city' => 'required|string|in:pasuruan,sby,both',
        ]);

        $user = User::findOrFail($id);
        $user->city = $request->city;
        $user->save();

        ActivityLog::log('Ubah Kota User', "Mengubah kota user {$user->full_name} ({$user->email}) menjadi " . strtoupper($user->city));

        return response()->json([
            'message' => 'Kota user berhasil diperbarui.',
            'user' => $user
        ]);
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
