# Audit backend — 27 Oktober 2026

## Kesimpulan dan cakupan

Rekomendasi: tahan penggunaan nilai/rapor resmi dan rilis produksi sampai temuan P1 diselesaikan. Skor triase 55/100 (berisiko), berdasarkan kontrol akses objek, perhitungan rapor, dan pemulihan data yang belum memadai. Skor bukan sertifikasi keamanan.

Audit membaca working tree `main`, termasuk perubahan tracked/untracked yang belum di-commit. Tidak mengubah kode aplikasi. Pemeriksaan meliputi Core/auth, Academic, Assessment/CBT, Learning, Admissions, Administration, penyimpanan privat, backup/restore, deployment, serta tes. Ini peninjauan berbasis kode lokal; tidak mencakup pentest server live, pengukuran beban, maupun advisory dependency terbaru. Target produk satu sekolah per instalasi; temuan lintas kelas tetap berdampak pada target tersebut, sedangkan dampak lintas sekolah berlaku bila ada beberapa record sekolah.

Checklist: petakan backend; periksa otorisasi objek; telusuri integritas nilai/rapor dan recovery; jalankan tes/lint yang ada; laporkan prioritas, bukti, tindakan, dan batas verifikasi. Perbaikan implementasi dan audit frontend di luar tahap ini.

## Temuan

### B01 — P1: guru dapat menulis nilai di luar penugasannya

- Bukti: `app/Modules/Assessment/Interface/Http/Controllers/ScoreInputController.php:84`, `app/Modules/Assessment/Application/Services/ScoreService.php:40` dan `:74`.
- POST `/penilaian/skor` hanya memeriksa role. `component_id` diperiksa secara global; `enrollment_id` hanya integer. Service tidak memvalidasi sekolah, kelas, semester, atau penugasan guru terhadap kedua objek.
- Guru dapat mengirim ID komponen/enrollment kelas lain. `updateOrCreate` menimpa nilai existing, bahkan mengganti `school_id` dengan sekolah aktor jika objek lintas sekolah digunakan.
- Perbaikan: otorisasi teaching assignment dan scope seluruh enrollment/komponen sebelum transaksi. Uji negatif guru lain, kelas lain, semester lain, dan sekolah lain. GET grid juga perlu scope kelas yang diajar.
- Validasi: penelusuran statis controller → policy → service.

### B02 — P1: perhitungan rapor mencampur nilai antar-mapel

- Bukti: `app/Modules/Assessment/Application/Services/ReportPublishingService.php:110`.
- Di dalam loop mata pelajaran, kode selalu memakai seluruh `$scheme->components` dan query skor yang sama, tanpa filter assignment/mapel. Identitas `$subj` hanya dipakai untuk label hasil.
- Dengan dua mata pelajaran bernilai berbeda, input kalkulasi setiap mapel tetap sama: nilai rapor dapat identik atau menjadi gabungan nilai mapel lain. Setelah terbit, snapshot dikunci.
- Perbaikan: ikat komponen/skor ke mapel dan penugasan yang tepat; tentukan semantik komponen generik. Tambahkan tes dua mapel dengan nilai berbeda, komponen kosong, dan bobot per mapel.
- Validasi: penelusuran statis; belum ada reproduksi end-to-end tambahan.

### B03 — P1: publikasi/generasi rapor tidak dibatasi kelas wali

- Bukti: `app/Modules/Assessment/Interface/Http/Controllers/ReportCardController.php:85` dan `:116`, `app/Modules/Assessment/Application/Policies/AssessmentPolicy.php:42`.
- `publishReports` hanya menerima aktor dan mengizinkan role homeroom; objek rapor tidak diperiksa. Endpoint publish menerima model berdasarkan ID, lalu menerbitkan dan menguncinya. Generasi batch menerima class/term melalui `exists` global.
- Wali kelas dapat menerbitkan rapor kelas lain; pada data beberapa sekolah, dapat memakai objek sekolah lain. Generasi juga dapat mengaitkan school_id aktor dengan enrollment yang tidak sesuai.
- Perbaikan: policy objek berdasarkan sekolah dan penugasan wali; validasi relasi kelas–tahun–semester–enrollment sebelum generate. Uji publish dan generate untuk wali kelas lain.
- Validasi: penelusuran statis.

### B04 — P1: ledger nilai terbuka untuk semua akun non-siswa

- Bukti: `app/Modules/Assessment/Interface/Http/Controllers/ReportCardController.php:125`, `app/Modules/Assessment/Application/Services/ReportGeneratorService.php:79`, route `app/Modules/Assessment/Interface/Routes/web.php`.
- GET `/penilaian/ledger?class_group_id=...&term_id=...` hanya menolak role student. Role guest yang sudah login, parent, atau staf lain tidak ditolak. Service menerima schoolId tetapi tidak menggunakannya untuk membatasi query.
- Request dengan `Accept: application/json` mengembalikan nama, NIS, nilai, status rapor, dan presensi kelas pilihan, termasuk draft bila tersedia.
- Perbaikan: gunakan allowlist role dan policy kelas/sekolah; scope query pada service. Uji akun guest terautentikasi, parent, guru kelas lain, dan sekolah lain.
- Validasi: penelusuran statis; bukan akses tanpa login.

