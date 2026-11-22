<script setup lang="ts">
import { requestConfirmation } from '@/composables/useActionConfirmation';
import { watchPostEffect, onUnmounted } from 'vue';
import AppButton from '@/components/AppButton.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, reactive, ref, watch } from 'vue';

interface StatusOption { value: string; label: string }
const expandedActions = reactive<Record<number, boolean>>({});
interface Signatory { id: number; name: string; title: string; employee_number?: string | null; rank?: string | null; template_scopes?: string[] | null; provider_configured?: boolean }
interface CorrespondenceRecord {
    id: number;
    letter_type: 'incoming' | 'outgoing';
    agenda_number: string | null;
    letter_number: string | null;
    letter_template: string;
    classification: string | null;
    urgency: string | null;
    letter_date: string | null;
    received_at: string | null;
    sender: string | null;
    recipient: string | null;
    subject: string;
    summary: string | null;
    disposition: string | null;
    status: string;
    approval_required: boolean;
    approval_status: string;
    approved_at: string | null;
    approval_note: string | null;
    signature_method: string;
    document_hash: string | null;
    attachment_name: string | null;
    has_attachment: boolean;
    archived_at: string | null;
    has_document: boolean;
    letter_body: string | null;
    signer_name: string | null;
    signer_title: string | null;
    signer_identifier_type: string | null;
    signer_identifier: string | null;
    attachment_note: string | null;
    recipient_address?: string | null;
    opening_greeting?: string | null;
    closing_text?: string | null;
    carbon_copy?: string | null;
    signatory_id?: number | null;
    signature_status?: string | null;
    signature_request_id?: number | null;
    signature_error?: string | null;
    signature_error_message?: string | null;
    signature_provider_request_id?: string | null;
    signature_verified?: boolean;
    signature_certificate_subject?: string | null;
    signature_signed_at?: string | null;
}

const props = defineProps<{
    records: CorrespondenceRecord[];
    filter: 'all' | 'incoming' | 'outgoing' | 'archived';
    status_options: { incoming: StatusOption[]; outgoing: StatusOption[] };
    can_manage: boolean;
    queue_counts?: Record<string, number>;
    signatories?: Signatory[];
    tte_status?: { enabled: boolean; configured: boolean; queue: string };
}>();

const pageTitle = computed(() => ({
    all: 'Dashboard Persuratan',
    incoming: 'Surat Masuk',
    outgoing: 'Surat Keluar',
    archived: 'Arsip Surat',
}[props.filter] ?? 'Persuratan'));
const pageDescription = computed(() => ({
    all: 'Pantau antrean surat dan lanjutkan pekerjaan TU dari satu tempat.',
    incoming: 'Catat, koreksi, disposisikan, dan selesaikan surat yang diterima sekolah.',
    outgoing: 'Susun draf, ajukan persetujuan, tanda tangani, lalu kirim surat sekolah.',
    archived: 'Telusuri surat yang sudah selesai dan tersimpan sebagai arsip sekolah.',
}[props.filter] ?? 'Kelola persuratan sekolah.'));

const page = usePage();
const flashMessage = computed(() => (page.props.flash as { message?: string }).message);
const form = useForm({
    letter_type: 'incoming' as 'incoming' | 'outgoing',
    letter_number: '',
    letter_template: 'official_school',
    classification: '',
    urgency: 'ordinary',
    letter_date: '',
    received_at: '',
    sender: '',
    recipient: '',
    recipient_address: '',
    subject: '',
    summary: '',
    disposition: '',
    disposition_target: '',
    letter_body: '',
    opening_greeting: 'Dengan hormat,',
    closing_text: 'Demikian surat ini disampaikan. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.',
    carbon_copy: '',
    signatory_id: null as number | null,
    signer_name: '',
    signer_title: '',
    signer_identifier_type: 'nip',
    signer_identifier: '',
    attachment_note: '',
    approval_required: false,
    signature_method: 'manual',
    attachment: null as File | null,
});

const editingId = ref<number | null>(null);
const editingType = ref<'incoming' | 'outgoing' | null>(null);
const existingAttachment = ref(false);
const creating = ref(false);
const creatingSignatory = ref(false);
const signatoryForm = useForm({ name: '', title: '', employee_number: '', rank: '', provider_reference: '', template_scopes: [] as string[] });
const wizardStep = ref(1);
const wizardError = ref('');
const activeSection = ref<'all' | 'incoming' | 'outgoing' | 'approval' | 'signature' | 'ready' | 'archived'>(props.filter === 'outgoing' || props.filter === 'archived' ? props.filter : props.filter === 'incoming' ? 'incoming' : 'all');
const search = ref('');
const dashboardDateFrom = ref('');
const dashboardDateTo = ref('');
const dashboardType = ref<'all' | 'incoming' | 'outgoing'>('all');
const dashboardStorage = ref<'all' | 'saved' | 'archived'>('all');
const archiveType = ref<'all' | 'incoming' | 'outgoing'>('all');
const archiveDateBasis = ref<'letter' | 'archived'>('archived');
const archiveDateFrom = ref('');
const archiveDateTo = ref('');
const currentPage = ref(1);
const approvalNotes = reactive<Record<number, string>>({});
const actionErrors = reactive<Record<number, string>>({});
const actionBusy = reactive<Record<number, boolean>>({});
const availableSignatories = computed(() => (props.signatories ?? []).filter((signer) => {
    const scopes = signer.template_scopes ?? [];
    return scopes.length === 0 || scopes.includes(form.letter_template);
}));
const selectedSignatory = computed(() => availableSignatories.value.find((signer) => signer.id === form.signatory_id));
const tteConfigured = computed(() => props.tte_status?.configured === true);
const sectionRecords = computed(() => props.records.filter((record) => {
    if (activeSection.value === 'all') return true;
    if (activeSection.value === 'archived') return record.status === 'archived';
    if (activeSection.value === 'ready') return record.letter_type === 'outgoing' && record.status === 'issued';
    if (activeSection.value === 'approval') return record.letter_type === 'outgoing' && record.status === 'pending_approval';
    if (activeSection.value === 'signature') return record.letter_type === 'outgoing' && ['approved', 'awaiting_signature', 'signature_pending', 'signature_failed'].includes(record.status);
    return record.letter_type === activeSection.value && record.status !== 'archived';
}));
const filteredRecords = computed(() => {
    const query = search.value.trim().toLocaleLowerCase();
    let records = sectionRecords.value;
    if (props.filter === 'all') {
        records = records.filter((record) => {
            const date = record.letter_date || record.received_at || '';
            const matchesFrom = !dashboardDateFrom.value || date >= dashboardDateFrom.value;
            const matchesTo = !dashboardDateTo.value || date <= dashboardDateTo.value;
            const matchesType = dashboardType.value === 'all' || record.letter_type === dashboardType.value;
            const matchesStorage = dashboardStorage.value === 'all'
                || (dashboardStorage.value === 'archived' ? record.status === 'archived' : record.status !== 'archived');
            return matchesFrom && matchesTo && matchesType && matchesStorage;
        });
    }
    if (props.filter === 'archived') {
        records = records.filter((record) => {
            const letterDate = record.letter_date || record.received_at || '';
            const archivedDate = record.archived_at?.slice(0, 10) || '';
            const matchesType = archiveType.value === 'all' || record.letter_type === archiveType.value;
            const date = archiveDateBasis.value === 'letter' ? letterDate : archivedDate;
            return matchesType && (!archiveDateFrom.value || date >= archiveDateFrom.value)
                && (!archiveDateTo.value || date <= archiveDateTo.value);
        });
    }
    return query ? records.filter((record) => [record.subject, record.letter_number, record.agenda_number, record.sender, record.recipient].some((value) => value?.toLocaleLowerCase().includes(query))) : records;
});
const pageCount = computed(() => Math.max(1, Math.ceil(filteredRecords.value.length / 10)));
const visibleRecords = computed(() => filteredRecords.value.slice((currentPage.value - 1) * 10, currentPage.value * 10));
const archiveCounts = computed(() => {
    const records = props.records.filter((record) => record.status === 'archived');
    return {
        total: records.length,
        incoming: records.filter((record) => record.letter_type === 'incoming').length,
        outgoing: records.filter((record) => record.letter_type === 'outgoing').length,
    };
});
watch([search, activeSection, dashboardDateFrom, dashboardDateTo, dashboardType, dashboardStorage, archiveType, archiveDateBasis, archiveDateFrom, archiveDateTo, () => props.filter, filteredRecords], () => {
    currentPage.value = Math.min(currentPage.value, pageCount.value);
    if (search.value || activeSection.value) currentPage.value = 1;
});
watch(() => form.letter_template, () => {
    if (form.signatory_id && !availableSignatories.value.some((signer) => signer.id === form.signatory_id)) {
        form.signatory_id = null;
    }
});

function startNew(type: 'incoming' | 'outgoing') {
    editingId.value = null;
    editingType.value = null;
    existingAttachment.value = false;
    form.reset();
    form.clearErrors();
    form.letter_type = type;
    creating.value = true; wizardStep.value = 1; wizardError.value = '';
    nextTick(() => document.getElementById('form-surat')?.scrollIntoView({ behavior: 'smooth' }));
}

function editDraft(record: CorrespondenceRecord) {
    editingId.value = record.id;
    editingType.value = 'outgoing';
    existingAttachment.value = record.has_attachment;
    form.reset();
    form.clearErrors();
    form.letter_type = 'outgoing';
    form.letter_number = record.letter_number ?? '';
    form.letter_template = record.letter_template || 'official_school';
    form.classification = record.classification ?? '';
    form.urgency = record.urgency ?? 'ordinary';
    form.letter_date = record.letter_date ?? '';
    form.recipient = record.recipient ?? '';
    form.recipient_address = record.recipient_address ?? '';
    form.subject = record.subject;
    form.summary = record.summary ?? '';
    form.letter_body = record.letter_body ?? '';
    form.opening_greeting = record.opening_greeting ?? 'Dengan hormat,';
    form.closing_text = record.closing_text ?? '';
    form.carbon_copy = record.carbon_copy ?? '';
    form.signatory_id = record.signatory_id ?? null;
    form.signer_name = record.signer_name ?? '';
    form.signer_title = record.signer_title ?? '';
    form.signer_identifier_type = record.signer_identifier_type ?? 'nip';
    form.signer_identifier = record.signer_identifier ?? '';
    form.attachment_note = record.attachment_note ?? '';
    form.approval_required = record.approval_required;
    form.signature_method = record.signature_method || 'manual';
    creating.value = true;
    wizardStep.value = 1;
    wizardError.value = '';
    nextTick(() => document.getElementById('form-surat')?.scrollIntoView({ behavior: 'smooth' }));
}

