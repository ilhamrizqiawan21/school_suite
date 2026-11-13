# Usulan Redesign School Suite 2026

Dokumen ini menjadi arah desain sebelum perubahan komponen produksi. Sasaran utamanya adalah antarmuka sekolah yang tenang, jelas, modern, dan nyaman dipakai sepanjang hari oleh admin, operator, kepala sekolah, guru, wali kelas, siswa, dan orang tua.

## Arah visual

Nama arah: **Calm Academic OS**.

Visual memakai ruang putih yang cukup, permukaan berlapis tipis, garis pemisah yang lembut, tipografi yang mudah dibaca, dan satu aksen akademik yang konsisten. Warna institusi boleh menjadi preset tema, tetapi tidak boleh mengubah makna status atau membuat seluruh aplikasi menjadi warna jenuh.

Prinsip:

1. **Tenang lebih dulu.** Hindari gradient keras, glow, kartu berlebihan, dan bidang hijau/biru yang memenuhi layar.
2. **Hirarki dari ukuran dan ruang.** Warna hanya membantu mengelompokkan dan menandai status.
3. **Satu layar, satu pekerjaan utama.** Dashboard mengarahkan pekerjaan hari ini; halaman daftar memudahkan mencari dan menindaklanjuti data.
4. **Aksi bisa dikenali tanpa membaca panjang.** Ikon SVG konsisten, label tersedia melalui tooltip/aria-label, dan warna aksi mengikuti risikonya.
5. **Tidak ada layout yang bergantung pada hover.** Semua aksi tersedia untuk keyboard, touch, dan pembaca layar.

## Token awal

| Token | Nilai | Penggunaan |
|---|---|---|
| `--calm-ink` | `#26344D` | Judul, navigasi aktif, teks utama |
| `--calm-ink-soft` | `#5E6B80` | Teks sekunder dan helper |
| `--calm-canvas` | `#F4F7FB` | Latar aplikasi |
| `--calm-surface` | `#FFFFFF` | Panel, tabel, modal |
| `--calm-line` | `#DDE5EF` | Border dan divider |
| `--calm-primary` | `#4D6FAE` | Aksi utama dan fokus |
| `--calm-primary-soft` | `#EAF0FA` | Selected state dan background ikon |
| `--calm-success` | `#4E8B73` | Berhasil, tuntas, aktif |
| `--calm-warning` | `#B7833D` | Perlu perhatian, belum lengkap |
| `--calm-danger` | `#B85C62` | Hapus, gagal, risiko |
| `--calm-info` | `#5E86A8` | Informasi netral |

Warna status selalu disertai label atau ikon; jangan menyampaikan status melalui warna saja. Kontras teks normal ditargetkan minimal 4.5:1. Tema gelap memakai token pasangannya, bukan sekadar membalik warna.

## Tipografi dan bentuk

- UI: **Plus Jakarta Sans** atau **Inter**, 15–16px sebagai ukuran dasar, line-height 1.5.
- Judul landing: **Newsreader** atau **Source Serif 4** secara terbatas agar terasa akademik, hangat, dan tetap modern.
- Gunakan sentence case. Hindari label ALL CAPS yang terlalu sering.
- Radius: 10px untuk kontrol, 14px untuk panel, 18px untuk hero landing.
- Shadow: sangat tipis dan hanya untuk pemisahan layer; gunakan border sebagai pemisah utama.
- Spacing: skala 4/8/12/16/24/32/48. Semua panel memakai padding konsisten.

## Struktur aplikasi internal

```text
┌ sidebar 240px ┐ ┌ topbar: search · periode · notifikasi · profil ┐
│                │ ├ breadcrumb                                    ┤
│ Beranda        │ │ page title + context + primary action          │
│ Ruang kerja    │ │ filter / tabs / saved view                      │
│  Akademik      │ │ content: table, board, calendar, or form        │
│  Pembelajaran  │ │                                                  │
│  Asesmen       │ │                                                  │
│ Administrasi   │ │                                                  │
│ Laporan        │ │                                                  │
│ Pengaturan     │ │                                                  │
└────────────────┘ └──────────────────────────────────────────────────┘
```

Sidebar desktop tetap 240px dan bisa diringkas menjadi rail 72px. Di bawah 960px, sidebar menjadi drawer dengan overlay; pada mobile, topbar hanya menyisakan menu, judul konteks, notifikasi, dan profil.

