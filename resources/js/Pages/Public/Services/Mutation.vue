<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import MutationProcedure from '@/components/MutationProcedure.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, Link, useForm } from '@inertiajs/vue3';
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

    <div class="mutasi-page" data-madrasah-service="mutation" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="mutasi-hero-banner">
            <div class="mutasi-container mutasi-hero-inner">
                <div class="mutasi-hero-pill">
                    <i class="fa-solid fa-right-left" aria-hidden="true"></i>
                    <span>Layanan Mutasi Siswa Online</span>
                </div>
                <h1>{{ school.name.toUpperCase() }}</h1>
                <h4>Tahun Pelajaran 2026/2027</h4>
                <p>
                    Informasi alur prosedur &amp; formulir permohonan mutasi masuk dan mutasi keluar siswa secara online.
                </p>
                <div class="mutasi-hero-badge-wrap">
                    <span class="mutasi-badge-status" :class="periodAvailable ? 'is-open' : 'is-closed'">
                        <i :class="periodAvailable ? 'fa-solid fa-circle-check' : 'fa-solid fa-ban'" aria-hidden="true"></i>
                        <span>{{ periodAvailable ? `Semester aktif: ${activeTermName}` : 'Pendaftaran Mutasi Masuk Ditutup' }}</span>
                    </span>
                </div>
            </div>
        </section>

        <div class="mutasi-container mutasi-tabs-wrap">
            <nav class="mutasi-tabs" aria-label="Navigasi layanan">
                <button type="button" class="btn-mutasi-tab" :class="{ active: activeTab === 'alur' }" @click="activeTab = 'alur'">
                    <i class="fa-solid fa-sitemap" aria-hidden="true"></i>
                    <span>Alur &amp; Persyaratan Mutasi</span>
                </button>
                <button type="button" class="btn-mutasi-tab" :class="{ active: activeTab === 'form' }" @click="activeTab = 'form'">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                    <span>Formulir Permohonan Mutasi</span>
                </button>
                <Link href="/lacak?category=mutasi" class="btn-mutasi-tab">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span>Cek Status Pengajuan</span>
                </Link>
            </nav>
        </div>

        <main class="mutasi-container mutasi-main">
            <div v-show="activeTab === 'alur'" id="alur-mutasi">
                <MutationProcedure :school-name="school.name" />
            </div>

            <div
                v-if="!periodAvailable"
                v-show="activeTab === 'form'"
                class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 mb-6"
            >
                Periode/semester aktif belum tersedia sehingga pengajuan mutasi sementara ditutup.
            </div>

            <div v-if="issuedReceipt" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950 space-y-1 mb-6">
                <p class="font-bold">Permohonan Mutasi Terkirim!</p>
                <p>Kode Mutasi: <strong class="font-mono">{{ issuedReceipt.mutation_code }}</strong> | Tiket Lacak: <strong class="font-mono">{{ issuedReceipt.ticket_code }}</strong> | PIN: <strong class="font-mono">{{ issuedReceipt.verification_secret }}</strong></p>
            </div>

            <form
                id="form-mutasi"
                v-show="activeTab === 'form' || !!issuedReceipt || !!revisingItem"
                v-if="periodAvailable"
                @submit.prevent="submit"
                class="card-custom mutasi-form-card space-y-5"
            >
                <div class="form-section-header">
                    <i class="fa-solid fa-sliders mr-2" aria-hidden="true"></i>1. Pilihan Jenis Mutasi
                </div>
                <fieldset class="service-choice-group">
                    <legend class="sr-only">Jenis Mutasi</legend>
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

                <div class="form-section-header">
                    <i class="fa-solid fa-user-graduate mr-2" aria-hidden="true"></i>2. Data Siswa &amp; Sekolah
                </div>
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

                <div class="form-section-header">
                    <i class="fa-solid fa-file-signature mr-2" aria-hidden="true"></i>3. Kontak Pemohon &amp; Berkas Persyaratan
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

            <div v-if="myMutations.length" class="card-custom mutasi-form-card space-y-4 mt-6">
                <div class="form-section-header">
                    <i class="fa-solid fa-clock-rotate-left mr-2" aria-hidden="true"></i>Riwayat Pengajuan Mutasi Anda
                </div>
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

            <form v-if="revisingItem" @submit.prevent="submitRevision" class="card-custom mutasi-form-card border border-amber-300 space-y-3 mt-6">
                <div class="form-section-header">
                    <i class="fa-solid fa-pen-clip mr-2" aria-hidden="true"></i>Perbaikan Permohonan {{ revisingItem.mutation_code }}
                </div>
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

