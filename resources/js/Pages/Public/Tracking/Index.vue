<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import { Link, useForm } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed } from 'vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
const publicText = usePublicContentText();

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    categories: { value: string; label: string }[];
    filters: { code: string; category: string };
    result: {
        found: boolean;
        authorized: boolean;
        reason?: string;
        ticket?: {
            ticket_code: string;
            service_type: string;
            service_label: string;
            public_status?: string;
            public_summary?: string;
            updated_at?: string | null;
            meta?: Record<string, string>;
            actions?: { receipt_url?: string; revision_url?: string };
            documents?: { id: number; document_kind: string; document_number?: string | null; original_name: string; status: string }[];
            timeline?: { public_status: string; created_at?: string | null }[];
        };
    } | null;
    contentBlocks?: Record<string, Record<string, any>>;
}>();

const heroBlock = computed(() => props.contentBlocks?.['tracking.hero'] ?? {});
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const referenceCategories = computed(() => props.categories.length > 0 ? props.categories : [
    { value: 'all', label: publicText("copy.vue_tracking_index", "text_12001", "Semua Layanan") }, { value: 'izin', label: publicText("copy.vue_tracking_index", "text_004", "Surat Izin") },
    { value: 'legalisir', label: publicText("copy.vue_tracking_index", "text_013", "Legalisir Ijazah") }, { value: 'mutasi', label: publicText("copy.vue_tracking_index", "text_021", "Mutasi Siswa") },
    { value: 'ptsp', label: publicText("copy.vue_tracking_index", "text_026", "Layanan PTSP") },
]);

const form = useForm({
    code: props.filters.code || '',
    category: props.filters.category || 'all',
    verification_secret: '',
});

const selectCategory = (value: string) => {
    form.category = value;
};

const submitLookup = () => {
    form.post('/lacak', { preserveScroll: true });
};

const formatDate = (iso?: string | null) => iso ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso)) : '-';

const categoryIcon = (value: string): string => {
    const map: Record<string, string> = {
        all: 'fa-solid fa-layer-group',
        ptsp: 'fa-solid fa-stamp',
        izin: 'fa-solid fa-file-signature',
        legalisir: 'fa-solid fa-certificate',
        mutasi: 'fa-solid fa-people-arrows',
        surat_eksternal: 'fa-solid fa-envelope-open-text',
        aduan: 'fa-solid fa-bullhorn',
    };
    return map[value] ?? 'fa-solid fa-ticket';
};
</script>

<template>
    <Head :title="`Lacak Berkas Terpadu — ${school.name}`" />
    <main class="public-site public-portal-page" data-madrasah-service="tracking" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="portal-container">
            <header v-if="contentBlocks?.['tracking.hero']?.visible !== false" class="portal-hero madrasah-service-hero text-center">
                <p class="madrasah-service-label mx-auto"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> {{ heroBlock.label || 'PUSAT LAYANAN INFORMASI & PELACAKAN RESMI' }}</p>
                <h1>{{ heroBlock.title || 'Pusat Lacak Status Layanan Terpadu' }}</h1>
                <p class="font-bold tracking-wider uppercase text-emerald-100">{{ school.name }}</p>
                <p class="mx-auto">{{ heroBlock.subtitle || 'Lacak progres seluruh permohonan online Anda secara transparan dan realtime dengan memasukkan Nomor Tiket atau Kode Registrasi Pengajuan beserta PIN Verifikasi.' }}</p>
                <div class="tracking-wave" aria-hidden="true"><svg viewBox="0 0 1440 120" preserveAspectRatio="none"><path d="M0,45 C320,110 440,15 720,65 C1000,115 1140,25 1440,55 L1440,120 L0,120 Z" fill="#f8fafc" /></svg></div>
            </header>

            <section class="portal-card parity-tracking-card">
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                    <strong class="tracking-search-label"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> {{ publicText("copy.vue_tracking_index", "text_001", "Masukkan Kata Kunci Pencarian:") }}</strong>
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-500">{{ heroBlock.keywords_hint || 'No. Tiket • Kode Resi • PIN Verifikasi' }}</span>
                </div>
                <form class="parity-tracking-pill" @submit.prevent="submitLookup">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="track-code" v-model="form.code" type="text" required :placeholder="heroBlock.placeholder || 'Contoh: PTSP-2026..., IZN-2026..., MUT-2026..., LEG-2026..., ADU-2026...'" :aria-label="publicText('copy.vue_tracking_index', 'aria_label_037', 'Nomor Tiket atau Kode Resi')" />
                    <button type="submit" class="parity-tracking-btn" :disabled="form.processing">
                        <span>{{ publicText("copy.vue_tracking_index", "text_002", "Lacak Status") }}</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <div class="chip-row" role="group" :aria-label="publicText('copy.vue_tracking_index', 'aria_label_038', 'Filter kategori layanan pelacakan')">
                    <button
                        v-for="cat in referenceCategories"
                        :key="cat.value"
                        type="button"
                        class="chip-btn"
                        :aria-pressed="form.category === cat.value"
                        :class="{ 'is-active': form.category === cat.value }"
                        @click="selectCategory(cat.value)"
                    >
                        <i :class="categoryIcon(cat.value)" aria-hidden="true"></i>
                        <span>{{ cat.label }}</span>
                    </button>
                </div>
            </section>

            <section class="parity-tracking-guides" :aria-label="publicText('copy.vue_tracking_index', 'aria_label_039', 'Panduan kata kunci berdasarkan layanan')">
                <p class="parity-guides-label">{{ publicText("copy.vue_tracking_index", "text_003", "PANDUAN KATA KUNCI BERDASARKAN LAYANAN") }}</p>
                <div class="parity-guides-grid">
                    <article class="guide-card guide-card--blue">
                        <div class="guide-card__head"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i><strong>{{ publicText("copy.vue_tracking_index", "text_004", "Surat Izin") }}</strong><span>{{ publicText("copy.vue_tracking_index", "text_005", "BK & Piket") }}</span></div>
                        <p>{{ publicText("copy.vue_tracking_index", "text_006", "Gunakan") }} <strong>{{ publicText("copy.vue_tracking_index", "text_007", "NISN Siswa") }}</strong> {{ publicText("copy.vue_tracking_index", "text_008", "atau") }} <strong>{{ publicText("copy.vue_tracking_index", "text_009", "No. Pengajuan Izin") }}</strong> {{ publicText("copy.vue_tracking_index", "text_010", "untuk melihat persetujuan wali kelas & piket.") }}</p>
                        <button class="guide-example" @click="form.code = 'IZN-2026'; selectCategory('izin')">{{ publicText("copy.vue_tracking_index", "text_011", "Contoh Format:") }} <code>{{ publicText("copy.vue_tracking_index", "text_012", "IZN-2026...") }}</code></button>
                    </article>
                    <article class="guide-card guide-card--amber">
                        <div class="guide-card__head"><i class="fa-solid fa-certificate" aria-hidden="true"></i><strong>{{ publicText("copy.vue_tracking_index", "text_013", "Legalisir Ijazah") }}</strong><span>{{ publicText("copy.vue_tracking_index", "text_014", "Alumni") }}</span></div>
                        <p>{{ publicText("copy.vue_tracking_index", "text_006", "Gunakan") }} <strong>{{ publicText("copy.vue_tracking_index", "text_015", "NISN") }}</strong> {{ publicText("copy.vue_tracking_index", "text_016", "saat sekolah atau") }} <strong>{{ publicText("copy.vue_tracking_index", "text_017", "Nomor Registrasi") }}</strong> {{ publicText("copy.vue_tracking_index", "text_018", "untuk memantau tanda tangan berkas.") }}</p>
                        <button class="guide-example" @click="selectCategory('legalisir')">{{ publicText("copy.vue_tracking_index", "text_019", "Format Input:") }} <code>{{ publicText("copy.vue_tracking_index", "text_020", "NISN / No. Reg") }}</code></button>
                    </article>
                    <article class="guide-card guide-card--emerald">
                        <div class="guide-card__head"><i class="fa-solid fa-right-left" aria-hidden="true"></i><strong>{{ publicText("copy.vue_tracking_index", "text_021", "Mutasi Siswa") }}</strong><span>{{ publicText("copy.vue_tracking_index", "text_022", "Kesiswaan") }}</span></div>
                        <p>{{ publicText("copy.vue_tracking_index", "text_006", "Gunakan") }} <strong>{{ publicText("copy.vue_tracking_index", "text_015", "NISN") }}</strong> {{ publicText("copy.vue_tracking_index", "text_008", "atau") }} <strong>{{ publicText("copy.vue_tracking_index", "text_023", "Kode Mutasi") }}</strong> {{ publicText("copy.vue_tracking_index", "text_024", "untuk memantau status persetujuan mutasi madrasah.") }}</p>
                        <button class="guide-example" @click="form.code = 'MUT-2026'; selectCategory('mutasi')">{{ publicText("copy.vue_tracking_index", "text_011", "Contoh Format:") }} <code>{{ publicText("copy.vue_tracking_index", "text_025", "MUT-2026...") }}</code></button>
                    </article>
                    <article class="guide-card guide-card--indigo">
                        <div class="guide-card__head"><i class="fa-regular fa-building" aria-hidden="true"></i><strong>{{ publicText("copy.vue_tracking_index", "text_026", "Layanan PTSP") }}</strong><span>{{ publicText("copy.vue_tracking_index", "text_027", "Pelayanan") }}</span></div>
                        <p>{{ publicText("copy.vue_tracking_index", "text_006", "Gunakan") }} <strong>{{ publicText("copy.vue_tracking_index", "text_028", "Kode Tiket PTSP") }}</strong> {{ publicText("copy.vue_tracking_index", "text_008", "atau") }} <strong>{{ publicText("copy.vue_tracking_index", "text_029", "NIK KTP") }}</strong> {{ publicText("copy.vue_tracking_index", "text_030", "pemohon untuk memantau progress dokumen Anda.") }}</p>
                        <button class="guide-example" @click="form.code = 'PTSP-2026'; selectCategory('ptsp')">{{ publicText("copy.vue_tracking_index", "text_011", "Contoh Format:") }} <code>{{ publicText("copy.vue_tracking_index", "text_031", "PTSP-2026... / NIK") }}</code></button>
                    </article>
                </div>
            </section>

            <section v-if="result" class="portal-card">
                <form v-if="result.found && !result.authorized" class="parity-tracking-pill" @submit.prevent="submitLookup"><input id="track-secret" v-model="form.verification_secret" type="password" autocomplete="off" :placeholder="publicText('copy.vue_tracking_index', 'placeholder_040', 'PIN verifikasi pemohon')" :aria-label="publicText('copy.vue_tracking_index', 'aria_label_041', 'PIN verifikasi pemohon')" required /><button type="submit" class="parity-tracking-btn">{{ publicText("copy.vue_tracking_index", "text_032", "Verifikasi") }}</button></form>
                <StateFeedback
                    v-if="!result.found"
                    state="empty"
                    :title="publicText('copy.vue_tracking_index', 'title_042', 'Tiket tidak ditemukan')"
                    :description="publicText('copy.vue_tracking_index', 'description_043', 'Periksa kembali nomor tiket dan kategori layanan yang dipilih.')"
                />

                <StateFeedback
                    v-else-if="!result.authorized"
                    state="error"
                    :title="publicText('copy.vue_tracking_index', 'title_044', 'Verifikasi identitas diperlukan')"
                    :description="result.reason === 'identity_verification_failed'
                        ? 'Kode verifikasi tidak cocok. Detail berkas tidak dapat dibuka.'
                        : 'Masukkan kode verifikasi/PIN pemohon atau masuk ke akun pemilik tiket untuk membuka rincian.'"
                />

                <div v-else-if="result.ticket" class="result-panel">
                    <div class="result-head">
                        <div>
                            <span class="category-tag">{{ result.ticket.service_label }}</span>
                            <h2>{{ result.ticket.ticket_code }}</h2>
                            <p v-if="result.ticket.meta?.service_name" class="muted">{{ result.ticket.meta.service_name }}</p>
                            <p v-if="result.ticket.meta?.agenda_number" class="muted">{{ publicText("copy.vue_tracking_index", "text_9001", "Nomor Agenda TU:") }} {{ result.ticket.meta.agenda_number }}</p>
                        </div>
                        <StatusBadge :status="result.ticket.public_status || 'submitted'" />
                    </div>

                    <p class="summary-box">{{ result.ticket.public_summary }}</p>

                    <div v-if="result.ticket.actions?.receipt_url || result.ticket.actions?.revision_url" class="action-row">
                        <Link v-if="result.ticket.actions?.receipt_url" :href="result.ticket.actions.receipt_url" class="ui-button ui-button--secondary ui-button--md">
                            {{ publicText("copy.vue_tracking_index", "text_033", "Buka Halaman Resi / Tanda Terima") }}
                        </Link>
                        <Link v-if="result.ticket.actions?.revision_url" :href="result.ticket.actions.revision_url" class="ui-button ui-button--primary ui-button--md">
                            {{ publicText("copy.vue_tracking_index", "text_034", "Perbaiki Berkas") }}
                        </Link>
                    </div>

                    <div v-if="result.ticket.documents?.length" class="docs-box">
                        <h3>{{ publicText("copy.vue_tracking_index", "text_035", "Dokumen Terkait") }}</h3>
                        <ul>
                            <li v-for="doc in result.ticket.documents" :key="doc.id">
                                <span>{{ doc.original_name }}</span>
                                <small v-if="doc.document_number"> ({{ doc.document_number }})</small>
                                <StatusBadge :status="doc.status" size="sm" />
                            </li>
                        </ul>
                    </div>

                    <div v-if="result.ticket.timeline?.length" class="timeline-box">
                        <h3>{{ publicText("copy.vue_tracking_index", "text_036", "Riwayat Status") }}</h3>
                        <ol>
                            <li v-for="(step, index) in result.ticket.timeline" :key="index">
                                <StatusBadge :status="step.public_status" size="sm" />
                                <span>{{ formatDate(step.created_at) }}</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </section>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(980px, calc(100% - 2rem)); margin-inline: auto; padding: 2.25rem 0 4rem; display: grid; gap: 1.5rem; }
