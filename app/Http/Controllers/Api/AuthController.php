<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\PasswordOtp;
use App\Services\IdentityNumberValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required_without:username|string',
            'username' => 'required_without:identifier|string',
            'password' => 'required|string',
        ]);

        $identifier = $request->input('identifier', $request->input('username'));
        $users = User::where('username', $identifier)
            ->orWhere(function ($query) use ($identifier) {
                $query->where('nis', $identifier)
                    ->whereHas('role', fn ($role) => $role->whereIn('role_name', ['Siswa', 'Orang Tua']));
            })
            ->orWhere(function ($query) use ($identifier) {
                $query->where('nip', $identifier)
                    ->whereHas('role', fn ($role) => $role->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum']));
            })
            ->get()
            ->filter(fn (User $candidate) => Hash::check($request->password, $candidate->password))
            ->values();

        if ($users->count() !== 1) {
            throw ValidationException::withMessages([
                'identifier' => ['NIS/NIP/username atau password salah.'],
            ]);
        }

        $user = $users->first();

        if ($user->hasPrimaryRole('Waka Kurikulum')) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Waka hanya bisa login melalui website.',
            ], 403);
        }

        // Cek jika siswa sudah lulus (Alumni)
        if ($user->isSiswa() && $user->is_alumni) {
            return response()->json([
                'success' => false,
                'message' => 'Akun dinonaktifkan karena Anda telah dinyatakan lulus (Alumni).',
            ], 403);
        }

        // Cek jika orang tua yang semua anaknya sudah lulus (Alumni)
        if ($user->isOrangTua()) {
            $user->syncChildrenParentId();

            $hasActiveChild = $user->getChildrenQuery(false)->exists();
            $totalChildren = $user->getChildrenQuery(true)->count();

            if ($totalChildren > 0 && !$hasActiveChild) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun dinonaktifkan karena seluruh siswa yang ditautkan telah lulus (Alumni).',
                ], 403);
            }
        }

        $user->load(['role:id,role_name', 'class:id,class_name', 'additionalRoles:id,role_name']);
        if ($user->isGuruBK()) {
            $user->load(['bkClasses:id,class_name,grade,major,section']);
        }

        $user->tokens()->delete();

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => $this->serializeUserProfile($user),
                'token' => $token,
            ],
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
        $user = $request->user();

        // Validasi jika akun sudah alumni saat token dicek
        if ($user->isSiswa() && $user->is_alumni) {
            $user->tokens()->delete();
            return response()->json([
                'success' => false,
                'message' => 'Akun dinonaktifkan karena Anda telah dinyatakan lulus (Alumni).',
            ], 403);
        }

        if ($user->isOrangTua()) {
            $user->syncChildrenParentId();
            $hasActiveChild = $user->getChildrenQuery(false)->exists();
            $totalChildren = $user->getChildrenQuery(true)->count();

            if ($totalChildren > 0 && !$hasActiveChild) {
                $user->tokens()->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Akun dinonaktifkan karena seluruh siswa yang ditautkan telah lulus (Alumni).',
                ], 403);
            }
        }

        $user->loadMissing(['role:id,role_name', 'class:id,class_name', 'additionalRoles:id,role_name']);
        if ($user->isGuruBK()) {
            $user->loadMissing(['bkClasses:id,class_name,grade,major,section']);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializeUserProfile($user),
        ]);
    }

    private function serializeUserProfile(User $user): array
    {
        $childrenData = [];
        if ($user->isOrangTua()) {
            $children = $user->syncChildrenParentId();
            $childrenData = $children->map(fn ($child) => [
                'id' => $child->id,
                'fullname' => $child->fullname,
                'nis' => $child->nis,
                'class_id' => $child->class_id,
                'class_name' => $child->class?->class_name,
                'class' => $child->class ? [
                    'id' => $child->class->id,
                    'class_name' => $child->class->class_name,
                ] : null,
                'username' => $child->username,
            ])->values()->all();
        }

        return [
            'id' => $user->id,
            'fullname' => $user->fullname,
            'username' => $user->username,
            'email' => $user->email,
            'nis' => $user->nis,
            'nip' => $user->nip,
            'role_id' => $user->role_id,
            'role' => $user->role->role_name,
            'class_id' => $user->class_id,
            'class' => $user->class?->class_name,
            'children' => $childrenData,
            'additional_roles' => $user->additionalRoles
                ->sortBy('role_name')
                ->map(fn (Role $role) => ['id' => $role->id, 'role_name' => $role->role_name])
                ->values()
                ->all(),
            'bk_classes' => $user->isGuruBK()
                ? $user->bkClasses
                    ->sortBy('class_name')
                    ->map(fn ($class) => [
                        'id' => $class->id,
                        'class_name' => $class->class_name,
                        'grade' => $class->grade,
                        'major' => $class->major,
                        'section' => $class->section,
                    ])
                    ->values()
                    ->all()
                : [],
        ];
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'fullname' => 'sometimes|required|string|max:100',
            'username' => [
                'sometimes',
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

        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password saat ini tidak sesuai.',
                    'errors' => [
                        'current_password' => ['Password saat ini tidak sesuai.'],
                    ],
                ], 422);
            }
            $user->password = Hash::make($request->new_password);
        }

        if ($request->has('fullname')) {
            $user->fullname = $request->fullname;
        }

        if ($request->has('username')) {
            $user->username = $request->username;
        }

        if ($request->has('email')) {
            $user->email = $request->filled('email') ? $request->email : null;
        }

        $user->save();

        $user->loadMissing(['role:id,role_name', 'class:id,class_name', 'additionalRoles:id,role_name']);
        if ($user->isGuruBK()) {
            $user->loadMissing(['bkClasses:id,class_name,grade,major,section']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'user' => $this->serializeUserProfile($user),
            ],
        ]);
    }

    public function children(Request $request)
    {
        $user = $request->user();

        if (!$user->isOrangTua()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Orang Tua yang dapat mengakses.',
            ], 403);
        }

        $children = $user->syncChildrenParentId();

        $data = $children->map(fn ($c) => [
            'id' => $c->id,
            'fullname' => $c->fullname,
            'nis' => $c->nis,
            'class_id' => $c->class_id,
            'class_name' => $c->class?->class_name,
            'class' => $c->class ? [
                'id' => $c->class->id,
                'class_name' => $c->class->class_name,
            ] : null,
            'username' => $c->username,
        ])->values()->all();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function register(Request $request)
    {
        $role = Role::find($request->role_id);
        $roleName = strtolower($role?->role_name ?? '');

        if ($roleName === 'orang tua') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Orang Tua dibuat melalui import data siswa agar tertaut ke anak.',
            ], 403);
        }

        $rules = [
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id'  => 'required|exists:roles,id',
            'class_id' => 'nullable|exists:classes,id',
            'subject'  => 'nullable|string|max:100',
            // [VALIDASI NIP & NIS] NIS wajib angka (panjang bebas); NIP wajib angka maks. 18 digit
            'nis'      => IdentityNumberValidator::optionalNisFormatRules(),
            'nip'      => IdentityNumberValidator::nipRules(required: false),
        ];

        if (in_array($roleName, ['siswa', 'wali kelas'], true)) {
            $rules['class_id'] = 'required|exists:classes,id';
        }
        if ($roleName === 'siswa') {
            // [VALIDASI NIP & NIS] Khusus Siswa: NIS wajib diisi, hanya angka (panjang bebas), dan unik
            $rules['nis'] = IdentityNumberValidator::nisRules($role->id);
        }
        if (in_array($roleName, ['guru', 'wali kelas', 'guru bk', 'waka kurikulum'], true)) {
            // [VALIDASI NIP & NIS] Khusus Guru/Staf: NIP wajib diisi, hanya angka maks. 18 digit, dan unik
            $rules['nip'] = IdentityNumberValidator::nipRules(required: true);
        }

        $request->validate($rules, IdentityNumberValidator::messages());

        if ($roleName === 'waka kurikulum') {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa mendaftar sebagai Waka Kurikulum dari aplikasi mobile.',
            ], 403);
        }

        $user = User::create([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
            'class_id' => $request->class_id,
            'subject'  => $request->subject,
            'nis'      => $roleName === 'siswa' ? $request->nis : null,
            'nip'      => in_array($roleName, ['guru', 'wali kelas', 'guru bk', 'waka kurikulum'], true) ? $request->nip : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dibuat. Silakan login.',
            'data' => [
                'id'       => $user->id,
                'username' => $user->username,
                'nis'      => $user->nis,
                'nip'      => $user->nip,
                'fullname' => $user->fullname,
                'class_id' => $user->class_id,
                'class'    => $user->class?->class_name,
            ],
        ], 201);
    }

    public function requestOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Email tidak ditemukan di sistem.',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->hasPrimaryRole('Waka Kurikulum')) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Waka hanya bisa reset password melalui website.',
            ], 403);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordOtp::where('email', $user->email)->delete();

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

        return response()->json([
            'success' => true,
            'message' => 'OTP telah dikirim ke email terdaftar.',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan.',
            ], 422);
        }

        if ($user->hasPrimaryRole('Waka Kurikulum')) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Waka hanya bisa reset password melalui website.',
            ], 403);
        }

        $row = PasswordOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>=', Carbon::now())
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP salah atau sudah kedaluwarsa.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP benar.',
        ]);
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

        if ($user->hasPrimaryRole('Waka Kurikulum')) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Waka hanya bisa reset password melalui website.',
            ], 403);
        }

        $row = PasswordOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>=', Carbon::now())
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'OTP salah atau sudah kedaluwarsa.',
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        PasswordOtp::where('email', $request->email)->delete();

        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan login.',
        ]);
    }

    public function roles()
    {
        $roles = Role::whereNotIn('role_name', ['Waka Kurikulum', 'Orang Tua'])
            ->orderBy('role_name')
            ->get(['id', 'role_name']);

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }
}