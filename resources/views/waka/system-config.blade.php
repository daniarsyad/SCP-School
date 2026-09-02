@extends('layouts.waka')

@section('title', 'Konfigurasi Sistem - SCP')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Konfigurasi Sistem</h1>
        <p class="text-gray-500">Kelola putaran aktif dan status kunci jadwal</p>
    </div>

    <!-- Info Putaran Saat Ini -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <h2 class="font-semibold text-lg mb-4">Informasi Putaran Aktif</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-gray-500">Putaran</p>
                <p class="text-xl font-bold text-blue-600">Putaran {{ $config->active_cycle }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Periode</p>
                <p class="text-xl font-bold">
                    {{ $config->start_date?->format('d M Y') ?? '-' }} – {{ $config->end_date?->format('d M Y') ?? '-' }}
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Status Jadwal</p>
                <p class="text-xl font-bold {{ $config->is_schedule_locked ? 'text-red-600' : 'text-green-600' }}">
                    {{ $config->is_schedule_locked ? 'TERKUNCI' : 'TERBUKA' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Aksi -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Ganti Siklus -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-lg mb-2">Ganti Siklus Blok</h3>
            <p class="text-sm text-gray-500 mb-5">
                Mengganti putaran akan menyimpan history putaran sebelumnya dan memulai periode 14 hari baru.
            </p>

            @if($config->is_schedule_locked)
                <button disabled class="w-full bg-gray-200 text-gray-500 font-medium py-3 rounded-xl cursor-not-allowed">
                    Tidak bisa diubah (Jadwal Terkunci)
                </button>
            @else
                <form action="{{ route('waka.toggle.cycle') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-xl transition"
                            onclick="return confirm('Yakin ingin mengganti putaran? History putaran saat ini akan disimpan.')">
                        Ubah ke Putaran {{ $config->active_cycle == '1' ? '2' : '1' }}
                    </button>
                </form>
            @endif
        </div>

        <!-- Lock/Unlock -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-lg mb-2">Lock / Unlock Jadwal</h3>
            <p class="text-sm text-gray-500 mb-5">
                Mengunci jadwal akan mencegah guru menambah atau mengubah jadwal.
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
@endsection