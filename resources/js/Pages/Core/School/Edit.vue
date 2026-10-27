<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import type { SharedProps } from '../../../types';

interface SchoolData {
    id: number;
    name: string;
    npsn: string;
    level: string;
    address?: string;
    phone?: string;
    email?: string;
    legal_name?: string;
    school_status?: string;
    accreditation?: string;
    principal_name?: string;
    website?: string;
    vision?: string;
    mission?: string;
    logo_path?: string;
    favicon_path?: string;
    settings?: { theme_primary?: string };
}

const props = defineProps<{
    school: SchoolData;
    canUpdate: boolean;
}>();

const page = usePage<SharedProps>();

const form = useForm({
    name: props.school.name,
    npsn: props.school.npsn,
    level: props.school.level,
    address: props.school.address ?? '',
    phone: props.school.phone ?? '',
    email: props.school.email ?? '',
    legal_name: props.school.legal_name ?? '',
    school_status: props.school.school_status ?? '',
    accreditation: props.school.accreditation ?? '',
    principal_name: props.school.principal_name ?? '',
    website: props.school.website ?? '',
    vision: props.school.vision ?? '',
    mission: props.school.mission ?? '',
    theme_primary: props.school.settings?.theme_primary ?? '#3157a4',
    logo: null as File | null,
    favicon: null as File | null,
});

const uploads = ref<Record<'logo' | 'favicon', string>>({ logo: '', favicon: '' });
const uploadVersion = ref(0);
const clearUploads = () => {
    Object.values(uploads.value).forEach((url) => { if (url) URL.revokeObjectURL(url); });
    uploads.value = { logo: '', favicon: '' };
};
onBeforeUnmount(clearUploads);
const selectImage = (kind: 'logo' | 'favicon', event: Event) => {
    if (uploads.value[kind]) URL.revokeObjectURL(uploads.value[kind]);
    form[kind] = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.clearErrors(kind);
    uploads.value[kind] = form[kind] ? URL.createObjectURL(form[kind]!) : '';
};
const clearImage = (kind: 'logo' | 'favicon') => {
    if (uploads.value[kind]) URL.revokeObjectURL(uploads.value[kind]);
    uploads.value[kind] = '';
    form[kind] = null;
    uploadVersion.value++;
};
const preview = (kind: 'logo' | 'favicon') => uploads.value[kind] || page.props.school?.[`${kind}_url`] || '';
const fields = [
    { key: 'name', label: 'Nama resmi sekolah', required: true, max: 255 },
    { key: 'npsn', label: 'NPSN', required: true, max: 20 },
    { key: 'legal_name', label: 'Nama badan hukum / yayasan', max: 255 },
    { key: 'principal_name', label: 'Nama kepala sekolah', max: 255 },
    { key: 'accreditation', label: 'Akreditasi', max: 10 },
    { key: 'phone', label: 'Nomor telepon', type: 'tel', max: 30 },
    { key: 'email', label: 'Email resmi', type: 'email', max: 255 },
    { key: 'website', label: 'Website', type: 'url', max: 255, placeholder: 'https://sekolah.sch.id' },
] as const;
const hasErrors = computed(() => Object.keys(form.errors).length > 0);
const submit = () => {
    form.post('/pengaturan/sekolah', {
        forceFormData: true, preserveScroll: true,
        onSuccess: () => { clearUploads(); form.logo = null; form.favicon = null; uploadVersion.value++; form.defaults(); },
    });
};
</script>

