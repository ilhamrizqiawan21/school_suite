# ADR-0010: Butir Bank Soal Berversi dan Snapshot Ujian

- Status: Accepted
- Tanggal: 25 Oktober 2026
- Task terkait: `BSC-01`

## Konteks

CBT saat ini menyimpan konten pada `exam_questions`, sementara `exam_question_items` dan `exam_answers` mengacu ke identitas butir tersebut. Jika isi bank soal diedit setelah ujian dibuat, hasil dan tampilan historis dapat membaca prompt atau kunci yang baru. Bank soal juga membutuhkan metadata yang dapat digunakan untuk pencarian dan analisis tanpa mengikat satu butir hanya ke satu penugasan mengajar.

## Keputusan

1. Pertahankan `exam_questions` sebagai identitas stabil butir dan pemiliknya. `school_id` menjadi batas sekolah; `owner_id` mencatat pembuat/pemilik.
2. Simpan revisi konten pada `exam_question_versions` dengan nomor versi unik per butir. Versi adalah snapshot immutable berisi tipe, prompt, opsi, kunci, penjelasan, poin default, dan metadata pendidikan.
3. Metadata pada versi mencakup mapel opsional, snapshot kode/nama mapel, jenjang, kurikulum, indikator, level kognitif, dan teks KKO. Relasi ke master KKO, kategori, dan tag dikerjakan pada `BSC-03`.
4. `exam_question_items` dan `exam_answers` dapat menunjuk `question_version_id`, sehingga ujian serta jawaban mengidentifikasi versi yang benar. Kolom `exam_question_id` tetap dipertahankan untuk kompatibilitas dan query pemilik.
5. Soal diarsipkan dengan `is_active = false`; hard delete tidak menjadi alur produk. Duplikasi membuat identitas butir baru. Versi historis tidak diedit atau dihapus secara langsung.
6. Untuk kompatibilitas data, migration membuat versi 1 dari setiap soal lama dan menghubungkan item ujian serta jawaban lama ke versi tersebut.

## Opsi yang dipertimbangkan

- Menggunakan satu baris `exam_questions` yang terus ditimpa: ditolak karena ujian historis berubah mengikuti konten terbaru.
- Menyalin semua konten langsung ke setiap item ujian: ditolak karena membuat salinan berulang dan menyulitkan pelacakan asal butir.
- Mengganti tabel CBT secara penuh dengan skema bank soal baru: ditolak untuk tahap ini karena berisiko pada alur attempt, hasil, dan data yang sudah ada.

## Konsekuensi

- Perubahan butir harus membuat versi baru dalam transaksi dan mengarahkan `current_version` ke versi tersebut.
- Builder ujian harus memilih versi aktif; setelah ujian dipublikasikan, versi item tidak berubah.
- Jawaban siswa tidak boleh bergantung pada versi terbaru bank soal.
- Poin pada versi adalah nilai default bank; bobot pada `exam_question_items` tetap menjadi bobot spesifik ujian.
- Master KKO dan relasi kategori/tag tetap menjadi pekerjaan lanjutan agar BSC-01 tidak membuat master duplikat.

## Migrasi dan rollback

Migration `2026_10_25_700000_add_question_bank_metadata_and_versions.php` menambah metadata, tabel versi, dan kolom referensi versi yang nullable untuk kompatibilitas. Backfill membuat versi 1 dan memasang referensi untuk item/jawaban lama. Migration lanjutan `2026_10_25_700001_add_matching_question_payloads.php` menambahkan payload terstruktur untuk menjodohkan dan rubrik esai. `down()` menghapus foreign key/kolom tambahan dan tabel versi; metadata baru pada identitas soal juga dilepas. Tidak ada tabel CBT lama yang dihapus.

## Bukti implementasi

- `app/Modules/Assessment/Infrastructure/Models/ExamQuestionVersion.php`
- Relasi versi pada `ExamQuestion`, `ExamQuestionItem`, dan `ExamAnswer`
- `docs/ERD.md`
- `docs/TASK_BANK_SOAL_CERDAS.md` / `BSC-01`
