@extends('layouts.waka')

@section('title', 'Data Guru - SCP')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Data Guru</h1>
            <p class="mt-0.5 text-sm text-slate-500">Kelola profil pendidik, penugasan Wali Kelas, dan kelas binaan Guru BK.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('waka.master.users') }}#import-user"
               title="Impor akun secara massal melalui menu Pengguna"
               class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                Import Excel
            </a>
            <button type="button" data-open-add
                    class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
                + Tambah Guru Baru
            </button>
        </div>
    </div>

    @if(session('success') || session('error') || $errors->any())
        <div class="mb-5 rounded-lg border p-4 text-sm {{ $errors->any() || session('error') ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800' }}" role="alert">
            @if(session('success'))<p>{{ session('success') }}</p>@endif
            @if(session('error'))<p>{{ session('error') }}</p>@endif
            @if($errors->any())
                <p class="font-semibold">Data guru gagal disimpan:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            @endif
        </div>
    @endif

    <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach([
            ['label' => 'Semua Guru', 'value' => $stats['total'], 'role' => ''],
            ['label' => 'Guru Pengajar', 'value' => $stats['guru'], 'role' => 'Guru'],
            ['label' => 'Wali Kelas', 'value' => $stats['wali'], 'role' => 'Wali Kelas'],
            ['label' => 'Guru BK', 'value' => $stats['bk'], 'role' => 'Guru BK'],
        ] as $stat)
            @php $isActiveRole = (string) request('role', '') === $stat['role']; @endphp
            <a href="{{ route('waka.teachers.index', array_filter(['role' => $stat['role'], 'search' => request('search')])) }}"
               class="rounded-lg border p-4 transition {{ $isActiveRole ? 'border-slate-400 bg-white' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                <p class="text-xs text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-1 text-xl font-semibold text-slate-900">{{ $stat['value'] }}</p>
            </a>
        @endforeach
    </div>

    <form method="GET" class="mb-5 rounded-lg border border-slate-200 bg-white p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_220px_auto]">
            <label>
                <span class="mb-1 block text-xs font-medium text-slate-600">Cari nama, NIP, email, username, atau nomor HP</span>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..."
                       class="w-full rounded-md border border-slate-300 px-3 py-1.5 text-sm outline-none focus:border-slate-500">
            </label>
            <label>
                <span class="mb-1 block text-xs font-medium text-slate-600">Filter Peran</span>
                <select name="role" class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm outline-none focus:border-slate-500">
                    <option value="">Semua Guru</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->role_name }}" {{ request('role') === $role->role_name ? 'selected' : '' }}>{{ $role->role_name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end gap-2">
                <button class="rounded-md bg-blue-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-blue-700 transition">Cari</button>
                @if(request()->hasAny(['search', 'role']))
                    <a href="{{ route('waka.teachers.index') }}" class="rounded-md border border-slate-200 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">Reset</a>
                @endif
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
            <h2 class="text-sm font-semibold text-slate-800">Daftar Guru</h2>
            <span class="text-xs text-slate-500">{{ $teachers->count() }} guru</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-left text-sm border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Nama Guru</th>
                        <th class="px-4 py-3">NIP</th>
                        <th class="px-4 py-3">Peran</th>
                        <th class="px-4 py-3">Mata Pelajaran</th>
                        <th class="px-4 py-3">Kelas / Binaan</th>
                        <th class="px-4 py-3 text-center">Jadwal</th>
                        <th class="px-4 py-3 text-right w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($teachers as $index => $teacher)
                        @php
                            $assignedClasses = $teacher->role?->role_name === 'Guru BK'
                                ? $teacher->bkClasses
                                : ($teacher->class ? collect([$teacher->class]) : collect());
                            $teacherData = [
                                'id' => $teacher->id,
                                'fullname' => $teacher->fullname,
                                'username' => $teacher->username,
                                'email' => $teacher->email,
                                'nip' => $teacher->nip,
                                'phone' => $teacher->phone,
                                'address' => $teacher->address,
                                'role_id' => $teacher->role_id,
                                'role_name' => $teacher->role?->role_name,
                                'class_id' => $teacher->class_id,
                                'bk_class_ids' => $teacher->bkClasses->pluck('id')->values(),
                                'classes' => $assignedClasses->pluck('class_name')->values(),
                                'schedules_count' => $teacher->schedules_count,
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $teacher->fullname }}</div>
                                <div class="text-xs text-slate-500">{{ $teacher->email ?: $teacher->username }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $teacher->nip ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium border border-slate-200 bg-slate-50 text-slate-700">
                                        {{ $teacher->role?->role_name ?? '-' }}
                                    </span>
                                    @foreach($teacher->additionalRoles as $addRole)
                                        <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium border border-blue-200 bg-blue-50 text-blue-700">
                                            {{ $addRole->role_name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 max-w-[200px]">
                                <div class="line-clamp-2" title="{{ $teacher->subject }}">{{ $teacher->subject ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($assignedClasses->isNotEmpty())
                                    <div class="flex max-w-xs flex-wrap gap-1">
                                        @foreach($assignedClasses->take(3) as $assignedClass)
                                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $assignedClass->class_name }}</span>
                                        @endforeach
                                        @if($assignedClasses->count() > 3)
                                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-500" title="{{ $assignedClasses->pluck('class_name')->implode(', ') }}">
                                                +{{ $assignedClasses->count() - 3 }} kelas
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-slate-600 whitespace-nowrap">
                                <a href="{{ route('waka.schedules.index', ['teacher_id' => $teacher->id]) }}"
                                   class="hover:text-blue-600 hover:underline">
                                    {{ $teacher->schedules_count }} sesi
                                </a>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" data-open-detail data-teacher="{{ json_encode($teacherData) }}"
                                            class="rounded border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Lihat</button>
                                    <button type="button" data-open-edit data-teacher="{{ json_encode($teacherData) }}"
                                            class="rounded border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Edit</button>
                                    @if($teacher->id !== auth()->id())
                                        <form action="{{ route('waka.teachers.delete', $teacher->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data guru ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded border border-red-200 bg-white px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada data guru yang sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <dialog id="addTeacherModal" class="w-[min(94vw,760px)] max-h-[92vh] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <h2 class="font-semibold text-slate-800">Tambah Guru Baru</h2>
                <p class="mt-0.5 text-xs text-slate-500">Lengkapi profil dan penugasan guru.</p>
            </div>
            <button type="button" data-close-modal class="rounded-lg px-3 py-1.5 text-xs text-slate-500 hover:bg-slate-100">Tutup</button>
        </div>
        <form action="{{ route('waka.teachers.store') }}" method="POST" data-teacher-form class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
            @csrf
            <label class="sm:col-span-2">
                <span class="mb-1 block text-xs font-medium text-slate-600">Nama Lengkap & Gelar</span>
                <input name="fullname" value="{{ old('fullname') }}" required maxlength="100" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            {{-- [VALIDASI NIP & NIS] Tambah Guru: NIP opsional, wajib angka maksimal 18 digit --}}
            <label>
                <span class="mb-1 block text-xs font-medium text-slate-600">NIP (Opsional, Maks. 18 digit angka)</span>
                <input name="nip" value="{{ old('nip') }}" inputmode="numeric" pattern="[0-9]{1,18}" maxlength="18" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 18)" title="NIP wajib berupa angka dan maksimal 18 digit" placeholder="Maksimal 18 angka" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            <label>
                <span class="mb-1 block text-xs font-medium text-slate-600">Username Login</span>
                <input name="username" value="{{ old('username') }}" required maxlength="50" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            <label>
                <span class="mb-1 block text-xs font-medium text-slate-600">Email Sekolah (Opsional)</span>
                <input type="email" name="email" value="{{ old('email') }}" maxlength="100" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            <label>
                <span class="mb-1 block text-xs font-medium text-slate-600">Nomor HP / WhatsApp</span>
                <input name="phone" value="{{ old('phone') }}" maxlength="30" placeholder="Opsional" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            <label>
                <span class="mb-1 block text-xs font-medium text-slate-600">Password</span>
                <input type="password" name="password" required minlength="6" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            <label>
                <span class="mb-1 block text-xs font-medium text-slate-600">Penugasan Peran Guru</span>
                <select name="role_id" data-role-select required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">-- Pilih Peran --</option>
                    @foreach($roles as $role)<option value="{{ $role->id }}" data-name="{{ strtolower($role->role_name) }}" {{ (string) old('role_id') === (string) $role->id ? 'selected' : '' }}>{{ $role->role_name }}</option>@endforeach
                </select>
            </label>
            <label data-class-field class="hidden">
                <span class="mb-1 block text-xs font-medium text-slate-600">Pilih Kelas Wali</span>
                <select name="class_id" data-class-select class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->class_name }}</option>@endforeach
                </select>
            </label>
            <div data-bk-field class="hidden sm:col-span-2">
                <div data-bk-picker class="rounded-xl border border-blue-100 bg-blue-50/70 p-4">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-blue-900">Kelas yang Diwakili / Binaan Guru BK</h3>
                            <p class="mt-0.5 text-xs text-blue-700">Guru BK dapat membina lebih dari satu kelas.</p>
                        </div>
                        <span data-count class="whitespace-nowrap rounded-full bg-white px-2.5 py-1 text-xs font-medium text-blue-700">0 kelas dipilih</span>
                    </div>
                    <div class="grid grid-cols-1 items-end gap-2 sm:grid-cols-3">
                        <label><span class="mb-1 block text-[11px] text-slate-500">Tingkat</span>
                            <select data-filter="grade" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm"><option value="">Semua Tingkat</option>@foreach($classes->pluck('grade')->filter()->unique()->sort() as $grade)<option value="{{ $grade }}">{{ $grade }}</option>@endforeach</select>
                        </label>
                        <label><span class="mb-1 block text-[11px] text-slate-500">Jurusan</span>
                            <select data-filter="major" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm"><option value="">Semua Jurusan</option>@foreach($classes->pluck('major')->filter()->unique()->sort() as $major)<option value="{{ $major }}">{{ $major }}</option>@endforeach</select>
                        </label>
                        <label><span class="mb-1 block text-[11px] text-slate-500">Rombel</span>
                            <select data-filter="section" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm"><option value="">Semua Rombel</option>@foreach($classes->pluck('section')->filter()->unique()->sort() as $section)<option value="{{ $section }}">{{ $section }}</option>@endforeach</select>
                        </label>
                    </div>
                    <select data-class-choice class="mt-3 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                        <option value="">-- Pilih kelas untuk ditambahkan --</option>
                    </select>
                    <div class="mt-2 flex justify-end">
                        <button type="button" data-add-class class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Tambah Kelas</button>
                    </div>
                    <div class="mt-3 rounded-lg border border-blue-100 bg-white p-3">
                        <p class="mb-2 text-xs font-medium text-slate-500">Daftar kelas binaan yang diwakili Guru BK:</p>
                        <div data-selected-classes class="flex min-h-7 flex-wrap gap-2">
                            <span data-empty class="text-xs text-slate-400">Belum ada kelas dipilih.</span>
                        </div>
                        <div data-hidden-inputs></div>
                    </div>
                </div>
            </div>
            <label class="sm:col-span-2">
                <span class="mb-1 block text-xs font-medium text-slate-600">Alamat Tempat Tinggal</span>
                <textarea name="address" rows="2" maxlength="500" placeholder="Opsional" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('address') }}</textarea>
            </label>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 sm:col-span-2">
                <button type="button" data-close-modal class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Batal</button>
                <button class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">Simpan Guru</button>
            </div>
        </form>
    </dialog>

    <dialog id="editTeacherModal" class="w-[min(94vw,760px)] max-h-[92vh] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <h2 class="font-semibold text-slate-800">Edit Data Guru & Peran</h2>
                <p class="mt-0.5 text-xs text-slate-500">Perbarui profil dan atur kelas binaan.</p>
            </div>
            <button type="button" data-close-modal class="rounded-lg px-3 py-1.5 text-xs text-slate-500 hover:bg-slate-100">Tutup</button>
        </div>
        <form id="editTeacherForm" method="POST" data-teacher-form class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
            @csrf
            @method('PUT')
            <label class="sm:col-span-2"><span class="mb-1 block text-xs font-medium text-slate-600">Nama Lengkap & Gelar</span>
                <input name="fullname" required maxlength="100" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            {{-- [VALIDASI NIP & NIS] Edit Guru: NIP opsional, wajib angka maksimal 18 digit --}}
            <label><span class="mb-1 block text-xs font-medium text-slate-600">NIP (Opsional, Maks. 18 digit angka)</span>
                <input name="nip" inputmode="numeric" pattern="[0-9]{1,18}" maxlength="18" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 18)" title="NIP wajib berupa angka dan maksimal 18 digit" placeholder="Maksimal 18 angka" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            <label><span class="mb-1 block text-xs font-medium text-slate-600">Username Login</span>
                <input name="username" required maxlength="50" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            <label><span class="mb-1 block text-xs font-medium text-slate-600">Email Sekolah (Opsional)</span>
                <input type="email" name="email" maxlength="100" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            <label><span class="mb-1 block text-xs font-medium text-slate-600">Nomor HP / WhatsApp</span>
                <input name="phone" maxlength="30" placeholder="Opsional" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            <label><span class="mb-1 block text-xs font-medium text-slate-600">Password Baru</span>
                <input type="password" name="password" minlength="6" placeholder="Kosongkan jika tidak diubah" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
            <label><span class="mb-1 block text-xs font-medium text-slate-600">Penugasan Peran Guru</span>
                <select name="role_id" data-role-select required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @foreach($roles as $role)<option value="{{ $role->id }}" data-name="{{ strtolower($role->role_name) }}">{{ $role->role_name }}</option>@endforeach
                </select>
            </label>
            <label data-class-field class="hidden"><span class="mb-1 block text-xs font-medium text-slate-600">Pilih Kelas Wali</span>
                <select name="class_id" data-class-select class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">-- Pilih Kelas --</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->class_name }}</option>@endforeach
                </select>
            </label>
            <div data-bk-field class="hidden sm:col-span-2">
                <div data-bk-picker class="rounded-xl border border-blue-100 bg-blue-50/70 p-4">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div><h3 class="text-sm font-semibold text-blue-900">Kelas Binaan Guru BK</h3><p class="mt-0.5 text-xs text-blue-700">Pilih satu atau beberapa kelas.</p></div>
                        <span data-count class="whitespace-nowrap rounded-full bg-white px-2.5 py-1 text-xs font-medium text-blue-700">0 kelas dipilih</span>
                    </div>
                    <div class="grid grid-cols-1 items-end gap-2 sm:grid-cols-3">
                        <label><span class="mb-1 block text-[11px] text-slate-500">Tingkat</span><select data-filter="grade" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm"><option value="">Semua Tingkat</option>@foreach($classes->pluck('grade')->filter()->unique()->sort() as $grade)<option value="{{ $grade }}">{{ $grade }}</option>@endforeach</select></label>
                        <label><span class="mb-1 block text-[11px] text-slate-500">Jurusan</span><select data-filter="major" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm"><option value="">Semua Jurusan</option>@foreach($classes->pluck('major')->filter()->unique()->sort() as $major)<option value="{{ $major }}">{{ $major }}</option>@endforeach</select></label>
                        <label><span class="mb-1 block text-[11px] text-slate-500">Rombel</span><select data-filter="section" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm"><option value="">Semua Rombel</option>@foreach($classes->pluck('section')->filter()->unique()->sort() as $section)<option value="{{ $section }}">{{ $section }}</option>@endforeach</select></label>
                    </div>
                    <select data-class-choice class="mt-3 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><option value="">-- Pilih kelas untuk ditambahkan --</option></select>
                    <div class="mt-2 flex justify-end"><button type="button" data-add-class class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Tambah Kelas</button></div>
                    <div class="mt-3 rounded-lg border border-blue-100 bg-white p-3">
                        <p class="mb-2 text-xs font-medium text-slate-500">Daftar kelas binaan Guru BK:</p>
                        <div data-selected-classes class="flex min-h-7 flex-wrap gap-2"><span data-empty class="text-xs text-slate-400">Belum ada kelas dipilih.</span></div>
                        <div data-hidden-inputs></div>
                    </div>
                </div>
            </div>
            <label class="sm:col-span-2"><span class="mb-1 block text-xs font-medium text-slate-600">Alamat Tempat Tinggal</span>
                <textarea name="address" rows="2" maxlength="500" placeholder="Opsional" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"></textarea>
            </label>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 sm:col-span-2">
                <button type="button" data-close-modal class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Batal</button>
                <button class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">Simpan Perubahan</button>
            </div>
        </form>
    </dialog>

    <dialog id="teacherDetailModal" class="w-[min(94vw,540px)] rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-semibold text-slate-800">Detail Profil Guru</h2>
            <button type="button" data-close-modal class="rounded-lg px-3 py-1.5 text-xs text-slate-500 hover:bg-slate-100">Tutup</button>
        </div>
        <div class="p-6">
            <div class="rounded-xl bg-slate-50 p-4">
                <p id="detailName" class="font-semibold text-slate-800"></p>
                <p id="detailUsername" class="mt-1 text-sm text-blue-600"></p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span id="detailRole" class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700"></span>
                    <span id="detailSchedule" class="rounded-full bg-white px-2.5 py-1 text-xs text-slate-600"></span>
                </div>
            </div>
            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><dt class="text-xs text-slate-500">NIP</dt><dd id="detailNip" class="mt-1 text-sm text-slate-800"></dd></div>
                <div><dt class="text-xs text-slate-500">Email Resmi</dt><dd id="detailEmail" class="mt-1 break-all text-sm text-slate-800"></dd></div>
                <div><dt class="text-xs text-slate-500">Nomor HP / WhatsApp</dt><dd id="detailPhone" class="mt-1 text-sm text-slate-800"></dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Kelas / Kelas Binaan</dt><dd id="detailClasses" class="mt-1 text-sm text-slate-800"></dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Alamat Tempat Tinggal</dt><dd id="detailAddress" class="mt-1 whitespace-pre-line text-sm text-slate-800"></dd></div>
            </dl>
            <div class="mt-6 flex justify-end">
                <button type="button" data-close-modal class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Tutup Tampilan</button>
            </div>
        </div>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const classes = @json($classOptions);
            const addModal = document.getElementById('addTeacherModal');
            const editModal = document.getElementById('editTeacherModal');
            const detailModal = document.getElementById('teacherDetailModal');
            const editForm = document.getElementById('editTeacherForm');

            function initPicker(picker) {
                const selectedIds = new Set();
                const filters = Array.from(picker.querySelectorAll('[data-filter]'));
                const classChoice = picker.querySelector('[data-class-choice]');
                const selectedContainer = picker.querySelector('[data-selected-classes]');
                const hiddenInputs = picker.querySelector('[data-hidden-inputs]');
                const count = picker.querySelector('[data-count]');

                function refreshChoices() {
                    const selected = Object.fromEntries(filters.map(filter => [filter.dataset.filter, filter.value]));
                    const available = classes.filter(item =>
                        !selected.grade || item.grade === selected.grade
                    ).filter(item =>
                        !selected.major || item.major === selected.major
                    ).filter(item =>
                        !selected.section || item.section === selected.section
                    );
                    classChoice.replaceChildren(new Option('-- Pilih kelas untuk ditambahkan --', ''));
                    available.forEach(item => {
                        if (!selectedIds.has(String(item.id))) {
                            classChoice.add(new Option(item.name, item.id));
                        }
                    });
                    if (classChoice.options.length === 1) {
                        classChoice.options[0].textContent = available.length ? '-- Semua kelas sudah dipilih --' : '-- Tidak ada kelas yang sesuai --';
                    }
                }

                function renderSelected() {
                    selectedContainer.replaceChildren();
                    hiddenInputs.replaceChildren();
                    const selectedClasses = classes.filter(item => selectedIds.has(String(item.id)));
                    count.textContent = `${selectedClasses.length} kelas dipilih`;
                    if (!selectedClasses.length) {
                        const empty = document.createElement('span');
                        empty.className = 'text-xs text-slate-400';
                        empty.textContent = 'Belum ada kelas dipilih.';
                        selectedContainer.append(empty);
                    }
                    selectedClasses.forEach(item => {
                        const chip = document.createElement('span');
                        chip.className = 'inline-flex items-center gap-1 rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-800';
                        const label = document.createElement('span');
                        label.textContent = item.name;
                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'rounded-full px-1 text-blue-500 hover:bg-blue-100 hover:text-red-600';
                        remove.setAttribute('aria-label', `Hapus kelas ${item.name}`);
                        remove.textContent = '×';
                        remove.addEventListener('click', () => {
                            selectedIds.delete(String(item.id));
                            renderSelected();
                            refreshChoices();
                        });
                        chip.append(label, remove);
                        selectedContainer.append(chip);
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'bk_class_ids[]';
                        input.value = item.id;
                        hiddenInputs.append(input);
                    });
                }

                picker.querySelector('[data-add-class]').addEventListener('click', () => {
                    if (classChoice.value) {
                        selectedIds.add(String(classChoice.value));
                        renderSelected();
                        refreshChoices();
                    }
                });
                filters.forEach(filter => filter.addEventListener('change', refreshChoices));
                refreshChoices();
                renderSelected();

                return {
                    set(ids) {
                        selectedIds.clear();
                        ids.forEach(id => selectedIds.add(String(id)));
                        renderSelected();
                        refreshChoices();
                    },
                    clear() {
                        this.set([]);
                    },
                };
            }

            function initTeacherForm(form) {
                const roleSelect = form.querySelector('[data-role-select]');
                const classField = form.querySelector('[data-class-field]');
                const classSelect = form.querySelector('[data-class-select]');
                const bkField = form.querySelector('[data-bk-field]');
                const picker = bkField ? initPicker(bkField.querySelector('[data-bk-picker]')) : null;

                function updateRole() {
                    const roleName = roleSelect.options[roleSelect.selectedIndex]?.dataset.name || '';
                    const isWali = roleName === 'wali kelas';
                    const isBk = roleName === 'guru bk';
                    if (classField) {
                        classField.classList.toggle('hidden', !isWali);
                        classSelect.required = isWali;
                        if (!isWali) classSelect.value = '';
                    }
                    if (bkField) {
                        bkField.classList.toggle('hidden', !isBk);
                        if (!isBk) picker.clear();
                    }
                }

                roleSelect.addEventListener('change', updateRole);
                updateRole();
                return {
                    setBkClasses(ids) {
                        const roleName = roleSelect.options[roleSelect.selectedIndex]?.dataset.name || '';
                        if (picker && roleName === 'guru bk') picker.set(ids);
                    },
                    setTeacher(teacher) {
                        form.elements.fullname.value = teacher.fullname || '';
                        form.elements.username.value = teacher.username || '';
                        form.elements.email.value = teacher.email || '';
                        form.elements.nip.value = teacher.nip || '';
                        form.elements.phone.value = teacher.phone || '';
                        form.elements.address.value = teacher.address || '';
                        form.elements.role_id.value = teacher.role_id;
                        updateRole();
                        if (classSelect) classSelect.value = teacher.class_id || '';
                        if (picker) picker.set(teacher.bk_class_ids || []);
                        form.action = `{{ url('/waka/teachers') }}/${teacher.id}`;
                    },
                };
            }

            const addController = initTeacherForm(addModal.querySelector('[data-teacher-form]'));
            const editController = initTeacherForm(editForm);
            addController.setBkClasses(@json((array) old('bk_class_ids', [])));

            document.querySelectorAll('[data-open-add]').forEach(button => button.addEventListener('click', () => addModal.showModal()));
            document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
            document.querySelectorAll('[data-open-edit]').forEach(button => button.addEventListener('click', () => {
                const teacher = JSON.parse(button.dataset.teacher);
                editController.setTeacher(teacher);
                editModal.showModal();
            }));
            document.querySelectorAll('[data-open-detail]').forEach(button => button.addEventListener('click', () => {
                const teacher = JSON.parse(button.dataset.teacher);
                document.getElementById('detailName').textContent = teacher.fullname || '-';
                document.getElementById('detailUsername').textContent = `@${teacher.username || '-'}`;
                document.getElementById('detailRole').textContent = teacher.role_name || '-';
                document.getElementById('detailSchedule').textContent = `${teacher.schedules_count} Jadwal Terdaftar`;
                document.getElementById('detailNip').textContent = teacher.nip || '-';
                document.getElementById('detailEmail').textContent = teacher.email || '-';
                document.getElementById('detailPhone').textContent = teacher.phone || '-';
                document.getElementById('detailClasses').textContent = teacher.classes.length ? teacher.classes.join(', ') : '-';
                document.getElementById('detailAddress').textContent = teacher.address || '-';
                detailModal.showModal();
            }));

            if (@json($errors->any())) {
                addModal.showModal();
            }
        });
    </script>
@endsection
