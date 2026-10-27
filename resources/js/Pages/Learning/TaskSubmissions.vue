<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';

interface FileItem { id: number; name: string; url: string }
interface SubmissionRow {
    id?: number;
    student: { name?: string | null; nis?: string | null };
    status: string;
    review_status?: string | null;
    submitted_at?: string | null;
    content?: string | null;
    score?: number | string | null;
    feedback?: string | null;
    files: FileItem[];
    grade_url?: string | null;
}

const props = defineProps<{
    assignment: { id: number; title: string; subject?: string | null; class_group?: string | null; due_at?: string | null; max_score: number };
    submissions: SubmissionRow[];
    back_url: string;
    assignment_url: string;
}>();

const selected = ref<SubmissionRow | null>(null);
const search = ref('');
const statusFilter = ref('all');
const gradeForm = useForm({ score: null as number | null, feedback: '', review_status: 'approved' as 'approved' | 'needs_revision' });

const filteredSubmissions = computed(() => props.submissions.filter((row) => {
    const query = search.value.toLowerCase();
    const matchesSearch = !query || `${row.student.name ?? ''} ${row.student.nis ?? ''} ${row.content ?? ''}`.toLowerCase().includes(query);
    const matchesStatus = statusFilter.value === 'all'
        || (statusFilter.value === 'not_submitted' && row.status === 'not_submitted')
        || (statusFilter.value === 'needs_revision' && row.review_status === 'needs_revision')
        || (statusFilter.value === 'graded' && (row.review_status === 'approved' || row.status === 'graded'))
        || (statusFilter.value === 'submitted' && row.status !== 'not_submitted' && row.review_status !== 'needs_revision' && row.review_status !== 'approved' && row.status !== 'graded');
    return matchesSearch && matchesStatus;
}));
const studentsCount = computed(() => props.submissions.length);
const submittedCount = computed(() => props.submissions.filter((row) => row.status !== 'not_submitted').length);
const gradedCount = computed(() => props.submissions.filter((row) => row.review_status === 'approved' || row.status === 'graded').length);
const needsGradingCount = computed(() => props.submissions.filter((row) => row.status !== 'not_submitted' && row.review_status !== 'approved' && row.status !== 'graded').length);
const notSubmittedCount = computed(() => props.submissions.filter((row) => row.status === 'not_submitted').length);
const openDetail = (row: SubmissionRow) => {
    selected.value = row;
    gradeForm.score = row.score === null || row.score === undefined ? null : Number(row.score);
    gradeForm.feedback = row.feedback ?? '';
    gradeForm.review_status = row.review_status === 'needs_revision' ? 'needs_revision' : 'approved';
};
const exportCsv = () => {
    const header = ['Siswa', 'NIS', 'Status', 'Tanggal kumpul', 'Nilai'];
    const rows = filteredSubmissions.value.map((row) => [row.student.name ?? '', row.student.nis ?? '', statusLabel(row), row.submitted_at ?? '', row.score ?? '']);
    const csv = [header, ...rows].map((line) => line.map((value) => `"${String(value).replaceAll('"', '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `pengumpulan-${props.assignment.title.toLowerCase().replace(/[^a-z0-9]+/g, '-')}.csv`;
    link.click();
    URL.revokeObjectURL(url);
};
const printPage = () => window.print();
const submitGrade = () => {
    if (!selected.value?.grade_url) return;
    gradeForm.post(selected.value.grade_url, { preserveScroll: true, onSuccess: () => { selected.value = null; gradeForm.reset(); } });
};
const statusLabel = (row: SubmissionRow) => {
    if (row.status === 'not_submitted') return 'Belum mengumpulkan';
    if (row.review_status === 'needs_revision') return 'Perlu perbaikan';
    if (row.review_status === 'approved' || row.status === 'graded') return 'Dinilai';
    if (row.status === 'late') return 'Terlambat';
    return 'Terkirim';
};
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '-';
</script>

<template>
    <AppLayout title="Pengumpulan Tugas">
        <Head :title="`Pengumpulan · ${assignment.title}`" />
        <main class="view space-y-6 task-submissions-page">
            <header class="page-heading">
                <div><p class="eyebrow">Penilaian tugas · {{ assignment.subject }} · Kelas {{ assignment.class_group }}</p><h1>{{ assignment.title }}</h1><p>Periksa jawaban, beri nilai, dan kirim umpan balik kepada siswa.</p></div>
                <div class="heading-actions"><Link class="secondary-button" :href="back_url">Kembali ke tugas</Link><Link class="primary-button" :href="assignment_url">Detail tugas</Link></div>
            </header>
            <section class="submission-stats">
                <article class="submission-stat submission-stat-blue"><span>Siswa</span><strong>{{ studentsCount }}</strong><small>terdaftar di kelas</small></article>
                <article class="submission-stat submission-stat-green"><span>Sudah kumpul</span><strong>{{ submittedCount }}</strong><small>jawaban diterima</small></article>
                <article class="submission-stat submission-stat-teal"><span>Sudah dinilai</span><strong>{{ gradedCount }}</strong><small>nilai tersimpan</small></article>
                <article class="submission-stat submission-stat-red"><span>Perlu dinilai</span><strong>{{ needsGradingCount }}</strong><small>menunggu pemeriksaan</small></article>
                <article class="submission-stat submission-stat-amber"><span>Belum kumpul</span><strong>{{ notSubmittedCount }}</strong><small>belum mengirim jawaban</small></article>
            </section>
            <section class="panel overflow-hidden">
                <div class="submission-toolbar">
                    <div><p class="eyebrow">Daftar pengumpulan</p><h2>Jawaban siswa</h2></div>
                    <div class="submission-tools"><label class="table-search"><span>⌕</span><input v-model="search" placeholder="Cari siswa" /></label><select v-model="statusFilter"><option value="all">Semua status</option><option value="submitted">Terkirim</option><option value="graded">Dinilai</option><option value="needs_revision">Perlu perbaikan</option><option value="not_submitted">Belum mengumpulkan</option></select><button class="secondary-button compact" type="button" @click="exportCsv">Excel</button><button class="secondary-button compact" type="button" @click="printPage">PDF</button></div>
                </div>
                <div class="table-scroll">
                    <table class="submission-table">
                        <thead><tr><th>Siswa</th><th>Status</th><th>Waktu</th><th>File / jawaban</th><th>Nilai</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <tr v-for="row in filteredSubmissions" :key="row.id ?? row.student.nis ?? row.student.name ?? 'row'">
                                <td><strong>{{ row.student.name || '-' }}</strong><small>{{ row.student.nis || '-' }}</small></td>
                                <td><span class="status-pill" :class="`status-${row.status}`">{{ statusLabel(row) }}</span></td>
                                <td>{{ formatDate(row.submitted_at) }}</td>
                                <td>
                                    <div v-if="row.content" class="answer-preview">{{ row.content }}</div>
                                    <div v-if="row.files.length" class="file-list"><a v-for="file in row.files" :key="file.id" :href="file.url">{{ file.name }}</a></div>
                                    <span v-if="!row.content && !row.files.length">-</span>
                                </td>
                                <td>{{ row.score ?? '—' }}</td>
                                <td><button v-if="row.id" class="secondary-button compact" type="button" @click="openDetail(row)">{{ row.score === null || row.score === undefined ? 'Buka' : 'Ubah nilai' }}</button></td>
                            </tr>
                            <tr v-if="filteredSubmissions.length === 0"><td colspan="6" class="empty-state">Tidak ada data yang sesuai dengan filter.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <div v-if="selected" class="modal-backdrop" @click.self="selected = null">
            <form class="modal-card" @submit.prevent="submitGrade">
                <div class="modal-heading"><div><p class="eyebrow">Detail pengumpulan</p><h2>{{ selected.student.name }}</h2></div><button type="button" @click="selected = null">×</button></div>
                <div class="submission-detail-meta"><span>Status: <strong>{{ statusLabel(selected) }}</strong></span><span>Tanggal kumpul: <strong>{{ formatDate(selected.submitted_at) }}</strong></span></div>
                <div v-if="selected.content" class="answer-box"><strong>Jawaban teks</strong><p>{{ selected.content }}</p></div>
                <div v-if="selected.files.length" class="file-list"><strong>Berkas jawaban</strong><a v-for="file in selected.files" :key="file.id" :href="file.url">{{ file.name }}</a></div>
                <p v-if="!selected.content && !selected.files.length" class="empty-state">Siswa belum melampirkan jawaban.</p>
                <label>Keputusan<select v-model="gradeForm.review_status"><option value="approved">Setujui & beri nilai</option><option value="needs_revision">Minta perbaikan</option></select></label>
                <label v-if="gradeForm.review_status === 'approved'">Nilai<input v-model.number="gradeForm.score" type="number" min="0" :max="assignment.max_score" required /></label>
                <label>Umpan balik<textarea v-model="gradeForm.feedback" rows="5" /></label>
                <div class="modal-actions"><button type="button" class="secondary-button" @click="selected = null">Batal</button><button class="primary-button" :disabled="gradeForm.processing">Simpan</button></div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.task-submissions-page { max-width: 1200px; margin: 0 auto; }
.page-heading, .heading-actions, .modal-heading, .modal-actions { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.heading-actions, .modal-actions { flex-wrap: wrap; }
.submission-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .8rem; }
.submission-stat { min-height: 105px; display: grid; align-content: center; gap: .15rem; padding: 1rem; border: 1px solid var(--line); border-radius: var(--radius-md); background: var(--surface); box-shadow: var(--shadow-sm); }
.submission-stat span, .submission-stat small { color: var(--ink-soft); font-size: .72rem; }
.submission-stat strong { color: var(--ink); font-size: 1.9rem; line-height: 1; }
.submission-stat-blue { border-top: 3px solid #5c8ff0; } .submission-stat-green { border-top: 3px solid #28b47d; } .submission-stat-teal { border-top: 3px solid #28c3b4; } .submission-stat-red { border-top: 3px solid #e76e73; } .submission-stat-amber { border-top: 3px solid #d9a73e; }
.submission-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--line); }
.submission-toolbar h2 { margin: .15rem 0 0; color: var(--ink); font-size: 1.12rem; }
.submission-tools { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; justify-content: flex-end; }
.table-search { min-height: 36px; width: 190px; display: flex; align-items: center; gap: .45rem; padding: 0 .65rem; color: var(--ink-faint); background: var(--surface-subtle); border: 1px solid var(--line); border-radius: .55rem; }
.table-search input { min-width: 0; width: 100%; color: var(--ink); background: transparent; border: 0; outline: 0; font-size: .76rem; }
.submission-tools select { min-height: 36px; color: var(--ink); background: var(--surface-subtle); border: 1px solid var(--line); border-radius: .55rem; padding: 0 .55rem; font-size: .76rem; }
.table-scroll { overflow-x: auto; }
.submission-table { width: 100%; min-width: 820px; border-collapse: collapse; font-size: .83rem; }
.submission-table th, .submission-table td { padding: .85rem 1rem; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
.submission-table th { background: var(--surface-subtle); color: var(--ink-faint); font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; }
.submission-table td strong, .submission-table td small { display: block; }
.submission-table td { color: var(--ink-soft); border-bottom-color: var(--line); }
.submission-table td strong { color: var(--ink); }
.submission-table td small { margin-top: .2rem; color: var(--ink-faint); }
.status-pill { display: inline-flex; padding: .3rem .55rem; border-radius: 999px; background: var(--blue-soft); color: var(--blue); font-size: .72rem; font-weight: 700; }
.status-not_submitted { background: var(--gold-soft); color: var(--gold); }
.status-late { background: var(--red-soft); color: var(--red); }
.answer-preview { max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #475569; }
.file-list { display: grid; gap: .2rem; }
.file-list a { color: var(--primary); text-decoration: underline; }
.compact { min-height: 32px; padding: .45rem .7rem; font-size: .75rem; }
.empty-state { padding: 2rem; text-align: center; color: var(--ink-faint); }
.submission-detail-meta { display: flex; flex-wrap: wrap; gap: .65rem; color: var(--ink-soft); font-size: .78rem; }
.answer-box { padding: .8rem; border-radius: .65rem; background: var(--surface-subtle); color: var(--ink-soft); }
.answer-box strong { display: block; color: var(--ink); font-size: .78rem; }
.answer-box p { max-height: 240px; margin: .45rem 0 0; overflow: auto; white-space: pre-wrap; line-height: 1.6; }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .56); }
.modal-card { width: min(620px, 100%); max-height: 90vh; overflow: auto; display: grid; gap: 1rem; padding: 1.35rem; color: var(--ink); background: var(--surface); border: 1px solid var(--line); border-radius: 1rem; box-shadow: 0 24px 80px rgb(0 0 0 / .3); }
.modal-heading h2 { margin: .2rem 0 0; color: var(--ink); font-size: 1.05rem; }
.modal-heading > button { color: var(--ink-soft); font-size: 1.6rem; }
.modal-card label { display: grid; gap: .4rem; color: var(--ink-soft); font-size: .8rem; font-weight: 700; }
.modal-card input, .modal-card select, .modal-card textarea { width: 100%; color: var(--ink); background: var(--surface-subtle); border: 1px solid var(--line-strong); border-radius: .65rem; padding: .65rem .75rem; font: inherit; }
@media (max-width: 980px) { .submission-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } .submission-toolbar { align-items: flex-start; flex-direction: column; } .submission-tools { width: 100%; justify-content: flex-start; } }
@media (max-width: 700px) { .page-heading { align-items: flex-start; flex-direction: column; } .submission-stats { grid-template-columns: 1fr 1fr; } .table-search { width: 100%; } }
</style>
