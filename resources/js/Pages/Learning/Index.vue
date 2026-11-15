<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import RowActionButton from '@/components/RowActionButton.vue';
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

interface TaskStats {
    active_tasks: number;
    target_classes: number;
    submissions: number;
    needs_grading: number;
    overdue: number;
}

interface TaskClass {
    id: number;
    class_group?: string | null;
    subject?: string | null;
    student_count: number;
    assignment_count: number;
    submission_count: number;
    needs_grading: number;
    overdue: number;
    latest_title?: string | null;
}

const props = defineProps<{
    section?: 'all' | 'materials' | 'assignments';
    active_term?: { id: number; name: string } | null;
    teaching_assignments: TeachingAssignment[];
    materials: Material[];
    assignments: Assignment[];
    task_stats?: TaskStats;
    task_classes?: TaskClass[];
    capabilities: { manage: boolean; submit: boolean };
    read_material_ids: number[];
}>();

const activeTab = ref<'materials' | 'assignments'>(props.section === 'assignments' ? 'assignments' : 'materials');
const showMaterialForm = ref(false);
const showAssignmentForm = ref(false);
const selectedAssignment = ref<Assignment | null>(null);
const selectedSubmission = ref<Submission | null>(null);
const activeOverlay = computed(() => showMaterialForm.value || showAssignmentForm.value || !!selectedAssignment.value || !!selectedSubmission.value);
const overlayRoot = ref<HTMLElement | null>(null);
let overlayTrigger: HTMLElement | null = null;
watch(activeOverlay, async (open) => {
    if (open) {
        overlayTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        await nextTick();
        overlayRoot.value?.querySelector<HTMLElement>('input:not([type="hidden"]), select, textarea, button')?.focus();
    } else {
        await nextTick();
        overlayTrigger?.focus();
        overlayTrigger = null;
    }
});
const handleOverlayKeydown = (event: KeyboardEvent) => {
    if (!activeOverlay.value) return;
    if (event.key === 'Escape') {
        selectedSubmission.value = null;
        selectedAssignment.value = null;
        showAssignmentForm.value = false;
        showMaterialForm.value = false;
        return;
    }
    if (event.key !== 'Tab' || !overlayRoot.value) return;
    const focusable = Array.from(overlayRoot.value.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href]'));
    const first = focusable[0];
    const last = focusable.at(-1);
    if (first && last && ((event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last))) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
    }
};
onMounted(() => document.addEventListener('keydown', handleOverlayKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', handleOverlayKeydown));

const materialForm = useForm({
    teaching_assignment_id: props.teaching_assignments[0]?.id ?? '',
    title: '',
    description: '',
    file: null as File | null,
});

const assignmentForm = useForm({
    teaching_assignment_id: props.teaching_assignments[0]?.id ?? '',
    teaching_assignment_ids: props.teaching_assignments.length ? [props.teaching_assignments[0].id] : [] as number[],
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
const isTaskSection = computed(() => props.section === 'assignments');
const isMaterialSection = computed(() => props.section === 'materials');
const studentEndpoint = (path: string) => props.capabilities.submit ? `/siswa${path}` : `/pembelajaran${path}`;
const taskSearch = ref('');
const page = usePage();
const taskClassView = computed(() => {
    const id = new URL(page.url, 'http://localhost').searchParams.get('kelas');
    return taskClasses.value.find(item => String(item.id) === id) ?? null;
});
const classSearch = ref('');
const materialSearch = ref('');
const firstStudentContentId = props.materials[0]?.teaching_assignment.id
    ?? props.assignments[0]?.teaching_assignment.id
    ?? props.teaching_assignments[0]?.id
    ?? '';
const materialClassId = ref<number | ''>(props.capabilities.submit ? '' : (props.teaching_assignments[0]?.id ?? ''));
const initialStudentAssignmentId = (() => {
    const subjectParam = new URL(page.url, 'http://localhost').searchParams.get('subject');
    if (subjectParam) {
        const matched = props.teaching_assignments.find((item) => String(item.subject.id) === subjectParam);
        if (matched) return matched.id;
    }
    return '' as number | '';
})();
const selectedStudentAssignmentId = ref<number | ''>(initialStudentAssignmentId);
const studentSearch = ref('');
const studentTaskFilter = ref<'all' | 'pending' | 'submitted' | 'revision' | 'overdue'>('all');
const selectedTaskClassIds = ref<number[]>(props.teaching_assignments.length ? [props.teaching_assignments[0].id] : []);
const taskStats = computed<TaskStats>(() => props.task_stats ?? {
    active_tasks: props.assignments.length,
    target_classes: new Set(props.assignments.map((assignment) => assignment.teaching_assignment.id)).size,
    submissions: submissionCount.value,
    needs_grading: 0,
    overdue: 0,
});
const taskClasses = computed(() => props.task_classes ?? []);
const filteredTaskClasses = computed(() => taskClasses.value.filter((item) => `${item.class_group ?? ''} ${item.subject ?? ''}`.toLowerCase().includes(classSearch.value.toLowerCase())));
const filteredAssignments = computed(() => props.assignments.filter((assignment) => {
    const query = taskSearch.value.toLowerCase();
    return (!taskClassView.value || assignment.teaching_assignment.id === taskClassView.value.id) && (!query || `${assignment.title} ${assignment.teaching_assignment.class_group.name} ${assignment.teaching_assignment.subject.name}`.toLowerCase().includes(query));
}));
const selectedMaterialClass = computed(() => props.teaching_assignments.find((item) => item.id === materialClassId.value) ?? props.teaching_assignments[0]);
const selectedStudentAssignment = computed(() =>
    selectedStudentAssignmentId.value === ''
        ? null
        : (props.teaching_assignments.find((item) => item.id === selectedStudentAssignmentId.value) ?? null),
);
const studentMaterials = computed(() => {
    const query = studentSearch.value.trim().toLowerCase();
    return props.materials.filter((material) => {
        const matchesSubject = selectedStudentAssignmentId.value === '' || material.teaching_assignment.id === selectedStudentAssignmentId.value;
        const matchesQuery = !query || `${material.title} ${material.description ?? ''} ${material.teaching_assignment.subject.name}`.toLowerCase().includes(query);
        return matchesSubject && matchesQuery;
    });
});
const studentAssignmentsBase = computed(() => {
    const query = studentSearch.value.trim().toLowerCase();
    return props.assignments.filter((assignment) => {
        const matchesSubject = selectedStudentAssignmentId.value === '' || assignment.teaching_assignment.id === selectedStudentAssignmentId.value;
        const matchesQuery = !query || `${assignment.title} ${assignment.instructions ?? ''} ${assignment.teaching_assignment.subject.name}`.toLowerCase().includes(query);
        return matchesSubject && matchesQuery;
    });
});
const studentAssignments = computed(() => studentAssignmentsBase.value.filter((assignment) => {
    const submission = assignment.submissions[0];
    if (studentTaskFilter.value === 'pending') return !submission;
    if (studentTaskFilter.value === 'submitted') return Boolean(submission);
    if (studentTaskFilter.value === 'revision') return submission?.review_status === 'needs_revision';
    if (studentTaskFilter.value === 'overdue') return !submission && Boolean(assignment.due_at) && new Date(assignment.due_at!).getTime() < Date.now();
    return true;
}));
const filteredMaterials = computed(() => props.materials.filter((material) => {
    const matchesClass = materialClassId.value === '' || material.teaching_assignment.id === materialClassId.value;
    const query = materialSearch.value.toLowerCase();
    const matchesSearch = !query || `${material.title} ${material.description ?? ''} ${material.original_filename ?? ''}`.toLowerCase().includes(query);
    return matchesClass && matchesSearch;
}));
const assignmentProgress = (assignment: Assignment) => {
    const target = props.teaching_assignments.find((item) => item.id === assignment.teaching_assignment.id);
    const classSize = target ? (taskClasses.value.find((item) => item.id === target.id)?.student_count ?? 0) : 0;
    return Math.min(100, classSize > 0 ? Math.round((assignment.submissions.length / classSize) * 100) : 0);
};

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
    assignmentForm.teaching_assignment_ids = selectedTaskClassIds.value.length ? selectedTaskClassIds.value : [Number(assignmentForm.teaching_assignment_id)];
    assignmentForm.post('/pembelajaran/tugas', {
        onSuccess: () => {
            assignmentForm.reset();
            showAssignmentForm.value = false;
        },
    });
};

const toggleAllTaskClasses = () => {
    selectedTaskClassIds.value = selectedTaskClassIds.value.length === props.teaching_assignments.length
        ? []
        : props.teaching_assignments.map((item) => item.id);
};

const formatShortDate = (value?: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
    : 'Tanpa batas waktu';

const studentTaskStatus = (assignment: Assignment) => {
    const submission = assignment.submissions[0];
    if (submission?.review_status === 'needs_revision') return { label: 'Perlu diperbaiki', tone: 'danger' };
    if (submission?.review_status === 'approved' || submission?.status === 'graded') return { label: 'Dinilai', tone: 'success' };
    if (submission) return { label: 'Sudah dikumpulkan', tone: 'success' };
    if (assignment.due_at && new Date(assignment.due_at).getTime() < Date.now()) return { label: 'Terlambat', tone: 'danger' };
    return { label: 'Belum dikumpulkan', tone: 'warning' };
};

const openSubmission = (assignment: Assignment) => {
    selectedAssignment.value = assignment;
    const existing = assignment.submissions[0];
    submissionForm.content = existing?.content ?? '';
    submissionForm.files = [];
};

const submitWork = () => {
    if (!selectedAssignment.value) return;
    const action = selectedAssignment.value.submissions.length ? 'Kirim revisi' : 'Kirim jawaban';
    if (!window.confirm(`${action} untuk tugas “${selectedAssignment.value.title}”? Jawaban akan dicatat untuk diperiksa guru.`)) return;
    submissionForm.post(studentEndpoint(`/tugas/${selectedAssignment.value.id}/${props.capabilities.submit ? 'kumpul' : 'kumpulkan'}`), {
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

        <div class="view space-y-6 learning-page" :class="{ 'student-learning-page': capabilities.submit }">
            <section class="hero-card compact-hero">
                <div>
                    <p class="eyebrow">{{ capabilities.submit ? 'Pembelajaran siswa' : (isMaterialSection && selectedMaterialClass ? 'Kelas & mapel' : 'Ruang pembelajaran') }} · {{ active_term?.name ?? 'Belum ada semester aktif' }}</p>
                    <h1>{{ capabilities.submit ? (selectedStudentAssignment ? `${selectedStudentAssignment.subject.name} · ${selectedStudentAssignment.class_group.name}` : (activeTab === 'materials' ? 'Materi pembelajaran' : 'Tugas saya')) : (isMaterialSection && selectedMaterialClass ? `${selectedMaterialClass.subject.name} · ${selectedMaterialClass.class_group.name}` : (activeTab === 'materials' ? 'Materi pembelajaran' : 'Tugas')) }}</h1>
                    <p>{{ capabilities.submit ? (activeTab === 'materials' ? 'Pilih mata pelajaran untuk melihat materi yang sesuai dengan kelasmu.' : 'Pantau tenggat, status pengumpulan, dan nilai tugas dari satu tempat.') : (activeTab === 'materials' ? 'Kelola materi pembelajaran untuk kelas ini dengan alur unggah dan unduh yang sederhana.' : 'Buat tugas, pantau pengumpulan siswa, dan beri nilai atau umpan balik.') }}</p>
                </div>
                <div v-if="capabilities.manage && teaching_assignments.length && !isTaskSection && !isMaterialSection" class="hero-actions">
                    <Link class="secondary-button ui-button ui-button--secondary ui-button--md" href="/pembelajaran/progress">Lihat progress</Link>
                    <AppButton variant="primary" icon="plus" v-if="activeTab === 'materials'" class="primary-button" type="button" @click="showMaterialForm = true">Tambah materi</AppButton>
                    <AppButton variant="primary" v-else class="primary-button" type="button" @click="showAssignmentForm = true">Buat tugas</AppButton>
                </div>
                <div v-else-if="capabilities.submit" class="hero-actions">
                    <Link class="secondary-button ui-button ui-button--secondary ui-button--md" href="/siswa/progress">Progress belajar</Link>
                </div>
            </section>

            <nav v-if="capabilities.manage" class="class-workspace-nav" aria-label="Alur kerja kelas dan mata pelajaran">
                <Link class="ui-tab-button workspace-tab" href="/pembelajaran/progress">Ringkasan</Link>
                <Link class="ui-tab-button workspace-tab" :class="{ 'is-current': activeTab === 'materials' }" href="/pembelajaran/materi">Materi</Link>
                <Link class="ui-tab-button workspace-tab" :class="{ 'is-current': activeTab === 'assignments' }" href="/pembelajaran/tugas">Tugas</Link>
                <Link class="ui-tab-button workspace-tab" href="/penilaian/skor">Nilai</Link>
                <Link class="ui-tab-button workspace-tab" href="/akademik/presensi">Absensi</Link>
                <Link class="ui-tab-button workspace-tab" href="/komunikasi/chat">Chat</Link>
            </nav>

            <nav v-else-if="capabilities.submit" class="class-workspace-nav" aria-label="Navigasi belajar siswa">
                <Link class="ui-tab-button workspace-tab" :class="{ 'is-current': activeTab === 'materials' }" href="/siswa/materi">Materi Pelajaran</Link>
                <Link class="ui-tab-button workspace-tab" :class="{ 'is-current': activeTab === 'assignments' }" href="/siswa/tugas">Tugas Saya</Link>
                <Link class="ui-tab-button workspace-tab" href="/siswa/progress">Progress Belajar</Link>
                <Link class="ui-tab-button workspace-tab" href="/akademik/kelas-daring">Kelas Daring</Link>
                <Link class="ui-tab-button workspace-tab" href="/komunikasi/chat">Diskusi Kelas</Link>
            </nav>

            <section v-if="capabilities.manage && !isTaskSection && !isMaterialSection" class="ss-summary-grid" :class="{ 'ss-summary-grid--3': section === 'materials' }">
                <MetricSummaryCard
                    label="Kelas mapel"
                    :value="teaching_assignments.length"
                    note="semester aktif"
                    tone="blue"
                    icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"
                />
                <MetricSummaryCard
                    label="Materi terbit"
                    :value="materials.length"
                    note="bahan ajar tersedia"
                    tone="violet"
                    icon="M4 5.5A2.5 2.5 0 016.5 3H20v16H6.5A2.5 2.5 0 014 16.5v-11zM4 16.5A2.5 2.5 0 016.5 14H20M8 7h8M8 10h6"
                />
                <MetricSummaryCard
                    v-if="section !== 'materials'"
                    label="Tugas aktif"
                    :value="assignments.length"
                    :note="`${pendingCount} belum dikumpulkan`"
                    tone="amber"
                    icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                />
                <MetricSummaryCard
                    v-if="section !== 'materials'"
                    label="Pengumpulan"
                    :value="submissionCount"
                    note="respons siswa tercatat"
                    tone="green"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </section>

            <section v-if="capabilities.submit" class="ss-summary-grid ss-summary-grid--3">
                <MetricSummaryCard
                    v-if="activeTab === 'materials'"
                    label="Materi tersedia"
                    :value="studentMaterials.length"
                    note="untuk mapel terpilih"
                    tone="violet"
                    icon="M4 5.5A2.5 2.5 0 016.5 3H20v16H6.5A2.5 2.5 0 014 16.5v-11zM4 16.5A2.5 2.5 0 016.5 14H20M8 7h8M8 10h6"
                />
                <MetricSummaryCard
                    v-if="activeTab !== 'materials'"
                    label="Tugas tersedia"
                    :value="studentAssignments.length"
                    note="di mapel terpilih"
                    tone="blue"
                    icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                />
                <MetricSummaryCard
                    v-if="activeTab !== 'materials'"
                    label="Sudah dikumpulkan"
                    :value="studentAssignments.filter((assignment) => assignment.submissions.length).length"
                    note="tugas terkirim"
                    tone="green"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    v-if="activeTab !== 'materials'"
                    label="Belum dikumpulkan"
                    :value="studentAssignments.filter((assignment) => !assignment.submissions.length).length"
                    note="periksa tenggat"
                    tone="amber"
                    icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </section>

            <template v-if="isMaterialSection && capabilities.manage">
                <section class="material-upload-panel panel">
                    <div class="task-panel-heading"><div><p class="eyebrow">Unggah materi</p><h2>Bagikan bahan ajar</h2><p>Materi yang diterbitkan hanya dapat dilihat oleh pengguna yang memiliki akses kelas.</p></div><span class="task-panel-icon material-panel-icon">↥</span></div>
                    <form class="material-inline-form" @submit.prevent="submitMaterial">
                        <label>Kelas dan mata pelajaran<select v-model="materialForm.teaching_assignment_id" required><option v-for="item in teaching_assignments" :key="item.id" :value="item.id">{{ item.class_group.name }} · {{ item.subject.name }}</option></select></label>
                        <label>Judul *<input v-model="materialForm.title" required maxlength="180" placeholder="Contoh: Modul pembelajaran minggu ini" /></label>
                        <label>Deskripsi<textarea v-model="materialForm.description" rows="4" placeholder="Tambahkan keterangan singkat untuk siswa." /></label>
                        <label>File materi *<input type="file" accept=".pdf,.docx,.jpg,.jpeg,.png,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png" required @change="handleMaterialFile" /><small>PDF, DOCX, JPG, JPEG, PNG · Maks. 10 MB</small></label>
                        <p v-if="materialForm.hasErrors" class="form-error">Periksa kembali data materi dan format file yang dipilih.</p>
                        <AppButton variant="primary" :loading="materialForm.processing" class="material-upload-button" type="submit" :disabled="materialForm.processing">{{ materialForm.processing ? 'Mengunggah...' : 'Unggah materi' }}</AppButton>
                    </form>
                </section>

                <section class="material-list-panel panel">
                    <div class="task-panel-heading material-list-heading"><div><p class="eyebrow">Daftar materi</p><h2>Materi pembelajaran</h2><p>{{ filteredMaterials.length }} materi tersedia untuk kelas yang dipilih.</p></div><div class="material-list-tools"><select v-model="materialClassId" aria-label="Pilih kelas dan mata pelajaran"><option v-for="item in teaching_assignments" :key="item.id" :value="item.id">{{ item.class_group.name }} · {{ item.subject.name }}</option></select><label class="task-search search-control-shell"><span>⌕</span><input v-model="materialSearch" type="search" placeholder="Cari materi" /></label></div></div>
                    <div v-if="filteredMaterials.length" class="material-resource-grid">
                        <article v-for="material in filteredMaterials" :key="material.id" class="material-resource-card">
                            <div class="material-resource-icon">▤</div><div class="material-resource-body"><small>{{ material.teaching_assignment.class_group.name }} · {{ material.teaching_assignment.subject.name }}</small><h3>{{ material.title }}</h3><p>{{ material.description || 'Tidak ada deskripsi tambahan.' }}</p><span>{{ material.original_filename || 'Berkas materi' }} · {{ formatDate(material.published_at) }}</span></div><div class="material-resource-actions"><AppButton variant="secondary" as="a" class="material-download-button" :href="`/pembelajaran/materi/${material.id}/unduh`">Unduh</AppButton><RowActionButton v-if="capabilities.manage" action="delete" label="Hapus" :aria-label="`Hapus materi ${material.title}`" @click="removeResource(`/pembelajaran/materi/${material.id}`)" /></div>
                        </article>
                    </div>
                    <div v-else class="material-empty-state"><div class="material-empty-icon">▤</div><strong>Belum ada materi</strong><p>Unggah materi pertama untuk kelas dan mata pelajaran ini.</p></div>
                </section>
            </template>

            <template v-else-if="isTaskSection && capabilities.manage">
                <section v-if="!taskClassView" class="task-dashboard-stats" aria-label="Ringkasan tugas">
                    <article class="task-stat task-stat-green"><span>Tugas aktif</span><strong>{{ taskStats.active_tasks }}</strong><small>tugas yang diterbitkan</small></article>
                    <article class="task-stat task-stat-blue"><span>Penugasan</span><strong>{{ taskStats.target_classes }}</strong><small>kelas dan mata pelajaran</small></article>
                    <article class="task-stat task-stat-teal"><span>Pengumpulan</span><strong>{{ taskStats.submissions }}</strong><small>jawaban siswa</small></article>
                    <article class="task-stat task-stat-red"><span>Perlu dinilai</span><strong>{{ taskStats.needs_grading }}</strong><small>menunggu pemeriksaan</small></article>
                    <article class="task-stat task-stat-amber"><span>Lewat batas waktu</span><strong>{{ taskStats.overdue }}</strong><small>perlu ditindaklanjuti</small></article>
                </section>

                <section v-if="!taskClassView" class="task-create-panel panel">
                    <div class="task-panel-heading"><div><p class="eyebrow">Buat tugas baru</p><h2>Bagikan aktivitas ke kelas</h2><p>Pilih satu atau beberapa kelas tujuan. Sistem membuat satu tugas untuk setiap kelas yang dipilih.</p></div><span class="task-panel-icon">＋</span></div>
                    <form class="task-create-form" @submit.prevent="submitAssignment">
                        <div class="task-form-main">
                            <label>Judul tugas *<input v-model="assignmentForm.title" required maxlength="180" placeholder="Contoh: Mencari ayat nama-nama Al-Qur'an" /></label>
                            <label>Deskripsi / instruksi<textarea v-model="assignmentForm.instructions" rows="4" placeholder="Jelaskan pekerjaan, sumber belajar, dan bentuk jawaban yang diharapkan." /></label>
                            <div class="task-form-grid"><label>Deadline<input v-model="assignmentForm.due_at" type="datetime-local" /></label><label>Nilai maksimal<input v-model.number="assignmentForm.max_score" type="number" min="1" max="1000" /></label></div>
                            <label class="task-check-row"><input v-model="assignmentForm.allow_late" type="checkbox" /> Izinkan pengumpulan setelah batas waktu</label>
                        </div>
                        <div class="task-class-picker">
                            <div class="task-picker-heading"><div><strong>Pilih kelas tujuan *</strong><small>{{ selectedTaskClassIds.length }} kelas dipilih</small></div><AppButton variant="ghost" size="sm" type="button" class="text-button" @click="toggleAllTaskClasses">{{ selectedTaskClassIds.length === teaching_assignments.length ? 'Batal pilih semua' : 'Pilih semua' }}</AppButton></div>
                            <label class="task-search search-control-shell"><span>⌕</span><input v-model="classSearch" type="search" placeholder="Cari kelas atau mapel" /></label>
                            <div class="task-class-options">
                                <label v-for="item in filteredTaskClasses" :key="item.id" class="task-class-option" :class="{ 'is-selected': selectedTaskClassIds.includes(item.id) }">
                                    <input v-model="selectedTaskClassIds" type="checkbox" :value="item.id" />
                                    <span><strong>{{ item.class_group }} · {{ item.subject }}</strong><small>{{ item.assignment_count }} tugas · {{ item.submission_count }} pengumpulan<span v-if="item.needs_grading"> · {{ item.needs_grading }} perlu dinilai</span></small></span>
                                </label>
                                <p v-if="filteredTaskClasses.length === 0" class="task-empty-copy">Kelas atau mata pelajaran tidak ditemukan.</p>
                            </div>
                        </div>
                        <div class="task-form-footer"><p v-if="assignmentForm.hasErrors" class="form-error">Pilih minimal satu kelas dan periksa kembali data tugas.</p><AppButton variant="primary" class="task-save-button" type="submit" :disabled="assignmentForm.processing || selectedTaskClassIds.length === 0">{{ assignmentForm.processing ? 'Menyimpan...' : 'Simpan tugas' }}</AppButton></div>
                    </form>
                </section>

                <section v-if="!taskClassView" class="task-class-panel panel">
                    <div class="task-panel-heading"><div><p class="eyebrow">Kelas dan mata pelajaran</p><h2>Ringkasan penugasan</h2><p>Pilih kelas untuk melihat daftar tugas dan progres pengumpulan.</p></div><label class="task-search search-control-shell task-search-wide"><span>⌕</span><input v-model="taskSearch" type="search" placeholder="Cari tugas, kelas, atau mapel" /></label></div>
                    <div class="task-class-grid">
                        <article v-for="item in filteredTaskClasses" :key="item.id" class="task-class-card" :class="{ 'is-highlighted': selectedTaskClassIds.includes(item.id) }">
                            <div class="task-class-card-top"><div><small>SEMESTER AKTIF</small><h3>{{ item.class_group }}</h3><p>{{ item.subject }}</p></div><span class="task-class-bookmark">▣</span></div>
                            <div class="task-class-metrics"><span><strong>{{ item.assignment_count }}</strong> tugas</span><span><strong>{{ item.submission_count }}</strong> kumpul</span><span><strong>{{ item.needs_grading }}</strong> dinilai</span><span><strong>{{ item.overdue }}</strong> lewat</span></div>
                            <p class="task-class-latest">{{ item.latest_title || 'Belum ada tugas diterbitkan' }}</p>
                            <Link class="task-card-action ui-button ui-button--secondary ui-button--md" :href="`/pembelajaran/tugas?kelas=${item.id}`">Lihat tugas kelas →</Link>
                        </article>
                    </div>
                </section>

                <section v-if="taskClassView" class="task-list-panel panel">
                    <Link href="/pembelajaran/tugas" class="secondary-button ui-button ui-button--secondary ui-button--md">← Kembali ke daftar kelas</Link>
                    <p class="task-empty-copy">{{ taskClassView.class_group }} · {{ taskClassView.subject }}</p>
                    <div class="task-panel-heading"><div><p class="eyebrow">Daftar tugas</p><h2>Tugas yang diterbitkan</h2></div><label class="task-search search-control-shell task-search-wide"><span>⌕</span><input v-model="taskSearch" type="search" placeholder="Cari tugas" /></label></div>
                    <div class="task-table-wrap table-scroll" tabindex="0" role="region" aria-label="Daftar tugas yang diterbitkan">
                        <table class="app-table task-table"><thead><tr><th scope="col">Judul</th><th scope="col">Kelas</th><th scope="col">Deadline</th><th scope="col">Pengumpulan</th><th scope="col">Aksi</th></tr></thead><tbody>
                            <tr v-for="assignment in filteredAssignments" :key="assignment.id">
                                <td><strong>{{ assignment.title }}</strong><small>{{ assignment.instructions || 'Tanpa deskripsi tambahan.' }}</small></td>
                                <td><span class="task-subject-chip">{{ assignment.teaching_assignment.class_group.name }}</span><small>{{ assignment.teaching_assignment.subject.name }}</small></td>
                                <td><span class="task-deadline" :class="{ 'is-overdue': assignment.due_at && new Date(assignment.due_at).getTime() < Date.now() }">{{ formatShortDate(assignment.due_at) }}</span></td>
                                <td><div class="task-progress-label"><span>{{ assignment.submissions.length }} terkumpul</span><strong>{{ assignmentProgress(assignment) }}%</strong></div><div class="task-progress-track"><i :style="{ width: `${assignmentProgress(assignment)}%` }" /></div></td>
                                <td><div class="task-row-actions"><Link class="task-outline-button ui-button ui-button--secondary ui-button--sm" :href="`/pembelajaran/tugas/${assignment.id}/pengumpulan`">Nilai</Link><Link class="task-icon-button ui-button ui-button--secondary ui-button--sm ui-button--icon-only" :href="`/pembelajaran/tugas/${assignment.id}`" aria-label="Buka detail tugas">↗</Link><AppButton variant="danger" size="sm" icon-only icon="delete" class="task-icon-button danger" type="button" aria-label="Hapus tugas" @click="removeResource(`/pembelajaran/tugas/${assignment.id}`)">⌫</AppButton></div></td>
                            </tr>
                            <tr v-if="filteredAssignments.length === 0"><td colspan="5" class="task-empty-row">Belum ada tugas pada kelas atau mapel yang dipilih.</td></tr>
                        </tbody></table>
                    </div>
                </section>
            </template>

            <template v-else-if="capabilities.submit">
                <div v-if="section !== 'materials' && section !== 'assignments'" class="student-tab-bar">
                    <div class="segmented-control" role="tablist" aria-label="Jenis konten pembelajaran">
                        <button type="button" role="tab" :aria-selected="activeTab === 'materials'" :class="{ 'is-active': activeTab === 'materials' }" @click="activeTab = 'materials'">Materi</button>
                        <button type="button" role="tab" :aria-selected="activeTab === 'assignments'" :class="{ 'is-active': activeTab === 'assignments' }" @click="activeTab = 'assignments'">Tugas</button>
                    </div>
                </div>
                <section class="student-course-picker panel">
                    <div class="student-course-picker-heading">
                        <div>
                            <p class="eyebrow">Filter & pencarian belajar</p>
                            <h2>{{ activeTab === 'materials' ? 'Cari materi pelajaran' : 'Filter tugas saya' }}</h2>
                            <p>Tampilkan semua mata pelajaran atau pilih satu mapel tertentu untuk fokus belajar.</p>
                        </div>
                        <div class="student-filter-controls">
                            <label class="task-search search-control-shell student-search-input">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" /></svg>
                                <input v-model="studentSearch" type="search" :placeholder="activeTab === 'materials' ? 'Cari judul materi atau mapel…' : 'Cari judul tugas atau mapel…'" />
                            </label>
                            <label class="student-course-select">
                                <span class="sr-only">Pilih mata pelajaran</span>
                                <select v-model="selectedStudentAssignmentId">
                                    <option value="">Semua mata pelajaran ({{ teaching_assignments.length }})</option>
                                    <option v-for="item in teaching_assignments" :key="item.id" :value="item.id">{{ item.subject.name }} · {{ item.class_group.name }}</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <div v-if="activeTab === 'assignments'" class="student-status-pills" role="group" aria-label="Filter status tugas">
                        <button type="button" class="ui-tab-button student-filter-pill" :class="{ 'is-active': studentTaskFilter === 'all' }" @click="studentTaskFilter = 'all'">
                            Semua <b>{{ studentAssignmentsBase.length }}</b>
                        </button>
                        <button type="button" class="ui-tab-button student-filter-pill" :class="{ 'is-active': studentTaskFilter === 'pending' }" @click="studentTaskFilter = 'pending'">
                            Belum dikumpulkan <b>{{ studentAssignmentsBase.filter(a => !a.submissions.length).length }}</b>
                        </button>
                        <button type="button" class="ui-tab-button student-filter-pill" :class="{ 'is-active': studentTaskFilter === 'submitted' }" @click="studentTaskFilter = 'submitted'">
                            Sudah dikumpulkan <b>{{ studentAssignmentsBase.filter(a => a.submissions.length).length }}</b>
                        </button>
                        <button type="button" class="ui-tab-button student-filter-pill" :class="{ 'is-active': studentTaskFilter === 'revision' }" @click="studentTaskFilter = 'revision'">
                            Perlu diperbaiki <b>{{ studentAssignmentsBase.filter(a => a.submissions[0]?.review_status === 'needs_revision').length }}</b>
                        </button>
                        <button type="button" class="ui-tab-button student-filter-pill" :class="{ 'is-active': studentTaskFilter === 'overdue' }" @click="studentTaskFilter = 'overdue'">
                            Terlambat <b>{{ studentAssignmentsBase.filter(a => !a.submissions.length && a.due_at && new Date(a.due_at).getTime() < Date.now()).length }}</b>
                        </button>
                    </div>
                </section>

                <section v-if="activeTab === 'materials'" class="panel student-learning-panel">
                    <div class="student-panel-heading">
                        <div><p class="eyebrow">Materi pembelajaran</p><h2>{{ studentMaterials.length }} materi tersedia</h2><p>Unduh bahan ajar dan tandai sudah dibaca setelah selesai dipelajari.</p></div>
                        <span class="student-course-chip">{{ selectedStudentAssignment ? `${selectedStudentAssignment.subject.name} · ${selectedStudentAssignment.class_group.name}` : 'Semua Mata Pelajaran' }}</span>
                    </div>
                    <div v-if="studentMaterials.length" class="student-material-grid">
                        <article v-for="material in studentMaterials" :key="material.id" class="student-material-card">
                            <div class="student-material-icon">M</div>
                            <div class="student-material-body">
                                <div class="student-material-meta"><span>{{ material.teaching_assignment.subject.name }}</span><span>{{ material.original_filename?.split('.').pop()?.toUpperCase() || 'MATERI' }}</span><span>{{ formatShortDate(material.published_at) }}</span></div>
                                <h3>{{ material.title }}</h3>
                                <p>{{ material.description || 'Materi pembelajaran tanpa deskripsi tambahan.' }}</p>
                                <span v-if="read_material_ids.includes(material.id)" class="read-badge">✓ Sudah dibaca</span>
                            </div>
                            <div class="student-material-actions">
                                <AppButton variant="secondary" as="a" v-if="material.original_filename" class="secondary-button" :href="studentEndpoint(`/materi/${material.id}/download`)">Unduh materi</AppButton>
                                <Link v-if="!read_material_ids.includes(material.id)" class="text-button ui-button ui-button--ghost ui-button--sm" :href="studentEndpoint(`/materi/${material.id}/dibaca`)" method="post" as="button">Tandai dibaca</Link>
                                <span v-if="!material.original_filename" class="material-file-missing">Berkas belum tersedia</span>
                            </div>
                        </article>
                    </div>
                    <div v-else class="student-empty-state"><strong>Belum ada materi yang sesuai</strong><p>Ubah pilihan mata pelajaran atau tunggu guru menerbitkan bahan ajar.</p></div>
                </section>

                <section v-else class="panel student-learning-panel">
                    <div class="student-panel-heading">
                        <div><p class="eyebrow">Tugas saya</p><h2>{{ studentAssignments.length }} tugas ditampilkan</h2><p>Buka tugas untuk membaca instruksi, mengirim jawaban, dan melihat nilai.</p></div>
                        <span class="student-course-chip">{{ selectedStudentAssignment ? `${selectedStudentAssignment.subject.name} · ${selectedStudentAssignment.class_group.name}` : 'Semua Mata Pelajaran' }}</span>
                    </div>
                    <div class="student-task-list">
                        <article v-for="assignment in studentAssignments" :key="assignment.id" class="student-task-row">
                            <div class="student-task-main">
                                <p class="student-task-subject">{{ assignment.teaching_assignment.subject.name }}</p>
                                <h3>{{ assignment.title }}</h3>
                                <p>{{ assignment.instructions || 'Tidak ada instruksi tambahan.' }}</p>
                            </div>
                            <div class="student-task-detail">
                                <span>Tenggat</span>
                                <strong class="student-task-due" :class="{ 'is-overdue': assignment.due_at && new Date(assignment.due_at).getTime() < Date.now() && !assignment.submissions.length }">{{ formatShortDate(assignment.due_at) }}</strong>
                            </div>
                            <div class="student-task-detail">
                                <span>Status</span>
                                <strong class="student-status-pill" :class="`is-${studentTaskStatus(assignment).tone}`">{{ studentTaskStatus(assignment).label }}</strong>
                            </div>
                            <div class="student-task-detail">
                                <span>Nilai</span>
                                <strong class="student-task-score">{{ assignment.submissions[0]?.score ?? '—' }}</strong>
                            </div>
                            <Link class="task-outline-button student-task-open ui-button ui-button--secondary ui-button--sm" :href="studentEndpoint(`/tugas/${assignment.id}`)">Buka</Link>
                        </article>
                        <div v-if="studentAssignments.length === 0" class="student-empty-row">Belum ada tugas untuk mapel ini.</div>
                    </div>
                </section>
            </template>

            <section v-else class="panel overflow-hidden">
                <div class="toolbar">
                    <div v-if="section !== 'materials' && section !== 'assignments'" class="segmented-control" aria-label="Jenis konten">
                        <button type="button" :class="{ 'is-active': activeTab === 'materials' }" @click="activeTab = 'materials'">Materi</button>
                        <button type="button" :class="{ 'is-active': activeTab === 'assignments' }" @click="activeTab = 'assignments'">Tugas</button>
                    </div>
                    <span class="muted-copy">{{ activeTab === 'materials' ? materials.length : assignments.length }} item</span>
                </div>
                <p v-if="activeTab === 'materials'" class="material-policy">Materi menerima PDF, DOCX, JPG, JPEG, dan PNG hingga 10 MB. Berkas disimpan privat dan diunduh melalui pemeriksaan akses.</p>

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
                            <AppButton variant="secondary" as="a" v-if="material.original_filename" class="secondary-button" :href="studentEndpoint(`/materi/${material.id}/${props.capabilities.submit ? 'download' : 'unduh'}`)">Unduh</AppButton>
                            <RowActionButton v-if="capabilities.manage" action="delete" label="Hapus" :aria-label="`Hapus materi ${material.title}`" @click="removeResource(`/pembelajaran/materi/${material.id}`)" />
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
                            <Link class="secondary-button ui-button ui-button--secondary ui-button--md" :href="studentEndpoint(`/tugas/${assignment.id}`)">Buka tugas</Link>
                            <Link v-if="capabilities.manage" class="secondary-button ui-button ui-button--secondary ui-button--md" :href="`/pembelajaran/tugas/${assignment.id}/pengumpulan`">Lihat pengumpulan</Link>
                            <AppButton variant="primary" v-if="capabilities.submit" class="primary-button" type="button" @click="openSubmission(assignment)">
                                {{ assignment.submissions.length ? 'Perbarui jawaban' : 'Kumpulkan' }}
                            </AppButton>
                            <RowActionButton v-if="capabilities.manage" action="delete" label="Hapus" :aria-label="`Hapus tugas ${assignment.title}`" @click="removeResource(`/pembelajaran/tugas/${assignment.id}`)" />
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
                                    <AppButton variant="ghost" size="sm" v-if="capabilities.manage" class="text-button" type="button" @click="openGrade(submission)">Nilai</AppButton>
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
        </div>

        <div v-if="showMaterialForm" ref="overlayRoot" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="material-modal-title" @click.self="showMaterialForm = false">
            <form class="modal-card" @submit.prevent="submitMaterial">
                <div class="modal-heading"><div><p class="eyebrow">Materi baru</p><h2 id="material-modal-title">Terbitkan bahan ajar</h2></div><AppButton variant="secondary" type="button" aria-label="Tutup formulir materi" @click="showMaterialForm = false">×</AppButton></div>
                <label>Kelas dan mata pelajaran<select v-model="materialForm.teaching_assignment_id" required><option v-for="item in teaching_assignments" :key="item.id" :value="item.id">{{ item.subject.name }} · {{ item.class_group.name }}</option></select></label>
                <label>Judul<input v-model="materialForm.title" required maxlength="180" /></label>
                <label>Deskripsi<textarea v-model="materialForm.description" rows="4" /></label>
                <label>Berkas materi *<input type="file" accept=".pdf,.docx,.jpg,.jpeg,.png,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png" required @change="handleMaterialFile" /></label>
                <p class="form-hint">Format: PDF, DOCX, JPG, JPEG, PNG. Maksimal 10 MB. File disimpan privat dan hanya dapat diunduh pengguna yang berwenang.</p>
                <p v-if="materialForm.hasErrors" class="form-error">Periksa kembali data materi yang diisi.</p>
                <div class="modal-actions"><AppButton variant="secondary" type="button" class="secondary-button" @click="showMaterialForm = false">Batal</AppButton><AppButton variant="primary" :loading="materialForm.processing" type="submit" class="primary-button" :disabled="materialForm.processing">Terbitkan</AppButton></div>
            </form>
        </div>

        <div v-if="showAssignmentForm" ref="overlayRoot" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="assignment-modal-title" @click.self="showAssignmentForm = false">
            <form class="modal-card" @submit.prevent="submitAssignment">
                <div class="modal-heading"><div><p class="eyebrow">Tugas baru</p><h2 id="assignment-modal-title">Buat aktivitas siswa</h2></div><AppButton variant="secondary" type="button" aria-label="Tutup formulir tugas" @click="showAssignmentForm = false">×</AppButton></div>
                <label>Kelas dan mata pelajaran<select v-model="assignmentForm.teaching_assignment_id" required><option v-for="item in teaching_assignments" :key="item.id" :value="item.id">{{ item.subject.name }} · {{ item.class_group.name }}</option></select></label>
                <label>Judul<input v-model="assignmentForm.title" required maxlength="180" /></label>
                <label>Instruksi<textarea v-model="assignmentForm.instructions" rows="5" /></label>
                <div class="form-grid"><label>Tenggat<input v-model="assignmentForm.due_at" type="datetime-local" /></label><label>Nilai maksimal<input v-model.number="assignmentForm.max_score" type="number" min="1" max="1000" /></label></div>
                <label>Batas revisi<input v-model.number="assignmentForm.revision_limit" type="number" min="0" max="10" /></label>
                <label class="check-row"><input v-model="assignmentForm.allow_late" type="checkbox" /> Izinkan pengumpulan setelah tenggat</label>
                <div class="modal-actions"><AppButton variant="secondary" type="button" class="secondary-button" @click="showAssignmentForm = false">Batal</AppButton><AppButton variant="primary" :loading="assignmentForm.processing" type="submit" class="primary-button" :disabled="assignmentForm.processing">Terbitkan tugas</AppButton></div>
            </form>
        </div>

        <div v-if="selectedAssignment" ref="overlayRoot" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="submission-modal-title" @click.self="selectedAssignment = null">
            <form class="modal-card" @submit.prevent="submitWork">
                <div class="modal-heading"><div><p class="eyebrow">Pengumpulan tugas</p><h2 id="submission-modal-title">{{ selectedAssignment.title }}</h2></div><AppButton variant="secondary" type="button" aria-label="Tutup pengumpulan tugas" @click="selectedAssignment = null">×</AppButton></div>
                <label>Jawaban teks<textarea v-model="submissionForm.content" rows="7" placeholder="Tulis jawaban atau catatan untuk guru" /></label>
                <label>Lampiran (maksimal 5)<input type="file" multiple @change="handleSubmissionFiles" /></label>
                <p v-if="submissionForm.hasErrors" class="form-error">Isi jawaban teks atau pilih minimal satu berkas.</p>
                <div class="modal-actions"><AppButton variant="secondary" type="button" class="secondary-button" @click="selectedAssignment = null">Batal</AppButton><AppButton variant="primary" :loading="submissionForm.processing" type="submit" class="primary-button" :disabled="submissionForm.processing">Kirim tugas</AppButton></div>
            </form>
        </div>

        <div v-if="selectedSubmission" ref="overlayRoot" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="grade-modal-title" @click.self="selectedSubmission = null">
            <form class="modal-card" @submit.prevent="submitGrade">
                <div class="modal-heading"><div><p class="eyebrow">Penilaian tugas</p><h2 id="grade-modal-title">{{ selectedSubmission.enrollment?.student?.name }}</h2></div><AppButton variant="secondary" type="button" aria-label="Tutup penilaian tugas" @click="selectedSubmission = null">×</AppButton></div>
                <label>Keputusan<select v-model="gradeForm.review_status"><option value="approved">Setujui & beri nilai</option><option value="needs_revision">Minta perbaikan</option></select></label>
                <label v-if="gradeForm.review_status === 'approved'">Nilai<input v-model.number="gradeForm.score" type="number" min="0" required /></label>
                <label>Umpan balik<textarea v-model="gradeForm.feedback" rows="5" /></label>
                <div class="modal-actions"><AppButton variant="secondary" type="button" class="secondary-button" @click="selectedSubmission = null">Batal</AppButton><AppButton variant="primary" :loading="gradeForm.processing" type="submit" class="primary-button" :disabled="gradeForm.processing">Simpan nilai</AppButton></div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.compact-hero { display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; }
.hero-actions, .resource-actions, .modal-actions { display: flex; align-items: center; gap: .75rem; }
.class-workspace-nav { display: flex; gap: .35rem; overflow-x: auto; padding: .4rem; border: 1px solid var(--line, #e2e8f0); border-radius: .9rem; background: var(--surface, #fff); scrollbar-width: thin; }
.workspace-tab { flex: 0 0 auto; border: 0; background: transparent; color: var(--ink-soft, #536174); text-decoration: none; cursor: pointer; }
.workspace-tab:hover { background: var(--surface-subtle, #f8faff); color: var(--ink, #172033); }
.workspace-tab.is-current { background: var(--primary-soft, #e8eefb); color: var(--primary, #3157a4); }
.workspace-tab:focus-visible { outline: 2px solid var(--primary, #3157a4); outline-offset: 2px; }
.toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--line, #e5e7eb); }
.material-policy { margin: 0; padding: .75rem 1.25rem; border-bottom: 1px solid var(--line); color: var(--ink-soft); font-size: var(--type-meta); line-height: 1.45; }
.segmented-control { display: inline-flex; padding: .25rem; border: 1px solid var(--line); border-radius: .75rem; background: var(--surface-subtle); }
.segmented-control button { min-height: 40px; padding: .55rem 1rem; border-radius: .55rem; color: var(--ink-soft); font-weight: var(--weight-semibold); }
.segmented-control .is-active { background: var(--primary-soft); color: var(--primary-strong); }
.resource-grid, .assignment-list { display: grid; gap: 1rem; padding: 1.25rem; }
.resource-grid { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
.resource-card, .assignment-card { display: flex; gap: 1rem; border: 1px solid var(--line); border-radius: 14px; padding: 1.1rem; background: var(--surface); }
.assignment-card { flex-wrap: wrap; align-items: flex-start; }
.resource-icon { width: 2.75rem; height: 2.75rem; flex: 0 0 auto; display: grid; place-items: center; border-radius: .8rem; background: var(--primary-soft); color: var(--primary); font-weight: var(--weight-bold); }
.resource-body, .assignment-main { min-width: 0; flex: 1; }
.resource-body h2, .assignment-main h2, .modal-heading h2 { margin: .2rem 0 .35rem; color: var(--ink); font-size: 1.05rem; font-weight: var(--weight-bold); }
.resource-body p, .assignment-main > p { color: var(--ink-soft); font-size: var(--type-md); }
.resource-body small { display: block; margin-top: .75rem; color: var(--ink-faint); }
.assignment-meta { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .9rem; }
.assignment-meta span { padding: .3rem .55rem; border-radius: 999px; background: var(--surface-strong); color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.submission-list { width: 100%; display: grid; gap: .6rem; padding-top: 1rem; border-top: 1px solid var(--line); }
.submission-row { display: grid; grid-template-columns: minmax(150px, 1fr) 2fr 1fr auto; align-items: center; gap: 1rem; padding: .8rem; border-radius: .75rem; background: var(--surface-subtle); font-size: var(--type-sm); }
.submission-row small { display: block; color: var(--ink-soft); }
.submission-files { display: grid; gap: .2rem; }
.submission-files a { color: var(--primary); text-decoration: underline; }
.submission-score { display: flex; align-items: center; gap: .5rem; }
.danger-link { color: var(--red); font-size: var(--type-control); font-weight: var(--weight-semibold); }
.empty-state { grid-column: 1 / -1; padding: 3rem 1rem; text-align: center; color: var(--ink-soft); }
.empty-state strong { display: block; color: var(--ink); font-size: var(--type-md); }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .56); }
.modal-card { width: min(620px, 100%); max-height: 90dvh; overflow-y: auto; display: grid; gap: 1rem; padding: 1.35rem; border: 1px solid var(--line); border-radius: 14px; color: var(--ink); background: var(--surface); box-shadow: 0 24px 80px rgb(15 23 42 / .25); }
.modal-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.modal-card label { display: grid; gap: .4rem; color: var(--ink); font-size: var(--type-meta); font-weight: var(--weight-semibold); }
.modal-card input:not([type='checkbox']), .modal-card select, .modal-card textarea { width: 100%; border: 1px solid var(--line-strong); border-radius: .65rem; padding: .65rem .75rem; color: var(--ink); background: var(--surface); font: inherit; font-weight: var(--weight-medium); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.check-row { display: flex !important; grid-template-columns: none; flex-direction: row; align-items: center; }
.modal-actions { justify-content: flex-end; padding-top: .5rem; }
.form-error { color: var(--red); font-size: var(--type-meta); }
.form-hint { margin: -.45rem 0 0; color: var(--ink-soft); font-size: var(--type-meta); line-height: 1.4; }
.read-badge { color: var(--green); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.task-dashboard-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .85rem; }
.task-stat { min-height: 112px; display: grid; align-content: center; gap: .15rem; padding: 1rem 1.1rem; border: 1px solid var(--line); border-radius: var(--radius-md); background: var(--surface); box-shadow: var(--shadow-sm); }
.task-stat span, .task-stat small { color: var(--ink-soft); font-size: var(--type-meta); }
.task-stat strong { color: var(--ink); font-size: 2rem; line-height: 1; letter-spacing: -.04em; }
.task-stat-green, .task-stat-blue, .task-stat-teal, .task-stat-red, .task-stat-amber { border-top: 1px solid var(--line); }
.task-create-panel, .task-class-panel, .task-list-panel { padding: 1.25rem; }
.task-panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
.task-panel-heading h2 { margin: .15rem 0 .25rem; color: var(--ink); font-size: 1.15rem; letter-spacing: -.025em; }
.task-panel-heading p:not(.eyebrow) { margin: 0; color: var(--ink-soft); font-size: var(--type-meta); }
.task-panel-icon { width: 2rem; height: 2rem; display: grid; place-items: center; border-radius: .65rem; color: var(--green); background: var(--green-soft); font-size: 1.3rem; }
.task-create-form { display: grid; grid-template-columns: minmax(0, 1fr) minmax(290px, .95fr); gap: 1.15rem; }
.task-form-main, .task-class-picker { display: grid; gap: .8rem; }
.task-create-form label { display: grid; gap: .4rem; color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-semibold); }
.task-create-form input:not([type='checkbox']), .task-create-form textarea { width: 100%; color: var(--ink); background: var(--surface-subtle); border: 1px solid var(--line-strong); border-radius: .65rem; padding: .7rem .8rem; font: inherit; font-weight: var(--weight-medium); }
.task-create-form input::placeholder, .task-create-form textarea::placeholder { color: var(--ink-faint); }
.task-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; }
.task-check-row { display: flex !important; grid-template-columns: none !important; flex-direction: row; align-items: center; gap: .55rem !important; }
.task-check-row input, .task-class-option input { accent-color: var(--green); }
.task-picker-heading { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
.task-picker-heading strong { display: block; color: var(--ink); font-size: var(--type-sm); }
.task-picker-heading small { display: block; margin-top: .15rem; color: var(--ink-faint); font-size: var(--type-meta); }
.task-search { min-height: 38px; display: flex; align-items: center; gap: .45rem; padding: 0 .7rem; color: var(--ink-faint); background: var(--surface-subtle); border: 1px solid var(--line); border-radius: .6rem; }
.task-search input { min-width: 0; flex: 1; color: var(--ink); background: transparent; border: 0; outline: 0; font-size: var(--type-control); }
.task-search-wide { width: min(280px, 100%); }
.task-class-options { max-height: 245px; display: grid; gap: .45rem; overflow: auto; padding-right: .15rem; }
.task-class-option { display: flex !important; grid-template-columns: none !important; grid-template-rows: none !important; align-items: flex-start; gap: .65rem !important; padding: .7rem .75rem; border: 1px solid var(--line); border-radius: .65rem; background: var(--surface-subtle); cursor: pointer; }
.task-class-option.is-selected { border-color: color-mix(in srgb, var(--green) 58%, var(--line)); background: var(--green-soft); }
.task-class-option strong, .task-class-option small { display: block; }
.task-class-option strong { color: var(--ink); font-size: var(--type-meta); }
.task-class-option small { margin-top: .2rem; color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-medium); }
.task-form-footer { grid-column: 1 / -1; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-top: .85rem; border-top: 1px solid var(--line); }
.task-form-footer .form-error { margin: 0; }
.task-class-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; }
.task-class-card { padding: .95rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.task-class-card.is-highlighted { border-color: color-mix(in srgb, var(--primary) 55%, var(--line)); }
.task-class-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
.task-class-card-top small { color: var(--green); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .07em; }
.task-class-card h3 { margin: .15rem 0 0; color: var(--ink); font-size: 1.12rem; }
.task-class-card p { margin: .15rem 0 0; color: var(--ink-soft); font-size: var(--type-meta); }
.task-class-bookmark { color: var(--primary); font-size: 1.05rem; }
.task-class-metrics { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem; margin: .8rem 0; }
.task-class-metrics span { padding: .45rem .5rem; border-radius: .45rem; color: var(--ink-soft); background: var(--surface); font-size: var(--type-meta); }
.task-class-metrics strong { color: var(--ink); margin-right: .18rem; font-size: var(--type-sm); }
.task-class-latest { min-height: 2.2em; }
.task-card-action { margin-top: .75rem; }
.task-table-wrap { overflow-x: auto; }
.task-table { width: 100%; min-width: 760px; border-collapse: collapse; font-size: var(--type-meta); }
.task-table th, .task-table td { padding: .85rem .7rem; border-bottom: 1px solid var(--line); text-align: left; vertical-align: middle; }
.task-table th { color: var(--ink-faint); background: var(--surface-subtle); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .04em; text-transform: uppercase; }
.task-table td strong, .task-table td small { display: block; }
.task-table td strong { color: var(--ink); font-size: var(--type-sm); }
.task-table td small { margin-top: .18rem; color: var(--ink-soft); font-size: var(--type-meta); }
.task-subject-chip, .task-deadline { display: inline-flex; padding: .28rem .5rem; border-radius: .5rem; color: var(--ink-soft); background: var(--surface-subtle); font-size: var(--type-meta); font-weight: var(--weight-bold); white-space: nowrap; }
.task-deadline.is-overdue { color: var(--red); background: var(--red-soft); }
.task-progress-label { display: flex; align-items: center; justify-content: space-between; gap: .7rem; color: var(--ink-soft); font-size: var(--type-meta); }
.task-progress-label strong { color: var(--ink) !important; font-size: var(--type-meta) !important; }
.task-progress-track { height: .35rem; margin-top: .35rem; overflow: hidden; border-radius: 999px; background: var(--surface-strong); }
.task-progress-track i { display: block; height: 100%; border-radius: inherit; background: var(--green); }
.task-row-actions { display: flex; align-items: center; gap: .35rem; }
.task-empty-copy, .task-empty-row { padding: 1rem; color: var(--ink-faint); font-size: var(--type-meta); text-align: center; }
.material-upload-panel, .material-list-panel { padding: 1.25rem; }
.material-panel-icon { color: var(--blue); background: var(--blue-soft); }
.material-inline-form { display: grid; gap: .85rem; }
.material-inline-form label { display: grid; gap: .4rem; color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-semibold); }
.material-inline-form input:not([type='file']), .material-inline-form select, .material-inline-form textarea { width: 100%; color: var(--ink); background: var(--surface-subtle); border: 1px solid var(--line-strong); border-radius: .65rem; padding: .7rem .8rem; font: inherit; }
.material-inline-form input[type='file'] { width: 100%; min-height: 42px; color: var(--ink); background: var(--surface-subtle); border: 1px solid var(--line-strong); border-radius: .65rem; padding: .55rem .7rem; font: inherit; }
.material-inline-form small { color: var(--ink-faint); font-size: var(--type-meta); font-weight: var(--weight-medium); }
.material-upload-button { width: 100%; }
.material-list-heading { align-items: center; }
.material-list-tools { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; justify-content: flex-end; }
.material-list-tools select { min-height: 38px; max-width: 250px; color: var(--ink); background: var(--surface-subtle); border: 1px solid var(--line); border-radius: .6rem; padding: 0 .65rem; font-size: var(--type-control); }
.material-resource-grid { display: grid; gap: .65rem; }
.material-resource-card { display: grid; grid-template-columns: auto minmax(0, 1fr) auto; align-items: center; gap: .9rem; padding: .85rem; border: 1px solid var(--line); border-radius: .75rem; background: var(--surface-subtle); }
.material-resource-icon { width: 2.4rem; height: 2.4rem; display: grid; place-items: center; border-radius: .65rem; color: var(--blue); background: var(--blue-soft); font-size: 1.1rem; }
.material-resource-body { min-width: 0; }
.material-resource-body small, .material-resource-body span { color: var(--ink-faint); font-size: var(--type-meta); }
.material-resource-body h3 { margin: .18rem 0 .18rem; color: var(--ink); font-size: var(--type-md); }
.material-resource-body p { margin: 0 0 .25rem; color: var(--ink-soft); font-size: var(--type-meta); }
.material-resource-actions { display: flex; align-items: center; gap: .45rem; }
.material-empty-state { min-height: 210px; display: grid; align-content: center; justify-items: center; gap: .3rem; color: var(--ink-faint); text-align: center; }
.material-empty-state strong { color: var(--ink); font-size: var(--type-md); }
.material-empty-state p { margin: 0; font-size: var(--type-meta); }
.material-empty-icon { width: 2.5rem; height: 2.5rem; display: grid; place-items: center; margin-bottom: .25rem; border-radius: .7rem; color: var(--ink-faint); background: var(--surface-strong); }
.student-course-picker, .student-learning-panel { padding: 1.25rem; }
.student-filter-controls { display: flex; align-items: center; gap: .65rem; flex-wrap: wrap; }
.student-search-input { min-width: 220px; min-height: 42px; }
.student-status-pills { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; margin-top: 1rem; padding-top: .85rem; border-top: 1px solid var(--line); }
.student-filter-pill { display: inline-flex; color: var(--ink-soft); background: var(--surface); border: 1px solid var(--line-strong); cursor: pointer; transition: all .15s ease; }
.student-filter-pill:hover { border-color: var(--primary); color: var(--ink); }
.student-filter-pill.is-active { color: #fff; background: var(--primary-action); border-color: var(--primary-action); }
.student-filter-pill b { background: color-mix(in srgb, currentColor 14%, transparent); }
.student-filter-pill.is-active b { color: #fff; background: rgba(255,255,255,.22); }
.student-learning-summary.student-task-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.student-course-picker-heading, .student-panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.student-course-picker-heading h2, .student-panel-heading h2 { margin: .15rem 0 .25rem; color: var(--ink); font-size: 1.15rem; letter-spacing: -.025em; }
.student-course-picker-heading p:not(.eyebrow), .student-panel-heading p:not(.eyebrow) { margin: 0; color: var(--ink-soft); font-size: var(--type-meta); }
.student-course-select { min-width: min(320px, 100%); display: grid; gap: .35rem; color: var(--ink-soft); font-size: var(--type-control); font-weight: var(--weight-bold); }
.student-course-select select { width: 100%; min-height: 42px; color: var(--ink); background: var(--surface-subtle); border: 1px solid var(--line-strong); border-radius: .65rem; padding: 0 .75rem; font: inherit; font-weight: var(--weight-semibold); }
.student-course-chip { flex: 0 0 auto; padding: .42rem .65rem; border-radius: 999px; color: var(--primary); background: var(--primary-soft); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.student-material-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(280px, 100%), 1fr)); gap: .85rem; margin-top: 1.1rem; }
.student-material-card { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .8rem; padding: 1rem; border: 1px solid var(--line); border-radius: .85rem; background: var(--surface-subtle); }
.student-material-icon { width: 2.55rem; height: 2.55rem; display: grid; place-items: center; border-radius: .7rem; color: var(--blue); background: var(--blue-soft); font-size: .85rem; font-weight: var(--weight-bold); }
.student-material-body { min-width: 0; }
.student-material-meta { display: flex; flex-wrap: wrap; gap: .5rem; color: var(--ink-faint); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .04em; text-transform: uppercase; }
.student-material-body h3 { margin: .35rem 0 .3rem; color: var(--ink); font-size: var(--type-md); line-height: 1.35; }
.student-material-body p { margin: 0; color: var(--ink-soft); font-size: var(--type-meta); line-height: 1.5; }
.student-material-actions { grid-column: 1 / -1; display: flex; align-items: center; justify-content: flex-end; gap: .55rem; padding-top: .65rem; border-top: 1px solid var(--line); }
.material-file-missing { color: var(--ink-faint); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.student-task-list { display: grid; margin-top: 1.1rem; overflow: hidden; border: 1px solid var(--line); border-radius: .75rem; }
.student-task-row { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(90px, .8fr) minmax(130px, 1fr) minmax(48px, .45fr) auto; align-items: center; gap: .9rem; padding: 1rem; border-bottom: 1px solid var(--line); background: var(--surface); }
.student-task-row:last-child { border-bottom: 0; }
.student-task-main { min-width: 0; }
.student-task-subject { margin: 0 0 .25rem; color: var(--ink-faint); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .04em; text-transform: uppercase; }
.student-task-main h3 { margin: 0; color: var(--ink); font-size: var(--type-sm); line-height: 1.4; overflow-wrap: anywhere; }
.student-task-main > p:last-child { margin: .25rem 0 0; color: var(--ink-soft); font-size: var(--type-meta); line-height: 1.4; }
.student-task-detail { min-width: 0; display: grid; gap: .25rem; }
.student-task-detail > span { color: var(--ink-faint); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .03em; text-transform: uppercase; }
.student-task-detail > strong { justify-self: start; }
.student-task-table td small { margin-top: .2rem; color: var(--ink-soft); font-size: var(--type-meta); }
.student-task-due { color: var(--ink-soft); font-size: var(--type-meta); white-space: nowrap; }
.student-task-due.is-overdue { color: var(--red); font-weight: var(--weight-bold); }
.student-status-pill { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .55rem; border-radius: 999px; font-size: var(--type-meta); font-weight: var(--weight-bold); white-space: nowrap; }
.student-status-pill::before { width: .35rem; height: .35rem; content: ''; border-radius: 50%; background: currentColor; }
.student-status-pill.is-success { color: var(--green); background: var(--green-soft); }
.student-status-pill.is-warning { color: var(--gold); background: var(--gold-soft); }
.student-status-pill.is-danger { color: var(--red); background: var(--red-soft); }
.student-task-score { color: var(--green) !important; font-size: var(--type-sm) !important; }
.student-empty-state { min-height: 220px; display: grid; align-content: center; justify-items: center; gap: .35rem; margin-top: 1rem; color: var(--ink-faint); text-align: center; }
.student-empty-state strong { color: var(--ink); font-size: var(--type-md); }
.student-empty-state p { margin: 0; font-size: var(--type-meta); }
.student-task-open { white-space: nowrap; }
.student-empty-row { padding: 2rem 1rem; color: var(--ink-faint); text-align: center; }
.student-tab-bar { display: flex; align-items: center; justify-content: flex-start; }
.student-learning-page .compact-hero h1, .student-material-body h3, .student-material-body p { overflow-wrap: anywhere; }
.student-course-picker-heading > div, .student-panel-heading > div { min-width: 0; }
.student-course-chip { max-width: 100%; overflow-wrap: anywhere; }
.student-material-actions { flex-wrap: wrap; }
.student-learning-page { display: grid; gap: 1.25rem; min-width: 0; }
.student-learning-page > * { margin-block: 0; min-width: 0; }
.student-learning-page .student-learning-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(220px, 100%), 1fr)); width: 100%; }
.student-learning-page .student-learning-summary > div { min-height: 104px; padding: 1rem 1.25rem; text-align: left; }
.student-learning-page .student-filter-controls { display: grid; grid-template-columns: minmax(0, 1fr); flex: 0 1 360px; width: 100%; }
.student-learning-page .student-filter-controls .task-search { width: 100%; max-width: none; }
.student-learning-page .student-course-select { min-width: 0; }
.student-learning-page .student-material-card { grid-template-rows: 1fr auto; }
.student-learning-page .student-material-body h3 { font-weight: var(--weight-bold); line-height: 1.5; }
.student-learning-page .student-material-meta { color: var(--ink-soft); }
@media (max-width: 900px) { .student-learning-page .student-course-picker-heading { flex-direction: column; } .student-learning-page .student-filter-controls { flex-basis: auto; grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 620px) { .student-learning-page .student-filter-controls { grid-template-columns: minmax(0, 1fr); } .student-learning-page .student-learning-summary.student-task-summary { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 760px) { .compact-hero, .resource-card { align-items: stretch; flex-direction: column; } .submission-row { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } }
@media (max-width: 980px) { .task-dashboard-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } .task-create-form { grid-template-columns: 1fr; } .task-class-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .task-form-footer { grid-column: auto; } }
@media (max-width: 880px) { .student-task-row { grid-template-columns: minmax(0, 1.5fr) repeat(3, minmax(70px, .7fr)) auto; gap: .65rem; } }
@media (max-width: 620px) { .task-dashboard-stats, .task-class-grid { grid-template-columns: 1fr; } .task-panel-heading, .task-form-footer, .material-list-heading, .student-course-picker-heading, .student-panel-heading { align-items: stretch; flex-direction: column; } .task-search-wide, .material-list-tools, .material-list-tools select, .material-list-tools .task-search, .student-course-select { width: 100%; max-width: none; } .task-save-button, .material-upload-button { width: 100%; } .task-form-grid { grid-template-columns: 1fr; } .material-resource-card { grid-template-columns: auto minmax(0, 1fr); } .material-resource-actions { grid-column: 1 / -1; justify-content: flex-end; } .student-course-chip { align-self: flex-start; } .student-learning-summary.student-task-summary { grid-template-columns: 1fr; } .student-task-row { grid-template-columns: 1fr 1fr; gap: .75rem; } .student-task-main { grid-column: 1 / -1; } .student-task-open { justify-self: end; } }
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
