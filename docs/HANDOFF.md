# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang diserahkan untuk review.

## Identitas

- Task/issue: Penyelesaian Penuh Tahap S07 (Persiapan Pilot, Backup/Restore & Observability: S07-T01 s/d S07-T06)
- Requirement ID: SS-F-008 (Export Laporan & Signed URL Berkas Privat), SS-F-009 (Backup, Restore, & Migrasi Versi), AI_RULES.md (Aturan 4 & 5: Sanitasi data sensitif & SOP Pemulihan Bencana), DESIGN.md, ARCHITECTURE.md
- Branch/commit: main / commit berikutnya
- Status: Selesai & Terverifikasi (Gate S07 Passed)

## Ringkasan dan scope

- Perubahan:
  - **S07-T01: Manajemen Penyimpanan Privat & Signed URL Handler (`SS-F-008`)**
    - Service `PrivateStorageService` di `app/Shared/Files/PrivateStorageService.php`:
      - Mengelola penyimpanan berkas di disk lokal privat non-public (`storage/app/private`).
      - Menyediakan proteksi pembersihan directory traversal attacks (`sanitizePath`).
      - Menghasilkan URL bertanda tangan berbatas waktu (`temporarySignedRoute`) dengan batas kedaluwarsa waktu.
    - Controller `PrivateFileController` di `app/Shared/Http/Controllers/PrivateFileController.php`:
      - Memvalidasi keabsahan tanda tangan URL (`$request->hasValidSignature()`).
      - Menolak akses langsung tanpa tanda tangan sah atau yang telah kedaluwarsa dengan respon HTTP 403 Forbidden.
      - Mengembalikan stream pengunduhan berkas aman dengan header keamanan `X-Content-Type-Options: nosniff`.
    - Teruji lewat [`tests/Feature/PrivateStorageTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/PrivateStorageTest.php) (6 tests, 12 assertions).
  - **S07-T02: Konfigurasi Queue Worker & Background Jobs**
    - Konfigurasi antrean database queue worker di `config/queue.php`.
    - Job `GenerateReportPdfJob` di `app/Shared/Jobs/GenerateReportPdfJob.php`:
      - Mengimplementasikan `ShouldQueue`, retry 3 kali (`$tries = 3`), dan batas waktu 120 detik (`$timeout = 120`).
      - Memproses penyusunan dan penyimpanan dokumen rapor ke disk privat secara asinkron tanpa membebani web server.
      - Penanganan kegagalan permanen dengan pencatatan structured error log (`failed`).
    - Teruji lewat [`tests/Feature/QueueJobsTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/QueueJobsTest.php) (3 tests, 4 assertions).
  - **S07-T03: Skrip Otomasi Backup Mandiri & Verifikasi Drill Restore (`SS-F-009`)**
    - Perintah `php artisan school:backup` di `app/Console/Commands/SchoolBackupCommand.php`:
      - Mencadangkan struktur dan data basis data ke script dump SQL portabel (kompatibel SQLite & MySQL).
      - Mengarsipkan database dump bersama folder `storage/app/private` dan `manifest.json` (memuat waktu, versi PHP, checksum database SHA-256) ke arsip ZIP terkompresi stempel waktu di `storage/app/backups/`.
    - Perintah `php artisan school:restore` di `app/Console/Commands/SchoolRestoreCommand.php`:
      - Membaca dan mengekstrak arsip cadangan.
      - Mendukung simulasi pemulihan pada instance database terisolasi (`--target-db` dan `--target-files`) untuk pengujian drill restore berkala.
    - Standard Operating Procedure (SOP) terdokumentasi lengkap di [`docs/RUNBOOK_BACKUP_RESTORE.md`](file:///home/ilhamrizqiawan/Projects/school_suite/docs/RUNBOOK_BACKUP_RESTORE.md).
    - Teruji lewat [`tests/Feature/BackupRestoreDrillTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/BackupRestoreDrillTest.php) (2 tests, 17 assertions: membuktikan 100% data dan file dipulihkan utuh).
  - **S07-T04: Observability, Health Check & Sanitasi Logging**
    - Controller `HealthCheckController` di `app/Http/Controllers/HealthCheckController.php`:
      - Endpoint `/up` dan `/health` yang menguji konektivitas basis data (latency ms), izin tulis storage disk privat (`storage/app/private`), dan pemantauan kegagalan jobs.
      - Mengembalikan respon HTTP 200 JSON berstatus `"healthy"` atau HTTP 503 jika komponen kritis mengalami gangguan.
    - Processor Monolog `SanitizeSensitiveDataProcessor` di `app/Logging/SanitizeSensitiveDataProcessor.php`:
      - Terdaftar secara otomatis di `AppServiceProvider::boot()`.
      - Menyaring parameter kunci: `password`, `token`, `secret`, `nisn`, `parent_phone`, `phone`, `authorization` dan menggantinya dengan `***REDACTED***` untuk mencegah kebocoran data siswa ke file log (`AI_RULES.md` Aturan 4 & 9).
    - Teruji lewat [`tests/Feature/HealthCheckTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/HealthCheckTest.php) (2 tests, 24 assertions).
  - **S07-T05: Seeder Data Sintetis Sekolah Pilot & Panduan Pengguna**
    - Seeder `PilotSchoolSeeder` di `database/seeders/PilotSchoolSeeder.php`:
      - Mengisi 1 sekolah percontohan: "SMP Negeri 1 Percontohan Nusantara" (NPSN 20109988).
      - 2 tahun ajaran (2025/2026 lampau & 2026/2027 aktif), 2 semester (Ganjil & Genap).
      - 7 akun pengguna utama (Admin, Kepala Sekolah, Operator, Guru, Wali Kelas, Siswa).
      - 10 mata pelajaran nasional & 7 guru pengampu.
      - 6 rombel (7A–9B) dan 30 siswa sintetis ter-enroll aktif di kelas 7A.
      - Skema Penilaian Kurikulum Merdeka (bobot 30/20/25/25, KKTP 75), sampel nilai guru, dan 1 snapshot rapor terbit untuk verifikasi cetak instan.
      - Terintegrasi di `database/seeders/DatabaseSeeder.php`.
    - Panduan Pengguna Operasional Sekolah Pilot di [`docs/USER_GUIDE_PILOT.md`](file:///home/ilhamrizqiawan/Projects/school_suite/docs/USER_GUIDE_PILOT.md).
    - Teruji lewat [`tests/Feature/PilotSeederTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/PilotSeederTest.php) (1 test, 16 assertions).
  - **S07-T06: Verifikasi Gate S07 & Sign-Off Kesiapan Pilot**
    - Seluruh CI dan test suite lulus 100% (137 tests, 660 assertions).
    - Boundary test arsitektur `ModularBoundaryTest` lulus (0 pelanggaran modular monolith).
    - `vendor/bin/pint --test`, `vue-tsc`, `vite build`, dan `audit-docs.py` lolos tanpa catatan.
- Tidak diubah:
  - Modul deployment production (Tahap S08).

## Dampak teknis

- Modul/file yang disentuh:
  - Backend & Infrastruktur:
    - `app/Console/Commands/SchoolBackupCommand.php`
    - `app/Console/Commands/SchoolRestoreCommand.php`
    - `app/Http/Controllers/HealthCheckController.php`
    - `app/Logging/SanitizeSensitiveDataProcessor.php`
    - `app/Providers/AppServiceProvider.php`
    - `app/Shared/Files/PrivateStorageService.php`
    - `app/Shared/Http/Controllers/PrivateFileController.php`
    - `app/Shared/Jobs/GenerateReportPdfJob.php`
    - `database/seeders/DatabaseSeeder.php`
    - `database/seeders/PilotSchoolSeeder.php`
    - `routes/web.php`
  - Dokumentasi:
    - `docs/RUNBOOK_BACKUP_RESTORE.md`
    - `docs/USER_GUIDE_PILOT.md`
    - `docs/TODO.md`
    - `docs/PROJECT_STATUS.md`
    - `docs/HANDOFF.md`
  - Pengujian:
    - `tests/Feature/PrivateStorageTest.php`
    - `tests/Feature/QueueJobsTest.php`
    - `tests/Feature/BackupRestoreDrillTest.php`
    - `tests/Feature/HealthCheckTest.php`
    - `tests/Feature/PilotSeederTest.php`
- Keamanan/izin:
  - Berkas privat diisolasi dari direktori publik web server dan hanya dapat diunduh menggunakan URL bertanda tangan kriptografis (`URL::temporarySignedRoute`) yang kedaluwarsa otomatis.
  - Percobaan manipulasi URL atau akses tanpa token ditolak dengan HTTP 403 Forbidden.
  - Seluruh parameter sensitif (kata sandi, token, NISN, telepon orang tua) disamarkan secara otomatis di berkas log aplikasi.
  - Perintah restore meminta konfirmasi interaktif secara default untuk mencegah tertimpanya basis data secara tidak sengaja.

## Bukti

- Hasil tes otomatis:
  - Total test suite: 137 tests, 660 assertions passed (100% HIJAU) dalam 6.5s.
  - `tests/Feature/PrivateStorageTest.php`: 6 passed, 12 assertions.
  - `tests/Feature/QueueJobsTest.php`: 3 passed, 4 assertions.
  - `tests/Feature/BackupRestoreDrillTest.php`: 2 passed, 17 assertions (drill restore sukses tanpa kehilangan data).
  - `tests/Feature/HealthCheckTest.php`: 2 passed, 24 assertions (status healthy & sanitasi Monolog lulus).
  - `tests/Feature/PilotSeederTest.php`: 1 passed, 16 assertions.
  - `tests/Architecture/ModularBoundaryTest.php`: 4 passed, 6 assertions (0 pelanggaran arsitektur).
- Pemeriksaan statis:
  - `vendor/bin/pint --test`: passed.
  - `npm run typecheck` (`vue-tsc --noEmit`): passed.
  - `npm run build` (`vite build`): passed.
  - `audit-docs.py`: passed (0 broken links).

## Risiko yang tersisa

- Skrip backup saat ini menghasilkan dump SQL dan arsip file ZIP lokal. Untuk lingkungan production multi-server, disarankan mengintegrasikan sinkronisasi ke S3-compatible offsite storage (akan disiapkan pada S08).

## Review manual

1. Jalankan seeder pilot: `php artisan db:seed`.
2. Jalankan perintah backup: `php artisan school:backup`. Pastikan file ZIP terbentuk di `storage/app/backups/`.
3. Jalankan pengujian pemulihan ke database terpisah:
   ```bash
   php artisan school:restore backup_school_suite_terbaru.zip --target-db=/tmp/test_drill.sqlite --force
   ```
4. Buka URL health check di browser: `http://localhost:8000/up`. Pastikan menampilkan JSON dengan `"status": "healthy"`.
5. Login menggunakan akun `budi@pilot.sch.id` (password: `password`) dan buka menu "Ruang Wali Kelas" serta "Rapor Siswa" untuk melihat dataset pilot yang telah terisi.

## Rencana rollback

1. Kembalikan branch ke commit sebelum S07: `git reset --hard 41405b8`.
2. Hapus berkas backup temporary di `storage/app/backups/`.
3. Jalankan migrasi ulang jika diperlukan: `php artisan migrate:fresh`.
