<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

type ResourceType = 'question' | 'pack';

interface ResourceOption {
    id: number;
    name: string;
    type?: string;
    jenjang?: string | null;
    curriculum?: string | null;
    subject_name?: string | null;
    subject_code?: string | null;
    status?: string | null;
    items_count?: number;
}

interface SharePage {
    data: any[];
    total?: number;
    links?: { url: string | null; label: string; active: boolean }[];
    current_page?: number;
    last_page?: number;
}

const props = defineProps<{
    sent: SharePage;
    received: SharePage;
    recipients: { id: number; name: string; username: string }[];
    resource_options: { questions: ResourceOption[]; packs: ResourceOption[] };
    resource: { type?: string; id?: string };
}>();

const form = useForm({
    resource_type: (['question', 'pack'].includes(String(props.resource.type)) ? String(props.resource.type) : 'question') as ResourceType,
    resource_id: props.resource.id ? Number(props.resource.id) : '',
    recipient_id: '',
    permission: 'view',
    request_note: '',
});
const decision = useForm({ decision_note: '' });
const page = usePage<any>();
const resourceSearch = ref('');

const availableResources = computed(() => {
    const options = form.resource_type === 'question' ? props.resource_options.questions : props.resource_options.packs;
    const query = resourceSearch.value.trim().toLowerCase();

    if (!query) return options;

    return options.filter((resource) => [resource.name, resource.subject_name, resource.subject_code, resource.jenjang]
        .filter(Boolean).join(' ').toLowerCase().includes(query));
});

const summary = computed(() => ({
    incomingPending: props.received.data.filter((share) => share.status === 'pending').length,
    outgoingPending: props.sent.data.filter((share) => share.status === 'pending').length,
    active: [...props.sent.data, ...props.received.data].filter((share) => share.status === 'accepted' && share.active_key === 'active').length,
}));

function submit() {
    form.post('/penilaian/berbagi-soal', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('resource_id', 'recipient_id', 'request_note');
            resourceSearch.value = '';
        },
    });
}

function decide(id: number, status: 'accept' | 'reject') {
    const action = status === 'accept' ? 'menerima' : 'menolak';
    if (!window.confirm(`Yakin ingin ${action} permintaan berbagi ini?`)) return;

    router.post(`/penilaian/berbagi-soal/${id}/${status}`, { decision_note: decision.decision_note }, {
        preserveScroll: true,
        onSuccess: () => decision.reset('decision_note'),
    });
}

function revoke(id: number) {
    if (!window.confirm('Cabut akses berbagi ini? Penerima akan langsung kehilangan akses ke sumber.')) return;

    router.post(`/penilaian/berbagi-soal/${id}/cabut`, {}, { preserveScroll: true });
}

function labelPermission(value: string) {
    return ({ view: 'Lihat', copy: 'Salin', edit: 'Edit sumber' } as Record<string, string>)[value] ?? value;
}

function labelStatus(value: string) {
    return ({ pending: 'Menunggu', accepted: 'Diterima', rejected: 'Ditolak', revoked: 'Dicabut' } as Record<string, string>)[value] ?? value;
}

function labelEvent(value: string) {
    return ({ requested: 'Mengirim permintaan', accepted: 'Menerima', rejected: 'Menolak', revoked: 'Mencabut akses', copied: 'Menyalin sumber' } as Record<string, string>)[value] ?? value;
}

function labelResourceType(value: ResourceType) {
    return value === 'question' ? 'Soal' : 'Paket soal';
}

function resourceMeta(resource: ResourceOption) {
    if (form.resource_type === 'pack') return `${resource.items_count ?? 0} soal · ${resource.jenjang ?? 'Jenjang belum diatur'}`;

    return [resource.subject_code || resource.subject_name, resource.jenjang, resource.type].filter(Boolean).join(' · ');
}

function shareName(share: any) {
    return share.resource_name || `${labelResourceType(share.resource_type)} #${share.resource_id}`;
}
</script>