function editIncoming(record: CorrespondenceRecord) {
    editingId.value = record.id;
    editingType.value = 'incoming';
    existingAttachment.value = record.has_attachment;
    form.reset();
    form.clearErrors();
    form.letter_type = 'incoming';
    form.letter_number = record.letter_number ?? '';
    form.letter_date = record.letter_date ?? '';
    form.received_at = record.received_at ?? '';
    form.sender = record.sender ?? '';
    form.subject = record.subject;
    form.summary = record.summary ?? '';
    form.disposition = record.disposition ?? '';
    creating.value = true;
    wizardStep.value = 1;
    wizardError.value = '';
    nextTick(() => document.getElementById('form-surat')?.scrollIntoView({ behavior: 'smooth' }));
}

const targets = reactive<Record<number, string>>({});
const dispositions = reactive<Record<number, string>>({});
const statusErrors = reactive<Record<number, string>>({});

const maxStep = computed(() => form.letter_type === 'incoming' ? 3 : 5);
const stepLabels = computed(() => form.letter_type === 'incoming' ? ['Identitas', 'Dokumen', 'Periksa'] : ['Jenis', 'Identitas', 'Naskah', 'Tanda tangan', 'Periksa']);
const wizardHint = computed(() => {
    if (form.letter_type === 'incoming') {
        return ({
            1: 'Mulai dari data yang terlihat di surat fisik.',
            2: 'Ringkasan membantu penerima memahami isi tanpa membuka lampiran.',
            3: 'Periksa kembali sebelum surat disimpan sebagai Diterima.',
        } as Record<number, string>)[wizardStep.value];
    }

    return ({
        1: 'Pilih format yang paling dekat dengan kebutuhan surat.',
        2: 'Lengkapi nomor, tujuan, sifat, dan perihal surat.',
        3: 'Tulis naskah yang akan masuk ke PDF resmi sekolah.',
        4: 'Pastikan pejabat dan metode tanda tangan sudah tepat.',
        5: 'Tinjau ringkasan sebelum menyimpan draf.',
    } as Record<number, string>)[wizardStep.value];
});
const queueCounts = computed(() => ({
    incoming: props.queue_counts?.incoming_new ?? props.records.filter(r => r.letter_type === 'incoming' && r.status === 'received').length,
    disposition: props.queue_counts?.awaiting_disposition ?? props.records.filter(r => r.letter_type === 'incoming' && r.status === 'disposition').length,
    draft: props.queue_counts?.draft ?? props.records.filter(r => r.letter_type === 'outgoing' && r.status === 'draft').length,
    approval: props.queue_counts?.awaiting_approval ?? props.records.filter(r => r.status === 'pending_approval').length,
    signature: props.queue_counts?.awaiting_signature ?? props.records.filter(r => ['approved', 'awaiting_signature', 'signature_pending', 'signature_failed'].includes(r.status)).length,
    ready: props.queue_counts?.ready_to_send ?? props.records.filter(r => r.status === 'issued').length,
    archived: props.queue_counts?.archived ?? props.records.filter(r => r.status === 'archived').length,
}));
const incomingCurrentStage = computed(() => {
    const receivedRecords = props.records.filter((record) => record.letter_type === 'incoming' && record.status === 'received');
    if (receivedRecords.some((record) => !record.summary && !record.has_attachment)) return 'recorded';
    if (queueCounts.value.incoming > 0) return 'complete';
    if (queueCounts.value.disposition > 0) return 'disposition';
    if (props.records.some((record) => record.letter_type === 'incoming' && record.status === 'done')) return 'done';
    return 'archived';
});
const outgoingCurrentStage = computed(() => {
    if (queueCounts.value.draft > 0) return 'draft';
    if (queueCounts.value.approval > 0) return 'approval';
    if (queueCounts.value.signature > 0) return 'signature';
    if (queueCounts.value.ready > 0) return 'issued';
    return 'sent';
});

function selectSignatory() {
    const signer = availableSignatories.value.find(item => item.id === form.signatory_id);
    if (signer) { form.signer_name = signer.name; form.signer_title = signer.title; form.signer_identifier = signer.employee_number ?? ''; }
}
function saveSignatory() {
    signatoryForm.post('/administrasi/persuratan/penandatangan', {
        preserveScroll: true,
        onSuccess: () => { signatoryForm.reset(); creatingSignatory.value = false; },
    });
}
function validateStep() {
    wizardError.value = '';
    if (form.letter_type === 'incoming' && wizardStep.value === 1 && (!form.sender || !form.subject || !form.received_at)) wizardError.value = 'Lengkapi asal surat, perihal, dan tanggal diterima.';
    if (form.letter_type === 'incoming' && wizardStep.value === 2 && !form.summary && !form.attachment && !existingAttachment.value) wizardError.value = 'Isi ringkasan atau unggah dokumen surat.';
    if (form.letter_type === 'outgoing' && wizardStep.value === 2 && (!form.letter_number || !form.letter_date || !form.recipient || !form.subject)) wizardError.value = 'Lengkapi nomor, tanggal, tujuan, dan perihal surat.';
    if (form.letter_type === 'outgoing' && wizardStep.value === 3 && !form.letter_body.trim()) wizardError.value = 'Isi naskah surat sebelum melanjutkan.';
    if (form.letter_type === 'outgoing' && wizardStep.value === 4 && (!form.signer_name || !form.signer_title)) wizardError.value = 'Pilih atau lengkapi penandatangan.';
    if (form.letter_type === 'outgoing' && wizardStep.value === 4 && form.signature_method === 'bsre' && !form.signatory_id) wizardError.value = 'Pilih pejabat penandatangan aktif untuk TTE BSrE.';
    if (form.letter_type === 'outgoing' && wizardStep.value === 4 && form.signature_method === 'bsre' && !selectedSignatory.value?.provider_configured) wizardError.value = 'Lengkapi identitas provider BSrE pada profil penandatangan sebelum memilih TTE.';
    return !wizardError.value;
}
function nextStep() { if (validateStep()) wizardStep.value = Math.min(maxStep.value, wizardStep.value + 1); }
function previousStep() { wizardError.value = ''; wizardStep.value = Math.max(1, wizardStep.value - 1); }

function nextStatuses(record: CorrespondenceRecord): StatusOption[] {
    const next = record.letter_type === 'incoming'
        ? ({ received: 'disposition', disposition: 'done', done: 'archived' } as Record<string, string>)[record.status]
        : ({ issued: 'sent', sent: 'archived', ...(record.has_document ? {} : { draft: 'sent' }) } as Record<string, string>)[record.status];
    return props.status_options[record.letter_type].filter((item) => item.value === next);
}

function labelForStatus(record: CorrespondenceRecord): string {
    return props.status_options[record.letter_type].find((item) => item.value === record.status)?.label ?? record.status;
}

function nextActionLabel(record: CorrespondenceRecord): string {
    const next = nextStatuses(record)[0]?.value;
    return ({
        disposition: 'Teruskan disposisi',
        done: 'Tandai selesai',
        archived: 'Arsipkan surat',
        issued: 'Terbitkan surat',
        sent: 'Tandai terkirim',
    } as Record<string, string>)[next ?? ''] ?? 'Perbarui status';
}

function saveRecord() {
    if (editingId.value !== null) {
        if (editingType.value === 'incoming') {
            form.patch(`/administrasi/persuratan/surat/${editingId.value}/masuk`, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => { form.reset(); editingId.value = null; editingType.value = null; creating.value = false; },
            });
            return;
        }
        form.patch(`/administrasi/persuratan/surat/${editingId.value}/draf`, {
            preserveScroll: true,
            onSuccess: () => { form.reset(); editingId.value = null; editingType.value = null; creating.value = false; },
        });
        return;
    }
    form.post('/administrasi/persuratan/surat', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { form.reset(); editingType.value = null; creating.value = false; },
    });
}

async function approveRecord(record: CorrespondenceRecord, decision: 'approve' | 'reject') {
    const message = decision === 'approve' ? 'Setujui surat ini untuk diterbitkan?' : 'Tolak surat ini agar operator memperbaikinya?';
    if (!await requestConfirmation(message)) return;
    actionBusy[record.id] = true;
    delete actionErrors[record.id];
    router.patch(`/administrasi/persuratan/surat/${record.id}/persetujuan`, { decision, note: approvalNotes[record.id] ?? '' }, {
        preserveScroll: true,
        onFinish: () => { actionBusy[record.id] = false; },
        onError: (errors) => { actionErrors[record.id] = errors.approval || errors.note || 'Keputusan persetujuan gagal disimpan.'; },
    });
}

function setAttachment(event: Event) {
    form.attachment = (event.target as HTMLInputElement).files?.[0] ?? null;
}

async function updateStatus(record: CorrespondenceRecord) {
    const status = targets[record.id] || nextStatuses(record)[0]?.value;
    if (!status) return;
    if (status === 'issued' && !await requestConfirmation('Finalkan surat untuk membuat QR? Isi surat tidak dapat diubah setelah difinalkan.')) return;
    if (record.letter_type === 'outgoing' && status === 'sent' && !await requestConfirmation('Pastikan surat sudah ditandatangani dan dikirim di luar aplikasi. Tandai surat sebagai terkirim?')) return;

    router.patch(`/administrasi/persuratan/surat/${record.id}/status`, {
        status,
        disposition: dispositions[record.id] ?? '',
    }, {
        preserveScroll: true,
        onSuccess: () => { delete statusErrors[record.id]; delete targets[record.id]; },
        onError: (errors) => { statusErrors[record.id] = errors.status || errors.disposition || 'Status surat gagal diperbarui.'; },
    });
}

