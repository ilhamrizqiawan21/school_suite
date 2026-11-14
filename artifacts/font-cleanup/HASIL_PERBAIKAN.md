# Hasil perapian tipografi — 14 November 2026

## Perubahan

- Font UI tetap system sans-serif. Serif dibatasi pada narasi/kutipan publik dan identitas tertentu; dokumen tetap memakai DejaVu.
- Token ukuran: isi 16 px, kontrol dan tabel 14 px, metadata 13 px, caption 12 px. Kontrol mobile tetap 16 px. Judul halaman internal memakai satu skala responsif 28–32 px.
- Ukuran kecil pada stylesheet dan utility Vue dipindahkan ke token, termasuk guru, jadwal, pengguna, bank soal, pembelajaran, dashboard, dan rapor.
- Ketebalan dirapikan melalui token regular/medium/semibold/bold. Timer ujian tetap mendapat penekanan tersendiri.
- Warna teks hapus/PDF/arsip, tombol dengan teks putih, navigasi aktif pada tema gelap, dan informasi login diperbaiki. Kartu profil memakai permukaan terang; sambutan pada permukaan gelap memakai teks putih.
- Label PDF pada ikon diperbesar. Warna dasar ikon file tetap cukup gelap pada kedua tema.
- Sebanyak 55 aturan CSS dashboard lama yang tidak lagi dipakai telah dibuang.
- Papan jadwal desktop memakai tiga kolom agar font yang lebih besar tetap terbaca dan halaman tidak melebar.
- Rapor PDF menggunakan isi 10 pt, header tabel/deskripsi 9 pt, footer 8 pt, dan checksum 7 pt. Margin bawah menyediakan ruang footer.
- Pembesaran soal CBT sampai 200% mencakup prompt, jawaban, dan pilihan menjodohkan. Navigasi tidak mengambang di atas jawaban saat teks diperbesar.
- Pinch zoom WebView Android diaktifkan; tombol zoom overlay tetap disembunyikan.
- Formatter tanggal/status yang dibutuhkan daftar rapor dilengkapi agar halaman dapat dibuild.

## Perbandingan source

| Ukuran inventaris | Sebelum | Sesudah |
|---|---:|---:|
| Deklarasi font-size layar yang langsung memakai token | 26 | 1373 |
| Deklarasi ukuran layar literal di bawah perkiraan 12 px | 443 | 1 |
| Nilai ukuran berbeda secara tekstual | 190 | 109 |

Hitungan source mencakup aturan media dan override; bukan hitungan ukuran yang tampil atau skor aksesibilitas. Aturan cetak dipisahkan. Utility font juga diperiksa.

## Verifikasi

- `npm run typecheck`: lolos.
- `npm run build`: lolos. Peringatan eksternalisasi modul buffer dari dependency docx masih muncul; build berhasil.
- `php artisan test --compact`: 451 tes lolos, 2 dilewati, 4.209 assertion.
- 76 skenario browser pada 19 halaman/peran, lebar 390/1440 CSS px dan kedua preferensi tema. Halaman publik/login tetap terang sesuai perilaku aplikasi.
- Tidak ada error JavaScript, overflow horizontal halaman, atau text node terlihat di bawah 12 px dalam sampel akhir.
- Tidak ada kandidat kontras rendah pada teks aktif yang dipindai. Kandidat mentah tersisa adalah pagination nonaktif, yang dikecualikan dari temuan teks aktif.
- Uji 320 px dan override jarak teks dilakukan pada sampel mobile selain pratinjau rapor. Tidak ada overflow halaman. Satu kandidat clipping dari input kosong di bank soal masih dicatat dalam data mentah; bukan bukti teks terpotong.
- Prompt mobile pada ukuran 200% berubah dari 16,8 menjadi 33,6 px; prompt desktop dari 21,6 menjadi 43,2 px. Jawaban dan select menjodohkan berubah dari 16 menjadi 32 px. Tampilan Arab dan navigasi diperiksa pada screenshot.
- Rapor demo dirender oleh Dompdf dan kedua halaman A4 diperiksa melalui raster Poppler; tidak terlihat overlap footer, clipping, atau karakter rusak pada contoh.

## Bukti dan batas pengujian

`runtime-results.json`, `source-inventory.json`, dan screenshot tersimpan di direktori ini. Data berasal dari seed SQLite dalam memori; fixture ujian serta daftar rapor ditambahkan untuk memeriksa state spesifik. Browser merender build produksi melalui fixture, bukan akun/data produksi.

Pengukuran teks mengabaikan sr-only, elemen aria-hidden, dan teks di luar batas horizontal viewport. Latar bergambar/gradien tidak dinilai otomatis. Ini belum merupakan sertifikasi WCAG untuk semua state, semua warna branding, atau semua browser/OS.

Toolchain Android yang dirujuk skrip proyek tidak tersedia, sehingga APK belum dibuild dan pinch zoom belum diuji pada perangkat fisik. Contoh PDF tidak menggantikan pemeriksaan semua variasi isi atau hasil cetak printer.
