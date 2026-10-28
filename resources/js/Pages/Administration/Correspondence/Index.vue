<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, reactive, ref, watch } from 'vue';

interface StatusOption { value: string; label: string }
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
}

const props = defineProps<{
    records: CorrespondenceRecord[];
    filter: 'all' | 'incoming' | 'outgoing' | 'archived';
    status_options: { incoming: StatusOption[]; outgoing: StatusOption[] };
    can_manage: boolean;
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
    subject: '',
    summary: '',
    letter_body: '',
    signer_name: '',
    signer_title: '',
    attachment_note: '',
    approval_required: false,
    signature_method: 'manual',
    attachment: null as File | null,
});

const editingId = ref<number | null>(null);
const creating = ref(false);
const search = ref('');
const currentPage = ref(1);
const filteredRecords = computed(() => {
    const query = search.value.trim().toLocaleLowerCase();
    return query ? props.records.filter((record) => [record.subject, record.letter_number, record.agenda_number, record.sender, record.recipient].some((value) => value?.toLocaleLowerCase().includes(query))) : props.records;
});
const pageCount = computed(() => Math.max(1, Math.ceil(filteredRecords.value.length / 10)));
const visibleRecords = computed(() => filteredRecords.value.slice((currentPage.value - 1) * 10, currentPage.value * 10));
watch([search, () => props.filter], () => { currentPage.value = 1; });

