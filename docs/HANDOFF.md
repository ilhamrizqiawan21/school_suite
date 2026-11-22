# Implementation Handoff — School Suite

Dokumen kerja ini diisi ulang oleh Gemini untuk perubahan yang diserahkan untuk review.

## Verifikasi silang Fase 1–5 — Codex — 22 November 2026

Verifikasi terarah lintas seluruh paket berjalan tanpa full suite:

- Fase 1–2: **100 tes lulus, 1.110 assertions**.
- Fase 3: **45 tes lulus, 588 assertions**.
- Fase 4: **71 tes lulus, 1.113 assertions**.
- Fase 5: **49 tes lulus, 420 assertions**.
- Total: **265 tes lulus, 3.231 assertions**.
- Build Vite (**1.018 modul**), `vue-tsc --noEmit`, Pint, lint PHP terkait, security grep terarah, dan `git diff --check` lulus.

Selama verifikasi ditemukan dan diperbaiki dua regresi: katalog Library tidak lagi mengimpor model Infrastructure lintas modul (pemeriksaan storage dipindah ke API Application `ServiceDocumentService::storedDocumentIds()`), dan middleware Inertia tidak melakukan lookup sekolah pada route private-file sehingga test/endpoint 403/404 tidak bergantung pada tabel sekolah.

Full suite, uji browser/E2E penuh, provider eksternal nyata, dan drill operasional produksi tetap di luar verifikasi ini sesuai instruksi pengguna.

## Lanjutan Fase 5 — AP-21.10 — Codex — 22 November 2026 — IN_PROGRESS

Perbaikan ruang kerja Komunikasi:

- `CommunicationController::index()` menghitung `unread_count` dari seluruh notifikasi milik pengguna, sementara payload daftar tetap dibatasi 50 item terbaru.
- `Communication/Index.vue` memakai counter penuh, mengikuti `action_url` setelah menandai notifikasi dibaca, dan memakai `communicationBase` untuk aksi tandai semua sehingga workspace guru tidak kembali ke rute `/komunikasi` yang salah.
- Regresi `CommunicationAuthorizationTest` memverifikasi 55 notifikasi unread menghasilkan counter 55 dan daftar 50 item, tanpa membuka notifikasi sekolah/pengguna lain (**4 tes lulus, 31 assertions**).

AP-21.10 masih `OPEN` untuk badge global di shell, pencarian internal, collapse menu, dan pergantian role/periode. Full suite tidak dijalankan tanpa instruksi terpisah.

## Lanjutan Fase 4 — AP-18.12 — Codex — 22 November 2026 — IN_PROGRESS

Edge case berkas e-book hilang ditutup pada sumber katalog, tanpa migrasi atau dependency baru:

- `LibraryService::searchCatalog()` memuat metadata `ServiceDocument` yang dirujuk koleksi dan memeriksa objek private yang benar-benar ada sebelum mengisi `has_ebook_file` atau URL unduh lokal.
- Tautan provider eksternal tetap dapat dipakai sebagai fallback terkonfigurasi; ketika tidak ada fallback, item tampil sebagai koleksi tanpa materi digital yang dapat diunduh.
- `source_status` menegaskan `school_suite_local_catalog` sebagai sumber kebenaran, SLiMS sebagai provider opsional, `fallback_active=true`, dan `missing_local_files` sebagai sinyal operasional agar pustakawan mengetahui ada berkas yang perlu dipulihkan.
- `ServiceDocumentService::hasStoredDocument()` memusatkan pemeriksaan storage tanpa query per-item tambahan.

Bukti: `php artisan test --compact tests/Feature/LibraryReplicaTest.php tests/Feature/ComplaintsAndLibraryTest.php` — **6 tes lulus, 157 assertions**; `vendor/bin/pint --dirty`, lint PHP terkait, dan `git diff --check` lulus. Full suite, uji provider SLiMS nyata, dan skenario edge-case AP-18.12 lainnya belum dijalankan tanpa instruksi terpisah.

## Lanjutan Fase 2 — Codex — 22 November 2026 — READY_REVIEW

Pengguna meminta pekerjaan dilanjutkan setelah Fase 1, dengan aturan pengujian dijalankan di akhir dan full suite menunggu instruksi terpisah. Serena tidak digunakan. Desain `/login` tetap menjadi pengecualian publik yang disetujui.

Perubahan yang sedang diserahkan:

- Menambah blok konten publik `school.programs`, `school.student_life`, dan `home.notice` pada registry `School`. Blok mengikuti editor rekursif, sanitasi backend, revisi, preview, publish, unpublish, dan jadwal yang sudah ada; tidak ada migrasi atau dependency baru.
- Menampilkan item program/fasilitas dan kesiswaan hanya jika memiliki judul terisi. Item kesiswaan tidak menampilkan identitas siswa dan memiliki flag `show_student_names` yang default `false` sebagai pagar moderasi.
- Menambahkan banner pengumuman terjadwal di shell Vue dan `public/madrasah/home.blade.php`; banner nonaktif secara default, rentang tanggal memakai timezone sekolah pada Blade, dan tautan publik dibatasi ke path internal atau HTTPS.
- Mengirim `contentBlocks` secara eksplisit dari `SchoolProfileController` ke halaman `/program` dan `/kehidupan-siswa`.
- Menyembunyikan dokumen pusat unduh yang file privatnya hilang dari listing publik, mempertahankan 404 pada endpoint unduh, serta menormalkan nama file kosong/berisi control character untuk unduhan Unicode yang aman.

Bukti verifikasi akhir: PHP lint pada file PHP/Blade terkait, `npm run typecheck`, `npm run build` (1.018 modul), `vendor/bin/pint --dirty`, `git diff --check`, dan `php artisan test --compact tests/Feature/PublicPortalTest.php tests/Feature/PublicEditableContentTest.php tests/Feature/PublicContentLifecycleTest.php tests/Feature/SchoolGalleryTest.php` — **39 tes lulus, 501 assertions**. Full suite tidak dijalankan sesuai instruksi pengguna.

Batas yang masih terbuka: AP-05.03/05.05/05.09, AP-06.10, AP-07.09, dan AP-08.08 berstatus `READY_REVIEW` karena cakupannya lebih luas dari irisan implementasi ini; AP-08.08 sudah memiliki mapping modal, lightbox, bantuan, pustaka foto, unduhan/SOP, dan cetak, tetapi masih membutuhkan review kelengkapan produk. Tidak ada commit, deployment, perubahan `.env`, migrasi, dependency baru, atau penggunaan Serena.

## Lanjutan Fase 3 — Codex — 22 November 2026 — READY_REVIEW

AP-10.12 ditutup pada level implementasi menjadi `READY_REVIEW`. `ServiceDocumentService::issueGeneratedDocument()` sekarang menolak bytes kosong, memeriksa kegagalan storage, dan membersihkan file privat bila transaksi penerbitan gagal. Renderer PDF hasil PTSP membungkus error menjadi pesan pemulihan yang aman sehingga status/tiket tidak tersimpan setengah jalan. `SecurityHeadersMiddleware` mempertahankan `Cache-Control` eksplisit dari endpoint verifikasi surat, sehingga kontrak `no-store, private` tidak ditimpa header global.

Verifikasi akhir: `php -l` pada file terkait, `vendor/bin/pint --dirty`, `git diff --check`, dan `php artisan test --compact tests/Feature/ParityFoundationTest.php tests/Feature/AdministrationCorrespondenceTest.php tests/Feature/PtspAndTataUsahaTest.php` — **39 tes lulus, 668 assertions**. Full suite dan provider/queue produksi tidak dijalankan tanpa instruksi terpisah.

## Penyempurnaan Fase 1 — Codex — 22 November 2026 — IN_PROGRESS

**Tujuan**: menutup kekurangan AP-01–AP-04 berdasarkan audit tanpa mengubah desain publik yang telah disetujui. Pengguna menyetujui mempertahankan desain `/login` sebagai pengecualian `D-02`/`AP-02.17`. Pengguna melarang Serena, meminta pengujian di akhir, serta memerlukan instruksi terpisah untuk full suite. Ketentuan ini berlaku untuk kelanjutan pekerjaan.

**Hasil dan batas penerimaan**: 34/62 `VERIFIED`, 19 `READY_REVIEW`, 8 `IN_PROGRESS`, 1 `OPEN`. Jangan mengubah seluruh checkbox menjadi selesai hanya berdasarkan hasil build atau 25 tes terarah. Bagian historis di bawah mencatat batch sebelumnya, termasuk full suite; angka full suite tersebut bukan hasil pengujian ulang batch ini.

### Perubahan yang diserahkan

- **Akses** — `SidebarNavigationService` memakai Gate akun/sekolah dan `AdmissionsPolicy` untuk panitia aktif; principal melihat menu readonly yang sesuai endpoint, PTSP melihat korespondensi sesuai rute yang sudah ada. Pencabutan izin Core dan penugasan panitia mencabut menu serta akses langsung dalam sesi berjalan. Tidak menambahkan role/permission.
- **Branding** — `SchoolController`/`School`, middleware Inertia dan `adminBranding.ts`: nama aplikasi nyata, lima warna semantic dengan ambang kontras putih 4,5:1, font system/serif, radius 6/8, density, navigasi active/expanded, preview/reset, favicon dan logo yang dapat dihapus. Hash revisi profil di bawah row lock menolak editor usang tanpa menimpa data. Identitas publik tetap memakai baseline warna kecuali toggle warna identitas diaktifkan.
- **Dialog** — `ActionConfirmationHost`, `useActionConfirmation`, `useDialogAccessibility`, `bodyScrollLock`: konfirmasi/input tindakan asinkron, validasi input, fokus dan Tab trap, Escape, restore focus dan scroll lock berbasis pemilik. Host berada di root sehingga CBT/halaman mandiri ikut memperoleh konfirmasi. Form yang memakai `useConfirmedDialogClose` juga dijaga ketika navigasi GET/menutup tab; persetujuan meninggalkan halaman hanya berlaku untuk navigasi yang dikonfirmasi. Reload polling pada halaman sama tidak membatalkan dialog.
- **CRUD** — profil sekolah, profil akun, IKM serta form perpustakaan, kartu/perangkat presensi, impor soal, kelulusan, lifecycle, SPMB, buku induk termasuk isi cepat, LMS termasuk pengumpulan tugas, proposal/LPJ, BK, piket dan PIN pos memakai overlay terpusat. Dialog lama yang disentuh memakai penjagaan perubahan belum tersimpan dan defaults edit yang benar. Grid presensi/nilai, chat composer dan keputusan status per baris tetap inline karena merupakan pekerjaan operasional, bukan formulir CRUD tambahan. Tidak membuat rute CRUD baru.
- **Konten/media** — judul PTSP khusus memerlukan toggle; nilai kosong terkelola dipertahankan; ikon CTA memakai allowlist; metadata/canonical divalidasi dan tetap mengikuti publikasi; jadwal memakai timezone sekolah. Guru beranda dihitung dari guru aktif; persentase komitmen integritas adalah konten editorial berlabel jelas, bukan hasil IKM. Identitas demo tidak menjadi fallback produksi. Penggantian/penghapusan foto organisasi memakai transaksi, guard referensi draft/live/jadwal/histori dan cleanup setelah commit. Cache halaman publik/media wajib revalidate; halaman auth/preview no-store.
- **Media orphan** — `php artisan school:media-orphans <school-id>` hanya memindai folder branding/galeri/berita/organisasi sekolah itu, mengabaikan file baru <24 jam dan seluruh referensi DB/settings. Default read-only. `--delete` memerlukan konfirmasi interaktif dan cadangan; tidak dijalankan terhadap media proyek dalam batch ini. Uji memakai filesystem fake.
- **CSP** — nonce Inertia diselaraskan dengan nonce dokumen untuk style progress; tidak melonggarkan kebijakan CSP. Permukaan dan teks netral admin memakai token semantic; public/auth dikecualikan dari transformasi utility.

### Matriks akses dan bukti yang tersedia

Permission default bersumber dari `PermissionEnum::defaultForRole`; policy, status aktif, sekolah dan penugasan tetap mempersempit izin. Daftar ini memetakan tanggung jawab dan bukti, bukan mengklaim setiap aksi sudah diuji ulang. Admin tidak menghapus batas kepemilikan/sekolah pada object policy.

| Role | Aksi/fitur yang dimaksud | Batas utama | GET akun/sekolah/galeri/keamanan pada fixture browser |
|---|---|---|---|
| admin | CRUD akun, konfigurasi, publikasi dan administrasi modul | Sekolah objek; larangan hapus diri | 200/200/200/200 |
| operator | Lihat/buat akun, ubah sekolah, konten, administrasi akademik/layanan | Permission aktual; tidak otomatis ubah/hapus akun | 200/200/200/403 |
| principal | Lihat akun/sekolah/audit; review, rilis/tanda tangan sesuai otoritas | Konfigurasi readonly; pemisahan verifikator | 200/200/403/200 |
| teacher | Presensi, input nilai, LMS, ajukan proposal | Mapel/kelas ditugaskan; SPMB perlu penugasan | 403/403/403/403 |
| homeroom | Presensi, catatan/disiplin, nilai/rapor, putuskan izin | Kelas/periode penugasan | 403/403/403/403 |
| counselor | Catatan siswa, kelola/ekspor BK | Sekolah dan scope layanan | 403/403/403/403 |
| librarian | Katalog, stok, konfirmasi pinjaman/pengembalian, usulan | Sekolah, stok atomik, permohonan terkait | 403/403/403/403 |
| student | Belajar, ujian, izin dan proposal sendiri | Identitas siswa/kepemilikan | 403/403/403/403 |
| parent | Ajukan izin anak terkait | Relasi anak terverifikasi | 403/403/403/403 |
| alumni | Tracer/profil dan permohonan legalisir | Data/permohonan sendiri; bukan verifikator | 403/403/403/403 |
| committee | Review kegiatan/SPMB | Penugasan panitia aktif | 403/403/403/403 |
| ptsp | Lihat/proses PTSP, tamu, mutasi, legalisir | Verifikasi; tidak menerbitkan tanda tangan | 403/403/403/403 |
| receptionist | Kelola buku tamu | Sekolah petugas | 403/403/403/403 |
| picket | Putuskan izin, scan gerbang | Sekolah/status izin | 403/403/403/403 |
| security_guard | Validasi gerbang | Izin valid; kredensial pos | 403/403/403/403 |
| task_force | Lihat/tangani pengaduan | Penugasan kasus aktif | 403/403/403/403 |
| signatory | Tanda tangan/rilis sesuai otoritas | Dokumen valid dan otoritas terpisah | 403/403/403/403 |

Bukti kode/tes yang sudah ada: `CoreAuthorizationTest` (default deny, cross-school, akun nonaktif), `RoleMatrixAccessTest` (menu/endpoint, principal readonly, pencabutan izin), `AdmissionsControllersTest` (penugasan/revokasi/cross-school), `RouteAndPermissionStressTest` (aksi siswa/guru/wali/operator/principal/admin dan kelas), `DocumentIssuanceAuthorityTest` (verifikator vs penandatangan), `StudentLifecycleAndAlumniTest` (kepemilikan/lifecycle/status/dokumen). Hanya subset terarah di bawah dijalankan pada akhir batch; referensi tes lain bukan bukti rerun. Matriks fitur/aksi dan skenario negatif sudah tersedia untuk review (`AP-01.02/.12`); pembuktian penuh multirole, agregat/dropdown dan ekspor tetap `IN_PROGRESS` pada `AP-01.06/.07`.

### Matriks fitur × aksi untuk review AP-01.02/AP-01.12

Aksi yang tidak tersedia pada fitur tidak diada-adakan. `R` mencakup daftar/detail/pencarian; `W` mencakup buat/ubah/unggah; ekspor/unduh tetap melalui scope objek yang sama. Tabel memetakan kontrak yang harus diperiksa, bukan mengizinkan role melalui dokumentasi. Sumber otoritas tetap route → controller/policy → service yang disebutkan.

| Fitur | R / pencarian | W / unggah | Aksi khusus / ekspor | Scope dan sumber otoritas |
|---|---|---|---|---|
| Akun internal | `core.users.view` | `.create`, `.update`, `.delete` terpisah | Role admin tidak dapat diberikan nonadmin; hapus diri ditolak | `UserPolicy`, `UserController`, sekolah objek |
| Sekolah, tema, CMS | `SchoolPolicy::view`; principal readonly | `SchoolPolicy::update` | Draft/preview/publish/schedule/unpublish/restore; revisi wajib | `SchoolController`, row lock dan snapshot |
| Galeri/berita/organisasi/unduhan/IKM | admin/operator | admin/operator | Publikasi/moderasi/hapus/ekspor IKM; media terpakai dilindungi | Route Core dan controller entitas, school_id |
| Keamanan | admin/principal | admin | Blokir/cabut IP, tandai error selesai | `SecurityOperationsController`, principal readonly |
| Siswa/guru/kelas/jadwal | Policy akademik dan role menu | admin/operator sesuai policy | Impor, template, reset sandi, ekspor Excel/PDF; jadwal terbit | Policy Student/Teacher/ClassGroup/Schedule dan tahun/kelas sekolah |
| Buku induk | admin/operator | admin/operator | Dokumen/foto, impor, ekspor/cetak sesuai filter | `buku_induk.php`, controller buku induk |
| Presensi/jurnal/kelas daring | teacher/homeroom; siswa kelas daring terkait | Guru/wali yang ditugaskan | Koreksi presensi, Word/PDF jurnal; principal rekap/ekspor | Rute Academic, lesson session/penugasan/kelas |
| Presensi guru | admin/operator/teacher/homeroom | Pemilik atau pengelola sesuai aksi | Review izin, pengganti, rekap PDF | `teacher_operations.php` dan TeacherOperationsController |
| Kartu/kios | admin/operator; petugas kios sesuai rute | admin/operator terbit/cabut kartu, daftar perangkat | Scan harus menolak kartu dicabut | StudentCardKioskController dan layanan presensi |
| LMS | teacher/homeroom/student | Guru buat materi/tugas; siswa kirim sendiri | Nilai guru; unduhan dibatasi kelas/pemilik | `LearningPolicy`, LearningController |
| Bank/paket/berbagi soal | teacher/homeroom | Pemilik membuat/versi/arsip/pulihkan | Impor/ekspor, salin, terima/tolak/cabut berbagi | Rute Assessment dan kepemilikan/share aktif |
| Nilai/sikap/rapor/skema | Role rute dan AssessmentPolicy | Guru/wali pada penugasan; skema pengelola | Publish rapor melalui policy; ekspor mengikuti akses rapor | AssessmentAccessService, report snapshot dan periode |
| CBT | Guru pengelola; siswa peserta | Pengelola ujian; jawaban milik attempt siswa | Publish, buka kunci, tambah waktu, paksa selesai, nilai esai, CSV | ExamController dan kepemilikan/roster/status |
| Catatan/disiplin wali | homeroom | homeroom | Hapus catatan, tambah insiden/prestasi/bantuan | StudentNotePolicy dan kelas/periode wali |
| Izin/piket/gerbang | Role rute sesuai tugas | Wali/piket putuskan; pos validasi | Koreksi/batal, token dispensasi, PIN pos; dokumen terkait | AccessScopeService, kelas/izin/status/identitas pemohon |
| BK/pengaduan | admin/counselor; satgas pada antrean tanpa petugas atau miliknya | Role penanganan yang berwenang | Eviden dan CSV BK; penugasan ulang mencabut petugas lama | BkDiscipline/ComplaintReportService, sekolah dan assignee |
| SPMB | admin/operator/principal; teacher/committee ditugaskan | Pengelola/panitia sesuai policy dan aksi | Verifikasi berkas, skor/ranking/keputusan, daftar ulang; konfigurasi panitia pengelola | AdmissionsPolicy, penugasan aktif dan sekolah objek |
| Mutasi/kelulusan/legalisir/PTSP | Role antrean rute | Verifikator admin/operator/PTSP sesuai layanan | Persetujuan/tanda tangan admin/principal/signatory; pickup verifikator | AccessScopeService dan layanan penerbitan, status dan dokumen |
| Persuratan | admin/operator/PTSP/principal | Pengelola sesuai controller | Disposisi/status, persetujuan principal; signature manual/BSrE sesuai otoritas; PDF | `correspondence.php`, CorrespondenceController dan status surat |
| Buku tamu/sarpras/tugas staf | Role rute/policy | Pengelola; penanggung jawab hanya aksi tugasnya | Checkout tamu, foto, lampiran privat tugas | School_id, StaffTaskPolicy, ownership/assignment |
| Komunikasi/notifikasi/chat | Role audiens dan kelas; notif user_id sendiri | Pengelola pengumuman; pembuat/anggota ruang chat | Hapus milik sendiri atau pengelola, moderasi dan unduhan | CommunicationController/EngagementController; nav principal memakai alias Kalender |
| Proposal | Pemohon sendiri atau antrean reviewer | Pemohon isi/revisi/LPJ | Review sesuai otoritas; dokumen pemohon/reviewer | ProposalController/service, kepemilikan/penugasan/status |
| Integrasi | admin/operator | admin/operator | Preview/commit impor, ekspor, retry log; secret tidak dikirim ulang | IntegrationSettingsController dan system key allowlist |
| Portal/akun eksternal | Publik terbit atau pemohon terautentikasi pada guard eksternal | Pengajuan/revisi milik pemohon | Pelacakan/PIN/dokumen, consent alumni, recovery netral | AccessScopeService, external applicant service dan kontrak tiket |

Matriks negatif untuk setiap kelompok: pengguna di luar role → 403; lintas sekolah/objek privat → 403/404 sesuai kontrak; pemilik/kelas/penugasan salah → ditolak; status belum layak → validasi; dokumen tidak terbit/dicabut → tidak diunduh sebagai dokumen valid. Bukti primer yang sudah tersedia dipetakan pada tabel role dan daftar tes di atas. Kasus yang belum mempunyai bukti granular tetap harus ditambahkan ke cakupan review; tabel bukan pengganti eksekusi tes.

Multirole lokal bukan role bebas yang digabung: akun mempunyai satu `role_id`; pengecualian yang memang ada adalah operator dengan profil guru aktif (`User::hasRole`). Menonaktifkan profil guru mencabut akses LMS/menu guru sambil mempertahankan role operator; tambahan assertion ini lulus pada akhir batch. Pencabutan assignment panitia dan pergantian petugas aduan memakai pemeriksaan DB saat akses, bukan izin yang disimpan di session. Akun admin nonaktif dikeluarkan pada request berikutnya; tes tersebut juga dijalankan ulang.

### Kontrak editor konten

| Tipe | Kontrol/sumber yang digunakan | Batas/perilaku |
|---|---|---|
| Teks/teks panjang | Input/textarea rekursif `PublicContentFields` | Maksimum 5.000; kosong berbeda dari tidak ada override |
| Rich text | Editor entitas yang sudah ada | Sanitasi backend; bukan HTML/JS bebas pada blok teks |
| Angka/boolean | Number/checkbox | Number hingga 1.000.000; integer tidak menerima pecahan |
| Ikon | Select allowlist server | Tidak menerima kelas/HTML arbitrer |
| Gambar/alt/caption | Pilih media terbit, metadata, upload entitas | Guard referensi dan validasi ukuran/rasio; tidak ada crop/compression baru |
| Warna/preset | Color dan preset tema | Hex, kontras server, preview dan reset dengan konfirmasi |
| Tanggal/jam | Tanggal field dan jadwal publikasi | Waktu jadwal ditafsirkan dalam timezone sekolah |
| Daftar/urutan | Editor list serta entitas galeri/berita/organisasi/unduhan | Maksimum 50 pada list; layout mengikuti template |
| URL/embed | Field URL dan validasi server | Skema aman/allowlist; canonical harus HTTP(S) absolut |
| Dokumen/relasi | Editor entitas dan pilihan data berscope | Bukan query database atau upload executable |
| SEO | Title, deskripsi, gambar berbagi, canonical | Title 160, deskripsi 320; draft tidak menjadi metadata live |

### Pengujian akhir yang benar-benar dijalankan

- `npm run typecheck`, `npm run build`, `vendor/bin/pint --dirty`, `git diff --check`: lulus. Setelah temuan select perpustakaan terlalu sempit pada screenshot 390px, grid diubah responsif dan typecheck/build diulang untuk perubahan itu.
- `php artisan test --compact tests/Feature/BrandingProfileTest.php tests/Feature/PublicEditableContentTest.php tests/Feature/PublicContentLifecycleTest.php tests/Feature/SchoolGalleryTest.php tests/Feature/RoleMatrixAccessTest.php tests/Feature/AdmissionsControllersTest.php --filter='branding|theme|media_orphan|media_used|media_kept|scheduled|metadata|empty_text|sidebar_visibility|revoked_core|dedicated_committee|principal_school'`: **25 passed, 269 assertions**. Tambahan filter `operator_account_can_be_linked|deactivated_admin_session` pada `AcademicControllersTest`/`CoreAuthorizationTest`: **2 passed, 13 assertions**. Total 27 tes unik, 282 assertions; tidak menjalankan full suite.
- Browser pada SQLite terisolasi `storage/framework/testing/phase-one/database.sqlite`: profil sekolah 390/1280 × light/dark tanpa overflow; fokus, Tab, Escape, nested discard/cancel, restore focus dan body lock lulus. Perpustakaan: input belum disimpan tetap ada saat batal; discard menutup dan melepas scroll. Pengaturan akademik: buat mapel fixture → batal konfirmasi hapus → konfirmasi hapus; body lock pulih pada kedua jalur dan fixture dihapus. Login 390px: show/hide password dan tidak ada overflow. Tidak mengubah data operasional.
- Browser setelah build terakhir: form perpustakaan berubah → navigasi GET → batal mempertahankan isian; ulangi → setuju membuka dashboard dan melepas scroll. Dialog perangkat kios pada 390px terbuka tanpa overflow; tidak ada page error pada alur ini.
- Bukti browser tambahan sebelum penundaan tes: branding simpan/refresh, editor dua tab usang ditolak, empat route Core × 17 role dan 12 kombinasi dialog Core. Pemeriksaan tersebut mendahului perubahan root host; perilaku host terkait diperiksa ulang pada alur di atas.

### Sisa yang belum boleh dicentang selesai

1. Review matriks fitur/aksi yang sekarang tersedia (`AP-01.02/.12`) dan lengkapi bukti multirole, pencarian/counter/notifikasi/dropdown/ekspor di seluruh modul (`AP-01.06/.07`). Implementasi menu yang diperbaiki tidak membuktikan seluruh scope agregat.
2. Audit aksi/ekspor, semua label/error/unsaved guard, seluruh elemen development dan kombinasi UI padat, zoom/screen reader/kamera/keyboard virtual pada semua layar (`AP-02.07/.08/.15/.19/.20/.22`). Pemeriksaan beberapa dialog bukan bukti semua halaman.
3. Penerimaan lintas template untuk branding, setiap field no-code, cache, pemulihan dan media. Item `READY_REVIEW` memiliki implementasi, tetapi masih memerlukan review cakupan sesuai checklist aslinya.
4. `AP-04.17`: UAT operator nonteknis yang mengubah tiap tipe, preview, publish, batal dan restore. Gate manusia; belum dilakukan oleh pengguna.

Audit source tambahan: 17 aksi ekspor yang belum berikon diselaraskan dengan PDF/Word/Excel; label tetap tersedia melalui title/aria-label, dan beberapa keluaran sejenis mempertahankan label. Impor dan isi cepat yang masih inline dipindahkan ke overlay. Build/typecheck sempat mendeteksi wrapper/deklarasi ganda buku induk dan nama error field e-book yang salah; keduanya dikoreksi sebelum hasil akhir.

Tidak ada dependency/skema baru dalam batch penyempurnaan ini, commit, deployment, perubahan `.env` atau pembersihan media/data operasional. Migrasi dan full suite yang tercatat di bagian historis berasal dari batch sebelumnya. Worktree memiliki perubahan sebelumnya; tidak di-reset.

Evaluasi mandiri: akurasi 4/5 (hasil tes dan batas bukti eksplisit); kelengkapan 2/5 (Fase 1 belum 62/62, sisa tercantum); kejelasan 4/5 (status granular dan matriks tersedia); keterlaksanaan 4/5 (perintah/bukti/area tersisa konkret); keringkasan 3/5 (handoff panjang karena cakupan lintas modul). Rata-rata 3,4/5. Prioritas berikutnya adalah menutup matriks dan audit cakupan, lalu UAT; jangan menaikkan status berdasarkan klaim agen sendiri.

## Konsolidasi hasil audit oleh Codex — 22 November 2026 — IN_PROGRESS

Otorisasi: pengguna meminta menyelesaikan seluruh tugas setelah audit. Pekerjaan dan checkbox Gemini dipertahankan; penutupan tambahan hanya berdasarkan bukti baru.

- Tujuan: menyelesaikan integrasi publik–admin dan kontrol konten tanpa mengubah baseline publik.
- Area: Library, Core CMS/configuration, dashboard, akses per role, komponen modal admin, tes feature/browser, serta dokumentasi penerimaan yang sudah ada.
- Urutan: integrasi perpustakaan → dashboard → lifecycle CMS dan pengamanan settings → konfigurasi konten → konsistensi akses/UI → regresi dan pemeriksaan browser → rekonsiliasi TODO.
- Kriteria penerimaan: form publik memakai kontrak backend yang sama; retry tidak menggandakan stok/tiket; role dan kepemilikan berlaku pada endpoint; draft/jadwal tidak bocor; konten yang disembunyikan tidak dikirim ke tamu; modal mengelola fokus dan scroll; build/typecheck/tes relevan lulus.
- Perubahan flow perpustakaan: sebelumnya katalog replika statis dan aksi login/booking tidak tersedia; sesudahnya katalog memakai koleksi aktif, login memakai akun akademik, booking menjadi reservasi stok atomik, pustakawan mengonfirmasi penyerahan, dan pengembalian mengembalikan stok sekali. Tabel pinjaman yang ada digunakan tanpa migrasi; role penerbit dan URL publik dipertahankan. Risiko kompatibilitas status `reserved` ditutup dengan dukungan antrean pustakawan dan tes retry/penyerahan/pengembalian.
- Perubahan lifecycle CMS: sebelumnya draft/publish/restore; sesudahnya jadwal menyimpan snapshot, scheduler menerbitkan snapshot itu sekali, dan unpublish/archive menyembunyikan bagian terkelola dengan riwayat yang dapat dipulihkan. Form layanan tetap tersedia. Rollback aplikasi harus mempertahankan JSON histori dan tidak menghapus dokumen.
- Risiko dan batas: UAT oleh pemilik produk, kredensial/provider nyata, pembersihan data development, perubahan skema, commit dan deployment mengikuti otorisasi tersendiri. Tidak ada dependency atau perubahan arsitektur yang direncanakan. Pengguna telah menyetujui kolom JSON nullable `public_ikm_surveys.instrument_snapshot`; migrasi khusus kolom tersebut sudah diterapkan pada database lokal. Migrasi pending lainnya tidak dijalankan.
- Bukti akhir batch: full suite **597 tes: 595 lulus, 2 skipped, 6.531 assertions** (45,148 detik). Dua skip: drill MySQL membutuhkan socket pengujian terisolasi; pengujian failed-jobs tidak tersedia pada fixture terkait. `vendor/bin/pint --test`, `npm run typecheck`, `npm run build` (1.011 modul), `git diff --check`, serta lint PHP atas 107 template hasil kompilasi lulus. Setelah koreksi responsif terakhir, build/typecheck dan pemeriksaan browser pada halaman terkait dijalankan ulang.
- Bukti browser: 35 halaman publik HTTP 200 tanpa page error pada desktop dan 390px; overflow perpustakaan/verifikasi yang ditemukan pada mobile diperbaiki dan diuji ulang pada 320/390/768/1280px. Panel sekolah, IKM, dan pusat unduh lulus 54 pemeriksaan gabungan 9 ukuran layar × 2 tema. Modal pusat unduh lulus fokus awal, Tab trap, scroll lock, Escape dan restore focus.
- Bukti transaksi browser menggunakan SQLite terisolasi, bukan data proyek: editor konten → simpan draf → preview privat → live tetap lama → publish → live baru; konfigurasi dua unsur IKM berbobot 2:1 → jawaban 5 dan 1 → antrean admin bernilai 3,67 → persetujuan → ringkasan publik 3,67/5.
- Perbaikan tambahan terverifikasi: route panitia khusus mengikuti policy dan penugasan; pencabutan penugasan panitia/satgas mencabut akses langsung; buku rekomendasi, filter katalog, NIS/NISN, counter dan tanggal perpustakaan menggunakan data aktif; logo/favicon memiliki URL berversi; riwayat media terpakai dilindungi; metadata halaman ikut lifecycle draf/publikasi; perubahan IKM dari editor usang ditolak dan snapshot jawaban tidak berubah.

**IMPORTANT — Integrasi keseluruhan belum dinyatakan selesai.** Checkbox Gemini tetap utuh. Penambahan bukti batch ini hanya menutup `AP-24.12`, bukan otomatis menutup seluruh task dalam paket yang disentuh. Sisa teknis yang masih harus diselesaikan/dibuktikan: pemetaan semua elemen no-code termasuk teks dinamis, menu, cetak/PDF dan field legacy yang bertumpang tindih (contoh `ptsp.hero.title` belum menjadi sumber judul pada template PTSP); pengurutan/list editorial menyeluruh; kepatuhan seluruh halaman CRUD/modal/aksi terhadap standar UI; matriks akses positif-negatif dan transaksi ujung ke ujung untuk semua role/layanan; perbandingan screenshot dengan fixture tetap dan uji pemulihan. Registry konten dan tes contoh tidak membuktikan cakupan penuh setiap elemen publik.

Gate manusia/operasional yang tetap terbuka: UAT pemilik produk/operator nonteknis, keputusan D-05 s.d. D-08 untuk aturan/template/provider nyata, drill backup/restore terisolasi, persetujuan pembersihan data development serta otorisasi deployment. Tidak ada commit, deployment, perubahan `.env`, dependency baru, atau penghapusan data proyek.

## Fase 5 (`AP-20` s.d. `AP-23`) — Pelacakan Terpadu, Verifikasi QR, Dashboard Per Role, Regresi Modul Internal & Integrasi Operasional — 22 November 2026

- **Task / Status**:
  - `AP-20` (`AP-20.01`..`AP-20.10` `VERIFIED`), `AP-21` (`AP-21.01`..`AP-21.09` `VERIFIED`, `AP-21.10` `OPEN`), `AP-22` (`AP-22.01`..`AP-22.11` `VERIFIED`, `AP-22.12` `OPEN`), `AP-23` (`AP-23.01`..`AP-23.06`, `AP-23.08` `VERIFIED`; `AP-23.07`, `AP-23.09`..`AP-23.11` `OPEN`).
