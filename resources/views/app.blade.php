@php
    $isMadrasahTheme = ($page['props']['publicPortalTheme'] ?? null) === 'madrasah';
    $usesMadrasahShell = str_starts_with($page['component'] ?? '', 'Public/') && $isMadrasahTheme;

    if ($usesMadrasahShell) {
        $portalSchool = \App\Modules\Core\Infrastructure\Models\School::query()->orderBy('id')->firstOrFail();
        $shellData = app(\App\Modules\Core\Application\Services\PublicPortalService::class)->madrasahShellData($portalSchool);
        $profile = $shellData['profile'];
        $schoolName = ($profile['name'] ?? null) ?: 'Madrasah Contoh Nusantara';
        if (request()->is('layanan/izin-siswa', 'layanan/kelulusan')) {
            $schoolName = $page['props']['school']['name'] ?? $schoolName;
        }
        $schoolAddress = ($profile['address'] ?? null) ?: 'Alamat belum tersedia';
        $schoolPhone = ($profile['phone'] ?? null) ?: 'Telepon belum tersedia';
        $schoolEmail = ($profile['email'] ?? null) ?: 'humas@madrasah.example';
        $schoolLogo = ($profile['logo_url'] ?? null) ?: '/madrasah-theme/images/logo-kemenag.png';
        $madrasahFavicon = ($profile['favicon_url'] ?? null) ?: $schoolLogo;
    } elseif ($isMadrasahTheme) {
        $madrasahFavicon = ($page['props']['branding']['favicon_url'] ?? null)
            ?: ($page['props']['branding']['logo_url'] ?? null)
            ?: '/madrasah-theme/images/logo-kemenag.png';
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="app-base-path" content="{{ rtrim(parse_url(config('app.url'), PHP_URL_PATH) ?: '', '/') }}">
        <title inertia>{{ config('app.name', 'School Suite') }}</title>

        @if ($isMadrasahTheme)
            <link rel="icon" href="{{ $madrasahFavicon }}">
            <link rel="apple-touch-icon" href="{{ $madrasahFavicon }}">
        @else
            <link rel="icon" href="{{ asset('brand/favicon/favicon.ico') }}" sizes="any">
            <link rel="icon" href="{{ asset('brand/favicon/favicon.svg') }}" type="image/svg+xml">
            <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/favicon/favicon-32x32.png') }}">
            <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('brand/favicon/favicon-16x16.png') }}">
            <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('brand/favicon/apple-touch-icon.png') }}">
            <link rel="manifest" href="{{ asset('brand/favicon/site.webmanifest') }}">
        @endif
        <meta name="theme-color" content="#0D3B66">

        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            (() => {
                const root = document.documentElement;
                const component = @json($page['component'] ?? '');
                const lightPage = component.startsWith('Public/') || component === 'Welcome' || component === 'Auth/Login';
                let theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                try {
                    const stored = localStorage.getItem('school-suite-theme');
                    if (stored === 'light' || stored === 'dark') theme = stored;
                } catch (_) { /* Storage is optional. */ }
                root.dataset.theme = lightPage ? 'light' : theme;
                root.style.colorScheme = root.dataset.theme;
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @if ($usesMadrasahShell)
            @include('public.madrasah.assets')
        @elseif (($page['component'] ?? '') === 'Academic/Attendance/Kiosk')
            <link rel="stylesheet" href="{{ asset('madrasah-theme/fonts.css') }}">
        @endif
        @inertiaHead
    </head>
    <body data-portal-path="{{ request()->path() }}" data-portal-layout="{{ request()->is('profil-sekolah', 'direktori-gtk', 'berita*', 'prestasi', 'galeri*') ? 'catalogue' : (request()->path() === '/' ? 'home' : 'service') }}" @class(['font-sans antialiased bg-slate-50 text-slate-900', 'madrasah-inertia-shell' => $usesMadrasahShell])>
        @if ($usesMadrasahShell)
            @include('public.madrasah.navigation', $shellData)
            <div id="main-content" tabindex="-1">
                <noscript>
                    <p class="madrasah-loading-state">Aktifkan JavaScript untuk membuka formulir dan layanan portal ini.</p>
                </noscript>
                <div class="madrasah-loading-state" data-portal-load-error hidden role="alert">
                    <p>Halaman layanan belum dapat dimuat.</p>
                    <a href="{{ request()->getRequestUri() }}">Muat ulang halaman</a>
                </div>
                @inertia
            </div>
            @include('public.madrasah.footer', $shellData)
        @else
            @inertia
        @endif
    </body>
</html>

