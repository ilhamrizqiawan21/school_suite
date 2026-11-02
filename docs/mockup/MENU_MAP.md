# Peta Menu School Suite 2026

Peta ini disusun dari navigasi aktual pada `resources/js/components/SidebarNavigation.vue`.
Prototype mockup mengelompokkan menu panjang menjadi ruang kerja yang lebih mudah dipindai,
tanpa menghilangkan area fungsional yang sudah tersedia.

## Navigasi utama

| Ruang kerja | Menu | Fokus pengguna |
| --- | --- | --- |
| Beranda | Dashboard peran aktif | Ringkasan prioritas, agenda, metrik, dan pekerjaan berikutnya |
| SPMB | Dashboard SPMB; Periode & Juknis; Jalur & Kuota; Panitia & Jadwal; Pendaftar; Verifikasi Berkas; Seleksi & Ranking; Pengumuman; Daftar Ulang; Laporan & Arsip | Penerimaan murid baru dari periode sampai arsip |
| Akademik | Data Siswa; Buku Induk; Data Guru; Rombel/Kelas; Jadwal Pelajaran; Absensi Guru; Izin & Cuti Guru; Absensi Siswa; Rekap Absensi; Perangkat Pembelajaran; Kelas Daring; Siklus Siswa; Pengaturan Akademik | Data pokok, jadwal, presensi, dan administrasi akademik |
| Pembelajaran LMS | Materi; Tugas; Progress Belajar | Aktivitas belajar digital dan progres siswa |
| Asesmen & CBT | Bank Soal; Paket Soal; Berbagi Soal; Ujian/CBT | Penyusunan, berbagi, dan pelaksanaan asesmen digital |
| Penilaian & Rapor | Input Nilai; Penilaian Sikap; Skema Penilaian; Rapor Siswa; Analisis; Pusat Laporan; Kinerja Guru | Pengolahan nilai, analisis, dan penerbitan rapor |
| Perwalian | Ruang Wali Kelas; Catatan Observasi & BK; Kedisiplinan & Prestasi | Pendampingan kelas dan perkembangan siswa |
| Komunikasi | Pengumuman & Agenda; Diskusi Chat | Komunikasi lintas warga sekolah |
| Tata Usaha | Persuratan & Arsip; Tugas Staf; Sarana & Prasarana | Operasional non-akademik sekolah |
| Pengaturan | Profil Saya; Pengguna; Profil Sekolah; Struktur Organisasi; Berita Sekolah; Galeri Sekolah; Keamanan | Identitas, akses, konten publik, dan keamanan |

## Prinsip pengelompokan mockup

1. **Beranda** tetap menjadi titik masuk universal berdasarkan peran pengguna.
2. **SPMB, Akademik, LMS, Asesmen, dan Penilaian** menjadi alur inti yang ditempatkan berurutan sesuai siklus kerja sekolah.
3. **Perwalian dan Komunikasi** diletakkan setelah alur akademik karena keduanya menghubungkan data dengan tindakan warga sekolah.
4. **Tata Usaha dan Pengaturan** ditempatkan sebagai area operasional dan sistem, bukan dicampur dengan pekerjaan harian guru.
5. Menu yang memiliki banyak turunan tetap ditampilkan sebagai satu kelompok pada mockup, sehingga sidebar tidak menjadi daftar panjang yang melelahkan.

## Layar yang divalidasi di prototype

- **Beranda**: agenda, prioritas, detak kelas, dan status periode 2026/2027.
- **Data siswa**: pencarian, filter, pemilihan massal, kualitas data, dan responsif tabel.
- **Input nilai**: nilai mentah, remedial, catatan, status ketuntasan, serta penyimpanan draf.

Menu lain sudah dipetakan pada navigasi mockup sebagai arah arsitektur informasi; layar khususnya
belum diganti menjadi implementasi palsu agar keputusan desain tidak mengaburkan alur produksi.
