<script setup lang="ts">
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
                        <p class="eyebrow">{{ is_student ? 'Ringkasan belajar saya' : 'Monitoring Pembelajaran & LMS' }}</p>
                        <span v-if="active_term" class="term-pill">{{ active_term.name }}</span>
                    </div>
                    <h1>{{ is_student ? 'Progress Belajar Saya' : 'Progress Belajar Siswa' }}</h1>
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
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="viewMode === 'table'"
                            class="view-mode-btn"
                            :class="{ 'is-active': viewMode === 'table' }"
                            @click="viewMode = 'table'"
                        >
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h14M3 10h14M3 15h14" />
                            </svg>
                            <span>Tabel Analitik</span>
                        </button>
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="viewMode === 'students'"
                            class="view-mode-btn"
                            :class="{ 'is-active': viewMode === 'students' }"
                            @click="viewMode = 'students'"
                        >
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <rect x="3" y="3.5" width="6" height="6" rx="1.2" />
                                <rect x="11" y="3.5" width="6" height="6" rx="1.2" />
                                <rect x="3" y="11" width="6" height="5.5" rx="1.2" />
                                <rect x="11" y="11" width="6" height="5.5" rx="1.2" />
                            </svg>
                            <span>Kartu per Siswa</span>
                        </button>
                    </div>

                    <Link
                        v-if="can_manage_lms ?? is_student"
                        class="secondary-button progress-back-link"
                        :href="is_student ? '/siswa/materi' : '/pembelajaran'"
                    >
                        {{ is_student ? 'Buka Materi & Tugas' : 'Ruang Pembelajaran' }}
                    </Link>
                </div>
            </section>

            <!-- Summary Metric Cards (Guru / Admin / Wali Kelas / Kepsek) -->
            <section v-if="!is_student" class="grade-summary progress-kpi-grid" aria-label="Ringkasan progres hasil filter">
                <article class="kpi-card">
                    <div class="kpi-card__top">
                        <span>Cakupan Siswa</span>
                        <span class="kpi-badge kpi-badge--blue">{{ pagination.total }} baris mapel</span>
                    </div>
                    <strong>{{ totals.students }}</strong>
                    <small>siswa aktif pada hasil filter</small>
                </article>

                <article class="kpi-card">
                    <div class="kpi-card__top">
                        <span>Keterbacaan Materi</span>
                        <span class="kpi-badge kpi-badge--purple">
                            {{ totals.materials_total ? `${ratio(totals.materials_read, totals.materials_total)}%` : '0%' }}
                        </span>
                    </div>
                    <strong>{{ totals.materials_read }} / {{ totals.materials_total }}</strong>
                    <div class="mini-progress-track" aria-hidden="true">
                        <span
                            class="mini-progress-fill"
                            :class="barTone(ratio(totals.materials_read, totals.materials_total))"
                            :style="{ width: `${ratio(totals.materials_read, totals.materials_total)}%` }"
                        />
                    </div>
                    <small>{{ totals.materials_total ? `${ratio(totals.materials_read, totals.materials_total)}% dari aktivitas materi terbit` : 'belum ada materi terbit' }}</small>
                </article>

                <article class="kpi-card">
                    <div class="kpi-card__top">
                        <span>Pengumpulan Tugas</span>
                        <span class="kpi-badge kpi-badge--emerald">
                            {{ totals.assignments_total ? `${ratio(totals.assignments_submitted, totals.assignments_total)}%` : '0%' }}
                        </span>
                    </div>
                    <strong>{{ totals.assignments_submitted }} / {{ totals.assignments_total }}</strong>
                    <div class="mini-progress-track" aria-hidden="true">
                        <span
                            class="mini-progress-fill"
                            :class="barTone(ratio(totals.assignments_submitted, totals.assignments_total))"
                            :style="{ width: `${ratio(totals.assignments_submitted, totals.assignments_total)}%` }"
                        />
                    </div>
                    <small>{{ totals.assignments_total ? `${ratio(totals.assignments_submitted, totals.assignments_total)}% dari tugas terbit` : 'belum ada tugas terbit' }}</small>
                </article>

                <article class="kpi-card" :class="{ 'score-summary-red': totals.late > 0 || totals.revisions > 0 }">
                    <div class="kpi-card__top">
                        <span>Perlu Tindak Lanjut</span>
                        <span class="kpi-badge" :class="totals.late > 0 || totals.revisions > 0 ? 'kpi-badge--rose' : 'kpi-badge--slate'">
                            {{ totals.revisions }} revisi
                        </span>
                    </div>
                    <strong>{{ totals.late }} <small class="kpi-inline-unit">terlambat</small></strong>
                    <small>
                        Rerata nilai kelas:
                        <strong class="kpi-inline-score">{{ totals.average_score === null ? '—' : `${totals.average_score}%` }}</strong>
                    </small>
                </article>
            </section>

            <!-- Summary Metric Cards (Siswa) -->
            <section v-else class="grade-summary student-summary progress-kpi-grid" aria-label="Ringkasan progress belajar saya">
                <article class="kpi-card">
                    <div class="kpi-card__top">
                        <span>Kehadiran</span>
                        <span class="kpi-badge kpi-badge--emerald">Presensi</span>
                    </div>
                    <strong>{{ personal_attendance ? `${personal_attendance.percentage}%` : '—' }}</strong>
                    <small>{{ personal_attendance ? `${personal_attendance.present} kali hadir dari ${personal_attendance.total} catatan` : 'belum ada catatan presensi' }}</small>
                </article>

                <article class="kpi-card">
                    <div class="kpi-card__top">
                        <span>Materi Dipelajari</span>
                        <span class="kpi-badge kpi-badge--purple">{{ ratio(totals.materials_read, totals.materials_total) }}%</span>
                    </div>
                    <strong>{{ totals.materials_read }} / {{ totals.materials_total }}</strong>
                    <div class="mini-progress-track" aria-hidden="true">
                        <span
                            class="mini-progress-fill"
                            :class="barTone(ratio(totals.materials_read, totals.materials_total))"
                            :style="{ width: `${ratio(totals.materials_read, totals.materials_total)}%` }"
                        />
                    </div>
                    <small>{{ totals.materials_total ? `${ratio(totals.materials_read, totals.materials_total)}% materi terbit` : 'belum ada materi terbit' }}</small>
                </article>

                <article class="kpi-card">
                    <div class="kpi-card__top">
                        <span>Tugas Dikumpulkan</span>
                        <span class="kpi-badge kpi-badge--emerald">{{ ratio(totals.assignments_submitted, totals.assignments_total) }}%</span>
                    </div>
                    <strong>{{ totals.assignments_submitted }} / {{ totals.assignments_total }}</strong>
                    <div class="mini-progress-track" aria-hidden="true">
                        <span
                            class="mini-progress-fill"
                            :class="barTone(ratio(totals.assignments_submitted, totals.assignments_total))"
                            :style="{ width: `${ratio(totals.assignments_submitted, totals.assignments_total)}%` }"
                        />
                    </div>
                    <small>{{ totals.assignments_total ? `${ratio(totals.assignments_submitted, totals.assignments_total)}% tugas terbit` : 'belum ada tugas terbit' }}</small>
                </article>

                <article class="kpi-card">
                    <div class="kpi-card__top">
                        <span>Rerata Nilai</span>
                        <span v-if="totals.revisions > 0" class="kpi-badge kpi-badge--rose">{{ totals.revisions }} perlu revisi</span>
                        <span v-else class="kpi-badge kpi-badge--blue">Capaian</span>
                    </div>
                    <strong>{{ totals.average_score === null ? '—' : `${totals.average_score}%` }}</strong>
                    <small>berdasarkan tugas yang dinilai</small>
                </article>
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

                        <button class="primary-button progress-apply" type="submit">Terapkan</button>
                        <button v-if="hasActiveFilter" class="secondary-button progress-reset" type="button" @click="clearFilters">
                            Reset
                        </button>
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
                        <button type="button" class="secondary-button" @click="clearFilters">Tampilkan Semua Data</button>
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
                                                    class="row-action-link"
                                                    :href="
                                                        is_student
                                                            ? `/siswa/materi?subject=${row.subject_id}`
                                                            : `/pembelajaran/materi?class=${row.class_group_id ?? ''}&subject=${row.subject_id}`
                                                    "
                                                >
                                                    Materi
                                                </Link>
                                                <Link
                                                    class="row-action-link row-action-link--primary"
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
                                            class="row-action-link"
                                            :href="is_student ? `/siswa/materi?subject=${row.subject_id}` : `/pembelajaran/materi?class=${row.class_group_id ?? ''}&subject=${row.subject_id}`"
                                        >
                                            Materi
                                        </Link>
                                        <Link
                                            class="row-action-link row-action-link--primary"
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
                                            class="row-action-link"
                                            :href="`/pembelajaran/tugas?class=${sub.class_group_id ?? student.class_group_id}&subject=${sub.subject_id}`"
                                        >
                                            Buka
                                        </Link>
                                    </div>
                                </div>

                                <button
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
                                </button>
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
    font-size: 0.7rem;
    font-weight: 700;
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

