<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';
import type { ClassGroup, Teacher, SharedProps } from '../../../types';

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
    can_manage: boolean;
    terms: Array<{ id: number; name: string; is_active: boolean }>;
    active_term?: { id: number; name: string; is_active: boolean } | null;
    stats: { classes: number; students: number; without_homeroom: number };
}>();

const page = usePage<SharedProps>();
const search = ref('');
const grade = ref('');
const selectedTerm = ref(props.active_term?.id ? String(props.active_term.id) : '');
const editingId = ref<number | null>(null);
const visibleClasses = computed(() => props.classes.filter(cls => (!grade.value || cls.grade_level === Number(grade.value)) && cls.name.toLocaleLowerCase().includes(search.value.trim().toLocaleLowerCase())));
const enrollmentCount = (cls: ClassGroup) => Number(cls.enrollments_count || 0);
const capacityPercent = (cls: ClassGroup) => Math.min(100, Math.round(enrollmentCount(cls) / Math.max(1, cls.capacity) * 100));
const selectedYear = ref(props.active_year?.id ? String(props.active_year.id) : '');

const showAddClassModal = ref(false);
const showAssignHomeroomModal = ref(false);
const selectedClassForHomeroom = ref<ClassGroup | null>(null);
const activeOverlay = computed(() => showAddClassModal.value || showAssignHomeroomModal.value);
const overlayRoot = ref<HTMLElement | null>(null);
let overlayTrigger: HTMLElement | null = null;
watch(activeOverlay, async (open) => {
    if (open) {
        overlayTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        await nextTick();
        overlayRoot.value?.querySelector<HTMLElement>('input, select, button')?.focus();
    } else {
        await nextTick();
        overlayTrigger?.focus();
        overlayTrigger = null;
    }
});
const handleOverlayKeydown = (event: KeyboardEvent) => {
    if (!activeOverlay.value) return;
    if (event.key === 'Escape') { showAddClassModal.value = false; showAssignHomeroomModal.value = false; }
    if (event.key !== 'Tab' || !overlayRoot.value) return;
    const focusable = Array.from(overlayRoot.value.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]), select:not([disabled])'));
    const first = focusable[0];
    const last = focusable.at(-1);
    if (first && last && ((event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last))) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
    }
};
onMounted(() => document.addEventListener('keydown', handleOverlayKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', handleOverlayKeydown));

const changeYear = () => {
    router.get('/akademik/kelas', { academic_year_id: selectedYear.value });
};

const changeTerm = () => router.get('/akademik/kelas', { academic_year_id: selectedYear.value, term_id: selectedTerm.value });
const openAdd = () => { editingId.value = null; classForm.reset(); classForm.clearErrors(); showAddClassModal.value = true; };
const openEdit = (cls: ClassGroup) => {
    editingId.value = cls.id;
    classForm.clearErrors();
    classForm.name = cls.name;
    classForm.grade_level = cls.grade_level;
    classForm.capacity = cls.capacity;
    showAddClassModal.value = true;
};
const deleteClass = (cls: ClassGroup) => {
    if (!window.confirm(`Hapus rombel ${cls.name}? Rombel dengan wali kelas, penugasan, atau riwayat akademik tidak dapat dihapus.`)) return;
    router.delete(`/akademik/kelas/${cls.id}`, { preserveScroll: true });
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
    const options = {
        preserveScroll: true,
        onSuccess: () => { showAddClassModal.value = false; classForm.reset('name', 'homeroom_teacher_id'); },
    };
    if (editingId.value) classForm.put(`/akademik/kelas/${editingId.value}`, options);
    else classForm.post('/akademik/kelas', options);
};

const homeroomForm = useForm({
    teacher_id: '',
});

const openHomeroomModal = (cls: ClassGroup) => {
    selectedClassForHomeroom.value = cls;
    homeroomForm.clearErrors();
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

        <div class="view class-page space-y-5">
            <div class="page-heading compact-heading">
                <div>
                    <p class="class-page-kicker">Akademik · Struktur kelas</p>
                    <h1 class="class-page-title">Rombongan belajar</h1>
                    <p class="class-page-summary">Atur rombel, kapasitas siswa, dan penetapan wali kelas untuk setiap tahun ajaran.</p>
                </div>

                <div class="class-heading-controls flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-semibold text-slate-600">Tahun Ajaran:</label>
                        <select
                            aria-label="Tahun ajaran" v-model="selectedYear"
                            @change="changeYear"
                            class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 font-medium text-slate-800"
                        >
                            <option v-for="y in academic_years" :key="y.id" :value="String(y.id)">
                                {{ y.name }} {{ y.is_active ? '(Aktif)' : '' }}
                            </option>
                        </select>
                    </div>
                    <select v-if="terms.length" v-model="selectedTerm" aria-label="Semester" class="rounded-lg border px-3 py-2 text-sm" @change="changeTerm"><option v-for="term in terms" :key="term.id" :value="String(term.id)">{{ term.name }}{{ term.is_active ? ' (Aktif)' : '' }}</option></select>
                    <button v-if="can_manage" type="button" class="class-action-button class-action-add" :disabled="!selectedYear" @click="openAdd"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>Tambah rombel</button>
                </div>
            </div>

            <div v-if="page.props.flash?.message" class="panel p-4" role="status">{{ page.props.flash.message }}</div>
            <div v-if="page.props.errors?.class_group" class="panel p-4 text-red-700" role="alert">{{ page.props.errors.class_group }}</div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="panel p-4 class-stat class-stat-classes"><p>Total rombel</p><strong class="text-2xl">{{ stats.classes }}</strong><span>pada tahun ajaran terpilih</span></div>
                <div class="panel p-4 class-stat class-stat-students"><p>Siswa aktif semester terpilih</p><strong class="text-2xl">{{ stats.students }}</strong><span>penempatan aktif di rombel</span></div>
                <div class="panel p-4 class-stat class-stat-homeroom"><p>Belum ada wali kelas</p><strong class="text-2xl">{{ stats.without_homeroom }}</strong><span>perlu ditetapkan operator</span></div>
            </div>
            <p v-if="!active_year" class="panel p-4">Belum ada tahun ajaran. Atur tahun ajaran melalui Pengaturan Akademik terlebih dahulu.</p>
            <p v-else-if="!active_term" class="panel p-4">Tahun ajaran ini belum memiliki semester. Jumlah siswa ditampilkan nol sampai semester tersedia.</p>
            <div class="panel class-filterbar">
                <label class="class-search-field search-control-shell"><span class="sr-only">Cari nama rombel</span><input v-model="search" aria-label="Cari nama rombel" placeholder="Cari nama rombel..." type="search" /></label>
                <select v-model="grade" aria-label="Filter tingkat" class="class-filter-select"><option value="">Semua tingkat</option><option v-for="level in 12" :key="level" :value="String(level)">Kelas {{ level }}</option></select>
                <button type="button" class="secondary-button" @click="search = ''; grade = ''">Reset filter</button>
                <p class="class-filter-count" role="status">Menampilkan {{ visibleClasses.length }} dari {{ classes.length }} rombel</p>
            </div>

            <!-- Grid Rombel -->
            <div class="class-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <article
                    v-for="cls in visibleClasses"
                    :key="cls.id"
                    class="class-card bg-white rounded-xl border border-slate-200 p-5 shadow-xs hover:border-indigo-300 transition-all flex flex-col justify-between"
                    :class="{ 'is-over-capacity': enrollmentCount(cls) > cls.capacity }"
                >
                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="inline-block px-2 py-0.5 text-xs font-bold rounded-sm bg-slate-100 text-slate-700 mb-1">
                                    Tingkat {{ cls.grade_level }}
                                </span>
                                <h3 class="text-xl font-bold text-slate-900">Kelas {{ cls.name }}</h3>
                            </div>
                            <span class="class-capacity-badge inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold">
                                {{ enrollmentCount(cls) }} dari {{ cls.capacity }} siswa
                            </span>
                        </div>

                        <!-- Progress Bar Kapasitas -->
                        <div class="class-capacity-track w-full bg-slate-100 rounded-full h-1.5 overflow-hidden" role="progressbar" :aria-valuemin="0" :aria-valuemax="cls.capacity" :aria-valuenow="Math.min(enrollmentCount(cls), cls.capacity)" :aria-valuetext="`${enrollmentCount(cls)} dari ${cls.capacity} siswa`" :aria-label="`Kapasitas kelas ${cls.name}`">
                            <div
                                class="class-capacity-fill bg-indigo-600 h-1.5 rounded-full"
                                :style="{ width: `${capacityPercent(cls)}%` }"
                            />
                        </div>
                        <p v-if="enrollmentCount(cls) > cls.capacity" class="class-capacity-warning" role="status">Melebihi kapasitas {{ enrollmentCount(cls) - cls.capacity }} siswa</p>

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

                    <div class="pt-4 mt-4 border-t border-slate-100 flex flex-wrap gap-3 items-center">
                        <button
                            v-if="can_manage"
                            type="button"
                            @click="openHomeroomModal(cls)"
                            class="class-link-action"
                        >
                            {{ cls.homeroom_teacher ? 'Ubah Wali Kelas' : 'Tetapkan Wali Kelas' }}
                        </button>
                        <button v-if="can_manage" type="button" class="class-card-action class-card-action-edit" :aria-label="`Ubah rombel ${cls.name}`" :title="`Ubah rombel ${cls.name}`" @click="openEdit(cls)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" /></svg></button>
                        <button v-if="can_manage" type="button" class="class-card-action class-card-action-danger" :aria-label="`Hapus rombel ${cls.name}`" :title="`Hapus rombel ${cls.name}`" @click="deleteClass(cls)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg></button>
                    </div>
                </article>

                <div v-if="visibleClasses.length === 0" class="col-span-full bg-white rounded-xl border border-dashed border-slate-300 p-12 text-center text-slate-400">
                    {{ classes.length ? 'Tidak ada rombel yang cocok dengan filter.' : 'Belum ada rombel pada tahun ajaran ini.' }}
                </div>
            </div>
        </div>

        <!-- Modal Tambah Rombel -->
        <div v-if="can_manage && showAddClassModal" ref="overlayRoot" role="dialog" aria-modal="true" aria-labelledby="class-form-title" class="class-overlay">
            <div class="class-drawer space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 id="class-form-title" class="text-lg font-bold text-slate-900">{{ editingId ? 'Ubah Rombongan Belajar' : 'Tambah Rombongan Belajar' }}</h3>
                    <button type="button" @click="showAddClassModal = false" class="class-close text-slate-400 hover:text-slate-600" aria-label="Tutup formulir rombel">✕</button>
                </div>

                <form @submit.prevent="submitAddClass" class="space-y-3">
                    <div>
                        <label for="class-grade" class="block text-xs font-semibold text-slate-700 mb-1">Tingkat kelas *</label>
                        <select id="class-grade" v-model="classForm.grade_level" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option v-for="level in 12" :key="level" :value="level">Kelas {{ level }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="class-name" class="block text-xs font-semibold text-slate-700 mb-1">Nama rombel *</label>
                        <input id="class-name" v-model="classForm.name" type="text" placeholder="contoh: 7A, 8B, X MIPA 1" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        <div v-if="classForm.errors.name" class="text-xs text-red-600 mt-1">{{ classForm.errors.name }}</div>
                    </div>

                    <div>
                        <label for="class-capacity" class="block text-xs font-semibold text-slate-700 mb-1">Kapasitas maksimal siswa</label>
                        <input id="class-capacity" v-model="classForm.capacity" type="number" min="1" max="60" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>

                    <div v-if="!editingId">
                        <label for="class-homeroom" class="block text-xs font-semibold text-slate-700 mb-1">Wali kelas (opsional)</label>
                        <select id="class-homeroom" v-model="classForm.homeroom_teacher_id" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option value="">(Belum ditetapkan)</option>
                            <option v-for="t in teachers" :key="t.id" :value="String(t.id)">{{ t.name }} ({{ t.nip || 'Non-NIP' }})</option>
                        </select>
                        <div v-if="classForm.errors.homeroom_teacher_id" class="text-xs text-red-600 mt-1">{{ classForm.errors.homeroom_teacher_id }}</div>
                    </div>

                    <ul v-if="Object.keys(classForm.errors).length" role="alert" class="text-sm text-red-700"><li v-for="(error, field) in classForm.errors" :key="field">{{ error }}</li></ul>
                    <p v-if="editingId" class="text-xs text-slate-500">Tahun ajaran tetap. Tingkat kelas dengan siswa atau penugasan tidak dapat diubah.</p>
                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showAddClassModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="classForm.processing" class="px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            {{ classForm.processing ? 'Menyimpan…' : 'Simpan Rombel' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Tetapkan Wali Kelas -->
        <div v-if="can_manage && showAssignHomeroomModal && selectedClassForHomeroom" ref="overlayRoot" role="dialog" aria-modal="true" aria-labelledby="homeroom-form-title" class="class-overlay">
            <div class="class-drawer space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 id="homeroom-form-title" class="text-lg font-bold text-slate-900">Tetapkan Wali Kelas {{ selectedClassForHomeroom.name }}</h3>
                    <button type="button" @click="showAssignHomeroomModal = false" class="class-close text-slate-400 hover:text-slate-600" aria-label="Tutup formulir wali kelas">✕</button>
                </div>

                <form @submit.prevent="submitAssignHomeroom" class="space-y-4">
                    <div>
                        <label for="class-homeroom-teacher" class="block text-xs font-semibold text-slate-700 mb-1">Pilih guru sebagai wali kelas</label>
                        <select id="class-homeroom-teacher" v-model="homeroomForm.teacher_id" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option value="">Tanpa wali kelas / lepaskan penetapan</option>
                            <option v-for="t in teachers" :key="t.id" :value="String(t.id)">{{ t.name }} ({{ t.nip || 'Non-NIP' }})</option>
                        </select>
                        <div v-if="homeroomForm.errors.teacher_id" class="text-xs text-red-600 mt-1">{{ homeroomForm.errors.teacher_id }}</div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showAssignHomeroomModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="homeroomForm.processing" class="px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            {{ homeroomForm.processing ? 'Menyimpan…' : 'Simpan Penetapan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.class-overlay { position: fixed; inset: 0; z-index: 80; display: flex; justify-content: flex-end; background: rgb(19 32 51 / 45%); backdrop-filter: blur(3px); }
.class-drawer { width: min(500px, 100%); height: 100%; padding: clamp(18px, 3vw, 30px); overflow-y: auto; overscroll-behavior: contain; background: var(--surface); color: var(--ink); box-shadow: -18px 0 40px rgb(19 32 51 / 15%); }
.class-drawer input, .class-drawer select { max-width: 100%; color: var(--ink); background: var(--surface); border-color: var(--line-strong); }
.class-drawer .text-slate-900, .class-drawer .text-slate-700 { color: var(--ink); }
.class-drawer .text-slate-600, .class-drawer .text-slate-500, .class-drawer .text-slate-400 { color: var(--ink-soft); }
.class-drawer button:focus-visible, .class-drawer input:focus-visible, .class-drawer select:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
.class-close { display: grid; place-items: center; width: 44px; height: 44px; flex: 0 0 auto; border-radius: 9px; }
.class-page { min-width: 0; }
.class-page select { max-width: 100%; }
.class-page-kicker { margin: 0 0 .3rem; color: var(--primary); font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.class-page-title { margin: 0; color: var(--ink); font-size: clamp(1.55rem, 2.4vw, 2rem); font-weight: 800; letter-spacing: -.035em; line-height: 1.12; }
.class-page-summary { max-width: 620px; margin: .45rem 0 0; color: var(--ink-soft); font-size: .88rem; line-height: 1.55; }
.class-page .page-heading { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 1.25rem; }
.class-page .page-heading > div:first-child { min-width: 0; }
.class-page .page-heading h1 { max-width: 23ch; }
.class-heading-controls { justify-content: flex-end; }
.class-action-button { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; padding: .65rem .9rem; border: 1px solid var(--primary); border-radius: .65rem; background: var(--primary); color: #fff; font-size: .8rem; font-weight: 700; white-space: nowrap; box-shadow: 0 5px 12px rgb(50 105 188 / 16%); }
.class-action-button:hover { background: var(--primary-strong); }
.class-action-button svg { width: 18px; height: 18px; margin-right: .35rem; }
.class-action-button:disabled { cursor: not-allowed; opacity: .55; }
.class-filterbar { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(160px, .4fr) auto; align-items: center; gap: .65rem; padding: .8rem 1rem; }
.class-search-field input, .class-filter-select { width: 100%; min-height: 42px; padding: .62rem .72rem; border: 1px solid var(--line-strong); border-radius: .65rem; outline: 0; background: var(--surface); color: var(--ink); font-size: .82rem; }
.class-search-field input:focus, .class-filter-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 18%, transparent); }
.class-link-action { min-height: 38px; padding: .45rem .65rem; border: 1px solid color-mix(in srgb, var(--primary) 20%, var(--line)); border-radius: .6rem; background: var(--primary-soft); color: var(--primary); font-size: .72rem; font-weight: 800; }
.class-card-action { display: grid; place-items: center; width: 44px; min-height: 44px; padding: 0; border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); color: var(--ink-soft); }
.class-card-action svg { width: 19px; height: 19px; }
.class-card-action-edit { color: var(--primary); background: var(--primary-soft); }
.class-card-action:focus-visible, .class-link-action:focus-visible, .class-action-button:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
.class-card-action:hover, .class-link-action:hover { border-color: var(--primary); color: var(--primary-strong); }
.class-card-action-danger { color: var(--red); }
.class-stat { display: grid; gap: .35rem; min-width: 0; border-top: 3px solid transparent; }
.class-stat p { margin: 0; color: var(--ink-soft); font-size: .8rem; font-weight: 600; }
.class-stat strong { color: var(--ink); line-height: 1.1; }
.class-stat span { color: var(--ink-faint); font-size: .72rem; line-height: 1.4; }
.class-stat-classes { border-top-color: #3269bc; background: color-mix(in srgb, #3269bc 7%, var(--surface)); }
.class-stat-students { border-top-color: #3d9b78; background: color-mix(in srgb, #3d9b78 7%, var(--surface)); }
.class-stat-homeroom { border-top-color: #ad7c2b; background: color-mix(in srgb, #ad7c2b 7%, var(--surface)); }
.class-filter-count { flex: 1 0 100%; margin: 0; color: var(--ink-faint); font-size: .75rem; }
.class-page .class-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.class-card { min-width: 0; border-radius: 1rem; }
.class-capacity-badge { flex: 0 0 auto; max-width: 46%; background: var(--primary-soft); color: var(--primary); border: 1px solid color-mix(in srgb, var(--primary) 16%, var(--line)); line-height: 1.35; text-align: center; }
.class-capacity-track { margin-top: .2rem; }
.class-capacity-fill { max-width: 100%; background: #3269bc; }
.class-capacity-warning { margin: -.35rem 0 0; color: #a43b32; font-size: .75rem; font-weight: 700; }
.class-card.is-over-capacity { border-color: #e3a29c; background: color-mix(in srgb, #c2473b 3%, var(--surface)); }
.class-card.is-over-capacity .class-capacity-badge { background: #fff0ee; border-color: #edc0bb; color: #a43b32; }
.class-card.is-over-capacity .class-capacity-fill { background: #c2473b; }
@media (max-width: 1200px) { .class-page .page-heading { grid-template-columns: minmax(0, 1fr); }.class-heading-controls { justify-content: flex-start; } }
@media (max-width: 1200px) { .class-page .class-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 820px) { .class-page .class-grid { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 760px) { .class-filterbar { grid-template-columns: minmax(0, 1fr) minmax(150px, .55fr); }.class-filterbar > .secondary-button { grid-column: 1 / -1; justify-self: start; }.class-filter-count { grid-column: 1 / -1; } }
@media (max-width: 640px) { .class-page .page-heading { align-items: stretch; flex-direction: column; gap: 1rem; }.class-heading-controls { display: grid; grid-template-columns: minmax(0, 1fr); align-items: stretch; }.class-heading-controls > div { flex-wrap: wrap; }.class-heading-controls select,.class-heading-controls .class-action-button { width: 100%; }.class-filterbar { grid-template-columns: minmax(0, 1fr); }.class-filterbar > .secondary-button { grid-column: auto; width: 100%; }.class-filter-count { grid-column: auto; } }
@media (max-width: 600px) { .class-overlay { align-items: flex-end; }.class-drawer { height: min(92dvh, 900px); border-radius: 18px 18px 0 0; padding-bottom: max(24px, env(safe-area-inset-bottom)); } }
</style>
