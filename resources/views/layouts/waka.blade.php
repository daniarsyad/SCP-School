<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SCP - Waka')</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        .sidebar-link.active {
            background-color: #1e40af;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white min-h-screen flex flex-col">
        <div class="p-5 border-b border-gray-700">
            <h1 class="text-xl font-bold">SCP School</h1>
            <p class="text-sm text-gray-400">Waka Kurikulum</p>
        </div>

        <nav class="flex-1 p-4 space-y-1">
            <a href="{{ route('waka.dashboard') }}" 
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-800 transition {{ request()->routeIs('waka.dashboard') ? 'active' : '' }}">
                <span>🏠</span> Dashboard
            </a>

            <div class="pt-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Manajemen Akademik</div>
            
            <a href="{{ route('waka.schedules.index') }}" 
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-800 transition {{ request()->routeIs('waka.schedules.*') ? 'active' : '' }}">
                <span>📅</span> Jadwal Mengajar
            </a>

            <a href="{{ route('waka.journal.recap') }}" 
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-800 transition {{ request()->routeIs('waka.journal.*') ? 'active' : '' }}">
                <span>📝</span> Jurnal Mengajar
            </a>

            <div class="pt-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Laporan & Rekap</div>

            <a href="{{ route('waka.cycle.history') }}" 
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-800 transition {{ request()->routeIs('waka.cycle.*') ? 'active' : '' }}">
                <span>📜</span> History Putaran
            </a>

            <div class="pt-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Settings</div>

            <a href="{{ route('waka.system.config') }}" 
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-800 transition {{ request()->routeIs('waka.system.config') ? 'active' : '' }}">
                <span>⚙️</span> Konfigurasi Sistem
            </a>

            <div class="pt-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Data Master</div>

            <a href="{{ route('waka.master.users') }}" 
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-800 transition {{ request()->routeIs('waka.master.users*') ? 'active' : '' }}">
                <span>👥</span> Pengguna
            </a>

            <a href="{{ route('waka.master.classes') }}" 
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-800 transition {{ request()->routeIs('waka.master.classes*') ? 'active' : '' }}">
                <span>🏫</span> Kelas
            </a>

            <a href="{{ route('waka.master.rooms') }}" 
               class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-800 transition {{ request()->routeIs('waka.master.rooms*') ? 'active' : '' }}">
                <span>🚪</span> Ruangan
            </a>
        </nav>

        <div class="p-4 border-t border-gray-700">
            <div class="text-sm mb-2">{{ auth()->user()->fullname }}</div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="w-full bg-red-600 hover:bg-red-700 text-white text-sm py-2 rounded-lg transition">
                    Logout
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-auto">
        <div class="p-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

</body>
</html>
