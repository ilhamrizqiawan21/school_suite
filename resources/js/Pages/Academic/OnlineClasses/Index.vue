<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface OnlineSession {
    id: number;
    title: string;
    meeting_url: string;
    starts_at: string;
    ends_at?: string | null;
    status: 'scheduled' | 'live' | 'completed' | 'cancelled';
    class_group?: { name: string };
    teaching_assignment?: { subject?: { name: string } } | null;
    attendances_count: number;
}

const props = defineProps<{
    term: { name: string } | null;
    classes: Array<{ id: number; name: string }>;
    assignments: Array<{ id: number; class_group_id: number; subject?: { name: string }; teacher?: { name: string } }>;
    sessions: OnlineSession[];
    can_manage: boolean;
    is_student: boolean;
    student_class?: { id: number; name: string } | null;
}>();

const open = ref(false);
const form = useForm({ class_group_id: '', teaching_assignment_id: '', title: '', provider: 'google_meet', meeting_url: '', starts_at: '', ends_at: '' });
const submit = () => form.post('/akademik/kelas-daring', { onSuccess: () => { open.value = false; form.reset(); } });
const setStatus = (id: number, status: string) => useForm({ status }).put(`/akademik/kelas-daring/${id}/status`);

const formatDateTime = (value: string) => new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeStyle: 'short' }).format(new Date(value));
const statusLabel: Record<OnlineSession['status'], string> = { scheduled: 'Terjadwal', live: 'Sedang berlangsung', completed: 'Selesai', cancelled: 'Dibatalkan' };

const studentStatusFilter = ref<'all' | 'live' | 'scheduled' | 'completed'>('all');
const studentStats = computed(() => {
    const items = props.sessions;
    return {
        total: items.length,
        live: items.filter((item) => item.status === 'live').length,
        scheduled: items.filter((item) => item.status === 'scheduled').length,
        completed: items.filter((item) => item.status === 'completed').length,
    };
});
const filteredStudentSessions = computed(() => {
    if (studentStatusFilter.value === 'all') return props.sessions;
    return props.sessions.filter((item) => item.status === studentStatusFilter.value);
});
</script>

