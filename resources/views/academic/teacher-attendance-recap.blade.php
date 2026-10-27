<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi Guru {{ $period }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; }
        h1 { margin: 0 0 5px; font-size: 20px; color: #17376d; }
        p { margin: 0 0 18px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 9px 10px; border: 1px solid #dbe3ee; text-align: left; }
        th { background: #eff4fb; color: #42536d; font-size: 9px; text-transform: uppercase; }
        td.number, th.number { width: 10%; text-align: center; }
        tfoot td { background: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Rekap Absensi Guru</h1>
    <p>Periode {{ $period }}</p>
    <table>
        <thead><tr><th>Nama guru</th><th>NIP</th><th class="number">Hadir</th><th class="number">Izin</th><th class="number">Sakit</th><th class="number">Alpha</th></tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr><td>{{ $row['name'] }}</td><td>{{ $row['nip'] ?: '—' }}</td><td class="number">{{ $row['present'] }}</td><td class="number">{{ $row['permitted'] }}</td><td class="number">{{ $row['sick'] }}</td><td class="number">{{ $row['absent'] }}</td></tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#64748b">Belum ada catatan absensi pada bulan ini.</td></tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
            <tfoot><tr><td colspan="2">Total</td><td class="number">{{ $rows->sum('present') }}</td><td class="number">{{ $rows->sum('permitted') }}</td><td class="number">{{ $rows->sum('sick') }}</td><td class="number">{{ $rows->sum('absent') }}</td></tr></tfoot>
        @endif
    </table>
</body>
</html>
