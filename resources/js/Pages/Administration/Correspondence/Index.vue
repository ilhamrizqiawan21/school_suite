<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, reactive, ref, watch } from 'vue';

interface StatusOption { value: string; label: string }
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
const archiveLetterDateFrom = ref('');
const archiveLetterDateTo = ref('');
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
const tteReadinessTitle = computed(() => tteConfigured.value ? 'TTE BSrE siap digunakan' : 'TTE BSrE belum siap digunakan');
const tteReadinessMessage = computed(() => tteConfigured.value
    ? 'Dokumen akan diproses melalui antrean tanda tangan elektronik. Pastikan pejabat sudah memiliki identitas BSrE yang terhubung.'
    : 'Hubungkan endpoint dan kredensial BSrE di server sebelum mengirim surat untuk TTE. Pilihan ini akan tetap aman dan tidak mengirim dokumen sebelum konfigurasi lengkap.');
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
            const matchesLetterFrom = !archiveLetterDateFrom.value || letterDate >= archiveLetterDateFrom.value;
            const matchesLetterTo = !archiveLetterDateTo.value || letterDate <= archiveLetterDateTo.value;
            const matchesArchiveFrom = !archiveDateFrom.value || archivedDate >= archiveDateFrom.value;
            const matchesArchiveTo = !archiveDateTo.value || archivedDate <= archiveDateTo.value;
            return matchesType && matchesLetterFrom && matchesLetterTo && matchesArchiveFrom && matchesArchiveTo;
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
watch([search, activeSection, dashboardDateFrom, dashboardDateTo, dashboardType, dashboardStorage, archiveType, archiveLetterDateFrom, archiveLetterDateTo, archiveDateFrom, archiveDateTo, () => props.filter, filteredRecords], () => {
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

function approveRecord(record: CorrespondenceRecord, decision: 'approve' | 'reject') {
    const message = decision === 'approve' ? 'Setujui surat ini untuk diterbitkan?' : 'Tolak surat ini agar operator memperbaikinya?';
    if (!window.confirm(message)) return;
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

function updateStatus(record: CorrespondenceRecord) {
    const status = targets[record.id] || nextStatuses(record)[0]?.value;
    if (!status) return;
    if (status === 'issued' && !window.confirm('Finalkan surat untuk membuat QR? Isi surat tidak dapat diubah setelah difinalkan.')) return;
    if (record.letter_type === 'outgoing' && status === 'sent' && !window.confirm('Pastikan surat sudah ditandatangani dan dikirim di luar aplikasi. Tandai surat sebagai terkirim?')) return;

    router.patch(`/administrasi/persuratan/surat/${record.id}/status`, {
        status,
        disposition: dispositions[record.id] ?? '',
    }, {
        preserveScroll: true,
        onSuccess: () => { delete statusErrors[record.id]; delete targets[record.id]; },
        onError: (errors) => { statusErrors[record.id] = errors.status || errors.disposition || 'Status surat gagal diperbarui.'; },
    });
}

function signatureAction(record: CorrespondenceRecord, action: 'manual-prepare' | 'bsre' | 'bsre-retry') {
    if (action !== 'manual-prepare') {
        if (!tteConfigured.value) {
            actionErrors[record.id] = 'TTE BSrE belum dikonfigurasi. Hubungi administrator sistem.';
            return;
        }
        const message = action === 'bsre'
            ? 'Kirim surat ini ke antrean TTE BSrE? Setelah diproses, isi PDF final tidak dapat diubah.'
            : 'Jadwalkan ulang permintaan TTE ini? Sistem akan memakai kunci idempotensi yang sama.';
        if (!window.confirm(message)) return;
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
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout :title="pageTitle">
        <main :class="['view', 'space-y-6', 'correspondence-page', { 'incoming-page': props.filter === 'incoming', 'outgoing-page': props.filter === 'outgoing', 'archive-page': props.filter === 'archived' }]">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Administrasi sekolah</p>
                    <h1>{{ pageTitle }}</h1>
                    <p>{{ pageDescription }}</p>
                </div>
                <div v-if="props.filter === 'incoming'" class="incoming-hero-summary"><strong>{{ queueCounts.incoming + queueCounts.disposition }}</strong><span>surat masuk aktif</span><small>{{ queueCounts.incoming }} baru · {{ queueCounts.disposition }} menunggu disposisi</small></div>
                <div v-else-if="props.filter === 'outgoing'" class="outgoing-hero-summary">
                    <div><strong>{{ queueCounts.draft }}</strong><span>Draf</span></div>
                    <div><strong>{{ queueCounts.approval }}</strong><span>Persetujuan</span></div>
                    <div><strong>{{ queueCounts.signature }}</strong><span>Tanda tangan</span></div>
                    <div><strong>{{ queueCounts.ready }}</strong><span>Siap dikirim</span></div>
                </div>
                <div v-else-if="props.filter === 'archived'" class="archive-hero-summary">
                    <div><strong>{{ archiveCounts.total }}</strong><span>Total arsip</span><small>surat tersimpan</small></div>
                    <div><strong>{{ archiveCounts.incoming }}</strong><span>Surat masuk</span><small>arsip diterima</small></div>
                    <div><strong>{{ archiveCounts.outgoing }}</strong><span>Surat keluar</span><small>arsip diterbitkan</small></div>
                </div>
                <div v-else class="correspondence-hero-metrics">
                    <div><span>Perlu dikerjakan</span><strong>{{ queueCounts.incoming + queueCounts.draft + queueCounts.approval + queueCounts.signature }}</strong><small>surat dalam antrean</small></div>
                    <div><span>Siap dikirim</span><strong>{{ queueCounts.ready }}</strong><small>menunggu pengiriman</small></div>
                    <div><span>Arsip</span><strong>{{ queueCounts.archived }}</strong><small>tersimpan aman</small></div>
                </div>
            </section>

            <section v-if="props.filter === 'incoming'" class="incoming-flow-panel" aria-labelledby="incoming-flow-heading">
                <div class="table-title"><div><p class="eyebrow">Cara kerja surat masuk</p><h2 id="incoming-flow-heading">Surat masuk, satu langkah jelas</h2><p>Pastikan setiap surat punya catatan, penerima disposisi, dan keputusan akhir.</p></div></div>
                <ol class="incoming-flow-list">
                    <li :class="{ 'is-current': incomingCurrentStage === 'recorded' }"><span>1</span><div><strong>Catat</strong><small>Identitas pengirim dan tanggal diterima</small></div></li>
                    <li :class="{ 'is-current': incomingCurrentStage === 'complete' }"><span>2</span><div><strong>Lengkapi</strong><small>Ringkasan dan lampiran surat</small></div></li>
                    <li :class="{ 'is-current': incomingCurrentStage === 'disposition' }"><span>3</span><div><strong>Disposisi</strong><small>Arahan dan tujuan tindak lanjut</small></div></li>
                    <li :class="{ 'is-current': incomingCurrentStage === 'done' }"><span>4</span><div><strong>Selesaikan</strong><small>Tandai pekerjaan sudah ditutup</small></div></li>
                    <li :class="{ 'is-current': incomingCurrentStage === 'archived' }"><span>5</span><div><strong>Arsipkan</strong><small>Simpan sebagai rekam sekolah</small></div></li>
                </ol>
            </section>

            <section v-if="props.filter === 'outgoing'" class="outgoing-flow-panel" aria-labelledby="outgoing-flow-heading">
                <div class="table-title"><div><p class="eyebrow">Cara kerja surat keluar</p><h2 id="outgoing-flow-heading">Dari draf sampai terkirim</h2><p>Setiap tahap diperiksa agar surat tidak langsung terbit sebelum siap.</p></div></div>
                <ol class="outgoing-flow-list">
                    <li :class="{ 'is-current': outgoingCurrentStage === 'draft' }"><span>1</span><div><strong>Draf</strong><small>{{ queueCounts.draft }} surat perlu dilengkapi</small></div></li>
                    <li :class="{ 'is-current': outgoingCurrentStage === 'approval' }"><span>2</span><div><strong>Persetujuan</strong><small>{{ queueCounts.approval }} surat diajukan</small></div></li>
                    <li :class="{ 'is-current': outgoingCurrentStage === 'signature' }"><span>3</span><div><strong>Tanda tangan</strong><small>{{ queueCounts.signature }} surat menunggu proses</small></div></li>
                    <li :class="{ 'is-current': outgoingCurrentStage === 'issued' }"><span>4</span><div><strong>Terbit</strong><small>{{ queueCounts.ready }} dokumen final</small></div></li>
                    <li :class="{ 'is-current': outgoingCurrentStage === 'sent' }"><span>5</span><div><strong>Terkirim</strong><small>Catat setelah dikirim</small></div></li>
                </ol>
            </section>

            <section v-if="props.filter === 'outgoing'" class="tte-readiness-panel" :class="{ 'is-ready': tteConfigured, 'is-blocked': !tteConfigured }" aria-labelledby="tte-readiness-heading">
                <div class="tte-readiness-mark" aria-hidden="true">{{ tteConfigured ? '✓' : '!' }}</div>
                <div>
                    <p class="eyebrow">Kesiapan tanda tangan</p>
                    <h2 id="tte-readiness-heading">{{ tteReadinessTitle }}</h2>
                    <p>{{ tteReadinessMessage }}</p>
                    <small v-if="!tteConfigured">Status server: {{ props.tte_status?.enabled ? 'provider aktif, kredensial belum lengkap' : 'provider belum diaktifkan' }} · Queue: {{ props.tte_status?.queue || 'belum diketahui' }}</small>
                    <small v-else>Gunakan identitas BSrE pejabat yang benar. Sistem tidak menyimpan token BSrE di browser.</small>
                </div>
            </section>

                <p v-if="flashMessage" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                {{ flashMessage }}
            </p>

            <section v-if="props.filter === 'all'" class="queue-section" aria-labelledby="queue-heading">
                <div class="table-title"><div><p class="eyebrow">Perlu ditindaklanjuti</p><h2 id="queue-heading">Antrean persuratan</h2><p>Pilih kartu untuk melihat surat yang membutuhkan tindakan.</p></div></div>
                <div class="queue-grid">
                    <button type="button" :class="{ 'is-selected': activeSection === 'incoming' }" @click="activeSection = 'incoming'"><span>Surat masuk aktif</span><strong>{{ queueCounts.incoming }}</strong><small>{{ queueCounts.disposition }} perlu disposisi</small></button>
                    <button type="button" :class="{ 'is-selected': activeSection === 'outgoing' }" @click="activeSection = 'outgoing'"><span>Draf surat</span><strong>{{ queueCounts.draft }}</strong><small>Perlu dilengkapi</small></button>
                    <button type="button" :class="{ 'is-selected': activeSection === 'approval' }" @click="activeSection = 'approval'"><span>Persetujuan</span><strong>{{ queueCounts.approval }}</strong><small>Sudah diajukan</small></button>
                    <button type="button" :class="{ 'is-selected': activeSection === 'signature' }" @click="activeSection = 'signature'"><span>Tanda tangan</span><strong>{{ queueCounts.signature }}</strong><small>Manual atau BSrE</small></button>
                    <button type="button" :class="{ 'is-selected': activeSection === 'ready' }" @click="activeSection = 'ready'"><span>Siap dikirim</span><strong>{{ queueCounts.ready }}</strong><small>Dokumen final</small></button>
                    <button type="button" :class="{ 'is-selected': activeSection === 'archived' }" @click="activeSection = 'archived'"><span>Arsip</span><strong>{{ queueCounts.archived }}</strong><small>Tersimpan</small></button>
                </div>
            </section>

            <section v-if="props.filter !== 'archived'" class="panel correspondence-panel workflow">
                <div class="table-title"><div><p class="eyebrow">Meja kerja TU</p><h2>{{ props.filter === 'incoming' ? 'Catat surat yang baru diterima' : props.filter === 'outgoing' ? 'Buat surat keluar' : 'Mulai dari sini' }}</h2><p>{{ props.filter === 'incoming' ? 'Masukkan data pokok terlebih dahulu. Disposisi dilakukan dari antrean setelah surat tersimpan.' : props.filter === 'outgoing' ? 'Mulai dari template, isi naskah, pilih penandatangan, lalu simpan sebagai draf.' : 'Pilih jenis pekerjaan. Sistem akan menjaga urutan tahap berikutnya.' }}</p></div></div>
                <div v-if="can_manage" class="workflow-actions">
                    <button v-if="props.filter !== 'outgoing'" type="button" class="primary-button" @click="startNew('incoming')">Catat surat masuk</button>
                    <button v-if="props.filter !== 'incoming'" type="button" class="secondary-button" @click="startNew('outgoing')">Buat surat keluar</button>
                </div>
                <p v-if="props.filter === 'incoming'" class="workflow-help">Setelah tersimpan, gunakan tindakan pada baris surat untuk meneruskan disposisi, menandai selesai, lalu mengarsipkan.</p>
                <p v-else-if="props.filter === 'outgoing'" class="workflow-help">Draf belum dikirim sebelum persetujuan, tanda tangan, dan penerbitan selesai. Gunakan tombol tindakan sesuai tahapnya.</p>
                <template v-else>
                    <p class="workflow-help">Surat masuk: catat → disposisi → selesai → arsip. Surat keluar: draf → persetujuan → tanda tangan → terbit → terkirim → arsip.</p>
                    <p class="workflow-help">QR memeriksa metadata dan integritas berkas final. Pada jalur manual, QR bukan tanda tangan elektronik tersertifikasi.</p>
                </template>
            </section>

            <section v-if="can_manage" v-show="creating" id="form-surat" class="panel correspondence-panel wizard-panel" tabindex="-1">
                <div class="wizard-heading"><div><p class="eyebrow">{{ form.letter_type === 'incoming' ? 'Surat masuk' : 'Surat keluar' }}</p><h2>{{ editingType === 'incoming' ? 'Koreksi data surat masuk' : editingType === 'outgoing' ? 'Perbaiki draf surat keluar' : form.letter_type === 'incoming' ? 'Catat surat masuk' : 'Buat surat keluar' }}</h2></div><button type="button" class="wizard-close" aria-label="Tutup formulir" @click="creating = false">×</button></div>
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
                        <div v-else-if="wizardStep === 2" class="wizard-grid"><label>Nomor surat <b>*</b><input v-model="form.letter_number" maxlength="100" placeholder="001/TU/X/2026" /></label><label>Tanggal surat <b>*</b><input v-model="form.letter_date" type="date" /></label><label>Tujuan surat <b>*</b><input v-model="form.recipient" maxlength="180" /></label><label>Sifat<select v-model="form.urgency"><option value="ordinary">Biasa</option><option value="urgent">Segera</option><option value="immediate">Sangat segera</option></select></label><label class="wide">Alamat tujuan<textarea v-model="form.recipient_address" rows="2" /></label><label class="wide">Perihal <b>*</b><input v-model="form.subject" maxlength="200" /></label><label>Klasifikasi<input v-model="form.classification" maxlength="40" /></label><label>Keterangan lampiran<input v-model="form.attachment_note" maxlength="180" placeholder="Contoh: 1 berkas" /></label><label class="wide">Berkas lampiran <span class="field-hint">PDF, DOCX, XLSX, JPG/PNG; maksimal 10 MB</span><input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" @change="setAttachment" /></label></div>
                        <div v-else-if="wizardStep === 3" class="wizard-grid"><label class="wide">Salam pembuka<input v-model="form.opening_greeting" maxlength="250" /></label><label class="wide">Isi surat <b>*</b><textarea v-model="form.letter_body" rows="10" maxlength="20000" placeholder="Tulis isi dalam paragraf yang singkat dan jelas." /></label><label class="wide">Penutup<textarea v-model="form.closing_text" rows="3" /></label><label class="wide">Tembusan<textarea v-model="form.carbon_copy" rows="2" placeholder="Satu penerima per baris" /></label></div>
                        <div v-else-if="wizardStep === 4" class="wizard-grid"><label v-if="availableSignatories.length" class="wide">Pejabat penandatangan<select v-model="form.signatory_id" @change="selectSignatory"><option :value="null">Pilih pejabat</option><option v-for="signer in availableSignatories" :key="signer.id" :value="signer.id">{{ signer.name }} — {{ signer.title }}{{ signer.employee_number ? ` · NIP ${signer.employee_number}` : '' }}{{ signer.provider_configured ? ' · TTE terhubung' : ' · TTE belum terhubung' }}</option></select></label><p v-else class="form-note wide">Belum ada pejabat aktif yang berwenang untuk template ini. Tambahkan profil pejabat atau pilih template lain.</p><p v-if="form.signature_method === 'bsre' && selectedSignatory && !selectedSignatory.provider_configured" class="form-note warning-note wide">Pejabat ini belum memiliki identitas provider BSrE. Minta administrator melengkapinya sebelum mengirim surat.</p><button class="secondary-button signatory-toggle" type="button" @click="creatingSignatory = !creatingSignatory">{{ creatingSignatory ? 'Tutup profil pejabat' : 'Tambah profil pejabat' }}</button><div v-if="creatingSignatory" class="signatory-form wide"><label>Nama lengkap<input v-model="signatoryForm.name" /></label><label>Jabatan<input v-model="signatoryForm.title" /></label><label>NIP<input v-model="signatoryForm.employee_number" /></label><label>Pangkat/golongan<input v-model="signatoryForm.rank" /></label><label class="wide">ID identitas provider BSrE <span class="field-hint">Diisi dari akun/sertifikat BSrE, bukan NIP.</span><input v-model="signatoryForm.provider_reference" maxlength="190" placeholder="Contoh: akun-bsre-kepala-sekolah" /></label><label class="wide">Kewenangan template<select v-model="signatoryForm.template_scopes" multiple><option value="official_school">Surat resmi</option><option value="invitation">Undangan</option><option value="assignment">Surat tugas</option><option value="statement">Surat keterangan</option></select></label><button class="primary-button" type="button" :disabled="signatoryForm.processing" @click="saveSignatory">Simpan profil pejabat</button><p v-if="Object.keys(signatoryForm.errors).length" class="field-error">Periksa nama, jabatan, identitas BSrE, dan kewenangan penandatangan.</p></div><label>Nama penandatangan <b>*</b><input v-model="form.signer_name" maxlength="180" /></label><label>Jabatan <b>*</b><input v-model="form.signer_title" maxlength="180" /></label><label>Jenis nomor identitas<select v-model="form.signer_identifier_type"><option value="nip">NIP</option><option value="nuptk">NUPTK</option><option value="npk">NPK</option></select></label><label>Nomor identitas<input v-model="form.signer_identifier" maxlength="80" /></label><fieldset class="signature-options wide"><legend>Metode tanda tangan</legend><label :class="{ selected: form.signature_method === 'manual' }"><input v-model="form.signature_method" type="radio" value="manual" /><span><strong>Tanda tangan basah</strong><small>Cetak dan unggah kembali hasil pindai PDF.</small></span></label><label :class="{ selected: form.signature_method === 'bsre', 'is-disabled': !tteConfigured }"><input v-model="form.signature_method" type="radio" value="bsre" :disabled="!tteConfigured" /><span><strong>TTE BSrE</strong><small>{{ tteConfigured ? 'Ditandatangani melalui layanan BSrE resmi.' : 'Belum tersedia sampai provider BSrE dikonfigurasi.' }}</small></span></label></fieldset><label class="wide checkbox-label"><input v-model="form.approval_required" type="checkbox" /> Minta persetujuan kepala sekolah</label></div>
                    </template>
                    <div v-if="wizardStep === maxStep" class="review-card"><div><span>Nomor</span><strong>{{ form.letter_number || 'Nomor agenda otomatis' }}</strong></div><div><span>Perihal</span><strong>{{ form.subject }}</strong></div><div><span>{{ form.letter_type === 'incoming' ? 'Asal' : 'Tujuan' }}</span><strong>{{ form.sender || form.recipient }}</strong></div><div v-if="form.letter_type === 'outgoing'"><span>Penandatangan</span><strong>{{ form.signer_name }} · {{ form.signature_method === 'bsre' ? 'TTE BSrE' : 'Tanda tangan basah' }}</strong></div><a v-if="editingId && form.letter_type === 'outgoing'" :href="`/administrasi/persuratan/surat/${editingId}/pdf`" target="_blank" rel="noopener">Buka preview PDF</a><p v-else-if="form.letter_type === 'outgoing'">Simpan draf terlebih dahulu untuk membuka preview PDF.</p></div>
                    <p v-if="wizardError" class="wizard-error" role="alert">{{ wizardError }}</p>
                    <div v-if="Object.keys(form.errors).length" class="wizard-error" role="alert"><strong>Periksa kembali isian:</strong><ul><li v-for="(message, field) in form.errors" :key="field">{{ message }}</li></ul></div>
                    <div class="wizard-actions"><button v-if="wizardStep > 1" class="secondary-button" type="button" @click="previousStep">← Sebelumnya</button><span></span><button class="secondary-button" type="button" @click="creating = false">Batal</button><button v-if="wizardStep < maxStep" class="primary-button" type="button" @click="nextStep">Lanjutkan →</button><button v-else class="primary-button" type="submit" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : editingId ? 'Simpan perubahan' : form.letter_type === 'incoming' ? 'Catat surat' : 'Simpan draf' }}</button></div>
                </form>
            </section>

            <section class="panel correspondence-panel">
                <div class="table-title">
                    <div><h2>{{ props.filter === 'all' ? 'Surat tersimpan & arsip' : props.filter === 'incoming' ? 'Antrean surat masuk' : props.filter === 'outgoing' ? 'Antrean surat keluar' : 'Arsip surat sekolah' }}</h2><p>{{ props.filter === 'all' ? 'Cari surat yang sudah dicatat atau diarsipkan dari satu tabel.' : props.filter === 'incoming' ? 'Kerjakan baris dari atas ke bawah. Setiap tindakan membuka tahap berikutnya.' : props.filter === 'outgoing' ? 'Lengkapi draf, minta persetujuan, lalu selesaikan tanda tangan.' : 'Cari kembali surat yang sudah selesai berdasarkan jenis dan tanggal.' }}</p></div>
                </div>
                <div v-if="props.filter === 'all'" class="dashboard-filter-bar" aria-label="Filter surat tersimpan dan arsip">
                    <label>Status penyimpanan<select v-model="dashboardStorage"><option value="all">Semua tersimpan</option><option value="saved">Belum diarsipkan</option><option value="archived">Sudah diarsipkan</option></select></label>
                    <label>Jenis surat<select v-model="dashboardType"><option value="all">Semua jenis</option><option value="incoming">Surat masuk</option><option value="outgoing">Surat keluar</option></select></label>
                    <label>Dari tanggal<input v-model="dashboardDateFrom" type="date" /></label>
                    <label>Sampai tanggal<input v-model="dashboardDateTo" type="date" /></label>
                </div>
                <div v-if="props.filter === 'archived'" class="dashboard-filter-bar archive-filter-bar" aria-label="Filter arsip surat">
                    <label>Jenis surat<select v-model="archiveType"><option value="all">Semua jenis</option><option value="incoming">Surat masuk</option><option value="outgoing">Surat keluar</option></select></label>
                    <label>Tanggal surat dari<input v-model="archiveLetterDateFrom" type="date" /></label>
                    <label>Tanggal surat sampai<input v-model="archiveLetterDateTo" type="date" /></label>
                    <label>Diarsipkan dari<input v-model="archiveDateFrom" type="date" /></label>
                    <label>Diarsipkan sampai<input v-model="archiveDateTo" type="date" /></label>
                </div>
                <nav v-if="props.filter === 'all'" class="correspondence-tabs" aria-label="Filter surat">
                    <button v-for="tab in [{ value: 'all', label: 'Semua Surat' }, { value: 'incoming', label: 'Surat Masuk' }, { value: 'outgoing', label: 'Surat Keluar' }, { value: 'approval', label: 'Persetujuan' }, { value: 'signature', label: 'Tanda tangan' }, { value: 'ready', label: 'Siap Dikirim' }, { value: 'archived', label: 'Arsip' }]" :key="tab.value" type="button" class="correspondence-tab" :class="{ 'is-active': activeSection === tab.value }" @click="activeSection = tab.value as typeof activeSection">{{ tab.label }}</button>
                </nav>

                <label class="search-label">Cari surat
                    <input v-model="search" type="search" placeholder="Perihal, nomor, asal, atau tujuan" />
                </label>
                <div v-if="visibleRecords.length" class="table-scroll" tabindex="0" role="region" aria-label="Daftar surat tersimpan">
                    <table class="app-table correspondence-table">
                        <thead><tr><th scope="col">Nomor & tanggal</th><th scope="col">Pihak terkait</th><th scope="col">Perihal</th><th scope="col">Status</th><th scope="col">Tindakan berikutnya</th></tr></thead>
                        <tbody>
                        <tr v-for="record in visibleRecords" :key="record.id">
                            <td data-label="Nomor & tanggal"><strong>{{ record.letter_number || record.agenda_number || '—' }}</strong><small>{{ record.letter_date || record.received_at || 'Tanggal belum diisi' }}</small><small v-if="props.filter === 'archived'">Diarsipkan {{ record.archived_at?.slice(0, 16) || '—' }}</small></td>
                            <td data-label="Pihak terkait"><div class="party-cell-content"><span class="correspondence-kind" :class="record.letter_type === 'incoming' ? 'kind-incoming' : 'kind-outgoing'">{{ record.letter_type === 'incoming' ? 'Masuk' : 'Keluar' }}</span><strong>{{ record.letter_type === 'incoming' ? record.sender : record.recipient }}</strong><small>{{ record.letter_type === 'incoming' ? 'Pengirim' : 'Penerima' }}</small></div></td>
                            <td data-label="Perihal"><strong>{{ record.subject }}</strong><small v-if="record.summary">{{ record.summary }}</small><small v-if="record.disposition"><b>Disposisi:</b> {{ record.disposition }}</small></td>
                            <td data-label="Status"><span class="status-chip" :class="`status-${record.status}`">{{ labelForStatus(record) }}</span><small v-if="record.signature_verified" class="signature-verified-note">TTE terverifikasi<span v-if="record.signature_signed_at"> · {{ record.signature_signed_at }}</span></small><small v-else-if="record.signature_method === 'bsre' && ['pending', 'processing'].includes(record.signature_status || '')">Sedang diproses melalui BSrE</small><small v-if="record.signature_error_message" class="signature-error-note">{{ record.signature_error_message }}</small></td>
                            <td data-label="Tindakan berikutnya" class="action-cell"><div class="record-actions">
                                <div class="primary-action-group">
                                    <button v-if="can_manage && record.status === 'draft' && record.approval_required && ['pending', 'rejected'].includes(record.approval_status)" class="row-action-button primary-row-action" type="button" :disabled="actionBusy[record.id]" @click="submitApproval(record)">{{ record.approval_status === 'rejected' ? 'Ajukan ulang' : 'Ajukan persetujuan' }}</button>
                                    <button v-if="can_manage && record.signature_method === 'manual' && ['draft', 'approved'].includes(record.status) && (!record.approval_required || record.approval_status === 'approved')" class="row-action-button primary-row-action" type="button" :disabled="actionBusy[record.id]" @click="signatureAction(record, 'manual-prepare')">Siapkan tanda tangan</button>
                                    <label v-if="can_manage && record.signature_method === 'manual' && record.status === 'awaiting_signature'" class="row-action-button primary-row-action upload-action" :class="{ disabled: actionBusy[record.id] }">Unggah hasil tanda tangan<input class="sr-only" type="file" accept="application/pdf,.pdf" :disabled="actionBusy[record.id]" @change="uploadManualSignature(record, $event)" /></label>
                                    <button v-if="can_manage && record.signature_method === 'bsre' && ['draft', 'approved'].includes(record.status) && (!record.approval_required || record.approval_status === 'approved')" class="row-action-button primary-row-action" type="button" :disabled="actionBusy[record.id] || !tteConfigured" :title="tteConfigured ? 'Kirim ke antrean TTE BSrE' : 'TTE BSrE belum dikonfigurasi'" @click="signatureAction(record, 'bsre')">{{ tteConfigured ? 'Kirim untuk TTE' : 'TTE belum siap' }}</button>
                                    <button v-if="can_manage && record.signature_method === 'bsre' && record.status === 'signature_failed' && record.signature_request_id" class="row-action-button primary-row-action" type="button" :disabled="actionBusy[record.id]" @click="signatureAction(record, 'bsre-retry')">Ulangi TTE</button>
                                    <template v-if="!can_manage && record.approval_required && record.status === 'pending_approval' && record.approval_status === 'pending'">
                                        <textarea v-model="approvalNotes[record.id]" class="approval-note-input" rows="2" placeholder="Catatan keputusan (opsional)" aria-label="Catatan keputusan" />
                                        <button class="primary-button approval-approve-button" type="button" :disabled="actionBusy[record.id]" @click="approveRecord(record, 'approve')">Setujui</button>
                                        <button class="secondary-button approval-reject-button" type="button" :disabled="actionBusy[record.id]" @click="approveRecord(record, 'reject')">Tolak</button>
                                    </template>
                                </div>
                                <div class="secondary-action-group">
                                    <button v-if="can_manage && record.letter_type === 'incoming' && ['received', 'disposition'].includes(record.status)" class="row-action-button secondary-row-action" type="button" @click="editIncoming(record)">Koreksi data</button>
                                    <button v-if="can_manage && record.letter_type === 'outgoing' && record.status === 'draft' && record.has_document" class="row-action-button secondary-row-action" type="button" @click="editDraft(record)">Ubah draf</button>
                                    <a v-if="record.has_document" :href="`/administrasi/persuratan/surat/${record.id}/pdf`" target="_blank" rel="noopener" class="row-action-link pdf-action-link"><span aria-hidden="true">PDF</span>{{ record.status === 'draft' ? 'Lihat PDF draf' : 'Buka PDF surat' }}</a>
                                    <a v-if="record.has_attachment" :href="`/administrasi/persuratan/surat/${record.id}/lampiran`" class="row-action-link">Unduh lampiran</a>
                                </div>
                            </div>
                            <form v-if="can_manage && nextStatuses(record).length" class="status-update-form" @submit.prevent="updateStatus(record)">
                                <span class="next-step-label">Berikutnya: {{ nextStatuses(record)[0]?.label }}</span>
                                <input v-if="record.letter_type === 'incoming' && (targets[record.id] || nextStatuses(record)[0]?.value) === 'disposition'" v-model="dispositions[record.id]" maxlength="3000" placeholder="Isi disposisi" required />
                                <button class="primary-button status-action-button" type="submit">{{ nextActionLabel(record) }}</button>
                                <span v-if="statusErrors[record.id]" class="field-error" role="alert">{{ statusErrors[record.id] }}</span>
                            </form>
                            <span v-if="actionErrors[record.id]" class="field-error action-error" role="alert">{{ actionErrors[record.id] }}</span>
                            <span v-if="record.status === 'archived'" class="archived-note">Tersimpan di arsip</span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="empty-state">{{ search ? 'Tidak ada surat yang cocok dengan pencarian.' : 'Belum ada surat pada filter ini. Surat yang dicatat akan muncul di sini.' }}</p>
                <div v-if="pageCount > 1" class="pagination">
                    <button type="button" class="secondary-button" :disabled="currentPage === 1" @click="currentPage--">Sebelumnya</button>
                    <span>Halaman {{ currentPage }} dari {{ pageCount }}</span>
                    <button type="button" class="secondary-button" :disabled="currentPage === pageCount" @click="currentPage++">Berikutnya</button>
                </div>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.correspondence-panel { padding: 1.25rem; }
.queue-section { display: grid; gap: .75rem; }
.correspondence-hero-metrics { display: grid; flex: 1 1 24rem; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .65rem; min-width: 0; }
.correspondence-hero-metrics > div { display: grid; gap: .2rem; min-width: 0; border: 1px solid transparent; border-radius: 1rem; padding: 1rem; box-shadow: 0 12px 22px rgb(15 23 42 / 12%); }
.correspondence-hero-metrics > div:nth-child(1) { border-color: #2f64c6; background: linear-gradient(145deg, #1d4ed8, #2563eb); }
.correspondence-hero-metrics > div:nth-child(2) { border-color: #21865a; background: linear-gradient(145deg, #047857, #16a34a); }
.correspondence-hero-metrics > div:nth-child(3) { border-color: #b7791f; background: linear-gradient(145deg, #b45309, #d97706); }
.correspondence-hero-metrics span, .correspondence-hero-metrics small { color: rgb(255 255 255 / 82%); font-size: .72rem; }
.correspondence-hero-metrics strong { color: white; font-size: 1.7rem; line-height: 1; }
.archive-hero-summary { display: grid; flex: 1 1 28rem; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .55rem; min-width: 0; }
.archive-hero-summary > div { display: grid; align-content: center; gap: .2rem; min-height: 6.5rem; border: 1px solid #d6c7a3; border-radius: .9rem; background: #fffaf0; padding: .8rem; }
.archive-hero-summary > div:nth-child(2) { border-color: #c9d7f3; background: #eff5ff; }
.archive-hero-summary > div:nth-child(3) { border-color: #b9dec8; background: #ecfdf3; }
.archive-hero-summary strong { color: #172b4d; font-size: 1.7rem; line-height: 1; }
.archive-hero-summary span { color: #52637c; font-size: .76rem; font-weight: 800; }
.archive-hero-summary small { color: #64748b; font-size: .72rem; }
.incoming-hero-summary { display: grid; flex: 0 0 14rem; align-content: center; gap: .15rem; min-height: 7rem; border: 1px solid #2f64c6; border-radius: 1rem; background: linear-gradient(145deg, #1d4ed8, #2563eb); padding: 1rem 1.2rem; box-shadow: 0 12px 22px rgb(15 23 42 / 12%); }
.incoming-hero-summary strong { color: white; font-size: 2rem; line-height: 1; }
.incoming-hero-summary span, .incoming-hero-summary small { color: rgb(255 255 255 / 84%); font-size: .78rem; }
.outgoing-hero-summary { display: grid; flex: 1 1 28rem; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .55rem; min-width: 0; }
.outgoing-hero-summary > div { display: grid; align-content: center; gap: .2rem; min-height: 6.5rem; border: 1px solid #3157a4; border-radius: .9rem; background: #eff5ff; padding: .8rem; }
.outgoing-hero-summary > div:nth-child(2) { border-color: #d5a03a; background: #fff7e6; }
.outgoing-hero-summary > div:nth-child(3) { border-color: #8b6cc7; background: #f4efff; }
.outgoing-hero-summary > div:nth-child(4) { border-color: #21865a; background: #ecfdf3; }
.outgoing-hero-summary strong { color: #172b4d; font-size: 1.7rem; line-height: 1; }
.outgoing-hero-summary span { color: #52637c; font-size: .72rem; font-weight: 800; }
.incoming-flow-panel { display: grid; gap: 1rem; border: 1px solid #dbe3ef; border-radius: 1rem; background: #fff; padding: 1.25rem; }
.incoming-flow-list { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .55rem; margin: 0; padding: 0; list-style: none; }
.incoming-flow-list li { display: grid; grid-template-columns: 2rem minmax(0, 1fr); align-items: start; gap: .55rem; min-width: 0; border-top: 3px solid #dbe3ef; padding: .75rem .25rem 0; color: #64748b; }
.incoming-flow-list li.is-current { border-top-color: #3157a4; color: #3157a4; }
.incoming-flow-list li > span { display: grid; width: 1.8rem; height: 1.8rem; place-items: center; border-radius: 50%; background: #eef2f7; color: #64748b; font-size: .75rem; font-weight: 800; }
.incoming-flow-list li.is-current > span { background: #3157a4; color: white; }
.incoming-flow-list strong, .incoming-flow-list small { display: block; }
.incoming-flow-list strong { color: #172b4d; font-size: .82rem; }
.incoming-flow-list small { margin-top: .2rem; color: #64748b; font-size: .72rem; line-height: 1.35; }
.outgoing-flow-panel { display: grid; gap: 1rem; border: 1px solid #dbe3ef; border-radius: 1rem; background: #fff; padding: 1.25rem; }
.outgoing-flow-list { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .55rem; margin: 0; padding: 0; list-style: none; }
.outgoing-flow-list li { display: grid; grid-template-columns: 2rem minmax(0, 1fr); align-items: start; gap: .55rem; min-width: 0; border-top: 3px solid #dbe3ef; padding: .75rem .25rem 0; color: #64748b; }
.outgoing-flow-list li.is-current { border-top-color: #3157a4; color: #3157a4; }
.outgoing-flow-list li > span { display: grid; width: 1.8rem; height: 1.8rem; place-items: center; border-radius: 50%; background: #eef2f7; color: #64748b; font-size: .75rem; font-weight: 800; }
.outgoing-flow-list li.is-current > span { background: #3157a4; color: white; }
.outgoing-flow-list strong, .outgoing-flow-list small { display: block; }
.outgoing-flow-list strong { color: #172b4d; font-size: .82rem; }
.outgoing-flow-list small { margin-top: .2rem; color: #64748b; font-size: .72rem; line-height: 1.35; }
.queue-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .75rem; }
.queue-grid button { display: grid; gap: .2rem; min-width: 0; border: 1px solid #dbe3ef; border-radius: .8rem; background: white; padding: 1rem; color: #334155; text-align: left; }
.queue-grid button:hover, .queue-grid button.is-selected { border-color: #3157a4; box-shadow: 0 4px 14px rgb(49 87 164 / 10%); }
.queue-grid button.is-selected { background: #eff5ff; }
.queue-grid span, .queue-grid small { color: #64748b; font-size: .74rem; }
.queue-grid strong { color: #172b4d; font-size: 1.65rem; }
.wizard-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.wizard-heading h2 { margin: .15rem 0 0; color: #172b4d; }
.wizard-close { border: 0; background: transparent; color: #64748b; font-size: 1.6rem; }
.stepper { display: grid; grid-template-columns: repeat(5, 1fr); margin: 1.2rem 0; padding: 0; list-style: none; }
.stepper li { position: relative; display: grid; justify-items: center; gap: .3rem; color: #94a3b8; }
.stepper li:not(:last-child)::after { position: absolute; top: .85rem; left: calc(50% + 1.15rem); width: calc(100% - 2.3rem); height: 2px; background: #e2e8f0; content: ''; }
.stepper span { z-index: 1; display: grid; width: 1.75rem; height: 1.75rem; place-items: center; border: 2px solid #cbd5e1; border-radius: 50%; background: white; font-size: .72rem; font-weight: 800; }
.stepper .active, .stepper .done { color: #3157a4; }
.stepper .active span, .stepper .done span { border-color: #3157a4; background: #3157a4; color: white; }
.stepper .done:not(:last-child)::after { background: #3157a4; }
.wizard-step-hint { margin: -.35rem 0 .2rem; color: #52637c; font-size: .82rem; line-height: 1.5; }
.wizard-form { display: grid; gap: 1rem; }
.wizard-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
.wizard-grid label { display: grid; gap: .4rem; color: #42526b; font-size: .84rem; font-weight: 700; }
.wizard-grid .wide { grid-column: 1 / -1; }
.wizard-grid input, .wizard-grid select, .wizard-grid textarea { width: 100%; min-height: 2.7rem; border: 1px solid #cbd5e1; border-radius: .65rem; background: white; padding: .6rem .75rem; color: #172b4d; font: inherit; }
.wizard-grid b { color: #b42318; }
.incoming-review-note { display: grid; gap: .35rem; border: 1px solid #c9d7f3; border-radius: .75rem; background: #eff5ff; padding: .85rem; color: #3157a4; font-size: .84rem; line-height: 1.5; }
.tte-readiness-panel { display: flex; align-items: flex-start; gap: .9rem; border: 1px solid #c9d7f3; border-radius: .85rem; background: #eff5ff; padding: 1rem 1.1rem; }
.tte-readiness-panel.is-ready { border-color: #b7e4cf; background: #effaf4; }
.tte-readiness-panel.is-blocked { border-color: #f1d19a; background: #fff8eb; }
.tte-readiness-mark { display: grid; flex: 0 0 2rem; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; background: #3157a4; color: white; font-weight: 800; }
.tte-readiness-panel.is-ready .tte-readiness-mark { background: #087a55; }
.tte-readiness-panel.is-blocked .tte-readiness-mark { background: #a15c00; }
.tte-readiness-panel h2 { margin: .15rem 0 .25rem; color: #172b4d; font-size: 1rem; }
.tte-readiness-panel p:not(.eyebrow) { margin: 0; max-width: 72ch; color: #52637c; font-size: .84rem; line-height: 1.55; }
.tte-readiness-panel small { display: block; margin-top: .45rem; color: #64748b; font-size: .75rem; }
.warning-note { border: 1px solid #f1d19a; background: #fff8eb; color: #92500a; }
.signature-options label.is-disabled { cursor: not-allowed; opacity: .58; }
.signatory-toggle { justify-self: start; }
.signatory-form { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: .75rem; border: 1px solid #dbe3ef; border-radius: .75rem; background: #f8fafc; padding: 1rem; }
.signatory-form .wide { grid-column: 1 / -1; }
.template-choices { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.template-choices label, .signature-options label { display: flex; gap: .65rem; border: 1px solid #dbe3ef; border-radius: .75rem; padding: 1rem; color: #334155; }
.template-choices label.selected, .signature-options label.selected { border-color: #3157a4; background: #eff5ff; }
.template-choices input, .signature-options input, .checkbox-label input { width: auto; min-height: auto; }
.template-choices span, .signature-options span { display: grid; gap: .2rem; }
.template-choices small, .signature-options small { color: #64748b; font-weight: 500; }
.signature-options { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; margin: 0; border: 0; padding: 0; }
.signature-options legend { margin-bottom: .5rem; color: #42526b; font-weight: 700; }
.checkbox-label { display: flex !important; align-items: center; }
.review-card { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; border: 1px solid #dbe3ef; border-radius: .8rem; background: #f8fafc; padding: 1rem; }
.review-card div { display: grid; gap: .15rem; }
.review-card span { color: #64748b; font-size: .74rem; }
.review-card a, .review-card p { grid-column: 1 / -1; margin: 0; color: #3157a4; font-size: .84rem; font-weight: 700; }
.wizard-error { margin: 0; border: 1px solid #fda29b; border-radius: .65rem; background: #fef3f2; padding: .75rem; color: #b42318; font-size: .82rem; }
.wizard-actions { display: flex; align-items: center; gap: .5rem; border-top: 1px solid #e2e8f0; padding-top: 1rem; }
.wizard-actions > span { flex: 1; }
.upload-action { cursor: pointer; }
.upload-action.disabled { cursor: wait; opacity: .55; }
.approval-note-input { width: min(19rem, 100%); min-height: 2.7rem; border: 1px solid #cbd5e1; border-radius: .6rem; padding: .55rem .7rem; color: #172b4d; font: inherit; font-size: .8rem; }
.hero-card { flex-wrap: wrap; }
.hero-card > div:first-child { min-width: 0; }
.correspondence-form { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
.correspondence-form label { display: grid; gap: .4rem; color: #42526b; font-size: .85rem; font-weight: 600; }
.correspondence-form input, .correspondence-form select, .correspondence-form textarea, .status-update-form select, .status-update-form input { width: 100%; min-height: 2.7rem; border: 1px solid #cbd5e1; border-radius: .65rem; background: white; padding: .6rem .75rem; color: #172b4d; }
.correspondence-form input[type=file] { padding: .4rem; }
.correspondence-wide { grid-column: 1 / -1; }
.correspondence-actions { display: flex; justify-content: end; gap: .5rem; flex-wrap: wrap; }
.workflow-actions, .pagination { display: flex; align-items: center; flex-wrap: wrap; gap: .6rem; }
.workflow-help, .form-note { margin: .8rem 0 0; color: #52637c; font-size: .85rem; line-height: 1.55; }
.form-note { margin: 0; padding: .75rem; background: #eff5ff; border-radius: .6rem; }
.dashboard-filter-bar { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-top: 1rem; border: 1px solid #dbe3ef; border-radius: .8rem; background: #f8fafc; padding: .85rem; }
.dashboard-filter-bar label { display: grid; gap: .35rem; color: #42526b; font-size: .76rem; font-weight: 700; }
.dashboard-filter-bar input, .dashboard-filter-bar select { width: 100%; min-height: 2.5rem; border: 1px solid #cbd5e1; border-radius: .55rem; background: white; padding: .5rem .65rem; color: #172b4d; font: inherit; font-size: .8rem; }
.search-label { display: grid; gap: .35rem; margin-top: 1rem; max-width: 26rem; color: #42526b; font-size: .85rem; font-weight: 600; }
.search-label input { width: 100%; min-height: 2.7rem; border: 1px solid #cbd5e1; border-radius: .65rem; padding: .6rem .75rem; background: white; color: #172b4d; }
.pagination { justify-content: end; margin-top: 1rem; color: #52637c; font-size: .85rem; }
.secondary-button:disabled { opacity: .5; cursor: not-allowed; }
.correspondence-card, .correspondence-card-heading > div { min-width: 0; overflow-wrap: anywhere; }
.primary-button, .secondary-button { border: 1px solid #3157a4; border-radius: .65rem; background: #3157a4; padding: .65rem 1rem; color: white; font-weight: 700; }
.secondary-button { background: white; color: #3157a4; white-space: nowrap; }
.primary-button:disabled { cursor: wait; opacity: .6; }
.required-mark, .field-error { color: #b42318; }
.field-hint, .approval-option small { color: #64748b; font-size: .76rem; font-weight: 500; line-height: 1.4; }
.approval-option { gap: .25rem !important; }
.approval-option input { width: auto; min-height: auto; margin-right: .35rem; }
.approval-actions { display: flex; align-items: center; flex-wrap: wrap; gap: .5rem; margin-top: .75rem; border-top: 1px solid #e2e8f0; padding-top: .75rem; }
.approval-pending { color: #92500a; font-size: .82rem; font-weight: 700; }
.field-error { font-size: .78rem; font-weight: 500; }
.action-error { flex-basis: 100%; }
.correspondence-tabs { display: flex; flex-wrap: wrap; gap: .5rem; border-bottom: 1px solid #dbe3ef; padding: 0 0 .8rem; }
.correspondence-tab { border: 1px solid #dbe3ef; border-radius: 999px; background: white; padding: .45rem .85rem; color: #42526b; font-size: .85rem; font-weight: 600; text-decoration: none; }
.correspondence-tab.is-active { border-color: #3157a4; background: #eaf0ff; color: #214991; }
.table-scroll { margin-top: 1rem; overflow-x: auto; border: 1px solid #dbe3ef; border-radius: .85rem; }
.correspondence-table { width: 100%; min-width: 920px; border-collapse: collapse; }
.correspondence-table th { background: #f8fafc; color: #52637c; font-size: .72rem; letter-spacing: .03em; text-align: left; text-transform: uppercase; }
.correspondence-table th, .correspondence-table td { border-bottom: 1px solid #e2e8f0; padding: .8rem; vertical-align: top; }
.correspondence-table tbody tr:last-child td { border-bottom: 0; }
.correspondence-table td { color: #334155; font-size: .84rem; }
.correspondence-table td > small { display: block; margin-top: .25rem; color: #64748b; line-height: 1.35; }
.correspondence-table td:nth-child(1) { width: 17%; }
.correspondence-table td:nth-child(2) { width: 18%; }
.correspondence-table td:nth-child(3) { width: 25%; }
.correspondence-table td:nth-child(4) { width: 13%; }
.party-cell-content { display: grid; align-content: start; justify-items: start; gap: .25rem; min-width: 0; }
.party-cell-content strong { max-width: 100%; color: #172b4d; line-height: 1.35; overflow-wrap: anywhere; }
.party-cell-content small { margin-top: 0; color: #64748b; font-size: .74rem; line-height: 1.3; }
.party-cell-content .correspondence-kind { margin-bottom: .05rem; }
.action-cell { width: 30%; min-width: 18rem; }
.action-cell .record-actions { display: grid; align-items: start; gap: .45rem; }
.primary-action-group, .secondary-action-group { display: flex; align-items: center; flex-wrap: wrap; gap: .4rem; width: 100%; }
.secondary-action-group { border-top: 1px solid #edf1f7; padding-top: .45rem; }
.primary-row-action, .secondary-row-action { min-width: 0; min-height: 34px; padding: .42rem .62rem; border-radius: .55rem; font-size: .74rem; line-height: 1.25; white-space: normal; text-align: left; }
.primary-action-group > .row-action-button, .primary-action-group > .primary-button, .primary-action-group > .secondary-button { flex: 1 1 10rem; justify-content: center; text-align: center; }
.primary-action-group > .approval-note-input { flex: 1 1 100%; width: 100%; }
.secondary-action-group > .row-action-button { flex: 1 1 10rem; justify-content: center; text-align: center; }
.primary-row-action { border-color: #3157a4; background: #3157a4; color: white; }
.primary-row-action:hover { border-color: #214991; background: #214991; color: white; }
.primary-row-action:disabled { cursor: wait; opacity: .6; }
.secondary-row-action { background: white; }
.row-action-link { display: inline-flex; align-items: center; min-height: 30px; padding: .25rem .15rem; color: #3157a4; font-size: .74rem; font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
.pdf-action-link { flex: 1 1 10rem; justify-content: center; gap: .4rem; min-height: 34px; border: 1px solid #f2b8c0; border-radius: .55rem; background: #fff1f2; padding: .42rem .62rem; color: #be123c; text-align: center; text-decoration: none; }
.pdf-action-link:hover { border-color: #e88a99; background: #ffe4e8; color: #9f1239; }
.pdf-action-link > span { display: inline-grid; min-width: 1.75rem; min-height: 1.25rem; place-items: center; border-radius: .25rem; background: #e11d48; color: white; font-size: .57rem; font-weight: 800; letter-spacing: .02em; line-height: 1; }
.approval-approve-button, .approval-reject-button, .status-action-button { min-height: 34px; padding: .42rem .7rem; border-radius: .55rem; font-size: .76rem; }
.approval-approve-button { border-color: #087a55; background: #087a55; box-shadow: none; }
.approval-approve-button:hover { border-color: #066044; background: #066044; }
.approval-reject-button { border-color: #f0b7b1; background: #fff7f6; color: #b42318; }
.approval-reject-button:hover { border-color: #e58b82; background: #fef0ee; color: #941f16; }
.status-update-form { margin-top: .05rem; border-top: 1px solid #edf1f7; padding-top: .55rem; }
.status-action-button { box-shadow: none; }
.status-action-button:hover { box-shadow: 0 5px 12px color-mix(in srgb, var(--primary) 20%, transparent); }
.correspondence-list { display: grid; gap: .85rem; padding-top: 1rem; }
.correspondence-card { border: 1px solid #dbe3ef; border-radius: .85rem; padding: 1rem; }
.correspondence-card-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.correspondence-card h3 { margin: .3rem 0 .2rem; color: #172b4d; font-size: 1.05rem; font-weight: 700; }
.correspondence-card-heading p, .muted-note, .archived-note { margin: 0; color: #64748b; font-size: .82rem; }
.correspondence-kind, .status-chip { display: inline-flex; border-radius: 999px; padding: .2rem .55rem; font-size: .75rem; font-weight: 700; }
.kind-incoming { background: #eaf0ff; color: #3157a4; }
.kind-outgoing { background: #f1f5f9; color: #475569; }
.status-chip { background: #ecfdf3; color: #067647; white-space: nowrap; }
.status-chip.status-received { background: #eef4ff; color: #3157a4; }
.status-chip.status-disposition { background: #fff7e6; color: #a15c00; }
.status-chip.status-done, .status-chip.status-archived { background: #ecfdf3; color: #067647; }
.status-chip.status-draft, .status-chip.status-approved { background: #eef4ff; color: #3157a4; }
.status-chip.status-pending_approval, .status-chip.status-awaiting_signature, .status-chip.status-signature_pending { background: #fff7e6; color: #a15c00; }
.status-chip.status-signature_failed { background: #fef3f2; color: #b42318; }
.signature-verified-note { color: #087a55 !important; font-weight: 700; }
.signature-error-note { color: #b42318 !important; }
.status-chip.status-issued, .status-chip.status-sent { background: #ecfdf3; color: #067647; }
.correspondence-meta { display: flex; flex-wrap: wrap; gap: .65rem 1.5rem; margin: .9rem 0; }
.correspondence-meta div { display: grid; gap: .1rem; }
.correspondence-meta dt { color: #64748b; font-size: .72rem; }
.correspondence-meta dd { margin: 0; color: #334155; font-size: .82rem; font-weight: 600; }
.correspondence-summary-text, .correspondence-disposition { margin: .5rem 0; color: #475569; font-size: .86rem; white-space: pre-wrap; }
.correspondence-footer { display: flex; align-items: center; justify-content: space-between; gap: .75rem; border-top: 1px solid #e2e8f0; padding-top: .8rem; }
.status-update-form { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-start; gap: .45rem; }
.next-step-label { flex: 1 1 100%; color: #3157a4; font-size: .75rem; font-weight: 800; }
.status-update-form select { width: auto; min-width: 9rem; }
.status-update-form input { width: min(22rem, 100%); flex: 1 1 14rem; }
.row-action-button { display: inline-flex; min-height: 34px; align-items: center; justify-content: center; border: 1px solid #c9d7f3; border-radius: .55rem; background: #eff5ff; padding: .42rem .62rem; color: #3157a4; font-size: .74rem; font-weight: 700; line-height: 1.25; text-align: center; text-decoration: none; white-space: normal; }
.empty-state { padding: 1.25rem 0; color: #64748b; }
@media (min-width: 761px) { .hero-card > div:first-child { flex: 1 1 18rem; } }
@media (max-width: 1100px) { .queue-grid { grid-template-columns: repeat(3, 1fr); } .template-choices { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 800px) { .correspondence-hero-metrics, .archive-hero-summary { width: 100%; } .dashboard-filter-bar { grid-template-columns: repeat(2, minmax(0, 1fr)); } .outgoing-hero-summary { width: 100%; } .queue-grid { grid-template-columns: repeat(2, 1fr); } .stepper small { display: none; } .wizard-grid, .signature-options, .review-card, .signatory-form { grid-template-columns: 1fr; } .wizard-grid .wide, .signatory-form .wide { grid-column: auto; } .correspondence-form { grid-template-columns: 1fr; } .correspondence-wide { grid-column: auto; } .correspondence-footer { align-items: stretch; flex-direction: column; } .status-update-form { justify-content: stretch; } .status-update-form select, .status-update-form input, .status-update-form button { width: 100%; } .correspondence-card-heading { align-items: start; } .correspondence-table { min-width: 0; } .correspondence-table thead { display: none; } .correspondence-table tr { display: grid; border-bottom: 1px solid #dbe3ef; padding: .75rem; } .correspondence-table tr:last-child { border-bottom: 0; } .correspondence-table td { display: grid; grid-template-columns: 7.5rem minmax(0,1fr); width: 100% !important; border: 0; padding: .35rem 0; } .correspondence-table td::before { color: #64748b; content: attr(data-label); font-size: .72rem; } .action-cell { min-width: 0; } .action-cell .record-actions, .action-cell > .status-update-form, .action-cell > .field-error, .action-cell > .archived-note { grid-column: 1 / -1; } .action-cell .record-actions { padding-top: .35rem; } }
@media (max-width: 800px) { .incoming-flow-list, .outgoing-flow-list { grid-template-columns: repeat(2, minmax(0, 1fr)); } .incoming-hero-summary { width: 100%; } }
@media (max-width: 480px) { .correspondence-hero-metrics > div, .archive-hero-summary > div { min-height: 78px; padding: .75rem; } .correspondence-hero-metrics span, .correspondence-hero-metrics small { font-size: .65rem; } .correspondence-hero-metrics strong { font-size: 1.35rem; } .dashboard-filter-bar { grid-template-columns: 1fr; } .outgoing-hero-summary, .archive-hero-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } .incoming-flow-list, .outgoing-flow-list { grid-template-columns: 1fr; } }
/* Shared card rhythm: workflow and form surfaces use the application tokens. */
.incoming-flow-panel, .outgoing-flow-panel, .correspondence-panel { border-radius: var(--card-radius); background: var(--surface); border-color: var(--line); }
.queue-grid button { border-color: var(--line); border-radius: var(--card-radius-compact); background: var(--surface); color: var(--ink); }
.queue-grid button:hover, .queue-grid button.is-selected { border-color: color-mix(in srgb, var(--primary) 42%, var(--line)); box-shadow: var(--card-shadow); }
.queue-grid button.is-selected { background: var(--primary-soft); }
.incoming-review-note, .tte-readiness-panel { border-color: color-mix(in srgb, var(--primary) 25%, var(--line)); background: var(--primary-soft); }
.signatory-form, .review-card, .dashboard-filter-bar { border-color: var(--line); background: var(--surface-subtle); }
.wizard-error { border-radius: var(--card-radius-compact); }
:global(html[data-theme='dark']) .incoming-page,
:global(html[data-theme='dark']) .outgoing-page,
:global(html[data-theme='dark']) .archive-page { color: var(--ink); }
:global(html[data-theme='dark']) .incoming-page .incoming-flow-panel,
:global(html[data-theme='dark']) .outgoing-page .outgoing-flow-panel,
:global(html[data-theme='dark']) .incoming-page .correspondence-panel,
:global(html[data-theme='dark']) .outgoing-page .correspondence-panel,
:global(html[data-theme='dark']) .archive-page .correspondence-panel,
:global(html[data-theme='dark']) .incoming-page .queue-grid button,
:global(html[data-theme='dark']) .outgoing-page .queue-grid button,
:global(html[data-theme='dark']) .archive-page .queue-grid button,
:global(html[data-theme='dark']) .incoming-page .correspondence-card,
:global(html[data-theme='dark']) .outgoing-page .correspondence-card,
:global(html[data-theme='dark']) .archive-page .correspondence-card { background: var(--surface) !important; border-color: var(--line) !important; color: var(--ink); }
:global(html[data-theme='dark']) .incoming-page .queue-grid button.is-selected,
:global(html[data-theme='dark']) .outgoing-page .queue-grid button.is-selected,
:global(html[data-theme='dark']) .archive-page .queue-grid button.is-selected { background: var(--primary-soft) !important; border-color: var(--primary) !important; }
:global(html[data-theme='dark']) .incoming-page .archive-hero-summary > div,
:global(html[data-theme='dark']) .outgoing-page .outgoing-hero-summary > div,
:global(html[data-theme='dark']) .archive-page .archive-hero-summary > div { background: var(--surface-subtle) !important; border-color: var(--line) !important; }
:global(html[data-theme='dark']) .incoming-page .incoming-hero-summary { background: linear-gradient(145deg, color-mix(in srgb, var(--primary) 45%, var(--surface)), var(--primary-soft)) !important; border-color: var(--primary) !important; }
:global(html[data-theme='dark']) .incoming-page .incoming-flow-list li,
:global(html[data-theme='dark']) .outgoing-page .outgoing-flow-list li { border-top-color: var(--line) !important; color: var(--ink-soft) !important; }
:global(html[data-theme='dark']) .incoming-page .incoming-flow-list li > span,
:global(html[data-theme='dark']) .outgoing-page .outgoing-flow-list li > span { background: var(--surface-strong) !important; color: var(--ink-soft) !important; }
:global(html[data-theme='dark']) .incoming-page .incoming-flow-list strong,
:global(html[data-theme='dark']) .outgoing-page .outgoing-flow-list strong,
:global(html[data-theme='dark']) .incoming-page .queue-grid strong,
:global(html[data-theme='dark']) .outgoing-page .queue-grid strong,
:global(html[data-theme='dark']) .archive-page .queue-grid strong,
:global(html[data-theme='dark']) .incoming-page .correspondence-card h3,
:global(html[data-theme='dark']) .outgoing-page .correspondence-card h3,
:global(html[data-theme='dark']) .archive-page .correspondence-card h3 { color: var(--ink) !important; }
:global(html[data-theme='dark']) .incoming-page .incoming-flow-list small,
:global(html[data-theme='dark']) .outgoing-page .outgoing-flow-list small,
:global(html[data-theme='dark']) .incoming-page .queue-grid span,
:global(html[data-theme='dark']) .incoming-page .queue-grid small,
:global(html[data-theme='dark']) .outgoing-page .queue-grid span,
:global(html[data-theme='dark']) .outgoing-page .queue-grid small,
:global(html[data-theme='dark']) .archive-page .queue-grid span,
:global(html[data-theme='dark']) .archive-page .queue-grid small,
:global(html[data-theme='dark']) .incoming-page .correspondence-card-heading p,
:global(html[data-theme='dark']) .outgoing-page .correspondence-card-heading p,
:global(html[data-theme='dark']) .archive-page .correspondence-card-heading p,
:global(html[data-theme='dark']) .incoming-page .correspondence-meta dt,
:global(html[data-theme='dark']) .outgoing-page .correspondence-meta dt,
:global(html[data-theme='dark']) .archive-page .correspondence-meta dt,
:global(html[data-theme='dark']) .incoming-page .correspondence-card p,
:global(html[data-theme='dark']) .outgoing-page .correspondence-card p,
:global(html[data-theme='dark']) .archive-page .correspondence-card p { color: var(--ink-soft) !important; }
:global(html[data-theme='dark']) .incoming-page .wizard-heading h2,
:global(html[data-theme='dark']) .outgoing-page .wizard-heading h2,
:global(html[data-theme='dark']) .incoming-page .review-card,
:global(html[data-theme='dark']) .outgoing-page .review-card,
:global(html[data-theme='dark']) .incoming-page .signatory-form,
:global(html[data-theme='dark']) .outgoing-page .signatory-form { color: var(--ink); }
:global(html[data-theme='dark']) .incoming-page .wizard-grid input,
:global(html[data-theme='dark']) .incoming-page .wizard-grid select,
:global(html[data-theme='dark']) .incoming-page .wizard-grid textarea,
:global(html[data-theme='dark']) .outgoing-page .wizard-grid input,
:global(html[data-theme='dark']) .outgoing-page .wizard-grid select,
:global(html[data-theme='dark']) .outgoing-page .wizard-grid textarea,
:global(html[data-theme='dark']) .incoming-page .approval-note-input,
:global(html[data-theme='dark']) .outgoing-page .approval-note-input,
:global(html[data-theme='dark']) .incoming-page .correspondence-form input,
:global(html[data-theme='dark']) .incoming-page .correspondence-form select,
:global(html[data-theme='dark']) .incoming-page .correspondence-form textarea,
:global(html[data-theme='dark']) .outgoing-page .correspondence-form input,
:global(html[data-theme='dark']) .outgoing-page .correspondence-form select,
:global(html[data-theme='dark']) .outgoing-page .correspondence-form textarea,
:global(html[data-theme='dark']) .incoming-page .status-update-form input,
:global(html[data-theme='dark']) .incoming-page .status-update-form select,
:global(html[data-theme='dark']) .outgoing-page .status-update-form input,
:global(html[data-theme='dark']) .outgoing-page .status-update-form select,
:global(html[data-theme='dark']) .dashboard-filter-bar input,
:global(html[data-theme='dark']) .dashboard-filter-bar select { background: var(--surface) !important; border-color: var(--line) !important; color: var(--ink) !important; }
:global(html[data-theme='dark']) .incoming-page .signatory-form,
:global(html[data-theme='dark']) .outgoing-page .signatory-form,
:global(html[data-theme='dark']) .incoming-page .review-card,
:global(html[data-theme='dark']) .outgoing-page .review-card,
:global(html[data-theme='dark']) .dashboard-filter-bar { background: var(--surface-subtle) !important; border-color: var(--line) !important; }
:global(html[data-theme='dark']) .incoming-page .template-choices label,
:global(html[data-theme='dark']) .outgoing-page .template-choices label,
:global(html[data-theme='dark']) .incoming-page .signature-options label,
:global(html[data-theme='dark']) .outgoing-page .signature-options label { background: var(--surface) !important; border-color: var(--line) !important; color: var(--ink) !important; }
:global(html[data-theme='dark']) .incoming-page .template-choices label.selected,
:global(html[data-theme='dark']) .outgoing-page .template-choices label.selected,
:global(html[data-theme='dark']) .incoming-page .signature-options label.selected,
:global(html[data-theme='dark']) .outgoing-page .signature-options label.selected { background: var(--primary-soft) !important; border-color: var(--primary) !important; }
:global(html[data-theme='dark']) .incoming-page .stepper li:not(:last-child)::after,
:global(html[data-theme='dark']) .outgoing-page .stepper li:not(:last-child)::after,
:global(html[data-theme='dark']) .incoming-page .correspondence-footer,
:global(html[data-theme='dark']) .outgoing-page .correspondence-footer,
:global(html[data-theme='dark']) .archive-page .correspondence-footer,
:global(html[data-theme='dark']) .incoming-page .correspondence-table tr { background: transparent; border-color: var(--line) !important; }
:global(html[data-theme='dark']) .incoming-page .stepper span,
:global(html[data-theme='dark']) .outgoing-page .stepper span { background: var(--surface) !important; border-color: var(--line) !important; }
:global(html[data-theme='dark']) .incoming-page .incoming-review-note,
:global(html[data-theme='dark']) .outgoing-page .incoming-review-note,
:global(html[data-theme='dark']) .incoming-page .tte-readiness-panel,
:global(html[data-theme='dark']) .outgoing-page .tte-readiness-panel { background: var(--primary-soft) !important; border-color: var(--line) !important; color: var(--ink-soft) !important; }
:global(html[data-theme='dark']) .incoming-page .tte-readiness-panel.is-ready,
:global(html[data-theme='dark']) .outgoing-page .tte-readiness-panel.is-ready { background: var(--green-soft) !important; }
:global(html[data-theme='dark']) .incoming-page .tte-readiness-panel.is-blocked,
:global(html[data-theme='dark']) .outgoing-page .tte-readiness-panel.is-blocked { background: var(--gold-soft) !important; }
:global(html[data-theme='dark']) .incoming-page .warning-note,
:global(html[data-theme='dark']) .outgoing-page .warning-note,
:global(html[data-theme='dark']) .incoming-page .wizard-error,
:global(html[data-theme='dark']) .outgoing-page .wizard-error { background: var(--red-soft) !important; border-color: var(--delete-line) !important; color: var(--red) !important; }
:global(html[data-theme='dark']) .incoming-page .correspondence-table,
:global(html[data-theme='dark']) .outgoing-page .correspondence-table,
:global(html[data-theme='dark']) .archive-page .correspondence-table { border-color: var(--line) !important; }
:global(html[data-theme='dark']) .incoming-page .correspondence-table th,
:global(html[data-theme='dark']) .outgoing-page .correspondence-table th,
:global(html[data-theme='dark']) .archive-page .correspondence-table th { background: var(--surface-subtle) !important; color: var(--ink-soft) !important; }
:global(html[data-theme='dark']) .incoming-page .correspondence-table td,
:global(html[data-theme='dark']) .outgoing-page .correspondence-table td,
:global(html[data-theme='dark']) .archive-page .correspondence-table td,
:global(html[data-theme='dark']) .incoming-page .party-cell-content strong,
:global(html[data-theme='dark']) .outgoing-page .party-cell-content strong,
:global(html[data-theme='dark']) .archive-page .party-cell-content strong { color: var(--ink) !important; }
:global(html[data-theme='dark']) .incoming-page .correspondence-table td > small,
:global(html[data-theme='dark']) .outgoing-page .correspondence-table td > small,
:global(html[data-theme='dark']) .archive-page .correspondence-table td > small,
:global(html[data-theme='dark']) .incoming-page .party-cell-content small,
:global(html[data-theme='dark']) .outgoing-page .party-cell-content small,
:global(html[data-theme='dark']) .archive-page .party-cell-content small { color: var(--ink-soft) !important; }
:global(html[data-theme='dark']) .kind-incoming,
:global(html[data-theme='dark']) .status-chip.status-received,
:global(html[data-theme='dark']) .status-chip.status-draft,
:global(html[data-theme='dark']) .status-chip.status-approved { background: var(--primary-soft) !important; color: var(--primary) !important; }
:global(html[data-theme='dark']) .kind-outgoing { background: var(--surface-strong) !important; color: var(--ink-soft) !important; }
:global(html[data-theme='dark']) .status-chip.status-disposition,
:global(html[data-theme='dark']) .status-chip.status-pending_approval,
:global(html[data-theme='dark']) .status-chip.status-awaiting_signature,
:global(html[data-theme='dark']) .status-chip.status-signature_pending { background: var(--gold-soft) !important; color: var(--gold) !important; }
:global(html[data-theme='dark']) .status-chip.status-done,
:global(html[data-theme='dark']) .status-chip.status-archived,
:global(html[data-theme='dark']) .status-chip.status-issued,
:global(html[data-theme='dark']) .status-chip.status-sent { background: var(--green-soft) !important; color: var(--green) !important; }
:global(html[data-theme='dark']) .status-chip.status-signature_failed { background: var(--red-soft) !important; color: var(--red) !important; }
:global(html[data-theme='dark']) .pdf-action-link { background: var(--pdf-soft) !important; border-color: var(--pdf-line) !important; color: var(--pdf) !important; }
:global(html[data-theme='dark']) .primary-row-action { background: var(--primary-action) !important; border-color: var(--primary-action) !important; color: #fff !important; }
:global(html[data-theme='dark']) .primary-row-action:hover { background: var(--primary-action-strong) !important; border-color: var(--primary-action-strong) !important; }
:global(html[data-theme='dark']) .secondary-row-action,
:global(html[data-theme='dark']) .row-action-button { background: var(--surface-subtle) !important; border-color: var(--line) !important; color: var(--primary) !important; }
.correspondence-page .hero-card, .correspondence-page .panel, .correspondence-page .incoming-flow-panel, .correspondence-page .outgoing-flow-panel { color: var(--ink); background: var(--surface); border-color: var(--line); border-radius: 14px; box-shadow: none; }
.correspondence-page .correspondence-hero-metrics > div, .correspondence-page .incoming-hero-summary, .correspondence-page .outgoing-hero-summary > div, .correspondence-page .archive-hero-summary > div { color: var(--ink); background: var(--surface); border: 1px solid var(--line); border-radius: 12px; box-shadow: none; }
.correspondence-page .correspondence-hero-metrics > div:first-child, .correspondence-page .incoming-hero-summary { border-top: 3px solid var(--primary); }
.correspondence-page .correspondence-hero-metrics > div:nth-child(2) { border-top: 3px solid var(--green); }
.correspondence-page .correspondence-hero-metrics > div:nth-child(3) { border-top: 3px solid var(--gold); }
.correspondence-page .correspondence-hero-metrics strong, .correspondence-page .incoming-hero-summary strong, .correspondence-page .outgoing-hero-summary strong, .correspondence-page .archive-hero-summary strong { color: var(--ink); }
.correspondence-page .correspondence-hero-metrics span, .correspondence-page .correspondence-hero-metrics small, .correspondence-page .incoming-hero-summary span, .correspondence-page .incoming-hero-summary small, .correspondence-page .outgoing-hero-summary span, .correspondence-page .archive-hero-summary span, .correspondence-page .archive-hero-summary small { color: var(--ink-soft); }
.correspondence-page .queue-grid button { background: var(--surface); border-color: var(--line); border-radius: 10px; box-shadow: none; }
.correspondence-page .queue-grid button.is-selected { background: var(--primary-soft); border-color: var(--primary); }
.correspondence-page .dashboard-filter-bar, .correspondence-page .correspondence-card { color: var(--ink); background: var(--surface); border-color: var(--line); border-radius: 12px; }
.correspondence-page .wizard-grid input, .correspondence-page .wizard-grid select, .correspondence-page .wizard-grid textarea, .correspondence-page .dashboard-filter-bar input, .correspondence-page .dashboard-filter-bar select, .correspondence-page .search-label input, .correspondence-page .status-update-form input, .correspondence-page .status-update-form select { min-height: 44px; color: var(--ink); background: var(--surface); border-color: var(--line-strong); border-radius: 10px; }
.correspondence-page button:focus-visible, .correspondence-page input:focus-visible, .correspondence-page select:focus-visible, .correspondence-page textarea:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
@media (max-width: 600px) { .correspondence-page .correspondence-hero-metrics, .correspondence-page .outgoing-hero-summary, .correspondence-page .archive-hero-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } .correspondence-page .incoming-hero-summary { flex-basis: 100%; } .correspondence-page .workflow-actions { align-items: stretch; flex-direction: column; } .correspondence-page .workflow-actions > * { width: 100%; } }
</style>
