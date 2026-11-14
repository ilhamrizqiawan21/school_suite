<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

interface ProgressRow {
    row_key: string;
    enrollment_id: number;
    class_group_id?: number;
    subject_id: number;
    nis: string;
    student_name: string;
    class_name: string;
    subject_name: string;
    materials_read: number;
    materials_total: number;
    assignments_submitted: number;
    assignments_total: number;
    late_count: number;
    revision_count: number;
    graded_count: number;
    average_score: number | null;
    completion_percentage?: number;
    is_complete?: boolean;
    needs_attention?: boolean;
}

interface StudentProgressGroup {
    enrollment_id: number;
    nis: string;
    student_name: string;
    class_name: string;
    class_group_id: number;
    subjects_count: number;
    materials_read: number;
    materials_total: number;
    assignments_submitted: number;
    assignments_total: number;
    late_count: number;
    revision_count: number;
    completion_percentage: number;
    average_score: number | null;
    needs_attention: boolean;
    subjects: ProgressRow[];
}

const props = defineProps<{
    active_term?: { name: string } | null;
    rows: ProgressRow[];
    student_groups?: StudentProgressGroup[];
    classes: { id: number; name: string }[];
    subjects: { id: number; name: string }[];
    pagination: { links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number };
    summary: {
        students: number;
        materials_read: number;
        materials_total: number;
        assignments_submitted: number;
        assignments_total: number;
        late: number;
        revisions: number;
        average_score: number | null;
        complete_rows?: number;
        attention_rows?: number;
        incomplete_rows?: number;
    };
    filters: { class: string; subject: string; search: string; status?: string; sort?: string };
    is_student: boolean;
    can_manage_lms?: boolean;
    personal_attendance?: { present: number; sick: number; permitted: number; absent: number; total: number; percentage: number } | null;
}>();

const viewMode = ref<'table' | 'students'>('table');
const expandedStudents = ref<Record<number, boolean>>({});

const filterForm = reactive({
    class: props.filters.class ?? '',
    subject: props.filters.subject ?? '',
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    sort: props.filters.sort ?? 'student_asc',
});

const totals = computed(() => props.summary);

const ratio = (done: number, total: number): number =>
    total > 0 ? Math.min(100, Math.round((done / total) * 100)) : 0;

const overallCompletion = (row: ProgressRow): number => {
    if (typeof row.completion_percentage === 'number') {
        return row.completion_percentage;
    }
    const total = row.materials_total + row.assignments_total;
    const done = row.materials_read + row.assignments_submitted;
    return total > 0 ? Math.min(100, Math.round((done / total) * 100)) : 100;
};

const rowStatusMeta = (row: ProgressRow): { label: string; tone: 'emerald' | 'amber' | 'rose' | 'slate' } => {
    const totalActivities = row.materials_total + row.assignments_total;
    if (totalActivities === 0) {
        return { label: 'Belum Ada Aktivitas', tone: 'slate' };
    }
    if (row.revision_count > 0) {
        return { label: `${row.revision_count} Perlu Revisi`, tone: 'rose' };
    }
    if (row.late_count > 0) {
        return { label: `${row.late_count} Terlambat`, tone: 'amber' };
    }
    const pct = overallCompletion(row);
    if (pct >= 100) {
        return { label: 'Tuntas', tone: 'emerald' };
    }
    if (pct < 50) {
        return { label: 'Perlu Perhatian', tone: 'rose' };
    }
    return { label: 'Sedang Berjalan', tone: 'amber' };
};

const scoreTone = (score: number | null): 'emerald' | 'amber' | 'rose' | 'slate' => {
    if (score === null) return 'slate';
    if (score >= 80) return 'emerald';
    if (score >= 65) return 'amber';
    return 'rose';
};

const barTone = (pct: number): string => {
    if (pct >= 80) return 'bar-fill--emerald';
    if (pct >= 50) return 'bar-fill--amber';
    return 'bar-fill--rose';
};

const hasActiveFilter = computed(() =>
    Boolean(
        filterForm.search.trim() ||
        filterForm.class ||
        filterForm.subject ||
        filterForm.status ||
        (filterForm.sort && filterForm.sort !== 'student_asc'),
    ),
);