.portal-hero h1 { margin: .35rem 0 .45rem; font-size: clamp(1.75rem, 3.6vw, 2.5rem); line-height: 1.15; }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); max-width: 65ch; }
.portal-card { background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; padding: 1.5rem; display: grid; gap: 1.25rem; }
.chip-row { display: flex; flex-wrap: wrap; gap: .45rem; }
.chip-btn { min-height: 40px; padding: .35rem .85rem; border-radius: 999px; border: 1px solid var(--public-panel-border, #d8e2ee); background: #fff; font: inherit; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: .4rem; }
.chip-btn.is-active { background: var(--public-primary, #3157a4); border-color: var(--public-primary, #3157a4); color: #fff; }
.tracking-hints { display: flex; flex-wrap: wrap; align-items: center; gap: .45rem; font-size: .78rem; color: var(--public-muted, #526673); padding-top: .25rem; border-top: 1px dashed #dce7e3; }
.tracking-hints code { padding: .15rem .45rem; border-radius: 6px; background: #f1f5f9; color: #0f172a; font-size: .75rem; }
.lookup-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; align-items: start; }
.form-action { grid-column: 1 / -1; }
.result-panel { display: grid; gap: 1rem; }
.result-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 1rem; }
.result-head h2 { margin: .25rem 0; font-size: 1.5rem; }
.category-tag { font-size: .78rem; font-weight: 700; color: var(--public-primary, #3157a4); text-transform: uppercase; }
.muted { margin: .15rem 0 0; color: var(--public-muted, #526673); font-size: .9rem; }
.summary-box { margin: 0; padding: .9rem 1rem; border-radius: 10px; background: #f1f5f9; }
.action-row { display: flex; flex-wrap: wrap; gap: .75rem; }
.docs-box ul, .timeline-box ol { margin: .5rem 0 0; padding-left: 1.2rem; display: grid; gap: .5rem; }
.docs-box li, .timeline-box li { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
</style>
