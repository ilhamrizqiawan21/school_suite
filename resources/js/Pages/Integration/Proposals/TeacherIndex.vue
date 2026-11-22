<script setup lang="ts">
import { useConfirmedDialogClose } from '@/composables/useUnsavedDialog';
import { ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AppCard from '@/components/AppCard.vue';
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import StatusBadge, { type StatusTone } from '@/components/StatusBadge.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import ProposalBudgetEditor, { type BudgetRow } from '@/components/ProposalBudgetEditor.vue';

interface RevisionEntry {
    version: number;
    event: string;
    note: string;
    total_requested_amount?: number;
    actor_name?: string;
    recorded_at?: string;
}

interface ProposalItem {
    id: number;
    proposal_code: string;
    title: string;
    activity_type: string;
    activity_type_label: string;
    organizer_name: string;
    organizer_unit?: string | null;
    start_date: string;
    end_date?: string | null;
    location?: string | null;
    purpose: string;
    budget_items: BudgetRow[];
    total_requested_amount: number;
    revision_number: number;
    revision_history: RevisionEntry[];
    attachment_document_id?: number | null;
    status: string;
    status_label: string;
    review_note?: string | null;
    lpj_realization_amount?: number | null;
    lpj_summary?: string | null;
    lpj_document_ids: number[];
}

const props = defineProps<{
    proposals: ProposalItem[];
    activityTypes: Record<string, string>;
    statusLabels: Record<string, string>;
    canReview: boolean;
}>();

const editingProposalId = ref<number | null>(null);
const selectedLpjProposalId = ref<number | null>(null);

const form = useForm({
    title: '',
    activity_type: 'academic_event',
    organizer_name: '',
    organizer_unit: '',
    start_date: '',
    end_date: '',
    location: '',
    purpose: '',
    revision_note: '',
    budget_items: [{ name: 'Perlengkapan & Modul Kegiatan', qty: 1, unit: 'paket', unit_price: 250000, note: '' }] as BudgetRow[],
    attachment: null as File | null,
});

const lpjForm = useForm({
    lpj_realization_amount: 0,
    lpj_summary: '',
    proof_file: null as File | null,
});

const toneForStatus = (status: string): StatusTone => {
    if (status === 'approved' || status === 'closed') return 'success';
    if (status === 'needs_revision' || status === 'lpj_submitted') return 'warning';
    if (status === 'rejected') return 'danger';
    return 'info';
};

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);

const startRevision = (item: ProposalItem) => {
    editingProposalId.value = item.id;
    form.title = item.title;
    form.activity_type = item.activity_type;
    form.organizer_name = item.organizer_name;
    form.organizer_unit = item.organizer_unit ?? '';
    form.start_date = item.start_date;
    form.end_date = item.end_date ?? item.start_date;
    form.location = item.location ?? '';
    form.purpose = item.purpose;
    form.revision_note = '';
    form.budget_items = item.budget_items.map((row) => ({ ...row }));
    form.attachment = null;
};

const cancelRevision = () => {
    editingProposalId.value = null;
    form.reset();
};

const submitProposal = () => {
    const url = editingProposalId.value
        ? `/proposal/${editingProposalId.value}/revisi`
        : '/guru/proposal';

    form.post(url, {
        forceFormData: true,
        onSuccess: () => {
            editingProposalId.value = null;
            form.reset();
        },
    });
};

const openLpj = (item: ProposalItem) => {
    selectedLpjProposalId.value = item.id;
    lpjForm.lpj_realization_amount = item.lpj_realization_amount ?? item.total_requested_amount;
    lpjForm.lpj_summary = item.lpj_summary ?? '';
    lpjForm.proof_file = null;
    lpjForm.clearErrors();
    lpjForm.defaults();
};

const submitLpj = () => {
    if (!selectedLpjProposalId.value) return;
    lpjForm.post(`/proposal/${selectedLpjProposalId.value}/lpj`, {
        forceFormData: true,
        onSuccess: () => {
            selectedLpjProposalId.value = null;
            lpjForm.reset();
        },
    });
};

const showTeacherProposalEditor = ref(false);
const requestTeacherProposalEditorClose = useConfirmedDialogClose(form, () => { showTeacherProposalEditor.value = false; form.reset(); form.clearErrors(); });
watch(() => form.wasSuccessful, (success) => { if (success) form.defaults(); });
const requestLpjClose = useConfirmedDialogClose(lpjForm, () => { selectedLpjProposalId.value = null; lpjForm.reset(); lpjForm.clearErrors(); });
</script>

