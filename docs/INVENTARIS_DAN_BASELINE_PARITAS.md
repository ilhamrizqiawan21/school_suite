# Inventaris dan Baseline Paritas MAN 4 Banjar — School Suite

Tanggal penyusunan: 19 November 2026  
Referensi kontrak: [Requirements paritas](REQUIREMENTS_PARITAS_MAN4_BANJAR.md), [Rencana & bukti REA](RENCANA_PARITAS_MAN4_BANJAR.md), [TODO paritas](TODO_PARITAS_MAN4_BANJAR.md), [Peta artboard mockup](mockup/figcraft-paritas/CAKUPAN.md), dan [Peta alur & presentasi](PETA_ALUR_DAN_PRESENTASI_PARITAS.md).

Dokumen ini memenuhi **Checklist awal** pada [TODO paritas](TODO_PARITAS_MAN4_BANJAR.md) dan menjadi fondasi awal **M4-FND-01** dengan memisahkan status **Teramati di acuan**, **Diverifikasi di School Suite**, dan **Diterima klien**.

---

## 1. Perubahan Lokal yang Ada & Baseline Pemeriksaan

### 1.1 Status Git & Perubahan Lokal Saat Ini

- **Branch & HEAD**: `main` pada commit `9265cdb` (`HANDOFF MAN4 BANJAR`, 19 Nov 2026).
- **Isi commit `9265cdb` yang sudah ada**:
  - Dokumen paritas baru: `docs/PETA_ALUR_DAN_PRESENTASI_PARITAS.md`, `docs/PROMPT_INISIASI_GEMINI_PARITAS.md`, `docs/RENCANA_PARITAS_MAN4_BANJAR.md`, `docs/REQUIREMENTS_PARITAS_MAN4_BANJAR.md`, `docs/TODO_PARITAS_MAN4_BANJAR.md`, `docs/HANDOFF.md`.
  - Artefak mockup: `docs/mockup/figcraft-paritas/CAKUPAN.md`, `README.md`, `build_mockup.py`, `index.html`, `school-suite-paritas.figcraft` (122 artboard).
  - Perubahan UI pada commit `9265cdb`: `resources/js/Pages/Auth/Login.vue` (split layout portal warga sekolah + tautan kembali ke `/`) dan `resources/js/Pages/Public/School/Home.vue` (layout beranda publik Calm Academic).
- **Berkas lokal belum di-commit (harus dipertahankan)**:
  - `output/playwright/school_suite/` (28 screenshot QA role & landing per 15 Nov 2026).
  - `/public/build` diabaikan oleh `.gitignore`.

### 1.2 Baseline Pemeriksaan yang Dapat Diulang (Dijalankan 19 November 2026)

| Pemeriksaan | Perintah | Hasil 19 Nov 2026 | Catatan |
|---|---|---|---|
| Backend test suite | `php artisan test --compact` | **Lulus** — 453 tes, 451 lulus, 2 dilewati, 4.322 assertions (60.5s) | PHP 8.5.12 CLI |
| PHP formatting | `./vendor/bin/pint --test` | **Gagal pada baseline (4 berkas existing)** | `PasswordController.php`, `JournalService.php`, `artifacts/font-audit/collect-fixtures.php`, `artifacts/dark-mode/collect.php`. Jangan ubah file existing di luar scope tugas. |
| TypeScript / Vue check | `PATH="$CODEX_NODE_BIN:$PATH" ./node_modules/.bin/vue-tsc --noEmit` | **Lulus** (exit 0) | `node`/`npm` tidak ada di PATH default shell; memakai binary `node` di `/home/ilhamzp/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin`. |
| Production build | `PATH="$CODEX_NODE_BIN:$PATH" ./node_modules/.bin/vite build` | **Lulus** (852 modul, 8.21s) | Menghasilkan `public/build/manifest.json` dan bundel aset. |
| E2E Playwright | `npx playwright test` | **Belum tersedia** | `playwright.config.ts` mengarah ke `./tests/e2e`, direktori `tests/e2e` belum ada. |

---

## 2. Inventaris Ulang Situs Publik, Varian Portal, Bukti REA & Cakupan Visual

> **Batas pengamatan**: Lingkungan eksekusi saat ini tidak memiliki akses HTTP langsung ke `man4banjar.com`. Inventaris ulang ini merujuk bukti REA CLI 6.7.0 yang sudah direkam di [Rencana](RENCANA_PARITAS_MAN4_BANJAR.md), rincian artboard di `build_mockup.py`, serta ringkasan indeks publik `search_web` pada 19 Nov 2026 terhadap `man4banjar.com` / `man4banjar.sch.id`.

### 2.1 Perbedaan Varian Portal Utama vs Portal Akademik (MAN 4 Banjar)

| Aspek | Varian Portal Utama (`https://man4banjar.com/`) | Varian Portal Akademik (`https://www.man4banjar.com/akademik/index.php`) | Status di School Suite Saat Ini |
|---|---|---|---|
| **Fokus & Peran Layar** | Portal profil madrasah, berita, galeri, pengumuman, jadwal salat, trafik pengunjung, bantuan/chat, serta pintu masuk E-Layanan dan login. | Hub layanan digital akademik & tata usaha (`ptsp.php`, `tracking.php`, `surat_izin.php`, `legalisir.php`, `lapor.php`, `perpustakaan.php`, verifikasi siswa/alumni, proposal/LPJ mandiri, dan tautan ke `/rdm/`). | School Suite baru memiliki portal publik profil/program/kesiswaan/berita/galeri/kontak/PPDB (`Public/School/*`, `Public/News/*`, `Public/Gallery/*`, `Public/Organization/*`, `Public/Admissions/Index.vue`). |
| **Navigasi & Menu E-Layanan (Bukti REA)** | Desktop (`ev_dcf1b9...`, `ev_b91cf0...`): tombol **E-Layanan** membuka panel putih 3 kolom di atas hero gelap (*Persuratan & Tamu*, *Siswa & Alumni*, *Ketertiban & Sarana*) dengan ikon, judul, deskripsi singkat, plus tautan langsung **PTSP Online** dan **Lacak Berkas**. | Menyediakan akses langsung ke modul layanan dan **Verifikasi siswa/alumni** serta **Proposal/LPJ mandiri** yang tidak muncul di menu awal portal utama. | `PublicHeader.vue` hanya memiliki menu horizontal datar (`Beranda`, `Profil` dropdown, `Akademik`, `Kesiswaan`, `Berita`, `Galeri`, `Kontak`, tombol `SPMB`, tautan `Portal sekolah`). Belum ada panel E-Layanan 3 kolom maupun pencarian layanan. |
| **Tampilan Ponsel (Bukti REA 390×844)** | `ev_2d5163...`, `ev_e41ecc...`: panel slide kanan 1 kolom, tombol **Login Siswa** di atas, 3 kelompok layanan yang sama + **Buku Tamu Digital**. Catatan bug acuan: tombol chat menutupi tautan BK; pada 1280px menu desktop turun ke baris kedua. | Halaman layanan akademik menggunakan formulir per endpoint `.php`. | `Home.vue` & `PublicHeader.vue` memiliki drawer mobile satu kolom, namun belum memuat kategori E-Layanan dan belum diuji regresi terhadap overlap tombol bantuan. |
| **Sistem Eksternal Terhubung** | RDM (`/rdm/` login terpisah), EMIS (ekspor/impor), SLiMS (perpustakaan), penyedia jadwal salat, peta, WhatsApp/chat. | Mengarah ke `/rdm/` untuk rapor digital; layanan lokal untuk PTSP, izin, legalisir, lapor, perpustakaan. | `Integration` module masih kerangka kosong (`.gitkeep`); `Library` module masih kerangka kosong (`.gitkeep`). |

