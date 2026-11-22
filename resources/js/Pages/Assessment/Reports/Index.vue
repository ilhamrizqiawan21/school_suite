<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface ExtraItem {
    name: string;
    predicate?: string | null;
    description?: string | null;
}

interface GradeItem {
    subject_id: number;
    subject_name: string;
    final_score: number | null;
    predicate?: string | null;
    competency_description?: string | null;
}

interface ReportItem {
    id: number;
    student_id?: number;
    student_name: string;
    nis: string;
    status: 'draft' | 'reviewed' | 'published';
    report_mode?: 'sas' | 'sts';
    published_at: string | null;
    scheme_snapshot: {
        version?: number;
        report_mode?: 'sas' | 'sts';
        grades_by_mode?: Partial<Record<'sas' | 'sts', GradeItem[]>>;
    };
    grades_snapshot?: GradeItem[];
    extracurricular_snapshot?: ExtraItem[];
    notes_snapshot?: {
        homeroom_report_note?: string | null;
        promotion_decision?: string | null;
        report_place?: string | null;
        report_date?: string | null;
    };
}

interface SubjectReadiness {
    teaching_assignment_id: number;
    subject_id: number;
    subject_name: string;
    teacher_name: string;
    scored_students: number;
    total_students: number;
    completion_percent: number;
}

interface ReadinessData {
    total_students: number;
    overall_completion_percent: number;
    subjects: SubjectReadiness[];
}

const props = defineProps<{
    academic_years?: { id: number; name: string; is_active?: boolean }[];
    selected_academic_year_id?: string;
    terms?: { id: number; academic_year_id: number; name: string; is_active?: boolean }[];
    selected_term_id?: string;
    active_term?: { id: number; name: string; academic_year_id?: number; academic_year?: { id: number; name: string } } | null;
    classes: { id: number; name: string; grade_level: number }[];
    selected_class_id?: string;
    report_mode?: 'sas' | 'sts';
    reports: ReportItem[];
    readiness?: ReadinessData | null;
    is_student: boolean;
    can_publish: boolean;
}>();

const selectedAcademicYearId = ref(
    props.selected_academic_year_id
        ?? (props.active_term?.academic_year_id ? String(props.active_term.academic_year_id) : ''),
);
const selectedTermId = ref(
    props.selected_term_id
        ?? (props.active_term?.id ? String(props.active_term.id) : ''),
);
const selectedClassId = ref(props.selected_class_id ?? '');
type PdfScope = 'full' | 'grades' | 'identity';
const reportMode = ref<'sas' | 'sts'>(props.report_mode ?? 'sas');
const showReadiness = ref(false);
const search = ref('');
const page = ref(1);
const selectedStudentIds = ref<number[]>([]);

const filtered = computed(() =>
    props.reports.filter((r) =>
        `${r.student_name} ${r.nis}`.toLocaleLowerCase('id-ID').includes(search.value.trim().toLocaleLowerCase('id-ID')),
    ),
);
const pages = computed(() => Math.max(1, Math.ceil(filtered.value.length / 15)));
const visible = computed(() => filtered.value.slice((page.value - 1) * 15, page.value * 15));

const selectableVisibleIds = computed(() =>
    visible.value.map((r) => r.student_id).filter((id): id is number => typeof id === 'number' && id > 0),
);
const allVisibleSelected = computed(() =>
    selectableVisibleIds.value.length > 0
    && selectableVisibleIds.value.every((id) => selectedStudentIds.value.includes(id)),
);

const toggleSelectAllVisible = () => {
    if (allVisibleSelected.value) {
        selectedStudentIds.value = selectedStudentIds.value.filter((id) => !selectableVisibleIds.value.includes(id));
    } else {
        const merged = new Set([...selectedStudentIds.value, ...selectableVisibleIds.value]);
        selectedStudentIds.value = Array.from(merged);
    }
};

const toggleStudentSelection = (studentId?: number) => {
    if (!studentId) return;
    if (selectedStudentIds.value.includes(studentId)) {
        selectedStudentIds.value = selectedStudentIds.value.filter((id) => id !== studentId);
    } else {
        selectedStudentIds.value = [...selectedStudentIds.value, studentId];
    }
};

watch([search, () => props.reports], () => {
    page.value = 1;
    selectedStudentIds.value = [];
});
watch(() => props.selected_academic_year_id, (value) => {
    if (value !== undefined) selectedAcademicYearId.value = value ?? '';
});
watch(() => props.selected_term_id, (value) => {
    if (value !== undefined) selectedTermId.value = value ?? '';
});
watch(() => props.selected_class_id, (value) => {
    selectedClassId.value = value ?? '';
});
watch(() => props.report_mode, (value) => {
    if (value) {
        reportMode.value = value;
    }
});

const reportsBasePath = () =>
    typeof window !== 'undefined' && window.location.pathname.startsWith('/kepsek/')
        ? '/kepsek/laporan/nilai'
        : '/penilaian/rapor';

