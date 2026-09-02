@extends('layouts.waka')

@section('title', 'History Putaran - SCP')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">History Putaran</h1>
            <p class="text-gray-600">
                Putaran Aktif: <strong class="text-blue-600">Putaran {{ $config->active_cycle }}</strong>
                ({{ $config->start_date?->format('d M Y') }} - {{ $config->end_date?->format('d M Y') }})
            </p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600 whitespace-nowrap">Putaran</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600 whitespace-nowrap">Periode</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600 whitespace-nowrap">Total Jurnal</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600 whitespace-nowrap">Total Presensi</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600 whitespace-nowrap">Jumlah Guru</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600 whitespace-nowrap">Ditutup Oleh</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600 whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($histories as $history)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-block px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                    Putaran {{ $history->cycle_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ $history->start_date->format('d M Y') }} – {{ $history->end_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $history->total_journals }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $history->total_attendances }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $history->teachers ? count($history->teachers) : 0 }} guru
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ $history->closedBy->fullname ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('waka.cycle.history.detail', $history->id) }}"
                                   class="text-blue-600 hover:underline text-sm font-medium">
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                Belum ada history putaran
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection