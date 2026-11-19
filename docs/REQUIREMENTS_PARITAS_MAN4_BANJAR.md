# Requirements paritas MAN 4 Banjar

Dokumen ini menjadi spesifikasi tambahan School Suite untuk pelaksana Gemini. Semua kebutuhan di bawah masuk cakupan. Nilai aturan, format dokumen, hak akses operasional, dan kontrak eksternal yang belum diketahui dicatat sebagai keputusan terbuka. Kriteria adalah target pengujian School Suite.

## Rujukan

- [Rencana dan bukti REA](RENCANA_PARITAS_MAN4_BANJAR.md)
- [Requirements pilot](REQUIREMENTS.md), [pedoman desain](DESIGN.md), [status proyek](PROJECT_STATUS.md), dan [panduan Gemini](GEMINI.md)
- [TODO pelaksanaan dan penelusuran](TODO_PARITAS_MAN4_BANJAR.md)

## Keputusan yang memerlukan bukti dari klien

### Rincian tambahan hasil REA

| ID | Kemampuan yang perlu dicakup | Bukti penerimaan |
|---|---|---|
| M4-TU-04 | Pencarian terpadu dan filter Semua, Izin, Legalisir, Mutasi dan PTSP. Acuan menampilkan pilihan identitas atau nomor referensi. Tetapkan cara verifikasi pemohon sebelum membuka hasil. | Tampilkan masing-masing kategori dengan data contoh. Salah identitas tidak membuka berkas. |
| M4-OPS-03 | Kios menampilkan tap kartu, QR kamera, kontrol Gate dan layar penuh. Makna operasional Gate menunggu demo atau SOP. | Operator membuka layar penuh dan berpindah ke pemindaian QR. Perilaku Gate diuji setelah kontraknya diketahui. |
| M4-OPS-04 | Pos jaga memiliki layar akses dengan papan angka. | Uji tampilan pintu masuk dan penolakan akses sebelum panel pos terbuka. |
| M4-OPS-05 | BK bisa memulai kamera atau mencari siswa secara manual. | Keduanya memilih siswa yang tepat untuk petugas berwenang. |
| M4-LIF-04 | Pemeriksaan keaktifan dan alumni menyediakan pencarian nama atau NISN dan akses terkait ke tracer dan legalisir. Pastikan cakupan daftar siswa/rombel serta data yang boleh tampil melalui konfirmasi. | Uji status aktif/alumni dan dokumen sah/dicabut tanpa membuka identitas di luar kewenangan. |
| M4-INT-01, M4-INT-02 | Selain proposal guru, varian pengajuan mandiri menampilkan pengusul siswa/organisasi, muat contoh webinar, susunan panitia, RAB dengan item tambah/hapus, serta pelacakan proposal dan realisasi dana. | Uji contoh yang dapat diubah, hitung kuantitas dan harga, perubahan item, pengajuan dan pelacakan. Aksi LPJ internal menunggu bukti. |

| ID | Bahan | Kebutuhan terkait |
|---|---|---|
| K-01 | SOP sekolah klien atau persetujuan alur School Suite yang diusulkan, dengan matriks peran dan data contoh | FND, TU, OPS, LIF, SERV, INT |
| K-02 | Contoh dokumen, batas berkas, aturan verifikasi, pejabat, tanda tangan, nomor, retensi | FND, TU, OPS, LIF, INT |
| K-03 | Jam layanan, kalender, aturan status, SLA, pengajuan izin, publikasi PPDB/SKL dan kebijakan notifikasi | PUB, TU, OPS, LIF, SERV |
| K-04 | Jenis kartu dan QR, model perangkat, jaringan sekolah serta skenario input manual | OPS, SERV |
| K-05 | Sampel ekspor dan akses sandbox/dokumentasi integrasi EMIS, RDM, SLiMS dan penyedia komunikasi | INT, SERV, PUB |
| K-06 | Aset sekolah, persetujuan publikasi, kategori layanan, perwakilan desain publik dan internal | UX, PUB, LIF, SERV |
| K-07 | Demo modul proposal/LPJ, mekanisme dana dan batas tanggung jawab aplikasi | INT |
| K-08 | Data dan ukuran pilot, pemilik operasional, ambang kinerja, jadwal pilot dan penerimaan rilis | REL |

Gunakan skenario sintetik untuk spesifikasi dan uji awal. Untuk pekerjaan yang bergantung pada keputusan K, catat siapa yang harus menjawab, bukti yang tersedia, opsi, dan langkah yang masih dapat dikerjakan. Pisahkan perilaku yang diamati di acuan dari SOP sekolah klien.

Target produk adalah 100% paritas fitur terhadap inventaris MAN 4 Banjar, termasuk tindakan dan hasil di panel masing-masing peran. Penyebut cakupan mencakup setiap kemampuan yang ditemukan melalui halaman publik atau bukti resmi peran. Inventaris tetap terbuka selama ada panel yang belum diperiksa. Rancang alur School Suite berdasarkan bukti yang tersedia dan catat tiap asumsi untuk ditinjau. Gunakan identitas visual School Suite dengan inspirasi dari hierarki dan navigasi acuan.

Untuk setiap kemampuan, catat titik masuk, peran, prasyarat, tindakan, keadaan sukses/gagal, keluaran, rujukan bukti, ID kebutuhan, artboard, serta hasil uji. Bukti publik tidak mengonfirmasi fitur setelah login. Bukti internal dapat berasal dari demo yang diizinkan pengelola, dokumentasi resmi, atau rekaman peran yang sah. Persetujuan klien atas perilaku School Suite merupakan bukti penerimaan, sedangkan kesetaraan terhadap panel acuan memerlukan bukti panel tersebut.

## Fondasi dan aturan lintas modul

Fondasi yang perlu diperiksa dan dipakai kembali bila sesuai: Core, penyimpanan privat, audit log, kebijakan akses, komponen Inertia.

### M4-FND-01 — Inventaris dan kesetaraan

Petakan rute, layar, aksi, peran, status, dokumen, integrasi, kondisi kosong dan kesalahan. Tandai setiap baris sebagai teramati, inferensi, atau menunggu klien. Catat versi bukti REA, URL, dan tanggal pemeriksaan.

**Penerimaan.** Setiap fitur acuan mempunyai status dan rujukan. Keputusan yang terbuka tercatat per fitur.

### M4-FND-02 — Identitas dan peran

Petakan admin sekolah, operator, kepala, guru, wali kelas, siswa, wali, alumni, panitia, TU/PTSP, penyambut tamu, piket, satpam, BK, satgas, pustakawan dan pejabat penandatangan. Gunakan kebijakan server, batas sekolah dan cakupan kelas/penugasan.

**Penerimaan.** Uji akses langsung ke halaman, aksi, unduh, ekspor, pencarian, dan data milik sekolah atau siswa lain.

### M4-FND-03 — Data induk dan periode

Gunakan sekolah, siswa, wali, GTK, kelas, enrollment, periode, dan zona waktu yang sudah ada. Bedakan identitas pemohon eksternal dari akun pegawai. Pertahankan histori dan referensi dokumen setelah data induk berubah.

**Penerimaan.** Pergantian periode atau kelas mempertahankan kondisi riwayat dan tidak mencampur data antar sekolah.

### M4-FND-04 — Status dan audit

Untuk setiap layanan definisikan diagram transisi, actor, prasyarat, alasan, status publik, tindakan koreksi dan histori. Gunakan transaksi dan perlindungan pengiriman ulang/konkurensi pada aksi yang mengubah data.

**Penerimaan.** Dua klik atau dua petugas tidak menghasilkan dokumen ganda, keputusan bertentangan, atau audit yang hilang.

### M4-FND-05 — Berkas dan dokumen

Simpan lampiran privat. Validasi jenis, ukuran, dan akses. Tentukan template, versi, nomor, penandatangan, pratinjau, pembatalan, arsip, serta bukti digital/fisik per layanan. Tanda tangan gambar dan tanda tangan elektronik resmi mengikuti persetujuan klien masing-masing.

**Penerimaan.** Berkas valid dapat dibaca oleh pihak berwenang. Berkas tidak valid dan akses tanpa kewenangan ditolak. Cetak sesuai contoh dan histori tetap dapat diaudit.

