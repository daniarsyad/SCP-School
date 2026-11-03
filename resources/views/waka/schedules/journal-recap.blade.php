@extends('layouts.waka')

@section('title', 'Rekap Jurnal - SCP')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Rekap Jurnal Hari Ini</h1>
            <p class="text-gray-600">{{ $today }}, Putaran {{ $config->active_cycle }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow p-6">
            <div class="text-gray-500 text-sm">Total Jadwal Hari Ini</div>
            <div class="text-3xl font-bold mt-1">{{ $todaySchedules->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-6">
            <div class="text-gray-500 text-sm">Sudah Isi Jurnal</div>
            <div class="text-3xl font-bold mt-1 text-green-600">{{ $sudahIsi->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-6">
            <div class="text-gray-500 text-sm">Belum Isi / Terlambat</div>
            <div class="text-3xl font-bold mt-1 text-red-600">{{ $belumIsi->count() }}</div>
        </div>
    </div>

    <!-- Belum Isi -->
    <div class="bg-white rounded-xl shadow overflow-hidden mb-8">
        <div class="bg-red-50 px-6 py-3 border-b">
            <h2 class="font-bold text-red-800">Belum Mengisi Jurnal</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Guru</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Mapel</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Kelas</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Jam</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($belumIsi as $item)
                    <tr>
                        <td class="px-6 py-4 font-medium">{{ $item->teacher->fullname }}</td>
                        <td class="px-6 py-4">{{ $item->subject }}</td>
                        <td class="px-6 py-4">{{ $item->class->class_name }}</td>
                        <td class="px-6 py-4">{{ substr($item->start_time, 0, 5) }} - {{ substr($item->end_time, 0, 5) }}</td>
                        <td class="px-6 py-4">
                            @if($terlambat->contains($item))
                                <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded-full">Terlambat >30 mnt</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full">Belum Isi</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">Semua guru sudah mengisi jurnal</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Sudah Isi -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="bg-green-50 px-6 py-3 border-b">
            <h2 class="font-bold text-green-800">Sudah Mengisi Jurnal</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Guru</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Mapel</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Kelas</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Jam</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($sudahIsi as $item)
                    <tr>
                        <td class="px-6 py-4 font-medium">{{ $item->teacher->fullname }}</td>
                        <td class="px-6 py-4">{{ $item->subject }}</td>
                        <td class="px-6 py-4">{{ $item->class->class_name }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-between gap-3 whitespace-nowrap">
                                <span>{{ substr($item->start_time, 0, 5) }} - {{ substr($item->end_time, 0, 5) }}</span>
                                @if($item->journals->first()?->photo_path)
                                    <a href="{{ asset('storage/' . $item->journals->first()->photo_path) }}"
                                       target="_blank"
                                       rel="noopener">
                                        <img src="{{ asset('storage/' . $item->journals->first()->photo_path) }}"
                                             alt="Foto jurnal"
                                             class="h-12 w-16 object-cover rounded border">
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">Belum ada yang mengisi jurnal</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection