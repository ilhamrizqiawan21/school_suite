'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const qa = require('../../resources/js/lib/reference/quickAnalysis.cjs');

const KUNCI20 = 'BADCACCBDABAADCDBCAB'.split('');
const setup20 = () => ({ nama: 'PTS Ganjil', kelas: 'VII A', jumlahSoal: 20, pilihan: 4, kunci: [...KUNCI20] });

test('validateSetup: menolak jumlah soal di luar 20-200 dan menerima batasnya', () => {
  assert.equal(qa.validateSetup(setup20()).valid, true);
  const kurang = { ...setup20(), jumlahSoal: 19, kunci: KUNCI20.slice(0, 19) };
  assert.match(qa.validateSetup(kurang).errors.join(' '), /minimal 20/i);
  const dua00 = { ...setup20(), jumlahSoal: 200, kunci: Array(200).fill('A') };
  assert.equal(qa.validateSetup(dua00).valid, true);
  const dua01 = { ...setup20(), jumlahSoal: 201, kunci: Array(201).fill('A') };
  assert.equal(qa.validateSetup(dua01).valid, false);
});

test('validateSetup: nama wajib, kunci lengkap dan sesuai pilihan', () => {
  assert.equal(qa.validateSetup({ ...setup20(), nama: '  ' }).valid, false);
  assert.equal(qa.validateSetup({ ...setup20(), nama: 'x'.repeat(101) }).valid, false);
  assert.equal(qa.validateSetup({ ...setup20(), kunci: KUNCI20.slice(0, 19) }).valid, false);
  const huruf = [...KUNCI20]; huruf[4] = 'E';
  assert.match(qa.validateSetup({ ...setup20(), kunci: huruf }).errors.join(' '), /soal 5/);
  assert.equal(qa.validateSetup({ ...setup20(), pilihan: 5, kunci: huruf }).valid, true);
  assert.equal(qa.validateSetup({ ...setup20(), pilihan: 6 }).valid, false);
  const kosong = [...KUNCI20]; kosong[9] = '';
  assert.match(qa.validateSetup({ ...setup20(), kunci: kosong }).errors.join(' '), /soal 10/);
});

test('makeItems: butir sintetis diterima mesin', () => {
  const items = qa.makeItems(KUNCI20, 4);
  assert.equal(items.length, 20);
  assert.deepEqual(items[0], { id: 's1', jenis: 'pg', teks: 'Soal 1', opsi: ['A', 'B', 'C', 'D'].map(l => ({ label: l, teks: l })), kunci: 'B' });
});

test('parseKeyText: tempel rapat, berspasi/koma, huruf kecil; menolak panjang/huruf salah', () => {
  const s = { jumlahSoal: 20, pilihan: 4 };
  assert.deepEqual(qa.parseKeyText('badcaccbdabaadcdbcab', s).kunci, KUNCI20);
  assert.deepEqual(qa.parseKeyText(KUNCI20.join(' '), s).kunci, KUNCI20);
  assert.deepEqual(qa.parseKeyText(KUNCI20.join(','), s).kunci, KUNCI20);
  assert.equal(qa.parseKeyText('BADC', s).valid, false);
  assert.match(qa.parseKeyText('BADCACCBDABAADCDBCAE', s).errors.join(' '), /soal 20/);
});

test('checkStudentRow: format campur diterima dan pesan jelas untuk baris tidak lengkap', () => {
  const s = setup20();
  const penuh = qa.checkStudentRow({ absen: '01', jawaban: 'BADCACCBDABAADCDBCAB' }, s);
  assert.equal(penuh.ok, true);
  assert.equal(penuh.message, '✓ 20/20');
  const pendek = qa.checkStudentRow({ absen: '03', jawaban: 'BAD' }, s);
  assert.equal(pendek.ok, false);
  assert.match(pendek.message, /baru 3 dari 20/);
  const panjang = qa.checkStudentRow({ absen: '04', jawaban: 'A'.repeat(22) }, s);
  assert.equal(panjang.ok, false);
  assert.match(panjang.message, /kelebihan 2/);
  const salah = qa.checkStudentRow({ absen: '05', jawaban: 'BADCACZBDABAADCDBCAB' }, s);
  assert.equal(salah.ok, false);
  assert.match(salah.message, /Z.*soal 7/);
  const campur = qa.checkStudentRow({ absen: '06', jawaban: 'b a, d  c , a c c b d a b a a d c d b c a . ' }, s);
  assert.equal(campur.ok, true);
  assert.equal(campur.jawaban[19], '.');
  assert.equal(campur.jawaban[0], 'B');
  assert.equal(qa.checkStudentRow({ absen: '07', jawaban: 'BADCACCBDABAADCDBC.-' }, s).ok, true);
  assert.equal(qa.checkStudentRow({ absen: '08', jawaban: 'BADCACCBDABAADCDBCAB,' }, s).ok, true);
  assert.equal(qa.checkStudentRow({ absen: '09', jawaban: 'BADCACCBDABAADCDBCAE' }, s).ok, false);
});

test('validateStudents: absen wajib, unik tanpa beda huruf, maksimal 20 karakter', () => {
  const s = setup20();
  const baris = (absen) => ({ absen, jawaban: KUNCI20.join('') });
  assert.equal(qa.validateStudents(s, [baris('1'), baris('01')]).valid, true, '1 dan 01 berbeda');
  const ganda = qa.validateStudents(s, [baris('A1'), baris('a1')]);
  assert.equal(ganda.valid, false);
  assert.match(ganda.errors.join(' '), /sudah digunakan/);
  assert.equal(qa.validateStudents(s, [baris('')]).valid, false);
  assert.equal(qa.validateStudents(s, [baris('x'.repeat(21))]).valid, false);
  assert.equal(qa.validateStudents(s, [baris('x'.repeat(20))]).valid, true);
  const ok = qa.validateStudents(s, [baris(' 07 ')]);
  assert.equal(ok.rows[0].absen, '07', 'spasi di ujung dipangkas');
  assert.equal(ok.status.length, 1);
});

function datasetBertingkat() {
  // Soal semua berkunci A. Siswa k menjawab benar soal j bila j < 20 - 2k, selebihnya B.
  const s = { nama: 'Uji', kelas: 'VII A', jumlahSoal: 20, pilihan: 4, kunci: Array(20).fill('A') };
  const rows = Array.from({ length: 8 }, (_, k) => ({
    absen: String(k + 1),
    jawaban: Array.from({ length: 20 }, (_, j) => (j < 20 - 2 * k ? 'A' : 'B')).join('')
  }));
  return { s, rows };
}

test('analyze: hasil mesin sesuai hitungan tangan dan peringatan sampel kecil', () => {
  const { s, rows } = datasetBertingkat();
  const r = qa.analyze(s, rows);
  assert.equal(r.success, true);
  assert.equal(r.result.butir.length, 20);
  assert.equal(r.result.butir[0].p, 1);
  assert.equal(r.result.butir[10].p, 0.625);
  assert.equal(r.result.butir[19].p, 0.125);
  assert.equal(r.result.ringkasan.peserta, 8);
  const teks = r.peringatan.join(' | ');
  assert.match(teks, /belum dapat diandalkan/);
  assert.match(teks, /mudah berubah/);
});

test('analyze: kurang dari 5 siswa ditolak; 10 siswa hanya diberi peringatan 30', () => {
  const { s, rows } = datasetBertingkat();
  const empat = qa.analyze(s, rows.slice(0, 4));
  assert.equal(empat.success, false);
  assert.match(empat.errors.join(' '), /minimal 5 siswa/i);
  const sepuluh = qa.analyze(s, [...rows, { absen: '9', jawaban: 'A'.repeat(20) }, { absen: '10', jawaban: 'B'.repeat(20) }]);
  assert.equal(sepuluh.success, true);
  assert.doesNotMatch(sepuluh.peringatan.join(' '), /belum dapat diandalkan/);
  assert.match(sepuluh.peringatan.join(' '), /mudah berubah/);
});

test('analyze: setup atau siswa tidak valid mengembalikan galat, bukan melempar', () => {
  const { s, rows } = datasetBertingkat();
  assert.equal(qa.analyze({ ...s, jumlahSoal: 10 }, rows).success, false);
  assert.equal(qa.analyze(s, [...rows.slice(0, 7), { absen: '8', jawaban: 'AAA' }]).success, false);
});

function deepFreeze(o) { Object.values(o).forEach((v) => { if (v && typeof v === 'object') deepFreeze(v); }); return Object.freeze(o); }
const stateAwal = () => ({
  nama: 'Uji', kelas: 'VII A', jumlahSoal: 20, pilihan: 4, kunci: [...KUNCI20],
  siswa: [{ absen: '01', jawaban: KUNCI20.join('') }, { absen: '02', jawaban: 'BADC' }]
});

test('applySetupChange: jumlah soal diperbesar menambah kunci kosong dan menandai siswa belum lengkap', () => {
  const r = qa.applySetupChange(deepFreeze(stateAwal()), { jumlahSoal: 25 });
  assert.equal(r.ok, true);
  assert.equal(r.state.jumlahSoal, 25);
  assert.equal(r.state.kunci.length, 25);
  assert.deepEqual(r.state.kunci.slice(20), ['', '', '', '', '']);
  assert.equal(r.state.siswa[0].jawaban, KUNCI20.join(''), 'jawaban siswa tidak diisi otomatis');
  assert.match(qa.checkStudentRow(r.state.siswa[0], r.state).message, /kurang 5/);
  assert.equal(qa.validateSetup(r.state).valid, false, 'kunci baru wajib diisi');
});

test('applySetupChange: jumlah soal diperkecil meminta konfirmasi bila ada data berharga dibuang', () => {
  const besar = qa.applySetupChange(stateAwal(), { jumlahSoal: 25 }).state;
  const kosongSaja = qa.applySetupChange(besar, { jumlahSoal: 20 });
  assert.equal(kosongSaja.ok, true, 'hanya kunci kosong yang dibuang: tidak perlu konfirmasi');

  const terisi = { ...besar, kunci: [...besar.kunci.slice(0, 20), 'A', 'B', 'C', 'D', 'A'],
    siswa: [{ absen: '01', jawaban: KUNCI20.join('') + 'ABCDA' }, { absen: '02', jawaban: 'BADC' }] };
  const tanya = qa.applySetupChange(deepFreeze(terisi), { jumlahSoal: 20 });
  assert.equal(tanya.ok, false);
  assert.equal(tanya.needsConfirm, true);
  assert.equal(tanya.impact.kunciDibuang, 5);
  assert.deepEqual(tanya.impact.jawabanDibuang, [{ absen: '01', jumlah: 5 }]);

  const setuju = qa.applySetupChange(terisi, { jumlahSoal: 20, confirm: true });
  assert.equal(setuju.ok, true);
  assert.equal(setuju.state.kunci.length, 20);
  assert.equal(setuju.state.siswa[0].jawaban, KUNCI20.join(''));
  assert.equal(setuju.state.siswa[1].jawaban, 'BADC', 'baris pendek tidak diformat ulang');
});

test('applySetupChange: batas jumlah soal 20-200 ditegakkan', () => {
  assert.equal(qa.applySetupChange(stateAwal(), { jumlahSoal: 19 }).ok, false);
  assert.equal(qa.applySetupChange(stateAwal(), { jumlahSoal: 201 }).ok, false);
  assert.equal(qa.applySetupChange(stateAwal(), { jumlahSoal: 2.5 }).ok, false);
});

test('applySetupChange: pilihan diperlebar diterima, dipersempit ditolak bila huruf masih dipakai', () => {
  assert.equal(qa.applySetupChange(stateAwal(), { pilihan: 5 }).ok, true);
  const lima = { ...stateAwal(), pilihan: 5, kunci: [...KUNCI20] };
  lima.kunci[6] = 'E';
  const tolakKunci = qa.applySetupChange(lima, { pilihan: 4 });
  assert.equal(tolakKunci.ok, false);
  assert.match(tolakKunci.errors.join(' '), /soal 7/);

  const limaJawab = { ...stateAwal(), pilihan: 5, siswa: [{ absen: '09', jawaban: 'E' + KUNCI20.join('').slice(1) }] };
  const tolakJawab = qa.applySetupChange(limaJawab, { pilihan: 4 });
  assert.equal(tolakJawab.ok, false);
  assert.match(tolakJawab.errors.join(' '), /09/);
  assert.match(tolakJawab.errors.join(' '), /soal 1/);

  const bersih = { ...stateAwal(), pilihan: 5 };
  const sempit = qa.applySetupChange(bersih, { pilihan: 4 });
  assert.equal(sempit.ok, true);
  assert.equal(sempit.state.pilihan, 4);
  assert.equal(qa.applySetupChange(stateAwal(), { pilihan: 6 }).ok, false);
});

test('applySetupChange: tidak memutasi state masukan', () => {
  const beku = deepFreeze(stateAwal());
  assert.doesNotThrow(() => qa.applySetupChange(beku, { jumlahSoal: 30 }));
  assert.doesNotThrow(() => qa.applySetupChange(beku, { pilihan: 5 }));
  assert.equal(beku.kunci.length, 20);
});

// ---- Task 3: lembar Excel ----
const A_E = 'ABCDE';
function lembarValid(opsi = {}) {
  const n = opsi.n || 20;
  const rows = [
    ['Nama tes', 'PTS Ganjil'], ['Kelas', 'VII A'], ['Pilihan jawaban', 'A-D'], [],
    ['No. Absen', ...Array.from({ length: n }, (_, i) => i + 1)],
    ['KUNCI', ...Array.from({ length: n }, (_, i) => A_E[i % 4])]
  ];
  for (let s = 1; s <= 6; s++) rows.push([String(s), ...Array.from({ length: n }, (_, i) => A_E[(i + s) % 4])]);
  return rows;
}

test('safeCell: netralkan awalan formula, biarkan angka dan teks biasa', () => {
  for (const v of ['=1+1', '+1', '-1', '@x', '\tx', '\rx']) assert.equal(qa.safeCell(v), `'${v}`);
  assert.equal(qa.safeCell('abc'), 'abc');
  assert.equal(qa.safeCell(5), 5);
  assert.equal(qa.safeCell(-0.12), -0.12);
  assert.equal(qa.safeCell(null), '');
});

test('templateRows: tata letak spec dan dapat dibaca kembali tanpa galat', () => {
  const rows = qa.templateRows(20, 4);
  assert.equal(rows[0][0], 'Nama tes');
  assert.equal(rows[2][0], 'Pilihan jawaban');
  assert.equal(rows[2][1], 'A-D');
  assert.equal(rows[4][0], 'No. Absen');
  assert.deepEqual(rows[4].slice(1), Array.from({ length: 20 }, (_, i) => i + 1));
  assert.equal(rows[5][0], 'KUNCI');
  assert.ok(rows.length >= 9, 'beberapa baris contoh');
  const parsed = qa.parseSheet(rows);
  assert.deepEqual(parsed.errors, []);
  assert.equal(parsed.setup.jumlahSoal, 20);
  assert.equal(parsed.setup.pilihan, 4);
  assert.equal(parsed.setup.kunci, undefined, 'kunci kosong pada templat diisi guru');
  assert.ok(parsed.warnings.join(' ').match(/kunci/i));
  assert.ok(parsed.siswa.length >= 3);
});

test('parseSheet: lembar valid memulihkan setup, kunci, dan siswa', () => {
  const p = qa.parseSheet(lembarValid());
  assert.deepEqual(p.errors, []);
  assert.deepEqual(p.setup, { nama: 'PTS Ganjil', kelas: 'VII A', pilihan: 4, jumlahSoal: 20, kunci: Array.from({ length: 20 }, (_, i) => A_E[i % 4]) });
  assert.equal(p.siswa.length, 6);
  assert.equal(p.siswa[0].absen, '1');
  assert.equal(p.siswa[0].jawaban.length, 20);
});

test('parseSheet: pilihan tidak tertulis disimpulkan dari huruf yang dipakai', () => {
  const rows = lembarValid();
  rows[2] = ['Pilihan jawaban', ''];
  assert.equal(qa.parseSheet(rows).setup.pilihan, 4);
  rows[6][3] = 'E';
  assert.equal(qa.parseSheet(rows).setup.pilihan, 5);
});

test('parseSheet: judul soal tidak berurutan, sel kosong, kunci sebagian, kolom berlebih ditunjuk jelas', () => {
  const loncat = lembarValid(); loncat[4][3] = 4;
  const e1 = qa.parseSheet(loncat).errors.map((e) => e.pesan).join(' ');
  assert.match(e1, /berurutan/i);

  const bolong = lembarValid(); bolong[8][7] = '';
  const e2 = qa.parseSheet(bolong).errors.map((e) => `${e.baris}:${e.pesan}`).join(' ');
  assert.match(e2, /9:.*soal 7/);

  const kunciSebagian = lembarValid(); kunciSebagian[5][5] = '';
  assert.match(qa.parseSheet(kunciSebagian).errors.map((e) => e.pesan).join(' '), /Kunci soal 5/);

  const lebih = lembarValid(); lebih[7].push('A');
  assert.ok(qa.parseSheet(lebih).errors.some((e) => e.baris === 8));

  const terlaluBanyak = qa.parseSheet(lembarValid({ n: 201 }));
  assert.match(terlaluBanyak.errors.map((e) => e.pesan).join(' '), /200/);

  const kurang = qa.parseSheet(lembarValid({ n: 19 }));
  assert.match(kurang.errors.map((e) => e.pesan).join(' '), /minimal 20/i);
  assert.equal(qa.parseSheet([['Nama tes', 'x']]).errors.length > 0, true, 'tanpa baris judul No. Absen');
});

