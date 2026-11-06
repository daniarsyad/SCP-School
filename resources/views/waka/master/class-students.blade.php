@extends('layouts.waka')

@section('title', 'Siswa - ' . $class->class_name)

@section('content')
    <div class="mb-5">
        <a href="{{ route('waka.master.classes') }}" class="text-blue-600 hover:underline text-xs font-medium">← Kembali ke Daftar Kelas</a>
        <h1 class="text-xl font-semibold text-slate-900 mt-1.5">Siswa Kelas {{ $class->class_name }}</h1>
        <p class="text-slate-500 text-sm mt-0.5">Daftar siswa aktif yang terdaftar di kelas {{ $class->class_name }}.</p>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-sm font-semibold text-slate-800">Daftar Siswa</h2>
            <span class="text-xs text-slate-500">{{ $students->count() }} siswa</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3 w-40">NIS</th>
                        <th class="px-4 py-3">Nama Lengkap</th>
                        <th class="px-4 py-3">Username</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($students as $index => $student)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $student->nis ?? '-' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $student->fullname }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $student->username }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500">Belum ada siswa terdaftar di kelas ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
