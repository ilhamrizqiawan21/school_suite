# ADR-0011: Batas Integrasi eMadrasah ke School Suite

- Status: Accepted
- Tanggal: 27 Oktober 2026
- Task terkait: `S15-T01`

## Konteks

Klien meminta alur LMS, CBT, dan administrasi/TU tersedia dalam School Suite. eMadrasah yang ditinjau menyimpan identitas dan data akademik dengan skema berbeda serta belum memiliki batas sekolah pada tabelnya. Menggabungkan database atau menyalin entitas akademik akan menciptakan dua sumber kebenaran dan meningkatkan risiko kebocoran data antarsekolah. Keuangan ditunda dari rilis awal; format impor/ekspor EMIS dirancang agar pemetaan kolom dapat disesuaikan.

## Keputusan

1. School Suite menjadi satu-satunya sumber kebenaran untuk identitas pengguna, sekolah, siswa, guru, kelas, enrollment, dan periode akademik. Integrasi tidak membuat akun atau salinan master akademik secara otomatis.
2. Fitur administrasi eMadrasah diport ke modular monolith School Suite dan memakai database School Suite. Database tidak dibagi dengan eMadrasah; komunikasi/sinkronisasi berulang dengan aplikasi lain memerlukan kontrak API terpisah yang disetujui.
3. Data domain baru wajib memiliki `school_id`, foreign key, indeks/keunikan yang sesuai cakupan sekolah, serta pemeriksaan otorisasi server-side pada setiap operasi. Setiap operasi data dan berkas membatasi sekolah secara eksplisit; tidak ada asumsi bahwa middleware tenant global melakukannya.
4. Dokumen dan lampiran disimpan di private storage. Unggah, unduh, dan penghapusan harus memvalidasi berkas, memeriksa policy dan cakupan sekolah, serta mencatat audit memakai fasilitas School Suite.
5. Tahap pertama memakai impor satu kali yang dapat dipreview, aman diulang, dan menghasilkan rekonsiliasi. Impor tidak boleh menggandakan pengguna atau master akademik; relasi legacy dipetakan ke identitas School Suite yang sudah ada.
6. Keuangan tidak termasuk rilis awal. Pemetaan EMIS tidak dikunci ke satu template; kolom dan urutan dapat disesuaikan menggunakan spesifikasi yang tersedia.

## Opsi yang dipertimbangkan

- **Memakai database bersama eMadrasah dan School Suite**: ditolak karena skema serta pemilik identitas berbeda, dan eMadrasah belum memiliki batas `school_id` yang dapat menjamin isolasi multi-sekolah.
- **Mempertahankan eMadrasah sebagai aplikasi terpisah dengan sinkronisasi dua arah**: ditunda karena membutuhkan kontrak API, aturan konflik, dan sumber kebenaran per entitas yang belum disepakati klien.
- **Memisahkan fitur TU menjadi microservice**: ditolak untuk tahap ini karena menambah deployment, autentikasi layanan, dan operasi data lintas sistem tanpa kebutuhan skala atau batas operasional yang terbukti.
- **Menyalin pengguna dan entitas akademik eMadrasah ke tabel baru**: ditolak karena akan menggandakan data siswa/guru/kelas dan membuat konflik dengan LMS/CBT School Suite.

## Konsekuensi

### Positif

- Semua modul School Suite memakai identitas dan data akademik yang sama.
- Batas sekolah, policy, dan audit dapat ditegakkan melalui pola aplikasi yang sudah ada.
- Impor awal dapat diulang dan diverifikasi tanpa menjadikan dua aplikasi sumber data aktif.

### Trade-off

- Implementasi harus menghubungkan fitur TU ke layanan/model pemilik data School Suite, bukan membaca database eMadrasah langsung.
- Migrasi data memerlukan pemetaan identitas dan penanganan eksplisit untuk record yang tidak cocok atau ganda.
- Sinkronisasi berulang, gateway pembayaran, dan modul keuangan tidak termasuk scope rilis awal.

### Risiko dan mitigasi

- **Akses lintas sekolah melalui ID atau URL langsung**: setiap query, policy, route binding, dan unduhan berkas memeriksa `school_id`; tambahkan tes isolasi lintas sekolah.
- **Data impor salah atau berulang**: gunakan preview, pemetaan ID legacy, idempotensi batch, dan laporan jumlah record sumber/tujuan sebelum cutover.
- **Format EMIS klien berbeda dari format awal**: gunakan pemetaan kolom yang dapat disesuaikan dan validasi file hasil sebelum dipakai.

## Migrasi dan rollback

Tambahkan tabel domain baru sebagai migrasi aditif di modul School Suite dengan `school_id` wajib dan relasi ke master Core/Academic yang sudah ada. Impor awal berjalan melalui preview dan batch yang dapat direkonsiliasi sebelum cutover; jangan menghapus sumber eMadrasah atau record School Suite saat migrasi. Untuk rollback, hentikan alur baru dan pulihkan backup sebelum melepas tabel domain; pertahankan laporan pemetaan dan batch impor agar pemulihan dapat ditelusuri.

## Bukti dan prasyarat rilis

- Implementasi harus memiliki policy dan tes deny-by-default serta isolasi lintas sekolah untuk data dan private files.
- Impor harus menunjukkan idempotensi, penanganan kegagalan parsial, dan rekonsiliasi sebelum data digunakan.
- Keuangan tetap ditunda; pemetaan ekspor EMIS harus dapat disesuaikan dan diverifikasi saat format klien tersedia.
- Rincian urutan implementasi dan acceptance criteria dicatat di `docs/EMADRASAH_INTEGRATION_TASKS.md`.
