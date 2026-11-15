<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Jurnal Mengajar</title>
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
        .school-name {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 0;
        }
        .doc-subtitle {
            font-size: 10pt;
            font-weight: bold;
            color: #334155;
            margin: 2px 0 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
            margin: 14px 0 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #64748b;
            padding: 6px 8px;
            vertical-align: top;
            text-align: left;
            font-size: 9pt;
        }
        th {
            background: #e2e8f0;
            width: 30%;
            font-weight: bold;
            color: #0f172a;
        }
        .attendance-table th {
            width: 25%;
            text-align: center;
            text-transform: uppercase;
            font-size: 8pt;
        }
        .attendance-table td {
            text-align: center;
            font-weight: bold;
            font-size: 10.5pt;
        }
        .signature {
            margin-top: 28px;
            width: 100%;
        }
        .signature td {
            border: none;
            width: 50%;
            text-align: center;
            padding-top: 8px;
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="school-name">{{ $school?->name ?? 'School Suite' }}</p>
        <p class="doc-subtitle">Jurnal Mengajar &bull; Jurnal Harian Guru</p>
    </div>

    <div class="section-title">A. Identitas Sesi Pembelajaran</div>
    <table>
        <tr><th>Guru Pengampu</th><td><strong>{{ $teacherName }}</strong></td></tr>
        <tr><th>Tanggal</th><td>{{ $session->session_date?->format('d/m/Y') }}</td></tr>
        <tr><th>Rombel</th><td>{{ $session->teachingAssignment?->classGroup?->name }}</td></tr>
        <tr><th>Mata Pelajaran</th><td>{{ $session->teachingAssignment?->subject?->name }}</td></tr>
        <tr><th>Pertemuan Ke-</th><td>{{ $journal->meeting_number ?? $session->meeting_number }}</td></tr>
        <tr><th>Pokok Bahasan / Topik</th><td>{{ $journal->topic ?? $session->topic }}</td></tr>
    </table>

    <div class="section-title">B. Pelaksanaan &amp; Evaluasi Pembelajaran</div>
    <table>
        <tr><th>Metode Pembelajaran</th><td>{{ $journal->teaching_method ?: '-' }}</td></tr>
        <tr><th>Ringkasan Materi</th><td>{{ $journal->material_summary }}</td></tr>
        <tr><th>Capaian Pembelajaran</th><td>{{ $journal->student_achievement ?: '-' }}</td></tr>
        <tr><th>Hambatan / Kendala</th><td>{{ $journal->obstacles ?: '-' }}</td></tr>
        <tr><th>Tindak Lanjut</th><td>{{ $journal->follow_up ?: '-' }}</td></tr>
        <tr><th>Catatan Kejadian Khusus</th><td>{{ $journal->incident_notes ?: '-' }}</td></tr>
    </table>

    <div class="section-title">C. Rekapitulasi Kehadiran Peserta Didik ({{ $studentCount }} Siswa)</div>
    <table class="attendance-table">
        <thead>
            <tr>
                <th>Hadir</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpa</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $attendance['present'] ?? 0 }}</td>
                <td>{{ $attendance['sick'] ?? 0 }}</td>
                <td>{{ $attendance['permitted'] ?? 0 }}</td>
                <td>{{ $attendance['absent'] ?? 0 }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td>
                Mengetahui,<br>Kepala Sekolah
                <div style="height: 52px;"></div>
                <strong>{{ $school?->principal_name ?? '________________________' }}</strong>
            </td>
            <td>
                {{ $session->session_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}<br>Guru Mata Pelajaran
                <div style="height: 52px;"></div>
                <strong>{{ $teacherName }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
