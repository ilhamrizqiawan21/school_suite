# Task Adaptasi `bank-soal-cerdas`

Dokumen ini mengatur adaptasi fitur dari [repo referensi `bank-soal-cerdas`](https://github.com/ilhamrizqiawan21/bank-soal-cerdas) ke `school_suite`.

Tanggal pemeriksaan referensi: **25 Oktober 2026**  
Ref yang diperiksa: `main` pada tree `f0b7eec440de859dad1f67b764b34b93ada22071`.

## Tujuan dan batas adaptasi

- Referensi digunakan untuk memahami fitur dan alur kerja bank soal, paket soal, ujian, kolaborasi, serta analisis.
- Implementasi mengikuti modular monolith, domain, otorisasi, audit, dan identitas sekolah `school_suite`.
- CBT yang sudah ada di `app/Modules/Assessment` menjadi fondasi; task baru memperluasnya dan tidak membuat alur ujian terpisah.
- UI mengikuti pola Inertia/Vue dan design system `school_suite`; Blade/React SPA dari repo sumber tidak dipindahkan.
- Repo sumber tidak mencantumkan lisensi. Adaptasi berfokus pada kebutuhan dan perilaku produk; jangan menyalin kode atau aset tanpa pemeriksaan hak penggunaannya.
- Tandai selesai setelah backend, migration, endpoint, policy, UI, dan bukti penerimaan task tersedia.

## Status saat pemeriksaan

| Area | Status | Kondisi di `school_suite` |
|---|---|---|
| Ujian dan attempt siswa | 🟡 Fondasi tersedia | `ExamController`, `exam_questions`, `exams`, `exam_attempts`, autosave, penilaian esai, publikasi hasil, serta export CSV sudah ada. |
| Bank soal mandiri | 🟡 Implementasi tersedia | Katalog Inertia/Vue menyediakan pencarian, filter, pagination, detail, CRUD versi, arsip, pemulihan, dan duplikasi; verifikasi integrasi database masih tertunda. |
| Tipe soal | 🟡 Implementasi tersedia | Pilihan ganda, esai, benar/salah, dan menjodohkan telah terhubung ke alur snapshot CBT; migrasi payload baru belum dijalankan. |
| Paket soal | 🟡 Implementasi tersedia | Paket sekolah-aware dengan item terurut, bobot, status, randomisasi, snapshot versi, dan pemakaian paket saat membuat draf ujian sudah tersedia; verifikasi migrasi pada database lokal masih tertunda. |
| Kategori, tag, taksonomi | 🟡 Implementasi tersedia | Kategori/tag sekolah-aware, master KKO terfilter sekolah/mapel, filter katalog, dan ringkasan komposisi tersedia; migration menunggu verifikasi database. |
| Impor dan ekspor bank soal | 🟡 Implementasi tersedia | Template XLSX, preview/validasi per baris, commit atomik all-or-nothing, ekspor terfilter, batas resource, dan perlindungan formula sudah tersedia; integrasi database lokal utama masih perlu diverifikasi. |
| Berbagi soal/paket | 🟡 Implementasi tersedia | Permintaan, keputusan, izin view/copy/edit, pencabutan, audit, dan riwayat tersedia; migrasi lokal utama masih menunggu. |
| Analisis bank soal/ujian | 🟡 Implementasi tersedia | Dashboard agregat bank soal dan hasil ujian tersedia dengan filter sekolah/penugasan, analisis butir pascapublikasi, dan XLSX tanpa identitas siswa atau kunci. |

## Legenda

| Status | Arti |
|---|---|
| ⬜ Belum | Belum diimplementasikan pada `school_suite`. |
| 🟡 Sebagian | Fondasi sudah ada, tetapi kriteria task belum lengkap. |
| ✅ Selesai | Kriteria penerimaan dan quality gate task sudah terbukti. |
| ⛔ Tidak diambil | Sengaja di luar cakupan; alasan dicatat. |

## Urutan kerja

```text
BSC-01 Model & metadata soal
       ↓
BSC-02 Bank soal dan tipe soal lengkap
       ↓
BSC-03 Kategori, tag, dan taksonomi ──┐
       ↓                              │
BSC-04 Paket soal ←───────────────────┘
       ↓
BSC-05 Integrasi paket dengan builder CBT
       ↓
BSC-06 Impor dan ekspor spreadsheet
       ↓
BSC-07 Kolaborasi dan berbagi
       ↓
BSC-08 Analisis bank soal dan ujian
       ↓
BSC-09 Quality gate, dokumentasi, dan handoff
```

Task dapat dikerjakan berurutan. BSC-03 dapat berjalan bersama BSC-02 setelah bentuk data soal disepakati, tetapi paket dan integrasi CBT bergantung pada model soal yang stabil.

## Task implementasi

### BSC-01 — Model domain bank soal dan metadata

- [x] Tinjau ERD, migrasi CBT, `ExamQuestion`, `ExamQuestionItem`, `ExamAnswer`, dan kebijakan Assessment saat ini.
- [x] Rancang model bank soal dengan identitas butir `exam_questions`, kepemilikan `school_id`/`owner_id`, serta visibilitas guru dan admin/operator melalui policy Assessment pada BSC-02.
- [x] Rancang versi immutable pada `exam_question_versions`; `exam_question_items.question_version_id` menunjuk konten dan metadata versi yang digunakan ujian.
- [x] Tetapkan metadata jenjang, kurikulum, mapel, indikator, level kognitif, dan teks KKO; relasi master KKO dan kategori/tag disiapkan pada BSC-03.
- [x] Tetapkan arsip lewat `is_active`, larangan hard delete untuk soal yang sudah dipakai ujian, duplikasi sebagai identitas baru, dan unique `(exam_question_id, version_number)`.
- [x] Tambahkan migration forward/backward compatible yang membuat versi pertama untuk soal lama dan menghubungkan item ujian lama ke versi tersebut.
- **Kriteria selesai:** rancangan dan migration tersedia; data CBT lama terpetakan ke versi 1; migration memiliki `down()`; model relasi dapat dipakai implementasi BSC-02/BSC-05.

**Bukti:** `2026_10_25_700000_add_question_bank_metadata_and_versions.php`, `ExamQuestionVersion`, relasi pada `ExamQuestion`/`ExamQuestionItem`/`ExamAnswer`, [ERD](ERD.md), keputusan `SS-D-010`, [ADR-0010](adr/0010-bank-soal-immutable-versions.md).

### BSC-02 — Bank soal mandiri dan tipe soal

- [x] Buat daftar, pencarian, filter, pagination, detail, buat, ubah, arsip, dan duplikasi soal.
- [x] Pertahankan dukungan pilihan ganda dan esai yang sudah ada.
- [x] Tambahkan tipe benar/salah dan menjodohkan dengan validasi kunci dan penilaian yang sesuai.
- [x] Simpan opsi, pasangan, rubrik, penjelasan, poin, dan indikator melalui relasi yang tervalidasi.
- [x] Buat halaman Inertia/Vue untuk bank soal yang responsif dan selaras dengan navigasi aplikasi.
- **Kriteria selesai:** CRUD berjalan untuk role yang berhak; siswa tidak dapat mengelola bank soal; query, model binding, dan berkas/media terisolasi per sekolah.

**Bukti implementasi:** `QuestionBankController`, route `assessment.questions.*`, halaman `resources/js/Pages/Assessment/QuestionBank/Index.vue`, migration `2026_10_25_700001_add_matching_question_payloads.php`, serta integrasi `ExamController` untuk snapshot versi dan penilaian empat tipe soal. Akses dibatasi pada admin/operator/guru/wali kelas dan school scope.

### BSC-03 — Kategori, tag, dan klasifikasi kognitif

- [x] Implementasikan kategori dan tag dengan cakupan sekolah serta relasi many-to-many ke soal.
- [x] Implementasikan klasifikasi jenjang dan kurikulum yang sesuai kebutuhan produk.
- [x] Implementasikan level kognitif C1–C6 atau pemetaan L1–L3; dokumentasikan mapping LOTS/MOTS/HOTS agar tidak ambigu.
- [x] Sediakan master KKO yang dapat dicari dan difilter menurut level kognitif serta mapel bila diperlukan.
- [x] Tambahkan filter metadata ke katalog bank soal dan statistik komposisi.
- **Kriteria selesai:** metadata tervalidasi di server, terlindungi school scope, dapat difilter, dan tidak menggandakan master mata pelajaran akademik.

**Bukti implementasi:** migration `2026_10_25_700002_add_question_taxonomy.php`; model `QuestionCategory`, `QuestionTag`, dan `QuestionKkoTerm`; relasi snapshot pada versi; filter kategori/tag/level dan ringkasan komposisi pada halaman Bank Soal. L1 ≈ C1–C2/LOTS, L2 ≈ C3–C4/MOTS, L3 ≈ C5–C6/HOTS sebagai panduan praktis, bukan persamaan akademik.

### BSC-04 — Pengelolaan paket soal

- [x] Buat paket dengan nama, deskripsi, jenjang/kurikulum, durasi opsional, status, dan pembuat.
- [x] Kelola item soal dengan urutan dan bobot nilai; dukung tambah, hapus, susun ulang, dan duplikasi paket.
- [x] Atur pengacakan soal dan opsi di tingkat paket bila sesuai model ujian.
- [x] Cegah pemilihan soal lintas sekolah atau soal yang tidak boleh dilihat/disalin pengguna.
- **Kriteria selesai:** paket dan item dikelola secara transaksional, agregat jumlah soal konsisten, dan policy berlaku untuk setiap operasi.

**Bukti implementasi:** migration `2026_10_25_700003_create_question_packs.php`, model `QuestionPack`/`QuestionPackItem`, controller dan route `assessment.question-packs.*`, halaman `resources/js/Pages/Assessment/QuestionPacks/Index.vue`. Setiap item menyimpan `question_version_id`, urutan, dan bobot; paket dapat diduplikasi, diarsipkan, dan dipulihkan sebagai draf. Integrasi builder ujian ditangani pada BSC-05.

### BSC-05 — Integrasi paket dengan CBT yang sudah ada

- [x] Builder ujian dapat memilih paket atau memilih soal langsung.
- [x] Salin/snapshot referensi versi konten, kunci, urutan, dan bobot soal ketika draf ujian dibuat.
- [x] Pertahankan proses attempt, autosave, deadline, anti-cheat, koreksi, sinkronisasi nilai, dan export pada model Assessment yang sudah ada.
- [x] Definisikan perilaku ketika paket atau soal sumber diubah/diarsip setelah ujian dibuat.
- **Kriteria selesai:** ujian dari paket dapat ditempuh end-to-end dan perubahan bank/paket tidak mengubah ujian yang sudah dipublikasikan.

### BSC-06 — Impor dan ekspor spreadsheet

- [x] Sediakan template XLSX dengan sheet data kosong dan sheet contoh untuk setiap tipe soal.
- [x] Buat validasi dan preview per baris sebelum commit; laporkan nomor baris dan alasan gagal.
- [x] Batasi ukuran file (2 MB), format (XLSX), jumlah baris (500), kolom (16), ukuran arsip, dan payload staging (5 MB).
- [x] Impor secara transaksional all-or-nothing: bila ada baris invalid, konfirmasi ditolak dan tidak ada soal tersimpan.
- [x] Ekspor hanya soal yang boleh dilihat pengguna, mengikuti filter katalog, dibatasi 2.000 soal, dan menulis seluruh sel sebagai teks untuk mencegah formula injection.
- **Kriteria selesai:** alur template → preview → konfirmasi → hasil impor/ekspor tercakup oleh test; data sekolah lain dan soal tak berhak tidak bocor.

### BSC-07 — Kolaborasi dan berbagi soal/paket

- [x] Berbagi soal atau paket ke pengguna pengelola yang berada pada sekolah yang sama.
- [x] Definisikan izin `view`, `copy`, dan `edit` beserta konsekuensi server-side; penerima tidak otomatis menjadi pemilik sumber.
- [x] Sediakan terima/tolak, catatan, status, pencabutan, dan riwayat berbagi.
- [x] Audit permintaan, keputusan, pencabutan, dan penggunaan salin; izin ditetapkan pada permintaan dan tidak dapat diubah tanpa permintaan baru.
- [x] Cegah berbagi lintas sekolah pada instalasi single-school dan duplikasi permintaan aktif melalui validasi sekolah dan unique index.
- **Kriteria selesai:** seluruh transisi status dan izin diuji; akses penerima berhenti sesuai aturan saat share dicabut.

**Bukti implementasi:** migration `2026_10_25_700005_create_question_shares.php`; model `QuestionShare` dan append-only `QuestionShareEvent`; route/controller `assessment.question-shares.*`; UI `resources/js/Pages/Assessment/QuestionShares/`; navigasi `Berbagi Soal`. `view` memberi pratinjau setelah disetujui; `copy` membuat identitas baru yang dimiliki penerima; `edit` mengizinkan update sumber tetapi mempertahankan owner dan tidak memberi hak arsip/pulihkan. Seluruh share tidak lagi memberi akses saat dicabut; snapshot ujian tidak berubah.

### BSC-08 — Analisis bank soal dan hasil ujian

- [x] Tambahkan ringkasan jumlah soal menurut tipe, mapel, kategori, kurikulum, dan tingkat kognitif.
- [x] Tambahkan analisis hasil ujian yang relevan, seperti distribusi nilai dan statistik jawaban per butir jika data memadai.
- [x] Batasi rincian siswa sesuai role dan cakupan kelas/penugasan; hindari kebocoran kunci sebelum hasil dipublikasikan.
- [x] Sediakan export analisis dengan sanitasi spreadsheet dan filter yang sama dengan halaman.
- **Kriteria selesai:** metrik memiliki definisi dan rumus terdokumentasi, teruji pada data kosong/edge case, dan dibatasi policy.

**Bukti implementasi:** route `assessment.analytics.index` dan `assessment.analytics.export`; `AssessmentAnalyticsController`, `AssessmentAnalyticsService`, serta `resources/js/Pages/Assessment/Analytics/Index.vue`. Cakupan teacher/wali kelas dibatasi ke soal milik/yang dibagikan dan ujian pada penugasan atau kelas wali; admin/operator/kepala sekolah tetap school-scoped; siswa ditolak. Nilai dan analisis butir disembunyikan sampai status `results_published`; tidak ada identitas siswa, kunci, atau jawaban mentah di props/XLSX. Setiap sel ekspor dipaksa menjadi teks.

**Definisi metrik:** katalog hanya menghitung soal aktif yang berada pada cakupan pengguna; setiap distribusi menghitung identitas soal berbeda dan filter halaman diterapkan identik saat ekspor. Nilai ujian hanya dianalisis setelah status `results_published`; rata-rata = rerata `score` attempt `graded`/`submitted` yang memiliki nilai, rentang nilai 0–59/60–69/70–79/80–89/90–100, dan persentase distribusi = jumlah rentang ÷ seluruh attempt dinilai × 100. Rasio benar butir objektif = jawaban benar ÷ jawaban objektif yang memiliki hasil koreksi × 100. Rata-rata poin butir = total skor butir (nilai kosong dihitung nol) ÷ seluruh attempt dinilai; untuk esai rasio benar tidak berlaku. Analisis dan XLSX tidak menyertakan nama, NIS, jawaban mentah, atau kunci jawaban siswa.

### BSC-09 — Integrasi, regresi, dan handoff

- [x] Periksa route aktif dan nama route unik.
- [x] Periksa migration forward/rollback, policy, school isolation, audit, dan batas modular monolith.
- [x] Periksa matriks akses admin, operator, kepala sekolah, guru/wali kelas, dan siswa untuk fitur yang relevan.
- [x] Periksa UI desktop/mobile serta alur ujian yang menggunakan soal langsung dan paket.
- [x] Jalankan test dan pemeriksaan kualitas yang diwajibkan proyek; catat hasil aktual di handoff/status.
- [x] Perbarui dokumen ini dengan bukti path, route, migration, dan hasil verifikasi; sinkronkan `PROJECT_STATUS.md` bila status fase berubah.
- **Kriteria selesai:** tidak ada blocker P0/P1, semua task yang ditandai selesai memiliki bukti, dan regresi CBT yang ada tidak gagal.

**Bukti handoff:** `AssessmentControllersTest`, `AssessmentMigrationTest`, `AssessmentAnalyticsService`, `QuestionBankController`, `QuestionPackController`, `QuestionShareController`, `ExamController`, dan route pada `app/Modules/Assessment/Interface/Routes/web.php`. Sebanyak 37 route penilaian aktif; 155 route bernama terdaftar tanpa nama duplikat. Role/school scope diperiksa pada controller dan feature tests: siswa ditolak dari bank soal/analisis, akses ujian siswa terikat enrollment sendiri, guru dibatasi penugasan/ownership/share, dan pemisahan sekolah divalidasi untuk soal/paket/share/analisis; audit tersedia pada ekspor dan transisi berbagi. Feature test memverifikasi ujian sumber manual dan paket mempertahankan snapshot versi.

Migration `2026_10_25_700005_create_question_shares` diuji up/down pada SQLite in-memory (tabel hadir setelah up dan hilang setelah down), lalu diterapkan pada database development lokal; seluruh migration bank soal `700000`–`700005` kini berstatus `Ran`. Tidak ada migration rollback pada database lokal. Browser QA read-only halaman login pada 375/768/1440 px tidak menemukan horizontal overflow atau console error; route bank soal mengarahkan guest ke login. Tidak ada baseline visual regression dan tidak digunakan kredensial untuk menguji halaman terlindungi; alur CBT divalidasi melalui feature tests. Full suite 159/159 lulus (868 assertions), `npm run typecheck`, `npm run build`, Pint, route audit, serta `git diff --check` lulus. Build masih memberi advisory bahwa bundle utama berukuran 500.06 KB. Test root diperbarui agar menguji redirect guest yang memang menjadi perilaku autentikasi aplikasi.

## Quality gate untuk setiap task

- [ ] Validasi bisnis dan otorisasi dilakukan di server, bukan hanya di UI.
- [ ] Query dan route model binding dibatasi `school_id`, pemilik, penugasan, atau share yang sah.
- [ ] Jawaban/kunci tidak dikirim ke siswa sebelum aturan publikasi mengizinkan.
- [ ] Data soal yang dipakai ujian mempertahankan snapshot/histori yang dapat direproduksi.
- [ ] Konten HTML kaya dibersihkan sebelum penyimpanan dan aman saat ditampilkan.
- [ ] Impor/ekspor menangani file berbahaya, batas resource, serta formula injection.
- [ ] Mutasi penting mencatat audit trail tanpa membocorkan kunci atau data sensitif.
- [ ] Modul berkomunikasi melalui boundary yang sudah ditetapkan; tidak menambah coupling lintas modul yang dilarang.
- [ ] Migration dapat di-rollback dan constraint konsisten pada driver database yang didukung.
- [ ] UI memakai komponen, shell, dan token visual `school_suite`.

## Catatan review

| Tanggal | Task | Hasil | Catatan/bukti |
|---|---|---|---|
| 2026-10-25 | BSC-01 | implementasi | Migration metadata/versi dengan backfill butir, item, dan jawaban lama; model versi immutable dan relasi; ERD serta ADR-0010 diperbarui. Pemeriksaan PHP syntax dan `git diff --check` lulus; eksekusi migration dan test suite belum dilakukan. |
| 2026-10-25 | BSC-02 | implementasi | Katalog, validasi CRUD, versi immutable, arsip/duplikasi, dan empat tipe soal terhubung ke CBT. `npm run typecheck`, `npm run build`, Pint, `php -l`, daftar route, dan `git diff --check` lulus. Test suite dan eksekusi migration belum dilakukan; kriteria selesai menunggu verifikasi database/integrasi. |
| 2026-10-25 | BSC-03 | implementasi | Taksonomi sekolah, relasi kategori/tag pada soal dan versi, master KKO umum/khusus mapel, filter dan statistik komposisi ditambahkan. `npm run typecheck`, `npm run build`, Pint, `php -l`, dan `git diff --check` lulus; migration dan test suite belum dijalankan, sehingga verifikasi database/integrasi masih tertunda. |
| 2026-10-25 | BSC-04 | implementasi | Pengelolaan paket, status draf/aktif/arsip, item terurut berbobot, randomisasi, duplikasi, dan snapshot versi ditambahkan. `npm run typecheck`, `npm run build`, Pint, `php -l`, daftar route, dan `git diff --check` lulus; migration database dan test suite belum dijalankan. |
| 2026-10-25 | BSC-05 | implementasi | Builder ujian menerima sumber paket aktif atau pemilihan manual. Paket disaring per sekolah dan kepemilikan guru; ujian mencatat `question_pack_id` dan menyalin versi item, urutan, serta bobot ke `exam_question_items`. Versi konten bersifat immutable; perubahan/arsip setelah pembuatan tidak memutasi ujian. Siswa tidak menerima daftar bank soal/kunci melalui props halaman ujian. Feature test 10/10 lulus (112 assertions), `npm run typecheck`, `npm run build`, Pint, `php -l`, dan `git diff --check` lulus. Migrasi SQLite teruji melalui test; migrasi database lokal utama belum dijalankan. |
| 2026-10-25 | BSC-06 | implementasi | Template XLSX dengan contoh empat tipe, validasi dan preview per baris, cache stage 15 menit yang terikat user/sekolah, konfirmasi atomik all-or-nothing, serta ekspor XLSX terfilter dengan semua sel bertipe teks. Test mencakup empat tipe, error row, tidak ada commit parsial, akses siswa ditolak, isolasi sekolah, serta teks `=HYPERLINK(...)` aman saat ekspor. Test Assessment 13/13 lulus (151 assertions), `npm run typecheck`, `npm run build`, Pint, `php -l`, dan `git diff --check` diverifikasi; migrasi/database lokal utama belum dijalankan. Full suite: 147/148 lulus; `ExampleTest` masih mengharapkan `/` mengembalikan 200 meski route root kini memang me-redirect guest ke login. |
| 2026-10-25 | BSC-07 | implementasi | Permintaan soal/paket, izin eksklusif `view`/`copy`/`edit`, keputusan dengan catatan, pencabutan langsung, event history append-only, audit, serta unique index duplikasi aktif. `AssessmentControllersTest` 19/19 lulus (193 assertions), `npm run typecheck`, `npm run build`, Pint, dan `git diff --check` lulus. Full suite 153/154 lulus; satu kegagalan lama `ExampleTest` mengharapkan `/` 200 meski guest diarahkan ke login. Migrasi database lokal utama tidak diterapkan. |
| 2026-10-25 | BSC-08 | implementasi | Dashboard agregat bank soal, distribusi nilai, statistik butir pascapublikasi, cakupan sekolah/penugasan, ekspor XLSX berbasis teks, dan throttle endpoint ditambahkan. Test Assessment 24/24 lulus (260 assertions), `npm run typecheck`, `npm run build`, Pint, daftar route, dan `git diff --check` lulus. Build memberi advisory chunk JS >500 KB. Full suite 158/159 lulus; satu gagal lama `ExampleTest` karena mengharapkan `/` 200 padahal guest diarahkan ke login. Tidak ada migration baru pada BSC-08. |
| 2026-10-25 | BSC-09 | selesai | Audit integrasi: 37 route Assessment aktif dan 155 nama route unik, migration BSC-07 up/down diuji terisolasi lalu forward diterapkan ke DB development, role/school scope dan alur CBT ditinjau/dibuktikan dengan feature tests. Browser smoke login pada 375/768/1440 px tanpa overflow atau console error; visual baseline dan auth halaman protected belum diuji manual. Full suite 159/159 lulus (868 assertions), typecheck, build, Pint, dan diff-check lulus; build tetap memberi advisory bundle 500.06 KB. Ekspektasi ExampleTest diselaraskan dengan redirect login untuk guest. |

## Referensi lokal utama

- `app/Modules/Assessment/Infrastructure/Database/Migrations/2026_10_25_400002_create_cbt_tables.php`
- `app/Modules/Assessment/Interface/Http/Controllers/ExamController.php`
- `app/Modules/Assessment/Interface/Routes/web.php`
- `resources/js/Pages/Assessment/Exams/`
- [README repo referensi](https://github.com/ilhamrizqiawan21/bank-soal-cerdas/blob/main/README.md)
