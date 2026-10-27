<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ assignments: any[]; questions: any[]; packs: any[]; exams: any[]; attempts: any[]; components: any[]; can_manage: boolean; can_manage_question_bank: boolean; is_student: boolean; server_now?: string }>();
const showExamForm = ref(false);
const examSearch = ref('');
const statusFilter = ref('all');
const questionSearch = ref('');
const questionType = ref('all');
const e = useForm({ teaching_assignment_id: props.assignments[0]?.id ?? '', assessment_component_id: '', title: '', instructions: '', starts_at: '', ends_at: '', duration_minutes: 60, randomize_questions: true, randomize_options: true, max_violations: 3, question_pack_id: '' as string | number, question_ids: [] as number[] });
const selectedPack = computed(() => props.packs.find((pack) => String(pack.id) === String(e.question_pack_id)));
const attemptsByExam = computed(() => new Map(props.attempts.map((attempt) => [attempt.exam_id, attempt])));
const totalAttempts = computed(() => props.exams.reduce((total: number, exam: any) => total + Number(exam.attempts_count ?? 0), 0));
const serverNow = computed(() => new Date(props.server_now ?? Date.now()).getTime());
const filteredExams = computed(() => props.exams.filter((exam) => {
    const term = examSearch.value.trim().toLowerCase();
    return (!term || String(exam.title).toLowerCase().includes(term)) && (statusFilter.value === 'all' || exam.status === statusFilter.value);
}));
const filteredQuestions = computed(() => props.questions.filter((question) => {
    const term = questionSearch.value.trim().toLowerCase();
    return (!term || `${question.prompt} ${question.type}`.toLowerCase().includes(term)) && (questionType.value === 'all' || question.type === questionType.value);
}));
const selectedQuestionCount = computed(() => e.question_ids.length);
const selectedQuestionPoints = computed(() => props.questions.filter((question) => e.question_ids.includes(Number(question.id))).reduce((sum, question) => sum + Number(question.points ?? 0), 0));
const canStartExam = (exam: any) => exam.status === 'published' && new Date(exam.starts_at).getTime() <= serverNow.value && new Date(exam.ends_at).getTime() >= serverNow.value;
const sourceChanged = () => { e.question_ids = []; if (selectedPack.value) { e.duration_minutes = selectedPack.value.duration_minutes ?? e.duration_minutes; e.randomize_questions = selectedPack.value.randomize_questions; e.randomize_options = selectedPack.value.randomize_options; } };
const toggleAllQuestions = () => { const ids = filteredQuestions.value.map((question) => Number(question.id)); const allSelected = ids.length > 0 && ids.every((id) => e.question_ids.includes(id)); e.question_ids = allSelected ? e.question_ids.filter((id) => !ids.includes(id)) : [...new Set([...e.question_ids, ...ids])]; };
const publishExam = (id: number) => { if (window.confirm('Terbitkan ujian ini? Setelah terbit, siswa dapat melihatnya pada periode yang ditentukan.')) router.post(`/ujian/${id}/terbitkan`, {}, { preserveScroll: true }); };
const closeForm = () => { showExamForm.value = false; e.clearErrors(); };
const submitExam = () => e.post('/ujian', { preserveScroll: true, onSuccess: closeForm });
const onEscape = (event: KeyboardEvent) => { if (event.key === 'Escape' && showExamForm.value) closeForm(); };
onMounted(() => window.addEventListener('keydown', onEscape));
onBeforeUnmount(() => window.removeEventListener('keydown', onEscape));
</script>

