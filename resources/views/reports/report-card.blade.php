<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rapor {{ $report['student']['name'] }}</title>
    <style>
        @page { margin: 34px 38px 58px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #202a33; line-height: 1.4; }
        h1,h2,h3,p { margin: 0; }
        .letterhead { text-align: center; border-bottom: 2px solid #263a48; padding-bottom: 12px; }
        .letterhead h2 { font-size: 17px; }
        .letterhead p { font-size: 9px; color: #526777; }
        .logo { width: 54px; height: 54px; float: left; }
        .identity { width: 100%; border-bottom: 3px double #70818c; padding: 8px 0; }
        .identity td { padding: 3px 0; vertical-align: top; }
        .identity .label { width: 16%; color: #526777; }
        .identity .value { width: 36%; }
        h1 { text-align: center; font-size: 14px; margin: 21px 0 18px; }
        h3 { font-size: 10px; margin: 0 0 5px; }
        .draft { text-align: center; color: #855b16; margin: -12px 0 12px; font-size: 9px; }
        .data { width: 100%; border-collapse: collapse; margin-bottom: 13px; }
        .data th { background: #f1f4f6; font-size: 8px; text-align: left; }
        .data td,.data th { padding: 6px 7px; border: 1px solid #7b8993; }
        .data td { vertical-align: middle; }
        .data .center { text-align: center; }
        .data .subject { width: 28%; }
        .data .score { width: 11%; text-align: center; }
        .competency { font-size: 8px; line-height: 1.55; }
        .total td { font-weight: bold; background: #f8fafb; }
        .number { width: 4%; text-align: center; }
        tr, .keep-together { page-break-inside: avoid; }
        thead { display: table-header-group; }
        .supplement { page-break-before: always; }
        .note,.parent-response { padding: 9px; border: 1px solid #7b8993; min-height: 39px; margin-bottom: 13px; white-space: pre-line; }
        .parent-response { height: 50px; }
        .signatures { width: 100%; margin-top: 20px; text-align: left; page-break-inside: avoid; }
        .signatures td { width: 50%; padding: 0 20px; vertical-align: top; font-size: 9px; }
        .signature-name { margin-top: 48px; font-weight: bold; }
        .principal { text-align: center; padding-top: 24px!important; }
        .footer { position: fixed; bottom: -38px; left: 0; right: 0; font-size: 7px; color: #526777; }
        .footer-top { width: 100%; }
        .page-number:before { content: counter(page); }
        .checksum { font-family: DejaVu Sans Mono, monospace; font-size: 6px; word-wrap: break-word; margin-top: 3px; }
    </style>
</head>
<body>
    <footer class="footer"><table class="footer-top"><tr><td>{{ $report['class_group']['name'] }} - {{ $report['student']['name'] }}</td><td style="text-align:right">Halaman <span class="page-number"></span></td></tr></table><div class="checksum">Pemeriksaan data SHA-256: {{ $report['checksum'] }}</div></footer>
    <header class="letterhead">
        @if($report['school']['logo_data_uri'])<img class="logo" src="{{ $report['school']['logo_data_uri'] }}" alt="Logo sekolah">@endif
        <h2>{{ $report['school']['name'] }}</h2><p>{{ $report['school']['address'] }}</p><p>NPSN {{ $report['school']['npsn'] }}</p>
    </header>
    <table class="identity"><tr><td class="label">Nama peserta didik</td><td class="value">: <strong>{{ $report['student']['name'] }}</strong></td><td class="label">Kelas</td><td>: {{ $report['class_group']['name'] }}</td></tr><tr><td class="label">NIS / NISN</td><td>: {{ $report['student']['nis'] }} / {{ $report['student']['nisn'] ?: '-' }}</td><td class="label">Semester</td><td>: {{ $report['term']['name'] }}</td></tr><tr><td class="label">Sekolah</td><td>: {{ $report['school']['name'] }}</td><td class="label">Tahun pelajaran</td><td>: {{ $report['term']['academic_year_name'] }}</td></tr></table>
    <h1>Capaian hasil belajar</h1>
    @if($report['status'] !== 'published')<p class="draft">Draf - belum diterbitkan</p>@endif
    <table class="data"><thead><tr><th class="number">No.</th><th class="subject">Mata pelajaran</th><th class="score">Nilai akhir</th><th>Capaian kompetensi</th></tr></thead><tbody>
    @forelse($report['grades'] as $index => $grade)
        <tr><td class="number">{{ $index + 1 }}</td><td>{{ $grade['subject_name'] }}</td><td class="score">{{ $grade['final_score'] ?? '-' }}</td><td class="competency">{{ $grade['competency_description'] ?: 'Belum ada deskripsi capaian kompetensi.' }}</td></tr>
    @empty<tr><td colspan="4">Belum ada nilai akademik pada rapor ini.</td></tr>@endforelse
    @if(count($report['grades']))<tr class="total"><td colspan="2">Jumlah nilai</td><td class="score">{{ collect($report['grades'])->whereNotNull('final_score')->isEmpty() ? '-' : collect($report['grades'])->sum('final_score') }}</td><td></td></tr>@endif
    </tbody></table>
    <div class="supplement">
        <div class="keep-together"><h3>Ekstrakurikuler</h3><table class="data"><thead><tr><th class="number">No.</th><th>Kegiatan ekstrakurikuler</th><th>Nilai</th><th>Keterangan</th></tr></thead><tbody>@forelse($report['extracurricular'] as $index => $activity)<tr><td class="number">{{ $index + 1 }}</td><td>{{ $activity['name'] }}</td><td>{{ $activity['predicate'] ?? '-' }}</td><td>{{ $activity['description'] ?? '-' }}</td></tr>@empty<tr><td colspan="4">Belum ada catatan ekstrakurikuler.</td></tr>@endforelse</tbody></table></div>
        <div class="keep-together"><h3>Prestasi</h3><table class="data"><thead><tr><th class="number">No.</th><th>Jenis prestasi</th><th>Keterangan</th></tr></thead><tbody>@forelse($report['notes']['achievements'] ?? [] as $index => $achievement)<tr><td class="number">{{ $index + 1 }}</td><td>{{ $achievement['title'] }}</td><td>{{ implode(' - ', array_filter([$achievement['level'] ?? null, $achievement['rank'] ?? null])) ?: '-' }}</td></tr>@empty<tr><td colspan="3">Belum ada catatan prestasi.</td></tr>@endforelse</tbody></table></div>
        <div class="keep-together"><h3>Ketidakhadiran</h3><table class="data"><tbody>@foreach(['sick' => 'Sakit', 'permitted' => 'Izin', 'absent' => 'Tanpa keterangan'] as $key => $label)<tr><td style="width:40%">{{ $label }}</td><td class="center" style="width:15%">{{ $report['attendance'][$key] ?? 0 }}</td><td>hari</td></tr>@endforeach</tbody></table></div>
        <div class="keep-together"><h3>Catatan wali kelas</h3><p class="note">{{ ($report['notes']['homeroom_notes'] ?? '') ?: 'Belum ada catatan wali kelas.' }}</p></div>
        <div class="keep-together"><h3>Tanggapan orang tua / wali</h3><div class="parent-response"></div></div>
        <table class="signatures"><tr><td>Orang tua / wali<div class="signature-name">( ................................ )</div></td><td>{{ $report['published_at'] ?? 'Tanggal: ........................' }}<br>Wali kelas<div class="signature-name">{{ $report['academic']['homeroom_teacher'] }}</div></td></tr><tr><td colspan="2" class="principal">Mengetahui,<br>Kepala sekolah<div class="signature-name">{{ $report['school']['principal_name'] ?: '( ................................ )' }}</div></td></tr></table>
    </div>
</body>
</html>
