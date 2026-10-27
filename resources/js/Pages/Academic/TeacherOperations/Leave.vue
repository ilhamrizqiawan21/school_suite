<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface LeaveRow {
    id: number; teacher_id: number; teacher_name: string; teacher_nip: string | null; type: string; type_label: string;
    start_date: string; end_date: string; date_range_label: string; reason: string; status: string;
    review_note: string | null; reviewer_name: string | null; can_cancel: boolean; can_review: boolean;
}
const props = defineProps<{
    leaves: { data: LeaveRow[]; links: { url: string | null; label: string; active: boolean }[] };
    filter: string; can_apply: boolean; can_review: boolean; types: Record<string, string>; max_days: number;
}>();

const page = usePage();
const message = computed(() => (page.props.flash as { message?: string }).message);
const showForm = ref(false);
const form = useForm({ type: 'permission', start_date: new Date().toISOString().slice(0, 10), end_date: new Date().toISOString().slice(0, 10), reason: '' });
const reviewNotes = ref<Record<number, string>>({});
const statusLabels: Record<string, string> = { pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' };
const statusClasses: Record<string, string> = { pending: 'bg-amber-50 text-amber-800', approved: 'bg-emerald-50 text-emerald-800', rejected: 'bg-rose-50 text-rose-800' };
const minDate = computed(() => { const d = new Date(); d.setDate(d.getDate() - 7); return d.toISOString().slice(0, 10); });
const maxEnd = computed(() => { const d = new Date(`${form.start_date}T00:00:00`); d.setDate(d.getDate() + props.max_days - 1); return d.toISOString().slice(0, 10); });

function submit() {
    form.post('/akademik/izin-guru', { preserveScroll: true, onSuccess: () => { form.reset(); form.type = 'permission'; form.start_date = new Date().toISOString().slice(0, 10); form.end_date = form.start_date; showForm.value = false; } });
}
function cancel(leave: LeaveRow) {
    if (window.confirm('Batalkan pengajuan izin/cuti ini?')) router.delete(`/akademik/izin-guru/${leave.id}`, { preserveScroll: true });
}
function review(leave: LeaveRow, decision: 'approve' | 'reject') {
    const prompt = decision === 'approve' ? 'Setujui pengajuan ini?' : 'Tolak pengajuan ini?';
    if (!window.confirm(prompt)) return;
    router.patch(`/akademik/izin-guru/${leave.id}/keputusan`, { decision, note: reviewNotes.value[leave.id] ?? '' }, { preserveScroll: true });
}
function setStartDate() {
    if (form.end_date < form.start_date) form.end_date = form.start_date;
}
</script>

<template>
    <AppLayout title="Izin & Cuti Guru">
        <Head title="Izin & Cuti Guru" />
        <main class="view space-y-6">
            <section class="hero-card">
                <div><p class="eyebrow">Administrasi akademik</p><h1>Izin &amp; cuti guru</h1><p>{{ can_review ? 'Tinjau pengajuan guru. Pengajuan yang disetujui akan dicatat otomatis pada absensi.' : 'Ajukan izin, sakit, cuti, atau dinas luar dan pantau keputusan sekolah.' }}</p></div>
                <div class="flex flex-wrap gap-2"><Link class="secondary-button" href="/akademik/absensi-guru">Absensi guru</Link><button v-if="can_apply" class="primary-button" @click="showForm = !showForm">{{ showForm ? 'Tutup formulir' : 'Ajukan izin/cuti' }}</button></div>
            </section>

            <p v-if="message" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ message }}</p>

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
                        <input v-model="form.start_date" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm" type="date" :min="minDate" :max="new Date().toISOString().slice(0, 10)" required @change="setStartDate" />
                        <small v-if="form.errors.start_date" class="text-red-700">{{ form.errors.start_date }}</small>
                    </label>
                    <label class="grid gap-1 text-sm font-medium text-slate-700">Tanggal selesai
                        <input v-model="form.end_date" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm" type="date" :min="form.start_date" :max="maxEnd" required />
                        <small v-if="form.errors.end_date" class="text-red-700">{{ form.errors.end_date }}</small>
                    </label>
                    <div class="flex justify-end gap-2 md:col-span-2"><button type="button" class="secondary-button" @click="showForm = false">Batal</button><button class="primary-button" :disabled="form.processing">{{ form.processing ? 'Mengirim…' : 'Kirim pengajuan' }}</button></div>
                </form>
            </section>

            <nav class="flex flex-wrap gap-2" aria-label="Filter status pengajuan">
                <Link v-for="tab in [{ value: 'all', label: 'Semua' }, { value: 'pending', label: 'Menunggu' }, { value: 'approved', label: 'Disetujui' }, { value: 'rejected', label: 'Ditolak' }]" :key="tab.value" class="rounded-full border px-4 py-2 text-sm font-semibold transition" :class="filter === tab.value ? 'border-blue-700 bg-blue-700 text-white' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'" :href="`/akademik/izin-guru?status=${tab.value}`">{{ tab.label }}</Link>
            </nav>

            <section class="panel overflow-hidden">
                <div v-if="!leaves.data.length" class="px-6 py-14 text-center"><strong class="text-base font-semibold text-slate-900">Belum ada pengajuan</strong><p class="mt-1 text-sm text-slate-500">Pengajuan izin dan cuti akan tercatat di sini.</p></div>
                <div v-else class="divide-y divide-slate-100">
                    <article v-for="leave in leaves.data" :key="leave.id" class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(240px,360px)]">
                        <div class="min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-2"><h2 class="font-semibold text-slate-900">{{ leave.type_label }}</h2><span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClasses[leave.status]">{{ statusLabels[leave.status] ?? leave.status }}</span></div>
                            <p class="text-sm font-medium text-slate-700">{{ leave.date_range_label }}</p>
                            <p v-if="can_review" class="text-sm text-slate-500">{{ leave.teacher_name }} · NIP {{ leave.teacher_nip || '—' }}</p>
                            <p class="text-sm text-slate-600">{{ leave.reason }}</p>
                            <p v-if="leave.review_note" class="text-sm text-slate-500">Catatan keputusan: {{ leave.review_note }}<span v-if="leave.reviewer_name"> · {{ leave.reviewer_name }}</span></p>
                        </div>
                        <div v-if="leave.can_review" class="flex flex-col justify-center gap-2">
                            <input v-model="reviewNotes[leave.id]" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm" maxlength="255" placeholder="Catatan keputusan (opsional)" :aria-label="`Catatan keputusan ${leave.teacher_name}`" />
                            <div class="flex flex-wrap justify-end gap-2"><button class="secondary-button" @click="review(leave, 'reject')">Tolak</button><button class="primary-button" @click="review(leave, 'approve')">Setujui</button></div>
                        </div>
                        <div v-else-if="leave.can_cancel" class="flex items-center justify-end"><button class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100" @click="cancel(leave)">Batalkan pengajuan</button></div>
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
