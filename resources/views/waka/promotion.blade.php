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

    {{-- Alur & Prosedur --}}
    <div class="bg-white rounded-lg border border-slate-200 p-5 mb-5 shadow-xs">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-sm font-semibold text-slate-800">Alur & Ketentuan Kenaikan Kelas Terbaru</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/70">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-5 h-5 rounded-full bg-slate-800 text-white text-[11px] font-bold flex items-center justify-center shrink-0">1</span>
                    <span class="text-xs font-semibold text-slate-800">Luluskan Tingkat XII</span>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Pilih kelas XII, centang siswa yang lulus, lalu klik <strong>Luluskan Siswa</strong>. Akun siswa berganti status jadi <em>Alumni</em> dan kursi kelas XII kosong untuk diisi siswa berikutnya.
                </p>
            </div>

            <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/70">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-5 h-5 rounded-full bg-slate-800 text-white text-[11px] font-bold flex items-center justify-center shrink-0">2</span>
                    <span class="text-xs font-semibold text-slate-800">Kenaikan XI &rarr; XII</span>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Pilih kelas XI. Siswa yang <strong>tinggal kelas jangan dicentang</strong> (otomatis tetap di XI). Siswa yang naik dicentang lalu tentukan rombel XII tujuan (maks. 36 siswa).
                </p>
            </div>

            <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/70">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-5 h-5 rounded-full bg-slate-800 text-white text-[11px] font-bold flex items-center justify-center shrink-0">3</span>
                    <span class="text-xs font-semibold text-slate-800">Kenaikan X &rarr; XI</span>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Pilih kelas X. Siswa tinggal kelas jangan dicentang (tetap di X). Siswa yang naik dicentang dan diarahkan ke kelas XI tujuan (sisa kuota otomatis memperhitungkan siswa tinggal kelas di XI).
                </p>
            </div>
        </div>

        <div class="p-3.5 rounded-lg bg-amber-50/80 border border-amber-200/80 text-xs text-amber-950 space-y-1.5">
            <div class="font-semibold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Ketentuan Penting Siswa Tinggal Kelas & Kuota Maksimal (36 Siswa):</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-slate-700 pl-0.5">
                <li><strong>Siswa Tinggal Kelas:</strong> Cukup <em>hilangkan centang</em> pada nama siswa tersebut. Siswa yang tidak dicentang otomatis tetap terdaftar di kelas asalnya tanpa perlu dipindahkan manual.</li>
                <li><strong>Batas Maksimal 36 Siswa:</strong> Setiap rombel dibatasi maksimal 36 siswa. Jika rombel kelas tujuan sudah berisi siswa yang tinggal kelas, kuota kursi yang tersisa akan berkurang secara otomatis.</li>
                <li><strong>Pemerataan ke Rombel Paralel:</strong> Jika rombel tujuan utama penuh (misal XI RPL 1 sisa kuotanya kurang akibat ada siswa tinggal kelas), Waka dapat memilih <strong>Rombel Tujuan</strong> lain pada pilihan dropdown (misal diarahkan ke XI RPL 2 yang masih memiliki sisa kursi).</li>
            </ul>
        </div>
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
