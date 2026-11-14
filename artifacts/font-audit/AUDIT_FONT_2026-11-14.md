# Audit font School Suite — 14 November 2026

> Tindak lanjut: perbaikan telah diterapkan. Lihat [hasil perapian](../font-cleanup/HASIL_PERBAIKAN.md). Temuan di bawah merekam kondisi sebelum perbaikan.

## Kesimpulan

Keluarga font utama sudah cukup konsisten. Prioritas perbaikan adalah ukuran teks kecil, kontras, dan penerapan token tipografi. Mengganti seluruh font belum diperlukan.

Audit ini tidak mengubah font atau CSS aplikasi. Perubahan dashboard yang sudah ada tetap dipertahankan.

## Cakupan dan metode

- Inventaris 124 file sumber di `resources`, termasuk seluruh 76 halaman Vue. Deklarasi CSS diparsing dari stylesheet, blok style Vue, dan template Blade. Template bawaan Laravel `welcome.blade.php` dikecualikan.
- Pemeriksaan tambahan pada CSS cetak/ekspor soal dan konfigurasi font/zoom Android exam browser.
- Pemeriksaan browser pada 15 halaman/peran, mencakup 12 komponen Vue: beranda publik, login, profil sekolah, berita, PPDB, dashboard admin/guru/siswa, data siswa, guru, jadwal, pengguna, pembelajaran guru/siswa, dan bank soal.
- 60 skenario diminta pada lebar 390 dan 1440 CSS px serta preferensi tema terang/gelap. Halaman publik dan login memang memaksa tema terang; kedua preferensi tidak menghasilkan dua tema berbeda di halaman tersebut.
- Data halaman diambil dari controller Laravel dengan database SQLite dalam memori dan seed demo terisolasi. Browser merender build produksi melalui fixture Inertia; tidak masuk ke akun produksi. Permintaan aset luar diblokir. Gambar yang tidak tersedia dapat memengaruhi penampilan area bergambar, sehingga kontras pada latar bergambar tidak dijadikan kesimpulan.
- Animasi masuk dinonaktifkan pada pengukuran halaman internal agar teks tidak dihitung saat masih transparan. Pengukuran akhir tersimpan di `runtime-results.json`.
- Pemeriksaan reflow 320 px dan override jarak teks dilakukan pada beranda publik, login, data siswa, serta dashboard admin/guru/siswa.

Ini merupakan audit source menyeluruh dengan sampel runtime, bukan sertifikasi WCAG atau pengujian langsung seluruh state aplikasi. Dialog, menu terbuka, pesan error, semua warna branding, render Android, teks Arab/rumus, font aktual lintas OS, PDF tercetak, dan pembesaran browser 200% belum diverifikasi penuh.

## Kondisi dasar

| Bagian | Kondisi |
|---|---|
| Font UI | `ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif` |
| Font display | Georgia / Times New Roman / serif |
| Font kode | System monospace; alias `--font-mono` terhubung ke `--font-code` |
| Font dokumen | DejaVu Sans/Serif untuk PDF; Arial pada layout ekspor soal |
| Body aplikasi | 15 px, line-height 1,65 |
| Konten publik | Umumnya 16 px; beberapa bagian memiliki override lokal |
| Font eksternal | Tidak ditemukan file font WOFF/TTF/OTF di `public`/`resources` maupun deklarasi `@font-face` pada source yang dipindai |
| Kontrol mobile | Umumnya 16 px pada sampel internal; login masih 14,72 px |

System font mengikuti font yang tersedia pada OS. String `font-family` hasil browser tidak membuktikan font fisik mana yang dipakai. Tidak ada dasar dari audit ini untuk menyatakan semua perangkat menampilkan bentuk huruf identik.

## Temuan berurutan menurut prioritas

### P1 — Teks pada profil sekolah menyatu dengan latar

Pada fixture branding hijau, label “Sambutan kepala sekolah” memiliki foreground dan background sama, `#277450`, sehingga kontrasnya **1:1**. Label “Negeri” pada kartu visual profil juga terukur **1:1**. Teks “Belajar. Berkarya. Berbagi.” sekitar **2,34:1**; teks kecil portofolio sekitar **1,05:1**. Screenshot profil memperlihatkan bagian yang sulit dibaca.

