# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang diserahkan untuk review.

## Identitas

- Task/issue: Penyelesaian Penuh Tahap S06 (Penilaian, Rapor & Formula Berversi: S06-T01 s/d S06-T06)
- Requirement ID: SS-F-007 (Penilaian, Rapor & Formula Berversi), SS-D-005 (Snapshot Rapor Immutable), AI_RULES.md (Aturan 6 & 7: Modular monolith & pengujian formula lengkap), DESIGN.md, ERD.md, ARCHITECTURE.md
- Branch/commit: main / commit berikutnya
- Status: Selesai & Terverifikasi (Gate S06 Passed)

## Ringkasan dan scope

- Perubahan:
  - **S06-T01: Migrasi Skema Penilaian & Rapor**
    - Tabel `assessment_schemes` (skema penilaian berversi, nama, kode, versi, status aktif, pembulatan, digit desimal, passing grade default).
    - Tabel `assessment_components` (komponen nilai: tugas, formatif, sumatif, UTS, UAS, bobot persentase %, passing grade/KKTP, urutan).
    - Tabel `scores` (skor per enrollment per komponen nilai, nilai mentah, nilai remedial, nilai akhir berbobot, catatan guru, user pencatat).
    - Tabel `remedial_records` (riwayat pelaksanaan remedial, skor awal, skor remedial, batas KKTP/KKM, skor efektif disesuaikan).
    - Tabel `report_snapshots` (snapshot rapor terbit berformat JSON snapshot mandiri yang immutable, memuat skema, nilai, kehadiran, catatan wali kelas, status `draft`/`published`/`archived`, dan checksum SHA-256).
    - Enums domain di `app/Modules/Assessment/Domain/Enums/`: `RoundingRuleEnum` (`half_up`, `floor`, `ceil`) dan `ReportStatusEnum` (`draft`, `published`, `archived`).
    - 5 Model Eloquent di `app/Modules/Assessment/Infrastructure/Models/` (`AssessmentScheme`, `AssessmentComponent`, `Score`, `RemedialRecord`, `ReportSnapshot`).
    - Service provider `AssessmentServiceProvider` di `app/Modules/Assessment/AssessmentServiceProvider.php`.
    - Teruji lewat [`tests/Feature/AssessmentMigrationTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/AssessmentMigrationTest.php) (4 tests, 9 assertions).
  - **S06-T02: Engine Penghitungan Nilai & Remedial Berversi (`GradeCalculationEngine`)**
    - Service murni domain di `app/Modules/Assessment/Domain/Services/GradeCalculationEngine.php`:
      - Formula pembobotan persentase dinamis ($\sum \text{score}_i \times \text{weight}_i$).
      - Aturan pembulatan baku (`half_up`, `floor`, `ceil`) dengan presisi desimal terkonfigurasi.
      - Logika remedial ketat: nilai remedial tidak boleh melebihi batas ketuntasan KKTP ($\min(\text{remedial}, \text{KKTP})$). Jika nilai mentah awal sudah tuntas atau nilai remedial lebih kecil, nilai awal dipertahankan.
      - Penanganan nilai batas: 0.0, 100.0, desimal `.5`, nilai kosong/null (`AI_RULES.md` Aturan 7).
      - Konversi huruf predikat baku: A (>= 90), B (>= 80), C (>= 75 / KKTP), D (< KKTP).
    - Teruji lewat [`tests/Unit/GradeCalculationEngineTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Unit/GradeCalculationEngineTest.php) (11 tests, 33 assertions passed).
  - **S06-T03: Penerbitan Rapor & Immutable Snapshot Engine (`SS-D-005`)**
    - Service `ReportPublishingService` di `app/Modules/Assessment/Application/Services/`:
      - Mengagregasikan capaian akademik seluruh mata pelajaran siswa rombel, presensi siswa semester berjalan, dan catatan wali kelas.
      - Menghasilkan tanda tangan digital `checksum` SHA-256 dari payload gabungan snapshot.
      - Metode `publishReport`: Mengunci rapor dengan status `published`, mencatat `published_at`, dan menolak perubahan jika rapor sudah pernah diterbitkan.
      - Mencegah modifikasi snapshot terbit: Terbukti bit-for-bit bahwa mutasi data mentah di tabel `scores` pasca-penerbitan rapor TIDAK mengubah sedikitpun data rapor yang telah terbit.
    - Teruji lewat [`tests/Feature/ReportSnapshotImmutabilityTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/ReportSnapshotImmutabilityTest.php) (3 tests, 17 assertions passed).
  - **S06-T04: Generator Cetak Rapor & Ekspor Ledger Nilai**
    - Service `ReportGeneratorService` di `app/Modules/Assessment/Application/Services/`:
      - `formatReportView`: menyusun representasi tampilan rapor siap cetak langsung dari data snapshot mandiri.
      - `exportClassLedger`: menyusun matriks buku ledger nilai rombel (seluruh siswa vs seluruh mata pelajaran, nilai rerata kelas, dan rekapitulasi presensi S/I/A).
    - Controller `ReportCardController` di `app/Modules/Assessment/Interface/Http/Controllers/ReportCardController.php`:
      - Dukungan respon HTML Inertia dan format JSON untuk ekspor ledger nilai.
  - **S06-T05: UI Input Nilai Guru & Verifikasi Rapor Wali Kelas (Inertia + Vue)**
    - Lembar Input Nilai Guru (`resources/js/Pages/Assessment/Scores/InputGrid.vue`): antarmuka spreadsheet-like grid, kolom NIS, nama, nilai mentah, nilai remedial, preview otomatis nilai akhir & indikator KKTP tuntas/belum tuntas, catatan kemajuan siswa, dan penyimpanan batch instan.
    - Skema Penilaian Berversi (`resources/js/Pages/Assessment/Schemes/Index.vue`): konfigurasi nama, kode skema (auto-increment version bila kode sama), aturan pembulatan, digit desimal, KKTP default, dan rincian komponen bobot persentase dengan pengecekan total bobot 100%.
    - Rapor Siswa & Snapshot (`resources/js/Pages/Assessment/Reports/Index.vue`): filter rombel, pemantauan status draf vs terbit, indikator checksum hash SHA-256, aksi generate rapor kelas batch, dan penguncian/penerbitan permanen (`SS-D-005`).
    - Lembar Cetak Rapor (`resources/js/Pages/Assessment/Reports/Show.vue`): layout baku cetak rapor pendidikan Indonesia (kop sekolah, identitas peserta didik, tabel capaian kompetensi akademik, presensi S/I/A, catatan wali kelas, tanda tangan 3 pihak, dan footer keaslian digital SHA-256), dilengkapi tombol cetak dan CSS `@media print`.
    - Buku Ledger Nilai (`resources/js/Pages/Assessment/Reports/Ledger.vue`): rekapitulasi tabular matriks nilai seluruh siswa dan mata pelajaran beserta nilai rerata kelas.
    - Menu samping `SidebarNavigation.vue` terintegrasi dengan menu "Input Nilai", "Skema Penilaian", dan "Rapor Siswa".
  - **S06-T06: Controller, Routes, Policies & Verifikasi Gate S06**
    - Controller `ScoreInputController`, `AssessmentSchemeController`, `ReportCardController`.
    - Routes web di `app/Modules/Assessment/Interface/Routes/web.php` (10 endpoint terdaftar).
    - Policy `AssessmentPolicy` di `app/Modules/Assessment/Application/Policies/AssessmentPolicy.php` dengan penegakan otorisasi: siswa hanya dapat melihat rapor pribadinya sendiri (403 untuk rapor siswa lain); hanya peran guru/wali kelas/kurikulum/admin yang dapat menginput nilai atau menerbitkan rapor.
    - Decoupling murni lintas modul: query data mengajar dan kelas menggunakan `AcademicQueryService::getTeachingAssignmentsForClass` tanpa model cross-import.
    - Teruji lewat [`tests/Feature/AssessmentControllersTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/AssessmentControllersTest.php) (8 tests, 92 assertions passed).
