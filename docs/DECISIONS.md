# Decision Log — School Suite

Dokumen ini merangkum keputusan aktif. Keputusan sulit dibalik memperoleh ADR terpisah di `docs/adr/`.

| ID | Tanggal | Keputusan | Alasan | Status |
|---|---|---|---|---|
| SS-D-001 | 2026-10-24 | Satu sekolah per instalasi pada rilis awal | Menekan kompleksitas isolasi dan operasi | Diterima |
| SS-D-002 | 2026-10-24 | Modular monolith Laravel + Vue/TypeScript | Cocok dengan aset repo dan kapasitas awal | Diterima |
| SS-D-003 | 2026-10-24 | MySQL sebagai database server awal | Sesuai target hosting dan pengalaman repo | Diterima |
| SS-D-004 | 2026-10-24 | Enrollment menyimpan histori kelas per periode | Kelas pada profil siswa merusak histori | Diterima |
| SS-D-005 | 2026-10-24 | Rapor terbit menggunakan snapshot dan formula berversi | Menjamin reproduksi laporan | Diterima |
| SS-D-006 | 2026-10-24 | Tabungan siswa di luar cakupan | Keputusan pemilik produk | Diterima |
| SS-D-007 | 2026-10-24 | Integrasi Teacher Desk memakai API, bukan database bersama | Isolasi, keamanan, dan konflik lebih terkendali | Diterima |
| SS-D-008 | 2026-10-24 | Repo lama dievaluasi per fitur, bukan disalin utuh | Kematangan dan stack berbeda | Diterima |
| SS-D-009 | 2026-10-24 | Keuangan institusi ditunda sampai ledger dirancang | Risiko akurasi transaksi tinggi | Diterima |

## Cara mengubah keputusan

Gemini tidak boleh mengubah keputusan ini diam-diam. Ajukan proposal berisi konteks, opsi, dampak data/migrasi, keamanan, pengujian, dan rollback. Codex menilai arsitekturnya; pemilik produk mengesahkan perubahan produk.

