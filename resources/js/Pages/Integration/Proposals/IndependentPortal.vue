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

interface CommitteeMember {
    role: string;
    name: string;
}

interface ProposalItem {
    id: number;
    proposal_code: string;
    title: string;
    activity_type: string;
    activity_type_label: string;
    organizer_name: string;
    organizer_unit?: string | null;
    committee_members: CommitteeMember[];
    start_date: string;
    end_date?: string | null;
    location?: string | null;
    purpose: string;
    budget_items: BudgetRow[];
    total_requested_amount: number;
    revision_number: number;
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
    samplePreset: {
        preset_code: string;
        title: string;
        activity_type: string;
        organizer_name: string;
        organizer_unit?: string;
        committee_members: CommitteeMember[];
        start_date: string;
        end_date?: string;
        location?: string;
        purpose: string;
        budget_items: BudgetRow[];
    };
    activityTypes: Record<string, string>;
    statusLabels: Record<string, string>;
    canReview: boolean;
}>();

const editingProposalId = ref<number | null>(null);
const selectedLpjProposalId = ref<number | null>(null);

const form = useForm({
    title: '',
    activity_type: 'extracurricular',
    organizer_name: '',
    organizer_unit: '',
    committee_members: [{ role: 'Ketua Pelaksana', name: '' }] as CommitteeMember[],
    start_date: '',
    end_date: '',
    location: '',
    purpose: '',
    revision_note: '',
    budget_items: [{ name: 'Kebutuhan Operasional Kegiatan', qty: 1, unit: 'paket', unit_price: 150000, note: '' }] as BudgetRow[],
    attachment: null as File | null,
});

const lpjForm = useForm({
    lpj_realization_amount: 0,
    lpj_summary: '',
    proof_file: null as File | null,
});

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);

const toneForStatus = (status: string): StatusTone => {
    if (status === 'approved' || status === 'closed') return 'success';
    if (status === 'needs_revision' || status === 'lpj_submitted') return 'warning';
    if (status === 'rejected') return 'danger';
    return 'info';
};

const loadSampleWebinar = () => {
    editingProposalId.value = null;
    form.title = props.samplePreset.title;
    form.activity_type = props.samplePreset.activity_type;
    form.organizer_name = props.samplePreset.organizer_name;
    form.organizer_unit = props.samplePreset.organizer_unit ?? '';
    form.committee_members = props.samplePreset.committee_members.map((m) => ({ ...m }));
    form.start_date = props.samplePreset.start_date;
    form.end_date = props.samplePreset.end_date ?? props.samplePreset.start_date;
    form.location = props.samplePreset.location ?? '';
    form.purpose = props.samplePreset.purpose;
    form.budget_items = props.samplePreset.budget_items.map((row) => ({ ...row }));
};

const addCommittee = () => {
    form.committee_members.push({ role: 'Anggota Panitia', name: '' });
};

const removeCommittee = (idx: number) => {
    if (form.committee_members.length <= 1) return;
    form.committee_members.splice(idx, 1);
};

const startRevision = (item: ProposalItem) => {
    editingProposalId.value = item.id;
    form.title = item.title;
    form.activity_type = item.activity_type;
    form.organizer_name = item.organizer_name;
    form.organizer_unit = item.organizer_unit ?? '';
    form.committee_members = item.committee_members.length > 0 ? item.committee_members.map((m) => ({ ...m })) : [{ role: 'Ketua Pelaksana', name: '' }];
    form.start_date = item.start_date;
    form.end_date = item.end_date ?? item.start_date;
    form.location = item.location ?? '';
    form.purpose = item.purpose;
    form.revision_note = '';
    form.budget_items = item.budget_items.map((row) => ({ ...row }));
};

const submitProposal = () => {
    const url = editingProposalId.value
        ? `/proposal/${editingProposalId.value}/revisi`
        : '/layanan/proposal';

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

const showIndependentProposalEditor = ref(false);
const requestIndependentProposalEditorClose = useConfirmedDialogClose(form, () => { showIndependentProposalEditor.value = false; form.reset(); form.clearErrors(); });
watch(() => form.wasSuccessful, (success) => { if (success) form.defaults(); });
const requestLpjClose = useConfirmedDialogClose(lpjForm, () => { selectedLpjProposalId.value = null; lpjForm.reset(); lpjForm.clearErrors(); });
</script>

<template>
    <Head title="Portal Proposal & LPJ Mandiri" />

    <AppLayout title="Portal Proposal & LPJ Mandiri">
        <div class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-[var(--ink)]">Portal Proposal & LPJ Mandiri</h1>
                    <p class="mt-1 text-sm text-[var(--ink-soft)]">
                        Pengajuan kegiatan mandiri siswa, OSIS/MPK, atau organisasi dengan editor RAB dinamis, susunan panitia, dan pelaporan bukti LPJ.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <AppButton variant="secondary" size="sm" @click="loadSampleWebinar">
                        Muat Contoh Webinar ({{ samplePreset.preset_code }})
                    </AppButton>
                    <Link
                        v-if="canReview"
                        href="/administrasi/proposal"
                        class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                    >
                        Antrean Telaah Reviewer
                    </Link>
                </div>
            </div>

            <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-xs text-sky-900">
                Portal ini berfokus pada telaah proposal, susunan panitia, anggaran terhitung, dan verifikasi LPJ. Tidak memproses pencairan dana/rekening bank (SS-D-009).
            </div>

            <AppCard>
                <AppButton variant="primary" type="button" @click="showIndependentProposalEditor = true">Isi proposal mandiri</AppButton>
<Teleport to="body"><div v-if="showIndependentProposalEditor" class="admin-form-overlay" role="dialog" aria-modal="true" aria-label="Isi proposal mandiri" @click.self="requestIndependentProposalEditorClose" @keydown.esc.stop.prevent="requestIndependentProposalEditorClose"><div class="admin-form-dialog"><header class="admin-form-header"><h2>Isi proposal mandiri</h2><AppButton type="button" icon="close" icon-only aria-label="Tutup isi proposal mandiri" title="Tutup formulir" :disabled="form.processing" @click="requestIndependentProposalEditorClose" /></header>
<form class="space-y-4 p-5" @submit.prevent="submitProposal">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--line)] pb-3">

                        <AppButton v-if="editingProposalId" variant="ghost" size="sm" @click="editingProposalId = null">Batal Revisi</AppButton>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <FormField id="ind-title" label="Judul Kegiatan" required :error="form.errors.title">
                            <input id="ind-title" v-model="form.title" type="text" required class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                        </FormField>

                        <FormField id="ind-type" label="Kategori Kegiatan" required :error="form.errors.activity_type">
                            <select id="ind-type" v-model="form.activity_type" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                                <option v-for="(label, key) in activityTypes" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </FormField>

                        <FormField id="ind-org" label="Nama Pemohon / Organisasi" required :error="form.errors.organizer_name">
                            <input id="ind-org" v-model="form.organizer_name" type="text" required class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                        </FormField>

                        <FormField id="ind-unit" label="Ekstrakurikuler / Unit Pendamping" :error="form.errors.organizer_unit">
                            <input id="ind-unit" v-model="form.organizer_unit" type="text" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                        </FormField>

                        <FormField id="ind-start" label="Tanggal Mulai" required :error="form.errors.start_date">
                            <input id="ind-start" v-model="form.start_date" type="date" required class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                        </FormField>

                        <FormField id="ind-end" label="Tanggal Selesai" :error="form.errors.end_date">
                            <input id="ind-end" v-model="form.end_date" type="date" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                        </FormField>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-[var(--ink)]">Susunan Panitia Pelaksana</span>
                            <AppButton variant="ghost" size="sm" @click="addCommittee">+ Tambah Panitia</AppButton>
                        </div>
                        <div v-for="(member, idx) in form.committee_members" :key="idx" class="grid gap-2 md:grid-cols-5">
                            <input v-model="member.role" type="text" placeholder="Jabatan (mis. Ketua)" class="md:col-span-2 rounded-lg border border-[var(--line)] px-3 py-1.5 text-sm">
                            <input v-model="member.name" type="text" placeholder="Nama lengkap / kelas" class="md:col-span-2 rounded-lg border border-[var(--line)] px-3 py-1.5 text-sm">
                            <AppButton v-if="form.committee_members.length > 1" variant="danger" size="sm" @click="removeCommittee(idx)">Hapus</AppButton>
                        </div>
                    </div>

                    <FormField id="ind-purpose" label="Tujuan & Latar Belakang Kegiatan" required :error="form.errors.purpose">
                        <textarea id="ind-purpose" v-model="form.purpose" rows="3" required class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                    </FormField>

                    <ProposalBudgetEditor v-model="form.budget_items" />
                    <p v-if="form.errors.budget_items" class="text-xs text-rose-600">{{ form.errors.budget_items }}</p>

                    <FormField id="ind-file" label="Lampiran Proposal (PDF/Foto)" :error="form.errors.attachment">
                        <input
                            id="ind-file"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-sm text-[var(--ink-soft)]"
                            @change="form.attachment = ($event.target as HTMLInputElement).files?.[0] ?? null"
                        >
                    </FormField>

                    <div class="flex justify-end">
                        <AppButton type="submit" variant="primary" :loading="form.processing">
                            {{ editingProposalId ? 'Kirim Revisi Proposal' : 'Ajukan Proposal Mandiri' }}
                        </AppButton>
                    </div>
                </form>
