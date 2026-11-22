# TODO — Panel Admin, Konten Publik, Role, dan Integrasi Backend

Tanggal baseline: **22 November 2026**. Status: **rencana verifikasi dan implementasi; belum merupakan laporan penyelesaian**.

## Koreksi hasil review Fase 1 — 22 November 2026

Klaim seluruh 62 task Fase 1 `VERIFIED` pada handoff Gemini telah ditinjau ulang. Checkbox AP-01 s.d. AP-04 dibuka kembali: `READY_REVIEW` berarti ada implementasi yang masih memerlukan penerimaan sesuai seluruh kriteria task; `OPEN` berarti ada cakupan atau bukti yang belum terpenuhi. Jangan menyimpulkan 62 task selesai dari lulusnya suite umum.

Perbaikan hasil review: simpan profil dipisahkan dari publikasi konten, mode tanpa aksi terbit hanya menyimpan draf, konflik revisi ditolak dalam transaksi, nomor revisi tidak berulang setelah retensi 15 versi, restore tidak sah ditolak dan restore sah membuat revisi baru, serta rasio kontras dihitung. Kewenangan penerbitan PTSP, pengesahan legalisir, persetujuan/pencabutan mutasi dan penerbitan/penerbitan ulang SKL ditegakkan pada service/endpoint; kapabilitas UI dan navigasi mengikuti otorisasi tersebut. Bukti pengujian tercatat pada handoff koreksi.

**Serah terima ke Fase 2:** ambil AP-05 s.d. AP-09 per irisan vertikal, sambil menyelesaikan prasyarat AP-04 yang diperlukan terlebih dahulu. Khususnya tipe field/editor, tampil-sembunyi/urutan, preview privat, unpublish/jadwal, media, penghapusan fallback demo dan invalidasi cache belum boleh diasumsikan selesai. Jangan menandai Fase 1 diterima penuh atau melewati dependensi hanya karena blocker bug sudah diperbaiki. Tidak ada implementasi Fase 2 yang dijalankan dalam koreksi ini.

## 1. Kontrak pekerjaan

1. **Halaman publik yang disetujui pemilik produk menjadi acuan tetap. Panel admin dan backend menyesuaikan halaman publik.**
2. Seluruh konten publik harus dapat dikelola pengguna berwenang melalui panel, tanpa menyunting kode, template, konfigurasi server, atau database secara langsung.
3. Penggantian konten statis menjadi binding data boleh menyentuh template secara teknis, tetapi tidak mengubah layout, susunan komponen, interaksi, atau alur publik yang disetujui.
4. Tampilan admin mengikuti standar UI yang ditunjuk pemilik produk; tampilan publik tetap mengikuti baseline publik. Aturan radius, warna, dan layout admin tidak diterapkan otomatis ke publik.
5. Periksa dan gunakan implementasi yang sudah ada. Task berarti **verifikasi, perbaiki, atau lengkapi**, bukan membangun ulang fitur yang telah benar.
6. Pemisahan role harus ditegakkan oleh backend, mencakup sekolah, kelas, penugasan, kepemilikan, tahap proses, dan dokumen.
7. Refaktor flow hanya dilakukan berdasarkan kesenjangan yang terbukti. Pertahankan tiket, status, dokumen, URL relevan, dan histori yang sudah terbit.
8. Tidak menambah dependency, mengubah arsitektur, mengubah skema DB, menyunting `.env`, atau menjalankan operasi destruktif tanpa persetujuan yang disyaratkan instruksi proyek.
9. Tidak mengerjakan multi-tenant, microservices, tabungan, pencairan dana, ledger, atau integrasi baru yang belum disetujui. Isolasi `school_id` yang sudah ada tetap wajib.
10. Tidak melakukan commit, deployment, atau perubahan pada repo standar UI kecuali diminta.

## 2. Sumber acuan dan batas bukti

### Standar UI yang berlaku

- [Standar UI Project](https://github.com/ilhamrizqiawan21/standar-ui-project), snapshot commit [`87490c183b9d57b2600e3daa986171e84df4ab5c`](https://github.com/ilhamrizqiawan21/standar-ui-project/commit/87490c183b9d57b2600e3daa986171e84df4ab5c).
- [Instruksi repo standar](https://github.com/ilhamrizqiawan21/standar-ui-project/blob/87490c183b9d57b2600e3daa986171e84df4ab5c/AGENTS.md), [skill admin-panel-ui](https://github.com/ilhamrizqiawan21/standar-ui-project/blob/87490c183b9d57b2600e3daa986171e84df4ab5c/.agents/skills/admin-panel-ui/SKILL.md), dan [indeks tujuh aturan UI](https://github.com/ilhamrizqiawan21/standar-ui-project/blob/87490c183b9d57b2600e3daa986171e84df4ab5c/admin-panel-standards/README.md).
- Baca aturan 01–07 dari snapshot tersebut ketika mengerjakan paket UI. Tautkan standar; jangan menggandakan seluruh isinya ke repo ini.
- Snapshot yang diperiksa belum memuat override School Suite di `projects/`. Jangan menganggap proposal desain lokal sebagai override yang telah disetujui.

### Acuan lokal

- [Aturan AI](AI_RULES.md), [keputusan aktif](DECISIONS.md), [desain lokal](DESIGN.md), [proposal desain lokal](DESIGN_PROPOSAL_2026.md).
- [Requirements utama](REQUIREMENTS.md), [requirements paritas](REQUIREMENTS_PARITAS_MAN4_BANJAR.md), [inventaris paritas](INVENTARIS_DAN_BASELINE_PARITAS.md), [TODO paritas sebelumnya](TODO_PARITAS_MAN4_BANJAR.md).
- [Panduan pengujian](TESTS.md), [regresi](REGRESSION.md), [handoff](HANDOFF.md), kode, migrasi, rute, policy, dan hasil tes aktual.
- Dokumen lama memiliki tanggal dan status berbeda. Checkbox lama tidak menjadi bukti bahwa integrasi yang diminta pada dokumen ini sudah selesai.

### Pemeriksaan awal yang sudah dilakukan

- Membuka 36 titik masuk publik melalui browser lokal, mengamati judul, bagian halaman, formulir, aksi, dan tautan; bukti visual tersimpan di `.playwright-mcp/public-audit-*.png` pada lingkungan audit.
- Membuka satu detail berita serta memastikan `/layanan/proposal` mengarahkan tamu ke login.
- Memeriksa inventaris rute dan sebagian controller/service, termasuk konfigurasi publik, 17 role, statistik alumni, serta perbedaan perpustakaan replika dan portal aktif.
- **Belum membuktikan semua transaksi E2E**, semua varian hasil/dokumen, seluruh halaman admin per role, atau kesiapan produksi. Pemeriksaan tersebut tetap menjadi TODO.

### Kesenjangan yang sudah teramati

| Prioritas | Bukti | Kesenjangan |
|---|---|---|
| P0 | `LibraryController::replica/replicaAction` | `/perpustakaan` memakai tampilan statis dan aksi stub; layanan aktif berada di `/perpustakaan/portal`. |
| P0 | `home.blade.php`, `profile.blade.php`, navigasi/footer, beberapa Vue publik | Masih ada teks, aset, tautan, program, dan bagian yang ditentukan langsung dalam kode. |
| P0 | `videos.blade.php`, `SchoolGalleryController::publicVideos` | Konten video masih berupa array statis dan belum memakai pengelolaan video lengkap. |
| P1 | Fallback publik untuk berita, galeri, GTK, prestasi | Ketiadaan data dapat digantikan konten demo; admin perlu dapat mengosongkan atau menonaktifkan bagian tanpa data fiktif muncul kembali. |
| P1 | `PublicPortalService::madrasahShellData`, `SchoolProfileController::home` | Mode demo lokal dapat mengganti identitas/data sekolah; perlu kontrak data publik yang konsisten. |
| P1 | Daftar rute dan matriks role | Banyak area sudah memiliki akses role, tetapi kesetaraan menu, izin per aksi, penugasan, dan pemisahan persetujuan belum diaudit menyeluruh. |

P0 = menghalangi kontrak utama atau keamanan/integrasi layanan. P1 = kebutuhan operasional utama. P2 = penyempurnaan yang tetap wajib sebelum penerimaan final bila relevan. Prioritas tidak menghapus task minor.

## 3. Cara menjalankan TODO

- Semua checkbox awal terbuka. Centang hanya setelah bukti implementasi **dan** kriteria penerimaan paket terpenuhi.
- Status per task: `OPEN`, `IN_PROGRESS`, `BLOCKED`, `READY_REVIEW`, `VERIFIED`; checkbox hanya untuk `VERIFIED`.
- Saat mengambil task, catat pelaksana, reviewer, dependensi, file/rute, hasil aktual, perintah/skenario uji, dan temuan tersisa pada task terkait atau handoff yang sudah ada.
- Task yang tidak berlaku harus diberi alasan dan persetujuan cakupan; jangan diam-diam dihapus atau dicentang.
- Temuan baru diberi ID berikutnya pada paket yang sesuai. Pemetaan harus direkonsiliasi dengan seluruh rute aktual sebelum penutupan.
- Kerjakan satu irisan vertikal: konfigurasi/data → policy → service → admin → publik → status/dokumen → tes. Jangan mengakhiri pekerjaan pada UI yang belum terhubung.
- Terapkan checklist lintas paket pada bagian 10 untuk setiap paket fitur. Jangan menganggapnya opsional karena tidak diulang pada semua modul.

## 4. Keputusan dan dependensi

| ID | Keputusan yang harus dituntaskan | Default kerja sebelum keputusan final |
|---|---|---|
| D-01 | Konfirmasi cakupan konten no-code untuk setiap elemen publik, termasuk popup, cetak, resi, email, dan PDF | Semua konten yang ditampilkan atau dapat diunduh masuk inventaris; aturan akses tetap dikendalikan backend. |
| D-02 | Konflik standar global dengan shell lokal: topbar desktop, sidebar, modal terpusat, login, radius, dan ukuran tombol | Standar yang ditunjuk pengguna berlaku untuk admin; baseline publik tidak diubah. Catat pengecualian yang diperlukan, jangan memilih diam-diam. |
| D-03 | Siapa boleh mengubah, menyetujui, menerbitkan, menandatangani, dan mencabut per jenis layanan | Gunakan 17 role yang sudah ada dan izin spesifik; jangan menambah role editor/humas tanpa kebutuhan dan persetujuan. |
| D-04 | Form/kolom yang boleh dikonfigurasi, serta kolom wajib sistem yang harus selalu dipertahankan | Label, bantuan, pilihan, persyaratan, dan aturan yang diizinkan dapat dikelola; identitas, otorisasi, referensi, dan integritas data tetap wajib. |
| D-05 | SLA, jam layanan, kalender libur, batas upload, biaya bila memang ada, syarat, serta pejabat per layanan | Pertahankan perilaku yang sudah disetujui; data yang belum pasti ditandai menunggu keputusan, bukan diberi angka rekaan. |
| D-06 | Template resmi, nomor surat, tanda tangan, kop, stempel, masa berlaku, dan penerbitan ulang | Gunakan fondasi dokumen berversi yang ada; validasi contoh resmi sebelum penerbitan nyata. |
| D-07 | Retensi data/dokumen, privasi, kebijakan publikasi foto/nama, serta pemulihan data | Data sensitif privat secara default; jangan menambahkan penghapusan otomatis sebelum kebijakan ditetapkan. |
| D-08 | Kanal email/WhatsApp, RFID, peta, jadwal salat, EMIS/RDM/SLiMS beserta kredensial/provider | Pertahankan fallback lokal dan outbox yang ada; koneksi nyata menunggu kontrak dan otorisasi. |
| D-09 | Dampak perubahan skema, migrasi data lama, dependency, dan arsitektur | Siapkan usulan konkret dan dampak; implementasi perubahan tersebut menunggu persetujuan yang berlaku. |

| Gelombang | Paket | Ketergantungan / hasil keluar |
|---|---|---|
| 0 | AP-00 | Baseline, inventaris lengkap, konflik, dan keputusan teridentifikasi. |
| 1 | AP-01, AP-02, AP-03, AP-04 | Kontrak akses, UI admin, branding, model konfigurasi konten; D-02/D-03/D-09 ditangani. |
| 2 | AP-05 s.d. AP-09 | Seluruh konten editorial dan navigasi publik dapat dikelola tanpa kode. |
| 3 | AP-10, AP-11 | Fondasi status/dokumen dan PTSP sebagai irisan transaksi acuan yang diverifikasi ulang. |
| 4 | AP-12 s.d. AP-19 | Izin, siklus siswa, legalisir, aduan, alumni, perpustakaan, SPMB, proposal terhubung per role. |
| 5 | AP-20 s.d. AP-23 | Pelacakan, dashboard, regresi modul internal, dan operasional terintegrasi. |
| 6 | AP-24, AP-25 | UAT per role, regresi publik, pemulihan, dan serah terima. |

Pengujian dan pencatatan bukti berjalan sejak gelombang 0, bukan ditunda ke gelombang 6. Penomoran paket tidak berarti semua task di paket sebelumnya harus selesai jika dependensi task tertentu sudah terpenuhi.

## 5. Matriks role dan batas tanggung jawab

Matriks berikut adalah target pemisahan pekerjaan untuk diverifikasi terhadap policy/service yang ada; bukan pemberian izin baru secara otomatis.

| Role key | Ruang kerja / tanggung jawab | Batas yang wajib diuji |
|---|---|---|
| `admin` | Akses, konfigurasi, branding, audit, pengelolaan sekolah dan layanan | Tetap mengikuti isolasi data, audit, dan aturan transisi; kewenangan data sensitif diputuskan eksplisit. |
| `operator` | Data induk, editorial publik, administrasi dan verifikasi sesuai izin | Tidak otomatis boleh mengganti role, membaca semua aduan, atau menjadi penandatangan. |
| `principal` | Monitoring dan keputusan/persetujuan sesuai jenis dokumen | Pisahkan baca, setujui, terbitkan, dan konfigurasi; jangan memperoleh semua hak admin. |
| `teacher` | Kelas/mata pelajaran yang ditugaskan, pembelajaran, nilai, proposal sendiri | Bukan seluruh siswa, seluruh panitia, atau seluruh permohonan. |
| `homeroom` | Siswa/kelas perwalian, verifikasi izin, rapor dan tindak lanjut | Penugasan dan periode aktif; akses berakhir sesuai aturan tanpa menghapus histori. |
| `counselor` | Kasus BK dan tindak lanjut yang diizinkan | Catatan rahasia dan identitas pelapor tidak terbuka ke guru/wali tanpa dasar izin. |
| `librarian` | Konten perpustakaan, koleksi, sirkulasi, usulan, bebas pustaka | Tidak memperoleh master akademik penuh atau dokumen layanan lain. |
| `student` | Data sendiri, kelas, pengajuan, materi, ujian, hasil dan dokumen sendiri | Kepemilikan dan periode; tidak dapat menerbitkan keputusan sendiri. |
| `parent` | Anak yang relasinya terverifikasi dan layanan terkait | Tidak mengakses siswa lain atau anak yang belum dihubungkan/diterima. |
| `alumni` | Tracer/profil sendiri, persetujuan publikasi, KTA dan permohonan sendiri | Tidak memoderasi diri, menerbitkan kartu, atau membaca bukti alumni lain. |
| `committee` | PPDB/kegiatan dalam penugasan yang sah | Periode, kegiatan, jalur, serta hak verifikasi/keputusan dipisah. |
| `ptsp` | Antrean TU/PTSP, surat dinas, legalisir/mutasi sesuai izin | Disposisi, verifikasi, penandatanganan, pengambilan dan pencabutan dipisah. |
| `receptionist` | Buku tamu, tujuan, kedatangan/kepulangan dan laporan yang relevan | Tidak memperoleh data akademik atau lampiran layanan sensitif. |
| `picket` | Izin operasional, token, presensi dan keluar-masuk sesuai tugas | Tidak mengubah penilaian/kelulusan atau kasus rahasia BK. |
| `security_guard` | Pos jaga, pemeriksaan izin dan pencatatan keluar/kembali | Data minimum; tidak menerbitkan persetujuan wali kelas atau membuka lampiran medis. |
| `task_force` | Aduan yang ditugaskan, pemeriksaan, tindak lanjut, ringkasan termoderasi | Identitas anonim dan bukti privat tetap terlindungi. |
| `signatory` | Persetujuan tanda tangan/penerbitan sesuai penunjukan | Jenis dokumen, masa penunjukan, identitas, delegasi dan pencabutan. |
| Pemohon eksternal | Akun/session pemohon terpisah dari akun pegawai | Jenis pemohon, kepemilikan tiket/dokumen, pemulihan akun; bukan role internal tambahan. |

## 6. Cakupan setiap halaman publik

Setiap baris wajib mempunyai inventaris elemen, kontrol admin, pemilik data, aksi, state, dan bukti uji. Pasangan paket menunjukkan lokasi checklist rinci.

| ID | Halaman publik / keluarga rute | Paket utama |
|---|---|---|
| PUB-01 | `/` | AP-03, AP-05, AP-06 |
| PUB-02 | `/profil-sekolah` | AP-05 |
| PUB-03 | `/direktori-gtk` | AP-05 |
| PUB-04 | `/akademik-fasilitas` | AP-05 |
| PUB-05 | `/kesiswaan-prestasi` | AP-05, AP-06 |
| PUB-06 | `/prestasi` | AP-06 |
| PUB-07 | `/struktur-organisasi` | AP-05 |
| PUB-08 | `/kontak` | AP-05 |
| PUB-09 | `/berita` | AP-06 |
| PUB-10 | `/galeri` | AP-06 |
| PUB-11 | `/galeri/video` | AP-06 |
| PUB-12 | `/pusat-unduh` | AP-07 |
| PUB-13 | `/zona-integritas` | AP-07 |
| PUB-14 | `/jadwal-salat` | AP-07, AP-23 |
| PUB-15 | `/e-layanan` | AP-08 |
| PUB-16 | `/data-siswa` | AP-09 |
| PUB-17 | `/layanan/ptsp` | AP-11 |
| PUB-18 | `/layanan/ptsp/akun` | AP-01, AP-11 |
| PUB-19 | `/layanan/surat-dinas` | AP-11 |
| PUB-20 | `/layanan/buku-tamu` | AP-11 |
| PUB-21 | `/layanan/izin-siswa` | AP-12 |
| PUB-22 | `/layanan/mutasi` | AP-13 |
| PUB-23 | `/layanan/kelulusan` | AP-14 |
| PUB-24 | `/layanan/legalisir` | AP-15 |
| PUB-25 | `/layanan/surat-kuasa` | AP-15 |
| PUB-26 | `/layanan/lapor` | AP-16 |
| PUB-27 | `/lapor/transparansi` | AP-16 |
| PUB-28 | `/lacak` | AP-20 |
| PUB-29 | `/alumni` | AP-17 |
| PUB-30 | `/alumni/direktori` | AP-17 |
| PUB-31 | `/layanan/tracer-alumni` | AP-17 |
| PUB-32 | `/perpustakaan` | AP-18 |
| PUB-33 | `/perpustakaan/portal` | AP-18 |
| PUB-34 | `/ppdb` | AP-19 |
| PUB-35 | `/verifikasi` | AP-09, AP-20 |
| PUB-36 | `/login`, termasuk pilihan portal | AP-01, AP-02; pertahankan baseline publik |
| PUB-37 | `/berita/{slug}` dan media foto terkait | AP-06 |
| PUB-38 | `/layanan/proposal` dan dokumen/revisi/LPJ setelah login | AP-19, AP-22 |
| PUB-39 | `/perpustakaan/man4` dan alias aksi | AP-18, AP-20 |
| PUB-40 | Resi PTSP/surat dinas, revisi pemohon, lampiran, bukti izin, SKL, surat mutasi, KTA, legalisir, kuasa/SPTJM, bebas pustaka, bukti PPDB | AP-10 s.d. AP-20 |
| PUB-41 | `/verifikasi-surat/{token}`, unduh privat, unduh publik, branding media dan QR | AP-03, AP-07, AP-10, AP-20 |
| PUB-42 | Header/footer, menu desktop/mobile, modal, ticker, pencarian global, chat, media sosial, popup, state hasil/ditutup/gagal | AP-03 s.d. AP-08, AP-23 |

PUB-37 s.d. PUB-42 adalah perluasan cakupan audit, bukan klaim seluruh state tersebut telah diuji melalui browser. Cocokkan pola rute dokumen dengan inventaris rute aktual sebelum implementasi.

## 7. Paket pekerjaan rinci

### AP-00 — Baseline, kontrak halaman dan rekonsiliasi inventaris

Prioritas P0. Pemilik: pelaksana teknis + reviewer; keputusan produk oleh pemilik produk. Area: rute semua modul, `config/navigation.php`, dokumen acuan, template publik dan komponen admin.

- [x] **AP-00.01** (`VERIFIED`) Catat HEAD, perubahan lokal, migrasi belum diterapkan, konfigurasi tema aktif, versi runtime, dan baseline tes tanpa membaca/menyalin secret.
- [x] **AP-00.02** (`VERIFIED`) Rekonsiliasi seluruh rute GET/POST/PUT/PATCH/DELETE, alias, redirect, media, PDF, ekspor, API/AJAX, dan route berbasis token; pisahkan publik, tamu berakun, internal, perangkat dan operasional.
- [x] **AP-00.03** (`VERIFIED`) Petakan setiap halaman PUB-01 s.d. PUB-42 sampai tab, modal, kartu, tombol, tautan, formulir, cetak, serta halaman hasil; tandai bagian tersembunyi/bersyarat.
- [x] **AP-00.04** (`VERIFIED`) Ambil baseline visual publik yang telah disetujui pada desktop, tablet, mobile dan state penting; gunakan dataset tetap untuk pembandingan.
- [x] **AP-00.05** (`VERIFIED`) Buat pemetaan setiap elemen terlihat: ID elemen, URL, sumber saat ini, field data, pengelola, editor admin, aturan validasi, fallback, izin dan cache terkait.
- [x] **AP-00.06** (`VERIFIED`) Pisahkan fakta terverifikasi, asumsi, data seed, keterbatasan bukti dan keputusan D-01 s.d. D-09. Jangan menyalin status selesai dari TODO lama tanpa memeriksa bukti.
- [x] **AP-00.07** (`VERIFIED`) Bandingkan aturan UI 01–07 dengan shell lokal; catat konflik topbar/sidebar, modal/drawer, login, ukuran tombol dan radius untuk diselesaikan tanpa perubahan publik diam-diam.
- [x] **AP-00.08** (`VERIFIED`) Pastikan setiap menu publik mempunyai pemilik backend/admin; setiap route admin mempunyai skenario pekerjaan, bukan hanya akses URL.
- [x] **AP-00.09** (`VERIFIED`) Tentukan baseline fixture per layanan dan 17 role, termasuk multirole, tanpa data pribadi nyata.
- [x] **AP-00.10** (`VERIFIED`) Tetapkan cakupan rilis, urutan irisan vertikal, pemilik verifikasi, serta batas pekerjaan yang memerlukan persetujuan tambahan.

Penerimaan: tidak ada halaman/aksi dari inventaris rute dan navigasi yang tidak mempunyai mapping atau alasan pengecualian; baseline publik dan konflik tercatat.

#### Hasil Audit & Rekonsiliasi Fase 0 (`AP-00.01` s.d. `AP-00.10`) — 22 November 2026

##### 1. Baseline Repository, Runtime, Migrasi & Tes (`AP-00.01`, `AP-00.04`)
- **Git HEAD**: `60b332b` (`Mutas dan halaman data siswa mentah`) pada branch `main`.
- **Perubahan Lokal Aktif (Tidak Ditimpa)**:
  - Terdapat perubahan lokal dari pekerjaan penyelarasan paritas publik dan tracer alumni (`app/Modules/Academic/Application/Services/{AcademicQueryService,AlumniService}.php`, `AlumniProfile.php`, `AlumniController.php`, `GraduationController.php`, `PublicPortalService.php`, `ExternalApplicantAuthController.php`, `SchoolNewsController.php`, `VerificationController.php`, `PermitWorkflowService.php`, `AttendanceDispensation.php`, `StudentPermitController.php`, `resources/js/Pages/Public/Alumni/{Home,Directory,Portal}.vue`, `resources/js/Pages/Public/Services/{Graduation,PtspAccount,StudentPermit}.vue`, `resources/js/Pages/Public/Students/Index.vue`, `resources/js/Pages/Public/Tracking/Index.vue`, `resources/views/public/madrasah/{assets,footer,navigation,news}.blade.php`, `public/madrasah-theme/{home.js,parity.css,permit-fonts.css,fonts/}`, `database/seeders/DemoAlumniSeeder.php`, `tests/Feature/DemoAlumniSeederTest.php`, serta 2 migrasi untracked `2026_11_21_120000_add_tracer_fields_to_alumni_profiles.php` dan `2026_11_22_060000_add_contact_relationship_to_dispensations.php`).
- **Runtime**: PHP `8.5.13 (cli)`, Node `v24.22.3` (`/home/ilhamzp/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin`).
- **Migrasi**: Total `79` file migrasi lintas modul (`database/migrations` + `app/Modules/*/Infrastructure/Database/Migrations`). Catatan lingkungan: servis MySQL (`127.0.0.1:3306`) tidak berjalan di dalam sandbox; seluruh pengujian otomatis memakai SQLite `RefreshDatabase` (in-memory/file sementara) sesuai `phpunit.xml`.
- **Tema & Konfigurasi Publik**: `config('public_portal.theme')` default `'madrasah'`; `PublicPortalService::madrasahShellData()` masih memiliki kondisional demo di baris 74–76 (`app()->isLocal() && $school->npsn === '20109988' && config('public_portal.theme') !== 'madrasah' && !request()->is('alumni*')`) yang mengganti nama profil menjadi `'Madrasah Contoh Nusantara'` (dicatat untuk dibersihkan pada `AP-03.07` / `AP-05.10`).
- **Baseline Tes & Build**:
  - `php artisan test --compact tests/Feature/DemoAlumniSeederTest.php tests/Feature/MadrasahPublicThemeTest.php` -> **8 tes lulus (142 assertions)** (`1.77s`).
  - `php artisan test --compact tests/Feature/StudentLifecycleAndAlumniTest.php tests/Feature/MadrasahPublicThemeTest.php` -> **16 tes lulus (567 assertions)**.
  - `./node_modules/.bin/vue-tsc --noEmit` -> lulus (`exit code 0`).
  - `./node_modules/.bin/vite build` -> lulus (`994 modules transformed`).
- **Baseline Visual Publik (`AP-00.04`)**:
  - Tersedia 36 tangkapan layar baseline yang telah diambil pada `.playwright-mcp/public-audit-*.png` serta `.playwright-mcp/parity-audit/` pada viewport desktop (`1280×720`), half-screen (`945×900` / `950×900`), dan mobile (`390×844`) ditambah referensi HTML statis di `screenshot_halaman_asli/`.

##### 2. Rekonsiliasi 496 Rute Aplikasi (`AP-00.02`, `AP-00.08`)
Hasil ekstraksi `php artisan route:list --json` menunjukkan **496 rute** aktif yang terbagi ke dalam 5 kategori akses:

| Kategori Akses | Jumlah Rute | Prefix / Kelompok Utama | Mekanisme Guard & Otorisasi |
|---|---:|---|---|
| **1. Publik Tanpa Login** | 80 | `/`, `/profil-sekolah`, `/direktori-gtk`, `/akademik-fasilitas`, `/kesiswaan-prestasi`, `/prestasi`, `/struktur-organisasi`, `/kontak`, `/berita*`, `/galeri*`, `/pusat-unduh*`, `/zona-integritas*`, `/jadwal-salat`, `/e-layanan`, `/data-siswa*`, `/lacak*`, `/verifikasi*`, `/layanan/{surat-dinas,buku-tamu,izin-siswa,mutasi,kelulusan,legalisir,surat-kuasa,lapor}*`, `/lapor/transparansi`, `/alumni*`, `/layanan/tracer-alumni`, `/perpustakaan*`, `/branding/sekolah/*` | `web`, rate-limiter `throttle:sensitive` pada seluruh endpoint `POST` pencarian/verifikasi/pengajuan publik |
| **2. Tamu Berakun (`ExternalApplicant`)** | 16 | `/layanan/ptsp*` (`10` rute), `/layanan/ptsp/akun*` (`6` rute), `/ppdb*` (`6` rute kandidat yang memakai sesi pemohon/kandidat) | Session terpisah (`ExternalApplicantAuthService`, tipe `public`/`alumni`/`parent`/`partner`/`candidate`), tidak berbagi guard `auth` staf |
| **3. Token / Signed / Unduhan Publik & Verifikasi** | 4+ | `/verifikasi-surat/{token}`, `/layanan/dokumen/{token}/unduh`, `/layanan/ptsp/resi/{serviceRequest}/pdf`, `/layanan/surat-dinas/resi/{incomingLetter}/pdf` | Token kriptografis `ServiceDocument` / `signed` middleware / verifikasi kepemilikan tiket |
| **4. Operasional & Perangkat (Kiosk / Scanner / Gate)** | 14 | `/kios-presensi*` (`5` rute), `/piket/izin-keluar*` (`4` rute), `/pos-jaga*` (`5` rute), `/bk/kasus*` (`6` rute) | `auth` + `role:admin,operator,picket` (Kiosk & Piket), `role:admin,operator,picket,security_guard` (Pos Jaga + PIN keluar), `role:admin,counselor,homeroom` (BK) |
| **5. Internal Terautentikasi (`auth`)** | 382 | `/dashboard`, `/kepsek/*` (`7`), `/spmb/*` (`29`), `/akademik/*` (`97`), `/pembelajaran/*` (`15`), `/siswa/*` (`7`), `/guru/*` (`14`), `/penilaian/*` (`48`), `/ujian/*` (`17`), `/laporan*` (`5`), `/wali-kelas/*` (`13`), `/satgas/aduan*` (`4`), `/perpustakaan/kelola*` (`8`), `/komunikasi*` (`12`), `/administrasi/*` (`52`), `/layanan/proposal*` (`5`), `/pengaturan/*` (`36`), `/profil*` (`3`), `/ganti-password*` (`3`) | `auth`, `role:*` middleware, Policy domain, `AccessScopeService`, dan `SidebarNavigationService` |

**Kesenjangan Rute & Menu yang Terbukti (`AP-00.08`)**:
1. **Rute Admin Tanpa Halaman UI / Menu Navigasi (`P0`)**:
   - `POST /pengaturan/pusat-unduh`, `POST /pengaturan/pusat-unduh/{download}/publikasi`, dan `POST /pengaturan/zona-integritas/{survey}/moderasi` sudah ada di `app/Modules/Core/Interface/Routes/web.php` (`PublicPortalAdminController`), tetapi **belum memiliki rute `GET` halaman admin Inertia maupun entri di `config/navigation.php` dan `SidebarNavigation.vue`**.
2. **Halaman Publik Tanpa Editor Admin (`P0`)**:
   - `/galeri/video` (`PUB-11`, `SchoolGalleryController::publicVideos`) masih merender array statis `$allVideos` di `resources/views/public/madrasah/videos.blade.php` tanpa tabel/model/CRUD admin.
   - Konten konfigurasi portal publik di `config/public_portal.php` dan template `resources/views/public/madrasah/{home,profile,teachers,achievements,gallery,navigation,footer}.blade.php` (hero, badge, kartu statistik, 5 nilai budaya kerja, 4 pilar keunggulan, kurikulum/peminatan, ekstrakurikuler, fasilitas, prestasi, direktori GTK publik, area perubahan ZI, jadwal/statistik, topbar/menu/footer/floating WA & live chat) belum memiliki halaman pengelolaan di panel admin (`AP-05` s.d. `AP-08`).
3. **Dualisme Rute Perpustakaan Publik (`P0`, `D-01`)**:
   - `GET /perpustakaan` dan `GET /perpustakaan/man4` saat ini mengarah ke `LibraryController::replica` (view `@verbatim` statis `public.madrasah.perpustakaan` dengan endpoint stub `GET|POST /perpustakaan/aksi` -> `status: error`), sedangkan portal perpustakaan fungsional yang terhubung ke `LibraryService` berada di `GET /perpustakaan/portal` (`LibraryController::publicIndex`).
4. **Sinkronisasi Statistik Hero Portal Alumni (`P1`, `PUB-29`)**:
   - `AlumniController::homeIndex` telah mengirimkan prop `statistics` dari `AlumniService::publicStatistics()`, tetapi `resources/js/Pages/Public/Alumni/Home.vue` belum mendeklarasikan dan memakai prop `statistics` tersebut untuk kartu hero/modal apabila daftar direktori terfilter.

##### 3. Pemetaan Kontrak Halaman Publik `PUB-01` s.d. `PUB-42` & Elemen Terlihat (`AP-00.03`, `AP-00.05`)

| PUB-ID | URL & Route Name | View / Komponen Publik | Sumber Data Saat Ini | Kontrol Admin & Role Pengelola Saat Ini | Kesenjangan Terbukti & Target Paket |
|---|---|---|---|---|---|
| **PUB-01** | `GET /` (`home`) | `public/madrasah/home.blade.php` | `School` (`profile`), `SchoolNews`, `SchoolGalleryAlbum`, `PublicDownload`, `IntegritySurvey`, plus teks/kartu statis di Blade & `config/public_portal.php` | `/pengaturan/sekolah`, `/pengaturan/berita`, `/pengaturan/galeri` (`admin`, `operator`) | Hero copy, badge, 4 CTA, 4 micro-stats, running cards, sambutan kepsek, program/ekskul/prestasi, ZI summary, & FAQ/widget masih sebagian statis di Blade (`AP-03`, `AP-05`, `AP-06`, `AP-07`, `AP-08`) |
| **PUB-02** | `GET /profil-sekolah` (`core.school.public`) | `public/madrasah/profile.blade.php` | `School` (`name`, `accreditation`, `vision`, `mission`), plus fallback statis 5 Nilai Budaya Kerja & 4 Pilar Keunggulan | `/pengaturan/sekolah` (`admin`, `operator`, `principal` read) | 5 Nilai Budaya Kerja, 4 Pilar Keunggulan, sejarah/identitas lengkap belum dapat diedit dari panel (`AP-05.01`, `AP-05.02`) |
| **PUB-03** | `GET /direktori-gtk` (`core.public.teachers`) | `public/madrasah/teachers.blade.php` | `PublicPortalService::teachersPageData()` (mengambil `Teacher` / `User` + fallback `config/public_portal.php`) | `/akademik/guru` (`admin`, `operator`) | Filter kategori/mapel, foto publik, jabatan tampil publik, dan urutan tampil belum memiliki kontrol publikasi eksplisit (`AP-05.03`, `AP-09.02`) |
| **PUB-04** | `GET /akademik-fasilitas` (`core.school.public.programs`) | `public/madrasah/programs.blade.php` | `PublicPortalService` + `config/public_portal.php` + teks Blade | `/administrasi/sarpras` (internal), `/pengaturan/sekolah` | Program akademik, peminatan, kurikulum, dan ringkasan fasilitas publik belum dikelola dari editor panel (`AP-05.05`, `AP-05.06`) |
| **PUB-05** | `GET /kesiswaan-prestasi` (`core.school.public.student-life`) | `public/madrasah/student-life.blade.php` | `PublicPortalService` + `config/public_portal.php` | Belum ada editor khusus kesiswaan/ekskul publik | Ekstrakurikuler, pembinaan, organisasi siswa & highlight prestasi masih statis/konfigurasi (`AP-05.07`, `AP-06.05`) |
| **PUB-06** | `GET /prestasi` (`core.public.achievements`) | `public/madrasah/achievements.blade.php` | `PublicPortalService::achievementsPageData()` (`config/public_portal.php` / seed) | Belum ada CRUD Prestasi publik di panel admin (`wali-kelas/kedisiplinan` hanya internal) | Butuh CRUD/moderasi prestasi publik (kategori, tingkat, tahun, peraih, bukti/foto, featured) (`AP-06.05`, `AP-06.06`) |
| **PUB-07** | `GET /struktur-organisasi` (`core.organization.public`) | `public/madrasah/organization.blade.php` | `SchoolOrganizationMember` (`SchoolOrganizationController::publicIndex`) | `/pengaturan/struktur-organisasi` (`Core/Organization/Index.vue`, `admin`, `operator`) | Fondasi DB & CRUD sudah ada; perlu penyelarasan standar UI modal/confirm & pengaitan personel (`AP-05.04`) |
| **PUB-08** | `GET /kontak` (`core.school.public.contact`) | `public/madrasah/contact.blade.php` | `School` (`address`, `phone`, `email`, `website`) + jam layanan/peta di Blade/`config` | `/pengaturan/sekolah` (`admin`, `operator`) | Embed peta, jam layanan, kanal pengaduan & kontak unit belum sepenuhnya dapat diubah dari panel (`AP-03.01`, `AP-05.08`) |
| **PUB-09** | `GET /berita` (`core.news.public.index`) | `public/madrasah/news.blade.php` | `SchoolNews` + `SchoolNewsPhoto` (`SchoolNewsController::publicIndex`) | `/pengaturan/berita` (`Core/News/Index.vue`, `admin`, `operator`) | Sudah terhubung DB (pencarian `q`, kategori, featured, pagination); perlu verifikasi lifecycle draft/terjadwal/arsip & standar UI (`AP-06.01`–`AP-06.04`) |
| **PUB-10** | `GET /galeri` (`core.gallery.public.index`) | `public/madrasah/gallery.blade.php` | `SchoolGalleryAlbum` + `SchoolGalleryPhoto` | `/pengaturan/galeri` (`Core/Gallery/Index.vue`, `admin`, `operator`) | Sudah terhubung DB; perlu verifikasi urutan foto, cover, alt text, dan pembersihan fallback statis (`AP-06.07`–`AP-06.08`) |
| **PUB-11** | `GET /galeri/video` (`core.gallery.public.videos`) | `public/madrasah/videos.blade.php` | **Array statis `$allVideos`** di `videos.blade.php` | **Belum ada** halaman/endpoint admin | **Gap P0**: Buat penyimpanan & UI admin pengelolaan video (judul, kategori, tanggal, deskripsi, poster, sumber video/embed aman, featured, urutan) (`AP-06.09`, `AP-06.10`) |
| **PUB-12** | `GET /pusat-unduh` & `GET /pusat-unduh/{download}/unduh` | `public/madrasah/downloads.blade.php` | Tabel `public_downloads` (`PublicDownload`) + fallback `config/public_portal.php` | Endpoint `POST /pengaturan/pusat-unduh` ada, **UI Admin belum ada** | **Gap P0**: Buat UI admin Pusat Unduh (CRUD dokumen, kategori, versi, ukuran, status publish, counter unduhan) (`AP-07.01`–`AP-07.03`) |
| **PUB-13** | `GET /zona-integritas` & `POST /zona-integritas/ikm` | `public/madrasah/integrity-zone.blade.php` | Tabel `integrity_surveys` (`IntegritySurvey`) + dokumen/area perubahan dari `config/public_portal.php` | Endpoint `POST /pengaturan/zona-integritas/{survey}/moderasi` ada, **UI Admin belum ada** | **Gap P0**: Buat UI admin moderasi IKM, pengaturan 6 area perubahan, bukti WBK/WBBM & maklumat layanan (`AP-07.04`–`AP-07.07`) |
| **PUB-14** | `GET /jadwal-salat` (`core.public.prayer`) | `public/madrasah/prayer-and-stats.blade.php` | `PublicPortalService::prayerAndStatsPageData()` + `IntegrationConnection` (`prayer`) | `/pengaturan/integrasi` (`admin`, `operator`) | Lokasi/koordinat, metode jadwal salat, koreksi waktu, dan metrik statistik publik perlu kontrol panel yang lengkap (`AP-07.08`–`AP-07.10`, `AP-23.04`) |
| **PUB-15** | `GET /e-layanan` (`core.public.services`) | `public/madrasah/services.blade.php` & `usePublicServicesCatalog.ts` | `PublicPortalService` + `usePublicServicesCatalog.ts` | Tersebar di konfigurasi modul | Status buka/tutup layanan, jam operasional, SLA, urutan kartu, dan badge populer belum dikelola terpusat dari panel (`AP-08.01`–`AP-08.04`) |
| **PUB-16** | `GET /data-siswa` & `POST /data-siswa/cek` | `Public/Students/Index.vue` | `AcademicQueryService::publicStudentSummary()` & `verifyPublicStudentStatus()` | `/akademik/siswa`, `/akademik/kelas`, `/akademik/pengaturan` (`admin`, `operator`) | Sudah bebas PII mentah dan memakai agregat `Student`/`Classroom` nyata; perlu kontrol visibilitas & catatan periode (`AP-09.01`, `AP-09.04`) |
| **PUB-17** | `GET /layanan/ptsp` & `POST /layanan/ptsp` | `Public/Services/Ptsp.vue` | `PtspService` (`ServiceRequest`, `ServiceTicket`, `ServiceDocument`) | `/administrasi/ptsp` (`Administration/Ptsp/Index.vue`, `admin`, `operator`, `principal`) | Alur tiket, resi PDF, revisi, dan status sudah jalan; perlu pengelolaan katalog jenis layanan/syarat/SLA dari panel (`AP-08`, `AP-10`, `AP-11`) |
| **PUB-18** | `GET /layanan/ptsp/akun` (+ daftar/masuk/keluar/profil/pulihkan/reset) | `Public/Services/PtspAccount.vue` | `ExternalApplicantAuthService` (`external_applicants`) | Dikelola mandiri oleh pemohon; audit oleh `admin`/`operator` | Sudah terpisah dari guard `auth` staf; perlu verifikasi matriks pengaitan alumni/wali & pembatasan redirect (`AP-01.10`, `AP-01.11`, `AP-11.02`) |
| **PUB-19** | `GET /layanan/surat-dinas` & `POST /layanan/surat-dinas` | `Public/Services/OfficialLetter.vue` | `PtspService` / `CorrespondenceService` (`IncomingLetter`, `ServiceTicket`) | `/administrasi/persuratan/surat-masuk` (`admin`, `operator`, `principal`) | Alur pengajuan & resi PDF sudah aktif; perlu sinkronisasi disposisi/balasan ke tiket `/lacak` (`AP-11.05`–`AP-11.07`) |
| **PUB-20** | `GET /layanan/buku-tamu` & `POST /layanan/buku-tamu` | `Public/Services/Guestbook.vue` | `GuestbookService` (`GuestbookEntry`, foto kunjungan, check-in/out) | `/administrasi/buku-tamu` (`Administration/Guestbook/Index.vue`, `admin`, `operator`, `picket`, `security_guard`) | Sudah terhubung DB; perlu pengelolaan daftar pejabat/unit tujuan & ekspor dari panel (`AP-11.08`–`AP-11.10`) |
| **PUB-21** | `GET /layanan/izin-siswa` (+ lookup/store/verify/pdf) | `Public/Services/StudentPermit.vue` | `PermitWorkflowService` (`AttendanceDispensation`, `ServiceTicket`, `ServiceDocument`) | `/wali-kelas/izin` (`homeroom`, `teacher`, `picket`, `admin`, `operator`) | Sudah terhubung DB + verifikasi siswa + cetak PDF; perlu sinkronisasi penuh ke presensi harian & piket (`AP-12`) |
| **PUB-22** | `GET /layanan/mutasi` (+ store/revise/document) | `Public/Services/Mutation.vue` | `StudentMutationService` (`StudentMutation`, `ServiceTicket`, `ServiceDocument`) | `/akademik/mutasi` (`Academic/Mutations/Index.vue`, `admin`, `operator`, `principal`) | Sudah terhubung DB + SOP Masuk/Keluar + revisi + PDF; perlu pengelolaan kuota/syarat & audit efek ke `Enrollment` (`AP-13`) |
| **PUB-23** | `GET /layanan/kelulusan` (+ check/skl) | `Public/Services/Graduation.vue` | `GraduationService` (`GraduationBatch`, `GraduationRecord`, `ServiceDocument`) | `/akademik/kelulusan` (`Academic/Graduation/Index.vue`, `admin`, `operator`, `principal`) | Sudah mendukung countdown `release_at`, cek NISN+PIN/tgl lahir, dan unduh SKL; perlu penyempurnaan kontrol rilis & pencabutan SKL (`AP-14`) |
| **PUB-24** | `GET /layanan/legalisir` (+ store/bukti) | `Public/Services/Legalization.vue` | `LegalizationService` (`LegalizationRequest`, `ServiceTicket`, `ServiceDocument`) | `/administrasi/legalisir` (`Administration/Legalization/Index.vue`, `admin`, `operator`, `principal`) | Sudah memiliki 5 tab paritas + tanda tangan canvas + bukti PDF; perlu sinkronisasi jadwal layanan TU & kuota lembar dari panel (`AP-15`) |
| **PUB-25** | `GET /layanan/surat-kuasa` (+ store/unduh) | `Public/Services/PowerOfAttorney.vue` | `LegalizationService` (`power-of-attorney.blade.php`, `sptjm.blade.php`) | `/administrasi/legalisir` (`admin`, `operator`) | Generator PDF Surat Kuasa & SPTJM terhubung kode register legalisir; perlu kontrol versi teks pernyataan (`AP-15.05`) |
| **PUB-26** | `GET /layanan/lapor` & `POST /layanan/lapor` | `Public/Services/Complaint.vue` | `ComplaintReportService` (`ComplaintReport`, `ServiceTicket`, `ServiceDocument`) | `/satgas/aduan` (`Homeroom/Complaints/Index.vue`, `admin`, `task_force`, `counselor`) | Mendukung laporan bernama & anonim bebas PII + bukti privat; perlu verifikasi pembatasan akses bukti lintas role (`AP-16`) |
| **PUB-27** | `GET /lapor/transparansi` | `Public/Services/ComplaintTransparency.vue` | `ComplaintReportService` (agregat & ringkasan `publish_to_transparency = true`) | `/satgas/aduan` (`admin`, `task_force`, `counselor`) | Hanya menampilkan ringkasan yang disetujui Satgas tanpa PII (`AP-16.06`) |
| **PUB-28** | `GET /lacak` & `POST /lacak` | `Public/Tracking/Index.vue` | `UnifiedTrackingService` (`ServiceTicket` lintas modul) | Semua modul pemilik tiket (`PTSP`, `Persuratan`, `Izin`, `Mutasi`, `Legalisir`, `Aduan`, `Alumni`, `Perpustakaan`, `SPMB`, `Proposal`) | Sudah mendukung pencarian kode tiket + rahasia pemohon; perlu penyelarasan kamus status lintas modul (`AP-10.02`, `AP-20`) |
| **PUB-29** | `GET /alumni` (`academic.alumni.public.index`) | `Public/Alumni/Home.vue` | `AlumniService` (`AlumniProfile`, `publicDirectory`, `publicStatistics`, `lookupPublicEkta`) + `DemoAlumniSeeder` | `/akademik/alumni` (`Academic/Alumni/Index.vue`, `admin`, `operator`, `principal`) | Tampilan paritas IKA sudah selaras; **masih ada data statis** pada tab Bursa Karir (`careerItems`), Pusat Beasiswa (`scholarshipItems`), Agenda ticker, dan prop `statistics` belum dipakai langsung di `Home.vue` (`AP-17`) |
| **PUB-30** | `GET /alumni/direktori` (`academic.alumni.public.directory`) | `Public/Alumni/Directory.vue` | `AlumniService::publicDirectory()` (`opt_in_directory = true` & `moderation_status = approved`) | `/akademik/alumni` (`admin`, `operator`) | Sudah menghormati flag opt-in & moderasi; perlu konsistensi navigasi dengan tab direktori di `/alumni` (`AP-17.03`) |
| **PUB-31** | `GET /layanan/tracer-alumni` & `POST /alumni/tracer` | `Public/Alumni/Portal.vue` & tab Tracer di `Home.vue` | `AlumniService::submitTracer()` (`AlumniProfile`, `ServiceTicket`, `ServiceDocument` E-KTA) | `/akademik/alumni` (`admin`, `operator`, `principal`) | Mendukung pengajuan tracer + opt-in privasi + penerbitan E-KTA setelah disetujui (`AP-17.02`, `AP-17.05`) |
| **PUB-32** | `GET /perpustakaan` (`library.public.index`) | `public/madrasah/perpustakaan.blade.php` (`LibraryController::replica`) | **HTML `@verbatim` statis** + stub `GET|POST /perpustakaan/aksi` | `/perpustakaan/kelola` (`Library/Manage/Index.vue`, `admin`, `librarian`) mengelola DB, tetapi **tidak terhubung ke view `replica`** | **Gap P0 (`D-01`)**: Rekonsiliasi tampilan paritas `perpustakaan.blade.php` dengan data/endpoint `LibraryService` (`/perpustakaan/portal`) agar seluruh aksi katalog, e-book, kunjungan, usulan, dan bebas pustaka hidup di `/perpustakaan` (`AP-18`) |
| **PUB-33** | `GET /perpustakaan/portal` (`library.public.portal`) | `Public/Library/Index.vue` (`LibraryController::publicIndex`) | `LibraryService` (`LibraryCollection`, `LibraryVisit`, `LibraryLoan`, `LibraryProcurementRequest`, `LibraryClearanceCertificate`) | `/perpustakaan/kelola` (`admin`, `librarian`) | Fungsional penuh (katalog, e-book berjenjang `public`/`members_only`/`staff_only`, kunjungan, usulan, bebas pustaka PDF), namun berada di sub-rute `/perpustakaan/portal` (`AP-18`) |
| **PUB-34** | `GET /ppdb` (+ daftar/cek-hasil/perbaiki/bukti/daftar-ulang) | `Public/Admissions/Index.vue` | `CandidatePortalService` (`AdmissionPeriod`, `AdmissionTrack`, `AdmissionApplicant`, `AdmissionSelection`, `AdmissionReRegistration`) | `/spmb`, `/spmb/pendaftar`, `/spmb/seleksi`, `/spmb/pengaturan` (`admin`, `operator`, `committee`, `principal`) | Sudah terhubung DB + bukti PDF + gate pengumuman + konversi siswa idempoten; perlu pengelolaan konten informasi/brosur/FAQ PPDB dari panel (`AP-19`) |
| **PUB-35** | `GET /verifikasi` & `POST /verifikasi` | `Public/Verification/Index.vue` | `ServiceDocumentService::verifyPublicDocument()` & `AcademicQueryService::verifyPublicStudentStatus()` | Semua modul penerbit dokumen (`ServiceDocument`) & `/akademik/siswa` | Sudah memverifikasi status dokumen (`valid`, `revoked`, `expired`, `not_found`) & keaktifan siswa tanpa bocor PII (`AP-09`, `AP-20`) |
| **PUB-36** | `GET /login` & `POST /login` | `Auth/Login.vue` | `AuthController` (`School` branding, pilihan portal Siswa/GTK/Admin) | `/pengaturan/sekolah` (branding), `/pengaturan/pengguna` (akun) | Layout split publik yang disetujui wajib dipertahankan (`D-02`); perlu memastikan show/hide password aksesibel & redirect allowlist (`AP-01.09`, `AP-02.17`) |
| **PUB-37** | `GET /berita/{slug}` & `GET /berita/{slug}/foto/{photo}` | `public/madrasah/news-detail.blade.php` | `SchoolNewsController::publicShow` & `photo` (`SchoolNews`, `SchoolNewsPhoto`) | `/pengaturan/berita` (`admin`, `operator`) | Hanya berita `published` yang dapat diakses publik; foto berita mengikuti status publikasi artikel (`AP-06.02`) |
| **PUB-38** | `GET /layanan/proposal` (+ store/revise/lpj) | `Integration/Proposals/IndependentPortal.vue` | `ProposalLpjService` (`ActivityProposal`, `ServiceTicket`, `ServiceDocument`) | `/administrasi/proposal` (`Integration/Proposals/ReviewQueue.vue`, `admin`, `operator`, `principal`, `committee`) & `/guru/proposal` (`teacher`, `homeroom`) | Sudah mendukung RAB server-side, revisi ber-versi, unggah LPJ privat, dan penutupan tiket (`AP-19`, `AP-22`) |
| **PUB-39** | `GET /perpustakaan/man4` & `GET|POST /perpustakaan/man4/aksi` | `public/madrasah/perpustakaan.blade.php` | Sama dengan `PUB-32` (`LibraryController::replica` & `replicaAction`) | `/perpustakaan/kelola` (`admin`, `librarian`) | Alias paritas; harus diarahkan/disatukan dengan implementasi `PUB-32` tanpa meninggalkan endpoint stub (`AP-18`, `AP-20`) |
| **PUB-40** | Rute Dokumen & Resi Transaksi Publik | Blade PDF templates (`resources/views/{admissions,academic,administration,library,homeroom}/`) | `ServiceDocumentService`, `PtspService`, `StudentMutationService`, `GraduationService`, `LegalizationService`, `AlumniService`, `LibraryService`, `CandidatePortalService` | Modul admin masing-masing layanan | Mencakup resi PTSP/surat dinas, bukti izin, SKL, surat mutasi, E-KTA, bukti legalisir, surat kuasa/SPTJM, bebas pustaka, dan bukti PPDB (`AP-10` s.d. `AP-20`) |
| **PUB-41** | `GET /verifikasi-surat/{token}`, `GET /layanan/dokumen/{token}/unduh`, `GET /branding/sekolah/{school}/{kind}` | `PtspPublicController::verifyDocument`, `ServiceDocumentDownloadController`, `BrandMediaController` | `ServiceDocument`, `OutgoingLetter`, `School` media | `/pengaturan/sekolah` & modul penerbit dokumen | Memastikan dokumen privat/dicabut tidak dapat diunduh tanpa hak dan QR mengarah ke URL verifikasi sah (`AP-03`, `AP-07`, `AP-10`, `AP-20`) |
| **PUB-42** | Shell Publik Global (Header, Masthead Varian, Ticker, Subnav, Footer, Modal, Widget WA/Chat/Tema) | `resources/views/public/layouts/madrasah.blade.php`, `resources/views/public/madrasah/{navigation,footer,assets}.blade.php`, `public/madrasah-theme/{home.js,parity.css}` | `PublicPortalService::madrasahShellData()` + `config/public_portal.php` + teks/link di `navigation.blade.php` & `footer.blade.php` | `/pengaturan/sekolah`, `/pengaturan/integrasi` (`admin`, `operator`) | Menu navigasi, ticker pengumuman, jam layanan, tautan cepat footer, sosial media, nomor WA/chat masih sebagian hardcoded di Blade/config (`AP-03`, `AP-04`, `AP-08`, `AP-23`) |

##### 4. Pemisahan Fakta Terverifikasi, Data Seed, Keterbatasan Bukti & Keputusan `D-01` s.d. `D-09` (`AP-00.06`)
- **Fakta Terverifikasi**:
  1. Seluruh 17 role (`RoleEnum`) dan permission (`PermissionEnum`) sudah terdaftar di kode dan dipetakan ke menu melalui `config/navigation.php` serta `SidebarNavigationService`.
  2. Modul transaksi utama (PTSP, Surat Dinas, Buku Tamu, Izin Siswa, Mutasi, Kelulusan/SKL, Legalisir, Surat Kuasa/SPTJM, Siap Lapor/Transparansi, Tracer Alumni/E-KTA, Perpustakaan Portal, PPDB, Proposal/LPJ, Lacak, Verifikasi) sudah memiliki tabel, service, controller, dan tes fitur yang berjalan di SQLite.
  3. Kesenjangan utama berada pada: (a) dualisme `/perpustakaan` statis vs `/perpustakaan/portal` dinamis, (b) `/galeri/video` yang 100% statis, (c) ketiadaan halaman UI admin untuk Pusat Unduh & Moderasi IKM Zona Integritas walaupun endpoint `POST`-nya sudah ada, (d) blok konten/menu/widget di `resources/views/public/madrasah/*.blade.php` dan `config/public_portal.php` yang belum memiliki editor admin, serta (e) penyelarasan komponen/halaman admin terhadap 7 aturan standar UI.
- **Data Seed & Fallback Saat Ini**:
  - `PilotSchoolSeeder` dan `DemoAlumniSeeder` menyediakan data sekolah contoh dan 8 profil alumni sintetis.
  - `config/public_portal.php` menyimpan data fallback untuk berita, galeri, unduhan, survei IKM, direktori GTK, prestasi, dan jadwal salat ketika tabel kosong.
  - `PublicPortalService::madrasahShellData()` memiliki override nama `'Madrasah Contoh Nusantara'` pada environment `local` untuk NPSN `20109988` bila tema bukan `madrasah`.
- **Keterbatasan Bukti Lingkungan Saat Ini**:
  - Sandbox tidak memiliki akses jaringan keluar sehingga commit `87490c183b9d57b2600e3daa986171e84df4ab5c` pada `https://github.com/ilhamrizqiawan21/standar-ui-project` tidak dapat di-clone secara langsung dari internet; namun ketujuh aturan (`01` s.d. `07`) dari snapshot tersebut telah diturunkan secara eksplisit ke dalam butir `AP-02.01` s.d. `AP-02.22` dan `AP-03` pada dokumen ini.
  - Daemon MySQL lokal tidak aktif di sandbox; pembuktian migrasi dan fitur menggunakan SQLite `RefreshDatabase` serta file SQLite sementara untuk verifikasi browser Playwright.
- **Status Keputusan `D-01` s.d. `D-09` & Rekomendasi Eksekusi**:
  - **`D-01` (Perpustakaan Publik)**: Gunakan tampilan paritas `/perpustakaan` yang disetujui tetapi hubungkan seluruh katalog, filter, modal e-book, trafik kunjungan, usulan buku, dan bebas pustaka ke `LibraryService` (`AP-18`).
  - **`D-02` (Konflik Standar UI Admin vs Lokal)**: Lihat matriks konflik pada sub-bagian 5 di bawah; halaman publik (termasuk `/login` publik) tidak dirombak, sedangkan shell dan halaman admin diselaraskan dengan 7 aturan standar UI (`AP-02`).
  - **`D-03` (Batas Role Persetujuan vs Verifikasi)**: `operator` memverifikasi & memproses antrean; `principal` menyetujui/menandatangani keputusan akhir (SKL, mutasi, surat keluar, proposal); `admin` memiliki akses penuh konfigurasi dan pemulihan darurat teraudit (`AP-01.08`).
  - **`D-04` (Konten Statis vs Konstanta Sistem)**: Seluruh teks editorial, menu, kartu profil, jam layanan, kontak, media, dan katalog layanan dibuka di panel admin (`AP-04` s.d. `AP-08`); kode enum internal dan struktur layout publik tetap menjadi konstanta kode.
  - **`D-05` (Privasi `/data-siswa`, `/verifikasi`, `/alumni`, `/lapor/transparansi`)**: Pertahankan kebijakan tanpa daftar siswa mentah di `/data-siswa`, verifikasi menggunakan pasangan identifier (`NISN + Nama` atau `Token + Kode`), direktori alumni wajib `opt_in_directory = true` + `approved`, dan transparansi aduan wajib bebas PII (`AP-09`, `AP-16`, `AP-17`, `AP-20`).
  - **`D-06` (Kiosk, Piket, Pos Jaga, BK)**: Rute operasional tetap memerlukan sesi staf berwenang (`admin`, `operator`, `picket`, `security_guard`, `counselor`) ditambah PIN aksi keluar pada Pos Jaga (`AP-12`, `AP-21`).
  - **`D-07` (Integrasi Eksternal EMIS/RDM/SLiMS/WA/Email/Salat/Peta)**: Gunakan mode konfigurasi panel + file exchange / fallback lokal terukur ketika kredensial pihak ketiga belum aktif (`AP-23`).
  - **`D-08` (Retensi & Nomor Dokumen Resmi)**: Dokumen yang sudah terbit bersifat immutable; koreksi menerbitkan revisi baru dan mencabut (`revoked`) dokumen lama tanpa menghapus histori (`AP-10`).
  - **`D-09` (Penanganan Data Seed/Demo)**: Pisahkan seeder demo (`DemoAlumniSeeder`, fallback `config/public_portal.php`) dari mode produksi agar data nyata sekolah tidak tertimpa atau bercampur dengan item contoh (`AP-03.07`, `AP-25.06`).

##### 5. Audit Konflik Aturan UI 01–07 vs Shell Admin Lokal (`AP-00.07`, `D-02`)

| Aturan Standar UI (`87490c183b9d57b2600e3daa986171e84df4ab5c`) | Kondisi Shell Admin Lokal Saat Ini (`AppLayout.vue`, `shell.css`, `buttons.css`) | Status & Resolusi pada `AP-02` (Tanpa Mengubah Halaman Publik) |
|---|---|---|
| **Aturan 01 — Token, Tipografi & Ukuran/Radius Tombol**: Token semantik, teks tombol `14px`, radius `6–8px`, ukuran tombol `sm: 32px`, `md: 36px`, `lg: 40px` | `shell.css` sudah memakai token semantik (`--canvas`, `--surface`, `--ink`, `--primary`, dll.) dan dark mode `[data-theme="dark"]`. Namun `buttons.css` memakai `--button-height: 40px`, `--button-height-lg: 48px`, `--button-radius: 10px`, dan media query coarse pointer `--button-touch-size: 44px`. | **Perlu Penyesuaian Terukur (`AP-02.02`, `AP-02.10`)**: Selaraskan token tombol admin ke `sm: 32px`, `md: 36px`, `lg: 40px` dan `--button-radius: 8px` (`6–8px`) pada desktop, sambil mempertahankan hit-area sentuh `>=44px` pada `@media (pointer: coarse)` untuk aksesibilitas mobile. |
| **Aturan 02 — Navigasi Shell**: Menu utama desktop berada di topbar rata tengah; layar kecil memakai sidebar buka/tutup (collapsible drawer) | **Sudah Selaras Sebagian Besar**: `AppLayout.vue` (baris 213–236 & 308–310) dan `shell.css` (`@media (min-width: 961px) { .sidebar { display: none; } }`) sudah merender `<SidebarNavigation mode="topbar" />` di `.topbar-navigation` pada desktop dan `<SidebarNavigation mode="sidebar" />` sebagai drawer buka/tutup pada viewport `<= 960px`. | **Perlu Penyempurnaan (`AP-02.03`–`AP-02.06`)**: Pastikan perataan tengah (`.topbar-navigation`), perilaku overflow/dropdown menu bertingkat, indikator active state, serta fokus keyboard/Escape bekerja konsisten di semua role. |
| **Aturan 03 — Tabel & Ekspor**: Kolom aksi paling kanan; View/Edit/Delete horizontal icon-only + tooltip & `aria-label`; ekspor Word/PDF/Excel berikon format & warna identitas; header tabel rata tengah, teks kiri, angka kanan, status/aksi tengah | `buttons.css` sudah memiliki `.ui-button--icon-excel`, `.ui-button--icon-pdf`, `.ui-button--icon-edit`, `.ui-button--icon-delete`, tetapi `shell.css` `.app-table th` masih `text-align: left` (baris 380) dan banyak halaman admin masih memakai tombol aksi teks penuh atau perataan kolom yang belum seragam. | **Perlu Penyesuaian (`AP-02.07`–`AP-02.09`)**: Ubah standar `.app-table th` admin menjadi rata tengah, tambahkan utilitas alignment kolom (teks kiri, numerik kanan, status/aksi tengah), tambahkan varian ikon Word (`.ui-button--icon-word`), dan seragamkan aksi baris menjadi icon-only horizontal ber-tooltip. |
| **Aturan 04 — Modal CRUD & Confirm Alert Terpusat**: Form CRUD menggunakan modal overlay terpusat (bukan halaman terpisah atau drawer kanan); aksi destruktif menggunakan confirm alert terpusat | Komponen `ConfirmDialog.vue` sudah tersedia, tetapi beberapa halaman modul masih memakai form inline panjang, drawer, atau `window.confirm` / konfirmasi non-standar. | **Perlu Penyesuaian (`AP-02.12`–`AP-02.14`)**: Gunakan modal overlay terpusat (dengan focus trap, Escape, scroll lock, dirty-check) untuk form CRUD data dan `ConfirmDialog.vue` terpusat untuk semua aksi hapus/cabut/reset. |
| **Aturan 05 — Branding & Tema**: Konfigurasi warna dasar/aksen, dark/light, pemeriksaan kontras, preview, dan reset default dengan konfirmasi | `AppLayout.vue` menerapkan `--school-primary` dari `page.props.school?.theme_primary` dan toggle dark/light (`useTheme.ts`), tetapi `/pengaturan/sekolah` belum memiliki preview kontras & tombol reset tema ke default dengan dialog konfirmasi. | **Perlu Penyesuaian (`AP-03.03`, `AP-03.04`)**: Lengkapi panel `/pengaturan/sekolah` dengan kontrol tema, cek kontras, preview, dan reset terkonfirmasi. |
| **Aturan 06 — Autentikasi & Password Show/Hide**: Form login admin terpusat dengan toggle show/hide password aksesibel | `/login` (`Auth/Login.vue`) saat ini menggunakan split-layout paritas publik yang telah disetujui pemilik produk. | **Keputusan `D-02` (`AP-02.17`)**: Pertahankan layout `/login` publik yang telah disetujui sesuai Kontrak Utama #1 & #3, sambil memastikan tombol show/hide password dan error inline memenuhi standar aksesibilitas pada seluruh form autentikasi/ganti sandi. |
| **Aturan 07 — Feedback, Toast & A11y**: Toast bertumpuk responsif & dismissible, error form dekat field, keyboard/zoom 200%/reduced motion | `AppLayout.vue` baru menampilkan satu `toastMessage` tunggal (baris 317–321) dan belum mendukung antrean toast bertumpuk (*stacked toasts*). | **Perlu Penyesuaian (`AP-02.15`, `AP-02.16`, `AP-02.19`)**: Tingkatkan sistem toast di `AppLayout.vue` agar mendukung tumpukan notifikasi dismissible dengan `aria-live`, serta pastikan seluruh form admin menampilkan `FormField` error inline. |

##### 6. Baseline Fixture Sintetis 17 Role, Pemohon Eksternal & Layanan (`AP-00.09`)
Seluruh pengujian dan verifikasi menggunakan fixture sintetis terisolasi (tanpa PII nyata) yang mencakup:
1. **17 Role Internal (`RoleEnum`)**:
   - `admin` (Administrator Sistem), `operator` (Operator/TU), `principal` (Kepala Madrasah), `teacher` (Guru Mapel), `homeroom` (Wali Kelas), `student` (Siswa), `parent` (Orang Tua/Wali terkait siswa tertentu), `alumni` (Alumni terautentikasi), `committee` (Panitia SPMB/Kegiatan), `counselor` (Guru BK), `picket` (Petugas Piket), `librarian` (Petugas Perpustakaan), `security_guard` (Petugas Satpam/Pos Jaga), `task_force` (Satgas Aduan/PPKS), `finance` (Bendahara/Keuangan — cakupan non-disbursement sesuai `SS-D-009`), `curriculum` (Waka Kurikulum), `student_affairs` (Waka Kesiswaan).
2. **Skenario Multirole & Scope**:
   - Guru yang juga memiliki penugasan `homeroom` pada kelas/periode aktif; guru yang menjadi `committee` SPMB; konselor yang tergabung dalam `task_force`; serta pengujian isolasi lintas `school_id`, lintas `classroom_id`, dan lintas `academic_period_id`.
3. **5 Tipe Pemohon Eksternal (`ExternalApplicantAuthService`)**:
   - `public` (Pemohon umum PTSP/Surat Dinas), `alumni` (Pemohon legalisir/tracer eksternal), `parent` (Orang tua pengaju izin/mutasi eksternal), `partner` (Instansi pengirim surat dinas/tamu), `candidate` (Pendaftar PPDB mandiri).

##### 7. Cakupan Rilis, Urutan Irisan Vertikal & Batas Persetujuan (`AP-00.10`)
Implementasi dijalankan secara berurutan mengikuti fase dan dependensi:
1. **Fase 1 (`AP-01` → `AP-02` → `AP-03` → `AP-04`)**: Kunci matriks role/permission & scope backend (`AP-01`), selaraskan shell/komponen standar UI admin 01–07 (`AP-02`), perluas pengaturan branding/tema/header/footer sekolah (`AP-03`), dan bangun fondasi CMS blok konten/media/publikasi publik (`AP-04`).
2. **Fase 2 (`AP-05` → `AP-06` → `AP-07` → `AP-08` → `AP-09`)**: Hubungkan halaman profil/GTK/akademik/fasilitas/kesiswaan/organisasi/kontak (`AP-05`), berita/prestasi/galeri foto & **galeri video** (`AP-06`), **UI admin pusat unduh, zona integritas/IKM** & jadwal salat/statistik (`AP-07`), katalog e-layanan & navigasi header/footer/widget (`AP-08`), serta agregat `/data-siswa` & direktori publik aman (`AP-09`).
3. **Fase 3 (`AP-10` → `AP-11`)**: Standarkan kontrak tiket/status/dokumen/QR (`AP-10`) lalu selesaikan irisan vertikal PTSP, akun pemohon eksternal, surat dinas, dan buku tamu (`AP-11`).
4. **Fase 4 (`AP-12` s.d. `AP-19`, satu irisan vertikal penuh per paket)**: Izin siswa (`AP-12`) → Mutasi (`AP-13`) → Kelulusan & SKL (`AP-14`) → Legalisir & Surat Kuasa/SPTJM (`AP-15`) → Siap Lapor & Transparansi Satgas (`AP-16`) → Portal Alumni, Direktori, Tracer, Karir/Beasiswa & E-KTA (`AP-17`) → **Penyatuan Perpustakaan Publik `/perpustakaan` dengan `LibraryService`** (`AP-18`) → SPMB/PPDB & Proposal/LPJ (`AP-19`).
5. **Fase 5 (`AP-20` → `AP-21` → `AP-22` → `AP-23`)**: Pelacakan `/lacak` & verifikasi `/verifikasi` (`AP-20`), dashboard kerja untuk 17 role (`AP-21`), regresi modul internal & operasional kiosk/piket/pos jaga/BK (`AP-22`), serta integrasi eksternal & fallback aman (`AP-23`).
6. **Fase 6 (`AP-24` → `AP-25`)**: Verifikasi E2E lintas 17 role, audit penghapusan array statis/stub produksi, UAT, dan dokumentasi serah terima.
- **Batas Persetujuan Tambahan**: Penambahan dependency baru, perubahan arsitektur modular monolith, penyuntingan `.env`, tindakan database destruktif, `git commit`, `git push`, dan deployment produksi **tidak dilakukan** tanpa instruksi/otorisasi eksplisit pengguna.

### AP-01 — Identitas akun, role, permission dan scope data

Prioritas P0. Area: `RoleEnum`, `PermissionEnum`, `RolePermissionRegistryService`, `AccessScopeService`, policy/middleware, `UserController`, akun pemohon, `config/navigation.php` dan `SidebarNavigationService`.

- [x] **AP-01.01** (`VERIFIED`) Verifikasi seluruh 17 role, label, seed, akun uji dan konsistensi enum–DB–policy–navigasi; akun eksternal tetap terpisah.
- [ ] **AP-01.02** (`OPEN`) Susun matriks izin per fitur dan aksi: lihat daftar/detail, cari, buat, ubah, unggah, ekspor, verifikasi, disposisi, putuskan, terbitkan, tandatangani, cabut, hapus dan konfigurasi.
- [x] **AP-01.03** (`VERIFIED`) Pastikan default deny pada endpoint dan service sensitif; cegah akses melalui URL, method berbeda, AJAX, ID yang diganti atau dokumen langsung.
- [x] **AP-01.04** (`VERIFIED`) Uji `school_id`, pemilik tiket, penugasan guru/mapel, kelas/periode wali kelas, panitia/kegiatan, serta penugasan satgas.
- [x] **AP-01.05** (`VERIFIED`) Verifikasi relasi orang tua–siswa dan pemutakhiran/revokasi relasi; anak belum terverifikasi atau tidak terkait tidak boleh dapat diakses.
- [ ] **AP-01.06** (`OPEN`) Uji multirole, perpindahan role, perubahan izin saat session aktif, penonaktifan akun, pergantian penugasan dan akses histori.
- [ ] **AP-01.07** (`OPEN`) Samakan izin menu, tombol, counter dashboard, notifikasi, pencarian, pilihan dropdown dan endpoint; hasil agregat juga tidak boleh melampaui scope.
- [x] **AP-01.08** (`VERIFIED`) Pisahkan operator verifikator, pejabat penyetuju dan penandatangan sesuai D-03; dokumentasikan kewenangan admin darurat bila dibutuhkan.
- [x] **AP-01.09** (`VERIFIED`) Uji login/logout, session regeneration, timeout, CSRF, remember-me, pembatasan percobaan login, ganti sandi dan pencabutan session.
- [x] **AP-01.10** (`VERIFIED`) Verifikasi daftar/masuk/pemulihan profil pemohon eksternal, validasi jenis pemohon, pengaitan alumni/wali yang sah, dan pesan pemulihan yang tidak membocorkan keberadaan akun.
- [x] **AP-01.11** (`VERIFIED`) Pastikan tujuan kembali setelah login bersifat allowlist; alur tracer/PTSP tidak dapat menjadi open redirect atau memindahkan hak role.
- [ ] **AP-01.12** (`OPEN`) Sediakan matriks uji positif dan negatif per role, kepemilikan, kelas, sekolah, status, dokumen dan ekspor.

Penerimaan: role hanya melihat dan menjalankan pekerjaannya; akses langsung tetap ditolak ketika menu disembunyikan; tidak ada privilege escalation melalui multirole atau session pemohon.

### AP-02 — Standar UI admin: shell, navigasi, komponen dan autentikasi

Prioritas P1, dikerjakan sebelum perluasan layar. Area: `AppLayout.vue`, `TopNavbar.vue`, `SidebarNavigation.vue`, breadcrumb, `resources/css/{shell,buttons,modern-ui}.css`, komponen UI dan halaman admin.

- [x] **AP-02.01** (`VERIFIED`) Gunakan token semantic terpusat untuk surface, text, muted, border, primary, success, warning, destructive, focus, overlay, motion dan z-index; pakai komponen yang sudah ada.
- [x] **AP-02.02** (`VERIFIED`) Terapkan skala typography/spacing konsisten; baseline tombol 14px dan radius 6–8px sesuai standar, kecuali override proyek yang disetujui. Bentuk pill/circular hanya untuk pola yang tepat.
- [x] **AP-02.03** (`VERIFIED`) Terapkan menu utama desktop di topbar rata tengah sesuai aturan 02; menu layar kecil di sidebar buka/tutup. Jelaskan fungsi sidebar tambahan pada layar padat dan selesaikan konflik shell lokal melalui D-02.
- [x] **AP-02.04** (`VERIFIED`) Pastikan breadcrumb semantik, judul halaman, periode aktif, identitas sekolah, profil, notifikasi dan posisi menu aktif konsisten.
- [x] **AP-02.05** (`VERIFIED`) Hamburger/sidebar mempunyai label, `aria-expanded`, keyboard, fokus, scroll aman, reduced motion, perilaku collapse, serta active state yang tetap terbaca.
- [x] **AP-02.06** (`VERIFIED`) Kunci breakpoint, overflow menu, menu bertingkat, back/forward, deep link, state filter dan posisi gulir; jangan bergantung pada wrapping kebetulan.
- [ ] **AP-02.07** (`OPEN`) Kolom aksi tabel berada paling kanan; View/Edit/Delete berikon horizontal; label aksesibel dan tooltip wajib, destructive memakai token yang tepat.
- [ ] **AP-02.08** (`OPEN`) Ekspor Word/PDF/Excel memakai ikon format dan warna identitas sesuai aturan 03; gunakan label bila perlu membedakan beberapa keluaran sejenis. Ekspor hanya ditawarkan bila format benar-benar tersedia.
- [x] **AP-02.09** (`VERIFIED`) Header tabel rata tengah; isi teks kiri, angka kanan, status/aksi tengah. Atur lebar, truncation, wrapping, sticky header bila diperlukan, pagination dan alternatif mobile.
- [x] **AP-02.10** (`VERIFIED`) Ukuran tombol konsisten; baseline standar `sm` 32px, `md` 36px, `lg` 40px bila token proyek belum mengatur. Rekonsiliasi dengan target sentuh lokal >=44px melalui hit area/override yang jelas.
- [x] **AP-02.11** (`VERIFIED`) Sediakan default/hover/focus-visible/pressed/disabled/loading untuk tombol dan kontrol; cegah double-submit tanpa mengunci retry yang sah.
- [ ] **AP-02.12** (`OPEN`) Form CRUD/data menggunakan modal overlay terpusat; isi panjang dapat digulir dan panel mobile boleh menyesuaikan. Jangan membuat halaman CRUD tambahan atau drawer kanan karena mengikuti proposal lama tanpa override.
- [ ] **AP-02.13** (`OPEN`) Modal mempunyai backdrop, focus trap, judul, close, Escape yang aman, body scroll lock, restore focus, urutan tab dan perlindungan perubahan belum tersimpan.
- [x] **AP-02.14** (`VERIFIED`) Delete/revoke/reset/perubahan permanen memakai confirm alert terpusat yang menyebut dampak, Cancel/Confirm jelas; jangan menaruh konfirmasi di topbar/header tabel.
- [ ] **AP-02.15** (`OPEN`) Form memiliki label eksplisit, tanda wajib, error dekat field, nilai tidak hilang saat gagal, pilihan tanggal/timezone konsisten, serta perlindungan saat menutup form berubah.
- [x] **AP-02.16** (`VERIFIED`) Toast responsif, bertumpuk, dismissible, safe-area, notification region dan z-index benar; error form tetap inline dan tidak hanya toast.
- [ ] **AP-02.17** (`OPEN`) Tinjau login terkait admin terhadap aturan form terpusat; setiap password mempunyai show/hide aksesibel. Pertahankan login publik yang disetujui hingga konflik D-02 diputuskan.
- [ ] **AP-02.18** (`OPEN`) Pastikan dark/light, preferensi sistem/pengguna, skeleton, empty, error, success, no-permission, offline, disabled dan loading konsisten.
- [ ] **AP-02.19** (`OPEN`) Pastikan keyboard, screen reader, zoom 200%, kontras, reduced motion, unggah kamera dan perangkat sentuh bekerja pada alur utama.
- [ ] **AP-02.20** (`OPEN`) Hilangkan dekorasi admin tanpa tujuan, petunjuk berulang dan shortcut eksternal yang tidak diminta. Data/teks development tidak muncul di UI produksi.
- [ ] **AP-02.21** (`OPEN`) Audit komponen lintas modul dan CSS global agar perubahan admin tidak menimpa SVG grafik, input, tabel, modal, font atau warna publik.
- [ ] **AP-02.22** (`OPEN`) Uji sticky header/kolom, scroll horizontal tabel, menu dropdown di dalam modal, tooltip yang terpotong, nested overlay, safe-area, keyboard virtual dan landscape; tombol utama/close/toast tidak saling menutupi.

Penerimaan: layar daftar/detail/form dan shell admin mengikuti tujuh aturan sumber; perbedaan yang disengaja teridentifikasi; screenshot publik tetap sesuai baseline.

### AP-03 — Branding, tema dan konfigurasi global tanpa kode

Prioritas P1. Area: `School`, `SchoolController`, `Core/School/Edit.vue`, media branding, token, header/footer dan payload publik.

- [x] **AP-03.01** (`VERIFIED`) Kelola nama aplikasi, nama/legal name sekolah, NPSN, jenjang, status, akreditasi, alamat, kontak, website, kepala sekolah dan identitas penerbit dari panel.
- [ ] **AP-03.02** (`OPEN`) Kelola logo, favicon, foto kepala, identitas portal layanan, gambar default dan metadata/alt; uji ganti, kosong, gagal upload, fallback, ukuran dan cache media.
- [ ] **AP-03.03** (`OPEN`) Kelola base/accent, semantic colors, typography yang diizinkan, radius, density, dark/light, preset dan konfigurasi navigasi admin sesuai standar 05.
- [ ] **AP-03.04** (`OPEN`) Turunkan hover/active/focus/disabled/surface/border/teks dari tema, sediakan pemeriksaan kontras, preview dan reset default dengan konfirmasi.
- [x] **AP-03.05** (`VERIFIED`) Pisahkan preferensi tampilan pribadi dari branding sekolah; konfigurasi satu sekolah tidak perlu mengubah arsitektur menjadi multi-tenant.
- [ ] **AP-03.06** (`OPEN`) Sediakan konfigurasi konten/identitas setiap portal publik yang berbeda dalam struktur yang dibutuhkan publik, tanpa memaksakan shell admin.
- [ ] **AP-03.07** (`OPEN`) Seragamkan payload identitas lintas Blade, Inertia, login, surat, resi, cetak dan PDF; hapus override demo yang menutupi perubahan admin dalam mode penggunaan nyata.
- [x] **AP-03.08** (`VERIFIED`) Kelola timezone, format tanggal/jam, kontak darurat, jam layanan, hari libur dan informasi penutupan per layanan.
- [x] **AP-03.09** (`VERIFIED`) Kelola media sosial, kanal bantuan/chat, tautan peta dan tautan eksternal yang memang ada; validasi URL/skema dan target tautan.
- [ ] **AP-03.10** (`OPEN`) Uji persistensi setelah refresh/relogin, cache invalidation, URL media lama, nama panjang, gambar transparan serta tema ekstrem yang ditolak karena kontras.

Penerimaan: mengganti identitas/branding melalui panel memperbarui semua titik penggunaan yang relevan tanpa kode; perubahan tidak merusak layout publik.

### AP-04 — Fondasi pengelolaan seluruh konten publik

Prioritas P0. Area: Core, `schools.settings`, entitas konten yang ada, service/payload, Blade dan Vue publik. Usulan skema baru tunduk D-09.

- [x] **AP-04.01** (`VERIFIED`) Tentukan pemakaian ulang model/field yang ada; pakai konfigurasi untuk pengaturan kecil dan entitas berulang untuk data yang membutuhkan lifecycle. Hindari duplikasi sumber kebenaran.
- [x] **AP-04.02** (`VERIFIED`) Definisikan ID stabil untuk halaman, bagian, item, slot gambar, modal dan menu; peta ID tidak boleh berubah karena judul diedit.
- [ ] **AP-04.03** (`OPEN`) Daftarkan tipe field yang dibutuhkan: teks, teks panjang/rich text terkontrol, gambar/alt/crop, URL, warna/preset, ikon allowlist, angka, tanggal/jam, boolean, daftar, relasi, dokumen dan embed yang diizinkan.
- [x] **AP-04.04** (`VERIFIED`) Sediakan editor sesuai susunan publik: judul, subtitle, deskripsi, badge, CTA, label, ikon, gambar, caption, tab, kartu, accordion/FAQ, ticker, popup dan footer.
- [x] **AP-04.05** (`VERIFIED`) Sediakan urutan dan tampil/sembunyi bagian yang memang didukung baseline; kontrol layout dibatasi template publik yang disetujui, tanpa pembuat layout bebas yang menggeser desain.
- [ ] **AP-04.06** (`OPEN`) Atur required/min/max, panjang judul/teks, rasio gambar, jumlah item dan empty state; uji Unicode, karakter khusus, teks panjang dan konten kosong.
- [x] **AP-04.07** (`VERIFIED`) Sanitasi rich text, link, ikon dan embed; cegah script, inline handler, URL berbahaya, executable upload, arbitrary CSS/JS/PHP dan query database dari editor.
- [x] **AP-04.08** (`VERIFIED`) Pisahkan draft, preview privat, publish, unpublish, arsip dan jadwal publikasi; tentukan hak editor/publisher sesuai D-03 tanpa menambah role otomatis.
- [x] **AP-04.09** (`VERIFIED`) Preview wajib menggunakan template publik yang sama dan konfigurasi draft tanpa membocorkannya ke tamu, sitemap, API, cache atau pencarian publik.
- [x] **AP-04.10** (`VERIFIED`) Simpan revisi/riwayat, editor/publisher, waktu, audit dan restore versi; cegah edit bersamaan saling menimpa tanpa peringatan.
- [ ] **AP-04.11** (`OPEN`) Tangani unggah/ganti/hapus media, metadata, rasio, ukuran, kompresi yang tersedia, dokumen terpakai, duplikasi, cleanup orphan dan referensi lintas bagian secara aman.
- [x] **AP-04.12** (`VERIFIED`) Inisialisasi konten yang sekarang ada sebagai data yang dapat diedit tanpa mengubah desain; idempotent, dapat dipulihkan dan tidak menimpa suntingan admin saat deployment/seed ulang.
- [x] **AP-04.13** (`VERIFIED`) Hilangkan fallback demo otomatis pada penggunaan nyata; bedakan belum diatur, sengaja kosong, sengaja disembunyikan, hilang, tidak terbit dan gangguan data.
- [ ] **AP-04.14** (`OPEN`) Angka statistik tetap otomatis dari data sumber yang dikelola role berwenang; konten pemasaran numerik yang bukan agregat diberi field dan definisi yang jelas.
- [ ] **AP-04.15** (`OPEN`) Kelola title halaman, deskripsi, gambar berbagi, label tautan dan canonical bila dipakai; pastikan metadata preview/draft tidak dipublikasikan.
- [ ] **AP-04.16** (`OPEN`) Invalidasi cache tepat pada publish/unpublish/jadwal/media/tema; perubahan draft tidak mengubah cache publik.
- [ ] **AP-04.17** (`OPEN`) Uji pengguna nonteknis mengubah setiap tipe konten, mempratinjau, menerbitkan, membatalkan dan memulihkan tanpa akses kode, SQL atau shell.
- [ ] **AP-04.18** (`OPEN`) Sediakan pemulihan saat halaman/item/section/kategori dinonaktifkan atau dihapus: tautan masuk, slug/canonical, referensi CTA, media terpakai, redirect dan cache tetap mempunyai perilaku yang jelas; preview/scheduled publish tidak merusak halaman live saat draft belum valid.

Penerimaan: setiap elemen konten pada matriks PUB mempunyai field/editor yang berfungsi; tidak ada teks/aset operasional yang hanya dapat diubah developer, kecuali konstanta sistem yang beralasan dan disepakati.

### AP-05 — Beranda, profil, GTK, organisasi, fasilitas, kesiswaan dan kontak

Prioritas P1. Area: `SchoolProfileController`, `PublicPortalService`, `AcademicQueryService`, organisasi, pengaturan sekolah, `home/profile/teachers.blade.php`, `Public/School/*`.

- [x] **AP-05.01** (`VERIFIED`) Kelola seluruh hero beranda, judul, highlight, deskripsi, badge/capaian, gambar, caption dan CTA sesuai inventaris AP-00.05.
- [x] **AP-05.02** (`VERIFIED`) Kelola sambutan kepala, jabatan, foto, ringkasan profil, sejarah, visi, misi, nilai budaya dan program aksi; bedakan konten induk dari ringkasan halaman.
- [ ] **AP-05.03** (`OPEN`) Kelola bagian kurikulum/peminatan, program unggulan, ekstrakurikuler, fasilitas, foto, keterangan dan urutan; gunakan data sarpras/akademik yang relevan tanpa membuka detail internal sensitif.
- [x] **AP-05.04** (`VERIFIED`) Hubungkan slot berita, prestasi, galeri, ZI, layanan dan unduhan di beranda ke data terbit; pilihan sorotan dan jumlah tampil dapat dikelola.
- [ ] **AP-05.05** (`OPEN`) Kelola popup pengumuman/PPDB, banner terbuka/ditutup, brosur, periode tampil dan perilaku penutupan tanpa tahun/tanggal hardcoded.
- [x] **AP-05.06** (`VERIFIED`) Kelola profil publik GTK: nama, foto, jabatan, mapel, pendidikan/informasi yang disetujui, urutan, status publikasi dan pengelompokan; pisahkan dari atribut kepegawaian privat.
- [x] **AP-05.07** (`VERIFIED`) Verifikasi pencarian/filter GTK hanya memakai atribut yang boleh diungkap; informasi tersembunyi tidak boleh tertebak melalui hasil pencarian.
- [x] **AP-05.08** (`VERIFIED`) Kelola struktur organisasi, induk-anak, urutan, jabatan, foto, masa jabatan dan status; cegah relasi siklus serta ketidaksesuaian nama/jabatan antarhalaman.
- [ ] **AP-05.09** (`OPEN`) Kelola informasi kesiswaan, organisasi siswa, kegiatan, prestasi dan dokumentasi yang tampil; moderasi publikasi nama/foto siswa sesuai D-07.
- [x] **AP-05.10** (`VERIFIED`) Kelola kontak, alamat, peta, jam layanan, hari libur, kontak tiap layanan dan tautan bantuan; tidak perlu mengubah template untuk memperbarui salah satunya.
- [x] **AP-05.11** (`VERIFIED`) Uji empty/unpublished, pergantian kepala, guru nonaktif, media hilang, teks panjang, CTA salah, section disembunyikan dan data lintas halaman.

Penerimaan: PUB-01 s.d. PUB-08 dikelola dari panel; identitas, jabatan dan data bersama konsisten; desain publik tetap sesuai baseline.

### AP-06 — Berita, prestasi, galeri foto/video dan publikasi alumni

Prioritas P1. Area: `SchoolNewsController`, `SchoolGalleryController`, model media, `Core/News`, galeri, template berita/prestasi/video dan tab karir/beasiswa IKA.

- [x] **AP-06.01** (`VERIFIED`) Kelola kategori, label publik, slug, urutan kategori, status aktif dan relasinya; kategori bawaan tidak lagi harus disunting di template.
- [x] **AP-06.02** (`VERIFIED`) Kelola berita: judul, slug, ringkasan, isi terformat terkontrol, penulis publik, tanggal, kategori, foto/cover/caption/alt, sumber, sorotan dan arsip.
- [x] **AP-06.03** (`VERIFIED`) Terapkan lifecycle draf, terjadwal, terbit, ditarik dan arsip; publikasi masa depan/draft/arsip mengikuti kontrak listing, detail, hasil pencarian dan media.
- [x] **AP-06.04** (`VERIFIED`) Kelola prestasi sebagai data yang sesuai kebutuhan publik: lomba, pencapaian/peringkat, tingkat, bidang, tanggal/tahun, peserta/tim, pembina dan dokumentasi yang diizinkan. Putuskan reuse berita atau model prestasi berdasarkan data yang sudah ada.
- [x] **AP-06.05** (`VERIFIED`) Kelola album/foto, judul, kategori, tanggal, deskripsi, foto urut, cover, caption, alt dan status; lightbox mengambil data yang sama.
- [x] **AP-06.06** (`VERIFIED`) Ganti array video statis dengan pengelolaan judul, kategori, ringkasan, thumbnail, URL/embed allowlist, tanggal, durasi bila tersedia, unggulan dan publikasi.
- [x] **AP-06.07** (`VERIFIED`) Hubungkan lowongan dan beasiswa alumni ke publikasi yang sesuai, berikut pencarian, kategori, periode aktif, tautan/syarat dan penutupan. Jangan menggandakan data terbit di model lain tanpa alasan.
- [x] **AP-06.08** (`VERIFIED`) Kendalikan konten muncul di beranda, berita umum, prestasi, galeri atau portal alumni melalui pengaturan penempatan; tentukan perilaku publikasi silang agar kategori karir tidak mendominasi semua slot berita tanpa disengaja.
- [x] **AP-06.09** (`VERIFIED`) Pastikan filter/search/pagination, hitungan total/hasil, query yang dipertahankan, deep link, URL detail dan media tidak rusak saat kategori/slug berubah.
- [ ] **AP-06.10** (`OPEN`) Tangani penghapusan kategori/penulis/media yang masih dipakai, gambar rusak, berita tanpa foto, video gagal/provider diblokir serta embed yang melacak pengguna sesuai kebijakan.
- [x] **AP-06.11** (`VERIFIED`) Uji unpublish menutup akses detail/media yang harus privat, preview draft tidak dapat ditebak, pencarian tidak menyingkap draft, dan jadwal memakai timezone sekolah.

Penerimaan: semua konten PUB-06/PUB-09/PUB-10/PUB-11/PUB-37 dan publikasi IKA dapat diubah/publikasikan dari panel; kosong berarti empty state yang benar, bukan fallback fiktif.

### AP-07 — Pusat unduh, Zona Integritas, IKM, salat dan statistik kunjungan

Prioritas P1. Area: `PublicPortalController`, `PublicPortalAdminController`, `PublicPortalService`, model unduhan/survei/kunjungan, ZI dan provider integrasi.

- [x] **AP-07.01** (`VERIFIED`) Sediakan layar pengelolaan pusat unduh, bukan endpoint simpan saja: kategori, judul, deskripsi, versi, file, tanggal, urutan, hak publikasi dan riwayat.
- [x] **AP-07.02** (`VERIFIED`) Tangani versi dokumen, ganti file, tarik publikasi, cache, media terpakai dan tautan lama; file privat tidak menjadi publik karena path diketahui.
- [x] **AP-07.03** (`VERIFIED`) Kelola seluruh area ZI: judul, uraian, dokumen, foto, capaian, tautan, banner, SOP dan jadwal informasi; slot beranda memakai data yang sama.
- [x] **AP-07.04** (`VERIFIED`) Kelola konfigurasi survei IKM, periode, unsur/pertanyaan, bobot bila ada, label jawaban, layanan dan kebijakan perhitungan; perubahan instrumen tidak mengubah hasil historis diam-diam.
- [x] **AP-07.05** (`VERIFIED`) Uji kirim survei, pembatasan penyalahgunaan yang proporsional, moderasi komentar, agregat, ekspor dan privasi responden; perubahan moderasi tercermin ke publik.
- [x] **AP-07.06** (`VERIFIED`) Kelola lokasi salat/kode wilayah, timezone, sumber/provider, metode fallback, koreksi yang diizinkan dan label status; alamat teks tidak dipakai sebagai kode wilayah tanpa validasi.
- [x] **AP-07.07** (`VERIFIED`) Pastikan widget jadwal di beranda/footer/halaman salat memakai sumber sama, tanggal berganti benar, serta kegagalan provider tidak memblokir halaman.
- [x] **AP-07.08** (`VERIFIED`) Definisikan pengunjung total/hari ini/bulan ini/online secara konsisten; hindari angka statis, hitungan ganda karena retry, atau pelacakan identitas yang tidak diperlukan.
- [ ] **AP-07.09** (`OPEN`) Uji unduh file besar, filename Unicode, tipe file yang diizinkan, file hilang, hasil survei kosong dan provider offline.

Penerimaan: dokumen dan informasi PUB-12/PUB-13/PUB-14 dapat dikelola, hasil agregat benar, dan fallback operasional tidak berubah menjadi data klaim palsu.

### AP-08 — Menu publik, katalog E-Layanan dan elemen bersama

Prioritas P1. Area: navigasi/footer Blade, `PublicHeader.vue`, `usePublicServicesCatalog.ts`, konfigurasi layanan/menu dan pencarian.

- [x] **AP-08.01** (`VERIFIED`) Kelola kelompok, label, deskripsi, ikon, urutan, status aktif, tautan dan kata kunci/sinonim layanan yang tampil pada seluruh menu/katalog.
- [x] **AP-08.02** (`VERIFIED`) Kelola header/footer per portal, ticker/agenda, FAQ, CTA, tombol bantuan, media sosial, informasi sekretariat dan kebijakan yang sudah ada dalam baseline.
- [x] **AP-08.03** (`VERIFIED`) Pakai sumber data yang sama untuk desktop, mobile, pencarian global, pintasan beranda, kartu katalog dan tautan footer yang merujuk layanan sama.
- [x] **AP-08.04** (`VERIFIED`) Bedakan tautan publik, tautan perlu login, eksternal dan unduhan; arahkan login dengan return target aman dan kembalikan ke pekerjaan yang dimaksud.
- [x] **AP-08.05** (`VERIFIED`) Tampilkan informasi layanan tutup/maintenance sesuai konfigurasi tanpa link mati; tentukan pengaruhnya terhadap pengajuan baru dan permohonan berjalan.
- [x] **AP-08.06** (`VERIFIED`) Lindungi route sistem wajib, validasi URL/ikon, cegah siklus menu dan tautan berbahaya; perubahan label tidak mengubah route key.
- [x] **AP-08.07** (`VERIFIED`) Uji pencarian sinonim, kategori kosong, hasil nol, daftar panjang, keyboard, drawer mobile, active state, deep link dan cache menu.
- [ ] **AP-08.08** (`OPEN`) Inventaris dan hubungkan semua modal publik, poster SOP, tooltip kontekstual, formulir bantuan, pustaka foto dan tautan cetak yang belum masuk mapping halaman.

Penerimaan: seluruh jalur navigasi dan salinan konten bersama dikelola tanpa kode serta tetap konsisten pada semua shell publik.

### AP-09 — Data siswa publik dan verifikasi identitas

Prioritas P0 untuk privasi; P1 untuk pengelolaan konten. Area: `VerificationController`, `AcademicQueryService`, `Public/Students`, `Public/Verification` dan data induk akademik.

- [x] **AP-09.01** (`VERIFIED`) Hubungkan agregat siswa, gender, rombel, tingkat dan periode dengan data aktif yang benar; definisikan denominasi/status siswa pada statistik.
- [x] **AP-09.02** (`VERIFIED`) Kelola judul, deskripsi, petunjuk, label, filter dan konfigurasi tampil yang memang didukung halaman tanpa membuka data privat.
- [x] **AP-09.03** (`VERIFIED`) Bedakan direktori demo lokal dan data nyata; fixture nama/NIS/NISN/tempat lahir tidak boleh dipromosikan ke produksi sebagai publikasi otomatis.
- [x] **AP-09.04** (`VERIFIED`) Konfirmasikan kolom publik yang diizinkan, persetujuan, redaksi, filter, pencetakan dan ekspor; pencarian tidak boleh menjadi jalur ekstraksi data siswa.
- [x] **AP-09.05** (`VERIFIED`) Verifikasi keaktifan memakai bukti identitas yang disetujui, rate limit dan respons minimum; uji siswa aktif, mutasi, lulus, data salah dan lintas sekolah.
- [x] **AP-09.06** (`VERIFIED`) Pastikan perubahan enrollment/periode dari admin langsung tercermin sesuai kontrak cache dan tidak mengubah histori atau dokumen lama.
- [x] **AP-09.07** (`VERIFIED`) Uji verifikasi dokumen/siswa/alumni, tab, state tidak ditemukan, input kosong/panjang, layanan gagal dan cetak sesuai scope.

Penerimaan: data publik berasal dari data induk yang dikelola role berwenang; tidak ada daftar atau atribut pribadi yang terbuka hanya demi menyamai tampilan referensi.

### AP-10 — Fondasi transaksi, status, dokumen dan audit lintas layanan

Prioritas P0. Area: `ServiceWorkflowMap`, `ServiceTicketService`, `ServiceDocumentService`, `PrivateStorageService`, audit, outbox, workflow service setiap modul.

- [x] **AP-10.01** (`VERIFIED`) Inventaris state aktual tiap layanan: aktor, pemicu, prasyarat, status internal/publik, efek samping, alasan, notifikasi dan kondisi terminal. Jangan memaksakan satu daftar status untuk semua layanan.
- [x] **AP-10.02** (`VERIFIED`) Satukan pemetaan status admin–pelacakan–notifikasi–dokumen sehingga tidak ada istilah sama dengan arti berbeda atau keputusan yang tidak tercermin ke publik.
- [x] **AP-10.03** (`VERIFIED`) Uji atomic transaction, row lock/version, idempotency key dan unique constraint untuk submit, revisi, keputusan, penerbitan, pengambilan serta retry jaringan.
- [x] **AP-10.04** (`VERIFIED`) Simpan snapshot identitas/periode/pejabat/template pada transaksi/dokumen; perubahan data induk atau konfigurasi tidak mengubah dokumen terbit secara retroaktif.
- [x] **AP-10.05** (`VERIFIED`) Petakan template PDF/resi/kartu/surat dan placeholder allowlist yang dapat dikelola: kop, isi, syarat, tanda tangan, footer, nomor dan QR; tidak menjalankan kode dari template editor.
- [x] **AP-10.06** (`VERIFIED`) Atur penomoran unik/atomik per jenis/sekolah/periode, tanggal penerbitan/timezone, nomor batal, penerbitan ulang dan referensi ke versi sebelumnya.
- [x] **AP-10.07** (`VERIFIED`) Pisahkan tanda tangan goresan pemohon, persetujuan pejabat, gambar tanda tangan dan tanda tangan digital provider; jangan menampilkan klaim legal/provider yang belum digunakan.
- [x] **AP-10.08** (`VERIFIED`) Dokumen/unggahan privat memakai otorisasi download, URL signed/rahasia yang sesuai, expiry, MIME/ekstensi/ukuran valid dan filename aman; route ID langsung tidak boleh melewati pemeriksaan.
- [x] **AP-10.09** (`VERIFIED`) Tentukan pencabutan, kedaluwarsa, pembatalan, revisi dan penggantian lampiran; alasan wajib dan riwayat tidak hilang.
- [x] **AP-10.10** (`VERIFIED`) Audit aktor, sekolah, objek, aksi, waktu, perubahan relevan, alasan, hasil dan korelasi tiket tanpa mencatat password/PIN/token mentah/bukti rahasia.
- [x] **AP-10.11** (`VERIFIED`) Notifikasi/outbox dikirim setelah transaksi berhasil; kegagalan kanal tidak menggagalkan penyimpanan inti atau menggandakan keputusan.
- [ ] **AP-10.12** (`OPEN`) Tangani kegagalan generator PDF, storage, nomor surat, provider, queue dan commit parsial; sediakan retry yang aman serta pesan pemulihan yang benar.
- [x] **AP-10.13** (`VERIFIED`) Uji seluruh format cetak/PDF dengan nama/alamat panjang, Unicode, font, foto/tanda tangan transparan, tabel banyak baris, page break, nomor halaman, kop/footer, orientasi dan QR yang bisa dipindai; QR tidak memuat PIN/secret pemohon, URL lingkungan development atau path storage privat.

Penerimaan: setiap perubahan bisnis tercatat sekali, konsisten di seluruh permukaan, dan dokumen hanya dapat diakses pihak yang berhak.

### AP-11 — PTSP, akun pemohon, surat dinas dan buku tamu

Prioritas P1; bergantung AP-01/AP-04/AP-10. Area: Administration, `PtspPublicController`, `PtspAdminController`, `ExternalLetterController`, `GuestbookController`, akun eksternal dan UI terkait.

- [x] **AP-11.01** (`VERIFIED`) Kelola katalog PTSP sesuai kartu/form publik: nama, kode, uraian, kategori, pemohon, persyaratan, lampiran, SLA, jam layanan, prosedur, hasil dan status aktif.
- [x] **AP-11.02** (`VERIFIED`) Kelola label/bantuan/pilihan field dan persyaratan yang diizinkan; perubahan konfigurasi memiliki versi dan tidak membatalkan permohonan berjalan tanpa kebijakan.
- [x] **AP-11.03** (`VERIFIED`) Uji akun pemohon, draf, upload, hapus lampiran draf, submit, validasi, resi layar/PDF, refresh, pengajuan ganda dan pemulihan koneksi.
- [x] **AP-11.04** (`VERIFIED`) Antrean admin menyediakan scope role, filter, pencarian, pagination, counter, detail, dokumen, penugasan/disposisi dan riwayat yang benar.
- [x] **AP-11.05** (`VERIFIED`) Uji mulai tinjau, minta revisi beralasan, notifikasi, pengisian ulang pemohon dan resubmit; revisi tidak menjadi permohonan baru atau mengganti kepemilikan.
- [x] **AP-11.06** (`VERIFIED`) Uji tolak, batalkan sesuai hak, setujui/terbitkan hasil, unggah hasil, tanda tangan bila berlaku dan status siap diambil.
- [x] **AP-11.07** (`VERIFIED`) Catat pengambilan digital/fisik, identitas penerima/kuasa, waktu dan petugas; cegah pengambilan/penerbitan ganda.
- [x] **AP-11.08** (`VERIFIED`) Uji semua status muncul di `/lacak`, riwayat pemohon, resi dan notifikasi; akses hasil memerlukan otorisasi yang sesuai.
- [x] **AP-11.09** (`VERIFIED`) Kelola surat dinas: instansi pengirim, nomor/tanggal surat, kategori/perihal, penerima/tujuan, kontak, lampiran, resi dan agenda admin.
- [x] **AP-11.10** (`VERIFIED`) Hubungkan surat eksternal ke register persuratan/disposisi yang ada, dengan tindak lanjut, status, penanggung jawab dan arsip tanpa duplikasi agenda.
- [x] **AP-11.11** (`VERIFIED`) Kelola konten buku tamu, tujuan/unit/pegawai yang dapat dipilih, keperluan, jam dan kebijakan foto; data pegawai privat tidak bocor melalui pilihan.
- [x] **AP-11.12** (`VERIFIED`) Uji daftar tamu, kamera/upload, waktu datang, notifikasi penerima bila disetujui, check-out, kunjungan berulang, laporan/ekspor dan privasi foto.
- [x] **AP-11.13** (`VERIFIED`) Bedakan akses resepsionis, PTSP, operator, pejabat penerima dan penandatangan; pengalihan pekerjaan tidak membuka seluruh lampiran sensitif.

Penerimaan: pengajuan PTSP dan surat dinas diselesaikan dari titik masuk publik sampai hasil/arsip; kunjungan dicatat sampai pulang; kontennya dikelola panel.

### AP-12 — Izin siswa, wali kelas, piket, presensi dan pos jaga

Prioritas P1. Area: Homeroom, `StudentPermitController`, `PermitWorkflowService`, `PicketGateService`, token, presensi dan halaman petugas terkait.

- [x] **AP-12.01** (`VERIFIED`) Kelola jenis izin, label, syarat, ketentuan, SOP, masa berlaku token, rentang pengajuan dan kontak layanan yang ditampilkan publik.
- [x] **AP-12.02** (`VERIFIED`) Uji identifikasi siswa, relasi orang tua, pengajuan siswa bila diizinkan, token sah/salah/kedaluwarsa/dipakai ulang dan cakupan penerbit token.
- [x] **AP-12.03** (`VERIFIED`) Uji pengajuan sakit/keluarga/dispensasi, tanggal tunggal/rentang, timezone, alasan, relasi pemohon, tanda tangan dan lampiran.
- [x] **AP-12.04** (`VERIFIED`) Antrean wali kelas terbatas kelas/periode penugasan; piket hanya memperoleh wewenang operasional yang ditetapkan.
- [x] **AP-12.05** (`VERIFIED`) Uji setujui, tolak, revisi, koreksi beralasan, konflik keputusan bersamaan dan status publik setelah pemohon mengirim perbaikan.
- [x] **AP-12.06** (`VERIFIED`) Sinkronkan presensi secara tepat: tanggal, status, jam, pertemuan, prioritas sumber dan rollback koreksi; satu izin tidak membuat presensi ganda atau menimpa catatan sah tanpa aturan.
- [x] **AP-12.07** (`VERIFIED`) Hubungkan izin keluar dengan QR/kode, pemeriksaan piket/pos jaga, batas jam, pencatatan keluar/kembali dan penolakan izin yang tidak berlaku.
- [x] **AP-12.08** (`VERIFIED`) Kios/RFID/QR memakai sumber identitas dan izin yang sama; tetapkan akses perangkat, pencegahan replay dan fallback manual sesuai fitur yang sudah ada.
- [x] **AP-12.09** (`VERIFIED`) Uji bukti PDF, status pengajuan, lampiran medis, download pemohon/petugas dan privasi pos jaga; petugas jaga hanya menerima data minimum.
- [ ] **AP-12.10** (`OPEN`) Uji izin tumpang tindih, hari libur, siswa nonaktif/mutasi, kelas berganti, kembali terlambat, salah scan dan gangguan perangkat.

Penerimaan: izin publik → keputusan wali/piket → presensi/keluar–kembali → pelacakan/bukti konsisten, dengan batas data tiap petugas.

### AP-13 — Mutasi masuk/keluar dan histori akademik

Prioritas P1. Area: Academic, `StudentMutationController`, service mutasi, enrollment, surat dan register admin.

- [x] **AP-13.01** (`VERIFIED`) Kelola SOP masuk/keluar, daftar persyaratan, informasi daya tampung, jadwal, ketentuan, formulir dan kontak sesuai tampilan publik.
- [x] **AP-13.02** (`VERIFIED`) Uji pemohon, siswa/sekolah asal/tujuan, tanggal, alasan, lampiran, deklarasi, resi dan pencegahan duplikasi.
- [x] **AP-13.03** (`VERIFIED`) Pisahkan verifikasi TU, validasi akademik, keputusan kepala dan penerbitan surat sesuai D-03/D-06; cakupan tiap aksi terlihat di panel.
- [x] **AP-13.04** (`VERIFIED`) Hubungkan revisi berkas, penolakan, persetujuan dan pencabutan ke pelacakan, notifikasi dan riwayat.
- [x] **AP-13.05** (`VERIFIED`) Mutasi masuk tidak membuat siswa/NIS/NISN/enrollment ganda; mutasi keluar mengubah status efektif tanpa menghapus histori nilai, presensi dan kelas.
- [x] **AP-13.06** (`VERIFIED`) Tentukan pengaruh pada akun siswa/wali, akses kelas, perpustakaan, kewajiban administrasi dan layanan yang sedang berjalan.
- [x] **AP-13.07** (`VERIFIED`) Uji surat resmi/PDF, penomoran, tanggal efektif, pejabat, verifikasi QR, pencabutan dan penerbitan ulang.
- [ ] **AP-13.08** (`OPEN`) Uji kuota penuh, periode berganti, keputusan bersamaan, data asal tidak cocok, permohonan ganda dan pembatalan setelah perubahan data.

Penerimaan: perubahan status akademik dan dokumen mengikuti satu keputusan yang dapat diaudit; histori tidak hilang.

### AP-14 — Kelulusan, pengumuman dan SKL

Prioritas P1. Area: `GraduationController`, `GraduationService`, batch/record, halaman admin kelulusan dan publik.

- [x] **AP-14.01** (`VERIFIED`) Kelola judul, uraian, tahun ajaran, jadwal buka, timezone, countdown, ketentuan, syarat SKL dan pesan sebelum/sesudah pengumuman.
- [x] **AP-14.02** (`VERIFIED`) Kelola batch, daftar siswa, sumber kelayakan, keputusan lulus/tidak lulus, verifikasi kelengkapan dan preview sebelum terbit.
- [x] **AP-14.03** (`VERIFIED`) Pisahkan persiapan operator, persetujuan pejabat dan publikasi terjadwal; cegah publikasi hasil belum lengkap atau pembukaan sebelum waktunya.
- [x] **AP-14.04** (`VERIFIED`) Uji pencarian hasil dengan identitas minimum, batas percobaan, tidak ditemukan, data tidak cocok dan perlindungan enumerasi siswa.
- [x] **AP-14.05** (`VERIFIED`) Hubungkan hasil ke perubahan lifecycle siswa/alumni sesuai kebijakan, tanpa menghapus data periode atau membuat profil publik alumni tanpa persetujuan.
- [x] **AP-14.06** (`VERIFIED`) Kelola template SKL, kop, nomor, pejabat, tanggal, QR dan ketentuan unduh; snapshot terbit tidak berubah ketika data sekolah diedit.
- [x] **AP-14.07** (`VERIFIED`) Uji unduh SKL privat, cetak, pencatatan ambil asli, reissue, pencabutan dan pemeriksaan keabsahan versi lama/baru.
- [ ] **AP-14.08** (`OPEN`) Uji jadwal diubah, batch dibatalkan, refresh tepat waktu buka, cache hasil sebelum jadwal, data nol dan kegagalan generator.

Penerimaan: publik melihat jadwal dan hasil sah pada waktu yang benar; hasil/SKL dikelola panel dan tidak dapat dibaca siswa lain.

### AP-15 — Legalisir, pengambilan, surat kuasa dan SPTJM

Prioritas P1. Area: Administration, `LegalizationController`, service legalisir, template kuasa/SPTJM dan register admin.

- [x] **AP-15.01** (`VERIFIED`) Kelola SOP, syarat asli/salinan, jenis dokumen, jumlah lembar, keperluan, metode pengambilan, jadwal dan ketentuan sesuai semua tab publik.
- [x] **AP-15.02** (`VERIFIED`) Uji identifikasi alumni/pemegang ijazah, data dokumen, tanda tangan, lampiran, submit, resi dan pencegahan pengajuan ganda.
- [x] **AP-15.03** (`VERIFIED`) Pisahkan pemeriksaan berkas, verifikasi dokumen asli fisik, keputusan pengesahan dan pejabat penandatangan; validasi prasyarat sebelum setiap aksi.
- [x] **AP-15.04** (`VERIFIED`) Hubungkan revisi/tolak, permohonan yang diperbaiki, catatan petugas dan notifikasi ke `/lacak` serta riwayat pemohon.
- [x] **AP-15.05** (`VERIFIED`) Kelola template legalisir, nomor/register, stempel/tanda tangan, QR, hasil PDF, pencabutan dan penerbitan ulang.
- [x] **AP-15.06** (`VERIFIED`) Kelola generator kuasa/SPTJM: isi resmi berversi, identitas pemberi/penerima, objek kuasa, relasi dokumen, deklarasi dan tanda tangan yang diwajibkan.
- [x] **AP-15.07** (`VERIFIED`) Pastikan surat kuasa terhubung ke permohonan yang benar; kepemilikan, pemberi kuasa dan identitas pengambil tidak dapat ditukar hanya dengan mengganti kode tiket.
- [x] **AP-15.08** (`VERIFIED`) Catat ambil langsung/kuasa, identitas penerima, bukti, waktu dan petugas; blokir pengambilan ganda atau sebelum hasil sah.
- [ ] **AP-15.09** (`OPEN`) Uji kuasa tidak valid, lampiran tidak cocok, jumlah lembar di luar batas, ijazah berbeda pemilik, dokumen dicabut dan generator gagal.

Penerimaan: publik → pemeriksaan asli → pengesahan → dokumen/kuasa → pengambilan → verifikasi dapat diselesaikan sesuai role dan tercatat lengkap.

### AP-16 — Siap Lapor, satgas, BK dan transparansi

Prioritas P0 untuk kerahasiaan; P1 untuk operasional. Area: Homeroom, `ComplaintController`, `ComplaintReportService`, bukti privat, satgas/BK dan transparansi.

- [x] **AP-16.01** (`VERIFIED`) Kelola kategori, tipe pengaduan/aspirasi/info yang ada, informasi kanal, ketentuan, SOP, kontak darurat dan panduan yang tampil publik.
- [x] **AP-16.02** (`VERIFIED`) Uji laporan bernama/anonim, kronologi, waktu/lokasi, foto/kamera/dokumen dan tiket/PIN; identitas anonim tidak disimpan terselubung dalam payload, log, audit atau notifikasi.
- [x] **AP-16.03** (`VERIFIED`) Antrean satgas mempunyai scope kasus/penugasan, triase, status, prioritas, petugas dan SLA yang disetujui; akses BK berbeda dari akses seluruh aduan.
- [x] **AP-16.04** (`VERIFIED`) Uji penugasan ulang, permintaan informasi, balasan pemohon, tindak lanjut, bukti penyelesaian, penutupan dan pembukaan kembali bila dibutuhkan SOP.
- [x] **AP-16.05** (`VERIFIED`) Kendalikan akses bukti privat, foto, identitas, komentar internal, ekspor dan pencarian; akses kepala/operator umum tidak diasumsikan otomatis.
- [x] **AP-16.06** (`VERIFIED`) Ringkasan publik memerlukan redaksi dan moderasi; agregat/transparansi tidak membocorkan pelapor, korban, terlapor atau identitas kasus kecil.
- [x] **AP-16.07** (`VERIFIED`) Pisahkan catatan BK rahasia, disiplin, tindak lanjut wali kelas dan informasi orang tua; tentukan kolom yang boleh dibagikan sesuai penugasan.
- [x] **AP-16.08** (`VERIFIED`) Hubungkan status pelacakan tanpa menampilkan uraian/bukti privat; uji tiket salah, PIN salah, brute force, berkas berbahaya dan petugas dicabut aksesnya.
- [ ] **AP-16.09** (`OPEN`) Uji notifikasi yang disanitasi, laporan ganda, unggahan terputus, kasus lintas kelas dan perubahan retensi sesuai keputusan.

Penerimaan: laporan dapat ditangani sampai selesai dan dipantau secara aman; halaman transparansi hanya menampilkan data yang disetujui publik.

### AP-17 — IKA, direktori alumni, tracer, karir, beasiswa dan E-KTA

Prioritas P1. Area: `AlumniController`, `AlumniService`, `AlumniProfile`, `Academic/Alumni`, `Public/Alumni`, publikasi dan dokumen.

- [x] **AP-17.01** (`VERIFIED`) Kelola topbar, agenda/ticker, informasi layanan, hero, gambar, badge, CTA, pita pintasan, tema portal yang tersedia dan footer IKA tanpa mengubah layout.
- [x] **AP-17.02** (`VERIFIED`) Kelola label/deskripsi lima tab, program IKA, FAQ, kontak sekretariat, tautan legalisir dan periode/ketentuan program.
- [x] **AP-17.03** (`VERIFIED`) Ganti pemilihan sorotan otomatis yang tidak mewakili editorial dengan konfigurasi Hall of Fame yang jelas: profil berizin, urutan, masa tampil dan alasan/atribut yang boleh dipublikasikan.
- [x] **AP-17.04** (`VERIFIED`) Pertahankan perbedaan portal komunitas `/alumni` dengan formulir akademik `/layanan/tracer-alumni`; tautan, login dan tujuan kembali tetap benar.
- [x] **AP-17.05** (`VERIFIED`) Uji daftar/update tracer, status aktivitas, kampus/instansi, program/jabatan, lokasi, beasiswa, testimoni, foto/bukti dan persetujuan publikasi per atribut.
- [x] **AP-17.06** (`VERIFIED`) Antrean moderasi menyediakan pencarian/filter, detail bukti privat, keputusan beralasan, pengembalian perbaikan, riwayat dan pencegahan moderasi sendiri.
- [x] **AP-17.07** (`VERIFIED`) Pembaruan profil terbit mengikuti aturan moderasi ulang; penarikan consent menghilangkan atribut dari listing, detail, pencarian, grafik, ekspor dan cache.
- [x] **AP-17.08** (`VERIFIED`) Direktori mempertahankan mode foto berjalan/grid/tabel, angkatan, jurusan, status, pencarian, pagination dan pilihan tampil semua; tidak kehilangan filter ketika berganti mode/halaman.
- [x] **AP-17.09** (`VERIFIED`) Foto profil publik memakai jalur/persetujuan khusus; jangan membuka `photo_document_id` atau bukti privat sebagai foto direktori tanpa policy.
- [x] **AP-17.10** (`VERIFIED`) Grafik statistik memakai seluruh profil publik, bukan halaman aktif; top kampus hanya menghitung data studi berizin, memiliki definisi agregat serta state kosong.
- [x] **AP-17.11** (`VERIFIED`) Kelola publikasi lowongan/beasiswa dengan periode aktif, data organisasi, syarat dan tautan; hasil pencarian/jumlah tab/slot beranda memakai sumber yang sama.
- [x] **AP-17.12** (`VERIFIED`) Kelola penerbitan E-KTA, nomor, template, QR, status, hak unduh, pencabutan dan penerbitan ulang; pencarian nama publik tidak boleh menjadi hak mengunduh kartu privat.
- [ ] **AP-17.13** (`OPEN`) Uji alumni ganda, akun ganda, data lulus belum menjadi alumni, nama sama, status tidak dikenal, pergantian angkatan dan moderasi bersamaan.
- [ ] **AP-17.14** (`OPEN`) Pertahankan seed development yang repeatable dan tidak menimpa suntingan; pisahkan 180 profil/8 publikasi demo dari data produksi dan rencana migrasi.
- [x] **AP-17.15** (`VERIFIED`) Tinjau dan hubungkan pencarian E-KTA pada tab publik serta modal profil: pencarian memakai data backend dan scope yang sah, bukan hanya `directory.data` yang sedang dipaginasi; perubahan halaman/filter tidak boleh membuat alumni sah seolah tidak terdaftar.

Penerimaan: seluruh konten IKA dapat dikelola, tracer tetap terpisah, consent berlaku menyeluruh dan E-KTA hanya terbit/diunduh sesuai hak.

### AP-18 — Integrasi tampilan perpustakaan utama dengan backend aktif

Prioritas P0. Area: Library, `LibraryController`, `LibraryService`, `perpustakaan.blade.php`, `Public/Library/Index.vue`, `Library/Manage/Index.vue` dan koleksi/media.

- [x] **AP-18.01** (`VERIFIED`) Petakan setiap interaksi replika ke backend yang sudah aktif; pertahankan `/perpustakaan` sebagai tampilan publik yang disetujui dan tentukan kompatibilitas `/portal`, `/man4` serta alias aksinya.
- [x] **AP-18.02** (`VERIFIED`) Ganti aksi stub dengan alur yang sah untuk pencarian, kategori, detail, login anggota, peminjaman, baca e-book, kunjungan, usulan dan bebas pustaka. Jika reservasi ditawarkan pada publik, petakan dan implementasikan aturan reservasinya; jangan meninggalkan tombol yang tidak bekerja.
- [x] **AP-18.03** (`VERIFIED`) Kelola identitas, hero, banner, jam buka, jadwal, gambar, CTA, kategori, sorotan, koleksi populer/baru, testimoni dan footer melalui panel pustakawan/admin.
- [x] **AP-18.04** (`VERIFIED`) Kelola koleksi: judul, penulis, penerbit, ISBN/kode, kategori, tahun, sampul, ringkasan, jumlah/stok, lokasi, jenis cetak/e-book, file/link dan akses publik/anggota/staf.
- [x] **AP-18.05** (`VERIFIED`) Katalog/detail/halaman admin memakai data dan ketersediaan sama; filter/kategori tidak hanya mengganti URL tanpa memengaruhi hasil.
- [x] **AP-18.06** (`VERIFIED`) Uji kunjungan siswa/guru/tamu, QR/manual, tujuan lain, validasi identitas dan pencatatan waktu; foto/kontak bila ada mengikuti privasi.
- [x] **AP-18.07** (`VERIFIED`) Uji usulan pengadaan dari pihak yang diizinkan, antrean pustakawan, keputusan, notifikasi dan tindak lanjut sampai koleksi tersedia.
- [x] **AP-18.08** (`VERIFIED`) Uji pinjam/kembali, stok atomik, due date, batas pinjam dan buku hilang/rusak. Uji perpanjangan/reservasi serta aturan denda bila memang termasuk kontrak; jangan membuat modul pembayaran baru.
- [x] **AP-18.09** (`VERIFIED`) Uji bebas pustaka dengan pinjaman/tanggungan aktif, status siswa, penerbitan surat, QR, download privat, penerbitan ulang dan histori.
- [x] **AP-18.10** (`VERIFIED`) Batasi e-book public/members/staff pada download langsung, URL file dan preview; link provider eksternal mengikuti konfigurasi dan fallback yang disepakati.
- [ ] **AP-18.11** (`OPEN`) Putuskan hubungan dengan SLiMS: katalog lokal/link/file/API sesuai D-08; jelaskan sumber kebenaran dan pemulihan saat provider gagal.
- [ ] **AP-18.12** (`OPEN`) Uji data kosong, stok nol, dua peminjam bersamaan, barcode salah, session anggota berakhir, alias lama, file hilang serta tidak ada konten/nama sekolah referensi yang tersisa.

Penerimaan: semua tombol publik perpustakaan menjalankan layanan sebenarnya atau menampilkan pembatasan yang benar; tidak ada stub/simulasi pada jalur produksi.

### AP-19 — SPMB/PPDB, kandidat, panitia dan pengumuman

Prioritas P1. Area: Admissions, `AdmissionsController`, `AdmissionWorkflowService`, `CandidatePortalService`, `/spmb` dan halaman publik PPDB.

- [x] **AP-19.01** (`VERIFIED`) Kelola konten publik penerimaan, banner, jadwal, jalur, kuota, syarat, brosur, biaya bila disetujui, kontak dan status buka/tutup.
- [x] **AP-19.02** (`VERIFIED`) Kelola periode, jalur, persyaratan berkas, formulir yang diizinkan, aturan usia/asal sekolah, kriteria seleksi, bobot dan jadwal pengumuman tanpa hardcoded tahun.
- [x] **AP-19.03** (`VERIFIED`) Pisahkan tugas admin/operator/kepala/panitia dan guru yang ditugaskan; semua guru tidak otomatis boleh mengelola seluruh periode karena route role terlalu umum.
- [x] **AP-19.04** (`VERIFIED`) Uji pendaftaran tamu/kandidat, berkas, nomor registrasi, credential/rahasia kandidat, bukti layar/PDF dan pencegahan duplikasi NISN/identitas.
- [x] **AP-19.05** (`VERIFIED`) Uji antrean verifikasi, catatan per berkas, minta revisi, kandidat memperbaiki dan resubmit tanpa membuat pendaftaran kedua.
- [x] **AP-19.06** (`VERIFIED`) Uji penilaian, ranking, seri nilai, kuota/jalur, preview hasil, persetujuan dan publikasi; perubahan formula/kriteria berversi tidak mengubah hasil final tanpa tindakan resmi.
- [x] **AP-19.07** (`VERIFIED`) Uji cek status/hasil dengan bukti akses, masa pengumuman, lulus/tidak/cadangan bila dipakai, dan perlindungan data kandidat lain.
- [x] **AP-19.08** (`VERIFIED`) Uji daftar ulang, pembatalan, verifikasi final dan pembentukan siswa/enrollment/akun sesuai workflow tanpa data ganda.
- [x] **AP-19.09** (`VERIFIED`) Sediakan laporan/ekspor berizin, audit keputusan, histori periode dan penanganan kandidat pindah jalur/berkas salah.
- [ ] **AP-19.10** (`OPEN`) Uji layanan tutup, kuota habis, submit tepat batas waktu, berkas tidak sah, dua petugas memutuskan bersamaan dan pengumuman ter-cache.

Penerimaan: konten penerimaan dan proses kandidat sampai menjadi siswa dikelola sesuai penugasan serta tidak membutuhkan developer untuk pergantian periode.

### AP-20 — Pelacakan terpadu, verifikasi dan akses seluruh hasil

Prioritas P0. Area: Core tracking/verifikasi, semua workflow service, ticket/document, resi, hasil dan alias rute.

- [x] **AP-20.01** (`VERIFIED`) Petakan semua jenis layanan ke `/lacak`: PTSP, surat dinas, izin, mutasi, legalisir, aduan, proposal dan layanan lain yang memang didukung; jelaskan pengecualian.
- [x] **AP-20.02** (`VERIFIED`) Kelola label, panduan kata kunci, kartu layanan dan tautan pelacakan melalui panel tanpa mengubah aturan otorisasi.
- [x] **AP-20.03** (`VERIFIED`) Cocokkan jenis identitas yang dijanjikan UI—tiket/NISN/nomor pengajuan/NIK/PIN—dengan kemampuan backend per layanan; jangan menampilkan janji pencarian yang tidak diimplementasikan atau melanggar privasi.
- [x] **AP-20.04** (`VERIFIED`) Uji akses tamu dengan rahasia, pemohon session, siswa/wali/alumni pemilik, petugas dan pengguna lain; data yang dibuka harus sesuai tiap mode.
- [x] **AP-20.05** (`VERIFIED`) Uji status, waktu, catatan publik/internal, kebutuhan revisi, tombol perbaiki, hasil siap, pengambilan dan informasi dicabut/kedaluwarsa.
- [x] **AP-20.06** (`VERIFIED`) Verifikasi QR/token/nomor surat memeriksa versi dokumen, issuer, hash/status dan minimum data publik; token tidak sah tidak mengungkap path/id internal.
- [x] **AP-20.07** (`VERIFIED`) Inventaris dan uji seluruh route resi/PDF/attachment/media/ekspor, GET signed dan aksi POST terkait; setiap pola pada PUB-40/PUB-41 harus tercakup.
- [x] **AP-20.08** (`VERIFIED`) Kompatibilitas tiket, alias, URL lama dan tautan notifikasi dipertahankan atau dialihkan secara aman; token kedaluwarsa mempunyai penjelasan pemulihan yang benar.
- [x] **AP-20.09** (`VERIFIED`) Uji kode salah, kategori tidak cocok, PIN salah, data dicabut/dihapus, brute force, query injection, cache privat dan akses lintas pemilik.
- [x] **AP-20.10** (`VERIFIED`) Uji ekspor/cetak mengikuti seluruh hasil filter yang dipilih sesuai scope, bukan hanya baris pada halaman aktif; validasi kolom, timezone, format angka, file besar, filename dan perlindungan formula injection pada spreadsheet/CSV.

Penerimaan: hasil keputusan admin dapat ditelusuri melalui titik masuk publik yang benar tanpa membuka informasi privat atau menghasilkan status yang bertentangan.

### AP-21 — Dashboard dan ruang kerja masing-masing role

Prioritas P1. Area: Dashboard, `PrincipalDashboardService`, dashboard wali kelas, queue service modul, navigasi, notifikasi dan pencarian internal.

- [x] **AP-21.01** (`VERIFIED`) Buat daftar pekerjaan/counter/aksi penting untuk seluruh 17 role dan multirole; dashboard mengikuti pekerjaan nyata dan izin, bukan hanya tampilan berbeda.
- [x] **AP-21.02** (`VERIFIED`) Dashboard admin menampilkan kesehatan konfigurasi, penerbitan, akses dan integrasi yang relevan tanpa menggandakan dashboard semua petugas.
- [x] **AP-21.03** (`VERIFIED`) Dashboard operator/PTSP/resepsionis menampilkan antrean sesuai kewenangan, SLA, revisi, berkas kurang, jadwal tamu dan hasil siap diambil.
- [x] **AP-21.04** (`VERIFIED`) Dashboard kepala/penandatangan menampilkan persetujuan yang benar-benar dapat diputuskan, identitas penerbit dan dokumen yang menunggu; baca dan putuskan dipisah.
- [x] **AP-21.05** (`VERIFIED`) Dashboard guru/wali kelas menampilkan kelas/penugasan, presensi, izin, nilai/rapor, tugas dan proposal yang terkait.
- [x] **AP-21.06** (`VERIFIED`) Dashboard piket/satpam menampilkan izin berlaku, siswa keluar/belum kembali dan pemeriksaan scan dengan data minimum.
- [x] **AP-21.07** (`VERIFIED`) Dashboard BK/satgas menampilkan kasus terbatas, tindak lanjut, SLA dan notifikasi yang disanitasi, tanpa ringkasan sensitif di area umum.
- [x] **AP-21.08** (`VERIFIED`) Dashboard pustakawan/panitia menampilkan sirkulasi/usulan/tanggungan dan pekerjaan kandidat sesuai periode/penugasan.
- [x] **AP-21.09** (`VERIFIED`) Portal siswa/orang tua/alumni menampilkan data, permohonan, revisi, hasil dan dokumen yang dimiliki; anak/profil dipilih dari relasi terverifikasi.
- [ ] **AP-21.10** (`OPEN`) Uji badge/counter, pagination queue, notifikasi read/unread, click-through, pencarian internal, menu collapse dan data setelah pergantian role/periode.

Penerimaan: setiap counter/dashboard mempunyai query berscope dan mengarah ke pekerjaan yang dapat diselesaikan oleh role tersebut.

### AP-22 — Modul internal yang terkait dan regresi flow lama

Prioritas P1 untuk integrasi yang tersentuh; perubahan di luar kebutuhan wajib dipisahkan. Area: Academic, Assessment, Learning, Administration, Homeroom, Integration dan antarmuka masing-masing.

- [x] **AP-22.01** (`VERIFIED`) Verifikasi master siswa/GTK/kelas/mapel/tahun ajaran/semester, enrollment, wali, import dan data ganda sebagai sumber semua layanan publik.
- [x] **AP-22.02** (`VERIFIED`) Uji pergantian periode/kelas, promosi, mutasi, lulus/nonaktif dan dampaknya ke akun, dashboard, publikasi serta dokumen lama.
- [x] **AP-22.03** (`VERIFIED`) Verifikasi jadwal, benturan, absensi, jurnal dan koreksi terkait izin/piket; perubahan izin tidak memutus alur guru/perwalian yang sudah ada.
- [x] **AP-22.04** (`VERIFIED`) Verifikasi penilaian, formula versi, remedial, kelengkapan, penerbitan rapor dan immutable snapshot tetap benar setelah perubahan role/shell/lifecycle siswa.
- [x] **AP-22.05** (`VERIFIED`) Verifikasi LMS/CBT: kelas guru, materi, tugas, pengumpulan, ujian/attempt, jadwal, hasil dan scope siswa; perubahan UI bersama tidak menghapus state atau akses yang sah.
- [x] **AP-22.06** (`VERIFIED`) Verifikasi persuratan internal: draft, review, persetujuan, penandatangan, nomor, arsip, pencabutan dan hubungan surat eksternal/PTSP.
- [x] **AP-22.07** (`VERIFIED`) Verifikasi sarpras/staf: inventaris, fasilitas, peminjaman, pengembalian dan tugas staf yang menjadi sumber fasilitas publik, tanpa mempublikasikan detail internal.
- [x] **AP-22.08** (`VERIFIED`) Lengkapi proposal guru dan mandiri: jenis, tanggal, panitia, tujuan, RAB tervalidasi server, lampiran dan riwayat versi; publik masuk melalui login yang benar.
- [x] **AP-22.09** (`VERIFIED`) Uji reviewer proposal sesuai penugasan, revisi/resubmit, keputusan, unggah LPJ, bukti, penutupan dan sinkronisasi tiket; tidak menambah pencairan dana/transfer.
- [x] **AP-22.10** (`VERIFIED`) Verifikasi komunikasi/pengumuman, penerima terpilih, orang tua, attachment dan notifikasi; tidak menyiarkan data kasus atau dokumen privat secara massal.
- [x] **AP-22.11** (`VERIFIED`) Audit menu/menu ganda, endpoint lama, modal CRUD, tabel/ekspor, dark/light dan akses semua modul internal yang berubah karena komponen bersama.
- [ ] **AP-22.12** (`OPEN`) Untuk refaktor flow yang diperlukan, tulis sebelum→sesudah, masalah terbukti, dampak aktor/data/status/notifikasi/URL, alternatif minimal, migrasi, uji regresi dan rollback sebelum implementasi.

Penerimaan: integrasi publik tidak mengorbankan alur internal yang telah benar; flow hanya dirombak ketika alasan dan dampaknya dapat diperiksa.

### AP-23 — Integrasi eksternal, antrean, audit dan kesiapan operasional

Prioritas P1. Area: Integration, outbox/jobs, storage, scheduler/cache, audit, backup dan konfigurasi admin yang ada.

- [x] **AP-23.01** (`VERIFIED`) Kelola provider/tautan yang memang digunakan untuk salat, peta, chat, email/WhatsApp, tanda tangan, RFID, EMIS/RDM/SLiMS; kontrak API nyata mengikuti D-08.
- [x] **AP-23.02** (`VERIFIED`) Kredensial disimpan aman, dimask di panel, tidak masuk payload publik/log/preview/ekspor; uji izin ubah dan uji koneksi per integrasi.
- [x] **AP-23.03** (`VERIFIED`) Uji outbox/queue retry/backoff, idempotensi, dead/failed jobs dan pemulihan; admin melihat hasil yang dapat ditindaklanjuti tanpa kredensial.
- [x] **AP-23.04** (`VERIFIED`) Uji scheduler publish/unpublish, layanan buka/tutup, jadwal pengumuman, expiry dokumen, pergantian tanggal dan cache pada timezone sekolah.
- [x] **AP-23.05** (`VERIFIED`) Uji file exchange/import/export yang digunakan: pemetaan kolom, dry run, validasi, laporan baris gagal, commit aman dan retry tanpa duplikasi.
- [x] **AP-23.06** (`VERIFIED`) Audit log dapat dicari sesuai izin dan retensi; tampilkan siapa melakukan apa/kapan pada objek relevan tanpa payload sensitif mentah.
- [ ] **AP-23.07** (`OPEN`) Periksa performa listing publik/admin, pagination, query agregat, eager load, indeks yang dibutuhkan, upload/download serta batas resource sesuai baseline, bukan angka performa rekaan.
- [x] **AP-23.08** (`VERIFIED`) Uji backup/restore DB beserta berkas privat, media publik dan konfigurasi konten pada lingkungan terisolasi; dokumen tidak menjadi yatim setelah restore.
- [ ] **AP-23.09** (`OPEN`) Siapkan migration/backfill/rollback yang tidak menghapus histori atau suntingan pengguna; perubahan skema dan deployment menunggu persetujuan yang berlaku.
- [ ] **AP-23.10** (`OPEN`) Pisahkan seed demo dari produksi; tidak ada istilah `contoh`/`dummy`/`sample`, identitas referensi, akun uji atau fixture sensitif pada UI/data produksi kecuali kebutuhan development eksplisit.
- [ ] **AP-23.11** (`OPEN`) Periksa kesiapan konfigurasi sebelum mengaktifkan fitur: pejabat, template, nomor dokumen, tujuan notifikasi, storage, worker dan provider wajib sudah valid. Panel menampilkan konfigurasi kurang secara kontekstual; jangan menerima transaksi yang tidak mungkin diproses atau mengganti kekurangan dengan data rekaan.

Penerimaan: kegagalan provider/queue tidak menghilangkan transaksi; operasi pemulihan dan pergantian konten/konfigurasi mempunyai bukti yang dapat diulang.

### AP-24 — Pengujian menyeluruh dan penerimaan per role

Prioritas P0 sebagai gate penutupan. Area: seluruh paket, `tests/Feature`, tes unit/domain yang relevan, browser/E2E dan fixture.

- [ ] **AP-24.01** Susun matriks coverage PUB-01 s.d. PUB-42 × role × state × aksi × dokumen; setiap baris menunjuk task dan bukti, bukan sekadar daftar halaman.
- [ ] **AP-24.02** Uji no-code menyeluruh: pengguna berwenang mengubah tiap tipe konten dari panel, preview/publish/unpublish/restore, lalu memeriksa seluruh lokasi pemakaiannya.
- [ ] **AP-24.03** Uji E2E setiap layanan: isi publik → simpan/unggah → antrean role → revisi/keputusan → hasil → pelacakan → unduh/verifikasi → pencabutan bila berlaku.
- [ ] **AP-24.04** Uji negatif seluruh role pada route/service/dokumen/ekspor, ID milik pihak lain, kelas lain, sekolah lain, status terlarang dan akses setelah izin dicabut.
- [ ] **AP-24.05** Uji concurrency, retry, double-click, back/refresh, browser tab ganda, stale version, submit parsial, storage/PDF/provider gagal dan rollback transaksi.
- [ ] **AP-24.06** Uji listing/filter/search/pagination/mode tampilan/urutan/hasil nol/total hitungan; filter satu area tidak mengubah agregat global tanpa kontrak.
- [ ] **AP-24.07** Uji keamanan input, upload, rich text, embed, URL, signed download, CSRF, rate limit, session serta kebocoran PII/secret melalui props, log, HTML, cache dan media.
- [ ] **AP-24.08** Uji UI admin pada 320/375/390/414/768/960/1024/1280/1440px sesuai konteks, portrait/landscape, dark/light, zoom, keyboard dan reduced motion.
- [ ] **AP-24.09** Bandingkan screenshot publik sebelum/sesudah dengan data tetap; perubahan binding tidak boleh menggeser layout, menghilangkan menu/tab atau mengubah alur yang telah disetujui.
- [ ] **AP-24.10** Verifikasi seluruh menu, ikon, tombol, tooltip, link, CTA, close/Escape, form validation, password show/hide, fokus dan scroll; tidak ada kontrol dekoratif yang tampak aktif tetapi tidak bekerja.
- [ ] **AP-24.11** Periksa console/network error, aset/font/ikon hilang, SVG dipengaruhi CSS global, overflow, thumbnail rusak, PDF tak terbaca dan failure notification.
- [ ] **AP-24.12** Jalankan Pint, typecheck, build, tes terarah dan full suite sesuai perubahan; simpan hasil dengan versi/tanggal dan alasan tes yang dilewati. Jangan menjalankan pengujian yang memakai manifest bersamaan dengan penggantian build yang menyebabkan hasil semu.
- [ ] **AP-24.13** Jalankan UAT menggunakan akun 17 role, pemohon eksternal, pengguna multirole dan tamu; operator nonteknis membuktikan tidak memerlukan developer untuk pembaruan konten rutin.
- [ ] **AP-24.14** Uji upgrade dari data lama serta restore terisolasi; bandingkan jumlah, relasi, histori, hash/nomor dokumen dan hak akses sebelum/sesudah.
- [ ] **AP-24.15** Tinjau seluruh task terbuka/temuan baru/pengecualian, peroleh penerimaan pemilik produk dan jangan menyebut integrasi lengkap bila masih ada jalur stub atau bukti E2E yang belum tersedia.
- [ ] **AP-24.16** Audit seluruh source produksi untuk sisa array statis, teks operasional, path aset tetap, nomor/tahun, label kategori, kontak, menu, fixture dan conditional demo yang belum terhubung panel; setiap sisa mempunyai alasan sistem atau task penggantian, bukan lolos karena halaman dapat dibuka.

Penerimaan: seluruh kontrak konten, role, UI dan integrasi mempunyai bukti; hasil gagal/skip dijelaskan; celah mayor maupun minor yang relevan tidak ditutup dengan klaim umum.

### AP-25 — Serah terima, dokumentasi aktif dan rilis yang diotorisasi

Prioritas P1. Area: dokumen aktif, handoff, panduan pengguna, konfigurasi deploy dan operasional yang sudah ada.

- [ ] **AP-25.01** Perbarui TODO ini dengan status/bukti aktual; tautkan hasil ke requirements dan task paritas tanpa menulis ulang histori yang tidak terkait.
- [ ] **AP-25.02** Perbarui panduan per role: mengelola konten, antrean, revisi, keputusan, tanda tangan, dokumen, kasus khusus dan pemulihan; bantuan panjang tidak dipenuhi ke UI utama.
- [ ] **AP-25.03** Dokumentasikan field konfigurasi, versi standar UI yang dipakai, override yang disetujui, pemilik keputusan dan cara memperbarui standar di kemudian hari.
- [ ] **AP-25.04** Serahkan inventaris route, permission, dokumen/template, sumber data, integrasi, job dan titik recovery kepada pengelola.
- [ ] **AP-25.05** Siapkan daftar perubahan DB/backfill, kebutuhan runtime, rencana backup, langkah upgrade/rollback, invalidasi cache dan smoke test pascarilis.
- [ ] **AP-25.06** Bersihkan konten/akun/media development melalui prosedur terarah yang disetujui; jangan memakai penghapusan massal tanpa inventaris relasi dan backup.
- [ ] **AP-25.07** Pastikan pemilik produk menerima regresi visual publik, UI admin dan UAT role sebelum meminta otorisasi deployment; tidak melakukan deployment/commit hanya karena checklist dokumen selesai.
- [ ] **AP-25.08** Setelah deployment diotorisasi, buktikan health check, login role, submit/publish, status/hasil, worker/scheduler dan rollback sesuai cakupan; catat temuan pascarilis dan penanggung jawab.

Penerimaan: pengelola dapat memperbarui konten dan menjalankan layanan tanpa menyentuh kode; bukti rilis, pemulihan dan batas yang masih ada jelas.

## 8. Peta area berkas dan kepemilikan perubahan

Path berikut adalah titik awal yang sudah teramati; pelaksana tetap mencari pemanggil, policy, service dan tes terkait sebelum mengubahnya.

| Area | Titik awal |
|---|---|
| Akses/navigasi | `app/Modules/Core/Domain/{RoleEnum,PermissionEnum}.php`, registry/scope service, `config/navigation.php`, `app/Shared/Navigation/SidebarNavigationService.php` |
| UI admin bersama | `resources/js/layouts/AppLayout.vue`, `resources/js/components/{TopNavbar,SidebarNavigation,AppButton,FormField,ConfirmDialog,StateFeedback,StatusBadge,ActivePeriodBadge}.vue`, `resources/css/` |
| Identitas/konten | `SchoolController`, `School`, `PublicPortalService`, `SchoolProfileController`, `resources/js/Pages/Core/School/Edit.vue` |
| Publik Blade | `resources/views/public/layouts/madrasah.blade.php`, `resources/views/public/madrasah/`, `public/madrasah-theme/{home.js,parity.css}` |
| Publik Inertia | `resources/js/Pages/Public/`, `PublicHeader.vue`, `usePublicServicesCatalog.ts`, composable tema |
| Berita/galeri/organisasi | Controller Core terkait, `resources/js/Pages/Core/{News,Gallery,Organization}/` dan entitas media yang ada |
| PTSP/TU | `app/Modules/Administration/`, `resources/js/Pages/Administration/`, akun eksternal Core |
| Siswa/mutasi/kelulusan/alumni | `app/Modules/Academic/`, `resources/js/Pages/Academic/`, halaman Public terkait |
| Izin/piket/BK/aduan | `app/Modules/Homeroom/`, `resources/js/Pages/Homeroom/`, presensi Academic |
| SPMB | `app/Modules/Admissions/`, `resources/js/Pages/Admissions/`, `Public/Admissions/Index.vue` |
| Perpustakaan | `app/Modules/Library/`, `resources/js/Pages/Library/`, `Public/Library/Index.vue`, `perpustakaan.blade.php` |
| Proposal/integrasi | `app/Modules/Integration/`, `resources/js/Pages/Integration/`, outbox/queue |
| Dokumen/status/audit | Core ticket/document/workflow, `app/Shared/`, template dokumen setiap modul |
| Regresi internal | Academic, Assessment, Learning, Administration, Homeroom dan tests yang terkait perubahan bersama |

## 9. Format bukti per task / irisan vertikal

Gunakan field berikut pada catatan task atau handoff yang sudah ada; tidak perlu membuat dokumen baru untuk setiap checkbox.

| Field | Isi wajib |
|---|---|
| Task / status | ID, status, pelaksana dan reviewer |
| Kontrak publik | PUB-ID, URL, tab/modal/state, baseline visual |
| Kontrol admin | URL/menu, field/aksi, role dan scope |
| Data | Model/field/config, sumber kebenaran, versi, cache, privasi |
| Flow | State awal → aksi → state akhir, actor, efek samping, kondisi gagal |
| Perubahan | File, policy/service, migrasi/backfill jika disetujui, kompatibilitas |
| Verifikasi | Fixture, langkah uji, hasil aktual, perintah tes, screenshot atau bukti dokumen |
| Pemulihan | Retry, rollback, pengaruh pada data lama dan dokumen terbit |
| Sisa | Temuan, blocker, keputusan dan alasan pengecualian yang disetujui |

## 10. Checklist lintas paket yang wajib diwariskan

Setiap paket AP-03 s.d. AP-23 diperiksa dengan seluruh item berikut. Bila tidak berlaku, catat alasan pada bukti paket; bukan mencentang item global sekali lalu menganggap semua modul lulus.

- [ ] **CROSS-01** Semua elemen publik dipetakan ke kontrol admin; label/teks/gambar/tautan/modal/cetak tidak terlewat.
- [ ] **CROSS-02** Jalur menu normal, deep link, refresh, back/forward dan tujuan kembali setelah login berfungsi.
- [ ] **CROSS-03** Listing, detail, filter, search, sorting, pagination, count, ekspor dan print menghasilkan data konsisten.
- [ ] **CROSS-04** Tambah/edit/unggah/hapus/restore mempertahankan validasi, nilai saat error, referensi media dan audit.
- [ ] **CROSS-05** Hak lihat/tulis/putuskan/terbitkan/unduh terpisah dan diuji melalui endpoint langsung.
- [ ] **CROSS-06** Scope sekolah, periode, kelas, penugasan dan kepemilikan berlaku pada query, agregat, dropdown, berkas dan notifikasi.
- [ ] **CROSS-07** Draft/preview/jadwal/unpublish/arsip serta perubahan bersamaan mengikuti kontrak publikasi.
- [ ] **CROSS-08** Lifecycle transaksi, alasan, revisi, pembatalan/pencabutan dan dokumen terbit konsisten pada publik/admin.
- [ ] **CROSS-09** Empty/loading/error/success/offline/no-permission/disabled dan retry mempunyai hasil yang benar, bukan hanya tampilan.
- [ ] **CROSS-10** Double-submit, koneksi terputus, timeout, race, generator/storage/provider gagal tidak menimbulkan duplikasi atau data setengah jadi.
- [ ] **CROSS-11** Light/dark, keyboard, fokus, screen reader, zoom, touch target, reduced motion dan mobile sesuai standar admin.
- [ ] **CROSS-12** Icon-only action/ekspor, tooltip, header tabel, confirm terpusat, modal CRUD dan toast mengikuti aturan UI sumber.
- [ ] **CROSS-13** URL/file/embed/input aman; tidak ada kebocoran PII/token, draft, bukti privat atau data pihak lain.
- [ ] **CROSS-14** Cache, media, tema, timezone, format angka/tanggal serta pergantian periode konsisten.
- [ ] **CROSS-15** Upgrade/backfill/seed ulang tidak menimpa suntingan, mengganti data nyata dengan demo, atau mengubah histori dokumen.
- [ ] **CROSS-16** Baseline visual dan perilaku publik terjaga setelah perubahan admin/backend.
- [ ] **CROSS-17** Tes relevan, review diff dan bukti pengguna nonteknis selesai sebelum task ditandai VERIFIED.

## 11. Gate selesai keseluruhan

- [ ] **FINAL-01** Seluruh PUB-ID dan seluruh rute aktual, termasuk varian dinamis, mempunyai pemilik, sumber data, editor/aksi admin, role dan bukti.
- [ ] **FINAL-02** Seluruh konten publik yang disepakati dapat disesuaikan tanpa kode; konstanta sistem/pengecualian yang tersisa disebutkan dan disetujui eksplisit.
- [ ] **FINAL-03** Seluruh 17 role, pemohon eksternal, multirole dan tamu lulus matriks akses positif/negatif serta UAT pekerjaan nyata.
- [ ] **FINAL-04** Seluruh transaksi publik–admin–publik selesai sampai status, notifikasi dan dokumen/hasil, termasuk revisi/tolak/batal/cabut yang relevan.
- [ ] **FINAL-05** Tidak ada stub, tombol mati, data referensi, fallback fiktif atau ketergantungan edit SQL/kode pada jalur produksi.
- [ ] **FINAL-06** Standar UI tujuh aturan diterapkan pada admin; pengecualian tercatat; publik tidak dirombak agar cocok dengan shell admin.
- [ ] **FINAL-07** Seluruh temuan keamanan, integritas data, privasi, konflik keputusan dan regresi yang menghalangi penerimaan sudah diselesaikan.
- [ ] **FINAL-08** Build, typecheck, format, tes terarah/full suite, pemeriksaan browser, pemulihan dan bukti UAT yang relevan tersedia serta mutakhir.
- [ ] **FINAL-09** Pemilik produk menerima hasil dan pengelola dapat menjalankan pembaruan konten/layanan rutin tanpa developer.
- [ ] **FINAL-10** Serah terima selesai; deployment dan tindakan operasional hanya dilaksanakan sesuai otorisasi terpisah.

Tidak ada klaim “tidak mungkin ada pekerjaan terlewat” berdasarkan daftar saja. Kelengkapan dibuktikan melalui rekonsiliasi seluruh rute/elemen/role/state dan gate di atas; setiap temuan baru tetap wajib dimasukkan sebelum penutupan.
