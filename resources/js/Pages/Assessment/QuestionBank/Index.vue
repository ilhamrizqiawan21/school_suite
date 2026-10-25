<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Pair = { left: string; right: string };
type QuestionForm = {
    subject_id: string | number;
    teaching_assignment_id: string | number;
    type: 'multiple_choice' | 'essay' | 'true_false' | 'matching';
    jenjang: string;
    curriculum: string;
    prompt: string;
    indicator_text: string;
    cognitive_level: string;
    kko_term_id: string | number;
    kko_text: string;
    category_names: string;
    tag_names: string;
    options: string[];
    matching_pairs: Pair[];
    essay_rubric: string;
    correct_answer: string;
    explanation: string;
    points: number;
};

const props = defineProps<{
    questions: { data: any[]; links: { url: string | null; label: string; active: boolean }[]; total: number };
    filters: Record<string, string>;
    subjects: { id: number; code: string; name: string }[];
    assignments: { id: number; subject_id: number; subject_name: string; subject_code: string; class_name: string }[];
    categories: { id: number; name: string }[];
    tags: { id: number; name: string }[];
    kko_terms: { id: number; subject_id: number | null; term: string; cognitive_level: string }[];
    taxonomy_stats: { categories: number; tags: number; jenjang: Record<string, number>; curriculum: Record<string, number>; cognitive_level: Record<string, number>; categories_distribution: { name: string; total: number }[] };
    capabilities: { manage_all: boolean };
}>();

const showForm = ref(false);
const editing = ref<number | null>(null);
const filterForm = ref({ ...props.filters });
const blank = (): QuestionForm => ({
    subject_id: '', teaching_assignment_id: '', type: 'multiple_choice', jenjang: '', curriculum: 'merdeka',
    prompt: '', indicator_text: '', cognitive_level: '', kko_term_id: '', kko_text: '', category_names: '', tag_names: '', options: ['', ''], matching_pairs: [{ left: '', right: '' }, { left: '', right: '' }],
    essay_rubric: '', correct_answer: '', explanation: '', points: 1,
});
const form = useForm<QuestionForm>(blank());
const editingQuestion = computed(() => props.questions.data.find((q) => q.id === editing.value));
const hasTypeFields = computed(() => form.type);
const visibleKkoTerms = computed(() => props.kko_terms.filter((term) => term.subject_id === null || !form.subject_id || Number(term.subject_id) === Number(form.subject_id)));

function selectKkoByText() {
    const term = visibleKkoTerms.value.find((item) => item.term.toLocaleLowerCase() === form.kko_text.trim().toLocaleLowerCase());
    form.kko_term_id = term?.id ?? '';
    if (term) form.cognitive_level = term.cognitive_level;
}

function applyFilters() {
    router.get('/penilaian/bank-soal', filterForm.value, { preserveState: true, preserveScroll: true, replace: true });
}

function openCreate() {
    editing.value = null;
    form.clearErrors();
    form.defaults(blank());
    form.reset();
    showForm.value = true;
}

function openEdit(question: any) {
    editing.value = question.id;
    form.clearErrors();
    form.defaults({
        subject_id: question.subject_id ?? '', teaching_assignment_id: question.teaching_assignment_id ?? '',
        type: question.type, jenjang: question.jenjang ?? '', curriculum: question.curriculum ?? 'merdeka',
        prompt: question.prompt, indicator_text: question.indicator_text ?? '', cognitive_level: question.cognitive_level ?? '',
        kko_term_id: question.kko_term_id ?? '', kko_text: question.kko_text ?? '',
        category_names: (question.categories ?? []).map((category: any) => category.name).join(', '),
        tag_names: (question.tags ?? []).map((tag: any) => tag.name).join(', '),
        options: question.options ?? ['', ''], matching_pairs: question.matching_pairs ?? [{ left: '', right: '' }, { left: '', right: '' }],
        essay_rubric: question.essay_rubric ?? '', correct_answer: question.correct_answer ?? '', explanation: question.explanation ?? '', points: Number(question.points ?? 1),
    });
    form.reset();
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editing.value = null;
    form.clearErrors();
}