### M4-FND-06 — Tiket dan identitas pemohon

Gunakan tiket yang sulit ditebak dan otorisasi pemilik sesuai jenis layanan. Untuk hasil sensitif, tetapkan verifikasi identitas sebelum menampilkan detail. Publikasikan hanya status minimum yang disetujui.

**Penerimaan.** Pengguna sah melihat status yang tepat. Salah identitas, tiket milik pihak lain, dan percobaan berulang tidak membuka data.

### M4-FND-07 — Notifikasi dan antrean

Petakan peristiwa, penerima, template, status kirim, percobaan ulang, dan kanal aplikasi/email/WhatsApp bila disepakati. Hindari isi sensitif di notifikasi, log, dan URL.

**Penerimaan.** Kegagalan pengiriman tercatat dan bisa diulang tanpa membuat permohonan atau keputusan ganda.

### M4-FND-08 — Privasi dan operasi

Dokumentasikan data publik, privat, dan sangat terbatas per layanan, retensi, koreksi data, ekspor, arsip, sanitasi log, pemulihan database/file, serta pemilik operasional.

**Penerimaan.** Data contoh bisa dipulihkan dan catatan sensitif tidak muncul di log, ringkasan publik, atau ekspor umum.

## Desain bersama seluruh aplikasi

Fondasi yang perlu diperiksa dan dipakai kembali bila sesuai: Brand kit, komponen Vue, layout publik, autentikasi, dashboard internal, dan tema yang ada.

### M4-UX-01 — Sistem visual

Tentukan warna, tipografi, skala jarak, grid, ikon, radius, bayangan, fokus, dan status. Terapkan identitas sekolah klien serta kepadatan sesuai kebutuhan, dengan inspirasi hero kontras dan panel layanan terang dari hasil REA.

**Penerimaan.** Token dapat ditelusuri ke layar publik, login, katalog, dashboard, daftar, detail, dan formulir tanpa variasi tak beralasan.

### M4-UX-02 — Navigasi responsif

Desktop menyediakan panel E-Layanan berkelompok dengan deskripsi singkat. Ponsel menyediakan panel satu kolom dengan akses login dan kategori yang konsisten. Uji keyboard, fokus kembali, Escape, penutupan, gulir, lebar menengah, dan posisi bantuan.

**Penerimaan.** Pada 390×844 dan 1280×720 serta lebar menengah, layanan dapat dicari dan dipilih tanpa tertutup chat atau keluar dari layar.

### M4-UX-03 — Ruang kerja internal

Samakan shell, menu berbasis izin, periode aktif, judul, breadcrumb, tindakan utama, daftar, filter, pagination, detail, riwayat, form, modal, unggah dan cetak. Pertahankan kepadatan tabel kerja dan alur modul yang ada.

**Penerimaan.** Pengguna menemukan pekerjaan menurut perannya dan setiap tindakan berfungsi dari titik masuk normal. Rute lama yang relevan tetap dapat dipakai.

### M4-UX-04 — Keadaan UI dan aksesibilitas

Sediakan label, petunjuk, indikator wajib, error per kolom, fokus terlihat, loading, kosong, gagal, berhasil, retry, konfirmasi, urutan tab dan status dengan teks. Uji zoom, keyboard, kontras, ukuran sentuh dan formulir ponsel.

**Penerimaan.** Uji keyboard dan layar kecil dapat menyelesaikan alur utama. Kesalahan server, jaringan dan validasi memberi tindakan pemulihan.

## Portal dan konten publik

Fondasi yang perlu diperiksa dan dipakai kembali bila sesuai: Core, profil publik, berita, galeri, kontak dan landing PPDB.

### M4-PUB-01 — Beranda dan identitas

Header identitas, logo, informasi singkat sekolah, kontak, pengumuman, hero, foto, ringkasan capaian bersumber, pintasan PPDB/PTSP/Siap Lapor/Lacak, footer dan tautan media sosial.

**Penerimaan.** Admin dapat memperbarui konten. Pengunjung menemukan empat pintasan dan data yang tampil sesuai publikasi.

