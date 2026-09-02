@extends('layouts.waka')

@section('title', 'Master Kelas - SCP')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Master Data - Kelas</h1>
    </div>

    <!-- Search -->
    <form method="GET" action="{{ route('waka.master.classes') }}" class="mb-6">
        <div class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama kelas..."
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                Cari
            </button>
            @if(request('search'))
                <a href="{{ route('waka.master.classes') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- Form Tambah -->
    <div class="bg-white rounded-xl shadow p-6 mb-8">
        <h2 class="font-bold text-lg mb-4">Tambah Kelas Baru</h2>
        <form action="{{ route('waka.master.classes.store') }}" method="POST" class="flex gap-4">
            @csrf
            <input type="text" name="class_name" placeholder="Contoh: X RPL 3" required
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
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Nama Kelas</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($classes as $index => $class)
                    <tr>
                        <td class="px-6 py-4">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 font-medium">{{ $class->class_name }}</td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button onclick="openEdit({{ $class->id }}, '{{ $class->class_name }}')"
                                    class="text-blue-600 hover:underline text-sm">Edit</button>
                            <form action="{{ route('waka.master.classes.delete', $class->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Yakin hapus kelas ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                            @if(request('search'))
                                Tidak ada kelas yang cocok dengan pencarian
                            @else
                                Belum ada data kelas
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
            <h3 class="font-bold text-lg mb-4">Edit Kelas</h3>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <input type="text" name="class_name" id="editClassName" required
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
            document.getElementById('editClassName').value = name;
            document.getElementById('editForm').action = `/waka/master/classes/${id}`;
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }
        function closeEdit() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }
    </script>
@endsection