async function signatureAction(record: CorrespondenceRecord, action: 'manual-prepare' | 'bsre' | 'bsre-retry') {
    if (action !== 'manual-prepare') {
        if (!tteConfigured.value) {
            actionErrors[record.id] = 'TTE BSrE belum dikonfigurasi. Hubungi administrator sistem.';
            return;
        }
        const message = action === 'bsre'
            ? 'Kirim surat ini ke antrean TTE BSrE? Setelah diproses, isi PDF final tidak dapat diubah.'
            : 'Jadwalkan ulang permintaan TTE ini? Sistem akan memakai kunci idempotensi yang sama.';
        if (!await requestConfirmation(message)) return;
    }
    const path = action === 'manual-prepare'
        ? `/administrasi/persuratan/surat/${record.id}/tanda-tangan-manual`
        : action === 'bsre'
            ? `/administrasi/persuratan/surat/${record.id}/tte-bsre`
            : `/administrasi/persuratan/tte-bsre/${record.signature_request_id}/ulangi`;
    actionBusy[record.id] = true;
    delete actionErrors[record.id];
    router.post(path, {}, {
        preserveScroll: true,
        onFinish: () => { actionBusy[record.id] = false; },
        onError: (errors) => { actionErrors[record.id] = errors.status || errors.signatory_id || errors.signature || 'Tindakan tanda tangan gagal dilakukan.'; },
    });
}
function uploadManualSignature(record: CorrespondenceRecord, event: Event) {
    const signed_document = (event.target as HTMLInputElement).files?.[0];
    if (signed_document) {
        actionBusy[record.id] = true;
        delete actionErrors[record.id];
        router.post(`/administrasi/persuratan/surat/${record.id}/hasil-tanda-tangan-manual`, { signed_document }, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => { actionBusy[record.id] = false; },
            onError: (errors) => { actionErrors[record.id] = errors.signed_document || 'Dokumen tanda tangan gagal diunggah.'; },
        });
    }
}

function submitApproval(record: CorrespondenceRecord) {
    actionBusy[record.id] = true;
    delete actionErrors[record.id];
    router.post(`/administrasi/persuratan/surat/${record.id}/ajukan-persetujuan`, {}, {
        preserveScroll: true,
        onFinish: () => { actionBusy[record.id] = false; },
        onError: (errors) => { actionErrors[record.id] = errors.approval || 'Surat belum siap diajukan untuk persetujuan.'; },
    });
}

let dialogTrigger: HTMLElement | null = null;
let activeDialog: HTMLElement | null = null;
const dialogControls = () => Array.from(activeDialog?.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), a[href], summary') ?? []).filter(element => element.getClientRects().length > 0);
const trapDialogFocus = (event: KeyboardEvent) => {
    if (!activeDialog || event.key !== 'Tab') return;
    const controls = dialogControls();
    const first = controls[0];
    const last = controls.at(-1);
    if (first && last && (!activeDialog.contains(document.activeElement) || (event.shiftKey ? document.activeElement === first : document.activeElement === last))) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
    }
};
watchPostEffect(() => {
    const open = creating.value;
    if (open && !activeDialog) {
        dialogTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        activeDialog = Array.from(document.querySelectorAll<HTMLElement>('[role="dialog"]')).at(-1) ?? null;
        dialogControls()[0]?.focus();
        document.addEventListener('keydown', trapDialogFocus);
    } else if (!open && activeDialog) {
        document.removeEventListener('keydown', trapDialogFocus);
        activeDialog = null;
        if (dialogTrigger?.isConnected) dialogTrigger.focus();
    }
});
onUnmounted(() => document.removeEventListener('keydown', trapDialogFocus));