<template>
    <AppLayout title="Kelas Daring">
        <Head title="Kelas Daring" />
        <main class="view space-y-6 online-classes-page">
            <section class="hero-card online-hero" :class="{ 'student-online-hero': is_student }">
                <div>
                    <p class="eyebrow">{{ is_student ? 'Kelas saya' : 'Operasi akademik' }} · {{ term?.name ?? 'Belum ada semester aktif' }}</p>
                    <h1>{{ is_student ? 'Pertemuan daring' : 'Kelas & presensi daring' }}</h1>
                    <p>{{ is_student ? 'Lihat jadwal pertemuan kelas dan buka tautan saat waktunya belajar.' : 'Jadwalkan pertemuan, kelola tautan, status, dan kehadiran siswa.' }}</p>
                </div>
                <div class="hero-actions">
                    <Link v-if="is_student" href="/akademik/jadwal" class="ghost-button ui-button ui-button--secondary ui-button--md">Jadwal saya</Link>
                    <AppButton variant="primary" v-if="can_manage" class="primary-button" type="button" @click="open = true">+ Pertemuan daring</AppButton>
                </div>
            </section>

            <section v-if="is_student" class="ss-summary-grid" aria-label="Ringkasan pertemuan daring">
                <MetricSummaryCard
                    label="Total Pertemuan"
                    :value="studentStats.total"
                    tone="blue"
                    icon="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                />
                <MetricSummaryCard
                    label="Sedang Berlangsung"
                    :value="studentStats.live"
                    tone="green"
                    icon="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"
                />
                <MetricSummaryCard
                    label="Terjadwal"
                    :value="studentStats.scheduled"
                    tone="amber"
                    icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
                <MetricSummaryCard
                    label="Selesai"
                    :value="studentStats.completed"
                    tone="violet"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </section>

            <section v-if="is_student" class="online-filter-pills" aria-label="Filter status pertemuan">
                <button
                    type="button"
                    class="online-filter-pill"
                    :class="{ 'is-active': studentStatusFilter === 'all' }"
                    @click="studentStatusFilter = 'all'"
                >
                    Semua <b>{{ studentStats.total }}</b>
                </button>
                <button
                    type="button"
                    class="online-filter-pill"
                    :class="{ 'is-active': studentStatusFilter === 'live' }"
                    @click="studentStatusFilter = 'live'"
                >
                    Sedang Berlangsung <b>{{ studentStats.live }}</b>
                </button>
                <button
                    type="button"
                    class="online-filter-pill"
                    :class="{ 'is-active': studentStatusFilter === 'scheduled' }"
                    @click="studentStatusFilter = 'scheduled'"
                >
                    Terjadwal <b>{{ studentStats.scheduled }}</b>
                </button>
                <button
                    type="button"
                    class="online-filter-pill"
                    :class="{ 'is-active': studentStatusFilter === 'completed' }"
                    @click="studentStatusFilter = 'completed'"
                >
                    Selesai <b>{{ studentStats.completed }}</b>
                </button>
            </section>

            <section v-if="is_student" class="online-session-grid" aria-label="Daftar pertemuan daring">
                <article v-for="item in filteredStudentSessions" :key="item.id" class="online-session-card">
                    <div class="online-session-topline">
                        <span class="online-status" :class="`status-${item.status}`">{{ statusLabel[item.status] }}</span>
                        <span class="online-session-date">{{ formatDateTime(item.starts_at) }}</span>
                    </div>
                    <div class="online-session-content">
                        <p>{{ item.class_group?.name ?? student_class?.name ?? 'Kelas Anda' }}<template v-if="item.teaching_assignment?.subject?.name"> · {{ item.teaching_assignment.subject.name }}</template></p>
                        <h2>{{ item.title }}</h2>
                    </div>
                    <AppButton variant="primary" as="a" v-if="item.meeting_url && item.status !== 'cancelled'" class="primary-button online-join" :href="item.meeting_url" target="_blank" rel="noopener noreferrer">
                        {{ item.status === 'live' ? 'Masuk kelas' : 'Buka tautan pertemuan' }} <span aria-hidden="true">↗</span>
                    </AppButton>
                </article>
                <div v-if="filteredStudentSessions.length === 0" class="online-empty">
                    <span class="online-empty-mark" aria-hidden="true">◷</span>
                    <h2>{{ sessions.length === 0 ? 'Belum ada pertemuan daring' : 'Tidak ada pertemuan untuk status ini' }}</h2>
                    <p>{{ sessions.length === 0 ? 'Jadwal pertemuan untuk kelasmu akan muncul di sini.' : 'Pilih filter "Semua" untuk melihat seluruh pertemuan daring.' }}</p>
                    <Link v-if="sessions.length === 0" href="/akademik/jadwal" class="secondary-button ui-button ui-button--secondary ui-button--md">Lihat jadwal pelajaran</Link>
                    <AppButton variant="secondary" v-else type="button" class="secondary-button" @click="studentStatusFilter = 'all'">Tampilkan semua</AppButton>
                </div>
            </section>

            <section v-else class="panel online-table-wrap">
                <div class="online-list-heading"><h2>Daftar pertemuan</h2><span>{{ sessions.length }} sesi</span></div>
                <div class="table-scroll" tabindex="0" role="region" aria-label="Tabel data">
                    <table class="app-table data-table">
                        <thead><tr><th scope="col">Pertemuan</th><th scope="col">Kelas</th><th scope="col">Waktu</th><th scope="col">Status</th><th scope="col">Presensi</th><th scope="col">Aksi</th></tr></thead>
                        <tbody>
                            <tr v-for="item in sessions" :key="item.id">
                                <td><strong>{{ item.title }}</strong><br><a :href="item.meeting_url" target="_blank" rel="noopener noreferrer">Buka tautan</a></td>
                                <td>{{ item.class_group?.name }}</td>
                                <td>{{ formatDateTime(item.starts_at) }}</td>
                                <td><select :value="item.status" aria-label="Ubah status pertemuan" @change="setStatus(item.id, ($event.target as HTMLSelectElement).value)"><option value="scheduled">Terjadwal</option><option value="live">Berlangsung</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option></select></td>
                                <td>{{ item.attendances_count }} tercatat</td>
                                <td><Link class="secondary-button ui-button ui-button--secondary ui-button--md" :href="`/akademik/kelas-daring/${item.id}/presensi`">Isi presensi</Link></td>
                            </tr>
                            <tr v-if="sessions.length === 0"><td colspan="6" class="empty-state">Belum ada pertemuan daring.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div v-if="open && can_manage" role="dialog" aria-modal="true" aria-labelledby="online-session-title" class="modal-backdrop" @click.self="open = false" @keydown.esc="open = false">
                <form class="modal-card" @submit.prevent="submit">
                    <div class="modal-header"><div><p class="eyebrow">Kelas daring</p><h2 id="online-session-title">Jadwalkan pertemuan</h2></div><AppButton variant="secondary" type="button" aria-label="Tutup formulir pertemuan" @click="open = false">×</AppButton></div>
                    <div class="form-grid">
                        <label class="field span-2">Judul<input v-model="form.title" required></label>
                        <label class="field">Kelas<select v-model="form.class_group_id" required><option value="">Pilih kelas</option><option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option></select></label>
                        <label class="field">Mata pelajaran<select v-model="form.teaching_assignment_id"><option value="">Umum</option><option v-for="a in assignments.filter(x => String(x.class_group_id) === String(form.class_group_id))" :key="a.id" :value="a.id">{{ a.subject?.name }} — {{ a.teacher?.name }}</option></select></label>
                        <label class="field">Platform<select v-model="form.provider"><option value="google_meet">Google Meet</option><option value="zoom">Zoom</option><option value="microsoft_teams">Microsoft Teams</option><option value="other">Lainnya</option></select></label>
                        <label class="field">Tautan<input v-model="form.meeting_url" type="url" required></label>
                        <label class="field">Mulai<input v-model="form.starts_at" type="datetime-local" required></label>
                        <label class="field">Selesai<input v-model="form.ends_at" type="datetime-local"></label>
                    </div>
                    <p v-if="Object.keys(form.errors).length" class="form-error">Periksa kembali data pertemuan.</p>
                    <div class="modal-actions"><AppButton variant="secondary" type="button" class="secondary-button" @click="open = false">Batal</AppButton><AppButton variant="primary" :loading="form.processing" type="submit" class="primary-button" :disabled="form.processing">Simpan jadwal</AppButton></div>
                </form>
            </div>
        </main>
    </AppLayout>
</template>

<style scoped>
.online-hero { min-height: 0; flex-wrap: wrap; align-items: center; gap: 1rem 1.5rem; padding: 1.35rem 1.5rem; color: var(--ink); }
.online-hero > div { flex: 1 1 20rem; min-width: 0; }
.student-online-hero > div { padding-left: .75rem; }
.online-hero h1 { color: var(--ink); font-size: var(--type-3xl); overflow-wrap: anywhere; }
.online-hero p { color: var(--ink-soft); }
.online-hero .eyebrow { color: var(--primary); font-size: var(--type-meta); font-weight: var(--weight-semibold); letter-spacing: .03em; text-transform: none; }
@media (max-width: 560px) { .online-hero { padding: 1.2rem; } .student-online-hero > div { padding-left: .5rem; } .online-hero .primary-button { max-width: 100%; } }
.online-list-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--line); }
.online-list-heading h2 { margin: 0; color: var(--ink); font-size: var(--type-md); }
.online-list-heading span { padding: .25rem .65rem; color: var(--primary); background: var(--primary-soft); border-radius: 8px; font-size: var(--type-meta); }
.online-session-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.online-session-card { display: flex; min-width: 0; min-height: 210px; flex-direction: column; padding: 20px; background: var(--surface); border: 1px solid var(--line); border-radius: 16px 16px 16px 5px; box-shadow: var(--shadow-sm); }
.online-session-topline { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 9px; }
.online-status { padding: 5px 9px; color: var(--primary); background: var(--primary-soft); border-radius: 999px; font-size: var(--type-meta); font-weight: var(--weight-bold); }
.status-live { color: var(--green); background: var(--green-soft); }
.status-completed { color: var(--ink-soft); background: var(--surface-strong); }
.status-cancelled { color: var(--red); background: var(--red-soft); }
.online-session-date { color: var(--ink-faint); font-size: var(--type-meta); }
.online-session-content { margin: 22px 0; }
.online-session-content p { margin: 0 0 7px; color: var(--ink-soft); font-size: var(--type-meta); }
.online-session-content h2 { margin: 0; color: var(--ink); font-size: var(--type-section); font-weight: var(--weight-bold); line-height: 1.35; }
.online-join { width: fit-content; margin-top: auto; }
.online-empty { grid-column: 1 / -1; display: grid; justify-items: center; padding: 52px 24px; text-align: center; background: var(--surface); border: 1px dashed var(--line-strong); border-radius: 18px; }
.online-empty-mark { display: grid; width: 46px; height: 46px; place-items: center; color: var(--primary); background: var(--primary-soft); border-radius: 14px; font-size: 23px; }
.online-empty h2 { margin: 15px 0 0; color: var(--ink); font-size: 17px; font-weight: var(--weight-bold); }
.online-empty p { margin: 6px 0 16px; color: var(--ink-soft); font-size: var(--type-sm); }
.online-table-wrap { overflow: hidden; }
.table-scroll { overflow-x: auto; }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(19 32 51 / .45); backdrop-filter: blur(3px); }
.modal-card { display: grid; align-content: start; width: min(560px, 100%); max-height: min(90dvh, 860px); height: auto; gap: 1rem; overflow: auto; padding: clamp(18px, 3vw, 28px); border: 1px solid var(--line); border-radius: 16px; color: var(--ink); background: var(--surface); box-shadow: 0 24px 80px rgb(15 23 42 / 25%); }
.modal-header { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.modal-header h2 { margin: .25rem 0 0; color: var(--ink); font-size: 1.2rem; font-weight: var(--weight-bold); }
.modal-header > button { display: grid; place-items: center; width: 44px; height: 44px; color: var(--ink-soft); font-size: 1.6rem; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
.field { display: grid; gap: .35rem; color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.field input, .field select { min-width: 0; padding: .65rem .75rem; color: var(--ink); background: var(--surface-subtle); border: 1px solid var(--line-strong); border-radius: .65rem; }
.span-2 { grid-column: 1 / -1; }
.modal-actions { display: flex; justify-content: flex-end; gap: .65rem; }
.form-error { color: var(--red); font-size: var(--type-meta); }
@media (max-width: 760px) { .online-session-grid { grid-template-columns: 1fr; }.form-grid { grid-template-columns: 1fr; }.span-2 { grid-column: auto; }.online-session-card { min-height: 0; }.modal-backdrop { padding: .75rem; }.modal-card { max-height: 92dvh; border-radius: 16px; } }
/* Center schedule metadata, status, attendance, and actions in the session table. */
.data-table th:nth-child(n+2), .data-table td:nth-child(n+2) { text-align: center; }
.hero-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.grade-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
.summary-card { display: grid; gap: 4px; padding: 14px 16px; border: 1px solid var(--line); border-radius: 16px; background: var(--surface); }
.summary-card span { color: var(--ink-3); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.summary-card strong { color: var(--ink); font-size: 24px; line-height: 1.15; letter-spacing: -.03em; }
.online-filter-pills { display: flex; flex-wrap: wrap; gap: 8px; }
.online-filter-pill { display: inline-flex; align-items: center; gap: 7px; min-height: 36px; padding: 6px 13px; border-radius: 999px; border: 1px solid var(--line); background: var(--surface); color: var(--ink-2); font-size: var(--type-sm); font-weight: var(--weight-bold); cursor: pointer; transition: all .15s ease; }
.online-filter-pill:hover { border-color: #c6dbce; color: var(--ink); }
.online-filter-pill.is-active { border-color: #155e43; background: #edf6f0; color: #155e43; }
.online-filter-pill b { display: inline-grid; place-items: center; min-width: 20px; height: 20px; padding: 0 5px; border-radius: 999px; background: rgba(15, 23, 42, .07); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.online-filter-pill.is-active b { background: #155e43; color: #fff; }
@media (max-width: 760px) { .grade-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>
