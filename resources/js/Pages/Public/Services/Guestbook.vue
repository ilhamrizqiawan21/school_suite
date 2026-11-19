<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    categories: { value: string; label: string }[];
    hosts: { id: number; name: string }[];
    confirmationSlip?: {
        visit_code: string;
        visitor_name: string;
        host_name: string;
        checked_in_at: string;
    } | null;
}>();

const page = usePage();
const flashMessage = computed(() => (page.props.flash as { message?: string } | undefined)?.message);
const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const form = useForm({
    visitor_name: '',
    visitor_organization: '',
    visitor_phone: '',
    visitor_email: '',
    category: 'tamu_dinas',
    purpose: '',
    host_teacher_id: '' as string | number,
    host_name: '',
    host_unit: '',
    notes: '',
});

const submit = () => {
    form.post('/layanan/buku-tamu', {
        onSuccess: () => form.reset('visitor_name', 'visitor_organization', 'visitor_phone', 'visitor_email', 'purpose', 'host_teacher_id', 'host_name', 'host_unit', 'notes'),
    });
};
</script>

<template>
    <Head :title="`Buku Tamu Digital — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="portal-container">
            <header class="portal-hero">
                <p class="eyebrow">Resepsionis &amp; Penerimaan Tamu</p>
                <h1>Registrasi Buku Tamu Digital</h1>
                <p>Silakan isi data kunjungan Anda untuk dicatat oleh resepsionis. Data pengunjung bersifat privat dan hanya dapat dilihat oleh petugas berwenang.</p>
            </header>

            <div v-if="flashMessage" class="alert-banner" role="status">{{ flashMessage }}</div>

            <section v-if="confirmationSlip" class="slip-card" role="status">
                <strong>Bukti Registrasi Tamu</strong>
                <div class="slip-code">{{ confirmationSlip.visit_code }}</div>
                <p>Pengunjung: <strong>{{ confirmationSlip.visitor_name }}</strong> · Tujuan: <strong>{{ confirmationSlip.host_name }}</strong></p>
            </section>

            <section class="portal-card">
                <form class="form-grid" @submit.prevent="submit">
                    <FormField id="guest-name" label="Nama Pengunjung" required :error="form.errors.visitor_name" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.visitor_name" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="guest-org" label="Instansi / Asal / Hubungan" :error="form.errors.visitor_organization" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.visitor_organization" type="text" placeholder="Contoh: Dinas Pendidikan / Wali Kelas X" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="guest-phone" label="Nomor HP / Kontak" required :error="form.errors.visitor_phone" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.visitor_phone" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="guest-category" label="Kategori Tamu" required :error="form.errors.category" v-slot="{ inputId, describedBy, invalid }">
                        <select :id="inputId" v-model="form.category" :aria-describedby="describedBy" :aria-invalid="invalid">
                            <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                        </select>
                    </FormField>

                    <FormField id="guest-host-select" label="Pegawai / Guru Tujuan (Jika Ada)" :error="form.errors.host_teacher_id" v-slot="{ inputId, describedBy, invalid }">
                        <select :id="inputId" v-model="form.host_teacher_id" :aria-describedby="describedBy" :aria-invalid="invalid">
                            <option value="">-- Pilih Pegawai atau Isi Manual --</option>
                            <option v-for="h in hosts" :key="h.id" :value="h.id">{{ h.name }}</option>
                        </select>
                    </FormField>

                    <FormField id="guest-host-manual" label="Nama Tujuan Lain / Unit (Opsional)" :error="form.errors.host_name" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.host_name" type="text" placeholder="Contoh: Kepala Tata Usaha / Loket PTSP" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <div class="full-span">
                        <FormField id="guest-purpose" label="Keperluan Kunjungan" required :error="form.errors.purpose" v-slot="{ inputId, describedBy, invalid }">
                            <input :id="inputId" v-model="form.purpose" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                    </div>

                    <div class="full-span">
                        <FormField id="guest-notes" label="Catatan Tambahan" :error="form.errors.notes" v-slot="{ inputId, describedBy, invalid }">
                            <textarea :id="inputId" v-model="form.notes" rows="2" :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                    </div>

                    <div class="full-span">
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="form.processing">
                            Catat Kehadiran Tamu
                        </button>
                    </div>
                </form>
            </section>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(920px, calc(100% - 2rem)); margin-inline: auto; padding: 2.25rem 0 4rem; display: grid; gap: 1.5rem; }
.portal-hero h1 { margin: .35rem 0 .45rem; font-size: clamp(1.75rem, 3.5vw, 2.5rem); line-height: 1.15; }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); max-width: 65ch; }
.portal-card { background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; padding: 1.5rem; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
.full-span { grid-column: 1 / -1; }
.alert-banner { padding: .85rem 1rem; border-radius: 12px; background: #e1f2e8; color: #1b5c3e; border: 1px solid #b6dec9; font-weight: 600; }
.slip-card { background: #eef4ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 1.1rem 1.25rem; display: grid; gap: .3rem; }
.slip-code { font-size: 1.35rem; font-weight: 800; color: #1e3a8a; letter-spacing: .06em; }
.slip-card p { margin: 0; }
</style>
