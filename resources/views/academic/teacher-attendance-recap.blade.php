<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Kehadiran Guru {{ $period }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 16mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9.5pt;
            color: #0f172a;
            line-height: 1.4;
            margin: 0;
        }
        .header {
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        h1 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 0 0 3px;
        }
        .subtitle {
            color: #475569;
            font-size: 9.5pt;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #64748b;
            padding: 6px 8px;
            text-align: left;
            font-size: 9pt;
        }
        th {
            background: #e2e8f0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            letter-spacing: 0.03em;
            color: #0f172a;
        }
        .num {
            text-align: center;
            width: 58px;
        }
        .no-col {
            text-align: center;
            width: 36px;
        }
        .footer {
            margin-top: 16px;
            font-size: 8pt;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rekapitulasi Kehadiran Guru</h1>
        <p class="subtitle">Periode Laporan: {{ $period }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="no-col">No.</th>
                <th>Nama Guru</th>
                <th style="width: 140px;">NIP / NUPTK</th>
                <th class="num">Hadir</th>
                <th class="num">Izin</th>
                <th class="num">Sakit</th>
                <th class="num">Alpa</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                @php
                    $total = (int) ($row['present'] ?? 0) + (int) ($row['permitted'] ?? 0) + (int) ($row['sick'] ?? 0) + (int) ($row['absent'] ?? 0);
                @endphp
                <tr>
                    <td class="no-col">{{ $index + 1 }}</td>
                    <td><strong>{{ $row['name'] }}</strong></td>
                    <td>{{ $row['nip'] ?: '-' }}</td>
                    <td class="num">{{ $row['present'] }}</td>
                    <td class="num">{{ $row['permitted'] }}</td>
                    <td class="num">{{ $row['sick'] }}</td>
                    <td class="num">{{ $row['absent'] }}</td>
                    <td class="num"><strong>{{ $total }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 14px;">Belum ada data kehadiran pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
