@if(filled($record->opening_greeting ?? null))<p class="salutation">{{ $normalise($record->opening_greeting) }}</p>@endif
<section class="letter-body">@forelse($bodyParagraphs as $paragraph)<p>{{ $paragraph }}</p>@empty<p class="no-indent">Belum ada isi surat.</p>@endforelse</section>
@if(count($closingParagraphs))<section class="closing">@foreach($closingParagraphs as $paragraph)<p>{{ $paragraph }}</p>@endforeach</section>@endif
