@extends('layouts.waka')

@section('title', 'Kenaikan Kelas - SCP')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-semibold text-slate-900">Kenaikan Kelas & Kelulusan Siswa</h1>
        <p class="text-slate-500 text-sm mt-0.5">Pilih kelas asal, pilih siswa, lalu naikkan ke kelas tujuan atau luluskan menjadi alumni.</p>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Total Siswa Aktif</p>
            <p class="text-xl font-semibold text-slate-900 mt-1">{{ $stats['total_siswa'] }}</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Kelas Kosong (Siap Diisi)</p>
            <p class="text-xl font-semibold text-slate-900 mt-1">{{ $stats['kelas_kosong'] }} <span class="text-xs font-normal text-slate-400">dari {{ $stats['total_kelas'] }} kelas</span></p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Kelas Terisi Siswa</p>
            <p class="text-xl font-semibold text-slate-900 mt-1">{{ $stats['kelas_terisi'] }}</p>
        </div>
    </div>

    {{-- Alur --}}
    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-2">Urutan Prosedur Kenaikan Kelas</h2>
        <ol class="text-xs text-slate-600 space-y-1 list-decimal list-inside">
            <li><span class="font-medium text-slate-800">Tingkat XII:</span> Luluskan siswa kelas XII terlebih dahulu menjadi Alumni agar kelas XII kosong.</li>
            <li><span class="font-medium text-slate-800">Tingkat XI → XII:</span> Setelah kelas XII kosong, naikkan siswa kelas XI ke kelas XII.</li>
            <li><span class="font-medium text-slate-800">Tingkat X → XI:</span> Setelah kelas XI kosong, naikkan siswa kelas X ke kelas XI.</li>
        </ol>
        <p class="text-xs text-slate-500 mt-2 pt-2 border-t border-slate-100">
            Catatan: Kuota maksimal setiap rombel adalah <strong class="text-slate-700">36 siswa</strong>. Siswa yang <strong class="text-slate-700">tidak dicentang</strong> akan tetap berada di kelas asal (tinggal kelas), dan kelas tujuan tetap bisa menerima siswa selama kapasitasnya masih mencukupi (&le; 36 siswa).
        </p>
    </div>

    {{-- Filter Kelas Asal --}}
    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-80">
                <label class="block text-xs font-medium text-slate-600 mb-1">Pilih Kelas Asal Siswa</label>
                <select name="class_id" class="w-full border border-slate-300 rounded-md px-3 py-1.5 text-sm bg-white focus:border-slate-500 focus:outline-none">
                    <option value="">-- Pilih Kelas Asal --</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->class_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium transition">
                    Terapkan Filter
                </button>
                @if(request('class_id'))
                    <a href="{{ route('waka.promotion') }}" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 px-3.5 py-1.5 rounded-md text-sm font-medium transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if($selectedClass)
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden mb-5">
            <form action="{{ route('waka.promotion.process') }}" method="POST" id="promotionForm">
                @csrf
                <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">

                <div class="px-5 py-3.5 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Daftar Siswa Kelas {{ $selectedClass->class_name }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $students->count() }} siswa terdaftar di kelas ini
                            @if($selectedClass->grade === 'XII')
                                · Siswa terpilih akan diluluskan menjadi <strong class="text-slate-700">Alumni</strong>
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if($selectedClass->grade !== 'XII')
                            <div class="flex items-center gap-1.5">
                                <label for="targetClassSelect" class="text-xs font-medium text-slate-600 whitespace-nowrap">Rombel Tujuan:</label>
                                <select name="target_class_id" id="targetClassSelect"
                                        class="border border-slate-300 rounded-md px-2.5 py-1.5 text-xs bg-white text-slate-700 font-medium focus:border-blue-500 focus:outline-none">
                                    @forelse($targetClasses as $target)
                                        @php
                                            $sisa = max(0, 36 - $target->active_students_count);
                                            $isDefault = $defaultTargetClass && $target->id === $defaultTargetClass->id;
                                        @endphp
                                        <option value="{{ $target->id }}" data-capacity="{{ $sisa }}" {{ $isDefault ? 'selected' : '' }}>
                                            {{ $target->class_name }} (Terisi: {{ $target->active_students_count }}/36 · Sisa {{ $sisa }} kursi)
                                        </option>
                                    @empty
                                        <option value="">-- Buat kelas tujuan di Master Kelas --</option>
                                    @endforelse
                                </select>
                            </div>
                        @endif

                        <span id="selectedCountBadge" class="text-xs px-2.5 py-1 rounded bg-slate-100 text-slate-700 font-medium">
                            0 dipilih
                        </span>

                        @if($selectedClass->grade === 'XII')
                            <button type="submit" name="graduate" value="1"
                                    class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-1.5 rounded-md text-xs font-medium transition"
                                    onclick="return confirm('Yakin luluskan siswa terpilih menjadi Alumni?')">
                                Luluskan Siswa Terpilih
                            </button>
                        @else
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-xs font-medium transition">
                                Proses Kenaikan Kelas
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Alert Info Siswa Tinggal Kelas & Kapasitas --}}
                <div class="px-5 py-2.5 bg-slate-50 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Siswa yang <strong>tidak dicentang</strong> akan tetap berada di kelas <strong class="text-slate-800">{{ $selectedClass->class_name }}</strong> (tinggal kelas).</span>
                    </div>
                    <span class="text-slate-500 text-[11px]">Batas maksimal per rombel: <strong>36 siswa</strong></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse min-w-[550px]">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                            <tr>
                                <th class="px-4 py-3 w-12 text-center">
                                    <input type="checkbox" id="checkAll" class="rounded border-slate-300">
                                </th>
                                <th class="px-4 py-3 w-12 text-center">No</th>
                                <th class="px-4 py-3 w-40">NIS</th>
                                <th class="px-4 py-3">Nama Siswa</th>
                                <th class="px-4 py-3">Username</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-700">
                            @forelse($students as $index => $student)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-check rounded border-slate-300">
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600">{{ $student->nis ?? '-' }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $student->fullname }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600">{{ $student->username }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">Tidak ada siswa aktif di kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const checkAll = document.getElementById('checkAll');
                const studentChecks = Array.from(document.querySelectorAll('.student-check'));
                const badge = document.getElementById('selectedCountBadge');
                const targetSelect = document.getElementById('targetClassSelect');

                function updateCounter() {
                    const count = studentChecks.filter(c => c.checked).length;
                    const total = studentChecks.length;
                    badge.textContent = `${count} dari ${total} dipilih`;

                    if (count > 0) {
                        badge.classList.remove('bg-slate-100', 'text-slate-700');
                        badge.classList.add('bg-blue-50', 'text-blue-700', 'border', 'border-blue-200');
                    } else {
                        badge.classList.remove('bg-blue-50', 'text-blue-700', 'border', 'border-blue-200');
                        badge.classList.add('bg-slate-100', 'text-slate-700');
                    }

                    if (targetSelect) {
                        const selectedOption = targetSelect.options[targetSelect.selectedIndex];
                        const sisaKursi = selectedOption ? parseInt(selectedOption.dataset.capacity || '36', 10) : 36;
                        if (count > sisaKursi) {
                            badge.classList.remove('bg-blue-50', 'text-blue-700', 'border-blue-200');
                            badge.classList.add('bg-red-50', 'text-red-700', 'border', 'border-red-200');
                            badge.textContent = `${count} dipilih (Melebihi sisa ${sisaKursi} kursi!)`;
                        }
                    }
                }

                if (checkAll) {
                    checkAll.addEventListener('change', function () {
                        studentChecks.forEach(c => c.checked = checkAll.checked);
                        updateCounter();
                    });
                }

                studentChecks.forEach(c => {
                    c.addEventListener('change', function () {
                        if (checkAll) {
                            checkAll.checked = studentChecks.every(item => item.checked);
                        }
                        updateCounter();
                    });
                });

                if (targetSelect) {
                    targetSelect.addEventListener('change', updateCounter);
                }

                updateCounter();
            });
        </script>
    @else
        <div class="bg-white rounded-lg border border-slate-200 p-10 text-center text-slate-500">
            <p class="text-sm font-medium text-slate-700">Pilih Kelas Asal Terlebih Dahulu</p>
            <p class="text-xs text-slate-500 mt-1">Pilih salah satu kelas melalui pilihan di atas untuk menampilkan daftar siswa.</p>
        </div>
    @endif
@endsection
@endsection
