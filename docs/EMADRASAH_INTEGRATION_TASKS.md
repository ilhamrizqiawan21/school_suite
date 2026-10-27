# Rencana Task Integrasi eMadrasah ke School Suite

Terakhir ditinjau: 27 Oktober 2026  
Sumber eMadrasah: `ilhamrizqiawan21/emadrasah-laravel`, commit `885a8c6`.

## Tujuan

Menyatukan alur Tata Usaha dan administrasi eMadrasah ke School Suite agar staf memakai satu aplikasi, satu identitas pengguna, dan data akademik School Suite. Cakupan awal mencakup persuratan, arsip, tugas staf, sarana-prasarana, serta impor/ekspor data EMIS dengan pemetaan format yang dapat disesuaikan. Keuangan ditunda dan tidak termasuk rilis awal.

Asumsi kerja: fitur eMadrasah akan diport ke modular monolith School Suite. School Suite tetap menjadi sumber kebenaran akun, sekolah, siswa, guru, kelas, dan periode akademik. Kedua aplikasi tidak berbagi database. Keputusan ini perlu dikukuhkan dalam ADR sebelum migrasi skema dimulai.

## Kondisi awal yang sudah dipetakan

- eMadrasah memakai Laravel 13, Blade, Bootstrap 5, dan satu instalasi database untuk satu madrasah.
- Role eMadrasah berupa string `admin`, `operator`, `guru`, `wali_murid`, dan `siswa`; School Suite memakai tabel role dan permission.
- Skema eMadrasah belum memakai `school_id`. School Suite menyimpan cakupan sekolah pada data dan otorisasi.
- Kedua proyek memiliki entitas siswa, guru, kelas, nilai, dan tahun ajaran yang tidak dapat disalin langsung tanpa pemetaan.
- Ekspor EMIS saat ini berbentuk Excel; belum ditemukan konektor API atau modul SPMB pada repo eMadrasah.
- Inventaris sumber eMadrasah juga mencakup tagihan/pembayaran; modul tersebut ditunda dan dikeluarkan dari cakupan rilis awal School Suite.

## Prinsip implementasi

1. Semua tabel baru memiliki `school_id`, kebijakan server-side, audit, dan tes isolasi lintas sekolah.
2. Jangan menyalin tabel `users`, `siswa`, `gurus`, `kelas`, atau `tahun_pelajaran` eMadrasah ke School Suite. Hubungkan fitur TU ke modul Core dan Academic melalui application service.
3. Pertahankan batas modular School Suite. Komunikasi lintas modul memakai service/kontrak, bukan import model Infrastructure modul lain.
4. Simpan surat, arsip, lampiran tugas, dan dokumen pada private storage; unduhan harus mengotorisasi pemilik/role dan sekolah.
5. Tunda sinkronisasi berulang/API eksternal sampai klien memastikan kebutuhan dan menyediakan kontrak sistemnya. Untuk migrasi awal gunakan proses impor yang dapat dipreview, diulang dengan aman, dan direkonsiliasi.

## Task implementasi

### S15-T00 — Kunci kebutuhan klien dan prioritas rilis

- [ ] Wawancarai admin/TU untuk memastikan fitur wajib dan alur yang benar-benar dipakai.
- [ ] Tetapkan matriks aksi untuk role School Suite yang tersedia (`admin`, `operator`, `principal`, `teacher`, `homeroom`, `counselor`, `student`); putuskan apakah wali murid memerlukan role/portal baru.
- [ ] Tetapkan apakah data lama dimigrasikan satu kali atau perlu sinkronisasi berulang.
- [ ] Kunci aturan nomor surat, template kop/tanda tangan, disposisi, dan arsip.
- [ ] Tentukan kolom wajib dan pemetaan format EMIS yang dapat diadaptasi; contoh file klien dapat dipakai untuk penyesuaian berikutnya.
- **Hasil:** scope rilis pertama, matriks akses, pemetaan data awal, serta acceptance criteria disetujui klien.

### S15-T01 — Putuskan arsitektur dan kontrak modul

