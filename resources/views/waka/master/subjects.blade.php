@extends('layouts.waka')

@section('title', 'Mata Pelajaran - SCP')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-semibold text-slate-900">Data Mata Pelajaran</h1>
        <p class="text-slate-500 text-sm mt-0.5">Daftar mata pelajaran beserta pengajar, kelas, dan ruangan yang digunakan.</p>
    </div>

    <form method="GET" class="bg-white rounded-lg border border-slate-200 p-4 mb-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Putaran</label>
                <select name="cycle" class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="1" {{ ($cycle ?? '1') == '1' ? 'selected' : '' }}>Putaran 1</option>
                    <option value="2" {{ ($cycle ?? '1') == '2' ? 'selected' : '' }}>Putaran 2</option>
                    <option value="all" {{ ($cycle ?? '') == 'all' ? 'selected' : '' }}>Semua Putaran (1 & 2)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Cari Mata Pelajaran</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Ketik nama mata pelajaran..."
                       class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm focus:border-slate-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Filter Pengajar</label>
                <select name="teacher_id" class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="">Semua Pengajar</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->fullname }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium transition">
                    Terapkan
                </button>
                @if(request()->hasAny(['search', 'teacher_id', 'cycle']))
                    <a href="{{ route('waka.master.subjects') }}" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 px-3.5 py-1.5 rounded-md text-sm font-medium transition">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-sm font-semibold text-slate-800">Daftar Mata Pelajaran & Pengampu</h2>
            <span class="text-xs text-slate-500">{{ $subjects->count() }} mata pelajaran</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Mata Pelajaran</th>
                        <th class="px-4 py-3">Guru Pengampu</th>
                        <th class="px-4 py-3">Kelas Diajar</th>
                        <th class="px-4 py-3">Ruangan</th>
                        <th class="px-4 py-3 text-center w-24">Total Sesi</th>
                        <th class="px-4 py-3 text-right w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($subjects as $index => $item)
                        @php
                            $teacherList = collect($item['teachers']);
                            $classList = collect($item['classes']);
                            $roomList = collect($item['rooms']);
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $item['subject'] }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 max-w-xs">
                                @if($teacherList->count() <= 2)
                                    {{ $teacherList->implode(', ') }}
                                @else
                                    <span>{{ $teacherList->take(2)->implode(', ') }}</span>
                                    <span class="ml-1 inline-flex rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600"
                                          title="{{ $teacherList->implode(', ') }}">
                                        +{{ $teacherList->count() - 2 }} guru
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @foreach($classList->take(3) as $className)
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $className }}</span>
                                    @endforeach
                                    @if($classList->count() > 3)
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-500"
                                              title="{{ $classList->implode(', ') }}">
                                            +{{ $classList->count() - 3 }} kelas
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @foreach($roomList->take(3) as $roomName)
                                        <span class="rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-600">{{ $roomName }}</span>
                                    @endforeach
                                    @if($roomList->count() > 3)
                                        <span class="rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-500"
                                              title="{{ $roomList->implode(', ') }}">
                                            +{{ $roomList->count() - 3 }} ruang
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-xs font-medium text-slate-700 whitespace-nowrap">
                                {{ $item['total_sessions'] }} sesi
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button" onclick="openDetail({{ $index }})"
                                        class="rounded border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                    Rincian
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Belum ada data mata pelajaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="detailModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg border border-slate-200 w-full max-w-3xl max-h-[82vh] flex flex-col shadow-lg">
            <div class="px-5 py-3.5 border-b border-slate-200 flex justify-between items-center">
                <h3 class="font-semibold text-sm text-slate-800" id="modalTitle">Rincian Jadwal</h3>
                <button type="button" onclick="closeDetail()" class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
            </div>
            <div class="overflow-y-auto flex-1" id="modalContent"></div>
            <div class="px-5 py-3 border-t border-slate-200 text-right">
                <button type="button" onclick="closeDetail()" class="px-3.5 py-1.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-md text-xs font-medium">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        const subjects = @json($subjects);

        function openDetail(index) {
            const item = subjects[index];
            document.getElementById('modalTitle').textContent = 'Rincian Jadwal — ' + item.subject;

            let html = '<table class="w-full text-sm text-left border-collapse">';
            html += '<thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500 sticky top-0"><tr>';
            html += '<th class="px-4 py-2.5 w-12 text-center">No</th>';
            html += '<th class="px-4 py-2.5">Putaran</th>';
            html += '<th class="px-4 py-2.5">Hari</th>';
            html += '<th class="px-4 py-2.5">Waktu</th>';
            html += '<th class="px-4 py-2.5">Kelas</th>';
            html += '<th class="px-4 py-2.5">Ruangan</th>';
            html += '<th class="px-4 py-2.5">Pengajar</th>';
            html += '</tr></thead><tbody class="divide-y divide-slate-200 text-slate-700">';

            (item.schedules || []).forEach((s, idx) => {
                html += `<tr class="hover:bg-slate-50/70">
                    <td class="px-4 py-2.5 text-center text-xs text-slate-400">${idx + 1}</td>
                    <td class="px-4 py-2.5 text-xs text-slate-600">Putaran ${s.cycle_type || '-'}</td>
                    <td class="px-4 py-2.5 font-medium text-slate-800">${s.day_of_week || '-'}</td>
                    <td class="px-4 py-2.5 text-xs text-slate-600 whitespace-nowrap">${(s.start_time || '').substring(0,5)} – ${(s.end_time || '').substring(0,5)}</td>
                    <td class="px-4 py-2.5"><span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-800">${s.class?.class_name || '-'}</span></td>
                    <td class="px-4 py-2.5 text-xs text-slate-600">${s.room?.room_name || '-'}</td>
                    <td class="px-4 py-2.5 text-xs text-slate-700">${s.teacher?.fullname || '-'}</td>
                </tr>`;
            });

            html += '</tbody></table>';
            document.getElementById('modalContent').innerHTML = html;

            const modal = document.getElementById('detailModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDetail() {
            const modal = document.getElementById('detailModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
@endsection
