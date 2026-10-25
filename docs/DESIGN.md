# Product & UX Design — School Suite

## Prinsip

1. Navigasi mengikuti pekerjaan pengguna, bukan struktur tabel.
2. Periode aktif selalu terlihat dan perubahan periode dibuat eksplisit.
3. Status ditulis dengan label dan ikon; warna hanya pendukung.
4. Perubahan massal memiliki preview, jumlah dampak, dan hasil baris gagal.
5. Draft, diajukan, dikembalikan, disetujui, dan terbit tidak dicampur.

## Arsitektur informasi

| Area | Isi utama |
|---|---|
| Beranda | pekerjaan hari ini, kelengkapan, jadwal, pengumuman |
| Akademik | master data, periode, kelas, enrollment, kalender, jadwal |
| Pembelajaran | kelas saya, materi, tugas, ujian |
| Wali Kelas | ringkasan siswa, absensi, perkembangan, rapor |
| Administrasi | SPMB, surat, perpustakaan, sarpras |
| Laporan | filter, preview, export, status penerbitan |
| Pengaturan | sekolah, akses, modul, integrasi, backup |

## Pola layar

- Daftar: pencarian, filter tersimpan, kolom penting, pagination, dan aksi massal aman.
- Form: draf otomatis bila sesuai, validasi dekat field, dan peringatan sebelum meninggalkan perubahan.
- Import: unggah → petakan kolom → preview → validasi → konfirmasi → laporan hasil.
- Laporan: pilih periode/template → preview → validasi kelengkapan → terbit/export.
- Error: jelaskan apa yang gagal, data yang tetap aman, dan tindakan berikutnya.

## Aksesibilitas dan perangkat

Alur inti dapat dioperasikan keyboard, fokus terlihat, label form eksplisit, tabel memiliki alternatif responsif, dan target sentuh memadai untuk tablet. Bahasa utama Indonesia dengan istilah sekolah yang konsisten.

## Arsitektur warna dan identitas

- Identitas inti School Suite memakai **Academic Blue**: biru-navy yang netral untuk sekolah umum, madrasah, maupun lembaga pendidikan lainnya.
- Warna institusi merupakan lapisan tema yang dapat dikonfigurasi, bukan warna inti produk. Madrasah dapat menggunakan preset hijau tanpa membuat seluruh produk selalu diasosiasikan dengan madrasah.
- Hijau pada tema bawaan hanya dipakai secara semantik untuk status berhasil, tuntas, aktif, dan aman.
- Kuning keemasan menandai perhatian atau identitas aksen; merah tetap khusus kesalahan dan tindakan berisiko.
- Logo/nama sekolah boleh membawa warna institusi, sementara navigasi, tabel, fokus, dan aksi utama tetap konsisten agar pelatihan serta dokumentasi lintas sekolah tidak berubah.

## Definition of ready desain

Sebelum Gemini membuat layar kompleks, task harus memiliki pengguna, skenario utama, keadaan kosong/loading/error/sukses, aturan izin, data minimum, dan acceptance criteria.

## Mockup tervalidasi terhadap repo

Prototype interaktif **Calm Academic OS** tersedia di [mockup/README.md](mockup/README.md). Mockup memetakan beranda, data siswa, dan input nilai ke rute serta model data yang sudah ada; ia merupakan acuan implementasi, bukan perubahan UI produksi.

