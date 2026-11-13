// Reference: jurnal-harian-guru @ 27b2eaaf69c1f8e555fb3198c4150a837500322e

const randomUUID = () => crypto.randomUUID();

const JENIS = new Set(['pg', 'essay', 'menjodohkan']);
const PAPER = new Set(['A4', 'F4']);
const ORIENTASI = new Set(['portrait', 'landscape']);
const MAX_ITEMS = 200;

const clean = (value, max = 500) => String(value ?? '').replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '').trim().slice(0, max);

function exportFilename(exam, label, format) {
  const paper = normalizePaper(exam?.paper);
  const name = String(exam?.nama || 'Paket_Soal')
    .replace(/[<>:"/\\|?*\x00-\x1f]/g, '_')
    .replace(/\b(?:portrait|potrait|landscape)\b/gi, paper.orientasi)
    .replace(/\b(?:a4|f4)\b/gi, paper.ukuran)
    .slice(0, 80).replace(/[. ]+$/, '');
  return `${label}_${name || 'Paket_Soal'}.${format}`;
}

function normalizePaper(input = {}) {
  const paper = input && typeof input === 'object' ? input : {};
  const columns = Math.max(1, Math.min(3, Number.parseInt(paper.columns, 10) || 1));
  return {
    ukuran: PAPER.has(String(paper.ukuran || '').toUpperCase()) ? String(paper.ukuran).toUpperCase() : 'A4',
    orientasi: ORIENTASI.has(paper.orientasi) ? paper.orientasi : 'portrait',
    columns
  };
}

function normalizeKop(input = {}, profile = {}) {
  const kop = input && typeof input === 'object' ? input : {};
  return {
    namaLembaga: clean(kop.namaLembaga ?? profile.namaMadrasah, 160),
    yayasan: clean(kop.yayasan, 160),
    keterangan: clean(kop.keterangan, 300),
    gambar: typeof kop.gambar === 'string' && kop.gambar.length <= 3 * 1024 * 1024 && /^data:image\/(png|jpe?g);base64,[A-Za-z0-9+/=]+$/.test(kop.gambar) ? kop.gambar : '',
    rasioGambar: Math.max(2, Math.min(12, Number(kop.rasioGambar) || 5)),
    alamat: clean(kop.alamat, 240),
    judul: clean(kop.judul || 'PENILAIAN SUMATIF', 160),
    subjudul: clean(kop.subjudul || 'NASKAH SOAL', 160),
    petunjuk: clean(kop.petunjuk || 'Berdoalah sebelum mengerjakan. Bacalah setiap soal dengan teliti.', 1000)
  };
}

function normalizeExamPackage(input = {}, profile = {}) {
  const now = new Date().toISOString();
  const source = input && typeof input === 'object' ? input : {};
  return {
    id: typeof source.id === 'string' && source.id ? source.id : randomUUID(),
    nama: clean(source.nama || 'Paket Soal', 160),
    mapel: clean(source.mapel, 120),
    kelas: clean(source.kelas, 120),
    semester: clean(source.semester || profile.semester, 40),
    tahunPelajaran: clean(source.tahunPelajaran || profile.tahunPelajaran, 40),
    tanggal: clean(source.tanggal, 30),
    waktu: clean(source.waktu, 60),
    questionIds: Array.isArray(source.questionIds) ? [...new Set(source.questionIds.filter(id => typeof id === 'string'))].slice(0, MAX_ITEMS) : [],
    items: Array.isArray(source.items) ? source.items.slice(0, MAX_ITEMS).map((item, index) => ({
      sourceId: typeof item?.sourceId === 'string' ? item.sourceId : (typeof item?.id === 'string' ? item.id : ''),
      nomor: Number.isInteger(item?.nomor) ? item.nomor : index + 1,
      bobot: Number.isFinite(Number(item?.bobot)) ? Math.max(0, Number(item.bobot)) : 1,
      snapshot: item?.snapshot && typeof item.snapshot === 'object' ? JSON.parse(JSON.stringify(item.snapshot)) : null
    })) : [],
    paper: normalizePaper(source.paper || source),
    kop: normalizeKop(source.kop, profile),
    createdAt: typeof source.createdAt === 'string' ? source.createdAt : now,
    updatedAt: now
  };
}

function validateExamPackage(input, questionBank = []) {
  const p = normalizeExamPackage(input);
  const errors = [];
  if (!p.nama) errors.push('Nama paket soal wajib diisi.');
  if (p.nama.length > 160) errors.push('Nama paket soal terlalu panjang.');
  if (!p.mapel) errors.push('Mata pelajaran wajib diisi.');
  if (!p.kelas) errors.push('Kelas/rombel wajib diisi.');
  const count = p.items.length || p.questionIds.length;
  if (!count) errors.push('Pilih sedikitnya satu butir soal.');
  if (count > MAX_ITEMS) errors.push(`Paket soal maksimal ${MAX_ITEMS} butir.`);
  const bankIds = new Set((Array.isArray(questionBank) ? questionBank : []).map(q => q && q.id));
  const missing = p.items.length ? p.items.filter(item => !item.snapshot || !JENIS.has(item.snapshot.jenis) || !item.snapshot.teks) : p.questionIds.filter(id => !bankIds.has(id));
  if (missing.length) errors.push(`${missing.length} butir soal tidak ditemukan di bank soal.`);
  return { valid: errors.length === 0, errors, package: p };
}

function resolveQuestions(exam, questionBank) {
  if (Array.isArray(exam.items) && exam.items.length) return exam.items.map(item => item.snapshot).filter(Boolean);
  const byId = new Map((Array.isArray(questionBank) ? questionBank : []).map(q => [q.id, q]));
  return (exam.questionIds || []).map(id => byId.get(id)).filter(Boolean);
}

// Semua keluaran menggunakan urutan dan struktur naskah yang sama.
import * as layout from './examLayout.js';
function buildExamHtml(input, bank, profile = {}, mode = 'paper') {
  const exam = normalizeExamPackage(input, profile);
  return layout.buildHtml(exam, resolveQuestions(exam, bank), profile, mode);
}
async function buildExamDocx(input, bank, profile = {}, mode = 'paper') {
  const exam = normalizeExamPackage(input, profile);
  return layout.buildDocx(exam, resolveQuestions(exam, bank), profile, mode);
}

function buildAllHtml(exam, bank, profile) {
  return { paper: buildExamHtml(exam, bank, profile, 'paper'), key: buildExamHtml(exam, bank, profile, 'key'), blueprint: buildExamHtml(exam, bank, profile, 'blueprint') };
}

export { MAX_ITEMS, normalizePaper, normalizeKop, normalizeExamPackage, validateExamPackage, resolveQuestions, buildExamHtml, buildExamDocx, buildAllHtml, exportFilename };

