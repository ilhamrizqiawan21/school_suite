<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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
</script>

<template>
    <AppLayout title="Kelas Daring">
        <Head title="Kelas Daring" />
        <main class="view space-y-6 online-classes-page">
            <section class="hero-card online-hero">
                <div>
                    <p class="eyebrow">{{ is_student ? 'Kelas saya' : 'Operasi akademik' }} · {{ term?.name ?? 'Belum ada semester aktif' }}</p>
                    <h1>{{ is_student ? 'Pertemuan daring' : 'Kelas & presensi daring' }}</h1>
                    <p>{{ is_student ? 'Lihat jadwal pertemuan kelas dan buka tautan saat waktunya belajar.' : 'Jadwalkan pertemuan, kelola tautan, status, dan kehadiran siswa.' }}</p>
                </div>
                <button v-if="can_manage" class="primary-button" type="button" @click="open = true">+ Pertemuan daring</button>
            </section>

            <section v-if="is_student" class="online-session-grid" aria-label="Daftar pertemuan daring">
                <article v-for="item in sessions" :key="item.id" class="online-session-card">
                    <div class="online-session-topline">
                        <span class="online-status" :class="`status-${item.status}`">{{ statusLabel[item.status] }}</span>
                        <span class="online-session-date">{{ formatDateTime(item.starts_at) }}</span>
                    </div>
                    <div class="online-session-content">
                        <p>{{ item.class_group?.name ?? student_class?.name ?? 'Kelas Anda' }}<template v-if="item.teaching_assignment?.subject?.name"> · {{ item.teaching_assignment.subject.name }}</template></p>
                        <h2>{{ item.title }}</h2>
                    </div>
                    <a v-if="item.meeting_url && item.status !== 'cancelled'" class="primary-button online-join" :href="item.meeting_url" target="_blank" rel="noopener noreferrer">
                        {{ item.status === 'live' ? 'Masuk kelas' : 'Buka tautan pertemuan' }} <span aria-hidden="true">↗</span>
                    </a>
                </article>
                <div v-if="sessions.length === 0" class="online-empty">
                    <span class="online-empty-mark" aria-hidden="true">◷</span>
                    <h2>Belum ada pertemuan daring</h2>
                    <p>Jadwal pertemuan untuk kelasmu akan muncul di sini.</p>
                    <Link href="/akademik/jadwal" class="secondary-button">Lihat jadwal pelajaran</Link>
                </div>
            </section>

            <section v-else class="panel online-table-wrap">
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Pertemuan</th><th>Kelas</th><th>Waktu</th><th>Status</th><th>Presensi</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <tr v-for="item in sessions" :key="item.id">
                                <td><strong>{{ item.title }}</strong><br><a :href="item.meeting_url" target="_blank" rel="noopener noreferrer">Buka tautan</a></td>
                                <td>{{ item.class_group?.name }}</td>
                                <td>{{ formatDateTime(item.starts_at) }}</td>
                                <td><select :value="item.status" aria-label="Ubah status pertemuan" @change="setStatus(item.id, ($event.target as HTMLSelectElement).value)"><option value="scheduled">Terjadwal</option><option value="live">Berlangsung</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option></select></td>
                                <td>{{ item.attendances_count }} tercatat</td>
                                <td><Link class="secondary-button" :href="`/akademik/kelas-daring/${item.id}/presensi`">Isi presensi</Link></td>
                            </tr>
                            <tr v-if="sessions.length === 0"><td colspan="6" class="empty-state">Belum ada pertemuan daring.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div v-if="open && can_manage" class="modal-backdrop" @click.self="open = false">
                <form class="modal-card" @submit.prevent="submit">
                    <div class="modal-header"><div><p class="eyebrow">Kelas daring</p><h2>Jadwalkan pertemuan</h2></div><button type="button" aria-label="Tutup" @click="open = false">×</button></div>
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
                    <div class="modal-actions"><button type="button" class="secondary-button" @click="open = false">Batal</button><button class="primary-button" :disabled="form.processing">Simpan jadwal</button></div>
                </form>
            </div>
        </main>
    </AppLayout>
</template>

<style scoped>
.online-hero { min-height: 132px; }
.online-session-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.online-session-card { display: flex; min-width: 0; min-height: 210px; flex-direction: column; padding: 20px; background: var(--surface); border: 1px solid var(--line); border-radius: 16px 16px 16px 5px; box-shadow: var(--shadow-sm); }
.online-session-topline { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 9px; }
.online-status { padding: 5px 9px; color: var(--primary); background: var(--primary-soft); border-radius: 999px; font-size: 11px; font-weight: 800; }
.status-live { color: var(--green); background: var(--green-soft); }
.status-completed { color: var(--ink-soft); background: var(--surface-strong); }
.status-cancelled { color: var(--red); background: var(--red-soft); }
.online-session-date { color: var(--ink-faint); font-size: 11px; }
.online-session-content { margin: 22px 0; }
.online-session-content p { margin: 0 0 7px; color: var(--ink-soft); font-size: 12px; }
.online-session-content h2 { margin: 0; color: var(--ink); font-size: 18px; font-weight: 800; line-height: 1.35; }
.online-join { display: inline-flex; width: fit-content; min-height: 42px; align-items: center; gap: 10px; margin-top: auto; text-decoration: none; }
.online-empty { grid-column: 1 / -1; display: grid; justify-items: center; padding: 52px 24px; text-align: center; background: var(--surface); border: 1px dashed var(--line-strong); border-radius: 18px; }
.online-empty-mark { display: grid; width: 46px; height: 46px; place-items: center; color: var(--primary); background: var(--primary-soft); border-radius: 14px; font-size: 23px; }
.online-empty h2 { margin: 15px 0 0; color: var(--ink); font-size: 17px; font-weight: 800; }
.online-empty p { margin: 6px 0 16px; color: var(--ink-soft); font-size: 13px; }
.online-table-wrap { overflow: hidden; }
.table-scroll { overflow-x: auto; }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .58); }
.modal-card { display: grid; width: min(680px, 100%); max-height: 90vh; gap: 1rem; overflow: auto; padding: 1.35rem; color: var(--ink); background: var(--surface); border: 1px solid var(--line); border-radius: 1rem; }
.modal-header { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.modal-header h2 { margin: .25rem 0 0; color: var(--ink); font-size: 1.2rem; font-weight: 800; }
.modal-header > button { color: var(--ink-soft); font-size: 1.6rem; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
.field { display: grid; gap: .35rem; color: var(--ink-soft); font-size: .78rem; font-weight: 700; }
.field input, .field select { min-width: 0; padding: .65rem .75rem; color: var(--ink); background: var(--surface-subtle); border: 1px solid var(--line-strong); border-radius: .65rem; }
.span-2 { grid-column: 1 / -1; }
.modal-actions { display: flex; justify-content: flex-end; gap: .65rem; }
.form-error { color: var(--red); font-size: .8rem; }
@media (max-width: 760px) { .online-session-grid { grid-template-columns: 1fr; }.form-grid { grid-template-columns: 1fr; }.span-2 { grid-column: auto; }.online-session-card { min-height: 0; } }
</style>
