<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, reactive, ref, watch } from 'vue';

interface StatusOption { value: string; label: string }
interface Signatory { id: number; name: string; title: string; employee_number?: string | null; rank?: string | null; template_scopes?: string[] | null }
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
    attachment_note: string | null;
    recipient_address?: string | null;
    opening_greeting?: string | null;
    closing_text?: string | null;
    carbon_copy?: string | null;
    signatory_id?: number | null;
    signature_status?: string | null;
    signature_request_id?: number | null;
    signature_error?: string | null;
}

const props = defineProps<{
    records: CorrespondenceRecord[];
    filter: 'all' | 'incoming' | 'outgoing' | 'archived';
    status_options: { incoming: StatusOption[]; outgoing: StatusOption[] };
    can_manage: boolean;
    queue_counts?: Record<string, number>;
    signatories?: Signatory[];
}>();

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
    attachment_note: '',
    approval_required: false,
    signature_method: 'manual',
    attachment: null as File | null,
});

const editingId = ref<number | null>(null);
const creating = ref(false);
const creatingSignatory = ref(false);
const signatoryForm = useForm({ name: '', title: '', employee_number: '', rank: '', template_scopes: [] as string[] });
const wizardStep = ref(1);
const wizardError = ref('');
const activeSection = ref<'incoming' | 'outgoing' | 'approval' | 'archived'>(props.filter === 'outgoing' || props.filter === 'archived' ? props.filter : 'incoming');
const search = ref('');
const currentPage = ref(1);
const sectionRecords = computed(() => props.records.filter((record) => {
    if (activeSection.value === 'archived') return record.status === 'archived';
    if (activeSection.value === 'approval') return record.letter_type === 'outgoing' && (record.approval_status === 'pending' || ['approved', 'awaiting_signature', 'signature_pending', 'signature_failed'].includes(record.status));
    return record.letter_type === activeSection.value && record.status !== 'archived';
}));
const filteredRecords = computed(() => {
    const query = search.value.trim().toLocaleLowerCase();
    return query ? sectionRecords.value.filter((record) => [record.subject, record.letter_number, record.agenda_number, record.sender, record.recipient].some((value) => value?.toLocaleLowerCase().includes(query))) : sectionRecords.value;
});
const pageCount = computed(() => Math.max(1, Math.ceil(filteredRecords.value.length / 10)));
const visibleRecords = computed(() => filteredRecords.value.slice((currentPage.value - 1) * 10, currentPage.value * 10));
watch([search, () => props.filter], () => { currentPage.value = 1; });

function startNew(type: 'incoming' | 'outgoing') {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.letter_type = type;
    creating.value = true; wizardStep.value = 1; wizardError.value = '';
    nextTick(() => document.getElementById('form-surat')?.scrollIntoView({ behavior: 'smooth' }));
}

function editDraft(record: CorrespondenceRecord) {
    editingId.value = record.id;
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
    form.attachment_note = record.attachment_note ?? '';
    form.approval_required = record.approval_required;
    form.signature_method = record.signature_method || 'manual';
    creating.value = true;
    nextTick(() => document.getElementById('form-surat')?.scrollIntoView({ behavior: 'smooth' }));
}

const targets = reactive<Record<number, string>>({});
const dispositions = reactive<Record<number, string>>({});
const statusErrors = reactive<Record<number, string>>({});