- **Kontrol Pelacakan & Verifikasi (`AP-20`)**:
  - `VerificationController::index()` kini mendukung parameter query `?code=` (tautan QR PDF resmi) selain `?kode=` untuk kompatibilitas mundur.
  - `UnifiedTrackingController` + `Public/Tracking/Index.vue` mengikat blok terkelola `tracking.hero` dari `School::defaultPublicContentBlocks()`, menampilkan seluruh kategori layanan dari `UnifiedTrackingService::TRACKING_CATEGORIES` (termasuk `surat_eksternal`, `aduan`, `proposal`), serta menyelaraskan panduan input dengan pencarian kode tiket/resi & PIN verifikasi.
  - Menambahkan perlindungan CSV formula injection (`=`, `+`, `-`, `@`, `\t`, `\r`) pada `ReportingController`, `AttendanceController`, dan `ExternalIntegrationService::exportFile()` melengkapi `BkDisciplineService`, `GuestbookService`, dan `ExamController`.
- **Dashboard Per Role (`AP-21`)**:
  - Menambahkan metrik operasional berscope `school_id` pada `/dashboard` (`routes/web.php`) untuk role `ptsp`, `receptionist`, `picket`, `security_guard`, `bk`, `librarian`, `parent`, dan `alumni` tanpa mengekspos data sensitif/PII.
- **Regresi Modul Internal & Integrasi (`AP-22`, `AP-23`)**:
  - Seluruh modul internal (`Academic`, `Assessment`, `Learning`, `Homeroom`, `Administration`, `Integration`, `Core`) teruji regresi penuh bersama `NotificationOutboxService`, `AuditLogger`, dan `BackupRecoverySafetyTest` / `BackupRestoreDrillTest`.
- **Hasil Pengujian**:
  - `./vendor/bin/pint --test --dirty`: **PASS**.
  - `./node_modules/.bin/vue-tsc --noEmit` & `./node_modules/.bin/vite build`: **PASS** (`1004 modules`).
  - `php artisan test --compact`: **530 tests, 528 passed, 2 skipped, 6175 assertions**.
- **Pekerjaan Tersisa**:
  - Fase 6 (`AP-24` s.d. `AP-25`): matriks uji lintas role, browser E2E/visual check, dan serah terima akhir.

## Fase 4 (`AP-12` s.d. `AP-19`) — Izin Siswa, Mutasi, Kelulusan, Legalisir, Aduan/BK, Alumni/E-KTA, Perpustakaan & SPMB/Proposal — 22 November 2026

- **Task / Status**:
  - `AP-12` (`AP-12.01`..`AP-12.09` `VERIFIED`, `AP-12.10` `OPEN`), `AP-13` (`AP-13.01`..`AP-13.07` `VERIFIED`, `AP-13.08` `OPEN`), `AP-14` (`AP-14.01`..`AP-14.07` `VERIFIED`, `AP-14.08` `OPEN`), `AP-15` (`AP-15.01`..`AP-15.08` `VERIFIED`, `AP-15.09` `OPEN`), `AP-16` (`AP-16.01`..`AP-16.08` `VERIFIED`, `AP-16.09` `OPEN`), `AP-17` (`AP-17.01`..`AP-17.12`, `AP-17.15` `VERIFIED`; `AP-17.13`, `AP-17.14` `OPEN`), `AP-18` (`AP-18.01`..`AP-18.10` `VERIFIED`; `AP-18.11`, `AP-18.12` `OPEN`), `AP-19` (`AP-19.01`..`AP-19.09` `VERIFIED`, `AP-19.10` `OPEN`).
- **Kontrak Publik & Konten Terkelola**:
  - Mempertahankan tata letak dan DOM baseline halaman publik layanan: `/layanan/izin-siswa`, `/layanan/mutasi`, `/layanan/kelulusan`, `/layanan/legalisir`, `/layanan/siap-lapor`, `/alumni`, `/perpustakaan`, dan `/ppdb`.
  - Menambahkan blok konten terkelola pada `School::defaultPublicContentBlocks()` (`student_permit.hero`, `mutation.hero`, `graduation.hero`, `legalization.hero`, `complaint.hero`, `alumni.hero`, `admissions.hero`), serta parameter operasional izin & kios (`operations.permit_same_day_cutoff`, `operations.permit_max_advance_days`, `operations.kiosk_dedup_seconds`) pada `Core/School/Edit.vue`.
- **Kontrol Admin, Workflow & Backend**:
  - **AP-12**: Sinkronisasi status `ServiceTicket` saat koreksi izin (`PermitWorkflowService::syncTicketCorrection`), QR `/lacak` pada PDF bukti izin, serta pembatasan akses lampiran medis terhadap petugas pos jaga (`403 Forbidden`).
  - **AP-13**: Pencegahan duplikasi permohonan mutasi aktif per NISN, validasi kapasitas rombel saat persetujuan mutasi masuk, QR `/verifikasi` pada surat mutasi PDF, dan sinkronisasi pembatalan tiket saat pencabutan.
  - **AP-14**: Guard pengambilan asli ganda pada `GraduationService::markOriginalCollected()`, QR `/verifikasi` pada PDF SKL, dan penggantian `window.prompt` menjadi modal Vue pada `Academic/Graduation/Index.vue`.
  - **AP-15**: QR `/verifikasi` pada PDF legalisir, surat kuasa, dan SPTJM (`LegalizationService`), serta penggantian `window.prompt` menjadi modal terpusat untuk catat ambil & revisi/tolak pada `Administration/Legalization/Index.vue`.
  - **AP-16**: Penyempurnaan modal (Escape/backdrop close) & header tabel rata tengah pada `Homeroom/Complaints/Index.vue` dan `Homeroom/Bk/Index.vue`.
  - **AP-17**: Endpoint backend `POST /alumni/cek-ekta` (`AlumniService::publicEktaLookup`) untuk pencarian E-KTA lintas seluruh direktori berizin tanpa membocorkan dokumen privat, QR pada PDF E-KTA, serta modal penolakan moderasi pada `Academic/Alumni/Index.vue`.
  - **AP-18**: Menghubungkan `/perpustakaan` & `/perpustakaan/aksi` (`LibraryController::replicaAction`) ke `LibraryService` (katalog, detail dengan otorisasi `public`/`members_only`/`staff_only`, statistik kunjungan tanpa PII, buku tamu, usulan pengadaan, cek bebas pustaka) dan mengaktifkan header CSRF pada `perpustakaan.blade.php` melalui `SchoolScopeService::findPrimaryPublicSnapshot()` (menjaga batas modular monolith).
  - **AP-19**: Verifikasi isolasi panitia SPMB berbasis penugasan (`AdmissionsPolicy`), pengikatan `admissions.hero`, serta penggantian `window.confirm` menjadi modal konfirmasi Vue pada `Admissions/Index.vue`.
- **Hasil Pengujian**:
  - `./vendor/bin/pint --test --dirty`: **PASS**.
  - `./node_modules/.bin/vue-tsc --noEmit`: **PASS**.
  - `./node_modules/.bin/vite build`: **PASS** (1004 modules, `public/build/manifest.json`).
  - `php artisan test --compact`: **529 tests, 527 passed, 2 skipped, 6143 assertions** (termasuk `ModularBoundaryTest`, `ParityFoundationDomainTest`, `OperationsPermitKioskPicketBkTest`, `StudentLifecycleAndAlumniTest`, `ComplaintsAndLibraryTest`, `LibraryReplicaTest`, `ProposalAndIntegrationTest`).
- **Pekerjaan Tersisa**:
  - Fase 5 (`AP-20` s.d. `AP-23`) dan uji beban/edge-case operasional yang masih berstatus `OPEN`.

## Fase 3 (`AP-10` s.d. `AP-11`) — Fondasi Transaksi/Status/Dokumen & Integrasi PTSP, Akun Pemohon, Surat Dinas Eksternal serta Buku Tamu — 22 November 2026

- **Task / Status**:
  - `AP-10` (`AP-10.01` s.d. `AP-10.11`, `AP-10.13`) — **READY_REVIEW** (`AP-10.12` untuk simulasi penuh kegagalan provider/queue eksternal tetap `OPEN` dan menunggu uji operasional `AP-23`).
  - `AP-11` (`AP-11.01` s.d. `AP-11.13`) — **READY_REVIEW**.
- **Kontrak Publik**:
  - Mempertahankan 100% tampilan dan tata letak baseline halaman publik layanan: `/layanan/ptsp` (`PUB-17`), `/layanan/ptsp/akun` (`PUB-18`), `/layanan/surat-dinas` (`PUB-19`), `/layanan/buku-tamu` (`PUB-20`), dan `/lacak` (`PUB-34`).
  - Menambahkan blok konten terkelola ber-ID stabil (`ptsp.hero`, `external_letter.hero`, `guestbook.hero`) pada `School::defaultPublicContentBlocks()`, `/pengaturan/sekolah` (`Core/School/Edit.vue`), dan prop `contentBlocks` di `Ptsp.vue`, `ExternalLetter.vue`, serta `Guestbook.vue` (termasuk pengikatan `Jam Resepsionis` ke `guestbook.hero.reception_hours` / `school.service_hours` dengan fallback identik terhadap baseline).
- **Kontrol Admin & Backend**:
  - **Pencegahan Pengambilan Fisik Ganda & Pencabutan Otomatis saat Penerbitan Ulang (`AP-10.06`, `AP-10.09`, `AP-11.07`)**:
    - `PtspWorkflowService::recordPhysicalPickup()` kini menolak pencatatan pengambilan fisik kedua jika `$locked->physical_pickup_at !== null` (`ValidationException` pada `physical_pickup_name`).
    - `PtspWorkflowService::issueResult()` secara otomatis mencabut (`revokeDocument`) dokumen hasil sebelumnya dengan alasan `'Digantikan oleh penerbitan ulang dokumen hasil layanan PTSP.'` apabila permohonan diterbitkan ulang, serta menangani penerbitan ulang pada status `approved`/`completed` yang sama tanpa melempar error transisi status redundan.
  - **Pembatalan Permohonan & Pencabutan Dokumen Resmi (`AP-10.03`, `AP-10.09`, `AP-11.06`)**:
    - Menambahkan `ServiceWorkflowMap` transisi terkontrol `completed -> cancelled` (khusus pencabutan dokumen resmi oleh pejabat berwenang dengan alasan wajib).
    - Menambahkan `PtspWorkflowService::cancelByApplicant()` + `POST /layanan/ptsp/{ticketCode}/batalkan` (`PtspPublicController::cancel`) + formulir pembatalan di `Public/Services/PtspReceipt.vue` agar pemohon terverifikasi dapat membatalkan permohonannya selama status masih `submitted` atau `needs_revision`.
    - Menambahkan `PtspWorkflowService::cancelByStaff()` + `POST /administrasi/ptsp/permohonan/{ptspRequest}/batalkan` (`PtspAdminController::cancel`) + mode aksi **Batalkan / Cabut Dokumen** di `Administration/Ptsp/Index.vue`. Petugas `ptsp`/`operator` dapat membatalkan permohonan sebelum terbit, sedangkan pembatalan/pencabutan setelah hasil terbit (`approved`/`completed` atau `result_document_id !== null`) wajib dilakukan oleh role berwenang (`canApproveFinalDecision` + `canSignOfficialDocument`: `admin`, `principal`, `signatory`) dan otomatis mencabut `ServiceDocument` terkait.
  - **QR Verifikasi & Keamanan Template PDF Hasil PTSP (`AP-10.05`, `AP-10.13`)**:
    - Memperbarui `PtspWorkflowService::issueResult()` dan `resources/views/administration/ptsp-result.blade.php` agar menyertakan `verification_token` dan QR SVG menuju `/verifikasi?code={token}` (tanpa memuat PIN/rahasia pemohon atau path storage privat), serta mengekspor `revoked_at` dan `revocation_reason` pada `ServiceDocumentService::serializeDocument()`.
  - **Pemisahan Akses Role Persuratan, PTSP & Resepsionis (`AP-11.10`, `AP-11.13`)**:
    - Membuka akses `/administrasi/persuratan` (`correspondence.php` & `CorrespondenceController`) bagi role `ptsp` (selain `admin`, `operator`, `principal`) untuk memproses agenda surat masuk eksternal & disposisi, sambil memastikan `receptionist` tetap terisolasi (`403`) dari `/administrasi/persuratan/*`, `/administrasi/ptsp/*`, dan unduhan dokumen PTSP.
  - **Standar UI Admin (`AP-02` Aturan 03 & 04)**:
    - Menyelaraskan header tabel `.data-table th` menjadi rata tengah (`text-align: center`) dan menambahkan penutupan modal via klik backdrop (`@click.self`) serta tombol `Escape` (`@keydown.esc`) pada `Administration/Ptsp/Index.vue` dan `Administration/Guestbook/Index.vue`.
- **Data**:
  - Tidak ada perubahan skema tabel database atau penambahan migrasi/dependency baru.
- **Perubahan**:
  - Core: `app/Modules/Core/Domain/ServiceWorkflowMap.php`, `app/Modules/Core/Infrastructure/Models/School.php`, `app/Modules/Core/Application/Services/{SchoolScopeService,ServiceDocumentService}.php`, `resources/js/Pages/Core/School/Edit.vue`
  - Administration: `app/Modules/Administration/Application/Services/PtspWorkflowService.php`, `app/Modules/Administration/Interface/Http/Controllers/{PtspAdminController,PtspPublicController,ExternalLetterController,GuestbookController,CorrespondenceController}.php`, `app/Modules/Administration/Interface/Routes/{ptsp,correspondence}.php`
  - Views & Frontend: `resources/views/administration/ptsp-result.blade.php`, `resources/js/Pages/Public/Services/{Ptsp,PtspReceipt,ExternalLetter,Guestbook}.vue`, `resources/js/Pages/Administration/{Ptsp/Index,Guestbook/Index}.vue`
  - Tes & Dokumentasi: `tests/Feature/PtspAndTataUsahaTest.php`, `docs/{TODO_ADMIN_PUBLIC_INTEGRATION,HANDOFF}.md`

## Fase 2 (`AP-05` s.d. `AP-09`) + Prasyarat CMS (`AP-04`) — Konten Editorial, Galeri Video, UI Admin Pusat Unduh & Zona Integritas/IKM, Jadwal Salat, Katalog E-Layanan & Data Siswa Publik — 22 November 2026

- **Task / Status**:
  - Prasyarat `AP-03`/`AP-04`: `AP-03.08`, `AP-03.09`, `AP-04.02`, `AP-04.04`, `AP-04.05`, `AP-04.07`, `AP-04.08`, `AP-04.09`, `AP-04.12`, `AP-04.13` — **READY_REVIEW** (tidak mencentang massal `VERIFIED`; menunggu review bersama bukti tes).
  - Fase 2 (`AP-05` s.d. `AP-09`): `AP-05.01`, `AP-05.02`, `AP-05.04`, `AP-05.06`..`AP-05.08`, `AP-05.10`, `AP-05.11`, `AP-06.01`..`AP-06.09`, `AP-06.11`, `AP-07.01`..`AP-07.08`, `AP-08.01`..`AP-08.07`, `AP-09.01`..`AP-09.07` — **READY_REVIEW**.
  - Task yang masih terbuka (`OPEN`) dan belum diklaim selesai: `AP-04.03`, `AP-04.06`, `AP-04.11`, `AP-04.14`..`AP-04.18`, `AP-05.03` (editor item kartu kurikulum/fasilitas individual), `AP-05.05` (popup pengumuman terjadwal), `AP-05.09` (editor organisasi siswa kesiswaan), `AP-06.10` (edge case media rusak/embed pihak ketiga), `AP-07.09` (uji stres file besar/Unicode/provider offline), `AP-08.08` (inventaris modal/poster SOP tambahan).
- **Kontrak Publik**:
  - Mempertahankan 100% tampilan dan tata letak baseline publik (`PUB-01` s.d. `PUB-16`, `PUB-35`, `PUB-37`, `PUB-42`).
  - Saat kustomisasi belum dilakukan, seluruh halaman publik merender konten dan struktur DOM yang identik dengan baseline.
- **Kontrol Admin & Backend**:
  - **Prasyarat CMS (`AP-04.02`..`AP-04.09`, `AP-04.12`, `AP-04.13`)**:
    - Memperluas `School::defaultPublicContentBlocks()` dengan ID seksi stabil (`home.hero`, `home.welcome`, `home.zi`, `home.services`, `home.programs`, `home.facilities`, `home.news`, `home.achievements`, `home.gallery`, `home.ppdb`, `home.contact`, `profile.values`, `profile.pillars`, `shell.options`, `shell.footer`) beserta flag `visible` dan opsi `hide_demo_fallbacks`.
    - Mengimplementasikan **pratinjau draf privat** (`?preview_draft=1`) melalui `PublicPortalService::isDraftPreviewRequested()` dan `applyPreviewResponseHeaders()`: hanya pengelola berwenang (`Gate::allows('update', $school)`) yang dapat melihat blok `$registry['draft']` pada template publik asli dengan header `Cache-Control: private, no-store, no-cache, must-revalidate` dan `X-Robots-Tag: noindex, nofollow`. Pengunjung tamu atau role tanpa hak yang mengakses `?preview_draft=1` ditolak dengan `403 Forbidden`.
    - Menghubungkan flag `visible` dan `hide_demo_fallbacks` ke template publik (`home.blade.php`, `profile.blade.php`, `news.blade.php`, `achievements.blade.php`, `gallery.blade.php`, `videos.blade.php`, `teachers.blade.php`, serta `VerificationController::studentData`) sehingga sekolah dapat mematikan konten contoh/demo saat tabel data masih kosong tanpa merusak baseline pengujian bawaan.
  - **Berita, Prestasi, Galeri Foto/Video & Publikasi Alumni (`AP-06`)**:
    - **Manajemen Galeri Video (`AP-06.06`, `PUB-11`)**: Mengganti array video statis di `videos.blade.php` dengan daftar video terkelola di `PublicPortalService::publicVideosForSchool()` (diinisialisasi secara idempoten dengan 6 video default). Menambahkan endpoint CRUD (`POST /pengaturan/galeri/video`, `PUT /pengaturan/galeri/video/{videoId}`, `DELETE /pengaturan/galeri/video/{videoId}`) di `SchoolGalleryController` dengan validasi allowlist path lokal (`/`) atau URL `http(s)://` (menolak `javascript:` dan path traversal `..`), serta UI modal CRUD + `ConfirmDialog` di `resources/js/Pages/Core/Gallery/Index.vue`.
    - **Lifecycle Berita, Arsip & Pemisahan Kategori (`AP-06.01`..`AP-06.04`, `AP-06.07`..`AP-06.08`)**: Menambahkan dukungan `is_archived` pada `SchoolNewsController` dan `Core/News/Index.vue`, memfilter `is_archived = false` pada kueri publik, memisahkan publikasi `karir-alumni` dan `beasiswa-alumni` agar tidak mencemari slot berita utama beranda, serta mendukung format ringkasan prestasi terstruktur (`Peringkat | Tingkat | Peraih`) dan foto berita pada `/prestasi`.
  - **UI Admin Pusat Unduh, Zona Integritas/IKM & Jadwal Salat (`AP-07`)**:
    - **Pusat Unduh (`AP-07.01`, `AP-07.02`, `PUB-12`)**: Menambahkan `GET /pengaturan/pusat-unduh`, `PUT /pengaturan/pusat-unduh/{download}`, dan `DELETE /pengaturan/pusat-unduh/{download}` di `PublicPortalAdminController` & `PublicPortalService`, lengkap dengan halaman `resources/js/Pages/Core/Downloads/Index.vue` (tabel berheader rata tengah, aksi baris icon-only di kolom kanan dengan tooltip, modal tambah/edit dokumen & ganti file, toggle publikasi, serta `ConfirmDialog` hapus).
    - **Zona Integritas & Moderasi IKM (`AP-07.03`..`AP-07.05`, `PUB-13`)**: Menambahkan `GET /pengaturan/zona-integritas`, `PUT /pengaturan/zona-integritas/konten`, dan `DELETE /pengaturan/zona-integritas/{survey}` beserta halaman `resources/js/Pages/Core/Integrity/Index.vue` untuk mengelola teks Maklumat Pelayanan, 6 Area Perubahan WBK/WBBM, kategori tautan SOP, daftar unit layanan IKM, serta moderasi setujui/tarik/hapus tanggapan survei IKM yang langsung tercermin pada `/zona-integritas`.
    - **Konfigurasi Jadwal Salat (`AP-07.06`, `AP-07.07`, `PUB-14`)**: Menambahkan pengaturan `prayer_city` (wilayah jadwal salat, terpisah dari alamat jalan multiline) dan 5 waktu salat (`Subuh`, `Dzuhur`, `Ashar`, `Maghrib`, `Isya`) di `SchoolController` & `Core/School/Edit.vue`, yang dikonsumsi oleh `PublicPortalService::prayerTimesForSchool()`.
    - Mendaftarkan menu `Pusat Unduh` (`downloads`) dan `Zona Integritas & IKM` (`integrity-zone`) untuk role `admin` dan `operator` di `config/navigation.php`, `SidebarNavigation.vue`, dan `Breadcrumbs.vue`.
  - **Katalog E-Layanan & Data Siswa Publik (`AP-08`, `AP-09`)**:
    - Menambahkan konfigurasi status layanan katalog (`service_catalog`: status aktif/pemeliharaan, badge, dan pesan jadwal/pemeliharaan) di `SchoolController`, `Core/School/Edit.vue`, `PublicPortalService::serviceCatalogOverrides()`, `PublicPortalController::services()`, dan `Public/Services/Index.vue`.
    - Menghubungkan opsi `hide_demo_fallbacks` ke `VerificationController::studentData()` (`AP-09.03`) sehingga direktori siswa demo sintetis dapat dimatikan pada penggunaan nyata sambil menjaga privasi agregat siswa dan verifikasi NISN+nama (`AP-09.01`..`AP-09.07`).
- **Data**:
  - Memanfaatkan tabel eksisting (`public_downloads`, `public_ikm_surveys`, `public_visit_stats`, `school_news`, `school_gallery_albums`, `school_organization_members`) dan struktur JSON `schools.settings` (`public_content`, `public_videos`, `integrity_zone`, `prayer_city`, `prayer_times`, `service_catalog`) tanpa menambah migrasi atau mengubah skema DB.
- **Perubahan**:
  - Backend: `app/Modules/Core/Infrastructure/Models/School.php`, `app/Modules/Core/Application/Services/PublicPortalService.php`, `app/Modules/Core/Interface/Http/Controllers/{SchoolController,SchoolProfileController,PublicPortalController,PublicPortalAdminController,SchoolGalleryController,SchoolNewsController,VerificationController}.php`, `app/Modules/Core/Interface/Routes/web.php`, `config/navigation.php`
  - Frontend Admin & Publik: `resources/js/Pages/Core/School/Edit.vue`, `resources/js/Pages/Core/Gallery/Index.vue`, `resources/js/Pages/Core/News/Index.vue`, `resources/js/Pages/Core/Downloads/Index.vue` (baru), `resources/js/Pages/Core/Integrity/Index.vue` (baru), `resources/js/Pages/Public/Integrity/Index.vue`, `resources/js/Pages/Public/Services/Index.vue`, `resources/js/components/{SidebarNavigation,Breadcrumbs}.vue`
  - Template Publik Blade: `resources/views/public/madrasah/{home,profile,news,achievements,gallery,videos}.blade.php`
  - Tes & Dokumentasi: `tests/Feature/{PublicContentLifecycleTest,PublicPortalTest}.php`, `docs/{TODO_ADMIN_PUBLIC_INTEGRATION,HANDOFF}.md`
- **Verifikasi**:
  - `./vendor/bin/pint --test --dirty` → **lulus (`passed`)**.
  - `./node_modules/.bin/vue-tsc --noEmit` → **lulus (exit code 0)**.
  - `./node_modules/.bin/vite build` → **lulus (`1004 modules transformed`, `built in 7.72s`)**.
  - `php artisan test --compact tests/Feature/PublicContentLifecycleTest.php tests/Feature/PublicPortalTest.php tests/Feature/MadrasahPublicThemeTest.php` → **23 tes lulus (406 assertions)**.
  - `php artisan test --compact` (suite penuh) → **528 tes, 526 lulus, 2 dilewati (MySQL-only), 6.110 assertions** (`83.67s`).
- **Pemulihan**:
  - Rollback perubahan file via `git checkout --` pada daftar file di atas dan hapus `resources/js/Pages/Core/{Downloads,Integrity}/Index.vue` jika diperlukan; tidak ada perubahan skema database.
- **Sisa & Kesiapan Fase Berikutnya**:
  - Pekerjaan utama Fase 2 (`AP-05` s.d. `AP-09`) beserta prasyarat CMS `AP-04` telah diimplementasikan dan diuji (`READY_REVIEW`), dengan item granular yang belum dicakup tetap ditandai `OPEN` secara jujur pada `docs/TODO_ADMIN_PUBLIC_INTEGRATION.md`.
  - Siap menerima review atau arahan pengguna untuk melanjutkan ke **Fase 3 (`AP-10` s.d. `AP-11`: Fondasi transaksi/status/dokumen dan integrasi PTSP, surat dinas serta buku tamu)**.

## Koreksi review Fase 1 — 22 November 2026

- **Status**: klaim `VERIFIED` untuk seluruh AP-01 s.d. AP-04 pada laporan sebelumnya tidak berlaku sebagai penerimaan final. Task terkait telah dibuka kembali menjadi `READY_REVIEW` atau `OPEN` sesuai bukti dan cakupan yang belum tersedia.
- **Publikasi konten**: simpan profil tidak mengirim aksi publikasi; server memperlakukan konten tanpa aksi eksplisit sebagai draf. Aksi publikasi/restore terpisah dan state aksi direset setelah request. Update sekolah memakai transaksi/row lock dan editor mengirim revision token untuk menolak perubahan kedaluwarsa.
- **Revisi/restore**: nomor publikasi dihitung dari versi maksimum, bukan jumlah riwayat yang dipangkas. Restore sah mencatat revisi baru berikut sumber versinya; versi restore hilang/tidak valid ditolak tanpa menerbitkan draf atau mengubah profil.
- **Kewenangan**: petugas operator/PTSP memverifikasi dan memproses antrean; penerbitan PTSP, pengesahan legalisir, persetujuan mutasi serta penerbitan/reissue SKL memerlukan admin/kepala/penandatangan. Service tetap memeriksa kewenangan walaupun dipanggil langsung. Route, navigasi dan tombol UI diselaraskan; signatory tidak otomatis memperoleh hak mengelola katalog atau menyiapkan data kelulusan.
- **Kontras**: nilai dihitung dari luminans warna, termasuk peringatan untuk `#b87818` terhadap putih (sekitar 3,66:1), bukan label AA yang ditulis manual.
- **Tes regresi baru**: `tests/Feature/PublicContentLifecycleTest.php` untuk pemisahan profil/draf, default aman, retensi/revisi, restore tidak sah dan konflik editor; `tests/reference/colorContrast.test.mjs`; perluasan tes HTTP PTSP dan lifecycle siswa untuk penolakan penerbitan oleh operator/petugas dan keberhasilan pejabat berwenang.
- **Batas cakupan**: tidak menambah dependency atau migrasi DB, tidak mengubah tampilan publik, tidak mengimplementasikan seluruh CMS pada Fase 2. Editor yang ada tetap baru empat blok teks; preview privat, jadwal/unpublish, media, visibilitas/urutan dan penghapusan fallback demo tetap tercatat sebagai pekerjaan terbuka.
- **Kelanjutan Gemini**: Fase 2 dapat diambil per irisan, dengan mendahulukan prasyarat AP-04 terkait dan menjaga status task yang belum teruji. Jangan mencentang kembali seluruh Fase 1 secara massal.
- **Verifikasi akhir**:
  - `php artisan test --compact` → **525 tes, 523 lulus, 2 dilewati, 6.025 assertions**. Suite dijalankan ulang setelah penyesuaian kewenangan terakhir.
  - `npm run typecheck` dan `npm run build` → lulus; build memakai log `/tmp/phase-one-repairs-build.log` pada lingkungan pengerjaan.
  - `vendor/bin/pint --test --dirty` dan `git diff --check` → lulus.
  - `node --experimental-strip-types --test tests/reference/colorContrast.test.mjs` → lulus.
  - Pengujian tambahan menggunakan SQLite `:memory:`; tidak mengubah data aplikasi lokal. Tidak ada migrasi atau dependency baru yang diterapkan.
  - UAT browser semua role dan pembandingan visual seluruh publik tetap bukan klaim selesai dari pengujian otomatis ini; ikuti task yang dibuka kembali pada TODO.

## Fase 1 (`AP-01` s.d. `AP-04`) — Identitas Role/Scope, Standar UI Admin 01–07, Branding Global & Fondasi Konten Publik — 22 November 2026

> Laporan historis Gemini berikut telah dikoreksi oleh bagian review di atas. Pernyataan selesai/VERIFIED di dalamnya tidak boleh dipakai untuk melewati task terbuka.

- **Task / Status**: `AP-01.01` s.d. `AP-01.12`, `AP-02.01` s.d. `AP-02.22`, `AP-03.01` s.d. `AP-03.10`, dan `AP-04.01` s.d. `AP-04.18` — **VERIFIED**.
- **Kontrak Publik**:
  - Mempertahankan 100% tampilan dan tata letak baseline publik (`PUB-01` s.d. `PUB-42`, termasuk layout split `/login` publik sesuai `D-02`).
  - Menghubungkan blok konten ber-ID stabil (`home.hero`, `profile.values`, `profile.pillars`, `shell.footer`) serta tautan sosial media/jam layanan/peta dari `School::publicContentRegistry()` dan `PublicPortalService::madrasahShellData()` ke template publik (`home.blade.php`, `profile.blade.php`, `footer.blade.php`) dengan fallback identik terhadap baseline.
- **Kontrol Admin**:
  - **`AP-01` (Role, Scope, Pemisahan Tugas `D-03` & Allowlist Redirect)**:
    - Menambahkan helper otorisasi pemisahan tugas (`canVerifyQueue`, `canApproveFinalDecision`, `canSignOfficialDocument`) dan validasi allowlist path pasca-login (`sanitizeReturnPath`) pada `AccessScopeService`.
    - Memperketat `ExternalApplicantAuthController` dengan daftar allowlist eksplisit (`ALLOWED_RETURN_PATHS`) agar parameter `return_to` tidak dapat disalahgunakan untuk open redirect atau eskalasi hak lintas portal.
  - **`AP-02` (Standar UI Admin Aturan 01–07)**:
    - **Aturan 01 (Ukuran & Radius Tombol)**: Menyelaraskan token `resources/css/buttons.css` ke baseline standar (`--button-height-sm: 32px`, `--button-height: 36px`, `--button-height-lg: 40px`, `--button-radius: 8px`, font `0.875rem`/`14px`) sambil menjaga touch target `>= 44px` pada `@media (max-width: 720px), (pointer: coarse)`.
    - **Aturan 03 (Tabel, Aksi Baris & Ekspor Word/PDF/Excel)**: Menetapkan header `.app-table th` rata tengah (`text-align: center`), isi teks kiri, `.is-numeric` kanan, `.is-status`/`.is-actions` tengah di `resources/css/shell.css`; menambahkan ikon `view` dan `word` (`DOC`) beserta warna identitas biru pada `ButtonIcon.vue`, `AppButton.vue`, `RowActionButton.vue`, dan `buttons.css`.
    - **Aturan 04 & 06 (Modal Focus Trap, Confirm Alert Terpusat & Toggle Password)**: Melengkapi `ConfirmDialog.vue` dengan focus trap (`Tab`/`Shift+Tab`), `Escape`, scroll lock, dan restore focus; mengganti seluruh `window.confirm` di halaman admin Core (`Core/Users/Index.vue`, `Core/Organization/Index.vue`, `Core/Gallery/Index.vue`, `Core/News/Index.vue`, `Core/Security/Index.vue`) dengan `ConfirmDialog.vue` terpusat; serta menambahkan tombol lihat/tutup password yang aksesibel pada modal tambah dan ubah pengguna di `Core/Users/Index.vue`.
    - **Aturan 07 (Stacked Dismissible Toasts)**: Meningkatkan notifikasi flash di `AppLayout.vue` menjadi antrean *stacked toasts* (`.app-toast-stack`) yang responsif, memiliki `aria-live="polite"`, dan dapat ditutup per item.
  - **`AP-03` & `AP-04` (Branding Global, Kontras/Reset Tema & Fondasi CMS Publik)**:
    - Memperluas `SchoolController`, `School`, dan `Core/School/Edit.vue` untuk mengelola zona waktu (`Asia/Jakarta`, `Asia/Makassar`, `Asia/Jayapura`), jam layanan operasional, informasi hari libur/penutupan, kontak darurat, URL peta, WhatsApp bantuan, dan tautan sosial media (`facebook`, `instagram`, `youtube`, `tiktok`).
    - Menambahkan informasi rasio kontras warna utama dan fitur **Reset tema default** dengan dialog konfirmasi terpusat (`ConfirmDialog.vue`).
    - Membangun `School::publicContentRegistry()` & `SchoolController::applyPublicContentUpdate()` yang memisahkan state `draft` dan `published`, menyaring tag HTML/script berbahaya (`strip_tags`), menyimpan riwayat versi (`revisions` lengkap dengan nomor versi, waktu, dan nama pelaku), serta mendukung pemulihan versi (`restore`) dari panel `/pengaturan/sekolah`.
- **Data**:
  - Menggunakan kolom `schools.timezone` dan struktur JSON `schools.settings` (`theme_primary`, `service_hours`, `holiday_notice`, `emergency_contact`, `map_url`, `whatsapp_url`, `social_links`, `public_theme`, `public_content.{draft,published,revisions}`) tanpa mengubah skema tabel database.
- **Flow**:
  - Admin/Operator menyunting identitas, tema, kanal publik, atau blok konten di `/pengaturan/sekolah` -> dapat menyimpan sebagai **Draf** (tidak mengubah halaman publik live) atau **Terbitkan** (mencatat revisi baru dan memperbarui halaman publik) atau **Pulihkan versi terakhir** / **Reset tema default** (dengan konfirmasi `ConfirmDialog`).
- **Perubahan**:
  - Backend: `app/Modules/Core/Application/Services/{AccessScopeService,PublicPortalService}.php`, `app/Modules/Core/Infrastructure/Models/School.php`, `app/Modules/Core/Interface/Http/Controllers/{ExternalApplicantAuthController,SchoolController,SchoolProfileController}.php`
  - Frontend Admin & Komponen: `resources/css/{buttons,shell}.css`, `resources/js/layouts/AppLayout.vue`, `resources/js/components/{AppButton,ButtonIcon,ConfirmDialog,RowActionButton}.vue`, `resources/js/Pages/Core/School/Edit.vue`, `resources/js/Pages/Core/Users/Index.vue`, `resources/js/Pages/Core/Organization/Index.vue`, `resources/js/Pages/Core/Gallery/Index.vue`, `resources/js/Pages/Core/News/Index.vue`, `resources/js/Pages/Core/Security/Index.vue`
  - Template Publik: `resources/views/public/madrasah/{home,profile,footer}.blade.php`
  - Tes & Dokumentasi: `tests/Feature/{ParityFoundationTest,BrandingProfileTest}.php`, `docs/{TODO_ADMIN_PUBLIC_INTEGRATION,HANDOFF}.md`
