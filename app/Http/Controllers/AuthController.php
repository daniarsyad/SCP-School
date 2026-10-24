<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\PasswordOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

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
            'email'    => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role_id'  => 'required|exists:roles,id',
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'username.unique'    => 'Username sudah digunakan.',
            'email.unique'       => 'Email sudah digunakan.',
            'email.required'     => 'Email wajib diisi.',
        ]);

        User::create([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
        ]);

        return redirect()->route('login')->with('success', 'Akun berhasil dibuat. Silakan login.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function requestOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists'   => 'Email tidak ditemukan di sistem.',
            'email.required' => 'Email wajib diisi.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user->isWaka()) {
            return back()->withErrors([
                'email' => 'Akun ini hanya bisa reset password melalui aplikasi mobile.',
            ])->withInput();
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordOtp::where('email', $request->email)->delete();

        PasswordOtp::create([
            'email'      => $user->email,
            'username'   => $user->username,
            'otp'        => $otp,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Mail::send('emails.otp', ['otp' => $otp], function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('OTP Reset Password - SCP School');
        });

        return redirect()
            ->route('password.request')
            ->with('otp_sent', true)
            ->with('email', $user->email)
            ->with('success', 'OTP telah dikirim ke email terdaftar.');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $row = PasswordOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>=', Carbon::now())
            ->first();

        if (!$row) {
            return redirect()
                ->route('password.request')
                ->with('otp_sent', true)
                ->with('email', $request->email)
                ->withErrors(['otp' => 'Kode OTP salah atau sudah kedaluwarsa.']);
        }

        return redirect()
            ->route('password.request')
            ->with('otp_verified', true)
            ->with('email', $request->email)
            ->with('otp', $request->otp);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'otp'      => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user->isWaka()) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Akun ini hanya bisa reset password melalui aplikasi mobile.']);
        }

        $row = PasswordOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>=', Carbon::now())
            ->first();

        if (!$row) {
            return redirect()
                ->route('password.request')
                ->with('otp_sent', true)
                ->with('email', $request->email)
                ->withErrors(['otp' => 'OTP salah atau sudah kedaluwarsa. Silakan minta OTP baru.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        PasswordOtp::where('email', $request->email)->delete();

        return redirect()->route('login')->with('success', 'Password berhasil diubah. Silakan login.');
    }
}