  <a class="portal-skip-link bg-white rounded-xl px-4 py-2 font-bold" href="{{ ($contentBlocks['copy.blade_navigation']['href_205'] ?? '#main-content') }}">{{ ($contentBlocks['copy.blade_navigation']['text_001'] ?? 'Lewati ke konten utama') }}</a>

@if (request()->is('profil-sekolah', 'direktori-gtk'))
  <!-- ================= TOP BAR INFO ================= -->
  <aside data-portal-identity class="bg-kemenag-900 text-white text-xs border-b border-kemenag-700/60 hidden md:block">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2 flex items-center justify-between">
      <div class="flex items-center space-x-6">
        <span class="flex items-center gap-1.5 font-semibold text-emerald-100">
          <i class="fa-solid fa-id-card text-accent-yellow"></i> NPSN: {{ ($profile['npsn'] ?? null) ?: 'Belum tersedia' }}
        </span>
        <span class="flex items-center gap-1.5 text-emerald-100">
          <i class="fa-solid fa-location-dot text-accent-yellow"></i> {{ $schoolAddress }}
        </span>
        <span class="sr-only">{{ ($contentBlocks['copy.blade_navigation']['text_003'] ?? 'Hari ini:') }} <strong id="topbarLiveVisitors">{{ number_format($visits['today'], 0, ',', '.') }}</strong> {{ ($contentBlocks['copy.blade_navigation']['text_004'] ?? 'kunjungan') }}</span>
      </div>
      <div class="flex items-center space-x-4 text-emerald-200">
        @if (request()->is('direktori-gtk'))
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_206'] ?? '/profil-sekolah') }}" class="hover:text-accent-yellow transition">{{ ($contentBlocks['copy.blade_navigation']['text_006'] ?? 'Visi & Misi') }}</a>
        @else
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_207'] ?? '/direktori-gtk') }}" class="hover:text-accent-yellow transition">{{ ($contentBlocks['copy.blade_navigation']['text_007'] ?? 'Dewan Guru & GTK') }}</a>
        @endif
        <span>•</span>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_208'] ?? '/#zona-integritas') }}" class="hover:text-accent-yellow transition flex items-center gap-1">
          <i class="fa-solid fa-shield-heart text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_navigation']['text_008'] ?? 'Zona Integritas (WBK)') }}
        </a>
      </div>
    </div>
  </aside>

  <!-- ================= NAVBAR ATAS ================= -->
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="flex items-center justify-between h-20 gap-4">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="flex items-center gap-3 min-w-0 flex-1 xl:flex-initial">
          <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-11 h-11 object-contain shrink-0">
          <div class="min-w-0">
            <span class="block font-extrabold text-base sm:text-lg text-slate-900 tracking-tight leading-none truncate">{{ $schoolName }}</span>
            <span class="text-[11px] text-slate-500 font-medium tracking-wide block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_009'] ?? 'Portal Informasi & Akademik Terpadu') }}</span>
          </div>
        </a>

        <nav class="hidden xl:flex items-center gap-6 text-sm font-semibold text-slate-600">
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="hover:text-kemenag-700 transition">{{ ($contentBlocks['copy.blade_navigation']['text_010'] ?? 'Beranda') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_206'] ?? '/profil-sekolah') }}" class="{{ request()->is('profil-sekolah') ? 'text-kemenag-700 font-extrabold border-b-2 border-kemenag-700 pb-1' : 'hover:text-kemenag-700 transition' }}" @if (request()->is('profil-sekolah')) aria-current="page" @endif>{{ ($contentBlocks['copy.blade_navigation']['text_006'] ?? 'Visi & Misi') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_207'] ?? '/direktori-gtk') }}" class="{{ request()->is('direktori-gtk') ? 'text-kemenag-700 font-extrabold border-b-2 border-kemenag-700 pb-1' : 'hover:text-kemenag-700 transition' }}" @if (request()->is('direktori-gtk')) aria-current="page" @endif>{{ ($contentBlocks['copy.blade_navigation']['text_011'] ?? 'Guru & GTK') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_208'] ?? '/#zona-integritas') }}" class="hover:text-kemenag-700 transition flex items-center gap-1">
            <i class="fa-solid fa-shield-heart text-amber-500 text-xs"></i> {{ ($contentBlocks['copy.blade_navigation']['text_012'] ?? 'ZI-WBK') }}
          </a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_210'] ?? '/layanan/ptsp') }}" class="hover:text-kemenag-700 transition">{{ ($contentBlocks['copy.blade_navigation']['text_013'] ?? 'PTSP Digital') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_211'] ?? '/perpustakaan') }}" class="hover:text-kemenag-700 transition">{{ ($contentBlocks['copy.blade_navigation']['text_014'] ?? 'Perpustakaan') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_212'] ?? '/alumni') }}" class="hover:text-kemenag-700 transition">{{ ($contentBlocks['copy.blade_navigation']['text_015'] ?? 'Alumni') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_213'] ?? '/berita') }}" class="hover:text-kemenag-700 transition">{{ ($contentBlocks['copy.blade_navigation']['text_016'] ?? 'Berita') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_214'] ?? '/prestasi') }}" class="hover:text-kemenag-700 transition">{{ ($contentBlocks['copy.blade_navigation']['text_017'] ?? 'Prestasi') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_215'] ?? '/#kontak') }}" class="hover:text-kemenag-700 transition">{{ ($contentBlocks['copy.blade_navigation']['text_018'] ?? 'Kontak') }}</a>
        </nav>

        <div class="flex items-center gap-2 shrink-0">
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_216'] ?? '/login') }}" class="bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-1.5">
            <i class="fa-solid fa-user-shield text-accent-yellow"></i>
            <span>{{ ($contentBlocks['copy.blade_navigation']['text_019'] ?? 'Login Sistem') }}</span>
          </a>
        </div>
      </div>
    </div>
  </header>
@elseif (request()->is('layanan/ptsp'))
  <!-- ================= TOP BAR PTSP ================= -->
  <div data-portal-identity class="ptsp-topbar">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-1.5 flex items-center justify-between flex-wrap gap-3 text-xs">
      <div class="flex items-center flex-wrap gap-4">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="font-bold text-accent-yellow hover:underline flex items-center gap-1.5">
          <i class="fa-solid fa-circle-arrow-left"></i> {{ ($contentBlocks['copy.blade_navigation']['text_021'] ?? 'Web Madrasah') }}
        </a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_215'] ?? '/#kontak') }}" class="text-emerald-100 hover:text-accent-yellow flex items-center gap-1.5">
          <i class="fa-solid fa-envelope"></i> {{ ($contentBlocks['copy.blade_navigation']['text_018'] ?? 'Kontak') }}
        </a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_215'] ?? '/#kontak') }}" class="text-emerald-300 font-semibold hover:text-accent-yellow flex items-center gap-1.5">
          <i class="fa-solid fa-comments"></i> {{ ($contentBlocks['copy.blade_navigation']['text_022'] ?? 'Live Chat') }}
        </a>
        <span class="hidden lg:inline text-emerald-100">
          <i class="fa-solid fa-phone text-emerald-300"></i> {{ ($contentBlocks['copy.blade_navigation']['text_023'] ?? 'Call Centre:') }} <strong>{{ $schoolPhone }}</strong>
        </span>
      </div>
      <div class="ptsp-topbar-ticker hidden md:flex items-center overflow-hidden text-emerald-100">
        <div class="whitespace-nowrap animate-marquee-slow">
          <i class="fa-solid fa-bullhorn text-accent-yellow mr-1"></i>
          <strong>{{ ($contentBlocks['copy.blade_navigation']['text_024'] ?? 'Pemberitahuan Layanan Online PTSP:') }}</strong>
          {{ ($contentBlocks['copy.blade_navigation']['text_025'] ?? 'Kini pengurusan permohonan surat keterangan aktif, legalisir, mutasi siswa, dan rekomendasi beasiswa dapat diajukan secara online melalui portal PTSP.') }}
        </div>
      </div>
    </div>
  </div>

  <!-- ================= NAVBAR HIJAU PTSP ================= -->
  <header data-madrasah-navigation class="ptsp-header sticky top-0 z-40 shadow-md">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 h-16 flex items-center justify-between gap-4">
      <a href="{{ ($contentBlocks['copy.blade_navigation']['href_210'] ?? '/layanan/ptsp') }}" class="flex items-center gap-2.5 text-white min-w-0 flex-1 md:flex-initial">
        <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-9 h-9 object-contain shrink-0">
        <span class="font-extrabold text-sm sm:text-base tracking-tight uppercase truncate">{{ $schoolName }}</span>
        <span class="ptsp-badge shrink-0">{{ ($contentBlocks['copy.blade_navigation']['text_026'] ?? 'PTSP DIGITAL') }}</span>
      </a>

      <nav class="hidden lg:flex items-center gap-5 text-xs font-semibold text-emerald-50">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_210'] ?? '/layanan/ptsp') }}" class="text-white font-bold flex items-center gap-1.5" aria-current="page"><i class="fa-solid fa-house"></i> {{ ($contentBlocks['copy.blade_navigation']['text_027'] ?? 'Home') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_217'] ?? '#tentang-ptsp') }}" class="hover:text-accent-yellow transition">{{ ($contentBlocks['copy.blade_navigation']['text_028'] ?? 'Tentang PTSP') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_218'] ?? '/lacak?category=ptsp') }}" class="hover:text-accent-yellow transition">{{ ($contentBlocks['copy.blade_navigation']['text_029'] ?? 'Cek Permohonan') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_218'] ?? '/lacak?category=ptsp') }}" class="text-accent-yellow font-bold hover:underline flex items-center gap-1"><i class="fa-solid fa-magnifying-glass"></i> {{ ($contentBlocks['copy.blade_navigation']['text_030'] ?? 'Lacak Terpadu') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_219'] ?? '#katalog-ptsp') }}" class="hover:text-accent-yellow transition">{{ ($contentBlocks['copy.blade_navigation']['text_031'] ?? 'Jenis Layanan') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_215'] ?? '/#kontak') }}" class="hover:text-accent-yellow transition">{{ ($contentBlocks['copy.blade_navigation']['text_018'] ?? 'Kontak') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_220'] ?? '/layanan/ptsp/akun') }}" class="text-accent-yellow font-bold hover:underline flex items-center gap-1"><i class="fa-solid fa-circle-user"></i> {{ ($contentBlocks['copy.blade_navigation']['text_032'] ?? 'Masuk') }}</a>
      </nav>

      <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_221'] ?? 'Menu navigasi') }}" class="ptsp-mobile-toggle shrink-0 w-9 h-9 rounded-lg text-white flex items-center justify-center hover:bg-white/10 transition">
        <i class="fa-solid fa-bars"></i>
      </button>

      <span class="sr-only">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_222'] ?? '/layanan/buku-tamu') }}">{{ ($contentBlocks['copy.blade_navigation']['text_033'] ?? 'Buku Tamu') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_223'] ?? '/galeri/video') }}">{{ ($contentBlocks['copy.blade_navigation']['text_034'] ?? 'Video') }}</a>
      </span>
    </div>
  </header>
