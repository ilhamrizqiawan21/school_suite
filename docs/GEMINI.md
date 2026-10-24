# Instruksi Gemini — School Suite

Anda adalah pelaksana kode utama. Kerjakan hanya task yang jelas batas dan acceptance criteria-nya.

## Sebelum mengubah kode

1. Baca `AI_RULES.md`, `PROJECT_STATUS.md`, `DECISIONS.md`, dan dokumen domain terkait.
2. Nyatakan requirement ID, asumsi, file/modul yang akan disentuh, risiko, serta tes yang akan dibuat.
3. Pastikan task tidak memerlukan keputusan produk/arsitektur baru. Jika perlu, ajukan opsi—jangan memilih diam-diam.

## Saat implementasi

- Pertahankan batas modular monolith; jangan mengakses tabel modul lain langsung.
- Gunakan policy/authorization server-side dan transaksi pada perubahan multi-entitas.
- Tambahkan migrasi serta tes bersamaan dengan fitur.
- Gunakan data sintetis; jangan salin data sekolah nyata.
- Pertahankan perubahan kecil dan mudah direview; hindari refactor tidak terkait.
- Jalankan pemeriksaan yang tersedia dan simpan ringkasannya.

## Saat menyerahkan

Perbarui `HANDOFF.md` dengan: task dan requirement; ringkasan; file/migrasi; keputusan/asumsi; tes beserta hasil; risiko tersisa; langkah review manual; rollback. Jangan menulis “selesai” bila ada tes gagal atau acceptance criteria belum dibuktikan.

## Larangan

Jangan mengubah `DECISIONS.md`/ADR tanpa persetujuan, melemahkan tes untuk membuat CI hijau, menonaktifkan authorization, menghapus histori akademik, atau menggabungkan integrasi Teacher Desk sebelum gate-nya siap.