Sumber: `resources/js/Pages/Public/School/Profile.vue:114` dan aturan warna publik pada `resources/css/public-portfolio.css`. Aturan warna teks umum perlu membedakan permukaan terang dan permukaan berwarna. Mengubah ukuran atau ketebalan font saja tidak menyelesaikan masalah ini.

### P1 — Warna teks aksi dan status kurang kontras

Contoh hasil computed style pada branding demo hijau:

| Teks/konteks | Tema | Rasio | Acuan |
|---|---|---:|---:|
| Hapus, tombol pengguna/jadwal/pembelajaran | Terang | 3,19:1 | 4,5:1 |
| Ekspor PDF, siswa/jadwal | Terang | 3,88:1 | 4,5:1 |
| Arsipkan, bank soal | Terang | 3,28:1 | 4,5:1 |
| Tambah siswa, putih pada warna primer | Gelap | 2,43:1 | 4,5:1 |
| Catatan layanan di login | Terang | 3,89:1 | 4,5:1 |

Sumber utama: `resources/css/shell.css:51`, `resources/css/shell.css:59`, `resources/css/app.css:223`, `resources/css/app.css:268`, `resources/js/Pages/Academic/Students/Index.vue:725`, dan `resources/js/Pages/Auth/Login.vue:249`.

Warna ikon/aksen sebaiknya tidak otomatis menjadi warna teks. Tombol dengan teks putih perlu memakai token permukaan aksi yang cukup gelap, termasuk saat tema gelap membuat `--primary` lebih terang. Rasio bergantung pada branding; temuan ini belum menguji semua warna sekolah.

### P1 — Metadata dan status operasional terlalu kecil untuk penggunaan harian

Contoh yang benar-benar dirender pada mobile:

| Lokasi | Teks | Ukuran |
|---|---|---:|
| Data guru | Status aktif | 10,88 px |
| Data guru | NIP dan status kepegawaian | 11,52 px |
| Jadwal | Kode mapel | 10,88 px |
| Jadwal | Ruang | 11,36 px |
| Pengguna | Username, peran, status | 11 px |
| Bank soal | Label klasifikasi | 10,88 px |
| Dashboard | Footer | 10 px |

Sumber contoh: `resources/js/Pages/Academic/Teachers/Index.vue:600`, `resources/js/Pages/Academic/Schedules/Index.vue:971`, `resources/js/Pages/Academic/Schedules/Index.vue:975`, serta utility `text-[11px]` di halaman pengguna.

Ukuran kecil bukan otomatis pelanggaran WCAG. Ini merupakan masalah kenyamanan membaca, terutama untuk operator/guru yang membaca banyak metadata berulang. Dahulukan status, identitas, jadwal, label input, dan petunjuk. Footer dapat berprioritas lebih rendah.

Teks “PDF” pada pseudo-element ikon bahkan ditetapkan `.42rem` atau sekitar 6,72 px (`resources/css/app.css:269`). Karena label utama “Ekspor PDF” tersedia, ini merupakan isu kualitas ikon dengan prioritas lebih rendah, bukan kehilangan nama aksi.

### P2 — Token tipografi tersedia, tetapi belum menjadi sumber utama

Inventaris source menemukan:

- 1.656 deklarasi `font-size` untuk layar, dengan 190 nilai berbeda secara tekstual.
- Hanya 26 deklarasi `font-size` yang secara langsung memakai `var(--type-...)`.
- 443 deklarasi ukuran literal di bawah perkiraan 12 px, tersebar pada 58 file.
- Nilai yang sering muncul antara lain `.72rem`, `.78rem`, `.82rem`, `.7rem`, `.76rem`, `.68rem`, `11px`, dan `10px`.

Angka tersebut adalah jumlah deklarasi source, termasuk aturan media, CSS lama, dan aturan yang tertimpa. Bukan jumlah ukuran akhir atau jumlah elemen bermasalah. Perhitungan ukuran relatif memakai asumsi root/parent 16 px; `em` dapat berbeda sesuai parent.

