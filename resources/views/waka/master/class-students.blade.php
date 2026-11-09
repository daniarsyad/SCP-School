@extends('layouts.waka')

@section('title', 'Detail Kelas & Presensi - ' . $class->class_name)

@section('content')
    <div class="mb-5">
        <a href="{{ route('waka.master.classes') }}" class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-700 hover:underline text-xs font-medium mb-2 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Data Kelas
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-1">
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-xl font-bold text-slate-900">Detail Kelas {{ $class->class_name }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                        Tingkat {{ $class->grade }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                        {{ $class->major }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                        Rombel {{ $class->section }}
                    </span>
                </div>
                <p class="text-slate-500 text-sm mt-1">Profil kelas, rekapitulasi kehadiran siswa, dan riwayat presensi jurnal mengajar.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('waka.master.classes.print-attendance', array_merge(['id' => $class->id], request()->all(), ['print' => 1])) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition shadow-2xs"
                   title="Cetak atau unduh rekapitulasi presensi siswa kelas ini dalam format PDF">
                    <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Cetak Presensi (PDF)</span>
                </a>
                <a href="{{ route('waka.master.classes.print-journals', array_merge(['id' => $class->id], request()->all(), ['print' => 1])) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition shadow-2xs"
                   title="Cetak atau unduh seluruh riwayat jurnal mengajar kelas ini">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span>Cetak Jurnal (PDF)</span>
                </a>
                <a href="{{ route('waka.schedules.index', ['class_id' => $class->id]) }}"
                   class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Jadwal KBM</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Ringkasan Info Kelas & Presensi -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mb-6">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Wali Kelas</span>
                <span class="p-1.5 bg-blue-50 text-blue-600 rounded-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </span>
            </div>
            <div class="text-base font-semibold text-slate-900 mt-2 truncate" title="{{ $class->homeroomTeacher?->fullname ?? 'Belum ditentukan' }}">
                {{ $class->homeroomTeacher?->fullname ?? 'Belum ditentukan' }}
            </div>
            <p class="text-xs text-slate-400 mt-0.5 truncate">
                {{ $class->homeroomTeacher?->nip ? 'NIP: ' . $class->homeroomTeacher->nip : ($class->homeroomTeacher?->phone ?? 'Wali kelas aktif') }}
            </p>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Total Siswa Aktif</span>
                <span class="p-1.5 bg-emerald-50 text-emerald-600 rounded-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">
                {{ $students->count() }} <span class="text-xs font-normal text-slate-500">siswa</span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Terdaftar aktif di kelas ini</p>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Jadwal Mingguan</span>
                <span class="p-1.5 bg-purple-50 text-purple-600 rounded-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">
                {{ $schedules->count() }} <span class="text-xs font-normal text-slate-500">sesi/minggu</span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">{{ $schedules->pluck('subject')->unique()->count() }} mata pelajaran</p>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Tingkat Kehadiran Kelas</span>
                <span class="p-1.5 bg-amber-50 text-amber-600 rounded-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-2xl font-bold {{ $classAttendanceRate >= 85 ? 'text-emerald-600' : ($classAttendanceRate >= 70 ? 'text-amber-600' : 'text-slate-900') }}">
                    {{ $classAttendanceRate }}%
                </span>
                <span class="text-xs text-slate-500">rata-rata kehadiran</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="h-1.5 rounded-full {{ $classAttendanceRate >= 85 ? 'bg-emerald-500' : ($classAttendanceRate >= 70 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ min(100, max(0, $classAttendanceRate)) }}%"></div>
            </div>
            <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mt-2 flex-wrap">
                <span class="text-emerald-700 font-medium">H: {{ $classTotalHadir }}</span> ·
                <span class="text-amber-700 font-medium">I: {{ $classTotalIzin }}</span> ·
                <span class="text-blue-700 font-medium">S: {{ $classTotalSakit }}</span> ·
                <span class="text-red-700 font-medium">A: {{ $classTotalAlpha }}</span>
            </div>
        </div>
    </div>

    <!-- Tab Navigasi -->
    <div class="border-b border-slate-200 mb-5">
        <nav class="flex gap-4 -mb-px" aria-label="Tabs">
            <button type="button"
                    onclick="switchTab('tab-students')"
                    id="btn-tab-students"
                    class="tab-btn pb-3 px-1 border-b-2 font-medium text-sm border-blue-600 text-blue-600 flex items-center gap-2">
                <span>Daftar Siswa & Rekap Presensi</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-50 text-blue-700">{{ $students->count() }}</span>
            </button>
            <button type="button"
                    onclick="switchTab('tab-history')"
                    id="btn-tab-history"
                    class="tab-btn pb-3 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 flex items-center gap-2">
                <span>Riwayat Presensi per Sesi Jurnal</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600">{{ $journalHistory->total() }}</span>
            </button>
            <button type="button"
                    onclick="switchTab('tab-schedules')"
                    id="btn-tab-schedules"
                    class="tab-btn pb-3 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 flex items-center gap-2">
                <span>Jadwal Pelajaran Kelas</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600">{{ $schedules->count() }}</span>
            </button>
        </nav>
    </div>

    <!-- Tab 1: Daftar Siswa & Rekap Presensi -->
    <div id="tab-students" class="tab-pane">
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Daftar Siswa & Rekapitulasi Presensi</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Akumulasi presensi siswa selama KBM di kelas ini.</p>
                </div>
                <div class="w-full sm:w-64">
                    <input type="text"
                           id="searchStudentInput"
                           onkeyup="filterStudentTable()"
                           placeholder="Cari nama atau NIS..."
                           class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-xs bg-white focus:border-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[720px]" id="studentsTable">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">No</th>
                            <th class="px-4 py-3 w-36">NIS</th>
                            <th class="px-4 py-3">Nama Siswa</th>
                            <th class="px-4 py-3 w-36">Username</th>
                            <th class="px-3 py-3 w-20 text-center font-semibold text-emerald-700 bg-emerald-50/50">Hadir</th>
                            <th class="px-3 py-3 w-20 text-center font-semibold text-amber-700 bg-amber-50/50">Izin</th>
                            <th class="px-3 py-3 w-20 text-center font-semibold text-blue-700 bg-blue-50/50">Sakit</th>
                            <th class="px-3 py-3 w-20 text-center font-semibold text-red-700 bg-red-50/50">Alpha</th>
                            <th class="px-4 py-3 w-28 text-center">% Hadir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($students as $index => $student)
                            @php
                                $stats = $studentStats[$student->id] ?? [
                                    'hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0, 'total' => 0, 'percentage' => 0,
                                ];
                            @endphp
                            <tr class="student-row hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600 font-mono student-nis">{{ $student->nis ?? '-' }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800 student-name">{{ $student->fullname }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500 font-mono">{{ $student->username }}</td>
                                <td class="px-3 py-3 text-center text-xs font-semibold text-emerald-700 bg-emerald-50/30">
                                    {{ $stats['hadir'] }}
                                </td>
                                <td class="px-3 py-3 text-center text-xs font-semibold text-amber-700 bg-amber-50/30">
                                    {{ $stats['izin'] }}
                                </td>
                                <td class="px-3 py-3 text-center text-xs font-semibold text-blue-700 bg-blue-50/30">
                                    {{ $stats['sakit'] }}
                                </td>
                                <td class="px-3 py-3 text-center text-xs font-semibold text-red-700 bg-red-50/30">
                                    {{ $stats['alpha'] }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $stats['percentage'] >= 85 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($stats['percentage'] >= 75 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-red-50 text-red-700 border border-red-200') }}">
                                        {{ $stats['percentage'] }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-slate-500">
                                    Belum ada siswa terdaftar di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Riwayat Presensi per Sesi Jurnal -->
    <div id="tab-history" class="tab-pane hidden">
        <!-- Filter Tanggal -->
        <form method="GET" class="bg-white rounded-lg border border-slate-200 p-4 mb-4">
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-48">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-xs bg-white focus:border-blue-500 focus:outline-none">
                </div>
                <div class="w-48">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-xs bg-white focus:border-blue-500 focus:outline-none">
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-md text-xs font-medium transition">
                        Terapkan Filter
                    </button>
                    @if(request()->hasAny(['start_date', 'end_date']))
                        <a href="{{ route('waka.master.classes.students', $class->id) }}" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 px-3 py-1.5 rounded-md text-xs font-medium transition">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>

        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 flex flex-wrap justify-between items-center gap-2">
                <div class="flex items-center gap-2.5">
                    <h2 class="text-sm font-semibold text-slate-800">Daftar Sesi Pembelajaran & Presensi Siswa</h2>
                    <span class="text-xs text-slate-500">{{ $journalHistory->total() }} sesi tercatat</span>
                </div>
                @if($journalHistory->total() > 0)
                    <a href="{{ route('waka.master.classes.print-journals', array_merge(['id' => $class->id], request()->all(), ['print' => 1])) }}"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition shadow-2xs"
                       title="Cetak jurnal mengajar kelas ini dalam format PDF">
                        <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Cetak Jurnal Kelas (PDF)</span>
                    </a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[750px]">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">No</th>
                            <th class="px-4 py-3 w-36">Tanggal & Waktu</th>
                            <th class="px-4 py-3">Mata Pelajaran & Pengajar</th>
                            <th class="px-4 py-3">Materi / Topik</th>
                            <th class="px-4 py-3 w-44">Rekap Kehadiran</th>
                            <th class="px-4 py-3 w-28 text-center">Lampiran</th>
                            <th class="px-4 py-3 w-32 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($journalHistory as $index => $journal)
                            @php
                                $atts = $journal->attendances ?? collect();
                                $h = $atts->where('status', 'Hadir')->count();
                                $i = $atts->where('status', 'Izin')->count();
                                $s = $atts->where('status', 'Sakit')->count();
                                $a = $atts->where('status', 'Alpha')->count();
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3 text-center text-xs text-slate-400">
                                    {{ $journalHistory->firstItem() + $index }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <div class="font-medium text-slate-800">
                                        {{ \Carbon\Carbon::parse($journal->date)->translatedFormat('d M Y') }}
                                    </div>
                                    <div class="text-slate-500 mt-0.5">
                                        {{ substr($journal->schedule->start_time ?? '', 0, 5) }} – {{ substr($journal->schedule->end_time ?? '', 0, 5) }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ $journal->schedule->room->room_name ?? 'Ruangan -' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $journal->schedule->subject ?? '-' }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $journal->schedule->teacher->fullname ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 max-w-xs">
                                    <div class="font-medium text-slate-800 truncate" title="{{ $journal->topic_description }}">
                                        {{ $journal->topic_description }}
                                    </div>
                                    @if($journal->activity_notes)
                                        <div class="text-slate-400 text-[11px] truncate mt-0.5" title="{{ $journal->activity_notes }}">
                                            Catatan: {{ $journal->activity_notes }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1 text-xs flex-wrap">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-medium" title="Hadir: {{ $h }}">H: {{ $h }}</span>
                                        @if($i > 0)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 font-medium" title="Izin: {{ $i }}">I: {{ $i }}</span>
                                        @endif
                                        @if($s > 0)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-medium" title="Sakit: {{ $s }}">S: {{ $s }}</span>
                                        @endif
                                        @if($a > 0)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-red-50 text-red-700 font-medium" title="Alpha: {{ $a }}">A: {{ $a }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($journal->photo_path)
                                        <a href="{{ asset('storage/' . $journal->photo_path) }}" target="_blank" rel="noopener" class="inline-block" title="Lihat foto kegiatan">
                                            <img src="{{ asset('storage/' . $journal->photo_path) }}" alt="Foto jurnal" class="h-9 w-12 object-cover rounded border border-slate-200 hover:opacity-80 transition">
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button type="button"
                                            onclick="openSessionAttendanceModal({{ $journal->id }})"
                                            class="inline-flex items-center gap-1 rounded border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 hover:border-blue-300 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Presensi
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                    Belum ada riwayat jurnal dan presensi untuk kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($journalHistory->hasPages())
                <div class="px-5 py-3 border-t border-slate-200">
                    {{ $journalHistory->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Tab 3: Jadwal Pelajaran Kelas -->
    <div id="tab-schedules" class="tab-pane hidden">
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-sm font-semibold text-slate-800">Jadwal Pelajaran Mingguan Kelas {{ $class->class_name }}</h2>
                <span class="text-xs text-slate-500">{{ $schedules->count() }} sesi KBM</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[650px]">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">No</th>
                            <th class="px-4 py-3 w-28">Hari</th>
                            <th class="px-4 py-3 w-36">Waktu</th>
                            <th class="px-4 py-3">Mata Pelajaran</th>
                            <th class="px-4 py-3">Guru Pengajar</th>
                            <th class="px-4 py-3 w-32">Ruangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($schedules as $index => $sch)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 text-xs font-medium text-slate-800">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium text-xs">
                                        {{ $sch->day_of_week }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">
                                    {{ substr($sch->start_time, 0, 5) }} – {{ substr($sch->end_time, 0, 5) }}
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $sch->subject }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $sch->teacher->fullname ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $sch->room->room_name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                    Belum ada jadwal KBM yang diatur untuk kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Rincian Presensi Sesi Jurnal (Tab 2) -->
    <div id="sessionAttendanceModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4 backdrop-blur-xs">
        <div class="bg-white rounded-xl border border-slate-200 w-full max-w-3xl shadow-xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/70">
                <div>
                    <h3 class="text-base font-semibold text-slate-900" id="modalSessionTitle">Daftar Presensi Siswa</h3>
                    <p class="text-xs text-slate-500 mt-0.5" id="modalSessionSubtitle">-</p>
                </div>
                <button type="button" onclick="closeSessionAttendanceModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto space-y-4">
                <!-- Info Jurnal Ringkas -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 rounded-lg p-3.5 border border-slate-200 text-xs">
                    <div>
                        <span class="text-slate-500 block">Materi / Topik:</span>
                        <span class="font-medium text-slate-800" id="modalTopic">-</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Catatan Kegiatan:</span>
                        <span class="font-medium text-slate-800" id="modalNotes">-</span>
                    </div>
                </div>

                <!-- Ringkasan Status Presensi -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <div class="p-2.5 rounded-lg border border-emerald-200 bg-emerald-50/50 text-center">
                        <span class="text-[11px] font-medium text-emerald-700">Hadir</span>
                        <div class="text-lg font-bold text-emerald-800" id="modalCountHadir">0</div>
                    </div>
                    <div class="p-2.5 rounded-lg border border-amber-200 bg-amber-50/50 text-center">
                        <span class="text-[11px] font-medium text-amber-700">Izin</span>
                        <div class="text-lg font-bold text-amber-800" id="modalCountIzin">0</div>
                    </div>
                    <div class="p-2.5 rounded-lg border border-blue-200 bg-blue-50/50 text-center">
                        <span class="text-[11px] font-medium text-blue-700">Sakit</span>
                        <div class="text-lg font-bold text-blue-800" id="modalCountSakit">0</div>
                    </div>
                    <div class="p-2.5 rounded-lg border border-red-200 bg-red-50/50 text-center">
                        <span class="text-[11px] font-medium text-red-700">Alpha</span>
                        <div class="text-lg font-bold text-red-800" id="modalCountAlpha">0</div>
                    </div>
                </div>

                <!-- Input Pencarian Siswa di Modal -->
                <div class="flex items-center justify-between gap-3 pt-2">
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Daftar Kehadiran Siswa</h4>
                    <input type="text"
                           id="modalSearchStudentInput"
                           onkeyup="filterModalStudents()"
                           placeholder="Filter siswa di sesi ini..."
                           class="border border-slate-300 rounded-md px-2.5 py-1 text-xs bg-white w-56 focus:border-blue-500 focus:outline-none">
                </div>

                <!-- Tabel Siswa Modal -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-xs text-left border-collapse" id="modalStudentsTable">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                            <tr>
                                <th class="px-3 py-2 w-10 text-center">No</th>
                                <th class="px-3 py-2 w-28">NIS</th>
                                <th class="px-3 py-2">Nama Siswa</th>
                                <th class="px-3 py-2 w-28 text-center">Status</th>
                                <th class="px-3 py-2">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700" id="modalStudentsBody">
                            <!-- Diisi via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 border-t border-slate-200 bg-slate-50/70 flex justify-end">
                <button type="button" onclick="closeSessionAttendanceModal()" class="px-4 py-1.5 rounded-md border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    @php
        $journalPayload = $journalHistory->getCollection()->mapWithKeys(function ($j) {
            $atts = $j->attendances ?? collect();
            return [$j->id => [
                'id' => $j->id,
                'title' => ($j->schedule->subject ?? 'Mata Pelajaran') . ' · ' . ($j->schedule->teacher->fullname ?? 'Guru'),
                'subtitle' => \Carbon\Carbon::parse($j->date)->translatedFormat('l, d F Y') . ' (' . substr($j->schedule->start_time ?? '', 0, 5) . ' – ' . substr($j->schedule->end_time ?? '', 0, 5) . ') · ' . ($j->schedule->room->room_name ?? '-'),
                'topic' => $j->topic_description ?? '-',
                'notes' => $j->activity_notes ?? 'Tidak ada catatan.',
                'counts' => [
                    'hadir' => $atts->where('status', 'Hadir')->count(),
                    'izin' => $atts->where('status', 'Izin')->count(),
                    'sakit' => $atts->where('status', 'Sakit')->count(),
                    'alpha' => $atts->where('status', 'Alpha')->count(),
                ],
                'students' => $atts->map(function ($att) {
                    return [
                        'nis' => $att->student->nis ?? '-',
                        'name' => $att->student->fullname ?? 'Siswa Tidak Diketahui',
                        'status' => $att->status,
                        'notes' => $att->notes ?? '-',
                    ];
                })->values(),
            ]];
        });
    @endphp

    <script>
        const sessionJournalData = @json($journalPayload);

        function switchTab(tabId) {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-blue-600', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-slate-500');
            });

            const activePane = document.getElementById(tabId);
            if (activePane) {
                activePane.classList.remove('hidden');
            }

            const activeBtn = document.getElementById('btn-' + tabId);
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent', 'text-slate-500');
                activeBtn.classList.add('border-blue-600', 'text-blue-600');
            }
        }

        function filterStudentTable() {
            const query = document.getElementById('searchStudentInput').value.toLowerCase().trim();
            const rows = document.querySelectorAll('#studentsTable .student-row');

            rows.forEach(row => {
                const name = row.querySelector('.student-name')?.innerText.toLowerCase() || '';
                const nis = row.querySelector('.student-nis')?.innerText.toLowerCase() || '';

                if (name.includes(query) || nis.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function openSessionAttendanceModal(journalId) {
            const data = sessionJournalData[journalId];
            if (!data) return;

            document.getElementById('modalSessionTitle').innerText = data.title;
            document.getElementById('modalSessionSubtitle').innerText = data.subtitle;
            document.getElementById('modalTopic').innerText = data.topic;
            document.getElementById('modalNotes').innerText = data.notes;

            document.getElementById('modalCountHadir').innerText = data.counts.hadir;
            document.getElementById('modalCountIzin').innerText = data.counts.izin;
            document.getElementById('modalCountSakit').innerText = data.counts.sakit;
            document.getElementById('modalCountAlpha').innerText = data.counts.alpha;

            const tbody = document.getElementById('modalStudentsBody');
            tbody.innerHTML = '';

            if (data.students && data.students.length > 0) {
                data.students.forEach((st, idx) => {
                    let badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
                    if (st.status === 'Hadir') badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    else if (st.status === 'Izin') badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                    else if (st.status === 'Sakit') badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                    else if (st.status === 'Alpha') badgeClass = 'bg-red-50 text-red-700 border-red-200';

                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50/70 transition modal-student-row';
                    tr.innerHTML = `
                        <td class="px-3 py-2 text-center text-slate-400">${idx + 1}</td>
                        <td class="px-3 py-2 font-mono text-slate-600 modal-st-nis">${st.nis}</td>
                        <td class="px-3 py-2 font-medium text-slate-800 modal-st-name">${st.name}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded border text-[11px] font-medium ${badgeClass}">
                                ${st.status}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-slate-500">${st.notes && st.notes !== '-' ? st.notes : '<span class="text-slate-300">-</span>'}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-400">
                            Tidak ada data presensi siswa yang tersimpan untuk sesi ini.
                        </td>
                    </tr>
                `;
            }

            document.getElementById('modalSearchStudentInput').value = '';
            const modal = document.getElementById('sessionAttendanceModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeSessionAttendanceModal() {
            const modal = document.getElementById('sessionAttendanceModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function filterModalStudents() {
            const query = document.getElementById('modalSearchStudentInput').value.toLowerCase().trim();
            const rows = document.querySelectorAll('#modalStudentsBody .modal-student-row');

            rows.forEach(row => {
                const name = row.querySelector('.modal-st-name')?.innerText.toLowerCase() || '';
                const nis = row.querySelector('.modal-st-nis')?.innerText.toLowerCase() || '';

                if (name.includes(query) || nis.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Tutup modal jika klik di luar box
        document.getElementById('sessionAttendanceModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSessionAttendanceModal();
            }
        });
    </script>
@endsection