function submit() {
    const payload = {
        ...form.data(),
        subject_id: form.subject_id || null,
        teaching_assignment_id: form.teaching_assignment_id || null,
        kko_term_id: form.kko_term_id || null,
        category_names: form.category_names.split(',').map((name) => name.trim()).filter(Boolean),
        tag_names: form.tag_names.split(',').map((name) => name.trim()).filter(Boolean),
        options: form.type === 'multiple_choice' ? form.options.filter((option) => option.trim() !== '') : null,
        matching_pairs: form.type === 'matching' ? form.matching_pairs.filter((pair) => pair.left.trim() && pair.right.trim()) : null,
        essay_rubric: form.type === 'essay' ? form.essay_rubric : null,
        correct_answer: form.type === 'multiple_choice' || form.type === 'true_false' ? form.correct_answer : null,
    };

    if (editing.value) {
        form.transform(() => payload).put(`/penilaian/bank-soal/${editing.value}`, { preserveScroll: true, onSuccess: closeForm });
    } else {
        form.transform(() => payload).post('/penilaian/bank-soal', { preserveScroll: true, onSuccess: closeForm });
    }
}

function addOption() {
    if (form.options.length < 8) form.options.push('');
}
function removeOption(index: number) {
    if (form.options.length > 2) form.options.splice(index, 1);
}
function addPair() {
    if (form.matching_pairs.length < 10) form.matching_pairs.push({ left: '', right: '' });
}
function removePair(index: number) {
    if (form.matching_pairs.length > 2) form.matching_pairs.splice(index, 1);
}
function action(path: string, label: string) {
    if (window.confirm(label)) router.post(path, {}, { preserveScroll: true });
}
function typeLabel(type: string) {
    return ({ multiple_choice: 'Pilihan ganda', essay: 'Esai', true_false: 'Benar / salah', matching: 'Menjodohkan' } as Record<string, string>)[type] ?? type;
}
</script>

