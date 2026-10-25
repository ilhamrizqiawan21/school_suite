# Task Adaptasi Fitur `lms_sekolah`

Dokumen ini adalah checklist pemantauan adaptasi fitur dari repo referensi `lms_sekolah` ke `school_suite`.

Tanggal pemeriksaan terakhir: **25 Oktober 2026**.

## Aturan adaptasi

- `lms_sekolah` hanya menjadi referensi fitur, alur bisnis, validasi, dan endpoint.
- Arsitektur, model data, otorisasi, serta penamaan akhir mengikuti modular monolith `school_suite`.
- UI, layout, navigasi, komponen, dan gaya visual mengikuti [`docs/mockup`](mockup/README.md).
- Status **selesai** hanya diberikan jika backend, endpoint, otorisasi, migrasi, dan UI `school_suite` sudah tersedia.
- Checklist ini membantu pemantauan, tetapi bukti akhir tetap berasal dari kode, route aktif, migrasi, dan hasil verifikasi.

## Legenda

| Simbol | Status | Arti |
|---|---|---|
| ✅ | Selesai | Sudah diadaptasi dan tersedia pada `school_suite`. |
| 🟡 | Sebagian | Fondasi sudah ada, tetapi masih ada perilaku referensi yang belum diadaptasi. |
| ⬜ | Belum | Belum diimplementasikan pada `school_suite`. |
| ⛔ | Tidak diambil | Sengaja tidak diadaptasi atau berada di luar scope produk. |

## Ringkasan per area

| Area | Status | Catatan |
|---|---|---|
| Akses dan tata kelola pengguna | ✅ | Role, permission, audit, isolasi sekolah, blokir IP, log operasional tersanitasi, dan wajib ganti password tersedia. |
| Master akademik | ✅ | Siswa, guru, kelas, enrollment historis, periode/mapel, jadwal, presensi luring/daring, jurnal, dan siklus siswa tersedia. |
| Pembelajaran | ✅ | Materi, tracking bacaan, progress siswa, tugas, submission teks/multi-file, revisi, nilai, dan feedback tersedia. |
| Evaluasi dan rapor | ✅ | Nilai, remedial, sikap spiritual/sosial, kehadiran, dashboard progress, snapshot rapor, dan ledger tersedia. |
| Komunikasi | ✅ | Pengumuman, notifikasi, kalender, chat kelas-mapel, moderasi, dan persiapan WhatsApp tersedia. |
| CBT/ujian | ✅ | Bank soal, builder, attempt, autosave, auto-grading, koreksi esai, anti-cheat, sinkronisasi nilai, dan export tersedia. |
| Branding sekolah dan profil akun | ✅ | Identitas/legal, logo, favicon, palet tema berbasis token mockup, profil/avatar, dan ganti password mandiri tersedia. |
| Pelaporan dan export | ✅ | Pusat laporan pimpinan, PDF rapor, XLSX nilai, serta CSV presensi, tugas, sikap, dan metrik operasional guru tersedia. |

## A. Akses dan keamanan

| ID | Status | Fitur referensi | Implementasi/bukti `school_suite` | Kriteria penerimaan yang masih harus diperiksa |
|---|---|---|---|---|
| LMS-A01 | ✅ | Login dan logout | `Core/AuthController`, route `/login`, `/logout` | Akun aktif dapat login; akun nonaktif ditolak. |
| LMS-A02 | ✅ | Role-based access | `RoleEnum`, permission, policy per modul | Aksi material ditolak server-side saat role tidak berhak. |
| LMS-A03 | ✅ | Manajemen pengguna | `/pengaturan/pengguna` | Admin/operator dapat membuat dan mengubah akun sesuai policy. |
| LMS-A04 | ✅ | Audit aktivitas material | `AuditLogger`, `audit_events` | Mutasi penting menyimpan actor, target, waktu, dan nilai aman. |
| LMS-A05 | ✅ | Rate limiting dan security headers | Limiter `sensitive`/`uploads`, middleware CSP, COOP, CORP, HSTS, frame, MIME, referrer, dan permissions policy | Endpoint upload dan mutasi sensitif menggunakan limiter bernama; header diterapkan oleh middleware web. |
| LMS-A06 | ✅ | Daftar IP terblokir | `/pengaturan/keamanan`, `security_blocked_ips`, middleware `CheckBlockedIp` | Admin dapat blokir/buka blokir IP dengan audit trail dan masa berlaku; IP aktif sendiri tidak dapat diblokir. |
| LMS-A07 | ✅ | Log login dan error operasional | `/pengaturan/keamanan`, `login_attempt_logs`, `system_error_events` | Identitas login disimpan sebagai hash; error tidak menyimpan pesan/stack dan terisolasi per sekolah. |
| LMS-A08 | ✅ | Wajib ganti password default | `/ganti-password`, middleware `RequirePasswordChange` | Akun baru/reset admin ditandai wajib ganti password dan tidak dapat membuka modul lain sebelum menggantinya. |