### 2.2 Peta Halaman Layanan Acuan: Bukti REA vs Belum Diuji Visual

| Halaman / Layanan Acuan | URL / Lokasi Acuan | Bukti REA Tercatat | Status Uji Visual / Interaksi |
|---|---|---|---|
| Beranda utama (Desktop 1280×720) | `man4banjar.com/` | `ev_a807c0...`, `ev_b91cf0...`, `ev_dcf1b9...` | **Teramati awal + screenshot menu terbuka**. |
| Beranda utama (Ponsel 390×844) | `man4banjar.com/` | `ev_e41ecc...`, `ev_2d5163...` | **Teramati awal + screenshot panel menu** (ditemukan overlap tombol chat). |
| PTSP Online | `/akademik/ptsp.php` | `ev_f2d63f...` (layar awal: panduan akun, unggah, verifikasi) | **Parsial (accessibility snapshot awal)**; interaksi submit & pasca-login belum diuji. |
| Lacak Berkas (Tracking) | `/akademik/tracking.php` | `ev_89c636...` (filter Semua, Izin, Legalisir, Mutasi, PTSP + input identitas/referensi) | **Parsial (accessibility snapshot awal)**; hasil pelacakan & penolakan belum diuji visual. |
| Pos Jaga / Satpam | (portal akademik) | `ev_0d6e50...` (layar akses + papan angka) | **Parsial (accessibility snapshot awal)**; panel pos setelah kode masuk belum diuji visual. |
| Pencatatan Pelanggaran / BK | (portal akademik) | `ev_911af8...` (kontrol kamera QR + pencarian siswa manual) | **Parsial (accessibility snapshot awal)**; form tindak lanjut & riwayat kasus belum diuji visual. |
| Kios Presensi | (portal akademik) | `ev_3f3d98...` (tap kartu, QR kamera, kontrol Gate, layar penuh) | **Parsial (accessibility snapshot awal)**; perilaku kontrol Gate & respon scan belum diuji visual. |
| Verifikasi Siswa & Alumni | (portal akademik) | `ev_5c5ba8...` (pencarian nama/NISN, validasi alumni, akses ke tracer & legalisir) | **Parsial (accessibility snapshot awal)**; hasil valid/dicabut & batas data publik belum diuji. |
| Proposal / LPJ Mandiri | (portal akademik) | `ev_72b43c...` (muat contoh webinar, panitia, editor RAB tambah/hapus, pelacakan realisasi) | **Parsial (accessibility snapshot awal)**; alur verifikasi/LPJ internal belum diuji visual. |
| Surat Izin Siswa | `/akademik/surat_izin.php` | Belum ada snapshot REA | **Belum diuji visual** (termasuk alur token dispensasi & validasi wali). |
| Legalisir & Kuasa/SPTJM | `/akademik/legalisir.php` | Belum ada snapshot REA | **Belum diuji visual**. |
| Siap Lapor (Pengaduan) | `/akademik/lapor.php` | Belum ada snapshot REA | **Belum diuji visual**. |
| Perpustakaan | `/akademik/perpustakaan.php` | Snapshot REA tersedia (`screenshot_halaman_asli/E-Perpustakaan Digital …`); salinan statis di `/perpustakaan/man4` | **Belum diuji visual & tes belum dijalankan** (lihat HANDOFF 21 Nov 2026). |
| Buku Tamu, Surat Dinas Eksternal, Mutasi, SKL, PPDB Publik, Alumni/Tracer, Pusat Unduh, Zona Integritas/IKM, Jadwal Salat, Profil/Direktori GTK | Berbagai sub-halaman publik & `/rdm/` | Belum ada snapshot REA terpisah | **Belum diuji visual**. |
| Seluruh Panel Internal di Balik Login (TU, Piket, Satpam, BK, Satgas, Pustakawan, Kepala, Panitia, Wali, Siswa, Alumni) | Di balik autentikasi acuan | Tidak ada akses | **Menunggu demo resmi / akun uji / materi pengelola**. |

---

## 3. Daftar Keputusan K-01 sampai K-08

| ID | Bahan Keputusan | Kebutuhan Terkait | Pemilik Keputusan | Bukti yang Dibutuhkan | Langkah Aman yang Bisa Dikerjakan Sekarang (Data Sintetik / Asumsi) |
|---|---|---|---|---|---|
| **K-01** | SOP alur layanan, matriks peran (17 peran) & data contoh | FND, TU, OPS, LIF, SERV, INT | **Pemilik Produk & Klien (Kepala/TU/Waka)**; Codex review arsitektur | Dokumen SOP sekolah atau persetujuan atas rancangan alur School Suite & matriks peran | Bangun kontrak data, policy server-side default-deny, state machine standar, dan seeder sintetik untuk seluruh alur inti. |
| **K-02** | Contoh dokumen resmi, batas ukuran/jenis berkas, format nomor, pejabat penandatangan, TTE/gambar TTD, retensi | FND, TU, OPS, LIF, INT | **TU / Kepala Madrasah (Klien)** | Contoh PDF/dokumen (resi PTSP, tanda terima surat, surat izin, mutasi, SKL, legalisir, kuasa, SPTJM), aturan penomoran & kebijakan TTD | Gunakan penyimpanan privat (`PrivateStorageService`), validasi MIME/ukuran ketat, template Blade berversi, dan placeholder kop/penandatangan yang dapat dikonfigurasi. |
| **K-03** | Jam layanan, kalender, aturan status, SLA, batas jam izin siswa, jadwal rilis PPDB/SKL, kebijakan notifikasi | PUB, TU, OPS, LIF, SERV | **Waka Kesiswaan, TU, Panitia PPDB (Klien)** | Aturan jam buka loket, batas jam pengajuan izin harian, SLA pengaduan, jadwal pengumuman | Implementasikan parameter konfigurasi per sekolah (jam layanan, cutoff izin, jadwal rilis, flag aktif/tutup layanan) dengan nilai default sintetik terdokumentasi. |
| **K-04** | Model pembaca RFID, format QR kartu pelajar/izin, protokol Gate kios, perangkat pos jaga & perilaku offline | OPS, SERV | **Tim Sarpras/IT Sekolah & Klien** | Spesifikasi reader (keyboard-wedge HID vs serial/API), contoh payload QR, definisi tombol `Gate` pada kios | Bangun antarmuka scan berbasis input keyboard/HID + kamera Web API, deduplikasi scan atomik, dan status perangkat/jaringan putus; tunda driver/protokol Gate khusus sampai kontrak jelas. |
| **K-05** | Sampel ekspor & kontrak/sandbox EMIS, RDM, SLiMS, penyedia jadwal salat, peta, chat, email/WhatsApp | INT, SERV, PUB | **Operator EMIS/RDM, Pustakawan, Admin TI Klien** | File sampel ekspor EMIS, keputusan arah integrasi RDM & SLiMS (tautan vs file vs API), kredensial sandbox/API | Buat layer adapter/kontrak di modul `Integration` dan `Library` dengan mekanisme ekspor/impor file berpreview + log rekonsiliasi serta fallback statis/lokal. |
| **K-06** | Aset sekolah (logo, foto), kategori layanan, persetujuan atribut publik GTK/Alumni, desain portal | UX, PUB, LIF, SERV | **Humas / Admin Sekolah & Pemilik Produk** | Aset branding sekolah klien, daftar atribut publik yang boleh tampil di direktori GTK & tracer alumni | Terapkan sistem token visual School Suite (berbasis `School::publicTheme()` + `design-tokens`), atribut publik opt-in/termoderasi, dan konten placeholder sintetik. |
| **K-07** | Demo modul proposal & LPJ (guru vs mandiri), mekanisme alur dana & batas fitur keuangan | INT | **Bendahara, Waka Kesiswaan/Kurikulum, Pemilik Produk** | Demo atau catatan aturan pengajuan proposal guru, proposal organisasi/siswa, revisi RAB, dan status dana (mengingat `SS-D-009` menunda ledger keuangan) | Bangun struktur proposal, panitia, dan editor item RAB dinamis (sesuai temuan REA `ev_72b43c...`) + pencatatan status realisasi non-ledger; jangan buat transaksi pembayaran/ledger tanpa persetujuan ADR. |
| **K-08** | Ukuran data pilot, pemilik operasional, ambang kinerja, jadwal UAT & acceptance rilis | REL | **Pemilik Produk & Tim Operasional Klien** | Daftar peserta UAT per peran, volume data historis untuk uji kinerja, tanda tangan UAT & penanggung jawab pemulihan | Siapkan skenario uji beban, drill backup/restore database + berkas privat terisolasi, health check, dan panduan operator/rollback. |

---

## 4. Pemetaan 17 Peran (M4-FND-02 & M4-FND-03)

Status peran saat ini di `database/seeders/PilotSchoolSeeder.php` dan `config/navigation.php`:

| Peran Target (`M4-FND-02`) | Role Key di School Suite Saat Ini | Status | Cakupan & Catatan Implementasi |
|---|---|---|---|
| Admin sekolah | `admin` | **Sudah ada** | Akses penuh konfigurasi sekolah, pengguna, keamanan, dan audit dalam `school_id` sendiri. |
| Operator | `operator` | **Sudah ada** | Pengelolaan master akademik, buku induk, persuratan, sarpras, publikasi, SPMB. |
| Kepala | `principal` | **Sudah ada** | Monitoring dashboard, rekap presensi, performa guru, persetujuan surat (`CorrespondenceController::approve`). |
| Guru | `teacher` | **Sudah ada** | Pembelajaran, bank soal, ujian, nilai, jurnal, presensi pertemuan, proposal kegiatan guru. |
| Wali kelas | `homeroom` | **Sudah ada** | Dashboard perwalian, observasi, kedisiplinan, dispensasi, rapor kelas perwalian. Perlu perluasan untuk verifikasi izin/dispensasi (`M4-OPS-02`). |
| Siswa | `student` | **Sudah ada** | Portal materi, tugas, ujian, rapor, jadwal. Perlu perluasan untuk pengajuan izin (bila diizinkan), SKL, perpustakaan, proposal mandiri, Siap Lapor. |
| Guru BK | `counselor` | **Sudah ada di seeder** (belum dipetakan penuh di `navigation.php`) | Di `navigation.php` baru muncul di `staff-tasks`. Belum punya panel/rute khusus kasus BK (`M4-OPS-05`). |
| Pustakawan | `librarian` | **Parsial di `navigation.php`** (belum ada di `PilotSchoolSeeder.php`) | Modul `Library` masih kosong; perlu dibuatkan role + kebijakan akses perpustakaan (`M4-SERV-03/04`). |
| Wali siswa | — | **Belum ada** | Perlu role `guardian`/`parent` + relasi ke siswa (jangan campur dengan akun pegawai); untuk izin siswa (`M4-OPS-01`), mutasi (`M4-LIF-02`), pelacakan. |
| Alumni | — | **Belum ada** | Perlu role `alumni` atau akun pemohon alumni terverifikasi untuk legalisir (`M4-LIF-05`), kuasa/SPTJM (`M4-LIF-06`), portal & E-KTA (`M4-LIF-07`). |
| Panitia (PPDB/Kegiatan) | — (flag tabel di SPMB) | **Parsial** | Saat ini panitia SPMB ditugaskan dari user internal (`AdmissionsController::assign`). Perlu cakupan peran/penugasan terstruktur. |
| TU / PTSP | — | **Belum ada** (masih pakai `operator`) | Perlu dipisahkan atau dipetakan izin spesifik layanan PTSP, agenda surat eksternal, legalisir, mutasi. |
| Penyambut tamu (Resepsionis) | — | **Belum ada** | Untuk modul Buku Tamu Digital (`M4-TU-06`) tanpa akses ke data akademik sensitif. |
| Piket | — | **Belum ada** | Untuk penerbitan izin keluar-masuk siswa (`M4-OPS-04`) dan monitor kios/piket. |
| Satpam | — | **Belum ada** | Untuk layar akses pos jaga & validasi keluar/kembali (`M4-OPS-04`). |
| Satgas (TPPK/Aduan) | — | **Belum ada** | Untuk antrean terbatas pengaduan Siap Lapor (`M4-SERV-02`) dengan isolasi bukti & identitas anonim. |
| Pejabat penandatangan | — (`CorrespondenceSignatory`) | **Parsial** | Entitas penandatangan ada di `CorrespondenceSignatory`, tetapi peran verifikator/penandatangan lintas dokumen (SKL, legalisir, surat) perlu disatukan. |
| Pemohon eksternal (PTSP/PPDB/Instansi) | — | **Belum ada** | Harus dibedakan dari akun pegawai (`M4-FND-03`); mendukung pengajuan ber-tiket / akun pemohon terpisah. |

