<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Lembar Pengesahan Legalisir — {{ $legalization->legalization_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; line-height: 1.5; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0 0; font-size: 11px; color: #475569; }
        h2 { font-size: 14px; text-align: center; text-decoration: underline; margin-bottom: 4px; }
        .docno { text-align: center; font-size: 11px; color: #475569; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 10px; text-align: left; }
        th { width: 35%; background: #f8fafc; }
        .sign-box { margin-top: 28px; text-align: right; }
        .verify { margin-top: 22px; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $school['name'] ?? 'Sekolah / Madrasah' }}</h1>
        <p>{{ $school['address'] ?? '' }}</p>
    </div>

    <h2>TANDA PENGESAHAN LEGALISIR DOKUMEN</h2>
    <p class="docno">Nomor Register: {{ $documentNumber }} | Kode Permohonan: {{ $legalization->legalization_code }}</p>

    <table>
        <tr><th>Nama Alumni / Pemohon</th><td><strong>{{ $legalization->applicant_name }}</strong></td></tr>
        <tr><th>NIS / NISN</th><td>{{ $legalization->applicant_nis ?: '-' }} / {{ $legalization->applicant_nisn ?: '-' }}</td></tr>
        <tr><th>Tahun Lulus</th><td>{{ $legalization->graduation_year }}</td></tr>
        <tr><th>Jenis &amp; Nomor Dokumen</th><td>{{ strtoupper($legalization->document_type) }} — {{ $legalization->certificate_number }}</td></tr>
        <tr><th>Jumlah Salinan Disahkan</th><td>{{ $legalization->copy_count }} lembar</td></tr>
        <tr><th>Keperluan</th><td>{{ $legalization->purpose }}</td></tr>
        <tr><th>Pemeriksaan Dokumen Asli</th><td>Sesuai &amp; Terverifikasi ({{ $legalization->original_verified_at?->format('d-m-Y') ?? now()->format('d-m-Y') }})</td></tr>
    </table>

    <div class="sign-box">
        <p>{{ $signatoryTitle ?: 'Kepala Tata Usaha / Pejabat Pengesahan' }}</p>
        <br><br>
        <p><strong>{{ $signatoryName ?: 'Petugas TU' }}</strong></p>
    </div>

    <div class="verify">
        Token Verifikasi Publik (/verifikasi): <strong>{{ $verificationToken }}</strong>
        @if(! empty($qrDataUri))
            <div style="margin-top: 8px;">
                <img src="{{ $qrDataUri }}" alt="QR Verifikasi Legalisir" style="width: 72px; height: 72px;">
            </div>
        @endif
    </div>
</body>
</html>
