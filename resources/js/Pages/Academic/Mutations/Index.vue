<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface MutationRow {
    id: number;
    mutation_code: string;
    direction: string;
    student_name: string;
    nis?: string | null;
    nisn?: string | null;
    origin_school?: string | null;
    destination_school?: string | null;
    reason: string;
    status: string;
    decision_note?: string | null;
    has_official_letter: boolean;
}

const props = defineProps<{
    canPublish: boolean;
    mutations: MutationRow[];
    classes: { id: number; name: string; grade_level: number }[];
    filters: { direction: string; status: string };
}>();

const directionFilter = ref(props.filters.direction || '');
const statusFilter = ref(props.filters.status || '');
const selected = ref<MutationRow | null>(null);
const modalMode = ref<'decide' | 'revoke'>('decide');

const decideForm = useForm({
    decision: props.canPublish ? 'approve' : 'request_revision',
    decision_note: '',
    nis: '',
    target_class_group_id: '' as string | number,
    effective_date: new Date().toISOString().slice(0, 10),
});

const revokeForm = useForm({
    reason: '',
});

const applyFilter = () => {
    router.get('/akademik/mutasi', {
        direction: directionFilter.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, replace: true });
};

const openModal = (row: MutationRow, mode: 'decide' | 'revoke') => {
    selected.value = row;
    modalMode.value = mode;
    decideForm.reset();
    decideForm.effective_date = new Date().toISOString().slice(0, 10);
    revokeForm.reset();
};

const submitDecision = () => {
    if (!selected.value) return;
    decideForm.post(`/akademik/mutasi/${selected.value.id}/putuskan`, {
        preserveScroll: true,
        onSuccess: () => { selected.value = null; },
    });
};

const submitRevoke = () => {
    if (!selected.value) return;
    revokeForm.post(`/akademik/mutasi/${selected.value.id}/cabut`, {
        preserveScroll: true,
        onSuccess: () => { selected.value = null; },
    });
};

const statusTone = (status: string): 'neutral' | 'info' | 'warning' | 'success' | 'danger' => {
    if (status === 'approved') return 'success';
    if (status === 'needs_revision') return 'warning';
    if (status === 'rejected' || status === 'cancelled') return 'danger';
    return 'info';
};
</script>

