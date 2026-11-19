@extends('public.layouts.madrasah')

@php
  $schoolName = ($profile['name'] ?? null) ?: 'PORTAL MADRASAH';
  $dummyTeachers = [
      ['name' => 'Drs. H. Ahmad Hidayat, M.Pd.', 'position' => 'Kepala Madrasah', 'badge' => 'Pembina Tk. I / IV b', 'avatar' => '/madrasah-theme/images/portrait.webp'],
      ['name' => 'Siti Aminah, S.Pd., M.Pd.', 'position' => 'Wakil Kepala Bidang Kurikulum', 'badge' => 'Guru Ahli Madya / IV a', 'avatar' => '/madrasah-theme/images/classroom.webp'],
      ['name' => 'Muhammad Rizwan, S.Si.', 'position' => 'Pembina Riset & Kimia Terapan', 'badge' => 'Guru Muda / III d', 'avatar' => '/madrasah-theme/images/lab.webp'],
      ['name' => 'Nurul Hikmah, S.Pd.I.', 'position' => 'Guru Al-Qur\'an Hadits & Tahfidz', 'badge' => 'Guru Pertama / III b', 'avatar' => '/madrasah-theme/images/ceremony.webp'],
      ['name' => 'Fajar Nugroho, S.Kom.', 'position' => 'Koordinator CBT & Informatika', 'badge' => 'Pranata Komputer', 'avatar' => '/madrasah-theme/images/robotics.webp'],
      ['name' => 'Rahmawati, S.E.', 'position' => 'Kepala Tata Usaha & PTSP', 'badge' => 'Penata Muda Tk. I / III b', 'avatar' => '/madrasah-theme/images/reception.webp'],
      ['name' => 'Hendra Wijaya, S.Pd.', 'position' => 'Guru Matematika & Pembina KSM', 'badge' => 'Guru Muda / III c', 'avatar' => '/madrasah-theme/images/science.webp'],
      ['name' => 'Dewi Lestari, S.Psi.', 'position' => 'Guru Bimbingan Konseling (BK)', 'badge' => 'Guru Ahli Pertama', 'avatar' => '/madrasah-theme/images/library.webp'],
  ];

  $displayTeachers = count($teachers ?? []) > 0
      ? collect($teachers)->map(fn ($t) => [
          'name' => $t['name'] ?? 'Tenaga Pendidik',
          'position' => $t['position'] ?? ($t['subject_name'] ?? 'Guru Mata Pelajaran'),
          'badge' => $t['employment_status'] ?? 'GTK Madrasah',
          'avatar' => '/madrasah-theme/images/portrait.webp',
      ])->all()
      : $dummyTeachers;

  $q = trim((string) ($filters['q'] ?? ''));
  if ($q !== '' && count($teachers ?? []) === 0) {
      $displayTeachers = array_values(array_filter(
          $displayTeachers,
          fn ($t) => str_contains(mb_strtolower($t['name'].' '.$t['position']), mb_strtolower($q)),
      ));
  }
@endphp

@section('title', 'Direktori Guru & Tenaga Kependidikan | ' . $schoolName)

@section('content')
  <!-- ================= HERO BANNER ================= -->
  <section class="bg-gradient-to-br from-slate-900 via-kemenag-900 to-slate-950 text-white py-12 lg:py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 relative z-10">
      <div class="grid lg:grid-cols-12 gap-8 items-center">
        <div class="lg:col-span-8">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold uppercase tracking-wider mb-3 border border-emerald-400/30">
            <i class="fa-solid fa-chalkboard-user text-accent-yellow"></i> Pendidik Berintegritas
          </div>
          <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white mb-3 leading-tight">
            Direktori Guru &amp; Tenaga Kependidikan
          </h1>
          <p class="text-sm sm:text-base text-slate-300 leading-relaxed max-w-2xl">
            Mengenal lebih dekat tenaga pendidik dan kependidikan profesional {{ $schoolName }} yang berdedikasi membimbing santri berakhlak mulia, unggul riset sains, dan berprestasi nasional.
          </p>

          <form action="/direktori-gtk" method="GET" class="mt-6 flex flex-col sm:flex-row gap-2 max-w-2xl">
            <div class="relative flex-1">
              <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
              <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama guru atau bidang tugas..." class="w-full pl-11 pr-4 py-3 rounded-xl bg-white text-slate-900 text-sm shadow-lg focus:outline-none focus:ring-2 focus:ring-emerald-400">
            </div>
            <button type="submit" class="bg-accent-gold hover:bg-amber-400 text-slate-950 font-extrabold px-6 py-3 rounded-xl shadow-lg transition text-sm">
              Cari GTK
            </button>
          </form>
        </div>

        <div class="lg:col-span-4 hidden lg:grid grid-cols-2 gap-3">
          <div class="bg-kemenag-900/80 border border-emerald-500/30 rounded-2xl p-4 text-center backdrop-blur-sm">
            <span class="text-2xl font-black text-accent-yellow block">{{ count($displayTeachers) }}</span>
            <span class="text-[11px] text-emerald-200 uppercase font-semibold">GTK Ditampilkan</span>
          </div>
          <div class="bg-kemenag-900/80 border border-emerald-500/30 rounded-2xl p-4 text-center backdrop-blur-sm">
            <span class="text-2xl font-black text-white block">100%</span>
            <span class="text-[11px] text-emerald-200 uppercase font-semibold">Tersertifikasi &amp; Aktif</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= KONTEN UTAMA ================= -->
  <main id="main-content" class="py-10 lg:py-14 max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 flex-1 w-full">
    <div class="flex flex-col sm:flex-row items-center justify-between pb-6 mb-6 border-b border-slate-200 gap-3">
      <div>
        <h2 class="text-lg font-bold text-slate-900">
          Daftar Guru &amp; Tenaga Kependidikan
        </h2>
        <p class="text-xs text-slate-500 mt-0.5">
          Menampilkan {{ count($displayTeachers) }} GTK terdaftar (data contoh disinkronkan dari direktori sekolah)
        </p>
      </div>
      @if ($q !== '')
        <a href="/direktori-gtk" class="text-xs font-bold text-kemenag-700 hover:underline">Reset Filter</a>
      @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      @forelse ($displayTeachers as $teacher)
        <div class="bg-white rounded-3xl p-6 border border-slate-200 hover:border-emerald-500 hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
          <div>
            <div class="w-28 h-28 sm:w-32 sm:h-32 mx-auto rounded-2xl overflow-hidden border-2 border-emerald-600/30 group-hover:border-emerald-600 mb-4 bg-slate-100 shadow-sm relative transition duration-300">
              <img src="{{ $teacher['avatar'] }}" alt="{{ $teacher['name'] }}" class="w-full h-full object-cover object-top group-hover:scale-105 transition duration-500">
            </div>

            <div class="text-center mb-2.5">
              <span class="bg-emerald-100 text-emerald-800 text-xs font-extrabold px-3.5 py-1 rounded-full uppercase inline-block shadow-xs">
                {{ $teacher['badge'] }}
              </span>
            </div>

            <h3 class="text-base sm:text-lg font-bold text-slate-900 text-center leading-snug group-hover:text-emerald-700 transition">
              {{ $teacher['name'] }}
            </h3>

            <p class="text-xs sm:text-sm text-emerald-800 font-semibold text-center mt-1.5">
              {{ $teacher['position'] }}
            </p>
          </div>
        </div>
      @empty
        <div class="col-span-full rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
          Tidak ditemukan GTK dengan kata kunci "{{ $q }}".
        </div>
      @endforelse
    </div>
  </main>
@endsection
