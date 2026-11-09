@extends('waka.print.layout')

@section('title', 'Rekap Presensi - ' . $class->class_name)
@section('toolbar_title', 'Rekap Presensi Kelas ' . $class->class_name)

@section('content')
    <div class="doc-title-container">
        <div class="doc-title">Laporan Rekapitulasi Presensi Siswa</div>
        <div class="doc-subtitle">Tahun Ajaran {{ date('Y') }}/{{ date('Y') + 1 }} · Semester Ganjil</div>
    </div>

    <!-- Metadata Informasi Kelas -->
    <table style="width: 100%; font-size: 10pt; margin-bottom: 12px; border: none;">
        <tr>
            <td style="width: 15%; padding: 2px 0; font-weight: bold;">Kelas</td>
            <td style="width: 35%; padding: 2px 0;">: {{ $class->class_name }}</td>
            <td style="width: 18%; padding: 2px 0; font-weight: bold;">Periode / Putaran</td>
            <td style="width: 32%; padding: 2px 0;">: {{ $periodText ?? 'Semua Putaran' }}</td>
        </tr>
        <tr>
            <td style="padding: 2px 0; font-weight: bold;">Jurusan / Rombel</td>
            <td style="padding: 2px 0;">: {{ $class->major }} / Rombel {{ $class->section }}</td>
            <td style="padding: 2px 0; font-weight: bold;">Wali Kelas</td>
            <td style="padding: 2px 0;">: {{ $class->homeroomTeacher?->fullname ?? 'Belum ditentukan' }}</td>
        </tr>
        <tr>
            <td style="padding: 2px 0; font-weight: bold;">Jumlah Siswa</td>
            <td style="padding: 2px 0;">: {{ $students->count() }} siswa aktif</td>
            <td style="padding: 2px 0; font-weight: bold;">Tanggal Cetak</td>
            <td style="padding: 2px 0;">: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <!-- Ringkasan Statistik -->
    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px 14px; margin-bottom: 12px; font-size: 9.5pt; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span style="color: #475569;">Total Pertemuan Tercatat:</span>
            <strong>{{ $totalSessions ?? ($classTotalPresensi > 0 ? ceil($classTotalPresensi / max(1, $students->count())) : 0) }} sesi</strong>
        </div>
        <div>
            <span style="color: #475569;">Total Presensi:</span>
            <span style="color: #166534; font-weight: bold;">H: {{ $classTotalHadir }}</span> ·
            <span style="color: #92400e; font-weight: bold;">I: {{ $classTotalIzin }}</span> ·
            <span style="color: #1e40af; font-weight: bold;">S: {{ $classTotalSakit }}</span> ·
            <span style="color: #991b1b; font-weight: bold;">A: {{ $classTotalAlpha }}</span>
        </div>
        <div>
            <span style="color: #475569;">Rata-rata Kehadiran:</span>
            <strong style="color: {{ $classAttendanceRate >= 85 ? '#166534' : ($classAttendanceRate >= 75 ? '#92400e' : '#991b1b') }}; font-size: 11pt;">{{ $classAttendanceRate }}%</strong>
        </div>
    </div>

    <!-- Tabel Daftar Siswa & Presensi -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 95px;">NIS</th>
                <th>Nama Siswa</th>
                <th style="width: 45px;">Hadir</th>
                <th style="width: 45px;">Izin</th>
                <th style="width: 45px;">Sakit</th>
                <th style="width: 45px;">Alpha</th>
                <th style="width: 50px;">Total</th>
                <th style="width: 65px;">% Hadir</th>
                <th style="width: 90px;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
                @php
                    $stats = $studentStats[$student->id] ?? [
                        'hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0, 'total' => 0, 'percentage' => 0
                    ];
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center font-mono">{{ $student->nis ?? '-' }}</td>
                    <td class="text-left font-bold" style="font-family: Arial, sans-serif; font-size: 9pt;">{{ $student->fullname }}</td>
                    <td class="text-center">{{ $stats['hadir'] }}</td>
                    <td class="text-center">{{ $stats['izin'] }}</td>
                    <td class="text-center">{{ $stats['sakit'] }}</td>
                    <td class="text-center" style="{{ $stats['alpha'] > 0 ? 'font-weight: bold; color: #b91c1c;' : '' }}">{{ $stats['alpha'] }}</td>
                    <td class="text-center font-bold">{{ $stats['total'] }}</td>
                    <td class="text-center font-bold" style="color: {{ $stats['percentage'] >= 85 ? '#15803d' : ($stats['percentage'] >= 75 ? '#b45309' : '#b91c1c') }};">
                        {{ $stats['percentage'] }}%
                    </td>
                    <td class="text-center" style="font-size: 8.5pt;">
                        @if($stats['percentage'] >= 85)
                            Sangat Baik
                        @elseif($stats['percentage'] >= 75)
                            Cukup
                        @elseif($stats['total'] > 0)
                            <span style="color: #b91c1c; font-weight: bold;">Perlu Bimbingan</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px;">Belum ada data siswa terdaftar di kelas ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: bold;">
                <td colspan="3" class="text-center">TOTAL KESELURUHAN</td>
                <td class="text-center">{{ $classTotalHadir }}</td>
                <td class="text-center">{{ $classTotalIzin }}</td>
                <td class="text-center">{{ $classTotalSakit }}</td>
                <td class="text-center">{{ $classTotalAlpha }}</td>
                <td class="text-center">{{ $classTotalPresensi }}</td>
                <td class="text-center">{{ $classAttendanceRate }}%</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Tanda Tangan Resmi -->
    <div class="signature-block">
        <div class="signature-box">
            <p>Mengetahui,</p>
            <p>Waka Kurikulum</p>
            <div class="signature-space"></div>
            <p class="signature-name">WIDI ASTUTI, S.Pd.</p>
            <p class="signature-nip">NIP. 19750815 200501 2 008</p>
        </div>

        <div class="signature-box">
            <p>Bantul, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
            <p>Wali Kelas {{ $class->class_name }}</p>
            <div class="signature-space"></div>
            <p class="signature-name">{{ $class->homeroomTeacher?->fullname ?? '............................................' }}</p>
            <p class="signature-nip">NIP. {{ $class->homeroomTeacher?->nip ?? '............................................' }}</p>
        </div>
    </div>
@endsection

