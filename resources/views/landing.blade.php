<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCP School - Sistem Manajemen Akademik, Jurnal KBM & Presensi SMK Negeri 1 Bantul</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-smkn1.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-smkn1.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }
        table {
            font-variant-numeric: tabular-nums;
        }
        .gradient-brand {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }
        .glow-effect {
            box-shadow: 0 0 50px -12px rgba(37, 99, 235, 0.25);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col selection:bg-blue-600 selection:text-white">

    @php
        try {
            $sysConfig = \App\Models\SystemConfiguration::current();
            $totalSiswa = \App\Models\User::whereHas('role', fn ($q) => $q->where('role_name', 'Siswa'))->where('is_alumni', false)->count();
            $totalGuru = \App\Models\User::whereHas('role', fn ($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas']))->count();
            $totalKelas = \App\Models\Classes::count();
            $activeCycle = $sysConfig->active_cycle ?? 1;
            $isScheduleLocked = $sysConfig->is_schedule_locked ?? false;
        } catch (\Throwable $e) {
            $totalSiswa = 3400;
            $totalGuru = 70;
            $totalKelas = 53;
            $activeCycle = 1;
            $isScheduleLocked = false;
        }
    @endphp

    {{-- ==================== NAVBAR (STICKY) ==================== --}}
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200/90 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            
            {{-- Brand / Logo SCP --}}
            <a href="{{ url('/') }}" class="flex items-center gap-3 group focus:outline-none shrink-0">
                <img src="{{ asset('images/logo-smkn1.png') }}"
                     alt="SMK Negeri 1 Bantul"
                     class="h-10 w-10 object-contain rounded-full bg-white p-0.5 border border-slate-200 shadow-xs transition transform group-hover:scale-105 duration-200">
                <div>
                    <div class="text-slate-900 font-bold text-base tracking-tight leading-tight group-hover:text-blue-600 transition">
                        SCP School
                    </div>
                    <div class="text-[11px] text-slate-500 leading-tight flex items-center gap-1.5 mt-0.5">
                        <span>SMK Negeri 1 Bantul</span>
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    </div>
                </div>
            </a>

            {{-- Navigasi Desktop --}}
            <nav class="hidden lg:flex items-center gap-1" aria-label="Navigasi Utama">
                <a href="#tentang" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    Tentang
                </a>
                <a href="#peran" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    Ekosistem Peran
                </a>
                <a href="#fitur" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    Fitur Unggulan
                </a>
                <a href="#platform" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    Web &amp; Mobile
                </a>
                <a href="#panduan" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    Panduan Akses
                </a>
            </nav>

            {{-- Tombol Aksi Kanan --}}
            <div class="flex items-center gap-2.5">
                <a href="{{ route('unduh.apk') }}"
                   class="hidden sm:inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200/80 text-slate-700 text-xs font-semibold px-3 py-2 rounded-lg border border-slate-200 transition"
                   title="Unduh Aplikasi Android SCP (.apk)">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1.0002 0-.5517.4482-1.0003.9993-1.0003.5517 0 .9998.4486.9998 1.0003 0 .5516-.4481 1.0002-.9998 1.0002m-11.046 0c-.5516 0-.9997-.4486-.9997-1.0002 0-.5517.4481-1.0003.9997-1.0003.5512 0 .9993.4486.9993 1.0003 0 .5516-.4481 1.0002-.9993 1.0002m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5802 8.414 13.8427 8.125 12 8.125c-1.8428 0-3.5802.289-5.1368.8247L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/>
                    </svg>
                    <span>Unduh APK</span>
                </a>

                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-semibold px-4 py-2 sm:py-2.5 rounded-lg shadow-xs hover:shadow transition">
                    <span>Masuk Portal Waka</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>

                {{-- Tombol Menu Mobile --}}
                <button type="button"
                        onclick="toggleMobileMenu()"
                        aria-label="Buka menu navigasi"
                        class="lg:hidden inline-flex items-center justify-center p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 transition">
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
        <div id="mobileMenu" class="hidden lg:hidden border-t border-slate-200 bg-white px-4 pt-2 pb-4 space-y-1">
            <a href="#tentang" onclick="closeMobileMenu()" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">
                Tentang Sistem
            </a>
            <a href="#peran" onclick="closeMobileMenu()" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">
                Ekosistem 5 Pilar Pengguna
            </a>
            <a href="#fitur" onclick="closeMobileMenu()" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">
                Fitur Unggulan (Web &amp; Mobile)
            </a>
            <a href="#platform" onclick="closeMobileMenu()" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">
                Arsitektur Web vs Mobile
            </a>
            <a href="#panduan" onclick="closeMobileMenu()" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">
                Panduan Akses &amp; Download App
            </a>
            <div class="pt-2 mt-2 border-t border-slate-100 flex flex-col gap-2">
                <a href="{{ route('unduh.apk') }}"
                   class="w-full text-center flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2.5 rounded-lg transition">
                    <svg class="w-4 h-4 text-emerald-100" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1.0002 0-.5517.4482-1.0003.9993-1.0003.5517 0 .9998.4486.9998 1.0003 0 .5516-.4481 1.0002-.9998 1.0002m-11.046 0c-.5516 0-.9997-.4486-.9997-1.0002 0-.5517.4481-1.0003.9997-1.0003.5512 0 .9993.4486.9993 1.0003 0 .5516-.4481 1.0002-.9993 1.0002m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5802 8.414 13.8427 8.125 12 8.125c-1.8428 0-3.5802.289-5.1368.8247L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/>
                    </svg>
                    <span>Unduh Aplikasi Mobile (APK)</span>
                </a>
                <a href="{{ route('login') }}"
                   class="w-full text-center block bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold py-2.5 rounded-lg transition">
                    Masuk ke Portal Login Waka
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1">

        {{-- ==================== HERO SECTION ==================== --}}
        <section class="relative overflow-hidden pt-10 pb-16 md:pt-16 md:pb-24 border-b border-slate-200 bg-gradient-to-b from-white via-slate-50 to-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                {{-- Hero Header --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-8 items-center">
                    
                    {{-- Kolom Kiri: Copywriting & CTA --}}
                    <div class="lg:col-span-6 space-y-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold">
                                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                Platform Akademik &amp; KBM Terintegrasi
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                SMK Negeri 1 Bantul
                            </span>
                        </div>

                        <h1 class="text-3xl sm:text-4xl lg:text-[2.65rem] font-extrabold text-slate-900 tracking-tight leading-[1.18]">
                            Satu Ekosistem Terpadu untuk Jadwal Blok, Jurnal KBM, dan Presensi Sekolah
                        </h1>

                        <p class="text-slate-600 text-sm sm:text-base leading-relaxed max-w-xl">
                            <strong class="font-semibold text-slate-800">SCP School</strong> menyatukan tata kelola kurikulum berbasis siklus blok (Putaran 1 &amp; 2) pada <strong class="text-slate-800">Portal Web Waka</strong>, serta mendigitalisasi jurnal mengajar, presensi siswa per jam, kamera ber-watermark otomatis, dan perizinan langsung dari <strong class="text-slate-800">Aplikasi Mobile SCP</strong> bagi Guru, Wali Kelas, Siswa, dan Orang Tua.
                        </p>

                        {{-- Tombol CTA Elegan & Profesional --}}
                        <div class="space-y-3 pt-1">
                            <div class="flex flex-wrap items-center gap-3">
                                {{-- Tombol Utama: Portal Waka --}}
                                <a href="{{ route('login') }}"
                                   class="inline-flex items-center justify-center gap-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold px-6 py-3.5 rounded-xl shadow-xs hover:shadow-md transition duration-150 group">
                                    <span>Masuk ke Portal Waka</span>
                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-white group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                    </svg>
                                </a>

                                {{-- Tombol Sekunder: Unduh Mobile App --}}
                                <a href="{{ route('unduh.apk') }}"
                                   class="inline-flex items-center justify-center gap-3 bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 text-sm font-semibold px-5 py-3 rounded-xl shadow-2xs hover:border-slate-400 transition duration-150 group"
                                   title="Unduh Aplikasi Android SCP (.apk)">
                                    <div class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-500/20 group-hover:bg-emerald-500/20 transition">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1.0002 0-.5517.4482-1.0003.9993-1.0003.5517 0 .9998.4486.9998 1.0003 0 .5516-.4481 1.0002-.9998 1.0002m-11.046 0c-.5516 0-.9997-.4486-.9997-1.0002 0-.5517.4481-1.0003.9997-1.0003.5512 0 .9993.4486.9993 1.0003 0 .5516-.4481 1.0002-.9993 1.0002m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5802 8.414 13.8427 8.125 12 8.125c-1.8428 0-3.5802.289-5.1368.8247L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="leading-tight text-xs font-semibold text-slate-800">Unduh Mobile App</div>
                                        <div class="text-[10px] text-slate-400 font-normal">APK Android &bull; v1.0.0</div>
                                    </div>
                                </a>
                            </div>

                            {{-- Tautan Pendukung --}}
                            <div class="flex items-center gap-3 text-xs text-slate-500 pt-1">
                                <a href="#fitur" class="inline-flex items-center gap-1 hover:text-blue-600 transition font-medium">
                                    <span>Pelajari fitur lengkap</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </a>
                                <span class="text-slate-300">&bull;</span>
                                <a href="#panduan" class="inline-flex items-center gap-1 hover:text-blue-600 transition font-medium">
                                    <span>Panduan akses</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </a>
                                <span class="text-slate-300">&bull;</span>
                                <span class="text-[11px] text-emerald-600 font-medium">Live Sync Web &amp; App</span>
                            </div>
                        </div>

                        {{-- Live Badges / Bar Status --}}
                        <div class="pt-4 border-t border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-xl">
                            <div class="bg-white p-3 rounded-lg border border-slate-200">
                                <div class="text-[11px] font-medium text-slate-500">Siklus Blok</div>
                                <div class="text-sm font-bold text-slate-900 mt-0.5 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                    Putaran {{ $activeCycle }}
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-slate-200">
                                <div class="text-[11px] font-medium text-slate-500">Status Jadwal</div>
                                <div class="text-sm font-bold {{ $isScheduleLocked ? 'text-amber-600' : 'text-emerald-600' }} mt-0.5">
                                    {{ $isScheduleLocked ? 'Terkunci' : 'Terbuka' }}
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-slate-200">
                                <div class="text-[11px] font-medium text-slate-500">Sync Data</div>
                                <div class="text-sm font-bold text-slate-900 mt-0.5">Real-Time API</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-slate-200">
                                <div class="text-[11px] font-medium text-slate-500">Platform</div>
                                <div class="text-sm font-bold text-slate-900 mt-0.5">Web &amp; Mobile</div>
                            </div>
                        </div>

                    </div>

                    {{-- Kolom Kanan: Visual Showcase Sinergi Web & Mobile --}}
                    <div class="lg:col-span-6 relative">
                        <div class="relative mx-auto max-w-lg lg:max-w-none">
                            
                            {{-- Frame Web Browser (Waka Kurikulum) --}}
                            <div class="bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden glow-effect">
                                {{-- Window Browser Bar --}}
                                <div class="bg-slate-900 px-4 py-2.5 flex items-center justify-between border-b border-slate-800">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        <span class="ml-2 text-[11px] font-medium text-slate-300 truncate">
                                            SCP Web · Panel Waka Kurikulum
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 border border-blue-400/20 text-[10px] font-semibold">
                                            Putaran {{ $activeCycle }} Aktif
                                        </span>
                                    </div>
                                </div>

                                {{-- Isi Mockup Web --}}
                                <div class="p-4 sm:p-5 bg-slate-50 space-y-3.5">
                                    
                                    {{-- Mini Metric Bar --}}
                                    <div class="grid grid-cols-3 gap-2.5">
                                        <div class="bg-white p-2.5 rounded-lg border border-slate-200 shadow-2xs">
                                            <p class="text-[10px] text-slate-500 font-medium">Pengguna</p>
                                            <p class="text-base font-bold text-slate-900">{{ number_format($totalSiswa + $totalGuru) }}</p>
                                            <span class="text-[9px] text-emerald-600 font-medium">Terverifikasi</span>
                                        </div>
                                        <div class="bg-white p-2.5 rounded-lg border border-slate-200 shadow-2xs">
                                            <p class="text-[10px] text-slate-500 font-medium">Keterisian Jurnal</p>
                                            <p class="text-base font-bold text-slate-900">96.8%</p>
                                            <span class="text-[9px] text-blue-600 font-medium">Alarm &gt;30 Mnt</span>
                                        </div>
                                        <div class="bg-white p-2.5 rounded-lg border border-slate-200 shadow-2xs">
                                            <p class="text-[10px] text-slate-500 font-medium">Kehadiran Siswa</p>
                                            <p class="text-base font-bold text-slate-900">98.1%</p>
                                            <span class="text-[9px] text-emerald-600 font-medium">Live Absensi</span>
                                        </div>
                                    </div>

                                    {{-- Mini List Sesi KBM Hari Ini --}}
                                    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden text-xs">
                                        <div class="px-3 py-2 bg-slate-100/70 border-b border-slate-200 flex items-center justify-between font-semibold text-slate-700">
                                            <span>Monitoring Sesi KBM Hari Ini</span>
                                            <span class="text-[10px] text-slate-400 font-normal">Sinkronisasi Mobile</span>
                                        </div>
                                        <div class="divide-y divide-slate-100">
                                            <div class="px-3 py-2 flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <div class="font-medium text-slate-800 truncate">Pemrograman Web &amp; Mobile</div>
                                                    <div class="text-[10px] text-slate-400 truncate">XII PPLG 1 · Lab RPL 1 · Guru: Bpk. Arif, S.Kom</div>
                                                </div>
                                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-semibold shrink-0">
                                                    Jurnal Lengkap
                                                </span>
                                            </div>
                                            <div class="px-3 py-2 flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <div class="font-medium text-slate-800 truncate">Praktikum Akuntansi Keuangan</div>
                                                    <div class="text-[10px] text-slate-400 truncate">XI AKL 2 · Lab Akuntansi · Hadir: 35 · Izin: 1</div>
                                                </div>
                                                <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-semibold shrink-0">
                                                    Presensi Sinkron
                                                </span>
                                            </div>
                                            <div class="px-3 py-2 flex items-center justify-between gap-2 bg-slate-50/50">
                                                <div class="min-w-0">
                                                    <div class="font-medium text-slate-800 truncate">Pengajuan Izin Sakit (Surat Dokter)</div>
                                                    <div class="text-[10px] text-slate-400 truncate">Disetujui Guru BK &amp; Wali Kelas via Mobile</div>
                                                </div>
                                                <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-semibold shrink-0">
                                                    Verified
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                {{-- Live Ecosystem Counters Bar --}}
                <div class="mt-12 pt-8 border-t border-slate-200 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="p-4 rounded-xl bg-white border border-slate-200">
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($totalSiswa) }}+</div>
                        <div class="text-xs font-semibold text-slate-700 mt-0.5">Siswa &amp; Peserta Didik Aktif</div>
                        <p class="text-[11px] text-slate-400 mt-1">Terbagi dalam rombel jurusan &amp; jenjang X, XI, XII.</p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-200">
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $totalGuru > 0 ? $totalGuru : 70 }}+</div>
                        <div class="text-xs font-semibold text-slate-700 mt-0.5">Guru, Wali Kelas &amp; Konselor BK</div>
                        <p class="text-[11px] text-slate-400 mt-1">Mencatat jurnal, presensi, dan verifikasi izin.</p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-200">
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $totalKelas > 0 ? $totalKelas : 53 }} Rombel</div>
                        <div class="text-xs font-semibold text-slate-700 mt-0.5">Kelas &amp; Ruang Bengkel/Lab</div>
                        <p class="text-[11px] text-slate-400 mt-1">Kapasitas maksimal 36 siswa per rombel.</p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-200">
                        <div class="text-2xl sm:text-3xl font-extrabold text-blue-600 tracking-tight">2 Putaran</div>
                        <div class="text-xs font-semibold text-slate-700 mt-0.5">Sistem Siklus Blok Mandiri</div>
                        <p class="text-[11px] text-slate-400 mt-1">Jadwal bergantian dengan proteksi Schedule Lock.</p>
                    </div>
                </div>

            </div>
        </section>

        {{-- ==================== TENTANG (ABOUT) ==================== --}}
        <section id="tentang" class="py-16 md:py-20 scroll-mt-16 bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="max-w-3xl mx-auto text-center mb-12">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold mb-3">
                        Tentang SCP School
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                        Transformasi Digital Pembelajaran SMK Negeri 1 Bantul
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base mt-3 leading-relaxed">
                        Dirancang khusus untuk menjawab kebutuhan karakteristik sekolah kejuruan modern, <strong class="text-slate-800">SCP School</strong> menjembatani manajemen kurikulum sistem blok, kepatuhan pengisian jurnal guru, validitas presensi kehadiran harian, dan keterbukaan akses informasi bagi orang tua.
                    </p>
                </div>

                {{-- 3 Nilai Utama Sistem --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 flex flex-col justify-between hover:shadow-xs transition">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center mb-4 shadow-xs">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-2">Presisi Kurikulum Sistem Blok</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Mendukung penuh rotasi jadwal Putaran 1 dan Putaran 2 yang khas di SMK. Dilengkapi fitur <em>Schedule Lock</em> untuk mengunci jadwal dari perubahan tak terduga saat jam efektif berlangsung.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] font-semibold text-blue-700">
                            Rotasi Otomatis &bull; Anti-Bentrok Jadwal
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 flex flex-col justify-between hover:shadow-xs transition">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center mb-4 shadow-xs">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-2">Akuntabilitas &amp; Bukti KBM Nyata</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Pengisian jurnal KBM oleh guru dilengkapi dokumentasi foto ber-watermark otomatis (tanggal, jam, mapel, kelas). Sistem juga mendeteksi otomatis keterlambatan pengisian jurnal lebih dari 30 menit.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] font-semibold text-emerald-700">
                            Watermark Kamera &bull; Alarm &gt;30 Menit
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 flex flex-col justify-between hover:shadow-xs transition">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center mb-4 shadow-xs">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-2">Transparansi Presensi Real-Time</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Kehadiran siswa tercatat per jam pelajaran dan langsung tersinkronisasi ke aplikasi orang tua. Pengajuan izin sakit dilengkapi verifikasi berjenjang oleh Wali Kelas dan Guru BK.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] font-semibold text-slate-800">
                            Multi-Anak Ortu &bull; Verifikasi Digital
                        </div>
                    </div>
                </div>

            </div>
        </section>

        {{-- ==================== EKOSISTEM 5 PILAR PERAN ==================== --}}
        <section id="peran" class="py-16 md:py-20 scroll-mt-16 bg-slate-100/70 border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="max-w-3xl mx-auto text-center mb-12">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-200 text-slate-800 text-xs font-semibold mb-3">
                        Multi-Role RBAC (Role-Based Access Control)
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                        5 Peran Pengguna dalam Satu Ekosistem Harmonis
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base mt-2.5">
                        Setiap peran memiliki hak akses yang terisolasi, aman, dan dirancang khusus sesuai tugas serta fungsinya di sekolah.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    
                    {{-- 1. Waka Kurikulum --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between shadow-2xs">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-10 h-10 rounded-lg bg-slate-900 text-white flex items-center justify-center font-bold text-sm">
                                    01
                                </div>
                                <span class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-medium border border-slate-200">
                                    Portal Web Browser
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-2">Waka Kurikulum</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-4">
                                Bertindak sebagai administrator utama akademik: mengelola pergantian Putaran 1 &amp; 2, kunci jadwal, impor jadwal Excel, pantauan keterlambatan jurnal harian, master data user/ruangan/kelas, serta memproses kenaikan kelas &amp; kelulusan alumni.
                            </p>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-medium">Akses via Desktop</span>
                            <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-600 hover:underline">Masuk Web Portal &rarr;</a>
                        </div>
                    </div>

                    {{-- 2. Guru Pengajar --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between shadow-2xs">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-10 h-10 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm">
                                    02
                                </div>
                                <span class="px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-medium border border-blue-200">
                                    Aplikasi Mobile SCP
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-2">Guru Pengajar</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-4">
                                Mengakses jadwal mengajar harian otomatis, mengisi jurnal pembelajaran digital, mengambil foto dokumentasi ber-watermark resmi, melakukan presensi siswa per jam pelajaran, serta menyimpan draft secara offline saat koneksi terbatas.
                            </p>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Fitur: Jurnal &amp; Watermark</span>
                            <span class="text-emerald-600 font-medium">Live Sinkron</span>
                        </div>
                    </div>

                    {{-- 3. Wali Kelas & Guru BK --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between shadow-2xs">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-10 h-10 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-sm">
                                    03
                                </div>
                                <span class="px-2.5 py-1 rounded-md bg-purple-50 text-purple-700 text-xs font-medium border border-purple-200">
                                    Aplikasi Mobile SCP
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-2">Wali Kelas &amp; Guru BK</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-4">
                                Berperan sebagai verifikator perizinan: meninjau surat izin/sakit dari siswa atau orang tua, menyetujui/menolak izin dengan catatan resmi, memantau riwayat ketidakhadiran rombel binaan, serta melakukan bimbingan konseling.
                            </p>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Verifikasi Izin Daring</span>
                            <span class="text-purple-600 font-medium">Approval Cepat</span>
                        </div>
                    </div>

                    {{-- 4. Siswa / Peserta Didik --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between shadow-2xs">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">
                                    04
                                </div>
                                <span class="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 text-xs font-medium border border-emerald-200">
                                    Aplikasi Mobile SCP
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-2">Siswa / Peserta Didik</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-4">
                                Melihat jadwal pelajaran harian beserta nama guru &amp; lokasi ruang praktik/lab, memantau riwayat absensi pribadi, serta mengajukan permohonan izin atau sakit langsung dari ponsel lengkap dengan foto surat dokter/keterangan.
                            </p>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Jadwal Harian &amp; Izin Mandiri</span>
                            <span class="text-emerald-600 font-medium">Status Transparan</span>
                        </div>
                    </div>

                    {{-- 5. Orang Tua / Wali Murid --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between shadow-2xs md:col-span-2 lg:col-span-2">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-10 h-10 rounded-lg bg-amber-500 text-white flex items-center justify-center font-bold text-sm">
                                    05
                                </div>
                                <span class="px-2.5 py-1 rounded-md bg-amber-50 text-amber-800 text-xs font-medium border border-amber-200">
                                    Portal Orang Tua Terpadu
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-2">Orang Tua / Wali Murid (Dukungan Multi-Anak)</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-4">
                                Memantau kehadiran anak secara langsung di setiap jam pelajaran sekolah (Hadir, Sakit, Izin, Alpa), mengajukan surat izin resmi atas nama orang tua, serta memiliki kemampuan menautkan lebih dari satu anak (multi-anak) dalam satu akun aplikasi tanpa perlu berganti-ganti login.
                            </p>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                            <span>Transparansi Absensi Real-Time &bull; Multi-Child Linking</span>
                            <span class="text-amber-700 font-semibold">Terkoneksi Langsung dengan Sekolah</span>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        {{-- ==================== FITUR LENGKAP (BENTO GRID) ==================== --}}
        <section id="fitur" class="py-16 md:py-24 bg-white border-b border-slate-200 scroll-mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold mb-3">
                            Katalog Fitur Komprehensif
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                            Semua Fitur Folder SCP (Web) &amp; scp_mobile
                        </h2>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 max-w-md leading-relaxed">
                        Integrasi total modul kurikulum, jadwal blok, pelaporan KBM, perizinan, presensi, hingga kenaikan kelas cerdas yang siap pakai.
                    </p>
                </div>

                {{-- Grid Fitur --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    {{-- 1. Siklus Blok & Schedule Lock --}}
                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between hover:border-blue-300 transition">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-blue-600 text-white flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-blue-600">Web Portal Waka</span>
                            <h3 class="text-base font-bold text-slate-900 mt-1 mb-2">Siklus Blok &amp; Schedule Lock</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Pengaturan mandiri Putaran 1 &amp; Putaran 2 dengan satu kali klik. Dilengkapi fitur <em>Kunci Jadwal</em> agar jadwal tidak berubah selama KBM berjalan, serta arsip riwayat putaran otomatis.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 font-medium">
                            Putaran 1 &bull; Putaran 2 &bull; History Putaran
                        </div>
                    </div>

                    {{-- 2. Jurnal Mengajar & Alarm >30 Menit --}}
                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between hover:border-emerald-300 transition">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-600">Web &amp; Mobile</span>
                            <h3 class="text-base font-bold text-slate-900 mt-1 mb-2">Jurnal KBM &amp; Deteksi &gt;30 Mnt</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Guru mencatat materi pokok, capaian kompetensi, dan kendala kelas. Dashboard Waka secara otomatis mendeteksi sesi yang belum terisi lebih dari 30 menit untuk evaluasi kedisiplinan.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 font-medium">
                            Input Cepat &bull; Rekap Jurnal &bull; Filter Lengkap
                        </div>
                    </div>

                    {{-- 3. Kamera Dokumentasi Ber-Watermark Otomatis --}}
                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between hover:border-indigo-300 transition">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-indigo-600 text-white flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-indigo-600">Aplikasi Mobile Guru</span>
                            <h3 class="text-base font-bold text-slate-900 mt-1 mb-2">Kamera Watermark Otomatis</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Dokumentasi foto KBM otomatis distempel dengan watermark digital: nama guru, mata pelajaran, rombel kelas, tanggal, dan timestamp akurat sebagai bukti otentik pembelajaran.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 font-medium">
                            Anti-Manipulasi &bull; Watermark Stempel Resmi
                        </div>
                    </div>

                    {{-- 4. Presensi Per Jam Pelajaran --}}
                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between hover:border-purple-300 transition">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-purple-600 text-white flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-purple-600">Mobile &amp; Web</span>
                            <h3 class="text-base font-bold text-slate-900 mt-1 mb-2">Presensi Kelas Sesi KBM</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Form presensi langsung tersedia saat guru membuka sesi. Menandai Hadir, Sakit, Izin, atau Alpa hanya dengan beberapa sentuhan, lengkap dengan rekapitulasi persentase otomatis.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 font-medium">
                            Hadir &bull; Izin &bull; Sakit &bull; Alpa
                        </div>
                    </div>

                    {{-- 5. Perizinan Daring & Verifikasi BK/Wali Kelas --}}
                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between hover:border-amber-300 transition">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-amber-500 text-white flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-amber-600">Mobile App</span>
                            <h3 class="text-base font-bold text-slate-900 mt-1 mb-2">Izin Daring &amp; Verifikasi Digital</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Siswa/wali mengunggah bukti surat sakit/izin dari HP. Wali Kelas &amp; Guru BK menerima notifikasi daftar pending dan dapat menyetujui secara digital agar presensi otomatis tersinkron.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 font-medium">
                            Lampiran Foto Surat &bull; Status Tracking
                        </div>
                    </div>

                    {{-- 6. Kenaikan Kelas Cerdas & Batas Kuota 36 --}}
                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between hover:border-slate-400 transition">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-slate-900 text-white flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-800">Web Portal Waka</span>
                            <h3 class="text-base font-bold text-slate-900 mt-1 mb-2">Kenaikan Kelas &amp; Batas 36 Kuota</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Prosedur promosi berjenjang (XII alumni, XI ke XII, X ke XI). Dilengkapi penanganan siswa tinggal kelas (uncheck) dan validasi otomatis batas kuota 36 siswa per rombel kelas tujuan.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 font-medium">
                            Tinggal Kelas Aman &bull; Rombel Paralel Flex
                        </div>
                    </div>

                    {{-- 7. Impor Massal Excel Jadwal & User --}}
                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between hover:border-emerald-300 transition">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-emerald-700 text-white flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-700">Web Portal Waka</span>
                            <h3 class="text-base font-bold text-slate-900 mt-1 mb-2">Impor Massal Excel Template</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Waka Kurikulum dapat mengunggah ribuan jadwal KBM dan akun pengguna sekaligus menggunakan template spreadsheet standar (.xlsx), bebas repot input satu per satu.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 font-medium">
                            Download Template &bull; Validasi Kolom Otomatis
                        </div>
                    </div>

                    {{-- 8. Reset Kata Sandi OTP Email 6 Digit --}}
                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between hover:border-blue-300 transition">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-blue-700 text-white flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-blue-700">Web &amp; Mobile</span>
                            <h3 class="text-base font-bold text-slate-900 mt-1 mb-2">Pemulihan Akun via OTP Email</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Fitur lupa kata sandi yang aman menggunakan verifikasi OTP 6 digit langsung ke email terdaftar, dilengkapi proteksi rate limiting untuk mencegah serangan brute-force.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 font-medium">
                            3 Langkah Verifikasi &bull; Rate Limiting Aman
                        </div>
                    </div>

                    {{-- 9. Mode Offline & Dark/Light Theme --}}
                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between hover:border-slate-400 transition">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-slate-800 text-white flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                </svg>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-700">Aplikasi Mobile</span>
                            <h3 class="text-base font-bold text-slate-900 mt-1 mb-2">Draft Offline &amp; Dark Theme</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Guru tetap bisa menyusun catatan jurnal saat internet bengkel tidak stabil berkat *Journal Draft Store*. Pengguna juga dapat memilih tema Dark Mode atau Light Mode sesuai kenyamanan.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 font-medium">
                            Local Draft Cache &bull; Flutter Dark Mode
                        </div>
                    </div>

                </div>

            </div>
        </section>

        {{-- ==================== WEB VS MOBILE COMPARISON ==================== --}}
        <section id="platform" class="py-16 md:py-20 scroll-mt-16 bg-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="max-w-3xl mx-auto text-center mb-12">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 border border-blue-400/30 text-xs font-semibold mb-3">
                        Arsitektur Multi-Platform
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Portal Web Waka &amp; Aplikasi Mobile yang Saling Melengkapi
                    </h2>
                    <p class="text-slate-400 text-sm sm:text-base mt-2.5">
                        Didesain secara khusus agar setiap platform berfokus pada efisiensi maksimal penggunanya masing-masing.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    {{-- Kolom Web --}}
                    <div class="rounded-2xl bg-slate-800/80 border border-slate-700 p-6 sm:p-8 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-700">
                                <div>
                                    <span class="text-xs font-bold text-blue-400 uppercase tracking-wider">Platform Desktop</span>
                                    <h3 class="text-xl font-bold text-white mt-0.5">Portal Web Waka Kurikulum</h3>
                                </div>
                                <span class="px-2.5 py-1 rounded-md bg-blue-600/30 text-blue-300 text-xs font-medium border border-blue-500/40">
                                    Browser Web
                                </span>
                            </div>

                            <p class="text-slate-300 text-xs sm:text-sm leading-relaxed mb-6">
                                Pusat komando kurikulum sekolah untuk manajemen data skala besar dan evaluasi akademik berkala.
                            </p>

                            <ul class="space-y-3 text-xs sm:text-sm text-slate-300">
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Penyusunan jadwal blok massal &amp; impor template Excel</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Toggle instan Putaran 1 &amp; 2 serta fitur kunci jadwal (*Schedule Lock*)</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Monitoring KBM &amp; alarm keterlambatan jurnal &gt;30 menit</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Master data terpusat (Kelas, Ruang, Mapel, Guru, Siswa, Ortu)</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Kenaikan Kelas otomatis dengan aturan siswa tinggal kelas &amp; batas 36 kuota</span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-700/80">
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center justify-center w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2.5 rounded-lg transition shadow-xs">
                                Masuk ke Portal Waka Sekarang &rarr;
                            </a>
                        </div>
                    </div>

                    {{-- Kolom Mobile --}}
                    <div class="rounded-2xl bg-slate-800/80 border border-slate-700 p-6 sm:p-8 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-700">
                                <div>
                                    <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Platform Mobile</span>
                                    <h3 class="text-xl font-bold text-white mt-0.5">Aplikasi Mobile SCP School</h3>
                                </div>
                                <span class="px-2.5 py-1 rounded-md bg-emerald-600/30 text-emerald-300 text-xs font-medium border border-emerald-500/40">
                                    Android / iOS (Flutter)
                                </span>
                            </div>

                            <p class="text-slate-300 text-xs sm:text-sm leading-relaxed mb-6">
                                Operasional harian praktis di dalam kelas, laboratorium, bengkel kejuruan, dan genggaman tangan orang tua.
                            </p>

                            <ul class="space-y-3 text-xs sm:text-sm text-slate-300">
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Jadwal harian dinamis sesuai putaran aktif untuk Guru, Siswa &amp; Ortu</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Pengisian jurnal KBM kilat + Kamera bukti foto ber-watermark otomatis</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Presensi siswa langsung per sesi mengajar + Mode draft offline</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Pengajuan izin daring (surat dokter) &amp; verifikasi Wali Kelas / Guru BK</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Portal pemantauan orang tua dengan fitur Multi-Anak &amp; Dark Theme</span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-700/80">
                            <a href="{{ route('unduh.apk') }}"
                               class="inline-flex items-center justify-center gap-2 w-full bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold py-2.5 rounded-lg transition shadow-xs">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1.0002 0-.5517.4482-1.0003.9993-1.0003.5517 0 .9998.4486.9998 1.0003 0 .5516-.4481 1.0002-.9998 1.0002m-11.046 0c-.5516 0-.9997-.4486-.9997-1.0002 0-.5517.4481-1.0003.9997-1.0003.5512 0 .9993.4486.9993 1.0003 0 .5516-.4481 1.0002-.9993 1.0002m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5802 8.414 13.8427 8.125 12 8.125c-1.8428 0-3.5802.289-5.1368.8247L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/>
                                </svg>
                                <span>Unduh APK Mobile Langsung</span>
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        {{-- ==================== ALUR KERJA TERPADU ==================== --}}
        <section class="py-16 md:py-20 bg-slate-50 border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="max-w-3xl mx-auto text-center mb-12">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold mb-3">
                        Alur Operasional Harian
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Bagaimana SCP School Bekerja Setiap Hari?
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base mt-2.5">
                        Alur terstruktur dari pagi hingga evaluasi akhir putaran demi menjaga kelancaran belajar mengajar.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    
                    <div class="bg-white p-5 rounded-xl border border-slate-200 relative">
                        <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs mb-3">
                            1
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 mb-1.5">Aktivasi Siklus Blok</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Waka Kurikulum menetapkan siklus blok (Putaran 1 atau Putaran 2) dan mengunci jadwal (*Schedule Lock*).
                        </p>
                    </div>

                    <div class="bg-white p-5 rounded-xl border border-slate-200 relative">
                        <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-xs mb-3">
                            2
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 mb-1.5">KBM &amp; Jurnal Kelas</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Guru membuka sesi di kelas/lab melalui HP, mempresensi siswa, mengisi materi, dan memotret kegiatan ber-watermark.
                        </p>
                    </div>

                    <div class="bg-white p-5 rounded-xl border border-slate-200 relative">
                        <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center font-bold text-xs mb-3">
                            3
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 mb-1.5">Verifikasi Izin &amp; Ortu</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Siswa/wali yang mengajukan izin langsung diverifikasi Guru BK/Wali Kelas. Orang tua melihat status kehadiran anak seketika.
                        </p>
                    </div>

                    <div class="bg-white p-5 rounded-xl border border-slate-200 relative">
                        <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center font-bold text-xs mb-3">
                            4
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 mb-1.5">Rekapitulasi &amp; Promosi</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Data terangkum di dashboard Waka. Saat akhir tahun, proses kenaikan kelas dijalankan cerdas dengan kuota 36 siswa.
                        </p>
                    </div>

                </div>

            </div>
        </section>

        {{-- ==================== PANDUAN AKSES & INSTALASI ==================== --}}
        <section id="panduan" class="py-16 md:py-24 scroll-mt-16 bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="max-w-3xl mx-auto text-center mb-12">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold mb-3">
                        Petunjuk Penggunaan
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Panduan Akses Portal &amp; Aplikasi Mobile
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base mt-2.5">
                        Pilih panduan sesuai peran Anda di SMK Negeri 1 Bantul untuk memulai.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    {{-- Panduan 1: Waka Kurikulum (Portal Web) --}}
                    <div class="p-6 rounded-2xl border border-slate-200 bg-slate-50 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-slate-900 text-white flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Akses Waka Kurikulum (Web Desktop)</h3>
                                    <p class="text-xs text-slate-500">Menggunakan browser di PC atau Laptop</p>
                                </div>
                            </div>

                            <ol class="space-y-3 text-xs sm:text-sm text-slate-700 list-decimal list-inside pl-1">
                                <li>Buka peramban (Chrome, Edge, Firefox) dan akses alamat resmi <strong>escepegaul.web.id</strong>.</li>
                                <li>Klik tombol <strong>Masuk Portal Waka</strong> di bagian navigasi atas.</li>
                                <li>Masukkan <strong>Username</strong> dan <strong>Password</strong> akun Waka Kurikulum.</li>
                                <li>Anda langsung diarahkan ke Dashboard Manajemen Akademik untuk mengelola putaran, jadwal, jurnal, dan kenaikan kelas.</li>
                            </ol>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-200 flex items-center justify-between">
                            <span class="text-xs text-slate-500">Panel Web Waka</span>
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                                <span>Buka Halaman Login</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    {{-- Panduan 2: Guru, Siswa & Ortu (Aplikasi Mobile) --}}
                    <div class="p-6 rounded-2xl border border-slate-200 bg-slate-50 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Akses Guru, Siswa &amp; Orang Tua (Mobile App)</h3>
                                    <p class="text-xs text-slate-500">Menggunakan aplikasi Android / iOS (Flutter)</p>
                                </div>
                            </div>

                            <ol class="space-y-3 text-xs sm:text-sm text-slate-700 list-decimal list-inside pl-1">
                                <li>Unduh dan pasang aplikasi <strong>SCP School Mobile</strong> pada perangkat smartphone Anda.</li>
                                <li>Buka aplikasi, lalu pilih peran yang sesuai (Guru, Siswa, atau Orang Tua).</li>
                                <li>Login menggunakan kredensial resmi (NIP untuk Guru, NIS untuk Siswa, atau Username/Email untuk Orang Tua).</li>
                                <li>Bila lupa kata sandi, gunakan menu <strong>Lupa Kata Sandi</strong> untuk verifikasi instan via kode OTP 6 digit email.</li>
                            </ol>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs text-slate-500">Lupa password akun?</span>
                            <a href="{{ route('password.request') }}"
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                                <span>Reset Password via OTP</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        {{-- ==================== CTA BANNER PENUTUP ==================== --}}
        <section class="pb-16 md:pb-24 px-4 sm:px-6 lg:px-8 bg-white">
            <div class="max-w-7xl mx-auto">
                <div class="gradient-brand rounded-2xl border border-slate-800 px-6 py-10 sm:px-12 sm:py-14 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8 shadow-xl">
                    
                    <div class="max-w-2xl space-y-3">
                        <div class="inline-flex items-center gap-2 text-xs font-semibold text-blue-400">
                            <span class="w-2 h-2 rounded-full bg-blue-400 animate-ping"></span>
                            <span>Sistem Informasi Manajemen Terpadu &bull; SMK Negeri 1 Bantul</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight leading-tight">
                            Siap Mengoptimalkan Kegiatan Belajar Mengajar Hari Ini?
                        </h2>
                        <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
                            Masuk ke portal kurikulum sekarang untuk memantau siklus blok aktif, rekapitulasi jurnal, kehadiran siswa, serta pengelolaan master kelas dalam satu genggaman terpadu.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 shrink-0">
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-6 py-3.5 rounded-lg transition shadow-md hover:shadow-lg">
                            <span>Masuk ke Portal Waka</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                            </svg>
                        </a>
                        <a href="#panduan"
                           class="inline-flex items-center gap-2 px-5 py-3.5 rounded-lg border border-slate-700 bg-slate-800/80 text-slate-300 hover:bg-slate-700 hover:text-white text-sm font-medium transition">
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
    <footer class="bg-slate-900 text-slate-300 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-10 border-b border-slate-800">
                
                {{-- Identitas Sekolah --}}
                <div class="md:col-span-5 space-y-3.5">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/logo-smkn1.png') }}"
                             alt="SMK Negeri 1 Bantul"
                             class="h-10 w-10 object-contain rounded-full bg-white p-0.5 border border-slate-700">
                        <div>
                            <div class="text-white font-bold text-base tracking-tight">SCP School</div>
                            <div class="text-xs text-slate-400">SMK Negeri 1 Bantul</div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed max-w-sm">
                        Sistem Informasi Manajemen Jadwal Pelajaran Siklus Blok, Jurnal Mengajar Kelas, Presensi Real-Time, dan Perizinan Daring Terintegrasi SMK Negeri 1 Bantul.
                    </p>
                    <div class="text-[11px] text-slate-500 pt-1">
                        Jl. Parangtritis No.KM.11, Sabdodadi, Bantul, D.I. Yogyakarta 55715
                    </div>
                </div>

                {{-- Navigasi Halaman --}}
                <div class="md:col-span-3 space-y-3">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Navigasi Halaman</div>
                    <ul class="space-y-2 text-xs text-slate-300">
                        <li><a href="#tentang" class="hover:text-white transition">Tentang Sistem</a></li>
                        <li><a href="#peran" class="hover:text-white transition">Ekosistem 5 Pilar Peran</a></li>
                        <li><a href="#fitur" class="hover:text-white transition">Fitur Unggulan (Web &amp; App)</a></li>
                        <li><a href="#platform" class="hover:text-white transition">Perbandingan Web vs Mobile</a></li>
                        <li><a href="#panduan" class="hover:text-white transition">Panduan Akses &amp; Download</a></li>
                        <li><a href="{{ route('unduh.apk') }}" class="text-emerald-400 hover:text-emerald-300 font-semibold transition inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1.0002 0-.5517.4482-1.0003.9993-1.0003.5517 0 .9998.4486.9998 1.0003 0 .5516-.4481 1.0002-.9998 1.0002m-11.046 0c-.5516 0-.9997-.4486-.9997-1.0002 0-.5517.4481-1.0003.9997-1.0003.5512 0 .9993.4486.9993 1.0003 0 .5516-.4481 1.0002-.9993 1.0002m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5802 8.414 13.8427 8.125 12 8.125c-1.8428 0-3.5802.289-5.1368.8247L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/></svg>Unduh Aplikasi Android (APK)</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-blue-400 font-semibold transition">Portal Login Waka</a></li>
                    </ul>
                </div>

                {{-- Bantuan & Layanan Akun --}}
                <div class="md:col-span-4 space-y-3">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Informasi &amp; Bantuan Akun</div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Portal web ini dikhususkan bagi <strong class="text-slate-200">Waka Kurikulum</strong>. Bapak/Ibu Guru, Siswa, dan Orang Tua silakan menggunakan <strong class="text-slate-200">Aplikasi Mobile SCP School</strong>.
                    </p>
                    <div class="pt-2 flex flex-col gap-2">
                        <a href="{{ route('login') }}" class="text-xs text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1.5">
                            <span>&rarr; Masuk ke Portal Login Waka</span>
                        </a>
                        <a href="{{ route('password.request') }}" class="text-xs text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1.5">
                            <span>&rarr; Reset Kata Sandi Akun via OTP Email</span>
                        </a>
                    </div>
                </div>

            </div>

            <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                <p>&copy; {{ date('Y') }} SMK Negeri 1 Bantul &bull; SCP School. Hak cipta dilindungi.</p>
                <p>Teknologi Sistem Manajemen Akademik &amp; Presensi Terpadu</p>
            </div>

        </div>
    </footer>

    {{-- Script Navigasi Mobile --}}
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