const maxStep = computed(() => form.letter_type === 'incoming' ? 4 : 5);
const stepLabels = computed(() => form.letter_type === 'incoming' ? ['Identitas', 'Dokumen', 'Disposisi', 'Periksa'] : ['Jenis', 'Identitas', 'Naskah', 'Tanda tangan', 'Periksa']);
const queueCounts = computed(() => ({
    incoming: props.queue_counts?.incoming_new ?? props.records.filter(r => r.letter_type === 'incoming' && r.status === 'received').length,
    disposition: props.queue_counts?.awaiting_disposition ?? props.records.filter(r => r.letter_type === 'incoming' && r.status === 'disposition').length,
    draft: props.queue_counts?.draft ?? props.records.filter(r => r.letter_type === 'outgoing' && r.status === 'draft').length,
    approval: props.queue_counts?.awaiting_approval ?? props.records.filter(r => r.approval_status === 'pending').length,
    signature: props.queue_counts?.awaiting_signature ?? props.records.filter(r => ['approved', 'awaiting_signature', 'signature_pending', 'signature_failed'].includes(r.status)).length,
    ready: props.queue_counts?.ready_to_send ?? props.records.filter(r => r.status === 'issued').length,
    archived: props.queue_counts?.archived ?? props.records.filter(r => r.status === 'archived').length,
}));

function selectSignatory() {
    const signer = props.signatories?.find(item => item.id === form.signatory_id);
    if (signer) { form.signer_name = signer.name; form.signer_title = signer.title; }
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
    if (form.letter_type === 'incoming' && wizardStep.value === 2 && !form.summary && !form.attachment) wizardError.value = 'Isi ringkasan atau unggah dokumen surat.';
    if (form.letter_type === 'outgoing' && wizardStep.value === 2 && (!form.letter_number || !form.letter_date || !form.recipient || !form.subject)) wizardError.value = 'Lengkapi nomor, tanggal, tujuan, dan perihal surat.';
    if (form.letter_type === 'outgoing' && wizardStep.value === 3 && !form.letter_body.trim()) wizardError.value = 'Isi naskah surat sebelum melanjutkan.';
    if (form.letter_type === 'outgoing' && wizardStep.value === 4 && (!form.signer_name || !form.signer_title)) wizardError.value = 'Pilih atau lengkapi penandatangan.';
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

function saveRecord() {
    if (editingId.value !== null) {
        form.patch(`/administrasi/persuratan/surat/${editingId.value}/draf`, {
            preserveScroll: true,
            onSuccess: () => { form.reset(); editingId.value = null; creating.value = false; },
        });
        return;
    }
    form.post('/administrasi/persuratan/surat', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { form.reset(); creating.value = false; },
    });
}

function approveRecord(record: CorrespondenceRecord, decision: 'approve' | 'reject') {
    const message = decision === 'approve' ? 'Setujui surat ini untuk diterbitkan?' : 'Tolak surat ini agar operator memperbaikinya?';
    if (!window.confirm(message)) return;
    router.patch(`/administrasi/persuratan/surat/${record.id}/persetujuan`, { decision }, { preserveScroll: true });
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
    const path = action === 'manual-prepare'
        ? `/administrasi/persuratan/surat/${record.id}/tanda-tangan-manual`
        : action === 'bsre'
            ? `/administrasi/persuratan/surat/${record.id}/tte-bsre`
            : `/administrasi/persuratan/tte-bsre/${record.signature_request_id}/ulangi`;
    router.post(path, {}, { preserveScroll: true });
}
function uploadManualSignature(record: CorrespondenceRecord, event: Event) {
    const signed_document = (event.target as HTMLInputElement).files?.[0];
    if (signed_document) router.post(`/administrasi/persuratan/surat/${record.id}/hasil-tanda-tangan-manual`, { signed_document }, { forceFormData: true, preserveScroll: true });
}

