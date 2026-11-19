@extends('public.layouts.madrasah')

@php
  $schoolName = ($profile['name'] ?? null) ?: 'PORTAL MADRASAH';
  $selectedCategory = trim((string) request()->query('kategori', ''));

  $dummyGallery = [
      ['title' => 'Eksperimen Laboratorium Kimia & Riset Terapan', 'caption' => 'Pembinaan tim riset ilmiah siswa di laboratorium IPA.', 'category' => 'Riset & Sains', 'url' => '/madrasah-theme/images/lab.webp'],
      ['title' => 'Pembelajaran Aktif di Kelas Digital', 'caption' => 'Suasana belajar interaktif berbasis proyek.', 'category' => 'Riset & Akademik', 'url' => '/madrasah-theme/images/classroom.webp'],
      ['title' => 'Upacara & Pembiasaan Karakter Kebangsaan', 'caption' => 'Apel pagi dan penguatan karakter disiplin siswa.', 'category' => 'Upacara & Hari Besar', 'url' => '/madrasah-theme/images/ceremony.webp'],
      ['title' => 'Aksi Kader Adiwiyata & Madrasah Hijau', 'caption' => 'Gotong royong pelestarian lingkungan kampus madrasah.', 'category' => 'Adiwiyata', 'url' => '/madrasah-theme/images/volunteer.webp'],
      ['title' => 'Penganugerahan Juara Olimpiade Sains & Bahasa', 'caption' => 'Apresiasi prestasi peserta didik berprestasi.', 'category' => 'Prestasi Sains', 'url' => '/madrasah-theme/images/award.webp'],
      ['title' => 'Pojok Baca & Literasi E-Perpustakaan', 'caption' => 'Kegiatan literasi harian siswa di perpustakaan madrasah.', 'category' => 'Literasi', 'url' => '/madrasah-theme/images/library.webp'],
      ['title' => 'Ekstrakurikuler Robotik & Karya IoT', 'caption' => 'Pengembangan teknologi terapan oleh klub robotik siswa.', 'category' => 'Riset & Sains', 'url' => '/madrasah-theme/images/robotics.webp'],
      ['title' => 'Pekan Olahraga & Kebugaran Santri', 'caption' => 'Kompetisi olahraga antarkelas membangun sportivitas.', 'category' => 'Olahraga', 'url' => '/madrasah-theme/images/sports.webp'],
  ];

  $items = count($albums ?? []) > 0
      ? collect($albums)->map(fn ($album) => [
          'title' => $album['title'],
          'caption' => $album['caption'] ?? $album['title'],
          'category' => 'Dokumentasi',
          'url' => $album['photos'][0]['url'] ?? '/madrasah-theme/images/campus.webp',
      ])->all()
      : $dummyGallery;

  if ($selectedCategory !== '') {
      $items = array_values(array_filter($items, fn ($i) => mb_strtolower($i['category']) === mb_strtolower($selectedCategory)));
  }
  $categories = ['Riset & Sains', 'Riset & Akademik', 'Prestasi Sains', 'Adiwiyata', 'Upacara & Hari Besar', 'Literasi', 'Olahraga'];
@endphp

@section('title', 'Galeri & Dokumentasi Kegiatan | ' . $schoolName)

@section('content')
  <!-- ================= HERO BANNER ================= -->
  <section class="bg-gradient-to-br from-teal-950 via-kemenag-900 to-slate-950 text-white py-12 lg:py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 relative z-10">
      <div class="max-w-3xl">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-400/20 text-emerald-300 text-xs font-bold uppercase tracking-wider mb-3 border border-emerald-400/30">
          <i class="fa-solid fa-camera"></i> Dokumentasi &amp; Memori
        </div>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white mb-3">
          Galeri &amp; Kegiatan Madrasah
        </h1>
        <p class="text-sm sm:text-base text-emerald-100 leading-relaxed">
          Kilas balik visual momen pembelajaran, halaqah religius, upacara kemerdekaan, pembinaan riset sains, dan kreasi santri {{ $schoolName }}.
        </p>
      </div>
    </div>
  </section>

  <!-- ================= KONTEN UTAMA ================= -->
  <main id="main-content" class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-10 flex-1 w-full">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-8 pb-4 border-b border-slate-200">
      <div class="flex items-center gap-2 overflow-x-auto pb-1 max-w-full">
        <a href="/galeri" class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $selectedCategory === '' ? 'bg-kemenag-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
          Semua Album Foto ({{ count($items) }})
        </a>
        @foreach ($categories as $cat)
          <a href="/galeri?kategori={{ urlencode($cat) }}" class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $selectedCategory === $cat ? 'bg-kemenag-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            {{ $cat }}
          </a>
        @endforeach
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
      @forelse ($items as $album)
        <button
          type="button"
          class="group relative rounded-2xl overflow-hidden shadow-sm h-56 cursor-pointer text-left border border-slate-200"
          data-open-dialog="lightboxModal"
          aria-haspopup="dialog"
          data-image="{{ $album['url'] }}"
          data-caption="{{ $album['caption'] }}"
        >
          <img src="{{ $album['url'] }}" alt="{{ $album['title'] }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
          <span class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-900/20 to-transparent flex flex-col justify-end p-4">
            <span class="text-[10px] font-bold text-accent-yellow uppercase tracking-wider">{{ $album['category'] }}</span>
            <span class="text-white text-sm font-bold leading-snug mt-0.5 line-clamp-2">{{ $album['title'] }}</span>
          </span>
        </button>
      @empty
        <div class="col-span-full rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
          Belum ada dokumentasi foto pada kategori yang dipilih.
        </div>
      @endforelse
    </div>
  </main>
@endsection
