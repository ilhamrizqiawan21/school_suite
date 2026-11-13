# School Suite

School Suite adalah aplikasi web terpadu untuk manajemen akademik, pembelajaran, penilaian, dan tata kelola sekolah. Target pertama adalah satu sekolah per instalasi. Produk harus tetap berguna tanpa Teacher Desk; integrasi desktop hadir setelah kedua produk stabil secara mandiri.

Implementasinya menggunakan modular monolith untuk menjaga batas domain dan memudahkan deployment satu sekolah.

## Dokumen utama

| Pertanyaan | Sumber kebenaran |
|---|---|
| Mengapa produk dibuat dan untuk siapa? | [PRD](docs/PRD.md) |
| Apa kebutuhan yang harus dipenuhi? | [Requirements](docs/REQUIREMENTS.md) |
| Bagaimana pengalaman pengguna dirancang? | [Design](docs/DESIGN.md) |
| Seperti apa arah visual dan interaksi yang diusulkan? | [Mockup interaktif](docs/mockup/README.md) |
| Bagaimana sistem dibangun? | [Architecture](docs/ARCHITECTURE.md) |
| Bagaimana data saling berhubungan? | [ERD](docs/ERD.md) |
| Keputusan apa yang sudah dikunci? | [Decisions](docs/DECISIONS.md) dan [ADR](docs/adr/README.md) |
| Apa yang dikerjakan berikutnya? | [Roadmap](docs/ROADMAP.md), [Status](docs/PROJECT_STATUS.md), dan [TODO](docs/TODO.md) |
| Bagaimana progres adaptasi fitur repo referensi LMS dipantau? | [Task Adaptasi LMS Sekolah](docs/TASK_LMS_SEKOLAH.md) |
| Bagaimana progres adaptasi bank soal dan paket soal dipantau? | [Task Adaptasi Bank Soal Cerdas](docs/TASK_BANK_SOAL_CERDAS.md) |
| Bagaimana AI bekerja di repo ini? | [AI Rules](docs/AI_RULES.md), [Gemini](docs/GEMINI.md), dan [Codex Review](docs/CODEX_REVIEW.md) |
| Bagaimana implementasi diserahkan untuk review? | [Handoff](docs/HANDOFF.md) |
| Apa hasil pemeriksaan keamanan dan kesiapan sistem? | [Security Handoff 2026-11-02](docs/SECURITY_HANDOFF_2026-11-02.md) |
| Bukti pengujian apa yang wajib? | [Tests](docs/TESTS.md) dan [Regression](docs/REGRESSION.md) |
| Bagaimana satu instalasi sekolah dipasang dan diinisialisasi? | [Installation](docs/INSTALLATION.md) dan [Deployment Checklist](docs/DEPLOYMENT_CHECKLIST.md) |

## Batas produk

- Termasuk: master akademik, jadwal, absensi, jurnal, wali kelas, penilaian/rapor, LMS, CBT, SPMB, perpustakaan, administrasi, sarpras, dan pelaporan bertahap.
- Ditunda: keuangan institusi sampai rancangan ledger disetujui.
- Tidak termasuk: tabungan siswa dan catatan pribadi guru yang tidak dipilih untuk dibagikan.
- Repo lama adalah sumber kebutuhan dan bukti perilaku, bukan sumber kode yang otomatis boleh disalin.

## Teknologi

| Area | Teknologi |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | Vue 3, TypeScript, Inertia.js |
| Styling/build | Tailwind CSS 4, Vite 8 |
| Database produksi | MySQL/MariaDB |
| Queue dan cache default | Database |
| Testing | PHPUnit, Laravel test suite, Playwright |
| Web server produksi | Nginx + PHP-FPM |

## Fitur utama

- Identitas sekolah, pengguna, role, permission, audit, dan pengaturan branding.
- Data siswa, guru, kelas, mata pelajaran, periode, jadwal, absensi, dan wali kelas.
- Materi, tugas, pengumpulan, penilaian, rapor, ujian, dan CBT.
- SPMB: periode, jalur, kuota, pendaftar, verifikasi berkas, seleksi, pengumuman, dan daftar ulang.
- Surat keluar, arsip, tanda tangan elektronik/provider BSrE, sarpras, dan pengumuman.
- Situs publik sekolah dengan warna identitas yang dapat dikonfigurasi.
- Backup terenkripsi AES-256, health check, queue worker, dan scheduler.

## Prasyarat

- PHP 8.3 atau lebih baru dengan ekstensi `gd` dan seluruh dependency yang dilaporkan oleh `composer check-platform-reqs`.
- Composer 2.
- MySQL/MariaDB untuk lingkungan yang menyerupai produksi.
- Node.js dan npm untuk memasang serta membangun aset frontend.
- Git.

## Instalasi lokal

```bash
git clone <URL_REPOSITORY> school_suite
cd school_suite

composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` dan isi koneksi database lokal. Contoh minimal:

```dotenv
APP_NAME="School Suite"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_suite
DB_USERNAME=school_suite_user
DB_PASSWORD=secret_db_password

SESSION_SECURE_COOKIE=false
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Buat database dan user sesuai konfigurasi tersebut, lalu jalankan:

```bash
php artisan migrate
npm ci
npm run build
```

Inisialisasi sekolah dan Admin pertama sekali saja:

```bash
SCHOOL_INSTALL_ADMIN_PASSWORD='ganti-dengan-password-kuat' \
php artisan school:install \
  --name="Nama Sekolah" \
  --npsn="12345678" \
  --level="SMA" \
  --admin-name="Administrator Sekolah" \
  --admin-email="admin@sekolah.sch.id" \
  --admin-username="admin.sekolah" \
  --no-interaction
