# School Suite — Brand Kit

Paket identitas visual **School Suite** untuk aplikasi manajemen sekolah terintegrasi.

## Isi paket

- `01_Logos_PNG/`: logo horizontal dan vertikal transparan, simbol transparan, monokrom hitam, versi putih, serta logo untuk latar terang.
- `02_App_Icons/`: ikon aplikasi SVG sederhana + PNG berbagai ukuran 16–1024 px.
- `03_Favicon_Web/`: `favicon.ico` multiresolusi (16/32/48 px), `favicon.svg`, favicon PNG, Apple Touch Icon 180 px, Android Chrome 192/512 px, ikon maskable untuk PWA, dan `site.webmanifest`.
- `04_Brand_Guidelines/`: papan konsep asli, palet warna CSS, dan JSON.
- `05_Integration_Examples/`: contoh HTML `<head>`, logo header, serta panduan Laravel/Vite.
- `06_Social_Preview/`: gambar Open Graph 1200×630 px untuk tautan dan preview media sosial.

## Warna merek

| Fungsi | Kode |
| --- | --- |
| Navy utama | `#0D3B66` |
| Teal sekunder | `#0F766E` |
| Hijau aksen | `#34D399` |
| Emas aksen | `#D4A017` |
| Latar lembut | `#F3F7FC` |
| Teks sekunder | `#6B7280` |

## Rekomendasi penggunaan

- **Sidebar/header aplikasi:** `01_Logos_PNG/school-suite-logo-horizontal-transparent.png` (atau logo vertikal untuk halaman login).
- **Login/landing page:** logo vertikal.
- **Favicon browser:** `03_Favicon_Web/favicon.svg` dengan fallback `.ico`.
- **PWA/shortcut Android:** berkas Android Chrome, termasuk maskable.
- **Logo dokumen/cetak:** versi horizontal monokrom (uji kontras sesuai media).
- **Tautan sosial:** `06_Social_Preview/school-suite-og-image-1200x630.jpg`.

## Catatan teknis

Logo utama berasal dari visual hasil generasi gambar, sehingga berupa **PNG raster beresolusi tinggi**, bukan file SVG vektor asli. `favicon.svg` dan `school-suite-app-icon.svg` adalah **interpretasi ulang vektor yang lebih sederhana** khusus agar jelas pada ukuran kecil; karena itu detailnya tidak identik dengan logo utama.

Untuk pemasangan cepat, buka `05_Integration_Examples/README-laravel.md`.

Penggunaan aset ini untuk branding proyek School Suite. Paket ini tidak menetapkan lisensi atas kode sumber aplikasi.
