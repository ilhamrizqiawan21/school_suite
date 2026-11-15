<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface RecapRow { teacher_id: number; name: string; nip: string | null; present: number; permitted: number; sick: number; absent: number }
const props = defineProps<{ month: string; rows: RecapRow[]; summary: { teachers: number; present: number; permitted: number; sick: number; absent: number } }>();

function changeMonth(event: Event) {
    const month = (event.target as HTMLInputElement).value;
    router.get('/akademik/absensi-guru/rekap', { month }, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Rekap Absensi Guru">
        <Head title="Rekap Absensi Guru" />
        <main class="view teacher-recap-page space-y-6">
            <section class="hero-card teacher-recap-hero">
                <div><p class="eyebrow">Administrasi akademik</p><h1>Rekap absensi guru</h1><p>Ringkasan kehadiran bulanan berdasarkan catatan sekolah.</p></div>
                <div class="flex flex-wrap gap-2"><Link class="secondary-button ui-button ui-button--secondary ui-button--md" href="/akademik/absensi-guru">Kembali ke absensi</Link><AppButton variant="secondary" icon="pdf" as="a" class="file-action-button file-action-button--pdf" target="_blank" rel="noopener noreferrer" :href="`/akademik/absensi-guru/rekap/pdf?month=${month}`">Unduh PDF</AppButton></div>
            </section>
            <section class="panel flex flex-wrap items-end justify-between gap-4 p-5">
                <label class="grid gap-1 text-sm font-medium text-slate-700">Bulan rekap<input class="min-h-10 rounded-lg border border-slate-300 bg-[var(--surface)] px-3 text-sm" type="month" :value="month" @change="changeMonth" /></label>
                <div class="text-sm text-slate-600">{{ summary.teachers }} guru · {{ summary.present + summary.permitted + summary.sick + summary.absent }} catatan</div>
            </section>
            <section class="ss-summary-grid" aria-label="Ringkasan kehadiran guru">
                <MetricSummaryCard
                    label="Hadir"
                    :value="summary.present"
                    tone="green"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    label="Izin"
                    :value="summary.permitted"
                    tone="blue"
                    icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                />
                <MetricSummaryCard
                    label="Sakit"
                    :value="summary.sick"
                    tone="amber"
                    icon="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                />
                <MetricSummaryCard
                    label="Alpha"
                    :value="summary.absent"
                    tone="rose"
                    icon="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </section>
            <section class="panel overflow-hidden">
                <div class="overflow-x-auto table-scroll" tabindex="0" role="region" aria-label="Rekap presensi guru, geser untuk melihat semua kolom"><table class="app-table w-full min-w-[640px] text-left text-sm"><thead class="bg-slate-50 text-meta uppercase tracking-wide text-slate-600"><tr><th scope="col" class="px-5 py-3">Guru</th><th scope="col" class="px-5 py-3 text-center">Hadir</th><th scope="col" class="px-5 py-3 text-center">Izin</th><th scope="col" class="px-5 py-3 text-center">Sakit</th><th scope="col" class="px-5 py-3 text-center">Alpha</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="row in rows" :key="row.teacher_id"><td class="px-5 py-4"><strong class="block font-semibold text-slate-900">{{ row.name }}</strong><small class="text-slate-600">NIP {{ row.nip || '—' }}</small></td><td class="px-5 py-4 text-center">{{ row.present }}</td><td class="px-5 py-4 text-center">{{ row.permitted }}</td><td class="px-5 py-4 text-center">{{ row.sick }}</td><td class="px-5 py-4 text-center">{{ row.absent }}</td></tr><tr v-if="!rows.length"><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-600">Belum ada catatan absensi pada bulan ini.</td></tr></tbody></table></div>
            </section>
        </main>
    </AppLayout>
</template>
<style scoped>
.teacher-recap-page { color: var(--ink); }
.teacher-recap-hero { min-height: 0; padding: 0 0 1.25rem; color: var(--ink); background: transparent; border: 0; border-radius: 0; box-shadow: none; }
.teacher-recap-hero h1 { color: var(--ink); font-size: var(--type-3xl); }
.teacher-recap-hero p { color: var(--ink-soft); }
.teacher-recap-hero .eyebrow { color: var(--primary); font-size: var(--type-meta); font-weight: var(--weight-semibold); letter-spacing: .03em; text-transform: none; }
.teacher-recap-page .grade-summary > div { border: 1px solid var(--line); border-radius: 12px; background: var(--surface); }
.teacher-recap-page input { color: var(--ink); background: var(--surface); border-color: var(--line-strong); }
.teacher-recap-page thead { color: var(--ink-soft); background: var(--surface-subtle); }
@media (max-width: 700px) { .teacher-recap-hero { align-items: flex-start; flex-direction: column; }.teacher-recap-hero > div:last-child { width: 100%; }.teacher-recap-hero a { flex: 1; text-align: center; } }
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