Kelompok menu yang diusulkan:

- **Beranda**
- **Akademik** → Siswa, Guru, Rombel, Jadwal, Presensi, Perangkat Pembelajaran
- **Pembelajaran** → Materi, Tugas, Kelas Daring, Progress
- **Asesmen & Rapor** → Bank Soal, Ujian/CBT, Input Nilai, Sikap, Rapor, Analitik
- **Perwalian** → Ringkasan Kelas, Observasi, Kedisiplinan, Prestasi
- **SPMB** → Periode, Jalur & Kuota, Pendaftar, Verifikasi, Seleksi, Daftar Ulang, Laporan
- **Komunikasi** → Pengumuman, Chat, WhatsApp, Persuratan
- **Administrasi** → Fasilitas, Tugas Staf, Arsip
- **Laporan**
- **Pengaturan** → Sekolah, Pengguna & Akses, Organisasi, Keamanan, Integrasi

Submenu dibuka berdasarkan area aktif, memiliki indikator `aria-expanded`, dan tidak menampilkan semua turunan sekaligus bila tidak relevan dengan peran pengguna. Item aktif memiliki accent bar 3px dan surface `--calm-primary-soft`, bukan bidang warna gelap penuh.

## Pola halaman

Setiap halaman internal mengikuti urutan tetap:

1. Breadcrumb yang bisa diklik dan judul halaman.
2. Deskripsi satu kalimat tentang tugas halaman.
3. Action bar: cari, filter, tampilan, export, dan satu primary action.
4. Konten utama dengan keadaan loading, empty, error, permission, dan success yang jelas.

CRUD tidak menambah menu baru. Buat dan edit muncul sebagai modal/drawer kontekstual:

- desktop: drawer kanan 440–560px untuk form singkat; modal 640–760px untuk form bertahap;
- mobile: bottom sheet/full-screen sheet dengan header tetap dan footer aksi sticky;
- form memakai label eksplisit, helper text, validasi dekat field, fokus pertama yang masuk akal, dan peringatan perubahan belum disimpan;
- setelah berhasil, tutup overlay, perbarui baris yang berubah, dan tampilkan toast singkat.

Toast hanya untuk hasil tindakan yang tidak membutuhkan keputusan lanjutan. Auto-dismiss 3–5 detik, dapat ditutup, tidak menutup konten, dan memiliki `role="status"` atau `role="alert"` sesuai tingkat urgensi.

## Sistem ikon dan aksi

Gunakan satu keluarga ikon SVG, misalnya Lucide atau Heroicons outline. Emoji dan ikon campuran dihilangkan.

| Aksi | Ikon | Warna | Catatan |
|---|---|---|---|
| Export Excel | file spreadsheet / `FileSpreadsheet` | `--calm-success` | label/tooltip “Ekspor Excel” |
| Export PDF | file text / `FileText` | `--calm-danger` | label/tooltip “Ekspor PDF” |
| Edit | pensil / `Pencil` | `--calm-primary` | tidak memakai warna merah |
| Hapus | trash / `Trash2` | `--calm-danger` | konfirmasi hanya untuk tindakan destruktif |
| Arsip | archive / `Archive` | `--calm-warning` | dapat dipulihkan bila aturan domain mendukung |
| Tambah | plus / `Plus` | `--calm-primary` | primary action halaman |
| Detail | eye / `Eye` | `--calm-info` | gunakan bila membuka preview |
| Filter | sliders / `SlidersHorizontal` | `--calm-ink-soft` | badge jumlah filter aktif |

Ikon-only button minimal 44×44px, memiliki `aria-label`, `title`, focus ring yang terlihat, dan tooltip yang tidak menjadi satu-satunya cara memahami fungsi.

## Dashboard per peran

Dashboard tidak memakai kumpulan kartu statistik yang seragam. Prioritasnya adalah pekerjaan yang perlu dilakukan:

- Admin/operator: kesehatan data, periode aktif, item belum lengkap, pekerjaan administrasi.
- Guru: agenda hari ini, jurnal/nilai yang belum lengkap, kelas berikutnya, pengumuman.
- Wali kelas: presensi, siswa yang perlu perhatian, catatan observasi, kesiapan rapor.
- Kepala sekolah: ringkasan tren, pengecualian, kelengkapan lintas unit, laporan.
- Siswa: jadwal, tugas jatuh tempo, materi terbaru, nilai/progress.

