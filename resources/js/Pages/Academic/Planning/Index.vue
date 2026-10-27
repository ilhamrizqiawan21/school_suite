<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

type PackageKey = 'merdeka' | 'kbc' | 'k13' | 'blank';
interface ReferenceRow { key: string; name: string; content: string; phase?: string; element?: string }
interface ChapterRow { id: string | number; title: string; semester: string }
interface UnitRow { id: string | number; chapter_id: string | number; code: string; objective: string; material: string; jp: number; kkm?: number | null }
interface PlanData { references: ReferenceRow[]; chapters: ChapterRow[]; units: UnitRow[]; waktu: { jp_per_minggu: number; minggu_efektif: { '1': number; '2': number } } }
interface Plan { id: number; subject_id: number; subject_name: string; grade_level: number; curriculum: string; updated_at: string }
interface SelectedPlan { id: number; subject_id: number; grade_level: number; curriculum: string; data: PlanData }
interface Subject { id: number; name: string; code: string }

const props = defineProps<{
    active_term: { id: number; name: string } | null;
    subjects: Subject[];
    grade_levels: number[];
    plans: Plan[];
    selected_plan: SelectedPlan | null;
}>();

const packageOptions: { value: PackageKey; label: string; detail: string }[] = [
    { value: 'merdeka', label: 'Kurikulum Merdeka', detail: 'CP, tujuan pembelajaran, dan alur tujuan.' },
    { value: 'kbc', label: 'Kurikulum Berbasis Cinta', detail: 'Perencanaan dengan penguatan nilai dan karakter.' },
    { value: 'k13', label: 'Kurikulum 2013', detail: 'Kompetensi inti, kompetensi dasar, dan indikator.' },
    { value: 'blank', label: 'Dokumen kosong', detail: 'Mulai dengan struktur ATP tanpa contoh isi.' },
];

const blankData = (): PlanData => ({ references: [], chapters: [], units: [], waktu: { jp_per_minggu: 2, minggu_efektif: { '1': 18, '2': 18 } } });
const selectedId = ref<number | null>(props.selected_plan?.id ?? null);
const packageChoice = ref<PackageKey>('blank');
const loadingPlan = ref(false);
const notice = ref('');
const form = useForm({ subject_id: '', grade_level: '', curriculum: 'merdeka', content: blankData() });

const hasFoundation = computed(() => Boolean(props.active_term && props.subjects.length && props.grade_levels.length));
const currentPlan = computed(() => props.plans.find((plan) => plan.id === selectedId.value) ?? null);
const isEditing = computed(() => selectedId.value !== null && props.selected_plan?.id === selectedId.value);
const curriculumName = (value: string) => packageOptions.find((item) => item.value === value)?.label ?? value;
const cpRows = computed(() => form.content.references.filter((reference) => Boolean(reference.phase) || /^CP(?:-|$)/i.test(reference.key) || reference.name.toLowerCase().includes('capaian pembelajaran')));
const generalReferences = computed(() => form.content.references.filter((reference) => !cpRows.value.includes(reference)));

function newKey(prefix: string) {
    return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
}

function loadSelectedPlan(plan: SelectedPlan | null) {
    selectedId.value = plan?.id ?? null;
    if (!plan) return;
    const curriculum = (['merdeka', 'kbc', 'k13', 'blank'] as string[]).includes(plan.curriculum)
        ? plan.curriculum as PackageKey
        : 'blank';
    packageChoice.value = curriculum;
    form.subject_id = String(plan.subject_id);
    form.grade_level = String(plan.grade_level);
    form.curriculum = curriculum;
    form.content = {
        references: (plan.data?.references ?? []).map((row) => ({ ...row })),
        chapters: (plan.data?.chapters ?? []).map((row) => ({ ...row })),
        units: (plan.data?.units ?? []).map((row) => ({ ...row })),
        waktu: plan.data?.waktu ?? { jp_per_minggu: 2, minggu_efektif: { '1': 18, '2': 18 } },
    };
    form.clearErrors();
    form.defaults();
}

watch(() => props.selected_plan, (plan) => loadSelectedPlan(plan), { immediate: true });

function startNewPlan() {
    selectedId.value = null;
    packageChoice.value = 'merdeka';
    form.reset();
    form.subject_id = props.subjects[0] ? String(props.subjects[0].id) : '';
    form.grade_level = props.grade_levels[0] ? String(props.grade_levels[0]) : '';
    form.curriculum = 'merdeka';
    form.content = blankData();
    form.clearErrors();
    notice.value = '';
}

function selectPlan(id: number) {
    loadingPlan.value = true;
    notice.value = '';
    router.get('/akademik/perangkat/atp', { plan_id: id }, {
        preserveScroll: true,
        onFinish: () => { loadingPlan.value = false; },
    });
}