### B05 — P1: backup MySQL tidak dapat diandalkan untuk restore

- Bukti: `app/Console/Commands/SchoolBackupCommand.php:144` dan `:185`; `SchoolRestoreCommand.php:147`.
- Dump hanya menyertakan DDL untuk SQLite. Jalur MySQL berisi INSERT tanpa schema dan memakai identifier dengan tanda kutip ganda, yang tidak sesuai mode MySQL default tanpa ANSI_QUOTES. Restore mengeksekusinya langsung, tanpa membersihkan tabel atau strategi penanganan data existing.
- Recovery ke database kosong tidak dapat membangun schema; recovery ke database existing juga tidak menjamin pemulihan dan dapat berhenti pada konflik. Tes drill yang ada hanya memakai SQLite in-memory dan target file baru.
- Perbaikan: gunakan mekanisme dump/restore native MySQL dengan snapshot konsisten; uji recovery terisolasi memakai engine produksi dan data berelasi sebelum menerima backup operasional.
- Validasi: statis; MySQL runtime belum diuji.

### B06 — P1: template deployment memakai PHP yang tidak memenuhi dependency

- Bukti: `composer.json` mensyaratkan PHP `^8.3`; `deploy/nginx/school-suite.conf:94` menunjuk socket `php8.2-fpm.sock`; `deploy/deploy.sh:100` dan `deploy/rollback.sh` me-reload php8.2-fpm.
- Server yang mengikuti template dapat melayani aplikasi memakai runtime yang ditolak Composer, atau menghasilkan 502 bila socket tersebut tidak tersedia. CLI yang lolos install tidak membuktikan runtime FPM sesuai.
- Perbaikan: selaraskan versi/socket/service dengan requirement lockfile, lalu smoke test PHP-FPM yang benar. Jangan menelan kegagalan restart/reload yang wajib.
- Validasi: ketidaksesuaian konfigurasi lokal; tidak mengeksekusi deployment.

### B07 — P2: endpoint update profil melewati aturan perubahan password

- Bukti: `app/Modules/Core/Application/Policies/UserPolicy.php:73`, `Interface/Http/Controllers/UserController.php:102`, `Application/Services/UserService.php:102` di modul Core.
- Semua role boleh mengubah akun sendiri lewat PUT `/pengaturan/pengguna/{id}`. Endpoint menerima password delapan karakter tanpa current_password/konfirmasi; service tidak membatalkan sesi lain. Ini melewati aturan endpoint `/ganti-password` yang lebih ketat. Service memasang must_change_password, tetapi password akun sudah berubah sebelum proses itu.
- Dampak: sesi yang diambil alih dapat mengganti password tanpa mengetahui password lama; kebijakan password tidak konsisten.
- Perbaikan: batasi self-update ke field profil dan arahkan perubahan password ke satu alur yang memeriksa password lama; pisahkan kemampuan reset administratif.
- Validasi: reproduksi PHPUnit terisolasi `/tmp/CoreAuditReproductionTest.php`, 1 tes/3 assertion lulus; akun student berhasil mengganti password tanpa password lama.

### B08 — P2: restore SQLite default membaca file biner sebagai SQL

- Bukti: `SchoolBackupCommand.php:121`, `SchoolRestoreCommand.php:120` dan `:147`.
- Backup SQLite berbasis file disalin sebagai biner, tetapi deteksi biner hanya dilakukan saat `--target-db` diisi. Restore default membaca file tersebut lalu memanggil `DB::unprepared`.
- Perbaikan: bedakan format dump berdasarkan manifest pada semua jalur restore; uji backup file SQLite → restore default pada database terisolasi, selain drill in-memory.
- Validasi: statis; restore destruktif tidak dijalankan.

### B09 — P2: backup data privat tidak dienkripsi dan checksum tidak diverifikasi

- Bukti: `SchoolBackupCommand.php:77`–`:94`, `SchoolRestoreCommand.php:70`–`:81`.
- ZIP dibuat dengan addFile tanpa enkripsi; stempel waktu dan SHA-256 bukan enkripsi. Restore hanya mencetak checksum manifest tanpa membandingkan isi dump.
- Dampak: salinan arsip membuka seluruh data/berkas privat bagi pemegang file; kerusakan dump tidak ditolak sebelum pemulihan. Klaim backup terenkripsi dalam arsitektur belum dipenuhi.
- Perbaikan: tetapkan enkripsi arsip dan pengelolaan kunci, permission/retensi yang sesuai, lalu verifikasi integritas seluruh komponen sebelum mutasi target.
- Validasi: statis; tidak mencetak data nyata.

