@extends('layouts.waka')

@section('title', 'Manajemen Pengguna - SCP')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Data Pengguna</h1>
            <p class="text-slate-500 text-sm mt-0.5">Kelola akun pengguna sistem (Waka Kurikulum, Guru, Wali Kelas, Guru BK, Siswa, dan Orang Tua).</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="document.getElementById('dangerZoneBox').classList.toggle('hidden')"
                    class="inline-flex items-center rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50 transition">
                Reset Massal
            </button>
            <button type="button" onclick="document.getElementById('import-user').classList.toggle('hidden')"
                    class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                Import Excel
            </button>
            <button type="button" onclick="document.getElementById('addUserBox').classList.toggle('hidden')"
                    class="inline-flex items-center rounded-md bg-blue-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
                + Tambah Pengguna
            </button>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <p class="font-semibold">Data pengguna gagal disimpan:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Statistik -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-xs text-slate-500">Total Akun</p>
            <p class="text-xl font-semibold text-slate-900 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-xs text-slate-500">Waka / Admin</p>
            <p class="text-xl font-semibold text-slate-900 mt-1">{{ $stats['waka'] }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-xs text-slate-500">Guru Pengajar</p>
            <p class="text-xl font-semibold text-slate-900 mt-1">{{ $stats['guru'] }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-xs text-slate-500">Wali Kelas & BK</p>
            <p class="text-xl font-semibold text-slate-900 mt-1">{{ $stats['walas_bk'] }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-xs text-slate-500">Siswa & Orang Tua</p>
            <p class="text-xl font-semibold text-slate-900 mt-1">{{ $stats['siswa_ortu'] }}</p>
        </div>
    </div>

    <!-- Hapus Semua User (Collapsible) -->
    <section id="dangerZoneBox" class="hidden mb-5 rounded-lg border border-red-200 bg-red-50/70 p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-semibold text-red-900">Hapus Semua Akun Pengguna</h2>
                <p class="mt-1 text-xs text-red-800">
                    Menghapus {{ $bulkDeleteCount }} akun selain akun Waka yang sedang digunakan beserta data terkait (jadwal, jurnal, presensi). Tindakan ini tidak dapat dibatalkan.
                </p>
                @if($bulkDeleteCount > 0)
                    <form action="{{ route('waka.master.users.delete-all') }}" method="POST" class="mt-3 flex flex-col gap-2.5 sm:flex-row sm:items-end"
                          onsubmit="return confirm('PERINGATAN: semua akun selain akun Anda beserta data akademik terkait akan dihapus permanen. Lanjutkan?')">
                        @csrf
                        @method('DELETE')
                        <label class="w-full sm:max-w-xs">
                            <span class="mb-1 block text-xs font-medium text-red-900">Ketik <span class="font-bold">HAPUS SEMUA USER</span> untuk konfirmasi</span>
                            <input type="text" name="confirmation" required pattern="HAPUS SEMUA USER" autocomplete="off"
                                   class="w-full rounded-md border border-red-300 bg-white px-3 py-1.5 text-sm text-slate-800 outline-none focus:border-red-500">
                        </label>
                        <button type="submit" class="rounded-md bg-red-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-red-700">
                            Hapus {{ $bulkDeleteCount }} Akun
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <!-- Form Tambah -->
    <div id="addUserBox" class="{{ $errors->any() ? '' : 'hidden' }} bg-white rounded-lg border border-slate-200 p-5 mb-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Tambah Pengguna Baru</h2>
        <form action="{{ route('waka.master.users.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            @csrf
            <div id="nisField" class="hidden w-full">
                <label class="block text-xs font-medium text-slate-600 mb-1">NIS</label>
                <input type="text" name="nis" value="{{ old('nis') }}" maxlength="30" class="w-full px-3 py-1.5 border border-slate-300 rounded-md text-sm">
            </div>
            <div id="nipField" class="hidden w-full">
                <label class="block text-xs font-medium text-slate-600 mb-1">NIP</label>
                <input type="text" name="nip" value="{{ old('nip') }}" maxlength="30" class="w-full px-3 py-1.5 border border-slate-300 rounded-md text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Nama Lengkap</label>
                <input type="text" name="fullname" value="{{ old('fullname') }}" required class="w-full px-3 py-1.5 border border-slate-300 rounded-md text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" class="w-full px-3 py-1.5 border border-slate-300 rounded-md text-sm">
                <p id="usernameHelp" class="hidden text-xs text-slate-500 mt-1">Boleh dikosongkan; otomatis menggunakan username berbasis NIS siswa.</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full px-3 py-1.5 border border-slate-300 rounded-md text-sm" placeholder="contoh@gmail.com">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-3 py-1.5 border border-slate-300 rounded-md text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Peran (Role)</label>
                <select name="role_id" id="roleSelect" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                    <option value="">-- Pilih Peran --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" data-name="{{ strtolower($role->role_name) }}" {{ (string) old('role_id') === (string) $role->id ? 'selected' : '' }}>{{ $role->role_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Input Dinamis -->
            <div id="dynamicInputs" class="flex flex-col gap-3.5 w-full md:col-span-1">
                <div id="subjectField" class="hidden w-full">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Mata Pelajaran</label>
                    <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Contoh: Pemrograman Web" class="w-full px-3 py-1.5 border border-slate-300 rounded-md text-sm">
                </div>

                <div id="classField" class="hidden w-full">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Kelas</label>
                    <select name="class_id" class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (string) old('class_id') === (string) $class->id ? 'selected' : '' }}>{{ $class->class_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="bkClassField" class="hidden w-full">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Kelas Binaan Guru BK (bisa memilih beberapa)</label>
                    <select name="bk_class_ids[]" id="bkClassSelect" multiple size="4" class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ in_array((string) $class->id, array_map('strval', (array) old('bk_class_ids', [])), true) ? 'selected' : '' }}>{{ $class->class_name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Tahan Ctrl (Windows) atau Command (Mac) untuk memilih lebih dari satu kelas.</p>
                </div>

                <div id="parentLinkFields" class="hidden w-full space-y-3.5">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Kelas Siswa</label>
                        <select name="parent_class_id" id="parentClassSelect" class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ (string) old('parent_class_id') === (string) $class->id ? 'selected' : '' }}>{{ $class->class_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Pilih Siswa</label>
                        <select name="student_id" id="parentStudentSelect" disabled class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white disabled:bg-slate-100">
                            <option value="">-- Pilih kelas terlebih dahulu --</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" data-class-id="{{ $student->class_id }}">
                                    {{ $student->fullname }} — NIS {{ $student->nis }}{{ $student->parent ? ' (akun Orang Tua sudah ada)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 flex justify-end pt-1">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>

    <!-- Import & Template -->
    <div id="import-user" class="{{ session('import_issues') ? '' : 'hidden' }} bg-white rounded-lg border border-slate-200 p-5 mb-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-1">Import Akun Pengguna</h2>
        <p class="text-xs text-slate-500 mb-4">Unggah file Excel/CSV untuk membuat akun secara massal.</p>

        @if(session('import_issues'))
            <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-800">
                <p class="font-semibold mb-1">Baris yang dilewati:</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach(session('import_issues') as $issue)
                        <li>{{ $issue }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
            <a href="{{ route('waka.master.users.template') }}"
               class="inline-flex items-center border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 px-3.5 py-1.5 rounded-md text-sm font-medium">
                Unduh Template
            </a>

            <form action="{{ route('waka.master.users.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap gap-2.5 items-center">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                       class="text-sm text-slate-600 border border-slate-300 rounded-md px-2.5 py-1">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium">
                    Proses Import
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.hash === '#import-user') {
                document.getElementById('import-user')?.classList.remove('hidden');
            }
            const roleSelect = document.getElementById('roleSelect');
            const subjectField = document.getElementById('subjectField');
            const classField = document.getElementById('classField');
            const bkClassField = document.getElementById('bkClassField');
            const nisField = document.getElementById('nisField');
            const parentLinkFields = document.getElementById('parentLinkFields');
            const nipField = document.getElementById('nipField');
            const usernameInput = document.querySelector('[name="username"]');
            const emailInput = document.querySelector('[name="email"]');
            const usernameHelp = document.getElementById('usernameHelp');
            const subjectInput = subjectField.querySelector('input');
            const classSelect = classField.querySelector('select');
            const bkClassSelect = document.getElementById('bkClassSelect');
            const nisInput = nisField.querySelector('input');
            const nipInput = nipField.querySelector('input');
            const parentClassSelect = document.getElementById('parentClassSelect');
            const parentStudentSelect = document.getElementById('parentStudentSelect');
            const studentOptions = Array.from(parentStudentSelect.querySelectorAll('option[data-class-id]'));

            parentClassSelect.addEventListener('change', function () {
                const classId = this.value;
                parentStudentSelect.value = '';
                parentStudentSelect.replaceChildren(new Option(
                    classId ? '-- Pilih Siswa --' : '-- Pilih kelas terlebih dahulu --',
                    ''
                ));
                studentOptions
                    .filter(option => option.dataset.classId === classId)
                    .forEach(option => parentStudentSelect.add(option.cloneNode(true)));
                parentStudentSelect.disabled = !classId || parentStudentSelect.options.length === 1;
                if (classId && parentStudentSelect.options.length === 1) {
                    parentStudentSelect.options[0].textContent = '-- Tidak ada siswa tersedia di kelas ini --';
                }
            });

            function updateRoleFields() {
                const selected = this.options[this.selectedIndex];
                const roleName = selected.getAttribute('data-name') || '';

                subjectField.classList.add('hidden');
                subjectInput.removeAttribute('required');

                classField.classList.add('hidden');
                classSelect.removeAttribute('required');
                bkClassField.classList.add('hidden');
                bkClassSelect.removeAttribute('required');
                parentLinkFields.classList.add('hidden');
                parentClassSelect.removeAttribute('required');
                parentStudentSelect.removeAttribute('required');
                parentStudentSelect.disabled = true;
                nisField.classList.add('hidden');
                nisInput.removeAttribute('required');
                nipField.classList.add('hidden');
                nipInput.removeAttribute('required');
                usernameInput.setAttribute('required', 'true');
                emailInput.setAttribute('required', 'true');
                usernameHelp.classList.add('hidden');

                if (roleName === 'guru' || roleName === 'wali kelas') {
                    subjectField.classList.remove('hidden');
                    subjectInput.setAttribute('required', 'true');
                }
                if (roleName === 'siswa' || roleName === 'wali kelas') {
                    classField.classList.remove('hidden');
                    classSelect.setAttribute('required', 'true');
                }
                if (roleName === 'guru bk') {
                    bkClassField.classList.remove('hidden');
                    bkClassSelect.setAttribute('required', 'true');
                }
                if (roleName === 'siswa') {
                    nisField.classList.remove('hidden');
                    nisInput.setAttribute('required', 'true');
                }
                if (roleName === 'orang tua') {
                    parentLinkFields.classList.remove('hidden');
                    parentClassSelect.setAttribute('required', 'true');
                    parentStudentSelect.setAttribute('required', 'true');
                    usernameInput.removeAttribute('required');
                    emailInput.removeAttribute('required');
                    usernameHelp.classList.remove('hidden');
                }
                if (['guru', 'wali kelas', 'guru bk', 'waka kurikulum'].includes(roleName)) {
                    nipField.classList.remove('hidden');
                    nipInput.setAttribute('required', 'true');
                }
            }

            roleSelect.addEventListener('change', updateRoleFields);
            updateRoleFields.call(roleSelect);
            if (parentClassSelect.value) {
                parentClassSelect.dispatchEvent(new Event('change'));
                parentStudentSelect.value = @json((string) old('student_id'));
            }
        });
    </script>

    <!-- Filter -->
    <form method="GET" class="bg-white rounded-lg border border-slate-200 p-4 mb-5">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Cari Nama / Username / NIS / NIP / Email</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Ketik kata kunci..."
                       class="w-full px-3 py-1.5 border border-slate-300 rounded-md text-sm focus:outline-none focus:border-slate-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Filter Peran (Role)</label>
                <select name="role_id" class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white focus:outline-none focus:border-slate-500">
                    <option value="">Semua Peran</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->role_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Filter Kelas</label>
                <select name="class_id" class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white focus:outline-none focus:border-slate-500">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->class_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium transition">
                    Cari
                </button>
                @if(request()->hasAny(['search', 'role_id', 'class_id']))
                    <a href="{{ route('waka.master.users') }}" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 px-3.5 py-1.5 rounded-md text-sm font-medium transition">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Tabel Pengguna -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-sm font-semibold text-slate-800">Daftar Akun Pengguna</h2>
            <span class="text-xs text-slate-500">{{ $users->count() }} pengguna</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">NIS / NIP</th>
                        <th class="px-4 py-3">Nama Lengkap</th>
                        <th class="px-4 py-3">Username</th>
                        <th class="px-4 py-3">Peran</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($users as $index => $user)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                @if($user->role?->role_name === 'Siswa' || $user->role?->role_name === 'Orang Tua')
                                    {{ $user->nis ?? '-' }}
                                @elseif(in_array($user->role?->role_name, ['Guru', 'Guru BK', 'Wali Kelas', 'Waka Kurikulum'], true))
                                    {{ $user->nip ?? '-' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">
                                    {{ $user->fullname }}
                                    @if($user->id === auth()->id())
                                        <span class="text-slate-400 text-xs font-normal">(Anda)</span>
                                    @endif
                                </div>
                                @if($user->email)
                                    <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $user->username }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium border border-slate-200 bg-slate-50 text-slate-700">
                                        {{ $user->role->role_name ?? '-' }}
                                    </span>
                                    @foreach($user->additionalRoles as $additionalRole)
                                        <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium border border-blue-200 bg-blue-50 text-blue-700">
                                            + {{ $additionalRole->role_name }}
                                        </span>
                                    @endforeach
                                </div>
                                @if($user->subject)
                                    <div class="text-xs text-slate-500 mt-0.5 line-clamp-1" title="{{ $user->subject }}">{{ $user->subject }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($user->role?->role_name === 'Guru BK' && $user->bkClasses->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @foreach($user->bkClasses->take(3) as $bkClass)
                                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $bkClass->class_name }}</span>
                                        @endforeach
                                        @if($user->bkClasses->count() > 3)
                                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-500" title="{{ $user->bkClasses->pluck('class_name')->implode(', ') }}">
                                                +{{ $user->bkClasses->count() - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @elseif($user->class)
                                    <span class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                        {{ $user->class->class_name }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex justify-end gap-1.5">
                                    @if(in_array($user->role?->role_name, ['Guru', 'Wali Kelas', 'Guru BK'], true))
                                        @php($hasWakaAccess = $user->isWaka())
                                        <form action="{{ route('waka.master.users.waka-access', $user->id) }}" method="POST"
                                              onsubmit="return confirm('Konfirmasi perubahan akses Waka untuk pengguna ini?')">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="enabled" value="{{ $hasWakaAccess ? '0' : '1' }}">
                                            <button type="submit"
                                                    class="rounded border px-2.5 py-1 text-xs font-medium {{ $hasWakaAccess ? 'border-slate-300 bg-slate-100 text-slate-700 hover:bg-slate-200' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                                                {{ $hasWakaAccess ? 'Cabut Waka' : '+ Akses Waka' }}
                                            </button>
                                        </form>
                                    @endif
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('waka.master.users.delete', $user->id) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Yakin hapus pengguna ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded border border-red-200 bg-white px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                Tidak ada data pengguna yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
