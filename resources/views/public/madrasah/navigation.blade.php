  <a class="portal-skip-link bg-white rounded-xl px-4 py-2 font-bold" href="#main-content">Lewati ke konten utama</a>

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
        <span class="sr-only">Hari ini: <strong id="topbarLiveVisitors">{{ number_format($visits['today'], 0, ',', '.') }}</strong> kunjungan</span>
      </div>
      <div class="flex items-center space-x-4 text-emerald-200">
        @if (request()->is('direktori-gtk'))
          <a href="/profil-sekolah" class="hover:text-accent-yellow transition">Visi &amp; Misi</a>
        @else
          <a href="/direktori-gtk" class="hover:text-accent-yellow transition">Dewan Guru &amp; GTK</a>
        @endif
        <span>•</span>
        <a href="/#zona-integritas" class="hover:text-accent-yellow transition flex items-center gap-1">
          <i class="fa-solid fa-shield-heart text-accent-yellow"></i> Zona Integritas (WBK)
        </a>
      </div>
    </div>
  </aside>

  <!-- ================= NAVBAR ATAS ================= -->
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="flex items-center justify-between h-20 gap-4">
        <a href="/" class="flex items-center gap-3 min-w-0 flex-1 xl:flex-initial">
          <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-11 h-11 object-contain shrink-0">
          <div class="min-w-0">
            <span class="block font-extrabold text-base sm:text-lg text-slate-900 tracking-tight leading-none truncate">{{ $schoolName }}</span>
            <span class="text-[11px] text-slate-500 font-medium tracking-wide block truncate">Portal Informasi &amp; Akademik Terpadu</span>
          </div>
        </a>

        <nav class="hidden xl:flex items-center gap-6 text-sm font-semibold text-slate-600">
          <a href="/" class="hover:text-kemenag-700 transition">Beranda</a>
          <a href="/profil-sekolah" class="{{ request()->is('profil-sekolah') ? 'text-kemenag-700 font-extrabold border-b-2 border-kemenag-700 pb-1' : 'hover:text-kemenag-700 transition' }}" @if (request()->is('profil-sekolah')) aria-current="page" @endif>Visi &amp; Misi</a>
          <a href="/direktori-gtk" class="{{ request()->is('direktori-gtk') ? 'text-kemenag-700 font-extrabold border-b-2 border-kemenag-700 pb-1' : 'hover:text-kemenag-700 transition' }}" @if (request()->is('direktori-gtk')) aria-current="page" @endif>Guru &amp; GTK</a>
          <a href="/#zona-integritas" class="hover:text-kemenag-700 transition flex items-center gap-1">
            <i class="fa-solid fa-shield-heart text-amber-500 text-xs"></i> ZI-WBK
          </a>
          <a href="/layanan/ptsp" class="hover:text-kemenag-700 transition">PTSP Digital</a>
          <a href="/perpustakaan" class="hover:text-kemenag-700 transition">Perpustakaan</a>
          <a href="/alumni" class="hover:text-kemenag-700 transition">Alumni</a>
          <a href="/berita" class="hover:text-kemenag-700 transition">Berita</a>
          <a href="/prestasi" class="hover:text-kemenag-700 transition">Prestasi</a>
          <a href="/#kontak" class="hover:text-kemenag-700 transition">Kontak</a>
        </nav>

        <div class="flex items-center gap-2 shrink-0">
          <a href="/login" class="bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-1.5">
            <i class="fa-solid fa-user-shield text-accent-yellow"></i>
            <span>Login Sistem</span>
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
        <a href="/" class="font-bold text-accent-yellow hover:underline flex items-center gap-1.5">
          <i class="fa-solid fa-circle-arrow-left"></i> Web Madrasah
        </a>
        <a href="/#kontak" class="text-emerald-100 hover:text-accent-yellow flex items-center gap-1.5">
          <i class="fa-solid fa-envelope"></i> Kontak
        </a>
        <a href="/#kontak" class="text-emerald-300 font-semibold hover:text-accent-yellow flex items-center gap-1.5">
          <i class="fa-solid fa-comments"></i> Live Chat
        </a>
        <span class="hidden lg:inline text-emerald-100">
          <i class="fa-solid fa-phone text-emerald-300"></i> Call Centre: <strong>{{ $schoolPhone }}</strong>
        </span>
      </div>
      <div class="ptsp-topbar-ticker hidden md:flex items-center overflow-hidden text-emerald-100">
        <div class="whitespace-nowrap animate-marquee-slow">
          <i class="fa-solid fa-bullhorn text-accent-yellow mr-1"></i>
          <strong>Pemberitahuan Layanan Online PTSP:</strong>
          Kini pengurusan permohonan surat keterangan aktif, legalisir, mutasi siswa, dan rekomendasi beasiswa dapat diajukan secara online melalui portal PTSP.
        </div>
      </div>
    </div>
  </div>

  <!-- ================= NAVBAR HIJAU PTSP ================= -->
  <header data-madrasah-navigation class="ptsp-header sticky top-0 z-40 shadow-md">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 h-16 flex items-center justify-between gap-4">
      <a href="/layanan/ptsp" class="flex items-center gap-2.5 text-white min-w-0 flex-1 md:flex-initial">
        <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-9 h-9 object-contain shrink-0">
        <span class="font-extrabold text-sm sm:text-base tracking-tight uppercase truncate">{{ $schoolName }}</span>
        <span class="ptsp-badge shrink-0">PTSP DIGITAL</span>
      </a>

      <nav class="hidden lg:flex items-center gap-5 text-xs font-semibold text-emerald-50">
        <a href="/layanan/ptsp" class="text-white font-bold flex items-center gap-1.5" aria-current="page"><i class="fa-solid fa-house"></i> Home</a>
        <a href="#tentang-ptsp" class="hover:text-accent-yellow transition">Tentang PTSP</a>
        <a href="/lacak?category=ptsp" class="hover:text-accent-yellow transition">Cek Permohonan</a>
        <a href="/lacak?category=ptsp" class="text-accent-yellow font-bold hover:underline flex items-center gap-1"><i class="fa-solid fa-magnifying-glass"></i> Lacak Terpadu</a>
        <a href="#katalog-ptsp" class="hover:text-accent-yellow transition">Jenis Layanan</a>
        <a href="/#kontak" class="hover:text-accent-yellow transition">Kontak</a>
        <a href="/layanan/ptsp/akun" class="text-accent-yellow font-bold hover:underline flex items-center gap-1"><i class="fa-solid fa-circle-user"></i> Masuk</a>
      </nav>

      <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="Menu navigasi" class="ptsp-mobile-toggle shrink-0 w-9 h-9 rounded-lg text-white flex items-center justify-center hover:bg-white/10 transition">
        <i class="fa-solid fa-bars"></i>
      </button>

      <span class="sr-only">
        <a href="/layanan/buku-tamu">Buku Tamu</a>
        <a href="/galeri/video">Video</a>
      </span>
    </div>
  </header>
