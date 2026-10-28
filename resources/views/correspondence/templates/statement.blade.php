<h1 class="letter-title">Surat Pernyataan</h1><p class="letter-title-number">Nomor: {{ $record->letter_number ?: '–' }}</p><p>Yang bertanda tangan di bawah ini:</p>
<table class="metadata"><tr><td class="metadata-label">Nama</td><td class="metadata-colon">:</td><td>{{ $record->signer_name }}</td></tr><tr><td class="metadata-label">Jabatan</td><td class="metadata-colon">:</td><td>{{ $record->signer_title }}</td></tr></table>
<div class="statement-box"><strong>Menyatakan bahwa:</strong><br>{{ $record->subject }}</div>
@include('correspondence.partials.body')
