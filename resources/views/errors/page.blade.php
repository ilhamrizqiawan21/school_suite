@php
    $copy = match ($status) {
        403 => ['Akses tidak diizinkan', 'Akun Anda tidak memiliki izin untuk membuka halaman ini. Hubungi administrator sekolah bila diperlukan.'],
        404 => ['Halaman tidak ditemukan', 'Halaman yang Anda cari mungkin telah dipindahkan, dihapus, atau alamatnya tidak sesuai.'],
        503 => ['Sistem sedang pemeliharaan', 'Layanan sedang tidak tersedia. Silakan periksa kembali beberapa saat lagi.'],
        default => ['Terjadi kesalahan sistem', 'Sistem gagal memproses permintaan Anda. Silakan coba kembali beberapa saat lagi.'],
    };
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $status }} — {{ $copy[0] }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="error-page">
    <main class="error-panel error-panel--{{ $status }}" aria-labelledby="error-title">
        <img src="{{ asset('madrasah-theme/images/logo-kemenag.png') }}" width="48" height="48" alt="Kementerian Agama">
        <p class="error-code">ERROR {{ $status }}</p>
        <h1 id="error-title">{{ $copy[0] }}</h1>
        <p class="error-description">{{ $copy[1] }}</p>
        <nav class="error-actions" aria-label="Pemulihan halaman">
            @if ($status >= 500 && request()->isMethod('GET'))
                <a class="error-action error-action--primary" href="{{ request()->url() }}">Periksa ulang status</a>
            @endif
            <a class="error-action {{ $status < 500 ? 'error-action--primary' : '' }}" href="{{ url('/dashboard') }}">Kembali ke dashboard</a>
            <a class="error-action" href="{{ url('/') }}">Halaman utama</a>
        </nav>
    </main>
</body>
</html>
