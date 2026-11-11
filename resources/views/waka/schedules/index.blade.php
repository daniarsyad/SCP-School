@extends('layouts.waka')

@section('title', 'Jadwal Mengajar - SCP')

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-5">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Jadwal Mengajar</h1>
            <p class="text-slate-500 text-sm mt-0.5">
                Kelola jadwal pelajaran berdasarkan putaran, hari, kelas, pengajar, dan ruangan.
            </p>
        </div>

        @php
            $isCycle1Locked = $config->isCycleLocked('1');
            $isCycle2Locked = $config->isCycleLocked('2');
            $isCurrentLocked = $config->isCycleLocked($cycle);
            $canAddSchedule = !$isCycle1Locked || !$isCycle2Locked;
        @endphp

        <div class="flex flex-wrap items-center gap-2">
            @if($isCurrentLocked)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 text-xs font-medium">
                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Putaran {{ $cycle }} Terkunci</span>
                </span>
            @endif

            @if(!$isCurrentLocked)
                <button
                    type="button"
                    onclick="document.getElementById('importForm').classList.toggle('hidden')"
                    class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-medium px-3.5 py-2 rounded-md text-sm transition"
                >
                    Import Excel
                </button>
            @endif

            @if($canAddSchedule)
                <button
                    type="button"
                    onclick="document.getElementById('addForm').classList.toggle('hidden')"
                    class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white font-medium px-3.5 py-2 rounded-md text-sm transition"
                >
                    + Tambah Jadwal
                </button>
            @endif
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Total Sesi Ditampilkan</div>
            <div class="text-xl font-semibold text-slate-900 mt-1">{{ $schedules->count() }} <span class="text-xs font-normal text-slate-400">sesi</span></div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Target Siklus</div>
            <div class="text-xl font-semibold text-slate-900 mt-1">Putaran {{ $cycle }}</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Kelas Terjadwal</div>
            <div class="text-xl font-semibold text-slate-900 mt-1">{{ $schedules->pluck('class_id')->unique()->count() }} <span class="text-xs font-normal text-slate-400">kelas</span></div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Ruangan Terpakai</div>
            <div class="text-xl font-semibold text-slate-900 mt-1">{{ $schedules->pluck('room_id')->unique()->count() }} <span class="text-xs font-normal text-slate-400">ruang</span></div>
        </div>
    </div>

    <!-- Filter & Mode Tampilan -->
    <form method="GET" class="bg-white rounded-lg border border-slate-200 p-4 mb-5">
        <input type="hidden" name="cycle" value="{{ $cycle }}">
        <input type="hidden" name="view" value="{{ $viewMode ?? 'matrix' }}">

        <div class="flex flex-wrap items-center justify-between gap-3 pb-3.5 mb-3.5 border-b border-slate-200">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-slate-500">Putaran:</span>
                    <div class="inline-flex rounded-md border border-slate-200 bg-slate-50 p-0.5">
                        <a href="{{ request()->fullUrlWithQuery(['cycle' => '1']) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded transition {{ $cycle == '1' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/80' : 'text-slate-600 hover:text-slate-900' }}">
                            <span>Putaran 1</span>
                            @if($isCycle1Locked)
                                <span class="inline-flex items-center text-[10px] text-red-600 bg-red-50 border border-red-200 px-1 py-0.5 rounded font-semibold leading-none">Terkunci</span>
                            @else
                                <span class="inline-flex items-center text-[10px] text-emerald-600 bg-emerald-50 border border-emerald-200 px-1 py-0.5 rounded font-semibold leading-none">Terbuka</span>
                            @endif
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['cycle' => '2']) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded transition {{ $cycle == '2' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/80' : 'text-slate-600 hover:text-slate-900' }}">
                            <span>Putaran 2</span>
                            @if($isCycle2Locked)
                                <span class="inline-flex items-center text-[10px] text-red-600 bg-red-50 border border-red-200 px-1 py-0.5 rounded font-semibold leading-none">Terkunci</span>
                            @else
                                <span class="inline-flex items-center text-[10px] text-emerald-600 bg-emerald-50 border border-emerald-200 px-1 py-0.5 rounded font-semibold leading-none">Terbuka</span>
                            @endif
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-slate-500">Tampilan:</span>
                    <div class="inline-flex rounded-md border border-slate-200 bg-slate-50 p-0.5">
                        <a href="{{ request()->fullUrlWithQuery(['view' => 'matrix']) }}"
                           class="px-3 py-1 text-xs font-medium rounded transition {{ ($viewMode ?? 'matrix') === 'matrix' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/80' : 'text-slate-600 hover:text-slate-900' }}">
                            Matriks Jam 1–10
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"
                           class="px-3 py-1 text-xs font-medium rounded transition {{ ($viewMode ?? 'matrix') === 'list' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/80' : 'text-slate-600 hover:text-slate-900' }}">
                            Daftar Tabel
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Hari</label>
                <select name="day" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="">Semua Hari</option>
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d)
                        <option value="{{ $d }}" {{ $dayFilter == $d ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Tingkat</label>
                <select name="grade" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="">Semua</option>
                    <option value="X" {{ request('grade') == 'X' ? 'selected' : '' }}>Kelas X</option>
                    <option value="XI" {{ request('grade') == 'XI' ? 'selected' : '' }}>Kelas XI</option>
                    <option value="XII" {{ request('grade') == 'XII' ? 'selected' : '' }}>Kelas XII</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Jurusan</label>
                <select name="major" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="">Semua</option>
                    @foreach(['RPL','TKJ','DKV','AKL','MP','BR','BD','LPS'] as $m)
                        <option value="{{ $m }}" {{ request('major') == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Rombel</label>
                <select name="section" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="">Semua</option>
                    @foreach(['1','2','3','4'] as $sec)
                        <option value="{{ $sec }}" {{ request('section') == $sec ? 'selected' : '' }}>{{ $sec }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Kelas</label>
                <select name="class_id" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="">Semua Kelas ({{ $classes->count() }})</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $classFilter == $c->id ? 'selected' : '' }}>{{ $c->class_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Pengajar</label>
                <select name="teacher_id" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="">Semua Pengajar</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" {{ ($teacherFilter ?? '') == $t->id ? 'selected' : '' }}>{{ $t->fullname }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Ruangan</label>
                <select name="room_id" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="">Semua Ruangan</option>
                    @foreach($rooms as $r)
                        <option value="{{ $r->id }}" {{ ($roomFilter ?? '') == $r->id ? 'selected' : '' }}>{{ $r->room_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-2">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium transition">
                Terapkan Filter
            </button>
            <a href="{{ route('waka.schedules.index', ['cycle' => $cycle, 'view' => $viewMode ?? 'matrix']) }}"
               class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 px-3.5 py-1.5 rounded-md text-sm font-medium transition">
                Reset
            </a>
        </div>
    </form>

    @if(!$isCurrentLocked)
        <div id="importForm" class="hidden bg-white rounded-lg border border-slate-200 p-5 mb-5">
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-slate-800">Import Jadwal Massal</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Unggah file Excel/CSV. Guru harus sudah terdaftar sebagai Guru, Wali Kelas, atau Guru BK.
                </p>
            </div>
            <form action="{{ route('waka.schedules.import') }}" method="POST" enctype="multipart/form-data"
                  class="flex flex-col sm:flex-row sm:items-end gap-3">
                @csrf
                <a href="{{ route('waka.schedules.import.template') }}"
                   class="whitespace-nowrap rounded-md border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    Unduh Template
                </a>
                <div class="flex-1">
                    <label for="scheduleImportFile" class="block text-xs font-medium text-slate-600 mb-1">File jadwal (.xlsx, .xls, .csv)</label>
                    <input id="scheduleImportFile" type="file" name="file" required accept=".xlsx,.xls,.csv"
                           class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    Import Jadwal
                </button>
            </form>
        </div>
    @endif

    @if($canAddSchedule)
        <!-- Form Tambah -->
        <div id="addForm" class="hidden bg-white rounded-lg border border-slate-200 p-5 mb-5">
            <h2 class="text-sm font-semibold text-slate-800 mb-4">Tambah Jadwal Baru</h2>
            <form action="{{ route('waka.schedules.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Putaran</label>
                    <select name="cycle_type" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                        <option value="1" {{ ($cycle == '1' && !$isCycle1Locked) || ($isCycle2Locked && !$isCycle1Locked) ? 'selected' : '' }} {{ $isCycle1Locked ? 'disabled' : '' }}>
                            Putaran 1 {{ $isCycle1Locked ? '(Terkunci)' : '' }}
                        </option>
                        <option value="2" {{ ($cycle == '2' && !$isCycle2Locked) || ($isCycle1Locked && !$isCycle2Locked) ? 'selected' : '' }} {{ $isCycle2Locked ? 'disabled' : '' }}>
                            Putaran 2 {{ $isCycle2Locked ? '(Terkunci)' : '' }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Pengajar</label>
                    <select name="teacher_id" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                        <option value="">-- Pilih --</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->fullname }} ({{ $t->role->role_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Kelas</label>
                    <select name="class_id" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                        <option value="">-- Pilih --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Ruangan</label>
                    <select name="room_id" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                        <option value="">-- Pilih --</option>
                        @foreach($rooms as $r)
                            <option value="{{ $r->id }}">{{ $r->room_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Mata Pelajaran</label>
                    <input type="text" name="subject" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Hari</label>
                    <select name="day_of_week" id="daySelect" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white" onchange="loadPeriods()">
                        <option value="">-- Pilih --</option>
                        @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d)
                            <option value="{{ $d }}">{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Jam Mulai</label>
                    <select name="period_start_id" id="periodStart" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                        <option value="">-- Pilih Hari dulu --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Jam Selesai</label>
                    <select name="period_end_id" id="periodEnd" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                        <option value="">-- Pilih Hari dulu --</option>
                    </select>
                </div>
                <div class="md:col-span-4 flex justify-end pt-1">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium">Simpan Jadwal</button>
                </div>
            </form>
        </div>

        <script>
            const periodsByDay = @json($periodsByDayJson ?? []);

            function loadPeriods() {
                const day = document.getElementById('daySelect').value;
                const startSel = document.getElementById('periodStart');
                const endSel = document.getElementById('periodEnd');

                startSel.innerHTML = '<option value="">-- Pilih --</option>';
                endSel.innerHTML = '<option value="">-- Pilih --</option>';

                if (!day || !periodsByDay[day]) return;

                periodsByDay[day].forEach(p => {
                    const opt = `<option value="${p.id}" data-start="${p.start}">${p.label} (${p.start} – ${p.end})</option>`;
                    startSel.innerHTML += opt;
                });
                filterEndPeriods();
            }

            function filterEndPeriods() {
                const day = document.getElementById('daySelect').value;
                const startSel = document.getElementById('periodStart');
                const endSel = document.getElementById('periodEnd');

                const selectedStart = startSel.options[startSel.selectedIndex];
                const startTime = selectedStart ? selectedStart.dataset.start : null;
                const startId = startSel.value;

                endSel.innerHTML = '<option value="">-- Pilih --</option>';

                if (!day || !periodsByDay[day] || !startId) return;

                periodsByDay[day].forEach(p => {
                    if (startTime && p.start >= startTime) {
                        const opt = `<option value="${p.id}">${p.label} (${p.start} – ${p.end})</option>`;
                        endSel.innerHTML += opt;
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                const startSel = document.getElementById('periodStart');
                if (startSel) startSel.addEventListener('change', filterEndPeriods);
            });
        </script>
    @endif

    @if(($viewMode ?? 'matrix') === 'list')
        <!-- MODE TABEL DAFTAR JADWAL -->
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold text-slate-800 text-sm">
                    Daftar Jadwal Pelajaran — Putaran {{ $cycle }}
                </h2>
                <span class="text-xs text-slate-500">
                    {{ $schedules->count() }} sesi jadwal
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-slate-50 text-xs font-medium text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">No</th>
                            <th class="px-4 py-3 w-24">Hari</th>
                            <th class="px-4 py-3 w-36">Jam Pelajaran</th>
                            <th class="px-4 py-3 w-32">Waktu</th>
                            <th class="px-4 py-3 w-28">Kelas</th>
                            <th class="px-4 py-3">Mata Pelajaran</th>
                            <th class="px-4 py-3">Pengajar</th>
                            <th class="px-4 py-3 w-28">Ruangan</th>
                            @if(!$isCurrentLocked)
                                <th class="px-4 py-3 w-32 text-right">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @php
                            $dayOrder = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5];
                            $sortedSchedules = $schedules->sortBy(fn($s) => sprintf(
                                '%d_%04d_%02d',
                                $dayOrder[$s->day_of_week] ?? 9,
                                $s->class_id,
                                $s->start_period_number
                            ))->values();
                        @endphp
                        @forelse($sortedSchedules as $idx => $s)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $s->day_of_week }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    @if($s->start_period_number === $s->end_period_number)
                                        Jam ke-{{ $s->start_period_number }}
                                    @else
                                        Jam ke-{{ $s->start_period_number }} – {{ $s->end_period_number }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">
                                    {{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-800 text-xs font-medium">
                                        {{ $s->class->class_name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $s->subject }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $s->teacher->fullname ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-700">
                                    {{ $s->room->room_name ?? '-' }}
                                </td>
                                @if(!$isCurrentLocked)
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex justify-end gap-1.5">
                                            <button type="button"
                                                    onclick="openEdit({{ $s->id }}, {{ $s->teacher_id }}, {{ $s->class_id }}, {{ $s->room_id }}, '{{ addslashes($s->subject) }}', '{{ $s->day_of_week }}', {{ $s->period_id ?? 0 }}, {{ $s->end_period_id ?? 0 }}, '{{ $s->cycle_type }}')"
                                                    class="px-2.5 py-1 text-xs font-medium rounded border border-slate-200 bg-white text-slate-700 hover:bg-slate-50">
                                                Edit
                                            </button>
                                            <form action="{{ route('waka.schedules.destroy', $s->id) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Yakin hapus jadwal ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1 text-xs font-medium rounded border border-red-200 bg-white text-red-600 hover:bg-red-50">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-slate-500">
                                    Belum ada jadwal untuk filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- MODE MATRIKS JAM KE-1 S.D. 10 -->
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold text-slate-800 text-sm">
                    Matriks Jadwal Pelajaran (Jam ke-1 s.d. Jam ke-10) — Putaran {{ $cycle }}
                </h2>
                <span class="text-xs text-slate-500">
                    @if($singleEntityMode)
                        Menampilkan jadwal terfokus
                    @else
                        Pilih Kelas, Pengajar, atau Ruangan di filter atas untuk tampilan fokus 1 tabel penuh
                    @endif
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse table-fixed min-w-[960px]">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                        <tr>
                            <th class="px-3 py-3 text-left w-36 border-r border-slate-200">
                                Jam Pelajaran
                            </th>
                            @foreach($days as $day)
                                <th class="px-3 py-3 text-left border-r border-slate-200 last:border-r-0">
                                    {{ $day }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @for($pNum = 1; $pNum <= 10; $pNum++)
                            <tr class="divide-x divide-slate-200">
                                <!-- Kolom Jam Ke-X -->
                                <td class="px-3 py-3 bg-slate-50/50 align-top">
                                    <div class="font-semibold text-xs text-slate-800">
                                        Jam ke-{{ $pNum }}
                                    </div>
                                    <div class="mt-1 space-y-0.5 text-[11px] text-slate-500 leading-tight">
                                        <div>Sen: {{ $periodTimeHints[$pNum]['Senin'] ?? '-' }}</div>
                                        <div>Sel: {{ $periodTimeHints[$pNum]['Selasa'] ?? '-' }}</div>
                                        <div>Rab–Kam: {{ $periodTimeHints[$pNum]['Rabu'] ?? '-' }}</div>
                                        @if($pNum <= 8)
                                            <div>Jum: {{ $periodTimeHints[$pNum]['Jumat'] ?? '-' }}</div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Kolom Hari -->
                                @foreach($days as $day)
                                    @if($singleEntityMode && !empty($skipCells[$pNum][$day]))
                                        @continue
                                    @endif

                                    @php
                                        $cellSchedules = $periodMatrix[$pNum][$day] ?? collect();
                                        $rowspan = 1;
                                        if ($singleEntityMode && $cellSchedules->count() === 1) {
                                            $rowspan = $cellSchedules->first()->period_span ?? 1;
                                        }
                                    @endphp

                                    <td @if($rowspan > 1) rowspan="{{ $rowspan }}" @endif
                                        class="p-2.5 align-top {{ ($day === 'Jumat' && $pNum >= 9) ? 'bg-slate-50/70' : '' }}">
                                        @if($day === 'Jumat' && $pNum >= 9 && $cellSchedules->isEmpty())
                                            <div class="py-3 text-center text-xs text-slate-400">
                                                —
                                            </div>
                                        @elseif($cellSchedules->isEmpty())
                                            <div class="py-3 text-center text-slate-300 text-xs">—</div>
                                        @elseif($singleEntityMode)
                                            <!-- Mode Fokus (1 Kelas / 1 Guru / 1 Ruangan) -->
                                            <div class="space-y-2 h-full">
                                                @foreach($cellSchedules as $s)
                                                    <div class="h-full rounded-md border border-slate-200 bg-slate-50/60 p-3 flex flex-col justify-between">
                                                        <div>
                                                            <div class="flex items-center justify-between gap-2 text-xs text-slate-500">
                                                                <span class="font-medium text-slate-700">
                                                                    @if($s->start_period_number === $s->end_period_number)
                                                                        Jam ke-{{ $s->start_period_number }}
                                                                    @else
                                                                        Jam ke-{{ $s->start_period_number }}–{{ $s->end_period_number }} ({{ $s->period_span }} JP)
                                                                    @endif
                                                                </span>
                                                                <span class="px-1.5 py-0.5 rounded bg-white border border-slate-200 text-slate-700 font-medium text-[11px]">
                                                                    {{ $s->room->room_name ?? '-' }}
                                                                </span>
                                                            </div>

                                                            <div class="font-semibold text-slate-900 text-sm mt-1.5 leading-snug">
                                                                {{ $s->subject }}
                                                            </div>

                                                            <div class="text-xs text-slate-600 mt-1">
                                                                {{ $s->teacher->fullname ?? '-' }}
                                                            </div>
                                                        </div>

                                                        <div class="mt-3 pt-2 border-t border-slate-200/80 flex items-center justify-between gap-2 text-xs">
                                                            <div class="flex items-center gap-1.5 text-slate-600">
                                                                <span class="font-medium text-slate-800">{{ $s->class->class_name ?? '-' }}</span>
                                                                <span class="text-slate-400">·</span>
                                                                <span class="text-[11px] text-slate-500">{{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}</span>
                                                            </div>

                                                            @if(!$isCurrentLocked)
                                                                <div class="flex items-center gap-2">
                                                                    <button type="button"
                                                                            onclick="openEdit({{ $s->id }}, {{ $s->teacher_id }}, {{ $s->class_id }}, {{ $s->room_id }}, '{{ addslashes($s->subject) }}', '{{ $s->day_of_week }}', {{ $s->period_id ?? 0 }}, {{ $s->end_period_id ?? 0 }}, '{{ $s->cycle_type }}')"
                                                                            class="text-blue-600 hover:underline font-medium">
                                                                        Edit
                                                                    </button>
                                                                    <form action="{{ route('waka.schedules.destroy', $s->id) }}" method="POST" class="inline"
                                                                          onsubmit="return confirm('Yakin hapus jadwal ini?')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="text-red-600 hover:underline font-medium">Hapus</button>
                                                                    </form>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <!-- Mode Semua Kelas -->
                                            <div class="flex items-center justify-between mb-1.5 px-0.5">
                                                <span class="text-[11px] font-medium text-slate-500">
                                                    {{ $cellSchedules->count() }} kelas
                                                </span>
                                            </div>
                                            <div class="max-h-52 overflow-y-auto pr-1 space-y-1.5">
                                                @foreach($cellSchedules as $s)
                                                    <div class="p-2 bg-slate-50/70 hover:bg-slate-100/80 rounded border border-slate-200 text-xs transition">
                                                        <div class="flex items-center justify-between gap-1 mb-0.5">
                                                            <a href="{{ request()->fullUrlWithQuery(['class_id' => $s->class_id]) }}"
                                                               title="Fokus ke kelas {{ $s->class->class_name ?? '' }}"
                                                               class="font-semibold text-slate-800 hover:text-blue-600">
                                                                {{ $s->class->class_name ?? '-' }}
                                                            </a>
                                                            <span class="text-[11px] text-slate-500">
                                                                {{ $s->room->room_name ?? '-' }}
                                                            </span>
                                                        </div>
                                                        <div class="font-medium text-slate-700 leading-snug line-clamp-1" title="{{ $s->subject }}">
                                                            {{ $s->subject }}
                                                        </div>
                                                        <div class="text-[11px] text-slate-500 truncate" title="{{ $s->teacher->fullname ?? '-' }}">
                                                            {{ $s->teacher->fullname ?? '-' }}
                                                        </div>
                                                        <div class="flex items-center justify-between mt-1 pt-1 border-t border-slate-200/70 text-[10px] text-slate-400">
                                                            <span>Jam {{ $s->start_period_number }}–{{ $s->end_period_number }}</span>
                                                            @if(!$isCurrentLocked)
                                                                <div class="flex gap-1.5">
                                                                    <button type="button"
                                                                            onclick="openEdit({{ $s->id }}, {{ $s->teacher_id }}, {{ $s->class_id }}, {{ $s->room_id }}, '{{ addslashes($s->subject) }}', '{{ $s->day_of_week }}', {{ $s->period_id ?? 0 }}, {{ $s->end_period_id ?? 0 }}, '{{ $s->cycle_type }}')"
                                                                            class="text-blue-600 hover:underline">
                                                                        Edit
                                                                    </button>
                                                                    <form action="{{ route('waka.schedules.destroy', $s->id) }}" method="POST" class="inline"
                                                                          onsubmit="return confirm('Yakin hapus jadwal ini?')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                                                    </form>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Modal Edit -->
    @if(!$isCurrentLocked || $canAddSchedule)
        <div id="editModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg border border-slate-200 p-6 w-full max-w-lg shadow-lg">
                <h3 class="font-semibold text-base text-slate-800 mb-4">Edit Jadwal</h3>
                <form id="editForm" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Putaran</label>
                        <select name="cycle_type" id="editCycle" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                            <option value="1" {{ $isCycle1Locked ? 'disabled' : '' }}>Putaran 1 {{ $isCycle1Locked ? '(Terkunci)' : '' }}</option>
                            <option value="2" {{ $isCycle2Locked ? 'disabled' : '' }}>Putaran 2 {{ $isCycle2Locked ? '(Terkunci)' : '' }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Pengajar</label>
                        <select name="teacher_id" id="editTeacher" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->fullname }} ({{ $t->role->role_name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Kelas</label>
                        <select name="class_id" id="editClass" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Ruangan</label>
                        <select name="room_id" id="editRoom" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}">{{ $r->room_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Mata Pelajaran</label>
                        <input type="text" name="subject" id="editSubject" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Hari</label>
                        <select name="day_of_week" id="editDay" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white" onchange="loadEditPeriods()">
                            @foreach(['Senin','Selasa','Rabu','Kamis','Jumat'] as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Jam Mulai</label>
                        <select name="period_start_id" id="editPeriodStart" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                            <option value="">-- Pilih --</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Jam Selesai</label>
                        <select name="period_end_id" id="editPeriodEnd" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                            <option value="">-- Pilih --</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-2 pt-2 border-t border-slate-100 mt-1">
                        <button type="button" onclick="closeEdit()" class="px-3.5 py-1.5 text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-md text-sm">Batal</button>
                        <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openEdit(id, teacherId, classId, roomId, subject, day, periodStartId, periodEndId, cycleType) {
                document.getElementById('editForm').action = `/waka/schedules/${id}`;
                document.getElementById('editCycle').value = cycleType;
                document.getElementById('editTeacher').value = teacherId;
                document.getElementById('editClass').value = classId;
                document.getElementById('editRoom').value = roomId;
                document.getElementById('editSubject').value = subject;
                document.getElementById('editDay').value = day;
                loadEditPeriods();
                if (periodStartId) {
                    document.getElementById('editPeriodStart').value = periodStartId;
                }
                if (periodEndId) {
                    document.getElementById('editPeriodEnd').value = periodEndId;
                }
                document.getElementById('editModal').classList.remove('hidden');
                document.getElementById('editModal').classList.add('flex');
            }

            function closeEdit() {
                document.getElementById('editModal').classList.add('hidden');
                document.getElementById('editModal').classList.remove('flex');
            }

            function loadEditPeriods() {
                const day = document.getElementById('editDay').value;
                const startSel = document.getElementById('editPeriodStart');
                const endSel = document.getElementById('editPeriodEnd');

                startSel.innerHTML = '<option value="">-- Pilih --</option>';
                endSel.innerHTML = '<option value="">-- Pilih --</option>';

                if (!day || !periodsByDay[day]) return;

                periodsByDay[day].forEach(p => {
                    const opt = `<option value="${p.id}">${p.label} (${p.start} – ${p.end})</option>`;
                    startSel.innerHTML += opt;
                    endSel.innerHTML += opt;
                });
            }
        </script>
    @endif
@endsection
