<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Jurnal Mengajar</title>
    <style>
        @page { margin: 1.8cm; }
        body { color: #1d2735; font-family: "DejaVu Sans", sans-serif; font-size: 10pt; line-height: 1.45; }
        h1, h2 { margin: 0; text-align: center; }
        h1 { font-size: 16pt; letter-spacing: .04em; }
        h2 { margin-top: 5px; margin-bottom: 20px; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        td, th { border: 1px solid #aab4c3; padding: 7px 9px; vertical-align: top; }
        .identity td { border: 0; padding: 2px 4px; }
        .identity td:first-child { width: 145px; font-weight: bold; }
        .identity td:nth-child(2) { width: 12px; }
        .section-title { margin: 18px 0 6px; font-size: 10pt; font-weight: bold; }
        .label { width: 27%; background: #eef2f7; font-weight: bold; }
        .muted { color: #68778b; }
        .signatures { margin-top: 34px; }
        .signatures td { width: 50%; height: 100px; border: 0; text-align: center; vertical-align: bottom; }
    </style>
</head>
<body>
    <h1>JURNAL MENGAJAR</h1>
    <h2>JURNAL HARIAN GURU</h2>

    <table class="identity">
        <tr><td>Sekolah</td><td>:</td><td>{{ $school->name }}</td></tr>
        <tr><td>Guru</td><td>:</td><td>{{ $teacherName }}</td></tr>
        <tr><td>Mata pelajaran</td><td>:</td><td>{{ $session->subject?->name ?? '—' }}</td></tr>
        <tr><td>Kelas / semester</td><td>:</td><td>{{ $session->classGroup?->name ?? '—' }} / {{ $session->term?->name ?? '—' }}</td></tr>
        <tr><td>Pertemuan</td><td>:</td><td>Ke-{{ $session->meeting_number }} · {{ $session->date?->locale('id')->translatedFormat('l, d F Y') }}</td></tr>
        <tr><td>Alokasi waktu</td><td>:</td><td>{{ $journal->period_count }} JP</td></tr>
    </table>

    <table>
        @foreach([
            'Materi pokok' => $journal->topic,
            'Tujuan pembelajaran' => $journal->learning_objectives,
            'Topik / tema' => $journal->theme,
            'Kegiatan pendahuluan' => $journal->opening_activity,
            'Kegiatan inti' => $journal->main_activity,
            'Kegiatan penutup' => $journal->closing_activity,
            'Tindak lanjut' => $journal->follow_up,
            'Catatan tambahan' => $journal->notes,
        ] as $label => $value)
            @if($label !== 'Topik / tema' || filled($value))
                <tr><td class="label">{{ $label }}</td><td>{!! nl2br(e($value ?: '—')) !!}</td></tr>
            @endif
        @endforeach
    </table>

    <p class="section-title">Kehadiran</p>
    <table>
        <tr>
            <th>Jumlah siswa</th><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alpa</th>
        </tr>
        <tr>
            <td>{{ $studentCount }}</td><td>{{ $attendance['present'] }}</td><td>{{ $attendance['sick'] }}</td>
            <td>{{ $attendance['permitted'] }}</td><td>{{ $attendance['absent'] }}</td>
        </tr>
    </table>
    <table>
        <tr><td class="label">Keterangan presensi</td><td>{!! nl2br(e($journal->attendance_notes ?: '—')) !!}</td></tr>
        <tr><td class="label">Hasil pengamatan</td><td>{!! nl2br(e($journal->observations ?: '—')) !!}</td></tr>
        <tr><td class="label">Refleksi guru</td><td>{!! nl2br(e($journal->reflection ?: '—')) !!}</td></tr>
    </table>

    <table class="signatures">
        <tr>
            <td>Kepala Sekolah,<br><br><br><strong>{{ $school->principal_name ?: '________________________' }}</strong></td>
            <td>Guru Mata Pelajaran,<br><br><br><strong>{{ $teacherName }}</strong></td>
        </tr>
    </table>
    <p class="muted">Jurnal Mengajar {{ $session->subject?->name ?? '' }} — {{ $school->name }}</p>
</body>
</html>
