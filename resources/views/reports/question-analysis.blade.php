<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 15mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #0f172a;
            font-size: 9pt;
            line-height: 1.35;
            margin: 0;
        }
        .header {
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .title {
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .subtitle {
            margin: 3px 0 0;
            font-size: 9.5pt;
            color: #475569;
        }
        .summary-box {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 8px 10px;
            margin-bottom: 10px;
        }
        .summary-title {
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #334155;
            margin-bottom: 4px;
        }
        .summary-text {
            margin: 0;
            font-size: 9pt;
            color: #1e293b;
        }
        .warning-list {
            margin: 4px 0 0 16px;
            padding: 0;
            color: #b45309;
            font-size: 8.5pt;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.data-table th,
        table.data-table td {
            border: 1px solid #64748b;
            padding: 5px 7px;
            vertical-align: top;
            text-align: left;
            font-size: 8.5pt;
        }
        table.data-table th {
            background: #e2e8f0;
            color: #0f172a;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            letter-spacing: 0.03em;
        }
        .text-center {
            text-align: center !important;
        }
        .text-right {
            text-align: right !important;
        }
        .guide-box {
            margin-top: 12px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 8pt;
            color: #475569;
        }
        .guide-item {
            margin-bottom: 3px;
        }
        .signature {
            margin-top: 18px;
            width: 100%;
        }
        .signature td {
            border: none;
            font-size: 9pt;
            vertical-align: top;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Laporan Analisis Butir Soal — {{ $report['name'] }}</h1>
        <p class="subtitle">Kelas / Rombel: {{ $report['class'] ?? '-' }} &bull; Guru Pengampu: {{ $teacher }}</p>
    </div>

    <div class="summary-box">
        <div class="summary-title">Ringkasan Analisis</div>
        <p class="summary-text">{{ $report['summary'] }}</p>
        @if(!empty($report['warnings']))
            <ul class="warning-list">
                @foreach($report['warnings'] as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 38px;">No.</th>
                <th>Pokok Soal</th>
                <th class="text-center" style="width: 48px;">Kunci</th>
                <th style="width: 120px;">Tingkat Kesukaran</th>
                <th style="width: 120px;">Daya Pembeda</th>
                <th style="width: 150px;">Efektivitas Pengecoh</th>
                <th style="width: 130px;">Rekomendasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td class="text-center">{{ $row['number'] }}</td>
                    <td>{{ $row['prompt'] }}</td>
                    <td class="text-center"><strong>{{ $row['answer_key'] }}</strong></td>
                    <td>{{ $row['difficulty_label'] }} ({{ $row['difficulty_code'] }})</td>
                    <td>{{ $row['discrimination_label'] }} ({{ $row['discrimination_code'] }})</td>
                    <td>{{ $row['distractor_note'] }}</td>
                    <td><strong>{{ $row['recommendation'] }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Belum ada data butir soal untuk dianalisis.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($report['guide']))
        <div class="guide-box">
            <strong>Keterangan Interpretasi:</strong>
            @foreach($report['guide'] as $guide)
                <div class="guide-item">&bull; <strong>{{ $guide['title'] }}:</strong> {{ $guide['meaning'] }} ({{ $guide['action'] }})</div>
            @endforeach
        </div>
    @endif

    <table class="signature">
        <tr>
            <td style="width: 65%;"></td>
            <td style="width: 35%; text-align: center;">
                <div>Dicetak pada {{ now()->format('d/m/Y H:i') }}</div>
                <div style="margin-top: 2px;">Guru Mata Pelajaran,</div>
                <div style="height: 46px;"></div>
                <div style="font-weight: bold; text-decoration: underline;">{{ $teacher }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
