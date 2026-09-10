@extends('layouts.waka')

@section('title', 'Jurnal Kelas - SCP')

@section('content')
    <div class="mb-6">
        <a href="{{ route('waka.cycle.history.detail', $history->id) }}" class="text-blue-600 hover:underline text-sm">
            ← Kembali ke Detail Putaran
        </a>
        <h1 class="text-2xl font-bold mt-2">Jurnal Kelas {{ $class->class_name }}</h1>
        <p class="text-gray-600">
            Putaran {{ $history->cycle_type }} ·
            {{ $history->start_date->format('d M Y') }} – {{ $history->end_date->format('d M Y') }}
        </p>
    </div>

    <div class="space-y-6">
        @forelse($journals as $j)
            @php
                $photo = $j->photo_path ?? $j->photo ?? null;
            @endphp
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="flex-1">
                        <div class="font-bold text-lg">{{ $j->schedule->subject ?? '-' }}</div>
                        <div class="text-sm text-gray-600 mt-2 space-y-1">
                            <div>Tanggal: <strong>{{ \Carbon\Carbon::parse($j->date)->format('d M Y') }}</strong></div>
                            <div>Guru: {{ $j->schedule->teacher->fullname ?? '-' }}</div>
                            <div>Ruang: {{ $j->schedule->room->room_name ?? '-' }}</div>
                        </div>
                        <div class="mt-3 p-3 bg-gray-50 rounded-lg text-sm">
                            <div class="text-gray-500 mb-1">Topik:</div>
                            <div>{{ $j->topic_description }}</div>
                        </div>

                        @if($j->attendances && $j->attendances->count())
                            <div class="mt-4">
                                <div class="text-sm font-semibold mb-2">Presensi ({{ $j->attendances->count() }})</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($j->attendances->take(20) as $att)
                                        <span class="text-xs px-2 py-1 rounded-full
                                            {{ $att->status === 'Hadir' ? 'bg-green-100 text-green-700' : ($att->status === 'Izin' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                            {{ $att->student->fullname ?? '-' }} · {{ $att->status }}
                                        </span>
                                    @endforeach
                                    @if($j->attendances->count() > 20)
                                        <span class="text-xs text-gray-500">+{{ $j->attendances->count() - 20 }} lainnya</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="w-full md:w-56 shrink-0">
                        @if($photo)
                            <a href="{{ asset('storage/' . $photo) }}" target="_blank">
                                <img src="{{ asset('storage/' . $photo) }}"
                                     alt="Foto jurnal"
                                     class="w-full h-40 object-cover rounded-lg border shadow-sm hover:opacity-90">
                            </a>
                            <p class="text-xs text-gray-400 mt-1 text-center">Klik untuk memperbesar</p>
                        @else
                            <div class="w-full h-40 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-sm">
                                Tidak ada foto
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow p-10 text-center text-gray-500">
                Belum ada jurnal untuk kelas ini pada putaran tersebut
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $journals->links() }}
    </div>
@endsection