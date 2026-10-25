<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';

interface TeachingAssignment {
    id: number;
    class_group: { id: number; name: string; grade_level: number };
    subject: { id: number; name: string; code: string };
    teacher: { id: number; name: string };
}

interface SubmissionFile {
    id: number;
    original_filename: string;
}

interface Submission {
    id: number;
    content?: string | null;
    status: 'submitted' | 'late' | 'graded';
    submitted_at?: string | null;
    score?: string | number | null;
    feedback?: string | null;
    review_status?: 'pending' | 'approved' | 'needs_revision';
    revision_count?: number;
    files: SubmissionFile[];
    enrollment?: { student?: { nis: string; name: string } };
}

interface Material {
    id: number;
    title: string;
    description?: string | null;
    original_filename?: string | null;
    published_at?: string | null;
    teaching_assignment: TeachingAssignment;
}

interface Assignment {
    id: number;
    title: string;
    instructions?: string | null;
    due_at?: string | null;
    max_score: number;
    allow_late: boolean;
    revision_limit: number;
    teaching_assignment: TeachingAssignment;
    submissions: Submission[];
}

const props = defineProps<{
    active_term?: { id: number; name: string } | null;
    teaching_assignments: TeachingAssignment[];
    materials: Material[];
    assignments: Assignment[];
    capabilities: { manage: boolean; submit: boolean };
    read_material_ids: number[];
}>();

const activeTab = ref<'materials' | 'assignments'>('materials');
const showMaterialForm = ref(false);
const showAssignmentForm = ref(false);
const selectedAssignment = ref<Assignment | null>(null);
const selectedSubmission = ref<Submission | null>(null);

const materialForm = useForm({
    teaching_assignment_id: props.teaching_assignments[0]?.id ?? '',
    title: '',
    description: '',
    file: null as File | null,
});

const assignmentForm = useForm({
    teaching_assignment_id: props.teaching_assignments[0]?.id ?? '',
    title: '',
    instructions: '',
    due_at: '',
    max_score: 100,
    allow_late: true,
    revision_limit: 1,
});

const submissionForm = useForm({
    content: '',
    files: [] as File[],
});

const gradeForm = useForm({
    score: null as number | null,
    feedback: '',
    review_status: 'approved' as 'approved' | 'needs_revision',
});

const pendingCount = computed(() => props.assignments.filter((assignment) => assignment.submissions.length === 0).length);
const submissionCount = computed(() => props.assignments.reduce((total, assignment) => total + assignment.submissions.length, 0));

