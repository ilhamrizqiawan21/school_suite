<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Siswa</title>
    <style>
        @page { margin: 24px 28px; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { color: #172d4c; font-size: 18px; margin: 0 0 4px; }
        p { color: #64748b; margin: 0 0 14px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #172d4c; color: #fff; font-size: 9px; padding: 8px 7px; text-align: left; }
        td { border-bottom: 1px solid #dbe3eb; padding: 7px; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
        .muted { color: #64748b; }
        .footer { color: #94a3b8; font-size: 8px; margin-top: 12px; }
    </style>
</head>
<body>
    <h1>Manajemen Data Siswa</h1>
    <p>{{ $school?->name ?? 'School Suite' }} · {{ $activeTerm?->academicYear?->name }} {{ $activeTerm?->name }} · {{ $students->count() }} data</p>
    <table>
        <thead><tr><th>No.</th><th>NIS</th><th>NISN</th><th>Nama Lengkap</th><th>L/P</th><th>Rombel Aktif</th><th>Orang Tua</th><th>Kontak</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($students as $student)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $student->nis }}</td>
                    <td>{{ $student->nisn ?: '-' }}</td>
                    <td><strong>{{ $student->name }}</strong></td>
                    <td>{{ $student->gender }}</td>
                    <td>{{ $student->enrollments->first()?->classGroup?->name ?: '-' }}</td>
                    <td>{{ $student->parent_name ?: '-' }}</td>
                    <td>{{ $student->parent_phone ?: '-' }}</td>
                    <td>{{ $student->status }}</td>
                </tr>
            @empty
                <tr><td colspan="9">Tidak ada data siswa sesuai filter.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Dibuat oleh School Suite pada {{ now()->format('d-m-Y H:i') }}. Maksimal 500 data per ekspor.</div>
</body>
</html>
