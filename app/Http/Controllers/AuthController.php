<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah.'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        ActivityLog::log('Login User', "User {$user->full_name} ({$user->email}) berhasil login ke sistem", $user);

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        ActivityLog::log('Logout User', "User {$user->full_name} ({$user->email}) keluar dari sistem", $user);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil keluar.'
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
    }
}
