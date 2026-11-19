# TODO paritas MAN 4 Banjar untuk Gemini

**Pelaksana kode: Gemini.** Gunakan [requirements paritas](REQUIREMENTS_PARITAS_MAN4_BANJAR.md) sebagai kontrak penerimaan dan [rencana](RENCANA_PARITAS_MAN4_BANJAR.md) sebagai bukti acuan. Baca pula instruksi proyek di [panduan Gemini](GEMINI.md). Semua kotak masih terbuka karena dokumen ini belum menjalankan implementasi.

## Cara eksekusi

1. Baca aturan repositori, periksa status Git, dan hormati perubahan yang sedang dikerjakan. Inventarisasi rute, model, migrasi, kebijakan, komponen dan tes sebelum mengubah kode. Gunakan kembali fondasi yang sudah sesuai.
2. Untuk setiap ID, catat modul, titik masuk dan peran, dependensi, keputusan K, desain data dan perubahan, serta skenario verifikasi. Validasi ketentuan yang masih terbuka melalui demo, contoh dokumen atau keputusan klien.
3. Kerjakan satu irisan vertikal per cabang atau kelompok perubahan. Sambungkan data, kebijakan akses, rute, UI, audit dan hasil melalui titik masuk normal. Tetapkan urutan migrasi dan rollback bila menyentuh data lama.
4. Uji skenario sukses dan penolakan yang relevan, akses langsung dan lintas peran, retry/duplikasi, tampilan ponsel dan kondisi gagal. Jalankan pemeriksaan format, build serta tes proyek yang relevan. Untuk uji perangkat dan integrasi gunakan staging dan data contoh.
5. Centang tugas setelah kriteria ID terkait terbukti. Catat ID, status (terbuka, dikerjakan, menunggu bukti, lulus), commit/PR, langkah uji, hasil, dan temuan tersisa pada laporan eksekusi. Temuan akses yang belum terselesaikan menghalangi penerimaan alur terkait.

## Urutan dan dependensi

| Paket | Mulai setelah | Hasil |
|---|---|---|
| FND | Inventaris awal | Kontrak data, akses, status, berkas dan audit |
| UX, PUB | FND untuk izin dan konfigurasi konten | Sistem visual dan portal dengan navigasi responsif |
| TU | FND dan komponen dasar UX | Permohonan PTSP berjalan sampai hasil dan pelacakan |
| OPS, LIF, SERV | FND serta pola TU yang relevan | Alur siswa, petugas, alumni, satgas dan perpustakaan |
| INT | Discovery mulai dari awal. Implementasi menunggu kontrak setiap koneksi | Proposal, LPJ dan koneksi sesuai lingkungan klien |
| REL | Berjalan pada setiap paket. Penerimaan final sesudah semua paket terkait teruji | UAT, migrasi, pemulihan, rilis dan serah terima |

## Checklist awal

Gunakan halaman publik MAN 4 Banjar sebagai awal inventaris. Catat fitur di balik login sebagai kebutuhan verifikasi, dan lengkapi lewat demo resmi, materi yang boleh dibagikan, atau akun uji yang diberikan pengelola. Implementasi tahap awal dapat berjalan sambil menunggu bukti tersebut.

- [ ] Perluas inventaris pada setiap temuan REA atau bukti resmi baru. Untuk tiap fitur baru, tambahkan kriteria penerimaan, TODO, tautan desain dan skenario demo.
- [ ] Periksa cakupan setiap peran dan kondisi. Tandai fitur yang sudah terlihat di acuan, diverifikasi di School Suite, dan diterima klien sebagai status terpisah.
- [ ] Nyatakan 100% paritas hanya setelah inventaris publik dan peran lengkap dan seluruh kemampuan lulus uji. Kekurangan bukti tetap tercatat sebagai terbuka.