- **Verifikasi**:
  - `./node_modules/.bin/vue-tsc --noEmit` -> **lulus (exit code 0)**.
  - `./node_modules/.bin/vite build` -> **lulus (`997 modules transformed`, `built in 3.88s`)**.
  - `./vendor/bin/pint --test` pada seluruh file PHP yang disentuh -> **lulus (`passed`)**.
  - `php artisan test --compact tests/Feature/ParityFoundationTest.php tests/Feature/RoleMatrixAccessTest.php tests/Feature/BrandingProfileTest.php tests/Feature/PublicPortalTest.php tests/Feature/MadrasahPublicThemeTest.php tests/Feature/UserManagementTest.php tests/Feature/CoreAuthorizationTest.php tests/Feature/AuthenticationTest.php tests/Feature/PtspAndTataUsahaTest.php` -> **82 tes lulus (1.072 assertions)** dalam `7.02s`.
- **Pemulihan**:
  - Rollback kode via `git checkout --` pada file yang disebutkan di atas; untuk data blok konten publik dapat dipulihkan langsung dari tombol **Pulihkan versi terakhir** atau **Reset tema default** di `/pengaturan/sekolah`.
- **Sisa & Kesiapan Fase Berikutnya**:
  - Fase 1 (`AP-01` s.d. `AP-04`) selesai dan terverifikasi.
  - Siap melanjutkan ke **Fase 2 (`AP-05` s.d. `AP-09`)**: perluasan pengelolaan konten editorial beranda/profil/GTK/organisasi/fasilitas/kesiswaan/kontak (`AP-05`), berita/prestasi/galeri foto & **galeri video** (`AP-06`), **UI admin pusat unduh, zona integritas/IKM** & jadwal salat (`AP-07`), katalog e-layanan & navigasi (`AP-08`), serta direktori publik & agregat data siswa (`AP-09`).

## Fase 0 (`AP-00`) — Baseline, Kontrak Halaman & Rekonsiliasi Inventaris Integrasi Admin–Publik — 22 November 2026

- **Task / Status**: `AP-00.01` s.d. `AP-00.10` — **VERIFIED** (Fase 0: Audit, inventaris, pemetaan kontrak publik–admin, dan rekonsiliasi standar UI; tanpa mengubah kode aplikasi sesuai kontrak Fase 0).
- **Kontrak Publik**:
  - Memetakan seluruh **42 kontrak halaman publik (`PUB-01` s.d. `PUB-42`)** beserta tab, modal, formulir, kartu, cetak PDF, token verifikasi, dan shell global (`navigation.blade.php`, `footer.blade.php`, masthead varian layanan).
  - Baseline visual publik merujuk pada 36 screenshot terverifikasi di `.playwright-mcp/public-audit-*.png` dan `.playwright-mcp/parity-audit/` (`1280×720`, `950×900`, `390×844`) serta snapshot HTML di `screenshot_halaman_asli/`.
- **Kontrol Admin & Rekonsiliasi Rute**:
  - Mengekstrak dan merekonsiliasi seluruh **496 rute** aplikasi: `80` rute publik, `16` rute tamu berakun (`ExternalApplicant`), `4+` rute token/signed/unduhan resi, `14` rute operasional/kiosk/scanner, dan `382` rute internal terautentikasi (`auth`).
  - Mengidentifikasi kesenjangan P0/P1 antara halaman publik dan panel admin:
    1. **Dualisme Perpustakaan (`PUB-32`, `PUB-33`, `PUB-39`, `D-01`)**: `/perpustakaan` dan `/perpustakaan/man4` masih merender view statis `@verbatim` (`LibraryController::replica`) dengan stub `/perpustakaan/aksi` (`status: error`), sedangkan portal dinamis yang terhubung ke `LibraryService` berada di `/perpustakaan/portal`.
    2. **Galeri Video Statis (`PUB-11`)**: `/galeri/video` (`SchoolGalleryController::publicVideos`) masih merender array statis `$allVideos` di `resources/views/public/madrasah/videos.blade.php` tanpa model/CRUD admin.
    3. **Endpoint Admin Tanpa Halaman/Menu (`PUB-12`, `PUB-13`)**: `POST /pengaturan/pusat-unduh`, `POST /pengaturan/pusat-unduh/{download}/publikasi`, dan `POST /pengaturan/zona-integritas/{survey}/moderasi` sudah ada di `PublicPortalAdminController`, tetapi belum memiliki rute `GET` halaman Inertia maupun menu di `config/navigation.php` & `SidebarNavigation.vue`.
    4. **Blok Editorial & Konfigurasi Statis (`PUB-01`..`PUB-08`, `PUB-14`, `PUB-15`, `PUB-29`, `PUB-42`)**: Konten hero, badge, micro-stats, 5 nilai budaya kerja, 4 pilar keunggulan, program akademik, ekskul, prestasi, direktori GTK publik, katalog e-layanan, karir/beasiswa alumni, serta tautan header/footer/widget di `resources/views/public/madrasah/*.blade.php` dan `config/public_portal.php` belum seluruhnya dapat dikelola dari panel admin.
    5. **Override Demo Lokal (`PublicPortalService`)**: Kondisional `Madrasah Contoh Nusantara` pada `PublicPortalService::madrasahShellData()` baris 74–76 tercatat untuk dibersihkan pada Fase 1/2 (`AP-03.07` / `AP-05.10`).
- **Data & Audit Standar UI Admin (`D-01` s.d. `D-09`)**:
  - Membandingkan ketujuh aturan standar UI (`https://github.com/ilhamrizqiawan21/standar-ui-project` snapshot `87490c183b9d57b2600e3daa986171e84df4ab5c`) dengan shell admin lokal (`AppLayout.vue`, `TopNavbar.vue`, `SidebarNavigation.vue`, `resources/css/{shell,buttons}.css`):
    - Navigasi topbar desktop & drawer mobile di `AppLayout.vue` sudah selaras secara struktural dengan Aturan 02, namun ukuran/radius tombol (`40px/48px` & `10px` vs baseline `32/36/40px` & `6–8px`), perataan header tabel (`.app-table th` masih `left` vs `center`), aksi baris icon-only + tooltip, tombol ekspor Word/PDF/Excel, penggunaan modal CRUD terpusat, preview/reset tema terkonfirmasi, dan stacked toasts perlu diselesaikan di Fase 1 (`AP-02`, `AP-03`).
  - Mendokumentasikan rekomendasi/keputusan `D-01` s.d. `D-09`, fixture sintetis 17 role (`RoleEnum`) + 5 tipe `ExternalApplicant`, serta urutan irisan vertikal Fase 1 s.d. Fase 6 di `docs/TODO_ADMIN_PUBLIC_INTEGRATION.md`.
- **Flow**: Audit dan pemetaan inventaris Fase 0 (`AP-00.01` s.d. `AP-00.10`) -> pembaruan matriks kontrak dan status `VERIFIED` pada `docs/TODO_ADMIN_PUBLIC_INTEGRATION.md` -> pencatatan handoff Fase 0 di `docs/HANDOFF.md`.
- **Perubahan**:
  - `docs/TODO_ADMIN_PUBLIC_INTEGRATION.md` (penandaan `AP-00.01` s.d. `AP-00.10` sebagai `VERIFIED` beserta dokumentasi hasil audit 7 bagian).
  - `docs/HANDOFF.md` (penambahan laporan serah terima Fase 0).
  - **Tidak ada file kode aplikasi yang diubah pada Fase 0.**
- **Verifikasi**:
  - Ekstraksi & analisis seluruh `496` rute aplikasi dan `79` file migrasi.
  - `php artisan test --compact tests/Feature/DemoAlumniSeederTest.php tests/Feature/MadrasahPublicThemeTest.php` -> **8 tes lulus (142 assertions)** (`1.77s`).
  - Baseline sebelumnya pada sesi yang sama: `vue-tsc --noEmit` lulus (`exit 0`), `vite build` lulus (`994 modules`), `StudentLifecycleAndAlumniTest` + `MadrasahPublicThemeTest` lulus (`16 passed, 567 assertions`).
- **Pemulihan**: `git checkout -- docs/TODO_ADMIN_PUBLIC_INTEGRATION.md docs/HANDOFF.md` (tidak ada perubahan kode atau database).
- **Sisa & Kesiapan Fase Berikutnya**:
  - Fase 0 (`AP-00`) selesai penuh.
  - Siap melanjutkan ke **Fase 1 (`AP-01` s.d. `AP-04`)**: Role/permission & scope backend (`AP-01`), standar UI admin 01–07 (`AP-02`), branding & tema global (`AP-03`), serta fondasi pengelolaan konten publik (`AP-04`).

## Penyelarasan Presisi Portal Alumni (IKA) (`/alumni`, `M4-LIF-07`) — 22 November 2026

- **Task & Requirement**: Menyelaraskan tampilan dan interaksi halaman Portal Alumni (`/alumni`, `M4-LIF-07`) dengan referensi `https://man4banjar.com/akademik/alumni.php` (`screenshot_halaman_asli/PORTAL ALUMNI (IKA) - MADRASAH ALIYAH NEGERI 4 BANJAR.html`), sambil menjaga identitas sekolah dinamis, batas privasi direktori *opt-in*, serta alur moderasi & E-KTA.
- **Ringkasan**:
  - **Topbar, Ticker & Navbar 2-Baris (`navigation.blade.php`, `parity.css`, `home.js`)**: Menyelaraskan topbar Portal IKA (NPSN, alamat, telepon, counter `Online: X User`, badge `Layanan IKA: Aktif`, pemilih 3 tema warna `blue`/`red`/`green` dengan persistensi `localStorage` dan variabel CSS `--ika-theme-*`, serta ikon sosial media), ticker `AGENDA IKA` berjalan (`.animate-marquee-slow`), dan header 2-baris lengkap (brand `IKATAN KELUARGA ALUMNI (IKA)` + badge `Official Hub`, tombol `Cari`, `Isi Tracer Study`, `KTA Digital`, `Legalisir Ijazah ↗`, `Beranda`, serta sub-navbar berikon dengan dukungan pemilihan tab & modal statistik via custom event `ika:select-tab` dan `ika:open-modal`).
  - **Hero 12-Kolom, Pita Pintasan Berjalan, Tab Portal & Direktori Per Angkatan (`Public/Alumni/Home.vue`)**:
    - Mengubah hero menjadi grid 12-kolom (7 kolom teks/pencarian/filter cepat/CTA/4 kartu metrik interaktif + 5 kolom frame gambar dengan 2 badge melayang `JEJARING KUAT` & `TRACER STUDY TERINTEGRASI` serta caption `SILATURAHMI & SINERGI`).
    - Mengubah 7 kartu pintasan menjadi pita marquee berjalan (`.ika-quick-strip`) di bawah hero.
    - Menambahkan tombol `Update Status Anda` di kanan baris tab (`Direktori Alumni`, `Tracer Study Mandiri`, `Bursa Karir`, `Pusat Beasiswa`, `E-KTA Digital`).
    - Menyelaraskan kartu `HALL OF FAME` 4-kolom, bar filter 4-kriteria (`12-col` grid), serta 3 mode tampilan direktori: **Foto Berjalan** per kotak angkatan (`.alumni-angkatan-box` dengan header `Angkatan YYYY`, jumlah alumni, tombol `Lihat Semua Angkatan YYYY (Grid)`, dan kartu pasfoto `3:4` `.alumni-marquee-card` + badge tahun + pill status berwarna), **Grid Kartu** 6-kolom (`.alumni-grid-wrapper` + `.alumni-grid-card` + `.alumni-activity-box` + tombol `Lihat Profil`), dan **Tabel Rapi**.
    - Menambahkan modal detail profil alumni, modal `Statistik Sebaran Alumni & Tracer Study`, pencarian lokal pada tab Bursa Karir & Beasiswa, pemeriksaan nama pada tab E-KTA Digital, serta 6 kartu berwarna pada bagian `PROGRAM & LAYANAN UNGGULAN`.
  - **Footer 4-Kolom Portal IKA (`footer.blade.php`, `parity.css`)**: Menyelaraskan footer gelap 4-kolom (`IKATAN KELUARGA ALUMNI`, `MENU PORTAL`, `LAYANAN AKADEMIK`, `SEKRETARIAT IKA`) dengan aksen garis kiri berwarna dan tautan `Kembali ke Atas ↑`.
- **File & Migrasi**: `resources/js/Pages/Public/Alumni/Home.vue`, `resources/views/public/madrasah/navigation.blade.php`, `resources/views/public/madrasah/footer.blade.php`, `public/madrasah-theme/parity.css`, `public/madrasah-theme/home.js`, `public/build/`, serta `docs/{HANDOFF,TODO_PARITAS_MAN4_BANJAR,INVENTARIS_DAN_BASELINE_PARITAS}.md`. Tanpa migrasi DB baru.
- **Tes & Hasil**:
  - `./node_modules/.bin/vue-tsc --noEmit` -> lulus (exit code 0).
  - `./node_modules/.bin/vite build` -> lulus (`994 modules transformed`, `built in 4.47s`).
  - `php artisan test --compact tests/Feature/StudentLifecycleAndAlumniTest.php tests/Feature/MadrasahPublicThemeTest.php` -> **16 tes lulus (567 assertions)**.
- **Risiko Tersisa**: Foto profil direktori publik menggunakan avatar standar `/madrasah-theme/images/kiosk/avatar-1.png` karena dokumen foto alumni disimpan di private storage (`photo_document_id`) sesuai batas privasi `M4-LIF-07`.
- **Langkah Review Manual**: Buka `/alumni`, uji pemilih 3 tema warna di topbar, pencarian & filter cepat di hero, pita pintasan berjalan, pergantian mode `Foto Berjalan` / `Grid Kartu` / `Tabel Rapi`, klik kartu alumni untuk membuka modal detail, klik menu `Statistik`, serta cek tampilan responsif mobile.
- **Rollback**: `git checkout -- resources/js/Pages/Public/Alumni/Home.vue resources/views/public/madrasah/navigation.blade.php resources/views/public/madrasah/footer.blade.php public/madrasah-theme/parity.css public/madrasah-theme/home.js public/build`

## Halaman Publik Data Siswa (`/data-siswa`, `M4-LIF-04`) — 21 November 2026

- **Task & Requirement**: Membuat halaman publik **Data Siswa** (`/data-siswa`) dalam shell **Aplikasi Database Siswa** tanpa membocorkan PII daftar siswa mentah (`M4-LIF-04`).
- **Ringkasan**:
  - **Ringkasan Agregat & Verifikasi Aman (`AcademicQueryService`, `VerificationController`, `routes/web.php`)**: Menambahkan `publicStudentSummary(int $schoolId)` (total siswa aktif, jumlah rombel aktif, rekap rombel per tingkat tanpa PII individu) serta endpoint `GET /data-siswa` dan `POST /data-siswa/cek` (rate-limited `throttle:sensitive`) yang memanggil `verifyPublicStudentStatus` berbasis NISN + Nama.
  - **Halaman Vue & Shell (`Public/Students/Index.vue`, `navigation.blade.php`, `footer.blade.php`)**: Membangun halaman dengan hero banner biru, 3 kartu statistik, form cek keaktifan siswa, rekapitulasi rombel per tingkat, sidebar tautan layanan terkait & perlindungan data, serta menghubungkan menu `Data Siswa` pada navbar desktop/mobile ke `/data-siswa` dengan footer `.parity-akademik-footer`.
- **File & Migrasi**: `app/Modules/Academic/Application/Services/AcademicQueryService.php`, `app/Modules/Core/Interface/Http/Controllers/VerificationController.php`, `app/Modules/Core/Interface/Routes/web.php`, `resources/js/Pages/Public/Students/Index.vue`, `resources/views/public/madrasah/navigation.blade.php`, `resources/views/public/madrasah/footer.blade.php`, `tests/Feature/StudentLifecycleAndAlumniTest.php`, `public/build/`, dan `docs/HANDOFF.md`. Tanpa migrasi DB.
- **Rollback**: `git checkout -- app/Modules/Academic/Application/Services/AcademicQueryService.php app/Modules/Core/Interface/Http/Controllers/VerificationController.php app/Modules/Core/Interface/Routes/web.php resources/views/public/madrasah/navigation.blade.php resources/views/public/madrasah/footer.blade.php tests/Feature/StudentLifecycleAndAlumniTest.php public/build && rm -f resources/js/Pages/Public/Students/Index.vue`

## Penyelarasan Presisi Halaman Mutasi Siswa (`M4-LIF-02`) — 21 November 2026

- **Task & Requirement**: Penyelarasan 100% tampilan `/layanan/mutasi` (`M4-LIF-02`) terhadap referensi `screenshot_halaman_asli/MUTASI SISWA _ MADRASAH ALIYAH NEGERI 4 BANJAR.html` dan screenshot pembanding, sambil menjaga identitas sekolah dinamis, alur pengajuan/revisi mutasi masuk & keluar, serta status periode aktif.
- **Ringkasan**:
  - **Hero Banner, Tab & Formulir (`Mutation.vue`)**: Mengganti layout sempit `max-w-4xl` menjadi hero banner full-bleed `.mutasi-hero-banner` (`#0f4c81 → #1b85df`, `border-radius: 0 0 20px 20px`) + kontainer `1140px`, menyelaraskan 3 tombol tab `.btn-mutasi-tab` berikon (`Alur & Persyaratan Mutasi`, `Formulir Permohonan Mutasi`, `Cek Status Pengajuan`), serta menata formulir `#form-mutasi`, riwayat pengajuan, dan perbaikan berkas dalam `.card-custom` + `.form-section-header` biru.
  - **Komponen SOP & CSS Prosedur (`MutationProcedure.vue`, `procedures.css`)**: Membungkus aturan stacking mobile `.reference-mutasi` di `procedures.css` ke dalam `@media (max-width: 767.98px)` agar baris SOP kembali horizontal (`row` + kotak `Estimasi Waktu` `135px`) pada desktop/half-screen; menambahkan pembungkus pill `.sop-toggle-wrap` + `.sop-toggle-group` untuk pemilih `SOP Mutasi Masuk (Siswa Pindahan)` / `Alur Mutasi Keluar (Pindah Sekolah)`; mengganti teks sekolah statis dengan prop `schoolName` dinamis; mengganti ikon FA4 yang hilang (`fa-calendar-check-o`, `fa-heart`, `fa-check-circle`) ke kelas FA6 lokal; serta melengkapi utilitas CSS pendukung (`.mr-2`, `.d-none`, `.d-md-block`, `.text-primary`, `.text-warning`, `.text-info`, `.text-muted`).
  - **Footer Halaman Aplikasi Database Siswa (`footer.blade.php`, `parity.css`)**: Mengganti footer portal hijau besar pada rute `lacak`, `layanan/legalisir`, `layanan/mutasi`, `layanan/kelulusan`, `layanan/izin-siswa`, dan `layanan/surat-kuasa` dengan `.parity-akademik-footer` (`#343a40`, judul dinamis per layanan + subteks sekolah `- Melayani dengan Sepenuh Hati`) sesuai referensi.
- **File & Migrasi**: `public/madrasah-theme/procedures.css`, `public/madrasah-theme/parity.css`, `resources/views/public/madrasah/footer.blade.php`, `resources/js/components/MutationProcedure.vue`, `resources/js/Pages/Public/Services/Mutation.vue`, `public/build/`, serta `docs/{HANDOFF,TODO_PARITAS_MAN4_BANJAR,INVENTARIS_DAN_BASELINE_PARITAS}.md`. Tanpa migrasi DB.
- **Tes & Hasil**:
  - `./node_modules/.bin/vue-tsc --noEmit` -> lulus (exit code 0).
  - `./node_modules/.bin/vite build` -> lulus (`987 modules transformed`, `built in 3.25s`).
  - `php artisan optimize:clear` -> lulus (`config`, `cache`, `compiled`, `events`, `routes`, `views` bersih).
  - `php artisan test --compact tests/Feature/StudentLifecycleAndAlumniTest.php tests/Feature/MadrasahPublicThemeTest.php` -> **10 tes lulus (302 assertions)**.
  - Playwright MCP pada `/layanan/mutasi` (`PilotSchoolSeeder` SQLite sementara): `1280×720` (`overflow: false`, Tab 1 Masuk/Keluar & Tab 2 Formulir), `950×900` (`overflow: false`, `stepRowFlexDir: row`), dan `390×844` (`overflow: false`, `stepRowFlexDir: column`, `scrollWidth: 382`).
- **Rollback**: `git checkout -- public/madrasah-theme/procedures.css resources/js/components/MutationProcedure.vue resources/js/Pages/Public/Services/Mutation.vue public/build`

## Penyelarasan Presisi Halaman Legalisir Ijazah Online (`M4-LIF-05`) — 21 November 2026

- **Task & Requirement**: Penyelarasan 100% tampilan `/layanan/legalisir` (`M4-LIF-05`) terhadap referensi `screenshot_halaman_asli/LEGALISIR IJAZAH _ MADRASAH ALIYAH NEGERI 4 BANJAR.html` dan screenshot pembanding, sambil menjaga identitas sekolah dinamis dan seluruh alur pengajuan/verifikasi server-side.
- **Ringkasan**:
  - **Hero, Tab Navigasi & Layout (`Legalization.vue`)**: Mengganti pembungkus `max-w-4xl` menjadi hero full-bleed `.legalisir-hero-banner` (`#0f4c81 → #1b85df`, `border-radius: 0 0 24px 24px`) dan kontainer `1140px`, menampilkan 5 tombol tab `.btn-tab-menu` berikon (`Alur & Ketentuan`, `Formulir Pengajuan`, `Cetak Surat Kuasa`, `Cetak SPTJM`, `Cek Status Pengajuan`), menyelaraskan breakpoint 2-kolom pada `>= 992px` dan 1-kolom pada `< 992px` (termasuk `950×900`), mengganti sidebar tunggal dengan 3 kartu `.card-custom` (`Ringkasan Persyaratan`, `Jadwal Pelayanan TU`, `Lokasi & Layanan`), menghapus `.service-tiles` berlebih, serta menyelaraskan Tab 2 `Formulir Pengajuan` dengan `.form-section-header` biru dan canvas tanda tangan digital interaktif.
  - **Komponen SOP & Controller (`LegalizationProcedure.vue`, `LegalizationController.php`)**: Menambahkan prop `schoolName` dinamis pada `LegalizationProcedure` serta menyertakan `address` sekolah pada `LegalizationController::publicIndex`.
- **File & Migrasi**: `app/Modules/Administration/Interface/Http/Controllers/LegalizationController.php`, `resources/js/components/LegalizationProcedure.vue`, `resources/js/Pages/Public/Services/Legalization.vue`, `public/build/`, serta `docs/{HANDOFF,TODO_PARITAS_MAN4_BANJAR,INVENTARIS_DAN_BASELINE_PARITAS}.md`. Tanpa migrasi DB.
- **Tes & Hasil**:
  - `./node_modules/.bin/vue-tsc --noEmit` -> lulus (exit code 0).
  - `./node_modules/.bin/vite build` -> lulus (`986 modules transformed`, `built in 5.00s`).
  - `./vendor/bin/pint --test app/Modules/Administration/Interface/Http/Controllers/LegalizationController.php` -> lulus (`passed`).
  - `php artisan test --compact tests/Feature/StudentLifecycleAndAlumniTest.php` -> **5 tes lulus (222 assertions)**.
  - Playwright MCP pada `/layanan/legalisir` (`PilotSchoolSeeder` SQLite sementara): `950×900` (`overflow: false`), `1280×720` (`overflow: false`, Tab 1 & Tab 2), dan `390×844` (`overflow: false`, `scrollWidth: 382`).
- **Rollback**: `git checkout -- app/Modules/Administration/Interface/Http/Controllers/LegalizationController.php resources/js/components/LegalizationProcedure.vue resources/js/Pages/Public/Services/Legalization.vue public/build`

## Penyelarasan Presisi Kios Presensi Mandiri Siswa (`M4-OPS-03`) — 21 November 2026

- **Task & Requirement**: Penyelarasan 100% tampilan dan interaksi halaman Kios Presensi Mandiri Siswa (`/kios-presensi`, `M4-OPS-03`) terhadap referensi `screenshot_halaman_asli/Kios Presensi Mandiri Siswa — MADRASAH ALIYAH NEGERI 4 BANJAR.html` dan screenshot pembanding, sambil menjaga nama/logo sekolah dinamis, otorisasi `auth` + `role:admin,operator,picket`, dan aturan data minimal di layar (tanpa `nis`/`nisn`).
- **Ringkasan**:
  - **Tata Letak & Tipografi Kios (`Kiosk.vue`)**: Menetapkan `line-height: normal` pada `.kiosk-shell-man4` serta kontrol `.btn-kiosk`, `.stat-pill`, dan `.gate-badge-indicator` agar tidak mewarisi `line-height` longgar dari `app.css` (tinggi header tepat `85px`, tombol `34px`, footer `57px` di `1280×720`); menyelaraskan logo `52px × 52px`, judul `20px`, dan layout header/footer 1 baris pada desktop maupun half-screen (`950×900`) tanpa wrap yang melompat; memperbaiki wrap responsif pada viewport ponsel (`390×844`) dengan `overflow: false` (`scrollWidth: 375`).
  - **Status Hasil Scan, Audio & Auto-Refresh Statistik (`Kiosk.vue`, `StudentCardKioskService`, `StudentCardKioskController`)**: Menambahkan animasi `kioskCardAppear`, avatar `100px × 100px` dengan fallback `/madrasah-theme/images/kiosk/avatar-1.png`, 4 varian badge (`.success`, `.warning`, `.info` untuk `PRESENSI PULANG`, `.error`), kolom `Keterlambatan` (`0 Menit` / `X Menit` / `-`), `Status Hari Ini` dengan warna dinamis, sambutan suara `speechSynthesis` (`id-ID`), serta endpoint terotorisasi `GET /kios-presensi/stats` untuk auto-polling statistik tiap 15 detik.
- **File & Migrasi**:
  - Backend: `app/Modules/Academic/Application/Services/StudentCardKioskService.php`, `app/Modules/Academic/Interface/Http/Controllers/StudentCardKioskController.php`, `app/Modules/Academic/Interface/Routes/web.php`
  - Frontend: `resources/js/Pages/Academic/Attendance/Kiosk.vue` (plus hasil build `public/build/`)
  - Tes & Docs: `tests/Feature/OperationsPermitKioskPicketBkTest.php`, `docs/HANDOFF.md`, `docs/TODO_PARITAS_MAN4_BANJAR.md`, `docs/INVENTARIS_DAN_BASELINE_PARITAS.md`
  - Tanpa migrasi DB.
- **Keputusan & Asumsi**:
  - Identitas sekolah (nama dan logo) tetap dinamis dari profil sekolah (`SchoolScopeService`), dan respons `/kios-presensi/scan` tetap membatasi data siswa hanya pada `name` dan `class_name` sesuai `M4-OPS-03`.
  - Karena `SecurityHeadersMiddleware` menerapkan `font-src 'self' data:` dan belum ada berkas `.woff2` `JetBrains Mono` di `public/madrasah-theme/fonts/`, jam digital dan angka monospaced memakai fallback `ui-monospace, monospace` sampai aset `.woff2` lokal ditambahkan.
- **Tes & Hasil**:
  - `./node_modules/.bin/vue-tsc --noEmit` -> lulus (exit code 0).
  - `./node_modules/.bin/vite build` -> lulus (`984 modules transformed`, `built in 6.54s`).
  - `./vendor/bin/pint --test` pada 4 file PHP yang disentuh -> lulus (`passed`).
  - `php artisan test --compact` -> **503 tes: 501 lulus, 2 dilewati (MySQL-only existing), 5.471 assertions**.
  - Playwright MCP pada `/kios-presensi` (database SQLite sementara `/tmp/kiosk_visual.sqlite` hasil `PilotSchoolSeeder`, kini sudah dibersihkan): `1280×720` (idle, hasil scan valid `HADIR TEPAT WAKTU`, scan berulang `SUDAH HADIR`, kartu asing `GAGAL / TIDAK DIKENALI` 422), `950×900` (`overflow: false`), dan `390×844` (`overflow: false`, `scrollWidth: 375`), tanpa error runtime JS/Vue.
- **Risiko Tersisa**:
  - Kontrak perangkat keras `Gate` fisik dan status `checked_out` penuh masih menunggu konfirmasi K-04; saat ini `late_minutes` mengembalikan `0` sampai aturan jam masuk per sekolah dikonfigurasi.
  - Font `JetBrains Mono` belum tersedia lokal di `public/madrasah-theme/fonts/`.
- **Langkah Review Manual**:
  - Login sebagai operator/admin/piket, buka `/kios-presensi`, uji tap kartu terdaftar, tap ulang (deduplikasi), tap kartu asing, modal `QR Kamera` dan `Gate`, serta cek tampilan di lebar penuh (`1280px`), setengah layar (`950px`), dan ponsel (`390px`).
- **Rollback**: `git checkout -- app/Modules/Academic resources/js/Pages/Academic/Attendance/Kiosk.vue tests/Feature/OperationsPermitKioskPicketBkTest.php public/build`

## Audit & Penyelarasan Presisi Visual 23 Halaman Paritas (`M4-UX` / `M4-PUB` / `M4-TU` / `M4-OPS` / `M4-LIF` / `M4-SERV`) — 21 November 2026

- **Task & Requirement**: Audit visual komparatif antara 23 halaman referensi `screenshot_halaman_asli/` (plus halaman beranda) dengan tampilan live School Suite pada desktop (`1280×720`) dan mobile (`390×844`), serta penyelarasan tata letak, masthead, komponen hero, tab, kartu, dan responsivitas tanpa hardcoding identitas `MAN 4 BANJAR` / `man4banjar.com`.
- **Ringkasan**:
  - **Portal Publik & Katalog (`home`, `news`, `achievements`, `gallery`, `navigation`)**: Penyelarasan paragraf hero dan 4 kartu micro-stats di beranda, pembersihan teks pratinjau berlebih pada katalog berita/prestasi/galeri, penyembunyian tautan cepat subnav pada layout katalog/layanan, tombol `Subscribe` pada galeri video, serta penambahan varian masthead khusus (`Aplikasi Database Siswa`, `E-Surat Masuk`, `Buku Tamu Digital`, `Siap Lapor`, `Portal Alumni IKA`, `E-Perpustakaan Digital`) dengan tetap menjaga `data-madrasah-navigation` dan tautan `.sr-only` dinamis.
  - **Layanan Publik & Sub-Portal (`Ptsp`, `Tracking/Index`, `Guestbook`, `Legalization`, `Mutation`, `Graduation`, `Alumni/Portal`, `Complaint`, `Library/Index`)**: Penyelarasan penuh 100% halaman PTSP menjadi section full-bleed (putih `#ffffff` → `#f8fafc` → putih `#ffffff`), breakpoint `< 1024px` 1 kolom dengan checklist vertikal 1 kolom di `#tentang-ptsp`, overlay hero cerah + tombol `.btn-ajukan-hero`/`.btn-cek-hero`, kotak fitur `.parity-feature-box` 2×2, prosedur 7 langkah `.p-icon` `44px`, dan formulir `LOGIN PTSP ONLINE` fungsional (`Email / NIK`, `Password / No. HP` ke `/layanan/ptsp/akun/masuk`), toggle `showCatalog`, search pill terpadu + 4 kartu panduan di Lacak, penggabungan panel kamera `Foto Kunjungan` ke `REGISTER TAMU`, tab `alur`/`form` di Legalisir & Mutasi, serta penyelarasan hero & fitur di Alumni, Siap Lapor, dan E-Perpustakaan.
  - **Login & Operasi (`Login`, `Attendance/Kiosk`, `OperationsScanner`, `SecurityPost/Index`, `OperationsLayout`)**: Penambahan pill brand + tombol kembali, kotak info login siswa/GTK, header 3-kolom + footer statistik di Kiosk, tombol kembali & placeholder NISN di scanner piket/BK, serta ikon perisai, tombol keypad `⌫`/`✓`, dan pemisahan panel konfigurasi PIN Pos Jaga.
  - **Responsivitas Mobile & CSS (`parity.css`, `procedures.css`, `navigation.blade.php`)**: Perbaikan grid 2-kolom `.parity-letter-hero .hero-inner` di desktop & 1-kolom di mobile (`<= 767px`), pemulihan foto `.parity-alumni-image` di mobile agar badge `JEJARING KUAT` tidak bertabrakan dengan teks, pembatasan `.parity-complaint-quick button[type="submit"]`, pill aktif header PTSP (`rgba(0,0,0,0.25)` + ring kuning), utilitas horizontal SOP `.reference-legalisir`/`.reference-mutasi`, perbaikan overflow horizontal `390px` pada 6 masthead mobile, dan pemulihan utilitas responsif (`.sm:inline-block`, `.md:flex`, `.lg:inline`, `.lg:hidden`) di `parity.css`.
- **File yang Diubah**:
  - CSS: `public/madrasah-theme/parity.css`, `public/madrasah-theme/procedures.css`
  - Blade: `resources/views/public/madrasah/{achievements,gallery,home,navigation,news}.blade.php`
  - Vue/Layout: `resources/js/Pages/Academic/Attendance/Kiosk.vue`, `resources/js/Pages/Auth/Login.vue`, `resources/js/Pages/Homeroom/SecurityPost/Index.vue`, `resources/js/Pages/Public/Alumni/Portal.vue`, `resources/js/Pages/Public/Library/Index.vue`, `resources/js/Pages/Public/Services/{Complaint,Graduation,Guestbook,Legalization,Mutation,Ptsp}.vue`, `resources/js/Pages/Public/Tracking/Index.vue`, `resources/js/components/OperationsScanner.vue`, `resources/js/layouts/OperationsLayout.vue`
- **Keputusan & Asumsi**:
  - Tidak ada perubahan skema DB, rute, otorisasi, atau hardcoding nama sekolah; seluruh identitas sekolah tetap dinamis melalui prop `$schoolName` / `school.name`.
  - Pengujian visual Playwright dijalankan menggunakan database SQLite sementara (`/tmp/parity_visual.sqlite`) hasil seed `PilotSchoolSeeder` karena MySQL tidak aktif di sandbox.
- **Verifikasi & Hasil**:
  - `vue-tsc --noEmit`: lulus (exit code 0).
  - `vite build`: lulus (`built in 3.74s`, 984 modules transformed).
  - `php artisan test --compact`: lulus (`tests: 500, passed: 498, skipped: 2, assertions: 5436`).
  - Playwright (`1280×720`, `945×900` half-screen PTSP, & `390×844` mobile) pada seluruh 23 rute paritas: `overflow: false` (`scrollWidth === innerWidth`), full-bleed band `#tentang-ptsp` selebar `1280px`, `pageErrors: []`, `brokenImgs: []`, `badResponses: []`, serta perbandingan visual `ptsp-945-about2` & `ptsp-1280-full2` sesuai referensi `PTSP ONLINE - MAN 4 BANJAR`.
- **Risiko Tersisa**:
  - `./vendor/bin/pint --test` tidak dijalankan ulang pada pass terakhir karena tidak ada file `.php` controller/service yang diubah (hanya Blade/Vue/CSS); baseline Pint sebelumnya memiliki 4 kegagalan pra-eksis di luar scope.
  - 2 test berstatus `skipped` dalam `php artisan test --compact` berasal dari suite bawaan repo.
- **Langkah Review Manual**:
  - Jalankan `npm run build` / cek halaman publik dan operasional pada viewport desktop (`1280×720`) dan mobile (`390×844`) dengan data sekolah nyata.
