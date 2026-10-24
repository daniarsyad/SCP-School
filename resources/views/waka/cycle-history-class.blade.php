@extends('layouts.waka')

@section('title', 'Presensi Kelas - SCP')

@section('content')
    <div class="mb-6">
        <a href="{{ route('waka.cycle.history') }}?history_id={{ $history->id }}&tab=presensi"
           class="text-blue-600 hover:underline text-sm">
            ← Kembali ke History
        </a>
        <h1 class="text-2xl font-bold mt-2">Presensi Kelas {{ $class->class_name }}</h1>
        <p class="text-gray-500 text-sm">
            Putaran {{ $history->cycle_type }} · 
            {{ $history->start_date->format('d M Y') }} – {{ $history->end_date->format('d M Y') }}
        </p>
    </div>

    <div class="bg-white rounded-xl border overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="font-semibold">Rekapitulasi Presensi</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left font-medium">No</th>
                        <th class="px-6 py-3 text-left font-medium">Nama Siswa</th>
                        <th class="px-6 py-3 text-left font-medium">Mata Pelajaran</th>
                        <th class="px-6 py-3 text-left font-medium">Guru</th>
                        <th class="px-6 py-3 text-left font-medium">Tanggal</th>
                        <th class="px-6 py-3 text-left font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @php $no = 1; @endphp
                    @forelse($journals as $journal)
                        @foreach($journal->attendances as $att)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-gray-500">{{ $no++ }}</td>
                                <td class="px-6 py-3 font-medium">{{ $att->student->fullname ?? '-' }}</td>
                                <td class="px-6 py-3">{{ $journal->schedule->subject ?? '-' }}</td>
                                <td class="px-6 py-3">{{ $journal->schedule->teacher->fullname ?? '-' }}</td>
                                <td class="px-6 py-3">{{ \Carbon\Carbon::parse($journal->date)->format('d M Y') }}</td>
                                <td class="px-6 py-3">
                                    @php
                                        $badge = match($att->status) {
                                            'Hadir' => 'bg-green-100 text-green-700',
                                            'Alpha' => 'bg-red-100 text-red-700',
                                            'Izin'  => 'bg-orange-100 text-orange-700',
                                            default => 'bg-yellow-100 text-yellow-700',
                                        };
                                    @endphp
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                        {{ $att->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                Tidak ada data presensi
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection