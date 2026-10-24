# Project Status — School Suite

Terakhir diperbarui: 24 Oktober 2026

## Keadaan saat ini

- Fase: S01 Fondasi selesai; siap masuk ke S02 Akses.
- Kesiapan:
  - Bootstrap Laravel 13, Inertia.js v3, Vue 3, Vite v8, Tailwind CSS v4, dan TypeScript selesai.
  - Struktur 9 modul monolit modular (`app/Modules/*`) dan layanan bersama (`app/Shared/*`) aktif dengan auto-discovery.
  - Architecture test otomatis aktif mengunci larangan coupling langsung database/model lintas modul.
  - Shell aplikasi dasar dengan indikator periode aktif persisten (`resources/js/layouts/AppLayout.vue`) selesai.
  - Seluruh CI dan test suite (Unit, Feature, Architecture, Lint, Typecheck, Build, Docs Governance) hijau 100%.
- Langkah berikut: Tahap S02 Akses (Core: akun pengguna, role matriks, permission, dan audit log).

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

