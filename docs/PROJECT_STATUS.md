# Project Status — School Suite

Terakhir diperbarui: 25 Oktober 2026

## Keadaan saat ini

- Fase: S03 Data Inti Akademik & Import Engine selesai; siap masuk ke S04 Operasi Akademik Harian & Jadwal.
- Kesiapan:
  - S01 Fondasi: Bootstrap Laravel 13, Inertia.js v3, Vue 3, Vite v8, Tailwind CSS v4, TypeScript, struktur 9 modul monolit modular, layout shell dasar, dan boundary test arsitektur selesai.
  - S02 Akses: Skema basis data Core, otorisasi server-side Default Deny, engine audit log terpusat, autentikasi rate-limited, UI kelola user & profil sekolah, dan matriks akses multi-peran selesai.
  - S03 Data Inti Akademik:
    - Skema basis data Akademik (`academic_years`, `terms`, `subjects`, `teachers`, `students`, `class_groups`, `enrollments`, `teaching_assignments`) forward & rollback bersih (`AcademicMigrationTest`).
    - Service pendaftaran dan kenaikan kelas bersejarah yang menjamin histori enrollment tidak berubah (`SS-D-004`) serta audit trail (`EnrollmentServiceTest`).
    - Manajemen penugasan mengajar dan wali kelas per rombel dengan validasi keunikan per tahun ajaran (`TeachingAssignmentServiceTest`).
    - Engine impor massal CSV siswa dan guru dengan validasi duplikasi NIS/NISN baris per baris, auto-enrollment, dan rollback transaksional atomik (`AcademicImportServiceTest`).
    - Antarmuka UI manajemen siswa, guru, dan rombel kelas terintegrasi dengan filter pencarian, modal form, dan download template CSV (`AcademicControllersTest`).
  - Seluruh CI dan test suite (62 tests, 295 assertions, Unit, Feature, Architecture, Lint, Typecheck, Build, Docs Governance) hijau 100%.
- Langkah berikut: Tahap S04 Operasi Akademik Harian & Jadwal (`app/Modules/Academic`: schedules, sessions, attendance, journals).

## Blocker sebelum data nyata

- Profil sekolah pilot, ukuran data, format rapor, dan aturan pembulatan belum dikunci.
- Kebijakan retensi, backup operasional, dan penanggung jawab pemulihan belum disahkan.
- Lisensi kode/template/font dari repo sumber belum diinventaris lengkap.

## Delete zone

| Konsep | Status | Syarat kembali |
|---|---|---|
| Tabungan siswa | Dikeluarkan | ADR baru dan persetujuan pemilik produk |
| Multi-tenant | Ditunda | kebutuhan nyata dan desain isolasi |
| Microservices | Ditunda | bukti beban/batas deployment |
| Database bersama dengan Teacher Desk | Dilarang | tidak direncanakan; gunakan API |

