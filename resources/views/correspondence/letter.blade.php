@php
    $template = in_array($record->letter_template, ['official_school', 'invitation', 'assignment', 'statement'], true)
        ? $record->letter_template
        : 'official_school';
    $finalStatuses = ['signed', 'issued', 'sent', 'archived'];
    $isDraft = ! in_array($record->status, $finalStatuses, true);
    $normalise = static fn (?string $value): string => trim((string) preg_replace('/[\h\v]+/u', ' ', (string) $value));
    $paragraphs = static function (?string $value) use ($normalise): array {
        return collect(preg_split('/\R+/u', trim((string) $value)) ?: [])
            ->map($normalise)
            ->filter()
            ->values()
            ->all();
    };
    $bodyParagraphs = $paragraphs($record->letter_body);
    $closingParagraphs = $paragraphs($record->closing_text ?? null);
    $recipientLines = $paragraphs($record->recipient_address ?? null);
    $carbonCopyLines = $paragraphs($record->carbon_copy ?? null);
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $record->letter_number ?: 'Draf surat' }}</title>
    <style>
        @page { size: A4 portrait; margin: 16mm 20mm 19mm 25mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: "DejaVu Serif", serif; font-size: 10.5pt; line-height: 1.38; }
        table { width: 100%; border-collapse: collapse; }
        p { margin: 0 0 7pt; orphans: 3; widows: 3; }
        .letterhead { margin-bottom: 11pt; border-bottom: 3px double #111827; padding-bottom: 6pt; }
        .letterhead td { border: 0; vertical-align: middle; }
        .letterhead-logo { width: 24mm; padding-right: 4mm; text-align: center; }
        .letterhead-logo img { width: 20mm; height: 20mm; object-fit: contain; }
        .letterhead-identity { text-align: center; }
        .letterhead-parent { margin: 0; font-size: 10pt; font-weight: 700; text-transform: uppercase; }
        .letterhead-name { margin: 1pt 0; font-size: 15pt; font-weight: 800; text-transform: uppercase; letter-spacing: .2pt; }
        .letterhead-address, .letterhead-contact { margin: 1pt 0; font-family: "DejaVu Sans", sans-serif; font-size: 8pt; line-height: 1.3; }
        .draft-banner { margin: 0 0 9pt; border: 1px solid #d97706; padding: 4pt; color: #92400e; font-family: "DejaVu Sans", sans-serif; font-size: 9pt; font-weight: 700; text-align: center; }
        .letter-title { margin: 0 0 2pt; font-size: 12pt; font-weight: 800; text-align: center; text-decoration: underline; text-transform: uppercase; }
        .letter-title-number { margin: 0 0 14pt; text-align: center; }
        .letter-date { margin: 0 0 12pt; text-align: right; }
        .metadata { margin: 0 0 14pt; }
        .metadata td { border: 0; padding: 1.5pt 0; vertical-align: top; }
        .metadata-label { width: 25mm; }
        .metadata-colon { width: 5mm; text-align: center; }
        .recipient { margin: 0 0 14pt; page-break-inside: avoid; }
        .recipient p { margin: 0; }
        .salutation { margin-bottom: 9pt; }
        .letter-body { text-align: justify; }
        .letter-body p { text-indent: 10mm; }
        .letter-body p.no-indent, .closing p { text-indent: 0; }
        .invitation-summary, .assignment-basis, .statement-box { margin: 7pt 0 9pt; border: 1px solid #9ca3af; padding: 7pt 9pt; page-break-inside: avoid; }
        .closing { margin-top: 8pt; text-align: justify; }
        .signature-section { margin-top: 12pt; page-break-inside: avoid; }
        .signature-section td { border: 0; vertical-align: top; }
        .verification-cell { width: 38%; padding-right: 8mm; font-family: "DejaVu Sans", sans-serif; font-size: 7pt; color: #475569; }
        .signature-cell { width: 62%; padding-left: 8mm; }
        .qr { display: block; width: 23mm; height: 23mm; margin: 0 0 3pt; }
        .verification-label { margin: 0 0 2pt; font-weight: 700; color: #1f2937; }
        .signature-space { height: 15mm; }
        .signer-name { font-weight: 700; text-decoration: underline; }
        .signer-meta { margin-top: 1pt; font-size: 9pt; }
        .signature-note { margin-top: 4pt; color: #475569; font-family: "DejaVu Sans", sans-serif; font-size: 7pt; }
        .carbon-copy { margin-top: 9pt; font-size: 9pt; page-break-inside: avoid; }
        .carbon-copy ol { margin: 3pt 0 0 18pt; padding: 0; }
        .document-hash { margin-top: 3pt; font-size: 6pt; overflow-wrap: anywhere; word-break: break-all; }
        .document-footer { position: fixed; right: 0; bottom: -14mm; left: 0; border-top: .5pt solid #cbd5e1; padding-top: 3pt; color: #64748b; font-family: "DejaVu Sans", sans-serif; font-size: 6.5pt; }
        .document-footer .page-number { float: right; }
        .document-footer .page-number::after { content: counter(page); }
    </style>
</head>
<body>
    @include('correspondence.partials.letterhead', ['school' => $school])
    @if($isDraft)<div class="draft-banner">DRAF — BELUM DITERBITKAN</div>@endif
    @include("correspondence.templates.{$template}")
    @include('correspondence.partials.signature')
    @if(count($carbonCopyLines))
        <section class="carbon-copy"><strong>Tembusan:</strong><ol>@foreach($carbonCopyLines as $line)<li>{{ $line }}</li>@endforeach</ol></section>
    @endif
    <footer class="document-footer"><span>{{ $record->letter_number ?: 'Draf belum bernomor' }}</span><span class="page-number">Halaman </span></footer>
</body>
</html>
