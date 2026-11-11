@extends('layouts.waka')

@section('title', 'Konfigurasi Sistem - SCP')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-semibold text-slate-900">Konfigurasi Sistem</h1>
        <p class="text-slate-500 text-sm mt-0.5">Kelola putaran aktif dan status penguncian jadwal mengajar.</p>
    </div>

    <!-- Info Putaran Saat Ini -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
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
            <p class="text-xs text-slate-500">Status Putaran 1</p>
            <p class="text-xl font-semibold mt-1 {{ $config->isCycleLocked('1') ? 'text-red-600' : 'text-emerald-600' }}">
                {{ $config->isCycleLocked('1') ? 'Terkunci' : 'Terbuka' }}
            </p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Status Putaran 2</p>
            <p class="text-xl font-semibold mt-1 {{ $config->isCycleLocked('2') ? 'text-red-600' : 'text-emerald-600' }}">
                {{ $config->isCycleLocked('2') ? 'Terkunci' : 'Terbuka' }}
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

            @if($config->isCycleLocked('1') && $config->isCycleLocked('2'))
                <button disabled class="w-full bg-slate-100 text-slate-400 text-sm font-medium py-2 rounded-md border border-slate-200 cursor-not-allowed">
                    Tidak bisa diubah (Semua Jadwal Terkunci)
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

        <!-- Lock/Unlock per Putaran -->
        <div class="bg-white rounded-lg border border-slate-200 p-5 flex flex-col justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Kunci / Buka Kunci Jadwal per Putaran</h2>
                <p class="text-xs text-slate-500 mt-1 mb-4">
                    Putaran yang dikunci tidak dapat ditambah/diubah jadwalnya, sedangkan putaran yang terbuka tetap bebas dikelola oleh Waka.
                </p>

                <div class="space-y-3 mb-2">
                    <!-- Putaran 1 -->
                    <div class="flex items-center justify-between p-3 rounded-lg border {{ $config->isCycleLocked('1') ? 'border-red-200 bg-red-50/50' : 'border-slate-200 bg-slate-50/50' }}">
                        <div>
                            <div class="text-xs font-semibold text-slate-800 flex items-center gap-1.5">
                                <span>Putaran 1</span>
                                @if($config->active_cycle == '1')
                                    <span class="text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.2 rounded font-medium">Aktif</span>
                                @endif
                            </div>
                            <span class="text-xs {{ $config->isCycleLocked('1') ? 'text-red-600 font-medium' : 'text-emerald-600 font-medium' }}">
                                {{ $config->isCycleLocked('1') ? 'Terkunci (Jadwal Tetap)' : 'Terbuka (Bisa Tambah/Edit)' }}
                            </span>
                        </div>
                        <form action="{{ route('waka.toggle.lock') }}" method="POST">
                            @csrf
                            <input type="hidden" name="cycle" value="1">
                            <button type="submit"
                                    class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition {{ $config->isCycleLocked('1') ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-slate-800 hover:bg-slate-900 text-white' }}">
                                {{ $config->isCycleLocked('1') ? 'Buka Kunci' : 'Kunci Putaran 1' }}
                            </button>
                        </form>
                    </div>

                    <!-- Putaran 2 -->
                    <div class="flex items-center justify-between p-3 rounded-lg border {{ $config->isCycleLocked('2') ? 'border-red-200 bg-red-50/50' : 'border-slate-200 bg-slate-50/50' }}">
                        <div>
                            <div class="text-xs font-semibold text-slate-800 flex items-center gap-1.5">
                                <span>Putaran 2</span>
                                @if($config->active_cycle == '2')
                                    <span class="text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.2 rounded font-medium">Aktif</span>
                                @endif
                            </div>
                            <span class="text-xs {{ $config->isCycleLocked('2') ? 'text-red-600 font-medium' : 'text-emerald-600 font-medium' }}">
                                {{ $config->isCycleLocked('2') ? 'Terkunci (Jadwal Tetap)' : 'Terbuka (Bisa Tambah/Edit)' }}
                            </span>
                        </div>
                        <form action="{{ route('waka.toggle.lock') }}" method="POST">
                            @csrf
                            <input type="hidden" name="cycle" value="2">
                            <button type="submit"
                                    class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition {{ $config->isCycleLocked('2') ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-slate-800 hover:bg-slate-900 text-white' }}">
                                {{ $config->isCycleLocked('2') ? 'Buka Kunci' : 'Kunci Putaran 2' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Bersihkan Cache Tampilan / Server -->
            <div class="bg-white rounded-lg border border-slate-200 p-5 mt-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Segarkan Cache Server</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Bersihkan cache tampilan (Blade) dan sistem jika status penguncian atau perubahan jadwal belum langsung terbarui di peramban.
                        </p>
                    </div>
                    <form action="{{ route('waka.clear.cache') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 whitespace-nowrap px-3.5 py-2 text-xs font-medium rounded-md border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 transition">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Bersihkan Cache</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
