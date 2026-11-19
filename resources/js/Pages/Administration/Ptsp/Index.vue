<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface PtspCatalogItem {
    id: number;
    code: string;
    name: string;
    category: string;
    description?: string | null;
    requirements: string[];
    required_documents: { key: string; label: string; required: boolean }[];
    estimated_duration: string;
    service_hours?: string | null;
    delivery_mode: string;
    requires_verification_secret: boolean;
    is_active: boolean;
    is_published: boolean;
    sort_order: number;
}

interface PtspRequestRow {
    id: number;
    ticket_code: string | null;
    ticket_version: number | null;
    service_snapshot: { name?: string; code?: string };
    applicant_name: string;
    applicant_phone?: string | null;
    applicant_email?: string | null;
    applicant_organization?: string | null;
    purpose: string;
    notes?: string | null;
    status: string;
    revision_note?: string | null;
    rejection_reason?: string | null;
    disposition_to?: string | null;
    disposition_note?: string | null;
    physical_pickup_name?: string | null;
    physical_pickup_at?: string | null;
    submitted_at?: string | null;
    documents: { id: number; document_kind: string; document_number?: string | null; original_name: string; status: string }[];
    transitions: { id: number; public_status: string; actor_role?: string | null; reason?: string | null; created_at?: string | null }[];
}

const props = defineProps<{
    requests: PtspRequestRow[];
    pagination: { current: number; last: number; total: number; previous: string | null; next: string | null };
    summary: { submitted: number; in_review: number; needs_revision: number; approved: number; completed: number };
    catalog: PtspCatalogItem[];
    filters: { service_id: string | number; status: string; date_from: string; date_to: string; search: string };
    categories: { value: string; label: string }[];
    statusOptions: { value: string; label: string }[];
}>();

const activeTab = ref<'queue' | 'catalog'>('queue');
const selectedRequest = ref<PtspRequestRow | null>(null);
const actionMode = ref<'none' | 'revision' | 'reject' | 'disposition' | 'issue' | 'pickup'>('none');
const showCatalogModal = ref(false);
const editingServiceId = ref<number | null>(null);

const filterState = ref({ ...props.filters });
const applyFilters = () => {
    router.get('/administrasi/ptsp', {
        service_id: filterState.value.service_id || undefined,
        status: filterState.value.status || undefined,
        date_from: filterState.value.date_from || undefined,
        date_to: filterState.value.date_to || undefined,
        search: filterState.value.search || undefined,
    }, { preserveState: true, replace: true });
};

const actionForm = useForm({
    reason: '',
    disposition_to: '',
    disposition_note: '',
    delivery_mode: 'digital',
    mark_completed: true,
    result_summary: '',
    signatory_name: '',
    signatory_title: 'Kepala Tata Usaha',
    signature_mode: 'none',
    result_file: null as File | null,
    physical_pickup_name: '',
    physical_pickup_notes: '',
    expected_version: null as number | null,
});

const openRequestDetail = (row: PtspRequestRow) => {
    selectedRequest.value = row;
    actionMode.value = 'none';
    actionForm.reset();
    actionForm.clearErrors();
    actionForm.expected_version = row.ticket_version;
};

const startReview = (row: PtspRequestRow) => {
    router.post(`/administrasi/ptsp/permohonan/${row.id}/tinjau`, {
        expected_version: row.ticket_version,
    }, { preserveScroll: true });
};

const submitAction = () => {
    if (!selectedRequest.value) return;
    const id = selectedRequest.value.id;
    actionForm.expected_version = selectedRequest.value.ticket_version;

    if (actionMode.value === 'revision') {
        actionForm.post(`/administrasi/ptsp/permohonan/${id}/revisi`, {
            preserveScroll: true,
            onSuccess: () => { selectedRequest.value = null; },
        });
    } else if (actionMode.value === 'reject') {
        actionForm.post(`/administrasi/ptsp/permohonan/${id}/tolak`, {
            preserveScroll: true,
            onSuccess: () => { selectedRequest.value = null; },
        });
    } else if (actionMode.value === 'disposition') {
        actionForm.post(`/administrasi/ptsp/permohonan/${id}/disposisi`, {
            preserveScroll: true,
            onSuccess: () => { selectedRequest.value = null; },
        });
    } else if (actionMode.value === 'issue') {
        actionForm.post(`/administrasi/ptsp/permohonan/${id}/terbitkan`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { selectedRequest.value = null; },
        });
    } else if (actionMode.value === 'pickup') {
        actionForm.post(`/administrasi/ptsp/permohonan/${id}/ambil-fisik`, {
            preserveScroll: true,
            onSuccess: () => { selectedRequest.value = null; },
        });
    }
};

