<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        return view('waka.profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'fullname' => 'required|string|max:100',
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => 'nullable|string|min:6|confirmed',
        ], [
            'username.unique' => 'Username ini sudah digunakan.',
            'email.unique' => 'Email ini sudah digunakan.',
            'new_password.min' => 'Password baru minimal 6 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'current_password.required_with' => 'Password saat ini wajib diisi untuk mengubah password.',
        ]);

        // Verifikasi password saat ini jika ingin mengganti password
        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors([
                    'current_password' => 'Password saat ini tidak sesuai.',
                ])->withInput();
            }
            $user->password = Hash::make($request->new_password);
        }

        $user->fullname = $request->fullname;
        $user->username = $request->username;
        $user->email = $request->filled('email') ? $request->email : null;
        $user->save();

        return back()->with('success', 'Profil dan akun berhasil diperbarui.');
    }
}

