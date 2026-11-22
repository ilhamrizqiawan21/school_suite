  <!-- Footer -->
@if (request()->is('profil-sekolah', 'direktori-gtk'))
  <footer class="bg-kemenag-950 text-white pt-8 pb-6 border-t border-kemenag-800">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="flex flex-col sm:flex-row items-center justify-between text-xs text-emerald-200 gap-3 font-medium">
        <p>© 2026 {{ $schoolName }}. Hak Cipta Dilindungi Undang-Undang.</p>
        <div class="flex items-center gap-4">
          @if (request()->is('direktori-gtk'))
            <a href="{{ ($contentBlocks['copy.blade_footer']['href_122'] ?? '/profil-sekolah') }}" class="hover:text-accent-yellow">{{ ($contentBlocks['copy.blade_footer']['text_003'] ?? 'Visi & Misi') }}</a>
            <a href="{{ ($contentBlocks['copy.blade_footer']['href_123'] ?? '/#zona-integritas') }}" class="hover:text-accent-yellow">{{ ($contentBlocks['copy.blade_footer']['text_004'] ?? 'Zona Integritas') }}</a>
            <a href="{{ ($contentBlocks['copy.blade_footer']['href_124'] ?? '/layanan/ptsp') }}" class="hover:text-accent-yellow">{{ ($contentBlocks['copy.blade_footer']['text_005'] ?? 'PTSP Digital') }}</a>
            <a href="{{ ($contentBlocks['copy.blade_footer']['href_125'] ?? '/layanan/lapor') }}" class="hover:text-accent-yellow">{{ ($contentBlocks['copy.blade_footer']['text_006'] ?? 'Pengaduan') }}</a>
          @else
            <a href="{{ ($contentBlocks['copy.blade_footer']['href_126'] ?? '/') }}" class="hover:text-accent-yellow">{{ ($contentBlocks['copy.blade_footer']['text_007'] ?? 'Beranda') }}</a>
            <a href="{{ ($contentBlocks['copy.blade_footer']['href_127'] ?? '/direktori-gtk') }}" class="hover:text-accent-yellow">{{ ($contentBlocks['copy.blade_footer']['text_008'] ?? 'Dewan Guru') }}</a>
            <a href="{{ ($contentBlocks['copy.blade_footer']['href_123'] ?? '/#zona-integritas') }}" class="hover:text-accent-yellow">{{ ($contentBlocks['copy.blade_footer']['text_004'] ?? 'Zona Integritas') }}</a>
            <a href="{{ ($contentBlocks['copy.blade_footer']['href_124'] ?? '/layanan/ptsp') }}" class="hover:text-accent-yellow">{{ ($contentBlocks['copy.blade_footer']['text_005'] ?? 'PTSP Digital') }}</a>
          @endif
        </div>
      </div>
    </div>
  </footer>
@elseif (request()->is('layanan/buku-tamu*'))
  {{-- Halaman Buku Tamu Digital menggunakan tampilan penuh tanpa footer sesuai desain acuan --}}
@elseif (request()->is('layanan/lapor*'))
  <footer class="parity-lapor-footer">
    <div class="parity-lapor-container">
      <div class="parity-lapor-footer-top">
        <div class="parity-lapor-footer-brand">
          <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="parity-lapor-footer-logo">
          <div>
            <div class="parity-lapor-footer-title">Siap Lapor — {{ strtoupper($schoolName) }}</div>
            <p class="parity-lapor-footer-sub">{{ ($contentBlocks['copy.blade_footer']['text_010'] ?? 'Sistem Pengelolaan Aspirasi & Pengaduan Pelayanan Publik Terpadu') }}</p>
          </div>
        </div>
        <div class="parity-lapor-footer-links">
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_126'] ?? '/') }}">{{ ($contentBlocks['copy.blade_footer']['text_011'] ?? 'Website Madrasah') }}</a>
          <span>•</span>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_124'] ?? '/layanan/ptsp') }}">{{ ($contentBlocks['copy.blade_footer']['text_012'] ?? 'Layanan PTSP') }}</a>
          <span>•</span>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_128'] ?? '/satgas/aduan') }}">{{ ($contentBlocks['copy.blade_footer']['text_013'] ?? 'Login Satgas') }}</a>
        </div>
      </div>
      <div class="parity-lapor-footer-bottom">
        <p>© 2026 {{ strtoupper($schoolName) }}. Hak Cipta Dilindungi Undang-Undang.</p>
        <p>{{ ($contentBlocks['copy.blade_footer']['text_014'] ?? 'Portal Pengaduan & Aspirasi Digital Terpadu.') }}</p>
      </div>
    </div>
  </footer>
@elseif (request()->is('layanan/ptsp'))
  <footer class="bg-kemenag-950 text-emerald-100 pt-6 pb-6 border-t border-kemenag-800 mt-auto">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 text-center text-xs space-y-2">
      <div class="flex flex-wrap items-center justify-center gap-3">
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_126'] ?? '/') }}" class="font-bold text-accent-yellow hover:underline flex items-center gap-1"><i class="fa-solid fa-globe"></i> {{ ($contentBlocks['copy.blade_footer']['text_016'] ?? 'Website Utama Madrasah') }}</a>
        <span class="text-emerald-600">•</span>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_129'] ?? '/lacak') }}" class="hover:text-accent-yellow flex items-center gap-1"><i class="fa-solid fa-magnifying-glass"></i> {{ ($contentBlocks['copy.blade_footer']['text_017'] ?? 'Tracking Tiket') }}</a>
        <span class="text-emerald-600">•</span>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_125'] ?? '/layanan/lapor') }}" class="hover:text-accent-yellow flex items-center gap-1"><i class="fa-solid fa-shield-heart"></i> {{ ($contentBlocks['copy.blade_footer']['text_018'] ?? 'Siap Lapor') }}</a>
      </div>
      <p>© 2026 <strong class="text-white">{{ $schoolName }}</strong> {{ ($contentBlocks['copy.blade_footer']['text_019'] ?? '• Pelayanan Terpadu Satu Pintu (PTSP) Digital Terpadu.') }}</p>
    </div>
  </footer>