---

## 5. Matriks Layar, Aksi, Rute & State untuk 48 ID + 6 Rincian REA

Keterangan kolom status terpisah:
- **Acuan**: `Teramati` (terlihat di REA/publik) / `Parsial` (hanya snapshot awal) / `Belum teramati` (di balik login / belum di-REA).
- **School Suite**: `Siap` / `Parsial` / `Belum ada`.
- **Klien**: `Disetujui` / `Menunggu klien`.

### 5.1 Paket FND — Fondasi dan Aturan Lintas Modul

| ID | Artboard Mockup | Fondasi Saat Ini di School Suite | Layar, Aksi, Rute & State yang Dibangun / Dipakai Ulang | Acuan | School Suite | Klien |
|---|---|---|---|---|---|---|
| **M4-FND-01** | `INFO`, `MENU`, `DASH` | `docs/REQUIREMENTS_PARITAS_MAN4_BANJAR.md`, `RENCANA_PARITAS_MAN4_BANJAR.md`, `CAKUPAN.md` | Dokumen inventaris ini (`INVENTARIS_DAN_BASELINE_PARITAS.md`) memetakan 48 ID, rute, layar, peran, status, dan K-01..K-08. Diperbarui tiap ada bukti REA/panel baru. | Parsial | Parsial | Menunggu klien |
| **M4-FND-02** | `AKSES`, `LOGIN` | `RoleEnum` (17 peran), `PermissionEnum`, `RolePermissionRegistryService`, `AccessScopeService`, `PilotSchoolSeeder`, `config/navigation.php` | 17 peran + izin standar disinkronkan; `AccessScopeService` menegakkan batas `school_id`, cakupan kelas wali kelas, wali murid terverifikasi, dan siswa. Menunggu integrasi layar layanan per peran (`TU`, `OPS`, `LIF`, `SERV`). | Belum teramati | Fondasi siap (teruji) | Menunggu klien (K-01) |
| **M4-FND-03** | `AKSES`, `DASH` | `schools.timezone`, `SchoolScopeService::getTimezone/localNow`, `external_applicants`, `student_guardians`, `AcademicQueryService` | Pemohon eksternal terpisah dari akun pegawai; relasi wali–siswa terverifikasi; snapshot identitas pada tiket tetap utuh setelah data induk/periode berubah. | Belum teramati | Fondasi siap (teruji) | Menunggu klien (K-01) |
| **M4-FND-04** | `AUDIT`, `REVIEW`, `HASIL` | `ServiceWorkflowMap`, `ServiceTicketService`, `service_tickets`, `service_ticket_transitions`, `AuditLogger` | Transisi status atomik (`lockForUpdate`), optimistic locking (`version`), `idempotency_key` pada pengajuan, alasan wajib untuk revisi/tolak/batal, riwayat & audit tak terhapus. | Belum teramati | Fondasi siap (teruji) | Menunggu klien (K-01) |
| **M4-FND-05** | `KUASA`, `TERBIT`, `HASIL-SIAP` | `PrivateStorageService::storeValidatedUpload`, `ServiceDocumentService`, `service_documents`, `PrivateFileController` | Validasi MIME/ekstensi/ukuran ketat, simpan privat + SHA-256, penomoran dokumen atomik per sekolah/jenis/tahun, `verification_token`, pembatalan beralasan, URL unduhan bertanda tangan berbasis otorisasi. Template PDF per layanan menyusul di TU/LIF/OPS. | Belum teramati | Fondasi siap (teruji) | Menunggu klien (K-02) |
| **M4-FND-06** | `TRACK`, `HASIL`, `VERIF` | `ServiceTicketService::generateTicketCode/lookupPublicStatus`, `/verifikasi-surat/{token}` | Tiket acak 8 karakter non-sekuensial (`PREFIX-YYYY-XXXXXXXX`), verifikasi rahasia pemohon (`Hash::check`), penolakan/kedaluwarsa/cabut tanpa bocor data privat, dan isolasi aduan anonim. | Teramati (`TRACK`, `VERIF`) | Fondasi siap (teruji) | Menunggu klien (K-01, K-02) |
| **M4-FND-07** | `AUDIT`, `INTEGRASI` | `NotificationOutboxService`, `notification_outboxes`, `DispatchNotificationOutboxJob` | Antrean outbox idempoten (`school_id, channel, idempotency_key`), redaksi payload sensitif sebelum simpan/log, retry aman tanpa menggandakan permohonan/keputusan. | Belum teramati | Fondasi siap (teruji) | Menunggu klien (K-03, K-05) |
| **M4-FND-08** | `AKSES`, `AUDIT` | `DataClassificationMap`, `AuditLogger`, `SanitizeSensitiveDataProcessor`, `DatabaseBackupService`, `RUNBOOK_BACKUP_RESTORE.md` | Matriks klasifikasi (`public`/`private`/`restricted`) & retensi per layanan aktif; sanitasi kunci sensitif tambahan (`kronologi`, `verification_secret`, `guardian_phone`, dll.) pada log & audit. Drill pemulihan MySQL + lampiran layanan menyusul di `REL`. | Belum teramati | Fondasi siap (teruji) | Menunggu klien (K-02, K-08) |

### 5.2 Paket UX — Desain Bersama Seluruh Aplikasi

