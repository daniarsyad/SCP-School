@extends('layouts.waka')

@section('title', 'Detail History Putaran - SCP')

@section('content')
    <div class="mb-6">
        <a href="{{ route('waka.cycle.history') }}" class="text-blue-600 hover:underline text-sm">← Kembali ke History</a>
        <h1 class="text-2xl font-bold mt-2">Detail Putaran {{ $history->cycle_type }}</h1>
        <p class="text-gray-600">
            {{ $history->start_date->format('d M Y') }} – {{ $history->end_date->format('d M Y') }}
            · Ditutup oleh {{ $history->closedBy->fullname ?? '-' }}
        </p>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500">Total Jurnal</p>
            <p class="text-3xl font-bold text-blue-600">{{ $history->total_journals }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500">Total Presensi</p>
            <p class="text-3xl font-bold text-green-600">{{ $history->total_attendances }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500">Jumlah Guru</p>
            <p class="text-3xl font-bold text-indigo-600">{{ $teachersData->count() }}</p>
        </div>
    </div>

    <!-- Guru yang Mengajar -->
    <div class="bg-white rounded-xl shadow overflow-hidden mb-8">
        <div class="bg-indigo-50 px-6 py-3 border-b">
            <h2 class="font-semibold text-indigo-800">👨‍🏫 Guru yang Mengajar</h2>
        </div>
        <div class="p-6">
            @forelse($teachersData as $t)
                <div class="flex justify-between items-start py-3 border-b last:border-0">
                    <div>
                        <p class="font-medium">{{ $t['name'] }}</p>
                        <p class="text-sm text-gray-500">
                            Kelas: {{ $t['classes']->implode(', ') ?: '-' }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-center py-4">Tidak ada data guru</p>
            @endforelse
        </div>
    </div>

    <!-- Daftar Jurnal -->
    <div class="bg-white rounded-xl shadow overflow-hidden mb-8">
        <div class="bg-blue-50 px-6 py-3 border-b">
            <h2 class="font-semibold text-blue-800">📝 Daftar Jurnal</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Tanggal</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Mapel</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Guru</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Kelas</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Topik</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($journals as $j)
                        <tr>
                            <td class="px-6 py-3 text-sm">{{ \Carbon\Carbon::parse($j->date)->format('d M Y') }}</td>
                            <td class="px-6 py-3">{{ $j->schedule->subject ?? '-' }}</td>
                            <td class="px-6 py-3">{{ $j->schedule->teacher->fullname ?? '-' }}</td>
                            <td class="px-6 py-3">{{ $j->schedule->class->class_name ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ Str::limit($j->topic_description, 40) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada jurnal</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Presensi per Kelas -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="bg-green-50 px-6 py-3 border-b">
            <h2 class="font-semibold text-green-800">✅ Presensi per Kelas</h2>
        </div>
        <div class="p-6 space-y-6">
            @forelse($attendances as $className => $items)
                <div>
                    <h3 class="font-semibold text-gray-800 mb-3">{{ $className }} ({{ $items->count() }} data)</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left">Siswa</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($items as $att)
                                    <tr>
                                        <td class="px-4 py-2">{{ $att->student->fullname ?? '-' }}</td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-0.5 text-xs rounded-full
                                                {{ $att->status === 'Hadir' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                                {{ $att->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">{{ \Carbon\Carbon::parse($att->date)->format('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-center py-4">Tidak ada data presensi</p>
            @endforelse
        </div>
    </div>
@endsection