# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang diserahkan untuk review.

## Identitas

- Task/issue: Penyelesaian Penuh Tahap S03 (Data Inti Akademik & Import Engine: S03-T01 s/d S03-T06)
- Requirement ID: SS-F-002, SS-F-003, SS-D-004 (Histori enrollment per periode), AI_RULES.md, ERD.md, ARCHITECTURE.md
- Branch/commit: main / commit berikutnya
- Status: Selesai & Terverifikasi (Gate S03 Passed)

## Ringkasan dan scope

- Perubahan:
  - **S03-T01: Migrasi Skema Basis Data Akademik**
    - Tabel `academic_years`, `terms`, `subjects`, `teachers`, `students`, `class_groups`, `enrollments`, dan `teaching_assignments`.
    - Invariant `SS-D-004` terjamin: tabel `students` hanya berisi identitas orang tanpa kolom `class_id` langsung; penempatan kelas ditentukan secara historis via tabel `enrollments` terikat pada `term_id`.
    - 8 Model Eloquent di `app/Modules/Academic/Infrastructure/Models/` dengan typing ketat dan relasi internal.
    - Uji integritas referensial dan rollback bersih di [`tests/Feature/AcademicMigrationTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/AcademicMigrationTest.php).
  - **S03-T02: Domain & Service Enrollment Bersejarah (`SS-D-004`)**
    - Enums domain: `EnrollmentStatusEnum` dan `StudentStatusEnum` di `app/Modules/Academic/Domain/Enums/`.
    - Service `EnrollmentService` di `app/Modules/Academic/Application/Services/EnrollmentService.php`: pendaftaran siswa ke rombel, perpindahan kelas mid-semester, dan kenaikan kelas antar-semester (`promoteOrAdvanceStudents`).
    - Terbukti secara empiris melalui [`tests/Feature/EnrollmentServiceTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/EnrollmentServiceTest.php): saat siswa naik dari kelas 7 ke kelas 8 di tahun berikutnya, rekaman kelas 7 pada semester lama tetap utuh (immutable history).
  - **S03-T03: Manajemen Penugasan Mengajar & Penugasan Wali Kelas**
    - Service `TeachingAssignmentService` di `app/Modules/Academic/Application/Services/TeachingAssignmentService.php`.
    - Penugasan guru mapel per kelas dan semester aktif dengan pencegahan entri duplikat.
    - Penetapan wali kelas dengan validasi keunikan ketat (seorang guru tidak boleh menjadi wali kelas di lebih dari satu rombel pada tahun ajaran yang sama).
    - Teruji melalui [`tests/Feature/TeachingAssignmentServiceTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/TeachingAssignmentServiceTest.php).
  - **S03-T04: Engine Impor Massal Siswa & Guru (CSV)**
    - Service `AcademicImportService` di `app/Modules/Academic/Application/Services/AcademicImportService.php`: parser CSV, pemetaan kolom, validasi kelengkapan, validasi format, dan deteksi duplikasi NIS/NISN baik di dalam berkas impor maupun terhadap database sekolah yang sudah ada.
    - Transaksional atomik: jika ditemukan baris tidak valid, seluruh batch di-rollback tanpa sisa data yatim di database.
    - Fitur auto-enrollment opsional langsung ke rombel saat impor siswa.
    - Teruji melalui [`tests/Feature/AcademicImportServiceTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/AcademicImportServiceTest.php).
  - **S03-T05: Antarmuka UI Manajemen Data Inti (Inertia + Vue)**
    - Policies: `StudentPolicy`, `TeacherPolicy`, `ClassGroupPolicy` di `app/Modules/Academic/Application/Policies/` didaftarkan via `AcademicServiceProvider`.
    - Controllers: `StudentController`, `TeacherController`, `ClassGroupController` di `app/Modules/Academic/Interface/Http/Controllers/`.
    - Routes di `app/Modules/Academic/Interface/Routes/web.php` (13 endpoints terdaftar).
    - Halaman Vue:
      - `resources/js/Pages/Academic/Students/Index.vue` (pencarian realtime, filter rombel, modal tambah siswa, modal impor CSV, unduh template).
      - `resources/js/Pages/Academic/Teachers/Index.vue` (pencarian, filter status pegawai, modal tambah guru, modal impor CSV).
      - `resources/js/Pages/Academic/Classes/Index.vue` (selector tahun ajaran, visualisasi kapasitas & rasio murid terdaftar, modal tambah rombel, modal tetapkan wali kelas).
    - Navigasi sidebar [`SidebarNavigation.vue`](file:///home/ilhamrizqiawan/Projects/school_suite/resources/js/components/SidebarNavigation.vue) diperbarui dengan route data siswa, data guru, dan kelas.
    - Teruji melalui [`tests/Feature/AcademicControllersTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/AcademicControllersTest.php).
  - **S03-T06: Verifikasi Gate S03 & Rekonsiliasi Data**
    - Verifikasi arsitektur: `ModularBoundaryTest` memastikan tidak ada pelanggaran batas modular monolith atau cross-module infrastructure coupling.
    - Seluruh test suite (62 tests, 295 assertions) lulus 100%.
- Tidak diubah:
  - Modul jadwal, presensi, rapor, atau integrasi (akan dikerjakan pada tahap berikutnya).

## Dampak teknis

- Modul/file yang disentuh:
  - Backend:
    - `app/Modules/Academic/AcademicServiceProvider.php`
    - `app/Modules/Academic/Infrastructure/Database/Migrations/2026_10_25_100000_create_academic_tables.php`
    - `app/Modules/Academic/Infrastructure/Models/AcademicYear.php`
    - `app/Modules/Academic/Infrastructure/Models/Term.php`
    - `app/Modules/Academic/Infrastructure/Models/Subject.php`
    - `app/Modules/Academic/Infrastructure/Models/Teacher.php`
    - `app/Modules/Academic/Infrastructure/Models/Student.php`
    - `app/Modules/Academic/Infrastructure/Models/ClassGroup.php`
    - `app/Modules/Academic/Infrastructure/Models/Enrollment.php`
    - `app/Modules/Academic/Infrastructure/Models/TeachingAssignment.php`
    - `app/Modules/Academic/Domain/Enums/EnrollmentStatusEnum.php`
    - `app/Modules/Academic/Domain/Enums/StudentStatusEnum.php`
    - `app/Modules/Academic/Application/Policies/StudentPolicy.php`
    - `app/Modules/Academic/Application/Policies/TeacherPolicy.php`
    - `app/Modules/Academic/Application/Policies/ClassGroupPolicy.php`
    - `app/Modules/Academic/Application/Services/EnrollmentService.php`
    - `app/Modules/Academic/Application/Services/TeachingAssignmentService.php`
    - `app/Modules/Academic/Application/Services/AcademicImportService.php`
    - `app/Modules/Academic/Interface/Http/Controllers/StudentController.php`
    - `app/Modules/Academic/Interface/Http/Controllers/TeacherController.php`
    - `app/Modules/Academic/Interface/Http/Controllers/ClassGroupController.php`
    - `app/Modules/Academic/Interface/Routes/web.php`
  - Frontend:
    - `resources/js/Pages/Academic/Students/Index.vue`
    - `resources/js/Pages/Academic/Teachers/Index.vue`
    - `resources/js/Pages/Academic/Classes/Index.vue`
    - `resources/js/components/SidebarNavigation.vue`
    - `resources/js/types/index.d.ts`
  - Pengujian:
    - `tests/Feature/AcademicMigrationTest.php`
    - `tests/Feature/EnrollmentServiceTest.php`
    - `tests/Feature/TeachingAssignmentServiceTest.php`
    - `tests/Feature/AcademicImportServiceTest.php`
    - `tests/Feature/AcademicControllersTest.php`
  - Dokumentasi:
    - `docs/TODO.md`
    - `docs/PROJECT_STATUS.md`
    - `docs/HANDOFF.md`
- Migrasi/data: Tabel `academic_years`, `terms`, `subjects`, `teachers`, `students`, `class_groups`, `enrollments`, `teaching_assignments`.
- Keamanan/izin:
  - Server-side Default Deny di seluruh endpoint akademik.
  - Siswa/Guru tanpa izin dilarang membuat atau mengubah data akademik (403).
  - Isolasi data per sekolah pada seluruh operasi query dan relasi.
  - Seluruh mutasi enrollment, penugasan guru, impor massal tercatat di audit log.
- Keputusan atau asumsi:
  - Mengikuti `SS-D-004`, tabel `students` murni identitas orang dan tidak memiliki kolom `class_id`. Penempatan rombel per semester tersimpan di `enrollments`.

## Bukti

- Perintah tes dan hasil:
  - `composer test`: 62 passed, 295 assertions (3.33s) — 100% lulus (Unit, Feature, Migration, Authorization, Audit, Enrollment, Assignments, Import, Controllers, Boundary Architecture).
  - `composer lint`: passed (Pint test passed).
  - `npm run typecheck`: passed (`vue-tsc --noEmit` exit 0).
  - `npm run build`: passed (Vite production bundle dibangun tanpa error).
  - `python3 ~/.gemini/config/skills/docs-governance/scripts/audit-docs.py --root . --scope full`: passed (exit code 0).
- Langkah review manual:
  - Jalankan `php artisan test --filter=Academic` untuk menjalankan test suite akademik.
  - Akses `/akademik/siswa` untuk memverifikasi daftar siswa, filter, modal tambah, dan modal impor CSV beserta download template.
  - Akses `/akademik/guru` untuk melihat daftar guru dan penetapan status kepegawaian.
  - Akses `/akademik/kelas` untuk melihat rombongan belajar per tahun ajaran dan penetapan wali kelas.

## Risiko dan pemulihan

- Risiko tersisa: Modul jadwal (`schedules`), presensi harian/pertemuan (`attendance_records`), dan jurnal mengajar guru (`teaching_journals`) belum dipasang. Hal ini merupakan cakupan pada Tahap S04.
- Rollback: `git revert HEAD` jika diperlukan pengembalian commit.
- Follow-up: Siap lanjut ke **Tahap S04: Operasi Akademik Harian & Jadwal (`app/Modules/Academic`)**.
