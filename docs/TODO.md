# Master Plan & Workflow Eksekusi: School Suite (End-to-End sampai Deploy)

> **Dokumen ini adalah rencana kerja komprehensif** yang memetakan seluruh siklus pengembangan School Suite mulai dari Fondasi (S01), Operasi Akademik & Penilaian Pilot (S02–S07), Deployment Production & Hardening (S08), hingga Ekspansi Modul Pasca-Pilot (S09–S14).

```mermaid
flowchart TD
    subgraph FONDASI ["TAHAP 1: FONDASI & CORE"]
        S01["S01: Fondasi Modular Monolith (SELESAI)"] --> S02["S02: Akses & Tata Kelola Core"]
        S02 --> S03["S03: Data Inti Akademik & Import Engine"]
    end

    subgraph AKADEMIK ["TAHAP 2: OPERASI SEKOLAH PILOT"]
        S03 --> S04["S04: Operasi Akademik Harian & Jadwal"]
        S04 --> S05["S05: Ruang Kerja Wali Kelas & Kesiswaan"]
        S05 --> S06["S06: Penilaian, Rapor & Formula Berversi"]
    end

    subgraph PILOT_DEPLOY ["TAHAP 3: PILOT & DEPLOYMENT"]
        S06 --> S07["S07: Persiapan Pilot, Backup & Observability"]
        S07 --> S08["S08: Deployment Production & Hardening"]
    end

    subgraph EKSPANSI ["TAHAP 4: EKSPANSI PASCA-PILOT"]
        S08 --> S09["S09–S13: LMS, CBT, SPMB, Perpustakaan, Sarpras"]
        S09 --> S14["S14: Integrasi Teacher Desk API"]
    end
```

---

## Ringkasan Roadmap & Gate Keluar

| Tahap | Fokus Utama | Target Gate Keluar | Status |
|---|---|---|---|
| **S01** | Fondasi Modular Monolith & Toolchain | Instalasi bersih, modular monolith terstruktur, CI hijau | **SELESAI (Commit `831b7e8`)** |
| **S02** | Akses & Tata Kelola Core | Matriks akses lulus server-side, audit trail aktif | **BERIKUTNYA** |
| **S03** | Data Inti Akademik & Import Engine | Histori enrollment teruji, validasi import lulus | Menunggu S02 |
| **S04** | Operasi Akademik Harian | Deteksi benturan jadwal & koreksi absensi teruji | Menunggu S03 |
| **S05** | Ruang Kerja Wali Kelas & Kesiswaan | Batas data privat lulus, rekap kelas teruji | Menunggu S04 |
| **S06** | Penilaian, Rapor & Formula Berversi | Contoh hitung manual cocok, snapshot rapor teruji | Menunggu S05 |
| **S07** | Persiapan Pilot & Observability | Drill restore terpisah sukses, SOP siap | Menunggu S06 |
| **S08** | Deployment Production & Hardening | Server production live, SSL/TLS, auto backup aktif | Menunggu S07 |
| **S09–S13** | Ekspansi Modul (LMS, CBT, SPMB, Sipus) | Gate kelulusan per modul mandiri | Pasca-Pilot |
| **S14** | Integrasi Teacher Desk | Idempotensi, sinkronisasi API teruji | Pasca-Stabil |

---

## Rincian Task & Acceptance Criteria

---

### [x] TAHAP S01: Fondasi Modular Monolith & Toolchain (SELESAI)

- [x] **S01-T01: Inisialisasi Git & Hygiene Baseline**
  - Inisialisasi repo git (`main`), `.gitignore` komprehensif, baseline dokumentasi di `docs/`.
  - *Bukti:* `git status` bersih, commit `f847ec4`.
- [x] **S01-T02: Bootstrap Laravel 13 + Inertia.js v3 + Vue 3 + TypeScript**
  - Framework Laravel 13 di PHP 8.5, Vite v8, Tailwind CSS v4, TypeScript (`tsconfig.json`).
  - *Bukti:* `php artisan test` passed, `npm run build` sukses (310ms), commit `9a1b855`.
- [x] **S01-T03: Struktur Direktori Modular Monolith & Shared Services**
  - 9 modul di `app/Modules/*` (4 lapis), 4 shared services di `app/Shared/*`, `ModularServiceProvider`.
  - *Bukti:* `ModularServiceProviderTest` passed (32 assertions), commit `5e21f8f`.
- [x] **S01-T04: Testing Toolchain & Architecture Boundary Test**
  - PHPUnit testsuites (`Unit`, `Feature`, `Architecture`, `Contracts`), AST Architecture Test.
  - *Bukti:* `ModularBoundaryTest` passed (mengunci larangan cross-module model coupling), commit `58638a1`.
- [x] **S01-T05: Base Application Shell & Navigation Layout (UX Base)**
  - Shell `AppLayout.vue`, `ActivePeriodBadge.vue` persisten, navigasi area kerja, accessibility skip link.
  - *Bukti:* Feature test dashboard passed, build frontend 409ms, commit `10cb176`.
- [x] **S01-T06: Verifikasi Gate S01 & Dokumentasi Handoff**
  - Seluruh CI hijau, audit dokumentasi 100%, handoff terdokumentasi.
  - *Bukti:* Commit `831b7e8`.

---

### [ ] TAHAP S02: Akses & Tata Kelola Core (`app/Modules/Core`)

> **Kebutuhan Terkait:** `SS-F-001`, `AI_RULES.md` (aturan 6: default deny policy server-side), `ARCHITECTURE.md`.  
> **Gate Keluar:** Matriks otorisasi server-side lulus, seluruh aksi material tercatat dalam audit log.

- [x] **S02-T01: Migrasi Skema Basis Data Core**
  - Tabel: `schools` (profil sekolah tunggal `SS-D-001`), `roles`, `permissions`, `role_permissions`, penambahan kolom `users` (`school_id`, `role_id`, `username`, `phone`, `is_active`), dan `audit_events`.
  - Model Eloquent di `app/Modules/Core/Infrastructure/Models/` (`School`, `Role`, `Permission`, `AuditEvent`) dan ekstensi relasi serta helper permission di `app/Models/User.php`.
  - Migrasi forward-safe dengan rollback drop table yang bersih.
  - *Bukti:* `php artisan migrate:fresh` dan `php artisan migrate:rollback` sukses; Feature test `CoreMigrationTest` (10 tests, 68 assertions) lulus 100%.
- [x] **S02-T02: Domain & Application Service Core (Otorisasi Server-Side)**
  - Entitas domain `RoleEnum`, `PermissionEnum` di `app/Modules/Core/Domain/`.
  - Kebijakan otorisasi server-side (Default Deny): `UserPolicy` dan `SchoolPolicy` di `app/Modules/Core/Application/Policies/` didaftarkan via `CoreServiceProvider`.
  - Service aplikasi `UserService` di `app/Modules/Core/Application/Services/` dengan pengecekan Gate eksplisit dan isolasi data per sekolah.
  - Aturan keamanan teruji: penolakan tanpa izin (403), isolasi akses lintas sekolah, larangan penghapusan akun sendiri oleh admin.
  - *Bukti:* Feature test `CoreAuthorizationTest` (6 tests, 21 assertions) lulus 100%; total testsuite: 16 passed, 89 assertions.
- [ ] **S02-T03: Engine Audit Log Terpusat (`app/Shared/Audit`)**
  - Service penangkap kejadian material: `actor_id`, `school_id`, `event_type`, `target_type`, `target_id`, `old_values`, `new_values`, `ip_address`, `user_agent`.
  - Sanitasi data otomatis (tidak mencatat password, secret, token, atau data sensitif pribadi).
  - *Bukti:* Audit recorded test saat terjadi perubahan profil atau status user.
- [ ] **S02-T04: Alur Autentikasi Pengguna & Sesi**
  - Login form berbasis email/username & password dengan rate limiting (brute force protection).
  - Logout, ganti password, proteksi session hijacking / CSRF token.
  - *Bukti:* Feature test authentication (login sukses, login gagal, rate limit 5x).
- [ ] **S02-T05: Antarmuka UI Manajemen Pengguna & Peran (Inertia + Vue)**
  - Halaman kelola user: pencarian, filter peran, reset password oleh admin, toggle status aktif.
  - Halaman konfigurasi identitas sekolah (Nama, NPSN, Alamat, Logo).
  - *Bukti:* UI render di `/pengaturan/pengguna` dan `/pengaturan/sekolah` dengan validasi form inline.
- [ ] **S02-T06: Verifikasi Gate S02 & Matriks Akses Multi-Peran**
  - Matriks pengujian akses untuk 6 peran: Administrator, Operator/TU, Kepala Sekolah, Guru, Wali Kelas, Siswa.
  - Perbarui `docs/HANDOFF.md` dan `docs/PROJECT_STATUS.md`.
  - *Bukti:* 100% skenario matriks akses lulus di test suite otomatis.

---

### [ ] TAHAP S03: Data Inti Akademik & Import Engine (`app/Modules/Academic`)

> **Kebutuhan Terkait:** `SS-F-002`, `SS-F-003`, `SS-D-004` (histori enrollment per periode), `ERD.md`.  
> **Gate Keluar:** Pergantian periode akademik tidak merusak histori enrollment; validasi import massal teruji (duplikat & rollback).

- [ ] **S03-T01: Migrasi Skema Basis Data Akademik**
  - Tabel: `academic_years`, `terms` (semester ganjil/genap), `subjects` (mata pelajaran), `teachers`, `students` (identitas orang), `class_groups` (rombongan belajar), `enrollments` (penempatan siswa di kelas per semester), `teaching_assignments`.
  - Kunci relasi: `students` tidak memiliki kolom `class_id` langsung; hubungan kelas ditentukan via `enrollments` terikat pada `term_id`.
  - *Bukti:* Migrasi database lulus uji integritas referensial.
- [ ] **S03-T02: Domain & Service Enrollment Bersejarah (`SS-D-004`)**
  - Service pendaftaran siswa ke rombel per semester aktif.
  - Service perpindahan semester / kenaikan kelas yang mempertahankan data semester sebelumnya (immutable history).
  - *Bukti:* Test perpindahan tahun ajaran membuktikan siswa kelas 7 tahun 2025 tetap tercatat di kelas 7 pada semester lama saat naik ke kelas 8 di semester baru.
- [ ] **S03-T03: Manajemen Penugasan Mengajar & Penugasan Wali Kelas**
  - Penetapan guru mata pelajaran pada rombel tertentu di semester aktif.
  - Penetapan guru sebagai wali kelas pada rombel tertentu di semester aktif.
  - *Bukti:* Service assignment memvalidasi keunikan wali kelas per rombel per periode.
- [ ] **S03-T04: Engine Impor Massal Siswa & Guru (Excel / CSV)**
  - Pipeline impor: Upload berkas → pemetaan kolom → validasi format/NIS/NISN → preview kesalahan baris → konfirmasi batch.
  - Transaksional atomik: Jika ada opsi batalkan batch, seluruh batch di-rollback tanpa sisa data yatim.
  - *Bukti:* Test import file sintetis: data duplikat ditolak dengan laporan baris invalid; data valid tersimpan sempurna.
- [ ] **S03-T05: Antarmuka UI Manajemen Data Inti (Inertia + Vue)**
  - Tampilan daftar siswa dan guru dengan filter rombel dan status aktif.
  - Modal/layar alur impor dengan feedback status interaktif dan unduhan template impor.
  - Tampilan manajemen kelas & anggota rombel per periode.
  - *Bukti:* Komponen Vue modular di `resources/js/modules/academic/`.
- [ ] **S03-T06: Verifikasi Gate S03 & Rekonsiliasi Data**
  - Eksekusi test suite data integrity: uji duplikasi NIS/NISN, uji histori kenaikan kelas, uji rollback import gagal.
  - Perbarui `docs/HANDOFF.md` dan `docs/PROJECT_STATUS.md`.

---

### [ ] TAHAP S04: Operasi Akademik Harian & Jadwal (`app/Modules/Academic`)

> **Kebutuhan Terkait:** `SS-F-004`, `SS-F-005`, `DESIGN.md`.  
> **Gate Keluar:** Deteksi benturan jadwal ruangan & guru teruji; audit log koreksi absensi tercatat.

- [ ] **S04-T01: Migrasi Skema Jadwal, Presensi, dan Jurnal**
  - Tabel: `schedules` (jadwal pelajaran per slot waktu, hari, ruang), `lesson_sessions` (pertemuan pembelajaran aktual), `attendance_records` (presensi harian & pertemuan: Hadir, Sakit, Izin, Alpa), `teaching_journals` (catatan materi, tujuan, dan dinamika kelas).
  - *Bukti:* Skema database mendukung pembedaan presensi harian sekolah vs presensi per jam pelajaran.
- [ ] **S04-T02: Engine Validasi Benturan Jadwal (Conflict Detection)**
  - Aturan: Guru tidak boleh dijadwalkan di 2 kelas berbeda pada slot jam yang sama.
  - Aturan: Ruang kelas / lab tidak boleh digunakan oleh 2 mata pelajaran bersamaan.
  - Status draft jadwal vs jadwal terbit (*published*).
  - *Bukti:* Unit test mendeteksi benturan guru dan benturan ruangan saat input jadwal.
- [ ] **S04-T03: Service Presensi Siswa & Jurnal Pembelajaran**
  - Pencatatan presensi siswa secara massal per pertemuan.
  - Pengisian jurnal mengajar guru terhubung langsung dengan sesi pelajaran.
  - Audit log otomatis saat terjadi perubahan/koreksi status presensi setelah disimpan.
  - *Bukti:* Test koreksi absensi membuktikan rekam jejak pengubah, waktu, dan alasan koreksi tercatat di audit log.
- [ ] **S04-T04: UI Input Presensi Cepat & Grid Jadwal Guru (Mobile/Tablet Friendly)**
  - Layar input absensi cepat dengan toggle satu klik (Hadir Semua, Sakit, Izin, Alpa).
  - Tampilan grid kalender jadwal mingguan untuk guru dan ruang.
  - Status visual yang jelas tanpa hanya bergantung pada warna (ada label dan ikon).
  - *Bukti:* Antarmuka Vue responsif dan dapat dioperasikan penuh dengan keyboard.
- [ ] **S04-T05: Rekapitulasi Presensi Periodik**
  - Service agregasi presensi: persentase kehadiran per siswa, per kelas, dan per periode.
  - Export rekap presensi format PDF dan Excel.
  - *Bukti:* Perhitungan agregasi presensi diverifikasi dengan tabel kalkulasi sintetis.
- [ ] **S04-T06: Verifikasi Gate S04 & Stress Testing Skenario Benturan**
  - Uji konflik jadwal, uji izin akses absensi (hanya guru pengajar atau admin yang dapat mengisi), uji audit log koreksi.
  - Perbarui `docs/HANDOFF.md` dan `docs/PROJECT_STATUS.md`.

---

### [ ] TAHAP S05: Ruang Kerja Wali Kelas & Kesiswaan (`app/Modules/Homeroom`)

> **Kebutuhan Terkait:** `SS-F-006`, `AI_RULES.md` (aturan 4 & 6: privasi data siswa), `DESIGN.md`.  
> **Gate Keluar:** Batas privasi data catatan kesiswaan lulus; catatan terbatas terisolasi dari ekspor umum.

- [ ] **S05-T01: Migrasi Skema Homeroom & Catatan Kesiswaan**
  - Tabel: `student_observations` (catatan perkembangan, sikap positif, pembinaan), `student_incidents` (pelanggaran/kejadian khusus), `follow_up_actions` (tindak lanjut, panggilan wali murid), `attendance_dispensations` (surat dokter, dispensasi lomba).
  - Klasifikasi visibilitas: `Publik` (masuk rapor/wali murid), `Internal Wali Kelas`, dan `Rahasia/BK` (hanya guru BK dan Kepala Sekolah).
  - *Bukti:* Database constraint dan foreign key terikat pada `enrollment_id`.
- [ ] **S05-T02: Policy Otorisasi Catatan Siswa Sensitif**
  - Policy server-side ketat: Guru biasa dilarang mengakses catatan berklasifikasi `Rahasia/BK`.
  - Wali kelas hanya berhak melihat catatan siswa di rombel perwaliannya pada semester aktif.
  - Filter otomatis ekspor: Catatan rahasia dilarang keras ikut terbawa ke dalam ekspor umum rapor atau ledger akademik.
  - *Bukti:* Feature test otorisasi: percobaan akses catatan rahasia oleh non-BK menghasilkan 403 Forbidden.
- [ ] **S05-T03: Rekap Kesiapan Siswa Perwalian**
  - Dashboard agregat wali kelas: kelengkapan nilai siswa, tren absensi, status dispensasi aktif, catatan pembinaan berjalan.
  - *Bukti:* Query terisolasi tanpa direct join ke tabel internal modul lain.
- [ ] **S05-T04: UI Ruang Kerja Wali Kelas (Inertia + Vue)**
  - Tampilan rekap komprehensif rombel perwalian.
  - Form input catatan pembinaan dengan pemilih tingkat klasifikasi privasi.
  - Timeline perkembangan siswa per individu.
  - *Bukti:* Layar di `resources/js/modules/homeroom/`.
- [ ] **S05-T05: Verifikasi Gate S05 & Uji Kebocoran Privasi**
  - Audit kebocoran data: memastikan ekspor rapor/rekap umum tidak pernah menampilkan data catatan privat.
  - Perbarui `docs/HANDOFF.md` dan `docs/PROJECT_STATUS.md`.

---

### [ ] TAHAP S06: Penilaian, Rapor & Formula Berversi (`app/Modules/Assessment`)

> **Kebutuhan Terkait:** `SS-F-007`, `SS-D-005` (snapshot rapor immutable), `AI_RULES.md` (aturan 7: pengujian formula lengkap).  
> **Gate Keluar:** Contoh hitung manual cocok 100% dengan formula berversi; snapshot rapor terbit immutable dan dapat direproduksi.

- [ ] **S06-T01: Migrasi Skema Penilaian & Rapor**
  - Tabel: `assessment_schemes` (skema penilaian berversi), `assessment_components` (tugas, formatif, sumatif, UTS, UAS, bobot %), `scores` (nilai per komponen per enrollment), `remedial_records` (nilai sebelum remedial, nilai sesudah remedial, batas KKM/KKTP), `report_snapshots` (data rapor terbit dalam format JSON snapshot yang immutable).
  - *Bukti:* Skema mendukung versioning formula penilaian.
- [ ] **S06-T02: Engine Penghitungan Nilai & Remedial Berversi**
  - Implementasi formula penilaian murni di `Domain` layer:
    - Dukungan bobot persentase komponen dinamis.
    - Aturan pembulatan baku (misal: 2 desimal atau pembulatan terdekat).
    - Logika remedial: nilai remedial tidak boleh melebihi nilai batas ketuntasan minimum (KKTP/KKM).
  - *Bukti:* Table-driven tests mencakup seluruh skenario batas: nilai nol, kosong (tidak ikut ujian), nilai sempurna (100), pembulatan pecahan `.5`, dan nilai remedial.
- [ ] **S06-T03: Penerbitan Rapor & Immutable Snapshot Engine (`SS-D-005`)**
  - Service penerbitan rapor: Mengambil seluruh nilai akhir, catatan wali kelas, kehadiran, dan menyimpannya sebagai payload snapshot utuh di tabel `report_snapshots`.
  - Begitu status rapor menjadi `TERBIT` (*published*), data terkunci permanen dan tidak terpengaruh jika skema nilai diubah di kemudian hari.
  - *Bukti:* Test membuktikan bahwa setelah rapor terbit, pengubahan nilai mentah di tabel `scores` tidak mengubah nilai pada cetakan rapor snapshot.
- [ ] **S06-T04: Generator Cetak Rapor & Ekspor Ledger Nilai (PDF / Excel)**
  - Template cetak rapor standar (Halaman Depan, Identitas Siswa, Capaian Akademik, Catatan Sikap, Tanda Tangan).
  - Ekspor ledger nilai per kelas dengan filter periode aktif.
  - Validasi otorisasi cetak: Hanya wali kelas, kepala sekolah, dan siswa bersangkutan (rapor pribadi) yang dapat mengunduh.
  - *Bukti:* Uji reproduksi file cetak rapor dari snapshot menghasilkan output identik bit-for-bit.
- [ ] **S06-T05: UI Input Nilai Guru & Verifikasi Rapor Wali Kelas**
  - Spreadsheet-like score input grid untuk guru (navigasi keyboard cepat dengan tombol panah/Enter).
  - Layar review kelengkapan nilai rombel untuk wali kelas sebelum pengajuan penerbitan rapor.
  - Layar pengesahan dan penerbitan rapor oleh Kepala Sekolah.
  - *Bukti:* Antarmuka Vue di `resources/js/modules/assessment/`.
- [ ] **S06-T06: Verifikasi Gate S06 & Reproducibility Test**
  - Uji kesesuaian formula dengan contoh hitung manual dari sekolah pilot.
  - Perbarui `docs/HANDOFF.md` dan `docs/PROJECT_STATUS.md`.

---

### [ ] TAHAP S07: Persiapan Pilot, Backup/Restore & Observability

> **Kebutuhan Terkait:** `SS-F-008`, `SS-F-009`, `AI_RULES.md` (aturan 4 & 5), `DECISIONS.md`.  
> **Gate Keluar:** Drill backup dan restore pada lingkungan terpisah sukses tanpa kehilangan data; health check aktif.

- [ ] **S07-T01: Manajemen Penyimpanan Privat & Signed URL Handler (`app/Shared/Files`)**
  - Penyimpanan dokumen siswa, berkas foto, dan lampiran di disk privat non-public (`storage/app/private`).
  - Controller pengunduhan berkas aman yang memvalidasi otorisasi dan masa kedaluwarsa URL bertanda tangan (Signed URL).
  - *Bukti:* Percobaan unduhan tanpa token/izin menghasilkan 403 Forbidden.
- [ ] **S07-T02: Konfigurasi Queue Worker & Background Jobs**
  - Konfigurasi database/Redis queue worker untuk operasi berat: pengolahan impor massal, generasi massal PDF rapor, dan pengiriman notifikasi.
  - Penanganan kegagalan job (*failed_jobs*) dengan mekanisme retry otomatis dan log alasan gagal.
  - *Bukti:* Test antrean memproses batch generasi PDF secara asinkron tanpa blocking web server.
- [ ] **S07-T03: Skrip Otomasi Backup Mandiri & Verifikasi Drill Restore**
  - Perintah artisan kustom `php artisan school:backup` yang mem-backup database dan arsip file privat ke format tar.gz terenkripsi.
  - Perintah `php artisan school:restore` dengan pengujian drill restore pada instance database terpisah.
  - Dokumentasi prosedur pemulihan bencana (*Disaster Recovery Runbook*) di `docs/RUNBOOK_BACKUP_RESTORE.md`.
  - *Bukti:* Simulasi penghapusan data dan pemulihan penuh membuktikan 100% data kembali utuh.
- [ ] **S07-T04: Observability, Health Check, & Sanitasi Logging**
  - Endpoint kesehatan sistem `/up` yang memvalidasi koneksi database, kesiapan disk storage, dan ketersediaan queue worker.
  - Konfigurasi logging terstruktur (Monolog JSON format) yang memfilter parameter sensitif (`password`, `token`, `nisn`, `data_siswa`).
  - *Bukti:* Uji endpoint `/up` merespons status `200 OK` dengan metrik kesehatan subsistem.
- [ ] **S07-T05: Seeder Data Sintetis Sekolah Pilot & Panduan Pengguna**
  - Seeder data sintetis komprehensif: 1 sekolah, 2 tahun ajaran, 12 kelas, 360 siswa sintetis, 25 guru, jadwal, dan nilai simulasi.
  - Panduan operasional singkat untuk pengguna sekolah pilot di `docs/USER_GUIDE_PILOT.md`.
  - *Bukti:* `php artisan db:seed` berhasil mengisi data siap uji tanpa error.
- [ ] **S07-T06: Verifikasi Gate S07 & Sign-Off Kesiapan Pilot**
  - Audit menyeluruh: semua modul pilot (S01–S07) teruji, drill restore berhasil, panduan lengkap.
  - Perbarui `docs/HANDOFF.md` dan `docs/PROJECT_STATUS.md`.

---

### [ ] TAHAP S08: Deployment Production & Server Hardening

> **Kebutuhan Terkait:** Target operasional satu sekolah per instalasi (`SS-D-001`, `SS-D-003`).  
> **Gate Keluar:** Aplikasi ter-deploy live di server production, terenkripsi HTTPS, process manager aktif, auto backup terjadwal, dan CI/CD deployment teruji.

