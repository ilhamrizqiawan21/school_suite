<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { computed, reactive, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { assessmentRequest, saveBlob } from '@/lib/assessment-http';
// @ts-expect-error Original JavaScript engine is intentionally retained without changing its calculations.
import engine from '@/lib/reference/quickAnalysis.js';

defineProps<{ classes: { name: string }[] }>();
type Student = { absen: string; jawaban: string };
const blank = () => ({ nama: '', kelas: '', jumlahSoal: 20, pilihan: 4, kunci: Array<string>(20).fill('') });
const setup = reactive(blank());
const students = ref<Student[]>([{ absen: '1', jawaban: '' }]);
const step = ref(1);
const errors = ref<string[]>([]);
const result = ref<any>(null);
const expanded = ref<number | null>(null);
const busy = ref(false);
const keyText = ref('');
const draftStatus = ref('');
const draftReady = ref(false);
const importPreview = ref<any>(null);
const letters = computed(() => ['A','B','C','D','E'].slice(0, setup.pilihan));
const rowStatus = computed(() => engine.validateStudents(setup, students.value));
const setupValid = computed(() => engine.validateSetup(setup).valid);
const validRows = computed(() => rowStatus.value.rows.length);
let draftTimer: ReturnType<typeof setTimeout> | undefined;
let savingDraft: Promise<void> = Promise.resolve();
let draftGeneration = 0;
const snapshot = () => ({ ...setup, kunci: [...setup.kunci], siswa: students.value.map(s => ({...s})), langkah: step.value });
function restore(data: any) {
    Object.assign(setup, { nama: data.nama ?? '', kelas: data.kelas ?? '', jumlahSoal: data.jumlahSoal, pilihan: data.pilihan, kunci: data.kunci });
    students.value = data.siswa.map((s: Student) => ({ absen: s.absen ?? '', jawaban: s.jawaban ?? '' }));
    step.value = Math.min(data.langkah ?? 1, 2);
}
onMounted(async () => {
    try {
        const { draft } = await (await assessmentRequest('/penilaian/analisis/cepat/draft', undefined, 'GET')).json();
        if (draft && engine.validateDraft(draft).valid) {
            if (confirm('Ada draf analisis yang belum diekspor. Lanjutkan draf?')) restore(draft);
            else await assessmentRequest('/penilaian/analisis/cepat/draft', undefined, 'DELETE');
        }
    } catch (e) { errors.value = [(e as Error).message]; }
    draftReady.value = true;
});
watch([setup, students, step], () => {
    result.value = step.value === 3 ? result.value : null;
    if (!draftReady.value) return;
    clearTimeout(draftTimer);
    const generation = draftGeneration;
    const data = snapshot();
    draftStatus.value = 'Menyimpan draf…';
    draftTimer = setTimeout(() => {
        savingDraft = savingDraft.then(async () => {
            if (generation !== draftGeneration) return;
            try { await assessmentRequest('/penilaian/analisis/cepat/draft', data, 'PUT'); draftStatus.value = 'Draf tersimpan'; }
            catch (e) { draftStatus.value = 'Draf belum tersimpan'; errors.value = [(e as Error).message]; }
        });
    }, 900);
}, { deep: true });
onBeforeUnmount(() => {
    if (draftTimer && draftReady.value && draftStatus.value === 'Menyimpan draf…') {
        const data = snapshot();
        savingDraft = savingDraft.then(async () => { await assessmentRequest('/penilaian/analisis/cepat/draft', data, 'PUT'); }).catch(() => {});
    }
    clearTimeout(draftTimer);
});
async function clearDraft() {
    clearTimeout(draftTimer);
    draftGeneration++;
    await savingDraft;
    await assessmentRequest('/penilaian/analisis/cepat/draft', undefined, 'DELETE');
    draftStatus.value = 'Salinan Excel sudah diunduh';
}
function applyKey() {
    const r = engine.parseKeyText(keyText.value, setup);
    errors.value = r.errors ?? [];
    if (r.valid) setup.kunci = r.kunci;
}
function changeSetup(event: Event, field: 'jumlahSoal' | 'pilihan') {
    const value = Number((event.target as HTMLInputElement).value);
    let r = engine.applySetupChange(snapshot(), { [field]: value });
    if (r.needsConfirm && confirm(`Mengurangi soal akan membuang ${r.impact.kunciDibuang} kunci dan jawaban nomor terakhir. Lanjutkan?`)) {
        r = engine.applySetupChange(snapshot(), { [field]: value, confirm: true });
    }
    if (r.ok) { Object.assign(setup, {jumlahSoal:r.state.jumlahSoal,pilihan:r.state.pilihan,kunci:r.state.kunci}); students.value = r.state.siswa; errors.value = []; }
    else errors.value = r.errors ?? ['Perubahan dibatalkan.'];
    (event.target as HTMLInputElement).value = String(setup[field]);
}
function next() { const r = engine.validateSetup(setup); errors.value = r.errors; if (r.valid) step.value = 2; }
function analyze() {
    const r = engine.prepareRun({ setup, siswa: students.value });
    errors.value = r.errors ?? [];
    if (r.success) { result.value = r; step.value = 3; }
}
async function run(action: () => Promise<void>) {
    if (busy.value) return;
    busy.value = true; errors.value = [];
    try { await action(); } catch (e) { errors.value = [(e as Error).message]; } finally { busy.value = false; }
}
async function template() {
    await run(async () => {
        const response = await assessmentRequest('/penilaian/analisis/cepat/export', {sheets:{Jawaban:engine.templateRows(setup.jumlahSoal,setup.pilihan)}});
        saveBlob(await response.blob(), 'template-jawaban.xlsx');
    });
}
async function importFile(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0]; if (!file) return;
    await run(async () => {
        const form = new FormData(); form.append('file', file);
        const { rows } = await (await assessmentRequest('/penilaian/analisis/cepat/import', form)).json();
        const parsed = engine.parseSheet(rows);
        const candidate = engine.applyImport(snapshot(), parsed);
        const validation = engine.validateStudents(candidate, candidate.siswa);
        importPreview.value = { ...parsed, validation, candidate };
    });
    input.value = '';
}
function confirmImport() {
    const p = importPreview.value; if (!p) return;
    if (students.value.some(s => s.jawaban) && !confirm('Ganti tes dan jawaban yang sudah diisi dengan data impor?')) return;
    const rows = p.validation.rows;
    restore({...p.candidate, siswa:rows.map((s:any)=>({absen:s.absen,jawaban:s.jawaban.map((v:any)=>v ?? '.').join('')})),langkah:2});
    if (!setupValid.value) step.value = 1;
    importPreview.value = null; errors.value = [];
}
async function exportExcel() {
    if (!result.value) return;
    await run(async () => {
        const sheets = engine.exportSheets(setup,result.value.rows,result.value.result);
        const response = await assessmentRequest('/penilaian/analisis/cepat/export',{sheets});
        saveBlob(await response.blob(), `${engine.fileSafeName(setup.nama,'analisis-soal')}.xlsx`);
        await clearDraft();
    });
}
async function exportPdf() {
    if (!result.value) return;
    await run(async () => {
        const d = result.value.desc;
        const rows = d.baris.map((b:any)=>[String(b.nomor), b.kunci, `${b.teksP} (${b.labelP})`, `${b.teksD} (${b.labelD})`, b.sebaran.map((o:any)=>`${o.label}${o.kunci?' (kunci)':''}: ${o.jumlah} (${o.persen}%)${o.tidakBerfungsi?' · tidak berfungsi':''}`).join('; '), `${b.saran}. ${b.alasan}`]);
        const response = await assessmentRequest('/penilaian/analisis/cepat/pdf',{name:setup.nama,class:setup.kelas,summary:`${d.ringkasan.peserta} siswa · Rata-rata ${d.ringkasan.rerata} · ${d.ringkasan.kalimatReliabilitas}`,warnings:result.value.peringatan,rows,guide:d.caraMembaca});
        saveBlob(await response.blob(), `${engine.fileSafeName(setup.nama,'analisis-soal')}.pdf`);
    });
}
async function newTest() {
    if (!confirm('Mulai tes baru? Ekspor Excel terlebih dahulu untuk menyimpan salinan tes ini.')) return;
    await run(async () => { await clearDraft(); draftReady.value=false; Object.assign(setup,blank());students.value=[{absen:'1',jawaban:''}];step.value=1;result.value=null;keyText.value='';await Promise.resolve();draftReady.value=true;draftStatus.value=''; });
}
</script>