@elseif (request()->is('layanan/izin-siswa'))
  <footer class="permit-footer">
    <p>© {{ now()->year }} {{ strtoupper($schoolName) }}. Seluruh Hak Cipta Dilindungi.</p>
    <small>{{ ($contentBlocks['copy.blade_footer']['text_021'] ?? 'Sistem Administrasi dan Layanan Akademik Sekolah') }}</small>
  </footer>
@elseif (request()->is('layanan/kelulusan'))
  <footer class="graduation-reference-footer">© {{ now()->year }} <strong>{{ strtoupper($schoolName) }}</strong> {{ ($contentBlocks['copy.blade_footer']['text_023'] ?? '• Portal Pengumuman Kelulusan & SKL') }}</footer>
@elseif (request()->is('lacak'))
  <footer class="tracking-reference-footer">© {{ now()->year }} <strong>{{ strtoupper($schoolName) }}</strong> {{ ($contentBlocks['copy.blade_footer']['text_025'] ?? '• Pusat Lacak Status Layanan Terpadu') }}</footer>
@elseif (request()->is('alumni*'))
  <footer class="ika-footer">
    <div class="ika-footer-columns">
      <div class="space-y-3">
        <div class="ika-footer-brand">
          <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}">
          <div>
            <strong class="block text-base text-white">{{ ($contentBlocks['copy.blade_footer']['text_027'] ?? 'IKATAN KELUARGA ALUMNI (IKA)') }}</strong>
            <span class="text-xs text-slate-400">{{ strtoupper($schoolName) }}</span>
          </div>
        </div>
        <p class="text-xs text-slate-400 leading-relaxed">{{ ($contentBlocks['copy.blade_footer']['text_028'] ?? 'Wadah pemersatu seluruh alumni lintas angkatan, mendukung kemajuan almamater, karir profesional lulusan, dan kontribusi nyata bagi masyarakat serta bangsa.') }}</p>
        <div class="flex items-center space-x-2.5 text-slate-400 pt-1">
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_130'] ?? '/kontak') }}" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-blue-700 hover:text-white flex items-center justify-center transition !mb-0" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_131'] ?? 'Facebook') }}"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_130'] ?? '/kontak') }}" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-blue-700 hover:text-white flex items-center justify-center transition !mb-0" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_132'] ?? 'Instagram') }}"><i class="fa-brands fa-instagram"></i></a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_133'] ?? '/galeri/video') }}" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-blue-700 hover:text-white flex items-center justify-center transition !mb-0" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_134'] ?? 'YouTube') }}"><i class="fa-brands fa-youtube"></i></a>
        </div>
      </div>
      <div>
        <h2 class="ika-footer-heading ika-footer-heading--blue">{{ ($contentBlocks['copy.blade_footer']['text_029'] ?? 'MENU PORTAL') }}</h2>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_135'] ?? '/alumni#ika-home-title') }}"><i class="fa-solid fa-angle-right text-blue-500 mr-1.5"></i> {{ ($contentBlocks['copy.blade_footer']['text_030'] ?? 'Beranda Alumni') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_136'] ?? '/alumni#direktori-alumni') }}" data-ika-tab="directory"><i class="fa-solid fa-angle-right text-blue-500 mr-1.5"></i> {{ ($contentBlocks['copy.blade_footer']['text_031'] ?? 'Direktori Temu Alumni') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_137'] ?? '/layanan/tracer-alumni') }}" data-ika-tab="tracer"><i class="fa-solid fa-angle-right text-blue-500 mr-1.5"></i> {{ ($contentBlocks['copy.blade_footer']['text_032'] ?? 'Form Tracer Study Mandiri') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_138'] ?? '/alumni?section=careers#ika-portal-tabs') }}" data-ika-tab="careers"><i class="fa-solid fa-angle-right text-blue-500 mr-1.5"></i> {{ ($contentBlocks['copy.blade_footer']['text_033'] ?? 'Bursa Karir & Magang') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_139'] ?? '/alumni?section=scholarships#ika-portal-tabs') }}" data-ika-tab="scholarships"><i class="fa-solid fa-angle-right text-blue-500 mr-1.5"></i> {{ ($contentBlocks['copy.blade_footer']['text_034'] ?? 'Info Beasiswa Aktif') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_140'] ?? '/layanan/tracer-alumni#alumni-kta') }}" data-ika-tab="kta"><i class="fa-solid fa-angle-right text-blue-500 mr-1.5"></i> {{ ($contentBlocks['copy.blade_footer']['text_035'] ?? 'Cetak KTA Digital Ber-QR') }}</a>
      </div>
      <div>
        <h2 class="ika-footer-heading ika-footer-heading--gold">{{ ($contentBlocks['copy.blade_footer']['text_036'] ?? 'LAYANAN AKADEMIK') }}</h2>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_141'] ?? '/layanan/legalisir') }}"><i class="fa-solid fa-angle-right text-amber-400 mr-1.5"></i> {{ ($contentBlocks['copy.blade_footer']['text_037'] ?? 'Legalisir Ijazah Online ↗') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_124'] ?? '/layanan/ptsp') }}"><i class="fa-solid fa-angle-right text-amber-400 mr-1.5"></i> {{ ($contentBlocks['copy.blade_footer']['text_038'] ?? 'PTSP Terpadu Online') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_129'] ?? '/lacak') }}"><i class="fa-solid fa-angle-right text-amber-400 mr-1.5"></i> {{ ($contentBlocks['copy.blade_footer']['text_039'] ?? 'Lacak Status Berkas') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_142'] ?? '/layanan/kelulusan') }}"><i class="fa-solid fa-angle-right text-amber-400 mr-1.5"></i> {{ ($contentBlocks['copy.blade_footer']['text_040'] ?? 'Cek SKL & Kelulusan') }}</a>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_126'] ?? '/') }}"><i class="fa-solid fa-angle-right text-amber-400 mr-1.5"></i> {{ ($contentBlocks['copy.blade_footer']['text_041'] ?? 'Website Resmi Sekolah') }}</a>
      </div>
      <div>
        <h2 class="ika-footer-heading ika-footer-heading--emerald">{{ ($contentBlocks['copy.blade_footer']['text_042'] ?? 'SEKRETARIAT IKA') }}</h2>
        <p class="flex items-start gap-2.5 text-xs"><i class="fa-solid fa-location-dot text-rose-500 mt-1"></i> <span>{{ $schoolAddress }}</span></p>
        @if ($schoolPhone)<p class="flex items-center gap-2.5 text-xs"><i class="fa-solid fa-phone text-emerald-500"></i> <span>{{ $schoolPhone }}</span></p>@endif
        @if ($schoolEmail)<p class="flex items-center gap-2.5 text-xs"><i class="fa-solid fa-envelope text-blue-400"></i> <span>{{ $schoolEmail }}</span></p>@endif
        <p class="flex items-center gap-2.5 text-xs pt-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span> <strong class="text-white">{{ ($contentBlocks['copy.blade_footer']['text_043'] ?? 'Layanan IKA: Aktif') }}</strong></p>
      </div>
    </div>
    <div class="ika-footer-bottom">
      <span>© {{ now()->year }} Ikatan Keluarga Alumni (IKA) {{ strtoupper($schoolName) }}. All Rights Reserved.</span>
      <div class="flex items-center gap-3">
        <span>{{ ($contentBlocks['copy.blade_footer']['text_044'] ?? 'Portal Resmi Alumni Terintegrasi') }}</span>
        <span>•</span>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_143'] ?? '#ika-home-title') }}" class="!inline !mb-0 text-slate-300 hover:text-amber-400">{{ ($contentBlocks['copy.blade_footer']['text_045'] ?? 'Kembali ke Atas ↑') }}</a>
      </div>
    </div>
  </footer>
