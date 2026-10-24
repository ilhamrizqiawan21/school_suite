# Project Status — School Suite

Terakhir diperbarui: 25 Oktober 2026

## Keadaan saat ini

- Fase: S05 Ruang Kerja Wali Kelas & Kesiswaan selesai; siap masuk ke S06 Penilaian, Rapor & Formula Berversi.
- Kesiapan:
  - S01 Fondasi: Bootstrap Laravel 13, Inertia.js v3, Vue 3, Vite v8, Tailwind CSS v4, TypeScript, struktur 9 modul monolit modular, layout shell dasar, dan boundary test arsitektur selesai.
  - S02 Akses: Skema basis data Core, otorisasi server-side Default Deny, engine audit log terpusat, autentikasi rate-limited, UI kelola user & profil sekolah, dan matriks akses multi-peran selesai.
  - S03 Data Inti Akademik: Skema basis data Akademik, histori enrollment tidak berubah (`SS-D-004`), penugasan guru & wali kelas, engine impor massal CSV dengan validasi duplikasi NIS/NISN, serta antarmuka UI data inti selesai.
  - S04 Operasi Akademik Harian & Jadwal:
    - Skema basis data `schedules`, `lesson_sessions`, `attendance_records`, dan `teaching_journals`.
    - Engine validasi benturan jadwal (`ScheduleService`) mendeteksi benturan guru, benturan rombel, dan benturan ruang pada slot waktu overlap, serta alur publikasi draf (`ScheduleConflictTest`).
    - Service presensi massal (`AttendanceService`) dan jurnal pembelajaran (`JournalService`) dengan pencatatan audit log otomatis pada setiap koreksi absensi (`SS-F-005`) (`AttendanceAndJournalTest`).
    - Antarmuka UI jadwal mingguan (`Schedules/Index.vue`), presensi cepat dengan toggle satu klik (`Attendance/Index.vue`), dan riwayat jurnal harian (`Journals/Index.vue`) terintegrasi (`DailyOperationsControllersTest`).
  - S05 Ruang Kerja Wali Kelas & Kesiswaan:
    - Skema basis data `student_notes`, `student_incidents`, `student_achievements`, dan `assistance_records`.
    - Isolasi akses data sensitif (`confidential`, `internal`, `public`) sesuai `SS-F-006` dengan perlindungan server-side policy (`StudentNotePolicy`) dan filter ekspor rapor (`StudentObservationService`, `HomeroomIsolationTest`).
    - Service rekapitulasi kehadiran rombel dan pendeteksian Early Warning ketidakhadiran (Alpa >= 3 hari atau kehadiran < 85%) (`HomeroomAttendanceService`, `HomeroomAttendanceSummaryTest`).
    - Decoupling murni lintas modul via `AcademicQueryService` tanpa model/table coupling terlarang ke modul internal lain (`ModularBoundaryTest`).
    - Antarmuka UI Ruang Kerja Wali Kelas: Dashboard perwalian (`Homeroom/Dashboard/Index.vue`), Catatan Observasi (`Homeroom/Observations/Index.vue`), dan Kedisiplinan/Prestasi/Bantuan (`Homeroom/Discipline/Index.vue`) terintegrasi (`HomeroomControllersTest`).
  - Seluruh CI dan test suite (96 tests, 432 assertions, Unit, Feature, Architecture, Lint, Typecheck, Build, Docs Governance) hijau 100%.
- Langkah berikut: Tahap S06 Manajemen Penilaian, Rapor & Formula Berversi (`app/Modules/Assessment`: skema nilai berversi, komponen penilaian, pembulatan desimal, logika remedial, cetak rapor, dan immutable report snapshot `SS-D-005`).

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

