<link rel="stylesheet" href="{{ asset('madrasah-theme/fonts.css') }}">
@if (request()->is('layanan/izin-siswa', 'layanan/kelulusan'))
    <link rel="stylesheet" href="{{ asset('madrasah-theme/permit-fonts.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('madrasah-theme/home.css') }}?v={{ filemtime(public_path('madrasah-theme/home.css')) }}">
<script defer src="{{ asset('madrasah-theme/home.js') }}?v={{ filemtime(public_path('madrasah-theme/home.js')) }}"></script>
<link rel="stylesheet" href="{{ asset('madrasah-theme/parity.css') }}?v={{ filemtime(public_path('madrasah-theme/parity.css')) }}">
<link rel="stylesheet" href="{{ asset('madrasah-theme/procedures.css') }}?v={{ filemtime(public_path('madrasah-theme/procedures.css')) }}">
