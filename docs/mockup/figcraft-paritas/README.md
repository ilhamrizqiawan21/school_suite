# School Suite · Mockup paritas layanan

- [Berkas FigCraft yang dapat diedit](school-suite-paritas.figcraft)
- [Pratinjau interaktif](index.html)
- [Requirements](../../REQUIREMENTS_PARITAS_MAN4_BANJAR.md) dan [TODO untuk Gemini](../../TODO_PARITAS_MAN4_BANJAR.md)

Terdapat 122 artboard, termasuk lima layar dari pemeriksaan REA lanjutan. Buka dokumen di FigCraft dan pilih flow pada mode Present. Di pratinjau HTML, gunakan pemilih artboard atau ikuti tombol yang terhubung. Pilih artboard berlabel Ponsel untuk menilai rancangan 390 piksel. [Peta 48 requirements ke artboard](CAKUPAN.md) menyediakan tautan peninjauan per ID.

| Area | Artboard yang dapat ditinjau | Requirements |
|---|---|---|
| Portal dan sistem visual | Beranda desktop/ponsel, menu layanan desktop/ponsel, informasi sekolah, pusat unduh, login, dan contoh keadaan layanan | UX, PUB |
| PTSP dan tata usaha | Formulir desktop/ponsel, resi, pelacakan, status, antrean, detail pemeriksaan, perbaikan, penerbitan, surat dinas, agenda dan buku tamu | TU, FND |
| Operasi siswa | Surat izin, verifikasi izin, kios RFID/QR, piket, pos jaga, BK dan daftar pekerjaan petugas | OPS |
| Siklus peserta didik | PPDB, mutasi, SKL, verifikasi, legalisir, surat kuasa/SPTJM, alumni dan daftar terkait | LIF |
| Pengaduan dan perpustakaan | Siap Lapor, resi, antrean satgas, pintu masuk katalog dan layanan perpustakaan, serta ruang kerja pustakawan | SERV |
| Proposal dan integrasi | Proposal guru, LPJ, daftar review, serta halaman pengaturan integrasi | INT |

## Alur yang diprototipekan

1. Beranda → E-Layanan → PTSP Online → kirim → resi → status.
2. Masuk → ruang kerja → antrean PTSP → tinjau → minta perbaikan atau terbitkan hasil.
3. Beranda ponsel → menu → pilih layanan. Flow PTSP ponsel juga tersedia langsung dari pemilih artboard.

## Batas fidelity

Target 90% dipakai sebagai arah kedalaman desain. Persentase kesesuaian belum diukur. Dokumen mencakup daftar dan detail petugas per layanan, layar pendukung, keadaan umum, serta variasi ponsel untuk jalur prioritas. Sebagian detail memakai komponen formulir bersama. Akurasi aturan operasional perlu divalidasi dengan SOP klien.

Data dan foto adalah ilustrasi. Formulir, pemindaian, pelacakan, cetak, login, dan integrasi disimulasikan. Tombol yang memiliki tujuan memperagakan navigasi antarartboard. Area input berfungsi sebagai contoh visual. Identitas visual menggunakan School Suite dengan inspirasi hierarki dan pengelompokan layanan dari [bukti REA](../../RENCANA_PARITAS_MAN4_BANJAR.md).

Arsip mengikuti format dokumen FigCraft yang sudah dipakai di repo. Pemeriksaan struktur mencakup integritas arsip, parent layer, posisi dasar artboard, konsistensi tujuan navigasi, dan jumlah artboard pada pratinjau HTML. Render dan interaksi di aplikasi FigCraft belum diperiksa langsung.

Untuk membangun ulang artefak dari sumber, jalankan `python docs/mockup/figcraft-paritas/build_mockup.py` dari root repositori. Generator memakai foto ilustratif dan format dokumen dari mockup Calm Academic yang sudah tersedia.
