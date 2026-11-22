<link rel="stylesheet" href="{{ asset('madrasah-theme/fonts.css') }}">
@if (request()->is('layanan/izin-siswa', 'layanan/kelulusan'))
    <link rel="stylesheet" href="{{ asset('madrasah-theme/permit-fonts.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('madrasah-theme/home.css') }}?v={{ filemtime(public_path('madrasah-theme/home.css')) }}">
<script defer src="{{ asset('madrasah-theme/home.js') }}?v={{ filemtime(public_path('madrasah-theme/home.js')) }}"></script>
<link rel="stylesheet" href="{{ asset('madrasah-theme/parity.css') }}?v={{ filemtime(public_path('madrasah-theme/parity.css')) }}">
<link rel="stylesheet" href="{{ asset('madrasah-theme/procedures.css') }}?v={{ filemtime(public_path('madrasah-theme/procedures.css')) }}">
@if (data_get($profile ?? [], 'theme.use_identity_color', false))
    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        :root { --portal-identity: {{ data_get($profile, 'theme.primary', '#3157a4') }}; }
        @foreach ([50 => 4, 100 => 10, 200 => 20, 300 => 35, 400 => 55, 500 => 75, 600 => 90, 700 => 100, 800 => 85, 900 => 70, 950 => 55] as $shade => $weight)
            .text-kemenag-{{ $shade }} { color: color-mix(in srgb, var(--portal-identity) {{ $weight }}%, {{ $shade >= 800 ? '#14263c' : '#ffffff' }}) !important; }
            .bg-kemenag-{{ $shade }}, .hover\:bg-kemenag-{{ $shade }}:hover { background-color: color-mix(in srgb, var(--portal-identity) {{ $weight }}%, {{ $shade >= 800 ? '#14263c' : '#ffffff' }}) !important; }
            .border-kemenag-{{ $shade }} { border-color: color-mix(in srgb, var(--portal-identity) {{ $weight }}%, {{ $shade >= 800 ? '#14263c' : '#ffffff' }}) !important; }
        @endforeach
    </style>
@endif
