<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { SharedProps } from '../../../types';
import RowActionButton from '@/components/RowActionButton.vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface TermItem {
    id: number;
    name: string;
    academic_year?: { name: string };
}

interface ClassItem {
    id: number;
    name: string;
    grade_level: number;
}

interface TeacherItem {
    id: number;
    name: string;
}

interface SubjectItem {
    id: number;
    name: string;
    code: string;
}

interface ScheduleItem {
    id: number;
    term_id: number;
    class_group_id: number;
    subject_id: number;
    teacher_id: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    room?: string | null;
    status: 'draft' | 'published';
    class_group: ClassItem;
    subject: SubjectItem;
    teacher: TeacherItem;
}

const props = defineProps<{
    active_term?: TermItem | null;
    terms: TermItem[];
    classes: ClassItem[];
    teachers: TeacherItem[];
    subjects: SubjectItem[];
    schedules: ScheduleItem[];
    filters: {
        class_group_id?: string;
        teacher_id?: string;
    };
    draft_count: number;
    can_manage: boolean;
    is_student: boolean;
}>();

const page = usePage<SharedProps>();
const selectedTerm = ref(props.active_term?.id ? String(props.active_term.id) : '');
const editingId = ref<number | null>(null);
const publishedCount = computed(() => props.schedules.filter(slot => slot.status === 'published').length);
const selectedClass = ref(props.filters.class_group_id || '');
const selectedTeacher = ref(props.filters.teacher_id || '');
const showAddModal = ref(false);

const days = [
    { id: 1, name: 'Senin' },
    { id: 2, name: 'Selasa' },
    { id: 3, name: 'Rabu' },
    { id: 4, name: 'Kamis' },
    { id: 5, name: 'Jumat' },
    { id: 6, name: 'Sabtu' },
    { id: 7, name: 'Minggu' },
];

const applyFilters = () => {
    router.get(
        '/akademik/jadwal',
        {
            term_id: selectedTerm.value || undefined,
            class_group_id: selectedClass.value || undefined,
            teacher_id: selectedTeacher.value || undefined,
        },
        { preserveState: true }
    );
};

const form = useForm({
    term_id: props.active_term?.id ? String(props.active_term.id) : '',
    class_group_id: '',
    subject_id: '',
    teacher_id: '',
    day_of_week: 1,
    start_time: '07:30',
    end_time: '09:00',
    room: '',
});

const openAdd = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.term_id = selectedTerm.value;
    showAddModal.value = true;
};
const openEdit = (slot: ScheduleItem) => {
    editingId.value = slot.id;
    form.clearErrors();
    form.term_id = String(slot.term_id);
    form.class_group_id = String(slot.class_group_id);
    form.subject_id = String(slot.subject_id);
    form.teacher_id = String(slot.teacher_id);
    form.day_of_week = slot.day_of_week;
    form.start_time = slot.start_time.substring(0, 5);
    form.end_time = slot.end_time.substring(0, 5);
    form.room = slot.room ?? '';
    showAddModal.value = true;
};
const submitAdd = () => {
    const options = { preserveScroll: true, onSuccess: () => { showAddModal.value = false; } };
    if (editingId.value) form.put(`/akademik/jadwal/${editingId.value}`, options);
    else form.post('/akademik/jadwal', options);
};
const resetFilters = () => { selectedClass.value = ''; selectedTeacher.value = ''; applyFilters(); };
const changeTerm = () => router.get('/akademik/jadwal', { term_id: selectedTerm.value });

const publishAll = () => {
    if (!props.active_term) return;
    if (confirm(`Terbitkan ${props.draft_count} jadwal draf untuk seluruh kelas pada semester ini? Filter kelas/guru tidak membatasi penerbitan.`)) {
        router.post('/akademik/jadwal/terbitkan', {
            term_id: props.active_term.id,
        });
    }
};

const deleteSchedule = (id: number) => {
    if (confirm('Hapus slot jadwal ini? Jadwal yang sudah dipakai untuk pertemuan atau guru pengganti tidak dapat dihapus.')) {
        router.delete(`/akademik/jadwal/${id}`);
    }
};
</script>