<template>
 <section class="quick-analysis view">
  <header class="analysis-heading"><div><p class="eyebrow">Evaluasi · Pilihan ganda</p><h1>Analisis soal</h1><p>Siapkan tes dan kunci, isi jawaban siswa, lalu lihat hasilnya.</p></div><span role="status" class="draft-status">{{ draftStatus }}</span></header>
  <nav class="steps" aria-label="Langkah analisis"><button v-for="(label,i) in ['Tes dan kunci','Jawaban siswa','Hasil analisis']" :key="label" :aria-current="step===i+1?'step':undefined" :disabled="i+1>step" @click="step=i+1"><span>{{ i+1 }}</span>{{ label }}</button></nav>
  <ul v-if="errors.length" role="alert" class="errors"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
  <section v-if="importPreview" class="panel import-preview" aria-label="Pratinjau impor"><h2>Pratinjau impor</h2><p>{{ importPreview.candidate.nama || 'Nama tes belum diisi' }} · {{ importPreview.candidate.jumlahSoal }} soal · {{ importPreview.validation.rows.length }} siswa valid</p><ul><li v-for="(e,i) in importPreview.errors" :key="i">Baris {{ e.baris ?? '—' }}: {{ e.pesan }}</li><li v-for="e in importPreview.validation.errors" :key="e">{{ e }}</li><li v-for="w in importPreview.warnings" :key="w">{{ w }}</li></ul><div class="actions"><AppButton variant="primary" class="primary-button" :disabled="!importPreview.validation.rows.length" @click="confirmImport">Impor baris valid</AppButton><AppButton variant="secondary" class="secondary-button" @click="importPreview=null">Batalkan</AppButton></div></section>
  <section v-if="step===1" class="panel analysis-panel">
   <h2>1. Siapkan tes dan kunci</h2>
   <div class="setup"><label>Nama tes<input v-model="setup.nama" maxlength="100" placeholder="Contoh: PTS Ganjil"></label><label>Kelas<input v-model="setup.kelas" list="analysis-classes" maxlength="120" placeholder="Pilih atau ketik kelas"><datalist id="analysis-classes"><option v-for="c in classes" :key="c.name">{{ c.name }}</option></datalist></label><label>Jumlah soal (20–200)<input type="number" min="20" max="200" :value="setup.jumlahSoal" @change="changeSetup($event,'jumlahSoal')"></label><label>Pilihan jawaban<select :value="setup.pilihan" @change="changeSetup($event,'pilihan')"><option :value="3">A–C</option><option :value="4">A–D</option><option :value="5">A–E</option></select></label></div>
   <div class="paste-key"><label>Tempel kunci sekaligus<input v-model="keyText" placeholder="BADCAC… (spasi dan koma boleh)"></label><AppButton variant="secondary" type="button" class="secondary-button" @click="applyKey">Terapkan kunci</AppButton></div>
   <div class="key-grid"><div v-for="(_,i) in setup.kunci" :key="i" class="key-cell"><strong>{{ i+1 }}</strong><div role="group" :aria-label="`Kunci soal ${i+1}`"><button v-for="l in letters" :key="l" :aria-pressed="setup.kunci[i]===l" @click="setup.kunci[i]=l">{{ l }}</button></div></div></div>
   <div class="actions"><label class="secondary-button file-label">Impor Excel / CSV<input type="file" accept=".xlsx,.csv" :disabled="busy" @change="importFile"></label><AppButton variant="primary" class="primary-button" :disabled="!setupValid || busy" @click="next">Lanjutkan →</AppButton></div>
  </section>
  <section v-else-if="step===2" class="panel analysis-panel">
   <h2>2. Isi jawaban siswa</h2><p>{{ setup.nama }} · {{ setup.kelas }} · {{ setup.jumlahSoal }} soal. Gunakan nomor absen. Titik (.) atau tanda minus (-) berarti tidak dijawab.</p>
   <div class="actions"><AppButton variant="secondary" icon="excel" class="excel-button" :disabled="busy" @click="template">Unduh template Excel</AppButton><label class="secondary-button file-label">Impor Excel / CSV<input type="file" accept=".xlsx,.csv" :disabled="busy" @change="importFile"></label></div>
   <div class="table-scroll"><table class="app-table"><thead><tr><th>No. absen</th><th>Jawaban</th><th>Kelengkapan</th><th>Tindakan</th></tr></thead><tbody><tr v-for="(s,i) in students" :key="i"><td><input v-model="s.absen" maxlength="20" :aria-label="`Nomor absen baris ${i+1}`"></td><td><input v-model="s.jawaban" maxlength="600" :aria-label="`Jawaban absen ${s.absen}`" placeholder="BADCAC…"></td><td :class="rowStatus.status[i]?.ok?'valid':'invalid'">{{ rowStatus.status[i]?.message }}</td><td><AppButton variant="danger" class="danger-button" :aria-label="`Hapus jawaban absen ${s.absen}`" @click="students.splice(i,1)"><template #icon><svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4h8v2m-9 0v14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V6M10 11v6M14 11v6"/></svg></template>Hapus</AppButton></td></tr></tbody></table></div>
   <ul v-if="rowStatus.errors.length" class="errors" aria-live="polite"><li v-for="message in rowStatus.errors" :key="message">{{ message }}</li></ul>
   <p>{{ validRows }}/{{ students.length }} baris lengkap. Minimal 5 siswa untuk membuka hasil.</p><div class="actions"><AppButton variant="secondary" icon="plus" class="secondary-button" :disabled="students.length>=500" @click="students.push({absen:String(students.length+1),jawaban:''})"> Tambah siswa</AppButton><AppButton variant="primary" class="primary-button" :disabled="students.length<5 || !rowStatus.valid" @click="analyze">Lihat hasil →</AppButton></div>
  </section>
  <section v-else-if="result" class="panel analysis-panel">
   <h2>3. Hasil analisis · {{ setup.nama }}</h2><div class="metrics"><div><span>Siswa</span><strong>{{ result.desc.ringkasan.peserta }}</strong></div><div><span>Rata-rata nilai</span><strong>{{ result.desc.ringkasan.rerata }}</strong></div><div><span>Reliabilitas KR-20</span><strong>{{ result.desc.ringkasan.kr20 ?? '—' }}</strong></div></div><p>{{ result.desc.ringkasan.kalimatReliabilitas }}</p><p v-for="w in result.peringatan" :key="w" class="warning">{{ w }}</p>
   <div class="table-scroll"><table class="app-table results"><thead><tr><th>No.</th><th>Kunci</th><th>Tingkat kesukaran</th><th>Daya pembeda</th><th>Saran</th></tr></thead><tbody><template v-for="b in result.desc.baris" :key="b.nomor"><tr><td><AppButton variant="secondary" class="secondary-button" :aria-expanded="expanded===b.nomor" :aria-label="`Sebaran jawaban soal ${b.nomor}`" @click="expanded=expanded===b.nomor?null:b.nomor">{{ b.nomor }}</AppButton></td><td>{{ b.kunci }}</td><td :class="`quality-${b.warnaP}`">{{ b.teksP }} · {{ b.labelP }}</td><td :class="`quality-${b.warnaD}`">{{ b.teksD }} · {{ b.labelD }}</td><td><strong>{{ b.saran }}</strong><small>{{ b.alasan }}</small></td></tr><tr v-if="expanded===b.nomor"><td colspan="5"><div class="distribution"><span v-for="o in b.sebaran" :key="o.label">{{ o.label }}{{ o.kunci?' (kunci)':'' }}: {{ o.jumlah }} ({{ o.persen }}%)<small v-if="o.tidakBerfungsi">Pengecoh tidak berfungsi</small></span></div></td></tr></template></tbody></table></div>
   <details><summary>Cara membaca hasil</summary><p v-for="g in result.desc.caraMembaca" :key="g.istilah"><strong>{{ g.istilah }}</strong> — {{ g.arti }}</p></details>
   <div class="actions"><AppButton variant="secondary" icon="excel" class="excel-button" :disabled="busy" @click="exportExcel">Ekspor Excel</AppButton><AppButton variant="secondary" icon="pdf" class="pdf-button" :disabled="busy" @click="exportPdf">Ekspor PDF</AppButton><AppButton variant="secondary" class="secondary-button" :disabled="busy" @click="newTest">Tes baru</AppButton></div>
  </section>
 </section>
