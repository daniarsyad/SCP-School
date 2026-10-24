@extends('layouts.waka')

@section('title', 'Dashboard - SCP')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-800">Dashboard</h1>
        <p class="text-slate-500 text-sm mt-0.5">Selamat datang, {{ auth()->user()->fullname }}</p>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-slate-500">Siklus Aktif</p>
                    <h3 class="text-xl font-semibold text-blue-600 mt-1">Putaran {{ $config->active_cycle }}</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ $config->start_date?->format('d M') }} – {{ $config->end_date?->format('d M Y') }}
                    </p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-slate-500">Status Jadwal</p>
                    <h3 class="text-xl font-semibold mt-1 {{ $config->is_schedule_locked ? 'text-red-600' : 'text-emerald-600' }}">
                        {{ $config->is_schedule_locked ? 'Terkunci' : 'Terbuka' }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ $config->is_schedule_locked ? 'Tidak bisa diubah' : 'Siap diedit' }}
                    </p>
                </div>
                <div class="w-10 h-10 rounded-lg flex items-center justify-center
                    {{ $config->is_schedule_locked ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }}">
                    @if($config->is_schedule_locked)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-slate-500">Jurnal Terlambat</p>
                    <h3 class="text-xl font-semibold text-amber-600 mt-1">{{ $lateJournals->count() }}</h3>
                    <p class="text-xs text-slate-400 mt-1">Belum diisi &gt;30 menit</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-slate-500">Total Jadwal</p>
                    <h3 class="text-xl font-semibold text-indigo-600 mt-1">
                        {{ \App\Models\Schedule::where('cycle_type', $config->active_cycle)->count() }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">Di putaran ini</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert jurnal --}}
    @if($lateJournals->count() > 0)
        <div class="bg-white rounded-xl border border-amber-200 mb-8 overflow-hidden">
            <div class="bg-amber-50 px-5 py-3 border-b border-amber-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                </svg>
                <h2 class="text-sm font-medium text-amber-800">Guru belum mengisi jurnal (&gt;30 menit)</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($lateJournals as $item)
                    <div class="px-5 py-3 flex justify-between items-center hover:bg-slate-50">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $item->teacher->fullname }}</p>
                            <p class="text-xs text-slate-500">{{ $item->subject }} · {{ $item->class->class_name }}</p>
                        </div>
                        <div class="text-right text-xs text-slate-500">
                            <p>{{ substr($item->start_time, 0, 5) }} – {{ substr($item->end_time, 0, 5) }}</p>
                            <p>{{ $item->room->room_name }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-5 py-4 mb-8 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-emerald-800">Semua jurnal hari ini sudah terisi tepat waktu</p>
                <p class="text-xs text-emerald-600">Tidak ada keterlambatan</p>
            </div>
        </div>
    @endif

    {{-- Aksi cepat --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-800">Ganti Siklus Blok</h3>
            <p class="text-xs text-slate-500 mt-1 mb-4">
                Aktif: <span class="font-medium text-blue-600">Putaran {{ $config->active_cycle }}</span><br>
                Periode: {{ $config->start_date?->format('d M Y') }} – {{ $config->end_date?->format('d M Y') }}
            </p>
            @if($config->is_schedule_locked)
                <button disabled class="w-full bg-slate-100 text-slate-400 text-sm font-medium py-2.5 rounded-lg cursor-not-allowed">
                    Tidak bisa diubah (jadwal terkunci)
                </button>
            @else
                <form action="{{ route('waka.toggle.cycle') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2.5 rounded-lg transition">
                        Ubah ke Putaran {{ $config->active_cycle == '1' ? '2' : '1' }}
                    </button>
                </form>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-800">Lock / Unlock Jadwal</h3>
            <p class="text-xs text-slate-500 mt-1 mb-4">
                Status:
                <span class="font-medium {{ $config->is_schedule_locked ? 'text-red-600' : 'text-emerald-600' }}">
                    {{ $config->is_schedule_locked ? 'TERKUNCI' : 'TERBUKA' }}
                </span>
            </p>
            <form action="{{ route('waka.toggle.lock') }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full text-sm font-medium py-2.5 rounded-lg text-white transition
                        {{ $config->is_schedule_locked ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700' }}">
                    {{ $config->is_schedule_locked ? 'Buka Kunci Jadwal' : 'Kunci Jadwal' }}
                </button>
            </form>
        </div>
    </div>

    {{-- Menu cepat --}}
    <h2 class="text-sm font-semibold text-slate-700 mb-3">Akses Cepat</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <a href="{{ route('waka.schedules.index') }}"
           class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 hover:bg-slate-50 transition text-center">
            <div class="mx-auto w-9 h-9 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
            </div>
            <div class="text-sm font-medium text-slate-700">Jadwal</div>
        </a>
        <a href="{{ route('waka.journal.recap') }}"
           class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 hover:bg-slate-50 transition text-center">
            <div class="mx-auto w-9 h-9 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
            </div>
            <div class="text-sm font-medium text-slate-700">Jurnal</div>
        </a>
        <a href="{{ route('waka.cycle.history') }}"
           class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 hover:bg-slate-50 transition text-center">
            <div class="mx-auto w-9 h-9 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="text-sm font-medium text-slate-700">History</div>
        </a>
        <a href="{{ route('waka.master.users') }}"
           class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 hover:bg-slate-50 transition text-center">
            <div class="mx-auto w-9 h-9 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </div>
            <div class="text-sm font-medium text-slate-700">Pengguna</div>
        </a>
    </div>
@endsection