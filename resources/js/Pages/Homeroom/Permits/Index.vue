<script setup lang="ts">
import { useConfirmedDialogClose } from '@/composables/useUnsavedDialog';
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface PermitItem {
    id: number;
    student_id: number;
    student_name: string;
    student_nis: string;
    class_name: string;
    ticket_code?: string | null;
    type_label: string;
    title: string;
    reason: string;
    start_date: string;
    end_date: string;
    attachment_document_id?: number | null;
    status: string;
    status_label: string;
    decision_note?: string | null;
    correction_reason?: string | null;
}

const props = defineProps<{
    permits: PermitItem[];
    students: { student_id: number; name: string; nis: string; class_group_name?: string | null }[];
    filters: { status: string };
    issuedToken?: { token: string; expires_at: string; student_id: number } | null;
}>();

const statusFilter = ref(props.filters.status || '');
const selectedPermit = ref<PermitItem | null>(null);
const modalMode = ref<'decide' | 'correct'>('decide');

const tokenForm = useForm({
    student_id: props.students[0]?.student_id ?? ('' as string | number),
    expires_in_hours: 48,
});

const decisionForm = useForm({
    decision: 'approve',
    decision_note: '',
});

const correctionForm = useForm({
    new_status: 'rejected',
    correction_reason: '',
});

const applyFilter = () => {
    router.get('/wali-kelas/izin', { status: statusFilter.value || undefined }, { preserveState: true, replace: true });
};

const issueToken = () => {
    tokenForm.post('/wali-kelas/izin/token', { preserveScroll: true });
};

const openModal = (item: PermitItem, mode: 'decide' | 'correct') => {
    selectedPermit.value = item;
    modalMode.value = mode;
    decisionForm.reset();
    correctionForm.reset();
};

const submitDecision = () => {
    if (!selectedPermit.value) return;
    decisionForm.post(`/wali-kelas/izin/${selectedPermit.value.id}/putuskan`, {
        preserveScroll: true,
        onSuccess: () => { selectedPermit.value = null; },
    });
};

const submitCorrection = () => {
    if (!selectedPermit.value) return;
    correctionForm.post(`/wali-kelas/izin/${selectedPermit.value.id}/koreksi`, {
        preserveScroll: true,
        onSuccess: () => { selectedPermit.value = null; },
    });
};

const showPermitTokenEditor = ref(false);
const requestPermitTokenEditorClose = useConfirmedDialogClose(tokenForm, () => { showPermitTokenEditor.value = false; tokenForm.reset(); tokenForm.clearErrors(); });
watch(() => tokenForm.wasSuccessful, (success) => { if (success) tokenForm.defaults(); });
</script>

<template>
    <Head title="Verifikasi Izin Siswa — Wali Kelas" />
    <AppLayout title="Verifikasi Izin Siswa">
        <div class="permits-workspace">
            <header class="workspace-head">
                <div>
                    <h1>Antrean Verifikasi Izin &amp; Dispensasi</h1>
                    <p>Tinjau pengajuan izin wali/siswa, kembalikan untuk perbaikan, putuskan dengan dampak otomatis ke presensi, atau terbitkan token sekali pakai.</p>
                </div>
            </header>

            <div v-if="issuedToken" class="token-banner" role="status">
                <strong>Token Izin Sekali Pakai Diterbitkan:</strong>
                <span class="token-code">{{ issuedToken.token }}</span>
                <small>Berlaku hingga {{ issuedToken.expires_at }}</small>
            </div>

            <section class="panel-card">
                <h2>Terbitkan Token Izin Sekali Pakai</h2>
                <AppButton variant="primary" type="button" @click="showPermitTokenEditor = true">Terbitkan token dispensasi</AppButton>
<Teleport to="body"><div v-if="showPermitTokenEditor" class="admin-form-overlay" role="dialog" aria-modal="true" aria-label="Terbitkan token dispensasi" @click.self="requestPermitTokenEditorClose" @keydown.esc.stop.prevent="requestPermitTokenEditorClose"><div class="admin-form-dialog"><header class="admin-form-header"><h2>Terbitkan token dispensasi</h2><AppButton type="button" icon="close" icon-only aria-label="Tutup terbitkan token dispensasi" title="Tutup formulir" :disabled="tokenForm.processing" @click="requestPermitTokenEditorClose" /></header>
<form class="inline-form" @submit.prevent="issueToken">
                    <select v-model="tokenForm.student_id" required aria-label="Pilih siswa penerima token">
                        <option v-for="s in students" :key="s.student_id" :value="s.student_id">
                            {{ s.name }} ({{ s.nis }}) — {{ s.class_group_name || '-' }}
                        </option>
                    </select>
                    <input v-model="tokenForm.expires_in_hours" type="number" min="1" max="168" aria-label="Masa berlaku jam" />
                    <AppButton type="submit" :disabled="tokenForm.processing">Terbitkan Token</AppButton>
                </form>
