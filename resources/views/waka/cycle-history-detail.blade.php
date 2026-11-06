@extends('layouts.waka')

@section('title', 'Detail History Putaran - SCP')

@section('content')
    <div class="mb-5">
        <a href="{{ route('waka.cycle.history') }}" class="text-blue-600 hover:underline text-xs font-medium">← Kembali ke History Putaran</a>
        <h1 class="text-xl font-semibold text-slate-900 mt-1.5">Detail Putaran {{ $history->cycle_type }}</h1>
        <p class="text-slate-500 text-sm mt-0.5">
            {{ $history->start_date->format('d M Y') }} – {{ $history->end_date->format('d M Y') }}
            · Ditutup oleh {{ $history->closedBy->fullname ?? '-' }}
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Total Jurnal</p>
            <p class="text-xl font-semibold text-slate-900 mt-1">{{ $history->total_journals }}</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Total Presensi</p>
            <p class="text-xl font-semibold text-slate-900 mt-1">{{ $history->total_attendances }}</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Jumlah Pengajar</p>
            <p class="text-xl font-semibold text-slate-900 mt-1">{{ $teacherRecap->count() }}</p>
        </div>
    </div>

    {{-- Rekap per kelas --}}
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-800">Rekapitulasi per Kelas</h2>
            <span class="text-xs text-slate-500">{{ $classRecap->count() }} kelas</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse min-w-[600px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3 text-right">Jurnal</th>
                        <th class="px-4 py-3 text-right">Hadir</th>
                        <th class="px-4 py-3 text-right">Izin</th>
                        <th class="px-4 py-3 text-right">Alpha</th>
                        <th class="px-4 py-3 text-right w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($classRecap as $index => $c)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $c['class_name'] }}</td>
                            <td class="px-4 py-3 text-right text-xs text-slate-600">{{ $c['journals'] }}</td>
                            <td class="px-4 py-3 text-right text-xs font-medium text-emerald-700">{{ $c['hadir'] }}</td>
                            <td class="px-4 py-3 text-right text-xs text-amber-700">{{ $c['izin'] }}</td>
                            <td class="px-4 py-3 text-right text-xs font-medium text-red-600">{{ $c['alpha'] }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('waka.cycle.history.class', [$history->id, $c['class_id']]) }}"
                                   class="inline-flex rounded border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                    Rincian
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Belum ada data kelas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rekap per guru --}}
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-800">Rekapitulasi per Pengajar</h2>
            <span class="text-xs text-slate-500">{{ $teacherRecap->count() }} pengajar</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse min-w-[600px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Pengajar</th>
                        <th class="px-4 py-3 text-center w-36">Jumlah Jurnal</th>
                        <th class="px-4 py-3">Kelas yang Diajar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($teacherRecap as $index => $t)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $t['name'] }}</td>
                            <td class="px-4 py-3 text-center text-xs text-slate-600">{{ $t['journals'] }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $t['classes']->implode(', ') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500">Belum ada data pengajar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
