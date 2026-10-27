<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import RowActionButton from '@/components/RowActionButton.vue';

type QuestionOption = {
    id: number;
    type: string;
    prompt: string;
    points: number | string;
    jenjang: string | null;
    curriculum: string | null;
    current_version: number;
    version_available: number | boolean | null;
    subject_name: string | null;
    subject_code: string | null;
};

type PackItemForm = {
    question_id: number;
    points: number;
    prompt: string;
    type: string;
    jenjang: string | null;
    curriculum: string | null;
    question_active: boolean;
};

type PackForm = {
    name: string;
    description: string;
    jenjang: string;
    curriculum: string;
    duration_minutes: number | '';
    randomize_questions: boolean;
    randomize_options: boolean;
    status: 'draft' | 'active';
    items: PackItemForm[];
};

const props = defineProps<{
    packs: { data: any[]; links: { url: string | null; label: string; active: boolean }[]; total: number };
    filters: Record<string, string>;
    question_options: QuestionOption[];
    capabilities: { manage_all: boolean };
}>();

const showForm = ref(false);
const editing = ref<number | null>(null);
const questionSearch = ref('');
const packError = ref('');
const filterForm = ref({ ...props.filters });
const blank = (): PackForm => ({
    name: '', description: '', jenjang: '', curriculum: 'merdeka', duration_minutes: '',
    randomize_questions: false, randomize_options: false, status: 'draft', items: [],
});
const form = useForm<PackForm>(blank());
const editingPack = computed(() => props.packs.data.find((pack) => pack.id === editing.value));
const selectedQuestionIds = computed(() => new Set(form.items.map((item) => item.question_id)));
const incompatibleItems = computed(() => form.items.filter((item) => {
    const jenjangMismatch = Boolean(item.jenjang && form.jenjang && item.jenjang !== form.jenjang);
    const curriculumMismatch = Boolean(item.curriculum && item.curriculum !== 'both' && form.curriculum !== 'both' && item.curriculum !== form.curriculum);

    return jenjangMismatch || curriculumMismatch;
}));
const availableQuestions = computed(() => {
    const term = questionSearch.value.trim().toLocaleLowerCase();

    return props.question_options.filter((question) => {
        if (selectedQuestionIds.value.has(question.id)) return false;
        if (question.jenjang && form.jenjang && question.jenjang !== form.jenjang) return false;
        if (question.curriculum && question.curriculum !== 'both' && form.curriculum !== 'both' && question.curriculum !== form.curriculum) return false;
        if (!term) return true;

        return `${question.prompt} ${question.subject_code ?? ''} ${question.subject_name ?? ''}`.toLocaleLowerCase().includes(term);
    });
});
const totalPoints = computed(() => form.items.reduce((total, item) => total + Number(item.points || 0), 0));

function applyFilters() {
    router.get('/penilaian/paket-soal', filterForm.value, { preserveState: true, preserveScroll: true, replace: true });
}

function openCreate() {
    editing.value = null;
    form.clearErrors();
    form.defaults(blank());
    form.reset();
    questionSearch.value = '';
    packError.value = '';
    showForm.value = true;
}

function openEdit(pack: any) {
    editing.value = pack.id;
    form.clearErrors();
    form.defaults({
        name: pack.name,
        description: pack.description ?? '',
        jenjang: pack.jenjang,
        curriculum: pack.curriculum,
        duration_minutes: pack.duration_minutes ?? '',
        randomize_questions: Boolean(pack.randomize_questions),
        randomize_options: Boolean(pack.randomize_options),
        status: pack.status === 'active' ? 'active' : 'draft',
        items: (pack.items ?? []).map((item: any) => ({
            question_id: Number(item.exam_question_id),
            points: Number(item.points),
            prompt: item.question_version?.prompt ?? item.question?.prompt ?? 'Soal tidak tersedia',
            type: item.question_version?.type ?? item.question?.type ?? '—',
            jenjang: item.question_version?.jenjang ?? item.question?.jenjang ?? null,
            curriculum: item.question_version?.curriculum ?? item.question?.curriculum ?? null,
            question_active: Boolean(item.question?.is_active),
        })),
    });
    form.reset();
    questionSearch.value = '';
    packError.value = '';
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editing.value = null;
    form.clearErrors();
    packError.value = '';
}

function addQuestion(question: QuestionOption) {
    if (!question.version_available) {
        packError.value = 'Soal ini belum memiliki versi tersimpan. Buka Bank Soal, simpan soalnya, lalu coba lagi.';
        return;
    }

    packError.value = '';
    form.items.push({
        question_id: question.id,
        points: Number(question.points) || 1,
        prompt: question.prompt,
        type: question.type,
        jenjang: question.jenjang,
        curriculum: question.curriculum,
        question_active: true,
    });
}

function moveItem(index: number, direction: -1 | 1) {
    const destination = index + direction;
    if (destination < 0 || destination >= form.items.length) return;
    const items = [...form.items];
    [items[index], items[destination]] = [items[destination], items[index]];
    form.items = items;
}

function submit() {
    if (!form.items.length) {
        packError.value = 'Tambahkan minimal satu soal sebelum menyimpan paket.';
        return;
    }
    if (!form.jenjang) {
        packError.value = 'Pilih jenjang paket sebelum menyimpan.';
        return;
    }
    if (incompatibleItems.value.length) {
        packError.value = 'Ada soal yang jenjang atau kurikulumnya tidak sesuai dengan paket. Ubah klasifikasi paket atau hapus soal bertanda tidak sesuai.';
        return;
    }
    packError.value = '';
    const payload = {
        ...form.data(),
        duration_minutes: form.duration_minutes === '' ? null : form.duration_minutes,
        items: form.items.map((item) => ({ question_id: item.question_id, points: item.points })),
    };

    if (editing.value) {
        form.transform(() => payload).put(`/penilaian/paket-soal/${editing.value}`, { preserveScroll: true, onSuccess: closeForm });
    } else {
        form.transform(() => payload).post('/penilaian/paket-soal', { preserveScroll: true, onSuccess: closeForm });
    }
}

function action(path: string, confirmation: string) {
    if (window.confirm(confirmation)) router.post(path, {}, { preserveScroll: true });
}

function typeLabel(type: string) {
    return ({ multiple_choice: 'Pilihan ganda', essay: 'Esai', true_false: 'Benar / salah', matching: 'Menjodohkan' } as Record<string, string>)[type] ?? type;
}
</script>