- Tidak diubah:
  - Modul persiapan pilot, backup/restore, CBT, SPMB, Sipus, dan integrasi Teacher Desk (tahap berikutnya).

## Dampak teknis

- Modul/file yang disentuh:
  - Backend:
    - `app/Modules/Academic/Application/Services/AcademicQueryService.php` (penambahan `getTeachingAssignmentsForClass`)
    - `app/Modules/Assessment/AssessmentServiceProvider.php`
    - `app/Modules/Assessment/Domain/Enums/RoundingRuleEnum.php`
    - `app/Modules/Assessment/Domain/Enums/ReportStatusEnum.php`
    - `app/Modules/Assessment/Domain/Services/GradeCalculationEngine.php`
    - `app/Modules/Assessment/Infrastructure/Database/Migrations/2026_10_25_400000_create_assessment_tables.php`
    - `app/Modules/Assessment/Infrastructure/Models/AssessmentScheme.php`
    - `app/Modules/Assessment/Infrastructure/Models/AssessmentComponent.php`
    - `app/Modules/Assessment/Infrastructure/Models/Score.php`
    - `app/Modules/Assessment/Infrastructure/Models/RemedialRecord.php`
    - `app/Modules/Assessment/Infrastructure/Models/ReportSnapshot.php`
    - `app/Modules/Assessment/Application/Services/ScoreService.php`
    - `app/Modules/Assessment/Application/Services/ReportPublishingService.php`
    - `app/Modules/Assessment/Application/Services/ReportGeneratorService.php`
    - `app/Modules/Assessment/Application/Policies/AssessmentPolicy.php`
    - `app/Modules/Assessment/Interface/Http/Controllers/ScoreInputController.php`
    - `app/Modules/Assessment/Interface/Http/Controllers/AssessmentSchemeController.php`
    - `app/Modules/Assessment/Interface/Http/Controllers/ReportCardController.php`
    - `app/Modules/Assessment/Interface/Routes/web.php`
  - Frontend:
    - `resources/js/Pages/Assessment/Scores/InputGrid.vue`
    - `resources/js/Pages/Assessment/Schemes/Index.vue`
    - `resources/js/Pages/Assessment/Reports/Index.vue`
    - `resources/js/Pages/Assessment/Reports/Show.vue`
    - `resources/js/Pages/Assessment/Reports/Ledger.vue`
    - `resources/js/components/SidebarNavigation.vue`
  - Pengujian:
    - `tests/Feature/AssessmentMigrationTest.php`
    - `tests/Unit/GradeCalculationEngineTest.php`
    - `tests/Feature/ReportSnapshotImmutabilityTest.php`
    - `tests/Feature/AssessmentControllersTest.php`
  - Dokumentasi:
    - `docs/TODO.md`
    - `docs/PROJECT_STATUS.md`
    - `docs/HANDOFF.md`
