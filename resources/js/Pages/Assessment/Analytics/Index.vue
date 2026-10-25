<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

type Distribution = { label: string; count: number };
type ExamItem = { question_id: number; prompt: string; type: string; max_points: number; attempts: number; answered: number; correct_count: number | null; correct_rate: number | null; average_points: number | null; average_percent: number | null };
const props = defineProps<{
    filters: Record<string, string | number | undefined>;
    subjects: { id: number; name: string }[];
    categories: { id: number; name: string }[];
    exams: { id: number; title: string; status: string; starts_at: string }[];
    bank: { total: number; by_type: Distribution[]; by_subject: Distribution[]; by_category: Distribution[]; by_curriculum: Distribution[]; by_cognitive_level: Distribution[] };
    exam_results: { available: boolean; exam: any; total_attempts: number; graded_attempts: number; average_score: number | null; score_distribution: (Distribution & { percentage: number | null })[]; items: ExamItem[] | null };
}>();

const filterForm = reactive({
    subject_id: props.filters.subject_id ?? '', category_id: props.filters.category_id ?? '',
    type: props.filters.type ?? '', jenjang: props.filters.jenjang ?? '',
    curriculum: props.filters.curriculum ?? '', cognitive_level: props.filters.cognitive_level ?? '',
    exam_id: props.filters.exam_id ?? '',
});
const exportUrl = computed(() => {
    const params = new URLSearchParams(Object.entries(filterForm).filter(([, value]) => value !== '' && value !== null).map(([key, value]) => [key, String(value)]));
    return `/penilaian/analisis/export?${params.toString()}`;
});
const maxQuestionCount = computed(() => Math.max(1, ...[...props.bank.by_type, ...props.bank.by_subject, ...props.bank.by_category, ...props.bank.by_curriculum, ...props.bank.by_cognitive_level].map((row) => row.count)));
const maxBandCount = computed(() => Math.max(1, ...(props.exam_results.score_distribution ?? []).map((row) => row.count)));

function applyFilters() {
    router.get('/penilaian/analisis', filterForm, { preserveState: true, preserveScroll: true, replace: true });
}
function labelType(type: string) {
    return ({ multiple_choice: 'Pilihan ganda', essay: 'Esai', true_false: 'Benar/Salah', matching: 'Menjodohkan' } as Record<string, string>)[type] ?? type;
}
</script>