## B. Master akademik dan operasi sekolah

| ID | Status | Fitur referensi | Implementasi/bukti `school_suite` | Sisa pekerjaan/kriteria penerimaan |
|---|---|---|---|---|
| LMS-B01 | ✅ | Tahun ajaran dan semester aktif | `/akademik/pengaturan`; tambah/ubah tahun, tambah semester, aktivasi atomik | Hanya satu tahun dan semester aktif per sekolah; rentang tanggal tervalidasi. |
| LMS-B02 | ✅ | Mata pelajaran | CRUD di `/akademik/pengaturan` | Kode unik per sekolah, urutan/status aktif tersedia, penghapusan ditolak saat masih digunakan. |
| LMS-B03 | ✅ | Data guru | `/akademik/guru`, import CSV, policy | Pencarian, tambah, ubah, import, dan isolasi sekolah tersedia. |
| LMS-B04 | ✅ | Data siswa | `/akademik/siswa`, import CSV, enrollment historis | Histori kelas antar-semester tidak tertimpa. |
| LMS-B05 | ✅ | Kelas/rombel dan wali kelas | `/akademik/kelas`, assign wali kelas | Keunikan wali kelas dan kapasitas rombel tervalidasi. |
| LMS-B06 | ✅ | Guru-mapel/kelas-mapel | `teaching_assignments` | Penugasan terikat term, kelas, mapel, dan guru. |
| LMS-B07 | ✅ | Jadwal pelajaran | `/akademik/jadwal` | Benturan guru/kelas/ruang ditolak sebelum publikasi. |
| LMS-B08 | ✅ | Presensi tatap muka | `/akademik/presensi` | Pencatatan dan koreksi menyimpan audit. |
| LMS-B09 | ✅ | Jurnal mengajar | `/akademik/jurnal` | Guru hanya mengisi jurnal untuk penugasannya. |
| LMS-B10 | ✅ | Kelas daring dan presensi daring | `/akademik/kelas-daring`; jadwal, tautan HTTPS, status, dan presensi per enrollment | Guru dibatasi pada penugasan mengajarnya; admin/operator/principal dapat mengelola lingkup sekolah. |
| LMS-B11 | ✅ | Siklus kelulusan/tinggal kelas | `/akademik/siklus-siswa`; ledger `student_lifecycle_events` | Naik/tinggal kelas membuat enrollment tujuan; lulus/keluar menutup enrollment aktif; seluruh keputusan diaudit. |

## C. Pembelajaran

