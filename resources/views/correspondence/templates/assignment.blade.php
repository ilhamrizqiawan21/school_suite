<h1 class="letter-title">Surat Tugas</h1><p class="letter-title-number">Nomor: {{ $record->letter_number ?: '–' }}</p>
@if($record->summary)<div class="assignment-basis"><strong>Dasar:</strong> {{ $normalise($record->summary) }}</div>@endif
<p>Dengan ini, {{ $record->signer_title ?: 'pejabat yang berwenang' }} menugaskan kepada:</p><section class="recipient"><p><strong>{{ $record->recipient }}</strong></p>@foreach($recipientLines as $line)<p>{{ $line }}</p>@endforeach</section>
@include('correspondence.partials.body')
