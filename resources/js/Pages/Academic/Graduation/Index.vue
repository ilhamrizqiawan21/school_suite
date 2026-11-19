<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

interface GraduationRecordItem {
    id: number;
    student_id: number;
    student_name: string;
    student_nis: string;
    student_nisn?: string | null;
    decision: string;
    skl_number: string;
    average_score?: number | null;
    skl_verification_token?: string | null;
    reissue_count: number;
    original_collected_at?: string | null;
    original_collected_by?: string | null;
}

interface GraduationBatchItem {
    id: number;
    academic_year_id: number;
    title: string;
    skl_prefix: string;
    release_at?: string | null;
    is_published: boolean;
    is_released_now: boolean;
    signatory_name?: string | null;
    signatory_title?: string | null;
    records: GraduationRecordItem[];
}

const props = defineProps<{
    years: { id: number; name: string; is_active: boolean }[];
    batches: GraduationBatchItem[];
    students: { student_id: number; name: string; nis: string }[];
    issuedPin?: { student_id: number; pin: string } | null;
}>();

const batchForm = useForm({
    academic_year_id: props.years[0]?.id ?? ('' as string | number),
    title: 'Penetapan Kelulusan Tahun Ajaran',
    skl_prefix: 'SKL',
    release_at: '',
    signatory_name: '',
    signatory_title: 'Kepala Madrasah / Sekolah',
});

const studentForm = useForm({
    batch_id: props.batches[0]?.id ?? ('' as string | number),
    student_id: props.students[0]?.student_id ?? ('' as string | number),
    decision: 'graduated',
    average_score: 85,
});

const publishForm = useForm({
    signatory_name: '',
    signatory_title: 'Kepala Madrasah / Sekolah',
    release_at: '',
});

const reissueForm = useForm({ reason: '' });
const collectedForm = useForm({ collected_by: '', relation: 'Siswa Bersangkutan' });

const createBatch = () => {
    batchForm.post('/akademik/kelulusan/batch', { preserveScroll: true });
};

const addStudent = () => {
    if (!studentForm.batch_id) return;
    studentForm.post(`/akademik/kelulusan/batch/${studentForm.batch_id}/siswa`, { preserveScroll: true });
};

const publish = (batchId: number) => {
    publishForm.post(`/akademik/kelulusan/batch/${batchId}/terbitkan`, { preserveScroll: true });
};

const reissue = (recordId: number) => {
    const reason = window.prompt('Masukkan alasan penerbitan ulang SKL:');
    if (!reason) return;
    reissueForm.reason = reason;
    reissueForm.post(`/akademik/kelulusan/skl/${recordId}/terbitkan-ulang`, { preserveScroll: true });
};

