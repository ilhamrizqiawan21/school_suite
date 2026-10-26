<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';

interface ClassItem { id: number; name: string; grade_level: number }
interface Announcement { id: number; title: string; body: string; audience_role?: string | null; attachment_name?: string | null; published_at: string; expires_at?: string | null; author: { name: string }; class_group?: { name: string } | null }
interface CalendarEvent { id: number; title: string; description?: string | null; event_type: string; starts_at: string; ends_at?: string | null; audience_role?: string | null; creator: { name: string }; class_group?: { name: string } | null }
interface NotificationItem { id: number; title: string; body?: string | null; action_url?: string | null; read_at?: string | null; created_at: string }

const props = defineProps<{
    announcements: Announcement[];
    events: CalendarEvent[];
    notifications: NotificationItem[];
    classes: ClassItem[];
    active_tab: string;
    can_manage: boolean;
    can_chat: boolean;
    can_whatsapp: boolean;
}>();

const tab = ref(['announcements', 'calendar', 'notifications'].includes(props.active_tab) ? props.active_tab : 'announcements');
const showAnnouncementForm = ref(false);
const showEventForm = ref(false);
const unreadCount = computed(() => props.notifications.filter((item) => !item.read_at).length);

const audienceOptions = [
    { value: '', label: 'Semua warga sekolah' },
    { value: 'principal', label: 'Kepala sekolah' },
    { value: 'teacher', label: 'Guru' },
    { value: 'homeroom', label: 'Wali kelas' },
    { value: 'student', label: 'Siswa' },
    { value: 'parent', label: 'Orang tua' },
];

const announcementForm = useForm({ title: '', body: '', audience_role: '', class_group_id: '', expires_at: '', attachment: null as File | null });
const eventForm = useForm({ title: '', description: '', event_type: 'academic', starts_at: '', ends_at: '', audience_role: '', class_group_id: '' });