@elseif (request()->is('data-siswa', 'layanan/tracer-alumni', 'lacak', 'layanan/legalisir', 'layanan/mutasi', 'layanan/kelulusan', 'layanan/izin-siswa'))
  <!-- ================= TOP BAR APLIKASI DATABASE SISWA ================= -->
  <div data-portal-identity class="db-topbar text-slate-200 text-xs py-1.5 border-b border-white/10">
    <div class="db-nav-container flex items-center justify-between gap-4 flex-wrap">
      <div class="flex items-center gap-2 font-bold text-[12px]">
        <i class="fa-solid fa-graduation-cap text-amber-400"></i>
        <span>{{ strtoupper($schoolName) }} • TA 2026/2027</span>
      </div>
      <div class="flex items-center gap-2">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_224'] ?? '/login?portal=siswa') }}" class="inline-flex items-center gap-1.5 bg-amber-400 hover:bg-amber-300 text-slate-900 font-bold px-3 py-1 rounded-full text-[12px] shadow-sm transition">
          <i class="fa-solid fa-user-graduate"></i> {{ ($contentBlocks['copy.blade_navigation']['text_036'] ?? 'Portal Siswa / Wali') }}
        </a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_216'] ?? '/login') }}" class="inline-flex items-center gap-1.5 bg-transparent hover:bg-white/10 text-white font-semibold px-3 py-1 rounded-full text-[12px] border border-white/30 transition">
          <i class="fa-solid fa-lock"></i> {{ ($contentBlocks['copy.blade_navigation']['text_037'] ?? 'Login Admin') }}
        </a>
      </div>
    </div>
  </div>

  <!-- ================= NAVBAR APLIKASI DATABASE SISWA ================= -->
  <header data-madrasah-navigation class="db-navbar sticky top-0 z-40 bg-white border-b border-slate-200">
    <div class="db-nav-container db-nav-inner py-1 flex items-center justify-between gap-4">
      <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="flex items-center gap-2.5 shrink-0">
        <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="db-nav-logo">
        <div>
          <span class="db-nav-title block font-bold text-emerald-700 leading-tight">{{ ($contentBlocks['copy.blade_navigation']['text_038'] ?? 'APLIKASI DATABASE SISWA') }}</span>
          <span class="db-nav-sub block font-medium text-slate-500 uppercase tracking-tight">{{ strtoupper($schoolName) }}</span>
        </div>
      </a>

      <nav id="databaseNavigation" class="db-nav-desktop items-center gap-1" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_225'] ?? 'Menu aplikasi database siswa') }}">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="db-nav-link">{{ ($contentBlocks['copy.blade_navigation']['text_027'] ?? 'Home') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_226'] ?? '/data-siswa') }}" class="db-nav-link {{ request()->is('data-siswa') ? 'is-active' : '' }}">{{ ($contentBlocks['copy.blade_navigation']['text_039'] ?? 'Data Siswa') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_227'] ?? '/layanan/tracer-alumni') }}" class="db-nav-link {{ request()->is('layanan/tracer-alumni') ? 'is-active' : '' }}">{{ ($contentBlocks['copy.blade_navigation']['text_040'] ?? 'Data Alumni') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_228'] ?? '/layanan/legalisir') }}" class="db-nav-link {{ request()->is('layanan/legalisir') ? 'is-active' : '' }}">{{ ($contentBlocks['copy.blade_navigation']['text_041'] ?? 'Legalisir') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_229'] ?? '/layanan/izin-siswa') }}" class="db-nav-link {{ request()->is('layanan/izin-siswa') ? 'is-active' : '' }}">{{ ($contentBlocks['copy.blade_navigation']['text_042'] ?? 'Surat Izin') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_230'] ?? '/layanan/mutasi') }}" class="db-nav-link {{ request()->is('layanan/mutasi') ? 'is-active' : '' }}">{{ ($contentBlocks['copy.blade_navigation']['text_043'] ?? 'Mutasi') }}</a>
        @unless (request()->is('data-siswa', 'alumni'))
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_231'] ?? '/lacak') }}" class="db-nav-link {{ request()->is('lacak') ? 'is-active' : 'is-tracking' }}"><i class="fa-solid fa-magnifying-glass mr-1"></i>{{ ($contentBlocks['copy.blade_navigation']['text_030'] ?? 'Lacak Terpadu') }}</a>
        @endunless
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_232'] ?? '/layanan/lapor') }}" class="db-nav-link">{{ ($contentBlocks['copy.blade_navigation']['text_045'] ?? 'Pengaduan') }}</a>
        @if (request()->is('alumni'))
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_211'] ?? '/perpustakaan') }}" class="db-nav-link">{{ ($contentBlocks['copy.blade_navigation']['text_014'] ?? 'Perpustakaan') }}</a>
        @endif
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_233'] ?? '/layanan/kelulusan') }}" class="db-nav-link {{ request()->is('layanan/kelulusan') ? 'is-active' : '' }}">{{ ($contentBlocks['copy.blade_navigation']['text_047'] ?? 'Cek Kelulusan') }}</a>
      </nav>

      <button data-toggle-mobile aria-controls="{{ request()->is('layanan/izin-siswa') ? 'databaseNavigation' : 'mobileMenu' }}" aria-expanded="false" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_221'] ?? 'Menu navigasi') }}" class="db-nav-mobile-btn w-9 h-9 rounded-lg bg-slate-100 text-slate-700 items-center justify-center">
        <i class="fa-solid fa-bars"></i>
      </button>

      <span class="sr-only">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_222'] ?? '/layanan/buku-tamu') }}">{{ ($contentBlocks['copy.blade_navigation']['text_033'] ?? 'Buku Tamu') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_223'] ?? '/galeri/video') }}">{{ ($contentBlocks['copy.blade_navigation']['text_034'] ?? 'Video') }}</a>
      </span>
    </div>
  </header>
@elseif (request()->is('layanan/surat-dinas'))
  <!-- ================= NAVBAR E-SURAT MASUK ================= -->
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-emerald-900/95 backdrop-blur-md text-white border-b border-emerald-700/60 shadow-md">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 h-20 flex items-center justify-between gap-4">
      <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="flex items-center gap-3 min-w-0 flex-1 md:flex-initial">
        <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-10 h-10 object-contain shrink-0">
        <div class="min-w-0">
          <div class="flex items-center gap-2 min-w-0">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-200 truncate">{{ ($contentBlocks['copy.blade_navigation']['text_049'] ?? 'KEMENTERIAN AGAMA REPUBLIK INDONESIA') }}</span>
            <span class="hidden sm:inline-block shrink-0 bg-emerald-700 text-emerald-100 text-[9px] font-extrabold px-2 py-0.5 rounded-full border border-emerald-500/40">{{ ($contentBlocks['copy.blade_navigation']['text_050'] ?? 'PORTAL PERSURATAN RESMI') }}</span>
          </div>
          <span class="block font-extrabold text-base sm:text-lg text-white leading-tight truncate">{{ strtoupper($schoolName) }}</span>
          <span class="text-[11px] text-emerald-200/80 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_051'] ?? 'Layanan Penerimaan & Disposisi Surat Dinas Eksternal (E-Surat Masuk)') }}</span>
        </div>
      </a>

      <div class="hidden md:flex items-center gap-2.5 shrink-0">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 text-xs font-bold text-white transition">{{ ($contentBlocks['copy.blade_navigation']['text_052'] ?? 'Portal Utama') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_210'] ?? '/layanan/ptsp') }}" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 text-xs font-bold text-white transition">{{ ($contentBlocks['copy.blade_navigation']['text_053'] ?? 'Layanan PTSP') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_216'] ?? '/login') }}" class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-extrabold shadow-md transition">{{ ($contentBlocks['copy.blade_navigation']['text_054'] ?? 'Meja Petugas TU') }}</a>
      </div>

      <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_221'] ?? 'Menu navigasi') }}" class="md:hidden shrink-0 w-9 h-9 rounded-lg bg-white/10 text-white flex items-center justify-center">
        <i class="fa-solid fa-bars"></i>
      </button>

      <span class="sr-only">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_222'] ?? '/layanan/buku-tamu') }}">{{ ($contentBlocks['copy.blade_navigation']['text_033'] ?? 'Buku Tamu') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_223'] ?? '/galeri/video') }}">{{ ($contentBlocks['copy.blade_navigation']['text_034'] ?? 'Video') }}</a>
      </span>
    </div>
  </header>
@elseif (request()->is('layanan/buku-tamu*'))
  <!-- ================= HEADER BUKU TAMU DIGITAL ================= -->
  <div data-portal-identity class="parity-gb-ticker">
    <div class="parity-gb-container parity-gb-ticker-inner">
      <span class="parity-gb-ticker-label"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> {{ ($contentBlocks['copy.blade_navigation']['text_056'] ?? 'STATUS TAMU HARI INI :') }}</span>
      <div class="parity-gb-marquee-wrap">
        <span class="inline-block animate-marquee-slow parity-gb-ticker-text">
          {{ ($contentBlocks['copy.blade_navigation']['text_057'] ?? 'Resepsionis Digital Aktif   |   Silakan Lakukan Registrasi Tamu pada Formulir Kunjungan   |   Data Pengunjung Bersifat Privat & Terjaga Kerahasiaannya') }}
        </span>
      </div>
    </div>
  </div>
  <header data-madrasah-navigation class="parity-gb-header">
    <div class="parity-gb-container parity-gb-header-inner">
      <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="parity-gb-brand">
        <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="parity-gb-logo">
        <div class="parity-gb-title">
          <span class="parity-gb-subtitle">{{ ($contentBlocks['copy.blade_navigation']['text_058'] ?? 'SISTEM BUKU TAMU DIGITAL') }}</span>
          <span class="parity-gb-schoolname">{{ strtoupper($schoolName) }}</span>
        </div>
      </a>
      <div class="parity-gb-right">
        <div class="parity-gb-datetime">
          <span class="parity-gb-date">{{ now($schoolTimezone ?? 'Asia/Jakarta')->locale('id')->translatedFormat('l, j F Y') }}</span>
          <span data-guestbook-clock class="parity-gb-clock">{{ now($schoolTimezone ?? 'Asia/Jakarta')->format('H:i:s') }}</span>
        </div>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_234'] ?? '/zona-integritas') }}" class="parity-gb-ikm">{{ ($contentBlocks['copy.blade_navigation']['text_059'] ?? 'IKM') }}</a>
      </div>
      <span class="sr-only">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_222'] ?? '/layanan/buku-tamu') }}">{{ ($contentBlocks['copy.blade_navigation']['text_033'] ?? 'Buku Tamu') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_223'] ?? '/galeri/video') }}">{{ ($contentBlocks['copy.blade_navigation']['text_034'] ?? 'Video') }}</a>
      </span>
    </div>
  </header>
  <div class="parity-gb-info">
    <div class="parity-gb-container parity-gb-info-inner">
      <span class="parity-gb-info-label">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i> {{ ($contentBlocks['copy.blade_navigation']['text_060'] ?? 'INFORMASI') }}
      </span>
      <div class="parity-gb-marquee-wrap">
        <span class="inline-block animate-marquee-slow parity-gb-info-text">Jam Layanan {{ $schoolName }} : Senin - Kamis (07.30 - 15.00), Jumat (07.30 - 11.30). Terima kasih atas kerjasama Anda.</span>
      </div>
    </div>
  </div>
