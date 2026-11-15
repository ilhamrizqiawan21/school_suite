<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface GalleryPhoto {
    id: number;
    name?: string | null;
    url: string;
}

interface GalleryAlbum {
    id: number;
    title: string;
    caption?: string | null;
    is_published: boolean;
    photos: GalleryPhoto[];
}

const props = defineProps<{ albums: GalleryAlbum[]; can_manage: boolean }>();
const page = usePage<any>();
const showForm = ref(false);
const editing = ref<GalleryAlbum | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const previewUrls = ref<string[]>([]);
const form = useForm({
    photos: [] as File[],
    title: '',
    caption: '',
    is_published: true,
});

const clearPreviews = () => {
    previewUrls.value.forEach((url) => URL.revokeObjectURL(url));
    previewUrls.value = [];
};

onBeforeUnmount(clearPreviews);

const selectPhotos = (event: Event) => {
    clearPreviews();
    const files = Array.from((event.target as HTMLInputElement).files ?? []);
    form.photos = files.slice(0, 4);
    previewUrls.value = form.photos.map((file) => URL.createObjectURL(file));
    form.clearErrors('photos');
};

const reset = () => {
    showForm.value = false;
    editing.value = null;
    form.reset();
    form.clearErrors();
    clearPreviews();
    if (fileInput.value) fileInput.value.value = '';
};

const openCreate = () => {
    reset();
    showForm.value = true;
};

const edit = (album: GalleryAlbum) => {
    editing.value = album;
    form.photos = [];
    form.title = album.title;
    form.caption = album.caption ?? '';
    form.is_published = album.is_published;
    form.clearErrors();
    clearPreviews();
    if (fileInput.value) fileInput.value.value = '';
    showForm.value = true;
};

const submit = () => {
    const endpoint = editing.value ? `/pengaturan/galeri/${editing.value.id}` : '/pengaturan/galeri';
    form.transform((data) => editing.value ? { ...data, _method: 'put' } : data).post(endpoint, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: reset,
    });
};

