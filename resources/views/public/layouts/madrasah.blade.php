@php
  $schoolName = ($profile["name"] ?? null) ?: "{{ $schoolName }}";
  $schoolAddress = ($profile["address"] ?? null) ?: "Jl. Pendidikan No. 1, Lingkungan Madrasah";
  $schoolPhone = ($profile["phone"] ?? null) ?: "(0511) 000-1234";
  $schoolEmail = ($profile["email"] ?? null) ?: "humas@madrasah-contoh.sch.id";
  $schoolLogo = ($profile["logo_url"] ?? null) ?: "/brand/favicon/favicon.svg";
  $schoolAccreditation = ($profile["accreditation"] ?? null) ?: "A (Unggul)";
@endphp
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield("title", $schoolName . " - Madrasah Mandiri Berprestasi & Unggul Riset")</title>
  <link rel="icon" href="/brand/favicon/favicon.svg">
  <link rel="stylesheet" href="/madrasah-theme/fonts.css">
  <link rel="stylesheet" href="/madrasah-theme/home.css">
  <script defer src="/madrasah-theme/home.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-body antialiased flex flex-col min-h-screen">
  <a class="portal-skip-link bg-white rounded-xl px-4 py-2 font-bold" href="#main-content">Lewati ke konten utama</a>

  <!-- ================= TOP BAR INFO & JADWAL SHOLAT ================= -->
  <aside class="bg-kemenag-900 text-white text-xs border-b border-kemenag-700/60 hidden md:block">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center space-x-6">
        <span class="flex items-center gap-1.5 font-semibold text-emerald-100">
          <i class="fa-solid fa-id-card text-accent-yellow"></i> {{ $schoolName }}        </span>
        <span class="flex items-center gap-1.5 text-emerald-100">
          <i class="fa-solid fa-location-dot text-accent-yellow"></i> {{ $schoolAddress }}        </span>
        <span class="flex items-center gap-1.5 text-emerald-100">
          <i class="fa-solid fa-phone text-accent-yellow"></i> {{ $schoolPhone }}        </span>

        <!-- Live Traffic Counter Badge (Top Bar) -->
        <span class="hidden lg:inline-flex items-center gap-1.5 bg-kemenag-950/80 px-2.5 py-0.5 rounded-full border border-emerald-500/40 text-[11px] text-emerald-100 font-semibold shadow-inner" title="Kunjungan portal hari ini">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
          </span>
          <span>Hari ini: <strong id="topbarLiveVisitors" class="text-accent-yellow font-bold">{{ number_format($visits['today'], 0, ',', '.') }}</strong> kunjungan</span>
        </span>
      </div>

      <div class="flex items-center space-x-4">
        <!-- Widget Sholat Dinamis setempat -->
        <div class="flex items-center gap-2 bg-kemenag-800/90 px-3 py-1 rounded-full border border-kemenag-600/50 cursor-pointer hover:bg-kemenag-700 transition" role="button" tabindex="0" data-open-dialog="prayerModal" aria-haspopup="dialog" title="Klik untuk lihat jadwal lengkap hari ini">
          <i class="fa-solid fa-mosque text-accent-yellow"></i>
          <span class="text-emerald-100">Waktu Sholat:</span>
          <span id="activePrayerDisplay" class="font-bold text-accent-yellow">{{ $prayer['next_prayer']['name'] }} {{ $prayer['next_prayer']['time'] }}</span>
          <i class="fa-solid fa-chevron-down text-[10px] text-emerald-300"></i>
        </div>

        <div class="flex items-center space-x-3 text-emerald-200">
          <a href="/kontak" class="hover:text-accent-yellow transition" title="Facebook"><i class="fa-brands fa-facebook"></i></a>
          <a href="/kontak" class="hover:text-accent-yellow transition" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="/galeri" class="hover:text-accent-yellow transition" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
          <a href="/kontak" class="hover:text-accent-yellow transition" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
        </div>
      </div>
    </div>
  </aside>

  <!-- ================= RUNNING NEWS TICKER (STANDAR MODERN SELARAS) ================= -->
    <div class="bg-gradient-to-r from-kemenag-950 via-kemenag-900 to-kemenag-950 text-white py-2.5 sm:py-3 border-b border-emerald-800/80 shadow-md overflow-hidden relative select-none z-30">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 flex items-center gap-3.5">

      <!-- Fixed Left Label Badge -->
      <div class="flex items-center gap-2 shrink-0 z-10 bg-kemenag-900/95 backdrop-blur-md pr-3 py-1 border-r border-emerald-700/60">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-accent-gold text-slate-950 text-xs font-bold tracking-wider uppercase shadow-md shadow-black/20">
          <span class="flex h-2 w-2 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-slate-950 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-slate-950"></span>
          </span>
          PENGUMUMAN
        </span>
      </div>

      <!-- Continuous Smooth Scrolling Track -->
      <div class="ticker-wrap flex-1 overflow-hidden relative">
        <div class="flex animate-marquee-slow hover:pause-on-hover whitespace-nowrap items-center will-change-transform" style="-webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-rose-400 text-slate-950">
                <i class="fa-solid fa-shield-halved text-[10px]"></i>
                TOLAK GRATIFIKASI              </span>
              <span class="text-white font-normal tracking-wide">Maklumat ZI: Kami Menolak Segala Bentuk Gratifikasi &amp; Pungli | Seluruh Layanan PTSP Bebas Biaya (Rp 0,-)</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-amber-300 text-slate-950">
                <i class="fa-solid fa-bullhorn text-[10px]"></i>
                INFO UTAMA              </span>
              <span class="text-white font-normal tracking-wide">Selamat Datang di Portal Resmi Madrasah</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-emerald-300 text-slate-950">
                <i class="fa-solid fa-graduation-cap text-[10px]"></i>
                PPDB ONLINE              </span>
              <span class="text-white font-normal tracking-wide">Layanan PTSP Aktif</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-sky-200 text-slate-950">
                <i class="fa-solid fa-stamp text-[10px]"></i>
                PTSP DIGITAL              </span>
              <span class="text-white font-normal tracking-wide">Informasi jalur dan jadwal Penerimaan Peserta Didik Baru (PPDB) tersedia di portal PPDB</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-yellow-200 text-slate-950">
                <i class="fa-solid fa-award text-[10px]"></i>
                PRESTASI              </span>
              <span class="text-white font-normal tracking-wide">Layanan Administrasi PTSP Mandiri Buka Senin - Jumat Bebas Biaya (Rp 0,-)</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-purple-200 text-slate-950">
                <i class="fa-solid fa-calendar-check text-[10px]"></i>
                AGENDA              </span>
              <span class="text-white font-normal tracking-wide">Madrasah Aliyah Negeri Riset &amp; Adiwiyata Mandiri: Meneguhkan Tradisi Prestasi &amp; Akhlakul Karimah</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-rose-400 text-slate-950">
                <i class="fa-solid fa-shield-halved text-[10px]"></i>
                TOLAK GRATIFIKASI              </span>
              <span class="text-white font-normal tracking-wide">Konsultasi &amp; Layanan Cepat Tersedia Melalui Fitur Bantuan di Pojok Kanan Bawah</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-rose-400 text-slate-950">
                <i class="fa-solid fa-shield-halved text-[10px]"></i>
                TOLAK GRATIFIKASI              </span>
              <span class="text-white font-normal tracking-wide">Maklumat ZI: Kami Menolak Segala Bentuk Gratifikasi &amp; Pungli | Seluruh Layanan PTSP Bebas Biaya (Rp 0,-)</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-amber-300 text-slate-950">
                <i class="fa-solid fa-bullhorn text-[10px]"></i>
                INFO UTAMA              </span>
              <span class="text-white font-normal tracking-wide">Selamat Datang di Portal Resmi Madrasah</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-emerald-300 text-slate-950">
                <i class="fa-solid fa-graduation-cap text-[10px]"></i>
                PPDB ONLINE              </span>
              <span class="text-white font-normal tracking-wide">Layanan PTSP Aktif</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-sky-200 text-slate-950">
                <i class="fa-solid fa-stamp text-[10px]"></i>
                PTSP DIGITAL              </span>
              <span class="text-white font-normal tracking-wide">Informasi jalur dan jadwal Penerimaan Peserta Didik Baru (PPDB) tersedia di portal PPDB</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-yellow-200 text-slate-950">
                <i class="fa-solid fa-award text-[10px]"></i>
                PRESTASI              </span>
              <span class="text-white font-normal tracking-wide">Layanan Administrasi PTSP Mandiri Buka Senin - Jumat Bebas Biaya (Rp 0,-)</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-purple-200 text-slate-950">
                <i class="fa-solid fa-calendar-check text-[10px]"></i>
                AGENDA              </span>
              <span class="text-white font-normal tracking-wide">Madrasah Aliyah Negeri Riset &amp; Adiwiyata Mandiri: Meneguhkan Tradisi Prestasi &amp; Akhlakul Karimah</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-rose-400 text-slate-950">
                <i class="fa-solid fa-shield-halved text-[10px]"></i>
                TOLAK GRATIFIKASI              </span>
              <span class="text-white font-normal tracking-wide">Konsultasi &amp; Layanan Cepat Tersedia Melalui Fitur Bantuan di Pojok Kanan Bawah</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                  </div>
      </div>

      <!-- Quick Date & Live Status on the Right -->
      <div class="hidden xl:flex items-center gap-2 text-xs text-emerald-200 shrink-0 font-medium z-10 bg-kemenag-950/80 border border-emerald-500/30 px-3 py-1.5 rounded-xl shadow-xs">
        <i class="fa-regular fa-clock text-accent-yellow text-xs"></i>
        <span>Layanan Digital Aktif 24 Jam</span>
      </div>

    </div>
  </div>

  <!-- ================= MAIN NAVIGATION BAR (2 BARIS ELEGAN) ================= -->
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md shadow-sm border-b border-slate-200">
    <!-- Baris 1: Brand Logo & Tombol Aksi Utama -->
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2.5 flex items-center justify-between gap-4 portal-header-row">

      <!-- Brand Logo -->
      <a href="/" class="portal-brand flex items-center gap-2.5 sm:gap-3 group">
                  <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-9 h-9 sm:w-10 sm:h-10 object-contain group-hover:scale-105 transition-transform">
                <div class="flex flex-col justify-center">
          <p class="text-sm sm:text-base lg:text-lg font-bold font-sans text-slate-900 tracking-tight leading-tight group-hover:text-kemenag-700 transition-colors">
            {{ $schoolName }}          </p>
          <span class="text-[10px] text-slate-500 font-medium -mt-0.5 block">Informasi dan layanan sekolah</span>
        </div>
      </a>

      <!-- Quick Action Right: Daftar Sekolah, Search, Login Siswa, Login, Mobile Toggle -->
      <div class="portal-header-actions flex items-center space-x-2 sm:space-x-3 ml-auto">

        <button data-open-dialog="searchModal" aria-haspopup="dialog" class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-kemenag-700 flex items-center justify-center transition shadow-sm" title="Cari di Portal">
          <i class="fa-solid fa-magnifying-glass text-xs sm:text-sm"></i>
        </button>

        <!-- Tombol Login Siswa (Nuansa Oranye-Emas Landing Page) -->
        <a href="/login" class="portal-login-action inline-flex items-center gap-1.5 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white text-xs sm:text-[13px] font-bold px-3.5 py-2 rounded-xl shadow-md hover:shadow-orange-500/30 transition-all border border-amber-300/30" title="Masuk Portal Siswa">
          <i class="fa-solid fa-user-graduate text-amber-100 text-xs"></i>
          <span>Login Siswa</span>
        </a>

        <!-- Tombol Login Admin (Nuansa Hijau Kemenag Tegas Berbeda) -->
        <a href="/login" class="portal-login-action inline-flex items-center gap-1.5 bg-gradient-to-r from-kemenag-700 to-kemenag-800 hover:from-kemenag-800 hover:to-kemenag-900 text-white text-xs sm:text-[13px] font-bold px-3.5 py-2 rounded-xl shadow-md hover:shadow-emerald-900/20 transition-all border border-emerald-600/30" title="Masuk Portal Admin / Guru / Tendik">
          <i class="fa-solid fa-user-tie text-accent-yellow text-xs"></i>
          <span>Login</span>
        </a>

        <!-- Mobile Menu Toggle -->
        <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="Menu navigasi" class="md:hidden w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center hover:bg-slate-200 transition">
          <i class="fa-solid fa-bars text-sm sm:text-base"></i>
        </button>
      </div>

    </div>

    <!-- Baris 2: Sub-Navbar Menu Navigasi Lengkap (Desktop & Tablet) -->
    <div class="hidden md:block bg-slate-50/90 border-t border-slate-200/80">
      <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-1.5 flex items-center justify-between">

        <nav class="flex flex-wrap items-center gap-x-6 gap-y-1 text-[13px] font-semibold text-slate-700">
          <a href="/" class="text-kemenag-700 hover:text-kemenag-800 transition py-1 flex items-center gap-1.5 font-bold">
            <i class="fa-solid fa-house text-xs text-kemenag-600"></i> Beranda
          </a>

          <!-- Profil Dropdown -->
          <div class="relative group">
            <button class="flex items-center gap-1 hover:text-kemenag-700 py-1 transition">
              <span>Profil</span>
              <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 group-hover:rotate-180 transition-transform"></i>
            </button>
            <div class="absolute left-0 top-full pt-1.5 hidden group-hover:block w-60 z-50">
              <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-2.5 flex flex-col space-y-1">
                @foreach (config('public_portal.profile_menu') as $item)
                  <a href="{{ $item['href'] }}" class="px-3 py-2 rounded-xl hover:bg-emerald-50 hover:text-kemenag-700 text-xs font-medium transition flex items-center gap-2">
                    <i class="fa-solid {{ $item['icon'] }} text-kemenag-600 w-4" aria-hidden="true"></i> {{ $item['label'] }}
                  </a>
                @endforeach
              </div>
            </div>
          </div>

          <a href="/zona-integritas" class="hover:text-kemenag-700 transition py-1 flex items-center gap-1 font-semibold text-emerald-800">
            <i class="fa-solid fa-shield-heart text-amber-500 text-xs"></i>
            <span>Zona Integritas</span>
          </a>
          <a href="/layanan/ptsp" class="hover:text-kemenag-700 transition py-1 flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            PTSP Digital
          </a>

          <!-- E-Layanan Mega Menu (3 Kolom Tematik Berkelompok) -->
          <div class="portal-mega-menu-group relative group">
            <button class="flex items-center gap-1.5 hover:text-kemenag-700 py-1 transition text-emerald-800 font-extrabold">
              <span>E-Layanan</span>
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
              <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 group-hover:rotate-180 transition-transform"></i>
            </button>

            <!-- Panel Mega Menu 3 Kolom -->
            <div class="portal-mega-menu absolute -left-48 lg:-left-64 top-full pt-2 hidden group-hover:block w-[780px] lg:w-[860px] z-50 transition-all duration-200">
              <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 p-5 ring-1 ring-black/5 overflow-hidden">

                <!-- Header Pita Mega Menu -->
                <div class="flex items-center justify-between pb-3.5 mb-3.5 border-b border-slate-100">
                  <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">
                      <i class="fa-solid fa-layer-group"></i>
                    </span>
                    <div>
                      <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Pusat Layanan Digital Mandiri Madrasah</h4>
                      <p class="text-[10px] text-slate-500">Pilih kategori layanan terpadu secara mandiri dan cepat</p>
                    </div>
                  </div>
                  <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                    16 Layanan Aktif
                  </span>
                </div>

                <!-- 3 Kolom Tematik -->
                <div class="grid grid-cols-3 gap-3.5">

                  <!-- Kolom 1: Persuratan, Tamu & PTSP -->
                  <div class="space-y-1.5 bg-slate-50/60 p-3 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-1.5 pb-1 border-b border-slate-200/60 mb-2">
                      <i class="fa-solid fa-landmark-dome text-emerald-600 text-xs"></i>
                      <span class="text-[10.5px] font-extrabold text-emerald-800 uppercase tracking-wider">Persuratan &amp; Tamu</span>
                    </div>

                    <!-- Kirim Surat Dinas (Unggulan Baru) -->
                    <a href="/layanan/surat-dinas" class="p-2 rounded-xl bg-emerald-50/90 hover:bg-emerald-100 text-slate-800 transition flex items-start gap-2.5 border border-emerald-300/80 group/item">
                      <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-paper-plane"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                          <span class="text-xs font-bold text-emerald-900 group-hover/item:text-emerald-700 truncate">Kirim Surat Dinas</span>
                          <span class="bg-amber-400 text-slate-950 text-[8.5px] font-black px-1.5 py-0.2 rounded-full shadow-xs">BARU</span>
                        </div>
                        <p class="text-[10px] text-emerald-700/80 leading-tight mt-0.5">Surat resmi instansi luar &amp; lacak</p>
                      </div>
                    </a>

                    <!-- PTSP Terpadu -->
                    <a href="/layanan/ptsp" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-stamp"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-kemenag-700 block truncate">PTSP Terpadu Online</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">Pelayanan izin &amp; permohonan</p>
                      </div>
                    </a>

                    <!-- Buku Tamu Digital -->
                    <a href="/layanan/buku-tamu" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-address-book"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-kemenag-700 block truncate">Buku Tamu Digital</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">Resepsionis &amp; kunjungan dinas</p>
                      </div>
                    </a>

                    <!-- Layanan Pengaduan (Lapor) -->
                    <a href="/layanan/lapor" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-bullhorn"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-rose-600 block truncate">Layanan Pengaduan</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">Kritik, aspirasi, &amp; lapor kendala</p>
                      </div>
                    </a>
                  </div>

                  <!-- Kolom 2: Siswa & Alumni -->
                  <div class="space-y-1.5 bg-slate-50/60 p-3 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-1.5 pb-1 border-b border-slate-200/60 mb-2">
                      <i class="fa-solid fa-graduation-cap text-amber-500 text-xs"></i>
                      <span class="text-[10.5px] font-extrabold text-amber-800 uppercase tracking-wider">Siswa &amp; Alumni</span>
                    </div>

                    <!-- Presensi Siswa Mandiri (RFID TAP) -->
                    <a href="/kios-presensi" class="p-2 rounded-xl bg-emerald-50/90 hover:bg-emerald-100 text-slate-800 transition flex items-start gap-2.5 border border-emerald-300/80 group/item">
                      <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-id-card-clip"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                          <span class="text-xs font-bold text-emerald-900 group-hover/item:text-emerald-700 truncate">Presensi Siswa Mandiri</span>
                          <span class="bg-amber-400 text-slate-950 text-[8.5px] font-black px-1.5 py-0.2 rounded-full shadow-xs">BARU</span>
                        </div>
                        <p class="text-[10px] text-emerald-700/80 leading-tight mt-0.5">Tap kartu RFID / Scan QR Kiosk</p>
                      </div>
                    </a>

                    <!-- Legalisir Ijazah -->
                    <a href="/layanan/legalisir" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-certificate"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-amber-700 block truncate">Legalisir Ijazah Online</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">Pengesahan ijazah alumni</p>
                      </div>
                    </a>

                    <!-- Mutasi Siswa -->
                    <a href="/layanan/mutasi" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-people-arrows"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-teal-700 block truncate">Mutasi &amp; Pindah Siswa</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">Pengajuan pindah masuk/keluar</p>
                      </div>
                    </a>

                    <!-- Surat Izin Siswa -->
                    <a href="/layanan/izin-siswa" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-file-signature"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-blue-700 block truncate">Surat Izin Siswa</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">Izin sakit &amp; keperluan penting</p>
                      </div>
                    </a>

                    <!-- Pengumuman Kelulusan -->
                    <a href="/layanan/kelulusan" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-award"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-amber-700 block truncate">Pengumuman Kelulusan</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">Cek kelulusan &amp; cetak SKL</p>
                      </div>
                    </a>
                  </div>

                  <!-- Kolom 3: Ketertiban & Penunjang -->
                  <div class="space-y-1.5 bg-slate-50/60 p-3 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-1.5 pb-1 border-b border-slate-200/60 mb-2">
                      <i class="fa-solid fa-shield-halved text-purple-600 text-xs"></i>
                      <span class="text-[10.5px] font-extrabold text-purple-900 uppercase tracking-wider">Ketertiban &amp; Sarana</span>
                    </div>

                    <!-- Izin Keluar-Masuk (Piket) -->
                    <a href="/piket/izin-keluar" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-door-open"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-emerald-700 block truncate">Izin Keluar-Masuk (Piket)</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">Validasi keluar gerbang santri</p>
                      </div>
                    </a>

                    <!-- Scan Pos Satpam -->
                    <a href="/pos-jaga" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-shield-halved"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-slate-900 block truncate">Scan Pos Satpam</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">Keamanan &amp; pos gerbang</p>
                      </div>
                    </a>

                    <!-- Scan Pelanggaran Disiplin -->
                    <a href="/bk/kasus" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-qrcode"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-amber-700 block truncate">Scan Disiplin BK</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">Pencatatan pelanggaran siswa</p>
                      </div>
                    </a>

                    <!-- Tracking Permohonan -->
                    <a href="/lacak" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-magnifying-glass-location"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-purple-700 block truncate">Lacak Status Berkas</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">Tracking permohonan online</p>
                      </div>
                    </a>
                  </div>

                </div>

                <!-- Footer Bar Mega Menu (Pusat Unduh & Zona Integritas) -->
                <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs bg-slate-50/70 -mx-5 -mb-5 px-5 py-3">
                  <div class="flex items-center gap-3">
                    <button data-open-dialog="downloadModal" aria-haspopup="dialog" class="text-slate-600 hover:text-emerald-700 font-bold transition flex items-center gap-1.5 text-xs">
                      <i class="fa-solid fa-cloud-arrow-down text-emerald-600"></i> Pusat Unduh Berkas PDF
                    </button>
                    <span class="text-slate-300">•</span>
                    <button data-open-dialog="ziModal" aria-haspopup="dialog" class="text-slate-600 hover:text-amber-700 font-bold transition flex items-center gap-1.5 text-xs">
                      <i class="fa-solid fa-shield-heart text-amber-500"></i> Zona Integritas &amp; IKM
                    </button>
                  </div>
                  <a href="/perpustakaan" class="text-emerald-700 hover:text-emerald-800 font-bold transition flex items-center gap-1 text-[11px]">
                    <i class="fa-solid fa-book-bookmark"></i> Perpustakaan Digital →
                  </a>
                </div>

              </div>
            </div>
          </div>

          <a href="/e-layanan" class="hover:text-kemenag-700 transition py-1">Katalog Layanan</a>
          <a href="/akademik-fasilitas" class="hover:text-kemenag-700 transition py-1">Peminatan</a>
          <a href="/perpustakaan" class="hover:text-kemenag-700 transition py-1">Perpustakaan</a>
          <a href="/alumni" class="hover:text-kemenag-700 transition py-1">Alumni</a>
          <a href="/berita" class="hover:text-kemenag-700 transition py-1">Berita</a>
          <a href="/prestasi" class="hover:text-kemenag-700 transition py-1">Prestasi</a>
          <a href="/galeri" class="hover:text-kemenag-700 transition py-1">Galeri</a>
          <a href="/galeri" class="hover:text-kemenag-700 transition py-1">Video</a>
          <a href="/kontak" class="hover:text-kemenag-700 transition py-1">Kontak</a>
        </nav>

        <!-- Quick Info Kanan Sub-Navbar -->
        <div class="hidden lg:flex items-center gap-3 text-xs text-slate-500 font-medium">
          <a href="/lacak" class="hover:text-kemenag-700 flex items-center gap-1.5 transition">
            <i class="fa-solid fa-clock-rotate-left text-emerald-600"></i>
            <span>Tracking Berkas</span>
          </a>
          <span class="text-slate-300">•</span>
          <a href="/layanan/kelulusan" class="hover:text-kemenag-700 flex items-center gap-1.5 transition">
            <i class="fa-solid fa-award text-amber-500"></i>
            <span>Cek SKL</span>
          </a>
        </div>

      </div>
    </div>
  </header>

  <!-- Mobile Drawer Menu -->
  <div id="mobileMenu" role="dialog" aria-modal="true" aria-label="Menu navigasi" tabindex="-1" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm">
    <div class="fixed right-0 top-0 bottom-0 w-4/5 max-w-sm bg-white p-6 shadow-2xl flex flex-col justify-between overflow-y-auto">
      <div>
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-graduation-cap text-kemenag-700 text-xl"></i>
            <span class="font-extrabold text-slate-900">{{ $schoolName }}</span>
          </div>
          <button data-close-dialog aria-label="Tutup menu" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <nav class="mt-6 flex flex-col space-y-2.5 font-semibold text-slate-700 text-sm">
          <a href="/e-layanan" class="p-2 hover:bg-emerald-50 rounded-xl text-kemenag-700 font-bold">Semua E-Layanan</a>
          <a href="/" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">Beranda</a>

          <div class="grid grid-cols-2 gap-1.5 my-1">
                        <a href="/login" class="p-2.5 bg-amber-50 hover:bg-orange-100 rounded-xl text-orange-900 font-bold flex items-center gap-1.5 items-center justify-center text-center text-xs border border-orange-200">
              <i class="fa-solid fa-user-graduate text-orange-600 "></i> Login Siswa
            </a>
            <a href="/login" class="p-2.5 bg-emerald-50 hover:bg-emerald-100 rounded-xl text-kemenag-800 font-bold flex items-center gap-1.5 items-center justify-center text-center text-xs border border-emerald-200">
              <i class="fa-solid fa-user-tie text-kemenag-600 "></i> Login Admin
            </a>
          </div>

          <!-- Kategori 1: Persuratan & Tamu -->
          <div class="pt-2">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200 block mb-1">
              🏛️ Persuratan &amp; Tamu
            </span>
            <div class="space-y-1">
              <a href="/layanan/surat-dinas" class="p-2 bg-emerald-50/80 hover:bg-emerald-100 rounded-xl text-kemenag-800 font-bold flex items-center justify-between text-xs border border-emerald-200">
                <span class="flex items-center gap-2"><i class="fa-solid fa-paper-plane text-emerald-600"></i> Kirim Surat Dinas</span>
                <span class="bg-amber-400 text-slate-950 text-[9px] font-black px-1.5 py-0.2 rounded-full">BARU</span>
              </a>
              <a href="/layanan/ptsp" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-stamp text-kemenag-600"></i> PTSP Terpadu Online
              </a>
              <a href="/layanan/buku-tamu" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-address-book text-emerald-600"></i> Buku Tamu Digital (Resepsionis)
              </a>
              <a href="/layanan/lapor" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-bullhorn text-rose-600"></i> Layanan Pengaduan (Lapor)
              </a>
            </div>
          </div>

          <!-- Kategori 2: Siswa & Alumni -->
          <div class="pt-2">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200 block mb-1">
              🎓 Layanan Siswa &amp; Alumni
            </span>
            <div class="space-y-1">
              <a href="/kios-presensi" class="p-2 bg-emerald-50/80 hover:bg-emerald-100 rounded-xl text-kemenag-800 font-bold flex items-center justify-between text-xs border border-emerald-200">
                <span class="flex items-center gap-2"><i class="fa-solid fa-id-card-clip text-emerald-600"></i> Presensi Mandiri (RFID Tap)</span>
                <span class="bg-amber-400 text-slate-950 text-[9px] font-black px-1.5 py-0.2 rounded-full">BARU</span>
              </a>
              <a href="/layanan/legalisir" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-certificate text-amber-600"></i> Legalisir Ijazah Online
              </a>
              <a href="/layanan/mutasi" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-people-arrows text-teal-600"></i> Pengajuan Mutasi Siswa
              </a>
              <a href="/layanan/izin-siswa" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-file-signature text-blue-600"></i> Surat Izin Siswa Online
              </a>
              <a href="/layanan/kelulusan" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-award text-amber-500"></i> Pengumuman Kelulusan (SKL)
              </a>
              <a href="/alumni" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-graduation-cap text-accent-gold"></i> Portal Ikatan Alumni &amp; Tracer
              </a>
            </div>
          </div>

          <!-- Kategori 3: Ketertiban & Fasilitas -->
          <div class="pt-2">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-purple-800 bg-purple-50 px-2.5 py-0.5 rounded-full border border-purple-200 block mb-1">
              🛡️ Ketertiban &amp; Sarana
            </span>
            <div class="space-y-1">
              <a href="/piket/izin-keluar" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-door-open text-emerald-600"></i> Izin Keluar-Masuk (Piket)
              </a>
              <a href="/pos-jaga" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-shield-halved text-emerald-700"></i> Scan Pos Satpam (Gerbang)
              </a>
              <a href="/bk/kasus" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-amber-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-qrcode text-amber-600"></i> Scan Pelanggaran Siswa (BK)
              </a>
              <a href="/lacak" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-magnifying-glass-location text-purple-600"></i> Lacak Status Permohonan
              </a>
              <a href="/perpustakaan" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-book-bookmark text-emerald-600"></i> Perpustakaan Digital
              </a>
              <button data-open-dialog="downloadModal" aria-haspopup="dialog" class="w-full text-left p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-cloud-arrow-down text-emerald-600"></i> Pusat Unduh Berkas (PDF)
              </button>
              <button data-open-dialog="ziModal" aria-haspopup="dialog" class="w-full text-left p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-shield-halved text-accent-gold"></i> Zona Integritas (WBK)
              </button>
            </div>
          </div>

          <div class="pt-2 border-t border-slate-100">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1">
              Profil &amp; Informasi
            </span>
          </div>
          @foreach (config('public_portal.profile_menu') as $item)
            <a href="{{ $item['href'] }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">
              <i class="fa-solid {{ $item['icon'] }} text-kemenag-600 w-4" aria-hidden="true"></i> {{ $item['label'] }}
            </a>
          @endforeach
          <a href="/berita" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">Warta Berita</a>
          <a href="/prestasi" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">Prestasi Siswa</a>
          <a href="/galeri" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">Galeri Foto</a>
          <a href="/galeri" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">Video Kegiatan</a>
          <a href="/kontak" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">Kontak &amp; Lokasi</a>
        </nav>
      </div>

      <div class="pt-6 border-t border-slate-100">
        <a href="/ppdb" class="w-full bg-kemenag-700 text-white font-bold py-3 rounded-xl shadow-md text-center block">
          Daftar PPDB        </a>
      </div>
    </div>
  </div>


  <div class="flex-1 flex flex-col">
    @yield("content")
  </div>

  <!-- Footer -->
  <footer class="bg-kemenag-950 text-white pt-10 pb-6 border-t border-kemenag-800 mt-auto">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 pb-8 border-b border-kemenag-800/80">

        <!-- Info Brand -->
        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <div class="w-10 h-10 rounded-xl bg-kemenag-800 flex items-center justify-center text-accent-yellow text-xl border border-emerald-500/40 shadow-sm">
              <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <span class="font-extrabold text-lg text-white font-sans">{{ $schoolName }}</span>
          </div>
          <p class="text-xs text-emerald-200 leading-relaxed">
            Madrasah Aliyah Negeri unggulan di lingkungan sekolah. Mengedepankan pendidikan berbasis adiwiyata, karakter islami, dan riset sains teknologi modern.
          </p>
          <div class="flex items-center space-x-3 text-emerald-300 pt-1">
            <a href="/kontak" class="hover:text-accent-yellow transition"><i class="fa-brands fa-facebook"></i></a>
            <a href="/kontak" class="hover:text-accent-yellow transition"><i class="fa-brands fa-instagram"></i></a>
            <a href="/galeri" class="hover:text-accent-yellow transition"><i class="fa-brands fa-youtube"></i></a>
            <a href="/kontak" class="hover:text-accent-yellow transition"><i class="fa-brands fa-tiktok"></i></a>
          </div>
        </div>

        <!-- Tautan Pintas Modul Akademik -->
        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-accent-yellow mb-3">Modul &amp; Akademik</h4>
          <ul class="space-y-2 text-xs text-emerald-100">
            <li><a href="/profil-sekolah#arah" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Visi &amp; Misi Madrasah</a></li>
            <li><a href="/direktori-gtk" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Direktori Dewan Guru &amp; GTK</a></li>
            <li><a href="/login" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Login Guru / Siswa / Admin</a></li>
            <li><a href="/perpustakaan" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Perpustakaan Digital SLiMS</a></li>
            <li><a href="/alumni" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Portal Tracer Study Alumni</a></li>
            <li><a href="/layanan/kelulusan" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Pengumuman Kelulusan SKL</a></li>
            <li><a href="/layanan/lapor" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Pusat Pengaduan (Lapor)</a></li>
          </ul>
        </div>

        <!-- Layanan PTSP Terpadu -->
        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-accent-yellow mb-3">Layanan Terpadu (PTSP)</h4>
          <ul class="space-y-2 text-xs text-emerald-100">
            <li><a href="/layanan/ptsp" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> PTSP Terpadu Online</a></li>
            <li><a href="/layanan/legalisir" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Legalisir Ijazah Online</a></li>
            <li><a href="/layanan/mutasi" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Permohonan Mutasi Siswa</a></li>
            <li><a href="/layanan/izin-siswa" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Surat Izin Siswa Online</a></li>
            <li><a href="/piket/izin-keluar" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Izin Keluar-Masuk (Piket)</a></li>
            <li><a href="/pos-jaga" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Scan Pos Satpam (Gerbang)</a></li>
            <li><a href="/lacak" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> Lacak Status Berkas</a></li>
          </ul>
        </div>

        <!-- Alamat -->
        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-accent-yellow mb-3">Alamat</h4>
          <p class="text-xs text-emerald-100 leading-relaxed">
            {{ $schoolAddress }}          </p>
          <p class="text-xs text-emerald-100 mt-2">
            Telepon: <span class="text-accent-yellow font-semibold">{{ $schoolPhone }}</span>
          </p>
          <p class="text-xs text-emerald-100 mt-1">
            Email: <a href="/kontak" class="underline text-accent-yellow">{{ $schoolEmail }}</a>
          </p>
        </div>

      </div>

      <!-- WIDGET TRAFIK KUNJUNGAN REALTIME -->
      <div class="my-6 p-4 sm:p-5 rounded-2xl bg-kemenag-950/90 border border-emerald-500/30 backdrop-blur-md shadow-inner">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-kemenag-800 flex items-center justify-center text-accent-yellow text-lg border border-emerald-500/40 shadow-sm">
              <i class="fa-solid fa-chart-line"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h4 class="text-xs font-bold text-white uppercase tracking-wider">Statistik Kunjungan Web</h4>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> Real-Time
                </span>
              </div>
              <p class="text-[11px] text-emerald-200">Total tayangan portal {{ $schoolName }}.</p>
            </div>
          </div>

          <!-- 4 Metrik Trafik Kunjungan -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4 w-full md:w-auto text-xs">
            <div class="p-2.5 rounded-xl bg-kemenag-900/80 border border-emerald-500/30 text-center min-w-[110px]">
              <span class="text-[10px] text-emerald-300 font-semibold block uppercase">Tanggal</span>
              <span class="text-base sm:text-lg font-extrabold text-accent-yellow flex items-center justify-center gap-1.5 mt-0.5">
                <span class="fa-solid fa-calendar-day" aria-hidden="true"></span>
                <span id="liveTrafficDate">{{ $prayer['date'] }}</span>
              </span>
            </div>
            <div class="p-2.5 rounded-xl bg-kemenag-900/80 border border-emerald-500/30 text-center min-w-[110px]">
              <span class="text-[10px] text-emerald-300 font-semibold block uppercase">Hari Ini</span>
              <span id="liveTrafficToday" class="text-base sm:text-lg font-extrabold text-white mt-0.5 block">{{ number_format($visits['today'], 0, ',', '.') }}</span>
            </div>
            <div class="p-2.5 rounded-xl bg-kemenag-900/80 border border-emerald-500/30 text-center min-w-[110px]">
              <span class="text-[10px] text-emerald-300 font-semibold block uppercase">Bulan Ini</span>
              <span id="liveTrafficMonth" class="text-base sm:text-lg font-extrabold text-emerald-200 mt-0.5 block">{{ number_format($visits['month'], 0, ',', '.') }}</span>
            </div>
            <div class="p-2.5 rounded-xl bg-kemenag-900/80 border border-emerald-500/30 text-center min-w-[110px]">
              <span class="text-[10px] text-emerald-300 font-semibold block uppercase">Total Kunjungan</span>
              <span id="liveTrafficTotal" class="text-base sm:text-lg font-extrabold text-accent-yellow mt-0.5 block">{{ number_format($visits['total'], 0, ',', '.') }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="pt-5 flex flex-col sm:flex-row items-center justify-between text-xs text-emerald-200 gap-3 font-medium">
        <p>© 2026 {{ $schoolName }}. Hak Cipta Dilindungi Undang-Undang.</p>
        <p>Kementerian Agama lingkungan sekolah.</p>
      </div>

    </div>
  </footer>

  <!-- ================= FLOATING ACTION BUTTONS ================= -->
  <div class="fixed z-40 flex flex-col items-end space-y-3" style="bottom: 104px; right: 43px;">
    <!-- Back to Top Button -->
    <button id="backToTopBtn" data-scroll-top aria-label="Kembali ke atas" class="w-10 h-10 rounded-full bg-slate-900/80 hover:bg-slate-900 text-white shadow-xl flex items-center justify-center text-sm transition-all duration-300 opacity-0 pointer-events-none translate-y-3">
      <i class="fa-solid fa-arrow-up"></i>
    </button>
  </div>

  <!-- ================= MODAL WINDOWS ================= -->

  <!-- 1. Modal Jadwal Sholat -->
  <div id="prayerModal" role="dialog" aria-modal="true" aria-label="Jadwal salat" tabindex="-1" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl relative">
      <button data-close-dialog aria-label="Tutup dialog" class="absolute right-4 top-4 w-8 h-8 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="text-center mb-5">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-kemenag-700 flex items-center justify-center text-xl mx-auto mb-2">
          <i class="fa-solid fa-mosque"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-900">Jadwal Sholat {{ $schoolName }}</h3>
        <p class="text-xs text-slate-500">{{ $prayer['timezone'] }} · {{ $prayer['is_fallback'] ? 'Jadwal perkiraan' : 'Jadwal madrasah' }}</p>
      </div>

      <div class="space-y-2.5 text-xs">
        @foreach ($prayer['times'] as $slot)
          <div class="flex items-center justify-between p-3 rounded-xl {{ $slot === $prayer['next_prayer'] ? 'bg-emerald-100 border-2 border-emerald-600' : 'bg-slate-50 border border-slate-200' }}">
            <span class="font-semibold text-slate-700">{{ $slot['name'] }}</span>
            <span class="font-extrabold text-slate-900">{{ $slot['time'] }}</span>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <!-- 2. Modal Pusat Unduhan (Download Center) -->
  <div id="downloadModal" role="dialog" aria-modal="true" aria-label="Pusat unduh" tabindex="-1" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-7 shadow-2xl relative max-h-[90vh] overflow-y-auto">
      <button data-close-dialog aria-label="Tutup dialog" class="absolute right-4 top-4 w-8 h-8 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="flex items-center gap-3 mb-5">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-kemenag-700 flex items-center justify-center text-xl shrink-0">
          <i class="fa-solid fa-cloud-arrow-down"></i>
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-900">Pusat Unduh Berkas &amp; Dokumen Resmi</h3>
          <p class="text-xs text-slate-500">Dokumen formulir, brosur PPDB, kalender akademik, dan tata tertib madrasah.</p>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-3.5 text-xs">
        @forelse ($downloads as $download)
          <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50 flex flex-col justify-between shadow-sm">
            <div>
              <p class="font-semibold text-kemenag-700">{{ $download['category_label'] }}</p>
              <h4 class="font-bold text-slate-900 text-sm">{{ $download['title'] }}</h4>
              <p class="text-slate-500 mt-1">{{ $download['description'] }}</p>
            </div>
            <a href="{{ $download['download_url'] }}" class="mt-3 bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-2 px-3 rounded-xl flex items-center justify-center gap-2">
              <i class="fa-solid fa-cloud-arrow-down" aria-hidden="true"></i> Unduh Berkas
            </a>
          </div>
        @empty
          <p class="text-slate-600">Belum ada berkas yang diterbitkan.</p>
        @endforelse
      </div>
      <a href="/pusat-unduh" class="inline-flex mt-4 text-sm font-bold text-kemenag-700">Buka Pusat Unduh</a>
    </div>
  </div>

  <!-- 3. Modal Zona Integritas & IKM -->
  <div id="ziModal" role="dialog" aria-modal="true" aria-label="Zona Integritas" tabindex="-1" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl relative">
      <button data-close-dialog aria-label="Tutup dialog" class="absolute right-4 top-4 w-8 h-8 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-kemenag-700 flex items-center justify-center text-xl shrink-0">
          <i class="fa-solid fa-shield-halved text-accent-gold"></i>
        </div>
        <div>
          <span class="bg-emerald-100 text-kemenag-800 text-[10px] font-extrabold px-2 py-0.5 rounded uppercase">Wilayah Bebas dari Korupsi (WBK)</span>
          <h3 class="text-base font-bold text-slate-900 mt-1">Zona Integritas &amp; Survei IKM</h3>
        </div>
      </div>

      <p class="text-slate-600 text-xs leading-relaxed mb-4">
        {{ $schoolName }} berkomitmen memberikan pelayanan prima tanpa gratifikasi, tanpa pungutan liar, dan transparan. Dukung mutu pelayanan kami melalui penilaian berikut:
      </p>

      <form method="post" action="{{ route('core.public.ikm.store') }}" class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-center">
        @csrf
        <input type="hidden" name="respondent_role" value="umum">
        <input type="hidden" name="service_name" value="PTSP">
        <span class="text-xs font-bold text-slate-800 block mb-2">Beri Nilai Kepuasan Layanan PTSP:</span>
        <div class="flex items-center justify-center gap-2 text-2xl text-amber-400 cursor-pointer mb-2">
          <button type="submit" name="rating" value="1" aria-label="Beri 1 bintang"><i class="fa-solid fa-star hover:scale-125 transition"  aria-hidden="true"></i></button>
          <button type="submit" name="rating" value="2" aria-label="Beri 2 bintang"><i class="fa-solid fa-star hover:scale-125 transition"  aria-hidden="true"></i></button>
          <button type="submit" name="rating" value="3" aria-label="Beri 3 bintang"><i class="fa-solid fa-star hover:scale-125 transition"  aria-hidden="true"></i></button>
          <button type="submit" name="rating" value="4" aria-label="Beri 4 bintang"><i class="fa-solid fa-star hover:scale-125 transition"  aria-hidden="true"></i></button>
          <button type="submit" name="rating" value="5" aria-label="Beri 5 bintang"><i class="fa-solid fa-star hover:scale-125 transition"  aria-hidden="true"></i></button>
        </div>
        <span id="ikmFeedback" aria-live="polite" @if(session('message')) data-ikm-submitted @endif class="text-[11px] text-kemenag-700 font-semibold block">{{ session('message') ?? 'Pilih jumlah bintang untuk menilai layanan PTSP.' }}</span>
      </form>

      <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
        <button type="button" data-open-dialog="ziAreaModal" aria-haspopup="dialog" class="text-kemenag-700 hover:text-kemenag-800 font-bold flex items-center gap-1">
          <i class="fa-solid fa-cubes-stacked text-amber-500"></i> Pelajari 6 Area ZI →
        </button>
        <a href="/layanan/lapor" class="text-rose-600 hover:text-rose-700 font-bold flex items-center gap-1">
          <i class="fa-solid fa-bullhorn"></i> Kanal Pengaduan
        </a>
      </div>
    </div>
  </div>

  <!-- Modal 6 Area Perubahan Zona Integritas (WBK & WBBM) -->
  <div id="ziAreaModal" role="dialog" aria-modal="true" aria-label="Enam area Zona Integritas" tabindex="-1" class="fixed inset-0 bg-slate-950/75 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-7 shadow-2xl relative max-h-[90vh] overflow-y-auto">
      <button data-close-dialog aria-label="Tutup dialog" class="absolute right-4 top-4 w-8 h-8 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center transition">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="flex items-center gap-3.5 mb-5 pb-4 border-b border-slate-100">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl shrink-0 shadow-xs">
          <i class="fa-solid fa-cubes-stacked text-accent-gold"></i>
        </div>
        <div>
          <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-wider">Reformasi Birokrasi</span>
          <h3 class="text-base sm:text-lg font-extrabold text-slate-900 mt-0.5 leading-snug">6 Area Perubahan Zona Integritas</h3>
          <p class="text-xs text-slate-500">Peta jalan pembangunan integritas &amp; pelayanan prima di {{ $schoolName }}</p>
        </div>
      </div>

      <div class="space-y-3 text-xs">
        <!-- Area 1 -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-400 transition flex items-start gap-3">
          <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs">1</span>
          <div>
            <div class="flex items-center justify-between">
              <strong class="text-slate-900 text-xs font-extrabold">Manajemen Perubahan</strong>
              <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.2 rounded-full">Pondasi Sikap</span>
            </div>
            <p class="text-slate-600 text-[11.5px] mt-1 leading-relaxed">
              Internalisasi budaya kerja 5 Nilai Budaya Kerja Kemenag, pembentukan Tim Kerja ZI, penetapan Agen Perubahan (Agent of Change), dan penandatanganan Pakta Integritas seluruh GTK.
            </p>
          </div>
        </div>

        <!-- Area 2 -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-400 transition flex items-start gap-3">
          <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs">2</span>
          <div>
            <div class="flex items-center justify-between">
              <strong class="text-slate-900 text-xs font-extrabold">Penataan Tatalaksana (SPBE Digital)</strong>
              <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.2 rounded-full">Digitalisasi</span>
            </div>
            <p class="text-slate-600 text-[11.5px] mt-1 leading-relaxed">
              Standard Operating Procedure (SOP) terstandar, implementasi PTSP Digital, persuratan elektronik, buku tamu digital, keterbukaan informasi publik, dan integrasi EMIS 4.0.
            </p>
          </div>
        </div>

        <!-- Area 3 -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-400 transition flex items-start gap-3">
          <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs">3</span>
          <div>
            <div class="flex items-center justify-between">
              <strong class="text-slate-900 text-xs font-extrabold">Penataan Sistem Manajemen SDM</strong>
              <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.2 rounded-full">Profesionalisme</span>
            </div>
            <p class="text-slate-600 text-[11.5px] mt-1 leading-relaxed">
              Peningkatan kompetensi berkelanjutan guru &amp; tendik, penegakan disiplin kehadiran presensi digital, pembinaan berkala, serta reward bagi GTK berprestasi.
            </p>
          </div>
        </div>

        <!-- Area 4 -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-400 transition flex items-start gap-3">
          <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs">4</span>
          <div>
            <div class="flex items-center justify-between">
              <strong class="text-slate-900 text-xs font-extrabold">Penguatan Akuntabilitas Kinerja</strong>
              <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.2 rounded-full">Transparansi</span>
            </div>
            <p class="text-slate-600 text-[11.5px] mt-1 leading-relaxed">
              Keterlibatan pimpinan dalam penyusunan Rencana Kinerja Tahunan (RKT), Perjanjian Kinerja (PK), LAKIP/LKjIP, serta publikasi realisasi anggaran DIPA &amp; BOS madrasah.
            </p>
          </div>
        </div>

        <!-- Area 5 -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-400 transition flex items-start gap-3">
          <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs">5</span>
          <div>
            <div class="flex items-center justify-between">
              <strong class="text-slate-900 text-xs font-extrabold">Penguatan Pengawasan &amp; WBS</strong>
              <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.2 rounded-full">Anti Korupsi</span>
            </div>
            <p class="text-slate-600 text-[11.5px] mt-1 leading-relaxed">
              Unit Pengendalian Gratifikasi (UPG), penanganan Benturan Kepentingan (Conflict of Interest), Whistleblowing System mandiri (Siap Lapor), serta kanal SP4N LAPOR.
            </p>
          </div>
        </div>

        <!-- Area 6 -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-400 transition flex items-start gap-3">
          <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs">6</span>
          <div>
            <div class="flex items-center justify-between">
              <strong class="text-slate-900 text-xs font-extrabold">Peningkatan Kualitas Pelayanan Publik</strong>
              <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.2 rounded-full">Pelayanan Prima</span>
            </div>
            <p class="text-slate-600 text-[11.5px] mt-1 leading-relaxed">
              Maklumat Pelayanan, ruang tunggu PTSP ramah disabilitas, kepastian standar operasional, inovasi layanan digital bebas antrean, dan Survei Kepuasan Masyarakat (IKM).
            </p>
          </div>
        </div>
      </div>

      <div class="mt-5 pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs">
        <span class="text-slate-500 text-[11px]">Ada indikasi penyimpangan?</span>
        <a href="/layanan/lapor" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 shadow-sm">
          <i class="fa-solid fa-shield-heart"></i>
          <span>Kirim Laporan Pengaduan</span>
        </a>
      </div>
    </div>
  </div>

  <!-- 4. Lightbox Modal Foto Galeri -->
  <div id="lightboxModal" role="dialog" aria-modal="true" aria-label="Foto galeri" tabindex="-1" class="fixed inset-0 bg-slate-950/90 z-50 hidden backdrop-blur-md flex items-center justify-center p-4">
    <div class="relative max-w-4xl w-full text-center">
      <button data-close-dialog aria-label="Tutup dialog" class="absolute -top-10 right-0 text-white text-2xl hover:text-accent-yellow">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <img id="lightboxImg" alt="Dokumentasi" class="max-h-[80vh] mx-auto rounded-2xl shadow-2xl border-2 border-emerald-500/30">
      <p id="lightboxCaption" class="text-white text-sm font-semibold mt-3"></p>
    </div>
  </div>

  <!-- Modal Baca Berita Lengkap -->
  <div id="beritaModal" role="dialog" aria-modal="true" aria-label="Baca berita" tabindex="-1" class="fixed inset-0 bg-slate-950/80 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl relative p-6">
      <button data-close-dialog aria-label="Tutup dialog" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div>
        <span id="bmKategori" class="bg-emerald-100 text-kemenag-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase"></span>
        <h2 id="bmJudul" class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2 leading-snug"></h2>
        <div class="flex items-center gap-3 text-xs text-slate-500 my-3">
          <span><i class="fa-regular fa-calendar text-kemenag-600 mr-1"></i> <span id="bmTanggal"></span></span>
          <span>•</span>
          <span><i class="fa-solid fa-user-pen text-slate-400 mr-1"></i> <span id="bmPenulis"></span></span>
        </div>
        <div class="h-60 sm:h-72 rounded-xl overflow-hidden my-4 border border-slate-100">
          <img id="bmGambar" alt="Thumbnail" class="w-full h-full object-cover">
        </div>
        <div id="bmIsi" class="text-slate-700 text-sm leading-relaxed space-y-3 whitespace-pre-line"></div>
      </div>
    </div>
  </div>

  <!-- 5. Quick Global Search Modal -->
  <div id="searchModal" role="dialog" aria-modal="true" aria-label="Cari layanan" tabindex="-1" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm flex items-start justify-center pt-20 p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 shadow-2xl relative">
      <form action="/e-layanan" method="get" class="flex items-center gap-2 border-b border-slate-200 pb-3">
        <i class="fa-solid fa-magnifying-glass text-slate-400" aria-hidden="true"></i>
        <input type="search" name="q" id="globalSearchInput" aria-label="Kata kunci layanan" placeholder="Cari layanan School Suite" class="w-full text-xs sm:text-sm outline-none">
        <button type="submit" class="bg-kemenag-700 text-white font-bold px-3 py-2 rounded-xl text-xs">Cari</button>
        <button type="button" data-close-dialog aria-label="Tutup dialog" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </form>
      <div id="portalSearchResults" class="pt-3 text-xs text-slate-500 space-y-1">
        <span class="block font-bold text-slate-700">Pintasan layanan</span>
        <div class="flex flex-wrap gap-1.5 pt-1">
          <a href="/layanan/ptsp" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">PTSP Terpadu</a>
          <a href="/layanan/legalisir" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Legalisir Ijazah</a>
          <a href="/layanan/mutasi" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Mutasi Siswa</a>
          <a href="/layanan/izin-siswa" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Surat Izin Siswa</a>
          <a href="/piket/izin-keluar" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Izin Keluar-Masuk</a>
          <a href="/pos-jaga" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Scan Pos Satpam</a>
          <a href="/lacak" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Lacak Berkas</a>
          <a href="/perpustakaan" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Perpustakaan Digital</a>
          <a href="/alumni" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Ikatan Alumni</a>
          <a href="/layanan/kelulusan" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Cek Kelulusan</a>
          <a href="/verifikasi" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Verifikasi Dokumen</a>
          <a href="/layanan/proposal" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Proposal &amp; LPJ</a>
          <a href="/login" class="px-2 py-1 rounded bg-emerald-100 text-kemenag-800 font-bold hover:bg-emerald-200">Login SIAKAD</a>
                    <a href="/login" class="px-2 py-1 rounded bg-indigo-100 text-indigo-800 font-bold hover:bg-indigo-200"><i class="fa-solid fa-shield mr-1"></i>Super Admin</a>
        </div>
        <p id="portalSearchEmpty" hidden>Tidak ada layanan yang cocok.</p>
      </div>
    </div>
  </div>
  <a class="portal-help" href="/kontak" aria-label="Hubungi layanan madrasah">
    <i class="fa-solid fa-comments" aria-hidden="true"></i>
  </a>
</body>
</html>
