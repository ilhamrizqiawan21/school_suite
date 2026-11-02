# Mockup School Suite 2026 — Academic Command Center

Prototype statis interaktif untuk memvalidasi arah UI dan arsitektur menu sebelum desain diterapkan ke aplikasi Laravel 13, Inertia 3, Vue 3, dan Tailwind CSS 4 yang sudah ada.

Mockup ini sengaja terisolasi dari kode produksi. Istilah, pengguna, rombel, siswa, skema penilaian, dan aturan remedial telah diselaraskan dengan implementasi serta `PilotSchoolSeeder` per 25 Oktober 2026. Lihat [pemetaan repo](REPO_ALIGNMENT.md) untuk batas dan target implementasinya.

Palet mockup memakai **deep navy**, **signal mint**, dan **amber** untuk membentuk Academic Command Center yang terasa lebih kontemporer pada 2026. Token produksi tidak diubah; arah ini hanya berlaku di prototype.

Peta menu lengkap ada di [MENU_MAP.md](MENU_MAP.md).

## Layar

1. **Beranda guru/wali kelas** — agenda, prioritas, kelengkapan, dan detak kelas.
2. **Data siswa/operator** — pencarian, filter, bulk selection, density, dan kualitas data.
3. **Input nilai** — nilai mentah, remedial, catatan, preview nilai akhir, status ketuntasan, dan simpan.

Navigasi kiri sudah memetakan seluruh kelompok menu aplikasi: SPMB, Akademik, Pembelajaran LMS, Asesmen & CBT, Penilaian & Rapor, Perwalian, Komunikasi, Tata Usaha, dan Pengaturan. Tiga layar prototype tetap menjadi layar validasi utama; menu lain ditampilkan sebagai struktur informasi tanpa membuat layar palsu yang belum terhubung ke alur produksi.

Gunakan navigasi kiri untuk berpindah layar. Pilihan `State` di topbar mensimulasikan loading, empty, error, partial, dan permission. Theme toggle, mobile drawer, pencarian siswa, pilihan tabel, density, task checklist, input nilai, dan toast juga interaktif.

## Menjalankan

Jalankan dari root repo agar asset mockup dan asset bersama berada pada origin yang sama:

```bash
cd /path/to/school_suite
python3 -m http.server 4175
```

Buka `http://127.0.0.1:4175/docs/mockup/`. Prototype tidak memiliki dependency eksternal dan memakai data pilot sintetis dari repo.

## Bukti render

Screenshot desktop/mobile dan laporan pemeriksaan berada di [`evidence/`](evidence/). Artefak tersebut direkam pada **25 Oktober 2026**; mockup dan stylesheet berubah setelah capture pada **26–28 Oktober 2026**, sehingga screenshot adalah referensi historis, bukan bukti pixel-level untuk render saat ini. Ini mockup untuk keputusan desain, bukan implementasi production atau bukti bahwa seluruh requirement aplikasi telah terpenuhi.

