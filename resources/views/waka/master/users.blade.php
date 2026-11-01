@extends('layouts.waka')

@section('title', 'Manajemen Pengguna - SCP')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Manajemen Pengguna</h1>
        <p class="text-gray-500 text-sm mt-1">Kelola akun seluruh pengguna sistem (Waka Kurikulum, Guru , Siswa, Wali Kelas, BK, dan Orang Tua).</p>
    </div>

    <!-- Statistik -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white border border-blue-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Total Akun</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white border border-blue-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Waka / Admin</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['waka'] }}</p>
        </div>
        <div class="bg-white border border-blue-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Guru</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['guru'] }}</p>
        </div>
        <div class="bg-white border border-blue-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Walas & BK</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['walas_bk'] }}</p>
        </div>
        <div class="bg-white border border-blue-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Siswa & Ortu</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['siswa_ortu'] }}</p>
        </div>
    </div>

    <!-- Form Tambah -->
    <div class="bg-white rounded-xl border p-6 mb-6">
        <h2 class="font-bold text-lg mb-4">Tambah User Baru</h2>
        <form action="{{ route('waka.master.users.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
                <input type="text" name="fullname" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Username</label>
                <input type="text" name="username" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg" placeholder="contoh@gmail.com">
                <p class="text-xs text-gray-500 mt-1">Untuk OTP lupa password</p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Role</label>
                <select name="role_id" id="roleSelect" required class="w-full px-4 py-2 border rounded-lg">
                    <option value="">-- Pilih Role --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" data-name="{{ strtolower($role->role_name) }}">{{ $role->role_name }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Input Dinamis -->
            <div id="dynamicInputs" class="flex flex-col gap-4 w-full md:col-span-1">
                <!-- Field Mata Pelajaran (Hanya untuk Guru dan Wali Kelas) -->
                <div id="subjectField" class="hidden w-full">
                    <label class="block text-sm font-medium mb-1">Mata Pelajaran</label>
                    <input type="text" name="subject" placeholder="Contoh: Pemrograman Web" class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <!-- Field Kelas (Untuk Siswa dan Wali Kelas) -->
                <div id="classField" class="hidden w-full">
                    <label class="block text-sm font-medium mb-1">Kelas</label>
                    <select name="class_id" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->class_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="md:col-span-2 mt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                    Tambah User
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('roleSelect');
            const subjectField = document.getElementById('subjectField');
            const classField = document.getElementById('classField');
            
            // Ambil input asli di dalam div untuk memanipulasi attribute 'required'
            const subjectInput = subjectField.querySelector('input');
            const classSelect = classField.querySelector('select');

            roleSelect.addEventListener('change', function () {
                const selected = this.options[this.selectedIndex];
                const roleName = selected.getAttribute('data-name') || '';

                // Reset Tampilan dan Required Attribute
                subjectField.classList.add('hidden');
                subjectInput.removeAttribute('required');
                
                classField.classList.add('hidden');
                classSelect.removeAttribute('required');

                // Logika Tampilan
                if (roleName === 'guru' || roleName === 'wali kelas') {
                    subjectField.classList.remove('hidden');
                    subjectInput.setAttribute('required', 'true');
                }

                if (roleName === 'siswa' || roleName === 'wali kelas') {
                    classField.classList.remove('hidden');
                    classSelect.setAttribute('required', 'true');
                }
            });
        });
    </script>

    <!-- Import & Template -->
    <div class="bg-white rounded-xl border p-6 mb-6">
        <h2 class="font-semibold mb-1">Import User</h2>
        <p class="text-sm text-gray-500 mb-4">Unggah file Excel/CSV untuk membuat akun secara massal. Password default: <strong>password123</strong></p>

        <div class="flex flex-col sm:flex-row gap-3 items-start">
            <a href="{{ route('waka.master.users.template') }}"
               class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                Download Template
            </a>

            <form action="{{ route('waka.master.users.import') }}" method="POST" enctype="multipart/form-data" class="flex gap-3 items-center">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                       class="text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm">
                    Import
                </button>
            </form>
        </div>
        <p class="text-xs text-gray-400 mt-3">Format: Excel (.xlsx) atau CSV. Maksimal 5 MB. Kolom: No, Nama Lengkap, Email, Username, Role, Kelas</p>
    </div>

    <!-- Filter -->
    <form method="GET" class="bg-white rounded-xl border p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Cari Nama / Username / Email</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Ketik kata kunci..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Filter Peran (Role)</label>
                <select name="role_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Semua Role (Semua Pengguna)</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->role_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Filter Kelas</label>
                <select name="class_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->class_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'role_id', 'class_id']))
                    <a href="{{ route('waka.master.users') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Tabel -->
    <div class="bg-white rounded-xl border overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="font-semibold flex items-center gap-2">
                <span>👥</span> Daftar Akun Pengguna
            </h2>
            <span class="text-sm text-gray-500">{{ $users->count() }} Pengguna Terdaftar</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left font-medium">No</th>
                        <th class="px-6 py-3 text-left font-medium">Nama Lengkap & Email</th>
                        <th class="px-6 py-3 text-left font-medium">Username</th>
                        <th class="px-6 py-3 text-left font-medium">Role / Peran</th>
                        <th class="px-6 py-3 text-left font-medium">Kelas</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($users as $index => $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-6 py-3">
                                <div class="font-medium text-gray-800">
                                    {{ $user->fullname }}
                                    @if($user->id === auth()->id())
                                        <span class="text-blue-600 text-xs font-normal">(Akun Anda)</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">{{ $user->email ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-3">{{ $user->username }}</td>
                            <td class="px-6 py-3">
                                {{ $user->role->role_name ?? '-' }}
                                @if($user->subject)
                                    <span class="text-xs text-gray-500">({{ $user->subject }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">{{ $user->class->class_name ?? '-' }}</td>
                            <td class="px-6 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('waka.master.users.delete', $user->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Yakin hapus user ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus pengguna" aria-label="Hapus pengguna"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-red-50 text-red-700 transition-colors hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <span title="Akun Anda tidak dapat dihapus" aria-label="Akun Anda tidak dapat dihapus"
                                          class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-md border border-gray-200 bg-gray-50 text-gray-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728m0-12.728l12.728 12.728"/>
                                        </svg>
                                    </span>
                                @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                Tidak ada data user
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection