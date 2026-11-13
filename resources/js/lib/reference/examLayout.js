// Reference: jurnal-harian-guru @ 27b2eaaf69c1f8e555fb3198c4150a837500322e
// Browser adaptation: ES modules, Uint8Array images, Blob output.
const imageBytes = value => Uint8Array.from(atob(value), c => c.charCodeAt(0));

import { Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, WidthType,
  AlignmentType, BorderStyle, PageOrientation, SectionType, Footer, PageNumber, ImageRun, TabStopType } from 'docx';

const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const TYPES = [
  { jenis: 'pg', title: 'PILIHAN GANDA', instruction: 'Pilihlah satu jawaban yang paling tepat.' },
  { jenis: 'menjodohkan', title: 'MENJODOHKAN', instruction: 'Pasangkan setiap pernyataan dengan huruf jawaban yang tepat pada daftar berikut.' },
  { jenis: 'essay', title: 'URAIAN', instruction: 'Jawablah pertanyaan berikut dengan jelas dan lengkap.' }
];
function groups(questions) {
  let nomor = 0;
  return TYPES.map(type => ({ ...type, questions: questions.filter(q => q.jenis === type.jenis).map(q => ({ ...q, nomor: ++nomor })) })).filter(g => g.questions.length);
}
function logoData(profile) {
  const logo = profile.logo;
  return typeof logo === 'string' && logo.length <= 3 * 1024 * 1024 && /^data:image\/(png|jpe?g);base64,[A-Za-z0-9+/=]+$/.test(logo) ? logo : '';
}
function dateText(value) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return value || '................................';
  const d = new Date(`${value}T00:00:00Z`);
  return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });
}
function metadata(exam) {
  return [
    ['Mata Pelajaran', exam.mapel, 'Hari / Tanggal', dateText(exam.tanggal)],
    ['Kelas / Semester', `${exam.kelas} / ${exam.semester}`, 'Waktu', exam.waktu || '................................'],
    ['Nama Peserta', '........................................', 'No. Peserta', '................................']
  ];
}
function answerLines(q) {
  const match = String(q.pedomanSkor || '').match(/(?:maksimal|maks)\s*(?:skor)?\s*([1-9]\d?)/i);
  return Math.max(3, Math.min(8, Number(match?.[1] || 4)));
}
function compactOptions(q, limit = 42) { return q.opsi?.length === 4 && q.opsi.every(o => String(o.teks).length <= limit && !String(o.teks).includes('\n')); }
function optionLimit(columnMm) { return Math.max(10, Math.min(42, Math.floor((columnMm - 6) / 3.4))); }
function matchingChoices(group) { return [...new Set(group.questions.map(q => q.kunci).filter(Boolean))]; }
function pageSize(paper) {
  const portrait = [paper.ukuran === 'F4' ? 215 : 210, paper.ukuran === 'F4' ? 330 : 297];
  return paper.orientasi === 'landscape' ? portrait.reverse() : portrait;
}
function kopHtml(exam, profile, mode) {
  const k = exam.kop, logo = logoData(profile);
  if (exam.paper.orientasi === 'landscape' && mode === 'paper') {
    const institution = k.gambar
      ? `<img class="exam-landscape-image" src="${k.gambar}" alt="Kop ${esc(k.namaLembaga)}">`
      : `${logo ? `<img class="exam-landscape-logo" src="${logo}" alt="Logo lembaga">` : ''}<div class="exam-landscape-institution">${k.yayasan ? `<div>${esc(k.yayasan)}</div>` : ''}<div>${esc(k.namaLembaga || 'NAMA SEKOLAH / MADRASAH')}</div>${k.keterangan ? `<small>${esc(k.keterangan)}</small>` : ''}${k.alamat ? `<small>${esc(k.alamat)}</small>` : ''}</div>`;
    return `<div class="exam-column-kop"><header class="exam-landscape-kop">${institution}<div>${esc(k.judul)}</div><div>${esc(k.subjudul)}</div><div>TAHUN PELAJARAN ${esc(exam.tahunPelajaran)}</div></header><table class="exam-meta"><tbody>${metadata(exam).map(row => `<tr>${[0, 2].map(i => `<th>${esc(row[i])}</th><td>: ${esc(row[i + 1])}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
  }
  const letterhead = k.gambar ? `<header class="exam-image-kop"><img src="${k.gambar}" alt="Kop ${esc(k.namaLembaga)}" style="width:100%;height:auto;display:block"></header>` : `<header class="exam-kop ${logo ? 'with-logo' : ''}">${logo ? `<img class="exam-logo" src="${logo}" alt="Logo lembaga">` : ''}<div class="exam-letterhead">${k.yayasan ? `<div class="exam-foundation">${esc(k.yayasan)}</div>` : ''}<div class="exam-school">${esc(k.namaLembaga || 'NAMA SEKOLAH / MADRASAH')}</div>${k.keterangan ? `<div class="exam-accreditation">${esc(k.keterangan)}</div>` : ''}${k.alamat ? `<div class="exam-address">${esc(k.alamat)}</div>` : ''}</div></header>`;
  return `<div class="exam-column-kop">${letterhead}<div class="exam-heading"><h1>${esc(k.judul)}</h1><div>${esc(k.subjudul)}</div><div>TAHUN PELAJARAN ${esc(exam.tahunPelajaran)}</div></div><table class="exam-meta"><tbody>${metadata(exam).map(row => `<tr>${[0, 2].map(i => `<th>${esc(row[i])}</th><td>: ${esc(row[i + 1])}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
}
function questionHtml(q, limit) {
  let content = `<div class="exam-question-text"><span class="exam-number">${q.nomor}.</span><span>${esc(q.teks)}</span></div>`;
  if (q.jenis === 'pg') {
    content += `<div class="exam-options ${compactOptions(q, limit) ? 'options-compact' : ''}">${(q.opsi || []).map(o => `<div class="exam-option"><span>${esc(o.label)}.</span><span>${esc(o.teks)}</span></div>`).join('')}</div>`;
  } else if (q.jenis === 'essay') {
    content += `<div class="exam-answer-lines">${'<div></div>'.repeat(answerLines(q))}</div>`;
  } else content += '<div class="exam-match-answer">Jawaban: ( ........ )</div>';
  return `<article class="exam-question exam-kind-${q.jenis}">${content}</article>`;
}
function buildHtml(exam, questions, profile, mode) {
  const sectionGroups = groups(questions), ordered = sectionGroups.flatMap(g => g.questions);
  const [w, h] = pageSize(exam.paper);
  const limit = optionLimit((w - 28 - 8 * (exam.paper.columns - 1)) / exam.paper.columns);
  const kop = kopHtml(exam, profile, mode);
  const kopInsideColumns = mode === 'paper' && exam.paper.orientasi === 'landscape' && exam.paper.columns > 1;
  let body = kopInsideColumns ? '' : kop;
  if (mode === 'paper') {
    const instructions = `<div class="exam-instructions"><strong>Petunjuk umum:</strong> ${esc(exam.kop.petunjuk)}</div>`;
    body += kopInsideColumns ? `<main class="exam-columns">${kop}${instructions}` : `${instructions}<main class="exam-columns">`;
    sectionGroups.forEach((group, index) => {
      const heading = `<h2>${String.fromCharCode(65 + index)}. ${group.title}</h2><p class="exam-section-instruction">${group.instruction}</p>`;
      const choices = group.jenis === 'menjodohkan' ? `<div class="exam-matching-bank"><strong>Daftar jawaban</strong>${matchingChoices(group).map((v, i) => `<div>${String.fromCharCode(65 + i)}. ${esc(v)}</div>`).join('')}</div>` : '';
      // Judul, petunjuk, dan butir pertama tetap berada pada kolom/halaman yang sama.
      body += `<div class="exam-section-start">${heading}${choices}${questionHtml(group.questions[0], limit)}</div>` + group.questions.slice(1).map(q => questionHtml(q, limit)).join('');
    });
    body += '</main>';
  } else {
    const key = mode === 'key';
    body += `<h2 class="exam-document-title">${key ? 'KUNCI JAWABAN DAN PEDOMAN PENSKORAN' : 'KISI-KISI SOAL'}</h2>`;
    const headings = key ? ['No.', 'Bentuk', 'Jawaban', 'Pedoman / Catatan'] : ['No.', 'Bentuk', 'Indikator / Teks Soal', 'Tujuan Pembelajaran', 'Level'];
    const rows = ordered.map(q => key ? [q.nomor, TYPES.find(t => t.jenis === q.jenis).title, q.kunci || '', q.pedomanSkor || ''] : [q.nomor, TYPES.find(t => t.jenis === q.jenis).title, q.teks, q.tujuan?.label || '', q.levelKognitif || '']);
    body += `<table class="exam-table"><thead><tr>${headings.map(v => `<th>${esc(v)}</th>`).join('')}</tr></thead><tbody>${rows.map(row => `<tr>${row.map(v => `<td>${esc(v)}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
  }
  const footer = `${exam.mapel} | ${exam.kelas} | ${exam.tahunPelajaran}`;
  // JSON string is a quoted CSS string; escape angle brackets to prevent closing the style tag.
  const footerCss = JSON.stringify(footer).replace(/</g, '\\3c ').replace(/>/g, '\\3e ');
  return `<!doctype html><html lang="id"><head><meta charset="utf-8"><title>${esc(exam.nama)}</title><style>
@page{size:${w}mm ${h}mm;margin:12mm 14mm 16mm;@bottom-left{content:${footerCss};font:8pt Arial;color:#555}@bottom-right{content:"Hal. " counter(page);font:8pt Arial;color:#555}}
*{box-sizing:border-box}html{background:#e8ebf0}body{margin:0;color:#111;font-family:Arial,sans-serif;font-size:10pt;line-height:1.28}.exam-sheet{width:${w}mm;min-height:${h}mm;margin:12px auto;background:white;padding:12mm 14mm 16mm;box-shadow:0 2px 12px #0002}
.exam-column-kop{break-inside:avoid;page-break-inside:avoid}.exam-kop{position:relative;display:flex;align-items:center;justify-content:center;min-height:24mm;padding:0 0 3mm;border-bottom:3px double #111;text-align:center;gap:4mm}.exam-logo{width:22mm;height:22mm;object-fit:contain;flex:none}.exam-letterhead{flex:1}.with-logo .exam-letterhead{padding-right:26mm}.exam-columns .exam-logo{width:15mm;height:15mm}.exam-columns .with-logo .exam-letterhead{padding-right:17mm}.exam-columns .exam-school{font-size:12pt}.exam-columns .exam-foundation{font-size:9.5pt}.exam-foundation{font-size:11pt;font-weight:bold;text-transform:uppercase}.exam-school{font-size:15pt;font-weight:bold;text-transform:uppercase;line-height:1.15}.exam-accreditation{font-size:8.5pt;font-weight:bold;margin-top:1mm;white-space:pre-line}.exam-address{font-size:8.5pt;margin-top:1mm;white-space:pre-line}.exam-heading{text-align:center;font-weight:bold;margin:3mm 0;font-size:10pt}.exam-heading h1{font-size:12pt;margin:0 0 1mm}.exam-meta{width:100%;border-collapse:collapse;font-size:9pt;margin:3mm 0;table-layout:fixed}.exam-meta th,.exam-meta td{padding:1.2mm 1.6mm;border:1px solid #555;text-align:left;vertical-align:top;overflow-wrap:anywhere}.exam-meta th{width:20%;font-weight:normal}.exam-meta td{width:30%}.exam-instructions{font-size:9pt;margin:3mm 0 4mm;white-space:pre-line;break-inside:avoid;page-break-inside:avoid}.exam-columns{column-count:${mode === 'paper' ? exam.paper.columns : 1};column-gap:8mm;column-fill:balance}h2{font-size:10pt;margin:3mm 0 1mm;break-after:avoid}.exam-section-instruction{font-weight:bold;margin:0 0 2mm}.exam-section-start,.exam-question{break-inside:avoid;page-break-inside:avoid}.exam-question{margin:0 0 3mm}.exam-question-text{display:flex;gap:1.4mm;align-items:baseline}.exam-question-text>span:last-child{flex:1;min-width:0;white-space:pre-line;overflow-wrap:anywhere}.exam-number{flex:none;min-width:5mm;text-align:right}.exam-options{margin:1mm 0 0 6.4mm}.options-compact{display:grid;grid-template-columns:1fr 1fr;grid-auto-flow:column;grid-template-rows:repeat(2,auto);gap:.3mm 3mm}.exam-option{display:flex;gap:1.5mm;align-items:baseline;margin:.3mm 0}.exam-option>span:first-child{flex:none;min-width:3mm}.exam-option>span:last-child{white-space:pre-line;overflow-wrap:anywhere}.exam-answer-lines{margin:1mm 0 0 6.4mm}.exam-answer-lines div{height:6mm;border-bottom:1px dotted #777}.exam-match-answer{margin:1mm 0 0 6.4mm}.exam-matching-bank{border:1px solid #555;padding:2mm;margin:2mm 0 3mm;break-inside:avoid}.exam-matching-bank strong{display:block;margin-bottom:1mm}.exam-table{width:100%;border-collapse:collapse;font-size:9pt;table-layout:fixed}.exam-table th,.exam-table td{border:1px solid #444;padding:2mm;vertical-align:top;overflow-wrap:anywhere;white-space:pre-line}.exam-table th{background:#f0f0f0}.exam-table th:first-child{width:9mm}.exam-table tr{break-inside:avoid}.exam-document-title{text-align:center;margin:5mm 0 3mm}.exam-screen-note{font-size:8pt;color:#666;border-top:1px solid #ccc;margin-top:5mm;padding-top:2mm}
.exam-landscape-kop{position:relative;width:100%;min-height:18mm;text-align:center;font-weight:bold;font-size:11pt;border-bottom:3px double #111;padding:0 0 2mm;margin-bottom:3mm}.exam-landscape-logo{position:absolute;left:0;top:0;width:15mm;height:15mm;object-fit:contain}.exam-landscape-institution{padding:0 16mm;white-space:pre-line}.exam-landscape-institution small{display:block;font-size:8pt}.exam-landscape-image{display:block;width:100%;height:auto}.exam-identity td{width:auto;font-weight:bold}.exam-identity td:nth-child(1){width:36%}.exam-identity td:nth-child(2){width:20%}.exam-identity td:nth-child(3){width:29%}.exam-identity td:nth-child(4){width:15%;text-align:center}.exam-identity tr:nth-child(2) td:nth-child(3){font-size:8pt}
@media print{html{background:white}.exam-sheet{width:auto;min-height:0;margin:0;padding:0;box-shadow:none}.exam-screen-note{display:none}}
</style></head><body><div class="exam-sheet">${body}<div class="exam-screen-note">${esc(footer)} · Pratinjau ukuran kertas. Batas halaman dan nomor halaman mengikuti hasil PDF/cetak.</div></div></body></html>`;
}

const twip = mm => Math.round(mm * 1440 / 25.4);
function para(text, opts = {}) {
  const { bold = false, size = 20, align, after = 50, keepNext = false, indent, border, tabStops } = opts;
  return new Paragraph({ children: String(text ?? '').split('\n').map((line, i) => new TextRun({ text: line, break: i ? 1 : 0, bold, size })), alignment: align, spacing: { after, line: 250 }, keepNext, keepLines: true, indent, border, tabStops: tabStops || (indent?.hanging ? [{ type: TabStopType.LEFT, position: indent.left }] : undefined) });
}
const noBorders = Object.fromEntries(['top', 'bottom', 'left', 'right', 'insideHorizontal', 'insideVertical'].map(k => [k, { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }]));
function table(rows, widths, borders, indent = 0) {
  return new Table({ width: { size: widths.reduce((a, b) => a + b, 0), type: WidthType.DXA }, columnWidths: widths, borders, indent: { size: indent, type: WidthType.DXA },
    rows: rows.map((row, n) => new TableRow({ cantSplit: true, tableHeader: n === 0 && !borders, children: row.map((content, i) => new TableCell({ width: { size: widths[i], type: WidthType.DXA }, margins: { top: 40, bottom: 40, left: 75, right: 75 }, children: Array.isArray(content) ? content : [para(content, { size: 18, after: 0 })] })) })) });
}
function docxQuestion(q, width) {
  const result = [];
  result.push(para(`${q.nomor}.\t${q.teks}`, { keepNext: q.jenis === 'pg' || q.jenis === 'essay' || q.jenis === 'menjodohkan', indent: { left: twip(6), hanging: twip(6) } }));
  if (q.jenis === 'pg') {
    const opts = q.opsi || [];
    if (compactOptions(q, optionLimit(width * 25.4 / 1440))) {
      const usable = width - twip(6);
      const tabStops = [{ type: TabStopType.LEFT, position: twip(6) + Math.floor(usable / 2) }];
      for (let i = 0; i < 2; i++) result.push(para(`${opts[i].label}. ${opts[i].teks}\t${opts[i + 2].label}. ${opts[i + 2].teks}`, { size: 20, indent: { left: twip(6) }, tabStops, keepNext: i === 0, after: i === 0 ? 20 : 130 }));
    } else opts.forEach((o, i) => result.push(para(`${o.label}. ${o.teks}`, { indent: { left: twip(6) }, keepNext: i < opts.length - 1, after: i === opts.length - 1 ? 130 : 20 })));
  } else if (q.jenis === 'essay') {
    const line = { style: BorderStyle.DOTTED, size: 2, color: '888888', space: 3 };
    for (let i = 0; i < answerLines(q); i++) result.push(para('', { after: 70, keepNext: i < answerLines(q) - 1, border: { bottom: line, between: line } }));
  } else result.push(para('Jawaban: ( ........ )', { indent: { left: twip(6) }, after: 130 }));
  return result;
}
function docxHeader(exam, profile, mode, headerWidth) {
  const k = exam.kop, logo = logoData(profile), head = [], center = AlignmentType.CENTER;
  if (exam.paper.orientasi === 'landscape' && mode === 'paper') {
    if (k.gambar) {
      const type = k.gambar.startsWith('data:image/png') ? 'png' : 'jpg';
      head.push(new Paragraph({ children: [new ImageRun({ type, data: Buffer.from(k.gambar.split(',')[1], 'base64'), transformation: { width: Math.round(headerWidth / 15), height: Math.round(headerWidth / 15 / k.rasioGambar) } })], keepNext: true }));
    } else {
      const inst = [
        ...(k.yayasan ? [para(k.yayasan.toUpperCase(), { size: 20, bold: true, align: center, keepNext: true })] : []),
        para(k.namaLembaga.toUpperCase(), { size: 22, bold: true, align: center, keepNext: true }),
        ...(k.keterangan ? [para(k.keterangan, { size: 16, bold: true, align: center, keepNext: true })] : []),
        ...(k.alamat ? [para(k.alamat, { size: 16, align: center, keepNext: true })] : [])
      ];
      if (logo) {
        const type = logo.startsWith('data:image/png') ? 'png' : 'jpg';
        const image = new Paragraph({ children: [new ImageRun({ type, data: Buffer.from(logo.split(',')[1], 'base64'), transformation: { width: 55, height: 55 } })] });
        head.push(table([[[image], inst, [para('')]]], [twip(17), headerWidth - twip(34), twip(17)], noBorders));
      } else head.push(...inst);
    }
    head.push(para(k.judul, { size: 22, bold: true, align: center, keepNext: true }));
    head.push(para(k.subjudul, { size: 20, bold: true, align: center, keepNext: true }));
    head.push(para(`TAHUN PELAJARAN ${exam.tahunPelajaran}`, { size: 20, bold: true, align: center, keepNext: true, border: { bottom: { style: BorderStyle.DOUBLE, size: 6, color: '111111', space: 3 } }, after: 100 }));
  } else {
    const multiCol = mode === 'paper' && exam.paper.orientasi === 'landscape' && exam.paper.columns > 1;
    const text = [
      ...(k.yayasan ? [para(k.yayasan.toUpperCase(), { size: multiCol ? 19 : 22, bold: true, align: center, keepNext: true })] : []),
      para(k.namaLembaga.toUpperCase(), { size: multiCol ? 24 : 30, bold: true, align: center, keepNext: true }),
      ...(k.keterangan ? [para(k.keterangan, { size: multiCol ? 15 : 17, bold: true, align: center, keepNext: true })] : []),
      ...(k.alamat ? [para(k.alamat, { size: multiCol ? 15 : 17, align: center, keepNext: true })] : [])
    ];
    if (k.gambar) {
      const type = k.gambar.startsWith('data:image/png') ? 'png' : 'jpg';
      head.push(new Paragraph({ children: [new ImageRun({ type, data: Buffer.from(k.gambar.split(',')[1], 'base64'), transformation: { width: Math.round(headerWidth / 15), height: Math.round(headerWidth / 15 / k.rasioGambar) } })], keepNext: true, spacing: { after: 100 } }));
    } else if (logo) {
      const type = logo.startsWith('data:image/png') ? 'png' : 'jpg', logoSize = multiCol ? 55 : 80, sideWidth = multiCol ? twip(17) : twip(24);
      const image = new Paragraph({ children: [new ImageRun({ type, data: Buffer.from(logo.split(',')[1], 'base64'), transformation: { width: logoSize, height: logoSize } })], keepNext: true });
      head.push(table([[[image], text, [para('')]]], [sideWidth, headerWidth - sideWidth * 2, sideWidth], noBorders));
    } else head.push(...text);
    if (!k.gambar) head.push(para('', { size: 2, after: 100, keepNext: true, border: { bottom: { style: BorderStyle.DOUBLE, size: 6, color: '111111', space: 3 } } }));
    head.push(para(k.judul, { size: multiCol ? 22 : 24, bold: true, align: center, keepNext: true }), para(k.subjudul, { size: 20, bold: true, align: center, keepNext: true }), para(`TAHUN PELAJARAN ${exam.tahunPelajaran}`, { size: 20, bold: true, align: center, keepNext: true, after: 120 }));
  }
  const labels = metadata(exam).map(row => [row[0], `: ${row[1]}`, row[2], `: ${row[3]}`]);
  const widths = [.20, .30, .20].map(r => Math.floor(headerWidth * r)); widths.push(headerWidth - widths.reduce((a, b) => a + b, 0));
  head.push(table(labels, widths), para('', { size: 2, after: 70 }));
  return head;
}
async function buildDocx(exam, questions, profile, mode) {
  const sectionGroups = groups(questions), ordered = sectionGroups.flatMap(g => g.questions);
  const [w] = pageSize(exam.paper), width = twip(w - 28);
  const colWidth = Math.floor((width - twip(8) * (exam.paper.columns - 1)) / exam.paper.columns);
  const kopInsideColumns = mode === 'paper' && exam.paper.orientasi === 'landscape' && exam.paper.columns > 1;
  const headerWidth = kopInsideColumns ? colWidth : width;
  const footer = new Footer({ children: [new Paragraph({ border: { top: { style: BorderStyle.SINGLE, size: 2, color: 'BBBBBB' } }, children: [new TextRun({ text: `${exam.mapel} | ${exam.kelas} | ${exam.tahunPelajaran}    -    Hal. `, size: 16 }), new TextRun({ children: [PageNumber.CURRENT], size: 16 })] })] });
  const head = docxHeader(exam, profile, mode, headerWidth);
  const content = [];
  if (mode === 'paper') {
    head.push(para(`Petunjuk umum: ${exam.kop.petunjuk}`, { size: 18, after: 160 }));
    sectionGroups.forEach((g, i) => {
      content.push(para(`${String.fromCharCode(65 + i)}. ${g.title}`, { bold: true, keepNext: true, after: 50 }), para(g.instruction, { bold: true, keepNext: true, after: 80 }));
      if (g.jenis === 'menjodohkan') {
        content.push(para('Daftar jawaban:', { bold: true, keepNext: true }));
        matchingChoices(g).forEach((v, n) => content.push(para(`${String.fromCharCode(65 + n)}. ${v}`, { size: 18, keepNext: true })));
      }
      g.questions.forEach(q => content.push(...docxQuestion(q, colWidth)));
    });
  } else {
    const key = mode === 'key';
    content.push(para(key ? 'KUNCI JAWABAN DAN PEDOMAN PENSKORAN' : 'KISI-KISI SOAL', { bold: true, align: AlignmentType.CENTER, keepNext: true, after: 150 }));
    const headings = key ? ['No.', 'Bentuk', 'Jawaban', 'Pedoman / Catatan'] : ['No.', 'Bentuk', 'Indikator / Teks Soal', 'Tujuan Pembelajaran', 'Level'];
    const rows = [headings, ...ordered.map(q => key ? [q.nomor, TYPES.find(t => t.jenis === q.jenis).title, q.kunci || '', q.pedomanSkor || ''] : [q.nomor, TYPES.find(t => t.jenis === q.jenis).title, q.teks, q.tujuan?.label || '', q.levelKognitif || ''])];
    const ratios = key ? [.06, .18, .4, .36] : [.06, .16, .38, .32, .08];
    const cols = ratios.slice(0, -1).map(r => Math.floor(width * r)); cols.push(width - cols.reduce((a, b) => a + b, 0));
    content.push(table(rows, cols));
  }
  const page = { size: { width: twip(exam.paper.ukuran === 'F4' ? 215 : 210), height: twip(exam.paper.ukuran === 'F4' ? 330 : 297), orientation: exam.paper.orientasi === 'landscape' ? PageOrientation.LANDSCAPE : PageOrientation.PORTRAIT }, margin: { top: twip(12), right: twip(14), bottom: twip(16), left: twip(14), footer: twip(7) } };
  const column = { count: exam.paper.columns, space: twip(8), equalWidth: true };
  const sections = kopInsideColumns ? [{ properties: { page, column }, footers: { default: footer }, children: [...head, ...content] }] : mode === 'paper' && exam.paper.columns > 1 ? [
    { properties: { page }, footers: { default: footer }, children: head },
    { properties: { page, type: SectionType.CONTINUOUS, column }, footers: { default: footer }, children: content }
  ] : [{ properties: { page }, footers: { default: footer }, children: [...head, ...content] }];
  return Packer.toBlob(new Document({ styles: { default: { document: { run: { font: 'Arial', size: 20 }, paragraph: { spacing: { after: 50, line: 250 } } } } }, sections }));
}

export { buildHtml, buildDocx, groups, pageSize };

