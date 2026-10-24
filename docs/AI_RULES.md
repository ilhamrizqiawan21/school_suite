# AI Rules — School Suite

## Otoritas dan sumber kebenaran

Urutan: instruksi pengguna saat ini → keputusan aktif/ADR → PRD dan requirements → architecture/ERD/design → task. Kode, migrasi, dan tes adalah bukti keadaan implementasi; bila bertentangan dengan dokumen, jangan menebak—catat konflik.

## Aturan wajib untuk semua AI

1. Baca `README.md`, `PROJECT_STATUS.md`, dokumen task terkait, dan ADR relevan sebelum bertindak.
2. Jangan memperluas scope task, mengganti stack, atau mengubah keputusan tanpa proposal.
3. Jangan menyalin kode repo lama sebelum lisensi, dependency, keamanan, dan perilakunya diperiksa.
4. Lindungi data siswa: jangan memasukkan data nyata ke fixture, log, prompt, screenshot, atau contoh.
5. Semua perubahan schema memiliki migrasi, dampak upgrade, dan strategi pemulihan.
6. Semua aturan akses diuji dari sisi server; menyembunyikan tombol bukan kontrol keamanan.
7. Semua formula nilai memiliki contoh uji nilai nol, kosong, batas, pembulatan, dan remedial.
8. Jangan menyatakan selesai tanpa bukti perintah tes dan ringkasan hasil.
9. Jangan commit secret, token, database nyata, hasil export siswa, atau file build besar.
10. Bila task tidak memenuhi definition of ready, hentikan implementasi spekulatif dan tulis pertanyaan/blocker.

## Pembagian kerja

- Gemini mengimplementasikan task yang siap dan membuat bukti.
- Codex menilai arsitektur, keamanan, konsistensi data, tes, dan risiko regresi.
- Pemilik produk memutuskan prioritas, trade-off produk, dan penerimaan.

