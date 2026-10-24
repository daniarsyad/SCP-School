@extends('layouts.waka')

@section('title', 'Jadwal Mengajar - SCP')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Pengelolaan Jadwal</h1>
            <p class="text-gray-500 text-sm">
                Atur dan pantau jadwal pelajaran berdasarkan putaran, hari, dan kelas.
            </p>
        </div>

        @if(!$config->is_schedule_locked)
            <button
                onclick="document.getElementById('addForm').classList.toggle('hidden')"
                class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-medium px-4 py-2 rounded-lg text-sm"
            >
                + Tambah Jadwal
            </button>
        @endif
    </div>

    <!-- Filter -->
    <form method="GET" class="bg-white rounded-xl border p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Target Putaran</label>
                <div class="flex gap-1">
                    <a href="{{ request()->fullUrlWithQuery(['cycle' => '1']) }}"
                       class="px-3 py-1.5 text-sm rounded-lg {{ $cycle == '1' ? 'bg-gray-900 text-white' : 'bg-gray-100' }}">
                        Putaran 1
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['cycle' => '2']) }}"
                       class="px-3 py-1.5 text-sm rounded-lg {{ $cycle == '2' ? 'bg-gray-900 text-white' : 'bg-gray-100' }}">
                        Putaran 2
                    </a>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Filter Hari</label>
                <select name="day" class="border rounded-lg px-3 py-1.5 text-sm" onchange="this.form.submit()">
                    <option value="">Semua Hari</option>
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d)
                        <option value="{{ $d }}" {{ $dayFilter == $d ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Grade</label>
                <select name="grade" class="border rounded-lg px-3 py-1.5 text-sm" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    <option value="X" {{ request('grade') == 'X' ? 'selected' : '' }}>X</option>
                    <option value="XI" {{ request('grade') == 'XI' ? 'selected' : '' }}>XI</option>
                    <option value="XII" {{ request('grade') == 'XII' ? 'selected' : '' }}>XII</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Major</label>
                <select name="major" class="border rounded-lg px-3 py-1.5 text-sm" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach(['RPL','TKJ','DKV','AKL','MP','BR','BD','LPS'] as $m)
                        <option value="{{ $m }}" {{ request('major') == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Section</label>
                <select name="section" class="border rounded-lg px-3 py-1.5 text-sm" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    <option value="1" {{ request('section') == '1' ? 'selected' : '' }}>1</option>
                    <option value="2" {{ request('section') == '2' ? 'selected' : '' }}>2</option>
                    <option value="3" {{ request('section') == '3' ? 'selected' : '' }}>3</option>
                    <option value="4" {{ request('section') == '4' ? 'selected' : '' }}>4</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Kelas</label>
                <select name="class_id" class="border rounded-lg px-3 py-1.5 text-sm" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $classFilter == $c->id ? 'selected' : '' }}>{{ $c->class_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <!-- Form Tambah -->
    @if(!$config->is_schedule_locked)
        <div id="addForm" class="hidden bg-white rounded-xl border p-6 mb-6">
            <h2 class="font-semibold mb-4">Tambah Jadwal Baru</h2>
            <form action="{{ route('waka.schedules.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-sm mb-1">Putaran</label>
                    <select name="cycle_type" required class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="1" {{ $cycle == '1' ? 'selected' : '' }}>Putaran 1</option>
                        <option value="2" {{ $cycle == '2' ? 'selected' : '' }}>Putaran 2</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Pengajar</label>
                    <select name="teacher_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->fullname }} ({{ $t->role->role_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Kelas</label>
                    <select name="class_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Ruangan</label>
                    <select name="room_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach($rooms as $r)
                            <option value="{{ $r->id }}">{{ $r->room_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Mata Pelajaran</label>
                    <input type="text" name="subject" required class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm mb-1">Hari</label>
                    <select name="day_of_week" id="daySelect" required class="w-full border rounded-lg px-3 py-2 text-sm" onchange="loadPeriods()">
                        <option value="">-- Pilih --</option>
                        @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d)
                            <option value="{{ $d }}">{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Jam Mulai</label>
                    <select name="period_start_id" id="periodStart" required class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Pilih Hari dulu --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Jam Selesai</label>
                    <select name="period_end_id" id="periodEnd" required class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Pilih Hari dulu --</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm">Simpan</button>
                </div>
            </form>
        </div>

        <script>
            const periodsByDay = @json($periodsByDayJson ?? []);

            function loadPeriods() {
                const day = document.getElementById('daySelect').value;
                const startSel = document.getElementById('periodStart');
                const endSel = document.getElementById('periodEnd');

                startSel.innerHTML = '<option value="">-- Pilih --</option>';
                endSel.innerHTML = '<option value="">-- Pilih --</option>';

                if (!day || !periodsByDay[day]) return;

                periodsByDay[day].forEach(p => {
                    const opt = `<option value="${p.id}" data-start="${p.start}">${p.label} (${p.start} – ${p.end})</option>`;
                    startSel.innerHTML += opt;
                });
                filterEndPeriods();
            }

            function filterEndPeriods() {
                const day = document.getElementById('daySelect').value;
                const startSel = document.getElementById('periodStart');
                const endSel = document.getElementById('periodEnd');

                const selectedStart = startSel.options[startSel.selectedIndex];
                const startTime = selectedStart ? selectedStart.dataset.start : null;
                const startId = startSel.value;

                endSel.innerHTML = '<option value="">-- Pilih --</option>';

                if (!day || !periodsByDay[day] || !startId) return;

                periodsByDay[day].forEach(p => {
                    if (p.id == startId) return;
                    if (startTime && p.start > startTime) {
                        const opt = `<option value="${p.id}">${p.label} (${p.start} – ${p.end})</option>`;
                        endSel.innerHTML += opt;
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                const startSel = document.getElementById('periodStart');
                if (startSel) startSel.addEventListener('change', filterEndPeriods);
            });
        </script>
    @endif

    <!-- Matriks Jadwal -->
    <div class="bg-white rounded-xl border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 w-28">Jam / Waktu</th>
                    @foreach($days as $day)
                        <th class="px-4 py-3 text-left font-medium text-gray-600">{{ strtoupper($day) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($matrix as $time => $daySlots)
                    <tr>
                        <td class="px-4 py-3 text-gray-600 font-medium whitespace-nowrap align-top">
                            {{ substr($time, 0, 5) }}
                        </td>
                        @foreach($days as $day)
                            <td class="px-4 py-3 align-top">
                                @forelse($daySlots[$day] as $s)
                                    <div class="mb-2 p-3 bg-gray-50 rounded-lg border text-xs">
                                        <div class="font-semibold text-gray-900">{{ $s->subject }}</div>
                                        <div class="text-gray-600">{{ $s->teacher->fullname ?? '-' }}</div>
                                        <div class="text-gray-500 mt-1">
                                            {{ $s->class->class_name ?? '-' }} · {{ $s->room->room_name ?? '-' }}
                                        </div>
                                        <div class="text-gray-400 mt-1">
                                            {{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}
                                            <span class="ml-1 px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px]">
                                                P{{ $s->cycle_type }}
                                            </span>
                                        </div>

                                        @if(!$config->is_schedule_locked)
                                            <div class="mt-2 flex gap-2">
                                                <button type="button"
                                                        onclick="openEdit({{ $s->id }}, {{ $s->teacher_id }}, {{ $s->class_id }}, {{ $s->room_id }}, '{{ addslashes($s->subject) }}', '{{ $s->day_of_week }}', {{ $s->period_id ?? 0 }}, '{{ $s->cycle_type }}')"
                                                        class="text-blue-600 hover:underline text-[11px]">
                                                    Edit
                                                </button>
                                                <form action="{{ route('waka.schedules.destroy', $s->id) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('Yakin hapus jadwal ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:underline text-[11px]">Hapus</button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-gray-300 text-xs">—</span>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            Belum ada jadwal untuk filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Edit -->
    @if(!$config->is_schedule_locked)
        <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                <h3 class="font-bold text-lg mb-4">Edit Jadwal</h3>
                <form id="editForm" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm mb-1">Putaran</label>
                        <select name="cycle_type" id="editCycle" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="1">Putaran 1</option>
                            <option value="2">Putaran 2</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Pengajar</label>
                        <select name="teacher_id" id="editTeacher" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->fullname }} ({{ $t->role->role_name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Kelas</label>
                        <select name="class_id" id="editClass" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Ruangan</label>
                        <select name="room_id" id="editRoom" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}">{{ $r->room_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm mb-1">Mata Pelajaran</label>
                        <input type="text" name="subject" id="editSubject" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Hari</label>
                        <select name="day_of_week" id="editDay" required class="w-full border rounded-lg px-3 py-2 text-sm" onchange="loadEditPeriods()">
                            @foreach(['Senin','Selasa','Rabu','Kamis','Jumat'] as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Jam Mulai</label>
                        <select name="period_start_id" id="editPeriodStart" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">-- Pilih --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Jam Selesai</label>
                        <select name="period_end_id" id="editPeriodEnd" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">-- Pilih --</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                        <button type="button" onclick="closeEdit()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg text-sm">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openEdit(id, teacherId, classId, roomId, subject, day, periodId, cycleType) {
                document.getElementById('editForm').action = `/waka/schedules/${id}`;
                document.getElementById('editCycle').value = cycleType;
                document.getElementById('editTeacher').value = teacherId;
                document.getElementById('editClass').value = classId;
                document.getElementById('editRoom').value = roomId;
                document.getElementById('editSubject').value = subject;
                document.getElementById('editDay').value = day;
                loadEditPeriods();
                if (periodId) {
                    document.getElementById('editPeriodStart').value = periodId;
                }
                document.getElementById('editModal').classList.remove('hidden');
                document.getElementById('editModal').classList.add('flex');
            }

            function closeEdit() {
                document.getElementById('editModal').classList.add('hidden');
                document.getElementById('editModal').classList.remove('flex');
            }

            function loadEditPeriods() {
                const day = document.getElementById('editDay').value;
                const startSel = document.getElementById('editPeriodStart');
                const endSel = document.getElementById('editPeriodEnd');

                startSel.innerHTML = '<option value="">-- Pilih --</option>';
                endSel.innerHTML = '<option value="">-- Pilih --</option>';

                if (!day || !periodsByDay[day]) return;

                periodsByDay[day].forEach(p => {
                    const opt = `<option value="${p.id}">${p.label} (${p.start} – ${p.end})</option>`;
                    startSel.innerHTML += opt;
                    endSel.innerHTML += opt;
                });
            }
        </script>
    @endif
@endsection