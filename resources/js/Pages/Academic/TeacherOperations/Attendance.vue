<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { SharedProps } from '@/types';

type AttendanceStatus = 'present' | 'permitted' | 'sick' | 'absent';
interface AttendanceRow {
    teacher_id: number;
    name: string;
    nip: string | null;
    record_id: number | null;
    status: AttendanceStatus;
    notes: string;
    substitute_url: string | null;
    updated_at: string | null;
    recorded_by: string | null;
}
const props = defineProps<{
    date: string;
    today: string;
    rows: AttendanceRow[];
    can_manage_all: boolean;
    summary: { teachers: number; present: number; permitted: number; sick: number; absent: number };
}>();
const statuses: Array<{ value: AttendanceStatus; label: string }> = [
    { value: 'present', label: 'Hadir' }, { value: 'permitted', label: 'Izin' },
    { value: 'sick', label: 'Sakit' }, { value: 'absent', label: 'Alpha' },
];
const page = usePage<SharedProps>();
const message = computed(() => page.props.flash?.message);
const selectedDate = ref(props.date);
const search = ref('');
const statusFilter = ref('');
const recordingFilter = ref('');
const selectedIds = ref<number[]>([]);
const bulkStatus = ref<AttendanceStatus>('present');
const bulkScope = ref('unrecorded');
const bulkMessage = ref('');
const loadingDate = ref(false);
const submittedIndices = ref<number[]>([]);
const initialRecords = () => props.rows.map(row => ({ teacher_id: row.teacher_id, status: row.status, notes: row.notes }));
const form = useForm({ date: props.date, records: initialRecords() });
const isChanged = (index: number) => form.records[index].status !== props.rows[index].status || form.records[index].notes !== props.rows[index].notes;
const pendingIndices = computed(() => props.rows.flatMap((row, index) => row.record_id === null || isChanged(index) ? [index] : []));
const changedCount = computed(() => props.rows.filter((_, index) => isChanged(index)).length);
const unrecordedCount = computed(() => props.rows.filter(row => row.record_id === null).length);
const busy = computed(() => form.processing || loadingDate.value);
const visibleRows = computed(() => props.rows.map((row, index) => ({ row, index })).filter(({ row, index }) => {
    const needle = search.value.trim().toLocaleLowerCase();
    return (!needle || `${row.name} ${row.nip ?? ''}`.toLocaleLowerCase().includes(needle))
        && (!statusFilter.value || form.records[index].status === statusFilter.value)
        && (!recordingFilter.value || (recordingFilter.value === 'unrecorded' ? row.record_id === null : row.record_id !== null));
}));
const allVisibleSelected = computed(() => visibleRows.value.length > 0 && visibleRows.value.every(({ row }) => selectedIds.value.includes(row.teacher_id)));
const someVisibleSelected = computed(() => visibleRows.value.some(({ row }) => selectedIds.value.includes(row.teacher_id)));
const bulkIndices = computed(() => props.can_manage_all ? props.rows.flatMap((row, index) => {
    const include = bulkScope.value === 'all' || (bulkScope.value === 'unrecorded' && row.record_id === null)
        || (bulkScope.value === 'selected' && selectedIds.value.includes(row.teacher_id))
        || (bulkScope.value === 'filtered' && visibleRows.value.some(item => item.index === index));
    return include ? [index] : [];
}) : []);
const attendanceSummary = computed(() => Object.fromEntries(statuses.map(status => [status.value, form.records.filter(row => row.status === status.value).length])));