function startNew(type: 'incoming' | 'outgoing') {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.letter_type = type;
    creating.value = true;
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
    form.subject = record.subject;
    form.summary = record.summary ?? '';
    form.letter_body = record.letter_body ?? '';
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

const tabs = [
    { value: 'all', label: 'Semua surat' },
    { value: 'incoming', label: 'Surat masuk' },
    { value: 'outgoing', label: 'Surat keluar' },
    { value: 'archived', label: 'Arsip' },
] as const;

function nextStatuses(record: CorrespondenceRecord): StatusOption[] {
    const statuses = props.status_options[record.letter_type];
    if (record.letter_type === 'outgoing' && record.status === 'draft' && !record.has_document) {
        return statuses.filter((item) => item.value === 'sent');
    }
    const currentIndex = statuses.findIndex((item) => item.value === record.status);
    return currentIndex >= 0 ? statuses.slice(currentIndex + 1, currentIndex + 2) : [];
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

            <section class="panel correspondence-panel workflow">
                <div class="table-title"><div><h2>Mulai dari sini</h2><p>Pilih pekerjaan yang ingin dilakukan.</p></div></div>
                <div v-if="can_manage" class="workflow-actions">
                    <button type="button" class="primary-button" @click="startNew('incoming')">Catat surat masuk</button>
                    <button type="button" class="secondary-button" @click="startNew('outgoing')">Buat surat keluar</button>
                </div>
                <p class="workflow-help">Surat masuk: catat → disposisi → selesai → arsip. Surat keluar: buat draf → periksa PDF → finalkan untuk membuat QR → tandatangani dan kirim → tandai terkirim → arsip.</p>
                <p class="workflow-help">QR memeriksa pencatatan surat. QR tidak memastikan isi dokumen tidak berubah dan bukan tanda tangan elektronik.</p>
            </section>

            <section v-if="can_manage" v-show="creating" id="form-surat" class="panel correspondence-panel">
                <div class="table-title"><div><h2>{{ editingId ? 'Ubah draf surat keluar' : form.letter_type === 'incoming' ? 'Catat surat masuk' : 'Buat surat keluar' }}</h2><p>Nomor surat keluar diisi manual sesuai penomoran sekolah.</p></div></div>
                <form class="correspondence-form" enctype="multipart/form-data" @submit.prevent="saveRecord">
                    <label v-if="!editingId">Jenis surat
                        <select v-model="form.letter_type">
                            <option value="incoming">Surat masuk</option>
                            <option value="outgoing">Surat keluar</option>
                        </select>
                        <span v-if="form.errors.letter_type" class="field-error">{{ form.errors.letter_type }}</span>
                    </label>
                    <label v-if="form.letter_type === 'outgoing'">Nomor surat keluar <span class="required-mark">*</span>
                        <input v-model="form.letter_number" maxlength="100" required autocomplete="off" placeholder="Contoh: 001/ADM/2026" />
                        <span v-if="form.errors.letter_number" class="field-error">{{ form.errors.letter_number }}</span>
                    </label>
                    <label v-if="form.letter_type === 'outgoing'">Format surat
                        <select v-model="form.letter_template">
                            <option value="official_school">Surat resmi sekolah</option>
                            <option value="invitation">Undangan</option>
                            <option value="assignment">Surat tugas</option>
                            <option value="statement">Surat keterangan</option>
                        </select>
                        <span class="field-hint">Struktur kop, identitas, nomor, perihal, isi, dan penandatangan dijaga konsisten.</span>
                        <span v-if="form.errors.letter_template" class="field-error">{{ form.errors.letter_template }}</span>
                    </label>
                    <label v-else>Nomor surat dari pengirim
                        <input v-model="form.letter_number" maxlength="100" autocomplete="off" />
                        <span v-if="form.errors.letter_number" class="field-error">{{ form.errors.letter_number }}</span>
                    </label>
                    <label>Tanggal surat
                        <input v-model="form.letter_date" type="date" :required="form.letter_type === 'outgoing' && !!form.letter_body" />
                        <span v-if="form.errors.letter_date" class="field-error">{{ form.errors.letter_date }}</span>
                    </label>
                    <label v-if="form.letter_type === 'incoming'">Tanggal diterima <span class="required-mark">*</span>
                        <input v-model="form.received_at" type="date" required />
                        <span v-if="form.errors.received_at" class="field-error">{{ form.errors.received_at }}</span>
                    </label>
                    <label v-if="form.letter_type === 'incoming'">Asal surat <span class="required-mark">*</span>
                        <input v-model="form.sender" maxlength="180" required autocomplete="off" />
                        <span v-if="form.errors.sender" class="field-error">{{ form.errors.sender }}</span>
                    </label>
                    <label v-else>Tujuan surat <span class="required-mark">*</span>
                        <input v-model="form.recipient" maxlength="180" required autocomplete="off" />
                        <span v-if="form.errors.recipient" class="field-error">{{ form.errors.recipient }}</span>
                    </label>
                    <label class="correspondence-wide">Perihal <span class="required-mark">*</span>
                        <input v-model="form.subject" maxlength="200" required autocomplete="off" />
                        <span v-if="form.errors.subject" class="field-error">{{ form.errors.subject }}</span>
                    </label>
                    <label v-if="form.letter_type === 'outgoing'">Sifat surat
                        <select v-model="form.urgency">
                            <option value="ordinary">Biasa</option>
                            <option value="urgent">Segera</option>
                            <option value="immediate">Sangat segera</option>
                        </select>
                    </label>
                    <label v-if="form.letter_type === 'outgoing'">Klasifikasi
                        <input v-model="form.classification" maxlength="40" placeholder="Contoh: Umum" />
                    </label>
                    <label class="correspondence-wide">Ringkasan
                        <textarea v-model="form.summary" rows="2" maxlength="5000" />
                        <span v-if="form.errors.summary" class="field-error">{{ form.errors.summary }}</span>
                    </label>
                    <template v-if="form.letter_type === 'outgoing'">
                        <div class="correspondence-wide form-note">Isi naskah untuk membuat PDF. Kosongkan jika hanya mencatat surat yang dibuat di luar aplikasi.</div>
                        <label class="correspondence-wide">Isi surat
                            <textarea v-model="form.letter_body" rows="8" maxlength="20000" placeholder="Tuliskan isi surat di sini" />
                            <span v-if="form.errors.letter_body" class="field-error">{{ form.errors.letter_body }}</span>
                        </label>
                        <label>Nama penandatangan <span v-if="form.letter_body" class="required-mark">*</span>
                            <input v-model="form.signer_name" maxlength="180" :required="!!form.letter_body" />
                            <span v-if="form.errors.signer_name" class="field-error">{{ form.errors.signer_name }}</span>
                        </label>
                        <label>Jabatan penandatangan <span v-if="form.letter_body" class="required-mark">*</span>
                            <input v-model="form.signer_title" maxlength="180" :required="!!form.letter_body" />
                            <span v-if="form.errors.signer_title" class="field-error">{{ form.errors.signer_title }}</span>
                        </label>
                        <label>Keterangan lampiran surat
                            <input v-model="form.attachment_note" maxlength="180" placeholder="Contoh: 1 berkas" />
                            <span v-if="form.errors.attachment_note" class="field-error">{{ form.errors.attachment_note }}</span>
                        </label>
                        <label class="correspondence-wide approval-option">
                            <span><input v-model="form.approval_required" type="checkbox" /> Minta persetujuan kepala sekolah sebelum diterbitkan</span>
                            <small>Persetujuan tercatat sebagai audit. Ini bukan tanda tangan elektronik tersertifikasi.</small>
                        </label>
                    </template>
                    <label v-if="!editingId" class="correspondence-wide">Lampiran (PDF, Office, JPG/PNG; maks. 10 MB)
                        <input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" @change="setAttachment" />
                        <span v-if="form.errors.attachment" class="field-error">{{ form.errors.attachment }}</span>
                    </label>
                    <div class="correspondence-wide correspondence-actions">
                        <button class="primary-button" type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan…' : editingId ? 'Simpan perubahan' : form.letter_type === 'outgoing' ? 'Simpan draf' : 'Simpan surat masuk' }}
                        </button>
                        <button class="secondary-button" type="button" @click="creating = false">Batal</button>
                    </div>
                </form>
            </section>

            <section class="panel correspondence-panel">
                <div class="table-title">
                    <div><h2>Daftar surat</h2><p>Surat masuk mengikuti alur diterima → disposisi → selesai → arsip.</p></div>
                </div>
                <nav class="correspondence-tabs" aria-label="Filter surat">
                    <Link v-for="tab in tabs" :key="tab.value" :href="`/administrasi/persuratan?filter=${tab.value}`" class="correspondence-tab" :class="{ 'is-active': filter === tab.value }">
                        {{ tab.label }}
                    </Link>
                </nav>

                <label class="search-label">Cari surat
                    <input v-model="search" type="search" placeholder="Perihal, nomor, asal, atau tujuan" />
                </label>
                <div v-if="visibleRecords.length" class="correspondence-list">
                    <article v-for="record in visibleRecords" :key="record.id" class="correspondence-card">
                        <div class="correspondence-card-heading">
                            <div>
                                <span class="correspondence-kind" :class="record.letter_type === 'incoming' ? 'kind-incoming' : 'kind-outgoing'">
                                    {{ record.letter_type === 'incoming' ? 'Surat masuk' : 'Surat keluar' }}
                                </span>
                                <h3>{{ record.subject }}</h3>
                                <p>{{ record.letter_type === 'incoming' ? `Asal: ${record.sender || '—'}` : `Tujuan: ${record.recipient || '—'}` }}</p>
                            </div>
                            <span class="status-chip">{{ labelForStatus(record) }}</span>
                        </div>
                        <dl class="correspondence-meta">
                            <div v-if="record.agenda_number"><dt>No. agenda</dt><dd>{{ record.agenda_number }}</dd></div>
                            <div v-if="record.letter_number"><dt>No. surat</dt><dd>{{ record.letter_number }}</dd></div>
                            <div v-if="record.letter_date"><dt>Tanggal surat</dt><dd>{{ record.letter_date }}</dd></div>
                            <div v-if="record.received_at"><dt>Diterima</dt><dd>{{ record.received_at }}</dd></div>
                        </dl>
                        <p v-if="record.summary" class="correspondence-summary-text">{{ record.summary }}</p>
                        <p v-if="record.disposition" class="correspondence-disposition"><strong>Disposisi:</strong> {{ record.disposition }}</p>
                        <p v-if="record.letter_type === 'outgoing' && record.status === 'draft' && record.has_document" class="workflow-help">Periksa PDF draf, kemudian finalkan untuk membuat QR. Isi tidak dapat diubah setelah difinalkan.</p>
                        <p v-else-if="record.letter_type === 'outgoing' && record.status === 'draft'" class="workflow-help">Setelah surat dikirim, tandai sebagai terkirim.</p>
                        <p v-if="record.status === 'issued' && record.has_document" class="workflow-help">Buka PDF dengan QR, tandatangani dan kirim surat, lalu tandai terkirim.</p>
                        <p v-else-if="record.letter_type === 'outgoing' && record.status === 'sent' && record.has_document" class="workflow-help">PDF surat memuat QR untuk memeriksa pencatatan.</p>
                        <div class="correspondence-footer">
                            <div class="record-actions">
                                <button v-if="can_manage && record.letter_type === 'outgoing' && record.status === 'draft' && record.has_document" class="row-action-button" type="button" @click="editDraft(record)">Ubah draf</button>
                                <a v-if="record.has_document" :href="`/administrasi/persuratan/surat/${record.id}/pdf`" target="_blank" rel="noopener" class="row-action-button">{{ record.status === 'draft' ? 'Periksa PDF draf' : 'Buka PDF surat' }}</a>
                            <a v-if="record.has_attachment" :href="`/administrasi/persuratan/surat/${record.id}/lampiran`" class="row-action-button">
                                Unduh {{ record.attachment_name || 'lampiran' }}
                            </a>
                            </div>
                            <div v-if="!can_manage && record.approval_required && record.status === 'draft' && record.approval_status === 'pending'" class="approval-actions">
                                <span class="approval-pending">Menunggu persetujuan kepala sekolah</span>
                                <button class="primary-button" type="button" @click="approveRecord(record, 'approve')">Setujui</button>
                                <button class="secondary-button" type="button" @click="approveRecord(record, 'reject')">Tolak</button>
                            </div>
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
                        </div>
                    </article>
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
.hero-card { flex-wrap: wrap; }
.hero-card > div:first-child { flex: 1 1 18rem; min-width: 0; }
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
.correspondence-tab { border: 1px solid #dbe3ef; border-radius: 999px; padding: .45rem .85rem; color: #42526b; font-size: .85rem; font-weight: 600; text-decoration: none; }
.correspondence-tab.is-active { border-color: #3157a4; background: #eaf0ff; color: #214991; }
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
@media (max-width: 800px) { .correspondence-summary { width: 100%; min-width: 0; } .correspondence-form { grid-template-columns: 1fr; } .correspondence-wide { grid-column: auto; } .correspondence-footer { align-items: stretch; flex-direction: column; } .status-update-form { justify-content: stretch; } .status-update-form select, .status-update-form input, .status-update-form button { width: 100%; } .correspondence-card-heading { align-items: start; } }
</style>
