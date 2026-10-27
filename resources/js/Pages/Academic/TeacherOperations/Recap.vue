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
        <main class="view space-y-6">
            <section class="hero-card">
                <div><p class="eyebrow">Administrasi akademik</p><h1>Rekap absensi guru</h1><p>Ringkasan kehadiran bulanan berdasarkan catatan sekolah.</p></div>
                <div class="flex flex-wrap gap-2"><Link class="secondary-button" href="/akademik/absensi-guru">Kembali ke absensi</Link><a class="primary-button" :href="`/akademik/absensi-guru/rekap/pdf?month=${month}`">Unduh PDF</a></div>
            </section>
            <section class="panel flex flex-wrap items-end justify-between gap-4 p-5">
                <label class="grid gap-1 text-sm font-medium text-slate-700">Bulan rekap<input class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm" type="month" :value="month" @change="changeMonth" /></label>
                <div class="text-sm text-slate-500">{{ summary.teachers }} guru · {{ summary.present + summary.permitted + summary.sick + summary.absent }} catatan</div>
            </section>
            <section class="grade-summary"><div><span>Hadir</span><strong>{{ summary.present }}</strong></div><div><span>Izin</span><strong>{{ summary.permitted }}</strong></div><div><span>Sakit</span><strong>{{ summary.sick }}</strong></div><div><span>Alpha</span><strong>{{ summary.absent }}</strong></div></section>
            <section class="panel overflow-hidden">
                <div class="overflow-x-auto"><table class="w-full min-w-[640px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Guru</th><th class="px-5 py-3 text-center">Hadir</th><th class="px-5 py-3 text-center">Izin</th><th class="px-5 py-3 text-center">Sakit</th><th class="px-5 py-3 text-center">Alpha</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="row in rows" :key="row.teacher_id"><td class="px-5 py-4"><strong class="block font-semibold text-slate-900">{{ row.name }}</strong><small class="text-slate-500">NIP {{ row.nip || '—' }}</small></td><td class="px-5 py-4 text-center">{{ row.present }}</td><td class="px-5 py-4 text-center">{{ row.permitted }}</td><td class="px-5 py-4 text-center">{{ row.sick }}</td><td class="px-5 py-4 text-center">{{ row.absent }}</td></tr><tr v-if="!rows.length"><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada catatan absensi pada bulan ini.</td></tr></tbody></table></div>
            </section>
        </main>
    </AppLayout>
</template>
