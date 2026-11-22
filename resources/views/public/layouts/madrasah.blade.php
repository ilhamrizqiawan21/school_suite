@php
  $schoolName = ($profile["name"] ?? null) ?: "Sekolah";
  $schoolAddress = ($profile["address"] ?? null) ?: "Alamat belum tersedia";
  $schoolPhone = ($profile["phone"] ?? null) ?: "Belum tersedia";
  $schoolEmail = ($profile["email"] ?? null) ?: "Email belum tersedia";
  $schoolLogo = ($profile["logo_url"] ?? null) ?: "/madrasah-theme/images/logo-kemenag.png";
  $metadata = $contentBlocks['seo.current'] ?? [];
@endphp
<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@if (!empty($metadata['title'])){{ $metadata['title'] }}@else @yield("title", $schoolName . " - Madrasah Mandiri Berprestasi & Unggul Riset")@endif</title>
  @if (!empty($metadata['description']))<meta name="description" content="{{ $metadata['description'] }}"><meta property="og:description" content="{{ $metadata['description'] }}">@endif
  @if (!empty($metadata['title']))<meta property="og:title" content="{{ $metadata['title'] }}">@endif
  @if (!empty($metadata['image_url']))<meta property="og:image" content="{{ $metadata['image_url'] }}">@endif
  @if (!empty($metadata['canonical_url']) && !($isDraftPreview ?? false))<link rel="canonical" href="{{ $metadata['canonical_url'] }}">@endif
  @if ($isDraftPreview ?? false)<meta name="robots" content="noindex, nofollow">@endif
  <link rel="icon" href="{{ $profile['favicon_url'] ?? $schoolLogo }}">
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
