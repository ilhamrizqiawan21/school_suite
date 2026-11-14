<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ assignments: any[]; questions: any[]; packs: any[]; exams: any[]; attempts: any[]; components: any[]; can_manage: boolean; can_manage_question_bank: boolean; is_student: boolean; server_now?: string }>();
const showExamForm = ref(false);
const examSearch = ref('');
const statusFilter = ref('all');
const studentExamFilter = ref<'all' | 'ready' | 'upcoming' | 'done'>('all');
const questionSearch = ref('');
const questionType = ref('all');
const e = useForm({ teaching_assignment_id: props.assignments[0]?.id ?? '', assessment_component_id: '', title: '', instructions: '', starts_at: '', ends_at: '', duration_minutes: 60, randomize_questions: true, randomize_options: true, max_violations: 3, question_pack_id: '' as string | number, question_ids: [] as number[] });
const selectedPack = computed(() => props.packs.find((pack) => String(pack.id) === String(e.question_pack_id)));
const attemptsByExam = computed(() => new Map(props.attempts.map((attempt) => [attempt.exam_id, attempt])));
const totalAttempts = computed(() => props.exams.reduce((total: number, exam: any) => total + Number(exam.attempts_count ?? 0), 0));
const serverNow = computed(() => new Date(props.server_now ?? Date.now()).getTime());
const canStartExam = (exam: any) => exam.status === 'published' && new Date(exam.starts_at).getTime() <= serverNow.value && new Date(exam.ends_at).getTime() >= serverNow.value;
const isExamUpcoming = (exam: any) => !attemptsByExam.value.get(exam.id) && exam.status === 'published' && new Date(exam.starts_at).getTime() > serverNow.value;
const isExamReady = (exam: any) => {
    const attempt = attemptsByExam.value.get(exam.id);
    if (attempt) return attempt.status === 'in_progress';
    return canStartExam(exam);
};
const isExamDone = (exam: any) => {
    const attempt = attemptsByExam.value.get(exam.id);
    return Boolean(attempt && attempt.status !== 'in_progress');
};
const studentExamStats = computed(() => ({
    total: props.exams.length,
    ready: props.exams.filter(isExamReady).length,
    upcoming: props.exams.filter(isExamUpcoming).length,
    done: props.exams.filter(isExamDone).length,
}));
const filteredExams = computed(() => props.exams.filter((exam) => {
    const term = examSearch.value.trim().toLowerCase();
    const matchesTerm = !term || `${exam.title} ${exam.teaching_assignment?.subject?.name ?? ''}`.toLowerCase().includes(term);
    if (!matchesTerm) return false;
    if (props.is_student) {
        if (studentExamFilter.value === 'ready') return isExamReady(exam);
        if (studentExamFilter.value === 'upcoming') return isExamUpcoming(exam);
        if (studentExamFilter.value === 'done') return isExamDone(exam);
        return true;
    }
    return statusFilter.value === 'all' || exam.status === statusFilter.value;
}));
const filteredQuestions = computed(() => props.questions.filter((question) => {
    const term = questionSearch.value.trim().toLowerCase();
    return (!term || `${question.prompt} ${question.type}`.toLowerCase().includes(term)) && (questionType.value === 'all' || question.type === questionType.value);
}));
const selectedQuestionCount = computed(() => e.question_ids.length);
const selectedQuestionPoints = computed(() => props.questions.filter((question) => e.question_ids.includes(Number(question.id))).reduce((sum, question) => sum + Number(question.points ?? 0), 0));
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
        <div class="view exam-page space-y-6">
            <header class="exam-hero"><div><p class="eyebrow">Asesmen · Computer based test</p><h1>{{ can_manage_question_bank ? 'Ujian & bank soal' : 'Ujian CBT' }}</h1><p>{{ is_student ? 'Lihat jadwal ujian kelasmu dan mulai saat periode ujian dibuka.' : 'Kelola jadwal, sumber soal, pengawasan, dan hasil ujian dari satu alur.' }}</p></div><div v-if="can_manage" class="actions"><Link v-if="can_manage_question_bank" class="secondary-button ui-button ui-button--secondary ui-button--md" href="/penilaian/bank-soal">Kelola bank soal</Link><AppButton variant="primary" class="primary-button" type="button" @click="showExamForm = true">+ Buat ujian</AppButton></div><div v-else-if="is_student" class="actions"><Link class="secondary-button ui-button ui-button--secondary ui-button--md" href="/akademik/jadwal">Jadwal saya</Link><Link class="secondary-button ui-button ui-button--secondary ui-button--md" href="/penilaian/rapor">Rapor saya</Link></div></header>
            <section v-if="can_manage" class="ss-summary-grid" :class="{ 'ss-summary-grid--3': !can_manage_question_bank }">
                <MetricSummaryCard
                    v-if="can_manage_question_bank"
                    label="Bank soal aktif"
                    :value="questions.length"
                    tone="blue"
                    icon="M4 5h16M4 10h16M4 15h16M4 20h16M7 3v4m5 1v4m5 1v4"
                />
                <MetricSummaryCard
                    label="Ujian"
                    :value="exams.length"
                    tone="violet"
                    icon="M9 12h6m-6 4h6M9 8h6m2 13H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"
                />
                <MetricSummaryCard
                    label="Terbit"
                    :value="exams.filter((exam) => exam.status === 'published').length"
                    tone="green"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    label="Attempt"
                    :value="totalAttempts"
                    tone="amber"
                    icon="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m6-10a4 4 0 100-8 4 4 0 000 8z"
                />
            </section>
            <section v-else-if="is_student" class="ss-summary-grid" aria-label="Ringkasan ujian saya">
                <MetricSummaryCard
                    label="Total ujian"
                    :value="studentExamStats.total"
                    note="terjadwal untuk kelasmu"
                    tone="blue"
                    icon="M9 12h6m-6 4h6M9 8h6m2 13H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"
                />
                <MetricSummaryCard
                    label="Siap dikerjakan"
                    :value="studentExamStats.ready"
                    note="dapat dimulai sekarang"
                    tone="green"
                    icon="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    label="Akan datang"
                    :value="studentExamStats.upcoming"
                    note="menunggu jadwal buka"
                    tone="amber"
                    icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    label="Sudah selesai"
                    :value="studentExamStats.done"
                    note="jawaban tercatat"
                    tone="violet"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </section>
            <section class="panel exam-list">
                <div class="list-toolbar">
                    <div><h2>Daftar ujian</h2><p>{{ filteredExams.length }} ujian ditampilkan</p></div>
                    <div class="toolbar-fields">
                        <input v-model="examSearch" class="search-control" type="search" aria-label="Cari ujian" placeholder="Cari judul ujian" />
                        <select v-if="!is_student" v-model="statusFilter" aria-label="Filter status"><option value="all">Semua status</option><option value="draft">Draf</option><option value="published">Terbit</option><option value="results_published">Hasil diterbitkan</option></select>
                    </div>
                </div>
                <div v-if="is_student" class="student-exam-pills" role="group" aria-label="Filter status ujian siswa">
                    <button type="button" class="exam-filter-pill" :class="{ 'is-active': studentExamFilter === 'all' }" @click="studentExamFilter = 'all'">Semua <b>{{ studentExamStats.total }}</b></button>
                    <button type="button" class="exam-filter-pill" :class="{ 'is-active': studentExamFilter === 'ready' }" @click="studentExamFilter = 'ready'">Siap dikerjakan <b>{{ studentExamStats.ready }}</b></button>
                    <button type="button" class="exam-filter-pill" :class="{ 'is-active': studentExamFilter === 'upcoming' }" @click="studentExamFilter = 'upcoming'">Akan datang <b>{{ studentExamStats.upcoming }}</b></button>
                    <button type="button" class="exam-filter-pill" :class="{ 'is-active': studentExamFilter === 'done' }" @click="studentExamFilter = 'done'">Selesai <b>{{ studentExamStats.done }}</b></button>
                </div>
                <article v-for="exam in filteredExams" :key="exam.id" class="exam-row"><div class="exam-copy"><div class="exam-meta"><span class="status-badge" :class="`status-${exam.status}`">{{ exam.status === 'results_published' ? 'Hasil diterbitkan' : exam.status === 'published' ? 'Terbit' : 'Draf' }}</span><span v-if="exam.teaching_assignment?.subject?.name">{{ exam.teaching_assignment.subject.name }}</span><span>{{ exam.items_count }} soal</span><span>{{ exam.duration_minutes }} menit</span></div><h3>{{ exam.title }}</h3><p>{{ new Date(exam.starts_at).toLocaleString('id-ID') }} — {{ new Date(exam.ends_at).toLocaleString('id-ID') }}</p></div><div class="actions exam-actions"><AppButton variant="primary" v-if="can_manage && exam.status === 'draft'" class="primary-button" type="button" @click="publishExam(exam.id)">Terbitkan</AppButton><Link v-if="can_manage" class="secondary-button ui-button ui-button--secondary ui-button--md" :href="`/ujian/${exam.id}/monitor`">Pantau siswa</Link><Link v-if="can_manage" class="secondary-button ui-button ui-button--secondary ui-button--md" :href="`/ujian/${exam.id}/hasil`">Lihat hasil</Link><AppButton variant="secondary" as="a" v-if="can_manage" class="secondary-button" :href="`/ujian/${exam.id}/hasil/export`">Ekspor CSV</AppButton><Link v-if="is_student && attemptsByExam.get(exam.id)" class="primary-button ui-button ui-button--primary ui-button--md" :href="`/ujian/attempt/${attemptsByExam.get(exam.id).id}`">{{ attemptsByExam.get(exam.id).status === 'in_progress' ? 'Lanjutkan' : 'Lihat pengerjaan' }}</Link><AppButton variant="primary" v-else-if="is_student && canStartExam(exam)" class="primary-button" type="button" @click="router.post(`/ujian/${exam.id}/mulai`)">Mulai ujian</AppButton><span v-else-if="is_student" class="exam-availability">{{ exam.status === 'results_published' ? 'Hasil telah diterbitkan' : new Date(exam.starts_at).getTime() > serverNow ? 'Ujian belum dimulai' : 'Waktu ujian berakhir' }}</span></div></article><p v-if="!filteredExams.length" class="empty-state">Belum ada ujian yang sesuai filter.</p></section>
            <div v-if="showExamForm" class="modal-backdrop" role="presentation" @click.self="closeForm"><form class="modal-card" aria-labelledby="exam-form-title" @submit.prevent="submitExam"><div class="modal-heading"><div><p class="eyebrow">Draf baru</p><h2 id="exam-form-title">Buat ujian</h2></div><AppButton variant="ghost" icon-only icon="close" class="close-button" type="button" aria-label="Tutup" @click="closeForm">×</AppButton></div><label>Kelas dan mata pelajaran<select v-model="e.teaching_assignment_id" required><option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">{{ assignment.subject?.name }} · {{ assignment.class_group?.name }}</option></select></label><label>Komponen nilai (opsional)<select v-model="e.assessment_component_id"><option value="">Tidak disinkronkan</option><option v-for="component in components.filter((item) => !item.teaching_assignment_id || String(item.teaching_assignment_id) === String(e.teaching_assignment_id))" :key="component.id" :value="component.id">{{ component.name }}</option></select></label><label>Judul<input v-model="e.title" required maxlength="180" placeholder="Contoh: Sumatif IPA Bab Ekosistem" /></label><label>Instruksi<textarea v-model="e.instructions" rows="2" maxlength="5000" placeholder="Petunjuk untuk siswa (opsional)" /></label><div class="grid"><label>Mulai<input v-model="e.starts_at" type="datetime-local" required /></label><label>Selesai<input v-model="e.ends_at" type="datetime-local" required /></label><label>Durasi (menit)<input v-model.number="e.duration_minutes" type="number" min="1" max="480" required /></label><label>Batas pelanggaran<input v-model.number="e.max_violations" type="number" min="3" max="3" readonly aria-describedby="violation-policy" required /><small id="violation-policy">Ujian berakhir otomatis setelah 3 pelanggaran.</small></label></div><label>Sumber soal<select v-model="e.question_pack_id" @change="sourceChanged"><option value="">Pilih soal manual</option><option v-for="pack in packs" :key="pack.id" :value="pack.id">{{ pack.name }} · {{ pack.items_count }} soal</option></select></label><fieldset v-if="selectedPack"><legend>Snapshot paket: {{ selectedPack.name }}</legend><p class="helper">Versi, urutan, dan bobot soal disalin ke draf; perubahan paket sesudahnya tidak mengubah ujian ini.</p><div class="selected-summary">{{ selectedPack.items_count }} soal · {{ selectedPack.items.reduce((sum: number, item: any) => sum + Number(item.points ?? 0), 0) }} poin</div></fieldset><fieldset v-else><legend>Pilih soal manual</legend><div class="picker-toolbar"><input v-model="questionSearch" placeholder="Cari teks soal" aria-label="Cari soal" /><select v-model="questionType" aria-label="Filter jenis soal"><option value="all">Semua jenis</option><option value="multiple_choice">Pilihan ganda</option><option value="true_false">Benar / salah</option><option value="matching">Menjodohkan</option><option value="essay">Esai</option></select><AppButton variant="secondary" type="button" class="secondary-button" @click="toggleAllQuestions">{{ filteredQuestions.length && filteredQuestions.every((question) => e.question_ids.includes(Number(question.id))) ? 'Batalkan semua' : 'Pilih semua hasil' }}</AppButton></div><div class="selected-summary">{{ selectedQuestionCount }} soal dipilih · {{ selectedQuestionPoints }} poin</div><div class="question-picker"><label v-for="question in filteredQuestions" :key="question.id" class="check"><input v-model="e.question_ids" type="checkbox" :value="Number(question.id)" /><span><b>{{ question.prompt }}</b><small>{{ question.type }} · {{ question.points }} poin</small></span></label><p v-if="!filteredQuestions.length" class="empty-picker">Tidak ada soal yang cocok.</p></div><p v-if="e.errors.question_ids" class="error">{{ e.errors.question_ids }}</p></fieldset><label class="check inline"><input v-model="e.randomize_questions" type="checkbox" /> Acak urutan soal</label><label class="check inline"><input v-model="e.randomize_options" type="checkbox" /> Acak pilihan jawaban</label><p v-if="e.hasErrors" class="error">{{ Object.values(e.errors)[0] }}</p><div class="modal-actions"><AppButton variant="secondary" class="secondary-button" type="button" @click="closeForm">Batal</AppButton><AppButton variant="primary" :loading="e.processing" class="save-button" type="submit" :disabled="e.processing">{{ e.processing ? 'Menyimpan…' : 'Simpan draf' }}</AppButton></div></form></div>
        </div>
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
    font-size: var(--type-control);
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
    font-weight: var(--weight-bold);
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
    font-weight: var(--weight-bold);
    overflow-wrap: anywhere;
}

