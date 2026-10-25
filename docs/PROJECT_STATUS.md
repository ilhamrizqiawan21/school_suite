# Project Status — School Suite

Terakhir diperbarui: 25 Oktober 2026

## Keadaan saat ini

- Fase: S07 Persiapan Pilot, Backup/Restore & Observability selesai; siap masuk ke S08 Deployment Production & Server Hardening.
- Kesiapan:
  - S01 Fondasi: Bootstrap Laravel 13, Inertia.js v3, Vue 3, Vite v8, Tailwind CSS v4, TypeScript, struktur 9 modul monolit modular, layout shell dasar, dan boundary test arsitektur selesai.
  - S02 Akses: Skema basis data Core, otorisasi server-side Default Deny, engine audit log terpusat, autentikasi rate-limited, UI kelola user & profil sekolah, dan matriks akses multi-peran selesai.
  - S03 Data Inti Akademik: Skema basis data Akademik, histori enrollment tidak berubah (`SS-D-004`), penugasan guru & wali kelas, engine impor massal CSV dengan validasi duplikasi NIS/NISN, serta antarmuka UI data inti selesai.
  - S04 Operasi Akademik Harian: Skema jadwal & presensi, validasi benturan jadwal mingguan (`ScheduleService`), koreksi presensi massal dengan audit trail otomatis (`AttendanceService`), serta UI jadwal & presensi harian selesai.
  - S05 Ruang Kerja Wali Kelas & Kesiswaan: Skema catatan kesiswaan, isolasi privasi catatan rahasia/BK (`StudentObservationService`, `SS-F-006`), monitoring Early Warning absensi (`HomeroomAttendanceService`), dispensasi surat izin, dan dashboard perwalian selesai.
  - S06 Penilaian, Rapor & Formula Berversi: Skema nilai berversi, engine formula ketuntasan & remedial ($\min(\text{remedial}, \text{KKTP})$) di Domain layer (`GradeCalculationEngine`), immutable report snapshot dengan hash SHA-256 (`SS-D-005`), UI input nilai guru, dan cetak rapor siap cetak selesai.
  - S07 Persiapan Pilot, Backup/Restore & Observability:
    - Manajemen penyimpanan privat terisolasi (`PrivateStorageService`) dan pengunduhan aman via Signed URL berbatas waktu (`SS-F-008`).
    - Queue worker background job (`GenerateReportPdfJob`) untuk operasi berat PDF/ekspor massal asinkron (`QueueJobsTest`).
    - Skrip otomasi backup mandiri (`php artisan school:backup`) dan drill restore terpisah (`php artisan school:restore`) terverifikasi 100% memulihkan data dan berkas tanpa kehilangan (`BackupRestoreDrillTest`).
    - Standard Operating Procedure (SOP) Disaster Recovery terdokumentasi di `docs/RUNBOOK_BACKUP_RESTORE.md`.
    - Endpoint observability & health check (`/up` & `/health`) yang memvalidasi database, storage writeability, dan queue worker (`HealthCheckTest`).
    - Sanitasi otomatis logging Monolog (`SanitizeSensitiveDataProcessor`) menyamarkan password, token, NISN, dan nomor telepon siswa (`***REDACTED***`).
    - Dataset sintetis sekolah pilot lengkap (`PilotSchoolSeeder`) dan panduan operasional pengguna di `docs/USER_GUIDE_PILOT.md`.
  - Seluruh CI dan test suite (137 tests, 660 assertions, Unit, Feature, Architecture, Lint `pint`, Typecheck `vue-tsc`, Build `vite`, Docs Governance) hijau 100%.
- Langkah berikut: Tahap S08 Deployment Production & Server Hardening (`SS-D-001`, `SS-D-003`: provisi database MySQL production, template konfigurasi reverse proxy Nginx/Caddy dengan SSL/TLS, script process manager, dan hardening file permissions).

## Blocker sebelum data nyata

- Profil sekolah pilot, ukuran data, format rapor, dan aturan pembulatan belum dikunci.
- Kebijakan retensi, backup operasional, dan penanggung jawab pemulihan belum disahkan.
- Lisensi kode/template/font dari repo sumber belum diinventaris lengkap.

## Delete zone

| Konsep | Status | Syarat kembali |
|---|---|---|
| Tabungan siswa | Dikeluarkan | ADR baru dan persetujuan pemilik produk |
| Multi-tenant | Ditunda | kebutuhan nyata dan desain isolasi |
| Microservices | Ditunda | bukti beban/batas deployment |
| Database bersama dengan Teacher Desk | Dilarang | tidak direncanakan; gunakan API |
