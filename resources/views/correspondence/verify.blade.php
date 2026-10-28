@php
    $integrity = ($integrity_status ?? 'unavailable') === 'unverifiable' ? 'unavailable' : ($integrity_status ?? 'unavailable');
    $integrityLabels = ['valid' => 'Valid', 'changed' => 'Dokumen berubah', 'unavailable' => 'Tidak dapat diverifikasi'];
    $statusLabels = [
        'awaiting_manual_upload' => 'Menunggu hasil pindai', 'pending_signature' => 'Menunggu TTE',
        'signature_failed' => 'TTE gagal', 'signed' => 'Ditandatangani', 'issued' => 'Diterbitkan',
        'sent' => 'Terkirim', 'archived' => 'Diarsipkan',
    ];
    $signatureMethod = $record->signature_method === 'bsre' ? 'Tanda tangan elektronik BSrE' : 'Tanda tangan basah';
    $certificateValid = ($signature_status ?? null) === 'verified';
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Verifikasi surat sekolah</title>
    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        :root { color-scheme: light; --navy:#172844; --line:#d8e0ec; --muted:#5b6b82; --green:#087a55; --red:#b42318; --amber:#a15c00; }
        * { box-sizing:border-box; }
        body { margin:0; padding:24px; background:#f3f6fb; color:#17263c; font:15px/1.6 system-ui,-apple-system,"Segoe UI",sans-serif; }
        main { width:min(720px,100%); margin:5vh auto; overflow:hidden; border:1px solid var(--line); border-radius:16px; background:#fff; box-shadow:0 16px 45px #17284414; }
        header { padding:26px 30px; background:var(--navy); color:#fff; }
        header p { margin:5px 0 0; color:#d7e1f1; }
        h1 { margin:0; font-size:1.35rem; }
        .content { padding:28px 30px; }
        .status { display:flex; align-items:center; gap:10px; margin-bottom:22px; padding:12px 14px; border-radius:10px; font-weight:700; }
        .status::before { content:""; width:10px; height:10px; border-radius:50%; background:currentColor; }
        .valid { background:#e8f8f1; color:var(--green); }
        .changed { background:#feeceb; color:var(--red); }
        .unavailable { background:#fff4df; color:var(--amber); }
        dl { display:grid; grid-template-columns:170px minmax(0,1fr); margin:0; border-top:1px solid var(--line); }
        dt,dd { margin:0; padding:11px 0; border-bottom:1px solid var(--line); overflow-wrap:anywhere; }
        dt { color:var(--muted); font-weight:600; }
        dd { font-weight:650; }
        code { font-size:11px; font-weight:400; word-break:break-all; }
        small { color:var(--muted); font-weight:400; }
        .notice { margin:22px 0 0; color:var(--muted); font-size:.86rem; }
        @media(max-width:560px){ body{padding:12px} header,.content{padding:20px} dl{grid-template-columns:1fr} dt{padding-bottom:0;border-bottom:0} dd{padding-top:2px} }
    </style>
</head>
<body>
<main>
    <header><h1>Verifikasi surat sekolah</h1><p>Informasi pencatatan dan integritas arsip digital.</p></header>
    <div class="content">
        <div class="status {{ $integrity }}">Integritas: {{ $integrityLabels[$integrity] ?? $integrityLabels['unavailable'] }}</div>
        <dl>
            <dt>Sekolah</dt><dd>{{ $school['name'] ?? ($school['legal_name'] ?? 'Sekolah') }}</dd>
            <dt>Nomor surat</dt><dd>{{ $record->letter_number }}</dd>
            <dt>Tanggal surat</dt><dd>{{ $record->letter_date?->locale('id')->translatedFormat('d F Y') }}</dd>
            <dt>Status</dt><dd>{{ $statusLabels[$record->status] ?? ucfirst(str_replace('_', ' ', $record->status)) }}</dd>
            <dt>Penandatangan</dt><dd>{{ data_get($record->signer_snapshot, 'name', $record->signer_name ?: 'Belum tersedia') }}<br><small>{{ data_get($record->signer_snapshot, 'title', $record->signer_title) }}</small></dd>
            <dt>Metode</dt><dd>{{ $signatureMethod }}</dd>
            @if($record->signature_method === 'bsre')<dt>Status sertifikat</dt><dd>{{ $certificateValid ? 'Terverifikasi' : 'Belum terverifikasi' }}</dd>@endif
            @if($record->document_hash)<dt>SHA-256 dokumen final</dt><dd><code>{{ $record->document_hash }}</code></dd>@endif
        </dl>
        <p class="notice">
            @if($record->signature_method === 'bsre' && $certificateValid)
                Metadata TTE telah diverifikasi melalui respons penyedia. Validasi kriptografis lengkap tetap dapat dilakukan dengan perangkat verifikasi PDF resmi.
            @elseif($record->signature_method === 'bsre')
                Dokumen ini belum boleh dianggap memiliki TTE tersertifikasi sampai verifikasi penyedia berhasil.
            @else
                QR memverifikasi pencatatan dan integritas arsip hasil pindai. QR bukan tanda tangan elektronik tersertifikasi.
            @endif
        </p>
    </div>
</main>
</body>
</html>
