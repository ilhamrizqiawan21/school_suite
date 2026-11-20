@extends('public.layouts.madrasah')

@php($schoolName = ($profile['name'] ?? null) ?: 'Madrasah Contoh Nusantara')

@section('content')
  <main id="main-content">
  <!-- Hero -->
  <section class="relative bg-gradient-to-br from-kemenag-950 via-kemenag-900 to-slate-950 text-white py-12 lg:py-16 overflow-hidden border-b border-emerald-900/60">
    <!-- Ambient Radial Glows & Grid Pattern -->
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#34d399_1px,transparent_1px)] [background-size:20px_20px]"></div>
    <div class="absolute -right-20 -top-20 w-96 h-96 rounded-full bg-emerald-500/15 blur-3xl pointer-events-none"></div>
    <div class="absolute -left-20 -bottom-20 w-96 h-96 rounded-full bg-accent-gold/15 blur-3xl pointer-events-none"></div>

    <div class="relative max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="grid lg:grid-cols-12 gap-8 lg:gap-10 items-center">

        <!-- LEFT COLUMN (7 COLS): Headline, Trust Badges, Direct Actions & Micro-Stats -->
        <div class="lg:col-span-7 space-y-5">

          <!-- Trust Badge Pills -->
          <div class="flex flex-wrap items-center gap-2.5">
            <div class="inline-flex items-center gap-2 bg-emerald-950/90 border border-emerald-500/40 px-3.5 py-1.5 rounded-full text-xs font-semibold text-emerald-200 shadow-md">
              <span class="flex h-2 w-2 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent-yellow opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-accent-yellow"></span>
              </span>
              <span>Madrasah Aliyah Negeri Riset &amp; Adiwiyata Mandiri</span>
            </div>
            <a href="#zona-integritas" class="inline-flex items-center gap-1.5 bg-gradient-to-r from-emerald-900/90 to-kemenag-950/90 hover:from-emerald-800 hover:to-kemenag-900 border border-emerald-400/40 px-3 py-1.5 rounded-full text-xs font-semibold text-emerald-200 shadow-md transition group">
              <i class="fa-solid fa-shield-heart text-amber-400 group-hover:scale-110 transition-transform"></i>
              <span>Zona Integritas (WBK)</span>
            </a>
          </div>

          <!-- Main Headline -->
          <h1 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-extrabold font-sans leading-[1.15] tracking-tight text-white">
            Mencetak Generasi <span class="text-transparent bg-clip-text bg-gradient-to-r from-accent-yellow via-yellow-200 to-emerald-300">Qur’ani, Unggul Riset</span>, dan Berintegritas
          </h1>

          <!-- School Pitch -->
          <p class="text-xs sm:text-sm lg:text-base text-emerald-100/90 max-w-2xl leading-relaxed font-normal">
            Temukan informasi madrasah, penerimaan siswa, dan layanan administrasi.
          </p>

          <!-- 4 Direct Action Buttons -->
          <div class="pt-2 flex flex-wrap items-center gap-3">
            <a href="/ppdb" class="bg-gradient-to-r from-accent-gold via-amber-400 to-accent-gold hover:from-amber-400 hover:to-amber-500 text-slate-950 font-extrabold px-5 py-2.5 rounded-xl shadow-lg shadow-amber-500/20 hover:scale-105 transition-all flex items-center gap-2 text-xs sm:text-sm">
              <i class="fa-solid fa-graduation-cap"></i>
              <span>Daftar PPDB</span>
            </a>

            <a href="/layanan/ptsp" class="bg-gradient-to-r from-kemenag-700 to-emerald-700 hover:from-kemenag-600 hover:to-emerald-600 border border-emerald-400/40 text-white font-bold px-5 py-2.5 rounded-xl hover:scale-105 transition-all flex items-center gap-2 text-xs sm:text-sm shadow-md">
              <i class="fa-solid fa-stamp text-accent-yellow"></i>
              <span>PTSP Online</span>
            </a>

            <a href="/layanan/lapor" class="bg-gradient-to-r from-rose-600 to-redbright-700 hover:from-rose-700 hover:to-redbright-800 border border-rose-400/40 text-white font-bold px-4 py-2.5 rounded-xl hover:scale-105 transition-all flex items-center gap-2 text-xs sm:text-sm shadow-md">
              <i class="fa-solid fa-shield-heart text-white"></i>
              <span>Siap Lapor</span>
            </a>

            <a href="/lacak" class="bg-slate-900/90 hover:bg-slate-800 border border-slate-700 text-slate-200 hover:text-white font-bold px-4 py-2.5 rounded-xl hover:scale-105 transition-all flex items-center gap-2 text-xs sm:text-sm shadow-sm">
              <i class="fa-solid fa-magnifying-glass text-accent-yellow"></i>
              <span>Lacak Berkas</span>
            </a>
          </div>

          <!-- School profile and service shortcuts -->
          <div class="pt-5 grid grid-cols-2 sm:grid-cols-4 gap-2.5 border-t border-emerald-800/60 text-center">
            <div class="bg-kemenag-900/70 p-2.5 rounded-xl border border-emerald-500/20 backdrop-blur-sm">
              <span class="text-xl lg:text-2xl font-black text-accent-yellow block font-sans">{{ ($profile['accreditation'] ?? null) ?: 'Contoh' }}</span>
              <span class="text-[10px] text-emerald-200 uppercase font-medium">Akreditasi BAN-S/M</span>
            </div>
            <a href="/direktori-gtk" class="bg-kemenag-900/70 p-2.5 rounded-xl border border-emerald-500/20 backdrop-blur-sm">
              <span class="text-xl lg:text-2xl font-black text-white block font-sans">GTK</span>
              <span class="text-[10px] text-emerald-200 uppercase font-medium">Direktori guru &amp; tendik</span>
            </a>
            <a href="/ppdb" class="bg-kemenag-900/70 p-2.5 rounded-xl border border-emerald-500/20 backdrop-blur-sm">
              <span class="text-xl lg:text-2xl font-black text-white block font-sans">PPDB</span>
              <span class="text-[10px] text-emerald-200 uppercase font-medium">Penerimaan siswa baru</span>
            </a>
            <a href="/alumni" class="bg-kemenag-900/70 p-2.5 rounded-xl border border-emerald-500/20 backdrop-blur-sm">
              <span class="text-xl lg:text-2xl font-black text-accent-yellow block font-sans">Alumni</span>
              <span class="text-[10px] text-emerald-200 uppercase font-medium">Portal tracer study</span>
            </a>
          </div>

        </div>

        <!-- Hero Visual Right (Modern Science Lab & Floating Achievement Badges) -->
        <div class="lg:col-span-5 relative">
          <div class="relative mx-auto max-w-md lg:max-w-none">
            <div class="rounded-3xl overflow-hidden shadow-2xl border-4 border-emerald-500/30 bg-kemenag-800 relative group">
              <img src="/madrasah-theme/images/lab.webp" alt="Laboratorium Sains &amp; Riset Modern {{ $schoolName }}" class="w-full h-80 sm:h-96 object-cover group-hover:scale-105 transition-transform duration-700">
              <div class="absolute inset-0 bg-gradient-to-t from-kemenag-950 via-kemenag-950/20 to-transparent"></div>

              <!-- Floating Badge 1 (Top Left) -->
              <div class="absolute top-4 left-4 bg-kemenag-950/90 backdrop-blur-md border border-emerald-500/40 px-3 py-2 rounded-2xl shadow-xl flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-accent-gold text-slate-950 flex items-center justify-center font-bold text-sm">
                  <i class="fa-solid fa-medal"></i>
                </div>
                <div>
                  <span class="text-[10px] text-emerald-300 font-semibold block uppercase">Prestasi Unggulan</span>
                  <span class="text-xs font-extrabold text-white">Medali Emas KSM 2026</span>
                </div>
              </div>

              <!-- Floating Badge 2 (Bottom Right) -->
              <div class="absolute bottom-16 right-4 bg-kemenag-950/90 backdrop-blur-md border border-emerald-500/40 px-3.5 py-2 rounded-2xl shadow-xl hidden sm:flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">
                  <i class="fa-solid fa-microchip"></i>
                </div>
                <div>
                  <span class="text-[10px] text-emerald-300 font-semibold block uppercase">Pusat Inovasi</span>
                  <span class="text-xs font-extrabold text-white">Lab Riset &amp; Robotik IoT</span>
                </div>
              </div>

              <div class="absolute bottom-4 left-4 right-4 text-white">
                <span class="bg-accent-gold text-slate-900 font-extrabold text-[10px] px-2.5 py-0.5 rounded-full uppercase tracking-wider">Inovasi &amp; Riset Madrasah</span>
                <h3 class="text-base font-bold mt-1 text-white">Laboratorium Sains &amp; Komputer Modern</h3>
                <p class="text-xs text-emerald-200">Mempersiapkan cendekiawan muslim yang kompetitif di kancah nasional.</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ================= 3. RUNNING CARDS: PINTASAN MODUL PENDUKUNG ================= -->
  <section class="relative bg-kemenag-950 py-3.5 overflow-hidden border-b border-emerald-800/40">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 relative">

      <!-- Gradient Fade Edge Masks -->
      <div class="absolute left-4 sm:left-8 lg:left-12 top-0 bottom-0 w-16 sm:w-24 bg-gradient-to-r from-kemenag-950 via-kemenag-950/90 to-transparent z-10 pointer-events-none"></div>
      <div class="absolute right-4 sm:right-8 lg:right-12 top-0 bottom-0 w-16 sm:w-24 bg-gradient-to-l from-kemenag-950 via-kemenag-950/90 to-transparent z-10 pointer-events-none"></div>

      <!-- Lintasan Kartu Berjalan -->
      <div class="overflow-hidden rounded-2xl">
        <div class="flex animate-marquee-slow hover:pause-on-hover whitespace-nowrap">

                    <div class="flex space-x-3.5 px-2">
            <a href="/layanan/buku-tamu" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-address-book text-emerald-400 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Buku Tamu Digital</span>
                <span class="text-[10px] text-emerald-300">Resepsionis &amp; Kunjungan Tamu</span>
              </div>
            </a>
            <a href="/layanan/legalisir" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-certificate text-amber-400 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Legalisir Online</span>
                <span class="text-[10px] text-emerald-300">Pengesahan Ijazah Siswa</span>
              </div>
            </a>
            <a href="/layanan/mutasi" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-people-arrows text-teal-400 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Mutasi Siswa</span>
                <span class="text-[10px] text-emerald-300">Pindah Masuk / Keluar</span>
              </div>
            </a>
            <a href="/layanan/izin-siswa" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-file-signature text-blue-400 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Surat Izin Siswa</span>
                <span class="text-[10px] text-emerald-300">Izin Tidak Masuk Sekolah</span>
              </div>
            </a>
            <a href="/piket/izin-keluar" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-door-open text-emerald-400 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Izin Keluar-Masuk</span>
                <span class="text-[10px] text-emerald-300">Piket Siswa &amp; Santri</span>
              </div>
            </a>
            <a href="/pos-jaga" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-shield-halved text-accent-yellow text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Scan Pos Satpam</span>
                <span class="text-[10px] text-emerald-300">Pintu Gerbang Madrasah</span>
              </div>
            </a>
            <a href="/bk/kasus" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-qrcode text-amber-300 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Scan Disiplin BK</span>
                <span class="text-[10px] text-emerald-300">Pencatatan Pelanggaran Siswa</span>
              </div>
            </a>
            <a href="/perpustakaan" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-book-bookmark text-emerald-400 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Perpustakaan Digital</span>
                <span class="text-[10px] text-emerald-300">Katalog Buku &amp; E-Book</span>
              </div>
            </a>
            <a href="/alumni" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-user-graduate text-accent-yellow text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Ikatan Alumni</span>
                <span class="text-[10px] text-emerald-300">Tracer Study &amp; Karir</span>
              </div>
            </a>
            <a href="/layanan/kelulusan" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-award text-amber-300 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Kelulusan Siswa</span>
                <span class="text-[10px] text-emerald-300">Cek SKL &amp; Nilai Ujian</span>
              </div>
            </a>
          </div>
                    <div class="flex space-x-3.5 px-2">
            <a href="/layanan/buku-tamu" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-address-book text-emerald-400 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Buku Tamu Digital</span>
                <span class="text-[10px] text-emerald-300">Resepsionis &amp; Kunjungan Tamu</span>
              </div>
            </a>
            <a href="/layanan/legalisir" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-certificate text-amber-400 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Legalisir Online</span>
                <span class="text-[10px] text-emerald-300">Pengesahan Ijazah Siswa</span>
              </div>
            </a>
            <a href="/layanan/mutasi" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-people-arrows text-teal-400 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Mutasi Siswa</span>
                <span class="text-[10px] text-emerald-300">Pindah Masuk / Keluar</span>
              </div>
            </a>
            <a href="/layanan/izin-siswa" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-file-signature text-blue-400 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Surat Izin Siswa</span>
                <span class="text-[10px] text-emerald-300">Izin Tidak Masuk Sekolah</span>
              </div>
            </a>
            <a href="/piket/izin-keluar" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-door-open text-emerald-400 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Izin Keluar-Masuk</span>
                <span class="text-[10px] text-emerald-300">Piket Siswa &amp; Santri</span>
              </div>
            </a>
            <a href="/pos-jaga" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-shield-halved text-accent-yellow text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Scan Pos Satpam</span>
                <span class="text-[10px] text-emerald-300">Pintu Gerbang Madrasah</span>
              </div>
            </a>
            <a href="/bk/kasus" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-qrcode text-amber-300 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Scan Disiplin BK</span>
                <span class="text-[10px] text-emerald-300">Pencatatan Pelanggaran Siswa</span>
              </div>
            </a>
            <a href="/perpustakaan" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-book-bookmark text-emerald-400 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Perpustakaan Digital</span>
                <span class="text-[10px] text-emerald-300">Katalog Buku &amp; E-Book</span>
              </div>
            </a>
            <a href="/alumni" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-user-graduate text-accent-yellow text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Ikatan Alumni</span>
                <span class="text-[10px] text-emerald-300">Tracer Study &amp; Karir</span>
              </div>
            </a>
            <a href="/layanan/kelulusan" class="inline-flex items-center gap-3 bg-kemenag-900/90 border border-emerald-500/30 px-3.5 py-2 rounded-xl hover:border-accent-yellow transition text-white shadow-sm">
              <i class="fa-solid fa-award text-amber-300 text-sm"></i>
              <div class="text-left">
                <span class="text-xs font-bold block">Kelulusan Siswa</span>
                <span class="text-[10px] text-emerald-300">Cek SKL &amp; Nilai Ujian</span>
              </div>
            </a>
          </div>

        </div>
      </div>

    </div>
  </section>

  <!-- ================= SAMBUTAN KEPALA MADRASAH ================= -->
  <section id="sambutan" class="py-7 lg:py-8 bg-white border-b border-slate-200">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="grid lg:grid-cols-12 gap-6 items-center">

        <div class="lg:col-span-4 flex flex-col items-center text-center">
          <div class="relative w-56 h-64 sm:w-64 sm:h-72 rounded-2xl overflow-hidden shadow-xl border-4 border-kemenag-700 bg-kemenag-800">
                        <img src="/madrasah-theme/images/portrait.webp" alt="Kepala {{ $schoolName }}" class="w-full h-full object-cover">
          </div>
          <h3 class="font-extrabold text-slate-900 text-base mt-3">{{ ($profile['principal_name'] ?? null) ?: 'Pimpinan Madrasah' }}</h3>
          <p class="text-xs text-kemenag-700 font-semibold">Kepala {{ $schoolName }}</p>
        </div>

        <div class="lg:col-span-8 space-y-4">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-kemenag-800">
            <i class="fa-solid fa-quote-left"></i> Sambutan Kepala Madrasah
          </div>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            Meneguhkan Eksistensi Madrasah Unggul: Berakar pada Nilai Keislaman, Bertumbuh dalam Sains &amp; Teknologi, dan Mendunia dengan Karakter Mulia          </h2>

          <!-- Konten Teks Sambutan (2 Paragraf Awal + Sisanya Bisa Buka-Tutup) -->
          <div class="text-slate-600 text-sm sm:text-base leading-relaxed space-y-3 relative">
            <p>Assalamu'alaikum Warahmatullahi Wabarakatuh.</p>
              <p>Selamat datang di portal resmi madrasah.</p>          </div>

          <!-- Tombol Aksi Buka/Tutup & Link Visi Misi -->
          <div class="pt-2 relative">

            <div class="flex flex-wrap items-center gap-3">

              <a href="/profil-sekolah#arah" class="inline-flex items-center gap-2 bg-white hover:bg-emerald-50 text-slate-700 hover:text-kemenag-800 font-bold px-4 py-2.5 rounded-xl text-xs sm:text-sm shadow-sm border border-slate-200 hover:border-emerald-300 transition-all">
                <i class="fa-solid fa-compass text-amber-500"></i>
                <span>Visi &amp; Misi Madrasah</span>
                <i class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
              </a>

              <a href="/direktori-gtk" class="inline-flex items-center gap-2 bg-white hover:bg-emerald-50 text-slate-700 hover:text-kemenag-800 font-bold px-4 py-2.5 rounded-xl text-xs sm:text-sm shadow-sm border border-slate-200 hover:border-emerald-300 transition-all">
                <i class="fa-solid fa-chalkboard-user text-emerald-600"></i>
                <span>Direktori Guru &amp; Tendik</span>
                <i class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
              </a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ================= ZONA INTEGRITAS & MAKLUMAT PELAYANAN ================= -->
    <section id="zona-integritas" class="py-10 lg:py-14 bg-gradient-to-b from-slate-50 via-white to-slate-50 border-b border-slate-200 relative overflow-hidden">
    <!-- Ambient Blur Glows -->
    <div class="absolute -top-24 -right-24 w-80 h-80 bg-emerald-100/50 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-amber-100/50 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 relative">

      <!-- Section Header -->
      <div class="text-center max-w-3xl mx-auto mb-8 sm:mb-10">
        <div class="inline-flex items-center gap-2 bg-emerald-100 border border-emerald-300/80 px-3.5 py-1 rounded-full text-xs font-extrabold text-emerald-900 mb-2 shadow-xs">
          <i class="fa-solid fa-shield-halved text-amber-500"></i>
          <span>MENUJU WILAYAH BEBAS DARI KORUPSI (WBK &amp; WBBM)</span>
        </div>
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold font-sans text-slate-900 tracking-tight leading-tight">
          Komitmen Pelayanan Bersih, <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-700 to-teal-600">Transparan &amp; Berintegritas</span>
        </h2>
        <p class="text-slate-600 text-xs sm:text-sm mt-2 leading-relaxed">
          {{ $schoolName }} berkomitmen menyelenggarakan tata kelola madrasah yang bebas dari korupsi, kolusi, nepotisme, dan gratifikasi serta memberikan pelayanan publik yang prima dan ramah bagi seluruh santri, wali murid, dan masyarakat.
        </p>
      </div>

      <!-- Grid 3 Blok Terpadu -->
      <div class="grid lg:grid-cols-12 gap-6 items-stretch">

        <!-- Kiri (5 Kolom): Maklumat Pelayanan Resmi & Tanda Tangan Pimpinan -->
        <div class="lg:col-span-5 bg-gradient-to-br from-kemenag-900 via-kemenag-950 to-slate-950 text-white p-6 sm:p-7 rounded-3xl shadow-xl border border-emerald-500/30 flex flex-col justify-between relative overflow-hidden">
          <div class="absolute top-0 right-0 w-40 h-40 bg-accent-gold/10 rounded-full blur-2xl pointer-events-none"></div>

          <div>
            <div class="flex items-center justify-between pb-3 border-b border-emerald-800/80 mb-4">
              <span class="text-xs font-extrabold uppercase tracking-widest text-accent-yellow flex items-center gap-1.5">
                <i class="fa-solid fa-certificate"></i> Maklumat Pelayanan
              </span>
              <span class="text-[10px] bg-emerald-500/20 text-emerald-200 border border-emerald-400/40 px-2 py-0.5 rounded-full font-mono">
                Standar KemenPAN-RB
              </span>
            </div>

            <!-- Foto Mini Banner Penandatanganan Pakta Integritas Pimpinan -->
            <div class="relative rounded-2xl overflow-hidden border border-emerald-500/40 mb-4 shadow-md group cursor-pointer" role="button" tabindex="0" data-open-dialog="lightboxModal" data-image="/madrasah-theme/images/campus.webp" data-caption="Deklarasi Pencanangan Pembangunan Zona Integritas Menuju WBK &amp; WBBM {{ $schoolName }}">
              <img src="/madrasah-theme/images/campus.webp" alt="Pencanangan ZI" class="w-full h-36 object-cover group-hover:scale-105 transition duration-500">
              <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent flex items-end p-3">
                <div class="flex items-center justify-between w-full">
                  <span class="text-[11px] font-bold text-white flex items-center gap-1.5">
                    <i class="fa-solid fa-camera text-accent-yellow"></i> Penandatanganan Pakta Integritas
                  </span>
                  <span class="text-[10px] text-emerald-200 bg-black/40 px-2 py-0.5 rounded-full border border-white/20">
                    <i class="fa-solid fa-magnifying-glass-plus"></i> Perbesar
                  </span>
                </div>
              </div>
            </div>

            <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed italic mb-4">
              "Dengan ini kami segenap pimpinan, tenaga pendidik, dan kependidikan madrasah menyatakan sanggup menyelenggarakan pelayanan pendidikan dan administrasi sesuai standar pelayanan yang telah ditetapkan, memberikan kemudahan tanpa diskriminasi, serta siap menerima sanksi apabila terjadi penyimpangan."
            </p>

            <!-- Janji Layanan 3 Poin -->
            <div class="space-y-2.5 text-xs text-emerald-100">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-accent-yellow text-sm shrink-0"></i>
                <span><strong>100% Bebas Pungutan Liar &amp; Calo:</strong> Semua administrasi madrasah bebas biaya (Rp 0,-).</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-accent-yellow text-sm shrink-0"></i>
                <span><strong>Kepastian Waktu &amp; Tracking:</strong> Alur disposisi berkas terpantau secara transparan.</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-accent-yellow text-sm shrink-0"></i>
                <span><strong>Tolak Segala Bentuk Gratifikasi:</strong> Menolak suap, bingkisan, maupun imbalan apa pun.</span>
              </div>
            </div>
          </div>

          <div class="pt-5 mt-5 border-t border-emerald-800/80 flex items-center justify-between gap-3">
            <div>
              <span class="text-xs font-bold text-white block">Pimpinan Madrasah</span>
              <span class="text-[10px] text-emerald-300">Kepala {{ $schoolName }}</span>
            </div>
            <button type="button" data-open-dialog="ziAreaModal" aria-haspopup="dialog" class="bg-emerald-600/90 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-1.5 rounded-xl border border-emerald-400/30 transition flex items-center gap-1.5 shadow-sm cursor-pointer" title="Lihat 6 Area Perubahan Zona Integritas">
              <i class="fa-solid fa-cubes-stacked text-accent-yellow"></i>
              <span>6 Area ZI</span>
              <i class="fa-solid fa-chevron-right text-[9px] opacity-70"></i>
            </button>
          </div>
        </div>

        <!-- Kanan (7 Kolom): 4 Kartu Pilar ZI + Widget Survei Kepuasan -->
        <div class="lg:col-span-7 flex flex-col justify-between gap-4">

          <!-- 4 Kotak Layanan Tanpa Pungli -->
          <div class="grid sm:grid-cols-2 gap-3.5">

            <!-- Pilar 1: PTSP Online Gratis -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-emerald-500 hover:shadow-md transition duration-300 flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-base shrink-0">
                <i class="fa-solid fa-stamp"></i>
              </div>
              <div class="min-w-0">
                <div class="flex items-center justify-between gap-1">
                  <h4 class="text-xs font-extrabold text-slate-900 truncate">PTSP Digital Rp 0,-</h4>
                  <span class="bg-emerald-100 text-emerald-800 text-[9px] font-black px-1.5 py-0.2 rounded-full">GRATIS</span>
                </div>
                <p class="text-[11px] text-slate-500 leading-tight mt-0.5">Izin, mutasi, permohonan surat &amp; legalisir tanpa biaya.</p>
                <a href="/layanan/ptsp" class="text-[11px] font-bold text-emerald-700 hover:underline mt-1 inline-flex items-center gap-1">
                  <span>Akses PTSP Daring</span> <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </a>
              </div>
            </div>

            <!-- Pilar 2: Legalisir Cepat & Bebas Calo -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-amber-500 hover:shadow-md transition duration-300 flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-base shrink-0">
                <i class="fa-solid fa-certificate"></i>
              </div>
              <div class="min-w-0">
                <div class="flex items-center justify-between gap-1">
                  <h4 class="text-xs font-extrabold text-slate-900 truncate">Legalisir Bebas Calo</h4>
                  <span class="bg-amber-100 text-amber-800 text-[9px] font-black px-1.5 py-0.2 rounded-full">ALUMNI</span>
                </div>
                <p class="text-[11px] text-slate-500 leading-tight mt-0.5">Pengesahan ijazah alumni terverifikasi dengan barcode tiket.</p>
                <a href="/layanan/legalisir" class="text-[11px] font-bold text-amber-700 hover:underline mt-1 inline-flex items-center gap-1">
                  <span>Layanan Alumni</span> <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </a>
              </div>
            </div>

            <!-- Pilar 3: PPDB Transparan & Akuntabel -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-teal-500 hover:shadow-md transition duration-300 flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center text-base shrink-0">
                <i class="fa-solid fa-graduation-cap"></i>
              </div>
              <div class="min-w-0">
                <div class="flex items-center justify-between gap-1">
                  <h4 class="text-xs font-extrabold text-slate-900 truncate">PPDB Objektif &amp; Terbuka</h4>
                  <span class="bg-teal-100 text-teal-800 text-[9px] font-black px-1.5 py-0.2 rounded-full">AKUNTABEL</span>
                </div>
                <p class="text-[11px] text-slate-500 leading-tight mt-0.5">Seleksi murni berbasis prestasi dan tes terstandar, anti-titipan.</p>
                <a href="/ppdb" class="text-[11px] font-bold text-teal-700 hover:underline mt-1 inline-flex items-center gap-1">
                  <span>Alur PPDB</span> <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </a>
              </div>
            </div>

            <!-- Pilar 4: Whistleblowing & Lapor -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-rose-500 hover:shadow-md transition duration-300 flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-base shrink-0">
                <i class="fa-solid fa-bullhorn"></i>
              </div>
              <div class="min-w-0">
                <div class="flex items-center justify-between gap-1">
                  <h4 class="text-xs font-extrabold text-slate-900 truncate">Saluran WBS (Siap Lapor)</h4>
                  <span class="bg-rose-100 text-rose-800 text-[9px] font-black px-1.5 py-0.2 rounded-full">ANONIM</span>
                </div>
                <p class="text-[11px] text-slate-500 leading-tight mt-0.5">Laporkan pelanggaran disiplin atau pungli dengan jaminan kerahasiaan identitas.</p>
                <a href="/layanan/lapor" class="text-[11px] font-bold text-rose-600 hover:underline mt-1 inline-flex items-center gap-1">
                  <span>Lapor Kendala</span> <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </a>
              </div>
            </div>

          </div>

          <!-- Dashboard Capaian Kinerja & Integritas (4 Metrik Utama WBK) -->
          <div class="bg-gradient-to-br from-slate-900 via-kemenag-950 to-slate-950 rounded-2xl p-4 sm:p-5 text-white border border-emerald-500/40 shadow-md relative overflow-hidden">
            <!-- Ambient Glow -->
            <div class="absolute -right-10 -bottom-10 w-36 h-36 bg-accent-gold/15 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -left-10 -top-10 w-36 h-36 bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative">
              <div class="flex items-center justify-between pb-3 mb-3 border-b border-emerald-800/80">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                  <span class="text-[11px] font-black uppercase tracking-wider text-accent-yellow">
                    Capaian Standar Integritas &amp; Mutu Layanan
                  </span>
                </div>
                <span class="text-[9.5px] bg-emerald-500/20 text-emerald-200 border border-emerald-400/40 px-2 py-0.5 rounded-full font-mono">
                  Publikasi Madrasah 2026                </span>
              </div>

              <!-- 4 Metrik Grid -->
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3">

                <!-- Metrik 1: IKM -->
                <div class="p-3 rounded-xl bg-white/5 border border-white/10 hover:border-amber-400/50 transition text-center group">
                  <div class="w-7 h-7 mx-auto rounded-lg bg-amber-400/20 text-accent-yellow flex items-center justify-center text-xs mb-1.5 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-star"></i>
                  </div>
                  <span class="text-[10px] text-emerald-200 block font-medium">Indeks Kepuasan (IKM)</span>
                  <div class="text-base sm:text-lg font-black text-accent-yellow mt-0.5">{{ $ikm['total_responses'] > 0 ? number_format($ikm['average_rating'], 2, ',', '.') . ' / 5' : 'Belum ada nilai' }}</div>
                  <span class="inline-block mt-1 text-[8.5px] font-extrabold bg-emerald-500/30 text-emerald-300 px-1.5 py-0.2 rounded"> {{ $ikm['total_responses'] }} tanggapan</span>
                </div>

                <!-- Metrik 2: IPAK -->
                <div class="p-3 rounded-xl bg-white/5 border border-white/10 hover:border-emerald-400/50 transition text-center group">
                  <div class="w-7 h-7 mx-auto rounded-lg bg-emerald-400/20 text-emerald-300 flex items-center justify-center text-xs mb-1.5 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-shield-heart"></i>
                  </div>
                  <span class="text-[10px] text-emerald-200 block font-medium">Pengaduan Publik</span>
                  <div class="text-base sm:text-lg font-black text-white mt-0.5"><a href="/layanan/lapor">Siap Lapor</a></div>
                  <span class="inline-block mt-1 text-[8.5px] font-extrabold bg-emerald-500/30 text-emerald-300 px-1.5 py-0.2 rounded">Kanal pengaduan</span>
                </div>

                <!-- Metrik 3: WBS Response -->
                <div class="p-3 rounded-xl bg-white/5 border border-white/10 hover:border-rose-400/50 transition text-center group">
                  <div class="w-7 h-7 mx-auto rounded-lg bg-rose-400/20 text-rose-300 flex items-center justify-center text-xs mb-1.5 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-circle-check"></i>
                  </div>
                  <span class="text-[10px] text-emerald-200 block font-medium">Transparansi Aduan</span>
                  <div class="text-base sm:text-lg font-black text-white mt-0.5"><a href="/lapor/transparansi">Lihat Rekap</a></div>
                  <span class="inline-block mt-1 text-[8.5px] font-extrabold bg-emerald-500/30 text-emerald-300 px-1.5 py-0.2 rounded">Ringkasan publik</span>
                </div>

                <!-- Metrik 4: Tarif Pungli -->
                <div class="p-3 rounded-xl bg-white/5 border border-white/10 hover:border-teal-400/50 transition text-center group">
                  <div class="w-7 h-7 mx-auto rounded-lg bg-teal-400/20 text-teal-300 flex items-center justify-center text-xs mb-1.5 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                  </div>
                  <span class="text-[10px] text-emerald-200 block font-medium">Tarif Layanan PTSP</span>
                  <div class="text-base sm:text-lg font-black text-accent-yellow mt-0.5">Rp 0,-</div>
                  <span class="inline-block mt-1 text-[8.5px] font-extrabold bg-rose-500/30 text-rose-300 px-1.5 py-0.2 rounded">Nol Pungli</span>
                </div>

              </div>
            </div>
          </div>

          <!-- Widget Interaktif Survei Kepuasan Masyarakat (IKM) & IPK -->
          <div class="bg-gradient-to-r from-emerald-50 via-teal-50/70 to-white p-4 sm:p-5 rounded-2xl border border-emerald-200 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
            <div class="flex items-center gap-3.5 text-center sm:text-left">
              <div class="w-12 h-12 rounded-2xl bg-white text-amber-500 border border-amber-200 flex items-center justify-center text-2xl shadow-xs shrink-0">
                <i class="fa-solid fa-star"></i>
              </div>
              <div>
                <div class="flex items-center gap-2 justify-center sm:justify-start">
                  <span class="text-xs font-black text-slate-900 uppercase">Indeks Kepuasan Masyarakat (IKM)</span>
                  <span class="text-[10px] font-bold bg-emerald-600 text-white px-2 py-0.5 rounded-full">{{ $ikm['total_responses'] > 0 ? number_format($ikm['average_rating'], 2, ',', '.') . ' / 5' : 'Belum ada nilai' }}</span>
                </div>
                <p class="text-[11px] text-slate-600 mt-0.5">Beri penilaian Anda terhadap mutu pelayanan PTSP &amp; administrasi madrasah untuk perbaikan mutu berkelanjutan.</p>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <button type="button" data-open-dialog="ziModal" aria-haspopup="dialog" class="bg-gradient-to-r from-emerald-700 to-kemenag-800 hover:from-emerald-800 hover:to-kemenag-900 text-white font-bold px-4 py-2.5 rounded-xl text-xs shadow-md hover:shadow-emerald-900/20 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-regular fa-star-half-stroke text-accent-yellow"></i>
                <span>Beri Rating IKM</span>
              </button>
            </div>
          </div>

        </div>

      </div>

      <!-- Sub-Baris: Galeri Eviden & Dokumentasi Lapangan ZI -->
      <div class="mt-8 pt-6 border-t border-slate-200/80">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
          <div>
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full border border-emerald-200 inline-flex items-center gap-1">
              <i class="fa-solid fa-camera-retro text-amber-500"></i> Eviden Otentik
            </span>
            <h3 class="text-base sm:text-lg font-extrabold text-slate-900 mt-1">Dokumentasi Pelaksanaan Zona Integritas</h3>
          </div>
          <span class="text-xs text-slate-500 font-medium">Foto ilustrasi AI · klik untuk memperbesar</span>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
          <!-- Eviden 1: Pencanangan & Pakta Integritas -->
          <div class="group bg-white rounded-2xl overflow-hidden border border-slate-200 hover:border-emerald-500 shadow-xs hover:shadow-md transition duration-300 cursor-pointer flex flex-col justify-between" role="button" tabindex="0" data-open-dialog="lightboxModal" data-image="/madrasah-theme/images/classroom.webp" data-caption="Deklarasi &amp; Pakta Integritas Bersama">
            <div>
              <div class="h-44 overflow-hidden relative">
                <img src="/madrasah-theme/images/classroom.webp" alt="Deklarasi &amp; Pakta Integritas Bersama" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                <span class="absolute top-2.5 left-2.5 bg-emerald-700 text-white text-[9.5px] font-extrabold px-2 py-0.5 rounded-full uppercase shadow-xs">
                  Area 1: Manajemen Perubahan
                </span>
                <span class="absolute bottom-2.5 right-2.5 bg-black/60 backdrop-blur-xs text-white text-[10px] px-2 py-0.5 rounded-lg flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <i class="fa-solid fa-expand text-[9px]"></i> Lihat
                </span>
              </div>
              <div class="p-4">
                <h4 class="text-xs font-bold text-slate-900 group-hover:text-emerald-700 transition leading-snug">Deklarasi &amp; Pakta Integritas Bersama</h4>
                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">Komitmen seluruh pendidik dan tenaga kependidikan dalam menegakkan tata kelola madrasah yang akuntabel.</p>
              </div>
            </div>
            <div class="px-4 pb-3.5 pt-0 border-t border-slate-100 text-[10px] text-emerald-700 font-semibold flex items-center justify-between">
              <span>Eviden SK &amp; Piagam Resmi</span>
              <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
            </div>
          </div>

          <!-- Eviden 2: Pelayanan Prima PTSP -->
          <div class="group bg-white rounded-2xl overflow-hidden border border-slate-200 hover:border-amber-500 shadow-xs hover:shadow-md transition duration-300 cursor-pointer flex flex-col justify-between" role="button" tabindex="0" data-open-dialog="lightboxModal" data-image="/madrasah-theme/images/reception.webp" data-caption="Pelayanan PTSP Ramah &amp; Bebas Calo">
            <div>
              <div class="h-44 overflow-hidden relative">
                <img src="/madrasah-theme/images/reception.webp" alt="Pelayanan PTSP Ramah &amp; Bebas Calo" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                <span class="absolute top-2.5 left-2.5 bg-amber-600 text-white text-[9.5px] font-extrabold px-2 py-0.5 rounded-full uppercase shadow-xs">
                  Area 6: Kualitas Pelayanan
                </span>
                <span class="absolute bottom-2.5 right-2.5 bg-black/60 backdrop-blur-xs text-white text-[10px] px-2 py-0.5 rounded-lg flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <i class="fa-solid fa-expand text-[9px]"></i> Lihat
                </span>
              </div>
              <div class="p-4">
                <h4 class="text-xs font-bold text-slate-900 group-hover:text-amber-700 transition leading-snug">Pelayanan PTSP Ramah &amp; Bebas Calo</h4>
                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">Penyelenggaraan meja resepsionis, buku tamu digital, dan asistensi cepat bagi orang tua serta alumni.</p>
              </div>
            </div>
            <div class="px-4 pb-3.5 pt-0 border-t border-slate-100 text-[10px] text-amber-700 font-semibold flex items-center justify-between">
              <span>Standar Maklumat PTSP</span>
              <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
            </div>
          </div>

          <!-- Eviden 3: Sosialisasi Anti Gratifikasi & WBS -->
          <div class="group bg-white rounded-2xl overflow-hidden border border-slate-200 hover:border-teal-500 shadow-xs hover:shadow-md transition duration-300 cursor-pointer flex flex-col justify-between" role="button" tabindex="0" data-open-dialog="lightboxModal" data-image="/madrasah-theme/images/classroom.webp" data-caption="Kampanye Budaya Tolak Gratifikasi">
            <div>
              <div class="h-44 overflow-hidden relative">
                <img src="/madrasah-theme/images/classroom.webp" alt="Kampanye Budaya Tolak Gratifikasi" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                <span class="absolute top-2.5 left-2.5 bg-teal-700 text-white text-[9.5px] font-extrabold px-2 py-0.5 rounded-full uppercase shadow-xs">
                  Area 5: Penguatan Pengawasan
                </span>
                <span class="absolute bottom-2.5 right-2.5 bg-black/60 backdrop-blur-xs text-white text-[10px] px-2 py-0.5 rounded-lg flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <i class="fa-solid fa-expand text-[9px]"></i> Lihat
                </span>
              </div>
              <div class="p-4">
                <h4 class="text-xs font-bold text-slate-900 group-hover:text-teal-700 transition leading-snug">Kampanye Budaya Tolak Gratifikasi</h4>
                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">Pembinaan berkala aparatur, sosialisasi penanganan benturan kepentingan, dan penguatan kanal pengaduan.</p>
              </div>
            </div>
            <div class="px-4 pb-3.5 pt-0 border-t border-slate-100 text-[10px] text-teal-700 font-semibold flex items-center justify-between">
              <span>Unit Pengendali Gratifikasi (UPG)</span>
              <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ================= PTSP & LAYANAN DIGITAL TERINTEGRASI ================= -->
  <section id="layanan" class="py-7 lg:py-8 bg-gradient-to-br from-kemenag-800 via-kemenag-900 to-kemenag-950 text-white border-b border-kemenag-700/60">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">

      <!-- Section Header -->
      <div class="text-center max-w-4xl mx-auto mb-5">
        <span class="text-accent-yellow text-xs font-extrabold uppercase tracking-widest block mb-1">
          TRANSFORMASI DIGITAL KEMENAG
        </span>
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold font-sans text-white tracking-tight leading-tight">
          Layanan Terpadu Satu Pintu (PTSP)
        </h2>
        <p class="text-emerald-100 text-xs sm:text-sm mt-1.5 max-w-2xl mx-auto font-normal">
          Permudah pengurusan administrasi siswa, alumni, dan orang tua secara daring tanpa antre.
        </p>
      </div>

      <!-- ================= BANNER MAKLUMAT TOLAK GRATIFIKASI & STOP PUNGLI (UPG) ================= -->
      <div class="mb-7 p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-gradient-to-r from-kemenag-950 via-slate-950 to-kemenag-950 border-2 border-amber-400/50 shadow-2xl relative overflow-hidden">
        <!-- Glow Ambient -->
        <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-rose-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -left-10 -top-10 w-44 h-44 bg-accent-gold/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative flex flex-col lg:flex-row items-center justify-between gap-4 sm:gap-6">

          <!-- Ikon & Teks Maklumat -->
          <div class="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-3.5 sm:gap-4 flex-1">
            <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-rose-600 via-red-600 to-amber-600 text-white flex items-center justify-center text-2xl sm:text-3xl shrink-0 shadow-lg border border-amber-300/40">
              <i class="fa-solid fa-hand-holding-hand"></i>
            </div>

            <div class="space-y-1">
              <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-black tracking-wider uppercase shadow-xs">
                  <i class="fa-solid fa-ban text-[9px]"></i> STOP PUNGLI &amp; GRATIFIKASI
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[10px] font-bold">
                  <i class="fa-solid fa-shield-halved text-amber-400"></i> Unit Pengendalian Gratifikasi (UPG)
                </span>
              </div>

              <h3 class="text-sm sm:text-base font-extrabold text-white tracking-tight">
                Layanan Bebas Biaya • Tolak Segala Bentuk Suap, Hadiah, atau Imbalan              </h3>

              <p class="text-xs text-emerald-100/90 leading-relaxed max-w-3xl">
                Aparatur madrasah dilarang keras menerima, dan masyarakat pemohon dilarang memberikan uang, parsel, cinderamata, atau imbalan apa pun dalam pelayanan administrasi. Seluruh layanan PTSP adalah hak masyarakat tanpa pungutan.              </p>
            </div>
          </div>

          <!-- Tombol Aksi Laporkan & Hotline WA -->
          <div class="flex flex-wrap sm:flex-nowrap items-center justify-center gap-2.5 shrink-0 w-full lg:w-auto pt-2 lg:pt-0 border-t lg:border-t-0 border-emerald-800/80">
            <a href="/layanan/lapor" class="bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 text-white text-xs font-black px-4 py-2.5 rounded-xl shadow-lg shadow-rose-900/30 hover:scale-105 transition-all flex items-center gap-2 border border-rose-300/30">
              <i class="fa-solid fa-bullhorn text-amber-300"></i>
              <span>Lapor Pungli / Gratifikasi</span>
            </a>

            <a href="/kontak" class="bg-emerald-900/90 hover:bg-emerald-800 border border-emerald-400/40 text-emerald-100 hover:text-white text-xs font-bold px-4 py-2.5 rounded-xl hover:scale-105 transition-all flex items-center gap-2 shadow-xs cursor-pointer" title="Buka Bantuan Bantuan &amp; Layanan Langsung">
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
              </span>
              <i class="fa-solid fa-comments text-accent-yellow text-sm"></i>
              <span>Bantuan</span>
            </a>
          </div>

        </div>
      </div>

      <!-- 6 Service Cards (Terkoneksi Langsung ke Halaman Sistem) -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">

        <!-- Card 1: Legalisir Ijazah Online -->
        <div class="bg-kemenag-900/90 rounded-2xl p-5 border border-emerald-500/40 hover:border-accent-yellow shadow-lg hover:-translate-y-1 transition duration-300 flex flex-col justify-between">
          <div>
            <div class="w-10 h-10 rounded-xl bg-kemenag-800 border border-emerald-500/40 flex items-center justify-center text-accent-yellow text-lg mb-3">
              <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <h3 class="text-base font-bold text-white mb-1.5">Legalisir Ijazah Online</h3>
            <p class="text-xs text-emerald-50 leading-relaxed font-normal">
              Khusus alumni {{ $schoolName }} yang membutuhkan pengesahan fotokopi ijazah dan transkrip nilai untuk keperluan kuliah atau dinas.
            </p>
          </div>
          <div class="pt-4 border-t border-emerald-800/80 mt-4 flex items-center justify-between">
            <span class="text-[11px] text-accent-yellow font-medium flex items-center gap-1">
              <i class="fa-regular fa-clock"></i> Estimasi 1-2 Hari Kerja
            </span>
            <a href="/layanan/legalisir" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3.5 py-1.5 rounded-lg shadow transition flex items-center gap-1">
              <span>Buka Layanan</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
          </div>
        </div>

        <!-- Card 2: Surat Izin Siswa Online -->
        <div class="bg-kemenag-900/90 rounded-2xl p-5 border border-emerald-500/40 hover:border-accent-yellow shadow-lg hover:-translate-y-1 transition duration-300 flex flex-col justify-between">
          <div>
            <div class="w-10 h-10 rounded-xl bg-kemenag-800 border border-emerald-500/40 flex items-center justify-center text-accent-yellow text-lg mb-3">
              <i class="fa-solid fa-file-signature"></i>
            </div>
            <h3 class="text-base font-bold text-white mb-1.5">Surat Izin Siswa Online</h3>
            <p class="text-xs text-emerald-50 leading-relaxed font-normal">
              Permohonan izin tidak masuk sekolah karena sakit atau keperluan keluarga secara resmi dengan unggah surat dokter / pernyataan ortu.
            </p>
          </div>
          <div class="pt-4 border-t border-emerald-800/80 mt-4 flex items-center justify-between">
            <span class="text-[11px] text-accent-yellow font-medium flex items-center gap-1">
              <i class="fa-solid fa-bolt"></i> Notifikasi BK &amp; Wali
            </span>
            <a href="/layanan/izin-siswa" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3.5 py-1.5 rounded-lg shadow transition flex items-center gap-1">
              <span>Buat Izin</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
          </div>
        </div>

        <!-- Card 3: Permohonan Mutasi Siswa -->
        <div class="bg-kemenag-900/90 rounded-2xl p-5 border border-emerald-500/40 hover:border-accent-yellow shadow-lg hover:-translate-y-1 transition duration-300 flex flex-col justify-between">
          <div>
            <div class="w-10 h-10 rounded-xl bg-kemenag-800 border border-emerald-500/40 flex items-center justify-center text-accent-yellow text-lg mb-3">
              <i class="fa-solid fa-people-arrows"></i>
            </div>
            <h3 class="text-base font-bold text-white mb-1.5">Permohonan Mutasi Siswa</h3>
            <p class="text-xs text-emerald-50 leading-relaxed font-normal">
              Pelayanan penerbitan surat rekomendasi pindah madrasah masuk maupun keluar daerah sesuai prosedur EMIS 4.0 Kementerian Agama.
            </p>
          </div>
          <div class="pt-4 border-t border-emerald-800/80 mt-4 flex items-center justify-between">
            <span class="text-[11px] text-accent-yellow font-medium flex items-center gap-1">
              <i class="fa-solid fa-network-wired"></i> Integrasi EMIS 4.0
            </span>
            <a href="/layanan/mutasi" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3.5 py-1.5 rounded-lg shadow transition flex items-center gap-1">
              <span>Ajukan Mutasi</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
          </div>
        </div>

        <!-- Card 4: Tracking Status Berkas & Tiket -->
        <div class="bg-kemenag-900/90 rounded-2xl p-5 border border-emerald-500/40 hover:border-accent-yellow shadow-lg hover:-translate-y-1 transition duration-300 flex flex-col justify-between">
          <div>
            <div class="w-10 h-10 rounded-xl bg-kemenag-800 border border-emerald-500/40 flex items-center justify-center text-accent-yellow text-lg mb-3">
              <i class="fa-solid fa-magnifying-glass-location"></i>
            </div>
            <h3 class="text-base font-bold text-white mb-1.5">Lacak Status Berkas (Tracking)</h3>
            <p class="text-xs text-emerald-50 leading-relaxed font-normal">
              Pantau progres disposisi surat permohonan, legalisir, atau mutasi Anda secara transparan cukup menggunakan nomor tiket atau NISN.
            </p>
          </div>
          <div class="pt-4 border-t border-emerald-800/80 mt-4 flex items-center justify-between">
            <span class="text-[11px] text-accent-yellow font-medium flex items-center gap-1">
              <i class="fa-solid fa-timeline"></i> Realtime Status
            </span>
            <a href="/lacak" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3.5 py-1.5 rounded-lg shadow transition flex items-center gap-1">
              <span>Lacak Berkas</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
          </div>
        </div>

        <!-- Card 5: Perpustakaan Digital SLiMS -->
        <div class="bg-kemenag-900/90 rounded-2xl p-5 border border-emerald-500/40 hover:border-accent-yellow shadow-lg hover:-translate-y-1 transition duration-300 flex flex-col justify-between">
          <div>
            <div class="w-10 h-10 rounded-xl bg-kemenag-800 border border-emerald-500/40 flex items-center justify-center text-accent-yellow text-lg mb-3">
              <i class="fa-solid fa-book-bookmark"></i>
            </div>
            <h3 class="text-base font-bold text-white mb-1.5">Perpustakaan &amp; Katalog Digital</h3>
            <p class="text-xs text-emerald-50 leading-relaxed font-normal">
              Jelajahi ribuan koleksi buku referensi, kitab kuning, jurnal sains, serta materi e-book digital madrasah langsung dari perangkat Anda.
            </p>
          </div>
          <div class="pt-4 border-t border-emerald-800/80 mt-4 flex items-center justify-between">
            <span class="text-[11px] text-accent-yellow font-medium flex items-center gap-1">
              <i class="fa-solid fa-book-open-reader"></i> E-Koleksi Aktif
            </span>
            <a href="/perpustakaan" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3.5 py-1.5 rounded-lg shadow transition flex items-center gap-1">
              <span>Katalog Buku</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
          </div>
        </div>

        <!-- Card 6: Portal Pengaduan Publik & Lapor -->
        <div class="bg-kemenag-900/90 rounded-2xl p-5 border border-emerald-500/40 hover:border-accent-yellow shadow-lg hover:-translate-y-1 transition duration-300 flex flex-col justify-between">
          <div>
            <div class="w-10 h-10 rounded-xl bg-kemenag-800 border border-emerald-500/40 flex items-center justify-center text-accent-yellow text-lg mb-3">
              <i class="fa-solid fa-headset"></i>
            </div>
            <h3 class="text-base font-bold text-white mb-1.5">Layanan Pengaduan (Lapor)</h3>
            <p class="text-xs text-emerald-50 leading-relaxed font-normal">
              Sampaikan aspirasi, saran, pengaduan sarana, atau laporan layanan madrasah dengan jaminan kerahasiaan identitas dan tindak lanjut cepat.
            </p>
          </div>
          <div class="pt-4 border-t border-emerald-800/80 mt-4 flex items-center justify-between">
            <span class="text-[11px] text-accent-yellow font-medium flex items-center gap-1">
              <i class="fa-solid fa-user-shield"></i> Aman &amp; Terpercaya
            </span>
            <a href="/layanan/lapor" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3.5 py-1.5 rounded-lg shadow transition flex items-center gap-1">
              <span>Sampaikan Laporan</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
          </div>
        </div>

        <!-- Card 7: Presensi Siswa Mandiri (RFID & QR) -->
        <div class="bg-kemenag-900/90 rounded-2xl p-5 border-2 border-emerald-400 hover:border-accent-yellow shadow-lg hover:-translate-y-1 transition duration-300 flex flex-col justify-between relative overflow-hidden">
          <div class="absolute -right-6 -top-6 w-20 h-20 bg-emerald-500/20 rounded-full blur-xl pointer-events-none"></div>
          <div>
            <div class="flex items-center justify-between mb-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-800 border border-emerald-400/60 flex items-center justify-center text-accent-yellow text-lg">
                <i class="fa-solid fa-id-card-clip"></i>
              </div>
              <span class="bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 text-[10px] font-black px-2 py-0.5 rounded-full shadow-xs uppercase tracking-wider">
                BARU • KIOS RFID
              </span>
            </div>
            <h3 class="text-base font-bold text-white mb-1.5">Presensi Siswa Mandiri</h3>
            <p class="text-xs text-emerald-50 leading-relaxed font-normal">
              Absensi kehadiran mandiri santri di gerbang madrasah cukup tempelkan Kartu Pelajar (RFID / Barcode / QR) pada mesin kios layar sentuh.
            </p>
          </div>
          <div class="pt-4 border-t border-emerald-800/80 mt-4 flex items-center justify-between">
            <span class="text-[11px] text-accent-yellow font-medium flex items-center gap-1">
              <i class="fa-solid fa-wifi"></i> Kios Tap 0.2 Detik
            </span>
            <a href="/kios-presensi" class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-xs font-bold px-3.5 py-1.5 rounded-lg shadow transition flex items-center gap-1">
              <span>Buka Kios</span> <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
            </a>
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- ================= PENDIDIK & TENAGA KEPENDIDIKAN (TEASER RINGKAS KE GURU.PHP) ================= -->
  <section class="py-7 lg:py-8 bg-slate-50 border-b border-slate-200">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="bg-gradient-to-r from-emerald-900 via-kemenag-900 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg border border-emerald-500/30 flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-accent-gold/15 rounded-full blur-2xl pointer-events-none"></div>

        <div class="flex items-center gap-4 sm:gap-5 text-center md:text-left flex-col md:flex-row">
          <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-white/10 text-accent-yellow border border-white/20 flex items-center justify-center text-2xl sm:text-3xl shrink-0 shadow-inner">
            <i class="fa-solid fa-chalkboard-user"></i>
          </div>
          <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[10px] font-extrabold uppercase mb-1">
              SDM Unggul &amp; Bersertifikasi
            </div>
            <h3 class="text-base sm:text-xl font-extrabold text-white tracking-tight">
              Direktori Guru &amp; Tenaga Kependidikan
            </h3>
            <p class="text-xs sm:text-sm text-emerald-100/90 mt-0.5 max-w-2xl">
              Pendidik berkompeten, ramah, dan berintegritas tinggi membina capaian akademik, riset sains, dan akhlakul karimah santri.
            </p>
          </div>
        </div>

        <div class="shrink-0 w-full md:w-auto text-center">
          <a href="/direktori-gtk" class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-accent-gold via-amber-400 to-accent-gold hover:from-amber-400 hover:to-amber-500 text-slate-950 font-extrabold px-5 py-3 rounded-xl shadow-lg hover:scale-105 transition-all text-xs sm:text-sm w-full md:w-auto">
            <span>Direktori Lengkap Dewan Guru</span>
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= PEMINATAN & KURIKULUM MERDEKA ================= -->
  <section id="akademik" class="py-7 lg:py-8 bg-white border-b border-slate-200">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="text-center max-w-2xl mx-auto mb-5">
        <span class="text-xs font-bold text-kemenag-700 uppercase tracking-widest">Pilihan Konsentrasi Belajar</span>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Peminatan &amp; Kurikulum Merdeka</h2>
        <p class="text-slate-600 text-xs sm:text-sm mt-1">Memberikan ruang eksplorasi bakat, minat, dan proyek penguatan profil pelajar rahmatan lil 'alamin.</p>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">

        <!-- Track 1: MIPA Unggulan -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 hover:border-emerald-500 shadow-sm hover:shadow-md transition flex flex-col justify-between">
          <div>
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg mb-3">
              <i class="fa-solid fa-atom"></i>
            </div>
            <span class="text-[10px] font-bold uppercase text-emerald-700 tracking-wider">Sains &amp; Matematika</span>
            <h3 class="text-base font-bold text-slate-900 mt-0.5 mb-2">MIPA Unggulan</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Fokus pada pendalaman biologi, kimia, fisika, dan kalkulus terapan serta persiapan intensif ajang Olimpiade Sains (OSN/KSM).
            </p>
          </div>
          <div class="pt-4 border-t border-slate-100 mt-4 text-[11px] text-slate-700 space-y-1">
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-kemenag-600"></i> Lab IPA Komprehensif</span>
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-kemenag-600"></i> Bimbingan Tembus Kedokteran/PTN</span>
          </div>
        </div>

        <!-- Track 2: IPS Terapan -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 hover:border-amber-500 shadow-sm hover:shadow-md transition flex flex-col justify-between">
          <div>
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg mb-3">
              <i class="fa-solid fa-chart-line"></i>
            </div>
            <span class="text-[10px] font-bold uppercase text-amber-700 tracking-wider">Sosial &amp; Humaniora</span>
            <h3 class="text-base font-bold text-slate-900 mt-0.5 mb-2">IPS Terapan</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Penguasaan ekonomi syariah, sosiologi, geografi spasial, literasi finansial, serta simulasi debat dan kepemimpinan publik.
            </p>
          </div>
          <div class="pt-4 border-t border-slate-100 mt-4 text-[11px] text-slate-700 space-y-1">
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-amber-600"></i> Inkubator Wirausaha Muda</span>
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-amber-600"></i> Diplomasi &amp; Public Speaking</span>
          </div>
        </div>

        <!-- Track 3: MAK Keagamaan -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 hover:border-emerald-600 shadow-sm hover:shadow-md transition flex flex-col justify-between">
          <div>
            <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-lg mb-3">
              <i class="fa-solid fa-book-quran"></i>
            </div>
            <span class="text-[10px] font-bold uppercase text-teal-700 tracking-wider">Studi Islam &amp; Turats</span>
            <h3 class="text-base font-bold text-slate-900 mt-0.5 mb-2">Keagamaan (MAK)</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Kajian mendalam kitab kuning, tafsir, hadits, ushul fiqih, bahasa Arab aktif, serta program talaqqi sanad Tahfidzul Qur'an.
            </p>
          </div>
          <div class="pt-4 border-t border-slate-100 mt-4 text-[11px] text-slate-700 space-y-1">
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-teal-600"></i> Jalur Beasiswa Timur Tengah</span>
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-teal-600"></i> Bimbingan Masuk PTKIN/UIN</span>
          </div>
        </div>

        <!-- Track 4: Madrasah Sains dan Teknologi -->
        <div class="bg-white rounded-2xl p-5 border-2 border-emerald-500/60 shadow-sm hover:shadow-md transition flex flex-col justify-between bg-gradient-to-b from-emerald-50/40 to-white">
          <div>
            <div class="w-10 h-10 rounded-xl bg-emerald-700 text-accent-yellow flex items-center justify-center text-lg mb-3">
              <i class="fa-solid fa-microchip"></i>
            </div>
            <span class="text-[10px] font-bold uppercase text-emerald-800 tracking-wider">Inovasi Terdepan</span>
            <h3 class="text-base font-bold text-slate-900 mt-0.5 mb-2">Madrasah Sains &amp; Teknologi</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Pusat pengembangan robotika cerdas, Internet of Things (IoT), kecerdasan buatan terapan, dan riset ilmiah remaja (MYRES Kemenag RI).
            </p>
          </div>
          <div class="pt-4 border-t border-slate-100 mt-4 text-[11px] text-slate-700 space-y-1">
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-emerald-700"></i> Laboratorium Robotik &amp; IoT</span>
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-emerald-700"></i> Mentoring Riset Lahan Basah</span>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ================= SARANA & PRASARANA ================= -->
  <section id="fasilitas" class="py-7 lg:py-8 bg-white border-b border-slate-200">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="text-center max-w-2xl mx-auto mb-5">
        <span class="text-xs font-bold text-kemenag-700 uppercase tracking-widest">Lingkungan Belajar Asri</span>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Sarana &amp; Prasarana Madrasah</h2>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 hover:border-kemenag-600 transition">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-kemenag-700 flex items-center justify-center text-lg mb-3">
            <i class="fa-solid fa-flask-vial"></i>
          </div>
          <h3 class="font-bold text-slate-900 text-base mb-1">Laboratorium Sains Terpadu</h3>
          <p class="text-xs text-slate-600 leading-relaxed">Laboratorium fisika, kimia, dan biologi berstandar riset dengan mikroskop digital dan reagen lengkap.</p>
        </div>

        <a href="/perpustakaan" class="bg-slate-50 p-5 rounded-2xl border border-slate-200 hover:border-kemenag-600 transition block group">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-kemenag-700 flex items-center justify-center text-lg mb-3 group-hover:bg-kemenag-700 group-hover:text-white transition">
            <i class="fa-solid fa-book-bookmark"></i>
          </div>
          <h3 class="font-bold text-slate-900 text-base mb-1 group-hover:text-kemenag-700 transition flex items-center justify-between">
            <span>Perpustakaan Digital</span>
            <i class="fa-solid fa-arrow-up-right-from-square text-xs text-slate-400"></i>
          </h3>
          <p class="text-xs text-slate-600 leading-relaxed">Akses ke ribuan e-book, jurnal sains, literatur turats Islam, serta ruang baca ber-AC yang nyaman.</p>
        </a>

        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 hover:border-kemenag-600 transition">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-kemenag-700 flex items-center justify-center text-lg mb-3">
            <i class="fa-solid fa-mosque"></i>
          </div>
          <h3 class="font-bold text-slate-900 text-base mb-1">Masjid Madrasah</h3>
          <p class="text-xs text-slate-600 leading-relaxed">Pusat pembiasaan sholat berjamaah Dzuhur/Ashar, halaqah Tahfidz, dan kajian kitab kuning santri.</p>
        </div>

        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 hover:border-kemenag-600 transition">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-kemenag-700 flex items-center justify-center text-lg mb-3">
            <i class="fa-solid fa-network-wired"></i>
          </div>
          <h3 class="font-bold text-slate-900 text-base mb-1">Lab Komputer &amp; Multimedia</h3>
          <p class="text-xs text-slate-600 leading-relaxed">Dilengkapi puluhan PC modern terkoneksi fiber-optic berkecepatan tinggi untuk CBT, coding, dan riset.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= WARTA & BERITA TERKINI ================= -->
  <section id="berita" class="py-7 lg:py-8 bg-slate-50 border-b border-slate-200">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-5 gap-3">
        <div>
          <span class="text-xs font-bold text-kemenag-700 uppercase tracking-widest">Kabar Madrasah</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Warta &amp; Agenda Terkini</h2>
        </div>
        <a href="/berita" class="text-xs font-bold text-kemenag-700 hover:text-kemenag-800 flex items-center gap-1">
          Semua Berita <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>

      <div class="grid md:grid-cols-3 gap-5">
        @forelse ($news as $article)
          <article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-200 hover:border-kemenag-500 hover:shadow-md transition flex flex-col justify-between">
            <div>
              @if ($photo = $article['photos'][0] ?? null)
                <div class="h-44 overflow-hidden relative">
                  <img src="{{ $photo['url'] }}" alt="{{ $photo['caption'] ?: $article['title'] }}" loading="lazy" class="w-full h-full object-cover hover:scale-105 transition duration-500">
                </div>
              @endif
              <div class="p-5">
                <time datetime="{{ $article['published_at'] }}" class="text-[11px] text-slate-500">
                  <i class="fa-regular fa-calendar text-kemenag-600" aria-hidden="true"></i>
                  {{ \Illuminate\Support\Carbon::parse($article['published_at'])->locale('id')->translatedFormat('j M Y') }}
                </time>
                <h3 class="font-bold text-slate-900 text-base leading-snug">{{ $article['title'] }}</h3>
                <p class="text-xs text-slate-600 mt-2 line-clamp-2">{{ $article['excerpt'] }}</p>
              </div>
            </div>
            <div class="px-5 pb-5">
              <a href="{{ route('core.news.public.show', $article['slug']) }}" class="text-xs font-bold text-kemenag-700 hover:text-kemenag-800 flex items-center gap-1">
                Baca Selengkapnya <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
              </a>
            </div>
          </article>
        @empty
                    <article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-200 hover:border-kemenag-500 hover:shadow-md transition flex flex-col justify-between">
              <div>
                <div class="h-44 overflow-hidden relative">
                  <img src="/madrasah-theme/images/award.webp" alt="Olimpiade Bahasa Arab (OBA) ke-9 Tingkat Nasional" class="w-full h-full object-cover hover:scale-105 transition duration-500">
                  <span class="absolute top-3 left-3 bg-kemenag-700 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase">Keagamaan &amp; Tahfidz</span>
                </div>
                <div class="p-5">
                  <div class="flex items-center gap-2 text-[11px] text-slate-500 mb-1.5">
                    <i class="fa-regular fa-calendar text-kemenag-600"></i> 13 Nov 2026                    <span class="text-slate-300">•</span>
                    <span><i class="fa-solid fa-user-pen text-slate-400"></i> Humas Madrasah</span>
                  </div>
                  <h3 class="font-bold text-slate-900 text-base hover:text-kemenag-700 transition leading-snug">
                    Olimpiade Bahasa Arab (OBA) ke-9 Tingkat Nasional                  </h3>
                  <p class="text-xs text-slate-600 mt-2 line-clamp-2">
                    Salah satu siswi terbaik kita, Tim Bahasa Arab, berhasil meraih Medali Emas pada ajang bergengsi Olimpiade Bahasa Arab (OBA) ke-9 Tingkat Nasional yang diselenggarakan oleh Forum Musyawarah Guru Mata Pelajaran (F-MGMP) Bahasa Arab se-Indonesia.                  </p>
                </div>
              </div>
              <div class="px-5 pb-5 pt-0">
                <button type="button" data-open-dialog="beritaModal" data-article="{&quot;category&quot;: &quot;Keagamaan &amp; Tahfidz&quot;, &quot;title&quot;: &quot;Olimpiade Bahasa Arab (OBA) ke-9 Tingkat Nasional&quot;, &quot;date&quot;: &quot;2026-11-13&quot;, &quot;author&quot;: &quot;Humas Madrasah&quot;, &quot;image&quot;: &quot;/madrasah-theme/images/award.webp&quot;, &quot;text&quot;: &quot;Alhamdulillah, rasa syukur dan bangga yang tak terhingga menyelimuti keluarga besar Madrasah. Salah satu siswi terbaik kita, Tim Bahasa Arab, berhasil meraih Medali Emas pada ajang bergengsi Olimpiade Bahasa Arab (OBA) ke-9 Tingkat Nasional yang diselenggarakan oleh Forum Musyawarah Guru Mata Pelajaran (F-MGMP) Bahasa Arab se-Indonesia.\r\n\r\nPencapaian gemilang di tingkat nasional ini merupakan buah dari kerja keras, ketekunan, dan proses seleksi berjenjang yang panjang:\r\n\r\n* Seleksi Tingkat Madrasah (13 April): Perjalanan dimulai dari seleksi internal secara luring (offline) di Madrasah. Dari seleksi ketat ini, terpilih 6 peserta didik terbaik untuk mewakili madrasah.\r\n* Seleksi Tingkat Kabupaten (16 Mei): Keenam delegasi bertanding secara daring (online) dengan fasilitas penuh dari madrasah menggunakan laboratorium komputer. Madrasah sukses mendominasi dengan menyapu bersih Juara 1 hingga 4, di mana Juara 1, 2, dan 3 berhak melaju ke tingkat provinsi.\r\n* Seleksi Tingkat Provinsi tingkat provinsi (29 Agustus): Bertempat di SMAN 1 setempat, para wakil kita bersaing secara luring melawan peserta terbaik se-tingkat provinsi. Dari ajang ini, terpilih 2 orang perwakilan (1 siswa dan 1 siswi) yang sukses lolos mewakili Provinsi tingkat provinsi ke kancah nasional.\r\n* Babak Grand Final Tingkat Nasional (10–12 November): Setelah melalui masa bimbingan yang intensif, kedua delegasi bertanding di Asrama Haji Pondok Gede, Jakarta. Pada pengumuman puncak, ananda Tim Bahasa Arab sukses mengharumkan nama madrasah dan Provinsi tingkat provinsi dengan mempersembahkan Medali Emas.\r\n\r\nSemoga prestasi luar biasa ini menjadi motivasi bagi seluruh siswa-siswi Madrasah untuk terus mencintai Bahasa Arab, berani bermimpi besar, dan mencetak prestasi gemilang di masa depan. Barakallahu fiik!&quot;}" class="text-xs font-bold text-kemenag-700 hover:text-kemenag-800 flex items-center gap-1">
                  Baca Selengkapnya <i class="fa-solid fa-angle-right text-[10px]"></i>
                </button>
              </div>
            </article>
                    <article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-200 hover:border-kemenag-500 hover:shadow-md transition flex flex-col justify-between">
              <div>
                <div class="h-44 overflow-hidden relative">
                  <img src="/madrasah-theme/images/volunteer.webp" alt="Aksi Bersih Rawa dan Bank Sampah Santri Wujudkan Madrasah Zero-Waste" class="w-full h-full object-cover hover:scale-105 transition duration-500">
                  <span class="absolute top-3 left-3 bg-emerald-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase">Adiwiyata</span>
                </div>
                <div class="p-5">
                  <div class="flex items-center gap-2 text-[11px] text-slate-500 mb-1.5">
                    <i class="fa-regular fa-calendar text-kemenag-600"></i> 14 Sep 2026                    <span class="text-slate-300">•</span>
                    <span><i class="fa-solid fa-user-pen text-slate-400"></i> Humas Madrasah</span>
                  </div>
                  <h3 class="font-bold text-slate-900 text-base hover:text-kemenag-700 transition leading-snug">
                    Aksi Bersih Rawa dan Bank Sampah Santri Wujudkan Madrasah Zero-Waste                  </h3>
                  <p class="text-xs text-slate-600 mt-2 line-clamp-2">
                    Komitmen menjaga kelestarian ekosistem lokal sekitar madrasah melalui pemilahan sampah organik dan kompos madrasah.                  </p>
                </div>
              </div>
              <div class="px-5 pb-5 pt-0">
                <button type="button" data-open-dialog="beritaModal" data-article="{&quot;category&quot;: &quot;Adiwiyata&quot;, &quot;title&quot;: &quot;Aksi Bersih Rawa dan Bank Sampah Santri Wujudkan Madrasah Zero-Waste&quot;, &quot;date&quot;: &quot;2026-09-14&quot;, &quot;author&quot;: &quot;Humas Madrasah&quot;, &quot;image&quot;: &quot;/madrasah-theme/images/volunteer.webp&quot;, &quot;text&quot;: &quot;Sebagai madrasah berwawasan adiwiyata mandiri, kader lingkungan madrasah bersama dewan guru dan OSIM menyelenggarakan aksi peduli lingkungan air dan pemilahan limbah plastik untuk disetorkan ke bank sampah madrasah.&quot;}" class="text-xs font-bold text-kemenag-700 hover:text-kemenag-800 flex items-center gap-1">
                  Baca Selengkapnya <i class="fa-solid fa-angle-right text-[10px]"></i>
                </button>
              </div>
            </article>
                    <article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-200 hover:border-kemenag-500 hover:shadow-md transition flex flex-col justify-between">
              <div>
                <div class="h-44 overflow-hidden relative">
                  <img src="/madrasah-theme/images/campus.webp" alt="Khatmil Qur'an &amp; Uji Publik Tahfidz Juz 30 Siswa Kelas X dan XI" class="w-full h-full object-cover hover:scale-105 transition duration-500">
                  <span class="absolute top-3 left-3 bg-amber-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase">Keagamaan</span>
                </div>
                <div class="p-5">
                  <div class="flex items-center gap-2 text-[11px] text-slate-500 mb-1.5">
                    <i class="fa-regular fa-calendar text-kemenag-600"></i> 14 Sep 2026                    <span class="text-slate-300">•</span>
                    <span><i class="fa-solid fa-user-pen text-slate-400"></i> Humas Madrasah</span>
                  </div>
                  <h3 class="font-bold text-slate-900 text-base hover:text-kemenag-700 transition leading-snug">
                    Khatmil Qur'an &amp; Uji Publik Tahfidz Juz 30 Siswa Kelas X dan XI                  </h3>
                  <p class="text-xs text-slate-600 mt-2 line-clamp-2">
                    Sebanyak 64 siswa berhasil menuntaskan tasmi' bil-ghaib di hadapan dewan asatidz dan para orang tua santri.                  </p>
                </div>
              </div>
              <div class="px-5 pb-5 pt-0">
                <button type="button" data-open-dialog="beritaModal" data-article="{&quot;category&quot;: &quot;Keagamaan&quot;, &quot;title&quot;: &quot;Khatmil Qur&#x27;an &amp; Uji Publik Tahfidz Juz 30 Siswa Kelas X dan XI&quot;, &quot;date&quot;: &quot;2026-09-14&quot;, &quot;author&quot;: &quot;Humas Madrasah&quot;, &quot;image&quot;: &quot;/madrasah-theme/images/campus.webp&quot;, &quot;text&quot;: &quot;Kegiatan rutin uji publik tahfidz Al-Qur&#x27;an juz 30 dan juz pilihan berlangsung khidmat di Masjid Kampus Madrasah. Para siswa memperdengarkan hafalan secara langsung tanpa teks di hadapan para penguji berkompeten dan wali santri.&quot;}" class="text-xs font-bold text-kemenag-700 hover:text-kemenag-800 flex items-center gap-1">
                  Baca Selengkapnya <i class="fa-solid fa-angle-right text-[10px]"></i>
                </button>
              </div>
            </article>
                    <article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-200 hover:border-kemenag-500 hover:shadow-md transition flex flex-col justify-between">
              <div>
                <div class="h-44 overflow-hidden relative">
                  <img src="/madrasah-theme/images/robotics.webp" alt="Tim Riset Madrasah Lolos Finalis Proposal MYRES Kemenag RI" class="w-full h-full object-cover hover:scale-105 transition duration-500">
                  <span class="absolute top-3 left-3 bg-blue-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase">Prestasi Sains</span>
                </div>
                <div class="p-5">
                  <div class="flex items-center gap-2 text-[11px] text-slate-500 mb-1.5">
                    <i class="fa-regular fa-calendar text-kemenag-600"></i> 14 Sep 2026                    <span class="text-slate-300">•</span>
                    <span><i class="fa-solid fa-user-pen text-slate-400"></i> Humas Madrasah</span>
                  </div>
                  <h3 class="font-bold text-slate-900 text-base hover:text-kemenag-700 transition leading-snug">
                    Tim Riset Madrasah Lolos Finalis Proposal MYRES Kemenag RI                  </h3>
                  <p class="text-xs text-slate-600 mt-2 line-clamp-2">
                    Karya ilmiah pemanfaatan eceng gondok rawa setempat sukses mengantarkan siswa melaju ke babak penjurian nasional.                  </p>
                </div>
              </div>
              <div class="px-5 pb-5 pt-0">
                <button type="button" data-open-dialog="beritaModal" data-article="{&quot;category&quot;: &quot;Prestasi Sains&quot;, &quot;title&quot;: &quot;Tim Riset Madrasah Lolos Finalis Proposal MYRES Kemenag RI&quot;, &quot;date&quot;: &quot;2026-09-14&quot;, &quot;author&quot;: &quot;Humas Madrasah&quot;, &quot;image&quot;: &quot;/madrasah-theme/images/robotics.webp&quot;, &quot;text&quot;: &quot;Tim riset siswa madrasah berhasil menembus babak finalis Madrasah Young Researchers Super Camp (MYRES) yang diselenggarakan oleh Direktorat KSKK Madrasah Kemenag RI. Penelitian ini mengangkat inovasi bio-komposit ramah lingkungan dari vegetasi rawa lokal setempat.&quot;}" class="text-xs font-bold text-kemenag-700 hover:text-kemenag-800 flex items-center gap-1">
                  Baca Selengkapnya <i class="fa-solid fa-angle-right text-[10px]"></i>
                </button>
              </div>
            </article>
        @endforelse
      </div>
    </div>
  </section>

  <!-- ================= PRESTASI SISWA MADRASAH ================= -->
  <section id="prestasi" class="py-7 lg:py-8 bg-white border-b border-slate-200">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="text-center max-w-2xl mx-auto mb-5">
        <span class="text-xs font-bold text-kemenag-700 uppercase tracking-widest">Tradisi Juara Siswa Banua</span>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Prestasi Gemilang Siswa <span class="text-kemenag-700">Madrasah</span></h2>
        <p class="text-slate-600 text-xs sm:text-sm mt-1">Capaian membanggakan siswa dalam bidang sains riset, seni kaligrafi, tilawah Al-Qur'an, olahraga, dan keagamaan.</p>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                  <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200 hover:border-amber-400 shadow-sm transition flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-3">
                <span class="bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full flex items-center gap-1">
                  <i class="fa-solid fa-medal text-amber-500"></i> Medali Emas                </span>
                <span class="text-[11px] font-bold text-slate-400">2026</span>
              </div>
                            <div class="h-32 rounded-xl overflow-hidden mb-3 border border-slate-200/60 bg-white">
                <img src="/madrasah-theme/images/award.webp" alt="Olimpiade Bahasa Arab (OBA) ke-9 Tingkat Nasional" class="w-full h-full object-cover hover:scale-105 transition duration-500">
              </div>
                            <h3 class="font-bold text-slate-900 text-base">Olimpiade Bahasa Arab (OBA) ke-9 Tingkat Nasional</h3>
              <p class="text-xs text-kemenag-700 font-semibold mt-1">Nasional</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-200/80 text-[11px] text-slate-600">
              Peraih: <strong class="text-slate-800">Tim Bahasa Arab</strong>
            </div>
          </div>
                  <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200 hover:border-amber-400 shadow-sm transition flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-3">
                <span class="bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full flex items-center gap-1">
                  <i class="fa-solid fa-medal text-amber-500"></i> Juara 1                </span>
                <span class="text-[11px] font-bold text-slate-400">2026</span>
              </div>
                            <h3 class="font-bold text-slate-900 text-base">Kaligrafi Dekorasi Islam</h3>
              <p class="text-xs text-kemenag-700 font-semibold mt-1">Porseni Madrasah se-Kalsel</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-200/80 text-[11px] text-slate-600">
              Peraih: <strong class="text-slate-800">Tim Riset</strong>
            </div>
          </div>
                  <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200 hover:border-amber-400 shadow-sm transition flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-3">
                <span class="bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full flex items-center gap-1">
                  <i class="fa-solid fa-medal text-amber-500"></i> Juara 2                </span>
                <span class="text-[11px] font-bold text-slate-400">2026</span>
              </div>
                            <h3 class="font-bold text-slate-900 text-base">Musabaqah Hifdzil Quran 5 Juz</h3>
              <p class="text-xs text-kemenag-700 font-semibold mt-1">Tingkat Kabupaten / Provinsi</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-200/80 text-[11px] text-slate-600">
              Peraih: <strong class="text-slate-800">Tim Madrasah</strong>
            </div>
          </div>
                  <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200 hover:border-amber-400 shadow-sm transition flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-3">
                <span class="bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full flex items-center gap-1">
                  <i class="fa-solid fa-medal text-amber-500"></i> Finalis Nasional                </span>
                <span class="text-[11px] font-bold text-slate-400">2026</span>
              </div>
                            <h3 class="font-bold text-slate-900 text-base">MYRES Ilmu Keagamaan Islam</h3>
              <p class="text-xs text-kemenag-700 font-semibold mt-1">Nasional Kemenag RI</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-200/80 text-[11px] text-slate-600">
              Peraih: <strong class="text-slate-800">Tim Lingkungan</strong>
            </div>
          </div>
              </div>
    </div>
  </section>

  <!-- ================= GALERI & KEGIATAN MADRASAH ================= -->
  <section id="galeri" class="pt-7 lg:pt-8 pb-4 bg-slate-50">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="text-center max-w-2xl mx-auto mb-5">
        <span class="text-xs font-bold text-kemenag-700 uppercase tracking-widest">Ilustrasi Aktivitas</span>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Galeri &amp; Kegiatan Madrasah</h2>
        <p class="text-slate-600 text-xs sm:text-sm mt-1">Kilas balik suasana belajar, pembiasaan religius, dan kegiatan kesiswaan santri &amp; siswa.</p>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @forelse ($gallery as $album)
          <button type="button" class="group relative rounded-2xl overflow-hidden shadow-sm h-48 sm:h-56 cursor-pointer text-left" data-open-dialog="lightboxModal" aria-haspopup="dialog" data-image="{{ $album['url'] }}" data-caption="{{ $album['caption'] ?: $album['title'] }}">
            <img src="{{ $album['url'] }}" alt="{{ $album['title'] }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
            <span class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
              <span class="text-white text-xs font-semibold line-clamp-1">{{ $album['title'] }}</span>
            </span>
          </button>
        @empty
                  <div class="group relative rounded-2xl overflow-hidden shadow-sm h-48 sm:h-56 cursor-pointer" role="button" tabindex="0" data-open-dialog="lightboxModal" data-image="/madrasah-theme/images/robotics.webp" data-caption="Untuk negeri yang kita cintai selalu ada doa agar Indonesia tetap damai">
            <img src="/madrasah-theme/images/robotics.webp" alt="Untuk negeri yang kita cintai selalu ada doa agar Indonesia tetap damai" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
              <div>
                <span class="text-[10px] font-bold text-emerald-300 block uppercase">Umum</span>
                <span class="text-white text-xs font-semibold line-clamp-1">Untuk negeri yang kita cintai selalu ada doa agar Indonesia tetap damai</span>
              </div>
            </div>
          </div>
                  <div class="group relative rounded-2xl overflow-hidden shadow-sm h-48 sm:h-56 cursor-pointer" role="button" tabindex="0" data-open-dialog="lightboxModal" data-image="/madrasah-theme/images/ceremony.webp" data-caption="OMI  Tahun 2026 Tingkat Provinsi tingkat provinsi">
            <img src="/madrasah-theme/images/ceremony.webp" alt="OMI  Tahun 2026 Tingkat Provinsi tingkat provinsi" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
              <div>
                <span class="text-[10px] font-bold text-emerald-300 block uppercase">Prestasi Sains</span>
                <span class="text-white text-xs font-semibold line-clamp-1">OMI  Tahun 2026 Tingkat Provinsi tingkat provinsi</span>
              </div>
            </div>
          </div>
                  <div class="group relative rounded-2xl overflow-hidden shadow-sm h-48 sm:h-56 cursor-pointer" role="button" tabindex="0" data-open-dialog="lightboxModal" data-image="/madrasah-theme/images/library.webp" data-caption="Assalam Futsal Turnamen ( AFT ) Tahun 2026">
            <img src="/madrasah-theme/images/library.webp" alt="Assalam Futsal Turnamen ( AFT ) Tahun 2026" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
              <div>
                <span class="text-[10px] font-bold text-emerald-300 block uppercase">Olahraga &amp; Prestasi</span>
                <span class="text-white text-xs font-semibold line-clamp-1">Assalam Futsal Turnamen ( AFT ) Tahun 2026</span>
              </div>
            </div>
          </div>
                  <div class="group relative rounded-2xl overflow-hidden shadow-sm h-48 sm:h-56 cursor-pointer" role="button" tabindex="0" data-open-dialog="lightboxModal" data-image="/madrasah-theme/images/ceremony.webp" data-caption="Upacara Bendera Peringatan Hari Santri Nasional">
            <img src="/madrasah-theme/images/ceremony.webp" alt="Upacara Bendera Peringatan Hari Santri Nasional" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
              <div>
                <span class="text-[10px] font-bold text-emerald-300 block uppercase">Upacara</span>
                <span class="text-white text-xs font-semibold line-clamp-1">Upacara Bendera Peringatan Hari Santri Nasional</span>
              </div>
            </div>
          </div>
                  <div class="group relative rounded-2xl overflow-hidden shadow-sm h-48 sm:h-56 cursor-pointer" role="button" tabindex="0" data-open-dialog="lightboxModal" data-image="/madrasah-theme/images/award.webp" data-caption="Upacara Hari Kesaktian Pancasila">
            <img src="/madrasah-theme/images/award.webp" alt="Upacara Hari Kesaktian Pancasila" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
              <div>
                <span class="text-[10px] font-bold text-emerald-300 block uppercase">Upacara &amp; Hari Besar</span>
                <span class="text-white text-xs font-semibold line-clamp-1">Upacara Hari Kesaktian Pancasila</span>
              </div>
            </div>
          </div>
                  <div class="group relative rounded-2xl overflow-hidden shadow-sm h-48 sm:h-56 cursor-pointer" role="button" tabindex="0" data-open-dialog="lightboxModal" data-image="/madrasah-theme/images/sports.webp" data-caption="Musabaqah Tilawatil Qur&#x27;an XIX  Putra Tingkat Kabupaten Balangan Tahun 2026">
            <img src="/madrasah-theme/images/sports.webp" alt="Musabaqah Tilawatil Qur'an XIX  Putra Tingkat Kabupaten Balangan Tahun 2026" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
              <div>
                <span class="text-[10px] font-bold text-emerald-300 block uppercase">Keagamaan &amp; Tahfidz</span>
                <span class="text-white text-xs font-semibold line-clamp-1">Musabaqah Tilawatil Qur'an XIX  Putra Tingkat Kabupaten Balangan Tahun 2026</span>
              </div>
            </div>
          </div>
                  <div class="group relative rounded-2xl overflow-hidden shadow-sm h-48 sm:h-56 cursor-pointer" role="button" tabindex="0" data-open-dialog="lightboxModal" data-image="/madrasah-theme/images/volunteer.webp" data-caption="Juara 1 Olimpiade Madrasah Indonesia OMI Tingkat Provinsi tingkat provinsi">
            <img src="/madrasah-theme/images/volunteer.webp" alt="Juara 1 Olimpiade Madrasah Indonesia OMI Tingkat Provinsi tingkat provinsi" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
              <div>
                <span class="text-[10px] font-bold text-emerald-300 block uppercase">Prestasi Sains</span>
                <span class="text-white text-xs font-semibold line-clamp-1">Juara 1 Olimpiade Madrasah Indonesia OMI Tingkat Provinsi tingkat provinsi</span>
              </div>
            </div>
          </div>
                  <div class="group relative rounded-2xl overflow-hidden shadow-sm h-48 sm:h-56 cursor-pointer" role="button" tabindex="0" data-open-dialog="lightboxModal" data-image="/madrasah-theme/images/science.webp" data-caption="Olimpiade Madrasah Indonesia OMI Tingkat Nasinal">
            <img src="/madrasah-theme/images/science.webp" alt="Olimpiade Madrasah Indonesia OMI Tingkat Nasinal" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
              <div>
                <span class="text-[10px] font-bold text-emerald-300 block uppercase">Prestasi Sains</span>
                <span class="text-white text-xs font-semibold line-clamp-1">Olimpiade Madrasah Indonesia OMI Tingkat Nasinal</span>
              </div>
            </div>
          </div>
        @endforelse
      </div>
    </div>
  </section>

  <!-- ================= FORMULIR & PENDAFTARAN PPDB ================= -->
  <section id="ppdb" class="pt-6 lg:pt-8 pb-10 lg:pb-12 bg-gradient-to-br from-kemenag-950 via-kemenag-900 to-kemenag-950 text-white border-b border-kemenag-800">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="grid lg:grid-cols-12 gap-8 items-center">

        <!-- Info PPDB Left -->
        <div class="lg:col-span-6 space-y-4">
                      <div class="inline-flex items-center gap-2 bg-amber-500 text-slate-950 text-xs font-extrabold px-3.5 py-1.5 rounded-full uppercase tracking-wider shadow-sm">
              <i class="fa-solid fa-graduation-cap"></i> Penerimaan Peserta Didik Baru
            </div>
            <h2 class="text-lg sm:text-xl md:text-2xl lg:text-[22px] xl:text-[25px] font-extrabold font-sans text-white tracking-tight leading-snug">
              Terima Kasih atas Antusiasme &amp; Kepercayaan Menuju {{ $schoolName }}            </h2>
            <p class="text-emerald-100 text-xs sm:text-sm leading-relaxed">
              Lihat jadwal dan jalur pendaftaran, pantau hasil seleksi, atau periksa informasi daftar ulang di portal PPDB.
            </p>

          <!-- Alumni Success Trust Badge -->
          <div class="p-3.5 rounded-2xl bg-kemenag-900/90 border border-emerald-500/40 flex items-center justify-between gap-3 shadow-inner">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-accent-gold text-slate-900 flex items-center justify-center font-bold text-base shrink-0">
                <i class="fa-solid fa-user-graduate"></i>
              </div>
              <div>
                <span class="text-xs font-bold text-white block">Jejaring Alumni Madrasah</span>
                <span class="text-[11px] text-emerald-200">Direktori dan tracer study alumni</span>
              </div>
            </div>
            <a href="/alumni" class="text-accent-yellow hover:text-white text-xs font-bold underline shrink-0">
              Lihat Alumni
            </a>
          </div>

          <div class="space-y-2 text-xs text-emerald-100 pt-1">
            <div class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-accent-yellow"></i> Bebas Biaya Pendaftaran (Gratis)</div>
            <div class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-accent-yellow"></i> Jalur Prestasi Akademik, Non-Akademik &amp; Tahfidz</div>
            <div class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-accent-yellow"></i> Terintegrasi Sistem Database Siswa Akademik</div>
          </div>
        </div>

        <!-- Informasi pendaftaran -->
        <div class="lg:col-span-6">
            <div class="bg-white text-slate-800 p-6 sm:p-7 rounded-3xl shadow-2xl border border-amber-400/40 relative overflow-hidden">
              <div class="flex items-center gap-3.5 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 text-xl font-bold shrink-0 shadow-sm">
                  <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <div>
                  <h3 class="text-base sm:text-lg font-extrabold text-slate-900 leading-snug">Pendaftaran PPDB</h3>
                  <p class="text-xs text-slate-500">Informasi &amp; Layanan Calon Siswa Baru</p>
                </div>
              </div>

              <!-- Banner Notice -->
              <div class="p-3.5 rounded-2xl bg-amber-50/90 border border-amber-200/90 mb-4">
                <div class="flex items-start gap-2.5">
                  <i class="fa-solid fa-circle-info text-amber-600 text-sm mt-0.5 shrink-0"></i>
                  <p class="text-xs text-amber-950 leading-relaxed font-medium">
                    Lihat periode, jalur, kuota, dan status pendaftaran pada <a href="/ppdb" class="font-extrabold underline">portal PPDB</a>.
                  </p>
                </div>
              </div>

              <!-- Quick Action Links -->
              <div class="space-y-2.5">
                <!-- Cek Pengumuman Kelulusan -->
                <a href="/layanan/kelulusan" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition group">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-bold group-hover:scale-105 transition">
                      <i class="fa-solid fa-award"></i>
                    </div>
                    <div>
                      <span class="text-xs font-bold text-slate-900 group-hover:text-emerald-800 block">Pengumuman Kelulusan Seleksi</span>
                      <span class="text-[11px] text-slate-500">Cek status penerimaan berkas &amp; hasil seleksi</span>
                    </div>
                  </div>
                  <i class="fa-solid fa-chevron-right text-xs text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition"></i>
                </a>

                <!-- Portal Login Siswa Terdaftar -->
                <a href="/login" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition group">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-bold group-hover:scale-105 transition">
                      <i class="fa-solid fa-right-to-bracket"></i>
                    </div>
                    <div>
                      <span class="text-xs font-bold text-slate-900 group-hover:text-emerald-800 block">Portal Login Siswa Terdaftar</span>
                      <span class="text-[11px] text-slate-500">Cetak bukti pendaftaran, konfirmasi &amp; daftar ulang</span>
                    </div>
                  </div>
                  <i class="fa-solid fa-chevron-right text-xs text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition"></i>
                </a>

                <!-- Helpdesk Bantuan PPDB -->
                <a href="/kontak" class="flex items-center justify-between p-3 rounded-xl bg-gradient-to-r from-emerald-700 to-emerald-800 hover:from-emerald-800 hover:to-emerald-900 text-white font-bold transition shadow-md shadow-emerald-800/20">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-white/20 text-white flex items-center justify-center text-sm font-bold">
                      <i class="fa-solid fa-comments text-base"></i>
                    </div>
                    <div>
                      <span class="text-xs font-extrabold text-white block">Layanan Konsultasi &amp; Helpdesk PPDB</span>
                      <span class="text-[11px] text-emerald-100">Lihat kanal bantuan pendaftaran</span>
                    </div>
                  </div>
                  <i class="fa-solid fa-arrow-up-right-from-square text-xs text-emerald-200"></i>
                </a>
              </div>

              <!-- Footer Info -->
              <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <span>Informasi Pendaftaran Mendatang:</span>
                <button type="button" data-open-dialog="downloadModal" aria-haspopup="dialog" class="text-kemenag-700 hover:underline font-semibold flex items-center gap-1">
                  <i class="fa-solid fa-file-pdf"></i> Unduh Brosur
                </button>
              </div>
            </div>
                  </div>

      </div>
    </div>
  </section>

  <!-- ================= KONTAK & PETA LOKASI ================= -->
  <section id="kontak" class="py-7 lg:py-8 bg-white border-b border-slate-200">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="grid lg:grid-cols-12 gap-6">

        <div class="lg:col-span-5 space-y-4">
          <span class="text-xs font-bold text-kemenag-700 uppercase tracking-widest">Hubungi Kami</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $schoolName }}</h2>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Kami siap melayani kebutuhan informasi pendidikan putra-putri Anda. Silakan berkunjung pada hari dan jam kerja madrasah.
          </p>

          <div class="space-y-3 text-xs text-slate-700 pt-2">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-emerald-100 text-kemenag-700 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-map-location-dot"></i>
              </div>
              <div>
                <strong class="block text-slate-900 font-bold">Alamat Lengkap:</strong>
                <span>{{ ($profile['address'] ?? null) ?: 'Alamat madrasah belum tersedia' }}</span>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-emerald-100 text-kemenag-700 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-phone"></i>
              </div>
              <div>
                <strong class="block text-slate-900 font-bold">Telepon &amp; PTSP:</strong>
                <span>{{ ($profile['phone'] ?? null) ?: 'Telepon madrasah belum tersedia' }}</span>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-emerald-100 text-kemenag-700 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-clock"></i>
              </div>
              <div>
                <strong class="block text-slate-900 font-bold">Jam Layanan Kantor:</strong>
                <span>Senin - Kamis: 07.30 - 16.00 WITA | Jum'at: 07.30 - 11.30 WITA</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Google Maps setempat -->
        <div class="lg:col-span-7 rounded-3xl overflow-hidden shadow-sm border border-slate-200 h-80 sm:h-96 bg-slate-100 relative group flex flex-col">
          <div class="absolute top-3 left-3 right-3 z-10 flex items-center justify-between pointer-events-none">
            <span class="bg-white/95 backdrop-blur-md px-3 py-1.5 rounded-xl shadow-md text-xs font-bold text-slate-800 border border-slate-200 flex items-center gap-1.5 pointer-events-auto">
              <i class="fa-solid fa-map-pin text-kemenag-600"></i> Lokasi {{ $schoolName }}            </span>
            <a href="/kontak" class="bg-kemenag-700 hover:bg-kemenag-800 text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-md transition flex items-center gap-1.5 pointer-events-auto">
              <span>Informasi Kontak</span>
              <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
            </a>
          </div>
          <img src="/madrasah-theme/images/campus.webp" alt="Ilustrasi lingkungan madrasah, dibuat dengan AI" class="w-full h-full object-cover" loading="lazy">
        </div>

      </div>
    </div>
  </section>

  </main>
@endsection
