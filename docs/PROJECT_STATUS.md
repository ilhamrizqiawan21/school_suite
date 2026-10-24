# Project Status — School Suite

Terakhir diperbarui: 25 Oktober 2026

## Keadaan saat ini

- Fase: S04 Operasi Akademik Harian & Jadwal selesai; siap masuk ke S05 Ruang Kerja Wali Kelas & Kesiswaan.
- Kesiapan:
  - S01 Fondasi: Bootstrap Laravel 13, Inertia.js v3, Vue 3, Vite v8, Tailwind CSS v4, TypeScript, struktur 9 modul monolit modular, layout shell dasar, dan boundary test arsitektur selesai.
  - S02 Akses: Skema basis data Core, otorisasi server-side Default Deny, engine audit log terpusat, autentikasi rate-limited, UI kelola user & profil sekolah, dan matriks akses multi-peran selesai.
  - S03 Data Inti Akademik: Skema basis data Akademik, histori enrollment tidak berubah (`SS-D-004`), penugasan guru & wali kelas, engine impor massal CSV dengan validasi duplikasi NIS/NISN, serta antarmuka UI data inti selesai.
  - S04 Operasi Akademik Harian & Jadwal:
    - Skema basis data `schedules`, `lesson_sessions`, `attendance_records`, dan `teaching_journals`.
    - Engine validasi benturan jadwal (`ScheduleService`) mendeteksi benturan guru, benturan rombel, dan benturan ruang pada slot waktu overlap, serta alur publikasi draf (`ScheduleConflictTest`).
    - Service presensi massal (`AttendanceService`) dan jurnal pembelajaran (`JournalService`) dengan pencatatan audit log otomatis pada setiap koreksi absensi (`SS-F-005`) (`AttendanceAndJournalTest`).
    - Antarmuka UI jadwal mingguan (`Schedules/Index.vue`), presensi cepat dengan toggle satu klik (`Attendance/Index.vue`), dan riwayat jurnal harian (`Journals/Index.vue`) terintegrasi (`DailyOperationsControllersTest`).
  - Seluruh CI dan test suite (82 tests, 353 assertions, Unit, Feature, Architecture, Lint, Typecheck, Build, Docs Governance) hijau 100%.
- Langkah berikut: Tahap S05 Ruang Kerja Wali Kelas & Kesiswaan (`app/Modules/Homeroom`: catatan siswa, observasi perilaku, presensi wali kelas, rekapitulasi ketidakhadiran).

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