| ID | Artboard Mockup | Fondasi Saat Ini di School Suite | Layar, Aksi, Rute & State yang Dibangun / Dipakai Ulang | Acuan | School Suite | Klien |
|---|---|---|---|---|---|---|
| **M4-UX-01** | `HOME`, `STATE`, `DASH` | `School::publicTheme()`, `publicThemeStyle`, `shell.css`, `buttons.css`, `AppButton.vue`, `AppCard.vue`, `StatusBadge.vue` | Konsolidasi token visual (warna, tipografi, jarak, radius, fokus ring, panel terang `--public-panel-surface`, status badge berikon/teks `StatusBadge.vue`). | Teramati (Beranda/Menu) | Komponen siap (menunggu build/QA) | Menunggu klien (K-06) |
| **M4-UX-02** | `MENU`, `M-MENU` | `PublicHeader.vue`, `usePublicServicesCatalog.ts` | Panel E-Layanan desktop berkelompok (3 kolom + deskripsi singkat + pencarian) dan drawer mobile 1 kolom + akses login, `Escape` focus-restore, serta clearance bawah. Uji `1280×720`, `390×844`, `1024px`. | Teramati (`ev_dcf1b9`, `ev_2d5163`) | Komponen siap (menunggu build/QA) | Menunggu klien (K-06) |
| **M4-UX-03** | `DASH`, `Q-PTSP`, `REVIEW` | `SidebarNavigation.vue`, `TopNavbar.vue`, `Breadcrumbs.vue`, `ActivePeriodBadge.vue`, `AppLayout.vue` | Sinkronisasi menu sidebar berbasis izin untuk peran baru (`visible_keys`), konsistensi shell internal (judul, breadcrumb, periode aktif, filter, tabel padat, pagination, detail, modal, unggah, cetak). | Belum teramati | Shell siap (menu layanan menyusul) | Menunggu klien (K-01) |
| **M4-UX-04** | `STATE`, `GAGAL`, `M-IZIN` | `FormField.vue`, `StateFeedback.vue`, `ConfirmDialog.vue`, toast error `app.css` | Komponen state standar lintas layanan: label + petunjuk + wajib `*`, inline error per field, loading skeleton, empty state, error + retry, konfirmasi `alertdialog`, focus-restore & touch target `>= 44px`. | Parsial | Komponen siap (menunggu build/QA) | Menunggu klien |

### 5.3 Paket PUB — Portal dan Konten Publik

| ID | Artboard Mockup | Fondasi Saat Ini di School Suite | Layar, Aksi, Rute & State yang Dibangun / Dipakai Ulang | Acuan | School Suite | Klien |
|---|---|---|---|---|---|---|
| **M4-PUB-01** | `HOME`, `M-HOME` | `/` (`Home.vue`), `SchoolProfileController::home`, `PublicPortalService` | Header & hero beranda dengan 4 pintasan utama (**PPDB Online**, **PTSP Online**, **Siap Lapor**, **Lacak Berkas**), ringkasan jadwal salat & statistik kunjungan anonim, dan tautan media sosial. | Teramati | Teruji backend (menunggu build/QA) | Menunggu klien (K-06) |
| **M4-PUB-02** | `MENU`, `M-MENU` | `/e-layanan` (`Public/Services/Index.vue`), `usePublicServicesCatalog.ts` | Direktori & katalog E-Layanan publik berkelompok + filter kategori + pencarian judul/sinonim + state kosong. | Teramati (`ev_b91cf0`, `ev_e41ecc`) | Teruji backend (menunggu build/QA) | Menunggu klien (K-03, K-06) |
| **M4-PUB-03** | `PROFIL`, `GTK`, `PROGRAM`, `PRESTASI`, `KONTAK` | `/direktori-gtk` (`Teachers.vue`), `/prestasi` (`Achievements.vue`), `/kontak` (`Contact.vue`), `AcademicQueryService::getPublicDirectoryTeachers` | Direktori GTK publik tanpa NIP/telepon/email, halaman Prestasi & Agenda, serta jam layanan & tautan peta pada `/kontak`. | Teramati | Teruji backend (menunggu build/QA) | Menunggu klien (K-06) |
| **M4-PUB-04** | `BERITA`, `GALERI`, `PUBLIKASI` | `/berita`, `/galeri`, `school_news` (`category`, `video_url`, `source_url`, `is_archived`) | Dukungan kolom kategori, video, tautan sumber, arsip pada `school_news` serta isolasi ketat `draft` agar tidak bocor ke publik/URL langsung. | Teramati | Teruji backend | Menunggu klien (K-06) |
| **M4-PUB-05** | `UNDUH` | `/pusat-unduh` (`Downloads/Index.vue`), `PublicDownload`, `PublicPortalAdminController` | Halaman publik Pusat Unduh dengan kategori, versi, ukuran file, statistik unduh, isolasi dokumen unpublished (404), serta endpoint admin `/pengaturan/pusat-unduh`. | Teramati | Teruji backend (menunggu build/QA) | Menunggu klien (K-02) |
| **M4-PUB-06** | `ZI`, `IKM` | `/zona-integritas` (`Integrity/Index.vue`), `PublicIkmSurvey`, `PublicPortalAdminController` | Maklumat pelayanan, 6 area perubahan, ringkasan agregat IKM terverifikasi, formulir penilaian, serta moderasi admin (`is_approved`). | Teramati | Teruji backend (menunggu build/QA) | Menunggu klien (K-06) |
| **M4-PUB-07** | `SALAT`, `TRAFIK` | `/jadwal-salat` (`Prayer/Index.vue`), `PublicVisitStat`, `PublicPortalService` | Jadwal salat sesuai zona waktu sekolah + fallback lokal, statistik kunjungan anonim harian/bulanan/total tanpa PII. | Teramati | Teruji backend (menunggu build/QA) | Menunggu klien (K-03, K-05) |

### 5.4 Paket TU — PTSP dan Tata Usaha

