# Rencana pengembangan School Suite mengacu pada MAN 4 Banjar

Status: siap untuk implementasi bertahap. MAN 4 Banjar adalah situs pihak ketiga yang menjadi referensi publik. Klien dan tim School Suite tidak memiliki akses ke ruang kerja internal situs tersebut.

## Sasaran

School Suite menargetkan 100% paritas fitur dari pintu masuk pengguna sampai penyelesaian oleh petugas. Target mencakup portal publik, panel per peran, tindakan, kondisi pengecualian dan hasil yang diterima pengguna. Inventaris dianggap lengkap setelah panel peran mempunyai bukti resmi dan semua temuan telah dicocokkan dengan requirements, TODO dan mockup. Portal, halaman layanan, dan ruang kerja berbagi hierarki visual dengan identitas dan konten sekolah klien.

## Tahapan dan hasil yang dapat diperagakan

| Tahap | Lingkup | Kriteria penerimaan |
|---|---|---|
| 1. Desain dan portal | Header, navigasi desktop/ponsel, E-Layanan, profil, direktori GTK, program dan sarana, berita, agenda, prestasi, galeri, video, kontak dan peta, pusat unduh, Zona Integritas, maklumat, IKM, waktu salat, statistik pengunjung, dan kanal bantuan | Pengunjung menemukan layanan dari menu, pintasan atau pencarian. Pengelola menerbitkan konten dan dokumen. Uji desktop dan ponsel mencakup halaman publik dan ruang kerja petugas. |
| 2. Tata usaha dan PTSP | Akun pemohon, katalog persyaratan, unggahan, tiket dan pelacakan, verifikasi dan disposisi, terbit/unduh/ambil hasil, surat dinas eksternal dengan tanda terima, buku tamu | Pemohon mengajukan permohonan. Petugas mengembalikan berkas yang kurang atau memprosesnya sampai selesai. Pemohon melihat status dan mendapatkan hasil sesuai haknya. |
| 3. Izin, presensi, piket dan BK | Izin siswa dengan lampiran, tanda tangan, pemeriksaan tanggal dan token dispensasi, kios RFID/QR, izin keluar, pos satpam, pencatatan BK | Wali mendapat bukti izin setelah verifikasi. Pemindaian berulang tidak menggandakan presensi. Piket dan satpam mencatat tahapan sesuai SOP. Catatan BK hanya terlihat oleh peran yang berwenang. |
| 4. Siklus peserta didik | PPDB, mutasi masuk/keluar, kelulusan dan SKL, verifikasi siswa/alumni, legalisir, surat kuasa dan SPTJM, direktori dan tracer alumni, karier, beasiswa dan E-KTA | Calon siswa mengikuti pendaftaran sampai daftar ulang. Mutasi tuntas dengan dokumen yang sesuai. SKL tersedia setelah jadwal rilis. Alumni bisa mengajukan dan melacak legalisir serta mengelola profilnya. |
| 5. Pengaduan dan perpustakaan | Laporan bernama/anonim, lampiran, penanganan satgas, tiket dan ringkasan publik, katalog/e-book, presensi perpustakaan, usul pengadaan, bebas pustaka dan statistik | Pelapor bisa melacak tindak lanjut tanpa kebocoran identitas. Pustakawan meninjau usulan dan pengguna dapat mengecek bebas pustaka sesuai hak akses. |
| 6. Proposal dan integrasi | Proposal kegiatan guru, proposal dan LPJ mandiri, RAB dan pelacakan dana sesuai alur klien, penetapan hubungan dengan RDM, EMIS dan SLiMS | Klien menyetujui alur proposal, hasil yang dapat diakses dan batas masing-masing integrasi. Setiap koneksi yang diperlukan diuji dengan data contoh. |

Urutan di atas merupakan usulan berdasarkan ketergantungan. PTSP membuktikan pola pengajuan sampai penyelesaian sebelum komponen yang sama digunakan pada layanan lain. Riset perangkat dan sistem eksternal berjalan sejak awal.

