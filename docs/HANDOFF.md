# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang diserahkan untuk review.

## Identitas

- Task/issue: Penyelesaian Penuh Tahap S05 (Ruang Kerja Wali Kelas & Kesiswaan: S05-T01 s/d S05-T05)
- Requirement ID: SS-F-006 (Catatan Siswa, Prestasi, Izin, dan Tindak Lanjut), AI_RULES.md (Aturan 4 & 6: Privasi data siswa & isolasi batas modul), DESIGN.md, ERD.md, ARCHITECTURE.md
- Branch/commit: main / commit berikutnya
- Status: Selesai & Terverifikasi (Gate S05 Passed)

## Ringkasan dan scope

- Perubahan:
  - **S05-T01: Migrasi Skema Homeroom & Catatan Kesiswaan**
    - Tabel `student_notes` (catatan perkembangan, sikap positif, pembinaan, konseling).
    - Tabel `student_incidents` (pelanggaran tata tertib, kategori, bobot poin pelanggaran, dan tindak lanjut).
    - Tabel `student_achievements` (prestasi akademik, olahraga, seni, keagamaan, tingkat sekolah s/d internasional).
    - Tabel `assistance_records` (tindak lanjut khusus, kunjungan rumah / home visit, konseling, bantuan siswa).
    - Tabel `attendance_dispensations` (surat keterangan dokter, dispensasi lomba, izin keluarga).
    - Enums domain di `app/Modules/Homeroom/Domain/Enums/`: `NoteAccessLevelEnum` (`public`, `internal`, `confidential`), `NoteCategoryEnum` (`academic`, `behavior`, `counseling`, `general`), `AchievementLevelEnum` (`school`, `district`, `city`, `province`, `national`, `international`), `DispensationTypeEnum`, dan `DispensationStatusEnum`.
    - 5 Model Eloquent di `app/Modules/Homeroom/Infrastructure/Models/` (`StudentNote`, `StudentIncident`, `StudentAchievement`, `AssistanceRecord`, `AttendanceDispensation`).
  - **S05-T02: Policy Otorisasi Catatan Siswa Sensitif (`SS-F-006`)**
    - Service `StudentObservationService` di `app/Modules/Homeroom/Application/Services/`:
      - Penegakan isolasi privasi: catatan berlabel `confidential` hanya dapat diakses oleh pembuat catatan, wali kelas siswa bersangkutan, guru BK, dan Kepala Sekolah.
      - Pengecekan sanitasi ekspor: method `getNotesForReportExport` secara ketat hanya mengambil catatan berstatus `public` untuk dimasukkan ke dalam rapor atau ledger publik.
    - Policy `StudentNotePolicy` di `app/Modules/Homeroom/Application/Policies/` didaftarkan melalui `HomeroomServiceProvider`.
    - Teruji lewat [`tests/Feature/HomeroomIsolationTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/HomeroomIsolationTest.php) (2 tests, 10 assertions).
  - **S05-T03: Rekap Kesiapan Siswa Perwalian & Early Warning Presensi**
    - Service `HomeroomAttendanceService` dan `DispensationService` di `app/Modules/Homeroom/Application/Services/`:
      - Mengagregasikan status presensi (H/S/I/A) dan menghitung persentase kehadiran rombel per semester aktif.
      - Mendeteksi flag *Early Warning* (`needs_attention = true`) secara otomatis jika siswa memiliki Alpa >= 3 hari atau tingkat kehadiran di bawah 85%.
      - Monitoring status surat dispensasi aktif siswa pada periode semester berjalan.
    - Decoupling murni: Service `AcademicQueryService` di `app/Modules/Academic/Application/Services/AcademicQueryService.php` menjembatani akses data rombel, guru, enrollment, dan presensi tanpa direct model/database coupling dari Homeroom ke Academic.
    - Teruji lewat [`tests/Feature/HomeroomAttendanceSummaryTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/HomeroomAttendanceSummaryTest.php) (1 test, 10 assertions).
  - **S05-T04: UI Ruang Kerja Wali Kelas (Inertia + Vue)**
    - Dashboard Wali Kelas (`resources/js/Pages/Homeroom/Dashboard/Index.vue`): ringkasan kelas perwalian, kartu indikator Early Warning ketidakhadiran, ringkasan statistik insiden/prestasi/catatan/dispensasi aktif.
    - Catatan Observasi (`resources/js/Pages/Homeroom/Observations/Index.vue`): filter siswa & kelas, kartu timeline observasi, badge visibilitas privasi (`Publik`, `Internal`, `Rahasia`), modal form pembuatan catatan baru.
    - Kedisiplinan & Prestasi (`resources/js/Pages/Homeroom/Discipline/Index.vue`): tab rekam pelanggaran & poin tata tertib, tab rekam prestasi, tab tindak lanjut & kunjungan rumah, serta tab surat izin/dispensasi.
    - Menu samping `SidebarNavigation.vue` terhubung ke `/wali-kelas`.
  - **S05-T05: Controller, Routes, & Verifikasi Gate S05**
    - Controller `HomeroomDashboardController`, `StudentObservationController`, `StudentDisciplineController` di `app/Modules/Homeroom/Interface/Http/Controllers/`.
    - Routes web di `app/Modules/Homeroom/Interface/Routes/web.php` (10 endpoint terproteksi autentikasi dan policy).
    - Enums izin kesiswaan ditambahkan ke `app/Modules/Core/Domain/PermissionEnum.php`: `HOMEROOM_NOTES_VIEW`, `HOMEROOM_NOTES_MANAGE`, `HOMEROOM_DISCIPLINE_MANAGE`.
    - Teruji lewat [`tests/Feature/HomeroomControllersTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/HomeroomControllersTest.php) (12 tests, 63 assertions).
    - Boundary test arsitektur `ModularBoundaryTest` lulus tanpa pelanggaran impor silang modular.
- Tidak diubah:
  - Modul penilaian (`Assessment`), administrasi, atau SPMB (tahap berikutnya).

## Dampak teknis

- Modul/file yang disentuh:
  - Backend:
    - `app/Modules/Academic/Application/Services/AcademicQueryService.php`
    - `app/Modules/Core/Domain/PermissionEnum.php`
    - `app/Modules/Homeroom/Application/Policies/StudentNotePolicy.php`
    - `app/Modules/Homeroom/Application/Services/DispensationService.php`
    - `app/Modules/Homeroom/Application/Services/HomeroomAttendanceService.php`
    - `app/Modules/Homeroom/Application/Services/StudentDisciplineService.php`
    - `app/Modules/Homeroom/Application/Services/StudentObservationService.php`
    - `app/Modules/Homeroom/Domain/Enums/AchievementLevelEnum.php`
    - `app/Modules/Homeroom/Domain/Enums/DispensationStatusEnum.php`
    - `app/Modules/Homeroom/Domain/Enums/DispensationTypeEnum.php`
    - `app/Modules/Homeroom/Domain/Enums/NoteAccessLevelEnum.php`
    - `app/Modules/Homeroom/Domain/Enums/NoteCategoryEnum.php`
    - `app/Modules/Homeroom/HomeroomServiceProvider.php`
    - `app/Modules/Homeroom/Infrastructure/Database/Migrations/2026_10_25_300000_create_homeroom_tables.php`
    - `app/Modules/Homeroom/Infrastructure/Database/Migrations/2026_10_25_300001_create_attendance_dispensations_table.php`
    - `app/Modules/Homeroom/Infrastructure/Models/AssistanceRecord.php`
    - `app/Modules/Homeroom/Infrastructure/Models/AttendanceDispensation.php`
    - `app/Modules/Homeroom/Infrastructure/Models/StudentAchievement.php`
    - `app/Modules/Homeroom/Infrastructure/Models/StudentIncident.php`
    - `app/Modules/Homeroom/Infrastructure/Models/StudentNote.php`
    - `app/Modules/Homeroom/Interface/Http/Controllers/HomeroomDashboardController.php`
    - `app/Modules/Homeroom/Interface/Http/Controllers/StudentDisciplineController.php`
    - `app/Modules/Homeroom/Interface/Http/Controllers/StudentObservationController.php`
    - `app/Modules/Homeroom/Interface/Routes/web.php`
  - Frontend:
    - `resources/js/Pages/Homeroom/Dashboard/Index.vue`
    - `resources/js/Pages/Homeroom/Observations/Index.vue`
    - `resources/js/Pages/Homeroom/Discipline/Index.vue`
    - `resources/js/components/SidebarNavigation.vue`
  - Pengujian:
    - `tests/Feature/HomeroomIsolationTest.php`
    - `tests/Feature/HomeroomAttendanceSummaryTest.php`
    - `tests/Feature/HomeroomControllersTest.php`
  - Dokumentasi:
    - `docs/TODO.md`
    - `docs/PROJECT_STATUS.md`
    - `docs/HANDOFF.md`
- Migrasi/data: Tabel `student_notes`, `student_incidents`, `student_achievements`, `assistance_records`, `attendance_dispensations`.
- Keamanan/izin:
  - Default Deny server-side di seluruh endpoint kesiswaan dan catatan observasi.
  - Catatan rahasia/BK (`confidential`) disaring ketat di Application layer dan dilarang muncul pada ekspor rapor umum.
  - Pengguna tanpa peran guru/wali kelas/BK (seperti siswa atau orang tua) diblokir dari endpoint observasi (403 Forbidden).
  - Penghapusan catatan dibatasi hanya untuk pembuat catatan asli (*author*) atau administrator.
- Keputusan atau asumsi:
  - Antarmuka lintas modul dari `Homeroom` ke `Academic` menggunakan `AcademicQueryService` untuk mencegah pelanggaran batasan arsitektur modular monolith.

## Bukti

- Perintah tes dan hasil:
  - `composer test`: 97 passed, 436 assertions (4.3s) — 100% lulus (Unit, Feature, Migration, Authorization, Audit, Enrollment, Assignments, Import, Conflict Detection, Attendance, Journals, Homeroom Isolation, Early Warning, Dispensations, Homeroom Controllers, Boundary Architecture).
  - `composer lint`: passed (Pint test passed).
  - `npm run typecheck`: passed (`vue-tsc --noEmit` exit 0).
  - `npm run build`: passed (Vite production bundle dibangun tanpa error).
  - `python3 ~/.gemini/config/skills/docs-governance/scripts/audit-docs.py --root . --scope full`: passed (exit code 0).
- Langkah review manual:
  - Jalankan `php artisan test --filter=HomeroomIsolationTest` untuk membuktikan catatan berkategori `confidential` tidak bocor ke guru lain dan ekspor rapor.
  - Jalankan `php artisan test --filter=HomeroomAttendanceSummaryTest` untuk memverifikasi logika deteksi Early Warning ketidakhadiran (Alpa >= 3 atau kehadiran < 85%).
  - Jalankan `php artisan test --filter=HomeroomControllersTest` untuk memverifikasi seluruh HTTP controllers, validasi input, pencatatan dispensasi, dan otorisasi.
  - Akses `/wali-kelas` untuk melihat tampilan dashboard ringkasan rombel, kartu peringatan dini presensi, dan statistik dispensasi aktif.
  - Akses `/wali-kelas/catatan` untuk menguji input catatan observasi siswa dengan opsi tingkat privasi.
  - Akses `/wali-kelas/kedisiplinan` untuk melihat tab pencatatan pelanggaran, prestasi, bantuan konseling, dan surat dispensasi/izin.

## Risiko dan pemulihan

- Risiko tersisa: Modul penilaian dan rapor (`Assessment`: formula nilai berversi, bobot komponen, remedial, dan immutable snapshot rapor `SS-D-005`) belum diimplementasikan. Hal ini merupakan cakupan pada Tahap S06.
- Rollback: `git revert HEAD` jika diperlukan pengembalian commit.
- Follow-up: Siap lanjut ke **Tahap S06: Manajemen Penilaian & Rapor (`app/Modules/Assessment`)**.
