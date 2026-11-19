<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Keterangan Lulus (SKL) — {{ $record->skl_number }}</title>
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
        .verdict { margin: 16px 0; padding: 10px; text-align: center; border: 2px solid #0f766e; background: #f0fdfa; font-weight: bold; font-size: 13px; }
        .sign-box { margin-top: 28px; text-align: right; }
        .verify { margin-top: 24px; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $school['name'] ?? 'Sekolah / Madrasah' }}</h1>
        <p>{{ $school['address'] ?? '' }}</p>
    </div>

    <h2>SURAT KETERANGAN LULUS (SKL)</h2>
    <p class="docno">Nomor Dokumen: {{ $documentNumber }} | Nomor SKL: {{ $record->skl_number }}</p>

    <p>Berdasarkan rapat penetapan kelulusan <strong>{{ $batch->title }}</strong>, Kepala {{ $school['name'] ?? 'Sekolah' }} menerangkan bahwa:</p>

    <table>
        <tr><th>Nama Peserta Didik</th><td><strong>{{ $student->name }}</strong></td></tr>
        <tr><th>NIS / NISN</th><td>{{ $student->nis }} / {{ $student->nisn ?: '-' }}</td></tr>
        <tr><th>Tempat, Tanggal Lahir</th><td>{{ $student->birth_place ?: '-' }}, {{ $student->birth_date?->format('d-m-Y') ?? '-' }}</td></tr>
        <tr><th>Nilai Rata-Rata</th><td>{{ $record->average_score !== null ? number_format((float) $record->average_score, 2) : '-' }}</td></tr>
    </table>

    <div class="verdict">
        DINYATAKAN: {{ $record->decision === 'graduated' ? 'LULUS' : 'TIDAK LULUS' }}
    </div>

    <p>Surat Keterangan Lulus ini berlaku sebagai pengganti sementara sebelum ijazah asli diterbitkan dan dapat diverifikasi keabsahannya secara daring.</p>

    <div class="sign-box">
        <p>{{ $batch->signatory_title ?: 'Kepala Madrasah / Sekolah' }}</p>
        <br><br>
        <p><strong>{{ $batch->signatory_name ?: 'Kepala Sekolah' }}</strong></p>
    </div>

    @if(! empty($verificationToken))
    <div class="verify">
        Kode Verifikasi Publik (/verifikasi): <strong>{{ $verificationToken }}</strong> (Terbitan ke-{{ $record->reissue_count + 1 }})
    </div>
    @endif
</body>
</html>
