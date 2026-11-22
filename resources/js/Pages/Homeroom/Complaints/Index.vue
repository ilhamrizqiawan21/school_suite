<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface ComplaintRow {
    id: number;
    report_code: string;
    category: string;
    category_label: string;
    is_anonymous: boolean;
    reporter_label: string;
    reporter_contact?: string | null;
    title: string;
    description: string;
    location?: string | null;
    evidence_document_ids: number[];
    resolution_document_ids: number[];
    assigned_to_user_id?: number | null;
    status: string;
    sla_due_at?: string | null;
    task_force_reply?: string | null;
    resolution_summary?: string | null;
    moderated_public_summary?: string | null;
    publish_to_transparency: boolean;
}

const props = defineProps<{
    reports: ComplaintRow[];
    assignees: { id: number; name: string }[];
    categories: { value: string; label: string }[];
    filters: { status: string; category: string };
}>();

const statusFilter = ref(props.filters.status || '');
const categoryFilter = ref(props.filters.category || '');
const activeReport = ref<ComplaintRow | null>(null);

const handleForm = useForm({
    status: 'in_review',
    assigned_to_user_id: '' as string | number,
    task_force_reply: '',
    resolution_summary: '',
    moderated_public_summary: '',
    publish_to_transparency: false,
    resolution_files: [] as File[],
});

const applyFilters = () => {
    router.get('/satgas/aduan', {
        status: statusFilter.value || undefined,
        category: categoryFilter.value || undefined,
    }, { preserveState: true, replace: true });
};

const openHandleModal = (row: ComplaintRow) => {
    activeReport.value = row;
    handleForm.status = row.status;
    handleForm.assigned_to_user_id = row.assigned_to_user_id ?? '';
    handleForm.task_force_reply = row.task_force_reply ?? '';
    handleForm.resolution_summary = row.resolution_summary ?? '';
    handleForm.moderated_public_summary = row.moderated_public_summary ?? '';
    handleForm.publish_to_transparency = row.publish_to_transparency;
    handleForm.resolution_files = [];
};

const pickResolutionFiles = (event: Event) => {
    const files = (event.target as HTMLInputElement).files;
    handleForm.resolution_files = files ? Array.from(files) : [];
};

const submitHandle = () => {
    if (!activeReport.value) return;
    handleForm.post(`/satgas/aduan/${activeReport.value.id}/tangani`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { activeReport.value = null; },
    });
};

const statusTone = (status: string): 'neutral' | 'info' | 'warning' | 'success' | 'danger' => {
    if (status === 'completed') return 'success';
    if (status === 'needs_revision') return 'warning';
    if (status === 'rejected') return 'danger';
    return 'info';
};
</script>

<template>
    <AppLayout title="Antrean Satgas Aduan">
        <Head title="Antrean Satgas Aduan" />

        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Antrean Terbatas Satgas Pengaduan</h1>
                    <p class="text-sm text-slate-600">Telaah aduan bernama/anonim, alokasi petugas, unggah bukti selesai privat, balasan tiket, serta ringkasan publik termoderasi.</p>
                </div>
                <div class="flex gap-2">
                    <select v-model="categoryFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="applyFilters">
                        <option value="">Semua Kategori</option>
                        <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                    </select>
                    <select v-model="statusFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="applyFilters">
                        <option value="">Semua Status</option>
                        <option value="submitted">Diajukan</option>
                        <option value="in_review">Ditelaah Satgas</option>
                        <option value="needs_revision">Butuh Info Tambahan</option>
                        <option value="completed">Selesai</option>
                        <option value="rejected">Ditutup / Ditolak</option>
                    </select>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <StateFeedback
                    v-if="reports.length === 0"
                    state="empty"
                    title="Belum ada laporan pengaduan"
                    description="Laporan yang masuk melalui Siap Lapor akan tampil di antrean Satgas."
                />

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-center text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2.5 text-center">Kode &amp; Pelapor</th>
                                <th class="px-3 py-2.5 text-center">Laporan</th>
                                <th class="px-3 py-2.5 text-center">Bukti</th>
                                <th class="px-3 py-2.5 text-center">Status</th>
                                <th class="px-3 py-2.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="r in reports" :key="r.id">
                                <td class="px-3 py-3">
                                    <p class="font-mono text-xs font-bold">{{ r.report_code }}</p>
                                    <p class="text-xs font-medium text-slate-800">{{ r.reporter_label }}</p>
                                    <p class="text-xs text-slate-500">{{ r.category_label }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-medium text-slate-900">{{ r.title }}</p>
                                    <p class="text-xs text-slate-600 line-clamp-2">{{ r.description }}</p>
                                </td>
                                <td class="px-3 py-3 text-xs space-x-2">
                                    <a
                                        v-for="(docId, idx) in r.evidence_document_ids"
                                        :key="docId"
                                        :href="`/satgas/aduan/${r.id}/eviden/${docId}`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-indigo-700 underline"
                                    >
                                        Bukti #{{ idx + 1 }}
                                    </a>
                                </td>
                                <td class="px-3 py-3">
                                    <StatusBadge :status="r.status" :tone="statusTone(r.status)" size="sm" />
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <AppButton type="button" variant="secondary" @click="openHandleModal(r)">Tangani</AppButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            v-if="activeReport"
            role="dialog"
            aria-modal="true"
            tabindex="-1"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="activeReport = null"
            @keydown.esc="activeReport = null"
        >
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl space-y-3">
                <h3 class="text-base font-bold">Tangani Aduan {{ activeReport.report_code }}</h3>
                <div class="grid gap-3 grid-cols-2">
                    <FormField id="satgas-status" label="Status" required>
                        <select id="satgas-status" v-model="handleForm.status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="in_review">Ditelaah Satgas</option>
                            <option value="needs_revision">Butuh Info Pelapor</option>
                            <option value="completed">Selesai Ditindaklanjuti</option>
                            <option value="rejected">Ditolak / Tidak Terbukti</option>
                        </select>
                    </FormField>
                    <FormField id="satgas-assignee" label="Penanggung Jawab">
                        <select id="satgas-assignee" v-model="handleForm.assigned_to_user_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Otomatis Anda</option>
                            <option v-for="u in assignees" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </FormField>
                </div>
                <FormField id="satgas-reply" label="Balasan untuk Tiket Pelapor">
                    <textarea id="satgas-reply" v-model="handleForm.task_force_reply" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </FormField>
                <FormField id="satgas-res-summary" label="Catatan Tindak Lanjut Internal">
                    <textarea id="satgas-res-summary" v-model="handleForm.resolution_summary" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </FormField>
                <FormField id="satgas-public-summary" label="Ringkasan Transparansi Publik (Bebas PII)">
                    <textarea id="satgas-public-summary" v-model="handleForm.moderated_public_summary" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </FormField>
                <label class="flex items-center gap-2 text-xs font-medium text-slate-700">
                    <input v-model="handleForm.publish_to_transparency" type="checkbox" />
                    Tampilkan ringkasan bebas PII pada halaman Transparansi Publik
                </label>
                <FormField id="satgas-res-files" label="Lampiran Bukti Penyelesaian (Privat)">
                    <input id="satgas-res-files" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="text-xs" @change="pickResolutionFiles" />
                </FormField>
                <div class="flex justify-end gap-2 pt-2">
                    <AppButton type="button" variant="secondary" @click="activeReport = null">Batal</AppButton>
                    <AppButton type="button" :disabled="handleForm.processing" @click="submitHandle">Simpan Penanganan</AppButton>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