| ID | Artboard Mockup | Fondasi Saat Ini di School Suite | Layar, Aksi, Rute & State yang Dibangun / Dipakai Ulang | Acuan | School Suite | Klien |
|---|---|---|---|---|---|---|
| **M4-TU-01** | `PTSP`, `AKUN`, `PULIH` | `ExternalApplicantAuthService`, `PtspService`, `PtspAdminController`, `ExternalApplicantAuthController` | Katalog layanan PTSP (jenis, syarat, estimasi, jam buka, status aktif/tutup), CRUD katalog oleh TU (`/administrasi/ptsp`), serta registrasi/login/pemulihan akun pemohon eksternal terpisah dari pegawai (`/layanan/ptsp/akun/*`). | Parsial (`ev_f2d63f`) | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-03) |
| **M4-TU-02** | `PTSP`, `M-PTSP`, `RESI-PTSP` | `PtspWorkflowService`, `PtspPublicController`, `PrivateStorageService` | Form pengajuan PTSP (`/layanan/ptsp`), simpan/ubah draf & hapus lampiran draf, validasi berkas wajib, ringkasan & persetujuan, submit atomik menghasilkan tepat 1 resi (`PTSP-YYYY-XXXXXXXX`), halaman resi & cetak PDF. | Parsial (`ev_f2d63f`) | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-02) |
| **M4-TU-03** | `Q-PTSP`, `REVIEW`, `KOREKSI`, `PERBAIKI`, `TERBIT`, `HASIL-SIAP` | `PtspWorkflowService`, `PtspAdminController`, `Administration/Ptsp/Index.vue` | Antrean TU (`/administrasi/ptsp`) dengan filter sekolah/layanan/tanggal/status; aksi: verifikasi, minta perbaikan (membuka form `/layanan/ptsp/{ticket}/perbaiki` bagi pemohon), tolak beralasan, disposisi, terbitkan hasil digital/fisik, catat serah terima fisik. | Belum teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-02) |
| **M4-TU-04** | `TRACK`, `M-TRACK`, `FILTER-TRACK`, `HASIL` | `UnifiedTrackingService`, `UnifiedTrackingController`, `Public/Tracking/Index.vue` | **Pelacakan Terpadu** (`/lacak`) dengan filter `Semua`, `PTSP`, `Izin`, `Legalisir`, `Mutasi`, `Surat Eksternal`, `Pengaduan`, verifikasi identitas pemohon, rate limit, dan agregator status dari layanan pemilik. | Parsial (`ev_89c636`) | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-02) |
| **M4-TU-05** | `SURAT`, `D-SURAT`, `Q-SURAT`, `TANDA-TERIMA` | `CorrespondenceService`, `ExternalLetterController`, `CorrespondenceRecord` | Titik masuk publik/instansi **Kirim Surat Dinas Eksternal** (`/layanan/surat-dinas`) -> terbitkan tanda terima & tiket (`SRT-*`) -> langsung masuk agenda `surat-masuk` `Correspondence` + disposisi & sinkron pelacakan tanpa entri ganda. | Teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-02) |
| **M4-TU-06** | `TAMU`, `Q-TAMU`, `D-TAMU` | `GuestbookVisit`, `GuestbookService`, `GuestbookController` | **Buku Tamu Digital** (`/layanan/buku-tamu` tertutup untuk daftar publik; layar resepsionis/TU `/administrasi/buku-tamu`) untuk registrasi pengunjung, pegawai tujuan, keperluan, waktu datang/pulang, riwayat, rekap & ekspor CSV. | Teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01) |

### 5.5 Paket OPS — Presensi, Izin, Piket dan BK

| ID | Artboard Mockup | Fondasi Saat Ini di School Suite | Layar, Aksi, Rute & State yang Dibangun / Dipakai Ulang | Acuan | School Suite | Klien |
|---|---|---|---|---|---|---|
| **M4-OPS-01** | `IZIN`, `M-IZIN`, `TOKEN`, `GAGAL` | `AttendanceDispensation`, `StudentPermitToken`, `PermitWorkflowService`, `StudentPermitController` | Titik masuk **Surat Izin & Dispensasi** (`/layanan/izin-siswa`) untuk wali/siswa terverifikasi dan token sekali pakai terikat siswa/tujuan: kategori, tanggal mulai/akhir (tolak bentrok), batas jam lokal, aturan izin terencana, lampiran privat, tanda tangan, bukti PDF, serta konsumsi token atomik. | Teramati (publik link) | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-03) |
| **M4-OPS-02** | `Q-IZIN`, `D-IZIN`, `HASIL` | `PermitWorkflowService`, `AttendanceService`, `StudentPermitController` | Antrean verifikasi wali kelas/petugas (`/wali-kelas/izin`): setujui, kembalikan untuk revisi (`/layanan/izin-siswa/{id}/perbaiki`), tolak beralasan, koreksi keputusan dengan audit log, unduh lampiran/PDF, serta pembaruan & pembalikan `attendance_records` otomatis. | Belum teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-03) |
| **M4-OPS-03** | `PRESENSI`, `M-PRESENSI`, `SCAN`, `KONTROL-KIOS`, `D-PRESENSI` | `StudentCard`, `AttendanceKioskDevice`, `AttendanceKioskScan`, `StudentCardKioskService`, `StudentCardKioskController` | Manajemen kartu RFID/QR siswa (`/akademik/kartu-siswa`: daftar/cabut + registrasi perangkat sesuai hak), **Kios Presensi** (`/kios-presensi`) dengan mode Tap RFID, QR Kamera/manual, Layar Penuh, kontrol `Gate` simulasi (menunggu kontrak K-04), deduplikasi scan berulang atomik, data minimal di layar, serta sinkronisasi antrean offline (`/kios-presensi/sinkron`). | Parsial (`ev_3f3d98`) | Teruji backend, build & Vue (simulasi Gate K-04; menunggu QA browser) | Menunggu klien (K-04) |
| **M4-OPS-04** | `PIKET`, `D-PIKET`, `SATPAM`, `M-SATPAM`, `AKSES-POS`, `D-SATPAM` | `GatePass`, `PicketGateService`, `PicketGateController` | Ruang kerja **Piket** (`/piket/izin-keluar`: cari/pindai siswa, terbitkan pass keluar + kode, batalkan pass) dan layar **Pos Jaga Satpam** (`/pos-jaga`: gerbang PIN papan angka numerik sebelum panel terbuka, scan/input manual kode pass, catat keluar & kembali, tolak kedaluwarsa/batal/pakai ulang). | Parsial (`ev_0d6e50`) | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-04) |
| **M4-OPS-05** | `BK`, `Q-BK`, `D-BK` | `StudentIncident`, `BkDisciplineService`, `BkDisciplineController` | Ruang kerja **BK** (`/bk/kasus`, terbatas `admin` & `counselor`): pindai QR kamera atau cari siswa manual (`ev_911af8`), pilih kategori pelanggaran, waktu/lokasi/kronologi, eviden privat (`evidence`), pembaruan teraudit, ekspor CSV aman formula, serta tersembunyi dari daftar disiplin wali kelas. | Parsial (`ev_911af8`) | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01) |

### 5.6 Paket LIF — Siklus Siswa dan Alumni