</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout :title="pageTitle">
        <div :class="['view', 'space-y-6', 'correspondence-page', { 'incoming-page': props.filter === 'incoming', 'outgoing-page': props.filter === 'outgoing', 'archive-page': props.filter === 'archived' }]">
            <nav class="module-tabs" aria-label="Submenu Persuratan">
                <Link href="/administrasi/persuratan" class="module-tab" :class="{ 'is-active': props.filter === 'all' }">Dashboard Persuratan</Link>
                <Link href="/administrasi/persuratan/surat-masuk" class="module-tab" :class="{ 'is-active': props.filter === 'incoming' }">Surat Masuk</Link>
                <Link href="/administrasi/persuratan/surat-keluar" class="module-tab" :class="{ 'is-active': props.filter === 'outgoing' }">Surat Keluar</Link>
                <Link href="/administrasi/persuratan/arsip" class="module-tab" :class="{ 'is-active': props.filter === 'archived' }">Arsip Surat</Link>
            </nav>

            <header class="correspondence-heading">
                <div>
                    <p class="eyebrow">Administrasi sekolah · Tata Usaha</p>
                    <h1>{{ pageTitle }}</h1>
                </div>
                <div v-if="can_manage && props.filter !== 'archived'" class="correspondence-header-actions">
                    <AppButton variant="primary" v-if="props.filter !== 'outgoing'" type="button" class="primary-button" @click="startNew('incoming')">Catat surat masuk</AppButton>
                    <AppButton variant="secondary" v-if="props.filter !== 'incoming'" type="button" class="secondary-button" @click="startNew('outgoing')">Buat surat keluar</AppButton>
                </div>
            </header>

            <section v-if="props.filter === 'incoming'" class="ss-summary-grid ss-summary-grid--3" aria-label="Ringkasan surat masuk">
                <MetricSummaryCard
                    label="Surat masuk aktif"
                    :value="queueCounts.incoming + queueCounts.disposition"
                    note="total sedang berjalan"
                    tone="blue"
                    icon="M4 4h16v16H4zM7 8h10M7 12h7M7 16h5"
                />
                <MetricSummaryCard
                    label="Baru diterima"
                    :value="queueCounts.incoming"
                    note="perlu dilengkapi"
                    tone="violet"
                    icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    label="Menunggu disposisi"
                    :value="queueCounts.disposition"
                    note="dalam tindak lanjut"
                    tone="amber"
                    icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                />
            </section>
            <section v-else-if="props.filter === 'outgoing'" class="ss-summary-grid" aria-label="Ringkasan surat keluar">
                <MetricSummaryCard
                    label="Draf"
                    :value="queueCounts.draft"
                    note="perlu dilengkapi"
                    tone="blue"
                    icon="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
                <MetricSummaryCard
                    label="Persetujuan"
                    :value="queueCounts.approval"
                    note="sudah diajukan"
                    tone="amber"
                    icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    label="Tanda tangan"
                    :value="queueCounts.signature"
                    note="manual atau BSrE"
                    tone="violet"
                    icon="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                />
                <MetricSummaryCard
                    label="Siap dikirim"
                    :value="queueCounts.ready"
                    note="dokumen final"
                    tone="green"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </section>
            <section v-else-if="props.filter === 'archived'" class="ss-summary-grid ss-summary-grid--3" aria-label="Ringkasan arsip surat">
                <MetricSummaryCard
                    label="Total arsip"
                    :value="archiveCounts.total"
                    note="surat tersimpan"
                    tone="amber"
                    icon="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"
                />
                <MetricSummaryCard
                    label="Surat masuk"
                    :value="archiveCounts.incoming"
                    note="arsip diterima"
                    tone="blue"
                    icon="M4 4h16v16H4zM7 8h10M7 12h7M7 16h5"
                />
                <MetricSummaryCard
                    label="Surat keluar"
                    :value="archiveCounts.outgoing"
                    note="arsip diterbitkan"
                    tone="green"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </section>




            <p v-if="flashMessage" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                {{ flashMessage }}
            </p>


            <details v-if="props.filter !== 'archived'" class="panel correspondence-panel">
                <summary>Panduan persuratan</summary>
                <p class="workflow-help">Surat masuk: catat → disposisi → selesai → arsip.</p>
                <p class="workflow-help">Surat keluar: draf → persetujuan → tanda tangan → terbit → terkirim → arsip.</p>
                <p class="workflow-help">Verifikasi QR memeriksa metadata dan integritas berkas final. Pada jalur manual, QR bukan tanda tangan elektronik tersertifikasi.</p>
            </details>

            <div v-if="can_manage && creating" role="dialog" aria-modal="true" class="modal-backdrop" @click.self="creating = false" @keydown.esc="creating = false">
            <section id="form-surat" class="panel correspondence-panel wizard-panel modal-card" tabindex="-1">
                <div class="wizard-heading"><div><p class="eyebrow">{{ form.letter_type === 'incoming' ? 'Surat masuk' : 'Surat keluar' }}</p><h2>{{ editingType === 'incoming' ? 'Koreksi data surat masuk' : editingType === 'outgoing' ? 'Perbaiki draf surat keluar' : form.letter_type === 'incoming' ? 'Catat surat masuk' : 'Buat surat keluar' }}</h2></div><AppButton variant="ghost" type="button" class="wizard-close" aria-label="Tutup formulir" @click="creating = false">×</AppButton></div>
                <ol class="stepper" :aria-label="`Langkah ${wizardStep} dari ${maxStep}`">
                    <li v-for="(label, index) in stepLabels" :key="label" :class="{ active: wizardStep === index + 1, done: wizardStep > index + 1 }"><span>{{ index + 1 }}</span><small>{{ label }}</small></li>
                </ol>
                <p v-if="wizardHint" class="wizard-step-hint">{{ wizardHint }}</p>
                <form class="wizard-form" enctype="multipart/form-data" @submit.prevent="saveRecord">
                    <template v-if="form.letter_type === 'incoming'">
                        <div v-if="wizardStep === 1" class="wizard-grid"><label>Nomor surat<input v-model="form.letter_number" maxlength="100" /></label><label>Tanggal surat<input v-model="form.letter_date" type="date" /></label><label>Asal surat <b>*</b><input v-model="form.sender" maxlength="180" /></label><label>Tanggal diterima <b>*</b><input v-model="form.received_at" type="date" /></label><label class="wide">Perihal <b>*</b><input v-model="form.subject" maxlength="200" /></label></div>
                        <div v-else-if="wizardStep === 2" class="wizard-grid"><label class="wide">Ringkasan isi<textarea v-model="form.summary" rows="6" maxlength="5000" /></label><label class="wide">Dokumen surat (PDF, DOCX, JPG/PNG; maks. 10 MB)<span v-if="existingAttachment" class="field-hint">Lampiran tersimpan tetap digunakan jika tidak memilih berkas baru.</span><input type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" @change="setAttachment" /></label></div>
                        <div v-else-if="wizardStep === 3" class="incoming-review-note wide"><strong>Surat akan disimpan sebagai “Diterima”.</strong><span>Setelah tersimpan, teruskan disposisi dari tindakan pada baris surat agar arahan dan penerima tercatat jelas.</span></div>
                    </template>
                    <template v-else>
                        <div v-if="wizardStep === 1" class="template-choices"><label v-for="item in [{v:'official_school',l:'Surat resmi',d:'Korespondensi umum sekolah'},{v:'invitation',l:'Undangan',d:'Rapat atau kegiatan'},{v:'assignment',l:'Surat tugas',d:'Penugasan resmi'},{v:'statement',l:'Surat pernyataan',d:'Pernyataan resmi sekolah'}]" :key="item.v" :class="{ selected: form.letter_template === item.v }"><input v-model="form.letter_template" type="radio" :value="item.v" /><span><strong>{{ item.l }}</strong><small>{{ item.d }}</small></span></label></div>
                        <div v-else-if="wizardStep === 2" class="wizard-grid"><label>Nomor surat <b>*</b><input v-model="form.letter_number" maxlength="100" placeholder="001/TU/X/2026" /></label><label>Tanggal surat <b>*</b><input v-model="form.letter_date" type="date" /></label><label>Tujuan surat <b>*</b><input v-model="form.recipient" maxlength="180" /></label><label>Sifat<select v-model="form.urgency"><option value="ordinary">Biasa</option><option value="urgent">Segera</option><option value="immediate">Sangat segera</option></select></label><label class="wide">Alamat tujuan<textarea v-model="form.recipient_address" rows="2" /></label><label class="wide">Perihal <b>*</b><input v-model="form.subject" maxlength="200" /></label><label>Klasifikasi<input v-model="form.classification" maxlength="40" /></label><label>Keterangan lampiran<input v-model="form.attachment_note" maxlength="180" placeholder="1 berkas" /></label><label class="wide">Berkas lampiran <span class="field-hint">PDF, DOCX, XLSX, JPG/PNG; maksimal 10 MB</span><input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" @change="setAttachment" /></label></div>
                        <div v-else-if="wizardStep === 3" class="wizard-grid"><label class="wide">Salam pembuka<input v-model="form.opening_greeting" maxlength="250" /></label><label class="wide">Isi surat <b>*</b><textarea v-model="form.letter_body" rows="10" maxlength="20000" placeholder="Tulis isi dalam paragraf yang singkat dan jelas." /></label><label class="wide">Penutup<textarea v-model="form.closing_text" rows="3" /></label><label class="wide">Tembusan<textarea v-model="form.carbon_copy" rows="2" placeholder="Satu penerima per baris" /></label></div>
                        <div v-else-if="wizardStep === 4" class="wizard-grid"><p v-if="form.signature_method === 'bsre' && !tteConfigured" class="form-note warning-note wide" role="status">TTE BSrE belum tersedia. Hubungi administrator atau pilih tanda tangan manual.</p><label v-if="availableSignatories.length" class="wide">Pejabat penandatangan<select v-model="form.signatory_id" @change="selectSignatory"><option :value="null">Pilih pejabat</option><option v-for="signer in availableSignatories" :key="signer.id" :value="signer.id">{{ signer.name }} — {{ signer.title }}{{ signer.employee_number ? ` · NIP ${signer.employee_number}` : '' }}{{ signer.provider_configured ? ' · TTE terhubung' : ' · TTE belum terhubung' }}</option></select></label><p v-else class="form-note wide">Belum ada pejabat aktif yang berwenang untuk template ini. Tambahkan profil pejabat atau pilih template lain.</p><p v-if="form.signature_method === 'bsre' && selectedSignatory && !selectedSignatory.provider_configured" class="form-note warning-note wide">Pejabat ini belum memiliki identitas provider BSrE. Minta administrator melengkapinya sebelum mengirim surat.</p><AppButton variant="secondary" class="secondary-button signatory-toggle" type="button" @click="creatingSignatory = !creatingSignatory">{{ creatingSignatory ? 'Tutup profil pejabat' : 'Tambah profil pejabat' }}</AppButton><div v-if="creatingSignatory" class="signatory-form wide"><label>Nama lengkap<input v-model="signatoryForm.name" /></label><label>Jabatan<input v-model="signatoryForm.title" /></label><label>NIP<input v-model="signatoryForm.employee_number" /></label><label>Pangkat/golongan<input v-model="signatoryForm.rank" /></label><label class="wide">ID identitas provider BSrE <span class="field-hint">Diisi dari akun/sertifikat BSrE, bukan NIP.</span><input v-model="signatoryForm.provider_reference" maxlength="190" placeholder="akun-bsre-kepala-sekolah" /></label><label class="wide">Kewenangan template<select v-model="signatoryForm.template_scopes" multiple><option value="official_school">Surat resmi</option><option value="invitation">Undangan</option><option value="assignment">Surat tugas</option><option value="statement">Surat keterangan</option></select></label><AppButton variant="primary" :loading="signatoryForm.processing" class="primary-button" type="button" :disabled="signatoryForm.processing" @click="saveSignatory">Simpan profil pejabat</AppButton><p v-if="Object.keys(signatoryForm.errors).length" class="field-error">Periksa nama, jabatan, identitas BSrE, dan kewenangan penandatangan.</p></div><label>Nama penandatangan <b>*</b><input v-model="form.signer_name" maxlength="180" /></label><label>Jabatan <b>*</b><input v-model="form.signer_title" maxlength="180" /></label><label>Jenis nomor identitas<select v-model="form.signer_identifier_type"><option value="nip">NIP</option><option value="nuptk">NUPTK</option><option value="npk">NPK</option></select></label><label>Nomor identitas<input v-model="form.signer_identifier" maxlength="80" /></label><fieldset class="signature-options wide"><legend>Metode tanda tangan</legend><label :class="{ selected: form.signature_method === 'manual' }"><input v-model="form.signature_method" type="radio" value="manual" /><span><strong>Tanda tangan basah</strong><small>Cetak dan unggah kembali hasil pindai PDF.</small></span></label><label :class="{ selected: form.signature_method === 'bsre', 'is-disabled': !tteConfigured }"><input v-model="form.signature_method" type="radio" value="bsre" :disabled="!tteConfigured" /><span><strong>TTE BSrE</strong><small>{{ tteConfigured ? 'Ditandatangani melalui layanan BSrE resmi.' : 'Belum tersedia sampai provider BSrE dikonfigurasi.' }}</small></span></label></fieldset><label class="wide checkbox-label"><input v-model="form.approval_required" type="checkbox" /> Minta persetujuan kepala sekolah</label></div>
                    </template>
                    <div v-if="wizardStep === maxStep" class="review-card"><div><span>Nomor</span><strong>{{ form.letter_number || 'Nomor agenda otomatis' }}</strong></div><div><span>Perihal</span><strong>{{ form.subject }}</strong></div><div><span>{{ form.letter_type === 'incoming' ? 'Asal' : 'Tujuan' }}</span><strong>{{ form.sender || form.recipient }}</strong></div><div v-if="form.letter_type === 'outgoing'"><span>Penandatangan</span><strong>{{ form.signer_name }} · {{ form.signature_method === 'bsre' ? 'TTE BSrE' : 'Tanda tangan basah' }}</strong></div><AppButton icon="pdf" as="a" v-if="editingId && form.letter_type === 'outgoing'" :href="`/administrasi/persuratan/surat/${editingId}/pdf`" target="_blank" rel="noopener">Buka preview PDF</AppButton><p v-else-if="form.letter_type === 'outgoing'">Simpan draf terlebih dahulu untuk membuka preview PDF.</p></div>
                    <p v-if="wizardError" class="wizard-error" role="alert">{{ wizardError }}</p>
                    <div v-if="Object.keys(form.errors).length" class="wizard-error" role="alert"><strong>Periksa kembali isian:</strong><ul><li v-for="(message, field) in form.errors" :key="field">{{ message }}</li></ul></div>
                    <div class="wizard-actions"><AppButton variant="secondary" v-if="wizardStep > 1" class="secondary-button" type="button" @click="previousStep">← Sebelumnya</AppButton><span></span><AppButton variant="secondary" class="secondary-button" type="button" @click="creating = false">Batal</AppButton><AppButton variant="primary" v-if="wizardStep < maxStep" class="primary-button" type="button" @click="nextStep">Lanjutkan →</AppButton><AppButton variant="primary" :loading="form.processing" v-else class="primary-button" type="submit" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : editingId ? 'Simpan perubahan' : form.letter_type === 'incoming' ? 'Catat surat' : 'Simpan draf' }}</AppButton></div>
                </form>
            </section>
            </div>

            <section class="panel correspondence-panel">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Daftar &amp; penelusuran</p>
                        <h2>{{ props.filter === 'all' ? 'Surat tersimpan & arsip' : props.filter === 'incoming' ? 'Antrean surat masuk' : props.filter === 'outgoing' ? 'Antrean surat keluar' : 'Arsip surat sekolah' }}</h2>
                    </div>
                </div>
                <details v-if="props.filter === 'all'" class="advanced-filters"><summary>Filter lanjutan</summary><div class="dashboard-filter-bar" aria-label="Filter surat tersimpan dan arsip">
                    <label>Status penyimpanan<select v-model="dashboardStorage"><option value="all">Semua tersimpan</option><option value="saved">Belum diarsipkan</option><option value="archived">Sudah diarsipkan</option></select></label>
                    <label>Jenis surat<select v-model="dashboardType"><option value="all">Semua jenis</option><option value="incoming">Surat masuk</option><option value="outgoing">Surat keluar</option></select></label>
                    <label>Dari tanggal<input v-model="dashboardDateFrom" type="date" /></label>
                    <label>Sampai tanggal<input v-model="dashboardDateTo" type="date" /></label>
                </div></details>
                <div v-if="props.filter === 'archived'" class="dashboard-filter-bar archive-filter-bar" aria-label="Filter arsip surat">
                    <label>Jenis surat<select v-model="archiveType"><option value="all">Semua jenis</option><option value="incoming">Surat masuk</option><option value="outgoing">Surat keluar</option></select></label>
                    <label>Dasar tanggal<select v-model="archiveDateBasis"><option value="archived">Tanggal pengarsipan</option><option value="letter">Tanggal surat</option></select></label>
                    <label>Dari tanggal<input v-model="archiveDateFrom" type="date" /></label>
                    <label>Sampai tanggal<input v-model="archiveDateTo" type="date" /></label>
                </div>
                <div class="table-toolbar">
                    <nav v-if="props.filter === 'all'" class="correspondence-tabs" aria-label="Filter surat">
                        <button v-for="tab in [{ value: 'all', label: 'Semua Surat' }, { value: 'incoming', label: 'Surat Masuk' }, { value: 'outgoing', label: 'Surat Keluar' }, { value: 'approval', label: 'Persetujuan' }, { value: 'signature', label: 'Tanda tangan' }, { value: 'ready', label: 'Siap Dikirim' }, { value: 'archived', label: 'Arsip' }]" :key="tab.value" type="button" class="correspondence-tab" :class="{ 'is-active': activeSection === tab.value }" @click="activeSection = tab.value as typeof activeSection">{{ tab.label }} · {{ tab.value === 'all' ? props.records.length : tab.value === 'incoming' || tab.value === 'outgoing' ? props.records.filter(record => record.letter_type === tab.value && record.status !== 'archived').length : queueCounts[tab.value as 'approval' | 'signature' | 'ready' | 'archived'] }}</button>
                    </nav>

                    <label class="search-label" aria-label="Cari surat">
                        <input v-model="search" class="search-control" type="search" placeholder="Cari perihal, nomor, asal, atau tujuan…" />
                    </label>
                </div>
                <div v-if="visibleRecords.length" class="table-scroll" tabindex="0" role="region" aria-label="Daftar surat tersimpan">
                    <table class="app-table correspondence-table">
                        <thead>
                            <tr>
                                <th scope="col">Surat</th>
                                <th scope="col">Pengirim / Tujuan</th>
                                <th scope="col">Perihal</th>
                                <th scope="col">Status</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr v-for="record in visibleRecords" :key="record.id">
                            <td data-label="Nomor & tanggal" class="col-number">
                                <strong class="doc-number">{{ record.letter_number || record.agenda_number || 'Belum bernomor' }}</strong>
                                <small v-if="record.letter_number && record.agenda_number" class="doc-agenda">Agenda: {{ record.agenda_number }}</small>
                                <small class="doc-date">{{ record.letter_date || record.received_at || 'Tanggal belum diisi' }}</small>
                                <small v-if="props.filter === 'archived'" class="doc-archived-at">Diarsipkan {{ record.archived_at?.slice(0, 16) || '—' }}</small>
                            </td>
                            <td data-label="Jenis & pihak terkait" class="col-party">
                                <div class="party-cell-content">
                                    <strong>{{ (record.letter_type === 'incoming' ? record.sender : record.recipient) || '—' }}</strong>
                                    <span class="correspondence-kind" :class="record.letter_type === 'incoming' ? 'kind-incoming' : 'kind-outgoing'">
                                        {{ record.letter_type === 'incoming' ? 'Surat Masuk' : 'Surat Keluar' }}
                                    </span>
                                </div>
                            </td>
                            <td data-label="Perihal & ringkasan" class="col-subject">
                                <strong class="subject-title">{{ record.subject }}</strong>
                                <p v-if="record.summary" class="subject-summary" :title="record.summary">{{ record.summary }}</p>
                                <p v-if="record.disposition" class="subject-disposition"><span>Disposisi:</span> {{ record.disposition }}</p>
                            </td>
                            <td data-label="Status" class="col-status">
                                <span class="status-chip" :class="`status-${record.status}`">{{ labelForStatus(record) }}</span>
                                <small v-if="record.signature_verified" class="signature-verified-note">TTE terverifikasi<span v-if="record.signature_signed_at"> · {{ record.signature_signed_at }}</span></small>
                                <small v-else-if="record.signature_method === 'bsre' && ['pending', 'processing'].includes(record.signature_status || '')" class="signature-pending-note">Diproses BSrE…</small>
                                <small v-if="record.signature_error_message" class="signature-error-note">{{ record.signature_error_message }}</small>
                            </td>
                            <td data-label="Aksi & tindak lanjut" class="action-cell">
                                <form v-if="can_manage && nextStatuses(record).length" class="action-stack" @submit.prevent="updateStatus(record)">
                                    <input
                                        v-if="expandedActions[record.id] && record.letter_type === 'incoming' && (targets[record.id] || nextStatuses(record)[0]?.value) === 'disposition'"
                                        v-model="dispositions[record.id]"
                                        class="disposition-inline-input"
                                        maxlength="3000"
                                        placeholder="Isi arahan disposisi…"
                                        aria-label="Arahan disposisi"
                                        required
                                    />
                                    <div class="action-bar">
                                        <AppButton v-if="!expandedActions[record.id] && record.letter_type === 'incoming' && (targets[record.id] || nextStatuses(record)[0]?.value) === 'disposition'" variant="primary" size="sm" type="button" :aria-expanded="false" @click="expandedActions[record.id] = true">Disposisi</AppButton>
                                        <AppButton v-else variant="primary" size="sm" type="submit">{{ nextActionLabel(record) }}</AppButton>
                                        <div class="utility-icon-group">
                                            <AppButton variant="secondary" size="sm" icon="edit" v-if="record.letter_type === 'incoming' && ['received', 'disposition'].includes(record.status)" type="button" title="Koreksi data surat" @click="editIncoming(record)">Koreksi data</AppButton>
                                            <AppButton variant="secondary" size="sm" icon="pdf" as="a" v-if="record.has_document" :href="`/administrasi/persuratan/surat/${record.id}/pdf`" target="_blank" rel="noopener" :title="record.status === 'draft' ? 'Lihat PDF draf' : 'Buka PDF surat'">{{ record.status === 'draft' ? 'Lihat PDF draf' : 'Buka PDF surat' }}</AppButton>
                                            <a v-if="record.has_attachment" :href="`/administrasi/persuratan/surat/${record.id}/lampiran`" class="ui-button ui-button--sm ui-button--secondary ui-button--icon-only" title="Unduh lampiran" aria-label="Unduh lampiran">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                                            </a>
                                        </div>
                                    </div>
                                    <span v-if="statusErrors[record.id]" class="field-error" role="alert">{{ statusErrors[record.id] }}</span>
                                </form>
                                <div v-else class="action-stack">
                                    <div class="action-bar">
                                        <div class="primary-action-group">
                                            <AppButton variant="primary" size="sm" v-if="can_manage && record.status === 'draft' && record.approval_required && ['pending', 'rejected'].includes(record.approval_status)" type="button" :disabled="actionBusy[record.id]" @click="submitApproval(record)">{{ record.approval_status === 'rejected' ? 'Ajukan ulang' : 'Ajukan persetujuan' }}</AppButton>
                                            <AppButton variant="primary" size="sm" v-if="can_manage && record.signature_method === 'manual' && ['draft', 'approved'].includes(record.status) && (!record.approval_required || record.approval_status === 'approved')" type="button" :disabled="actionBusy[record.id]" @click="signatureAction(record, 'manual-prepare')">Siapkan TTD</AppButton>
                                            <label v-if="can_manage && record.signature_method === 'manual' && record.status === 'awaiting_signature'" class="ui-button ui-button--sm ui-button--primary upload-action" :class="{ disabled: actionBusy[record.id] }">Unggah TTD<input class="sr-only" type="file" accept="application/pdf,.pdf" :disabled="actionBusy[record.id]" @change="uploadManualSignature(record, $event)" /></label>
                                            <AppButton variant="primary" size="sm" v-if="can_manage && record.signature_method === 'bsre' && ['draft', 'approved'].includes(record.status) && (!record.approval_required || record.approval_status === 'approved')" type="button" :disabled="actionBusy[record.id] || !tteConfigured" :title="tteConfigured ? 'Kirim ke antrean TTE BSrE' : 'TTE BSrE belum dikonfigurasi'" @click="signatureAction(record, 'bsre')">{{ tteConfigured ? 'Kirim TTE' : 'TTE belum siap' }}</AppButton>
                                            <AppButton variant="primary" size="sm" v-if="can_manage && record.signature_method === 'bsre' && record.status === 'signature_failed' && record.signature_request_id" type="button" :disabled="actionBusy[record.id]" @click="signatureAction(record, 'bsre-retry')">Ulangi TTE</AppButton>
                                            <details v-if="!can_manage && record.approval_required && record.status === 'pending_approval' && record.approval_status === 'pending'" class="approval-details">
                                                <summary>Tinjau</summary>
                                                <textarea v-model="approvalNotes[record.id]" class="approval-note-input" rows="2" placeholder="Catatan keputusan (opsional)" aria-label="Catatan keputusan" />
                                                <div class="approval-btn-pair">
                                                    <AppButton variant="primary" size="sm" type="button" :disabled="actionBusy[record.id]" @click="approveRecord(record, 'approve')">Setujui</AppButton>
                                                    <AppButton variant="secondary" size="sm" type="button" :disabled="actionBusy[record.id]" @click="approveRecord(record, 'reject')">Tolak</AppButton>
                                                </div>
                                            </details>
                                        </div>
                                        <div class="utility-icon-group">
                                            <AppButton variant="secondary" size="sm" icon="edit" v-if="can_manage && record.letter_type === 'incoming' && ['received', 'disposition'].includes(record.status)" type="button" title="Koreksi data surat" @click="editIncoming(record)">Koreksi data</AppButton>
                                            <AppButton variant="secondary" size="sm" icon="edit" v-if="can_manage && record.letter_type === 'outgoing' && record.status === 'draft' && record.has_document" type="button" title="Ubah draf surat" @click="editDraft(record)">Ubah draf</AppButton>
                                            <AppButton variant="secondary" size="sm" icon="pdf" as="a" v-if="record.has_document" :href="`/administrasi/persuratan/surat/${record.id}/pdf`" target="_blank" rel="noopener" :title="record.status === 'draft' ? 'Lihat PDF draf' : 'Buka PDF surat'">{{ record.status === 'draft' ? 'Lihat PDF draf' : 'Buka PDF surat' }}</AppButton>
                                            <a v-if="record.has_attachment" :href="`/administrasi/persuratan/surat/${record.id}/lampiran`" class="ui-button ui-button--sm ui-button--secondary ui-button--icon-only" title="Unduh lampiran" aria-label="Unduh lampiran">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <span v-if="actionErrors[record.id]" class="field-error action-error" role="alert">{{ actionErrors[record.id] }}</span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="empty-state">{{ search ? 'Tidak ada surat yang cocok dengan pencarian.' : 'Belum ada surat pada filter ini. Surat yang dicatat akan muncul di sini.' }}</p>
                <div v-if="filteredRecords.length" class="table-footer">
                    <span class="table-count">Menampilkan {{ visibleRecords.length }} dari {{ filteredRecords.length }} surat</span>
                    <div v-if="pageCount > 1" class="pagination">
                        <AppButton variant="secondary" size="sm" type="button" :disabled="currentPage === 1" @click="currentPage--">Sebelumnya</AppButton>
                        <span>Halaman {{ currentPage }} / {{ pageCount }}</span>
                        <AppButton variant="secondary" size="sm" type="button" :disabled="currentPage === pageCount" @click="currentPage++">Berikutnya</AppButton>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.correspondence-page {
    max-width: 1370px;
    margin-inline: auto;
}

