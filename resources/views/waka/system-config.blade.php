@extends('layouts.waka')

@section('title', 'Konfigurasi Sistem - SCP')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-semibold text-slate-900">Konfigurasi Sistem</h1>
        <p class="text-slate-500 text-sm mt-0.5">Kelola putaran aktif dan status penguncian jadwal mengajar.</p>
    </div>

    <!-- Info Putaran Saat Ini -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Siklus Aktif</p>
            <p class="text-xl font-semibold text-slate-900 mt-1">Putaran {{ $config->active_cycle }}</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Periode Berjalan</p>
            <p class="text-base font-semibold text-slate-900 mt-1.5">
                {{ $config->start_date?->format('d M Y') ?? '-' }} – {{ $config->end_date?->format('d M Y') ?? '-' }}
            </p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Status Jadwal</p>
            <p class="text-xl font-semibold mt-1 {{ $config->is_schedule_locked ? 'text-red-600' : 'text-emerald-600' }}">
                {{ $config->is_schedule_locked ? 'Terkunci' : 'Terbuka' }}
            </p>
        </div>
    </div>

    <!-- Aksi -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Ganti Siklus -->
        <div class="bg-white rounded-lg border border-slate-200 p-5 flex flex-col justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Ganti Siklus Blok</h2>
                <p class="text-xs text-slate-500 mt-1 mb-5">
                    Mengganti putaran akan menyimpan riwayat putaran sebelumnya dan memulai periode 14 hari baru.
                </p>
            </div>

            @if($config->is_schedule_locked)
                <button disabled class="w-full bg-slate-100 text-slate-400 text-sm font-medium py-2 rounded-md border border-slate-200 cursor-not-allowed">
                    Tidak bisa diubah (Jadwal Terkunci)
                </button>
            @else
                <form action="{{ route('waka.toggle.cycle') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-md transition"
                            onclick="return confirm('Yakin ingin mengganti putaran? History putaran saat ini akan disimpan.')">
                        Ubah ke Putaran {{ $config->active_cycle == '1' ? '2' : '1' }}
                    </button>
                </form>
            @endif
        </div>

        <!-- Lock/Unlock -->
        <div class="bg-white rounded-lg border border-slate-200 p-5 flex flex-col justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Kunci / Buka Kunci Jadwal</h2>
                <p class="text-xs text-slate-500 mt-1 mb-5">
                    Mengunci jadwal akan mencegah penambahan, perubahan, atau penghapusan data jadwal mengajar.
                </p>
            </div>
            <form action="{{ route('waka.toggle.lock') }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full {{ $config->is_schedule_locked ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-800 hover:bg-slate-900' }} text-white text-sm font-medium py-2 rounded-md transition">
                    {{ $config->is_schedule_locked ? 'Buka Kunci Jadwal' : 'Kunci Jadwal' }}
                </button>
            </form>
        </div>
    </div>
@endsection
