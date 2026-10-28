<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

type AttendanceStatus = 'present' | 'sick' | 'permitted' | 'absent';
interface ClassItem { id: number; name: string; grade_level: number }
interface SubjectItem { id: number; name: string; code: string }
interface SessionItem {
    id: number; date: string; meeting_number: number; start_time: string; end_time: string;
    status: string; attendance_count: number; student_count: number;
}
interface StudentItem {
    enrollment_id: number; student_id: number; nis: string; name: string; gender: 'L' | 'P';
    roll_number?: number | null; status: AttendanceStatus; notes: string;
}

const props = defineProps<{
    classes: ClassItem[]; selected_class?: ClassItem | null; subjects: SubjectItem[];
    selected_subject?: SubjectItem | null; month: string; date: string;
    students: StudentItem[]; sessions: SessionItem[]; selected_session_id?: number | null;
}>();

const cloneStudents = (students: StudentItem[]): StudentItem[] => students.map((student) => ({
    ...student,
    notes: student.notes || '',
}));

const classId = ref(props.selected_class?.id ? String(props.selected_class.id) : '');
const subjectId = ref(props.selected_subject?.id ? String(props.selected_subject.id) : '');
const month = ref(props.month);
const selectedSessionId = ref(props.selected_session_id ? String(props.selected_session_id) : '');
const studentList = ref<StudentItem[]>(cloneStudents(props.students));
const bulkStatus = ref<AttendanceStatus>('present');

const form = useForm({
    session_id: selectedSessionId.value,
    date: props.date,
    records: props.students.map((student) => ({
        enrollment_id: student.enrollment_id, status: student.status, notes: student.notes || '',
    })),
});

watch(() => [props.students, props.selected_session_id], () => {
    studentList.value = cloneStudents(props.students);
    selectedSessionId.value = props.selected_session_id ? String(props.selected_session_id) : '';
    form.session_id = selectedSessionId.value;
    form.date = props.date;
    form.records = props.students.map((student) => ({
        enrollment_id: student.enrollment_id, status: student.status, notes: student.notes || '',
    }));
    form.defaults();
}, { deep: true });

const isDirty = computed(() => JSON.stringify(studentList.value.map(({ enrollment_id, status, notes }) => ({ enrollment_id, status, notes }))
    ) !== JSON.stringify(props.students.map(({ enrollment_id, status, notes }) => ({ enrollment_id, status, notes }))));

const summary = computed(() => studentList.value.reduce((counts, student) => {
    counts[student.status]++;
    return counts;
}, { present: 0, sick: 0, permitted: 0, absent: 0 }));

const selectedSession = computed(() => props.sessions.find((session) => String(session.id) === selectedSessionId.value));
const canSave = computed(() => isDirty.value || Boolean(selectedSession.value && selectedSession.value.attendance_count < selectedSession.value.student_count));
const sessionProgress = (session: SessionItem) => session.student_count
    ? Math.round(session.attendance_count / session.student_count * 100) : 0;

function visit(overrides: Record<string, string | undefined> = {}) {
    router.get('/akademik/presensi', {
        class_group_id: classId.value || undefined,
        subject_id: subjectId.value || undefined,
        month: month.value || undefined,
        session_id: selectedSessionId.value || undefined,
        ...overrides,
    }, { preserveScroll: true, preserveState: false, replace: true });
}

function changeClass() {
    subjectId.value = '';
    selectedSessionId.value = '';
    visit({ subject_id: undefined, session_id: undefined });
}

function changeSubject() {
    selectedSessionId.value = '';
    visit({ session_id: undefined });
}

function changeMonth() {
    selectedSessionId.value = '';
    visit({ session_id: undefined });
}

function selectSession(session: SessionItem) {
    selectedSessionId.value = String(session.id);
    visit();
}

function applyBulkStatus() {
    studentList.value.forEach((student) => { student.status = bulkStatus.value; });
}

function resetChanges() {
    if (!isDirty.value || !window.confirm('Reset perubahan absensi yang belum disimpan?')) return;
    studentList.value = cloneStudents(props.students);
}