<template>
    <Head title="Paket Soal" />
    <AppLayout title="Paket Soal">
        <main class="view space-y-6">
            <section class="hero-card pack-hero">
                <div>
                    <p class="eyebrow">Asesmen · Koleksi reusable</p>
                    <h1>Paket Soal</h1>
                    <p>Kelompokkan soal, atur urutan dan bobot, lalu simpan sebagai draf atau paket aktif.</p>
                </div>
                <div class="hero-actions">
                    <Link href="/penilaian/bank-soal" class="secondary-button">Bank Soal</Link>
                    <button class="primary-button" @click="openCreate">+ Buat paket</button>
                </div>
            </section>

            <section class="pack-stats">
                <div class="panel stat-card"><span>Paket ditemukan</span><strong>{{ packs.total }}</strong></div>
                <div class="panel stat-card"><span>Soal yang bisa dipilih</span><strong>{{ question_options.length }}</strong></div>
                <div class="panel stat-card"><span>Paket aktif</span><strong>{{ packs.data.filter((pack) => pack.status === 'active').length }}</strong></div>
            </section>

            <section class="panel filters-panel">
                <form class="filters-grid" @submit.prevent="applyFilters">
                    <label class="search-field">Cari paket<input v-model="filterForm.search" placeholder="Nama atau deskripsi paket" /></label>
                    <label>Jenjang<select v-model="filterForm.jenjang"><option value="">Semua jenjang</option><option value="SD">SD</option><option value="SMP">SMP</option><option value="SMA">SMA</option></select></label>
                    <label>Kurikulum<select v-model="filterForm.curriculum"><option value="">Semua kurikulum</option><option value="merdeka">Merdeka</option><option value="kbc">KBC</option><option value="both">Merdeka & KBC</option></select></label>
                    <label>Status<select v-model="filterForm.status"><option value="all">Semua status</option><option value="draft">Draf</option><option value="active">Aktif</option><option value="archived">Arsip</option></select></label>
                    <button class="primary-button filter-button">Terapkan filter</button>
                </form>
            </section>

            <section class="pack-grid">
                <article v-for="pack in packs.data" :key="pack.id" class="panel pack-card">
                    <div class="pack-card-heading">
                        <div>
                            <div class="pack-badges"><span class="status-badge" :class="`status-${pack.status}`">{{ pack.status === 'active' ? 'Aktif' : pack.status === 'archived' ? 'Arsip' : 'Draf' }}</span><span class="status-badge level-badge">{{ pack.jenjang }}</span><span class="status-badge curriculum-badge">{{ pack.curriculum === 'both' ? 'Merdeka & KBC' : pack.curriculum === 'kbc' ? 'KBC' : 'Merdeka' }}</span></div>
                            <h2>{{ pack.name }}</h2>
                        </div>
                        <div class="pack-actions">
                            <RowActionButton v-if="pack.status !== 'archived'" action="edit" label="Ubah" @click="openEdit(pack)" />
                            <button class="text-button" @click="action(`/penilaian/paket-soal/${pack.id}/duplikasi`, 'Duplikasi paket ini sebagai draf baru?')">Duplikasi</button>
                            <Link class="text-button" :href="`/penilaian/berbagi-soal?type=pack&id=${pack.id}`">Bagikan</Link>
                            <RowActionButton v-if="pack.status !== 'archived'" action="archive" label="Arsipkan" :aria-label="`Arsipkan paket ${pack.name}`" @click="action(`/penilaian/paket-soal/${pack.id}/arsip`, 'Arsipkan paket soal ini?')" />
                            <button v-else class="text-button" @click="action(`/penilaian/paket-soal/${pack.id}/pulihkan`, 'Pulihkan paket ini sebagai draf?')">Pulihkan</button>
                        </div>
                    </div>
                    <p v-if="pack.description" class="pack-description">{{ pack.description }}</p>
                    <div class="pack-metrics"><span>{{ pack.items_count }} soal</span><span>{{ Number(pack.items_sum_points ?? 0).toLocaleString('id-ID') }} poin</span><span>{{ pack.duration_minutes ? `${pack.duration_minutes} menit` : 'Tanpa durasi' }}</span><span v-if="capabilities.manage_all">Pembuat: {{ pack.creator?.name ?? '—' }}</span></div>
                    <p class="pack-randomization">{{ pack.randomize_questions ? 'Soal diacak' : 'Urutan tetap' }} · {{ pack.randomize_options ? 'Opsi diacak' : 'Opsi tetap' }}</p>
                    <details class="pack-items"><summary>Lihat item dan bobot</summary><ol><li v-for="item in pack.items" :key="item.id"><span>{{ item.sequence }}. {{ item.question_version?.prompt ?? 'Soal snapshot' }}</span><small>{{ typeLabel(item.question_version?.type ?? '') }} · {{ item.points }} poin</small></li></ol></details>
                </article>
                <p v-if="!packs.data.length" class="panel empty-state">Belum ada paket untuk filter ini. Buat paket pertama dari soal aktif yang dapat Anda akses.</p>
            </section>

            <nav v-if="packs.links.length > 3" class="pagination" aria-label="Halaman paket soal">
                <Link v-for="(link, index) in packs.links" :key="index" :href="link.url ?? '# '" :class="{ current: link.active, disabled: !link.url }" v-html="link.label" />
            </nav>
        </main>

        <div v-if="showForm" class="modal-backdrop" @click.self="closeForm">
            <form class="modal-card pack-form" @submit.prevent="submit">
                <div class="form-heading"><div><p class="eyebrow">{{ editing ? 'Ubah metadata dan item' : 'Paket baru' }}</p><h2>{{ editing ? 'Ubah paket soal' : 'Buat paket soal' }}</h2></div><button type="button" class="close-button" aria-label="Tutup" @click="closeForm">×</button></div>
                <div class="form-grid">
                    <label>Nama paket<input v-model="form.name" required maxlength="180" placeholder="Contoh: Sumatif Aljabar Kelas VII" /></label>
                    <label>Jenjang<select v-model="form.jenjang" required><option value="">Pilih jenjang</option><option value="SD">SD</option><option value="SMP">SMP</option><option value="SMA">SMA</option></select></label>
                    <label>Kurikulum<select v-model="form.curriculum" required><option value="merdeka">Merdeka</option><option value="kbc">KBC</option><option value="both">Merdeka & KBC</option></select></label>
                    <label>Durasi (menit, opsional)<input v-model.number="form.duration_minutes" type="number" min="1" max="480" placeholder="Tidak dibatasi" /></label>
                    <label>Status<select v-model="form.status"><option value="draft">Draf</option><option value="active">Aktif</option></select></label>
                    <label class="span-all">Deskripsi<textarea v-model="form.description" rows="2" maxlength="5000" placeholder="Tujuan atau cakupan paket" /></label>
                </div>

                <section class="settings-grid" aria-label="Pengaturan paket">
                    <label class="toggle-row"><input v-model="form.randomize_questions" type="checkbox" /> Acak urutan soal saat digunakan</label>
                    <label class="toggle-row"><input v-model="form.randomize_options" type="checkbox" /> Acak opsi saat digunakan</label>
                </section>

                <section class="item-builder">
                    <div class="item-builder-heading"><div><h3>Soal dalam paket</h3><p>{{ form.items.length }} soal · {{ totalPoints.toLocaleString('id-ID') }} poin total</p></div></div>
                    <div class="question-picker">
                        <label>Cari soal yang tersedia<input v-model="questionSearch" placeholder="Cari teks soal atau mapel" /></label>
                        <div class="question-options">
                            <button v-for="question in availableQuestions" :key="question.id" type="button" class="question-option" :disabled="!question.version_available" @click="addQuestion(question)">
                                <span><b>{{ question.subject_code ? `${question.subject_code} · ` : '' }}{{ typeLabel(question.type) }}</b><small>{{ question.prompt }}</small></span><strong>{{ question.version_available ? '+ Tambah' : 'Versi belum tersimpan' }}</strong>
                            </button>
                            <p v-if="!availableQuestions.length" class="empty-picker">Tidak ada soal aktif yang cocok dengan pencarian dan klasifikasi paket.</p>
                        </div>
                        <p v-if="availableQuestions.some((question) => !question.version_available)" class="picker-hint">Soal tanpa versi tersimpan tidak dapat dimasukkan ke paket. Simpan ulang soal tersebut di Bank Soal terlebih dahulu.</p>
                    </div>
                    <ol class="selected-items">
                        <li v-for="(item, index) in form.items" :key="item.question_id" class="selected-item">
                            <div class="sequence-controls"><button type="button" :disabled="index === 0" :aria-label="`Naikkan soal ${index + 1}`" @click="moveItem(index, -1)">↑</button><span>{{ index + 1 }}</span><button type="button" :disabled="index === form.items.length - 1" :aria-label="`Turunkan soal ${index + 1}`" @click="moveItem(index, 1)">↓</button></div>
                            <div class="selected-copy"><b>{{ typeLabel(item.type) }}<span v-if="!item.question_active" class="archived-note"> · soal sumber diarsip</span></b><span>{{ item.prompt }}</span></div>
                            <label class="points-field">Poin<input v-model.number="item.points" type="number" min="0.1" max="100" step="0.1" required /></label>
                            <button type="button" class="remove-button" :aria-label="`Hapus soal ${index + 1}`" @click="form.items.splice(index, 1)">×</button>
                        </li>
                        <li v-if="!form.items.length" class="empty-selected">Tambahkan minimal satu soal dari daftar di atas.</li>
                    </ol>
                    <p v-if="incompatibleItems.length" class="form-error item-warning">{{ incompatibleItems.length }} soal tidak sesuai dengan jenjang atau kurikulum paket. Perbaiki sebelum menyimpan.</p>
                </section>

                <p v-if="packError || form.hasErrors" class="form-error">{{ packError || Object.values(form.errors)[0] }}</p>
                <div class="modal-actions"><button type="button" class="secondary-button" @click="closeForm">Batal</button><button class="primary-button" :disabled="form.processing || !form.items.length || incompatibleItems.length > 0">{{ form.processing ? 'Menyimpan…' : 'Simpan paket' }}</button></div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.pack-hero,.hero-actions,.pack-card-heading,.form-heading,.item-builder-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem}.hero-actions,.pack-actions{display:flex;align-items:center;gap:.45rem;flex-wrap:wrap}.pack-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.stat-card{padding:1rem 1.2rem;display:grid;gap:.3rem}.stat-card span,.pack-metrics,.pack-randomization,.pack-description,.item-builder-heading p{color:#64748b;font-size:.82rem}.stat-card strong{font-size:1.7rem}.filters-panel{padding:1rem}.filters-grid{display:grid;grid-template-columns:2fr repeat(3,minmax(130px,1fr));gap:.8rem;align-items:end}.filters-grid label,.pack-form label{display:grid;gap:.35rem;font-size:.8rem;font-weight:650}.filters-grid input,.filters-grid select,.pack-form input:not([type=checkbox]),.pack-form select,.pack-form textarea{width:100%;border:1px solid #cbd5e1;border-radius:.6rem;padding:.62rem .7rem;background:#fff;color:#0f172a}.filter-button{min-height:42px}.pack-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.pack-card{padding:1.1rem;min-width:0}.pack-card h2{margin-top:.45rem;font-weight:850;font-size:1.05rem}.pack-badges{display:flex;gap:.35rem;flex-wrap:wrap}.status-badge{border-radius:999px;padding:.2rem .55rem;font-size:.68rem;font-weight:750;background:#f1f5f9;color:#475569}.status-active{background:#dcfce7;color:#166534}.status-archived{background:#fee2e2;color:#991b1b}.level-badge{background:#e0f2fe;color:#075985}.curriculum-badge{background:#eef2ff;color:#4338ca}.pack-metrics{display:flex;gap:.5rem .8rem;flex-wrap:wrap;margin-top:.8rem}.pack-randomization{margin-top:.4rem}.pack-description{margin-top:.65rem;white-space:pre-wrap}.pack-items{margin-top:.8rem;padding-top:.65rem;border-top:1px solid #e2e8f0;font-size:.8rem}.pack-items summary{cursor:pointer;font-weight:750}.pack-items ol{display:grid;gap:.55rem;margin-top:.6rem;padding-left:1.4rem}.pack-items li{padding-left:.15rem}.pack-items li span,.pack-items li small{display:block;overflow-wrap:anywhere}.pack-items li small{color:#64748b}.danger-text{color:#b91c1c}.pagination{display:flex;justify-content:center;gap:.35rem}.pagination :deep(a){padding:.4rem .65rem;border:1px solid #e2e8f0;border-radius:.4rem;font-size:.8rem}.pagination :deep(a.current){background:#4f46e5;color:#fff;border-color:#4f46e5}.pagination :deep(a.disabled){opacity:.45;pointer-events:none}.modal-backdrop{position:fixed;inset:0;z-index:90;display:grid;place-items:center;padding:1rem;background:#0f172a99}.pack-form{width:min(1000px,97vw);max-height:94vh;overflow:auto;display:grid;gap:1rem;padding:1.3rem;background:#fff;border-radius:1rem}.form-heading h2{font-size:1.25rem;font-weight:850}.close-button,.remove-button{border:0;background:transparent;font-size:1.5rem;line-height:1;cursor:pointer}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}.span-all{grid-column:1/-1}.settings-grid{display:flex;gap:1rem;flex-wrap:wrap}.toggle-row{display:flex!important;align-items:center;gap:.45rem}.toggle-row input{accent-color:#4f46e5}.item-builder{display:grid;gap:.8rem;padding:1rem;border:1px solid #e2e8f0;border-radius:.85rem}.item-builder h3{font-weight:850}.question-picker{display:grid;gap:.5rem}.question-options{max-height:190px;overflow:auto;display:grid;gap:.35rem}.question-option{display:flex;align-items:center;justify-content:space-between;gap:.7rem;padding:.55rem .65rem;text-align:left;border:1px solid #e2e8f0;border-radius:.55rem}.question-option:disabled{opacity:.58;cursor:not-allowed}.question-option span,.question-option small{display:block;min-width:0}.question-option small{margin-top:.15rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.question-option strong{flex-shrink:0;color:#4338ca;font-size:.75rem}.empty-picker,.empty-selected{padding:.7rem;color:#64748b;font-size:.8rem}.picker-hint{color:#92400e;font-size:.78rem}.selected-items{display:grid;gap:.45rem;list-style:none;padding:0;margin:0}.selected-item{display:grid;grid-template-columns:3rem minmax(0,1fr) 6rem 2rem;align-items:center;gap:.65rem;padding:.6rem;border:1px solid #e2e8f0;border-radius:.6rem}.sequence-controls{display:grid;justify-items:center;gap:.1rem}.sequence-controls button{width:1.8rem;height:1.5rem;border:1px solid #cbd5e1;border-radius:.35rem;color:#334155}.sequence-controls button:disabled{opacity:.35}.sequence-controls span{font-size:.75rem;font-weight:800}.selected-copy{min-width:0;display:grid;gap:.16rem;font-size:.78rem}.selected-copy>span{color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.archived-note{color:#b45309;font-weight:500}.points-field{min-width:0}.remove-button{color:#b91c1c}.modal-actions{display:flex;justify-content:flex-end;gap:.6rem}.form-error{color:#b91c1c;font-size:.85rem}.item-warning{margin-top:.4rem}@media(max-width:850px){.filters-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.search-field,.filter-button{grid-column:1/-1}.pack-grid{grid-template-columns:1fr}}@media(max-width:600px){.pack-hero,.pack-card-heading{align-items:stretch;flex-direction:column}.pack-stats{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.span-all,.search-field,.filter-button{grid-column:auto}.selected-item{grid-template-columns:2.4rem minmax(0,1fr) 4.7rem 1.5rem;gap:.4rem;padding:.45rem}.pack-actions{justify-content:flex-start}}
</style>
