<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import RowActionButton from '@/components/RowActionButton.vue';
import AppLayout from '../../layouts/AppLayout.vue';

interface ClassItem { id: number; name: string; grade_level: number }
interface Announcement { id: number; title: string; body: string; audience_role?: string | null; attachment_name?: string | null; published_at: string | null; expires_at?: string | null; author: { name: string }; class_group?: { name: string } | null }
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
}>();

const page = usePage<{ flash?: { message?: string } }>();
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
const classAllowed = (role: string) => !['parent', 'principal', 'admin', 'operator'].includes(role);
watch(() => announcementForm.audience_role, (role) => { if (!classAllowed(role)) announcementForm.class_group_id = ''; });
watch(() => eventForm.audience_role, (role) => { if (!classAllowed(role)) eventForm.class_group_id = ''; });
const audienceLabel = (role?: string | null, group?: { name: string } | null) => {
    const label = audienceOptions.find((option) => option.value === (role ?? ''))?.label ?? role ?? 'Semua warga sekolah';
    return group ? `${label} · ${group.name}` : label;
};
const eventTypeLabel = (type: string) => ({ academic: 'Akademik', exam: 'Ujian', holiday: 'Libur', meeting: 'Rapat', activity: 'Kegiatan' } as Record<string, string>)[type] ?? type;