- [ ] Catat perubahan lokal yang sudah ada dan buat baseline pemeriksaan yang dapat diulang.
- [ ] Ulangi inventaris situs publik dan catat perbedaan varian portal utama dan akademik. Pakai bukti REA untuk menu. Petakan halaman layanan yang belum diuji visual.
- [ ] Buka daftar K-01 sampai K-08 dan catat pemilik keputusan serta kebutuhan bukti.
- [ ] Catat setiap layar, aksi, rute dan state yang akan dibangun atau dipakai ulang untuk setiap ID di bawah.

## Fondasi dan aturan lintas modul

- [ ] **M4-FND-01 — Inventaris dan kesetaraan.** Petakan rute, layar, aksi, peran, status, dokumen, integrasi, kondisi kosong dan kesalahan. Tandai setiap baris sebagai teramati, inferensi, atau menunggu klien. Catat versi bukti REA, URL, dan tanggal pemeriksaan. Buktikan kriteria pada [M4-FND-01](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-fnd-01--inventaris-dan-kesetaraan).
- [ ] **M4-FND-02 — Identitas dan peran.** Petakan admin sekolah, operator, kepala, guru, wali kelas, siswa, wali, alumni, panitia, TU/PTSP, penyambut tamu, piket, satpam, BK, satgas, pustakawan dan pejabat penandatangan. Gunakan kebijakan server, batas sekolah dan cakupan kelas/penugasan. Buktikan kriteria pada [M4-FND-02](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-fnd-02--identitas-dan-peran).
- [ ] **M4-FND-03 — Data induk dan periode.** Gunakan sekolah, siswa, wali, GTK, kelas, enrollment, periode, dan zona waktu yang sudah ada. Bedakan identitas pemohon eksternal dari akun pegawai. Pertahankan histori dan referensi dokumen setelah data induk berubah. Buktikan kriteria pada [M4-FND-03](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-fnd-03--data-induk-dan-periode).
- [ ] **M4-FND-04 — Status dan audit.** Untuk setiap layanan definisikan diagram transisi, actor, prasyarat, alasan, status publik, tindakan koreksi dan histori. Gunakan transaksi dan perlindungan pengiriman ulang/konkurensi pada aksi yang mengubah data. Buktikan kriteria pada [M4-FND-04](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-fnd-04--status-dan-audit).
- [ ] **M4-FND-05 — Berkas dan dokumen.** Simpan lampiran privat. Validasi jenis, ukuran, dan akses. Tentukan template, versi, nomor, penandatangan, pratinjau, pembatalan, arsip, serta bukti digital/fisik per layanan. Tanda tangan gambar dan tanda tangan elektronik resmi mengikuti persetujuan klien masing-masing. Buktikan kriteria pada [M4-FND-05](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-fnd-05--berkas-dan-dokumen).
- [ ] **M4-FND-06 — Tiket dan identitas pemohon.** Gunakan tiket yang sulit ditebak dan otorisasi pemilik sesuai jenis layanan. Untuk hasil sensitif, tetapkan verifikasi identitas sebelum menampilkan detail. Publikasikan hanya status minimum yang disetujui. Buktikan kriteria pada [M4-FND-06](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-fnd-06--tiket-dan-identitas-pemohon).
- [ ] **M4-FND-07 — Notifikasi dan antrean.** Petakan peristiwa, penerima, template, status kirim, percobaan ulang, dan kanal aplikasi/email/WhatsApp bila disepakati. Hindari isi sensitif di notifikasi, log, dan URL. Buktikan kriteria pada [M4-FND-07](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-fnd-07--notifikasi-dan-antrean).
- [ ] **M4-FND-08 — Privasi dan operasi.** Dokumentasikan data publik, privat, dan sangat terbatas per layanan, retensi, koreksi data, ekspor, arsip, sanitasi log, pemulihan database/file, serta pemilik operasional. Buktikan kriteria pada [M4-FND-08](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-fnd-08--privasi-dan-operasi).

## Desain bersama seluruh aplikasi

