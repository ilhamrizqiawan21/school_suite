@php
    $schoolName = $school['legal_name'] ?? ($school['name'] ?? 'Sekolah');
    $legalName = $school['legal_name'] ?? null;
    $parentInstitution = $school['parent_institution'] ?? null;
    $address = collect([$school['address'] ?? null, $school['postal_code'] ?? null])->filter()->join(' ');
    $contact = collect([$school['phone'] ?? null, $school['email'] ?? null, $school['website'] ?? null, ! empty($school['npsn']) ? 'NPSN '.$school['npsn'] : null])->filter()->join(' · ');
@endphp
<header class="letterhead"><table><tr><td class="letterhead-logo">@if(! empty($school['logo_data_uri']))<img src="{{ $school['logo_data_uri'] }}" alt="Logo sekolah">@endif</td><td class="letterhead-identity">@if($parentInstitution && $parentInstitution !== $schoolName)<p class="letterhead-parent">{{ $parentInstitution }}</p>@endif<p class="letterhead-name">{{ $schoolName }}</p>@if($address)<p class="letterhead-address">{{ $address }}</p>@endif @if($contact)<p class="letterhead-contact">{{ $contact }}</p>@endif</td><td class="letterhead-logo"></td></tr></table></header>
