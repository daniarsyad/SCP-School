@extends('layouts.waka')

@section('title', 'Jadwal Mengajar - SCP')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Jadwal Mengajar</h1>
            <p class="text-gray-600">
                Putaran Aktif: <strong class="text-blue-600">{{ $config->active_cycle }}</strong>
                @if($config->is_schedule_locked)
                    <span class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-700 rounded">TERKUNCI</span>
                @else
                    <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-700 rounded">TERBUKA</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Form Tambah --}}
    @if(!$config->is_schedule_locked)
        <div class="bg-white rounded-xl shadow p-6 mb-8">
            <h2 class="font-bold text-lg mb-4">Tambah Jadwal Baru</h2>
            <form action="{{ route('waka.schedules.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Guru</label>
                    <select name="teacher_id" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="">-- Pilih Guru --</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ old('teacher_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->fullname }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Kelas</label>
                    <select name="class_id" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ old('class_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->class_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Ruangan</label>
                    <select name="room_id" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="">-- Pilih Ruangan --</option>
                        @foreach($rooms as $r)
                            <option value="{{ $r->id }}" {{ old('room_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->room_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Mata Pelajaran</label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required
                           class="w-full px-3 py-2 border rounded-lg" placeholder="Contoh: Pemrograman Web">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Hari</label>
                    <select name="day_of_week" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="">-- Pilih Hari --</option>
                        @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $day)
                            <option value="{{ $day }}" {{ old('day_of_week') == $day ? 'selected' : '' }}>{{ $day }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Jam Mulai</label>
                    <input type="time" name="start_time" value="{{ old('start_time') }}" required
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Jam Selesai</label>
                    <input type="time" name="end_time" value="{{ old('end_time') }}" required
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg">
                        Tambah Jadwal
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-8">
            Jadwal sedang <strong>TERKUNCI</strong>. Buka kunci di Konfigurasi Sistem untuk menambah/mengubah jadwal.
        </div>
    @endif

    {{-- Tabel --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Hari</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Jam</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Mapel</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Guru</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Kelas</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Ruangan</th>
                        @if(!$config->is_schedule_locked)
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($schedules as $s)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $s->day_of_week }}</td>
                            <td class="px-4 py-3 text-sm">
                                {{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}
                            </td>
                            <td class="px-4 py-3">{{ $s->subject }}</td>
                            <td class="px-4 py-3">{{ $s->teacher->fullname ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $s->class->class_name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $s->room->room_name ?? '-' }}</td>
                            @if(!$config->is_schedule_locked)
                                <td class="px-4 py-3 text-right space-x-2">
                                    <button type="button"
                                            onclick="openEdit(
                                                {{ $s->id }},
                                                {{ $s->teacher_id }},
                                                {{ $s->class_id }},
                                                {{ $s->room_id }},
                                                '{{ addslashes($s->subject) }}',
                                                '{{ $s->day_of_week }}',
                                                '{{ substr($s->start_time, 0, 5) }}',
                                                '{{ substr($s->end_time, 0, 5) }}'
                                            )"
                                            class="text-blue-600 hover:underline text-sm">Edit</button>
                                    <form action="{{ route('waka.schedules.destroy', $s->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Yakin hapus jadwal ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline text-sm">Hapus</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $config->is_schedule_locked ? 6 : 7 }}" class="px-4 py-10 text-center text-gray-500">
                                Belum ada jadwal untuk Putaran {{ $config->active_cycle }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Edit --}}
    @if(!$config->is_schedule_locked)
        <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg mx-4">
                <h3 class="font-bold text-lg mb-4">Edit Jadwal</h3>
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Guru</label>
                            <select name="teacher_id" id="editTeacher" required class="w-full px-3 py-2 border rounded-lg">
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}">{{ $t->fullname }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Kelas</label>
                            <select name="class_id" id="editClass" required class="w-full px-3 py-2 border rounded-lg">
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Ruangan</label>
                            <select name="room_id" id="editRoom" required class="w-full px-3 py-2 border rounded-lg">
                                @foreach($rooms as $r)
                                    <option value="{{ $r->id }}">{{ $r->room_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Mata Pelajaran</label>
                            <input type="text" name="subject" id="editSubject" required class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Hari</label>
                            <select name="day_of_week" id="editDay" required class="w-full px-3 py-2 border rounded-lg">
                                @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $day)
                                    <option value="{{ $day }}">{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Jam Mulai</label>
                            <input type="time" name="start_time" id="editStart" required class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Jam Selesai</label>
                            <input type="time" name="end_time" id="editEnd" required class="w-full px-3 py-2 border rounded-lg">
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeEdit()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openEdit(id, teacherId, classId, roomId, subject, day, start, end) {
                document.getElementById('editForm').action = `/waka/schedules/${id}`;
                document.getElementById('editTeacher').value = teacherId;
                document.getElementById('editClass').value = classId;
                document.getElementById('editRoom').value = roomId;
                document.getElementById('editSubject').value = subject;
                document.getElementById('editDay').value = day;
                document.getElementById('editStart').value = start;
                document.getElementById('editEnd').value = end;
                document.getElementById('editModal').classList.remove('hidden');
                document.getElementById('editModal').classList.add('flex');
            }
            function closeEdit() {
                document.getElementById('editModal').classList.add('hidden');
                document.getElementById('editModal').classList.remove('flex');
            }
        </script>
    @endif
@endsection