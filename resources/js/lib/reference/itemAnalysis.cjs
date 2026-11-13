'use strict';

/* Mesin analisis butir pilihan ganda. Murni: tidak membaca DOM, Electron, atau berkas. */
(function (root, factory) {
  if (typeof module === 'object' && module.exports) module.exports = factory();
  else {
    root.JG = root.JG || {};
    root.JG.itemAnalysis = factory();
  }
})(typeof self !== 'undefined' ? self : this, function () {
  const LABELS = ['A', 'B', 'C', 'D', 'E'];
  const LEVELS = ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'];
  const MAX_ITEMS = 200;
  const MAX_STUDENTS = 500;
  const MAX_TEXT = 10000;
  const MAX_OPTION_TEXT = 2000;

  const AMBANG_BAWAAN = Object.freeze({
    kesukaran: { sukarMaks: 0.30, sedangMaks: 0.70 },
    dayaPembeda: { jelekMaks: 0.20, cukupMaks: 0.40, baikMaks: 0.70 },
    pengecohMatiPersen: 5,
    reliabilitas: { dipertanyakanMaks: 0.50, rendahMaks: 0.60, cukupMaks: 0.70, baikMaks: 0.80, sangatBaikMaks: 0.90 }
  });

  const plain = (v) => v && typeof v === 'object' && !Array.isArray(v);
  const finite = (v) => typeof v === 'number' && Number.isFinite(v);
  const avg = (values) => values.length ? values.reduce((a, b) => a + b, 0) / values.length : null;

  function sampleVariance(values) {
    if (values.length < 2) return null;
    const mean = avg(values);
    return values.reduce((sum, x) => sum + (x - mean) ** 2, 0) / (values.length - 1);
  }

  function validateQuestions(items) {
    const errors = [];
    if (!Array.isArray(items) || items.length < 1 || items.length > MAX_ITEMS) {
      return { valid: false, errors: [`Perangkat harus berisi 1–${MAX_ITEMS} butir.`] };
    }
    const ids = new Set();
    items.forEach((item, index) => {
      const n = index + 1;
      if (!plain(item)) { errors.push(`Butir ${n}: data tidak valid.`); return; }
      if (typeof item.id !== 'string' || !item.id.trim() || item.id.length > 100 || ids.has(item.id)) errors.push(`Butir ${n}: ID wajib unik (maksimal 100 karakter).`);
      else ids.add(item.id);
      if (item.jenis !== 'pg') errors.push(`Butir ${n}: jenis soal harus pilihan ganda.`);
      if (typeof item.teks !== 'string' || !item.teks.trim() || item.teks.length > MAX_TEXT) errors.push(`Butir ${n}: teks wajib diisi (maksimal ${MAX_TEXT} karakter).`);
      if (!Array.isArray(item.opsi) || item.opsi.length < 3 || item.opsi.length > 5) {
        errors.push(`Butir ${n}: isi 3 sampai 5 opsi.`);
      } else {
        item.opsi.forEach((option, optionIndex) => {
          const label = LABELS[optionIndex];
          if (!plain(option) || option.label !== label || typeof option.teks !== 'string' || !option.teks.trim() || option.teks.length > MAX_OPTION_TEXT) {
            errors.push(`Butir ${n}: opsi harus berurutan A–${LABELS[item.opsi.length - 1]} dan setiap teks wajib diisi (maksimal ${MAX_OPTION_TEXT} karakter).`);
            return;
          }
        });
      }
      const validKeys = Array.isArray(item.opsi) ? item.opsi.map(o => o && o.label) : [];
      if (!validKeys.includes(item.kunci)) errors.push(`Butir ${n}: kunci harus cocok dengan salah satu opsi.`);
      if (item.levelKognitif != null && !LEVELS.includes(item.levelKognitif)) errors.push(`Butir ${n}: level kognitif harus C1 sampai C6 atau kosong.`);
      if (item.tujuan != null && !plain(item.tujuan)) errors.push(`Butir ${n}: metadata tujuan tidak valid.`);
      if (plain(item.tujuan)) {
        for (const key of ['rencanaId', 'satuanId', 'label']) {
          if (item.tujuan[key] != null && (typeof item.tujuan[key] !== 'string' || item.tujuan[key].length > 200)) errors.push(`Butir ${n}: ${key} tujuan maksimal 200 karakter.`);
        }
      }
    });
    return { valid: errors.length === 0, errors };
  }

  const VALID_JENIS = ['pg', 'menjodohkan', 'essay'];

  function validateBankItem(item, index = 0) {
    const errors = [];
    const n = index + 1;
    if (!plain(item)) return { valid: false, errors: [`Butir ${n}: data tidak valid.`] };
    if (typeof item.id !== 'string' || !item.id.trim() || item.id.length > 100) {
      errors.push(`Butir ${n}: ID wajib unik (maksimal 100 karakter).`);
    }
    const jenis = item.jenis || 'pg';
    if (!VALID_JENIS.includes(jenis)) {
      errors.push(`Butir ${n}: jenis soal harus pilihan ganda (pg), menjodohkan, atau essay.`);
    }
    if (typeof item.teks !== 'string' || !item.teks.trim() || item.teks.length > MAX_TEXT) {
      errors.push(`Butir ${n}: teks pertanyaan wajib diisi (maksimal ${MAX_TEXT} karakter).`);
    }

    if (jenis === 'pg') {
      if (!Array.isArray(item.opsi) || item.opsi.length < 3 || item.opsi.length > 5) {
        errors.push(`Butir ${n}: isi 3 sampai 5 opsi.`);
      } else {
        item.opsi.forEach((option, optionIndex) => {
          const label = LABELS[optionIndex];
          if (!plain(option) || option.label !== label || typeof option.teks !== 'string' || !option.teks.trim() || option.teks.length > MAX_OPTION_TEXT) {
            errors.push(`Butir ${n}: opsi harus berurutan A–${LABELS[item.opsi.length - 1]} dan setiap teks wajib diisi (maksimal ${MAX_OPTION_TEXT} karakter).`);
          }
        });
      }
      const validKeys = Array.isArray(item.opsi) ? item.opsi.map(o => o && o.label) : [];
      if (!validKeys.includes(item.kunci)) errors.push(`Butir ${n}: kunci harus cocok dengan salah satu opsi.`);
    } else if (jenis === 'menjodohkan') {
      if (typeof item.kunci !== 'string' || !item.kunci.trim() || item.kunci.length > MAX_OPTION_TEXT) {
        errors.push(`Butir ${n}: pasangan jawaban (kunci) wajib diisi (maksimal ${MAX_OPTION_TEXT} karakter).`);
      }
      if (item.opsi != null && !Array.isArray(item.opsi)) {
        errors.push(`Butir ${n}: opsi pengecoh harus berupa daftar.`);
      }
    } else if (jenis === 'essay') {
      if (typeof item.kunci !== 'string' || !item.kunci.trim() || item.kunci.length > 5000) {
        errors.push(`Butir ${n}: pokok jawaban / rubrik esai wajib diisi (maksimal 5000 karakter).`);
      }
      if (item.pedomanSkor != null && typeof item.pedomanSkor !== 'string' && typeof item.pedomanSkor !== 'number') {
        errors.push(`Butir ${n}: pedoman skor harus berupa teks atau angka.`);
      }
    }

    if (item.levelKognitif != null && item.levelKognitif !== '' && !LEVELS.includes(item.levelKognitif)) {
      errors.push(`Butir ${n}: level kognitif harus C1 sampai C6 atau kosong.`);
    }
    if (item.tujuan != null && !plain(item.tujuan)) errors.push(`Butir ${n}: metadata tujuan tidak valid.`);
    if (plain(item.tujuan)) {
      for (const key of ['rencanaId', 'satuanId', 'label']) {
        if (item.tujuan[key] != null && (typeof item.tujuan[key] !== 'string' || item.tujuan[key].length > 200)) {
          errors.push(`Butir ${n}: ${key} tujuan maksimal 200 karakter.`);
        }
      }
    }

    return { valid: errors.length === 0, errors };
  }

  function validateBankItems(items) {
    if (!Array.isArray(items) || items.length < 1 || items.length > MAX_ITEMS) {
      return { valid: false, errors: [`Perangkat harus berisi 1–${MAX_ITEMS} butir.`] };
    }
    const errors = [];
    const ids = new Set();
    items.forEach((item, index) => {
      const res = validateBankItem(item, index);
      if (!res.valid) errors.push(...res.errors);
      if (item && item.id) {
        if (ids.has(item.id)) errors.push(`Butir ${index + 1}: ID wajib unik.`);
        else ids.add(item.id);
      }
    });
    return { valid: errors.length === 0, errors };
  }

  function canonicalAnswer(value, item) {
    if (value === null || value === undefined) return { valid: true, answer: null };
    if (typeof value !== 'string') return { valid: false };
    const token = value.trim().toUpperCase();
    if (!token || token === '.' || token === '-') return { valid: true, answer: null };
    const labels = item.opsi.map(o => o.label);
    return labels.includes(token) ? { valid: true, answer: token } : { valid: false };
  }

  function validateResponses(items, responses) {
    const errors = [];
    if (!Array.isArray(responses) || responses.length < 1 || responses.length > MAX_STUDENTS) {
      return { valid: false, errors: [`Asesmen harus memiliki 1–${MAX_STUDENTS} peserta.`] };
    }
    const ids = new Set();
    const normalized = [];
    responses.forEach((response, rowIndex) => {
      const row = rowIndex + 1;
      if (!plain(response) || typeof response.siswaId !== 'string' || !response.siswaId.trim() || response.siswaId.length > 80) {
        errors.push(`Baris ${row}: nomor absen/inisial wajib diisi (maksimal 80 karakter).`);
        return;
      }
      const siswaId = response.siswaId.trim();
      const key = siswaId.toLocaleLowerCase('id');
      if (ids.has(key)) errors.push(`Baris ${row}: nomor absen/inisial "${siswaId}" sudah digunakan.`);
      ids.add(key);
      if (!Array.isArray(response.jawaban) || response.jawaban.length !== items.length) {
        errors.push(`Baris ${row} (${siswaId}): jumlah jawaban harus tepat ${items.length} butir.`);
        return;
      }
      const answers = response.jawaban.map((value, itemIndex) => {
        const parsed = canonicalAnswer(value, items[itemIndex]);
        if (!parsed.valid) errors.push(`Baris ${row} (${siswaId}), butir ${itemIndex + 1}: jawaban tidak tersedia pada opsi soal.`);
        return parsed.answer;
      });
      normalized.push({ siswaId, jawaban: answers });
    });
    return { valid: errors.length === 0, errors, responses: normalized };
  }

  function parseAnswerLine(text, items) {
    if (typeof text !== 'string') return { valid: false, errors: ['Jawaban harus berupa teks.'] };
    const input = text.trim();
    if (!input) return { valid: false, errors: ['Jawaban belum diisi.'] };
    let tokens;
    if (/[\s,]/.test(input)) tokens = input.split(/[\s,]+/).filter(Boolean);
    else if (/^[A-Ea-e.\-]+$/.test(input) && input.length === items.length) tokens = [...input];
    else tokens = [input];
    if (tokens.length !== items.length) return { valid: false, errors: [`Jumlah jawaban ${tokens.length}; seharusnya ${items.length}.`] };
    const jawaban = tokens.map((token, i) => {
      const answer = canonicalAnswer(token, items[i]);
      return answer.valid ? answer.answer : undefined;
    });
    const invalidIndex = jawaban.findIndex(a => a === undefined);
    if (invalidIndex >= 0) return { valid: false, errors: [`Butir ${invalidIndex + 1}: jawaban tidak tersedia pada opsi soal.`] };
    return { valid: true, jawaban };
  }

  function pearson(x, y) {
    if (x.length < 3 || x.length !== y.length) return null;
    const meanX = avg(x);
    const meanY = avg(y);
    let xx = 0, yy = 0, xy = 0;
    for (let i = 0; i < x.length; i++) {
      const dx = x[i] - meanX;
      const dy = y[i] - meanY;
      xx += dx * dx;
      yy += dy * dy;
      xy += dx * dy;
    }
    if (xx === 0 || yy === 0) return null;
    return xy / Math.sqrt(xx * yy);
  }

  function round(value, digits = 4) {
    if (!finite(value)) return null;
    const factor = 10 ** digits;
    return Math.round((value + Number.EPSILON) * factor) / factor;
  }

  function classifyP(p, ambang) {
    if (p <= ambang.kesukaran.sukarMaks) return 'sukar';
    if (p <= ambang.kesukaran.sedangMaks) return 'sedang';
    return 'mudah';
  }

  function classifyD(d, ambang) {
    if (d === null) return 'tidak-dapat-dihitung';
    if (d < 0) return 'negatif';
    if (d <= ambang.dayaPembeda.jelekMaks) return 'jelek';
    if (d <= ambang.dayaPembeda.cukupMaks) return 'cukup';
    if (d <= ambang.dayaPembeda.baikMaks) return 'baik';
    return 'sangat-baik';
  }

  function analyzeAssessment({ butir, hasil, ambang = AMBANG_BAWAAN, batasTuntasPersen = null } = {}) {
    const qCheck = validateQuestions(butir);
    if (!qCheck.valid) return { success: false, errors: qCheck.errors };
    const rCheck = validateResponses(butir, hasil);
    if (!rCheck.valid) return { success: false, errors: rCheck.errors };
    const effectiveThresholds = { ...AMBANG_BAWAAN, ...ambang,
      kesukaran: { ...AMBANG_BAWAAN.kesukaran, ...(ambang.kesukaran || {}) },
      dayaPembeda: { ...AMBANG_BAWAAN.dayaPembeda, ...(ambang.dayaPembeda || {}) },
      reliabilitas: { ...AMBANG_BAWAAN.reliabilitas, ...(ambang.reliabilitas || {}) }
    };
    if (!finite(effectiveThresholds.pengecohMatiPersen) || effectiveThresholds.pengecohMatiPersen < 0 || effectiveThresholds.pengecohMatiPersen > 100) {
      return { success: false, errors: ['Ambang pengecoh harus berupa persen 0 sampai 100.'] };
    }
    const thresholdGroups = [
      [effectiveThresholds.kesukaran.sukarMaks, effectiveThresholds.kesukaran.sedangMaks],
      [effectiveThresholds.dayaPembeda.jelekMaks, effectiveThresholds.dayaPembeda.cukupMaks, effectiveThresholds.dayaPembeda.baikMaks],
      [effectiveThresholds.reliabilitas.dipertanyakanMaks, effectiveThresholds.reliabilitas.rendahMaks,
        effectiveThresholds.reliabilitas.cukupMaks, effectiveThresholds.reliabilitas.baikMaks,
        effectiveThresholds.reliabilitas.sangatBaikMaks]
    ];
    if (thresholdGroups.some(group => group.some((value, i) => !finite(value) || value < 0 || value > 1
        || (i > 0 && value <= group[i - 1])))) {
      return { success: false, errors: ['Ambang kategori harus meningkat berurutan dalam rentang 0 sampai 1.'] };
    }
    if (batasTuntasPersen !== null && (!finite(batasTuntasPersen) || batasTuntasPersen < 0 || batasTuntasPersen > 100)) {
      return { success: false, errors: ['Batas tuntas harus kosong atau berupa persen 0 sampai 100.'] };
    }

    const students = rCheck.responses.map((response) => {
      const skorButir = butir.map((item, i) => response.jawaban[i] === item.kunci ? 1 : 0);
      const skor = skorButir.reduce((sum, n) => sum + n, 0);
      return { siswaId: response.siswaId, jawaban: response.jawaban, skorButir, skor, persen: skor / butir.length * 100 };
    });
    const totals = students.map(s => s.skor);
    const n = students.length;
    const k = butir.length;
    const variance = sampleVariance(totals);
    const sd = variance === null ? null : Math.sqrt(variance);
    const itemVarianceTerms = butir.map((_, j) => {
      const p = students.reduce((sum, s) => sum + s.skorButir[j], 0) / n;
      return p * (1 - p);
    });
    // Sum(p*q) adalah bentuk populasi, jadi pembaginya harus varians populasi skor total (n, bukan n-1);
    // hasilnya sama dengan Alpha Cronbach pada skor 0/1. Simpangan baku yang ditampilkan tetap sampel (n-1).
    const populationVariance = variance === null ? null : variance * (n - 1) / n;
    const kr20 = k < 2 || variance === null || variance === 0 ? null
      : k / (k - 1) * (1 - itemVarianceTerms.reduce((a, b) => a + b, 0) / populationVariance);
    const sem = kr20 !== null && kr20 >= 0 && kr20 <= 1 && sd !== null ? sd * Math.sqrt(1 - kr20) : null;

    const sorted = [...students].sort((a, b) => b.skor - a.skor);
    const groupSize = Math.max(1, Math.floor(0.27 * n));
    const upperCut = sorted[Math.min(groupSize - 1, n - 1)].skor;
    const lowerCut = sorted[Math.max(0, n - groupSize)].skor;
    const upper = students.filter(s => s.skor >= upperCut);
    const lower = students.filter(s => s.skor <= lowerCut);
    const groupOverlap = upper.some(s => lower.includes(s));
    const itemResults = butir.map((item, j) => {
      const correct = students.reduce((sum, s) => sum + s.skorButir[j], 0);
      const p = correct / n;
      const upperCorrect = upper.reduce((sum, s) => sum + s.skorButir[j], 0);
      const lowerCorrect = lower.reduce((sum, s) => sum + s.skorButir[j], 0);
      const d = groupOverlap || !upper.length || !lower.length ? null : upperCorrect / upper.length - lowerCorrect / lower.length;
      const correctedTotals = students.map(s => s.skor - s.skorButir[j]);
      const pointBiserial = pearson(students.map(s => s.skorButir[j]), correctedTotals);
      const options = item.opsi.map(option => {
        const selected = students.filter(s => s.jawaban[j] === option.label);
        const means = selected.map(s => s.skor - s.skorButir[j]);
        const pct = selected.length / n * 100;
        return {
          label: option.label,
          teks: option.teks,
          jumlah: selected.length,
          persen: round(pct, 2),
          rerataSkorTerkoreksi: round(avg(means), 2),
          kunci: option.label === item.kunci,
          pengecohMati: option.label !== item.kunci && pct < effectiveThresholds.pengecohMatiPersen
        };
      });
      const suspectDistractor = options.find(o => !o.kunci && o.jumlah > 0 && o.rerataSkorTerkoreksi !== null
        && options.find(key => key.kunci)?.rerataSkorTerkoreksi !== null
        && o.rerataSkorTerkoreksi > options.find(key => key.kunci).rerataSkorTerkoreksi);
      const keySuspicious = (d !== null && d < 0) || (pointBiserial !== null && pointBiserial < 0) || Boolean(suspectDistractor);
      const classificationD = classifyD(d, effectiveThresholds);
      let recommendation = 'pertahankan';
      const reasons = [];
      if (classificationD === 'negatif' && pointBiserial !== null && pointBiserial < 0) {
        recommendation = 'buang';
        reasons.push('D dan korelasi terkoreksi negatif; periksa kunci dan redaksi sebelum memutuskan butir tidak digunakan.');
      } else {
        if (keySuspicious) reasons.push('Ada sinyal kunci atau pengecoh yang perlu diperiksa.');
        if (classificationD === 'jelek') reasons.push('Daya pembeda rendah.');
        if (options.some(o => o.pengecohMati)) reasons.push('Ada pengecoh yang dipilih kurang dari ambang.');
        if (reasons.length) recommendation = 'revisi';
      }
      return {
        id: item.id,
        nomor: j + 1,
        teks: item.teks,
        kunci: item.kunci,
        tujuan: item.tujuan || null,
        levelKognitif: item.levelKognitif || null,
        benar: correct,
        peserta: n,
        p: round(p),
        kategoriP: classifyP(p, effectiveThresholds),
        dayaPembeda: round(d),
        kategoriD: classificationD,
        nKelompokAtas: upper.length,
        nKelompokBawah: lower.length,
        korelasiPointBiserial: round(pointBiserial),
        opsi: options,
        kunciMencurigakan: keySuspicious,
        rekomendasi: recommendation,
        alasan: reasons
      };
    });

    const histogram = Array.from({ length: 10 }, (_, i) => ({ dari: i * 10, sampai: i === 9 ? 100 : (i + 1) * 10 - 1, jumlah: 0 }));
    students.forEach(s => { histogram[Math.min(9, Math.floor(s.persen / 10))].jumlah++; });
    const tujuanMap = new Map();
    const levelMap = new Map();
    function addGroup(map, key, label, itemResult) {
      if (!key) return;
      if (!map.has(key)) map.set(key, { key, label, jumlahButir: 0, benar: 0, totalRespons: 0 });
      const group = map.get(key);
      group.jumlahButir++;
      group.benar += itemResult.benar;
      group.totalRespons += itemResult.peserta;
    }
    itemResults.forEach(item => {
      if (item.tujuan && (item.tujuan.satuanId || item.tujuan.label)) {
        const key = item.tujuan.satuanId || item.tujuan.label;
        addGroup(tujuanMap, key, item.tujuan.label || key, item);
      }
      if (item.levelKognitif) addGroup(levelMap, item.levelKognitif, item.levelKognitif, item);
    });
    const summarizeGroups = (map) => [...map.values()].map(group => ({
      ...group,
      persenBenar: round(group.totalRespons ? group.benar / group.totalRespons * 100 : null, 2)
    }));
    const remedial = batasTuntasPersen === null ? null : students.filter(s => s.persen < batasTuntasPersen).map(s => ({ siswaId: s.siswaId, skor: s.skor, persen: round(s.persen, 2) }));
    const pengayaan = batasTuntasPersen === null ? null : students.filter(s => s.persen >= batasTuntasPersen).map(s => ({ siswaId: s.siswaId, skor: s.skor, persen: round(s.persen, 2) }));
    const rel = (value) => value === null ? 'tidak-dapat-dihitung'
      : value < effectiveThresholds.reliabilitas.dipertanyakanMaks ? 'dipertanyakan'
        : value < effectiveThresholds.reliabilitas.rendahMaks ? 'rendah'
          : value < effectiveThresholds.reliabilitas.cukupMaks ? 'cukup-rendah'
            : value < effectiveThresholds.reliabilitas.baikMaks ? 'cukup'
              : value < effectiveThresholds.reliabilitas.sangatBaikMaks ? 'baik' : 'sangat-baik';

    return {
      success: true,
      ringkasan: {
        peserta: n,
        jumlahButir: k,
        rerataSkor: round(avg(totals), 2),
        rerataPersen: round(avg(students.map(s => s.persen)), 2),
        simpanganBaku: round(sd, 3),
        tertinggi: Math.max(...totals),
        terendah: Math.min(...totals),
        kr20: round(kr20),
        kategoriKr20: rel(kr20),
        sem: round(sem, 3),
        histogram,
        peringatanSampelKecil: n < 30,
        peringatanVariansNol: variance === 0,
        catatanSem: kr20 !== null && sem === null ? 'SEM tidak dihitung karena KR-20 di luar rentang 0–1.' : null
      },
      butir: itemResults,
      perTujuan: summarizeGroups(tujuanMap),
      perLevelKognitif: summarizeGroups(levelMap),
      tindakLanjut: batasTuntasPersen === null ? null : { batasTuntasPersen, remedial, pengayaan },
      siswa: students.map(({ siswaId, skor, persen }) => ({ siswaId, skor, persen: round(persen, 2) }))
    };
  }

  function canCombineAssessments(assessments) {
    if (!Array.isArray(assessments) || assessments.length < 2) return { valid: false, error: 'Pilih sedikitnya dua asesmen.' };
    const first = assessments[0];
    const same = (a, b) => a.perangkatId === b.perangkatId && a.versiPerangkat === b.versiPerangkat
      && JSON.stringify(a.butir) === JSON.stringify(b.butir);
    if (!assessments.every(a => same(first, a))) return { valid: false, error: 'Asesmen hanya dapat digabung jika perangkat, versi, dan snapshot butir identik.' };
    return {
      valid: true,
      butir: first.butir,
      hasil: assessments.flatMap(a => (a.hasil || []).map(row => ({
        ...row,
        siswaId: `${a.kelas || 'Rombel'}:${row.siswaId}`
      })))
    };
  }

  return {
    LABELS, LEVELS, VALID_JENIS, MAX_ITEMS, MAX_STUDENTS, AMBANG_BAWAAN,
    validateQuestions, validateBankItem, validateBankItems, validateResponses, parseAnswerLine, analyzeAssessment, canCombineAssessments
  };
});

