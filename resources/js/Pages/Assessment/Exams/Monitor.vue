<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type SnapshotStudent = {
    enrollment_id: number;
    name: string;
    nis: string | null;
    status: 'online' | 'offline' | 'finished' | 'locked' | 'cheated' | 'not_started';
    answered: number;
    total_questions: number;
    violation_count: number;
    attempt_id?: number;
    expires_at?: string | null;
    last_heartbeat_at?: string | null;
};

const props = defineProps<{ exam: any; snapshot: { summary: Record<string, number>; students: SnapshotStudent[] }; server_now: string }>();
const search = ref('');
const status = ref('all');
const refreshing = ref(false);
let poller: number | undefined;

const filteredStudents = computed(() => props.snapshot.students.filter((student) => {
    const term = search.value.trim().toLowerCase();
    const matchesSearch = !term || `${student.name} ${student.nis ?? ''}`.toLowerCase().includes(term);
    return matchesSearch && (status.value === 'all' || student.status === status.value);
}));
const progress = (student: SnapshotStudent) => student.total_questions ? Math.round((student.answered / student.total_questions) * 100) : 0;
const statusLabel = (value: SnapshotStudent['status']) => ({ online: 'Online', offline: 'Offline', finished: 'Selesai', locked: 'Terkunci', cheated: 'Pelanggaran · nilai 0', not_started: 'Belum masuk' })[value];
const statusHint = (student: SnapshotStudent) => student.status === 'online' && student.last_heartbeat_at ? `Aktif ${new Date(student.last_heartbeat_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}` : student.status === 'finished' ? 'Jawaban sudah dikirim' : student.status === 'cheated' ? 'Ujian dihentikan otomatis' : 'Belum ada aktivitas terbaru';
const formatDate = (value?: string | null) => value ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
const refresh = () => {
    refreshing.value = true;
    router.reload({ only: ['snapshot', 'server_now'], onFinish: () => { refreshing.value = false; } });
};
const postAction = (url: string, data: any = {}) => router.post(url, data, { preserveScroll: true, onSuccess: refresh });
const unlock = (student: SnapshotStudent) => student.attempt_id && postAction(`/ujian/attempt/${student.attempt_id}/buka-kunci`);
const extend = (student: SnapshotStudent) => {
    if (! student.attempt_id) return;
    const minutes = window.prompt('Tambahkan berapa menit?', '10');
    if (minutes && Number(minutes) > 0) postAction(`/ujian/attempt/${student.attempt_id}/tambah-waktu`, { minutes: Number(minutes) });
};
const forceFinish = (student: SnapshotStudent) => {
    if (student.attempt_id && window.confirm(`Paksa sesi ${student.name} selesai? Jawaban yang sudah tersimpan akan dinilai.`)) postAction(`/ujian/attempt/${student.attempt_id}/paksa-selesai`);
};
onMounted(() => { poller = window.setInterval(refresh, 15000); });
onBeforeUnmount(() => { if (poller) window.clearInterval(poller); });
</script>

<template>
    <Head :title="`Monitor · ${exam.title}`" />
    <AppLayout>
        <main class="view space-y-6">
            <section class="hero-card monitor-hero">
                <div><p class="eyebrow">Asesmen · Pengawasan CBT</p><h1>{{ exam.title }}</h1><p>Pantau kehadiran, progres, pelanggaran, dan sisa waktu siswa dalam satu layar.</p></div>
                <div class="hero-actions"><Link class="secondary-button" :href="`/ujian/${exam.id}/hasil`">Lihat hasil</Link><button class="secondary-button" type="button" :disabled="refreshing" @click="refresh">{{ refreshing ? 'Memuat…' : 'Segarkan' }}</button></div>
            </section>
            <section class="monitor-meta"><span><strong>Jadwal</strong> {{ formatDate(exam.starts_at) }} — {{ formatDate(exam.ends_at) }}</span><span><strong>Durasi</strong> {{ exam.duration_minutes }} menit</span><span><strong>Batas pelanggaran</strong> {{ exam.max_violations }} kali</span></section>
            <section class="monitor-stats"><div class="stat stat-total"><span>Total siswa</span><strong>{{ snapshot.summary.total ?? 0 }}</strong><small>Peserta pada rombel ujian</small></div><div class="stat stat-online"><span>Online</span><strong>{{ snapshot.summary.online ?? 0 }}</strong><small>Heartbeat &lt; 45 detik</small></div><div class="stat stat-finished"><span>Selesai</span><strong>{{ snapshot.summary.finished ?? 0 }}</strong><small>Jawaban sudah dikirim</small></div><div class="stat stat-locked"><span>Terkunci</span><strong>{{ snapshot.summary.locked ?? 0 }}</strong><small>Melewati batas pelanggaran</small></div></section>
            <section class="panel monitor-panel">
                <div class="panel-heading"><div><p class="eyebrow">Status peserta</p><h2>Daftar siswa</h2><p>{{ filteredStudents.length }} dari {{ snapshot.students.length }} siswa ditampilkan · diperbarui {{ formatDate(server_now) }}</p></div><div class="filters"><input v-model="search" aria-label="Cari siswa" placeholder="Cari nama atau NIS" /><select v-model="status" aria-label="Filter status"><option value="all">Semua status</option><option value="online">Online</option><option value="offline">Offline</option><option value="not_started">Belum masuk</option><option value="locked">Terkunci</option><option value="finished">Selesai</option></select></div></div>
                <div class="student-table-wrap"><table class="student-table"><thead><tr><th>Siswa</th><th>Status</th><th>Progres</th><th>Pelanggaran</th><th>Sisa sesi</th><th>Aksi</th></tr></thead><tbody><tr v-for="student in filteredStudents" :key="student.enrollment_id"><td><strong>{{ student.name }}</strong><small>{{ student.nis || 'NIS belum diisi' }}</small></td><td><span class="status-pill" :class="`status-${student.status}`"><i />{{ statusLabel(student.status) }}</span><small>{{ statusHint(student) }}</small></td><td><div class="progress-copy"><b>{{ student.answered }}/{{ student.total_questions }}</b><span>{{ progress(student) }}%</span></div><div class="progress-track"><span :style="{ width: `${progress(student)}%` }" /></div></td><td><strong :class="{ danger: student.violation_count >= exam.max_violations }">{{ student.violation_count }}/{{ exam.max_violations }}</strong></td><td>{{ student.status === 'online' || student.status === 'offline' || student.status === 'locked' ? formatDate(student.expires_at) : '—' }}</td><td><div class="row-actions" v-if="student.attempt_id"><button v-if="student.status === 'locked'" class="action-button success" type="button" @click="unlock(student)">Buka kunci</button><button v-if="student.status === 'online' || student.status === 'offline' || student.status === 'locked'" class="action-button" type="button" @click="extend(student)">+ Waktu</button><button v-if="student.status === 'online' || student.status === 'offline' || student.status === 'locked'" class="action-button danger-button" type="button" @click="forceFinish(student)">Paksa selesai</button><span v-if="student.status === 'finished'" class="muted">Selesai</span></div><span v-else class="muted">Belum ada sesi</span></td></tr><tr v-if="!filteredStudents.length"><td colspan="6" class="empty-state">Tidak ada siswa yang sesuai filter.</td></tr></tbody></table></div>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.monitor-hero,.panel-heading,.hero-actions{display:flex;align-items:center;justify-content:space-between;gap:1rem}.hero-actions{flex-wrap:wrap}.monitor-meta{display:flex;gap:.75rem;flex-wrap:wrap;color:#475569;font-size:.82rem}.monitor-meta span{padding:.65rem .8rem;border:1px solid #dbe4f0;border-radius:.65rem;background:#fff}.monitor-meta strong{margin-right:.25rem;color:#172844}.monitor-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}.stat{display:grid;gap:.25rem;padding:1rem 1.15rem;border:1px solid #dbe4f0;border-top:3px solid #94a3b8;border-radius:.8rem;background:#fff}.stat span,.stat small,.panel-heading p,.student-table small{color:#64748b;font-size:.78rem}.stat strong{font-size:1.7rem}.stat-online{border-top-color:#16a34a}.stat-online strong{color:#15803d}.stat-finished{border-top-color:#2563eb}.stat-finished strong{color:#1d4ed8}.stat-locked{border-top-color:#dc2626}.stat-locked strong{color:#b91c1c}.monitor-panel{padding:1.2rem}.panel-heading{align-items:flex-end;padding-bottom:1rem;border-bottom:1px solid #e2e8f0}.panel-heading h2{margin:.25rem 0;font-size:1.1rem;font-weight:800}.panel-heading p{margin:0}.filters{display:flex;gap:.55rem;flex-wrap:wrap}.filters input,.filters select{min-height:40px;border:1px solid #cbd5e1;border-radius:.55rem;padding:.55rem .7rem;background:#fff;color:#172844}.student-table-wrap{overflow-x:auto}.student-table{width:100%;min-width:900px;border-collapse:collapse}.student-table th,.student-table td{padding:.85rem .65rem;border-bottom:1px solid #e2e8f0;text-align:left;vertical-align:middle;font-size:.82rem}.student-table th{color:#64748b;font-size:.72rem;letter-spacing:.03em;text-transform:uppercase}.student-table td strong,.student-table td small{display:block}.student-table td small{margin-top:.18rem}.status-pill{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .55rem;border-radius:999px;font-size:.72rem;font-weight:800}.status-pill i{width:7px;height:7px;border-radius:50%;background:currentColor}.status-online{background:#dcfce7;color:#15803d}.status-offline{background:#fef3c7;color:#a16207}.status-finished{background:#dbeafe;color:#1d4ed8}.status-locked{background:#fee2e2;color:#b91c1c}.status-not_started{background:#f1f5f9;color:#64748b}.progress-copy{display:flex;justify-content:space-between;gap:.5rem;margin-bottom:.3rem;font-size:.75rem}.progress-track{height:7px;min-width:120px;overflow:hidden;border-radius:999px;background:#e2e8f0}.progress-track span{display:block;height:100%;border-radius:inherit;background:#2563eb}.danger{color:#b91c1c}.row-actions{display:flex;gap:.35rem;flex-wrap:wrap}.action-button{min-height:30px;padding:.35rem .55rem;border:1px solid #cbd5e1;border-radius:.45rem;background:#fff;color:#334155;font-size:.72rem;font-weight:750;cursor:pointer}.action-button.success{border-color:#86efac;color:#15803d}.danger-button{border-color:#fecaca;color:#b91c1c}.muted{color:#94a3b8;font-size:.78rem}.empty-state{padding:2rem!important;text-align:center!important;color:#64748b}@media(max-width:900px){.monitor-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.monitor-hero,.panel-heading{align-items:stretch;flex-direction:column}.hero-actions,.filters{width:100%}.filters>*{flex:1;min-width:0}}@media(max-width:520px){.monitor-stats{grid-template-columns:1fr}.hero-actions>*{flex:1}.monitor-meta{display:grid;grid-template-columns:1fr}}
</style>
