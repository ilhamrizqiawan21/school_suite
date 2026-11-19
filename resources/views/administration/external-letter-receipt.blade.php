<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tanda Terima Surat Dinas {{ $ticket['ticket_code'] }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 20mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #172033; line-height: 1.45; margin: 0; }
        .head { border-bottom: 2px solid #172033; padding-bottom: 10px; margin-bottom: 16px; display: table; width: 100%; }
        .head-logo { display: table-cell; width: 64px; vertical-align: middle; }
        .head-logo img { width: 54px; height: 54px; object-fit: contain; }
        .head-title { display: table-cell; vertical-align: middle; }
        .head-title h1 { margin: 0; font-size: 14pt; text-transform: uppercase; }
        .head-title p { margin: 2px 0 0; font-size: 8.5pt; color: #475569; }
        .banner { background: #eef4ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px 14px; margin-bottom: 16px; }
        .banner-row { font-size: 13pt; font-weight: 700; color: #1e3a8a; margin: 4px 0; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.meta td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.meta td.label { width: 175px; font-weight: 700; color: #334155; }
        .qr-box { margin-top: 14px; border-top: 1px dashed #cbd5e1; padding-top: 12px; display: table; width: 100%; }
        .qr-cell { display: table-cell; width: 110px; vertical-align: top; }
        .qr-cell img { width: 96px; height: 96px; }
        .qr-note { display: table-cell; vertical-align: top; font-size: 8.5pt; color: #475569; }
    </style>
</head>
<body>
    <div class="head">
        @if(!empty($school['logo_data_uri']))
            <div class="head-logo"><img src="{{ $school['logo_data_uri'] }}" alt="Logo"></div>
        @endif
        <div class="head-title">
            <h1>{{ $school['name'] ?? 'Sekolah' }}</h1>
            <p>{{ $school['address'] ?? '' }} {{ !empty($school['phone']) ? '· Telp: '.$school['phone'] : '' }}</p>
            <p><strong>Tanda Terima Resmi Surat Dinas Eksternal</strong></p>
        </div>
    </div>

    <div class="banner">
        <div>Nomor Tiket: <strong class="banner-row">{{ $ticket['ticket_code'] }}</strong></div>
        <div>Nomor Agenda TU: <strong>{{ $record['agenda_number'] ?? '-' }}</strong></div>
    </div>

    <table class="meta">
        <tr><td class="label">Instansi Pengirim</td><td>{{ $record['agency_name'] }}</td></tr>
        <tr><td class="label">Narahubung</td><td>{{ $record['contact_person'] }} ({{ $record['contact_position'] }})</td></tr>
        <tr><td class="label">Nomor Surat Asal</td><td>{{ $record['letter_number'] }}</td></tr>
        <tr><td class="label">Tanggal Surat</td><td>{{ $record['letter_date'] }}</td></tr>
        <tr><td class="label">Tanggal Diterima</td><td>{{ $record['received_at'] }}</td></tr>
        <tr><td class="label">Perihal</td><td>{{ $record['subject'] }}</td></tr>
        <tr><td class="label">Ringkasan</td><td>{{ $record['summary'] }}</td></tr>
        <tr><td class="label">Status Agenda TU</td><td>{{ strtoupper((string) $record['status']) }}</td></tr>
    </table>

    <div class="qr-box">
        <div class="qr-cell"><img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR Lacak"></div>
        <div class="qr-note">
            <p><strong>Pelacakan Disposisi Surat</strong></p>
            <p>Gunakan nomor tiket <strong>{{ $ticket['ticket_code'] }}</strong> dan kode verifikasi Anda pada portal pelacakan terpadu:</p>
            <p>{{ $track_url }}</p>
        </div>
    </div>
</body>
</html>