.view-mode-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.42rem 0.75rem;
    font-size: 0.76rem;
    font-weight: 700;
    color: var(--ink-soft);
    background: transparent;
    border: 0;
    border-radius: 0.52rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.view-mode-btn svg {
    width: 0.95rem;
    height: 0.95rem;
}

.view-mode-btn.is-active {
    color: var(--primary);
    background: var(--surface);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
}

.progress-back-link {
    white-space: nowrap;
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

.kpi-card__top > span:first-child {
    color: var(--ink-faint);
    font-size: 0.73rem;
    font-weight: 600;
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
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--ink-soft);
}

.kpi-inline-score {
    display: inline !important;
    font-size: 0.82rem !important;
    color: var(--ink);
}

.kpi-card > small {
    display: block;
    margin-top: 0.1rem;
    color: var(--ink-soft);
    font-size: 0.72rem;
}

.kpi-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    white-space: nowrap;
}

.kpi-badge--blue {
    color: #1d4ed8;
    background: rgba(59, 130, 246, 0.12);
}

.kpi-badge--purple {
    color: #6d28d9;
    background: rgba(139, 92, 246, 0.12);
}

.kpi-badge--emerald {
    color: #047857;
    background: rgba(16, 185, 129, 0.13);
}

.kpi-badge--rose {
    color: #be123c;
    background: rgba(244, 63, 94, 0.13);
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
    font-size: 0.76rem;
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
    font-size: 0.75rem;
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
    font-size: 0.74rem;
    font-weight: 700;
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
    background: var(--primary);
    border-color: var(--primary);
}

