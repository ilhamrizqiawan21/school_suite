# Roadmap — School Suite

Urutan ini berbasis dependensi, bukan janji tanggal.

| Tahap | Isi | Gate keluar |
|---|---|---|
| S01 Fondasi | bootstrap, CI, layout, modul | instalasi bersih dan CI hijau |
| S02 Akses | akun, role, permission, audit | matriks akses lulus |
| S03 Data inti | periode, siswa, guru, kelas, enrollment, import | histori dan duplikat teruji |
| S04 Operasi akademik | kalender, jadwal, absensi, jurnal | benturan dan koreksi teruji |
| S05 Wali kelas | observasi, izin, prestasi, tindak lanjut | batas data privat lulus |
| S06 Penilaian | skema, rubrik, remedial, rapor, export | contoh hitung dan snapshot lulus |
| S07 Pilot | backup/restore, panduan, observability | penerimaan sekolah pilot |
| S08+ Ekspansi | LMS, CBT, SPMB, perpustakaan, administrasi | gate per modul |
| S14 Integrasi | API Teacher Desk dan inbox | konflik/idempotensi/izin lulus |

Prioritas awal adalah S01–S07. Setiap tahap dipecah menjadi task kecil yang menyebut requirement, migrasi, tes, risiko, dan rollback.

