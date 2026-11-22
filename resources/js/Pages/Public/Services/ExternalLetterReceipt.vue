<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import { Link, usePage } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed } from 'vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    receipt: {
        ticket: { ticket_code: string; public_status: string; public_summary: string };
        record: {
            agenda_number: string;
            letter_number: string;
            letter_date?: string | null;
            received_at?: string | null;
            agency_name: string;
            contact_person: string;
            contact_position: string;
            subject: string;
            summary: string;
            disposition?: string | null;
            status: string;
        };
    };
    issuedSecret?: string | null;
}>();

const page = usePage();
const flashMessage = computed(() => (page.props.flash as { message?: string } | undefined)?.message);
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const publicText = usePublicContentText();
</script>

<template>
    <Head :title="`Tanda Terima ${receipt.ticket.ticket_code} — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="portal-container">
            <div v-if="flashMessage" class="alert-banner alert-banner--success" role="status">{{ flashMessage }}</div>

            <div v-if="issuedSecret" class="alert-banner alert-banner--pin" role="status">
                <strong>{{ publicText("copy.vue_services_externalletterreceipt", "text_001", "Simpan Kode Verifikasi Pelacakan:") }}</strong>
                <span class="pin-code">{{ issuedSecret }}</span>
            </div>

            <section class="portal-card">
                <header class="head-row">
                    <div>
                        <p class="eyebrow">{{ publicText("copy.vue_services_externalletterreceipt", "text_002", "Tanda Terima Surat Dinas Eksternal") }}</p>
                        <h1>{{ receipt.ticket.ticket_code }}</h1>
                        <p class="muted">{{ publicText("copy.vue_services_externalletterreceipt", "text_003", "Nomor Agenda TU:") }} <strong>{{ receipt.record.agenda_number }}</strong></p>
                    </div>
                    <StatusBadge :status="receipt.ticket.public_status" />
                </header>

                <p class="summary-callout">{{ receipt.ticket.public_summary }}</p>

                <dl class="meta-grid">
                    <div><dt>{{ publicText("copy.vue_services_externalletterreceipt", "text_004", "Instansi Pengirim") }}</dt><dd>{{ receipt.record.agency_name }}</dd></div>
                    <div><dt>{{ publicText("copy.vue_services_externalletterreceipt", "text_005", "Narahubung") }}</dt><dd>{{ receipt.record.contact_person }} ({{ receipt.record.contact_position }})</dd></div>
                    <div><dt>{{ publicText("copy.vue_services_externalletterreceipt", "text_006", "Nomor Surat Asal") }}</dt><dd>{{ receipt.record.letter_number }}</dd></div>
                    <div><dt>{{ publicText("copy.vue_services_externalletterreceipt", "text_007", "Tanggal Terima") }}</dt><dd>{{ receipt.record.received_at || '-' }}</dd></div>
                    <div class="full"><dt>{{ publicText("copy.vue_services_externalletterreceipt", "text_008", "Perihal") }}</dt><dd>{{ receipt.record.subject }}</dd></div>
                    <div class="full"><dt>{{ publicText("copy.vue_services_externalletterreceipt", "text_009", "Ringkasan") }}</dt><dd>{{ receipt.record.summary }}</dd></div>
                    <div v-if="receipt.record.disposition" class="full"><dt>{{ publicText("copy.vue_services_externalletterreceipt", "text_010", "Disposisi TU") }}</dt><dd>{{ receipt.record.disposition }}</dd></div>
                </dl>

                <div class="action-row">
                    <a :href="`/layanan/surat-dinas/tanda-terima/${receipt.ticket.ticket_code}/pdf`" target="_blank" rel="noopener" class="ui-button ui-button--primary ui-button--md">
                        {{ publicText("copy.vue_services_externalletterreceipt", "text_011", "Unduh / Cetak Tanda Terima PDF") }}
                    </a>
                    <Link :href="`/lacak?code=${receipt.ticket.ticket_code}&category=surat_eksternal`" class="ui-button ui-button--secondary ui-button--md">
                        {{ publicText("copy.vue_services_externalletterreceipt", "text_012", "Lacak Status Disposisi") }}
                    </Link>
                </div>
            </section>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(920px, calc(100% - 2rem)); margin-inline: auto; padding: 2.25rem 0 4rem; display: grid; gap: 1.25rem; }
.portal-card { background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; padding: 1.5rem; display: grid; gap: 1rem; }
.head-row { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 1rem; }
.head-row h1 { margin: .2rem 0; font-size: 1.75rem; }
.muted { margin: 0; color: var(--public-muted, #526673); }
.summary-callout { margin: 0; padding: .85rem 1rem; border-radius: 10px; background: #f1f5f9; }
.meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .85rem; margin: 0; }
.meta-grid dt { font-size: .78rem; text-transform: uppercase; color: var(--public-muted, #526673); font-weight: 700; }
.meta-grid dd { margin: .15rem 0 0; font-weight: 600; }
.meta-grid .full { grid-column: 1 / -1; }
.action-row { display: flex; flex-wrap: wrap; gap: .75rem; }
.alert-banner { padding: .9rem 1rem; border-radius: 12px; display: grid; gap: .25rem; }
.alert-banner--success { background: #e1f2e8; color: #1b5c3e; border: 1px solid #b6dec9; font-weight: 600; }
.alert-banner--pin { background: #eef4ff; color: #1e3a8a; border: 1px solid #bfdbfe; }
.pin-code { font-size: 1.35rem; font-weight: 800; letter-spacing: .12em; }
</style>
