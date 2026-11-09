@extends('waka.print.layout')

@section('title', 'Rekap Presensi per Kelas - Putaran ' . ($history->cycle_type ?? ''))
@section('toolbar_title', 'Rekapitulasi Presensi Seluruh Kelas · Putaran ' . ($history->cycle_type ?? ''))

@section('content')
    <div class="doc-title-container">
        <div class="doc-title">Rekapitulasi Kehadiran Siswa per Kelas</div>
        <div class="doc-subtitle">
            Putaran {{ $history->cycle_type }} · Periode: {{ $history->start_date->translatedFormat('d F Y') }} – {{ $history->end_date->translatedFormat('d F Y') }}
        </div>
    </div>

    <!-- Info Filter jika ada -->
    @if(!empty($appliedFilters))
        <div style="font-size: 9pt; color: #475569; margin-bottom: 8px;">
            Filter Diterapkan: <em>{{ implode(' | ', $appliedFilters) }}</em>
        </div>
    @endif

    <!-- Ringkasan Statistik -->
    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px 14px; margin-bottom: 12px; font-size: 9.5pt; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span style="color: #475569;">Total Kelas Terdata:</span>
            <strong>{{ $classRecap->count() }} kelas</strong>
        </div>
        <div>
            <span style="color: #475569;">Total Sesi KBM:</span>
            <strong>{{ $classRecap->sum('journals') }} sesi jurnal</strong>
        </div>
        <div>
            <span style="color: #475569;">Akumulasi Presensi:</span>
            <span style="color: #166534; font-weight: bold;">H: {{ $classRecap->sum('hadir') }}</span> ·
            <span style="color: #92400e; font-weight: bold;">I: {{ $classRecap->sum('izin') }}</span> ·
            <span style="color: #1e40af; font-weight: bold;">S: {{ $classRecap->sum('sakit') }}</span> ·
            <span style="color: #991b1b; font-weight: bold;">A: {{ $classRecap->sum('alpha') }}</span>
        </div>
        @php
            $totHadir = $classRecap->sum('hadir');
            $totAll = $totHadir + $classRecap->sum('izin') + $classRecap->sum('sakit') + $classRecap->sum('alpha');
            $totRate = $totAll > 0 ? round(($totHadir / $totAll) * 100, 1) : 0;
        @endphp
        <div>
            <span style="color: #475569;">Rata-rata Sekolah:</span>
            <strong style="color: #166534; font-size: 11pt;">{{ $totRate }}%</strong>
        </div>
    </div>

    <!-- Tabel Rekapitulasi Presensi per Kelas -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 140px;">Nama Kelas</th>
                <th>Tanggal KBM Tercatat</th>
                <th style="width: 60px;">Total Sesi</th>
                <th style="width: 50px;">Hadir</th>
                <th style="width: 50px;">Izin</th>
                <th style="width: 50px;">Sakit</th>
                <th style="width: 50px;">Alpha</th>
                <th style="width: 65px;">% Hadir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($classRecap as $index => $row)
                @php
                    $h = $row['hadir'];
                    $i = $row['izin'];
                    $s = $row['sakit'];
                    $a = $row['alpha'];
                    $t = $h + $i + $s + $a;
                    $rate = $t > 0 ? round(($h / $t) * 100, 1) : 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold" style="font-family: Arial, sans-serif;">{{ $row['class_name'] }}</td>
                    <td style="font-size: 8.5pt;">
                        @if(!empty($row['dates']) && count($row['dates']) > 0)
                            {{ implode(', ', $row['dates']->toArray()) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center font-bold">{{ $row['journals'] }}</td>
                    <td class="text-center">{{ $h }}</td>
                    <td class="text-center">{{ $i }}</td>
                    <td class="text-center">{{ $s }}</td>
                    <td class="text-center" style="{{ $a > 0 ? 'font-weight: bold; color: #b91c1c;' : '' }}">{{ $a }}</td>
                    <td class="text-center font-bold" style="color: {{ $rate >= 85 ? '#15803d' : ($rate >= 75 ? '#b45309' : '#b91c1c') }};">
                        {{ $rate }}%
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px;">Tidak ada data presensi pada periode/filter ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: bold;">
                <td colspan="3" class="text-center">TOTAL KESELURUHAN</td>
                <td class="text-center">{{ $classRecap->sum('journals') }}</td>
                <td class="text-center">{{ $classRecap->sum('hadir') }}</td>
                <td class="text-center">{{ $classRecap->sum('izin') }}</td>
                <td class="text-center">{{ $classRecap->sum('sakit') }}</td>
                <td class="text-center">{{ $classRecap->sum('alpha') }}</td>
                <td class="text-center">{{ $totRate }}%</td>
            </tr>
        </tfoot>
    </table>

    <!-- Tanda Tangan Resmi -->
    <div class="signature-block">
        <div class="signature-box">
            <p>Mengetahui,</p>
            <p>Kepala SMK Negeri 1 Bantul</p>
            <div class="signature-space"></div>
            <p class="signature-name">MUJIONO, S.Pd., M.Eng.</p>
            <p class="signature-nip">NIP. 19680312 199412 1 002</p>
        </div>

        <div class="signature-box">
            <p>Bantul, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
            <p>Waka Kurikulum</p>
            <div class="signature-space"></div>
            <p class="signature-name">WIDI ASTUTI, S.Pd.</p>
            <p class="signature-nip">NIP. 19750815 200501 2 008</p>
        </div>
    </div>
@endsection

