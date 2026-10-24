<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SCP - Waka Kurikulum')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.55rem 0.85rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: #94a3b8;
            transition: background 0.15s, color 0.15s;
        }
        .nav-item:hover { background: #1e293b; color: #e2e8f0; }
        .nav-item.active { background: #1e3a5f; color: #fff; }
        .nav-item svg { width: 18px; height: 18px; opacity: 0.85; flex-shrink: 0; }
        .nav-label {
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
            padding: 1rem 0.85rem 0.35rem;
        }
        /* Sidebar tetap, konten yang scroll */
        .app-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            flex-shrink: 0;
            height: 100vh;
            overflow-y: auto;
        }
        .main-scroll {
            flex: 1;
            height: 100vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-slate-100">

    <div class="app-shell">

        <aside class="sidebar w-60 bg-slate-900 text-slate-300 flex flex-col border-r border-slate-800">
            <div class="px-4 py-4 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-smkn1.png') }}"
                         alt="SMKN 1 Bantul"
                         class="h-10 w-10 object-contain flex-shrink-0 rounded-full bg-white p-0.5">
                    <div class="min-w-0">
                        <div class="text-white font-semibold text-sm tracking-tight leading-tight truncate">
                            SCP School
                        </div>
                        <div class="text-[11px] text-slate-500 leading-tight mt-0.5 truncate">
                            SMKN 1 Bantul · Waka
                        </div>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                <a href="{{ route('waka.dashboard') }}"
                   class="nav-item {{ request()->routeIs('waka.dashboard') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/>
                    </svg>
                    Dashboard
                </a>

                <div class="nav-label">Manajemen Akademik</div>

                <a href="{{ route('waka.schedules.index') }}"
                   class="nav-item {{ request()->routeIs('waka.schedules.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Jadwal Mengajar
                </a>

                <a href="{{ route('waka.journal.recap') }}"
                   class="nav-item {{ request()->routeIs('waka.journal.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Jurnal Mengajar
                </a>

                <div class="nav-label">Laporan & Rekap</div>

                <a href="{{ route('waka.cycle.history') }}"
                   class="nav-item {{ request()->routeIs('waka.cycle.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    History Putaran
                </a>

                <div class="nav-label">Settings</div>

                <a href="{{ route('waka.system.config') }}"
                   class="nav-item {{ request()->routeIs('waka.system.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Konfigurasi Sistem
                </a>

                <div class="nav-label">Data Master</div>

                <a href="{{ route('waka.master.users') }}"
                   class="nav-item {{ request()->routeIs('waka.master.users*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Pengguna
                </a>

                <a href="{{ route('waka.master.classes') }}"
                   class="nav-item {{ request()->routeIs('waka.master.classes*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0121 18.5M12 14v7"/>
                    </svg>
                    Kelas
                </a>

                <a href="{{ route('waka.master.rooms') }}"
                   class="nav-item {{ request()->routeIs('waka.master.rooms*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/>
                    </svg>
                    Ruangan
                </a>

                <a href="{{ route('waka.master.subjects') }}"
                    class="nav-item {{ request()->routeIs('waka.master.subjects') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                     Mata Pelajaran
                </a>

                <a href="{{ route('waka.promotion') }}"
                   class="nav-item {{ request()->routeIs('waka.promotion*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    Kenaikan Kelas
                </a>
            </nav>

            <div class="px-4 py-4 border-t border-slate-800">
                <div class="text-sm text-slate-300 truncate mb-2">{{ auth()->user()->fullname }}</div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full text-sm py-2 rounded-lg border border-slate-700 text-slate-400 hover:bg-slate-800 hover:text-white transition">
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <main class="main-scroll">
            <div class="p-6 md:p-8 max-w-7xl">
                @if(session('success'))
                    <div class="mb-5 px-4 py-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm border border-emerald-100">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 text-red-800 text-sm border border-red-100">
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </div>
        </main>

    </div>

</body>
</html>