`resources/css/app.css:113` sudah menghubungkan utility Tailwind ke token proyek. Karena itu penggunaan utility tidak otomatis berarti menyimpang. Masalah utamanya adalah banyak aturan lokal yang tetap menentukan angka sendiri.

Aturan `.eyebrow` dan tabel di `resources/css/typography.css` berada di CSS layer, sedangkan banyak aturan lokal tidak ber-layer. Penyeragaman hanya lewat file tersebut dapat kalah oleh aturan lokal. Contoh dashboard juga masih menyimpan CSS panel lama yang tidak lagi ada pada template, sehingga pembersihan harus berdasarkan penggunaan dan computed style.

### P2 — Ketebalan dan hierarki judul belum seragam

Ada 295 deklarasi source `font-weight:800` dan 298 deklarasi `700` pada CSS layar. Ini bukan hitungan elemen runtime, tetapi menunjukkan dominasi teks tebal dalam aturan desain.

Pada mobile, judul utama halaman guru/jadwal/bank soal terukur 24,8 px dengan weight 800; judul dashboard 27,2 px dengan weight 700; judul pengguna 28,8 px dengan weight 700. Nama guru juga 800, sehingga bobot visualnya mendekati judul utama. Sebaliknya, sebagian heading bagian dashboard masih weight 400.

Saran: tetapkan peran yang jelas untuk 400 (isi), 500/600 (label dan aksi), 700 (judul dan penekanan). Weight 800/900 dapat dipertahankan untuk konteks tertentu seperti timer ujian, bukan menjadi default hampir semua label. Nilai 650/720 adalah CSS valid, tetapi sebaiknya diselaraskan dengan skala proyek agar pemetaan font lintas OS lebih mudah diprediksi.

### P2 — Serif belum memiliki batas penggunaan yang jelas

Beranda publik memakai sans-serif sepenuhnya pada teks sampel. Login memakai serif pada kedua judul. Profil memakai serif pada narasi/sambutan, dan PPDB memakai serif pada judul kartu serta langkah proses, sementara judul besarnya sans-serif.

Campuran ini dapat menjadi pilihan desain yang sah. Yang belum jelas adalah aturan penggunaannya. Sebaiknya UI operasional, formulir, menu, dan tabel memakai font UI; serif dibatasi untuk kutipan/narasi publik yang memang disengaja. Font dokumen PDF perlu tetap dipisahkan karena kebutuhan render cetaknya berbeda.

### P2 — Kemampuan membesarkan teks perlu verifikasi lanjutan pada ujian

`exam-browser/app/src/main/java/id/itstore/cbtdn/exambrowser/MainActivity.java:63` menonaktifkan kontrol zoom, dan baris 65 menonaktifkan dukungan zoom WebView. Ini berisiko membatasi pembesaran teks selama ujian. Pengaruh pengaturan aksesibilitas/font Android belum diuji pada perangkat, sehingga belum ditetapkan sebagai kegagalan WCAG yang terkonfirmasi.

Sebelum mengubah pembatasan ujian, uji alternatif pembesaran teks yang tetap sesuai aturan ujian. Jangan mengganti font atau ukuran soal secara global tanpa memeriksa rumus, teks Arab, pilihan jawaban, dan timer.

### P2 — Dokumen cetak memakai ukuran yang perlu dinilai di hasil fisik

Contoh: body rapor PDF `9px` (sekitar 6,75pt), dan checksum `6px` (sekitar 4,5pt) di `resources/views/reports/report-card.blade.php:8` dan `:43`. Ukuran ini perlu diperiksa pada PDF dan cetak A4 skala 100%.

DejaVu bukan masalah utamanya. Rekomendasi awal adalah menggunakan satuan pt yang eksplisit untuk dokumen, lalu menilai ukuran isi dan metadata berdasarkan hasil cetak. Tidak ada klaim kegagalan cetak karena audit ini belum merender atau mencetak semua dokumen.

## Hasil uji adaptasi teks

