@extends('layouts.waka')

@section('title', 'Master Ruangan - SCP')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Master Data - Ruangan</h1>
    </div>

    <!-- Search -->
    <form method="GET" action="{{ route('waka.master.rooms') }}" class="mb-6">
        <div class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama ruangan..."
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                Cari
            </button>
            @if(request('search'))
                <a href="{{ route('waka.master.rooms') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- Form Tambah -->
    <div class="bg-white rounded-xl shadow p-6 mb-8">
        <h2 class="font-bold text-lg mb-4">Tambah Ruangan Baru</h2>
        <form action="{{ route('waka.master.rooms.store') }}" method="POST" class="flex gap-4">
            @csrf
            <input type="text" name="room_name" placeholder="Contoh: Lab RPL 3" required
                   class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                Tambah
            </button>
        </form>
    </div>

    <!-- Tabel -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">No</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Nama Ruangan</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($rooms as $index => $room)
                    <tr>
                        <td class="px-6 py-4">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 font-medium">{{ $room->room_name }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                            <button type="button" onclick="openEdit({{ $room->id }}, '{{ $room->room_name }}')"
                                    title="Edit ruangan" aria-label="Edit ruangan"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 transition-colors hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828z"/>
                                </svg>
                            </button>
                            <form action="{{ route('waka.master.rooms.delete', $room->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Yakin hapus ruangan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Hapus ruangan" aria-label="Hapus ruangan"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-red-50 text-red-700 transition-colors hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                            @if(request('search'))
                                Tidak ada ruangan yang cocok dengan pencarian
                            @else
                                Belum ada data ruangan
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md">
            <h3 class="font-bold text-lg mb-4">Edit Ruangan</h3>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <input type="text" name="room_name" id="editRoomName" required
                       class="w-full px-4 py-2 border rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeEdit()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
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