### M4-PUB-02 — Katalog E-Layanan

Daftarkan Persuratan & Tamu, Siswa & Alumni, Ketertiban & Sarana, pusat unduh, Zona Integritas/IKM, perpustakaan, verifikasi siswa/alumni serta akses proposal sesuai peran. Sediakan pencarian judul/sinonim dan keadaan tanpa hasil.

**Penerimaan.** Hasil pencarian membuka layanan yang benar. Layanan tutup menunjukkan status dan opsi yang masih tersedia. Hak operasional diperiksa server.

### M4-PUB-03 — Profil dan direktori

Sambutan, visi dan misi, profil, direktori GTK, fasilitas, program/peminatan, kesiswaan, prestasi, jadwal/agenda, kontak, jam layanan dan peta.

**Penerimaan.** Setiap konten bisa diterbitkan, diubah, dan ditarik oleh pengelola. Direktori hanya memuat atribut publik yang disetujui.

### M4-PUB-04 — Media dan publikasi

Berita daftar/detail, foto/galeri, video, keterangan, kategori, media alternatif, tautan sumber, arsip dan status draft/terbit.

**Penerimaan.** Konten draft tidak tampil pada daftar, pencarian, atau URL publik. Konten terbit ramah ponsel.

### M4-PUB-05 — Pusat unduh

Kelola kategori formulir, kalender, tata tertib, brosur, SOP, dokumen resmi dan versi. Tampilkan judul, format, ukuran dan tautan, serta statistik unduhan jika dipakai.

**Penerimaan.** Unduhan publik selalu memakai versi yang terbit dan tidak membuka dokumen internal.

### M4-PUB-06 — Zona Integritas dan IKM

Maklumat, enam area perubahan, dokumentasi, standar layanan, ringkasan IKM dan indikator publik yang disetujui, formulir penilaian, agregasi, dan kanal aduan.

**Penerimaan.** Penilaian masuk ke agregat sesuai aturan. Tampilan publik hanya menunjukkan data agregat atau konten yang telah ditinjau.

### M4-PUB-07 — Jadwal salat, trafik dan bantuan

Sumber jadwal sesuai lokasi/zona waktu, tampilan waktu berikutnya dan jadwal lengkap. Definisikan statistik online/harian/bulanan/total, kanal kontak, live chat bila disetujui, jam operasional dan fallback.

**Penerimaan.** Gangguan sumber eksternal memberi tampilan cadangan. Chat tidak menutup menu atau tindakan. Statistik tidak mengungkap identitas pengunjung.

## PTSP dan tata usaha

Fondasi yang perlu diperiksa dan dipakai kembali bila sesuai: Berkas privat, akun, audit, administrasi, persuratan dan notifikasi yang ada.

### M4-TU-01 — Katalog PTSP dan akun

Kelola jenis layanan, persyaratan, jam/estimasi, publikasi, registrasi, login/pemulihan akun, serta identitas dan kontak pemohon sesuai SOP.

**Penerimaan.** Pemohon dapat masuk dan memilih layanan aktif. Perubahan syarat tidak mengubah permohonan yang sudah masuk.

### M4-TU-02 — Pengajuan dan resi

Form identitas/kontak, jenis layanan, kebutuhan, catatan, lampiran, ringkasan, persetujuan submit dan resi. Tentukan kapan draft dapat diubah dan berkas mana yang wajib.

**Penerimaan.** Draf dapat dilanjutkan sesuai aturan. Kirim dengan syarat lengkap menghasilkan tepat satu resi.

### M4-TU-03 — Antrean petugas dan hasil

Filter sekolah, layanan, tanggal dan status. Verifikasi, minta perbaikan, tolak beralasan, disposisi, proses, terbitkan, unduh atau catat pengambilan fisik. Sediakan histori petugas dan status pemohon.

**Penerimaan.** Petugas menyelesaikan pengajuan dari antrean. Pemohon bisa memperbaiki, melihat pembaruan dan memperoleh hasil sesuai status.

### M4-TU-04 — Pelacakan terpadu

