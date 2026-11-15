<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Jadwal Pelajaran</title>
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
            font-size: 9pt;
            color: #0f172a;
            line-height: 1.35;
            margin: 0;
        }
        .header {
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .school-name {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 0;
        }
        .title {
            font-size: 10.5pt;
            font-weight: bold;
            margin: 2px 0 0;
            color: #1e293b;
        }
        .meta {
            margin: 4px 0 0;
            color: #475569;
            font-size: 8.5pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #64748b;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
            font-size: 8.5pt;
        }
        th {
            background: #e2e8f0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            letter-spacing: 0.03em;
            color: #0f172a;
        }
        .text-center {
            text-align: center;
        }
        .muted {
            color: #475569;
            font-size: 8pt;
        }
        .footer {
            margin-top: 12px;
            font-size: 8pt;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $dayMap = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
        ];
    @endphp
    <div class="header">
        <p class="school-name">{{ $school?->name ?? 'School Suite' }}</p>
        <p class="title">Daftar Jadwal Pelajaran</p>
        <p class="meta">
            Semester: {{ $term ? ($term->academicYear?->name . ' · ' . $term->name) : 'Semua Semester' }}
            @if($selectedClass)
                &bull; Rombel: {{ $selectedClass->name }}
            @endif
            @if($selectedTeacher)
                &bull; Guru: {{ $selectedTeacher->full_name }}
            @endif
            @if($selectedDay)
                &bull; Hari: {{ $dayMap[$selectedDay] ?? $selectedDay }}
            @endif
            @if($selectedStatus)
                &bull; Status: {{ $selectedStatus === 'active' ? 'Aktif' : 'Nonaktif' }}
            @endif
            @if($search)
                &bull; Kata Kunci: "{{ $search }}"
            @endif
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 34px;">No.</th>
                <th style="width: 68px;">Hari</th>
                <th class="text-center" style="width: 88px;">Jam</th>
                <th style="width: 85px;">Rombel</th>
                <th>Mata Pelajaran</th>
                <th style="width: 165px;">Guru Pengampu</th>
                <th style="width: 85px;">Ruang</th>
                <th class="text-center" style="width: 68px;">Status</th>
                <th style="width: 130px;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedules as $index => $schedule)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $dayMap[$schedule->day_of_week] ?? $schedule->day_of_week }}</strong></td>
                    <td class="text-center">{{ substr((string) $schedule->start_time, 0, 5) }} - {{ substr((string) $schedule->end_time, 0, 5) }}</td>
                    <td>{{ $schedule->teachingAssignment?->classGroup?->name ?? '-' }}</td>
                    <td>
                        <strong>{{ $schedule->teachingAssignment?->subject?->name ?? '-' }}</strong>
                        @if($schedule->teachingAssignment?->subject?->code)
                            <div class="muted">{{ $schedule->teachingAssignment->subject->code }}</div>
                        @endif
                    </td>
                    <td>{{ $schedule->teachingAssignment?->teacher?->full_name ?? '-' }}</td>
                    <td>{{ $schedule->room ?: '-' }}</td>
                    <td class="text-center">{{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td>{{ $schedule->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 14px;">Belum ada jadwal pelajaran yang sesuai filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>
