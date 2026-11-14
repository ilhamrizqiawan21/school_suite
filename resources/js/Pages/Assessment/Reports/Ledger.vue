<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';

interface SubjectCol {
    id: number;
    name: string;
    code: string;
}

interface StudentLedgerRow {
    enrollment_id: number;
    student_id: number;
    nis: string;
    name: string;
    grades: Record<number, number | null>;
    average: number | null;
    attendance: {
        present: number;
        sick: number;
        permitted: number;
        absent: number;
    };
}

interface LedgerData {
    class_group: {
        id: number;
        name: string;
    } | null;
    term: {
        id: number;
        name: string;
    } | null;
    subjects: SubjectCol[];
    students: StudentLedgerRow[];
}

const props = defineProps<{
    ledger: LedgerData;
}>();

const printLedger = () => {
    window.print();
};
</script>

<template>
    <AppLayout>
        <Head :title="`Buku Ledger Nilai - ${props.ledger.class_group?.name || 'Kelas'}`" />

        <div class="space-y-6">
            <!-- Header & Navigasi -->
            <div class="print:hidden flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        href="/penilaian/rapor"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-[var(--surface)] text-meta font-semibold text-slate-700 hover:bg-slate-50 shadow-xs"
                    >
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Kembali ke Rapor</span>
                    </Link>

                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-slate-900">
                            Buku Ledger Nilai — {{ props.ledger.class_group?.name }}
                        </h1>
                        <p class="text-meta text-slate-600">
                            Semester: {{ props.ledger.term?.name }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a
                        v-if="props.ledger.class_group?.id && props.ledger.term?.id"
                        :href="`/laporan/nilai?class_group_id=${props.ledger.class_group.id}&term_id=${props.ledger.term.id}`"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-sm font-semibold shadow-xs transition-colors"
                    >
                        <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M8 13l4 4m0-4l-4 4" />
                        </svg>
                        <span>Export Excel</span>
                    </a>
                    <AppButton variant="primary"
                        type="button"
                        class="inline-flex items-center transition-colors"
                        @click="printLedger"
                    ><template #icon><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg></template>

                        <span>Cetak Ledger</span>
                    </AppButton>
                </div>
            </div>

            <!-- Tabel Matriks Ledger -->
            <div class="bg-[var(--surface)] rounded-xl border border-[var(--line)] shadow-xs overflow-hidden print:border-none print:shadow-none">
                <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-slate-800 text-base">Rekapitulasi Nilai Seluruh Mata Pelajaran</h2>
                        <p class="text-meta text-slate-600">
                            Menampilkan nilai akhir tiap mata pelajaran, nilai rerata, dan ketidakhadiran (S, I, A).
                        </p>
                    </div>
                    <span class="text-meta font-semibold text-slate-600">
                        {{ props.ledger.students.length }} Siswa | {{ props.ledger.subjects.length }} Mata Pelajaran
                    </span>
                </div>

                <div class="overflow-x-auto table-scroll" tabindex="0" role="region" aria-label="Tabel data">
                    <table class="app-table w-full text-left text-meta text-slate-700 border-collapse border border-slate-200">
                        <thead class="bg-slate-50 text-slate-800 font-bold uppercase text-meta">
                            <tr>
                                <th scope="col" class="border border-slate-200 py-2.5 px-2 w-10 text-center" rowspan="2">No</th>
                                <th scope="col" class="border border-slate-200 py-2.5 px-3 w-24" rowspan="2">NIS</th>
                                <th scope="col" class="border border-slate-200 py-2.5 px-4 min-w-[160px]" rowspan="2">Nama Siswa</th>
                                <th
                                    :colspan="props.ledger.subjects.length"
                                    class="border border-slate-200 py-2 px-2 text-center bg-indigo-50/60 text-indigo-900"
                                >
                                    Mata Pelajaran
                                </th>
                                <th scope="col" class="border border-slate-200 py-2.5 px-3 w-16 text-center bg-slate-100" rowspan="2">Rerata</th>
                                <th scope="colgroup" colspan="3" class="border border-slate-200 py-2 px-2 text-center bg-amber-50/60 text-amber-900">
                                    Presensi
                                </th>
                            </tr>
                            <tr>
                                <th
                                    v-for="sub in props.ledger.subjects"
                                    :key="sub.id"
                                    class="border border-slate-200 py-2 px-2 text-center w-16 font-mono text-meta"
                                    :title="sub.name"
                                >
                                    {{ sub.code }}
                                </th>
                                <th scope="col" class="border border-slate-200 py-1.5 px-1.5 text-center w-8 text-amber-700">S</th>
                                <th scope="col" class="border border-slate-200 py-1.5 px-1.5 text-center w-8 text-blue-700">I</th>
                                <th scope="col" class="border border-slate-200 py-1.5 px-1.5 text-center w-8 text-rose-700">A</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(stu, idx) in props.ledger.students"
                                :key="stu.student_id"
                                class="hover:bg-slate-50 transition-colors"
                            >
                                <td class="border border-slate-200 py-2 px-2 text-center text-slate-600 font-mono">
                                    {{ idx + 1 }}
                                </td>
                                <td class="border border-slate-200 py-2 px-3 font-mono text-slate-600">
                                    {{ stu.nis }}
                                </td>
                                <td class="border border-slate-200 py-2 px-4 font-semibold text-slate-900">
                                    {{ stu.name }}
                                </td>
                                <!-- Nilai Per Mata Pelajaran -->
                                <td
                                    v-for="sub in props.ledger.subjects"
                                    :key="sub.id"
                                    class="border border-slate-200 py-2 px-2 text-center font-mono"
                                >
                                    <span v-if="stu.grades[sub.id] !== undefined && stu.grades[sub.id] !== null">
                                        {{ stu.grades[sub.id] }}
                                    </span>
                                    <span v-else class="text-slate-300">-</span>
                                </td>
                                <!-- Rerata -->
                                <td class="border border-slate-200 py-2 px-3 text-center font-bold font-mono text-indigo-700 bg-indigo-50/20">
                                    {{ stu.average !== null ? stu.average : '-' }}
                                </td>
                                <!-- Presensi -->
                                <td class="border border-slate-200 py-2 px-1.5 text-center font-mono text-amber-700">
                                    {{ stu.attendance?.sick || 0 }}
                                </td>
                                <td class="border border-slate-200 py-2 px-1.5 text-center font-mono text-blue-700">
                                    {{ stu.attendance?.permitted || 0 }}
                                </td>
                                <td class="border border-slate-200 py-2 px-1.5 text-center font-mono text-rose-700">
                                    {{ stu.attendance?.absent || 0 }}
                                </td>
                            </tr>
                            <tr v-if="props.ledger.students.length === 0">
                                <td
                                    :colspan="5 + props.ledger.subjects.length"
                                    class="border border-slate-200 py-8 text-center text-slate-600 text-meta"
                                >
                                    Tidak ada data nilai atau siswa ditemukan untuk rombel ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

