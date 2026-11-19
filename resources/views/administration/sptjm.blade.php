<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Pernyataan Tanggung Jawab Mutlak (SPTJM)</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; line-height: 1.55; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 16px; }
        .header h1 { margin: 0; font-size: 15px; text-transform: uppercase; }
        .header p { margin: 2px 0 0; font-size: 11px; color: #475569; }
        h2 { text-align: center; font-size: 14px; text-decoration: underline; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0 14px; }
        td { padding: 4px 6px; vertical-align: top; }
        .lbl { width: 32%; font-weight: bold; }
        .sign-box { margin-top: 28px; text-align: right; }
        .sig-img { max-height: 55px; margin: 6px 0; }
        .verify { margin-top: 22px; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $school['name'] ?? 'Sekolah / Madrasah' }}</h1>
        <p>{{ $school['address'] ?? '' }} — Template Resmi SPTJM v1</p>
    </div>

    <h2>SURAT PERNYATAAN TANGGUNG JAWAB MUTLAK (SPTJM)</h2>
    <p>Nomor Dokumen: <strong>{{ $documentNumber }}</strong></p>

    <p>Saya yang bertanda tangan di bawah ini menyatakan bertanggung jawab penuh atas kebenaran data dan keabsahan dokumen yang diajukan:</p>
    <table>
        <tr><td class="lbl">Nama Pembuat Pernyataan</td><td>: {{ $payload['grantor_name'] }}</td></tr>
        <tr><td class="lbl">NISN / Identitas (4 digit akhir)</td><td>: {{ $payload['grantor_nisn'] ?: '-' }} / NIK ...{{ $payload['grantor_nik_last4'] }}</td></tr>
        <tr><td class="lbl">Tahun Lulus &amp; Nomor Telepon</td><td>: {{ $payload['grantor_graduation_year'] ?: '-' }} / {{ $payload['grantor_phone'] }}</td></tr>
        <tr><td class="lbl">Nomor Dokumen / Ijazah Terkait</td><td>: {{ $payload['document_ref_number'] ?: '-' }}</td></tr>
        <tr><td class="lbl">Keperluan / Pernyataan</td><td>: {{ $payload['purpose'] }}</td></tr>
    </table>

    <p>Demikian Surat Pernyataan Tanggung Jawab Mutlak ini dibuat dengan sebenarnya untuk digunakan pada pengurusan layanan administrasi sekolah@if(! empty($payload['legalization_code'])) (Register Legalisir: <strong>{{ $payload['legalization_code'] }}</strong>)@endif.</p>

    <div class="sign-box">
        <p>Pembuat Pernyataan,</p>
        @if(! empty($payload['grantor_signature_data']) && str_starts_with($payload['grantor_signature_data'], 'data:image/'))
            <img class="sig-img" src="{{ $payload['grantor_signature_data'] }}" alt="TTD Pembuat SPTJM"><br>
        @else
            <br><br>
        @endif
        <p><strong>{{ $payload['grantor_name'] }}</strong></p>
    </div>

    <div class="verify">
        Kode Verifikasi Publik (/verifikasi): <strong>{{ $verificationToken }}</strong>
    </div>
</body>
</html>
