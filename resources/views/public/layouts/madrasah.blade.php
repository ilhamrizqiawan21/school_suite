@php
  $schoolName = ($profile["name"] ?? null) ?: "Madrasah Contoh Nusantara";
  $schoolAddress = ($profile["address"] ?? null) ?: "Jl. Pendidikan No. 1 (alamat contoh)";
  $schoolPhone = ($profile["phone"] ?? null) ?: "Belum tersedia";
  $schoolEmail = ($profile["email"] ?? null) ?: "humas@madrasah.example";
  $schoolLogo = ($profile["logo_url"] ?? null) ?: "/madrasah-theme/images/logo-kemenag.png";
@endphp
<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield("title", $schoolName . " - Madrasah Mandiri Berprestasi & Unggul Riset")</title>
  <link rel="icon" href="{{ $schoolLogo }}">
  <link rel="apple-touch-icon" href="{{ $schoolLogo }}">
  @include('public.madrasah.assets')
</head>
<body data-portal-path="{{ request()->path() }}" data-portal-layout="{{ request()->is('profil-sekolah', 'direktori-gtk') ? 'profile' : (request()->is('berita*', 'prestasi', 'galeri*') ? 'catalogue' : (request()->path() === '/' ? 'home' : 'service')) }}" class="bg-slate-50 text-slate-800 font-sans antialiased flex flex-col min-h-screen">
  @include('public.madrasah.navigation')

  <div class="flex-1 flex flex-col">
    @yield("content")
  </div>

  @include('public.madrasah.footer')
</body>
</html>
