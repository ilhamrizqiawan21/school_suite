# Project Status — School Suite

Terakhir diperbarui: 25 Oktober 2026

## Keadaan saat ini

- Fase: S06 Penilaian, Rapor & Formula Berversi selesai; siap masuk ke S07 Persiapan Pilot, Backup/Restore & Observability.
- Kesiapan:
  - S01 Fondasi: Bootstrap Laravel 13, Inertia.js v3, Vue 3, Vite v8, Tailwind CSS v4, TypeScript, struktur 9 modul monolit modular, layout shell dasar, dan boundary test arsitektur selesai.
  - S02 Akses: Skema basis data Core, otorisasi server-side Default Deny, engine audit log terpusat, autentikasi rate-limited, UI kelola user & profil sekolah, dan matriks akses multi-peran selesai.
  - S03 Data Inti Akademik: Skema basis data Akademik, histori enrollment tidak berubah (`SS-D-004`), penugasan guru & wali kelas, engine impor massal CSV dengan validasi duplikasi NIS/NISN, serta antarmuka UI data inti selesai.
  - S04 Operasi Akademik Harian:
    - Skema basis data `schedules`, `lesson_sessions`, `attendance_records`, dan `teaching_journals`.
    - Engine validasi benturan jadwal (`ScheduleService`) mendeteksi benturan guru, benturan rombel, dan benturan ruang pada slot waktu overlap, serta alur publikasi draf (`ScheduleConflictTest`).
    - Service presensi massal (`AttendanceService`) dan jurnal pembelajaran (`JournalService`) dengan pencatatan audit log otomatis pada setiap koreksi absensi (`SS-F-005`) (`AttendanceAndJournalTest`).
    - Antarmuka UI jadwal mingguan (`Schedules/Index.vue`), presensi cepat dengan toggle satu klik (`Attendance/Index.vue`), dan riwayat jurnal harian (`Journals/Index.vue`) terintegrasi (`DailyOperationsControllersTest`).
  - S05 Ruang Kerja Wali Kelas & Kesiswaan:
    - Skema basis data `student_notes`, `student_incidents`, `student_achievements`, `assistance_records`, dan `attendance_dispensations`.
    - Isolasi akses data sensitif (`confidential`, `internal`, `public`) sesuai `SS-F-006` dengan perlindungan server-side policy (`StudentNotePolicy`) dan filter ekspor rapor (`StudentObservationService`, `HomeroomIsolationTest`).
    - Service rekapitulasi kehadiran rombel dan pendeteksian Early Warning ketidakhadiran (Alpa >= 3 hari atau kehadiran < 85%) (`HomeroomAttendanceService`, `HomeroomAttendanceSummaryTest`).
    - Monitoring surat dokter, dispensasi lomba, dan izin keluarga siswa (`DispensationService`).
    - Decoupling murni lintas modul via `AcademicQueryService` tanpa model/table coupling terlarang ke modul internal lain (`ModularBoundaryTest`).
    - Antarmuka UI Ruang Kerja Wali Kelas: Dashboard perwalian (`Homeroom/Dashboard/Index.vue`), Catatan Observasi (`Homeroom/Observations/Index.vue`), dan Kedisiplinan/Prestasi/Bantuan/Dispensasi (`Homeroom/Discipline/Index.vue`) terintegrasi (`HomeroomControllersTest`).
  - S06 Penilaian, Rapor & Formula Berversi:
    - Skema basis data `assessment_schemes`, `assessment_components`, `scores`, `remedial_records`, dan `report_snapshots`.
    - Engine formula penilaian Domain layer (`GradeCalculationEngine`): pembobotan dinamis, pembulatan baku (`half_up`, `floor`, `ceil`), penanganan nilai batas (0, 100), nilai kosong/null, dan logika remedial ($\min(\text{remedial}, \text{KKTP})$) teruji table-driven 100% (`GradeCalculationEngineTest`).
    - Immutable Report Snapshot Engine (`ReportPublishingService`, `SS-D-005`): penguncian status `published` dengan checksum SHA-256 membuktikan bit-for-bit kebal terhadap mutasi tabel nilai mentah pasca-terbit (`ReportSnapshotImmutabilityTest`).
    - Antarmuka UI (Vue 3 + Inertia): Grid Input Nilai Guru spreadsheet-like (`Assessment/Scores/InputGrid.vue`), Manajemen Skema Penilaian Berversi (`Assessment/Schemes/Index.vue`), Penerbitan Rapor & Snapshot (`Assessment/Reports/Index.vue`), Lembar Cetak Rapor (`Assessment/Reports/Show.vue`), dan Buku Ledger Nilai Rombel (`Assessment/Reports/Ledger.vue`).
    - Kebijakan Otorisasi (`AssessmentPolicy`): siswa dibatasi hanya melihat rapor pribadi; guru, wali kelas, dan kurikulum memiliki kontrol bertahap (`AssessmentControllersTest`).
  - Seluruh CI dan test suite (123 tests, 587 assertions, Unit, Feature, Architecture, Lint `pint`, Typecheck `vue-tsc`, Build `vite`, Docs Governance) hijau 100%.
- Langkah berikut: Tahap S07 Persiapan Pilot, Backup/Restore & Observability (`SS-F-008`, `SS-F-009`: drill restore basis data terpisah tanpa kehilangan data, backup otomasi terenkripsi, health check, logging rotasi, dan panduan runbook).

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
