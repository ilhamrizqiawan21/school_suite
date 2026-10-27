<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Data Siswa</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; color: #17233a; font-size: 10px; }
        h1 { margin: 0 0 4px; color: #183b70; font-size: 20px; }
        .school { margin: 0 0 3px; color: #334155; font-size: 12px; font-weight: bold; }
        .meta { margin: 0 0 16px; color: #64748b; font-size: 9px; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { padding: 7px 8px; border: 1px solid #dbe3ee; text-align: left; }
        th { background: #edf3fb; color: #344a68; font-size: 8px; text-transform: uppercase; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .number { width: 34px; text-align: center; }
        .nis { width: 95px; }
        .gender, .status { width: 76px; }
        .class { width: 100px; }
        .empty { padding: 24px; color: #64748b; text-align: center; }
        .footer { margin-top: 10px; color: #64748b; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <p class="school">{{ $school->name }}</p>
    <h1>Daftar Data Siswa</h1>
    <p class="meta">
        Periode: {{ $term?->name ?? 'Tidak ada semester aktif' }}
        @if($filters['search']) · Pencarian: “{{ $filters['search'] }}” @endif
        @if($filters['status']) · Status: {{ ['active' => 'Aktif', 'graduated' => 'Lulus', 'transferred' => 'Pindah', 'dropped_out' => 'Keluar'][$filters['status']] ?? $filters['status'] }} @endif
        · {{ $students->count() }} siswa · Dicetak {{ $generatedAt->format('d/m/Y H:i') }} WIB
    </p>
    <table>
        <thead>
            <tr><th class="number">No.</th><th class="nis">NIS</th><th class="nis">NISN</th><th>Nama lengkap</th><th class="gender">L/P</th><th class="class">Rombel aktif</th><th class="status">Status</th></tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
                <tr>
                    <td class="number">{{ $index + 1 }}</td>
                    <td>{{ $student->nis }}</td>
                    <td>{{ $student->nisn ?: '—' }}</td>
                    <td>{{ $student->name }}</td>
                    <td>{{ $student->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                    <td>{{ $student->enrollments->first()?->classGroup?->name ?? 'Belum ada rombel' }}</td>
                    <td>{{ ['active' => 'Aktif', 'graduated' => 'Lulus', 'transferred' => 'Pindah', 'dropped_out' => 'Keluar'][$student->status] ?? $student->status }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Tidak ada data siswa yang cocok dengan filter.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="footer">Dokumen internal sekolah · {{ $school->name }}</p>
</body>
</html>
