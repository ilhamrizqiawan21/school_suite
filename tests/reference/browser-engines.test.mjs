import test from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import JSZip from 'jszip';
import engine from '../../resources/js/lib/reference/quickAnalysis.js';
import { normalizeExamPackage, buildExamDocx, buildExamHtml } from '../../resources/js/lib/reference/examPackage.js';
const require = createRequire(import.meta.url);
const original = require('../../resources/js/lib/reference/quickAnalysis.cjs');

test('browser analysis matches original calculations and Excel roundtrip', () => {
    const setup = { nama: 'Sumatif', kelas: 'VII A', jumlahSoal: 20, pilihan: 4, kunci: [...'ABCDABCDABCDABCDABCD'] };
    const siswa = Array.from({ length: 30 }, (_, i) => ({ absen: String(i + 1), jawaban: Array.from({ length: 20 }, (_, q) => i % (q + 2) ? setup.kunci[q] : 'B').join('') }));
    const input = { setup, siswa };
    const actual = engine.prepareRun(input);
    assert.equal(actual.success, true);
    assert.deepEqual(actual, original.prepareRun(input));
    const sheets = engine.exportSheets(setup, actual.rows, actual.result);
    const imported = engine.applyImport({ ...setup, siswa: [] }, engine.parseSheet(sheets.Jawaban));
    assert.deepEqual(engine.prepareRun({ setup: imported, siswa: imported.siswa }).result, actual.result);
    assert.equal(engine.validateDraft({ ...setup, siswa, langkah: 3 }).valid, true);
});

for (const columns of [1, 2, 3]) {
    for (const mode of ['paper', 'key', 'blueprint']) {
        test(`Word and preview share grouped numbering: ${columns} columns / ${mode}`, async () => {
            const questions = [
                { jenis: 'essay', teks: 'Uraian terakhir', pedomanSkor: 'Jawaban uraian', kunci: 'Model jawaban', levelKognitif: 'C6' },
                { jenis: 'menjodohkan', teks: 'Pasangan tengah', kunci: 'Pasangan benar' },
                { jenis: 'pg', teks: 'PG pertama <script>', kunci: 'B', opsi: ['Satu', 'Dua', 'Tiga', 'Empat'].map((teks, i) => ({ label: String.fromCharCode(65 + i), teks })) },
            ];
            const exam = normalizeExamPackage({ nama: 'Ujian', mapel: 'IPA', kelas: 'VII A', tahunPelajaran: '2026/2027', paper: { ukuran: 'F4', orientasi: 'landscape', columns }, items: questions.map(snapshot => ({ snapshot })) });
            const html = buildExamHtml(exam, [], {}, mode);
            assert.ok(!html.includes('<script>'));
            if (mode !== 'key') assert.ok(html.indexOf('PG pertama') < html.indexOf('Pasangan tengah') && html.indexOf('Pasangan tengah') < html.indexOf('Uraian terakhir'));
            const blob = await buildExamDocx(exam, [], {}, mode);
            const zip = await JSZip.loadAsync(await blob.arrayBuffer());
            const xml = await zip.file('word/document.xml').async('string');
            assert.ok(xml.includes('w:orient="landscape"'));
            assert.ok(xml.includes('KUNCI JAWABAN') || xml.includes('KISI-KISI') || xml.includes('PILIHAN GANDA'));
            assert.ok((await zip.file('word/footer1.xml').async('string')).includes('PAGE'));
            if (mode === 'paper') assert.ok(xml.indexOf('PG pertama') < xml.indexOf('Pasangan tengah') && xml.indexOf('Pasangan tengah') < xml.indexOf('Uraian terakhir'));
        });
    }
}
