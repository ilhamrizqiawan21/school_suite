<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import type { SharedProps } from '../../../types';

interface OrganizationMember {
    id: number;
    parent_id?: number | null;
    name: string;
    position: string;
    department?: string | null;
    photo_url?: string | null;
    sort_order: number;
    is_published: boolean;
    depth: number;
}

const props = defineProps<{ members: OrganizationMember[] }>();
const page = usePage<SharedProps>();
const showForm = ref(false);
const editing = ref<OrganizationMember | null>(null);
const form = useForm({
    name: '',
    position: '',
    department: '',
    parent_id: null as number | null,
    sort_order: 0,
    is_published: true,
    photo: null as File | null,
});

const parentOptions = computed(() => props.members.filter((member) => member.id !== editing.value?.id));
const uploadPreview = ref('');
const clearUploadPreview = () => {
    if (uploadPreview.value.startsWith('blob:')) URL.revokeObjectURL(uploadPreview.value);
    uploadPreview.value = '';
};
onBeforeUnmount(clearUploadPreview);
const photoPreview = computed(() => uploadPreview.value || editing.value?.photo_url || '');
const selectPhoto = (event: Event) => {
    clearUploadPreview();
    form.photo = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.clearErrors('photo');
    uploadPreview.value = form.photo ? URL.createObjectURL(form.photo) : '';
};
const parentName = (parentId?: number | null) => props.members.find((member) => member.id === parentId)?.name ?? 'Pimpinan utama';
const reset = () => {
    showForm.value = false;
    editing.value = null;
    clearUploadPreview();
    form.reset();
    form.photo = null;
    form.clearErrors();
};
const openCreate = () => {
    reset();
    showForm.value = true;
};
const edit = (member: OrganizationMember) => {
    editing.value = member;
    form.name = member.name;
    form.position = member.position;
    form.department = member.department ?? '';
    form.parent_id = member.parent_id ?? null;
    form.sort_order = member.sort_order;
    form.is_published = member.is_published;
    form.photo = null;
    clearUploadPreview();
    showForm.value = true;
};
const submit = () => {
    const options = { preserveScroll: true, onSuccess: reset };
    if (editing.value) {
        form.put('/pengaturan/struktur-organisasi/' + editing.value.id, { ...options, forceFormData: true });
    } else {
        form.post('/pengaturan/struktur-organisasi', { ...options, forceFormData: true });
    }
};
const remove = (member: OrganizationMember) => {
    if (!window.confirm('Hapus ' + member.name + ' dari struktur organisasi?')) return;
    form.delete('/pengaturan/struktur-organisasi/' + member.id, { preserveScroll: true });
};
</script>