.exam-meta {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
    color: var(--ink-soft);
    font-size: var(--type-meta);
}

.status-badge {
    padding: .2rem .55rem;
    border-radius: 999px;
    font-weight: var(--weight-semibold);
}

.status-draft {
    background: var(--gold-soft);
    color: var(--gold);
}

.status-published {
    background: var(--green-soft);
    color: var(--green);
}

.status-results_published {
    background: var(--primary-soft);
    color: var(--primary);
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
    font-size: var(--type-sm);
    font-weight: var(--weight-semibold);
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
    font-weight: var(--weight-bold);
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
    font-weight: var(--weight-medium) !important;
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
    font-weight: var(--weight-semibold);
    line-height: 1.35;
}

.empty-picker,
.exam-availability {
    color: var(--ink-soft);
    font-size: var(--type-meta);
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: .55rem;
}

.error {
    color: var(--red);
    font-size: var(--type-sm);
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
.exam-page{max-width:1440px;margin-inline:auto}
.exam-hero{align-items:flex-start;padding:0 0 .8rem;border-bottom:1px solid var(--line)}
.exam-hero h1{margin:.25rem 0;color:var(--ink);font-size:var(--type-3xl);font-weight:var(--weight-bold);letter-spacing:-.035em}
.exam-hero>div>p:last-child{max-width:62ch;color:var(--ink-soft);font-size:var(--type-sm);line-height:1.55}
.grade-summary>div{position:relative;border-radius:var(--radius-md);box-shadow:var(--shadow-sm)}
.grade-summary>div::before{position:absolute;inset:0 auto 0 0;width:3px;background:var(--primary);content:''}
.grade-summary>div:nth-child(2)::before{background:var(--green)}
.grade-summary>div:nth-child(3)::before{background:var(--gold)}
.grade-summary strong{color:var(--ink);font-weight:var(--weight-bold)}
.exam-list{gap:0;padding:0;overflow:hidden}
.list-toolbar{padding:1.15rem 1.3rem}
.exam-row{border:0;border-top:1px solid var(--line);border-radius:0;padding:1.1rem 1.3rem}
.exam-row:hover{background:var(--surface-subtle)}
.status-draft{background:var(--gold-soft);color:var(--gold)}
.status-published{background:var(--green-soft);color:var(--green)}
.status-results_published{background:var(--blue-soft);color:var(--blue)}
.modal-backdrop{background:color-mix(in srgb,var(--ink) 58%,transparent)!important;backdrop-filter:none}
.modal-card{border-radius:var(--radius-md)}
.modal-heading{position:sticky;top:-1.3rem;z-index:1;padding:.35rem 0 .75rem;background:var(--surface);border-bottom:1px solid var(--line)}
.modal-actions{position:sticky;bottom:-1.3rem;z-index:1;padding:.75rem 0 .35rem;background:var(--surface);border-top:1px solid var(--line)}
.student-exam-pills{display:flex;flex-wrap:wrap;gap:.5rem}
.exam-filter-pill{display:inline-flex;align-items:center;gap:.45rem;min-height:36px;padding:.4rem .82rem;border-radius:999px;border:1px solid var(--line);background:var(--surface);color:var(--ink-2);font-size:var(--type-sm);font-weight:var(--weight-bold);cursor:pointer;transition:all .15s ease}
.exam-filter-pill:hover{border-color:var(--line-strong);color:var(--ink)}
.exam-filter-pill.is-active{border-color:var(--primary);background:var(--primary-soft);color:var(--primary)}
.exam-filter-pill b{display:inline-grid;place-items:center;min-width:20px;height:20px;padding:0 .35rem;border-radius:999px;background:rgba(15,23,42,.07);font-size:var(--type-meta);font-weight:var(--weight-bold)}
.exam-filter-pill.is-active b{background:var(--primary-action);color:#fff}
@media(max-width:620px){.exam-row{padding:1rem}.modal-backdrop{padding:0}.modal-card{width:100vw;max-height:100dvh;height:100dvh;border-radius:0;padding:1rem}.modal-heading{top:-1rem}.modal-actions{bottom:-1rem}}
</style>

<style scoped>
/* Summary metrics share the same quiet card treatment across menus. */
.grade-summary { gap: .75rem; overflow: visible; background: transparent; border: 0; box-shadow: none; }
.grade-summary > div, .grade-summary > article { display: grid; align-content: center; gap: .25rem; min-width: 0; text-align: center; background: var(--surface); border: 1px solid var(--line); border-top: 3px solid #3157a4; border-radius: .8rem; box-shadow: none; }
.grade-summary > div:nth-child(2), .grade-summary > article:nth-child(2) { border-top-color: #7952a3; }
.grade-summary > div:nth-child(3), .grade-summary > article:nth-child(3) { border-top-color: #138a62; }
.grade-summary > div:nth-child(4), .grade-summary > article:nth-child(4) { border-top-color: #168782; }
.grade-summary > div::before, .grade-summary > article::before { content: none; }
.grade-summary > .score-summary-green { border-top-color: #138a62; }
.grade-summary > .score-summary-red { border-top-color: #b42318; }
.grade-summary > .score-summary-gold { border-top-color: #c28a27; }
</style>
