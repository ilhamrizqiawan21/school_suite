<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ledger Nilai Kelas {{ $ledger['class_group']['name'] ?? '' }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 12mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #0f172a; line-height: 1.35; }
        h1, h2, h3, p { margin: 0; }
        .letterhead { width: 100%; border-bottom: 2px solid #1e3a8a; padding-bottom: 6px; margin-bottom: 8px; }
        .letterhead td { border: none; padding: 0; vertical-align: middle; }
        .letterhead h2 { font-size: 12pt; text-transform: uppercase; color: #0f172a; }
        .letterhead p { font-size: 8pt; color: #334155; }
        .meta-table { width: 100%; margin-bottom: 8px; font-size: 8.5pt; }
        .meta-table td { padding: 1.5px 4px; vertical-align: top; }
        .meta-label { width: 14%; color: #475569; font-weight: bold; }
        .meta-value { width: 36%; color: #0f172a; }
        .ledger-table { width: 100%; border-collapse: collapse; border: 1px solid #475569; }
        .ledger-table th, .ledger-table td { border: 1px solid #64748b; padding: 4px 5px; font-size: 8pt; }
        .ledger-table th { background: #f1f5f9; color: #0f172a; font-weight: bold; text-align: center; }
        .ledger-table td.center { text-align: center; }
        .ledger-table td.num { text-align: right; font-variant-numeric: tabular-nums; }
        .ledger-table td.bold { font-weight: bold; background: #f8fafc; }
        .signatures { width: 100%; margin-top: 14px; page-break-inside: avoid; }
        .signatures td { width: 50%; vertical-align: top; font-size: 8.5pt; }
        .sig-name { margin-top: 38px; font-weight: bold; text-decoration: underline; }
        .sig-nip { font-size: 7.8pt; color: #475569; }
    </style>
</head>
<body>
@php
    $isSts = ($ledger['report_mode'] ?? 'sas') === 'sts';
    $modeLabel = $isSts ? 'Sumatif Tengah Semester (STS)' : 'Sumatif Akhir Semester (SAS)';
@endphp

<table class="letterhead">
    <tr>
        <td>
            <h2>{{ $ledger['school']['name'] ?? 'Sekolah' }}</h2>
            <p>{{ $ledger['school']['address'] ?? '' }} · NPSN {{ $ledger['school']['npsn'] ?? '—' }}</p>
        </td>
        <td style="text-align: right;">
            <strong style="font-size: 10pt; text-transform: uppercase; color: #1e3a8a;">LEDGER NILAI PESERTA DIDIK</strong><br>
            <span style="font-size: 8pt; color: #475569;">{{ $modeLabel }}</span>
        </td>
    </tr>
</table>

<table class="meta-table">
    <tr>
        <td class="meta-label">Kelas / Rombel</td>
        <td class="meta-value">: <strong>{{ $ledger['class_group']['name'] ?? '—' }}</strong> (Tingkat {{ $ledger['class_group']['grade_level'] ?? '—' }} · Fase {{ $ledger['class_group']['phase'] ?? '—' }})</td>
        <td class="meta-label">Tahun Pelajaran</td>
        <td class="meta-value">: {{ $ledger['term']['academic_year_name'] ?? '—' }}</td>
    </tr>
    <tr>
        <td class="meta-label">Wali Kelas</td>
        <td class="meta-value">: {{ $ledger['class_group']['homeroom_teacher_name'] ?? '—' }}</td>
        <td class="meta-label">Semester / Mode</td>
        <td class="meta-value">: {{ $ledger['term']['name'] ?? '—' }} ({{ strtoupper($ledger['report_mode'] ?? 'sas') }})</td>
    </tr>
</table>

<table class="ledger-table">
    <thead>
        <tr>
            <th rowspan="2" style="width: 24px;">No</th>
            <th rowspan="2" style="width: 72px;">NIS</th>
            <th rowspan="2" style="text-align: left; min-width: 130px;">Nama Peserta Didik</th>
            @if(count($ledger['subjects']) > 0)
                <th colspan="{{ count($ledger['subjects']) }}">Mata Pelajaran</th>
            @endif
            <th rowspan="2" style="width: 48px;">Jumlah</th>
            <th rowspan="2" style="width: 48px;">Rerata</th>
            <th rowspan="2" style="width: 42px;">Rank</th>
            <th colspan="3">Presensi</th>
        </tr>
        <tr>
            @foreach($ledger['subjects'] as $subject)
                <th title="{{ $subject['name'] }}">{{ $subject['code'] ?: \Illuminate\Support\Str::limit($subject['name'], 8, '') }}</th>
            @endforeach
            <th style="width: 24px;">S</th>
            <th style="width: 24px;">I</th>
            <th style="width: 24px;">A</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ledger['students'] as $index => $student)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td class="center">{{ $student['nis'] }}</td>
                <td><strong>{{ $student['name'] }}</strong></td>
                @foreach($ledger['subjects'] as $subject)
                    @php($val = $student['grades'][$subject['id']] ?? null)
                    <td class="center">{{ $val !== null ? rtrim(rtrim(number_format((float) $val, 1, ',', '.'), '0'), ',') : '—' }}</td>
                @endforeach
                <td class="num bold">{{ ($student['graded_count'] ?? 0) > 0 ? rtrim(rtrim(number_format((float) ($student['total_score'] ?? 0), 1, ',', '.'), '0'), ',') : '—' }}</td>
                <td class="num bold">{{ ($student['graded_count'] ?? 0) > 0 ? number_format((float) ($student['average_score'] ?? 0), 2, ',', '.') : '—' }}</td>
                <td class="center bold">{{ $student['rank'] ?? '—' }}</td>
                <td class="center">{{ $student['attendance']['sick'] ?? 0 }}</td>
                <td class="center">{{ $student['attendance']['permitted'] ?? 0 }}</td>
                <td class="center">{{ $student['attendance']['absent'] ?? 0 }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ 9 + count($ledger['subjects']) }}" class="center">Belum ada peserta didik aktif pada rombongan belajar ini.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="signatures">
    <tr>
        <td style="padding-left: 20px;">
            Mengetahui,<br>
            Kepala Sekolah,
            <div class="sig-name">{{ ($ledger['school']['principal_name'] ?? null) ?: '................................................' }}</div>
            @if(!empty($ledger['school']['principal_nip']))
                <div class="sig-nip">NIP. {{ $ledger['school']['principal_nip'] }}</div>
            @endif
        </td>
        <td style="padding-left: 120px;">
            {{ now()->locale('id')->translatedFormat('d F Y') }}<br>
            Wali Kelas {{ $ledger['class_group']['name'] ?? '' }},
            <div class="sig-name">{{ ($ledger['class_group']['homeroom_teacher_name'] ?? null) ?: '................................................' }}</div>
            @if(!empty($ledger['class_group']['homeroom_teacher_nip']))
                <div class="sig-nip">NIP. {{ $ledger['class_group']['homeroom_teacher_nip'] }}</div>
            @endif
        </td>
    </tr>
</table>
</body>
</html>
