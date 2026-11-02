# Instalasi School Suite untuk Sekolah

Dokumen ini menjelaskan instalasi satu School Suite untuk satu sekolah pada hosting milik sekolah.

Untuk quick start developer, lihat [README root](../README.md). Dokumen ini berfokus pada instalasi lokal yang lengkap dan production.

## Instalasi lokal/development

### 1. Siapkan source dan dependency

```bash
git clone <URL_REPOSITORY> school_suite
cd school_suite
composer install
cp .env.example .env
php artisan key:generate
```

Konfigurasikan `APP_URL` dan `DB_*` di `.env`. Database lokal sebaiknya MySQL/MariaDB agar perilakunya sama dengan production. Pastikan database dan user sudah dibuat sebelum migrasi.

```bash
php artisan migrate
npm ci
npm run build
```

### 2. Buat sekolah dan Admin pertama

```bash
SCHOOL_INSTALL_ADMIN_PASSWORD='gunakan-password-kuat-sementara' \
php artisan school:install \
  --name="Nama Sekolah" \
  --npsn="12345678" \
  --level="SMA" \
  --admin-name="Administrator Sekolah" \
  --admin-email="admin@sekolah.sch.id" \
  --admin-username="admin.sekolah" \
  --no-interaction
```

Untuk prompt password tersembunyi, hilangkan environment `SCHOOL_INSTALL_ADMIN_PASSWORD` dan `--no-interaction`. Jangan menyimpan password di file atau history shell.

### 3. Jalankan layanan development

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev

# Terminal 3 — diperlukan untuk job asynchronous
php artisan queue:work --tries=3

# Terminal 4 — diperlukan untuk scheduled task saat diuji lokal
php artisan schedule:work
```

Buka `/login`. Endpoint `/up` dan `/health` dapat digunakan untuk smoke check.

## Prasyarat

- PHP 8.3 atau lebih baru dengan ekstensi `gd`, serta dependency Composer lainnya.
- MySQL/MariaDB untuk database produksi.
- Composer tersedia saat proses instalasi atau build release.
- HTTPS aktif pada domain sekolah.
- Direktori `storage/` dan `bootstrap/cache/` dapat ditulis oleh user runtime PHP.
- Queue worker dan scheduler tersedia untuk operasi produksi.

Node.js hanya diperlukan saat build aset frontend. Node.js tidak wajib tersedia pada runtime aplikasi jika aset hasil build sudah disertakan dalam release.

Periksa kecocokan ekstensi PHP sebelum instalasi production:

```bash
composer check-platform-reqs
```

## Instalasi awal

Jalankan perintah dari root aplikasi setelah `.env` dikonfigurasi:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
npm ci
npm run build
php artisan school:install \
  --name="Nama Sekolah" \
  --npsn="12345678" \
  --level="SMA" \
  --admin-name="Administrator Sekolah" \
  --admin-email="admin@sekolah.sch.id" \
  --admin-username="admin.sekolah"
```

Perintah `school:install` akan:

1. memastikan instalasi belum memiliki sekolah;
2. membuat role sistem dan permission dasar;
3. membuat profil sekolah;
4. membuat akun Admin pertama;
5. menyimpan password Admin melalui prompt tersembunyi.

Jangan menjalankan `php artisan db:seed` pada instalasi produksi. Seeder tersebut menyiapkan data demo/pilot, bukan data awal sekolah nyata.

Jika deployment menggunakan [deploy/deploy.sh](../deploy/deploy.sh), instalasi dependency, build frontend, migrasi terisolasi, cache framework, restart worker, dan health check dilakukan oleh pipeline deployment tersebut. Siapkan `.env` shared terlebih dahulu dan ikuti [Deployment Checklist](DEPLOYMENT_CHECKLIST.md).

## Mode non-interaktif

Untuk otomasi deployment, password Admin dapat diberikan melalui environment sementara:

```bash
SCHOOL_INSTALL_ADMIN_PASSWORD='gunakan-nilai-rahasia-sementara' \
php artisan school:install \
  --name="Nama Sekolah" \
  --npsn="12345678" \
  --level="SMA" \
  --admin-name="Administrator Sekolah" \
  --admin-email="admin@sekolah.sch.id" \
  --admin-username="admin.sekolah" \
  --no-interaction
```

Gunakan secret manager atau mekanisme environment sementara pada pipeline deployment. Jangan menyimpan nilai tersebut di `.env.example`, Git, log, atau history shell.

## Setelah instalasi

- Login melalui `/login` menggunakan akun Admin pertama.
- Lengkapi identitas, kontak, sekilas tentang sekolah, sambutan kepala sekolah, logo, favicon, dan warna sekolah pada Pengaturan Sekolah.
- Kelola Berita Sekolah dari menu Pengaturan. Berita mendukung draf/terbit dan sampai lima foto per berita, masing-masing maksimal 2 MB.
- Halaman profil publik sekolah tersedia di `/` (alias `/profil-sekolah`), sedangkan berita yang sudah terbit tersedia di `/berita`.
- Buat akun Operator/TU, Kepala Sekolah, Guru, Wali Kelas, dan Siswa sesuai kebutuhan.
- Hubungkan akun guru yang merangkap TU dengan profil Guru aktif; jangan memberikan role Admin untuk kebutuhan administrasi harian.
- Pastikan `/up` mengembalikan status kesehatan aplikasi.
- Aktifkan queue worker, scheduler, backup, dan HTTPS sesuai [Deployment Checklist](DEPLOYMENT_CHECKLIST.md).

### Konfigurasi environment production minimum

`deploy/validate-production-env.php` menolak konfigurasi yang tidak aman. Production minimal harus memiliki:

- `APP_ENV=production` dan `APP_DEBUG=false`.
- `APP_URL` menggunakan `https://`.
- `APP_KEY` sudah terisi.
- `ENFORCE_PASSWORD_CHANGE` tidak boleh dinonaktifkan.
- `SESSION_SECURE_COOKIE=true`.
- `SESSION_ENCRYPT=true`.
- `BACKUP_PASSWORD` minimal 32 karakter dan disimpan terpisah dari source code.
- `LOG_LEVEL` menggunakan level Laravel yang valid.

Uji preflight environment secara eksplisit bila diperlukan:

```bash
php deploy/validate-production-env.php /var/www/school_suite/shared/.env
```

### Backup dan restore

Backup menyertakan database dan berkas privat dalam ZIP terenkripsi AES-256. Pastikan `BACKUP_PASSWORD` tersedia sebelum menjalankan:

```bash
php artisan school:backup
php artisan school:backup --database-only
```

Restore production harus dilakukan dalam maintenance mode dan setelah worker dihentikan. Command memverifikasi enkripsi, path arsip, manifest, checksum, dan symlink sebelum mengaktifkan hasil restore:

```bash
php artisan down
sudo systemctl stop school-suite-worker
php artisan school:restore backup_school_suite_YYYY-MM-DD_HHMMSS_xxxx.zip --force
sudo systemctl start school-suite-worker
php artisan up
```

Jalankan restore drill pada environment terisolasi sebelum mengandalkan backup sebagai strategi pemulihan.

## Batas instalasi

Setiap instalasi hanya memiliki satu sekolah. Menjalankan `school:install` kedua kali akan ditolak untuk mencegah pembuatan sekolah atau Admin yang tidak sengaja.
