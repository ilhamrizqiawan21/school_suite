# Project Status — School Suite

Terakhir diperbarui: 27 Oktober 2026

## Keadaan saat ini

- Fase: S08 Deployment Production & Server Hardening. Kesiapan operasional live belum dapat dinyatakan sampai verifikasi backup/restore MySQL, health check deploy, dan otorisasi lintas peran selesai.
- Kesiapan:
  - S01 Fondasi: Bootstrap Laravel 13, Inertia.js v3, Vue 3, Vite v8, Tailwind CSS v4, TypeScript, struktur 9 modul monolit modular, layout shell dasar, dan boundary test arsitektur selesai.
  - S02 Akses: Skema basis data Core, otorisasi server-side Default Deny, engine audit log terpusat, autentikasi rate-limited, UI kelola user & profil sekolah, dan matriks akses multi-peran selesai.
  - S03 Data Inti Akademik: Skema basis data Akademik, histori enrollment tidak berubah (`SS-D-004`), penugasan guru & wali kelas, engine impor massal CSV dengan validasi duplikasi NIS/NISN, serta antarmuka UI data inti selesai.
  - S04 Operasi Akademik Harian: Skema jadwal & presensi, validasi benturan jadwal mingguan (`ScheduleService`), koreksi presensi massal dengan audit trail otomatis (`AttendanceService`), serta UI jadwal & presensi harian selesai.
  - S05 Ruang Kerja Wali Kelas & Kesiswaan: Skema catatan kesiswaan, isolasi privasi catatan rahasia/BK (`StudentObservationService`, `SS-F-006`), monitoring Early Warning absensi (`HomeroomAttendanceService`), dispensasi surat izin, dan dashboard perwalian selesai.
  - S06 Penilaian, Rapor & Formula Berversi: Skema nilai berversi, engine formula ketuntasan & remedial ($\min(\text{remedial}, \text{KKTP})$) di Domain layer (`GradeCalculationEngine`), immutable report snapshot dengan hash SHA-256 (`SS-D-005`), UI input nilai guru, dan cetak rapor siap cetak selesai.
  - S07 Persiapan Pilot, Backup/Restore & Observability: Penyimpanan privat terisolasi (`PrivateStorageService`, `SS-F-008`), queue background jobs, skrip backup dan restore tersedia (`SS-F-009`), endpoint `/up` & sanitasi logging, dataset pilot (`PilotSchoolSeeder`), dan user guide. Drill MySQL 8.4.11 dan restore SQLite berbasis file sudah lulus secara terisolasi; operasi server tetap mengikuti runbook.
  - S08 Deployment Production & Server Hardening:
    - Template reverse proxy Nginx production dengan SSL/TLS TLSv1.2/1.3, gzip, dan proteksi file tersembunyi (`deploy/nginx/school-suite.conf`).
    - Optimasi runtime PHP-FPM (`deploy/php/www.conf`) dan OPcache production (`deploy/php/opcache.ini`).
    - Process manager Systemd unit untuk queue worker (`deploy/systemd/school-suite-worker.service`).
    - Cron scheduler Laravel di `routes/console.php` untuk backup harian otomatis jam 02:00 dan restart worker jam 04:00.
    - Security headers middleware (`SecurityHeadersMiddleware`): `X-Frame-Options`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection`, `Referrer-Policy`, `HSTS`.
    - Skrip otomasi deployment zero-downtime atomik (`deploy/deploy.sh`) dan skrip rollback darurat (`deploy/rollback.sh`).
    - Panduan checklist rilis produksi di `docs/DEPLOYMENT_CHECKLIST.md`.
  - Verifikasi lokal 27 Oktober 2026: `php artisan test --compact` — 350 tes, 349 lulus, 1 tes MySQL dilewati, 3.200 assertions. Tes MySQL dijalankan terpisah pada server drill 8.4.11 — 1 tes/20 assertions lulus. Pint, typecheck, build, shell syntax, dan diff check lulus. Bukti serta batas penerapan: [Audit backend](AUDIT_BACKEND_2026-10-27.md). Deployment live belum dilakukan.
- Langkah berikut: Ekspansi Modul Pasca-Pilot (S09 Modul Pembelajaran / LMS, S10 CBT, S11 SPMB, S12 Sipus, S13 TU & Sarpras).

## Blocker sebelum data nyata

- Drill restore backup pada MySQL terisolasi dan restore backup SQLite berbasis file belum dibuktikan.
- Health check deployment, termasuk rollback saat instalasi pertama dan saat ada rilis sebelumnya, telah diverifikasi melalui dry run; endpoint HTTPS domain production belum diproba.
- Pengujian negatif lintas kelas/sekolah yang diminta sudah ditambahkan/dijalankan. Audit menemukan URL jadwal, kelas, rekap presensi, perwalian, dan komunikasi yang akses langsungnya tidak selalu cocok dengan visibilitas sidebar; audit semua route/menu dan parity browser belum tuntas.
- PHP 8.3 CLI/FPM asli belum tersedia pada mesin verifikasi; lockfile diuji dengan Composer platform 8.3.0 pada PHP lokal 8.5.4.

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