| ID | Status | Fitur referensi | Endpoint `school_suite` | Bukti utama |
|---|---|---|---|---|
| LMS-C01 | ✅ | Daftar ruang pembelajaran | `GET /pembelajaran` | `LearningController@index`, `Pages/Learning/Index.vue` |
| LMS-C02 | ✅ | Terbitkan materi | `POST /pembelajaran/materi` | Materi terikat penugasan mengajar dan sekolah. |
| LMS-C03 | ✅ | Unduh materi privat | `GET /pembelajaran/materi/{material}/unduh` | Policy membatasi guru/siswa kelas terkait. |
| LMS-C04 | ✅ | Hapus materi | `DELETE /pembelajaran/materi/{material}` | File privat ikut dibersihkan. |
| LMS-C05 | ✅ | Buat tugas dan tenggat | `POST /pembelajaran/tugas` | Mendukung nilai maksimum dan kebijakan terlambat. |
| LMS-C06 | ✅ | Hapus tugas | `DELETE /pembelajaran/tugas/{assignment}` | Submission dan berkas turunannya ikut dibersihkan. |
| LMS-C07 | ✅ | Submission teks/multi-file | `POST /pembelajaran/tugas/{assignment}/kumpulkan` | Maksimal lima file, validasi jenis/ukuran, satu submission per enrollment. |
| LMS-C08 | ✅ | Unduh berkas submission | `GET /pembelajaran/berkas/{file}/unduh` | Hanya pemilik submission atau pengajar berhak. |
| LMS-C09 | ✅ | Penilaian dan feedback tugas | `POST /pembelajaran/pengumpulan/{submission}/nilai` | Nilai dibatasi nilai maksimum tugas. |
| LMS-C10 | ✅ | Catatan perbaikan/remedial tugas | `review_status`, `revision_limit`, `revision_count`, dan `assignment_submission_revisions` | Guru dapat meminta perbaikan; siswa hanya mengirim ulang saat diminta dan belum melewati batas revisi. |
| LMS-C11 | ✅ | Progress belajar siswa | `/pembelajaran/progress`, `learning_material_reads` | Ringkasan materi dibaca, tugas selesai/terlambat, revisi, dan rerata capaian nilai per siswa/mapel tersedia sesuai lingkup role. |

## D. Evaluasi, sikap, dan rapor

| ID | Status | Fitur referensi | Implementasi/bukti `school_suite` | Sisa pekerjaan |
|---|---|---|---|---|
| LMS-D01 | ✅ | Input nilai batch | `/penilaian/skor` | Nilai mentah, remedial, nilai akhir, dan catatan. |
| LMS-D02 | ✅ | Skema penilaian berversi | `/penilaian/skema` | Komponen, bobot, KKTP, pembulatan. |
| LMS-D03 | ✅ | Rapor dan snapshot | `/penilaian/rapor` | Generate batch, verifikasi, publikasi, snapshot immutable. |
| LMS-D04 | ✅ | Ledger nilai | `/penilaian/ledger` | Rekap nilai per kelas tersedia. |
| LMS-D05 | ✅ | Sikap spiritual | `/penilaian/sikap`, `student_attitudes` | Input batch per siswa, predikat/deskripsi, audit, dan snapshot rapor tersedia. |
| LMS-D06 | ✅ | Sikap sosial | `/penilaian/sikap`, `student_attitudes` | Input batch per siswa, predikat/deskripsi, audit, dan snapshot rapor tersedia. |
| LMS-D07 | ✅ | Rekap absensi | `/akademik/presensi/rekap` dan `/akademik/presensi/rekap/export` | Filter rombel/rentang tanggal, agregat status, persentase hadir, dan export CSV kompatibel spreadsheet. |
| LMS-D08 | ✅ | Dashboard progress siswa | `/pembelajaran/progress` | Siswa hanya melihat materi, tugas, nilai, revisi, keterlambatan, dan rekap kehadirannya sendiri. |

## E. Komunikasi

