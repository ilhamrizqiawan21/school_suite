<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface ClassGroup {
    id: number;
    name: string;
    grade_level: number;
}

interface ComponentItem {
    id: number;
    name: string;
    code: string;
    weight_percentage: number;
    passing_grade: number;
}

interface SchemeItem {
    id: number;
    name: string;
    code: string;
    passing_grade_default: number;
    components: ComponentItem[];
}

interface TermItem {
    id: number;
    name: string;
    semester: number;
}

interface StudentScoreRow {
    enrollment_id: number;
    student_id: number;
    roll_number?: number | null;
    nis: string;
    name: string;
    gender: 'L' | 'P';
    raw_score: number | string | null;
    remedial_score: number | string | null;
    final_score?: number | null;
    notes?: string | null;
}

const props = defineProps<{
    active_term?: TermItem | null;
    classes: ClassGroup[];
    scheme?: SchemeItem | null;
    components: ComponentItem[];
    selected_class_id?: string;
    selected_component_id?: string;
    students: StudentScoreRow[];
}>();

const selectedClassId = ref(props.selected_class_id || (props.classes[0]?.id ? String(props.classes[0].id) : ''));
const selectedComponentId = ref(props.selected_component_id || (props.components[0]?.id ? String(props.components[0].id) : ''));

const currentComponent = computed(() => {
    return props.components.find((c) => String(c.id) === selectedComponentId.value) || props.components[0] || null;
});

const passingGrade = computed(() => {
    return currentComponent.value ? Number(currentComponent.value.passing_grade) : 75;
});

// Salin state lokal untuk baris penilaian
const localRows = ref<StudentScoreRow[]>(JSON.parse(JSON.stringify(props.students)));

watch(
    () => props.students,
    (newVal) => {
        localRows.value = JSON.parse(JSON.stringify(newVal));
    },
    { deep: true }
);

const onFilterChange = () => {
    router.get(
        '/penilaian/skor',
        {
            class_group_id: selectedClassId.value || undefined,
            component_id: selectedComponentId.value || undefined,
        },
        { preserveState: true }
    );
};

// Hitung preview nilai akhir per baris
const calculateRowFinal = (row: StudentScoreRow): number | null => {
    const raw = row.raw_score !== null && row.raw_score !== '' ? Number(row.raw_score) : null;
    const rem = row.remedial_score !== null && row.remedial_score !== '' ? Number(row.remedial_score) : null;
    const kktp = passingGrade.value;

    if (raw === null) {
        return null;
    }

    if (rem === null) {
        return raw;
    }

    if (raw >= kktp) {
        return raw;
    }

    const effectiveRem = Math.min(rem, kktp);
    return Math.max(raw, effectiveRem);
};

const form = useForm({
    component_id: selectedComponentId.value,
    scores: [] as {
        enrollment_id: number;
        raw_score: number | null;
        remedial_score: number | null;
        notes: string | null;
    }[],
});

const isSaving = ref(false);

