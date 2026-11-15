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
        h1 {
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        p.meta {
            margin: 3px 0 0;
            color: #475569;
            font-size: 9pt;
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
        .footer {
            margin-top: 12px;
            font-size: 8pt;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Manajemen Data Siswa — {{ $school?->name ?? 'School Suite' }}</h1>
        <p class="meta">Semester Aktif: {{ $activeTerm ? $activeTerm->academicYear->name.' · '.$activeTerm->name : 'Belum diatur' }} &bull; Total: {{ count($students) }} siswa</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 36px;">No.</th>
                <th style="width: 100px;">NIS</th>
                <th style="width: 115px;">NISN</th>
                <th>Nama Lengkap</th>
                <th class="text-center" style="width: 50px;">L/P</th>
                <th style="width: 130px;">Rombel</th>
                <th style="width: 180px;">Wali / Orang Tua</th>
                <th class="text-center" style="width: 85px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $student->nis ?: '-' }}</td>
                    <td>{{ $student->nisn ?: '-' }}</td>
                    <td><strong>{{ $student->full_name }}</strong></td>
                    <td class="text-center">{{ $student->gender ?: '-' }}</td>
                    <td>{{ $student->currentEnrollment?->classGroup?->name ?? '-' }}</td>
                    <td>{{ $student->guardian_name ?: '-' }}</td>
                    <td class="text-center">{{ strtoupper($student->status ?: '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 14px;">Belum ada data siswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
