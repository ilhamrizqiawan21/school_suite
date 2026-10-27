<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface AttendanceRow {
    teacher_id: number;
    name: string;
    nip: string | null;
    record_id: number | null;
    status: 'present' | 'permitted' | 'sick' | 'absent';
    notes: string;
    substitute_url: string | null;
}

const props = defineProps<{
    date: string;
    rows: AttendanceRow[];
    can_manage_all: boolean;
    summary: { teachers: number; present: number; permitted: number; sick: number; absent: number };
}>();

const page = usePage();
const message = computed(() => (page.props.flash as { message?: string }).message);
const form = useForm({
    date: props.date,
    records: props.rows.map((row) => ({ teacher_id: row.teacher_id, status: row.status, notes: row.notes })),
});

watch(() => [props.date, props.rows] as const, () => {
    form.date = props.date;
    form.records = props.rows.map((row) => ({ teacher_id: row.teacher_id, status: row.status, notes: row.notes }));
});

function showDate() {
    router.get('/akademik/absensi-guru', { date: form.date }, { preserveState: true, preserveScroll: true });
}

function save() {
    form.post('/akademik/absensi-guru', { preserveScroll: true });
}

const statusLabel = (status: string) => ({ present: 'Hadir', permitted: 'Izin', sick: 'Sakit', absent: 'Alpha' }[status] ?? status);
</script>

<template>
    <AppLayout title="Absensi Guru">
        <Head title="Absensi Guru" />
        <main class="view space-y-6">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Administrasi akademik</p>
                    <h1>Absensi guru</h1>
                    <p>Catat kehadiran staf pengajar dan atur guru pengganti untuk sesi yang terdampak.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link class="secondary-button" href="/akademik/absensi-guru/rekap">Rekap bulanan</Link>
                    <Link class="secondary-button" href="/akademik/izin-guru">Izin &amp; cuti</Link>
                </div>
            </section>

            <p v-if="message" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ message }}</p>

            <section class="grade-summary">
                <div><span>Total guru</span><strong>{{ summary.teachers }}</strong></div>
                <div><span>Hadir</span><strong>{{ summary.present }}</strong></div>
                <div><span>Izin / sakit</span><strong>{{ summary.permitted + summary.sick }}</strong></div>
                <div><span>Alpha</span><strong>{{ summary.absent }}</strong></div>
            </section>

            <section class="panel p-5">
                <form class="flex flex-wrap items-end gap-3" @submit.prevent="showDate">
                    <label class="grid gap-1 text-sm font-medium text-slate-700">Tanggal
                        <input v-model="form.date" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm" type="date" :max="new Date().toISOString().slice(0, 10)" required />
                    </label>
                    <button class="secondary-button" type="submit">Tampilkan</button>
                    <span v-if="can_manage_all" class="text-sm text-slate-500">Admin dan operator dapat mengelola absensi semua guru di sekolah.</span>
                    <span v-else class="text-sm text-slate-500">Guru hanya melihat dan mengisi absensinya sendiri.</span>
                </form>
                <small v-if="form.errors.date" class="mt-2 block text-red-700">{{ form.errors.date }}</small>
            </section>

            <section class="panel overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div><h2 class="text-base font-semibold text-slate-900">Daftar kehadiran</h2><p class="mt-1 text-sm text-slate-500">Status awal untuk catatan baru adalah hadir.</p></div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', { dateStyle: 'long' }) }}</span>
                </div>
                <div v-if="!rows.length" class="px-6 py-14 text-center text-sm text-slate-500">Belum ada data guru aktif di sekolah ini.</div>
                <form v-else @submit.prevent="save">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Guru</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Keterangan</th><th class="px-5 py-3 text-right">Pengganti</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(row, index) in rows" :key="row.teacher_id" class="align-middle">
                                    <td class="px-5 py-4"><strong class="block font-semibold text-slate-900">{{ row.name }}</strong><small class="text-slate-500">NIP {{ row.nip || '—' }}</small></td>
                                    <td class="px-5 py-4"><select v-model="form.records[index].status" class="min-h-10 min-w-32 rounded-lg border border-slate-300 bg-white px-3 text-sm" :aria-label="`Status ${row.name}`"><option value="present">Hadir</option><option value="permitted">Izin</option><option value="sick">Sakit</option><option value="absent">Alpha</option></select></td>
                                    <td class="px-5 py-4"><input v-model="form.records[index].notes" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm" maxlength="255" placeholder="Keterangan opsional" /></td>
                                    <td class="px-5 py-4 text-right"><Link v-if="row.record_id && row.status !== 'present'" class="text-sm font-semibold text-blue-700 hover:underline" :href="row.substitute_url!">Atur pengganti</Link><span v-else class="text-slate-400">—</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-end border-t border-slate-200 px-5 py-4"><button class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : 'Simpan absensi' }}</button></div>
                </form>
            </section>
        </main>
    </AppLayout>
</template>
