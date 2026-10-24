# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang diserahkan untuk review.

## Identitas

- Task/issue: S02-T02 Domain & Application Service Core (Otorisasi Server-Side & Default Deny Policy)
- Requirement ID: SS-F-001, AI_RULES.md (Aturan 6: Otorisasi Server-Side), ARCHITECTURE.md (Keamanan & Operasi: Default Deny)
- Branch/commit: main / commit berikutnya
- Status: Siap direview

## Ringkasan dan scope

- Perubahan:
  - Membuat Enum domain di `app/Modules/Core/Domain/`:
    - `RoleEnum`: peran sistem (`admin`, `operator`, `principal`, `teacher`, `homeroom`, `counselor`, `librarian`, `student`, `parent`) dengan label deskriptif.
    - `PermissionEnum`: izin sistem granular per modul.
  - Mengimplementasikan Authorization Policy berprinsip *Default Deny* di `app/Modules/Core/Application/Policies/`:
    - `UserPolicy`:
      - Penolakan akses jika pengguna tidak aktif (`!is_active`).
      - Isolasi sekolah: Pengguna/operator dari Sekolah A dilarang memeriksa, memodifikasi, atau menghapus pengguna di Sekolah B.
      - Perlindungan akun: Larangan admin menghapus akunnya sendiri (pencegahan lockout).
      - Self-viewing & self-profile update diperbolehkan untuk pengguna bersangkutan.
    - `SchoolPolicy`:
      - Isolasi profil sekolah berbasis `school_id`.
  - Mendaftarkan kebijakan otorisasi pada container via `App\Modules\Core\CoreServiceProvider`.
  - Mengimplementasikan `App\Modules\Core\Application\Services\UserService` dengan penegakan otorisasi server-side (`Gate::forUser($actor)->denies(...)` melempar `AuthorizationException`).
  - Menulis test suite komprehensif `tests/Feature/CoreAuthorizationTest.php`.
  - Memperbarui status di `docs/TODO.md`.
- Tidak diubah:
  - Struktur modul di luar Core.

## Dampak teknis

- Modul/file yang disentuh:
  - `app/Modules/Core/Domain/RoleEnum.php`
  - `app/Modules/Core/Domain/PermissionEnum.php`
  - `app/Modules/Core/Application/Policies/UserPolicy.php`
  - `app/Modules/Core/Application/Policies/SchoolPolicy.php`
  - `app/Modules/Core/Application/Services/UserService.php`
  - `app/Modules/Core/CoreServiceProvider.php`
  - `tests/Feature/CoreAuthorizationTest.php`
  - `docs/TODO.md`
- Migrasi/data: Tidak ada migrasi baru.
- Keamanan/izin: Otorisasi server-side aktif secara konsisten; dilarang keras mengandalkan penyembunyian UI.
- Keputusan atau asumsi:
  - Aturan Default Deny: Semua kemampuan mengembalikan false secara default kecuali kondisi izin eksplisit terpenuhi.
  - Admin memiliki blanket bypass untuk fungsi operasional tetapi tetap dilarang menghapus diri sendiri dan dibatasi scope sekolah.

## Bukti

- Perintah tes dan hasil:
  - `composer test`: 16 passed, 89 assertions (958ms) — seluruh unit, feature, migration, authorization, dan architecture tests lulus 100%.
  - `composer lint`: passed (Pint test passed).
  - `npm run typecheck`: passed.
  - `python3 ~/.gemini/config/skills/docs-governance/scripts/audit-docs.py --root . --scope full`: passed (exit code 0).
- Langkah review manual:
  - Jalankan `php artisan test --filter=CoreAuthorizationTest`

## Risiko dan pemulihan

- Risiko tersisa: Audit log engine belum mengotomatiskan penangkapan mutasi user (akan dikerjakan pada S02-T03).
- Rollback: `git revert HEAD`.
- Follow-up: Lanjut ke S02-T03 (Engine Audit Log Terpusat di `app/Shared/Audit`).