@elseif (request()->is('layanan/lapor*'))
  <!-- ================= NAVBAR SIAP LAPOR ================= -->
  <header data-madrasah-navigation class="parity-lapor-header">
    <div class="parity-lapor-container parity-lapor-header-inner">
      <a href="{{ ($contentBlocks['copy.blade_navigation']['href_232'] ?? '/layanan/lapor') }}" class="parity-lapor-brand">
        <span class="parity-lapor-brand-icon">
          <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
        </span>
        <div class="parity-lapor-brand-text">
          <div class="parity-lapor-brand-top">
            <span class="parity-lapor-brand-name">{{ ($contentBlocks['copy.blade_navigation']['text_062'] ?? 'Siap Lapor') }}</span>
            <span class="parity-lapor-brand-badge">{{ ($contentBlocks['copy.blade_navigation']['text_063'] ?? 'PORTAL RESMI') }}</span>
          </div>
          <span class="parity-lapor-brand-school">{{ strtoupper($schoolName) }}</span>
        </div>
      </a>

      <nav class="parity-lapor-nav" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_235'] ?? 'Navigasi Siap Lapor') }}">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_232'] ?? '/layanan/lapor') }}" class="parity-lapor-nav-link is-active">{{ ($contentBlocks['copy.blade_navigation']['text_010'] ?? 'Beranda') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_236'] ?? '#kategori-kasus') }}" class="parity-lapor-nav-link">{{ ($contentBlocks['copy.blade_navigation']['text_064'] ?? 'Kategori Kasus') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_237'] ?? '#alur-jaminan') }}" class="parity-lapor-nav-link">{{ ($contentBlocks['copy.blade_navigation']['text_065'] ?? 'Alur & Jaminan') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_238'] ?? '/lapor/transparansi') }}" class="parity-lapor-nav-link">{{ ($contentBlocks['copy.blade_navigation']['text_066'] ?? 'Transparansi Laporan') }}</a>
      </nav>

      <div class="parity-lapor-cta">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_239'] ?? '/lacak?category=aduan') }}" class="parity-lapor-btn-track">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <span>{{ ($contentBlocks['copy.blade_navigation']['text_067'] ?? 'Lacak Tiket') }}</span>
        </a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_240'] ?? '#form-lapor') }}" class="parity-lapor-btn-write">
          <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
          <span>{{ ($contentBlocks['copy.blade_navigation']['text_068'] ?? 'Tulis Laporan') }}</span>
        </a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_241'] ?? '/satgas/aduan') }}" class="parity-lapor-btn-staff" title="{{ ($contentBlocks['copy.blade_navigation']['title_242'] ?? 'Login Petugas Satgas Madrasah') }}" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_243'] ?? 'Login Petugas Satgas Madrasah') }}">
          <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
          <span class="parity-lapor-staff-label">{{ ($contentBlocks['copy.blade_navigation']['text_069'] ?? 'Petugas') }}</span>
        </a>
      </div>

      <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_221'] ?? 'Menu navigasi') }}" class="parity-lapor-mobile-toggle">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
      </button>

      <span class="sr-only">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_222'] ?? '/layanan/buku-tamu') }}">{{ ($contentBlocks['copy.blade_navigation']['text_033'] ?? 'Buku Tamu') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_223'] ?? '/galeri/video') }}">{{ ($contentBlocks['copy.blade_navigation']['text_034'] ?? 'Video') }}</a>
      </span>
    </div>
  </header>
  <div class="parity-lapor-ticker">
    <div class="parity-lapor-container parity-lapor-ticker-inner">
      <div class="parity-lapor-ticker-left">
        <span class="parity-lapor-info-pill">
          <span class="parity-lapor-pulse-dot" aria-hidden="true"></span>
          {{ ($contentBlocks['copy.blade_navigation']['text_070'] ?? 'INFO TERKINI') }}
        </span>
      </div>
      <div class="parity-lapor-ticker-wrap">
        <div class="parity-lapor-ticker-track">
          <span class="parity-lapor-ticker-item">
            <span class="parity-lapor-tag parity-lapor-tag--amber"><i class="fa-solid fa-bullhorn"></i> {{ ($contentBlocks['copy.blade_navigation']['text_071'] ?? 'PENGUMUMAN') }}</span>
            <strong class="parity-lapor-ticker-hl">{{ ($contentBlocks['copy.blade_navigation']['text_072'] ?? 'Deklarasi Madrasah Ramah Anak & Bebas Bullying:') }}</strong>
            <span>{{ $schoolName }} berkomitmen penuh memberantas segala bentuk intimidasi dan perundungan. Setiap laporan ditangani secara rahasia oleh Tim Satgas.</span>
            <span class="parity-lapor-sep">◆</span>
          </span>
          <span class="parity-lapor-ticker-item">
            <span class="parity-lapor-tag parity-lapor-tag--emerald"><i class="fa-solid fa-user-shield"></i> {{ ($contentBlocks['copy.blade_navigation']['text_073'] ?? 'JAMINAN WHISTLEBLOWER') }}</span>
            <strong class="parity-lapor-ticker-hl">{{ ($contentBlocks['copy.blade_navigation']['text_074'] ?? 'Identitas 100% Terlindungi:') }}</strong>
            <span>{{ ($contentBlocks['copy.blade_navigation']['text_075'] ?? 'Gunakan opsi "Kirim Sebagai Anonim" agar nama dan data pribadi Anda aman terlindungi.') }}</span>
            <span class="parity-lapor-sep">◆</span>
          </span>
          <span class="parity-lapor-ticker-item">
            <span class="parity-lapor-tag parity-lapor-tag--sky"><i class="fa-solid fa-stopwatch"></i> {{ ($contentBlocks['copy.blade_navigation']['text_076'] ?? 'RESPON SATGAS') }}</span>
            <strong class="parity-lapor-ticker-hl">{{ ($contentBlocks['copy.blade_navigation']['text_077'] ?? 'SOP Tindak Lanjut < 24 Jam:') }}</strong>
            <span>{{ ($contentBlocks['copy.blade_navigation']['text_078'] ?? 'Setiap aduan yang masuk diverifikasi maksimal 1 hari kerja untuk penanganan terukur.') }}</span>
            <span class="parity-lapor-sep">◆</span>
          </span>
          <span class="parity-lapor-ticker-item">
            <span class="parity-lapor-tag parity-lapor-tag--purple"><i class="fa-solid fa-ticket"></i> {{ ($contentBlocks['copy.blade_navigation']['text_079'] ?? 'LACAK STATUS') }}</span>
            <strong class="parity-lapor-ticker-hl">{{ ($contentBlocks['copy.blade_navigation']['text_080'] ?? 'Pantau Progres Tiket:') }}</strong>
            <span>{{ ($contentBlocks['copy.blade_navigation']['text_081'] ?? 'Simpan nomor tiket untuk memeriksa status perkembangan penanganan petugas secara real-time.') }}</span>
            <span class="parity-lapor-sep">◆</span>
          </span>
          <span class="parity-lapor-ticker-item" aria-hidden="true">
            <span class="parity-lapor-tag parity-lapor-tag--amber"><i class="fa-solid fa-bullhorn"></i> {{ ($contentBlocks['copy.blade_navigation']['text_071'] ?? 'PENGUMUMAN') }}</span>
            <strong class="parity-lapor-ticker-hl">{{ ($contentBlocks['copy.blade_navigation']['text_072'] ?? 'Deklarasi Madrasah Ramah Anak & Bebas Bullying:') }}</strong>
            <span>{{ $schoolName }} berkomitmen penuh memberantas segala bentuk intimidasi dan perundungan. Setiap laporan ditangani secara rahasia oleh Tim Satgas.</span>
            <span class="parity-lapor-sep">◆</span>
          </span>
          <span class="parity-lapor-ticker-item" aria-hidden="true">
            <span class="parity-lapor-tag parity-lapor-tag--emerald"><i class="fa-solid fa-user-shield"></i> {{ ($contentBlocks['copy.blade_navigation']['text_073'] ?? 'JAMINAN WHISTLEBLOWER') }}</span>
            <strong class="parity-lapor-ticker-hl">{{ ($contentBlocks['copy.blade_navigation']['text_074'] ?? 'Identitas 100% Terlindungi:') }}</strong>
            <span>{{ ($contentBlocks['copy.blade_navigation']['text_075'] ?? 'Gunakan opsi "Kirim Sebagai Anonim" agar nama dan data pribadi Anda aman terlindungi.') }}</span>
            <span class="parity-lapor-sep">◆</span>
          </span>
        </div>
      </div>
      <div class="parity-lapor-ticker-right">
        <i class="fa-solid fa-lock" aria-hidden="true"></i>
        <span>{{ ($contentBlocks['copy.blade_navigation']['text_082'] ?? '100% Terlindungi & Anonim') }}</span>
      </div>
    </div>
  </div>