test('parseSheet: baris kosong di antara siswa diabaikan dan huruf kecil/titik diterima', () => {
  const rows = lembarValid();
  rows.splice(8, 0, [], ['', '', '']);
  rows[10][2] = 'b'; rows[10][3] = '.';
  const p = qa.parseSheet(rows);
  assert.deepEqual(p.errors, []);
  assert.equal(p.siswa.length, 6);
  assert.equal(p.siswa[2].jawaban[1], 'B');
  assert.equal(p.siswa[2].jawaban[2], '.');
});

test('exportSheets: Jawaban round-trip, rumus dinetralkan, Hasil dan Ringkasan terisi', () => {
  const { s, rows } = datasetBertingkat();
  rows[0] = { ...rows[0], absen: '=1+1' };
  const analisis = qa.analyze(s, rows);
  const sheets = qa.exportSheets(s, rows, analisis.result);
  assert.equal(sheets.Jawaban[6][0], "'=1+1");
  const p = qa.parseSheet(sheets.Jawaban);
  assert.deepEqual(p.errors, []);
  assert.equal(p.setup.nama, 'Uji');
  assert.equal(p.setup.jumlahSoal, 20);
  assert.deepEqual(p.setup.kunci, s.kunci);
  assert.equal(p.siswa[0].absen, '=1+1', 'apostrof netralisasi dibuang saat impor ulang');
  assert.deepEqual(p.siswa.map((x) => x.jawaban), rows.map((r) => r.jawaban));
  assert.equal(sheets.Hasil.length, 21, 'judul + 20 soal');
  assert.equal(sheets.Hasil[1][0], 1);
  assert.ok(sheets.Ringkasan.flat().includes(8), 'jumlah siswa tercantum');
  assert.equal(JSON.stringify(sheets).includes('undefined'), false);
});

// ---- Task 4: deskripsi hasil ----
function semuaNilai(o, keluar = []) {
  if (o && typeof o === 'object') Object.values(o).forEach((v) => semuaNilai(v, keluar)); else keluar.push(o);
  return keluar;
}

test('describeResult: label, warna, sebaran, dan saran dalam bahasa guru', () => {
  const { s, rows } = datasetBertingkat();
  const d = qa.describeResult(qa.analyze(s, rows).result);
  assert.equal(d.baris.length, 20);
  const b1 = d.baris[0];
  assert.equal(b1.labelP, 'Mudah');
  assert.equal(b1.warnaP, 'sedang');
  assert.equal(b1.teksP, '1,00');
  const b11 = d.baris[10];
  assert.equal(b11.labelP, 'Sedang');
  assert.equal(b11.warnaP, 'baik');
  assert.equal(b11.teksP, '0,63', 'P 0,625 dibulatkan tampilan dengan koma desimal');
  d.baris.forEach((b) => {
    assert.equal(b.sebaran.reduce((a, o) => a + o.jumlah, 0), 8, `sebaran soal ${b.nomor} berjumlah peserta`);
    assert.deepEqual(b.sebaran.map((o) => o.label), ['A', 'B', 'C', 'D']);
    assert.equal(b.sebaran.filter((o) => o.kunci).length, 1);
    assert.ok(['Pertahankan', 'Revisi', 'Buang'].includes(b.saran));
    assert.ok(b.alasan.length > 0);
  });
  const mati = d.baris[10].sebaran.filter((o) => o.tidakBerfungsi).map((o) => o.label);
  assert.deepEqual(mati, ['C', 'D']);
  assert.match(d.baris[10].alasan, /Pengecoh C, D/);
  assert.notEqual(d.baris[10].saran, 'Pertahankan');
});

