# Prompt inisiasi Gemini · School Suite

Salin blok berikut ke sesi Gemini yang dibuka dari root repositori School Suite.

```text
Anda adalah pelaksana implementasi School Suite. Target proyek adalah 100% paritas fitur MAN 4 Banjar dari portal publik sampai tindakan setiap peran dan hasil akhir pengguna. Gunakan identitas visual School Suite, dengan hierarki, pengelompokan layanan, dan kemudahan navigasi yang terinspirasi dari situs acuan.

Baca sumber dalam repositori sebelum mengubah kode.

1. GEMINI.md dan docs/GEMINI.md untuk aturan kerja proyek.
2. docs/RENCANA_PARITAS_MAN4_BANJAR.md untuk cakupan dan bukti REA.
3. docs/REQUIREMENTS_PARITAS_MAN4_BANJAR.md untuk 48 ID awal, rincian tambahan REA, dan keputusan klien.
4. docs/TODO_PARITAS_MAN4_BANJAR.md untuk urutan pekerjaan, dependensi, dan kriteria penutupan.
5. docs/mockup/figcraft-paritas/CAKUPAN.md, index.html, dan school-suite-paritas.figcraft untuk 122 artboard dan penelusuran per ID.
6. docs/PETA_ALUR_DAN_PRESENTASI_PARITAS.md untuk hasil konkret, kasus pengecualian, dan urutan demo klien.

Periksa git status, aturan repositori, dan pekerjaan yang sudah ada. Pertahankan perubahan lokal yang sedang dikerjakan. Telusuri entry point, layout, komponen, rute, model, kebijakan akses, penyimpanan, dan tes yang relevan. Gunakan kembali fondasi yang memenuhi kriteria. Catat apa yang sudah tersedia, sebagian tersedia, belum ada, atau menunggu keputusan.

Mulai dari paket FND, UX, dan PUB yang diperlukan untuk satu irisan beranda dan E-Layanan. Setelah baseline diketahui, kerjakan navigasi publik yang dapat membuka layanan dari titik masuk normal pada desktop dan ponsel. Sertakan pencarian, pengelompokan layanan, akses keyboard dan pengelolaan fokus, status layanan, serta konsistensi visual dengan mockup. Uji 1280 × 720, 390 × 844, dan lebar menengah. Pastikan menu dan tombol bantuan tidak menutupi tindakan. Lanjutkan ke satu alur PTSP vertikal setelah fondasi terkait tersedia. Gunakan resi yang sama dari pengajuan, pemeriksaan, revisi, sampai penerbitan dan pelacakan.

Gunakan bukti REA yang sudah tercatat. Jika REA tersedia, periksa perilaku publik yang masih terbuka melalui navigasi biasa. Untuk panel internal situs pihak ketiga, gunakan demo resmi, materi yang diizinkan untuk dibagikan, atau akun uji dari pengelola. Catat batas pengamatan. Jika muncul temuan baru, tambahkan atau perbarui requirement, TODO, peta artboard, dan skenario demo yang terkait. Pisahkan fitur teramati di acuan, aturan yang disetujui klien, dan hasil yang lulus uji di School Suite. Jangan menyatakan 100% tercapai selama inventaris peran masih terbuka atau hasil uji belum lengkap.

Jika aturan internal belum diketahui, lanjutkan pekerjaan yang dapat dibuktikan. Catat keputusan terbuka dengan ID kebutuhan, bukti yang dibutuhkan, dampak, dan pemilik keputusan. Gunakan data contoh. Uji alur utama, revisi/penolakan, izin server dan akses lintas sekolah/peran, pengiriman ulang, dan penanganan kegagalan sesuai cakupan irisan. Jalankan build dan tes yang relevan.

Pada akhir sesi, laporkan ID yang dikerjakan, berkas yang berubah, cara memperagakan hasil, pengujian yang benar-benar dijalankan beserta hasilnya, keputusan yang masih terbuka, dan irisan berikutnya. Centang TODO hanya jika kriteria terkait sudah terbukti. Mulai dengan membaca dokumen, menilai baseline, dan mengerjakan irisan pertama yang siap.
```
