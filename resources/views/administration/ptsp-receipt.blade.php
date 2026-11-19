<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Resi PTSP {{ $request['ticket_code'] }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 20mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #172033; line-height: 1.45; margin: 0; }
        .head { border-bottom: 2px solid #172033; padding-bottom: 10px; margin-bottom: 16px; display: table; width: 100%; }
        .head-logo { display: table-cell; width: 64px; vertical-align: middle; }
        .head-logo img { width: 54px; height: 54px; object-fit: contain; }
        .head-title { display: table-cell; vertical-align: middle; }
        .head-title h1 { margin: 0; font-size: 14pt; text-transform: uppercase; }
        .head-title p { margin: 2px 0 0; font-size: 8.5pt; color: #475569; }
        .banner { background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 14px; margin-bottom: 16px; }
        .banner-code { font-size: 15pt; font-weight: 700; letter-spacing: 0.5px; color: #1e3a8a; margin: 4px 0; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.meta td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.meta td.label { width: 170px; font-weight: 700; color: #334155; }
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
            <p><strong>Tanda Bukti Pengajuan Layanan Terpadu Satu Pintu (PTSP)</strong></p>
        </div>
    </div>

    <div class="banner">
        <div>Nomor Tiket / Resi Layanan</div>
        <div class="banner-code">{{ $request['ticket_code'] }}</div>
        <div>Status Saat Ini: <strong>{{ strtoupper((string) $request['status']) }}</strong></div>
    </div>

    <table class="meta">
        <tr><td class="label">Jenis Layanan</td><td>{{ $request['service_snapshot']['name'] ?? '-' }} ({{ $request['service_snapshot']['code'] ?? '-' }})</td></tr>
        <tr><td class="label">Estimasi Waktu</td><td>{{ $request['service_snapshot']['estimated_duration'] ?? '-' }}</td></tr>
        <tr><td class="label">Nama Pemohon</td><td>{{ $request['applicant_name'] }}</td></tr>
        <tr><td class="label">Instansi / Asal</td><td>{{ $request['applicant_organization'] ?: '-' }}</td></tr>
        <tr><td class="label">Kontak</td><td>{{ $request['applicant_phone'] ?: '-' }} {{ $request['applicant_email'] ? '· '.$request['applicant_email'] : '' }}</td></tr>
        <tr><td class="label">Keperluan</td><td>{{ $request['purpose'] }}</td></tr>
        <tr><td class="label">Waktu Pengajuan</td><td>{{ $request['submitted_at'] ?? '-' }}</td></tr>
        <tr><td class="label">Jumlah Lampiran</td><td>{{ count($request['attachment_document_ids'] ?? []) }} berkas</td></tr>
    </table>

    <div class="qr-box">
        <div class="qr-cell"><img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR Lacak"></div>
        <div class="qr-note">
            <p><strong>Pelacakan Status Layanan</strong></p>
            <p>Gunakan nomor tiket <strong>{{ $request['ticket_code'] }}</strong> beserta kode verifikasi Anda pada portal pelacakan terpadu:</p>
            <p>{{ $track_url }}</p>
            <p>Simpan resi ini sebagai bukti pengajuan dan pengambilan dokumen fisik apabila diperlukan.</p>
        </div>
    </div>
</body>
</html>