@elseif (request()->is('alumni*'))
  <!-- ================= HEADER PORTAL ALUMNI (IKA) ================= -->
  <div data-portal-identity class="ika-topbar text-white text-xs border-b hidden md:block transition-colors duration-300">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center space-x-6">
        <span class="flex items-center gap-1.5 font-semibold text-theme-100">
          <i class="fa-solid fa-graduation-cap text-accent-yellow"></i> NPSN: {{ ($profile['npsn'] ?? null) ?: 'Belum tersedia' }}
        </span>
        <span class="flex items-center gap-1.5 text-theme-100">
          <i class="fa-solid fa-location-dot text-accent-yellow"></i> {{ $schoolAddress }}
        </span>
        <span class="flex items-center gap-1.5 text-theme-100">
          <i class="fa-solid fa-phone text-accent-yellow"></i> {{ $schoolPhone }}
        </span>
        <span class="hidden lg:inline-flex items-center gap-1.5 bg-slate-950/80 px-2.5 py-0.5 rounded-full border border-white/15 text-[11px] text-theme-100 font-semibold shadow-inner" title="{{ ($contentBlocks['copy.blade_navigation']['title_244'] ?? 'Pengunjung online di portal saat ini') }}">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
          </span>
          <span>{{ ($contentBlocks['copy.blade_navigation']['text_084'] ?? 'Online:') }} <strong id="topbarLiveVisitors" class="text-accent-yellow font-bold">{{ number_format($visits['today'] ?? 1, 0, ',', '.') }}</strong> {{ ($contentBlocks['copy.blade_navigation']['text_085'] ?? 'User') }}</span>
        </span>
      </div>
      <div class="flex items-center space-x-4">
        <div class="flex items-center gap-1.5 text-theme-100 bg-white/10 px-3 py-1 rounded-full border border-white/15">
          <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
          <span>{{ ($contentBlocks['copy.blade_navigation']['text_086'] ?? 'Layanan IKA:') }} <strong class="text-emerald-300">{{ ($contentBlocks['copy.blade_navigation']['text_087'] ?? 'Aktif') }}</strong></span>
        </div>
        <span class="ika-theme-picker"><i class="fa-solid fa-palette"></i> {{ ($contentBlocks['copy.blade_navigation']['text_088'] ?? 'TEMA:') }} <button type="button" data-ika-theme="blue" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_245'] ?? 'Tema biru') }}" aria-pressed="true"></button><button type="button" data-ika-theme="red" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_246'] ?? 'Tema merah') }}" aria-pressed="false"></button><button type="button" data-ika-theme="green" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_247'] ?? 'Tema hijau') }}" aria-pressed="false"></button></span>
        <div class="flex items-center space-x-3 text-slate-300">
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_248'] ?? '/kontak') }}" class="hover:text-accent-yellow transition" title="{{ ($contentBlocks['copy.blade_navigation']['title_249'] ?? 'Instagram Alumni') }}"><i class="fa-brands fa-instagram"></i></a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_248'] ?? '/kontak') }}" class="hover:text-accent-yellow transition" title="{{ ($contentBlocks['copy.blade_navigation']['title_250'] ?? 'LinkedIn Alumni') }}"><i class="fa-brands fa-linkedin-in"></i></a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_223'] ?? '/galeri/video') }}" class="hover:text-accent-yellow transition" title="{{ ($contentBlocks['copy.blade_navigation']['title_251'] ?? 'YouTube') }}"><i class="fa-brands fa-youtube"></i></a>
        </div>
      </div>
    </div>
  </div>
  <div class="ika-ticker text-white py-2 px-4 sm:px-8 lg:px-12 border-b text-xs overflow-hidden transition-colors duration-300">
    <div class="max-w-[1600px] mx-auto flex items-center gap-3">
      <span class="bg-gradient-to-r from-amber-500 to-amber-400 text-slate-950 font-extrabold px-3 py-1 rounded-lg uppercase tracking-wider shrink-0 flex items-center gap-1.5 shadow-sm text-[11px]">
        <i class="fa-solid fa-bullhorn animate-bounce"></i> {{ ($contentBlocks['copy.blade_navigation']['text_089'] ?? 'AGENDA IKA') }}
      </span>
      <div class="overflow-hidden w-full relative">
        <div class="flex whitespace-nowrap animate-marquee-slow hover:pause-on-hover">
          @for ($repeat = 0; $repeat < 2; $repeat++)
            <div class="flex items-center space-x-8 shrink-0 pr-8" @if ($repeat === 1) aria-hidden="true" @endif>
              <span class="inline-flex items-center text-slate-100 font-medium tracking-wide">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-2"></span>
                Selamat Datang di Portal Resmi Ikatan Keluarga Alumni (IKA) {{ strtoupper($schoolName) }}
              </span>
              <span class="inline-flex items-center text-slate-100 font-medium tracking-wide">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-2"></span>
                {{ ($contentBlocks['copy.blade_navigation']['text_090'] ?? 'Pengisian Tracer Study Mandiri Lulusan Terbuka Untuk Seluruh Angkatan') }}
              </span>
              <span class="inline-flex items-center text-slate-100 font-medium tracking-wide">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-2"></span>
                {{ ($contentBlocks['copy.blade_navigation']['text_091'] ?? 'Tersedia Info Lowongan Karir Aktif & Peluang Beasiswa Terverifikasi') }}
              </span>
              <span class="inline-flex items-center text-slate-100 font-medium tracking-wide">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-2"></span>
                {{ ($contentBlocks['copy.blade_navigation']['text_092'] ?? 'Layanan Pengajuan Legalisir Ijazah & Transkrip Nilai Online Buka 24 Jam') }}
              </span>
              <span class="inline-flex items-center text-slate-100 font-medium tracking-wide">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-2"></span>
                {{ ($contentBlocks['copy.blade_navigation']['text_093'] ?? 'Dapatkan E-KTA (Kartu Tanda Anggota) Digital Resmi Ber-QR Code Secara Mandiri') }}
              </span>
            </div>
          @endfor
        </div>
      </div>
    </div>
  </div>
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-xs">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-3 flex items-center justify-between gap-4">
      <a href="{{ ($contentBlocks['copy.blade_navigation']['href_212'] ?? '/alumni') }}" class="flex items-center gap-3 min-w-0 flex-1 md:flex-initial group">
        <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-11 h-11 sm:w-12 sm:h-12 object-contain shrink-0 drop-shadow-sm">
        <div class="min-w-0">
          <div class="flex items-center gap-1.5 min-w-0">
            <span class="font-black text-sm sm:text-base tracking-tight text-slate-900 truncate">{{ ($contentBlocks['copy.blade_navigation']['text_094'] ?? 'IKATAN KELUARGA ALUMNI (IKA)') }}</span>
            <span class="hidden sm:inline-block shrink-0 bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase">{{ ($contentBlocks['copy.blade_navigation']['text_095'] ?? 'Official Hub') }}</span>
          </div>
          <span class="text-[11px] text-slate-500 font-semibold block truncate max-w-[260px] sm:max-w-md">{{ strtoupper($schoolName) }} • Penelusuran Lulusan &amp; Jejaring Karir</span>
        </div>
      </a>
      <div class="flex items-center space-x-2 sm:space-x-3 ml-auto">
        <button type="button" data-open-dialog="searchModal" aria-haspopup="dialog" class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-100 text-slate-600 hover:bg-blue-50 hover:text-blue-700 flex items-center justify-center transition shadow-sm" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_252'] ?? 'Cari alumni') }}" title="{{ ($contentBlocks['copy.blade_navigation']['title_253'] ?? 'Cari Alumni / Info') }}">
          <i class="fa-solid fa-magnifying-glass text-xs sm:text-sm"></i>
        </button>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_254'] ?? '/layanan/tracer-alumni#tracer-form') }}" data-ika-tab="tracer" class="inline-flex items-center gap-1.5 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white text-xs sm:text-[13px] font-bold px-3.5 py-2 rounded-xl shadow-md transition-all border border-amber-300/30">
          <i class="fa-solid fa-pen-to-square text-amber-100 text-xs"></i>
          <span>{{ ($contentBlocks['copy.blade_navigation']['text_096'] ?? 'Isi Tracer Study') }}</span>
        </a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_255'] ?? '/layanan/tracer-alumni#alumni-kta') }}" data-ika-tab="kta" class="hidden sm:inline-flex items-center gap-1.5 ika-btn-theme text-white text-xs sm:text-[13px] font-bold px-3.5 py-2 rounded-xl shadow-md transition-all">
          <i class="fa-solid fa-id-card text-amber-300 text-xs"></i>
          <span>{{ ($contentBlocks['copy.blade_navigation']['text_097'] ?? 'KTA Digital') }}</span>
        </a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_228'] ?? '/layanan/legalisir') }}" class="hidden lg:inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs sm:text-[13px] font-bold px-3.5 py-2 rounded-xl transition">
          <i class="fa-solid fa-certificate text-rose-600"></i>
          <span>{{ ($contentBlocks['copy.blade_navigation']['text_098'] ?? 'Legalisir Ijazah ↗') }}</span>
        </a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 flex items-center justify-center transition" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_256'] ?? 'Website sekolah') }}" title="{{ ($contentBlocks['copy.blade_navigation']['title_257'] ?? 'Kembali ke Beranda Website Sekolah') }}">
          <i class="fa-solid fa-house text-xs sm:text-sm"></i>
        </a>
        <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_221'] ?? 'Menu navigasi') }}" class="md:hidden shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center hover:bg-slate-200 transition">
          <i class="fa-solid fa-bars text-sm sm:text-base"></i>
        </button>
      </div>
    </div>
    <div class="hidden md:block bg-slate-50/90 border-t border-slate-200/80">
      <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-1.5 flex items-center justify-between text-[13px] font-semibold text-slate-700">
        <nav class="flex flex-wrap items-center gap-x-6 gap-y-1">
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_212'] ?? '/alumni') }}" aria-current="page" class="text-blue-700 hover:text-blue-800 transition py-1 flex items-center gap-1.5 font-bold"><i class="fa-solid fa-house text-xs text-blue-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_010'] ?? 'Beranda') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_258'] ?? '/alumni#direktori-alumni') }}" data-ika-tab="directory" class="hover:text-blue-700 transition py-1 flex items-center gap-1"><i class="fa-solid fa-users text-xs text-blue-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_099'] ?? 'Direktori Alumni') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_254'] ?? '/layanan/tracer-alumni#tracer-form') }}" data-ika-tab="tracer" class="hover:text-blue-700 transition py-1 flex items-center gap-1"><i class="fa-solid fa-pen-nib text-xs text-amber-500"></i> {{ ($contentBlocks['copy.blade_navigation']['text_100'] ?? 'Tracer Study') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_259'] ?? '/alumni?section=careers#ika-portal-tabs') }}" data-ika-tab="careers" class="hover:text-blue-700 transition py-1 flex items-center gap-1"><i class="fa-solid fa-briefcase text-xs text-emerald-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_101'] ?? 'Bursa Karir') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_260'] ?? '/alumni?section=scholarships#ika-portal-tabs') }}" data-ika-tab="scholarships" class="hover:text-blue-700 transition py-1 flex items-center gap-1"><i class="fa-solid fa-graduation-cap text-xs text-purple-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_102'] ?? 'Beasiswa') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_255'] ?? '/layanan/tracer-alumni#alumni-kta') }}" data-ika-tab="kta" class="hover:text-blue-700 transition py-1 flex items-center gap-1"><i class="fa-solid fa-id-card text-xs text-amber-500"></i> {{ ($contentBlocks['copy.blade_navigation']['text_097'] ?? 'KTA Digital') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_228'] ?? '/layanan/legalisir') }}" class="hover:text-rose-600 text-rose-700 transition py-1 flex items-center gap-1 font-bold"><i class="fa-solid fa-certificate text-xs text-rose-500"></i> {{ ($contentBlocks['copy.blade_navigation']['text_103'] ?? 'Legalisir Online ↗') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_261'] ?? '#program-ika') }}" class="hover:text-blue-700 transition py-1">{{ ($contentBlocks['copy.blade_navigation']['text_104'] ?? 'Program IKA') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_262'] ?? '/alumni#statistik-alumni') }}" data-ika-modal="stats" class="hover:text-blue-700 transition py-1 flex items-center gap-1"><i class="fa-solid fa-chart-pie text-xs text-blue-500"></i> {{ ($contentBlocks['copy.blade_navigation']['text_105'] ?? 'Statistik') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_263'] ?? '#faq-alumni') }}" class="hover:text-blue-700 transition py-1">{{ ($contentBlocks['copy.blade_navigation']['text_106'] ?? 'FAQ') }}</a>
        </nav>
        <div class="hidden lg:flex items-center gap-3 text-xs text-slate-500 font-medium">
          <span class="inline-flex items-center gap-1.5 text-emerald-700 font-bold">
            <i class="fa-solid fa-shield-halved text-emerald-600"></i>
            <span>{{ ($contentBlocks['copy.blade_navigation']['text_107'] ?? 'Portal Resmi Alumni Terintegrasi') }}</span>
          </span>
        </div>
      </div>
    </div>
    <span class="sr-only">
      <a href="{{ ($contentBlocks['copy.blade_navigation']['href_222'] ?? '/layanan/buku-tamu') }}">{{ ($contentBlocks['copy.blade_navigation']['text_033'] ?? 'Buku Tamu') }}</a>
      <a href="{{ ($contentBlocks['copy.blade_navigation']['href_223'] ?? '/galeri/video') }}">{{ ($contentBlocks['copy.blade_navigation']['text_034'] ?? 'Video') }}</a>
    </span>
  </header>