| ID | Status | Fitur referensi | Endpoint `school_suite` | Bukti utama |
|---|---|---|---|---|
| LMS-E01 | ✅ | Pengumuman terarah | `POST /komunikasi/pengumuman` | Target seluruh sekolah, role, atau rombel. |
| LMS-E02 | ✅ | Daftar pengumuman sesuai penerima | `GET /komunikasi` | Query dibatasi sekolah, role, kelas, dan masa berlaku. |
| LMS-E03 | ✅ | Lampiran privat pengumuman | `GET /komunikasi/pengumuman/{announcement}/lampiran` | File tidak dipublikasikan langsung. |
| LMS-E04 | ✅ | Notifikasi otomatis | Dibuat saat pengumuman terbit | `CommunicationService` membuat penerima dalam transaksi yang sama. |
| LMS-E05 | ✅ | Tandai notifikasi dibaca | `POST /komunikasi/notifikasi/{notification}/baca` | Hanya pemilik notifikasi. |
| LMS-E06 | ✅ | Tandai semua dibaca | `POST /komunikasi/notifikasi/baca-semua` | Hanya notifikasi pengguna aktif. |
| LMS-E07 | ✅ | Kalender akademik | `POST/DELETE /komunikasi/kalender` | Agenda dapat ditargetkan berdasarkan role/rombel. |
| LMS-E08 | ✅ | Chat kelas-mapel | `/komunikasi/chat` | Ruang terikat teaching assignment, peserta diisolasi berdasarkan role/enrollment, dan pesan dapat dimoderasi dengan audit. |
| LMS-E09 | ✅ | Persiapan pesan WhatsApp | `/komunikasi/whatsapp` | Normalisasi nomor Indonesia, template, preview, tautan pengiriman manual, serta status/log pengiriman tersedia. |

## F. CBT dan ujian

| ID | Status | Task adaptasi | Kriteria penerimaan |
|---|---|---|---|
| LMS-F01 | ✅ | Bank soal pilihan ganda/esai | `/ujian/soal`; kepemilikan sekolah/guru, opsi, kunci, poin, dan jenis soal tervalidasi. |
| LMS-F02 | ✅ | Builder ujian | `POST /ujian`; kelas-mapel, komponen nilai, soal, jadwal, durasi, acak soal/opsi, dan batas pelanggaran tersedia. |
| LMS-F03 | ✅ | Attempt siswa | `/ujian/attempt/{attempt}`; satu attempt per enrollment, autosave, heartbeat, dan deadline server-side. |
| LMS-F04 | ✅ | Auto-grading | Pilihan ganda dinilai otomatis; esai berstatus `pending_review` hingga dikoreksi guru. |
| LMS-F05 | ✅ | Hasil dan sinkronisasi nilai | `/ujian/{exam}/hasil`; publikasi eksplisit dan sinkronisasi ke komponen penilaian. |
| LMS-F06 | ✅ | Anti-cheat policy | `exam_events`; pindah tab, heartbeat, jumlah pelanggaran, dan submit otomatis pada batas maksimum. |
| LMS-F07 | ✅ | Export hasil ujian | `/ujian/{exam}/hasil/export`; CSV dibatasi sekolah dan penugasan pengajar. |

## G. Pelaporan dan export

| ID | Status | Task adaptasi | Kriteria penerimaan |
|---|---|---|---|
| LMS-G01 | ✅ | Export PDF rapor | `/penilaian/rapor/{report}/pdf`; PDF A4 bersumber dari snapshot dan memuat identitas, periode, status, serta checksum SHA-256. |
| LMS-G02 | ✅ | Export Excel nilai | `/laporan/nilai`; XLSX per rombel pada semester aktif, NIS dipertahankan sebagai teks dan nilai ditulis sebagai angka. |
| LMS-G03 | ✅ | Export presensi | `/laporan/presensi`; CSV per siswa berisi jumlah hadir, sakit, izin, dan alpa. |
| LMS-G04 | ✅ | Export tugas | `/laporan/tugas`; CSV memuat mapel, tenggat, waktu pengumpulan, status, ketepatan, nilai, dan feedback. |
| LMS-G05 | ✅ | Export sikap | `/laporan/sikap`; CSV memuat predikat/deskripsi spiritual dan sosial per siswa. |
| LMS-G06 | ✅ | Laporan kepala sekolah | `/laporan`; ringkasan siswa, kehadiran, nilai, penyelesaian tugas, wali kelas, dan tren bulanan. |
| LMS-G07 | ✅ | Performa guru | Metrik operasional penugasan, sesi, jurnal, materi, tugas, dan ujian; ditampilkan terpisah tanpa skor komposit/ranking. |

## H. Branding dan profil akun

