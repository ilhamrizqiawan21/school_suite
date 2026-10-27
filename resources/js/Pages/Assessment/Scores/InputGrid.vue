<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface ClassGroup {
    id: number;
    name: string;
    grade_level: number;
}

interface ComponentItem {
    id: number;
    name: string;
    code: string;
    weight_percentage: number;
    passing_grade: number;
}

interface SchemeItem {
    id: number;
    name: string;
    code: string;
    passing_grade_default: number;
    components: ComponentItem[];
}

interface TermItem {
    id: number;
    name: string;
    semester: number;
}

interface ScoreCell {
    raw_score: number | string | null;
    remedial_score: number | string | null;
    final_score: number | null;
    notes: string | null;
}

interface StudentScoreRow {
    enrollment_id: number;
    student_id: number;
    roll_number?: number | null;
    nis: string;
    name: string;
    gender: 'L' | 'P';
    raw_score: number | string | null;
    remedial_score: number | string | null;
    final_score?: number | null;
    notes?: string | null;
    scores?: Record<string, ScoreCell>;
}

interface CourseCard {
    id: number;
    class_group_id: number;
    class_name: string;
    subject_name: string;
    subject_code?: string | null;
}

const props = defineProps<{
    active_term?: TermItem | null;
    classes: ClassGroup[];
    courses?: CourseCard[];
    scheme?: SchemeItem | null;
    components: ComponentItem[];
    selected_class_id?: string;
    selected_assignment_id?: string;
    selected_component_id?: string;
    students: StudentScoreRow[];
}>();

const selectedClassId = ref(props.selected_class_id || (props.classes[0]?.id ? String(props.classes[0].id) : ''));
const selectedCourseId = ref(props.selected_assignment_id || '');
const selectedComponentId = ref(props.selected_component_id || (props.components[0]?.id ? String(props.components[0].id) : ''));
const studentSearch = ref('');

const courses = computed(() => props.courses ?? []);
const selectedCourse = computed(() => courses.value.find((course) => String(course.id) === selectedCourseId.value) ?? null);
const selectedClass = computed(() => props.classes.find((item) => String(item.id) === selectedClassId.value) ?? null);

const currentComponent = computed(() => {
    return props.components.find((c) => String(c.id) === selectedComponentId.value) || props.components[0] || null;
});

const passingGrade = computed(() => {
    return currentComponent.value ? Number(currentComponent.value.passing_grade) : 75;
});

const makeScoreCell = (value?: Partial<ScoreCell>): ScoreCell => ({
    raw_score: value?.raw_score ?? null,
    remedial_score: value?.remedial_score ?? null,
    final_score: value?.final_score ?? null,
    notes: value?.notes ?? null,
});

const hydrateRows = (rows: StudentScoreRow[]) => JSON.parse(JSON.stringify(rows)).map((row: StudentScoreRow) => {
    row.scores = row.scores ?? {};
    if (selectedComponentId.value && !row.scores[selectedComponentId.value]) {
        row.scores[selectedComponentId.value] = makeScoreCell(row);
    }
    return row;
});

// Salin state lokal untuk baris penilaian
const localRows = ref<StudentScoreRow[]>(hydrateRows(props.students));

const scoreCell = (row: StudentScoreRow, componentId: number | string): ScoreCell => {
    row.scores = row.scores ?? {};
    const key = String(componentId);
    if (!row.scores[key]) row.scores[key] = makeScoreCell();
    return row.scores[key];
};

const componentFinal = (row: StudentScoreRow, component: ComponentItem): number | null => {
    const cell = scoreCell(row, component.id);
    const raw = cell.raw_score !== null && cell.raw_score !== '' ? Number(cell.raw_score) : null;
    const remedial = cell.remedial_score !== null && cell.remedial_score !== '' ? Number(cell.remedial_score) : null;
    if (raw === null || Number.isNaN(raw)) return null;
    if (remedial === null || Number.isNaN(remedial) || raw >= Number(component.passing_grade)) return raw;
    return Math.max(raw, Math.min(remedial, Number(component.passing_grade)));
};

