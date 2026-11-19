<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Alumni Elektronik (E-KTA) — {{ $profile->full_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        .card { border: 2px solid #0f766e; border-radius: 10px; padding: 18px; width: 480px; }
        .card-head { border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; margin-bottom: 12px; }
        .card-head h1 { margin: 0; font-size: 15px; color: #0f766e; text-transform: uppercase; }
        .card-head p { margin: 2px 0 0; font-size: 11px; color: #475569; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 0; font-size: 12px; }
        .label { width: 36%; color: #475569; font-weight: bold; }
        .verify { margin-top: 12px; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-head">
            <h1>{{ $school['name'] ?? 'Ikatan Alumni' }} — E-KTA ALUMNI</h1>
            <p>Nomor Kartu: {{ $profile->ekta_number }} | Dokumen: {{ $documentNumber }}</p>
        </div>
        <table>
            <tr><td class="label">Nama Lengkap</td><td><strong>{{ $profile->full_name }}</strong></td></tr>
            <tr><td class="label">Angkatan / Lulus</td><td>{{ $profile->graduation_year }} {{ $profile->major_or_class ? "({$profile->major_or_class})" : '' }}</td></tr>
            <tr><td class="label">NISN / NIS</td><td>{{ $profile->nisn ?: '-' }} / {{ $profile->nis ?: '-' }}</td></tr>
            @if($profile->opt_in_career && $profile->institution_or_company)
            <tr><td class="label">Instansi / Kampus</td><td>{{ $profile->institution_or_company }}</td></tr>
            @endif
        </table>
        <div class="verify">
            Verifikasi Keabsahan (/verifikasi): <strong>{{ $verificationToken }}</strong>
        </div>
    </div>
</body>
</html>
