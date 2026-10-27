<!doctype html>
<html lang="id"><head><meta charset="utf-8"><style>
@page { margin: 24mm 23mm; } body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 10pt; line-height: 1.5; }
.header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 18px; } .header h2 { font-size: 15pt; margin: 0; } .header p { margin: 2px 0; font-size: 9pt; }
table { border-collapse: collapse; } .meta td { padding: 2px 5px 2px 0; vertical-align: top; } .date { text-align: right; margin-bottom: 12px; } .body { white-space: pre-wrap; margin-top: 20px; overflow-wrap: break-word; } .signature { margin-top: 30px; margin-left: 60%; page-break-inside: avoid; } .qr { width: 76px; height: 76px; margin: 8px 0; } .small { font-size: 7pt; color: #444; } .draft { text-align: center; color: #b45309; font-weight: bold; margin-bottom: 12px; }
</style></head><body>
<div class="header"><h2>{{ $school['legal_name'] ?: $school['name'] }}</h2><p>{{ $school['address'] ?? '' }}</p><p>{{ collect([$school['phone'] ?? null, $school['email'] ?? null, $school['website'] ?? null])->filter()->join(' · ') }}</p></div>
@if($record->status === 'draft')<div class="draft">DRAF — BELUM DITERBITKAN</div>@endif
<div class="date">{{ $record->letter_date?->locale('id')->translatedFormat('d F Y') }}</div>
<table class="meta"><tr><td>Nomor</td><td>:</td><td>{{ $record->letter_number }}</td></tr><tr><td>Lampiran</td><td>:</td><td>{{ $record->attachment_note ?: '–' }}</td></tr><tr><td>Perihal</td><td>:</td><td>{{ $record->subject }}</td></tr></table>
<p>Yth. {{ $record->recipient }}</p><div class="body">{{ $record->letter_body }}</div>
<div class="signature"><div>{{ $record->signer_title }},</div><div style="height: 75px"></div><strong>{{ $record->signer_name }}</strong>@if($qr)<div><img class="qr" src="data:image/svg+xml;base64,{{ $qr }}" alt="QR pencatatan surat"></div><div class="small">QR memeriksa pencatatan surat. Bukan tanda tangan elektronik bersertifikat atau bukti keaslian berkas.</div>@endif</div>
</body></html>
