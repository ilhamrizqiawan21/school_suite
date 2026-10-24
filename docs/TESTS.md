# Test Strategy — School Suite

| ID | Area | Bukti wajib |
|---|---|---|
| SS-T-ACCESS | Akses | feature test role, sekolah, kelas, periode, export, lampiran |
| SS-T-DATA | Data inti | unit/feature test enrollment, duplikat, import, histori |
| SS-T-SCHEDULE | Jadwal | benturan guru/ruang dan visibilitas draft |
| SS-T-ASSESS | Penilaian | table-driven test nol, kosong, batas, bobot, pembulatan, remedial |
| SS-T-REPORT | Rapor | snapshot, versi formula, dan reproduksi export |
| SS-T-RECOVERY | Operasi | instalasi bersih, migrasi, backup dan restore terpisah |
| SS-T-INTEGRATION | Teacher Desk | idempotensi, konflik versi, izin dicabut, periode terkunci, reconnect |

CI minimum saat repo kode tersedia: format/lint, analisis statis, unit, feature, contract, dan build frontend. E2E dipakai untuk alur kritis, bukan menggantikan tes domain. Data uji harus sintetis.