@elseif (request()->is('lacak', 'layanan/legalisir', 'layanan/mutasi', 'layanan/kelulusan', 'layanan/izin-siswa'))
  <!-- ================= TOP BAR APLIKASI DATABASE SISWA ================= -->
  <div data-portal-identity class="bg-slate-900 text-slate-200 text-xs py-2 border-b border-slate-800">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 flex items-center justify-between gap-4">
      <div class="flex items-center gap-2 font-semibold">
        <i class="fa-solid fa-graduation-cap text-amber-400"></i>
        <span>{{ strtoupper($schoolName) }} • TA 2026/2027</span>
      </div>
      <div class="flex items-center gap-2">
        <a href="/login?portal=siswa" class="inline-flex items-center gap-1.5 bg-amber-400 hover:bg-amber-300 text-slate-950 font-extrabold px-3 py-1 rounded-lg transition">
          <i class="fa-solid fa-user-graduate"></i> Portal Siswa / Wali
        </a>
        <a href="/login" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-700 text-white font-semibold px-3 py-1 rounded-lg border border-slate-700 transition">
          <i class="fa-solid fa-lock"></i> Login Admin
        </a>
      </div>
    </div>
  </div>

  <!-- ================= NAVBAR APLIKASI DATABASE SISWA ================= -->
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 h-16 flex items-center justify-between gap-4">
      <a href="/" class="flex items-center gap-2.5 shrink-0">
        <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-9 h-9 object-contain">
        <div>
          <span class="block font-extrabold text-sm text-emerald-700 leading-none">APLIKASI DATABASE SISWA</span>
          <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">{{ strtoupper($schoolName) }}</span>
        </div>
      </a>

      <nav class="hidden lg:flex items-center gap-4 text-xs font-semibold text-slate-600">
        <a href="/" class="hover:text-blue-600 transition">Home</a>
        <a href="/direktori-gtk" class="hover:text-blue-600 transition">Data Siswa</a>
        <a href="/alumni" class="hover:text-blue-600 transition">Data Alumni</a>
        <a href="/layanan/legalisir" class="{{ request()->is('layanan/legalisir') ? 'bg-blue-600 text-white font-bold px-4 py-1.5 rounded-full shadow-sm' : 'hover:text-blue-600 transition' }}">Legalisir</a>
        <a href="/layanan/izin-siswa" class="{{ request()->is('layanan/izin-siswa') ? 'bg-blue-600 text-white font-bold px-4 py-1.5 rounded-full shadow-sm' : 'hover:text-blue-600 transition' }}">Surat Izin</a>
        <a href="/layanan/mutasi" class="{{ request()->is('layanan/mutasi') ? 'bg-blue-600 text-white font-bold px-4 py-1.5 rounded-full shadow-sm' : 'hover:text-blue-600 transition' }}">Mutasi</a>
        <a href="/lacak" class="{{ request()->is('lacak') ? 'bg-blue-600 text-white font-bold px-4 py-1.5 rounded-full shadow-sm' : 'text-emerald-600 font-bold hover:text-emerald-700 transition' }}">Lacak Terpadu</a>
        <a href="/layanan/lapor" class="hover:text-blue-600 transition">Pengaduan</a>
        <a href="/layanan/kelulusan" class="{{ request()->is('layanan/kelulusan') ? 'bg-blue-600 text-white font-bold px-4 py-1.5 rounded-full shadow-sm' : 'hover:text-blue-600 transition' }}">Cek Kelulusan</a>
      </nav>

      <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="Menu navigasi" class="lg:hidden w-9 h-9 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
        <i class="fa-solid fa-bars"></i>
      </button>

      <span class="sr-only">
        <a href="/layanan/buku-tamu">Buku Tamu</a>
        <a href="/galeri/video">Video</a>
      </span>
    </div>
  </header>