const remove = (album: GalleryAlbum) => {
    if (!window.confirm(`Hapus grup foto “${album.title}” dari galeri?`)) return;
    router.delete(`/pengaturan/galeri/${album.id}`, { preserveScroll: true });
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
                    <p>Kelompokkan sampai empat foto dalam satu momen agar portofolio lebih rapi dan mudah dikelola.</p>
                </div>
                <AppButton variant="primary" icon="plus" v-if="can_manage" type="button" class="primary-button" @click="openCreate"> Tambah grup foto</AppButton>
            </section>

            <div v-if="page.props.flash?.message" class="notice success" role="status">{{ page.props.flash.message }}</div>

            <div v-if="showForm" role="dialog" aria-modal="true" aria-labelledby="gallery-modal-title" class="modal-backdrop" @click.self="reset" @keydown.esc="reset">
                <form class="modal-card gallery-editor" @submit.prevent="submit">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">{{ editing ? 'Edit grup foto' : 'Tambah grup foto' }}</p>
                            <h2 id="gallery-modal-title">{{ editing ? 'Perbarui momen sekolah' : 'Tambahkan momen sekolah' }}</h2>
                            <p>Satu judul dapat memuat maksimal 4 foto dengan ukuran total maksimal 5 MB.</p>
                        </div>
                        <AppButton variant="ghost" type="button" class="modal-close" aria-label="Tutup formulir galeri" @click="reset">×</AppButton>
                    </div>
                    <div class="gallery-fields">
                        <label class="wide"><span>Foto{{ editing ? ' pengganti (opsional)' : '' }}</span><span class="file-picker"><input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" multiple :required="!editing" @change="selectPhotos" /><span class="file-picker-button">{{ form.photos.length ? `${form.photos.length} foto dipilih` : 'Pilih foto' }}</span><span class="file-picker-name">{{ form.photos.length ? form.photos.map((file) => file.name).join(', ') : 'Maksimal 4 foto · total 5 MB' }}</span></span><small>JPG, PNG, atau WebP. Pilihan lebih dari 4 foto akan dipotong menjadi 4 foto pertama.</small><em v-if="form.errors.photos">{{ form.errors.photos }}</em></label>
                        <div v-if="previewUrls.length" class="selected-previews wide"><figure v-for="(url, index) in previewUrls" :key="url"><img :src="url" :alt="form.photos[index]?.name || `Pratinjau foto ${index + 1}`" /><figcaption>Foto {{ index + 1 }}</figcaption></figure></div>
                        <label><span>Judul grup foto</span><input v-model="form.title" maxlength="120" placeholder="Contoh: Kegiatan belajar di laboratorium" required /><em v-if="form.errors.title">{{ form.errors.title }}</em></label>
                        <label><span>Keterangan grup</span><textarea v-model="form.caption" rows="3" maxlength="255" placeholder="Keterangan singkat untuk pengunjung portofolio."></textarea><em v-if="form.errors.caption">{{ form.errors.caption }}</em></label>
                        <label class="publish-choice"><input v-model="form.is_published" type="checkbox" /><span><strong>Tampilkan di portofolio</strong><small>Grup yang tidak dicentang hanya tersimpan di panel admin.</small></span></label>
                    </div>
                    <div class="form-actions"><AppButton variant="secondary"  type="button" class="secondary-button" :disabled="form.processing" @click="reset">Batal</AppButton><AppButton variant="primary" :loading="form.processing" type="submit" class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : editing ? 'Simpan perubahan' : 'Tambahkan ke galeri' }}</AppButton></div>
                </form>
            </div>

            <section class="panel gallery-list">
                <div class="section-heading"><div><p class="eyebrow">Koleksi portofolio</p><h2>Grup foto sekolah</h2><p>{{ albums.length }} momen tersimpan.</p></div></div>
                <div v-if="albums.length" class="album-grid">
                    <article v-for="album in albums" :key="album.id" class="album-card">
                        <div class="album-cover"><div class="cover-grid"><img v-for="photo in album.photos" :key="photo.id" :src="photo.url" :alt="photo.name || album.title" /></div><span :class="['visibility-badge', album.is_published ? 'published' : 'hidden']">{{ album.is_published ? 'Tampil' : 'Disembunyikan' }}</span><span class="photo-count">{{ album.photos.length }} foto</span></div>
                        <div class="album-body"><div><h3>{{ album.title }}</h3><p v-if="album.caption">{{ album.caption }}</p></div><div class="album-actions"><AppButton variant="secondary" size="sm" icon="edit" type="button" @click="edit(album)">Ubah grup</AppButton><AppButton variant="danger" size="sm" icon="delete" type="button" @click="remove(album)">Hapus</AppButton></div></div>
                    </article>
                </div>
                <div v-else class="empty-state"><strong>Belum ada grup foto</strong><p>Tambahkan satu momen dengan beberapa foto untuk mulai membangun portofolio sekolah.</p></div>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.gallery-page{display:grid;gap:1.25rem;max-width:1100px;margin:auto}.gallery-editor,.gallery-list{display:grid;gap:1.25rem;padding:1.25rem}.section-heading h2{margin:0;font-size:1.05rem}.section-heading p{margin:.35rem 0 0;color:var(--ink-soft);font-size:var(--type-meta);line-height:1.5}.gallery-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.gallery-fields label{display:grid;align-content:start;gap:.45rem;color:var(--ink-soft);font-size:var(--type-meta);font-weight:var(--weight-semibold)}.gallery-fields label.wide,.selected-previews.wide{grid-column:1/-1}.gallery-fields input:not([type=checkbox]),.gallery-fields textarea{width:100%;padding:.7rem .8rem;color:var(--ink);background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem;font:inherit;font-weight:var(--weight-regular)}.gallery-fields textarea{resize:vertical;line-height:1.5}.gallery-fields small{font-size:var(--type-meta);font-weight:var(--weight-regular);line-height:1.5}.gallery-fields em{color:var(--red);font-size:var(--type-meta);font-style:normal}.file-picker{position:relative;display:flex;align-items:center;gap:.7rem;min-height:46px;width:100%;padding:.3rem .65rem;background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem;cursor:pointer}.file-picker input{position:absolute;width:1px!important;height:1px!important;overflow:hidden;clip:rect(0,0,0,0);opacity:0}.file-picker-button{display:inline-flex;align-items:center;justify-content:center;flex:none;min-height:34px;padding:.45rem .8rem;color:var(--primary);background:var(--primary-soft);border:1px solid color-mix(in srgb,var(--primary) 18%,transparent);border-radius:.5rem;font-size:var(--type-control);font-weight:var(--weight-bold)}.file-picker-name{min-width:0;overflow:hidden;color:var(--ink-soft);font-size:var(--type-meta);font-weight:var(--weight-medium);text-overflow:ellipsis;white-space:nowrap}.file-picker:focus-within{outline:3px solid color-mix(in srgb,var(--primary) 30%,transparent);outline-offset:2px}.selected-previews{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.6rem}.selected-previews figure{position:relative;margin:0;overflow:hidden;border:1px solid var(--line);border-radius:.6rem;background:var(--surface-subtle)}.selected-previews img{display:block;width:100%;height:100px;object-fit:cover}.selected-previews figcaption{padding:.35rem .45rem;color:var(--ink-soft);font-size:var(--type-control)}.publish-choice{display:flex!important;grid-column:1/-1;align-items:flex-start;gap:.65rem;padding:.8rem;background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem}.publish-choice input{width:16px;height:16px;margin-top:.1rem;accent-color:var(--primary)}.publish-choice span{display:grid;gap:.2rem}.publish-choice strong{color:var(--ink);font-size:var(--type-meta)}.form-actions,.album-actions{display:flex;align-items:center;justify-content:flex-end;gap:.65rem}.album-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.album-card{overflow:hidden;border:1px solid var(--line);border-radius:.8rem;background:var(--surface-subtle)}.album-cover{position:relative;height:190px;background:var(--surface-strong)}.cover-grid{display:grid;width:100%;height:100%;grid-template-columns:repeat(2,1fr);grid-template-rows:repeat(2,1fr);gap:2px;background:var(--surface)}.cover-grid img{display:block;width:100%;height:100%;object-fit:cover}.visibility-badge,.photo-count{position:absolute;top:.65rem;padding:.25rem .5rem;border-radius:999px;background:rgba(255,255,255,.92);font-size:var(--type-meta);font-weight:var(--weight-bold)}.visibility-badge{right:.65rem}.photo-count{left:.65rem;color:var(--ink-soft)}.visibility-badge.published{color:var(--green)}.visibility-badge.hidden{color:var(--ink-soft)}.album-body{display:grid;gap:.7rem;padding:.85rem}.album-body h3{margin:0;color:var(--ink);font-size:var(--type-md)}.album-body p{display:-webkit-box;margin:.3rem 0 0;min-height:2.3rem;color:var(--ink-soft);font-size:var(--type-meta);line-height:1.5;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2}.album-actions{justify-content:flex-start}.empty-state{padding:3rem 1rem;text-align:center;color:var(--ink-soft)}.empty-state strong{display:block;color:var(--ink)}.notice{padding:1rem;border:1px solid #cbd5e1;border-radius:.75rem;font-size:var(--type-sm)}.notice.success{background:#ecfdf5;color:#166534}@media(max-width:800px){.album-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.gallery-fields{grid-template-columns:1fr}.gallery-fields label.wide,.selected-previews.wide,.publish-choice{grid-column:auto}.selected-previews{grid-template-columns:repeat(2,1fr)}.album-grid{grid-template-columns:1fr}.form-actions{align-items:stretch;flex-direction:column-reverse}.form-actions>*{width:100%}}
.gallery-page .hero-card { background: var(--surface); border-radius: 14px; box-shadow: none; }
.gallery-page .gallery-editor, .gallery-page .gallery-list { padding: clamp(1rem, 2.5vw, 1.5rem); border-radius: 14px; box-shadow: none; }
.gallery-fields input:not([type=checkbox]), .gallery-fields textarea { min-height: 44px; background: var(--surface); border-color: var(--line-strong); }
.gallery-page .file-picker { min-height: 48px; background: var(--surface); border-color: var(--line-strong); }
.gallery-fields input:focus-visible, .gallery-fields textarea:focus-visible, .gallery-page button:focus-visible, .gallery-page .file-picker:focus-within { outline: 3px solid var(--primary); outline-offset: 2px; }
.album-card { background: var(--surface); border-radius: 12px; }
.gallery-fields label, .album-card, .album-body { min-width: 0; }
.gallery-page .section-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding-bottom: .85rem; border-bottom: 1px solid var(--line); }
.gallery-fields .file-picker input { min-width: 0; min-height: 0; padding: 0; border: 0; }
.publish-choice input { flex: 0 0 16px; }
.album-card { display: flex; flex-direction: column; }
.album-body { flex: 1; grid-template-rows: 1fr auto; gap: 1rem; padding: 1rem; }
.album-body h3 { overflow-wrap: anywhere; }
.cover-grid img:only-child { grid-column: 1 / -1; grid-row: 1 / -1; }
.cover-grid img:first-child:nth-last-child(2), .cover-grid img:first-child:nth-last-child(2) ~ img { grid-row: 1 / -1; }
.visibility-badge, .photo-count { background: var(--surface); color: var(--ink); }
.visibility-badge.published { color: var(--green); }
.visibility-badge.hidden { color: var(--ink-soft); }
.notice.success { background: var(--green-soft); color: var(--green); border-color: var(--line); }
@media (max-width: 600px) { .album-actions { flex-wrap: wrap; } .album-actions button { flex: 1; } }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .56); backdrop-filter: blur(3px); }
.modal-card { width: min(680px, 100%); max-height: 90dvh; overflow-y: auto; display: grid; gap: 1rem; padding: 1.35rem; border: 1px solid var(--line); border-radius: 16px; background: var(--surface); box-shadow: 0 24px 80px rgb(15 23 42 / .25); }
</style>
