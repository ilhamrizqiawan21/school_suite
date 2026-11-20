@extends('public.layouts.madrasah')

@php
  $schoolName = ($profile['name'] ?? null) ?: 'Madrasah Contoh Nusantara';
  $q = trim((string) request()->query('q', ''));
  $selectedCategory = trim((string) request()->query('kategori', ''));

  $dummyNews = [
      [
          'title' => 'Tim Bahasa Arab Madrasah Raih Medali Emas Olimpiade Nasional',
          'category' => 'Keagamaan & Tahfidz',
          'date' => '2026-11-13',
          'author' => 'Humas Madrasah',
          'image' => '/madrasah-theme/images/award.webp',
          'excerpt' => 'Delegasi siswa berhasil meraih prestasi terbaik pada ajang kompetisi bahasa Arab tingkat nasional.',
          'body' => 'Alhamdulillah, keluarga besar ' . $schoolName . ' bersyukur atas capaian delegasi siswa yang berhasil mempersembahkan medali emas tingkat nasional melalui pembinaan intensif bersama dewan pembimbing madrasah.',
          'slug' => null,
      ],
      [
          'title' => 'Aksi Bersih Lingkungan & Bank Sampah Santri Wujudkan Madrasah Zero-Waste',
          'category' => 'Adiwiyata & Lingkungan',
          'date' => '2026-10-18',
          'author' => 'Tim Adiwiyata',
          'image' => '/madrasah-theme/images/volunteer.webp',
          'excerpt' => 'Komitmen menjaga kelestarian ekosistem sekolah melalui pemilahan sampah organik dan kompos madrasah.',
          'body' => 'Kader Adiwiyata bersama dewan guru dan OSIM melaksanakan program pemilahan sampah harian serta pengolahan kompos organik untuk menjaga lingkungan belajar yang asri.',
          'slug' => null,
      ],
      [
          'title' => 'Khatmil Qur\'an & Uji Publik Tahfidz Juz 30 Siswa Kelas X dan XI',
          'category' => 'Keagamaan & Tahfidz',
          'date' => '2026-09-25',
          'author' => 'Pembina Tahfidz',
          'image' => '/madrasah-theme/images/campus.webp',
          'excerpt' => 'Sebanyak 64 siswa berhasil menuntaskan tasmi\' bil-ghaib di hadapan dewan asatidz dan orang tua.',
          'body' => 'Kegiatan uji publik tahfidz berlangsung khidmat di masjid madrasah. Para peserta memperdengarkan hafalan secara langsung di hadapan tim penguji dan wali murid.',
          'slug' => null,
      ],
      [
          'title' => 'Tim Riset Madrasah Lolos Finalis Karya Ilmiah Sains Terapan',
          'category' => 'Prestasi Sains',
          'date' => '2026-09-14',
          'author' => 'Koordinator Riset',
          'image' => '/madrasah-theme/images/robotics.webp',
          'excerpt' => 'Inovasi bio-komposit ramah lingkungan mengantarkan tim riset melaju ke babak penjurian nasional.',
          'body' => 'Tim riset siswa mempresentasikan penelitian terapan pemanfaatan material ramah lingkungan sebagai solusi berkelanjutan yang dinilai layak oleh dewan juri.',
          'slug' => null,
      ],
  ];

  $articles = count($news ?? []) > 0
      ? collect($news)->map(fn ($item) => [
          'title' => $item['title'],
          'category' => $item['category'] ?? 'Warta Madrasah',
          'date' => \Illuminate\Support\Carbon::parse($item['published_at'] ?? now())->toDateString(),
          'author' => $item['author'] ?? 'Humas Madrasah',
          'image' => $item['photos'][0]['url'] ?? '/madrasah-theme/images/campus.webp',
          'excerpt' => $item['excerpt'] ?? '',
          'body' => $item['excerpt'] ?? '',
          'slug' => $item['slug'] ?? null,
      ])->all()
      : $dummyNews;

  $categories = array_values(array_unique(array_column($articles, 'category')));

  if ($selectedCategory !== '') {
      $articles = array_values(array_filter($articles, fn ($a) => mb_strtolower((string) $a['category']) === mb_strtolower($selectedCategory)));
  }
  if ($q !== '') {
      $articles = array_values(array_filter($articles, fn ($a) => str_contains(mb_strtolower($a['title'].' '.$a['excerpt']), mb_strtolower($q))));
  }
@endphp

@section('title', 'Warta & Berita Resmi | ' . $schoolName)

