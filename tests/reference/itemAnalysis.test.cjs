'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const analysis = require('../../resources/js/lib/reference/itemAnalysis.cjs');

const questions = [
  { id: 'q1', jenis: 'pg', teks: '1 + 1 = ?', opsi: [{ label: 'A', teks: '2' }, { label: 'B', teks: '3' }, { label: 'C', teks: '4' }], kunci: 'A', tujuan: { label: 'TP 1' }, levelKognitif: 'C1' },
  { id: 'q2', jenis: 'pg', teks: '2 + 2 = ?', opsi: [{ label: 'A', teks: '3' }, { label: 'B', teks: '4' }, { label: 'C', teks: '5' }], kunci: 'B', tujuan: { label: 'TP 1' }, levelKognitif: 'C2' },
  { id: 'q3', jenis: 'pg', teks: '3 + 3 = ?', opsi: [{ label: 'A', teks: '5' }, { label: 'B', teks: '6' }, { label: 'C', teks: '7' }], kunci: 'B', tujuan: null, levelKognitif: null }
];
const responses = [
  { siswaId: '01', jawaban: ['A', 'B', 'B'] },
  { siswaId: '02', jawaban: ['A', 'A', 'B'] },
  { siswaId: '03', jawaban: ['B', 'B', 'A'] },
  { siswaId: '04', jawaban: ['B', 'C', 'C'] }
];

test('itemAnalysis: validates 3–5 ordered options and a key in the option set', () => {
  assert.equal(analysis.validateQuestions(questions).valid, true);
  assert.match(analysis.validateQuestions([{ ...questions[0], opsi: questions[0].opsi.slice(0, 2) }]).errors[0], /3 sampai 5/);
  assert.match(analysis.validateQuestions([{ ...questions[0], kunci: 'E' }]).errors[0], /kunci/);
});

test('itemAnalysis: response validation identifies duplicate IDs, invalid answers, and count mismatch', () => {
  assert.equal(analysis.validateResponses(questions, responses).valid, true);
  const invalid = analysis.validateResponses(questions, [
    responses[0], { ...responses[1], siswaId: '01' }, { ...responses[2], jawaban: ['A', 'E', 'A'] }
  ]);
  assert.equal(invalid.valid, false);
  assert.ok(invalid.errors.some(e => e.includes('sudah digunakan')));
  assert.ok(invalid.errors.some(e => e.includes('butir 2')));
  assert.match(analysis.validateResponses(questions, [{ siswaId: 'x', jawaban: ['A'] }]).errors[0], /tepat 3/);
});

test('itemAnalysis: parser accepts separated and compact answers and normalizes blanks', () => {
  assert.deepEqual(analysis.parseAnswerLine('a, b, -', questions), { valid: true, jawaban: ['A', 'B', null] });
  assert.deepEqual(analysis.parseAnswerLine('ABC', questions), { valid: true, jawaban: ['A', 'B', 'C'] });
  assert.equal(analysis.parseAnswerLine('A D C', questions).valid, false);
  assert.equal(analysis.parseAnswerLine('A B', questions).valid, false);
});

test('itemAnalysis: calculates P, upper-lower D, corrected point-biserial, KR-20, SEM, and follow-up', () => {
  const result = analysis.analyzeAssessment({ butir: questions, hasil: responses, batasTuntasPersen: 60 });
  assert.equal(result.success, true);
  assert.equal(result.ringkasan.peserta, 4);
  assert.equal(result.ringkasan.rerataSkor, 1.5);
  // Hitung tangan: skor total 3,2,1,0; varians populasi 1,25; sum(pq)=0,75 -> KR-20 = 3/2*(1-0,75/1,25)=0,6.
  // SEM = SD sampel (1,291) * sqrt(1-0,6) = 0,8165 (versi lama memakai varians campuran: KR-20 0,825).
  assert.equal(result.ringkasan.kr20, 0.6);
  assert.ok(result.ringkasan.sem > 0.81 && result.ringkasan.sem < 0.82);
  assert.equal(result.butir[0].p, 0.5);
  assert.equal(result.butir[0].dayaPembeda, 1);
  assert.ok(result.butir[0].korelasiPointBiserial > 0);
  assert.equal(result.butir[0].opsi[2].pengecohMati, true);
  assert.equal(result.perTujuan[0].jumlahButir, 2);
  assert.equal(result.perLevelKognitif.length, 2);
  assert.deepEqual(result.tindakLanjut.remedial.map(s => s.siswaId), ['03', '04']);
  assert.deepEqual(result.tindakLanjut.pengayaan.map(s => s.siswaId), ['01', '02']);
});

