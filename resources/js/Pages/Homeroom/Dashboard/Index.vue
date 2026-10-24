<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface ClassItem {
    id: number;
    name: string;
    grade_level: number;
    homeroom_teacher?: { name: string } | null;
}

interface TermItem {
    id: number;
    name: string;
}

interface StudentSummaryItem {
    enrollment_id: number;
    student_id: number;
    nis: string;
    name: string;
    gender: 'L' | 'P';
    roll_number?: number | null;
    present: number;
    sick: number;
    permitted: number;
    absent: number;
    total_recorded: number;
    attendance_percentage: number;
    needs_attention: boolean;
    attention_reason: string;
}

interface SummaryData {
    class_group_id: number;
    term_id: number;
    total_students: number;
    students_needing_attention_count: number;
    average_attendance_percentage: number;
    students: StudentSummaryItem[];
}

const props = defineProps<{
    active_term?: TermItem | null;
    classes: ClassItem[];
    selected_class?: ClassItem | null;
    summary?: SummaryData | null;
    stats: {
        incidents_count: number;
        achievements_count: number;
        notes_count: number;
    };
}>();

const selectedClassId = ref(props.selected_class?.id ? String(props.selected_class.id) : '');

const changeClass = () => {
    router.get('/wali-kelas', { class_group_id: selectedClassId.value }, { preserveState: true });
};
</script>

<template>
    <AppLayout title="Ruang Kerja Wali Kelas">
        <Head title="Ruang Kerja Wali Kelas — School Suite" />

        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Ruang Kerja Wali Kelas
                        <span v-if="selected_class" class="text-indigo-600"> — Kelas {{ selected_class.name }}</span>
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Monitoring perkembangan peserta didik, deteksi dini ketidakhadiran, catatan observasi, dan rekam prestasi.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-semibold text-slate-600">Pilih Rombel:</label>
                        <select
                            v-model="selectedClassId"
                            @change="changeClass"
                            class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 font-medium"
                        >
                            <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                                Kelas {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <Link
                        href="/wali-kelas/catatan"
                        class="px-3.5 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-xs"
                    >
                        Catatan Siswa
                    </Link>
                    <Link
                        href="/wali-kelas/kedisiplinan"
                        class="px-3.5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition shadow-xs"
                    >
                        Prestasi & Tata Tertib
                    </Link>
                </div>
            </div>

            <!-- Metric Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Peserta Didik</div>
                    <div class="text-2xl font-bold text-slate-900 mt-2">{{ summary?.total_students || 0 }} Siswa</div>
                    <div class="text-xs text-slate-400 mt-1">Terdaftar pada rombel aktif</div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rata-rata Kehadiran</div>
                    <div class="text-2xl font-bold text-emerald-600 mt-2">{{ summary?.average_attendance_percentage || 100 }}%</div>
                    <div class="text-xs text-slate-400 mt-1">Presensi semester berjalan</div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Perlu Perhatian (Early Warning)</div>
                    <div class="text-2xl font-bold text-rose-600 mt-2">{{ summary?.students_needing_attention_count || 0 }} Siswa</div>
                    <div class="text-xs text-slate-400 mt-1">Alpa ≥ 3 hari / kehadiran &lt; 85%</div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Prestasi & Insiden</div>
                    <div class="text-2xl font-bold text-indigo-600 mt-2">{{ stats.achievements_count }} / {{ stats.incidents_count }}</div>
                    <div class="text-xs text-slate-400 mt-1">{{ stats.achievements_count }} Prestasi, {{ stats.incidents_count }} Pelanggaran</div>
                </div>
            </div>

            <!-- Early Warning Alert Banner (jika ada siswa yang butuh perhatian) -->
            <div v-if="summary && summary.students_needing_attention_count > 0" class="p-4 bg-rose-50 border border-rose-200 rounded-xl space-y-2">
                <div class="flex items-center gap-2 text-rose-800 font-bold text-sm">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-600 animate-ping" />
                    Peringatan Dini Wali Kelas (Early Warning): Terdapat {{ summary.students_needing_attention_count }} siswa memerlukan perhatian khusus!
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 pt-1">
                    <div
                        v-for="s in summary.students.filter(x => x.needs_attention)"
                        :key="s.student_id"
                        class="p-2.5 bg-white rounded-lg border border-rose-200 text-xs flex flex-col justify-between"
                    >
                        <div>
                            <span class="font-bold text-slate-900">{{ s.name }}</span>
                            <span class="text-slate-400 font-mono text-[11px] block">NIS: {{ s.nis }}</span>
                        </div>
                        <div class="text-rose-600 font-semibold mt-1">
                            {{ s.attention_reason }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Attendance Summary Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm">Rekapitulasi Kehadiran & Status Siswa</h3>
                    <span class="text-xs text-slate-500">Semester {{ active_term?.name || 'Aktif' }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <tr>
                                <th class="px-5 py-3.5 w-16">Absen</th>
                                <th class="px-5 py-3.5">NIS</th>
                                <th class="px-5 py-3.5">Nama Peserta Didik</th>
                                <th class="px-5 py-3.5 text-center">Hadir</th>
                                <th class="px-5 py-3.5 text-center">Sakit</th>
                                <th class="px-5 py-3.5 text-center">Izin</th>
                                <th class="px-5 py-3.5 text-center">Alpa</th>
                                <th class="px-5 py-3.5 text-center">% Hadir</th>
                                <th class="px-5 py-3.5">Status Peringatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="s in summary?.students" :key="s.student_id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3 font-mono text-xs text-slate-400">
                                    {{ s.roll_number || '-' }}
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-600">
                                    {{ s.nis }}
                                </td>
                                <td class="px-5 py-3 font-semibold text-slate-900">
                                    {{ s.name }}
                                </td>
                                <td class="px-5 py-3 text-center font-bold text-emerald-600">
                                    {{ s.present }}
                                </td>
                                <td class="px-5 py-3 text-center text-amber-600">
                                    {{ s.sick }}
                                </td>
                                <td class="px-5 py-3 text-center text-blue-600">
                                    {{ s.permitted }}
                                </td>
                                <td class="px-5 py-3 text-center font-bold" :class="s.absent > 0 ? 'text-rose-600' : 'text-slate-400'">
                                    {{ s.absent }}
                                </td>
                                <td class="px-5 py-3 text-center font-bold">
                                    <span :class="s.attendance_percentage < 85 ? 'text-rose-600' : 'text-slate-800'">
                                        {{ s.attendance_percentage }}%
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <span
                                        v-if="s.needs_attention"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500" />
                                        {{ s.attention_reason }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700"
                                    >
                                        Baik
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!summary || summary.students.length === 0">
                                <td colspan="9" class="px-5 py-12 text-center text-slate-400">
                                    Belum ada data siswa pada rombel ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
