<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import RowActionButton from '@/components/RowActionButton.vue';

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
    source: string;
    class_label: string;
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
    prompt: '', indicator_text: '', cognitive_level: '', kko_term_id: '', kko_text: '', category_names: '', tag_names: '', options: ['', ''], matching_pairs: [{ left: '', right: '' }],
    essay_rubric: '', correct_answer: '', explanation: '', source: '', class_label: '', points: 1,
});
const form = useForm<QuestionForm>(blank());
const importForm = useForm<{ file: File | null; jenjang: string; curriculum: string }>({ file: null, jenjang: 'SMP', curriculum: 'merdeka' });
const exportUrl = computed(() => `/penilaian/bank-soal/export?${new URLSearchParams(Object.entries(filterForm.value).filter(([, value]) => value !== '' && value !== null).map(([key, value]) => [key, String(value)]))}`);
const activeFilterCount = computed(() => Object.entries(filterForm.value).filter(([key, value]) => value !== '' && value !== null && value !== undefined && (key !== 'status' || value !== 'active')).length);
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

function resetFilters() {
    filterForm.value = {
        search: '', type: '', subject_id: '', jenjang: '', curriculum: '', cognitive_level: '', class_label: '', usage: '',
        category_id: '', tag_id: '', status: 'active',
    };
    applyFilters();
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
        source: question.source ?? '', class_label: question.class_label ?? '',
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
    if (!editing.value && props.questions.data.some(q => q.prompt.trim().toLocaleLowerCase() === form.prompt.trim().toLocaleLowerCase() && q.type === form.type) && !window.confirm('Soal dengan teks dan jenis yang sama sudah ada. Tetap simpan sebagai butir baru?')) return;
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

function submitImport() {
    importForm.post('/penilaian/bank-soal/import/preview', { forceFormData: true });
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
    if (form.matching_pairs.length > 1) form.matching_pairs.splice(index, 1);
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
        <div class="view bank-page space-y-6">
            <header class="bank-hero">
                <div class="bank-hero-copy">
                    <div class="eyebrow-row"><p class="eyebrow">Asesmen · Katalog soal</p><span class="hero-status"><i aria-hidden="true" /> Versi tersimpan aman</span></div>
                    <h1>Bank Soal</h1>
                    <p>Susun butir soal terstruktur dan gunakan kembali pada ujian. Setiap perubahan tersimpan sebagai versi baru.</p>
                </div>
                <div class="hero-actions">
                    <div class="hero-link-actions"><Link href="/ujian" class="secondary-button">Ujian & CBT</Link><Link href="/penilaian/bank-soal/template" class="secondary-button">Unduh template</Link><a :href="exportUrl" class="secondary-button">Ekspor XLSX</a></div>
                    <form class="import-control" aria-label="Impor soal" @submit.prevent="submitImport">
                        <label>Jenjang impor<select v-model="importForm.jenjang"><option>SD</option><option>SMP</option><option>SMA</option></select></label><label>Kurikulum impor<select v-model="importForm.curriculum"><option value="merdeka">Merdeka</option><option value="kbc">KBC</option><option value="both">Keduanya</option></select></label><label class="file-picker">Pilih XLSX / CSV<input type="file" accept=".xlsx,.csv" @change="importForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null" /></label>
                        <button type="submit" class="secondary-button" :disabled="importForm.processing || !importForm.file">{{ importForm.processing ? 'Memeriksa…' : 'Preview impor' }}</button>
                        <small v-if="importForm.errors.file" class="form-error">{{ importForm.errors.file }}</small>
                    </form>
                    <button type="button" class="primary-button" @click="openCreate">+ Tambah soal</button>
                </div>
            </header>

            <section class="bank-stats" aria-label="Ringkasan bank soal">
                <article class="panel stat-card stat-card-primary"><div class="stat-card-top"><span class="stat-label">Soal sesuai filter</span><span class="stat-index" aria-hidden="true">01</span></div><strong>{{ questions.total }}</strong><small>Butir aktif dalam cakupan Anda</small></article>
                <article class="panel stat-card"><div class="stat-card-top"><span class="stat-label">Mapel tersedia</span><span class="stat-index" aria-hidden="true">02</span></div><strong>{{ subjects.length }}</strong><small>Siap dipakai untuk asesmen</small></article>
                <article class="panel stat-card"><div class="stat-card-top"><span class="stat-label">Format soal</span><span class="stat-index" aria-hidden="true">03</span></div><strong>4</strong><small>Pilihan ganda hingga menjodohkan</small></article>
            </section>

            <section class="taxonomy-summary" aria-label="Ringkasan klasifikasi bank soal">
                <article class="panel taxonomy-card"><div class="taxonomy-heading"><span class="section-kicker">Klasifikasi</span><span class="taxonomy-mark">A</span></div><h2>Kategori & tag sekolah</h2><p>{{ taxonomy_stats.categories }} kategori · {{ taxonomy_stats.tags }} tag tersedia untuk penyaringan.</p></article>
                <article class="panel taxonomy-card"><div class="taxonomy-heading"><span class="section-kicker">Kualitas soal</span><span class="taxonomy-mark">B</span></div><h2>Komposisi kognitif</h2><p>L1 {{ taxonomy_stats.cognitive_level.L1 ?? 0 }} · L2 {{ taxonomy_stats.cognitive_level.L2 ?? 0 }} · L3 {{ taxonomy_stats.cognitive_level.L3 ?? 0 }}</p></article>
                <article class="panel taxonomy-card"><div class="taxonomy-heading"><span class="section-kicker">Konteks ajar</span><span class="taxonomy-mark">C</span></div><h2>Jenjang & kurikulum</h2><p>SD {{ taxonomy_stats.jenjang.SD ?? 0 }} · SMP {{ taxonomy_stats.jenjang.SMP ?? 0 }} · SMA {{ taxonomy_stats.jenjang.SMA ?? 0 }}</p><p>Merdeka {{ taxonomy_stats.curriculum.merdeka ?? 0 }} · KBC {{ taxonomy_stats.curriculum.kbc ?? 0 }} · Gabungan {{ taxonomy_stats.curriculum.both ?? 0 }}</p></article>
                <article v-if="taxonomy_stats.categories_distribution.length" class="panel taxonomy-card taxonomy-card-featured"><div class="taxonomy-heading"><span class="section-kicker">Paling sering dipakai</span><span class="taxonomy-mark">D</span></div><h2>Kategori terbanyak</h2><p v-for="category in taxonomy_stats.categories_distribution" :key="category.name"><b>{{ category.name }}</b><span>{{ category.total }} soal</span></p></article>
            </section>

            <section class="panel filters-panel">
                <div class="filters-heading"><div><p class="eyebrow">Penyaringan katalog</p><h2>Temukan soal yang tepat</h2><p>Gunakan satu atau beberapa filter untuk menyusun paket asesmen lebih cepat.</p></div><button v-if="activeFilterCount" type="button" class="text-button reset-button" @click="resetFilters">Reset filter <span>{{ activeFilterCount }}</span></button></div>
                <form class="filters-grid" @submit.prevent="applyFilters">
                    <label class="search-field">Cari soal<input v-model="filterForm.search" class="search-control" type="search" placeholder="Cari teks soal atau indikator" /></label>
                    <label>Jenis<select v-model="filterForm.type"><option value="">Semua jenis</option><option value="multiple_choice">Pilihan ganda</option><option value="essay">Esai</option><option value="true_false">Benar / salah</option><option value="matching">Menjodohkan</option></select></label>
                    <label>Mata pelajaran<select v-model="filterForm.subject_id"><option value="">Semua mapel</option><option v-for="s in subjects" :key="s.id" :value="String(s.id)">{{ s.code }} · {{ s.name }}</option></select></label>
                    <label>Jenjang<select v-model="filterForm.jenjang"><option value="">Semua</option><option value="SD">SD</option><option value="SMP">SMP</option><option value="SMA">SMA</option></select></label>
                    <label>Kurikulum<select v-model="filterForm.curriculum"><option value="">Semua</option><option value="merdeka">Merdeka</option><option value="kbc">KBC</option><option value="both">Merdeka & KBC</option></select></label>
                    <label>Kelas<input v-model="filterForm.class_label" placeholder="Semua kelas" /></label><label>Pemakaian<select v-model="filterForm.usage"><option value="">Semua</option><option value="used">Sudah dipakai</option><option value="unused">Belum dipakai</option></select></label><label>Level kognitif<select v-model="filterForm.cognitive_level"><option value="">Semua</option><option v-for="(label, level) in {C1:'Mengingat',C2:'Memahami',C3:'Menerapkan',C4:'Menganalisis',C5:'Mengevaluasi',C6:'Mencipta'}" :key="level" :value="level">{{ level }} · {{ label }}</option><option value="L1">L1 · LOTS</option><option value="L2">L2 · MOTS</option><option value="L3">L3 · HOTS</option></select></label>
                    <label>Kategori<select v-model="filterForm.category_id"><option value="">Semua kategori</option><option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></select></label>
                    <label>Tag<select v-model="filterForm.tag_id"><option value="">Semua tag</option><option v-for="tag in tags" :key="tag.id" :value="String(tag.id)">{{ tag.name }}</option></select></label>
                    <label>Status<select v-model="filterForm.status"><option value="active">Aktif</option><option value="archived">Arsip</option><option value="all">Semua</option></select></label>
                    <button type="submit" class="primary-button filter-button">Terapkan filter <span aria-hidden="true">→</span></button>
                </form>
            </section>

            <section class="panel question-list">
                <div class="list-heading"><div><p class="eyebrow">Katalog aktif</p><h2>Butir soal</h2><p>{{ questions.total }} soal ditemukan · pilih tindakan di sisi kanan setiap butir.</p></div><div class="list-meta"><span class="list-meta-dot" aria-hidden="true" /> Versi historis tetap aman</div></div>
                <article v-for="question in questions.data" :key="question.id" class="question-row">
                    <div class="question-main">
                        <div class="question-topline"><div class="badges"><span class="badge" :class="question.is_active ? 'active' : 'archived'">{{ question.is_active ? 'Aktif' : 'Arsip' }}</span><span class="badge type-badge">{{ typeLabel(question.type) }}</span><span v-if="question.cognitive_level" class="badge level-badge">{{ question.cognitive_level }}</span></div><span class="question-version">v{{ question.current_version }}</span></div>
                        <h3>{{ question.prompt }}</h3>
                        <p class="question-meta">{{ question.subject_code ? `${question.subject_code} · ${question.subject_name}` : 'Bank umum' }}<span v-if="question.jenjang"> · {{ question.jenjang }}</span><span v-if="question.curriculum"> · {{ question.curriculum }}</span> · {{ question.points }} poin</p>
                        <p v-if="question.indicator_text" class="indicator">Indikator: {{ question.indicator_text }}</p>
                        <div v-if="question.categories?.length || question.tags?.length" class="taxonomy-badges"><span v-for="category in question.categories" :key="`c-${category.id}`" class="taxonomy-chip">{{ category.name }}</span><span v-for="tag in question.tags" :key="`t-${tag.id}`" class="tag-chip">#{{ tag.name }}</span></div>
                        <details class="question-details"><summary>Lihat rincian dan kunci</summary><div v-if="question.options?.length" class="detail-values"><span v-for="(option, index) in question.options" :key="index">{{ String.fromCharCode(65 + Number(index)) }}. {{ option }}<b v-if="option === question.correct_answer"> · Kunci</b></span></div><p v-if="question.type === 'true_false'">Kunci: {{ question.correct_answer === 'true' ? 'Benar' : 'Salah' }}</p><div v-if="question.matching_pairs?.length" class="detail-values"><span v-for="pair in question.matching_pairs" :key="pair.left">{{ pair.left }} ↔ {{ pair.right }}</span></div><p v-if="question.essay_rubric"><b>Rubrik:</b> {{ question.essay_rubric }}</p><p v-if="question.explanation"><b>Penjelasan:</b> {{ question.explanation }}</p></details>
                    </div>
                    <div class="row-actions">
                        <RowActionButton v-if="question.is_active" action="edit" label="Ubah" @click="openEdit(question)" />
                        <button type="button" class="text-button" @click="action(`/penilaian/bank-soal/${question.id}/duplikasi`, 'Duplikasi soal ini sebagai butir baru?')">Duplikasi</button>
                        <Link class="text-button" :href="`/penilaian/berbagi-soal?type=question&id=${question.id}`">Bagikan</Link>
                        <RowActionButton v-if="question.is_active" action="archive" label="Arsipkan" :aria-label="`Arsipkan soal ${question.id}`" @click="action(`/penilaian/bank-soal/${question.id}/arsip`, 'Arsipkan soal ini? Soal pada ujian yang sudah dibuat tetap tersedia.')" />
                        <button v-else type="button" class="text-button" @click="action(`/penilaian/bank-soal/${question.id}/pulihkan`, 'Pulihkan soal ini ke katalog aktif?')">Pulihkan</button>
                    </div>
                </article>
                <div v-if="!questions.data.length" class="empty-state"><span class="empty-mark" aria-hidden="true">∅</span><div><strong>Belum ada soal untuk filter ini.</strong><p>Perluas pencarian atau tambahkan butir soal pertama untuk memulai.</p></div><button type="button" class="primary-button" @click="openCreate">+ Tambah soal</button></div>
                <nav v-if="questions.links.length > 3" class="pagination" aria-label="Halaman bank soal">
                    <Link v-for="(link, index) in questions.links" :key="index" :href="link.url ?? '# '" :class="{ current: link.active, disabled: !link.url }" v-html="link.label" />
                </nav>
            </section>
        </div>

        <div v-if="showForm" class="modal-backdrop" @click.self="closeForm">
            <form class="modal-card question-form" @submit.prevent="submit">
                <div class="form-heading"><div><p class="eyebrow">{{ editing ? `Simpan sebagai versi ${Number(editingQuestion?.current_version ?? 0) + 1}` : 'Butir baru' }}</p><h2>{{ editing ? 'Ubah soal' : 'Tambah soal' }}</h2></div><button type="button" class="close-button" aria-label="Tutup" @click="closeForm">×</button></div>
                <div class="form-grid">
                    <label>Mata pelajaran<select v-model="form.subject_id"><option value="">Bank umum / belum ditentukan</option><option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.code }} · {{ s.name }}</option></select></label>
                    <label>Penugasan mengajar (opsional)<select v-model="form.teaching_assignment_id"><option value="">Tidak diikat ke kelas tertentu</option><option v-for="a in assignments" :key="a.id" :value="a.id">{{ a.subject_code }} · {{ a.subject_name }} — {{ a.class_name }}</option></select></label>
                    <label>Jenis soal<select v-model="form.type"><option value="multiple_choice">Pilihan ganda</option><option value="essay">Esai</option><option value="true_false">Benar / salah</option><option value="matching">Menjodohkan</option></select></label>
                    <label>Jenjang<select v-model="form.jenjang" required><option value="">Pilih jenjang</option><option value="SD">SD</option><option value="SMP">SMP</option><option value="SMA">SMA</option></select></label>
                    <label>Kurikulum<select v-model="form.curriculum" required><option value="merdeka">Merdeka</option><option value="kbc">KBC</option><option value="both">Merdeka & KBC</option></select></label>
                    <label>Level kognitif<select v-model="form.cognitive_level"><option value="">Belum diklasifikasikan</option><option v-for="(label, level) in {C1:'Mengingat',C2:'Memahami',C3:'Menerapkan',C4:'Menganalisis',C5:'Mengevaluasi',C6:'Mencipta'}" :key="level" :value="level">{{ level }} · {{ label }}</option><option value="L1">L1 · LOTS (C1–C2)</option><option value="L2">L2 · MOTS (C3–C4)</option><option value="L3">L3 · HOTS (C5–C6)</option></select></label>
                    <label class="span-all">Teks soal<textarea v-model="form.prompt" rows="4" required maxlength="10000" /></label>
                    <label class="span-all">Indikator soal<textarea v-model="form.indicator_text" rows="2" maxlength="5000" placeholder="Kompetensi atau indikator yang diukur" /></label>
                    <label>Kelas / rombel<input v-model="form.class_label" maxlength="120" placeholder="Contoh: VII A" /></label><label>Sumber<input v-model="form.source" maxlength="500" placeholder="Buku atau referensi soal" /></label><label>KKO (kata kerja operasional)<input v-model="form.kko_text" list="question-kko-terms" maxlength="180" placeholder="Cari, misalnya: menganalisis" @change="selectKkoByText" /><datalist id="question-kko-terms"><option v-for="term in visibleKkoTerms" :key="term.id" :value="term.term">{{ term.cognitive_level }}</option></datalist></label>
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
                    <div v-if="hasTypeFields === 'matching'" class="span-all type-fields"><h3>Pasangan yang benar</h3><div v-for="(pair, index) in form.matching_pairs" :key="index" class="pair-row"><input v-model="pair.left" :aria-label="`Bagian kiri ${index + 1}`" placeholder="Pernyataan" maxlength="1000" required /><span>↔</span><input v-model="pair.right" :aria-label="`Bagian kanan ${index + 1}`" placeholder="Pasangan jawaban" maxlength="1000" required /><button v-if="form.matching_pairs.length > 1" type="button" class="icon-button" aria-label="Hapus pasangan" @click="removePair(index)">×</button></div><button v-if="form.matching_pairs.length < 10" type="button" class="text-button" @click="addPair">+ Tambah pasangan</button></div>
                    <label class="span-all">Penjelasan jawaban<textarea v-model="form.explanation" rows="2" maxlength="5000" placeholder="Opsional" /></label>
                </div>
                <p v-if="form.hasErrors" class="form-error">{{ Object.values(form.errors)[0] }}</p>
                <div class="modal-actions"><button type="button" class="secondary-button" @click="closeForm">Batal</button><button type="submit" class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : editing ? 'Simpan versi baru' : 'Simpan soal' }}</button></div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.bank-page{max-width:1440px;margin-right:auto;margin-left:auto}.bank-hero{align-items:stretch;padding:1.55rem 1.6rem 1.55rem 1.8rem}.bank-hero-copy{display:grid;align-content:center;min-width:0}.eyebrow-row{display:flex;align-items:center;flex-wrap:wrap;gap:.7rem}.eyebrow-row .eyebrow{margin:0}.hero-status{display:inline-flex;align-items:center;gap:.35rem;color:var(--ink-faint);font-size:.72rem;font-weight:700}.hero-status i,.list-meta-dot{width:7px;height:7px;display:inline-block;border-radius:50%;background:var(--green);box-shadow:0 0 0 4px var(--green-soft)}.bank-hero h1{max-width:650px}.hero-actions{max-width:650px;align-items:flex-end}.hero-link-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.55rem}.import-control{display:flex;align-items:center;justify-content:flex-end;gap:.4rem;flex-wrap:wrap}.file-picker{display:inline-flex;align-items:center;min-height:var(--control-height);padding:.55rem .75rem;color:var(--ink-soft);background:var(--surface);border:1px solid var(--line-strong);border-radius:var(--radius-sm);font-size:.8rem;cursor:pointer;transition:border-color .16s var(--ease),background-color .16s var(--ease)}.file-picker:hover{border-color:var(--primary);background:var(--primary-soft)}.file-picker input{position:absolute;width:1px;height:1px;opacity:0}.bank-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.stat-card{position:relative;display:grid;gap:.4rem;min-height:132px;padding:1.1rem 1.25rem;overflow:hidden}.stat-card::before{position:absolute;inset:0 auto 0 0;width:4px;background:var(--primary);content:''}.stat-card:nth-child(2)::before{background:var(--green)}.stat-card:nth-child(3)::before{background:var(--gold)}.stat-card-top{display:flex;align-items:center;justify-content:space-between;gap:.7rem}.stat-label{color:var(--ink-soft);font-size:.78rem;font-weight:700}.stat-index{color:var(--primary);font-size:.68rem;font-weight:800;letter-spacing:.08em}.stat-card strong{font-size:2rem;letter-spacing:-.045em;line-height:1}.stat-card small{max-width:24ch;color:var(--ink-faint);font-size:.74rem;line-height:1.45}.taxonomy-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}.taxonomy-card{min-height:148px;padding:1rem 1.15rem}.taxonomy-heading{display:flex;align-items:center;justify-content:space-between;gap:.7rem;margin-bottom:.7rem}.section-kicker{color:var(--primary);font-size:.68rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.taxonomy-mark{display:grid;width:25px;height:25px;place-items:center;border-radius:var(--radius-sm);color:var(--primary);background:var(--primary-soft);font-size:.72rem;font-weight:800}.taxonomy-card h2{margin:0 0 .35rem;font-size:.92rem;font-weight:800;letter-spacing:-.015em}.taxonomy-card p{display:flex;justify-content:space-between;gap:.7rem;margin:.2rem 0;color:var(--ink-soft);font-size:.78rem;line-height:1.5}.taxonomy-card-featured{border-color:color-mix(in srgb,var(--primary) 28%,var(--line))}.taxonomy-card-featured p b{color:var(--ink)}.taxonomy-card-featured p span{color:var(--primary);font-weight:800;white-space:nowrap}.taxonomy-badges{display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.45rem}.taxonomy-chip,.tag-chip{border-radius:var(--radius-pill);padding:.16rem .5rem;font-size:.68rem;font-weight:700;background:var(--blue-soft);color:var(--blue)}.tag-chip{background:var(--surface-strong);color:var(--ink-soft)}.filters-panel{padding:1.3rem 1.35rem}.filters-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem}.filters-heading .eyebrow{margin:0 0 .3rem}.filters-heading h2{margin:0;font-size:1.1rem;letter-spacing:-.02em}.filters-heading p:not(.eyebrow){margin:.25rem 0 0;color:var(--ink-soft);font-size:.78rem}.reset-button{display:inline-flex;align-items:center;gap:.45rem;white-space:nowrap}.reset-button span{display:grid;min-width:20px;height:20px;place-items:center;padding:0 .35rem;border-radius:var(--radius-pill);color:var(--primary-strong);background:var(--primary-soft);font-size:.65rem}.filters-grid{display:grid;grid-template-columns:minmax(220px,2fr) repeat(3,minmax(145px,1fr));gap:.8rem;align-items:end}.filters-grid .search-field{grid-column:span 2}.filters-grid label,.question-form label{display:grid;gap:.35rem;color:var(--ink-soft);font-size:.78rem;font-weight:700}.filters-grid input,.filters-grid select,.question-form input,.question-form select,.question-form textarea{width:100%;border:1px solid var(--line-strong);border-radius:var(--radius-sm);padding:.62rem .7rem;background:var(--surface);color:var(--ink)}.filter-button{min-height:42px}.question-list{overflow:hidden}.list-heading{align-items:flex-start;padding:1.15rem 1.35rem;border-bottom:1px solid var(--line)}.list-heading .eyebrow{margin:0 0 .25rem}.list-heading h2{margin:0;font-size:1.12rem;font-weight:800;letter-spacing:-.025em}.list-heading p:not(.eyebrow){margin:.25rem 0 0;color:var(--ink-soft);font-size:.78rem}.list-meta{display:inline-flex;align-items:center;gap:.55rem;padding:.45rem .65rem;border:1px solid var(--line);border-radius:var(--radius-pill);color:var(--ink-soft);font-size:.7rem;font-weight:700;white-space:nowrap}.question-row{display:grid;grid-template-columns:minmax(0,1fr) auto;padding:1.2rem 1.35rem;border-bottom:1px solid var(--line);transition:background-color .16s var(--ease)}.question-row:hover{background:var(--surface-subtle)}.question-main{min-width:0}.question-topline{display:flex;align-items:center;justify-content:space-between;gap:1rem}.badges,.row-actions{display:flex;align-items:center;gap:.45rem;flex-wrap:wrap}.badge{border-radius:var(--radius-pill);padding:.2rem .55rem;font-size:.68rem;font-weight:700;background:var(--primary-soft);color:var(--primary-strong)}.badge.active{background:var(--green-soft);color:var(--green)}.badge.archived{background:var(--surface-strong);color:var(--ink-soft)}.badge.type-badge{background:var(--surface-strong);color:var(--ink-soft)}.badge.level-badge{background:var(--gold-soft);color:var(--gold)}.question-version{color:var(--ink-faint);font-size:.7rem;font-weight:800}.question-main h3{max-width:76ch;margin:.65rem 0 .35rem;color:var(--ink);font-size:1rem;font-weight:800;line-height:1.45;white-space:pre-wrap;overflow-wrap:anywhere}.question-meta,.indicator{margin-top:.25rem;color:var(--ink-soft);font-size:.78rem;line-height:1.5}.indicator{padding-left:.7rem;border-left:2px solid var(--primary-soft)}.question-details{margin-top:.7rem;color:var(--ink-soft);font-size:.78rem}.question-details summary{display:inline-flex;cursor:pointer;color:var(--primary);font-weight:800}.detail-values{display:grid;gap:.2rem;margin:.5rem 0}.taxonomy-badges{display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.55rem}.taxonomy-chip,.tag-chip{border-radius:var(--radius-pill);padding:.16rem .5rem;font-size:.68rem;font-weight:700;background:var(--blue-soft);color:var(--blue)}.tag-chip{background:var(--surface-strong);color:var(--ink-soft)}.row-actions{justify-content:flex-end;align-self:start;padding-top:2.05rem;flex-shrink:0}.danger-text{color:var(--red)}.pagination{display:flex;justify-content:center;gap:.35rem;padding:1rem}.pagination :deep(a){padding:.4rem .65rem;border:1px solid var(--line);border-radius:var(--radius-sm);font-size:.8rem}.pagination :deep(a.current){background:var(--primary);color:#fff;border-color:var(--primary)}.pagination :deep(a.disabled){opacity:.45;pointer-events:none}.empty-state{display:flex;align-items:center;gap:.8rem;margin:0;padding:1.4rem 1.35rem;color:var(--ink-soft)}.empty-state strong{display:block;color:var(--ink);font-size:.88rem}.empty-state p{margin:.2rem 0 0;font-size:.78rem}.empty-mark{display:grid;width:36px;height:36px;place-items:center;flex:0 0 auto;border-radius:var(--radius-md);color:var(--primary);background:var(--primary-soft);font-size:1.4rem}.empty-state .primary-button{margin-left:auto}.modal-backdrop{position:fixed;inset:0;z-index:90;display:grid;place-items:center;padding:1rem;background:color-mix(in srgb,var(--ink) 62%,transparent)}.question-form{width:min(900px,96vw);max-height:92vh;overflow:auto;display:grid;gap:1rem;padding:1.35rem;color:var(--ink);background:var(--surface);border:1px solid var(--line);border-radius:var(--radius-lg);box-shadow:var(--shadow-md)}.form-heading h2{font-size:1.25rem;font-weight:800}.close-button,.icon-button{border:0;background:transparent;font-size:1.5rem;line-height:1;cursor:pointer}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}.span-all{grid-column:1/-1}.type-fields{display:grid;gap:.65rem;padding:1rem;background:var(--surface-subtle);border:1px solid var(--line);border-radius:var(--radius-md)}.type-fields h3{font-weight:800}.option-row{display:grid;grid-template-columns:2rem 1fr 2rem;align-items:center;gap:.5rem}.option-row span{text-align:center;font-weight:700}.pair-row{display:grid;grid-template-columns:1fr auto 1fr 2rem;align-items:center;gap:.5rem}.modal-actions{display:flex;justify-content:flex-end;gap:.6rem}.form-error{color:var(--red);font-size:.85rem}@media(max-width:1050px){.taxonomy-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.filters-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.filters-grid .search-field,.filter-button{grid-column:1/-1}}@media(max-width:760px){.bank-hero,.question-row{align-items:stretch;flex-direction:column}.bank-hero{padding:1.25rem}.hero-actions,.hero-link-actions,.import-control{justify-content:flex-start}.bank-stats{grid-template-columns:1fr}.list-heading{flex-direction:column}.list-meta{align-self:flex-start}.row-actions{justify-content:flex-start;padding-top:.5rem}.empty-state{align-items:flex-start;flex-wrap:wrap}.empty-state .primary-button{margin-left:0}}@media(max-width:600px){.taxonomy-summary{grid-template-columns:1fr}.filters-heading{align-items:stretch;flex-direction:column}.filters-grid{grid-template-columns:1fr}.filters-grid .search-field,.filter-button{grid-column:auto}.form-grid{grid-template-columns:1fr}.span-all{grid-column:auto}.pair-row{grid-template-columns:1fr auto 1fr}.pair-row button{grid-column:3;justify-self:end}}
@media(max-width:760px){.question-row{grid-template-columns:1fr}}
.bank-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:1.5rem;padding:0 0 .4rem;border-bottom:1px solid var(--line)}
.bank-hero h1{margin:.25rem 0;color:var(--ink);font-size:clamp(1.55rem,2.2vw,2rem);font-weight:800;letter-spacing:-.035em}
.bank-hero-copy>p{max-width:62ch;color:var(--ink-soft);font-size:.85rem;line-height:1.55}
.bank-hero .hero-actions{display:grid;justify-items:end;gap:.55rem;max-width:680px}
.question-form{border-radius:var(--radius-md)}
.question-form .form-heading{position:sticky;top:-1.35rem;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.35rem 0 .75rem;background:var(--surface);border-bottom:1px solid var(--line)}
.question-form .modal-actions{position:sticky;bottom:-1.35rem;z-index:1;padding:.75rem 0 .35rem;background:var(--surface);border-top:1px solid var(--line)}
@media(max-width:900px){.bank-hero{flex-direction:column}.bank-hero .hero-actions{justify-items:start;max-width:none}.hero-link-actions,.import-control{justify-content:flex-start}}
@media(max-width:600px){.bank-hero{padding:0 0 .75rem}.question-form{width:100vw;max-height:100dvh;height:100dvh;border-radius:0;padding:1rem}.modal-backdrop{padding:0}.question-form .form-heading{top:-1rem}.question-form .modal-actions{bottom:-1rem;flex-wrap:wrap}}
</style>