const catalogForm = useForm({
    code: '',
    name: '',
    category: 'persuratan',
    description: '',
    requirements_text: '',
    required_documents: [{ key: 'berkas_utama', label: 'Berkas Permohonan / Identitas', required: true }],
    estimated_duration: '2 Hari Kerja',
    service_hours: 'Senin–Jumat, 07.30–15.00',
    delivery_mode: 'both',
    requires_verification_secret: true,
    is_active: true,
    is_published: true,
    sort_order: 0,
});

const openNewCatalog = () => {
    editingServiceId.value = null;
    catalogForm.reset();
    catalogForm.clearErrors();
    showCatalogModal.value = true;
};

const openEditCatalog = (svc: PtspCatalogItem) => {
    editingServiceId.value = svc.id;
    catalogForm.code = svc.code;
    catalogForm.name = svc.name;
    catalogForm.category = svc.category;
    catalogForm.description = svc.description ?? '';
    catalogForm.requirements_text = (svc.requirements ?? []).join('\n');
    catalogForm.required_documents = (svc.required_documents?.length ? svc.required_documents : []).map((d) => ({ ...d }));
    catalogForm.estimated_duration = svc.estimated_duration;
    catalogForm.service_hours = svc.service_hours ?? '';
    catalogForm.delivery_mode = svc.delivery_mode;
    catalogForm.requires_verification_secret = svc.requires_verification_secret;
    catalogForm.is_active = svc.is_active;
    catalogForm.is_published = svc.is_published;
    catalogForm.sort_order = svc.sort_order;
    showCatalogModal.value = true;
};

const addRequiredDocRow = () => {
    catalogForm.required_documents.push({
        key: `dokumen_${catalogForm.required_documents.length + 1}`,
        label: '',
        required: true,
    });
};

const removeRequiredDocRow = (index: number) => {
    catalogForm.required_documents.splice(index, 1);
};

const submitCatalog = () => {
    const payload = {
        ...catalogForm.data(),
        requirements: catalogForm.requirements_text.split('\n').map((s) => s.trim()).filter(Boolean),
    };

    if (editingServiceId.value) {
        catalogForm.transform(() => payload).put(`/administrasi/ptsp/katalog/${editingServiceId.value}`, {
            preserveScroll: true,
            onSuccess: () => { showCatalogModal.value = false; },
        });
    } else {
        catalogForm.transform(() => payload).post('/administrasi/ptsp/katalog', {
            preserveScroll: true,
            onSuccess: () => { showCatalogModal.value = false; },
        });
    }
};

const toggleServiceActive = (svc: PtspCatalogItem) => {
    router.patch(`/administrasi/ptsp/katalog/${svc.id}/status`, {
        is_active: !svc.is_active,
    }, { preserveScroll: true });
};

const formatDate = (iso?: string | null) => iso ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso)) : '-';
const catalogCounts = computed(() => ({
    total: props.catalog.length,
    active: props.catalog.filter((c) => c.is_active).length,
}));
</script>

