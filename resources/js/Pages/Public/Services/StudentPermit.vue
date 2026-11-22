<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import { useForm, usePage } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
const publicText = usePublicContentText();

interface StudentOption {
    student_id: number;
    nis: string;
    nisn?: string | null;
    name: string;
    class_group_name?: string | null;
}

interface PermitRow {
    id: number;
    student_name: string;
    class_name: string;
    ticket_code?: string | null;
    type_label: string;
    title: string;
    reason: string;
    start_date: string;
    end_date: string;
    status: string;
    status_label: string;
    decision_note?: string | null;
    attachment_document_id?: number | null;
}

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    contentBlocks?: Record<string, Record<string, any>>;
    students: StudentOption[];
    myPermits: PermitRow[];
    types: { value: string; label: string }[];
    operations: { permit_same_day_cutoff: string; permit_max_advance_days: number };
    timezone: string;
    today: string;
    localHour: string;
    issuedReceipt?: { id: number; ticket_code?: string | null; verification_secret: string } | null;
}>();

const page = usePage();
const flashMessage = computed(() => (page.props.flash as { message?: string } | undefined)?.message);
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const revisingPermit = ref<PermitRow | null>(null);
const activeTab = ref<'form' | 'status' | 'rules'>(props.issuedReceipt ? 'status' : 'form');
watch(() => props.issuedReceipt, (receipt) => {
    if (receipt) activeTab.value = 'status';
});
const studentQuery = ref('');
const studentLookupMessage = ref('');
type PublicPermit = Pick<PermitRow, 'ticket_code' | 'type_label' | 'start_date' | 'end_date' | 'status' | 'status_label'>;
const selectedStudent = ref<StudentOption | null>(null);
const activePermits = ref<PublicPermit[]>([]);
const lookupBusy = ref(false);
const statusQuery = ref('');
const statusBusy = ref(false);
const statusMessage = ref('');
const statusResults = ref<PublicPermit[]>([]);
const tokenVerified = ref(false);
const tokenMessage = ref('');
const tokenBusy = ref(false);
const homeroomContact = ref<{ name: string; whatsapp_url: string | null } | null>(null);
const permitKind = ref('sick');
const otherKind = ref('');
const permitKinds = [
    { value: 'sick', type: 'sick', label: publicText("copy.vue_services_studentpermit", "text_12001", "Sakit (Disarankan melampirkan Surat Dokter / Keterangan Faskes)") },
    { value: 'personal', type: 'personal', label: publicText("copy.vue_services_studentpermit", "text_12002", "Izin Kepentingan Keluarga / Urusan Penting") },
    { value: 'bereavement', type: 'personal', label: publicText("copy.vue_services_studentpermit", "text_12003", "Kedukaan / Musibah Keluarga") },
    { value: 'medical', type: 'sick', label: publicText("copy.vue_services_studentpermit", "text_12004", "Pemeriksaan Medis / Rawat Jalan / Kontrol Dokter") },
    { value: 'event', type: 'event', label: publicText("copy.vue_services_studentpermit", "text_12005", "Dispensasi (Tugas Sekolah / Lomba / Kegiatan Organisasi)") },
    { value: 'religious', type: 'event', label: publicText("copy.vue_services_studentpermit", "text_12006", "Kegiatn Keagamaan") },
    { value: 'special', type: 'other', label: publicText("copy.vue_services_studentpermit", "text_12007", "Keperluan Khusus (Tes Masuk PTN / Administrasi Resmi)") },
    { value: 'other', type: 'other', label: publicText("copy.vue_services_studentpermit", "text_12008", "Lainnya (Isi Keterangan)") },
];
async function permitRequest(path: string, payload: Record<string, string>) {
    const response = await fetch(`/layanan/izin-siswa/${path}`, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' },
        body: JSON.stringify(payload),
    });
    const data = await response.json();
    if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] as string || data.message || 'Permintaan gagal. Silakan coba kembali.');
    return data;
}
const selectStudent = async () => {
    if (lookupBusy.value) return;
    lookupBusy.value = true;
    studentLookupMessage.value = '';
    const query = studentQuery.value.trim();
    try {
        const data = await permitRequest('cari-siswa', { query });
        if (query !== studentQuery.value.trim()) return;
        selectedStudent.value = data.student;
        homeroomContact.value = data.homeroom_contact;
        form.student_id = data.student.student_id;
        activePermits.value = data.permits.filter((permit: PublicPermit) => ['pending', 'needs_revision', 'approved'].includes(permit.status));
    } catch (error) { studentLookupMessage.value = error instanceof Error ? error.message : 'Gagal memuat data siswa.'; }
    finally { lookupBusy.value = false; }
};
const lookupStatus = async () => {
    if (statusBusy.value) return;
    statusBusy.value = true;
    statusMessage.value = '';
    statusResults.value = [];
    try {
        const data = await permitRequest('cek-status', { query: statusQuery.value.trim() });
        statusResults.value = data.permits;
        if (!data.permits.length) statusMessage.value = 'Data permohonan izin tidak ditemukan.';
    } catch (error) { statusMessage.value = error instanceof Error ? error.message : 'Gagal memuat status pengajuan.'; }
    finally { statusBusy.value = false; }
};
const verifyToken = async () => {
    tokenBusy.value = true;
    tokenMessage.value = '';
    try {
        await permitRequest('cek-token', { token: form.one_time_token });
        tokenVerified.value = true;
        tokenMessage.value = 'Token valid. Formulir berhasil dibuka.';
    } catch (error) { tokenMessage.value = error instanceof Error ? error.message : 'Token tidak valid.'; }
    finally { tokenBusy.value = false; }
};

const contactHomeroom = () => {
    if (homeroomContact.value?.whatsapp_url) window.open(homeroomContact.value.whatsapp_url, '_blank', 'noopener,noreferrer');
    else tokenMessage.value = selectedStudent.value ? 'Nomor WhatsApp wali kelas belum tersedia. Silakan hubungi Guru Piket.' : 'Klik tombol Cek Siswa terlebih dahulu.';
};

const form = useForm({
    student_query: '',
    student_id: (props.students[0]?.student_id ?? '') as string | number,
    one_time_token: '',
    type: 'sick',
    start_date: props.today,
    end_date: props.today,
    title: '',
    reason: '',
    contact_name: '',
    contact_phone: '',
    contact_relationship: 'father',
    signature_data: '',
    attachment: null as File | null,
    idempotency_key: `izin-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
});
const durationDays = computed(() => Math.max(0, Math.round((Date.parse(form.end_date) - Date.parse(form.start_date)) / 86400000) + 1));
const requiresToken = computed(() => form.start_date <= props.today && props.localHour >= '08:00' && props.localHour < '22:00');
const requiresEvidence = computed(() => ['sick', 'medical'].includes(permitKind.value) && durationDays.value >= 3);
const datesConflict = computed(() => activePermits.value.some((permit) => permit.start_date <= form.end_date && permit.end_date >= form.start_date));
watch(studentQuery, () => { selectedStudent.value = null; form.student_id = ''; activePermits.value = []; tokenVerified.value = false; studentLookupMessage.value = ''; });
watch(() => form.one_time_token, () => { tokenVerified.value = false; tokenMessage.value = ''; });
const attachmentPreview = ref('');
const attachmentInput = ref<HTMLInputElement | null>(null);
const clearAttachment = () => {
    if (attachmentPreview.value) URL.revokeObjectURL(attachmentPreview.value);
    attachmentPreview.value = '';
    form.attachment = null;
    if (attachmentInput.value) attachmentInput.value.value = '';
};
const pickAttachment = async (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    clearAttachment();
    if (!file) return;
    form.clearErrors('attachment');
    if (!['image/jpeg', 'image/png', 'application/pdf'].includes(file.type)) { form.setError('attachment', 'Format yang didukung: JPG, PNG, JPEG, PDF.'); return; }
    let upload = file;
    if (file.type.startsWith('image/')) {
        try {
            const bitmap = await createImageBitmap(file);
            const scale = Math.min(1, 1600 / Math.max(bitmap.width, bitmap.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(bitmap.width * scale); canvas.height = Math.round(bitmap.height * scale);
            canvas.getContext('2d')?.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            bitmap.close();
            const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/jpeg', .85));
            if (blob && blob.size < file.size) upload = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' });
        } catch { /* Preserve the original file when browser image decoding is unavailable. */ }
    }
    if (upload.size > 5 * 1024 * 1024) { form.setError('attachment', 'Ukuran file maksimal 5 MB.'); return; }
    form.attachment = upload;
    if (upload.type.startsWith('image/')) attachmentPreview.value = URL.createObjectURL(upload);
};
onBeforeUnmount(() => { if (attachmentPreview.value) URL.revokeObjectURL(attachmentPreview.value); });

const signatureCanvas = ref<HTMLCanvasElement | null>(null);
let drawingSignature = false;
const signaturePoint = (event: PointerEvent) => {
    const rect = signatureCanvas.value!.getBoundingClientRect();
    return [(event.clientX - rect.left) * 600 / rect.width, (event.clientY - rect.top) * 180 / rect.height] as const;
};
const startSignature = (event: PointerEvent) => {
    const canvas = signatureCanvas.value;
    const context = canvas?.getContext('2d');
    if (!canvas || !context) return;
    canvas.setPointerCapture(event.pointerId);
    context.lineWidth = 2;
    context.lineCap = 'round';
    context.strokeStyle = '#172e48';
    context.beginPath();
    context.moveTo(...signaturePoint(event));
    drawingSignature = true;
};
const moveSignature = (event: PointerEvent) => {
    if (!drawingSignature) return;
    const context = signatureCanvas.value?.getContext('2d');
    context?.lineTo(...signaturePoint(event));
    context?.stroke();
};
const finishSignature = () => {
    if (!drawingSignature) return;
    drawingSignature = false;
    form.signature_data = signatureCanvas.value?.toDataURL('image/png') ?? '';
};
const clearSignature = () => {
    drawingSignature = false;
    signatureCanvas.value?.getContext('2d')?.clearRect(0, 0, 600, 180);
    form.signature_data = '';
};

const revisionForm = useForm({
    start_date: '',
    end_date: '',
    title: '',
    reason: '',
    attachment: null as File | null,
});

const openRevision = (row: PermitRow) => {
    revisingPermit.value = row;
    revisionForm.start_date = row.start_date;
    revisionForm.end_date = row.end_date;
    revisionForm.title = row.title;
    revisionForm.reason = row.reason;
};

const submitPermit = () => {
    form.student_query = studentQuery.value.trim();
    const kind = permitKinds.find((kind) => kind.value === permitKind.value)!;
    form.type = kind.type;
    form.title = permitKind.value === 'other' ? otherKind.value.trim() : kind.label;
    if (!selectedStudent.value) { form.setError('student_query', 'Silakan klik Cek Siswa terlebih dahulu.'); return; }
    if (!form.signature_data) {
        form.setError('signature_data', 'Silakan bubuhkan tanda tangan.');
        return;
    }
    form.post('/layanan/izin-siswa', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.one_time_token = '';
            form.idempotency_key = `izin-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
            tokenVerified.value = false;
            activeTab.value = 'status';
            statusQuery.value = studentQuery.value;
            void lookupStatus();
        },
    });
};

const submitRevision = () => {
    if (!revisingPermit.value) return;
    revisionForm.post(`/layanan/izin-siswa/${revisingPermit.value.id}/perbaiki`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            revisingPermit.value = null;
        },
    });
};
</script>