| ID | Artboard Mockup | Fondasi Saat Ini di School Suite | Layar, Aksi, Rute & State yang Dibangun / Dipakai Ulang | Acuan | School Suite | Klien |
|---|---|---|---|---|---|---|
| **M4-LIF-01** | `PPDB`, `M-PPDB`, `D-PPDB`, `HASIL-PPDB` | `CandidatePortalService`, `AdmissionWorkflowService`, `AdmissionsController`, `AdmissionApplicant` | Portal publik/kandidat (`/ppdb`, `/ppdb/daftar`, `/ppdb/cek-hasil`, `/ppdb/perbaiki/{id}`, `/ppdb/bukti/{id}/pdf`, `/ppdb/daftar-ulang/{id}`): daftar dengan syarat berkas, bukti PDF, tahap perbaikan, gerbang rilis pengumuman, daftar ulang & konversi `Student` idempoten. | Teramati (info) | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-03) |
| **M4-LIF-02** | `MUTASI`, `D-MUTASI`, `RESI-MUTASI` | `StudentMutation`, `StudentMutationService`, `StudentMutationController`, `EnrollmentService` | Formulir publik/wali **Mutasi Masuk & Keluar** (`/layanan/mutasi`) dan antrean TU/operator (`/akademik/mutasi`): cek semester aktif, unggah syarat, review/revisi/tolak/setujui, surat rekomendasi/penerimaan PDF, pencabutan teraudit, serta perubahan status aman tanpa menghapus histori enrollment lama. | Teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-02) |
| **M4-LIF-03** | `SKL`, `D-SKL`, `RILIS-SKL` | `GraduationBatch`, `GraduationRecord`, `GraduationService`, `GraduationController` | Modul **Kelulusan & SKL** (`/akademik/kelulusan` & `/layanan/kelulusan`): persiapan daftar kelulusan, pengesahan pejabat, gerbang tanggal/jam rilis (tersembunyi sebelum rilis), cek hasil + PIN/pemilik, unduh PDF SKL QR, penerbitan ulang terkendali (cabut SKL lama), dan pencatatan pengambilan dokumen asli. | Teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-02, K-03) |
| **M4-LIF-04** | `VERIF`, `D-VERIF` | `ServiceDocumentService`, `AcademicQueryService`, `VerificationController` | Halaman publik **Verifikasi Siswa & Alumni** (`/verifikasi`) untuk validasi token/QR dokumen (`SKL`, `Legalisir`, `Surat Kuasa`, `SPTJM`, `E-KTA`, `Mutasi`) beserta status sah/dicabut/kedaluwarsa dan cek keaktifan siswa via NISN + nama tanpa membocorkan PII sensitif. | Parsial (`ev_5c5ba8`) | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-02) |
| **M4-LIF-05** | `LEGAL`, `M-LEGAL`, `D-LEGAL` | `LegalizationRequest`, `LegalizationService`, `LegalizationController` | Formulir **Legalisir Ijazah** (`/layanan/legalisir`) untuk alumni (jumlah lembar, keperluan, pengambilan langsung/kuasa, TTD, lampiran) dan register TU (`/administrasi/legalisir`: verifikasi berkas asli fisik, pengesahan PDF QR, pencatatan pengambil) + terhubung ke `/lacak`. | Teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-02) |
| **M4-LIF-06** | `KUASA`, `D-KUASA` | `LegalizationService`, `LegalizationController`, `barryvdh/laravel-dompdf` | Generator & template versi resmi **Surat Kuasa & SPTJM** (`/layanan/surat-kuasa`) terhubung opsional ke register legalisir (`LEG-...`), validasi identitas pemberi/penerima kuasa, TTD kedua pihak, unduh PDF privat terotorisasi & token verifikasi. | Teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-02) |
| **M4-LIF-07** | `ALUMNI`, `D-ALUMNI`, `IKA` | `AlumniProfile`, `AlumniService`, `AlumniController` | **Portal & Tracer Alumni** (`/alumni`), direktori terfilter (`/alumni/direktori`), dan moderasi admin (`/akademik/alumni`): pembaruan studi/karier/beasiswa/testimoni terverifikasi, preferensi opt-in atribut sensitif, moderasi admin sebelum tampil di direktori, serta penerbitan **E-KTA Alumni** PDF. | Teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-06) |

### 5.7 Paket SERV — Aduan dan Perpustakaan

| ID | Artboard Mockup | Fondasi Saat Ini di School Suite | Layar, Aksi, Rute & State yang Dibangun / Dipakai Ulang | Acuan | School Suite | Klien |
|---|---|---|---|---|---|---|
| **M4-SERV-01** | `ADUAN`, `M-ADUAN`, `RESI-ADUAN` | `ComplaintReport`, `ComplaintReportService`, `ComplaintController`, `ServiceTicketService` | Halaman **Siap Lapor** (`/layanan/lapor`): kategori (akademik, perundungan, fasilitas, staf, keselamatan, lainnya), pilih mode bernama/anonim (identitas anonim tidak disimpan), kronologi, lokasi, unggah bukti/foto kamera (`documentKind: 'evidence'`), serta penerbitan tiket rahasia untuk `/lacak`. | Teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-03) |
| **M4-SERV-02** | `Q-ADUAN`, `D-ADUAN`, `LAPOR-PUB` | `ComplaintReportService`, `ComplaintController`, `ServiceDocumentService` | Antrean terbatas **Satgas** (`/satgas/aduan`, terbatas `admin`, `task_force`, `counselor`): alokasi petugas, telaah, tindak lanjut, lampiran bukti selesai privat, balasan tiket, SLA, serta halaman **Transparansi Publik** (`/lapor/transparansi`) hanya menampilkan statistik & ringkasan termoderasi tanpa PII. | Belum teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-03) |
| **M4-SERV-03** | `PUSTAKA`, `KOLEKSI` | `LibraryCollection`, `LibraryService`, `LibraryController` | Portal **Perpustakaan** (`/perpustakaan`) & katalog koleksi (`/perpustakaan/kelola`): kategori, buku & e-book, pencarian, detail & ketersediaan, kontrol akses materi bertingkat (`public`, `members_only`, `staff_only`), CRUD koleksi oleh `admin`/`librarian`, serta mode fallback SLiMS lokal. | Teramati | Teruji backend, build & Vue (fallback SLiMS; menunggu QA browser) | Menunggu klien (K-05) |
| **M4-SERV-04** | `KUNJUNG`, `USUL`, `BEBAS`, `D-PUSTAKA` | `LibraryVisit`, `LibraryLoan`, `LibraryProcurementRequest`, `LibraryClearanceCertificate`, `LibraryService` | **Presensi Kunjungan** (`/perpustakaan/kunjungan`: QR kartu siswa / manual), **Usul Pengadaan** + keputusan pustakawan, sirkulasi pinjam/kembali stok atomik, **Pemeriksaan Bebas Pustaka** (`/perpustakaan/bebas-pustaka`: blokir bila ada pinjaman aktif; terbitkan PDF ber-token `/verifikasi` saat bebas pinjaman). | Teramati | Teruji backend, build & Vue (sirkulasi lokal; menunggu QA browser) | Menunggu klien (K-04, K-05) |

