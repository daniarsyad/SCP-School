@extends('layouts.waka')

@section('title', 'Rekap Jurnal - SCP')

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-5">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Jurnal Mengajar</h1>
            <p class="text-slate-500 text-sm mt-0.5">{{ $today }}, {{ $todayDate }} · Putaran {{ $config->active_cycle }}</p>
        </div>
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
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Pengajar</th>
                        <th class="px-4 py-3">Mata Pelajaran</th>
                        <th class="px-4 py-3 w-28">Kelas</th>
                        <th class="px-4 py-3 w-28">Ruangan</th>
                        <th class="px-4 py-3 w-32">Waktu</th>
                        <th class="px-4 py-3 w-36">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($belumIsi as $index => $item)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $item->teacher->fullname }}</td>
                            <td class="px-4 py-3">{{ $item->subject }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-800 text-xs font-medium">
                                    {{ $item->class->class_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $item->room->room_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">{{ substr($item->start_time, 0, 5) }} – {{ substr($item->end_time, 0, 5) }}</td>
                            <td class="px-4 py-3">
                                @if($terlambat->contains($item))
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium bg-red-50 text-red-700 border border-red-200 rounded">Terlambat &gt;30 mnt</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 rounded">Belum Isi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">Semua pengajar sudah mengisi jurnal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sudah Isi -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-800">Sudah Mengisi Jurnal</h2>
            <span class="text-xs text-slate-500">{{ $sudahIsi->count() }} sesi</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Pengajar</th>
                        <th class="px-4 py-3">Mata Pelajaran</th>
                        <th class="px-4 py-3 w-28">Kelas</th>
                        <th class="px-4 py-3 w-28">Ruangan</th>
                        <th class="px-4 py-3 w-36">Waktu</th>
                        <th class="px-4 py-3 w-28">Lampiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($sudahIsi as $index => $item)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $item->teacher->fullname }}</td>
                            <td class="px-4 py-3">{{ $item->subject }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-800 text-xs font-medium">
                                    {{ $item->class->class_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $item->room->room_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">{{ substr($item->start_time, 0, 5) }} – {{ substr($item->end_time, 0, 5) }}</td>
                            <td class="px-4 py-3">
                                @if($item->journals->first()?->photo_path)
                                    <a href="{{ asset('storage/' . $item->journals->first()->photo_path) }}"
                                       target="_blank"
                                       rel="noopener"
                                       class="inline-block">
                                        <img src="{{ asset('storage/' . $item->journals->first()->photo_path) }}"
                                             alt="Foto jurnal"
                                             class="h-10 w-14 object-cover rounded border border-slate-200">
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada yang mengisi jurnal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