test('describeResult: jawaban seragam (varians nol) tidak menghasilkan NaN/undefined dan dijelaskan', () => {
  const s = { nama: 'Seragam', kelas: '', jumlahSoal: 20, pilihan: 4, kunci: Array(20).fill('A') };
  const rows = Array.from({ length: 6 }, (_, i) => ({ absen: String(i + 1), jawaban: 'A'.repeat(20) }));
  const d = qa.describeResult(qa.analyze(s, rows).result);
  const nilai = semuaNilai(d);
  assert.equal(nilai.some((v) => v === undefined || (typeof v === 'number' && Number.isNaN(v)) || (typeof v === 'string' && /NaN|undefined/.test(v))), false);
  assert.match(d.ringkasan.kalimatReliabilitas, /tidak dapat dihitung/i);
  assert.equal(d.baris[0].teksD, '—');
  assert.equal(d.baris[0].d, null);
  assert.equal(d.baris[0].warnaD, 'netral');
  assert.match(d.baris[0].alasan, /tidak dapat dihitung/i);
});

test('describeResult: peringatan menurut jumlah siswa dan panduan "Cara membaca"', () => {
  const { s, rows } = datasetBertingkat();
  const delapan = qa.describeResult(qa.analyze(s, rows).result);
  assert.equal(delapan.ringkasan.peserta, 8);
  assert.equal(delapan.ringkasan.peringatan.length, 2);
  const tigaPuluh = Array.from({ length: 30 }, (_, i) => ({ ...rows[i % 8], absen: String(i + 1) }));
  const banyak = qa.describeResult(qa.analyze(s, tigaPuluh).result);
  assert.deepEqual(banyak.ringkasan.peringatan, []);
  const istilah = delapan.caraMembaca.map((x) => x.istilah).join(' | ');
  assert.match(istilah, /Tingkat kesukaran/);
  assert.match(istilah, /Daya pembeda/);
  assert.match(istilah, /Pengecoh/);
  const arti = delapan.caraMembaca.map((x) => x.arti).join(' ');
  for (const angka of ['0,30', '0,70', '0,20', '0,40', '5%']) assert.ok(arti.includes(angka), `memuat ${angka}`);
});

// ---- Task 11: penerapan hasil impor ----
test('applyImport: impor lengkap memulihkan seluruh tes dan menimpa siswa', () => {
  const lama = { nama: 'Lama', kelas: 'VII A', jumlahSoal: 20, pilihan: 4, kunci: Array(20).fill('A'), siswa: [{ absen: '9', jawaban: 'A' }], langkah: 2 };
  const beku = deepFreeze(JSON.parse(JSON.stringify(lama)));
  const diimpor = {
    setup: { nama: 'Baru', kelas: 'VIII B', pilihan: 5, jumlahSoal: 25, kunci: Array(25).fill('E') },
    siswa: [{ absen: '1', jawaban: 'A'.repeat(25) }, { absen: '2', jawaban: 'B'.repeat(25) }]
  };
  const hasil = qa.applyImport(beku, diimpor);
  assert.equal(hasil.nama, 'Baru');
  assert.equal(hasil.kelas, 'VIII B');
  assert.equal(hasil.pilihan, 5);
  assert.equal(hasil.jumlahSoal, 25);
  assert.equal(hasil.kunci.length, 25);
  assert.equal(hasil.siswa.length, 2);
  assert.equal(hasil.langkah, 2, 'langkah aktif tidak berubah');
  assert.equal(beku.nama, 'Lama');
});

test('applyImport: tanpa kunci di berkas, kunci dikosongkan sesuai jumlah soal baru; info tes yang tak ada dipertahankan', () => {
  const lama = { nama: 'Tetap', kelas: 'VII A', jumlahSoal: 20, pilihan: 4, kunci: Array(20).fill('A'), siswa: [], langkah: 1 };
  const hasil = qa.applyImport(lama, { setup: { jumlahSoal: 22, pilihan: 4 }, siswa: [{ absen: '1', jawaban: 'A'.repeat(22) }] });
  assert.equal(hasil.nama, 'Tetap');
  assert.equal(hasil.kelas, 'VII A');
  assert.equal(hasil.jumlahSoal, 22);
  assert.equal(hasil.kunci.length, 22);
  assert.equal(hasil.kunci.every((k) => k === ''), true);
  const sama = qa.applyImport(lama, { setup: { jumlahSoal: 20 }, siswa: [] });
  assert.equal(sama.kunci.every((k) => k === 'A'), true, 'jumlah soal sama: kunci lama dipertahankan');
});

