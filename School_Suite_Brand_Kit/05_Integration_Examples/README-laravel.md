# Integrasi cepat untuk Laravel / Vite

1. Salin semua isi `03_Favicon_Web/` ke `public/brand/favicon/`.
2. Salin semua isi `01_Logos_PNG/` ke `public/brand/logos/`.
3. Tempel isi `head-tags.html` ke `<head>` layout utama (mis. `resources/views/layouts/app.blade.php`).
4. Pakai logo di header/sidebar sesuai `example-logo-layout.html`.
5. Jalankan ulang aplikasi atau bersihkan cache browser jika favicon lama masih muncul.
6. Pastikan `https://domain-sekolah/brand/favicon/favicon.svg` dan `site.webmanifest` terbuka.

**Catatan PWA:** Jika `site.webmanifest` ditempatkan pada `/brand/favicon/site.webmanifest`, semua URL gambar di manifest tetap merujuk ke `/brand/favicon/...`, sehingga aman dipakai dari root situs.

**Catatan:** Logo berformat PNG merupakan aset raster beresolusi tinggi. Berkas SVG yang disertakan hanya untuk ikon ringkas/favikon, bukan versi vektor penuh dari seluruh logo utama.