Hubungkan PTSP, izin, mutasi dan legalisir. Surat eksternal dan pengaduan perlu titik akses pelacakan sesuai aturan masing-masing. Uji tiket valid, salah, kedaluwarsa, pembatasan permintaan dan status lintas layanan.

**Penerimaan.** Status selalu berasal dari layanan pemilik. Hasil publik sesuai hak pemohon dan tidak menampilkan rincian privat.

### M4-TU-05 — Surat dinas eksternal

Form instansi, narahubung, jabatan, kontak, nomor/tanggal/sifat/perihal, ringkasan, PDF utama, lampiran dan pernyataan. Terbitkan tanda terima, agenda, disposisi dan status melalui alur TU.

**Penerimaan.** Instansi mendapatkan tiket dan tanda terima. TU melihat surat di agenda tanpa entri ganda.

### M4-TU-06 — Buku tamu

Registrasi pengunjung, kontak sesuai SOP, pegawai tujuan, keperluan dan kategori lainnya, waktu datang/pulang bila diperlukan, riwayat resepsionis dan rekap.

**Penerimaan.** Resepsionis merekam dan menemukan kunjungan. Data tamu tidak terlihat publik.

## Presensi, izin, piket dan BK

Fondasi yang perlu diperiksa dan dipakai kembali bila sesuai: Academic, Homeroom, data kartu dan histori presensi.

### M4-OPS-01 — Izin dan dispensasi

Verifikasi wali/siswa, kategori, tanggal mulai/akhir, alasan, kontak, lampiran dan tanda tangan. Tolak rentang bentrok. Terapkan jam pengajuan lokal, aturan izin terencana, dan token sekali pakai terikat siswa/tujuan dengan validasi dan konsumsi atomik.

**Penerimaan.** Uji hari mendatang, batas jam, hari sama, tanggal bentrok, token salah/kedaluwarsa/terpakai, dan percobaan ulang.

### M4-OPS-02 — Verifikasi dan dampak presensi

Wali kelas/petugas meninjau, mengembalikan atau memutuskan izin. Tampilkan status, bukti, pemberitahuan dan jalur koreksi. Gunakan aturan klien untuk dampaknya pada presensi.

**Penerimaan.** Wali melihat keputusan. Presensi dan rekap konsisten dengan keputusan sah dan koreksi dapat diaudit.

### M4-OPS-03 — Kios RFID dan QR

Daftarkan dan cabut kartu sesuai hak. Dukung scan RFID dan QR yang disepakati, pesan sukses/gagal, batas pemindaian berulang, data minimal di layar, jam/periode, perangkat terotorisasi dan pemulihan gangguan jaringan.

**Penerimaan.** Perangkat nyata mencatat satu peristiwa pada scan berulang. Kartu asing, tidak aktif atau salah sekolah tidak mengubah presensi.

### M4-OPS-04 — Piket dan pos satpam

Piket mencari/memindai kartu, mencatat alasan, periode dan persetujuan. Satpam memindai atau memasukkan kode manual sesuai hak dan mencatat keluar/kembali. Tentukan status kedaluwarsa, pembatalan, serah terima dan pengecualian dari SOP.

**Penerimaan.** Uji izin aktif, batal, kedaluwarsa, sudah digunakan, pencatatan kembali dan gangguan perangkat.

### M4-OPS-05 — Disiplin BK

Pindai QR atau cari siswa sesuai akses, pilih jenis pelanggaran, catat waktu/tempat/kejadian dan tindak lanjut. Batasi eviden, riwayat dan ekspor untuk petugas yang berwenang.

**Penerimaan.** BK dapat mencatat dan memperbaiki kasus dengan audit. Pengguna di luar kewenangan tidak melihat data maupun lampiran.

## Siklus siswa dan alumni

Fondasi yang perlu diperiksa dan dipakai kembali bila sesuai: Admissions, Academic, Assessment dan arsip dokumen.

### M4-LIF-01 — PPDB dan pelayanan kandidat

Periode/jalur, kuota/syarat, akun kandidat bila dipakai, pendaftaran, unggah, bukti, tahap perbaikan, penilaian/ranking, keputusan, publikasi hasil, daftar ulang, rekap dan konversi siswa sesuai prosedur.

