# Runbook Backup & Disaster Recovery — School Suite

## Prasyarat dan kunci

Gunakan PHP minimal 8.3 sesuai Composer, ekstensi pdo/zip dengan AES-256, serta client `mysqldump`/`mysql` untuk driver MySQL (atau `mariadb-dump`/`mariadb` untuk MariaDB). Client harus kompatibel dengan server. Akun database membutuhkan izin dump schema/data, routines, events, triggers, serta izin membangun ulang objek ketika restore. Backup konsisten MySQL menggunakan `--single-transaction`: gunakan InnoDB dan hindari perubahan DDL selama backup. Konsistensi database dengan berkas privat memerlukan penghentian perubahan data/file selama pengambilan backup penuh.

Isi `BACKUP_PASSWORD` dengan secret acak minimal 32 karakter pada environment operasi, kemudian refresh config cache. Contoh pembuatan secret di terminal lokal aman: `openssl rand -base64 48`. Jangan commit hasilnya. Password ini terpisah dari APP_KEY; simpan salinan di secret manager/offsite, termasuk password lama selama arsip lama masih disimpan. Kehilangan password membuat arsip tidak dapat dipulihkan. Jangan mengganti secret produksi secara otomatis.

Arsip versi 2 memakai AES-256 untuk seluruh entry dan SHA-256 setiap komponen dalam manifest terenkripsi. Direktori sementara dibuat privat dan dibersihkan; arsip berizin 0600. Restore menolak password salah, entry tidak terenkripsi, checksum tidak cocok, komponen hilang, serta path tidak aman sebelum mengubah target.

Arsip ZIP lama tanpa enkripsi/manifest versi 2 ditolak oleh command baru. Pertahankan arsip lama dan lakukan konversi/restore dengan tooling lama dalam lingkungan terisolasi yang dikendalikan; jangan menimpa arsip lama atau menganggapnya sudah dilindungi oleh perubahan ini.

## Backup

```bash
php artisan school:backup
php artisan school:backup --database-only
php artisan school:backup --destination=/lokasi/backup-privat
```

Output: `storage/app/backups/backup_school_suite_<waktu>_<acak>.zip`. Dump MySQL mencakup DDL dan data tanpa mengunci target nama database asal; kredensial hanya berada di options file sementara berizin 0600. SQLite file menggunakan snapshot `VACUUM INTO` agar data WAL ikut tercakup. SQLite in-memory memakai SQL dengan escaping PDO.

Scheduler Laravel sudah menjadwalkan backup harian pukul 02:00. Jalankan satu cron `schedule:run` pada rilis aktif, bukan cron backup tambahan yang menggandakan jadwal:

```cron
* * * * * cd /var/www/school_suite/current && php artisan schedule:run >> /var/log/school_suite_scheduler.log 2>&1
```

Kebijakan yang perlu disahkan operator: harian 30 hari, mingguan 12 minggu, bulanan 1 tahun, serta salinan offsite. Command tidak menghapus arsip atau menyalin ke offsite secara otomatis. Pantau exit code dan kegagalan scheduler.

## Drill restore terisolasi

Jangan mengonversi dump MySQL menjadi SQLite untuk membuktikan recovery produksi. Gunakan engine yang sama dengan sumber. Lakukan drill pada instance terpisah; semua restore dalam APP_ENV=production memerlukan maintenance, termasuk ketika opsi target diberikan, karena nama koneksi/path dapat menunjuk target aktif.

Untuk SQLite, `--target-db` adalah path file baru:

```bash
php artisan school:restore /lokasi/backup.zip \
  --target-db=/tmp/school_drill.sqlite \
  --target-files=/tmp/school_drill_files --force
```

Untuk MySQL, siapkan database target terpisah dan konfigurasi koneksi Laravel bernama `mysql_drill` yang menunjuk database tersebut. Jangan membuat koneksi drill menunjuk database produksi. `--target-db` adalah **nama koneksi**, bukan nama database atau path:

```bash
php artisan school:restore /lokasi/backup.zip \
  --target-db=mysql_drill \
  --target-files=/tmp/school_drill_files --force
```

Restore MySQL mewajibkan target kosong: database dengan tabel/view, routines, atau events ditolak sebelum import agar objek dari versi schema lain tidak tertinggal. Gunakan database dan direktori file kosong untuk pemulihan bersih; file ekstra di target tidak dibersihkan otomatis. Verifikasi jumlah tabel/baris, foreign keys, contoh nilai dan rapor, checksum data, serta unduhan berkas privat. Catat versi client/server, arsip, hasil, dan penanggung jawab.

Regression drill MySQL tersedia melalui `tests/Feature/MysqlBackupRestoreTest.php`. `BACKUP_MYSQL_TEST_SOCKET` hanya boleh menunjuk server MySQL uji terisolasi dengan akun root tanpa password, tanpa port jaringan. Tes membuat/menghapus database berawalan acak `backup_source_` dan `backup_target_`, menjalankan migrasi dan seed pilot, lalu membandingkan jumlah baris serta checksum tabel penting. Tanpa variabel ini tes dilewati; socket yang ditentukan tetapi tidak dapat diakses menyebabkan kegagalan, bukan kelulusan semu.

## Pemulihan operasional

1. Siapkan instance dengan kode/engine database yang cocok. Simpan APP_KEY dan BACKUP_PASSWORD yang sesuai arsip dari secret store.
2. Ambil backup keadaan target sebelum pemulihan jika memungkinkan. Aktifkan `php artisan down`, hentikan worker dan scheduler, serta pastikan tidak ada proses menulis database/file. Mode maintenance web saja tidak menghentikan worker.
3. Siapkan database bersih dan direktori berkas bersih; koneksi aktif harus diarahkan ke database baru/kosong sebelum restore MySQL. Restore berisi schema sehingga tidak perlu menjalankan migrasi sebelum import dump.
4. Jalankan `php artisan school:restore /lokasi/backup.zip --force`. Target default adalah koneksi dan direktori privat aktif. SQLite biner dikenali pada jalur default juga, lalu diganti melalui file staging.
5. Pastikan file hasil pemulihan dapat dibaca service user. Jalankan health check HTTPS, verifikasi data/berkas dan login beberapa peran, kemudian nyalakan kembali layanan dan `php artisan up`.

Restore MySQL menggunakan DDL sehingga tidak dapat dibungkus satu transaksi rollback. Jika proses gagal setelah import dimulai, target dapat berubah sebagian: jangan buka trafik; pulihkan backup pra-restore atau ulangi pada instance bersih. Tidak ada fallback database otomatis. Perubahan ini tidak menjalankan pemulihan pada database operasional.