.module-tabs {
    display: inline-flex;
    flex-wrap: wrap;
    gap: .35rem;
    padding: .3rem;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 999px;
    box-shadow: var(--shadow-sm);
}

.module-tab {
    display: inline-flex;
    align-items: center;
    padding: .45rem 1rem;
    color: var(--ink-soft);
    border-radius: 999px;
    font-size: var(--type-sm);
    font-weight: var(--weight-bold);
    text-decoration: none;
    transition: background-color .15s ease, color .15s ease;
}

.module-tab:hover {
    color: var(--ink);
    background: var(--surface-subtle);
}

.module-tab.is-active {
    color: var(--primary-strong);
    background: var(--primary-soft);
}

.correspondence-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1.25rem;
    padding-bottom: .9rem;
    border-bottom: 1px solid var(--line);
}

.correspondence-heading .eyebrow {
    margin: 0;
    color: var(--primary);
    font-size: var(--type-xs);
    font-weight: var(--weight-heavy);
    letter-spacing: var(--tracking-label);
    text-transform: uppercase;
}

.correspondence-heading h1 {
    margin: .25rem 0 .3rem;
    color: var(--ink);
    font-size: var(--type-3xl);
    font-weight: var(--weight-bold);
    letter-spacing: -.04em;
    line-height: 1.12;
}