function choosePackage(value: PackageKey) {
    packageChoice.value = value;
    form.curriculum = value;
}

function addReference() {
    form.content.references.push({ key: newKey('ref'), name: '', content: '' });
}
function addCp() {
    form.content.references.push({ key: newKey('CP'), name: 'Capaian Pembelajaran', phase: 'D', element: '', content: '' });
}
function addK13Presets() {
    const presets = [
        { key: 'KI-1', name: 'Sikap Spiritual (KI-1)', content: 'Menghargai dan menghayati ajaran agama yang dianutnya.' },
        { key: 'KI-2', name: 'Sikap Sosial (KI-2)', content: 'Menghargai dan menghayati perilaku jujur, disiplin, tanggung jawab, peduli (toleransi, gotong royong), santun, percaya diri, dalam berinteraksi secara efektif dengan lingkungan sosial dan alam dalam jangkauan pergaulan dan keberadaannya.' },
        { key: 'KI-3', name: 'Pengetahuan (KI-3)', content: 'Memahami pengetahuan (faktual, konseptual, dan prosedural) berdasarkan rasa ingin tahunya tentang ilmu pengetahuan, teknologi, seni, budaya terkait fenomena dan kejadian tampak mata.' },
        { key: 'KI-4', name: 'Keterampilan (KI-4)', content: 'Mencoba, mengolah, dan menyaji dalam ranah konkret (menggunakan, mengurai, merangkai, memodifikasi, dan membuat) dan ranah abstrak (menulis, membaca, menghitung, menggambar, dan mengarang) sesuai dengan yang dipelajari di sekolah dan sumber lain yang sama dalam sudut pandang/teori.' },
    ];
    const existing = new Map(form.content.references.map((reference) => [reference.key, reference]));
    form.content.references = presets.map((preset) => ({ ...preset, ...(existing.get(preset.key) ?? {}) }))
        .concat(form.content.references.filter((reference) => !presets.some((preset) => preset.key === reference.key)));
}
function addChapter() {
    form.content.chapters.push({ id: newKey('chapter'), title: '', semester: '1' });
}
function addUnit(chapterId?: string | number) {
    const parent = chapterId ?? form.content.chapters[0]?.id ?? '';
    form.content.units.push({ id: newKey('unit'), chapter_id: parent, code: '', objective: '', material: '', jp: 2, kkm: null });
}
function removeUnit(id: string | number) {
    form.content.units = form.content.units.filter((unit) => unit.id !== id);
}
function removeChapter(id: string | number) {
    form.content.chapters = form.content.chapters.filter((chapter) => chapter.id !== id);
    form.content.units = form.content.units.filter((unit) => unit.chapter_id !== id);
}

function savePlan() {
    if (!form.subject_id || !form.grade_level) return;
    form.content.references = form.content.references.map((reference) => reference.phase
        ? { ...reference, key: reference.key.startsWith('CP-') ? reference.key : `CP-${reference.key}`, name: `CP Fase ${reference.phase}${reference.element?.trim() ? ` · ${reference.element.trim()}` : ''}` }
        : reference);
    const options = {
        preserveScroll: true,
        onSuccess: () => { form.defaults(); notice.value = 'ATP/Silabus berhasil disimpan.'; },
    };
    if (isEditing.value && selectedId.value) {
        form.put(`/akademik/perangkat/atp/${selectedId.value}`, options);
    } else {
        form.post('/akademik/perangkat/atp', options);
    }
}

