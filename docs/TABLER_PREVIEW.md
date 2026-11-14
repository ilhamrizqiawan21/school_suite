# Eksperimen UI Tabler — School Suite

Tujuan: membandingkan dashboard admin School Suite saat ini dengan tampilan `@tabler/core` (Bootstrap 5), **tanpa mengubah halaman Dashboard.vue, rute Laravel, database, atau dependensi proyek**.

## Lokasi pratinjau
- File: `public/tabler-preview/index.html`
- Akses saat server lokal hidup: `http://127.0.0.1:8000/tabler-preview/`
- Dashboard asli: `http://127.0.0.1:8000/dashboard`

**Penting:** pratinjau ini adalah file HTML statis publik. Jangan menaruh data sensitif, akses token, atau data siswa sebenarnya di dalamnya. Gunakan untuk menilai tampilan/UX saja. Link ke halaman aplikasi akan mengikuti autentikasi dan hak akses Laravel yang berlaku.

## Cara mencoba

```bash
git fetch origin
git switch experiment/tabler-dashboard-preview-20261114
# Jalankan server aplikasi jika belum berjalan
php artisan serve
```

Buka `http://127.0.0.1:8000/tabler-preview/`. Ini tidak membutuhkan `npm install` tambahan atau migrasi database. Tampilan menggunakan Tabler CDN versi 1.7.5 sehingga memerlukan koneksi internet agar CSS/JS lengkap termuat.

Jika ingin membandingkan dengan versi asli, klik tombol **Lihat Dashboard Asli**. Tombol tema, navigasi mobile, dan pencarian menu akses cepat dapat dicoba pada pratinjau.

## Batasan tahap ini
1. Angka, grafik kehadiran, dan aktivitas menggunakan **data ilustrasi** dan ditandai demikian.
2. Pratinjau belum mengambil data dari service/dashboard Inertia. Tidak ada perubahan role/permission.
3. CSS Tabler dimuat hanya di HTML mandiri ini, untuk menghindari konflik global dengan Tailwind CSS 4.
4. Belum diuji di browser pada lingkungan lokal milik pengguna; lakukan pengujian visual desktop/mobile dan pastikan CDN tersedia.
5. Tidak ada perubahan ke `main` sampai pull request ditinjau dan digabungkan secara sadar.

## Tindak lanjut setelah memilih desain
- Pilih varian visual, tetapkan token warna/spacing dan aturan responsif.
- Buat komponen Vue 3 untuk kartu, tabel, dan grafik dengan data asli dari props Inertia.
- Gunakan scoped integration; lakukan audit konflik `@tabler/core` vs Tailwind.
- Migrasikan admin lebih dulu. Peran Guru, Siswa, Kepala Sekolah, dan Operator menyusul setelah validasi.
- Jalankan `npm run typecheck`, `npm run build`, test terfokus, dan Playwright sebelum mengusulkan penggantian dashboard utama.