- [ ] **S08-T01: Provisi Database Server Production (MySQL 8.x / MariaDB)**
  - Konfigurasi koneksi MySQL production di `.env`: `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
  - Optimasi parameter database: `charset=utf8mb4`, `collation=utf8mb4_unicode_ci`, connection pooling, slow query log.
  - *Bukti:* Migrasi database berhasil dijalankan di server MySQL target.
- [ ] **S08-T02: Konfigurasi Reverse Proxy Web Server (Nginx / Caddy)**
  - Template konfigurasi Nginx production:
    - Root directory mengarah ke `/public`.
    - Blokir akses ke file tersembunyi (`.env`, `.git`).
    - Gzip / Brotli compression untuk static assets.
    - Sertifikat SSL/TLS otomatis via Let's Encrypt (Certbot / Caddy automatic HTTPS) dengan pengalihan paksa HTTP → HTTPS.
  - *Bukti:* File konfigurasi disimpan di `deploy/nginx/school-suite.conf`.
- [ ] **S08-T03: Konfigurasi Runtime PHP-FPM & OPcache Hardening**
  - Konfigurasi PHP-FPM pool: penyesuaian `pm = ondemand/dynamic`, `max_children`, `memory_limit = 256M`, `upload_max_filesize = 20M`.
  - Optimasi OPcache: `opcache.enable=1`, `opcache.memory_consumption=128`, `opcache.validate_timestamps=0` (untuk production).
  - *Bukti:* File konfigurasi `deploy/php/opcache.ini` dan `deploy/php/www.conf`.
- [ ] **S08-T04: Konfigurasi Process Manager & Cron Scheduler**
  - Setup Systemd Service / Supervisor untuk queue worker: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`.
  - Setup crontab sistem untuk scheduler Laravel: `* * * * * cd /var/www/school_suite && php artisan schedule:run >> /dev/null 2>&1`.
  - *Bukti:* File unit systemd disimpan di `deploy/systemd/school-suite-worker.service`.
- [ ] **S08-T05: Security Hardening & Environment Lockdown**
  - Kunci file environment: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`.
  - Header keamanan HTTP: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Strict-Transport-Security`, `Content-Security-Policy`.
  - Izin direktori aman di Linux: `chown -R www-data:www-data`, chmod 755 direktori, chmod 644 file, chmod 775 `storage/` dan `bootstrap/cache/`.
  - *Bukti:* Pemindaian keamanan lokal membuktikan `APP_DEBUG` mati dan header proteksi aktif.
- [ ] **S08-T06: Skrip Otomasi Deployment (Zero-Downtime Deployment)**
  - Script deployment (`deploy/deploy.sh`):
    1. Clone/pull commit terbaru ke rilis baru.
    2. Jalankan `composer install --no-dev --optimize-autoloader`.
    3. Jalankan `npm ci && npm run build`.
    4. Jalankan `php artisan migrate --force`.
    5. Cache konfigurasi, routes, dan views (`php artisan config:cache`, `route:cache`, `view:cache`).
    6. Switch symlink rilis ke direktori live.
    7. Restart queue worker dan reload PHP-FPM.
  - *Bukti:* Eksekusi simulasi deployment script selesai tanpa downtime.
- [ ] **S08-T07: Disaster Recovery, Rollback Script & Uji Rilis Akhir**
  - Skrip rollback otomatis (`deploy/rollback.sh`) untuk kembali ke rilis sebelumnya jika health check `/up` gagal pasca-deploy.
  - Penjadwalan backup harian via cron ke cold storage / storage terpisah.
  - Verifikasi akhir penerimaan deployment: seluruh fungsionalitas pilot berjalan lancar di lingkungan live.
  - *Bukti:* Deployment checklist tertanda tangani di `docs/DEPLOYMENT_CHECKLIST.md`.

---

### [ ] TAHAP S09–S13: Ekspansi Modul Sekolah (Pasca-Pilot)

> Dikerjakan secara bertahap setelah sekolah pilot stabil menggunakan sistem inti.

- [ ] **S09: Modul Pembelajaran / LMS (`app/Modules/Learning`)**
  - Bahan ajar digital (PDF, video, link referensi).
  - Penugasan siswa dengan batas waktu pengumpulan (*deadline*).
  - Pengunggahan berkas tugas oleh siswa dan penilaian/feedback guru.
- [ ] **S10: Modul Ujian / CBT (`app/Modules/Assessment`)**
  - Bank soal (Pilihan ganda, esai, menjodohkan).
  - Sesi ujian dengan token akses, pengacakan nomor soal/opsi, dan timer interaktif.
  - Analisis butir soal dan integrasi hasil ujian otomatis ke daftar nilai.
- [ ] **S11: Modul Penerimaan Siswa Baru / SPMB (`app/Modules/Admissions`)**
  - Formulir pendaftaran online calon siswa.
  - Unggah dan verifikasi berkas persyaratan (Akta, KK, Ijazah).
  - Pengumuman seleksi dan alur daftar ulang otomatis menjadi data siswa di `Academic`.
- [ ] **S12: Modul Perpustakaan / Sipus (`app/Modules/Library`)**
  - Katalog buku (ISBN, pengarang, penerbit, nomor klasifikasi).
  - Manajemen eksemplar fisik & cetak barcode buku/kartu anggota.
  - Sirkulasi peminjaman, pengembalian, perpanjangan, dan denda keterlambatan.
- [ ] **S13: Modul Tata Usaha & Sarana Prasarana (`app/Modules/Administration`)**
  - Agenda surat masuk dan surat keluar dengan penomoran otomatis.
  - Kearsipan dokumen digital sekolah.
  - Inventaris sarana prasarana, status kondisi barang, dan pelaporan perbaikan.

---

### [ ] TAHAP S14: Integrasi Teacher Desk (`app/Modules/Integration`)

> **Prinsip Kunci:** `SS-D-007` (Dilarang keras memakai database bersama; wajib menggunakan REST API berversi yang idempotent).

- [ ] **S14-T01: Desain Kontrak & Spesifikasi OpenAPI Teacher Desk**
  - Definisi endpoint: otentikasi perangkat, pertukaran payload nilai/absensi offline, rekonsiliasi timestamp.
- [ ] **S14-T02: Engine Idempotensi & Inbox Submission**
  - Penyimpanan request dengan `idempotency_key` untuk mencegah input ganda saat perangkat reconnect.
  - Inbox validasi transaksi sebelum data diterapkan ke database akademik.
- [ ] **S14-T03: Uji Resolusi Konflik & Penarikan Izin**
  - Penanganan kasus konflik (misal: data diubah di web dan di desktop bersamaan).
  - Penolakan sinkronisasi jika token guru dicabut atau periode akademik telah dikunci.
  - *Bukti:* Test suite integrasi `tests/Feature/IntegrationTest.php` lulus 100%.

- [ ] **S04-T01: Migrasi Skema Jadwal, Presensi, dan Jurnal**
  - Tabel: `schedules` (jadwal pelajaran per slot waktu, hari, ruang), `lesson_sessions` (pertemuan pembelajaran aktual), `attendance_records` (presensi harian & pertemuan: Hadir, Sakit, Izin, Alpa), `teaching_journals` (catatan materi, tujuan, dan dinamika kelas).
  - *Bukti:* Skema database mendukung pembedaan presensi harian sekolah vs presensi per jam pelajaran.
- [ ] **S04-T02: Engine Validasi Benturan Jadwal (Conflict Detection)**
  - Aturan: Guru tidak boleh dijadwalkan di 2 kelas berbeda pada slot jam yang sama.
  - Aturan: Ruang kelas / lab tidak boleh digunakan oleh 2 mata pelajaran bersamaan.
  - Status draft jadwal vs jadwal terbit (*published*).
  - *Bukti:* Unit test mendeteksi benturan guru dan benturan ruangan saat input jadwal.
- [ ] **S04-T03: Service Presensi Siswa & Jurnal Pembelajaran**
  - Pencatatan presensi siswa secara massal per pertemuan.
  - Pengisian jurnal mengajar guru terhubung langsung dengan sesi pelajaran.
  - Audit log otomatis saat terjadi perubahan/koreksi status presensi setelah disimpan.
  - *Bukti:* Test koreksi absensi membuktikan rekam jejak pengubah, waktu, dan alasan koreksi tercatat di audit log.
- [ ] **S04-T04: UI Input Presensi Cepat & Grid Jadwal Guru (Mobile/Tablet Friendly)**
  - Layar input absensi cepat dengan toggle satu klik (Hadir Semua, Sakit, Izin, Alpa).
  - Tampilan grid kalender jadwal mingguan untuk guru dan ruang.
  - Status visual yang jelas tanpa hanya bergantung pada warna (ada label dan ikon).
  - *Bukti:* Antarmuka Vue responsif dan dapat dioperasikan penuh dengan keyboard.
- [ ] **S04-T05: Rekapitulasi Presensi Periodik**
  - Service agregasi presensi: persentase kehadiran per siswa, per kelas, dan per periode.
  - Export rekap presensi format PDF dan Excel.
  - *Bukti:* Perhitungan agregasi presensi diverifikasi dengan tabel kalkulasi sintetis.
- [ ] **S04-T06: Verifikasi Gate S04 & Stress Testing Skenario Benturan**
  - Uji konflik jadwal, uji izin akses absensi (hanya guru pengajar atau admin yang dapat mengisi), uji audit log koreksi.
  - Perbarui `docs/HANDOFF.md` dan `docs/PROJECT_STATUS.md`.

---

### [ ] TAHAP S05: Ruang Kerja Wali Kelas & Kesiswaan (`app/Modules/Homeroom`)

> **Kebutuhan Terkait:** `SS-F-006`, `AI_RULES.md` (aturan 4 & 6: privasi data siswa), `DESIGN.md`.  
> **Gate Keluar:** Batas privasi data catatan kesiswaan lulus; catatan terbatas terisolasi dari ekspor umum.

- [ ] **S05-T01: Migrasi Skema Homeroom & Catatan Kesiswaan**
  - Tabel: `student_observations` (catatan perkembangan, sikap positif, pembinaan), `student_incidents` (pelanggaran/kejadian khusus), `follow_up_actions` (tindak lanjut, panggilan wali murid), `attendance_dispensations` (surat dokter, dispensasi lomba).
  - Klasifikasi visibilitas: `Publik` (masuk rapor/wali murid), `Internal Wali Kelas`, dan `Rahasia/BK` (hanya guru BK dan Kepala Sekolah).
  - *Bukti:* Database constraint dan foreign key terikat pada `enrollment_id`.
- [ ] **S05-T02: Policy Otorisasi Catatan Siswa Sensitif**
  - Policy server-side ketat: Guru biasa dilarang mengakses catatan berklasifikasi `Rahasia/BK`.
  - Wali kelas hanya berhak melihat catatan siswa di rombel perwaliannya pada semester aktif.
  - Filter otomatis ekspor: Catatan rahasia dilarang keras ikut terbawa ke dalam ekspor umum rapor atau ledger akademik.
  - *Bukti:* Feature test otorisasi: percobaan akses catatan rahasia oleh non-BK menghasilkan 403 Forbidden.
- [ ] **S05-T03: Rekap Kesiapan Siswa Perwalian**
  - Dashboard agregat wali kelas: kelengkapan nilai siswa, tren absensi, status dispensasi aktif, catatan pembinaan berjalan.
  - *Bukti:* Query terisolasi tanpa direct join ke tabel internal modul lain.
- [ ] **S05-T04: UI Ruang Kerja Wali Kelas (Inertia + Vue)**
  - Tampilan rekap komprehensif rombel perwalian.
  - Form input catatan pembinaan dengan pemilih tingkat klasifikasi privasi.
  - Timeline perkembangan siswa per individu.
  - *Bukti:* Layar di `resources/js/modules/homeroom/`.
- [ ] **S05-T05: Verifikasi Gate S05 & Uji Kebocoran Privasi**
  - Audit kebocoran data: memastikan ekspor rapor/rekap umum tidak pernah menampilkan data catatan privat.
  - Perbarui `docs/HANDOFF.md` dan `docs/PROJECT_STATUS.md`.

---

### [ ] TAHAP S06: Penilaian, Rapor & Formula Berversi (`app/Modules/Assessment`)

> **Kebutuhan Terkait:** `SS-F-007`, `SS-D-005` (snapshot rapor immutable), `AI_RULES.md` (aturan 7: pengujian formula lengkap).  
> **Gate Keluar:** Contoh hitung manual cocok 100% dengan formula berversi; snapshot rapor terbit immutable dan dapat direproduksi.

- [ ] **S06-T01: Migrasi Skema Penilaian & Rapor**
  - Tabel: `assessment_schemes` (skema penilaian berversi), `assessment_components` (tugas, formatif, sumatif, UTS, UAS, bobot %), `scores` (nilai per komponen per enrollment), `remedial_records` (nilai sebelum remedial, nilai sesudah remedial, batas KKM/KKTP), `report_snapshots` (data rapor terbit dalam format JSON snapshot yang immutable).
  - *Bukti:* Skema mendukung versioning formula penilaian.
- [ ] **S06-T02: Engine Penghitungan Nilai & Remedial Berversi**
  - Implementasi formula penilaian murni di `Domain` layer:
    - Dukungan bobot persentase komponen dinamis.
    - Aturan pembulatan baku (misal: 2 desimal atau pembulatan terdekat).
    - Logika remedial: nilai remedial tidak boleh melebihi nilai batas ketuntasan minimum (KKTP/KKM).
  - *Bukti:* Table-driven tests mencakup seluruh skenario batas: nilai nol, kosong (tidak ikut ujian), nilai sempurna (100), pembulatan pecahan `.5`, dan nilai remedial.
- [ ] **S06-T03: Penerbitan Rapor & Immutable Snapshot Engine (`SS-D-005`)**
  - Service penerbitan rapor: Mengambil seluruh nilai akhir, catatan wali kelas, kehadiran, dan menyimpannya sebagai payload snapshot utuh di tabel `report_snapshots`.
  - Begitu status rapor menjadi `TERBIT` (*published*), data terkunci permanen dan tidak terpengaruh jika skema nilai diubah di kemudian hari.
  - *Bukti:* Test membuktikan bahwa setelah rapor terbit, pengubahan nilai mentah di tabel `scores` tidak mengubah nilai pada cetakan rapor snapshot.
- [ ] **S06-T04: Generator Cetak Rapor & Ekspor Ledger Nilai (PDF / Excel)**
  - Template cetak rapor standar (Halaman Depan, Identitas Siswa, Capaian Akademik, Catatan Sikap, Tanda Tangan).
  - Ekspor ledger nilai per kelas dengan filter periode aktif.
  - Validasi otorisasi cetak: Hanya wali kelas, kepala sekolah, dan siswa bersangkutan (rapor pribadi) yang dapat mengunduh.
  - *Bukti:* Uji reproduksi file cetak rapor dari snapshot menghasilkan output identik bit-for-bit.
- [ ] **S06-T05: UI Input Nilai Guru & Verifikasi Rapor Wali Kelas**
  - Spreadsheet-like score input grid untuk guru (navigasi keyboard cepat dengan tombol panah/Enter).
  - Layar review kelengkapan nilai rombel untuk wali kelas sebelum pengajuan penerbitan rapor.
  - Layar pengesahan dan penerbitan rapor oleh Kepala Sekolah.
  - *Bukti:* Antarmuka Vue di `resources/js/modules/assessment/`.
- [ ] **S06-T06: Verifikasi Gate S06 & Reproducibility Test**
  - Uji kesesuaian formula dengan contoh hitung manual dari sekolah pilot.
  - Perbarui `docs/HANDOFF.md` dan `docs/PROJECT_STATUS.md`.

---

### [ ] TAHAP S07: Persiapan Pilot, Backup/Restore & Observability

> **Kebutuhan Terkait:** `SS-F-008`, `SS-F-009`, `AI_RULES.md` (aturan 4 & 5), `DECISIONS.md`.  
> **Gate Keluar:** Drill backup dan restore pada lingkungan terpisah sukses tanpa kehilangan data; health check aktif.

- [ ] **S07-T01: Manajemen Penyimpanan Privat & Signed URL Handler (`app/Shared/Files`)**
  - Penyimpanan dokumen siswa, berkas foto, dan lampiran di disk privat non-public (`storage/app/private`).
  - Controller pengunduhan berkas aman yang memvalidasi otorisasi dan masa kedaluwarsa URL bertanda tangan (Signed URL).
  - *Bukti:* Percobaan unduhan tanpa token/izin menghasilkan 403 Forbidden.
- [ ] **S07-T02: Konfigurasi Queue Worker & Background Jobs**
  - Konfigurasi database/Redis queue worker untuk operasi berat: pengolahan impor massal, generasi massal PDF rapor, dan pengiriman notifikasi.
  - Penanganan kegagalan job (*failed_jobs*) dengan mekanisme retry otomatis dan log alasan gagal.
  - *Bukti:* Test antrean memproses batch generasi PDF secara asinkron tanpa blocking web server.
- [ ] **S07-T03: Skrip Otomasi Backup Mandiri & Verifikasi Drill Restore**
  - Perintah artisan kustom `php artisan school:backup` yang mem-backup database dan arsip file privat ke format tar.gz terenkripsi.
  - Perintah `php artisan school:restore` dengan pengujian drill restore pada instance database terpisah.
  - Dokumentasi prosedur pemulihan bencana (*Disaster Recovery Runbook*) di `docs/RUNBOOK_BACKUP_RESTORE.md`.
  - *Bukti:* Simulasi penghapusan data dan pemulihan penuh membuktikan 100% data kembali utuh.
- [ ] **S07-T04: Observability, Health Check, & Sanitasi Logging**
  - Endpoint kesehatan sistem `/up` yang memvalidasi koneksi database, kesiapan disk storage, dan ketersediaan queue worker.
  - Konfigurasi logging terstruktur (Monolog JSON format) yang memfilter parameter sensitif (`password`, `token`, `nisn`, `data_siswa`).
  - *Bukti:* Uji endpoint `/up` merespons status `200 OK` dengan metrik kesehatan subsistem.
- [ ] **S07-T05: Seeder Data Sintetis Sekolah Pilot & Panduan Pengguna**
  - Seeder data sintetis komprehensif: 1 sekolah, 2 tahun ajaran, 12 kelas, 360 siswa sintetis, 25 guru, jadwal, dan nilai simulasi.
  - Panduan operasional singkat untuk pengguna sekolah pilot di `docs/USER_GUIDE_PILOT.md`.
  - *Bukti:* `php artisan db:seed` berhasil mengisi data siap uji tanpa error.
- [ ] **S07-T06: Verifikasi Gate S07 & Sign-Off Kesiapan Pilot**
  - Audit menyeluruh: semua modul pilot (S01–S07) teruji, drill restore berhasil, panduan lengkap.
  - Perbarui `docs/HANDOFF.md` dan `docs/PROJECT_STATUS.md`.

---

### [ ] TAHAP S08: Deployment Production & Server Hardening

> **Kebutuhan Terkait:** Target operasional satu sekolah per instalasi (`SS-D-001`, `SS-D-003`).  
> **Gate Keluar:** Aplikasi ter-deploy live di server production, terenkripsi HTTPS, process manager aktif, auto backup terjadwal, dan CI/CD deployment teruji.

- [ ] **S08-T01: Provisi Database Server Production (MySQL 8.x / MariaDB)**
  - Konfigurasi koneksi MySQL production di `.env`: `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
  - Optimasi parameter database: `charset=utf8mb4`, `collation=utf8mb4_unicode_ci`, connection pooling, slow query log.
  - *Bukti:* Migrasi database berhasil dijalankan di server MySQL target.
- [ ] **S08-T02: Konfigurasi Reverse Proxy Web Server (Nginx / Caddy)**
  - Template konfigurasi Nginx production:
    - Root directory mengarah ke `/public`.
    - Blokir akses ke file tersembunyi (`.env`, `.git`).
    - Gzip / Brotli compression untuk static assets.
    - Sertifikat SSL/TLS otomatis via Let's Encrypt (Certbot / Caddy automatic HTTPS) dengan pengalihan paksa HTTP → HTTPS.
  - *Bukti:* File konfigurasi disimpan di `deploy/nginx/school-suite.conf`.
- [ ] **S08-T03: Konfigurasi Runtime PHP-FPM & OPcache Hardening**
  - Konfigurasi PHP-FPM pool: penyesuaian `pm = ondemand/dynamic`, `max_children`, `memory_limit = 256M`, `upload_max_filesize = 20M`.
  - Optimasi OPcache: `opcache.enable=1`, `opcache.memory_consumption=128`, `opcache.validate_timestamps=0` (untuk production).
  - *Bukti:* File konfigurasi `deploy/php/opcache.ini` dan `deploy/php/www.conf`.
- [ ] **S08-T04: Konfigurasi Process Manager & Cron Scheduler**
  - Setup Systemd Service / Supervisor untuk queue worker: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`.
  - Setup crontab sistem untuk scheduler Laravel: `* * * * * cd /var/www/school_suite && php artisan schedule:run >> /dev/null 2>&1`.
  - *Bukti:* File unit systemd disimpan di `deploy/systemd/school-suite-worker.service`.
- [ ] **S08-T05: Security Hardening & Environment Lockdown**
  - Kunci file environment: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`.
  - Header keamanan HTTP: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Strict-Transport-Security`, `Content-Security-Policy`.
  - Izin direktori aman di Linux: `chown -R www-data:www-data`, chmod 755 direktori, chmod 644 file, chmod 775 `storage/` dan `bootstrap/cache/`.
  - *Bukti:* Pemindaian keamanan lokal membuktikan `APP_DEBUG` mati dan header proteksi aktif.
- [ ] **S08-T06: Skrip Otomasi Deployment (Zero-Downtime Deployment)**
  - Script deployment (`deploy/deploy.sh`):
    1. Clone/pull commit terbaru ke rilis baru.
    2. Jalankan `composer install --no-dev --optimize-autoloader`.
    3. Jalankan `npm ci && npm run build`.
    4. Jalankan `php artisan migrate --force`.
    5. Cache konfigurasi, routes, dan views (`php artisan config:cache`, `route:cache`, `view:cache`).
    6. Switch symlink rilis ke direktori live.
    7. Restart queue worker dan reload PHP-FPM.
  - *Bukti:* Eksekusi simulasi deployment script selesai tanpa downtime.
- [ ] **S08-T07: Disaster Recovery, Rollback Script & Uji Rilis Akhir**
  - Skrip rollback otomatis (`deploy/rollback.sh`) untuk kembali ke rilis sebelumnya jika health check `/up` gagal pasca-deploy.
  - Penjadwalan backup harian via cron ke cold storage / storage terpisah.
  - Verifikasi akhir penerimaan deployment: seluruh fungsionalitas pilot berjalan lancar di lingkungan live.
  - *Bukti:* Deployment checklist tertanda tangani di `docs/DEPLOYMENT_CHECKLIST.md`.

---

### [ ] TAHAP S09–S13: Ekspansi Modul Sekolah (Pasca-Pilot)

> Dikerjakan secara bertahap setelah sekolah pilot stabil menggunakan sistem inti.

- [ ] **S09: Modul Pembelajaran / LMS (`app/Modules/Learning`)**
  - Bahan ajar digital (PDF, video, link referensi).
  - Penugasan siswa dengan batas waktu pengumpulan (*deadline*).
  - Pengunggahan berkas tugas oleh siswa dan penilaian/feedback guru.
- [ ] **S10: Modul Ujian / CBT (`app/Modules/Assessment`)**
  - Bank soal (Pilihan ganda, esai, menjodohkan).
  - Sesi ujian dengan token akses, pengacakan nomor soal/opsi, dan timer interaktif.
  - Analisis butir soal dan integrasi hasil ujian otomatis ke daftar nilai.
- [ ] **S11: Modul Penerimaan Siswa Baru / SPMB (`app/Modules/Admissions`)**
  - Formulir pendaftaran online calon siswa.
  - Unggah dan verifikasi berkas persyaratan (Akta, KK, Ijazah).
  - Pengumuman seleksi dan alur daftar ulang otomatis menjadi data siswa di `Academic`.
- [ ] **S12: Modul Perpustakaan / Sipus (`app/Modules/Library`)**
  - Katalog buku (ISBN, pengarang, penerbit, nomor klasifikasi).
  - Manajemen eksemplar fisik & cetak barcode buku/kartu anggota.
  - Sirkulasi peminjaman, pengembalian, perpanjangan, dan denda keterlambatan.
- [ ] **S13: Modul Tata Usaha & Sarana Prasarana (`app/Modules/Administration`)**
  - Agenda surat masuk dan surat keluar dengan penomoran otomatis.
  - Kearsipan dokumen digital sekolah.
  - Inventaris sarana prasarana, status kondisi barang, dan pelaporan perbaikan.

---

### [ ] TAHAP S14: Integrasi Teacher Desk (`app/Modules/Integration`)

> **Prinsip Kunci:** `SS-D-007` (Dilarang keras memakai database bersama; wajib menggunakan REST API berversi yang idempotent).

- [ ] **S14-T01: Desain Kontrak & Spesifikasi OpenAPI Teacher Desk**
  - Definisi endpoint: otentikasi perangkat, pertukaran payload nilai/absensi offline, rekonsiliasi timestamp.
- [ ] **S14-T02: Engine Idempotensi & Inbox Submission**
  - Penyimpanan request dengan `idempotency_key` untuk mencegah input ganda saat perangkat reconnect.
  - Inbox validasi transaksi sebelum data diterapkan ke database akademik.
- [ ] **S14-T03: Uji Resolusi Konflik & Penarikan Izin**
  - Penanganan kasus konflik (misal: data diubah di web dan di desktop bersamaan).
  - Penolakan sinkronisasi jika token guru dicabut atau periode akademik telah dikunci.
  - *Bukti:* Test suite integrasi `tests/Feature/IntegrationTest.php` lulus 100%.