- [ ] **M4-UX-01 — Sistem visual.** Tentukan warna, tipografi, skala jarak, grid, ikon, radius, bayangan, fokus, dan status. Terapkan identitas sekolah klien serta kepadatan sesuai kebutuhan, dengan inspirasi hero kontras dan panel layanan terang dari hasil REA. Buktikan kriteria pada [M4-UX-01](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-ux-01--sistem-visual).
- [ ] **M4-UX-02 — Navigasi responsif.** Desktop menyediakan panel E-Layanan berkelompok dengan deskripsi singkat. Ponsel menyediakan panel satu kolom dengan akses login dan kategori yang konsisten. Uji keyboard, fokus kembali, Escape, penutupan, gulir, lebar menengah, dan posisi bantuan. Buktikan kriteria pada [M4-UX-02](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-ux-02--navigasi-responsif).
- [ ] **M4-UX-03 — Ruang kerja internal.** Samakan shell, menu berbasis izin, periode aktif, judul, breadcrumb, tindakan utama, daftar, filter, pagination, detail, riwayat, form, modal, unggah dan cetak. Pertahankan kepadatan tabel kerja dan alur modul yang ada. Buktikan kriteria pada [M4-UX-03](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-ux-03--ruang-kerja-internal).
- [ ] **M4-UX-04 — Keadaan UI dan aksesibilitas.** Sediakan label, petunjuk, indikator wajib, error per kolom, fokus terlihat, loading, kosong, gagal, berhasil, retry, konfirmasi, urutan tab dan status dengan teks. Uji zoom, keyboard, kontras, ukuran sentuh dan formulir ponsel. Buktikan kriteria pada [M4-UX-04](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-ux-04--keadaan-ui-dan-aksesibilitas).

## Portal dan konten publik

- [ ] **M4-PUB-01 — Beranda dan identitas.** Header identitas, logo, informasi singkat sekolah, kontak, pengumuman, hero, foto, ringkasan capaian bersumber, pintasan PPDB/PTSP/Siap Lapor/Lacak, footer dan tautan media sosial. Buktikan kriteria pada [M4-PUB-01](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-pub-01--beranda-dan-identitas).
- [ ] **M4-PUB-02 — Katalog E-Layanan.** Daftarkan Persuratan & Tamu, Siswa & Alumni, Ketertiban & Sarana, pusat unduh, Zona Integritas/IKM, perpustakaan, verifikasi siswa/alumni serta akses proposal sesuai peran. Sediakan pencarian judul/sinonim dan keadaan tanpa hasil. Buktikan kriteria pada [M4-PUB-02](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-pub-02--katalog-e-layanan).
- [ ] **M4-PUB-03 — Profil dan direktori.** Sambutan, visi dan misi, profil, direktori GTK, fasilitas, program/peminatan, kesiswaan, prestasi, jadwal/agenda, kontak, jam layanan dan peta. Buktikan kriteria pada [M4-PUB-03](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-pub-03--profil-dan-direktori).
- [ ] **M4-PUB-04 — Media dan publikasi.** Berita daftar/detail, foto/galeri, video, keterangan, kategori, media alternatif, tautan sumber, arsip dan status draft/terbit. Buktikan kriteria pada [M4-PUB-04](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-pub-04--media-dan-publikasi).
- [ ] **M4-PUB-05 — Pusat unduh.** Kelola kategori formulir, kalender, tata tertib, brosur, SOP, dokumen resmi dan versi. Tampilkan judul, format, ukuran dan tautan, serta statistik unduhan jika dipakai. Buktikan kriteria pada [M4-PUB-05](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-pub-05--pusat-unduh).
- [ ] **M4-PUB-06 — Zona Integritas dan IKM.** Maklumat, enam area perubahan, dokumentasi, standar layanan, ringkasan IKM dan indikator publik yang disetujui, formulir penilaian, agregasi, dan kanal aduan. Buktikan kriteria pada [M4-PUB-06](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-pub-06--zona-integritas-dan-ikm).
- [ ] **M4-PUB-07 — Jadwal salat, trafik dan bantuan.** Sumber jadwal sesuai lokasi/zona waktu, tampilan waktu berikutnya dan jadwal lengkap. Definisikan statistik online/harian/bulanan/total, kanal kontak, live chat bila disetujui, jam operasional dan fallback. Buktikan kriteria pada [M4-PUB-07](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-pub-07--jadwal-salat-trafik-dan-bantuan).