function statusButtonClass(status: AttendanceStatus, selected: boolean): string {
    if (!selected) return 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50';
    return {
        present: 'border-emerald-600 bg-emerald-600 text-white',
        sick: 'border-amber-500 bg-amber-500 text-white',
        permitted: 'border-blue-600 bg-blue-600 text-white',
        absent: 'border-rose-600 bg-rose-600 text-white',
    }[status];
}

function submitAttendance() {
    if (!selectedSession.value || form.processing || !canSave.value) return;
    form.session_id = String(selectedSession.value.id);
    form.date = selectedSession.value.date;
    form.records = studentList.value.map(({ enrollment_id, status, notes }) => ({
        enrollment_id, status, notes: notes || '',
    }));
    form.post('/akademik/presensi', { preserveScroll: true });
}

function exportSessionCsv() {
    if (!selectedSession.value) return;
    const escapeCsv = (value: string | number) => `"${String(value).replaceAll('"', '""')}"`;
    const statusLabels: Record<AttendanceStatus, string> = { present: 'Hadir', sick: 'Sakit', permitted: 'Izin', absent: 'Alpa' };
    const rows = [
        ['No', 'NIS', 'Nama siswa', 'JK', 'Status', 'Catatan'],
        ...studentList.value.map((student, index) => [student.roll_number || index + 1, student.nis, student.name, student.gender, statusLabels[student.status], student.notes]),
    ];
    const content = `\uFEFF${rows.map((row) => row.map(escapeCsv).join(',')).join('\r\n')}`;
    const url = URL.createObjectURL(new Blob([content], { type: 'text/csv;charset=utf-8' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `presensi-${props.selected_class?.name.replaceAll(/[^a-z0-9-]/gi, '-')}-${selectedSession.value.date}.csv`;
    link.click();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}

function restoreSelection() {
    classId.value = props.selected_class?.id ? String(props.selected_class.id) : '';
    subjectId.value = props.selected_subject?.id ? String(props.selected_subject.id) : '';
    month.value = props.month;
    selectedSessionId.value = props.selected_session_id ? String(props.selected_session_id) : '';
}

const warnBeforeUnload = (event: BeforeUnloadEvent) => {
    if (isDirty.value) { event.preventDefault(); event.returnValue = ''; }
};

let removeBeforeListener: (() => void) | undefined;
onMounted(() => {
    window.addEventListener('beforeunload', warnBeforeUnload);
    removeBeforeListener = router.on('before', event => {
        if (event.detail.visit.method === 'get' && isDirty.value && !window.confirm('Ada perubahan absensi yang belum disimpan. Tinggalkan perubahan?')) {
            restoreSelection();
            event.preventDefault();
        }
    });
});
onBeforeUnmount(() => { window.removeEventListener('beforeunload', warnBeforeUnload); removeBeforeListener?.(); });
</script>

<template>
    <AppLayout title="Absensi Siswa">
        <Head title="Absensi Siswa — School Suite" />
        <div class="view attendance-page mx-auto w-full max-w-[1440px] space-y-5">
            <header class="page-heading compact-heading flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="eyebrow">KEHADIRAN SISWA</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Absensi Siswa</h1>
                    <p class="mt-1 text-sm text-slate-500">Pilih kelas, mata pelajaran, dan bulan. Buka sesi untuk mencatat presensi.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link href="/" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Kembali</Link>
                    <button type="button" class="inline-flex min-h-10 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!selectedSession || !studentList.length" @click="exportSessionCsv">Ekspor CSV</button>
                    <a href="/akademik/presensi/rekap" class="inline-flex min-h-10 items-center rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Rekap Absensi</a>
                </div>
            </header>

            <section class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:grid-cols-2 xl:grid-cols-3" aria-label="Filter presensi">
                <label class="block text-xs font-semibold text-slate-600">Kelas
                    <select v-model="classId" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" @change="changeClass">
                        <option value="" disabled>Pilih kelas</option>
                        <option v-for="item in classes" :key="item.id" :value="String(item.id)">{{ item.name }} · Tingkat {{ item.grade_level }}</option>
                    </select>
                </label>
                <label class="block text-xs font-semibold text-slate-600">Mata pelajaran
                    <select v-model="subjectId" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" :disabled="subjects.length === 0" @change="changeSubject">
                        <option value="" disabled>Pilih mata pelajaran</option>
                        <option v-for="item in subjects" :key="item.id" :value="String(item.id)">{{ item.name }} ({{ item.code }})</option>
                    </select>
                </label>
                <label class="block text-xs font-semibold text-slate-600">Bulan
                    <input v-model="month" type="month" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" @change="changeMonth">
                </label>
            </section>

            <section v-if="classes.length && subjects.length" class="grid min-w-0 grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(280px,0.38fr)]">
                <div class="min-w-0 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pertemuan dipilih</p>
                            <h2 class="mt-1 text-lg font-bold text-slate-900">
                                {{ selectedSession ? `Pertemuan ${selectedSession.meeting_number} · ${selectedSession.date}` : 'Belum ada pertemuan' }}
                            </h2>
                            <p v-if="selectedSession" class="mt-1 text-sm text-slate-500">{{ selected_subject?.name }} · {{ selectedSession.start_time }}–{{ selectedSession.end_time }}</p>
                        </div>
                        <div v-if="studentList.length" class="flex w-full flex-wrap items-end gap-2 sm:w-auto">
                            <label class="min-w-40 flex-1 text-xs font-semibold text-slate-600 sm:flex-none">Input massal
                                <select v-model="bulkStatus" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                    <option value="present">Hadir</option><option value="sick">Sakit</option><option value="permitted">Izin</option><option value="absent">Alpa</option>
                                </select>
                            </label>
                            <button type="button" class="min-h-10 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300" @click="applyBulkStatus">Terapkan ke semua</button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold text-slate-600" aria-label="Legenda status">
                        <span class="font-bold text-slate-800">Legenda:</span>
                        <span class="inline-flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full bg-emerald-500"/>Hadir</span>
                        <span class="inline-flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full bg-amber-500"/>Sakit</span>
                        <span class="inline-flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full bg-blue-500"/>Izin</span>
                        <span class="inline-flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full bg-rose-500"/>Alpa</span>
                    </div>

                    <div v-if="selectedSession" class="grid grid-cols-2 gap-2 rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-4">
                        <div class="rounded-lg bg-emerald-50 p-3 text-center"><span class="block text-xs font-semibold text-emerald-700">Hadir</span><strong class="text-lg text-emerald-900">{{ summary.present }}</strong></div>
                        <div class="rounded-lg bg-amber-50 p-3 text-center"><span class="block text-xs font-semibold text-amber-700">Sakit</span><strong class="text-lg text-amber-900">{{ summary.sick }}</strong></div>
                        <div class="rounded-lg bg-blue-50 p-3 text-center"><span class="block text-xs font-semibold text-blue-700">Izin</span><strong class="text-lg text-blue-900">{{ summary.permitted }}</strong></div>
                        <div class="rounded-lg bg-rose-50 p-3 text-center"><span class="block text-xs font-semibold text-rose-700">Alpa</span><strong class="text-lg text-rose-900">{{ summary.absent }}</strong></div>
                    </div>

                    <form v-if="selectedSession" class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs" @submit.prevent="submitAttendance">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                            <div><h3 class="font-bold text-slate-900">Daftar siswa</h3><p class="text-xs text-slate-500">{{ studentList.length }} siswa · {{ selected_subject?.name }}</p></div>
                            <span v-if="selectedSession.attendance_count" class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ selectedSession.attendance_count }}/{{ selectedSession.student_count }} tercatat</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[720px] border-collapse text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600"><tr><th class="w-24 px-4 py-3">No / NIS</th><th class="min-w-44 px-4 py-3">Nama siswa</th><th class="min-w-64 px-4 py-3">Status</th><th class="min-w-40 px-4 py-3">Catatan</th></tr></thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    <tr v-for="(student, index) in studentList" :key="student.enrollment_id" class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-4 py-3 text-xs"><span class="block font-semibold text-slate-700">{{ student.roll_number || index + 1 }}</span><span class="text-slate-500">{{ student.nis }}</span></td>
                                        <td class="px-4 py-3"><span class="font-semibold text-slate-900">{{ student.name }}</span><span class="ml-2 text-xs text-slate-500">{{ student.gender }}</span></td>
                                        <td class="px-4 py-3"><div class="flex flex-wrap gap-1.5">
                                            <button v-for="option in ([['present','Hadir'],['sick','Sakit'],['permitted','Izin'],['absent','Alpa']] as [AttendanceStatus,string][])" :key="option[0]" type="button" :aria-pressed="student.status === option[0]" :class="statusButtonClass(option[0], student.status === option[0])" class="min-h-9 rounded-md border px-2.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-300" @click="student.status = option[0]">{{ option[1] }}</button>
                                        </div></td>
                                        <td class="px-4 py-3"><input v-model="student.notes" type="text" maxlength="255" placeholder="Opsional" class="w-full min-w-40 rounded-md border border-slate-300 bg-white px-2.5 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"></td>
                                    </tr>
                                    <tr v-if="studentList.length === 0"><td colspan="4" class="px-4 py-10 text-center text-slate-500">Belum ada siswa pada kelas dan semester aktif ini.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="sticky bottom-0 z-10 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-6px_18px_rgba(15,23,42,0.08)] backdrop-blur">
                            <p class="text-sm" :class="isDirty ? 'font-semibold text-amber-700' : 'text-slate-500'" role="status" aria-live="polite">
                                <template v-if="isDirty">● Ada perubahan yang belum disimpan</template>
                                <template v-else-if="canSave">Belum ada presensi untuk pertemuan ini</template>
                                <template v-else-if="form.recentlySuccessful">✓ Presensi tersimpan</template>
                                <template v-else>Semua perubahan tersimpan</template>
                            </p>
                            <div class="flex items-center gap-2">
                                <button type="button" :disabled="!isDirty" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" @click="resetChanges">Reset</button>
                                <button type="submit" :disabled="form.processing || !canSave || studentList.length === 0" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 disabled:cursor-not-allowed disabled:opacity-50">{{ form.processing ? 'Menyimpan…' : 'Simpan presensi' }}</button>
                            </div>
                        </div>
                    </form>
                    <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-600">Belum ada sesi pertemuan untuk kelas, mata pelajaran, dan bulan ini.</div>
                </div>

                <aside class="h-fit overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
                    <div class="border-b border-slate-200 px-4 py-3"><h3 class="font-bold text-slate-900">Riwayat pertemuan</h3><p class="mt-0.5 text-xs text-slate-500">Pilih sesi untuk melihat atau memperbarui presensi.</p></div>
                    <div v-if="sessions.length" class="max-h-[70vh] divide-y divide-slate-100 overflow-y-auto">
                        <button v-for="session in sessions" :key="session.id" type="button" class="block w-full px-4 py-3 text-left transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-400" :class="String(session.id) === selectedSessionId ? 'bg-indigo-50' : 'bg-white'" @click="selectSession(session)">
                            <div class="flex items-start justify-between gap-2"><span class="font-semibold text-slate-900">{{ new Date(`${session.date}T00:00:00`).toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'}) }}</span><span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold" :class="sessionProgress(session) === 100 ? 'bg-emerald-100 text-emerald-800' : session.attendance_count ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600'">{{ session.attendance_count }}/{{ session.student_count }}</span></div>
                            <p class="mt-1 text-xs text-slate-500">Pertemuan {{ session.meeting_number }} · {{ session.start_time }}–{{ session.end_time }}</p>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-indigo-500" :style="{ width: `${sessionProgress(session)}%` }" /></div>
                        </button>
                    </div>
                    <p v-else class="px-4 py-8 text-center text-sm text-slate-500">Belum ada riwayat sesi pada bulan ini.</p>
                </aside>
            </section>

            <section v-else class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                <h2 class="font-bold text-slate-900">{{ classes.length ? 'Belum ada penugasan mata pelajaran' : 'Belum ada penugasan mengajar' }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ classes.length ? 'Kelas ini belum memiliki mata pelajaran yang ditugaskan kepada Anda.' : 'Anda belum memiliki kelas pada periode akademik aktif.' }}</p>
            </section>
        </div>
    </AppLayout>
</template>
