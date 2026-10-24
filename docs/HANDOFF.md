# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang sedang direview.

## Identitas

- Task/issue: S01-T04 Testing Toolchain & Architecture Boundary Test
- Requirement ID: S01 Fondasi, ARCHITECTURE.md, TESTS.md (SS-T-ACCESS, SS-T-DATA), REGRESSION.md
- Branch/commit: main / commit berikutnya
- Status: Siap direview

## Ringkasan dan scope

- Perubahan:
  - Mengonfigurasi testsuite di `phpunit.xml`: `Unit`, `Feature`, `Architecture`, `Contracts`.
  - Mengimplementasikan `tests/Architecture/ModularBoundaryTest.php` menggunakan PhpParser AST inspection untuk membuktikan dan mengunci aturan batas modular monolith:
    1. Modul dilarang mengimpor `Infrastructure` (Model/Repository/Migration) milik modul lain secara langsung.
    2. Lapisan `Domain` harus murni PHP dan dilarang mengimpor artefak HTTP (`Illuminate\Http`, `Inertia`).
    3. Controller harus berada di lapisan `Interface/Http/Controllers`.
    4. Scanner boundary terbukti dapat mendeteksi simulasi pelanggaran impor lintas modul.
  - Menambahkan skrip CLI di `composer.json`: `composer test`, `composer lint`, `composer format`.
  - Format kode otomatis melalui `composer format` (`pint`).
  - Pembaruan status di `docs/TODO.md`.
- Tidak diubah:
  - Struktur modul dan route yang sudah dibentuk pada S01-T03.

## Dampak teknis

- Modul/file:
  - `phpunit.xml`
  - `composer.json`
  - `tests/Architecture/ModularBoundaryTest.php`
  - `app/Shared/Providers/ModularServiceProvider.php`
  - `docs/TODO.md`
- Migrasi/data: Tidak ada migrasi database.
- Keamanan/izin: Mencegah bypass isolasi data antar-modul sejak level statis/AST.
- Keputusan atau asumsi:
  - Menggunakan PhpParser bawaan framework untuk architecture testing yang ringan, cepat (<500ms), dan bebas konflik versi dependency.

## Bukti

- Perintah tes dan hasil:
  - `composer test`: 8 passed, 38 assertions (641ms).
  - `composer lint`: passed (Pint test passed).
  - `npm run typecheck`: passed.
  - `npm run build`: passed.
  - `python3 ~/.gemini/config/skills/docs-governance/scripts/audit-docs.py --root . --scope full`: passed (exit code 0).
- Langkah review manual:
  - Jalankan `composer test` untuk melihat seluruh testsuite termasuk Architecture test.
  - Jalankan `composer lint` untuk melihat kepatuhan format kode.

## Risiko dan pemulihan

- Risiko tersisa: Halaman shell navigasi dan state periodik UI dasar belum dibangun (akan dikerjakan pada S01-T05).
- Rollback: `git revert HEAD`.
- Follow-up: Lanjut ke S01-T05 (Base Application Shell & Navigation Layout UX Base).