<template>
    <Head title="Pelayanan PTSP — Tata Usaha" />
    <AppLayout title="Pelayanan PTSP">
        <div class="ptsp-workspace">
            <header class="workspace-head">
                <div>
                    <h1>Pelayanan Terpadu Satu Pintu (PTSP)</h1>
                    <p>Kelola antrean permohonan layanan masuk, disposisi, revisi, penerbitan hasil, dan katalog layanan PTSP.</p>
                </div>
                <div class="head-actions">
                    <AppButton variant="secondary" @click="activeTab = activeTab === 'queue' ? 'catalog' : 'queue'">
                        {{ activeTab === 'queue' ? `Kelola Katalog (${catalogCounts.total})` : 'Kembali ke Antrean' }}
                    </AppButton>
                    <AppButton v-if="activeTab === 'catalog'" @click="openNewCatalog">Tambah Layanan PTSP</AppButton>
                </div>
            </header>

            <div class="kpi-grid">
                <MetricSummaryCard label="Menunggu Verifikasi" :value="summary.submitted" tone="blue" icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2" />
                <MetricSummaryCard label="Sedang Diproses" :value="summary.in_review" tone="violet" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                <MetricSummaryCard label="Menunggu Perbaikan" :value="summary.needs_revision" tone="amber" icon="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                <MetricSummaryCard label="Siap Diambil" :value="summary.approved" tone="green" icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                <MetricSummaryCard label="Selesai" :value="summary.completed" tone="green" icon="M5 13l4 4L19 7" />
            </div>

            <section v-if="activeTab === 'queue'" class="panel-card">
                <form class="filter-bar" @submit.prevent="applyFilters">
                    <input v-model="filterState.search" type="search" placeholder="Cari kode tiket atau nama pemohon..." aria-label="Cari permohonan" />
                    <select v-model="filterState.service_id" aria-label="Filter layanan">
                        <option value="">Semua Layanan</option>
                        <option v-for="svc in catalog" :key="svc.id" :value="svc.id">{{ svc.name }}</option>
                    </select>
                    <select v-model="filterState.status" aria-label="Filter status">
                        <option value="">Semua Status</option>
                        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <input v-model="filterState.date_from" type="date" aria-label="Tanggal mulai" />
                    <input v-model="filterState.date_to" type="date" aria-label="Tanggal akhir" />
                    <AppButton type="submit" variant="secondary">Terapkan</AppButton>
                </form>

                <div v-if="requests.length" class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tiket &amp; Tanggal</th>
                                <th>Layanan</th>
                                <th>Pemohon</th>
                                <th>Keperluan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in requests" :key="row.id">
                                <td>
                                    <strong>{{ row.ticket_code }}</strong>
                                    <div class="sub-text">{{ formatDate(row.submitted_at) }}</div>
                                </td>
                                <td>
                                    <span>{{ row.service_snapshot.name }}</span>
                                    <div v-if="row.disposition_to" class="sub-text">Disposisi: {{ row.disposition_to }}</div>
                                </td>
                                <td>
                                    <strong>{{ row.applicant_name }}</strong>
                                    <div class="sub-text">{{ row.applicant_organization || row.applicant_phone || '-' }}</div>
                                </td>
                                <td>{{ row.purpose }}</td>
                                <td><StatusBadge :status="row.status" size="sm" /></td>
                                <td>
                                    <div class="row-actions">
                                        <AppButton v-if="row.status === 'submitted'" size="sm" variant="secondary" @click="startReview(row)">Proses</AppButton>
                                        <AppButton size="sm" variant="primary" @click="openRequestDetail(row)">Detail &amp; Aksi</AppButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <StateFeedback v-else state="empty" title="Belum ada permohonan PTSP" description="Tidak ada permohonan yang cocok dengan filter saat ini." />

                <div v-if="pagination.last > 1" class="pager">
                    <span>Halaman {{ pagination.current }} dari {{ pagination.last }} ({{ pagination.total }} data)</span>
                    <div class="pager-links">
                        <Link v-if="pagination.previous" :href="pagination.previous" class="ui-button ui-button--secondary ui-button--sm">Sebelumnya</Link>
                        <Link v-if="pagination.next" :href="pagination.next" class="ui-button ui-button--secondary ui-button--sm">Berikutnya</Link>
                    </div>
                </div>
            </section>

            <section v-else class="panel-card">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Layanan</th>
                                <th>Kategori</th>
                                <th>Estimasi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="svc in catalog" :key="svc.id">
                                <td><strong>{{ svc.code }}</strong></td>
                                <td>
                                    <strong>{{ svc.name }}</strong>
                                    <div class="sub-text">{{ svc.required_documents?.length || 0 }} syarat dokumen</div>
                                </td>
                                <td>{{ svc.category }}</td>
                                <td>{{ svc.estimated_duration }}</td>
                                <td><StatusBadge :status="svc.is_active ? 'active' : 'inactive'" :label="svc.is_active ? 'Aktif' : 'Tutup'" size="sm" /></td>
                                <td>
                                    <div class="row-actions">
                                        <AppButton size="sm" variant="secondary" @click="openEditCatalog(svc)">Ubah</AppButton>
                                        <AppButton size="sm" variant="secondary" @click="toggleServiceActive(svc)">
                                            {{ svc.is_active ? 'Tutup' : 'Aktifkan' }}
                                        </AppButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Detail & Action Modal -->
            <div v-if="selectedRequest" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="ptsp-detail-title">
                <div class="modal-card">
                    <header class="modal-head">
                        <div>
                            <h2 id="ptsp-detail-title">{{ selectedRequest.ticket_code }} — {{ selectedRequest.service_snapshot.name }}</h2>
                            <p class="sub-text">Pemohon: {{ selectedRequest.applicant_name }} ({{ selectedRequest.applicant_phone || selectedRequest.applicant_email || '-' }})</p>
                        </div>
                        <button type="button" class="close-btn" @click="selectedRequest = null" aria-label="Tutup">×</button>
                    </header>

                    <div class="detail-body">
                        <div class="detail-meta">
                            <p><strong>Keperluan:</strong> {{ selectedRequest.purpose }}</p>
                            <p v-if="selectedRequest.notes"><strong>Catatan:</strong> {{ selectedRequest.notes }}</p>
                            <p v-if="selectedRequest.disposition_to"><strong>Disposisi:</strong> {{ selectedRequest.disposition_to }} <span v-if="selectedRequest.disposition_note">({{ selectedRequest.disposition_note }})</span></p>
                            <p v-if="selectedRequest.physical_pickup_name"><strong>Pengambilan Fisik:</strong> {{ selectedRequest.physical_pickup_name }} ({{ formatDate(selectedRequest.physical_pickup_at) }})</p>
                        </div>

                        <div v-if="selectedRequest.documents.length" class="doc-section">
                            <strong>Lampiran &amp; Dokumen:</strong>
                            <ul class="doc-list">
                                <li v-for="doc in selectedRequest.documents" :key="doc.id">
                                    <span>{{ doc.original_name }} ({{ doc.document_kind }})</span>
                                    <a :href="`/administrasi/ptsp/permohonan/${selectedRequest.id}/dokumen/${doc.id}`" class="ui-button ui-button--secondary ui-button--sm">Unduh</a>
                                </li>
                            </ul>
                        </div>

                        <div class="action-switches">
                            <AppButton size="sm" variant="secondary" @click="actionMode = 'disposition'">Disposisi</AppButton>
                            <AppButton size="sm" variant="secondary" @click="actionMode = 'revision'">Minta Perbaikan</AppButton>
                            <AppButton size="sm" variant="danger" @click="actionMode = 'reject'">Tolak</AppButton>
                            <AppButton size="sm" variant="primary" @click="actionMode = 'issue'">Terbitkan Hasil</AppButton>
                            <AppButton size="sm" variant="secondary" @click="actionMode = 'pickup'">Catat Ambil Fisik</AppButton>
                        </div>

                        <form v-if="actionMode !== 'none'" class="action-form" @submit.prevent="submitAction">
                            <template v-if="actionMode === 'revision'">
                                <FormField id="act-rev" label="Catatan Perbaikan untuk Pemohon" required :error="actionForm.errors.reason" v-slot="{ inputId }">
                                    <textarea :id="inputId" v-model="actionForm.reason" rows="3" required />
                                </FormField>
                            </template>

                            <template v-else-if="actionMode === 'reject'">
                                <FormField id="act-rej" label="Alasan Penolakan" required :error="actionForm.errors.reason" v-slot="{ inputId }">
                                    <textarea :id="inputId" v-model="actionForm.reason" rows="3" required />
                                </FormField>
                            </template>

                            <template v-else-if="actionMode === 'disposition'">
                                <FormField id="act-disp-to" label="Disposisi Kepada (Unit / Pegawai)" required :error="actionForm.errors.disposition_to" v-slot="{ inputId }">
                                    <input :id="inputId" v-model="actionForm.disposition_to" type="text" required placeholder="Contoh: Wakamad Kesiswaan / Staf TU" />
                                </FormField>
                                <FormField id="act-disp-note" label="Catatan Disposisi" :error="actionForm.errors.disposition_note" v-slot="{ inputId }">
                                    <textarea :id="inputId" v-model="actionForm.disposition_note" rows="2" />
                                </FormField>
                            </template>

                            <template v-else-if="actionMode === 'issue'">
                                <FormField id="act-deliv" label="Metode Penyerahan Hasil" required v-slot="{ inputId }">
                                    <select :id="inputId" v-model="actionForm.delivery_mode">
                                        <option value="digital">Digital (Langsung Unduh &amp; Selesai)</option>
                                        <option value="physical">Fisik (Siap Diambil di Loket)</option>
                                        <option value="both">Digital &amp; Fisik</option>
                                    </select>
                                </FormField>
                                <FormField id="act-sum" label="Ringkasan / Keterangan Hasil" v-slot="{ inputId }">
                                    <textarea :id="inputId" v-model="actionForm.result_summary" rows="2" />
                                </FormField>
                                <FormField id="act-signer" label="Nama Pejabat Penandatangan" v-slot="{ inputId }">
                                    <input :id="inputId" v-model="actionForm.signatory_name" type="text" />
                                </FormField>
                                <FormField id="act-file" label="Unggah File Hasil (Opsional; kosongkan untuk PDF standar)" v-slot="{ inputId }">
                                    <input :id="inputId" type="file" accept=".pdf,.jpg,.jpeg,.png" @change="actionForm.result_file = ($event.target as HTMLInputElement).files?.[0] ?? null" />
                                </FormField>
                            </template>

                            <template v-else-if="actionMode === 'pickup'">
                                <FormField id="act-pick-name" label="Nama Pengambil Dokumen Fisik" required :error="actionForm.errors.physical_pickup_name" v-slot="{ inputId }">
                                    <input :id="inputId" v-model="actionForm.physical_pickup_name" type="text" required />
                                </FormField>
                                <FormField id="act-pick-note" label="Catatan Serah Terima" v-slot="{ inputId }">
                                    <textarea :id="inputId" v-model="actionForm.physical_pickup_notes" rows="2" />
                                </FormField>
                            </template>

                            <div class="modal-footer">
                                <AppButton type="submit" :disabled="actionForm.processing">Simpan Keputusan</AppButton>
                            </div>
                        </form>

                        <div v-if="selectedRequest.transitions.length" class="history-section">
                            <strong>Histori Status:</strong>
                            <ul>
                                <li v-for="tr in selectedRequest.transitions" :key="tr.id">
                                    <StatusBadge :status="tr.public_status" size="sm" />
                                    <span>{{ formatDate(tr.created_at) }}</span>
                                    <span v-if="tr.actor_role">({{ tr.actor_role }})</span>
                                    <span v-if="tr.reason"> — {{ tr.reason }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Catalog Modal -->
            <div v-if="showCatalogModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="cat-modal-title">
                <div class="modal-card">
                    <header class="modal-head">
                        <h2 id="cat-modal-title">{{ editingServiceId ? 'Ubah Layanan PTSP' : 'Tambah Layanan PTSP' }}</h2>
                        <button type="button" class="close-btn" @click="showCatalogModal = false" aria-label="Tutup">×</button>
                    </header>

                    <form class="action-form" @submit.prevent="submitCatalog">
                        <div class="two-col">
                            <FormField id="cat-code" label="Kode Layanan" required :error="catalogForm.errors.code" v-slot="{ inputId }">
                                <input :id="inputId" v-model="catalogForm.code" type="text" required placeholder="SK-AKTIF" />
                            </FormField>
                            <FormField id="cat-cat" label="Kategori" required v-slot="{ inputId }">
                                <select :id="inputId" v-model="catalogForm.category">
                                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                                </select>
                            </FormField>
                        </div>

                        <FormField id="cat-name" label="Nama Layanan" required :error="catalogForm.errors.name" v-slot="{ inputId }">
                            <input :id="inputId" v-model="catalogForm.name" type="text" required />
                        </FormField>

                        <FormField id="cat-desc" label="Deskripsi Singkat" v-slot="{ inputId }">
                            <textarea :id="inputId" v-model="catalogForm.description" rows="2" />
                        </FormField>

                        <div class="two-col">
                            <FormField id="cat-est" label="Estimasi Selesai" required v-slot="{ inputId }">
                                <input :id="inputId" v-model="catalogForm.estimated_duration" type="text" required />
                            </FormField>
                            <FormField id="cat-hours" label="Jam Layanan" v-slot="{ inputId }">
                                <input :id="inputId" v-model="catalogForm.service_hours" type="text" />
                            </FormField>
                        </div>

                        <FormField id="cat-reqs" label="Daftar Persyaratan (1 baris per item)" v-slot="{ inputId }">
                            <textarea :id="inputId" v-model="catalogForm.requirements_text" rows="3" placeholder="Kartu pelajar&#10;Surat pengantar wali" />
                        </FormField>

                        <div class="doc-rules">
                            <div class="doc-rules__head">
                                <strong>Syarat Unggah Berkas</strong>
                                <AppButton type="button" size="sm" variant="secondary" @click="addRequiredDocRow">+ Tambah Syarat Berkas</AppButton>
                            </div>
                            <div v-for="(doc, idx) in catalogForm.required_documents" :key="idx" class="doc-rule-row">
                                <input v-model="doc.label" type="text" placeholder="Nama berkas wajib" />
                                <label class="inline-check"><input v-model="doc.required" type="checkbox" /> Wajib</label>
                                <button type="button" class="link-danger" @click="removeRequiredDocRow(idx)">Hapus</button>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <AppButton type="submit" :disabled="catalogForm.processing">Simpan Layanan</AppButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.ptsp-workspace { display: grid; gap: 1.25rem; }
.workspace-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1rem; }
.workspace-head h1 { margin: 0; font-size: 1.5rem; }
.workspace-head p { margin: .25rem 0 0; color: var(--ink-soft, #536174); }
.head-actions { display: flex; flex-wrap: wrap; gap: .6rem; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .85rem; }
.panel-card { background: var(--surface, #fff); border: 1px solid var(--line, #dee4ee); border-radius: 14px; padding: 1.15rem; display: grid; gap: 1rem; }
.filter-bar { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }
.filter-bar input, .filter-bar select { min-height: 40px; padding: .4rem .75rem; border: 1px solid var(--line-strong, #c8d1df); border-radius: 8px; font: inherit; background: var(--surface, #fff); }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.data-table th, .data-table td { padding: .7rem .65rem; border-bottom: 1px solid var(--line, #dee4ee); text-align: left; vertical-align: top; }
.sub-text { font-size: .78rem; color: var(--ink-soft, #536174); margin-top: .15rem; }
.row-actions { display: flex; flex-wrap: wrap; gap: .4rem; }
.pager { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .75rem; font-size: .85rem; }
.pager-links { display: flex; gap: .5rem; }
.modal-backdrop { position: fixed; inset: 0; background: rgb(15 23 42 / 55%); display: grid; place-items: center; padding: 1rem; z-index: 60; }
.modal-card { width: min(740px, 100%); max-height: min(90dvh, 840px); overflow-y: auto; background: var(--surface, #fff); border-radius: 16px; padding: 1.35rem; display: grid; gap: 1rem; }
.modal-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; }
.modal-head h2 { margin: 0; font-size: 1.15rem; }
.close-btn { min-width: 40px; min-height: 40px; border: 0; background: transparent; font-size: 1.5rem; cursor: pointer; line-height: 1; }
.detail-body { display: grid; gap: 1rem; }
.detail-meta p { margin: .2rem 0; }
.doc-list, .history-section ul { margin: .4rem 0 0; padding-left: 1.1rem; display: grid; gap: .4rem; }
.doc-list li { display: flex; justify-content: space-between; align-items: center; gap: .5rem; }
.action-switches { display: flex; flex-wrap: wrap; gap: .5rem; border-top: 1px solid var(--line, #dee4ee); padding-top: .85rem; }
.action-form { display: grid; gap: .85rem; }
.two-col { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: .85rem; }
.doc-rules { display: grid; gap: .5rem; }
.doc-rules__head { display: flex; justify-content: space-between; align-items: center; }
.doc-rule-row { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
.doc-rule-row input[type='text'] { flex: 1 1 220px; min-height: 38px; padding: .35rem .65rem; border: 1px solid var(--line-strong, #c8d1df); border-radius: 8px; }
.inline-check { display: inline-flex; align-items: center; gap: .35rem; font-size: .85rem; }
.link-danger { border: 0; background: none; color: #b13f38; font-weight: 600; cursor: pointer; }
.modal-footer { display: flex; justify-content: flex-end; gap: .5rem; }
</style>
