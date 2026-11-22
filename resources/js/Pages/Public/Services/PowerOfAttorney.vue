<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { useForm } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed } from 'vue';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    legalizationCode: string;
    generatedDocument?: {
        document_id: number;
        document_number: string;
        verification_token: string;
        template_type: string;
    } | null;
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const form = useForm({
    template_type: 'power_of_attorney',
    grantor_name: '',
    grantor_nisn: '',
    grantor_nik: '',
    grantor_graduation_year: new Date().getFullYear(),
    grantor_phone: '',
    proxy_name: '',
    proxy_nik: '',
    proxy_relation: '',
    proxy_phone: '',
    document_ref_number: '',
    purpose: 'Pengurusan dan pengambilan berkas legalisir ijazah',
    legalization_code: props.legalizationCode || '',
    grantor_signature_data: 'data:text/plain;base64,VFREX1BFTUJFUkk=',
    proxy_signature_data: 'data:text/plain;base64,VFREX1BFTkVSSU1B',
});

const submit = () => {
    form.post('/layanan/surat-kuasa', { preserveScroll: true });
};
const publicText = usePublicContentText();
</script>

<template>
    <Head :title="`Surat Kuasa &amp; SPTJM — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-4xl px-4 py-10 space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ publicText("copy.vue_services_powerofattorney", "text_001", "Generator Resmi Surat Kuasa & SPTJM") }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ publicText("copy.vue_services_powerofattorney", "text_002", "Terbitkan PDF resmi versi sekolah yang terhubung langsung ke permohonan legalisir.") }}</p>
            </div>

            <div v-if="generatedDocument" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950 space-y-2">
                <p class="font-bold">{{ publicText("copy.vue_services_powerofattorney", "text_003", "Dokumen Resmi Diterbitkan!") }}</p>
                <p>{{ publicText("copy.vue_services_powerofattorney", "text_004", "Nomor:") }} <strong class="font-mono">{{ generatedDocument.document_number }}</strong> {{ publicText("copy.vue_services_powerofattorney", "text_005", "| Token Verifikasi:") }} <strong class="font-mono">{{ generatedDocument.verification_token }}</strong></p>
                <a
                    :href="`/layanan/legalisir/dokumen/${generatedDocument.document_id}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-block rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white"
                >
                    {{ publicText("copy.vue_services_powerofattorney", "text_006", "Unduh PDF Dokumen Resmi") }}
                </a>
            </div>

            <form @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <FormField id="poa-type" :label="publicText('copy.vue_services_powerofattorney', 'label_10001', 'Jenis Template Dokumen')" required>
                        <select id="poa-type" v-model="form.template_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="power_of_attorney">{{ publicText("copy.vue_services_powerofattorney", "text_007", "Surat Kuasa Pengambilan / Pengurusan (v1)") }}</option>
                            <option value="sptjm">{{ publicText("copy.vue_services_powerofattorney", "text_008", "SPTJM Keabsahan Dokumen (v1)") }}</option>
                        </select>
                    </FormField>
                    <FormField id="poa-leg-code" :label="publicText('copy.vue_services_powerofattorney', 'label_10002', 'Kode Register Legalisir (Opsional)')" :error="form.errors.legalization_code">
                        <input id="poa-leg-code" v-model="form.legalization_code" type="text" :placeholder="publicText('copy.vue_services_powerofattorney', 'placeholder_009', 'LEG-2026-...')" class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm uppercase" />
                    </FormField>
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <FormField id="poa-grantor-name" :label="publicText('copy.vue_services_powerofattorney', 'label_10003', 'Nama Pemberi Kuasa / Alumni')" :error="form.errors.grantor_name" required>
                        <input id="poa-grantor-name" v-model="form.grantor_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="poa-grantor-nik" :label="publicText('copy.vue_services_powerofattorney', 'label_10004', 'NIK Pemberi Kuasa')" :error="form.errors.grantor_nik" required>
                        <input id="poa-grantor-nik" v-model="form.grantor_nik" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="poa-grantor-phone" :label="publicText('copy.vue_services_powerofattorney', 'label_10005', 'No. HP Pemberi Kuasa')" :error="form.errors.grantor_phone" required>
                        <input id="poa-grantor-phone" v-model="form.grantor_phone" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                </div>

                <div v-if="form.template_type === 'power_of_attorney'" class="grid gap-3 md:grid-cols-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <FormField id="poa-proxy-name" :label="publicText('copy.vue_services_powerofattorney', 'label_10006', 'Nama Penerima Kuasa')" :error="form.errors.proxy_name" required>
                        <input id="poa-proxy-name" v-model="form.proxy_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="poa-proxy-nik" :label="publicText('copy.vue_services_powerofattorney', 'label_10007', 'NIK Penerima Kuasa')" :error="form.errors.proxy_nik" required>
                        <input id="poa-proxy-nik" v-model="form.proxy_nik" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="poa-proxy-rel" :label="publicText('copy.vue_services_powerofattorney', 'label_10008', 'Hubungan')" :error="form.errors.proxy_relation" required>
                        <input id="poa-proxy-rel" v-model="form.proxy_relation" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="poa-proxy-phone" :label="publicText('copy.vue_services_powerofattorney', 'label_10009', 'No. HP Kuasa')">
                        <input id="poa-proxy-phone" v-model="form.proxy_phone" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                </div>

                <FormField id="poa-purpose" :label="publicText('copy.vue_services_powerofattorney', 'label_10010', 'Keperluan / Keterangan')" :error="form.errors.purpose" required>
                    <input id="poa-purpose" v-model="form.purpose" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </FormField>

                <AppButton type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Membuat PDF...' : 'Terbitkan PDF Resmi' }}
                </AppButton>
            </form>
        </main>
    </div>
</template>
