# Mockup School Suite — Calm Academic OS

Prototype statis interaktif untuk memvalidasi arah UI sebelum desain diterapkan ke aplikasi Laravel 13, Inertia 3, Vue 3, dan Tailwind CSS 4 yang sudah ada.

Mockup ini sengaja terisolasi dari kode produksi. Istilah, pengguna, rombel, siswa, skema penilaian, dan aturan remedial telah diselaraskan dengan implementasi serta `PilotSchoolSeeder` per 25 Oktober 2026. Lihat [pemetaan repo](REPO_ALIGNMENT.md) untuk batas dan target implementasinya.

Palet bawaan menggunakan **Academic Blue** sebagai identitas universal School Suite. Hijau tetap tersedia sebagai warna status berhasil dan kelak sebagai preset branding khusus madrasah.

## Layar

1. **Beranda guru/wali kelas** — agenda, prioritas, kelengkapan, dan detak kelas.
2. **Data siswa/operator** — pencarian, filter, bulk selection, density, dan kualitas data.
3. **Input nilai** — nilai mentah, remedial, catatan, preview nilai akhir, status ketuntasan, dan simpan.

Gunakan navigasi kiri untuk berpindah layar. Pilihan `State` di topbar mensimulasikan loading, empty, error, partial, dan permission. Theme toggle, mobile drawer, pencarian siswa, pilihan tabel, density, task checklist, input nilai, dan toast juga interaktif.

## Menjalankan

```bash
python3 -m http.server 4175 --directory docs/mockup
```

Buka `http://127.0.0.1:4175`. Prototype tidak memiliki dependency eksternal dan memakai data pilot sintetis dari repo.

## Bukti render

Screenshot desktop/mobile dan laporan pemeriksaan berada di [`evidence/`](evidence/). Ini mockup untuk keputusan desain, bukan implementasi production atau bukti bahwa seluruh requirement aplikasi telah terpenuhi.

