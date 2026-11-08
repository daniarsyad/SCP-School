<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SCP - Waka Kurikulum')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-smkn1.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-smkn1.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
        }
        .app-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            flex-shrink: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: #0f172a;
        }
        /* Sembunyikan scrollbar bawaan Windows yang tebal di sidebar */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .sidebar-nav::-webkit-scrollbar {
            width: 0;
            height: 0;
            display: none;
        }
        .main-scroll {
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.55rem 0.8rem;
            border-radius: 0.5rem;
            font-size: 0.84rem;
            font-weight: 500;
            color: #94a3b8;
            transition: all 0.15s ease;
            outline: none !important;
        }
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #f8fafc;
        }
        .nav-item.active {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        .nav-item svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }
        .nav-group-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 0.5rem 0.8rem;
            border-radius: 0.5rem;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #64748b;
            transition: color 0.15s ease, background 0.15s ease;
            outline: none !important;
        }
        .nav-group-btn:hover {
            color: #cbd5e1;
            background: rgba(255, 255, 255, 0.03);
        }
        .nav-sub-list {
            margin-left: 1.15rem;
            padding-left: 0.65rem;
            border-left: 1px solid #1e293b;
        }
        .nav-sub-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.48rem 0.75rem;
            border-radius: 0.45rem;
            font-size: 0.83rem;
            font-weight: 500;
            color: #94a3b8;
            transition: all 0.15s ease;
            outline: none !important;
        }
        .nav-sub-item:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #f8fafc;
        }
        .nav-sub-item.active {
            background: #2563eb;
            color: #ffffff;
            font-weight: 500;
        }
        .nav-sub-item svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            opacity: 0.85;
        }
        table {
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>
<body class="bg-slate-100/80 text-slate-800">

    @php
        $sysConfig = \App\Models\SystemConfiguration::current();
    @endphp

    <div class="app-shell relative">

        {{-- BACKDROP OVERLAY UNTUK MOBILE --}}
        <div id="sidebarBackdrop" onclick="closeSidebarMobile()"
             class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40 hidden lg:hidden transition-opacity duration-200"></div>

        {{-- SIDEBAR --}}
        <aside id="sidebarDrawer"
               class="sidebar fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] lg:static lg:w-64 lg:max-w-none text-slate-300 border-r border-slate-800 -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col">
            {{-- Brand Sekolah --}}
            <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
                <a href="{{ route('landing') }}" class="flex items-center gap-3 min-w-0 group" title="Buka Halaman Utama">
                    <img src="{{ asset('images/logo-smkn1.png') }}"
                         alt="SMKN 1 Bantul"
                         class="h-10 w-10 object-contain flex-shrink-0 rounded-full bg-white p-0.5 shadow-xs transition transform group-hover:scale-105 duration-200">
                    <div class="min-w-0">
                        <div class="text-white font-semibold text-sm tracking-tight leading-tight truncate group-hover:text-blue-400 transition">
                            SCP School
                        </div>
                        <div class="text-xs text-slate-400 leading-tight mt-0.5 truncate">
                            SMK Negeri 1 Bantul
                        </div>
                    </div>
                </a>
                {{-- Tombol Tutup Sidebar di Mobile --}}
                <button type="button" onclick="closeSidebarMobile()"
                        class="lg:hidden p-1.5 -mr-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition"
                        aria-label="Tutup Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Menu Navigasi dengan Dropdown --}}
            <nav class="sidebar-nav px-3 py-4 space-y-2">
                {{-- Dashboard --}}
                <div>
                    <a href="{{ route('waka.dashboard') }}"
                       class="nav-item {{ request()->routeIs('waka.dashboard') ? 'active' : '' }}">
                        <svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </div>

                {{-- Dropdown: Manajemen Akademik --}}
                <div class="pt-1">
                    <button type="button"
                            onclick="toggleSidebarGroup('groupAkademik', 'chevronAkademik')"
                            class="nav-group-btn">
                        <span>Manajemen Akademik</span>
                        <svg id="chevronAkademik" class="w-3.5 h-3.5 transition-transform duration-150" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div id="groupAkademik" class="nav-sub-list mt-1 space-y-1">
                        <a href="{{ route('waka.schedules.index') }}"
                           class="nav-sub-item {{ request()->routeIs('waka.schedules.*') ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Jadwal Mengajar</span>
                        </a>
                        <a href="{{ route('waka.journal.recap') }}"
                           class="nav-sub-item {{ request()->routeIs('waka.journal.*') ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Jurnal Mengajar</span>
                        </a>
                        <a href="{{ route('waka.teachers.index') }}"
                           class="nav-sub-item {{ request()->routeIs('waka.teachers.*') ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m16-13a4 4 0 11-8 0 4 4 0 018 0zm6 13v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                            </svg>
                            <span>Data Guru</span>
                        </a>
                        <a href="{{ route('waka.cycle.history') }}"
                           class="nav-sub-item {{ request()->routeIs('waka.cycle.*') ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>History Putaran</span>
                        </a>
                    </div>
                </div>

                {{-- Dropdown: Data Master --}}
                <div class="pt-1">
                    <button type="button"
                            onclick="toggleSidebarGroup('groupMaster', 'chevronMaster')"
                            class="nav-group-btn">
                        <span>Data Master</span>
                        <svg id="chevronMaster" class="w-3.5 h-3.5 transition-transform duration-150" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div id="groupMaster" class="nav-sub-list mt-1 space-y-1">
                        <a href="{{ route('waka.master.users') }}"
                           class="nav-sub-item {{ request()->routeIs('waka.master.users*') ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Pengguna</span>
                        </a>
                        <a href="{{ route('waka.master.classes') }}"
                           class="nav-sub-item {{ request()->routeIs('waka.master.classes*') ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0121 18.5M12 14v7"/>
                            </svg>
                            <span>Kelas</span>
                        </a>
                        <a href="{{ route('waka.master.rooms') }}"
                           class="nav-sub-item {{ request()->routeIs('waka.master.rooms*') ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/>
                            </svg>
                            <span>Ruangan</span>
                        </a>
                        <a href="{{ route('waka.master.subjects') }}"
                           class="nav-sub-item {{ request()->routeIs('waka.master.subjects*') ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <span>Mata Pelajaran</span>
                        </a>
                        <a href="{{ route('waka.promotion') }}"
                           class="nav-sub-item {{ request()->routeIs('waka.promotion*') ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                            <span>Kenaikan Kelas</span>
                        </a>
                    </div>
                </div>

                {{-- Pengaturan Sistem & Halaman Utama --}}
                <div class="pt-2 border-t border-slate-800/80 space-y-1">
                    <a href="{{ route('waka.profile') }}"
                       class="nav-item {{ request()->routeIs('waka.profile*') ? 'active' : '' }}">
                        <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Edit Profil & Akun</span>
                    </a>
                    <a href="{{ route('waka.system.config') }}"
                       class="nav-item {{ request()->routeIs('waka.system.*') ? 'active' : '' }}">
                        <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Konfigurasi Sistem</span>
                    </a>
                    <a href="{{ route('landing') }}"
                       class="nav-item text-slate-400 hover:text-white"
                       title="Buka Halaman Utama / Landing Page">
                        <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        <span>Lihat Web Utama</span>
                    </a>
                </div>
            </nav>

            {{-- Footer User & Tombol Keluar --}}
            <div class="px-4 py-3.5 border-t border-slate-800/80 bg-slate-950/40">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-semibold text-white truncate">{{ auth()->user()->fullname }}</div>
                        <div class="text-[11px] text-slate-400 truncate">Waka Kurikulum</div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 text-xs font-medium rounded-md border border-slate-700 text-slate-300 hover:bg-red-600 hover:border-red-600 hover:text-white transition">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- MAIN CONTENT AREA --}}
        <main class="main-scroll min-w-0">
            {{-- Top Bar Bersih & Responsif --}}
            <header class="bg-white border-b border-slate-200 px-3.5 sm:px-6 py-2.5 sm:py-3 flex items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                    {{-- Hamburger Button Mobile --}}
                    <button type="button" onclick="openSidebarMobile()"
                            class="lg:hidden p-1.5 sm:p-2 -ml-1 sm:-ml-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none transition"
                            aria-label="Buka Menu Navigasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="flex items-center gap-1.5 sm:gap-2 text-xs text-slate-500 truncate">
                        <a href="{{ route('landing') }}" class="font-semibold text-slate-800 hover:text-blue-600 truncate transition" title="Buka Halaman Utama">SCP School</a>
                        <span class="hidden sm:inline text-slate-300">/</span>
                        <span class="hidden sm:inline truncate">SMK Negeri 1 Bantul</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    @if($sysConfig)
                        <span class="inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200/80 text-[11px] sm:text-xs font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            <span>Putaran {{ $sysConfig->active_cycle }}</span>
                        </span>
                        <span class="hidden md:inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-md text-[11px] sm:text-xs font-medium border {{ $sysConfig->is_schedule_locked ? 'bg-red-50 text-red-700 border-red-200/80' : 'bg-emerald-50 text-emerald-700 border-emerald-200/80' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $sysConfig->is_schedule_locked ? 'bg-red-600' : 'bg-emerald-600' }}"></span>
                            <span>{{ $sysConfig->is_schedule_locked ? 'Terkunci' : 'Terbuka' }}</span>
                        </span>
                    @endif

                    {{-- Tombol Edit Profil di Topbar --}}
                    <a href="{{ route('waka.profile') }}"
                       class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-blue-50 hover:border-blue-300 text-slate-700 hover:text-blue-700 text-xs font-semibold transition"
                       title="Edit Profil & Password Akun">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="hidden sm:inline">Edit Profil</span>
                    </a>
                </div>
            </header>

            {{-- Isi Halaman --}}
            <div class="p-3.5 sm:p-5 md:p-6 lg:p-7 max-w-7xl w-full mx-auto flex-1 min-w-0">
                @if(session('success'))
                    <div class="mb-4 sm:mb-5 px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-lg bg-emerald-50 text-emerald-800 text-xs sm:text-sm border border-emerald-200">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 sm:mb-5 px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-lg bg-red-50 text-red-800 text-xs sm:text-sm border border-red-200">
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </div>
        </main>

    </div>

    <script>
        function openSidebarMobile() {
            const sidebar = document.getElementById('sidebarDrawer');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar) sidebar.classList.remove('-translate-x-full');
            if (backdrop) backdrop.classList.remove('hidden');
            document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
        }

        function closeSidebarMobile() {
            const sidebar = document.getElementById('sidebarDrawer');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar) sidebar.classList.add('-translate-x-full');
            if (backdrop) backdrop.classList.add('hidden');
            document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSidebarMobile();
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                closeSidebarMobile();
            }
        });

        function toggleSidebarGroup(groupId, chevronId) {
            const group = document.getElementById(groupId);
            const chevron = document.getElementById(chevronId);
            if (!group) return;
            const isHidden = group.classList.toggle('hidden');
            if (chevron) {
                chevron.classList.toggle('-rotate-90', isHidden);
            }
            try {
                localStorage.setItem('scp_sidebar_' + groupId, isHidden ? 'closed' : 'open');
            } catch (e) {}
        }

        document.addEventListener('DOMContentLoaded', function () {
            ['groupAkademik', 'groupMaster'].forEach(function (groupId) {
                const chevronId = groupId === 'groupAkademik' ? 'chevronAkademik' : 'chevronMaster';
                try {
                    const saved = localStorage.getItem('scp_sidebar_' + groupId);
                    if (saved === 'closed') {
                        const group = document.getElementById(groupId);
                        const chevron = document.getElementById(chevronId);
                        if (group && !group.querySelector('.active')) {
                            group.classList.add('hidden');
                            if (chevron) chevron.classList.add('-rotate-90');
                        }
                    }
                } catch (e) {}
            });
        });
    </script>
</body>
</html>
