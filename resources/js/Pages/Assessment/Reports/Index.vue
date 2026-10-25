<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface ClassGroup {
    id: number;
    name: string;
    grade_level: number;
}

interface TermItem {
    id: number;
    name: string;
    semester: number;
}

interface ReportItem {
    id: number;
    enrollment_id: number;
    student_id: number;
    status: 'draft' | 'published' | 'archived';
    published_at?: string | null;
    checksum: string;
    scheme_version: number;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    active_term?: TermItem | null;
    classes: ClassGroup[];
    selected_class_id?: string;
    reports: ReportItem[];
}>();

const selectedClassId = ref(props.selected_class_id || (props.classes[0]?.id ? String(props.classes[0].id) : ''));

const onClassChange = () => {
    router.get(
        '/penilaian/rapor',
        {
            class_group_id: selectedClassId.value || undefined,
        },
        { preserveState: true }
    );
};

const generateForm = useForm({
    class_group_id: selectedClassId.value,
    term_id: props.active_term?.id || '',
});

const generateBatch = () => {
    if (!props.active_term || !selectedClassId.value) return;

    generateForm.class_group_id = selectedClassId.value;
    generateForm.term_id = props.active_term.id;

    generateForm.post('/penilaian/rapor/generate-batch', {
        preserveScroll: true,
    });
};

const publishReport = (reportId: number) => {
    if (confirm('Apakah Anda yakin ingin menerbitkan dan mengunci rapor ini? Setelah diterbitkan, snapshot bersifat permanen dan tidak dapat diubah (SS-D-005).')) {
        router.post(`/penilaian/rapor/${reportId}/publish`, {}, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Penerbitan Rapor & Snapshot" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Rapor Siswa & Snapshot Immutability</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Penerbitan rapor berversi dengan jaminan integritas snapshot permanen (SS-D-005) & checksum SHA-256.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        v-if="selectedClassId && props.active_term"
                        :href="`/penilaian/ledger?class_group_id=${selectedClassId}&term_id=${props.active_term.id}`"
                        class="inline-flex items-center gap-2 px-3.5 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-sm font-semibold shadow-xs transition-colors"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Buku Ledger Nilai</span>
                    </Link>

                    <button
                        type="button"
                        :disabled="generateForm.processing || !selectedClassId"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 text-white rounded-lg text-sm font-semibold shadow-xs transition-colors"
                        @click="generateBatch"
                    >
                        <svg
                            v-if="generateForm.processing"
                            class="animate-spin -ml-1 mr-1 h-4 w-4 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ generateForm.processing ? 'Menyusun...' : 'Generate Rapor Kelas' }}</span>
                    </button>
                </div>
            </div>

            <!-- Filter Kelas -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between gap-4">
                <div class="w-full sm:w-72">
                    <label for="class-select" class="block text-xs font-semibold text-slate-600 mb-1">Pilih Kelas / Rombel</label>
                    <select
                        id="class-select"
                        v-model="selectedClassId"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="onClassChange"
                    >
                        <option value="" disabled>-- Pilih Kelas --</option>
                        <option v-for="cls in props.classes" :key="cls.id" :value="String(cls.id)">
                            {{ cls.name }} (Tingkat {{ cls.grade_level }})
                        </option>
                    </select>
                </div>

                <div v-if="props.active_term" class="text-right">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tahun Ajaran / Semester:</span>
                    <p class="text-sm font-bold text-slate-800">{{ props.active_term.name }}</p>
                </div>
            </div>

            <!-- Tabel Snapshot Rapor Siswa -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-800 text-base">Snapshot Rapor Siswa</h2>
                        <p class="text-xs text-slate-500">
                            Status <span class="font-bold text-emerald-600">published</span> menandakan rapor sudah dikunci permanen dengan tanda tangan SHA-256.
                        </p>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">Total: {{ props.reports.length }} Rapor</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 border-collapse">
                        <thead class="bg-slate-50 text-slate-700 font-semibold text-xs border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4 w-16 text-center">ID Rapor</th>
                                <th class="py-3 px-4 w-32">Status</th>
                                <th class="py-3 px-4 w-28">Versi Skema</th>
                                <th class="py-3 px-4">Checksum SHA-256</th>
                                <th class="py-3 px-4 w-44">Waktu Terbit</th>
                                <th class="py-3 px-4 w-48 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-normal">
                            <tr
                                v-for="rep in props.reports"
                                :key="rep.id"
                                class="hover:bg-slate-50 transition-colors"
                            >
                                <td class="py-3 px-4 text-center font-mono text-xs text-slate-500">
                                    #{{ rep.id }}
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase',
                                            rep.status === 'published'
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : 'bg-amber-100 text-amber-800',
                                        ]"
                                    >
                                        {{ rep.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-700">
                                    v{{ rep.scheme_version }}
                                </td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-500">
                                    <span :title="rep.checksum" class="bg-slate-100 px-2 py-1 rounded">
                                        {{ rep.checksum ? rep.checksum.substring(0, 16) + '...' : '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-xs text-slate-600">
                                    {{ rep.published_at ? new Date(rep.published_at).toLocaleString('id-ID') : 'Belum Terbit' }}
                                </td>
                                <td class="py-3 px-4 text-right space-x-2">
                                    <Link
                                        :href="`/penilaian/rapor/${rep.id}`"
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 hover:bg-slate-200 text-slate-700"
                                    >
                                        Cetak / Lihat
                                    </Link>
                                    <button
                                        v-if="rep.status !== 'published'"
                                        type="button"
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded bg-emerald-600 hover:bg-emerald-700 text-white"
                                        @click="publishReport(rep.id)"
                                    >
                                        Terbitkan & Kunci
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="props.reports.length === 0">
                                <td colspan="6" class="text-center py-10 text-slate-400 text-sm">
                                    Belum ada snapshot rapor untuk kelas ini. Klik "Generate Rapor Kelas" di atas untuk menyusun draf.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

