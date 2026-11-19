<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    urgencyOptions: { value: string; label: string }[];
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const form = useForm({
    agency_name: '',
    contact_person: '',
    contact_position: '',
    contact_phone: '',
    contact_email: '',
    letter_number: '',
    letter_date: '',
    urgency: 'ordinary',
    classification: 'Umum',
    subject: '',
    summary: '',
    primary_pdf: null as File | null,
    supporting_files: [] as File[],
    verification_secret: '',
    statement_accepted: false,
    idempotency_key: `srt-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
});

const onPrimaryPdfChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.primary_pdf = input.files?.[0] ?? null;
};

const onSupportingChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.supporting_files = Array.from(input.files ?? []);
};

const submit = () => {
    form.post('/layanan/surat-dinas', { forceFormData: true });
};
</script>

<template>
    <Head :title="`Kirim Surat Dinas Eksternal — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="portal-container">
            <header class="portal-hero">
                <p class="eyebrow">Persuratan Eksternal Instansi</p>
                <h1>Kirim Surat Dinas Resmi</h1>
                <p>Instansi atau mitra eksternal dapat mengirim surat resmi secara digital. Surat langsung tercatat pada agenda Surat Masuk Tata Usaha dan menerbitkan tanda terima resmi.</p>
                <div class="hero-links">
                    <Link href="/lacak?category=surat_eksternal" class="ui-button ui-button--secondary ui-button--md">Lacak Surat Terkirim</Link>
                </div>
            </header>

            <section class="portal-card">
                <form class="form-grid" @submit.prevent="submit">
                    <FormField id="ext-agency" label="Nama Instansi / Lembaga Pengirim" required :error="form.errors.agency_name" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.agency_name" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="ext-contact-person" label="Nama Narahubung" required :error="form.errors.contact_person" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.contact_person" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="ext-contact-pos" label="Jabatan Narahubung" required :error="form.errors.contact_position" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.contact_position" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="ext-contact-phone" label="Nomor Telepon / WhatsApp Narahubung" required :error="form.errors.contact_phone" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.contact_phone" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="ext-contact-email" label="Email Narahubung (Opsional)" :error="form.errors.contact_email" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.contact_email" type="email" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="ext-letter-number" label="Nomor Surat Dinas" required :error="form.errors.letter_number" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.letter_number" type="text" required placeholder="Contoh: 421/104/DISDIK/2026" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="ext-letter-date" label="Tanggal Surat" required :error="form.errors.letter_date" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.letter_date" type="date" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="ext-urgency" label="Sifat / Urgensi Surat" required :error="form.errors.urgency" v-slot="{ inputId, describedBy, invalid }">
                        <select :id="inputId" v-model="form.urgency" :aria-describedby="describedBy" :aria-invalid="invalid">
                            <option v-for="opt in urgencyOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </FormField>

                    <FormField id="ext-classification" label="Klasifikasi Surat" :error="form.errors.classification" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.classification" type="text" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="ext-secret" label="PIN Verifikasi Tanda Terima (Opsional)" hint="Kosongkan untuk PIN otomatis 6 digit." :error="form.errors.verification_secret" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.verification_secret" type="text" minlength="4" maxlength="24" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <div class="full-span">
                        <FormField id="ext-subject" label="Perihal Surat" required :error="form.errors.subject" v-slot="{ inputId, describedBy, invalid }">
                            <input :id="inputId" v-model="form.subject" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                    </div>

                    <div class="full-span">
                        <FormField id="ext-summary" label="Ringkasan Isi Surat" required :error="form.errors.summary" v-slot="{ inputId, describedBy, invalid }">
                            <textarea :id="inputId" v-model="form.summary" rows="3" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                    </div>

                    <FormField id="ext-primary-pdf" label="Berkas Surat Utama (PDF, maks. 5 MB)" required :error="form.errors.primary_pdf" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" type="file" accept=".pdf" required :aria-describedby="describedBy" :aria-invalid="invalid" @change="onPrimaryPdfChange" />
                    </FormField>

                    <FormField id="ext-supporting" label="Lampiran Pendukung (Opsional, maks. 5 berkas)" :error="form.errors.supporting_files" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" :aria-describedby="describedBy" :aria-invalid="invalid" @change="onSupportingChange" />
                    </FormField>

                    <div class="full-span">
                        <label class="checkbox-label">
                            <input v-model="form.statement_accepted" type="checkbox" />
                            <span>Saya menyatakan surat dinas beserta lampiran yang dikirimkan resmi dan sah dari instansi pengirim.</span>
                        </label>
                        <p v-if="form.errors.statement_accepted" class="field-error" role="alert">{{ form.errors.statement_accepted }}</p>
                    </div>

                    <div class="full-span">
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="form.processing">
                            Kirim Surat &amp; Terbitkan Tanda Terima
                        </button>
                    </div>
                </form>
            </section>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(980px, calc(100% - 2rem)); margin-inline: auto; padding: 2.25rem 0 4rem; display: grid; gap: 1.5rem; }
.portal-hero h1 { margin: .35rem 0 .45rem; font-size: clamp(1.75rem, 3.5vw, 2.5rem); line-height: 1.15; }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); max-width: 65ch; }
.hero-links { margin-top: 1rem; }
.portal-card { background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; padding: 1.5rem; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
.full-span { grid-column: 1 / -1; }
.checkbox-label { display: flex; align-items: flex-start; gap: .6rem; font-size: .9rem; cursor: pointer; }
.checkbox-label input { width: 20px; height: 20px; margin-top: .1rem; }
.field-error { margin: .35rem 0 0; color: #b13f38; font-size: .8rem; font-weight: 600; }
</style>