</div></div></Teleport>
            </AppCard>

            <AppCard>
                <div class="p-5 space-y-4">
                    <h2 class="text-base font-semibold text-[var(--ink)]">Pelacakan Proposal & Realisasi LPJ</h2>

                    <StateFeedback
                        v-if="proposals.length === 0"
                        state="empty"
                        title="Belum ada proposal mandiri"
                        description="Klik 'Muat Contoh Webinar' atau isi formulir untuk mengajukan kegiatan baru."
                    />

                    <div v-else class="space-y-4">
                        <div
                            v-for="item in proposals"
                            :key="item.id"
                            class="rounded-xl border border-[var(--line)] bg-slate-50/70 p-4 space-y-3"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-xs font-semibold text-[var(--ink-faint)]">{{ item.proposal_code }}</span>
                                        <StatusBadge :status="item.status" :label="`${item.status_label} (v${item.revision_number})`" :tone="toneForStatus(item.status)" />
                                    </div>
                                    <h3 class="mt-1 text-base font-semibold text-[var(--ink)]">{{ item.title }}</h3>
                                    <p class="text-xs text-[var(--ink-soft)]">
                                        {{ item.organizer_name }} · {{ item.start_date }} · RAB Diajukan: {{ formatCurrency(item.total_requested_amount) }}
                                        <template v-if="item.lpj_realization_amount !== null && item.lpj_realization_amount !== undefined">
                                            · Realisasi LPJ: <strong>{{ formatCurrency(item.lpj_realization_amount) }}</strong>
                                        </template>
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <AppButton v-if="item.status === 'needs_revision'" variant="warning" size="sm" @click="startRevision(item)">
                                        Perbaiki Proposal
                                    </AppButton>
                                    <AppButton v-if="item.status === 'approved' || item.status === 'lpj_submitted'" variant="primary" size="sm" @click="openLpj(item)">
                                        {{ item.status === 'lpj_submitted' ? 'Perbarui Bukti LPJ' : 'Unggah Bukti LPJ' }}
                                    </AppButton>
                                </div>
                            </div>

                            <div v-if="item.lpj_document_ids.length > 0" class="flex flex-wrap items-center gap-2 text-xs text-emerald-800">
                                <span>Berkas LPJ terunggah:</span>
                                <a
                                    v-for="(docId, idx) in item.lpj_document_ids"
                                    :key="docId"
                                    :href="`/proposal/${item.id}/dokumen/${docId}`"
                                    class="rounded bg-[var(--surface)] px-2 py-0.5 border border-emerald-200 font-medium hover:underline"
                                >
                                    Bukti #{{ idx + 1 }}
                                </a>
                            </div>

                            <Teleport to="body"><div v-if="selectedLpjProposalId === item.id" class="admin-form-overlay" role="dialog" aria-modal="true" aria-label="Unggah realisasi LPJ" @click.self="requestLpjClose" @keydown.esc.stop.prevent="requestLpjClose"><div class="admin-form-dialog"><header class="admin-form-header"><h2>Unggah realisasi LPJ</h2><AppButton type="button" icon="close" icon-only aria-label="Tutup unggah realisasi lpj" :disabled="lpjForm.processing" @click="requestLpjClose" /></header><form
                                v-if="selectedLpjProposalId === item.id"
                                class="mt-2 rounded-lg border border-emerald-200 bg-[var(--surface)] p-4 space-y-3"
                                @submit.prevent="submitLpj"
                            >
                                <h4 class="text-sm font-semibold text-[var(--ink)]">Pelaporan Bukti Realisasi LPJ</h4>
                                <div class="grid gap-3 md:grid-cols-2">
                                    <FormField id="ind-lpj-amount" label="Nominal Realisasi (Rp)" required :error="lpjForm.errors.lpj_realization_amount">
                                        <input id="ind-lpj-amount" v-model.number="lpjForm.lpj_realization_amount" type="number" min="0" required class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                                    </FormField>
                                    <FormField id="ind-lpj-proof" label="Bukti Kuitansi / LPJ (PDF/JPG/PNG)" required :error="lpjForm.errors.proof_file">
                                        <input
                                            id="ind-lpj-proof"
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            class="block w-full text-sm text-[var(--ink-soft)]"
                                            @change="lpjForm.proof_file = ($event.target as HTMLInputElement).files?.[0] ?? null"
                                        >
                                    </FormField>
                                </div>
                                <FormField id="ind-lpj-summary" label="Ringkasan Pelaksanaan & Realisasi" required :error="lpjForm.errors.lpj_summary">
                                    <textarea id="ind-lpj-summary" v-model="lpjForm.lpj_summary" rows="2" required class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                                </FormField>
                                <div class="flex justify-end gap-2">
                                    <AppButton variant="ghost" size="sm" @click="requestLpjClose">Batal</AppButton>
                                    <AppButton type="submit" variant="primary" size="sm" :loading="lpjForm.processing">Kirim LPJ</AppButton>
                                </div>
                            </form></div></div></Teleport>
                        </div>
                    </div>
                </div>
            </AppCard>
        </div>
    </AppLayout>
</template>