<template>
    <Head title="Profil Sekolah" />
    <AppLayout title="Profil Sekolah">
        <main class="view school-page">
            <section class="hero-card">
                <div>
                <p class="eyebrow">Pengaturan sekolah</p>
                <h1>Identitas & profil sekolah</h1>
                <p>Lengkapi identitas dan kontak yang digunakan pada kop surat, rapor, dan dokumen sekolah.</p>
                </div>
            </section>
            <div v-if="page.props.flash?.message" class="notice success" role="status">{{ page.props.flash.message }}</div>
            <div v-if="!canUpdate" class="notice" role="status">Anda dapat melihat profil sekolah. Perubahan dikelola administrator yang memiliki akses pengaturan sekolah.</div>
            <form class="school-form" @submit.prevent="submit">
                <div v-if="hasErrors" class="notice" role="alert">Profil sekolah belum disimpan. Periksa isian yang ditandai di bawah.</div>
                <fieldset :disabled="!canUpdate || form.processing">
                    <section class="panel settings-section">
                        <div class="section-heading"><h2>Identitas resmi</h2><p>Isian bertanda * wajib diisi. Gunakan nama dan data resmi sekolah.</p></div>
                        <div class="fields">
                            <label v-for="field in fields" :key="field.key">
                                <span>{{ field.label }}{{ 'required' in field && field.required ? ' *' : '' }}</span>
                                <input v-model="form[field.key]" :type="'type' in field ? field.type : 'text'" :required="'required' in field && field.required" :maxlength="field.max" :placeholder="'placeholder' in field ? field.placeholder : undefined" :aria-invalid="!!form.errors[field.key]" />
                                <em v-if="form.errors[field.key]">{{ form.errors[field.key] }}</em>
                            </label>
                            <label><span>Jenjang sekolah *</span><select v-model="form.level" required><option v-for="level in ['SD', 'SMP', 'SMA', 'SMK', 'MI', 'MTs', 'MA']" :key="level" :value="level">{{ level }}</option></select><em v-if="form.errors.level">{{ form.errors.level }}</em></label>
                            <label><span>Status sekolah</span><select v-model="form.school_status"><option value="">Belum diisi</option><option>Negeri</option><option>Swasta</option></select><em v-if="form.errors.school_status">{{ form.errors.school_status }}</em></label>
                            <label class="wide"><span>Alamat lengkap</span><textarea v-model="form.address" rows="3" placeholder="Jalan, kelurahan, kecamatan, kota/kabupaten, dan kode pos"></textarea><em v-if="form.errors.address">{{ form.errors.address }}</em></label>
                        </div>
                    </section>
                    <section class="panel settings-section">
                        <div class="section-heading"><h2>Logo & tampilan aplikasi</h2><p>Logo digunakan sebagai identitas sekolah. Favicon adalah ikon kecil pada tab browser.</p></div>
                        <div class="fields">
                            <label v-for="kind in (['logo', 'favicon'] as const)" :key="kind" class="upload-field">
                                <span>{{ kind === 'logo' ? 'Logo sekolah' : 'Ikon tab browser (favicon)' }}</span>
                                <div class="image-preview"><img v-if="preview(kind)" :src="preview(kind)" :alt="kind === 'logo' ? 'Pratinjau logo sekolah' : 'Pratinjau favicon'" /><small v-else>Belum ada gambar</small></div>
                                <input :key="`${kind}-${uploadVersion}`" type="file" :accept="kind === 'logo' ? 'image/jpeg,image/png,image/webp' : 'image/png'" @change="selectImage(kind, $event)" />
                                <small>{{ kind === 'logo' ? 'JPG, PNG, atau WebP; maksimal 2 MB.' : 'PNG persegi; maksimal 512 × 512 piksel dan 512 KB.' }} Mengunggah file baru akan mengganti gambar lama setelah disimpan.</small>
                                <em v-if="form.errors[kind]">{{ form.errors[kind] }}</em>
                                <button v-if="form[kind]" type="button" class="secondary-button" @click="clearImage(kind)">Batalkan gambar baru</button>
                            </label>
                        </div>
                        <div class="color-picker"><span>Warna utama aplikasi</span><div class="theme-options"><label v-for="color in ['#3157a4','#277450','#7255ae','#b87818','#b64a42']" :key="color" class="theme-choice"><input v-model="form.theme_primary" type="radio" :value="color"><i :style="{background: color}"></i><span>{{ color }}</span></label></div><em v-if="form.errors.theme_primary">{{ form.errors.theme_primary }}</em></div>
                    </section>
                    <section class="panel settings-section">
                        <div class="section-heading"><h2>Visi & misi</h2><p>Jelaskan arah dan tujuan pendidikan sekolah.</p></div>
                        <label><span>Visi</span><textarea v-model="form.vision" rows="3" maxlength="5000"></textarea><em v-if="form.errors.vision">{{ form.errors.vision }}</em></label>
                        <label><span>Misi</span><textarea v-model="form.mission" rows="5" maxlength="10000" placeholder="Tuliskan setiap butir misi pada baris terpisah"></textarea><em v-if="form.errors.mission">{{ form.errors.mission }}</em></label>
                    </section>
                    <div v-if="canUpdate" class="panel save-bar"><span>{{ form.isDirty ? 'Ada perubahan yang belum disimpan' : 'Profil sekolah sudah tersimpan' }}</span><button type="submit" class="primary-button" :disabled="form.processing || !form.isDirty">{{ form.processing ? 'Menyimpan…' : 'Simpan profil sekolah' }}</button></div>
                </fieldset>
            </form>
        </main>
    </AppLayout>