<template>
    <Head title="Berbagi Bank Soal" />
    <AppLayout>
        <main class="view teacher-sharing-page sharing-shell space-y-5 md:space-y-6">
            <section class="hero-card compact-hero sharing-hero">
                <div class="min-w-0">
                    <p class="eyebrow">Kolaborasi bank soal</p>
                    <h1>Berbagi soal dan paket</h1>
                    <p>Kirim akses ke guru lain dengan izin yang jelas. Sumber tetap dimiliki pembuatnya sampai penerima memilih menyalin.</p>
                </div>
                <div class="sharing-flow" aria-label="Alur berbagi soal">
                    <span><b>1</b>Pilih sumber</span>
                    <span><b>2</b>Tentukan izin</span>
                    <span><b>3</b>Tunggu persetujuan</span>
                </div>
            </section>

            <div v-if="page.props.flash?.message" class="sharing-alert sharing-alert-success" role="status">
                <span class="sharing-alert-icon">✓</span><span>{{ page.props.flash.message }}</span>
            </div>
            <div v-if="page.props.errors?.share" class="sharing-alert sharing-alert-warning" role="alert">
                <span class="sharing-alert-icon">!</span><span>{{ page.props.errors?.share }}</span>
            </div>

            <section class="sharing-summary" aria-label="Ringkasan berbagi soal">
                <article class="sharing-metric metric-warning"><span class="metric-icon">!</span><div><p>Menunggu keputusan Anda</p><strong>{{ summary.incomingPending }}</strong></div></article>
                <article class="sharing-metric metric-primary"><span class="metric-icon">↗</span><div><p>Permintaan terkirim</p><strong>{{ summary.outgoingPending }}</strong></div></article>
                <article class="sharing-metric metric-success"><span class="metric-icon">✓</span><div><p>Akses aktif di halaman ini</p><strong>{{ summary.active }}</strong></div></article>
            </section>

            <form @submit.prevent="submit" class="sharing-form panel-card">
                <div class="sharing-section-heading">
                    <div><p class="eyebrow">Permintaan baru</p><h2>Kirim akses sumber</h2><p>Pilih sumber milik Anda, lalu tentukan apa yang boleh dilakukan penerima.</p></div>
                    <span class="sharing-step-label">Guru / wali kelas</span>
                </div>
                <div class="sharing-form-grid">
                    <label class="sharing-field">Jenis sumber
                        <select v-model="form.resource_type" @change="form.resource_id = ''; resourceSearch = ''">
                            <option value="question">Soal</option>
                            <option value="pack">Paket soal</option>
                        </select>
                    </label>
                    <label class="sharing-field">Cari sumber
                        <input v-model="resourceSearch" type="search" placeholder="Judul, mapel, atau jenjang" />
                    </label>
                    <label class="sharing-field sharing-field-wide">Sumber yang dibagikan
                        <select v-model="form.resource_id" required>
                            <option value="">Pilih {{ labelResourceType(form.resource_type).toLowerCase() }}</option>
                            <option v-for="resourceOption in availableResources" :key="resourceOption.id" :value="resourceOption.id">
                                {{ resourceOption.name }}<template v-if="resourceMeta(resourceOption)"> · {{ resourceMeta(resourceOption) }}</template>
                            </option>
                        </select>
                        <small v-if="!availableResources.length" class="sharing-help sharing-help-warning">Belum ada sumber yang bisa dibagikan untuk jenis ini.</small>
                        <small v-if="form.errors.resource_id" class="sharing-help sharing-help-error">{{ form.errors.resource_id }}</small>
                    </label>
                    <label class="sharing-field">Penerima
                        <select v-model="form.recipient_id" required><option value="">Pilih guru atau wali kelas</option><option v-for="user in recipients" :key="user.id" :value="user.id">{{ user.name }} · {{ user.username }}</option></select>
                        <small v-if="form.errors.recipient_id" class="sharing-help sharing-help-error">{{ form.errors.recipient_id }}</small>
                    </label>
                    <label class="sharing-field">Izin akses
                        <select v-model="form.permission"><option value="view">Lihat isi sumber</option><option value="copy">Salin menjadi milik penerima</option><option value="edit">Ubah sumber (pemilik tetap)</option></select>
                    </label>
                    <label class="sharing-field sharing-field-wide">Catatan untuk penerima
                        <textarea v-model="form.request_note" maxlength="2000" rows="2" placeholder="Contoh: gunakan untuk ulangan harian kelas VII-A" />
                    </label>
                </div>
                <div class="sharing-form-footer"><p>Permintaan baru aktif setelah penerima menyetujui.</p><button type="submit" :disabled="form.processing || !availableResources.length" class="btn btn-primary">Kirim permintaan</button></div>
            </form>

            <section class="sharing-requests">
                <div class="sharing-decision-note panel-card">
                    <label class="sharing-field"><span>Catatan keputusan <small>(opsional)</small></span><textarea v-model="decision.decision_note" maxlength="2000" rows="2" placeholder="Dipakai saat menerima atau menolak permintaan masuk" /></label>
                </div>
                <div class="sharing-request-grid">
                    <section class="sharing-request-panel panel-card">
                        <div class="sharing-panel-heading"><div><p class="eyebrow">Perlu ditinjau</p><h2>Permintaan masuk</h2><p>Akses baru aktif setelah Anda menerima.</p></div><span class="sharing-count count-warning">{{ received.total ?? received.data.length }}</span></div>
                        <div v-if="!received.data.length" class="sharing-empty"><span>↓</span><p>Belum ada permintaan masuk.</p></div>
                        <article v-for="share in received.data" :key="share.id" class="sharing-request-card">
                            <div class="sharing-request-header"><div class="min-w-0"><p class="sharing-resource-name">{{ shareName(share) }}</p><p class="sharing-resource-meta">{{ labelResourceType(share.resource_type) }} · Dari {{ share.sender?.name }} · {{ labelPermission(share.permission) }}</p><span :class="['sharing-status', `status-${share.status}`]">{{ labelStatus(share.status) }}</span><p v-if="share.request_note" class="sharing-request-note">{{ share.request_note }}</p></div><Link :href="`/penilaian/berbagi-soal/${share.id}`" class="sharing-detail-link">Detail</Link></div>
                            <div v-if="share.status === 'pending'" class="sharing-card-actions"><button @click="decide(share.id, 'accept')" class="btn btn-primary btn-small">Terima</button><button @click="decide(share.id, 'reject')" class="btn btn-secondary btn-small">Tolak</button></div>
                            <details v-if="share.events?.length" class="sharing-history"><summary>Riwayat aktivitas ({{ share.events.length }})</summary><p v-for="event in share.events" :key="event.id">{{ event.actor?.name }} · {{ labelEvent(event.event_type) }} · {{ event.created_at }}</p></details>
                        </article>
                        <nav v-if="received.links?.length && received.last_page && received.last_page > 1" class="sharing-pagination" aria-label="Halaman permintaan masuk"><Link v-for="link in received.links" :key="link.label" :href="link.url || '#'" :class="['page-link', { active: link.active, disabled: !link.url }]" v-html="link.label" /></nav>
                    </section>

                    <section class="sharing-request-panel panel-card">
                        <div class="sharing-panel-heading"><div><p class="eyebrow">Riwayat permintaan</p><h2>Permintaan terkirim</h2><p>Cabut akses saat masih menunggu atau sudah diterima.</p></div><span class="sharing-count count-primary">{{ sent.total ?? sent.data.length }}</span></div>
                        <div v-if="!sent.data.length" class="sharing-empty"><span>↗</span><p>Belum ada permintaan terkirim.</p></div>
                        <article v-for="share in sent.data" :key="share.id" class="sharing-request-card">
                            <div class="sharing-request-header"><div class="min-w-0"><p class="sharing-resource-name">{{ shareName(share) }}</p><p class="sharing-resource-meta">{{ labelResourceType(share.resource_type) }} · Kepada {{ share.recipient?.name }} · {{ labelPermission(share.permission) }}</p><span :class="['sharing-status', `status-${share.status}`]">{{ labelStatus(share.status) }}</span><p v-if="share.decision_note" class="sharing-request-note">{{ share.decision_note }}</p></div><Link :href="`/penilaian/berbagi-soal/${share.id}`" class="sharing-detail-link">Riwayat</Link></div>
                            <button v-if="['pending','accepted'].includes(share.status)" @click="revoke(share.id)" class="btn btn-danger btn-small">Cabut akses</button>
                            <details v-if="share.events?.length" class="sharing-history"><summary>Riwayat aktivitas ({{ share.events.length }})</summary><p v-for="event in share.events" :key="event.id">{{ event.actor?.name }} · {{ labelEvent(event.event_type) }} · {{ event.created_at }}</p></details>
                        </article>
                        <nav v-if="sent.links?.length && sent.last_page && sent.last_page > 1" class="sharing-pagination" aria-label="Halaman permintaan terkirim"><Link v-for="link in sent.links" :key="link.label" :href="link.url || '#'" :class="['page-link', { active: link.active, disabled: !link.url }]" v-html="link.label" /></nav>
                    </section>
                </div>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.sharing-shell { max-width: 1180px; margin-inline: auto; }