**Penerimaan.** Uji sebelum/saat/sesudah periode, kandidat lengkap/tidak lengkap, hasil rilis, kuota, cetak bukti dan konversi tanpa duplikasi.

### M4-LIF-02 — Mutasi masuk dan keluar

Pisahkan jenis, ketersediaan periode, identitas dan sekolah asal/tujuan, bukti, review, persetujuan, dokumen rekomendasi/penerimaan, pelacakan dan efek enrollment menurut SOP.

**Penerimaan.** Uji kedua jalur sampai dokumen, penolakan, pencabutan dan histori siswa tetap tersedia.

### M4-LIF-03 — Kelulusan dan SKL

Siapkan data kelulusan, verifikasi pejabat, tingkat akses, jadwal rilis, cek hasil, cetak/unduh SKL, penerbitan ulang terkendali dan pengambilan dokumen asli bila ada.

**Penerimaan.** Sebelum rilis hasil tidak tampil. Sesudah rilis hanya pemilik/pegawai sah mendapat hasil dan SKL yang tepat.

### M4-LIF-04 — Verifikasi siswa dan alumni

Tentukan bukti keaktifan/keabsahan ijazah yang ditampilkan, pemegang dokumen, kode/QR, masa berlaku, kondisi dicabut dan batas data publik.

**Penerimaan.** Contoh dokumen sah, salah, dan dicabut memberi respons yang tepat tanpa membuka data siswa atau alumni di luar persetujuan.

### M4-LIF-05 — Legalisir

Form alumni, jumlah lembar, keperluan, metode pengambilan, data kuasa dan tanda tangan. Dukung daftar syarat asli/salinan, register TU, verifikasi fisik, pengesahan, pengambilan langsung/kuasa, serta status.

**Penerimaan.** Alumni dapat melacak permohonan sampai selesai dan petugas dapat mencatat verifikasi asli serta siapa mengambil hasil.

### M4-LIF-06 — Surat kuasa dan SPTJM

Template versi resmi, identitas pihak sesuai kebutuhan, tanda tangan pemberi/penerima kuasa bila disyaratkan, cetak PDF dan keterkaitan ke legalisir.

**Penerimaan.** Hasil cetak sesuai dokumen contoh. Validasi data pihak dan akses file berlaku sebelum serta sesudah penerbitan.

### M4-LIF-07 — Alumni dan tracer

Registrasi dan pembaruan terverifikasi, angkatan/jurusan, kontak, status kuliah/kerja, berkas pembuktian, preferensi tampil, pencarian terfilter, testimoni, karier, beasiswa dan E-KTA. Putuskan atribut sensitif dan persetujuan publikasinya.

**Penerimaan.** Alumni dapat mengelola profil sendiri. Admin memoderasi publikasi. Direktori dan kartu hanya memuat atribut yang disetujui.

## Aduan dan perpustakaan

Fondasi yang perlu diperiksa dan dipakai kembali bila sesuai: Akses, penyimpanan, pengelolaan konten, dan modul Library sesuai audit.

### M4-SERV-01 — Aduan bernama/anonim

Kategori akademik, perundungan, fasilitas, staf, keselamatan, dan lainnya sesuai SOP. Form kronologi, lokasi, lampiran atau kamera, pilihan anonim dan penerbitan tiket.

**Penerimaan.** Kedua mode dapat dilacak. Aturan anonimitas jelas dan identitas tidak muncul di halaman publik atau notifikasi umum.

### M4-SERV-02 — Satgas dan transparansi

Antrean terbatas, alokasi kategori, telaah, tindak lanjut, bukti selesai, balasan tiket, filter status, ringkasan publik dan statistik yang dimoderasi. Tetapkan SLA dengan klien.

**Penerimaan.** Alur dari laporan masuk sampai selesai dapat diperagakan. Pelapor melihat pembaruan yang berhak diterima dan publik hanya melihat ringkasan yang disetujui.

### M4-SERV-03 — Katalog dan koleksi

Kategori, katalog buku dan e-book, pencarian, detail dan ketersediaan. Tentukan sumber koleksi/hak akses/tautan baca serta pengelolaan oleh pustakawan.