## PTSP dan tata usaha

- [ ] **M4-TU-01 — Katalog PTSP dan akun.** Kelola jenis layanan, persyaratan, jam/estimasi, publikasi, registrasi, login/pemulihan akun, serta identitas dan kontak pemohon sesuai SOP. Buktikan kriteria pada [M4-TU-01](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-tu-01--katalog-ptsp-dan-akun).
- [ ] **M4-TU-02 — Pengajuan dan resi.** Form identitas/kontak, jenis layanan, kebutuhan, catatan, lampiran, ringkasan, persetujuan submit dan resi. Tentukan kapan draft dapat diubah dan berkas mana yang wajib. Buktikan kriteria pada [M4-TU-02](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-tu-02--pengajuan-dan-resi).
- [ ] **M4-TU-03 — Antrean petugas dan hasil.** Filter sekolah, layanan, tanggal dan status. Verifikasi, minta perbaikan, tolak beralasan, disposisi, proses, terbitkan, unduh atau catat pengambilan fisik. Sediakan histori petugas dan status pemohon. Buktikan kriteria pada [M4-TU-03](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-tu-03--antrean-petugas-dan-hasil).
- [ ] **M4-TU-04 — Pelacakan terpadu.** Hubungkan PTSP, izin, mutasi dan legalisir. Surat eksternal dan pengaduan perlu titik akses pelacakan sesuai aturan masing-masing. Uji tiket valid, salah, kedaluwarsa, pembatasan permintaan dan status lintas layanan. Buktikan kriteria pada [M4-TU-04](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-tu-04--pelacakan-terpadu).
- [ ] **M4-TU-05 — Surat dinas eksternal.** Form instansi, narahubung, jabatan, kontak, nomor/tanggal/sifat/perihal, ringkasan, PDF utama, lampiran dan pernyataan. Terbitkan tanda terima, agenda, disposisi dan status melalui alur TU. Buktikan kriteria pada [M4-TU-05](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-tu-05--surat-dinas-eksternal).
- [ ] **M4-TU-06 — Buku tamu.** Registrasi pengunjung, kontak sesuai SOP, pegawai tujuan, keperluan dan kategori lainnya, waktu datang/pulang bila diperlukan, riwayat resepsionis dan rekap. Buktikan kriteria pada [M4-TU-06](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-tu-06--buku-tamu).

## Presensi, izin, piket dan BK

- [ ] **M4-OPS-01 — Izin dan dispensasi.** Verifikasi wali/siswa, kategori, tanggal mulai/akhir, alasan, kontak, lampiran dan tanda tangan. Tolak rentang bentrok. Terapkan jam pengajuan lokal, aturan izin terencana, dan token sekali pakai terikat siswa/tujuan dengan validasi dan konsumsi atomik. Buktikan kriteria pada [M4-OPS-01](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-ops-01--izin-dan-dispensasi).
- [ ] **M4-OPS-02 — Verifikasi dan dampak presensi.** Wali kelas/petugas meninjau, mengembalikan atau memutuskan izin. Tampilkan status, bukti, pemberitahuan dan jalur koreksi. Gunakan aturan klien untuk dampaknya pada presensi. Buktikan kriteria pada [M4-OPS-02](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-ops-02--verifikasi-dan-dampak-presensi).
- [ ] **M4-OPS-03 — Kios RFID dan QR.** Daftarkan dan cabut kartu sesuai hak. Dukung scan RFID dan QR yang disepakati, pesan sukses/gagal, batas pemindaian berulang, data minimal di layar, jam/periode, perangkat terotorisasi dan pemulihan gangguan jaringan. Buktikan kriteria pada [M4-OPS-03](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-ops-03--kios-rfid-dan-qr).
- [ ] **M4-OPS-04 — Piket dan pos satpam.** Piket mencari/memindai kartu, mencatat alasan, periode dan persetujuan. Satpam memindai atau memasukkan kode manual sesuai hak dan mencatat keluar/kembali. Tentukan status kedaluwarsa, pembatalan, serah terima dan pengecualian dari SOP. Buktikan kriteria pada [M4-OPS-04](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-ops-04--piket-dan-pos-satpam).
- [ ] **M4-OPS-05 — Disiplin BK.** Pindai QR atau cari siswa sesuai akses, pilih jenis pelanggaran, catat waktu/tempat/kejadian dan tindak lanjut. Batasi eviden, riwayat dan ekspor untuk petugas yang berwenang. Buktikan kriteria pada [M4-OPS-05](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-ops-05--disiplin-bk).