@elseif (request()->is('layanan/surat-dinas'))
  <!-- ================= NAVBAR E-SURAT MASUK ================= -->
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-emerald-900/95 backdrop-blur-md text-white border-b border-emerald-700/60 shadow-md">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 h-20 flex items-center justify-between gap-4">
      <a href="/" class="flex items-center gap-3 min-w-0 flex-1 md:flex-initial">
        <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-10 h-10 object-contain shrink-0">
        <div class="min-w-0">
          <div class="flex items-center gap-2 min-w-0">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-200 truncate">KEMENTERIAN AGAMA REPUBLIK INDONESIA</span>
            <span class="hidden sm:inline-block shrink-0 bg-emerald-700 text-emerald-100 text-[9px] font-extrabold px-2 py-0.5 rounded-full border border-emerald-500/40">PORTAL PERSURATAN RESMI</span>
          </div>
          <span class="block font-extrabold text-base sm:text-lg text-white leading-tight truncate">{{ strtoupper($schoolName) }}</span>
          <span class="text-[11px] text-emerald-200/80 block truncate">Layanan Penerimaan &amp; Disposisi Surat Dinas Eksternal (E-Surat Masuk)</span>
        </div>
      </a>

      <div class="hidden md:flex items-center gap-2.5 shrink-0">
        <a href="/" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 text-xs font-bold text-white transition">Portal Utama</a>
        <a href="/layanan/ptsp" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 text-xs font-bold text-white transition">Layanan PTSP</a>
        <a href="/login" class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-extrabold shadow-md transition">Meja Petugas TU</a>
      </div>

      <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="Menu navigasi" class="md:hidden shrink-0 w-9 h-9 rounded-lg bg-white/10 text-white flex items-center justify-center">
        <i class="fa-solid fa-bars"></i>
      </button>

      <span class="sr-only">
        <a href="/layanan/buku-tamu">Buku Tamu</a>
        <a href="/galeri/video">Video</a>
      </span>
    </div>
  </header>
