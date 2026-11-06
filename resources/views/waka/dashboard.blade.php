@extends('layouts.waka')

@section('title', 'Dashboard - SCP')

@section('content')
    {{-- Header Dashboard --}}
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Dashboard Waka Kurikulum</h1>
        <p class="text-slate-500 text-sm mt-0.5">Selamat datang, {{ auth()->user()->fullname }} · Ringkasan akademik hari ini</p>
    </div>

    {{-- Panel Kontrol Siklus & Status Jadwal (Ringkas & Proporsional) --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex flex-wrap items-center gap-6">
            <div>
                <div class="text-xs font-medium text-slate-500">Siklus Blok Aktif</div>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-lg font-bold text-slate-900">Putaran {{ $config->active_cycle }}</span>
                    <span class="text-xs text-slate-500">
                        ({{ $config->start_date?->format('d M Y') }} – {{ $config->end_date?->format('d M Y') }})
                    </span>
                </div>
            </div>

            <div class="hidden sm:block h-8 w-px bg-slate-200"></div>

            <div>
                <div class="text-xs font-medium text-slate-500">Status Pengelolaan Jadwal</div>
                <div class="flex items-center gap-2 mt-1">
                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold {{ $config->is_schedule_locked ? 'text-red-600' : 'text-emerald-600' }}">
                        <span class="w-2 h-2 rounded-full {{ $config->is_schedule_locked ? 'bg-red-600' : 'bg-emerald-600' }}"></span>
                        {{ $config->is_schedule_locked ? 'Terkunci' : 'Terbuka' }}
                    </span>
                    <span class="text-xs text-slate-400">
                        — {{ $config->is_schedule_locked ? 'Perubahan jadwal dinonaktifkan' : 'Jadwal dapat ditambah & diubah' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if($config->is_schedule_locked)
                <button disabled class="px-4 py-2 rounded-lg border border-slate-200 bg-slate-100 text-slate-400 text-xs font-medium cursor-not-allowed">
                    Ganti Putaran (Jadwal Terkunci)
                </button>
            @else
                <form action="{{ route('waka.toggle.cycle') }}" method="POST"
                      onsubmit="return confirm('Yakin ingin mengganti ke Putaran {{ $config->active_cycle == '1' ? '2' : '1' }}? History putaran saat ini akan disimpan.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition">
                        Ubah ke Putaran {{ $config->active_cycle == '1' ? '2' : '1' }}
                    </button>
                </form>
            @endif

            <form action="{{ route('waka.toggle.lock') }}" method="POST">
                @csrf
                <button type="submit"
                        class="px-4 py-2 rounded-lg text-xs font-semibold transition border
                        {{ $config->is_schedule_locked
                            ? 'bg-emerald-600 hover:bg-emerald-700 text-white border-emerald-600'
                            : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-300' }}">
                    {{ $config->is_schedule_locked ? 'Buka Kunci Jadwal' : 'Kunci Jadwal' }}
                </button>
            </form>
        </div>
    </div>

    {{-- 4 Kartu Statistik Utama --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Jadwal --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-xs font-medium text-slate-500">Total Jadwal Aktif</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">
                        {{ \App\Models\Schedule::where('cycle_type', $config->active_cycle)->count() }}
                    </p>
                </div>
                <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-400">Putaran {{ $config->active_cycle }}</span>
                <a href="{{ route('waka.schedules.index') }}" class="text-blue-600 hover:underline font-medium">Lihat Jadwal →</a>
            </div>
        </div>

        {{-- Jurnal Hari Ini --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-xs font-medium text-slate-500">Jurnal Terisi Hari Ini</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ $todayJournalPercentage }}%</p>
                </div>
                <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ min(100, max(0, $todayJournalPercentage)) }}%"></div>
                </div>
                <p class="text-xs text-slate-400 mt-1.5">{{ $todayJournalCount }} dari {{ $todaySchedules->count() }} sesi terjadwal hari ini</p>
            </div>
        </div>

        {{-- Kehadiran Hari Ini --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-xs font-medium text-slate-500">Kehadiran Siswa Hari Ini</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ $todayAttendancePercentage }}%</p>
                </div>
                <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-600 rounded-full" style="width: {{ min(100, max(0, $todayAttendancePercentage)) }}%"></div>
                </div>
                <p class="text-xs text-slate-400 mt-1.5">{{ $todayHadirCount }} hadir dari {{ $totalTargetStudents }} siswa terjadwal hari ini</p>
            </div>
        </div>

        {{-- Jurnal Terlambat --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-xs font-medium text-slate-500">Jurnal Terlambat (&gt;30 mnt)</p>
                    <p class="text-2xl font-bold {{ $lateJournals->count() > 0 ? 'text-amber-600' : 'text-slate-900' }} mt-1">
                        {{ $lateJournals->count() }}
                    </p>
                </div>
                <div class="w-9 h-9 rounded-lg {{ $lateJournals->count() > 0 ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-400">{{ $lateJournals->count() > 0 ? 'Perlu tindak lanjut' : 'Semua tepat waktu' }}</span>
                <a href="{{ route('waka.journal.recap') }}" class="text-blue-600 hover:underline font-medium">Rekap Jurnal →</a>
            </div>
        </div>
    </div>

    {{-- Tabel Pantauan Keterlambatan Jurnal Hari Ini --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Pantauan Keterlambatan Jurnal Hari Ini</h2>
                <p class="text-xs text-slate-500 mt-0.5">Daftar sesi mengajar hari ini yang belum mengisi jurnal lebih dari 30 menit.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-medium">
                    {{ $lateJournals->count() }} sesi
                </span>
                <a href="{{ route('waka.journal.recap') }}"
                   class="px-3 py-1.5 rounded-md border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium transition">
                    Lihat Detail Rekap
                </a>
            </div>
        </div>

        <div class="overflow-x-auto max-h-[440px] overflow-y-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3 w-14 text-center">No</th>
                        <th class="px-4 py-3">Pengajar</th>
                        <th class="px-4 py-3">Mata Pelajaran</th>
                        <th class="px-4 py-3 w-32">Kelas</th>
                        <th class="px-4 py-3 w-32">Ruangan</th>
                        <th class="px-4 py-3 w-36 text-right">Waktu Mengajar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($lateJournals as $index => $item)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $item->teacher->fullname }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->subject }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-800 text-xs font-medium">
                                    {{ $item->class->class_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded border border-slate-200 bg-slate-50 text-slate-600 text-xs">
                                    {{ $item->room->room_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-xs font-medium text-slate-600 whitespace-nowrap">
                                {{ substr($item->start_time, 0, 5) }} – {{ substr($item->end_time, 0, 5) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500 text-sm">
                                Semua jurnal mengajar hari ini sudah terisi tepat waktu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
