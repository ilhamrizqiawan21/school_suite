<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $mutation->direction === 'outgoing' ? 'Surat Rekomendasi Pindah / Mutasi Keluar' : 'Surat Penerimaan Mutasi Masuk' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; line-height: 1.55; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0 0; font-size: 11px; color: #475569; }
        h2 { font-size: 14px; text-align: center; text-decoration: underline; margin-bottom: 4px; }
        .docno { text-align: center; font-size: 11px; color: #475569; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 10px; text-align: left; }
        th { width: 34%; background: #f8fafc; }
        .sign-box { margin-top: 28px; text-align: right; }
        .verify { margin-top: 24px; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $school['name'] ?? 'Sekolah / Madrasah' }}</h1>
        <p>{{ $school['address'] ?? '' }}</p>
    </div>

    <h2>{{ $mutation->direction === 'outgoing' ? 'SURAT KETERANGAN PINDAH / MUTASI KELUAR' : 'SURAT PENERIMAAN MUTASI MASUK' }}</h2>
    <p class="docno">Nomor: {{ $documentNumber }}</p>

    <p>Yang bertanda tangan di bawah ini menerangkan bahwa peserta didik berikut telah disetujui proses mutasinya:</p>

    <table>
        <tr><th>Kode Permohonan</th><td>{{ $mutation->mutation_code }}</td></tr>
        <tr><th>Nama Peserta Didik</th><td>{{ $mutation->student_name }}</td></tr>
        <tr><th>NIS / NISN</th><td>{{ $mutation->nis ?: '-' }} / {{ $mutation->nisn ?: '-' }}</td></tr>
        <tr><th>Jenis Kelamin</th><td>{{ $mutation->gender === 'P' ? 'Perempuan' : 'Laki-laki' }}</td></tr>
        @if($mutation->direction === 'incoming')
            <tr><th>Sekolah / Madrasah Asal</th><td>{{ $mutation->origin_school ?: '-' }}</td></tr>
        @else
            <tr><th>Sekolah / Madrasah Tujuan</th><td>{{ $mutation->destination_school ?: '-' }}</td></tr>
        @endif
        <tr><th>Alasan Mutasi</th><td>{{ $mutation->reason }}</td></tr>
        <tr><th>Status Keputusan</th><td>DISETUJUI ({{ $mutation->decided_at?->format('d-m-Y') ?? now()->format('d-m-Y') }})</td></tr>
    </table>

    <div class="sign-box">
        <p>{{ $signatoryTitle ?: 'Kepala Sekolah / Pejabat Berwenang' }}</p>
        <br><br>
        <p><strong>{{ $signatoryName ?: 'Pejabat Sekolah' }}</strong></p>
    </div>

    @if(! empty($verificationToken))
    <div class="verify">
        Token Verifikasi Publik (/verifikasi): <strong>{{ $verificationToken }}</strong>
    </div>
    @endif
</body>
</html>
