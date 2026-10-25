<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';

interface SubjectGrade {
    subject_id: number;
    subject_name: string;
    subject_code: string;
    teacher_name: string;
    final_score: number | null;
    letter_grade: string;
    is_passing: boolean;
}

interface AttendanceSnapshot {
    present: number;
    sick: number;
    permitted: number;
    absent: number;
}

interface ReportDetail {
    id: number;
    status: string;
    scheme_version: number;
    checksum: string;
    student: {
        id: number;
        name: string;
        nis: string;
        nisn: string;
    };
    class_group: {
        id: number;
        name: string;
    };
    term: {
        id: number;
        name: string;
    };
    school: {
        name: string;
        address: string;
    };
    grades: SubjectGrade[];
    attendance: AttendanceSnapshot;
    notes: {
        homeroom_notes?: string;
    };
    published_at?: string | null;
}

const props = defineProps<{
    report: ReportDetail;
}>();

const printReport = () => {
    window.print();
};
</script>

<template>
    <AppLayout>
        <Head :title="`Rapor Siswa - ${props.report.student.name}`" />

        <div class="space-y-6">
            <!-- Navigasi & Aksi (Disembunyikan saat cetak) -->
            <div class="print:hidden flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        href="/penilaian/rapor"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-xs"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Kembali ke Daftar Rapor</span>
                    </Link>

                    <span
                        :class="[
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase',
                            props.report.status === 'published'
                                ? 'bg-emerald-100 text-emerald-800'
                                : 'bg-amber-100 text-amber-800',
                        ]"
                    >
                        Status: {{ props.report.status }}
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-xs transition-colors"
                        @click="printReport"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Rapor (Print / PDF)</span>
                    </button>
                </div>
            </div>

            <!-- Lembar Rapor Baku (Printable Sheet) -->
            <div class="bg-white p-8 sm:p-12 rounded-xl border border-slate-200 shadow-sm print:shadow-none print:border-none print:p-0 max-w-4xl mx-auto text-slate-900">
                <!-- Kop Rapor -->
                <div class="border-b-2 border-slate-900 pb-4 text-center mb-6">
                    <h2 class="text-xl font-bold uppercase tracking-wide">{{ props.report.school.name }}</h2>
                    <p class="text-xs text-slate-600">{{ props.report.school.address }}</p>
                    <h3 class="text-base font-bold uppercase tracking-wider mt-3 underline">
                        LAPORAN HASIL BELAJAR SISWA (RAPOR)
                    </h3>
                </div>

                <!-- Identitas Siswa -->
                <div class="grid grid-cols-2 gap-4 text-xs mb-6">
                    <table class="w-full">
                        <tbody>
                            <tr>
                                <td class="py-1 w-32 font-semibold">Nama Peserta Didik</td>
                                <td class="py-1 w-4">:</td>
                                <td class="py-1 font-bold">{{ props.report.student.name }}</td>
                            </tr>
                            <tr>
                                <td class="py-1 font-semibold">NIS / NISN</td>
                                <td class="py-1">:</td>
                                <td class="py-1 font-mono">{{ props.report.student.nis }} / {{ props.report.student.nisn }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="w-full">
                        <tbody>
                            <tr>
                                <td class="py-1 w-28 font-semibold">Kelas</td>
                                <td class="py-1 w-4">:</td>
                                <td class="py-1 font-bold">{{ props.report.class_group.name }}</td>
                            </tr>
                            <tr>
                                <td class="py-1 font-semibold">Semester / Tahun</td>
                                <td class="py-1">:</td>
                                <td class="py-1">{{ props.report.term.name }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tabel Nilai Akademik -->
                <div class="mb-6">
                    <h4 class="text-xs font-bold uppercase tracking-wider mb-2">A. Capaian Kompetensi Akademik</h4>
                    <table class="w-full text-xs border border-slate-300 border-collapse">
                        <thead class="bg-slate-100 font-bold text-center">
                            <tr>
                                <th class="border border-slate-300 py-2 px-2 w-10">No</th>
                                <th class="border border-slate-300 py-2 px-3 text-left">Mata Pelajaran</th>
                                <th class="border border-slate-300 py-2 px-2 w-16">Nilai Akhir</th>
                                <th class="border border-slate-300 py-2 px-2 w-14">Predikat</th>
                                <th class="border border-slate-300 py-2 px-2 w-20">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(gr, idx) in props.report.grades" :key="gr.subject_id">
                                <td class="border border-slate-300 py-2 px-2 text-center">{{ idx + 1 }}</td>
                                <td class="border border-slate-300 py-2 px-3 font-medium">
                                    {{ gr.subject_name }}
                                    <span class="block text-[10px] text-slate-500 font-normal">Guru: {{ gr.teacher_name }}</span>
                                </td>
                                <td class="border border-slate-300 py-2 px-2 text-center font-bold font-mono">
                                    {{ gr.final_score !== null ? gr.final_score : '-' }}
                                </td>
                                <td class="border border-slate-300 py-2 px-2 text-center font-bold">
                                    {{ gr.letter_grade }}
                                </td>
                                <td class="border border-slate-300 py-2 px-2 text-center">
                                    <span
                                        :class="[
                                            gr.is_passing ? 'text-emerald-700 font-semibold' : 'text-rose-600 font-semibold',
                                        ]"
                                    >
                                        {{ gr.is_passing ? 'Tuntas' : 'Perlu Bimbingan' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="props.report.grades.length === 0">
                                <td colspan="5" class="border border-slate-300 py-4 text-center text-slate-400">
                                    Belum ada catatan nilai akademik.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Rekapitulasi Presensi & Catatan -->
                <div class="grid grid-cols-2 gap-6 mb-8 text-xs">
                    <!-- Presensi -->
                    <div>
                        <h4 class="font-bold uppercase tracking-wider mb-2">B. Kehadiran</h4>
                        <table class="w-full border border-slate-300 border-collapse">
                            <tbody>
                                <tr>
                                    <td class="border border-slate-300 py-1.5 px-3">Sakit (S)</td>
                                    <td class="border border-slate-300 py-1.5 px-3 text-center font-bold w-16">
                                        {{ props.report.attendance.sick }} hari
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-slate-300 py-1.5 px-3">Izin (I)</td>
                                    <td class="border border-slate-300 py-1.5 px-3 text-center font-bold">
                                        {{ props.report.attendance.permitted }} hari
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-slate-300 py-1.5 px-3">Tanpa Keterangan (A)</td>
                                    <td class="border border-slate-300 py-1.5 px-3 text-center font-bold">
                                        {{ props.report.attendance.absent }} hari
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Catatan Wali Kelas -->
                    <div>
                        <h4 class="font-bold uppercase tracking-wider mb-2">C. Catatan Wali Kelas</h4>
                        <div class="border border-slate-300 p-3 h-24 rounded-xs text-slate-700 italic">
                            {{ props.report.notes?.homeroom_notes || 'Tingkatkan semangat belajar dan terus kembangkan bakat serta prestasimu.' }}
                        </div>
                    </div>
                </div>

                <!-- Tanda Tangan -->
                <div class="grid grid-cols-3 gap-4 text-center text-xs mt-12 mb-8">
                    <div>
                        <p class="mb-16">Mengetahui,<br>Orang Tua / Wali</p>
                        <p class="font-bold underline">( ............................................ )</p>
                    </div>

                    <div>
                        <p class="mb-16">Wali Kelas,</p>
                        <p class="font-bold underline">( ............................................ )</p>
                    </div>

                    <div>
                        <p class="mb-16">Kepala Sekolah,</p>
                        <p class="font-bold underline">( ............................................ )</p>
                    </div>
                </div>

                <!-- Footer Keamanan & Checksum SHA-256 (SS-D-005) -->
                <div class="border-t border-slate-200 pt-3 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                    <span>Dokumen Rapor Digital Resmi — Snapshot Versi {{ props.report.scheme_version }}</span>
                    <span>SHA-256: {{ props.report.checksum }}</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