const calculateAverage = (row: StudentScoreRow): number | null => {
    const scored = props.components
        .map((component) => ({ component, final: componentFinal(row, component) }))
        .filter((item): item is { component: ComponentItem; final: number } => item.final !== null);
    if (!scored.length) return null;
    const totalWeight = scored.reduce((sum, item) => sum + Number(item.component.weight_percentage || 0), 0);
    const weighted = scored.reduce((sum, item) => sum + item.final * Number(item.component.weight_percentage || 0), 0);
    return Number((totalWeight > 0 ? weighted / totalWeight : scored.reduce((sum, item) => sum + item.final, 0) / scored.length).toFixed(2));
};

const gradedCount = computed(() => localRows.value.filter((row) => {
    const cell = scoreCell(row, selectedComponentId.value || currentComponent.value?.id || '');
    return cell.raw_score !== null && cell.raw_score !== '';
}).length);
const belowPassingCount = computed(() => localRows.value.filter((row) => {
    const finalScore = calculateRowFinal(row);
    return finalScore !== null && finalScore < passingGrade.value;
}).length);
const filteredRows = computed(() => {
    const query = studentSearch.value.trim().toLowerCase();
    if (!query) return localRows.value;

    return localRows.value.filter((row) => `${row.name} ${row.nis}`.toLowerCase().includes(query));
});

watch(
    () => props.students,
    (newVal) => {
        localRows.value = hydrateRows(newVal);
    },
    { deep: true }
);

watch(() => props.selected_assignment_id, (value) => {
    selectedCourseId.value = value || '';
});

watch(() => props.selected_class_id, (value) => {
    selectedClassId.value = value || '';
});

watch(() => props.selected_component_id, (value) => {
    selectedComponentId.value = value || (props.components[0]?.id ? String(props.components[0].id) : '');
});

