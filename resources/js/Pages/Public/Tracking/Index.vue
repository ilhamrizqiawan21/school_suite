<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

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
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));

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
</script>

<template>
    <Head :title="`Lacak Berkas Terpadu — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="portal-container">
            <header class="portal-hero">
                <p class="eyebrow">Pelacakan Terpadu</p>
                <h1>Lacak Tiket &amp; Berkas Layanan</h1>
                <p>Pantau progres permohonan PTSP, izin siswa, legalisir, mutasi, surat dinas eksternal, dan pengaduan dari satu pintu.</p>
            </header>

            <section class="portal-card">
                <div class="chip-row" role="group" aria-label="Filter kategori layanan pelacakan">
                    <button
                        v-for="cat in categories"
                        :key="cat.value"
                        type="button"
                        class="chip-btn"
                        :class="{ 'is-active': form.category === cat.value }"
                        @click="selectCategory(cat.value)"
                    >
                        {{ cat.label }}
                    </button>
                </div>

                <form class="lookup-form" @submit.prevent="submitLookup">
                    <FormField id="track-code" label="Nomor Tiket / Referensi" required :error="form.errors.code" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.code" type="text" required placeholder="Contoh: PTSP-2026-XXXXXXXX atau SRT-2026-XXXXXXXX" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="track-secret" label="Kode Verifikasi / PIN Pemohon" hint="Wajib diisi kecuali Anda masuk dengan akun pemilik tiket." :error="form.errors.verification_secret" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.verification_secret" type="password" autocomplete="off" placeholder="Masukkan PIN verifikasi resi" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <div class="form-action">
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="form.processing">
                            Periksa Status
                        </button>
                    </div>
                </form>
            </section>

            <section v-if="result" class="portal-card">
                <StateFeedback
                    v-if="!result.found"
                    state="empty"
                    title="Tiket tidak ditemukan"
                    description="Periksa kembali nomor tiket dan kategori layanan yang dipilih."
                />

                <StateFeedback
                    v-else-if="!result.authorized"
                    state="error"
                    title="Verifikasi identitas diperlukan"
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
                            <p v-if="result.ticket.meta?.agenda_number" class="muted">Nomor Agenda TU: {{ result.ticket.meta.agenda_number }}</p>
                        </div>
                        <StatusBadge :status="result.ticket.public_status || 'submitted'" />
                    </div>

                    <p class="summary-box">{{ result.ticket.public_summary }}</p>

                    <div v-if="result.ticket.actions?.receipt_url || result.ticket.actions?.revision_url" class="action-row">
                        <Link v-if="result.ticket.actions?.receipt_url" :href="result.ticket.actions.receipt_url" class="ui-button ui-button--secondary ui-button--md">
                            Buka Halaman Resi / Tanda Terima
                        </Link>
                        <Link v-if="result.ticket.actions?.revision_url" :href="result.ticket.actions.revision_url" class="ui-button ui-button--primary ui-button--md">
                            Perbaiki Berkas
                        </Link>
                    </div>

                    <div v-if="result.ticket.documents?.length" class="docs-box">
                        <h3>Dokumen Terkait</h3>
                        <ul>
                            <li v-for="doc in result.ticket.documents" :key="doc.id">
                                <span>{{ doc.original_name }}</span>
                                <small v-if="doc.document_number"> ({{ doc.document_number }})</small>
                                <StatusBadge :status="doc.status" size="sm" />
                            </li>
                        </ul>
                    </div>

                    <div v-if="result.ticket.timeline?.length" class="timeline-box">
                        <h3>Riwayat Status</h3>
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
.chip-btn { min-height: 40px; padding: .35rem .85rem; border-radius: 999px; border: 1px solid var(--public-panel-border, #d8e2ee); background: #fff; font: inherit; font-weight: 600; cursor: pointer; }
.chip-btn.is-active { background: var(--public-primary, #3157a4); border-color: var(--public-primary, #3157a4); color: #fff; }
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