const markCollected = (recordId: number) => {
    const who = window.prompt('Nama pengambil dokumen kelulusan asli:');
    if (!who) return;
    collectedForm.collected_by = who;
    collectedForm.post(`/akademik/kelulusan/skl/${recordId}/ambil-asli`, { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Kelulusan &amp; SKL">
        <Head title="Kelulusan &amp; SKL" />

        <div class="space-y-6">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Pengelolaan Kelulusan &amp; SKL</h1>
                <p class="text-sm text-slate-600">Siapkan daftar lulusan, atur jadwal rilis pengumuman, sahkan pejabat penandatangan, terbitkan ulang SKL, dan catat pengambilan asli.</p>
            </div>

            <div v-if="issuedPin" class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-950">
                PIN Akses Kelulusan untuk Siswa #{{ issuedPin.student_id }}: <strong class="font-mono">{{ issuedPin.pin }}</strong>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <form @submit.prevent="createBatch" class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
                    <h2 class="text-base font-semibold text-slate-900">Buat Batch Kelulusan</h2>
                    <FormField id="grad-year" label="Tahun Ajaran" required>
                        <select id="grad-year" v-model="batchForm.academic_year_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                        </select>
                    </FormField>
                    <FormField id="grad-title" label="Judul Batch" required>
                        <input id="grad-title" v-model="batchForm.title" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="grad-release-at" label="Jadwal Rilis Publik (Opsional)">
                        <input id="grad-release-at" v-model="batchForm.release_at" type="datetime-local" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <AppButton type="submit" :disabled="batchForm.processing">Simpan Batch</AppButton>
                </form>

                <form v-if="batches.length" @submit.prevent="addStudent" class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
                    <h2 class="text-base font-semibold text-slate-900">Tambah / Perbarui Siswa di Batch</h2>
                    <FormField id="grad-rec-batch" label="Pilih Batch" required>
                        <select id="grad-rec-batch" v-model="studentForm.batch_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option v-for="b in batches" :key="b.id" :value="b.id">{{ b.title }}</option>
                        </select>
                    </FormField>
                    <FormField id="grad-rec-student" label="Siswa" required>
                        <select id="grad-rec-student" v-model="studentForm.student_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option v-for="s in students" :key="s.student_id" :value="s.student_id">{{ s.name }} ({{ s.nis }})</option>
                        </select>
                    </FormField>
                    <div class="grid gap-3 grid-cols-2">
                        <FormField id="grad-rec-decision" label="Status">
                            <select id="grad-rec-decision" v-model="studentForm.decision" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="graduated">Lulus</option>
                                <option value="not_graduated">Tidak Lulus</option>
                            </select>
                        </FormField>
                        <FormField id="grad-rec-score" label="Rata-Rata Nilai">
                            <input id="grad-rec-score" v-model.number="studentForm.average_score" type="number" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                    </div>
                    <AppButton type="submit" :disabled="studentForm.processing">Simpan Data Siswa</AppButton>
                </form>
            </div>

            <StateFeedback v-if="batches.length === 0" state="empty" title="Belum ada batch kelulusan" description="Buat batch kelulusan terlebih dahulu untuk menyiapkan SKL." />

            <div v-for="batch in batches" :key="batch.id" class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">{{ batch.title }}</h3>
                        <p class="text-xs text-slate-600">Rilis: {{ batch.release_at || 'Langsung saat disahkan' }} · Pengesah: {{ batch.signatory_name || '-' }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <StatusBadge :status="batch.is_released_now ? 'Rilis Publik' : (batch.is_published ? 'Terjadwal' : 'Draf')" :tone="batch.is_released_now ? 'success' : 'warning'" size="sm" />
                        <form @submit.prevent="publish(batch.id)" class="flex items-center gap-2">
                            <input v-model="publishForm.signatory_name" type="text" placeholder="Nama Penandatangan" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs" required />
                            <AppButton type="submit" :disabled="publishForm.processing">Sahkan &amp; Terbitkan</AppButton>
                        </form>
                    </div>
                </div>

                <div v-if="batch.records.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Siswa</th>
                                <th class="px-3 py-2">No. SKL &amp; Token</th>
                                <th class="px-3 py-2">Nilai</th>
                                <th class="px-3 py-2">Pengambilan Asli</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="rec in batch.records" :key="rec.id">
                                <td class="px-3 py-2.5">
                                    <p class="font-medium">{{ rec.student_name }}</p>
                                    <p class="text-xs text-slate-500">{{ rec.student_nis }} / {{ rec.student_nisn || '-' }}</p>
                                </td>
                                <td class="px-3 py-2.5 font-mono text-xs">
                                    <p>{{ rec.skl_number }}</p>
                                    <p v-if="rec.skl_verification_token" class="text-slate-500">{{ rec.skl_verification_token }}</p>
                                </td>
                                <td class="px-3 py-2.5">{{ rec.average_score ?? '-' }}</td>
                                <td class="px-3 py-2.5 text-xs">
                                    <span v-if="rec.original_collected_at">{{ rec.original_collected_by }} ({{ rec.original_collected_at }})</span>
                                    <span v-else class="text-slate-400">Belum diambil</span>
                                </td>
                                <td class="px-3 py-2.5 text-right space-x-2">
                                    <a v-if="rec.skl_verification_token" :href="`/layanan/kelulusan/${rec.id}/skl.pdf`" target="_blank" rel="noopener noreferrer" class="text-xs text-indigo-700 underline">SKL PDF</a>
                                    <button type="button" class="text-xs font-semibold text-amber-700" @click="reissue(rec.id)">Terbitkan Ulang</button>
                                    <button v-if="!rec.original_collected_at" type="button" class="text-xs font-semibold text-emerald-700" @click="markCollected(rec.id)">Catat Ambil</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
