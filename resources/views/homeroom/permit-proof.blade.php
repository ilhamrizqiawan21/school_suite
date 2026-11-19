<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Izin Siswa {{ $ticket['ticket_code'] ?? $dispensation->id }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 20mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #172033; line-height: 1.45; margin: 0; }
        .head { border-bottom: 2px solid #172033; padding-bottom: 10px; margin-bottom: 16px; display: table; width: 100%; }
        .head-logo { display: table-cell; width: 64px; vertical-align: middle; }
        .head-logo img { width: 54px; height: 54px; object-fit: contain; }
        .head-title { display: table-cell; vertical-align: middle; }
        .head-title h1 { margin: 0; font-size: 14pt; text-transform: uppercase; }
        .head-title p { margin: 2px 0 0; font-size: 8.5pt; color: #475569; }
        .banner { background: #eef4ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.meta td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.meta td.label { width: 165px; font-weight: 700; color: #334155; }
    </style>
</head>
<body>
    <div class="head">
        @if(!empty($school['logo_data_uri']))
            <div class="head-logo"><img src="{{ $school['logo_data_uri'] }}" alt="Logo"></div>
        @endif
        <div class="head-title">
            <h1>{{ $school['name'] ?? 'Sekolah' }}</h1>
            <p>{{ $school['address'] ?? '' }}</p>
            <p><strong>Bukti Pengajuan &amp; Keputusan Izin Siswa</strong></p>
        </div>
    </div>

    <div class="banner">
        <div>Kode Tiket: <strong>{{ $ticket['ticket_code'] ?? ('IZN-'.$dispensation->id) }}</strong></div>
        <div>Status Keputusan: <strong>{{ strtoupper($dispensation->status->label()) }}</strong></div>
    </div>

    <table class="meta">
        <tr><td class="label">Nama Siswa</td><td>{{ $student['name'] ?? '-' }} (NIS: {{ $student['nis'] ?? '-' }})</td></tr>
        <tr><td class="label">Kelas / Rombel</td><td>{{ $student['class_group_name'] ?? '-' }}</td></tr>
        <tr><td class="label">Kategori Izin</td><td>{{ $dispensation->type->label() }}</td></tr>
        <tr><td class="label">Rentang Tanggal</td><td>{{ $dispensation->start_date->format('d-m-Y') }} s.d. {{ $dispensation->end_date->format('d-m-Y') }}</td></tr>
        <tr><td class="label">Judul / Keperluan</td><td>{{ $dispensation->title }}</td></tr>
        <tr><td class="label">Alasan</td><td>{{ $dispensation->reason }}</td></tr>
        <tr><td class="label">Kontak Pemohon</td><td>{{ $dispensation->contact_name ?: '-' }} ({{ $dispensation->contact_phone ?: '-' }})</td></tr>
        <tr><td class="label">Catatan Keputusan</td><td>{{ $dispensation->decision_note ?: '-' }}</td></tr>
    </table>
</body>
</html>