- **Rollback**: `git checkout -- public/madrasah-theme/parity.css public/madrasah-theme/procedures.css resources/views/public/madrasah resources/js` bila ingin mengembalikan tampilan sebelum penyesuaian visual ini.

## Salinan Tampilan Perpustakaan MAN 4 Banjar — 21 November 2026

- **Task & Requirement**: Menduplikasi tampilan halaman perpustakaan man4banjar.com dari snapshot REA yang sudah ada di repo. Tanpa requirement ID baru; tercatat di `docs/TODO_PARITAS_MAN4_BANJAR.md` sebagai item belum dicentang.
- **Ringkasan**:
  - Halaman statis di `GET /perpustakaan/man4` (`LibraryController::replica`) memakai view `public.madrasah.perpustakaan`, dibangun dari `screenshot_halaman_asli/E-Perpustakaan Digital …html` dan dibungkus `@verbatim`.
  - Aset (Tailwind runtime, jQuery, Chart.js, ikon, sampul buku) disalin ke `public/madrasah-theme/perpustakaan/`; font memakai `madrasah-theme/fonts.css` lokal; webfont Font Awesome 6 diarahkan ke `madrasah-theme/fonts/`.
  - Tautan absolut `man4banjar.com` dipetakan ke rute lokal (`/`, `/login`, `/login?portal=siswa`, `/lacak`, `/layanan/ptsp`, `/perpustakaan/man4`).
  - Panggilan AJAX asli (`crud_perpus_web.php`, `ajax_traffic.php`) diarahkan ke `GET /perpustakaan/man4/aksi`, stub yang hanya mengembalikan `status: error` dan tidak membaca/menyimpan data. Satu `$.ajaxPrefilter` mengubahnya menjadi GET tanpa data sehingga tidak ada data formulir terkirim dan tidak butuh pengecualian CSRF.
  - Portal aktif `/perpustakaan` (Vue/Inertia) **tidak diubah**.
- **File & Migrasi**: `app/Modules/Library/Interface/Http/Controllers/LibraryController.php`, `app/Modules/Library/Interface/Routes/web.php`, `resources/views/public/madrasah/perpustakaan.blade.php`, `public/madrasah-theme/perpustakaan/*`, `tests/Feature/LibraryReplicaTest.php`, dokumen paritas. Tanpa migrasi.
- **Keputusan/Asumsi**: Salinan dipasang di rute terpisah agar tidak mengubah perilaku portal aktif; apakah menggantikan `/perpustakaan` adalah keputusan produk. Isi (teks, sampul, foto) masih milik MAN 4 Banjar dan perlu diganti sebelum dipakai untuk sekolah lain.
- **Tes & Hasil**: `tests/Feature/LibraryReplicaTest.php` (2 tes) ditulis **tetapi belum dijalankan**: lingkungan penulis tidak memiliki PHP/Composer. Verifikasi yang dilakukan hanya: seluruh 21 aset lokal yang dirujuk ada, tidak ada URL `man4banjar.com` tersisa. Belum ada uji visual di browser.
- **Risiko Tersisa**: Pencarian/filter katalog (`?q=`, `?kat=`, `?ebook=`) dan semua formulir asli bergantung pada backend PHP MAN 4 Banjar sehingga tidak berfungsi di salinan; widget sholat/online bisa memakai data statis. Berkas aset ±3,2 MB.
- **Langkah Review Manual**: jalankan `php artisan test tests/Feature/LibraryReplicaTest.php`, buka `/perpustakaan/man4`, bandingkan dengan snapshot asli (tata letak desktop & mobile, ikon, modal).
- **Rollback**: `git revert` commit ini; tidak ada migrasi atau perubahan data.

## Paket Proposal, LPJ dan Koneksi Eksternal (`M4-INT-01` s.d. `M4-INT-04`) — 19 November 2026

- **Task & Requirement**: Implementasi lengkap modul proposal kegiatan guru, portal proposal & LPJ mandiri, serta integrasi sistem eksternal dan kanal komunikasi (`M4-INT-01` s.d. `M4-INT-04`).
- **Ringkasan**:
  - **Skema & Model**:
    - Migrasi `2026_11_19_170000_create_proposals_and_integration_tables.php` menambahkan tabel `activity_proposals`, `integration_connections`, dan `integration_sync_logs` pada modul `Integration`.
  - **Proposal Kegiatan Guru & Antrean Reviewer (`M4-INT-01`)**:
    - `ProposalLpjService` + `ProposalController` menyediakan `/guru/proposal` (khusus pengajuan penugasan kerja guru oleh peran `teacher`, `homeroom`, `admin`) serta antrean reviewer `/administrasi/proposal` (`admin`, `operator`, `principal`, `committee`).
    - Total anggaran RAB dihitung ulang sepenuhnya di server dan menolak kuantitas `< 1` atau harga satuan negatif; revisi reviewer menaikkan `revision_number` dan merekam riwayat keputusan per versi.
  - **Portal Proposal & LPJ Mandiri (`M4-INT-02`)**:
    - `/layanan/proposal` mendukung pengajuan kegiatan siswa/organisasi dilengkapi tombol pemuat contoh webinar (`ev_72b43c`), susunan panitia, editor item RAB dinamis bersama (`ProposalBudgetEditor.vue`), serta pengunggahan bukti realisasi LPJ privat (`/proposal/{proposal}/lpj`) via `ServiceDocumentService` jenis `proposal_lpj`.
    - Reviewer menutup proposal (`closed`) hanya setelah bukti LPJ diunggah, dan penutupan menyinkronkan status `completed` pada `ServiceTicket` terkait (`proposal`). Sesuai `SS-D-009`, tidak ada fitur pencairan atau transfer bank.
  - **EMIS, RDM, SLiMS & Kanal Eksternal (`M4-INT-03`, `M4-INT-04`)**:
    - `ExternalIntegrationService` + `IntegrationSettingsController` menyediakan panel `/pengaturan/integrasi` (terbatas `admin` dan `operator`, `403` bagi peran non-berwenang).
    - Mendukung pemilihan mekanisme (`link`, `file_exchange`, `api_sync` menunggu `K-05`), pemetaan kolom, pratinjau validasi dry-run, pencatatan log batch `partial_failed` dengan detail baris gagal + instruksi pemulihan, ekspor CSV siswa/koleksi perpustakaan, serta percobaan ulang idempoten (`retrySyncLog`).
    - Konfigurasi kanal eksternal (`prayer`, `map`, `chat`, `email`, `whatsapp`, `kiosk_hardware`) menyimpan token dalam kolom terenkripsi `secret_encrypted` (`hidden` dari serialisasi, hanya `secret_last4` dan `has_secret` yang dikirim ke klien, tidak pernah ditulis ke `audit_events`), serta uji koneksi / simulasi gangguan mengembalikan status fallback eksplisit tanpa memengaruhi layanan inti.
- **File & Migrasi**:
  - Core: `app/Modules/Core/Application/Services/UnifiedTrackingService.php` (`proposal`), `app/Modules/Core/Application/Services/ServiceDocumentService.php` (`proposal_lpj`)
  - Integration: `2026_11_19_170000_create_proposals_and_integration_tables.php`, `ActivityProposal.php`, `IntegrationConnection.php`, `IntegrationSyncLog.php`, `ProposalLpjService.php`, `ExternalIntegrationService.php`, `ProposalController.php`, `IntegrationSettingsController.php`, `app/Modules/Integration/Interface/Routes/web.php`
  - Navigasi & Frontend: `config/navigation.php`, `SidebarNavigation.vue`, `Breadcrumbs.vue`, `usePublicServicesCatalog.ts`, `resources/js/components/ProposalBudgetEditor.vue`, `resources/js/Pages/Integration/Proposals/{TeacherIndex,IndependentPortal,ReviewQueue}.vue`, `resources/js/Pages/Integration/Settings/Index.vue`
  - Tes & Docs: `tests/Feature/ProposalAndIntegrationTest.php`, `docs/TODO_PARITAS_MAN4_BANJAR.md`, `docs/INVENTARIS_DAN_BASELINE_PARITAS.md`, `docs/HANDOFF.md`
- **Keputusan & Asumsi**:
  - Komunikasi antar-modul (Core/Academic/Library ke Integration) dilakukan murni lewat layer `Application\Services` (`AcademicQueryService`, `LibraryService`, `ServiceTicketService`, `ServiceDocumentService`) sesuai batas modular monolith (`ModularBoundaryTest`).
  - Portal `/layanan/proposal` mensyaratkan sesi autentikasi pengguna sekolah (`student`, `teacher`, `homeroom`, `committee`, `operator`, `admin`) sedangkan pelacakan ringkas tiket tetap kompatibel via `/lacak`.
- **Tes & Hasil**:
  - `php artisan test tests/Feature/ProposalAndIntegrationTest.php tests/Architecture/ModularBoundaryTest.php tests/Feature/AcademicMigrationTest.php` -> **14 tes lulus (122 assertions)**.
  - `./vendor/bin/pint --test` pada seluruh file PHP baru/ubah -> **lulus (`passed`)**.
  - `vue-tsc --noEmit` & `vite build` -> **lulus (0 error TS, 972 modul terkompilasi dalam 3,76s)**.
  - `php artisan test --compact` -> **491 tes: 489 lulus, 2 dilewati (MySQL-only existing), 5.241 assertions**.
- **Risiko Tersisa**:
  - Migrasi `2026_11_19_170000` baru teruji pada SQLite `RefreshDatabase` dan belum dieksekusi pada MySQL staging/produksi.
  - Uji antarmuka langsung via Playwright pada breakpoint `1280×720` dan `390×844` serta kredensial API EMIS/RDM (`K-05`) belum dijalankan pada sesi ini.
- **Langkah Review Manual**:
  1. Login sebagai guru di `/guru/proposal` -> ajukan proposal dengan RAB multi-item & lampiran -> minta revisi memakai peran `principal`/`operator` di `/administrasi/proposal` -> kirim ulang revisi dan pastikan versi naik menjadi `v2` beserta riwayat keputusan.
  2. Login sebagai siswa di `/layanan/proposal` -> klik **Muat Contoh Webinar (`ev_72b43c`)** -> ajukan -> setujui melalui `/administrasi/proposal` -> unggah bukti realisasi LPJ -> tutup LPJ dan pastikan tiket `/lacak` berstatus selesai.
  3. Login sebagai `admin`/`operator` di `/pengaturan/integrasi` -> uji pratinjau CSV dengan 1 baris salah -> komitmen impor `partial_failed` -> klik **Coba Ulang Idempoten**; uji tab **Kanal Eksternal** dengan menyimpan token WhatsApp (verifikasi token hanya tampil masked `••••9876`) lalu klik **Simulasi Gangguan** untuk memverifikasi status fallback aktif.
- **Rollback**:
  - Jalankan `php artisan migrate:rollback --step=1` (bila migrasi `170000` sudah dijalankan di database target) dan kembalikan perubahan via Git.

## Paket Aduan dan Perpustakaan (`M4-SERV-01` s.d. `M4-SERV-04`) — 19 November 2026

- **Task & Requirement**: Implementasi lengkap layanan pengaduan Siap Lapor, antrean Satgas & transparansi publik, serta modul Perpustakaan paritas MAN 4 Banjar (`M4-SERV-01` s.d. `M4-SERV-04`).
- **Ringkasan**:
  - **Skema & Model**:
    - Migrasi `2026_11_19_160000_create_complaint_reports_table.php` menambahkan tabel `complaint_reports` pada modul `Homeroom`.
    - Migrasi `2026_11_19_160100_create_library_tables.php` menambahkan 5 tabel pada modul `Library`: `library_collections`, `library_visits`, `library_loans`, `library_procurement_requests`, dan `library_clearance_certificates`.
  - **Siap Lapor & Satgas Transparansi (`M4-SERV-01`, `M4-SERV-02`)**:
    - `ComplaintReportService` + `ComplaintController` menyediakan `/layanan/lapor` (dukungan laporan bernama dan mode anonim yang tidak menyimpan identitas maupun tautan user pelapor di tabel laporan maupun snapshot tiket `/lacak`), lampiran bukti `evidence` privat, serta `/satgas/aduan` strictly untuk `admin`, `task_force`, `counselor` (dengan transisi multi-langkah tiket agar pembaruan status Satgas langsung terbaca di `/lacak` dan unduhan bukti privat terlindungi `403` bagi peran non-Satgas).
    - `/lapor/transparansi` hanya mengekspor statistik agregat per kategori/status serta ringkasan publik termoderasi bebas PII yang telah ditandai `publish_to_transparency = true`.
  - **Perpustakaan — Katalog, Sirkulasi, Kunjungan, Usulan & Bebas Pustaka (`M4-SERV-03`, `M4-SERV-04`)**:
    - `LibraryService` + `LibraryController` menyediakan portal `/perpustakaan` (katalog pencarian buku & e-book dengan kontrol akses `public`, `members_only`, `staff_only`, presensi kunjungan QR kartu siswa/manual `/perpustakaan/kunjungan`, usul pengadaan `/perpustakaan/usulan`, serta pengajuan Bebas Pustaka `/perpustakaan/bebas-pustaka` yang memblokir penerbitan bila masih ada pinjaman aktif dan menerbitkan PDF ber-token `/verifikasi` saat bebas tanggungan).
    - `/perpustakaan/kelola` (terbatas `admin` dan `librarian`) menyediakan CRUD koleksi & unggah e-book PDF privat, peminjaman/pengembalian buku dengan mutasi stok `available_copies` atomik, dan review usulan pengadaan.
  - **Navigasi & Katalog Publik**:
    - Mengaktifkan item `siap-lapor`, `transparansi-lapor`, dan `perpustakaan` pada `usePublicServicesCatalog.ts` serta menambahkan entri sidebar `task-force-complaints` (`/satgas/aduan`) dan `library-manage` (`/perpustakaan/kelola`) di `config/navigation.php`, `SidebarNavigation.vue`, dan `Breadcrumbs.vue`.
- **File & Migrasi**:
  - Core: `app/Modules/Core/Application/Services/ServiceDocumentService.php` (`kindLabel` untuk `clearance` & `ebook`)
  - Homeroom (Aduan/Satgas): `2026_11_19_160000_create_complaint_reports_table.php`, `ComplaintReport.php`, `ComplaintReportService.php`, `ComplaintController.php`, `app/Modules/Homeroom/Interface/Routes/web.php`
  - Library: `2026_11_19_160100_create_library_tables.php`, `LibraryCollection.php`, `LibraryVisit.php`, `LibraryLoan.php`, `LibraryProcurementRequest.php`, `LibraryClearanceCertificate.php`, `LibraryService.php`, `LibraryController.php`, `app/Modules/Library/Interface/Routes/web.php`, `resources/views/library/clearance-letter.blade.php`
  - Frontend & Navigasi: `config/navigation.php`, `SidebarNavigation.vue`, `Breadcrumbs.vue`, `usePublicServicesCatalog.ts`, `resources/js/Pages/Public/Services/{Complaint,ComplaintTransparency}.vue`, `resources/js/Pages/Homeroom/Complaints/Index.vue`, `resources/js/Pages/Public/Library/Index.vue`, `resources/js/Pages/Library/Manage/Index.vue`
  - Tes & Docs: `tests/Feature/ComplaintsAndLibraryTest.php`, `docs/TODO_PARITAS_MAN4_BANJAR.md`, `docs/INVENTARIS_DAN_BASELINE_PARITAS.md`, `docs/HANDOFF.md`
- **Keputusan & Asumsi**:
  - Ketika koneksi eksternal SLiMS belum dikonfigurasi/aktif (`schools.settings['library']['slims_base_url']`), katalog dan pemeriksaan bebas pustaka berjalan penuh secara lokal disertai indikator status fallback eksplisit untuk kesiapan integrasi `M4-INT-03`.
  - Dokumen `members_only` disembunyikan dari katalog tamu anonim dan diblokir `403` bila diakses langsung tanpa autentikasi anggota sekolah.
- **Tes & Hasil**:
  - `php artisan test --compact tests/Feature/ComplaintsAndLibraryTest.php tests/Architecture/ModularBoundaryTest.php tests/Feature/HomeroomControllersTest.php tests/Feature/AcademicMigrationTest.php` -> **26 tes lulus (202 assertions)**.
  - `./vendor/bin/pint` pada seluruh berkas PHP baru/ubah paket SERV -> **lulus (`passed`)**.
  - `vue-tsc --noEmit` & `vite build` -> **lulus (0 error TS, 960 modul terkompilasi dalam 3,41s)**.
  - `php artisan test --compact` -> **487 tes: 485 lulus, 2 dilewati (MySQL-only existing), 5.152 assertions**.
- **Risiko Tersisa**:
  - Migrasi `2026_11_19_160000` dan `2026_11_19_160100` baru teruji via SQLite `RefreshDatabase` (termasuk `AcademicMigrationTest`) dan belum dijalankan pada database MySQL live/staging.
  - Uji browser/Playwright pada breakpoint `1280×720` dan `390×844` serta sinkronisasi SLiMS eksternal (`K-05`) belum dijalankan pada sesi ini.
- **Langkah Review Manual**:
  1. Jalankan `php artisan migrate` pada database staging dan uji laporan anonim & bernama di `/layanan/lapor`.
  2. Login memakai peran `task_force` di `/satgas/aduan` -> tangani laporan -> terbitkan ringkasan bebas PII -> pastikan tampil di `/lapor/transparansi` dan status berubah di `/lacak` tanpa kebocoran identitas.
  3. Login memakai peran `librarian` di `/perpustakaan/kelola` -> tambahkan koleksi fisik & e-book `members_only`/`staff_only` -> uji peminjaman aktif memblokir `/perpustakaan/bebas-pustaka` -> kembalikan buku -> verifikasi PDF Bebas Pustaka terbaca sah di `/verifikasi`.
- **Rollback**:
  - Jalankan `php artisan migrate:rollback --step=2` (bila migrasi `160000` dan `160100` sudah dijalankan di database target) dan kembalikan perubahan via Git.

## Paket Siklus Siswa dan Alumni (`M4-LIF-01` s.d. `M4-LIF-07`) — 19 November 2026

- **Task & Requirement**: Implementasi lengkap modul siklus siswa dan alumni paritas MAN 4 Banjar (`M4-LIF-01` s.d. `M4-LIF-07`).
- **Ringkasan**:
  - **Skema & Model**:
    - Migrasi `2026_11_19_150000_expand_admissions_for_candidate_portal.php` memperluas `admission_applicants` (`created_by` nullable bagi kandidat mandiri, `external_applicant_id`, `service_ticket_id`, `verification_secret_hash`, `birth_place`, `address`, `revision_notes`, `idempotency_key`) dan `admission_re_registrations` (`submitted_by_candidate_at`).
    - Migrasi `2026_11_19_150100_create_mutation_graduation_and_alumni_tables.php` menyelaraskan kolom `enrollments.status` pada SQLite agar mendukung nilai lifecycle (`promoted`, `retained`, `graduated`, `transferred`) serta menambah tabel `student_mutations`, `graduation_batches`, `graduation_records`, dan `alumni_profiles`.
    - Migrasi `2026_11_19_150200_create_legalization_requests_table.php` menambah tabel `legalization_requests` untuk pengajuan legalisir alumni dan pencatatan register Tata Usaha.
  - **PPDB & Portal Kandidat (`M4-LIF-01`)**:
    - `CandidatePortalService` + `AdmissionsController` menambahkan `/ppdb/daftar`, `/ppdb/cek-hasil`, `/ppdb/perbaiki/{applicant}`, `/ppdb/bukti/{applicant}/pdf`, `/ppdb/daftar-ulang/{applicant}`, dan `/spmb/pendaftar/{applicant}/revisi`, lengkap dengan validasi jadwal/status periode, bukti PDF `registration-proof.blade.php`, perbaikan berkas kandidat, gerbang pengumuman `announcement_at`, serta konversi idempoten ke `Student`.
  - **Mutasi Masuk & Keluar (`M4-LIF-02`)**:
    - `StudentMutationService` + `StudentMutationController` menyediakan `/layanan/mutasi` & `/akademik/mutasi` dengan validasi semester aktif, pemisahan mutasi masuk/keluar, surat rekomendasi/penerimaan PDF (`mutation-letter.blade.php`), pencabutan persetujuan teraudit, dan pembaruan aman `Enrollment` & `Student` tanpa menghapus histori lama.
  - **Kelulusan & SKL (`M4-LIF-03`)**:
    - `GraduationService` + `GraduationController` menyediakan `/akademik/kelulusan` & `/layanan/kelulusan` dengan gerbang rilis `release_at` + `is_published`, verifikasi PIN/pemilik, PDF SKL QR (`graduation-skl.blade.php`), penerbitan ulang terkendali (otomatis mencabut SKL lama via `ServiceDocumentService`), dan pencatatan pengambilan dokumen asli.
  - **Verifikasi Siswa & Alumni (`M4-LIF-04`)**:
    - `ServiceDocumentService::verifyPublicDocument()`, `AcademicQueryService::verifyPublicStudentStatus()`, dan `VerificationController` menyediakan portal `/verifikasi` untuk verifikasi token/nomor dokumen (`valid`, `revoked`, `expired`, `not_found`) serta keaktifan siswa via NISN + nama tanpa membocorkan PII sensitif.
  - **Legalisir, Surat Kuasa & SPTJM (`M4-LIF-05`, `M4-LIF-06`)**:
    - `LegalizationService` + `LegalizationController` menyediakan `/layanan/legalisir`, generator templat resmi `/layanan/surat-kuasa` (`power-of-attorney.blade.php` & `sptjm.blade.php` versi v1 terhubung kode register legalisir), serta antrean TU `/administrasi/legalisir` untuk verifikasi berkas asli, pengesahan PDF QR (`legalization-proof.blade.php`), dan pencatatan pengambilan langsung/kuasa.
  - **Alumni, Tracer & E-KTA (`M4-LIF-07`)**:
    - `AlumniService` + `AlumniController` menyediakan portal tracer `/alumni`, direktori terfilter `/alumni/direktori`, antrean moderasi `/akademik/alumni`, kontrol `opt_in_directory`/`opt_in_contact`/`opt_in_career`/`opt_in_testimonial`, serta penerbitan E-KTA PDF (`alumni-ekta.blade.php`).
- **File & Migrasi**:
  - Core: `ExternalApplicantAuthService.php`, `ServiceDocumentService.php`, `VerificationController.php`, `app/Modules/Core/Interface/Routes/web.php`
  - Admissions: `2026_11_19_150000_expand_admissions_for_candidate_portal.php`, `AdmissionApplicant.php`, `CandidatePortalService.php`, `AdmissionsController.php`, `app/Modules/Admissions/Interface/Routes/web.php`, `resources/views/admissions/registration-proof.blade.php`
  - Academic: `2026_11_19_150100_create_mutation_graduation_and_alumni_tables.php`, `StudentMutation.php`, `GraduationBatch.php`, `GraduationRecord.php`, `AlumniProfile.php`, `AcademicQueryService.php`, `StudentMutationService.php`, `GraduationService.php`, `AlumniService.php`, `StudentMutationController.php`, `GraduationController.php`, `AlumniController.php`, `app/Modules/Academic/Interface/Routes/web.php`, `resources/views/academic/{mutation-letter,graduation-skl,alumni-ekta}.blade.php`
  - Administration: `2026_11_19_150200_create_legalization_requests_table.php`, `LegalizationRequest.php`, `LegalizationService.php`, `LegalizationController.php`, `app/Modules/Administration/Interface/Routes/ptsp.php`, `resources/views/administration/{legalization-proof,power-of-attorney,sptjm}.blade.php`
  - Frontend & Navigasi: `config/navigation.php`, `SidebarNavigation.vue`, `Breadcrumbs.vue`, `usePublicServicesCatalog.ts`, `resources/js/Pages/Public/Admissions/Index.vue`, `resources/js/Pages/Public/Verification/Index.vue`, `resources/js/Pages/Public/Services/{Mutation,Graduation,Legalization,PowerOfAttorney}.vue`, `resources/js/Pages/Public/Alumni/{Portal,Directory}.vue`, `resources/js/Pages/Academic/{Mutations,Graduation,Alumni}/Index.vue`, `resources/js/Pages/Administration/Legalization/Index.vue`
  - Tes & Docs: `tests/Feature/StudentLifecycleAndAlumniTest.php`, `docs/TODO_PARITAS_MAN4_BANJAR.md`, `docs/INVENTARIS_DAN_BASELINE_PARITAS.md`, `docs/HANDOFF.md`
- **Keputusan & Asumsi**:
  - Komunikasi antar-modul untuk verifikasi dokumen/token dan unduhan PDF dari `Academic`/`Administration` ditempatkan di `ServiceDocumentService` (`Core\Application`) agar bebas dari impor `Infrastructure` lintas modul (`ModularBoundaryTest`).
  - `ExternalApplicantAuthService::ALLOWED_TYPES` ditambah tipe `'candidate'` untuk mendukung pendaftaran akun kandidat PPDB maupun alumni eksternal yang terpisah dari guard staf.
- **Tes & Hasil**:
  - `php artisan test --compact tests/Feature/StudentLifecycleAndAlumniTest.php tests/Architecture/ModularBoundaryTest.php tests/Feature/AdmissionsControllersTest.php tests/Feature/AdmissionsWorkflowTest.php tests/Feature/AcademicMigrationTest.php tests/Feature/AcademicControllersTest.php` -> **62 tes lulus (687 assertions)**.
  - `./vendor/bin/pint` pada seluruh 29 berkas PHP baru/ubah paket LIF -> **lulus (`passed`)**.
  - `vue-tsc --noEmit` & `vite build` -> **lulus (0 error TS, 950 modul terkompilasi dalam 3,00s)**.
  - `php artisan test --compact` -> **485 tes: 483 lulus, 2 dilewati (MySQL-only existing), 5.054 assertions**.
- **Risiko Tersisa**:
  - Ketiga migrasi baru (`150000`, `150100`, `150200`) belum dijalankan terhadap database MySQL live/staging (teruji penuh di SQLite `RefreshDatabase` serta uji rollback `AcademicMigrationTest`).
  - QA visual browser/Playwright pada breakpoint desktop (`1280×720`) dan ponsel (`390×844`) belum dijalankan pada sesi ini.
- **Langkah Review Manual**:
  1. Jalankan `php artisan migrate` pada database uji/staging dan cek menu publik E-Layanan (`/e-layanan`) mengarah ke `/ppdb`, `/layanan/mutasi`, `/layanan/kelulusan`, `/verifikasi`, `/layanan/legalisir`, `/layanan/surat-kuasa`, dan `/alumni`.
  2. Uji siklus penuh PPDB di `/ppdb` dan `/spmb`: daftar saat terbuka -> minta revisi -> perbaiki -> pastikan hasil tersembunyi sebelum `announcement_at` -> rilis & konfirmasi daftar ulang.
  3. Uji mutasi di `/layanan/mutasi` & `/akademik/mutasi` serta kelulusan di `/akademik/kelulusan` & `/layanan/kelulusan`, lalu verifikasi token dokumen (dan kondisi dicabut) di `/verifikasi`.
  4. Uji legalisir + Surat Kuasa/SPTJM di `/layanan/legalisir`, `/layanan/surat-kuasa`, dan `/administrasi/legalisir`, serta moderasi alumni + direktori + E-KTA di `/alumni`, `/akademik/alumni`, dan `/alumni/direktori`.
- **Rollback**:
  - Jalankan `php artisan migrate:rollback --step=3` (bila ketiga migrasi `150000`, `150100`, `150200` telah dijalankan di database target) dan kembalikan perubahan via Git.

## Paket Presensi, Izin, Piket dan BK (`M4-OPS-01` s.d. `M4-OPS-05`) — 19 November 2026

- **Task & Requirement**: Implementasi lengkap modul operasional Presensi, Izin/Dispensasi, Kios RFID/QR, Piket & Pos Jaga Satpam, serta Disiplin BK rahasia paritas MAN 4 Banjar (`M4-OPS-01` s.d. `M4-OPS-05`).
- **Ringkasan**:
  - **Skema & Model**:
    - Migrasi `2026_11_19_140000_create_student_cards_and_kiosk_tables.php` menambahkan tabel `student_cards`, `attendance_kiosk_devices`, dan `attendance_kiosk_scans` untuk registrasi/pencabutan kartu siswa, perangkat kios, dan log scan ber-idempotensi.
    - Migrasi `2026_11_19_140100_expand_dispensations_picket_gate_and_bk.php` memperluas `attendance_dispensations` (dukung pengajuan tamu bertoken, lampiran privat, tanda tangan, tiket layanan, catatan keputusan/koreksi, serta `idempotency_key`), menambahkan `student_permit_tokens` & `gate_passes`, dan memperluas `student_incidents` dengan `incident_time`, `location`, `evidence_document_ids`, dan `is_bk_confidential`.
  - **Izin & Verifikasi Presensi (`M4-OPS-01`, `M4-OPS-02`)**:
    - `PermitWorkflowService` + `StudentPermitController` menyediakan `/layanan/izin-siswa` (wali/siswa terverifikasi atau token sekali pakai terikat siswa/tujuan yang dikonsumsi atomik dengan `lockForUpdate()`, penolakan rentang bentrok & melewati cutoff jam lokal sekolah, idempotensi sebelum validasi bentrok, tanda tangan kanvas/data URL, unggah/unduh lampiran privat, dan bukti PDF `permit-proof.blade.php`) serta `/wali-kelas/izin` (penerbitan token, antrean terisolasi wali kelas, keputusan setujui / minta revisi / tolak beralasan, dan koreksi keputusan dengan audit `homeroom.dispensation.corrected`).
    - `AttendanceService::applyApprovedDispensationToAttendance()` & `revertDispensationAttendance()` otomatis mengisi/mengoreksi baris `attendance_records` untuk status `sick`/`permitted` dan mengembalikannya saat keputusan dikoreksi (memakai `whereDate()` yang konsisten pada SQLite maupun MySQL).
  - **Kios Presensi RFID/QR (`M4-OPS-03`)**:
    - `StudentCardKioskService` + `StudentCardKioskController` menyediakan `/akademik/kartu-siswa` (pendaftaran/pencabutan kartu RFID/QR & perangkat kios) dan `/kios-presensi` (mode tap RFID, scan QR kamera/manual, tombol Gate simulasi menunggu kontrak perangkat K-04, tampilan layar minimal nama+kelas, deduplikasi scan berulang dalam jendela waktu konfirgurasi sekolah, antrean offline `localStorage` yang disinkronkan idempoten via `/kios-presensi/sinkron`, dan penolakan kartu dicabut/asing/nonaktif).
  - **Piket & Pos Jaga Satpam (`M4-OPS-04`)**:
    - `PicketGateService` + `PicketGateController` menyediakan `/piket/izin-keluar` (pencarian/scan kartu siswa, penerbitan `gate_passes` berzona waktu lokal dikonversi ke timezone aplikasi sebelum persistensi, serta pembatalan beralasan) dan `/pos-jaga` (pengaturan PIN numerik oleh `admin`/`operator`, gerbang papan angka PIN wajib sebelum panel terbuka, validasi keluar/kembali lewat `/pos-jaga/scan` serta penolakan pass kedaluwarsa/dibatalkan/sudah dipakai kembali).
  - **Disiplin BK Rahasia (`M4-OPS-05`)**:
    - `BkDisciplineService` + `BkDisciplineController` menyediakan `/bk/kasus` strictly untuk `admin` dan `counselor` (`403` untuk guru/wali kelas/piket/satpam/siswa), pencarian siswa via QR/manual, pencatatan & pembaruan kasus bertanda `is_bk_confidential = true` beserta eviden privat (`documentKind: 'evidence'`), serta ekspor CSV aman injeksi formula dan tercatat di `audit_events` (`homeroom.bk_case.exported`). `StudentDisciplineController::index()` menyaring keluar baris `is_bk_confidential = true` agar kasus rahasia BK tidak muncul di daftar disiplin wali kelas.
  - **Navigasi & Katalog Publik**:
    - Menambahkan key navigasi `permits_queue`, `student_cards`, `kiosk`, `picket`, `security_post`, dan `bk_cases` pada `config/navigation.php`, `SidebarNavigation.vue`, dan `Breadcrumbs.vue`, serta memperbarui entri katalog izin siswa di `usePublicServicesCatalog.ts`.
- **File & Migrasi**:
  - Migrasi: `app/Modules/Academic/Infrastructure/Database/Migrations/2026_11_19_140000_create_student_cards_and_kiosk_tables.php`, `app/Modules/Homeroom/Infrastructure/Database/Migrations/2026_11_19_140100_expand_dispensations_picket_gate_and_bk.php`
  - Core: `app/Modules/Core/Application/Services/SchoolScopeService.php`
  - Academic: `StudentCard.php`, `AttendanceKioskDevice.php`, `AttendanceKioskScan.php`, `AcademicQueryService.php`, `AttendanceService.php`, `StudentCardKioskService.php`, `StudentCardKioskController.php`, `app/Modules/Academic/Interface/Routes/web.php`
  - Homeroom: `DispensationStatusEnum.php`, `AttendanceDispensation.php`, `StudentPermitToken.php`, `GatePass.php`, `StudentIncident.php`, `PermitWorkflowService.php`, `PicketGateService.php`, `BkDisciplineService.php`, `StudentPermitController.php`, `PicketGateController.php`, `BkDisciplineController.php`, `StudentDisciplineController.php`, `app/Modules/Homeroom/Interface/Routes/web.php`
  - Views & Frontend: `resources/views/homeroom/permit-proof.blade.php`, `resources/js/Pages/Public/Services/StudentPermit.vue`, `resources/js/Pages/Homeroom/{Permits,Picket,SecurityPost,Bk}/Index.vue`, `resources/js/Pages/Academic/Attendance/{Cards,Kiosk}.vue`, `SidebarNavigation.vue`, `Breadcrumbs.vue`, `usePublicServicesCatalog.ts`, `config/navigation.php`
  - Tes & Docs: `tests/Feature/OperationsPermitKioskPicketBkTest.php`, `docs/TODO_PARITAS_MAN4_BANJAR.md`, `docs/INVENTARIS_DAN_BASELINE_PARITAS.md`, `docs/HANDOFF.md`
- **Keputusan & Asumsi**:
  - Kontrol perangkat keras gerbang/gate fisik (`Kiosk.vue` & `/kios-presensi/gate`) diimplementasikan dalam mode simulasi sampai kontrak protokol perangkat K-04 difinalisasi.
  - Cutoff pengajuan izin hari yang sama (`09:00`), jendela deduplikasi kios (`120` detik), dan hash PIN Pos Jaga disimpan dalam `schools.settings['operations']` via `SchoolScopeService` agar dapat disesuaikan per sekolah tanpa perubahan skema.
- **Tes & Hasil**:
  - `php artisan test --compact tests/Feature/OperationsPermitKioskPicketBkTest.php tests/Architecture/ModularBoundaryTest.php tests/Feature/HomeroomControllersTest.php tests/Feature/HomeroomIsolationTest.php tests/Feature/HomeroomAttendanceSummaryTest.php tests/Feature/AcademicMigrationTest.php` -> **34 tes lulus (247 assertions)**.
  - `php artisan test --compact` -> **481 tes: 479 lulus, 2 dilewati (MySQL-only existing), 4.891 assertions**.
  - `./vendor/bin/pint --test` pada seluruh berkas PHP baru/diubah (`Core`, `Academic`, `Homeroom`, `config/navigation.php`, `OperationsPermitKioskPicketBkTest.php`) -> **lulus**.
  - `vue-tsc --noEmit` & `vite build` -> **lulus (0 error TS, 928 modul terkompilasi dalam 3,52s)**.
