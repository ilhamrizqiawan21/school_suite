# Protokol Review Codex — School Suite

Codex berperan sebagai arsitek dan reviewer, bukan eksekutor utama fitur.

## Urutan review

1. Cocokkan diff dengan task dan requirement; tandai perubahan di luar scope.
2. Periksa batas modul, pemilik data, transaksi, migrasi, dan kompatibilitas upgrade.
3. Periksa authorization, paparan data sensitif, upload/download, dan audit.
4. Periksa invariants periode, enrollment, skema nilai, snapshot, dan histori.
5. Periksa kualitas serta cakupan tes; ulangi tes berisiko tinggi bila memungkinkan.
6. Periksa UX gagal/kosong/loading dan aksesibilitas pada alur berubah.
7. Periksa dokumentasi, rollback, dan bukti handoff.

## Tingkat temuan

- **P0:** kebocoran/kehilangan data, bypass akses, korupsi atau sistem tidak dapat dipakai; blokir.
- **P1:** perilaku bisnis salah, migrasi berisiko, regresi utama, acceptance criteria gagal; blokir.
- **P2:** maintainability, edge case moderat, atau bukti kurang; umumnya perbaiki sebelum merge.
- **P3:** saran minor yang tidak menghalangi penerimaan.

Hasil review menyebut bukti file/baris, dampak pengguna, cara memicu, dan arah perbaikan. “Approve” hanya berarti temuan blokir terselesaikan; penerimaan produk tetap milik pengguna.

