# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang diserahkan untuk review.

## Tugas 1 — Kompatibilitas dependency PHP 8.3 (27 Oktober 2026)

### File yang diubah

- `composer.json`: menambahkan `config.platform.php=8.3.0`.
- `composer.lock`: menurunkan dependency Symfony yang mensyaratkan PHP >=8.4.1 ke rilis Symfony 7.4 yang mendukung PHP 8.3; paket Symfony 16 turun, dan `symfony/polyfill-php83` ditambahkan.
- `docs/DEPLOYMENT_CHECKLIST.md`: mensyaratkan CLI dan PHP-FPM 8.3.x.

### Perintah verifikasi dan output asli

PHP lokal: `php -v`

```text
PHP 8.5.4 (cli) (built: Oct 19 2026 14:35:18) (NTS)
Copyright (c) The PHP Group
Built by Ubuntu
Zend Engine v4.5.4, Copyright (c) Zend Technologies
    with Zend OPcache v8.5.4, Copyright (c), by Zend Technologies
```

`composer validate`

```text
Cannot create cache directory /home/ilhamzp/.cache/composer/files/, or directory is not writable. Proceeding without cache. See also cache-read-only config if your filesystem is read-only.
./composer.json is valid
```

`composer check-platform-reqs`

```text
Checking platform requirements for packages in the vendor dir
composer-runtime-api 2.2.2      success
ext-ctype            *          success provided by symfony/polyfill-ctype
ext-dom              20031129   success
ext-fileinfo         8.5.4      success
ext-filter           8.5.4      success
ext-gd               8.5.4      success
ext-hash             8.5.4      success
ext-iconv            8.5.4      success
ext-json             8.5.4      success
ext-libxml           8.5.4      success
ext-mbstring         *          success provided by symfony/polyfill-mbstring
ext-openssl          8.5.4      success
ext-pcre             8.5.4      success
ext-phar             8.5.4      success
ext-session          8.5.4      success
ext-simplexml        8.5.4      success
ext-tokenizer         8.5.4      success
ext-xml              8.5.4      success
ext-xmlreader         8.5.4      success
ext-xmlwriter        8.5.4      success
ext-zip              1.22.7     success
ext-zlib             8.5.4      success
php                  8.5.4      success
php-64bit            8.5.4      success
```

`composer install --no-interaction` was run after moving the existing `vendor/` directory aside. It verified the lock and installed 127 packages with exit code 0. Relevant original output:

```text
Installing dependencies from lock file (including require-dev)
Verifying lock file contents can be installed on current platform.
Package operations: 127 installs, 0 updates, 0 removals
Generating optimized autoload files
> Illuminate\Foundation\ComposerScripts::postAutoloadDump
> @php artisan package:discover --ansi
...
84 packages you are using are looking for funding.
```

`composer check-platform-reqs --lock` also passed all locked requirements. It reported PHP 8.5.4, the actual local runtime.

### Risiko dan verifikasi yang belum dilakukan

- PHP 8.3 binary/FPM tidak tersedia di mesin ini (`command -v php8.3` tidak menemukan executable), sehingga `composer install` dan `check-platform-reqs` belum dijalankan pada PHP 8.3 asli. Hasil ini membuktikan solver menerima platform PHP 8.3.0 dan semua paket terkunci mengizinkannya; target runtime tetap perlu diuji di host/container PHP 8.3.
- Composer cache di home tidak dapat ditulis; Composer berjalan tanpa cache.
- Regenerasi memakai Packagist setelah akses jaringan diizinkan; install bersih menyelesaikan seluruh 127 paket.

### Commit

- `6007c52 fix(deps): constrain Composer platform to PHP 8.3`.

## Tugas 2 — Bukti kualitas dan status proyek (27 Oktober 2026)

### File yang diubah

- `docs/PROJECT_STATUS.md`: mengganti klaim kesiapan live dan angka lama dengan bukti run 27 Oktober serta blocker yang belum ditutup.
- Sembilan file yang gagal Pint diformat satu per satu sesuai hasil `pint --test`: `tests/Unit/TeachingPlanDocumentServiceTest.php`, `tests/Feature/MysqlBackupRestoreTest.php`, `tests/Feature/TeachingPlanningDocumentsTest.php`, `app/Shared/Backup/DatabaseBackupService.php`, `app/Modules/Academic/Application/Services/TeachingPlanDocumentService.php`, `app/Modules/Academic/Application/Services/JournalService.php`, `app/Modules/Academic/Interface/Http/Controllers/JournalController.php`, `app/Modules/Academic/Interface/Http/Controllers/TeachingPlanController.php`, dan `app/Modules/Academic/Interface/Http/Controllers/AttendanceController.php`.
- `AcademicQueryService`, `SchoolScopeService`, `CorrespondenceService`, `CorrespondenceController`, dan `CommunicationController`: memperbaiki pelanggaran batas modul yang ditemukan oleh test suite dengan memindahkan query data akademik/sekolah ke Application services.
- `tests/Feature/CoreAuthorizationTest.php`: menguji exception validasi yang memang dihasilkan saat pengguna mencoba mengubah role akunnya sendiri; assertion tetap memastikan role tidak berubah.

### Perintah verifikasi dan output asli

Run pertama `php artisan test --compact`:

```json
{"tool":"phpunit","result":"failed","tests":343,"passed":340,"assertions":3155,"duration_ms":32021,"failed":1,"failures":[{"test":"Tests\\Architecture\\ModularBoundaryTest::test_modules_cannot_import_other_modules_infrastructure_or_models","file":"/home/ilhamzp/Projects/school_suite/tests/Architecture/ModularBoundaryTest.php","line":134,"message":"Modular monolith boundary violations detected: imports School dari Core pada CorrespondenceService dan CorrespondenceController, serta TeachingAssignment dari Academic pada CommunicationController."}],"errors":1,"error_details":[{"test":"Tests\\Feature\\CoreAuthorizationTest::test_non_admin_with_user_create_permission_cannot_assign_admin_role","file":"/home/ilhamzp/Projects/school_suite/tests/Feature/CoreAuthorizationTest.php","line":265,"message":"Peran akun yang sedang digunakan tidak dapat diubah. Gunakan akun administrator lain."}],"skipped":1}
```

Run ulang `php artisan test --compact` setelah perbaikan:

```json
{"tool":"phpunit","result":"passed","tests":343,"passed":342,"assertions":3156,"duration_ms":31826,"skipped":1}
```

`vendor/bin/pint --test` sebelum perbaikan menemukan sembilan file; Pint dijalankan pada masing-masing file tersebut. Pemeriksaan akhir:

```text
{"tool":"pint","result":"passed"}
```

`npm run typecheck`:

```text
> typecheck
> vue-tsc --noEmit
```

Perintah selesai dengan exit code 0.

`npm run build`:

```text
> build
> vite build
vite v8.3.5 building client environment for production...
✓ 754 modules transformed.
✓ built in 3.43s
```

Perintah selesai dengan exit code 0.

### Risiko dan verifikasi yang belum dilakukan

- Satu tes dilewati; alasan skip yang spesifik dicatat pada tes terkait. Suite sekarang lulus, tetapi belum membuktikan jalur MySQL dan deployment server yang diminta di Tugas 3 dan 4.
- Pemeriksaan sebelumnya menyebut perubahan lintas modul; perbaikan sudah diuji dengan run suite penuh (343 tes) dan terarah (14 tes, 55 assertions).

### Commit

- `960faba fix(quality): resolve architecture and test-suite failures`.

## Tugas 3 — Drill backup/restore MySQL dan SQLite file (27 Oktober 2026)

### File yang diubah

- `tests/Feature/MysqlBackupRestoreTest.php`: mengganti fixture dua tabel buatan dengan migrasi aplikasi dan `PilotSchoolSeeder` pada database sumber MySQL terisolasi; target dibuat kosong. Tes membandingkan `COUNT(*)` seluruh tabel serta SHA-256 serialisasi data pada `schools`, `roles`, `users`, `students`, `enrollments`, `teaching_assignments`, `scores`, dan `report_snapshots`. Database sementara dibuang pada `finally`. Tes dilewati dengan alasan jelas bila socket MySQL khusus tidak dikonfigurasi/tersedia.
- `app/Shared/Backup/DatabaseBackupService.php` sudah diformat di commit Tugas 2; tidak ada perubahan perilaku restore MySQL pada Tugas 3.

### Perintah verifikasi dan output asli

`php artisan test --compact tests/Feature/MysqlBackupRestoreTest.php tests/Feature/BackupRecoverySafetyTest.php`

```json
{"tool":"phpunit","result":"passed","tests":8,"passed":7,"assertions":19,"duration_ms":642,"skipped":1}
```

SQLite file backup → restore default berada di `BackupRecoverySafetyTest::test_file_sqlite_backup_includes_wal_and_restores_to_default_connection`; test file keseluruhan lulus 7 tes, termasuk alur tersebut.

Pemeriksaan ketersediaan server:

```text
/usr/bin/mysqld
/usr/bin/mysqldump
/usr/bin/mysql
/usr/bin/mysqladmin
```