**Penerimaan.** Koleksi dapat ditemukan dari portal. Materi terbatas hanya tersedia bagi pengguna sah.

### M4-SERV-04 — Layanan perpustakaan

Presensi kunjungan QR/input manual sesuai SOP, usul pengadaan dan antrean pustakawan, bebas pustaka dan statistik. Tetapkan sumber pinjaman aktif dan cakupan peminjaman/pengembalian lokal atau SLiMS.

**Penerimaan.** Peminjaman aktif memengaruhi hasil bebas pustaka sesuai aturan. Scan, usul, keputusan, dan statistik dapat diperagakan.

## Proposal, LPJ dan koneksi eksternal

Fondasi yang perlu diperiksa dan dipakai kembali bila sesuai: Administration dan Integration sesuai kontrak klien.

### M4-INT-01 — Proposal kegiatan guru

Petakan jenis kegiatan, data penyelenggara, tanggal, tujuan, lampiran, RAB, reviewer, perubahan dan keluaran berdasarkan demo. Bedakan pekerjaan guru dari pengajuan mandiri.

**Penerimaan.** Guru mengajukan proposal yang valid, menerima revisi atau keputusan, dan dapat melihat histori.

### M4-INT-02 — Proposal/LPJ mandiri

Petakan identitas pengaju, keterkaitan proposal dan LPJ, revisi RAB, bukti realisasi, status dana, peran verifikator dan arsip. Batas fitur pembayaran/pencairan menunggu persetujuan klien.

**Penerimaan.** Pengaju dapat melacak proposal, dana sesuai wewenang, dan LPJ. Reviewer dapat menutup pengajuan lengkap menurut SOP.

### M4-INT-03 — EMIS, RDM dan SLiMS

Putuskan per sistem apakah tautan, file impor/ekspor, sinkronisasi atau integrasi akun. Dokumentasikan pemilik data, identitas, pemetaan, arah, jadwal, kesalahan parsial, retry, rekonsiliasi dan otorisasi.

**Penerimaan.** Uji pertukaran dengan data contoh dan kegagalan koneksi. Hasil menunjukkan baris gagal, sumber, dan tindakan pemulihan sesuai mekanisme.

### M4-INT-04 — Kanal eksternal

Konfirmasi penyedia waktu salat, peta, chat, email, WhatsApp, media dan kemampuan perangkat. Dokumentasikan konfigurasi sekolah, biaya bila ada, izin, pemeliharaan, dan fallback.

**Penerimaan.** Layanan inti tetap memberi status yang jelas saat penyedia tidak tersedia. Konfigurasi dan kredensial tetap berada di penyimpanan yang sesuai.

## Verifikasi, peluncuran dan serah terima

Fondasi yang perlu diperiksa dan dipakai kembali bila sesuai: Suite pengujian proyek, pilot seed, runbook dan checklist deployment.

### M4-REL-01 — Regresi desain dan alur

Uji desktop/ponsel, keyboard, web responsif, tampilan login, publik dan per peran, serta alur sukses/koreksi/ditolak/ulang. Verifikasi menu, rute langsung, link, unggah, cetak dan data lama.

**Penerimaan.** Simpan bukti pengujian per ID. Hasil menampilkan langkah, peran, data contoh dan keputusan lulus/gagal.

### M4-REL-02 — Kinerja, pemulihan dan data lama

Uji antrean, daftar besar, query, ekspor, batas unggahan, migrasi dengan data lama, pemulihan database plus berkas, koreksi operasi dan observabilitas tanpa isi sensitif.

**Penerimaan.** Pemulihan diverifikasi di lingkungan terisolasi. Ambang kinerja dan ukuran pilot disepakati sebelum UAT.

### M4-REL-03 — UAT dan rollout

Siapkan data contoh, role matrix, SOP dan panduan, daftar fitur yang dibuka, konfigurasi, migrasi, persetujuan UAT per peran, rencana rollback, health check, monitoring dan penanggung jawab.

**Penerimaan.** Klien menerima setiap alur yang tercatat. Masalah terbuka punya pemilik dan keputusan tertulis terhadap rilis.
