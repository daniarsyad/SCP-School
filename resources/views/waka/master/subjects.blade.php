@extends('layouts.waka')

@section('title', 'Mata Pelajaran - SCP')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Data Master Mata Pelajaran</h1>
        <p class="text-gray-500 text-sm">Daftar mata pelajaran beserta guru pengampu dan rombel.</p>
    </div>

    <form method="GET" class="bg-white rounded-xl border p-4 mb-6 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama mata pelajaran..."
                   class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <select name="teacher_id" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Guru</option>
                @foreach($teachers as $t)
                    <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>
                        {{ $t->fullname }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
            Filter
        </button>
    </form>

    <div class="bg-white rounded-xl border overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="font-semibold">Daftar Mata Pelajaran & Guru Pengampu</h2>
            <span class="text-sm text-gray-500">Total: {{ $subjects->count() }} Mata Pelajaran</span>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left font-medium">No</th>
                    <th class="px-6 py-3 text-left font-medium">Mata Pelajaran</th>
                    <th class="px-6 py-3 text-left font-medium">Guru Pengampu</th>
                    <th class="px-6 py-3 text-left font-medium">Kelas Diajar</th>
                    <th class="px-6 py-3 text-left font-medium">Ruang</th>
                    <th class="px-6 py-3 text-left font-medium">Total Sesi</th>
                    <th class="px-6 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($subjects as $index => $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 font-medium">{{ $item['subject'] }}</td>
                        <td class="px-6 py-4">
                            @foreach($item['teachers'] as $teacher)
                                <div>{{ $teacher }}</div>
                            @endforeach
                        </td>
                        <td class="px-6 py-4">
                            @foreach($item['classes'] as $class)
                                <div>{{ $class }}</div>
                            @endforeach
                        </td>
                        <td class="px-6 py-4">
                            @foreach($item['rooms'] as $room)
                                <div>{{ $room }}</div>
                            @endforeach
                        </td>
                        <td class="px-6 py-4">{{ $item['total_sessions'] }} Sesi</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end">
                            <button type="button" onclick="openDetail({{ $index }})"
                                    title="Lihat detail mata pelajaran" aria-label="Lihat detail mata pelajaran"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 transition-colors hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 4.5-4.03 8-9 8s-9-3.5-9-8 4.03-8 9-8 9 3.5 9 8z"/>
                                </svg>
                            </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">Belum ada data mata pelajaran</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-3xl mx-4 max-h-[80vh] overflow-auto">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="font-semibold" id="modalTitle">Rincian Jadwal</h3>
                <button type="button" onclick="closeDetail()" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <div class="p-6" id="modalContent"></div>
            <div class="px-6 py-4 border-t text-right">
                <button type="button" onclick="closeDetail()" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        const subjects = @json($subjects);

        function openDetail(index) {
            const item = subjects[index];
            document.getElementById('modalTitle').textContent = 'Rincian Jadwal: ' + item.subject;

            let html = '<table class="w-full text-sm"><thead class="bg-gray-50"><tr>';
            html += '<th class="px-3 py-2 text-left">Hari</th><th class="px-3 py-2 text-left">Waktu</th>';
            html += '<th class="px-3 py-2 text-left">Kelas</th><th class="px-3 py-2 text-left">Ruangan</th>';
            html += '<th class="px-3 py-2 text-left">Guru</th></tr></thead><tbody class="divide-y">';

            (item.schedules || []).forEach(s => {
                html += `<tr>
                    <td class="px-3 py-2">${s.day_of_week || '-'}</td>
                    <td class="px-3 py-2">${(s.start_time || '').substring(0,5)} - ${(s.end_time || '').substring(0,5)}</td>
                    <td class="px-3 py-2">${s.class?.class_name || '-'}</td>
                    <td class="px-3 py-2">${s.room?.room_name || '-'}</td>
                    <td class="px-3 py-2">${s.teacher?.fullname || '-'}</td>
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