.correspondence-heading p:not(.eyebrow) {
    margin: 0;
    max-width: 66ch;
    color: var(--ink-soft);
    font-size: var(--type-sm);
    line-height: 1.55;
}

.correspondence-header-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .6rem;
    flex-shrink: 0;
}

.correspondence-panel {
    padding: 1.35rem 1.5rem;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: var(--surface);
    box-shadow: var(--shadow-sm);
}

.section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.section-head .eyebrow {
    margin: 0 0 .2rem;
    color: var(--primary);
    font-size: var(--type-xs);
    font-weight: var(--weight-heavy);
    letter-spacing: var(--tracking-label);
    text-transform: uppercase;
}

.section-head h2 {
    margin: 0;
    color: var(--ink);
    font-size: 1.15rem;
    font-weight: var(--weight-bold);
    letter-spacing: -.02em;
}

.section-head p:not(.eyebrow) {
    margin: .25rem 0 0;
    color: var(--ink-soft);
    font-size: var(--type-sm);
    line-height: 1.5;
}

.reset-filter-pill {
    display: inline-flex;
    align-items: center;
    padding: .4rem .85rem;
    border: 1px solid var(--line-strong);
    border-radius: 999px;
    background: var(--surface-subtle);
    color: var(--ink-soft);
    font-size: var(--type-meta);
    font-weight: var(--weight-bold);
    cursor: pointer;
    transition: border-color .15s ease, color .15s ease, background-color .15s ease;
}

.reset-filter-pill:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: var(--primary-soft);
}

.queue-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: .85rem;
}

.queue-card {
    --queue-accent: var(--blue);
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    min-width: 0;
    min-height: 126px;
    padding: 1.1rem .85rem;
    border: 1px solid color-mix(in srgb, var(--queue-accent) 26%, var(--line));
    border-top: 3px solid var(--queue-accent);
    border-radius: 12px;
    background: color-mix(in srgb, var(--queue-accent) 7%, var(--surface));
    color: var(--ink);
    text-align: center;
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease, background-color .15s ease;
}

.queue-card--blue { --queue-accent: var(--blue); }
.queue-card--indigo { --queue-accent: var(--primary); }
.queue-card--amber { --queue-accent: var(--gold); }
.queue-card--violet { --queue-accent: var(--violet); }
.queue-card--green { --queue-accent: var(--green); }
.queue-card--slate { --queue-accent: var(--ink-soft); }

.queue-card:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--queue-accent) 55%, var(--line));
    box-shadow: 0 8px 20px -12px rgba(15, 23, 42, .22);
}

.queue-card.is-selected {
    border-color: var(--queue-accent);
    background: color-mix(in srgb, var(--queue-accent) 16%, var(--surface));
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--queue-accent) 22%, transparent);
}

.queue-card span {
    color: var(--ink-soft);
    font-size: var(--type-meta);
    font-weight: var(--weight-bold);
    line-height: 1.3;
}

.queue-card strong {
    color: var(--ink);
    font-size: 1.85rem;
    font-weight: var(--weight-bold);
    line-height: 1.05;
    letter-spacing: -.03em;
    font-variant-numeric: tabular-nums;
}

.queue-card small {
    display: inline-block;
    padding: .18rem .55rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--surface) 82%, transparent);
    border: 1px solid color-mix(in srgb, var(--queue-accent) 20%, var(--line));
    color: var(--ink-soft);
    font-size: 11.5px;
    font-weight: var(--weight-semibold);
    line-height: 1.3;
}

.workflow-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.workflow-top .section-head {
    margin-bottom: 0;
}

.workflow-pipelines {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .85rem;
    margin-top: 1.1rem;
}

.pipeline-box {
    display: flex;
    flex-direction: column;
    gap: .45rem;
    padding: .9rem 1rem;
    border: 1px solid var(--line);
    border-radius: 11px;
    background: var(--surface-subtle);
}

.pipeline-badge {
    align-self: flex-start;
    padding: .2rem .6rem;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: var(--weight-bold);
    letter-spacing: .02em;
}

.pipeline-badge--blue {
    background: var(--blue-soft);
    color: var(--blue);
}

.pipeline-badge--green {
    background: var(--green-soft);
    color: var(--green);
}

.pipeline-steps {
    margin: 0;
    color: var(--ink);
    font-size: var(--type-sm);
    font-weight: var(--weight-semibold);
    line-height: 1.5;
}

.pipeline-steps span {
    margin-inline: .25rem;
    color: var(--ink-faint);
}

.pipeline-note {
    grid-column: 1 / -1;
    padding: .7rem .95rem;
    border: 1px dashed var(--line-strong);
    border-radius: 10px;
    background: var(--surface);
    color: var(--ink-soft);
    font-size: var(--type-meta);
    line-height: 1.5;
}

.pipeline-note strong {
    color: var(--ink);
}

.table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .85rem;
    margin-top: 1rem;
    padding-bottom: .85rem;
    border-bottom: 1px solid var(--line);
}

