<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    result?: {
        found: boolean;
        released: boolean;
        release_at?: string | null;
        message: string;
        record?: {
            id: number;
            batch_title: string;
            student_name: string;
            nis: string;
            nisn?: string | null;
            decision: string;
            skl_number: string;
            average_score?: number | null;
            has_skl_pdf: boolean;
            original_collected_at?: string | null;
        };
    } | null;
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const form = useForm({
    nisn: '',
    pin: '',
});

const submit = () => {
    form.post('/layanan/kelulusan', { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Kelulusan &amp; SKL — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-3xl px-4 py-10 space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Pengumuman Kelulusan &amp; Unduh SKL</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Hasil kelulusan hanya dibuka sesuai jadwal rilis resmi sekolah bagi pemilik NISN dan PIN akses yang sah.
                </p>
            </div>

            <form @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <FormField id="grad-nisn" label="NISN" :error="form.errors.nisn" required>
                        <input id="grad-nisn" v-model="form.nisn" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="grad-pin" label="PIN Kelulusan" :error="form.errors.pin">
                        <input id="grad-pin" v-model="form.pin" type="password" class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm" />
                    </FormField>
                </div>
                <AppButton type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Memeriksa...' : 'Cek Hasil Kelulusan' }}
                </AppButton>
            </form>

            <div v-if="result" class="rounded-2xl border p-6 text-sm space-y-3" :class="result.found && result.released ? 'border-emerald-200 bg-emerald-50 text-emerald-950' : 'border-amber-200 bg-amber-50 text-amber-950'">
                <p class="font-bold">{{ result.message }}</p>
                <div v-if="result.released && result.record" class="space-y-1 text-xs sm:text-sm">
                    <p>Program: <strong>{{ result.record.batch_title }}</strong></p>
                    <p>Nama: <strong>{{ result.record.student_name }}</strong> (NIS {{ result.record.nis }})</p>
                    <p>Keputusan: <strong class="uppercase">{{ result.record.decision === 'graduated' ? 'LULUS' : 'TIDAK LULUS' }}</strong></p>
                    <p>Nomor SKL: <strong class="font-mono">{{ result.record.skl_number }}</strong></p>
                    <div v-if="result.record.has_skl_pdf" class="pt-2">
                        <a
                            :href="`/layanan/kelulusan/${result.record.id}/skl.pdf`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-block rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white"
                        >
                            Unduh Dokumen SKL (PDF)
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