test('itemAnalysis: KR-20 memakai rumus konsisten (sama dengan Alpha Cronbach pada skor 0/1)', () => {
  // Vektor uji Tahap H: 5 butir, opsi A-D, kunci semua A, 8 peserta; dihitung independen oleh peninjau.
  const mk = (n) => ({ id: `v${n}`, jenis: 'pg', teks: `Soal ${n}`, opsi: ['A', 'B', 'C', 'D'].map(label => ({ label, teks: label.toLowerCase() })), kunci: 'A' });
  const butir = [1, 2, 3, 4, 5].map(mk);
  const baris = { s1: 'AAAAA', s2: 'AAAAB', s3: 'AAABB', s4: 'AABBB', s5: 'ABBBB', s6: 'BBBCC', s7: 'AABBC', s8: 'ABABA' };
  const hasil = Object.entries(baris).map(([siswaId, jawaban]) => ({ siswaId, jawaban: [...jawaban] }));
  const r = analysis.analyzeAssessment({ butir, hasil });
  assert.equal(r.success, true);
  assert.deepEqual(r.butir.map(b => b.p), [0.875, 0.625, 0.5, 0.25, 0.25]);
  assert.deepEqual(r.butir.map(b => b.korelasiPointBiserial), [0.4664, 0.3307, 0.6708, 0.6019, 0.3333]);
  assert.equal(r.ringkasan.rerataSkor, 2.5);
  assert.equal(r.ringkasan.simpanganBaku, 1.604);
  assert.equal(r.ringkasan.kr20, 0.7118);
  assert.ok(Math.abs(r.ringkasan.sem - 1.6036 * Math.sqrt(1 - 0.7118)) < 0.002);

  // Silang-periksa: Alpha Cronbach pada matriks 0/1 dengan varians sampel untuk butir dan total.
  const skor = hasil.map(h => h.jawaban.map(a => (a === 'A' ? 1 : 0)));
  const varSampel = (v) => { const m = v.reduce((a, b) => a + b) / v.length; return v.reduce((a, x) => a + (x - m) ** 2, 0) / (v.length - 1); };
  const sumVarButir = butir.reduce((a, _, j) => a + varSampel(skor.map(row => row[j])), 0);
  const alpha = (5 / 4) * (1 - sumVarButir / varSampel(skor.map(row => row.reduce((a, b) => a + b))));
  assert.ok(Math.abs(r.ringkasan.kr20 - alpha) < 0.0001);
});

test('itemAnalysis: empty responses are wrong and constant scores produce undefined statistics', () => {
  const withBlank = analysis.analyzeAssessment({
    butir: questions.slice(0, 2),
    hasil: [
      { siswaId: '1', jawaban: ['.', '-'] },
      { siswaId: '2', jawaban: [null, null] },
      { siswaId: '3', jawaban: ['', null] }
    ]
  });
  assert.equal(withBlank.success, true);
  assert.equal(withBlank.ringkasan.kr20, null);
  assert.equal(withBlank.ringkasan.peringatanVariansNol, true);
  assert.equal(withBlank.butir[0].p, 0);
  assert.equal(withBlank.butir[0].dayaPembeda, null);
  assert.equal(withBlank.butir[0].korelasiPointBiserial, null);
});

test('itemAnalysis: small sample warning is displayed without suppressing valid metrics', () => {
  const result = analysis.analyzeAssessment({ butir: questions, hasil: responses });
  assert.equal(result.success, true);
  assert.equal(result.ringkasan.peringatanSampelKecil, true);
  assert.equal(typeof result.butir[0].p, 'number');
});

test('itemAnalysis: combined assessment requires identical device snapshot and namespaces student IDs', () => {
  const first = { kelas: 'VII A', perangkatId: 'dev', versiPerangkat: 1, butir: questions, hasil: [responses[0]] };
  const second = { kelas: 'VII B', perangkatId: 'dev', versiPerangkat: 1, butir: structuredClone(questions), hasil: [responses[0]] };
  const combined = analysis.canCombineAssessments([first, second]);
  assert.equal(combined.valid, true);
  assert.equal(combined.hasil[1].siswaId, 'VII B:01');
  assert.equal(analysis.validateResponses(questions, combined.hasil).valid, true);
  assert.equal(analysis.canCombineAssessments([first, { ...second, versiPerangkat: 2 }]).valid, false);
});