watch(() => [props.date, props.rows] as const, () => {
    selectedDate.value = props.date;
    form.date = props.date;
    form.records = initialRecords();
    form.defaults();
    selectedIds.value = [];
    bulkMessage.value = '';
    submittedIndices.value = [];
});
function showDate(value = selectedDate.value) {
    if (busy.value || !value || value > props.today) return;
    router.get('/akademik/absensi-guru', { date: value }, {
        preserveState: true, preserveScroll: true,
        onStart: () => { loadingDate.value = true; },
        onFinish: () => { loadingDate.value = false; },
    });
}
function shiftDate(days: number) {
    const value = new Date(`${props.date}T12:00:00`);
    value.setDate(value.getDate() + days);
    const target = `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
    showDate(target);
}
function save() {
    if (busy.value || !pendingIndices.value.length) return;
    submittedIndices.value = [...pendingIndices.value];
    form.transform(data => ({ date: props.date, records: submittedIndices.value.map(index => data.records[index]) }))
        .post('/akademik/absensi-guru', { preserveScroll: true });
}
function applyBulkStatus() {
    if (!props.can_manage_all || busy.value || !bulkIndices.value.length) return;
    const indices = [...bulkIndices.value];
    indices.forEach(index => { form.records[index].status = bulkStatus.value; });
    bulkMessage.value = `${indices.length} guru ditandai ${statuses.find(status => status.value === bulkStatus.value)?.label}. Periksa lalu klik Simpan absensi.`;
}
function resetChanges() {
    if (busy.value) return;
    form.records = initialRecords();
    bulkMessage.value = '';
    form.clearErrors();
}
function toggleVisible(checked: boolean) {
    const visibleIds = visibleRows.value.map(({ row }) => row.teacher_id);
    selectedIds.value = checked ? [...new Set([...selectedIds.value, ...visibleIds])] : selectedIds.value.filter(id => !visibleIds.includes(id));
}
function recordError(index: number, field: string) {
    const submittedIndex = submittedIndices.value.indexOf(index);
    return submittedIndex < 0 ? undefined : (form.errors as Record<string, string>)[`records.${submittedIndex}.${field}`];
}
const formatDate = (value: string) => new Date(`${value}T12:00:00`).toLocaleDateString('id-ID', { dateStyle: 'full' });
const formatUpdated = (value: string) => new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
const warnBeforeUnload = (event: BeforeUnloadEvent) => {
    if (changedCount.value > 0) { event.preventDefault(); event.returnValue = ''; }
};
let removeBeforeListener: (() => void) | undefined;
onMounted(() => {
    window.addEventListener('beforeunload', warnBeforeUnload);
    removeBeforeListener = router.on('before', event => {
        if (event.detail.visit.method === 'get' && changedCount.value > 0 && !window.confirm('Ada perubahan absensi yang belum disimpan. Tinggalkan perubahan?')) event.preventDefault();
    });
});
onBeforeUnmount(() => { window.removeEventListener('beforeunload', warnBeforeUnload); removeBeforeListener?.(); });
</script>

<template>
    <AppLayout title="Absensi Guru">
        <Head title="Absensi Guru" />
        <main class="view attendance-page space-y-5">
            <section class="hero-card">
                <div><p class="eyebrow">Administrasi akademik</p><h1>Absensi guru</h1><p>Catat kehadiran harian, periksa izin, dan atur guru pengganti.</p></div>
                <div class="flex flex-wrap gap-2"><Link class="secondary-button" href="/akademik/absensi-guru/rekap">Rekap bulanan</Link><Link class="secondary-button" href="/akademik/izin-guru">Izin &amp; cuti</Link></div>
            </section>
            <p v-if="message" class="panel p-4" role="status">{{ message }}</p>
            <section class="grade-summary" aria-label="Ringkasan isian absensi">
                <div><span>Guru aktif</span><strong>{{ rows.length }}</strong></div>
                <div><span>Sudah dicatat</span><strong>{{ rows.length - unrecordedCount }}</strong></div>
                <div><span>Belum dicatat</span><strong>{{ unrecordedCount }}</strong></div>
                <div><span>Perubahan</span><strong>{{ changedCount }}</strong></div>
            </section>
            <section class="panel p-5">
                <form class="flex flex-wrap items-end gap-3" @submit.prevent="showDate()">
                    <label class="grid gap-1 text-sm font-medium">Tanggal<input v-model="selectedDate" type="date" :max="today" required :disabled="busy" class="min-h-10 rounded-lg border px-3" /></label>
                    <button class="secondary-button" :disabled="busy" type="submit">{{ loadingDate ? 'Memuat…' : 'Tampilkan' }}</button>
                    <button class="secondary-button" :disabled="busy" type="button" @click="shiftDate(-1)">Hari sebelumnya</button>
                    <button class="secondary-button" :disabled="busy || date >= today" type="button" @click="shiftDate(1)">Hari berikutnya</button>
                    <button class="secondary-button" :disabled="busy || date === today" type="button" @click="showDate(today)">Hari ini</button>
                </form>
                <p class="mt-3 text-sm text-slate-500">{{ can_manage_all ? 'Kelola seluruh guru aktif di sekolah.' : 'Anda hanya dapat mengisi absensi sendiri.' }}</p>
                <p v-if="page.props.errors?.date" class="mt-2 text-sm text-red-700" role="alert">{{ page.props.errors.date }}</p>
            </section>
            <section class="panel overflow-hidden">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4"><div><h2 class="text-base font-semibold">{{ formatDate(date) }}</h2><p class="mt-1 text-sm text-slate-500">Isian awal Hadir adalah usulan, belum tercatat sampai disimpan.</p></div><span class="text-sm text-slate-500">{{ visibleRows.length }} dari {{ rows.length }} guru</span></header>
                <div v-if="!rows.length" class="px-6 py-14 text-center text-sm text-slate-500">{{ can_manage_all ? 'Belum ada guru aktif untuk dicatat.' : 'Data guru aktif Anda belum tersedia.' }}</div>
                <form v-else @submit.prevent="save">
                    <div v-if="can_manage_all" class="space-y-4 border-b px-5 py-4">
                        <div class="flex flex-wrap gap-3">
                            <input v-model="search" type="search" aria-label="Cari guru atau NIP" placeholder="Cari nama atau NIP…" class="min-w-0 flex-1 rounded-lg border px-3 py-2" />
                            <select v-model="statusFilter" aria-label="Filter status kehadiran" class="rounded-lg border px-3 py-2"><option value="">Semua status</option><option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option></select>
                            <select v-model="recordingFilter" aria-label="Filter pencatatan" class="rounded-lg border px-3 py-2"><option value="">Semua pencatatan</option><option value="unrecorded">Belum dicatat</option><option value="recorded">Sudah dicatat</option></select>
                            <button type="button" class="secondary-button" @click="search = ''; statusFilter = ''; recordingFilter = ''">Reset filter</button>
                        </div>
                        <div class="bulk-controls">
                            <label>Status massal<select v-model="bulkStatus" aria-label="Status massal" :disabled="busy"><option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option></select></label>
                            <label>Terapkan pada<select v-model="bulkScope" aria-label="Sasaran aksi massal" :disabled="busy"><option value="unrecorded">Guru belum dicatat</option><option value="selected">Guru dipilih ({{ selectedIds.length }})</option><option value="filtered">Hasil filter ({{ visibleRows.length }})</option><option value="all">Semua guru ({{ rows.length }})</option></select></label>
                            <button type="button" class="secondary-button" :disabled="busy || !bulkIndices.length" @click="applyBulkStatus">Terapkan ke {{ bulkIndices.length }} guru</button>
                            <button v-if="selectedIds.length" type="button" class="secondary-button" :disabled="busy" @click="selectedIds = []">Batalkan pilihan</button>
                        </div>
                        <p class="text-xs text-slate-500">Aksi massal hanya mengubah status; keterangan dipertahankan. Pilihan guru tetap berlaku saat filter berubah. Perubahan menunggu tombol Simpan absensi.</p>
                    </div>
                    <p v-if="bulkMessage" class="px-5 py-3 text-sm text-blue-700" role="status">{{ bulkMessage }}</p>
                    <ul v-if="Object.keys(form.errors).length" class="px-5 py-3 text-sm text-red-700" role="alert"><li v-for="(error, field) in form.errors" :key="field">{{ error }}</li></ul>
                    <div class="flex flex-wrap gap-4 border-b px-5 py-3 text-xs text-slate-500" aria-label="Ringkasan status isian"><span v-for="status in statuses" :key="status.value">{{ status.label }}: {{ attendanceSummary[status.value] }}</span><span>Termasuk usulan yang belum disimpan</span></div>
                    <div class="overflow-x-auto">
                        <table class="attendance-table w-full text-left text-sm">
                            <thead><tr><th v-if="can_manage_all"><input type="checkbox" :checked="allVisibleSelected" :indeterminate="someVisibleSelected && !allVisibleSelected" :disabled="busy || !visibleRows.length" aria-label="Pilih semua guru hasil filter" @change="toggleVisible(($event.target as HTMLInputElement).checked)" /></th><th>Guru</th><th>Status</th><th>Keterangan</th><th>Pencatatan &amp; pengganti</th></tr></thead>
                            <tbody>
                                <tr v-for="{ row, index } in visibleRows" :key="row.teacher_id">
                                    <td v-if="can_manage_all" data-label="Pilih"><input v-model="selectedIds" type="checkbox" :value="row.teacher_id" :disabled="busy" :aria-label="`Pilih ${row.name}`" /></td>
                                    <td data-label="Guru"><strong class="block">{{ row.name }}</strong><small class="text-slate-500">NIP {{ row.nip || '—' }}</small><span v-if="isChanged(index)" class="attendance-badge is-pending">Diubah</span></td>
                                    <td data-label="Status"><select v-model="form.records[index].status" :disabled="busy" :aria-label="`Status ${row.name}`"><option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option></select><small v-if="recordError(index, 'status')" class="block text-red-700">{{ recordError(index, 'status') }}</small></td>
                                    <td data-label="Keterangan"><input v-model="form.records[index].notes" :disabled="busy" :aria-label="`Keterangan ${row.name}`" maxlength="255" placeholder="Keterangan opsional" /><small v-if="recordError(index, 'notes')" class="block text-red-700">{{ recordError(index, 'notes') }}</small></td>
                                    <td data-label="Pencatatan"><span class="attendance-badge" :class="row.record_id ? 'is-saved' : 'is-pending'">{{ row.record_id ? 'Tercatat' : 'Belum dicatat' }}</span><small v-if="row.updated_at" class="mt-1 block text-slate-500">{{ formatUpdated(row.updated_at) }}{{ row.recorded_by ? ` · ${row.recorded_by}` : '' }}</small><Link v-if="row.substitute_url && !isChanged(index)" class="mt-2 block text-sm font-semibold text-blue-700" :href="row.substitute_url">Atur pengganti</Link><small v-else-if="form.records[index].status !== 'present'" class="mt-2 block text-slate-500">{{ isChanged(index) || !row.record_id ? 'Simpan untuk mengatur pengganti.' : '' }}</small></td>
                                </tr>
                                <tr v-if="!visibleRows.length"><td :colspan="can_manage_all ? 5 : 4" class="py-10 text-center text-slate-500">Tidak ada guru sesuai filter. <button class="font-semibold underline" type="button" @click="search = ''; statusFilter = ''; recordingFilter = ''">Reset filter</button></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <footer class="attendance-save-bar">
                        <div><strong>{{ pendingIndices.length }} catatan perlu disimpan</strong><p class="mt-1 text-xs text-slate-500">Mencakup guru baru dan perubahan, termasuk yang tidak terlihat karena filter.</p></div>
                        <div class="flex flex-wrap gap-2"><button type="button" class="secondary-button" :disabled="busy || !changedCount" @click="resetChanges">Batalkan perubahan</button><button class="primary-button" :disabled="busy || !pendingIndices.length">{{ form.processing ? 'Menyimpan…' : 'Simpan absensi' }}</button></div>
                    </footer>
                </form>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.attendance-page { min-width: 0; }
.attendance-page input, .attendance-page select { max-width: 100%; }
.bulk-controls { display: flex; flex-wrap: wrap; align-items: end; gap: .75rem; }
.bulk-controls label { display: grid; gap: .35rem; font-size: .875rem; font-weight: 600; }
.bulk-controls select, .attendance-table select, .attendance-table input:not([type='checkbox']) { min-height: 2.5rem; width: 100%; padding: .5rem .75rem; border: 1px solid var(--line); border-radius: .5rem; background: var(--surface); color: var(--ink); }
.attendance-table th { padding: .75rem 1rem; background: var(--surface-subtle); color: var(--ink-soft); font-size: .75rem; }
.attendance-table td { padding: 1rem; border-top: 1px solid var(--line); vertical-align: top; }
.attendance-table input[type='checkbox'] { width: 1.1rem; height: 1.1rem; }
.attendance-badge { display: inline-block; padding: .25rem .5rem; border-radius: .5rem; font-size: .7rem; font-weight: 650; }
.is-pending { color: var(--gold); background: var(--gold-soft); }
.is-saved { color: var(--green); background: var(--green-soft); }
.attendance-save-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-top: 1px solid var(--line); background: var(--surface); }
@media (max-width: 700px) {
    .attendance-table thead { display: none; }
    .attendance-table, .attendance-table tbody, .attendance-table tr { display: block; }
    .attendance-table tr { padding: .5rem 1rem; border-bottom: 1px solid var(--line); }
    .attendance-table td { height: auto; display: grid; grid-template-columns: 6rem minmax(0, 1fr); gap: .5rem; padding: .5rem 0; border: 0; }
    .attendance-table td::before { content: attr(data-label); color: var(--ink-soft); font-size: .75rem; }
    .attendance-table td > small, .attendance-table td > a, .attendance-table td > span, .attendance-table td > strong { grid-column: 2; }
    .bulk-controls label, .bulk-controls button { width: 100%; }
    .attendance-save-bar { align-items: stretch; }
    .attendance-save-bar > div { width: 100%; }
    .attendance-save-bar button { flex: 1; }
}
</style>
