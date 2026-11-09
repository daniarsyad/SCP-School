@extends('layouts.waka')

@section('title', 'History Putaran - SCP')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-semibold text-slate-900">History Putaran</h1>
        <p class="text-slate-500 text-sm mt-0.5">Rekapitulasi jurnal mengajar dan presensi siswa per putaran.</p>
    </div>

    @if(isset($selected) && $selected)
        <!-- Filter & Tab -->
        <form method="GET" class="bg-white rounded-lg border border-slate-200 p-4 mb-5">
            <input type="hidden" name="tab" value="{{ $tab ?? 'presensi' }}">

            <div class="flex flex-wrap items-center justify-between gap-3 pb-3.5 mb-3.5 border-b border-slate-200">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label for="historySelect" class="text-xs font-medium text-slate-500">Periode Putaran:</label>
                        <select id="historySelect" name="history_id" class="border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                            @foreach($histories as $h)
                                <option value="{{ $h->id }}" @selected(($selected?->id ?? null) == $h->id)>
                                    Putaran {{ $h->cycle_type }} · {{ $h->start_date->format('d M Y') }} – {{ $h->end_date->format('d M Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="inline-flex rounded-md border border-slate-200 bg-slate-50 p-0.5">
                        <a href="{{ request()->fullUrlWithQuery(['tab' => 'presensi']) }}"
                           class="px-3 py-1 text-xs font-medium rounded transition {{ ($tab ?? 'presensi') === 'presensi' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/80' : 'text-slate-600 hover:text-slate-900' }}">
                            Rekap Presensi
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['tab' => 'jurnal']) }}"
                           class="px-3 py-1 text-xs font-medium rounded transition {{ ($tab ?? '') === 'jurnal' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/80' : 'text-slate-600 hover:text-slate-900' }}">
                            Jurnal Mengajar
                        </a>
                    </div>
                </div>

                <div class="ml-auto">
                    @if(($tab ?? 'presensi') === 'presensi')
                        <a href="{{ route('waka.cycle.history.print-attendance', array_merge(['id' => $selected->id], request()->all(), ['print' => 1])) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white px-3.5 py-1.5 rounded-md text-xs font-semibold transition shadow-2xs"
                           title="Cetak atau unduh rekap presensi per kelas dalam format PDF">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Cetak / Export PDF Rekap Presensi</span>
                        </a>
                    @else
                        <a href="{{ route('waka.cycle.history.print-journals', array_merge(['id' => $selected->id], request()->all(), ['print' => 1])) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white px-3.5 py-1.5 rounded-md text-xs font-semibold transition shadow-2xs"
                           title="Cetak atau unduh jurnal mengajar dalam format PDF">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Cetak / Export PDF Jurnal Mengajar</span>
                        </a>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tanggal</label>
                    <select name="date" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                        <option value="">Semua Tanggal</option>
                        @foreach($dates as $date)
                            <option value="{{ $date }}" @selected(request('date') === $date)>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tingkat</label>
                    <select name="grade" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                        <option value="">Semua Tingkat</option>
                        @foreach(['X', 'XI', 'XII'] as $grade)
                            <option value="{{ $grade }}" @selected(request('grade') === $grade)>{{ $grade }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Jurusan</label>
                    <select name="major" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                        <option value="">Semua Jurusan</option>
                        @foreach($classes->pluck('major')->filter()->unique()->sort() as $major)
                            <option value="{{ $major }}" @selected(request('major') === $major)>{{ $major }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Rombel</label>
                    <select name="section" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                        <option value="">Semua Rombel</option>
                        @foreach($classes->pluck('section')->filter()->unique()->sort() as $section)
                            <option value="{{ $section }}" @selected(request('section') === $section)>{{ $section }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Kelas</label>
                    <select name="class_id" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                        <option value="">Semua Kelas</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Pengajar</label>
                    <select name="teacher_id" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                        <option value="">Semua Pengajar</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected((string) request('teacher_id') === (string) $teacher->id)>{{ $teacher->fullname }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium transition">Terapkan Filter</button>
                <a href="{{ route('waka.cycle.history', ['history_id' => $selected->id, 'tab' => $tab]) }}" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 px-3.5 py-1.5 rounded-md text-sm font-medium transition">Reset</a>
            </div>
        </form>

        @if(($tab ?? 'presensi') === 'presensi')
            <!-- Kartu Ringkasan -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                <div class="bg-white rounded-lg border border-slate-200 p-4">
                    <p class="text-xs text-slate-500">Hadir</p>
                    <p class="text-xl font-semibold text-slate-900 mt-1">{{ $classRecap->sum('hadir') }}</p>
                </div>
                <div class="bg-white rounded-lg border border-slate-200 p-4">
                    <p class="text-xs text-slate-500">Sakit</p>
                    <p class="text-xl font-semibold text-slate-900 mt-1">{{ $classRecap->sum('sakit') }}</p>
                </div>
                <div class="bg-white rounded-lg border border-slate-200 p-4">
                    <p class="text-xs text-slate-500">Izin</p>
                    <p class="text-xl font-semibold text-slate-900 mt-1">{{ $classRecap->sum('izin') }}</p>
                </div>
                <div class="bg-white rounded-lg border border-slate-200 p-4">
                    <p class="text-xs text-slate-500">Alpha</p>
                    <p class="text-xl font-semibold text-slate-900 mt-1">{{ $classRecap->sum('alpha') }}</p>
                </div>
            </div>

            <!-- Tabel per Kelas -->
            <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-800">Rekapitulasi Presensi per Kelas</h2>
                    <span class="text-xs text-slate-500">{{ $classRecap->count() }} kelas</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse min-w-[700px]">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                            <tr>
                                <th class="px-4 py-3 w-12 text-center">No</th>
                                <th class="px-4 py-3">Kelas</th>
                                <th class="px-4 py-3">Tanggal Jurnal</th>
                                <th class="px-4 py-3 text-right">Jurnal</th>
                                <th class="px-4 py-3 text-right">Hadir</th>
                                <th class="px-4 py-3 text-right">Izin</th>
                                <th class="px-4 py-3 text-right">Sakit</th>
                                <th class="px-4 py-3 text-right">Alpha</th>
                                <th class="px-4 py-3 text-right w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-700">
                            @forelse($classRecap as $index => $row)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $row['class_name'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @forelse($row['dates'] as $date)
                                                <span class="whitespace-nowrap rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $date }}</span>
                                            @empty
                                                <span class="text-xs text-slate-400">-</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs text-slate-600">{{ $row['journals'] }}</td>
                                    <td class="px-4 py-3 text-right text-xs font-medium text-emerald-700">{{ $row['hadir'] }}</td>
                                    <td class="px-4 py-3 text-right text-xs text-amber-700">{{ $row['izin'] }}</td>
                                    <td class="px-4 py-3 text-right text-xs text-slate-600">{{ $row['sakit'] }}</td>
                                    <td class="px-4 py-3 text-right text-xs font-medium text-red-600">{{ $row['alpha'] }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('waka.cycle.history.class', [$selected->id, $row['class_id']]) }}"
                                               class="inline-flex rounded border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                                                Rincian
                                            </a>
                                            <a href="{{ route('waka.cycle.history.class.print', [$selected->id, $row['class_id'], 'print' => 1]) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1 rounded border border-red-200 bg-red-50 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100 transition shadow-2xs"
                                               title="Cetak atau unduh rekap presensi kelas {{ $row['class_name'] }} (PDF)">
                                                <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-4 py-10 text-center text-slate-500">Tidak ada data presensi pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- Tab Jurnal -->
            <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-200 flex flex-wrap justify-between items-center gap-2">
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-sm font-semibold text-slate-800">Daftar Jurnal Mengajar</h2>
                        <span class="text-xs text-slate-500">{{ $journals->count() }} jurnal</span>
                    </div>
                    @if($journals->count() > 0)
                        <a href="{{ route('waka.cycle.history.print-journals', array_merge(['id' => $selected->id], request()->all(), ['print' => 1])) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition shadow-2xs"
                           title="Cetak jurnal mengajar dalam format PDF">
                            <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Cetak Jurnal (PDF)</span>
                        </a>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse min-w-[700px]">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                            <tr>
                                <th class="px-4 py-3 w-12 text-center">No</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Mata Pelajaran</th>
                                <th class="px-4 py-3">Pengajar</th>
                                <th class="px-4 py-3">Kelas</th>
                                <th class="px-4 py-3">Topik / Materi</th>
                                <th class="px-4 py-3">Lampiran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-700">
                            @forelse($journals as $index => $j)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">{{ \Carbon\Carbon::parse($j->date)->format('d M Y') }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $j->schedule->subject ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $j->schedule->teacher->fullname ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-800">
                                            {{ $j->schedule->class->class_name ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 max-w-xs text-xs text-slate-600">
                                        {{ $j->topic_description ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($j->photo_path)
                                            <a href="{{ asset('storage/' . $j->photo_path) }}" target="_blank" rel="noopener">
                                                <img src="{{ asset('storage/' . $j->photo_path) }}"
                                                     alt="Foto jurnal"
                                                     class="h-10 w-14 object-cover rounded border border-slate-200">
                                            </a>
                                        @else
                                            <span class="text-slate-400 text-xs">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Tidak ada data jurnal mengajar.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div class="bg-white rounded-lg border border-slate-200 p-10 text-center text-slate-500 text-sm">
            Belum ada data history putaran yang tersimpan.
        </div>
    @endif
@endsection
