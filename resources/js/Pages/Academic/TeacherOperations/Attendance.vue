<script setup lang="ts">
import { requestConfirmation } from '@/composables/useActionConfirmation';
import AppButton from '@/components/AppButton.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
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
async function resetChanges() {
    if (busy.value || !changedCount.value || !await requestConfirmation('Batalkan perubahan absensi yang belum disimpan?')) return;
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
let leaveApproved = false;
onMounted(() => {
    window.addEventListener('beforeunload', warnBeforeUnload);
    removeBeforeListener = router.on('before', event => {
        if (!leaveApproved && event.detail.visit.method === 'get' && changedCount.value > 0) {
            event.preventDefault();
            const visit = event.detail.visit;
            void requestConfirmation('Ada perubahan absensi yang belum disimpan. Tinggalkan perubahan?').then((accepted) => {
                if (!accepted) { selectedDate.value = props.date; return; }
                leaveApproved = true;
                router.visit(visit.url, { ...visit, onStart: () => { loadingDate.value = true; }, onFinish: () => { leaveApproved = false; loadingDate.value = false; } });
            });
        }
    });
});
onBeforeUnmount(() => { window.removeEventListener('beforeunload', warnBeforeUnload); removeBeforeListener?.(); });
</script>

<template>
    <AppLayout title="Absensi Guru">
        <Head title="Absensi Guru" />
        <main class="view attendance-page space-y-5">
            <section class="hero-card attendance-hero">
                <div class="attendance-heading"><p class="eyebrow">Akademik · Kehadiran guru</p><h1>Presensi guru</h1><p>{{ can_manage_all ? 'Catat kehadiran harian, periksa izin, dan atur guru pengganti.' : 'Catat kehadiran harian dan periksa riwayat presensi Anda.' }}</p></div>
                <div class="attendance-heading-actions"><Link class="secondary-button ui-button ui-button--secondary ui-button--md" href="/akademik/absensi-guru/rekap">Rekap bulanan</Link><Link class="secondary-button ui-button ui-button--secondary ui-button--md" href="/akademik/izin-guru">Izin &amp; cuti</Link></div>
            </section>
            <p v-if="message" class="panel p-4" role="status">{{ message }}</p>
            <section class="ss-summary-grid" aria-label="Ringkasan isian absensi">
                <MetricSummaryCard
                    label="Guru aktif"
                    :value="rows.length"
                    tone="blue"
                    icon="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                />
                <MetricSummaryCard
                    label="Sudah dicatat"
                    :value="rows.length - unrecordedCount"
                    tone="green"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    label="Belum dicatat"
                    :value="unrecordedCount"
                    tone="amber"
                    icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    label="Perubahan"
                    :value="changedCount"
                    tone="violet"
                    icon="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
            </section>
            <section class="panel p-5">
                <form class="flex flex-wrap items-end gap-3" @submit.prevent="showDate()">
                    <label class="grid gap-1 text-sm font-medium">Tanggal<input v-model="selectedDate" type="date" :max="today" required :disabled="busy" class="min-h-10 rounded-lg border px-3" /></label>
                    <AppButton variant="primary" class="secondary-button" :disabled="busy" type="submit">{{ loadingDate ? 'Memuat…' : 'Tampilkan' }}</AppButton>
                    <AppButton variant="secondary" class="secondary-button" :disabled="busy" type="button" @click="shiftDate(-1)">Hari sebelumnya</AppButton>
                    <AppButton variant="secondary" type="button" class="secondary-button" :disabled="busy || date >= today" @click="shiftDate(1)">Hari berikutnya</AppButton>
                    <AppButton variant="secondary" class="secondary-button" :disabled="busy || date === today" type="button" @click="showDate(today)">Hari ini</AppButton>
                </form>
                <p class="mt-3 text-sm text-[var(--ink-soft)]">{{ can_manage_all ? 'Kelola seluruh guru aktif di sekolah.' : 'Anda hanya dapat mengisi absensi sendiri.' }}</p>
                <p v-if="page.props.errors?.date" class="mt-2 text-sm text-red-700" role="alert">{{ page.props.errors.date }}</p>
            </section>
            <section class="panel attendance-list-panel overflow-hidden">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4"><div><h2 class="text-base font-semibold">{{ formatDate(date) }}</h2><p class="mt-1 text-sm text-[var(--ink-soft)]">Isian awal Hadir adalah usulan, belum tercatat sampai disimpan.</p></div><span class="text-sm text-[var(--ink-soft)]">{{ visibleRows.length }} dari {{ rows.length }} guru</span></header>
                <div v-if="!rows.length" class="px-6 py-14 text-center text-sm text-[var(--ink-soft)]">{{ can_manage_all ? 'Belum ada guru aktif untuk dicatat.' : 'Data guru aktif Anda belum tersedia.' }}</div>
                <form v-else @submit.prevent="save">
                    <div v-if="can_manage_all" class="space-y-4 border-b px-5 py-4">
                        <div class="flex flex-wrap gap-3">
                            <input v-model="search" type="search" aria-label="Cari guru atau NIP" placeholder="Cari nama atau NIP…" class="search-control min-w-0 flex-1" />
                            <select v-model="statusFilter" aria-label="Filter status kehadiran" class="rounded-lg border px-3 py-2"><option value="">Semua status</option><option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option></select>
                            <select v-model="recordingFilter" aria-label="Filter pencatatan" class="rounded-lg border px-3 py-2"><option value="">Semua pencatatan</option><option value="unrecorded">Belum dicatat</option><option value="recorded">Sudah dicatat</option></select>
                            <AppButton variant="secondary" type="button" class="secondary-button" @click="search = ''; statusFilter = ''; recordingFilter = ''">Reset filter</AppButton>
                        </div>
                        <div class="bulk-controls">
                            <label>Status massal<select v-model="bulkStatus" aria-label="Status massal" :disabled="busy"><option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option></select></label>
                            <label>Terapkan pada<select v-model="bulkScope" aria-label="Sasaran aksi massal" :disabled="busy"><option value="unrecorded">Guru belum dicatat</option><option value="selected">Guru dipilih ({{ selectedIds.length }})</option><option value="filtered">Hasil filter ({{ visibleRows.length }})</option><option value="all">Semua guru ({{ rows.length }})</option></select></label>
                            <AppButton variant="secondary" type="button" class="secondary-button" :disabled="busy || !bulkIndices.length" @click="applyBulkStatus">Terapkan ke {{ bulkIndices.length }} guru</AppButton>
                            <AppButton variant="secondary" v-if="selectedIds.length" type="button" class="secondary-button" :disabled="busy" @click="selectedIds = []">Batalkan pilihan</AppButton>
                        </div>
                        <p class="text-meta text-[var(--ink-soft)]">Aksi massal hanya mengubah status; keterangan dipertahankan. Pilihan guru tetap berlaku saat filter berubah. Perubahan menunggu tombol Simpan absensi.</p>
                    </div>
                    <p v-if="bulkMessage" class="px-5 py-3 text-sm text-blue-700" role="status">{{ bulkMessage }}</p>
                    <ul v-if="Object.keys(form.errors).length" class="px-5 py-3 text-sm text-red-700" role="alert"><li v-for="(error, field) in form.errors" :key="field">{{ error }}</li></ul>
                    <div class="flex flex-wrap gap-4 border-b px-5 py-3 text-meta text-[var(--ink-soft)]" aria-label="Ringkasan status isian"><span v-for="status in statuses" :key="status.value">{{ status.label }}: {{ attendanceSummary[status.value] }}</span><span>Termasuk usulan yang belum disimpan</span></div>
                    <div class="overflow-x-auto table-scroll" tabindex="0" role="region" aria-label="Tabel data">
                        <table class="app-table attendance-table w-full text-left text-sm">
                            <thead><tr><th scope="col" v-if="can_manage_all"><input type="checkbox" :checked="allVisibleSelected" :indeterminate="someVisibleSelected && !allVisibleSelected" :disabled="busy || !visibleRows.length" aria-label="Pilih semua guru hasil filter" @change="toggleVisible(($event.target as HTMLInputElement).checked)" /></th><th scope="col">Guru</th><th scope="col">Status</th><th scope="col">Keterangan</th><th scope="col">Pencatatan &amp; pengganti</th></tr></thead>
                            <tbody>
                                <tr v-for="{ row, index } in visibleRows" :key="row.teacher_id">
                                    <td v-if="can_manage_all" data-label="Pilih"><input v-model="selectedIds" type="checkbox" :value="row.teacher_id" :disabled="busy" :aria-label="`Pilih ${row.name}`" /></td>
                                    <td data-label="Guru"><strong class="block">{{ row.name }}</strong><small class="text-[var(--ink-soft)]">NIP {{ row.nip || '—' }}</small><span v-if="isChanged(index)" class="attendance-badge is-pending">Diubah</span></td>
                                    <td data-label="Status"><select v-model="form.records[index].status" :disabled="busy" :aria-label="`Status ${row.name}`"><option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option></select><small v-if="recordError(index, 'status')" class="block text-red-700">{{ recordError(index, 'status') }}</small></td>
                                    <td data-label="Keterangan"><input v-model="form.records[index].notes" :disabled="busy" :aria-label="`Keterangan ${row.name}`" maxlength="255" placeholder="Keterangan opsional" /><small v-if="recordError(index, 'notes')" class="block text-red-700">{{ recordError(index, 'notes') }}</small></td>
                                    <td data-label="Pencatatan"><span class="attendance-badge" :class="row.record_id ? 'is-saved' : 'is-pending'">{{ row.record_id ? 'Tercatat' : 'Belum dicatat' }}</span><small v-if="row.updated_at" class="mt-1 block text-[var(--ink-soft)]">{{ formatUpdated(row.updated_at) }}{{ row.recorded_by ? ` · ${row.recorded_by}` : '' }}</small><Link v-if="row.substitute_url && !isChanged(index)" class="mt-2 block text-sm font-semibold text-blue-700" :href="row.substitute_url">Atur pengganti</Link><small v-else-if="form.records[index].status !== 'present'" class="mt-2 block text-[var(--ink-soft)]">{{ isChanged(index) || !row.record_id ? 'Simpan untuk mengatur pengganti.' : '' }}</small></td>
                                </tr>
                                <tr v-if="!visibleRows.length"><td :colspan="can_manage_all ? 5 : 4" class="py-10 text-center text-[var(--ink-soft)]">Tidak ada guru sesuai filter. <button class="font-semibold underline" type="button" @click="search = ''; statusFilter = ''; recordingFilter = ''">Reset filter</button></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <footer class="attendance-save-bar">
                        <div><strong>{{ pendingIndices.length }} catatan perlu disimpan</strong><p class="mt-1 text-meta text-[var(--ink-soft)]">Mencakup guru baru dan perubahan, termasuk yang tidak terlihat karena filter.</p></div>
                        <div class="flex flex-wrap gap-2"><AppButton variant="secondary" type="button" class="secondary-button" :disabled="busy || !changedCount" @click="resetChanges">Batalkan perubahan</AppButton><AppButton variant="primary" type="submit" class="primary-button" :disabled="busy || !pendingIndices.length">{{ form.processing ? 'Menyimpan…' : 'Simpan absensi' }}</AppButton></div>
                    </footer>
                </form>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.attendance-page { min-width: 0; }
.attendance-hero { min-height: 0; padding: .5rem 1.25rem 1.25rem; color: var(--ink); background: transparent; border: 0; border-radius: 0; box-shadow: none; }
.attendance-hero h1 { color: var(--ink); font-size: var(--type-3xl); }
.attendance-hero p { color: var(--ink-soft); }
.attendance-hero .eyebrow { color: var(--primary); font-size: var(--type-meta); font-weight: var(--weight-semibold); letter-spacing: .03em; text-transform: none; }
.attendance-hero { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.attendance-heading { min-width: 0; }
.attendance-heading .eyebrow { margin: 0 0 .4rem; }
.attendance-heading h1 { margin: 0; font-weight: var(--weight-bold); line-height: 1.2; letter-spacing: -.035em; }
.attendance-heading > p:last-child { max-width: 40rem; margin: .5rem 0 0; font-size: var(--type-md); line-height: 1.55; }
.attendance-heading-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
@media (max-width: 700px) { .attendance-heading-actions { width: 100%; } }
.attendance-list-panel { border-radius: 14px; }
.attendance-page input, .attendance-page select { max-width: 100%; }
.bulk-controls { display: flex; flex-wrap: wrap; align-items: end; gap: .75rem; }
.bulk-controls label { display: grid; gap: .35rem; font-size: var(--type-md); font-weight: var(--weight-semibold); }
.bulk-controls select, .attendance-table select, .attendance-table input:not([type='checkbox']) { min-height: 2.5rem; width: 100%; padding: .5rem .75rem; border: 1px solid var(--line); border-radius: .5rem; background: var(--surface); color: var(--ink); }
.attendance-table th { padding: .75rem 1rem; background: var(--surface-subtle); color: var(--ink-soft); font-size: var(--type-meta); }
.attendance-table td { padding: 1rem; border-top: 1px solid var(--line); vertical-align: top; }
.attendance-table input[type='checkbox'] { width: 1.1rem; height: 1.1rem; }
.attendance-badge { display: inline-block; padding: .25rem .5rem; border-radius: .5rem; font-size: var(--type-meta); font-weight: var(--weight-semibold); }
.is-pending { color: var(--gold); background: var(--gold-soft); }
.is-saved { color: var(--green); background: var(--green-soft); }
.attendance-save-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-top: 1px solid var(--line); background: var(--surface); }
@media (max-width: 700px) {
    .attendance-table thead { display: none; }
    .attendance-table, .attendance-table tbody, .attendance-table tr { display: block; }
    .attendance-table tr { margin: 12px; padding: .6rem .9rem; background: var(--surface); border: 1px solid var(--line); border-radius: 12px; }
    .attendance-table td { height: auto; display: grid; grid-template-columns: 6rem minmax(0, 1fr); gap: .5rem; padding: .5rem 0; border: 0; }
    .attendance-table td::before { content: attr(data-label); color: var(--ink-soft); font-size: var(--type-meta); }
    .attendance-table td > small, .attendance-table td > a, .attendance-table td > span, .attendance-table td > strong { grid-column: 2; }
    .bulk-controls label, .bulk-controls button { width: 100%; }
    .attendance-save-bar { align-items: stretch; }
    .attendance-save-bar > div { width: 100%; }
    .attendance-save-bar button { flex: 1; }
}
/* Keep status and recording controls visually centered while leaving teacher and notes readable. */
.attendance-table td[data-label="Status"], .attendance-table td[data-label="Pencatatan"] { text-align: center; }
.attendance-table td[data-label="Status"] select { text-align: center; text-align-last: center; }
.attendance-table td[data-label="Pencatatan"] > span, .attendance-table td[data-label="Pencatatan"] > a { margin-inline: auto; }
</style>

<style scoped>
/* Summary metrics share the same quiet card treatment across menus. */
.grade-summary { gap: .75rem; overflow: visible; background: transparent; border: 0; box-shadow: none; }
.grade-summary > div, .grade-summary > article { display: grid; align-content: center; gap: .25rem; min-width: 0; text-align: center; background: var(--surface); border: 1px solid var(--line); border-top: 3px solid #3157a4; border-radius: .8rem; box-shadow: none; }
.grade-summary > div:nth-child(2), .grade-summary > article:nth-child(2) { border-top-color: #7952a3; }
.grade-summary > div:nth-child(3), .grade-summary > article:nth-child(3) { border-top-color: #138a62; }
.grade-summary > div:nth-child(4), .grade-summary > article:nth-child(4) { border-top-color: #168782; }
.grade-summary > div::before, .grade-summary > article::before { content: none; }
.grade-summary > .score-summary-green { border-top-color: #138a62; }
.grade-summary > .score-summary-red { border-top-color: #b42318; }
.grade-summary > .score-summary-gold { border-top-color: #c28a27; }
</style>