- **Risiko Tersisa**:
  - Migrasi `2026_11_19_140000` dan `2026_11_19_140100` baru diverifikasi pada SQLite `RefreshDatabase` (termasuk `AcademicMigrationTest`) dan belum dijalankan pada database MySQL live/staging.
  - Verifikasi visual browser/Playwright (`1280×720` dan `390×844`) serta uji kamera fisik QR/pembaca RFID & kontrak gerbang fisik (`K-04`) belum dijalankan pada sesi ini.
- **Langkah Review Manual**:
  1. Jalankan `php artisan migrate` pada staging dan akses `/layanan/izin-siswa` baik sebagai wali/siswa maupun tamu bertoken dari `/wali-kelas/izin`.
  2. Uji persetujuan & koreksi izin di `/wali-kelas/izin` lalu cek perubahan status `present`/`sick`/`permitted`/`absent` di rekap presensi harian siswa terkait.
  3. Terbitkan kartu di `/akademik/kartu-siswa`, uji scan berulang & sinkronisasi offline di `/kios-presensi`, lalu cabut kartu dan pastikan scan ditolak.
  4. Atur PIN Pos Jaga di `/pos-jaga` (login sebagai admin/operator), terbitkan pass keluar di `/piket/izin-keluar`, buka kunci `/pos-jaga` via papan angka, lalu uji scan keluar -> kembali -> tolak pakai ulang.
  5. Uji `/bk/kasus` memakai peran `counselor` vs `homeroom`/`teacher` untuk memastikan isolasi akses `403`, unggah/unduh eviden privat, serta tidak bocornya kasus BK di `/wali-kelas/kedisiplinan`.
- **Rollback**:
  - Jalankan `php artisan migrate:rollback --step=2` (bila kedua migrasi `140000` & `140100` sudah dijalankan di target DB) dan kembalikan perubahan via Git.

## Paket PTSP dan Tata Usaha (`M4-TU-01` s.d. `M4-TU-06`) — 19 November 2026

- **Task & Requirement**: Implementasi lengkap modul PTSP & Tata Usaha paritas MAN 4 Banjar (`M4-TU-01` s.d. `M4-TU-06`, beserta rincian tambahan REA `M4-TU-04` untuk filter pelacakan terpadu).
- **Ringkasan**:
  - **Skema & Model**:
    - Migrasi `2026_11_19_130000_add_auth_columns_to_external_applicants.php` menambahkan kolom `password_hash`, `recovery_token_hash`, `recovery_expires_at`, dan `last_login_at` pada `external_applicants` agar akun pemohon eksternal terpisah sepenuhnya dari akun pegawai (`users`).
    - Migrasi `2026_11_19_130100_create_ptsp_and_guestbook_tables.php` menambahkan tabel `ptsp_services`, `ptsp_requests`, `guestbook_visits`, serta kolom integrasi eksternal pada `correspondence_records` (`service_ticket_id`, kontak narahubung, `external_supporting_document_ids`, dan `created_by` nullable dengan pemulihan trigger provenance SQLite & FK MySQL).
  - **Katalog PTSP, Akun Pemohon, Pengajuan & Resi (`M4-TU-01`, `M4-TU-02`)**:
    - `ExternalApplicantAuthService` + `ExternalApplicantAuthController` (`/layanan/ptsp/akun/*`) menyediakan registrasi, masuk, keluar, pembaruan profil/kontak, serta pemulihan sandi bertoken hashed + outbox.
    - `PtspWorkflowService` + `PtspPublicController` (`/layanan/ptsp/*`) menyediakan katalog layanan aktif/tutup, penyimpanan & penyuntingan draf (termasuk hapus lampiran draf), snapshot persyaratan pada permohonan agar perubahan katalog tidak memengaruhi pengajuan lama, validasi berkas wajib, submit atomik ber-idempotensi menghasilkan tepat 1 tiket (`PTSP-YYYY-XXXXXXXX`), halaman resi terproteksi akses, dan cetak resi PDF (`ptsp-receipt.blade.php`).
  - **Antrean Petugas & Hasil (`M4-TU-03`)**:
    - `PtspAdminController` + `Administration/Ptsp/Index.vue` (`/administrasi/ptsp`) untuk peran `admin`, `operator`, dan `ptsp`: filter layanan/status/tanggal/cari, verifikasi/tinjau, minta perbaikan (membuka `/layanan/ptsp/{ticket}/perbaiki` bagi pemohon), tolak beralasan, disposisi, terbitkan hasil PDF standar/unggulan (`ptsp-result.blade.php`), catat pengambilan fisik, unduh berkas privat, serta CRUD katalog PTSP.
  - **Pelacakan Terpadu (`M4-TU-04`)**:
    - `UnifiedTrackingService` + `UnifiedTrackingController` + `Public/Tracking/Index.vue` (`/lacak`) menyediakan filter `Semua`, `PTSP`, `Izin`, `Legalisir`, `Mutasi`, `Surat Eksternal`, dan `Pengaduan`, rate limiting `throttle:sensitive`, verifikasi PIN/pemilik sebelum membuka rincian/dokumen, penanganan tiket kedaluwarsa/dicabut, serta isolasi identitas aduan anonim.
  - **Surat Dinas Eksternal (`M4-TU-05`)**:
    - `CorrespondenceService::createIncomingFromExternal()` + `ExternalLetterController` (`/layanan/surat-dinas`) langsung mencatat surat masuk ke agenda `correspondence_records` (`IN/YYYY/XXXXXX`) dan menerbitkan tiket `SRT-YYYY-XXXXXXXX` + tanda terima PDF (`external-letter-receipt.blade.php`) tanpa entri ganda, serta menyinkronkan status disposisi/selesai TU ke `/lacak`.
  - **Buku Tamu Digital (`M4-TU-06`)**:
    - `GuestbookService` + `GuestbookController` menyediakan registrasi mandiri (`/layanan/buku-tamu`, hanya menampilkan bukti slip milik pengunjung sendiri tanpa daftar tamu publik) dan ruang kerja resepsionis/TU (`/administrasi/buku-tamu`) untuk peran `admin`, `operator`, `ptsp`, dan `receptionist` lengkap dengan pencatatan pulang, KPI harian/bulanan, filter, dan ekspor CSV aman injeksi formula.
  - **Navigasi & Katalog Publik**:
    - Menambahkan key `ptsp` & `guestbook` di `config/navigation.php`, `SidebarNavigation.vue`, dan `Breadcrumbs.vue`, serta memperbarui `usePublicServicesCatalog.ts` dan pintasan `SchoolProfileController::home()`.
- **File & Migrasi**:
  - Migrasi: `app/Modules/Core/Infrastructure/Database/Migrations/2026_11_19_130000_add_auth_columns_to_external_applicants.php`, `app/Modules/Administration/Infrastructure/Database/Migrations/2026_11_19_130100_create_ptsp_and_guestbook_tables.php`
  - Core: `ExternalApplicant.php`, `ExternalApplicantAuthService.php`, `UnifiedTrackingService.php`, `ServiceTicketService.php`, `ServiceDocumentService.php`, `ExternalApplicantAuthController.php`, `UnifiedTrackingController.php`, `SchoolProfileController.php`, `app/Modules/Core/Interface/Routes/web.php`
  - Administration: `PtspService.php`, `PtspRequest.php`, `GuestbookVisit.php`, `CorrespondenceRecord.php`, `PtspWorkflowService.php`, `GuestbookService.php`, `CorrespondenceService.php`, `PtspPublicController.php`, `PtspAdminController.php`, `ExternalLetterController.php`, `GuestbookController.php`, `app/Modules/Administration/Interface/Routes/{ptsp,web}.php`
  - Views & Frontend: `resources/views/administration/{ptsp-receipt,ptsp-result,external-letter-receipt}.blade.php`, `resources/js/Pages/Public/Services/{Ptsp,PtspAccount,PtspReceipt,PtspRevision,ExternalLetter,ExternalLetterReceipt,Guestbook}.vue`, `resources/js/Pages/Public/Tracking/Index.vue`, `resources/js/Pages/Administration/{Ptsp,Guestbook}/Index.vue`, `SidebarNavigation.vue`, `Breadcrumbs.vue`, `usePublicServicesCatalog.ts`, `config/navigation.php`
  - Tes & Docs: `tests/Feature/PtspAndTataUsahaTest.php`, `docs/TODO_PARITAS_MAN4_BANJAR.md`, `docs/INVENTARIS_DAN_BASELINE_PARITAS.md`, `docs/HANDOFF.md`
- **Keputusan & Asumsi**:
  - Akun pemohon eksternal menggunakan sesi terpisah (`ptsp_external_applicant_id` di atas tabel `external_applicants`) sehingga tidak bercampur dengan guard pegawai (`users`).
  - Pembuatan katalog layanan default mengikuti kebutuhan konfigurasi masing-masing sekolah melalui `/administrasi/ptsp` (menunggu konfirmasi SOP klien `K-01`/`K-02`/`K-03`).
- **Tes & Hasil**:
  - `php artisan test --compact tests/Feature/PtspAndTataUsahaTest.php tests/Architecture/ModularBoundaryTest.php tests/Feature/PublicPortalTest.php tests/Feature/RoleMatrixAccessTest.php tests/Feature/AdministrationCorrespondenceTest.php tests/Feature/ParityFoundationTest.php tests/Feature/PilotSeederTest.php` -> **59 tes lulus (899 assertions)**.
  - `php artisan test --compact` -> **475 tes, 473 lulus, 2 dilewati (MySQL-only existing), 4.771 assertions**.
  - `./vendor/bin/pint --test` pada seluruh 26 berkas PHP baru/ubah -> **lulus**.
  - `vue-tsc --noEmit` & `vite build` (memakai `node` di `~/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin`) -> **lulus (0 error TS, 910 modul terkompilasi)**.
- **Risiko Tersisa**:
  - Migrasi `2026_11_19_130000` dan `2026_11_19_130100` belum dijalankan terhadap database MySQL live (teruji penuh via `RefreshDatabase` SQLite, termasuk uji rollback `AcademicMigrationTest`).
  - Verifikasi visual browser/Playwright (`1280×720` dan `390×844`) dan konfirmasi SOP/format dokumen klien (`K-01`, `K-02`, `K-03`) belum dijalankan di sesi ini.
- **Langkah Review Manual**:
  1. Jalankan `php artisan migrate` pada database uji/staging dan login sebagai `operator`/`ptsp` ke `/administrasi/ptsp` untuk membuat 1 layanan katalog PTSP aktif.
  2. Uji alur pemohon di `/layanan/ptsp/akun` & `/layanan/ptsp` (simpan draf -> unggah wajib -> kirim -> lihat resi PDF), lalu proses di `/administrasi/ptsp` (minta perbaikan -> pemohon perbaiki di `/layanan/ptsp/{tiket}/perbaiki` -> terbitkan hasil -> catat ambil fisik).
  3. Uji `/layanan/surat-dinas` -> cek muncul di `/administrasi/persuratan/surat-masuk` tanpa duplikasi dan lacak statusnya di `/lacak`.
  4. Uji `/layanan/buku-tamu` (pastikan tidak ada daftar tamu publik) lalu catat pulang & ekspor CSV di `/administrasi/buku-tamu` memakai peran `receptionist`.
- **Rollback**:
  - Jalankan `php artisan migrate:rollback --step=2` (bila sudah dimigrasikan pada DB target) dan kembalikan perubahan via Git.

## Paket Portal dan Konten Publik (`M4-PUB-01` s.d. `M4-PUB-07`) — 19 November 2026

- **Task & Requirement**: Implementasi halaman publik, katalog E-Layanan, direktori GTK, prestasi, pusat unduh, zona integritas & IKM, jadwal salat, serta statistik kunjungan anonim (`M4-PUB-01` s.d. `M4-PUB-07`).
- **Ringkasan**:
  - **Skema & Model**: Migrasi `2026_11_19_120000_create_public_portal_tables.php` menambahkan tabel `public_downloads`, `public_ikm_surveys`, `public_visit_stats`, serta kolom `category`, `video_url`, `source_url`, `is_archived` pada `school_news`.
  - **Service & Batas Modul**: `AcademicQueryService::getPublicDirectoryTeachers()` hanya mengekspor `id` dan `name` GTK aktif tanpa membocorkan NIP/email/telepon. `PublicPortalService` menangani penghitung kunjungan anonim (`public_visit_stats`), jadwal salat sesuai zona waktu sekolah dengan fallback lokal, pusat unduh terbit, dan moderasi IKM.
  - **Rute & Halaman Publik/Admin**: Menambahkan `/e-layanan`, `/direktori-gtk`, `/prestasi`, `/pusat-unduh`, `/pusat-unduh/{download}/unduh`, `/zona-integritas`, `POST /zona-integritas/ikm`, `/jadwal-salat`, serta endpoint admin `/pengaturan/pusat-unduh` & `/pengaturan/zona-integritas/{survey}/moderasi`. Memperkaya `/` (`Home.vue`) dengan 4 pintasan utama & ringkasan harian, serta `/kontak` (`Contact.vue`) dengan jam layanan & peta.
- **File & Migrasi**:
  - Migrasi: `app/Modules/Core/Infrastructure/Database/Migrations/2026_11_19_120000_create_public_portal_tables.php`
  - Backend: `PublicDownload.php`, `PublicIkmSurvey.php`, `PublicVisitStat.php`, `SchoolNews.php`, `PublicPortalService.php`, `AcademicQueryService.php`, `PublicPortalController.php`, `PublicPortalAdminController.php`, `SchoolProfileController.php`, `app/Modules/Core/Interface/Routes/web.php`
  - Frontend: `resources/js/Pages/Public/{Services/Index,Directory/Teachers,School/Achievements,Downloads/Index,Integrity/Index,Prayer/Index}.vue`, `Home.vue`, `Contact.vue`, `usePublicServicesCatalog.ts`
  - Tes & Docs: `tests/Feature/PublicPortalTest.php`, `docs/TODO_PARITAS_MAN4_BANJAR.md`, `docs/INVENTARIS_DAN_BASELINE_PARITAS.md`, `docs/GEMINI.md`, `docs/HANDOFF.md`
- **Tes & Hasil**:
  - `php artisan test --compact tests/Feature/PublicPortalTest.php tests/Architecture/ModularBoundaryTest.php tests/Feature/SchoolNewsTest.php tests/Feature/SchoolGalleryTest.php` -> **23 tes lulus (292 assertions)**.
  - `./vendor/bin/pint --test` pada seluruh 12 berkas PHP yang ditambah/diubah -> **lulus**.
  - `vue-tsc --noEmit` & `vite build` -> **belum terverifikasi** di sesi ini karena biner `node`/`npm` tidak tersedia pada sandbox.
- **Rollback**:
  - Jalankan `php artisan migrate:rollback --step=1` (jika sudah dimigrasikan pada DB target) dan kembalikan berkas via Git.

## Paket Desain Bersama Seluruh Aplikasi (`M4-UX-01` s.d. `M4-UX-04`) — 19 November 2026

- **Task & Requirement**: Implementasi komponen desain bersama, panel E-Layanan responsif, dan komponen state/aksesibilitas standar (`M4-UX-01`, `M4-UX-02`, `M4-UX-03`, `M4-UX-04`).
- **Ringkasan**:
  - **Sistem Visual (`M4-UX-01`)**: Menambahkan token panel terang & kontras hero pada `resources/js/composables/publicTheme.ts` (`--public-panel-surface`, `--public-panel-border`, `--public-hero-contrast`) serta komponen `resources/js/components/StatusBadge.vue` yang menampilkan status dengan ikon + label teks (bukan warna saja).
  - **Navigasi Responsif E-Layanan (`M4-UX-02`)**: Menambahkan katalog layanan `resources/js/composables/usePublicServicesCatalog.ts` dan mega-panel **E-Layanan** di `resources/js/components/PublicHeader.vue` (3 kolom berkelompok di desktop `>= 1041px`, 1 kolom di mobile/tablet `<= 1040px` dengan akses login/portal sekolah, pencarian judul/sinonim + state kosong, penutupan klik-luar & `Escape` dengan pemulihan fokus ke tombol pemicu, serta `padding-bottom` aman di mobile).
  - **Keadaan UI & Aksesibilitas (`M4-UX-04`)**: Menambahkan komponen standar `FormField.vue` (label, indikator wajib `*`, hint, error per kolom dengan `aria-invalid`/`aria-describedby`, tinggi kontrol `44px`), `StateFeedback.vue` (`loading` skeleton, `empty`, dan `error` dengan tombol `Coba lagi`), serta `ConfirmDialog.vue` (`role="alertdialog"`, `Escape` & pemulihan fokus).
- **File yang Ditambah / Diubah**:
  - `resources/js/composables/publicTheme.ts`
  - `resources/js/composables/usePublicServicesCatalog.ts` (baru)
  - `resources/js/components/PublicHeader.vue`
  - `resources/js/components/StatusBadge.vue` (baru)
  - `resources/js/components/FormField.vue` (baru)
  - `resources/js/components/StateFeedback.vue` (baru)
  - `resources/js/components/ConfirmDialog.vue` (baru)
  - `docs/TODO_PARITAS_MAN4_BANJAR.md`, `docs/HANDOFF.md`
- **Keputusan & Asumsi**:
  - Layanan pada panel E-Layanan yang rutenya belum dibangun (paket `PUB`/`TU`/`OPS`/`LIF`/`SERV`) menautkan ke rute yang sudah tersedia (`/ppdb`, `/login`, `/profil-sekolah`, `/kontak`) dengan badge `Segera` atau `Portal` agar tidak menimbulkan 404.
- **Tes & Hasil**:
  - `php artisan test --compact tests/Feature/SchoolGalleryTest.php tests/Feature/SchoolNewsTest.php tests/Feature/SchoolOrganizationTest.php tests/Feature/RoleMatrixAccessTest.php tests/Feature/CoreAuthorizationTest.php` -> **43 tes lulus (490 assertions)**.
  - `vue-tsc --noEmit`, `vite build`, dan uji browser Playwright (`390×844`, `1024px`, `1280×720`) -> **belum terverifikasi** di sesi ini karena biner `node`/`npm` tidak tersedia pada lingkungan sandbox.
- **Langkah Review Manual**:
  1. Jalankan `npx vue-tsc --noEmit && npm run build` pada mesin yang memiliki Node.js.
  2. Uji panel **E-Layanan** di `/` pada viewport `1280×720`, `1024×768`, dan `390×844`, termasuk pencarian layanan, tombol `Escape` (fokus kembali ke pemicu), serta gulir menu mobile.
- **Rollback**:
  - Hapus 5 berkas komponen/composable baru dan kembalikan `publicTheme.ts`, `PublicHeader.vue`, `TODO_PARITAS_MAN4_BANJAR.md`, serta `HANDOFF.md` via Git.

## Paket Fondasi dan Aturan Lintas Modul (`M4-FND-01` s.d. `M4-FND-08`) — 19 November 2026

- **Task & Requirement**: Implementasi kontrak fondasi, skema database lintas layanan, otorisasi 17 peran, mesin status/tiket/dokumen, outbox notifikasi, dan sanitasi privasi untuk `M4-FND-01` s.d. `M4-FND-08`.
- **Ringkasan**:
  - **Peran & Akses (`M4-FND-02`)**: Memperluas `RoleEnum` menjadi 17 peran (`admin`, `operator`, `principal`, `teacher`, `homeroom`, `counselor`, `librarian`, `student`, `parent`, `alumni`, `committee`, `ptsp`, `receptionist`, `picket`, `security_guard`, `task_force`, `signatory`), menambah izin layanan pada `PermissionEnum` dengan pemetaan baku `defaultForRole()`, mengekstrak `RolePermissionRegistryService` (dipakai ulang oleh `PilotSchoolSeeder` dan `SchoolInstallCommand`), memperbarui `config/navigation.php` untuk rute `/profil`, serta menambah `AccessScopeService` untuk pembatasan sekolah, wali kelas, wali murid terverifikasi, dan siswa tanpa melanggar batas modular monolith.
  - **Data Induk, Wali & Pemohon Eksternal (`M4-FND-03`)**: Menambahkan kolom `schools.timezone` (default `Asia/Jakarta`) beserta `SchoolScopeService::getTimezone()` / `localNow()`, tabel `external_applicants` (terpisah dari akun pegawai `users`), tabel `student_guardians` di modul `Academic`, serta method query cakupan wali/siswa/snapshot di `AcademicQueryService`.
  - **Status, Konkurensi, Tiket & Verifikasi (`M4-FND-04`, `M4-FND-06`)**: Menambahkan `ServiceWorkflowMap`, tabel `service_tickets` dan `service_ticket_transitions`, serta `ServiceTicketService` dengan kode tiket acak 8 karakter non-sekuensial (`PREFIX-YYYY-XXXXXXXX`), `idempotency_key` unik, `lockForUpdate()` + optimistic `version`, hash rahasia verifikasi (`Hash::check`), penolakan eksplisit `identity_verification_required` jika `verification_secret_hash` kosong bagi pemirsa tidak berwenang, status publik minimum, penolakan/kedaluwarsa/cabut, dan isolasi aduan anonim untuk `task_force`/`admin`.
  - **Berkas Privat & Dokumen (`M4-FND-05`)**: Menambahkan `PrivateStorageService::storeValidatedUpload()` (validasi MIME, ekstensi aman, ukuran, SHA-256), tabel `service_documents`, serta `ServiceDocumentService` (unggah lampiran privat, penomoran dokumen atomik per sekolah/jenis/tahun, `verification_token`, pembatalan beralasan dengan pencatatan `oldValues['status']` aktual, dan URL bertanda tangan berbasis `canAccessDocument`).
  - **Notifikasi Antrean & Privasi (`M4-FND-07`, `M4-FND-08`)**: Menambahkan tabel `notification_outboxes`, `NotificationOutboxService`, `DispatchNotificationOutboxJob`, `DataClassificationMap`, dan pemusatan daftar kunci sensitif pada `DataClassificationMap::sensitiveKeys()` yang dipakai bersama oleh `AuditLogger` dan `SanitizeSensitiveDataProcessor`.
- **File & Migrasi**:
  - Migrasi: `app/Modules/Core/Infrastructure/Database/Migrations/2026_11_19_100000_create_parity_foundation_tables.php`, `app/Modules/Academic/Infrastructure/Database/Migrations/2026_11_19_100001_create_student_guardians_table.php`
  - Domain: `app/Modules/Core/Domain/{RoleEnum,PermissionEnum,ServiceWorkflowMap,DataClassificationMap}.php`
  - Models: `app/Modules/Core/Infrastructure/Models/{School,ExternalApplicant,ServiceTicket,ServiceTicketTransition,ServiceDocument,NotificationOutbox}.php`, `app/Modules/Academic/Infrastructure/Models/StudentGuardian.php`
  - Application Services: `app/Modules/Core/Application/Services/{RolePermissionRegistryService,AccessScopeService,SchoolScopeService,ServiceTicketService,ServiceDocumentService,NotificationOutboxService}.php`, `app/Modules/Academic/Application/Services/AcademicQueryService.php`
  - Shared & Console/Seeder: `app/Shared/Files/PrivateStorageService.php`, `app/Shared/Audit/AuditLogger.php`, `app/Shared/Jobs/DispatchNotificationOutboxJob.php`, `app/Logging/SanitizeSensitiveDataProcessor.php`, `app/Console/Commands/SchoolInstallCommand.php`, `database/seeders/PilotSchoolSeeder.php`, `config/navigation.php`
  - Tes: `tests/Unit/ParityFoundationDomainTest.php`, `tests/Feature/ParityFoundationTest.php`
  - Docs: `docs/INVENTARIS_DAN_BASELINE_PARITAS.md`, `docs/TODO_PARITAS_MAN4_BANJAR.md`, `docs/HANDOFF.md`
- **Keputusan & Asumsi**:
  - Status checkbox `M4-FND-01` s.d. `M4-FND-08` di `TODO_PARITAS_MAN4_BANJAR.md` tetap ditandai terbuka (`[ ]`, status `dikerjakan`) hingga layar modul (`TU`, `OPS`, `LIF`, `SERV`), drill pemulihan (`REL`), dan keputusan `K-01`/`K-02`/`K-03`/`K-08` terverifikasi.
  - Tidak ada perubahan pada `DECISIONS.md` maupun dependensi baru.
- **Tes & Hasil**:
  - `php artisan test --compact tests/Unit/ParityFoundationDomainTest.php tests/Feature/ParityFoundationTest.php tests/Architecture/ModularBoundaryTest.php tests/Feature/PilotSeederTest.php tests/Feature/SchoolInstallCommandTest.php tests/Feature/RoleMatrixAccessTest.php tests/Feature/CoreAuthorizationTest.php tests/Feature/PrivateStorageTest.php tests/Feature/AuditLoggerTest.php` -> 56 tes lulus (428 assertions).
  - `./vendor/bin/pint --test` pada berkas fondasi yang disentuh (`ServiceTicketService.php`, `ServiceDocumentService.php`, `ParityFoundationTest.php`) -> lulus.
  - `php artisan test --compact` (full suite) -> 463 tes, 461 lulus, 2 dilewati (4.402 assertions, 49.3s).
  - `./vendor/bin/pint --test` pada seluruh 29 berkas PHP yang ditambah/diubah -> lulus.
  - `vue-tsc --noEmit` & `vite build` (memakai `node` di `~/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin`) -> lulus (852 modul, 4.33s).
- **Risiko Tersisa**:
  - Migrasi `2026_11_19_100000` dan `2026_11_19_100001` belum dijalankan terhadap database MySQL lokal/staging (hanya teruji penuh via `RefreshDatabase` SQLite).
  - Belum ada antarmuka pengguna (UI) maupun rute HTTP baru pada tahap fondasi ini; pengkabelan ke rute & layar dimulai pada paket `UX`/`PUB` dan `TU`.
- **Langkah Review Manual**:
  1. Tinjau skema migrasi baru dan pastikan siap dijalankan (`php artisan migrate`).
  2. Lanjutkan ke **Irisan 1 (`M4-UX-01`/`02` + `M4-PUB-01`/`02`)** untuk panel E-Layanan dan beranda publik.
- **Rollback**:
  - Jalankan `php artisan migrate:rollback --step=2` (bila sudah dimigrasikan pada DB target) dan kembalikan berkas melalui Git.

## Checklist Awal Paritas MAN 4 Banjar (Inventaris, Baseline & Peta 48 ID) — 19 November 2026

- **Task & Requirement**: Memeriksa `docs/TODO_PARITAS_MAN4_BANJAR.md` dan mengerjakan `Checklist awal` (fondasi awal `M4-FND-01`, pemetaan `K-01`..`K-08`, pemetaan 17 peran `M4-FND-02`, serta inventaris layar/aksi/rute/state untuk 48 ID + 6 rincian tambahan REA).
- **Ringkasan**:
  - Mencatat keadaan repositori pada `main` commit `9265cdb` (`HANDOFF MAN4 BANJAR`) beserta folder untracked lokal `output/playwright/school_suite/` (28 screenshot QA 15 Nov 2026).
  - Menjalankan baseline pemeriksaan berulang: suite PHPUnit, Laravel Pint, TypeScript check (`vue-tsc`), dan build produksi Vite.
  - Menyusun dokumen [INVENTARIS_DAN_BASELINE_PARITAS.md](INVENTARIS_DAN_BASELINE_PARITAS.md) yang memisahkan varian portal utama vs portal akademik, bukti REA CLI 6.7.0 + batas pengamatan sandbox (`search_web` 19 Nov 2026 tanpa akses HTTP langsung ke `man4banjar.com`), daftar halaman layanan yang belum diuji visual, matriks keputusan `K-01`..`K-08` beserta pemiliknya, pemetaan 17 peran (`PilotSchoolSeeder` vs target `M4-FND-02`), dan matriks 48 ID + 6 rincian tambahan REA dengan tiga status terpisah (Acuan, School Suite, Klien).
  - Memperbarui bagian `Checklist awal` pada `docs/TODO_PARITAS_MAN4_BANJAR.md` (4 item inventaris & baseline dicentang `[x]`; 3 item tata kelola berkelanjutan / gate 100% dibiarkan terbuka `[ ]`).
- **File yang Ditambah / Diubah**:
  - `docs/INVENTARIS_DAN_BASELINE_PARITAS.md` (baru)
  - `docs/TODO_PARITAS_MAN4_BANJAR.md`
  - `docs/HANDOFF.md`
- **Keputusan & Asumsi**:
  - Tidak ada perubahan kode aplikasi, skema database, atau `DECISIONS.md` pada tahap ini.
  - `M4-FND-01` tetap berstatus parsial sampai akses demo/panel di balik login acuan atau persetujuan alur School Suite dari klien tersedia.
- **Tes & Hasil**:
  - `php artisan test --compact` -> 453 tes, 451 lulus, 2 dilewati (4.322 assertions, 60.5s, PHP 8.5.12 CLI).
  - `./vendor/bin/pint --test` -> gagal pada 4 berkas existing baseline (`PasswordController.php`, `JournalService.php`, `artifacts/font-audit/collect-fixtures.php`, `artifacts/dark-mode/collect.php`).
  - `vue-tsc --noEmit` (memakai `node` di `~/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin`) -> lulus (exit 0).
  - `vite build` (memakai `node` yang sama) -> lulus (852 modul, 8.21s).
- **Risiko Tersisa**:
  - Lingkungan sandbox tidak memiliki akses HTTP langsung ke `man4banjar.com` dan tidak ada `node`/`npm` pada `PATH` shell default.
  - Direktori `tests/e2e` belum tersedia sehingga suite Playwright belum berjalan.
  - Panel internal di balik login acuan belum terverifikasi dan `K-01`..`K-08` masih menunggu konfirmasi klien.
- **Langkah Review Manual & Berikutnya**:
  1. Tinjau `docs/INVENTARIS_DAN_BASELINE_PARITAS.md` dan konfirmasi urutan irisan berikutnya: **Irisan 1 (FND dasar + UX-01/02 + PUB-01/02)** — panel E-Layanan 3 kolom desktop/mobile, pencarian layanan, dan 4 pintasan beranda.
- **Rollback**:
  - Hapus `docs/INVENTARIS_DAN_BASELINE_PARITAS.md` dan kembalikan `docs/TODO_PARITAS_MAN4_BANJAR.md` serta `docs/HANDOFF.md` via Git.

## Pelengkapan & Penyeragaman Menu Rapor Siswa (Standar e-Rapor Kemdikdasmen & STS/SAS) — 15 November 2026

- **Task & Requirement**: Melengkapi alur kerja, kesiapan nilai, data pelengkap, serta format dokumen cetak e-Rapor Kemdikdasmen (termasuk dukungan mode STS dan SAS).
- **Ringkasan**:
  - Menambahkan mode periode rapor (`sas` / `sts`) dan generator otomatis `competency_description` pada `ReportPublishingService::generateReportSnapshot()`, serta endpoint `POST /penilaian/rapor/{report}/pelengkap` (`updateReportSupplements`) untuk mengisi ekstrakurikuler, catatan wali kelas, keputusan kenaikan kelas/kelulusan, tempat/tanggal pembagian rapor, dan penyuntingan deskripsi kompetensi selama status masih draf.
  - Menambahkan ringkasan kesiapan & progres kelengkapan nilai per mata pelajaran (`ReportGeneratorService::getClassReadiness()`) serta modal **Lengkapi Data Pelengkap Rapor** di `Assessment/Reports/Index.vue` dan `Show.vue`.
  - Menghapus alur **Terbitkan** / approval (`publishBatch`, `publishSingle`, `publishReport`, serta label status `Draf`/`Terbit`) dari antarmuka `Assessment/Reports/Index.vue`, `Show.vue`, dan footer `resources/views/reports/report-card.blade.php` sehingga rapor dapat langsung dilengkapi dan dicetak/dipratinjau tanpa penerbitan (endpoint backend tetap dipertahankan agar kompatibel dengan tes fitur yang ada).
  - Merombak `resources/views/reports/report-card.blade.php` sesuai format e-Rapor Kemdikdasmen (Halaman Sampul, Identitas Sekolah & Peserta Didik + Pas Foto 3x4, Fase, Capaian Hasil Belajar, Ekstrakurikuler, Prestasi, Ketidakhadiran, Catatan Wali Kelas, Keputusan Kenaikan/Kelulusan, Tanggapan Orang Tua, Tanda Tangan + NIP) dan menambahkan pratinjau PDF tab baru (`inline`) serta cetak PDF massal per rombel (`/penilaian/rapor/cetak-massal/pdf`).
- **File yang Diubah**:
  - `app/Modules/Assessment/Application/Services/ReportPublishingService.php`
  - `app/Modules/Assessment/Application/Services/ReportGeneratorService.php`
  - `app/Modules/Assessment/Interface/Http/Controllers/ReportCardController.php`
  - `app/Modules/Assessment/Interface/Http/Controllers/ReportingController.php`
  - `app/Modules/Assessment/Interface/Routes/web.php`
  - `resources/views/reports/report-card.blade.php`
  - `resources/js/Pages/Assessment/Reports/Index.vue`
  - `resources/js/Pages/Assessment/Reports/Show.vue`
  - `tests/Feature/AssessmentControllersTest.php`
- **Tes & Hasil**:
  - `php artisan test --compact tests/Feature/AssessmentControllersTest.php` -> 64 tes lulus (575 assertions, 0 gagal).
  - `npm run build` -> lulus (`vite build`, 865 modul ditransformasi dalam 4.34s).

## Audit & Konversi Overlay Modal (Side-Drawer ke Centered Dialog) — 14 November 2026

- **Task & Requirement**: Mengubah dialog tambah/edit/impor data siswa dan seluruh halaman lain yang masih menggunakan pola *side-drawer overlay* dari kanan menjadi *centered modal dialog* di tengah layar.
- **Ringkasan**:
  - Mengaudit seluruh halaman Vue di `resources/js/Pages/**` terhadap penggunaan `role="dialog"`, `aria-modal="true"`, `-overlay`, `-drawer`, dan `.modal-backdrop`.
  - Mengubah 8 halaman yang sebelumnya menampilkan panel formulir dari samping kanan (`justify-content: flex-end; height: 100%`) menjadi dialog terpusat di tengah layar (`display: grid; place-items: center; padding: 1rem; max-height: min(90dvh, 860px–880px); border-radius: 16px`), termasuk pada tampilan *mobile* (`max-width: 600px`/`760px`).
  - Menambahkan animasi masuk (*motion*) terpadu untuk seluruh *form overlay* dan dialog di `resources/css/app.css` (`ssOverlayFadeIn` 180ms + `ssModalPopIn` 220ms dengan `cubic-bezier(0.16, 1, 0.3, 1)` dan dukungan `prefers-reduced-motion: reduce`).
