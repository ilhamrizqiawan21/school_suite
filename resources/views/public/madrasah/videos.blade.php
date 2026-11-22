@extends('public.layouts.madrasah')

@php
  $schoolName = ($profile['name'] ?? null) ?: 'Sekolah';
  $q = trim((string) request()->query('q', ''));
  $selectedCategory = trim((string) request()->query('kategori', ''));

  $defaultVideos = [
      [
          'id' => 'tur-kampus',
          'title' => 'Tur Kampus Madrasah & Lingkungan Belajar Terpadu',
          'category' => 'Profil',
          'category_label' => 'Profil Madrasah',
          'date' => '11 Oct 2026',
          'description' => 'Klip ilustrasi suasana kampus, sarana pembelajaran, laboratorium, dan ruang terbuka hijau ' . $schoolName . '.',
          'poster' => '/madrasah-theme/images/videos/tur-kampus.webp',
          'src' => '/madrasah-theme/images/videos/tur-kampus.webm',
      ],
      [
          'id' => 'praktikum-sains',
          'title' => 'Praktikum Sains & Pembinaan Riset Ilmiah Siswa',
          'category' => 'Prestasi',
          'category_label' => 'Prestasi & Riset Sains',
          'date' => '06 Oct 2026',
          'description' => 'Kegiatan eksperimen laboratorium kimia dan fisika terapan oleh tim peneliti muda madrasah.',
          'poster' => '/madrasah-theme/images/videos/praktikum-sains.webp',
          'src' => '/madrasah-theme/images/videos/praktikum-sains.webm',
      ],
      [
          'id' => 'kegiatan-literasi',
          'title' => 'Kegiatan Literasi & Halaqah Keagamaan Santri',
          'category' => 'Keagamaan',
          'category_label' => 'Halaqah & Keagamaan',
          'date' => '28 Sep 2026',
          'description' => 'Pembiasaan tadarus pagi, kajian kitab, serta pojok literasi di E-Perpustakaan Digital.',
          'poster' => '/madrasah-theme/images/videos/kegiatan-literasi.webp',
          'src' => '/madrasah-theme/images/videos/kegiatan-literasi.webm',
      ],
      [
          'id' => 'karya-siswa',
          'title' => 'Pameran Karya Siswa & Inovasi Teknologi Terapan',
          'category' => 'Prestasi',
          'category_label' => 'Prestasi & Riset Sains',
          'date' => '19 Sep 2026',
          'description' => 'Gelar karya proyek kreatif, robotik, dan inovasi sains siswa lintas peminatan.',
          'poster' => '/madrasah-theme/images/videos/karya-siswa.webp',
          'src' => '/madrasah-theme/images/videos/karya-siswa.webm',
      ],
      [
          'id' => 'madrasah-hijau',
          'title' => 'Program Madrasah Hijau & Gerakan Adiwiyata Mandiri',
          'category' => 'Kegiatan',
          'category_label' => 'Kegiatan Santri & Siswa',
          'date' => '10 Sep 2026',
          'description' => 'Aksi pelestarian lingkungan, bank sampah organik, dan perawatan taman belajar asri.',
          'poster' => '/madrasah-theme/images/videos/madrasah-hijau.webp',
          'src' => '/madrasah-theme/images/videos/madrasah-hijau.webm',
      ],
      [
          'id' => 'orientasi-siswa',
          'title' => 'Orientasi Siswa Baru & Pengenalan Budaya Madrasah',
          'category' => 'Kegiatan',
          'category_label' => 'Kegiatan Santri & Siswa',
          'date' => '02 Sep 2026',
          'description' => 'Penyambutan peserta didik baru dan pengenalan lima nilai budaya kerja madrasah.',
          'poster' => '/madrasah-theme/images/videos/orientasi-siswa.webp',
          'src' => '/madrasah-theme/images/videos/orientasi-siswa.webm',
      ],
  ];

  $allVideos = isset($videos) && is_array($videos) ? $videos : $defaultVideos;

  $categoryPills = [
      'Profil' => '🏛️ Profil Madrasah',
      'Kegiatan' => '🎒 Kegiatan Santri & Siswa',
      'Prestasi' => '🏆 Prestasi & Riset Sains',
      'Keagamaan' => '🕌 Halaqah & Keagamaan',
  ];

  $filteredVideos = $allVideos;
  if ($selectedCategory !== '') {
      $filteredVideos = array_values(array_filter(
          $filteredVideos,
          fn (array $v): bool => mb_strtolower($v['category']) === mb_strtolower($selectedCategory),
      ));
  }
  if ($q !== '') {
      $filteredVideos = array_values(array_filter(
          $filteredVideos,
          fn (array $v): bool => str_contains(mb_strtolower($v['title'] . ' ' . $v['description']), mb_strtolower($q)),
      ));
  }

  $featuredCandidate = collect($filteredVideos)->firstWhere('is_featured', true);
  $featured = $featuredCandidate ?? $filteredVideos[0] ?? $allVideos[0] ?? null;