<template>
    <AppLayout title="Jadwal Pelajaran">
        <Head title="Jadwal Pelajaran — School Suite" />

        <div class="view space-y-6 schedule-page">
            <!-- Header bar -->
            <div class="page-heading compact-heading">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Jadwal Pelajaran</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        {{ is_student ? 'Jadwal pelajaran kelasmu pada periode aktif.' : 'Penyusunan jadwal mingguan dengan deteksi benturan guru, rombel, dan ruang kelas.' }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        v-if="can_manage && draft_count > 0"
                        type="button"
                        @click="publishAll"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition shadow-xs"
                    >
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                        Terbitkan {{ draft_count }} Draf
                    </button>
                    <button
                        v-if="can_manage"
                        type="button"
                        :disabled="!active_term" @click="openAdd"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition shadow-xs"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Slot Jadwal
                    </button>
                </div>
            </div>

            <div v-if="page.props.flash?.message" class="panel p-4" role="status">{{ page.props.flash.message }}</div>
            <div v-if="page.props.errors?.schedule || page.props.errors?.term_id" class="panel p-4 text-red-700" role="alert">{{ page.props.errors.schedule ?? page.props.errors.term_id }}</div>
            <div v-if="!is_student && terms.length" class="panel p-4">
                <label class="text-sm">Semester <select v-model="selectedTerm" aria-label="Semester jadwal" class="max-w-full rounded-lg border px-3 py-2" @change="changeTerm"><option v-if="!active_term" value="">Pilih semester...</option><option v-for="term in terms" :key="term.id" :value="String(term.id)">{{ term.academic_year?.name }} — {{ term.name }}</option></select></label>
            </div>
            <p v-if="!active_term" class="panel p-4">Belum ada semester aktif atau terpilih. Atur periode akademik untuk menyusun jadwal.</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="panel p-4"><p>Slot sesuai filter</p><strong class="text-2xl">{{ schedules.length }}</strong></div>
                <div class="panel p-4"><p>Terbit sesuai filter</p><strong class="text-2xl">{{ publishedCount }}</strong></div>
                <div v-if="can_manage" class="panel p-4"><p>Draf seluruh semester terpilih</p><strong class="text-2xl">{{ draft_count }}</strong></div>
            </div>
            <!-- Filters -->
            <div v-if="!is_student" class="schedule-filter bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <select
                        aria-label="Filter kelas" v-model="selectedClass"
                        @change="applyFilters"
                        class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Semua Rombongan Belajar</option>
                        <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                            Kelas {{ c.name }}
                        </option>
                    </select>
                </div>
                <div class="flex-1">
                    <select
                        aria-label="Filter guru" v-model="selectedTeacher"
                        @change="applyFilters"
                        class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Semua Guru Pengajar</option>
                        <option v-for="t in teachers" :key="t.id" :value="String(t.id)">
                            {{ t.name }}
                        </option>
                    </select>
                </div>
            </div>
            <button v-if="!is_student" type="button" class="secondary-button" @click="resetFilters">Reset filter</button>
            <div v-if="is_student" class="schedule-filter schedule-student-context">
                <span>Jadwal kelas</span>
                <strong>{{ classes[0]?.name ?? 'Belum ada kelas aktif' }}</strong>
                <span>{{ active_term?.name ?? 'Periode belum tersedia' }}</span>
            </div>

            <!-- Weekly Timetable Grid -->
            <div v-if="schedules.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="d in days"
                    :key="d.id"
                    class="schedule-day-card bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs flex flex-col"
                >
                    <div class="schedule-day-header bg-slate-50 px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800 text-sm">{{ d.name }}</h3>
                        <span class="text-xs text-slate-400 font-medium">
                            {{ schedules.filter(s => s.day_of_week === d.id).length }} Sesi
                        </span>
                    </div>

                    <div class="p-3 divide-y divide-slate-100 flex-1 space-y-2">
                        <div
                            v-for="s in schedules.filter(s => s.day_of_week === d.id)"
                            :key="s.id"
                            class="schedule-session p-3 rounded-lg border border-slate-100 bg-slate-50/50 hover:bg-white hover:border-indigo-200 transition group relative"
                        >
                            <div class="flex items-start justify-between">
                                <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-sm">
                                    {{ s.start_time.substring(0, 5) }} - {{ s.end_time.substring(0, 5) }}
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        :class="s.status === 'published' ? 'text-emerald-700 bg-emerald-50' : 'text-amber-700 bg-amber-50'"
                                        class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded-sm"
                                    >
                                        {{ s.status === 'published' ? 'Terbit' : 'Draf' }}
                                    </span>
                                    <RowActionButton v-if="can_manage" action="edit" label="Ubah" :aria-label="`Ubah jadwal ${s.subject.name} ${s.start_time}`" @click="openEdit(s)" />
                                    <RowActionButton v-if="can_manage" action="delete" label="Hapus" :aria-label="`Hapus jadwal ${s.subject.name} ${s.start_time}`" @click="deleteSchedule(s.id)" />
                                </div>
                            </div>

                            <div class="mt-2 font-bold text-slate-900 text-sm">
                                {{ s.subject.name }} ({{ s.subject.code }})
                            </div>
                            <div class="schedule-meta text-xs text-slate-600 mt-0.5 flex items-center justify-between">
                                <span>Kelas {{ s.class_group.name }}</span>
                                <span v-if="s.room" class="schedule-room text-slate-400 font-mono">{{ s.room }}</span>
                            </div>
                            <div class="schedule-teacher text-xs text-slate-500 mt-1 italic">
                                {{ s.teacher.name }}
                            </div>
                        </div>

                        <div
                            v-if="schedules.filter(s => s.day_of_week === d.id).length === 0"
                            class="schedule-empty py-8 text-center text-xs text-slate-400 italic"
                        >
                            Tidak ada jadwal
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="schedule-no-results">
                <strong>{{ is_student ? 'Belum ada jadwal terbit untuk kelasmu' : 'Belum ada jadwal pelajaran' }}</strong>
                <span>{{ is_student ? 'Jadwal yang sudah diterbitkan sekolah akan muncul di sini.' : 'Tambahkan slot jadwal atau ubah filter untuk melihat jadwal.' }}</span>
            </div>
        </div>

        <!-- Modal Tambah Slot Jadwal -->
        <div v-if="can_manage && showAddModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div role="dialog" aria-modal="true" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">{{ editingId ? 'Ubah Jadwal Pelajaran' : 'Tambah Slot Jadwal Pelajaran' }}</h3>
                    <button type="button" @click="showAddModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <ul v-if="Object.keys(form.errors).length" role="alert" class="text-sm text-red-700"><li v-for="(error, field) in form.errors" :key="field">{{ error }}</li></ul>
                <p v-if="editingId" class="text-xs text-slate-500">Perubahan disimpan sebagai draf dan perlu diterbitkan kembali. Jadwal dengan riwayat pertemuan atau guru pengganti tidak dapat diubah.</p>

                <form @submit.prevent="submitAdd" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Hari *</label>
                            <select v-model="form.day_of_week" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option v-for="d in days" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Rombel (Kelas) *</label>
                            <select v-model="form.class_group_id" required class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="">Pilih rombel...</option>
                                <option v-for="c in classes" :key="c.id" :value="String(c.id)">Kelas {{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Mata Pelajaran *</label>
                            <select v-model="form.subject_id" required class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="">Pilih mapel...</option>
                                <option v-for="s in subjects" :key="s.id" :value="String(s.id)">{{ s.name }} ({{ s.code }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Guru Pengajar *</label>
                            <select v-model="form.teacher_id" required class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="">Pilih guru...</option>
                                <option v-for="t in teachers" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Mulai *</label>
                            <input v-model="form.start_time" type="time" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Selesai *</label>
                            <input v-model="form.end_time" type="time" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Ruang / Lab</label>
                            <input v-model="form.room" type="text" placeholder="contoh: Lab IPA" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.schedule-page { min-width: 0; }
.schedule-session > .flex { flex-wrap: wrap; gap: .5rem; }
.schedule-student-context { flex-wrap: wrap; }
@media (max-width: 640px) { .schedule-page .page-heading { flex-direction: column; align-items: stretch; gap: 1rem; } }

.schedule-student-context { display: flex; align-items: center; gap: .55rem; min-height: 48px; padding: .75rem 1rem; color: var(--ink-soft); background: var(--surface); border: 1px solid var(--line); border-radius: .85rem; font-size: .84rem; font-weight: 650; }
.schedule-student-context::before { content: ''; width: .55rem; height: .55rem; flex: 0 0 auto; border-radius: 50%; background: var(--primary); box-shadow: 0 0 0 4px var(--primary-soft); }
</style>

