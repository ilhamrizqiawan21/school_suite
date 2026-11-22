@php
    $isMadrasahTheme = ($page['props']['publicPortalTheme'] ?? null) === 'madrasah';
    $usesMadrasahShell = str_starts_with($page['component'] ?? '', 'Public/') && $isMadrasahTheme;

    if ($usesMadrasahShell) {
        $portalSchool = \App\Modules\Core\Infrastructure\Models\School::query()->orderBy('id')->firstOrFail();
        $shellData = app(\App\Modules\Core\Application\Services\PublicPortalService::class)->madrasahShellData($portalSchool);
        $profile = $shellData['profile'];
        $schoolName = ($profile['name'] ?? null) ?: 'Sekolah';
        if (request()->is('layanan/izin-siswa', 'layanan/kelulusan')) {
            $schoolName = $page['props']['school']['name'] ?? $schoolName;
        }
        $schoolAddress = ($profile['address'] ?? null) ?: 'Alamat belum tersedia';
        $schoolPhone = ($profile['phone'] ?? null) ?: 'Telepon belum tersedia';
        $schoolEmail = ($profile['email'] ?? null) ?: 'Email belum tersedia';
        $schoolLogo = ($profile['logo_url'] ?? null) ?: '/madrasah-theme/images/logo-kemenag.png';
        $madrasahFavicon = ($profile['favicon_url'] ?? null) ?: $schoolLogo;
        // Keep untouched legacy records on the original Kemenag green. Once
        // the profile saves a base color, that identity color drives the
        // static Blade shell as well as the Inertia page.
        $portalPrimary = data_get($shellData, 'profile.theme.use_identity_color', false)
            ? data_get($shellData, 'profile.theme.primary', '#047857')
            : '#047857';
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
        <meta name="csp-nonce" content="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
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
            <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
                body.madrasah-inertia-shell {
                    --portal-primary: {{ $portalPrimary }};
                    --portal-primary-deep: color-mix(in srgb, var(--portal-primary) 78%, #0f172a);
                }
                body.madrasah-inertia-shell :is(.bg-kemenag-700, .bg-emerald-700, .bg-emerald-800) { background-color: var(--portal-primary) !important; }
                body.madrasah-inertia-shell :is(.bg-kemenag-800, .bg-kemenag-900, .bg-kemenag-950, .bg-emerald-900, .bg-emerald-950) { background-color: var(--portal-primary-deep) !important; }
                body.madrasah-inertia-shell :is(.text-kemenag-600, .text-kemenag-700, .text-kemenag-800, .text-emerald-600, .text-emerald-700, .text-emerald-800) { color: var(--portal-primary) !important; }
                body.madrasah-inertia-shell :is(.border-kemenag-700, .border-kemenag-800, .border-emerald-500, .border-emerald-600, .border-emerald-700) { border-color: color-mix(in srgb, var(--portal-primary) 72%, transparent) !important; }
                body.madrasah-inertia-shell :is(.from-kemenag-700, .from-emerald-700) { --tw-gradient-from: var(--portal-primary) var(--tw-gradient-from-position); }
                body.madrasah-inertia-shell :is(.to-kemenag-800, .to-emerald-800, .to-emerald-700) { --tw-gradient-to: var(--portal-primary) var(--tw-gradient-to-position); }
                body.madrasah-inertia-shell :is(.hover\:bg-kemenag-700:hover, .hover\:bg-kemenag-800:hover, .hover\:bg-emerald-800:hover) { background-color: var(--portal-primary) !important; }
                body.madrasah-inertia-shell :is(.hover\:text-kemenag-700:hover, .hover\:text-kemenag-800:hover, .hover\:text-emerald-700:hover, .hover\:text-emerald-800:hover) { color: var(--portal-primary) !important; }
            </style>
        @elseif (($page['component'] ?? '') === 'Academic/Attendance/Kiosk')
            <link rel="stylesheet" href="{{ asset('madrasah-theme/fonts.css') }}">
        @endif
        @inertiaHead
    </head>
    <body data-portal-path="{{ request()->path() }}" data-portal-layout="{{ request()->is('profil-sekolah', 'direktori-gtk', 'berita*', 'prestasi', 'galeri*') ? 'catalogue' : (request()->path() === '/' ? 'home' : 'service') }}" @style($usesMadrasahShell ? '--portal-primary: '.($portalPrimary ?? '#047857').';' : null) @class(['font-sans antialiased bg-slate-50 text-slate-900', 'madrasah-inertia-shell' => $usesMadrasahShell])>
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