<template>
    <Head title="Ujian CBT" />
    <AppLayout>
        <main class="view space-y-6">
            <section class="hero-card exam-hero"><div><p class="eyebrow">Asesmen · Computer based test</p><h1>{{ can_manage_question_bank ? 'Ujian & bank soal' : 'Ujian CBT' }}</h1><p>{{ is_student ? 'Lihat jadwal ujian kelasmu dan mulai saat periode ujian dibuka.' : 'Kelola jadwal, sumber soal, pengawasan, dan hasil ujian dari satu alur.' }}</p></div><div v-if="can_manage" class="actions"><Link v-if="can_manage_question_bank" class="secondary-button" href="/penilaian/bank-soal">Kelola bank soal</Link><button class="primary-button" type="button" @click="showExamForm = true">+ Buat ujian</button></div></section>
            <section v-if="can_manage" class="grade-summary"><div v-if="can_manage_question_bank"><span>Bank soal aktif</span><strong>{{ questions.length }}</strong></div><div><span>Ujian</span><strong>{{ exams.length }}</strong></div><div><span>Terbit</span><strong>{{ exams.filter((exam) => exam.status === 'published').length }}</strong></div><div><span>Attempt</span><strong>{{ totalAttempts }}</strong></div></section>
            <section class="panel exam-list"><div class="list-toolbar"><div><h2>Daftar ujian</h2><p>{{ filteredExams.length }} ujian ditampilkan</p></div><div class="toolbar-fields"><input v-model="examSearch" aria-label="Cari ujian" placeholder="Cari judul ujian" /><select v-model="statusFilter" aria-label="Filter status"><option value="all">Semua status</option><option value="draft">Draf</option><option value="published">Terbit</option><option value="results_published">Hasil diterbitkan</option></select></div></div><article v-for="exam in filteredExams" :key="exam.id" class="exam-row"><div class="exam-copy"><div class="exam-meta"><span class="status-badge" :class="`status-${exam.status}`">{{ exam.status === 'results_published' ? 'Hasil diterbitkan' : exam.status === 'published' ? 'Terbit' : 'Draf' }}</span><span>{{ exam.items_count }} soal</span><span>{{ exam.duration_minutes }} menit</span></div><h3>{{ exam.title }}</h3><p>{{ new Date(exam.starts_at).toLocaleString('id-ID') }} — {{ new Date(exam.ends_at).toLocaleString('id-ID') }}</p></div><div class="actions exam-actions"><button v-if="can_manage && exam.status === 'draft'" class="primary-button" type="button" @click="publishExam(exam.id)">Terbitkan</button><Link v-if="can_manage" class="secondary-button" :href="`/ujian/${exam.id}/monitor`">Pantau siswa</Link><Link v-if="can_manage" class="secondary-button" :href="`/ujian/${exam.id}/monitor`">Pantau siswa</Link><Link v-if="can_manage" class="secondary-button" :href="`/ujian/${exam.id}/hasil`">Lihat hasil</Link><a v-if="can_manage" class="secondary-button" :href="`/ujian/${exam.id}/hasil/export`">Ekspor CSV</a><Link v-if="is_student && attemptsByExam.get(exam.id)" class="primary-button" :href="`/ujian/attempt/${attemptsByExam.get(exam.id).id}`">{{ attemptsByExam.get(exam.id).status === 'in_progress' ? 'Lanjutkan' : 'Lihat pengerjaan' }}</Link><button v-else-if="is_student && canStartExam(exam)" class="primary-button" type="button" @click="router.post(`/ujian/${exam.id}/mulai`)">Mulai ujian</button><span v-else-if="is_student" class="exam-availability">{{ exam.status === 'results_published' ? 'Hasil telah diterbitkan' : new Date(exam.starts_at).getTime() > serverNow ? 'Ujian belum dimulai' : 'Waktu ujian berakhir' }}</span></div></article><p v-if="!filteredExams.length" class="empty-state">Belum ada ujian yang sesuai filter.</p></section>
            <div v-if="showExamForm" class="modal-backdrop" role="presentation" @click.self="closeForm"><form class="modal-card" aria-labelledby="exam-form-title" @submit.prevent="submitExam"><div class="modal-heading"><div><p class="eyebrow">Draf baru</p><h2 id="exam-form-title">Buat ujian</h2></div><button class="close-button" type="button" aria-label="Tutup" @click="closeForm">×</button></div><label>Kelas dan mata pelajaran<select v-model="e.teaching_assignment_id" required><option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">{{ assignment.subject?.name }} · {{ assignment.class_group?.name }}</option></select></label><label>Komponen nilai (opsional)<select v-model="e.assessment_component_id"><option value="">Tidak disinkronkan</option><option v-for="component in components.filter((item) => !item.teaching_assignment_id || String(item.teaching_assignment_id) === String(e.teaching_assignment_id))" :key="component.id" :value="component.id">{{ component.name }}</option></select></label><label>Judul<input v-model="e.title" required maxlength="180" placeholder="Contoh: Sumatif IPA Bab Ekosistem" /></label><label>Instruksi<textarea v-model="e.instructions" rows="2" maxlength="5000" placeholder="Petunjuk untuk siswa (opsional)" /></label><div class="grid"><label>Mulai<input v-model="e.starts_at" type="datetime-local" required /></label><label>Selesai<input v-model="e.ends_at" type="datetime-local" required /></label><label>Durasi (menit)<input v-model.number="e.duration_minutes" type="number" min="1" max="480" required /></label><label>Batas pelanggaran<input v-model.number="e.max_violations" type="number" min="1" max="20" required /></label></div><label>Sumber soal<select v-model="e.question_pack_id" @change="sourceChanged"><option value="">Pilih soal manual</option><option v-for="pack in packs" :key="pack.id" :value="pack.id">{{ pack.name }} · {{ pack.items_count }} soal</option></select></label><fieldset v-if="selectedPack"><legend>Snapshot paket: {{ selectedPack.name }}</legend><p class="helper">Versi, urutan, dan bobot soal disalin ke draf; perubahan paket sesudahnya tidak mengubah ujian ini.</p><div class="selected-summary">{{ selectedPack.items_count }} soal · {{ selectedPack.items.reduce((sum: number, item: any) => sum + Number(item.points ?? 0), 0) }} poin</div></fieldset><fieldset v-else><legend>Pilih soal manual</legend><div class="picker-toolbar"><input v-model="questionSearch" placeholder="Cari teks soal" aria-label="Cari soal" /><select v-model="questionType" aria-label="Filter jenis soal"><option value="all">Semua jenis</option><option value="multiple_choice">Pilihan ganda</option><option value="true_false">Benar / salah</option><option value="matching">Menjodohkan</option><option value="essay">Esai</option></select><button type="button" class="secondary-button" @click="toggleAllQuestions">{{ filteredQuestions.length && filteredQuestions.every((question) => e.question_ids.includes(Number(question.id))) ? 'Batalkan semua' : 'Pilih semua hasil' }}</button></div><div class="selected-summary">{{ selectedQuestionCount }} soal dipilih · {{ selectedQuestionPoints }} poin</div><div class="question-picker"><label v-for="question in filteredQuestions" :key="question.id" class="check"><input v-model="e.question_ids" type="checkbox" :value="Number(question.id)" /><span><b>{{ question.prompt }}</b><small>{{ question.type }} · {{ question.points }} poin</small></span></label><p v-if="!filteredQuestions.length" class="empty-picker">Tidak ada soal yang cocok.</p></div><p v-if="e.errors.question_ids" class="error">{{ e.errors.question_ids }}</p></fieldset><label class="check inline"><input v-model="e.randomize_questions" type="checkbox" /> Acak urutan soal</label><label class="check inline"><input v-model="e.randomize_options" type="checkbox" /> Acak pilihan jawaban</label><p v-if="e.hasErrors" class="error">{{ Object.values(e.errors)[0] }}</p><div class="modal-actions"><button class="secondary-button" type="button" @click="closeForm">Batal</button><button class="primary-button" type="submit" :disabled="e.processing">{{ e.processing ? 'Menyimpan…' : 'Simpan draf' }}</button></div></form></div>
        </main>
    </AppLayout>
