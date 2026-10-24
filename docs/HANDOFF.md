# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang diserahkan untuk review.

## Identitas

- Task/issue: Penyelesaian Penuh Tahap S02 (Akses & Tata Kelola Core: S02-T01 s/d S02-T06)
- Requirement ID: SS-F-001, AI_RULES.md (Aturan 2, 4, 6, 9), REQUIREMENTS.md (Manajemen Pengguna & Otorisasi Server-Side), ARCHITECTURE.md
- Branch/commit: main / commit berikutnya
- Status: Selesai & Terverifikasi (Gate S02 Passed)

## Ringkasan dan scope

- Perubahan:
  - **S02-T01: Migrasi Skema Basis Data Core**
    - Tabel `schools`, `roles`, `permissions`, `role_permissions`, `audit_events`, serta penambahan kolom pada `users` (`school_id`, `role_id`, `username`, `phone`, `is_active`).
    - Model Eloquent di `app/Modules/Core/Infrastructure/Models/` (`School`, `Role`, `Permission`, `AuditEvent`) dan integrasi helper relasi/role di `app/Models/User.php`.
    - Uji migrasi forward dan rollback bersih (`php artisan migrate:fresh` dan `php artisan migrate:rollback`).
  - **S02-T02: Otorisasi Server-Side Default Deny**
    - Enum domain `RoleEnum` dan `PermissionEnum` di `app/Modules/Core/Domain/`.
    - Policy server-side `UserPolicy` dan `SchoolPolicy` di `app/Modules/Core/Application/Policies/` didaftarkan di `CoreServiceProvider`.
    - Service aplikasi `UserService` di `app/Modules/Core/Application/Services/` yang mewajibkan pengecekan Gate eksplisit dan isolasi sekolah.
  - **S02-T03: Engine Audit Log Terpusat**
    - `App\Shared\Audit\AuditLogger` di `app/Shared/Audit/AuditLogger.php` menangkap seluruh mutasi material dan otomatis me-redact field sensitif secara rekursif (`password`, `token`, `secret`, dll.).
  - **S02-T04: Alur Autentikasi Pengguna & Sesi**
    - `AuthController` dan `LoginRequest`: mendukung login via email atau username, rate limiting ketat (maksimal 5 percobaan gagal per menit), penolakan akun inaktif (`is_active = false`), regenerasi session token pencegah fixation, proteksi CSRF, serta pencatatan audit log `core.auth.login` dan `core.auth.logout`.
    - Halaman UI Vue: `resources/js/Pages/Auth/Login.vue` dengan form reaktif dan error feedback inline.
  - **S02-T05: Antarmuka UI Manajemen Pengguna & Peran (Inertia + Vue)**
    - `UserController`: endpoint index (pencarian realtime, filter peran, paginasi 15 item), store, update, dan delete dengan validasi form dan audit log.
    - `SchoolController`: endpoint edit dan update identitas profil sekolah tunggal (`SS-D-001`).
    - Halaman UI Vue: `resources/js/Pages/Core/Users/Index.vue` (tabel interaktif, modal buat/edit, toggle status aktif, proteksi larangan hapus diri sendiri) dan `resources/js/Pages/Core/School/Edit.vue` (profil sekolah).
    - Navigasi & App Layout terintegrasi: `SidebarNavigation.vue` dan `TopNavbar.vue` terhubung dengan data autentikasi dan logout action.
  - **S02-T06: Verifikasi Gate S02 & Matriks Akses Multi-Peran**
    - Matriks pengujian otorisasi server-side menyeluruh di `tests/Feature/RoleMatrixAccessTest.php` untuk 6 peran: `admin`, `operator`, `principal`, `teacher`, `homeroom`, `student`.
    - Pengujian isolasi lintas sekolah (*school boundary isolation*) dan blokir akun nonaktif.
  - Dokumentasi diperbarui: `docs/TODO.md` dan `docs/PROJECT_STATUS.md`.
- Tidak diubah:
  - Modul domain akademik, presensi, rapor, atau integrasi (akan dikerjakan pada tahap berikutnya).

## Dampak teknis

- Modul/file yang disentuh:
  - Backend:
    - `app/Modules/Core/Interface/Http/Controllers/AuthController.php`
    - `app/Modules/Core/Interface/Http/Controllers/UserController.php`
    - `app/Modules/Core/Interface/Http/Controllers/SchoolController.php`
    - `app/Modules/Core/Interface/Http/Requests/LoginRequest.php`
    - `app/Modules/Core/Interface/Routes/web.php`
    - `app/Http/Middleware/HandleInertiaRequests.php`
    - `routes/web.php`
  - Frontend:
    - `resources/js/Pages/Auth/Login.vue`
    - `resources/js/Pages/Core/Users/Index.vue`
    - `resources/js/Pages/Core/School/Edit.vue`
    - `resources/js/components/SidebarNavigation.vue`
    - `resources/js/components/TopNavbar.vue`
    - `resources/js/types/index.d.ts`
  - Pengujian:
    - `tests/Feature/AuthenticationTest.php`
    - `tests/Feature/UserManagementTest.php`
    - `tests/Feature/RoleMatrixAccessTest.php`
  - Dokumentasi:
    - `docs/TODO.md`
    - `docs/PROJECT_STATUS.md`
    - `docs/HANDOFF.md`
- Migrasi/data: Menggunakan skema `schools`, `roles`, `permissions`, `role_permissions`, `audit_events`, `users`.
- Keamanan/izin:
  - Server-side Default Deny di seluruh endpoint.
  - Rate limiting autentikasi (5 attempts/min).
  - Sanitasi rekursif data sensitif pada audit logs.
  - Proteksi anti-self-deletion bagi pengguna yang sedang aktif login.
  - Larangan akses lintas sekolah (*multi-school tenant isolation*).
- Keputusan atau asumsi:
  - Mengikuti `SS-D-001` (instalasi sekolah tunggal), konfigurasi profil sekolah diakses melalui satu record utama di tabel `schools`.

## Bukti

- Perintah tes dan hasil:
  - `composer test`: 37 passed, 195 assertions (2.74s) — 100% lulus (Unit, Feature, Architecture, Migration, Authorization, Audit, Auth, User Management, Role Matrix).
  - `composer lint`: passed (Pint test passed).
  - `npm run typecheck`: passed (`vue-tsc --noEmit` exit 0).
  - `npm run build`: passed (Vite production bundle dibangun tanpa error).
  - `python3 ~/.gemini/config/skills/docs-governance/scripts/audit-docs.py --root . --scope full`: passed (exit code 0).
- Langkah review manual:
  - Jalankan `php artisan test` untuk menjalankan keseluruhan 37 automated tests.
  - Akses route `/login` untuk memvalidasi form login, submit kredensial admin dan coba login dengan akun inaktif atau rate limit 5x.
  - Akses `/pengaturan/pengguna` untuk melihat daftar pengguna, pencarian, dan pembuatan user.
  - Akses `/pengaturan/sekolah` untuk menguji pembaruan identitas sekolah.

## Risiko dan pemulihan

- Risiko tersisa: Modul akademik belum terpasang skema tabelnya (`academic_years`, `terms`, `students`, `teachers`, `class_groups`). Hal ini akan ditangani pada Tahap S03.
- Rollback: `git revert HEAD` jika diperlukan pengembalian commit.
- Follow-up: Siap lanjut ke **Tahap S03: Data Inti Akademik & Import Engine (`app/Modules/Academic`)**.