<template>
    <AppLayout title="Antrean Mutasi Siswa">
        <Head title="Antrean Mutasi Siswa" />

        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-[var(--ink)]">Antrean Mutasi Masuk &amp; Keluar Siswa</h1>
                    <p class="text-sm text-[var(--ink-soft)]">Review permohonan mutasi, terbitkan surat resmi PDF, atau cabut persetujuan secara aman tanpa menghapus histori enrollment.</p>
                </div>
                <div class="flex gap-2">
                    <select v-model="directionFilter" class="rounded-lg border border-[var(--line)] px-3 py-2 text-sm" @change="applyFilter">
                        <option value="">Semua Jenis</option>
                        <option value="incoming">Mutasi Masuk</option>
                        <option value="outgoing">Mutasi Keluar</option>
                    </select>
                    <select v-model="statusFilter" class="rounded-lg border border-[var(--line)] px-3 py-2 text-sm" @change="applyFilter">
                        <option value="">Semua Status</option>
                        <option value="submitted">Diajukan</option>
                        <option value="needs_revision">Perlu Perbaikan</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
                        <option value="cancelled">Dicabut</option>
                    </select>
                </div>
            </div>

            <div class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-5">
                <StateFeedback
                    v-if="mutations.length === 0"
                    state="empty"
                    title="Belum ada permohonan mutasi"
                    description="Permohonan mutasi masuk dan keluar yang dikirim dari portal akan muncul di sini."
                />

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-[var(--surface-subtle)] text-center text-xs font-semibold uppercase text-[var(--ink-faint)]">
                            <tr>
                                <th class="px-3 py-2.5">Kode &amp; Jenis</th>
                                <th class="px-3 py-2.5">Siswa</th>
                                <th class="px-3 py-2.5">Sekolah Asal/Tujuan</th>
                                <th class="px-3 py-2.5">Status</th>
                                <th class="px-3 py-2.5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="m in mutations" :key="m.id">
                                <td class="px-3 py-3">
                                    <p class="font-mono text-xs font-bold">{{ m.mutation_code }}</p>
                                    <p class="text-xs text-[var(--ink-faint)]">{{ m.direction === 'incoming' ? 'Mutasi Masuk' : 'Mutasi Keluar' }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-medium text-[var(--ink)]">{{ m.student_name }}</p>
                                    <p class="text-xs text-[var(--ink-faint)]">NIS: {{ m.nis || '-' }} • NISN: {{ m.nisn || '-' }}</p>
                                </td>
                                <td class="px-3 py-3 text-xs text-[var(--ink-soft)]">
                                    <p>{{ m.origin_school || m.destination_school || '-' }}</p>
                                    <p class="text-[var(--ink-faint)]">{{ m.reason }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <StatusBadge :status="m.status" :tone="statusTone(m.status)" size="sm" />
                                </td>
                                <td class="px-3 py-3 text-right space-x-2">
                                    <AppButton icon="pdf" as="a"
                                        v-if="m.has_official_letter"
                                        :href="`/layanan/mutasi/${m.id}/surat.pdf`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-xs font-semibold text-emerald-700 underline"
                                    >
                                        Surat PDF
                                    </AppButton>
                                    <button
                                        v-if="m.status === 'submitted' || m.status === 'needs_revision'"
                                        type="button"
                                        class="rounded-md border border-[var(--line)] px-2.5 py-1 text-xs font-semibold text-[var(--ink-soft)]"
                                        @click="openModal(m, 'decide')"
                                    >
                                        Proses
                                    </button>
                                    <button
                                        v-if="canPublish && m.status === 'approved'"
                                        type="button"
                                        class="rounded-md border border-rose-300 px-2.5 py-1 text-xs font-semibold text-rose-700"
                                        @click="openModal(m, 'revoke')"
                                    >
                                        Cabut
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="selected" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @click.self="selected = null" @keydown.esc="selected = null">
            <div class="w-full max-w-md rounded-xl bg-[var(--surface)] p-5 shadow-xl space-y-4">
                <h3 class="text-base font-semibold">
                    {{ modalMode === 'decide' ? `Putuskan Mutasi ${selected.mutation_code}` : `Cabut Mutasi ${selected.mutation_code}` }}
                </h3>
                <div v-if="modalMode === 'decide'" class="space-y-3">
                    <FormField id="mut-decision" label="Keputusan" required>
                        <select id="mut-decision" v-model="decideForm.decision" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                            <option v-if="canPublish" value="approve">Setujui &amp; Terbitkan Surat</option>
                            <option value="request_revision">Minta Perbaikan Berkas</option>
                            <option value="reject">Tolak Permohonan</option>
                        </select>
                    </FormField>
                    <div v-if="decideForm.decision === 'approve' && selected.direction === 'incoming'" class="grid gap-2 md:grid-cols-2">
                        <FormField id="mut-nis" label="NIS Baru (Opsional)">
                            <input id="mut-nis" v-model="decideForm.nis" type="text" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                        </FormField>
                        <FormField id="mut-class" label="Kelas Penempatan">
                            <select id="mut-class" v-model="decideForm.target_class_group_id" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                                <option value="">Pilih kelas...</option>
                                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </FormField>
                    </div>
                    <FormField id="mut-note" label="Catatan / Alasan" :error="decideForm.errors.decision_note">
                        <textarea id="mut-note" v-model="decideForm.decision_note" rows="3" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                    </FormField>
                    <div class="flex justify-end gap-2">
                        <AppButton type="button" variant="secondary" @click="selected = null">Tutup</AppButton>
                        <AppButton type="button" :disabled="decideForm.processing" @click="submitDecision">Simpan</AppButton>
                    </div>
                </div>
                <div v-else class="space-y-3">
                    <FormField id="mut-revoke-reason" label="Alasan Pencabutan" :error="revokeForm.errors.reason" required>
                        <textarea id="mut-revoke-reason" v-model="revokeForm.reason" rows="3" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                    </FormField>
                    <div class="flex justify-end gap-2">
                        <AppButton type="button" variant="secondary" @click="selected = null">Tutup</AppButton>
                        <AppButton type="button" :disabled="revokeForm.processing" @click="submitRevoke">Cabut Persetujuan</AppButton>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