```

Jangan menyimpan password tersebut di Git, `.env.example`, atau history shell. Untuk instalasi interaktif, hilangkan `SCHOOL_INSTALL_ADMIN_PASSWORD` dan `--no-interaction` agar password diminta secara tersembunyi.

Jalankan aplikasi pada terminal terpisah:

```bash
php artisan serve
npm run dev
php artisan queue:work --tries=3
php artisan schedule:work
```

Buka `http://127.0.0.1:8000/login`. Health check tersedia di `/up` dan `/health`.

## Perintah pengembangan dan verifikasi

```bash
# Backend
php artisan test
vendor/bin/pint --test

# Frontend
npm run typecheck
npm run build
npm run test:e2e

# Pemeriksaan dependency dan environment
composer check-platform-reqs
composer audit
npm audit
```

`npm run test:e2e` memerlukan server aplikasi dan konfigurasi Playwright yang sesuai. Untuk perubahan frontend, minimal jalankan `npm run typecheck` dan `npm run build`; untuk perubahan backend jalankan test yang relevan.

## Konfigurasi penting

- `APP_URL` menentukan URL aplikasi dan base path Vite jika aplikasi dipasang di subpath.
- `APP_TIMEZONE` menentukan timezone aplikasi; gunakan `Asia/Jakarta` untuk operasi sekolah di Indonesia.
- `APP_KEY` wajib tersedia; jangan mengganti key pada instalasi yang sudah memiliki data terenkripsi.
- `DB_CONNECTION`, `DB_*` mengatur database aplikasi.
- `SESSION_SECURE_COOKIE=true` wajib untuk production di balik HTTPS.
- `SESSION_ENCRYPT=true` wajib untuk production agar payload session terenkripsi di storage.
- `SESSION_ENCRYPT=true` wajib untuk production agar payload session terenkripsi di storage.
- `ENFORCE_PASSWORD_CHANGE=true` mempertahankan kewajiban mengganti password sementara.
- `BACKUP_PASSWORD` adalah secret terpisah minimal 32 karakter untuk backup terenkripsi.
- `BSRE_ENABLED`, `BSRE_ENDPOINT`, `BSRE_TOKEN`, dan `BSRE_TIMEOUT` mengatur integrasi tanda tangan elektronik BSrE.
- `FILESYSTEM_DISK=local` menjaga berkas privat tetap di storage aplikasi, bukan di web root.

Salin `.env.example` menjadi `.env`; file `.env` tidak boleh masuk repository.

## Arsitektur singkat

```text
Browser
  -> Nginx/PHP-FPM atau artisan serve
  -> Laravel + Inertia
  -> module application service / policy / controller
  -> MySQL + private storage + database queue
```

Modul utama berada di `app/Modules/`: `Core`, `Academic`, `Homeroom`, `Learning`, `Assessment`, `Admissions`, `Library`, dan `Administration`. Kode lintas modul bersama berada di `app/Shared/`. UI berada di `resources/js/`, stylesheet global di `resources/css/`, migrasi dan seeder di `database/`, dan pengujian di `tests/`.

## Production

Panduan lengkap tersedia di:

- [Instalasi dan konfigurasi production](docs/INSTALLATION.md)
- [Checklist deployment](docs/DEPLOYMENT_CHECKLIST.md)
- [Arsitektur](docs/ARCHITECTURE.md)
- [Dokumentasi proyek](docs/README.md)

Deployment production menggunakan `deploy/deploy.sh`, Nginx, PHP-FPM, queue worker, scheduler timer, HTTPS, dan health check `/up`. Jangan menjalankan `php artisan db:seed` pada instalasi production karena seeder menyiapkan data demo/pilot.

## Baseline security

- Set `APP_DEBUG=false` dan `APP_ENV=production` di production.
- Gunakan HTTPS dan `SESSION_SECURE_COOKIE=true`.
- Simpan `.env`, `APP_KEY`, token BSrE, dan `BACKUP_PASSWORD` di secret store atau permission file yang ketat.
- Pastikan `storage/` dan `bootstrap/cache/` hanya dapat ditulis oleh user runtime.
- Jalankan queue worker dan scheduler dengan user non-root yang terisolasi.
- Verifikasi `/up` setelah deploy dan uji restore backup secara berkala.
- Tinjau hasil `composer audit`, `npm audit`, PHPUnit, Pint, dan typecheck sebelum release.

## Pembagian peran

- **Pemilik produk:** pengguna; menetapkan prioritas dan menerima hasil.
- **Codex:** arsitek dan reviewer; menjaga batas domain, keputusan, risiko, dan bukti penerimaan. Codex tidak menjadi pelaksana utama fitur.
- **Gemini:** eksekutor implementasi; membuat kode, migrasi, tes, dan dokumentasi sesuai task yang sudah siap.

Alur kerja: keputusan/brief disiapkan → Gemini mengimplementasikan satu task → Gemini mengisi `HANDOFF.md` → Codex meninjau diff dan bukti → Gemini memperbaiki temuan → pemilik produk menerima.

## Batasan penting

`school:install` hanya boleh berhasil sekali pada satu database instalasi. Jalankan backup sebelum migrasi production atau perubahan operasional besar. Prosedur restore ada pada [Deployment Checklist](docs/DEPLOYMENT_CHECKLIST.md) dan command `php artisan school:restore`.
