import engine from './itemAnalysis.js';
const reference = (() => {
  const MIN_SOAL = 20;
  const MAX_SOAL = 200;
  const MIN_SISWA = 5;
  const MAX_SISWA = 500;
  const MAX_ABSEN = 20;
  const MAX_NAMA = 100;
  const MAX_KELAS = 120;
  const LETTERS = ['A', 'B', 'C', 'D', 'E'];

  const isInt = (v) => Number.isInteger(v);
  const lettersFor = (pilihan) => LETTERS.slice(0, pilihan);

  /** Butir sintetis untuk mesin: teks soal dan teks opsi hanya penampung. */
  function makeItems(kunci, pilihan) {
    return kunci.map((answer, index) => ({
      id: `s${index + 1}`,
      jenis: 'pg',
      teks: `Soal ${index + 1}`,
      opsi: lettersFor(pilihan).map((label) => ({ label, teks: label })),
      kunci: answer
    }));
  }

  /** @returns {{valid:boolean, errors:string[]}} */
  function validateSetup(setup) {
    const errors = [];
    const s = setup && typeof setup === 'object' ? setup : {};
    const nama = typeof s.nama === 'string' ? s.nama.trim() : '';
    if (!nama) errors.push('Nama tes wajib diisi.');
    else if (nama.length > MAX_NAMA) errors.push(`Nama tes maksimal ${MAX_NAMA} karakter.`);
    if (s.kelas != null && (typeof s.kelas !== 'string' || s.kelas.length > MAX_KELAS)) errors.push(`Kelas maksimal ${MAX_KELAS} karakter.`);
    if (!isInt(s.jumlahSoal) || s.jumlahSoal < MIN_SOAL) errors.push(`Jumlah soal minimal ${MIN_SOAL}.`);
    else if (s.jumlahSoal > MAX_SOAL) errors.push(`Jumlah soal maksimal ${MAX_SOAL}.`);
    if (!isInt(s.pilihan) || s.pilihan < 3 || s.pilihan > 5) errors.push('Pilihan jawaban harus 3 sampai 5 (A–C sampai A–E).');
    if (!Array.isArray(s.kunci)) {
      errors.push('Kunci jawaban belum diisi.');
    } else if (isInt(s.jumlahSoal) && isInt(s.pilihan) && s.pilihan >= 3 && s.pilihan <= 5) {
      if (s.kunci.length !== s.jumlahSoal) errors.push(`Kunci jawaban harus ${s.jumlahSoal} butir (terisi ${s.kunci.length}).`);
      const allowed = lettersFor(s.pilihan);
      s.kunci.forEach((answer, index) => {
        if (!allowed.includes(answer)) {
          errors.push(answer ? `Kunci soal ${index + 1}: huruf ${answer} tidak tersedia.` : `Kunci soal ${index + 1} belum dipilih.`);
        }
      });
    }
    return { valid: errors.length === 0, errors };
  }

  /** Memecah teks jawaban per huruf: spasi dan koma hanya pemisah, kelompok ("BADCA CCBDA") diterima. */
  function tokens(text) {
    return [...String(text == null ? '' : text).replace(/[\s,]+/g, '')];
  }

  /** @returns {{valid:boolean, kunci?:string[], errors?:string[]}} */
  function parseKeyText(text, setup) {
    const list = tokens(text).map((t) => t.toUpperCase());
    const allowed = lettersFor(setup.pilihan);
    const errors = [];
    if (list.length !== setup.jumlahSoal) errors.push(`Kunci harus ${setup.jumlahSoal} huruf (terbaca ${list.length}).`);
    list.forEach((answer, index) => {
      if (!allowed.includes(answer)) errors.push(`Kunci soal ${index + 1}: huruf ${answer} tidak tersedia.`);
    });
    return errors.length ? { valid: false, errors } : { valid: true, kunci: list };
  }

  /**
   * Memeriksa satu baris siswa.
   * @returns {{absen:string, ok:boolean, count:number, expected:number, message:string, jawaban?:string[]}}
   */
  function checkStudentRow(row, setup) {
    const absen = String(row && row.absen != null ? row.absen : '').trim();
    const expected = setup.jumlahSoal;
    const list = tokens(row && row.jawaban);
    const count = list.length;
    if (count < expected) {
      return { absen, ok: false, count, expected, message: `✗ baru ${count} dari ${expected} (kurang ${expected - count})` };
    }
    if (count > expected) {
      return { absen, ok: false, count, expected, message: `✗ kelebihan ${count - expected} jawaban (${count} dari ${expected})` };
    }
    const allowed = lettersFor(setup.pilihan);
    const jawaban = [];
    for (let i = 0; i < list.length; i++) {
      const token = list[i].toUpperCase();
      if (token === '.' || token === '-') jawaban.push('.');
      else if (allowed.includes(token)) jawaban.push(token);
      else return { absen, ok: false, count, expected, message: `✗ huruf ${list[i]} tidak tersedia pada soal ${i + 1}` };
    }
    return { absen, ok: true, count, expected, message: `✓ ${count}/${expected}`, jawaban };
  }

  /**
   * @returns {{valid:boolean, errors:string[], rows:{absen:string,jawaban:string[]}[], status:object[]}}
   */
  function validateStudents(setup, rows) {
    const list = Array.isArray(rows) ? rows : [];
    const errors = [];
    const status = [];
    const valid = [];
    const seen = new Set();
    if (list.length > MAX_SISWA) errors.push(`Jumlah siswa maksimal ${MAX_SISWA}.`);
    list.forEach((row, index) => {
      const check = checkStudentRow(row, setup);
      status.push(check);
      const label = check.absen || `baris ${index + 1}`;
      if (!check.absen) {
        errors.push(`Baris ${index + 1}: nomor absen wajib diisi.`);
        return;
      }
      if (check.absen.length > MAX_ABSEN) {
        errors.push(`Absen ${label.slice(0, 24)}…: nomor absen maksimal ${MAX_ABSEN} karakter.`);
        return;
      }
      const key = check.absen.toLocaleLowerCase('id');
      if (seen.has(key)) {
        errors.push(`Absen ${check.absen}: nomor absen sudah digunakan.`);
        return;
      }
      seen.add(key);
      if (!check.ok) {
        errors.push(`Absen ${check.absen}: ${check.message.replace(/^✗\s*/, '')}.`);
        return;
      }
      valid.push({ absen: check.absen, jawaban: check.jawaban });
    });
    return { valid: errors.length === 0, errors, rows: valid, status };
  }

  function warningsFor(jumlahSiswa) {
    const peringatan = [];
    if (jumlahSiswa < 10) peringatan.push('Siswa kurang dari 10: daya pembeda belum dapat diandalkan.');
    if (jumlahSiswa < 30) peringatan.push('Siswa kurang dari 30: statistik mudah berubah, tafsirkan dengan hati-hati.');
    return peringatan;
  }

  /**
   * Menjalankan analisis. Tidak menyimpan apa pun.
   * @returns {{success:true, result:object, peringatan:string[]}|{success:false, errors:string[]}}
   */
  function analyze(setup, rows) {
    const setupCheck = validateSetup(setup);
    if (!setupCheck.valid) return { success: false, errors: setupCheck.errors };
    const students = validateStudents(setup, rows);
    if (!students.valid) return { success: false, errors: students.errors };
    if (students.rows.length < MIN_SISWA) {
      return { success: false, errors: [`Hasil dibuka dengan minimal ${MIN_SISWA} siswa (terisi ${students.rows.length}).`] };
    }
    const result = engine.analyzeAssessment({
      butir: makeItems(setup.kunci, setup.pilihan),
      hasil: students.rows.map((r) => ({ siswaId: r.absen, jawaban: r.jawaban }))
    });
    if (!result.success) return { success: false, errors: result.errors };
    return { success: true, result, peringatan: warningsFor(students.rows.length) };
  }

  function changePilihan(state, pilihan) {
    if (!isInt(pilihan) || pilihan < 3 || pilihan > 5) return { ok: false, errors: ['Pilihan jawaban harus 3 sampai 5 (A–C sampai A–E).'] };
    if (pilihan < state.pilihan) {
      const allowed = lettersFor(pilihan);
      const errors = [];
      state.kunci.forEach((answer, index) => {
        if (answer && !allowed.includes(answer)) errors.push(`Kunci soal ${index + 1} memakai huruf ${answer}.`);
      });
      state.siswa.forEach((row) => {
        tokens(row.jawaban).forEach((token, index) => {
          const t = token.toUpperCase();
          if (LETTERS.includes(t) && !allowed.includes(t)) errors.push(`Absen ${row.absen}, soal ${index + 1}: jawaban ${t} tidak ada pada pilihan baru.`);
        });
      });
      if (errors.length) return { ok: false, errors: [...errors.slice(0, 20), 'Ubah dulu jawaban dan kunci tersebut sebelum mempersempit pilihan.'] };
    }
    return { ok: true, state: { ...state, pilihan } };
  }

  function changeJumlah(state, jumlah, confirm) {
    if (!isInt(jumlah) || jumlah < MIN_SOAL || jumlah > MAX_SOAL) {
      return { ok: false, errors: [`Jumlah soal harus bilangan bulat ${MIN_SOAL} sampai ${MAX_SOAL}.`] };
    }
    if (jumlah >= state.jumlahSoal) {
      const kunci = [...state.kunci, ...Array(jumlah - state.kunci.length).fill('')];
      return { ok: true, state: { ...state, jumlahSoal: jumlah, kunci } };
    }
    const kunciDibuang = state.kunci.slice(jumlah).filter(Boolean).length;
    const jawabanDibuang = state.siswa
      .map((row) => ({ absen: row.absen, jumlah: Math.max(0, tokens(row.jawaban).length - jumlah) }))
      .filter((entry) => entry.jumlah > 0);
    if ((kunciDibuang > 0 || jawabanDibuang.length > 0) && !confirm) {
      return { ok: false, needsConfirm: true, impact: { kunciDibuang, jawabanDibuang } };
    }
    const siswa = state.siswa.map((row) => (tokens(row.jawaban).length > jumlah
      ? { ...row, jawaban: tokens(row.jawaban).slice(0, jumlah).join('') }
      : { ...row }));
    return { ok: true, state: { ...state, jumlahSoal: jumlah, kunci: state.kunci.slice(0, jumlah), siswa } };
  }

  /**
   * Mengubah jumlah soal/pilihan tanpa menghilangkan data diam-diam. Tidak memutasi state.
   * @returns {{ok:true,state:object}|{ok:false,needsConfirm:true,impact:object}|{ok:false,errors:string[]}}
   */
  function applySetupChange(state, change) {
    const c = change || {};
    let next = { ...state, kunci: [...state.kunci], siswa: state.siswa.map((row) => ({ ...row })) };
    if (c.pilihan !== undefined && c.pilihan !== next.pilihan) {
      const r = changePilihan(next, c.pilihan);
      if (!r.ok) return r;
      next = r.state;
    }
    if (c.jumlahSoal !== undefined && c.jumlahSoal !== next.jumlahSoal) {
      const r = changeJumlah(next, c.jumlahSoal, c.confirm === true);
      if (!r.ok) return r;
      next = r.state;
    }
    return { ok: true, state: next };
  }

  /**
   * Menerapkan hasil impor ke state tes: info tes yang ada di berkas menggantikan isian lama,
   * yang tidak ada dipertahankan; kunci hanya diganti bila ada di berkas; siswa diganti seluruhnya.
   * @returns {object} state baru (tidak memutasi masukan)
   */
  function applyImport(state, imported) {
    const s = imported && imported.setup ? imported.setup : {};
    const jumlahSoal = isInt(s.jumlahSoal) ? s.jumlahSoal : state.jumlahSoal;
    const pilihan = isInt(s.pilihan) ? s.pilihan : state.pilihan;
    let kunci;
    if (Array.isArray(s.kunci)) kunci = [...s.kunci];
    else if (jumlahSoal === state.jumlahSoal) kunci = [...state.kunci];
    else kunci = Array(jumlahSoal).fill('');
    return {
      ...state,
      nama: typeof s.nama === 'string' && s.nama ? s.nama : state.nama,
      kelas: typeof s.kelas === 'string' && s.kelas ? s.kelas : state.kelas,
      jumlahSoal, pilihan, kunci,
      siswa: (imported && Array.isArray(imported.siswa) ? imported.siswa : []).map((r) => ({ absen: r.absen, jawaban: r.jawaban }))
    };
  }

  // ---- Lembar Excel/CSV: templat, impor, ekspor ----
  const cellText = (v) => (v == null ? '' : String(v).trim());
  const labelKey = (v) => cellText(v).toLocaleLowerCase('id').replace(/[^a-z]/g, '');
  const FORMULA_START = /^[=+\-@\t\r]/;
  const unsafeCell = (text) => (/^'[=+\-@\t\r]/.test(text) ? text.slice(1) : text);

  /** Menetralkan sel berawalan rumus; angka dibiarkan, null menjadi teks kosong. */
  function safeCell(value) {
    if (value == null) return '';
    if (typeof value === 'number') return value;
    const text = String(value);
    return FORMULA_START.test(text) ? `'${text}` : text;
  }

  const lastLetter = (pilihan) => lettersFor(pilihan)[pilihan - 1];
  const rangeLabel = (pilihan) => `A-${lastLetter(pilihan)}`;

  /** Templat unduhan: info tes, judul soal, baris KUNCI kosong, dan tiga baris contoh siswa. */
  function templateRows(jumlahSoal = MIN_SOAL, pilihan = 4) {
    const letters = lettersFor(pilihan);
    const rows = [
      ['Nama tes', ''], ['Kelas', ''], ['Pilihan jawaban', rangeLabel(pilihan)], [],
      ['No. Absen', ...Array.from({ length: jumlahSoal }, (_, i) => i + 1)],
      ['KUNCI', ...Array(jumlahSoal).fill('')]
    ];
    for (let s = 1; s <= 3; s++) {
      rows.push([String(s), ...Array.from({ length: jumlahSoal }, (_, i) => letters[(i + s) % letters.length])]);
    }
    return rows;
  }

  /**
   * Membaca lembar (baris-baris sel) menjadi setup parsial dan daftar siswa.
   * Tata letak dikenali dari label di kolom A, bukan dari nomor baris.
   * @returns {{setup:object, siswa:{absen:string,jawaban:string}[], errors:{baris:number|null,kolom?:string,pesan:string}[], warnings:string[]}}
   */
  function parseSheet(rows) {
    const errors = [];
    const warnings = [];
    const err = (baris, pesan, kolom) => errors.push({ baris, kolom, pesan });
    const grid = (Array.isArray(rows) ? rows : []).map((r) => (Array.isArray(r) ? r.map(cellText) : []));
    const headerAt = grid.findIndex((r) => labelKey(r[0]) === 'noabsen');
    if (headerAt < 0) {
      err(null, 'Baris judul "No. Absen" tidak ditemukan. Pakai template dari aplikasi.');
      return { setup: {}, siswa: [], errors, warnings };
    }
    const setup = {};
    let pilihanText = '';
    for (const [key, field] of [['namates', 'nama'], ['kelas', 'kelas'], ['pilihanjawaban', 'pilihan']]) {
      const at = grid.findIndex((r, i) => i < headerAt && labelKey(r[0]) === key);
      const value = at >= 0 ? unsafeCell(grid[at][1] || '') : '';
      if (field === 'pilihan') pilihanText = value;
      else if (value) setup[field] = value;
    }

    const head = grid[headerAt].slice(1);
    let n = head.length;
    while (n > 0 && head[n - 1] === '') n--;
    for (let i = 0; i < n; i++) {
      if (head[i] !== String(i + 1)) {
        err(headerAt + 1, `Judul kolom soal ke-${i + 1} bernilai "${head[i]}"; judul soal harus 1, 2, 3, … berurutan tanpa celah.`, `soal ${i + 1}`);
        break;
      }
    }
    if (n > MAX_SOAL) err(headerAt + 1, `Jumlah soal maksimal ${MAX_SOAL} (terbaca ${n}).`);
    else if (n < MIN_SOAL) err(headerAt + 1, `Jumlah soal minimal ${MIN_SOAL} (terbaca ${n}).`);
    if (errors.length) return { setup, siswa: [], errors, warnings };
    setup.jumlahSoal = n;

    const keyAt = grid.findIndex((r, i) => i > headerAt && labelKey(r[0]) === 'kunci');
    let kunci = null;
    if (keyAt >= 0) {
      const cells = Array.from({ length: n }, (_, i) => cellText(grid[keyAt][i + 1]).toUpperCase());
      if (cells.every((c) => c === '')) {
        warnings.push('Baris KUNCI kosong: isi kunci jawaban di Langkah 1.');
      } else {
        const before = errors.length;
        cells.forEach((c, i) => {
          if (c === '') err(keyAt + 1, `Kunci soal ${i + 1} kosong.`, `soal ${i + 1}`);
          else if (!LETTERS.includes(c)) err(keyAt + 1, `Kunci soal ${i + 1}: huruf ${c} tidak valid.`, `soal ${i + 1}`);
        });
        if (errors.length === before) kunci = cells;
      }
    } else {
      warnings.push('Baris KUNCI tidak ditemukan: isi kunci jawaban di Langkah 1.');
    }

    const siswa = [];
    for (let r = headerAt + 1; r < grid.length; r++) {
      if (r === keyAt) continue;
      const row = grid[r];
      if (row.every((c) => c === '')) continue;
      const baris = r + 1;
      if (row.slice(n + 1).some((c) => c !== '')) {
        err(baris, `Ada isi di kolom setelah soal ${n}.`);
        continue;
      }
      const absen = unsafeCell(row[0] || '');
      if (!absen) {
        err(baris, 'Nomor absen kosong.');
        continue;
      }
      const letters = [];
      const problems = [];
      for (let j = 0; j < n; j++) {
        const v = cellText(row[j + 1]).toUpperCase();
        if (v === '') problems.push(`jawaban kosong pada soal ${j + 1} (tulis . bila tidak dijawab)`);
        else if (v === '.' || v === '-') letters.push('.');
        else if (LETTERS.includes(v)) letters.push(v);
        else problems.push(`huruf "${v}" tidak valid pada soal ${j + 1}`);
      }
      if (problems.length) {
        problems.slice(0, 3).forEach((m) => err(baris, `Absen ${absen}: ${m}.`, absen));
        if (problems.length > 3) err(baris, `Absen ${absen}: dan ${problems.length - 3} masalah lain pada baris ini.`, absen);
        continue;
      }
      siswa.push({ absen, jawaban: letters.join('') });
    }

    if (kunci) setup.kunci = kunci;
    const m = /([A-E])\s*$/i.exec(pilihanText);
    if (m && m[1].toUpperCase().charCodeAt(0) - 64 >= 3) {
      setup.pilihan = m[1].toUpperCase().charCodeAt(0) - 64;
    } else {
      const used = [...(kunci || []), ...siswa.flatMap((x) => [...x.jawaban])];
      setup.pilihan = used.includes('E') ? 5 : 4;
      if (pilihanText) warnings.push(`Pilihan jawaban "${pilihanText}" tidak dikenali; dipakai ${rangeLabel(setup.pilihan)}.`);
    }
    return { setup, siswa, errors, warnings };
  }

  /**
   * Tiga lembar ekspor. `Jawaban` identik tata letaknya dengan templat sehingga dapat diimpor ulang.
   * @returns {{Jawaban:unknown[][], Hasil:unknown[][], Ringkasan:unknown[][]}}
   */
  function exportSheets(setup, rows, result) {
    const n = setup.jumlahSoal;
    const letters = lettersFor(setup.pilihan);
    const Jawaban = [
      ['Nama tes', setup.nama], ['Kelas', setup.kelas || ''], ['Pilihan jawaban', rangeLabel(setup.pilihan)], [],
      ['No. Absen', ...Array.from({ length: n }, (_, i) => i + 1)],
      ['KUNCI', ...setup.kunci],
      ...rows.map((r) => [r.absen, ...(Array.isArray(r.jawaban) ? r.jawaban : tokens(r.jawaban)).map((x) => (x == null ? '.' : x))])
    ].map((row) => row.map(safeCell));

    const Hasil = [
      ['No', 'Kunci', 'Jumlah benar', 'Tingkat kesukaran (P)', 'Kategori kesukaran', 'Daya pembeda (D)', 'Kategori daya pembeda',
        ...letters.map((l) => `Pilih ${l} (%)`), 'Saran', 'Alasan'],
      ...result.butir.map((b) => [
        b.nomor, b.kunci, b.benar, b.p, b.kategoriP, b.dayaPembeda, b.kategoriD,
        ...letters.map((l) => { const o = b.opsi.find((x) => x.label === l); return o ? o.persen : 0; }),
        b.rekomendasi, b.alasan.join(' ')
      ])
    ].map((row) => row.map(safeCell));

    const r = result.ringkasan;
    const Ringkasan = [
      ['Nama tes', setup.nama], ['Kelas', setup.kelas || ''], ['Jumlah siswa', r.peserta], ['Jumlah soal', r.jumlahButir],
      ['Rata-rata nilai (0-100)', r.rerataPersen], ['KR-20 (reliabilitas)', r.kr20], ['Kategori KR-20', r.kategoriKr20]
    ].map((row) => row.map(safeCell));
    return { Jawaban, Hasil, Ringkasan };
  }

  // ---- Deskripsi hasil untuk guru ----
  const fmt = (value, digits = 2) => (value == null ? '—' : value.toFixed(digits).replace('.', ','));
  const LABEL_P = { sukar: 'Sukar', sedang: 'Sedang', mudah: 'Mudah' };
  const WARNA_P = { sukar: 'buruk', sedang: 'baik', mudah: 'sedang' };
  const LABEL_D = { 'sangat-baik': 'Sangat baik', baik: 'Baik', cukup: 'Cukup', jelek: 'Jelek', negatif: 'Negatif (periksa)', 'tidak-dapat-dihitung': '—' };
  const WARNA_D = { 'sangat-baik': 'baik', baik: 'baik', cukup: 'sedang', jelek: 'buruk', negatif: 'buruk', 'tidak-dapat-dihitung': 'netral' };
  const SARAN = { pertahankan: 'Pertahankan', revisi: 'Revisi', buang: 'Buang' };
  const LABEL_KR20 = {
    dipertanyakan: 'dipertanyakan, hasil tes belum konsisten', rendah: 'rendah', 'cukup-rendah': 'cukup rendah',
    cukup: 'cukup', baik: 'baik', 'sangat-baik': 'sangat baik'
  };

  function reasonsFor(b, mati) {
    const alasan = [];
    if (b.kategoriD === 'negatif') alasan.push('Siswa pandai justru lebih sering salah daripada siswa kurang pandai; periksa kunci jawaban.');
    else if (b.kategoriD === 'jelek') alasan.push('Soal kurang membedakan siswa pandai dari siswa kurang pandai.');
    else if (b.dayaPembeda == null) alasan.push('Daya pembeda tidak dapat dihitung (jawaban siswa seragam atau terlalu sedikit).');
    if (mati.length) alasan.push(`Pengecoh ${mati.join(', ')} dipilih kurang dari 5% siswa (tidak berfungsi).`);
    if (b.kunciMencurigakan && b.kategoriD !== 'negatif') alasan.push('Ada sinyal kunci atau pengecoh yang perlu diperiksa.');
    if (b.kategoriP === 'mudah') alasan.push('Soal tergolong mudah.');
    else if (b.kategoriP === 'sukar') alasan.push('Soal tergolong sukar.');
    return alasan.length ? alasan.join(' ') : 'Tidak ada masalah yang terdeteksi.';
  }

  function readingGuide() {
    const a = engine.AMBANG_BAWAAN;
    return [
      { istilah: 'Tingkat kesukaran (P)', arti: `Bagian siswa yang menjawab benar. Sukar bila ${fmt(a.kesukaran.sukarMaks)} ke bawah, sedang sampai ${fmt(a.kesukaran.sedangMaks)}, mudah bila di atasnya. Soal sedang paling cocok untuk ujian umum.` },
      { istilah: 'Daya pembeda (D)', arti: `Seberapa baik soal membedakan siswa pandai dari yang kurang pandai. Jelek bila ${fmt(a.dayaPembeda.jelekMaks)} ke bawah, cukup sampai ${fmt(a.dayaPembeda.cukupMaks)}, baik sampai ${fmt(a.dayaPembeda.baikMaks)}, sangat baik di atasnya. Bernilai negatif berarti siswa pandai justru lebih sering salah: periksa kunci jawaban.` },
      { istilah: 'Pengecoh', arti: `Pilihan jawaban yang salah. Pengecoh berfungsi bila dipilih minimal ${a.pengecohMatiPersen}% siswa; yang hampir tidak dipilih sebaiknya diganti.` },
      { istilah: 'Saran', arti: 'Pertahankan: soal baik. Revisi: perbaiki redaksi atau pengecoh. Buang: soal bermasalah berat; periksa kunci sebelum memutuskan.' },
      { istilah: 'Reliabilitas (KR-20)', arti: 'Seberapa konsisten tes secara keseluruhan (0 sampai 1); makin tinggi makin baik. Dengan siswa kurang dari 30 angkanya mudah berubah.' }
    ];
  }

  /**
   * Mengubah keluaran mesin menjadi bahasa dan warna untuk layar/PDF guru. Tidak menghitung statistik baru.
   * Warna: 'baik' | 'sedang' | 'buruk' | 'netral' (nilai tidak dapat dihitung).
   */
  function describeResult(result) {
    const r = result.ringkasan;
    const kalimatReliabilitas = r.kr20 == null
      ? 'Reliabilitas tidak dapat dihitung (jawaban siswa seragam atau data terlalu sedikit).'
      : `Reliabilitas (KR-20) ${fmt(r.kr20)}: ${LABEL_KR20[r.kategoriKr20] || r.kategoriKr20}.`;
    const baris = result.butir.map((b) => {
      const sebaran = b.opsi.map((o) => ({ label: o.label, jumlah: o.jumlah, persen: o.persen, kunci: o.kunci, tidakBerfungsi: o.pengecohMati }));
      const mati = sebaran.filter((o) => o.tidakBerfungsi).map((o) => o.label);
      return {
        nomor: b.nomor, kunci: b.kunci,
        p: b.p, teksP: fmt(b.p), labelP: LABEL_P[b.kategoriP] || '—', warnaP: WARNA_P[b.kategoriP] || 'netral',
        d: b.dayaPembeda, teksD: fmt(b.dayaPembeda), labelD: LABEL_D[b.kategoriD] || '—', warnaD: WARNA_D[b.kategoriD] || 'netral',
        saran: SARAN[b.rekomendasi] || 'Revisi', alasan: reasonsFor(b, mati), sebaran, perluCekKunci: Boolean(b.kunciMencurigakan)
      };
    });
    return {
      ringkasan: { peserta: r.peserta, rerata: r.rerataPersen, kr20: r.kr20, kategoriKr20: r.kategoriKr20, kalimatReliabilitas, peringatan: warningsFor(r.peserta) },
      baris,
      caraMembaca: readingGuide()
    };
  }

  // ---- Draf otomatis ----
  const DRAFT_KEYS = ['nama', 'kelas', 'jumlahSoal', 'pilihan', 'kunci', 'siswa', 'langkah'];
  const MAX_JAWABAN_TEKS = 600;

  /** Bentuk draf yang boleh disimpan: hanya kolom di atas; tanpa nama siswa. */
  function validateDraft(draft) {
    const errors = [];
    if (!draft || typeof draft !== 'object' || Array.isArray(draft)) return { valid: false, errors: ['Draf tidak valid.'] };
    Object.keys(draft).forEach((key) => { if (!DRAFT_KEYS.includes(key)) errors.push(`Kolom draf tidak dikenal: ${key}.`); });
    if (typeof draft.nama !== 'string' || draft.nama.length > MAX_NAMA) errors.push('Nama tes pada draf tidak valid.');
    if (typeof draft.kelas !== 'string' || draft.kelas.length > MAX_KELAS) errors.push('Kelas pada draf tidak valid.');
    if (!isInt(draft.jumlahSoal) || draft.jumlahSoal < 1 || draft.jumlahSoal > MAX_SOAL) errors.push('Jumlah soal pada draf tidak valid.');
    if (!isInt(draft.pilihan) || draft.pilihan < 3 || draft.pilihan > 5) errors.push('Pilihan jawaban pada draf tidak valid.');
    if (![1, 2, 3].includes(draft.langkah)) errors.push('Langkah pada draf tidak valid.');
    if (!Array.isArray(draft.kunci) || draft.kunci.length > MAX_SOAL || draft.kunci.some((k) => k !== '' && !LETTERS.includes(k))) {
      errors.push('Kunci pada draf tidak valid.');
    }
    if (!Array.isArray(draft.siswa) || draft.siswa.length > MAX_SISWA) {
      errors.push('Daftar siswa pada draf tidak valid.');
    } else if (draft.siswa.some((row) => !row || typeof row !== 'object' || Array.isArray(row)
      || Object.keys(row).some((k) => k !== 'absen' && k !== 'jawaban')
      || typeof row.absen !== 'string' || row.absen.length > MAX_ABSEN
      || typeof row.jawaban !== 'string' || row.jawaban.length > MAX_JAWABAN_TEKS)) {
      errors.push('Baris siswa pada draf tidak valid.');
    }
    return { valid: errors.length === 0, errors };
  }

  /**
   * Menyiapkan satu kali proses (analisis, ekspor) dari masukan renderer: hanya kolom yang dikenal dipakai
   * (nama siswa atau kolom lain dibuang), lalu memvalidasi ulang dan menganalisis.
   * @returns {{success:true,setup:object,rows:{absen:string,jawaban:string[]}[],result:object,desc:object,peringatan:string[]}|{success:false,errors:string[]}}
   */
  function prepareRun(input) {
    const src = input && typeof input === 'object' ? input : {};
    const raw = src.setup && typeof src.setup === 'object' ? src.setup : null;
    if (!raw || !Array.isArray(src.siswa)) return { success: false, errors: ['Masukan analisis tidak lengkap.'] };
    const setup = {
      nama: typeof raw.nama === 'string' ? raw.nama.trim() : '',
      kelas: typeof raw.kelas === 'string' ? raw.kelas.trim() : '',
      jumlahSoal: raw.jumlahSoal,
      pilihan: raw.pilihan,
      kunci: Array.isArray(raw.kunci) ? raw.kunci.map((k) => (typeof k === 'string' ? k.trim().toUpperCase() : '')) : raw.kunci
    };
    const input_rows = src.siswa.map((r) => ({
      absen: r && r.absen != null ? String(r.absen) : '',
      jawaban: r && r.jawaban != null ? (Array.isArray(r.jawaban) ? r.jawaban.join('') : String(r.jawaban)) : ''
    }));
    const run = analyze(setup, input_rows);
    if (!run.success) return run;
    return {
      success: true, setup, rows: validateStudents(setup, input_rows).rows,
      result: run.result, desc: describeResult(run.result), peringatan: run.peringatan
    };
  }

  /** Nama berkas aman untuk Windows: karakter terlarang diganti garis bawah, maksimal 80 karakter. */
  function fileSafeName(text, fallback) {
    const clean = String(text == null ? '' : text).replace(/[<>:"/\\|?*\x00-\x1f]/g, '_').trim().slice(0, 80);
    return clean || fallback;
  }

  return {
    MIN_SOAL, MAX_SOAL, MIN_SISWA, MAX_SISWA, MAX_ABSEN, LETTERS,
    makeItems, validateSetup, parseKeyText, checkStudentRow, validateStudents, analyze, applySetupChange,
    safeCell, templateRows, parseSheet, exportSheets, describeResult, validateDraft,
    prepareRun, fileSafeName, applyImport
  };
})();
export default reference;

