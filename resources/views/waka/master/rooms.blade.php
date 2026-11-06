@extends('layouts.waka')

@section('title', 'Master Ruangan - SCP')

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-5">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Data Ruangan</h1>
            <p class="text-sm text-slate-500 mt-0.5">Daftar ruang teori, laboratorium, dan area praktik yang digunakan dalam jadwal pelajaran.</p>
        </div>
    </div>

    <!-- Search & Tambah -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-5">
        <form method="GET" action="{{ route('waka.master.rooms') }}" class="bg-white rounded-lg border border-slate-200 p-3.5 flex items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama ruangan (misal: Ruang 12, Lab 16, OR 1)..."
                   class="flex-1 px-3 py-1.5 border border-slate-300 rounded-md text-sm focus:outline-none focus:border-slate-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium transition">
                Cari
            </button>
            @if(request('search'))
                <a href="{{ route('waka.master.rooms') }}" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 px-3 py-1.5 rounded-md text-sm font-medium transition">
                    Reset
                </a>
            @endif
        </form>

        <form action="{{ route('waka.master.rooms.store') }}" method="POST" class="bg-white rounded-lg border border-slate-200 p-3.5 flex items-center gap-2">
            @csrf
            <input type="text" name="room_name" placeholder="Nama ruangan baru (contoh: Lab 23)..." required
                   class="flex-1 px-3 py-1.5 border border-slate-300 rounded-md text-sm focus:outline-none focus:border-slate-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium whitespace-nowrap transition">
                + Tambah
            </button>
        </form>
    </div>

    <!-- Tabel Ruangan -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-800">Daftar Ruangan</h2>
            <span class="text-xs text-slate-500">{{ $rooms->count() }} ruangan</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Nama Ruangan</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3 text-center">Putaran 1</th>
                        <th class="px-4 py-3 text-center">Putaran 2</th>
                        <th class="px-4 py-3 text-center">Total Pemakaian</th>
                        <th class="px-4 py-3 text-right w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($rooms as $index => $room)
                        @php
                            $rName = $room->room_name;
                            if (stripos($rName, 'Lab') === 0) {
                                $catLabel = 'Laboratorium';
                            } elseif (preg_match('/^Ruang\s+\d+$/i', $rName)) {
                                $catLabel = 'Ruang Teori';
                            } else {
                                $catLabel = 'Area Praktik / Umum';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">
                                <a href="{{ route('waka.schedules.index', ['room_id' => $room->id]) }}"
                                   class="hover:text-blue-600 hover:underline"
                                   title="Lihat jadwal pemakaian {{ $room->room_name }}">
                                    {{ $room->room_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border border-slate-200 bg-slate-50 text-slate-600">
                                    {{ $catLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-slate-600">
                                {{ $room->schedules_p1_count ?? 0 }} sesi
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-slate-600">
                                {{ $room->schedules_p2_count ?? 0 }} sesi
                            </td>
                            <td class="px-4 py-3 text-center text-xs font-medium text-slate-800">
                                {{ $room->schedules_count ?? 0 }} sesi
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" onclick="openEdit({{ $room->id }}, '{{ addslashes($room->room_name) }}')"
                                            class="rounded border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        Edit
                                    </button>
                                    <form action="{{ route('waka.master.rooms.delete', $room->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Yakin hapus ruangan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded border border-red-200 bg-white px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                @if(request('search'))
                                    Tidak ada ruangan yang cocok dengan pencarian.
                                @else
                                    Belum ada data ruangan.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg border border-slate-200 p-6 w-full max-w-md shadow-lg">
            <h3 class="font-semibold text-base text-slate-800 mb-4">Edit Ruangan</h3>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <label class="block text-xs font-medium text-slate-600 mb-1">Nama Ruangan</label>
                <input type="text" name="room_name" id="editRoomName" required
                       class="w-full px-3 py-1.5 border border-slate-300 rounded-md mb-4 text-sm focus:outline-none focus:border-slate-500">
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeEdit()" class="px-3.5 py-1.5 text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-md text-sm">Batal</button>
                    <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEdit(id, name) {
            document.getElementById('editRoomName').value = name;
            document.getElementById('editForm').action = `/waka/master/rooms/${id}`;
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }
        function closeEdit() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }
    </script>
@endsection
