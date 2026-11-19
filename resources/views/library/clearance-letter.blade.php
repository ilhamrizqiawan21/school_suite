<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Keterangan Bebas Pustaka — {{ $clearance->clearance_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; line-height: 1.55; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0 0; font-size: 11px; color: #475569; }
        h2 { font-size: 14px; text-align: center; text-decoration: underline; margin-bottom: 4px; }
        .docno { text-align: center; font-size: 11px; color: #475569; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 10px; text-align: left; }
        th { width: 34%; background: #f8fafc; }
        .sign-box { margin-top: 28px; text-align: right; }
        .verify { margin-top: 22px; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $school['name'] ?? 'Perpustakaan Sekolah / Madrasah' }}</h1>
        <p>{{ $school['address'] ?? '' }}</p>
    </div>

    <h2>SURAT KETERANGAN BEBAS PUSTAKA</h2>
    <p class="docno">Nomor Dokumen: {{ $documentNumber }} | Kode Register: {{ $clearance->clearance_code }}</p>

    <p>Pengelola Perpustakaan {{ $school['name'] ?? 'Sekolah' }} menerangkan bahwa peserta didik berikut telah menyelesaikan seluruh kewajiban pengembalian koleksi dan dinyatakan <strong>BEBAS PUSTAKA</strong>:</p>

    <table>
        <tr><th>Nama Peserta Didik</th><td><strong>{{ $student['name'] ?? '-' }}</strong></td></tr>
        <tr><th>NIS / NISN</th><td>{{ $student['nis'] ?? '-' }} / {{ $student['nisn'] ?? '-' }}</td></tr>
        <tr><th>Rombongan Belajar</th><td>{{ $student['class_group_name'] ?? '-' }}</td></tr>
        <tr><th>Status Tanggungan Pinjaman</th><td>0 pinjaman aktif (Terpenuhi)</td></tr>
        <tr><th>Tanggal Diterbitkan</th><td>{{ $clearance->issued_at?->format('d-m-Y') ?? now()->format('d-m-Y') }}</td></tr>
    </table>

    <div class="sign-box">
        <p>Kepala / Pustakawan Perpustakaan,</p>
        <br><br>
        <p><strong>{{ $signatoryName }}</strong></p>
    </div>

    <div class="verify">
        Kode Verifikasi Publik (/verifikasi): <strong>{{ $verificationToken }}</strong>
    </div>
</body>
</html>
