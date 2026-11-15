<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface SubjectCol {
    id: number;
    name: string;
    code: string;
}

interface StudentLedgerRow {
    enrollment_id: number;
    student_id: number;
    nis: string;
    name: string;
    grades: Record<number, number | null>;
    total_score?: number;
    average: number | null;
    average_score?: number;
    rank?: number | null;
    report_status?: string;
    attendance: {
        present: number;
        sick: number;
        permitted: number;
        absent: number;
    };
}

interface LedgerData {
    report_mode?: 'sas' | 'sts';
    school?: {
        name: string;
        npsn?: string;
    } | null;
    class_group: {
        id: number;
        name: string;
        grade_level?: number;
        phase?: string;
        homeroom_teacher_name?: string | null;
    } | null;
    term: {
        id: number;
        name: string;
        academic_year_id?: number;
        academic_year_name?: string;
    } | null;
    subjects: SubjectCol[];
    students: StudentLedgerRow[];
}

const props = defineProps<{
    ledger: LedgerData;
    academic_years?: { id: number; name: string; is_active?: boolean }[];
    selected_academic_year_id?: string;
    terms?: { id: number; academic_year_id: number; name: string; is_active?: boolean }[];
    selected_term_id?: string;
    classes?: { id: number; name: string; grade_level: number }[];
    selected_class_id?: string;
    report_mode?: 'sas' | 'sts';
}>();

const selectedAcademicYearId = ref(
    props.selected_academic_year_id
        ?? (props.ledger.term?.academic_year_id ? String(props.ledger.term.academic_year_id) : ''),
);
const selectedTermId = ref(
    props.selected_term_id
        ?? (props.ledger.term?.id ? String(props.ledger.term.id) : ''),
);
const selectedClassId = ref(
    props.selected_class_id
        ?? (props.ledger.class_group?.id ? String(props.ledger.class_group.id) : ''),
);
const reportMode = ref<'sas' | 'sts'>(props.report_mode ?? props.ledger.report_mode ?? 'sas');
const search = ref('');

watch(() => props.selected_academic_year_id, (val) => { if (val !== undefined) selectedAcademicYearId.value = val ?? ''; });
watch(() => props.selected_term_id, (val) => { if (val !== undefined) selectedTermId.value = val ?? ''; });
watch(() => props.selected_class_id, (val) => { if (val !== undefined) selectedClassId.value = val ?? ''; });
watch(() => props.report_mode, (val) => { if (val) reportMode.value = val; });

