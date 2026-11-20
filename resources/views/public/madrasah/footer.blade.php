  <!-- Footer -->
@if (request()->is('profil-sekolah', 'direktori-gtk'))
  <footer class="bg-kemenag-950 text-white pt-8 pb-6 border-t border-kemenag-800">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="flex flex-col sm:flex-row items-center justify-between text-xs text-emerald-200 gap-3 font-medium">
        <p>© 2026 {{ $schoolName }}. Hak Cipta Dilindungi Undang-Undang.</p>
        <div class="flex items-center gap-4">
          @if (request()->is('direktori-gtk'))
            <a href="/profil-sekolah" class="hover:text-accent-yellow">Visi &amp; Misi</a>
            <a href="/#zona-integritas" class="hover:text-accent-yellow">Zona Integritas</a>
            <a href="/layanan/ptsp" class="hover:text-accent-yellow">PTSP Digital</a>
            <a href="/layanan/lapor" class="hover:text-accent-yellow">Pengaduan</a>
          @else
            <a href="/" class="hover:text-accent-yellow">Beranda</a>
            <a href="/direktori-gtk" class="hover:text-accent-yellow">Dewan Guru</a>
            <a href="/#zona-integritas" class="hover:text-accent-yellow">Zona Integritas</a>
            <a href="/layanan/ptsp" class="hover:text-accent-yellow">PTSP Digital</a>
          @endif
        </div>
      </div>
    </div>
  </footer>
@elseif (request()->is('layanan/ptsp'))
  <footer class="bg-kemenag-950 text-emerald-100 pt-6 pb-6 border-t border-kemenag-800 mt-auto">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 text-center text-xs space-y-2">
      <div class="flex flex-wrap items-center justify-center gap-3">
        <a href="/" class="font-bold text-accent-yellow hover:underline flex items-center gap-1"><i class="fa-solid fa-globe"></i> Website Utama Madrasah</a>
        <span class="text-emerald-600">•</span>
        <a href="/lacak" class="hover:text-accent-yellow flex items-center gap-1"><i class="fa-solid fa-magnifying-glass"></i> Tracking Tiket</a>
        <span class="text-emerald-600">•</span>
        <a href="/layanan/lapor" class="hover:text-accent-yellow flex items-center gap-1"><i class="fa-solid fa-shield-heart"></i> Siap Lapor</a>
      </div>
      <p>© 2026 <strong class="text-white">{{ $schoolName }}</strong> • Pelayanan Terpadu Satu Pintu (PTSP) Digital Terpadu.</p>
    </div>
  </footer>
@else
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
            Madrasah Aliyah Negeri unggulan yang mengedepankan pendidikan berbasis adiwiyata, karakter islami, dan riset sains teknologi modern.
          </p>
          <div class="flex items-center space-x-3 text-emerald-300 pt-1">
            <a href="/kontak" class="hover:text-accent-yellow transition"><i class="fa-brands fa-facebook"></i></a>
            <a href="/kontak" class="hover:text-accent-yellow transition"><i class="fa-brands fa-instagram"></i></a>
            <a href="/galeri/video" class="hover:text-accent-yellow transition" aria-label="Galeri video"><i class="fa-brands fa-youtube"></i></a>
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
        <p>Kementerian Agama Republik Indonesia.</p>
      </div>

    </div>
  </footer>
@endif

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
      <img id="lightboxImg" src="/madrasah-theme/images/campus.webp" alt="Dokumentasi" class="max-h-[80vh] mx-auto rounded-2xl shadow-2xl border-2 border-emerald-500/30">
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
          <img id="bmGambar" src="/madrasah-theme/images/campus.webp" alt="Thumbnail" class="w-full h-full object-cover">
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