<template>
    <Head title="Proposal Kegiatan Guru" />

    <AppLayout title="Proposal Kegiatan Guru">
        <div class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-[var(--ink)]">Proposal Kegiatan Guru</h1>
                    <p class="mt-1 text-sm text-[var(--ink-soft)]">
                        Pengajuan kegiatan penugasan kerja guru lengkap dengan rincian RAB, lampiran, dan riwayat revisi versi.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        href="/layanan/proposal"
                        class="rounded-lg border border-[var(--line)] bg-[var(--surface)] px-3 py-2 text-xs font-semibold text-[var(--ink-soft)] hover:bg-[var(--surface-subtle)]"
                    >
                        Portal Proposal Mandiri
                    </Link>
                    <Link
                        v-if="canReview"
                        href="/administrasi/proposal"
                        class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                    >
                        Antrean Telaah Reviewer
                    </Link>
                </div>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                Catatan tata kelola (SS-D-009): Pengajuan ini mencakup rencana kegiatan, RAB, telaah, dan realisasi LPJ administratif; pencairan dana atau transaksi rekening perbankan tidak diproses melalui School Suite.
            </div>

            <AppCard>
                <AppButton variant="primary" type="button" @click="showTeacherProposalEditor = true">Isi proposal penugasan guru</AppButton>
<Teleport to="body"><div v-if="showTeacherProposalEditor" class="admin-form-overlay" role="dialog" aria-modal="true" aria-label="Isi proposal penugasan guru" @click.self="requestTeacherProposalEditorClose" @keydown.esc.stop.prevent="requestTeacherProposalEditorClose"><div class="admin-form-dialog"><header class="admin-form-header"><h2>Isi proposal penugasan guru</h2><AppButton type="button" icon="close" icon-only aria-label="Tutup isi proposal penugasan guru" title="Tutup formulir" :disabled="form.processing" @click="requestTeacherProposalEditorClose" /></header>
<form class="space-y-4 p-5" @submit.prevent="submitProposal">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--line)] pb-3">

                        <AppButton v-if="editingProposalId" variant="ghost" size="sm" @click="cancelRevision">
                            Batal Revisi
                        </AppButton>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <FormField id="prop-title" label="Judul Kegiatan" required :error="form.errors.title">
                            <input id="prop-title" v-model="form.title" type="text" required class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                        </FormField>

                        <FormField id="prop-type" label="Jenis Kegiatan" required :error="form.errors.activity_type">
                            <select id="prop-type" v-model="form.activity_type" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                                <option v-for="(label, key) in activityTypes" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </FormField>

                        <FormField id="prop-org" label="Nama Penanggung Jawab / Guru" required :error="form.errors.organizer_name">
                            <input id="prop-org" v-model="form.organizer_name" type="text" required class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                        </FormField>

                        <FormField id="prop-unit" label="Unit / Bidang Tugas" :error="form.errors.organizer_unit">
                            <input id="prop-unit" v-model="form.organizer_unit" type="text" placeholder="Kurikulum / MGMP IPA" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                        </FormField>

                        <FormField id="prop-start" label="Tanggal Mulai" required :error="form.errors.start_date">
                            <input id="prop-start" v-model="form.start_date" type="date" required class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                        </FormField>

                        <FormField id="prop-end" label="Tanggal Selesai" :error="form.errors.end_date">
                            <input id="prop-end" v-model="form.end_date" type="date" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                        </FormField>
                    </div>

                    <FormField id="prop-location" label="Lokasi Pelaksanaan" :error="form.errors.location">
                        <input id="prop-location" v-model="form.location" type="text" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                    </FormField>

                    <FormField id="prop-purpose" label="Tujuan & Sasaran Kegiatan" required :error="form.errors.purpose">
                        <textarea id="prop-purpose" v-model="form.purpose" rows="3" required class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                    </FormField>

                    <FormField v-if="editingProposalId" id="prop-rev-note" label="Catatan Perbaikan (Versi Baru)">
                        <input id="prop-rev-note" v-model="form.revision_note" type="text" placeholder="Ringkas perbaikan yang dilakukan" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                    </FormField>

                    <ProposalBudgetEditor v-model="form.budget_items" />
                    <p v-if="form.errors.budget_items" class="text-xs text-rose-600">{{ form.errors.budget_items }}</p>

                    <FormField id="prop-attachment" label="Lampiran Kerangka Acuan / Proposal (PDF/Gambar)" :error="form.errors.attachment">
                        <input
                            id="prop-attachment"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-sm text-[var(--ink-soft)]"
                            @change="form.attachment = ($event.target as HTMLInputElement).files?.[0] ?? null"
                        >
                    </FormField>

                    <div class="flex justify-end">
                        <AppButton type="submit" variant="primary" :loading="form.processing">
                            {{ editingProposalId ? 'Kirim Ulang Revisi' : 'Kirim Proposal Guru' }}
                        </AppButton>
                    </div>
                </form>
