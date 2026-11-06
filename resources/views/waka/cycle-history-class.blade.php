@extends('layouts.waka')

@section('title', 'Presensi Kelas - SCP')

@section('content')
    <div class="mb-5">
        <a href="{{ route('waka.cycle.history') }}?history_id={{ $history->id }}&tab=presensi"
           class="text-blue-600 hover:underline text-xs font-medium">
            ← Kembali ke History Putaran
        </a>
        <h1 class="text-xl font-semibold text-slate-900 mt-1.5">Presensi Kelas {{ $class->class_name }}</h1>
        <p class="text-slate-500 text-sm mt-0.5">
            Putaran {{ $history->cycle_type }} ·
            {{ $history->start_date->format('d M Y') }} – {{ $history->end_date->format('d M Y') }}
        </p>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-800">Rekapitulasi Presensi Siswa</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="px-4 py-3">Mata Pelajaran</th>
                        <th class="px-4 py-3">Pengajar</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @php $no = 1; @endphp
                    @forelse($journals as $journal)
                        @foreach($journal->attendances as $att)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $no++ }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $att->student->fullname ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $journal->schedule->subject ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $journal->schedule->teacher->fullname ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($journal->date)->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $badge = match($att->status) {
                                            'Hadir' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'Alpha' => 'bg-red-50 text-red-700 border-red-200',
                                            'Izin'  => 'bg-amber-50 text-amber-700 border-amber-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                                        };
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded border text-xs font-medium {{ $badge }}">
                                        {{ $att->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                Tidak ada data presensi pada kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