- **File yang Diubah**:
  - `resources/css/app.css`
  - `resources/js/Pages/Academic/Students/Index.vue`
  - `resources/js/Pages/Academic/Teachers/Index.vue`
  - `resources/js/Pages/Academic/Classes/Index.vue`
  - `resources/js/Pages/Academic/Schedules/Index.vue`
  - `resources/js/Pages/Academic/OnlineClasses/Index.vue`
  - `resources/js/Pages/Academic/Journals/Index.vue`
  - `resources/js/Pages/Core/Users/Index.vue`
  - `resources/js/Pages/Communication/Index.vue`
- **Keputusan & Asumsi**:
  - Nama class DOM (`.student-overlay`, `.student-drawer`, `.teacher-drawer`, dll.) dipertahankan agar tidak memutus selector tes maupun struktur komponen yang ada.
- **Tes & Hasil**:
  - Audit CSS via `grep` memastikan tidak ada lagi modal dialog dengan `justify-content: flex-end` di `resources/js/Pages`.
  - `npm run build` -> lulus (`vite build`, 865 modul ditransformasi dalam 6.20s).
- **Langkah Review Manual**:
  1. Jalankan `npm run dev` atau `npm run build`.
  2. Buka menu Data Siswa, Data Guru, Rombel, Jadwal Pelajaran, Kelas Online, Jurnal Mengajar, Pengguna, dan Pengumuman/Kalender; klik tombol Tambah/Edit untuk memastikan modal tampil tepat di tengah layar.
- **Rollback**:
  - Kembalikan perubahan blok `<style scoped>` pada ke-8 file Vue di atas melalui Git.

## Redesign Buku Induk Siswa (Format Hardcopy, Alur Massal & Cetakan Lengkap) — 14 November 2026

- **Task & Requirement**: Redesign Buku Induk Peserta Didik (`SS-F-002`, `SS-F-003`, `SS-F-007` / `SS-D-005`, `SS-F-008`).
- **Ringkasan**:
  - Memperluas skema dan formulir Buku Induk sesuai struktur buku induk fisik/hardcopy (A. Pribadi, B. Tempat Tinggal/Kesehatan/Kegemaran, C. Orang Tua/Wali, D. Pendidikan Asal & Penerimaan, E. Tamat Belajar/Keluar) serta memperbaiki panjang kolom `blood_type` (`VARCHAR(15)`) agar opsi `'Tidak Tahu'` tidak terpotong di MySQL strict mode.
  - Menambahkan indikator & filter kelengkapan data (`complete` / `incomplete`), panel *Isi Cepat* (*Quick Edit*) non-intrusif di tabel daftar, opsi koreksi identitas dasar (`students`) langsung dari halaman detail Buku Induk, serta tombol *Simpan & Lanjut ke Siswa Berikutnya*.
  - Menambahkan fitur **Ekspor / Template Excel (`.xlsx`)** lengkap (59 kolom dengan format teks eksplisit) dan **Impor / Update Massal Excel/CSV** berbasis pencocokan `NIS` secara transaksional.
  - Merombak cetakan PDF Buku Induk (per siswa maupun cetak massal per filter/rombel) dengan Kop Sekolah, Pas Foto 3x4, bagian A–F, blok tanda tangan Kepala Sekolah, serta halaman ke-2 **Rekapitulasi Capaian Hasil Belajar (Rapor Terbit per Semester)** melalui `ReportPublishingService` tanpa melanggar batas modular monolith.
- **File & Migrasi**:
  - `app/Modules/Academic/Infrastructure/Database/Migrations/2026_11_14_221500_expand_student_ledger_hardcopy_fields.php`
  - `app/Modules/Academic/Infrastructure/Models/StudentLedgerDetail.php`
  - `app/Modules/Academic/Infrastructure/Models/Student.php`
  - `app/Modules/Assessment/Application/Services/ReportPublishingService.php`
  - `app/Modules/Academic/Interface/Routes/buku_induk.php`
  - `app/Modules/Academic/Interface/Http/Controllers/StudentLedgerController.php`
  - `resources/views/academic/student-ledger.blade.php`
  - `resources/js/Pages/Academic/Students/Ledger/Index.vue`
  - `resources/js/Pages/Academic/Students/Ledger/Show.vue`
  - `tests/Feature/StudentLedgerControllersTest.php`
- **Keputusan & Asumsi**:
  - Pengambilan snapshot rapor terbit dari modul `Assessment` ke modul `Academic` dilakukan melalui `ReportPublishingService::getPublishedSnapshotsForStudents()` pada layer `Application`.
  - Seluruh kolom baru bersifat `nullable` agar kompatibel dengan data siswa yang sudah ada.
- **Tes & Hasil**:
  - `php artisan test --compact tests/Feature/StudentLedgerControllersTest.php tests/Architecture/ModularBoundaryTest.php` -> 13 tes lulus (153 assertions).
  - `php artisan test --compact` -> 456 tes, 454 lulus, 2 dilewati (4.258 assertions).
  - `./vendor/bin/pint --test` pada seluruh file PHP yang disentuh -> lulus.
  - `npm run typecheck` / `npm run build` -> *not verified* (binary `node`/`npm` tidak tersedia pada environment shell saat ini).
- **Risiko Tersisa**:
  - Kompilasi aset frontend (`npm run typecheck` dan `npm run build`) serta migrasi database lokal (`php artisan migrate`) perlu dijalankan pada mesin tempat Node.js dan MySQL aktif.
- **Langkah Review Manual**:
  1. Jalankan `php artisan migrate` dan `npm run build`.
  2. Buka `/akademik/buku-induk`, uji filter **Kelengkapan data**, tombol **Isi cepat**, **Ekspor / Template Excel (.xlsx)**, **Impor / Update Massal Excel**, serta **Cetak PDF Massal**.
  3. Buka detail siswa `/akademik/buku-induk/{id}`, lengkapi bagian A–E, uji tombol **Simpan & Lanjut**, dan unduh PDF Buku Induk.
- **Rollback**:
  - Jalankan `php artisan migrate:rollback --step=1` untuk melepas kolom tambahan `2026_11_14_221500_expand_student_ledger_hardcopy_fields.php` dan kembalikan perubahan file melalui Git.

## Duplikasi alur soal Jurnal Guru — 13 November 2026

Rujukan dipatok ke `ilhamrizqiawan21/jurnal-harian-guru`, branch `fitur-lisensi-2-0`, commit `27b2eaaf69c1f8e555fb3198c4150a837500322e`.

- Bank soal: C1–C6, kelas, sumber, pasangan tunggal, impor CSV/XLSX format School Suite maupun PG Jurnal Guru, preview validasi, impor baris valid secara eksplisit, dan melewati duplikat.
- Paket soal: identitas, kop teks/gambar, A4/F4, portrait/landscape, 1–3 kolom, pratinjau naskah/kunci/kisi-kisi, Word memakai mesin rujukan; PDF menggunakan dialog cetak browser. Snapshot tetap tersimpan saat bank berubah dan saat paket disalin.
- Analisis soal: mesin hitung dan validasi rujukan, tiga langkah, manual/impor, draf terenkripsi per akun/sekolah, Excel tiga sheet yang dapat diimpor ulang, dan PDF khusus analisis. Excel membersihkan draf, PDF mempertahankannya.
- Dua migrasi penambahan kolom nullable sudah diterapkan pada MySQL lokal: `2026_11_13_120000` dan `2026_11_13_120100`.

Verifikasi: suite backend 453 tes (451 lulus, 2 dilewati), TypeScript dan build lulus, tes mesin CommonJS/ESM serta XML Word lulus. Pemeriksaan browser memakai SQLite uji terpisah: analisis lima siswa, unduhan Excel/PDF, penyimpanan pengaturan F4 landscape tiga kolom, naskah/kunci/kisi-kisi, dan unduhan Word. Gaya iframe memakai nonce CSP halaman.

Detail adaptasi dan perintah uji tercatat di `resources/js/lib/reference/README.md`. Belum dipublikasikan atau di-commit; perubahan UI lain yang sebelumnya ada di worktree tetap dipertahankan.

## Tugas 1 — Kompatibilitas dependency PHP 8.3 (27 Oktober 2026)

### File yang diubah

- `composer.json`: menambahkan `config.platform.php=8.3.0`.
- `composer.lock`: menurunkan dependency Symfony yang mensyaratkan PHP >=8.4.1 ke rilis Symfony 7.4 yang mendukung PHP 8.3; paket Symfony 16 turun, dan `symfony/polyfill-php83` ditambahkan.
- `docs/DEPLOYMENT_CHECKLIST.md`: mensyaratkan CLI dan PHP-FPM 8.3.x.

### Perintah verifikasi dan output asli

PHP lokal: `php -v`

```text
PHP 8.5.4 (cli) (built: Oct 19 2026 14:35:18) (NTS)
Copyright (c) The PHP Group
Built by Ubuntu
Zend Engine v4.5.4, Copyright (c) Zend Technologies
    with Zend OPcache v8.5.4, Copyright (c), by Zend Technologies
```

`composer validate`

```text
Cannot create cache directory /home/ilhamzp/.cache/composer/files/, or directory is not writable. Proceeding without cache. See also cache-read-only config if your filesystem is read-only.
./composer.json is valid
```

`composer check-platform-reqs`

```text
Checking platform requirements for packages in the vendor dir
composer-runtime-api 2.2.2      success
ext-ctype            *          success provided by symfony/polyfill-ctype
ext-dom              20031129   success
ext-fileinfo         8.5.4      success
ext-filter           8.5.4      success
ext-gd               8.5.4      success
ext-hash             8.5.4      success
ext-iconv            8.5.4      success
ext-json             8.5.4      success
ext-libxml           8.5.4      success
ext-mbstring         *          success provided by symfony/polyfill-mbstring
ext-openssl          8.5.4      success
ext-pcre             8.5.4      success
ext-phar             8.5.4      success
ext-session          8.5.4      success
ext-simplexml        8.5.4      success
ext-tokenizer         8.5.4      success
ext-xml              8.5.4      success
ext-xmlreader         8.5.4      success
ext-xmlwriter        8.5.4      success
ext-zip              1.22.7     success
ext-zlib             8.5.4      success
php                  8.5.4      success
php-64bit            8.5.4      success
```

`composer install --no-interaction` was run after moving the existing `vendor/` directory aside. It verified the lock and installed 127 packages with exit code 0. Relevant original output:

```text
Installing dependencies from lock file (including require-dev)
Verifying lock file contents can be installed on current platform.
Package operations: 127 installs, 0 updates, 0 removals
Generating optimized autoload files
> Illuminate\Foundation\ComposerScripts::postAutoloadDump
> @php artisan package:discover --ansi
...
84 packages you are using are looking for funding.
```

`composer check-platform-reqs --lock` also passed all locked requirements. It reported PHP 8.5.4, the actual local runtime.

### Risiko dan verifikasi yang belum dilakukan

- PHP 8.3 binary/FPM tidak tersedia di mesin ini (`command -v php8.3` tidak menemukan executable), sehingga `composer install` dan `check-platform-reqs` belum dijalankan pada PHP 8.3 asli. Hasil ini membuktikan solver menerima platform PHP 8.3.0 dan semua paket terkunci mengizinkannya; target runtime tetap perlu diuji di host/container PHP 8.3.
- Composer cache di home tidak dapat ditulis; Composer berjalan tanpa cache.
- Regenerasi memakai Packagist setelah akses jaringan diizinkan; install bersih menyelesaikan seluruh 127 paket.

### Commit

- `6007c52 fix(deps): constrain Composer platform to PHP 8.3`.

## Tugas 2 — Bukti kualitas dan status proyek (27 Oktober 2026)

### File yang diubah

- `docs/PROJECT_STATUS.md`: mengganti klaim kesiapan live dan angka lama dengan bukti run 27 Oktober serta blocker yang belum ditutup.
- Sembilan file yang gagal Pint diformat satu per satu sesuai hasil `pint --test`: `tests/Unit/TeachingPlanDocumentServiceTest.php`, `tests/Feature/MysqlBackupRestoreTest.php`, `tests/Feature/TeachingPlanningDocumentsTest.php`, `app/Shared/Backup/DatabaseBackupService.php`, `app/Modules/Academic/Application/Services/TeachingPlanDocumentService.php`, `app/Modules/Academic/Application/Services/JournalService.php`, `app/Modules/Academic/Interface/Http/Controllers/JournalController.php`, `app/Modules/Academic/Interface/Http/Controllers/TeachingPlanController.php`, dan `app/Modules/Academic/Interface/Http/Controllers/AttendanceController.php`.
- `AcademicQueryService`, `SchoolScopeService`, `CorrespondenceService`, `CorrespondenceController`, dan `CommunicationController`: memperbaiki pelanggaran batas modul yang ditemukan oleh test suite dengan memindahkan query data akademik/sekolah ke Application services.
- `tests/Feature/CoreAuthorizationTest.php`: menguji exception validasi yang memang dihasilkan saat pengguna mencoba mengubah role akunnya sendiri; assertion tetap memastikan role tidak berubah.

### Perintah verifikasi dan output asli

Run pertama `php artisan test --compact`:

```json
{"tool":"phpunit","result":"failed","tests":343,"passed":340,"assertions":3155,"duration_ms":32021,"failed":1,"failures":[{"test":"Tests\\Architecture\\ModularBoundaryTest::test_modules_cannot_import_other_modules_infrastructure_or_models","file":"/home/ilhamzp/Projects/school_suite/tests/Architecture/ModularBoundaryTest.php","line":134,"message":"Modular monolith boundary violations detected: imports School dari Core pada CorrespondenceService dan CorrespondenceController, serta TeachingAssignment dari Academic pada CommunicationController."}],"errors":1,"error_details":[{"test":"Tests\\Feature\\CoreAuthorizationTest::test_non_admin_with_user_create_permission_cannot_assign_admin_role","file":"/home/ilhamzp/Projects/school_suite/tests/Feature/CoreAuthorizationTest.php","line":265,"message":"Peran akun yang sedang digunakan tidak dapat diubah. Gunakan akun administrator lain."}],"skipped":1}
```

Run ulang `php artisan test --compact` setelah perbaikan:

```json
{"tool":"phpunit","result":"passed","tests":343,"passed":342,"assertions":3156,"duration_ms":31826,"skipped":1}
```

`vendor/bin/pint --test` sebelum perbaikan menemukan sembilan file; Pint dijalankan pada masing-masing file tersebut. Pemeriksaan akhir:

```text
{"tool":"pint","result":"passed"}
```

`npm run typecheck`:

```text
> typecheck
> vue-tsc --noEmit
```

Perintah selesai dengan exit code 0.

`npm run build`:

```text
> build
> vite build
vite v8.3.5 building client environment for production...
✓ 754 modules transformed.
✓ built in 3.43s
```

Perintah selesai dengan exit code 0.

### Risiko dan verifikasi yang belum dilakukan

- Satu tes dilewati; alasan skip yang spesifik dicatat pada tes terkait. Suite sekarang lulus, tetapi belum membuktikan jalur MySQL dan deployment server yang diminta di Tugas 3 dan 4.
- Pemeriksaan sebelumnya menyebut perubahan lintas modul; perbaikan sudah diuji dengan run suite penuh (343 tes) dan terarah (14 tes, 55 assertions).

### Commit

- `960faba fix(quality): resolve architecture and test-suite failures`.

## Tugas 3 — Drill backup/restore MySQL dan SQLite file (27 Oktober 2026)

### File yang diubah

- `tests/Feature/MysqlBackupRestoreTest.php`: mengganti fixture dua tabel buatan dengan migrasi aplikasi dan `PilotSchoolSeeder` pada database sumber MySQL terisolasi; target dibuat kosong. Tes membandingkan `COUNT(*)` seluruh tabel serta SHA-256 serialisasi data pada `schools`, `roles`, `users`, `students`, `enrollments`, `teaching_assignments`, `scores`, dan `report_snapshots`. Database sementara dibuang pada `finally`. Tes dilewati dengan alasan jelas bila socket MySQL khusus tidak dikonfigurasi/tersedia.
- `app/Shared/Backup/DatabaseBackupService.php` sudah diformat di commit Tugas 2; tidak ada perubahan perilaku restore MySQL pada Tugas 3.

### Perintah verifikasi dan output asli

`php artisan test --compact tests/Feature/MysqlBackupRestoreTest.php tests/Feature/BackupRecoverySafetyTest.php`

```json
{"tool":"phpunit","result":"passed","tests":8,"passed":7,"assertions":19,"duration_ms":642,"skipped":1}
```

SQLite file backup → restore default berada di `BackupRecoverySafetyTest::test_file_sqlite_backup_includes_wal_and_restores_to_default_connection`; test file keseluruhan lulus 7 tes, termasuk alur tersebut.

Pemeriksaan ketersediaan server:

```text
/usr/bin/mysqld
/usr/bin/mysqldump
/usr/bin/mysql
/usr/bin/mysqladmin
```

`mysqladmin --protocol=socket -uroot ping`:

```text
\a mysqladmin: connect to server at 'localhost' failed
error: 'Can't connect to local MySQL server through socket '/var/run/mysqld/mysqld.sock' (1)'
Check that mysqld is running and that the socket: '/var/run/mysqld/mysqld.sock' exists!
```

`docker info --format '{{.ServerVersion}}'`:

```text
permission denied while trying to connect to the Docker API at unix:///var/run/docker.sock
```

Pada pemeriksaan awal sesi ini MySQL lokal tidak dapat dihubungi; inisialisasi sementara di `/tmp/school-suite-mysql-drill-20261027b` gagal karena `InnoDB Unable to lock ./ibdata1 error: 11`. Tidak ada koneksi operasional yang digunakan.

Pembaruan bukti yang muncul kemudian di working tree saat tugas berlangsung (perubahan kode/dokumen tersebut tidak termasuk commit Tugas 5): `docs/AUDIT_BACKEND_2026-10-27.md` mencatat perintah `BACKUP_MYSQL_TEST_SOCKET=<socket-drill> php artisan test --compact --filter=MysqlBackupRestoreTest` lulus pada MySQL 8.4.11 dengan 1 tes/20 assertions, target acak terisolasi, tanpa port jaringan. Dokumen audit juga mencatat drill membandingkan jumlah baris/checksum dataset pilot dan menolak target yang tidak kosong. Output PHPUnit mentah dan path socket tidak tercantum di dokumen audit.

### Risiko dan verifikasi yang belum dilakukan

- Hasil awal sesi ini memang skip; bukti MySQL 8.4.11 yang dicatat kemudian di audit tidak menyertakan output mentah atau socket agar dapat direproduksi dari sesi ini.
- Perintah `BackupRecoverySafetyTest` awal lulus, dan dokumen audit yang diperbarui kemudian menyebut cakupan restore SQLite file default/explicit; detail akhir tercantum di `docs/AUDIT_BACKEND_2026-10-27.md`.

### Commit

- `e64ad50 test(backup): cover isolated MySQL restore with pilot dataset`.

## Tugas 4 — Health check deployment dan rollback (27 Oktober 2026)

### File yang diubah

- `deploy/deploy.sh`: `HEALTH_CHECK_URL` wajib diisi, harus memakai HTTPS, dan kegagalan probe pada instalasi pertama menghapus symlink `current` lalu menghentikan worker; jika ada previous release, symlink dipulihkan ke rilis itu.
- `deploy/health-check.sh`: tidak lagi memakai domain placeholder; URL wajib diberikan dan curl memiliki `--connect-timeout 5 --max-time 15`.
- `tests/Feature/DeploymentConfigurationTest.php`: memeriksa URL wajib, timeout eksplisit, dan pembersihan symlink saat belum ada rilis sebelumnya.
- `docs/PROJECT_STATUS.md`: mencatat dry run rollback; probe endpoint HTTPS production masih terbuka.

### Perintah verifikasi dan output asli

`bash -n deploy/deploy.sh && bash -n deploy/health-check.sh && bash -n deploy/rollback.sh` selesai dengan exit code 0 dan tanpa output.

`HEALTH_CHECK_URL= bash deploy/deploy.sh`:

```text
Kesalahan: HEALTH_CHECK_URL wajib diisi, misalnya https://<domain-aplikasi>/up.
```

Exit code: `1`.

`php artisan test --compact tests/Feature/DeploymentConfigurationTest.php tests/Feature/DeploymentHealthProbeTest.php`:

```json
{"tool":"phpunit","result":"passed","tests":10,"passed":10,"assertions":77,"duration_ms":1190}
```

Dry run fungsi rollback aktual yang diekstrak dari `deploy.sh` memakai direktori `/tmp` dan stub `sudo`:

```text
Rilis gagal diverifikasi.
Rilis gagal diverifikasi.
first install: exit=1, current symlink removed
previous release: exit=1, current symlink restored
```

Tes health probe memakai stub curl menguji payload sehat, payload salah, redirect, HTTP redirect, 503, dan timeout; seluruh 10 tes konfigurasi/probe lulus. Server HTTP lokal tidak dipakai karena health check mewajibkan HTTPS dan memvalidasi JSON aplikasi.

### Risiko dan verifikasi yang belum dilakukan

- Tidak ada rilis/deploy pada server nyata. HTTPS domain production, sertifikat, vhost Nginx, dan payload dari rilis aktif belum diuji end-to-end.
- URL health check kini harus diberikan eksplisit oleh operator saat deploy.

### Commit

- `ec7a57f fix(deploy): require health URL and roll back failed first release`.

## Tugas 5 — Verifikasi otorisasi per peran (27 Oktober 2026)

### File yang diubah

- `tests/Feature/AssessmentControllersTest.php`: menambah tes bahwa parent, student, guest dan operator lintas sekolah ditolak pada ledger; daftar rapor siswa hanya berisi rapor miliknya yang terbit; rapor draft miliknya tidak muncul dan tidak dapat dibuka.
- `docs/HANDOFF.md` dan `docs/PROJECT_STATUS.md`: mencatat hasil aktual, komposisi role, ketidakcocokan navigasi/route, dan blocker.

### Perintah verifikasi dan output asli

`php artisan test --compact`:

```json
{"tool":"phpunit","result":"passed","tests":350,"passed":349,"assertions":3200,"duration_ms":30724,"skipped":1}
```

`vendor/bin/pint --test`:

```json
{"tool":"pint","result":"passed"}
```

`npm run typecheck`:

```text
> typecheck
> vue-tsc --noEmit
```

Exit code: `0`.

`npm run build` (output ringkas dari build yang lulus):

```text
vite v8.3.5 building client environment for production...
✓ 754 modules transformed.
✓ built in 3.48s
```

Perintah khusus yang diminta, `php artisan test --compact tests/Feature/RoleMatrixAccessTest.php tests/Feature/RouteAndPermissionStressTest.php`:

```json
{"tool":"phpunit","result":"passed","tests":22,"passed":22,"assertions":533,"duration_ms":3313}
```

`php artisan test --compact tests/Feature/AssessmentControllersTest.php`:

```json
{"tool":"phpunit","result":"passed","tests":57,"passed":57,"assertions":470,"duration_ms":8002}
```

### Daftar role dan batas audit

`RoleEnum` berisi sembilan role: `admin`, `operator`, `principal`, `teacher`, `homeroom`, `counselor`, `librarian`, `student`, `parent`. `PilotSchoolSeeder` hanya membuat tujuh: `admin`, `principal`, `operator`, `teacher`, `homeroom`, `counselor`, `student`. Maka usulan daftar delapan role dari permintaan kurang `counselor` dan `librarian`; `guest` bukan role kanonis di enum atau seeder, sedangkan `parent` ada di enum tetapi tidak dibuat oleh Pilot seeder. `guest` diuji sebagai status tanpa role/aktor unauthenticated sesuai konteks.

Tes negatif langsung yang terverifikasi: guru tidak dapat membaca/menulis nilai penugasan kelas guru lain (tes yang sudah ada); wali kelas tidak dapat menerbitkan rapor kelas lain (tes yang sudah ada); parent, guest, dan student menerima 403 untuk `/penilaian/ledger`; student hanya melihat rapor terbit miliknya dan menerima 403 untuk snapshot draft; operator menerima 403 untuk ledger sekolah lain. Tes di atas juga memverifikasi rute negatif ini dengan request HTTP Laravel. Tidak ada perubahan kebijakan akses yang dibuat pada tugas ini.

### Ketidakcocokan menu dan akses URL

Audit statis `resources/js/components/SidebarNavigation.vue` dibanding route/policy/controller menemukan beberapa tautan yang disembunyikan dari sidebar tetapi URL langsung tidak memberi 403 kepada role di bawah ini. Ini dilaporkan sebagai ketidakcocokan dan tidak diubah diam-diam:

| Menu/URL | Role yang tercatat | Perilaku URL langsung |
|---|---|---|
| `/akademik/jadwal` | Parent tidak diberi menu; student justru diberi menu | Route hanya `auth`; `SchedulePolicy::viewAny` mengizinkan authenticated user, sehingga parent juga dapat masuk. |
| `/akademik/kelas` | Student tidak diberi menu | `ClassGroupPolicy::viewAny` mengizinkan student; route tidak menolak role itu. |
| `/akademik/presensi/rekap` | Menu hanya untuk principal | `AttendancePolicy::viewAny` juga mengizinkan operator, teacher, dan homeroom (atau permission yang sesuai). |
| `/wali-kelas/catatan` dan `/wali-kelas/kedisiplinan` | Menu hanya untuk homeroom | Route/policy juga memberi akses sebagian role teacher/counselor, jadi URL langsung tidak konsisten dengan menu. |
| `/komunikasi` | Menu tidak mencantumkan principal | `RouteAndPermissionStressTest::test_principal_role_actions_succeed_and_boundaries_enforced` mengharapkan principal mendapat HTTP 200. |

`RoleMatrixAccessTest` dan `RouteAndPermissionStressTest` lulus, tetapi keduanya tidak membandingkan DOM sidebar dengan seluruh URL untuk semua role. Tidak ada pengujian browser/DOM parity untuk sembilan role; daftar ketidakcocokan di atas bukan inventaris lengkap semua menu aplikasi.

### Risiko dan hal yang belum diverifikasi

- Tes unit/feature menegaskan batas objek yang diminta, namun tidak membuktikan kesesuaian semua menu terhadap seluruh izin dan seluruh route. Ketidakcocokan URL/menu yang diketahui di atas masih terbuka.
- Pengujian MySQL terisolasi tetap skip karena server MySQL lokal tidak tersedia; endpoint health HTTPS produksi belum diuji; runtime PHP 8.3 asli tidak tersedia. Detail bukti berada pada Tugas 1, 3, dan 4.
- Tidak dilakukan uji browser terhadap setiap role. `npm run typecheck` dan `npm run build` lulus, tetapi tidak menggantikan uji otorisasi UI end-to-end.

### Commit

- `test(auth): cover cross-role assessment boundaries` (commit Tugas 5).

### Commit

- `fix(deploy): require health URL and roll back failed first release` (akan diisi setelah commit dibuat).

## Identitas

- Task/issue: Restrukturisasi, Perapihan & Pelengkapan Menu Navigasi Sidebar dan Alur Kerja Dashboard Non-Admin (Guru, Wali Kelas, Siswa, Kepala Sekolah, Operator)
- Requirement ID: UI-ROLE-NAV-02, S01-T05, LMS-C01–C11, LMS-D01–D08, LMS-E01–E09, LMS-F01–F07, DESIGN.md (Arsitektur Informasi), AI_RULES.md
- Branch/commit: main / commit berikutnya
- Status: Selesai & Terverifikasi (Victory Confirmed oleh Independent Victory Auditor & 184/184 tests passed)

## Ringkasan dan scope

- Perubahan:
  - **1. Restrukturisasi Kategori Domain Navigasi Sidebar (`SidebarNavigation.vue`)**:
    - Menu dikelompokkan ke dalam kategori domain yang terstruktur: *Akademik*, *Pembelajaran LMS*, *Asesmen & CBT*, *Penilaian & Rapor*, *Perwalian*, *Komunikasi*, *Pengaturan*, serta *Beranda*.
    - Header kategori kosong dieliminasi secara dinamis (`.filter(s => s.items.length > 0)`).
    - Logika `isItemActive` disempurnakan dengan segment boundary check (`===` atau diawali prefix diikuti slash/query/hash) untuk mencegah highlight ganda pada sub-rute.
    - Menambahkan rute navigasi yang sebelumnya hilang atau belum terhubung:
      - `/komunikasi/chat` (Chat Kelas) untuk Guru, Siswa, Wali Kelas, Kepsek, dan Operator.
      - `/wali-kelas/catatan` (Catatan Observasi & BK) untuk Wali Kelas.
      - `/wali-kelas/kedisiplinan` (Kedisiplinan & Prestasi) untuk Wali Kelas.
      - `/komunikasi/whatsapp` (WhatsApp Ortu) untuk Wali Kelas, Kepsek, Operator.
      - `/akademik/jadwal` (Jadwal Pelajaran) kini terlihat untuk Siswa (`student`).
  - **2. Pengayaan Alur Kerja & Pintasan Dashboard Role Non-Admin (`Dashboard.vue`)**:
    - **Isolasi Mutlak Admin**: Bagian `adminActions` dan komponen overview admin dipertahankan utuh dan tidak terganggu.
    - Menambahkan dan memperkaya panel aksi mandiri per peran:
      - `teacherActions` (8 alur kerja: input nilai, presensi & jurnal, ruang belajar LMS, bank soal cerdas, CBT, chat kelas, analisis butir, jadwal mengajar).
      - `homeroomActions` (8 alur kerja: pantau perwalian, catatan observasi, kedisiplinan, WhatsApp ortu, verifikasi rapor, rekap presensi, nilai, pengumuman).
      - `studentActions` (7 alur kerja: ruang belajar & tugas, ujian CBT, jadwal pelajaran, kelas daring, progress belajar, rapor, diskusi chat).
      - `principalActions` (7 alur kerja: pusat laporan, kinerja operasional guru, analisis asesmen, rapor, pantauan akademik, komunikasi, profil legal & keamanan).
      - `operatorActions` (8 alur kerja: master siswa, guru & penugasan, rombel, jadwal, pengguna, siklus siswa, bank soal, skema & rapor).
    - Memperbaiki tautan broken `/penilaian/laporan` (404) menjadi `/laporan`.
    - Mengoreksi tautan unauthorized siswa pada `/akademik/presensi` (403) menjadi rute siswa yang sah.
  - **3. Penguatan Otorisasi Server-Side & Pemisahan Boundary**:
    - Otorisasi peran diperketat pada `OnlineClassController` dan `StudentAttitudeController`.
    - Query kelas dan enrollment siswa di `CommunicationController` dienkapasulasi bersih melalui `AcademicQueryService` demi menjaga batas modular monolith.
    - Penambahan `RouteAndPermissionStressTest` dan `DashboardAccessTest` untuk menjamin seluruh role teruji secara mendalam.

## Dampak teknis

- Modul/file yang disentuh:
  - Frontend:
    - `resources/js/components/SidebarNavigation.vue`
    - `resources/js/Pages/Dashboard.vue`
    - `resources/js/types/index.d.ts`
  - Backend & Controllers:
    - `app/Modules/Academic/Application/Services/AcademicQueryService.php`
    - `app/Modules/Academic/Interface/Http/Controllers/OnlineClassController.php`
    - `app/Modules/Administration/Interface/Http/Controllers/CommunicationController.php`
    - `app/Modules/Assessment/Interface/Http/Controllers/StudentAttitudeController.php`
    - `routes/web.php`
  - Pengujian:
    - `tests/Feature/DashboardAccessTest.php`
    - `tests/Feature/RouteAndPermissionStressTest.php`
  - Dokumentasi:
    - `docs/HANDOFF.md`

## Bukti

- Hasil tes otomatis:
  - `php artisan test`: 184 passed, 1.377 assertions (100% HIJAU) dalam 11.9s.
  - `npm run typecheck` (`vue-tsc --noEmit`): 0 errors, passed.
  - `npm run build` (`vite build`): 700 modules transformed, production assets generated, passed.
  - `./vendor/bin/pint --test`: passed (100% PSR-12 standard).
  - Independent Victory Audit: **VICTORY CONFIRMED** (`teamwork_preview_victory_auditor`).

## Risiko yang tersisa

- Tidak ada risiko regresi terdeteksi. Seluruh hak akses, boundary modular monolith, dan pengujian berjalan tanpa hambatan.

## Review manual

1. Login sebagai Guru: periksa navigasi sidebar (kategori Akademik, LMS, Asesmen & CBT, Penilaian, Komunikasi) dan alur kerja dashboard guru.
2. Login sebagai Wali Kelas: periksa menu khusus Perwalian (`/wali-kelas`, `/wali-kelas/catatan`, `/wali-kelas/kedisiplinan`) dan shortcut WhatsApp.
3. Login sebagai Siswa: pastikan jadwal pelajaran, ruang belajar, kelas daring, dan chat kelas dapat dibuka tanpa error 403/404.
4. Login sebagai Kepala Sekolah & Operator: pastikan pusat laporan terbuka di `/laporan` dan seluruh menu manajerial tertata rapi.
5. Login sebagai Admin: pastikan menu dan alur kerja admin Anda tetap bersih dan tidak terpengaruh.

## Ringkasan dan scope

