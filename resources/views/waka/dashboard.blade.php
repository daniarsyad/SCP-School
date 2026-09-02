@extends('layouts.waka')

@section('title', 'Dashboard - SCP')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-gray-500">Selamat datang, {{ auth()->user()->fullname }}</p>
    </div>

    <!-- Ringkasan Utama -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Putaran -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Siklus Aktif</p>
                    <h3 class="text-2xl font-bold text-blue-600 mt-1">Putaran {{ $config->active_cycle }}</h3>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $config->start_date?->format('d M') }} - {{ $config->end_date?->format('d M Y') }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-2xl">
                    🔄
                </div>
            </div>
        </div>

        <!-- Status Jadwal -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Status Jadwal</p>
                    <h3 class="text-2xl font-bold mt-1 {{ $config->is_schedule_locked ? 'text-red-600' : 'text-green-600' }}">
                        {{ $config->is_schedule_locked ? 'Terkunci' : 'Terbuka' }}
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $config->is_schedule_locked ? 'Tidak bisa diubah' : 'Siap diedit' }}
                    </p>
                </div>
                <div class="w-12 h-12 {{ $config->is_schedule_locked ? 'bg-red-50' : 'bg-green-50' }} rounded-xl flex items-center justify-center text-2xl">
                    {{ $config->is_schedule_locked ? '🔒' : '🔓' }}
                </div>
            </div>
        </div>

        <!-- Alert Jurnal -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Jurnal Terlambat</p>
                    <h3 class="text-2xl font-bold text-orange-600 mt-1">{{ $lateJournals->count() }}</h3>
                    <p class="text-xs text-gray-400 mt-1">Belum diisi >30 menit</p>
                </div>
                <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center text-2xl">
                    ⚠️
                </div>
            </div>
        </div>

        <!-- Total Jadwal -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Jadwal</p>
                    <h3 class="text-2xl font-bold text-indigo-600 mt-1">
                        {{ \App\Models\Schedule::where('cycle_type', $config->active_cycle)->count() }}
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">Di putaran ini</p>
                </div>
                <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-2xl">
                    📅
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Jurnal Detail -->
    @if($lateJournals->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-orange-200 mb-8 overflow-hidden">
            <div class="bg-orange-50 px-6 py-4 border-b border-orange-100">
                <h2 class="font-semibold text-orange-800">⚠️ Guru Belum Mengisi Jurnal (>30 menit)</h2>
            </div>
            <div class="divide-y">
                @foreach($lateJournals as $item)
                    <div class="px-6 py-4 flex justify-between items-center hover:bg-gray-50">
                        <div>
                            <p class="font-medium text-gray-800">{{ $item->teacher->fullname }}</p>
                            <p class="text-sm text-gray-500">{{ $item->subject }} • {{ $item->class->class_name }}</p>
                        </div>
                        <div class="text-right text-sm text-gray-500">
                            <p>{{ substr($item->start_time, 0, 5) }} - {{ substr($item->end_time, 0, 5) }}</p>
                            <p>{{ $item->room->room_name }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-green-50 border border-green-200 rounded-2xl p-5 mb-8 flex items-center gap-3">
            <div class="text-2xl">✅</div>
            <div>
                <p class="font-medium text-green-800">Semua jurnal hari ini sudah terisi tepat waktu</p>
                <p class="text-sm text-green-600">Tidak ada keterlambatan</p>
            </div>
        </div>
    @endif

    <!-- Aksi Cepat -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Ganti Siklus -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-lg mb-1">Ganti Siklus Blok</h3>
            <p class="text-sm text-gray-500 mb-5">
                Saat ini aktif: <span class="font-medium text-blue-600">Putaran {{ $config->active_cycle }}</span><br>
                Periode: {{ $config->start_date?->format('d M Y') }} – {{ $config->end_date?->format('d M Y') }}
            </p>

            @if($config->is_schedule_locked)
                <button disabled class="w-full bg-gray-200 text-gray-500 font-medium py-3 rounded-xl cursor-not-allowed">
                    Tidak bisa diubah (Jadwal Terkunci)
                </button>
            @else
                <form action="{{ route('waka.toggle.cycle') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-xl transition">
                        Ubah ke Putaran {{ $config->active_cycle == '1' ? '2' : '1' }}
                    </button>
                </form>
            @endif
        </div>

        <!-- Lock/Unlock -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-lg mb-1">Lock / Unlock Jadwal</h3>
            <p class="text-sm text-gray-500 mb-5">
                Status saat ini:
                <span class="font-medium {{ $config->is_schedule_locked ? 'text-red-600' : 'text-green-600' }}">
                    {{ $config->is_schedule_locked ? 'TERKUNCI' : 'TERBUKA' }}
                </span>
            </p>
            <form action="{{ route('waka.toggle.lock') }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full {{ $config->is_schedule_locked ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} text-white font-medium py-3 rounded-xl transition">
                    {{ $config->is_schedule_locked ? 'Buka Kunci Jadwal' : 'Kunci Jadwal' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Menu Cepat -->
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Akses Cepat</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('waka.schedules.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition text-center">
            <div class="text-3xl mb-2">📅</div>
            <div class="font-medium text-gray-800">Jadwal</div>
        </a>
        <a href="{{ route('waka.journal.recap') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition text-center">
            <div class="text-3xl mb-2">📝</div>
            <div class="font-medium text-gray-800">Jurnal</div>
        </a>
        <a href="{{ route('waka.cycle.history') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition text-center">
            <div class="text-3xl mb-2">📜</div>
            <div class="font-medium text-gray-800">History</div>
        </a>
        <a href="{{ route('waka.master.users') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition text-center">
            <div class="text-3xl mb-2">👥</div>
            <div class="font-medium text-gray-800">Pengguna</div>
        </a>
    </div>
@endsection