.status-pill b {
    padding: 0.05rem 0.38rem;
    font-size: 0.68rem;
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
    font-size: 0.73rem;
    font-weight: 600;
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
    font-size: 0.8rem;
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

.progress-reset,
.progress-apply {
    min-height: 38px;
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
    font-size: 0.84rem;
}

.identity-cell small {
    display: block;
    margin-top: 0.15rem;
    color: var(--ink-faint);
    font-size: 0.72rem;
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
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--primary);
    background: color-mix(in srgb, var(--primary) 11%, transparent);
    border-radius: 0.38rem;
}

.subject-title {
    font-size: 0.82rem;
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
    font-size: 0.82rem;
    color: var(--ink);
}

.metric-bar-cell__head span {
    font-size: 0.7rem;
    font-weight: 700;
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
    font-size: 0.69rem;
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
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
}

.status-badge--emerald {
    color: #047857;
    background: rgba(16, 185, 129, 0.14);
}

.status-badge--amber {
    color: #b45309;
    background: rgba(245, 158, 11, 0.15);
}

.status-badge--rose {
    color: #be123c;
    background: rgba(244, 63, 94, 0.14);
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
    font-size: 0.67rem;
    font-weight: 700;
    padding: 0.1rem 0.42rem;
    border-radius: 0.35rem;
}

.sub-chip--amber {
    color: #b45309;
    background: rgba(245, 158, 11, 0.13);
}

.sub-chip--rose {
    color: #be123c;
    background: rgba(244, 63, 94, 0.13);
}

.progress-attention {
    color: var(--red);
    font-weight: 800;
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
    font-size: 0.78rem;
    font-weight: 800;
}

.score-pill--sm {
    min-width: 2.6rem;
    padding: 0.18rem 0.48rem;
    font-size: 0.72rem;
}

.score-pill--emerald {
    color: #047857;
    background: rgba(16, 185, 129, 0.15);
}

.score-pill--amber {
    color: #b45309;
    background: rgba(245, 158, 11, 0.16);
}

.score-pill--rose {
    color: #be123c;
    background: rgba(244, 63, 94, 0.15);
}

.score-pill--slate {
    color: var(--ink-faint);
    background: color-mix(in srgb, var(--line-strong) 45%, transparent);
}

.graded-note {
    display: block;
    margin-top: 0.18rem;
    font-size: 0.66rem;
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

.row-action-link {
    display: inline-flex;
    align-items: center;
    padding: 0.28rem 0.58rem;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--ink);
    background: var(--surface);
    border: 1px solid var(--line-strong);
    border-radius: 0.45rem;
    text-decoration: none;
    transition: all 0.15s ease;
}

.row-action-link:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.row-action-link--primary {
    color: var(--primary);
    background: color-mix(in srgb, var(--primary) 10%, var(--surface));
    border-color: color-mix(in srgb, var(--primary) 30%, var(--line));
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
    font-size: 0.92rem;
    color: var(--ink);
}

.student-group-card__identity small {
    display: block;
    margin-top: 0.2rem;
    color: var(--ink-faint);
    font-size: 0.72rem;
}

.student-group-card__score {
    text-align: right;
}

.student-group-card__score small {
    display: block;
    margin-top: 0.15rem;
    font-size: 0.65rem;
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
    font-size: 0.74rem;
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
    font-size: 0.63rem;
    color: var(--ink-faint);
}

.counter-box strong {
    display: block;
    margin-top: 0.1rem;
    font-size: 0.78rem;
    color: var(--ink);
}

.counter-box--warn strong {
    color: #b45309;
}

.counter-box--danger strong {
    color: #be123c;
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
    font-size: 0.78rem;
    color: var(--ink);
}

.student-subject-row__info small {
    display: block;
    font-size: 0.68rem;
    color: var(--ink-soft);
}

.text-rose {
    color: #be123c;
}

.text-amber {
    color: #b45309;
}

.student-subject-row__right {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.expand-subjects-btn {
    padding: 0.35rem;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--primary);
    background: transparent;
    border: 1px dashed color-mix(in srgb, var(--primary) 35%, var(--line));
    border-radius: 0.45rem;
    cursor: pointer;
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
    font-size: 0.95rem;
}

.progress-empty p {
    max-width: 480px;
    margin: 0;
    color: var(--ink-soft);
    font-size: 0.8rem;
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
    font-size: 0.88rem;
}

.progress-row-heading small {
    margin-top: 0.2rem;
    color: var(--ink-faint);
    font-size: 0.68rem;
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
    font-size: 0.65rem;
}

.progress-row-metrics strong {
    color: var(--ink);
    font-size: 0.78rem;
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
    font-size: 0.8rem;
    text-decoration: none;
}

.progress-pagination :deep(a.current) {
    color: #fff;
    background: var(--primary);
    border-color: var(--primary);
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
