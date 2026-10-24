# Project Status — School Suite

Terakhir diperbarui: 24 Oktober 2026

## Keadaan saat ini

- Fase: desain awal, belum bootstrap kode.
- Kesiapan: PRD, requirement, UX, arsitektur, ERD, keputusan, dan workflow AI tersedia sebagai draft 0.1.
- Langkah berikut: validasi sekolah pilot dan pecah S01 menjadi task implementasi.

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

