@extends('layouts.waka')

@section('title', 'History Putaran - SCP')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">History Putaran</h1>
        <p class="text-gray-500 text-sm">Lihat rekap jurnal dan presensi per putaran.</p>
    </div>

    <!-- Pilih Putaran -->
    <form method="GET" class="mb-6">
        <select name="history_id" class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
            @foreach($histories as $h)
                <option value="{{ $h->id }}" {{ request('history_id') == $h->id ? 'selected' : '' }}>
                    Putaran {{ $h->cycle_type }} · {{ $h->start_date->format('d M Y') }} – {{ $h->end_date->format('d M Y') }}
                </option>
            @endforeach
        </select>
        <input type="hidden" name="tab" value="{{ $tab ?? 'presensi' }}">
    </form>

    @if(isset($selected) && $selected)
        <!-- Tab -->
        <div class="flex gap-2 mb-6">
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'presensi']) }}"
               class="px-4 py-2 text-sm rounded-lg {{ ($tab ?? 'presensi') === 'presensi' ? 'bg-gray-900 text-white' : 'bg-gray-100' }}">
                Rekap Presensi
            </a>
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'jurnal']) }}"
               class="px-4 py-2 text-sm rounded-lg {{ ($tab ?? '') === 'jurnal' ? 'bg-gray-900 text-white' : 'bg-gray-100' }}">
                Jurnal Mengajar
            </a>
        </div>

        @if(($tab ?? 'presensi') === 'presensi')
            <!-- Kartu Ringkasan -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl border p-5">
                    <p class="text-xs text-gray-500">Hadir</p>
                    <p class="text-2xl font-bold text-green-600">{{ $classRecap->sum('hadir') }}</p>
                </div>
                <div class="bg-white rounded-xl border p-5">
                    <p class="text-xs text-gray-500">Sakit</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $classRecap->sum('sakit') }}</p>
                </div>
                <div class="bg-white rounded-xl border p-5">
                    <p class="text-xs text-gray-500">Izin</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $classRecap->sum('izin') }}</p>
                </div>
                <div class="bg-white rounded-xl border p-5">
                    <p class="text-xs text-gray-500">Alpha</p>
                    <p class="text-2xl font-bold text-red-600">{{ $classRecap->sum('alpha') }}</p>
                </div>
            </div>

            <!-- Tabel per Kelas -->
            <div class="bg-white rounded-xl border overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h2 class="font-semibold">Rekapitulasi per Kelas</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3 text-left">Kelas</th>
                            <th class="px-6 py-3 text-left">Tanggal Jurnal</th>
                            <th class="px-6 py-3 text-right">Jurnal</th>
                            <th class="px-6 py-3 text-right">Hadir</th>
                            <th class="px-6 py-3 text-right">Izin</th>
                            <th class="px-6 py-3 text-right">Sakit</th>
                            <th class="px-6 py-3 text-right">Alpha</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($classRecap as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium">{{ $row['class_name'] }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($row['dates'] as $date)
                                            <span class="whitespace-nowrap rounded bg-gray-100 px-2 py-1 text-xs">{{ $date }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-right">{{ $row['journals'] }}</td>
                                <td class="px-6 py-3 text-right text-green-600">{{ $row['hadir'] }}</td>
                                <td class="px-6 py-3 text-right text-orange-600">{{ $row['izin'] }}</td>
                                <td class="px-6 py-3 text-right text-yellow-600">{{ $row['sakit'] }}</td>
                                <td class="px-6 py-3 text-right text-red-600">{{ $row['alpha'] }}</td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('waka.cycle.history.class', [$selected->id, $row['class_id']]) }}"
                                       class="text-blue-600 hover:underline text-sm">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">Tidak ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
             <!-- Tab Jurnal -->
            <div class="bg-white rounded-xl border overflow-hidden">
                <div class="px-6 py-4 border-b flex justify-between">
                    <h2 class="font-semibold">Jurnal Mengajar</h2>
                    <span class="text-sm text-gray-500">{{ $journals->count() }} jurnal</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left">Tanggal</th>
                                <th class="px-6 py-3 text-left">Mapel</th>
                                <th class="px-6 py-3 text-left">Guru</th>
                                <th class="px-6 py-3 text-left">Kelas</th>
                                <th class="px-6 py-3 text-left">Topik / Materi</th>
                                <th class="px-6 py-3 text-left">Foto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($journals as $j)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($j->date)->format('d M Y') }}</td>
                                    <td class="px-6 py-3">{{ $j->schedule->subject ?? '-' }}</td>
                                    <td class="px-6 py-3">{{ $j->schedule->teacher->fullname ?? '-' }}</td>
                                    <td class="px-6 py-3">{{ $j->schedule->class->class_name ?? '-' }}</td>
                                    <td class="px-6 py-3 max-w-xs">
                                        <div class="text-sm text-gray-700">{{ $j->topic_description ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-3">
                                        @if($j->photo_path)
                                            <a href="{{ asset('storage/' . $j->photo_path) }}" target="_blank">
                                                <img src="{{ asset('storage/' . $j->photo_path) }}"
                                                     alt="Foto jurnal"
                                                     class="h-12 w-16 object-cover rounded border">
                                            </a>
                                        @else
                                            <span class="text-gray-400 text-xs">Tidak ada</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Tidak ada jurnal</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div class="bg-white rounded-xl border p-10 text-center text-gray-500">
            Belum ada history putaran.
        </div>
    @endif
@endsection