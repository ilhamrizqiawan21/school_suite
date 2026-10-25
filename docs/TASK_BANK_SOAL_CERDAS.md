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
| Paket soal | ⬜ Belum | Belum ada entitas paket yang dapat dikelola dan digunakan kembali oleh ujian. |
| Kategori, tag, taksonomi | 🟡 Implementasi tersedia | Kategori/tag sekolah-aware, master KKO terfilter sekolah/mapel, filter katalog, dan ringkasan komposisi tersedia; migration menunggu verifikasi database. |
| Impor dan ekspor bank soal | ⬜ Belum | Belum ada alur spreadsheet untuk butir soal. |
| Berbagi soal/paket | ⬜ Belum | Belum ada permintaan berbagi, persetujuan, izin, atau riwayat. |
| Analisis ujian | 🟡 Sebagian | Hasil dan nilai ujian tersedia; analisis agregat dan rincian butir/siswa dari referensi belum lengkap. |

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

- [ ] Buat paket dengan nama, deskripsi, jenjang/kurikulum, durasi opsional, status, dan pembuat.
- [ ] Kelola item soal dengan urutan dan bobot nilai; dukung tambah, hapus, susun ulang, dan duplikasi paket.
- [ ] Atur pengacakan soal dan opsi di tingkat paket bila sesuai model ujian.
- [ ] Cegah pemilihan soal lintas sekolah atau soal yang tidak boleh dilihat/disalin pengguna.
- **Kriteria selesai:** paket dan item dikelola secara transaksional, agregat jumlah soal konsisten, dan policy berlaku untuk setiap operasi.

### BSC-05 — Integrasi paket dengan CBT yang sudah ada

- [ ] Builder ujian dapat memilih paket atau memilih soal langsung.
- [ ] Salin/snapshot konten, kunci, urutan, dan bobot soal saat ujian dipublikasikan atau saat aturan snapshot ditetapkan.
- [ ] Pastikan proses attempt, autosave, deadline, anti-cheat, koreksi, sinkronisasi nilai, dan export yang sudah ada tetap memakai model Assessment.
- [ ] Definisikan perilaku ketika paket atau soal sumber diubah/diarsip setelah ujian dibuat.
- **Kriteria selesai:** ujian dari paket dapat ditempuh end-to-end dan perubahan bank/paket tidak mengubah ujian yang sudah dipublikasikan.

### BSC-06 — Impor dan ekspor spreadsheet

- [ ] Sediakan template spreadsheet dengan kolom dan contoh untuk setiap tipe soal.
- [ ] Buat validasi dan preview per baris sebelum commit; laporkan nomor baris dan alasan gagal.
- [ ] Batasi ukuran file, tipe file, jumlah baris, dan waktu pemrosesan.
- [ ] Impor secara transaksional atau dengan kebijakan partial-success yang eksplisit; jangan membuat data yatim.
- [ ] Ekspor hanya soal yang boleh dilihat pengguna; cegah formula injection pada sel spreadsheet.
- **Kriteria selesai:** alur template → preview → konfirmasi → hasil impor/ekspor tercakup oleh test; data sekolah lain dan soal tak berhak tidak bocor.

### BSC-07 — Kolaborasi dan berbagi soal/paket

- [ ] Berbagi soal atau paket ke pengguna yang berada pada sekolah yang sama.
- [ ] Definisikan izin `view`, `copy`, dan `edit` beserta konsekuensi server-side; penerima tidak otomatis menjadi pemilik sumber.
- [ ] Sediakan terima/tolak, catatan, status, pencabutan, dan riwayat berbagi.
- [ ] Audit semua perubahan izin dan keputusan penerima.
- [ ] Cegah berbagi lintas sekolah pada instalasi single-school dan cegah duplikasi permintaan aktif.
- **Kriteria selesai:** seluruh transisi status dan izin diuji; akses penerima berhenti sesuai aturan saat share dicabut.

### BSC-08 — Analisis bank soal dan hasil ujian

- [ ] Tambahkan ringkasan jumlah soal menurut tipe, mapel, kategori, kurikulum, dan tingkat kognitif.
- [ ] Tambahkan analisis hasil ujian yang relevan, seperti distribusi nilai dan statistik jawaban per butir jika data memadai.
- [ ] Batasi rincian siswa sesuai role dan cakupan kelas/penugasan; hindari kebocoran kunci sebelum hasil dipublikasikan.
- [ ] Sediakan export analisis dengan sanitasi spreadsheet dan filter yang sama dengan halaman.
- **Kriteria selesai:** metrik memiliki definisi dan rumus terdokumentasi, teruji pada data kosong/edge case, dan dibatasi policy.

### BSC-09 — Integrasi, regresi, dan handoff

- [ ] Periksa route aktif dan nama route unik.
- [ ] Periksa migration forward/rollback, policy, school isolation, audit, dan batas modular monolith.
- [ ] Periksa matriks akses admin, operator, kepala sekolah, guru/wali kelas, dan siswa untuk fitur yang relevan.
- [ ] Periksa UI desktop/mobile serta alur ujian yang menggunakan soal langsung dan paket.
- [ ] Jalankan test dan pemeriksaan kualitas yang diwajibkan proyek; catat hasil aktual di handoff/status.
- [ ] Perbarui dokumen ini dengan bukti path, route, migration, dan hasil verifikasi; sinkronkan `PROJECT_STATUS.md` bila status fase berubah.
- **Kriteria selesai:** tidak ada blocker P0/P1, semua task yang ditandai selesai memiliki bukti, dan regresi CBT yang ada tidak gagal.

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

## Referensi lokal utama

- `app/Modules/Assessment/Infrastructure/Database/Migrations/2026_10_25_400002_create_cbt_tables.php`
- `app/Modules/Assessment/Interface/Http/Controllers/ExamController.php`
- `app/Modules/Assessment/Interface/Routes/web.php`
- `resources/js/Pages/Assessment/Exams/`
- [README repo referensi](https://github.com/ilhamrizqiawan21/bank-soal-cerdas/blob/main/README.md)
