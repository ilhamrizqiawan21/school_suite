<script setup lang="ts">
import type { SharedProps } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface LeaveRow {
    id: number; teacher_id: number; teacher_name: string; teacher_nip: string | null; type: string; type_label: string;
    start_date: string; end_date: string; date_range_label: string; reason: string; status: string;
    reviewed_at: string | null; duration_days: number; review_note: string | null; reviewer_name: string | null; can_cancel: boolean; can_review: boolean;
}
const props = defineProps<{
    leaves: { total: number; data: LeaveRow[]; links: { url: string | null; label: string; active: boolean }[] };
    today: string; min_start_date: string;
    filters: { search: string; type: string; month: string };
    summary: { all: number; pending: number; approved: number; rejected: number };
    filter: string; can_apply: boolean; can_review: boolean; types: Record<string, string>; max_days: number;
}>();

const page = usePage<SharedProps>();
const search = ref(props.filters.search);
const selectedType = ref(props.filters.type);
const month = ref(props.filters.month);
const busyId = ref<number | null>(null);
const actionErrors = ref<string[]>([]);
const message = computed(() => page.props.flash?.message);
const showForm = ref(false);
const form = useForm({ type: 'permission', start_date: props.today, end_date: props.today, reason: '' });
const reviewNotes = ref<Record<number, string>>({});
const statusLabels: Record<string, string> = { pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' };
const statusClasses: Record<string, string> = { pending: 'bg-amber-50 text-amber-800', approved: 'bg-emerald-50 text-emerald-800', rejected: 'bg-rose-50 text-rose-800' };
const minDate = computed(() => props.min_start_date);
const maxEnd = computed(() => {
    if (!form.start_date) return undefined;
    const d = new Date(`${form.start_date}T12:00:00`);
    if (!Number.isFinite(d.getTime())) return undefined;
    d.setDate(d.getDate() + props.max_days - 1);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
});
const duration = computed(() => {
    if (!form.start_date || !form.end_date) return 0;
    const days = Math.round((Date.parse(`${form.end_date}T12:00:00Z`) - Date.parse(`${form.start_date}T12:00:00Z`)) / 86400000) + 1;
    return Number.isFinite(days) && days > 0 ? days : 0;
});
const statusTabs = [{ value: 'all', label: 'Semua' }, { value: 'pending', label: 'Menunggu' }, { value: 'approved', label: 'Disetujui' }, { value: 'rejected', label: 'Ditolak' }] as const;
const filterUrl = (status: string) => '/akademik/izin-guru?' + new URLSearchParams({ status, search: search.value, type: selectedType.value, month: month.value });
function applyFilters() { router.get(filterUrl(props.filter), {}, { preserveScroll: true }); }
function resetFilters() { search.value = ''; selectedType.value = ''; month.value = ''; router.get('/akademik/izin-guru', {}, { preserveScroll: true }); }
function closeForm() { if (!form.processing && (!form.isDirty || window.confirm('Batalkan perubahan formulir yang belum dikirim?'))) { showForm.value = false; form.reset(); form.clearErrors(); } }

function submit() {
    form.post('/akademik/izin-guru', { preserveScroll: true, onSuccess: () => { form.reset(); form.type = 'permission'; form.start_date = props.today; form.end_date = form.start_date; showForm.value = false; } });
}
function cancel(leave: LeaveRow) {
    if (busyId.value !== null || !window.confirm('Batalkan pengajuan izin/cuti ini?')) return;
    actionErrors.value = [];
    router.delete(`/akademik/izin-guru/${leave.id}`, { preserveScroll: true, onStart: () => { busyId.value = leave.id; }, onFinish: () => { busyId.value = null; }, onError: errors => { actionErrors.value = Object.values(errors).map(String); } });
}
function review(leave: LeaveRow, decision: 'approve' | 'reject') {
    if (busyId.value !== null) return;
    actionErrors.value = [];
    const prompt = decision === 'approve' ? 'Setujui pengajuan ini? Absensi akan diisi pada hari selain Minggu; catatan yang sudah ada dipertahankan.' : 'Tolak pengajuan ini?';
    if (!window.confirm(prompt)) return;
    router.patch(`/akademik/izin-guru/${leave.id}/keputusan`, { decision, note: reviewNotes.value[leave.id] ?? '' }, { preserveScroll: true, onStart: () => { busyId.value = leave.id; }, onFinish: () => { busyId.value = null; }, onError: errors => { actionErrors.value = Object.values(errors).map(String); }, onSuccess: () => { delete reviewNotes.value[leave.id]; } });
}
function setStartDate() {
    if (form.end_date < form.start_date) form.end_date = form.start_date;
}
</script>

<template>
    <AppLayout title="Izin & Cuti Guru">
        <Head title="Izin & Cuti Guru" />
        <main class="view leave-page space-y-6">
            <section class="hero-card">
                <div><p class="eyebrow">Administrasi akademik</p><h1>Izin &amp; cuti guru</h1><p>{{ can_review ? 'Tinjau pengajuan guru. Pengajuan yang disetujui akan dicatat otomatis pada absensi.' : 'Ajukan izin, sakit, cuti, atau dinas luar dan pantau keputusan sekolah.' }}</p></div>
                <div class="flex flex-wrap gap-2"><Link class="secondary-button" href="/akademik/absensi-guru">Absensi guru</Link><button v-if="can_apply" class="primary-button" :disabled="form.processing" @click="showForm ? closeForm() : showForm = true">{{ showForm ? 'Tutup formulir' : 'Ajukan izin/cuti' }}</button></div>
            </section>

            <p v-if="message" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ message }}</p>

            <ul v-if="actionErrors.length" class="panel p-4 text-sm text-red-700" role="alert"><li v-for="error in actionErrors" :key="error">{{ error }}</li></ul>
            <section class="grade-summary" aria-label="Ringkasan seluruh pengajuan"><div v-for="tab in statusTabs" :key="tab.value"><span>{{ tab.label }}</span><strong>{{ summary[tab.value] }}</strong></div></section>

            <section v-if="showForm && can_apply" class="panel p-5">
                <div class="mb-4"><h2 class="text-base font-semibold text-slate-900">Pengajuan baru</h2><p class="mt-1 text-sm text-slate-500">Rentang maksimal {{ max_days }} hari kalender. Pengajuan tidak dapat bertumpuk dengan pengajuan yang masih aktif.</p></div>
                <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
                    <label class="grid gap-1 text-sm font-medium text-slate-700">Jenis
                        <select v-model="form.type" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm"><option v-for="(label, value) in types" :key="value" :value="value">{{ label }}</option></select>
                        <small v-if="form.errors.type" class="text-red-700">{{ form.errors.type }}</small>
                    </label>
                    <label class="grid gap-1 text-sm font-medium text-slate-700">Alasan
                        <input v-model="form.reason" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm" maxlength="500" required placeholder="Ringkasan alasan pengajuan" />
                        <small v-if="form.errors.reason" class="text-red-700">{{ form.errors.reason }}</small>
                    </label>
                    <label class="grid gap-1 text-sm font-medium text-slate-700">Tanggal mulai
                        <input v-model="form.start_date" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm" type="date" :min="minDate"  required @change="setStartDate" />
                        <small v-if="form.errors.start_date" class="text-red-700">{{ form.errors.start_date }}</small>
                    </label>
                    <label class="grid gap-1 text-sm font-medium text-slate-700">Tanggal selesai
                        <input v-model="form.end_date" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm" type="date" :min="form.start_date" :max="maxEnd" required />
                        <small v-if="form.errors.end_date" class="text-red-700">{{ form.errors.end_date }}</small>
                    </label>
                    <p class="text-sm text-slate-500 md:col-span-2">Durasi {{ duration }} hari kalender. Minggu dilewati pada pengisian absensi; catatan absensi yang sudah ada dipertahankan.</p><div class="flex justify-end gap-2 md:col-span-2"><button type="button" class="secondary-button" :disabled="form.processing" @click="closeForm">Batal</button><button class="primary-button" :disabled="form.processing">{{ form.processing ? 'Mengirim…' : 'Kirim pengajuan' }}</button></div>
                </form>
            </section>

            <form class="panel p-4 flex flex-wrap items-end gap-3" @submit.prevent="applyFilters">
                <label class="grid gap-1 text-sm min-w-0 flex-1">Cari pengajuan<input v-model="search" type="search" placeholder="Nama, NIP, atau alasan…" class="rounded-lg border px-3 py-2" /></label>
                <label class="grid gap-1 text-sm">Jenis pengajuan<select v-model="selectedType" class="rounded-lg border px-3 py-2"><option value="">Semua jenis</option><option v-for="(label, value) in types" :key="value" :value="value">{{ label }}</option></select></label>
                <label class="grid gap-1 text-sm">Bulan izin/cuti<input v-model="month" type="month" class="rounded-lg border px-3 py-2" /></label>
                <button class="secondary-button" type="submit">Terapkan filter</button><button class="secondary-button" type="button" @click="resetFilters">Reset filter</button>
            </form>
            <p class="text-sm text-slate-500">{{ leaves.total }} pengajuan sesuai filter. Ringkasan menampilkan seluruh pengajuan yang dapat Anda akses.</p>
            <nav class="flex flex-wrap gap-2" aria-label="Filter status pengajuan">
                <Link v-for="tab in statusTabs" :key="tab.value" class="rounded-full border px-4 py-2 text-sm font-semibold transition" :class="filter === tab.value ? 'border-blue-700 bg-blue-700 text-white' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'" :href="filterUrl(tab.value)">{{ tab.label }} ({{ summary[tab.value] }})</Link>
            </nav>

            <section class="panel overflow-hidden">
                <div v-if="!leaves.data.length" class="px-6 py-14 text-center"><strong class="text-base font-semibold text-slate-900">Belum ada pengajuan</strong><p class="mt-1 text-sm text-slate-500">Coba ubah filter atau ajukan izin/cuti melalui akun guru.</p></div>
                <div v-else class="divide-y divide-slate-100">
                    <article v-for="leave in leaves.data" :key="leave.id" class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(240px,360px)]">
                        <div class="min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-2"><h2 class="font-semibold text-slate-900">{{ leave.type_label }}</h2><span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClasses[leave.status]">{{ statusLabels[leave.status] ?? leave.status }}</span></div>
                            <p class="text-sm font-medium text-slate-700">{{ leave.date_range_label }} · {{ leave.duration_days }} hari kalender</p>
                            <p v-if="can_review" class="text-sm text-slate-500">{{ leave.teacher_name }} · NIP {{ leave.teacher_nip || '—' }}</p>
                            <p class="text-sm text-slate-600">{{ leave.reason }}</p>
                            <p v-if="leave.review_note" class="text-sm text-slate-500">Catatan keputusan: {{ leave.review_note }}</p>
                        </div>
                        <div v-if="leave.reviewer_name" class="text-xs text-slate-500">Diputuskan oleh {{ leave.reviewer_name }}<span v-if="leave.reviewed_at"> · {{ new Date(leave.reviewed_at).toLocaleString('id-ID') }}</span></div>
                        <div v-if="leave.can_review" class="flex flex-col justify-center gap-2">
                            <input v-model="reviewNotes[leave.id]" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm" maxlength="255" placeholder="Catatan keputusan (opsional)" :aria-label="`Catatan keputusan ${leave.teacher_name}`" />
                            <div class="flex flex-wrap justify-end gap-2"><button class="secondary-button" :disabled="busyId !== null" @click="review(leave, 'reject')">Tolak</button><button class="primary-button" :disabled="busyId !== null" @click="review(leave, 'approve')">Setujui</button></div>
                        </div>
                        <div v-else-if="leave.can_cancel" class="flex items-center justify-end"><button class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100" :disabled="busyId !== null" @click="cancel(leave)">Batalkan pengajuan</button></div>
                    </article>
                </div>
                <nav v-if="leaves.links.length > 3" class="flex flex-wrap justify-end gap-1 border-t border-slate-200 px-5 py-4" aria-label="Halaman pengajuan">
                    <template v-for="link in leaves.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" class="rounded-md border px-3 py-2 text-sm" :class="link.active ? 'border-blue-700 bg-blue-700 text-white' : 'border-slate-200 text-slate-600'" v-html="link.label" />
                        <span v-else class="rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-400" v-html="link.label" />
                    </template>
                </nav>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.leave-page { min-width: 0; }
.leave-page input, .leave-page select { width: 100%; min-width: 0; max-width: 100%; }
.leave-page article { overflow-wrap: anywhere; }
@media (max-width: 640px) {
    .leave-page .hero-card { align-items: stretch; flex-direction: column; }
    .leave-page form > label { width: 100%; flex-basis: 100%; min-width: 0; }
    .leave-page .grade-summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>
