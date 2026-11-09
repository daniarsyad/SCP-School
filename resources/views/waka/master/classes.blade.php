@extends('layouts.waka')

@section('title', 'Master Kelas - SCP')

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-5">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Data Kelas</h1>
            <p class="text-slate-500 text-sm mt-0.5">Kelola daftar rombongan belajar dan kelas di sekolah.</p>
        </div>
        <button onclick="document.getElementById('addForm').classList.toggle('hidden')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-2 rounded-md text-sm font-medium transition">
            + Tambah Kelas
        </button>
    </div>

    <!-- Filter -->
    <form method="GET" class="bg-white rounded-lg border border-slate-200 p-4 mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="w-40">
                <label class="block text-xs font-medium text-slate-600 mb-1">Tingkat</label>
                <select name="grade" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="">Semua Tingkat</option>
                    <option value="X" {{ request('grade') == 'X' ? 'selected' : '' }}>Kelas X</option>
                    <option value="XI" {{ request('grade') == 'XI' ? 'selected' : '' }}>Kelas XI</option>
                    <option value="XII" {{ request('grade') == 'XII' ? 'selected' : '' }}>Kelas XII</option>
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-slate-600 mb-1">Jurusan</label>
                <select name="major" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="">Semua Jurusan</option>
                    @foreach(['RPL','TKJ','DKV','AKL','MP','BR','BD','LPS'] as $m)
                        <option value="{{ $m }}" {{ request('major') == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-slate-600 mb-1">Rombel</label>
                <select name="section" class="w-full border border-slate-300 rounded-md px-2.5 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="">Semua Rombel</option>
                    <option value="1" {{ request('section') == '1' ? 'selected' : '' }}>1</option>
                    <option value="2" {{ request('section') == '2' ? 'selected' : '' }}>2</option>
                    <option value="3" {{ request('section') == '3' ? 'selected' : '' }}>3</option>
                    <option value="4" {{ request('section') == '4' ? 'selected' : '' }}>4</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium transition">
                    Terapkan Filter
                </button>
                @if(request()->hasAny(['grade', 'major', 'section']))
                    <a href="{{ route('waka.master.classes') }}"
                       class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 px-3.5 py-1.5 rounded-md text-sm font-medium transition">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Form Tambah -->
    <div id="addForm" class="hidden bg-white rounded-lg border border-slate-200 p-5 mb-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Tambah Kelas Baru</h2>
        <form action="{{ route('waka.master.classes.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Tingkat</label>
                <select name="grade" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                    <option value="X">X</option>
                    <option value="XI">XI</option>
                    <option value="XII">XII</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Jurusan</label>
                <select name="major" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
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
                <label class="block text-xs font-medium text-slate-600 mb-1">Rombel</label>
                <select name="section" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium">Simpan Kelas</button>
            </div>
        </form>
    </div>

    <!-- Tabel -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-sm font-semibold text-slate-800">Daftar Kelas</h2>
            <span class="text-xs text-slate-500">{{ $classes->count() }} kelas</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse min-w-[650px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Nama Kelas</th>
                        <th class="px-4 py-3">Tingkat</th>
                        <th class="px-4 py-3">Jurusan</th>
                        <th class="px-4 py-3">Rombel</th>
                        <th class="px-4 py-3 text-center">Siswa Aktif</th>
                        <th class="px-4 py-3 text-center">Total Jadwal</th>
                        <th class="px-4 py-3 text-right w-44">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($classes as $index => $class)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">
                                <a href="{{ route('waka.schedules.index', ['class_id' => $class->id]) }}"
                                   class="hover:text-blue-600 hover:underline"
                                   title="Lihat jadwal {{ $class->class_name }}">
                                    {{ $class->class_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $class->grade ?? '-' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $class->major ?? '-' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $class->section ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-xs text-slate-600">{{ $class->users_count ?? 0 }} siswa</td>
                            <td class="px-4 py-3 text-center text-xs text-slate-600">{{ $class->schedules_count ?? 0 }} sesi</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('waka.master.classes.students', $class->id) }}"
                                       class="rounded border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 transition"
                                       title="Lihat detail kelas dan rekap presensi">
                                        Detail & Presensi
                                    </a>
                                    <button type="button" onclick="openEdit({{ $class->id }}, '{{ $class->grade }}', '{{ $class->major }}', '{{ $class->section }}')"
                                            class="rounded border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        Edit
                                    </button>
                                    <form action="{{ route('waka.master.classes.delete', $class->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Yakin hapus kelas ini?')">
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
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">Belum ada data kelas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg border border-slate-200 p-6 w-full max-w-md shadow-lg">
            <h3 class="font-semibold text-base text-slate-800 mb-4">Edit Kelas</h3>
            <form id="editForm" method="POST" class="space-y-3.5">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tingkat</label>
                    <select name="grade" id="editGrade" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                        <option value="X">X</option>
                        <option value="XI">XI</option>
                        <option value="XII">XII</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Jurusan</label>
                    <select name="major" id="editMajor" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
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
                    <label class="block text-xs font-medium text-slate-600 mb-1">Rombel</label>
                    <select name="section" id="editSection" required class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeEdit()" class="px-3.5 py-1.5 text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-md text-sm">Batal</button>
                    <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">Simpan</button>
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