| ID | Status | Task adaptasi | Kriteria penerimaan |
|---|---|---|---|
| LMS-H01 | ✅ | Identitas sekolah | `/pengaturan/sekolah` memuat identitas dasar, badan hukum, status, akreditasi, kepala sekolah, website, visi, dan misi | Validasi server-side dan audit perubahan profil tersedia. |
| LMS-H02 | ✅ | Logo dan favicon | `/pengaturan/sekolah`; file image tervalidasi dan disimpan privat, memiliki fallback, dipakai pada login/sidebar/rapor dan favicon browser. |
| LMS-H03 | ✅ | Warna tema terkonfigurasi | Lima palet aman mengubah token `--primary` tanpa mengganti komponen atau layout mockup. |
| LMS-H04 | ✅ | Profil pengguna | `/profil`; pengguna mengubah nama, email, kontak, dan avatar miliknya sendiri melalui policy, validasi, dan audit. |
| LMS-H05 | ✅ | Ganti password mandiri | `/ganti-password`; password lama diverifikasi, kebijakan password kuat diterapkan, sesi database lain dihapus, dan audit tidak menyimpan password. |

## I. Quality gate setiap fitur

Checklist berikut wajib diterapkan untuk setiap task yang berpindah ke **selesai**:

- [x] Endpoint dilindungi autentikasi dan policy/gate server-side — route Core terautentikasi; akses sekolah/profil/media dibatasi policy dan school ownership.
- [x] Semua query dibatasi `school_id`, assignment, enrollment, atau pemilik data yang relevan — diverifikasi pada controller Core dan matriks akses.
- [x] Migrasi memiliki `down()` dan tidak mengubah schema secara manual — seluruh migrasi module memiliki pasangan `down()`, termasuk branding/profile media.
- [x] Upload memakai penyimpanan privat, whitelist tipe file, dan batas ukuran — logo/avatar/favicon memakai disk `local` dan validasi image/size/dimensi.
- [x] Validasi bisnis tidak hanya dilakukan di frontend — validasi Laravel, policy, dan invalidasi sesi berjalan di server.
- [x] UI mengikuti mockup `school_suite`, bukan komponen/layout `lms_sekolah` — smoke check browser pada profil dan branding selesai.
- [x] `php artisan route:list` tidak memiliki route konflik — 135 route, tidak ada nama route duplikat.
- [x] PHP lint, `npm run typecheck`, dan `npm run build` berhasil.
- [x] Alur role Admin/Operator/Kepsek/Guru/Wali Kelas/Siswa diperiksa sesuai fitur — `RoleMatrixAccessTest` lulus; fitur Core tambahan diuji dengan admin/self-owner.
- [x] Status dan bukti pada dokumen ini diperbarui setelah implementasi.

## Review pemilik produk

Gunakan bagian ini untuk mencatat hasil pengecekan Anda.

| Tanggal | ID task | Hasil (`sesuai`/`revisi`) | Catatan |
|---|---|---|---|
| 2026-10-25 | Quality gate / H01-H05 | sesuai | Seluruh gate lulus: frontend, migrasi, route, upload, auth/policy, role matrix, formatter, boundary arsitektur, dan 143/143 test. Referensi lintas modul Learning/Administration sudah dipindahkan dari import Infrastructure langsung ke class-string/FQCN terkontrol. |

## Urutan adaptasi berikutnya

Urutan ini berbasis dependensi dan dapat diubah oleh pemilik produk:

1. `LMS-C10` dan `LMS-C11`: revisi tugas dan progress siswa.
2. `LMS-E08`: chat kelas-mapel.
3. `LMS-D05`–`LMS-D08`: sikap dan dashboard progress/evaluasi.
4. `LMS-F01`–`LMS-F07`: CBT dan ujian.
5. `LMS-G01`–`LMS-G07`: export dan laporan manajerial.
6. `LMS-H01`–`LMS-H05`: branding lanjutan dan profil akun.
7. `LMS-A05`–`LMS-A08`: hardening keamanan operasional.