const applyFilters = () => {
    router.get(
        props.is_student ? '/siswa/progress' : '/pembelajaran/progress',
        {
            class: filterForm.class || undefined,
            subject: filterForm.subject || undefined,
            search: filterForm.search.trim() || undefined,
            status: filterForm.status || undefined,
            sort: filterForm.sort && filterForm.sort !== 'student_asc' ? filterForm.sort : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const selectStatusPill = (status: string) => {
    filterForm.status = filterForm.status === status ? '' : status;
    applyFilters();
};

const clearFilters = () => {
    filterForm.class = '';
    filterForm.subject = '';
    filterForm.search = '';
    filterForm.status = '';
    filterForm.sort = 'student_asc';
    applyFilters();
};

const toggleStudentExpand = (enrollmentId: number) => {
    expandedStudents.value[enrollmentId] = !expandedStudents.value[enrollmentId];
};

const studentGroupsList = computed<StudentProgressGroup[]>(() => props.student_groups ?? []);
</script>

<template>
    <Head title="Progress Belajar" />
    <AppLayout>
        <div class="view space-y-5 learning-progress-page" :class="{ 'student-progress-page': is_student }">
            <!-- Header Hero -->
            <section class="hero-card compact-hero progress-hero">
                <div class="progress-hero__copy">
                    <div class="progress-hero__eyebrow-row">
                        <p class="eyebrow">{{ is_student ? 'Ringkasan belajar saya' : 'Pemantauan Pembelajaran' }}</p>
                        <span v-if="active_term" class="term-pill">{{ active_term.name }}</span>
                    </div>
                    <h1>{{ is_student ? 'Progres Belajar Saya' : 'Progres Belajar Siswa' }}</h1>
                    <p>
                        {{
                            is_student
                                ? 'Pantau kehadiran, ketuntasan materi yang dipelajari, pengumpulan tugas, dan capaian nilai per mata pelajaran.'
                                : 'Pantau keterbacaan materi, ketuntasan pengumpulan tugas, keterlambatan, revisi, serta capaian nilai siswa secara terukur.'
                        }}
                    </p>
                </div>

                <div class="progress-hero__actions">
                    <!-- View Mode Switcher (Non-Student) -->
                    <div v-if="!is_student" class="view-mode-switch" role="tablist" aria-label="Mode tampilan progress belajar">
                        <AppButton variant="secondary"
                            type="button"
                            role="tab"
                            :aria-selected="viewMode === 'table'"
                            class="view-mode-btn"
                            :class="{ 'is-active': viewMode === 'table' }"
                            @click="viewMode = 'table'"
                        ><template #icon><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h14M3 10h14M3 15h14" />
                            </svg></template>

                            <span>Tabel Analitik</span>
                        </AppButton>
                        <AppButton variant="secondary"
                            type="button"
                            role="tab"
                            :aria-selected="viewMode === 'students'"
                            class="view-mode-btn"
                            :class="{ 'is-active': viewMode === 'students' }"
                            @click="viewMode = 'students'"
                        ><template #icon><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <rect x="3" y="3.5" width="6" height="6" rx="1.2" />
                                <rect x="11" y="3.5" width="6" height="6" rx="1.2" />
                                <rect x="3" y="11" width="6" height="5.5" rx="1.2" />
                                <rect x="11" y="11" width="6" height="5.5" rx="1.2" />
                            </svg></template>

                            <span>Kartu per Siswa</span>
                        </AppButton>
                    </div>

                    <Link
                        v-if="can_manage_lms ?? is_student"
                        class="secondary-button progress-back-link ui-button ui-button--secondary ui-button--md"
                        :href="is_student ? '/siswa/materi' : '/pembelajaran'"
                    >
                        {{ is_student ? 'Buka Materi & Tugas' : 'Ruang Pembelajaran' }}
                    </Link>
                </div>
            </section>

            <!-- Summary Metric Cards (Guru / Admin / Wali Kelas / Kepsek) -->
            <section v-if="!is_student" class="ss-summary-grid" aria-label="Ringkasan progres hasil filter">
                <MetricSummaryCard
                    label="Cakupan Siswa"
                    :value="totals.students"
                    :note="`${pagination.total} baris mapel · aktif pada hasil filter`"
                    tone="blue"
                    icon="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m8-10a4 4 0 100-8 4 4 0 000 8zm10 10v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"
                />
                <MetricSummaryCard
                    label="Keterbacaan Materi"
                    :value="`${totals.materials_read} / ${totals.materials_total}`"
                    :note="totals.materials_total ? `${ratio(totals.materials_read, totals.materials_total)}% sudah dibaca` : 'belum ada bahan ajar terbit'"
                    tone="violet"
                    icon="M4 5.5A2.5 2.5 0 016.5 3H20v16H6.5A2.5 2.5 0 014 16.5v-11zM4 16.5A2.5 2.5 0 016.5 14H20M8 7h8M8 10h6"
                >
                    <div class="mini-progress-track" aria-hidden="true">
                        <span
                            class="mini-progress-fill"
                            :class="barTone(ratio(totals.materials_read, totals.materials_total))"
                            :style="{ width: `${ratio(totals.materials_read, totals.materials_total)}%` }"
                        />
                    </div>
                </MetricSummaryCard>
                <MetricSummaryCard
                    label="Pengumpulan Tugas"
                    :value="`${totals.assignments_submitted} / ${totals.assignments_total}`"
                    :note="totals.assignments_total ? `${ratio(totals.assignments_submitted, totals.assignments_total)}% sudah terkumpul` : 'belum ada yang diterbitkan'"
                    tone="green"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                >
                    <div class="mini-progress-track" aria-hidden="true">
                        <span
                            class="mini-progress-fill"
                            :class="barTone(ratio(totals.assignments_submitted, totals.assignments_total))"
                            :style="{ width: `${ratio(totals.assignments_submitted, totals.assignments_total)}%` }"
                        />
                    </div>
                </MetricSummaryCard>
                <MetricSummaryCard
                    label="Perlu Tindak Lanjut"
                    :value="`${totals.late} terlambat`"
                    :note="`${totals.revisions} revisi · Rerata nilai kelas: ${totals.average_score === null ? '—' : `${totals.average_score}%`}`"
                    :tone="totals.late > 0 || totals.revisions > 0 ? 'rose' : 'slate'"
                    icon="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                />
            </section>

            <!-- Summary Metric Cards (Siswa) -->
            <section v-else class="ss-summary-grid" aria-label="Ringkasan progress belajar saya">
                <MetricSummaryCard
                    label="Kehadiran"
                    :value="personal_attendance ? `${personal_attendance.percentage}%` : '—'"
                    :note="personal_attendance ? `${personal_attendance.present} kali hadir dari ${personal_attendance.total} catatan` : 'belum ada catatan presensi'"
                    tone="green"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    label="Materi Dipelajari"
                    :value="`${totals.materials_read} / ${totals.materials_total}`"
                    :note="totals.materials_total ? `${ratio(totals.materials_read, totals.materials_total)}% sudah dibaca` : 'belum ada bahan ajar terbit'"
                    tone="violet"
                    icon="M4 5.5A2.5 2.5 0 016.5 3H20v16H6.5A2.5 2.5 0 014 16.5v-11zM4 16.5A2.5 2.5 0 016.5 14H20M8 7h8M8 10h6"
                >
                    <div class="mini-progress-track" aria-hidden="true">
                        <span
                            class="mini-progress-fill"
                            :class="barTone(ratio(totals.materials_read, totals.materials_total))"
                            :style="{ width: `${ratio(totals.materials_read, totals.materials_total)}%` }"
                        />
                    </div>
                </MetricSummaryCard>
                <MetricSummaryCard
                    label="Tugas Dikumpulkan"
                    :value="`${totals.assignments_submitted} / ${totals.assignments_total}`"
                    :note="totals.assignments_total ? `${ratio(totals.assignments_submitted, totals.assignments_total)}% sudah terkumpul` : 'belum ada yang diterbitkan'"
                    tone="amber"
                    icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                >
                    <div class="mini-progress-track" aria-hidden="true">
                        <span
                            class="mini-progress-fill"
                            :class="barTone(ratio(totals.assignments_submitted, totals.assignments_total))"
                            :style="{ width: `${ratio(totals.assignments_submitted, totals.assignments_total)}%` }"
                        />
                    </div>
                </MetricSummaryCard>
                <MetricSummaryCard
                    label="Rerata Nilai"
                    :value="totals.average_score === null ? '—' : `${totals.average_score}%`"
                    :note="totals.revisions > 0 ? `${totals.revisions} perlu revisi · berdasarkan tugas yang dinilai` : 'berdasarkan tugas yang dinilai'"
                    :tone="totals.revisions > 0 ? 'rose' : 'blue'"
                    icon="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                />
            </section>

            <!-- Student Attendance Breakdown -->
            <section v-if="is_student && personal_attendance" class="panel personal-attendance" aria-label="Rincian kehadiran saya">
                <div class="personal-attendance__lead">
                    <span>Rincian Kehadiran Semester Ini</span>
                    <strong>{{ personal_attendance.percentage }}%</strong>
                </div>
                <div class="personal-attendance__chips">
                    <span class="att-chip att-chip--present">Hadir: <b>{{ personal_attendance.present }}</b></span>
                    <span class="att-chip att-chip--sick">Sakit: <b>{{ personal_attendance.sick }}</b></span>
                    <span class="att-chip att-chip--permitted">Izin: <b>{{ personal_attendance.permitted }}</b></span>
                    <span class="att-chip att-chip--absent">Alpa: <b>{{ personal_attendance.absent }}</b></span>
                </div>
            </section>

            <!-- Main Control & Data Panel -->
            <section class="panel progress-panel">
                <!-- Filter & Quick Control Bar -->
                <div class="progress-controls-wrapper">
                    <!-- Quick Status Filter Pills -->
                    <div class="status-pills-bar" role="group" aria-label="Filter cepat status belajar">
                        <button
                            type="button"
                            class="status-pill"
                            :class="{ 'is-active': filterForm.status === '' }"
                            @click="selectStatusPill('')"
                        >
                            <span>Semua Status</span>
                        </button>
                        <button
                            type="button"
                            class="status-pill status-pill--rose"
                            :class="{ 'is-active': filterForm.status === 'attention' }"
                            @click="selectStatusPill('attention')"
                        >
                            <span class="status-dot status-dot--rose" />
                            <span>Perlu Perhatian</span>
                            <b v-if="totals.attention_rows !== undefined">{{ totals.attention_rows }}</b>
                        </button>
                        <button
                            v-if="!is_student"
                            type="button"
                            class="status-pill status-pill--amber"
                            :class="{ 'is-active': filterForm.status === 'revision' }"
                            @click="selectStatusPill('revision')"
                        >
                            <span>Perlu Revisi</span>
                            <b>{{ totals.revisions }}</b>
                        </button>
                        <button
                            v-if="!is_student"
                            type="button"
                            class="status-pill status-pill--amber"
                            :class="{ 'is-active': filterForm.status === 'late' }"
                            @click="selectStatusPill('late')"
                        >
                            <span>Terlambat</span>
                            <b>{{ totals.late }}</b>
                        </button>
                        <button
                            type="button"
                            class="status-pill status-pill--slate"
                            :class="{ 'is-active': filterForm.status === 'incomplete' }"
                            @click="selectStatusPill('incomplete')"
                        >
                            <span>Belum Tuntas</span>
                            <b v-if="totals.incomplete_rows !== undefined">{{ totals.incomplete_rows }}</b>
                        </button>
                        <button
                            type="button"
                            class="status-pill status-pill--emerald"
                            :class="{ 'is-active': filterForm.status === 'complete' }"
                            @click="selectStatusPill('complete')"
                        >
                            <span class="status-dot status-dot--emerald" />
                            <span>Tuntas 100%</span>
                            <b v-if="totals.complete_rows !== undefined">{{ totals.complete_rows }}</b>
                        </button>

                        <span class="progress-result-count">
                            Menampilkan {{ pagination.from ?? 0 }}–{{ pagination.to ?? 0 }} dari {{ pagination.total }} baris
                        </span>
                    </div>

                    <!-- Search, Dropdowns & Sort Form -->
                    <form v-if="!is_student" class="toolbar progress-toolbar" @submit.prevent="applyFilters">
                        <label class="progress-search search-control-shell">
                            <span class="sr-only">Cari siswa atau mata pelajaran</span>
                            <input
                                v-model="filterForm.search"
                                type="search"
                                placeholder="Cari nama siswa, NIS, kelas, atau mata pelajaran…"
                            />
                        </label>

                        <label class="progress-class-filter">
                            <span class="sr-only">Filter kelas</span>
                            <select v-model="filterForm.class" @change="applyFilters">
                                <option value="">Semua kelas</option>
                                <option v-for="classGroup in classes" :key="classGroup.id" :value="String(classGroup.id)">
                                    Kelas {{ classGroup.name }}
                                </option>
                            </select>
                        </label>

                        <label class="progress-subject-filter">
                            <span class="sr-only">Filter mata pelajaran</span>
                            <select v-model="filterForm.subject" @change="applyFilters">
                                <option value="">Semua mata pelajaran</option>
                                <option v-for="subject in subjects" :key="subject.id" :value="String(subject.id)">
                                    {{ subject.name }}
                                </option>
                            </select>
                        </label>

                        <label class="progress-sort-filter">
                            <span class="sr-only">Urutkan berdasarkan</span>
                            <select v-model="filterForm.sort" @change="applyFilters">
                                <option value="student_asc">Urut: Nama Siswa (A–Z)</option>
                                <option value="attention_desc">Urut: Prioritas Pendampingan</option>
                                <option value="completion_asc">Urut: Progres Terendah</option>
                                <option value="completion_desc">Urut: Progres Tertinggi</option>
                                <option value="score_desc">Urut: Nilai Tertinggi</option>
                                <option value="score_asc">Urut: Nilai Terendah</option>
                            </select>
                        </label>

                        <AppButton variant="primary" class="primary-button progress-apply" type="submit">Terapkan</AppButton>
                        <AppButton variant="secondary" v-if="hasActiveFilter" class="secondary-button progress-reset" type="button" @click="clearFilters">
                            Reset
                        </AppButton>
                    </form>
                </div>

                <!-- Empty States -->
                <div v-if="!active_term" class="progress-empty" role="status">
                    <strong>Belum ada semester aktif</strong>
                    <p>Progress akan tampil setelah admin mengaktifkan semester pada Pengaturan Akademik.</p>
                </div>

                <template v-else>
                    <div v-if="!rows.length && !hasActiveFilter" class="progress-empty" role="status">
                        <strong>{{ is_student ? 'Belum ada aktivitas belajar' : 'Belum ada progress untuk periode ini' }}</strong>
                        <p>
                            {{
                                is_student
                                    ? 'Materi dan tugas akan muncul di sini setelah guru menerbitkannya untuk kelasmu.'
                                    : 'Progress siswa akan muncul setelah materi atau tugas diterbitkan pada semester aktif.'
                            }}
                        </p>
                    </div>

                    <div v-else-if="!rows.length" class="progress-empty" role="status">
                        <strong>Tidak ada hasil yang cocok</strong>
                        <p>Ubah filter status, kelas, mata pelajaran, atau kata kunci pencarian Anda.</p>
                        <AppButton variant="secondary" type="button" class="secondary-button" @click="clearFilters">Tampilkan Semua Data</AppButton>
                    </div>

                    <!-- MODE 1: TABEL ANALITIK -->
                    <template v-else-if="viewMode === 'table' || is_student">
                        <div class="table-wrap table-scroll progress-desktop-table" tabindex="0" role="region" aria-label="Rekap progres belajar">
                            <table class="app-table data-table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ is_student ? 'Mata Pelajaran' : 'Siswa' }}</th>
                                        <th v-if="!is_student" scope="col">Kelas &amp; Mapel</th>
                                        <th scope="col">Materi Dibaca</th>
                                        <th scope="col">Tugas Dikumpulkan</th>
                                        <th scope="col">Status &amp; Tindak Lanjut</th>
                                        <th scope="col" class="th-score">Rerata Nilai</th>
                                        <th v-if="can_manage_lms ?? is_student" scope="col" class="th-action">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in rows"
                                        :key="row.row_key"
                                        :class="{ 'row-needs-attention': row.needs_attention }"
                                    >
                                        <!-- Kolom 1: Identitas Siswa / Mapel -->
                                        <td>
                                            <div v-if="is_student" class="identity-cell">
                                                <strong>{{ row.subject_name }}</strong>
                                                <small>Kelas {{ row.class_name }}</small>
                                            </div>
                                            <div v-else class="identity-cell">
                                                <strong>{{ row.student_name }}</strong>
                                                <small>NIS {{ row.nis }}</small>
                                            </div>
                                        </td>

                                        <!-- Kolom 2: Kelas & Mapel (Non-Siswa) -->
                                        <td v-if="!is_student">
                                            <div class="class-subject-cell">
                                                <span class="class-chip">{{ row.class_name }}</span>
                                                <strong class="subject-title">{{ row.subject_name }}</strong>
                                            </div>
                                        </td>

                                        <!-- Kolom 3: Bar Progres Materi -->
                                        <td>
                                            <div class="metric-bar-cell">
                                                <div class="metric-bar-cell__head">
                                                    <strong>{{ row.materials_read }} / {{ row.materials_total }}</strong>
                                                    <span v-if="row.materials_total > 0">{{ ratio(row.materials_read, row.materials_total) }}%</span>
                                                </div>
                                                <div v-if="row.materials_total > 0" class="metric-bar-track" aria-hidden="true">
                                                    <span
                                                        class="metric-bar-fill"
                                                        :class="barTone(ratio(row.materials_read, row.materials_total))"
                                                        :style="{ width: `${ratio(row.materials_read, row.materials_total)}%` }"
                                                    />
                                                </div>
                                                <small>{{ row.materials_total ? `${ratio(row.materials_read, row.materials_total)}% selesai` : 'Belum ada materi terbit' }}</small>
                                            </div>
                                        </td>

                                        <!-- Kolom 4: Bar Progres Tugas -->
                                        <td>
                                            <div class="metric-bar-cell">
                                                <div class="metric-bar-cell__head">
                                                    <strong>{{ row.assignments_submitted }} / {{ row.assignments_total }}</strong>
                                                    <span v-if="row.assignments_total > 0">{{ ratio(row.assignments_submitted, row.assignments_total) }}%</span>
                                                </div>
                                                <div v-if="row.assignments_total > 0" class="metric-bar-track" aria-hidden="true">
                                                    <span
                                                        class="metric-bar-fill"
                                                        :class="barTone(ratio(row.assignments_submitted, row.assignments_total))"
                                                        :style="{ width: `${ratio(row.assignments_submitted, row.assignments_total)}%` }"
                                                    />
                                                </div>
                                                <small>{{ row.assignments_total ? `${ratio(row.assignments_submitted, row.assignments_total)}% terkumpul` : 'Belum ada tugas terbit' }}</small>
                                            </div>
                                        </td>

                                        <!-- Kolom 5: Status & Indikator Terlambat/Revisi -->
                                        <td>
                                            <div class="status-badges-cell">
                                                <span class="status-badge" :class="`status-badge--${rowStatusMeta(row).tone}`">
                                                    {{ rowStatusMeta(row).label }}
                                                </span>
                                                <div v-if="!is_student && (row.late_count > 0 || row.revision_count > 0)" class="attention-subbadges">
                                                    <span v-if="row.late_count > 0" class="sub-chip sub-chip--amber" :class="{ 'progress-attention': row.late_count > 0 }">
                                                        {{ row.late_count }} terlambat
                                                    </span>
                                                    <span v-if="row.revision_count > 0" class="sub-chip sub-chip--rose" :class="{ 'progress-attention': row.revision_count > 0 }">
                                                        {{ row.revision_count }} revisi
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Kolom 6: Rerata Nilai -->
                                        <td class="td-score">
                                            <span class="score-pill" :class="`score-pill--${scoreTone(row.average_score)}`">
                                                {{ row.average_score === null ? '—' : `${row.average_score}%` }}
                                            </span>
                                            <small v-if="row.graded_count > 0" class="graded-note">{{ row.graded_count }} dinilai</small>
                                        </td>

                                        <!-- Kolom 7: Pintasan Kontrol / Aksi -->
                                        <td v-if="can_manage_lms ?? is_student" class="td-action">
                                            <div class="row-actions">
                                                <Link
                                                    class="row-action-link ui-button ui-button--secondary ui-button--sm"
                                                    :href="
                                                        is_student
                                                            ? `/siswa/materi?subject=${row.subject_id}`
                                                            : `/pembelajaran/materi?class=${row.class_group_id ?? ''}&subject=${row.subject_id}`
                                                    "
                                                >
                                                    Materi
                                                </Link>
                                                <Link
                                                    class="row-action-link row-action-link--primary ui-button ui-button--primary ui-button--sm"
                                                    :href="
                                                        is_student
                                                            ? `/siswa/tugas?subject=${row.subject_id}`
                                                            : `/pembelajaran/tugas?class=${row.class_group_id ?? ''}&subject=${row.subject_id}`
                                                    "
                                                >
                                                    Tugas
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Kartu Responsif Mobile -->
                        <div class="progress-mobile-cards">
                            <article
                                v-for="row in rows"
                                :key="row.row_key"
                                class="progress-row-card"
                                :class="{ 'progress-row-card--attention': row.needs_attention }"
                            >
                                <div class="progress-row-heading">
                                    <div>
                                        <strong>{{ is_student ? row.subject_name : row.student_name }}</strong>
                                        <small>{{ is_student ? `Kelas ${row.class_name}` : `${row.nis} · Kelas ${row.class_name} · ${row.subject_name}` }}</small>
                                    </div>
                                    <span class="score-pill" :class="`score-pill--${scoreTone(row.average_score)}`">
                                        {{ row.average_score === null ? '—' : `${row.average_score}%` }}
                                    </span>
                                </div>

                                <div class="progress-row-metrics">
                                    <div>
                                        <span>{{ is_student ? 'Materi dipelajari' : 'Materi dibaca' }}</span>
                                        <strong>{{ row.materials_read }} / {{ row.materials_total }} ({{ ratio(row.materials_read, row.materials_total) }}%)</strong>
                                        <div v-if="row.materials_total > 0" class="metric-bar-track">
                                            <span
                                                class="metric-bar-fill"
                                                :class="barTone(ratio(row.materials_read, row.materials_total))"
                                                :style="{ width: `${ratio(row.materials_read, row.materials_total)}%` }"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <span>Tugas dikumpulkan</span>
                                        <strong>{{ row.assignments_submitted }} / {{ row.assignments_total }} ({{ ratio(row.assignments_submitted, row.assignments_total) }}%)</strong>
                                        <div v-if="row.assignments_total > 0" class="metric-bar-track">
                                            <span
                                                class="metric-bar-fill"
                                                :class="barTone(ratio(row.assignments_submitted, row.assignments_total))"
                                                :style="{ width: `${ratio(row.assignments_submitted, row.assignments_total)}%` }"
                                            />
                                        </div>
                                    </div>
                                    <div v-if="!is_student">
                                        <span>Terlambat</span>
                                        <strong :class="{ 'progress-attention': row.late_count > 0 }">{{ row.late_count }}</strong>
                                    </div>
                                    <div v-if="!is_student">
                                        <span>Perlu revisi</span>
                                        <strong :class="{ 'progress-attention': row.revision_count > 0 }">{{ row.revision_count }}</strong>
                                    </div>
                                </div>

                                <div v-if="can_manage_lms ?? is_student" class="progress-row-card__footer">
                                    <span class="status-badge" :class="`status-badge--${rowStatusMeta(row).tone}`">
                                        {{ rowStatusMeta(row).label }}
                                    </span>
                                    <div class="row-actions">
                                        <Link
                                            class="row-action-link ui-button ui-button--secondary ui-button--sm"
                                            :href="is_student ? `/siswa/materi?subject=${row.subject_id}` : `/pembelajaran/materi?class=${row.class_group_id ?? ''}&subject=${row.subject_id}`"
                                        >
                                            Materi
                                        </Link>
                                        <Link
                                            class="row-action-link row-action-link--primary ui-button ui-button--primary ui-button--sm"
                                            :href="is_student ? `/siswa/tugas?subject=${row.subject_id}` : `/pembelajaran/tugas?class=${row.class_group_id ?? ''}&subject=${row.subject_id}`"
                                        >
                                            Tugas
                                        </Link>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </template>

                    <!-- MODE 2: KARTU RINGKAS PER SISWA (Non-Siswa) -->
                    <div v-else class="student-groups-grid" role="region" aria-label="Ringkasan progres per siswa">
                        <article
                            v-for="student in studentGroupsList"
                            :key="student.enrollment_id"
                            class="student-group-card"
                            :class="{ 'student-group-card--attention': student.needs_attention }"
                        >
                            <header class="student-group-card__header">
                                <div class="student-group-card__identity">
                                    <div class="student-group-card__title-row">
                                        <strong>{{ student.student_name }}</strong>
                                        <span class="class-chip">Kelas {{ student.class_name }}</span>
                                    </div>
                                    <small>NIS {{ student.nis }} · {{ student.subjects_count }} mata pelajaran</small>
                                </div>
                                <div class="student-group-card__score">
                                    <span class="score-pill" :class="`score-pill--${scoreTone(student.average_score)}`">
                                        {{ student.average_score === null ? '—' : `${student.average_score}%` }}
                                    </span>
                                    <small>Rerata</small>
                                </div>
                            </header>

                            <div class="student-group-card__bars">
                                <div class="group-bar-item">
                                    <div class="group-bar-item__label">
                                        <span>Ketuntasan Total</span>
                                        <strong>{{ student.completion_percentage }}%</strong>
                                    </div>
                                    <div class="metric-bar-track">
                                        <span
                                            class="metric-bar-fill"
                                            :class="barTone(student.completion_percentage)"
                                            :style="{ width: `${student.completion_percentage}%` }"
                                        />
                                    </div>
                                </div>

                                <div class="student-group-card__counters">
                                    <div class="counter-box">
                                        <span>Materi Dibaca</span>
                                        <strong>{{ student.materials_read }} / {{ student.materials_total }}</strong>
                                    </div>
                                    <div class="counter-box">
                                        <span>Tugas Dikumpulkan</span>
                                        <strong>{{ student.assignments_submitted }} / {{ student.assignments_total }}</strong>
                                    </div>
                                    <div class="counter-box" :class="{ 'counter-box--warn': student.late_count > 0 }">
                                        <span>Terlambat</span>
                                        <strong>{{ student.late_count }}</strong>
                                    </div>
                                    <div class="counter-box" :class="{ 'counter-box--danger': student.revision_count > 0 }">
                                        <span>Perlu Revisi</span>
                                        <strong>{{ student.revision_count }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Daftar Mapel per Siswa -->
                            <div class="student-group-card__subjects">
                                <div
                                    v-for="sub in (expandedStudents[student.enrollment_id] ? student.subjects : student.subjects.slice(0, 3))"
                                    :key="sub.row_key"
                                    class="student-subject-row"
                                >
                                    <div class="student-subject-row__info">
                                        <strong>{{ sub.subject_name }}</strong>
                                        <small>
                                            Materi {{ sub.materials_read }}/{{ sub.materials_total }} · Tugas {{ sub.assignments_submitted }}/{{ sub.assignments_total }}
                                            <template v-if="sub.revision_count > 0"> · <b class="text-rose">{{ sub.revision_count }} revisi</b></template>
                                            <template v-else-if="sub.late_count > 0"> · <b class="text-amber">{{ sub.late_count }} terlambat</b></template>
                                        </small>
                                    </div>
                                    <div class="student-subject-row__right">
                                        <span class="score-pill score-pill--sm" :class="`score-pill--${scoreTone(sub.average_score)}`">
                                            {{ sub.average_score === null ? '—' : `${sub.average_score}%` }}
                                        </span>
                                        <Link
                                            v-if="can_manage_lms"
                                            class="row-action-link ui-button ui-button--secondary ui-button--sm"
                                            :href="`/pembelajaran/tugas?class=${sub.class_group_id ?? student.class_group_id}&subject=${sub.subject_id}`"
                                        >
                                            Buka
                                        </Link>
                                    </div>
                                </div>

                                <AppButton variant="secondary"
                                    v-if="student.subjects.length > 3"
                                    type="button"
                                    class="expand-subjects-btn"
                                    @click="toggleStudentExpand(student.enrollment_id)"
                                >
                                    {{
                                        expandedStudents[student.enrollment_id]
                                            ? 'Sembunyikan sebagian mata pelajaran'
                                            : `Lihat semua ${student.subjects.length} mata pelajaran`
                                    }}
                                </AppButton>
                            </div>
                        </article>
                    </div>
                </template>

                <!-- Pagination -->
                <nav v-if="viewMode === 'table' && pagination.total > 15" class="progress-pagination" aria-label="Halaman progress belajar">
                    <Link
                        v-for="(link, index) in pagination.links"
                        :key="index"
                        :href="link.url ?? '#progress'"
                        :class="{ current: link.active, disabled: !link.url }"
                        preserve-scroll
                        v-html="link.label"
                    />
                </nav>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.compact-hero {
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.progress-hero__eyebrow-row {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-wrap: wrap;
    margin-bottom: 0.2rem;
}

.term-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.18rem 0.6rem;
    font-size: var(--type-meta);
    font-weight: var(--weight-bold);
    color: var(--primary);
    background: color-mix(in srgb, var(--primary) 12%, var(--surface));
    border: 1px solid color-mix(in srgb, var(--primary) 26%, var(--line));
    border-radius: 999px;
}

.progress-hero__actions {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.view-mode-switch {
    display: inline-flex;
    align-items: center;
    padding: 0.22rem;
    background: color-mix(in srgb, var(--surface-muted, var(--surface)) 75%, var(--line));
    border: 1px solid var(--line-strong);
    border-radius: 0.7rem;
    gap: 0.2rem;
}

.view-mode-btn.is-active {
    color: var(--primary);
    background: var(--surface);
}

/* KPI Summary Cards */
.grade-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.85rem;
    margin: 0;
}

.kpi-card {
    display: grid;
    align-content: space-between;
    gap: 0.35rem;
    min-width: 0;
    padding: 1rem 1.1rem;
    text-align: left !important;
    color: var(--ink);
    background: var(--surface);
    border: 1px solid var(--line);
    border-top: 3px solid #3157a4;
    border-radius: 0.85rem;
}

.kpi-card:nth-child(2) {
    border-top-color: #7952a3;
}

.kpi-card:nth-child(3) {
    border-top-color: #138a62;
}

.kpi-card:nth-child(4) {
    border-top-color: #168782;
}

.kpi-card.score-summary-red {
    border-top-color: #b42318;
}

.kpi-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.kpi-card__top > span:first-child:not(.kpi-badge) {
    color: var(--ink-faint);
    font-size: var(--type-meta);
    font-weight: var(--weight-semibold);
}

.kpi-card > strong {
    overflow-wrap: anywhere;
    color: var(--ink);
    font-size: 1.45rem;
    line-height: 1.2;
    letter-spacing: -0.03em;
}

.kpi-inline-unit {
    display: inline !important;
    font-size: var(--type-sm);
    font-weight: var(--weight-semibold);
    color: var(--ink-soft);
}

.kpi-inline-score {
    display: inline !important;
    font-size: var(--type-sm) !important;
    color: var(--ink);
}

.kpi-card > small {
    display: block;
    margin-top: 0.1rem;
    color: var(--ink-soft);
    font-size: var(--type-meta);
}

.kpi-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    font-size: var(--type-meta);
    font-weight: var(--weight-semibold);
    white-space: nowrap;
}

.kpi-badge--blue {
    color: var(--blue);
    background: var(--blue-soft);
}

.kpi-badge--purple {
    color: var(--violet);
    background: var(--violet-soft);
}

.kpi-badge--emerald {
    color: var(--green);
    background: var(--green-soft);
}

.kpi-badge--rose {
    color: var(--red);
    background: var(--red-soft);
}

.kpi-badge--slate {
    color: var(--ink-soft);
    background: color-mix(in srgb, var(--ink-faint) 14%, transparent);
}

.mini-progress-track {
    width: 100%;
    height: 6px;
    background: color-mix(in srgb, var(--line-strong) 55%, transparent);
    border-radius: 999px;
    overflow: hidden;
}

.mini-progress-fill {
    display: block;
    height: 100%;
    border-radius: 999px;
    transition: width 0.25s ease;
}

/* Personal Attendance (Student) */
.personal-attendance {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1rem 1.25rem;
    color: var(--ink);
    background: linear-gradient(135deg, var(--surface) 0%, var(--green-soft) 170%);
    border-color: color-mix(in srgb, var(--green) 26%, var(--line));
}

.personal-attendance__lead span {
    display: block;
    color: var(--ink-soft);
    font-size: var(--type-meta);
}

.personal-attendance__lead strong {
    color: var(--green);
    font-size: 1.5rem;
}

.personal-attendance__chips {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.att-chip {
    padding: 0.3rem 0.65rem;
    border-radius: 999px;
    font-size: var(--type-meta);
    background: var(--surface);
    border: 1px solid var(--line);
}

/* Controls & Status Pills */
.progress-panel {
    overflow: hidden;
}

.progress-controls-wrapper {
    display: grid;
    gap: 0.75rem;
    padding: 1rem 1.15rem;
    border-bottom: 1px solid var(--line);
    background: color-mix(in srgb, var(--surface) 92%, var(--line));
}

.status-pills-bar {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.38rem;
    padding: 0.36rem 0.72rem;
    font-size: var(--type-meta);
    font-weight: var(--weight-semibold);
    color: var(--ink-soft);
    background: var(--surface);
    border: 1px solid var(--line-strong);
    border-radius: 999px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.status-pill:hover {
    border-color: var(--primary);
    color: var(--ink);
}

.status-pill.is-active {
    color: #fff;
    background: var(--primary-action);
    border-color: var(--primary-action);
}

.status-pill b {
    padding: 0.05rem 0.38rem;
    font-size: var(--type-meta);
    border-radius: 999px;
    background: color-mix(in srgb, currentColor 14%, transparent);
}

.status-pill.is-active b {
    color: #fff;
    background: rgba(255, 255, 255, 0.22);
}

.status-dot {
    width: 7px;
    height: 7px;
    border-radius: 999px;
}

.status-dot--rose {
    background: #e11d48;
}

.status-dot--emerald {
    background: #10b981;
}

.progress-result-count {
    margin-left: auto;
    color: var(--ink-faint);
    font-size: var(--type-meta);
    font-weight: var(--weight-semibold);
    white-space: nowrap;
}

.progress-toolbar {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
    padding: 0;
}

.progress-search {
    min-width: 220px;
    flex: 1;
}

.progress-toolbar input,
.progress-toolbar select {
    width: 100%;
    min-height: 38px;
    padding: 0.5rem 0.75rem;
    color: var(--ink);
    background: var(--surface);
    border: 1px solid var(--line-strong);
    border-radius: 0.6rem;
    font-size: var(--type-control);
}

.progress-toolbar input::placeholder {
    color: var(--ink-faint);
}

.progress-class-filter {
    width: min(175px, 100%);
}

.progress-subject-filter {
    width: min(210px, 100%);
}

.progress-sort-filter {
    width: min(220px, 100%);
}

/* Table Analytic View */
.table-wrap {
    overflow-x: auto;
}

.progress-desktop-table .data-table {
    width: 100%;
    min-width: 860px;
}

.progress-desktop-table .data-table tr.row-needs-attention {
    background: color-mix(in srgb, #f43f5e 4%, var(--surface));
}

.identity-cell strong {
    display: block;
    color: var(--ink);
    font-size: var(--type-sm);
}

.identity-cell small {
    display: block;
    margin-top: 0.15rem;
    color: var(--ink-faint);
    font-size: var(--type-meta);
}

.class-subject-cell {
    display: grid;
    gap: 0.22rem;
    justify-items: start;
}

.class-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.12rem 0.48rem;
    font-size: var(--type-meta);
    font-weight: var(--weight-bold);
    color: var(--primary);
    background: color-mix(in srgb, var(--primary) 11%, transparent);
    border-radius: 0.38rem;
}

.subject-title {
    font-size: var(--type-sm);
    color: var(--ink);
}

.metric-bar-cell {
    display: grid;
    gap: 0.26rem;
    min-width: 135px;
}

.metric-bar-cell__head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.4rem;
}

.metric-bar-cell__head strong {
    font-size: var(--type-sm);
    color: var(--ink);
}

.metric-bar-cell__head span {
    font-size: var(--type-meta);
    font-weight: var(--weight-bold);
    color: var(--ink-soft);
}

.metric-bar-track {
    width: 100%;
    height: 6px;
    background: color-mix(in srgb, var(--line-strong) 60%, transparent);
    border-radius: 999px;
    overflow: hidden;
}

.metric-bar-fill {
    display: block;
    height: 100%;
    border-radius: 999px;
    transition: width 0.25s ease;
}

.bar-fill--emerald {
    background: #10b981;
}

.bar-fill--amber {
    background: #f59e0b;
}

.bar-fill--rose {
    background: #e11d48;
}

.metric-bar-cell small {
    color: var(--ink-faint);
    font-size: var(--type-meta);
}

/* Status & Score Badges */
.status-badges-cell {
    display: grid;
    gap: 0.3rem;
    justify-items: start;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.22rem 0.58rem;
    border-radius: 999px;
    font-size: var(--type-meta);
    font-weight: var(--weight-semibold);
    white-space: nowrap;
}

.status-badge--emerald {
    color: var(--green);
    background: var(--green-soft);
}

.status-badge--amber {
    color: var(--gold);
    background: var(--gold-soft);
}

.status-badge--rose {
    color: var(--red);
    background: var(--red-soft);
}

.status-badge--slate {
    color: var(--ink-soft);
    background: color-mix(in srgb, var(--ink-faint) 15%, transparent);
}

.attention-subbadges {
    display: flex;
    gap: 0.3rem;
    flex-wrap: wrap;
}

.sub-chip {
    font-size: var(--type-meta);
    font-weight: var(--weight-bold);
    padding: 0.1rem 0.42rem;
    border-radius: 0.35rem;
}

.sub-chip--amber {
    color: var(--gold);
    background: var(--gold-soft);
}

.sub-chip--rose {
    color: var(--red);
    background: var(--red-soft);
}

.progress-attention {
    color: var(--red);
    font-weight: var(--weight-bold);
}

.th-score,
.td-score {
    text-align: center;
}

.score-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 3.1rem;
    padding: 0.26rem 0.6rem;
    border-radius: 999px;
    font-size: var(--type-meta);
    font-weight: var(--weight-bold);
}

.score-pill--sm {
    min-width: 2.6rem;
    padding: 0.18rem 0.48rem;
    font-size: var(--type-meta);
}

.score-pill--emerald {
    color: var(--green);
    background: var(--green-soft);
}

.score-pill--amber {
    color: var(--gold);
    background: var(--gold-soft);
}

.score-pill--rose {
    color: var(--red);
    background: var(--red-soft);
}

.score-pill--slate {
    color: var(--ink-soft);
    background: color-mix(in srgb, var(--line-strong) 45%, transparent);
}

.graded-note {
    display: block;
    margin-top: 0.18rem;
    font-size: var(--type-meta);
    color: var(--ink-faint);
}

.th-action,
.td-action {
    text-align: right;
    white-space: nowrap;
}

.row-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

/* Mode 2: Kartu Ringkas per Siswa */
.student-groups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1rem;
    padding: 1.15rem;
}

.student-group-card {
    display: grid;
    align-content: start;
    gap: 0.85rem;
    padding: 1.05rem;
    background: var(--surface);
    border: 1px solid var(--line);
    border-left: 4px solid var(--primary);
    border-radius: 0.85rem;
}

.student-group-card--attention {
    border-left-color: #e11d48;
}

.student-group-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    padding-bottom: 0.7rem;
    border-bottom: 1px solid var(--line);
}

.student-group-card__title-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.student-group-card__title-row strong {
    font-size: var(--type-md);
    color: var(--ink);
}

.student-group-card__identity small {
    display: block;
    margin-top: 0.2rem;
    color: var(--ink-faint);
    font-size: var(--type-meta);
}

.student-group-card__score {
    text-align: right;
}

.student-group-card__score small {
    display: block;
    margin-top: 0.15rem;
    font-size: var(--type-meta);
    color: var(--ink-faint);
}

.student-group-card__bars {
    display: grid;
    gap: 0.65rem;
}

.group-bar-item {
    display: grid;
    gap: 0.25rem;
}

.group-bar-item__label {
    display: flex;
    justify-content: space-between;
    font-size: var(--type-meta);
}

.group-bar-item__label span {
    color: var(--ink-soft);
}

.group-bar-item__label strong {
    color: var(--ink);
}

.student-group-card__counters {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.45rem;
}

.counter-box {
    padding: 0.45rem 0.5rem;
    background: color-mix(in srgb, var(--surface) 75%, var(--line));
    border: 1px solid var(--line);
    border-radius: 0.55rem;
    text-align: center;
}

.counter-box span {
    display: block;
    font-size: var(--type-meta);
    color: var(--ink-faint);
}

.counter-box strong {
    display: block;
    margin-top: 0.1rem;
    font-size: var(--type-meta);
    color: var(--ink);
}

.counter-box--warn strong {
    color: var(--gold);
}

.counter-box--danger strong {
    color: var(--red);
}

.student-group-card__subjects {
    display: grid;
    gap: 0.45rem;
    padding-top: 0.55rem;
    border-top: 1px dashed var(--line);
}

.student-subject-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.45rem 0.6rem;
    background: color-mix(in srgb, var(--surface) 90%, var(--line));
    border: 1px solid var(--line);
    border-radius: 0.55rem;
}