<template>
    <Head :title="`SURAT IZIN SISWA | ${school.name.toUpperCase()}`" />
    <main class="public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />

        <header v-if="contentBlocks?.['student_permit.hero']?.visible !== false" class="portal-hero">
            <div class="permit-hero-inner">
                <div>
                    <p class="permit-badge">{{ contentBlocks?.['student_permit.hero']?.badge ?? 'Layanan Resmi Perizinan Siswa' }}</p>
                    <h1>{{ contentBlocks?.['student_permit.hero']?.title ?? 'Surat Izin Siswa Online' }}</h1>
                    <div class="permit-school-row"><strong class="permit-school">{{ school.name }}</strong><span class="permit-year">{{ contentBlocks?.['student_permit.hero']?.academic_year_label ?? 'TA 2026/2027' }}</span></div>
                    <p>{{ contentBlocks?.['student_permit.hero']?.description ?? 'Layanan permohonan izin tidak masuk sekolah (Sakit / Kepentingan Keluarga) resmi yang diajukan oleh Orang Tua / Wali Siswa secara online, cepat, dan disertai tanda tangan digital sah.' }}</p>
                    <div class="permit-hero-tags"><span>{{ publicText("copy.vue_services_studentpermit", "text_001", "1 Menit Selesai") }}</span><span>{{ publicText("copy.vue_services_studentpermit", "text_002", "TTD Digital Sah") }}</span><span>{{ publicText("copy.vue_services_studentpermit", "text_003", "Terkirim ke Wali Kelas") }}</span><span>{{ publicText("copy.vue_services_studentpermit", "text_004", "Ber-QR Code") }}</span></div>
                </div>
                <aside class="permit-steps">
                    <div class="permit-steps-heading"><strong>{{ publicText("copy.vue_services_studentpermit", "text_005", "Alur 3 Langkah Singkat:") }}</strong><span>{{ publicText("copy.vue_services_studentpermit", "text_006", "24 Jam Aktif") }}</span></div>
                    <ol>
                        <li><b>1</b><span><strong>{{ publicText("copy.vue_services_studentpermit", "text_007", "Cek Siswa:") }}</strong> {{ publicText("copy.vue_services_studentpermit", "text_008", "Ketik 10 digit NISN atau Nama Siswa") }}</span></li>
                        <li><b>2</b><span><strong>{{ publicText("copy.vue_services_studentpermit", "text_009", "Pilih Izin:") }}</strong> {{ publicText("copy.vue_services_studentpermit", "text_010", "Sakit, Izin Keluarga, atau Dispensasi") }}</span></li>
                        <li><b>3</b><span><strong>{{ publicText("copy.vue_services_studentpermit", "text_011", "Goreskan TTD:") }}</strong> {{ publicText("copy.vue_services_studentpermit", "text_012", "Tanda tangan di layar HP & Kirim") }}</span></li>
                    </ol>
                    <p class="permit-steps-footer">{{ publicText("copy.vue_services_studentpermit", "text_013", "Otomatis terhubung ke Wali Kelas & Guru Piket.") }}</p>
                </aside>
            </div>
        </header>

        <section class="portal-container">
            <nav class="permit-tabs" :aria-label="publicText('copy.vue_services_studentpermit', 'aria_label_113', 'Bagian surat izin siswa')">
                <button type="button" :aria-pressed="activeTab === 'form'" @click="activeTab = 'form'"><span class="tab-desktop">{{ publicText("copy.vue_services_studentpermit", "text_014", "Formulir Pengajuan") }}</span><span class="tab-mobile">{{ publicText("copy.vue_services_studentpermit", "text_015", "Formulir") }}</span></button>
                <button type="button" :aria-pressed="activeTab === 'status'" @click="activeTab = 'status'"><span class="tab-desktop">{{ publicText("copy.vue_services_studentpermit", "text_016", "Cek Status Pengajuan") }}</span><span class="tab-mobile">{{ publicText("copy.vue_services_studentpermit", "text_017", "Cek Status") }}</span></button>
                <button type="button" :aria-pressed="activeTab === 'rules'" @click="activeTab = 'rules'"><span class="tab-desktop">{{ publicText("copy.vue_services_studentpermit", "text_018", "Alur & Ketentuan") }}</span><span class="tab-mobile">{{ publicText("copy.vue_services_studentpermit", "text_019", "Ketentuan") }}</span></button>
            </nav>
            <div v-if="flashMessage" class="alert-banner alert-banner--success" role="status">{{ flashMessage }}</div>

            <div v-if="issuedReceipt" class="alert-banner alert-banner--pin" role="status">
                <strong>{{ publicText("copy.vue_services_studentpermit", "text_9001", "Pengajuan Izin Tercatat:") }} {{ issuedReceipt.ticket_code }}</strong>
                <span>{{ publicText("copy.vue_services_studentpermit", "text_020", "PIN Verifikasi Lacak:") }} <strong>{{ issuedReceipt.verification_secret }}</strong></span>
                <a :href="`/layanan/izin-siswa/${issuedReceipt.id}/pdf`" target="_blank" rel="noopener" class="ui-button ui-button--secondary ui-button--sm">{{ publicText("copy.vue_services_studentpermit", "text_021", "Cetak Bukti PDF") }}</a>
            </div>

            <section v-show="activeTab === 'form'" class="portal-card permit-application" :aria-label="publicText('copy.vue_services_studentpermit', 'aria_label_114', 'Formulir Pengajuan Izin')">
                <aside class="permit-notice">
                    <strong>{{ publicText("copy.vue_services_studentpermit", "text_022", "Ketentuan Pengajuan Izin Siswa Online:") }}</strong>
                    <ul>
                        <li>{{ publicText("copy.vue_services_studentpermit", "text_023", "Pengajuan izin cukup dikirim") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_024", "1 (satu) kali") }}</b> {{ publicText("copy.vue_services_studentpermit", "text_025", "untuk setiap periode izin yang diajukan.") }}</li>
                        <li>{{ publicText("copy.vue_services_studentpermit", "text_026", "Setelah berhasil mengirim, pantau status persetujuan di menu") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_016", "Cek Status Pengajuan") }}</b>{{ publicText("copy.vue_services_studentpermit", "text_027", ". Anda tidak perlu mengirim ulang.") }}</li>
                        <li>{{ publicText("copy.vue_services_studentpermit", "text_028", "Jika ada koreksi tanggal atau alasan mendesak, silakan konfirmasi langsung ke Wali Kelas.") }}</li>
                    </ul>
                </aside>
                <form class="form-grid" @submit.prevent="submitPermit">
                    <h2 class="permit-section-title full-span">{{ publicText("copy.vue_services_studentpermit", "text_029", "1. Identifikasi & Data Siswa") }}</h2>
                    <div class="permit-lookup full-span">
                        <label for="izin-student-query">{{ publicText("copy.vue_services_studentpermit", "text_030", "Cari Siswa") }} <span class="text-red-600">*</span></label>
                        <div>
                            <div class="permit-lookup-controls">
                                <input id="izin-student-query" v-model="studentQuery" type="search" required :placeholder="publicText('copy.vue_services_studentpermit', 'placeholder_115', 'Masukkan 10 digit NISN atau Nama Lengkap Siswa...')" @keydown.enter.prevent="selectStudent" />
                                <button type="button" :disabled="lookupBusy" @click="selectStudent">{{ lookupBusy ? 'Mencari...' : 'Cek Siswa' }}</button>
                            </div>
                            <small>{{ publicText("copy.vue_services_studentpermit", "text_031", "Ketikkan") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_032", "10 digit NISN") }}</b> {{ publicText("copy.vue_services_studentpermit", "text_033", "atau") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_034", "Nama Lengkap Siswa") }}</b> {{ publicText("copy.vue_services_studentpermit", "text_035", "(huruf besar/kecil bebas) lalu klik") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_036", "Cek Siswa") }}</b>.</small>
                            <p v-if="studentLookupMessage" role="status" class="muted">{{ studentLookupMessage }}</p>
                            <p v-if="form.errors.student_id || form.errors.student_query" role="alert" class="text-red-600">{{ form.errors.student_id || form.errors.student_query }}</p>
                        </div>
                    </div>

                    <div v-if="selectedStudent" class="permit-student-info full-span" role="status">
                        <strong>{{ selectedStudent.name }}</strong>
                        <div>{{ publicText("copy.vue_services_studentpermit", "text_9002", "NISN:") }} {{ selectedStudent.nisn || '-' }} <span>{{ publicText("copy.vue_services_studentpermit", "text_9003", "Kelas:") }} {{ selectedStudent.class_group_name || '-' }}</span></div>
                        <aside v-if="activePermits.length" class="permit-active-warning">
                            <strong>{{ publicText("copy.vue_services_studentpermit", "text_037", "Perhatian: Siswa Memiliki Permohonan Izin Terdaftar") }}</strong>
                            <p>{{ publicText("copy.vue_services_studentpermit", "text_038", "Ananda sudah memiliki permohonan izin dalam proses / disetujui. Mohon tidak mengirim formulir ulang untuk tanggal yang sama:") }}</p>
                            <div class="permit-table-scroll"><table><thead><tr><th>{{ publicText("copy.vue_services_studentpermit", "text_039", "No. Registrasi") }}</th><th>{{ publicText("copy.vue_services_studentpermit", "text_040", "Kategori") }}</th><th>{{ publicText("copy.vue_services_studentpermit", "text_041", "Periode") }}</th><th>{{ publicText("copy.vue_services_studentpermit", "text_042", "Status") }}</th></tr></thead><tbody><tr v-for="permit in activePermits" :key="permit.ticket_code || permit.start_date"><td>{{ permit.ticket_code }}</td><td>{{ permit.type_label }}</td><td>{{ permit.start_date }} {{ publicText("copy.vue_services_studentpermit", "text_9004", "s.d.") }} {{ permit.end_date }}</td><td>{{ permit.status_label }}</td></tr></tbody></table></div>
                            <small>{{ publicText("copy.vue_services_studentpermit", "text_043", "Untuk perubahan izin, silakan konfirmasi langsung ke Wali Kelas.") }}</small>
                            <button type="button" @click="activeTab = 'status'; statusQuery = studentQuery; lookupStatus()">{{ publicText("copy.vue_services_studentpermit", "text_044", "Buka Menu Lacak Status") }}</button>
                        </aside>
                    </div>

                    <h2 class="permit-section-title full-span">{{ publicText("copy.vue_services_studentpermit", "text_045", "2. Rincian Permohonan Izin") }}</h2>
                    <FormField class="full-span" id="izin-type" :label="publicText('copy.vue_services_studentpermit', 'label_10001', 'Jenis Izin')" required :error="form.errors.type" v-slot="{ inputId, describedBy, invalid }">
                        <select :id="inputId" v-model="permitKind" :aria-describedby="describedBy" :aria-invalid="invalid">
                            <option v-for="kind in permitKinds" :key="kind.value" :value="kind.value">{{ kind.label }}</option>
                        </select>
                    </FormField>
                    <FormField v-if="permitKind === 'other'" id="izin-other-kind" class="full-span" :label="publicText('copy.vue_services_studentpermit', 'label_10002', 'Jenis Izin Lainnya')" required :hint="publicText('copy.vue_services_studentpermit', 'hint_10003', 'Mohon tuliskan jenis izin secara singkat, lalu lengkapi alasan detailnya pada kotak Alasan di bawah.')" :error="form.errors.title">
                        <input id="izin-other-kind" v-model="otherKind" required :placeholder="publicText('copy.vue_services_studentpermit', 'placeholder_116', 'Sebutkan jenis izin / keperluan Anda (misal: Mengurus Paspor, Khitanan, dll)')" />
                    </FormField>

                    <div class="permit-date-row full-span"><label>{{ publicText("copy.vue_services_studentpermit", "text_046", "Rentang Tanggal") }} <span class="text-red-600">*</span></label><div class="permit-dates">
                    <FormField id="izin-start" :label="publicText('copy.vue_services_studentpermit', 'label_10004', 'Mulai Tanggal (Tgl-Bln-Thn):')" :error="form.errors.start_date" v-slot="{ inputId, describedBy, invalid }">
                        <div class="permit-date-input"><span aria-hidden="true">{{ form.start_date.split('-').reverse().join('-') }}</span><input :id="inputId" v-model="form.start_date" type="date" required :aria-describedby="describedBy" :aria-invalid="invalid" @click="($event.target as HTMLInputElement).showPicker?.()" /></div>
                    </FormField>

                    <FormField id="izin-end" :label="publicText('copy.vue_services_studentpermit', 'label_10005', 'Sampai Tanggal (Tgl-Bln-Thn):')" :error="form.errors.end_date" v-slot="{ inputId, describedBy, invalid }">
                        <div class="permit-date-input"><span aria-hidden="true">{{ form.end_date.split('-').reverse().join('-') }}</span><input :id="inputId" v-model="form.end_date" type="date" required :aria-describedby="describedBy" :aria-invalid="invalid" @click="($event.target as HTMLInputElement).showPicker?.()" /></div>
                    </FormField>
                    <span class="permit-duration">{{ durationDays > 0 ? `${durationDays} Hari` : 'Tanggal Salah' }}</span>
                    </div></div>

                    <p v-if="datesConflict" class="permit-conflict full-span" role="alert"><b>{{ publicText("copy.vue_services_studentpermit", "text_047", "Tanggal Sudah Memiliki Izin:") }}</b> {{ publicText("copy.vue_services_studentpermit", "text_048", "Tanggal yang Anda pilih bentrok dengan pengajuan izin yang sudah terdaftar untuk siswa ini. Tombol kirim dinonaktifkan untuk mencegah duplikasi.") }}</p>
                    <div v-if="requiresToken" class="permit-date-row full-span"><label class="text-red-600">{{ publicText("copy.vue_services_studentpermit", "text_049", "Izin Lewat Batas Waktu") }}</label><div class="permit-token-box">
                        <h3>{{ publicText("copy.vue_services_studentpermit", "text_050", "Batas Pengajuan Mandiri Ditutup (Pukul 08:00 s/d 22:00 WITA)") }}</h3>
                        <strong class="permit-token-badge">{{ publicText("copy.vue_services_studentpermit", "text_051", "Perlu Token Wali Kelas (Sekali Pakai)") }}</strong>
                        <p>{{ publicText("copy.vue_services_studentpermit", "text_052", "Sesuai tata tertib madrasah, pengajuan izin hari ini ditutup pukul") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_053", "08:00 WITA") }}</b> {{ publicText("copy.vue_services_studentpermit", "text_054", "dan akan dibuka kembali pukul") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_055", "22:00 WITA") }}</b>{{ publicText("copy.vue_services_studentpermit", "text_056", ". Jika perlu mengirimkan surat izin pada jam aktif sekolah ini, orang tua wajib konfirmasi ke") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_057", "Wali Kelas / Guru Piket") }}</b> {{ publicText("copy.vue_services_studentpermit", "text_058", "untuk mendapatkan") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_059", "Kode Token Dispensasi (Sekali Pakai)") }}</b>{{ publicText("copy.vue_services_studentpermit", "text_060", ". Kode token otomatis hangus setelah dikirimkan dan tidak dapat digunakan untuk siswa lain.") }}</p>
                        <button type="button" class="permit-contact-teacher" @click="contactHomeroom">{{ publicText("copy.vue_services_studentpermit", "text_061", "Hubungi Wali Kelas via WhatsApp untuk Minta Token") }}</button>
                        <small>{{ publicText("copy.vue_services_studentpermit", "text_062", "Klik tombol \"Cek Siswa\" di atas agar terhubung langsung ke nomor WhatsApp Wali Kelas siswa.") }}</small>
                        <FormField id="izin-token" :label="publicText('copy.vue_services_studentpermit', 'label_10006', 'Masukkan Kode Token Dispensasi:')" required :error="form.errors.one_time_token">
                            <div class="permit-lookup-controls"><input id="izin-token" v-model="form.one_time_token" type="text" :placeholder="publicText('copy.vue_services_studentpermit', 'placeholder_117', 'Contoh: 6 Digit Kode Token')" /><button type="button" :disabled="tokenBusy || !selectedStudent" @click="verifyToken">{{ publicText("copy.vue_services_studentpermit", "text_063", "Buka Kunci") }}</button></div>
                        </FormField>
                        <small role="status">{{ tokenMessage || 'Token bersifat Sekali Pakai dan otomatis hangus setelah permohonan berhasil dikirimkan.' }}</small>
                    </div></div>


                    <div class="full-span">
                        <FormField id="izin-reason" class="full-span" :label="publicText('copy.vue_services_studentpermit', 'label_10007', 'Alasan / Keterangan')" required :error="form.errors.reason" v-slot="{ inputId, describedBy, invalid }">
                            <textarea :id="inputId" v-model="form.reason" rows="3" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                    </div>

                    <h2 class="permit-section-title full-span">{{ publicText("copy.vue_services_studentpermit", "text_064", "3. Data Orang Tua / Pemohon Izin") }}</h2>
                    <FormField class="full-span" id="izin-contact-name" :label="publicText('copy.vue_services_studentpermit', 'label_10008', 'Nama Orang Tua / Wali')" required :error="form.errors.contact_name">
                        <input id="izin-contact-name" v-model="form.contact_name" type="text" required :placeholder="publicText('copy.vue_services_studentpermit', 'placeholder_118', 'Nama lengkap orang tua / wali yang bertanggung jawab')" />
                    </FormField>
                    <FormField class="full-span" id="izin-relationship" :label="publicText('copy.vue_services_studentpermit', 'label_10009', 'Hubungan dengan Siswa')" required :error="form.errors.contact_relationship">
                        <select id="izin-relationship" v-model="form.contact_relationship">
                            <option value="father">{{ publicText("copy.vue_services_studentpermit", "text_065", "Ayah (Orang Tua)") }}</option>
                            <option value="mother">{{ publicText("copy.vue_services_studentpermit", "text_066", "Ibu (Orang Tua)") }}</option>
                            <option value="guardian">{{ publicText("copy.vue_services_studentpermit", "text_067", "Wali Siswa") }}</option>
                            <option value="parent">{{ publicText("copy.vue_services_studentpermit", "text_068", "Orang Tua") }}</option>
                            <option value="family">{{ publicText("copy.vue_services_studentpermit", "text_069", "Keluarga Dekat") }}</option>
                        </select>
                    </FormField>
                    <FormField class="full-span" id="izin-contact-phone" :label="publicText('copy.vue_services_studentpermit', 'label_10010', 'Nomor WhatsApp Aktif')" required :error="form.errors.contact_phone" :hint="publicText('copy.vue_services_studentpermit', 'hint_10011', 'Notifikasi verifikasi atau konfirmasi dari pihak sekolah akan dikirimkan ke nomor ini.')">
                        <input id="izin-contact-phone" v-model="form.contact_phone" type="tel" required :placeholder="publicText('copy.vue_services_studentpermit', 'placeholder_119', 'Contoh: 081234567890')" />
                    </FormField>

                    <h2 class="permit-section-title full-span">{{ publicText("copy.vue_services_studentpermit", "text_070", "4. Unggah Bukti (Surat Dokter / Surat Keterangan)") }}</h2>
                    <FormField class="full-span" id="izin-attachment" :label="requiresEvidence ? 'Foto / File Bukti — Wajib Surat Dokter (≥ 3 Hari)' : 'Foto / File Bukti (Opsional jika < 3 hari)'" :required="requiresEvidence" :hint="publicText('copy.vue_services_studentpermit', 'hint_10012', 'Format yang didukung: JPG, PNG, JPEG, PDF. Foto dari kamera HP akan otomatis dikompres agar cepat diunggah.')" :error="form.errors.attachment" v-slot="{ inputId, describedBy, invalid }">
                        <div v-if="requiresEvidence" class="permit-evidence-warning"><strong>{{ publicText("copy.vue_services_studentpermit", "text_071", "Wajib Melampirkan Surat Keterangan Dokter!") }}</strong><p>{{ publicText("copy.vue_services_studentpermit", "text_9005", "Pengajuan izin sakit selama") }} {{ durationDays }} {{ publicText("copy.vue_services_studentpermit", "text_9006", "hari atau lebih wajib melampirkan foto/file Surat Keterangan Sakit resmi dari Dokter / Puskesmas / Faskes.") }}</p></div>
                        <input :id="inputId" ref="attachmentInput" type="file" accept=".pdf,.jpg,.jpeg,.png" :aria-describedby="describedBy" :aria-invalid="invalid" @change="pickAttachment" />
                        <div v-if="form.attachment" class="permit-preview"><img v-if="attachmentPreview" :src="attachmentPreview" :alt="publicText('copy.vue_services_studentpermit', 'alt_120', 'Pratinjau bukti izin')" /><p v-else>{{ form.attachment.name }}</p><button type="button" @click="clearAttachment">{{ publicText("copy.vue_services_studentpermit", "text_072", "Batalkan / Ganti Foto") }}</button></div>
                    </FormField>

                    <h2 class="permit-section-title full-span">{{ publicText("copy.vue_services_studentpermit", "text_073", "5. Tanda Tangan Digital Orang Tua / Wali Siswa") }}</h2>
                    <FormField class="full-span" id="izin-signature" :label="publicText('copy.vue_services_studentpermit', 'label_10013', 'Tanda Tangan')" required :error="form.errors.signature_data">
                        <p class="signature-instruction">{{ publicText("copy.vue_services_studentpermit", "text_074", "Goreskan tanda tangan Anda pada kotak di bawah ini menggunakan jari (layar sentuh HP) atau kursor mouse.") }}</p>
                        <div class="signature-pad">
                            <canvas id="izin-signature" ref="signatureCanvas" width="600" height="180" :aria-label="publicText('copy.vue_services_studentpermit', 'aria_label_121', 'Kanvas tanda tangan orang tua atau wali')" @pointerdown="startSignature" @pointermove="moveSignature" @pointerup="finishSignature" @pointercancel="finishSignature" />
                            <span v-if="!form.signature_data" aria-hidden="true">{{ publicText("copy.vue_services_studentpermit", "text_075", "Goreskan tanda tangan digital di sini") }}</span>
                        </div>
                        <div class="signature-toolbar"><small>{{ publicText("copy.vue_services_studentpermit", "text_076", "Tanda tangan ini sah sebagai bukti persetujuan resmi orang tua/wali siswa") }}</small><button type="button" class="signature-clear" @click="clearSignature">{{ publicText("copy.vue_services_studentpermit", "text_077", "Hapus / Ulangi TTD") }}</button></div>
                    </FormField>

                    <div class="full-span permit-submit">
                        <button type="submit" :disabled="form.processing || datesConflict || (requiresToken && !tokenVerified)">
                            {{ publicText("copy.vue_services_studentpermit", "text_078", "Kirim Permohonan Izin") }}
                        </button>
                    </div>
                </form>
            </section>

            <section v-if="activeTab === 'rules'" class="portal-card permit-secondary-card permit-rules">
                <h2>{{ publicText("copy.vue_services_studentpermit", "text_079", "Alur & Tata Cara Pengajuan Izin") }}</h2>
                <article class="permit-rule-step"><span>1</span><div><h3>{{ publicText("copy.vue_services_studentpermit", "text_080", "1. Masukkan NISN Siswa") }}</h3><p>{{ publicText("copy.vue_services_studentpermit", "text_081", "Orang tua memasukkan NISN siswa pada formulir untuk memuat nama, kelas, dan identitas siswa secara valid.") }}</p></div></article>
                <article class="permit-rule-step"><span>2</span><div><h3>{{ publicText("copy.vue_services_studentpermit", "text_082", "2. Lengkapi Data & Unggah Bukti") }}</h3><p>{{ publicText("copy.vue_services_studentpermit", "text_083", "Pilih jenis izin (Sakit/Izin), tentukan tanggal tidak masuk, tuliskan alasan, dan unggah foto surat keterangan dokter (jika sakit) atau surat izin.") }}</p></div></article>
                <article class="permit-rule-step"><span>3</span><div><h3>{{ publicText("copy.vue_services_studentpermit", "text_084", "3. Pengajuan Diterima Sekolah") }}</h3><p>{{ publicText("copy.vue_services_studentpermit", "text_085", "Setelah formulir dikirim, sistem akan menerbitkan") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_086", "Nomor Registrasi Pengajuan") }}</b> {{ publicText("copy.vue_services_studentpermit", "text_087", "resmi.") }}</p></div></article>
                <article class="permit-rule-step"><span>4</span><div><h3>{{ publicText("copy.vue_services_studentpermit", "text_088", "4. Verifikasi Guru / Wali Kelas") }}</h3><p>{{ publicText("copy.vue_services_studentpermit", "text_089", "Wali kelas atau pihak sekolah akan memeriksa permohonan. Orang tua dapat mengecek status secara berkala di tab Cek Status serta mencetak Bukti Izin Digital.") }}</p></div></article>
                <aside class="permit-cutoff-rules"><h3>{{ publicText("copy.vue_services_studentpermit", "text_090", "Ketentuan Batas Jam Pengajuan Izin (Cut-Off Time):") }}</h3><ul>
                    <li><b>{{ publicText("copy.vue_services_studentpermit", "text_091", "Waktu Bebas Mandiri:") }}</b> {{ publicText("copy.vue_services_studentpermit", "text_092", "Pengajuan izin mandiri dibuka bebas mulai pukul") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_093", "22:00 Malam s/d 08:00 Pagi WITA") }}</b> {{ publicText("copy.vue_services_studentpermit", "text_094", "tanpa memerlukan kode token apa pun.") }}</li>
                    <li><b>{{ publicText("copy.vue_services_studentpermit", "text_095", "Waktu Terkunci Jam Sekolah (Pukul 08:00 s/d 22:00 WITA):") }}</b> {{ publicText("copy.vue_services_studentpermit", "text_096", "Formulir izin terkunci selama jam sekolah untuk mencegah manipulasi kehadiran. Pengajuan di jam ini wajib konfirmasi ke Wali Kelas / Guru Piket untuk mendapatkan") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_059", "Kode Token Dispensasi (Sekali Pakai)") }}</b>{{ publicText("copy.vue_services_studentpermit", "text_097", ". Begitu surat izin terkirim, token otomatis hangus dan tidak dapat dipakai untuk siswa lain.") }}</li>
                    <li><b>{{ publicText("copy.vue_services_studentpermit", "text_098", "Izin Hari Esok / Terencana:") }}</b> {{ publicText("copy.vue_services_studentpermit", "text_099", "Bebas diajukan kapan saja 24 jam tanpa memerlukan kode token, asalkan tanggal mulai izin adalah hari esok atau mendatang.") }}</li>
                </ul></aside>
            </section>

            <section v-if="activeTab === 'status'" class="portal-card permit-secondary-card permit-status">
                <div class="permit-status-heading"><span aria-hidden="true">⌕</span><div><h2>{{ publicText("copy.vue_services_studentpermit", "text_100", "Cek Status Permohonan Izin") }}</h2><p>{{ publicText("copy.vue_services_studentpermit", "text_101", "Lacak progres dan status verifikasi permohonan izin siswa oleh wali kelas/guru secara realtime.") }}</p></div></div>
                <hr />
                <form @submit.prevent="lookupStatus"><label for="permit-status-query">{{ publicText("copy.vue_services_studentpermit", "text_102", "Masukkan NISN Siswa, No. Pengajuan, atau Nama Siswa:") }}</label><div class="permit-status-controls"><input id="permit-status-query" v-model="statusQuery" required :placeholder="publicText('copy.vue_services_studentpermit', 'placeholder_122', 'Contoh: 0071234567 atau IZN-20260820-0001')" /><button type="submit" :disabled="statusBusy">{{ statusBusy ? 'Mencari...' : 'Cari Data' }}</button></div><small>{{ publicText("copy.vue_services_studentpermit", "text_103", "Masukkan") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_032", "10 digit NISN") }}</b>, <b>{{ publicText("copy.vue_services_studentpermit", "text_104", "Nomor Registrasi") }}</b> {{ publicText("copy.vue_services_studentpermit", "text_105", "(misal:") }} <code>{{ publicText("copy.vue_services_studentpermit", "text_106", "IZN-20260820-0001") }}</code>{{ publicText("copy.vue_services_studentpermit", "text_107", "), atau") }} <b>{{ publicText("copy.vue_services_studentpermit", "text_034", "Nama Lengkap Siswa") }}</b>.</small></form>
                <p v-if="statusMessage" role="status">{{ statusMessage }}</p>
                <div v-if="statusResults.length" class="permit-table-scroll"><table><thead><tr><th>{{ publicText("copy.vue_services_studentpermit", "text_039", "No. Registrasi") }}</th><th>{{ publicText("copy.vue_services_studentpermit", "text_040", "Kategori") }}</th><th>{{ publicText("copy.vue_services_studentpermit", "text_041", "Periode") }}</th><th>{{ publicText("copy.vue_services_studentpermit", "text_042", "Status") }}</th></tr></thead><tbody><tr v-for="permit in statusResults" :key="permit.ticket_code || permit.start_date"><td>{{ permit.ticket_code }}</td><td>{{ permit.type_label }}</td><td>{{ permit.start_date }} {{ publicText("copy.vue_services_studentpermit", "text_9004", "s.d.") }} {{ permit.end_date }}</td><td>{{ permit.status_label }}</td></tr></tbody></table></div>
            </section>

            <section v-if="activeTab === 'status' && revisingPermit" class="portal-card">
                <h2>{{ publicText("copy.vue_services_studentpermit", "text_9007", "Perbaiki Permohonan Izin —") }} {{ revisingPermit.title }}</h2>
                <p v-if="revisingPermit.decision_note" class="warning-callout">{{ publicText("copy.vue_services_studentpermit", "text_9008", "Catatan Wali Kelas:") }} {{ revisingPermit.decision_note }}</p>
                <form class="form-grid" @submit.prevent="submitRevision">
                    <FormField id="rev-start" :label="publicText('copy.vue_services_studentpermit', 'label_10014', 'Tanggal Mulai')" required v-slot="{ inputId }">
                        <input :id="inputId" v-model="revisionForm.start_date" type="date" required />
                    </FormField>
                    <FormField id="rev-end" :label="publicText('copy.vue_services_studentpermit', 'label_10015', 'Tanggal Selesai')" required v-slot="{ inputId }">
                        <input :id="inputId" v-model="revisionForm.end_date" type="date" required />
                    </FormField>
                    <div class="full-span">
                        <FormField id="rev-title" :label="publicText('copy.vue_services_studentpermit', 'label_10016', 'Perihal Izin')" required v-slot="{ inputId }">
                            <input :id="inputId" v-model="revisionForm.title" type="text" required />
                        </FormField>
                    </div>
                    <div class="full-span">
                        <FormField id="rev-reason" :label="publicText('copy.vue_services_studentpermit', 'label_10017', 'Alasan Rinci')" required v-slot="{ inputId }">
                            <textarea :id="inputId" v-model="revisionForm.reason" rows="3" required />
                        </FormField>
                    </div>
                    <div class="full-span">
                        <FormField id="rev-att" :label="publicText('copy.vue_services_studentpermit', 'label_10018', 'Unggah Ulang Lampiran (Opsional)')" v-slot="{ inputId }">
                            <input :id="inputId" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" @change="revisionForm.attachment = ($event.target as HTMLInputElement).files?.[0] ?? null" />
                        </FormField>
                    </div>
                    <div class="full-span action-row">
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="revisionForm.processing">{{ publicText("copy.vue_services_studentpermit", "text_108", "Kirim Ulang Perbaikan") }}</button>
                        <button type="button" class="ui-button ui-button--secondary ui-button--md" @click="revisingPermit = null">{{ publicText("copy.vue_services_studentpermit", "text_109", "Batal") }}</button>
                    </div>
                </form>
            </section>

            <section v-if="activeTab === 'status' && myPermits.length" class="portal-card">
                <h2>{{ publicText("copy.vue_services_studentpermit", "text_110", "Riwayat Permohonan Izin") }}</h2>
                <div class="permit-list">
                    <article v-for="row in myPermits" :key="row.id" class="permit-item">
                        <div>
                            <div class="permit-head">
                                <strong>{{ row.title }}</strong>
                                <StatusBadge :status="row.status" :label="row.status_label" size="sm" />
                            </div>
                            <p class="muted">{{ row.student_name }} ({{ row.class_name }}) · {{ row.start_date }} {{ publicText("copy.vue_services_studentpermit", "text_9004", "s.d.") }} {{ row.end_date }}</p>
                            <p v-if="row.decision_note" class="muted">{{ publicText("copy.vue_services_studentpermit", "text_9009", "Catatan:") }} {{ row.decision_note }}</p>
                        </div>
                        <div class="action-row">
                            <a :href="`/layanan/izin-siswa/${row.id}/pdf`" target="_blank" rel="noopener" class="ui-button ui-button--secondary ui-button--sm">{{ publicText("copy.vue_services_studentpermit", "text_111", "Bukti PDF") }}</a>
                            <button v-if="row.status === 'needs_revision'" type="button" class="ui-button ui-button--primary ui-button--sm" @click="openRevision(row)">{{ publicText("copy.vue_services_studentpermit", "text_112", "Perbaiki") }}</button>
                        </div>
                    </article>
                </div>
            </section>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { background: #f8fafc; color: #343a40; padding: 0; font-family: 'Poppins', 'Plus Jakarta Sans', sans-serif; }
.portal-container { width: min(1140px, 100%); margin-inline: auto; padding: 25px 15px 39px; display: grid; gap: 19.5px; }
.portal-hero { padding: 38.75px 0 34px; border-radius: 0 0 24px 24px; color: #fff; background: linear-gradient(135deg, #0f2b48 0%, #1e3c72 50%, #2a5298 100%); box-shadow: 0 10px 25px #0f2b4826; }
.permit-hero-inner { width: min(1140px, 100%); padding-inline: 15px; margin-inline: auto; display: grid; grid-template-columns: minmax(0, 7fr) minmax(0, 5fr); align-items: center; gap: 30px; }
.portal-hero h1 { margin: 8px 0 4px; color: white; font-size: 32px; font-weight: 800; line-height: 1.25; letter-spacing: -.3px; }
.portal-hero p { margin: 8px 0 14px; color: #ffffffe6; font-size: 13px; line-height: 1.55; max-width: 600px; }
.portal-hero .permit-badge { display: inline-flex; margin: 0; padding: 4px 12px; border: 1px solid #ffffff40; border-radius: 999px; background: #ffffff26; color: #fef08a; font-size: 11px; line-height: 16.5px; letter-spacing: .5px; font-weight: 700; text-transform: uppercase; }
.permit-school { font-size: 13.5px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase; }
.permit-school-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; line-height: 20.25px; }
.permit-hero-inner > div > p:not(.permit-badge) { margin-top: 6.5px; }
.permit-year { background: #ffc107; color: #212529; border-radius: 6px; padding: 3.25px 6.5px; font-size: 11px; line-height: 11px; font-weight: 700; }
.permit-hero-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
.permit-hero-tags span { padding: 4px 12px; border: 1px solid #ffffff29; border-radius: 999px; background: #0003; font-size: 11.5px; font-weight: 600; }
.permit-steps { padding: 16px 18px; border: 1px solid #ffffff30; border-radius: 16px; background: #111d343d; font-size: 13px; }
.permit-steps > strong { display: block; padding: 0 0 8px; border-bottom: 1px solid #ffffff28; }
.permit-steps-heading { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 0 18px 6.5px; border-bottom: 1px solid #ffffff26; }
.permit-steps-heading > span { border-radius: 20px; padding: 1px 18px; color: #343a40; background: #f8f9fa; font-size: 10px; font-weight: 700; white-space: nowrap; }
.portal-hero .permit-steps-footer { margin: 6.5px 0 0; padding: 6.5px 14px 0; border-top: 1px solid #ffffff1f; color: #ffffff80; font-size: 11px; line-height: 16.5px; max-width: none; }
.permit-steps li { font-size: 12px; }
.permit-steps ol { display: grid; gap: 6.5px; margin: 6.5px 0 0; padding: 0; list-style: none; }
.permit-steps li { display: flex; gap: 9px; align-items: center; }
.permit-steps b { display: grid; flex: 0 0 22px; height: 22px; place-items: center; border-radius: 50%; background: #f59e0b; color: white; font-size: 11px; }
.permit-tabs { justify-self: center; display: flex; flex-wrap: wrap; justify-content: center; gap: 4px; padding: 5px; border: 1px solid #dce5f0; border-radius: 24px; background: white; box-shadow: 0 5px 14px #1729440f; }
.permit-tabs button { padding: 9px clamp(10px, 2.6vw, 26px); border-radius: 20px; color: #64748b; font-size: 13px; font-weight: 700; }
.permit-tabs button[aria-pressed="true"] { background: #223d6c; color: white; box-shadow: 0 3px 8px #223d6c44; }
.action-row { display: flex; flex-wrap: wrap; gap: .6rem; }
.portal-card { background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; padding: 1.5rem; display: grid; gap: 1rem; }
.portal-card h2 { margin: 0; font-size: 1.2rem; }
.permit-application { width: calc(83.333333% - 5px); justify-self: center; padding: 19.5px; border: 0; border-radius: 12px; box-shadow: 0 5px 20px #00000014; gap: 19.5px; }
.permit-notice { padding: 12px 18px 14px 39px; border: 1px solid #d6dfe8; border-left: 4px solid #317efb; border-radius: 8px; background: #f8fbff; font-size: 13.5px; line-height: 1.5; }
.permit-notice ul { margin: 3.25px 0 0; padding-left: 1rem; color: #6c757d; list-style: disc; }
.permit-application .permit-section-title { background: #eaf1fa; padding: 11px 18px 11px 24px; border-left: 4px solid #223d6c; border-radius: 4px; color: #223d6c; font-size: 13px; font-weight: 800; }
.permit-lookup { display: grid; grid-template-columns: 1fr 3fr; gap: 12px; align-items: start; font-size: 13px; font-weight: 700; }
.permit-lookup label { padding-top: 8px; }
.permit-lookup-controls { display: flex; }
.permit-lookup-controls input { flex: 1; min-width: 0; padding: 6px 9px; border: 1px solid #c4d0de; border-radius: 4px 0 0 4px; background: white; }
.permit-lookup-controls button { padding: 6px 18px; background: #007bff; color: white; border-radius: 0 4px 4px 0; }
.permit-lookup small { display: block; margin-top: 4px; color: #6c757d; font-size: 11px; font-weight: 400; }
.permit-application .form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
.permit-application :deep(.ss-form-field.full-span) { display: grid; grid-template-columns: 172px minmax(0, 1fr); gap: 0 18px; align-items: start; }
.permit-application :deep(.ss-form-field.full-span > label) { padding-top: 7px; font-size: 13px; font-weight: 700; }
.permit-application :deep(.ss-form-field.full-span > :is(.ss-form-field__hint, .ss-form-field__error)) { grid-column: 2; }
.permit-application :deep(input:not([type="file"]), select, textarea) { width: 100%; min-width: 0; border: 1px solid #cbd5e1; border-radius: 3px; background: white; padding: 6px 10px; font-size: 13px; }
.signature-instruction { margin-bottom: 8px; font-size: 11px; color: #6b7280; line-height: 1.5; }
.signature-pad { position: relative; height: 164px; border: 2px dashed #397dff; border-radius: 10px; background: #f8f9fa; }
.signature-pad canvas { display: block; width: 100%; height: 160px; touch-action: none; }
.signature-pad span { position: absolute; inset: 0; display: grid; place-items: center; pointer-events: none; color: #8da2c2; font-size: 14px; }
.signature-clear { margin-top: 10px; padding: 3px 20px; border: 1px solid #718096; border-radius: 2px; color: #64748b; font-size: 11px; }
.permit-submit { display: flex; justify-content: center; border-top: 1px solid #e2e8f0; padding-top: 20px; }
.permit-submit button { border-radius: 999px; padding: 11px 50px; background: #007bff; color: white; font-size: 14px; font-weight: 700; box-shadow: 0 4px 10px #007bff40; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; }
.full-span { grid-column: 1 / -1; }
.alert-banner { padding: .9rem 1rem; border-radius: 12px; display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; justify-content: space-between; }
.alert-banner--success { background: #e1f2e8; color: #1b5c3e; border: 1px solid #b6dec9; font-weight: 600; }
.alert-banner--pin { background: #eef4ff; color: #1e3a8a; border: 1px solid #bfdbfe; }
.warning-callout { margin: 0; padding: .85rem 1rem; border-radius: 10px; background: #fff1d5; color: #80500d; font-weight: 600; }
.permit-list { display: grid; gap: .8rem; }
.permit-item { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; padding: .95rem; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 12px; }
.permit-head { display: flex; align-items: center; gap: .5rem; }
.muted { margin: .25rem 0 0; color: var(--public-muted, #526673); font-size: .88rem; }
.tab-mobile { display: none; }
.permit-date-row { display: grid; grid-template-columns: 25% minmax(0, 1fr); font-size: 13px; font-weight: 700; }
.permit-date-row > label { padding-top: 7px; padding-right: 20px; }
.permit-dates { display: grid; grid-template-columns: 1fr 1fr auto; align-items: end; gap: 20px; }
.permit-dates :deep(label) { color: #6c757d; font-size: 11px; }
.permit-date-input { position: relative; min-height: 34px; border: 1px solid #ced4da; border-radius: 4px; padding: 6px 12px; background: white; font-size: 13px; font-weight: 400; }
.permit-date-input::after { content: '▦'; position: absolute; right: 12px; color: #007bff; }
.permit-date-input:focus-within { outline: 2px solid #80bdff; outline-offset: 1px; }
.permit-date-input :deep(input) { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
.permit-duration { background: #17a2b8; color: white; padding: 8px; border-radius: 4px; white-space: nowrap; font-size: 13px; }
.permit-application :deep(.ss-form-field.full-span) { grid-template-columns: calc(25% - 14px) minmax(0, 1fr); }
.permit-application :deep(input:not([type="file"]), select, textarea) { min-height: 34px; padding: 6px 12px; border-radius: 4px; font-family: inherit; font-size: 13px; }
.permit-application :deep(.ss-form-field__hint) { font-size: 11px; color: #6c757d; }
.permit-application :deep(.ss-form-field__label) { color: #343a40; line-height: 1.5; }
.permit-token-box { background: #fffbeb; padding: 16px; border: 1px solid #f59e0b; border-radius: 12px; font-weight: 400; }
.permit-token-box h3 { font-size: 14.5px; font-weight: 700; }
.permit-token-box p { font-size: 11px; line-height: 1.5; color: #6c757d; margin: 8px 0; }
.permit-token-box small { display: block; margin: 6px 0 12px; font-size: 11px; color: #6c757d; }
.permit-token-badge { display: inline-block; background: #ffc107; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
.permit-contact-teacher { background: #28a745; color: white; padding: 4px 12px; font-size: 11px; border-radius: 6px; }
.permit-token-box .permit-lookup-controls button { background: #ffc107; color: #212529; }
.permit-student-info { background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 8px; padding: 12px 20px; font-size: 13px; }
.permit-student-info span { margin-left: 12px; }
.permit-active-warning { background: #fff9db; border: 1px solid #ffe066; padding: 16px; border-radius: 4px; margin-top: 16px; }
.permit-active-warning p { margin: 4px 0 10px; }
.permit-active-warning button { padding: 4px 10px; border: 1px solid #007bff; border-radius: 4px; color: #007bff; margin-top: 8px; }
.permit-conflict, .permit-evidence-warning { padding: 12px; background: #fff8f8; color: #721c24; border-left: 4px solid #dc3545; font-size: 12px; }
.permit-preview { padding: 15px; border: 2px dashed #b8c7db; border-radius: 8px; text-align: center; margin-top: 10px; }
.permit-preview img { max-height: 250px; max-width: 100%; margin: auto; border-radius: 6px; }
.permit-preview button { border: 1px solid #dc3545; color: #dc3545; padding: 4px 8px; border-radius: 4px; margin-top: 8px; }
.signature-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
.signature-toolbar small { font-size: 10px; color: #6c757d; }
.signature-toolbar button { margin-top: 0; }
.permit-submit button:disabled { opacity: .65; cursor: not-allowed; }
.permit-secondary-card { justify-self: center; width: calc(75% - 7.5px); padding: 20px; box-shadow: 0 5px 20px #00000014; border: 0; border-radius: 12px; }
.permit-secondary-card h2 { font-size: 22px; color: #007bff; font-weight: 700; line-height: 1.2; }
.permit-rules > h2 { text-align: center; margin-bottom: 8px; }
.permit-rule-step { display: flex; align-items: start; gap: 15px; padding: 15px; border-left: 4px solid #1e3c72; border-radius: 10px; box-shadow: 0 2px 8px #0000000a; }
.permit-rule-step > span { display: grid; place-items: center; flex: 0 0 44px; height: 44px; border-radius: 50%; background: #eef4fc; color: #1e3c72; font-size: 18px; }
.permit-rule-step h3 { font-size: 15px; font-weight: 700; margin: 0 0 4px; }
.permit-rule-step p { font-size: 13px; color: #6c757d; margin: 0; }
.permit-cutoff-rules { background: #fff3cd; border: 1px solid #f59e0b; border-radius: 10px; padding: 16px; margin-top: 8px; }
.permit-cutoff-rules h3 { font-size: 14px; font-weight: 700; }
.permit-cutoff-rules ul { list-style: disc; padding-left: 16px; color: #6c757d; font-size: 11px; line-height: 1.6; }
.permit-status-heading { display: flex; align-items: center; gap: 16px; }
.permit-status-heading > span { display: grid; place-items: center; flex: 0 0 48px; height: 48px; border-radius: 50%; background: #007bff; color: white; font-size: 26px; box-shadow: 0 4px 14px #007bff4d; }
.permit-status-heading p { font-size: 11px; color: #6c757d; margin: 4px 0 0; }
.permit-status hr { border: 0; border-top: 1px solid #0000001a; }
.permit-status label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; }
.permit-status-controls { display: flex; overflow: hidden; border: 1.5px solid #cbd5e1; border-radius: 10px; box-shadow: 0 2px 4px #00000013; }
.permit-status-controls input { width: 0; flex: 1; min-width: 0; padding: 12px 16px; font-size: 15px; border: 0; }
.permit-status-controls button { padding: 12px 24px; background: #007bff; color: white; font-weight: 700; font-size: 15px; white-space: nowrap; }
.permit-status small { display: block; color: #6c757d; margin-top: 8px; font-size: 11px; }
.permit-table-scroll { overflow-x: auto; }
.permit-table-scroll table { width: 100%; border-collapse: collapse; font-size: 12px; }
.permit-table-scroll th, .permit-table-scroll td { padding: 8px; border: 1px solid #dee2e6; text-align: left; }
@media (max-width: 1199.98px) {
    .permit-hero-inner, .portal-container { max-width: 960px; }
}
@media (max-width: 991.98px) {
    .permit-hero-inner, .portal-container { max-width: 720px; }
    .permit-hero-inner { grid-template-columns: minmax(0, 1fr); gap: 19.5px; }
    .permit-application { width: 100%; }
    .permit-secondary-card { width: 100%; }
}
@media (max-width: 767.98px) {
    .permit-hero-inner, .portal-container { max-width: 540px; }
    .portal-hero h1 { font-size: 28px; }
    .permit-tabs { width: 100%; border-radius: 14px; flex-wrap: nowrap; }
    .permit-tabs button { flex: 1; min-width: 0; padding: 9px 4px; font-size: 11.5px; border-radius: 10px; }
    .tab-desktop { display: none; }
    .tab-mobile { display: inline; }
    .permit-lookup { grid-template-columns: minmax(0, 1fr); gap: 4px; }
}
@media (max-width: 490px) {
    .form-grid, .permit-application .form-grid { grid-template-columns: minmax(0, 1fr); }
    .permit-application :deep(.ss-form-field.full-span) { grid-template-columns: minmax(0, 1fr); gap: 6px; }
    .permit-application :deep(.ss-form-field.full-span > :is(.ss-form-field__hint, .ss-form-field__error)) { grid-column: 1; }
}
@media (max-width: 575.98px) {
    .permit-date-row, .permit-application :deep(.ss-form-field.full-span) { grid-template-columns: minmax(0, 1fr); gap: 6px; }
    .permit-dates { grid-template-columns: minmax(0, 1fr); gap: 12px; }
    .permit-duration { justify-self: start; }
    .permit-application :deep(.ss-form-field.full-span > :is(.ss-form-field__hint, .ss-form-field__error)) { grid-column: 1; }
}
</style>
