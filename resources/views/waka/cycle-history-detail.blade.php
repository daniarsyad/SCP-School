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
            <p class="text-3xl font-bold text-indigo-600">{{ $teacherRecap->count() }}</p>
        </div>
    </div>

    {{-- Rekap per kelas --}}
    <div class="bg-white rounded-xl shadow overflow-hidden mb-8">
        <div class="bg-blue-50 px-6 py-3 border-b">
            <h2 class="font-semibold text-blue-800">Rekap per Kelas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Kelas</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Jurnal</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Hadir</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Izin</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Alpha</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($classRecap as $c)
                        <tr>
                            <td class="px-6 py-3 font-medium">{{ $c['class_name'] }}</td>
                            <td class="px-6 py-3">{{ $c['journals'] }}</td>
                            <td class="px-6 py-3 text-green-700">{{ $c['hadir'] }}</td>
                            <td class="px-6 py-3 text-yellow-700">{{ $c['izin'] }}</td>
                            <td class="px-6 py-3 text-red-700">{{ $c['alpha'] }}</td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('waka.cycle.history.class', [$history->id, $c['class_id']]) }}"
                                   class="text-blue-600 hover:underline text-sm">
                                    Lihat Jurnal & Foto
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada data kelas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rekap per guru --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="bg-indigo-50 px-6 py-3 border-b">
            <h2 class="font-semibold text-indigo-800">Rekap per Guru</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[600px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Guru</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Jumlah Jurnal</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Kelas yang Diajar</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($teacherRecap as $t)
                        <tr>
                            <td class="px-6 py-3 font-medium">{{ $t['name'] }}</td>
                            <td class="px-6 py-3">{{ $t['journals'] }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $t['classes']->implode(', ') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">Belum ada data guru</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection