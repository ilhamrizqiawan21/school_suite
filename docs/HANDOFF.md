# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang diserahkan untuk review.

## Identitas

- Task/issue: Restrukturisasi, Perapihan & Pelengkapan Menu Navigasi Sidebar dan Alur Kerja Dashboard Non-Admin (Guru, Wali Kelas, Siswa, Kepala Sekolah, Operator)
- Requirement ID: UI-ROLE-NAV-02, S01-T05, LMS-C01–C11, LMS-D01–D08, LMS-E01–E09, LMS-F01–F07, DESIGN.md (Arsitektur Informasi), AI_RULES.md
- Branch/commit: main / commit berikutnya
- Status: Selesai & Terverifikasi (Victory Confirmed oleh Independent Victory Auditor & 184/184 tests passed)

## Ringkasan dan scope

- Perubahan:
  - **1. Restrukturisasi Kategori Domain Navigasi Sidebar (`SidebarNavigation.vue`)**:
    - Menu dikelompokkan ke dalam kategori domain yang terstruktur: *Akademik*, *Pembelajaran LMS*, *Asesmen & CBT*, *Penilaian & Rapor*, *Perwalian*, *Komunikasi*, *Pengaturan*, serta *Beranda*.
    - Header kategori kosong dieliminasi secara dinamis (`.filter(s => s.items.length > 0)`).
    - Logika `isItemActive` disempurnakan dengan segment boundary check (`===` atau diawali prefix diikuti slash/query/hash) untuk mencegah highlight ganda pada sub-rute.
    - Menambahkan rute navigasi yang sebelumnya hilang atau belum terhubung:
      - `/komunikasi/chat` (Chat Kelas) untuk Guru, Siswa, Wali Kelas, Kepsek, dan Operator.
      - `/wali-kelas/catatan` (Catatan Observasi & BK) untuk Wali Kelas.
      - `/wali-kelas/kedisiplinan` (Kedisiplinan & Prestasi) untuk Wali Kelas.
      - `/komunikasi/whatsapp` (WhatsApp Ortu) untuk Wali Kelas, Kepsek, Operator.
      - `/akademik/jadwal` (Jadwal Pelajaran) kini terlihat untuk Siswa (`student`).
  - **2. Pengayaan Alur Kerja & Pintasan Dashboard Role Non-Admin (`Dashboard.vue`)**:
    - **Isolasi Mutlak Admin**: Bagian `adminActions` dan komponen overview admin dipertahankan utuh dan tidak terganggu.
    - Menambahkan dan memperkaya panel aksi mandiri per peran:
      - `teacherActions` (8 alur kerja: input nilai, presensi & jurnal, ruang belajar LMS, bank soal cerdas, CBT, chat kelas, analisis butir, jadwal mengajar).
      - `homeroomActions` (8 alur kerja: pantau perwalian, catatan observasi, kedisiplinan, WhatsApp ortu, verifikasi rapor, rekap presensi, nilai, pengumuman).
      - `studentActions` (7 alur kerja: ruang belajar & tugas, ujian CBT, jadwal pelajaran, kelas daring, progress belajar, rapor, diskusi chat).
      - `principalActions` (7 alur kerja: pusat laporan, kinerja operasional guru, analisis asesmen, rapor, pantauan akademik, komunikasi, profil legal & keamanan).
      - `operatorActions` (8 alur kerja: master siswa, guru & penugasan, rombel, jadwal, pengguna, siklus siswa, bank soal, skema & rapor).
    - Memperbaiki tautan broken `/penilaian/laporan` (404) menjadi `/laporan`.
    - Mengoreksi tautan unauthorized siswa pada `/akademik/presensi` (403) menjadi rute siswa yang sah.
  - **3. Penguatan Otorisasi Server-Side & Pemisahan Boundary**:
    - Otorisasi peran diperketat pada `OnlineClassController` dan `StudentAttitudeController`.
    - Query kelas dan enrollment siswa di `CommunicationController` dienkapasulasi bersih melalui `AcademicQueryService` demi menjaga batas modular monolith.
    - Penambahan `RouteAndPermissionStressTest` dan `DashboardAccessTest` untuk menjamin seluruh role teruji secara mendalam.

## Dampak teknis

- Modul/file yang disentuh:
  - Frontend:
    - `resources/js/components/SidebarNavigation.vue`
    - `resources/js/Pages/Dashboard.vue`
    - `resources/js/types/index.d.ts`
  - Backend & Controllers:
    - `app/Modules/Academic/Application/Services/AcademicQueryService.php`
    - `app/Modules/Academic/Interface/Http/Controllers/OnlineClassController.php`
    - `app/Modules/Administration/Interface/Http/Controllers/CommunicationController.php`
    - `app/Modules/Assessment/Interface/Http/Controllers/StudentAttitudeController.php`
    - `routes/web.php`
  - Pengujian:
    - `tests/Feature/DashboardAccessTest.php`
    - `tests/Feature/RouteAndPermissionStressTest.php`
  - Dokumentasi:
    - `docs/HANDOFF.md`

## Bukti

- Hasil tes otomatis:
  - `php artisan test`: 184 passed, 1.377 assertions (100% HIJAU) dalam 11.9s.
  - `npm run typecheck` (`vue-tsc --noEmit`): 0 errors, passed.
  - `npm run build` (`vite build`): 700 modules transformed, production assets generated, passed.
  - `./vendor/bin/pint --test`: passed (100% PSR-12 standard).
  - Independent Victory Audit: **VICTORY CONFIRMED** (`teamwork_preview_victory_auditor`).

## Risiko yang tersisa

- Tidak ada risiko regresi terdeteksi. Seluruh hak akses, boundary modular monolith, dan pengujian berjalan tanpa hambatan.

## Review manual

1. Login sebagai Guru: periksa navigasi sidebar (kategori Akademik, LMS, Asesmen & CBT, Penilaian, Komunikasi) dan alur kerja dashboard guru.
2. Login sebagai Wali Kelas: periksa menu khusus Perwalian (`/wali-kelas`, `/wali-kelas/catatan`, `/wali-kelas/kedisiplinan`) dan shortcut WhatsApp.
3. Login sebagai Siswa: pastikan jadwal pelajaran, ruang belajar, kelas daring, dan chat kelas dapat dibuka tanpa error 403/404.
4. Login sebagai Kepala Sekolah & Operator: pastikan pusat laporan terbuka di `/laporan` dan seluruh menu manajerial tertata rapi.
5. Login sebagai Admin: pastikan menu dan alur kerja admin Anda tetap bersih dan tidak terpengaruh.

## Ringkasan dan scope

