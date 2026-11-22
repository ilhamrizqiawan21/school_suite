<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface LegalRow {
    id: number;
    legalization_code: string;
    applicant_name: string;
    graduation_year: number;
    document_type: string;
    certificate_number: string;
    copy_count: number;
    delivery_method: string;
    proxy_name?: string | null;
    status: string;
    original_verified_at?: string | null;
    legalization_document_id?: number | null;
    picked_up_at?: string | null;
    picked_up_by_name?: string | null;
}

const props = defineProps<{
    canProcess: boolean;
    canPublish: boolean;
    requests: LegalRow[];
    filters: { status: string };
}>();

const statusFilter = ref(props.filters.status || '');
const actionForm = useForm({ note: '', action: 'reject', reason: '', picked_up_by_name: '', relation: 'Pemohon Langsung' });
const activeModal = ref<{ type: 'pickup' | 'decide'; row: LegalRow } | null>(null);

const openPickupModal = (row: LegalRow) => {
    const defaultName = row.delivery_method === 'pickup_proxy' ? (row.proxy_name || row.applicant_name) : row.applicant_name;
    actionForm.picked_up_by_name = defaultName;
    actionForm.relation = row.delivery_method === 'pickup_proxy' ? 'Penerima Kuasa' : 'Pemohon Langsung';
    activeModal.value = { type: 'pickup', row };
};

const openDecideModal = (row: LegalRow) => {
    actionForm.action = 'revision';
    actionForm.reason = '';
    activeModal.value = { type: 'decide', row };
};

const submitPickup = () => {
    if (!activeModal.value || activeModal.value.type !== 'pickup') return;
    actionForm.post(`/administrasi/legalisir/${activeModal.value.row.id}/ambil`, {
        preserveScroll: true,
        onSuccess: () => {
            activeModal.value = null;
        },
    });
};

const submitDecision = () => {
    if (!activeModal.value || activeModal.value.type !== 'decide') return;
    actionForm.post(`/administrasi/legalisir/${activeModal.value.row.id}/keputusan`, {
        preserveScroll: true,
        onSuccess: () => {
            activeModal.value = null;
        },
    });
};

const applyFilter = () => {
    router.get('/administrasi/legalisir', { status: statusFilter.value || undefined }, { preserveState: true, replace: true });
};

const verifyOriginal = (id: number) => {
    actionForm.note = 'Ijazah asli sesuai register';
    actionForm.post(`/administrasi/legalisir/${id}/verifikasi-asli`, { preserveScroll: true });
};

const approve = (id: number) => {
    router.post(`/administrasi/legalisir/${id}/sahkan`, {}, { preserveScroll: true });
};

const statusTone = (status: string): 'neutral' | 'info' | 'warning' | 'success' | 'danger' => {
    if (status === 'completed' || status === 'approved') return 'success';
    if (status === 'verified_original') return 'warning';
    if (status === 'rejected') return 'danger';
    return 'info';
};
</script>

