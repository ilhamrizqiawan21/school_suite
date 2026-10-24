# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang diserahkan untuk review.

## Identitas

- Task/issue: S02-T01 Migrasi Skema Basis Data Core
- Requirement ID: SS-F-001, SS-D-001 (Satu sekolah per instalasi), ARCHITECTURE.md (Modul Core), AI_RULES.md
- Branch/commit: main / commit berikutnya
- Status: Siap direview

## Ringkasan dan scope

- Perubahan:
  - Membuat migrasi basis data Core di `app/Modules/Core/Infrastructure/Database/Migrations/2026_10_24_100000_create_core_tables.php`:
    - Tabel `schools`: profil sekolah tunggal (NPSN, nama, jenjang, alamat, kontak, logo, settings).
    - Tabel `roles`: peran sistem & kustom (Admin, TU, Kepala Sekolah, Guru, Wali Kelas, BK, Pustakawan, Siswa, Wali Murid).
    - Tabel `permissions`: izin per modul (`Core`, `Academic`, `Assessment`, dll.).
    - Tabel pivot `role_permissions`.
    - Ekstensi tabel `users`: `school_id`, `role_id`, `username`, `phone`, `is_active`.
    - Tabel `audit_events`: rekam jejak aktivitas pengguna, target, nilai sebelum/sesudah, IP, dan user agent.
  - Membuat model Eloquent di `app/Modules/Core/Infrastructure/Models/`: `School`, `Role`, `Permission`, `AuditEvent`.
  - Memperbarui `app/Models/User.php` dengan relasi `school()`, `role()`, serta helper `hasRole()` dan `hasPermission()`.
  - Menulis feature test `tests/Feature/CoreMigrationTest.php` untuk menguji eksistensi tabel, kolom, relasi, otorisasi peran, dan pencatatan audit.
  - Memverifikasi kemampuan migrasi maju (`migrate`) dan mundur (`migrate:rollback`) secara bersih.
  - Memperbarui status di `docs/TODO.md`.
- Tidak diubah:
  - Struktur modul di luar Core.

## Dampak teknis

- Modul/file yang disentuh:
  - `app/Modules/Core/Infrastructure/Database/Migrations/2026_10_24_100000_create_core_tables.php`
  - `app/Modules/Core/Infrastructure/Models/School.php`
  - `app/Modules/Core/Infrastructure/Models/Role.php`
  - `app/Modules/Core/Infrastructure/Models/Permission.php`
  - `app/Modules/Core/Infrastructure/Models/AuditEvent.php`
  - `app/Models/User.php`
  - `tests/Feature/CoreMigrationTest.php`
  - `docs/TODO.md`
- Migrasi/data: Migrasi baru Core berhasil dieksekusi dan diverifikasi.
- Keamanan/izin: Mendukung model RBAC (Role-Based Access Control) multi-peran dan pencatatan audit event.
- Keputusan atau asumsi:
  - Role `admin` memiliki blanket permission (akses menyeluruh).
  - Satu instalasi sekolah diikat via relasi `school_id` pada entitas user.

## Bukti

- Perintah tes dan hasil:
  - `composer test`: 10 passed, 68 assertions (1085ms) — mencakup `CoreMigrationTest`.
  - `composer lint`: passed (Pint test passed).
  - `npm run typecheck`: passed.
  - `python3 ~/.gemini/config/skills/docs-governance/scripts/audit-docs.py --root . --scope full`: passed (exit code 0).
- Langkah review manual:
  - Jalankan `php artisan test --filter=CoreMigrationTest`
  - Jalankan `php artisan migrate:status`

## Risiko dan pemulihan

- Risiko tersisa: Service policy otorisasi server-side dan alur autentikasi UI belum diimplementasikan (akan dikerjakan pada S02-T02 hingga S02-T05).
- Rollback: `git revert HEAD` dan `php artisan migrate:rollback --step=1`.
- Follow-up: Lanjut ke S02-T02 (Domain & Application Service Core / Otorisasi Server-Side).
