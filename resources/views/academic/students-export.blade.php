<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Siswa</title>
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
        .letterhead {
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
        .doc-title {
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
        .footer {
            margin-top: 12px;
            font-size: 8pt;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="letterhead">
        <p class="school-name">{{ $school?->name ?? 'School Suite' }}</p>
        <p class="doc-title">Daftar Data Peserta Didik</p>
        <p class="meta">
            Tahun Ajaran Aktif: {{ $term?->AcademicYear?->name ?? '-' }} &bull; Semester: {{ $term?->name ?? '-' }}
            @if(!empty($filters['status']))
                &bull; Status: {{ strtoupper($filters['status']) }}
            @endif
            @if(!empty($filters['search']))
                &bull; Pencarian: "{{ $filters['search'] }}"
            @endif
            &bull; Total: {{ count($students) }} siswa
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 34px;">No.</th>
                <th style="width: 90px;">NIS</th>
                <th style="width: 105px;">NISN</th>
                <th>Nama Lengkap</th>
                <th class="text-center" style="width: 44px;">L/P</th>
                <th style="width: 110px;">Rombel Aktif</th>
                <th class="text-center" style="width: 80px;">Status</th>
                <th style="width: 160px;">Nama Wali</th>
                <th style="width: 115px;">Kontak Wali</th>
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
                    <td>{{ $student->currentEnrollment?->classGroup?->name ?? 'Belum ditempatkan' }}</td>
                    <td class="text-center">{{ strtoupper($student->status ?: '-') }}</td>
                    <td>{{ $student->guardian_name ?: '-' }}</td>
                    <td>{{ $student->guardian_phone ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 14px;">Tidak ada data siswa yang sesuai filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>
