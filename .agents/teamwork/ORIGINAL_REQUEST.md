# Original User Request

## Initial Request — 2026-10-25T12:39:40Z

Perapihan, pelengkapan, dan pengelompokan menu navigasi sidebar serta alur kerja dashboard untuk role non-admin (Guru Pengajar, Wali Kelas, Siswa, Kepala Sekolah, dan Operator) pada School Suite dengan merujuk pada kapabilitas LMS Sekolah, Bank Soal Cerdas, dan modul penilaian.

Working directory: /home/ilhamzp/Projects/school_suite
Integrity mode: development

## Requirements

### R1. Restrukturisasi & Pelengkapan Navigasi Sidebar Non-Admin
Kelompokkan menu navigasi di `resources/js/components/SidebarNavigation.vue` ke dalam kategori domain yang terstruktur (Akademik, Pembelajaran LMS, Asesmen & CBT, Penilaian & Rapor, Perwalian, Komunikasi, Pengaturan) sesuai hak akses masing-masing role:
- **Guru (`teacher`)**: Akses lengkap ke Ruang Belajar LMS, Progress, Presensi, Jurnal, Kelas Daring, Bank Soal, Paket Soal, Berbagi Soal, Ujian CBT, Input Nilai, Penilaian Sikap, Rapor, Diskusi Chat.
- **Wali Kelas (`homeroom`)**: Seluruh menu guru ditambah Dashboard Wali Kelas, Catatan Observasi & BK, Kedisiplinan & Prestasi, serta WhatsApp Orang Tua.
- **Siswa (`student`)**: Jadwal Pelajaran, Ruang Belajar (materi & pengumpulan tugas), Kelas Daring, Progress Belajar Mandiri, Ujian CBT & Hasil, Rapor Siswa, Pengumuman, Diskusi Chat.
- **Kepala Sekolah (`principal`)**: Pusat Laporan & Ekspor, Analisis Asesmen, Monitoring Akademik (Siswa, Guru, Rombel, Jadwal, Siklus), Komunikasi, Profil & Keamanan.
- **Operator (`operator`)**: Manajemen Data Akademik, Penugasan Guru, Rombel, Jadwal, Bank Soal & CBT, Skema & Rapor, Pengguna & Sekolah.

### R2. Pengayaan Alur Kerja & Pintasan Dashboard Role Non-Admin
Lengkapi daftar workflow actions dan aksi cepat di `resources/js/Pages/Dashboard.vue` untuk masing-masing role non-admin (`teacherActions`, `homeroomActions`, `studentActions`, `principalActions`, `operatorActions`), memperbaiki rute yang salah (misal link laporan), serta menyelaraskan metrik dan copy fokus per peran.
Catatan penting: Pengguna (Admin) sedang mengedit menu khusus admin; jangan menimpa atau merusak bagian adminActions yang sedang dikerjakan admin.

### R3. Preservasi Boundary Arsitektur & Integritas Pengujian
Pertahankan modular monolith tanpa coupling antar model lintas modul, pastikan tidak ada route broken/404, serta verifikasi bahwa seluruh test suite backend (`php artisan test`) dan pemeriksaan tipe frontend (`npm run typecheck`, `npm run build`) tetap hijau 100%.

## Acceptance Criteria

### Integrasi Navigasi & Visibilitas Role
- [ ] Role Guru, Wali Kelas, Siswa, Kepsek, dan Operator dapat melihat menu yang relevan tanpa menu yang tidak diizinkan atau section heading kosong.
- [ ] Siswa dapat melihat Jadwal Pelajaran (`/akademik/jadwal`), Ruang Belajar (`/pembelajaran`), Ujian CBT (`/ujian`), dan Chat Kelas (`/komunikasi/chat`).
- [ ] Wali Kelas memiliki tautan langsung ke `/wali-kelas`, `/wali-kelas/catatan`, `/wali-kelas/kedisiplinan`, dan `/komunikasi/whatsapp`.
- [ ] Tidak ada tautan 404 (seperti `/penilaian/laporan` yang harus diarahkan ke `/laporan`).

### Kualitas Kode & CI
- [ ] `npm run typecheck` (`vue-tsc --noEmit`) lulus tanpa error.
- [ ] `npm run build` (`vite build`) berhasil.
- [ ] Seluruh unit dan feature tests (`php artisan test`) lulus 100%.