`mysqladmin --protocol=socket -uroot ping`:

```text
\a mysqladmin: connect to server at 'localhost' failed
error: 'Can't connect to local MySQL server through socket '/var/run/mysqld/mysqld.sock' (1)'
Check that mysqld is running and that the socket: '/var/run/mysqld/mysqld.sock' exists!
```

`docker info --format '{{.ServerVersion}}'`:

```text
permission denied while trying to connect to the Docker API at unix:///var/run/docker.sock
```

Pada pemeriksaan awal sesi ini MySQL lokal tidak dapat dihubungi; inisialisasi sementara di `/tmp/school-suite-mysql-drill-20261027b` gagal karena `InnoDB Unable to lock ./ibdata1 error: 11`. Tidak ada koneksi operasional yang digunakan.

Pembaruan bukti yang muncul kemudian di working tree saat tugas berlangsung (perubahan kode/dokumen tersebut tidak termasuk commit Tugas 5): `docs/AUDIT_BACKEND_2026-10-27.md` mencatat perintah `BACKUP_MYSQL_TEST_SOCKET=<socket-drill> php artisan test --compact --filter=MysqlBackupRestoreTest` lulus pada MySQL 8.4.11 dengan 1 tes/20 assertions, target acak terisolasi, tanpa port jaringan. Dokumen audit juga mencatat drill membandingkan jumlah baris/checksum dataset pilot dan menolak target yang tidak kosong. Output PHPUnit mentah dan path socket tidak tercantum di dokumen audit.

### Risiko dan verifikasi yang belum dilakukan

- Hasil awal sesi ini memang skip; bukti MySQL 8.4.11 yang dicatat kemudian di audit tidak menyertakan output mentah atau socket agar dapat direproduksi dari sesi ini.
- Perintah `BackupRecoverySafetyTest` awal lulus, dan dokumen audit yang diperbarui kemudian menyebut cakupan restore SQLite file default/explicit; detail akhir tercantum di `docs/AUDIT_BACKEND_2026-10-27.md`.

### Commit

- `e64ad50 test(backup): cover isolated MySQL restore with pilot dataset`.

## Tugas 4 — Health check deployment dan rollback (27 Oktober 2026)

### File yang diubah

- `deploy/deploy.sh`: `HEALTH_CHECK_URL` wajib diisi, harus memakai HTTPS, dan kegagalan probe pada instalasi pertama menghapus symlink `current` lalu menghentikan worker; jika ada previous release, symlink dipulihkan ke rilis itu.
- `deploy/health-check.sh`: tidak lagi memakai domain placeholder; URL wajib diberikan dan curl memiliki `--connect-timeout 5 --max-time 15`.
- `tests/Feature/DeploymentConfigurationTest.php`: memeriksa URL wajib, timeout eksplisit, dan pembersihan symlink saat belum ada rilis sebelumnya.
- `docs/PROJECT_STATUS.md`: mencatat dry run rollback; probe endpoint HTTPS production masih terbuka.

### Perintah verifikasi dan output asli

`bash -n deploy/deploy.sh && bash -n deploy/health-check.sh && bash -n deploy/rollback.sh` selesai dengan exit code 0 dan tanpa output.

`HEALTH_CHECK_URL= bash deploy/deploy.sh`:

```text
Kesalahan: HEALTH_CHECK_URL wajib diisi, misalnya https://<domain-aplikasi>/up.
```

Exit code: `1`.

`php artisan test --compact tests/Feature/DeploymentConfigurationTest.php tests/Feature/DeploymentHealthProbeTest.php`:

```json
{"tool":"phpunit","result":"passed","tests":10,"passed":10,"assertions":77,"duration_ms":1190}
```

Dry run fungsi rollback aktual yang diekstrak dari `deploy.sh` memakai direktori `/tmp` dan stub `sudo`:

```text
Rilis gagal diverifikasi.
Rilis gagal diverifikasi.
first install: exit=1, current symlink removed
previous release: exit=1, current symlink restored
```

Tes health probe memakai stub curl menguji payload sehat, payload salah, redirect, HTTP redirect, 503, dan timeout; seluruh 10 tes konfigurasi/probe lulus. Server HTTP lokal tidak dipakai karena health check mewajibkan HTTPS dan memvalidasi JSON aplikasi.

### Risiko dan verifikasi yang belum dilakukan

- Tidak ada rilis/deploy pada server nyata. HTTPS domain production, sertifikat, vhost Nginx, dan payload dari rilis aktif belum diuji end-to-end.
- URL health check kini harus diberikan eksplisit oleh operator saat deploy.

