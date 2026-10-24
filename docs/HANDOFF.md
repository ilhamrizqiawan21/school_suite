# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang sedang direview.

## Identitas

- Task/issue: Reorganisasi dokumentasi ke direktori `docs/` dan penelaahan project School Suite
- Requirement ID: Tata kelola dokumentasi & kesiapan arsitektur awal
- Branch/commit: -
- Status: Siap direview

## Ringkasan dan scope

- Perubahan:
  - Memindahkan seluruh dokumen spesifikasi, arsitektur, dan tata kelola `.md` dari root project ke dalam folder `docs/`: `AI_RULES.md`, `ARCHITECTURE.md`, `CODEX_REVIEW.md`, `DECISIONS.md`, `DESIGN.md`, `ERD.md`, `GEMINI.md`, `HANDOFF.md`, `PRD.md`, `PROJECT_STATUS.md`, `README.md`, `REGRESSION.md`, `REQUIREMENTS.md`, `ROADMAP.md`, `TESTS.md`.
  - Memperbarui pemetaan peran dokumen pada `.governance/docs-map.json` agar mengarah ke `docs/*`.
  - Menyesuaikan relative link pada `docs/README.md` (`adr/README.md`) dan `docs/adr/README.md` (`../DECISIONS.md`).
  - Menyiapkan symlink `README.md` dan `GEMINI.md` di root mengarah ke `docs/` untuk integrasi browser repo dan IDE rule discovery.
- Tidak diubah:
  - Isi esensial dan keputusan arsitektur di dalam dokumen tidak diubah.
  - Tidak ada perubahan kode aplikasi (tahap bootstrap kode S01 belum dimulai).

## Dampak teknis

- Modul/file:
  - `docs/*`
  - `.governance/docs-map.json`
  - `README.md` (symlink)
  - `GEMINI.md` (symlink)
- Migrasi/data: Tidak ada migrasi database.
- Keamanan/izin: Tidak ada perubahan izin.
- Keputusan atau asumsi: Pemindahan dokumen ke `docs/` sesuai instruksi pengguna, dengan symlink root untuk menjamin keterbacaan IDE dan viewer git.

## Bukti

- Perintah tes dan hasil:
  - `python3 /home/ilhamrizqiawan/.gemini/config/skills/docs-governance/scripts/audit-docs.py --root /home/ilhamrizqiawan/Projects/school_suite --scope full`
  - Hasil: Exit code 0, semua path spine ditemukan, relative markdown link valid, tidak ada orphan document.
- Langkah review manual:
  - Jalankan `ls -la` pada root untuk memeriksa kerapian.
  - Jalankan `python3 ~/.gemini/config/skills/docs-governance/scripts/audit-docs.py --root . --scope full`.
- Screenshot/output sintetis bila relevan:
  - Audit output: `✓ Deterministic audit passed.`

## Risiko dan pemulihan

- Risiko tersisa: Jika ada tool eksternal yang mencari dokumen selain `README.md`/`GEMINI.md` di root, perlu diarahkan ke `docs/`.
- Rollback: Pindahkan kembali file dari `docs/` ke root dan hapus symlink.
- Follow-up: Mempersiapkan bootstrap S01 (Laravel + Inertia + Vue/TS) sesuai roadmap dan kesiapan sekolah pilot.
