<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import MutationProcedure from '@/components/MutationProcedure.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface MutationItem {
    id: number;
    mutation_code: string;
    direction: string;
    student_name: string;
    origin_school?: string | null;
    destination_school?: string | null;
    reason: string;
    status: string;
    revision_note?: string | null;
    decision_note?: string | null;
    has_official_letter: boolean;
}

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    periodAvailable: boolean;
    activeTermName?: string | null;
    students: { student_id: number; name: string; nis: string; class_group_name?: string | null }[];
    classes: { id: number; name: string; grade_level: number }[];
    myMutations: MutationItem[];
    issuedReceipt?: { id: number; mutation_code: string; ticket_code?: string | null; verification_secret: string } | null;
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const activeTab = ref<'alur' | 'form'>(props.issuedReceipt || props.myMutations.length > 0 ? 'form' : 'alur');
const revisingItem = ref<MutationItem | null>(null);

const form = useForm({
    direction: props.students.length > 0 ? 'outgoing' : 'incoming',
    student_id: props.students[0]?.student_id ?? ('' as string | number),
    student_name: '',
    nis: '',
    nisn: '',
    gender: 'L',
    birth_date: '',
    target_class_group_id: '' as string | number,
    origin_school: '',
    destination_school: '',
    reason: '',
    applicant_name: '',
    applicant_phone: '',
    idempotency_key: `mts-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
    attachments: [] as File[],
});

const revisionForm = useForm({
    reason: '',
    school_name: '',
    attachments: [] as File[],
});

const pickFiles = (event: Event) => {
    const files = (event.target as HTMLInputElement).files;
    form.attachments = files ? Array.from(files) : [];
};

const submit = () => {
    form.post('/layanan/mutasi', { forceFormData: true, preserveScroll: true });
};

const startRevision = (item: MutationItem) => {
    revisingItem.value = item;
    revisionForm.reason = item.reason;
    revisionForm.school_name = item.origin_school || item.destination_school || '';
};

const submitRevision = () => {
    if (!revisingItem.value) return;
    revisionForm.post(`/layanan/mutasi/${revisingItem.value.id}/perbaiki`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            revisingItem.value = null;
        },
    });
};

const statusTone = (status: string): 'neutral' | 'info' | 'warning' | 'success' | 'danger' => {
    if (status === 'approved') return 'success';
    if (status === 'needs_revision') return 'warning';
    if (status === 'rejected' || status === 'cancelled') return 'danger';
    return 'info';
};
</script>

<template>
    <Head :title="`Mutasi Siswa — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" data-madrasah-service="mutation" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-4xl px-4 py-10 space-y-6">
            <div class="madrasah-service-hero flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="madrasah-service-label"><i class="fa-solid fa-right-left" aria-hidden="true"></i> LAYANAN MUTASI SISWA ONLINE</p>
                    <h1 class="text-2xl font-bold text-slate-900">{{ school.name }}</h1>
                    <p class="mt-1 text-sm font-semibold text-sky-200">Tahun Pelajaran 2026/2027</p>
                    <p class="mt-1 text-sm text-slate-600">
                        Informasi alur prosedur &amp; formulir permohonan mutasi masuk dan mutasi keluar siswa secara online.
                    </p>
                    <div class="mt-3">
                        <span class="inline-flex items-center rounded-full px-4 py-1 text-xs font-extrabold text-white" :class="periodAvailable ? 'bg-emerald-600' : 'bg-rose-600'">
                            {{ periodAvailable ? `Semester aktif: ${activeTermName}` : 'Pendaftaran Mutasi Masuk Ditutup' }}
                        </span>
                    </div>
                </div>

            </div>

                <nav class="parity-document-nav" aria-label="Navigasi layanan">
                    <button type="button" :class="{ 'is-active': activeTab === 'alur' }" @click="activeTab = 'alur'">Alur &amp; Persyaratan Mutasi</button>
                    <button type="button" :class="{ 'is-active': activeTab === 'form' }" @click="activeTab = 'form'">Formulir Permohonan Mutasi</button>
                    <a href="/lacak?category=mutasi" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white">Cek Status Pengajuan</a>
                </nav>

            <div v-show="activeTab === 'alur'" id="alur-mutasi"><MutationProcedure /></div>

            <div v-if="!periodAvailable" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                Periode/semester aktif belum tersedia sehingga pengajuan mutasi sementara ditutup.
            </div>

            <div v-if="issuedReceipt" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950 space-y-1">
                <p class="font-bold">Permohonan Mutasi Terkirim!</p>
                <p>Kode Mutasi: <strong class="font-mono">{{ issuedReceipt.mutation_code }}</strong> | Tiket Lacak: <strong class="font-mono">{{ issuedReceipt.ticket_code }}</strong> | PIN: <strong class="font-mono">{{ issuedReceipt.verification_secret }}</strong></p>
            </div>

            <form id="form-mutasi" v-show="activeTab === 'form' || !!issuedReceipt || !!revisingItem" v-if="periodAvailable" @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <fieldset class="service-choice-group">
                    <legend class="service-form-title">Jenis Mutasi</legend>
                    <div class="service-tiles">
                        <label class="service-choice">
                            <input v-model="form.direction" name="mutation-direction" type="radio" value="incoming" />
                            <span><strong>Mutasi Masuk</strong><small>Pindah dari sekolah asal</small></span>
                        </label>
                        <label class="service-choice">
                            <input v-model="form.direction" name="mutation-direction" type="radio" value="outgoing" />
                            <span><strong>Mutasi Keluar</strong><small>Pindah ke sekolah tujuan</small></span>
                        </label>
                    </div>
                    <p v-if="form.errors.direction" class="text-sm text-rose-700" role="alert">{{ form.errors.direction }}</p>
                </fieldset>

                <div v-if="form.direction === 'outgoing'" class="grid gap-3 md:grid-cols-2">
                    <FormField id="mts-student-id" label="Pilih Siswa Aktif" :error="form.errors.student_id" required>
                        <select id="mts-student-id" v-model="form.student_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option v-for="s in students" :key="s.student_id" :value="s.student_id">
                                {{ s.name }} ({{ s.nis }}) {{ s.class_group_name ? `— ${s.class_group_name}` : '' }}
                            </option>
                        </select>
                    </FormField>
                    <FormField id="mts-dest-school" label="Sekolah Tujuan" :error="form.errors.destination_school" required>
                        <input id="mts-dest-school" v-model="form.destination_school" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                </div>

                <div v-else class="grid gap-3 md:grid-cols-2">
                    <FormField id="mts-student-name" label="Nama Calon Siswa Pindahan" :error="form.errors.student_name" required>
                        <input id="mts-student-name" v-model="form.student_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="mts-origin-school" label="Sekolah Asal" :error="form.errors.origin_school" required>
                        <input id="mts-origin-school" v-model="form.origin_school" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="mts-nisn" label="NISN">
                        <input id="mts-nisn" v-model="form.nisn" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="mts-target-class" label="Kelas Tujuan (Opsional)">
                        <select id="mts-target-class" v-model="form.target_class_group_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Ditentukan TU saat persetujuan</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </FormField>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <FormField id="mts-applicant-name" label="Nama Pemohon / Wali">
                        <input id="mts-applicant-name" v-model="form.applicant_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="mts-applicant-phone" label="No. Telepon / WA">
                        <input id="mts-applicant-phone" v-model="form.applicant_phone" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                </div>

                <FormField id="mts-reason" label="Alasan &amp; Keterangan Mutasi" :error="form.errors.reason" required>
                    <textarea id="mts-reason" v-model="form.reason" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </FormField>

                <FormField id="mts-attachments" label="Lampiran Syarat (Surat Pindah / Rapor / Pernyataan Wali)">
                    <input id="mts-attachments" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="text-sm" @change="pickFiles" />
                </FormField>

                <AppButton type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Mengirim...' : 'Ajukan Mutasi Siswa' }}
                </AppButton>
            </form>

            <div v-if="myMutations.length" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-900">Riwayat Pengajuan Mutasi Anda</h2>
                <div v-for="m in myMutations" :key="m.id" class="rounded-xl border border-slate-200 p-4 text-sm space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-semibold">{{ m.mutation_code }} — {{ m.student_name }} ({{ m.direction === 'incoming' ? 'Masuk' : 'Keluar' }})</p>
                        <StatusBadge :status="m.status" :tone="statusTone(m.status)" size="sm" />
                    </div>
                    <p class="text-xs text-slate-600">{{ m.reason }}</p>
                    <p v-if="m.revision_note" class="text-xs text-amber-800">Catatan Perbaikan: {{ m.revision_note }}</p>
                    <p v-if="m.decision_note" class="text-xs text-slate-700">Catatan Keputusan: {{ m.decision_note }}</p>
                    <div class="flex flex-wrap gap-2">
                        <a
                            v-if="m.has_official_letter"
                            :href="`/layanan/mutasi/${m.id}/surat.pdf`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-md bg-emerald-700 px-3 py-1 text-xs font-semibold text-white"
                        >
                            Unduh Surat Mutasi (PDF)
                        </a>
                        <button
                            v-if="m.status === 'needs_revision'"
                            type="button"
                            class="rounded-md border border-amber-300 px-3 py-1 text-xs font-semibold text-amber-800"
                            @click="startRevision(m)"
                        >
                            Perbaiki Berkas
                        </button>
                    </div>
                </div>
            </div>

            <form v-if="revisingItem" @submit.prevent="submitRevision" class="rounded-2xl border border-amber-300 bg-white p-6 space-y-3">
                <h3 class="text-base font-semibold">Perbaikan Permohonan {{ revisingItem.mutation_code }}</h3>
                <FormField id="mts-rev-school" label="Nama Sekolah Asal / Tujuan">
                    <input id="mts-rev-school" v-model="revisionForm.school_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </FormField>
                <FormField id="mts-rev-reason" label="Alasan Mutasi Terbaru" required>
                    <textarea id="mts-rev-reason" v-model="revisionForm.reason" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </FormField>
                <div class="flex gap-2">
                    <AppButton type="submit" :disabled="revisionForm.processing">Kirim Perbaikan</AppButton>
                    <AppButton type="button" variant="secondary" @click="revisingItem = null">Batal</AppButton>
                </div>
            </form>
        </main>
    </div>
</template>