</template>

<style scoped>
.quick-analysis{display:grid;gap:1.25rem;max-width:1440px;margin:auto;color:var(--ink)}.analysis-heading{display:flex;align-items:start;justify-content:space-between;gap:1rem}.analysis-heading h1{font-size:var(--type-3xl);font-weight:var(--weight-bold)}.analysis-heading p,.draft-status,.analysis-panel>p{color:var(--ink-soft);font-size:var(--type-sm);line-height:1.6}.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:.65rem}.steps button{display:flex;align-items:center;gap:.6rem;padding:.85rem;border:1px solid var(--line);border-radius:.7rem;background:var(--surface);color:var(--ink-soft);text-align:left}.steps button[aria-current]{background:var(--primary-soft);border-color:var(--primary);color:var(--primary)}.steps span{font-weight:var(--weight-bold)}.steps button:disabled{opacity:.5}.analysis-panel{display:grid;gap:1rem;padding:clamp(1rem,2vw,1.5rem);min-width:0}.analysis-panel h2,.import-preview h2{font-size:1.1rem;font-weight:var(--weight-bold)}.setup{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}label{display:grid;gap:.4rem;font-size:var(--type-sm);color:var(--ink-soft)}input,select{min-width:0;width:100%;min-height:42px;padding:.6rem;border:1px solid var(--line-strong);border-radius:.5rem;color:var(--ink);background:var(--surface)}.paste-key{display:flex;align-items:end;gap:.7rem}.paste-key label{flex:1}.key-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:.6rem}.key-cell{display:grid;gap:.4rem;border:1px solid var(--line);border-radius:.6rem;padding:.65rem}.key-cell>div{display:flex;gap:.2rem}.key-cell button{flex:1;min-height:36px;border:1px solid var(--line);border-radius:.35rem;background:var(--surface);color:var(--ink-soft)}.key-cell [aria-pressed=true]{background:var(--primary-action);color:white;border-color:var(--primary-action)}.actions{display:flex;align-items:center;flex-wrap:wrap;gap:.6rem}.file-label{position:relative;cursor:pointer;overflow:hidden}.file-label input{position:absolute;inset:0;opacity:0;cursor:pointer}.errors{padding:1rem;color:var(--red);background:var(--red-soft);border-radius:.6rem}.warning{color:var(--gold);background:var(--gold-soft);padding:.8rem;border-radius:.6rem}.table-scroll{overflow:auto;min-width:0}.app-table{min-width:650px}.app-table td:first-child{width:100px}small{display:block;color:var(--ink-soft);font-size:var(--type-meta)}.results td:last-child{max-width:380px}.results td:last-child small{white-space:normal}.metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:.7rem}.metrics>div{display:grid;gap:.35rem;padding:1rem;background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem}.metrics span{color:var(--ink-soft);font-size:var(--type-meta)}.metrics strong{font-size:1.5rem}.distribution{display:flex;flex-wrap:wrap;gap:1rem}.valid,.quality-baik{color:var(--green)!important}.invalid,.quality-buruk{color:var(--red)!important}.quality-sedang{color:var(--gold)!important}summary{cursor:pointer;font-weight:var(--weight-semibold)}details p{margin-top:.8rem;font-size:var(--type-sm);line-height:1.6}.import-preview{padding:1rem;display:grid;gap:.75rem}.import-preview ul{max-height:180px;overflow:auto;color:var(--gold)}button:focus-visible{outline:3px solid var(--primary);outline-offset:2px}@media(max-width:600px){.setup{grid-template-columns:1fr}.steps button{flex-direction:column;align-items:start;font-size:var(--type-control)}.analysis-heading{flex-direction:column}.paste-key{align-items:stretch;flex-direction:column}.metrics{grid-template-columns:1fr}}
.btn-icon{width:1rem;height:1rem;flex-shrink:0}
</style>
