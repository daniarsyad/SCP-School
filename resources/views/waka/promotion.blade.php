@extends('layouts.waka')

@section('title', 'Kenaikan Kelas - SCP')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Kenaikan Kelas & Kelulusan Siswa</h1>
        <p class="text-gray-500 text-sm">Pilih kelas asal, lihat siswa, lalu naikkan ke kelas tujuan atau luluskan.</p>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border p-5">
            <p class="text-sm text-gray-500">Total Siswa Aktif</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['total_siswa'] }}</p>
        </div>
        <div class="bg-white rounded-xl border p-5">
            <p class="text-sm text-gray-500">Kelas Kosong (Siap Diisi)</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['kelas_kosong'] }} / {{ $stats['total_kelas'] }}</p>
        </div>
        <div class="bg-white rounded-xl border p-5">
            <p class="text-sm text-gray-500">Kelas Terisi Siswa</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['kelas_terisi'] }}</p>
        </div>
    </div>

    {{-- Alur --}}
    <div class="bg-white rounded-xl border p-5 mb-6">
        <h2 class="font-semibold mb-2">Alur & Prosedur Kenaikan Kelas</h2>
        <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
            <li><strong>Langkah 1 (Tingkat XII):</strong> Luluskan siswa kelas XII terlebih dahulu. Data siswa yang lulus tetap tersimpan di database (status Alumni), sehingga kelas XII menjadi kosong.</li>
            <li><strong>Langkah 2 (Tingkat XI → XII):</strong> Setelah kelas XII kosong, siswa kelas XI dapat dinaikkan ke kelas XII.</li>
            <li><strong>Langkah 3 (Tingkat X → XI):</strong> Setelah kelas XI kosong, siswa kelas X dapat dinaikkan ke kelas XI.</li>
            <li class="text-red-600"><strong>Catatan Validasi:</strong> Kelas tujuan HARUS KOSONG (0 siswa aktif). Sistem akan menolak kenaikan apabila kelas tujuan masih terisi.</li>
        </ul>
    </div>

    {{-- Filter Kelas Asal --}}
    <div class="bg-white rounded-xl border p-5 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium mb-1">Pilih Kelas Asal Siswa</label>
                <select name="class_id" class="w-full border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas Asal yang Akan Diproses --</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->class_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if($selectedClass)
    <div class="bg-white rounded-xl border overflow-hidden mb-6">
        <div class="px-6 py-4 border-b">
            <h2 class="font-semibold">Siswa {{ $selectedClass->class_name }}</h2>
            <p class="text-sm text-gray-500">{{ $students->count() }} siswa</p>
        </div>

        <form action="{{ route('waka.promotion.process') }}" method="POST">
            @csrf
            <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">

            <div class="px-6 py-4 border-b flex flex-wrap items-center gap-3">
                @if($selectedClass->grade === 'XII')
                    <span class="text-sm text-gray-600">Kelas XII → akan diluluskan menjadi Alumni</span>
                    <button type="submit" name="graduate" value="1"
                            class="ml-auto bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm"
                            onclick="return confirm('Yakin luluskan siswa terpilih menjadi Alumni?')">
                        Luluskan (Alumni)
                    </button>
                @else
                    @php
                        $nextGrade = $selectedClass->grade === 'X' ? 'XI' : 'XII';
                        $targetName = $nextGrade . ' ' . $selectedClass->major . ' ' . $selectedClass->section;
                    @endphp
                    <span class="text-sm text-gray-600">
                        Akan naik ke: <strong>{{ $targetName }}</strong>
                    </span>
                    <button type="submit"
                            class="ml-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                        Proses Kenaikan
                    </button>
                @endif
            </div>

            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left w-10">
                            <input type="checkbox" id="checkAll" onclick="document.querySelectorAll('.student-check').forEach(c => c.checked = this.checked)">
                        </th>
                        <th class="px-6 py-3 text-left font-medium">No</th>
                        <th class="px-6 py-3 text-left font-medium">Nama Siswa</th>
                        <th class="px-6 py-3 text-left font-medium">Username</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($students as $index => $student)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-check">
                            </td>
                            <td class="px-6 py-3">{{ $index + 1 }}</td>
                            <td class="px-6 py-3 font-medium">{{ $student->fullname }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $student->username }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-500">Tidak ada siswa di kelas ini</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </form>
    </div>
@else
        <div class="bg-white rounded-xl border p-10 text-center text-gray-500">
            <p class="font-medium">Silakan Pilih Kelas Asal Terlebih Dahulu</p>
            <p class="text-sm mt-1">Pilih salah satu kelas melalui menu dropdown di atas untuk memulai proses kenaikan atau kelulusan siswa.</p>
        </div>
    @endif
@endsection