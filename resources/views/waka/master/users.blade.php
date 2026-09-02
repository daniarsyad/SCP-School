@extends('layouts.waka')

@section('title', 'Master User - SCP')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Master Data - User</h1>
    </div>

    <!-- Search -->
    <form method="GET" action="{{ route('waka.master.users') }}" class="mb-6">
        <div class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama atau username..."
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                Cari
            </button>
            @if(request('search'))
                <a href="{{ route('waka.master.users') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- Form Tambah -->
    <div class="bg-white rounded-xl shadow p-6 mb-8">
        <h2 class="font-bold text-lg mb-4">Tambah User Baru</h2>
        <form action="{{ route('waka.master.users.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
                <input type="text" name="fullname" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Username</label>
                <input type="text" name="username" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Role</label>
                <select name="role_id" required class="w-full px-4 py-2 border rounded-lg">
                    <option value="">-- Pilih Role --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->role_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Kelas (opsional)</label>
                <select name="class_id" class="w-full px-4 py-2 border rounded-lg">
                    <option value="">-- Tidak ada --</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->class_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg w-full">
                    Tambah User
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">No</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Nama</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Username</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Role</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Kelas</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($users as $index => $user)
                    <tr>
                        <td class="px-6 py-4">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 font-medium">{{ $user->fullname }}</td>
                        <td class="px-6 py-4">{{ $user->username }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                {{ $user->role->role_name ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ $user->class->class_name ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <form action="{{ route('waka.master.users.delete', $user->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Yakin hapus user ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            @if(request('search'))
                                Tidak ada user yang cocok dengan pencarian
                            @else
                                Belum ada data user
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection