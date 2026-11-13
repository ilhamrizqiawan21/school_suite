<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Jadwal Pelajaran — {{ $school->name }}</title>
    <style>
        @page { margin: 26px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #17233a; font-size: 9.5px; line-height: 1.4; }
        .header { margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #183b70; }
        .school { margin: 0 0 2px; color: #334155; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.03em; }
        h1 { margin: 0 0 4px; color: #183b70; font-size: 18px; }
        .meta { margin: 0; color: #475569; font-size: 9px; line-height: 1.5; }
        .day-section { margin-top: 12px; page-break-inside: avoid; }
        .day-title { margin: 0 0 5px; padding: 5px 8px; background: #183b70; color: #ffffff; font-size: 10px; font-weight: bold; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { padding: 6px 8px; border: 1px solid #cbd5e1; text-align: left; vertical-align: middle; }
        th { background: #edf3fb; color: #1e3a5f; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.03em; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .col-no { width: 32px; text-align: center; }
        .col-day { width: 78px; font-weight: bold; }
        .col-time { width: 92px; font-weight: bold; color: #1e3a5f; }
        .col-code { width: 72px; font-family: DejaVu Sans Mono, monospace; font-size: 8.5px; color: #475569; }
        .col-class { width: 88px; font-weight: bold; }
        .col-room { width: 95px; }
        .col-status { width: 68px; text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 7.5px; font-weight: bold; text-transform: uppercase; }
        .badge-published { background: #dcfce7; color: #166534; }
        .badge-draft { background: #fef3c7; color: #92400e; }
        .empty { padding: 22px; color: #64748b; text-align: center; font-style: italic; }
        .footer { margin-top: 12px; padding-top: 6px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    @php
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
    @endphp

    <div class="header">
        <p class="school">{{ $school->name }}</p>
        <h1>Daftar Jadwal Pelajaran</h1>
        <p class="meta">
            Periode: <strong>{{ $term?->academicYear?->name ? $term->academicYear->name . ' — ' : '' }}{{ $term?->name ?? 'Belum ada semester aktif' }}</strong>
            @if($selectedClass) · Rombel: <strong>Kelas {{ $selectedClass->name }}</strong> @endif
            @if($selectedTeacher) · Guru: <strong>{{ $selectedTeacher->name }}</strong> @endif
            @if($selectedDay && isset($dayNames[$selectedDay])) · Hari: <strong>{{ $dayNames[$selectedDay] }}</strong> @endif
            @if($selectedStatus) · Status: <strong>{{ $selectedStatus === 'published' ? 'Terbit' : 'Draf' }}</strong> @endif
            @if($search) · Pencarian: “{{ $search }}” @endif
            · Total: <strong>{{ $schedules->count() }} sesi</strong>
            · Dicetak {{ $generatedAt->format('d/m/Y H:i') }} WIB
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th class="col-day">Hari</th>
                <th class="col-time">Waktu</th>
                <th class="col-code">Kode</th>
                <th>Mata Pelajaran</th>
                <th class="col-class">Rombel</th>
                <th>Guru Pengajar</th>
                <th class="col-room">Ruang / Lab</th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedules as $index => $slot)
                <tr>
                    <td class="col-no">{{ $index + 1 }}</td>
                    <td class="col-day">{{ $dayNames[$slot->day_of_week] ?? 'Hari ' . $slot->day_of_week }}</td>
                    <td class="col-time">{{ substr((string) $slot->start_time, 0, 5) }} – {{ substr((string) $slot->end_time, 0, 5) }}</td>
                    <td class="col-code">{{ $slot->subject?->code ?? '—' }}</td>
                    <td><strong>{{ $slot->subject?->name ?? '—' }}</strong></td>
                    <td class="col-class">Kelas {{ $slot->classGroup?->name ?? '—' }}</td>
                    <td>{{ $slot->teacher?->name ?? '—' }}</td>
                    <td class="col-room">{{ $slot->room ?: '—' }}</td>
                    <td class="col-status">
                        <span class="badge {{ $slot->status === 'published' ? 'badge-published' : 'badge-draft' }}">
                            {{ $slot->status === 'published' ? 'Terbit' : 'Draf' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">Tidak ada jadwal pelajaran yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Dokumen resmi internal · {{ $school->name }} · School Suite</p>
</body>
</html>
