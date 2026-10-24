# Regression Matrix — School Suite

Setiap perubahan menjalankan tes modulnya dan seluruh baris downstream yang relevan.

| Perubahan | Regresi yang wajib diperiksa |
|---|---|
| User/role/policy | login, semua scope data, export, lampiran, API |
| Periode/enrollment | jadwal, absensi, jurnal, nilai, rapor, wali kelas |
| Skema nilai/rubrik | nilai draft, remedial, snapshot, export, sync |
| Template laporan | preview, snapshot lama, font, PDF/DOCX |
| Import siswa | duplikat, relasi kelas, rollback batch, audit |
| API integrasi | idempotensi, reconnect, konflik, pencabutan, lampiran |
| Migrasi/storage | upgrade, antrean, backup, restore, rollback operasional |

Perintah aktual dicatat setelah toolchain dibuat. Gemini menuliskan perintah dan hasil nyata di `HANDOFF.md`; Codex memeriksa cakupan berdasarkan matriks ini.