</template>

<style scoped>
.exam-hero,
.list-toolbar,
.modal-heading,
.actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .8rem;
}

.actions {
    flex-wrap: wrap;
}

.grade-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.grade-summary > div {
    display: grid;
    gap: .2rem;
    padding: 1rem 1.2rem;
    border: 1px solid var(--line);
    border-radius: .8rem;
    background: var(--surface);
}

.grade-summary span,
.list-toolbar p,
.exam-copy p,
.helper,
.selected-summary {
    color: var(--ink-soft);
    font-size: .8rem;
}

.grade-summary strong {
    font-size: 1.6rem;
}

.exam-list {
    display: grid;
    gap: .8rem;
    padding: 1.2rem;
}

.list-toolbar {
    padding-bottom: .9rem;
    border-bottom: 1px solid var(--line);
}

.list-toolbar h2 {
    font-weight: 800;
}

.toolbar-fields,
.picker-toolbar {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
}

.toolbar-fields input,
.toolbar-fields select,
.picker-toolbar input,
.picker-toolbar select {
    min-height: 40px;
    border: 1px solid var(--line-strong);
    border-radius: .55rem;
    padding: .55rem .7rem;
    background: var(--surface);
    color: var(--ink);
}

.exam-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--line);
    border-radius: .75rem;
}

