<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Pendaftaran SPMB / PPDB — {{ $applicant->registration_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; line-height: 1.5; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 16px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0 0; font-size: 11px; color: #475569; }
        .badge { display: inline-block; padding: 3px 8px; background: #e2e8f0; border-radius: 4px; font-weight: bold; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 10px; text-align: left; }
        th { width: 32%; background: #f8fafc; font-weight: bold; }
        .footer { margin-top: 24px; font-size: 11px; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $school['name'] ?? 'Madrasah / Sekolah' }}</h1>
        <p>{{ $school['address'] ?? '' }}</p>
        <p>BUKTI PENDAFTARAN PESERTA DIDIK BARU (PPDB / SPMB)</p>
    </div>

    <table>
        <tr>
            <th>Nomor Pendaftaran</th>
            <td><strong>{{ $applicant->registration_no }}</strong></td>
        </tr>
        @if(! empty($ticketCode))
        <tr>
            <th>Kode Pelacakan Terpadu</th>
            <td>{{ $ticketCode }}</td>
        </tr>
        @endif
        <tr>
            <th>Periode &amp; Jalur</th>
            <td>{{ $applicant->period?->name ?? '-' }} — {{ $applicant->track?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Nama Lengkap</th>
            <td>{{ $applicant->name }}</td>
        </tr>
        <tr>
            <th>NISN</th>
            <td>{{ $applicant->nisn ?: '-' }}</td>
        </tr>
        <tr>
            <th>Jenis Kelamin</th>
            <td>{{ $applicant->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
        </tr>
        <tr>
            <th>Tempat, Tanggal Lahir</th>
            <td>{{ $applicant->birth_place ?: '-' }}, {{ $applicant->birth_date?->format('d-m-Y') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Asal Sekolah / Madrasah</th>
            <td>{{ $applicant->previous_school ?: '-' }}</td>
        </tr>
        <tr>
            <th>Nama Orang Tua / Wali</th>
            <td>{{ $applicant->parent_name ?: '-' }}</td>
        </tr>
        <tr>
            <th>Status Pendaftaran</th>
            <td><span class="badge">{{ strtoupper((string) $applicant->status) }}</span></td>
        </tr>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d-m-Y H:i') }}. Simpan nomor pendaftaran dan PIN verifikasi untuk memantau hasil seleksi serta perbaikan berkas.</p>
    </div>
</body>
</html>
