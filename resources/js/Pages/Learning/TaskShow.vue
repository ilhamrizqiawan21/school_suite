<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';

interface Assignment {
    id: number;
    title: string;
    instructions?: string | null;
    due_at?: string | null;
    max_score: number;
    allow_late: boolean;
    subject?: string | null;
    class_group?: string | null;
    teacher?: string | null;
}

interface SubmissionFile { id: number; name: string; url: string }
interface Submission { id: number; status: string; review_status?: string | null; content?: string | null; submitted_at?: string | null; score?: number | string | null; feedback?: string | null; files: SubmissionFile[] }

const props = defineProps<{
    assignment: Assignment;
    submission?: Submission | null;
    can_submit: boolean;
    can_manage: boolean;
    submit_url: string;
    submissions_url: string;
    back_url: string;
}>();

const form = useForm({ content: props.submission?.content ?? '', files: [] as File[] });
const fileInputKey = ref(0);
const taskStatus = computed(() => {
    if (props.submission?.review_status === 'needs_revision') return { label: 'Perlu diperbaiki', tone: 'danger' };
    if (props.submission?.review_status === 'approved' || props.submission?.status === 'graded') return { label: 'Dinilai', tone: 'success' };
    if (props.submission) return { label: 'Sudah dikumpulkan', tone: 'success' };
    if (props.assignment.due_at && new Date(props.assignment.due_at).getTime() < Date.now()) return { label: 'Terlambat', tone: 'danger' };
    return { label: 'Belum dikumpulkan', tone: 'warning' };
});

const handleFiles = (event: Event) => {
    form.files = Array.from((event.target as HTMLInputElement).files ?? []);
};

const submit = () => {
    form.post(props.submit_url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { form.reset('files'); fileInputKey.value += 1; },
    });
};