.exam-copy {
    min-width: 0;
}

.exam-copy h3 {
    margin: .45rem 0 .2rem;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.exam-meta {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
    color: var(--ink-soft);
    font-size: .75rem;
}

.status-badge {
    padding: .2rem .55rem;
    border-radius: 999px;
    font-weight: 750;
}

.status-draft {
    background: #fef3c7;
    color: #92400e;
}

.status-published {
    background: #dcfce7;
    color: #166534;
}

.status-results_published {
    background: #dbeafe;
    color: #1d4ed8;
}

.exam-actions {
    justify-content: flex-end;
    flex-shrink: 0;
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: grid;
    place-items: center;
    padding: 1rem;
    background: rgba(8, 15, 30, .9) !important;
    backdrop-filter: blur(8px) saturate(.75);
}

.modal-card {
    width: min(760px, 96vw);
    max-height: 92vh;
    overflow: auto;
    display: grid;
    gap: .8rem;
    padding: 1.3rem;
    border: 1px solid var(--line-strong) !important;
    border-radius: 1rem;
    background: var(--surface) !important;
    color: var(--ink) !important;
    box-shadow: 0 24px 70px rgba(15, 23, 42, .3);
}

.modal-card label {
    display: grid;
    gap: .35rem;
    font-size: .82rem;
    font-weight: 700;
    color: var(--ink-soft) !important;
}

.modal-card input:not([type=checkbox]),
.modal-card select,
.modal-card textarea {
    width: 100%;
    border: 1px solid var(--line-strong) !important;
    border-radius: .55rem;
    padding: .62rem;
    background: var(--surface) !important;
    color: var(--ink) !important;
}

.modal-card input::placeholder,
.modal-card textarea::placeholder {
    color: var(--ink-faint) !important;
}

.modal-card fieldset {
    display: grid;
    gap: .65rem;
    border: 1px solid var(--line);
    border-radius: .7rem;
    padding: .8rem;
    background: var(--surface-subtle);
}

.modal-card legend {
    color: var(--ink);
    font-weight: 800;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .7rem;
}

.check {
    display: flex !important;
    align-items: flex-start;
    gap: .55rem;
    font-weight: 500 !important;
}

.check input {
    margin-top: .25rem;
}

.check span {
    display: grid;
    gap: .15rem;
}

.check small {
    color: var(--ink-soft);
}

.check.inline {
    align-items: center !important;
}

.question-picker {
    display: grid;
    gap: .35rem;
    max-height: 250px;
    overflow: auto;
    padding: .2rem;
}

.question-picker .check {
    padding: .55rem;
    border: 1px solid var(--line);
    border-radius: .5rem;
    background: var(--surface);
}

.question-picker b {
    font-weight: 650;
    line-height: 1.35;
}

.empty-picker,
.exam-availability {
    color: var(--ink-soft);
    font-size: .8rem;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: .55rem;
}

.error {
    color: #b91c1c;
    font-size: .82rem;
}

.close-button {
    border: 0;
    background: transparent;
    color: var(--ink-soft);
    font-size: 1.5rem;
    cursor: pointer;
}

.close-button:hover {
    color: var(--ink);
}

.modal-card .helper,
.modal-card .selected-summary {
    color: var(--ink-soft);
}

@media (max-width: 850px) {
    .grade-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .exam-hero,
    .list-toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .toolbar-fields {
        width: 100%;
    }

    .toolbar-fields input,
    .toolbar-fields select {
        flex: 1;
        min-width: 0;
    }
}

@media (max-width: 620px) {
    .grade-summary {
        grid-template-columns: 1fr;
    }

    .exam-row {
        align-items: stretch;
        flex-direction: column;
    }

    .exam-actions {
        justify-content: flex-start;
    }

    .grid {
        grid-template-columns: 1fr;
    }

    .picker-toolbar > * {
        width: 100%;
    }
}
</style>