<template>
    <Head title="Bank Soal" />
    <AppLayout title="Bank Soal">
        <main class="view space-y-6">
            <section class="hero-card bank-hero">
                <div>
                    <p class="eyebrow">Asesmen · Katalog soal</p>
                    <h1>Bank Soal</h1>
                    <p>Susun butir soal terstruktur dan gunakan kembali pada ujian. Setiap perubahan tersimpan sebagai versi baru.</p>
                </div>
                <div class="hero-actions">
                    <Link href="/ujian" class="secondary-button">Ujian & CBT</Link>
                    <button class="primary-button" @click="openCreate">+ Tambah soal</button>
                </div>
            </section>

            <section class="bank-stats">
                <div class="panel stat-card"><span>Hasil filter</span><strong>{{ questions.total }}</strong></div>
                <div class="panel stat-card"><span>Mapel tersedia</span><strong>{{ subjects.length }}</strong></div>
                <div class="panel stat-card"><span>Format soal</span><strong>4</strong></div>
            </section>

            <section class="taxonomy-summary" aria-label="Ringkasan klasifikasi bank soal">
                <div class="panel taxonomy-card"><h2>Kategori & tag sekolah</h2><p>{{ taxonomy_stats.categories }} kategori · {{ taxonomy_stats.tags }} tag</p></div>
                <div class="panel taxonomy-card"><h2>Komposisi kognitif</h2><p>L1 {{ taxonomy_stats.cognitive_level.L1 ?? 0 }} · L2 {{ taxonomy_stats.cognitive_level.L2 ?? 0 }} · L3 {{ taxonomy_stats.cognitive_level.L3 ?? 0 }}</p></div>
                <div class="panel taxonomy-card"><h2>Jenjang & kurikulum</h2><p>SD {{ taxonomy_stats.jenjang.SD ?? 0 }} · SMP {{ taxonomy_stats.jenjang.SMP ?? 0 }} · SMA {{ taxonomy_stats.jenjang.SMA ?? 0 }}</p><p>Merdeka {{ taxonomy_stats.curriculum.merdeka ?? 0 }} · KBC {{ taxonomy_stats.curriculum.kbc ?? 0 }} · Gabungan {{ taxonomy_stats.curriculum.both ?? 0 }}</p></div>
                <div v-if="taxonomy_stats.categories_distribution.length" class="panel taxonomy-card"><h2>Kategori terbanyak</h2><p v-for="category in taxonomy_stats.categories_distribution" :key="category.name">{{ category.name }} · {{ category.total }} soal</p></div>
            </section>

            <section class="panel filters-panel">
                <form class="filters-grid" @submit.prevent="applyFilters">
                    <label class="search-field">Cari soal<input v-model="filterForm.search" placeholder="Cari teks soal atau indikator" /></label>
                    <label>Jenis<select v-model="filterForm.type"><option value="">Semua jenis</option><option value="multiple_choice">Pilihan ganda</option><option value="essay">Esai</option><option value="true_false">Benar / salah</option><option value="matching">Menjodohkan</option></select></label>
                    <label>Mata pelajaran<select v-model="filterForm.subject_id"><option value="">Semua mapel</option><option v-for="s in subjects" :key="s.id" :value="String(s.id)">{{ s.code }} · {{ s.name }}</option></select></label>
                    <label>Jenjang<select v-model="filterForm.jenjang"><option value="">Semua</option><option value="SD">SD</option><option value="SMP">SMP</option><option value="SMA">SMA</option></select></label>
                    <label>Kurikulum<select v-model="filterForm.curriculum"><option value="">Semua</option><option value="merdeka">Merdeka</option><option value="kbc">KBC</option><option value="both">Merdeka & KBC</option></select></label>
                    <label>Level kognitif<select v-model="filterForm.cognitive_level"><option value="">Semua</option><option value="L1">L1 · LOTS</option><option value="L2">L2 · MOTS</option><option value="L3">L3 · HOTS</option></select></label>
                    <label>Kategori<select v-model="filterForm.category_id"><option value="">Semua kategori</option><option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></select></label>
                    <label>Tag<select v-model="filterForm.tag_id"><option value="">Semua tag</option><option v-for="tag in tags" :key="tag.id" :value="String(tag.id)">{{ tag.name }}</option></select></label>
                    <label>Status<select v-model="filterForm.status"><option value="active">Aktif</option><option value="archived">Arsip</option><option value="all">Semua</option></select></label>
                    <button class="primary-button filter-button">Terapkan filter</button>
                </form>
            </section>

            <section class="panel question-list">
                <div class="list-heading"><div><h2>Butir soal</h2><p>{{ questions.total }} soal ditemukan</p></div></div>
                <article v-for="question in questions.data" :key="question.id" class="question-row">
                    <div class="question-main">
                        <div class="badges"><span class="badge" :class="question.is_active ? 'active' : 'archived'">{{ question.is_active ? 'Aktif' : 'Arsip' }}</span><span class="badge type-badge">{{ typeLabel(question.type) }}</span><span v-if="question.cognitive_level" class="badge level-badge">{{ question.cognitive_level }}</span></div>
                        <h3>{{ question.prompt }}</h3>
                        <p class="question-meta">{{ question.subject_code ? `${question.subject_code} · ${question.subject_name}` : 'Bank umum' }}<span v-if="question.jenjang"> · {{ question.jenjang }}</span><span v-if="question.curriculum"> · {{ question.curriculum }}</span> · {{ question.points }} poin · versi {{ question.current_version }}</p>
                        <p v-if="question.indicator_text" class="indicator">Indikator: {{ question.indicator_text }}</p>
                        <div v-if="question.categories?.length || question.tags?.length" class="taxonomy-badges"><span v-for="category in question.categories" :key="`c-${category.id}`" class="taxonomy-chip">{{ category.name }}</span><span v-for="tag in question.tags" :key="`t-${tag.id}`" class="tag-chip">#{{ tag.name }}</span></div>
                        <details class="question-details"><summary>Lihat rincian dan kunci</summary><div v-if="question.options?.length" class="detail-values"><span v-for="(option, index) in question.options" :key="index">{{ String.fromCharCode(65 + Number(index)) }}. {{ option }}<b v-if="option === question.correct_answer"> · Kunci</b></span></div><p v-if="question.type === 'true_false'">Kunci: {{ question.correct_answer === 'true' ? 'Benar' : 'Salah' }}</p><div v-if="question.matching_pairs?.length" class="detail-values"><span v-for="pair in question.matching_pairs" :key="pair.left">{{ pair.left }} ↔ {{ pair.right }}</span></div><p v-if="question.essay_rubric"><b>Rubrik:</b> {{ question.essay_rubric }}</p><p v-if="question.explanation"><b>Penjelasan:</b> {{ question.explanation }}</p></details>
                    </div>
                    <div class="row-actions">
                        <button v-if="question.is_active" class="text-button" @click="openEdit(question)">Ubah</button>
                        <button class="text-button" @click="action(`/penilaian/bank-soal/${question.id}/duplikasi`, 'Duplikasi soal ini sebagai butir baru?')">Duplikasi</button>
                        <button v-if="question.is_active" class="text-button danger-text" @click="action(`/penilaian/bank-soal/${question.id}/arsip`, 'Arsipkan soal ini? Soal pada ujian yang sudah dibuat tetap tersedia.')">Arsipkan</button>
                        <button v-else class="text-button" @click="action(`/penilaian/bank-soal/${question.id}/pulihkan`, 'Pulihkan soal ini ke katalog aktif?')">Pulihkan</button>
                    </div>
                </article>
                <p v-if="!questions.data.length" class="empty-state">Belum ada soal untuk filter ini. Tambahkan butir soal pertama untuk memulai.</p>
                <nav v-if="questions.links.length > 3" class="pagination" aria-label="Halaman bank soal">
                    <Link v-for="(link, index) in questions.links" :key="index" :href="link.url ?? '# '" :class="{ current: link.active, disabled: !link.url }" v-html="link.label" />
                </nav>
            </section>
        </main>

        <div v-if="showForm" class="modal-backdrop" @click.self="closeForm">
            <form class="modal-card question-form" @submit.prevent="submit">
                <div class="form-heading"><div><p class="eyebrow">{{ editing ? `Simpan sebagai versi ${Number(editingQuestion?.current_version ?? 0) + 1}` : 'Butir baru' }}</p><h2>{{ editing ? 'Ubah soal' : 'Tambah soal' }}</h2></div><button type="button" class="close-button" aria-label="Tutup" @click="closeForm">×</button></div>
                <div class="form-grid">
                    <label>Mata pelajaran<select v-model="form.subject_id"><option value="">Bank umum / belum ditentukan</option><option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.code }} · {{ s.name }}</option></select></label>
                    <label>Penugasan mengajar (opsional)<select v-model="form.teaching_assignment_id"><option value="">Tidak diikat ke kelas tertentu</option><option v-for="a in assignments" :key="a.id" :value="a.id">{{ a.subject_code }} · {{ a.subject_name }} — {{ a.class_name }}</option></select></label>
                    <label>Jenis soal<select v-model="form.type"><option value="multiple_choice">Pilihan ganda</option><option value="essay">Esai</option><option value="true_false">Benar / salah</option><option value="matching">Menjodohkan</option></select></label>
                    <label>Jenjang<select v-model="form.jenjang" required><option value="">Pilih jenjang</option><option value="SD">SD</option><option value="SMP">SMP</option><option value="SMA">SMA</option></select></label>
                    <label>Kurikulum<select v-model="form.curriculum" required><option value="merdeka">Merdeka</option><option value="kbc">KBC</option><option value="both">Merdeka & KBC</option></select></label>
                    <label>Level kognitif<select v-model="form.cognitive_level"><option value="">Belum diklasifikasikan</option><option value="L1">L1 · LOTS (C1–C2)</option><option value="L2">L2 · MOTS (C3–C4)</option><option value="L3">L3 · HOTS (C5–C6)</option></select></label>
                    <label class="span-all">Teks soal<textarea v-model="form.prompt" rows="4" required maxlength="10000" /></label>
                    <label class="span-all">Indikator soal<textarea v-model="form.indicator_text" rows="2" maxlength="5000" placeholder="Kompetensi atau indikator yang diukur" /></label>
                    <label>KKO (kata kerja operasional)<input v-model="form.kko_text" list="question-kko-terms" maxlength="180" placeholder="Cari, misalnya: menganalisis" @change="selectKkoByText" /><datalist id="question-kko-terms"><option v-for="term in visibleKkoTerms" :key="term.id" :value="term.term">{{ term.cognitive_level }}</option></datalist></label>
                    <label>Kategori (pisahkan dengan koma)<input v-model="form.category_names" maxlength="1000" placeholder="Contoh: Aljabar, Persamaan" /></label>
                    <label>Tag (pisahkan dengan koma)<input v-model="form.tag_names" maxlength="1000" placeholder="Contoh: remedial, HOTS" /></label>
                    <label>Poin default<input v-model.number="form.points" type="number" min="0.1" max="100" step="0.1" required /></label>

                    <div v-if="hasTypeFields === 'multiple_choice'" class="span-all type-fields">
                        <h3>Opsi jawaban</h3>
                        <div v-for="(option, index) in form.options" :key="index" class="option-row"><span>{{ String.fromCharCode(65 + index) }}</span><input v-model="form.options[index]" :aria-label="`Opsi ${index + 1}`" maxlength="1000" required /><button v-if="form.options.length > 2" type="button" class="icon-button" aria-label="Hapus opsi" @click="removeOption(index)">×</button></div>
                        <button v-if="form.options.length < 8" type="button" class="text-button" @click="addOption">+ Tambah opsi</button>
                        <label>Kunci jawaban<select v-model="form.correct_answer" required><option value="">Pilih kunci</option><option v-for="option in form.options.filter((x) => x.trim())" :key="option" :value="option">{{ option }}</option></select></label>
                    </div>
                    <label v-if="hasTypeFields === 'true_false'" class="span-all">Kunci jawaban<select v-model="form.correct_answer" required><option value="">Pilih jawaban benar</option><option value="true">Benar</option><option value="false">Salah</option></select></label>
                    <label v-if="hasTypeFields === 'essay'" class="span-all">Rubrik penilaian<textarea v-model="form.essay_rubric" rows="3" maxlength="5000" placeholder="Kriteria atau panduan penilaian esai" /></label>
                    <div v-if="hasTypeFields === 'matching'" class="span-all type-fields"><h3>Pasangan yang benar</h3><div v-for="(pair, index) in form.matching_pairs" :key="index" class="pair-row"><input v-model="pair.left" :aria-label="`Bagian kiri ${index + 1}`" placeholder="Pernyataan" maxlength="1000" required /><span>↔</span><input v-model="pair.right" :aria-label="`Bagian kanan ${index + 1}`" placeholder="Pasangan jawaban" maxlength="1000" required /><button v-if="form.matching_pairs.length > 2" type="button" class="icon-button" aria-label="Hapus pasangan" @click="removePair(index)">×</button></div><button v-if="form.matching_pairs.length < 10" type="button" class="text-button" @click="addPair">+ Tambah pasangan</button></div>
                    <label class="span-all">Penjelasan jawaban<textarea v-model="form.explanation" rows="2" maxlength="5000" placeholder="Opsional" /></label>
                </div>
                <p v-if="form.hasErrors" class="form-error">{{ Object.values(form.errors)[0] }}</p>
                <div class="modal-actions"><button type="button" class="secondary-button" @click="closeForm">Batal</button><button class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : editing ? 'Simpan versi baru' : 'Simpan soal' }}</button></div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.bank-hero,.hero-actions,.list-heading,.form-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem}.hero-actions{flex-wrap:wrap}.bank-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.stat-card{padding:1rem 1.2rem;display:grid;gap:.3rem}.stat-card span,.question-meta,.indicator,.list-heading p{color:#64748b;font-size:.82rem}.stat-card strong{font-size:1.7rem}.filters-panel{padding:1rem}.filters-grid{display:grid;grid-template-columns:2fr repeat(3,minmax(130px,1fr));gap:.8rem;align-items:end}.filters-grid label,.question-form label{display:grid;gap:.35rem;font-size:.8rem;font-weight:650}.filters-grid input,.filters-grid select,.question-form input,.question-form select,.question-form textarea{width:100%;border:1px solid #cbd5e1;border-radius:.6rem;padding:.62rem .7rem;background:#fff;color:#0f172a}.filter-button{min-height:42px}.question-list{overflow:hidden}.list-heading{padding:1rem 1.2rem;border-bottom:1px solid #e2e8f0}.list-heading h2{font-weight:800}.question-row{padding:1.1rem 1.2rem;display:flex;justify-content:space-between;gap:1rem;border-bottom:1px solid #e2e8f0}.question-main{min-width:0}.question-main h3{font-weight:750;margin:.55rem 0 .25rem;white-space:pre-wrap;overflow-wrap:anywhere}.question-meta,.indicator{margin-top:.25rem}.question-details{margin-top:.55rem;color:#475569;font-size:.8rem}.question-details summary{cursor:pointer;font-weight:700}.detail-values{display:grid;gap:.2rem;margin:.4rem 0}.badges,.row-actions{display:flex;align-items:center;gap:.45rem;flex-wrap:wrap}.badge{border-radius:999px;padding:.2rem .55rem;font-size:.68rem;font-weight:750;background:#eef2ff;color:#4338ca}.badge.active{background:#dcfce7;color:#166534}.badge.archived{background:#f1f5f9;color:#475569}.badge.type-badge{background:#f1f5f9;color:#334155}.badge.level-badge{background:#ffedd5;color:#9a3412}.row-actions{justify-content:flex-end;align-self:center;flex-shrink:0}.danger-text{color:#b91c1c}.pagination{padding:1rem;display:flex;justify-content:center;gap:.35rem}.pagination :deep(a){padding:.4rem .65rem;border:1px solid #e2e8f0;border-radius:.4rem;font-size:.8rem}.pagination :deep(a.current){background:#4f46e5;color:#fff;border-color:#4f46e5}.pagination :deep(a.disabled){opacity:.45;pointer-events:none}.modal-backdrop{position:fixed;inset:0;z-index:90;display:grid;place-items:center;padding:1rem;background:#0f172a99}.question-form{width:min(900px,96vw);max-height:92vh;overflow:auto;display:grid;gap:1rem;padding:1.35rem;background:#fff;border-radius:1rem}.form-heading h2{font-size:1.25rem;font-weight:850}.close-button,.icon-button{border:0;background:transparent;font-size:1.5rem;line-height:1;cursor:pointer}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}.span-all{grid-column:1/-1}.type-fields{display:grid;gap:.65rem;padding:1rem;border:1px solid #e2e8f0;border-radius:.75rem}.type-fields h3{font-weight:800}.option-row{display:grid;grid-template-columns:2rem 1fr 2rem;align-items:center;gap:.5rem}.option-row span{text-align:center;font-weight:750}.pair-row{display:grid;grid-template-columns:1fr auto 1fr 2rem;align-items:center;gap:.5rem}.modal-actions{display:flex;justify-content:flex-end;gap:.6rem}.form-error{color:#b91c1c;font-size:.85rem}@media(max-width:850px){.filters-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.search-field,.filter-button{grid-column:1/-1}}@media(max-width:600px){.bank-hero,.question-row{align-items:stretch;flex-direction:column}.bank-stats{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.span-all,.search-field,.filter-button{grid-column:auto}.row-actions{justify-content:flex-start}.pair-row{grid-template-columns:1fr auto 1fr}.pair-row button{grid-column:3;justify-self:end}}
.taxonomy-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.taxonomy-card{padding:1rem 1.2rem}.taxonomy-card h2{font-weight:800}.taxonomy-card p{color:#64748b;font-size:.82rem}.taxonomy-badges{display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.45rem}.taxonomy-chip,.tag-chip{border-radius:999px;padding:.16rem .5rem;font-size:.68rem;font-weight:700;background:#e0f2fe;color:#075985}.tag-chip{background:#f1f5f9;color:#475569}@media(max-width:600px){.taxonomy-summary{grid-template-columns:1fr}}
</style>