Gunakan satu panel “Perlu perhatian” yang dapat ditindaklanjuti, satu panel agenda, dan satu panel tren. Angka ringkas hanya muncul bila membantu keputusan.

## Landing page sekolah

Landing publik memakai struktur yang lazim pada situs sekolah modern: identitas sekolah, program/keunggulan, kehidupan siswa, bukti dan berita, admissions/SPMB, lalu kontak. Riset situs sekolah dan panduan admissions terbaru menunjukkan bahwa pengunjung mencari program, kampus, jadwal/biaya, proses pendaftaran, bukti kualitas, dan cara menghubungi sekolah dengan cepat.

Susunan yang diusulkan:

1. Header: logo/nama, Profil, Akademik, Kehidupan Siswa, Berita, Kontak; CTA “SPMB” atau “Jadwalkan kunjungan”.
2. Hero: satu foto kampus/siswa yang autentik, headline singkat, dua CTA, dan fakta kepercayaan (akreditasi, tahun berdiri, program).
3. “Belajar di sini”: tiga pilar dengan ikon sederhana.
4. Program/jenjang dengan kartu yang tidak berlebihan.
5. Kehidupan siswa: galeri/activity strip.
6. Berita dan agenda terdekat.
7. SPMB: tanggal penting, jalur, dokumen, FAQ, CTA daftar.
8. Footer: alamat, jam layanan, kontak, media sosial, aksesibilitas.

Gunakan foto lokal yang nyata dari `public/images/landing`, overlay lembut, dan jangan menaruh teks di atas area foto yang ramai. Pada mobile, hero menjadi satu kolom; CTA tetap terlihat tanpa harus menggulir jauh.

## Responsive dan kualitas

Target uji: 320, 375, 414, 768, 1024, dan 1440px; zoom teks 200%; keyboard-only; reduced motion; dark mode; dan koneksi lambat.

- tabel berubah menjadi card/list pada lebar sempit atau memakai scroll container yang diberi label;
- teks memakai `minmax(0, 1fr)`, `overflow-wrap:anywhere`, dan line length yang wajar;
- gambar `max-width:100%`, aspect ratio dicadangkan untuk mencegah layout shift;
- tidak ada panel fixed yang menutupi konten tanpa scroll internal;
- modal mengunci fokus, dapat ditutup Escape, dan mengembalikan fokus ke tombol pemicu;
- state loading/empty/error/permission didefinisikan per halaman, bukan placeholder generik.

## Urutan implementasi

1. Kunci token warna, tipografi, spacing, icon set, dan primitive button/input/modal/toast.
2. Rapikan shell: sidebar, topbar, breadcrumb, responsive drawer, focus state.
3. Migrasikan halaman daftar CRUD ke action bar + overlay form.
4. Migrasikan dashboard per peran dan halaman laporan/export.
5. Rebuild landing publik menggunakan struktur di atas.
6. Jalankan regression visual pada breakpoint utama dan review aksesibilitas.

## Dasar riset

- Mockup internal sudah menetapkan arah **Calm Academic OS** dan struktur menu; dokumen ini memperhalusnya menjadi sistem yang lebih lunak dan terukur.
- W3C merekomendasikan navigasi yang konsisten, breadcrumb, judul halaman deskriptif, dukungan keyboard/touch, dan indikasi state yang jelas: [Navigation Design](https://www.w3.org/WAI/curricula/designer-modules/navigation-design/).
- Panduan aksesibilitas pendidikan 2026 menekankan struktur heading, label form, kontras, alt text, fokus terlihat, dan responsif saat teks diperbesar: [LACOE Accessibility Guidance 2026–27](https://www.lacoe.edu/content/dam/lacoeedu/documents/newsroom/accessibility/LACOE_Accessibility-Guidance-Document_2026-27.pdf).
- Pola landing sekolah modern menonjolkan program, admissions, tanggal/dokumen, bukti kualitas, profil guru, berita, dan kontak/kunjungan: [School Website Design That Drives Enrollment](https://www.bearhouse.io/websites/schools) dan [School and College Website Design](https://dauagency.com/school-and-college-website-design/).
