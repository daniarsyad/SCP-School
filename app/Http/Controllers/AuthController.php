<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'username' => 'Username atau password salah.',
            ])->onlyInput('username');
        }

        // Hanya Waka yang boleh masuk web
        if (!$user->isWaka()) {
            return back()->withErrors([
                'username' => 'Akun ini tidak memiliki akses ke panel web. Silakan gunakan aplikasi mobile.',
            ])->onlyInput('username');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('waka.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ==================== REGISTER ====================
    public function showRegister()
    {
    $roles = Role::where('role_name', 'Waka Kurikulum')->get();
    return view('auth.register', compact('roles'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
            'role_id'  => 'required|exists:roles,id',
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'username.unique'    => 'Username sudah digunakan.',
        ]);

        User::create([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
        ]);

        return redirect()->route('login')->with('success', 'Akun berhasil dibuat. Silakan login.');
    }

    // ==================== LUPA PASSWORD ====================
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'username'     => 'required|string|exists:users,username',
            'password'     => 'required|string|min:6|confirmed',
        ], [
            'username.exists'    => 'Username tidak ditemukan.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = User::where('username', $request->username)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('login')->with('success', 'Password berhasil diubah. Silakan login dengan password baru.');
    }
}