@elseif (request()->is('data-siswa', 'layanan/tracer-alumni'))
  <footer class="bg-slate-900 px-4 py-5 text-center text-xs leading-6 text-slate-400">
    <p>© {{ now()->year }} {{ strtoupper($schoolName) }}. All Rights Reserved.</p>
    <p class="text-[11px] text-slate-400">{{ ($contentBlocks['copy.blade_footer']['text_047'] ?? 'Aplikasi Database Sistem Informasi Akademik & Kesiswaan Terpadu') }}</p>
  </footer>
@elseif (request()->is('lacak', 'layanan/legalisir', 'layanan/mutasi', 'layanan/kelulusan', 'layanan/izin-siswa', 'layanan/surat-kuasa'))
  @php
    $akademikFooterTitle = match (true) {
        request()->is('layanan/mutasi*') => 'Aplikasi Database & Administrasi Mutasi Siswa',
        request()->is('layanan/legalisir*') => 'Aplikasi Database & Administrasi Legalisir Ijazah',
        request()->is('layanan/izin-siswa*') => 'Aplikasi Database & Administrasi Surat Izin Siswa',
        request()->is('layanan/kelulusan*') => 'Aplikasi Database & Administrasi Kelulusan Siswa',
        request()->is('layanan/surat-kuasa*') => 'Aplikasi Database & Administrasi Surat Kuasa & SPTJM',
        default => 'Aplikasi Database & Lacak Layanan Terpadu',
    };
  @endphp
  <footer class="parity-akademik-footer mt-auto">
    <div class="parity-akademik-footer-inner">
      <p class="parity-akademik-footer-title">{{ $akademikFooterTitle }} © {{ now()->year }} {{ strtoupper($schoolName) }}</p>
      <small class="parity-akademik-footer-sub">{{ $schoolName }} - Melayani dengan Sepenuh Hati</small>
    </div>
  </footer>
