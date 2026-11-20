<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    mode: string;
    initialCode: string;
    documentResult?: {
        found: boolean;
        status: string;
        message: string;
        document?: {
            document_kind_label: string;
            document_number: string;
            holder_name: string;
            issued_at?: string | null;
            revoked_at?: string | null;
            revocation_reason?: string | null;
            signatory_name?: string | null;
            signatory_title?: string | null;
            sha256_prefix?: string | null;
        };
    } | null;
    studentResult?: {
        found: boolean;
        status_code?: string;
        status_label?: string;
        student_name?: string;
        class_or_level?: string;
        academic_year?: string;
        message: string;
    } | null;
}>();

const activeMode = ref<'document' | 'student'>(props.mode === 'student' ? 'student' : 'document');
const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const docForm = useForm({
    mode: 'document',
    code: props.initialCode || '',
});

const studentForm = useForm({
    mode: 'student',
    nisn: '',
    name: '',
});

const submitDoc = () => {
    docForm.post('/verifikasi', { preserveScroll: true });
};

const submitStudent = () => {
    studentForm.post('/verifikasi', { preserveScroll: true });
};

const docTone = (status: string): 'success' | 'danger' | 'warning' | 'neutral' => {
    if (status === 'valid') return 'success';
    if (status === 'revoked') return 'danger';
    if (status === 'expired') return 'warning';
    return 'neutral';
};
</script>

<template>
    <Head :title="`Verifikasi Siswa &amp; Dokumen — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-4xl px-4 py-10 space-y-6">
            <header class="madrasah-profile-hero">
                <p class="eyebrow">Verifikasi Resmi Madrasah</p>
                <h1 class="text-2xl font-bold text-slate-900">Verifikasi Siswa &amp; Keabsahan Dokumen</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Cek keabsahan kode/QR SKL, Ijazah Legalisir, Surat Kuasa, SPTJM, E-KTA Alumni, serta status keaktifan peserta didik tanpa menampilkan data pribadi sensitif.
                </p>
            </header>

            <div class="flex gap-2">
                <AppButton type="button" :variant="activeMode === 'document' ? 'primary' : 'secondary'" @click="activeMode = 'document'">
                    Verifikasi Dokumen / QR
                </AppButton>
                <AppButton type="button" :variant="activeMode === 'student' ? 'primary' : 'secondary'" @click="activeMode = 'student'">
                    Cek Keaktifan Siswa / Alumni
                </AppButton>
            </div>

            <div v-if="activeMode === 'document'" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-5">
                <form @submit.prevent="submitDoc" class="space-y-4">
                    <FormField id="verify-code" label="Kode Verifikasi / Nomor Dokumen" :error="docForm.errors.code" required>
                        <input
                            id="verify-code"
                            v-model="docForm.code"
                            type="text"
                            placeholder="Tempel kode verifikasi atau nomor SKL/Legalisir"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm"
                        />
                    </FormField>
                    <AppButton type="submit" :disabled="docForm.processing">
                        {{ docForm.processing ? 'Memeriksa...' : 'Verifikasi Dokumen' }}
                    </AppButton>
                </form>

                <div
                    v-if="documentResult"
                    class="rounded-xl border p-4 space-y-2 text-sm"
                    :class="
                        documentResult.status === 'valid'
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-950'
                            : 'border-rose-200 bg-rose-50 text-rose-950'
                    "
                >
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-semibold">{{ documentResult.message }}</p>
                        <StatusBadge :status="documentResult.status" :tone="docTone(documentResult.status)" size="sm" />
                    </div>
                    <div v-if="documentResult.document" class="grid gap-1 text-xs sm:grid-cols-2">
                        <p>Jenis: <strong>{{ documentResult.document.document_kind_label }}</strong></p>
                        <p>Nomor: <strong class="font-mono">{{ documentResult.document.document_number }}</strong></p>
                        <p>Pemegang: <strong>{{ documentResult.document.holder_name }}</strong></p>
                        <p v-if="documentResult.document.issued_at">Terbit: {{ documentResult.document.issued_at }}</p>
                        <p v-if="documentResult.document.signatory_name">Pengesah: {{ documentResult.document.signatory_name }}</p>
                        <p v-if="documentResult.document.revocation_reason">Alasan Cabut: {{ documentResult.document.revocation_reason }}</p>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-2xl border border-slate-200 bg-white p-6 space-y-5">
                <form @submit.prevent="submitStudent" class="grid gap-3 md:grid-cols-2">
                    <FormField id="verify-nisn" label="NISN Siswa" :error="studentForm.errors.nisn" required>
                        <input id="verify-nisn" v-model="studentForm.nisn" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="verify-name" label="Nama Siswa (Minimal 3 Huruf)" :error="studentForm.errors.name" required>
                        <input id="verify-name" v-model="studentForm.name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <div class="md:col-span-2">
                        <AppButton type="submit" :disabled="studentForm.processing">
                            {{ studentForm.processing ? 'Memeriksa...' : 'Cek Status Siswa' }}
                        </AppButton>
                    </div>
                </form>

                <div
                    v-if="studentResult"
                    class="rounded-xl border p-4 text-sm space-y-1"
                    :class="studentResult.found ? 'border-emerald-200 bg-emerald-50 text-emerald-950' : 'border-rose-200 bg-rose-50 text-rose-950'"
                >
                    <p class="font-semibold">{{ studentResult.message }}</p>
                    <div v-if="studentResult.found" class="text-xs space-y-0.5">
                        <p>Nama: <strong>{{ studentResult.student_name }}</strong></p>
                        <p>Status: <strong>{{ studentResult.status_label }}</strong></p>
                        <p>Kelas / Tingkat: {{ studentResult.class_or_level }} — {{ studentResult.academic_year }}</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