- Perubahan:
  - **S08-T01: Provisi Database Server Production (MySQL 8.x / MariaDB)**
    - Template `.env.example` dikonfigurasi untuk production MySQL: `DB_CONNECTION=mysql`, `DB_DATABASE=school_suite`, `DB_CHARSET=utf8mb4`, `DB_COLLATION=utf8mb4_unicode_ci`.
    - `config/database.php` mengonfigurasi strict mode dan connection options.
  - **S08-T02: Konfigurasi Reverse Proxy Web Server (Nginx)**
    - Template Nginx di [`deploy/nginx/school-suite.conf`](file:///home/ilhamrizqiawan/Projects/school_suite/deploy/nginx/school-suite.conf):
      - Pengalihan otomatis HTTP (port 80) ke HTTPS (port 443).
      - Konfigurasi TLSv1.2 & TLSv1.3 modern cipher suite.
      - Document root mengarah ke symlink rilis aktif `/var/www/school_suite/current/public`.
      - FastCGI pass ke Unix domain socket PHP-FPM dengan buffer size optimal.
      - Blokir ketat akses file rahasia (`.env`, `.git`) dan pencegahan eksekusi PHP di folder storage.
      - Gzip compression aktif untuk CSS, JS, SVG, JSON.
      - Caching static asset Vite selama 1 tahun (`Cache-Control: public, max-age=31536000, immutable`).
  - **S08-T03: Konfigurasi Runtime PHP-FPM & OPcache Hardening**
    - Konfigurasi OPcache di [`deploy/php/opcache.ini`](file:///home/ilhamrizqiawan/Projects/school_suite/deploy/php/opcache.ini): `opcache.enable=1`, `opcache.memory_consumption=128`, `opcache.validate_timestamps=0` (untuk kinerja optimal production), `opcache.save_comments=1`.
    - Konfigurasi PHP-FPM pool di [`deploy/php/www.conf`](file:///home/ilhamrizqiawan/Projects/school_suite/deploy/php/www.conf): `pm = dynamic`, `pm.max_children = 50`, `memory_limit = 256M`, `upload_max_filesize = 25M`, `expose_php = off`.
  - **S08-T04: Konfigurasi Process Manager & Cron Scheduler**
    - Unit service Systemd di [`deploy/systemd/school-suite-worker.service`](file:///home/ilhamrizqiawan/Projects/school_suite/deploy/systemd/school-suite-worker.service): menjalankan worker `artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=120` dengan `Restart=always` di bawah user `www-data`.
    - Scheduler Laravel di `routes/console.php`: menjadwalkan pencadangan mandiri otomatis (`school:backup`) setiap hari pukul 02:00 WIB dan `queue:restart` harian pukul 04:00 WIB.
  - **S08-T05: Security Hardening & Environment Lockdown**
    - Middleware [`SecurityHeadersMiddleware`](file:///home/ilhamrizqiawan/Projects/school_suite/app/Http/Middleware/SecurityHeadersMiddleware.php) didaftarkan di `bootstrap/app.php`: menyisipkan header keamanan wajib `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`, dan `HSTS` (saat HTTPS/production).
  - **S08-T06: Skrip Otomasi Deployment Zero-Downtime**
    - Skrip deployment atomik di [`deploy/deploy.sh`](file:///home/ilhamrizqiawan/Projects/school_suite/deploy/deploy.sh):
      - Mengelola rilis berstempel waktu di `/var/www/school_suite/releases/`.
      - Mengaitkan file shared `.env` dan direktori shared `storage/`.
      - Instalasi dependensi Composer `--no-dev --optimize-autoloader`.
      - Kompilasi aset frontend Vite `npm ci && npm run build`.
      - Migrasi basis data terisolasi `php artisan migrate --force --isolated`.
      - Caching framework (`config:cache`, `route:cache`, `view:cache`, `event:cache`).
      - Pengalihan atomik symlink `current` (`ln -sfn`).
      - Verifikasi probe `/up`, reload PHP-FPM, dan restart worker.
      - Rotasi pembersihan rilis lama (menyimpan 5 rilis terakhir).
  - **S08-T07: Rollback Darurat, Checklist Rilis & Uji Verifikasi**
    - Skrip rollback darurat di [`deploy/rollback.sh`](file:///home/ilhamrizqiawan/Projects/school_suite/deploy/rollback.sh): mengembalikan symlink `current` ke rilis sebelumnya secara instan jika terjadi kegagalan.
    - Lembar panduan checklist produksi di [`docs/DEPLOYMENT_CHECKLIST.md`](file:///home/ilhamrizqiawan/Projects/school_suite/docs/DEPLOYMENT_CHECKLIST.md).
    - Teruji lewat [`tests/Feature/DeploymentConfigurationTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/DeploymentConfigurationTest.php) (3 tests, 32 assertions passed).
- Tidak diubah:
  - Modul ekspansi pasca-pilot (LMS, CBT, SPMB, Sipus, TU) pada Tahap S09–S13.

## Dampak teknis

- Modul/file yang disentuh:
  - Konfigurasi & Script Deployment:
    - `.env.example`
    - `bootstrap/app.php`
    - `routes/console.php`
    - `app/Http/Middleware/SecurityHeadersMiddleware.php`
    - `deploy/nginx/school-suite.conf`
    - `deploy/php/opcache.ini`
    - `deploy/php/www.conf`
    - `deploy/systemd/school-suite-worker.service`
    - `deploy/deploy.sh`
    - `deploy/rollback.sh`
  - Dokumentasi:
    - `docs/DEPLOYMENT_CHECKLIST.md`
    - `docs/TODO.md`
    - `docs/PROJECT_STATUS.md`
    - `docs/HANDOFF.md`
  - Pengujian:
    - `tests/Feature/DeploymentConfigurationTest.php`
- Keamanan/izin:
  - Header proteksi keamanan aktif di seluruh rute aplikasi (mencegah Clickjacking, MIME sniffing, XSS injection).
  - Eksekusi file PHP di direktori publik dinonaktifkan di level web server Nginx.
  - File `.env` dan direktori internal `.git` diblokir langsung oleh reverse proxy Nginx.
  - Process manager berjalan di bawah pengguna tanpa hak root (`www-data`).
  - Deployment zero-downtime memitigasi risiko downtime pengguna saat update sistem.

## Bukti

- Hasil tes otomatis:
  - Total test suite: 140 tests, 692 assertions passed (100% HIJAU) dalam 7.9s.
  - `tests/Feature/DeploymentConfigurationTest.php`: 3 passed, 32 assertions (seluruh header keamanan, file konfigurasi deployment, dan cron scheduler teruji).
  - `tests/Architecture/ModularBoundaryTest.php`: 4 passed, 6 assertions (0 pelanggaran modular monolith).
- Pemeriksaan statis:
  - `vendor/bin/pint --test`: passed (100% PSR-12/Laravel standard).
  - `npm run typecheck` (`vue-tsc --noEmit`): passed (0 type error).
  - `npm run build` (`vite build`): passed.
  - `audit-docs.py`: passed (0 broken links).

## Risiko yang tersisa

- Konfigurasi nama domain pada `deploy/nginx/school-suite.conf` (`school.sch.id`) perlu disesuaikan dengan domain sekolah target nyata saat instalasi server fisik.

## Review manual

1. Periksa file konfigurasi Nginx: `cat deploy/nginx/school-suite.conf`.
2. Periksa skrip deployment zero-downtime: `bash -n deploy/deploy.sh` dan `bash -n deploy/rollback.sh` (sintaks bash valid).
3. Jalankan `php artisan schedule:list` untuk memverifikasi bahwa jadwal backup harian pukul 02:00 dan restart worker pukul 04:00 terdaftar.
4. Lakukan curl probe lokal: `curl -I http://localhost:8000/up` dan verifikasi header `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`.

## Rencana rollback

1. Kembalikan branch ke commit sebelum S08: `git reset --hard b035233`.
2. Hapus direktori `deploy/`.