.student-subject-row__info strong {
    display: block;
    font-size: var(--type-meta);
    color: var(--ink);
}

.student-subject-row__info small {
    display: block;
    font-size: var(--type-meta);
    color: var(--ink-soft);
}

.text-rose {
    color: var(--red);
}

.text-amber {
    color: var(--gold);
}

.student-subject-row__right {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

/* Empty & Mobile Cards */
.progress-empty {
    display: grid;
    justify-items: center;
    gap: 0.45rem;
    padding: 3rem 1.2rem;
    text-align: center;
}

.progress-empty strong {
    color: var(--ink);
    font-size: var(--type-md);
}

.progress-empty p {
    max-width: 480px;
    margin: 0;
    color: var(--ink-soft);
    font-size: var(--type-meta);
    line-height: 1.55;
}

.progress-mobile-cards {
    display: none;
}

.progress-row-card {
    padding: 1rem;
    color: var(--ink);
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 0.85rem;
}

.progress-row-card--attention {
    border-left: 4px solid #e11d48;
}

.progress-row-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    padding-bottom: 0.7rem;
    border-bottom: 1px solid var(--line);
}

.progress-row-heading strong,
.progress-row-heading small {
    display: block;
}

.progress-row-heading strong {
    font-size: var(--type-md);
}

