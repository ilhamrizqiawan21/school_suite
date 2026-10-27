# Production Deployment Checklist — School Suite

Dokumen ini adalah lembar verifikasi checklist resmi sebelum, saat, dan sesudah peluncuran rilis School Suite ke server production.

---

## 1. Fase Pra-Deployment (Pre-Flight Verification)

Wajib dipenuhi sebelum menjalankan skrip rilis ke server produksi:

- [ ] **CI & Uji Kualitas Otomatis:**
  - Seluruh unit & feature tests lulus 100% (`php artisan test`).
  - Arsitektur modular monolith lulus tanpa pelanggaran batas (`ModularBoundaryTest`).
  - Linter kode bersih (`vendor/bin/pint --test`).
  - Static typecheck TypeScript lulus (`npm run typecheck`).
  - Kompilasi build frontend sukses (`npm run build`).
  - Integritas dokumentasi lulus audit (`audit-docs.py`).
- [ ] **Pencadangan Basis Data & Berkas Pra-Deploy:**
  - Jalankan pencadangan cadangan snapshot manual: `php artisan school:backup`.
  - Pastikan berkas ZIP tersimpan aman di `storage/app/backups/`.
- [ ] **Konfigurasi Environment Production (`.env`):**
  - Runtime CLI dan PHP-FPM memakai PHP 8.3.x; `composer.lock` dikunci dengan `config.platform.php=8.3.0`.
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_KEY` telah terisi kunci enkripsi 32-karakter.
  - `DB_CONNECTION=mysql` dengan database `school_suite` dan kredensial terisolasi.
  - `SESSION_SECURE_COOKIE=true` (untuk enkripsi cookie via HTTPS).
  - Hak akses file `.env` dikunci ketat (`chmod 600 .env` milik `www-data:www-data`).

---

## 2. Fase Eksekusi Deployment

Jalankan skrip otomasi deployment zero-downtime:

```bash
cd /var/www/school_suite
./deploy/deploy.sh main
```

Pantau tahapan rilis yang berjalan:
1. Ekstraksi rilis baru ke direktori berstempel waktu.
2. Penautan symlink `.env` dan direktori shared `storage/`.
3. Instalasi Composer produksi (`--no-dev --optimize-autoloader`).
4. Kompilasi aset frontend Vite.
5. Eksekusi migrasi skema database terisolasi (`php artisan migrate --force --isolated`).
6. Caching framework (`config:cache`, `route:cache`, `view:cache`).
7. Pengalihan symlink atomik `current` ke rilis baru.
8. Verifikasi otomatis probe `/up`.
9. Restart antrean worker dan reload PHP-FPM.

---

## 3. Fase Pasca-Deployment (Post-Deployment Verification)

Segera lakukan verifikasi operasional setelah rilis aktif:

- [ ] **Verifikasi Observability:**
  ```bash
  curl -i https://school.sch.id/up
  ```
  - Pastikan mengembalikan kode `HTTP/2 200 OK` dengan payload JSON `"status": "healthy"`.
- [ ] **Verifikasi Header Keamanan HTTP:**
  - Header `X-Frame-Options: SAMEORIGIN` aktif.
  - Header `X-Content-Type-Options: nosniff` aktif.
  - Header `Strict-Transport-Security` aktif.
- [ ] **Verifikasi Background Worker:**
  ```bash
  sudo systemctl status school-suite-worker
  ```
  - Status worker harus `active (running)`.
- [ ] **Uji Coba Masuk Pengguna (Smoke Testing):**
  - Masuk sebagai Administrator: verifikasi dashboard dan manajemen pengguna.
  - Masuk sebagai Guru: verifikasi lembar input nilai dan presensi.
  - Masuk sebagai Siswa: verifikasi pembacaan rapor pribadi.

---

## 4. Prosedur Darurat & Rollback

Jika ditemukan kegagalan kritis atau regresi yang tidak dapat diperbaiki dalam 5 menit:

1. **Jalankan Rollback Cepat:**
   ```bash
   ./deploy/rollback.sh
   ```
2. **Jika Terjadi Kerusakan Basis Data:**
   Ikuti panduan pemulihan cadangan mandiri di [`RUNBOOK_BACKUP_RESTORE.md`](RUNBOOK_BACKUP_RESTORE.md):
   ```bash
   php artisan school:restore backup_pra_deploy.zip --force
   ```
3. Beritahukan penanggung jawab teknis sekolah dan dokumentasikan insiden di log rilis.

