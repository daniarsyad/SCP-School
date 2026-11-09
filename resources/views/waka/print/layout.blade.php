<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dokumen Cetak - SCP SMK Negeri 1 Bantul')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-smkn1.png') }}">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            color: #0f172a;
            background-color: #f1f5f9;
            font-size: 11pt;
            line-height: 1.35;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Toolbar Layar (Tidak Tercetak) */
        .print-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #0f172a;
            color: #ffffff;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 13px;
        }

        .print-toolbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .print-toolbar-left img {
            height: 28px;
            width: auto;
        }

        .print-toolbar-title {
            font-weight: 600;
            font-size: 14px;
            color: #f8fafc;
        }

        .print-toolbar-hint {
            font-size: 11px;
            color: #94a3b8;
        }

        .print-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            border: none;
        }

        .btn-print {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-print:hover {
            background: #1d4ed8;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.12);
            color: #f1f5f9;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Lembar Kertas */
        .sheet-container {
            padding-top: 65px;
            padding-bottom: 40px;
            display: flex;
            justify-content: center;
        }

        .sheet {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 18mm 18mm 18mm;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 2px;
            position: relative;
        }

        /* Khusus Orientasi Landscape */
        .sheet.landscape {
            width: 297mm;
            min-height: 210mm;
            padding: 12mm 15mm 15mm 15mm;
        }

        /* Kop Surat Resmi */
        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding-bottom: 8px;
            margin-bottom: 2px;
        }

        .kop-logo {
            position: absolute;
            left: 0;
            top: 2px;
            width: 65px;
            height: auto;
        }

        .kop-teks {
            text-align: center;
            width: 100%;
            padding: 0 65px;
        }

        .kop-teks h4 {
            font-size: 12pt;
            font-weight: normal;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 1px;
        }

        .kop-teks h3 {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 1px;
        }

        .kop-teks h2 {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .kop-teks p {
            font-size: 9pt;
            margin-bottom: 1px;
            font-style: italic;
        }

        .kop-teks .kop-kontak {
            font-size: 8.5pt;
            font-style: normal;
        }

        .kop-garis-ganda {
            border-top: 2px solid #000000;
            border-bottom: 1px solid #000000;
            height: 4px;
            margin-bottom: 16px;
        }

        /* Judul Dokumen */
        .doc-title-container {
            text-align: center;
            margin-bottom: 14px;
        }

        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-subtitle {
            font-size: 10pt;
            margin-top: 3px;
            color: #334155;
        }

        /* Tabel Data */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-top: 10px;
            margin-bottom: 14px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #1e293b;
            padding: 5px 6px;
            vertical-align: middle;
        }

        table.data-table th {
            background-color: #f1f5f9 !important;
            font-weight: bold;
            text-align: center;
        }

        table.data-table tr:nth-child(even) td {
            background-color: #fafafa !important;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .nowrap { white-space: nowrap; }

        /* Status Badge */
        .badge {
            display: inline-block;
            padding: 1px 6px;
            font-size: 8.5pt;
            font-weight: bold;
            border-radius: 3px;
            border: 1px solid #cbd5e1;
        }
        .badge-hadir { background: #dcfce7 !important; color: #166534; border-color: #86efac; }
        .badge-izin { background: #fef3c7 !important; color: #92400e; border-color: #fde68a; }
        .badge-sakit { background: #dbeafe !important; color: #1e40af; border-color: #93c5fd; }
        .badge-alpha { background: #fee2e2 !important; color: #991b1b; border-color: #fca5a5; }

        /* Tanda Tangan */
        .signature-block {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
            font-size: 10.5pt;
        }

        .signature-box {
            width: 220px;
            text-align: center;
        }

        .signature-space {
            height: 65px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .signature-nip {
            font-size: 9.5pt;
            color: #334155;
            margin-top: 2px;
        }

        /* Aturan Media Print */
        @media print {
            .no-print, .print-toolbar {
                display: none !important;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
            }

            .sheet-container {
                padding: 0 !important;
            }

            .sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-height: auto !important;
            }

            .sheet.landscape {
                width: 100% !important;
                min-height: auto !important;
            }

            table.data-table th {
                background-color: #f1f5f9 !important;
            }

            tr {
                page-break-inside: avoid;
            }

            @page {
                size: A4 portrait;
                margin: 12mm 15mm 15mm 15mm;
            }

            @page landscape {
                size: A4 landscape;
                margin: 10mm 12mm 12mm 12mm;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <!-- Top Action Bar (Hanya tampil di layar) -->
    <div class="print-toolbar no-print">
        <div class="print-toolbar-left">
            <img src="{{ asset('images/logo-smkn1.png') }}" alt="Logo SMK">
            <div>
                <div class="print-toolbar-title">@yield('toolbar_title', 'Cetak Rekap Dokumen')</div>
                <div class="print-toolbar-hint">Pilih "Save as PDF" / "Simpan sebagai PDF" pada dialog cetak browser untuk menyimpan file PDF.</div>
            </div>
        </div>
        <div class="print-toolbar-actions">
            <button type="button" onclick="window.print()" class="btn-action btn-print">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak / Simpan PDF
            </button>
            <a href="javascript:history.back()" class="btn-action btn-back">
                Kembali
            </a>
        </div>
    </div>

    <!-- Lembar Dokumen -->
    <div class="sheet-container">
        <div class="sheet @yield('sheet_class')">
            <!-- Kop Surat Resmi SMK Negeri 1 Bantul -->
            <div class="kop-surat">
                <img src="{{ asset('images/logo-smkn1.png') }}" class="kop-logo" alt="Logo SMK Negeri 1 Bantul">
                <div class="kop-teks">
                    <h4>Pemerintah Daerah Daerah Istimewa Yogyakarta</h4>
                    <h3>Dinas Pendidikan, Pemuda, dan Olahraga</h3>
                    <h2>SMK Negeri 1 Bantul</h2>
                    <p>Jl. Sabdodadi, Keyongan, Sabdodadi, Kec. Bantul, Kabupaten Bantul, Daerah Istimewa Yogyakarta 55715</p>
                    <p class="kop-kontak">Telepon: (0274) 367156 · Laman: www.smkn1bantul.sch.id · Pos-el: smea_btl@yahoo.com</p>
                </div>
            </div>
            <div class="kop-garis-ganda"></div>

            <!-- Konten Dokumen -->
            @yield('content')
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === '1' || urlParams.get('auto') === '1') {
                setTimeout(function() {
                    window.print();
                }, 400);
            }
        });
    </script>
</body>
</html>

