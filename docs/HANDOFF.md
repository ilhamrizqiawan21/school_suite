# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang diserahkan untuk review.

## Identitas

- Task/issue: Penyelesaian Penuh Tahap S04 (Operasi Akademik Harian & Jadwal: S04-T01 s/d S04-T06)
- Requirement ID: SS-F-004 (Jadwal manual & deteksi benturan), SS-F-005 (Presensi harian/pertemuan & audit log koreksi), DESIGN.md, ERD.md, ARCHITECTURE.md
- Branch/commit: main / commit berikutnya
- Status: Selesai & Terverifikasi (Gate S04 Passed)

## Ringkasan dan scope

- Perubahan:
  - **S04-T01: Migrasi Skema Jadwal, Presensi, dan Jurnal**
    - Tabel `schedules` (jadwal pelajaran per slot waktu, hari, ruang, status draf/terbit).
    - Tabel `lesson_sessions` (pertemuan pembelajaran aktual terhubung dengan penugasan mengajar/jadwal).
    - Tabel `attendance_records` (presensi harian sekolah maupun presensi per pertemuan: Hadir, Sakit, Izin, Alpa).
    - Tabel `teaching_journals` (catatan materi pokok, indikator/tujuan pembelajaran, catatan kelas, dan snapshot ringkasan kehadiran).
    - 4 Model Eloquent di `app/Modules/Academic/Infrastructure/Models/` (`Schedule`, `LessonSession`, `AttendanceRecord`, `TeachingJournal`).
  - **S04-T02: Engine Validasi Benturan Jadwal (Conflict Detection)**
    - Enums domain: `ScheduleStatusEnum` di `app/Modules/Academic/Domain/Enums/`.
    - Service `ScheduleService` di `app/Modules/Academic/Application/Services/ScheduleService.php`:
      - Deteksi benturan guru: mencegah seorang guru dijadwalkan mengajar di 2 kelas berbeda pada hari dan slot waktu yang tumpang tindih (*overlapping*).
      - Deteksi benturan rombel: mencegah rombel dijadwalkan 2 mata pelajaran berbeda pada slot waktu yang sama.
      - Deteksi benturan ruang/lab: mencegah ruangan dipakai bersamaan oleh kelas/guru lain.
      - Alur publikasi massal draf jadwal (`publishSchedules`) beserta pencatatan audit log `academic.schedule.published`.
    - Teruji lewat [`tests/Feature/ScheduleConflictTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/ScheduleConflictTest.php) (6 tests, 13 assertions).
  - **S04-T03: Service Presensi Siswa & Jurnal Pembelajaran**
    - Enums domain: `AttendanceStatusEnum` dan `AttendanceTypeEnum` di `app/Modules/Academic/Domain/Enums/`.
    - Service `AttendanceService` di `app/Modules/Academic/Application/Services/AttendanceService.php`: pencatatan presensi massal per pertemuan dan harian, deteksi mutasi nilai, dan pencatatan otomatis audit event `academic.attendance.corrected` dengan nilai lama dan nilai baru (`SS-F-005`).
    - Service `JournalService` di `app/Modules/Academic/Application/Services/JournalService.php`: pengisian jurnal mengajar guru, snapshot ringkasan kehadiran, dan penutupan sesi otomatis (`status = completed`).
    - Teruji lewat [`tests/Feature/AttendanceAndJournalTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/AttendanceAndJournalTest.php) (3 tests, 12 assertions).
  - **S04-T04: UI Input Presensi Cepat & Grid Jadwal Guru (Mobile/Tablet Friendly)**
    - Halaman jadwal mingguan (`resources/js/Pages/Academic/Schedules/Index.vue`): filter rombel/guru, tampilan kartu jadwal Senin - Sabtu, badge draf/terbit, modal tambah jadwal dengan validasi benturan, tombol publikasi draf massal.
    - Halaman input presensi cepat (`resources/js/Pages/Academic/Attendance/Index.vue`): selector rombel & tanggal, tombol satu klik "Tandai Semua Hadir", toggle tombol H/S/I/A per siswa, input keterangan sakit/izin, kartu ringkasan langsung (Live Summary Cards).
    - Halaman jurnal mengajar (`resources/js/Pages/Academic/Journals/Index.vue`): riwayat jurnal per pertemuan, materi pokok, tujuan pembelajaran, dan snapshot status presensi.
  - **S04-T05: Controller & Kebijakan Otorisasi Operasi Harian**
    - Otorisasi server-side: `SchedulePolicy` dan `AttendancePolicy` didaftarkan via `AcademicServiceProvider`. Siswa dilarang membuat jadwal (403), guru hanya diizinkan mencatat sesi mengajarnya sendiri.
    - Controller `ScheduleController`, `AttendanceController`, `JournalController` di `app/Modules/Academic/Interface/Http/Controllers/`.
    - Routes di `app/Modules/Academic/Interface/Routes/web.php` (total 22 route akademik aktif).
    - Teruji lewat [`tests/Feature/DailyOperationsControllersTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/DailyOperationsControllersTest.php) (5 tests, 20 assertions).
  - **S04-T06: Verifikasi Gate S04 & Stress Testing Skenario Benturan**
    - Uji benturan guru, benturan rombel, benturan ruang, uji audit koreksi presensi.
    - Seluruh test suite (82 tests, 353 assertions) lulus 100%.
- Tidak diubah:
  - Modul wali kelas, penilaian, atau administrasi (tahap berikutnya).

## Dampak teknis

- Modul/file yang disentuh:
  - Backend:
    - `app/Modules/Academic/Infrastructure/Database/Migrations/2026_10_25_200000_create_schedule_and_attendance_tables.php`
    - `app/Modules/Academic/Infrastructure/Models/Schedule.php`
    - `app/Modules/Academic/Infrastructure/Models/LessonSession.php`
    - `app/Modules/Academic/Infrastructure/Models/AttendanceRecord.php`
    - `app/Modules/Academic/Infrastructure/Models/TeachingJournal.php`
    - `app/Modules/Academic/Domain/Enums/ScheduleStatusEnum.php`
    - `app/Modules/Academic/Domain/Enums/AttendanceStatusEnum.php`
    - `app/Modules/Academic/Domain/Enums/AttendanceTypeEnum.php`
    - `app/Modules/Academic/Application/Services/ScheduleService.php`
    - `app/Modules/Academic/Application/Services/AttendanceService.php`
    - `app/Modules/Academic/Application/Services/JournalService.php`
    - `app/Modules/Academic/Application/Policies/SchedulePolicy.php`
    - `app/Modules/Academic/Application/Policies/AttendancePolicy.php`
    - `app/Modules/Academic/AcademicServiceProvider.php`
    - `app/Modules/Academic/Interface/Http/Controllers/ScheduleController.php`
    - `app/Modules/Academic/Interface/Http/Controllers/AttendanceController.php`
    - `app/Modules/Academic/Interface/Http/Controllers/JournalController.php`
    - `app/Modules/Academic/Interface/Routes/web.php`
  - Frontend:
    - `resources/js/Pages/Academic/Schedules/Index.vue`
    - `resources/js/Pages/Academic/Attendance/Index.vue`
    - `resources/js/Pages/Academic/Journals/Index.vue`
    - `resources/js/components/SidebarNavigation.vue`
  - Pengujian:
    - `tests/Feature/ScheduleConflictTest.php`
    - `tests/Feature/AttendanceAndJournalTest.php`
    - `tests/Feature/DailyOperationsControllersTest.php`
  - Dokumentasi:
    - `docs/TODO.md`
    - `docs/PROJECT_STATUS.md`
    - `docs/HANDOFF.md`
- Migrasi/data: Tabel `schedules`, `lesson_sessions`, `attendance_records`, `teaching_journals`.
- Keamanan/izin:
  - Server-side Default Deny di seluruh endpoint jadwal dan presensi.
  - Penolakan akses create jadwal untuk peran bukan pengelola kurikulum (siswa/wali murid 403).
  - Pengecekan sesi kepemilikan guru saat input absensi sesi.
  - Setiap koreksi status absensi tercatat di audit log.
- Keputusan atau asumsi:
  - Pembedaan presensi harian sekolah vs presensi per pertemuan didukung dalam satu skema yang dapat diagregasi bersama.

## Bukti

- Perintah tes dan hasil:
  - `composer test`: 82 passed, 353 assertions (3.78s) — 100% lulus (Unit, Feature, Migration, Authorization, Audit, Enrollment, Assignments, Import, Conflict Detection, Attendance, Journals, Boundary Architecture).
  - `composer lint`: passed (Pint test passed).
  - `npm run typecheck`: passed (`vue-tsc --noEmit` exit 0).
  - `npm run build`: passed (Vite production bundle dibangun tanpa error).
  - `python3 ~/.gemini/config/skills/docs-governance/scripts/audit-docs.py --root . --scope full`: passed (exit code 0).
- Langkah review manual:
  - Jalankan `php artisan test --filter=ScheduleConflictTest` untuk memverifikasi seluruh aturan benturan jadwal.
  - Jalankan `php artisan test --filter=AttendanceAndJournalTest` untuk memverifikasi pencatatan dan audit koreksi presensi.
  - Akses `/akademik/jadwal` untuk melihat grid jadwal mingguan dan mencoba menambahkan slot jadwal.
  - Akses `/akademik/presensi` untuk menguji alur satu klik "Tandai Semua Hadir" dan pengubahan status individual.
  - Akses `/akademik/jurnal` untuk melihat rekam jurnal mengajar dan snapshot presensi.

## Risiko dan pemulihan

- Risiko tersisa: Modul wali kelas (`Homeroom`: catatan siswa, observasi perilaku, kesiswaan) dan modul penilaian (`Assessment`) belum terpasang. Hal ini merupakan cakupan pada Tahap S05 dan S06.
- Rollback: `git revert HEAD` jika diperlukan pengembalian commit.
- Follow-up: Siap lanjut ke **Tahap S05: Ruang Kerja Wali Kelas & Kesiswaan (`app/Modules/Homeroom`)**.
