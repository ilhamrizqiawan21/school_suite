<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="app-base-path" content="{{ rtrim(parse_url(config('app.url'), PHP_URL_PATH) ?: '', '/') }}">
        <title inertia>{{ config('app.name', 'School Suite') }}</title>

        <link rel="icon" href="{{ asset('brand/favicon/favicon.ico') }}" sizes="any">
        <link rel="icon" href="{{ asset('brand/favicon/favicon.svg') }}" type="image/svg+xml">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/favicon/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('brand/favicon/favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('brand/favicon/apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('brand/favicon/site.webmanifest') }}">
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
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900">
        @inertia
    </body>
</html>