const applyFilters = (overrides: Record<string, string | undefined> = {}) => {
    router.get(
        '/penilaian/ledger',
        {
            academic_year_id: selectedAcademicYearId.value || undefined,
            term_id: selectedTermId.value || undefined,
            class_group_id: selectedClassId.value || undefined,
            report_mode: reportMode.value,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const onAcademicYearChange = () => applyFilters({ term_id: undefined, class_group_id: undefined });
const onTermChange = () => applyFilters();
const onClassChange = () => applyFilters();
const onModeChange = () => applyFilters();

const filteredStudents = computed(() => {
    const q = search.value.trim().toLocaleLowerCase('id-ID');
    if (!q) return props.ledger.students;
    return props.ledger.students.filter((stu) =>
        `${stu.name} ${stu.nis}`.toLocaleLowerCase('id-ID').includes(q),
    );
});

const classAverage = computed(() => {
    const valid = props.ledger.students.filter((s) => s.average !== null && s.average !== undefined);
    if (!valid.length) return '—';
    const avg = valid.reduce((sum, s) => sum + Number(s.average ?? 0), 0) / valid.length;
    return avg.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});

const page = usePage<{ auth?: { user?: { role?: string } } }>();
const isPrincipal = computed(() => page.props.auth?.user?.role === 'principal');

const reportsHref = computed(() => {
    const params = new URLSearchParams();
    if (selectedAcademicYearId.value) params.set('academic_year_id', selectedAcademicYearId.value);
    if (selectedTermId.value) params.set('term_id', selectedTermId.value);
    if (selectedClassId.value) params.set('class_group_id', selectedClassId.value);
    params.set('report_mode', reportMode.value);
    const qs = params.toString();
    const basePath = isPrincipal.value ? '/kepsek/laporan/nilai' : '/penilaian/rapor';
    return `${basePath}${qs ? `?${qs}` : ''}`;
});

const exportQuery = computed(() => {
    const classId = selectedClassId.value || (props.ledger.class_group?.id ? String(props.ledger.class_group.id) : '');
    const termId = selectedTermId.value || (props.ledger.term?.id ? String(props.ledger.term.id) : '');
    if (!classId || !termId) return '';
    const params = new URLSearchParams({
        class_group_id: classId,
        term_id: termId,
        report_mode: reportMode.value,
    });
    return params.toString();
});

const excelUrl = computed(() => exportQuery.value ? `/penilaian/ledger/excel?${exportQuery.value}` : '');
const pdfPreviewUrl = computed(() => exportQuery.value ? `/penilaian/ledger/pdf?${exportQuery.value}` : '');
</script>

<template>
    <Head :title="`Ledger Nilai — ${props.ledger.class_group?.name || 'Kelas'}`" />
    <AppLayout>
        <div class="view ledger-page">
            <!-- Navigasi Kontekstual Rapor & Ledger Nilai -->
            <nav class="module-tabs" aria-label="Submenu Rapor dan Ledger">
                <Link :href="reportsHref" class="module-tab">
                    Rapor Siswa
                </Link>
                <Link href="/penilaian/ledger" class="module-tab is-active" aria-current="page">
                    Ledger Nilai
                </Link>
                <Link href="/penilaian/skema" class="module-tab">
                    Skema Penilaian
                </Link>
            </nav>

            <header class="ledger-heading">
                <div>
                    <span class="ledger-eyebrow">REKAPITULASI NILAI KELAS</span>
                    <h1>Buku Ledger Nilai — {{ props.ledger.class_group?.name ?? 'Pilih Rombel' }}</h1>
                    <p>
                        {{ props.ledger.term?.academic_year_name ?? 'Tahun Pelajaran' }}
                        · Semester {{ props.ledger.term?.name ?? '—' }}
                        · {{ reportMode === 'sts' ? 'Tengah Semester (STS)' : 'Akhir Semester (SAS)' }}
                    </p>
                </div>

                <div class="ledger-actions">
                    <Link :href="reportsHref" class="secondary-button ui-button ui-button--secondary ui-button--md">
                        Kembali ke Rapor
                    </Link>
                    <AppButton
                        v-if="excelUrl"
                        variant="secondary"
                        as="a"
                        :href="excelUrl"
                        class="secondary-button"
                    >
                        Export Excel (.xlsx)
                    </AppButton>
                    <AppButton
                        v-if="pdfPreviewUrl"
                        variant="primary"
                        icon="pdf"
                        as="a"
                        target="_blank"
                        rel="noopener noreferrer"
                        :href="pdfPreviewUrl"
                        class="pdf-button"
                    >
                        Pratinjau / Cetak PDF
                    </AppButton>
                </div>
            </header>

            <div class="ss-summary-grid ss-summary-grid--4" aria-label="Ringkasan ledger kelas">
                <MetricSummaryCard
                    label="Peserta didik"
                    :value="props.ledger.students.length"
                    :note="props.ledger.class_group?.name ?? 'Rombel'"
                    tone="blue"
                    icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                />
                <MetricSummaryCard
                    label="Mata pelajaran"
                    :value="props.ledger.subjects.length"
                    note="terjadwal di rombel"
                    tone="violet"
                    icon="M4 5.5A2.5 2.5 0 016.5 3H20v16H6.5A2.5 2.5 0 014 16.5v-11z"
                />
                <MetricSummaryCard
                    label="Rerata kelas"
                    :value="classAverage"
                    :note="`Mode ${reportMode.toUpperCase()}`"
                    tone="green"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    label="Jenis rapor"
                    :value="reportMode.toUpperCase()"
                    :note="`${props.ledger.term?.name ?? '—'} · ${props.ledger.term?.academic_year_name ?? ''}`"
                    tone="amber"
                    icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
            </div>

            <section class="panel ledger-panel">
                <div class="ledger-filters">
                    <label v-if="academic_years?.length" for="ledger-academic-year">
                        Tahun pelajaran
                        <select id="ledger-academic-year" v-model="selectedAcademicYearId" @change="onAcademicYearChange">
                            <option v-for="yr in academic_years" :key="yr.id" :value="String(yr.id)">{{ yr.name }}</option>
                        </select>
                    </label>
                    <label v-if="terms?.length" for="ledger-term">
                        Semester
                        <select id="ledger-term" v-model="selectedTermId" @change="onTermChange">
                            <option v-for="trm in terms" :key="trm.id" :value="String(trm.id)">{{ trm.name }}</option>
                        </select>
                    </label>
                    <label v-if="classes?.length" for="ledger-class">
                        Kelas / rombel
                        <select id="ledger-class" v-model="selectedClassId" @change="onClassChange">
                            <option v-for="cls in classes" :key="cls.id" :value="String(cls.id)">{{ cls.name }}</option>
                        </select>
                    </label>
                    <label for="ledger-mode">
                        Jenis rapor
                        <select id="ledger-mode" v-model="reportMode" @change="onModeChange">
                            <option value="sas">Akhir Semester (SAS)</option>
                            <option value="sts">Tengah Semester (STS)</option>
                        </select>
                    </label>
                    <label for="ledger-search">
                        Cari siswa
                        <input id="ledger-search" v-model="search" type="search" placeholder="Cari nama atau NIS…">
                    </label>
                </div>

                <div class="ledger-toolbar">
                    <div>
                        <h2>Matriks Nilai Seluruh Mata Pelajaran ({{ reportMode.toUpperCase() }})</h2>
                        <p>Menampilkan nilai per mata pelajaran, jumlah nilai, rata-rata, peringkat, dan rekap presensi (S, I, A).</p>
                    </div>
                    <span class="ledger-meta-count">
                        {{ filteredStudents.length }} Siswa · {{ props.ledger.subjects.length }} Mata Pelajaran
                    </span>
                </div>

                <div class="table-scroll ledger-scroll" tabindex="0" role="region" aria-label="Tabel matriks ledger nilai">
                    <table class="app-table ledger-table">
                        <thead>
                            <tr>
                                <th scope="col" rowspan="2" class="col-center col-no">No</th>
                                <th scope="col" rowspan="2" class="col-nis">NIS</th>
                                <th scope="col" rowspan="2" class="col-name">Nama Peserta Didik</th>
                                <th
                                    v-if="props.ledger.subjects.length"
                                    :colspan="props.ledger.subjects.length"
                                    class="col-center col-group-subjects"
                                >
                                    Mata Pelajaran ({{ reportMode.toUpperCase() }})
                                </th>
                                <th scope="col" rowspan="2" class="col-center col-summary">Jumlah</th>
                                <th scope="col" rowspan="2" class="col-center col-summary">Rerata</th>
                                <th scope="col" rowspan="2" class="col-center col-summary">Rank</th>
                                <th scope="colgroup" colspan="3" class="col-center col-group-att">Presensi</th>
                            </tr>
                            <tr>
                                <th
                                    v-for="sub in props.ledger.subjects"
                                    :key="sub.id"
                                    class="col-center col-subj"
                                    :title="sub.name"
                                >
                                    {{ sub.code || sub.name.slice(0, 6) }}
                                </th>
                                <th scope="col" class="col-center col-att">S</th>
                                <th scope="col" class="col-center col-att">I</th>
                                <th scope="col" class="col-center col-att">A</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(stu, idx) in filteredStudents" :key="stu.student_id">
                                <td class="col-center">{{ idx + 1 }}</td>
                                <td class="col-mono">{{ stu.nis }}</td>
                                <td><strong>{{ stu.name }}</strong></td>
                                <td
                                    v-for="sub in props.ledger.subjects"
                                    :key="sub.id"
                                    class="col-center col-mono"
                                >
                                    <span v-if="stu.grades[sub.id] !== undefined && stu.grades[sub.id] !== null">
                                        {{ stu.grades[sub.id] }}
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="col-center col-mono col-bold">
                                    {{ stu.total_score ? stu.total_score : '—' }}
                                </td>
                                <td class="col-center col-mono col-bold col-highlight">
                                    {{ stu.average !== null && stu.average !== undefined ? stu.average : '—' }}
                                </td>
                                <td class="col-center col-mono col-bold">
                                    {{ stu.rank ?? '—' }}
                                </td>
                                <td class="col-center col-mono">{{ stu.attendance?.sick ?? 0 }}</td>
                                <td class="col-center col-mono">{{ stu.attendance?.permitted ?? 0 }}</td>
                                <td class="col-center col-mono">{{ stu.attendance?.absent ?? 0 }}</td>
                            </tr>
                            <tr v-if="!filteredStudents.length">
                                <td :colspan="9 + props.ledger.subjects.length" class="ledger-empty">
                                    <strong>Tidak ada data nilai atau siswa ditemukan</strong>
                                    <p>Pastikan tahun pelajaran, semester, dan kelas/rombel sudah memiliki peserta didik aktif.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.ledger-page{display:grid;gap:1.15rem;max-width:1440px;margin-inline:auto}
.module-tabs{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem;border:1px solid var(--line);border-radius:.75rem;background:var(--surface);width:fit-content;flex-wrap:wrap}
.module-tab{display:inline-flex;align-items:center;padding:.45rem .9rem;border-radius:.55rem;font-size:var(--type-sm);font-weight:var(--weight-semibold);color:var(--ink-soft);text-decoration:none;transition:background 150ms ease,color 150ms ease}
.module-tab:hover{color:var(--ink);background:var(--surface-soft,var(--surface))}
.module-tab.is-active{background:var(--primary-soft);color:var(--primary-strong)}
.ledger-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:0 0 .85rem;border-bottom:1px solid var(--line);flex-wrap:wrap}
.ledger-eyebrow{display:block;color:var(--primary);font-size:var(--type-meta);font-weight:var(--weight-bold);letter-spacing:.09em}
.ledger-heading h1{font-size:var(--type-2xl);font-weight:var(--weight-bold);color:var(--ink);letter-spacing:-.025em;margin-top:.15rem}
.ledger-heading p{color:var(--ink-soft);font-size:var(--type-sm);margin-top:.3rem}
.ledger-actions{display:flex;align-items:center;gap:.55rem;flex-wrap:wrap}
.ledger-panel{overflow:hidden}
.ledger-filters{display:grid;grid-template-columns:repeat(5,minmax(150px,1fr));gap:.85rem;padding:1.15rem 1.4rem;background:var(--surface-soft,var(--surface));border-bottom:1px solid var(--line)}
.ledger-filters label{display:grid;gap:.35rem;font-size:var(--type-meta);color:var(--ink-soft);font-weight:var(--weight-semibold)}
.ledger-filters select,.ledger-filters input{width:100%;min-height:40px;padding:.5rem .7rem;border:1px solid var(--line-strong);border-radius:.6rem;background:var(--surface);color:var(--ink);font-size:var(--type-control);font-weight:var(--weight-regular)}
.ledger-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:1.1rem 1.4rem;border-bottom:1px solid var(--line);flex-wrap:wrap}
.ledger-toolbar h2{margin:0;font-size:var(--type-md);font-weight:var(--weight-bold);color:var(--ink)}
.ledger-toolbar p{margin:.2rem 0 0;font-size:var(--type-meta);color:var(--ink-soft)}
.ledger-meta-count{font-size:var(--type-meta);font-weight:var(--weight-semibold);color:var(--ink-soft)}
.ledger-scroll{overflow-x:auto}
.ledger-table{width:100%;border-collapse:collapse;font-size:var(--type-sm)}
.ledger-table th,.ledger-table td{padding:.65rem .75rem;border:1px solid var(--line)}
.ledger-table thead th{background:var(--surface-soft,var(--surface));color:var(--ink-soft);font-size:var(--type-meta);font-weight:var(--weight-bold);text-transform:uppercase}
.col-center{text-align:center}
.col-no{width:42px}
.col-nis{width:96px}
.col-name{min-width:180px}
.col-subj{min-width:58px}
.col-summary{width:68px}
.col-att{width:38px}
.col-group-subjects{background:var(--primary-soft) !important;color:var(--primary-strong) !important}
.col-group-att{background:var(--gold-soft) !important;color:var(--gold) !important}
.col-mono{font-variant-numeric:tabular-nums}
.col-bold{font-weight:var(--weight-bold)}
.col-highlight{background:color-mix(in srgb,var(--primary-soft) 45%,transparent);color:var(--primary-strong)}
.text-muted{color:var(--ink-faint)}
.ledger-empty{text-align:center;padding:2.5rem !important;color:var(--ink-soft)}
.ledger-empty p{margin-top:.35rem;font-size:var(--type-meta)}
@media(max-width:1024px){.ledger-filters{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.ledger-filters{grid-template-columns:1fr}.ledger-heading{flex-direction:column;align-items:stretch}}
</style>
