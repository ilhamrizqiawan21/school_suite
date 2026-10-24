<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface ClassItem {
    id: number;
    name: string;
    grade_level: number;
}

interface StudentAttendanceItem {
    enrollment_id: number;
    student_id: number;
    nis: string;
    name: string;
    gender: 'L' | 'P';
    roll_number?: number | null;
    status: 'present' | 'sick' | 'permitted' | 'absent';
    notes: string;
    attendance_id?: number | null;
}

interface SessionItem {
    id: number;
    subject: { name: string; code: string };
    teacher: { name: string };
}

const props = defineProps<{
    classes: ClassItem[];
    selected_class?: ClassItem | null;
    date: string;
    students: StudentAttendanceItem[];
    sessions: SessionItem[];
}>();

const selectedClassId = ref(props.selected_class?.id ? String(props.selected_class.id) : '');
const selectedDate = ref(props.date);
const selectedSessionId = ref('');

const studentList = ref<StudentAttendanceItem[]>(JSON.parse(JSON.stringify(props.students)));

watch(() => props.students, (newVal) => {
    studentList.value = JSON.parse(JSON.stringify(newVal));
}, { deep: true });

const changeClassOrDate = () => {
    router.get(
        '/akademik/presensi',
        {
            class_group_id: selectedClassId.value || undefined,
            date: selectedDate.value,
        },
        { preserveState: true }
    );
};

const markAllPresent = () => {
    studentList.value.forEach((s) => {
        s.status = 'present';
    });
};

const form = useForm({
    session_id: '',
    date: props.date,
    records: [] as Array<{
        enrollment_id: number;
        status: string;
        notes?: string;
    }>,
});

const submitAttendance = () => {
    form.session_id = selectedSessionId.value;
    form.date = selectedDate.value;
    form.records = studentList.value.map((s) => ({
        enrollment_id: s.enrollment_id,
        status: s.status,
        notes: s.notes || undefined,
    }));

    form.post('/akademik/presensi', {
        preserveScroll: true,
    });
};

const summary = computed(() => {
    const counts = { present: 0, sick: 0, permitted: 0, absent: 0 };
    studentList.value.forEach((s) => {
        if (counts[s.status] !== undefined) {
            counts[s.status]++;
        }
    });
    return counts;
});
</script>

<template>
    <AppLayout title="Presensi Siswa">
        <Head title="Presensi Siswa — School Suite" />

        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Presensi Siswa</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Pencatatan kehadiran harian dan sesi pelajaran dengan audit log riwayat koreksi.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="markAllPresent"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-xs"
                    >
                        ✓ Tandai Semua Hadir
                    </button>
                    <button
                        type="button"
                        @click="submitAttendance"
                        :disabled="form.processing || studentList.length === 0"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition shadow-xs"
                    >
                        Simpan Presensi
                    </button>
                </div>
            </div>

            <!-- Filters & Summary Bar -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs md:col-span-2 flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Rombongan Belajar</label>
                        <select
                            v-model="selectedClassId"
                            @change="changeClassOrDate"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                                Kelas {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Tanggal</label>
                        <input
                            v-model="selectedDate"
                            @change="changeClassOrDate"
                            type="date"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <div v-if="sessions.length > 0" class="flex-1">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Sesi Pelajaran (Opsional)</label>
                        <select
                            v-model="selectedSessionId"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Presensi Harian Sekolah</option>
                            <option v-for="ses in sessions" :key="ses.id" :value="String(ses.id)">
                                {{ ses.subject.name }} ({{ ses.teacher.name }})
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Live Summary Pill Cards -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex items-center justify-around text-center">
                    <div>
                        <div class="text-xs font-semibold text-emerald-600">Hadir</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">{{ summary.present }}</div>
                    </div>
                    <div class="w-px h-8 bg-slate-200" />
                    <div>
                        <div class="text-xs font-semibold text-amber-600">Sakit</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">{{ summary.sick }}</div>
                    </div>
                    <div class="w-px h-8 bg-slate-200" />
                    <div>
                        <div class="text-xs font-semibold text-blue-600">Izin</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">{{ summary.permitted }}</div>
                    </div>
                    <div class="w-px h-8 bg-slate-200" />
                    <div>
                        <div class="text-xs font-semibold text-red-600">Alpa</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">{{ summary.absent }}</div>
                    </div>
                </div>
            </div>

            <!-- Table of Students -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <tr>
                                <th class="px-5 py-3.5 w-16">No</th>
                                <th class="px-5 py-3.5">NIS</th>
                                <th class="px-5 py-3.5">Nama Peserta Didik</th>
                                <th class="px-5 py-3.5">Status Kehadiran</th>
                                <th class="px-5 py-3.5">Keterangan / Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="(s, index) in studentList" :key="s.enrollment_id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3 text-slate-400 font-mono text-xs">
                                    {{ s.roll_number || (index + 1) }}
                                </td>
                                <td class="px-5 py-3 font-medium text-slate-900">
                                    {{ s.nis }}
                                </td>
                                <td class="px-5 py-3 font-semibold text-slate-900">
                                    {{ s.name }}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-50 gap-1">
                                        <button
                                            type="button"
                                            @click="s.status = 'present'"
                                            :class="s.status === 'present' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-600 hover:text-slate-900'"
                                            class="px-2.5 py-1 text-xs rounded-md transition"
                                        >
                                            Hadir
                                        </button>
                                        <button
                                            type="button"
                                            @click="s.status = 'sick'"
                                            :class="s.status === 'sick' ? 'bg-amber-500 text-white font-bold' : 'text-slate-600 hover:text-slate-900'"
                                            class="px-2.5 py-1 text-xs rounded-md transition"
                                        >
                                            Sakit
                                        </button>
                                        <button
                                            type="button"
                                            @click="s.status = 'permitted'"
                                            :class="s.status === 'permitted' ? 'bg-blue-600 text-white font-bold' : 'text-slate-600 hover:text-slate-900'"
                                            class="px-2.5 py-1 text-xs rounded-md transition"
                                        >
                                            Izin
                                        </button>
                                        <button
                                            type="button"
                                            @click="s.status = 'absent'"
                                            :class="s.status === 'absent' ? 'bg-rose-600 text-white font-bold' : 'text-slate-600 hover:text-slate-900'"
                                            class="px-2.5 py-1 text-xs rounded-md transition"
                                        >
                                            Alpa
                                        </button>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <input
                                        v-model="s.notes"
                                        type="text"
                                        placeholder="Keterangan opsional..."
                                        class="w-full px-2.5 py-1 text-xs border border-slate-200 rounded-md focus:ring-1 focus:ring-indigo-500"
                                    />
                                </td>
                            </tr>
                            <tr v-if="studentList.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                    Belum ada siswa yang terdaftar di rombongan belajar ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