## Siklus siswa dan alumni

- [ ] **M4-LIF-01 — PPDB dan pelayanan kandidat.** Periode/jalur, kuota/syarat, akun kandidat bila dipakai, pendaftaran, unggah, bukti, tahap perbaikan, penilaian/ranking, keputusan, publikasi hasil, daftar ulang, rekap dan konversi siswa sesuai prosedur. Buktikan kriteria pada [M4-LIF-01](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-lif-01--ppdb-dan-pelayanan-kandidat).
- [ ] **M4-LIF-02 — Mutasi masuk dan keluar.** Pisahkan jenis, ketersediaan periode, identitas dan sekolah asal/tujuan, bukti, review, persetujuan, dokumen rekomendasi/penerimaan, pelacakan dan efek enrollment menurut SOP. Buktikan kriteria pada [M4-LIF-02](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-lif-02--mutasi-masuk-dan-keluar).
- [ ] **M4-LIF-03 — Kelulusan dan SKL.** Siapkan data kelulusan, verifikasi pejabat, tingkat akses, jadwal rilis, cek hasil, cetak/unduh SKL, penerbitan ulang terkendali dan pengambilan dokumen asli bila ada. Buktikan kriteria pada [M4-LIF-03](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-lif-03--kelulusan-dan-skl).
- [ ] **M4-LIF-04 — Verifikasi siswa dan alumni.** Tentukan bukti keaktifan/keabsahan ijazah yang ditampilkan, pemegang dokumen, kode/QR, masa berlaku, kondisi dicabut dan batas data publik. Buktikan kriteria pada [M4-LIF-04](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-lif-04--verifikasi-siswa-dan-alumni).
- [ ] **M4-LIF-05 — Legalisir.** Form alumni, jumlah lembar, keperluan, metode pengambilan, data kuasa dan tanda tangan. Dukung daftar syarat asli/salinan, register TU, verifikasi fisik, pengesahan, pengambilan langsung/kuasa, serta status. Buktikan kriteria pada [M4-LIF-05](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-lif-05--legalisir).
- [ ] **M4-LIF-06 — Surat kuasa dan SPTJM.** Template versi resmi, identitas pihak sesuai kebutuhan, tanda tangan pemberi/penerima kuasa bila disyaratkan, cetak PDF dan keterkaitan ke legalisir. Buktikan kriteria pada [M4-LIF-06](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-lif-06--surat-kuasa-dan-sptjm).
- [ ] **M4-LIF-07 — Alumni dan tracer.** Registrasi dan pembaruan terverifikasi, angkatan/jurusan, kontak, status kuliah/kerja, berkas pembuktian, preferensi tampil, pencarian terfilter, testimoni, karier, beasiswa dan E-KTA. Putuskan atribut sensitif dan persetujuan publikasinya. Buktikan kriteria pada [M4-LIF-07](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-lif-07--alumni-dan-tracer).

## Aduan dan perpustakaan

