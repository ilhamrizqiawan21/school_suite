<script setup lang="ts">
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
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-xs"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Kembali ke Rapor</span>
                    </Link>

                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-slate-900">
                            Buku Ledger Nilai — {{ props.ledger.class_group?.name }}
                        </h1>
                        <p class="text-xs text-slate-500">
                            Semester: {{ props.ledger.term?.name }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-xs transition-colors"
                        @click="printLedger"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Ledger</span>
                    </button>
                </div>
            </div>

            <!-- Tabel Matriks Ledger -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden print:border-none print:shadow-none">
                <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-slate-800 text-base">Rekapitulasi Nilai Seluruh Mata Pelajaran</h2>
                        <p class="text-xs text-slate-500">
                            Menampilkan nilai akhir tiap mata pelajaran, nilai rerata, dan ketidakhadiran (S, I, A).
                        </p>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">
                        {{ props.ledger.students.length }} Siswa | {{ props.ledger.subjects.length }} Mata Pelajaran
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700 border-collapse border border-slate-200">
                        <thead class="bg-slate-50 text-slate-800 font-bold uppercase text-[11px]">
                            <tr>
                                <th class="border border-slate-200 py-2.5 px-2 w-10 text-center" rowspan="2">No</th>
                                <th class="border border-slate-200 py-2.5 px-3 w-24" rowspan="2">NIS</th>
                                <th class="border border-slate-200 py-2.5 px-4 min-w-[160px]" rowspan="2">Nama Siswa</th>
                                <th
                                    :colspan="props.ledger.subjects.length"
                                    class="border border-slate-200 py-2 px-2 text-center bg-indigo-50/60 text-indigo-900"
                                >
                                    Mata Pelajaran
                                </th>
                                <th class="border border-slate-200 py-2.5 px-3 w-16 text-center bg-slate-100" rowspan="2">Rerata</th>
                                <th colspan="3" class="border border-slate-200 py-2 px-2 text-center bg-amber-50/60 text-amber-900">
                                    Presensi
                                </th>
                            </tr>
                            <tr>
                                <th
                                    v-for="sub in props.ledger.subjects"
                                    :key="sub.id"
                                    class="border border-slate-200 py-2 px-2 text-center w-16 font-mono text-[10px]"
                                    :title="sub.name"
                                >
                                    {{ sub.code }}
                                </th>
                                <th class="border border-slate-200 py-1.5 px-1.5 text-center w-8 text-amber-700">S</th>
                                <th class="border border-slate-200 py-1.5 px-1.5 text-center w-8 text-blue-700">I</th>
                                <th class="border border-slate-200 py-1.5 px-1.5 text-center w-8 text-rose-700">A</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(stu, idx) in props.ledger.students"
                                :key="stu.student_id"
                                class="hover:bg-slate-50 transition-colors"
                            >
                                <td class="border border-slate-200 py-2 px-2 text-center text-slate-500 font-mono">
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
                                    class="border border-slate-200 py-8 text-center text-slate-400 text-xs"
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