</div></div></Teleport>
            </AppCard>

            <AppCard>
                <div class="p-5 space-y-4">
                    <h2 class="text-base font-semibold text-[var(--ink)]">Daftar Proposal Penugasan Guru</h2>

                    <StateFeedback
                        v-if="proposals.length === 0"
                        state="empty"
                        title="Belum ada proposal guru"
                        description="Ajukan proposal kegiatan pertama menggunakan formulir di atas."
                    />

                    <div v-else class="space-y-4">
                        <div
                            v-for="item in proposals"
                            :key="item.id"
                            class="rounded-xl border border-[var(--line)] bg-slate-50/60 p-4 space-y-3"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-xs font-semibold text-[var(--ink-faint)]">{{ item.proposal_code }}</span>
                                        <StatusBadge :status="item.status" :label="`${item.status_label} (v${item.revision_number})`" :tone="toneForStatus(item.status)" />
                                    </div>
                                    <h3 class="mt-1 text-base font-semibold text-[var(--ink)]">{{ item.title }}</h3>
                                    <p class="text-xs text-[var(--ink-soft)]">
                                        {{ item.activity_type_label }} · PJ: {{ item.organizer_name }} · {{ item.start_date }} · Total RAB: {{ formatCurrency(item.total_requested_amount) }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a
                                        v-if="item.attachment_document_id"
                                        :href="`/proposal/${item.id}/dokumen/${item.attachment_document_id}`"
                                        class="rounded-md border border-[var(--line)] bg-[var(--surface)] px-2.5 py-1.5 text-xs font-medium text-[var(--ink-soft)] hover:bg-[var(--surface-subtle)]"
                                    >
                                        Unduh Lampiran
                                    </a>
                                    <AppButton v-if="item.status === 'needs_revision'" variant="warning" size="sm" @click="startRevision(item)">
                                        Perbaiki & Kirim Revisi
                                    </AppButton>
                                    <AppButton v-if="item.status === 'approved' || item.status === 'lpj_submitted'" variant="primary" size="sm" @click="openLpj(item)">
                                        Unggah Bukti LPJ
                                    </AppButton>
                                </div>
                            </div>

                            <p v-if="item.review_note" class="rounded-md bg-[var(--surface)] px-3 py-2 text-xs text-[var(--ink-soft)] border border-[var(--line)]">
                                <strong>Catatan Reviewer:</strong> {{ item.review_note }}
                            </p>

                            <div v-if="item.revision_history.length > 0" class="text-xs text-[var(--ink-soft)] space-y-1">
                                <p class="font-semibold text-[var(--ink-soft)]">Riwayat Keputusan & Revisi:</p>
                                <ul class="list-disc pl-5 space-y-0.5">
                                    <li v-for="(entry, idx) in item.revision_history" :key="idx">
                                        v{{ entry.version }} · {{ entry.event }} — {{ entry.note }} ({{ entry.actor_name ?? 'Sistem' }})
                                    </li>
                                </ul>
                            </div>

                            <Teleport to="body"><div v-if="selectedLpjProposalId === item.id" class="admin-form-overlay" role="dialog" aria-modal="true" aria-label="Unggah realisasi LPJ" @click.self="requestLpjClose" @keydown.esc.stop.prevent="requestLpjClose"><div class="admin-form-dialog"><header class="admin-form-header"><h2>Unggah realisasi LPJ</h2><AppButton type="button" icon="close" icon-only aria-label="Tutup unggah realisasi lpj" :disabled="lpjForm.processing" @click="requestLpjClose" /></header><form
                                v-if="selectedLpjProposalId === item.id"
                                class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50/60 p-4 space-y-3"
                                @submit.prevent="submitLpj"
                            >
                                <h4 class="text-sm font-semibold text-emerald-950">Unggah Bukti Realisasi LPJ</h4>
                                <div class="grid gap-3 md:grid-cols-2">
                                    <FormField id="lpj-amount" label="Nominal Realisasi (Rp)" required :error="lpjForm.errors.lpj_realization_amount">
                                        <input id="lpj-amount" v-model.number="lpjForm.lpj_realization_amount" type="number" min="0" required class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm bg-[var(--surface)]">
                                    </FormField>
                                    <FormField id="lpj-file" label="Berkas Bukti LPJ (PDF/Foto)" :error="lpjForm.errors.proof_file">
                                        <input
                                            id="lpj-file"
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            class="block w-full text-sm text-[var(--ink-soft)]"
                                            @change="lpjForm.proof_file = ($event.target as HTMLInputElement).files?.[0] ?? null"
                                        >
                                    </FormField>
                                </div>
                                <FormField id="lpj-summary" label="Ringkasan Realisasi Kegiatan" required :error="lpjForm.errors.lpj_summary">
                                    <textarea id="lpj-summary" v-model="lpjForm.lpj_summary" rows="2" required class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm bg-[var(--surface)]" />
                                </FormField>
                                <div class="flex justify-end gap-2">
                                    <AppButton variant="ghost" size="sm" @click="requestLpjClose">Batal</AppButton>
                                    <AppButton type="submit" variant="primary" size="sm" :loading="lpjForm.processing">Simpan Bukti LPJ</AppButton>
                                </div>
                            </form></div></div></Teleport>
                        </div>
                    </div>
                </div>
            </AppCard>
        </div>
    </AppLayout>
</template>
