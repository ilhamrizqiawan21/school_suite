<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    ptspRequest: {
        ticket_code: string;
        status: string;
        public_summary?: string | null;
        applicant_name: string;
        applicant_phone?: string | null;
        applicant_email?: string | null;
        applicant_organization?: string | null;
        purpose: string;
        notes?: string | null;
        revision_note?: string | null;
        rejection_reason?: string | null;
        disposition_to?: string | null;
        physical_pickup_name?: string | null;
        physical_pickup_at?: string | null;
        submitted_at?: string | null;
        service_snapshot: { name?: string; code?: string; estimated_duration?: string };
        documents: { id: number; document_kind: string; document_number?: string | null; original_name: string; status: string }[];
        transitions: { id: number; public_status: string; reason?: string | null; created_at?: string | null }[];
    };
    issuedSecret?: string | null;
    canRevise: boolean;
    canCancel?: boolean;
}>();

const page = usePage();
const flashMessage = computed(() => (page.props.flash as { message?: string } | undefined)?.message);
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const formatDate = (iso?: string | null) => iso ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso)) : '-';

const showCancelForm = ref(false);
const cancelForm = useForm({
    reason: '',
});

const submitCancel = () => {
    cancelForm.post(`/layanan/ptsp/${props.ptspRequest.ticket_code}/batalkan`, {
        preserveScroll: true,
        onSuccess: () => {
            showCancelForm.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Resi ${ptspRequest.ticket_code} — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="portal-container">
            <div v-if="flashMessage" class="alert-banner alert-banner--success" role="status">{{ flashMessage }}</div>

            <div v-if="issuedSecret" class="alert-banner alert-banner--pin" role="status">
                <strong>Simpan Kode Verifikasi Anda:</strong>
                <span class="pin-code">{{ issuedSecret }}</span>
                <small>Gunakan kode ini bersama nomor tiket untuk melacak berkas di menu Lacak Berkas.</small>
            </div>

            <section class="portal-card">
                <header class="receipt-head">
                    <div>
                        <p class="eyebrow">Resi Pengajuan PTSP</p>
                        <h1>{{ ptspRequest.ticket_code }}</h1>
                        <p class="muted">{{ ptspRequest.service_snapshot.name }} ({{ ptspRequest.service_snapshot.code }})</p>
                    </div>
                    <StatusBadge :status="ptspRequest.status" />
                </header>

                <p v-if="ptspRequest.public_summary" class="summary-callout">{{ ptspRequest.public_summary }}</p>
                <p v-if="ptspRequest.revision_note && canRevise" class="warning-callout">
                    Catatan Perbaikan Petugas: {{ ptspRequest.revision_note }}
                </p>
                <p v-if="ptspRequest.rejection_reason && ptspRequest.status === 'rejected'" class="danger-callout">
                    Alasan Penolakan: {{ ptspRequest.rejection_reason }}
                </p>

                <dl class="meta-grid">
                    <div><dt>Pemohon</dt><dd>{{ ptspRequest.applicant_name }}</dd></div>
                    <div><dt>Instansi / Asal</dt><dd>{{ ptspRequest.applicant_organization || '-' }}</dd></div>
                    <div><dt>Estimasi</dt><dd>{{ ptspRequest.service_snapshot.estimated_duration || '-' }}</dd></div>
                    <div><dt>Waktu Ajuan</dt><dd>{{ formatDate(ptspRequest.submitted_at) }}</dd></div>
                    <div class="full"><dt>Keperluan</dt><dd>{{ ptspRequest.purpose }}</dd></div>
                    <div v-if="ptspRequest.physical_pickup_name" class="full">
                        <dt>Pengambilan Fisik</dt>
                        <dd>Diambil oleh {{ ptspRequest.physical_pickup_name }} pada {{ formatDate(ptspRequest.physical_pickup_at) }}</dd>
                    </div>
                </dl>

                <div class="action-row">
                    <a :href="`/layanan/ptsp/resi/${ptspRequest.ticket_code}/pdf`" target="_blank" rel="noopener" class="ui-button ui-button--primary ui-button--md">
                        Unduh / Cetak Resi PDF
                    </a>
                    <Link v-if="canRevise" :href="`/layanan/ptsp/${ptspRequest.ticket_code}/perbaiki`" class="ui-button ui-button--secondary ui-button--md">
                        Perbaiki Berkas Sekarang
                    </Link>
                    <button v-if="canCancel" type="button" class="ui-button ui-button--secondary ui-button--md" @click="showCancelForm = !showCancelForm">
                        {{ showCancelForm ? 'Tutup Pembatalan' : 'Batalkan Permohonan' }}
                    </button>
                    <Link href="/layanan/ptsp" class="ui-button ui-button--secondary ui-button--md">Kembali ke PTSP</Link>
                </div>

                <form v-if="canCancel && showCancelForm" class="cancel-form" @submit.prevent="submitCancel">
                    <label for="cancel-reason" class="cancel-label">Alasan Pembatalan Permohonan</label>
                    <textarea id="cancel-reason" v-model="cancelForm.reason" rows="2" placeholder="Tuliskan alasan pembatalan (opsional)..." />
                    <div class="action-row">
                        <button type="submit" class="ui-button ui-button--danger ui-button--sm" :disabled="cancelForm.processing">
                            Konfirmasi Batalkan Permohonan
                        </button>
                    </div>
                </form>
            </section>

            <section v-if="ptspRequest.documents.length" class="portal-card">
                <h2>Lampiran &amp; Hasil Dokumen</h2>
                <ul class="doc-list">
                    <li v-for="doc in ptspRequest.documents" :key="doc.id" class="doc-row">
                        <div>
                            <strong>{{ doc.original_name }}</strong>
                            <small class="muted"> · {{ doc.document_kind === 'letter' ? 'Hasil Terbit' : 'Lampiran Pemohon' }} <span v-if="doc.document_number">({{ doc.document_number }})</span></small>
                        </div>
                        <a :href="`/layanan/ptsp/${ptspRequest.ticket_code}/dokumen/${doc.id}`" class="ui-button ui-button--secondary ui-button--sm">Unduh</a>
                    </li>
                </ul>
            </section>

            <section class="portal-card">
                <h2>Riwayat Status</h2>
                <ol class="timeline">
                    <li v-for="step in ptspRequest.transitions" :key="step.id">
                        <StatusBadge :status="step.public_status" size="sm" />
                        <span>{{ formatDate(step.created_at) }}</span>
                        <p v-if="step.reason" class="muted">{{ step.reason }}</p>
                    </li>
                </ol>
            </section>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(920px, calc(100% - 2rem)); margin-inline: auto; padding: 2.25rem 0 4rem; display: grid; gap: 1.25rem; }
.portal-card { background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; padding: 1.5rem; display: grid; gap: 1rem; }
.receipt-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 1rem; }
.receipt-head h1 { margin: .2rem 0; font-size: 1.75rem; }
.muted { color: var(--public-muted, #526673); font-size: .88rem; margin: 0; }
.summary-callout { margin: 0; padding: .85rem 1rem; border-radius: 10px; background: #f1f5f9; }
.warning-callout { margin: 0; padding: .85rem 1rem; border-radius: 10px; background: #fff1d5; color: #80500d; font-weight: 600; }
.danger-callout { margin: 0; padding: .85rem 1rem; border-radius: 10px; background: #fbe9e7; color: #b13f38; font-weight: 600; }
.meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .85rem; margin: 0; }
.meta-grid dt { font-size: .78rem; text-transform: uppercase; color: var(--public-muted, #526673); font-weight: 700; }
.meta-grid dd { margin: .15rem 0 0; font-weight: 600; }
.meta-grid .full { grid-column: 1 / -1; }
.action-row { display: flex; flex-wrap: wrap; gap: .75rem; }
.doc-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .6rem; }
.doc-row { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .75rem; padding: .75rem; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 10px; }
.timeline { margin: 0; padding-left: 1.2rem; display: grid; gap: .65rem; }
.timeline li { display: grid; gap: .2rem; }
.alert-banner { padding: .9rem 1rem; border-radius: 12px; display: grid; gap: .25rem; }
.alert-banner--success { background: #e1f2e8; color: #1b5c3e; border: 1px solid #b6dec9; font-weight: 600; }
.alert-banner--pin { background: #eef4ff; color: #1e3a8a; border: 1px solid #bfdbfe; }
.pin-code { font-size: 1.35rem; font-weight: 800; letter-spacing: .12em; }
</style>
