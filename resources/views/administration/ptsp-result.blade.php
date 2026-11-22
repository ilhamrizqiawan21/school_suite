<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil Layanan PTSP {{ $ticket['ticket_code'] }}</title>
    <style>
        @page { size: A4 portrait; margin: 20mm 22mm; }
        body { font-family: "DejaVu Serif", serif; font-size: 10.5pt; color: #111827; line-height: 1.48; margin: 0; }
        .letterhead { border-bottom: 3px double #111827; padding-bottom: 8px; margin-bottom: 18px; display: table; width: 100%; }
        .letterhead-logo { display: table-cell; width: 68px; vertical-align: middle; }
        .letterhead-logo img { width: 58px; height: 58px; object-fit: contain; }
        .letterhead-copy { display: table-cell; text-align: center; vertical-align: middle; }
        .letterhead-copy h1 { margin: 0; font-size: 14pt; text-transform: uppercase; }
        .letterhead-copy p { margin: 2px 0 0; font-size: 8.5pt; font-family: "DejaVu Sans", sans-serif; }
        h2.doc-title { text-align: center; text-decoration: underline; margin: 0 0 4px; font-size: 12pt; text-transform: uppercase; }
        p.doc-sub { text-align: center; margin: 0 0 18px; font-size: 9.5pt; }
        table.info { width: 100%; border-collapse: collapse; margin: 12px 0 16px; }
        table.info td { padding: 4px 6px; vertical-align: top; }
        table.info td.label { width: 165px; font-weight: 700; }
        .summary-box { border: 1px solid #9ca3af; padding: 10px 12px; margin: 12px 0 22px; background: #fafafa; }
        .signature { margin-top: 28px; width: 240px; margin-left: auto; text-align: left; }
        .signature-space { height: 54px; }
        .qr-verify { margin-top: 24px; border-top: 1px dashed #cbd5e1; padding-top: 12px; display: table; width: 100%; font-family: "DejaVu Sans", sans-serif; }
        .qr-verify-cell { display: table-cell; width: 105px; vertical-align: top; }
        .qr-verify-cell img { width: 92px; height: 92px; }
        .qr-verify-note { display: table-cell; vertical-align: top; font-size: 8.5pt; color: #374151; }
    </style>
</head>
<body>
    <div class="letterhead">
        @if(!empty($school['logo_data_uri']))
            <div class="letterhead-logo"><img src="{{ $school['logo_data_uri'] }}" alt="Logo"></div>
        @endif
        <div class="letterhead-copy">
            @if(!empty($school['parent_institution']))
                <p style="font-weight:700;text-transform:uppercase;">{{ $school['parent_institution'] }}</p>
            @endif
            <h1>{{ $school['legal_name'] ?: ($school['name'] ?? 'Sekolah') }}</h1>
            <p>{{ $school['address'] ?? '' }} {{ !empty($school['phone']) ? '· Telp: '.$school['phone'] : '' }}</p>
        </div>
    </div>

    <h2 class="doc-title">Surat Keterangan Penyelesaian Layanan PTSP</h2>
    <p class="doc-sub">Nomor Tiket: {{ $ticket['ticket_code'] }}@if(!empty($verification_token)) · Kode Verifikasi Dokumen: {{ $verification_token }}@endif</p>

    <p>Yang bertanda tangan di bawah ini menerangkan bahwa permohonan Pelayanan Terpadu Satu Pintu (PTSP) berikut telah selesai diproses:</p>

    <table class="info">
        <tr><td class="label">Nama Pemohon</td><td>: {{ $request->applicant_name }}</td></tr>
        <tr><td class="label">Jenis Layanan</td><td>: {{ $request->service_snapshot['name'] ?? '-' }}</td></tr>
        <tr><td class="label">Keperluan</td><td>: {{ $request->purpose }}</td></tr>
        <tr><td class="label">Tanggal Pengajuan</td><td>: {{ $request->submitted_at?->format('d-m-Y H:i') ?? '-' }}</td></tr>
    </table>

    <div class="summary-box">
        <strong>Keterangan Hasil Layanan:</strong>
        <p style="margin:6px 0 0;">{{ $result_summary }}</p>
    </div>

    <div class="signature">
        <div>{{ !empty($school['city']) ? $school['city'].', ' : '' }}{{ $issued_date }}</div>
        <div>{{ $signatory_title }}</div>
        <div class="signature-space"></div>
        <div style="font-weight:700;text-decoration:underline;">{{ $signatory_name }}</div>
    </div>

    @if(!empty($qr_verify) && !empty($verify_url))
        <div class="qr-verify">
            <div class="qr-verify-cell"><img src="data:image/svg+xml;base64,{{ $qr_verify }}" alt="QR Verifikasi"></div>
            <div class="qr-verify-note">
                <p style="margin:0 0 4px;"><strong>Verifikasi Keaslian Dokumen Resmi</strong></p>
                <p style="margin:0 0 4px;">Pindai QR atau buka portal verifikasi dokumen dengan kode <strong>{{ $verification_token }}</strong>:</p>
                <p style="margin:0;">{{ $verify_url }}</p>
            </div>
        </div>
    @endif
</body>
</html>