@elseif (request()->is('layanan/buku-tamu'))
  <!-- ================= HEADER BUKU TAMU DIGITAL ================= -->
  <div data-portal-identity class="bg-[#2e7d32] text-white text-xs py-1.5 px-4 sm:px-8 lg:px-12 border-b-2 border-[#1b5e20]">
    <div class="max-w-[1400px] mx-auto flex items-center justify-between gap-4">
      <span class="font-bold tracking-wide flex items-center gap-2"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> STATUS TAMU HARI INI :</span>
      <span class="font-semibold text-emerald-50 truncate">Resepsionis Digital Aktif</span>
    </div>
  </div>
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-[#006666]/60 backdrop-blur-md text-white border-b border-white/15 shadow-md">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-8 lg:px-12 py-3.5 flex items-center justify-between gap-4">
      <a href="/" class="flex items-center gap-3 min-w-0 flex-1 md:flex-initial">
        <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-11 h-11 object-contain shrink-0">
        <div class="min-w-0">
          <span class="block text-[10px] font-semibold uppercase tracking-widest text-teal-100/85 truncate">SISTEM BUKU TAMU DIGITAL</span>
          <span class="block font-extrabold text-base sm:text-lg text-white leading-tight truncate">{{ strtoupper($schoolName) }}</span>
        </div>
      </a>
      <div class="flex items-center gap-3 shrink-0">
        <span class="hidden sm:inline text-xs font-semibold text-teal-50">{{ now($schoolTimezone ?? 'Asia/Jakarta')->locale('id')->translatedFormat('l, j F Y') }}</span>
        <a href="/zona-integritas" class="bg-[#2e7d32] hover:bg-[#1b5e20] text-white font-extrabold text-xs px-4 py-2 rounded-lg shadow transition">IKM</a>
      </div>
      <span class="sr-only">
        <a href="/layanan/buku-tamu">Buku Tamu</a>
        <a href="/galeri/video">Video</a>
      </span>
    </div>
    <div class="bg-[#003c3c]/70 border-t border-white/15 py-2 px-4 sm:px-8 lg:px-12 text-xs">
      <div class="max-w-[1400px] mx-auto flex items-center gap-3 overflow-hidden">
        <span class="shrink-0 inline-flex items-center gap-1.5 bg-gradient-to-r from-[#008f8f] to-[#00b3b3] text-white font-bold px-3.5 py-1 rounded-full border border-white/35 text-[11px] shadow-sm">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i> INFORMASI
        </span>
        <span class="text-white font-semibold truncate">Jam Layanan {{ $schoolName }} : Senin - Kamis (07.30 - 15.00), Jumat (07.30 - 11.30). Terima kasih atas kerjasama Anda.</span>
      </div>
    </div>
  </header>
@elseif (request()->is('layanan/lapor*'))
  <!-- ================= NAVBAR SIAP LAPOR ================= -->
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-rose-100 shadow-sm">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 h-16 flex items-center justify-between gap-4">
      <a href="/layanan/lapor" class="flex items-center gap-2.5 shrink-0">
        <span class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center shadow-sm">
          <i class="fa-solid fa-shield-heart"></i>
        </span>
        <div>
          <div class="flex items-center gap-1.5">
            <span class="font-extrabold text-sm text-slate-900">Siap Lapor</span>
            <span class="bg-rose-100 text-rose-700 text-[9px] font-extrabold px-2 py-0.5 rounded-full">PORTAL RESMI</span>
          </div>
          <span class="block text-[10px] font-bold text-slate-500 uppercase">{{ strtoupper($schoolName) }}</span>
        </div>
      </a>

      <nav class="hidden lg:flex items-center gap-6 text-xs font-semibold text-slate-600">
        <a href="/" class="text-rose-600 font-bold">Beranda</a>
        <a href="#kategori-kasus" class="hover:text-rose-600 transition">Kategori Kasus</a>
        <a href="#alur-jaminan" class="hover:text-rose-600 transition">Alur &amp; Jaminan</a>
        <a href="/lapor/transparansi" class="hover:text-rose-600 transition">Transparansi Laporan</a>
      </nav>

      <div class="hidden md:flex items-center gap-2 shrink-0">
        <a href="/lacak?category=aduan" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">Lacak Tiket</a>
        <a href="#form-lapor" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold shadow-sm transition">Tulis Laporan</a>
        <a href="/satgas/aduan" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold transition">Petugas</a>
      </div>

      <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="Menu navigasi" class="lg:hidden w-9 h-9 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
        <i class="fa-solid fa-bars"></i>
      </button>

      <span class="sr-only">
        <a href="/layanan/buku-tamu">Buku Tamu</a>
        <a href="/galeri/video">Video</a>
      </span>
    </div>
    <div class="bg-[#9f1239] text-white py-2 px-4 sm:px-8 lg:px-12 flex items-center justify-between gap-4 text-xs">
      <div class="flex items-center gap-3 overflow-hidden">
        <span class="bg-white text-rose-800 font-extrabold px-2.5 py-0.5 rounded-full text-[10px] shrink-0">INFO TERKINI</span>
        <span class="truncate text-rose-50">Deklarasi Madrasah Ramah Anak &amp; Bebas Bullying: {{ $schoolName }} berkomitmen penuh melindungi pelapor.</span>
      </div>
      <span class="hidden sm:inline-flex items-center px-3 py-0.5 rounded-full bg-rose-950/60 border border-rose-400/30 text-[11px] font-bold shrink-0">100% Terlindungi &amp; Anonim</span>
    </div>
  </header>
@elseif (request()->is('alumni*'))
  <!-- ================= HEADER PORTAL ALUMNI (IKA) ================= -->
  <div data-portal-identity class="bg-slate-950 text-slate-200 text-xs py-2 border-b border-slate-800 hidden md:block">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 flex items-center justify-between gap-4">
      <div class="flex items-center gap-5">
        <span><i class="fa-solid fa-id-card text-amber-400"></i> NPSN: {{ ($profile['npsn'] ?? null) ?: 'Belum tersedia' }}</span>
        <span><i class="fa-solid fa-location-dot text-amber-400"></i> {{ $schoolAddress }}</span>
        <span><i class="fa-solid fa-phone text-amber-400"></i> {{ $schoolPhone }}</span>
      </div>
      <div class="flex items-center gap-3">
        <span class="bg-slate-800 px-2.5 py-0.5 rounded-full text-[11px]">Layanan IKA: <strong class="text-emerald-300">Aktif</strong></span>
      </div>
    </div>
  </div>
  <div class="bg-slate-900 text-white py-2 px-4 sm:px-8 lg:px-12 border-b border-slate-800 hidden md:block">
    <div class="max-w-[1600px] mx-auto flex items-center gap-3 text-xs">
      <span class="bg-amber-400 text-slate-950 font-black px-2.5 py-0.5 rounded-lg text-[10px] shrink-0">AGENDA IKA</span>
      <span class="text-slate-200 truncate">Selamat Datang di Portal Resmi Ikatan Keluarga Alumni (IKA) {{ strtoupper($schoolName) }} • Pengisian Tracer Study Mandiri Lulusan Terbuka Untuk Seluruh Angkatan</span>
    </div>
  </div>
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2.5 flex items-center justify-between gap-4">
      <a href="/alumni" class="flex items-center gap-3 min-w-0 flex-1 md:flex-initial">
        <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-9 h-9 object-contain shrink-0">
        <div class="min-w-0">
          <div class="flex items-center gap-2 min-w-0">
            <span class="font-extrabold text-sm sm:text-base text-slate-900 truncate">IKATAN KELUARGA ALUMNI (IKA)</span>
            <span class="hidden sm:inline-block shrink-0 bg-amber-100 text-amber-800 text-[9px] font-extrabold px-2 py-0.5 rounded-md border border-amber-200">OFFICIAL HUB</span>
          </div>
          <span class="text-[10px] text-slate-500 block truncate">{{ strtoupper($schoolName) }} • Penelusuran Lulusan &amp; Jejaring Karir</span>
        </div>
      </a>
      <div class="hidden md:flex items-center gap-2">
        <a href="#tracer-alumni" class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-white text-xs font-extrabold shadow-sm">Isi Tracer Study</a>
        <a href="#tracer-alumni" class="px-3.5 py-2 rounded-xl bg-blue-900 hover:bg-blue-800 text-white text-xs font-extrabold shadow-sm">KTA Digital</a>
        <a href="/layanan/legalisir" class="px-3.5 py-2 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold">Legalisir Ijazah ↗</a>
      </div>
      <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="Menu navigasi" class="md:hidden shrink-0 w-9 h-9 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
    <div class="hidden md:block bg-slate-50 border-t border-slate-200">
      <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2 flex items-center justify-between text-xs font-semibold text-slate-700">
        <nav class="flex flex-wrap items-center gap-5">
          <a href="/" class="hover:text-blue-700">Beranda</a>
          <a href="/alumni/direktori" class="hover:text-blue-700">Direktori Alumni</a>
          <a href="#tracer-alumni" class="hover:text-blue-700">Tracer Study</a>
          <a href="/alumni/direktori" class="hover:text-blue-700">Bursa Karir</a>
          <a href="/alumni/direktori" class="hover:text-blue-700">Beasiswa</a>
          <a href="#tracer-alumni" class="hover:text-blue-700">KTA Digital</a>
          <a href="/layanan/legalisir" class="text-rose-600 font-bold">Legalisir Online ↗</a>
        </nav>
        <span class="text-emerald-700 font-bold text-[11px]">Portal Resmi Alumni Terintegrasi</span>
      </div>
    </div>
    <span class="sr-only">
      <a href="/layanan/buku-tamu">Buku Tamu</a>
      <a href="/galeri/video">Video</a>
    </span>
  </header>
@elseif (request()->is('perpustakaan*'))
  <!-- ================= HEADER E-PERPUSTAKAAN DIGITAL ================= -->
  <div data-portal-identity class="bg-kemenag-900 text-emerald-100 text-xs py-2 border-b border-kemenag-700/60 hidden md:block">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 flex items-center justify-between gap-4">
      <div class="flex items-center gap-5">
        <span class="font-bold">{{ strtoupper($schoolName) }} • E-LIBRARY</span>
        <span>{{ now($schoolTimezone ?? 'Asia/Jakarta')->locale('id')->translatedFormat('j F Y') }}</span>
        <span class="bg-kemenag-950/70 px-2.5 py-0.5 rounded-full border border-emerald-500/30">Online: <strong>{{ number_format($visits['today'] ?? 1, 0, ',', '.') }}</strong></span>
      </div>
      <div class="flex items-center gap-4">
        <span>Waktu Sholat: <strong class="text-accent-yellow">{{ $prayer['next_prayer']['name'] }} {{ $prayer['next_prayer']['time'] }}</strong></span>
        <a href="/" class="hover:text-accent-yellow transition">Beranda Utama</a>
      </div>
    </div>
  </div>
  <div class="bg-kemenag-950 text-white py-2 px-4 sm:px-8 lg:px-12 border-b border-emerald-800/60 hidden md:block">
    <div class="max-w-[1600px] mx-auto flex items-center gap-3 text-xs">
      <span class="bg-amber-400 text-slate-950 font-black px-2.5 py-0.5 rounded-lg text-[10px] shrink-0">WARTA PUSTAKA</span>
      <span class="text-emerald-100 truncate">E-Library 24 Jam: Tersedia koleksi buku teks, referensi keislaman, dan modul digital bebas akses untuk warga madrasah.</span>
    </div>
  </div>
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2.5 flex items-center justify-between gap-4">
      <a href="/perpustakaan" class="flex items-center gap-3 min-w-0 flex-1 md:flex-initial">
        <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-9 h-9 object-contain shrink-0">
        <div class="min-w-0">
          <div class="flex items-center gap-2 min-w-0">
            <span class="font-extrabold text-sm sm:text-base text-slate-900 truncate">{{ strtoupper($schoolName) }}</span>
            <span class="shrink-0 bg-amber-100 text-amber-800 text-[9px] font-extrabold px-2 py-0.5 rounded-md">E-LIBRARY</span>
          </div>
          <span class="text-[10px] text-slate-500 block truncate">E-Perpustakaan Digital • Khazanah Literasi Madrasah</span>
        </div>
      </a>
      <div class="hidden md:flex items-center gap-2">
        <a href="#kunjungan-pustaka" class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold">Buku Tamu</a>
        <a href="/login?portal=siswa" class="px-3.5 py-1.5 rounded-xl bg-amber-500 text-white text-xs font-extrabold">Login Siswa</a>
        <a href="/perpustakaan/kelola" class="px-3.5 py-1.5 rounded-xl bg-kemenag-700 text-white text-xs font-extrabold">Pustakawan</a>
      </div>
      <button data-toggle-mobile aria-controls="mobileMenu" aria-expanded="false" aria-label="Menu navigasi" class="md:hidden shrink-0 w-9 h-9 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
    <div class="hidden md:block bg-slate-50 border-t border-slate-200">
      <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2 flex items-center justify-between text-xs font-semibold text-slate-700">
        <nav class="flex flex-wrap items-center gap-5">
          <a href="/perpustakaan" class="text-emerald-700 font-bold">Beranda Perpus</a>
          <a href="#katalog-pustaka" class="hover:text-emerald-700">Semua Katalog (OPAC)</a>
          <a href="#katalog-pustaka" class="text-amber-600 font-bold">E-Book Digital</a>
          <a href="#kunjungan-pustaka" class="hover:text-emerald-700">Layanan Mandiri</a>
          <a href="/lacak" class="hover:text-emerald-700">Tracking Berkas</a>
          <a href="/" class="hover:text-emerald-700">Portal Madrasah</a>
        </nav>
      </div>
    </div>
    <span class="sr-only">
      <a href="/layanan/buku-tamu">Buku Tamu</a>
      <a href="/galeri/video">Video</a>
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
          <a href="/galeri/video" class="hover:text-accent-yellow transition" aria-label="Galeri video"><i class="fa-brands fa-youtube"></i></a>
          <a href="/kontak" class="hover:text-accent-yellow transition" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
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
  <header data-madrasah-navigation class="sticky top-0 z-40 bg-white/95 backdrop-blur-md shadow-sm border-b border-slate-200">
    <!-- Baris 1: Brand Logo & Tombol Aksi Utama -->
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2.5 flex items-center justify-between gap-4 portal-header-row">

      <!-- Brand Logo -->
      <a href="/" class="portal-brand flex items-center gap-2.5 sm:gap-3 group">
                  <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="w-9 h-9 sm:w-10 sm:h-10 object-contain group-hover:scale-105 transition-transform">
                <div class="flex flex-col justify-center">
          <p class="text-sm sm:text-base lg:text-lg font-bold font-sans text-slate-900 tracking-tight leading-tight group-hover:text-kemenag-700 transition-colors">
            {{ $schoolName }}          </p>
          <span class="text-[10px] text-slate-500 font-medium -mt-0.5 block">Portal Informasi &amp; Akademik Terpadu</span>
        </div>
      </a>

      <!-- Quick Action Right: Daftar Sekolah, Search, Login Siswa, Login, Mobile Toggle -->
      <div class="portal-header-actions flex items-center space-x-2 sm:space-x-3 ml-auto">

        <button data-open-dialog="searchModal" aria-haspopup="dialog" class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-kemenag-700 flex items-center justify-center transition shadow-sm" title="Cari di Portal">
          <i class="fa-solid fa-magnifying-glass text-xs sm:text-sm"></i>
        </button>

        <a href="/login" class="portal-catalogue-login">Login Sistem</a>
        <!-- Tombol Login Siswa (Nuansa Oranye-Emas Landing Page) -->
        <a href="/login?portal=siswa" class="portal-login-action inline-flex items-center gap-1.5 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white text-xs sm:text-[13px] font-bold px-3.5 py-2 rounded-xl shadow-md hover:shadow-orange-500/30 transition-all border border-amber-300/30" title="Masuk Portal Siswa">
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

        <nav class="portal-primary-navigation flex flex-wrap items-center gap-x-6 gap-y-1 text-[13px] font-semibold text-slate-700">
          <a href="/" class="hover:text-kemenag-700 transition py-1 flex items-center gap-1.5" @if (request()->routeIs('public.home')) aria-current="page" @endif>
            <i class="fa-solid fa-house text-xs text-kemenag-600"></i> Beranda
          </a>

          <!-- Profil Dropdown -->
          <div class="relative group">
            <button type="button" data-portal-menu="portal-profile-menu" aria-controls="portal-profile-menu" aria-expanded="false" class="flex items-center gap-1 hover:text-kemenag-700 py-1 transition">
              <span>Profil</span>
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

          <a href="/#zona-integritas" class="hover:text-kemenag-700 transition py-1 flex items-center gap-1 font-semibold text-emerald-800">
            <i class="fa-solid fa-shield-heart text-amber-500 text-xs"></i>
            <span>Zona Integritas</span>
          </a>
          <a href="/layanan/ptsp" class="hover:text-kemenag-700 transition py-1 flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            PTSP Digital
          </a>

          <!-- E-Layanan Mega Menu (3 Kolom Tematik Berkelompok) -->
          <div class="portal-mega-menu-group relative group">
            <button type="button" data-portal-menu="portal-services-menu" aria-controls="portal-services-menu" aria-expanded="false" class="flex items-center gap-1.5 hover:text-kemenag-700 py-1 transition text-emerald-800 font-extrabold">
              <span>E-Layanan</span>
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

          <a href="/#akademik" class="hover:text-kemenag-700 transition py-1">Peminatan</a>
          <a href="/perpustakaan" class="hover:text-kemenag-700 transition py-1">Perpustakaan</a>
          <a href="/alumni" class="hover:text-kemenag-700 transition py-1">Alumni</a>
          <a href="/berita" class="hover:text-kemenag-700 transition py-1">Berita</a>
          <a href="/prestasi" class="hover:text-kemenag-700 transition py-1">Prestasi</a>
          <a href="/galeri" class="hover:text-kemenag-700 transition py-1">Galeri</a>
          <a href="/galeri/video" class="hover:text-kemenag-700 transition py-1">Video</a>
          <a href="/#kontak" class="hover:text-kemenag-700 transition py-1">Kontak</a>
          @if (request()->is('galeri/video*'))
            <a href="#videoModal" data-open-video-featured class="portal-video-subscribe">Subscribe</a>
          @endif
          <a href="/login" class="portal-desktop-catalogue-login">Login Sistem</a>
        </nav>

        <!-- Quick Info Kanan Sub-Navbar -->
        <div class="portal-subnav-quick hidden lg:flex items-center gap-3 text-xs text-slate-500 font-medium">
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
@endif

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
                        <a href="/login?portal=siswa" class="p-2.5 bg-amber-50 hover:bg-orange-100 rounded-xl text-orange-900 font-bold flex items-center gap-1.5 items-center justify-center text-center text-xs border border-orange-200">
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
          <a href="/galeri/video" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">Video Kegiatan</a>
          <a href="/#kontak" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">Kontak &amp; Lokasi</a>
        </nav>
      </div>

      <div class="pt-6 border-t border-slate-100">
        <a href="/#ppdb" class="w-full bg-kemenag-700 text-white font-bold py-3 rounded-xl shadow-md text-center block">
          Daftar PPDB        </a>
      </div>
    </div>
  </div>