const formatDate = (value?: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'Tanpa batas waktu';

const handleMaterialFile = (event: Event) => {
    materialForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const handleSubmissionFiles = (event: Event) => {
    submissionForm.files = Array.from((event.target as HTMLInputElement).files ?? []);
};

const submitMaterial = () => {
    materialForm.post('/pembelajaran/materi', {
        forceFormData: true,
        onSuccess: () => {
            materialForm.reset();
            showMaterialForm.value = false;
        },
    });
};

const submitAssignment = () => {
    assignmentForm.post('/pembelajaran/tugas', {
        onSuccess: () => {
            assignmentForm.reset();
            showAssignmentForm.value = false;
        },
    });
};

const openSubmission = (assignment: Assignment) => {
    selectedAssignment.value = assignment;
    const existing = assignment.submissions[0];
    submissionForm.content = existing?.content ?? '';
    submissionForm.files = [];
};

const submitWork = () => {
    if (!selectedAssignment.value) return;
    submissionForm.post(`/pembelajaran/tugas/${selectedAssignment.value.id}/kumpulkan`, {
        forceFormData: true,
        onSuccess: () => {
            selectedAssignment.value = null;
            submissionForm.reset();
        },
    });
};

const openGrade = (submission: Submission) => {
    selectedSubmission.value = submission;
    gradeForm.score = submission.score === null || submission.score === undefined ? null : Number(submission.score);
    gradeForm.feedback = submission.feedback ?? '';
    gradeForm.review_status = submission.review_status === 'needs_revision' ? 'needs_revision' : 'approved';
};

const submitGrade = () => {
    if (!selectedSubmission.value) return;
    gradeForm.post(`/pembelajaran/pengumpulan/${selectedSubmission.value.id}/nilai`, {
        onSuccess: () => {
            selectedSubmission.value = null;
            gradeForm.reset();
        },
    });
};

const removeResource = (url: string) => {
    if (window.confirm('Hapus data ini? Tindakan ini tidak dapat dibatalkan.')) {
        router.delete(url, { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Pembelajaran">
        <Head title="Pembelajaran" />

        <main class="view space-y-6 learning-page">
            <section class="hero-card compact-hero">
                <div>
                    <p class="eyebrow">Ruang pembelajaran · {{ active_term?.name ?? 'Belum ada semester aktif' }}</p>
                    <h1>Materi & tugas kelas</h1>
                    <p>Kelola bahan ajar, tenggat tugas, pengumpulan siswa, dan umpan balik dalam satu ruang.</p>
                </div>
                <div v-if="capabilities.manage && teaching_assignments.length" class="hero-actions">
                    <Link class="secondary-button" href="/pembelajaran/progress">Lihat progress</Link>
                    <button class="secondary-button" type="button" @click="showMaterialForm = true">Tambah materi</button>
                    <button class="primary-button" type="button" @click="showAssignmentForm = true">Buat tugas</button>
                </div>
            </section>

            <section class="grade-summary">
                <div><span>Kelas mapel</span><strong>{{ teaching_assignments.length }}</strong><small>semester aktif</small></div>
                <div><span>Materi terbit</span><strong>{{ materials.length }}</strong><small>bahan ajar tersedia</small></div>
                <div><span>Tugas aktif</span><strong>{{ assignments.length }}</strong><small>{{ pendingCount }} belum dikumpulkan</small></div>
                <div><span>Pengumpulan</span><strong>{{ submissionCount }}</strong><small>respons siswa tercatat</small></div>
            </section>

            <section class="panel overflow-hidden">
                <div class="toolbar">
                    <div class="segmented-control" aria-label="Jenis konten">
                        <button type="button" :class="{ 'is-active': activeTab === 'materials' }" @click="activeTab = 'materials'">Materi</button>
                        <button type="button" :class="{ 'is-active': activeTab === 'assignments' }" @click="activeTab = 'assignments'">Tugas</button>
                    </div>
                    <span class="muted-copy">{{ activeTab === 'materials' ? materials.length : assignments.length }} item</span>
                </div>

                <div v-if="activeTab === 'materials'" class="resource-grid">
                    <article v-for="material in materials" :key="material.id" class="resource-card">
                        <div class="resource-icon">M</div>
                        <div class="resource-body">
                            <p class="eyebrow">{{ material.teaching_assignment.subject.name }} · {{ material.teaching_assignment.class_group.name }}</p>
                            <h2>{{ material.title }}</h2>
                            <p>{{ material.description || 'Materi pembelajaran tanpa deskripsi.' }}</p>
                            <small>Diterbitkan {{ formatDate(material.published_at) }}</small>
                        </div>
                        <div class="resource-actions">
                            <a v-if="material.original_filename" class="secondary-button" :href="`/pembelajaran/materi/${material.id}/unduh`">Unduh</a>
                            <button v-if="capabilities.submit && !read_material_ids.includes(material.id)" class="primary-button" type="button" @click="router.post(`/pembelajaran/materi/${material.id}/dibaca`, {}, { preserveScroll: true })">Tandai dibaca</button>
                            <span v-if="capabilities.submit && read_material_ids.includes(material.id)" class="read-badge">✓ Sudah dibaca</span>
                            <button v-if="capabilities.manage" class="danger-link" type="button" @click="removeResource(`/pembelajaran/materi/${material.id}`)">Hapus</button>
                        </div>
                    </article>
                    <div v-if="materials.length === 0" class="empty-state">
                        <strong>Belum ada materi</strong>
                        <p>Materi yang diterbitkan akan tampil di sini.</p>
                    </div>
                </div>

                <div v-else class="assignment-list">
                    <article v-for="assignment in assignments" :key="assignment.id" class="assignment-card">
                        <div class="assignment-main">
                            <p class="eyebrow">{{ assignment.teaching_assignment.subject.name }} · {{ assignment.teaching_assignment.class_group.name }}</p>
                            <h2>{{ assignment.title }}</h2>
                            <p>{{ assignment.instructions || 'Tidak ada instruksi tambahan.' }}</p>
                            <div class="assignment-meta">
                                <span>Tenggat {{ formatDate(assignment.due_at) }}</span>
                                <span>Nilai maksimal {{ assignment.max_score }}</span><span>Maks. revisi {{ assignment.revision_limit }}</span>
                                <span>{{ assignment.allow_late ? 'Terlambat diizinkan' : 'Tutup saat tenggat' }}</span>
                            </div>
                        </div>
                        <div class="resource-actions">
                            <button v-if="capabilities.submit" class="primary-button" type="button" @click="openSubmission(assignment)">
                                {{ assignment.submissions.length ? 'Perbarui jawaban' : 'Kumpulkan' }}
                            </button>
                            <button v-if="capabilities.manage" class="danger-link" type="button" @click="removeResource(`/pembelajaran/tugas/${assignment.id}`)">Hapus</button>
                        </div>

                        <div v-if="assignment.submissions.length" class="submission-list">
                            <div v-for="submission in assignment.submissions" :key="submission.id" class="submission-row">
                                <div>
                                    <strong>{{ submission.enrollment?.student?.name ?? 'Pengumpulan Anda' }}</strong>
                                    <small>{{ submission.enrollment?.student?.nis }} · {{ submission.review_status || submission.status }} · revisi {{ submission.revision_count || 0 }}</small>
                                </div>
                                <p>{{ submission.content || `${submission.files.length} berkas terlampir` }}</p>
                                <div class="submission-files">
                                    <a v-for="file in submission.files" :key="file.id" :href="`/pembelajaran/berkas/${file.id}/unduh`">{{ file.original_filename }}</a>
                                </div>
                                <div class="submission-score">
                                    <strong>{{ submission.score ?? '—' }}</strong>
                                    <button v-if="capabilities.manage" class="text-button" type="button" @click="openGrade(submission)">Nilai</button>
                                </div>
                            </div>
                        </div>
                    </article>
                    <div v-if="assignments.length === 0" class="empty-state">
                        <strong>Belum ada tugas</strong>
                        <p>Tugas kelas dan status pengumpulan akan tampil di sini.</p>
                    </div>
                </div>
            </section>
        </main>

        <div v-if="showMaterialForm" class="modal-backdrop" @click.self="showMaterialForm = false">
            <form class="modal-card" @submit.prevent="submitMaterial">
                <div class="modal-heading"><div><p class="eyebrow">Materi baru</p><h2>Terbitkan bahan ajar</h2></div><button type="button" @click="showMaterialForm = false">×</button></div>
                <label>Kelas dan mata pelajaran<select v-model="materialForm.teaching_assignment_id" required><option v-for="item in teaching_assignments" :key="item.id" :value="item.id">{{ item.subject.name }} · {{ item.class_group.name }}</option></select></label>
                <label>Judul<input v-model="materialForm.title" required maxlength="180" /></label>
                <label>Deskripsi<textarea v-model="materialForm.description" rows="4" /></label>
                <label>Berkas pendukung<input type="file" @change="handleMaterialFile" /></label>
                <p v-if="materialForm.hasErrors" class="form-error">Periksa kembali data materi yang diisi.</p>
                <div class="modal-actions"><button type="button" class="secondary-button" @click="showMaterialForm = false">Batal</button><button class="primary-button" :disabled="materialForm.processing">Terbitkan</button></div>
            </form>
        </div>

        <div v-if="showAssignmentForm" class="modal-backdrop" @click.self="showAssignmentForm = false">
            <form class="modal-card" @submit.prevent="submitAssignment">
                <div class="modal-heading"><div><p class="eyebrow">Tugas baru</p><h2>Buat aktivitas siswa</h2></div><button type="button" @click="showAssignmentForm = false">×</button></div>
                <label>Kelas dan mata pelajaran<select v-model="assignmentForm.teaching_assignment_id" required><option v-for="item in teaching_assignments" :key="item.id" :value="item.id">{{ item.subject.name }} · {{ item.class_group.name }}</option></select></label>
                <label>Judul<input v-model="assignmentForm.title" required maxlength="180" /></label>
                <label>Instruksi<textarea v-model="assignmentForm.instructions" rows="5" /></label>
                <div class="form-grid"><label>Tenggat<input v-model="assignmentForm.due_at" type="datetime-local" /></label><label>Nilai maksimal<input v-model.number="assignmentForm.max_score" type="number" min="1" max="1000" /></label></div>
                <label>Batas revisi<input v-model.number="assignmentForm.revision_limit" type="number" min="0" max="10" /></label>
                <label class="check-row"><input v-model="assignmentForm.allow_late" type="checkbox" /> Izinkan pengumpulan setelah tenggat</label>
                <div class="modal-actions"><button type="button" class="secondary-button" @click="showAssignmentForm = false">Batal</button><button class="primary-button" :disabled="assignmentForm.processing">Terbitkan tugas</button></div>
            </form>
        </div>

        <div v-if="selectedAssignment" class="modal-backdrop" @click.self="selectedAssignment = null">
            <form class="modal-card" @submit.prevent="submitWork">
                <div class="modal-heading"><div><p class="eyebrow">Pengumpulan tugas</p><h2>{{ selectedAssignment.title }}</h2></div><button type="button" @click="selectedAssignment = null">×</button></div>
                <label>Jawaban teks<textarea v-model="submissionForm.content" rows="7" placeholder="Tulis jawaban atau catatan untuk guru" /></label>
                <label>Lampiran (maksimal 5)<input type="file" multiple @change="handleSubmissionFiles" /></label>
                <p v-if="submissionForm.hasErrors" class="form-error">Isi jawaban teks atau pilih minimal satu berkas.</p>
                <div class="modal-actions"><button type="button" class="secondary-button" @click="selectedAssignment = null">Batal</button><button class="primary-button" :disabled="submissionForm.processing">Kirim tugas</button></div>
            </form>
        </div>

        <div v-if="selectedSubmission" class="modal-backdrop" @click.self="selectedSubmission = null">
            <form class="modal-card" @submit.prevent="submitGrade">
                <div class="modal-heading"><div><p class="eyebrow">Penilaian tugas</p><h2>{{ selectedSubmission.enrollment?.student?.name }}</h2></div><button type="button" @click="selectedSubmission = null">×</button></div>
                <label>Keputusan<select v-model="gradeForm.review_status"><option value="approved">Setujui & beri nilai</option><option value="needs_revision">Minta perbaikan</option></select></label>
                <label v-if="gradeForm.review_status === 'approved'">Nilai<input v-model.number="gradeForm.score" type="number" min="0" required /></label>
                <label>Umpan balik<textarea v-model="gradeForm.feedback" rows="5" /></label>
                <div class="modal-actions"><button type="button" class="secondary-button" @click="selectedSubmission = null">Batal</button><button class="primary-button" :disabled="gradeForm.processing">Simpan nilai</button></div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.compact-hero { display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; }
.hero-actions, .resource-actions, .modal-actions { display: flex; align-items: center; gap: .75rem; }
.toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--line, #e5e7eb); }
.segmented-control { display: inline-flex; padding: .25rem; border-radius: .75rem; background: #eef2ff; }
.segmented-control button { padding: .55rem 1rem; border-radius: .55rem; color: #64748b; font-weight: 700; }
.segmented-control .is-active { background: #fff; color: #4338ca; box-shadow: 0 1px 3px rgb(15 23 42 / .12); }
.resource-grid, .assignment-list { display: grid; gap: 1rem; padding: 1.25rem; }
.resource-grid { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
.resource-card, .assignment-card { display: flex; gap: 1rem; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.1rem; background: #fff; }
.assignment-card { flex-wrap: wrap; align-items: flex-start; }
.resource-icon { width: 2.75rem; height: 2.75rem; flex: 0 0 auto; display: grid; place-items: center; border-radius: .8rem; background: #eef2ff; color: #4f46e5; font-weight: 800; }
.resource-body, .assignment-main { min-width: 0; flex: 1; }
.resource-body h2, .assignment-main h2, .modal-heading h2 { margin: .2rem 0 .35rem; color: #0f172a; font-size: 1.05rem; font-weight: 800; }
.resource-body p, .assignment-main > p { color: #64748b; font-size: .875rem; }
.resource-body small { display: block; margin-top: .75rem; color: #94a3b8; }
.assignment-meta { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .9rem; }
.assignment-meta span { padding: .3rem .55rem; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: .72rem; font-weight: 700; }
.submission-list { width: 100%; display: grid; gap: .6rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
.submission-row { display: grid; grid-template-columns: minmax(150px, 1fr) 2fr 1fr auto; align-items: center; gap: 1rem; padding: .8rem; border-radius: .75rem; background: #f8fafc; font-size: .82rem; }
.submission-row small { display: block; color: #64748b; }
.submission-files { display: grid; gap: .2rem; }
.submission-files a { color: #4f46e5; text-decoration: underline; }
.submission-score { display: flex; align-items: center; gap: .5rem; }
.danger-link, .text-button { color: #dc2626; font-size: .78rem; font-weight: 700; }
.text-button { color: #4f46e5; }
.empty-state { grid-column: 1 / -1; padding: 3rem 1rem; text-align: center; color: #64748b; }
.empty-state strong { display: block; color: #334155; font-size: 1rem; }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .56); }
.modal-card { width: min(620px, 100%); max-height: 90vh; overflow-y: auto; display: grid; gap: 1rem; padding: 1.35rem; border-radius: 1rem; background: #fff; box-shadow: 0 24px 80px rgb(15 23 42 / .25); }
.modal-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.modal-heading > button { color: #64748b; font-size: 1.6rem; line-height: 1; }
.modal-card label { display: grid; gap: .4rem; color: #334155; font-size: .8rem; font-weight: 700; }
.modal-card input:not([type='checkbox']), .modal-card select, .modal-card textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: .65rem; padding: .65rem .75rem; font: inherit; font-weight: 500; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.check-row { display: flex !important; grid-template-columns: none; flex-direction: row; align-items: center; }
.modal-actions { justify-content: flex-end; padding-top: .5rem; }
.form-error { color: #dc2626; font-size: .8rem; }
.read-badge { color: #15803d; font-size: .78rem; font-weight: 800; }
@media (max-width: 760px) { .compact-hero, .resource-card { align-items: stretch; flex-direction: column; } .submission-row { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } }
</style>
