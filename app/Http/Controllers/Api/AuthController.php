<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password salah.'],
            ]);
        }

        // Hapus token lama (opsional, biar bersih)
        $user->tokens()->delete();

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id'       => $user->id,
                    'fullname' => $user->fullname,
                    'username' => $user->username,
                    'role'     => $user->role->role_name,
                    'class_id' => $user->class_id,
                ],
                'token' => $token,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('role', 'class');

        return response()->json([
            'success' => true,
            'data' => [
                'id'       => $user->id,
                'fullname' => $user->fullname,
                'username' => $user->username,
                'role'     => $user->role->role_name,
                'class'    => $user->class->class_name ?? null,
                'class_id' => $user->class_id,
            ]
        ]);
    }
}