@endphp

@section('title', 'Kanal Video & Dokumentasi Multimedia - ' . $schoolName)

@section('content')
  <!-- ================= HERO BANNER ================= -->
  <section class="bg-gradient-to-br from-slate-950 via-kemenag-950 to-slate-900 text-white py-12 lg:py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 relative z-10">
      <div class="max-w-3xl">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/20 text-red-300 text-xs font-bold uppercase tracking-wider mb-3 border border-red-500/30">
          <i class="fa-solid fa-video text-red-400" aria-hidden="true"></i> {{ ($contentBlocks['copy.blade_videos']['text_001'] ?? 'Kanal Multimedia & Video Resmi') }}
        </div>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white mb-3">
          {{ ($contentBlocks['copy.blade_videos']['text_002'] ?? 'Galeri Video & Dokumentasi') }}
        </h1>
        <p class="text-sm sm:text-base text-slate-300 leading-relaxed mb-6">
          Saksikan tayangan profil madrasah, liputan kegiatan santri, karya riset sains, kejuaraan, serta pembelajaran digital {{ $schoolName }}.
        </p>

        <!-- Search Bar -->
        <form method="GET" action="/galeri/video" class="flex flex-col sm:flex-row gap-2 max-w-xl">
          @if ($selectedCategory !== '')
            <input type="hidden" name="kategori" value="{{ $selectedCategory }}">
          @endif
          <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
            <input
              type="search"
              name="q"
              value="{{ $q }}"
              aria-label="{{ ($contentBlocks['copy.blade_videos']['aria_label_015'] ?? 'Cari judul video atau topik kegiatan') }}"
              placeholder="{{ ($contentBlocks['copy.blade_videos']['placeholder_016'] ?? 'Cari judul video atau topik kegiatan...') }}"
              class="w-full pl-11 pr-4 py-3 rounded-2xl bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-400 shadow-lg text-sm"
            >
          </div>
          <button type="submit" class="px-6 py-3 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-2xl transition shadow-lg flex items-center justify-center gap-2 text-sm">
            <i class="fa-solid fa-play text-xs" aria-hidden="true"></i>
            <span>{{ ($contentBlocks['copy.blade_videos']['text_003'] ?? 'Cari Video') }}</span>
          </button>
        </form>
      </div>
    </div>
  </section>

  <!-- ================= FILTER PILLS ================= -->
  <div class="bg-white border-b border-slate-200 shadow-sm">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-3">
      <div class="madrasah-catalog-filters text-xs font-bold">
        <span class="text-slate-500 font-semibold mr-1 flex items-center gap-1">
          <i class="fa-solid fa-filter" aria-hidden="true"></i> {{ ($contentBlocks['copy.blade_videos']['text_004'] ?? 'Kategori:') }}
        </span>
        <a
          href="/galeri/video{{ $q !== '' ? '?q=' . urlencode($q) : '' }}"
          class="px-4 py-2 rounded-xl transition {{ $selectedCategory === '' ? 'bg-kemenag-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
        >
          Semua Video ({{ count($allVideos) }})
        </a>
        @foreach ($categoryPills as $catKey => $catLabel)
          <a
            href="/galeri/video?kategori={{ urlencode($catKey) }}{{ $q !== '' ? '&q=' . urlencode($q) : '' }}"
            class="px-4 py-2 rounded-xl transition {{ mb_strtolower($selectedCategory) === mb_strtolower($catKey) ? 'bg-kemenag-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
          >
            {{ $catLabel }}
          </a>
        @endforeach
      </div>
    </div>
  </div>

  <!-- ================= KONTEN UTAMA VIDEO ================= -->
  <main id="main-content" class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-10 flex-1 w-full">

    <!-- FEATURED VIDEO SPOTLIGHT -->
    @if ($featured)
    <article class="mb-12 bg-slate-900 rounded-3xl overflow-hidden shadow-2xl border border-slate-700 text-white">
      <div class="grid lg:grid-cols-12 gap-0 items-center">
        <!-- Frame Video Player -->
        <div class="lg:col-span-7 bg-black relative">
          <video
            class="madrasah-video-thumbnail"
            controls
            playsinline
            preload="none"
            poster="{{ $featured['poster'] }}"
            aria-label="Video contoh: {{ $featured['title'] }}"
          >
            <source src="{{ $featured['src'] }}" type="video/webm">
          </video>
        </div>

        <!-- Detail Video Unggulan -->
        <div class="lg:col-span-5 p-6 sm:p-8 flex flex-col justify-between">
          <div>
            <div class="flex flex-wrap items-center gap-2 mb-3">
              <span class="px-3 py-1 bg-rose-600 text-white font-extrabold text-[10px] rounded-full uppercase tracking-wider">
                <i class="fa-solid fa-star mr-1" aria-hidden="true"></i> {{ ($contentBlocks['copy.blade_videos']['text_005'] ?? 'Video Contoh') }}
              </span>
              <span class="px-3 py-1 bg-white/10 text-emerald-200 font-bold text-[10px] rounded-full uppercase">
                {{ $featured['category_label'] }}
              </span>
            </div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-white leading-snug mb-3">
              {{ $featured['title'] }}
            </h2>
            <p class="text-xs sm:text-sm text-slate-200 leading-relaxed mb-6">
              {{ $featured['description'] }}
            </p>
          </div>

          <div class="pt-4 border-t border-white/10 flex items-center justify-between text-xs text-slate-300">
            <span><i class="fa-regular fa-calendar mr-1" aria-hidden="true"></i> Rilis: {{ $featured['date'] }}</span>
            <button
              type="button"
              data-open-dialog="videoPlayerModal"
              data-video-src="{{ $featured['src'] }}"
              data-video-poster="{{ $featured['poster'] }}"
              data-video-title="{{ $featured['title'] }}"
              aria-haspopup="dialog"
              class="text-amber-300 hover:text-amber-200 font-bold flex items-center gap-1.5"
            >
              <span>{{ ($contentBlocks['copy.blade_videos']['text_006'] ?? 'Putar di Layar Penuh') }}</span> <i class="fa-solid fa-circle-play" aria-hidden="true"></i>
            </button>
          </div>
        </div>
      </div>
    </article>
    @endif

    <!-- JUDUL DAFTAR VIDEO -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-8">
      <div>
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
          <i class="fa-solid fa-film text-kemenag-600" aria-hidden="true"></i>
          <span>{{ ($contentBlocks['copy.blade_videos']['text_007'] ?? 'Koleksi Video & Dokumentasi') }}</span>
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
          Menampilkan {{ count($filteredVideos) }} dari total {{ count($allVideos) }} video dokumentasi madrasah.
        </p>
      </div>
      @if ($q !== '' || $selectedCategory !== '')
        <a href="{{ ($contentBlocks['copy.blade_videos']['href_017'] ?? '/galeri/video') }}" class="text-xs font-bold text-kemenag-700 hover:underline">{{ ($contentBlocks['copy.blade_videos']['text_008'] ?? 'Reset Filter Video') }}</a>
      @endif
    </div>

    <!-- GRID VIDEO -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
      @forelse ($filteredVideos as $video)
        <article class="group bg-white rounded-2xl overflow-hidden border border-slate-200 hover:border-emerald-500 shadow-sm hover:shadow-xl transition duration-300 flex flex-col justify-between">
          <div>
            <div class="relative overflow-hidden bg-slate-900">
              <video
                class="madrasah-video-thumbnail"
                controls
                playsinline
                preload="none"
                poster="{{ $video['poster'] }}"
                aria-label="Video contoh: {{ $video['title'] }}"
              >
                <source src="{{ $video['src'] }}" type="video/webm">
              </video>
              <span class="absolute top-3 left-3 bg-slate-900/80 backdrop-blur-md text-white font-extrabold text-[10px] px-2.5 py-1 rounded-lg uppercase tracking-wider pointer-events-none">
                {{ $video['category'] }}
              </span>
            </div>

            <div class="p-5">
              <h3 class="font-extrabold text-slate-900 text-base line-clamp-2 leading-snug group-hover:text-kemenag-700 transition">
                {{ $video['title'] }}
              </h3>
              <p class="text-slate-500 text-xs line-clamp-2 leading-relaxed mt-2">
                {{ $video['description'] }}
              </p>
            </div>
          </div>

          <div class="px-5 pb-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span><i class="fa-regular fa-clock mr-1" aria-hidden="true"></i> {{ $video['date'] }}</span>
            <button
              type="button"
              data-open-dialog="videoPlayerModal"
              data-video-src="{{ $video['src'] }}"
              data-video-poster="{{ $video['poster'] }}"
              data-video-title="{{ $video['title'] }}"
              aria-haspopup="dialog"
              class="font-extrabold text-kemenag-700 hover:text-kemenag-800 flex items-center gap-1"
            >
              <span>{{ ($contentBlocks['copy.blade_videos']['text_009'] ?? 'Putar Video') }}</span> <i class="fa-solid fa-circle-play text-rose-600" aria-hidden="true"></i>
            </button>
          </div>
        </article>
      @empty
        <div class="col-span-full rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
          {{ ($contentBlocks['copy.blade_videos']['text_010'] ?? 'Tidak ditemukan video dokumentasi yang sesuai dengan kata kunci pencarian Anda.') }}
        </div>
      @endforelse
    </div>

    <!-- BANNER KANAL MULTIMEDIA MADRASAH -->
    <div class="mt-12 bg-gradient-to-r from-kemenag-900 via-kemenag-800 to-kemenag-950 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-emerald-500/30 flex flex-col md:flex-row items-center justify-between gap-6">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center text-2xl text-accent-yellow shrink-0 border border-white/10">
          <i class="fa-solid fa-photo-film" aria-hidden="true"></i>
        </div>
        <div>
          <h3 class="text-lg sm:text-xl font-extrabold">{{ ($contentBlocks['copy.blade_videos']['text_011'] ?? 'Jelajahi Dokumentasi & Publikasi Resmi Madrasah') }}</h3>
          <p class="text-xs sm:text-sm text-emerald-100 mt-1 max-w-xl">
            Dapatkan pembaruan liputan kegiatan, galeri foto kegiatan, warta berita, dan rekam jejak prestasi siswa {{ $schoolName }}.
          </p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-3 shrink-0">
        <a href="{{ ($contentBlocks['copy.blade_videos']['href_018'] ?? '/galeri') }}" class="px-5 py-3 bg-white hover:bg-slate-100 text-kemenag-800 font-extrabold text-xs sm:text-sm rounded-2xl shadow-lg transition flex items-center gap-2">
          <i class="fa-solid fa-images" aria-hidden="true"></i>
          <span>{{ ($contentBlocks['copy.blade_videos']['text_012'] ?? 'Galeri Foto') }}</span>
        </a>
        <a href="{{ ($contentBlocks['copy.blade_videos']['href_019'] ?? '/berita') }}" class="px-5 py-3 bg-accent-gold hover:bg-amber-400 text-slate-950 font-extrabold text-xs sm:text-sm rounded-2xl shadow-lg transition flex items-center gap-2">
          <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
          <span>{{ ($contentBlocks['copy.blade_videos']['text_013'] ?? 'Warta Madrasah') }}</span>
        </a>
      </div>
    </div>
  </main>

  <!-- MODAL PEMUTAR VIDEO LOKAL -->
  <div id="videoPlayerModal" role="dialog" aria-modal="true" aria-label="{{ ($contentBlocks['copy.blade_videos']['aria_label_020'] ?? 'Pemutar video') }}" tabindex="-1" class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md hidden flex items-center justify-center p-4">
    <div class="relative w-full max-w-4xl bg-slate-900 rounded-3xl overflow-hidden shadow-2xl border border-slate-700">
      <div class="px-6 py-4 bg-slate-900 border-b border-slate-700 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 min-w-0">
          <i class="fa-solid fa-circle-play text-accent-yellow text-lg" aria-hidden="true"></i>
          <h3 id="videoModalTitle" class="text-white font-extrabold text-sm sm:text-base truncate">{{ ($contentBlocks['copy.blade_videos']['text_014'] ?? 'Pemutar Video') }}</h3>
        </div>
        <button type="button" data-close-dialog aria-label="{{ ($contentBlocks['copy.blade_videos']['aria_label_021'] ?? 'Tutup dialog') }}" class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 hover:bg-white flex items-center justify-center transition">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
      </div>
      <div class="bg-black">
        <video id="videoModalPlayer" class="madrasah-video-thumbnail" controls playsinline preload="none">
          <source id="videoModalSource" src="{{ ($contentBlocks['copy.blade_videos']['src_022'] ?? '/madrasah-theme/images/videos/tur-kampus.webm') }}" type="video/webm">
        </video>
      </div>
    </div>
  </div>
@endsection