.sharing-hero { align-items: stretch; }
.sharing-flow { display: grid; gap: .5rem; min-width: 15rem; align-content: center; padding: .85rem 1rem; border: 1px solid var(--line); border-radius: .85rem; background: color-mix(in srgb, var(--surface) 80%, var(--primary-soft)); color: var(--ink-soft); font-size: .76rem; font-weight: 700; }
.sharing-flow span { display: flex; align-items: center; gap: .5rem; }
.sharing-flow b, .metric-icon { display: inline-flex; align-items: center; justify-content: center; width: 1.55rem; height: 1.55rem; border-radius: 999px; background: var(--primary-soft); color: var(--primary); font-size: .72rem; }
.sharing-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
.sharing-metric { display: flex; align-items: center; gap: .75rem; min-width: 0; padding: 1rem; border: 1px solid var(--line); border-radius: var(--radius-md); background: var(--surface); box-shadow: var(--shadow-sm); }
.sharing-metric p { margin: 0; color: var(--ink-soft); font-size: .73rem; font-weight: 700; }
.sharing-metric strong { display: block; margin-top: .12rem; color: var(--ink); font-size: 1.45rem; line-height: 1; }
.metric-warning { border-color: color-mix(in srgb, #d6a000 35%, var(--line)); background: color-mix(in srgb, var(--surface) 91%, #d6a000); }.metric-warning .metric-icon { background: color-mix(in srgb, var(--surface) 76%, #d6a000); color: #936500; }
.metric-primary { border-color: color-mix(in srgb, var(--primary) 24%, var(--line)); background: color-mix(in srgb, var(--surface) 92%, var(--primary-soft)); }.metric-primary .metric-icon { background: var(--primary-soft); color: var(--primary); }
.metric-success { border-color: color-mix(in srgb, var(--green) 24%, var(--line)); background: color-mix(in srgb, var(--surface) 92%, var(--green-soft)); }.metric-success .metric-icon { background: var(--green-soft); color: var(--green); }
.panel-card { border: 1px solid var(--line); border-radius: var(--radius-md); background: var(--surface); box-shadow: var(--shadow-sm); }
.sharing-form { padding: 1.25rem; }.sharing-section-heading, .sharing-panel-heading, .sharing-form-footer { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }.sharing-section-heading { padding-bottom: 1rem; border-bottom: 1px solid var(--line); }.sharing-section-heading h2, .sharing-panel-heading h2 { margin: .2rem 0 .25rem; color: var(--ink); font-size: 1.02rem; font-weight: 800; }.sharing-section-heading p:not(.eyebrow), .sharing-panel-heading p:not(.eyebrow), .sharing-form-footer p { margin: 0; color: var(--ink-soft); font-size: .8rem; line-height: 1.5; }.sharing-step-label { flex: 0 0 auto; padding: .4rem .65rem; border: 1px solid var(--line); border-radius: 999px; color: var(--ink-soft); font-size: .7rem; font-weight: 800; }.sharing-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; margin-top: 1rem; }.sharing-field { display: grid; gap: .35rem; min-width: 0; color: var(--ink); font-size: .78rem; font-weight: 750; }.sharing-field-wide { grid-column: 1 / -1; }.sharing-field input, .sharing-field select, .sharing-field textarea { width: 100%; min-width: 0; min-height: 40px; padding: .58rem .7rem; border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); color: var(--ink); font: inherit; font-size: .8rem; }.sharing-field textarea { resize: vertical; }.sharing-field input:focus, .sharing-field select:focus, .sharing-field textarea:focus { outline: 2px solid color-mix(in srgb, var(--primary) 35%, transparent); outline-offset: 1px; border-color: var(--primary); }.sharing-help { font-size: .72rem; font-weight: 600; }.sharing-help-warning { color: #936500; }.sharing-help-error { color: var(--red); }.sharing-form-footer { align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--line); }.sharing-form-footer p { flex: 1; }.btn-small { min-height: 34px !important; padding: .45rem .72rem !important; font-size: .74rem !important; }.btn-danger { color: var(--red) !important; border-color: color-mix(in srgb, var(--red) 32%, var(--line)) !important; background: var(--surface) !important; }.btn-danger:hover { background: color-mix(in srgb, var(--red) 8%, var(--surface)) !important; }.sharing-alert { display: flex; align-items: center; gap: .6rem; padding: .75rem .9rem; border: 1px solid; border-radius: .7rem; font-size: .8rem; font-weight: 650; }.sharing-alert-success { border-color: color-mix(in srgb, var(--green) 28%, var(--line)); background: color-mix(in srgb, var(--surface) 90%, var(--green-soft)); color: var(--green); }.sharing-alert-warning { border-color: color-mix(in srgb, #d6a000 35%, var(--line)); background: color-mix(in srgb, var(--surface) 90%, #d6a000); color: #845900; }.sharing-alert-icon { display: inline-flex; align-items: center; justify-content: center; width: 1.4rem; height: 1.4rem; border-radius: 999px; background: currentColor; color: var(--surface); font-size: .7rem; font-weight: 900; }
.sharing-requests { display: grid; gap: .85rem; }.sharing-decision-note { padding: 1rem; }.sharing-decision-note .sharing-field span { display: flex; align-items: baseline; gap: .35rem; }.sharing-decision-note small { color: var(--ink-faint); font-size: .7rem; font-weight: 600; }.sharing-request-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }.sharing-request-panel { min-width: 0; padding: 1.1rem; }.sharing-panel-heading { align-items: center; padding-bottom: .85rem; border-bottom: 1px solid var(--line); }.sharing-count { display: inline-flex; align-items: center; justify-content: center; min-width: 2rem; height: 2rem; padding-inline: .55rem; border-radius: 999px; font-size: .78rem; font-weight: 850; }.count-warning { background: color-mix(in srgb, var(--surface) 76%, #d6a000); color: #936500; }.count-primary { background: var(--primary-soft); color: var(--primary); }.sharing-empty { display: grid; place-items: center; gap: .35rem; min-height: 9rem; color: var(--ink-faint); text-align: center; }.sharing-empty span { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; background: var(--surface-subtle); color: var(--primary); font-size: 1.1rem; }.sharing-empty p { margin: 0; font-size: .78rem; }.sharing-request-card { display: grid; gap: .7rem; margin-top: .75rem; padding: .85rem; border: 1px solid var(--line); border-radius: .75rem; background: var(--surface); }.sharing-request-header { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }.sharing-resource-name { margin: 0; overflow-wrap: anywhere; color: var(--ink); font-size: .82rem; font-weight: 800; line-height: 1.35; }.sharing-resource-meta { margin: .25rem 0 .45rem; color: var(--ink-soft); font-size: .72rem; line-height: 1.45; }.sharing-request-note { margin: .55rem 0 0; padding: .5rem .6rem; border-left: 3px solid var(--line-strong); background: var(--surface-subtle); color: var(--ink-soft); font-size: .74rem; line-height: 1.45; }.sharing-detail-link { flex: 0 0 auto; color: var(--primary); font-size: .74rem; font-weight: 800; text-decoration: none; }.sharing-detail-link:hover { text-decoration: underline; }.sharing-status { display: inline-flex; width: fit-content; padding: .22rem .52rem; border-radius: 999px; font-size: .68rem; font-weight: 800; }.status-pending { background: color-mix(in srgb, var(--surface) 76%, #d6a000); color: #936500; }.status-accepted { background: var(--green-soft); color: var(--green); }.status-rejected, .status-revoked { background: var(--red-soft); color: var(--red); }.sharing-card-actions { display: flex; flex-wrap: wrap; gap: .5rem; }.sharing-history { border-top: 1px solid var(--line); padding-top: .6rem; color: var(--ink-soft); font-size: .68rem; }.sharing-history summary { cursor: pointer; color: var(--primary); font-weight: 750; }.sharing-history p { margin: .35rem 0 0; }.sharing-pagination { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: 1rem; }.page-link { display: inline-flex; align-items: center; justify-content: center; min-width: 2rem; min-height: 2rem; padding: .35rem .55rem; border: 1px solid var(--line); border-radius: .5rem; color: var(--ink-soft); font-size: .72rem; text-decoration: none; }.page-link.active { border-color: var(--primary); background: var(--primary); color: #fff; }.page-link.disabled { pointer-events: none; opacity: .45; }
@media (max-width: 760px) { .sharing-flow { min-width: 0; }.sharing-summary, .sharing-request-grid, .sharing-form-grid { grid-template-columns: 1fr; }.sharing-field-wide { grid-column: auto; }.sharing-form-footer { align-items: stretch; flex-direction: column; }.sharing-form-footer .btn { width: 100%; }.sharing-request-header { align-items: stretch; flex-direction: column; }.sharing-detail-link { align-self: flex-start; }.sharing-section-heading, .sharing-panel-heading { align-items: flex-start; flex-direction: column; }.sharing-step-label { align-self: flex-start; } }
</style>
