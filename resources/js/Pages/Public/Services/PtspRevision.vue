<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import { Link, useForm } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed, ref } from 'vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    ptspRequest: {
        ticket_code: string;
        status: string;
        purpose: string;
        notes?: string | null;
        revision_note?: string | null;
        service_snapshot: {
            name?: string;
            required_documents?: { key: string; label: string; required: boolean }[];
        };
    };
    verificationSecret?: string;
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const fileInputs = ref<Record<string, File | null>>({});

const form = useForm({
    purpose: props.ptspRequest.purpose,
    notes: props.ptspRequest.notes ?? '',
    revision_reply: '',
    verification_secret: props.verificationSecret ?? '',
});

const onFileChange = (key: string, event: Event) => {
    const input = event.target as HTMLInputElement;
    fileInputs.value[key] = input.files?.[0] ?? null;
};

const submitRevision = () => {
    const attachments: Record<string, File> = {};
    for (const [k, file] of Object.entries(fileInputs.value)) {
        if (file) attachments[k] = file;
    }

    form.transform((data) => ({ ...data, attachments }))
        .post(`/layanan/ptsp/${props.ptspRequest.ticket_code}/perbaiki`, {
            forceFormData: true,
        });
};
const publicText = usePublicContentText();
</script>

<template>
    <Head :title="`Perbaiki Permohonan ${ptspRequest.ticket_code} — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="portal-container">
            <section class="portal-card">
                <header class="rev-head">
                    <div>
                        <p class="eyebrow">{{ publicText("copy.vue_services_ptsprevision", "text_001", "Perbaikan Permohonan PTSP") }}</p>
                        <h1>{{ ptspRequest.ticket_code }}</h1>
                        <p class="muted">{{ ptspRequest.service_snapshot.name }}</p>
                    </div>
                    <StatusBadge :status="ptspRequest.status" />
                </header>

                <div v-if="ptspRequest.revision_note" class="revision-box">
                    <strong>{{ publicText("copy.vue_services_ptsprevision", "text_002", "Catatan dari Petugas TU:") }}</strong>
                    <p>{{ ptspRequest.revision_note }}</p>
                </div>

                <form class="form-stack" @submit.prevent="submitRevision">
                    <FormField id="rev-purpose" :label="publicText('copy.vue_services_ptsprevision', 'label_10001', 'Keperluan Permohonan')" required :error="form.errors.purpose" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.purpose" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="rev-notes" :label="publicText('copy.vue_services_ptsprevision', 'label_10002', 'Catatan Permohonan')" :error="form.errors.notes" v-slot="{ inputId, describedBy, invalid }">
                        <textarea :id="inputId" v-model="form.notes" rows="3" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="rev-reply" :label="publicText('copy.vue_services_ptsprevision', 'label_10003', 'Keterangan Perbaikan untuk Petugas')" :error="form.errors.revision_reply" v-slot="{ inputId, describedBy, invalid }">
                        <textarea :id="inputId" v-model="form.revision_reply" rows="2" :placeholder="publicText('copy.vue_services_ptsprevision', 'placeholder_005', 'Jelaskan berkas atau data yang telah diperbaiki...')" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <div v-if="ptspRequest.service_snapshot.required_documents?.length" class="docs-grid">
                        <FormField
                            v-for="rule in ptspRequest.service_snapshot.required_documents"
                            :key="rule.key"
                            :id="`rev-doc-${rule.key}`"
                            :label="`Unggah Ulang / Tambah: ${rule.label}`"
                            v-slot="{ inputId }"
                        >
                            <input :id="inputId" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" @change="onFileChange(rule.key, $event)" />
                        </FormField>
                    </div>

                    <div class="action-row">
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="form.processing">
                            {{ publicText("copy.vue_services_ptsprevision", "text_003", "Kirim Ulang Perbaikan") }}
                        </button>
                        <Link :href="`/layanan/ptsp/resi/${ptspRequest.ticket_code}`" class="ui-button ui-button--secondary ui-button--md">
                            {{ publicText("copy.vue_services_ptsprevision", "text_004", "Batal") }}
                        </Link>
                    </div>
                </form>
            </section>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(860px, calc(100% - 2rem)); margin-inline: auto; padding: 2.25rem 0 4rem; }
.portal-card { background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; padding: 1.5rem; display: grid; gap: 1.25rem; }
.rev-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 1rem; }
.rev-head h1 { margin: .2rem 0; font-size: 1.6rem; }
.muted { color: var(--public-muted, #526673); margin: 0; }
.revision-box { padding: .9rem 1rem; border-radius: 12px; background: #fff1d5; color: #80500d; }
.revision-box p { margin: .25rem 0 0; }
.form-stack { display: grid; gap: 1rem; }
.docs-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
.action-row { display: flex; flex-wrap: wrap; gap: .75rem; }
</style>