function deletePlan() {
    if (!selectedId.value || !window.confirm(`Hapus ATP/Silabus ${currentPlan.value?.subject_name ?? 'ini'}? Tindakan ini tidak dapat dibatalkan.`)) return;
    router.delete(`/akademik/perangkat/atp/${selectedId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            selectedId.value = null;
            startNewPlan();
            notice.value = 'ATP/Silabus berhasil dihapus.';
        },
    });
}
</script>

<template>
    <AppLayout title="ATP & Silabus">
        <Head title="ATP & Silabus" />
        <main class="planning-page">
            <header class="page-intro">
                <div>
                    <p class="eyebrow">Perangkat pembelajaran <span class="term-chip">{{ active_term?.name ?? 'Periode belum aktif' }}</span></p>
                    <h1>ATP &amp; Silabus</h1>
                    <p class="intro-copy">Rangkai capaian, bab, dan tujuan pembelajaran dalam satu dokumen yang mudah dirawat.</p>
                </div>
                <button v-if="hasFoundation" type="button" class="action-primary" @click="startNewPlan">Buat dokumen</button>
            </header>

            <section v-if="!active_term" class="foundation-empty" role="status">
                <span class="empty-mark" aria-hidden="true">i</span>
                <div><h2>Periode akademik belum aktif</h2><p>ATP/Silabus akan tersedia setelah sekolah menetapkan periode akademik aktif.</p></div>
            </section>
            <section v-else-if="!subjects.length || !grade_levels.length" class="foundation-empty" role="status">
                <span class="empty-mark" aria-hidden="true">i</span>
                <div><h2>Belum ada penugasan aktif</h2><p>Pastikan akun guru memiliki penugasan mata pelajaran dan tingkat kelas pada periode ini.</p></div>
            </section>

            <div v-else class="planning-layout">
                <aside class="plan-rail" aria-label="Dokumen ATP dan Silabus">
                    <div class="rail-heading"><div><span class="section-kicker">Ruang dokumen</span><h2>ATP tersimpan</h2></div><span class="count-badge">{{ plans.length }}</span></div>
                    <button type="button" class="new-plan-link" @click="startNewPlan"><span aria-hidden="true">＋</span> Dokumen baru</button>
                    <div v-if="plans.length" class="plan-list">
                        <button v-for="plan in plans" :key="plan.id" type="button" class="plan-item" :class="{ active: selectedId === plan.id }" :aria-current="selectedId === plan.id ? 'page' : undefined" @click="selectPlan(plan.id)">
                            <span class="plan-subject">{{ plan.subject_name }}</span>
                            <span class="plan-meta">Kelas {{ plan.grade_level }} <i aria-hidden="true">·</i> {{ curriculumName(plan.curriculum) }}</span>
                            <span class="plan-updated">Diperbarui {{ plan.updated_at || '—' }}</span>
                        </button>
                    </div>
                    <p v-else class="rail-empty">Belum ada dokumen. Mulai dengan membuat ATP pertama.</p>
                    <div v-if="loadingPlan" class="rail-loading" role="status">Membuka dokumen…</div>
                </aside>

                <section class="editor-shell" aria-labelledby="editor-title">
                    <form class="editor-form" @submit.prevent="savePlan">
                        <div class="editor-topline">
                            <div><span class="section-kicker">{{ isEditing ? 'Dokumen tersimpan' : 'Dokumen baru' }}</span><h2 id="editor-title">{{ isEditing ? currentPlan?.subject_name : 'Susun ATP / Silabus' }}</h2></div>
                            <span class="save-state" :class="{ saving: form.processing, unsaved: form.isDirty && !form.processing }"><span class="state-dot" />{{ form.processing ? 'Menyimpan…' : form.isDirty ? 'Perubahan belum disimpan' : isEditing ? 'Perubahan tersimpan' : 'Dokumen baru · belum disimpan' }}</span>
                        </div>

                        <div class="document-fields">
                            <label class="field"><span>Mata pelajaran</span><select v-model="form.subject_id" required :disabled="isEditing"><option value="" disabled>Pilih mata pelajaran</option><option v-for="subject in subjects" :key="subject.id" :value="String(subject.id)">{{ subject.name }}<template v-if="subject.code"> ({{ subject.code }})</template></option></select><small v-if="form.errors.subject_id" class="field-error">{{ form.errors.subject_id }}</small></label>
                            <label class="field"><span>Tingkat kelas</span><select v-model="form.grade_level" required :disabled="isEditing"><option value="" disabled>Pilih tingkat</option><option v-for="grade in grade_levels" :key="grade" :value="String(grade)">Kelas {{ grade }}</option></select><small v-if="form.errors.grade_level" class="field-error">{{ form.errors.grade_level }}</small></label>
                        </div>

                        <section class="package-section" aria-labelledby="package-title">
                            <div class="section-heading"><div><h3 id="package-title">Pilih kerangka kurikulum</h3><p>Kerangka menentukan titik awal penyusunan dokumen.</p></div><small v-if="form.errors.curriculum" class="field-error">{{ form.errors.curriculum }}</small></div>
                            <div class="package-grid">
                                <button v-for="option in packageOptions" :key="option.value" type="button" class="package-option" :class="{ chosen: packageChoice === option.value }" :aria-pressed="packageChoice === option.value" @click="choosePackage(option.value)">
                                    <span class="radio-indicator" aria-hidden="true"></span><span><strong>{{ option.label }}</strong><small>{{ option.detail }}</small></span>
                                </button>
                            </div>
                        </section>

                        <section class="editor-section references-section">
                            <div class="section-heading"><div><span class="section-kicker">Landasan kurikulum</span><h3>{{ ['merdeka', 'kbc'].includes(form.curriculum) ? 'Capaian Pembelajaran (CP)' : form.curriculum === 'k13' ? 'Kompetensi Inti (KI)' : 'Referensi &amp; capaian' }}</h3><p>{{ ['merdeka', 'kbc'].includes(form.curriculum) ? 'Catat CP per fase dan elemen. Capaian ini akan dibawa ke Modul Ajar dan paket administrasi.' : form.curriculum === 'k13' ? 'Isi KI-1 sampai KI-4 atau tambahkan referensi capaian lainnya.' : 'Tambahkan acuan kurikulum yang menjadi dasar perencanaan.' }}</p></div><div class="reference-actions"><button v-if="form.curriculum === 'k13'" type="button" class="action-secondary" @click="addK13Presets">Isi contoh KI-1 sampai KI-4</button><button v-if="['merdeka', 'kbc'].includes(form.curriculum)" type="button" class="action-secondary" @click="addCp">＋ Tambah CP</button><button type="button" class="action-secondary" @click="addReference">＋ Tambah referensi</button></div></div>
                            <div v-if="['merdeka', 'kbc'].includes(form.curriculum)" class="cp-list">
                                <article v-for="(reference, index) in cpRows" :key="reference.key" class="cp-card">
                                    <div class="cp-card-heading"><span class="row-index">{{ String(index + 1).padStart(2, '0') }}</span><strong>Capaian Pembelajaran</strong><button type="button" class="remove-button" :aria-label="`Hapus CP ${index + 1}`" @click="form.content.references.splice(form.content.references.indexOf(reference), 1)">Hapus</button></div>
                                    <div class="cp-fields"><label class="field"><span>Fase</span><select v-model="reference.phase" required><option v-for="phase in ['A', 'B', 'C', 'D', 'E', 'F']" :key="phase" :value="phase">Fase {{ phase }}</option></select></label><label class="field"><span>Elemen (opsional)</span><input v-model="reference.element" maxlength="100" placeholder="Contoh: Bilangan" /></label><label class="field cp-content"><span>Deskripsi CP</span><textarea v-model="reference.content" rows="5" maxlength="10000" required placeholder="Tuliskan capaian pembelajaran untuk fase dan elemen ini…" /></label></div>
                                </article>
                                <div v-if="!cpRows.length" class="section-empty"><strong>CP belum ditambahkan</strong><br />Gunakan “Tambah CP”, lalu isi fase, elemen, dan deskripsi capaian.</div>
                            </div>
                            <div v-if="generalReferences.length" class="reference-list">
                                <article v-for="(reference, index) in generalReferences" :key="reference.key" class="reference-row">
                                    <span class="row-index">{{ String(index + 1).padStart(2, '0') }}</span>
                                    <div class="reference-fields"><label class="field"><span>Nama referensi</span><input v-model="reference.name" placeholder="Contoh: Capaian Pembelajaran Fase D" /></label><label class="field"><span>Isi / catatan</span><textarea v-model="reference.content" rows="2" placeholder="Tuliskan capaian atau ringkasan acuan…" /></label></div>
                                    <button type="button" class="remove-button" :aria-label="`Hapus referensi ${reference.name || index + 1}`" @click="form.content.references.splice(form.content.references.indexOf(reference), 1)">Hapus</button>
                                </article>
                            </div>
                            <div v-if="!form.content.references.length" class="section-empty">Belum ada referensi kurikulum. Tambahkan CP atau acuan yang relevan.</div>
                        </section>

                        <section class="editor-section">
                            <div class="section-heading"><div><span class="section-kicker">Kalender &amp; beban belajar</span><h3>Alokasi waktu</h3><p>Perkiraan JP efektif dihitung dari minggu efektif dikali JP per minggu.</p></div></div>
                            <div class="document-fields">
                                <label class="field"><span>JP per minggu</span><input v-model.number="form.content.waktu.jp_per_minggu" type="number" min="1" max="40" /></label>
                                <label class="field"><span>Minggu efektif · Semester 1</span><input v-model.number="form.content.waktu.minggu_efektif['1']" type="number" min="0" max="30" /></label>
                                <label class="field"><span>Minggu efektif · Semester 2</span><input v-model.number="form.content.waktu.minggu_efektif['2']" type="number" min="0" max="30" /></label>
                                <div class="allocation-summary"><strong>{{ form.content.waktu.jp_per_minggu * form.content.waktu.minggu_efektif['1'] }} JP</strong><span>efektif semester 1</span><strong>{{ form.content.waktu.jp_per_minggu * form.content.waktu.minggu_efektif['2'] }} JP</strong><span>efektif semester 2</span></div>
                            </div>
                        </section>

                        <section class="editor-section sequence-section">
                            <div class="section-heading"><div><span class="section-kicker">Alur pembelajaran</span><h3>Bab &amp; unit pembelajaran</h3><p>Kelompokkan tujuan pembelajaran ke dalam bab, semester, dan alokasi jam.</p></div><button type="button" class="action-secondary" @click="addChapter">＋ Tambah bab</button></div>
                            <div v-if="form.content.chapters.length" class="chapter-list">
                                <article v-for="(chapter, index) in form.content.chapters" :key="chapter.id" class="chapter-block">
                                    <header class="chapter-heading"><span class="chapter-index">Bab {{ String(index + 1).padStart(2, '0') }}</span><div class="chapter-title-field"><label class="field"><span>Nama bab</span><input v-model="chapter.title" placeholder="Contoh: Pengukuran dan sains" /></label><label class="field semester-field"><span>Semester</span><select v-model="chapter.semester"><option value="1">Semester 1</option><option value="2">Semester 2</option></select></label></div><button type="button" class="remove-button" :aria-label="`Hapus bab ${chapter.title || index + 1} beserta unitnya`" @click="removeChapter(chapter.id)">Hapus bab</button></header>
                                    <div class="unit-heading"><span>Tujuan pembelajaran</span><button type="button" class="text-action" @click="addUnit(chapter.id)">＋ Tambah unit</button></div>
                                    <div v-if="form.content.units.filter((unit) => unit.chapter_id === chapter.id).length" class="unit-list">
                                        <article v-for="unit in form.content.units.filter((item) => item.chapter_id === chapter.id)" :key="unit.id" class="unit-row">
                                            <label class="field code-field"><span>Kode</span><input v-model="unit.code" placeholder="7.1.1" /></label>
                                            <label class="field objective-field"><span>Tujuan pembelajaran</span><textarea v-model="unit.objective" rows="2" placeholder="Peserta didik mampu…" /></label>
                                            <label class="field material-field"><span>Materi</span><input v-model="unit.material" placeholder="Pokok materi" /></label>
                                            <label class="field jp-field"><span>JP</span><input v-model.number="unit.jp" type="number" min="1" max="100" /></label>
                                            <button type="button" class="remove-icon" :aria-label="`Hapus unit ${unit.code || 'pembelajaran'}`" @click="removeUnit(unit.id)">×</button>
                                        </article>
                                    </div>
                                    <p v-else class="unit-empty">Bab ini belum memiliki unit pembelajaran.</p>
                                </article>
                            </div>
                            <div v-else class="section-empty chapter-empty"><strong>Alur masih kosong</strong><span>Tambahkan bab pertama, lalu susun unit dan alokasi waktunya.</span></div>
                        </section>

                        <section class="editor-section">
                            <div class="section-heading"><div><span class="section-kicker">Kriteria ketercapaian</span><h3>KKTP / KKM per tujuan</h3><p>Tetapkan batas nilai per unit. Nilai kosong berarti belum ditentukan.</p></div></div>
                            <div v-if="form.content.units.length" class="threshold-list">
                                <label v-for="unit in form.content.units" :key="unit.id" class="threshold-row"><span><strong>{{ unit.code || 'Tujuan tanpa kode' }}</strong><small>{{ unit.objective || unit.material || 'Deskripsi tujuan belum diisi' }}</small></span><input v-model.number="unit.kkm" type="number" min="0" max="100" placeholder="75" :aria-label="`KKTP atau KKM untuk ${unit.code || 'tujuan'}`" /></label>
                            </div>
                            <div v-else class="section-empty">Tambahkan unit pembelajaran untuk mengatur KKTP atau KKM.</div>
                        </section>

                        <p v-if="notice" class="success-notice" role="status">{{ notice }}</p>
                        <p v-if="form.hasErrors" class="error-notice" role="alert">Periksa kembali isian yang ditandai. Dokumen belum berhasil disimpan.</p>
                        <footer class="editor-footer">
                            <button v-if="isEditing" type="button" class="delete-button" @click="deletePlan">Hapus dokumen</button>
                            <span class="footer-hint">{{ form.content.chapters.length }} bab <i>·</i> {{ form.content.units.length }} unit <i>·</i> {{ form.content.references.length }} referensi</span>
                            <button type="submit" class="action-primary save-button" :disabled="form.processing || !form.subject_id || !form.grade_level">{{ form.processing ? 'Menyimpan…' : isEditing ? 'Simpan perubahan' : 'Buat ATP / Silabus' }}</button>
                        </footer>
                    </form>
                </section>
            </div>
        </main>
    </AppLayout>
</template>

<style scoped>
.planning-page{--plan-ink:var(--ink,#17243a);--plan-muted:var(--ink-soft,#61718a);--plan-line:var(--line,#dce4ef);--plan-surface:var(--surface,#fff);--plan-canvas:var(--surface-subtle,#f5f7fb);--plan-primary:var(--primary,#315caa);--plan-primary-soft:var(--primary-soft,#eaf0fb);max-width:1560px;margin:0 auto;padding:clamp(1rem,2.5vw,2.25rem);color:var(--plan-ink)}
.page-intro{display:flex;align-items:flex-end;justify-content:space-between;gap:1.5rem;margin-bottom:1.5rem}.page-intro h1{margin:.35rem 0 .45rem;font-size:clamp(1.7rem,3vw,2.35rem);line-height:1.12;font-weight:800;letter-spacing:-.04em}.intro-copy{max-width:620px;margin:0;color:var(--plan-muted);line-height:1.55}.eyebrow,.section-kicker{font-size:.72rem;font-weight:800;letter-spacing:.04em;color:var(--plan-primary)}.term-chip{display:inline-flex;margin-left:.45rem;padding:.28rem .55rem;border:1px solid var(--plan-line);border-radius:999px;color:var(--plan-muted);font-weight:650;letter-spacing:0}.action-primary,.action-secondary,.new-plan-link,.text-action,.delete-button,.remove-button,.remove-icon{font:inherit;cursor:pointer}.action-primary{min-height:44px;padding:.7rem 1rem;border:1px solid var(--plan-primary);border-radius:.7rem;background:var(--plan-primary);color:#fff;font-weight:750;box-shadow:0 5px 14px rgb(49 92 170 / 17%)}.action-primary:hover{filter:brightness(1.06)}.planning-layout{display:grid;grid-template-columns:minmax(230px,285px) minmax(0,1fr);align-items:start;gap:1.15rem}.plan-rail,.editor-shell,.foundation-empty{border:1px solid var(--plan-line);border-radius:1rem;background:var(--plan-surface);box-shadow:0 7px 24px rgb(24 44 78 / 4%)}.plan-rail{position:sticky;top:1rem;overflow:hidden}.rail-heading{display:flex;align-items:center;justify-content:space-between;padding:1.05rem 1rem .85rem}.rail-heading h2{margin:.25rem 0 0;font-size:1.05rem;font-weight:800}.count-badge{display:grid;min-width:28px;height:28px;place-items:center;border-radius:999px;background:var(--plan-primary-soft);color:var(--plan-primary);font-size:.78rem;font-weight:800}.new-plan-link{display:flex;width:calc(100% - 1.25rem);min-height:42px;align-items:center;gap:.55rem;margin:0 .625rem .7rem;padding:.55rem .7rem;border:1px dashed var(--plan-primary);border-radius:.65rem;background:transparent;color:var(--plan-primary);font-size:.86rem;font-weight:750;text-align:left}.new-plan-link span{font-size:1.2rem}.plan-list{display:grid;gap:.35rem;max-height:min(66vh,720px);overflow:auto;padding:0 .5rem .65rem}.plan-item{display:grid;gap:.28rem;width:100%;padding:.75rem .7rem;border:1px solid transparent;border-radius:.7rem;background:transparent;color:var(--plan-ink);text-align:left;cursor:pointer}.plan-item:hover{background:var(--plan-canvas)}.plan-item.active{border-color:color-mix(in srgb,var(--plan-primary) 30%,transparent);background:var(--plan-primary-soft)}.plan-subject{font-size:.9rem;font-weight:780}.plan-meta,.plan-updated{color:var(--plan-muted);font-size:.74rem}.plan-meta i,.footer-hint i{padding:0 .2rem;font-style:normal}.rail-empty,.rail-loading{margin:.2rem .75rem .9rem;padding:.8rem;border-radius:.65rem;background:var(--plan-canvas);color:var(--plan-muted);font-size:.82rem;line-height:1.5}.rail-loading{color:var(--plan-primary)}.editor-shell{min-width:0}.editor-form{padding:clamp(1rem,2vw,1.55rem)}.editor-topline,.section-heading,.editor-footer,.chapter-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem}.editor-topline{padding-bottom:1.1rem;border-bottom:1px solid var(--plan-line)}.editor-topline h2{margin:.25rem 0 0;font-size:1.35rem;font-weight:820;letter-spacing:-.025em}.save-state{display:inline-flex;align-items:center;gap:.4rem;color:var(--plan-muted);font-size:.75rem;white-space:nowrap}.state-dot{width:7px;height:7px;border-radius:50%;background:#32a477}.save-state.saving .state-dot{background:#cf8a21;animation:pulse 1s infinite alternate}@keyframes pulse{to{opacity:.35}}.document-fields{display:grid;grid-template-columns:1fr 1fr;gap:.85rem;margin:1.15rem 0}.field{display:grid;min-width:0;gap:.35rem}.field>span{color:var(--plan-muted);font-size:.75rem;font-weight:750}.field input,.field select,.field textarea{width:100%;min-height:42px;padding:.63rem .7rem;border:1px solid var(--plan-line);border-radius:.58rem;outline:none;background:var(--plan-surface);color:var(--plan-ink);font:inherit;font-size:.85rem;line-height:1.4}.field textarea{min-height:76px;resize:vertical}.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--plan-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--plan-primary) 20%,transparent)}.field :disabled{background:var(--plan-canvas);color:var(--plan-muted)}.field-error{color:#b42332;font-size:.74rem}.package-section{padding:1rem 0 1.15rem;border-top:1px solid var(--plan-line);border-bottom:1px solid var(--plan-line)}.section-heading{align-items:flex-end;margin-bottom:.8rem}.section-heading h3{margin:.2rem 0;font-size:1rem;font-weight:800}.section-heading p{margin:.2rem 0 0;color:var(--plan-muted);font-size:.8rem;line-height:1.45}.package-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.55rem}.package-option{display:flex;min-height:86px;align-items:flex-start;gap:.6rem;padding:.75rem;border:1px solid var(--plan-line);border-radius:.7rem;background:var(--plan-surface);color:var(--plan-ink);text-align:left;cursor:pointer}.package-option.chosen{border-color:var(--plan-primary);background:var(--plan-primary-soft);box-shadow:inset 0 0 0 1px var(--plan-primary)}.package-option strong,.package-option small{display:block}.package-option strong{font-size:.78rem;line-height:1.35}.package-option small{margin-top:.27rem;color:var(--plan-muted);font-size:.7rem;line-height:1.4}.radio-indicator{flex:0 0 15px;width:15px;height:15px;margin-top:.05rem;border:1.5px solid #9aa8ba;border-radius:50%}.chosen .radio-indicator{border:4px solid var(--plan-primary);background:var(--plan-surface)}.editor-section{padding:1.25rem 0;border-bottom:1px solid var(--plan-line)}.action-secondary{min-height:38px;padding:.52rem .7rem;border:1px solid var(--plan-line);border-radius:.58rem;background:var(--plan-surface);color:var(--plan-ink);font-size:.78rem;font-weight:730;white-space:nowrap}.action-secondary:hover{border-color:var(--plan-primary);color:var(--plan-primary)}.reference-list,.chapter-list{display:grid;gap:.65rem}.reference-row{display:grid;grid-template-columns:36px minmax(0,1fr) auto;align-items:start;gap:.75rem;padding:.8rem;border:1px solid var(--plan-line);border-radius:.75rem;background:var(--plan-canvas)}.row-index,.chapter-index{color:var(--plan-primary);font-size:.72rem;font-weight:820}.row-index{padding-top:1.7rem}.reference-fields{display:grid;gap:.6rem}.section-empty{padding:1rem;border:1px dashed var(--plan-line);border-radius:.7rem;color:var(--plan-muted);font-size:.83rem;line-height:1.5}.remove-button{padding:.4rem .5rem;border:0;border-radius:.4rem;background:transparent;color:#a63740;font-size:.73rem;font-weight:720}.remove-button:hover{background:rgb(180 35 50 / 8%)}.chapter-block{overflow:hidden;border:1px solid var(--plan-line);border-radius:.8rem}.chapter-heading{align-items:flex-end;padding:.8rem .85rem;background:var(--plan-canvas)}.chapter-index{align-self:center;white-space:nowrap}.chapter-title-field{display:grid;flex:1;grid-template-columns:minmax(0,1fr) 135px;gap:.65rem}.unit-heading{display:flex;align-items:center;justify-content:space-between;padding:.65rem .85rem;border-top:1px solid var(--plan-line);color:var(--plan-muted);font-size:.76rem;font-weight:750}.text-action{padding:.35rem .45rem;border:0;border-radius:.4rem;background:transparent;color:var(--plan-primary);font-size:.76rem;font-weight:780}.text-action:hover{background:var(--plan-primary-soft)}.unit-list{display:grid;gap:.5rem;padding:0 .75rem .75rem}.unit-row{display:grid;grid-template-columns:85px minmax(180px,1.5fr) minmax(130px,1fr) 64px 30px;align-items:end;gap:.55rem;padding:.7rem;border:1px solid var(--plan-line);border-radius:.65rem}.unit-row .field textarea{min-height:62px}.unit-empty{margin:0;padding:.2rem .85rem .85rem;color:var(--plan-muted);font-size:.78rem}.chapter-empty{display:grid;gap:.25rem}.chapter-empty strong{color:var(--plan-ink)}.editor-footer{position:sticky;bottom:0;z-index:2;flex-wrap:wrap;margin:0 calc(clamp(1rem,2vw,1.55rem)*-1) calc(clamp(1rem,2vw,1.55rem)*-1);padding:.85rem clamp(1rem,2vw,1.55rem);border-top:1px solid var(--plan-line);border-radius:0 0 1rem 1rem;background:var(--plan-surface)}.footer-hint{margin-left:auto;color:var(--plan-muted);font-size:.75rem}.save-button{min-width:160px}.delete-button{padding:.55rem .65rem;border:1px solid #e7bbc0;border-radius:.58rem;background:transparent;color:#a63740;font-size:.78rem;font-weight:740}.foundation-empty{display:flex;align-items:flex-start;gap:1rem;max-width:760px;margin:3rem auto;padding:1.4rem}.empty-mark{display:grid;flex:0 0 34px;height:34px;place-items:center;border-radius:50%;background:var(--plan-primary-soft);color:var(--plan-primary);font-weight:850}.foundation-empty h2{margin:0 0 .3rem;font-size:1.05rem;font-weight:800}.foundation-empty p{margin:0;color:var(--plan-muted);line-height:1.55}.success-notice,.error-notice{margin:1rem 0 0;padding:.7rem .8rem;border-radius:.55rem;font-size:.82rem}.success-notice{background:#e9f7f0;color:#176c4b}.error-notice{background:#fff0f0;color:#a52c37}button:focus-visible{outline:3px solid color-mix(in srgb,var(--plan-primary) 55%,transparent);outline-offset:2px}
.allocation-summary{display:grid;grid-template-columns:auto 1fr;align-content:center;column-gap:.6rem;row-gap:.2rem;padding:.55rem .75rem;border-left:2px solid var(--plan-primary);color:var(--plan-muted);font-size:.75rem}.allocation-summary strong{color:var(--plan-primary);font-size:.9rem}.threshold-list{display:grid;gap:.4rem}.threshold-row{display:grid;grid-template-columns:minmax(0,1fr) 90px;align-items:center;gap:.8rem;padding:.65rem .75rem;border:1px solid var(--plan-line);border-radius:.6rem;background:var(--plan-canvas)}.threshold-row span{display:grid;gap:.2rem;min-width:0}.threshold-row strong{color:var(--plan-ink);font-size:.8rem}.threshold-row small{overflow:hidden;color:var(--plan-muted);font-size:.74rem;text-overflow:ellipsis;white-space:nowrap}.threshold-row input{width:100%;min-height:40px;padding:.5rem;border:1px solid var(--plan-line);border-radius:.5rem;background:var(--plan-surface);color:var(--plan-ink);font:inherit;text-align:center}
@media(max-width:1050px){.planning-layout{grid-template-columns:220px minmax(0,1fr)}.package-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.unit-row{grid-template-columns:75px minmax(0,1fr) 90px 30px}.material-field{grid-column:2/4}.jp-field{grid-column:1}.remove-icon{grid-column:4;grid-row:1}}
@media(max-width:740px){.planning-page{padding:1rem}.page-intro{align-items:flex-start;flex-direction:column}.planning-layout{grid-template-columns:minmax(0,1fr)}.plan-rail{position:static}.plan-list{display:flex;max-height:none;overflow-x:auto}.plan-item{flex:0 0 min(250px,78vw)}.editor-topline,.section-heading{align-items:flex-start;flex-direction:column}.save-state{white-space:normal}.document-fields{grid-template-columns:1fr}.package-grid{grid-template-columns:1fr 1fr}.reference-row{grid-template-columns:25px minmax(0,1fr)}.reference-row>.remove-button{grid-column:2;justify-self:start}.row-index{padding-top:1.7rem}.chapter-heading{align-items:stretch;flex-direction:column}.chapter-title-field{grid-template-columns:1fr}.unit-row{grid-template-columns:1fr 1fr}.objective-field,.material-field{grid-column:1/3}.jp-field{grid-column:1}.remove-icon{grid-column:2;grid-row:3;justify-self:end}.editor-footer{position:static;align-items:stretch}.footer-hint{width:100%;margin:0}.save-button{width:100%}}
@media(max-width:420px){.package-grid{grid-template-columns:1fr}.action-secondary{white-space:normal}.editor-form{padding:.9rem}.editor-footer{margin:0 -.9rem -.9rem;padding:.8rem .9rem}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
.reference-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.45rem}
@media(max-width:740px){.reference-actions{width:100%;justify-content:flex-start}}
.cp-list{display:grid;gap:.65rem}.cp-card{padding:.85rem;border:1px solid var(--plan-line);border-radius:.75rem;background:var(--plan-canvas)}.cp-card-heading{display:flex;align-items:center;gap:.55rem;margin-bottom:.65rem}.cp-card-heading strong{flex:1;color:var(--plan-ink);font-size:.84rem}.cp-card-heading .row-index{padding:0}.cp-fields{display:grid;grid-template-columns:120px minmax(170px,.7fr) minmax(0,1.6fr);gap:.65rem}.cp-content textarea{min-height:112px;resize:vertical}
.planning-page{container-type:inline-size}
.page-intro .action-primary{white-space:nowrap}
.save-state.unsaved .state-dot{background:#cf8a21}
@container (max-width: 950px){.planning-layout{grid-template-columns:minmax(0,1fr)}.plan-rail{position:static}.plan-list{grid-template-columns:repeat(auto-fit,minmax(190px,1fr));max-height:none;overflow:visible}.plan-item{min-width:0}.package-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@container (max-width: 700px){.cp-fields{grid-template-columns:minmax(0,1fr)}.cp-content{grid-column:1}}
@media(max-width:740px){.cp-fields{grid-template-columns:minmax(0,1fr)}.cp-content{grid-column:1}}
</style>
