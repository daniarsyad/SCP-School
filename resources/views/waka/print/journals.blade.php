@extends('waka.print.layout')

@section('title', 'Rekap Jurnal Mengajar Guru')
@section('toolbar_title', 'Rekapitulasi Jurnal Mengajar (KBM)')
@section('sheet_class', 'landscape')

@section('styles')
<style>
    @media print {
        @page {
            size: A4 landscape !important;
            margin: 10mm 12mm 12mm 12mm !important;
        }
    }
</style>
@endsection

@section('content')
    <div class="doc-title-container">
        <div class="doc-title">Laporan Rekapitulasi Jurnal Mengajar Guru (KBM)</div>
        <div class="doc-subtitle">
            {{ $docSubtitle ?? 'Periode Kegiatan Belajar Mengajar SMK Negeri 1 Bantul' }}
        </div>
    </div>

    <!-- Metadata Informasi -->
    <table style="width: 100%; font-size: 9.5pt; margin-bottom: 10px; border: none;">
        <tr>
            <td style="width: 14%; padding: 2px 0; font-weight: bold;">Periode / Putaran</td>
            <td style="width: 36%; padding: 2px 0;">: {{ $periodText ?? '-' }}</td>
            <td style="width: 14%; padding: 2px 0; font-weight: bold;">Total Sesi Jurnal</td>
            <td style="width: 36%; padding: 2px 0;">: {{ $journals->count() }} sesi tercatat</td>
        </tr>
        <tr>
            <td style="padding: 2px 0; font-weight: bold;">Filter Diterapkan</td>
            <td style="padding: 2px 0;">: {{ !empty($appliedFilters) ? implode(' | ', $appliedFilters) : 'Semua Data' }}</td>
            <td style="padding: 2px 0; font-weight: bold;">Tanggal Cetak</td>
            <td style="padding: 2px 0;">: {{ \Carbon\Carbon::now()->translatedFormat('d F Y · H:i') }} WIB</td>
        </tr>
    </table>

    <!-- Tabel Jurnal Mengajar -->
    <table class="data-table" style="font-size: 8.5pt;">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 90px;">Hari & Tanggal</th>
                <th style="width: 75px;">Waktu</th>
                <th style="width: 130px;">Mata Pelajaran</th>
                <th style="width: 140px;">Guru Pengajar</th>
                <th style="width: 70px;">Kelas</th>
                <th style="width: 65px;">Ruang</th>
                <th>Materi / Topik Pembelajaran</th>
                <th style="width: 140px;">Catatan KBM</th>
                <th style="width: 80px;">Presensi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($journals as $index => $j)
                @php
                    $sch = $j->schedule;
                    $atts = $j->attendances ?? collect();
                    $h = $atts->where('status', 'Hadir')->count();
                    $i = $atts->where('status', 'Izin')->count();
                    $s = $atts->where('status', 'Sakit')->count();
                    $a = $atts->where('status', 'Alpha')->count();
                    $dateObj = \Carbon\Carbon::parse($j->date);
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center nowrap">
                        <div class="font-bold">{{ $dateObj->locale('id')->isoFormat('dddd') }}</div>
                        <div style="font-size: 7.5pt; color: #475569;">{{ $dateObj->format('d/m/Y') }}</div>
                    </td>
                    <td class="text-center nowrap font-mono" style="font-size: 8pt;">
                        {{ substr($sch->start_time ?? '', 0, 5) }}–{{ substr($sch->end_time ?? '', 0, 5) }}
                    </td>
                    <td class="font-bold" style="font-family: Arial, sans-serif;">{{ $sch->subject ?? '-' }}</td>
                    <td>{{ $sch->teacher->fullname ?? '-' }}</td>
                    <td class="text-center font-bold" style="font-family: Arial, sans-serif;">
                        {{ $sch->class->class_name ?? '-' }}
                    </td>
                    <td class="text-center" style="font-size: 8pt;">{{ $sch->room->room_name ?? '-' }}</td>
                    <td>
                        <div class="font-bold" style="font-size: 8.5pt;">{{ $j->topic_description }}</div>
                        @if($j->late_minutes > 0)
                            <div style="font-size: 7pt; color: #b91c1c;">(Input terlambat {{ $j->late_minutes }} mnt)</div>
                        @endif
                    </td>
                    <td style="font-size: 8pt; color: #334155;">
                        {{ $j->activity_notes ?: '-' }}
                    </td>
                    <td class="text-center nowrap" style="font-size: 7.5pt;">
                        <span style="color: #166534; font-weight: bold;">H:{{ $h }}</span>
                        @if($i > 0)<span style="color: #92400e;"> I:{{ $i }}</span>@endif
                        @if($s > 0)<span style="color: #1e40af;"> S:{{ $s }}</span>@endif
                        @if($a > 0)<span style="color: #991b1b; font-weight: bold;"> A:{{ $a }}</span>@endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 25px;">
                        Tidak ada data jurnal mengajar pada periode / filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Tanda Tangan Resmi Landscape -->
    <div class="signature-block" style="margin-top: 18px;">
        <div class="signature-box" style="width: 250px;">
            <p>Mengetahui,</p>
            <p>Kepala SMK Negeri 1 Bantul</p>
            <div class="signature-space"></div>
            <p class="signature-name">MUJIONO, S.Pd., M.Eng.</p>
            <p class="signature-nip">NIP. 19680312 199412 1 002</p>
        </div>

        <div class="signature-box" style="width: 250px;">
            <p>Bantul, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
            <p>Waka Kurikulum</p>
            <div class="signature-space"></div>
            <p class="signature-name">WIDI ASTUTI, S.Pd.</p>
            <p class="signature-nip">NIP. 19750815 200501 2 008</p>
        </div>
    </div>
@endsection

