# Product Requirements Document — School Suite

Status: Draft awal  
Versi: 0.1  
Tanggal: 24 Oktober 2026

## Visi

Menjadi ruang kerja sekolah yang memakai satu sumber data siswa untuk kegiatan akademik, pembelajaran, penilaian, dan administrasi, sehingga sekolah tidak perlu mengulang input antar aplikasi.

## Pengguna utama

Administrator, operator/TU, kepala sekolah, kurikulum, guru, wali kelas, BK/kesiswaan, pustakawan/sarpras, siswa, dan wali murid. Setiap pengguna hanya melihat sekolah, periode, kelas, dan data yang menjadi kewenangannya.

## Masalah yang diselesaikan

1. Data siswa, kelas, nilai, dan absensi tersebar di banyak aplikasi.
2. Guru dan wali kelas mengulang input untuk laporan yang berbeda.
3. Riwayat periode dan perubahan laporan sulit dilacak.
4. Hak akses, lampiran privat, dan penerbitan laporan belum konsisten.
5. Modul sekolah sulit dikembangkan tanpa merusak bagian lain.

## Sasaran pilot

- Impor siswa satu kali, lalu gunakan untuk kelas, absensi, jurnal, nilai, dan rapor.
- Guru menyelesaikan alur pertemuan dan penilaian hanya pada penugasannya.
- Wali kelas menyelesaikan rekap dan catatan siswa pada kelas perwaliannya.
- Rapor terbit memiliki snapshot, periode, formula, dan jejak perubahan.
- Backup dapat dipulihkan pada lingkungan terpisah.

## Cakupan rilis

### Pilot inti

Core akses dan audit; identitas sekolah; periode; siswa, guru, kelas, enrollment dan penugasan; jadwal manual; absensi; jurnal; wali kelas; skema nilai; rapor dan export.

### Setelah pilot

LMS, bank soal/CBT, SPMB, perpustakaan, surat/arsip, sarpras, pengumuman, penilaian khusus, dan API Teacher Desk.

### Di luar cakupan awal

Multi-tenant banyak sekolah, microservices, tabungan siswa, pembayaran otomatis, AI sebagai syarat alur utama, dan sinkronisasi langsung database.

## Indikator keberhasilan

- Tidak ada input ulang roster pada modul pilot.
- Tidak ada akses lintas kelas/peran pada skenario uji izin.
- Semua rumus pilot cocok dengan contoh hitung yang disetujui.
- Semua laporan terbit dapat direproduksi dari snapshot dan versi formula.
- Restore dan upgrade diuji tanpa kehilangan data.

Angka kapasitas dan target waktu respons dikunci setelah profil sekolah pilot diketahui.

## Risiko produk

- Scope terlalu luas: atasi dengan modul dan gate rilis.
- Rumus nilai berbeda antar sekolah: gunakan skema berversi, bukan formula tersembunyi.
- Data siswa sensitif: batasi akses, audit, penyimpanan privat, dan masa retensi.
- Reuse repo lama membawa utang/bug: lakukan inventaris dan uji perilaku per fitur.

