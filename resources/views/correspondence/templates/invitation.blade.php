<h1 class="letter-title">Undangan</h1><p class="letter-title-number">Nomor: {{ $record->letter_number ?: '–' }}</p>
<section class="recipient"><p>Yth. {{ $record->recipient }}</p>@foreach($recipientLines as $line)<p>{{ $line }}</p>@endforeach</section>
<div class="invitation-summary"><strong>{{ $record->subject }}</strong>@if($record->summary)<p style="margin-top:6pt">{{ $normalise($record->summary) }}</p>@endif</div>
@include('correspondence.partials.body')