.progress-row-heading small {
    margin-top: 0.2rem;
    color: var(--ink-faint);
    font-size: var(--type-meta);
    line-height: 1.4;
}

.progress-row-metrics {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
    padding-top: 0.75rem;
}

.progress-row-metrics div {
    display: grid;
    gap: 0.2rem;
}

.progress-row-metrics span {
    color: var(--ink-faint);
    font-size: var(--type-meta);
}

.progress-row-metrics strong {
    color: var(--ink);
    font-size: var(--type-meta);
}

.progress-row-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding-top: 0.65rem;
    border-top: 1px solid var(--line);
}

.progress-pagination {
    display: flex;
    justify-content: center;
    gap: 0.35rem;
    padding: 1rem;
    border-top: 1px solid var(--line);
}

.progress-pagination :deep(a) {
    padding: 0.4rem 0.65rem;
    color: var(--ink);
    border: 1px solid var(--line);
    border-radius: 0.4rem;
    font-size: var(--type-meta);
    text-decoration: none;
}

.progress-pagination :deep(a.current) {
    color: #fff;
    background: var(--primary-action);
    border-color: var(--primary-action);
}

.progress-pagination :deep(a.disabled) {
    opacity: 0.45;
    pointer-events: none;
}

@media (max-width: 960px) {
    .grade-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .compact-hero {
        align-items: flex-start;
        flex-direction: column;
    }

    .progress-toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .progress-search,
    .progress-class-filter,
    .progress-subject-filter,
    .progress-sort-filter {
        width: 100%;
        min-width: 0;
    }

    .progress-result-count {
        margin-left: 0;
        width: 100%;
    }

    .personal-attendance {
        align-items: flex-start;
        flex-direction: column;
    }

    .progress-desktop-table {
        display: none;
    }

    .progress-mobile-cards {
        display: grid;
        gap: 0.7rem;
        padding: 1rem;
    }

    .student-groups-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .grade-summary {
        grid-template-columns: 1fr;
    }

    .student-group-card__counters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
