@extends('public.layouts.madrasah')

@php
  $schoolName = ($profile['name'] ?? null) ?: 'PORTAL MADRASAH';
  $q = trim((string) request()->query('q', ''));
  $selectedLevel = trim((string) request()->query('tingkat', ''));

  $dummyAchievements = [
      ['rank' => 'Medali Emas', 'year' => '2026', 'title' => 'Olimpiade Bahasa Arab (OBA) Tingkat Nasional', 'level' => 'Nasional', 'winner' => 'Tim Bahasa Arab Madrasah', 'image' => '/madrasah-theme/images/award.webp'],
      ['rank' => 'Juara 1', 'year' => '2026', 'title' => 'Kompetisi Sains Madrasah (KSM) Kimia Terintegrasi', 'level' => 'Provinsi', 'winner' => 'Tim Riset Sains', 'image' => '/madrasah-theme/images/lab.webp'],
      ['rank' => 'Finalis Nasional', 'year' => '2026', 'title' => 'MYRES Ilmu Keagamaan & Sains Terapan', 'level' => 'Nasional Kemenag RI', 'winner' => 'Tim Peneliti Muda', 'image' => '/madrasah-theme/images/robotics.webp'],
      ['rank' => 'Juara 1', 'year' => '2026', 'title' => 'Musabaqah Hifdzil Qur\'an 5 Juz & Tilawah', 'level' => 'Kabupaten / Kota', 'winner' => 'Tim Tahfidz Madrasah', 'image' => '/madrasah-theme/images/ceremony.webp'],
      ['rank' => 'Juara 2', 'year' => '2026', 'title' => 'Turnamen Futsal Pelajar Antar Madrasah', 'level' => 'Provinsi', 'winner' => 'Tim Olahraga Madrasah', 'image' => '/madrasah-theme/images/sports.webp'],
  ];

  $list = count($items ?? []) > 0
      ? collect($items)->map(fn ($item) => [
          'rank' => strtoupper((string) ($item['category'] ?? 'Prestasi')),
          'year' => \Illuminate\Support\Carbon::parse($item['published_at'] ?? now())->format('Y'),
          'title' => $item['title'],
          'level' => 'Prestasi Madrasah',
          'winner' => $item['excerpt'] ?: 'Siswa Berprestasi',
          'image' => '/madrasah-theme/images/award.webp',
      ])->all()
      : $dummyAchievements;

  if ($selectedLevel !== '') {
      $list = array_values(array_filter($list, fn ($i) => mb_strtolower($i['level']) === mb_strtolower($selectedLevel)));
  }
  if ($q !== '') {
      $list = array_values(array_filter($list, fn ($i) => str_contains(mb_strtolower($i['title'].' '.$i['winner']), mb_strtolower($q))));
  }
  $levels = ['Nasional', 'Nasional Kemenag RI', 'Provinsi', 'Kabupaten / Kota'];
@endphp

@section('title', 'Rekam Jejak Prestasi & Tradisi Juara | ' . $schoolName)

@section('content')
  <!-- ================= HERO BANNER ================= -->
  <section class="bg-gradient-to-br from-slate-900 via-kemenag-900 to-slate-950 text-white py-12 lg:py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 relative z-10">
      <div class="max-w-3xl">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/20 text-accent-yellow text-xs font-bold uppercase tracking-wider mb-3 border border-amber-400/30">
          <i class="fa-solid fa-trophy"></i> Hall of Fame Siswa
        </div>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white mb-3">
          Prestasi &amp; Tradisi Juara
        </h1>
        <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
          Koleksi piala, medali, dan penghargaan yang diraih santri dan siswa {{ $schoolName }} dalam ajang riset sains, keagamaan, seni, dan olahraga.
        </p>

        <form action="/prestasi" method="GET" class="mt-6 flex flex-col sm:flex-row gap-2 max-w-2xl">
          <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama lomba atau tim peraih..." class="w-full pl-11 pr-4 py-3 rounded-xl bg-white text-slate-900 text-sm shadow-lg focus:outline-none focus:ring-2 focus:ring-emerald-400">
          </div>
          <button type="submit" class="bg-accent-gold hover:bg-amber-400 text-slate-950 font-extrabold px-6 py-3 rounded-xl shadow-lg transition text-sm">
            Cari Prestasi
          </button>
        </form>
      </div>
    </div>
  </section>

  <!-- ================= KONTEN UTAMA ================= -->
  <main id="main-content" class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-10 flex-1 w-full">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-8 pb-4 border-b border-slate-200">
      <div class="flex items-center gap-2 overflow-x-auto pb-1 max-w-full">
        <a href="/prestasi" class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $selectedLevel === '' ? 'bg-kemenag-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
          Semua Tingkat ({{ count($list) }})
        </a>
        @foreach ($levels as $lvl)
          <a href="/prestasi?tingkat={{ urlencode($lvl) }}" class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $selectedLevel === $lvl ? 'bg-kemenag-700 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            {{ $lvl }}
          </a>
        @endforeach
      </div>
      <div class="text-xs text-slate-500">
        Total <b>{{ count($list) }}</b> rekam jejak juara
      </div>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      @forelse ($list as $item)
        <div class="bg-white rounded-3xl p-6 border border-slate-200 hover:border-amber-400 hover:shadow-xl transition flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between mb-4">
              <span class="bg-amber-100 text-amber-900 font-extrabold text-[11px] px-3 py-1 rounded-full flex items-center gap-1.5 shadow-sm">
                <i class="fa-solid fa-medal text-amber-500"></i> {{ $item['rank'] }}
              </span>
              <span class="text-xs font-bold text-slate-400">{{ $item['year'] }}</span>
            </div>
            <div class="h-36 rounded-2xl overflow-hidden mb-4 border border-slate-200/60 bg-slate-100">
              <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
            </div>
            <h2 class="font-bold text-slate-900 text-base leading-snug">{{ $item['title'] }}</h2>
            <p class="text-xs text-kemenag-700 font-semibold mt-1.5">{{ $item['level'] }}</p>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-600">
            Peraih: <strong class="text-slate-800">{{ $item['winner'] }}</strong>
          </div>
        </div>
      @empty
        <div class="col-span-full rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
          Belum ada catatan prestasi pada filter yang dipilih.
        </div>
      @endforelse
    </div>
  </main>
@endsection
