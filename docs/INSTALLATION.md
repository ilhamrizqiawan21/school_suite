# Instalasi School Suite untuk Sekolah

Dokumen ini menjelaskan instalasi satu School Suite untuk satu sekolah pada hosting milik sekolah.

## Prasyarat

- PHP 8.3 atau lebih baru yang memenuhi dependency Composer.
- MySQL/MariaDB untuk database produksi.
- Composer tersedia saat proses instalasi atau build release.
- HTTPS aktif pada domain sekolah.
- Direktori `storage/` dan `bootstrap/cache/` dapat ditulis oleh user runtime PHP.
- Queue worker dan scheduler tersedia untuk operasi produksi.

Node.js hanya diperlukan saat build aset frontend. Node.js tidak wajib tersedia pada runtime aplikasi jika aset hasil build sudah disertakan dalam release.

## Instalasi awal

Jalankan perintah dari root aplikasi setelah `.env` dikonfigurasi:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
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
- Halaman profil publik sekolah tersedia di `/profil-sekolah`, sedangkan berita yang sudah terbit tersedia di `/berita`.
- Buat akun Operator/TU, Kepala Sekolah, Guru, Wali Kelas, dan Siswa sesuai kebutuhan.
- Hubungkan akun guru yang merangkap TU dengan profil Guru aktif; jangan memberikan role Admin untuk kebutuhan administrasi harian.
- Pastikan `/up` mengembalikan status kesehatan aplikasi.
- Aktifkan queue worker, scheduler, backup, dan HTTPS sesuai [Deployment Checklist](DEPLOYMENT_CHECKLIST.md).

## Batas instalasi

Setiap instalasi hanya memiliki satu sekolah. Menjalankan `school:install` kedua kali akan ditolak untuk mencegah pembuatan sekolah atau Admin yang tidak sengaja.