@section('content')
  <!-- ================= HERO HEADER ================= -->
  <section class="bg-gradient-to-br from-kemenag-900 via-kemenag-800 to-kemenag-950 text-white py-12 lg:py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 relative z-10">
      <div class="max-w-3xl">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-emerald-300 text-xs font-bold uppercase tracking-wider mb-3">
          <i class="fa-solid fa-newspaper text-accent-yellow"></i> Pusat Informasi &amp; Publikasi
        </div>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white mb-3">
          Warta &amp; Kabar Madrasah
        </h1>
        <p class="text-sm sm:text-base text-emerald-100 leading-relaxed">
          Temukan liputan agenda sekolah, prestasi ilmiah, program adiwiyata, serta informasi terkini seputar civitas akademika {{ $schoolName }}.
        </p>

        <form action="/berita" method="GET" class="mt-6 flex flex-col sm:flex-row gap-2 max-w-xl">
          @if ($selectedCategory !== '')
            <input type="hidden" name="kategori" value="{{ $selectedCategory }}">
          @endif
          <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="search" name="q" aria-label="Cari berita" value="{{ $q }}" placeholder="Cari judul berita atau agenda..." class="w-full pl-11 pr-4 py-3 rounded-xl bg-white text-slate-900 text-sm shadow-lg focus:outline-none focus:ring-2 focus:ring-emerald-400">
          </div>
          <button type="submit" class="bg-accent-gold hover:bg-amber-400 text-slate-950 font-extrabold px-6 py-3 rounded-xl shadow-lg transition text-sm">
            Cari Warta
          </button>
        </form>
      </div>
    </div>
  </section>

  <!-- ================= LISTING KATALOG BERITA & FILTER ================= -->
  <main id="main-content" class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-10 flex-1 w-full">
    @if (empty($news))
      <p class="mb-5 text-sm font-semibold text-slate-600">Konten contoh untuk pratinjau portal.</p>
    @endif

    <div class="flex items-center justify-between flex-wrap gap-4 mb-8 pb-4 border-b border-slate-200">
      <div class="madrasah-catalog-filters">
        <a href="/berita" class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $selectedCategory === '' ? 'bg-kemenag-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
          Semua Kategori ({{ count($articles) }})
        </a>
        @foreach ($categories as $cat)
          <a href="/berita?kategori={{ urlencode($cat) }}&amp;q={{ urlencode($q) }}" class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $selectedCategory === $cat ? 'bg-kemenag-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            {{ $cat }}
          </a>
        @endforeach
      </div>
      <div class="text-xs text-slate-500">
        Menampilkan <b>{{ count($articles) }}</b> warta berita
      </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
      @forelse ($articles as $article)
        <article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-200 hover:border-kemenag-500 hover:shadow-md transition flex flex-col justify-between">
          <div>
            <div class="h-48 overflow-hidden relative">
              <img src="{{ $article['image'] }}" alt="{{ $article['title'] }}" class="w-full h-full object-cover hover:scale-105 transition duration-500">
              <span class="absolute top-3 left-3 bg-kemenag-700 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase">
                {{ $article['category'] }}
              </span>
            </div>
            <div class="p-5">
              <div class="flex items-center gap-2 text-[11px] text-slate-500 mb-1.5">
                <i class="fa-regular fa-calendar text-kemenag-600"></i>
                {{ \Illuminate\Support\Carbon::parse($article['date'])->locale('id')->translatedFormat('j M Y') }}
                <span class="text-slate-300">•</span>
                <span><i class="fa-solid fa-user-pen text-slate-400"></i> {{ $article['author'] }}</span>
              </div>
              <h2 class="font-bold text-slate-900 text-base hover:text-kemenag-700 transition leading-snug">
                {{ $article['title'] }}
              </h2>
              <p class="text-xs text-slate-600 mt-2 line-clamp-2">{{ $article['excerpt'] }}</p>
            </div>
          </div>
          <div class="px-5 pb-5 pt-0">
            @if ($article['slug'])
              <a href="{{ route('core.news.public.show', $article['slug']) }}" class="text-xs font-bold text-kemenag-700 hover:text-kemenag-800 flex items-center gap-1">
                Baca Selengkapnya <i class="fa-solid fa-angle-right text-[10px]"></i>
              </a>
            @else
              <button type="button" data-open-dialog="beritaModal" data-article="{{ json_encode($article) }}" class="text-xs font-bold text-kemenag-700 hover:text-kemenag-800 flex items-center gap-1">
                Baca Selengkapnya <i class="fa-solid fa-angle-right text-[10px]"></i>
              </button>
            @endif
          </div>
        </article>
      @empty
        <div class="col-span-full rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
          Belum ada berita yang cocok dengan filter pencarian Anda.
        </div>
      @endforelse
    </div>
  </main>
@endsection