const formatDate = (value?: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : '—';

const handleAttachment = (event: Event) => {
    announcementForm.attachment = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const submitAnnouncement = () => announcementForm.post('/komunikasi/pengumuman', {
    forceFormData: true,
    onSuccess: () => { announcementForm.reset(); showAnnouncementForm.value = false; },
});

const submitEvent = () => eventForm.post('/komunikasi/kalender', {
    onSuccess: () => { eventForm.reset(); showEventForm.value = false; },
});

const remove = (url: string) => {
    if (window.confirm('Hapus data ini?')) router.delete(url, { preserveScroll: true });
};

const markRead = (notification: NotificationItem) => {
    if (!notification.read_at) router.post(`/komunikasi/notifikasi/${notification.id}/baca`, {}, { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Komunikasi">
        <Head title="Komunikasi Sekolah" />
        <main class="view space-y-6">
            <section class="hero-card communication-hero">
                <div>
                    <p class="eyebrow">Pusat informasi sekolah</p>
                    <h1>Komunikasi & kalender</h1>
                    <p>Pengumuman terarah, agenda akademik, dan notifikasi pribadi dalam satu ruang kerja.</p>
                </div>
                <div class="hero-actions">
                    <Link v-if="can_chat" class="secondary-button" href="/komunikasi/chat">Chat kelas</Link>
                    <Link v-if="can_whatsapp" class="secondary-button" href="/komunikasi/whatsapp">WhatsApp</Link>
                    <button v-if="can_manage" class="secondary-button" @click="showEventForm = true">Tambah agenda</button>
                    <button v-if="can_manage" class="primary-button" @click="showAnnouncementForm = true">Buat pengumuman</button>
                </div>
            </section>

            <section class="grade-summary">
                <div><span>Pengumuman</span><strong>{{ announcements.length }}</strong><small>masih aktif</small></div>
                <div><span>Agenda</span><strong>{{ events.length }}</strong><small>periode berjalan</small></div>
                <div><span>Belum dibaca</span><strong>{{ unreadCount }}</strong><small>notifikasi pribadi</small></div>
                <div><span>Jangkauan</span><strong>{{ classes.length }}</strong><small>rombel semester aktif</small></div>
            </section>

            <section class="panel overflow-hidden">
                <div class="comm-tabs">
                    <button :class="{ active: tab === 'announcements' }" @click="tab = 'announcements'">Pengumuman</button>
                    <button :class="{ active: tab === 'calendar' }" @click="tab = 'calendar'">Kalender</button>
                    <button :class="{ active: tab === 'notifications' }" @click="tab = 'notifications'">Notifikasi <span v-if="unreadCount">{{ unreadCount }}</span></button>
                </div>

                <div v-if="tab === 'announcements'" class="comm-list">
                    <article v-for="item in announcements" :key="item.id" class="announcement-card">
                        <div class="announcement-date"><strong>{{ new Date(item.published_at).getDate() }}</strong><span>{{ new Intl.DateTimeFormat('id-ID', { month: 'short' }).format(new Date(item.published_at)) }}</span></div>
                        <div class="comm-body">
                            <p class="eyebrow">{{ item.class_group?.name || item.audience_role || 'Semua warga sekolah' }}</p>
                            <h2>{{ item.title }}</h2>
                            <p>{{ item.body }}</p>
                            <small>{{ item.author.name }} · {{ formatDate(item.published_at) }}</small>
                        </div>
                        <div class="comm-actions">
                            <a v-if="item.attachment_name" class="secondary-button" :href="`/komunikasi/pengumuman/${item.id}/lampiran`">Lampiran</a>
                            <button v-if="can_manage" class="danger-link" @click="remove(`/komunikasi/pengumuman/${item.id}`)">Hapus</button>
                        </div>
                    </article>
                    <div v-if="!announcements.length" class="empty-state"><strong>Belum ada pengumuman</strong><p>Informasi terbaru sekolah akan tampil di sini.</p></div>
                </div>

                <div v-else-if="tab === 'calendar'" class="comm-list timeline-list">
                    <article v-for="event in events" :key="event.id" class="timeline-event">
                        <div class="timeline-dot" :data-type="event.event_type"></div>
                        <div class="comm-body">
                            <p class="eyebrow">{{ event.event_type }} · {{ event.class_group?.name || event.audience_role || 'Semua warga sekolah' }}</p>
                            <h2>{{ event.title }}</h2>
                            <p>{{ event.description || 'Tidak ada keterangan tambahan.' }}</p>
                            <small>{{ formatDate(event.starts_at) }}<template v-if="event.ends_at"> — {{ formatDate(event.ends_at) }}</template></small>
                        </div>
                        <button v-if="can_manage" class="danger-link" @click="remove(`/komunikasi/kalender/${event.id}`)">Hapus</button>
                    </article>
                    <div v-if="!events.length" class="empty-state"><strong>Belum ada agenda</strong><p>Agenda akademik akan tampil secara kronologis.</p></div>
                </div>

                <div v-else class="comm-list">
                    <div class="notification-toolbar"><span>{{ unreadCount }} belum dibaca</span><button v-if="unreadCount" class="text-button" @click="router.post('/komunikasi/notifikasi/baca-semua')">Tandai semua dibaca</button></div>
                    <button v-for="item in notifications" :key="item.id" class="notification-item" :class="{ unread: !item.read_at }" @click="markRead(item)">
                        <span class="notification-dot"></span><span><strong>{{ item.title }}</strong><small>{{ item.body }} · {{ formatDate(item.created_at) }}</small></span>
                    </button>
                    <div v-if="!notifications.length" class="empty-state"><strong>Belum ada notifikasi</strong><p>Pemberitahuan pribadi akan tampil di sini.</p></div>
                </div>
            </section>
        </main>

        <div v-if="showAnnouncementForm" class="modal-backdrop" @click.self="showAnnouncementForm = false">
            <form class="modal-card" @submit.prevent="submitAnnouncement">
                <div class="modal-heading"><div><p class="eyebrow">Informasi baru</p><h2>Buat pengumuman</h2></div><button type="button" @click="showAnnouncementForm = false">×</button></div>
                <label>Judul<input v-model="announcementForm.title" required maxlength="180" /></label>
                <label>Isi pengumuman<textarea v-model="announcementForm.body" required rows="6" /></label>
                <div class="form-grid"><label>Sasaran<select v-model="announcementForm.audience_role"><option v-for="option in audienceOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select></label><label>Rombel (opsional)<select v-model="announcementForm.class_group_id"><option value="">Semua rombel</option><option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }}</option></select></label></div>
                <div class="form-grid"><label>Berlaku sampai<input v-model="announcementForm.expires_at" type="datetime-local" /></label><label>Lampiran<input type="file" @change="handleAttachment" /></label></div>
                <p v-if="announcementForm.hasErrors" class="form-error">Periksa kembali data pengumuman.</p>
                <div class="modal-actions"><button type="button" class="secondary-button" @click="showAnnouncementForm = false">Batal</button><button class="primary-button" :disabled="announcementForm.processing">Terbitkan</button></div>
            </form>
        </div>

        <div v-if="showEventForm" class="modal-backdrop" @click.self="showEventForm = false">
            <form class="modal-card" @submit.prevent="submitEvent">
                <div class="modal-heading"><div><p class="eyebrow">Kalender akademik</p><h2>Tambah agenda</h2></div><button type="button" @click="showEventForm = false">×</button></div>
                <label>Nama agenda<input v-model="eventForm.title" required maxlength="180" /></label>
                <label>Keterangan<textarea v-model="eventForm.description" rows="4" /></label>
                <div class="form-grid"><label>Jenis<select v-model="eventForm.event_type"><option value="academic">Akademik</option><option value="exam">Ujian</option><option value="holiday">Libur</option><option value="meeting">Rapat</option><option value="activity">Kegiatan</option></select></label><label>Sasaran<select v-model="eventForm.audience_role"><option v-for="option in audienceOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select></label></div>
                <div class="form-grid"><label>Mulai<input v-model="eventForm.starts_at" required type="datetime-local" /></label><label>Selesai<input v-model="eventForm.ends_at" type="datetime-local" /></label></div>
                <label>Rombel (opsional)<select v-model="eventForm.class_group_id"><option value="">Semua rombel</option><option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
                <p v-if="eventForm.hasErrors" class="form-error">Periksa kembali data agenda.</p>
                <div class="modal-actions"><button type="button" class="secondary-button" @click="showEventForm = false">Batal</button><button class="primary-button" :disabled="eventForm.processing">Simpan agenda</button></div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.communication-hero,.hero-actions,.comm-actions,.modal-actions,.notification-toolbar{display:flex;align-items:center}.communication-hero,.notification-toolbar{justify-content:space-between}.communication-hero{gap:1.5rem}.hero-actions,.comm-actions,.modal-actions{gap:.75rem}.comm-tabs{display:flex;gap:.35rem;padding:1rem 1.25rem;border-bottom:1px solid var(--line)}.comm-tabs button{padding:.6rem .9rem;border-radius:.65rem;color:var(--ink-soft);font-size:.82rem;font-weight:800}.comm-tabs button.active{background:var(--primary-soft);color:var(--primary-strong)}.comm-tabs span{margin-left:.25rem;padding:.1rem .4rem;border-radius:999px;background:var(--red);color:#fff;font-size:.65rem}.comm-list{display:grid;gap:.8rem;padding:1.25rem}.announcement-card,.timeline-event{display:flex;align-items:flex-start;gap:1rem;padding:1rem;color:var(--ink);border:1px solid var(--line);border-radius:1rem;background:var(--surface)}.announcement-date{width:3.5rem;flex:0 0 auto;display:grid;place-items:center;padding:.55rem;border-radius:.8rem;background:var(--primary-soft);color:var(--primary-strong)}.announcement-date strong{font-size:1.35rem;line-height:1}.announcement-date span{font-size:.7rem;text-transform:uppercase}.comm-body{min-width:0;flex:1}.comm-body h2,.modal-heading h2{margin:.2rem 0 .35rem;color:var(--ink);font-size:1.05rem;font-weight:800}.comm-body>p{color:var(--ink-soft);font-size:.86rem;white-space:pre-line}.comm-body small{display:block;margin-top:.65rem;color:var(--ink-faint)}.danger-link,.text-button{color:var(--red);font-size:.78rem;font-weight:800}.text-button{color:var(--primary)}.timeline-list{position:relative}.timeline-event{border-left:3px solid var(--primary-soft)}.timeline-dot{width:.75rem;height:.75rem;margin-top:.35rem;border-radius:50%;background:var(--primary)}.timeline-dot[data-type='exam']{background:var(--red)}.timeline-dot[data-type='holiday']{background:var(--green)}.notification-toolbar{padding:.35rem .25rem .8rem;color:var(--ink-soft);font-size:.8rem}.notification-item{width:100%;display:flex;align-items:flex-start;gap:.75rem;padding:1rem;color:var(--ink);border:1px solid var(--line);border-radius:.8rem;text-align:left;background:var(--surface)}.notification-item.unread{border-color:var(--primary);background:var(--primary-soft)}.notification-dot{width:.55rem;height:.55rem;margin-top:.35rem;border-radius:50%;background:var(--line-strong)}.notification-item.unread .notification-dot{background:var(--primary)}.notification-item strong{display:block;color:var(--ink)}.notification-item small{display:block;margin-top:.25rem;color:var(--ink-soft)}.empty-state{padding:3rem 1rem;text-align:center;color:var(--ink-soft)}.empty-state strong{display:block;color:var(--ink)}.modal-backdrop{position:fixed;inset:0;z-index:80;display:grid;place-items:center;padding:1rem;background:rgb(15 23 42/.56)}.modal-card{width:min(650px,100%);max-height:90vh;overflow:auto;display:grid;gap:1rem;padding:1.35rem;color:var(--ink);border:1px solid var(--line);border-radius:1rem;background:var(--surface)}.modal-heading{display:flex;justify-content:space-between}.modal-heading>button{font-size:1.6rem;color:var(--ink-soft)}.modal-card label{display:grid;gap:.4rem;color:var(--ink-soft);font-size:.8rem;font-weight:700}.modal-card input,.modal-card select,.modal-card textarea{width:100%;padding:.65rem .75rem;color:var(--ink);background:var(--surface-subtle);border:1px solid var(--line-strong);border-radius:.65rem}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.modal-actions{justify-content:flex-end}.form-error{color:var(--red);font-size:.8rem}@media(max-width:760px){.communication-hero,.announcement-card{flex-direction:column}.form-grid{grid-template-columns:1fr}.announcement-date{width:auto;display:flex;gap:.3rem}}
</style>