const formatDate = (value?: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : '—';

const handleAttachment = (event: Event) => {
    announcementForm.attachment = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const submitAnnouncement = () => announcementForm.post('/komunikasi/pengumuman', {
    forceFormData: true,
    onSuccess: () => { announcementForm.reset(); showAnnouncementForm.value = false; tab.value = 'announcements'; },
});

const submitEvent = () => eventForm.post('/komunikasi/kalender', {
    onSuccess: () => { eventForm.reset(); showEventForm.value = false; tab.value = 'calendar'; },
});

const remove = (url: string, label: string) => {
    if (window.confirm(`Hapus ${label}?`)) router.delete(url, { preserveScroll: true });
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
                    <h1>Pengumuman & agenda</h1>
                    <p>Bagikan informasi sekolah, lihat jadwal kegiatan, dan pantau notifikasi Anda.</p>
                </div>
                <div class="hero-actions">
                    <Link v-if="can_chat" class="secondary-button" href="/komunikasi/chat">Chat kelas</Link>
                </div>
            </section>

            <div v-if="page.props.flash?.message" class="comm-notice" role="status">{{ page.props.flash.message }}</div>

            <section class="grade-summary comm-summary" aria-label="Ringkasan informasi">
                <div><span>Pengumuman</span><strong>{{ announcements.length }}</strong><small>yang dapat Anda lihat</small></div>
                <div><span>Agenda</span><strong>{{ events.length }}</strong><small>sejak bulan lalu</small></div>
                <div><span>Belum dibaca</span><strong>{{ unreadCount }}</strong><small>dari 50 notifikasi terbaru</small></div>
            </section>

            <section class="panel overflow-hidden">
                <div class="comm-tabs" aria-label="Bagian informasi sekolah">
                    <button type="button" :class="{ active: tab === 'announcements' }" :aria-pressed="tab === 'announcements'" @click="tab = 'announcements'">Pengumuman <b>{{ announcements.length }}</b></button>
                    <button type="button" :class="{ active: tab === 'calendar' }" :aria-pressed="tab === 'calendar'" @click="tab = 'calendar'">Agenda <b>{{ events.length }}</b></button>
                    <button type="button" :class="{ active: tab === 'notifications' }" :aria-pressed="tab === 'notifications'" @click="tab = 'notifications'">Notifikasi <span v-if="unreadCount">{{ unreadCount }}</span></button>
                </div>

                <div v-if="tab === 'announcements'" class="comm-list">
                    <div class="list-heading"><div><h2>Pengumuman sekolah</h2><p>Informasi yang masih berlaku untuk Anda dan pengumuman yang Anda buat.</p></div><button v-if="can_manage" type="button" class="primary-button" @click="showAnnouncementForm = true">Buat pengumuman</button></div>
                    <article v-for="item in announcements" :key="item.id" class="announcement-card">
                        <div class="announcement-date"><template v-if="item.published_at"><strong>{{ new Date(item.published_at).getDate() }}</strong><span>{{ new Intl.DateTimeFormat('id-ID', { month: 'short' }).format(new Date(item.published_at)) }}</span></template><span v-else>—</span></div>
                        <div class="comm-body">
                            <p class="eyebrow">{{ audienceLabel(item.audience_role, item.class_group) }}</p>
                            <h2>{{ item.title }}</h2>
                            <p>{{ item.body }}</p>
                            <small>{{ item.author.name }} · Terbit {{ formatDate(item.published_at) }}<template v-if="item.expires_at"> · Berlaku sampai {{ formatDate(item.expires_at) }}</template></small>
                        </div>
                        <div class="comm-actions">
                            <a v-if="item.attachment_name" class="secondary-button" :href="`/komunikasi/pengumuman/${item.id}/lampiran`">Lampiran</a>
                            <RowActionButton v-if="can_manage" action="delete" label="Hapus" :aria-label="`Hapus pengumuman ${item.title}`" @click="remove(`/komunikasi/pengumuman/${item.id}`, `pengumuman “${item.title}”`)" />
                        </div>
                    </article>
                    <div v-if="!announcements.length" class="empty-state"><strong>Belum ada pengumuman</strong><p>Informasi terbaru sekolah akan tampil di sini.</p></div>
                </div>

                <div v-else-if="tab === 'calendar'" class="comm-list timeline-list">
                    <div class="list-heading"><div><h2>Agenda sekolah</h2><p>Kegiatan mulai dari satu bulan terakhir, diurutkan menurut waktu mulai.</p></div><button v-if="can_manage" type="button" class="primary-button" @click="showEventForm = true">Tambah agenda</button></div>
                    <article v-for="event in events" :key="event.id" class="timeline-event">
                        <div class="timeline-dot" :data-type="event.event_type"></div>
                        <div class="comm-body">
                            <p class="eyebrow">{{ eventTypeLabel(event.event_type) }} · {{ audienceLabel(event.audience_role, event.class_group) }}</p>
                            <h2>{{ event.title }}</h2>
                            <p>{{ event.description || 'Tidak ada keterangan tambahan.' }}</p>
                            <small>{{ formatDate(event.starts_at) }}<template v-if="event.ends_at"> — {{ formatDate(event.ends_at) }}</template></small>
                        </div>
                        <RowActionButton v-if="can_manage" action="delete" label="Hapus" :aria-label="`Hapus agenda ${event.title}`" @click="remove(`/komunikasi/kalender/${event.id}`, `agenda “${event.title}”`)" />
                    </article>
                    <div v-if="!events.length" class="empty-state"><strong>Belum ada agenda</strong><p>Agenda akademik akan tampil secara kronologis.</p></div>
                </div>

                <div v-else class="comm-list">
                    <div class="list-heading"><div><h2>Notifikasi Anda</h2><p>Pilih notifikasi untuk menandainya telah dibaca.</p></div></div>
                    <div class="notification-toolbar"><span>{{ unreadCount }} belum dibaca</span><button v-if="unreadCount" class="text-button" @click="router.post('/komunikasi/notifikasi/baca-semua')">Tandai semua dibaca</button></div>
                    <button v-for="item in notifications" :key="item.id" class="notification-item" :class="{ unread: !item.read_at }" @click="markRead(item)">
                        <span class="notification-dot"></span><span><strong>{{ item.title }}</strong><small>{{ item.body }} · {{ formatDate(item.created_at) }}</small></span>
                    </button>
                    <div v-if="!notifications.length" class="empty-state"><strong>Belum ada notifikasi</strong><p>Pemberitahuan pribadi akan tampil di sini.</p></div>
                </div>
            </section>
        </main>

        <div v-if="showAnnouncementForm" class="modal-backdrop" @click.self="showAnnouncementForm = false">
            <form class="modal-card" role="dialog" aria-modal="true" aria-labelledby="announcement-title" @submit.prevent="submitAnnouncement">
                <div class="modal-heading"><div><p class="eyebrow">Informasi baru</p><h2 id="announcement-title">Buat pengumuman</h2><p>Pengumuman langsung terbit. Notifikasi dibuat untuk akun aktif yang sesuai sasaran; jumlah penerima ditampilkan setelah terbit.</p></div><button type="button" aria-label="Tutup formulir pengumuman" @click="showAnnouncementForm = false">×</button></div>
                <label>Judul <input v-model="announcementForm.title" required maxlength="180" placeholder="Contoh: Jadwal pembagian rapor" /><small v-if="announcementForm.errors.title" class="form-error">{{ announcementForm.errors.title }}</small></label>
                <label>Isi pengumuman <textarea v-model="announcementForm.body" required rows="5" placeholder="Tuliskan informasi yang perlu diketahui penerima." /><small v-if="announcementForm.errors.body" class="form-error">{{ announcementForm.errors.body }}</small></label>
                <div class="form-grid"><label>Sasaran<select v-model="announcementForm.audience_role"><option v-for="option in audienceOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select><small v-if="announcementForm.errors.audience_role" class="form-error">{{ announcementForm.errors.audience_role }}</small></label><label>Rombel (opsional)<select v-model="announcementForm.class_group_id" :disabled="!classAllowed(announcementForm.audience_role)"><option value="">Semua rombel</option><option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }}</option></select><small v-if="announcementForm.errors.class_group_id" class="form-error">{{ announcementForm.errors.class_group_id }}</small></label></div>
                <p class="form-hint">Jika memilih rombel, penerima dibatasi pada siswa terdaftar dan guru yang memiliki penugasan mengajar di rombel tersebut. Sasaran orang tua dan kepala sekolah berlaku untuk seluruh sekolah.</p>
                <div class="form-grid"><label>Berlaku sampai (opsional)<input v-model="announcementForm.expires_at" type="datetime-local" /><small v-if="announcementForm.errors.expires_at" class="form-error">{{ announcementForm.errors.expires_at }}</small></label><label>Lampiran (opsional)<input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" @change="handleAttachment" /><small>PDF, Word, Excel, atau gambar · maks. 10 MB</small><small v-if="announcementForm.errors.attachment" class="form-error">{{ announcementForm.errors.attachment }}</small></label></div>
                <p v-if="announcementForm.hasErrors" class="form-error" role="alert">Periksa kolom yang ditandai sebelum menerbitkan.</p>
                <div class="modal-actions"><button type="button" class="secondary-button" :disabled="announcementForm.processing" @click="showAnnouncementForm = false">Batal</button><button class="primary-button" :disabled="announcementForm.processing">{{ announcementForm.processing ? 'Menerbitkan…' : 'Terbitkan pengumuman' }}</button></div>
            </form>
        </div>

        <div v-if="showEventForm" class="modal-backdrop" @click.self="showEventForm = false">
            <form class="modal-card" role="dialog" aria-modal="true" aria-labelledby="event-title" @submit.prevent="submitEvent">
                <div class="modal-heading"><div><p class="eyebrow">Kalender sekolah</p><h2 id="event-title">Tambah agenda</h2><p>Agenda tersimpan di halaman ini. Untuk mengirim notifikasi, buat pengumuman terpisah.</p></div><button type="button" aria-label="Tutup formulir agenda" @click="showEventForm = false">×</button></div>
                <label>Nama agenda<input v-model="eventForm.title" required maxlength="180" placeholder="Contoh: Rapat orang tua" /><small v-if="eventForm.errors.title" class="form-error">{{ eventForm.errors.title }}</small></label>
                <label>Keterangan (opsional)<textarea v-model="eventForm.description" rows="3" /><small v-if="eventForm.errors.description" class="form-error">{{ eventForm.errors.description }}</small></label>
                <div class="form-grid"><label>Jenis<select v-model="eventForm.event_type"><option value="academic">Akademik</option><option value="exam">Ujian</option><option value="holiday">Libur</option><option value="meeting">Rapat</option><option value="activity">Kegiatan</option></select><small v-if="eventForm.errors.event_type" class="form-error">{{ eventForm.errors.event_type }}</small></label><label>Sasaran<select v-model="eventForm.audience_role"><option v-for="option in audienceOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select><small v-if="eventForm.errors.audience_role" class="form-error">{{ eventForm.errors.audience_role }}</small></label></div>
                <div class="form-grid"><label>Mulai<input v-model="eventForm.starts_at" required type="datetime-local" /><small v-if="eventForm.errors.starts_at" class="form-error">{{ eventForm.errors.starts_at }}</small></label><label>Selesai (opsional)<input v-model="eventForm.ends_at" type="datetime-local" /><small v-if="eventForm.errors.ends_at" class="form-error">{{ eventForm.errors.ends_at }}</small></label></div>
                <label>Rombel (opsional)<select v-model="eventForm.class_group_id" :disabled="!classAllowed(eventForm.audience_role)"><option value="">Semua rombel</option><option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }}</option></select><small v-if="eventForm.errors.class_group_id" class="form-error">{{ eventForm.errors.class_group_id }}</small></label>
                <p class="form-hint">Sasaran orang tua dan kepala sekolah berlaku untuk seluruh sekolah.</p>
                <p v-if="eventForm.hasErrors" class="form-error" role="alert">Periksa kolom yang ditandai sebelum menyimpan.</p>
                <div class="modal-actions"><button type="button" class="secondary-button" :disabled="eventForm.processing" @click="showEventForm = false">Batal</button><button class="primary-button" :disabled="eventForm.processing">{{ eventForm.processing ? 'Menyimpan…' : 'Simpan agenda' }}</button></div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.communication-hero,.hero-actions,.comm-actions,.modal-actions,.notification-toolbar{display:flex;align-items:center}.communication-hero,.notification-toolbar{justify-content:space-between}.communication-hero{gap:1.5rem}.hero-actions,.comm-actions,.modal-actions{gap:.75rem}.comm-tabs{display:flex;gap:.35rem;padding:1rem 1.25rem;border-bottom:1px solid var(--line)}.comm-tabs button{padding:.6rem .9rem;border-radius:.65rem;color:var(--ink-soft);font-size:.82rem;font-weight:800}.comm-tabs button.active{background:var(--primary-soft);color:var(--primary-strong)}.comm-tabs span{margin-left:.25rem;padding:.1rem .4rem;border-radius:999px;background:var(--red);color:#fff;font-size:.65rem}.comm-list{display:grid;gap:.8rem;padding:1.25rem}.announcement-card,.timeline-event{display:flex;align-items:flex-start;gap:1rem;padding:1rem;color:var(--ink);border:1px solid var(--line);border-radius:1rem;background:var(--surface)}.announcement-date{width:3.5rem;flex:0 0 auto;display:grid;place-items:center;padding:.55rem;border-radius:.8rem;background:var(--primary-soft);color:var(--primary-strong)}.announcement-date strong{font-size:1.35rem;line-height:1}.announcement-date span{font-size:.7rem;text-transform:uppercase}.comm-body{min-width:0;flex:1}.comm-body h2,.modal-heading h2{margin:.2rem 0 .35rem;color:var(--ink);font-size:1.05rem;font-weight:800}.comm-body>p{color:var(--ink-soft);font-size:.86rem;white-space:pre-line}.comm-body small{display:block;margin-top:.65rem;color:var(--ink-faint)}.danger-link,.text-button{color:var(--red);font-size:.78rem;font-weight:800}.text-button{color:var(--primary)}.timeline-list{position:relative}.timeline-event{border-left:3px solid var(--primary-soft)}.timeline-dot{width:.75rem;height:.75rem;margin-top:.35rem;border-radius:50%;background:var(--primary)}.timeline-dot[data-type='exam']{background:var(--red)}.timeline-dot[data-type='holiday']{background:var(--green)}.notification-toolbar{padding:.35rem .25rem .8rem;color:var(--ink-soft);font-size:.8rem}.notification-item{width:100%;display:flex;align-items:flex-start;gap:.75rem;padding:1rem;color:var(--ink);border:1px solid var(--line);border-radius:.8rem;text-align:left;background:var(--surface)}.notification-item.unread{border-color:var(--primary);background:var(--primary-soft)}.notification-dot{width:.55rem;height:.55rem;margin-top:.35rem;border-radius:50%;background:var(--line-strong)}.notification-item.unread .notification-dot{background:var(--primary)}.notification-item strong{display:block;color:var(--ink)}.notification-item small{display:block;margin-top:.25rem;color:var(--ink-soft)}.empty-state{padding:3rem 1rem;text-align:center;color:var(--ink-soft)}.empty-state strong{display:block;color:var(--ink)}.modal-backdrop{position:fixed;inset:0;z-index:80;display:grid;place-items:center;padding:1rem;background:rgb(15 23 42/.56)}.modal-card{width:min(650px,100%);max-height:90vh;overflow:auto;display:grid;gap:1rem;padding:1.35rem;color:var(--ink);border:1px solid var(--line);border-radius:1rem;background:var(--surface)}.modal-heading{display:flex;justify-content:space-between}.modal-heading>button{font-size:1.6rem;color:var(--ink-soft)}.modal-card label{display:grid;gap:.4rem;color:var(--ink-soft);font-size:.8rem;font-weight:700}.modal-card input,.modal-card select,.modal-card textarea{width:100%;padding:.65rem .75rem;color:var(--ink);background:var(--surface-subtle);border:1px solid var(--line-strong);border-radius:.65rem}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.modal-actions{justify-content:flex-end}.form-error{color:var(--red);font-size:.8rem}@media(max-width:760px){.communication-hero,.announcement-card{flex-direction:column}.form-grid{grid-template-columns:1fr}.announcement-date{width:auto;display:flex;gap:.3rem}}
.comm-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.comm-notice { padding: .9rem 1rem; border: 1px solid var(--line); border-radius: .75rem; background: var(--green-soft); color: var(--ink); font-size: .85rem; }
.comm-tabs { flex-wrap: wrap; }
.comm-tabs button { min-height: 44px; }
.comm-tabs b { margin-left: .25rem; color: var(--ink-faint); font-weight: 700; }
.list-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .15rem 0 .4rem; }
.list-heading h2 { color: var(--ink); font-size: 1rem; font-weight: 800; }
.list-heading p, .modal-heading p:not(.eyebrow), .form-hint { margin-top: .3rem; color: var(--ink-soft); font-size: .8rem; line-height: 1.5; }
.comm-body h2, .comm-body p, .comm-body small, .notification-item small { overflow-wrap: anywhere; }
.comm-actions { flex-wrap: wrap; }
.modal-card input, .modal-card select, .modal-card button { min-height: 44px; }
.modal-card select:disabled { opacity: .55; }
.modal-card label small { font-weight: 400; }
.modal-card .form-error { color: var(--red); }
.modal-heading > button { align-self: flex-start; width: 44px; flex: 0 0 44px; }
@media (max-width: 760px) {
    .comm-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .comm-list { padding: 1rem; }
    .list-heading { align-items: stretch; flex-direction: column; }
    .list-heading button { width: 100%; }
    .comm-actions { width: 100%; }
    .timeline-event { flex-wrap: wrap; }
    .modal-actions { flex-wrap: wrap; }
}
@media (max-width: 440px) { .comm-summary { grid-template-columns: 1fr; } }
</style>