const submitScores = () => {
    isSaving.value = true;
    form.component_id = selectedComponentId.value;
    form.scores = localRows.value.map((row) => ({
        enrollment_id: row.enrollment_id,
        raw_score: row.raw_score !== null && row.raw_score !== '' ? Number(row.raw_score) : null,
        remedial_score: row.remedial_score !== null && row.remedial_score !== '' ? Number(row.remedial_score) : null,
        notes: row.notes || null,
    }));

    form.post('/penilaian/skor', {
        preserveScroll: true,
        onFinish: () => {
            isSaving.value = false;
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Input Nilai Guru" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Input Nilai Guru</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Input nilai formatif, sumatif, dan remedial secara cepat dengan validasi otomatis & formula ketuntasan.
                    </p>
                </div>

                <div v-if="props.scheme" class="flex items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                        Skema: {{ props.scheme.name }} (KKTP: {{ passingGrade }})
                    </span>
                </div>
            </div>

            <!-- Filter Rombel & Komponen -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center gap-4">
                <div class="w-full sm:w-64">
                    <label for="class-select" class="block text-xs font-semibold text-slate-600 mb-1">Pilih Kelas / Rombel</label>
                    <select
                        id="class-select"
                        v-model="selectedClassId"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="onFilterChange"
                    >
                        <option value="" disabled>-- Pilih Kelas --</option>
                        <option v-for="cls in props.classes" :key="cls.id" :value="String(cls.id)">
                            {{ cls.name }} (Tingkat {{ cls.grade_level }})
                        </option>
                    </select>
                </div>

                <div class="w-full sm:w-64">
                    <label for="component-select" class="block text-xs font-semibold text-slate-600 mb-1">Pilih Komponen Nilai</label>
                    <select
                        id="component-select"
                        v-model="selectedComponentId"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="onFilterChange"
                    >
                        <option value="" disabled>-- Pilih Komponen --</option>
                        <option v-for="comp in props.components" :key="comp.id" :value="String(comp.id)">
                            {{ comp.name }} (Bobot: {{ comp.weight_percentage }}%, KKTP: {{ comp.passing_grade }})
                        </option>
                    </select>
                </div>

                <div class="ml-auto flex items-center gap-3">
                    <button
                        type="button"
                        :disabled="isSaving || localRows.length === 0"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 text-white rounded-lg text-sm font-semibold shadow-xs transition-colors"
                        @click="submitScores"
                    >
                        <svg
                            v-if="isSaving"
                            class="animate-spin -ml-1 mr-1 h-4 w-4 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Semua Nilai' }}</span>
                    </button>
                </div>
            </div>

            <!-- Tabel Spreadsheet-like Input Nilai -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-800 text-base">Lembar Penilaian Siswa</h2>
                        <p class="text-xs text-slate-500">
                            Aturan Remedial: Jika siswa remedial, nilai akhir maksimal adalah KKTP ({{ passingGrade }}).
                        </p>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">Total: {{ localRows.length }} Siswa</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 border-collapse">
                        <thead class="bg-slate-50 text-slate-700 font-semibold text-xs border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-3 w-12 text-center">No</th>
                                <th class="py-3 px-3 w-28">NIS</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-3 w-32 text-center">Nilai Mentah</th>
                                <th class="py-3 px-3 w-32 text-center">Nilai Remedial</th>
                                <th class="py-3 px-3 w-28 text-center">Nilai Akhir</th>
                                <th class="py-3 px-3 w-28 text-center">Status</th>
                                <th class="py-3 px-4">Catatan Guru</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-normal">
                            <tr
                                v-for="(row, idx) in localRows"
                                :key="row.enrollment_id"
                                class="hover:bg-slate-50 transition-colors"
                            >
                                <td class="py-2.5 px-3 text-center text-xs text-slate-500">
                                    {{ row.roll_number || idx + 1 }}
                                </td>
                                <td class="py-2.5 px-3 font-mono text-xs text-slate-700">
                                    {{ row.nis }}
                                </td>
                                <td class="py-2.5 px-4 font-medium text-slate-900">
                                    {{ row.name }}
                                </td>
                                <!-- Kolom Input Nilai Mentah -->
                                <td class="py-2.5 px-3">
                                    <input
                                        v-model.number="row.raw_score"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="100"
                                        placeholder="0-100"
                                        class="w-full text-center rounded-md border-slate-300 py-1.5 px-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </td>
                                <!-- Kolom Input Nilai Remedial -->
                                <td class="py-2.5 px-3">
                                    <input
                                        v-model.number="row.remedial_score"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="100"
                                        placeholder="Remedial"
                                        class="w-full text-center rounded-md border-slate-300 py-1.5 px-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-amber-50/40"
                                    />
                                </td>
                                <!-- Preview Nilai Akhir Otomatis -->
                                <td class="py-2.5 px-3 text-center font-bold font-mono text-sm">
                                    <span
                                        v-if="calculateRowFinal(row) !== null"
                                        :class="[
                                            (calculateRowFinal(row) ?? 0) >= passingGrade
                                                ? 'text-emerald-700'
                                                : 'text-rose-600',
                                        ]"
                                    >
                                        {{ calculateRowFinal(row) }}
                                    </span>
                                    <span v-else class="text-slate-300">-</span>
                                </td>
                                <!-- Badge Ketuntasan -->
                                <td class="py-2.5 px-3 text-center">
                                    <span
                                        v-if="calculateRowFinal(row) !== null"
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold',
                                            (calculateRowFinal(row) ?? 0) >= passingGrade
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : 'bg-rose-100 text-rose-800',
                                        ]"
                                    >
                                        {{ (calculateRowFinal(row) ?? 0) >= passingGrade ? 'Tuntas' : 'Belum' }}
                                    </span>
                                    <span v-else class="text-xs text-slate-400">Kosong</span>
                                </td>
                                <!-- Kolom Catatan Guru -->
                                <td class="py-2.5 px-4">
                                    <input
                                        v-model="row.notes"
                                        type="text"
                                        maxlength="255"
                                        placeholder="Catatan kemajuan siswa..."
                                        class="w-full rounded-md border-slate-300 py-1.5 px-2 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </td>
                            </tr>
                            <tr v-if="localRows.length === 0">
                                <td colspan="8" class="text-center py-10 text-slate-400 text-sm">
                                    Tidak ada data siswa ditemukan untuk kelas dan komponen ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

