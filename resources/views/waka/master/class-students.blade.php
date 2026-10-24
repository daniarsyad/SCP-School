@extends('layouts.waka')

@section('title', 'Siswa - ' . $class->class_name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('waka.master.classes') }}" class="text-blue-600 hover:underline text-sm">← Kembali ke Kelas</a>
        <h1 class="text-2xl font-bold mt-2">Siswa {{ $class->class_name }}</h1>
        <p class="text-gray-500 text-sm">Daftar siswa di kelas ini</p>
    </div>

    <div class="bg-white rounded-xl border overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="font-semibold">Daftar Siswa</h2>
            <span class="text-sm text-gray-500">Total: {{ $students->count() }} Siswa</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left font-medium w-16">No</th>
                    <th class="px-6 py-3 text-left font-medium">Nama Siswa</th>
                    <th class="px-6 py-3 text-left font-medium">Username</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($students as $index => $student)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3">{{ $index + 1 }}</td>
                        <td class="px-6 py-3 font-medium">{{ $student->fullname }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $student->username }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-10 text-center text-gray-500">Belum ada siswa di kelas ini</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection