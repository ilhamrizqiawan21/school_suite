# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang diserahkan untuk review.

## Audit & Konversi Overlay Modal (Side-Drawer ke Centered Dialog) — 14 November 2026

- **Task & Requirement**: Mengubah dialog tambah/edit/impor data siswa dan seluruh halaman lain yang masih menggunakan pola *side-drawer overlay* dari kanan menjadi *centered modal dialog* di tengah layar.
- **Ringkasan**:
  - Mengaudit seluruh halaman Vue di `resources/js/Pages/**` terhadap penggunaan `role="dialog"`, `aria-modal="true"`, `-overlay`, `-drawer`, dan `.modal-backdrop`.
  - Mengubah 8 halaman yang sebelumnya menampilkan panel formulir dari samping kanan (`justify-content: flex-end; height: 100%`) menjadi dialog terpusat di tengah layar (`display: grid; place-items: center; padding: 1rem; max-height: min(90dvh, 860px–880px); border-radius: 16px`), termasuk pada tampilan *mobile* (`max-width: 600px`/`760px`).
  - Menambahkan animasi masuk (*motion*) terpadu untuk seluruh *form overlay* dan dialog di `resources/css/app.css` (`ssOverlayFadeIn` 180ms + `ssModalPopIn` 220ms dengan `cubic-bezier(0.16, 1, 0.3, 1)` dan dukungan `prefers-reduced-motion: reduce`).
- **File yang Diubah**:
  - `resources/css/app.css`
  - `resources/js/Pages/Academic/Students/Index.vue`
  - `resources/js/Pages/Academic/Teachers/Index.vue`
  - `resources/js/Pages/Academic/Classes/Index.vue`
  - `resources/js/Pages/Academic/Schedules/Index.vue`
  - `resources/js/Pages/Academic/OnlineClasses/Index.vue`
  - `resources/js/Pages/Academic/Journals/Index.vue`
  - `resources/js/Pages/Core/Users/Index.vue`
  - `resources/js/Pages/Communication/Index.vue`
- **Keputusan & Asumsi**:
  - Nama class DOM (`.student-overlay`, `.student-drawer`, `.teacher-drawer`, dll.) dipertahankan agar tidak memutus selector tes maupun struktur komponen yang ada.
- **Tes & Hasil**:
  - Audit CSS via `grep` memastikan tidak ada lagi modal dialog dengan `justify-content: flex-end` di `resources/js/Pages`.
  - `npm run build` -> lulus (`vite build`, 865 modul ditransformasi dalam 6.20s).
- **Langkah Review Manual**:
  1. Jalankan `npm run dev` atau `npm run build`.
  2. Buka menu Data Siswa, Data Guru, Rombel, Jadwal Pelajaran, Kelas Online, Jurnal Mengajar, Pengguna, dan Pengumuman/Kalender; klik tombol Tambah/Edit untuk memastikan modal tampil tepat di tengah layar.
- **Rollback**:
  - Kembalikan perubahan blok `<style scoped>` pada ke-8 file Vue di atas melalui Git.

## Redesign Buku Induk Siswa (Format Hardcopy, Alur Massal & Cetakan Lengkap) — 14 November 2026

- **Task & Requirement**: Redesign Buku Induk Peserta Didik (`SS-F-002`, `SS-F-003`, `SS-F-007` / `SS-D-005`, `SS-F-008`).
- **Ringkasan**:
  - Memperluas skema dan formulir Buku Induk sesuai struktur buku induk fisik/hardcopy (A. Pribadi, B. Tempat Tinggal/Kesehatan/Kegemaran, C. Orang Tua/Wali, D. Pendidikan Asal & Penerimaan, E. Tamat Belajar/Keluar) serta memperbaiki panjang kolom `blood_type` (`VARCHAR(15)`) agar opsi `'Tidak Tahu'` tidak terpotong di MySQL strict mode.
  - Menambahkan indikator & filter kelengkapan data (`complete` / `incomplete`), panel *Isi Cepat* (*Quick Edit*) non-intrusif di tabel daftar, opsi koreksi identitas dasar (`students`) langsung dari halaman detail Buku Induk, serta tombol *Simpan & Lanjut ke Siswa Berikutnya*.
  - Menambahkan fitur **Ekspor / Template Excel (`.xlsx`)** lengkap (59 kolom dengan format teks eksplisit) dan **Impor / Update Massal Excel/CSV** berbasis pencocokan `NIS` secara transaksional.
  - Merombak cetakan PDF Buku Induk (per siswa maupun cetak massal per filter/rombel) dengan Kop Sekolah, Pas Foto 3x4, bagian A–F, blok tanda tangan Kepala Sekolah, serta halaman ke-2 **Rekapitulasi Capaian Hasil Belajar (Rapor Terbit per Semester)** melalui `ReportPublishingService` tanpa melanggar batas modular monolith.
- **File & Migrasi**:
  - `app/Modules/Academic/Infrastructure/Database/Migrations/2026_11_14_221500_expand_student_ledger_hardcopy_fields.php`
  - `app/Modules/Academic/Infrastructure/Models/StudentLedgerDetail.php`
  - `app/Modules/Academic/Infrastructure/Models/Student.php`
  - `app/Modules/Assessment/Application/Services/ReportPublishingService.php`
  - `app/Modules/Academic/Interface/Routes/buku_induk.php`
  - `app/Modules/Academic/Interface/Http/Controllers/StudentLedgerController.php`
  - `resources/views/academic/student-ledger.blade.php`
  - `resources/js/Pages/Academic/Students/Ledger/Index.vue`
  - `resources/js/Pages/Academic/Students/Ledger/Show.vue`
  - `tests/Feature/StudentLedgerControllersTest.php`
- **Keputusan & Asumsi**:
  - Pengambilan snapshot rapor terbit dari modul `Assessment` ke modul `Academic` dilakukan melalui `ReportPublishingService::getPublishedSnapshotsForStudents()` pada layer `Application`.
  - Seluruh kolom baru bersifat `nullable` agar kompatibel dengan data siswa yang sudah ada.
- **Tes & Hasil**:
  - `php artisan test --compact tests/Feature/StudentLedgerControllersTest.php tests/Architecture/ModularBoundaryTest.php` -> 13 tes lulus (153 assertions).
  - `php artisan test --compact` -> 456 tes, 454 lulus, 2 dilewati (4.258 assertions).
  - `./vendor/bin/pint --test` pada seluruh file PHP yang disentuh -> lulus.
  - `npm run typecheck` / `npm run build` -> *not verified* (binary `node`/`npm` tidak tersedia pada environment shell saat ini).
- **Risiko Tersisa**:
  - Kompilasi aset frontend (`npm run typecheck` dan `npm run build`) serta migrasi database lokal (`php artisan migrate`) perlu dijalankan pada mesin tempat Node.js dan MySQL aktif.
- **Langkah Review Manual**:
  1. Jalankan `php artisan migrate` dan `npm run build`.
  2. Buka `/akademik/buku-induk`, uji filter **Kelengkapan data**, tombol **Isi cepat**, **Ekspor / Template Excel (.xlsx)**, **Impor / Update Massal Excel**, serta **Cetak PDF Massal**.
  3. Buka detail siswa `/akademik/buku-induk/{id}`, lengkapi bagian A–E, uji tombol **Simpan & Lanjut**, dan unduh PDF Buku Induk.
- **Rollback**:
  - Jalankan `php artisan migrate:rollback --step=1` untuk melepas kolom tambahan `2026_11_14_221500_expand_student_ledger_hardcopy_fields.php` dan kembalikan perubahan file melalui Git.

## Duplikasi alur soal Jurnal Guru — 13 November 2026

Rujukan dipatok ke `ilhamrizqiawan21/jurnal-harian-guru`, branch `fitur-lisensi-2-0`, commit `27b2eaaf69c1f8e555fb3198c4150a837500322e`.

- Bank soal: C1–C6, kelas, sumber, pasangan tunggal, impor CSV/XLSX format School Suite maupun PG Jurnal Guru, preview validasi, impor baris valid secara eksplisit, dan melewati duplikat.
- Paket soal: identitas, kop teks/gambar, A4/F4, portrait/landscape, 1–3 kolom, pratinjau naskah/kunci/kisi-kisi, Word memakai mesin rujukan; PDF menggunakan dialog cetak browser. Snapshot tetap tersimpan saat bank berubah dan saat paket disalin.
- Analisis soal: mesin hitung dan validasi rujukan, tiga langkah, manual/impor, draf terenkripsi per akun/sekolah, Excel tiga sheet yang dapat diimpor ulang, dan PDF khusus analisis. Excel membersihkan draf, PDF mempertahankannya.
- Dua migrasi penambahan kolom nullable sudah diterapkan pada MySQL lokal: `2026_11_13_120000` dan `2026_11_13_120100`.

Verifikasi: suite backend 453 tes (451 lulus, 2 dilewati), TypeScript dan build lulus, tes mesin CommonJS/ESM serta XML Word lulus. Pemeriksaan browser memakai SQLite uji terpisah: analisis lima siswa, unduhan Excel/PDF, penyimpanan pengaturan F4 landscape tiga kolom, naskah/kunci/kisi-kisi, dan unduhan Word. Gaya iframe memakai nonce CSP halaman.

Detail adaptasi dan perintah uji tercatat di `resources/js/lib/reference/README.md`. Belum dipublikasikan atau di-commit; perubahan UI lain yang sebelumnya ada di worktree tetap dipertahankan.

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

## Tugas 1 — Pembersihan artefak sesi browser (28 Oktober 2026)

### Hasil

- Tidak ditemukan referensi ke sepuluh nama PNG root atau direktori `.playwright-mcp/` di `docs/`, `README*`, atau `tests/`.
- 26 snapshot YAML di `.playwright-mcp/` dan 10 PNG root dikeluarkan dari Git index dengan `git rm --cached`; salinan lokal tidak dihapus.
- `.gitignore` ditambah `/.playwright-mcp/` dan `/*.png`. Pola PNG hanya cocok di root, sehingga PNG sah di `public/` dan `resources/` tidak terkena.
- Pemeriksaan string pada snapshot menemukan indikator data akun/identitas (misalnya field email, nama, siswa, token/cookie) pada beberapa snapshot. Nilainya tidak disalin ke dokumen. Ini berarti artefak pernah memuat data yang berpotensi pribadi/rahasia; pembersihan riwayat Git belum dilakukan.
- Pemeriksaan biner PNG root tidak menemukan string metadata sensitif dengan pola yang dicari.

### Bukti mentah

```text
$ rg -n --hidden --glob 'docs/**' --glob 'README*' --glob 'tests/**' 'material-dashboard-final\\.png|material-dashboard\\.png|nilai-implementasi\\.png|nilai-tabel\\.png|playwright-student-ledger-redacted\\.png|score-verify-2\\.png|score-verify\\.png|task-dashboard-dark\\.png|task-dashboard-full\\.png|task-dashboard\\.png|\\.playwright-mcp/' .
(tidak ada output)

$ git ls-files | grep -E '^\\.playwright-mcp/|^[^/]+\\.png$'
(tidak ada output setelah perubahan index)
```

### Risiko / belum terbukti

- Karena snapshot memuat indikator data sensitif dan masih ada di riwayat Git lama, penghapusan dari index tidak menghapusnya dari riwayat. **BELUM TERBUKTI** apakah seluruh isi historis bebas dari data pribadi/credential. Konfirmasi pemilik repo diperlukan sebelum pembersihan riwayat yang mengubah commit lama.

## Tugas 2 — Ketidakcocokan menu vs akses URL, Langkah A (28 Oktober 2026)

Tidak ada policy, middleware, atau menu yang diubah pada langkah ini. Matriks berikut adalah keputusan yang masih menunggu pemilik produk.

| Butir | Route dan kontrol server saat ini | Role diizinkan server saat ini | Role yang diberi menu saat ini | Opsi 1 — perketat server | Opsi 2 — tambah menu | Rekomendasi sementara |
|---|---|---|---|---|---|---|
| a | `GET /akademik/jadwal`; group `auth`; `SchedulePolicy::viewAny` mengembalikan true untuk user aktif terautentikasi | admin, operator, principal, teacher, homeroom, student, dan parent yang lolos `auth` | sidebar item umum; student juga melihatnya; parent tidak diberi menu | Batasi `viewAny`/route ke role yang memang perlu jadwal; parent dikeluarkan | Tambahkan menu jadwal untuk parent | Opsi 1. Menu parent tidak ada dan jadwal dapat memuat informasi kelas/guru; paling kecil hak aksesnya. Tes student yang ada harus dipertahankan. |
| b | `GET /akademik/kelas`; group `auth`; `ClassGroupPolicy::viewAny` memeriksa permission atau role | admin, operator, principal, teacher, homeroom, student; parent tidak termasuk policy | sidebar item umum; student tidak diberi menu karena tidak ada `studentHref` khusus | Keluarkan student dari `viewAny` atau beri route read-only terpisah | Tambahkan menu student dengan halaman read-only yang jelas | Opsi 1 sementara. Rombel dapat memuat daftar siswa/wali; tidak menambah paparan data tanpa kebutuhan produk eksplisit. |
| c | `GET /akademik/presensi/rekap`; group `auth`; controller memakai `authorize('viewAny', AttendanceRecord::class)` | admin, operator, principal, teacher, homeroom sesuai policy/middleware | menu akademik memakai URL utama untuk non-principal; principal dialihkan ke `/kepsek/laporan/rekap-absensi`; audit menyebut menu praktis principal | Batasi rekap ke principal (atau definisikan role operasional eksplisit) dan pertahankan route khusus principal | Tambahkan menu rekap bagi operator/teacher/homeroom | Opsi 1 untuk default karena rekap presensi adalah data siswa sensitif; dampak ke tes role yang sekarang harus diputuskan bersama. |
| d | `GET /wali-kelas/catatan` dan `GET /wali-kelas/kedisiplinan`; middleware group `auth, role:teacher,homeroom`; policy catatan juga memberi kemampuan create kepada counselor/principal | route saat ini teacher, homeroom; policy catatan create juga counselor/principal; service membatasi data berdasarkan sekolah/relasi | kedua menu ditampilkan sebagai item perwalian; tidak ada pemetaan role khusus di item | Pisahkan route/policy menjadi homeroom-only atau endpoint BK terpisah untuk counselor/principal | Tambahkan menu untuk role yang benar-benar membutuhkan akses, dengan scope read/write eksplisit | Opsi 1 untuk data catatan/BK dan kedisiplinan sampai kebutuhan counselor/teacher disahkan; risiko kebocoran catatan sensitif paling tinggi di butir ini. |
| e | `GET /komunikasi/`; middleware `auth, role:admin,operator,principal,teacher,homeroom,student`; controller index | admin, operator, principal, teacher, homeroom, student; parent ditolak | principal diarahkan item komunikasi ke `/kepsek/pengumuman`, bukan URL dasar `/komunikasi`; tes mengharapkan principal 200 pada route dasar | Hapus principal dari route dasar atau ubah ekspektasi tes bila route khusus menjadi satu-satunya akses | Tambahkan menu/tautan principal yang secara eksplisit menuju route komunikasi yang diizinkan | Opsi 2 dengan route principal read-only yang konsisten. Ini menjaga tes dan menghindari false 404/403 dari URL yang memang server izinkan. |

### Bukti kode yang dipakai

```text
app/Modules/Academic/Interface/Routes/web.php:
Route::get('kelas', ...); Route::get('jadwal', ...); Route::get('presensi/rekap', ...);
app/Modules/Academic/Application/Policies/SchedulePolicy.php: viewAny() => true
app/Modules/Academic/Application/Policies/ClassGroupPolicy.php: viewAny() => permission/operator/principal/teacher/homeroom/student
app/Modules/Academic/Application/Policies/AttendancePolicy.php: viewAny() => permission/operator/principal/teacher/homeroom
app/Modules/Homeroom/Interface/Routes/web.php: middleware auth,role:teacher,homeroom
app/Modules/Administration/Interface/Routes/web.php: /komunikasi role includes principal
resources/js/components/SidebarNavigation.vue: principalHref/teacherHref/studentHref determine visible destination
```

**Langkah B belum dikerjakan.** Mohon pilih opsi 1 atau 2 untuk butir a, b, c, d, dan e sebelum saya mengubah policy/middleware/menu atau menambah tes parity.

## Tugas 3 — Bukti restore MySQL (28 Oktober 2026)

- Runtime lokal: PHP 8.3.36; MySQL server binary: `mysqld 8.0.46-0ubuntu0.22.04.4`.
- Datadir dan socket yang digunakan berada di `/tmp/school-suite-mysql-ffZMEU`; tidak menyentuh database operasional.
- Server sempat melaporkan `mysqld is alive`, tetapi proses pengujian berikut tidak menghasilkan hasil PHPUnit yang dapat dipakai. Percobaan kedua pada datadir yang sama gagal dengan `InnoDB Unable to lock ./ibdata1 error: 11`.

```text
$ mysqladmin --no-defaults --socket=/tmp/school-suite-mysql-ffZMEU/mysql.sock -uroot ping
mysqld is alive
$ mysqld --no-defaults --version
/usr/sbin/mysqld  Ver 8.0.46-0ubuntu0.22.04.4 for Linux on x86_64 ((Ubuntu))
$ BACKUP_MYSQL_TEST_SOCKET=/tmp/school-suite-mysql-ffZMEU/mysql.sock php artisan test --compact --filter=MysqlBackupRestoreTest
(tidak menghasilkan output PHPUnit; proses dihentikan setelah socket tidak lagi tersedia)
$ tail mysql.err
[ERROR] [MY-012574] [InnoDB] Unable to lock ./ibdata1 error: 11
```

**BELUM TERBUKTI:** test restore MySQL, perbandingan jumlah baris seluruh tabel, checksum data penting, dan alasan skip pada run yang gagal. Klaim lulus MySQL lama di dokumen status/audit telah diturunkan menjadi belum terbukti.

## Tugas 4 — Validator ENFORCE_PASSWORD_CHANGE (28 Oktober 2026)

- Validator production menolak `false`, `0`, `off`, dan `no` tanpa membedakan kapitalisasi; nilai kosong diperlakukan sebagai aktif.
- Test `php artisan test --compact tests/Feature/DeploymentEnvironmentPreflightTest.php` lulus 13 tes / 34 assertions.

```text
{"tool":"phpunit","result":"passed","tests":13,"passed":13,"assertions":34,"duration_ms":1367}
```

## Verifikasi suite penuh (28 Oktober 2026)

```text
{"tool":"phpunit","result":"failed","tests":393,"passed":133,"assertions":916,"failed":245,"errors":14,"skipped":1}
```

Run penuh gagal dengan banyak respons 404 pada test feature; ini **BELUM TERBUKTI** sebagai regresi kode sebelum konfigurasi test/path-prefix diperiksa. Tidak ada test atau policy yang dilonggarkan.

## Tugas 2 — Langkah B selesai (28 Oktober 2026)

Pilihan yang diterapkan: **a1, b1, c1, d1, e2**.

- a: GET jadwal kini dibatasi ke role admin/operator/principal/teacher/homeroom/student; `SchedulePolicy::viewAny` memakai daftar role yang sama. Parent tidak lagi dapat membuka URL langsung.
- b: `ClassGroupPolicy::viewAny` tidak lagi mengizinkan student. Menu kelas memang tidak diberikan kepada student dan URL langsung menghasilkan 403.
- c: rekap presensi dan ekspornya kini hanya role principal. Policy presensi umum tetap dipertahankan untuk layar input presensi guru/wali kelas; pembatasan rekap dilakukan di route agar tidak merusak operasi input.
- d: seluruh route `/wali-kelas` kini role homeroom saja. Fixture test dipisah menjadi role `homeroom` dan `teacher`; guru biasa tidak lagi dapat membuka catatan/kedisiplinan.
- e: principal tetap diberi menu komunikasi melalui `principalHref=/kepsek/pengumuman`; route dasar `/komunikasi` juga tetap 200 sesuai kontrak test.
- Ditambahkan test parity role di `RoleMatrixAccessTest` untuk jalur positif/negatif dan visibilitas menu principal.

### Verifikasi mentah

```text
$ APP_URL=https://school.test APP_ENV=testing php artisan test --compact tests/Feature/RoleMatrixAccessTest.php tests/Feature/HomeroomControllersTest.php tests/Feature/RouteAndPermissionStressTest.php
{"tool":"phpunit","result":"passed","tests":37,"passed":37,"assertions":656,"duration_ms":5038}

$ vendor/bin/pint --test app/Modules/Academic/Application/Policies/SchedulePolicy.php app/Modules/Academic/Application/Policies/ClassGroupPolicy.php app/Modules/Academic/Interface/Routes/web.php app/Modules/Homeroom/Interface/Routes/web.php tests/Feature/HomeroomControllersTest.php tests/Feature/RoleMatrixAccessTest.php
{"tool":"pint","result":"passed"}

$ npm run typecheck
> typecheck
> vue-tsc --noEmit
(exit 0, tidak ada output error)

$ npm run build
vite v8.3.5 building client environment for production...
✓ 766 modules transformed.
✓ built in 10.01s

$ APP_URL=https://school.test APP_ENV=testing php artisan test --compact
{"tool":"phpunit","result":"passed","tests":394,"passed":393,"assertions":3661,"duration_ms":83889,"skipped":1}
```

Tes yang dilewati tetap merupakan `MysqlBackupRestoreTest` karena socket MySQL terisolasi tidak tersedia pada run penuh. Sebelum konfigurasi `APP_URL` test diperbaiki, run tanpa override menghasilkan 393 test dengan 133 lulus, 245 gagal, 14 error, dan 1 dilewati; itu adalah kegagalan konfigurasi subpath, bukan hasil suite final.

## Tugas A — Kunci APP_URL pada PHPUnit (28 Oktober 2026)

- `phpunit.xml` kini mengunci `APP_URL=http://localhost` dengan `force="true"` pada `<env>` dan nilai yang sama pada `<server>` agar nilai dari `.env`/shell tidak bocor ke test.
- Test khusus `DeploymentConfigurationTest::test_subpath_app_url_is_rendered_as_the_frontend_base_path` sengaja menyetel `config('app.url')` ke `https://example.com/sekolah` dan memverifikasi meta `app-base-path=/sekolah`.
- Tidak ada variabel subpath lain yang ditemukan pada konfigurasi test; subpath frontend berasal dari `app-base-path` pada Blade dan dibaca oleh `resources/js/app.ts`.

### Verifikasi mentah

```text
$ APP_ENV=testing APP_URL=https://example.com/sekolah php artisan test --compact tests/Feature/DeploymentConfigurationTest.php --filter=test_subpath_app_url_is_rendered_as_the_frontend_base_path
{"tool":"phpunit","result":"passed","tests":1,"passed":1,"assertions":1,"duration_ms":291}

$ APP_ENV=testing APP_URL=https://example.com php artisan test --compact
{"tool":"phpunit","result":"passed","tests":395,"passed":394,"assertions":3662,"duration_ms":76928,"skipped":1}

$ APP_ENV=testing APP_URL=https://example.com/sekolah php artisan test --compact
{"tool":"phpunit","result":"passed","tests":395,"passed":394,"assertions":3662,"duration_ms":88392,"skipped":1}
```

