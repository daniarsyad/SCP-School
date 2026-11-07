@extends('layouts.waka')

@section('title', 'Edit Profil & Akun - SCP School')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Edit Profil & Akun</h1>
        <p class="text-xs text-slate-500 sm:text-sm mt-1">Perbarui informasi nama lengkap, username, email, dan ubah password akun Waka Kurikulum Anda.</p>
    </div>

    @if($errors->any())
        <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-xs text-red-700 sm:text-sm">
                    <p class="font-semibold mb-1">Terjadi kesalahan pada data yang dimasukkan:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('waka.profile.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Kartu Profil Utama --}}
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-5 sm:p-7">
            <div class="flex items-center gap-3 pb-5 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-800">Informasi Pribadi & Kontak</h2>
                    <p class="text-xs text-slate-500">Nama lengkap dan kontak identitas untuk masuk ke sistem.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5 mt-5">
                {{-- Nama Lengkap --}}
                <div class="sm:col-span-2">
                    <label for="fullname" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="fullname" id="fullname"
                           value="{{ old('fullname', $user->fullname) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition"
                           placeholder="Masukkan nama lengkap beserta gelar">
                </div>

                {{-- Username --}}
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Username <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-sm">@</span>
                        <input type="text" name="username" id="username"
                               value="{{ old('username', $user->username) }}" required
                               class="w-full pl-8 pr-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition"
                               placeholder="username">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Digunakan untuk login ke panel web Waka.</p>
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Alamat Email
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input type="email" name="email" id="email"
                               value="{{ old('email', $user->email) }}"
                               class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition"
                               placeholder="email@sekolah.sch.id">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Digunakan untuk verifikasi OTP saat lupa password.</p>
                </div>

                {{-- NIP / Role (Readonly Info) --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">NIP</label>
                    <input type="text" value="{{ $user->nip ?? '-' }}" disabled
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 text-sm cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Peran Sistem</label>
                    <input type="text" value="{{ $user->role->role_name ?? 'Waka Kurikulum' }}" disabled
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 text-sm cursor-not-allowed">
                </div>
            </div>
        </div>

        {{-- Kartu Ubah Password --}}
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-5 sm:p-7">
            <div class="flex items-center gap-3 pb-5 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-800">Ubah Password</h2>
                    <p class="text-xs text-slate-500">Kosongkan jika Anda tidak ingin mengubah password akun.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5 mt-5">
                {{-- Password Saat Ini --}}
                <div>
                    <label for="current_password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Password Saat Ini
                    </label>
                    <input type="password" name="current_password" id="current_password"
                           autocomplete="current-password"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition"
                           placeholder="••••••••">
                    <p class="text-[11px] text-slate-400 mt-1">Wajib jika ingin mengganti password.</p>
                </div>

                {{-- Password Baru --}}
                <div>
                    <label for="new_password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Password Baru
                    </label>
                    <input type="password" name="new_password" id="new_password"
                           autocomplete="new-password"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition"
                           placeholder="Minimal 6 karakter">
                </div>

                {{-- Konfirmasi Password Baru --}}
                <div>
                    <label for="new_password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Ulangi Password Baru
                    </label>
                    <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                           autocomplete="new-password"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition"
                           placeholder="Ulangi password baru">
                </div>
            </div>
        </div>

        {{-- Tombol Simpan --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('waka.dashboard') }}"
               class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-50 text-xs sm:text-sm font-semibold transition">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold shadow-sm hover:shadow transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Simpan Perubahan</span>
            </button>
        </div>
    </form>
</div>
@endsection