const onFilterChange = () => {
    router.get(
        '/penilaian/skor',
        {
            class_group_id: selectedClassId.value || undefined,
            teaching_assignment_id: selectedCourseId.value || undefined,
            component_id: selectedComponentId.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const selectCourse = (course: CourseCard) => {
    selectedCourseId.value = String(course.id);
    selectedClassId.value = String(course.class_group_id);
    selectedComponentId.value = '';
    onFilterChange();
};

// Hitung preview nilai akhir per baris
const calculateRowFinal = (row: StudentScoreRow): number | null => {
    const cell = scoreCell(row, selectedComponentId.value || currentComponent.value?.id || '');
    const raw = cell.raw_score !== null && cell.raw_score !== '' ? Number(cell.raw_score) : null;
    const rem = cell.remedial_score !== null && cell.remedial_score !== '' ? Number(cell.remedial_score) : null;
    const kktp = passingGrade.value;

    if (raw === null) {
        return null;
    }

    if (rem === null) {
        return raw;
    }

    if (raw >= kktp) {
        return raw;
    }

    const effectiveRem = Math.min(rem, kktp);
    return Math.max(raw, effectiveRem);
};

const form = useForm({
    component_id: selectedComponentId.value,
    scores: [] as {
        enrollment_id: number;
        raw_score: number | null;
        remedial_score: number | null;
        notes: string | null;
    }[],
    score_batches: [] as {
        component_id: number;
        scores: {
            enrollment_id: number;
            raw_score: number | null;
            remedial_score: number | null;
            notes: string | null;
        }[];
    }[],
});

const isSaving = ref(false);

const submitScores = () => {
    isSaving.value = true;
    form.component_id = selectedComponentId.value;
    form.scores = localRows.value.map((row) => {
        const cell = scoreCell(row, selectedComponentId.value || currentComponent.value?.id || '');
        return {
            enrollment_id: row.enrollment_id,
            raw_score: cell.raw_score !== null && cell.raw_score !== '' ? Number(cell.raw_score) : null,
            remedial_score: cell.remedial_score !== null && cell.remedial_score !== '' ? Number(cell.remedial_score) : null,
            notes: cell.notes || null,
        };
    });
    form.score_batches = props.components.map((component) => ({
        component_id: component.id,
        scores: localRows.value.map((row) => {
            const cell = scoreCell(row, component.id);
            return {
                enrollment_id: row.enrollment_id,
                raw_score: cell.raw_score !== null && cell.raw_score !== '' ? Number(cell.raw_score) : null,
                remedial_score: cell.remedial_score !== null && cell.remedial_score !== '' ? Number(cell.remedial_score) : null,
                notes: cell.notes || null,
            };
        }),
    }));

    form.post('/penilaian/skor', {
        preserveScroll: true,
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

const csvEscape = (value: unknown) => `"${String(value ?? '').replace(/"/g, '""')}"`;

const exportExcel = () => {
    const headers = ['No', 'NIS', 'Nama siswa', ...props.components.map((component) => component.code || component.name), 'Rata-rata akhir'];
    const lines = [headers.map(csvEscape).join(',')];
    filteredRows.value.forEach((row, index) => {
        lines.push([
            row.roll_number || index + 1,
            row.nis,
            row.name,
            ...props.components.map((component) => componentFinal(row, component) ?? ''),
            calculateAverage(row) ?? '',
        ].map(csvEscape).join(','));
    });
    const blob = new Blob([`\ufeff${lines.join('\n')}`], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `nilai-${selectedCourse.value?.class_name ?? 'kelas'}-${selectedCourse.value?.subject_name ?? 'mapel'}.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
};

const printScores = () => window.print();
</script>

<template>
    <AppLayout>
        <Head title="Input Nilai" />

        <div class="view space-y-6 score-page">
            <div class="page-heading score-hero">
                <div>
                    <p class="eyebrow">Ruang penilaian <span v-if="active_term">· {{ active_term.name }}</span></p>
                    <h1>Input Nilai</h1>
                    <p>Kelola nilai siswa berdasarkan kelas, mata pelajaran, dan komponen penilaian yang aktif.</p>
                </div>
                <div class="score-hero-actions">
                    <a href="/dashboard" class="secondary-button score-back">← Kembali</a>
                    <button type="button" class="secondary-button" @click="exportExcel">▣ Excel</button>
                    <button type="button" class="secondary-button" @click="printScores">▤ PDF</button>
                </div>
            </div>

            <div class="score-hero-context">
                <span>{{ selectedCourse?.subject_name ?? 'Mata pelajaran' }}</span>
                <strong>{{ selectedCourse?.class_name ?? selectedClass?.name ?? 'Pilih rombel' }}</strong>
                <small>{{ currentComponent?.name ?? 'Belum ada komponen dipilih' }} · {{ localRows.length }} siswa</small>
            </div>

            <nav class="workspace-tabs" aria-label="Navigasi ruang kelas dan mata pelajaran">
                <a href="/dashboard" class="workspace-tab"><span aria-hidden="true">⌂</span>Ringkasan</a>
                <a href="/pembelajaran" class="workspace-tab"><span aria-hidden="true">▤</span>Materi</a>
                <a href="/pembelajaran" class="workspace-tab"><span aria-hidden="true">✓</span>Tugas</a>
                <a href="#" class="workspace-tab is-active" aria-current="page"><span aria-hidden="true">▥</span>Nilai</a>
                <a href="/akademik/presensi" class="workspace-tab"><span aria-hidden="true">◷</span>Absensi</a>
                <a href="/komunikasi/chat" class="workspace-tab"><span aria-hidden="true">⋯</span>Chat</a>
            </nav>

            <section v-if="courses.length" class="course-dashboard" aria-labelledby="course-dashboard-title">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Penugasan mengajar</p>
                        <h2 id="course-dashboard-title">Pilih kelas dan mata pelajaran</h2>
                    </div>
                    <span class="section-count">{{ courses.length }} kelas mapel</span>
                </div>
                <div class="course-card-grid">
                    <button
                        v-for="course in courses"
                        :key="course.id"
                        type="button"
                        class="course-card"
                        :class="{ 'is-selected': String(course.id) === selectedCourseId }"
                        @click="selectCourse(course)"
                    >
                        <span class="course-card-icon" aria-hidden="true">{{ course.subject_name.slice(0, 1).toUpperCase() }}</span>
                        <span class="course-card-body">
                            <strong>{{ course.subject_name }}</strong>
                            <small>{{ course.class_name }}<template v-if="course.subject_code"> · {{ course.subject_code }}</template></small>
                        </span>
                        <span class="course-card-mark" aria-hidden="true">{{ String(course.id) === selectedCourseId ? '✓' : '›' }}</span>
                    </button>
                </div>
            </section>

            <div class="grade-summary score-summary">
                <div class="score-summary-card score-summary-blue"><span>Jumlah siswa</span><strong>{{ localRows.length }}</strong><small>pada rombel terpilih</small></div>
                <div class="score-summary-card score-summary-green"><span>Sudah dinilai</span><strong>{{ gradedCount }}/{{ localRows.length }}</strong><small>{{ localRows.length - gradedCount }} siswa belum dinilai</small></div>
                <div class="score-summary-card score-summary-red"><span>Di bawah KKTP</span><strong>{{ belowPassingCount }}</strong><small>berdasarkan nilai akhir saat ini</small></div>
                <div class="score-summary-card score-summary-gold"><span>Aturan remedial</span><strong class="rule-value">Maks. {{ passingGrade }}</strong><small>nilai remedial dibatasi sampai KKTP</small></div>
            </div>

            <!-- Filter Rombel & Komponen -->
            <div class="toolbar panel grade-toolbar">
                <div class="score-filter-field">
                    <label for="class-select">Pilih Kelas / Rombel</label>
                    <select
                        id="class-select"
                        v-model="selectedClassId"
                        class="score-select"
                        @change="onFilterChange"
                    >
                        <option value="" disabled>-- Pilih Kelas --</option>
                        <option v-for="cls in props.classes" :key="cls.id" :value="String(cls.id)">
                            {{ cls.name }} (Tingkat {{ cls.grade_level }})
                        </option>
                    </select>
                </div>

                <div class="score-filter-field">
                    <label for="component-select">Pilih Komponen Nilai</label>
                    <select
                        id="component-select"
                        v-model="selectedComponentId"
                        class="score-select"
                        @change="onFilterChange"
                    >
                        <option value="" disabled>-- Pilih Komponen --</option>
                        <option v-for="comp in props.components" :key="comp.id" :value="String(comp.id)">
                            {{ comp.name }} (Bobot: {{ comp.weight_percentage }}%, KKTP: {{ comp.passing_grade }})
                        </option>
                    </select>
                </div>

                <div class="score-toolbar-actions">
                    <span class="score-filter-context">{{ currentComponent?.code ?? '—' }} · KKTP {{ passingGrade }}</span>
                    <button
                        type="button"
                        :disabled="isSaving || localRows.length === 0"
                        class="primary-button"
                        @click="submitScores"
                    >
                        <span aria-hidden="true">{{ isSaving ? '…' : '✓' }}</span>
                        <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Semua Nilai' }}</span>
                    </button>
                </div>
            </div>

            <!-- Tabel Spreadsheet-like Input Nilai -->
            <div class="grade-card panel score-table-card">
                <div class="score-table-heading">
                    <div>
                        <p class="eyebrow">{{ selectedCourse?.subject_name ?? 'Penilaian kelas' }} · {{ selectedCourse?.class_name ?? selectedClass?.name ?? 'Rombel' }}</p>
                        <h2>Lembar nilai kelas</h2>
                        <p>Isi nilai pada setiap komponen. Nilai akhir dan rata-rata berbobot dihitung otomatis sesuai KKTP.</p>
                    </div>
                    <div class="score-table-tools">
                        <label class="student-search">
                            <span aria-hidden="true">⌕</span>
                            <input v-model="studentSearch" type="search" placeholder="Cari siswa / NIS" aria-label="Cari siswa atau NIS" />
                        </label>
                        <span class="table-count">{{ filteredRows.length }} / {{ localRows.length }} siswa</span>
                    </div>
                </div>

                <div class="table-scroll">
                        <table class="grade-table score-input-table">
                        <thead>
                            <tr>
                                <th rowspan="2" class="score-index-col">No</th>
                                <th rowspan="2" class="score-nis-col">NIS</th>
                                <th rowspan="2" class="score-name-col">Nama siswa</th>
                                <th :colspan="Math.max(components.length, 1)" class="score-group-heading">Komponen penilaian</th>
                                <th rowspan="2" class="score-average-col">Rata-rata akhir</th>
                                <th rowspan="2" class="score-notes-col">Catatan guru</th>
                            </tr>
                            <tr>
                                <th v-for="component in components" :key="component.id" class="score-component-heading" :title="`${component.name} · bobot ${component.weight_percentage}%`">
                                    {{ component.code || component.name }}
                                    <small>{{ component.weight_percentage }}%</small>
                                </th>
                                <th v-if="components.length === 0" class="score-component-heading">Nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-normal">
                            <tr
                                v-for="(row, idx) in filteredRows"
                                :key="row.enrollment_id"
                                class="score-row"
                            >
                                <td class="score-index-cell">
                                    {{ row.roll_number || localRows.indexOf(row) + 1 }}
                                </td>
                                <td class="score-nis-cell">
                                    {{ row.nis }}
                                </td>
                                <td class="score-name-cell">
                                    {{ row.name }}
                                </td>
                                <td v-for="component in components" :key="component.id" class="score-component-cell">
                                    <input
                                        v-model.number="scoreCell(row, component.id).raw_score"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="100"
                                        placeholder="—"
                                        class="score-input"
                                        :aria-label="`Nilai ${component.name} untuk ${row.name}`"
                                    />
                                    <small v-if="scoreCell(row, component.id).remedial_score !== null && scoreCell(row, component.id).remedial_score !== ''" class="score-remedial">Remedial {{ scoreCell(row, component.id).remedial_score }}</small>
                                    <strong v-if="componentFinal(row, component) !== null" class="score-cell-final" :class="componentFinal(row, component)! >= Number(component.passing_grade) ? 'is-passed' : 'is-below'">{{ componentFinal(row, component) }}</strong>
                                </td>
                                <td v-if="components.length === 0" class="text-center text-slate-400">—</td>
                                <td class="score-final-cell">
                                    <span v-if="calculateAverage(row) !== null" :class="calculateAverage(row)! >= passingGrade ? 'is-passed' : 'is-below'">{{ calculateAverage(row) }}</span>
                                    <span v-else>—</span>
                                </td>
                                <td>
                                    <input
                                        v-model="scoreCell(row, selectedComponentId || currentComponent?.id || '').notes"
                                        type="text"
                                        maxlength="255"
                                        placeholder="Catatan..."
                                        class="note-input"
                                        :aria-label="`Catatan ${row.name}`"
                                    />
                                </td>
                            </tr>
                            <tr v-if="filteredRows.length === 0">
                                <td :colspan="components.length + 5" class="text-center py-10 text-slate-400 text-sm">
                                    {{ studentSearch ? 'Siswa tidak ditemukan.' : 'Tidak ada data siswa untuk kelas dan komponen ini.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.score-page { max-width: 1280px; margin: 0 auto; }
.score-hero { align-items: center; }
.score-hero-actions { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
.score-hero-actions .secondary-button { white-space: nowrap; }
.score-hero-context { display: grid; gap: .2rem; padding: .85rem 1rem; color: var(--ink-soft); background: var(--primary-soft); border: 1px solid color-mix(in srgb, var(--primary) 18%, var(--line)); border-radius: var(--radius-md); }
.score-hero-context span, .score-hero-context small { font-size: .72rem; }
.score-hero-context strong { color: var(--primary-strong); font-size: 1rem; }
.workspace-tabs { display: flex; align-items: center; gap: .25rem; overflow-x: auto; padding: .3rem; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); }
.workspace-tab { display: inline-flex; align-items: center; gap: .45rem; min-height: 36px; padding: .45rem .75rem; color: var(--ink-soft); border-radius: .5rem; font-size: .78rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
.workspace-tab:hover { color: var(--primary-strong); background: var(--primary-soft); }
.workspace-tab.is-active { color: var(--primary-strong); background: var(--primary-soft); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--primary) 22%, transparent); }
.course-dashboard, .score-table-card { min-width: 0; overflow: hidden; }
.course-dashboard { padding: 1rem 1.1rem 1.1rem; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); }
.section-heading, .score-table-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.section-heading h2, .score-table-heading h2 { margin: .2rem 0 0; color: var(--ink); font-size: 1.08rem; letter-spacing: -.015em; }
.section-count, .table-count { color: var(--ink-faint); font-size: .74rem; font-weight: 700; white-space: nowrap; }
.course-card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: .65rem; margin-top: .9rem; }
.course-card { display: flex; align-items: center; gap: .7rem; min-width: 0; padding: .75rem; color: var(--ink-soft); text-align: left; background: var(--surface-subtle); border: 1px solid var(--line); border-radius: .75rem; transition: border-color .15s ease, background-color .15s ease, transform .15s ease; }
.course-card:hover { transform: translateY(-1px); border-color: color-mix(in srgb, var(--primary) 42%, var(--line)); }
.course-card.is-selected { color: var(--primary-strong); background: var(--primary-soft); border-color: color-mix(in srgb, var(--primary) 45%, var(--line)); box-shadow: 0 4px 12px color-mix(in srgb, var(--primary) 12%, transparent); }
.course-card-icon { display: inline-grid; flex: 0 0 34px; width: 34px; height: 34px; place-items: center; color: var(--primary-strong); background: var(--surface); border: 1px solid var(--line); border-radius: .6rem; font-size: .85rem; font-weight: 800; }
.course-card-body { display: grid; min-width: 0; gap: .12rem; }
.course-card-body strong, .course-card-body small { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.course-card-body strong { color: var(--ink); font-size: .82rem; }
.course-card-body small { color: var(--ink-faint); font-size: .71rem; }
.course-card-mark { margin-left: auto; color: var(--primary); font-size: 1.05rem; font-weight: 800; }
.score-summary { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.score-summary-card { min-width: 0; padding: 1rem; background: var(--surface); border: 1px solid var(--line); border-top: 3px solid var(--primary); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); }
.score-summary-card span, .score-summary-card small { display: block; color: var(--ink-faint); font-size: .72rem; }
.score-summary-card strong { display: block; margin: .3rem 0; color: var(--ink); font-size: 1.45rem; line-height: 1.1; }
.score-summary-blue { border-top-color: var(--blue); } .score-summary-green { border-top-color: var(--green); } .score-summary-red { border-top-color: var(--red); } .score-summary-gold { border-top-color: var(--gold); }
.rule-value { font-size: 1.15rem !important; }
.grade-toolbar { display: grid; grid-template-columns: minmax(180px, 1fr) minmax(220px, 1.25fr) auto; align-items: end; gap: .85rem; padding: 1rem; }
.score-filter-field { display: grid; gap: .35rem; min-width: 0; }
.score-filter-field label { color: var(--ink-soft); font-size: .72rem; font-weight: 750; }
.score-select { width: 100%; min-width: 0; min-height: 40px; padding: .55rem .7rem; color: var(--ink); background: var(--surface-subtle); border: 1px solid var(--line-strong); border-radius: .6rem; font-size: .8rem; }
.score-toolbar-actions { display: flex; align-items: center; justify-content: flex-end; gap: .65rem; flex-wrap: wrap; }
.score-filter-context { color: var(--ink-faint); font-size: .72rem; white-space: nowrap; }
.primary-button:disabled { cursor: not-allowed; opacity: .55; box-shadow: none; }
.score-table-heading { align-items: center; padding: 1rem 1.1rem; border-bottom: 1px solid var(--line); }
.score-table-heading p:not(.eyebrow) { margin: .3rem 0 0; color: var(--ink-faint); font-size: .75rem; }
.score-table-tools { display: flex; align-items: center; gap: .7rem; }
.student-search { display: flex; align-items: center; gap: .4rem; width: min(210px, 35vw); min-height: 36px; padding: 0 .65rem; color: var(--ink-faint); background: var(--surface-subtle); border: 1px solid var(--line); border-radius: .55rem; }
.student-search input { width: 100%; min-width: 0; color: var(--ink); background: transparent; border: 0; outline: 0; font-size: .76rem; }
.score-input-table { min-width: 980px; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
.score-input-table th, .score-input-table td { padding: .65rem .55rem; border-bottom: 1px solid var(--line); vertical-align: middle; }
.score-input-table thead th { color: var(--ink-faint); background: var(--surface-subtle); font-size: .7rem; font-weight: 800; text-align: center; text-transform: uppercase; letter-spacing: .035em; white-space: nowrap; }
.score-input-table thead th[rowspan] { vertical-align: middle; }
.score-group-heading { color: var(--primary-strong) !important; background: var(--primary-soft) !important; }
.score-component-heading { width: 104px; color: var(--ink) !important; text-transform: none !important; }
.score-component-heading small { display: block; margin-top: .15rem; color: var(--ink-faint); font-size: .64rem; font-weight: 700; text-transform: none; }
.score-input-table tbody td { color: var(--ink-soft); font-size: .8rem; }
.score-input-table tbody tr:hover td { background: color-mix(in srgb, var(--primary-soft) 42%, var(--surface)); }
.score-index-col, .score-index-cell { width: 52px; text-align: center !important; }
.score-nis-col, .score-nis-cell { width: 105px; }
.score-name-col, .score-name-cell { width: 220px; }
.score-average-col { width: 112px; }
.score-notes-col { width: 180px; }
.score-index-cell { color: var(--ink-faint) !important; font-size: .72rem !important; }
.score-nis-cell { color: var(--ink-faint) !important; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .72rem !important; }
.score-name-cell { overflow: hidden; color: var(--ink) !important; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
.score-input-table .score-input, .score-input-table .note-input { width: 100%; min-width: 0; min-height: 35px; padding: .45rem .35rem; color: var(--ink); background: var(--surface); border: 1px solid var(--line-strong); border-radius: .5rem; font-size: .8rem; text-align: center; }
.score-input-table .note-input { padding-inline: .55rem; text-align: left; }
.score-input-table .score-input:focus, .score-input-table .note-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 14%, transparent); outline: 0; }
.score-component-cell { text-align: center; }
.score-cell-final { display: block; margin-top: .25rem; font-size: .7rem; }
.score-remedial { display: block; margin-top: .2rem; color: var(--ink-faint); font-size: .62rem; }
.is-passed { color: var(--green) !important; }
.is-below { color: var(--red) !important; }
.score-final-cell { color: var(--ink) !important; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .85rem !important; font-weight: 800; text-align: center; }
.score-status-cell { text-align: center; }
@media print {
    .score-page { max-width: none; }
    .score-hero-actions, .workspace-tabs, .course-dashboard, .score-summary, .grade-toolbar, .score-table-tools, .app-sidebar, .app-topbar { display: none !important; }
    .score-table-card { border: 0; box-shadow: none; }
    .table-scroll { overflow: visible; }
    .score-input-table { min-width: 0; font-size: 9px; }
    .score-input-table .score-input, .score-input-table .note-input { border: 0; padding: 0; background: transparent; }
}
@media (max-width: 900px) { .score-hero { align-items: stretch; flex-direction: column; } .score-hero-actions { justify-content: flex-start; } .score-hero-context { width: 100%; } .grade-toolbar { grid-template-columns: 1fr 1fr; } .score-toolbar-actions { grid-column: 1 / -1; justify-content: space-between; } .score-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 600px) { .score-summary { grid-template-columns: 1fr 1fr; gap: .55rem; } .score-summary-card { padding: .75rem; } .score-summary-card strong { font-size: 1.2rem; } .grade-toolbar { grid-template-columns: 1fr; } .score-toolbar-actions { grid-column: auto; align-items: stretch; flex-direction: column; } .score-filter-context { text-align: left; } .score-table-heading { align-items: flex-start; flex-direction: column; } .score-table-tools { width: 100%; justify-content: space-between; } .student-search { width: min(100%, 240px); } .workspace-tabs { margin-inline: -.15rem; } }
</style>

