# Runbook Backup & Disaster Recovery — School Suite

Dokumen ini adalah Standard Operating Procedure (SOP) operasional untuk pencadangan mandiri (*backup*), pengujian pemulihan berkala (*drill restore*), dan penanganan bencana (*Disaster Recovery*) pada instalasi School Suite.

---

## 1. Kebijakan & Frekuensi Cadangan

| Tipe Cadangan | Frekuensi | Retensi | Lokasi Simpan |
|---|---|---|---|
| **Harian (Automated Daily)** | Setiap hari pukul 02:00 WIB | 30 hari | Lokal (`storage/app/backups/`) + Offsite Storage |
| **Mingguan (Weekly Archive)** | Setiap hari Minggu pukul 03:00 WIB | 12 minggu | Cold Storage Terenkripsi |
| **Bulanan (Monthly Snapshot)** | Akhir bulan kalender akademik | 1 tahun | Backup Terpisah Luar Server |
| **Ad-Hoc / Pra-Upgrade** | Setiap sebelum migrasi/update besar | 60 hari | Penyimpanan Administrator |

---

## 2. Perintah Pencadangan Otomatis (`school:backup`)

### 2.1 Menjalankan Backup Lengkap (Basis Data & Berkas Privat)
Perintah ini mencadangkan database dan seluruh arsip berkas privat (`storage/app/private`):
```bash
php artisan school:backup
```

Output yang dihasilkan:
- Berkas arsip ZIP stempel waktu di `storage/app/backups/backup_school_suite_YYYY-MM-DD_HHmmss.zip`.
- Manifest JSON metadata memuat: waktu backup, versi PHP/Framework, checksum SHA-256 database, dan daftar komponen.

### 2.2 Menjalankan Backup Basis Data Saja
```bash
php artisan school:backup --database-only
```

### 2.3 Konfigurasi Cron Otomatis (Linux Crontab)
Jadwalkan eksekusi otomatis melalui cron server:
```cron
# Eksekusi backup School Suite setiap hari pukul 02:00 WIB
0 2 * * * cd /var/www/school_suite && php artisan school:backup >> /var/log/school_suite_backup.log 2>&1
```

---

## 3. Prosedur Drill Restore (Pengujian Pemulihan Berkala)

Sesuai `AI_RULES.md` (Aturan 5), sistem cadangan **wajib** diuji secara berkala pada lingkungan atau instance database terpisah untuk memastikan tidak ada data yang hilang atau rusak saat pemulihan sungguhan diperlukan.

### Langkah-langkah Drill Restore pada Lingkungan Uji:
1. Siapkan database target terpisah (contoh: database sqlite sementara atau database `school_suite_drill`).
2. Jalankan perintah restore dengan flag `--target-db` dan direktori berkas target:
   ```bash
   php artisan school:restore backup_school_suite_2026-10-25_020000.zip \
       --target-db=/tmp/school_drill.sqlite \
       --target-files=/tmp/school_drill_files \
       --force
   ```
3. Verifikasi jumlah tabel, jumlah baris data siswa, rombel, nilai, dan kecocokan checksum SHA-256 berkas.
4. Catat hasil pengujian pada log kepatuhan operasional sekolah.

---

## 4. Prosedur Disaster Recovery (Pemulihan Saat Bencana Nyata)

Jika server utama mengalami kegagalan perangkat keras, kerusakan disk, atau malfungsi sistem operasi:

1. **Persiapan Server Baru / Instance Bersih:**
   - Pasang lingkungan PHP 8.2+, dependensi ekstensi (`pdo`, `zip`, `mbstring`, `fileinfo`), Nginx, dan database server.
   - Clone atau deploy source code aplikasi School Suite pada commit rilis stabil yang sesuai.
   - Salin file `.env` dengan konfigurasi `APP_KEY` yang sama dengan saat pencadangan dibuat.

2. **Jalankan Migrasi Awal:**
   ```bash
   php artisan migrate --force
   ```

3. **Eksekusi Pemulihan Cadangan Terakhir:**
   Salin arsip backup terakhir ke direktori `storage/app/backups/`, lalu jalankan:
   ```bash
   php artisan school:restore backup_school_suite_terakhir.zip --force
   ```

4. **Verifikasi Pasca-Pemulihan:**
   - Jalankan health check sistem:
     ```bash
     curl -i http://localhost/up
     ```
   - Pastikan respon berstatus `200 OK` dengan status `healthy`.
   - Lakukan uji masuk (login) perwakilan peran (Admin, Guru, Siswa).
   - Pastikan riwayat nilai dan snapshot rapor terbit tidak mengalami perubahan bit-for-bit.

---

## 5. Rencana Rollback

Jika proses pemulihan gagal di tengah jalan:
1. Hentikan web server (`systemctl stop nginx php-fpm`).
2. Kembalikan dump database pra-restore yang tersimpan di temporary fallback.
3. Hubungi penanggung jawab teknis sekolah dan buka tiket penanganan insiden darurat.