@else
  <footer class="bg-kemenag-950 text-white pt-10 pb-6 border-t border-kemenag-800 mt-auto">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 pb-8 border-b border-kemenag-800/80">

        <!-- Info Brand -->
        @php
          $footerBlock = $contentBlocks['shell.footer'] ?? [];
          $footerTagline = $footerBlock['tagline'] ?? 'Madrasah Aliyah Negeri unggulan yang mengedepankan pendidikan berbasis adiwiyata, karakter islami, dan riset sains teknologi modern.';
          $socialLinks = $profile['social_links'] ?? [];
        @endphp
        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <div class="w-10 h-10 rounded-xl bg-kemenag-800 flex items-center justify-center text-accent-yellow text-xl border border-emerald-500/40 shadow-sm">
              <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <span class="font-extrabold text-lg text-white font-sans">{{ $schoolName }}</span>
          </div>
          <p class="text-xs text-emerald-200 leading-relaxed">
            {{ $footerTagline }}
          </p>
          <div class="flex items-center space-x-3 text-emerald-300 pt-1">
            <a href="{{ $socialLinks['facebook'] ?? '/kontak' }}" class="hover:text-accent-yellow transition"><i class="fa-brands fa-facebook"></i></a>
            <a href="{{ $socialLinks['instagram'] ?? '/kontak' }}" class="hover:text-accent-yellow transition"><i class="fa-brands fa-instagram"></i></a>
            <a href="{{ $socialLinks['youtube'] ?? '/galeri/video' }}" class="hover:text-accent-yellow transition" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_144'] ?? 'Galeri video') }}"><i class="fa-brands fa-youtube"></i></a>
            <a href="{{ $socialLinks['tiktok'] ?? '/kontak' }}" class="hover:text-accent-yellow transition"><i class="fa-brands fa-tiktok"></i></a>
          </div>
        </div>

        <!-- Tautan Pintas Modul Akademik -->
        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-accent-yellow mb-3">{{ ($contentBlocks['copy.blade_footer']['text_048'] ?? 'Modul & Akademik') }}</h4>
          <ul class="space-y-2 text-xs text-emerald-100">
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_145'] ?? '/profil-sekolah#arah') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_049'] ?? 'Visi & Misi Madrasah') }}</a></li>
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_127'] ?? '/direktori-gtk') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_050'] ?? 'Direktori Dewan Guru & GTK') }}</a></li>
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_146'] ?? '/login') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_051'] ?? 'Login Guru / Siswa / Admin') }}</a></li>
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_147'] ?? '/perpustakaan') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_052'] ?? 'Perpustakaan Digital SLiMS') }}</a></li>
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_137'] ?? '/layanan/tracer-alumni') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_053'] ?? 'Portal Tracer Study Alumni') }}</a></li>
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_142'] ?? '/layanan/kelulusan') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_054'] ?? 'Pengumuman Kelulusan SKL') }}</a></li>
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_125'] ?? '/layanan/lapor') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_055'] ?? 'Pusat Pengaduan (Lapor)') }}</a></li>
          </ul>
        </div>

        <!-- Layanan PTSP Terpadu -->
        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-accent-yellow mb-3">{{ ($contentBlocks['copy.blade_footer']['text_056'] ?? 'Layanan Terpadu (PTSP)') }}</h4>
          <ul class="space-y-2 text-xs text-emerald-100">
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_124'] ?? '/layanan/ptsp') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_038'] ?? 'PTSP Terpadu Online') }}</a></li>
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_141'] ?? '/layanan/legalisir') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_057'] ?? 'Legalisir Ijazah Online') }}</a></li>
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_148'] ?? '/layanan/mutasi') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_058'] ?? 'Permohonan Mutasi Siswa') }}</a></li>
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_149'] ?? '/layanan/izin-siswa') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_059'] ?? 'Surat Izin Siswa Online') }}</a></li>
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_150'] ?? '/piket/izin-keluar') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_060'] ?? 'Izin Keluar-Masuk (Piket)') }}</a></li>
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_151'] ?? '/pos-jaga') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_061'] ?? 'Scan Pos Satpam (Gerbang)') }}</a></li>
            <li><a href="{{ ($contentBlocks['copy.blade_footer']['href_129'] ?? '/lacak') }}" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_footer']['text_039'] ?? 'Lacak Status Berkas') }}</a></li>
          </ul>
        </div>

        <!-- Alamat -->
        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-accent-yellow mb-3">{{ ($contentBlocks['copy.blade_footer']['text_062'] ?? 'Alamat') }}</h4>
          <p class="text-xs text-emerald-100 leading-relaxed">
            {{ $schoolAddress }}          </p>
          <p class="text-xs text-emerald-100 mt-2">
            {{ ($contentBlocks['copy.blade_footer']['text_063'] ?? 'Telepon:') }} <span class="text-accent-yellow font-semibold">{{ $schoolPhone }}</span>
          </p>
          <p class="text-xs text-emerald-100 mt-1">
            {{ ($contentBlocks['copy.blade_footer']['text_064'] ?? 'Email:') }} <a href="{{ ($contentBlocks['copy.blade_footer']['href_130'] ?? '/kontak') }}" class="underline text-accent-yellow">{{ $schoolEmail }}</a>
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
                <h4 class="text-xs font-bold text-white uppercase tracking-wider">{{ ($contentBlocks['copy.blade_footer']['text_065'] ?? 'Statistik Kunjungan Web') }}</h4>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> {{ ($contentBlocks['copy.blade_footer']['text_066'] ?? 'Real-Time') }}
                </span>
              </div>
              <p class="text-[11px] text-emerald-200">Total tayangan portal {{ $schoolName }}.</p>
            </div>
          </div>

          <!-- 4 Metrik Trafik Kunjungan -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4 w-full md:w-auto text-xs">
            <div class="p-2.5 rounded-xl bg-kemenag-900/80 border border-emerald-500/30 text-center min-w-[110px]">
              <span class="text-[10px] text-emerald-300 font-semibold block uppercase">{{ ($contentBlocks['copy.blade_footer']['text_067'] ?? 'Tanggal') }}</span>
              <span class="text-base sm:text-lg font-extrabold text-accent-yellow flex items-center justify-center gap-1.5 mt-0.5">
                <span class="fa-solid fa-calendar-day" aria-hidden="true"></span>
                <span id="liveTrafficDate">{{ $prayer['date'] }}</span>
              </span>
            </div>
            <div class="p-2.5 rounded-xl bg-kemenag-900/80 border border-emerald-500/30 text-center min-w-[110px]">
              <span class="text-[10px] text-emerald-300 font-semibold block uppercase">{{ ($contentBlocks['copy.blade_footer']['text_068'] ?? 'Hari Ini') }}</span>
              <span id="liveTrafficToday" class="text-base sm:text-lg font-extrabold text-white mt-0.5 block">{{ number_format($visits['today'], 0, ',', '.') }}</span>
            </div>
            <div class="p-2.5 rounded-xl bg-kemenag-900/80 border border-emerald-500/30 text-center min-w-[110px]">
              <span class="text-[10px] text-emerald-300 font-semibold block uppercase">{{ ($contentBlocks['copy.blade_footer']['text_069'] ?? 'Bulan Ini') }}</span>
              <span id="liveTrafficMonth" class="text-base sm:text-lg font-extrabold text-emerald-200 mt-0.5 block">{{ number_format($visits['month'], 0, ',', '.') }}</span>
            </div>
            <div class="p-2.5 rounded-xl bg-kemenag-900/80 border border-emerald-500/30 text-center min-w-[110px]">
              <span class="text-[10px] text-emerald-300 font-semibold block uppercase">{{ ($contentBlocks['copy.blade_footer']['text_070'] ?? 'Total Kunjungan') }}</span>
              <span id="liveTrafficTotal" class="text-base sm:text-lg font-extrabold text-accent-yellow mt-0.5 block">{{ number_format($visits['total'], 0, ',', '.') }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="pt-5 flex flex-col sm:flex-row items-center justify-between text-xs text-emerald-200 gap-3 font-medium">
        <p>© 2026 {{ $schoolName }}. Hak Cipta Dilindungi Undang-Undang.</p>
        <p>{{ ($contentBlocks['copy.blade_footer']['text_071'] ?? 'Kementerian Agama Republik Indonesia.') }}</p>
      </div>

    </div>
  </footer>
@endif

@unless (request()->is('data-siswa', 'alumni', 'layanan/izin-siswa'))
  <!-- ================= FLOATING ACTION BUTTONS ================= -->
  <div class="fixed z-40 flex flex-col items-end space-y-3" style="bottom: 104px; right: 43px;">
    <!-- Back to Top Button -->
    <button id="backToTopBtn" data-scroll-top aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_152'] ?? 'Kembali ke atas') }}" class="w-10 h-10 rounded-full bg-slate-900/80 hover:bg-slate-900 text-white shadow-xl flex items-center justify-center text-sm transition-all duration-300 opacity-0 pointer-events-none translate-y-3">
      <i class="fa-solid fa-arrow-up"></i>
    </button>
  </div>
@endunless

  <!-- ================= MODAL WINDOWS ================= -->

  <!-- 1. Modal Jadwal Sholat -->
  <div id="prayerModal" role="dialog" aria-modal="true" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_153'] ?? 'Jadwal salat') }}" tabindex="-1" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl relative">
      <button data-close-dialog aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_154'] ?? 'Tutup dialog') }}" class="absolute right-4 top-4 w-8 h-8 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center">
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
  <div id="downloadModal" role="dialog" aria-modal="true" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_155'] ?? 'Pusat unduh') }}" tabindex="-1" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-7 shadow-2xl relative max-h-[90vh] overflow-y-auto">
      <button data-close-dialog aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_154'] ?? 'Tutup dialog') }}" class="absolute right-4 top-4 w-8 h-8 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="flex items-center gap-3 mb-5">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-kemenag-700 flex items-center justify-center text-xl shrink-0">
          <i class="fa-solid fa-cloud-arrow-down"></i>
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-900">{{ ($contentBlocks['copy.blade_footer']['text_073'] ?? 'Pusat Unduh Berkas & Dokumen Resmi') }}</h3>
          <p class="text-xs text-slate-500">{{ ($contentBlocks['copy.blade_footer']['text_074'] ?? 'Dokumen formulir, brosur PPDB, kalender akademik, dan tata tertib madrasah.') }}</p>
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
              <i class="fa-solid fa-cloud-arrow-down" aria-hidden="true"></i> {{ ($contentBlocks['copy.blade_footer']['text_075'] ?? 'Unduh Berkas') }}
            </a>
          </div>
        @empty
          <p class="text-slate-600">{{ ($contentBlocks['copy.blade_footer']['text_076'] ?? 'Belum ada berkas yang diterbitkan.') }}</p>
        @endforelse
      </div>
      <a href="{{ ($contentBlocks['copy.blade_footer']['href_156'] ?? '/pusat-unduh') }}" class="inline-flex mt-4 text-sm font-bold text-kemenag-700">{{ ($contentBlocks['copy.blade_footer']['text_077'] ?? 'Buka Pusat Unduh') }}</a>
    </div>
  </div>

  <!-- 3. Modal Zona Integritas & IKM -->
  <div id="ziModal" role="dialog" aria-modal="true" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_157'] ?? 'Zona Integritas') }}" tabindex="-1" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl relative">
      <button data-close-dialog aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_154'] ?? 'Tutup dialog') }}" class="absolute right-4 top-4 w-8 h-8 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-kemenag-700 flex items-center justify-center text-xl shrink-0">
          <i class="fa-solid fa-shield-halved text-accent-gold"></i>
        </div>
        <div>
          <span class="bg-emerald-100 text-kemenag-800 text-[10px] font-extrabold px-2 py-0.5 rounded uppercase">{{ ($contentBlocks['copy.blade_footer']['text_078'] ?? 'Wilayah Bebas dari Korupsi (WBK)') }}</span>
          <h3 class="text-base font-bold text-slate-900 mt-1">{{ ($contentBlocks['copy.blade_footer']['text_079'] ?? 'Zona Integritas & Survei IKM') }}</h3>
        </div>
      </div>

      <p class="text-slate-600 text-xs leading-relaxed mb-4">
        {{ $schoolName }} berkomitmen memberikan pelayanan prima tanpa gratifikasi, tanpa pungutan liar, dan transparan. Dukung mutu pelayanan kami melalui penilaian berikut:
      </p>

      <form method="post" action="{{ route('core.public.ikm.store') }}" class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-center">
        @csrf
        <input type="hidden" name="respondent_role" value="umum">
        <input type="hidden" name="service_name" value="PTSP">
        <span class="text-xs font-bold text-slate-800 block mb-2">{{ ($contentBlocks['copy.blade_footer']['text_080'] ?? 'Beri Nilai Kepuasan Layanan PTSP:') }}</span>
        <div class="flex items-center justify-center gap-2 text-2xl text-amber-400 cursor-pointer mb-2">
          <button type="submit" name="rating" value="1" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_158'] ?? 'Beri 1 bintang') }}"><i class="fa-solid fa-star hover:scale-125 transition"  aria-hidden="true"></i></button>
          <button type="submit" name="rating" value="2" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_159'] ?? 'Beri 2 bintang') }}"><i class="fa-solid fa-star hover:scale-125 transition"  aria-hidden="true"></i></button>
          <button type="submit" name="rating" value="3" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_160'] ?? 'Beri 3 bintang') }}"><i class="fa-solid fa-star hover:scale-125 transition"  aria-hidden="true"></i></button>
          <button type="submit" name="rating" value="4" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_161'] ?? 'Beri 4 bintang') }}"><i class="fa-solid fa-star hover:scale-125 transition"  aria-hidden="true"></i></button>
          <button type="submit" name="rating" value="5" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_162'] ?? 'Beri 5 bintang') }}"><i class="fa-solid fa-star hover:scale-125 transition"  aria-hidden="true"></i></button>
        </div>
        <span id="ikmFeedback" aria-live="polite" @if(session('message')) data-ikm-submitted @endif class="text-[11px] text-kemenag-700 font-semibold block">{{ session('message') ?? 'Pilih jumlah bintang untuk menilai layanan PTSP.' }}</span>
      </form>

      <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
        <button type="button" data-open-dialog="ziAreaModal" aria-haspopup="dialog" class="text-kemenag-700 hover:text-kemenag-800 font-bold flex items-center gap-1">
          <i class="fa-solid fa-cubes-stacked text-amber-500"></i> {{ ($contentBlocks['copy.blade_footer']['text_081'] ?? 'Pelajari 6 Area ZI →') }}
        </button>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_125'] ?? '/layanan/lapor') }}" class="text-rose-600 hover:text-rose-700 font-bold flex items-center gap-1">
          <i class="fa-solid fa-bullhorn"></i> {{ ($contentBlocks['copy.blade_footer']['text_082'] ?? 'Kanal Pengaduan') }}
        </a>
      </div>
    </div>
  </div>

  <!-- Modal 6 Area Perubahan Zona Integritas (WBK & WBBM) -->
  <div id="ziAreaModal" role="dialog" aria-modal="true" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_163'] ?? 'Enam area Zona Integritas') }}" tabindex="-1" class="fixed inset-0 bg-slate-950/75 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-7 shadow-2xl relative max-h-[90vh] overflow-y-auto">
      <button data-close-dialog aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_154'] ?? 'Tutup dialog') }}" class="absolute right-4 top-4 w-8 h-8 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center transition">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="flex items-center gap-3.5 mb-5 pb-4 border-b border-slate-100">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl shrink-0 shadow-xs">
          <i class="fa-solid fa-cubes-stacked text-accent-gold"></i>
        </div>
        <div>
          <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-wider">{{ ($contentBlocks['copy.blade_footer']['text_083'] ?? 'Reformasi Birokrasi') }}</span>
          <h3 class="text-base sm:text-lg font-extrabold text-slate-900 mt-0.5 leading-snug">{{ ($contentBlocks['copy.blade_footer']['text_084'] ?? '6 Area Perubahan Zona Integritas') }}</h3>
          <p class="text-xs text-slate-500">Peta jalan pembangunan integritas &amp; pelayanan prima di {{ $schoolName }}</p>
        </div>
      </div>

      <div class="space-y-3 text-xs">
        <!-- Area 1 -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-400 transition flex items-start gap-3">
          <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs">1</span>
          <div>
            <div class="flex items-center justify-between">
              <strong class="text-slate-900 text-xs font-extrabold">{{ ($contentBlocks['copy.blade_footer']['text_085'] ?? 'Manajemen Perubahan') }}</strong>
              <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.2 rounded-full">{{ ($contentBlocks['copy.blade_footer']['text_086'] ?? 'Pondasi Sikap') }}</span>
            </div>
            <p class="text-slate-600 text-[11.5px] mt-1 leading-relaxed">
              {{ ($contentBlocks['copy.blade_footer']['text_087'] ?? 'Internalisasi budaya kerja 5 Nilai Budaya Kerja Kemenag, pembentukan Tim Kerja ZI, penetapan Agen Perubahan (Agent of Change), dan penandatanganan Pakta Integritas seluruh GTK.') }}
            </p>
          </div>
        </div>

        <!-- Area 2 -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-400 transition flex items-start gap-3">
          <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs">2</span>
          <div>
            <div class="flex items-center justify-between">
              <strong class="text-slate-900 text-xs font-extrabold">{{ ($contentBlocks['copy.blade_footer']['text_088'] ?? 'Penataan Tatalaksana (SPBE Digital)') }}</strong>
              <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.2 rounded-full">{{ ($contentBlocks['copy.blade_footer']['text_089'] ?? 'Digitalisasi') }}</span>
            </div>
            <p class="text-slate-600 text-[11.5px] mt-1 leading-relaxed">
              {{ ($contentBlocks['copy.blade_footer']['text_090'] ?? 'Standard Operating Procedure (SOP) terstandar, implementasi PTSP Digital, persuratan elektronik, buku tamu digital, keterbukaan informasi publik, dan integrasi EMIS 4.0.') }}
            </p>
          </div>
        </div>

        <!-- Area 3 -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-400 transition flex items-start gap-3">
          <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs">3</span>
          <div>
            <div class="flex items-center justify-between">
              <strong class="text-slate-900 text-xs font-extrabold">{{ ($contentBlocks['copy.blade_footer']['text_091'] ?? 'Penataan Sistem Manajemen SDM') }}</strong>
              <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.2 rounded-full">{{ ($contentBlocks['copy.blade_footer']['text_092'] ?? 'Profesionalisme') }}</span>
            </div>
            <p class="text-slate-600 text-[11.5px] mt-1 leading-relaxed">
              {{ ($contentBlocks['copy.blade_footer']['text_093'] ?? 'Peningkatan kompetensi berkelanjutan guru & tendik, penegakan disiplin kehadiran presensi digital, pembinaan berkala, serta reward bagi GTK berprestasi.') }}
            </p>
          </div>
        </div>

        <!-- Area 4 -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-400 transition flex items-start gap-3">
          <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs">4</span>
          <div>
            <div class="flex items-center justify-between">
              <strong class="text-slate-900 text-xs font-extrabold">{{ ($contentBlocks['copy.blade_footer']['text_094'] ?? 'Penguatan Akuntabilitas Kinerja') }}</strong>
              <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.2 rounded-full">{{ ($contentBlocks['copy.blade_footer']['text_095'] ?? 'Transparansi') }}</span>
            </div>
            <p class="text-slate-600 text-[11.5px] mt-1 leading-relaxed">
              {{ ($contentBlocks['copy.blade_footer']['text_096'] ?? 'Keterlibatan pimpinan dalam penyusunan Rencana Kinerja Tahunan (RKT), Perjanjian Kinerja (PK), LAKIP/LKjIP, serta publikasi realisasi anggaran DIPA & BOS madrasah.') }}
            </p>
          </div>
        </div>

        <!-- Area 5 -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-400 transition flex items-start gap-3">
          <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs">5</span>
          <div>
            <div class="flex items-center justify-between">
              <strong class="text-slate-900 text-xs font-extrabold">{{ ($contentBlocks['copy.blade_footer']['text_097'] ?? 'Penguatan Pengawasan & WBS') }}</strong>
              <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.2 rounded-full">{{ ($contentBlocks['copy.blade_footer']['text_098'] ?? 'Anti Korupsi') }}</span>
            </div>
            <p class="text-slate-600 text-[11.5px] mt-1 leading-relaxed">
              {{ ($contentBlocks['copy.blade_footer']['text_099'] ?? 'Unit Pengendalian Gratifikasi (UPG), penanganan Benturan Kepentingan (Conflict of Interest), Whistleblowing System mandiri (Siap Lapor), serta kanal SP4N LAPOR.') }}
            </p>
          </div>
        </div>

        <!-- Area 6 -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-400 transition flex items-start gap-3">
          <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs">6</span>
          <div>
            <div class="flex items-center justify-between">
              <strong class="text-slate-900 text-xs font-extrabold">{{ ($contentBlocks['copy.blade_footer']['text_100'] ?? 'Peningkatan Kualitas Pelayanan Publik') }}</strong>
              <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.2 rounded-full">{{ ($contentBlocks['copy.blade_footer']['text_101'] ?? 'Pelayanan Prima') }}</span>
            </div>
            <p class="text-slate-600 text-[11.5px] mt-1 leading-relaxed">
              {{ ($contentBlocks['copy.blade_footer']['text_102'] ?? 'Maklumat Pelayanan, ruang tunggu PTSP ramah disabilitas, kepastian standar operasional, inovasi layanan digital bebas antrean, dan Survei Kepuasan Masyarakat (IKM).') }}
            </p>
          </div>
        </div>
      </div>

      <div class="mt-5 pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs">
        <span class="text-slate-500 text-[11px]">{{ ($contentBlocks['copy.blade_footer']['text_103'] ?? 'Ada indikasi penyimpangan?') }}</span>
        <a href="{{ ($contentBlocks['copy.blade_footer']['href_125'] ?? '/layanan/lapor') }}" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 shadow-sm">
          <i class="fa-solid fa-shield-heart"></i>
          <span>{{ ($contentBlocks['copy.blade_footer']['text_104'] ?? 'Kirim Laporan Pengaduan') }}</span>
        </a>
      </div>
    </div>
  </div>

  <!-- 4. Lightbox Modal Foto Galeri -->
  <div id="lightboxModal" role="dialog" aria-modal="true" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_164'] ?? 'Foto galeri') }}" tabindex="-1" class="fixed inset-0 bg-slate-950/90 z-50 hidden backdrop-blur-md flex items-center justify-center p-4">
    <div class="relative max-w-4xl w-full text-center">
      <button data-close-dialog aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_154'] ?? 'Tutup dialog') }}" class="absolute -top-10 right-0 text-white text-2xl hover:text-accent-yellow">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <img id="lightboxImg" src="{{ ($contentBlocks['copy.blade_footer']['src_165'] ?? '/madrasah-theme/images/campus.webp') }}" alt="{{ ($contentBlocks['copy.blade_footer']['alt_166'] ?? 'Dokumentasi') }}" class="max-h-[80vh] mx-auto rounded-2xl shadow-2xl border-2 border-emerald-500/30">
      <p id="lightboxCaption" class="text-white text-sm font-semibold mt-3"></p>
    </div>
  </div>

  <!-- Modal Baca Berita Lengkap -->
  <div id="beritaModal" role="dialog" aria-modal="true" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_167'] ?? 'Baca berita') }}" tabindex="-1" class="fixed inset-0 bg-slate-950/80 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl relative p-6">
      <button data-close-dialog aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_154'] ?? 'Tutup dialog') }}" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition">
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
          <img id="bmGambar" src="{{ ($contentBlocks['copy.blade_footer']['src_165'] ?? '/madrasah-theme/images/campus.webp') }}" alt="{{ ($contentBlocks['copy.blade_footer']['alt_168'] ?? 'Thumbnail') }}" class="w-full h-full object-cover">
        </div>
        <div id="bmIsi" class="text-slate-700 text-sm leading-relaxed space-y-3 whitespace-pre-line"></div>
      </div>
    </div>
  </div>

  <!-- 5. Quick Global Search Modal -->
  <div id="searchModal" role="dialog" aria-modal="true" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_169'] ?? 'Cari layanan') }}" tabindex="-1" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm flex items-start justify-center pt-20 p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 shadow-2xl relative">
      <form action="/e-layanan" method="get" class="flex items-center gap-2 border-b border-slate-200 pb-3">
        <i class="fa-solid fa-magnifying-glass text-slate-400" aria-hidden="true"></i>
        <input type="search" name="q" id="globalSearchInput" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_170'] ?? 'Kata kunci layanan') }}" placeholder="{{ ($contentBlocks['copy.blade_footer']['placeholder_171'] ?? 'Cari layanan School Suite') }}" class="w-full text-xs sm:text-sm outline-none">
        <button type="submit" class="bg-kemenag-700 text-white font-bold px-3 py-2 rounded-xl text-xs">{{ ($contentBlocks['copy.blade_footer']['text_105'] ?? 'Cari') }}</button>
        <button type="button" data-close-dialog aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_154'] ?? 'Tutup dialog') }}" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </form>
      <div id="portalSearchResults" class="pt-3 text-xs text-slate-500 space-y-1">
        <span class="block font-bold text-slate-700">{{ ($contentBlocks['copy.blade_footer']['text_106'] ?? 'Pintasan layanan') }}</span>
        <div class="flex flex-wrap gap-1.5 pt-1">
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_124'] ?? '/layanan/ptsp') }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">{{ ($contentBlocks['copy.blade_footer']['text_107'] ?? 'PTSP Terpadu') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_141'] ?? '/layanan/legalisir') }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">{{ ($contentBlocks['copy.blade_footer']['text_108'] ?? 'Legalisir Ijazah') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_148'] ?? '/layanan/mutasi') }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">{{ ($contentBlocks['copy.blade_footer']['text_109'] ?? 'Mutasi Siswa') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_149'] ?? '/layanan/izin-siswa') }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">{{ ($contentBlocks['copy.blade_footer']['text_110'] ?? 'Surat Izin Siswa') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_150'] ?? '/piket/izin-keluar') }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">{{ ($contentBlocks['copy.blade_footer']['text_111'] ?? 'Izin Keluar-Masuk') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_151'] ?? '/pos-jaga') }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">{{ ($contentBlocks['copy.blade_footer']['text_112'] ?? 'Scan Pos Satpam') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_129'] ?? '/lacak') }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">{{ ($contentBlocks['copy.blade_footer']['text_113'] ?? 'Lacak Berkas') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_147'] ?? '/perpustakaan') }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">{{ ($contentBlocks['copy.blade_footer']['text_114'] ?? 'Perpustakaan Digital') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_172'] ?? '/alumni') }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">{{ ($contentBlocks['copy.blade_footer']['text_115'] ?? 'Ikatan Alumni') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_142'] ?? '/layanan/kelulusan') }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">{{ ($contentBlocks['copy.blade_footer']['text_116'] ?? 'Cek Kelulusan') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_173'] ?? '/verifikasi') }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">{{ ($contentBlocks['copy.blade_footer']['text_117'] ?? 'Verifikasi Dokumen') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_174'] ?? '/layanan/proposal') }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">{{ ($contentBlocks['copy.blade_footer']['text_118'] ?? 'Proposal & LPJ') }}</a>
          <a href="{{ ($contentBlocks['copy.blade_footer']['href_146'] ?? '/login') }}" class="px-2 py-1 rounded bg-emerald-100 text-kemenag-800 font-bold hover:bg-emerald-200">{{ ($contentBlocks['copy.blade_footer']['text_119'] ?? 'Login SIAKAD') }}</a>
                    <a href="{{ ($contentBlocks['copy.blade_footer']['href_146'] ?? '/login') }}" class="px-2 py-1 rounded bg-indigo-100 text-indigo-800 font-bold hover:bg-indigo-200"><i class="fa-solid fa-shield mr-1"></i>{{ ($contentBlocks['copy.blade_footer']['text_120'] ?? 'Super Admin') }}</a>
        </div>
        <p id="portalSearchEmpty" hidden>{{ ($contentBlocks['copy.blade_footer']['text_121'] ?? 'Tidak ada layanan yang cocok.') }}</p>
      </div>
    </div>
  </div>
@unless (request()->is('data-siswa', 'alumni', 'layanan/izin-siswa'))
  <a class="portal-help" href="{{ ($contentBlocks['copy.blade_footer']['href_130'] ?? '/kontak') }}" aria-label="{{ ($contentBlocks['copy.blade_footer']['aria_label_175'] ?? 'Hubungi layanan madrasah') }}" title="{{ ($contentBlocks['copy.blade_footer']['title_176'] ?? 'Live Chat & Bantuan') }}">
    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#ffffff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M14 9a2 2 0 0 1-2 2H6l-4 4V4c0-1.1.9-2 2-2h8a2 2 0 0 1 2 2v5Z"/>
      <path d="M18 9h2a2 2 0 0 1 2 2v11l-4-4h-6a2 2 0 0 1-2-2v-1"/>
    </svg>
  </a>
@endunless