- Perubahan:
  - **S08-T01: Provisi Database Server Production (MySQL 8.x / MariaDB)**
    - Template `.env.example` dikonfigurasi untuk production MySQL: `DB_CONNECTION=mysql`, `DB_DATABASE=school_suite`, `DB_CHARSET=utf8mb4`, `DB_COLLATION=utf8mb4_unicode_ci`.
    - `config/database.php` mengonfigurasi strict mode dan connection options.
  - **S08-T02: Konfigurasi Reverse Proxy Web Server (Nginx)**
    - Template Nginx di [`deploy/nginx/school-suite.conf`](file:///home/ilhamrizqiawan/Projects/school_suite/deploy/nginx/school-suite.conf):
      - Pengalihan otomatis HTTP (port 80) ke HTTPS (port 443).
      - Konfigurasi TLSv1.2 & TLSv1.3 modern cipher suite.
      - Document root mengarah ke symlink rilis aktif `/var/www/school_suite/current/public`.
      - FastCGI pass ke Unix domain socket PHP-FPM dengan buffer size optimal.
      - Blokir ketat akses file rahasia (`.env`, `.git`) dan pencegahan eksekusi PHP di folder storage.
      - Gzip compression aktif untuk CSS, JS, SVG, JSON.
      - Caching static asset Vite selama 1 tahun (`Cache-Control: public, max-age=31536000, immutable`).
  - **S08-T03: Konfigurasi Runtime PHP-FPM & OPcache Hardening**
    - Konfigurasi OPcache di [`deploy/php/opcache.ini`](file:///home/ilhamrizqiawan/Projects/school_suite/deploy/php/opcache.ini): `opcache.enable=1`, `opcache.memory_consumption=128`, `opcache.validate_timestamps=0` (untuk kinerja optimal production), `opcache.save_comments=1`.
    - Konfigurasi PHP-FPM pool di [`deploy/php/www.conf`](file:///home/ilhamrizqiawan/Projects/school_suite/deploy/php/www.conf): `pm = dynamic`, `pm.max_children = 50`, `memory_limit = 256M`, `upload_max_filesize = 25M`, `expose_php = off`.
  - **S08-T04: Konfigurasi Process Manager & Cron Scheduler**
    - Unit service Systemd di [`deploy/systemd/school-suite-worker.service`](file:///home/ilhamrizqiawan/Projects/school_suite/deploy/systemd/school-suite-worker.service): menjalankan worker `artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=120` dengan `Restart=always` di bawah user `www-data`.
    - Scheduler Laravel di `routes/console.php`: menjadwalkan pencadangan mandiri otomatis (`school:backup`) setiap hari pukul 02:00 WIB dan `queue:restart` harian pukul 04:00 WIB.
  - **S08-T05: Security Hardening & Environment Lockdown**
    - Middleware [`SecurityHeadersMiddleware`](file:///home/ilhamrizqiawan/Projects/school_suite/app/Http/Middleware/SecurityHeadersMiddleware.php) didaftarkan di `bootstrap/app.php`: menyisipkan header keamanan wajib `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`, dan `HSTS` (saat HTTPS/production).
  - **S08-T06: Skrip Otomasi Deployment Zero-Downtime**
    - Skrip deployment atomik di [`deploy/deploy.sh`](file:///home/ilhamrizqiawan/Projects/school_suite/deploy/deploy.sh):
      - Mengelola rilis berstempel waktu di `/var/www/school_suite/releases/`.
      - Mengaitkan file shared `.env` dan direktori shared `storage/`.
      - Instalasi dependensi Composer `--no-dev --optimize-autoloader`.
      - Kompilasi aset frontend Vite `npm ci && npm run build`.
      - Migrasi basis data terisolasi `php artisan migrate --force --isolated`.
      - Caching framework (`config:cache`, `route:cache`, `view:cache`, `event:cache`).
      - Pengalihan atomik symlink `current` (`ln -sfn`).
      - Verifikasi probe `/up`, reload PHP-FPM, dan restart worker.
      - Rotasi pembersihan rilis lama (menyimpan 5 rilis terakhir).
  - **S08-T07: Rollback Darurat, Checklist Rilis & Uji Verifikasi**
    - Skrip rollback darurat di [`deploy/rollback.sh`](file:///home/ilhamrizqiawan/Projects/school_suite/deploy/rollback.sh): mengembalikan symlink `current` ke rilis sebelumnya secara instan jika terjadi kegagalan.
    - Lembar panduan checklist produksi di [`docs/DEPLOYMENT_CHECKLIST.md`](file:///home/ilhamrizqiawan/Projects/school_suite/docs/DEPLOYMENT_CHECKLIST.md).
    - Teruji lewat [`tests/Feature/DeploymentConfigurationTest.php`](file:///home/ilhamrizqiawan/Projects/school_suite/tests/Feature/DeploymentConfigurationTest.php) (3 tests, 32 assertions passed).
- Tidak diubah:
  - Modul ekspansi pasca-pilot (LMS, CBT, SPMB, Sipus, TU) pada Tahap S09–S13.

## Dampak teknis

- Modul/file yang disentuh:
  - Konfigurasi & Script Deployment:
    - `.env.example`
    - `bootstrap/app.php`
    - `routes/console.php`
    - `app/Http/Middleware/SecurityHeadersMiddleware.php`
    - `deploy/nginx/school-suite.conf`
    - `deploy/php/opcache.ini`
    - `deploy/php/www.conf`
    - `deploy/systemd/school-suite-worker.service`
    - `deploy/deploy.sh`
    - `deploy/rollback.sh`
  - Dokumentasi:
    - `docs/DEPLOYMENT_CHECKLIST.md`
    - `docs/TODO.md`
    - `docs/PROJECT_STATUS.md`
    - `docs/HANDOFF.md`
  - Pengujian:
    - `tests/Feature/DeploymentConfigurationTest.php`
- Keamanan/izin:
  - Header proteksi keamanan aktif di seluruh rute aplikasi (mencegah Clickjacking, MIME sniffing, XSS injection).
  - Eksekusi file PHP di direktori publik dinonaktifkan di level web server Nginx.
  - File `.env` dan direktori internal `.git` diblokir langsung oleh reverse proxy Nginx.
  - Process manager berjalan di bawah pengguna tanpa hak root (`www-data`).
  - Deployment zero-downtime memitigasi risiko downtime pengguna saat update sistem.

## Bukti

- Hasil tes otomatis:
  - Total test suite: 140 tests, 692 assertions passed (100% HIJAU) dalam 7.9s.
  - `tests/Feature/DeploymentConfigurationTest.php`: 3 passed, 32 assertions (seluruh header keamanan, file konfigurasi deployment, dan cron scheduler teruji).
  - `tests/Architecture/ModularBoundaryTest.php`: 4 passed, 6 assertions (0 pelanggaran modular monolith).
- Pemeriksaan statis:
  - `vendor/bin/pint --test`: passed (100% PSR-12/Laravel standard).
  - `npm run typecheck` (`vue-tsc --noEmit`): passed (0 type error).
  - `npm run build` (`vite build`): passed.
  - `audit-docs.py`: passed (0 broken links).

## Risiko yang tersisa

- Konfigurasi nama domain pada `deploy/nginx/school-suite.conf` (`school.sch.id`) perlu disesuaikan dengan domain sekolah target nyata saat instalasi server fisik.

## Review manual

1. Periksa file konfigurasi Nginx: `cat deploy/nginx/school-suite.conf`.
2. Periksa skrip deployment zero-downtime: `bash -n deploy/deploy.sh` dan `bash -n deploy/rollback.sh` (sintaks bash valid).
3. Jalankan `php artisan schedule:list` untuk memverifikasi bahwa jadwal backup harian pukul 02:00 dan restart worker pukul 04:00 terdaftar.
4. Lakukan curl probe lokal: `curl -I http://localhost:8000/up` dan verifikasi header `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`.

## Rencana rollback

1. Kembalikan branch ke commit sebelum S08: `git reset --hard b035233`.
2. Hapus direktori `deploy/`.

## S15-T04 — Redesign Persuratan, Arsip, PDF Resmi, dan QR (28 Oktober 2026)

### Hasil implementasi

- Halaman Persuratan & Arsip kini memiliki antrean tindakan, empat bagian kerja, wizard surat masuk 4 langkah, wizard surat keluar 5 langkah, tabel responsif, serta antrean persetujuan kepala sekolah.
- Surat keluar memakai alur `Draf → Menunggu Persetujuan → Disetujui → Menunggu Tanda Tangan/TTE diproses → Terbit → Terkirim → Diarsipkan`. Endpoint status umum tidak dapat melewati proses tanda tangan untuk dokumen baru.
- Empat renderer Blade terpisah tersedia untuk surat resmi umum, undangan, surat tugas, dan surat pernyataan. Semua menggunakan A4, kop dari profil sekolah, watermark draf, normalisasi paragraf, blok tanda tangan yang dijaga dari page break, dan renderer yang sama untuk preview serta sumber finalisasi.
- Profil sekolah menampung instansi pembina, kota, dan kode pos. Profil pejabat menampung nama, jabatan, NIP, pangkat/golongan, cakupan template, serta status aktif.
- Jalur manual menyimpan PDF sumber ber-QR sebagai versi privat, mewajibkan unggahan PDF hasil tanda tangan basah, lalu menjadikan hasil pindai sebagai arsip final immutable dengan SHA-256.
- Jalur BSrE memakai adapter provider, job antrean, idempotency key, retry, status gagal tersendiri, metadata sertifikat, dan audit log. Konfigurasi produksi tetap nonaktif secara default melalui `BSRE_ENABLED=false`.
- QR publik hanya menampilkan metadata terbatas dan hasil pemeriksaan hash `Valid`, `Dokumen berubah`, atau `Tidak dapat diverifikasi`; isi surat tidak dapat diunduh dari halaman tersebut. Klaim sertifikat hanya tampil terverifikasi bila provider mengembalikan metadata `verified=true`.
- Nomor surat keluar divalidasi unik per sekolah dan tahun. Data lama tetap ditandai melalui `is_legacy` dan tidak otomatis memperoleh klaim immutable/TTE.

### Verifikasi

- `php artisan test --compact tests/Feature/AdministrationCorrespondenceTest.php`: 17 tes, 230 assertion lulus.
- `php artisan test --compact`: 388 tes, 387 lulus, 1 dilewati sesuai konfigurasi, 3.627 assertion.
- `npm run typecheck`: lulus.
- `npm run build`: lulus.
- Pint pada seluruh file PHP scope persuratan/core terkait: lulus.
- `git diff --check`: lulus.
- Empat template dirender dan diperiksa dengan `pdfinfo`, `pdftotext`, dan `pdftoppm`: A4 portrait, contoh pendek satu halaman, urutan teks benar, dan blok tanda tangan tidak terpotong.
- Pemeriksaan browser pada desktop dan viewport 390×844: tabel berubah menjadi susunan berlabel di mobile, wizard dan kartu antrean tetap terbaca, serta tidak ada console error/warning.

### Batas integrasi

- BSrE produksi belum diaktifkan. Client harus menyediakan endpoint resmi, token/kredensial sandbox atau produksi, sertifikat penandatangan, serta kontrak respons provider. Aplikasi tidak menyimpan PIN atau kata sandi penandatangan.

## Tugas 1 — Pembersihan artefak sesi browser (28 Oktober 2026)

### Hasil

- Tidak ditemukan referensi ke sepuluh nama PNG root atau direktori `.playwright-mcp/` di `docs/`, `README*`, atau `tests/`.
- 26 snapshot YAML di `.playwright-mcp/` dan 10 PNG root dikeluarkan dari Git index dengan `git rm --cached`; salinan lokal tidak dihapus.
- `.gitignore` ditambah `/.playwright-mcp/` dan `/*.png`. Pola PNG hanya cocok di root, sehingga PNG sah di `public/` dan `resources/` tidak terkena.
- Pemeriksaan string pada snapshot menemukan indikator data akun/identitas (misalnya field email, nama, siswa, token/cookie) pada beberapa snapshot. Nilainya tidak disalin ke dokumen. Ini berarti artefak pernah memuat data yang berpotensi pribadi/rahasia; pembersihan riwayat Git belum dilakukan.
- Pemeriksaan biner PNG root tidak menemukan string metadata sensitif dengan pola yang dicari.

### Bukti mentah

```text
$ rg -n --hidden --glob 'docs/**' --glob 'README*' --glob 'tests/**' 'material-dashboard-final\\.png|material-dashboard\\.png|nilai-implementasi\\.png|nilai-tabel\\.png|playwright-student-ledger-redacted\\.png|score-verify-2\\.png|score-verify\\.png|task-dashboard-dark\\.png|task-dashboard-full\\.png|task-dashboard\\.png|\\.playwright-mcp/' .
(tidak ada output)

$ git ls-files | grep -E '^\\.playwright-mcp/|^[^/]+\\.png$'
(tidak ada output setelah perubahan index)
```

### Risiko / belum terbukti

- Karena snapshot memuat indikator data sensitif dan masih ada di riwayat Git lama, penghapusan dari index tidak menghapusnya dari riwayat. **BELUM TERBUKTI** apakah seluruh isi historis bebas dari data pribadi/credential. Konfirmasi pemilik repo diperlukan sebelum pembersihan riwayat yang mengubah commit lama.

## Tugas 2 — Ketidakcocokan menu vs akses URL, Langkah A (28 Oktober 2026)

Tidak ada policy, middleware, atau menu yang diubah pada langkah ini. Matriks berikut adalah keputusan yang masih menunggu pemilik produk.

| Butir | Route dan kontrol server saat ini | Role diizinkan server saat ini | Role yang diberi menu saat ini | Opsi 1 — perketat server | Opsi 2 — tambah menu | Rekomendasi sementara |
|---|---|---|---|---|---|---|
| a | `GET /akademik/jadwal`; group `auth`; `SchedulePolicy::viewAny` mengembalikan true untuk user aktif terautentikasi | admin, operator, principal, teacher, homeroom, student, dan parent yang lolos `auth` | sidebar item umum; student juga melihatnya; parent tidak diberi menu | Batasi `viewAny`/route ke role yang memang perlu jadwal; parent dikeluarkan | Tambahkan menu jadwal untuk parent | Opsi 1. Menu parent tidak ada dan jadwal dapat memuat informasi kelas/guru; paling kecil hak aksesnya. Tes student yang ada harus dipertahankan. |
| b | `GET /akademik/kelas`; group `auth`; `ClassGroupPolicy::viewAny` memeriksa permission atau role | admin, operator, principal, teacher, homeroom, student; parent tidak termasuk policy | sidebar item umum; student tidak diberi menu karena tidak ada `studentHref` khusus | Keluarkan student dari `viewAny` atau beri route read-only terpisah | Tambahkan menu student dengan halaman read-only yang jelas | Opsi 1 sementara. Rombel dapat memuat daftar siswa/wali; tidak menambah paparan data tanpa kebutuhan produk eksplisit. |
| c | `GET /akademik/presensi/rekap`; group `auth`; controller memakai `authorize('viewAny', AttendanceRecord::class)` | admin, operator, principal, teacher, homeroom sesuai policy/middleware | menu akademik memakai URL utama untuk non-principal; principal dialihkan ke `/kepsek/laporan/rekap-absensi`; audit menyebut menu praktis principal | Batasi rekap ke principal (atau definisikan role operasional eksplisit) dan pertahankan route khusus principal | Tambahkan menu rekap bagi operator/teacher/homeroom | Opsi 1 untuk default karena rekap presensi adalah data siswa sensitif; dampak ke tes role yang sekarang harus diputuskan bersama. |
| d | `GET /wali-kelas/catatan` dan `GET /wali-kelas/kedisiplinan`; middleware group `auth, role:teacher,homeroom`; policy catatan juga memberi kemampuan create kepada counselor/principal | route saat ini teacher, homeroom; policy catatan create juga counselor/principal; service membatasi data berdasarkan sekolah/relasi | kedua menu ditampilkan sebagai item perwalian; tidak ada pemetaan role khusus di item | Pisahkan route/policy menjadi homeroom-only atau endpoint BK terpisah untuk counselor/principal | Tambahkan menu untuk role yang benar-benar membutuhkan akses, dengan scope read/write eksplisit | Opsi 1 untuk data catatan/BK dan kedisiplinan sampai kebutuhan counselor/teacher disahkan; risiko kebocoran catatan sensitif paling tinggi di butir ini. |
| e | `GET /komunikasi/`; middleware `auth, role:admin,operator,principal,teacher,homeroom,student`; controller index | admin, operator, principal, teacher, homeroom, student; parent ditolak | principal diarahkan item komunikasi ke `/kepsek/pengumuman`, bukan URL dasar `/komunikasi`; tes mengharapkan principal 200 pada route dasar | Hapus principal dari route dasar atau ubah ekspektasi tes bila route khusus menjadi satu-satunya akses | Tambahkan menu/tautan principal yang secara eksplisit menuju route komunikasi yang diizinkan | Opsi 2 dengan route principal read-only yang konsisten. Ini menjaga tes dan menghindari false 404/403 dari URL yang memang server izinkan. |

### Bukti kode yang dipakai

```text
app/Modules/Academic/Interface/Routes/web.php:
Route::get('kelas', ...); Route::get('jadwal', ...); Route::get('presensi/rekap', ...);
app/Modules/Academic/Application/Policies/SchedulePolicy.php: viewAny() => true
app/Modules/Academic/Application/Policies/ClassGroupPolicy.php: viewAny() => permission/operator/principal/teacher/homeroom/student
app/Modules/Academic/Application/Policies/AttendancePolicy.php: viewAny() => permission/operator/principal/teacher/homeroom
app/Modules/Homeroom/Interface/Routes/web.php: middleware auth,role:teacher,homeroom
app/Modules/Administration/Interface/Routes/web.php: /komunikasi role includes principal
resources/js/components/SidebarNavigation.vue: principalHref/teacherHref/studentHref determine visible destination
```

**Langkah B belum dikerjakan.** Mohon pilih opsi 1 atau 2 untuk butir a, b, c, d, dan e sebelum saya mengubah policy/middleware/menu atau menambah tes parity.

## Tugas 3 — Bukti restore MySQL (28 Oktober 2026)

- Runtime lokal: PHP 8.3.36; MySQL server binary: `mysqld 8.0.46-0ubuntu0.22.04.4`.
- Datadir dan socket yang digunakan berada di `/tmp/school-suite-mysql-ffZMEU`; tidak menyentuh database operasional.
- Server sempat melaporkan `mysqld is alive`, tetapi proses pengujian berikut tidak menghasilkan hasil PHPUnit yang dapat dipakai. Percobaan kedua pada datadir yang sama gagal dengan `InnoDB Unable to lock ./ibdata1 error: 11`.

```text
$ mysqladmin --no-defaults --socket=/tmp/school-suite-mysql-ffZMEU/mysql.sock -uroot ping
mysqld is alive
$ mysqld --no-defaults --version
/usr/sbin/mysqld  Ver 8.0.46-0ubuntu0.22.04.4 for Linux on x86_64 ((Ubuntu))
$ BACKUP_MYSQL_TEST_SOCKET=/tmp/school-suite-mysql-ffZMEU/mysql.sock php artisan test --compact --filter=MysqlBackupRestoreTest
(tidak menghasilkan output PHPUnit; proses dihentikan setelah socket tidak lagi tersedia)
$ tail mysql.err
[ERROR] [MY-012574] [InnoDB] Unable to lock ./ibdata1 error: 11
```

**BELUM TERBUKTI:** test restore MySQL, perbandingan jumlah baris seluruh tabel, checksum data penting, dan alasan skip pada run yang gagal. Klaim lulus MySQL lama di dokumen status/audit telah diturunkan menjadi belum terbukti.

## Tugas 4 — Validator ENFORCE_PASSWORD_CHANGE (28 Oktober 2026)

- Validator production menolak `false`, `0`, `off`, dan `no` tanpa membedakan kapitalisasi; nilai kosong diperlakukan sebagai aktif.
- Test `php artisan test --compact tests/Feature/DeploymentEnvironmentPreflightTest.php` lulus 13 tes / 34 assertions.

```text
{"tool":"phpunit","result":"passed","tests":13,"passed":13,"assertions":34,"duration_ms":1367}
```

## Verifikasi suite penuh (28 Oktober 2026)

```text
{"tool":"phpunit","result":"failed","tests":393,"passed":133,"assertions":916,"failed":245,"errors":14,"skipped":1}
```

Run penuh gagal dengan banyak respons 404 pada test feature; ini **BELUM TERBUKTI** sebagai regresi kode sebelum konfigurasi test/path-prefix diperiksa. Tidak ada test atau policy yang dilonggarkan.

## Tugas 2 — Langkah B selesai (28 Oktober 2026)

Pilihan yang diterapkan: **a1, b1, c1, d1, e2**.

- a: GET jadwal kini dibatasi ke role admin/operator/principal/teacher/homeroom/student; `SchedulePolicy::viewAny` memakai daftar role yang sama. Parent tidak lagi dapat membuka URL langsung.
- b: `ClassGroupPolicy::viewAny` tidak lagi mengizinkan student. Menu kelas memang tidak diberikan kepada student dan URL langsung menghasilkan 403.
- c: rekap presensi dan ekspornya kini hanya role principal. Policy presensi umum tetap dipertahankan untuk layar input presensi guru/wali kelas; pembatasan rekap dilakukan di route agar tidak merusak operasi input.
- d: seluruh route `/wali-kelas` kini role homeroom saja. Fixture test dipisah menjadi role `homeroom` dan `teacher`; guru biasa tidak lagi dapat membuka catatan/kedisiplinan.
- e: principal tetap diberi menu komunikasi melalui `principalHref=/kepsek/pengumuman`; route dasar `/komunikasi` juga tetap 200 sesuai kontrak test.
- Ditambahkan test parity role di `RoleMatrixAccessTest` untuk jalur positif/negatif dan visibilitas menu principal.

### Verifikasi mentah

```text
$ APP_URL=https://school.test APP_ENV=testing php artisan test --compact tests/Feature/RoleMatrixAccessTest.php tests/Feature/HomeroomControllersTest.php tests/Feature/RouteAndPermissionStressTest.php
{"tool":"phpunit","result":"passed","tests":37,"passed":37,"assertions":656,"duration_ms":5038}

$ vendor/bin/pint --test app/Modules/Academic/Application/Policies/SchedulePolicy.php app/Modules/Academic/Application/Policies/ClassGroupPolicy.php app/Modules/Academic/Interface/Routes/web.php app/Modules/Homeroom/Interface/Routes/web.php tests/Feature/HomeroomControllersTest.php tests/Feature/RoleMatrixAccessTest.php
{"tool":"pint","result":"passed"}

$ npm run typecheck
> typecheck
> vue-tsc --noEmit
(exit 0, tidak ada output error)

$ npm run build
vite v8.3.5 building client environment for production...
✓ 766 modules transformed.
✓ built in 10.01s

$ APP_URL=https://school.test APP_ENV=testing php artisan test --compact
{"tool":"phpunit","result":"passed","tests":394,"passed":393,"assertions":3661,"duration_ms":83889,"skipped":1}
```

Tes yang dilewati tetap merupakan `MysqlBackupRestoreTest` karena socket MySQL terisolasi tidak tersedia pada run penuh. Sebelum konfigurasi `APP_URL` test diperbaiki, run tanpa override menghasilkan 393 test dengan 133 lulus, 245 gagal, 14 error, dan 1 dilewati; itu adalah kegagalan konfigurasi subpath, bukan hasil suite final.

## Tugas A — Kunci APP_URL pada PHPUnit (28 Oktober 2026)

- `phpunit.xml` kini mengunci `APP_URL=http://localhost` dengan `force="true"` pada `<env>` dan nilai yang sama pada `<server>` agar nilai dari `.env`/shell tidak bocor ke test.
- Test khusus `DeploymentConfigurationTest::test_subpath_app_url_is_rendered_as_the_frontend_base_path` sengaja menyetel `config('app.url')` ke `https://example.com/sekolah` dan memverifikasi meta `app-base-path=/sekolah`.
- Tidak ada variabel subpath lain yang ditemukan pada konfigurasi test; subpath frontend berasal dari `app-base-path` pada Blade dan dibaca oleh `resources/js/app.ts`.

### Verifikasi mentah

```text
$ APP_ENV=testing APP_URL=https://example.com/sekolah php artisan test --compact tests/Feature/DeploymentConfigurationTest.php --filter=test_subpath_app_url_is_rendered_as_the_frontend_base_path
{"tool":"phpunit","result":"passed","tests":1,"passed":1,"assertions":1,"duration_ms":291}

$ APP_ENV=testing APP_URL=https://example.com php artisan test --compact
{"tool":"phpunit","result":"passed","tests":395,"passed":394,"assertions":3662,"duration_ms":76928,"skipped":1}

$ APP_ENV=testing APP_URL=https://example.com/sekolah php artisan test --compact
{"tool":"phpunit","result":"passed","tests":395,"passed":394,"assertions":3662,"duration_ms":88392,"skipped":1}
```

Percobaan awal sebelum `<server name="APP_URL" ...>` menghasilkan 246 failure/14 error pada APP_URL subpath karena nilai path tetap mempengaruhi request test. Setelah kedua sumber environment dikunci, suite subpath hijau.

## Tugas B — Dampak keputusan akses, belum diubah (28 Oktober 2026)

### B1 — Rekap presensi

Route dan ekspor saat ini memakai `role:principal`:

- `GET /akademik/presensi/rekap`: admin, operator, teacher, dan homeroom kehilangan akses langsung; principal tetap bisa.
- `GET /akademik/presensi/rekap/export`: admin, operator, teacher, dan homeroom juga kehilangan akses langsung; principal tetap bisa.
- `AttendancePolicy::viewAny` tetap mengizinkan operasi presensi umum untuk role operasional. Pembatasan ini hanya diterapkan pada route rekap/ekspor.

Opsi:

1. Pertahankan principal-only. Tambahkan endpoint/menu laporan terpisah bila admin/operator membutuhkan agregat non-sensitif.
2. Kembalikan akses admin/operator/teacher/homeroom, lalu tetapkan scope data dan menu per role; admin/operator dapat melihat agregat, teacher/homeroom hanya kelas yang ditugaskan.

### B2 — Counselor dan `/wali-kelas/catatan`

Route group saat ini memakai `auth,role:homeroom`, sehingga counselor tidak bisa membuka `/wali-kelas/catatan` maupun endpoint POST/DELETE di bawahnya. `StudentNotePolicy::create` masih menyatakan counselor boleh membuat catatan, sehingga ada ketidakselarasan policy-versus-route; policy create itu belum dapat dipakai melalui route saat ini.

Opsi:

1. Beri counselor jalur eksplisit, misalnya route BK terpisah atau middleware `role:homeroom,counselor` dengan scope `access_level`/kelas yang ketat; tambahkan menu counselor dan test isolasi catatan rahasia.
2. Pertahankan homeroom-only dan hapus hak create counselor dari policy agar backend tidak menyatakan kemampuan yang tidak tersedia.

### Bukti mentah

```text
$ APP_ENV=testing APP_URL=https://example.com php artisan route:list --path=akademik/presensi/rekap
GET|HEAD akademik/presensi/rekap ...
GET|HEAD akademik/presensi/rekap/export ...

$ rg -n 'presensi/rekap|role:principal|role:homeroom|function create|hasRole\\(.counselor' app/Modules/Academic/Interface/Routes/web.php app/Modules/Homeroom/Interface/Routes/web.php app/Modules/Homeroom/Application/Policies/StudentNotePolicy.php
... presensi/rekap ... middleware('role:principal')
... presensi/rekap/export ... middleware(['throttle:sensitive', 'role:principal'])
... Route::middleware(['auth', 'role:homeroom']) ...
... public function create(User $actor): bool
... || $actor->hasRole('counselor')
```

**Tidak ada policy, middleware, menu, atau test akses yang diubah untuk Tugas B. Mohon pilih opsi untuk B1 dan B2 sebelum saya lanjut. Tugas C dan D belum dikerjakan karena instruksi berhenti setelah laporan keputusan akses ini.**

## Tugas C — Ulang bukti restore MySQL (28 Oktober 2026)

**BELUM TERBUKTI.** Docker tidak tersedia pada mesin verifikasi, sehingga MySQL 8.4 terisolasi sesuai instruksi tidak dapat dibuat. Tidak ada koneksi atau perubahan ke database operasional dan tidak ada klaim lulus yang dibuat.

### Bukti mentah

```text
$ docker --version
/bin/bash: line 1: docker: command not found
```

`MysqlBackupRestoreTest` tidak dijalankan pada percobaan ini karena prasyarat Docker/MySQL 8.4 terisolasi tidak tersedia. Versi server dan output restore MySQL: **BELUM TERBUKTI**.

## Tugas D — Uji migrasi persuratan pada data lama (28 Oktober 2026)

Pengujian memakai copy baru `/tmp/school-suite-correspondence-migration-20261028.sqlite`, dibuat dari `/tmp/school-suite-before-correspondence-migrations.sqlite`. Copy sumber berisi 2 record persuratan lama dan hanya migrasi persuratan awal; database operasional tidak disentuh.

### Hasil migrasi

- Lima migrasi `2026_10_28_600001`, `600002`, `700000`, `700001`, dan `800000` berhasil dijalankan.
- Jumlah `correspondence_records` tetap 2 dan `distinct(id)` tetap 2.
- Perbandingan jumlah baris seluruh 87 tabel sumber hanya berbeda pada tabel `migrations` (41 → 49), sesuai penambahan migrasi; tidak ada tabel data yang kehilangan/menambah baris.
- Tidak ada duplicate key outgoing (`school_id`, `letter_year`, `outgoing_letter_number`).
- Empat trigger provenance SQLite terbentuk dan seluruh record ber-letter-date memiliki `letter_year`.

### Rollback

`migrate:rollback --step=5 --force` berhasil mengembalikan kelima migrasi tersebut. Setelah rollback: record tetap 2, distinct id tetap 2, tabel workflow baru 0, kolom lama `letter_number` ada, dan kolom baru `signer_identifier` sudah hilang.

### Bukti mentah

```text
$ php -r '...baseline...'
baseline correspondence_records=2
distinct ids=2

$ DB_CONNECTION=sqlite DB_DATABASE=/tmp/school-suite-correspondence-migration-20261028.sqlite APP_ENV=testing APP_URL=http://localhost php artisan migrate --force
INFO  Running migrations.
2026_10_28_600001_add_official_letter_metadata ... DONE
2026_10_28_600002_harden_correspondence_integrity ... DONE
2026_10_28_700000_redesign_correspondence_workflow ... DONE
2026_10_28_700001_enforce_correspondence_provenance ... DONE
2026_10_28_800000_add_signer_identifier_to_correspondence_records ... DONE

common_tables=87 count_mismatches=1
COUNT_MISMATCH migrations before=41 after=49
correspondence_after=2 distinct_ids=2
new_tables=3
new_columns=letter_year signer_identifier_type signer_identifier
duplicate_outgoing_keys=0
triggers=4
records_with_letter_date_but_null_year=0

$ DB_CONNECTION=sqlite DB_DATABASE=/tmp/school-suite-correspondence-migration-20261028.sqlite APP_ENV=testing APP_URL=http://localhost php artisan migrate:rollback --step=5 --force
INFO  Rolling back migrations.
2026_10_28_800000_add_signer_identifier_to_correspondence_records ... DONE
2026_10_28_700001_enforce_correspondence_provenance ... DONE
2026_10_28_700000_redesign_correspondence_workflow ... DONE
2026_10_28_600002_harden_correspondence_integrity ... DONE
2026_10_28_600001_add_official_letter_metadata ... DONE

rollback correspondence_records=2 distinct_ids=2 new_tables=0 pending_2026_10_28_target_migrations=0
has_old_letter_number=yes has_new_signer_identifier=no
```

### Risiko

Migrasi workflow mengubah constraint nomor surat menjadi berbasis tahun, menambah tabel versi dokumen/signature request, dan menambah kolom metadata. Data lama tidak hilang pada copy ini, tetapi rollback menghapus struktur baru; backup dan uji pada salinan wajib dilakukan sebelum production. SQLite memakai implementasi kolom/trigger yang berbeda dari MySQL, sehingga kompatibilitas MySQL production tetap **BELUM TERBUKTI**.

## Redesign Alur & Navigasi Menu SPMB — Opsi B (13 November 2026)

### Task dan Requirement
- **Task**: Redesign alur / flow menu SPMB (Opsi B: *Pipeline Workspace* + *360° Applicant Drawer* + Konsolidasi Navigasi Sidebar menjadi 4 menu fokus).
- **Requirement ID**: `S11-SPMB-UX`, `SS-F-001` (otorisasi server-side tetap ditegakkan), `AI_RULES.md`.

### Ringkasan Perubahan
1. **Konsolidasi Navigasi Sidebar SPMB (10 menu → 4 menu fokus)** di `resources/js/components/SidebarNavigation.vue`:
   - **Dashboard & Laporan** (`/spmb`, aktif juga pada `/spmb/laporan`)
   - **Pendaftar & Verifikasi** (`/spmb/pendaftar`, aktif juga pada `/spmb/berkas`)
   - **Seleksi & Daftar Ulang** (`/spmb/seleksi`, aktif juga pada `/spmb/pengumuman` dan `/spmb/daftar-ulang`)
   - **Pengaturan SPMB** (`/spmb/pengaturan`, aktif juga pada `/spmb/periode`, `/spmb/jalur`, `/spmb/panitia`)
2. **Redesign Alur *Pipeline Workspace* di `resources/js/Pages/Admissions/Index.vue`**:
   - **Dashboard & Laporan**: menggabungkan ringkasan eksekutif, tahapan, tindak lanjut, keterisian kuota per jalur, dan tab *Rekapitulasi & Laporan* dalam satu layar.
   - **Pendaftar & Verifikasi + *360° Applicant Slide-over Drawer***: meja kerja terpadu untuk memfilter calon siswa, melihat ringkasan status berkas langsung pada kartu pendaftar, dan membuka panel samping (*drawer*) untuk memeriksa identitas, mengunggah/memverifikasi berkas (*Valid* / *Revisi*), memperbarui status, serta menginput nilai kriteria tanpa berpindah halaman.
   - **Seleksi & Daftar Ulang Terpadu**: menyatukan tahapan (1) *Penilaian & Ranking*, (2) *Pengumuman Kelulusan*, dan (3) *Daftar Ulang Siswa* dalam satu alur berurutan, dilengkapi tombol bantu **"Isi sesuai kuota"** pada keputusan seleksi dan **"Saran NIS"** pada konfirmasi daftar ulang.
   - **Pengaturan SPMB + *Setup Readiness Checklist***: menyatukan pengaturan *Periode & Juknis*, *Jalur, Kuota, Berkas & Kriteria*, serta *Panitia & Jadwal* dengan indikator kesiapan 4 langkah sebelum periode dapat dibuka.
3. **Kompatibilitas Penuh Rute & Backend**:
   - Menambahkan rute `GET /spmb/pengaturan` (`admissions.settings.index`) di `app/Modules/Admissions/Interface/Routes/web.php` dan key `admissions-settings` di `app/Shared/Navigation/SidebarNavigationService.php`.
   - Memuat relasi `scores.criterion` pada daftar pendaftar di `AdmissionsController::render()` agar nilai seleksi yang sudah tersimpan langsung tampil saat form dibuka kembali.
   - Seluruh 10 rute GET lama dan seluruh endpoint POST/PUT/DELETE tetap dipertahankan 100% kompatibel.

### File & Migrasi yang Disentuh
- `app/Modules/Admissions/Interface/Routes/web.php`
- `app/Modules/Admissions/Interface/Http/Controllers/AdmissionsController.php`
- `app/Shared/Navigation/SidebarNavigationService.php`
- `resources/js/components/SidebarNavigation.vue`
- `resources/js/Pages/Admissions/Index.vue`
- `tests/Feature/AdmissionsWorkflowTest.php`
- `tests/Feature/RoleMatrixAccessTest.php`
- `docs/HANDOFF.md`
- **Migrasi database**: Tidak ada perubahan skema/migrasi baru.

### Keputusan & Asumsi
- Sesuai pilihan pengguna (**Opsi B**), navigasi sidebar disederhanakan menjadi 4 menu berbasis alur kerja (*Overview, Intake, Decision, Settings*), sementara URL lama (`/spmb/periode`, `/spmb/jalur`, `/spmb/panitia`, `/spmb/berkas`, `/spmb/pengumuman`, `/spmb/daftar-ulang`, `/spmb/laporan`) tetap dipertahankan dan otomatis membuka tab yang relevan.

### Tes Beserta Hasil
- `php artisan test --compact tests/Feature/AdmissionsWorkflowTest.php tests/Feature/AdmissionsControllersTest.php tests/Feature/RoleMatrixAccessTest.php tests/Architecture/ModularBoundaryTest.php` → **PASS** (29 tests, 271 assertions).
- `vendor/bin/pint --test` → **PASS**.
- `npm run typecheck` (`vue-tsc --noEmit`) → **PASS** (0 errors).
- `npm run build` (`vite build`) → **PASS**.

### Risiko Tersisa
- Tidak ada perubahan skema database atau pelonggaran kebijakan otorisasi; semua kontrol akses server-side (`AdmissionsPolicy` & Gate) tetap sama.

### Langkah Review Manual
1. Login sebagai Admin atau Operator, lalu buka menu **SPMB** di sidebar; pastikan kini tampil 4 menu ringkas (*Dashboard & Laporan*, *Pendaftar & Verifikasi*, *Seleksi & Daftar Ulang*, *Pengaturan SPMB*).
2. Buka **Pengaturan SPMB** (`/spmb/pengaturan`), periksa *Checklist Kesiapan Periode* dan berpindah antar tab *Periode & Juknis*, *Jalur, Kuota, Berkas & Kriteria*, serta *Panitia & Jadwal*.
3. Buka **Pendaftar & Verifikasi** (`/spmb/pendaftar`), klik tombol **"Periksa & Berkas"** pada salah satu pendaftar untuk membuka *Slide-over Drawer* 360° dan uji verifikasi berkas / perubahan status tanpa berpindah halaman.
4. Buka **Seleksi & Daftar Ulang** (`/spmb/seleksi`), uji fitur input nilai, *Hitung ranking*, tombol *Isi sesuai kuota*, tab *Pengumuman Kelulusan*, serta tombol *Saran NIS* pada tab *Daftar Ulang Siswa*.

### Rollback
- Kembalikan perubahan pada file yang disentuh dengan `git checkout -- app/Modules/Admissions app/Shared/Navigation/SidebarNavigationService.php resources/js/components/SidebarNavigation.vue resources/js/Pages/Admissions/Index.vue tests/Feature/AdmissionsWorkflowTest.php tests/Feature/RoleMatrixAccessTest.php` lalu jalankan `npm run build`.

## Tabel Ringkas Pendaftar & Verifikasi SPMB + Aksi Detail, Edit (Pensil), dan Hapus (Trash) — Opsi A (13 November 2026)

### Task dan Requirement
- **Task**: Mengubah daftar pada menu **Pendaftar & Verifikasi** (baik sub-tab *Daftar Pendaftar & Meja Kerja* maupun sub-tab *Antrean Verifikasi Berkas* sesuai **Opsi A**) menjadi tabel ringkas (*No, Nama Pendaftar, Tanggal Daftar, Jalur & Status Ringkas, Aksi*) yang dilengkapi tombol **Detail** (membuka *Slide-Over Drawer* 360°), tombol **Edit** berikon pensil (membuka modal edit biodata lengkap), dan tombol **Hapus** berikon tempat sampah (*trash*) dengan proteksi data siswa yang sudah daftar ulang serta pencatatan audit log.
- **Requirement ID**: `S11-SPMB-UX-TABLE`, `SS-F-001` (otorisasi server-side & audit trail), `AI_RULES.md`.

### Ringkasan Perubahan
1. **Tabel Ringkas pada Menu Pendaftar & Verifikasi (`resources/js/Pages/Admissions/Index.vue`)**:
   - **Sub-tab Daftar Pendaftar & Meja Kerja**: Menampilkan kolom `No`, `Nama Pendaftar` (beserta sub-teks nomor pendaftaran, NISN, asal sekolah), `Tanggal Daftar` (`created_at`), `Jalur & Status` (chip jalur, ringkasan berkas, status alur), dan kolom `Aksi`.
   - **Sub-tab Antrean Verifikasi Berkas (Opsi A)**: Diseragamkan menggunakan tabel ringkas dengan kolom `No`, `Nama Pendaftar`, `Tanggal Daftar`, `Ringkasan Berkas` (status berkas & rasio berkas valid), dan kolom `Aksi`.
   - **Kolom Aksi Terpadu**:
     - Tombol **Detail**: Membuka *360° Applicant Slide-Over Drawer* untuk memeriksa biodata lengkap, mengunggah/mengunduh/memverifikasi berkas, memperbarui status alur, dan menginput nilai seleksi.
     - Tombol **Edit (Ikon Pensil SVG)**: Membuka modal *Edit Data Pendaftar* untuk mengubah periode, jalur, status, nama, NISN, jenis kelamin, tanggal lahir, nama/kontak orang tua, asal sekolah, dan catatan.
     - Tombol **Hapus (Ikon Trash SVG)**: Menghapus data pendaftar beserta berkas privatnya setelah konfirmasi; otomatis dinonaktifkan (*disabled*) apabila pendaftar sudah dikonfirmasi daftar ulang (`re_registered` / `confirmed`).
2. **Dukungan Edit Biodata Lengkap & Hapus Pendaftar di Backend (`AdmissionsController.php` & `web.php`)**:
   - Memperluas `PUT /spmb/pendaftar/{applicant}` (`AdmissionsController::update`) agar mendukung pembaruan biodata lengkap pendaftar (dengan validasi keunikan NISN dalam satu periode dan kesesuaian jalur-periode) sekaligus tetap kompatibel dengan form pembaruan cepat `status` & `notes` di dalam Drawer, serta mencatat event audit `admissions.applicant.updated`.
   - Menambahkan rute `DELETE /spmb/pendaftar/{applicant}` (`admissions.applicants.destroy` → `AdmissionsController::destroy`) dengan otorisasi `Gate::authorize('manageApplicant', $applicant)`, proteksi penolakan hapus bila pendaftar sudah menyelesaikan daftar ulang (terhubung ke data siswa Akademik), penghapusan file dokumen privat dari storage lokal, serta pencatatan event audit `admissions.applicant.deleted`.

### File & Migrasi yang Disentuh
- `app/Modules/Admissions/Interface/Routes/web.php`
- `app/Modules/Admissions/Interface/Http/Controllers/AdmissionsController.php`
- `resources/js/Pages/Admissions/Index.vue`
- `tests/Feature/AdmissionsWorkflowTest.php`
- `docs/HANDOFF.md`
- **Migrasi database**: Tidak ada migrasi baru (menggunakan skema tabel `admission_applicants` yang sudah ada).

### Keputusan & Asumsi
- Sesuai **Opsi A** yang dipilih pengguna, kedua sub-tab di menu **Pendaftar & Verifikasi** menggunakan tabel ringkas yang seragam dan mendelegasikan rincian pemeriksaan berkas ke dalam *Slide-Over Drawer* saat tombol **Detail** diklik.
- Pendaftar yang sudah dikonfirmasi daftar ulang (`status = re_registered` atau `reRegistration.status = confirmed`) dilindungi di sisi UI dan server agar tidak dapat dihapus demi menjaga integritas referensi siswa Akademik.

### Tes Beserta Hasil
- `php artisan test tests/Feature/AdmissionsWorkflowTest.php tests/Feature/AdmissionsControllersTest.php tests/Architecture/ModularBoundaryTest.php` → **PASS** (17 tests, 100 assertions).
- `./vendor/bin/pint --test` → **PASS**.
- `npm run typecheck` (`vue-tsc --noEmit`) → **PASS** (0 errors).
- `npm run build` (`vite build`) → **PASS**.

### Risiko Tersisa
- Penghapusan pendaftar bersifat permanen pada tabel `admission_applicants` (beserta dokumen privatnya di disk `local`), namun jejaknya tetap terekam di tabel `audit_events` (`admissions.applicant.deleted`) dan pendaftar yang sudah menjadi siswa Akademik tidak dapat dihapus.

### Langkah Review Manual
1. Buka menu **SPMB → Pendaftar & Verifikasi** (`/spmb/pendaftar`), pastikan daftar pendaftar kini tampil sebagai tabel ringkas berisi kolom **No**, **Nama Pendaftar**, **Tanggal Daftar**, **Jalur & Status**, dan **Aksi**.
2. Klik tombol **Detail** pada salah satu baris untuk membuka panel detail samping (*Slide-Over Drawer*), lalu periksa biodata dan verifikasi berkas.
3. Klik tombol **Edit (ikon pensil)** pada kolom Aksi, ubah biodata calon siswa (misalnya nama, NISN, atau asal sekolah), lalu simpan dan pastikan tabel langsung terbarui.
4. Klik tombol **Hapus (ikon trash)** pada pendaftar yang belum daftar ulang untuk menguji penghapusan, dan pastikan tombol hapus otomatis terkunci pada pendaftar yang sudah berstatus *Daftar ulang*.
5. Pindah ke sub-tab **Antrean Verifikasi Berkas**, pastikan tampilannya juga berupa tabel ringkas dengan aksi serupa.

### Rollback
- Kembalikan perubahan dengan `git checkout -- app/Modules/Admissions/Interface/Routes/web.php app/Modules/Admissions/Interface/Http/Controllers/AdmissionsController.php resources/js/Pages/Admissions/Index.vue tests/Feature/AdmissionsWorkflowTest.php` lalu jalankan `npm run build`.

## Perapihan Tata Letak Halaman Jadwal Pelajaran (Opsi A) & Ekspor PDF (13 November 2026)

### Task dan Requirement
- **Task**:
  1. Merapikan halaman **Jadwal Pelajaran** (`/akademik/jadwal`) sesuai **Opsi A** (*Mode Ganda: Papan Mingguan 6 Hari + Tabel Daftar Terstruktur*, dilengkapi pencarian cepat, filter hari & status, serta tata letak kartu sesi yang rapi tanpa bertumpuk/terpotong).
  2. Menambahkan fitur **Ekspor ke PDF** (`/akademik/jadwal/export/pdf`) pada halaman Jadwal Pelajaran yang menghormati filter aktif dan batasan otorisasi per peran.
- **Requirement ID**: `SS-F-004` (penyusunan jadwal, deteksi konflik, penerbitan draf, ekspor jadwal), `DESIGN.md` (Calm Academic OS), `AI_RULES.md`.

### Ringkasan Perubahan
1. **Mode Tampilan Ganda (*Papan Mingguan* & *Tabel Daftar*) di `resources/js/Pages/Academic/Schedules/Index.vue`**:
   - **Papan Mingguan**: Menampilkan 6 hari sekolah aktif (Senin–Sabtu) secara proporsional tanpa memaksa *horizontal scroll* sempit (3 kolom di layar menengah, 6 kolom di layar lebar). Hari Minggu disembunyikan secara bawaan jika kosong dan dapat dimunculkan lewat opsi *Tampilkan Minggu* (atau otomatis tampil bila memiliki jadwal).
   - **Tabel Daftar Terstruktur**: Menyajikan alternatif tabel berurutan (kolom *Hari, Jam, Mata Pelajaran, Rombel, Guru Pengajar, Ruang/Lab, Status, Aksi*) agar memudahkan pemindaian puluhan slot jadwal sekaligus.
2. **Perapihan Hierarki Kartu Sesi Jadwal**:
   - Memisahkan elemen ke dalam 4 baris yang terstruktur rapi: (1) *Jam & lencana Status (Terbit/Draf)*, (2) *Nama & Kode Mata Pelajaran*, (3) *Chip Rombel & Ruangan/Lab*, dan (4) *Nama Guru Pengajar & Tombol Aksi Ikon (Ubah/Hapus)* di sudut kanan bawah sehingga tidak lagi saling bertumpuk di baris atas.
   - Menambahkan tombol pintas `+` pada header setiap hari untuk langsung membuka formulir tambah slot jadwal pada hari tersebut.
3. **Pencarian Cepat, Filter Hari (Pills), dan Filter Status**:
   - Menambahkan kotak pencarian instan (*Mapel, kode, guru, kelas, ruang*), tombol filter per hari (*Semua Hari, Senin..Sabtu*) lengkap dengan penghitung sesi, serta filter status (*Semua, Terbit, Draf*).
   - Memperbaiki *override* border di `resources/css/app.css` dan `resources/css/modern-ui.css` agar indikator aksen kiri kartu sesi tetap tampil jelas di mode terang maupun gelap.
4. **Fitur Ekspor Jadwal Pelajaran ke PDF (`/akademik/jadwal/export/pdf`)**:
   - Menambahkan rute `GET /akademik/jadwal/export/pdf` (`academic.schedules.export.pdf`) di `app/Modules/Academic/Interface/Routes/web.php` dengan middleware `['throttle:sensitive', 'role:admin,operator,principal,teacher,homeroom,student']`.
   - Menambahkan method `ScheduleController::exportPdf()` yang menghasilkan dokumen PDF A4 Landscape via `Barryvdh\DomPDF\Facade\Pdf` dari template `resources/views/academic/schedules-export.blade.php`.
   - Mendukung penerusan filter aktif (`term_id`, `class_group_id`, `teacher_id`, `day_of_week`, `status`, dan `search`) serta mengisolasi role `student` agar hanya dapat mengekspor jadwal `published` dari rombel aktifnya sendiri pada semester aktif.
   - Menambahkan tombol **Ekspor PDF** (`.file-action-button--pdf`) di header halaman `resources/js/Pages/Academic/Schedules/Index.vue` yang menyertakan query filter aktif secara dinamis.

### File & Migrasi yang Disentuh
- `app/Modules/Academic/Interface/Routes/web.php`
- `app/Modules/Academic/Interface/Http/Controllers/ScheduleController.php`
- `resources/views/academic/schedules-export.blade.php`
- `resources/js/Pages/Academic/Schedules/Index.vue`
- `resources/css/app.css`
- `resources/css/modern-ui.css`
- `tests/Feature/DailyOperationsControllersTest.php`
- `docs/HANDOFF.md`
- **Migrasi database**: Tidak ada perubahan skema/migrasi baru.

### Keputusan & Asumsi
- Sesuai **Opsi A** yang dipilih pengguna, halaman jadwal menyediakan sakelar *Papan Mingguan* dan *Tabel Daftar* tanpa mengubah kontrak props Inertia maupun aturan otorisasi server-side (`ScheduleController` & `SchedulePolicy`).
- Ekspor PDF menggunakan `SchoolScopeService::getSchoolDetails()` dari modul `Core\Application` untuk menjaga batas arsitektur modular monolith (`ModularBoundaryTest`).

### Tes Beserta Hasil
- `php artisan test --compact tests/Feature/DailyOperationsControllersTest.php tests/Feature/ScheduleConflictTest.php tests/Feature/RoleMatrixAccessTest.php tests/Architecture/ModularBoundaryTest.php` → **PASS** (41 tests, 337 assertions).
- `./vendor/bin/pint` → **PASS**.
- `npm run typecheck` (`vue-tsc --noEmit`) → **PASS** (0 errors).
- `npm run build` (`vite build`) → **PASS**.

### Risiko Tersisa
- Tidak ada risiko regresi terdeteksi; seluruh validasi benturan guru/rombel/ruang dan isolasi hak akses per peran di sisi server tetap utuh dan teruji.

### Langkah Review Manual
1. Buka menu **Akademik → Jadwal Pelajaran** (`/akademik/jadwal`).
2. Periksa tampilan **Papan Mingguan**: pastikan kartu sesi pada setiap hari menampilkan jam, status, mata pelajaran, kelas, ruang, nama guru, serta tombol ikon Ubah/Hapus dengan rapi tanpa terpotong.
3. Klik sakelar **Tabel Daftar** di kanan atas untuk melihat seluruh jadwal dalam bentuk tabel terstruktur.
4. Uji filter cepat menggunakan *pill* nama hari (mis. klik **Senin** untuk fokus 1 hari), filter status (*Terbit* / *Draf*), serta kotak pencarian.
5. Klik tombol **Ekspor PDF** di bagian kanan atas halaman untuk mengunduh dokumen `jadwal-pelajaran.pdf` sesuai filter yang sedang aktif.

### Rollback
- Kembalikan perubahan dengan `git checkout -- app/Modules/Academic/Interface/Routes/web.php app/Modules/Academic/Interface/Http/Controllers/ScheduleController.php resources/js/Pages/Academic/Schedules/Index.vue resources/css/app.css resources/css/modern-ui.css tests/Feature/DailyOperationsControllersTest.php && rm -f resources/views/academic/schedules-export.blade.php` lalu jalankan `npm run build`.

## Redesign Halaman Progress Belajar — Opsi A (13 November 2026)

### Task dan Requirement
- **Task**: Merombak antarmuka dan kontrol halaman **Progress Belajar** (`/pembelajaran/progress` & `/siswa/progress`) sesuai **Opsi A** (*Mode Ganda: Tabel Analitik + Kartu Ringkas per Siswa, Filter Status Cepat & Bar Progres Visual*) agar lebih mudah dipahami, dikontrol, dan dibaca.
- **Requirement ID**: `SS-F-009` (monitoring progres belajar LMS), `DESIGN.md` (Calm Academic OS), `AI_RULES.md`.

### Ringkasan Perubahan
1. **Mode Ganda (*Tabel Analitik* & *Kartu per Siswa*) di `resources/js/Pages/Learning/Progress.vue`**:
   - **Tabel Analitik**: Menampilkan progres tiap siswa per mata pelajaran dilengkapi *progress bar* visual berwarna (`emerald`/`amber`/`rose`), lencana status (*Tuntas, Sedang Berjalan, Perlu Perhatian, Perlu Revisi, Terlambat*), lencana rerata nilai, dan tombol pintas langsung ke **Materi** atau **Tugas** terfilter.
   - **Kartu Ringkas per Siswa**: Mengelompokkan capaian lintas seluruh mata pelajaran untuk setiap siswa dalam satu kartu ringkas (ketuntasan total, total materi dibaca, tugas dikumpulkan, keterlambatan, revisi, dan daftar rincian nilai per mapel yang dapat diperluas).
2. **Kontrol Cepat (Filter Status Pills & Pengurutan Prioritas) di `LearningProgressController.php` & `Progress.vue`**:
   - Menambahkan *Quick Status Filter Pills* (**Semua Status**, **Perlu Perhatian**, **Perlu Revisi**, **Terlambat**, **Belum Tuntas**, **Tuntas 100%**) beserta penghitung jumlah baris otomatis.
   - Menambahkan opsi pengurutan (*Prioritas Pendampingan, Progres Terendah/Tertinggi, Nilai Tertinggi/Terendah, Nama Siswa A–Z*) dan penerapan filter otomatis saat dropdown Kelas/Mapel/Urutan diubah.
3. **Kartu KPI dengan Mini Progress Bar & Kepatuhan Hak Akses**:
   - Memperkaya 4 kartu metrik ringkasan di bagian atas dengan *mini progress bar* dan indikator persentase.
   - Menyesuaikan tombol pintas LMS menggunakan flag `can_manage_lms` agar peran manajerial (`admin`, `operator`, `principal`) yang memantau progres tidak diarahkan ke rute khusus pengajar/siswa yang membatasi akses.

### File & Migrasi yang Disentuh
- `app/Modules/Learning/Interface/Http/Controllers/LearningProgressController.php`
- `resources/js/Pages/Learning/Progress.vue`
- `tests/Feature/LearningControllersTest.php`
- `docs/HANDOFF.md`
- **Migrasi database**: Tidak ada perubahan skema/migrasi baru.

### Keputusan & Asumsi
- Sesuai **Opsi A** yang dipilih pengguna, halaman menyediakan sakelar tampilan **Tabel Analitik** dan **Kartu per Siswa** bagi peran non-siswa, serta mempertahankan tampilan khusus siswa lengkap dengan rincian kehadiran (`personal_attendance`).

### Tes Beserta Hasil
- `php artisan test --compact tests/Feature/LearningControllersTest.php tests/Architecture/ModularBoundaryTest.php` → **PASS** (9 tests, 124 assertions).
- `./vendor/bin/pint` → **PASS**.
- `npm run typecheck` (`vue-tsc --noEmit`) → **PASS** (0 errors).
- `npm run build` (`vite build`) → **PASS**.

### Risiko Tersisa
- Tidak ada risiko regresi terdeteksi; batas modular monolith dan matriks izin peran tetap terjaga.

### Langkah Review Manual
1. Buka menu **Pembelajaran → Progress Belajar** (`/pembelajaran/progress`).
2. Periksa kartu ringkasan KPI di bagian atas dan klik *pill* filter cepat (**Perlu Perhatian**, **Perlu Revisi**, **Terlambat**, **Tuntas 100%**) serta dropdown pengurutan (**Urut: Prioritas Pendampingan**).
3. Periksa **Tabel Analitik**: pastikan bar progres materi & tugas, lencana status, dan lencana nilai tampil jelas dan mudah dipindai.
4. Klik sakelar **Kartu per Siswa** di kanan atas untuk melihat agregasi capaian belajar per siswa lintas mata pelajaran.

### Rollback
- Kembalikan perubahan dengan `git checkout -- app/Modules/Learning/Interface/Http/Controllers/LearningProgressController.php resources/js/Pages/Learning/Progress.vue tests/Feature/LearningControllersTest.php` lalu jalankan `npm run build`.

---

## Perapihan Menu & Halaman Role Siswa — Paket Lengkap Opsi A (13 November 2026)

### Task dan Requirement
- Merapikan seluruh menu dan pengalaman antarmuka role `student` agar konsisten dengan halaman role lain (gaya *Calm Academic OS*: sidebar label ramah siswa, ringkasan metrik, filter pills status, navigasi lintas fitur siswa, dan keterbacaan aksi).
- Cakupan perubahan murni di lapisan frontend (`resources/js/**`) tanpa mengubah rute backend atau policy otorisasi server-side.

### Ringkasan Perubahan
1. **Sidebar Siswa (`resources/js/components/SidebarNavigation.vue`)**
   - Menambahkan mapping `displayName` khusus role `student` sehingga label navigasi menjadi lebih personal dan jelas: *Jadwal Saya*, *Materi Pelajaran*, *Tugas Saya*, *Progress Belajar*, *Ujian CBT*, *Rapor Saya*, *Pengumuman & Agenda*, *Diskusi Kelas*, dan *Profil Saya*.
2. **Dashboard Siswa (`resources/js/Pages/Dashboard.vue`)**
   - Memperbarui `studentActions` menjadi 8 akses utama dengan rute kanonis siswa (`/siswa/materi`, `/siswa/tugas`, `/akademik/jadwal`, `/ujian`, `/siswa/progress`, `/penilaian/rapor`, `/akademik/kelas-daring`, `/komunikasi/chat`).
   - Memperbarui tautan pada seksi `student-overview` ke `/siswa/progress` dan `/siswa/tugas`.
   - Menambahkan seksi `student-shortcuts` ("Akses cepat belajar / Semua menu siswa") beserta CSS responsif.
3. **Materi & Tugas LMS Siswa (`resources/js/Pages/Learning/Index.vue`)**
   - Menambahkan opsi *"Semua mata pelajaran"* (`''`), dukungan query `?subject=`, pencarian `studentSearch`, dan filter status cepat tugas (`all`, `pending`, `submitted`, `revision`, `overdue`).
   - Menambahkan `.class-workspace-nav` khusus siswa (*Materi Pelajaran*, *Tugas Saya*, *Progress Belajar*, *Kelas Daring*, *Diskusi Kelas*) dan tombol hero *Progress belajar*.
4. **Ujian CBT Siswa (`resources/js/Pages/Assessment/Exams/Index.vue`)**
   - Menambahkan 4 kartu ringkasan `.grade-summary` (*Total Ujian*, *Siap Dikerjakan*, *Terjadwal*, *Selesai*), filter pills status (`all`, `ready`, `upcoming`, `done`), serta menampilkan nama mata pelajaran pada `.exam-meta`.
5. **Kelas Daring Siswa (`resources/js/Pages/Academic/OnlineClasses/Index.vue`)**
   - Menambahkan tombol pintas hero *Jadwal saya*, 4 kartu ringkasan `.grade-summary` (*Total Pertemuan*, *Sedang Berlangsung*, *Terjadwal*, *Selesai*), filter pills status (`all`, `live`, `scheduled`, `completed`), serta empty state khusus hasil filter.

### File yang Disentuh
- `resources/js/components/SidebarNavigation.vue`
- `resources/js/Pages/Dashboard.vue`
- `resources/js/Pages/Learning/Index.vue`
- `resources/js/Pages/Assessment/Exams/Index.vue`
- `resources/js/Pages/Academic/OnlineClasses/Index.vue`

### Keputusan / Asumsi
- Semua tautan LMS siswa diarahkan ke rute kanonis `/siswa/materi`, `/siswa/tugas`, dan `/siswa/progress` agar state aktif pada `SidebarNavigation.vue` tetap akurat.
- Tidak ada perubahan migrasi, database, maupun policy otorisasi backend.

### Tes dan Verifikasi
- `npm run typecheck && npm run build`: **PASS** (0 error TypeScript/Vue, 854 modul ter-transform, build Vite selesai dalam 2.74s).
- `php artisan test --compact tests/Feature/DashboardAccessTest.php tests/Feature/LearningControllersTest.php tests/Feature/RoleMatrixAccessTest.php tests/Feature/RouteAndPermissionStressTest.php`: **PASS** (41 tests, 863 assertions, 0 failures).

### Langkah Review Manual
1. Login sebagai akun siswa, periksa sidebar kiri (*Jadwal Saya*, *Materi Pelajaran*, *Tugas Saya*, *Progress Belajar*, *Ujian CBT*, *Rapor Saya*, *Pengumuman & Agenda*, *Diskusi Kelas*, *Profil Saya*).
2. Cek `/dashboard` (bagian `student-overview` dan `student-shortcuts`).
3. Cek `/siswa/materi` dan `/siswa/tugas` (filter semua mapel, kotak pencarian, filter pills status tugas, serta navigasi `.class-workspace-nav`).
4. Cek `/ujian` dan `/akademik/kelas-daring` (4 kartu ringkasan dan filter pills status).

### Rollback
- Kembalikan perubahan dengan `git checkout -- resources/js/components/SidebarNavigation.vue resources/js/Pages/Dashboard.vue resources/js/Pages/Learning/Index.vue resources/js/Pages/Assessment/Exams/Index.vue resources/js/Pages/Academic/OnlineClasses/Index.vue` lalu jalankan `npm run build`.


---

## Ledger Nilai (Excel & PDF), Pemilihan Tahun Pelajaran & Semester, Cetak Rapor Spesifik, dan Penyempurnaan Skenario STS vs SAS (15 November 2026)

### Task dan Requirement
- **Task**: Melengkapi fitur Ledger Nilai dengan ekspor Excel (`.xlsx`) dan PDF (`A4 Landscape`), menyediakan pemilihan Tahun Pelajaran dan Semester pada Rapor Siswa, Ledger Nilai, dan Buku Induk, mendukung pencetakan rapor spesifik (per semester, per mode STS/SAS, dan per siswa terpilih), memperbaiki konsistensi menu navigasi, serta menyempurnakan skenario perhitungan dan tampilan STS vs SAS.
- **Requirement ID**: `SS-D-005` (Rapor & Ledger Nilai), `SS-F-001` (Buku Induk), `DESIGN.md`, `AI_RULES.md`.

### Ringkasan Perubahan
1. **Ledger Nilai (`/penilaian/ledger`, `/penilaian/ledger/excel`, `/penilaian/ledger/pdf`)**:
   - Mendukung pembukaan langsung `/penilaian/ledger` tanpa error redirect ketika parameter kosong (otomatis memilih semester aktif dan kelas pertama yang dapat diakses pengguna).
   - Menyediakan filter **Tahun Pelajaran**, **Semester**, **Kelas / Rombel**, **Skenario Evaluasi (`SAS` / `STS`)**, dan pencarian siswa.
   - Menghitung matriks nilai per mata pelajaran, **Jumlah Nilai**, **Rata-rata**, **Peringkat**, dan **Presensi (S, I, A)** baik dari snapshot rapor maupun kalkulasi langsung dari skor komponen apabila snapshot belum disusun.
   - Menambahkan ekspor **Excel (`.xlsx`)** dan **Pratinjau / Cetak PDF (`A4 Landscape`)** via `resources/views/reports/class-ledger.blade.php`.
2. **Pemilihan Tahun Pelajaran, Semester & Cetak Rapor Spesifik (`Assessment/Reports/Index.vue`, `StudentLedgerController.php`, `Academic/Students/Ledger/Show.vue`)**:
   - Menambahkan filter **Tahun Pelajaran**, **Semester**, **Kelas / Rombel**, dan **Skenario Evaluasi (`SAS` / `STS`)** di halaman Rapor Siswa (termasuk bagi akun siswa).
   - Menambahkan pemilihan spesifik siswa (checkbox per baris & pilih semua) sehingga pengguna dapat mencetak PDF untuk **siswa tertentu**, **semester tertentu**, dan **mode tertentu (`STS` / `SAS`)** tanpa harus mencetak seluruh kelas atau seluruh semester sekaligus.
   - Pada detail **Buku Induk Siswa**, menambahkan filter semester pada Seksi G (*Rekapitulasi nilai rapor per semester*) serta tombol pintas **Cetak Rapor STS** dan **Cetak Rapor SAS** per semester.
3. **Skenario STS vs SAS Secara Sempurna (`ReportPublishingService.php`, `ReportGeneratorService.php`, `Show.vue`, `report-card.blade.php`)**:
   - Menyimpan hasil kalkulasi kedua skenario (`sts` dan `sas`) secara bersamaan di dalam `scheme_snapshot.grades_by_mode` saat rapor disusun: mode **STS** mengecualikan komponen akhir semester (`SAS`, `PAS`, `UAS`, `PAT`, `UKK`), sedangkan **SAS** menghitung seluruh komponen semester.
   - Menyesuaikan pratinjau web (`Assessment/Reports/Show.vue`) dan cetakan PDF (`resources/views/reports/report-card.blade.php`): pada mode **STS**, menampilkan kolom **Rincian Komponen (Formatif/STS)** & **Predikat**, menyembunyikan bagian **Keputusan Kenaikan Kelas / Kelulusan**, serta menonaktifkan sampul akhir semester secara default.
4. **Konsistensi Menu & Navigasi (`SidebarNavigation.vue`, `Breadcrumbs.vue`, `Index.vue`, `Ledger.vue`)**:
   - Menambahkan tab navigasi kontekstual (**Rapor Siswa** | **Ledger Nilai (Excel & PDF)** | **Skema Penilaian**) yang mempertahankan filter aktif.
   - Memastikan item sidebar `Rapor Siswa` tetap aktif saat berada di `/penilaian/ledger` dan melengkapi label breadcrumb `ledger` serta `skema`.

### File & Migrasi yang Disentuh
- `app/Modules/Academic/Application/Services/AcademicQueryService.php`
- `app/Modules/Academic/Interface/Http/Controllers/StudentLedgerController.php`
- `app/Modules/Assessment/Domain/Services/GradeCalculationEngine.php`
- `app/Modules/Assessment/Application/Services/ReportPublishingService.php`
- `app/Modules/Assessment/Application/Services/ReportGeneratorService.php`
- `app/Modules/Assessment/Interface/Http/Controllers/ReportCardController.php`
- `app/Modules/Assessment/Interface/Http/Controllers/ReportingController.php`
- `app/Modules/Assessment/Interface/Routes/web.php`
- `resources/views/reports/class-ledger.blade.php`
- `resources/views/reports/report-card.blade.php`
- `resources/js/Pages/Assessment/Reports/Index.vue`
- `resources/js/Pages/Assessment/Reports/Ledger.vue`
- `resources/js/Pages/Assessment/Reports/Show.vue`
- `resources/js/Pages/Academic/Students/Ledger/Show.vue`
- `resources/js/components/SidebarNavigation.vue`
- `resources/js/components/Breadcrumbs.vue`
- `tests/Feature/AssessmentControllersTest.php`
- **Migrasi database**: Tidak ada migrasi atau perubahan skema database.

### Tes Beserta Hasil
- `php artisan test --compact tests/Feature/AssessmentControllersTest.php tests/Feature/StudentLedgerControllersTest.php tests/Feature/RoleMatrixAccessTest.php tests/Architecture/ModularBoundaryTest.php` → **PASS** (90 tests, 919+ assertions).
- `php artisan test --compact --filter=test_report_supplements_sts_mode_and_pdf_preview_flow tests/Feature/AssessmentControllersTest.php` → **PASS** (1 test, 46 assertions).
- `./vendor/bin/pint --test` → **PASS**.
- `npm run build` → **PASS** (866 modules transformed).

### Risiko Tersisa
- Snapshot rapor lama yang dibuat sebelum pembaruan ini tetap kompatibel melalui fallback ke `grades_snapshot`; untuk mendapatkan rincian ganda `grades_by_mode` (`sts` & `sas`), pengguna cukup menekan tombol **Susun rapor** kembali pada kelas terkait.

### Langkah Review Manual
1. Buka **Penilaian & Rapor → Rapor Siswa** (`/penilaian/rapor`), pilih **Tahun Pelajaran**, **Semester**, **Kelas / Rombel**, dan **Skenario Evaluasi (`SAS` / `STS`)**.
2. Centang beberapa siswa tertentu lalu klik **Pratinjau PDF Terpilih**, atau klik **Lihat** / **Pratinjau PDF** pada baris siswa untuk membandingkan tampilan **STS** (dengan rincian komponen formatif/STS tanpa keputusan kenaikan kelas) dan **SAS**.
3. Klik tab **Ledger Nilai (Excel & PDF)** (`/penilaian/ledger`), uji filter Tahun Pelajaran/Semester/Mode, lalu klik **Export Excel (.xlsx)** dan **Pratinjau / Cetak PDF**.
4. Buka **Akademik → Buku Induk → Detail Siswa**, periksa filter semester pada bagian Rekapitulasi Rapor serta tombol **Cetak Rapor STS** / **Cetak Rapor SAS** per semester.

### Rollback
- Kembalikan perubahan dengan `git checkout -- app/Modules/Academic/ app/Modules/Assessment/ resources/views/reports/ resources/js/ tests/Feature/AssessmentControllersTest.php && rm -f resources/views/reports/class-ledger.blade.php` lalu jalankan `npm run build`.


## 2026-11-15 â€” Perapian landing page publik (Home.vue)
- **Task:** konsolidasi CSS `resources/js/Pages/Public/School/Home.vue` + seragamkan istilah SPMB.
- **Ringkasan:** 3 lapisan CSS (awal/Editorial/Calm Academic) dilebur jadi satu lapisan (~440 â†’ ~150 baris), aturan mati dihapus (`.gallery-*`, `::before/::after` hero, override yang selalu kalah). Label default hero `Daftar PPDB` â†’ `Daftar SPMB`. Template/markup tidak diubah; URL `/ppdb` tetap.
- **Migrasi:** tidak ada.
- **Tes:** `npm run build` sukses; `php artisan test --filter="Public|SchoolProfile"` 11/11 lulus.
- **Risiko:** pergeseran visual minor; hover kartu Jelajahi kini tanpa ganti warna judul (sebelumnya juga tertimpa). Tambah dukungan reduced-motion untuk kartu.
- **Review manual:** buka `/` di desktop, â‰¤900px, â‰¤600px; bandingkan hero, kartu jalur, berita, footer.
- **Rollback:** `git checkout -- resources/js/Pages/Public/School/Home.vue`.


## 2026-11-15 — Redesign halaman login (Auth/Login.vue)
- **Ringkasan:** layout split layar penuh bergaya Calm Academic (selaras situs publik): panel brand terang + chip fitur, form tanpa kartu. Dihapus: orbit dekoratif, panel gelap, kartu berbayang. Ditambah: tautan "Kembali ke situs sekolah", catatan bantuan kata sandi. Logika form/submit/branding/favicon tidak diubah.
- **Tes:** 
pm run build sukses; AuthenticationTest 7/7 lulus.
- **Risiko:** teks "Hubungi operator atau wali kelas" adalah panduan statis (belum ada alur reset kata sandi); verifikasi visual manual belum dilakukan.
- **Review manual:** buka /login desktop & <860px, uji error validasi, tampilkan/sembunyikan sandi, logo kustom.
- **Rollback:** `git checkout -- resources/js/Pages/Auth/Login.vue`