function submitApproval(record: CorrespondenceRecord) {
    router.post(`/administrasi/persuratan/surat/${record.id}/ajukan-persetujuan`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Persuratan & Arsip" />
    <AppLayout title="Persuratan & Arsip">
        <main class="view space-y-6">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Administrasi sekolah</p>
                    <h1>Persuratan &amp; arsip</h1>
                    <p>Catat surat masuk dan keluar, teruskan disposisi, lalu simpan surat selesai ke arsip sekolah.</p>
                </div>
                <div class="grade-summary correspondence-summary">
                    <div><span>Surat masuk</span><strong>{{ records.filter((item) => item.letter_type === 'incoming').length }}</strong></div>
                    <div><span>Surat keluar</span><strong>{{ records.filter((item) => item.letter_type === 'outgoing').length }}</strong></div>
                    <div><span>Diarsipkan</span><strong>{{ records.filter((item) => item.status === 'archived').length }}</strong></div>
                </div>
            </section>

            <p v-if="flashMessage" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                {{ flashMessage }}
            </p>

            <section class="queue-section" aria-labelledby="queue-heading">
                <div class="table-title"><div><p class="eyebrow">Perlu ditindaklanjuti</p><h2 id="queue-heading">Antrean persuratan</h2><p>Pilih kartu untuk melihat surat yang membutuhkan tindakan.</p></div></div>
                <div class="queue-grid">
                    <button type="button" @click="activeSection = 'incoming'"><span>Surat masuk aktif</span><strong>{{ queueCounts.incoming }}</strong><small>{{ queueCounts.disposition }} perlu disposisi</small></button>
                    <button type="button" @click="activeSection = 'outgoing'"><span>Draf surat</span><strong>{{ queueCounts.draft }}</strong><small>Perlu dilengkapi</small></button>
                    <button type="button" @click="activeSection = 'approval'"><span>Persetujuan</span><strong>{{ queueCounts.approval }}</strong><small>Menunggu keputusan</small></button>
                    <button type="button" @click="activeSection = 'approval'"><span>Tanda tangan</span><strong>{{ queueCounts.signature }}</strong><small>Manual atau BSrE</small></button>
                    <button type="button" @click="activeSection = 'outgoing'"><span>Siap dikirim</span><strong>{{ queueCounts.ready }}</strong><small>Dokumen final</small></button>
                    <button type="button" @click="activeSection = 'archived'"><span>Arsip</span><strong>{{ queueCounts.archived }}</strong><small>Tersimpan</small></button>
                </div>
            </section>

            <section class="panel correspondence-panel workflow">
                <div class="table-title"><div><h2>Mulai dari sini</h2><p>Pilih pekerjaan yang ingin dilakukan.</p></div></div>
                <div v-if="can_manage" class="workflow-actions">
                    <button type="button" class="primary-button" @click="startNew('incoming')">Catat surat masuk</button>
                    <button type="button" class="secondary-button" @click="startNew('outgoing')">Buat surat keluar</button>
                </div>
                <p class="workflow-help">Surat masuk: catat → disposisi → selesai → arsip. Surat keluar: draf → persetujuan → tanda tangan → terbit → terkirim → arsip.</p>
                <p class="workflow-help">QR memeriksa metadata dan integritas berkas final. Pada jalur manual, QR bukan tanda tangan elektronik tersertifikasi.</p>
            </section>

            <section v-if="can_manage" v-show="creating" id="form-surat" class="panel correspondence-panel wizard-panel" tabindex="-1">
                <div class="wizard-heading"><div><p class="eyebrow">{{ form.letter_type === 'incoming' ? 'Surat masuk' : 'Surat keluar' }}</p><h2>{{ editingId ? 'Perbaiki draf' : form.letter_type === 'incoming' ? 'Catat surat masuk' : 'Buat surat keluar' }}</h2></div><button type="button" class="wizard-close" aria-label="Tutup formulir" @click="creating = false">×</button></div>
                <ol class="stepper" :aria-label="`Langkah ${wizardStep} dari ${maxStep}`">
                    <li v-for="(label, index) in stepLabels" :key="label" :class="{ active: wizardStep === index + 1, done: wizardStep > index + 1 }"><span>{{ index + 1 }}</span><small>{{ label }}</small></li>
                </ol>
                <form class="wizard-form" enctype="multipart/form-data" @submit.prevent="saveRecord">
                    <template v-if="form.letter_type === 'incoming'">
                        <div v-if="wizardStep === 1" class="wizard-grid"><label>Nomor surat<input v-model="form.letter_number" maxlength="100" /></label><label>Tanggal surat<input v-model="form.letter_date" type="date" /></label><label>Asal surat <b>*</b><input v-model="form.sender" maxlength="180" /></label><label>Tanggal diterima <b>*</b><input v-model="form.received_at" type="date" /></label><label class="wide">Perihal <b>*</b><input v-model="form.subject" maxlength="200" /></label></div>
                        <div v-else-if="wizardStep === 2" class="wizard-grid"><label class="wide">Ringkasan isi<textarea v-model="form.summary" rows="6" maxlength="5000" /></label><label class="wide">Dokumen surat (PDF, DOCX, JPG/PNG; maks. 10 MB)<input type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" @change="setAttachment" /></label></div>
                        <div v-else-if="wizardStep === 3" class="wizard-grid"><label class="wide">Arahan disposisi<textarea v-model="form.disposition" rows="5" placeholder="Tindakan yang perlu dilakukan" /></label><label class="wide">Tujuan disposisi<input v-model="form.disposition_target" placeholder="Nama atau unit kerja" /></label></div>
                    </template>
                    <template v-else>
                        <div v-if="wizardStep === 1" class="template-choices"><label v-for="item in [{v:'official_school',l:'Surat resmi',d:'Korespondensi umum sekolah'},{v:'invitation',l:'Undangan',d:'Rapat atau kegiatan'},{v:'assignment',l:'Surat tugas',d:'Penugasan resmi'},{v:'statement',l:'Surat pernyataan',d:'Pernyataan resmi sekolah'}]" :key="item.v" :class="{ selected: form.letter_template === item.v }"><input v-model="form.letter_template" type="radio" :value="item.v" /><span><strong>{{ item.l }}</strong><small>{{ item.d }}</small></span></label></div>
                        <div v-else-if="wizardStep === 2" class="wizard-grid"><label>Nomor surat <b>*</b><input v-model="form.letter_number" maxlength="100" placeholder="001/TU/X/2026" /></label><label>Tanggal surat <b>*</b><input v-model="form.letter_date" type="date" /></label><label>Tujuan surat <b>*</b><input v-model="form.recipient" maxlength="180" /></label><label>Sifat<select v-model="form.urgency"><option value="ordinary">Biasa</option><option value="urgent">Segera</option><option value="immediate">Sangat segera</option></select></label><label class="wide">Alamat tujuan<textarea v-model="form.recipient_address" rows="2" /></label><label class="wide">Perihal <b>*</b><input v-model="form.subject" maxlength="200" /></label><label>Klasifikasi<input v-model="form.classification" maxlength="40" /></label><label>Keterangan lampiran<input v-model="form.attachment_note" maxlength="180" placeholder="Contoh: 1 berkas" /></label></div>
                        <div v-else-if="wizardStep === 3" class="wizard-grid"><label class="wide">Salam pembuka<input v-model="form.opening_greeting" maxlength="250" /></label><label class="wide">Isi surat <b>*</b><textarea v-model="form.letter_body" rows="10" maxlength="20000" placeholder="Tulis isi dalam paragraf yang singkat dan jelas." /></label><label class="wide">Penutup<textarea v-model="form.closing_text" rows="3" /></label><label class="wide">Tembusan<textarea v-model="form.carbon_copy" rows="2" placeholder="Satu penerima per baris" /></label></div>
                        <div v-else-if="wizardStep === 4" class="wizard-grid"><label v-if="signatories?.length" class="wide">Pejabat penandatangan<select v-model="form.signatory_id" @change="selectSignatory"><option :value="null">Pilih pejabat</option><option v-for="signer in signatories" :key="signer.id" :value="signer.id">{{ signer.name }} — {{ signer.title }}{{ signer.employee_number ? ` · NIP ${signer.employee_number}` : '' }}</option></select></label><button class="secondary-button signatory-toggle" type="button" @click="creatingSignatory = !creatingSignatory">{{ creatingSignatory ? 'Tutup profil pejabat' : 'Tambah profil pejabat' }}</button><div v-if="creatingSignatory" class="signatory-form wide"><label>Nama lengkap<input v-model="signatoryForm.name" /></label><label>Jabatan<input v-model="signatoryForm.title" /></label><label>NIP<input v-model="signatoryForm.employee_number" /></label><label>Pangkat/golongan<input v-model="signatoryForm.rank" /></label><label class="wide">Kewenangan template<select v-model="signatoryForm.template_scopes" multiple><option value="official_school">Surat resmi</option><option value="invitation">Undangan</option><option value="assignment">Surat tugas</option><option value="statement">Surat keterangan</option></select></label><button class="primary-button" type="button" :disabled="signatoryForm.processing" @click="saveSignatory">Simpan profil pejabat</button><p v-if="Object.keys(signatoryForm.errors).length" class="field-error">Periksa nama, jabatan, dan kewenangan penandatangan.</p></div><label>Nama penandatangan <b>*</b><input v-model="form.signer_name" maxlength="180" /></label><label>Jabatan <b>*</b><input v-model="form.signer_title" maxlength="180" /></label><fieldset class="signature-options wide"><legend>Metode tanda tangan</legend><label :class="{ selected: form.signature_method === 'manual' }"><input v-model="form.signature_method" type="radio" value="manual" /><span><strong>Tanda tangan basah</strong><small>Cetak dan unggah kembali hasil pindai PDF.</small></span></label><label :class="{ selected: form.signature_method === 'bsre' }"><input v-model="form.signature_method" type="radio" value="bsre" /><span><strong>TTE BSrE</strong><small>Memerlukan integrasi dan sertifikat resmi yang aktif.</small></span></label></fieldset><label class="wide checkbox-label"><input v-model="form.approval_required" type="checkbox" /> Minta persetujuan kepala sekolah</label></div>
                    </template>
                    <div v-if="wizardStep === maxStep" class="review-card"><div><span>Nomor</span><strong>{{ form.letter_number || 'Nomor agenda otomatis' }}</strong></div><div><span>Perihal</span><strong>{{ form.subject }}</strong></div><div><span>{{ form.letter_type === 'incoming' ? 'Asal' : 'Tujuan' }}</span><strong>{{ form.sender || form.recipient }}</strong></div><div v-if="form.letter_type === 'outgoing'"><span>Penandatangan</span><strong>{{ form.signer_name }} · {{ form.signature_method === 'bsre' ? 'TTE BSrE' : 'Tanda tangan basah' }}</strong></div><a v-if="editingId && form.letter_type === 'outgoing'" :href="`/administrasi/persuratan/surat/${editingId}/pdf`" target="_blank" rel="noopener">Buka preview PDF</a><p v-else-if="form.letter_type === 'outgoing'">Simpan draf terlebih dahulu untuk membuka preview PDF.</p></div>
                    <p v-if="wizardError" class="wizard-error" role="alert">{{ wizardError }}</p>
                    <div v-if="Object.keys(form.errors).length" class="wizard-error" role="alert"><strong>Periksa kembali isian:</strong><ul><li v-for="(message, field) in form.errors" :key="field">{{ message }}</li></ul></div>
                    <div class="wizard-actions"><button v-if="wizardStep > 1" class="secondary-button" type="button" @click="previousStep">← Sebelumnya</button><span></span><button class="secondary-button" type="button" @click="creating = false">Batal</button><button v-if="wizardStep < maxStep" class="primary-button" type="button" @click="nextStep">Lanjutkan →</button><button v-else class="primary-button" type="submit" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : editingId ? 'Simpan perubahan' : form.letter_type === 'incoming' ? 'Catat surat' : 'Simpan draf' }}</button></div>
                </form>
            </section>

            <section class="panel correspondence-panel">
                <div class="table-title">
                    <div><h2>Daftar surat</h2><p>Surat masuk mengikuti alur diterima → disposisi → selesai → arsip.</p></div>
                </div>
                <nav class="correspondence-tabs" aria-label="Filter surat">
                    <button v-for="tab in [{ value: 'incoming', label: 'Surat Masuk' }, { value: 'outgoing', label: 'Surat Keluar' }, { value: 'approval', label: 'Persetujuan & TTE' }, { value: 'archived', label: 'Arsip' }]" :key="tab.value" type="button" class="correspondence-tab" :class="{ 'is-active': activeSection === tab.value }" @click="activeSection = tab.value as typeof activeSection">{{ tab.label }}</button>
                </nav>

                <label class="search-label">Cari surat
                    <input v-model="search" type="search" placeholder="Perihal, nomor, asal, atau tujuan" />
                </label>
                <div v-if="visibleRecords.length" class="table-scroll">
                    <table class="correspondence-table">
                        <thead><tr><th>Nomor & tanggal</th><th>Pihak terkait</th><th>Perihal</th><th>Status</th><th>Tindakan berikutnya</th></tr></thead>
                        <tbody>
                        <tr v-for="record in visibleRecords" :key="record.id">
                            <td data-label="Nomor & tanggal"><strong>{{ record.letter_number || record.agenda_number || '—' }}</strong><small>{{ record.letter_date || record.received_at || 'Tanggal belum diisi' }}</small></td>
                            <td data-label="Pihak terkait">{{ record.letter_type === 'incoming' ? record.sender : record.recipient }}<small>{{ record.letter_type === 'incoming' ? 'Pengirim' : 'Penerima' }}</small></td>
                            <td data-label="Perihal"><strong>{{ record.subject }}</strong><small v-if="record.summary">{{ record.summary }}</small><small v-if="record.disposition"><b>Disposisi:</b> {{ record.disposition }}</small></td>
                            <td data-label="Status"><span class="status-chip">{{ labelForStatus(record) }}</span><small v-if="record.signature_error">Kode: {{ record.signature_error }}</small></td>
                            <td data-label="Tindakan berikutnya" class="action-cell"><div class="record-actions">
                                <button v-if="can_manage && record.letter_type === 'outgoing' && record.status === 'draft' && record.has_document" class="row-action-button" type="button" @click="editDraft(record)">Ubah draf</button>
                                <a v-if="record.has_document" :href="`/administrasi/persuratan/surat/${record.id}/pdf`" target="_blank" rel="noopener" class="row-action-button">{{ record.status === 'draft' ? 'Periksa PDF draf' : 'Buka PDF surat' }}</a>
                                <a v-if="record.has_attachment" :href="`/administrasi/persuratan/surat/${record.id}/lampiran`" class="row-action-button">Unduh lampiran</a>
                                <button v-if="can_manage && record.status === 'draft' && record.approval_required && record.approval_status === 'pending'" class="row-action-button" type="button" @click="submitApproval(record)">Ajukan persetujuan</button>
                                <button v-if="can_manage && record.signature_method === 'manual' && ['draft', 'approved'].includes(record.status) && (!record.approval_required || record.approval_status === 'approved')" class="row-action-button" type="button" @click="signatureAction(record, 'manual-prepare')">Siapkan PDF ber-QR</button>
                                <label v-if="can_manage && record.signature_method === 'manual' && record.status === 'awaiting_signature'" class="row-action-button upload-action">Unggah hasil tanda tangan<input class="sr-only" type="file" accept="application/pdf,.pdf" @change="uploadManualSignature(record, $event)" /></label>
                                <button v-if="can_manage && record.signature_method === 'bsre' && ['draft', 'approved'].includes(record.status) && (!record.approval_required || record.approval_status === 'approved')" class="row-action-button" type="button" @click="signatureAction(record, 'bsre')">Kirim ke BSrE</button>
                                <button v-if="can_manage && record.signature_method === 'bsre' && record.status === 'signature_failed' && record.signature_request_id" class="row-action-button" type="button" @click="signatureAction(record, 'bsre-retry')">Coba lagi</button>
                                <template v-if="!can_manage && record.approval_required && record.status === 'pending_approval' && record.approval_status === 'pending'">
                                <button class="primary-button" type="button" @click="approveRecord(record, 'approve')">Setujui</button>
                                <button class="secondary-button" type="button" @click="approveRecord(record, 'reject')">Tolak</button>
                                </template>
                            <form v-if="can_manage && nextStatuses(record).length" class="status-update-form" @submit.prevent="updateStatus(record)">
                                <label class="sr-only" :for="`next-status-${record.id}`">Status berikutnya</label>
                                <select :id="`next-status-${record.id}`" v-model="targets[record.id]">
                                    <option v-for="status in nextStatuses(record)" :key="status.value" :value="status.value">{{ status.label }}</option>
                                </select>
                                <input v-if="record.letter_type === 'incoming' && (targets[record.id] || nextStatuses(record)[0]?.value) === 'disposition'" v-model="dispositions[record.id]" maxlength="3000" placeholder="Isi disposisi" required />
                                <button class="secondary-button" type="submit">Perbarui status</button>
                                <span v-if="statusErrors[record.id]" class="field-error" role="alert">{{ statusErrors[record.id] }}</span>
                            </form>
                            <span v-else-if="record.status === 'archived'" class="archived-note">Tersimpan di arsip</span>
                            </div></td>
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
.queue-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .75rem; }
.queue-grid button { display: grid; gap: .2rem; min-width: 0; border: 1px solid #dbe3ef; border-radius: .8rem; background: white; padding: 1rem; color: #334155; text-align: left; }
.queue-grid button:hover { border-color: #7996cf; box-shadow: 0 4px 14px rgb(49 87 164 / 10%); }
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
.wizard-form { display: grid; gap: 1rem; }
.wizard-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
.wizard-grid label { display: grid; gap: .4rem; color: #42526b; font-size: .84rem; font-weight: 700; }
.wizard-grid .wide { grid-column: 1 / -1; }
.wizard-grid input, .wizard-grid select, .wizard-grid textarea { width: 100%; min-height: 2.7rem; border: 1px solid #cbd5e1; border-radius: .65rem; background: white; padding: .6rem .75rem; color: #172b4d; font: inherit; }
.wizard-grid b { color: #b42318; }
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
.hero-card { flex-wrap: wrap; }
.hero-card > div:first-child { min-width: 0; }
.correspondence-summary { flex: 1 1 20rem; margin: 0; min-width: 0; }
.correspondence-form { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
.correspondence-form label { display: grid; gap: .4rem; color: #42526b; font-size: .85rem; font-weight: 600; }
.correspondence-form input, .correspondence-form select, .correspondence-form textarea, .status-update-form select, .status-update-form input { width: 100%; min-height: 2.7rem; border: 1px solid #cbd5e1; border-radius: .65rem; background: white; padding: .6rem .75rem; color: #172b4d; }
.correspondence-form input[type=file] { padding: .4rem; }
.correspondence-wide { grid-column: 1 / -1; }
.correspondence-actions { display: flex; justify-content: end; gap: .5rem; flex-wrap: wrap; }
.workflow-actions, .record-actions, .pagination { display: flex; align-items: center; flex-wrap: wrap; gap: .6rem; }
.workflow-help, .form-note { margin: .8rem 0 0; color: #52637c; font-size: .85rem; line-height: 1.55; }
.form-note { margin: 0; padding: .75rem; background: #eff5ff; border-radius: .6rem; }
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
.correspondence-table td:nth-child(2) { width: 15%; }
.correspondence-table td:nth-child(3) { width: 25%; }
.correspondence-table td:nth-child(4) { width: 13%; }
.action-cell { width: 30%; }
.action-cell .record-actions { align-items: flex-start; }
.correspondence-list { display: grid; gap: .85rem; padding-top: 1rem; }
.correspondence-card { border: 1px solid #dbe3ef; border-radius: .85rem; padding: 1rem; }
.correspondence-card-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.correspondence-card h3 { margin: .3rem 0 .2rem; color: #172b4d; font-size: 1.05rem; font-weight: 700; }
.correspondence-card-heading p, .muted-note, .archived-note { margin: 0; color: #64748b; font-size: .82rem; }
.correspondence-kind, .status-chip { display: inline-flex; border-radius: 999px; padding: .2rem .55rem; font-size: .75rem; font-weight: 700; }
.kind-incoming { background: #eaf0ff; color: #3157a4; }
.kind-outgoing { background: #f1f5f9; color: #475569; }
.status-chip { background: #ecfdf3; color: #067647; white-space: nowrap; }
.correspondence-meta { display: flex; flex-wrap: wrap; gap: .65rem 1.5rem; margin: .9rem 0; }
.correspondence-meta div { display: grid; gap: .1rem; }
.correspondence-meta dt { color: #64748b; font-size: .72rem; }
.correspondence-meta dd { margin: 0; color: #334155; font-size: .82rem; font-weight: 600; }
.correspondence-summary-text, .correspondence-disposition { margin: .5rem 0; color: #475569; font-size: .86rem; white-space: pre-wrap; }
.correspondence-footer { display: flex; align-items: center; justify-content: space-between; gap: .75rem; border-top: 1px solid #e2e8f0; padding-top: .8rem; }
.status-update-form { display: flex; flex-wrap: wrap; align-items: center; justify-content: end; gap: .5rem; }
.status-update-form select { width: auto; min-width: 9rem; }
.status-update-form input { width: min(22rem, 100%); }
.row-action-button { display: inline-flex; align-items: center; border: 1px solid #c9d7f3; border-radius: .55rem; background: #eff5ff; padding: .5rem .7rem; color: #3157a4; font-size: .8rem; font-weight: 700; text-decoration: none; }
.empty-state { padding: 1.25rem 0; color: #64748b; }
@media (min-width: 761px) { .hero-card > div:first-child { flex: 1 1 18rem; } }
@media (max-width: 1100px) { .queue-grid { grid-template-columns: repeat(3, 1fr); } .template-choices { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 800px) { .correspondence-summary { width: 100%; min-width: 0; } .queue-grid { grid-template-columns: repeat(2, 1fr); } .stepper small { display: none; } .wizard-grid, .signature-options, .review-card, .signatory-form { grid-template-columns: 1fr; } .wizard-grid .wide, .signatory-form .wide { grid-column: auto; } .correspondence-form { grid-template-columns: 1fr; } .correspondence-wide { grid-column: auto; } .correspondence-footer { align-items: stretch; flex-direction: column; } .status-update-form { justify-content: stretch; } .status-update-form select, .status-update-form input, .status-update-form button { width: 100%; } .correspondence-card-heading { align-items: start; } .correspondence-table { min-width: 0; } .correspondence-table thead { display: none; } .correspondence-table tr { display: grid; border-bottom: 1px solid #dbe3ef; padding: .75rem; } .correspondence-table tr:last-child { border-bottom: 0; } .correspondence-table td { display: grid; grid-template-columns: 7.5rem minmax(0,1fr); width: 100% !important; border: 0; padding: .35rem 0; } .correspondence-table td::before { color: #64748b; content: attr(data-label); font-size: .72rem; font-weight: 700; } .action-cell .record-actions { grid-column: 1 / -1; padding-top: .35rem; } }
@media (max-width: 480px) { .correspondence-summary { grid-template-columns: repeat(3, minmax(0,1fr)); } .correspondence-summary > div { min-height: 78px; padding: .75rem; border-right: 1px solid var(--line); border-bottom: 0; } .correspondence-summary > div:last-child { border-right: 0; } .correspondence-summary span { font-size: .65rem; } }
</style>