@elseif (request()->is('perpustakaan*'))
  <!-- ================= HEADER E-PERPUSTAKAAN DIGITAL ================= -->
  <div data-portal-identity class="bg-kemenag-900 text-emerald-100 text-xs py-2 border-b border-kemenag-700/60 hidden md:block">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 flex items-center justify-between gap-4">
      <div class="flex items-center gap-5">
        <span class="font-bold">{{ strtoupper($schoolName) }} • E-LIBRARY</span>
        <span>{{ now($schoolTimezone ?? 'Asia/Jakarta')->locale('id')->translatedFormat('j F Y') }}</span>
        <span class="bg-kemenag-950/70 px-2.5 py-0.5 rounded-full border border-emerald-500/30">{{ ($contentBlocks['copy.blade_navigation']['text_084'] ?? 'Online:') }} <strong>{{ number_format($visits['today'] ?? 1, 0, ',', '.') }}</strong></span>
      </div>
      <div class="flex items-center gap-4">
        <span>{{ ($contentBlocks['copy.blade_navigation']['text_109'] ?? 'Waktu Sholat:') }} <strong class="text-accent-yellow">{{ $prayer['next_prayer']['name'] }} {{ $prayer['next_prayer']['time'] }}</strong></span>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="hover:text-accent-yellow transition">{{ ($contentBlocks['copy.blade_navigation']['text_110'] ?? 'Beranda Utama') }}</a>
      </div>
    </div>
  </div>
  <div class="bg-kemenag-950 text-white py-2 px-4 sm:px-8 lg:px-12 border-b border-emerald-800/60 hidden md:block">
    <div class="max-w-[1600px] mx-auto flex items-center gap-3 text-xs">
      <span class="bg-amber-400 text-slate-950 font-black px-2.5 py-0.5 rounded-lg text-[10px] shrink-0">{{ ($contentBlocks['copy.blade_navigation']['text_111'] ?? 'WARTA PUSTAKA') }}</span>
      <span class="text-emerald-100 truncate">{{ ($contentBlocks['copy.blade_navigation']['text_112'] ?? 'E-Library 24 Jam: Tersedia koleksi buku teks, referensi keislaman, dan modul digital bebas akses untuk warga madrasah.') }}</span>
    </div>
  </div>
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2.5 flex items-center justify-between gap-4">
      <a href="{{ ($contentBlocks['copy.blade_navigation']['href_211'] ?? '/perpustakaan') }}" class="flex items-center gap-3 min-w-0 flex-1 md:flex-initial">
        <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-9 h-9 object-contain shrink-0">
        <div class="min-w-0">
          <div class="flex items-center gap-2 min-w-0">
            <span class="font-extrabold text-sm sm:text-base text-slate-900 truncate">{{ strtoupper($schoolName) }}</span>
            <span class="shrink-0 bg-amber-100 text-amber-800 text-[9px] font-extrabold px-2 py-0.5 rounded-md">{{ ($contentBlocks['copy.blade_navigation']['text_113'] ?? 'E-LIBRARY') }}</span>
          </div>
          <span class="text-[10px] text-slate-500 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_114'] ?? 'E-Perpustakaan Digital • Khazanah Literasi Madrasah') }}</span>
        </div>
      </a>
      <div class="hidden md:flex items-center gap-2">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_264'] ?? '#kunjungan-pustaka') }}" class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold">{{ ($contentBlocks['copy.blade_navigation']['text_033'] ?? 'Buku Tamu') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_224'] ?? '/login?portal=siswa') }}" class="px-3.5 py-1.5 rounded-xl bg-amber-500 text-white text-xs font-extrabold">{{ ($contentBlocks['copy.blade_navigation']['text_115'] ?? 'Login Siswa') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_265'] ?? '/perpustakaan/kelola') }}" class="px-3.5 py-1.5 rounded-xl bg-kemenag-700 text-white text-xs font-extrabold">{{ ($contentBlocks['copy.blade_navigation']['text_116'] ?? 'Pustakawan') }}</a>
      </div>
      <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_221'] ?? 'Menu navigasi') }}" class="md:hidden shrink-0 w-9 h-9 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
    <div class="hidden md:block bg-slate-50 border-t border-slate-200">
      <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2 flex items-center justify-between text-xs font-semibold text-slate-700">
        <nav class="flex flex-wrap items-center gap-5">
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_211'] ?? '/perpustakaan') }}" class="text-emerald-700 font-bold">{{ ($contentBlocks['copy.blade_navigation']['text_117'] ?? 'Beranda Perpus') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_266'] ?? '#katalog-pustaka') }}" class="hover:text-emerald-700">{{ ($contentBlocks['copy.blade_navigation']['text_118'] ?? 'Semua Katalog (OPAC)') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_266'] ?? '#katalog-pustaka') }}" class="text-amber-600 font-bold">{{ ($contentBlocks['copy.blade_navigation']['text_119'] ?? 'E-Book Digital') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_264'] ?? '#kunjungan-pustaka') }}" class="hover:text-emerald-700">{{ ($contentBlocks['copy.blade_navigation']['text_120'] ?? 'Layanan Mandiri') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_231'] ?? '/lacak') }}" class="hover:text-emerald-700">{{ ($contentBlocks['copy.blade_navigation']['text_121'] ?? 'Tracking Berkas') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="hover:text-emerald-700">{{ ($contentBlocks['copy.blade_navigation']['text_122'] ?? 'Portal Madrasah') }}</a>
        </nav>
      </div>
    </div>
    <span class="sr-only">
      <a href="{{ ($contentBlocks['copy.blade_navigation']['href_222'] ?? '/layanan/buku-tamu') }}">{{ ($contentBlocks['copy.blade_navigation']['text_033'] ?? 'Buku Tamu') }}</a>
      <a href="{{ ($contentBlocks['copy.blade_navigation']['href_223'] ?? '/galeri/video') }}">{{ ($contentBlocks['copy.blade_navigation']['text_034'] ?? 'Video') }}</a>
    </span>
  </header>
@else
  <!-- ================= TOP BAR INFO & JADWAL SHOLAT ================= -->
  <aside data-portal-identity class="bg-kemenag-900 text-white text-xs border-b border-kemenag-700/60 hidden md:block">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center space-x-6">
        <span class="flex items-center gap-1.5 font-semibold text-emerald-100">
          <i class="fa-solid fa-id-card text-accent-yellow"></i> NPSN: {{ ($profile['npsn'] ?? null) ?: 'Belum tersedia' }}        </span>
        <span class="flex items-center gap-1.5 text-emerald-100">
          <i class="fa-solid fa-location-dot text-accent-yellow"></i> {{ $schoolAddress }}        </span>
        <span class="flex items-center gap-1.5 text-emerald-100">
          <i class="fa-solid fa-phone text-accent-yellow"></i> {{ $schoolPhone }}        </span>

        <!-- Live Traffic Counter Badge (Top Bar) -->
        <span class="hidden lg:inline-flex items-center gap-1.5 bg-kemenag-950/80 px-2.5 py-0.5 rounded-full border border-emerald-500/40 text-[11px] text-emerald-100 font-semibold shadow-inner" title="{{ ($contentBlocks['copy.blade_navigation']['title_267'] ?? 'Kunjungan portal hari ini') }}">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
          </span>
          <span>{{ ($contentBlocks['copy.blade_navigation']['text_003'] ?? 'Hari ini:') }} <strong id="topbarLiveVisitors" class="text-accent-yellow font-bold">{{ number_format($visits['today'], 0, ',', '.') }}</strong> {{ ($contentBlocks['copy.blade_navigation']['text_004'] ?? 'kunjungan') }}</span>
        </span>
      </div>

      <div class="flex items-center space-x-4">
        <!-- Widget Sholat Dinamis setempat -->
        <div class="flex items-center gap-2 bg-kemenag-800/90 px-3 py-1 rounded-full border border-kemenag-600/50 cursor-pointer hover:bg-kemenag-700 transition" role="button" tabindex="0" data-open-dialog="prayerModal" aria-haspopup="dialog" title="{{ ($contentBlocks['copy.blade_navigation']['title_268'] ?? 'Klik untuk lihat jadwal lengkap hari ini') }}">
          <i class="fa-solid fa-mosque text-accent-yellow"></i>
          <span class="text-emerald-100">{{ ($contentBlocks['copy.blade_navigation']['text_109'] ?? 'Waktu Sholat:') }}</span>
          <span id="activePrayerDisplay" class="font-bold text-accent-yellow">{{ $prayer['next_prayer']['name'] }} {{ $prayer['next_prayer']['time'] }}</span>
          <i class="fa-solid fa-chevron-down text-[10px] text-emerald-300"></i>
        </div>

        <div class="flex items-center space-x-3 text-emerald-200">
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_248'] ?? '/kontak') }}" class="hover:text-accent-yellow transition" title="{{ ($contentBlocks['copy.blade_navigation']['title_269'] ?? 'Facebook') }}"><i class="fa-brands fa-facebook"></i></a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_248'] ?? '/kontak') }}" class="hover:text-accent-yellow transition" title="{{ ($contentBlocks['copy.blade_navigation']['title_270'] ?? 'Instagram') }}"><i class="fa-brands fa-instagram"></i></a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_223'] ?? '/galeri/video') }}" class="hover:text-accent-yellow transition" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_271'] ?? 'Galeri video') }}"><i class="fa-brands fa-youtube"></i></a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_248'] ?? '/kontak') }}" class="hover:text-accent-yellow transition" title="{{ ($contentBlocks['copy.blade_navigation']['title_272'] ?? 'TikTok') }}"><i class="fa-brands fa-tiktok"></i></a>
        </div>
      </div>
    </div>
  </aside>

  <!-- ================= RUNNING NEWS TICKER (STANDAR MODERN SELARAS) ================= -->
    <div data-portal-ticker class="bg-gradient-to-r from-kemenag-950 via-kemenag-900 to-kemenag-950 text-white py-2.5 sm:py-3 border-b border-emerald-800/80 shadow-md overflow-hidden relative select-none z-30">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 flex items-center gap-3.5">

      <!-- Fixed Left Label Badge -->
      <div class="flex items-center gap-2 shrink-0 z-10 bg-kemenag-900/95 backdrop-blur-md pr-3 py-1 border-r border-emerald-700/60">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-accent-gold text-slate-950 text-xs font-bold tracking-wider uppercase shadow-md shadow-black/20">
          <span class="flex h-2 w-2 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-slate-950 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-slate-950"></span>
          </span>
          {{ ($contentBlocks['copy.blade_navigation']['text_071'] ?? 'PENGUMUMAN') }}
        </span>
      </div>

      <!-- Continuous Smooth Scrolling Track -->
      <div class="ticker-wrap flex-1 overflow-hidden relative">
        <div class="flex animate-marquee-slow hover:pause-on-hover whitespace-nowrap items-center will-change-transform" style="-webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-rose-400 text-slate-950">
                <i class="fa-solid fa-shield-halved text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_123'] ?? 'TOLAK GRATIFIKASI') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_124'] ?? 'Maklumat ZI: Kami Menolak Segala Bentuk Gratifikasi & Pungli | Seluruh Layanan PTSP Bebas Biaya (Rp 0,-)') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-amber-300 text-slate-950">
                <i class="fa-solid fa-bullhorn text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_125'] ?? 'INFO UTAMA') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_126'] ?? 'Selamat Datang di Portal Resmi Madrasah') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-emerald-300 text-slate-950">
                <i class="fa-solid fa-graduation-cap text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_127'] ?? 'PPDB ONLINE') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_128'] ?? 'Layanan PTSP Aktif') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-sky-200 text-slate-950">
                <i class="fa-solid fa-stamp text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_026'] ?? 'PTSP DIGITAL') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_129'] ?? 'Informasi jalur dan jadwal Penerimaan Peserta Didik Baru (PPDB) tersedia di portal PPDB') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-yellow-200 text-slate-950">
                <i class="fa-solid fa-award text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_130'] ?? 'PRESTASI') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_131'] ?? 'Layanan Administrasi PTSP Mandiri Buka Senin - Jumat Bebas Biaya (Rp 0,-)') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-purple-200 text-slate-950">
                <i class="fa-solid fa-calendar-check text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_132'] ?? 'AGENDA') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_133'] ?? 'Madrasah Aliyah Negeri Riset & Adiwiyata Mandiri: Meneguhkan Tradisi Prestasi & Akhlakul Karimah') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-rose-400 text-slate-950">
                <i class="fa-solid fa-shield-halved text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_123'] ?? 'TOLAK GRATIFIKASI') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_134'] ?? 'Konsultasi & Layanan Cepat Tersedia Melalui Fitur Bantuan di Pojok Kanan Bawah') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-rose-400 text-slate-950">
                <i class="fa-solid fa-shield-halved text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_123'] ?? 'TOLAK GRATIFIKASI') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_124'] ?? 'Maklumat ZI: Kami Menolak Segala Bentuk Gratifikasi & Pungli | Seluruh Layanan PTSP Bebas Biaya (Rp 0,-)') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-amber-300 text-slate-950">
                <i class="fa-solid fa-bullhorn text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_125'] ?? 'INFO UTAMA') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_126'] ?? 'Selamat Datang di Portal Resmi Madrasah') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-emerald-300 text-slate-950">
                <i class="fa-solid fa-graduation-cap text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_127'] ?? 'PPDB ONLINE') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_128'] ?? 'Layanan PTSP Aktif') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-sky-200 text-slate-950">
                <i class="fa-solid fa-stamp text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_026'] ?? 'PTSP DIGITAL') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_129'] ?? 'Informasi jalur dan jadwal Penerimaan Peserta Didik Baru (PPDB) tersedia di portal PPDB') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-yellow-200 text-slate-950">
                <i class="fa-solid fa-award text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_130'] ?? 'PRESTASI') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_131'] ?? 'Layanan Administrasi PTSP Mandiri Buka Senin - Jumat Bebas Biaya (Rp 0,-)') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-purple-200 text-slate-950">
                <i class="fa-solid fa-calendar-check text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_132'] ?? 'AGENDA') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_133'] ?? 'Madrasah Aliyah Negeri Riset & Adiwiyata Mandiri: Meneguhkan Tradisi Prestasi & Akhlakul Karimah') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                      <div class="inline-flex items-center gap-2.5 mx-6 text-[13px] sm:text-[14px] text-white cursor-default">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-bold uppercase tracking-wider shrink-0 shadow-xs bg-rose-400 text-slate-950">
                <i class="fa-solid fa-shield-halved text-[10px]"></i>
                {{ ($contentBlocks['copy.blade_navigation']['text_123'] ?? 'TOLAK GRATIFIKASI') }}              </span>
              <span class="text-white font-normal tracking-wide">{{ ($contentBlocks['copy.blade_navigation']['text_134'] ?? 'Konsultasi & Layanan Cepat Tersedia Melalui Fitur Bantuan di Pojok Kanan Bawah') }}</span>
              <span class="text-emerald-400/50 font-medium ml-4 text-xs select-none">◆</span>
            </div>
                  </div>
      </div>

      <!-- Quick Date & Live Status on the Right -->
      <div class="hidden xl:flex items-center gap-2 text-xs text-emerald-200 shrink-0 font-medium z-10 bg-kemenag-950/80 border border-emerald-500/30 px-3 py-1.5 rounded-xl shadow-xs">
        <i class="fa-regular fa-clock text-accent-yellow text-xs"></i>
        <span>{{ ($contentBlocks['copy.blade_navigation']['text_135'] ?? 'Layanan Digital Aktif 24 Jam') }}</span>
      </div>

    </div>
  </div>

  <!-- ================= MAIN NAVIGATION BAR (2 BARIS ELEGAN) ================= -->
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-white/95 backdrop-blur-md shadow-sm border-b border-slate-200">
    <!-- Baris 1: Brand Logo & Tombol Aksi Utama -->
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2.5 flex items-center justify-between gap-4 portal-header-row">

      <!-- Brand Logo -->
      <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="portal-brand flex items-center gap-2.5 sm:gap-3 group">
                  <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-9 h-9 sm:w-10 sm:h-10 object-contain group-hover:scale-105 transition-transform">
                <div class="flex flex-col justify-center">
          <p class="text-sm sm:text-base lg:text-lg font-bold font-sans text-slate-900 tracking-tight leading-tight group-hover:text-kemenag-700 transition-colors">
            {{ $schoolName }}          </p>
          <span class="text-[10px] text-slate-500 font-medium -mt-0.5 block">{{ ($contentBlocks['copy.blade_navigation']['text_009'] ?? 'Portal Informasi & Akademik Terpadu') }}</span>
        </div>
      </a>

      <!-- Quick Action Right: Daftar Sekolah, Search, Login Siswa, Login, Mobile Toggle -->
      <div class="portal-header-actions flex items-center space-x-2 sm:space-x-3 ml-auto">

        <button data-open-dialog="searchModal" aria-haspopup="dialog" class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-kemenag-700 flex items-center justify-center transition shadow-sm" title="{{ ($contentBlocks['copy.blade_navigation']['title_273'] ?? 'Cari di Portal') }}">
          <i class="fa-solid fa-magnifying-glass text-xs sm:text-sm"></i>
        </button>

        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_216'] ?? '/login') }}" class="portal-catalogue-login"><i class="fa-solid fa-user-shield" aria-hidden="true"></i> {{ ($contentBlocks['copy.blade_navigation']['text_019'] ?? 'Login Sistem') }}</a>
        <!-- Tombol Login Siswa (Nuansa Oranye-Emas Landing Page) -->
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_224'] ?? '/login?portal=siswa') }}" class="portal-login-action inline-flex items-center gap-1.5 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white text-xs sm:text-[13px] font-bold px-3.5 py-2 rounded-xl shadow-md hover:shadow-orange-500/30 transition-all border border-amber-300/30" title="{{ ($contentBlocks['copy.blade_navigation']['title_274'] ?? 'Masuk Portal Siswa') }}">
          <i class="fa-solid fa-user-graduate text-amber-100 text-xs"></i>
          <span>{{ ($contentBlocks['copy.blade_navigation']['text_115'] ?? 'Login Siswa') }}</span>
        </a>

        <!-- Tombol Login Admin (Nuansa Hijau Kemenag Tegas Berbeda) -->
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_216'] ?? '/login') }}" class="portal-login-action inline-flex items-center gap-1.5 bg-gradient-to-r from-kemenag-700 to-kemenag-800 hover:from-kemenag-800 hover:to-kemenag-900 text-white text-xs sm:text-[13px] font-bold px-3.5 py-2 rounded-xl shadow-md hover:shadow-emerald-900/20 transition-all border border-emerald-600/30" title="{{ ($contentBlocks['copy.blade_navigation']['title_275'] ?? 'Masuk Portal Admin / Guru / Tendik') }}">
          <i class="fa-solid fa-user-tie text-accent-yellow text-xs"></i>
          <span>{{ ($contentBlocks['copy.blade_navigation']['text_136'] ?? 'Login') }}</span>
        </a>

        <!-- Mobile Menu Toggle -->
        <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_221'] ?? 'Menu navigasi') }}" class="md:hidden w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center hover:bg-slate-200 transition">
          <i class="fa-solid fa-bars text-sm sm:text-base"></i>
        </button>
      </div>

    </div>

    <!-- Baris 2: Sub-Navbar Menu Navigasi Lengkap (Desktop & Tablet) -->
    <div class="hidden md:block bg-slate-50/90 border-t border-slate-200/80">
      <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-1.5 flex items-center justify-between">

        <nav class="portal-primary-navigation flex flex-wrap items-center gap-x-6 gap-y-1 text-[13px] font-semibold text-slate-700">
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="hover:text-kemenag-700 transition py-1 flex items-center gap-1.5" @if (request()->routeIs('public.home')) aria-current="page" @endif>
            <i class="fa-solid fa-house text-xs text-kemenag-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_010'] ?? 'Beranda') }}
          </a>

          <!-- Profil Dropdown -->
          <div class="relative group">
            <button type="button" data-portal-menu="portal-profile-menu" aria-controls="portal-profile-menu" aria-expanded="false" class="flex items-center gap-1 hover:text-kemenag-700 py-1 transition">
              <span>{{ ($contentBlocks['copy.blade_navigation']['text_137'] ?? 'Profil') }}</span>
              <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 group-hover:rotate-180 transition-transform"></i>
            </button>
            <div id="portal-profile-menu" hidden class="absolute left-0 top-full pt-1.5 w-60 z-50">
              <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-2.5 flex flex-col space-y-1">
                @foreach (config('public_portal.profile_menu') as $item)
                  <a href="{{ $item['href'] }}" class="px-3 py-2 rounded-xl hover:bg-emerald-50 hover:text-kemenag-700 text-xs font-medium transition flex items-center gap-2">
                    <i class="fa-solid {{ $item['icon'] }} {{ $item['color'] ?? 'text-kemenag-600' }} w-4" aria-hidden="true"></i> {{ $item['label'] }}
                  </a>
                @endforeach
              </div>
            </div>
          </div>

          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_208'] ?? '/#zona-integritas') }}" class="hover:text-kemenag-700 transition py-1 flex items-center gap-1 font-semibold text-emerald-800">
            <i class="fa-solid fa-shield-heart text-amber-500 text-xs"></i>
            <span>{{ ($contentBlocks['copy.blade_navigation']['text_138'] ?? 'Zona Integritas') }}</span>
          </a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_210'] ?? '/layanan/ptsp') }}" class="hover:text-kemenag-700 transition py-1 flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            {{ ($contentBlocks['copy.blade_navigation']['text_013'] ?? 'PTSP Digital') }}
          </a>

          <!-- E-Layanan Mega Menu (3 Kolom Tematik Berkelompok) -->
          <div class="portal-mega-menu-group relative group">
            <button type="button" data-portal-menu="portal-services-menu" aria-controls="portal-services-menu" aria-expanded="false" class="flex items-center gap-1.5 hover:text-kemenag-700 py-1 transition text-emerald-800 font-extrabold">
              <span>{{ ($contentBlocks['copy.blade_navigation']['text_139'] ?? 'E-Layanan') }}</span>
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
              <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 group-hover:rotate-180 transition-transform"></i>
            </button>

            <!-- Panel Mega Menu 3 Kolom -->
            <div id="portal-services-menu" hidden class="portal-mega-menu absolute -left-48 lg:-left-64 top-full pt-2 w-[780px] lg:w-[860px] z-50 transition-all duration-200">
              <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 p-5 ring-1 ring-black/5 overflow-hidden">

                <!-- Header Pita Mega Menu -->
                <div class="flex items-center justify-between pb-3.5 mb-3.5 border-b border-slate-100">
                  <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">
                      <i class="fa-solid fa-layer-group"></i>
                    </span>
                    <div>
                      <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">{{ ($contentBlocks['copy.blade_navigation']['text_140'] ?? 'Pusat Layanan Digital Mandiri Madrasah') }}</h4>
                      <p class="text-[10px] text-slate-500">{{ ($contentBlocks['copy.blade_navigation']['text_141'] ?? 'Pilih kategori layanan terpadu secara mandiri dan cepat') }}</p>
                    </div>
                  </div>
                  <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                    {{ ($contentBlocks['copy.blade_navigation']['text_142'] ?? '16 Layanan Aktif') }}
                  </span>
                </div>

                <!-- 3 Kolom Tematik -->
                <div class="grid grid-cols-3 gap-3.5">

                  <!-- Kolom 1: Persuratan, Tamu & PTSP -->
                  <div class="space-y-1.5 bg-slate-50/60 p-3 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-1.5 pb-1 border-b border-slate-200/60 mb-2">
                      <i class="fa-solid fa-landmark-dome text-emerald-600 text-xs"></i>
                      <span class="text-[10.5px] font-extrabold text-emerald-800 uppercase tracking-wider">{{ ($contentBlocks['copy.blade_navigation']['text_143'] ?? 'Persuratan & Tamu') }}</span>
                    </div>

                    <!-- Kirim Surat Dinas (Unggulan Baru) -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_276'] ?? '/layanan/surat-dinas') }}" class="p-2 rounded-xl bg-emerald-50/90 hover:bg-emerald-100 text-slate-800 transition flex items-start gap-2.5 border border-emerald-300/80 group/item">
                      <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-paper-plane"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                          <span class="text-xs font-bold text-emerald-900 group-hover/item:text-emerald-700 truncate">{{ ($contentBlocks['copy.blade_navigation']['text_144'] ?? 'Kirim Surat Dinas') }}</span>
                          <span class="bg-amber-400 text-slate-950 text-[8.5px] font-black px-1.5 py-0.2 rounded-full shadow-xs">{{ ($contentBlocks['copy.blade_navigation']['text_145'] ?? 'BARU') }}</span>
                        </div>
                        <p class="text-[10px] text-emerald-700/80 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_146'] ?? 'Surat resmi instansi luar & lacak') }}</p>
                      </div>
                    </a>

                    <!-- PTSP Terpadu -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_210'] ?? '/layanan/ptsp') }}" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-stamp"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-kemenag-700 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_147'] ?? 'PTSP Terpadu Online') }}</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_148'] ?? 'Pelayanan izin & permohonan') }}</p>
                      </div>
                    </a>

                    <!-- Buku Tamu Digital -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_222'] ?? '/layanan/buku-tamu') }}" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-address-book"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-kemenag-700 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_149'] ?? 'Buku Tamu Digital') }}</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_150'] ?? 'Resepsionis & kunjungan dinas') }}</p>
                      </div>
                    </a>

                    <!-- Layanan Pengaduan (Lapor) -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_232'] ?? '/layanan/lapor') }}" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-bullhorn"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-rose-600 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_151'] ?? 'Layanan Pengaduan') }}</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_152'] ?? 'Kritik, aspirasi, & lapor kendala') }}</p>
                      </div>
                    </a>
                  </div>

                  <!-- Kolom 2: Siswa & Alumni -->
                  <div class="space-y-1.5 bg-slate-50/60 p-3 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-1.5 pb-1 border-b border-slate-200/60 mb-2">
                      <i class="fa-solid fa-graduation-cap text-amber-500 text-xs"></i>
                      <span class="text-[10.5px] font-extrabold text-amber-800 uppercase tracking-wider">{{ ($contentBlocks['copy.blade_navigation']['text_153'] ?? 'Siswa & Alumni') }}</span>
                    </div>

                    <!-- Presensi Siswa Mandiri (RFID TAP) -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_277'] ?? '/kios-presensi') }}" class="p-2 rounded-xl bg-emerald-50/90 hover:bg-emerald-100 text-slate-800 transition flex items-start gap-2.5 border border-emerald-300/80 group/item">
                      <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-id-card-clip"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                          <span class="text-xs font-bold text-emerald-900 group-hover/item:text-emerald-700 truncate">{{ ($contentBlocks['copy.blade_navigation']['text_154'] ?? 'Presensi Siswa Mandiri') }}</span>
                          <span class="bg-amber-400 text-slate-950 text-[8.5px] font-black px-1.5 py-0.2 rounded-full shadow-xs">{{ ($contentBlocks['copy.blade_navigation']['text_145'] ?? 'BARU') }}</span>
                        </div>
                        <p class="text-[10px] text-emerald-700/80 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_155'] ?? 'Tap kartu RFID / Scan QR Kiosk') }}</p>
                      </div>
                    </a>

                    <!-- Legalisir Ijazah -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_228'] ?? '/layanan/legalisir') }}" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-certificate"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-amber-700 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_156'] ?? 'Legalisir Ijazah Online') }}</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_157'] ?? 'Pengesahan ijazah alumni') }}</p>
                      </div>
                    </a>

                    <!-- Mutasi Siswa -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_230'] ?? '/layanan/mutasi') }}" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-people-arrows"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-teal-700 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_158'] ?? 'Mutasi & Pindah Siswa') }}</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_159'] ?? 'Pengajuan pindah masuk/keluar') }}</p>
                      </div>
                    </a>

                    <!-- Surat Izin Siswa -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_229'] ?? '/layanan/izin-siswa') }}" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-file-signature"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-blue-700 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_160'] ?? 'Surat Izin Siswa') }}</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_161'] ?? 'Izin sakit & keperluan penting') }}</p>
                      </div>
                    </a>

                    <!-- Pengumuman Kelulusan -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_233'] ?? '/layanan/kelulusan') }}" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-award"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-amber-700 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_162'] ?? 'Pengumuman Kelulusan') }}</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_163'] ?? 'Cek kelulusan & cetak SKL') }}</p>
                      </div>
                    </a>
                  </div>

                  <!-- Kolom 3: Ketertiban & Penunjang -->
                  <div class="space-y-1.5 bg-slate-50/60 p-3 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-1.5 pb-1 border-b border-slate-200/60 mb-2">
                      <i class="fa-solid fa-shield-halved text-purple-600 text-xs"></i>
                      <span class="text-[10.5px] font-extrabold text-purple-900 uppercase tracking-wider">{{ ($contentBlocks['copy.blade_navigation']['text_164'] ?? 'Ketertiban & Sarana') }}</span>
                    </div>

                    <!-- Izin Keluar-Masuk (Piket) -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_278'] ?? '/piket/izin-keluar') }}" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-door-open"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-emerald-700 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_165'] ?? 'Izin Keluar-Masuk (Piket)') }}</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_166'] ?? 'Validasi keluar gerbang santri') }}</p>
                      </div>
                    </a>

                    <!-- Scan Pos Satpam -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_279'] ?? '/pos-jaga') }}" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-shield-halved"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-slate-900 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_167'] ?? 'Scan Pos Satpam') }}</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_168'] ?? 'Keamanan & pos gerbang') }}</p>
                      </div>
                    </a>

                    <!-- Scan Pelanggaran Disiplin -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_280'] ?? '/bk/kasus') }}" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-qrcode"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-amber-700 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_169'] ?? 'Scan Disiplin BK') }}</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_170'] ?? 'Pencatatan pelanggaran siswa') }}</p>
                      </div>
                    </a>

                    <!-- Tracking Permohonan -->
                    <a href="{{ ($contentBlocks['copy.blade_navigation']['href_231'] ?? '/lacak') }}" class="p-2 rounded-xl hover:bg-white text-slate-800 transition flex items-start gap-2.5 group/item">
                      <span class="w-7 h-7 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-xs shrink-0 mt-0.5 group-hover/item:scale-105 transition-transform">
                        <i class="fa-solid fa-magnifying-glass-location"></i>
                      </span>
                      <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 group-hover/item:text-purple-700 block truncate">{{ ($contentBlocks['copy.blade_navigation']['text_171'] ?? 'Lacak Status Berkas') }}</span>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ ($contentBlocks['copy.blade_navigation']['text_172'] ?? 'Tracking permohonan online') }}</p>
                      </div>
                    </a>
                  </div>

                </div>

                <!-- Footer Bar Mega Menu (Pusat Unduh & Zona Integritas) -->
                <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs bg-slate-50/70 -mx-5 -mb-5 px-5 py-3">
                  <div class="flex items-center gap-3">
                    <button data-open-dialog="downloadModal" aria-haspopup="dialog" class="text-slate-600 hover:text-emerald-700 font-bold transition flex items-center gap-1.5 text-xs">
                      <i class="fa-solid fa-cloud-arrow-down text-emerald-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_173'] ?? 'Pusat Unduh Berkas PDF') }}
                    </button>
                    <span class="text-slate-300">•</span>
                    <button data-open-dialog="ziModal" aria-haspopup="dialog" class="text-slate-600 hover:text-amber-700 font-bold transition flex items-center gap-1.5 text-xs">
                      <i class="fa-solid fa-shield-heart text-amber-500"></i> {{ ($contentBlocks['copy.blade_navigation']['text_174'] ?? 'Zona Integritas & IKM') }}
                    </button>
                  </div>
                  <a href="{{ ($contentBlocks['copy.blade_navigation']['href_211'] ?? '/perpustakaan') }}" class="text-emerald-700 hover:text-emerald-800 font-bold transition flex items-center gap-1 text-[11px]">
                    <i class="fa-solid fa-book-bookmark"></i> {{ ($contentBlocks['copy.blade_navigation']['text_175'] ?? 'Perpustakaan Digital →') }}
                  </a>
                </div>

              </div>
            </div>
          </div>

          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_281'] ?? '/#akademik') }}" class="hover:text-kemenag-700 transition py-1">{{ ($contentBlocks['copy.blade_navigation']['text_176'] ?? 'Peminatan') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_211'] ?? '/perpustakaan') }}" class="hover:text-kemenag-700 transition py-1">{{ ($contentBlocks['copy.blade_navigation']['text_014'] ?? 'Perpustakaan') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_212'] ?? '/alumni') }}" class="hover:text-kemenag-700 transition py-1">{{ ($contentBlocks['copy.blade_navigation']['text_015'] ?? 'Alumni') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_213'] ?? '/berita') }}" class="hover:text-kemenag-700 transition py-1">{{ ($contentBlocks['copy.blade_navigation']['text_016'] ?? 'Berita') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_214'] ?? '/prestasi') }}" class="hover:text-kemenag-700 transition py-1">{{ ($contentBlocks['copy.blade_navigation']['text_017'] ?? 'Prestasi') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_282'] ?? '/galeri') }}" class="hover:text-kemenag-700 transition py-1">{{ ($contentBlocks['copy.blade_navigation']['text_177'] ?? 'Galeri') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_223'] ?? '/galeri/video') }}" class="hover:text-kemenag-700 transition py-1">{{ ($contentBlocks['copy.blade_navigation']['text_034'] ?? 'Video') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_215'] ?? '/#kontak') }}" class="hover:text-kemenag-700 transition py-1">{{ ($contentBlocks['copy.blade_navigation']['text_018'] ?? 'Kontak') }}</a>
          @if (request()->is('galeri/video*'))
            <a href="{{ ($contentBlocks['copy.blade_navigation']['href_283'] ?? '#videoModal') }}" data-open-video-featured class="portal-video-subscribe">{{ ($contentBlocks['copy.blade_navigation']['text_179'] ?? 'Subscribe') }}</a>
          @endif
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_216'] ?? '/login') }}" class="portal-desktop-catalogue-login">{{ ($contentBlocks['copy.blade_navigation']['text_019'] ?? 'Login Sistem') }}</a>
        </nav>

        <!-- Quick Info Kanan Sub-Navbar -->
        <div class="portal-subnav-quick hidden lg:flex items-center gap-3 text-xs text-slate-500 font-medium">
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_231'] ?? '/lacak') }}" class="hover:text-kemenag-700 flex items-center gap-1.5 transition">
            <i class="fa-solid fa-clock-rotate-left text-emerald-600"></i>
            <span>{{ ($contentBlocks['copy.blade_navigation']['text_121'] ?? 'Tracking Berkas') }}</span>
          </a>
          <span class="text-slate-300">•</span>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_233'] ?? '/layanan/kelulusan') }}" class="hover:text-kemenag-700 flex items-center gap-1.5 transition">
            <i class="fa-solid fa-award text-amber-500"></i>
            <span>{{ ($contentBlocks['copy.blade_navigation']['text_180'] ?? 'Cek SKL') }}</span>
          </a>
        </div>

      </div>
    </div>
  </header>