const applyFilters = (overrides: Record<string, string | undefined> = {}) => {
    const query: Record<string, string | undefined> = {
        academic_year_id: selectedAcademicYearId.value || undefined,
        term_id: selectedTermId.value || undefined,
        class_group_id: props.is_student ? undefined : (selectedClassId.value || undefined),
        report_mode: reportMode.value,
        ...overrides,
    };
    router.get(reportsBasePath(), query, { preserveState: true, preserveScroll: true });
};

const onAcademicYearChange = () => {
    applyFilters({ term_id: undefined, class_group_id: undefined });
};

const onTermChange = () => {
    applyFilters();
};

const onClassChange = () => {
    applyFilters();
};

const onModeChange = () => {
    applyFilters();
};

const generateForm = useForm({
    class_group_id: selectedClassId.value,
    term_id: selectedTermId.value || (props.active_term?.id ?? ''),
    report_mode: reportMode.value,
});
const generateBatch = () => {
    const targetTermId = selectedTermId.value || props.active_term?.id;
    if (!targetTermId || !selectedClassId.value) return;
    generateForm.class_group_id = selectedClassId.value;
    generateForm.term_id = targetTermId;
    generateForm.report_mode = reportMode.value;
    generateForm.post('/penilaian/rapor/generate-batch', { preserveScroll: true });
};

const activeSupplementReport = ref<ReportItem | null>(null);
const supplementForm = useForm({
    report_mode: 'sas' as 'sas' | 'sts',
    extracurricular: [{ name: '', predicate: 'Baik', description: '' }] as ExtraItem[],
    homeroom_report_note: '',
    promotion_decision: '',
    report_place: '',
    report_date: '',
    grade_competencies: {} as Record<string, string>,
});

const activeReportGrades = (report: ReportItem): GradeItem[] => {
    const modeGrades = report.scheme_snapshot?.grades_by_mode?.[reportMode.value];
    if (Array.isArray(modeGrades) && modeGrades.length > 0) {
        return modeGrades;
    }
    return report.grades_snapshot ?? [];
};

const openSupplementModal = (report: ReportItem) => {
    activeSupplementReport.value = report;
    supplementForm.clearErrors();
    supplementForm.report_mode = reportMode.value;
    const extras = report.extracurricular_snapshot?.length
        ? report.extracurricular_snapshot.map((e) => ({
            name: e.name ?? '',
            predicate: e.predicate ?? 'Baik',
            description: e.description ?? '',
        }))
        : [{ name: '', predicate: 'Baik', description: '' }];
    supplementForm.extracurricular = extras;
    supplementForm.homeroom_report_note = report.notes_snapshot?.homeroom_report_note ?? '';
    supplementForm.promotion_decision = report.notes_snapshot?.promotion_decision ?? '';
    supplementForm.report_place = report.notes_snapshot?.report_place ?? '';
    supplementForm.report_date = report.notes_snapshot?.report_date ?? '';

    const competencies: Record<string, string> = {};
    for (const g of activeReportGrades(report)) {
        competencies[String(g.subject_id)] = g.competency_description ?? '';
    }
    supplementForm.grade_competencies = competencies;
};

const addExtraRow = () => {
    if (supplementForm.extracurricular.length >= 10) return;
    supplementForm.extracurricular.push({ name: '', predicate: 'Baik', description: '' });
};
const removeExtraRow = (index: number) => {
    supplementForm.extracurricular.splice(index, 1);
    if (!supplementForm.extracurricular.length) {
        supplementForm.extracurricular.push({ name: '', predicate: 'Baik', description: '' });
    }
};
const saveSupplements = () => {
    if (!activeSupplementReport.value) return;
    supplementForm.report_mode = reportMode.value;
    supplementForm.post(`/penilaian/rapor/${activeSupplementReport.value.id}/pelengkap`, {
        preserveScroll: true,
        onSuccess: () => { activeSupplementReport.value = null; },
    });
};

const dateLabel = (date: string | null) =>
    date ? new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Siap cetak';
const statusLabel = (value: ReportItem['status']) => ({ draft: 'Draf', reviewed: 'Ditinjau', published: 'Terbit' })[value];
const modeLabel = () => reportMode.value === 'sts' ? 'STS (Tengah Semester)' : 'SAS (Akhir Semester)';

const ledgerHref = computed(() => {
    const params = new URLSearchParams();
    if (selectedAcademicYearId.value) params.set('academic_year_id', selectedAcademicYearId.value);
    if (selectedTermId.value) params.set('term_id', selectedTermId.value);
    if (selectedClassId.value) params.set('class_group_id', selectedClassId.value);
    params.set('report_mode', reportMode.value);
    const qs = params.toString();
    return `/penilaian/ledger${qs ? `?${qs}` : ''}`;
});

const batchPdfUrl = computed(() => {
    const params = new URLSearchParams();
    params.set('class_group_id', selectedClassId.value);
    if (selectedTermId.value) params.set('term_id', selectedTermId.value);
    params.set('report_mode', reportMode.value);
    params.set('cover', reportMode.value === 'sas' ? '1' : '0');
    if (selectedStudentIds.value.length > 0) {
        params.set('student_ids', selectedStudentIds.value.join(','));
    }
    return `/penilaian/rapor/cetak-massal/pdf?${params.toString()}`;
});

const singlePdfUrl = (reportId: number, scope: PdfScope = 'full') => {
    const params = new URLSearchParams();
    params.set('report_mode', reportMode.value);
    params.set('cover', scope === 'full' && reportMode.value === 'sas' ? '1' : '0');
    if (scope === 'grades') params.set('grades_only', '1');
    if (scope === 'identity') params.set('identity_only', '1');
    return `/penilaian/rapor/${reportId}/pdf?${params.toString()}`;
};
</script>

<template>
    <Head :title="is_student ? 'Rapor Saya' : 'Rapor Siswa'" />
    <AppLayout :title="is_student ? 'Rapor Saya' : 'Rapor Siswa'">
        <div class="view reports-page">
            <!-- Navigasi Kontekstual Rapor & Ledger Nilai -->
            <nav v-if="!is_student" class="module-tabs" aria-label="Submenu Rapor dan Ledger">
                <Link :href="reportsBasePath()" class="module-tab is-active" aria-current="page">
                    Rapor Siswa
                </Link>
                <Link :href="ledgerHref" class="module-tab">
                    Ledger Nilai
                </Link>
                <Link href="/penilaian/skema" class="module-tab">
                    Skema Penilaian
                </Link>
            </nav>

            <header class="reports-heading">
                <div>
                    <h1>{{ is_student ? 'Rapor saya' : 'Rapor siswa' }}</h1>
                    <p>{{ is_student ? 'Pilih tahun pelajaran, semester, dan jenis rapor (STS/SAS) untuk melihat atau mencetak rapor.' : 'Pilih tahun pelajaran, semester, dan kelas untuk menyusun serta mencetak rapor siswa.' }}</p>
                </div>
                <div class="report-actions">
                    <AppButton
                        v-if="!is_student && selectedClassId && reports.length > 0"
                        variant="secondary"
                        icon="pdf"
                        as="a"
                        target="_blank"
                        rel="noopener noreferrer"
                        :href="batchPdfUrl"
                        class="pdf-button"
                    >
                        {{ selectedStudentIds.length > 0 ? `Cetak PDF Terpilih (${selectedStudentIds.length})` : 'Cetak PDF Kelas' }}
                    </AppButton>
                    <div v-if="can_publish" class="report-generate-group">
                        <AppButton
                            variant="primary"
                            type="button"
                            class="primary-button"
                            :disabled="generateForm.processing || !selectedClassId || !active_term"
                            @click="generateBatch"
                        >
                            {{ generateForm.processing ? 'Menyusun rapor…' : `Susun rapor (${reportMode.toUpperCase()})` }}
                        </AppButton>
                    </div>
                </div>
            </header>

            <div v-if="Object.keys(generateForm.errors).length" class="report-error" role="alert">
                <p v-for="(error, key) in generateForm.errors" :key="key">{{ error }}</p>
                <Link href="/penilaian/skema">Periksa skema penilaian</Link>
            </div>

            <div v-if="!is_student && readiness" class="ss-summary-grid ss-summary-grid--3" aria-label="Ringkasan kesiapan rapor">
                <MetricSummaryCard
                    label="Siswa di rombel"
                    :value="readiness.total_students"
                    tone="blue"
                    icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                />
                <MetricSummaryCard
                    :label="`Kelengkapan nilai (${reportMode.toUpperCase()})`"
                    :value="`${readiness.overall_completion_percent}%`"
                    :note="`${readiness.subjects.length} mata pelajaran`"
                    tone="violet"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    label="Rapor siap cetak"
                    :value="reports.length"
                    :note="modeLabel()"
                    tone="green"
                    icon="M5 13l4 4L19 7"
                />
            </div>

            <section v-if="!is_student && readiness && readiness.subjects.length" class="panel readiness-panel">
                <div class="readiness-header">
                    <div>
                        <h2>Kelengkapan nilai per mata pelajaran ({{ reportMode.toUpperCase() }})</h2>
                        <p>Pastikan nilai mata pelajaran untuk {{ reportMode === 'sts' ? 'tengah semester (STS)' : 'akhir semester (SAS)' }} sudah terisi lengkap.</p>
                    </div>
                    <AppButton variant="secondary" type="button" class="secondary-button" @click="showReadiness = !showReadiness">
                        {{ showReadiness ? 'Sembunyikan rincian' : `Lihat ${readiness.subjects.length} mapel` }}
                    </AppButton>
                </div>
                <div v-if="showReadiness" class="readiness-grid">
                    <div v-for="subj in readiness.subjects" :key="subj.teaching_assignment_id" class="readiness-item">
                        <div class="readiness-item-top">
                            <strong>{{ subj.subject_name }}</strong>
                            <span :class="{ 'is-done': subj.completion_percent === 100 }">{{ subj.scored_students }}/{{ subj.total_students }} ({{ subj.completion_percent }}%)</span>
                        </div>
                        <small>{{ subj.teacher_name }}</small>
                        <div class="readiness-bar"><div class="readiness-fill" :style="{ width: `${subj.completion_percent}%` }" /></div>
                    </div>
                </div>
            </section>

            <section v-if="is_student" class="student-report-card panel" aria-labelledby="student-report-title">
                <div class="register-context student-filter-context">
                    <label v-if="academic_years?.length" for="student-report-year">
                        Tahun pelajaran
                        <select id="student-report-year" v-model="selectedAcademicYearId" @change="onAcademicYearChange">
                            <option v-for="yr in academic_years" :key="yr.id" :value="String(yr.id)">{{ yr.name }}</option>
                        </select>
                    </label>
                    <label v-if="terms?.length" for="student-report-term">
                        Semester
                        <select id="student-report-term" v-model="selectedTermId" @change="onTermChange">
                            <option v-for="trm in terms" :key="trm.id" :value="String(trm.id)">{{ trm.name }}</option>
                        </select>
                    </label>
                    <label for="student-report-mode">
                        Jenis rapor
                        <select id="student-report-mode" v-model="reportMode" @change="onModeChange">
                            <option value="sas">Akhir Semester (SAS)</option>
                            <option value="sts">Tengah Semester (STS)</option>
                        </select>
                    </label>
                </div>

                <div class="student-report-intro">
                    <div class="report-document-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3.75h8.25L19 8.5v11.75H6a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3.75V9h5M8 13h8M8 16.5h6"/></svg>
                    </div>
                    <div>
                        <span class="report-eyebrow">DOKUMEN HASIL BELAJAR · {{ reportMode.toUpperCase() }}</span>
                        <h2 id="student-report-title">Rapor {{ active_term?.name ?? 'semester dipilih' }}</h2>
                        <p>{{ classes[0]?.name ?? 'Kelas belum tersedia' }} · {{ active_term?.academic_year?.name ?? '' }} · {{ active_term?.name ?? 'Belum ada semester aktif' }}</p>
                    </div>
                    <span v-if="reports[0]" class="report-status" :class="reports[0].status">{{ statusLabel(reports[0].status) }}</span>
                </div>

                <div v-if="reports[0]" class="student-report-details">
                    <div><span>Peserta didik</span><strong>{{ reports[0].student_name }}</strong><small>NIS {{ reports[0].nis }}</small></div>
                    <div><span>Mode &amp; Tanggal</span><strong>{{ modeLabel() }} · {{ dateLabel(reports[0].published_at) }}</strong><small v-if="reports[0].scheme_snapshot?.version">Skema penilaian v{{ reports[0].scheme_snapshot.version }}</small></div>
                </div>

                <div v-if="reports[0]" class="student-report-actions">
                    <Link :href="reportMode === 'sts' ? `/penilaian/rapor/${reports[0].id}?report_mode=sts` : `/penilaian/rapor/${reports[0].id}`" class="pdf-chip-button is-preview">Pratinjau Rapor</Link>
                    <a :href="singlePdfUrl(reports[0].id, 'identity')" target="_blank" rel="noopener noreferrer" class="pdf-chip-button is-identity" title="Cetak PDF Identitas Siswa">Identitas</a>
                    <a :href="singlePdfUrl(reports[0].id, 'grades')" target="_blank" rel="noopener noreferrer" class="pdf-chip-button is-grades" title="Cetak PDF Nilai Siswa">Nilai</a>
                    <a :href="singlePdfUrl(reports[0].id, 'full')" target="_blank" rel="noopener noreferrer" class="pdf-chip-button is-report" title="Pratinjau & Cetak PDF Rapor Siswa">Rapor Siswa</a>
                </div>
                <div v-else class="student-report-empty">
                    <strong>Rapor belum tersedia</strong>
                    <p>Rapor akan muncul di sini setelah disusun oleh sekolah untuk tahun pelajaran dan semester yang dipilih.</p>
                </div>
                <p class="student-report-help">Anda dapat memilih Tahun Pelajaran, Semester, serta mode STS atau SAS untuk melihat dan mencetak rapor semester terkait.</p>
            </section>

            <section v-else class="report-register panel">
                <div class="register-context">
                    <label v-if="academic_years?.length" for="report-academic-year">
                        Tahun pelajaran
                        <select id="report-academic-year" v-model="selectedAcademicYearId" @change="onAcademicYearChange">
                            <option v-for="yr in academic_years" :key="yr.id" :value="String(yr.id)">{{ yr.name }}</option>
                        </select>
                    </label>
                    <label v-if="terms?.length" for="report-term">
                        Semester
                        <select id="report-term" v-model="selectedTermId" @change="onTermChange">
                            <option v-for="trm in terms" :key="trm.id" :value="String(trm.id)">{{ trm.name }}</option>
                        </select>
                    </label>
                    <label for="report-class">
                        Kelas / rombel
                        <select id="report-class" v-model="selectedClassId" @change="onClassChange">
                            <option value="" disabled>Pilih kelas</option>
                            <option v-for="cls in classes" :key="cls.id" :value="String(cls.id)">{{ cls.name }}</option>
                        </select>
                    </label>
                    <label for="report-mode">
                        Jenis rapor
                        <select id="report-mode" v-model="reportMode" @change="onModeChange">
                            <option value="sas">Akhir Semester (SAS)</option>
                            <option value="sts">Tengah Semester (STS)</option>
                        </select>
                    </label>
                </div>
                <div class="register-toolbar">
                    <div>
                        <h2>Daftar rapor kelas ({{ reportMode.toUpperCase() }})</h2>
                        <p>Centang siswa tertentu atau cetak langsung per siswa sesuai Tahun Pelajaran &amp; Semester yang dipilih.</p>
                    </div>
                    <div class="register-filters">
                        <span v-if="selectedStudentIds.length > 0" class="selection-pill">
                            {{ selectedStudentIds.length }} siswa dipilih
                            <button type="button" class="clear-selection-btn" @click="selectedStudentIds = []">Reset</button>
                        </span>
                        <input v-model="search" class="search-control" type="search" aria-label="Cari siswa" placeholder="Cari nama atau NIS…">
                    </div>
                </div>
                <div class="register-scroll table-scroll" tabindex="0" role="region" aria-label="Daftar laporan">
                    <table class="app-table report-table">
                        <thead>
                            <tr>
                                <th scope="col" class="checkbox-col">
                                    <input
                                        type="checkbox"
                                        :checked="allVisibleSelected"
                                        :disabled="!selectableVisibleIds.length"
                                        aria-label="Pilih semua siswa di halaman ini"
                                        @change="toggleSelectAllVisible"
                                    >
                                </th>
                                <th scope="col">Peserta didik</th>
                                <th scope="col">Jenis Rapor</th>
                                <th scope="col" class="action-column">Aksi &amp; Cetak</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="report in visible" :key="report.id">
                                <td class="checkbox-col">
                                    <input
                                        type="checkbox"
                                        :checked="report.student_id ? selectedStudentIds.includes(report.student_id) : false"
                                        :aria-label="`Pilih ${report.student_name}`"
                                        @change="toggleStudentSelection(report.student_id)"
                                    >
                                </td>
                                <td>
                                    <strong>{{ report.student_name }}</strong>
                                    <small>NIS {{ report.nis }} <span v-if="report.scheme_snapshot?.version">· Skema v{{ report.scheme_snapshot.version }}</span></small>
                                </td>
                                <td>
                                    <span class="report-mode-badge" :class="{ 'is-sts': reportMode === 'sts' }">
                                        {{ reportMode === 'sts' ? 'STS · Tengah Semester' : 'SAS · Akhir Semester' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="report-actions">
                                        <AppButton
                                            v-if="can_publish"
                                            variant="secondary"
                                            size="sm"
                                            type="button"
                                            class="secondary-button"
                                            @click="openSupplementModal(report)"
                                        >
                                            Lengkapi
                                        </AppButton>
                                        <a
                                            :href="singlePdfUrl(report.id, 'identity')"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="pdf-chip-button is-identity"
                                            title="Cetak PDF Identitas Siswa"
                                        >
                                            Identitas
                                        </a>
                                        <a
                                            :href="singlePdfUrl(report.id, 'grades')"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="pdf-chip-button is-grades"
                                            title="Cetak PDF Nilai Siswa"
                                        >
                                            Nilai
                                        </a>
                                        <a
                                            :href="singlePdfUrl(report.id, 'full')"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="pdf-chip-button is-report"
                                            title="Pratinjau & Cetak PDF Rapor Siswa"
                                        >
                                            Rapor Siswa
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!visible.length">
                                <td colspan="4" class="register-empty">
                                    <strong>{{ search ? 'Tidak ada rapor yang cocok' : 'Belum ada rapor pada semester ini' }}</strong>
                                    <p>{{ !active_term ? 'Pilih Tahun Pelajaran dan Semester terlebih dahulu.' : search ? 'Ubah pencarian untuk melihat hasil lainnya.' : 'Pilih kelas dan jenis rapor, lalu klik Susun rapor.' }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <footer class="register-footer">
                    <span>{{ filtered.length ? (page - 1) * 15 + 1 : 0 }}–{{ Math.min(page * 15, filtered.length) }} dari {{ filtered.length }} rapor</span>
                    <nav v-if="pages > 1" aria-label="Halaman rapor">
                        <AppButton variant="secondary" type="button" class="secondary-button" :disabled="page <= 1" @click="page--">Sebelumnya</AppButton>
                        <span>{{ page }} / {{ pages }}</span>
                        <AppButton variant="secondary" type="button" class="secondary-button" :disabled="page >= pages" @click="page++">Berikutnya</AppButton>
                    </nav>
                </footer>
            </section>
        </div>

        <!-- Modal Lengkapi Data Pelengkap Rapor -->
        <Transition name="ss-overlay" appear>
            <div
                v-if="activeSupplementReport"
                class="modal-backdrop"
                role="dialog"
                aria-modal="true"
                aria-labelledby="supplement-modal-title"
                @click.self="activeSupplementReport = null"
                @keydown.esc="activeSupplementReport = null"
            >
                <Transition name="ss-modal" appear>
                    <form class="modal-card supplement-modal" @submit.prevent="saveSupplements">
                        <div class="supplement-header">
                            <div>
                                <span class="report-eyebrow">PELENGKAP E-RAPOR · {{ reportMode.toUpperCase() }}</span>
                                <h2 id="supplement-modal-title">{{ activeSupplementReport.student_name }}</h2>
                                <p>NIS {{ activeSupplementReport.nis }} · {{ modeLabel() }}</p>
                            </div>
                            <AppButton variant="ghost" icon-only icon="close" type="button" aria-label="Tutup" @click="activeSupplementReport = null" />
                        </div>

                        <div class="supplement-section">
                            <div class="supplement-section-head">
                                <h3>Ekstrakurikuler</h3>
                                <AppButton variant="secondary" type="button" class="secondary-button" @click="addExtraRow">+ Tambah kegiatan</AppButton>
                            </div>
                            <div v-for="(extra, idx) in supplementForm.extracurricular" :key="idx" class="extra-row">
                                <input v-model="extra.name" type="text" placeholder="Nama kegiatan (mis. Pramuka)" />
                                <select v-model="extra.predicate">
                                    <option value="Sangat Baik">Sangat Baik</option>
                                    <option value="Baik">Baik</option>
                                    <option value="Cukup">Cukup</option>
                                    <option value="Kurang">Kurang</option>
                                </select>
                                <input v-model="extra.description" type="text" placeholder="Keterangan singkat" />
                                <AppButton variant="danger" icon-only icon="delete" type="button" aria-label="Hapus baris" @click="removeExtraRow(idx)" />
                            </div>
                        </div>

                        <div class="supplement-grid">
                            <label>
                                <span>Tempat pembagian rapor</span>
                                <input v-model="supplementForm.report_place" type="text" placeholder="Yogyakarta" />
                            </label>
                            <label>
                                <span>Tanggal pembagian rapor</span>
                                <input v-model="supplementForm.report_date" type="text" placeholder="20 Desember 2026" />
                            </label>
                        </div>

                        <label class="supplement-field">
                            <span>Catatan wali kelas</span>
                            <textarea v-model="supplementForm.homeroom_report_note" rows="3" placeholder="Tuliskan catatan perkembangan & motivasi peserta didik..." />
                        </label>

                        <label v-if="reportMode === 'sas'" class="supplement-field">
                            <span>Keputusan kenaikan kelas / kelulusan (opsional, khusus akhir semester genap)</span>
                            <input v-model="supplementForm.promotion_decision" type="text" placeholder="Naik ke kelas VIII / Lulus" />
                        </label>

                        <div v-if="activeReportGrades(activeSupplementReport).length" class="supplement-section">
                            <h3>Capaian kompetensi mata pelajaran ({{ reportMode.toUpperCase() }})</h3>
                            <div v-for="grade in activeReportGrades(activeSupplementReport)" :key="grade.subject_id" class="competency-edit-row">
                                <span>{{ grade.subject_name }} <strong v-if="grade.final_score !== null">({{ grade.final_score }})</strong></span>
                                <textarea v-model="supplementForm.grade_competencies[String(grade.subject_id)]" rows="2" placeholder="Deskripsi capaian kompetensi..." />
                            </div>
                        </div>

                        <div class="supplement-footer">
                            <AppButton variant="secondary" type="button" class="secondary-button" :disabled="supplementForm.processing" @click="activeSupplementReport = null">Batal</AppButton>
                            <AppButton variant="primary" type="submit" class="primary-button" :loading="supplementForm.processing">Simpan pelengkap</AppButton>
                        </div>
                    </form>
                </Transition>
            </div>
        </Transition>
    </AppLayout>
</template>

<style scoped>
.reports-page{display:grid;gap:1.15rem;max-width:1440px;margin-inline:auto}
.module-tabs{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem;border:1px solid var(--line);border-radius:.75rem;background:var(--surface);width:fit-content;flex-wrap:wrap}
.module-tab{display:inline-flex;align-items:center;padding:.45rem .9rem;border-radius:.55rem;font-size:var(--type-sm);font-weight:var(--weight-semibold);color:var(--ink-soft);text-decoration:none;transition:background 150ms ease,color 150ms ease}
.module-tab:hover{color:var(--ink);background:var(--surface-soft,var(--surface))}
.module-tab.is-active{background:var(--primary-soft);color:var(--primary-strong)}
.reports-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:0 0 .85rem;border-bottom:1px solid var(--line);flex-wrap:wrap}
.reports-heading h1{font-size:var(--type-3xl);font-weight:var(--weight-bold);color:var(--ink);letter-spacing:-.035em}
.reports-heading p,.register-toolbar p,.student-report-help{color:var(--ink-soft);font-size:var(--type-sm);margin-top:.35rem}
.report-actions{display:flex;align-items:center;justify-content:flex-end;gap:.35rem;flex-wrap:nowrap}
.pdf-chip-button{display:inline-flex;align-items:center;justify-content:center;height:30px;padding:0 .65rem;border-radius:.5rem;border:1px solid var(--line-strong);font-size:var(--type-meta);font-weight:var(--weight-semibold);text-decoration:none;white-space:nowrap;transition:background 150ms ease,border-color 150ms ease,color 150ms ease}
.pdf-chip-button.is-preview{background:var(--primary-soft);border-color:color-mix(in srgb,var(--primary) 30%,var(--line));color:var(--primary-strong)}
.pdf-chip-button.is-preview:hover{background:color-mix(in srgb,var(--primary) 16%,var(--surface));border-color:var(--primary)}
.pdf-chip-button.is-identity{background:var(--blue-soft,#eff6ff);border-color:color-mix(in srgb,var(--blue,#2563eb) 30%,var(--line));color:var(--blue,#1d4ed8)}
.pdf-chip-button.is-identity:hover{background:color-mix(in srgb,var(--blue,#2563eb) 14%,var(--surface));border-color:var(--blue,#2563eb)}
.pdf-chip-button.is-grades{background:var(--gold-soft,#fffbeb);border-color:color-mix(in srgb,var(--gold,#d97706) 32%,var(--line));color:var(--gold,#b45309)}
.pdf-chip-button.is-grades:hover{background:color-mix(in srgb,var(--gold,#d97706) 15%,var(--surface));border-color:var(--gold,#d97706)}
.pdf-chip-button.is-report{background:var(--red-soft);border-color:color-mix(in srgb,var(--red) 35%,var(--line));color:var(--red)}
.pdf-chip-button.is-report:hover{background:color-mix(in srgb,var(--red) 15%,var(--surface));border-color:var(--red)}
.report-generate-group{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
.readiness-panel{padding:1.15rem 1.35rem;display:grid;gap:1rem}
.readiness-header{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.readiness-header h2{margin:0;font-size:var(--type-md);font-weight:var(--weight-bold);color:var(--ink)}
.readiness-header p{margin:.2rem 0 0;font-size:var(--type-meta);color:var(--ink-soft)}
.readiness-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:.75rem}
.readiness-item{display:grid;gap:.25rem;padding:.75rem .9rem;border:1px solid var(--line);border-radius:.75rem;background:var(--surface-subtle)}
.readiness-item-top{display:flex;justify-content:space-between;gap:.5rem;font-size:var(--type-sm);color:var(--ink)}
.readiness-item-top span{color:var(--gold);font-weight:var(--weight-bold);font-size:var(--type-meta)}
.readiness-item-top span.is-done{color:var(--green)}
.readiness-item small{color:var(--ink-soft);font-size:var(--type-meta)}
.readiness-bar{height:6px;border-radius:999px;background:var(--line);overflow:hidden;margin-top:.2rem}
.readiness-fill{height:100%;background:var(--primary);border-radius:999px}
.report-register,.student-report-card{overflow:hidden}
.student-report-card{padding:0}
.student-filter-context{grid-template-columns:repeat(3,minmax(0,1fr)) !important}
.student-report-intro{display:flex;align-items:center;gap:1rem;padding:1.5rem;background:linear-gradient(135deg,var(--surface-soft,var(--surface)),var(--surface));border-bottom:1px solid var(--line)}
.report-document-icon{display:grid;place-items:center;width:3rem;height:3rem;flex:0 0 3rem;border-radius:.85rem;color:#fff;background:var(--primary);box-shadow:0 8px 18px color-mix(in srgb,var(--primary) 22%,transparent)}
.report-document-icon svg{width:1.45rem;height:1.45rem}
.report-eyebrow{display:block;color:var(--primary);font-size:var(--type-meta);font-weight:var(--weight-bold);letter-spacing:.09em}
.student-report-intro h2{margin-top:.2rem;color:var(--ink);font-size:var(--type-xl);font-weight:var(--weight-bold)}
.student-report-intro p{margin-top:.2rem;color:var(--ink-soft);font-size:var(--type-sm)}
.student-report-intro .report-status{margin-left:auto}
.student-report-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;padding:1.4rem 1.5rem}
.student-report-details>div{display:grid;gap:.28rem;padding:.9rem 1rem;border:1px solid var(--line);border-radius:.75rem;background:var(--surface)}
.student-report-details span{color:var(--ink-soft);font-size:var(--type-meta)}
.student-report-details strong{color:var(--ink);font-size:var(--type-md)}
.student-report-details small{color:var(--ink-soft);font-size:var(--type-meta)}
.student-report-actions{display:flex;gap:.65rem;flex-wrap:wrap;padding:0 1.5rem 1.25rem}
.student-report-help{padding:0 1.5rem 1.3rem;margin:0}
.student-report-empty{padding:1.5rem;color:var(--ink);border-top:1px solid var(--line)}
.student-report-empty p{margin-top:.35rem;color:var(--ink-soft);font-size:var(--type-sm)}
.register-context{display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:1rem;align-items:end;padding:1.25rem 1.5rem;background:var(--surface-soft,var(--surface));border-bottom:1px solid var(--line)}
.register-context label,.register-context>div{display:grid;gap:.35rem;font-size:var(--type-meta);color:var(--ink-soft)}
.register-context strong{font-size:var(--type-md);color:var(--ink)}
.register-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:1.15rem 1.5rem;flex-wrap:wrap}
.register-toolbar h2{font-weight:var(--weight-bold);color:var(--ink)}
.register-filters{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}
.selection-pill{display:inline-flex;align-items:center;gap:.45rem;padding:.35rem .7rem;border-radius:999px;background:var(--primary-soft);color:var(--primary-strong);font-size:var(--type-meta);font-weight:var(--weight-semibold)}
.clear-selection-btn{border:0;background:transparent;color:inherit;text-decoration:underline;cursor:pointer;font-size:var(--type-meta);padding:0}
.register-filters input,.register-context select{min-height:42px;padding:.55rem .7rem;border:1px solid var(--line-strong);border-radius:.6rem;background:var(--surface);color:var(--ink);font-size:var(--type-control)}
.register-context select{width:100%}
.register-scroll{overflow-x:auto}
.report-table{width:100%;min-width:680px;border-collapse:collapse;text-align:left;font-size:var(--type-sm);color:var(--ink)}
.report-table th{padding:.8rem 1.25rem;background:var(--surface-soft,var(--surface));color:var(--ink-soft);font-size:var(--type-meta);font-weight:var(--weight-semibold);border-block:1px solid var(--line)}
.report-table td{padding:1rem 1.25rem;border-bottom:1px solid var(--line)}
.checkbox-col{width:44px;text-align:center}
.checkbox-col input{width:16px;height:16px;min-height:0;cursor:pointer}
.report-table td>strong,.report-table small{display:block}
.report-table small{margin-top:.3rem;color:var(--ink-soft);font-size:var(--type-meta)}
.action-column{text-align:right}
.report-mode-badge{display:inline-flex;padding:.25rem .6rem;border-radius:999px;font-size:var(--type-meta);font-weight:var(--weight-bold);background:var(--primary-soft);color:var(--primary)}
.report-mode-badge.is-sts{background:var(--gold-soft);color:var(--gold)}
.report-status{display:inline-flex;padding:.25rem .6rem;border-radius:2rem;font-size:var(--type-meta);font-weight:var(--weight-semibold);background:var(--surface);border:1px solid var(--line);color:var(--ink-soft)}
.report-status.reviewed{color:var(--blue);background:var(--blue-soft);border-color:color-mix(in srgb,var(--blue) 25%,var(--line))}
.report-status.published{color:var(--green);background:var(--green-soft);border-color:color-mix(in srgb,var(--green) 25%,var(--line))}
.report-status.draft{color:var(--gold);background:var(--gold-soft);border-color:color-mix(in srgb,var(--gold) 25%,var(--line))}
.register-empty{text-align:center;padding:3rem!important}
.register-empty p{margin-top:.5rem;color:var(--ink-soft)}
.register-footer{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:1rem 1.5rem;color:var(--ink-soft);font-size:var(--type-meta)}
.register-footer nav{display:flex;align-items:center;gap:.75rem}
.report-error{padding:1rem;color:var(--red);background:var(--red-soft);border:1px solid var(--delete-line);border-radius:.75rem}
.report-error a{text-decoration:underline}
.modal-backdrop{position:fixed;inset:0;z-index:80;display:grid;place-items:center;padding:1rem;background:rgb(19 32 51 / 45%);backdrop-filter:blur(3px)}
.supplement-modal{width:min(720px,100%);max-height:min(90dvh,880px);overflow-y:auto;display:grid;gap:1rem;padding:clamp(18px,3vw,28px);background:var(--surface);color:var(--ink);border:1px solid var(--line);border-radius:16px;box-shadow:0 24px 80px rgb(15 23 42 / 25%)}
.supplement-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding-bottom:.75rem;border-bottom:1px solid var(--line)}
.supplement-header h2{margin:.2rem 0 0;font-size:1.15rem;font-weight:var(--weight-bold)}
.supplement-header p{margin:.2rem 0 0;color:var(--ink-soft);font-size:var(--type-meta)}
.supplement-section{display:grid;gap:.6rem}
.supplement-section h3{margin:0;font-size:var(--type-sm);font-weight:var(--weight-bold)}
.supplement-section-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem}
.extra-row{display:grid;grid-template-columns:1.2fr .9fr 1.2fr auto;gap:.5rem;align-items:center}
.supplement-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.supplement-grid label,.supplement-field,.competency-edit-row{display:grid;gap:.35rem;font-size:var(--type-meta);font-weight:var(--weight-semibold);color:var(--ink-soft)}
.supplement-modal input,.supplement-modal select,.supplement-modal textarea{width:100%;padding:.55rem .7rem;border:1px solid var(--line-strong);border-radius:.6rem;background:var(--surface);color:var(--ink);font-size:var(--type-sm);font-weight:var(--weight-regular)}
.supplement-footer{display:flex;justify-content:flex-end;gap:.6rem;padding-top:.75rem;border-top:1px solid var(--line)}
@media(max-width:960px){.register-context,.student-filter-context{grid-template-columns:1fr 1fr !important}}
@media(max-width:720px){.reports-heading{align-items:stretch;flex-direction:column}.report-actions,.report-generate-group{justify-content:flex-start;width:100%}.register-context,.student-filter-context{grid-template-columns:1fr !important}.student-report-details,.extra-row,.supplement-grid{grid-template-columns:1fr}.register-footer{align-items:flex-start;flex-direction:column}}
</style>
