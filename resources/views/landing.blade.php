<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCP School - Sistem Manajemen Akademik & Kehadiran SMK Negeri 1 Bantul</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-smkn1.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-smkn1.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
        }
        table {
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>
<body class="bg-slate-100/80 text-slate-800 min-h-screen flex flex-col selection:bg-blue-600 selection:text-white">

    {{-- ==================== NAVBAR (STICKY) ==================== --}}
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            {{-- Brand / Logo SCP --}}
            <a href="{{ url('/') }}" class="flex items-center gap-3 group focus:outline-none">
                <img src="{{ asset('images/logo-smkn1.png') }}"
                     alt="SMK Negeri 1 Bantul"
                     class="h-10 w-10 object-contain flex-shrink-0 rounded-full bg-white p-0.5 border border-slate-200">
                <div>
                    <div class="text-slate-900 font-semibold text-base tracking-tight leading-tight group-hover:text-blue-600 transition">
                        SCP School
                    </div>
                    <div class="text-xs text-slate-500 leading-tight mt-0.5">
                        SMK Negeri 1 Bantul
                    </div>
                </div>
            </a>

            {{-- Navigasi Desktop --}}
            <nav class="hidden md:flex items-center gap-1" aria-label="Navigasi Utama">
                <a href="#tentang"
                   class="px-3.5 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition">
                    Tentang
                </a>
                <a href="#fitur"
                   class="px-3.5 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition">
                    Fitur Unggulan
                </a>
                <a href="#panduan"
                   class="px-3.5 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition">
                    Panduan Akses
                </a>
            </nav>

            {{-- Tombol CTA "Masuk ke Portal" & Toggle Mobile --}}
            <div class="flex items-center gap-2.5">
                <a href="{{ route('unduh.apk') }}"
                   class="hidden sm:inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-3.5 py-2.5 rounded-lg shadow-xs transition"
                   title="Unduh Aplikasi Android SCP (.apk)">
                    <svg class="w-4 h-4 text-emerald-100" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1.0002 0-.5517.4482-1.0003.9993-1.0003.5517 0 .9998.4486.9998 1.0003 0 .5516-.4481 1.0002-.9998 1.0002m-11.046 0c-.5516 0-.9997-.4486-.9997-1.0002 0-.5517.4481-1.0003.9997-1.0003.5512 0 .9993.4486.9993 1.0003 0 .5516-.4481 1.0002-.9993 1.0002m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5802 8.414 13.8427 8.125 12 8.125c-1.8428 0-3.5802.289-5.1368.8247L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/>
                    </svg>
                    <span>Unduh APK</span>
                </a>

                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium px-4 py-2.5 rounded-lg shadow-xs transition">
                    <span>Masuk ke Portal</span>
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>

                {{-- Tombol Menu Mobile --}}
                <button type="button"
                        onclick="toggleMobileMenu()"
                        aria-label="Buka menu navigasi"
                        class="md:hidden inline-flex items-center justify-center p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">
                    <svg id="iconMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg id="iconMenuClose" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Menu Dropdown Mobile --}}
        <div id="mobileMenu" class="hidden md:hidden border-t border-slate-200 bg-white px-4 pt-2 pb-4 space-y-1">
            <a href="#tentang" onclick="closeMobileMenu()" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">
                Tentang Sistem
            </a>
            <a href="#fitur" onclick="closeMobileMenu()" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">
                Fitur Unggulan
            </a>
            <a href="#panduan" onclick="closeMobileMenu()" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">
                Panduan Akses
            </a>
            <div class="pt-2 mt-2 border-t border-slate-100 flex flex-col gap-2">
                <a href="{{ route('unduh.apk') }}"
                   class="w-full text-center flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium py-2.5 rounded-lg transition">
                    <svg class="w-4 h-4 text-emerald-100" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1.0002 0-.5517.4482-1.0003.9993-1.0003.5517 0 .9998.4486.9998 1.0003 0 .5516-.4481 1.0002-.9998 1.0002m-11.046 0c-.5516 0-.9997-.4486-.9997-1.0002 0-.5517.4481-1.0003.9997-1.0003.5512 0 .9993.4486.9993 1.0003 0 .5516-.4481 1.0002-.9993 1.0002m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5802 8.414 13.8427 8.125 12 8.125c-1.8428 0-3.5802.289-5.1368.8247L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/>
                    </svg>
                    <span>Unduh Aplikasi Mobile (APK)</span>
                </a>
                <a href="{{ route('login') }}"
                   class="w-full text-center bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2.5 rounded-lg transition">
                    Masuk ke Portal Login
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1">

        {{-- ==================== HERO SECTION ==================== --}}
        <section class="relative overflow-hidden pt-10 pb-16 md:pt-16 md:pb-24 border-b border-slate-200/80 bg-gradient-to-b from-white via-white to-slate-100/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-8 items-center">

                    {{-- Kolom Kiri: Copywriting & CTA --}}
                    <div class="lg:col-span-6 space-y-6">
                        <div class="inline-flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200/80 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                Sistem Manajemen Akademik
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                SMK Negeri 1 Bantul
                            </span>
                        </div>

                        <h1 class="text-3xl sm:text-4xl lg:text-[2.5rem] font-bold text-slate-900 tracking-tight leading-[1.2]">
                            Kelola Jadwal, Jurnal Kelas, dan Kehadiran Sekolah dalam Satu Portal Terpadu
                        </h1>

                        <p class="text-slate-600 text-base leading-relaxed max-w-xl">
                            <strong class="font-semibold text-slate-800">SCP School</strong> menyederhanakan pemantauan kegiatan belajar mengajar berbasis siklus blok (Putaran 1 &amp; 2), rekapitulasi jurnal mengajar guru, presensi siswa real-time, hingga verifikasi izin secara transparan.
                        </p>

                        {{-- Tombol CTA Utama & Sekunder --}}
                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium px-6 py-3 rounded-lg transition shadow-xs">
                                <span>Masuk ke Portal Waka</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                            <a href="{{ route('unduh.apk') }}"
                               class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-5 py-3 rounded-lg transition shadow-xs"
                               title="Unduh Aplikasi Android (APK)">
                                <svg class="w-4 h-4 text-emerald-100" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1.0002 0-.5517.4482-1.0003.9993-1.0003.5517 0 .9998.4486.9998 1.0003 0 .5516-.4481 1.0002-.9998 1.0002m-11.046 0c-.5516 0-.9997-.4486-.9997-1.0002 0-.5517.4481-1.0003.9997-1.0003.5512 0 .9993.4486.9993 1.0003 0 .5516-.4481 1.0002-.9993 1.0002m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5802 8.414 13.8427 8.125 12 8.125c-1.8428 0-3.5802.289-5.1368.8247L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/>
                                </svg>
                                <span>Unduh Aplikasi Android</span>
                            </a>
                            <a href="#fitur"
                               class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-sm font-medium px-5 py-3 rounded-lg transition">
                                <span>Pelajari Fitur Sistem</span>
                            </a>
                        </div>

                        {{-- Info Singkat Ekosistem --}}
                        <div class="pt-4 border-t border-slate-200/80 grid grid-cols-3 gap-4 max-w-lg">
                            <div>
                                <div class="text-sm font-bold text-slate-900">Putaran 1 &amp; 2</div>
                                <div class="text-xs text-slate-500 mt-0.5">Manajemen Siklus Blok</div>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900">Real-Time</div>
                                <div class="text-xs text-slate-500 mt-0.5">Pantauan Jurnal &amp; Absensi</div>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900">Web &amp; Mobile</div>
                                <div class="text-xs text-slate-500 mt-0.5">Sinkronisasi Multi-Peran</div>
                            </div>
                        </div>
                    </div>

                    {{-- Kolom Kanan: Mockup Preview Tampilan Sistem (100% Selaras dengan Dashboard Waka) --}}
                    <div class="lg:col-span-6">
                        <div class="bg-white rounded-xl border border-slate-200 shadow-lg overflow-hidden">
                            {{-- Window Browser Top Bar --}}
                            <div class="bg-slate-900 px-4 py-2.5 flex items-center justify-between border-b border-slate-800">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-500/80"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400/80"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></span>
                                    <span class="ml-2 text-[11px] font-medium text-slate-400 truncate">
                                        SCP School — Panel Waka Kurikulum
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 border border-blue-400/20 text-[10px] font-medium">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                        Putaran 1
                                    </span>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-400/20 text-[10px] font-medium">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        Jadwal Terbuka
                                    </span>
                                </div>
                            </div>

                            {{-- Shell Mockup: Sidebar + Main Content --}}
                            <div class="grid grid-cols-12 bg-slate-100/80">
                                {{-- Mini Sidebar (#0f172a) --}}
                                <div class="hidden sm:flex sm:col-span-4 bg-[#0f172a] p-3.5 flex-col justify-between border-r border-slate-800">
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-800">
                                            <img src="{{ asset('images/logo-smkn1.png') }}" alt="Logo" class="w-7 h-7 rounded-full bg-white p-0.5 object-contain">
                                            <div class="min-w-0">
                                                <div class="text-xs font-semibold text-white truncate">SCP School</div>
                                                <div class="text-[10px] text-slate-400 truncate">SMK Negeri 1 Bantul</div>
                                            </div>
                                        </div>

                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md bg-blue-600 text-white text-xs font-medium">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/>
                                                </svg>
                                                <span>Dashboard</span>
                                            </div>
                                            <div class="pt-1.5 pb-0.5 px-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                                Manajemen Akademik
                                            </div>
                                            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-slate-400 text-xs">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                <span>Jadwal Mengajar</span>
                                            </div>
                                            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-slate-400 text-xs">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <span>Jurnal Mengajar</span>
                                            </div>
                                            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-slate-400 text-xs">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span>History Putaran</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
                                        <div>
                                            <div class="text-[11px] font-semibold text-white">Waka Kurikulum</div>
                                            <div class="text-[10px] text-slate-400">Panel Aktif</div>
                                        </div>
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    </div>
                                </div>

                                {{-- Mini Main Content --}}
                                <div class="col-span-12 sm:col-span-8 p-4 space-y-3.5">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-bold text-slate-900">Ringkasan Akademik Hari Ini</div>
                                            <div class="text-[11px] text-slate-500">Pemantauan KBM &amp; Presensi Kelas</div>
                                        </div>
                                        <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[11px] font-medium text-slate-600">
                                            Live Sync
                                        </span>
                                    </div>

                                    {{-- 2 Kartu Metrik Mockup --}}
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <div class="bg-white rounded-lg border border-slate-200 p-3">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <p class="text-[11px] font-medium text-slate-500">Jurnal Terisi</p>
                                                    <p class="text-lg font-bold text-slate-900 mt-0.5">94%</p>
                                                </div>
                                                <div class="w-7 h-7 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mt-2">
                                                <div class="h-full bg-emerald-500 rounded-full" style="width: 94%"></div>
                                            </div>
                                        </div>

                                        <div class="bg-white rounded-lg border border-slate-200 p-3">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <p class="text-[11px] font-medium text-slate-500">Kehadiran Siswa</p>
                                                    <p class="text-lg font-bold text-slate-900 mt-0.5">97.2%</p>
                                                </div>
                                                <div class="w-7 h-7 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mt-2">
                                                <div class="h-full bg-blue-600 rounded-full" style="width: 97%"></div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Mini Tabel Jadwal & Jurnal --}}
                                    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
                                        <div class="px-3 py-2 border-b border-slate-200 flex items-center justify-between">
                                            <span class="text-[11px] font-semibold text-slate-800">Status Sesi KBM Terkini</span>
                                            <span class="text-[10px] text-slate-400">Hari Ini</span>
                                        </div>
                                        <div class="divide-y divide-slate-100 text-xs">
                                            <div class="px-3 py-2 flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <div class="font-medium text-slate-800 truncate">Pemrograman Web &amp; Perangkat Bergerak</div>
                                                    <div class="text-[11px] text-slate-400">XII PPLG 1 · Lab RPL 1 · 07:00 – 09:15</div>
                                                </div>
                                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-medium shrink-0">
                                                    Jurnal Terisi
                                                </span>
                                            </div>
                                            <div class="px-3 py-2 flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <div class="font-medium text-slate-800 truncate">Basis Data Terapan</div>
                                                    <div class="text-[11px] text-slate-400">XI PPLG 2 · Lab RPL 2 · 09:30 – 11:45</div>
                                                </div>
                                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-medium shrink-0">
                                                    Jurnal Terisi
                                                </span>
                                            </div>
                                            <div class="px-3 py-2 flex items-center justify-between gap-2 bg-slate-50/70">
                                                <div class="min-w-0">
                                                    <div class="font-medium text-slate-800 truncate">Pengajuan Izin Siswa (Terverifikasi)</div>
                                                    <div class="text-[11px] text-slate-400">Sinkronisasi otomatis ke daftar presensi kelas</div>
                                                </div>
                                                <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-medium shrink-0">
                                                    Mobile API
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- ==================== TENTANG (ABOUT) ==================== --}}
        <section id="tentang" class="py-16 md:py-20 scroll-mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto text-center mb-12">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200/80 text-xs font-medium mb-3">
                        Tentang SCP School
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                        Menghubungkan Sekolah, Siswa, dan Orang Tua Secara Real-Time
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base mt-3 leading-relaxed">
                        SCP School dirancang khusus untuk mendukung tata kelola akademik di SMK Negeri 1 Bantul. Sistem ini mengintegrasikan pengelolaan jadwal sistem blok, pencatatan jurnal mengajar harian, absensi kelas, serta perizinan daring ke dalam ekosistem yang saling terhubung.
                    </p>
                </div>

                {{-- 3 Pilar Pengguna Sistem --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    {{-- Pihak Sekolah (Waka & Guru) --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-slate-900 text-white flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/>
                                    </svg>
                                </div>
                                <span class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-medium">
                                    Portal Web &amp; App Guru
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-2">
                                Waka Kurikulum &amp; Guru Pengajar
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Waka Kurikulum mengelola master data, jadwal blok (Putaran 1 &amp; 2), serta memantau keterlambatan pengisian jurnal melalui Portal Web. Guru mencatat jurnal mengajar, melakukan presensi siswa, dan memverifikasi izin langsung dari kelas.
                            </p>
                        </div>
                        <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Akses Panel Web Waka</span>
                            <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">Masuk Portal →</a>
                        </div>
                    </div>

                    {{-- Siswa --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0121 18.5M12 14v7"/>
                                    </svg>
                                </div>
                                <span class="px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200/80 text-xs font-medium">
                                    Aplikasi Mobile
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-2">
                                Siswa &amp; Peserta Didik
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Memudahkan siswa melihat jadwal pelajaran harian sesuai putaran siklus aktif, mengecek lokasi ruangan/bengkel praktik, memantau rekap kehadiran pribadi, serta mengajukan permohonan izin atau sakit secara daring beserta lampiran bukti.
                            </p>
                        </div>
                        <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Jadwal &amp; Izin Daring</span>
                            <span class="text-slate-700 font-medium">Tersinkronisasi Otomatis</span>
                        </div>
                    </div>

                    {{-- Orang Tua / Wali --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <span class="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-xs font-medium">
                                    Pemantauan Wali
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-2">
                                Orang Tua / Wali Siswa
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Memberikan transparansi penuh bagi orang tua untuk memantau kehadiran putra-putrinya di setiap mata pelajaran, mengetahui riwayat ketidakhadiran (Hadir, Izin, Sakit, Alpha), dan memastikan pengajuan izin telah terverifikasi pihak sekolah.
                            </p>
                        </div>
                        <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Transparansi Kehadiran</span>
                            <span class="text-emerald-600 font-medium">Akurat &amp; Terpantau</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ==================== FITUR UNGGULAN (BENTO GRID) ==================== --}}
        <section id="fitur" class="py-16 md:py-20 bg-white border-y border-slate-200 scroll-mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium mb-3">
                            Fitur Unggulan Sistem
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                            Dirancang Sesuai Alur Akademik SMK Negeri 1 Bantul
                        </h2>
                    </div>
                    <p class="text-sm text-slate-500 max-w-md">
                        Seluruh modul saling terhubung secara otomatis—mulai dari penyusunan jadwal blok hingga laporan rekapitulasi presensi akhir putaran.
                    </p>
                </div>

                {{-- Bento Grid 4 Fitur Utama --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                    {{-- Fitur 1: Jadwal Pelajaran & Siklus Blok (7 Kolom) --}}
                    <div class="lg:col-span-7 bg-slate-50/70 rounded-xl border border-slate-200 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 border border-blue-200/60 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200/80 text-xs font-medium">
                                    Putaran 1 &amp; Putaran 2
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-slate-900 mb-2">
                                Manajemen Jadwal Pelajaran &amp; Siklus Blok
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed mb-5">
                                Mendukung pengaturan jadwal sistem blok (Putaran 1 dan Putaran 2), fitur kunci jadwal (Schedule Lock) untuk mencegah perubahan tanpa izin, impor jadwal massal via template Excel, serta penyimpanan riwayat putaran secara otomatis.
                            </p>
                        </div>

                        {{-- Mini Visual Panel Kontrol Siklus --}}
                        <div class="bg-white rounded-lg border border-slate-200 p-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-4">
                                <div>
                                    <div class="text-[11px] font-medium text-slate-500">Siklus Blok Aktif</div>
                                    <div class="text-sm font-bold text-slate-900 mt-0.5">Putaran 1 / Putaran 2</div>
                                </div>
                                <div class="h-7 w-px bg-slate-200"></div>
                                <div>
                                    <div class="text-[11px] font-medium text-slate-500">Proteksi Jadwal</div>
                                    <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 mt-0.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        Kunci &amp; Buka Jadwal Instan
                                    </div>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded bg-slate-100 text-slate-700 text-xs font-medium">
                                Import Excel Ready
                            </span>
                        </div>
                    </div>

                    {{-- Fitur 2: Jurnal Mengajar (5 Kolom) --}}
                    <div class="lg:col-span-5 bg-slate-50/70 rounded-xl border border-slate-200 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200/60 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 border border-amber-200/80 text-xs font-medium">
                                    Deteksi &gt;30 Menit
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-slate-900 mb-2">
                                Jurnal Mengajar &amp; Pantauan Keterlambatan
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed mb-5">
                                Guru mengisi topik materi dan catatan kelas setiap sesi KBM. Sistem secara otomatis mendeteksi sesi mengajar yang belum mengisi jurnal lebih dari 30 menit untuk memudahkan tindak lanjut Waka Kurikulum.
                            </p>
                        </div>

                        <div class="bg-white rounded-lg border border-slate-200 p-3.5 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-700">Keterisian Jurnal Harian</span>
                                <span class="font-bold text-emerald-600">Terpantau Otomatis</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full w-11/12"></div>
                            </div>
                            <div class="text-[11px] text-slate-400">
                                Filter rekap berdasarkan tanggal, tingkat, jurusan, rombel, dan pengajar.
                            </div>
                        </div>
                    </div>

                    {{-- Fitur 3: Pengajuan Izin / Sakit Online (5 Kolom) --}}
                    <div class="lg:col-span-5 bg-slate-50/70 rounded-xl border border-slate-200 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 border border-amber-200/60 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-medium">
                                    Verifikasi Digital
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-slate-900 mb-2">
                                Pengajuan Izin &amp; Sakit Online
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed mb-5">
                                Siswa atau orang tua dapat mengajukan permohonan izin maupun keterangan sakit lengkap dengan unggahan dokumen pendukung. Setelah diverifikasi, status kehadiran siswa pada sesi kelas terkait langsung diperbarui otomatis.
                            </p>
                        </div>

                        <div class="bg-white rounded-lg border border-slate-200 p-3.5 flex items-center justify-between gap-2 text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="text-slate-700 font-medium">Alur Persetujuan Cepat</span>
                            </div>
                            <span class="text-slate-400">Pending → Verified</span>
                        </div>
                    </div>

                    {{-- Fitur 4: Monitoring Kehadiran & Dashboard Waka (7 Kolom) --}}
                    <div class="lg:col-span-7 bg-slate-50/70 rounded-xl border border-slate-200 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-slate-800 text-white flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-xs font-medium">
                                    Analitik &amp; Master Data
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-slate-900 mb-2">
                                Monitoring Kehadiran &amp; Dashboard Eksekutif Waka
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed mb-5">
                                Pusat kendali komprehensif untuk memantau persentase kehadiran siswa per kelas, mengelola data master (Pengguna, Guru, Kelas, Ruangan, Mata Pelajaran), proses kenaikan kelas, hingga melihat arsip histori setiap putaran akademik.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <div class="bg-white rounded-lg border border-slate-200 px-3 py-2 text-center">
                                <div class="text-xs font-bold text-emerald-600">Hadir</div>
                                <div class="text-[11px] text-slate-400">Presensi Sesi</div>
                            </div>
                            <div class="bg-white rounded-lg border border-slate-200 px-3 py-2 text-center">
                                <div class="text-xs font-bold text-blue-600">Izin</div>
                                <div class="text-[11px] text-slate-400">Terverifikasi</div>
                            </div>
                            <div class="bg-white rounded-lg border border-slate-200 px-3 py-2 text-center">
                                <div class="text-xs font-bold text-amber-600">Sakit</div>
                                <div class="text-[11px] text-slate-400">Surat Dokter/Wali</div>
                            </div>
                            <div class="bg-white rounded-lg border border-slate-200 px-3 py-2 text-center">
                                <div class="text-xs font-bold text-red-600">Alpha</div>
                                <div class="text-[11px] text-slate-400">Deteksi Dini</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- ==================== PANDUAN AKSES SINGKAT (3 LANGKAH) ==================== --}}
        <section id="panduan" class="py-16 md:py-20 scroll-mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mx-auto text-center mb-12">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200/80 text-xs font-medium mb-3">
                        Panduan Penggunaan
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                        3 Langkah Mudah Mengakses Sistem SCP
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base mt-2.5">
                        Ikuti alur singkat berikut untuk mulai mengelola atau memantau kegiatan akademik di SMK Negeri 1 Bantul.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    {{-- Langkah 1 --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-6 relative">
                        <div class="flex items-center justify-between mb-4">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-900 text-white text-sm font-bold">
                                01
                            </span>
                            <span class="text-xs font-medium text-slate-400">Autentikasi Akun</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">
                            Masuk ke Portal Sesuai Peran
                        </h3>
                        <p class="text-sm text-slate-600 leading-relaxed mb-3">
                            Klik tombol <strong class="font-semibold text-slate-800">Masuk ke Portal</strong> dan login menggunakan Username atau NIP bagi Waka Kurikulum. Untuk Guru, Siswa, dan Orang Tua dapat login melalui aplikasi mobile SCP.
                        </p>
                        <a href="{{ route('unduh.apk') }}"
                           class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-3 py-1.5 rounded-md transition">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1.0002 0-.5517.4482-1.0003.9993-1.0003.5517 0 .9998.4486.9998 1.0003 0 .5516-.4481 1.0002-.9998 1.0002m-11.046 0c-.5516 0-.9997-.4486-.9997-1.0002 0-.5517.4481-1.0003.9997-1.0003.5512 0 .9993.4486.9993 1.0003 0 .5516-.4481 1.0002-.9993 1.0002m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5802 8.414 13.8427 8.125 12 8.125c-1.8428 0-3.5802.289-5.1368.8247L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/>
                            </svg>
                            <span>Unduh APK Mobile (Guru/Siswa/Ortu) &rarr;</span>
                        </a>
                    </div>

                    {{-- Langkah 2 --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-6 relative">
                        <div class="flex items-center justify-between mb-4">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white text-sm font-bold">
                                02
                            </span>
                            <span class="text-xs font-medium text-slate-400">Operasional Harian</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">
                            Kelola Jadwal, Jurnal &amp; Absensi
                        </h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Waka mengatur jadwal putaran aktif dan master kelas. Guru mengisi jurnal mengajar serta presensi di kelas, sedangkan siswa/wali dapat mengajukan izin apabila berhalangan hadir.
                        </p>
                    </div>

                    {{-- Langkah 3 --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-6 relative">
                        <div class="flex items-center justify-between mb-4">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-600 text-white text-sm font-bold">
                                03
                            </span>
                            <span class="text-xs font-medium text-slate-400">Monitoring &amp; Evaluasi</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">
                            Pantau Rekapitulasi Real-Time
                        </h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Seluruh data kehadiran dan keterisian jurnal langsung terangkum otomatis di Dashboard Waka serta riwayat putaran, siap dievaluasi maupun diekspor kapan saja.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ==================== CTA PENUTUP ==================== --}}
        <section class="pb-16 md:pb-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="bg-[#0f172a] rounded-2xl border border-slate-800 px-6 py-10 sm:px-12 sm:py-12 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="max-w-2xl space-y-2">
                        <div class="inline-flex items-center gap-2 text-xs font-medium text-blue-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                            <span>Portal Resmi Waka Kurikulum · SMK Negeri 1 Bantul</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                            Siap Mengelola Kegiatan Akademik Hari Ini?
                        </h2>
                        <p class="text-slate-400 text-sm sm:text-base leading-relaxed">
                            Masuk ke portal untuk mulai memantau jadwal mengajar aktif, rekapitulasi jurnal kelas, serta laporan kehadiran siswa secara terpusat.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 shrink-0">
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-6 py-3 rounded-lg transition shadow-xs">
                            <span>Masuk ke Portal Sekarang</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="#panduan"
                           class="inline-flex items-center gap-2 px-5 py-3 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white text-sm font-medium transition">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <span>Panduan Akses</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    {{-- ==================== FOOTER ==================== --}}
    <footer class="bg-white border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-8 border-b border-slate-100">
                {{-- Info Sekolah --}}
                <div class="md:col-span-5 space-y-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/logo-smkn1.png') }}"
                             alt="SMK Negeri 1 Bantul"
                             class="h-10 w-10 object-contain rounded-full bg-white p-0.5 border border-slate-200">
                        <div>
                            <div class="text-slate-900 font-semibold text-sm tracking-tight">SCP School</div>
                            <div class="text-xs text-slate-500">SMK Negeri 1 Bantul</div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed max-w-sm">
                        Sistem Informasi Manajemen Jadwal Pelajaran, Jurnal Mengajar Kelas, dan Pemantauan Kehadiran Siswa Terpadu SMK Negeri 1 Bantul.
                    </p>
                </div>

                {{-- Navigasi Cepat --}}
                <div class="md:col-span-3 space-y-2.5">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Navigasi Halaman</div>
                    <ul class="space-y-2 text-xs text-slate-600">
                        <li><a href="#tentang" class="hover:text-slate-900 transition">Tentang Sistem</a></li>
                        <li><a href="#fitur" class="hover:text-slate-900 transition">Fitur Unggulan</a></li>
                        <li><a href="#panduan" class="hover:text-slate-900 transition">Panduan Akses</a></li>
                        <li><a href="{{ route('unduh.apk') }}" class="text-emerald-600 hover:text-emerald-700 font-medium transition inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1.0002 0-.5517.4482-1.0003.9993-1.0003.5517 0 .9998.4486.9998 1.0003 0 .5516-.4481 1.0002-.9998 1.0002m-11.046 0c-.5516 0-.9997-.4486-.9997-1.0002 0-.5517.4481-1.0003.9997-1.0003.5512 0 .9993.4486.9993 1.0003 0 .5516-.4481 1.0002-.9993 1.0002m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5802 8.414 13.8427 8.125 12 8.125c-1.8428 0-3.5802.289-5.1368.8247L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/></svg>Unduh Aplikasi Android (APK)</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-blue-600 font-medium transition">Portal Login Waka</a></li>
                    </ul>
                </div>

                {{-- Bantuan & Akses --}}
                <div class="md:col-span-4 space-y-2.5">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Informasi &amp; Bantuan Akses</div>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Portal web ini dikhususkan bagi <strong class="text-slate-700">Waka Kurikulum</strong>. Bapak/Ibu Guru, Siswa, dan Orang Tua silakan menggunakan <strong class="text-slate-700">Aplikasi Mobile SCP School</strong> untuk mengakses fitur harian.
                    </p>
                    <div class="pt-1">
                        <a href="{{ route('password.request') }}" class="text-xs text-blue-600 hover:underline font-medium">
                            Reset kata sandi akun melalui OTP Email →
                        </a>
                    </div>
                </div>
            </div>

            <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400">
                <p>&copy; {{ date('Y') }} SMK Negeri 1 Bantul · SCP School. Hak cipta dilindungi.</p>
                <p>Sistem Manajemen Akademik &amp; Presensi Sekolah</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const iconOpen = document.getElementById('iconMenuOpen');
            const iconClose = document.getElementById('iconMenuClose');
            if (!menu) return;
            const isHidden = menu.classList.toggle('hidden');
            if (iconOpen && iconClose) {
                iconOpen.classList.toggle('hidden', !isHidden);
                iconClose.classList.toggle('hidden', isHidden);
            }
        }

        function closeMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const iconOpen = document.getElementById('iconMenuOpen');
            const iconClose = document.getElementById('iconMenuClose');
            if (menu && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                if (iconOpen && iconClose) {
                    iconOpen.classList.remove('hidden');
                    iconClose.classList.add('hidden');
                }
            }
        }
    </script>
</body>
</html>