Enam sampel (beranda publik, login, data siswa, dashboard admin/guru/siswa) tidak menghasilkan overflow horizontal halaman pada 320 px. Override line-height 1,5, letter-spacing .12em, word-spacing .16em, serta paragraph spacing 2em tidak memicu overflow halaman atau kandidat clipping pada kontrol/judul yang dipindai di sampel tersebut.

Hasil ini terbatas: pemeriksaan otomatis tersebut belum membuktikan seluruh teks bebas overlap, clipping vertikal, atau hilangnya fungsi. Pembesaran browser 200% dan semua state interaktif masih perlu pengujian tersendiri.

Halaman jadwal menghasilkan overflow horizontal tingkat dokumen pada desktop 1440 px di kedua tema. Perlu ditelusuri sebagai masalah layout tersendiri; keberadaan tabel dua dimensi tidak otomatis berarti seluruh halaman boleh melebar.

Tidak ada error JavaScript dalam 60 skenario akhir.

## Skala yang disarankan

Ini usulan desain, bukan ukuran minimum yang diwajibkan WCAG.

| Peran teks | Ukuran awal | Weight | Line-height |
|---|---:|---:|---:|
| Isi utama/narasi | 16 px | 400 | 1,55–1,7 |
| Isi tabel operasional | 14 px | 400 | 1,45–1,55 |
| Label input dan tombol | 14 px | 500–600 | 1,4–1,5 |
| Metadata/status | 13 px | 400–600 | 1,45–1,55 |
| Caption nonutama | 12 px | 400–500 | 1,45–1,55 |
| Judul bagian | 18–20 px | 600–700 | 1,3–1,4 |
| Judul halaman internal | 28–32 px | 700 | 1,2–1,3 |

Pertahankan kontrol mobile 16 px dan naikkan kontrol login agar sejalan. Heading publik boleh memiliki skala berbeda. Skala akhir perlu dicoba pada tabel terpadat sebelum diterapkan luas.

## Urutan perbaikan

1. Perbaiki pasangan warna teks/latar pada profil, tombol aksi, status, dan tema gelap.
2. Tetapkan token peran teks, lalu perbaiki guru, jadwal, pengguna, bank soal, serta metadata dashboard.
3. Selaraskan weight, judul halaman, label, dan penggunaan serif. Hapus aturan lama setelah dipastikan tidak dipakai.
4. Uji dialog/error/empty state, browser 200%, semua warna branding, Android, soal Arab/rumus, serta PDF A4.

## Bukti dan batas interpretasi

- `source-inventory.json`: deklarasi, lokasi source, dan hitungan statis.
- `runtime-results.json`: computed style dan kandidat masalah per halaman/peran/tema/ukuran.
- `page-fixtures.json`: data controller dari seed demo terisolasi.
- Screenshot halaman dan override jarak teks berada pada direktori laporan ini.

Kandidat kontras mentah mencakup teks `sr-only`, skip link yang tidak sedang fokus, dan pagination nonaktif. Item tersebut tidak otomatis menjadi kegagalan teks terlihat; tabel temuan di atas memilih contoh aktif yang relevan. Pengukuran latar bergradasi/bergambar dilewati dan belum mencakup seluruh pseudo-element atau efek compositing. Jumlah text node juga dapat mencakup elemen off-canvas; gunakan contoh selector dan screenshot untuk keputusan, bukan menjumlahkan kandidat sebagai skor kepatuhan.

## Acuan

- [WCAG 2.2 — Contrast Minimum](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html): 4,5:1 untuk teks normal; 3:1 untuk teks besar, dengan pengecualian yang berlaku.
- [WCAG 2.2 — Resize Text](https://www.w3.org/WAI/WCAG22/Understanding/resize-text.html): teks dapat dibesarkan hingga 200% tanpa kehilangan isi/fungsi.
- [WCAG 2.2 — Text Spacing](https://www.w3.org/WAI/WCAG22/Understanding/text-spacing.html): konten harus menoleransi pengaturan jarak pengguna; nilainya bukan kewajiban desain default.
- [WCAG 2.2 — Reflow](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html): reflow setara 320 CSS px, dengan pengecualian untuk konten yang memerlukan layout dua dimensi.