const formatDate = (value?: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'Tanpa batas waktu';
</script>

<template>
    <AppLayout title="Detail Tugas">
        <Head :title="`Tugas · ${assignment.title}`" />
        <main class="view space-y-6 task-detail-page">
            <header class="page-heading">
                <div>
                    <p class="eyebrow">{{ assignment.subject }} · Kelas {{ assignment.class_group }}</p>
                    <h1>{{ assignment.title }}</h1>
                    <p>{{ assignment.teacher ? `Pengajar: ${assignment.teacher}` : 'Detail tugas pembelajaran' }}</p>
                </div>
                <div class="heading-actions">
                    <Link class="secondary-button" :href="back_url">Kembali ke tugas</Link>
                    <Link v-if="can_manage" class="primary-button" :href="submissions_url">Lihat pengumpulan</Link>
                </div>
            </header>

            <section class="panel task-detail-card">
                <div class="task-meta-grid">
                    <div><span>Tenggat</span><strong>{{ formatDate(assignment.due_at) }}</strong></div>
                    <div><span>Nilai maksimal</span><strong>{{ assignment.max_score }}</strong></div>
                    <div><span>Status pengumpulan</span><strong class="task-status-pill" :class="`is-${taskStatus.tone}`">{{ taskStatus.label }}</strong></div>
                </div>
                <div class="task-instructions">
                    <span>Instruksi</span>
                    <p>{{ assignment.instructions || 'Tidak ada instruksi tambahan.' }}</p>
                </div>
            </section>

            <section v-if="submission" class="panel">
                <div class="section-heading"><div><p class="eyebrow">Riwayat pengumpulan</p><h2>Status jawaban</h2></div><strong class="status-pill" :class="`is-${taskStatus.tone}`">{{ taskStatus.label }}</strong></div>
                <p v-if="submission.submitted_at" class="submission-date">Dikirim {{ formatDate(submission.submitted_at) }}</p>
                <p v-if="submission.content" class="submission-content">{{ submission.content }}</p>
                <div v-if="submission.files.length" class="file-list"><a v-for="file in submission.files" :key="file.id" :href="file.url">{{ file.name }}</a></div>
                <div v-if="submission.score !== null && submission.score !== undefined" class="feedback-box"><strong>Nilai {{ submission.score }}</strong><span>{{ submission.feedback || 'Belum ada umpan balik.' }}</span></div>
            </section>

            <section v-if="can_submit" class="panel">
                <div class="section-heading"><div><p class="eyebrow">Pengumpulan siswa</p><h2>{{ submission ? 'Kirim revisi' : 'Kirim jawaban' }}</h2></div></div>
                <form class="submission-form" @submit.prevent="submit">
                    <label>Jawaban teks<textarea v-model="form.content" rows="7" placeholder="Tulis jawaban atau catatan untuk guru" /></label>
                    <label>Lampiran (maksimal 5 file)<input :key="fileInputKey" type="file" multiple accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg" @change="handleFiles" /></label>
                    <p class="form-hint">Format pengumpulan: PDF, JPG, JPEG. Maksimal 5 MB per file dan 20 MB total.</p>
                    <p v-if="form.hasErrors" class="form-error">Isi jawaban teks atau pilih minimal satu berkas.</p>
                    <div class="form-actions"><button class="primary-button" :disabled="form.processing">{{ form.processing ? 'Mengirim...' : 'Kirim tugas' }}</button></div>
                </form>
            </section>
            <section v-else-if="!can_manage" class="panel empty-state"><strong>Pengumpulan terkunci</strong><p>Jawaban sudah dikirim dan menunggu pemeriksaan guru.</p></section>
        </main>
    </AppLayout>
</template>

<style scoped>
.task-detail-page { max-width: 980px; margin: 0 auto; }
.page-heading, .section-heading, .heading-actions, .form-actions { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.heading-actions, .form-actions { flex-wrap: wrap; }
.task-detail-card { padding: 1.25rem; }
.task-meta-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .8rem; }
.task-meta-grid div { padding: .9rem; border-radius: .75rem; background: var(--surface-subtle); }
.task-meta-grid span, .task-instructions > span { display: block; color: var(--ink-soft); font-size: .75rem; font-weight: 700; }
.task-meta-grid strong { display: block; margin-top: .25rem; color: var(--ink); }
.task-instructions { margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--line); }
.task-instructions p, .submission-content { margin: .45rem 0 0; white-space: pre-wrap; color: var(--ink-soft); line-height: 1.6; }
.section-heading { padding: 1.2rem 1.25rem 0; }
.section-heading h2 { margin: .2rem 0; color: var(--ink); font-size: 1.1rem; }
.status-pill, .task-status-pill { display: inline-flex; width: fit-content; padding: .35rem .65rem; border-radius: 999px; background: var(--blue-soft); color: var(--blue); font-size: .75rem; font-weight: 800; }
.status-pill { margin-right: 1.25rem; }
.status-pill.is-success, .task-status-pill.is-success { color: var(--green); background: var(--green-soft); }
.status-pill.is-warning, .task-status-pill.is-warning { color: #a16207; background: #fef3c7; }
.status-pill.is-danger, .task-status-pill.is-danger { color: var(--red); background: var(--red-soft); }
.submission-date { margin: .9rem 1.25rem 0; color: var(--ink-faint); font-size: .75rem; }
.submission-content, .file-list, .feedback-box { margin: 1rem 1.25rem 0; }
.file-list { display: flex; flex-wrap: wrap; gap: .5rem; }
.file-list a { color: var(--primary); text-decoration: underline; }
.feedback-box { display: grid; gap: .25rem; padding: .8rem; border-radius: .75rem; background: var(--green-soft); color: var(--green); }
.submission-form { display: grid; gap: 1rem; padding: 1.25rem; }
.submission-form label { display: grid; gap: .45rem; color: var(--ink-soft); font-size: .8rem; font-weight: 700; }
.submission-form textarea, .submission-form input[type='file'] { width: 100%; color: var(--ink); background: var(--surface-subtle); border: 1px solid var(--line-strong); border-radius: .65rem; padding: .7rem .8rem; font: inherit; font-weight: 500; }
.form-hint { margin: -.5rem 0 0; color: var(--ink-faint); font-size: .75rem; }
.form-error { color: var(--red); font-size: .8rem; }
.empty-state { padding: 2rem; color: var(--ink-soft); text-align: center; }
@media (max-width: 700px) { .page-heading, .section-heading { align-items: flex-start; flex-direction: column; } .task-meta-grid { grid-template-columns: 1fr; } .status-pill { margin-right: 0; } }
</style>
