# Project Status — School Suite

Terakhir diperbarui: 25 Oktober 2026

## Keadaan saat ini

- Fase: S02 Akses & Tata Kelola Core selesai; siap masuk ke S03 Data Inti Akademik.
- Kesiapan:
  - S01 Fondasi: Bootstrap Laravel 13, Inertia.js v3, Vue 3, Vite v8, Tailwind CSS v4, TypeScript, struktur 9 modul monolit modular, layout shell dasar, dan boundary test arsitektur selesai.
  - S02 Akses:
    - Skema basis data Core (`schools`, `roles`, `permissions`, `role_permissions`, `audit_events`, ekstensi `users`) dengan migrasi dan rollback bersih (`CoreMigrationTest`).
    - Otorisasi server-side Default Deny (`UserPolicy`, `SchoolPolicy`, `RoleEnum`, `PermissionEnum`, `UserService`) dengan isolasi akses (`CoreAuthorizationTest`).
    - Engine audit log terpusat (`App\Shared\Audit\AuditLogger`) dengan sanitasi rekursif secret otomatis (`AuditLoggerTest`).
    - Alur autentikasi pengguna (email/username), rate limiting 5x, penolakan akun inaktif, proteksi session CSRF/regeneration, dan login UI (`AuthenticationTest`).
    - Antarmuka manajemen pengguna (`resources/js/Pages/Core/Users/Index.vue`) dan profil sekolah (`resources/js/Pages/Core/School/Edit.vue`) dengan paginasi, pencarian realtime, filter peran, dan proteksi hapus diri sendiri (`UserManagementTest`).
    - Verifikasi gate akses multi-peran (`RoleMatrixAccessTest`) menguji 6 peran (`admin`, `operator`, `principal`, `teacher`, `homeroom`, `student`).
  - Seluruh CI dan test suite (37 tests, 195 assertions, Unit, Feature, Architecture, Lint, Typecheck, Build, Docs Governance) hijau 100%.
- Langkah berikut: Tahap S03 Data Inti Akademik & Import Engine (`app/Modules/Academic`).

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