.incoming-flow-panel { display: grid; gap: 1rem; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); padding: 1.35rem 1.5rem; box-shadow: var(--shadow-sm); }
.incoming-flow-list { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .65rem; margin: 0; padding: 0; list-style: none; }
.incoming-flow-list li { display: grid; grid-template-columns: 2rem minmax(0, 1fr); align-items: start; gap: .55rem; min-width: 0; border-top: 3px solid var(--line); padding: .75rem .25rem 0; color: var(--ink-soft); }
.incoming-flow-list li.is-current { border-top-color: var(--primary); color: var(--primary); }
.incoming-flow-list li > span { display: grid; width: 1.8rem; height: 1.8rem; place-items: center; border-radius: 50%; background: var(--surface-strong); color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.incoming-flow-list li.is-current > span { background: var(--primary); color: white; }
.incoming-flow-list strong, .incoming-flow-list small { display: block; }
.incoming-flow-list strong { color: var(--ink); font-size: var(--type-sm); }
.incoming-flow-list small { margin-top: .2rem; color: var(--ink-soft); font-size: var(--type-meta); line-height: 1.35; }
.outgoing-flow-panel { display: grid; gap: 1rem; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); padding: 1.35rem 1.5rem; box-shadow: var(--shadow-sm); }
.outgoing-flow-list { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .65rem; margin: 0; padding: 0; list-style: none; }
.outgoing-flow-list li { display: grid; grid-template-columns: 2rem minmax(0, 1fr); align-items: start; gap: .55rem; min-width: 0; border-top: 3px solid var(--line); padding: .75rem .25rem 0; color: var(--ink-soft); }
.outgoing-flow-list li.is-current { border-top-color: var(--primary); color: var(--primary); }
.outgoing-flow-list li > span { display: grid; width: 1.8rem; height: 1.8rem; place-items: center; border-radius: 50%; background: var(--surface-strong); color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.outgoing-flow-list li.is-current > span { background: var(--primary); color: white; }
.outgoing-flow-list strong, .outgoing-flow-list small { display: block; }
.outgoing-flow-list strong { color: var(--ink); font-size: var(--type-sm); }
.outgoing-flow-list small { margin-top: .2rem; color: var(--ink-soft); font-size: var(--type-meta); line-height: 1.35; }
.wizard-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.wizard-heading h2 { margin: .15rem 0 0; color: var(--ink); }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .56); backdrop-filter: blur(3px); }
.modal-card { width: min(760px, 100%); max-height: 90dvh; overflow-y: auto; border-radius: 16px; box-shadow: 0 24px 80px rgb(15 23 42 / .25); }
.stepper { display: grid; grid-template-columns: repeat(5, 1fr); margin: 1.2rem 0; padding: 0; list-style: none; }
.stepper li { position: relative; display: grid; justify-items: center; gap: .3rem; color: var(--ink-faint); }
.stepper li:not(:last-child)::after { position: absolute; top: .85rem; left: calc(50% + 1.15rem); width: calc(100% - 2.3rem); height: 2px; background: var(--line); content: ''; }
.stepper span { z-index: 1; display: grid; width: 1.75rem; height: 1.75rem; place-items: center; border: 2px solid var(--line-strong); border-radius: 50%; background: var(--surface); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.stepper .active, .stepper .done { color: var(--primary); }
.stepper .active span, .stepper .done span { border-color: var(--primary); background: var(--primary); color: white; }
.stepper .done:not(:last-child)::after { background: var(--primary); }
.wizard-step-hint { margin: -.35rem 0 .2rem; color: var(--ink-soft); font-size: var(--type-sm); line-height: 1.5; }
.wizard-form { display: grid; gap: 1rem; }
.wizard-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
.wizard-grid label { display: grid; gap: .4rem; color: var(--ink-soft); font-size: var(--type-sm); font-weight: var(--weight-semibold); }
.wizard-grid .wide { grid-column: 1 / -1; }
.wizard-grid input, .wizard-grid select, .wizard-grid textarea { width: 100%; min-height: 2.7rem; border: 1px solid var(--line-strong); border-radius: .65rem; background: var(--surface); padding: .6rem .75rem; color: var(--ink); font: inherit; }
.wizard-grid b { color: var(--red); }
.incoming-review-note { display: grid; gap: .35rem; border: 1px solid color-mix(in srgb, var(--primary) 28%, var(--line)); border-radius: .75rem; background: var(--primary-soft); padding: .85rem; color: var(--primary-strong); font-size: var(--type-sm); line-height: 1.5; }
.tte-readiness-panel { display: flex; align-items: flex-start; gap: .9rem; border: 1px solid color-mix(in srgb, var(--primary) 28%, var(--line)); border-radius: .85rem; background: var(--primary-soft); padding: 1rem 1.1rem; }
.tte-readiness-panel.is-ready { border-color: color-mix(in srgb, var(--green) 30%, var(--line)); background: var(--green-soft); }
.tte-readiness-panel.is-blocked { border-color: color-mix(in srgb, var(--gold) 30%, var(--line)); background: var(--gold-soft); }
.tte-readiness-mark { display: grid; flex: 0 0 2rem; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; background: var(--primary); color: white; font-weight: var(--weight-bold); }
.tte-readiness-panel.is-ready .tte-readiness-mark { background: var(--green); }
.tte-readiness-panel.is-blocked .tte-readiness-mark { background: var(--gold); }
.tte-readiness-panel h2 { margin: .15rem 0 .25rem; color: var(--ink); font-size: var(--type-md); }
.tte-readiness-panel p:not(.eyebrow) { margin: 0; max-width: 72ch; color: var(--ink-soft); font-size: var(--type-sm); line-height: 1.55; }
.tte-readiness-panel small { display: block; margin-top: .45rem; color: var(--ink-soft); font-size: var(--type-meta); }
.warning-note { border: 1px solid color-mix(in srgb, var(--gold) 32%, var(--line)); background: var(--gold-soft); color: var(--gold); }
.signature-options label.is-disabled { cursor: not-allowed; opacity: .58; }
.signatory-toggle { justify-self: start; }
.signatory-form { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: .75rem; border: 1px solid var(--line); border-radius: .75rem; background: var(--surface-subtle); padding: 1rem; }
.signatory-form .wide { grid-column: 1 / -1; }
.template-choices { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.template-choices label, .signature-options label { display: flex; gap: .65rem; border: 1px solid var(--line); border-radius: .75rem; padding: 1rem; color: var(--ink); }
.template-choices label.selected, .signature-options label.selected { border-color: var(--primary); background: var(--primary-soft); }
.template-choices input, .signature-options input, .checkbox-label input { width: auto; min-height: auto; }
.template-choices span, .signature-options span { display: grid; gap: .2rem; }
.template-choices small, .signature-options small { color: var(--ink-soft); font-weight: var(--weight-medium); }
.signature-options { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; margin: 0; border: 0; padding: 0; }
.signature-options legend { margin-bottom: .5rem; color: var(--ink-soft); font-weight: var(--weight-bold); }
.checkbox-label { display: flex !important; align-items: center; }
.review-card { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); padding: 1rem; }
.review-card div { display: grid; gap: .15rem; }
.review-card span { color: var(--ink-soft); font-size: var(--type-meta); }
.review-card a, .review-card p { grid-column: 1 / -1; margin: 0; color: var(--primary); font-size: var(--type-sm); font-weight: var(--weight-bold); }
.wizard-error { margin: 0; border: 1px solid color-mix(in srgb, var(--red) 35%, var(--line)); border-radius: .65rem; background: var(--red-soft); padding: .75rem; color: var(--red); font-size: var(--type-sm); }
.wizard-actions { display: flex; align-items: center; gap: .5rem; border-top: 1px solid var(--line); padding-top: 1rem; }
.wizard-actions > span { flex: 1; }
.upload-action { cursor: pointer; }
.upload-action.disabled { cursor: wait; opacity: .55; }
.approval-note-input { width: min(19rem, 100%); min-height: 2.7rem; border: 1px solid var(--line-strong); border-radius: .6rem; padding: .55rem .7rem; color: var(--ink); background: var(--surface); font: inherit; font-size: var(--type-control); }
.correspondence-form { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
.correspondence-form label { display: grid; gap: .4rem; color: var(--ink-soft); font-size: var(--type-sm); font-weight: var(--weight-semibold); }
.correspondence-form input, .correspondence-form select, .correspondence-form textarea, .status-update-form select, .status-update-form input { width: 100%; min-height: 2.7rem; border: 1px solid var(--line-strong); border-radius: .65rem; background: var(--surface); padding: .6rem .75rem; color: var(--ink); }
.correspondence-form input[type=file] { padding: .4rem; }
.correspondence-wide { grid-column: 1 / -1; }
.correspondence-actions { display: flex; justify-content: end; gap: .5rem; flex-wrap: wrap; }
.workflow-actions, .pagination { display: flex; align-items: center; flex-wrap: wrap; gap: .6rem; }
.workflow-help, .form-note { margin: .8rem 0 0; color: var(--ink-soft); font-size: var(--type-sm); line-height: 1.55; }
.form-note { margin: 0; padding: .75rem; background: var(--primary-soft); border-radius: .6rem; }
.dashboard-filter-bar { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-top: .25rem; border: 1px solid var(--line); border-radius: 12px; background: var(--surface-subtle); padding: .9rem 1rem; }
.dashboard-filter-bar label { display: grid; gap: .35rem; color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-semibold); }
.dashboard-filter-bar input, .dashboard-filter-bar select { width: 100%; min-height: 2.5rem; border: 1px solid var(--line-strong); border-radius: .55rem; background: var(--surface); padding: .5rem .65rem; color: var(--ink); font: inherit; font-size: var(--type-control); }
.search-label { flex: 1 1 18rem; max-width: 22rem; margin: 0; }
.search-label input { width: 100%; min-height: 40px; border: 1px solid var(--line-strong); border-radius: 999px; padding: .5rem 1rem; background: var(--surface); color: var(--ink); font-size: var(--type-sm); }
.pagination { justify-content: end; margin-top: 1rem; color: var(--ink-soft); font-size: var(--type-sm); }
.correspondence-card, .correspondence-card-heading > div { min-width: 0; overflow-wrap: anywhere; }
.required-mark, .field-error { color: var(--red); }
.field-hint, .approval-option small { color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-medium); line-height: 1.4; }
.approval-option { gap: .25rem !important; }
.approval-option input { width: auto; min-height: auto; margin-right: .35rem; }
.approval-actions { display: flex; align-items: center; flex-wrap: wrap; gap: .5rem; margin-top: .75rem; border-top: 1px solid var(--line); padding-top: .75rem; }
.approval-pending { color: var(--gold); font-size: var(--type-sm); font-weight: var(--weight-bold); }
.field-error { font-size: var(--type-meta); font-weight: var(--weight-medium); }
.action-error { flex-basis: 100%; }
.correspondence-tabs { display: flex; flex-wrap: wrap; gap: .45rem; }
.correspondence-tab { border: 1px solid var(--line); border-radius: 999px; background: var(--surface-subtle); padding: .42rem .85rem; color: var(--ink-soft); font-size: var(--type-sm); font-weight: var(--weight-semibold); text-decoration: none; cursor: pointer; transition: all .15s ease; }
.correspondence-tab:hover { border-color: var(--primary); color: var(--primary); }
.correspondence-tab.is-active { border-color: var(--primary); background: var(--primary-soft); color: var(--primary-strong); }
.table-scroll { margin-top: 1rem; overflow-x: auto; border: 1px solid var(--line); border-radius: 12px; background: var(--surface); }
.correspondence-table { width: 100%; min-width: 900px; border-collapse: collapse; }
.correspondence-table th { background: var(--surface-subtle); color: var(--ink-soft); font-size: 11.5px; font-weight: var(--weight-bold); letter-spacing: .04em; text-align: left; text-transform: uppercase; white-space: nowrap; }
.correspondence-table th, .correspondence-table td { border-bottom: 1px solid var(--line); padding: .85rem 1rem; vertical-align: top; }
.correspondence-table tbody tr { transition: background-color .14s ease; }
.correspondence-table tbody tr:hover { background: color-mix(in srgb, var(--primary-soft) 36%, transparent); }
.correspondence-table tbody tr:last-child td { border-bottom: 0; }
.correspondence-table td { color: var(--ink); font-size: var(--type-sm); }
.correspondence-table td > small { display: block; margin-top: .2rem; color: var(--ink-soft); font-size: var(--type-meta); line-height: 1.35; }
.col-number { width: 16%; }
.doc-number { display: block; color: var(--ink); font-variant-numeric: tabular-nums; line-height: 1.35; }
.doc-agenda { color: var(--ink-faint) !important; }
.doc-date { color: var(--ink-soft); }
.col-party { width: 19%; }
.party-cell-content { display: grid; align-content: start; justify-items: start; gap: .25rem; min-width: 0; }
.party-cell-content strong { max-width: 100%; color: var(--ink); line-height: 1.35; overflow-wrap: anywhere; }
.party-cell-content small { margin-top: 0; color: var(--ink-soft); font-size: var(--type-meta); line-height: 1.3; }
.col-subject { width: 27%; }
.subject-title { display: block; color: var(--ink); line-height: 1.38; }
.subject-summary { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin: .25rem 0 0; color: var(--ink-soft); font-size: var(--type-meta); line-height: 1.4; }
.subject-disposition { margin: .35rem 0 0; padding: .28rem .55rem; border-left: 2px solid var(--gold); border-radius: 4px; background: color-mix(in srgb, var(--gold-soft) 55%, var(--surface)); color: var(--ink); font-size: 12px; line-height: 1.4; }
.subject-disposition span { font-weight: var(--weight-bold); color: var(--gold); }
.col-status { width: 13%; }
.action-cell { width: 20%; min-width: 12rem; }
.action-stack { display: flex; flex-direction: column; gap: .45rem; }
.disposition-inline-input { width: 100%; min-height: 34px !important; padding: .35rem .65rem; border: 1px solid var(--line-strong); border-radius: 8px; background: var(--surface); color: var(--ink); font-size: 12.5px; }
.action-bar { display: flex; align-items: center; flex-wrap: wrap; gap: .35rem; }
.primary-action-group { display: flex; align-items: center; flex-wrap: wrap; gap: .35rem; min-width: 0; }
.utility-icon-group { display: inline-flex; align-items: center; gap: .35rem; flex-shrink: 0; }
.action-muted-note { color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-medium); }
.approval-btn-pair { display: flex; gap: .35rem; width: 100%; }
.approval-note-input { width: 100%; min-height: 2.5rem; border: 1px solid var(--line-strong); border-radius: .6rem; padding: .45rem .65rem; color: var(--ink); font: inherit; font-size: var(--type-meta); }
.table-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .75rem; margin-top: 1rem; padding-top: .75rem; border-top: 1px solid var(--line); }
.table-count { color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-semibold); }
.pagination { display: flex; align-items: center; gap: .5rem; margin: 0; color: var(--ink-soft); font-size: var(--type-sm); }
.correspondence-list { display: grid; gap: .85rem; padding-top: 1rem; }
.correspondence-card { border: 1px solid var(--line); border-radius: .85rem; padding: 1rem; }
.correspondence-card-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.correspondence-card h3 { margin: .3rem 0 .2rem; color: var(--ink); font-size: 1.05rem; font-weight: var(--weight-bold); }
.correspondence-card-heading p, .muted-note, .archived-note { margin: 0; color: var(--ink-soft); font-size: var(--type-sm); }
.correspondence-kind, .status-chip { display: inline-flex; border-radius: 999px; padding: .2rem .55rem; font-size: var(--type-meta); font-weight: var(--weight-semibold); }
.kind-incoming { background: var(--primary-soft); color: var(--primary-strong); }
.kind-outgoing { background: var(--surface-strong); color: var(--ink-soft); }
.status-chip { background: var(--green-soft); color: var(--green); white-space: nowrap; }
.status-chip.status-received { background: var(--primary-soft); color: var(--primary-strong); }
.status-chip.status-disposition { background: var(--gold-soft); color: var(--gold); }
.status-chip.status-done, .status-chip.status-archived { background: var(--green-soft); color: var(--green); }
.status-chip.status-draft, .status-chip.status-approved { background: var(--primary-soft); color: var(--primary-strong); }
.status-chip.status-pending_approval, .status-chip.status-awaiting_signature, .status-chip.status-signature_pending { background: var(--gold-soft); color: var(--gold); }
.status-chip.status-signature_failed { background: var(--red-soft); color: var(--red); }
.signature-verified-note { color: var(--green) !important; font-weight: var(--weight-bold); }
.signature-error-note { color: var(--red) !important; }
.status-chip.status-issued, .status-chip.status-sent { background: var(--green-soft); color: var(--green); }
.correspondence-meta { display: flex; flex-wrap: wrap; gap: .65rem 1.5rem; margin: .9rem 0; }
.correspondence-meta div { display: grid; gap: .1rem; }
.correspondence-meta dt { color: var(--ink-soft); font-size: var(--type-meta); }
.correspondence-meta dd { margin: 0; color: var(--ink); font-size: var(--type-sm); font-weight: var(--weight-semibold); }
.correspondence-summary-text, .correspondence-disposition { margin: .5rem 0; color: var(--ink-soft); font-size: var(--type-sm); white-space: pre-wrap; }
.correspondence-footer { display: flex; align-items: center; justify-content: space-between; gap: .75rem; border-top: 1px solid var(--line); padding-top: .8rem; }
.row-action-button { text-align: center; }
.empty-state { padding: 1.25rem 0; color: var(--ink-soft); }
@media (max-width: 1100px) { .queue-grid { grid-template-columns: repeat(3, 1fr); } .template-choices { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 800px) { .correspondence-heading { flex-direction: column; align-items: stretch; } .workflow-pipelines { grid-template-columns: 1fr; } .dashboard-filter-bar { grid-template-columns: repeat(2, minmax(0, 1fr)); } .queue-grid { grid-template-columns: repeat(2, 1fr); } .stepper small { display: none; } .wizard-grid, .signature-options, .review-card, .signatory-form { grid-template-columns: 1fr; } .wizard-grid .wide, .signatory-form .wide { grid-column: auto; } .correspondence-form { grid-template-columns: 1fr; } .correspondence-wide { grid-column: auto; } .correspondence-footer { align-items: stretch; flex-direction: column; } .status-update-form { justify-content: stretch; } .status-update-form select, .status-update-form input, .status-update-form button { width: 100%; } .correspondence-card-heading { align-items: start; } .correspondence-table { min-width: 0; } .correspondence-table thead { display: none; } .correspondence-table tr { display: grid; border-bottom: 1px solid var(--line); padding: .75rem; } .correspondence-table tr:last-child { border-bottom: 0; } .correspondence-table td { display: grid; grid-template-columns: 7.5rem minmax(0,1fr); width: 100% !important; border: 0; padding: .35rem 0; } .correspondence-table td::before { color: var(--ink-soft); content: attr(data-label); font-size: var(--type-meta); } .action-cell { min-width: 0; } .action-cell .record-actions, .action-cell > .status-update-form, .action-cell > .field-error, .action-cell > .archived-note { grid-column: 1 / -1; } .action-cell .record-actions { padding-top: .35rem; } .incoming-flow-list, .outgoing-flow-list { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 480px) { .dashboard-filter-bar { grid-template-columns: 1fr; } .incoming-flow-list, .outgoing-flow-list { grid-template-columns: 1fr; } .correspondence-page .workflow-actions { align-items: stretch; flex-direction: column; width: 100%; } .correspondence-page .workflow-actions > * { width: 100%; } }
.incoming-review-note, .tte-readiness-panel { border-color: color-mix(in srgb, var(--primary) 25%, var(--line)); background: var(--primary-soft); }
.signatory-form, .review-card { border-color: var(--line); background: var(--surface-subtle); }
.wizard-error { border-radius: var(--card-radius-compact); }
.correspondence-page .wizard-grid input, .correspondence-page .wizard-grid select, .correspondence-page .wizard-grid textarea, .correspondence-page .dashboard-filter-bar input, .correspondence-page .dashboard-filter-bar select, .correspondence-page .status-update-form input, .correspondence-page .status-update-form select { min-height: 42px; color: var(--ink); background: var(--surface); border-color: var(--line-strong); border-radius: 10px; }
.correspondence-page button:focus-visible, .correspondence-page input:focus-visible, .correspondence-page select:focus-visible, .correspondence-page textarea:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
.correspondence-table { table-layout: fixed; }
.col-subject { width: 32%; }
.col-status { width: 13%; }
.doc-number { overflow-wrap: anywhere; }
.subject-title { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; }
.subject-summary { -webkit-line-clamp: 1; }
.subject-disposition { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.approval-details { width: 100%; }
.approval-details summary { cursor: pointer; color: var(--primary-strong); font-weight: var(--weight-semibold); padding: .35rem 0; }
.approval-details[open] summary { margin-bottom: .35rem; }
.approval-details .approval-btn-pair { margin-top: .35rem; }
</style>