</div></div></Teleport>
            </section>

            <section class="panel-card">
                <div class="filter-bar">
                    <select v-model="statusFilter" aria-label="Filter status izin" @change="applyFilter">
                        <option value="">Semua Status</option>
                        <option value="pending">Menunggu Verifikasi</option>
                        <option value="needs_revision">Perlu Perbaikan</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>

                <div v-if="permits.length" class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Siswa &amp; Rombel</th>
                                <th>Izin &amp; Rentang</th>
                                <th>Alasan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in permits" :key="row.id">
                                <td>
                                    <strong>{{ row.student_name }}</strong>
                                    <div class="sub-text">{{ row.class_name }} · {{ row.ticket_code || '-' }}</div>
                                </td>
                                <td>
                                    <strong>{{ row.title }}</strong>
                                    <div class="sub-text">{{ row.type_label }} · {{ row.start_date }} s.d. {{ row.end_date }}</div>
                                </td>
                                <td>
                                    <span>{{ row.reason }}</span>
                                    <div v-if="row.attachment_document_id" class="sub-text">
                                        <a :href="`/layanan/izin-siswa/${row.id}/lampiran`" target="_blank" rel="noopener">Lihat Lampiran</a>
                                    </div>
                                </td>
                                <td><StatusBadge :status="row.status" :label="row.status_label" size="sm" /></td>
                                <td>
                                    <div class="row-actions">
                                        <AppButton v-if="row.status === 'pending' || row.status === 'needs_revision'" size="sm" @click="openModal(row, 'decide')">Putuskan</AppButton>
                                        <AppButton v-else size="sm" variant="secondary" @click="openModal(row, 'correct')">Koreksi</AppButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <StateFeedback v-else state="empty" title="Belum ada permohonan izin" description="Permohonan izin yang diajukan wali atau siswa akan muncul di sini." />
            </section>

            <div v-if="selectedPermit" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="perm-modal-title" @click.self="selectedPermit = null" @keydown.esc="selectedPermit = null">
                <div class="modal-card">
                    <header class="modal-head">
                        <h2 id="perm-modal-title">{{ modalMode === 'decide' ? 'Putuskan Permohonan Izin' : 'Koreksi Keputusan Izin' }}</h2>
                        <button type="button" class="close-btn" @click="selectedPermit = null" aria-label="Tutup">×</button>
                    </header>

                    <form v-if="modalMode === 'decide'" class="modal-form" @submit.prevent="submitDecision">
                        <FormField id="dec-choice" label="Keputusan" required v-slot="{ inputId }">
                            <select :id="inputId" v-model="decisionForm.decision">
                                <option value="approve">Setujui &amp; Perbarui Presensi</option>
                                <option value="request_revision">Kembalikan untuk Perbaikan</option>
                                <option value="reject">Tolak Beralasan</option>
                            </select>
                        </FormField>
                        <FormField id="dec-note" label="Catatan / Alasan Keputusan" :error="decisionForm.errors.decision_note" v-slot="{ inputId }">
                            <textarea :id="inputId" v-model="decisionForm.decision_note" rows="3" />
                        </FormField>
                        <div class="modal-footer">
                            <AppButton type="submit" :disabled="decisionForm.processing">Simpan Keputusan</AppButton>
                        </div>
                    </form>

                    <form v-else class="modal-form" @submit.prevent="submitCorrection">
                        <FormField id="cor-status" label="Status Baru" required v-slot="{ inputId }">
                            <select :id="inputId" v-model="correctionForm.new_status">
                                <option value="approved">Disetujui</option>
                                <option value="rejected">Ditolak</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </FormField>
                        <FormField id="cor-reason" label="Alasan Koreksi (Audit)" required :error="correctionForm.errors.correction_reason" v-slot="{ inputId }">
                            <textarea :id="inputId" v-model="correctionForm.correction_reason" rows="3" required />
                        </FormField>
                        <div class="modal-footer">
                            <AppButton type="submit" :disabled="correctionForm.processing">Simpan Koreksi</AppButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.permits-workspace { display: grid; gap: 1.25rem; }
.workspace-head h1 { margin: 0; font-size: 1.5rem; }
.workspace-head p { margin: .25rem 0 0; color: var(--ink-soft, #536174); }
.token-banner { padding: .9rem 1rem; border-radius: 12px; background: #eef4ff; border: 1px solid #bfdbfe; color: #1e3a8a; display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
.token-code { font-size: 1.15rem; font-weight: 800; letter-spacing: .08em; }
.panel-card { background: var(--surface, #fff); border: 1px solid var(--line, #dee4ee); border-radius: 14px; padding: 1.15rem; display: grid; gap: 1rem; }
.panel-card h2 { margin: 0; font-size: 1.05rem; }
.inline-form, .filter-bar { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }
.inline-form select, .inline-form input, .filter-bar select { min-height: 40px; padding: .4rem .75rem; border: 1px solid var(--line-strong, #c8d1df); border-radius: 8px; font: inherit; background: var(--surface, #fff); }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.data-table th, .data-table td { padding: .7rem .65rem; border-bottom: 1px solid var(--line, #dee4ee); text-align: left; vertical-align: top; }
.data-table th { text-align: center; }
.sub-text { font-size: .78rem; color: var(--ink-soft, #536174); margin-top: .15rem; }
.row-actions { display: flex; gap: .4rem; }
.modal-backdrop { position: fixed; inset: 0; background: rgb(15 23 42 / 55%); display: grid; place-items: center; padding: 1rem; z-index: 60; }
.modal-card { width: min(560px, 100%); background: var(--surface, #fff); border-radius: 16px; padding: 1.35rem; display: grid; gap: 1rem; }
.modal-head { display: flex; justify-content: space-between; align-items: center; }
.modal-head h2 { margin: 0; font-size: 1.15rem; }
.close-btn { min-width: 40px; min-height: 40px; border: 0; background: transparent; font-size: 1.5rem; cursor: pointer; }
.modal-form { display: grid; gap: .85rem; }
.modal-footer { display: flex; justify-content: flex-end; }
</style>
