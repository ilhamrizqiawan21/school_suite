<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
const selectedClass = ref(props.filters.class_group_id ? String(props.filters.class_group_id) : '');
const selectedTeacher = ref(props.filters.teacher_id ? String(props.filters.teacher_id) : '');
const searchQuery = ref('');
const selectedDay = ref<number | 'all'>('all');
const statusFilter = ref<'all' | 'published' | 'draft'>('all');
const viewMode = ref<'board' | 'table'>('board');
const showSunday = ref(false);
const showAddModal = ref(false);
const overlayRoot = ref<HTMLElement | null>(null);
let overlayTrigger: HTMLElement | null = null;

watch(showAddModal, async (open) => {
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
    if (!showAddModal.value || event.key !== 'Tab' || !overlayRoot.value) return;
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

const days = [
    { id: 1, name: 'Senin', short: 'Sen' },
    { id: 2, name: 'Selasa', short: 'Sel' },
    { id: 3, name: 'Rabu', short: 'Rab' },
    { id: 4, name: 'Kamis', short: 'Kam' },
    { id: 5, name: 'Jumat', short: 'Jum' },
    { id: 6, name: 'Sabtu', short: 'Sab' },
    { id: 7, name: 'Minggu', short: 'Min' },
];

const dayNameMap: Record<number, string> = {
    1: 'Senin',
    2: 'Selasa',
    3: 'Rabu',
    4: 'Kamis',
    5: 'Jumat',
    6: 'Sabtu',
    7: 'Minggu',
};

const formatTime = (timeStr: string) => timeStr.substring(0, 5);

const filteredSchedules = computed(() => {
    const q = searchQuery.value.trim().toLocaleLowerCase('id-ID');
    return props.schedules.filter((slot) => {
        if (selectedDay.value !== 'all' && slot.day_of_week !== selectedDay.value) {
            return false;
        }
        if (statusFilter.value !== 'all' && slot.status !== statusFilter.value) {
            return false;
        }
        if (q) {
            const haystack = [
                slot.subject?.name,
                slot.subject?.code,
                slot.teacher?.name,
                slot.class_group?.name ? `kelas ${slot.class_group.name}` : '',
                slot.room ?? '',
                dayNameMap[slot.day_of_week] ?? '',
            ].join(' ').toLocaleLowerCase('id-ID');
            if (!haystack.includes(q)) return false;
        }
        return true;
    });
});

const publishedCount = computed(() => filteredSchedules.value.filter(slot => slot.status === 'published').length);
const filteredDraftCount = computed(() => filteredSchedules.value.filter(slot => slot.status === 'draft').length);
const uniqueClassCount = computed(() => new Set(filteredSchedules.value.map(slot => slot.class_group_id)).size);
const uniqueTeacherCount = computed(() => new Set(filteredSchedules.value.map(slot => slot.teacher_id)).size);

const schedulesForDay = (dayId: number) => filteredSchedules.value.filter(slot => slot.day_of_week === dayId);
const totalSchedulesForDay = (dayId: number) => props.schedules.filter(slot => slot.day_of_week === dayId).length;
const hasSundaySchedules = computed(() => totalSchedulesForDay(7) > 0);

const visibleBoardDays = computed(() => {
    if (selectedDay.value !== 'all') {
        return days.filter(d => d.id === selectedDay.value);
    }
    return days.filter(d => d.id <= 6 || showSunday.value || hasSundaySchedules.value);
});

const hasActiveLocalFilters = computed(() => Boolean(
    searchQuery.value.trim() ||
    selectedDay.value !== 'all' ||
    statusFilter.value !== 'all' ||
    selectedClass.value ||
    selectedTeacher.value
));

const applyFilters = () => {
    router.get(
        '/akademik/jadwal',
        {
            term_id: selectedTerm.value || undefined,
            class_group_id: selectedClass.value || undefined,
            teacher_id: selectedTeacher.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
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

const openAdd = (defaultDay?: number) => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.term_id = selectedTerm.value;
    if (defaultDay) {
        form.day_of_week = defaultDay;
    } else if (selectedDay.value !== 'all') {
        form.day_of_week = selectedDay.value;
    }
    if (selectedClass.value) {
        form.class_group_id = selectedClass.value;
    }
    if (selectedTeacher.value) {
        form.teacher_id = selectedTeacher.value;
    }
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

const resetFilters = () => {
    searchQuery.value = '';
    selectedDay.value = 'all';
    statusFilter.value = 'all';
    if (!props.is_student && (selectedClass.value || selectedTeacher.value)) {
        selectedClass.value = '';
        selectedTeacher.value = '';
        applyFilters();
    }
};

const changeTerm = () => router.get('/akademik/jadwal', { term_id: selectedTerm.value });

const exportPdfUrl = computed(() => {
    const params = new URLSearchParams();
    if (selectedTerm.value) params.set('term_id', selectedTerm.value);
    if (selectedClass.value) params.set('class_group_id', selectedClass.value);
    if (selectedTeacher.value) params.set('teacher_id', selectedTeacher.value);
    if (selectedDay.value !== 'all') params.set('day_of_week', String(selectedDay.value));
    if (statusFilter.value !== 'all') params.set('status', statusFilter.value);
    if (searchQuery.value.trim()) params.set('search', searchQuery.value.trim());
    const qs = params.toString();
    return qs ? `/akademik/jadwal/export/pdf?${qs}` : '/akademik/jadwal/export/pdf';
});

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
        router.delete(`/akademik/jadwal/${id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Jadwal Pelajaran">
        <Head title="Jadwal Pelajaran — School Suite" />

        <div class="view space-y-5 schedule-page">
            <!-- Header Halaman -->
            <div class="page-heading compact-heading">
                <div>
                    <p class="schedule-page-kicker">Akademik · Kalender pembelajaran</p>
                    <h1 class="schedule-page-title">Jadwal pelajaran</h1>
                    <p class="schedule-page-summary">
                        {{ is_student ? 'Jadwal pelajaran mingguan kelasmu pada periode akademik aktif.' : 'Penyusunan dan pemantauan jadwal mingguan dengan deteksi benturan guru, rombel, dan ruang kelas.' }}
                    </p>
                </div>

                <div class="schedule-heading-actions">
                    <div class="schedule-view-toggle" role="group" aria-label="Mode tampilan jadwal">
                        <button
                            type="button"
                            class="schedule-view-btn"
                            :class="{ 'is-active': viewMode === 'board' }"
                            :aria-pressed="viewMode === 'board'"
                            @click="viewMode = 'board'"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <rect x="3" y="4" width="5" height="16" rx="1.5" stroke-width="1.8" />
                                <rect x="10" y="4" width="5" height="16" rx="1.5" stroke-width="1.8" />
                                <rect x="17" y="4" width="4" height="16" rx="1.5" stroke-width="1.8" />
                            </svg>
                            <span>Papan Mingguan</span>
                        </button>
                        <button
                            type="button"
                            class="schedule-view-btn"
                            :class="{ 'is-active': viewMode === 'table' }"
                            :aria-pressed="viewMode === 'table'"
                            @click="viewMode = 'table'"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <span>Tabel Daftar</span>
                        </button>
                    </div>

                    <a
                        v-if="active_term"
                        :href="exportPdfUrl"
                        class="file-action-button file-action-button--pdf schedule-export-pdf"
                        aria-label="Ekspor Jadwal ke PDF"
                        title="Unduh jadwal pelajaran dalam format PDF"
                    >
                        <span>Ekspor PDF</span>
                    </a>

                    <button
                        v-if="can_manage && draft_count > 0"
                        type="button"
                        @click="publishAll"
                        class="schedule-action-button schedule-action-publish"
                    >
                        <span class="schedule-status-dot is-draft" aria-hidden="true" />
                        Terbitkan {{ draft_count }} Draf
                    </button>
                    <button
                        v-if="can_manage"
                        type="button"
                        :disabled="!active_term"
                        @click="openAdd()"
                        class="schedule-action-button schedule-action-add"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Slot Jadwal
                    </button>
                </div>
            </div>

            <!-- Notifikasi & Peringatan -->
            <div v-if="page.props.flash?.message" class="panel p-4 schedule-alert schedule-alert--info" role="status">
                {{ page.props.flash.message }}
            </div>
            <div v-if="page.props.errors?.schedule || page.props.errors?.term_id" class="panel p-4 text-red-700 schedule-alert schedule-alert--error" role="alert">
                {{ page.props.errors.schedule ?? page.props.errors.term_id }}
            </div>
            <p v-if="!active_term" class="panel p-4">
                Belum ada semester aktif atau terpilih. Atur periode akademik untuk menyusun jadwal.
            </p>

            <!-- Ringkasan Metrik -->
            <div class="schedule-metrics" :class="{ 'schedule-metrics-manager': can_manage }">
                <div class="panel schedule-metric schedule-metric-slots">
                    <div class="schedule-metric-head">
                        <p>Slot sesuai filter</p>
                        <span class="schedule-metric-badge">{{ uniqueClassCount }} rombel · {{ uniqueTeacherCount }} guru</span>
                    </div>
                    <strong>{{ filteredSchedules.length }}</strong>
                    <span>dari {{ schedules.length }} sesi pada semester terpilih</span>
                </div>
                <div class="panel schedule-metric schedule-metric-published">
                    <div class="schedule-metric-head">
                        <p>Jadwal terbit</p>
                        <span class="schedule-metric-badge is-published">Aktif</span>
                    </div>
                    <strong>{{ publishedCount }}</strong>
                    <span>sesi siap digunakan presensi & jurnal</span>
                </div>
                <div v-if="can_manage" class="panel schedule-metric schedule-metric-drafts">
                    <div class="schedule-metric-head">
                        <p>Draf seluruh semester</p>
                        <span class="schedule-metric-badge is-draft">{{ filteredDraftCount }} di tampilan ini</span>
                    </div>
                    <strong>{{ draft_count }}</strong>
                    <span>{{ draft_count > 0 ? 'siap ditinjau & diterbitkan serentak' : 'seluruh jadwal telah diterbitkan' }}</span>
                </div>
            </div>

            <!-- Konteks Siswa -->
            <div v-if="is_student" class="schedule-student-bar panel">
                <div class="schedule-student-context">
                    <span>Jadwal kelas</span>
                    <strong>{{ classes[0]?.name ? `Kelas ${classes[0].name}` : 'Belum ada kelas aktif' }}</strong>
                    <span class="schedule-context-sep">·</span>
                    <span>{{ active_term?.name ?? 'Periode belum tersedia' }}</span>
                </div>
                <label class="search-control-shell schedule-student-search">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="searchQuery"
                        type="search"
                        aria-label="Cari mata pelajaran, guru, atau ruangan"
                        placeholder="Cari mapel, guru, atau ruang..."
                    />
                </label>
            </div>

            <!-- Panel Filter & Kontrol Utama (Non-Siswa) -->
            <div v-if="!is_student" class="schedule-controls-card panel">
                <div class="schedule-controls">
                    <label class="schedule-term-control">
                        <span>Semester</span>
                        <select v-model="selectedTerm" aria-label="Semester jadwal" :disabled="!terms.length" @change="changeTerm">
                            <option v-if="!active_term" value="">Pilih semester...</option>
                            <option v-for="term in terms" :key="term.id" :value="String(term.id)">
                                {{ term.academic_year?.name }} — {{ term.name }}
                            </option>
                        </select>
                    </label>
                    <label class="schedule-filter-control">
                        <span>Rombongan belajar</span>
                        <select aria-label="Filter kelas" v-model="selectedClass" @change="applyFilters">
                            <option value="">Semua rombongan belajar</option>
                            <option v-for="c in classes" :key="c.id" :value="String(c.id)">Kelas {{ c.name }}</option>
                        </select>
                    </label>
                    <label class="schedule-filter-control">
                        <span>Guru pengajar</span>
                        <select aria-label="Filter guru" v-model="selectedTeacher" @change="applyFilters">
                            <option value="">Semua guru pengajar</option>
                            <option v-for="t in teachers" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                        </select>
                    </label>
                    <label class="schedule-filter-control">
                        <span>Cari cepat</span>
                        <div class="search-control-shell schedule-search-input">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="searchQuery"
                                type="search"
                                aria-label="Cari mata pelajaran, kode, atau ruangan"
                                placeholder="Mapel, kode, ruang..."
                            />
                        </div>
                    </label>
                    <button
                        type="button"
                        class="secondary-button schedule-reset"
                        :disabled="!hasActiveLocalFilters"
                        @click="resetFilters"
                    >
                        Reset filter
                    </button>
                </div>
            </div>

            <!-- Baris Filter Hari & Status -->
            <div v-if="schedules.length" class="schedule-subbar panel">
                <div class="schedule-day-pills" role="tablist" aria-label="Filter hari pembelajaran">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="selectedDay === 'all'"
                        class="schedule-day-pill"
                        :class="{ 'is-active': selectedDay === 'all' }"
                        @click="selectedDay = 'all'"
                    >
                        <span>Semua Hari</span>
                        <em class="schedule-pill-count">{{ schedules.length }}</em>
                    </button>
                    <button
                        v-for="d in days.filter(day => day.id <= 6 || showSunday || hasSundaySchedules)"
                        :key="d.id"
                        type="button"
                        role="tab"
                        :aria-selected="selectedDay === d.id"
                        class="schedule-day-pill"
                        :class="{ 'is-active': selectedDay === d.id, 'is-empty': totalSchedulesForDay(d.id) === 0 }"
                        @click="selectedDay = selectedDay === d.id ? 'all' : d.id"
                    >
                        <span>{{ d.name }}</span>
                        <em class="schedule-pill-count">{{ totalSchedulesForDay(d.id) }}</em>
                    </button>
                </div>

                <div class="schedule-subbar-right">
                    <div v-if="!is_student" class="schedule-status-pills" role="group" aria-label="Filter status jadwal">
                        <button
                            type="button"
                            class="schedule-status-pill"
                            :class="{ 'is-active': statusFilter === 'all' }"
                            @click="statusFilter = 'all'"
                        >
                            Semua
                        </button>
                        <button
                            type="button"
                            class="schedule-status-pill"
                            :class="{ 'is-active': statusFilter === 'published' }"
                            @click="statusFilter = 'published'"
                        >
                            Terbit
                        </button>
                        <button
                            type="button"
                            class="schedule-status-pill"
                            :class="{ 'is-active': statusFilter === 'draft' }"
                            @click="statusFilter = 'draft'"
                        >
                            Draf
                        </button>
                    </div>

                    <label v-if="viewMode === 'board' && selectedDay === 'all' && !hasSundaySchedules" class="schedule-sunday-toggle">
                        <input v-model="showSunday" type="checkbox" />
                        <span>Tampilkan Minggu</span>
                    </label>
                </div>
            </div>

            <!-- Tampilan 1: Papan Mingguan (Board View) -->
            <template v-if="schedules.length && filteredSchedules.length">
                <div
                    v-if="viewMode === 'board'"
                    class="schedule-week-scroll"
                    tabindex="0"
                    aria-label="Jadwal mingguan"
                >
                    <div
                        class="schedule-week-grid"
                        :class="{
                            'is-single-day': visibleBoardDays.length === 1,
                            'is-seven-days': visibleBoardDays.length === 7,
                        }"
                    >
                        <section
                            v-for="d in visibleBoardDays"
                            :key="d.id"
                            class="schedule-day-card"
                            :class="{ 'is-empty-day': schedulesForDay(d.id).length === 0 }"
                            :aria-label="`${d.name}, ${schedulesForDay(d.id).length} sesi`"
                        >
                            <header class="schedule-day-header">
                                <div class="schedule-day-title">
                                    <h3>{{ d.name }}</h3>
                                    <span class="schedule-day-count" :class="{ 'has-sessions': schedulesForDay(d.id).length > 0 }">
                                        {{ schedulesForDay(d.id).length }} Sesi
                                    </span>
                                </div>
                                <button
                                    v-if="can_manage && active_term"
                                    type="button"
                                    class="schedule-day-add-btn"
                                    :aria-label="`Tambah jadwal hari ${d.name}`"
                                    :title="`Tambah jadwal hari ${d.name}`"
                                    @click="openAdd(d.id)"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
                                    </svg>
                                </button>
                            </header>

                            <div class="schedule-day-body">
                                <article
                                    v-for="s in schedulesForDay(d.id)"
                                    :key="s.id"
                                    class="schedule-session"
                                    :class="s.status === 'published' ? 'is-published' : 'is-draft'"
                                >
                                    <!-- Baris 1: Jam & Status -->
                                    <div class="schedule-session-top">
                                        <span class="schedule-time-badge">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                <circle cx="12" cy="12" r="9" stroke-width="1.8" />
                                                <path stroke-linecap="round" stroke-width="1.8" d="M12 7v5l3 2" />
                                            </svg>
                                            {{ formatTime(s.start_time) }} – {{ formatTime(s.end_time) }}
                                        </span>
                                        <span
                                            class="schedule-status-badge"
                                            :class="s.status === 'published' ? 'is-published' : 'is-draft'"
                                        >
                                            {{ s.status === 'published' ? 'Terbit' : 'Draf' }}
                                        </span>
                                    </div>

                                    <!-- Baris 2: Mata Pelajaran -->
                                    <div class="schedule-session-subject">
                                        <strong class="schedule-subject-name">{{ s.subject.name }}</strong>
                                        <span class="schedule-subject-code">{{ s.subject.code }}</span>
                                    </div>

                                    <!-- Baris 3: Kelas & Ruangan -->
                                    <div class="schedule-meta">
                                        <span class="schedule-class-chip">Kelas {{ s.class_group.name }}</span>
                                        <span v-if="s.room" class="schedule-room" :title="`Ruang: ${s.room}`">
                                            {{ s.room }}
                                        </span>
                                        <span v-else class="schedule-room is-muted">Tanpa ruang</span>
                                    </div>

                                    <!-- Baris 4: Guru & Tombol Aksi -->
                                    <div class="schedule-session-footer">
                                        <span class="schedule-teacher" :title="s.teacher.name">
                                            {{ s.teacher.name }}
                                        </span>
                                        <div v-if="can_manage" class="schedule-session-actions">
                                            <RowActionButton
                                                action="edit"
                                                label="Ubah"
                                                :aria-label="`Ubah jadwal ${s.subject.name} ${s.start_time}`"
                                                @click="openEdit(s)"
                                            />
                                            <RowActionButton
                                                action="delete"
                                                label="Hapus"
                                                :aria-label="`Hapus jadwal ${s.subject.name} ${s.start_time}`"
                                                @click="deleteSchedule(s.id)"
                                            />
                                        </div>
                                    </div>
                                </article>

                                <div
                                    v-if="schedulesForDay(d.id).length === 0"
                                    class="schedule-empty"
                                >
                                    <span>Tidak ada sesi terjadwal</span>
                                    <button
                                        v-if="can_manage && active_term"
                                        type="button"
                                        class="schedule-empty-add"
                                        @click="openAdd(d.id)"
                                    >
                                        + Tambah sesi {{ d.name }}
                                    </button>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Tampilan 2: Tabel Terstruktur (Table View) -->
                <div v-else class="table-card panel schedule-table-card">
                    <div class="schedule-table-header">
                        <div>
                            <h2>Daftar Sesi Pembelajaran</h2>
                            <p>Menampilkan {{ filteredSchedules.length }} sesi jadwal berurutan menurut hari dan jam mulai.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto table-scroll" tabindex="0" role="region" aria-label="Tabel jadwal pelajaran">
                        <table class="app-table schedule-table w-full text-left border-collapse text-sm">
                            <thead>
                                <tr>
                                    <th scope="col">Hari</th>
                                    <th scope="col">Jam</th>
                                    <th scope="col">Mata Pelajaran</th>
                                    <th scope="col">Rombel</th>
                                    <th scope="col">Guru Pengajar</th>
                                    <th scope="col">Ruang / Lab</th>
                                    <th scope="col">Status</th>
                                    <th v-if="can_manage" scope="col" class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="s in filteredSchedules" :key="s.id">
                                    <td class="font-semibold text-slate-900 whitespace-nowrap">
                                        {{ dayNameMap[s.day_of_week] ?? `Hari ${s.day_of_week}` }}
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <span class="schedule-time-badge">
                                            {{ formatTime(s.start_time) }} – {{ formatTime(s.end_time) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-bold text-slate-900">{{ s.subject.name }}</div>
                                        <div class="text-xs text-slate-500 font-mono">{{ s.subject.code }}</div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <span class="schedule-class-chip">Kelas {{ s.class_group.name }}</span>
                                    </td>
                                    <td>
                                        <span class="text-slate-700 font-medium">{{ s.teacher.name }}</span>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <span v-if="s.room" class="schedule-room">{{ s.room }}</span>
                                        <span v-else class="text-xs text-slate-400">—</span>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <span
                                            class="schedule-status-badge"
                                            :class="s.status === 'published' ? 'is-published' : 'is-draft'"
                                        >
                                            {{ s.status === 'published' ? 'Terbit' : 'Draf' }}
                                        </span>
                                    </td>
                                    <td v-if="can_manage" class="text-right whitespace-nowrap">
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            <RowActionButton
                                                action="edit"
                                                label="Ubah"
                                                :aria-label="`Ubah jadwal ${s.subject.name} ${s.start_time}`"
                                                @click="openEdit(s)"
                                            />
                                            <RowActionButton
                                                action="delete"
                                                label="Hapus"
                                                :aria-label="`Hapus jadwal ${s.subject.name} ${s.start_time}`"
                                                @click="deleteSchedule(s.id)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <!-- Kosong karena filter lokal -->
            <div v-else-if="schedules.length && !filteredSchedules.length" class="schedule-no-results">
                <strong>Tidak ada sesi yang cocok dengan filter</strong>
                <span>Ubah kata kunci pencarian, pilihan hari, atau status untuk menampilkan jadwal kembali.</span>
                <div>
                    <button type="button" class="secondary-button mt-2" @click="resetFilters">
                        Tampilkan semua sesi
                    </button>
                </div>
            </div>

            <!-- Kosong sama sekali -->
            <div v-else class="schedule-no-results">
                <strong>{{ is_student ? 'Belum ada jadwal terbit untuk kelasmu' : 'Belum ada jadwal pelajaran' }}</strong>
                <span>{{ is_student ? 'Jadwal yang sudah diterbitkan sekolah akan muncul di sini.' : 'Tambahkan slot jadwal atau ubah filter untuk melihat jadwal.' }}</span>
            </div>
        </div>

        <!-- Modal Tambah / Ubah Slot Jadwal -->
        <div
            v-if="can_manage && showAddModal"
            ref="overlayRoot"
            role="dialog"
            aria-modal="true"
            aria-labelledby="schedule-form-title"
            class="schedule-overlay"
            @keydown.esc="showAddModal = false"
            @click.self="showAddModal = false"
        >
            <div class="schedule-drawer space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">
                            {{ active_term?.academic_year?.name }} · {{ active_term?.name }}
                        </p>
                        <h3 id="schedule-form-title" class="text-lg font-bold text-slate-900">
                            {{ editingId ? 'Ubah Jadwal Pelajaran' : 'Tambah Slot Jadwal Pelajaran' }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="showAddModal = false"
                        class="schedule-close text-slate-400 hover:text-slate-600"
                        aria-label="Tutup formulir jadwal"
                    >
                        ✕
                    </button>
                </div>

                <ul v-if="Object.keys(form.errors).length" role="alert" class="schedule-form-errors">
                    <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                </ul>
                <p v-if="editingId" class="schedule-form-notice">
                    Perubahan disimpan sebagai draf dan perlu diterbitkan kembali. Jadwal dengan riwayat pertemuan atau guru pengganti tidak dapat diubah.
                </p>

                <form @submit.prevent="submitAdd" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="schedule-day" class="block text-xs font-semibold text-slate-700 mb-1">Hari *</label>
                            <select id="schedule-day" v-model="form.day_of_week" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option v-for="d in days" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="schedule-class" class="block text-xs font-semibold text-slate-700 mb-1">Rombel (kelas) *</label>
                            <select id="schedule-class" v-model="form.class_group_id" required class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="">Pilih rombel...</option>
                                <option v-for="c in classes" :key="c.id" :value="String(c.id)">Kelas {{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="schedule-subject" class="block text-xs font-semibold text-slate-700 mb-1">Mata pelajaran *</label>
                            <select id="schedule-subject" v-model="form.subject_id" required class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="">Pilih mapel...</option>
                                <option v-for="s in subjects" :key="s.id" :value="String(s.id)">{{ s.name }} ({{ s.code }})</option>
                            </select>
                        </div>
                        <div>
                            <label for="schedule-teacher" class="block text-xs font-semibold text-slate-700 mb-1">Guru pengajar *</label>
                            <select id="schedule-teacher" v-model="form.teacher_id" required class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="">Pilih guru...</option>
                                <option v-for="t in teachers" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label for="schedule-start" class="block text-xs font-semibold text-slate-700 mb-1">Mulai *</label>
                            <input id="schedule-start" v-model="form.start_time" type="time" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label for="schedule-end" class="block text-xs font-semibold text-slate-700 mb-1">Selesai *</label>
                            <input id="schedule-end" v-model="form.end_time" type="time" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label for="schedule-room" class="block text-xs font-semibold text-slate-700 mb-1">Ruang / lab</label>
                            <input id="schedule-room" v-model="form.room" type="text" placeholder="contoh: Lab IPA" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan…' : 'Simpan Jadwal' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Overlay & Drawer */
.schedule-overlay { position: fixed; inset: 0; z-index: 80; display: flex; justify-content: flex-end; background: rgb(19 32 51 / 45%); backdrop-filter: blur(3px); }
.schedule-drawer { width: min(560px, 100%); height: 100%; padding: clamp(18px, 3vw, 30px); overflow-y: auto; overscroll-behavior: contain; background: var(--surface); color: var(--ink); box-shadow: -18px 0 40px rgb(19 32 51 / 15%); }
.schedule-drawer input, .schedule-drawer select { max-width: 100%; color: var(--ink); background: var(--surface); border-color: var(--line-strong); }
.schedule-drawer .text-slate-900, .schedule-drawer .text-slate-700 { color: var(--ink); }
.schedule-drawer .text-slate-600, .schedule-drawer .text-slate-500, .schedule-drawer .text-slate-400 { color: var(--ink-soft); }
.schedule-drawer button:focus-visible, .schedule-drawer input:focus-visible, .schedule-drawer select:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
.schedule-close { display: grid; place-items: center; width: 40px; height: 40px; flex: 0 0 auto; border-radius: 9px; }
.schedule-form-errors { margin: 0; padding: .75rem 1rem; list-style: disc inside; border-radius: .65rem; border: 1px solid color-mix(in srgb, var(--red) 30%, var(--line)); background: var(--red-soft); color: var(--red); font-size: .82rem; font-weight: 600; }
.schedule-form-notice { margin: 0; padding: .65rem .85rem; border-radius: .65rem; border: 1px solid color-mix(in srgb, var(--gold) 30%, var(--line)); background: var(--gold-soft); color: var(--ink-soft); font-size: .78rem; line-height: 1.45; }

/* Page Header & Actions */
.schedule-page { min-width: 0; }
.schedule-page :deep(.page-heading) { margin-bottom: 0; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 1.25rem; align-items: center; }
.schedule-page :deep(.page-heading > div:first-child) { min-width: 0; }
.schedule-page-kicker { margin: 0 0 .3rem; color: var(--primary); font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.schedule-page-title { margin: 0; color: var(--ink); font-size: clamp(1.55rem, 2.4vw, 2rem); font-weight: 800; letter-spacing: -.035em; line-height: 1.12; }
.schedule-page-summary { max-width: 65ch; margin: .4rem 0 0; color: var(--ink-soft); font-size: .86rem; line-height: 1.55; }
.schedule-heading-actions { display: flex; align-items: center; justify-content: flex-end; gap: .55rem; flex-wrap: wrap; }

/* View Mode Switcher */
.schedule-view-toggle { display: inline-flex; align-items: center; padding: 3px; border: 1px solid var(--line-strong); border-radius: .65rem; background: var(--surface-subtle); }
.schedule-view-btn { display: inline-flex; align-items: center; gap: .4rem; min-height: 36px; padding: .35rem .7rem; border: 0; border-radius: .5rem; background: transparent; color: var(--ink-soft); font-size: .78rem; font-weight: 700; transition: background-color .16s ease, color .16s ease, box-shadow .16s ease; }
.schedule-view-btn svg { width: 15px; height: 15px; flex: 0 0 15px; }
.schedule-view-btn:hover { color: var(--ink); }
.schedule-view-btn.is-active { background: var(--surface); color: var(--primary); box-shadow: var(--shadow-sm); }

/* Action Buttons */
.schedule-action-button { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; gap: .45rem; padding: .6rem .95rem; border: 1px solid var(--line-strong); border-radius: .65rem; color: var(--ink-soft); background: var(--surface); font-size: .8rem; font-weight: 700; white-space: nowrap; transition: background-color .16s ease, border-color .16s ease, color .16s ease; }
.schedule-action-button:hover:not(:disabled) { color: var(--primary); background: var(--primary-soft); border-color: var(--primary); }
.schedule-action-publish { color: var(--green); background: var(--green-soft); border-color: color-mix(in srgb, var(--green) 28%, var(--line)); }
.schedule-action-publish:hover:not(:disabled) { color: var(--green); background: color-mix(in srgb, var(--green-soft) 75%, var(--green) 25%); border-color: var(--green); }
.schedule-action-add { color: #fff; background: var(--primary); border-color: var(--primary); box-shadow: 0 4px 12px rgb(50 105 188 / 16%); }
.schedule-action-add:hover:not(:disabled) { color: #fff; background: var(--primary-strong); border-color: var(--primary-strong); }
.schedule-action-button:disabled { cursor: not-allowed; opacity: .55; }
.schedule-status-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green); }

/* Metrics Strip */
.schedule-metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
.schedule-metrics-manager { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.schedule-metric { display: grid; gap: .25rem; min-width: 0; padding: .95rem 1.15rem; border-top: 3px solid var(--metric-tone); background: var(--surface); }
.schedule-metric-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
.schedule-metric p { margin: 0; color: var(--ink-soft); font-size: .78rem; font-weight: 700; }
.schedule-metric-badge { display: inline-flex; align-items: center; padding: .15rem .5rem; border-radius: 999px; background: var(--surface-subtle); border: 1px solid var(--line); color: var(--ink-soft); font-size: .68rem; font-weight: 700; white-space: nowrap; }
.schedule-metric-badge.is-published { color: var(--green); background: var(--green-soft); border-color: transparent; }
.schedule-metric-badge.is-draft { color: var(--gold); background: var(--gold-soft); border-color: transparent; }
.schedule-metric strong { color: var(--ink); font-size: 1.65rem; font-weight: 800; line-height: 1.15; font-variant-numeric: tabular-nums; }
.schedule-metric span { color: var(--ink-faint); font-size: .73rem; line-height: 1.4; }
.schedule-metric-slots { --metric-tone: var(--primary); }
.schedule-metric-published { --metric-tone: var(--green); }
.schedule-metric-drafts { --metric-tone: var(--gold); }

/* Student Context Bar */
.schedule-student-bar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .75rem 1rem; flex-wrap: wrap; }
.schedule-student-context { display: flex; align-items: center; gap: .5rem; color: var(--ink-soft); font-size: .84rem; font-weight: 600; flex-wrap: wrap; }
.schedule-student-context strong { color: var(--ink); font-weight: 800; }
.schedule-student-context::before { content: ''; width: .55rem; height: .55rem; flex: 0 0 auto; border-radius: 50%; background: var(--primary); box-shadow: 0 0 0 4px var(--primary-soft); }
.schedule-context-sep { color: var(--line-strong); }
.schedule-student-search { flex: 0 1 280px; min-width: 220px; }

/* Filter Controls (Non-Student) */
.schedule-controls-card { padding: 1rem 1.15rem; }
.schedule-controls { display: grid; grid-template-columns: minmax(190px, 1.15fr) minmax(160px, 1fr) minmax(160px, 1fr) minmax(180px, 1.1fr) auto; align-items: end; gap: .75rem; }
.schedule-term-control, .schedule-filter-control { display: grid; gap: .35rem; min-width: 0; color: var(--ink-soft); font-size: .76rem; font-weight: 700; }
.schedule-term-control select, .schedule-filter-control select { display: block; width: 100%; max-width: 100%; min-width: 0; height: var(--control-height, 40px); padding: 0 .75rem; border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); color: var(--ink); font-size: .82rem; font-weight: 500; }
.schedule-term-control select:disabled { cursor: not-allowed; opacity: .65; }
.schedule-search-input { width: 100%; }
.schedule-reset { height: var(--control-height, 40px); white-space: nowrap; }

/* Subbar: Day Pills & Status Toggle */
.schedule-subbar { display: flex; align-items: center; justify-content: space-between; gap: .85rem; padding: .6rem .85rem; flex-wrap: wrap; }
.schedule-day-pills { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
.schedule-day-pill { display: inline-flex; align-items: center; gap: .4rem; min-height: 34px; padding: .3rem .65rem; border: 1px solid transparent; border-radius: .55rem; background: transparent; color: var(--ink-soft); font-size: .78rem; font-weight: 700; transition: background-color .15s ease, color .15s ease, border-color .15s ease; }
.schedule-day-pill:hover { color: var(--ink); background: var(--surface-subtle); border-color: var(--line); }
.schedule-day-pill.is-active { color: var(--primary-strong); background: var(--primary-soft); border-color: color-mix(in srgb, var(--primary) 30%, var(--line)); }
.schedule-day-pill.is-empty:not(.is-active) { opacity: .65; }
.schedule-pill-count { display: inline-grid; place-items: center; min-width: 20px; height: 18px; padding: 0 5px; border-radius: 999px; background: var(--surface-strong); color: var(--ink-soft); font-style: normal; font-size: .68rem; font-weight: 800; font-variant-numeric: tabular-nums; }
.schedule-day-pill.is-active .schedule-pill-count { background: var(--primary); color: #fff; }

.schedule-subbar-right { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; margin-left: auto; }
.schedule-status-pills { display: inline-flex; align-items: center; padding: 2px; border-radius: .55rem; background: var(--surface-subtle); border: 1px solid var(--line); }
.schedule-status-pill { min-height: 28px; padding: .2rem .6rem; border: 0; border-radius: .4rem; background: transparent; color: var(--ink-soft); font-size: .74rem; font-weight: 700; transition: background-color .15s ease, color .15s ease; }
.schedule-status-pill.is-active { background: var(--surface); color: var(--ink); box-shadow: var(--shadow-sm); }
.schedule-sunday-toggle { display: inline-flex; align-items: center; gap: .4rem; color: var(--ink-soft); font-size: .76rem; font-weight: 600; cursor: pointer; user-select: none; }

/* Weekly Timetable Grid */
.schedule-week-scroll { max-width: 100%; overflow-x: auto; padding-bottom: .25rem; scrollbar-color: var(--line-strong) transparent; }
.schedule-week-grid { display: grid; width: 100%; grid-template-columns: repeat(3, minmax(240px, 1fr)); align-items: stretch; gap: .9rem; }
@media (min-width: 1400px) {
    .schedule-week-grid { grid-template-columns: repeat(6, minmax(195px, 1fr)); }
    .schedule-week-grid.is-seven-days { grid-template-columns: repeat(7, minmax(185px, 1fr)); }
}
.schedule-week-grid.is-single-day { grid-template-columns: minmax(0, 1fr); }
.schedule-week-grid.is-single-day .schedule-day-body { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: .75rem; }

/* Day Column Card */
.schedule-day-card { min-width: 0; display: flex; flex-direction: column; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; box-shadow: var(--shadow-sm); overflow: hidden; }
.schedule-day-header { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .75rem .9rem; background: var(--surface-subtle); border-bottom: 1px solid var(--line); }
.schedule-day-title { display: flex; align-items: center; gap: .5rem; min-width: 0; }
.schedule-day-title h3 { margin: 0; color: var(--ink); font-size: .88rem; font-weight: 800; letter-spacing: -.01em; }
.schedule-day-count { display: inline-flex; align-items: center; padding: .15rem .5rem; border-radius: 999px; background: var(--surface-strong); color: var(--ink-faint); font-size: .7rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.schedule-day-count.has-sessions { color: var(--primary-strong); background: var(--primary-soft); }
.schedule-day-add-btn { display: inline-grid; place-items: center; width: 28px; height: 28px; padding: 0; border: 1px solid var(--line-strong); border-radius: .45rem; background: var(--surface); color: var(--ink-soft); transition: color .15s ease, border-color .15s ease, background-color .15s ease; }
.schedule-day-add-btn svg { width: 14px; height: 14px; }
.schedule-day-add-btn:hover { color: var(--primary); border-color: var(--primary); background: var(--primary-soft); }

/* Session Card inside Day Column */
.schedule-day-body { padding: .7rem; display: flex; flex-direction: column; gap: .6rem; flex: 1; }
.schedule-session { display: flex; flex-direction: column; gap: .45rem; min-width: 0; padding: .75rem; border-radius: .75rem; border: 1px solid var(--line); border-left: 3px solid var(--primary); background: var(--surface-subtle); transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease; }
.schedule-session.is-draft { border-left-color: var(--gold); }
.schedule-session:hover { background: var(--surface); border-color: color-mix(in srgb, var(--primary) 35%, var(--line)); box-shadow: var(--shadow-sm); }

.schedule-session-top { display: flex; align-items: center; justify-content: space-between; gap: .4rem; flex-wrap: wrap; }
.schedule-time-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .5rem; border-radius: .4rem; background: var(--primary-soft); color: var(--primary-strong); font-size: .72rem; font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
.schedule-time-badge svg { width: 12px; height: 12px; flex: 0 0 12px; }
.schedule-status-badge { display: inline-flex; align-items: center; padding: .15rem .45rem; border-radius: .35rem; font-size: .64rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
.schedule-status-badge.is-published { color: var(--green); background: var(--green-soft); }
.schedule-status-badge.is-draft { color: var(--gold); background: var(--gold-soft); }

.schedule-session-subject { display: flex; flex-direction: column; gap: .1rem; min-width: 0; margin-top: .1rem; }
.schedule-subject-name { color: var(--ink); font-size: .84rem; font-weight: 800; line-height: 1.3; overflow-wrap: anywhere; }
.schedule-subject-code { color: var(--ink-faint); font-family: var(--font-mono); font-size: .68rem; font-weight: 600; }

.schedule-meta { display: flex; align-items: center; justify-content: space-between; gap: .4rem; flex-wrap: wrap; margin-top: .1rem; padding-top: .4rem; border-top: 1px dashed var(--line); font-size: .74rem; }
.schedule-class-chip { display: inline-flex; align-items: center; padding: .15rem .45rem; border-radius: .35rem; background: var(--surface); border: 1px solid var(--line); color: var(--ink); font-size: .72rem; font-weight: 700; white-space: nowrap; }
.schedule-room { color: var(--ink-soft); font-family: var(--font-mono); font-size: .71rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 120px; }
.schedule-room.is-muted { color: var(--ink-faint); font-family: var(--font-sans); font-weight: 400; font-style: italic; }

.schedule-session-footer { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .1rem; }
.schedule-teacher { min-width: 0; flex: 1; color: var(--ink-soft); font-size: .75rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.schedule-session-actions { display: inline-flex; align-items: center; gap: .25rem; flex: 0 0 auto; }

.schedule-session :deep(.row-action-button) { display: inline-grid; width: 28px; min-width: 28px; height: 28px; min-height: 28px; place-items: center; padding: 0; border-radius: .45rem; }
.schedule-session :deep(.row-action-button span) { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
.schedule-session :deep(.row-action-button svg) { width: 13px; height: 13px; }

/* Empty Day State */
.schedule-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .45rem; flex: 1; min-height: 110px; padding: 1rem .5rem; border: 1px dashed var(--line); border-radius: .65rem; color: var(--ink-faint); text-align: center; font-size: .76rem; }
.schedule-empty-add { padding: .3rem .65rem; border: 1px solid var(--line-strong); border-radius: .45rem; background: var(--surface); color: var(--primary); font-size: .72rem; font-weight: 700; transition: background-color .15s ease, border-color .15s ease; }
.schedule-empty-add:hover { background: var(--primary-soft); border-color: var(--primary); }

/* Table View */
.schedule-table-card { overflow: hidden; }
.schedule-table-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--line); }
.schedule-table-header h2 { margin: 0; color: var(--ink); font-size: .95rem; font-weight: 800; }
.schedule-table-header p { margin: .15rem 0 0; color: var(--ink-soft); font-size: .78rem; }
.schedule-table :deep(.row-action-button) { min-height: 32px; min-width: 32px; padding: .35rem .6rem; }

/* Empty All State */
.schedule-no-results { display: grid; gap: .35rem; padding: 2.25rem 1.5rem; border: 1px dashed var(--line-strong); border-radius: 1rem; background: var(--surface); color: var(--ink-soft); text-align: center; }
.schedule-no-results strong { color: var(--ink); font-size: 1rem; }
.schedule-no-results span { font-size: .85rem; line-height: 1.5; }

/* Responsive Breakpoints */
@media (max-width: 1180px) {
    .schedule-page :deep(.page-heading) { grid-template-columns: minmax(0, 1fr); }
    .schedule-heading-actions { justify-content: flex-start; }
    .schedule-controls { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .schedule-reset { grid-column: 1 / -1; justify-self: end; }
    .schedule-week-grid { grid-template-columns: repeat(2, minmax(240px, 1fr)); }
}

@media (max-width: 720px) {
    .schedule-heading-actions { width: 100%; }
    .schedule-view-toggle { width: 100%; display: grid; grid-template-columns: 1fr 1fr; }
    .schedule-view-btn { justify-content: center; }
    .schedule-action-button { flex: 1 1 auto; }
    .schedule-controls, .schedule-metrics, .schedule-metrics-manager { grid-template-columns: minmax(0, 1fr); }
    .schedule-reset { width: 100%; justify-self: stretch; }
    .schedule-week-grid { grid-template-columns: minmax(0, 1fr); }
    .schedule-day-pills { overflow-x: auto; flex-wrap: nowrap; width: 100%; padding-bottom: 2px; scrollbar-width: none; }
    .schedule-day-pills::-webkit-scrollbar { display: none; }
    .schedule-day-pill { flex: 0 0 auto; }
    .schedule-subbar-right { width: 100%; justify-content: space-between; }
    .schedule-student-search { flex: 1 1 100%; }
}

@media (max-width: 600px) {
    .schedule-overlay { align-items: flex-end; }
    .schedule-drawer { height: min(92dvh, 900px); border-radius: 18px 18px 0 0; padding-bottom: max(24px, env(safe-area-inset-bottom)); }
    .schedule-drawer .grid-cols-2, .schedule-drawer .grid-cols-3 { grid-template-columns: 1fr; }
}
</style>


