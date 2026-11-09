@extends('layouts.waka')

@section('title', 'Rekap Jurnal - SCP')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-semibold text-slate-900">Jurnal Mengajar</h1>
        <p class="text-slate-500 text-sm mt-0.5">{{ $today }}, {{ $todayDate }} · Putaran {{ $config->active_cycle }}</p>
    </div>

    <form method="GET" class="bg-white rounded-lg border border-slate-200 p-4 mb-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Tanggal</label>
                <input type="date" name="date" value="{{ $todayDate }}" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
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
            <a href="{{ route('waka.journal.recap') }}" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 px-3.5 py-1.5 rounded-md text-sm font-medium transition">Reset</a>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-slate-500 text-xs">Total Jadwal pada Tanggal Dipilih</div>
            <div class="text-xl font-semibold text-slate-900 mt-1">{{ $todaySchedules->count() }} <span class="text-xs font-normal text-slate-400">sesi</span></div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-slate-500 text-xs">Sudah Mengisi Jurnal</div>
            <div class="text-xl font-semibold text-emerald-600 mt-1">{{ $sudahIsi->count() }} <span class="text-xs font-normal text-slate-400">sesi</span></div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-slate-500 text-xs">Belum Mengisi / Terlambat</div>
            <div class="text-xl font-semibold {{ $belumIsi->count() > 0 ? 'text-red-600' : 'text-slate-900' }} mt-1">{{ $belumIsi->count() }} <span class="text-xs font-normal text-slate-400">sesi</span></div>
        </div>
    </div>

    <!-- Belum Isi -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-800">Belum Mengisi Jurnal</h2>
            <span class="text-xs text-slate-500">{{ $belumIsi->count() }} sesi</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse min-w-[700px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Pengajar</th>
                        <th class="px-4 py-3">Mata Pelajaran</th>
                        <th class="px-4 py-3 w-32">Kelas</th>
                        <th class="px-4 py-3 w-28">Ruangan</th>
                        <th class="px-4 py-3 w-32">Waktu</th>
                        <th class="px-4 py-3 w-36">Status</th>
                        <th class="px-4 py-3 w-28 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($belumIsi as $index => $item)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $item->teacher->fullname }}</td>
                            <td class="px-4 py-3">{{ $item->subject }}</td>
                            <td class="px-4 py-3 text-xs font-medium text-slate-800">{{ $item->class->class_name }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $item->room->room_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">{{ substr($item->start_time, 0, 5) }} – {{ substr($item->end_time, 0, 5) }}</td>
                            <td class="px-4 py-3">
                                @if($terlambat->contains($item))
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium bg-red-50 text-red-700 border border-red-200 rounded">Terlambat &gt;30 mnt</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 rounded">Belum Isi</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('waka.master.classes.students', $item->class_id) }}"
                                   class="inline-flex items-center rounded border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 transition"
                                   title="Lihat profil kelas & presensi">
                                    Detail Kelas
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Semua pengajar sudah mengisi jurnal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sudah Isi -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-800">Sudah Mengisi Jurnal</h2>
            <span class="text-xs text-slate-500">{{ $sudahIsi->count() }} sesi</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse min-w-[750px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Pengajar</th>
                        <th class="px-4 py-3">Mata Pelajaran</th>
                        <th class="px-4 py-3 w-32">Kelas</th>
                        <th class="px-4 py-3 w-28">Ruangan</th>
                        <th class="px-4 py-3 w-36">Waktu</th>
                        <th class="px-4 py-3 w-24 text-center">Lampiran</th>
                        <th class="px-4 py-3 w-40">Presensi</th>
                        <th class="px-4 py-3 w-36 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($sudahIsi as $index => $item)
                        @php
                            $journal = $item->journals->first();
                            $attendances = $journal?->attendances ?? collect();
                            $h = $attendances->where('status', 'Hadir')->count();
                            $i = $attendances->where('status', 'Izin')->count();
                            $s = $attendances->where('status', 'Sakit')->count();
                            $a = $attendances->where('status', 'Alpha')->count();
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $item->teacher->fullname }}</td>
                            <td class="px-4 py-3">{{ $item->subject }}</td>
                            <td class="px-4 py-3 text-xs font-medium text-slate-800">{{ $item->class->class_name }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $item->room->room_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">{{ substr($item->start_time, 0, 5) }} – {{ substr($item->end_time, 0, 5) }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($journal?->photo_path)
                                    <a href="{{ asset('storage/' . $journal->photo_path) }}"
                                       target="_blank"
                                       rel="noopener"
                                       class="inline-block"
                                       title="Lihat foto kegiatan">
                                        <img src="{{ asset('storage/' . $journal->photo_path) }}"
                                             alt="Foto jurnal"
                                             class="h-10 w-14 object-cover rounded border border-slate-200 hover:opacity-80 transition">
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1 text-xs flex-wrap">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-medium" title="Hadir: {{ $h }}">H: {{ $h }}</span>
                                    @if($i > 0)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 font-medium" title="Izin: {{ $i }}">I: {{ $i }}</span>
                                    @endif
                                    @if($s > 0)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-medium" title="Sakit: {{ $s }}">S: {{ $s }}</span>
                                    @endif
                                    @if($a > 0)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-red-50 text-red-700 font-medium" title="Alpha: {{ $a }}">A: {{ $a }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button"
                                        onclick="openJournalModal({{ $item->id }})"
                                        class="inline-flex items-center gap-1.5 rounded border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 hover:border-blue-300 transition shadow-2xs">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>Detail & Presensi</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500">Belum ada yang mengisi jurnal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Detail Jurnal & Presensi Kelas -->
    <div id="journalDetailModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4 backdrop-blur-xs">
        <div class="bg-white rounded-xl border border-slate-200 w-full max-w-3xl shadow-xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/70">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800" id="modalBadgeClass">-</span>
                        <h3 class="text-base font-semibold text-slate-900" id="modalSubjectTitle">Detail Jurnal Mengajar</h3>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5" id="modalSubtitle">-</p>
                </div>
                <button type="button" onclick="closeJournalModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto space-y-4">
                <!-- Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-50 rounded-lg p-3.5 border border-slate-200 text-xs">
                    <div>
                        <span class="text-slate-500 block">Guru Pengajar:</span>
                        <span class="font-medium text-slate-800" id="modalTeacherName">-</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Ruangan & Waktu:</span>
                        <span class="font-medium text-slate-800" id="modalRoomTime">-</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Waktu Submit Jurnal:</span>
                        <span class="font-medium text-slate-800" id="modalSubmitStatus">-</span>
                    </div>
                </div>

                <!-- Materi & Catatan -->
                <div class="space-y-3">
                    <div class="bg-white border border-slate-200 rounded-lg p-3.5">
                        <h4 class="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Materi / Topik Pembelajaran</h4>
                        <p class="text-sm text-slate-800 whitespace-pre-line" id="modalTopicDescription">-</p>
                    </div>

                    <div id="modalActivityNotesContainer" class="bg-white border border-slate-200 rounded-lg p-3.5 hidden">
                        <h4 class="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Catatan Kegiatan / Pembelajaran</h4>
                        <p class="text-sm text-slate-800 whitespace-pre-line" id="modalActivityNotes">-</p>
                    </div>
                </div>

                <!-- Foto Dokumentasi -->
                <div id="modalPhotoContainer" class="bg-white border border-slate-200 rounded-lg p-3.5 hidden">
                    <h4 class="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Foto Dokumentasi Kegiatan</h4>
                    <div class="flex items-center gap-3">
                        <img id="modalPhotoImage" src="" alt="Foto Kegiatan" class="h-28 w-44 object-cover rounded border border-slate-200">
                        <div class="text-xs text-slate-500">
                            <p class="font-medium text-slate-700">Lampiran bukti mengajar di kelas</p>
                            <a id="modalPhotoLink" href="" target="_blank" rel="noopener" class="text-blue-600 hover:underline mt-1 inline-block font-medium">
                                Buka Foto Ukuran Penuh →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Kehadiran Siswa -->
                <div class="pt-2">
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2.5">Rekapitulasi Presensi Sesi Ini</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <div class="p-2.5 rounded-lg border border-emerald-200 bg-emerald-50/60 text-center">
                            <span class="text-[11px] font-medium text-emerald-700">Hadir</span>
                            <div class="text-lg font-bold text-emerald-800" id="modalStatHadir">0</div>
                        </div>
                        <div class="p-2.5 rounded-lg border border-amber-200 bg-amber-50/60 text-center">
                            <span class="text-[11px] font-medium text-amber-700">Izin</span>
                            <div class="text-lg font-bold text-amber-800" id="modalStatIzin">0</div>
                        </div>
                        <div class="p-2.5 rounded-lg border border-blue-200 bg-blue-50/60 text-center">
                            <span class="text-[11px] font-medium text-blue-700">Sakit</span>
                            <div class="text-lg font-bold text-blue-800" id="modalStatSakit">0</div>
                        </div>
                        <div class="p-2.5 rounded-lg border border-red-200 bg-red-50/60 text-center">
                            <span class="text-[11px] font-medium text-red-700">Alpha</span>
                            <div class="text-lg font-bold text-red-800" id="modalStatAlpha">0</div>
                        </div>
                    </div>
                </div>

                <!-- Input Pencarian Siswa di Modal -->
                <div class="flex items-center justify-between gap-3 pt-2">
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Daftar Kehadiran Siswa</h4>
                    <input type="text"
                           id="modalSearchInput"
                           onkeyup="filterModalJournalStudents()"
                           placeholder="Filter siswa di kelas ini..."
                           class="border border-slate-300 rounded-md px-2.5 py-1 text-xs bg-white w-56 focus:border-blue-500 focus:outline-none">
                </div>

                <!-- Tabel Siswa Modal -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-xs text-left border-collapse" id="modalJournalStudentsTable">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                            <tr>
                                <th class="px-3 py-2 w-10 text-center">No</th>
                                <th class="px-3 py-2 w-28">NIS</th>
                                <th class="px-3 py-2">Nama Siswa</th>
                                <th class="px-3 py-2 w-24 text-center">Status</th>
                                <th class="px-3 py-2">Catatan / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700" id="modalJournalStudentsBody">
                            <!-- Diisi via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 border-t border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <a id="modalClassLink"
                   href="#"
                   class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                    <span>Lihat Halaman Detail & Rekap Lengkap Kelas Ini</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <button type="button"
                        onclick="closeJournalModal()"
                        class="px-4 py-1.5 rounded-md border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    @php
        $journalModalPayload = $sudahIsi->mapWithKeys(function ($item) {
            $j = $item->journals->first();
            $atts = $j?->attendances ?? collect();
            return [$item->id => [
                'id' => $item->id,
                'class_name' => $item->class->class_name,
                'class_url' => route('waka.master.classes.students', $item->class_id),
                'teacher_name' => $item->teacher->fullname,
                'subject' => $item->subject,
                'room' => $item->room->room_name ?? '-',
                'time' => substr($item->start_time, 0, 5) . ' – ' . substr($item->end_time, 0, 5),
                'date' => \Carbon\Carbon::parse($j?->date)->translatedFormat('l, d F Y'),
                'created_at' => $j?->created_at ? \Carbon\Carbon::parse($j->created_at)->format('H:i') . ' WIB' : '-',
                'late_minutes' => (int) ($j?->late_minutes ?? 0),
                'topic_description' => $j?->topic_description ?? 'Tidak ada catatan materi.',
                'activity_notes' => $j?->activity_notes ?? null,
                'photo_url' => $j?->photo_path ? asset('storage/' . $j->photo_path) : null,
                'stats' => [
                    'hadir' => $atts->where('status', 'Hadir')->count(),
                    'izin' => $atts->where('status', 'Izin')->count(),
                    'sakit' => $atts->where('status', 'Sakit')->count(),
                    'alpha' => $atts->where('status', 'Alpha')->count(),
                ],
                'students' => $atts->map(function ($att) {
                    return [
                        'nis' => $att->student->nis ?? '-',
                        'name' => $att->student->fullname ?? 'Siswa Tidak Diketahui',
                        'status' => $att->status,
                        'notes' => $att->notes ?? '-',
                    ];
                })->values(),
            ]];
        });
    @endphp

    <script>
        const journalModalData = @json($journalModalPayload);

        function openJournalModal(scheduleId) {
            const data = journalModalData[scheduleId];
            if (!data) return;

            document.getElementById('modalBadgeClass').innerText = data.class_name;
            document.getElementById('modalSubjectTitle').innerText = data.subject;
            document.getElementById('modalSubtitle').innerText = `${data.date} · ${data.time}`;
            document.getElementById('modalTeacherName').innerText = data.teacher_name;
            document.getElementById('modalRoomTime').innerText = `${data.room} (${data.time})`;

            let submitText = data.created_at;
            if (data.late_minutes > 0) {
                submitText += ` (Terlambat ${data.late_minutes} menit)`;
            } else {
                submitText += ` (Tepat waktu)`;
            }
            document.getElementById('modalSubmitStatus').innerText = submitText;

            document.getElementById('modalTopicDescription').innerText = data.topic_description;

            const notesEl = document.getElementById('modalActivityNotesContainer');
            if (data.activity_notes) {
                document.getElementById('modalActivityNotes').innerText = data.activity_notes;
                notesEl.classList.remove('hidden');
            } else {
                notesEl.classList.add('hidden');
            }

            const photoEl = document.getElementById('modalPhotoContainer');
            if (data.photo_url) {
                document.getElementById('modalPhotoImage').src = data.photo_url;
                document.getElementById('modalPhotoLink').href = data.photo_url;
                photoEl.classList.remove('hidden');
            } else {
                photoEl.classList.add('hidden');
            }

            document.getElementById('modalStatHadir').innerText = data.stats.hadir;
            document.getElementById('modalStatIzin').innerText = data.stats.izin;
            document.getElementById('modalStatSakit').innerText = data.stats.sakit;
            document.getElementById('modalStatAlpha').innerText = data.stats.alpha;

            document.getElementById('modalClassLink').href = data.class_url;

            const tbody = document.getElementById('modalJournalStudentsBody');
            tbody.innerHTML = '';

            if (data.students && data.students.length > 0) {
                data.students.forEach((st, idx) => {
                    let badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
                    if (st.status === 'Hadir') badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    else if (st.status === 'Izin') badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                    else if (st.status === 'Sakit') badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                    else if (st.status === 'Alpha') badgeClass = 'bg-red-50 text-red-700 border-red-200';

                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50/70 transition modal-st-row';
                    tr.innerHTML = `
                        <td class="px-3 py-2 text-center text-slate-400">${idx + 1}</td>
                        <td class="px-3 py-2 font-mono text-slate-600 modal-nis">${st.nis}</td>
                        <td class="px-3 py-2 font-medium text-slate-800 modal-name">${st.name}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded border text-[11px] font-medium ${badgeClass}">
                                ${st.status}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-slate-500">${st.notes && st.notes !== '-' ? st.notes : '<span class="text-slate-300">-</span>'}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-400">
                            Tidak ada data presensi siswa yang tersimpan untuk sesi ini.
                        </td>
                    </tr>
                `;
            }

            document.getElementById('modalSearchInput').value = '';
            const modal = document.getElementById('journalDetailModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeJournalModal() {
            const modal = document.getElementById('journalDetailModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function filterModalJournalStudents() {
            const query = document.getElementById('modalSearchInput').value.toLowerCase().trim();
            const rows = document.querySelectorAll('#modalJournalStudentsBody .modal-st-row');

            rows.forEach(row => {
                const name = row.querySelector('.modal-name')?.innerText.toLowerCase() || '';
                const nis = row.querySelector('.modal-nis')?.innerText.toLowerCase() || '';

                if (name.includes(query) || nis.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Tutup modal jika klik di latar belakang
        document.getElementById('journalDetailModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeJournalModal();
            }
        });

        // Tutup modal dengan tombol Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeJournalModal();
            }
        });
    </script>
@endsection
