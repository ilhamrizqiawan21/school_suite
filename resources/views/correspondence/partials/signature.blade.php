@php
    $signer = is_array($record->signer_snapshot ?? null) ? $record->signer_snapshot : [];
    $signerName = $signer['name'] ?? $record->signer_name;
    $signerTitle = $signer['title'] ?? $record->signer_title;
    $signerNip = $signer['employee_number'] ?? null;
    $signerRank = $signer['rank'] ?? null;
    $signatureMethod = $record->signature_method ?? 'manual';
    $city = $school['city'] ?? null;
@endphp
<section class="signature-section"><table><tr><td class="verification-cell">@if($qr)<p class="verification-label">Verifikasi arsip digital</p><img class="qr" src="data:image/svg+xml;base64,{{ $qr }}" alt="QR verifikasi surat"><p>Pindai QR untuk memeriksa status pencatatan dan integritas file. QR ini bukan bukti TTE tersertifikasi.</p>@if($record->document_hash)<p class="document-hash">SHA-256: {{ $record->document_hash }}</p>@endif @endif</td><td class="signature-cell"><p>{{ collect([$city, $record->letter_date?->locale('id')->translatedFormat('d F Y')])->filter()->join(', ') }}</p><p>{{ $signerTitle ?: 'Pejabat berwenang' }},</p><div class="signature-space"></div><p class="signer-name">{{ $signerName ?: 'Nama penandatangan' }}</p>@if($record->signer_identifier)<p class="signer-meta">{{ strtoupper($record->signer_identifier_type ?: 'nip') }}. {{ $record->signer_identifier }}</p>@elseif($signerNip)<p class="signer-meta">NIP. {{ $signerNip }}</p>@endif @if($signerRank)<p class="signer-meta">{{ $signerRank }}</p>@endif @if($signatureMethod === 'bsre')<p class="signature-note">Posisi tanda tangan elektronik diisi oleh penyedia resmi setelah proses penandatanganan berhasil.</p>@elseif($record->status === 'awaiting_signature')<p class="signature-note">Menunggu tanda tangan basah dan unggahan hasil pindai.</p>@endif</td></tr></table></section>