- [ ] **M4-SERV-01 — Aduan bernama/anonim.** Kategori akademik, perundungan, fasilitas, staf, keselamatan, dan lainnya sesuai SOP. Form kronologi, lokasi, lampiran atau kamera, pilihan anonim dan penerbitan tiket. Buktikan kriteria pada [M4-SERV-01](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-serv-01--aduan-bernamaanonim).
- [ ] **M4-SERV-02 — Satgas dan transparansi.** Antrean terbatas, alokasi kategori, telaah, tindak lanjut, bukti selesai, balasan tiket, filter status, ringkasan publik dan statistik yang dimoderasi. Tetapkan SLA dengan klien. Buktikan kriteria pada [M4-SERV-02](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-serv-02--satgas-dan-transparansi).
- [ ] **M4-SERV-03 — Katalog dan koleksi.** Kategori, katalog buku dan e-book, pencarian, detail dan ketersediaan. Tentukan sumber koleksi/hak akses/tautan baca serta pengelolaan oleh pustakawan. Buktikan kriteria pada [M4-SERV-03](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-serv-03--katalog-dan-koleksi).
- [ ] **M4-SERV-04 — Layanan perpustakaan.** Presensi kunjungan QR/input manual sesuai SOP, usul pengadaan dan antrean pustakawan, bebas pustaka dan statistik. Tetapkan sumber pinjaman aktif dan cakupan peminjaman/pengembalian lokal atau SLiMS. Buktikan kriteria pada [M4-SERV-04](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-serv-04--layanan-perpustakaan).

## Proposal, LPJ dan koneksi eksternal

- [ ] **M4-INT-01 — Proposal kegiatan guru.** Petakan jenis kegiatan, data penyelenggara, tanggal, tujuan, lampiran, RAB, reviewer, perubahan dan keluaran berdasarkan demo. Bedakan pekerjaan guru dari pengajuan mandiri. Buktikan kriteria pada [M4-INT-01](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-int-01--proposal-kegiatan-guru).
- [ ] **M4-INT-02 — Proposal/LPJ mandiri.** Petakan identitas pengaju, keterkaitan proposal dan LPJ, revisi RAB, bukti realisasi, status dana, peran verifikator dan arsip. Batas fitur pembayaran/pencairan menunggu persetujuan klien. Buktikan kriteria pada [M4-INT-02](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-int-02--proposallpj-mandiri).
- [ ] **M4-INT-03 — EMIS, RDM dan SLiMS.** Putuskan per sistem apakah tautan, file impor/ekspor, sinkronisasi atau integrasi akun. Dokumentasikan pemilik data, identitas, pemetaan, arah, jadwal, kesalahan parsial, retry, rekonsiliasi dan otorisasi. Buktikan kriteria pada [M4-INT-03](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-int-03--emis-rdm-dan-slims).
- [ ] **M4-INT-04 — Kanal eksternal.** Konfirmasi penyedia waktu salat, peta, chat, email, WhatsApp, media dan kemampuan perangkat. Dokumentasikan konfigurasi sekolah, biaya bila ada, izin, pemeliharaan, dan fallback. Buktikan kriteria pada [M4-INT-04](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-int-04--kanal-eksternal).

## Verifikasi, peluncuran dan serah terima

