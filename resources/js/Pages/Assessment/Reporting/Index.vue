<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    active_term: any;
    classes: any[];
    selected_class_id: number | null;
    metrics: any;
    trends: any[];
    teachers: any[];
}>();

const classId = ref(String(props.selected_class_id ?? props.classes[0]?.id ?? ''));
const url = (type: string) => classId.value ? `/laporan/${type}?class_group_id=${classId.value}` : undefined;
</script>
<template>
    <Head title="Pusat Laporan" />
    <AppLayout title="Pusat Laporan">
        <div class="view space-y-6 reporting-page">
            <header class="page-heading reporting-hero">
                <div>
                    <p class="eyebrow">Ringkasan pimpinan · {{ active_term?.name ?? 'Belum ada semester aktif' }}</p>
                    <h1>Pelaporan &amp; ekspor</h1>
                    <p>Ringkasan operasional sekolah dan ekspor data berbasis semester aktif.</p>
                </div>
                <label class="class-picker">
                    Rombongan belajar
                    <select v-model="classId" :disabled="!classes.length" aria-label="Pilih rombongan belajar">
                        <option v-if="!classes.length" value="">Belum ada rombel</option>
                        <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                    </select>
                </label>
            </header>

            <section class="ss-summary-grid" aria-label="Ringkasan operasional">
                <MetricSummaryCard label="Siswa aktif" :value="metrics.students" tone="blue" icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                <MetricSummaryCard label="Kehadiran" :value="`${metrics.attendance_rate}%`" tone="green" icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                <MetricSummaryCard label="Rerata nilai" :value="metrics.average_score" tone="amber" icon="M9 17v-2m3 2v-4m3 4v-6m4 10H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14z" />
                <MetricSummaryCard label="Tugas selesai" :value="`${metrics.assignment_completion}%`" tone="violet" icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </section>

            <section class="panel export-panel">
                <h2>Ekspor rombel</h2>
                <div class="exports">
                    <AppButton variant="secondary" icon="excel" keep-label as="a" class="file-action-button file-action-button--excel" :disabled="!classId" :href="url('nilai')">Nilai</AppButton>
                    <AppButton variant="secondary" icon="excel" keep-label as="a" class="file-action-button file-action-button--excel" :disabled="!classId" :href="url('presensi')">Presensi</AppButton>
                    <AppButton variant="secondary" icon="excel" keep-label as="a" class="file-action-button file-action-button--excel" :disabled="!classId" :href="url('tugas')">Tugas</AppButton>
                    <AppButton variant="secondary" icon="excel" keep-label as="a" class="file-action-button file-action-button--excel" :disabled="!classId" :href="url('sikap')">Sikap</AppButton>
                </div>
            </section>

            <div class="content-grid two-columns">
                <section class="panel chart">
                    <h2>Tren kehadiran bulanan</h2>
                    <div v-for="t in trends" :key="t.month" class="bar">
                        <span>{{ t.month }}</span>
                        <i><b :style="{ width: `${t.rate}%` }"></b></i>
                        <strong>{{ t.rate }}%</strong>
                    </div>
                    <p v-if="!trends.length" class="empty-note">Belum ada data tren.</p>
                </section>
                <section class="panel coverage">
                    <h2>Cakupan wali kelas</h2>
                    <strong>{{ metrics.homeroom_coverage }}%</strong>
                    <p>Persentase rombel yang telah memiliki wali kelas.</p>
                </section>
            </div>

            <section class="panel table-wrap table-scroll" tabindex="0" role="region" aria-label="Aktivitas operasional guru">
                <div class="table-title">
                    <h2>Aktivitas operasional guru</h2>
                    <p>Bukan ranking kinerja; angka digunakan untuk mendeteksi data yang belum lengkap.</p>
                </div>
                <table class="app-table data-table">
                    <thead>
                        <tr>
                            <th scope="col">Guru</th>
                            <th scope="col" class="num-col">Penugasan</th>
                            <th scope="col" class="num-col">Sesi</th>
                            <th scope="col" class="num-col">Jurnal</th>
                            <th scope="col" class="num-col">Kelengkapan</th>
                            <th scope="col" class="num-col">Materi</th>
                            <th scope="col" class="num-col">Tugas</th>
                            <th scope="col" class="num-col">Ujian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in teachers" :key="t.id">
                            <td><strong>{{ t.name }}</strong></td>
                            <td class="num-col">{{ t.assignments }}</td>
                            <td class="num-col">{{ t.sessions }}</td>
                            <td class="num-col">{{ t.journals }}</td>
                            <td class="num-col">{{ t.journal_rate }}%</td>
                            <td class="num-col">{{ t.materials }}</td>
                            <td class="num-col">{{ t.tasks }}</td>
                            <td class="num-col">{{ t.exams }}</td>
                        </tr>
                        <tr v-if="!teachers.length">
                            <td colspan="8" class="empty-row">Belum ada data aktivitas operasional guru pada semester aktif.</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </AppLayout>
</template>
<style scoped>
.reporting-page { max-width: 1440px; margin-inline: auto; }
.reporting-hero { margin-bottom: 0; }
.reporting-hero .eyebrow { margin: 0; color: var(--primary); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.reporting-hero h1 { margin: .25rem 0; color: var(--ink); font-size: var(--type-3xl); font-weight: var(--weight-bold); letter-spacing: -.035em; }
.reporting-hero > div > p:last-child { max-width: 62ch; margin: 0; color: var(--ink-soft); font-size: var(--type-sm); line-height: 1.55; }
.class-picker { display: grid; gap: .35rem; min-width: 210px; color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.reporting-hero select { min-height: 42px; min-width: 180px; padding: .55rem .75rem; border: 1px solid var(--line-strong); border-radius: .7rem; background: var(--surface); color: var(--ink); }
.export-panel, .chart, .coverage { padding: 1.25rem; }
.export-panel h2, .chart h2, .coverage h2, .table-title h2 { font-weight: var(--weight-bold); color: var(--ink); }
.exports { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: 1rem; }
.exports a[aria-disabled='true'] { opacity: .52; cursor: not-allowed; pointer-events: none; }
.bar { display: grid; grid-template-columns: 75px 1fr 50px; gap: .6rem; align-items: center; margin-top: .8rem; color: var(--ink); }
.bar i { height: .55rem; background: var(--surface-strong); border-radius: 1rem; overflow: hidden; }
.bar b { display: block; height: 100%; background: var(--primary); }
.coverage > strong { display: block; margin-top: 1rem; font-size: 2.5rem; color: var(--primary); font-variant-numeric: tabular-nums; }
.coverage p, .empty-note { margin-top: .5rem; color: var(--ink-soft); font-size: var(--type-sm); }
.table-wrap { overflow: auto; }
.table-title { padding: 1rem 1.25rem; }
.table-title p { color: var(--ink-soft); font-size: var(--type-meta); }
.data-table { min-width: 780px; }
.data-table .num-col { text-align: center; font-variant-numeric: tabular-nums; }
.empty-row { text-align: center; padding: 2rem 1rem; color: var(--ink-soft); }
@media (max-width: 760px) {
    .reporting-hero select { width: 100%; }
}
</style>