<template>
    <AppLayout title="Register Legalisir Ijazah">
        <Head title="Register Legalisir Ijazah" />

        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Register &amp; Pengesahan Legalisir Tata Usaha</h1>
                    <p class="text-sm text-slate-600">Catat verifikasi berkas asli, sahkan PDF ber-QR verifikasi, dan catat serah terima pengambilan langsung maupun kuasa.</p>
                </div>
                <select v-model="statusFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="applyFilter">
                    <option value="">Semua Status</option>
                    <option value="submitted">Diajukan</option>
                    <option value="verified_original">Asli Terverifikasi</option>
                    <option value="approved">Disahkan</option>
                    <option value="completed">Sudah Diambil</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <StateFeedback
                    v-if="requests.length === 0"
                    state="empty"
                    title="Belum ada permohonan legalisir"
                    description="Permohonan legalisir dari alumni akan muncul pada register ini."
                />

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-center text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2.5 text-center">Kode &amp; Alumni</th>
                                <th class="px-3 py-2.5 text-center">Dokumen</th>
                                <th class="px-3 py-2.5 text-center">Pengambilan</th>
                                <th class="px-3 py-2.5 text-center">Status</th>
                                <th class="px-3 py-2.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="r in requests" :key="r.id">
                                <td class="px-3 py-3">
                                    <p class="font-mono text-xs font-bold">{{ r.legalization_code }}</p>
                                    <p class="font-medium">{{ r.applicant_name }} ({{ r.graduation_year }})</p>
                                </td>
                                <td class="px-3 py-3 text-xs">
                                    <p class="font-semibold uppercase">{{ r.document_type }} — {{ r.copy_count }} lbr</p>
                                    <p class="text-slate-600">No. {{ r.certificate_number }}</p>
                                </td>
                                <td class="px-3 py-3 text-xs">
                                    <p>{{ r.delivery_method === 'pickup_proxy' ? `Kuasa: ${r.proxy_name || '-'}` : 'Ambil Langsung' }}</p>
                                    <p v-if="r.picked_up_at" class="text-emerald-700">Diambil: {{ r.picked_up_by_name }} ({{ r.picked_up_at }})</p>
                                </td>
                                <td class="px-3 py-3">
                                    <StatusBadge :status="r.status" :tone="statusTone(r.status)" size="sm" />
                                </td>
                                <td class="px-3 py-3 text-right space-x-2">
                                    <AppButton v-if="canProcess && !r.original_verified_at && r.status !== 'rejected'" type="button" variant="secondary" @click="verifyOriginal(r.id)">
                                        Cek Asli
                                    </AppButton>
                                    <AppButton v-if="canPublish && r.status === 'verified_original'" type="button" @click="approve(r.id)">
                                        Sahkan
                                    </AppButton>
                                    <AppButton v-if="canProcess && r.status === 'approved'" type="button" @click="openPickupModal(r)">
                                        Catat Ambil
                                    </AppButton>
                                    <AppButton v-if="canProcess && ['submitted', 'verified_original'].includes(r.status)" type="button" variant="secondary" @click="openDecideModal(r)">
                                        Revisi / Tolak
                                    </AppButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            v-if="activeModal"
            role="dialog"
            aria-modal="true"
            tabindex="-1"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="activeModal = null"
            @keydown.esc="activeModal = null"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900">
                        {{ activeModal.type === 'pickup' ? 'Catat Pengambilan Dokumen' : 'Revisi / Tolak Permohonan' }}
                    </h3>
                    <button type="button" class="text-sm text-slate-500 hover:text-slate-700" @click="activeModal = null">Tutup</button>
                </div>

                <form v-if="activeModal.type === 'pickup'" class="space-y-3" @submit.prevent="submitPickup">
                    <div>
                        <label for="legal-pickup-name" class="block text-xs font-semibold text-slate-700">Nama Pengambil Dokumen</label>
                        <input
                            id="legal-pickup-name"
                            v-model="actionForm.picked_up_by_name"
                            type="text"
                            required
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        />
                    </div>
                    <div>
                        <label for="legal-pickup-relation" class="block text-xs font-semibold text-slate-700">Status Pengambil</label>
                        <input
                            id="legal-pickup-relation"
                            v-model="actionForm.relation"
                            type="text"
                            required
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        />
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <AppButton type="button" variant="secondary" @click="activeModal = null">Batal</AppButton>
                        <AppButton type="submit" :disabled="actionForm.processing">Simpan Pengambilan</AppButton>
                    </div>
                </form>

                <form v-else class="space-y-3" @submit.prevent="submitDecision">
                    <div>
                        <label for="legal-decide-action" class="block text-xs font-semibold text-slate-700">Tindakan</label>
                        <select id="legal-decide-action" v-model="actionForm.action" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="revision">Minta Revisi Berkas</option>
                            <option value="reject">Tolak Permohonan</option>
                        </select>
                    </div>
                    <div>
                        <label for="legal-decide-reason" class="block text-xs font-semibold text-slate-700">Catatan / Alasan</label>
                        <textarea
                            id="legal-decide-reason"
                            v-model="actionForm.reason"
                            rows="3"
                            required
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        />
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <AppButton type="button" variant="secondary" @click="activeModal = null">Batal</AppButton>
                        <AppButton type="submit" :disabled="actionForm.processing">Kirim Keputusan</AppButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
