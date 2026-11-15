<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface ClassOption {
    id: number;
    name: string;
    grade_level: number;
}

interface RecapRow {
    nis: string;
    name: string;
    present: number;
    sick: number;
    permitted: number;
    absent: number;
    total: number;
    attendance_rate: number;
}

const p = defineProps<{
    classes: ClassOption[];
    selected_class_id: string;
    from: string;
    to: string;
    rows: RecapRow[];
}>();

const c = ref(p.selected_class_id);
const f = ref(p.from);
const t = ref(p.to);

const apply = () => {
    const basePath = typeof window !== 'undefined' && window.location.pathname.startsWith('/kepsek/')
        ? '/kepsek/laporan/rekap-absensi'
        : '/akademik/presensi/rekap';
    router.get(basePath, { class_group_id: c.value, from: f.value, to: t.value }, { preserveState: true });
};

const exportUrl = () => `/akademik/presensi/rekap/export?class_group_id=${c.value}&from=${f.value}&to=${t.value}`;
</script>
<template>
    <AppLayout title="Rekap Absensi">
        <Head title="Rekap Absensi" />
        <div class="view space-y-6">
            <section class="page-heading recap-hero">
                <div>
                    <p class="eyebrow">Analitik kehadiran</p>
                    <h1>Rekap absensi siswa</h1>
                    <p>Tinjau kehadiran per rombel dan rentang tanggal, lalu unduh data spreadsheet.</p>
                </div>
                <AppButton variant="secondary" icon="excel" as="a" class="file-action-button file-action-button--excel" :href="exportUrl()">Unduh CSV</AppButton>
            </section>
            <section class="panel filter">
                <label>Rombel<select v-model="c"><option v-for="x in classes" :key="x.id" :value="String(x.id)">{{ x.name }}</option></select></label>
                <label>Dari<input v-model="f" type="date" /></label>
                <label>Sampai<input v-model="t" type="date" /></label>
                <AppButton variant="secondary" type="button" class="secondary-button" @click="apply">Terapkan</AppButton>
            </section>
            <section class="panel table-scroll" tabindex="0" role="region" aria-label="Rekap absensi siswa">
                <table class="app-table recap-table">
                    <thead>
                        <tr>
                            <th scope="col">NIS</th>
                            <th scope="col">Nama</th>
                            <th scope="col" class="num-col">Hadir</th>
                            <th scope="col" class="num-col">Sakit</th>
                            <th scope="col" class="num-col">Izin</th>
                            <th scope="col" class="num-col">Alpa</th>
                            <th scope="col" class="num-col">Total</th>
                            <th scope="col" class="num-col">Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in rows" :key="r.nis">
                            <td>{{ r.nis }}</td>
                            <td><strong>{{ r.name }}</strong></td>
                            <td class="num-col">{{ r.present }}</td>
                            <td class="num-col">{{ r.sick }}</td>
                            <td class="num-col">{{ r.permitted }}</td>
                            <td class="num-col">{{ r.absent }}</td>
                            <td class="num-col">{{ r.total }}</td>
                            <td class="num-col"><span class="rate">{{ r.attendance_rate }}%</span></td>
                        </tr>
                        <tr v-if="!rows.length">
                            <td colspan="8" class="empty">Belum ada data pada rentang ini.</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </AppLayout>
</template>
<style scoped>
.recap-hero,
.filter {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
.recap-hero {
    margin-bottom: 0;
}
.recap-hero h1 {
    margin: .2rem 0 .35rem;
    color: var(--ink);
    font-size: var(--type-3xl);
    font-weight: var(--weight-bold);
    letter-spacing: -.035em;
}
.recap-hero p:not(.eyebrow) {
    margin: 0;
    color: var(--ink-soft);
}
.recap-hero .eyebrow {
    margin: 0;
    color: var(--primary);
    font-size: var(--type-meta);
    font-weight: var(--weight-semibold);
    letter-spacing: .03em;
}
.filter {
    justify-content: flex-start;
    gap: .75rem;
    padding: 1rem;
    border-radius: 14px;
}
.filter label {
    display: grid;
    gap: .3rem;
    color: var(--ink-soft);
    font-size: var(--type-meta);
    font-weight: var(--weight-semibold);
}
.filter input,
.filter select {
    min-height: 44px;
    padding: .55rem .7rem;
    color: var(--ink);
    background: var(--surface);
    border: 1px solid var(--line-strong);
    border-radius: .6rem;
}
.recap-table {
    width: 100%;
    border-collapse: collapse;
}
.recap-table th,
.recap-table td {
    padding: .8rem 1rem;
    border-bottom: 1px solid var(--line);
    text-align: left;
    font-size: var(--type-meta);
}
.recap-table th {
    background: var(--surface-subtle);
    color: var(--ink-soft);
}
.recap-table .num-col {
    text-align: center;
    font-variant-numeric: tabular-nums;
}
.rate {
    display: inline-block;
    padding: .25rem .5rem;
    border-radius: 999px;
    background: var(--primary-soft);
    color: var(--primary);
    font-weight: var(--weight-bold);
}
.empty {
    text-align: center !important;
    color: var(--ink-faint);
}
@media (max-width: 760px) {
    .recap-hero,
    .filter {
        align-items: stretch;
        flex-direction: column;
    }
    .filter > * {
        width: 100%;
    }
    .recap-table {
        min-width: 700px;
    }
}
</style>