- Migrasi/data: Tabel `assessment_schemes`, `assessment_components`, `scores`, `remedial_records`, `report_snapshots`.
- Keamanan/izin:
  - Kebijakan `AssessmentPolicy` Default Deny terdaftar di Service Provider.
  - Siswa hanya berhak melihat rapor snapshot milik mereka sendiri berdasarkan kecocokan `user_id` di basis data siswa (akses ke rapor siswa lain menghasilkan HTTP 403 Forbidden).
  - Siswa dilarang keras mengakses endpoint input nilai (HTTP 403 Forbidden).
  - Penerbitan dan penguncian rapor dibatasi untuk wali kelas, kepala sekolah, dan kurikulum.
- Keputusan atau asumsi:
  - Sesuai `SS-D-005`, rapor terbit disimpan sebagai immutable snapshot terisolasi yang dilengkapi tanda tangan checksum SHA-256. Setiap kali rapor dicetak atau dilihat kembali, sumber data berasal dari snapshot JSON yang terkunci, bukan dari pembacaan ulang tabel transaksional mentah.
  - Formula remedial mengadopsi standar kurikulum: skor remedial maksimal bernilai sebesar batas KKTP ($\min(\text{remedial}, \text{KKTP})$).

## Bukti

- Hasil tes otomatis:
  - Total test suite: 123 tests, 587 assertions passed (100% HIJAU) dalam 6.2s.
  - `tests/Feature/AssessmentMigrationTest.php`: 4 passed, 9 assertions.
  - `tests/Unit/GradeCalculationEngineTest.php`: 11 passed, 33 assertions (termasuk kasus table-driven batas 0, 100, pecahan .5, null, dan remedial).
  - `tests/Feature/ReportSnapshotImmutabilityTest.php`: 3 passed, 17 assertions (terbukti bit-for-bit snapshot kebal dari modifikasi pasca terbit).
  - `tests/Feature/AssessmentControllersTest.php`: 8 passed, 92 assertions (otorisasi siswa vs guru, input batch, skema berversi, penerbitan rapor, ledger JSON).
  - `tests/Architecture/ModularBoundaryTest.php`: 4 passed, 6 assertions (0 pelanggaran modular monolith).
