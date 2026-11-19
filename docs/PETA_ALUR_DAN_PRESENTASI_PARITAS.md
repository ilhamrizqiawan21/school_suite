# Peta alur dan presentasi paritas School Suite

Pelaksana kode: Gemini. Gunakan [requirements](REQUIREMENTS_PARITAS_MAN4_BANJAR.md), [TODO](TODO_PARITAS_MAN4_BANJAR.md), dan [peta layar](mockup/figcraft-paritas/CAKUPAN.md) bersama dokumen ini. MAN 4 Banjar menjadi acuan kemampuan yang terlihat publik dan pola penyajian. Untuk setiap langkah petugas di balik login, validasi hasil dan aturan dengan sekolah klien. Jangan beri label terverifikasi pada perilaku internal yang belum diperlihatkan.

## Peta alur dari masuk sampai hasil

| Alur dan kebutuhan | Yang diperagakan berurutan | Hasil yang diperiksa klien | Kasus pembanding |
|---|---|---|---|
| Portal, konten, dan desain · UX/PUB | Pengelola membuat draf dan menerbitkan konten → pengunjung membuka beranda → navigasi E-Layanan, pencarian, profil/GTK, program/fasilitas, berita/agenda, prestasi, galeri/video, kontak/peta dan unduhan | Konten terbit tampak pada desktop dan ponsel. Pintasan membuka tujuan yang tepat. | Draf tetap privat. Menu dapat digunakan dengan keyboard dan tombol bantu tidak menutupi tautan. |
| Zona Integritas dan IKM · PUB | Baca maklumat dan dokumentasi → beri penilaian → pengelola meninjau agregat | Pengunjung melihat publikasi dan agregat yang disetujui. | Layanan ditutup, input tidak valid, data penilaian individu. |
| Informasi pendukung · PUB/INT | Buka jadwal salat, statistik trafik, kontak dan kanal bantuan | Informasi sesuai konfigurasi sekolah dan zona waktu. | Sumber jadwal/chat gagal. Status dan kanal cadangan jelas. |
| Akun dan PTSP · TU/FND | Daftar/masuk → pilih layanan → unggah → terima resi → TU memeriksa/meminta revisi → pemohon memperbaiki → petugas terbitkan hasil → unduh/ambil | Resi, status, histori yang boleh dilihat, dan dokumen akhir sesuai satu pengajuan. | Akun lain, berkas tidak valid, kirim ganda, penolakan, layanan tutup. |
| Surat dinas dan buku tamu · TU | Instansi unggah surat → TU terima dan disposisi → instansi lacak → resepsionis daftar tamu dan catat kunjungan | Tanda terima dan status surat cocok dengan agenda. Kunjungan muncul pada rekap petugas. | Nomor surat berulang, lampiran rusak, data tamu dibatasi. |
| Pelacakan terpadu · TU/FND | Buka Lacak Berkas → pilih atau kenali jenis layanan → verifikasi resi → tampilkan status PTSP, izin, legalisir atau mutasi | Status dan nomor referensi cocok dengan catatan layanan terkait. | Resi salah/kedaluwarsa, verifikasi gagal dan akses data orang lain. |
| Izin siswa · OPS | Wali pilih siswa dan tanggal → lampirkan bukti → gunakan token jika disyaratkan → wali kelas putuskan → wali lihat hasil → presensi diperbarui sesuai SOP | Bukti izin, keputusan dan rekap presensi konsisten. | Tanggal bentrok, batas waktu, token kedaluwarsa/terpakai, koreksi keputusan. |
| Kios presensi · OPS | Petugas daftarkan kartu → siswa tap RFID atau QR → kios memberi hasil → petugas cek rekap dan koreksi | Rekap menampilkan waktu dan hasil yang sama. | Scan ulang, kartu asing, jaringan/perangkat gagal. |
| Piket, satpam dan BK · OPS | Piket terbitkan izin → satpam validasi dan catat keluar/kembali → BK catat kejadian dan tindak lanjut pada alur terpisah | Riwayat perlintasan konsisten. Kasus BK hanya tampil ke peran berwenang. | Izin kedaluwarsa/dibatalkan, kode manual sesuai SOP, akses kasus oleh role lain. |
| PPDB · LIF | Calon daftar dan unggah → panitia periksa, nilai dan umumkan → calon lihat hasil dan daftar ulang → petugas konversi | Bukti pendaftaran, hasil seleksi dan bukti daftar ulang dapat diperiksa. | Periode tutup, hasil belum rilis, kuota dan konversi ganda. |
| Mutasi · LIF | Wali pilih masuk/keluar → lengkapi berkas → TU verifikasi dan putuskan → terbitkan surat → wali lacak | Surat sesuai arah mutasi dan histori enrollment utuh. | Permintaan kurang lengkap, penolakan dan pencabutan. |
| Kelulusan dan verifikasi · LIF | Operator siapkan hasil → pejabat sahkan → jadwalkan rilis → siswa cek dan unduh SKL → pihak berwenang verifikasi dokumen | SKL sesuai keputusan dan dapat diverifikasi setelah rilis. | Sebelum rilis, kode salah atau dokumen dicabut. |
| Legalisir, kuasa dan SPTJM · LIF | Alumni ajukan → pilih langsung/kuasa → buat dokumen pendukung → TU periksa fisik dan register → catat pengesahan/penyerahan | Pemohon melihat progres. Petugas dapat menunjukkan register, dokumen dan bukti penyerahan versi yang benar. | Kuasa tanpa syarat, jumlah lembar keliru, PDF lama dan koreksi. |
| Alumni dan tracer · LIF | Alumni perbarui profil → petugas verifikasi/publikasi → cari dengan filter → lihat karier/beasiswa dan E-KTA | Data yang ditampilkan cocok dengan persetujuan publikasi. | Profil belum disetujui, data sensitif, kartu milik orang lain. |
| Siap Lapor · SERV | Pelapor pilih kategori dan mode anonim/bernama → kirim → satgas telaah dan tindak lanjuti → pelapor cek tiket → publik lihat ringkasan yang disetujui | Status, balasan, dan ringkasan mencerminkan hasil yang sama dengan tingkat rincian sesuai akses. | Lampiran gagal, tiket salah, identitas anonim dan data kasus terlindungi. |
| Perpustakaan · SERV/INT | Cari koleksi/e-book → catat kunjungan → usul buku → pustakawan putuskan → cek bebas pustaka | Pengunjung menemukan koleksi, usul tercatat, dan hasil bebas pustaka cocok dengan status pinjaman. | Koleksi terbatas, pinjaman aktif dan sumber SLiMS gagal. |
| Proposal dan LPJ · INT | Guru/pengaju kirim proposal dan RAB → reviewer minta revisi/putuskan → pengaju catat realisasi dan bukti → verifikator tutup LPJ | Versi RAB, status dana sesuai akses, dan arsip LPJ dapat ditelusuri. | Revisi berulang, bukti kurang, status dana belum tersedia. |
| Integrasi dan operasi · INT/FND/REL | Operator tunjukkan koneksi yang disepakati, contoh impor/ekspor atau tautan resmi → rekonsiliasi gagal parsial → peragakan audit, pemulihan dan pemeriksaan kesehatan di lingkungan uji | Hasil integrasi dapat diperiksa dengan data contoh. Database dan berkas dapat dipulihkan serta diakses sesuai izin. | Baris gagal, kirim ulang, kehilangan koneksi, akses lintas sekolah, dan rollback. |