## Pemanfaatan modul yang ada

- Core dan portal publik menjadi dasar konten, akun dan akses. Gunakan komponen Vue/Inertia yang ada untuk pola antarmuka bersama.
- Admissions sudah menyediakan alur internal periode, jalur, verifikasi, seleksi dan daftar ulang. Cocokkan dengan kebutuhan akun calon siswa, cek hasil dan bukti dari sisi publik.
- Academic dan Homeroom sudah menyediakan presensi, koreksi, rekap, dispensasi dan pencatatan kedisiplinan. Tambahkan titik masuk wali dan perangkat berdasarkan SOP tanpa membuat master siswa kedua.
- Gunakan penyimpanan privat dan audit aktivitas yang tersedia untuk dokumen dan tindakan petugas. Uji akses terhadap tiket, file dan data lintas sekolah/kelas sesuai wewenang.
- Keberadaan direktori Library belum membuktikan alur katalog dan sirkulasi siap digunakan. Putuskan kebutuhan katalog lokal, akses SLiMS atau sinkronisasi setelah melihat lingkungan klien.

## Bukti yang diperlukan untuk mengunci spesifikasi

| Bukti | Keputusan yang ditopang |
|---|---|
| SOP sekolah klien atau persetujuan rancangan alur School Suite dengan data contoh | Antrean kerja, peran, perubahan status, keputusan, koreksi, pemberitahuan, dan hasil tiap layanan. Dahulukan TU/PTSP, wali kelas, piket, satpam, BK, satgas, pustakawan dan panitia PPDB. |
| Contoh tanda terima, surat, SKL, legalisir, formulir, RAB dan LPJ | Isi wajib, format, pihak yang berwenang, penomoran dan cara penerbitan. |
| Model pembaca RFID, tipe kartu/QR dan prosedur pos | Cara pencatatan, aturan duplikasi, input manual, dan perilaku saat perangkat atau jaringan gagal. |
| Contoh ekspor, dokumentasi dan konfigurasi lingkungan integrasi | Data rujukan, arah pertukaran dan mekanisme yang tersedia untuk EMIS, RDM, SLiMS dan notifikasi. |
| Tampilan acuan pada desktop dan ponsel serta lokasi skill rea | Verifikasi halaman layanan dan formulir pada ukuran layar yang berbeda. Klon REA sudah ditemukan dan CLI sudah digunakan. |

## Batas verifikasi

REA CLI 6.7.0 merekam layar awal tujuh halaman tambahan tanpa login atau pengiriman data. Cuplikan aksesibilitas mengonfirmasi bahwa kontrol berikut tersedia. Interaksi setelah menekan kontrol belum diperiksa.

| Halaman | Bukti REA | Temuan |
|---|---|---|
| PTSP | `ev_f2d63f4bbf6d4258f692b72238d39c993f63bdbc5c003c400c04a6cda0f86415` | Panduan akun, unggah dan verifikasi |
| Tracking | `ev_89c636782fc186739eada5ecf0622f1b979745681dec1327b4eeb47ad87c2f4d` | Pencarian dan filter per layanan |
| Pos jaga | `ev_0d6e501cc389281f78e5149c869dcc276e24dc268c1079b65b6a0188eb6eef51` | Layar akses dan papan angka |
| Pelanggaran | `ev_911af8112fcd9db71ba4fa11731ffd8f1c39bcbe2381f1eb1a14774ef9d9fc30` | Kontrol kamera dan pencarian siswa manual |
| Kios | `ev_3f3d984b39474bc74fd5f2bfbbde0eeed8f9a657c32f5d7ebde6993e3e3b213d` | Tap kartu, QR kamera, Gate, dan layar penuh |
| Verifikasi | `ev_5c5ba8dfd1e5aa5e824c56924af3175a344cfd6d83d475bbb26e61f4c4e8a22d` | Pencarian keaktifan siswa dan validasi alumni |
| Proposal mandiri | `ev_72b43c592716f2143b293090b845905ddcd81119efa28007da684b65ef84470e` | Contoh webinar, identitas siswa dan organisasi, panitia, RAB per item dan pelacakan realisasi |