<style scoped>
.mutasi-page {
    min-height: 100vh;
    background-color: #f8fafc;
    color: #1e293b;
}

.mutasi-container {
    width: 100%;
    max-width: 1140px;
    margin: 0 auto;
    padding-inline: 15px;
}

.mutasi-hero-banner {
    background: linear-gradient(135deg, #0f4c81 0%, #1b85df 100%);
    color: #ffffff;
    padding: 40px 0;
    margin-bottom: 30px;
    border-radius: 0 0 20px 20px;
    box-shadow: 0 10px 25px rgba(15, 76, 129, 0.14);
    text-align: center;
}

.mutasi-hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    padding: 4px 12px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    font-size: 12px;
    font-weight: 700;
    color: #fef08a;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.mutasi-hero-banner h1 {
    margin: 0 0 6px;
    font-weight: 800;
    font-size: 26px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: #ffffff;
    line-height: 1.25;
}

.mutasi-hero-banner h4 {
    margin: 0 0 10px;
    font-weight: 600;
    font-size: 16px;
    opacity: 0.95;
    color: #93c5fd;
    line-height: 1.3;
}

.mutasi-hero-banner p {
    font-size: 14.5px;
    opacity: 0.9;
    max-width: 680px;
    margin: 0 auto;
    line-height: 1.6;
    color: #ffffff;
}

.mutasi-hero-badge-wrap {
    margin-top: 14px;
}

.mutasi-badge-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 700;
    padding: 7px 14px;
    border-radius: 50px;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.mutasi-badge-status.is-open {
    background-color: #059669;
}

.mutasi-badge-status.is-closed {
    background-color: #dc3545;
}

.mutasi-tabs-wrap {
    margin-bottom: 24px;
    text-align: center;
}

.mutasi-tabs {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
}

.btn-mutasi-tab {
    color: #495057;
    background-color: #f8f9fa;
    border-radius: 8px;
    font-weight: 600;
    padding: 12px 24px;
    margin: 0 8px 10px 8px;
    border: 1px solid #ced4da;
    cursor: pointer;
    outline: none;
    transition: all 0.2s ease-in-out;
    font-size: 15px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
}

.btn-mutasi-tab:hover {
    background-color: #e9ecef;
    color: #007bff;
    text-decoration: none;
}

.btn-mutasi-tab.active {
    color: #ffffff;
    background-color: #007bff;
    border-color: #007bff;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.35);
}

.mutasi-main {
    padding-bottom: 48px;
}

.card-custom {
    background: #ffffff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.mutasi-form-card {
    padding: 28px;
}

.form-section-header {
    background: #eef5ff;
    padding: 10px 15px;
    border-left: 4px solid #007bff;
    border-radius: 4px;
    font-weight: 700;
    color: #0056b3;
    margin-bottom: 18px;
    font-size: 15px;
}

@media (max-width: 767.98px) {
    .mutasi-hero-banner {
        padding: 32px 16px;
        margin-bottom: 20px;
    }

    .mutasi-hero-banner h1 {
        font-size: 22px;
    }

    .btn-mutasi-tab {
        font-size: 13.5px;
        padding: 10px 16px;
        margin: 0 4px 8px 4px;
    }

    .mutasi-form-card {
        padding: 18px;
    }
}
</style>