### 5.8 Paket INT — Proposal, LPJ dan Koneksi Eksternal

| ID | Artboard Mockup | Fondasi Saat Ini di School Suite | Layar, Aksi, Rute & State yang Dibangun / Dipakai Ulang | Acuan | School Suite | Klien |
|---|---|---|---|---|---|---|
| **M4-INT-01** | `PROPOSAL`, `D-PROPOSAL`, `EDITOR-RAB` | `ActivityProposal`, `ProposalLpjService`, `ProposalController` | Pengajuan **Proposal Kegiatan Guru** (`/guru/proposal` & antrean telaah `/administrasi/proposal`): jenis, penyelenggara, tanggal, tujuan, RAB beritem tervalidasi server, lampiran privat, revisi berversi, dan riwayat keputusan (`SS-D-009` tanpa pencairan dana). | Belum teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-01, K-07) |
| **M4-INT-02** | `LPJ`, `D-LPJ`, `EDITOR-RAB`, `STATUS-PROPOSAL` | `ActivityProposal`, `ProposalLpjService`, `ProposalController`, `ServiceTicketService` | Varian **Proposal/LPJ Mandiri** (`/layanan/proposal`): pengusul siswa/organisasi, tombol muat contoh webinar (`ev_72b43c`), susunan panitia, editor item RAB dinamis, pelacakan & unggah bukti realisasi LPJ, penutupan reviewer di `/administrasi/proposal` tanpa fitur pembayaran/bank (`SS-D-009`). | Parsial (`ev_72b43c`) | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-07) |
| **M4-INT-03** | `INTEGRASI`, `INTEGRASI-LOG` | `IntegrationConnection`, `IntegrationSyncLog`, `ExternalIntegrationService`, `IntegrationSettingsController` | Panel pengaturan & log rekonsiliasi integrasi (`/pengaturan/integrasi`) untuk **EMIS**, **RDM**, dan **SLiMS**: mekanisme tautan/file/API (`K-05`), pemetaan kolom, preview dry-run & laporan baris gagal parsial beserta recovery, ekspor CSV, dan retry idempoten. | Teramati (`/rdm/`) | Teruji backend, build & Vue (mode file/tautan; API sync menunggu K-05) | Menunggu klien (K-05) |
| **M4-INT-04** | `INTEGRASI`, `TRAFIK`, `SCAN` | `IntegrationConnection`, `ExternalIntegrationService`, `WhatsappMessageLog` | Konfigurasi kanal eksternal per sekolah (`/pengaturan/integrasi`: jadwal salat, peta, chat, email, WhatsApp, pembaca RFID/QR) tersimpan terenkripsi & termask di props/audit log, dengan uji kesehatan dan fallback layanan inti saat penyedia eksternal gagal. | Teramati | Teruji backend, build & Vue (menunggu QA browser) | Menunggu klien (K-04, K-05) |

### 5.9 Paket REL — Verifikasi, Peluncuran dan Serah Terima

| ID | Artboard Mockup | Fondasi Saat Ini di School Suite | Layar, Aksi, Rute & State yang Dibangun / Dipakai Ulang | Acuan | School Suite | Klien |
|---|---|---|---|---|---|---|
| **M4-REL-01** | `STATE`, `GAGAL` | `tests/Feature/*`, `RoleMatrixAccessTest`, `playwright.config.ts` | Suite Playwright E2E (`tests/e2e/`) pada 1280×720, lebar menengah, dan 390×844 + keyboard nav, fokus, rute langsung, alur sukses/revisi/tolak/ulang, serta regresi feature test Laravel. | — | Parsial (`tests/e2e` belum ada) | Menunggu klien |
| **M4-REL-02** | `AUDIT`, `INTEGRASI-LOG` | `DatabaseBackupService`, `BackupRestoreDrillTest`, `MysqlBackupRestoreTest`, `HealthCheckController` | Uji beban daftar besar/antrean, batas unggahan, migrasi pada salinan data lama, serta drill pemulihan MySQL + berkas privat di lingkungan terisolasi & sanitasi log. | — | Parsial (drill MySQL belum teruji live) | Menunggu klien (K-08) |
| **M4-REL-03** | `DASH`, `STATE` | `PilotSchoolSeeder`, `DEPLOYMENT_CHECKLIST.md`, `RUNBOOK_BACKUP_RESTORE.md` | Paket serah terima: data contoh paritas, role matrix teruji, SOP operator per peran, daftar fitur aktif, rencana rollback, health check, dan sign-off UAT per peran. | — | Parsial | Menunggu klien (K-08) |

---

## 6. Urutan Eksekusi Irisan Vertikal Berikutnya

1. **Irisan 1 — Paket FND Dasar + UX-01/02 + PUB-01/02**:
   - Fondasi peran/izin dasar & status publik layanan, token visual konsisten, serta `PublicHeader.vue` + panel **E-Layanan** 3 kolom desktop & drawer mobile dengan pencarian layanan, pintasan beranda (SPMB, PTSP Online, Siap Lapor, Lacak Berkas), dan verifikasi Playwright 1280×720, ~1024px, dan 390×844.
2. **Irisan 2 — Paket PUB Lanjutan (`PUB-03`..`PUB-07`)**:
   - Direktori GTK, Prestasi, Agenda, Pusat Unduh, Zona Integritas/IKM, Jadwal Salat (dengan fallback), Statistik Trafik anonim, dan Kanal Bantuan non-blocking.
3. **Irisan 3 — Paket TU Vertikal (`TU-01`..`TU-05`, lalu `TU-06`)**:
   - Katalog PTSP, akun pemohon eksternal, pengajuan ber-resi tunggal, antrean revisi/tolak/terbit TU, **Pelacakan Terpadu** (`FILTER-TRACK`), sambungan surat dinas eksternal ke agenda `Correspondence`, dan Buku Tamu Digital.
4. **Irisan 4 — Paket OPS, LIF, SERV, INT & REL**:
   - Berjalan bertahap sambil mengonfirmasi bukti K-01..K-08 dari klien dan demo/akun uji panel internal acuan.
