# Pemetaan Mockup ke Repo School Suite

Dokumen ini mencatat dasar faktual mockup agar desain tidak dianggap sebagai fitur produksi yang sudah selesai.

## Dasar implementasi yang diperiksa

- Stack: Laravel 13, Inertia Laravel 3, Vue 3, TypeScript, Vite 8, dan Tailwind CSS 4.
- Modul aktif: Core, Academic, Homeroom, dan Assessment.
- Data pilot: SMP Negeri 1 Percontohan Nusantara, periode 2026/2027 Semester Ganjil, wali kelas Budi Hartono, S.Pd., rombel VII-A, dan 30 siswa sintetis.
- Penilaian: skema Kurikulum Merdeka SMP 2026, komponen Formatif Harian 1, bobot 25%, dan KKTP 75.
- Aturan remedial: nilai akhir memakai nilai mentah bila tuntas; jika belum tuntas, nilai remedial dibatasi maksimal KKTP.

## Pemetaan layar

| Mockup | Rute saat ini | Target implementasi utama |
|---|---|---|
| Beranda guru/wali kelas | `/` | `Dashboard.vue`, `AppLayout.vue`, `TopNavbar.vue` |
| Data siswa VII-A | `/akademik/siswa` | `Academic/Students/Index.vue` |
| Formatif Harian 1 | `/penilaian/skor` | `Assessment/Scores/InputGrid.vue` |
| Navigasi responsif | semua halaman | `AppLayout.vue`, `SidebarNavigation.vue` |

## Batas mockup

- Tidak terhubung ke database, autentikasi, izin, atau endpoint Laravel.
- Angka operasional pada beranda dan dua nilai tambahan selain data seed adalah contoh presentasi untuk memperlihatkan keadaan remedial/belum tuntas, bukan hasil agregasi produksi.
- Aksi simpan, impor, filter, dan notifikasi hanya simulasi antarmuka.
- Implementasi produksi perlu dilakukan per komponen oleh Gemini, lalu ditinjau Codex sesuai `AI_RULES.md` dan `HANDOFF.md`.

## Urutan penerapan yang disarankan

1. Terapkan token visual Academic Blue, mekanisme tema institusi, shell responsif, navigasi berdasarkan peran, dan konteks periode aktif.
2. Migrasikan beranda menjadi ringkasan berbasis query nyata dan izin pengguna.
3. Terapkan pola tabel pada Data Siswa tanpa mengubah kontrak backend.
4. Terapkan desain Input Nilai dengan mempertahankan aturan remedial yang sudah diuji.
5. Tambahkan keadaan loading, kosong, gagal, parsial, dan akses terbatas beserta pengujian aksesibilitas.
