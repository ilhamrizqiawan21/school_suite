# School Suite

School Suite adalah aplikasi web terpadu untuk manajemen akademik, pembelajaran, penilaian, dan tata kelola sekolah. Target pertama adalah satu sekolah per instalasi. Produk harus tetap berguna tanpa Teacher Desk; integrasi desktop hadir setelah kedua produk stabil secara mandiri.

## Dokumen utama

| Pertanyaan | Sumber kebenaran |
|---|---|
| Mengapa produk dibuat dan untuk siapa? | [PRD](PRD.md) |
| Apa kebutuhan yang harus dipenuhi? | [Requirements](REQUIREMENTS.md) |
| Bagaimana pengalaman pengguna dirancang? | [Design](DESIGN.md) |
| Bagaimana sistem dibangun? | [Architecture](ARCHITECTURE.md) |
| Bagaimana data saling berhubungan? | [ERD](ERD.md) |
| Keputusan apa yang sudah dikunci? | [Decisions](DECISIONS.md) dan [ADR](adr/README.md) |
| Apa yang dikerjakan berikutnya? | [Roadmap](ROADMAP.md), [Status](PROJECT_STATUS.md), dan [TODO](TODO.md) |
| Bagaimana AI bekerja di repo ini? | [AI Rules](AI_RULES.md), [Gemini](GEMINI.md), [Codex Review](CODEX_REVIEW.md) |
| Bagaimana implementasi diserahkan untuk review? | [Handoff](HANDOFF.md) |
| Bukti pengujian apa yang wajib? | [Tests](TESTS.md) dan [Regression](REGRESSION.md) |

## Batas produk

- Termasuk: master akademik, jadwal, absensi, jurnal, wali kelas, penilaian/rapor, LMS, CBT, SPMB, perpustakaan, administrasi, sarpras, dan pelaporan bertahap.
- Ditunda: keuangan institusi sampai rancangan ledger disetujui.
- Tidak termasuk: tabungan siswa dan catatan pribadi guru yang tidak dipilih untuk dibagikan.
- Repo lama adalah sumber kebutuhan dan bukti perilaku, bukan sumber kode yang otomatis boleh disalin.

## Pembagian peran

- **Pemilik produk:** pengguna; menetapkan prioritas dan menerima hasil.
- **Codex:** arsitek dan reviewer; menjaga batas domain, keputusan, risiko, dan bukti penerimaan. Codex tidak menjadi pelaksana utama fitur.
- **Gemini:** eksekutor implementasi; membuat kode, migrasi, tes, dan dokumentasi sesuai task yang sudah siap.

Alur kerja: keputusan/brief disiapkan → Gemini mengimplementasikan satu task → Gemini mengisi `HANDOFF.md` → Codex meninjau diff dan bukti → Gemini memperbaiki temuan → pemilik produk menerima.

