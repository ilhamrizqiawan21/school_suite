<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface GalleryPhoto {
    id: number;
    title?: string | null;
    caption?: string | null;
    name?: string | null;
    is_published: boolean;
    url: string;
}

const props = defineProps<{ photos: GalleryPhoto[]; can_manage: boolean }>();
const page = usePage<any>();
const editing = ref<GalleryPhoto | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const form = useForm({
    photo: null as File | null,
    title: '',
    caption: '',
    is_published: true,
});

const selectPhoto = (event: Event) => {
    form.photo = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.clearErrors('photo');
};

const reset = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    if (fileInput.value) fileInput.value.value = '';
};

const edit = (photo: GalleryPhoto) => {
    editing.value = photo;
    form.photo = null;
    form.title = photo.title ?? '';
    form.caption = photo.caption ?? '';
    form.is_published = photo.is_published;
    form.clearErrors();
    if (fileInput.value) fileInput.value.value = '';
};

const submit = () => {
    const endpoint = editing.value ? `/pengaturan/galeri/${editing.value.id}` : '/pengaturan/galeri';
    form.transform((data) => editing.value ? { ...data, _method: 'put' } : data).post(endpoint, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: reset,
    });
};

const remove = (photo: GalleryPhoto) => {
    if (!window.confirm(`Hapus foto ${photo.title || photo.name || 'ini'} dari galeri?`)) return;
    router.delete(`/pengaturan/galeri/${photo.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Galeri Sekolah" />
    <AppLayout title="Galeri Sekolah">
        <main class="view gallery-page">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Portofolio sekolah</p>
                    <h1>Galeri sekolah</h1>
                    <p>Pilih momen yang ingin ditampilkan sebagai wajah sekolah pada halaman portofolio publik.</p>
                </div>
            </section>

            <div v-if="page.props.flash?.message" class="notice success" role="status">{{ page.props.flash.message }}</div>

            <form class="panel gallery-editor" @submit.prevent="submit">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">{{ editing ? 'Edit foto' : 'Tambah foto' }}</p>
                        <h2>{{ editing ? 'Perbarui momen sekolah' : 'Tambahkan momen sekolah' }}</h2>
                        <p>Gunakan foto kegiatan, ruang belajar, prestasi, atau suasana sekolah yang mewakili portofolio.</p>
                    </div>
                    <button v-if="editing" type="button" class="secondary-button" @click="reset">Foto baru</button>
                </div>
                <div class="gallery-fields">
                    <label class="wide"><span>Foto{{ editing ? ' baru (opsional)' : '' }}</span><input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" :required="!editing" @change="selectPhoto" /><small>JPG, PNG, atau WebP; maksimal 4 MB.</small><em v-if="form.errors.photo">{{ form.errors.photo }}</em></label>
                    <label><span>Judul foto</span><input v-model="form.title" maxlength="120" placeholder="Contoh: Laboratorium sains" /><em v-if="form.errors.title">{{ form.errors.title }}</em></label>
                    <label><span>Keterangan</span><textarea v-model="form.caption" rows="3" maxlength="255" placeholder="Keterangan singkat untuk pengunjung portofolio."></textarea><em v-if="form.errors.caption">{{ form.errors.caption }}</em></label>
                    <label class="publish-choice"><input v-model="form.is_published" type="checkbox" /><span><strong>Tampilkan di portofolio</strong><small>Foto yang tidak dicentang hanya tersimpan di panel admin.</small></span></label>
                </div>
                <div class="form-actions"><button type="button" class="secondary-button" :disabled="form.processing" @click="reset">Batal</button><button type="submit" class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : editing ? 'Simpan perubahan' : 'Tambahkan ke galeri' }}</button></div>
            </form>

            <section class="panel gallery-list">
                <div class="section-heading"><div><p class="eyebrow">Koleksi portofolio</p><h2>Foto sekolah</h2><p>{{ photos.length }} foto tersimpan.</p></div></div>
                <div v-if="photos.length" class="gallery-grid">
                    <article v-for="photo in photos" :key="photo.id" class="gallery-card">
                        <div class="gallery-image"><img :src="photo.url" :alt="photo.title || photo.caption || photo.name || 'Foto sekolah'" /><span :class="['visibility-badge', photo.is_published ? 'published' : 'hidden']">{{ photo.is_published ? 'Tampil' : 'Disembunyikan' }}</span></div>
                        <div class="gallery-card-body"><h3>{{ photo.title || photo.name || 'Foto sekolah' }}</h3><p v-if="photo.caption">{{ photo.caption }}</p><div class="gallery-actions"><button type="button" class="secondary-button" @click="edit(photo)">Ubah</button><button type="button" class="text-button danger" @click="remove(photo)">Hapus</button></div></div>
                    </article>
                </div>
                <div v-else class="empty-state"><strong>Belum ada foto galeri</strong><p>Tambahkan foto pertama untuk mulai membangun portofolio sekolah.</p></div>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.gallery-page{display:grid;gap:1.25rem;max-width:1100px;margin:auto}.gallery-editor,.gallery-list{display:grid;gap:1.25rem;padding:1.25rem}.section-heading h2{margin:0;font-size:1.05rem}.section-heading p{margin:.35rem 0 0;color:var(--ink-soft);font-size:.8rem;line-height:1.5}.gallery-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.gallery-fields label{display:grid;align-content:start;gap:.45rem;color:var(--ink-soft);font-size:.8rem;font-weight:700}.gallery-fields label.wide{grid-column:1/-1}.gallery-fields input:not([type=checkbox]),.gallery-fields textarea{width:100%;padding:.7rem .8rem;color:var(--ink);background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem;font:inherit;font-weight:400}.gallery-fields textarea{resize:vertical;line-height:1.5}.gallery-fields small{font-size:.72rem;font-weight:400;line-height:1.5}.gallery-fields em{color:var(--red);font-size:.75rem;font-style:normal}.publish-choice{display:flex!important;grid-column:1/-1;align-items:flex-start;gap:.65rem;padding:.8rem;background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem}.publish-choice input{width:16px;height:16px;margin-top:.1rem;accent-color:var(--primary)}.publish-choice span{display:grid;gap:.2rem}.publish-choice strong{color:var(--ink);font-size:.8rem}.form-actions,.gallery-actions{display:flex;align-items:center;justify-content:flex-end;gap:.65rem}.gallery-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.gallery-card{overflow:hidden;border:1px solid var(--line);border-radius:.8rem;background:var(--surface-subtle)}.gallery-image{position:relative;height:180px;background:var(--surface-strong)}.gallery-image img{display:block;width:100%;height:100%;object-fit:cover}.visibility-badge{position:absolute;top:.65rem;right:.65rem;padding:.25rem .5rem;border-radius:999px;background:rgba(255,255,255,.9);font-size:.65rem;font-weight:800}.visibility-badge.published{color:var(--green)}.visibility-badge.hidden{color:var(--ink-soft)}.gallery-card-body{display:grid;gap:.55rem;padding:.8rem}.gallery-card-body h3{margin:0;color:var(--ink);font-size:.9rem}.gallery-card-body p{display:-webkit-box;margin:0;min-height:2.4rem;color:var(--ink-soft);font-size:.75rem;line-height:1.5;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2}.gallery-actions{justify-content:flex-start;margin-top:.2rem}.text-button{font-size:.75rem;font-weight:700}.danger{color:var(--red)}.empty-state{padding:3rem 1rem;text-align:center;color:var(--ink-soft)}.empty-state strong{display:block;color:var(--ink)}.notice{padding:1rem;border:1px solid #cbd5e1;border-radius:.75rem;font-size:.85rem}.notice.success{background:#ecfdf5;color:#166534}@media(max-width:760px){.gallery-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.gallery-fields{grid-template-columns:1fr}.gallery-fields label.wide,.publish-choice{grid-column:auto}.gallery-grid{grid-template-columns:1fr}.form-actions{align-items:stretch;flex-direction:column-reverse}.form-actions>*{width:100%}}
</style>