<template>
    <Head title="Struktur Organisasi" />
    <AppLayout title="Struktur Organisasi">
        <main class="view organization-page">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Profil sekolah</p>
                    <h1>Struktur organisasi sekolah</h1>
                    <p>Susun pimpinan dan unit kerja sekolah dalam bentuk hierarki yang akan tampil di profil publik.</p>
                </div>
                <AppButton variant="primary" icon="plus" type="button" class="primary-button" @click="openCreate"> Tambah anggota</AppButton>
            </section>

            <div v-if="page.props.flash?.message" class="notice success" role="status">{{ page.props.flash.message }}</div>
            <div v-if="Object.keys(form.errors).length" class="notice error" role="alert">Periksa isian struktur organisasi yang ditandai.</div>

            <div v-if="showForm" role="dialog" aria-modal="true" aria-labelledby="org-modal-title" class="modal-backdrop" @click.self="reset" @keydown.esc="reset">
                <section class="modal-card editor-panel">
                    <div class="section-heading"><div><h2 id="org-modal-title">{{ editing ? 'Ubah anggota organisasi' : 'Tambah anggota organisasi' }}</h2><p>Pilih atasan. Kosongkan untuk pimpinan utama.</p></div><AppButton variant="ghost" type="button" class="modal-close" aria-label="Tutup formulir organisasi" @click="reset">×</AppButton></div>
                    <form class="organization-form" @submit.prevent="submit">
                        <label><span>Nama personel *</span><input v-model="form.name" maxlength="150" required placeholder="Contoh: Drs. Budi Santoso" /><em v-if="form.errors.name">{{ form.errors.name }}</em></label>
                        <label><span>Jabatan *</span><input v-model="form.position" maxlength="120" required placeholder="Contoh: Kepala Sekolah" /><em v-if="form.errors.position">{{ form.errors.position }}</em></label>
                        <label><span>Bidang / unit kerja</span><input v-model="form.department" maxlength="120" placeholder="Contoh: Pimpinan atau Kurikulum" /><em v-if="form.errors.department">{{ form.errors.department }}</em></label>
                        <label class="photo-field"><span>Foto personel</span><div class="file-picker"><input type="file" accept="image/jpeg,image/png,image/webp" @change="selectPhoto" /><span class="file-picker-button">Pilih foto</span><span class="file-picker-name">{{ form.photo?.name || (editing?.photo_url ? 'Foto saat ini tersimpan' : 'Belum ada foto dipilih') }}</span></div><div v-if="photoPreview" class="photo-preview"><img :src="photoPreview" alt="Pratinjau foto personel" /></div><small>JPG, PNG, atau WebP; maksimal 2 MB. Foto baru akan mengganti foto sebelumnya setelah disimpan.</small><em v-if="form.errors.photo">{{ form.errors.photo }}</em></label>
                        <label><span>Atasan langsung</span><select v-model="form.parent_id"><option :value="null">Pimpinan utama</option><option v-for="member in parentOptions" :key="member.id" :value="member.id">{{ member.name }} — {{ member.position }}</option></select><em v-if="form.errors.parent_id">{{ form.errors.parent_id }}</em></label>
                        <label><span>Urutan tampil</span><input v-model.number="form.sort_order" type="number" min="0" max="999" /><em v-if="form.errors.sort_order">{{ form.errors.sort_order }}</em></label>
                        <label class="publish-choice"><input v-model="form.is_published" type="checkbox" /><span><strong>Tampilkan di profil publik</strong><small>Matikan untuk menyimpan data internal tanpa menampilkannya kepada pengunjung.</small></span></label>
                        <div class="form-actions"><AppButton variant="secondary"  type="button" class="secondary-button" :disabled="form.processing" @click="reset">Batal</AppButton><AppButton variant="primary" :loading="form.processing" type="submit" class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : editing ? 'Simpan perubahan' : 'Tambah ke struktur' }}</AppButton></div>
                    </form>
                </section>
            </div>

            <section class="panel organization-list">
                <div class="section-heading"><div><h2>Peta organisasi</h2><p>{{ members.length }} anggota tersimpan. Kedalaman kartu menunjukkan hubungan atasan dan bawahan.</p></div></div>
                <div v-if="members.length" class="member-list">
                    <article v-for="member in members" :key="member.id" class="member-card" :style="{ '--depth': member.depth }">
                        <div class="member-rail" aria-hidden="true" />
                        <div class="member-avatar"><img v-if="member.photo_url" :src="member.photo_url" :alt="`Foto ${member.name}`" /><span v-else>{{ member.name.slice(0, 1) }}</span></div><div class="member-content"><div class="member-heading"><div><span class="member-position">{{ member.position }}</span><h3>{{ member.name }}</h3></div></div><p>{{ member.department || 'Unit kerja belum diisi' }} · Atasan: {{ parentName(member.parent_id) }}</p></div>
                        <span class="status" :class="member.is_published ? 'published' : 'hidden'">{{ member.is_published ? 'Publik' : 'Internal' }}</span>
                        <div class="member-actions"><AppButton variant="ghost" size="sm" type="button" class="text-button" @click="edit(member)">Ubah</AppButton><AppButton variant="danger" size="sm" type="button" class="text-button danger" @click="remove(member)">Hapus</AppButton></div>
                    </article>
                </div>
                <div v-else class="empty-state"><strong>Struktur organisasi belum diisi.</strong><p>Tambahkan pimpinan utama untuk mulai membangun hierarki sekolah.</p></div>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.organization-page{display:grid;gap:1.25rem;max-width:1100px;margin:auto}.editor-panel,.organization-list{display:grid;gap:1.25rem;padding:1.25rem}.section-heading{display:flex;align-items:start;justify-content:space-between;gap:1rem}.section-heading h2{margin:0;font-size:1.05rem}.section-heading p{margin:.4rem 0 0;color:var(--ink-soft);font-size:var(--type-meta);line-height:1.55}.organization-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.organization-form label{display:grid;align-content:start;gap:.45rem;color:var(--ink-soft);font-size:var(--type-meta);font-weight:var(--weight-semibold)}.organization-form label input:not([type=checkbox]),.organization-form select{width:100%;min-height:44px;padding:.7rem .8rem;color:var(--ink);background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem;font:inherit;font-weight:var(--weight-regular)}.organization-form em{color:var(--red);font-size:var(--type-meta);font-style:normal}.publish-choice{display:flex!important;grid-column:1/-1;align-items:flex-start;gap:.65rem;padding:.8rem;background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem}.publish-choice input{width:16px;height:16px;margin-top:.1rem;accent-color:var(--primary)}.publish-choice span{display:grid;gap:.2rem}.publish-choice strong{color:var(--ink);font-size:var(--type-meta)}.publish-choice small{font-size:var(--type-meta);font-weight:var(--weight-regular);line-height:1.5}.form-actions{display:flex;grid-column:1/-1;justify-content:flex-end}.member-list{display:grid;gap:.65rem}.member-card{position:relative;display:grid;grid-template-columns:5px minmax(0,1fr) auto;gap:.85rem;align-items:center;margin-left:calc(var(--depth) * 1.35rem);padding:.85rem .9rem .85rem calc(.9rem + var(--depth) * .2rem);background:var(--surface-subtle);border:1px solid var(--line);border-radius:.75rem}.member-rail{height:100%;min-height:48px;background:var(--primary);border-radius:999px}.member-heading{display:flex;align-items:start;justify-content:space-between;gap:.8rem}.member-position{color:var(--primary);font-size:var(--type-meta);font-weight:var(--weight-bold);text-transform:uppercase;letter-spacing:.04em}.member-content h3{margin:.2rem 0 0;color:var(--ink);font-size:var(--type-md)}.member-content p{margin:.3rem 0 0;color:var(--ink-soft);font-size:var(--type-meta)}.status{padding:.25rem .5rem;border-radius:999px;font-size:var(--type-meta);font-weight:var(--weight-bold)}.status.published{color:var(--green);background:#ecfdf5}.status.hidden{color:var(--ink-soft);background:var(--surface)}.member-actions{display:flex;gap:.7rem}.empty-state{padding:2.8rem 1rem;text-align:center;color:var(--ink-soft)}.empty-state strong{display:block;color:var(--ink)}.empty-state p{margin:.35rem 0 0;font-size:var(--type-meta)}.notice{padding:1rem;border:1px solid #cbd5e1;border-radius:.75rem;font-size:var(--type-sm)}.notice.success{color:#166534;background:#ecfdf5}.notice.error{color:#991b1b;background:#fef2f2}@media(max-width:650px){.organization-form{grid-template-columns:1fr}.publish-choice,.form-actions{grid-column:auto}.form-actions>*{width:100%}.section-heading{align-items:stretch;flex-direction:column}.member-card{grid-template-columns:5px minmax(0,1fr);margin-left:calc(var(--depth) * .7rem)}.member-actions{grid-column:2}.member-heading{align-items:flex-start;flex-direction:column}}
.photo-field{grid-column:1/-1}.file-picker{position:relative;display:flex;align-items:center;gap:.7rem;min-height:46px;padding:.3rem .65rem;background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem;cursor:pointer}.file-picker input{position:absolute;width:1px;height:1px;overflow:hidden;opacity:0}.file-picker-button{display:inline-flex;align-items:center;justify-content:center;min-height:34px;padding:.45rem .8rem;color:var(--primary);background:var(--primary-soft);border:1px solid color-mix(in srgb,var(--primary) 18%,transparent);border-radius:.5rem;font-size:var(--type-control);font-weight:var(--weight-bold)}.file-picker-name{min-width:0;overflow:hidden;color:var(--ink-soft);font-size:var(--type-meta);font-weight:var(--weight-medium);text-overflow:ellipsis;white-space:nowrap}.photo-field small{color:var(--ink-soft);font-size:var(--type-meta);font-weight:var(--weight-regular);line-height:1.5}.photo-preview{display:grid;place-items:center;width:96px;height:96px;overflow:hidden;border:1px solid var(--line);border-radius:.7rem;background:var(--surface-subtle)}.photo-preview img{width:100%;height:100%;object-fit:cover}.file-picker:focus-within{outline:3px solid color-mix(in srgb,var(--primary) 30%,transparent);outline-offset:2px}.member-card{grid-template-columns:5px 44px minmax(0,1fr) auto}.member-avatar{display:grid;place-items:center;width:44px;height:44px;overflow:hidden;color:var(--primary);background:var(--primary-soft);border-radius:50%;font-size:.95rem;font-weight:var(--weight-bold)}.member-avatar img{width:100%;height:100%;object-fit:cover}@media(max-width:650px){.member-card{grid-template-columns:5px 44px minmax(0,1fr);margin-left:calc(var(--depth) * .7rem)}.member-actions{grid-column:3}}
.organization-page .hero-card { background: var(--surface); border-radius: 14px; box-shadow: none; }
.organization-page .editor-panel, .organization-page .organization-list { padding: clamp(1rem, 2.5vw, 1.5rem); border-radius: 14px; box-shadow: none; }
.organization-form label input:not([type=checkbox]), .organization-form select { background: var(--surface); border-color: var(--line-strong); }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .56); backdrop-filter: blur(3px); }
.modal-card { width: min(680px, 100%); max-height: 90dvh; overflow-y: auto; border: 1px solid var(--line); background: var(--surface); box-shadow: 0 24px 80px rgb(15 23 42 / .25) !important; }
.form-actions { gap: .65rem; }
.organization-page .file-picker { min-height: 48px; background: var(--surface); border-color: var(--line-strong); }
.organization-form input:focus-visible, .organization-form select:focus-visible, .organization-page button:focus-visible, .organization-page .file-picker:focus-within { outline: 3px solid var(--primary); outline-offset: 2px; }
.member-card { background: var(--surface); border-radius: 12px; }
@media (max-width: 650px) { .member-card { margin-left: min(calc(var(--depth) * .5rem), 1.5rem); } }
.member-card { grid-template-columns: 5px 44px minmax(0,1fr) 5rem auto; }
.member-card > .status { justify-self: center; text-align: center; }
.status.published { color: var(--green); background: var(--green-soft); }
.notice.success { color: var(--green); background: var(--green-soft); border-color: var(--line); }
.notice.error { color: var(--red); background: var(--red-soft); border-color: var(--line); }
@media (max-width:650px) {
    .member-card { grid-template-columns: 5px 44px minmax(0,1fr) auto; }
    .member-card > .status { grid-column: 4; grid-row: 1; }
    .member-actions { grid-column: 3 / -1; }
}
</style>
