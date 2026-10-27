<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

type Distribution = { label: string; count: number };
type ExamItem = {
    question_id: number;
    prompt: string;
    type: string;
    max_points: number;
    attempts: number;
    answered: number;
    correct_count: number | null;
    correct_rate: number | null;
    average_points: number | null;
    average_percent: number | null;
};
type Exam = { id: number; title: string; status: string; starts_at: string };

const props = defineProps<{
    filters: Record<string, string | number | undefined>;
    subjects: { id: number; name: string }[];
    categories: { id: number; name: string }[];
    exams: Exam[];
    bank: {
        total: number;
        by_type: Distribution[];
        by_subject: Distribution[];
        by_category: Distribution[];
        by_curriculum: Distribution[];
        by_cognitive_level: Distribution[];
    };
    exam_results: {
        available: boolean;
        exam: Exam | null;
        total_attempts: number;
        graded_attempts: number;
        average_score: number | null;
        score_distribution: (Distribution & { percentage: number | null })[];
        items: ExamItem[] | null;
    };
    can_manage_question_bank: boolean;
}>();

const filterForm = reactive({
    subject_id: props.filters.subject_id ?? '',
    category_id: props.filters.category_id ?? '',
    type: props.filters.type ?? '',
    jenjang: props.filters.jenjang ?? '',
    curriculum: props.filters.curriculum ?? '',
    cognitive_level: props.filters.cognitive_level ?? '',
    exam_id: props.filters.exam_id ?? '',
});

const exportUrl = computed(() => {
    const params = new URLSearchParams(Object.entries(filterForm)
        .filter(([, value]) => value !== '' && value !== null)
        .map(([key, value]) => [key, String(value)]));

    return `/penilaian/analisis/export?${params.toString()}`;
});

const charts = computed(() => [
    { title: 'Soal menurut tipe', rows: props.bank.by_type.map((row) => ({ ...row, label: labelType(row.label) })) },
    { title: 'Soal menurut mata pelajaran', rows: props.bank.by_subject },
    { title: 'Soal menurut kategori', rows: props.bank.by_category },
    { title: 'Soal menurut kurikulum', rows: props.bank.by_curriculum },
    { title: 'Soal menurut tingkat kognitif', rows: props.bank.by_cognitive_level },
]);

const activeFilterCount = computed(() => Object.entries(filterForm)
    .filter(([key, value]) => key !== 'exam_id' && value !== '')
    .length);
const hasQuestionData = computed(() => props.bank.total > 0);

function applyFilters() {
    router.get('/penilaian/analisis', filterForm, { preserveState: true, preserveScroll: true, replace: true });
}

function resetFilters() {
    const defaultExam = props.exams.find((exam) => exam.status === 'results_published');
    Object.assign(filterForm, {
        subject_id: '', category_id: '', type: '', jenjang: '', curriculum: '', cognitive_level: '', exam_id: defaultExam?.id ?? '',
    });
    router.get('/penilaian/analisis', filterForm, { preserveState: true, preserveScroll: true, replace: true });
}

function labelType(type: string) {
    return ({
        multiple_choice: 'Pilihan ganda', essay: 'Esai', true_false: 'Benar/Salah', matching: 'Menjodohkan',
    } as Record<string, string>)[type] ?? type;
}

function labelExamStatus(status: string) {
    return ({
        draft: 'Draf', published: 'Berlangsung', results_published: 'Hasil terbit', closed: 'Ditutup',
    } as Record<string, string>)[status] ?? status;
}

function formatDate(value: string) {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? 'Tanggal tidak tersedia' : new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(date);
}
</script>

<template>
    <Head :title="can_manage_question_bank ? 'Analisis Bank Soal dan Ujian' : 'Analisis Asesmen dan Ujian'" />
    <AppLayout>
        <main class="view space-y-6">
            <header class="page-heading compact-heading">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Wawasan asesmen</p>
                    <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ can_manage_question_bank ? 'Analisis Bank Soal & Ujian' : 'Analisis Asesmen & Ujian' }}</h1>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500">Pantau komposisi bank soal dan hasil ujian yang sudah diterbitkan. Ringkasan mengikuti cakupan akses akun Anda dan tidak menampilkan identitas siswa.</p>
                </div>
                <a :href="exportUrl" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ekspor XLSX</a>
            </header>

            <form @submit.prevent="applyFilters" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                    <label class="text-xs font-medium text-slate-600">Hasil ujian
                        <select v-model="filterForm.exam_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Pilih ujian</option><option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.title }} · {{ labelExamStatus(exam.status) }} · {{ formatDate(exam.starts_at) }}</option></select>
                        <span class="mt-1 block font-normal text-slate-500">Ujian terbaru dengan hasil terbit dipilih otomatis.</span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Terapkan</button>
                        <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="resetFilters">Reset</button>
                    </div>
                </div>
                <details class="mt-4 border-t border-slate-100 pt-3">
                    <summary class="flex cursor-pointer list-none flex-wrap items-center gap-2 py-1 text-sm font-semibold text-slate-700">
                        Filter bank soal
                        <span v-if="activeFilterCount" class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ activeFilterCount }} aktif</span>
                        <span class="ml-auto text-xs font-normal text-slate-500">Opsional</span>
                    </summary>
                    <p class="mb-3 mt-1 text-xs text-slate-500">Filter ini hanya mengubah komposisi bank soal; hasil ujian mengikuti pilihan di atas.</p>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <label class="text-xs font-medium text-slate-600">Mata pelajaran
                            <select v-model="filterForm.subject_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Semua mapel</option><option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option></select>
                        </label>
                        <label class="text-xs font-medium text-slate-600">Kategori
                            <select v-model="filterForm.category_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Semua kategori</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select>
                        </label>
                        <label class="text-xs font-medium text-slate-600">Tipe soal
                            <select v-model="filterForm.type" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Semua tipe</option><option value="multiple_choice">Pilihan ganda</option><option value="essay">Esai</option><option value="true_false">Benar/Salah</option><option value="matching">Menjodohkan</option></select>
                        </label>
                        <label class="text-xs font-medium text-slate-600">Jenjang
                            <select v-model="filterForm.jenjang" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Semua jenjang</option><option>SD</option><option>SMP</option><option>SMA</option></select>
                        </label>
                        <label class="text-xs font-medium text-slate-600">Kurikulum
                            <select v-model="filterForm.curriculum" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Semua kurikulum</option><option value="merdeka">Merdeka</option><option value="kbc">KBC</option><option value="both">Keduanya</option></select>
                        </label>
                        <label class="text-xs font-medium text-slate-600">Level kognitif
                            <select v-model="filterForm.cognitive_level" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Semua level</option><option value="L1">L1 · LOTS</option><option value="L2">L2 · MOTS</option><option value="L3">L3 · HOTS</option></select>
                        </label>
                    </div>
                </details>
            </form>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Ringkasan analisis">
                <article class="rounded-xl border border-indigo-100 bg-indigo-50 p-4 sm:p-5"><p class="text-sm text-indigo-700">Soal aktif sesuai filter</p><p class="mt-2 text-3xl font-semibold text-indigo-950">{{ bank.total }}</p><p class="mt-1 text-xs text-indigo-700/80">Bank soal dalam cakupan Anda</p></article>
                <article class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5"><p class="text-sm text-slate-500">Attempt ujian</p><p class="mt-2 text-3xl font-semibold text-slate-900">{{ exam_results.exam ? exam_results.total_attempts : '—' }}</p><p class="mt-1 text-xs text-slate-500">Semua status pengumpulan</p></article>
                <article class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5"><p class="text-sm text-slate-500">Attempt dinilai</p><p class="mt-2 text-3xl font-semibold text-slate-900">{{ exam_results.available ? exam_results.graded_attempts : '—' }}</p><p class="mt-1 text-xs text-slate-500">Hasil yang sudah diterbitkan</p></article>
                <article class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5"><p class="text-sm text-slate-500">Rata-rata nilai</p><p class="mt-2 text-3xl font-semibold text-slate-900">{{ exam_results.available ? (exam_results.average_score ?? '—') : '—' }}<span v-if="exam_results.available && exam_results.average_score !== null" class="ml-1 text-sm font-normal text-slate-500">/ 100</span></p><p class="mt-1 text-xs text-slate-500">Berdasarkan attempt yang dinilai</p></article>
            </section>

            <section v-if="!hasQuestionData" class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center">
                <h2 class="font-semibold text-slate-900">Belum ada soal untuk filter ini</h2>
                <p class="mx-auto mt-1 max-w-lg text-sm text-slate-500">Ubah atau reset filter untuk melihat ringkasan bank soal. Data ujian tetap mengikuti pilihan ujian di atas.</p>
                <button v-if="activeFilterCount" type="button" class="mt-4 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="resetFilters">Reset semua filter</button>
            </section>
            <section v-else class="grid gap-4 lg:grid-cols-2">
                <article v-for="chart in charts" :key="chart.title" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                    <h2 class="font-semibold text-slate-900">{{ chart.title }}</h2>
                    <p v-if="!chart.rows.length" class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-500">Belum ada rincian untuk filter ini.</p>
                    <div v-for="row in chart.rows" :key="row.label" class="mt-4">
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-sm"><span class="min-w-0 truncate text-slate-600">{{ row.label }}</span><span class="shrink-0 font-semibold tabular-nums text-slate-900">{{ row.count }}</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="img" :aria-label="`${row.label}: ${row.count} soal`"><div class="h-full rounded-full bg-indigo-500 transition-all" :style="{ width: `${Math.max(2, row.count / Math.max(1, ...chart.rows.map((item) => item.count)) * 100)}%` }" /></div>
                    </div>
                </article>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                <div><h2 class="font-semibold text-slate-900">Distribusi nilai</h2><p v-if="exam_results.exam" class="mt-1 text-sm text-slate-500">{{ exam_results.exam.title }} · nilai pada skala 0–100</p></div>
                <p v-if="!exam_results.exam" class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Pilih ujian lalu terapkan filter untuk melihat ringkasan nilainya.</p>
                <p v-else-if="!exam_results.available" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm leading-relaxed text-amber-900">Hasil ujian ini belum diterbitkan. Distribusi nilai dan analisis butir akan muncul setelah guru menerbitkan hasil.</p>
                <p v-else-if="!exam_results.graded_attempts" class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Belum ada attempt yang dapat dianalisis pada ujian ini.</p>
                <div v-else class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div v-for="band in exam_results.score_distribution" :key="band.label" class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                        <div class="flex justify-between gap-2 text-sm"><span class="text-slate-600">{{ band.label }}</span><b class="tabular-nums text-slate-900">{{ band.count }}</b></div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full bg-emerald-500" :style="{ width: `${Math.max(0, Math.min(100, band.percentage ?? 0))}%` }" /></div>
                        <p class="mt-1.5 text-xs text-slate-500">{{ band.percentage ?? '—' }}% dari hasil dinilai</p>
                    </div>
                </div>
            </section>

            <section v-if="exam_results.available && exam_results.items" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                <div><h2 class="font-semibold text-slate-900">Analisis per butir</h2><p class="mt-1 text-xs leading-relaxed text-slate-500">Rasio benar hanya tersedia untuk soal objektif. Rata-rata poin menghitung attempt tanpa skor butir sebagai nol. Nama siswa dan isi jawaban tidak ditampilkan.</p></div>
                <p class="mt-3 text-xs text-slate-500 lg:hidden">Geser tabel ke samping untuk melihat semua kolom.</p>
                <div class="mt-4 min-w-0 overflow-x-auto rounded-lg border border-slate-200">
                    <table class="analytics-item-table w-full min-w-[820px] table-fixed text-left text-sm">
                        <colgroup><col class="w-[42%]"><col class="w-[15%]"><col class="w-[13%]"><col class="w-[14%]"><col class="w-[16%]"></colgroup>
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-3 py-3">Butir</th><th class="px-3 py-3">Tipe</th><th class="px-3 py-3">Terjawab</th><th class="px-3 py-3">Benar</th><th class="px-3 py-3">Rata-rata poin</th></tr></thead>
                        <tbody>
                            <tr v-for="item in exam_results.items" :key="item.question_id" class="border-t border-slate-100 align-top"><td class="whitespace-normal break-words px-3 py-3 text-slate-700 [overflow-wrap:anywhere]">{{ item.prompt }}</td><td class="whitespace-normal break-words px-3 py-3 text-slate-600">{{ labelType(item.type) }}</td><td class="whitespace-nowrap px-3 py-3 tabular-nums">{{ item.answered }}/{{ item.attempts }}</td><td class="whitespace-nowrap px-3 py-3 tabular-nums">{{ item.correct_rate === null ? '—' : `${item.correct_count} (${item.correct_rate}%)` }}</td><td class="whitespace-normal break-words px-3 py-3 tabular-nums">{{ item.average_points === null ? '—' : `${item.average_points} / ${item.max_points} (${item.average_percent}%)` }}</td></tr>
                            <tr v-if="!exam_results.items.length"><td colspan="5" class="px-3 py-6 text-center text-slate-500">Ujian ini belum memiliki butir soal.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <p class="text-xs leading-relaxed text-slate-500">Definisi: persentase distribusi adalah jumlah attempt pada rentang ÷ seluruh attempt yang sudah dinilai; rasio benar butir adalah jawaban benar ÷ jawaban objektif yang dinilai; rata-rata poin adalah total poin butir ÷ seluruh attempt yang dinilai. Analisis statistik tidak membuka kunci jawaban.</p>
        </main>
    </AppLayout>
</template>

<style scoped>
.analytics-item-table {
    table-layout: fixed;
}

.analytics-item-table th,
.analytics-item-table td {
    white-space: normal !important;
    overflow-wrap: anywhere;
}

.analytics-item-table tbody td:nth-child(3),
.analytics-item-table tbody td:nth-child(4) {
    white-space: nowrap !important;
}
</style>
