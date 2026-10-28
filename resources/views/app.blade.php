<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="app-base-path" content="{{ rtrim(parse_url(config('app.url'), PHP_URL_PATH) ?: '', '/') }}">
        <title inertia>{{ config('app.name', 'School Suite') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('icon-192.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('icon-192.png') }}">

        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            (() => {
                try {
                    if (localStorage.getItem('school-suite-theme') === 'dark') {
                        document.documentElement.dataset.theme = 'dark';
                    }
                } catch (_) {
                    // Theme preference is optional; the app can still load normally.
                }
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900">
        @inertia
    </body>
</html>

