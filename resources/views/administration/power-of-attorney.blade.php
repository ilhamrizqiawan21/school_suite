<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Kuasa Pengambilan Dokumen Resmi</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; line-height: 1.55; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 16px; }
        .header h1 { margin: 0; font-size: 15px; text-transform: uppercase; }
        .header p { margin: 2px 0 0; font-size: 11px; color: #475569; }
        h2 { text-align: center; font-size: 14px; text-decoration: underline; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
        td { padding: 4px 6px; vertical-align: top; }
        .lbl { width: 32%; font-weight: bold; }
        .sigs { width: 100%; margin-top: 28px; }
        .sigs td { width: 50%; text-align: center; }
        .sig-img { max-height: 55px; margin: 6px 0; }
        .verify { margin-top: 22px; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $school['name'] ?? 'Sekolah / Madrasah' }}</h1>
        <p>{{ $school['address'] ?? '' }} — Template Resmi Surat Kuasa v1</p>
    </div>

    <h2>SURAT KUASA PENGAMBILAN / PENGURUSAN DOKUMEN</h2>
    <p>Nomor Dokumen: <strong>{{ $documentNumber }}</strong></p>

    <p>Saya yang bertanda tangan di bawah ini (Pemberi Kuasa):</p>
    <table>
        <tr><td class="lbl">Nama Lengkap</td><td>: {{ $payload['grantor_name'] }}</td></tr>
        <tr><td class="lbl">NISN / Identitas (4 digit akhir)</td><td>: {{ $payload['grantor_nisn'] ?: '-' }} / NIK ...{{ $payload['grantor_nik_last4'] }}</td></tr>
        <tr><td class="lbl">Tahun Lulus &amp; Nomor Telepon</td><td>: {{ $payload['grantor_graduation_year'] ?: '-' }} / {{ $payload['grantor_phone'] }}</td></tr>
    </table>

    <p>Dengan ini memberikan kuasa kepada:</p>
    <table>
        <tr><td class="lbl">Nama Penerima Kuasa</td><td>: {{ $payload['proxy_name'] }}</td></tr>
        <tr><td class="lbl">Identitas (4 digit akhir NIK)</td><td>: ...{{ $payload['proxy_nik_last4'] }}</td></tr>
        <tr><td class="lbl">Hubungan &amp; Nomor Telepon</td><td>: {{ $payload['proxy_relation'] }} / {{ $payload['proxy_phone'] }}</td></tr>
    </table>

    <p>Untuk keperluan: <strong>{{ $payload['purpose'] }}</strong>@if(! empty($payload['legalization_code'])) (Terkait Register Legalisir: <strong>{{ $payload['legalization_code'] }}</strong>)@endif.</p>

    <table class="sigs">
        <tr>
            <td>
                Penerima Kuasa,<br>
                @if(! empty($payload['proxy_signature_data']) && str_starts_with($payload['proxy_signature_data'], 'data:image/'))
                    <img class="sig-img" src="{{ $payload['proxy_signature_data'] }}" alt="TTD Penerima Kuasa"><br>
                @else
                    <br><br>
                @endif
                <strong>{{ $payload['proxy_name'] }}</strong>
            </td>
            <td>
                Pemberi Kuasa,<br>
                @if(! empty($payload['grantor_signature_data']) && str_starts_with($payload['grantor_signature_data'], 'data:image/'))
                    <img class="sig-img" src="{{ $payload['grantor_signature_data'] }}" alt="TTD Pemberi Kuasa"><br>
                @else
                    <br><br>
                @endif
                <strong>{{ $payload['grantor_name'] }}</strong>
            </td>
        </tr>
    </table>

    <div class="verify">
        Kode Verifikasi Publik (/verifikasi): <strong>{{ $verificationToken }}</strong>
    </div>
</body>
</html>