- Pemeriksaan statis:
  - `vendor/bin/pint --test`: passed (100% sesuai standar PSR-12/Laravel).
  - `npm run typecheck` (`vue-tsc --noEmit`): passed (0 error).
  - `npm run build` (`vite build`): passed (sukses mem-bundle seluruh aset Vue & Tailwind CSS).
  - `audit-docs.py`: passed (0 broken links, spine valid).

## Risiko yang tersisa

- Skema penilaian saat ini mendukung pembobotan persentase dan remedial standar; variasi format rapor khusus (misalnya rapor kurikulum merdeka berbasis deskripsi capaian pembelajaran naratif per elemen) dapat dikembangkan lebih lanjut pada modul ekspansi pembelajaran (LMS).
- Operasi backup & restore otomatis belum diuji pada kontainer/lingkungan terpisah (akan dieksekusi pada Tahap S07).

## Review manual

1. Login sebagai Guru (`teacher@sekolah.id`), buka menu "Input Nilai" (`/penilaian/skor`), pilih rombel dan komponen nilai. Masukkan nilai mentah dan remedial, klik "Simpan Semua Nilai".
2. Verifikasi bahwa nilai akhir preview dan indikator status ketuntasan KKTP dihitung seketika sesuai aturan remedial $\min(\text{remedial}, \text{KKTP})$.
3. Login sebagai Administrator (`admin@sekolah.id`), buka menu "Skema Penilaian" (`/penilaian/skema`), coba tambahkan skema dan komponen bobot baru.
4. Buka menu "Rapor Siswa" (`/penilaian/rapor`), klik tombol "Generate Rapor Kelas", lalu klik "Terbitkan & Kunci" pada salah satu siswa.
5. Buka tampilan cetak rapor siswa (`/penilaian/rapor/{id}`), klik tombol cetak untuk memverifikasi kesesuaian layout cetak dan keberadaan checksum digital SHA-256.
6. Buka menu "Buku Ledger Nilai" (`/penilaian/ledger?class_group_id=...&term_id=...`) untuk melihat matriks nilai seluruh siswa dan mata pelajaran.
7. Login sebagai Siswa (`ahmad@sekolah.id`), buka URL rapor siswa lain dan pastikan sistem menolak dengan HTTP 403 Forbidden.

## Rencana rollback

1. Kembalikan branch ke commit sebelum S06: `git reset --hard 1dd65b6`.
2. Rollback migrasi penilaian: `php artisan migrate:rollback --step=1`.
3. Re-build frontend: `npm run build`.