- [ ] Buat ADR untuk porting fitur TU ke School Suite, penyimpanan sekolah, pemakaian identity Core, private storage, dan batas API.
- [ ] Putuskan pembagian modul: `Administration` untuk surat/arsip/sarana/tugas; keuangan berada di luar scope rilis awal.
- [ ] Definisikan kontrak pemakaian siswa, guru, kelas, enrollment, periode, sekolah, user, dan audit dari modul pemilik.
- [ ] Tetapkan aturan kompatibilitas migrasi dan prosedur pemulihan sebelum tabel baru dirilis.
- **Hasil:** ADR diterima dan tes modular boundary mengunci keputusan tersebut.

### S15-T02 — Pemetaan data dan akses

- [ ] Buat field map eMadrasah ke School Suite untuk siswa, NIS/NISN/NISM/NIK, guru/NIP, kelas, tahun ajaran, dan akun.
- [ ] Petakan `admin` eMadrasah ke `admin`, `operator` ke `operator`, dan tautkan guru ke user/Teacher yang sudah ada; jangan membuat akun duplikat otomatis.
- [ ] Tentukan penanganan data ganda, akun tanpa email, relasi orang tua, data historis, serta data yang tidak memiliki padanan.
- [ ] Definisikan policy untuk akses admin dan TU/operator, serta akses guru hanya jika alur tertentu memang memerlukannya.
- **Hasil:** dokumen field map, aturan resolusi konflik, dan matriks otorisasi.

### S15-T03 — Fondasi schema, file, dan audit

- [ ] Tambahkan migrasi domain TU dengan `school_id`, foreign key, index, status, dan unique key sesuai aturan penomoran sekolah.
- [ ] Tambahkan model, application service, policy, route/controller, dan provider sesuai pola modular School Suite.
- [ ] Simpan berkas privat dengan validasi MIME/ukuran/ekstensi, audit unggah/unduh/hapus, dan otorisasi per sekolah.
- [ ] Tambahkan audit event untuk perubahan material seperti nomor surat, transaksi pembayaran, penghapusan, serta perubahan status.
- **Hasil:** migrasi fresh/rollback teruji, policy default deny, isolasi sekolah teruji, boundary test lulus.

### S15-T04 — Persuratan dan arsip akademik

- [ ] Implementasikan surat masuk: nomor agenda per sekolah/tahun, tanggal terima, asal, perihal, disposisi, status, pencarian, lampiran privat.
- [ ] Implementasikan surat keluar dan template surat, termasuk nomor surat, tujuan, tanggal, lampiran, cetak/PDF, dan pengaitan surat siswa ke record Academic.
- [ ] Implementasikan arsip akademik dengan kategori/periode dan lampiran privat; jangan menggandakan dokumen siswa yang sudah dikelola Academic.
- [ ] Uji nomor otomatis pada request bersamaan, update/hapus, validasi berkas berbahaya, akses berkas lintas sekolah, dan hasil cetak.
- **Hasil:** alur surat masuk → disposisi → selesai dan surat keluar → cetak/arsip dapat ditelusuri serta diuji.

### S15-T05 — Task staf/Tata Usaha

- [ ] Porting task dengan judul, uraian, kategori, prioritas, tenggat, progres, penanggung jawab, status, lampiran, dan riwayat perubahan.
- [ ] Batasi daftar assignee ke user aktif yang boleh ditugaskan dalam sekolah yang sama.
- [ ] Definisikan apakah guru boleh melihat/memperbarui task yang ditugaskan kepadanya; admin/TU mengelola keseluruhan task sekolah.
- [ ] Uji perubahan status, tenggat, lampiran, log aktivitas, dan penolakan assignee lintas sekolah.
- **Hasil:** task tidak bocor ke sekolah lain dan semua perubahan status tercatat.

### S15-T06 — Sarana-prasarana

- [ ] Porting kategori/aset, kode inventaris, lokasi, kondisi, status aktif, dan riwayat pemeliharaan.
- [ ] Implementasikan peminjaman/pengembalian aset dengan peminjam, tanggal, kondisi keluar/kembali, dan status keterlambatan.
- [ ] Tambahkan pencarian, filter, ringkasan aset rusak/dipinjam, serta lampiran bukti pemeliharaan bila diperlukan.
- [ ] Uji stok/ketersediaan aset, pencegahan peminjaman ganda, transaksi pengembalian, audit, dan isolasi sekolah.
- **Hasil:** inventaris dan siklus pinjam-kembali konsisten setelah operasi bersamaan.

### S15-T07 — Keuangan sekolah (ditunda)

- Tidak termasuk rilis awal. Modul, menu, route, dan tabel keuangan tidak disertakan; pekerjaan dapat dibuka kembali setelah prioritas serta aturan bisnis disepakati.

### S15-T08 — UI, navigasi, dan role workflow

- [ ] Implementasikan halaman TU memakai Inertia/Vue dan komponen School Suite; pertahankan pola form, tabel, empty/error/loading state, dan aksi row.
- [ ] Tambahkan menu berdasarkan capability/role; sembunyikan menu di UI dan tetap tegakkan policy di server.
- [ ] Uji alur desktop/mobile untuk daftar, formulir, detail, filter, unggah/unduh, cetak, dan konfirmasi aksi destruktif.
- [ ] Pastikan data siswa/guru yang ditampilkan mengambil sumber Academic yang sama dengan LMS/CBT.
- **Hasil:** satu navigasi School Suite, tanpa login atau salinan data akademik kedua.

### S15-T09 — Impor data awal dan ekspor EMIS

- [ ] Buat preview impor eMadrasah dari format yang disetujui: validasi header, NIS/NISN, kelas, tahun ajaran, relasi guru, dan duplikasi.
- [ ] Buat pemetaan ID legacy → ID School Suite dan simpan jejak batch impor untuk audit, retry, serta rekonsiliasi.
- [ ] Pastikan proses idempotent: pengulangan batch tidak menggandakan user, siswa, tagihan, surat, atau aset.
- [ ] Implementasikan ekspor EMIS dengan pemetaan kolom yang dapat disesuaikan; gunakan contoh klien untuk menyetel urutan kolom, label, dan format file.
- [ ] Uji dry-run, batch parsial, baris gagal, rollback/kompensasi, dan kecocokan jumlah record sumber/tujuan.
- **Hasil:** laporan rekonsiliasi impor dan file ekspor yang divalidasi klien.

### S15-T10 — Verifikasi dan kesiapan rilis

- [ ] Tambahkan feature tests untuk semua role, perubahan state, file privat, audit, validasi, dan isolasi lintas sekolah.
- [ ] Tambahkan E2E alur admin/TU, guru yang diberi akses, dan portal siswa/wali bila ada dampak perubahan.
- [ ] Jalankan `composer test`, `npm run build`, `npm run typecheck`, Pint, dan browser E2E pada environment terisolasi.
- [ ] Uji migrasi ke database staging dari backup salinan; lakukan restore drill dan verifikasi berkas.
- [ ] Tulis panduan operator/TU, panduan migrasi/onboarding, daftar konfigurasi, rollback, dan prosedur support.
- [ ] Lakukan UAT dengan data dan template klien; catat persetujuan sebelum cutover.
- **Gate rilis:** tidak ada akses lintas sekolah, hasil migrasi direkonsiliasi, backup/restore berhasil, acceptance UAT ditandatangani, dan checklist deployment lengkap.

## Urutan eksekusi yang disarankan

`S15-T00 → S15-T01 → S15-T02 → S15-T03 → S15-T04/S15-T05/S15-T06 → S15-T07 → S15-T08 → S15-T09 → S15-T10`.

S15-T04 sampai S15-T06 dapat berjalan paralel setelah schema, kontrak, dan matriks role disepakati. S15-T07 ditunda. S15-T09 dapat dimulai dengan pemetaan data eMadrasah yang tersedia lalu disesuaikan saat contoh format klien tersedia.