</template>

<style scoped>
.settings-section,.save-bar{padding:1.25rem}.section-heading h2{font-weight:750}.hero-card>div{min-width:0}.notice{padding:1rem;border:1px solid #cbd5e1;border-radius:.75rem;font-size:.85rem;line-height:1.6}.notice.success{background:#ecfdf5;color:#166534}
.school-page,.school-form,fieldset{display:grid;gap:1.25rem;min-width:0}.school-page{max-width:1100px;margin:auto}fieldset{border:0;padding:0;margin:0}.settings-section{display:grid;gap:1.25rem;min-width:0}.section-heading h2{margin:0;font-size:1rem}.section-heading p{margin:.4rem 0 0;color:var(--ink-soft);font-size:.8rem;line-height:1.6}.fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.settings-section label{display:grid;align-content:start;gap:.45rem;min-width:0;font-size:.8rem;font-weight:700}.settings-section input:not([type=radio]),.settings-section select,.settings-section textarea{width:100%;min-width:0;min-height:44px;padding:.7rem .8rem;border:1px solid var(--line);border-radius:.7rem;background:var(--surface);color:var(--ink);font:inherit;font-weight:400}.settings-section textarea{resize:vertical;line-height:1.6}.settings-section :is(input,select,textarea):focus-visible{outline:3px solid color-mix(in srgb,var(--primary) 30%,transparent);outline-offset:2px}.wide{grid-column:1/-1}.settings-section small{font-size:.72rem;font-weight:400;color:var(--ink-soft);line-height:1.6;overflow-wrap:anywhere}.settings-section em{font-size:.75rem;font-style:normal;color:var(--red)}.image-preview{display:grid;place-items:center;height:110px;border:1px dashed var(--line);border-radius:.7rem;background:var(--surface-subtle)}.image-preview img{max-width:100%;max-height:90px;object-fit:contain}.color-picker{display:grid;gap:.6rem;font-size:.8rem;font-weight:700}.theme-options{display:flex;flex-wrap:wrap;gap:.6rem}.settings-section .theme-choice{display:flex;align-items:center;gap:.4rem;padding:.65rem;border:1px solid var(--line);border-radius:.65rem;font-size:.75rem}.theme-choice i{width:1.1rem;height:1.1rem;border-radius:50%}.save-bar{display:flex;align-items:center;justify-content:space-between;gap:1rem}.save-bar span{font-size:.8rem;color:var(--ink-soft)}button{min-height:44px}.upload-field button{width:fit-content}fieldset:disabled input,fieldset:disabled select,fieldset:disabled textarea{opacity:.7}@media(max-width:640px){.fields{grid-template-columns:1fr}.save-bar{flex-direction:column;align-items:stretch}.save-bar button{width:100%}}
</style>
