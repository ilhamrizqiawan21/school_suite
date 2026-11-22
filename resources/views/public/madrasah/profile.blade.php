@extends('public.layouts.madrasah')

@php
  $schoolName = ($profile['name'] ?? null) ?: 'Sekolah';
  $accreditation = ($profile['accreditation'] ?? null) ?: 'A (Unggul)';
  $valuesBlock = $contentBlocks['profile.values'] ?? [];
  $pillarsBlock = $contentBlocks['profile.pillars'] ?? [];
  $valuesTitle = $valuesBlock['title'] ?? '5 Nilai Budaya Kerja Kementerian Agama';
  $valuesSubtitle = $valuesBlock['subtitle'] ?? ('Pondasi karakter dan integritas seluruh aparatur, pendidik, dan tenaga kependidikan ' . $schoolName . '.');
  $pillarsTitle = $pillarsBlock['title'] ?? ('4 Pilar Keunggulan ' . $schoolName);
  $visionText = ($profile['vision'] ?? null) ?: 'Terwujudnya Generasi yang Berakhlak Mulia, Unggul dalam Prestasi Akademik & Riset, Berwawasan Lingkungan, serta Mandiri Berlandaskan Iman dan Taqwa.';
  $rawMission = trim((string) ($profile['mission'] ?? ''));
  $missionItems = $rawMission !== ''
      ? array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawMission) ?: [])))
      : [
          'Menyelenggarakan tata kelola madrasah yang akuntabel, transparan, dan berintegritas.',
          'Meningkatkan kompetensi riset ilmiah siswa melalui program Madrasah Riset & Sains Terapan.',
          'Membina karakter religius, pembiasaan sholat berjamaah, dan tahfidz Al-Qur\'an.',
          'Mengembangkan budaya peduli lingkungan, madrasah hijau, dan ramah anak.',
          'Memperkuat kecakapan digital siswa menyongsong era kecerdasan buatan (AI) & industri 4.0.',
      ];
@endphp

@section('title', 'Visi, Misi & Nilai Budaya | ' . $schoolName)