- [ ] **M4-REL-01 — Regresi desain dan alur.** Uji desktop/ponsel, keyboard, web responsif, tampilan login, publik dan per peran, serta alur sukses/koreksi/ditolak/ulang. Verifikasi menu, rute langsung, link, unggah, cetak dan data lama. Buktikan kriteria pada [M4-REL-01](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-rel-01--regresi-desain-dan-alur).
- [ ] **M4-REL-02 — Kinerja, pemulihan dan data lama.** Uji antrean, daftar besar, query, ekspor, batas unggahan, migrasi dengan data lama, pemulihan database plus berkas, koreksi operasi dan observabilitas tanpa isi sensitif. Buktikan kriteria pada [M4-REL-02](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-rel-02--kinerja-pemulihan-dan-data-lama).
- [ ] **M4-REL-03 — UAT dan rollout.** Siapkan data contoh, role matrix, SOP dan panduan, daftar fitur yang dibuka, konfigurasi, migrasi, persetujuan UAT per peran, rencana rollback, health check, monitoring dan penanggung jawab. Buktikan kriteria pada [M4-REL-03](REQUIREMENTS_PARITAS_MAN4_BANJAR.md#m4-rel-03--uat-dan-rollout).

## Penutupan

- [ ] Implementasikan dan uji rincian tambahan pada bagian **Rincian tambahan hasil REA** di requirements. Sertakan filter pelacakan, kontrol kios, gerbang akses pos, jalur manual BK, pencarian verifikasi, dan editor RAB pengajuan mandiri pada demo.

Gunakan [peta alur dan presentasi paritas](PETA_ALUR_DAN_PRESENTASI_PARITAS.md) sebagai urutan demo dan daftar pemeriksaan hasil akhir.

### Uji penerimaan lintas peran

| Skenario | Kebutuhan | Bukti dari pengguna dan petugas |
|---|---|---|
| Pengunjung mencari layanan lalu membuka formulir | UX, PUB | Uji desktop, lebar menengah dan ponsel. Periksa fokus saat menu ditutup, layar kosong, dan layanan tutup. |
| Pemohon memperbaiki berkas dan memperoleh dokumen | TU, FND | Resi awal, histori koreksi, pemberitahuan, hasil terbaru, bukti pengambilan atau unduhan, dan penolakan akses akun lain. |
| Instansi mengirim surat dan resepsionis menerima tamu | TU | Surat muncul di agenda dan menghasilkan tanda terima. Entri tamu muncul di rekap yang berwenang. |
| Wali mengajukan izin dan wali kelas memutuskan | OPS | Uji tanggal bentrok dan token habis pakai. Tunjukkan bukti dan rekap presensi setelah keputusan atau koreksi. |
| Siswa tap kartu dan melewati pos dengan izin | OPS | Satu presensi untuk pengulangan scan. Piket, satpam dan BK melihat data sesuai wewenang. Uji izin kedaluwarsa, kembali, input manual dan jaringan putus. |
| Peserta mendaftar, diseleksi dan daftar ulang | LIF | Cek hak kandidat, jadwal rilis, kuota, bukti cetak dan satu data siswa setelah konversi. |
| Siswa mutasi dan alumni mengurus SKL atau legalisir | LIF, TU | Dokumen dan status benar, histori enrollment tetap utuh, perwakilan diverifikasi, kode dokumen yang dicabut ditolak. |
| Alumni memperbarui profil dan mencetak kartu | LIF | Persetujuan publikasi, hasil filter, moderasi dan data yang tampil pada E-KTA sesuai hak. |
| Satgas menangani laporan anonim | SERV | Tiket menunjukkan pembaruan yang relevan, bukti privat tetap terlindungi, agregat publik sesuai aturan moderasi. |
| Pustakawan mengelola usulan dan bebas pustaka | SERV, INT | Uji kunjungan, usulan dan status pinjaman yang memengaruhi bebas pustaka. Uji skenario sumber katalog gagal. |
| Guru mengajukan proposal dan menyelesaikan LPJ | INT | Revisi RAB dan keputusan meninggalkan histori. Status dana dan dokumen hanya terlihat sesuai kewenangan. |
| Admin memulihkan sistem pada lingkungan terisolasi | REL, FND | Database dan berkas cocok, lampiran bisa diakses menurut hak, pemeriksaan kesehatan dan rollback terdokumentasi. |

### Pemeriksaan akhir

- [ ] Cocokkan inventaris acuan dengan 48 ID kebutuhan dan hasil uji. Perbedaan dan pekerjaan yang belum bisa diverifikasi mempunyai keputusan dan pemilik.
- [ ] Peragakan alur pesanan klien dari titik masuk sampai hasil per peran, termasuk koreksi, kegagalan, dan batas akses.
- [ ] Verifikasi penggunaan perangkat nyata dan layanan eksternal yang disepakati.
- [ ] Simpan hasil UAT, instruksi deploy, migrasi, rollback, pemulihan, dan panduan operator.
