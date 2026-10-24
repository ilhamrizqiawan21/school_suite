<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';
import type { ClassGroup, Teacher } from '../../../types';

interface AcademicYearItem {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
}

const props = defineProps<{
    academic_years: AcademicYearItem[];
    active_year?: AcademicYearItem | null;
    classes: ClassGroup[];
    teachers: Teacher[];
}>();

const selectedYear = ref(props.active_year?.id ? String(props.active_year.id) : '');

const showAddClassModal = ref(false);
const showAssignHomeroomModal = ref(false);
const selectedClassForHomeroom = ref<ClassGroup | null>(null);

const changeYear = () => {
    router.get('/akademik/kelas', { academic_year_id: selectedYear.value }, { preserveState: true });
};

const classForm = useForm({
    academic_year_id: props.active_year?.id ? String(props.active_year.id) : '',
    grade_level: 7,
    name: '',
    homeroom_teacher_id: '',
    capacity: 32,
});

const submitAddClass = () => {
    classForm.academic_year_id = selectedYear.value;
    classForm.post('/akademik/kelas', {
        onSuccess: () => {
            showAddClassModal.value = false;
            classForm.reset('name', 'homeroom_teacher_id');
        },
    });
};

const homeroomForm = useForm({
    teacher_id: '',
});

const openHomeroomModal = (cls: ClassGroup) => {
    selectedClassForHomeroom.value = cls;
    homeroomForm.teacher_id = cls.homeroom_teacher_id ? String(cls.homeroom_teacher_id) : '';
    showAssignHomeroomModal.value = true;
};

const submitAssignHomeroom = () => {
    if (!selectedClassForHomeroom.value) return;

    homeroomForm.post(`/akademik/kelas/${selectedClassForHomeroom.value.id}/wali-kelas`, {
        onSuccess: () => {
            showAssignHomeroomModal.value = false;
        },
    });
};
</script>

<template>
    <AppLayout title="Rombongan Belajar & Penugasan Wali Kelas">
        <Head title="Rombongan Belajar — School Suite" />

        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Rombongan Belajar (Kelas)</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Struktur kelas per tahun ajaran, kapasitas, dan penetapan wali kelas.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-semibold text-slate-600">Tahun Ajaran:</label>
                        <select
                            v-model="selectedYear"
                            @change="changeYear"
                            class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 font-medium text-slate-800"
                        >
                            <option v-for="y in academic_years" :key="y.id" :value="String(y.id)">
                                {{ y.name }} {{ y.is_active ? '(Aktif)' : '' }}
                            </option>
                        </select>
                    </div>

                    <button
                        type="button"
                        @click="showAddClassModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition shadow-xs"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Rombel
                    </button>
                </div>
            </div>

            <!-- Grid Rombel -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="cls in classes"
                    :key="cls.id"
                    class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs hover:border-indigo-300 transition-all flex flex-col justify-between"
                >
                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="inline-block px-2 py-0.5 text-xs font-bold rounded-sm bg-slate-100 text-slate-700 mb-1">
                                    Tingkat {{ cls.grade_level }}
                                </span>
                                <h3 class="text-xl font-bold text-slate-900">Kelas {{ cls.name }}</h3>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                {{ cls.enrollments_count || 0 }} / {{ cls.capacity }} Siswa
                            </span>
                        </div>

                        <!-- Progress Bar Kapasitas -->
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div
                                class="bg-indigo-600 h-1.5 rounded-full"
                                :style="{ width: `${Math.min(100, (((cls.enrollments_count || 0) / cls.capacity) * 100))}%` }"
                            />
                        </div>

                        <!-- Wali Kelas -->
                        <div class="pt-2 border-t border-slate-100 text-xs">
                            <div class="text-slate-500 font-medium">Wali Kelas:</div>
                            <div v-if="cls.homeroom_teacher" class="font-semibold text-slate-800 mt-0.5 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500" />
                                {{ cls.homeroom_teacher.name }}
                            </div>
                            <div v-else class="text-amber-600 italic mt-0.5 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-400" />
                                Belum ditetapkan
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between">
                        <button
                            type="button"
                            @click="openHomeroomModal(cls)"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline"
                        >
                            {{ cls.homeroom_teacher ? 'Ubah Wali Kelas' : 'Tetapkan Wali Kelas' }}
                        </button>
                    </div>
                </div>

                <div v-if="classes.length === 0" class="col-span-full bg-white rounded-xl border border-dashed border-slate-300 p-12 text-center text-slate-400">
                    Belum ada rombongan belajar pada tahun ajaran ini. Klik "Tambah Rombel" untuk memulai.
                </div>
            </div>
        </div>

        <!-- Modal Tambah Rombel -->
        <div v-if="showAddClassModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Tambah Rombongan Belajar</h3>
                    <button type="button" @click="showAddClassModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitAddClass" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat Kelas (Grade Level) *</label>
                        <select v-model="classForm.grade_level" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option :value="7">Kelas 7 (SMP / MTs)</option>
                            <option :value="8">Kelas 8 (SMP / MTs)</option>
                            <option :value="9">Kelas 9 (SMP / MTs)</option>
                            <option :value="10">Kelas 10 (SMA / SMK)</option>
                            <option :value="11">Kelas 11 (SMA / SMK)</option>
                            <option :value="12">Kelas 12 (SMA / SMK)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Rombel *</label>
                        <input v-model="classForm.name" type="text" placeholder="contoh: 7A, 8B, X MIPA 1" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        <div v-if="classForm.errors.name" class="text-xs text-red-600 mt-1">{{ classForm.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kapasitas Maksimal Siswa</label>
                        <input v-model="classForm.capacity" type="number" min="1" max="60" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Wali Kelas (Opsional)</label>
                        <select v-model="classForm.homeroom_teacher_id" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option value="">(Belum ditetapkan)</option>
                            <option v-for="t in teachers" :key="t.id" :value="String(t.id)">{{ t.name }} ({{ t.nip || 'Non-NIP' }})</option>
                        </select>
                        <div v-if="classForm.errors.homeroom_teacher_id" class="text-xs text-red-600 mt-1">{{ classForm.errors.homeroom_teacher_id }}</div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showAddClassModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="classForm.processing" class="px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Simpan Rombel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Tetapkan Wali Kelas -->
        <div v-if="showAssignHomeroomModal && selectedClassForHomeroom" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Tetapkan Wali Kelas {{ selectedClassForHomeroom.name }}</h3>
                    <button type="button" @click="showAssignHomeroomModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitAssignHomeroom" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Guru sebagai Wali Kelas *</label>
                        <select v-model="homeroomForm.teacher_id" required class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option value="">Pilih guru...</option>
                            <option v-for="t in teachers" :key="t.id" :value="String(t.id)">{{ t.name }} ({{ t.nip || 'Non-NIP' }})</option>
                        </select>
                        <div v-if="homeroomForm.errors.teacher_id" class="text-xs text-red-600 mt-1">{{ homeroomForm.errors.teacher_id }}</div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showAssignHomeroomModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="homeroomForm.processing" class="px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Simpan Penetapan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
