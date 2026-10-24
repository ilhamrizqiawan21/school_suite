# Requirements — School Suite

## Kebutuhan fungsional pilot

| ID | Kebutuhan | Bukti penerimaan minimum |
|---|---|---|
| SS-F-001 | Kelola sekolah, periode, akun, peran, dan izin | Matriks akses server-side lulus |
| SS-F-002 | Impor dan kelola siswa/guru dengan preview dan laporan invalid | Uji duplikat, pembatalan batch, dan histori |
| SS-F-003 | Kelola kelas, enrollment, mapel, penugasan, dan wali kelas per periode | Perpindahan periode tidak menghapus histori |
| SS-F-004 | Susun jadwal manual dan tolak benturan guru/ruang | Uji konflik dan publikasi draft |
| SS-F-005 | Catat absensi harian/pertemuan dan jurnal | Koreksi tercatat dalam audit |
| SS-F-006 | Kelola catatan wali kelas, prestasi, izin, dan tindak lanjut | Catatan terbatas tidak masuk export umum |
| SS-F-007 | Kelola skema nilai, rubrik, remedial, pembulatan, dan rapor | Contoh hitung manual dan snapshot cocok |
| SS-F-008 | Export laporan dengan filter periode dan hak akses | Pengguna tanpa izin ditolak |
| SS-F-009 | Backup, restore, dan migrasi versi | Restore terpisah dan upgrade diuji |

## Kebutuhan nonfungsional

- Otorisasi diterapkan pada server untuk baca, ubah, export, dan lampiran.
- Perubahan material menghasilkan audit actor, waktu, target, dan ringkasan perubahan.
- Lampiran disimpan privat; URL sementara dan hak unduh diperiksa.
- Operasi impor, export, dan notifikasi yang lama berjalan melalui antrean.
- Tampilan utama dapat digunakan dengan keyboard dan status tidak bergantung pada warna.
- Database, file, dan konfigurasi dapat dipulihkan menggunakan prosedur terdokumentasi.
- CI memeriksa format, analisis statis, unit, feature, dan kontrak penting.

## Kebutuhan integrasi Teacher Desk

Integrasi baru boleh dikerjakan setelah pilot kedua produk stabil. API harus berversi, idempotent, memakai identitas stabil, memetakan skema nilai, dan menerapkan ulang pemeriksaan izin/periode/versi secara atomik saat kiriman diterapkan.

## Traceability

Setiap task dan pull request menyebut ID requirement yang dipenuhi serta tes yang menjadi bukti. Status selesai tetap dimiliki task/issue dan persetujuan pemilik produk, bukan dokumen ini.