<template>
    <Head title="Analisis Bank Soal dan Ujian" />
    <AppLayout>
        <main class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Assessment insight</p><h1 class="mt-1 text-2xl font-semibold text-slate-900">Analisis Bank Soal & Ujian</h1><p class="mt-1 text-sm text-slate-500">Ringkasan agregat sesuai cakupan sekolah, penugasan, dan izin berbagi Anda.</p></div>
                <a :href="exportUrl" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ekspor XLSX</a>
            </header>

            <form @submit.prevent="applyFilters" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="text-xs font-medium text-slate-600">Mata pelajaran<select v-model="filterForm.subject_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Semua mapel</option><option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option></select></label>
                <label class="text-xs font-medium text-slate-600">Kategori<select v-model="filterForm.category_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Semua kategori</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select></label>
                <label class="text-xs font-medium text-slate-600">Tipe soal<select v-model="filterForm.type" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Semua tipe</option><option value="multiple_choice">Pilihan ganda</option><option value="essay">Esai</option><option value="true_false">Benar/Salah</option><option value="matching">Menjodohkan</option></select></label>
                <label class="text-xs font-medium text-slate-600">Jenjang<select v-model="filterForm.jenjang" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Semua jenjang</option><option>SD</option><option>SMP</option><option>SMA</option></select></label>
                <label class="text-xs font-medium text-slate-600">Kurikulum<select v-model="filterForm.curriculum" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Semua kurikulum</option><option value="merdeka">Merdeka</option><option value="kbc">KBC</option><option value="both">Keduanya</option></select></label>
                <label class="text-xs font-medium text-slate-600">Level kognitif<select v-model="filterForm.cognitive_level" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Semua level</option><option value="L1">L1 · LOTS</option><option value="L2">L2 · MOTS</option><option value="L3">L3 · HOTS</option></select></label>
                <label class="text-xs font-medium text-slate-600 sm:col-span-2">Ujian untuk dianalisis<select v-model="filterForm.exam_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Pilih ujian</option><option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.title }} · {{ exam.status }} · {{ new Date(exam.starts_at).toLocaleDateString('id-ID') }}</option></select></label>
                <div class="flex items-end"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Terapkan filter</button></div>
            </form>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-xl border border-indigo-100 bg-indigo-50 p-5"><p class="text-sm text-indigo-700">Soal aktif sesuai filter</p><p class="mt-2 text-3xl font-semibold text-indigo-950">{{ bank.total }}</p></article>
                <article class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Attempt ujian</p><p class="mt-2 text-3xl font-semibold">{{ exam_results.exam ? exam_results.total_attempts : '—' }}</p><p class="mt-1 text-xs text-slate-500">Tanpa identitas siswa</p></article>
                <article class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Attempt dinilai</p><p class="mt-2 text-3xl font-semibold">{{ exam_results.available ? exam_results.graded_attempts : '—' }}</p></article>
                <article class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Rata-rata nilai</p><p class="mt-2 text-3xl font-semibold">{{ exam_results.available ? `${exam_results.average_score ?? '—'}` : '—' }}<span v-if="exam_results.available && exam_results.average_score !== null" class="ml-1 text-sm font-normal">/ 100</span></p></article>
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                <article v-for="chart in [
                    { title: 'Soal menurut tipe', rows: bank.by_type.map((x) => ({ ...x, label: labelType(x.label) })) },
                    { title: 'Soal menurut mata pelajaran', rows: bank.by_subject },
                    { title: 'Soal menurut kategori', rows: bank.by_category },
                    { title: 'Soal menurut kurikulum', rows: bank.by_curriculum },
                    { title: 'Soal menurut tingkat kognitif', rows: bank.by_cognitive_level },
                ]" :key="chart.title" class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold text-slate-900">{{ chart.title }}</h2>
                    <p v-if="!chart.rows.length" class="mt-4 text-sm text-slate-500">Belum ada data untuk filter ini.</p>
                    <div v-for="row in chart.rows" :key="row.label" class="mt-4"><div class="mb-1 flex justify-between gap-3 text-sm"><span class="truncate text-slate-600">{{ row.label }}</span><span class="font-semibold">{{ row.count }}</span></div><div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-indigo-500" :style="{ width: `${Math.max(3, row.count / maxQuestionCount * 100)}%` }" /></div></div>
                </article>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900">Distribusi nilai</h2><p v-if="exam_results.exam" class="mt-1 text-xs text-slate-500">{{ exam_results.exam.title }} · rentang memakai nilai 0–100</p></div></div>
                <p v-if="!exam_results.exam" class="mt-4 text-sm text-slate-500">Pilih ujian untuk melihat distribusi hasil.</p>
                <p v-else-if="!exam_results.available" class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Hasil dan analisis butir hanya tersedia setelah hasil ujian diterbitkan.</p>
                <p v-else-if="!exam_results.graded_attempts" class="mt-4 text-sm text-slate-500">Hasil sudah diterbitkan, tetapi belum ada attempt yang dapat dianalisis.</p>
                <div v-else class="mt-4 grid gap-3 sm:grid-cols-5"><div v-for="band in exam_results.score_distribution" :key="band.label" class="rounded-lg bg-slate-50 p-3"><div class="flex justify-between text-sm"><span>{{ band.label }}</span><b>{{ band.count }}</b></div><div class="mt-3 h-2 rounded bg-slate-200"><div class="h-2 rounded bg-emerald-500" :style="{ width: `${Math.max(3, band.count / maxBandCount * 100)}%` }" /></div><p class="mt-1 text-xs text-slate-500">{{ band.percentage ?? '—' }}%</p></div></div>
            </section>

            <section v-if="exam_results.available && exam_results.items" class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="font-semibold text-slate-900">Analisis per butir</h2><p class="mt-1 text-xs text-slate-500">Rasio benar dihitung dari jawaban objektif yang telah dinilai. Rata-rata poin memasukkan attempt tanpa skor butir sebagai nol. Tidak ada jawaban atau nama siswa yang ditampilkan.</p>
                <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-3 py-2">Butir</th><th class="px-3 py-2">Tipe</th><th class="px-3 py-2">Terjawab</th><th class="px-3 py-2">Benar</th><th class="px-3 py-2">Rata-rata poin</th></tr></thead><tbody><tr v-for="item in exam_results.items" :key="item.question_id" class="border-b last:border-0"><td class="max-w-lg whitespace-pre-wrap px-3 py-3">{{ item.prompt }}</td><td class="px-3 py-3">{{ labelType(item.type) }}</td><td class="px-3 py-3">{{ item.answered }}/{{ item.attempts }}</td><td class="px-3 py-3">{{ item.correct_rate === null ? 'N/A' : `${item.correct_count} (${item.correct_rate}%)` }}</td><td class="px-3 py-3">{{ item.average_points === null ? '—' : `${item.average_points} / ${item.max_points} (${item.average_percent}%)` }}</td></tr><tr v-if="!exam_results.items.length"><td colspan="5" class="px-3 py-5 text-center text-slate-500">Ujian ini belum memiliki butir soal.</td></tr></tbody></table></div>
            </section>

            <p class="text-xs leading-relaxed text-slate-500">Definisi: persentase distribusi = jumlah pada rentang ÷ seluruh attempt yang sudah dinilai × 100; rasio benar butir = jawaban benar ÷ jawaban objektif yang dinilai × 100; rata-rata poin = total poin butir ÷ seluruh attempt yang dinilai. Nilai statistik butir tidak membuka kunci jawaban.</p>
        </main>
    </AppLayout>
</template>