### B10 — P2: probe deployment tidak memverifikasi endpoint HTTPS aplikasi

- Bukti: `deploy/deploy.sh:17`, `:84`–`:93`, serta blok HTTP Nginx.
- Probe memakai `http://127.0.0.1/up` tanpa Host domain aplikasi atau TLS. Template Nginx melakukan redirect HTTP ke HTTPS (301), sedangkan script hanya menerima 200/302. Pada host lain/default vhost, respons yang diterima pun belum membuktikan rilis aplikasi sehat. Saat rilis pertama gagal dan tidak ada previous release, script masih melanjutkan.
- Perbaikan: probe hostname/TLS dan payload sehat dari rilis yang benar; wajib gagal bila probe gagal, termasuk instalasi pertama, dengan timeout eksplisit.
- Validasi: konfigurasi statis; server tidak diakses.

### B11 — P2: jawaban matching berformat scalar dapat menggagalkan submit CBT

- Bukti: `app/Modules/Assessment/Interface/Http/Controllers/ExamController.php:284`–`:287`.
- Autosave menerima string jawaban. String JSON scalar valid seperti `1` berubah menjadi integer truthy saat decode; `ksort($given)` lalu menerima non-array dan melempar TypeError.
- Dampak: submit/penilaian attempt gagal sampai jawaban diperbaiki; jalur tenggat juga perlu diuji.
- Perbaikan: validasi struktur sesuai tipe soal pada autosave dan pertahankan validasi defensif saat grading; tes JSON scalar, invalid JSON, dan jawaban kosong.
- Validasi: penelusuran statis.

## Pemeriksaan aktual dan batas bukti

- `php artisan test --compact`: run pertama 242 tes, 241 lulus, 1 error import DB pada tes foto ledger. File tes berubah selama audit; run kedua lulus **242 tes, 2119 assertions**. Hasil kedua adalah status terakhir yang diamati.
- `vendor/bin/pint --test`: **gagal pada 8 file**, termasuk TeachingPlanDocumentService, JournalService, tiga controller Academic, DemoAssessmentContentSeeder, dan dua tes teaching plan. Tidak menjalankan auto-format.
- Reproduksi password terisolasi: **1 tes, 3 assertions lulus**. File berada di /tmp, tidak menambah tes ke repo.
- Suite menggunakan SQLite in-memory. Kelulusan tidak membuktikan jalur MySQL, deployment FPM/Nginx, restore operasional, beban bersamaan, atau seluruh kasus penolakan akses di atas.
- Tidak menjalankan deploy, migrate pada database operasional, restore destruktif, scanner eksternal, frontend build, atau browser QA. Tidak memeriksa isi secret .env.
- Status proyek menyatakan 140 tes dan kesiapan live, tetapi snapshot saat ini memiliki 242 tes, lint gagal, dan blocker di atas. Dokumentasi kesiapan perlu diselaraskan setelah verifikasi ulang.

## Urutan tindak lanjut

1. B01/B03/B04: tutup akses objek nilai, rapor, dan ledger; tambahkan regression test penolakan akses.
2. B02: betulkan kalkulasi multi-mapel sebelum menerbitkan rapor resmi; tinjau ulang snapshot yang sudah dibuat bila fitur pernah dipakai.
3. B05/B06/B08/B10: buktikan deployment dan recovery pada lingkungan terisolasi yang sama dengan produksi.
4. B07/B09/B11: satukan perubahan password, lindungi arsip, dan validasi jawaban CBT.
5. Selesaikan lint, perluas tes untuk kasus temuan, kemudian perbarui klaim kesiapan. Audit frontend menjadi tahap berikutnya.

## Evaluasi keluaran audit

| Aspek | Skor | Bukti dan batas |
|---|---|---|
| Akurasi | 4/5 | Lokasi kode ditelusuri; hanya bypass password mendapat reproduksi tambahan, temuan lain diberi label statis. |
| Kelengkapan | 4/5 | Domain utama dan operasi diperiksa; belum ada pengujian MySQL, beban, server live, dan frontend. |
| Kejelasan | 4/5 | Setiap temuan memuat pemicu, dampak, dan tindakan; detail teknis masih memerlukan pembaca developer. |
| Dapat ditindaklanjuti | 4/5 | Urutan perbaikan dan regression test tersedia; patch belum termasuk scope audit. |
| Keringkasan | 4/5 | Ringkasan dapat dibaca terpisah; laporan rinci mempertahankan bukti 11 temuan. |

Rata-rata 4,0/5. Peningkatan terpenting: reproduksi negatif B01/B03/B04, tes multi-mapel B02, dan drill MySQL B05. Penilaian ini seharusnya sesuai harapan pengguna untuk audit backend awal, tetapi belum merupakan audit seluruh produk termasuk frontend.