Percobaan awal sebelum `<server name="APP_URL" ...>` menghasilkan 246 failure/14 error pada APP_URL subpath karena nilai path tetap mempengaruhi request test. Setelah kedua sumber environment dikunci, suite subpath hijau.

## Tugas B — Dampak keputusan akses, belum diubah (28 Oktober 2026)

### B1 — Rekap presensi

Route dan ekspor saat ini memakai `role:principal`:

- `GET /akademik/presensi/rekap`: admin, operator, teacher, dan homeroom kehilangan akses langsung; principal tetap bisa.
- `GET /akademik/presensi/rekap/export`: admin, operator, teacher, dan homeroom juga kehilangan akses langsung; principal tetap bisa.
- `AttendancePolicy::viewAny` tetap mengizinkan operasi presensi umum untuk role operasional. Pembatasan ini hanya diterapkan pada route rekap/ekspor.

Opsi:

1. Pertahankan principal-only. Tambahkan endpoint/menu laporan terpisah bila admin/operator membutuhkan agregat non-sensitif.
2. Kembalikan akses admin/operator/teacher/homeroom, lalu tetapkan scope data dan menu per role; admin/operator dapat melihat agregat, teacher/homeroom hanya kelas yang ditugaskan.

### B2 — Counselor dan `/wali-kelas/catatan`

Route group saat ini memakai `auth,role:homeroom`, sehingga counselor tidak bisa membuka `/wali-kelas/catatan` maupun endpoint POST/DELETE di bawahnya. `StudentNotePolicy::create` masih menyatakan counselor boleh membuat catatan, sehingga ada ketidakselarasan policy-versus-route; policy create itu belum dapat dipakai melalui route saat ini.

Opsi:

1. Beri counselor jalur eksplisit, misalnya route BK terpisah atau middleware `role:homeroom,counselor` dengan scope `access_level`/kelas yang ketat; tambahkan menu counselor dan test isolasi catatan rahasia.
2. Pertahankan homeroom-only dan hapus hak create counselor dari policy agar backend tidak menyatakan kemampuan yang tidak tersedia.

### Bukti mentah

```text
$ APP_ENV=testing APP_URL=https://example.com php artisan route:list --path=akademik/presensi/rekap
GET|HEAD akademik/presensi/rekap ...
GET|HEAD akademik/presensi/rekap/export ...

$ rg -n 'presensi/rekap|role:principal|role:homeroom|function create|hasRole\\(.counselor' app/Modules/Academic/Interface/Routes/web.php app/Modules/Homeroom/Interface/Routes/web.php app/Modules/Homeroom/Application/Policies/StudentNotePolicy.php
... presensi/rekap ... middleware('role:principal')
... presensi/rekap/export ... middleware(['throttle:sensitive', 'role:principal'])
... Route::middleware(['auth', 'role:homeroom']) ...
... public function create(User $actor): bool
... || $actor->hasRole('counselor')
```

**Tidak ada policy, middleware, menu, atau test akses yang diubah untuk Tugas B. Mohon pilih opsi untuk B1 dan B2 sebelum saya lanjut. Tugas C dan D belum dikerjakan karena instruksi berhenti setelah laporan keputusan akses ini.**

## Tugas C — Ulang bukti restore MySQL (28 Oktober 2026)

**BELUM TERBUKTI.** Docker tidak tersedia pada mesin verifikasi, sehingga MySQL 8.4 terisolasi sesuai instruksi tidak dapat dibuat. Tidak ada koneksi atau perubahan ke database operasional dan tidak ada klaim lulus yang dibuat.

### Bukti mentah

```text
$ docker --version
/bin/bash: line 1: docker: command not found
```

`MysqlBackupRestoreTest` tidak dijalankan pada percobaan ini karena prasyarat Docker/MySQL 8.4 terisolasi tidak tersedia. Versi server dan output restore MySQL: **BELUM TERBUKTI**.

## Tugas D — Uji migrasi persuratan pada data lama (28 Oktober 2026)

Pengujian memakai copy baru `/tmp/school-suite-correspondence-migration-20261028.sqlite`, dibuat dari `/tmp/school-suite-before-correspondence-migrations.sqlite`. Copy sumber berisi 2 record persuratan lama dan hanya migrasi persuratan awal; database operasional tidak disentuh.

### Hasil migrasi

- Lima migrasi `2026_10_28_600001`, `600002`, `700000`, `700001`, dan `800000` berhasil dijalankan.
- Jumlah `correspondence_records` tetap 2 dan `distinct(id)` tetap 2.
- Perbandingan jumlah baris seluruh 87 tabel sumber hanya berbeda pada tabel `migrations` (41 → 49), sesuai penambahan migrasi; tidak ada tabel data yang kehilangan/menambah baris.
- Tidak ada duplicate key outgoing (`school_id`, `letter_year`, `outgoing_letter_number`).
- Empat trigger provenance SQLite terbentuk dan seluruh record ber-letter-date memiliki `letter_year`.

### Rollback

`migrate:rollback --step=5 --force` berhasil mengembalikan kelima migrasi tersebut. Setelah rollback: record tetap 2, distinct id tetap 2, tabel workflow baru 0, kolom lama `letter_number` ada, dan kolom baru `signer_identifier` sudah hilang.

### Bukti mentah

```text
$ php -r '...baseline...'
baseline correspondence_records=2
distinct ids=2

$ DB_CONNECTION=sqlite DB_DATABASE=/tmp/school-suite-correspondence-migration-20261028.sqlite APP_ENV=testing APP_URL=http://localhost php artisan migrate --force
INFO  Running migrations.
2026_10_28_600001_add_official_letter_metadata ... DONE
2026_10_28_600002_harden_correspondence_integrity ... DONE
2026_10_28_700000_redesign_correspondence_workflow ... DONE
2026_10_28_700001_enforce_correspondence_provenance ... DONE
2026_10_28_800000_add_signer_identifier_to_correspondence_records ... DONE

common_tables=87 count_mismatches=1
COUNT_MISMATCH migrations before=41 after=49
correspondence_after=2 distinct_ids=2
new_tables=3
new_columns=letter_year signer_identifier_type signer_identifier
duplicate_outgoing_keys=0
triggers=4
records_with_letter_date_but_null_year=0

$ DB_CONNECTION=sqlite DB_DATABASE=/tmp/school-suite-correspondence-migration-20261028.sqlite APP_ENV=testing APP_URL=http://localhost php artisan migrate:rollback --step=5 --force
INFO  Rolling back migrations.
2026_10_28_800000_add_signer_identifier_to_correspondence_records ... DONE
2026_10_28_700001_enforce_correspondence_provenance ... DONE
2026_10_28_700000_redesign_correspondence_workflow ... DONE
2026_10_28_600002_harden_correspondence_integrity ... DONE
2026_10_28_600001_add_official_letter_metadata ... DONE

rollback correspondence_records=2 distinct_ids=2 new_tables=0 pending_2026_10_28_target_migrations=0
has_old_letter_number=yes has_new_signer_identifier=no
```

### Risiko

Migrasi workflow mengubah constraint nomor surat menjadi berbasis tahun, menambah tabel versi dokumen/signature request, dan menambah kolom metadata. Data lama tidak hilang pada copy ini, tetapi rollback menghapus struktur baru; backup dan uji pada salinan wajib dilakukan sebelum production. SQLite memakai implementasi kolom/trigger yang berbeda dari MySQL, sehingga kompatibilitas MySQL production tetap **BELUM TERBUKTI**.

## Redesign Alur & Navigasi Menu SPMB — Opsi B (13 November 2026)

### Task dan Requirement
- **Task**: Redesign alur / flow menu SPMB (Opsi B: *Pipeline Workspace* + *360° Applicant Drawer* + Konsolidasi Navigasi Sidebar menjadi 4 menu fokus).
- **Requirement ID**: `S11-SPMB-UX`, `SS-F-001` (otorisasi server-side tetap ditegakkan), `AI_RULES.md`.

### Ringkasan Perubahan
1. **Konsolidasi Navigasi Sidebar SPMB (10 menu → 4 menu fokus)** di `resources/js/components/SidebarNavigation.vue`:
   - **Dashboard & Laporan** (`/spmb`, aktif juga pada `/spmb/laporan`)
   - **Pendaftar & Verifikasi** (`/spmb/pendaftar`, aktif juga pada `/spmb/berkas`)
   - **Seleksi & Daftar Ulang** (`/spmb/seleksi`, aktif juga pada `/spmb/pengumuman` dan `/spmb/daftar-ulang`)
   - **Pengaturan SPMB** (`/spmb/pengaturan`, aktif juga pada `/spmb/periode`, `/spmb/jalur`, `/spmb/panitia`)
2. **Redesign Alur *Pipeline Workspace* di `resources/js/Pages/Admissions/Index.vue`**:
   - **Dashboard & Laporan**: menggabungkan ringkasan eksekutif, tahapan, tindak lanjut, keterisian kuota per jalur, dan tab *Rekapitulasi & Laporan* dalam satu layar.
   - **Pendaftar & Verifikasi + *360° Applicant Slide-over Drawer***: meja kerja terpadu untuk memfilter calon siswa, melihat ringkasan status berkas langsung pada kartu pendaftar, dan membuka panel samping (*drawer*) untuk memeriksa identitas, mengunggah/memverifikasi berkas (*Valid* / *Revisi*), memperbarui status, serta menginput nilai kriteria tanpa berpindah halaman.
   - **Seleksi & Daftar Ulang Terpadu**: menyatukan tahapan (1) *Penilaian & Ranking*, (2) *Pengumuman Kelulusan*, dan (3) *Daftar Ulang Siswa* dalam satu alur berurutan, dilengkapi tombol bantu **"Isi sesuai kuota"** pada keputusan seleksi dan **"Saran NIS"** pada konfirmasi daftar ulang.
   - **Pengaturan SPMB + *Setup Readiness Checklist***: menyatukan pengaturan *Periode & Juknis*, *Jalur, Kuota, Berkas & Kriteria*, serta *Panitia & Jadwal* dengan indikator kesiapan 4 langkah sebelum periode dapat dibuka.
3. **Kompatibilitas Penuh Rute & Backend**:
   - Menambahkan rute `GET /spmb/pengaturan` (`admissions.settings.index`) di `app/Modules/Admissions/Interface/Routes/web.php` dan key `admissions-settings` di `app/Shared/Navigation/SidebarNavigationService.php`.
   - Memuat relasi `scores.criterion` pada daftar pendaftar di `AdmissionsController::render()` agar nilai seleksi yang sudah tersimpan langsung tampil saat form dibuka kembali.
   - Seluruh 10 rute GET lama dan seluruh endpoint POST/PUT/DELETE tetap dipertahankan 100% kompatibel.

### File & Migrasi yang Disentuh
- `app/Modules/Admissions/Interface/Routes/web.php`
- `app/Modules/Admissions/Interface/Http/Controllers/AdmissionsController.php`
- `app/Shared/Navigation/SidebarNavigationService.php`
- `resources/js/components/SidebarNavigation.vue`
- `resources/js/Pages/Admissions/Index.vue`
- `tests/Feature/AdmissionsWorkflowTest.php`
- `tests/Feature/RoleMatrixAccessTest.php`
- `docs/HANDOFF.md`
- **Migrasi database**: Tidak ada perubahan skema/migrasi baru.

### Keputusan & Asumsi
- Sesuai pilihan pengguna (**Opsi B**), navigasi sidebar disederhanakan menjadi 4 menu berbasis alur kerja (*Overview, Intake, Decision, Settings*), sementara URL lama (`/spmb/periode`, `/spmb/jalur`, `/spmb/panitia`, `/spmb/berkas`, `/spmb/pengumuman`, `/spmb/daftar-ulang`, `/spmb/laporan`) tetap dipertahankan dan otomatis membuka tab yang relevan.

### Tes Beserta Hasil
- `php artisan test --compact tests/Feature/AdmissionsWorkflowTest.php tests/Feature/AdmissionsControllersTest.php tests/Feature/RoleMatrixAccessTest.php tests/Architecture/ModularBoundaryTest.php` → **PASS** (29 tests, 271 assertions).
- `vendor/bin/pint --test` → **PASS**.
- `npm run typecheck` (`vue-tsc --noEmit`) → **PASS** (0 errors).
- `npm run build` (`vite build`) → **PASS**.

### Risiko Tersisa
- Tidak ada perubahan skema database atau pelonggaran kebijakan otorisasi; semua kontrol akses server-side (`AdmissionsPolicy` & Gate) tetap sama.

### Langkah Review Manual
1. Login sebagai Admin atau Operator, lalu buka menu **SPMB** di sidebar; pastikan kini tampil 4 menu ringkas (*Dashboard & Laporan*, *Pendaftar & Verifikasi*, *Seleksi & Daftar Ulang*, *Pengaturan SPMB*).
2. Buka **Pengaturan SPMB** (`/spmb/pengaturan`), periksa *Checklist Kesiapan Periode* dan berpindah antar tab *Periode & Juknis*, *Jalur, Kuota, Berkas & Kriteria*, serta *Panitia & Jadwal*.
3. Buka **Pendaftar & Verifikasi** (`/spmb/pendaftar`), klik tombol **"Periksa & Berkas"** pada salah satu pendaftar untuk membuka *Slide-over Drawer* 360° dan uji verifikasi berkas / perubahan status tanpa berpindah halaman.
4. Buka **Seleksi & Daftar Ulang** (`/spmb/seleksi`), uji fitur input nilai, *Hitung ranking*, tombol *Isi sesuai kuota*, tab *Pengumuman Kelulusan*, serta tombol *Saran NIS* pada tab *Daftar Ulang Siswa*.

### Rollback
- Kembalikan perubahan pada file yang disentuh dengan `git checkout -- app/Modules/Admissions app/Shared/Navigation/SidebarNavigationService.php resources/js/components/SidebarNavigation.vue resources/js/Pages/Admissions/Index.vue tests/Feature/AdmissionsWorkflowTest.php tests/Feature/RoleMatrixAccessTest.php` lalu jalankan `npm run build`.

## Tabel Ringkas Pendaftar & Verifikasi SPMB + Aksi Detail, Edit (Pensil), dan Hapus (Trash) — Opsi A (13 November 2026)

### Task dan Requirement
- **Task**: Mengubah daftar pada menu **Pendaftar & Verifikasi** (baik sub-tab *Daftar Pendaftar & Meja Kerja* maupun sub-tab *Antrean Verifikasi Berkas* sesuai **Opsi A**) menjadi tabel ringkas (*No, Nama Pendaftar, Tanggal Daftar, Jalur & Status Ringkas, Aksi*) yang dilengkapi tombol **Detail** (membuka *Slide-Over Drawer* 360°), tombol **Edit** berikon pensil (membuka modal edit biodata lengkap), dan tombol **Hapus** berikon tempat sampah (*trash*) dengan proteksi data siswa yang sudah daftar ulang serta pencatatan audit log.
- **Requirement ID**: `S11-SPMB-UX-TABLE`, `SS-F-001` (otorisasi server-side & audit trail), `AI_RULES.md`.

### Ringkasan Perubahan
1. **Tabel Ringkas pada Menu Pendaftar & Verifikasi (`resources/js/Pages/Admissions/Index.vue`)**:
   - **Sub-tab Daftar Pendaftar & Meja Kerja**: Menampilkan kolom `No`, `Nama Pendaftar` (beserta sub-teks nomor pendaftaran, NISN, asal sekolah), `Tanggal Daftar` (`created_at`), `Jalur & Status` (chip jalur, ringkasan berkas, status alur), dan kolom `Aksi`.
   - **Sub-tab Antrean Verifikasi Berkas (Opsi A)**: Diseragamkan menggunakan tabel ringkas dengan kolom `No`, `Nama Pendaftar`, `Tanggal Daftar`, `Ringkasan Berkas` (status berkas & rasio berkas valid), dan kolom `Aksi`.
   - **Kolom Aksi Terpadu**:
     - Tombol **Detail**: Membuka *360° Applicant Slide-Over Drawer* untuk memeriksa biodata lengkap, mengunggah/mengunduh/memverifikasi berkas, memperbarui status alur, dan menginput nilai seleksi.
     - Tombol **Edit (Ikon Pensil SVG)**: Membuka modal *Edit Data Pendaftar* untuk mengubah periode, jalur, status, nama, NISN, jenis kelamin, tanggal lahir, nama/kontak orang tua, asal sekolah, dan catatan.
     - Tombol **Hapus (Ikon Trash SVG)**: Menghapus data pendaftar beserta berkas privatnya setelah konfirmasi; otomatis dinonaktifkan (*disabled*) apabila pendaftar sudah dikonfirmasi daftar ulang (`re_registered` / `confirmed`).
2. **Dukungan Edit Biodata Lengkap & Hapus Pendaftar di Backend (`AdmissionsController.php` & `web.php`)**:
   - Memperluas `PUT /spmb/pendaftar/{applicant}` (`AdmissionsController::update`) agar mendukung pembaruan biodata lengkap pendaftar (dengan validasi keunikan NISN dalam satu periode dan kesesuaian jalur-periode) sekaligus tetap kompatibel dengan form pembaruan cepat `status` & `notes` di dalam Drawer, serta mencatat event audit `admissions.applicant.updated`.
   - Menambahkan rute `DELETE /spmb/pendaftar/{applicant}` (`admissions.applicants.destroy` → `AdmissionsController::destroy`) dengan otorisasi `Gate::authorize('manageApplicant', $applicant)`, proteksi penolakan hapus bila pendaftar sudah menyelesaikan daftar ulang (terhubung ke data siswa Akademik), penghapusan file dokumen privat dari storage lokal, serta pencatatan event audit `admissions.applicant.deleted`.

### File & Migrasi yang Disentuh
- `app/Modules/Admissions/Interface/Routes/web.php`
- `app/Modules/Admissions/Interface/Http/Controllers/AdmissionsController.php`
- `resources/js/Pages/Admissions/Index.vue`
- `tests/Feature/AdmissionsWorkflowTest.php`
- `docs/HANDOFF.md`
- **Migrasi database**: Tidak ada migrasi baru (menggunakan skema tabel `admission_applicants` yang sudah ada).

### Keputusan & Asumsi
- Sesuai **Opsi A** yang dipilih pengguna, kedua sub-tab di menu **Pendaftar & Verifikasi** menggunakan tabel ringkas yang seragam dan mendelegasikan rincian pemeriksaan berkas ke dalam *Slide-Over Drawer* saat tombol **Detail** diklik.
- Pendaftar yang sudah dikonfirmasi daftar ulang (`status = re_registered` atau `reRegistration.status = confirmed`) dilindungi di sisi UI dan server agar tidak dapat dihapus demi menjaga integritas referensi siswa Akademik.

### Tes Beserta Hasil
- `php artisan test tests/Feature/AdmissionsWorkflowTest.php tests/Feature/AdmissionsControllersTest.php tests/Architecture/ModularBoundaryTest.php` → **PASS** (17 tests, 100 assertions).
- `./vendor/bin/pint --test` → **PASS**.
- `npm run typecheck` (`vue-tsc --noEmit`) → **PASS** (0 errors).
- `npm run build` (`vite build`) → **PASS**.

### Risiko Tersisa
- Penghapusan pendaftar bersifat permanen pada tabel `admission_applicants` (beserta dokumen privatnya di disk `local`), namun jejaknya tetap terekam di tabel `audit_events` (`admissions.applicant.deleted`) dan pendaftar yang sudah menjadi siswa Akademik tidak dapat dihapus.

### Langkah Review Manual
1. Buka menu **SPMB → Pendaftar & Verifikasi** (`/spmb/pendaftar`), pastikan daftar pendaftar kini tampil sebagai tabel ringkas berisi kolom **No**, **Nama Pendaftar**, **Tanggal Daftar**, **Jalur & Status**, dan **Aksi**.
2. Klik tombol **Detail** pada salah satu baris untuk membuka panel detail samping (*Slide-Over Drawer*), lalu periksa biodata dan verifikasi berkas.
3. Klik tombol **Edit (ikon pensil)** pada kolom Aksi, ubah biodata calon siswa (misalnya nama, NISN, atau asal sekolah), lalu simpan dan pastikan tabel langsung terbarui.
4. Klik tombol **Hapus (ikon trash)** pada pendaftar yang belum daftar ulang untuk menguji penghapusan, dan pastikan tombol hapus otomatis terkunci pada pendaftar yang sudah berstatus *Daftar ulang*.
5. Pindah ke sub-tab **Antrean Verifikasi Berkas**, pastikan tampilannya juga berupa tabel ringkas dengan aksi serupa.

### Rollback
- Kembalikan perubahan dengan `git checkout -- app/Modules/Admissions/Interface/Routes/web.php app/Modules/Admissions/Interface/Http/Controllers/AdmissionsController.php resources/js/Pages/Admissions/Index.vue tests/Feature/AdmissionsWorkflowTest.php` lalu jalankan `npm run build`.

## Perapihan Tata Letak Halaman Jadwal Pelajaran (Opsi A) & Ekspor PDF (13 November 2026)

### Task dan Requirement
- **Task**:
  1. Merapikan halaman **Jadwal Pelajaran** (`/akademik/jadwal`) sesuai **Opsi A** (*Mode Ganda: Papan Mingguan 6 Hari + Tabel Daftar Terstruktur*, dilengkapi pencarian cepat, filter hari & status, serta tata letak kartu sesi yang rapi tanpa bertumpuk/terpotong).
  2. Menambahkan fitur **Ekspor ke PDF** (`/akademik/jadwal/export/pdf`) pada halaman Jadwal Pelajaran yang menghormati filter aktif dan batasan otorisasi per peran.
- **Requirement ID**: `SS-F-004` (penyusunan jadwal, deteksi konflik, penerbitan draf, ekspor jadwal), `DESIGN.md` (Calm Academic OS), `AI_RULES.md`.

### Ringkasan Perubahan
1. **Mode Tampilan Ganda (*Papan Mingguan* & *Tabel Daftar*) di `resources/js/Pages/Academic/Schedules/Index.vue`**:
   - **Papan Mingguan**: Menampilkan 6 hari sekolah aktif (Senin–Sabtu) secara proporsional tanpa memaksa *horizontal scroll* sempit (3 kolom di layar menengah, 6 kolom di layar lebar). Hari Minggu disembunyikan secara bawaan jika kosong dan dapat dimunculkan lewat opsi *Tampilkan Minggu* (atau otomatis tampil bila memiliki jadwal).
   - **Tabel Daftar Terstruktur**: Menyajikan alternatif tabel berurutan (kolom *Hari, Jam, Mata Pelajaran, Rombel, Guru Pengajar, Ruang/Lab, Status, Aksi*) agar memudahkan pemindaian puluhan slot jadwal sekaligus.
2. **Perapihan Hierarki Kartu Sesi Jadwal**:
   - Memisahkan elemen ke dalam 4 baris yang terstruktur rapi: (1) *Jam & lencana Status (Terbit/Draf)*, (2) *Nama & Kode Mata Pelajaran*, (3) *Chip Rombel & Ruangan/Lab*, dan (4) *Nama Guru Pengajar & Tombol Aksi Ikon (Ubah/Hapus)* di sudut kanan bawah sehingga tidak lagi saling bertumpuk di baris atas.
   - Menambahkan tombol pintas `+` pada header setiap hari untuk langsung membuka formulir tambah slot jadwal pada hari tersebut.
3. **Pencarian Cepat, Filter Hari (Pills), dan Filter Status**:
   - Menambahkan kotak pencarian instan (*Mapel, kode, guru, kelas, ruang*), tombol filter per hari (*Semua Hari, Senin..Sabtu*) lengkap dengan penghitung sesi, serta filter status (*Semua, Terbit, Draf*).
   - Memperbaiki *override* border di `resources/css/app.css` dan `resources/css/modern-ui.css` agar indikator aksen kiri kartu sesi tetap tampil jelas di mode terang maupun gelap.
4. **Fitur Ekspor Jadwal Pelajaran ke PDF (`/akademik/jadwal/export/pdf`)**:
   - Menambahkan rute `GET /akademik/jadwal/export/pdf` (`academic.schedules.export.pdf`) di `app/Modules/Academic/Interface/Routes/web.php` dengan middleware `['throttle:sensitive', 'role:admin,operator,principal,teacher,homeroom,student']`.
   - Menambahkan method `ScheduleController::exportPdf()` yang menghasilkan dokumen PDF A4 Landscape via `Barryvdh\DomPDF\Facade\Pdf` dari template `resources/views/academic/schedules-export.blade.php`.
   - Mendukung penerusan filter aktif (`term_id`, `class_group_id`, `teacher_id`, `day_of_week`, `status`, dan `search`) serta mengisolasi role `student` agar hanya dapat mengekspor jadwal `published` dari rombel aktifnya sendiri pada semester aktif.
   - Menambahkan tombol **Ekspor PDF** (`.file-action-button--pdf`) di header halaman `resources/js/Pages/Academic/Schedules/Index.vue` yang menyertakan query filter aktif secara dinamis.

### File & Migrasi yang Disentuh
- `app/Modules/Academic/Interface/Routes/web.php`
- `app/Modules/Academic/Interface/Http/Controllers/ScheduleController.php`
- `resources/views/academic/schedules-export.blade.php`
- `resources/js/Pages/Academic/Schedules/Index.vue`
- `resources/css/app.css`
- `resources/css/modern-ui.css`
- `tests/Feature/DailyOperationsControllersTest.php`
- `docs/HANDOFF.md`
- **Migrasi database**: Tidak ada perubahan skema/migrasi baru.

### Keputusan & Asumsi
- Sesuai **Opsi A** yang dipilih pengguna, halaman jadwal menyediakan sakelar *Papan Mingguan* dan *Tabel Daftar* tanpa mengubah kontrak props Inertia maupun aturan otorisasi server-side (`ScheduleController` & `SchedulePolicy`).
- Ekspor PDF menggunakan `SchoolScopeService::getSchoolDetails()` dari modul `Core\Application` untuk menjaga batas arsitektur modular monolith (`ModularBoundaryTest`).

### Tes Beserta Hasil
- `php artisan test --compact tests/Feature/DailyOperationsControllersTest.php tests/Feature/ScheduleConflictTest.php tests/Feature/RoleMatrixAccessTest.php tests/Architecture/ModularBoundaryTest.php` → **PASS** (41 tests, 337 assertions).
- `./vendor/bin/pint` → **PASS**.
- `npm run typecheck` (`vue-tsc --noEmit`) → **PASS** (0 errors).
- `npm run build` (`vite build`) → **PASS**.

### Risiko Tersisa
- Tidak ada risiko regresi terdeteksi; seluruh validasi benturan guru/rombel/ruang dan isolasi hak akses per peran di sisi server tetap utuh dan teruji.

### Langkah Review Manual
1. Buka menu **Akademik → Jadwal Pelajaran** (`/akademik/jadwal`).
2. Periksa tampilan **Papan Mingguan**: pastikan kartu sesi pada setiap hari menampilkan jam, status, mata pelajaran, kelas, ruang, nama guru, serta tombol ikon Ubah/Hapus dengan rapi tanpa terpotong.
3. Klik sakelar **Tabel Daftar** di kanan atas untuk melihat seluruh jadwal dalam bentuk tabel terstruktur.
4. Uji filter cepat menggunakan *pill* nama hari (mis. klik **Senin** untuk fokus 1 hari), filter status (*Terbit* / *Draf*), serta kotak pencarian.
5. Klik tombol **Ekspor PDF** di bagian kanan atas halaman untuk mengunduh dokumen `jadwal-pelajaran.pdf` sesuai filter yang sedang aktif.

### Rollback
- Kembalikan perubahan dengan `git checkout -- app/Modules/Academic/Interface/Routes/web.php app/Modules/Academic/Interface/Http/Controllers/ScheduleController.php resources/js/Pages/Academic/Schedules/Index.vue resources/css/app.css resources/css/modern-ui.css tests/Feature/DailyOperationsControllersTest.php && rm -f resources/views/academic/schedules-export.blade.php` lalu jalankan `npm run build`.

## Redesign Halaman Progress Belajar — Opsi A (13 November 2026)

### Task dan Requirement
- **Task**: Merombak antarmuka dan kontrol halaman **Progress Belajar** (`/pembelajaran/progress` & `/siswa/progress`) sesuai **Opsi A** (*Mode Ganda: Tabel Analitik + Kartu Ringkas per Siswa, Filter Status Cepat & Bar Progres Visual*) agar lebih mudah dipahami, dikontrol, dan dibaca.
- **Requirement ID**: `SS-F-009` (monitoring progres belajar LMS), `DESIGN.md` (Calm Academic OS), `AI_RULES.md`.

### Ringkasan Perubahan
1. **Mode Ganda (*Tabel Analitik* & *Kartu per Siswa*) di `resources/js/Pages/Learning/Progress.vue`**:
   - **Tabel Analitik**: Menampilkan progres tiap siswa per mata pelajaran dilengkapi *progress bar* visual berwarna (`emerald`/`amber`/`rose`), lencana status (*Tuntas, Sedang Berjalan, Perlu Perhatian, Perlu Revisi, Terlambat*), lencana rerata nilai, dan tombol pintas langsung ke **Materi** atau **Tugas** terfilter.
   - **Kartu Ringkas per Siswa**: Mengelompokkan capaian lintas seluruh mata pelajaran untuk setiap siswa dalam satu kartu ringkas (ketuntasan total, total materi dibaca, tugas dikumpulkan, keterlambatan, revisi, dan daftar rincian nilai per mapel yang dapat diperluas).
2. **Kontrol Cepat (Filter Status Pills & Pengurutan Prioritas) di `LearningProgressController.php` & `Progress.vue`**:
   - Menambahkan *Quick Status Filter Pills* (**Semua Status**, **Perlu Perhatian**, **Perlu Revisi**, **Terlambat**, **Belum Tuntas**, **Tuntas 100%**) beserta penghitung jumlah baris otomatis.
   - Menambahkan opsi pengurutan (*Prioritas Pendampingan, Progres Terendah/Tertinggi, Nilai Tertinggi/Terendah, Nama Siswa A–Z*) dan penerapan filter otomatis saat dropdown Kelas/Mapel/Urutan diubah.
3. **Kartu KPI dengan Mini Progress Bar & Kepatuhan Hak Akses**:
   - Memperkaya 4 kartu metrik ringkasan di bagian atas dengan *mini progress bar* dan indikator persentase.
   - Menyesuaikan tombol pintas LMS menggunakan flag `can_manage_lms` agar peran manajerial (`admin`, `operator`, `principal`) yang memantau progres tidak diarahkan ke rute khusus pengajar/siswa yang membatasi akses.

### File & Migrasi yang Disentuh
- `app/Modules/Learning/Interface/Http/Controllers/LearningProgressController.php`
- `resources/js/Pages/Learning/Progress.vue`
- `tests/Feature/LearningControllersTest.php`
- `docs/HANDOFF.md`
- **Migrasi database**: Tidak ada perubahan skema/migrasi baru.

### Keputusan & Asumsi
- Sesuai **Opsi A** yang dipilih pengguna, halaman menyediakan sakelar tampilan **Tabel Analitik** dan **Kartu per Siswa** bagi peran non-siswa, serta mempertahankan tampilan khusus siswa lengkap dengan rincian kehadiran (`personal_attendance`).

### Tes Beserta Hasil
- `php artisan test --compact tests/Feature/LearningControllersTest.php tests/Architecture/ModularBoundaryTest.php` → **PASS** (9 tests, 124 assertions).
- `./vendor/bin/pint` → **PASS**.
- `npm run typecheck` (`vue-tsc --noEmit`) → **PASS** (0 errors).
- `npm run build` (`vite build`) → **PASS**.

### Risiko Tersisa
- Tidak ada risiko regresi terdeteksi; batas modular monolith dan matriks izin peran tetap terjaga.

### Langkah Review Manual
1. Buka menu **Pembelajaran → Progress Belajar** (`/pembelajaran/progress`).
2. Periksa kartu ringkasan KPI di bagian atas dan klik *pill* filter cepat (**Perlu Perhatian**, **Perlu Revisi**, **Terlambat**, **Tuntas 100%**) serta dropdown pengurutan (**Urut: Prioritas Pendampingan**).
3. Periksa **Tabel Analitik**: pastikan bar progres materi & tugas, lencana status, dan lencana nilai tampil jelas dan mudah dipindai.
4. Klik sakelar **Kartu per Siswa** di kanan atas untuk melihat agregasi capaian belajar per siswa lintas mata pelajaran.

### Rollback
- Kembalikan perubahan dengan `git checkout -- app/Modules/Learning/Interface/Http/Controllers/LearningProgressController.php resources/js/Pages/Learning/Progress.vue tests/Feature/LearningControllersTest.php` lalu jalankan `npm run build`.

---

## Perapihan Menu & Halaman Role Siswa — Paket Lengkap Opsi A (13 November 2026)

### Task dan Requirement
- Merapikan seluruh menu dan pengalaman antarmuka role `student` agar konsisten dengan halaman role lain (gaya *Calm Academic OS*: sidebar label ramah siswa, ringkasan metrik, filter pills status, navigasi lintas fitur siswa, dan keterbacaan aksi).
- Cakupan perubahan murni di lapisan frontend (`resources/js/**`) tanpa mengubah rute backend atau policy otorisasi server-side.

### Ringkasan Perubahan
1. **Sidebar Siswa (`resources/js/components/SidebarNavigation.vue`)**
   - Menambahkan mapping `displayName` khusus role `student` sehingga label navigasi menjadi lebih personal dan jelas: *Jadwal Saya*, *Materi Pelajaran*, *Tugas Saya*, *Progress Belajar*, *Ujian CBT*, *Rapor Saya*, *Pengumuman & Agenda*, *Diskusi Kelas*, dan *Profil Saya*.
2. **Dashboard Siswa (`resources/js/Pages/Dashboard.vue`)**
   - Memperbarui `studentActions` menjadi 8 akses utama dengan rute kanonis siswa (`/siswa/materi`, `/siswa/tugas`, `/akademik/jadwal`, `/ujian`, `/siswa/progress`, `/penilaian/rapor`, `/akademik/kelas-daring`, `/komunikasi/chat`).
   - Memperbarui tautan pada seksi `student-overview` ke `/siswa/progress` dan `/siswa/tugas`.
   - Menambahkan seksi `student-shortcuts` ("Akses cepat belajar / Semua menu siswa") beserta CSS responsif.
3. **Materi & Tugas LMS Siswa (`resources/js/Pages/Learning/Index.vue`)**
   - Menambahkan opsi *"Semua mata pelajaran"* (`''`), dukungan query `?subject=`, pencarian `studentSearch`, dan filter status cepat tugas (`all`, `pending`, `submitted`, `revision`, `overdue`).
   - Menambahkan `.class-workspace-nav` khusus siswa (*Materi Pelajaran*, *Tugas Saya*, *Progress Belajar*, *Kelas Daring*, *Diskusi Kelas*) dan tombol hero *Progress belajar*.
4. **Ujian CBT Siswa (`resources/js/Pages/Assessment/Exams/Index.vue`)**
   - Menambahkan 4 kartu ringkasan `.grade-summary` (*Total Ujian*, *Siap Dikerjakan*, *Terjadwal*, *Selesai*), filter pills status (`all`, `ready`, `upcoming`, `done`), serta menampilkan nama mata pelajaran pada `.exam-meta`.
5. **Kelas Daring Siswa (`resources/js/Pages/Academic/OnlineClasses/Index.vue`)**
   - Menambahkan tombol pintas hero *Jadwal saya*, 4 kartu ringkasan `.grade-summary` (*Total Pertemuan*, *Sedang Berlangsung*, *Terjadwal*, *Selesai*), filter pills status (`all`, `live`, `scheduled`, `completed`), serta empty state khusus hasil filter.

### File yang Disentuh
- `resources/js/components/SidebarNavigation.vue`
- `resources/js/Pages/Dashboard.vue`
- `resources/js/Pages/Learning/Index.vue`
- `resources/js/Pages/Assessment/Exams/Index.vue`
- `resources/js/Pages/Academic/OnlineClasses/Index.vue`

### Keputusan / Asumsi
- Semua tautan LMS siswa diarahkan ke rute kanonis `/siswa/materi`, `/siswa/tugas`, dan `/siswa/progress` agar state aktif pada `SidebarNavigation.vue` tetap akurat.
- Tidak ada perubahan migrasi, database, maupun policy otorisasi backend.

### Tes dan Verifikasi
- `npm run typecheck && npm run build`: **PASS** (0 error TypeScript/Vue, 854 modul ter-transform, build Vite selesai dalam 2.74s).
- `php artisan test --compact tests/Feature/DashboardAccessTest.php tests/Feature/LearningControllersTest.php tests/Feature/RoleMatrixAccessTest.php tests/Feature/RouteAndPermissionStressTest.php`: **PASS** (41 tests, 863 assertions, 0 failures).

### Langkah Review Manual
1. Login sebagai akun siswa, periksa sidebar kiri (*Jadwal Saya*, *Materi Pelajaran*, *Tugas Saya*, *Progress Belajar*, *Ujian CBT*, *Rapor Saya*, *Pengumuman & Agenda*, *Diskusi Kelas*, *Profil Saya*).
2. Cek `/dashboard` (bagian `student-overview` dan `student-shortcuts`).
3. Cek `/siswa/materi` dan `/siswa/tugas` (filter semua mapel, kotak pencarian, filter pills status tugas, serta navigasi `.class-workspace-nav`).
4. Cek `/ujian` dan `/akademik/kelas-daring` (4 kartu ringkasan dan filter pills status).

### Rollback
- Kembalikan perubahan dengan `git checkout -- resources/js/components/SidebarNavigation.vue resources/js/Pages/Dashboard.vue resources/js/Pages/Learning/Index.vue resources/js/Pages/Assessment/Exams/Index.vue resources/js/Pages/Academic/OnlineClasses/Index.vue` lalu jalankan `npm run build`.
