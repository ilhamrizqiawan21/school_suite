<script setup lang="ts">
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
                <div class="flex flex-wrap gap-2"><Link class="secondary-button" href="/akademik/absensi-guru">Kembali ke absensi</Link><a class="primary-button" :href="`/akademik/absensi-guru/rekap/pdf?month=${month}`">Unduh PDF</a></div>
            </section>
            <section class="panel flex flex-wrap items-end justify-between gap-4 p-5">
                <label class="grid gap-1 text-sm font-medium text-slate-700">Bulan rekap<input class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm" type="month" :value="month" @change="changeMonth" /></label>
                <div class="text-sm text-slate-500">{{ summary.teachers }} guru · {{ summary.present + summary.permitted + summary.sick + summary.absent }} catatan</div>
            </section>
            <section class="grade-summary"><div><span>Hadir</span><strong>{{ summary.present }}</strong></div><div><span>Izin</span><strong>{{ summary.permitted }}</strong></div><div><span>Sakit</span><strong>{{ summary.sick }}</strong></div><div><span>Alpha</span><strong>{{ summary.absent }}</strong></div></section>
            <section class="panel overflow-hidden">
                <div class="overflow-x-auto table-scroll" tabindex="0" role="region" aria-label="Rekap presensi guru, geser untuk melihat semua kolom"><table class="app-table w-full min-w-[640px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th scope="col" class="px-5 py-3">Guru</th><th scope="col" class="px-5 py-3 text-center">Hadir</th><th scope="col" class="px-5 py-3 text-center">Izin</th><th scope="col" class="px-5 py-3 text-center">Sakit</th><th scope="col" class="px-5 py-3 text-center">Alpha</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="row in rows" :key="row.teacher_id"><td class="px-5 py-4"><strong class="block font-semibold text-slate-900">{{ row.name }}</strong><small class="text-slate-500">NIP {{ row.nip || '—' }}</small></td><td class="px-5 py-4 text-center">{{ row.present }}</td><td class="px-5 py-4 text-center">{{ row.permitted }}</td><td class="px-5 py-4 text-center">{{ row.sick }}</td><td class="px-5 py-4 text-center">{{ row.absent }}</td></tr><tr v-if="!rows.length"><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada catatan absensi pada bulan ini.</td></tr></tbody></table></div>
            </section>
        </main>
    </AppLayout>
</template>
<style scoped>
.teacher-recap-page { color: var(--ink); }
.teacher-recap-hero { min-height: 0; padding: 0 0 1.25rem; color: var(--ink); background: transparent; border: 0; border-radius: 0; box-shadow: none; }
.teacher-recap-hero h1 { color: var(--ink); font-size: clamp(1.7rem, 3vw, 2.2rem); }
.teacher-recap-hero p { color: var(--ink-soft); }
.teacher-recap-hero .eyebrow { color: var(--primary); font-size: .75rem; font-weight: 700; letter-spacing: .03em; text-transform: none; }
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