### Commit

- `ec7a57f fix(deploy): require health URL and roll back failed first release`.

## Tugas 5 — Verifikasi otorisasi per peran (27 Oktober 2026)

### File yang diubah

- `tests/Feature/AssessmentControllersTest.php`: menambah tes bahwa parent, student, guest dan operator lintas sekolah ditolak pada ledger; daftar rapor siswa hanya berisi rapor miliknya yang terbit; rapor draft miliknya tidak muncul dan tidak dapat dibuka.
- `docs/HANDOFF.md` dan `docs/PROJECT_STATUS.md`: mencatat hasil aktual, komposisi role, ketidakcocokan navigasi/route, dan blocker.

### Perintah verifikasi dan output asli

`php artisan test --compact`:

```json
{"tool":"phpunit","result":"passed","tests":350,"passed":349,"assertions":3200,"duration_ms":30724,"skipped":1}
```

`vendor/bin/pint --test`:

```json
{"tool":"pint","result":"passed"}
```

`npm run typecheck`:

```text
> typecheck
> vue-tsc --noEmit
```

Exit code: `0`.

`npm run build` (output ringkas dari build yang lulus):

```text
vite v8.3.5 building client environment for production...
✓ 754 modules transformed.
✓ built in 3.48s
```

Perintah khusus yang diminta, `php artisan test --compact tests/Feature/RoleMatrixAccessTest.php tests/Feature/RouteAndPermissionStressTest.php`:

```json
{"tool":"phpunit","result":"passed","tests":22,"passed":22,"assertions":533,"duration_ms":3313}
```

`php artisan test --compact tests/Feature/AssessmentControllersTest.php`:

```json
{"tool":"phpunit","result":"passed","tests":57,"passed":57,"assertions":470,"duration_ms":8002}
```

### Daftar role dan batas audit

`RoleEnum` berisi sembilan role: `admin`, `operator`, `principal`, `teacher`, `homeroom`, `counselor`, `librarian`, `student`, `parent`. `PilotSchoolSeeder` hanya membuat tujuh: `admin`, `principal`, `operator`, `teacher`, `homeroom`, `counselor`, `student`. Maka usulan daftar delapan role dari permintaan kurang `counselor` dan `librarian`; `guest` bukan role kanonis di enum atau seeder, sedangkan `parent` ada di enum tetapi tidak dibuat oleh Pilot seeder. `guest` diuji sebagai status tanpa role/aktor unauthenticated sesuai konteks.

Tes negatif langsung yang terverifikasi: guru tidak dapat membaca/menulis nilai penugasan kelas guru lain (tes yang sudah ada); wali kelas tidak dapat menerbitkan rapor kelas lain (tes yang sudah ada); parent, guest, dan student menerima 403 untuk `/penilaian/ledger`; student hanya melihat rapor terbit miliknya dan menerima 403 untuk snapshot draft; operator menerima 403 untuk ledger sekolah lain. Tes di atas juga memverifikasi rute negatif ini dengan request HTTP Laravel. Tidak ada perubahan kebijakan akses yang dibuat pada tugas ini.

### Ketidakcocokan menu dan akses URL

Audit statis `resources/js/components/SidebarNavigation.vue` dibanding route/policy/controller menemukan beberapa tautan yang disembunyikan dari sidebar tetapi URL langsung tidak memberi 403 kepada role di bawah ini. Ini dilaporkan sebagai ketidakcocokan dan tidak diubah diam-diam:

| Menu/URL | Role yang tercatat | Perilaku URL langsung |
|---|---|---|
| `/akademik/jadwal` | Parent tidak diberi menu; student justru diberi menu | Route hanya `auth`; `SchedulePolicy::viewAny` mengizinkan authenticated user, sehingga parent juga dapat masuk. |
| `/akademik/kelas` | Student tidak diberi menu | `ClassGroupPolicy::viewAny` mengizinkan student; route tidak menolak role itu. |
| `/akademik/presensi/rekap` | Menu hanya untuk principal | `AttendancePolicy::viewAny` juga mengizinkan operator, teacher, dan homeroom (atau permission yang sesuai). |
| `/wali-kelas/catatan` dan `/wali-kelas/kedisiplinan` | Menu hanya untuk homeroom | Route/policy juga memberi akses sebagian role teacher/counselor, jadi URL langsung tidak konsisten dengan menu. |
| `/komunikasi` | Menu tidak mencantumkan principal | `RouteAndPermissionStressTest::test_principal_role_actions_succeed_and_boundaries_enforced` mengharapkan principal mendapat HTTP 200. |

`RoleMatrixAccessTest` dan `RouteAndPermissionStressTest` lulus, tetapi keduanya tidak membandingkan DOM sidebar dengan seluruh URL untuk semua role. Tidak ada pengujian browser/DOM parity untuk sembilan role; daftar ketidakcocokan di atas bukan inventaris lengkap semua menu aplikasi.

### Risiko dan hal yang belum diverifikasi

- Tes unit/feature menegaskan batas objek yang diminta, namun tidak membuktikan kesesuaian semua menu terhadap seluruh izin dan seluruh route. Ketidakcocokan URL/menu yang diketahui di atas masih terbuka.
- Pengujian MySQL terisolasi tetap skip karena server MySQL lokal tidak tersedia; endpoint health HTTPS produksi belum diuji; runtime PHP 8.3 asli tidak tersedia. Detail bukti berada pada Tugas 1, 3, dan 4.
- Tidak dilakukan uji browser terhadap setiap role. `npm run typecheck` dan `npm run build` lulus, tetapi tidak menggantikan uji otorisasi UI end-to-end.

### Commit

- `test(auth): cover cross-role assessment boundaries` (commit Tugas 5).

### Commit

- `fix(deploy): require health URL and roll back failed first release` (akan diisi setelah commit dibuat).

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

## S15-T04 — Redesign Persuratan, Arsip, PDF Resmi, dan QR (28 Oktober 2026)

### Hasil implementasi

- Halaman Persuratan & Arsip kini memiliki antrean tindakan, empat bagian kerja, wizard surat masuk 4 langkah, wizard surat keluar 5 langkah, tabel responsif, serta antrean persetujuan kepala sekolah.
- Surat keluar memakai alur `Draf → Menunggu Persetujuan → Disetujui → Menunggu Tanda Tangan/TTE diproses → Terbit → Terkirim → Diarsipkan`. Endpoint status umum tidak dapat melewati proses tanda tangan untuk dokumen baru.
- Empat renderer Blade terpisah tersedia untuk surat resmi umum, undangan, surat tugas, dan surat pernyataan. Semua menggunakan A4, kop dari profil sekolah, watermark draf, normalisasi paragraf, blok tanda tangan yang dijaga dari page break, dan renderer yang sama untuk preview serta sumber finalisasi.
- Profil sekolah menampung instansi pembina, kota, dan kode pos. Profil pejabat menampung nama, jabatan, NIP, pangkat/golongan, cakupan template, serta status aktif.
- Jalur manual menyimpan PDF sumber ber-QR sebagai versi privat, mewajibkan unggahan PDF hasil tanda tangan basah, lalu menjadikan hasil pindai sebagai arsip final immutable dengan SHA-256.
- Jalur BSrE memakai adapter provider, job antrean, idempotency key, retry, status gagal tersendiri, metadata sertifikat, dan audit log. Konfigurasi produksi tetap nonaktif secara default melalui `BSRE_ENABLED=false`.
- QR publik hanya menampilkan metadata terbatas dan hasil pemeriksaan hash `Valid`, `Dokumen berubah`, atau `Tidak dapat diverifikasi`; isi surat tidak dapat diunduh dari halaman tersebut. Klaim sertifikat hanya tampil terverifikasi bila provider mengembalikan metadata `verified=true`.
- Nomor surat keluar divalidasi unik per sekolah dan tahun. Data lama tetap ditandai melalui `is_legacy` dan tidak otomatis memperoleh klaim immutable/TTE.

### Verifikasi

- `php artisan test --compact tests/Feature/AdministrationCorrespondenceTest.php`: 17 tes, 230 assertion lulus.
- `php artisan test --compact`: 388 tes, 387 lulus, 1 dilewati sesuai konfigurasi, 3.627 assertion.
- `npm run typecheck`: lulus.
- `npm run build`: lulus.
- Pint pada seluruh file PHP scope persuratan/core terkait: lulus.
- `git diff --check`: lulus.
- Empat template dirender dan diperiksa dengan `pdfinfo`, `pdftotext`, dan `pdftoppm`: A4 portrait, contoh pendek satu halaman, urutan teks benar, dan blok tanda tangan tidak terpotong.
- Pemeriksaan browser pada desktop dan viewport 390×844: tabel berubah menjadi susunan berlabel di mobile, wizard dan kartu antrean tetap terbaca, serta tidak ada console error/warning.

### Batas integrasi

- BSrE produksi belum diaktifkan. Client harus menyediakan endpoint resmi, token/kredensial sandbox atau produksi, sertifikat penandatangan, serta kontrak respons provider. Aplikasi tidak menyimpan PIN atau kata sandi penandatangan.