Screenshot REA dengan menu terbuka memperlihatkan panel desktop putih tiga kolom di atas hero hijau gelap. Setiap layanan memiliki ikon, judul dan deskripsi singkat. Pada ponsel, panel membuka dari kanan dengan satu kolom, tombol login di bagian atas, dan kategori yang sama. Pada ukuran 1280 piksel beberapa item navigasi desktop turun ke baris kedua. Pada ponsel tombol chat menutupi sebagian area panel di dekat tautan BK. Kriteria untuk School Suite adalah navigasi tetap terbaca di lebar menengah dan tombol bantuan tidak menutupi tautan ketika panel terbuka.

Bukti screenshot REA desktop: `ev_dcf1b9ecf490c478a9c3db8dfe5a9684c28153c7733f2101c51754d804cc4bf8`. Bukti screenshot REA ponsel: `ev_2d5163fe468e9c81d9fb34a3d4cb97f68ccf85a0bf30e711cd9023b532ba2b31`.

REA CLI 6.7.0 membuka beranda di Edge tanpa login pada viewport 1280 × 720 dan merekam DOM, pohon aksesibilitas, serta URL. Kondisi awal menampilkan tombol E-Layanan dan tautan langsung PTSP Online serta Lacak Berkas. Bukti awal: `ev_a807c024713dd52a64be9895ebe7664ade750985d330670c8f9369aa1102a65a`.

Klik E-Layanan pada desktop membuka Persuratan & Tamu, Siswa & Alumni, dan Ketertiban & Sarana, dengan deskripsi singkat pada tautan layanan. Bukti: `ev_b91cf05957b6391071e760e0fe28c437b3dca849d3f7c60ef350aa17a9d72fbb`.

Klik tombol navigasi pada viewport 390 × 844 membuka panel dengan tiga kelompok layanan yang sama, tautan Buku Tamu Digital, dan akses Login Siswa. Bukti: `ev_e41eccff1ea7420e444b2df70cc40b13c8cce10a0c03fe5059de9fedb24f5f87`. Kriteria desain School Suite adalah layanan utama dapat ditemukan pada kedua ukuran layar dengan pengelompokan yang konsisten. 

Perbandingan lokal berdasarkan rute dan kode serta catatan proyek. Pemeriksaan acuan mencakup halaman publik, struktur formulir, sumber HTML, dan interaksi menu melalui REA. Alur yang memerlukan login dan visual halaman layanan selain beranda belum diverifikasi. Verifikasi siswa/alumni serta proposal/LPJ masuk inventaris setelah ditemukan di varian portal akademik. RDM menampilkan halaman login tersendiri. Ketiganya memerlukan konfirmasi terhadap instalasi klien sebelum estimasi akhir.

Setiap tahap siap diterima setelah alur utama, penolakan/koreksi, dan hak akses diperagakan dengan data contoh. Rilis mengikuti verifikasi aplikasi dan kesiapan operasional School Suite, termasuk pemulihan cadangan yang masih tercatat sebagai pekerjaan terbuka.

## Sumber

- [Beranda MAN 4 Banjar](https://man4banjar.com/)
- [Varian portal akademik MAN 4 Banjar](https://www.man4banjar.com/akademik/index.php)
- [PTSP](https://man4banjar.com/akademik/ptsp.php), [pelacakan](https://man4banjar.com/akademik/tracking.php), [surat izin](https://man4banjar.com/akademik/surat_izin.php), [legalisir](https://man4banjar.com/akademik/legalisir.php), [pengaduan](https://man4banjar.com/akademik/lapor.php), [perpustakaan](https://man4banjar.com/akademik/perpustakaan.php), dan [RDM](https://man4banjar.com/rdm/)
- [Status School Suite](PROJECT_STATUS.md), [pedoman desain](DESIGN.md), dan [catatan integrasi eMadrasah](EMADRASAH_INTEGRATION_TASKS.md)