## Urutan presentasi klien

Sertakan pembuktian kontrol publik yang ditemukan REA. Peragakan filter pelacakan, kamera dan layar penuh kios, pintu masuk pos, jalur manual BK, pencarian verifikasi, serta contoh proposal, susunan panitia dan editor item RAB. Catat konfirmasi peran dan hasil untuk aksi yang belum diperagakan oleh pengelola.

1. Buka portal pada desktop dan ponsel. Tunjukkan cara menemukan layanan dari menu, pencarian dan pintasan. Gunakan merek dan konten contoh sekolah klien.
2. Peragakan satu pengajuan lengkap dengan PTSP. Pakai satu nomor resi yang sama pada layar pemohon, layar petugas, hasil unduh dan histori. Peragakan revisi, lalu penyelesaian.
3. Beralih per peran untuk alur lain. Tunjukkan hak akses, antrean awal, tindakan yang wajar, keputusan dan hasil yang terlihat oleh pengguna. Gunakan tabel di atas sebagai checklist demo.
4. Perlihatkan hasil akhir yang memang dipakai sekolah, seperti resi, tanda terima, bukti izin, surat, SKL, ringkasan, dan ekspor yang disetujui. Cocokkan isi, nama file, nomor, tanggal, versi, dan hak unduh.
5. Catat bukti dan keputusan klien per alur. Skenario lulus memiliki tautan ke requirements, layar, hasil uji, serta tanggal dan penerima. Temuan yang memengaruhi hasil masuk TODO dengan pemilik dan kriteria uji ulang.

## Standar kerapian sebelum demo

- Tinjau desktop 1280 × 720, ponsel 390 × 844, dan lebar menengah. Periksa header, panel menu, tabel, formulir, dialog, footer, dan tombol bantuan. Kontrol tidak bertumpuk, teks penting terbaca, dan status punya label.
- Gunakan hierarki tipografi, jarak, lebar kolom, label, ikon, format tanggal dan penempatan tindakan yang konsisten. Periksa layar kosong, memuat, gagal, ditolak, berhasil, dan dokumen siap unduh.
- Jalankan demo dari titik masuk normal dengan data contoh konsisten dan akun per peran. Validasi data, aksi, audit, dan hasil di aplikasi berjalan. Gunakan mockup untuk menilai desain.
- Sebelum penutupan, cocokkan seluruh 48 ID requirements dengan peta mockup, TODO, dan bukti hasil uji. Pisahkan status **teramati di acuan**, **disetujui klien**, dan **lulus di School Suite**.
