<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

type Scores = Record<string, number | null>;
interface StudentRow {
    enrollment_id: number;
    nis: string;
    name: string;
    spiritual_predicate: string | null;
    spiritual_description: string;
    spiritual_scores: Scores;
    social_predicate: string | null;
    social_description: string;
    social_scores: Scores;
}

const props = defineProps<{
    active_term: { name: string } | null;
    classes: Array<{ id: number; name: string }>;
    selected_class_id: string;
    students: StudentRow[];
}>();

const spiritualIndicators = [
    { key: 'taqwa', label: 'Taqwa' },
    { key: 'kejujuran', label: 'Kejujuran' },
    { key: 'disiplin', label: 'Disiplin' },
    { key: 'sabar', label: 'Sabar' },
    { key: 'syukur', label: 'Syukur' },
    { key: 'tawadhu', label: 'Tawadhu' },
];
const socialIndicators = [
    { key: 'empati', label: 'Empati' },
    { key: 'kerjasama', label: 'Kerja sama' },
    { key: 'toleransi', label: 'Toleransi' },
    { key: 'percaya_diri', label: 'Percaya diri' },
    { key: 'komunikasi', label: 'Komunikasi' },
];

const form = useForm({
    records: props.students.map((student) => ({
        enrollment_id: student.enrollment_id,
        spiritual_scores: { ...student.spiritual_scores },
        social_scores: { ...student.social_scores },
    })),
});
const studentByEnrollment = new Map(props.students.map((student) => [student.enrollment_id, student]));
const ratedCount = computed(() => form.records.filter((record) =>
    [...Object.values(record.spiritual_scores), ...Object.values(record.social_scores)].some((score) => score !== null && score !== undefined),
).length);
const saveLabel = computed(() => {
    if (form.processing) return 'Menyimpan…';
    if (form.isDirty) return 'Belum disimpan';
    if (form.recentlySuccessful) return 'Tersimpan';
    return `${props.students.length} siswa`;
});

function changeClass(event: Event) {
    router.get('/penilaian/sikap', { class_group_id: (event.target as HTMLSelectElement).value }, { preserveState: false });
}

function average(recordIndex: number, group: 'spiritual_scores' | 'social_scores'): string | null {
    const scores = Object.values(form.records[recordIndex]?.[group] ?? {})
        .filter((score): score is number => score !== null && score !== undefined && score !== 0);
    if (!scores.length) return null;
    return (scores.reduce((sum, score) => sum + Number(score), 0) / scores.length).toFixed(1);
}

function averageTone(value: string | null) {
    if (value === null) return '';
    const score = Number(value);
    return score >= 4 ? 'average-high' : score >= 3 ? 'average-mid' : 'average-low';
}

function legacyLabel(predicate: string) {
    return ({ excellent: 'Sangat Baik', good: 'Baik', fair: 'Cukup', needs_guidance: 'Perlu Bimbingan' } as Record<string, string>)[predicate] ?? predicate;
}

function submit() {
    if (form.processing || !form.isDirty) return;
    form.post('/penilaian/sikap', { preserveScroll: true });
}
</script>

<template>
    <Head title="Penilaian Sikap" />
    <AppLayout>
        <main class="attitude-page">
            <header class="attitude-header">
                <div>
                    <p class="attitude-eyebrow">Penilaian · {{ active_term?.name ?? 'Belum ada semester aktif' }}</p>
                    <h1>Penilaian Sikap</h1>
                    <p class="attitude-intro">Catat indikator sikap setiap siswa dengan skala 1–5. Rata-rata akan dihitung otomatis.</p>
                </div>
                <label class="class-picker">
                    <span>Rombongan belajar</span>
                    <select :value="selected_class_id" @change="changeClass" :disabled="!classes.length">
                        <option v-for="group in classes" :key="group.id" :value="group.id">{{ group.name }}</option>
                    </select>
                </label>
            </header>

            <div class="scale-note" role="note">
                <strong>Skala penilaian</strong>
                <span><b>1</b> Nilai terendah</span>
                <span><b>2</b></span>
                <span><b>3</b></span>
                <span><b>4</b></span>
                <span><b>5</b> Nilai tertinggi</span>
            </div>

            <form @submit.prevent="submit">
                <section v-for="group in [
                    { key: 'spiritual_scores', title: 'Sikap Spiritual', code: 'KI-1', icon: '✦', indicators: spiritualIndicators },
                    { key: 'social_scores', title: 'Sikap Sosial', code: 'KI-2', icon: '◎', indicators: socialIndicators },
                ]" :key="group.key" class="attitude-section">
                    <div class="section-heading">
                        <div class="section-mark" aria-hidden="true">{{ group.icon }}</div>
                        <div><h2>{{ group.title }} <span>{{ group.code }}</span></h2><p>Nilai perilaku yang teramati selama kegiatan belajar.</p></div>
                        <span class="student-count">{{ students.length }} siswa</span>
                    </div>

                    <div class="attitude-table-wrap">
                        <table class="attitude-table">
                            <thead>
                                <tr>
                                    <th class="student-column">Siswa</th>
                                    <th v-for="indicator in group.indicators" :key="indicator.key" class="indicator-column">{{ indicator.label }}</th>
                                    <th class="average-column">Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(record, index) in form.records" :key="record.enrollment_id">
                                    <th scope="row" class="student-cell">
                                        <strong>{{ studentByEnrollment.get(record.enrollment_id)?.name }}</strong>
                                        <small>{{ studentByEnrollment.get(record.enrollment_id)?.nis }}</small>
                                        <span v-if="studentByEnrollment.get(record.enrollment_id)?.[group.key === 'spiritual_scores' ? 'spiritual_predicate' : 'social_predicate']" class="legacy-predicate">Predikat lama: {{ legacyLabel(studentByEnrollment.get(record.enrollment_id)?.[group.key === 'spiritual_scores' ? 'spiritual_predicate' : 'social_predicate'] ?? '') }}</span>
                                    </th>
                                    <td v-for="indicator in group.indicators" :key="indicator.key">
                                        <label class="sr-only" :for="`${group.key}-${record.enrollment_id}-${indicator.key}`">{{ indicator.label }} — {{ studentByEnrollment.get(record.enrollment_id)?.name }}</label>
                                        <select :id="`${group.key}-${record.enrollment_id}-${indicator.key}`" v-model="record[group.key][indicator.key]">
                                            <option :value="null">—</option>
                                            <option v-for="score in 5" :key="score" :value="score">{{ score }}</option>
                                        </select>
                                    </td>
                                    <td class="average-cell"><span v-if="average(index, group.key)" class="average-badge" :class="averageTone(average(index, group.key))">{{ average(index, group.key) }}</span><span v-else class="average-empty">—</span></td>
                                </tr>
                                <tr v-if="!students.length"><td :colspan="group.indicators.length + 2" class="empty-row">Belum ada siswa aktif di rombongan belajar ini.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <details v-if="students.some((student) => student[group.key === 'spiritual_scores' ? 'spiritual_description' : 'social_description'])" class="legacy-notes">
                        <summary>Lihat deskripsi sikap dari penilaian sebelumnya</summary>
                        <div v-for="student in students.filter((item) => item[group.key === 'spiritual_scores' ? 'spiritual_description' : 'social_description'])" :key="student.enrollment_id" class="legacy-note">
                            <strong>{{ student.name }}</strong>
                            <p>{{ student[group.key === 'spiritual_scores' ? 'spiritual_description' : 'social_description'] }}</p>
                        </div>
                    </details>
                </section>

                <footer class="attitude-savebar">
                    <div class="save-status" role="status" aria-live="polite">
                        <span class="status-dot" :class="{ dirty: form.isDirty, saved: form.recentlySuccessful && !form.isDirty }"></span>
                        <span>{{ saveLabel }}</span>
                        <small v-if="form.isDirty">{{ ratedCount }} dari {{ students.length }} siswa sudah memiliki nilai</small>
                    </div>
                    <button class="save-button" type="submit" :disabled="form.processing || !form.isDirty || !students.length">
                        <span aria-hidden="true">✓</span>{{ form.processing ? 'Menyimpan…' : 'Simpan penilaian' }}
                    </button>
                </footer>
            </form>
        </main>
    </AppLayout>
</template>

<style scoped>
.attitude-page { --att-surface: var(--surface, #fff); --att-subtle: var(--surface-subtle, #f6f8fb); --att-ink: var(--ink, #17243a); --att-muted: var(--ink-soft, #607087); --att-line: var(--line, #dce3ed); --att-primary: var(--primary, #355ea8); display: grid; gap: 1rem; max-width: 1480px; margin: 0 auto; padding: clamp(1rem, 2.4vw, 2rem); padding-bottom: 6rem; color: var(--att-ink); }
.attitude-header { display: flex; align-items: end; justify-content: space-between; gap: 1.5rem; padding: clamp(1.2rem, 2.5vw, 2rem); border: 1px solid var(--att-line); border-radius: 1rem; background: linear-gradient(115deg, var(--att-surface), color-mix(in srgb, var(--att-primary) 8%, var(--att-surface))); }
.attitude-header h1 { margin: .25rem 0 .4rem; font-size: clamp(1.55rem, 3vw, 2.15rem); font-weight: 820; letter-spacing: -.04em; }
.attitude-header p { margin: 0; color: var(--att-muted); line-height: 1.5; }
.attitude-header .attitude-eyebrow { color: var(--att-primary); font-size: .76rem; font-weight: 760; }
.class-picker { display: grid; flex: 0 0 min(260px, 32%); gap: .4rem; color: var(--att-muted); font-size: .76rem; font-weight: 700; }
.class-picker select, .attitude-table select { min-height: 40px; border: 1px solid var(--att-line); border-radius: .55rem; background: var(--att-surface); color: var(--att-ink); font: inherit; }
.class-picker select { padding: .55rem .7rem; }
.scale-note { display: flex; flex-wrap: wrap; align-items: center; gap: .55rem; padding: .8rem 1rem; border: 1px solid var(--att-line); border-radius: .8rem; background: var(--att-subtle); color: var(--att-muted); font-size: .75rem; }
.scale-note strong { margin-right: .25rem; color: var(--att-ink); }
.scale-note span { display: inline-flex; align-items: center; gap: .32rem; }
.scale-note b { display: grid; width: 1.35rem; height: 1.35rem; place-items: center; border-radius: 50%; background: color-mix(in srgb, var(--att-primary) 12%, var(--att-surface)); color: var(--att-primary); }
.attitude-section { min-width: 0; overflow: hidden; border: 1px solid var(--att-line); border-radius: .9rem; background: var(--att-surface); box-shadow: var(--shadow-sm, 0 4px 16px rgb(24 48 78 / 5%)); }
.section-heading { display: flex; align-items: center; gap: .75rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--att-line); }
.section-mark { display: grid; flex: 0 0 2.5rem; height: 2.5rem; place-items: center; border-radius: .7rem; background: color-mix(in srgb, var(--att-primary) 11%, var(--att-surface)); color: var(--att-primary); font-size: 1.15rem; }
.section-heading h2 { margin: 0; font-size: 1rem; font-weight: 800; }.section-heading h2 span { margin-left: .35rem; color: var(--att-muted); font-size: .75rem; font-weight: 650; }
.section-heading p { margin: .2rem 0 0; color: var(--att-muted); font-size: .77rem; }.student-count { margin-left: auto; color: var(--att-muted); font-size: .75rem; white-space: nowrap; }
.attitude-table-wrap { overflow: auto; scrollbar-width: thin; }
.attitude-table { width: 100%; min-width: 850px; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
.attitude-table th, .attitude-table td { padding: .58rem .5rem; border-bottom: 1px solid var(--att-line); text-align: center; }
.attitude-table thead th { position: sticky; top: 0; z-index: 2; background: var(--att-subtle); color: var(--att-muted); font-size: .7rem; font-weight: 760; }
.attitude-table .student-column, .student-cell { position: sticky; left: 0; z-index: 1; width: 230px; text-align: left; }
.attitude-table .student-column { z-index: 3; }.student-cell { background: var(--att-surface); }
.student-cell strong, .student-cell small, .legacy-predicate { display: block; }.student-cell strong { font-size: .8rem; }.student-cell small { margin-top: .1rem; color: var(--att-muted); font-size: .7rem; font-weight: 500; }
.legacy-predicate { margin-top: .25rem; color: var(--att-muted); font-size: .66rem; font-weight: 500; }
.indicator-column { width: 88px; }.average-column { width: 100px; }
.attitude-table select { width: 100%; min-width: 48px; padding: .42rem; text-align: center; }
.attitude-table select:focus-visible, .class-picker select:focus-visible, .save-button:focus-visible, summary:focus-visible { outline: 3px solid color-mix(in srgb, var(--att-primary) 55%, transparent); outline-offset: 2px; }
.average-badge { display: inline-grid; min-width: 2.55rem; min-height: 2rem; place-items: center; border-radius: .55rem; font-size: .82rem; font-weight: 800; }.average-high { background: color-mix(in srgb, #178357 13%, var(--att-surface)); color: #08764b; }.average-mid { background: color-mix(in srgb, #ba7a15 14%, var(--att-surface)); color: #8a5700; }.average-low { background: color-mix(in srgb, #c24444 12%, var(--att-surface)); color: #a12e34; }.average-empty { color: var(--att-muted); }
.empty-row { padding: 2rem !important; color: var(--att-muted); text-align: center !important; }
.legacy-notes { padding: .75rem 1rem; color: var(--att-muted); font-size: .78rem; }.legacy-notes summary { width: fit-content; cursor: pointer; color: var(--att-primary); font-weight: 700; }.legacy-note { padding: .6rem .2rem 0; }.legacy-note strong { color: var(--att-ink); }.legacy-note p { margin: .2rem 0 0; }
.attitude-savebar { position: sticky; bottom: .7rem; z-index: 5; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .75rem .85rem; border: 1px solid var(--att-line); border-radius: .8rem; background: color-mix(in srgb, var(--att-surface) 94%, transparent); box-shadow: var(--shadow-md, 0 8px 25px rgb(24 48 78 / 12%)); backdrop-filter: blur(12px); }
.save-status { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; font-size: .8rem; font-weight: 720; }.save-status small { flex-basis: 100%; padding-left: 1rem; color: var(--att-muted); font-size: .7rem; font-weight: 500; }
.status-dot { width: .55rem; height: .55rem; border-radius: 50%; background: #178357; }.status-dot.dirty { background: #bd7912; box-shadow: 0 0 0 3px color-mix(in srgb, #bd7912 17%, transparent); }.status-dot.saved { background: #178357; }
.save-button { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; gap: .5rem; padding: .6rem 1rem; border: 1px solid var(--att-primary); border-radius: .6rem; background: var(--att-primary); color: #fff; font: inherit; font-size: .8rem; font-weight: 760; cursor: pointer; }.save-button:disabled { opacity: .55; cursor: not-allowed; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; clip-path: inset(50%); }
@media (max-width: 700px) { .attitude-page { padding: .75rem; padding-bottom: 6rem; }.attitude-header { align-items: stretch; flex-direction: column; }.class-picker { flex-basis: auto; }.scale-note { align-items: flex-start; flex-direction: column; }.section-heading { padding: .8rem; }.section-heading p { max-width: 42ch; }.attitude-table { min-width: 760px; }.attitude-table .student-column, .student-cell { width: 170px; }.attitude-table th, .attitude-table td { padding: .5rem .35rem; }.indicator-column { width: 72px; }.average-column { width: 82px; }.attitude-savebar { bottom: .35rem; }.save-status small { display: none; } }
@media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; } }
html[data-theme='dark'] .average-high { color: #81d5ab; } html[data-theme='dark'] .average-mid { color: #f0c678; } html[data-theme='dark'] .average-low { color: #f2a0a0; }
</style>