@endif

  <!-- Mobile Drawer Menu -->
  <div id="mobileMenu" role="dialog" aria-modal="true" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_221'] ?? 'Menu navigasi') }}" tabindex="-1" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm">
    <div class="fixed right-0 top-0 bottom-0 w-4/5 max-w-sm bg-white p-6 shadow-2xl flex flex-col justify-between overflow-y-auto">
      @if (request()->is('data-siswa', 'layanan/tracer-alumni', 'alumni', 'alumni/*', 'layanan/legalisir', 'layanan/izin-siswa', 'layanan/mutasi', 'lacak', 'layanan/lapor', 'lapor/transparansi', 'layanan/kelulusan'))
      @php
        $databaseDrawerLinks = [
            ['/', 'Home', 'house', ['/']],
            ['/data-siswa', 'Data Siswa', 'users', ['data-siswa']],
            ['/layanan/tracer-alumni', 'Data Alumni', 'user-graduate', ['layanan/tracer-alumni']],
            ['/layanan/legalisir', 'Legalisir', 'certificate', ['layanan/legalisir']],
            ['/layanan/izin-siswa', 'Surat Izin', 'file-signature', ['layanan/izin-siswa']],
            ['/layanan/mutasi', 'Mutasi', 'people-arrows', ['layanan/mutasi']],
            ['/lacak', 'Lacak Terpadu', 'magnifying-glass', ['lacak']],
            ['/layanan/lapor', 'Pengaduan', 'bullhorn', ['layanan/lapor', 'lapor/transparansi']],
            ['/layanan/kelulusan', 'Cek Kelulusan', 'award', ['layanan/kelulusan']],
        ];
        if (request()->is('alumni*')) {
            $databaseDrawerLinks = [
                ['/alumni', 'Beranda IKA', 'house', ['alumni']],
                ['/alumni#direktori-alumni', 'Direktori Alumni', 'users', ['alumni/direktori']],
                ['/layanan/tracer-alumni#tracer-form', 'Tracer Study', 'pen-to-square', []],
                ['/alumni?section=careers#ika-portal-tabs', 'Bursa Karir', 'briefcase', []],
                ['/alumni?section=scholarships#ika-portal-tabs', 'Beasiswa', 'graduation-cap', []],
                ['/layanan/tracer-alumni#alumni-kta', 'KTA Digital', 'id-card', []],
                ['/layanan/legalisir', 'Legalisir Online', 'certificate', []],
                ['/alumni#program-ika', 'Program IKA', 'handshake', []],
                ['/alumni#statistik-alumni', 'Statistik', 'chart-pie', []],
                ['/alumni#faq-alumni', 'FAQ', 'circle-info', []],
            ];
        }
      @endphp
      <div>
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
          <div class="flex items-center gap-2.5 min-w-0">
            <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-9 h-9 object-contain shrink-0">
            <div class="min-w-0">
              <span class="block font-extrabold text-sm text-emerald-700 leading-tight truncate">{{ request()->is('alumni*') ? 'IKATAN KELUARGA ALUMNI (IKA)' : 'APLIKASI DATABASE SISWA' }}</span>
              <span class="block text-[10px] font-bold text-slate-500 uppercase truncate">{{ strtoupper($schoolName) }}</span>
            </div>
          </div>
          <button data-close-dialog aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_284'] ?? 'Tutup menu') }}" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <nav class="mt-5 flex flex-col space-y-1.5 font-semibold text-slate-700 text-sm" aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_225'] ?? 'Menu aplikasi database siswa') }}">
          @foreach ($databaseDrawerLinks as [$href, $label, $icon, $paths])
            @php($active = request()->is(...$paths))
            <a href="{{ $href }}" @if ($active) aria-current="page" @endif
              @class([
                'px-3.5 py-2.5 rounded-xl transition flex items-center gap-2.5',
                'bg-blue-600 text-white font-bold shadow-sm' => $active,
                'text-emerald-600 font-bold hover:bg-emerald-50' => ! $active && $href === '/lacak',
                'hover:bg-slate-100 hover:text-blue-600' => ! $active && $href !== '/lacak',
              ])>
              <i aria-hidden="true" @class(['fa-solid fa-'.$icon, 'w-4', 'text-slate-400' => ! $active && $href !== '/lacak'])></i>
              {{ $label }}
            </a>
          @endforeach
        </nav>
      </div>

      <div class="pt-5 border-t border-slate-100 grid grid-cols-2 gap-2">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_224'] ?? '/login?portal=siswa') }}" class="py-2.5 px-3 bg-amber-400 hover:bg-amber-300 text-slate-900 font-bold rounded-full text-center text-xs shadow-sm flex items-center justify-center gap-1.5">
          <i class="fa-solid fa-user-graduate"></i> {{ ($contentBlocks['copy.blade_navigation']['text_181'] ?? 'Portal Siswa') }}
        </a>
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_216'] ?? '/login') }}" class="py-2.5 px-3 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-full text-center text-xs flex items-center justify-center gap-1.5">
          <i class="fa-solid fa-lock"></i> {{ ($contentBlocks['copy.blade_navigation']['text_037'] ?? 'Login Admin') }}
        </a>
      </div>
      @else
      <div>
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-graduation-cap text-kemenag-700 text-xl"></i>
            <span class="font-extrabold text-slate-900">{{ $schoolName }}</span>
          </div>
          <button data-close-dialog aria-label="{{ ($contentBlocks['copy.blade_navigation']['aria_label_284'] ?? 'Tutup menu') }}" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <nav class="mt-6 flex flex-col space-y-2.5 font-semibold text-slate-700 text-sm">
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_285'] ?? '/e-layanan') }}" class="p-2 hover:bg-emerald-50 rounded-xl text-kemenag-700 font-bold">{{ ($contentBlocks['copy.blade_navigation']['text_182'] ?? 'Semua E-Layanan') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_209'] ?? '/') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">{{ ($contentBlocks['copy.blade_navigation']['text_010'] ?? 'Beranda') }}</a>

          <div class="grid grid-cols-2 gap-1.5 my-1">
                        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_224'] ?? '/login?portal=siswa') }}" class="p-2.5 bg-amber-50 hover:bg-orange-100 rounded-xl text-orange-900 font-bold flex items-center gap-1.5 items-center justify-center text-center text-xs border border-orange-200">
              <i class="fa-solid fa-user-graduate text-orange-600 "></i> {{ ($contentBlocks['copy.blade_navigation']['text_115'] ?? 'Login Siswa') }}
            </a>
            <a href="{{ ($contentBlocks['copy.blade_navigation']['href_216'] ?? '/login') }}" class="p-2.5 bg-emerald-50 hover:bg-emerald-100 rounded-xl text-kemenag-800 font-bold flex items-center gap-1.5 items-center justify-center text-center text-xs border border-emerald-200">
              <i class="fa-solid fa-user-tie text-kemenag-600 "></i> {{ ($contentBlocks['copy.blade_navigation']['text_037'] ?? 'Login Admin') }}
            </a>
          </div>

          <!-- Kategori 1: Persuratan & Tamu -->
          <div class="pt-2">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200 block mb-1">
              {{ ($contentBlocks['copy.blade_navigation']['text_183'] ?? '🏛️ Persuratan & Tamu') }}
            </span>
            <div class="space-y-1">
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_276'] ?? '/layanan/surat-dinas') }}" class="p-2 bg-emerald-50/80 hover:bg-emerald-100 rounded-xl text-kemenag-800 font-bold flex items-center justify-between text-xs border border-emerald-200">
                <span class="flex items-center gap-2"><i class="fa-solid fa-paper-plane text-emerald-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_144'] ?? 'Kirim Surat Dinas') }}</span>
                <span class="bg-amber-400 text-slate-950 text-[9px] font-black px-1.5 py-0.2 rounded-full">{{ ($contentBlocks['copy.blade_navigation']['text_145'] ?? 'BARU') }}</span>
              </a>
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_210'] ?? '/layanan/ptsp') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-stamp text-kemenag-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_147'] ?? 'PTSP Terpadu Online') }}
              </a>
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_222'] ?? '/layanan/buku-tamu') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-address-book text-emerald-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_184'] ?? 'Buku Tamu Digital (Resepsionis)') }}
              </a>
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_232'] ?? '/layanan/lapor') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-bullhorn text-rose-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_185'] ?? 'Layanan Pengaduan (Lapor)') }}
              </a>
            </div>
          </div>

          <!-- Kategori 2: Siswa & Alumni -->
          <div class="pt-2">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200 block mb-1">
              {{ ($contentBlocks['copy.blade_navigation']['text_186'] ?? '🎓 Layanan Siswa & Alumni') }}
            </span>
            <div class="space-y-1">
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_277'] ?? '/kios-presensi') }}" class="p-2 bg-emerald-50/80 hover:bg-emerald-100 rounded-xl text-kemenag-800 font-bold flex items-center justify-between text-xs border border-emerald-200">
                <span class="flex items-center gap-2"><i class="fa-solid fa-id-card-clip text-emerald-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_187'] ?? 'Presensi Mandiri (RFID Tap)') }}</span>
                <span class="bg-amber-400 text-slate-950 text-[9px] font-black px-1.5 py-0.2 rounded-full">{{ ($contentBlocks['copy.blade_navigation']['text_145'] ?? 'BARU') }}</span>
              </a>
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_228'] ?? '/layanan/legalisir') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-certificate text-amber-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_156'] ?? 'Legalisir Ijazah Online') }}
              </a>
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_230'] ?? '/layanan/mutasi') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-people-arrows text-teal-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_188'] ?? 'Pengajuan Mutasi Siswa') }}
              </a>
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_229'] ?? '/layanan/izin-siswa') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-file-signature text-blue-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_189'] ?? 'Surat Izin Siswa Online') }}
              </a>
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_233'] ?? '/layanan/kelulusan') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-award text-amber-500"></i> {{ ($contentBlocks['copy.blade_navigation']['text_190'] ?? 'Pengumuman Kelulusan (SKL)') }}
              </a>
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_212'] ?? '/alumni') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-graduation-cap text-accent-gold"></i> {{ ($contentBlocks['copy.blade_navigation']['text_191'] ?? 'Portal Ikatan Alumni & Tracer') }}
              </a>
            </div>
          </div>

          <!-- Kategori 3: Ketertiban & Fasilitas -->
          <div class="pt-2">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-purple-800 bg-purple-50 px-2.5 py-0.5 rounded-full border border-purple-200 block mb-1">
              {{ ($contentBlocks['copy.blade_navigation']['text_192'] ?? '🛡️ Ketertiban & Sarana') }}
            </span>
            <div class="space-y-1">
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_278'] ?? '/piket/izin-keluar') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-door-open text-emerald-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_165'] ?? 'Izin Keluar-Masuk (Piket)') }}
              </a>
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_279'] ?? '/pos-jaga') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-shield-halved text-emerald-700"></i> {{ ($contentBlocks['copy.blade_navigation']['text_193'] ?? 'Scan Pos Satpam (Gerbang)') }}
              </a>
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_280'] ?? '/bk/kasus') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-amber-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-qrcode text-amber-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_194'] ?? 'Scan Pelanggaran Siswa (BK)') }}
              </a>
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_231'] ?? '/lacak') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-magnifying-glass-location text-purple-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_195'] ?? 'Lacak Status Permohonan') }}
              </a>
              <a href="{{ ($contentBlocks['copy.blade_navigation']['href_211'] ?? '/perpustakaan') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-book-bookmark text-emerald-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_196'] ?? 'Perpustakaan Digital') }}
              </a>
              <button data-open-dialog="downloadModal" aria-haspopup="dialog" class="w-full text-left p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-cloud-arrow-down text-emerald-600"></i> {{ ($contentBlocks['copy.blade_navigation']['text_197'] ?? 'Pusat Unduh Berkas (PDF)') }}
              </button>
              <button data-open-dialog="ziModal" aria-haspopup="dialog" class="w-full text-left p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 text-xs">
                <i class="fa-solid fa-shield-halved text-accent-gold"></i> {{ ($contentBlocks['copy.blade_navigation']['text_008'] ?? 'Zona Integritas (WBK)') }}
              </button>
            </div>
          </div>

          <div class="pt-2 border-t border-slate-100">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1">
              {{ ($contentBlocks['copy.blade_navigation']['text_198'] ?? 'Profil & Informasi') }}
            </span>
          </div>
          @foreach (config('public_portal.profile_menu') as $item)
            <a href="{{ $item['href'] }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">
              <i class="fa-solid {{ $item['icon'] }} text-kemenag-600 w-4" aria-hidden="true"></i> {{ $item['label'] }}
            </a>
          @endforeach
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_213'] ?? '/berita') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">{{ ($contentBlocks['copy.blade_navigation']['text_199'] ?? 'Warta Berita') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_214'] ?? '/prestasi') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">{{ ($contentBlocks['copy.blade_navigation']['text_200'] ?? 'Prestasi Siswa') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_282'] ?? '/galeri') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">{{ ($contentBlocks['copy.blade_navigation']['text_201'] ?? 'Galeri Foto') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_223'] ?? '/galeri/video') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">{{ ($contentBlocks['copy.blade_navigation']['text_202'] ?? 'Video Kegiatan') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_navigation']['href_215'] ?? '/#kontak') }}" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">{{ ($contentBlocks['copy.blade_navigation']['text_203'] ?? 'Kontak & Lokasi') }}</a>
        </nav>
      </div>

      <div class="pt-6 border-t border-slate-100">
        <a href="{{ ($contentBlocks['copy.blade_navigation']['href_286'] ?? '/#ppdb') }}" class="w-full bg-kemenag-700 text-white font-bold py-3 rounded-xl shadow-md text-center block">
          {{ ($contentBlocks['copy.blade_navigation']['text_204'] ?? 'Daftar PPDB') }}        </a>
      </div>
      @endif
    </div>
  </div>
