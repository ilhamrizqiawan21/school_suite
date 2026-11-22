<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import OperationsScanner from '@/components/OperationsScanner.vue';
import FormField from '@/components/FormField.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import OperationsLayout from '@/layouts/OperationsLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface GatePassItem {
    id: number;
    pass_code: string;
    student_id: number;
    student_name: string;
    student_nis: string;
    class_name: string;
    exit_type: string;
    reason: string;
    destination?: string | null;
    valid_from: string;
    valid_until: string;
    status: string;
    exit_recorded_at?: string | null;
    return_recorded_at?: string | null;
    cancellation_reason?: string | null;
    handover_notes?: string | null;
}

interface StudentOption {
    student_id: number;
    nis: string;
    name: string;
    class_group_name?: string | null;
}

const props = defineProps<{
    passes: GatePassItem[];
    students: StudentOption[];
    filters: { status: string };
    timezone: string;
}>();

const nowIsoLocal = () => {
    const d = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const plusHoursIsoLocal = (hours: number) => {
    const d = new Date(Date.now() + hours * 3600 * 1000);
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const statusFilter = ref(props.filters.status || '');
const searchQuery = ref('');
const searchResults = ref<StudentOption[]>(props.students.slice(0, 15));
const searchLoading = ref(false);
const cancellingPass = ref<GatePassItem | null>(null);

const issueForm = useForm({
    student_id: props.students[0]?.student_id ?? ('' as string | number),
    exit_type: 'temporary_out',
    reason: '',
    destination: '',
    valid_from: nowIsoLocal(),
    valid_until: plusHoursIsoLocal(2),
});

const cancelForm = useForm({
    reason: '',
});

const applyFilter = () => {
    router.get('/piket/izin-keluar', { status: statusFilter.value || undefined }, { preserveState: true, replace: true });
};

const lookupStudent = async () => {
    searchLoading.value = true;
    try {
        const res = await fetch(`/piket/izin-keluar/cari-siswa?q=${encodeURIComponent(searchQuery.value)}`, {
            headers: { Accept: 'application/json' },
        });
        if (res.ok) {
            const payload = await res.json();
            searchResults.value = payload.students ?? [];
            if (searchResults.value.length === 1) {
                issueForm.student_id = searchResults.value[0].student_id;
            }
        }
    } finally {
        searchLoading.value = false;
    }
};

const submitIssue = () => {
    issueForm.post('/piket/izin-keluar', {
        preserveScroll: true,
        onSuccess: () => {
            issueForm.reason = '';
            issueForm.destination = '';
        },
    });
};

const openCancelModal = (pass: GatePassItem) => {
    cancellingPass.value = pass;
    cancelForm.reset();
};

const submitCancel = () => {
    if (!cancellingPass.value) return;
    cancelForm.post(`/piket/izin-keluar/${cancellingPass.value.id}/batal`, {
        preserveScroll: true,
        onSuccess: () => {
            cancellingPass.value = null;
        },
    });
};

const statusTone = (status: string): 'neutral' | 'info' | 'warning' | 'success' | 'danger' => {
    if (status === 'active') return 'warning';
    if (status === 'out') return 'info';
    if (status === 'returned') return 'success';
    if (status === 'cancelled' || status === 'expired') return 'danger';
    return 'neutral';
};

const exitTypeLabel = (type: string): string => {
    if (type === 'early_home') return 'Pulang Lebih Awal';
    if (type === 'school_duty') return 'Tugas Sekolah';
    return 'Keluar Sementara';
};
</script>

<template>
    <OperationsLayout mode="scan" title="Piket Izin Keluar">
        <Head title="Piket Izin Keluar" />

        <OperationsScanner v-model="searchQuery" title="Scan Kartu Pelajar" description="Izin Keluar-Masuk Siswa (Piket & Satpam)"
            :busy="searchLoading" @lookup="lookupStudent">
            <a href="/e-layanan">Form / Beranda →</a>
            <a href="/pos-jaga">Pos Satpam →</a>
        </OperationsScanner>
        <details class="operations-workflow" :open="searchResults.length > 0">
            <summary>Form Izin & Riwayat Surat Jalan</summary>
        <div class="space-y-6">
            <header class="operations-scan-hero flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="operations-scan-label">Piket Sekolah · Scan Kartu Pelajar</p>
                    <h1 class="text-xl font-bold text-white">Piket Sekolah — Surat Jalan &amp; Izin Keluar</h1>
                    <p class="text-sm text-emerald-100">
                        Terbitkan pass keluar sementara, pulang lebih awal, atau tugas sekolah untuk divalidasi di Pos Jaga ({{ timezone }}).
                    </p>
                </div>
                <a
                    href="/pos-jaga"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Buka Layar Pos Jaga
                </a>
            </header>

            <form @submit.prevent="submitIssue" class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                <h2 class="text-base font-semibold text-slate-900">Terbitkan Pass Izin Keluar</h2>

                <div class="grid gap-3 md:grid-cols-3">
                    <div class="operations-scan-panel md:col-span-2">
                        <p>Pindai kartu atau cari siswa sebelum menerbitkan pass izin keluar.</p>
                        <FormField id="picket-search-student" label="Cari Siswa (Nama, NIS, atau Scan Kartu)">
                            <div class="flex gap-2">
                                <input
                                    id="picket-search-student"
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Ketik nama/NIS atau tempel token kartu"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                    @keydown.enter.prevent="lookupStudent"
                                />
                                <AppButton type="button" variant="secondary" :disabled="searchLoading" @click="lookupStudent">
                                    {{ searchLoading ? 'Mencari...' : 'Cari' }}
                                </AppButton>
                            </div>
                        </FormField>
                    </div>
                    <FormField id="picket-student-id" label="Pilih Siswa" :error="issueForm.errors.student_id" required>
                        <select id="picket-student-id" v-model="issueForm.student_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option v-for="student in searchResults" :key="student.student_id" :value="student.student_id">
                                {{ student.name }} ({{ student.nis }}){{ student.class_group_name ? ` — ${student.class_group_name}` : '' }}
                            </option>
                        </select>
                    </FormField>
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <FormField id="picket-exit-type" label="Jenis Izin Keluar" :error="issueForm.errors.exit_type" required>
                        <select id="picket-exit-type" v-model="issueForm.exit_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="temporary_out">Keluar Sementara</option>
                            <option value="early_home">Pulang Lebih Awal</option>
                            <option value="school_duty">Tugas Sekolah</option>
                        </select>
                    </FormField>
                    <FormField id="picket-valid-from" label="Mulai Berlaku" :error="issueForm.errors.valid_from" required>
                        <input id="picket-valid-from" v-model="issueForm.valid_from" type="datetime-local" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="picket-valid-until" label="Berlaku Sampai" :error="issueForm.errors.valid_until" required>
                        <input id="picket-valid-until" v-model="issueForm.valid_until" type="datetime-local" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <FormField id="picket-destination" label="Tujuan / Lokasi (Opsional)" :error="issueForm.errors.destination">
                        <input
                            id="picket-destination"
                            v-model="issueForm.destination"
                            type="text"
                            placeholder="Misal: Puskesmas / Rumah Sakit / Kantor Dinas"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        />
                    </FormField>
                    <FormField id="picket-reason" label="Alasan Izin Keluar" :error="issueForm.errors.reason" required>
                        <input
                            id="picket-reason"
                            v-model="issueForm.reason"
                            type="text"
                            placeholder="Jelaskan keperluan keluar lingkungan madrasah"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        />
                    </FormField>
                </div>

                <div>
                    <AppButton type="submit" :disabled="issueForm.processing">
                        {{ issueForm.processing ? 'Menerbitkan...' : 'Terbitkan Pass Keluar' }}
                    </AppButton>
                </div>
            </form>

            <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900">Daftar Pass Izin Keluar</h2>
                    <div class="flex items-center gap-2">
                        <select v-model="statusFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="applyFilter">
                            <option value="">Semua Status</option>
                            <option value="active">Aktif / Diterbitkan</option>
                            <option value="out">Di Luar Sekolah</option>
                            <option value="returned">Sudah Kembali</option>
                            <option value="cancelled">Dibatalkan</option>
                            <option value="expired">Kedaluwarsa</option>
                        </select>
                    </div>
                </div>

                <StateFeedback
                    v-if="passes.length === 0"
                    state="empty"
                    title="Belum ada pass izin keluar"
                    description="Pass izin keluar yang diterbitkan oleh guru piket akan tampil di sini beserta kode pass untuk Pos Jaga."
                />

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-center text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2.5">Kode Pass</th>
                                <th class="px-3 py-2.5">Siswa</th>
                                <th class="px-3 py-2.5">Jenis & Alasan</th>
                                <th class="px-3 py-2.5">Masa Berlaku</th>
                                <th class="px-3 py-2.5">Status</th>
                                <th class="px-3 py-2.5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="pass in passes" :key="pass.id" class="hover:bg-slate-50/70">
                                <td class="px-3 py-3 font-mono text-xs font-bold text-slate-900">{{ pass.pass_code }}</td>
                                <td class="px-3 py-3">
                                    <p class="font-medium text-slate-900">{{ pass.student_name }}</p>
                                    <p class="text-xs text-slate-500">{{ pass.student_nis }} • {{ pass.class_name }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-medium text-slate-800">{{ exitTypeLabel(pass.exit_type) }}</p>
                                    <p class="text-xs text-slate-600">{{ pass.reason }}</p>
                                    <p v-if="pass.destination" class="text-xs text-slate-500">Tujuan: {{ pass.destination }}</p>
                                </td>
                                <td class="px-3 py-3 text-xs text-slate-600">
                                    <p>{{ pass.valid_from }} — {{ pass.valid_until }}</p>
                                    <p v-if="pass.exit_recorded_at" class="text-slate-500">Keluar: {{ pass.exit_recorded_at }}</p>
                                    <p v-if="pass.return_recorded_at" class="text-emerald-700">Kembali: {{ pass.return_recorded_at }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <StatusBadge :status="pass.status" :tone="statusTone(pass.status)" size="sm" />
                                    <p v-if="pass.cancellation_reason" class="mt-1 text-xs text-rose-700">{{ pass.cancellation_reason }}</p>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <button
                                        v-if="pass.status === 'active' || pass.status === 'out'"
                                        type="button"
                                        class="rounded-md border border-rose-300 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                                        @click="openCancelModal(pass)"
                                    >
                                        Batalkan
                                    </button>
                                    <span v-else class="text-xs text-slate-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="cancellingPass" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @click.self="cancellingPass = null" @keydown.esc="cancellingPass = null">
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl space-y-4">
                <h3 class="text-base font-semibold text-slate-900">Batalkan Pass {{ cancellingPass.pass_code }}</h3>
                <p class="text-sm text-slate-600">{{ cancellingPass.student_name }} — {{ cancellingPass.reason }}</p>
                <FormField id="picket-cancel-reason" label="Alasan Pembatalan" :error="cancelForm.errors.reason" required>
                    <textarea id="picket-cancel-reason" v-model="cancelForm.reason" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </FormField>
                <div class="flex justify-end gap-2">
                    <AppButton type="button" variant="secondary" @click="cancellingPass = null">Tutup</AppButton>
                    <AppButton type="button" :disabled="cancelForm.processing" @click="submitCancel">
                        {{ cancelForm.processing ? 'Menyimpan...' : 'Konfirmasi Batal' }}
                    </AppButton>
                </div>
            </div>
        </div>
        </details>
    </OperationsLayout>
</template>