@section('content')
  <!-- ================= HERO BANNER ================= -->
  <section class="bg-gradient-to-br from-slate-900 via-kemenag-900 to-slate-950 text-white py-12 lg:py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 relative z-10">
      <div class="max-w-3xl">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/20 text-accent-yellow text-xs font-bold uppercase tracking-wider mb-3 border border-amber-400/30">
          <i class="fa-solid fa-compass"></i> {{ ($contentBlocks['copy.blade_profile']['text_001'] ?? 'Kompas & Landasan Lembaga') }}
        </div>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white mb-3 leading-tight">
          {{ ($contentBlocks['copy.blade_profile']['text_002'] ?? 'Visi, Misi & Nilai Budaya Madrasah') }}
        </h1>
        <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
          Arah strategis dan komitmen kelembagaan {{ $schoolName }} dalam mencetak generasi cendekiawan muslim yang unggul, berakhlak mulia, dan siap memimpin peradaban masa depan.
        </p>
      </div>
    </div>
  </section>

  <!-- ================= KONTEN UTAMA VISI & MISI ================= -->
  <main id="main-content" class="py-12 lg:py-16 max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 flex-1 w-full space-y-12">

    <!-- Bagian 1: Visi & Misi Side-by-Side Modern -->
    <div id="arah" class="grid lg:grid-cols-12 gap-8 items-stretch">

      <!-- Visi Madrasah (5 Kolom) -->
      <div class="lg:col-span-5 bg-gradient-to-br from-kemenag-900 via-kemenag-950 to-slate-950 text-white p-7 sm:p-9 rounded-3xl shadow-xl border border-emerald-500/30 flex flex-col justify-between relative overflow-hidden">
        <div class="absolute top-0 right-0 w-44 h-44 bg-accent-gold/15 rounded-full blur-2xl pointer-events-none"></div>

        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-accent-yellow text-xs font-bold uppercase tracking-wider mb-4 border border-emerald-400/30">
            <i class="fa-solid fa-bullseye"></i> {{ ($contentBlocks['copy.blade_profile']['text_003'] ?? 'Visi Utama') }}
          </div>
          <h2 class="text-xl sm:text-2xl font-extrabold text-white mb-4">
            Visi {{ $schoolName }}
          </h2>
          <p class="text-emerald-100 text-sm sm:text-base italic leading-relaxed bg-white/5 p-5 rounded-2xl border border-white/10 backdrop-blur-sm">
            "{{ $visionText }}"
          </p>
        </div>

        <div class="pt-6 mt-6 border-t border-emerald-800/80 flex items-center justify-between text-xs text-emerald-200">
          <span class="flex items-center gap-1.5">
            <i class="fa-solid fa-award text-accent-yellow"></i> {{ ($contentBlocks['copy.blade_profile']['text_004'] ?? 'Akreditasi:') }} <strong>{{ $accreditation }}</strong>
          </span>
          <span class="font-mono">{{ ($contentBlocks['copy.blade_profile']['text_005'] ?? 'BAN-S/M') }}</span>
        </div>
      </div>

      <!-- Misi Madrasah (7 Kolom) -->
      <div class="lg:col-span-7 bg-white p-7 sm:p-9 rounded-3xl shadow-sm border border-slate-200 flex flex-col justify-between">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-kemenag-800 text-xs font-bold uppercase tracking-wider mb-4 border border-emerald-200">
            <i class="fa-solid fa-list-check"></i> {{ ($contentBlocks['copy.blade_profile']['text_006'] ?? 'Misi Strategis') }}
          </div>
          <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 mb-4">
            {{ ($contentBlocks['copy.blade_profile']['text_007'] ?? 'Misi & Program Aksi Utama') }}
          </h2>

          <div class="space-y-3.5">
            @foreach ($missionItems as $idx => $mission)
              <div class="flex items-start gap-3.5 p-3 rounded-2xl bg-slate-50 border border-slate-100 hover:border-emerald-200 transition">
                <span class="w-7 h-7 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-xs mt-0.5">
                  {{ $idx + 1 }}
                </span>
                <p class="text-xs sm:text-sm text-slate-700 leading-relaxed font-medium">
                  {{ preg_replace('/^\d+[\.\)]\s*/', '', $mission) }}
                </p>
              </div>
            @endforeach
          </div>
        </div>

        <div class="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <span>{{ ($contentBlocks['copy.blade_profile']['text_008'] ?? 'Panduan Pelaksanaan Kurikulum Merdeka') }}</span>
          <a href="{{ ($contentBlocks['copy.blade_profile']['href_030'] ?? '/direktori-gtk') }}" class="text-kemenag-700 font-bold hover:underline flex items-center gap-1">
            <span>{{ ($contentBlocks['copy.blade_profile']['text_009'] ?? 'Lihat Dewan Guru') }}</span> &rarr;
          </a>
        </div>
      </div>

    </div>

    <!-- Bagian 2: 5 Nilai Budaya Kerja Kemenag -->
    @if (($valuesBlock['visible'] ?? true) !== false)
    <div class="bg-gradient-to-b from-emerald-50/50 to-white p-8 sm:p-10 rounded-3xl border border-emerald-200/90 shadow-sm">
      <div class="text-center max-w-2xl mx-auto mb-8">
        <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-800 bg-emerald-100 px-3 py-1 rounded-full border border-emerald-200 inline-block mb-2">
          {{ ($contentBlocks['copy.blade_profile']['text_010'] ?? 'Etika Kerja ASN Kemenag') }}
        </span>
        <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900">
          {{ $valuesTitle }}
        </h3>
        <p class="text-xs sm:text-sm text-slate-600 mt-1">
          {{ $valuesSubtitle }}
        </p>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 hover:border-emerald-500 shadow-xs hover:shadow-md transition text-center flex flex-col items-center">
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl mb-3 shadow-xs">
            <i class="fa-solid fa-shield-heart"></i>
          </div>
          <h4 class="text-sm font-extrabold text-slate-900 mb-1">{{ ($contentBlocks['copy.blade_profile']['text_011'] ?? '1. Integritas') }}</h4>
          <p class="text-[11px] text-slate-500 leading-relaxed">
            {{ ($contentBlocks['copy.blade_profile']['text_012'] ?? 'Keselarasan antara hati, pikiran, perkataan, dan perbuatan yang baik dan benar serta anti-gratifikasi.') }}
          </p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 hover:border-emerald-500 shadow-xs hover:shadow-md transition text-center flex flex-col items-center">
          <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl mb-3 shadow-xs">
            <i class="fa-solid fa-briefcase"></i>
          </div>
          <h4 class="text-sm font-extrabold text-slate-900 mb-1">{{ ($contentBlocks['copy.blade_profile']['text_013'] ?? '2. Profesionalitas') }}</h4>
          <p class="text-[11px] text-slate-500 leading-relaxed">
            {{ ($contentBlocks['copy.blade_profile']['text_014'] ?? 'Bekerja secara disiplin, kompeten, tepat waktu, dan memberikan mutu layanan terbaik bagi peserta didik.') }}
          </p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 hover:border-emerald-500 shadow-xs hover:shadow-md transition text-center flex flex-col items-center">
          <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center text-xl mb-3 shadow-xs">
            <i class="fa-solid fa-lightbulb"></i>
          </div>
          <h4 class="text-sm font-extrabold text-slate-900 mb-1">{{ ($contentBlocks['copy.blade_profile']['text_015'] ?? '3. Inovasi') }}</h4>
          <p class="text-[11px] text-slate-500 leading-relaxed">
            {{ ($contentBlocks['copy.blade_profile']['text_016'] ?? 'Menyempurnakan yang sudah ada dan mengkreasi hal baru demi kemajuan pembelajaran riset & digitalisasi.') }}
          </p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 hover:border-emerald-500 shadow-xs hover:shadow-md transition text-center flex flex-col items-center">
          <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-800 flex items-center justify-center text-xl mb-3 shadow-xs">
            <i class="fa-solid fa-scale-balanced"></i>
          </div>
          <h4 class="text-sm font-extrabold text-slate-900 mb-1">{{ ($contentBlocks['copy.blade_profile']['text_017'] ?? '4. Tanggung Jawab') }}</h4>
          <p class="text-[11px] text-slate-500 leading-relaxed">
            {{ ($contentBlocks['copy.blade_profile']['text_018'] ?? 'Bekerja secara tuntas dan konsekuen terhadap seluruh tugas pembinaan akademik dan moral santri.') }}
          </p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 hover:border-emerald-500 shadow-xs hover:shadow-md transition text-center flex flex-col items-center">
          <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-800 flex items-center justify-center text-xl mb-3 shadow-xs">
            <i class="fa-solid fa-hand-holding-heart"></i>
          </div>
          <h4 class="text-sm font-extrabold text-slate-900 mb-1">{{ ($contentBlocks['copy.blade_profile']['text_019'] ?? '5. Keteladanan') }}</h4>
          <p class="text-[11px] text-slate-500 leading-relaxed">
            {{ ($contentBlocks['copy.blade_profile']['text_020'] ?? 'Menjadi contoh yang baik bagi lingkungan madrasah, masyarakat luas, dan generasi muda penerus bangsa.') }}
          </p>
        </div>
      </div>
    </div>
    @endif

    <!-- Bagian 3: 4 Pilar Keunggulan Madrasah -->
    @if (($pillarsBlock['visible'] ?? true) !== false)
    <div>
      <div class="text-center max-w-2xl mx-auto mb-8">
        <span class="text-xs font-bold text-kemenag-700 uppercase tracking-widest">{{ ($contentBlocks['copy.blade_profile']['text_021'] ?? 'Karakter Khas') }}</span>
        <h3 class="text-2xl font-extrabold text-slate-900 mt-1">
          {{ $pillarsTitle }}
        </h3>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-xs hover:border-emerald-500 transition">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-lg mb-3">
            <i class="fa-solid fa-microscope"></i>
          </div>
          <h4 class="text-sm font-extrabold text-slate-900 mb-1">{{ ($contentBlocks['copy.blade_profile']['text_022'] ?? 'Madrasah Riset') }}</h4>
          <p class="text-xs text-slate-600 leading-relaxed">{{ ($contentBlocks['copy.blade_profile']['text_023'] ?? 'Fokus pembimbingan karya tulis ilmiah remaja (KIR/MYRES) dan inovasi sains lingkungan rawa.') }}</p>
        </div>

        <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-xs hover:border-amber-500 transition">
          <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg mb-3">
            <i class="fa-solid fa-book-quran"></i>
          </div>
          <h4 class="text-sm font-extrabold text-slate-900 mb-1">{{ ($contentBlocks['copy.blade_profile']['text_024'] ?? 'Tahfidz & Turats') }}</h4>
          <p class="text-xs text-slate-600 leading-relaxed">{{ ($contentBlocks['copy.blade_profile']['text_025'] ?? 'Pembiasaan sholat berjamaah, tahfidz Al-Qur\'an teruji publik, dan pengkajian kitab kuning.') }}</p>
        </div>

        <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-xs hover:border-teal-500 transition">
          <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center text-lg mb-3">
            <i class="fa-solid fa-leaf"></i>
          </div>
          <h4 class="text-sm font-extrabold text-slate-900 mb-1">{{ ($contentBlocks['copy.blade_profile']['text_026'] ?? 'Adiwiyata Mandiri') }}</h4>
          <p class="text-xs text-slate-600 leading-relaxed">{{ ($contentBlocks['copy.blade_profile']['text_027'] ?? 'Ekosistem belajar asri, ramah anak, hijau, bebas asap rokok, serta pembiasaan pilah sampah.') }}</p>
        </div>

        <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-xs hover:border-purple-500 transition">
          <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center text-lg mb-3">
            <i class="fa-solid fa-shield-heart"></i>
          </div>
          <h4 class="text-sm font-extrabold text-slate-900 mb-1">{{ ($contentBlocks['copy.blade_profile']['text_028'] ?? 'Zona Integritas (WBK)') }}</h4>
          <p class="text-xs text-slate-600 leading-relaxed">{{ ($contentBlocks['copy.blade_profile']['text_029'] ?? 'Pelayanan terpadu digital tanpa antre, bebas pungli, transparan, dan berakuntabilitas tinggi.') }}</p>
        </div>
      </div>
    </div>
    @endif

  </main>
@endsection
