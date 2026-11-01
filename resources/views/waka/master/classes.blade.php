@extends('layouts.waka')

@section('title', 'Master Kelas - SCP')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Data Master Kelas</h1>
            <p class="text-gray-500 text-sm">Kelola daftar rombongan belajar / kelas di sekolah.</p>
        </div>
        <button onclick="document.getElementById('addForm').classList.toggle('hidden')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
            + Tambah Kelas
        </button>
    </div>

    <!-- Filter -->
    <form method="GET" class="bg-white rounded-xl border p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Kelas</label>
                <select name="grade" class="border rounded-lg px-3 py-1.5 text-sm" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    <option value="X" {{ request('grade') == 'X' ? 'selected' : '' }}>X</option>
                    <option value="XI" {{ request('grade') == 'XI' ? 'selected' : '' }}>XI</option>
                    <option value="XII" {{ request('grade') == 'XII' ? 'selected' : '' }}>XII</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Jurusan</label>
                <select name="major" class="border rounded-lg px-3 py-1.5 text-sm" onchange="this.form.submit()">
                    <option value="">Semua Jurusan</option>
                    @foreach(['RPL','TKJ','DKV','AKL','MP','BR','BD','LPS'] as $m)
                        <option value="{{ $m }}" {{ request('major') == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Rombel</label>
                <select name="section" class="border rounded-lg px-3 py-1.5 text-sm" onchange="this.form.submit()">
                    <option value="">Semua Rombel</option>
                    <option value="1" {{ request('section') == '1' ? 'selected' : '' }}>1</option>
                    <option value="2" {{ request('section') == '2' ? 'selected' : '' }}>2</option>
                    <option value="3" {{ request('section') == '3' ? 'selected' : '' }}>3</option>
                    <option value="4" {{ request('section') == '4' ? 'selected' : '' }}>4</option>
                </select>
            </div>
        </div>
    </form>

    <!-- Form Tambah -->
    <div id="addForm" class="hidden bg-white rounded-xl border p-6 mb-6">
        <h2 class="font-semibold mb-4">Tambah Kelas Baru</h2>
        <form action="{{ route('waka.master.classes.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-sm mb-1">Kelas</label>
                <select name="grade" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="X">X</option>
                    <option value="XI">XI</option>
                    <option value="XII">XII</option>
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">Jurusan</label>
                <select name="major" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="RPL">RPL</option>
                    <option value="TKJ">TKJ</option>
                    <option value="DKV">DKV</option>
                    <option value="AKL">AKL</option>
                    <option value="MP">MP</option>
                    <option value="BR">BR</option>
                    <option value="BD">BD</option>
                    <option value="LPS">LPS</option>
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">Rombel</label>
                <select name="section" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm">Simpan</button>
            </div>
        </form>
    </div>

    <!-- Tabel -->
    <div class="bg-white rounded-xl border overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="font-semibold">Daftar Kelas</h2>
            <span class="text-sm text-gray-500">Total: {{ $classes->count() }} Kelas</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left font-medium">No</th>
                    <th class="px-6 py-3 text-left font-medium">Kelas</th>
                    <th class="px-6 py-3 text-left font-medium">Jurusan</th>
                    <th class="px-6 py-3 text-left font-medium">Rombel</th>
                    <th class="px-6 py-3 text-left font-medium">Jumlah Siswa</th>
                    <th class="px-6 py-3 text-left font-medium">Total Jadwal</th>
                    <th class="px-6 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($classes as $index => $class)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3">{{ $index + 1 }}</td>
                        <td class="px-6 py-3 font-medium">{{ $class->grade ?? '-' }}</td>
                        <td class="px-6 py-3">{{ $class->major ?? '-' }}</td>
                        <td class="px-6 py-3">{{ $class->section ?? '-' }}</td>
                        <td class="px-6 py-3">{{ $class->users_count ?? 0 }} Siswa</td>
                        <td class="px-6 py-3">{{ $class->schedules_count ?? 0 }} Sesi Jadwal</td>
                        <td class="px-6 py-3 text-right">
                            <div class="flex justify-end gap-2">
                            <a href="{{ route('waka.master.classes.students', $class->id) }}"
                               title="Lihat siswa" aria-label="Lihat siswa"
                               class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 transition-colors hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </a>
                            <button type="button" onclick="openEdit({{ $class->id }}, '{{ $class->grade }}', '{{ $class->major }}', '{{ $class->section }}')"
                                    title="Edit kelas" aria-label="Edit kelas"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 transition-colors hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <form action="{{ route('waka.master.classes.delete', $class->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Yakin hapus kelas ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Hapus kelas" aria-label="Hapus kelas"
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
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">Belum ada data kelas</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md">
            <h3 class="font-bold text-lg mb-4">Edit Kelas</h3>
            <form id="editForm" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm mb-1">Kelas</label>
                    <select name="grade" id="editGrade" required class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="X">X</option>
                        <option value="XI">XI</option>
                        <option value="XII">XII</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Jurusan</label>
                    <select name="major" id="editMajor" required class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="RPL">RPL</option>
                        <option value="TKJ">TKJ</option>
                        <option value="DKV">DKV</option>
                        <option value="AKL">AKL</option>
                        <option value="MP">MP</option>
                        <option value="BR">BR</option>
                        <option value="BD">BD</option>
                        <option value="LPS">LPS</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Rombel</label>
                    <select name="section" id="editSection" required class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeEdit()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg text-sm">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEdit(id, grade, major, section) {
            document.getElementById('editGrade').value = grade;
            document.getElementById('editMajor').value = major;
            document.getElementById('editSection').value = section;
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