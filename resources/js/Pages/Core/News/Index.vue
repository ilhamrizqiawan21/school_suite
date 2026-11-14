<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface Photo { id: number; name?: string | null; caption?: string | null; url: string }
interface NewsItem { id: number; title: string; slug: string; excerpt?: string | null; body?: string; status: 'draft' | 'published'; published_at?: string | null; author_name?: string | null; photos: Photo[] }

const props = defineProps<{ news: NewsItem[]; can_manage: boolean }>();
const page = usePage<{ flash?: { message?: string } }>();
const maxPhotoBytes = 2 * 1024 * 1024;
const showForm = ref(false);
const editingId = ref<number | null>(null);
const localPhotoError = ref('');
const searchTerm = ref('');
const statusFilter = ref<'all' | 'draft' | 'published'>('all');
const photoPreviews = ref<string[]>([]);
const form = useForm({ title: '', excerpt: '', body: '', status: 'draft' as 'draft' | 'published', published_at: '', photos: [] as File[], photo_captions: [] as string[] });
const editing = computed(() => editingId.value ? props.news.find((item) => item.id === editingId.value) : null);
const filteredNews = computed(() => {
    const query = searchTerm.value.trim().toLowerCase();

    return props.news.filter((item) => {
        const matchesStatus = statusFilter.value === 'all' || item.status === statusFilter.value;
        const matchesQuery = !query || [item.title, item.excerpt, item.body, item.author_name]
            .filter(Boolean)
            .some((value) => value!.toLowerCase().includes(query));

        return matchesStatus && matchesQuery;
    });
});
const publishedCount = computed(() => props.news.filter((item) => item.status === 'published').length);
const draftCount = computed(() => props.news.filter((item) => item.status === 'draft').length);
const serverPhotoError = computed(() => {
    const errors = form.errors as Record<string, string | undefined>;
    const message = errors.photos ?? Object.entries(errors).find(([key]) => key.startsWith('photos.'))?.[1];

    if (!message) return '';
    if (/failed to upload|greater than 2048|must not be greater/i.test(message)) {
        return 'Ukuran foto terlalu besar. Maksimal 2 MB per foto.';
    }

    return message;
});
const photoError = computed(() => localPhotoError.value || serverPhotoError.value);

const clearPhotoPreviews = () => {
    photoPreviews.value.forEach((url) => URL.revokeObjectURL(url));
    photoPreviews.value = [];
};
const reset = () => {
    showForm.value = false;
    editingId.value = null;
    localPhotoError.value = '';
    clearPhotoPreviews();
    form.reset();
    form.clearErrors();
};
const openCreate = () => {
    reset();
    showForm.value = true;
};
const edit = (item: NewsItem) => {
    editingId.value = item.id;
    form.title = item.title;
    form.excerpt = item.excerpt ?? '';
    form.body = item.body ?? '';
    form.status = item.status;
    form.published_at = item.published_at ? new Date(item.published_at).toISOString().slice(0, 16) : '';
    clearPhotoPreviews();
    form.photos = [];
    form.photo_captions = [];
    localPhotoError.value = '';
    form.clearErrors();
    showForm.value = true;
};
const selectPhotos = (event: Event) => {
    const maxNewPhotos = Math.max(0, 5 - (editing.value?.photos.length ?? 0));
    const selectedFiles = Array.from((event.target as HTMLInputElement).files ?? []);
    const selected = selectedFiles.slice(0, maxNewPhotos);
    const oversized = selected.filter((photo) => photo.size > maxPhotoBytes);

    clearPhotoPreviews();
    form.photos = selected.filter((photo) => photo.size <= maxPhotoBytes);
    form.photo_captions = form.photos.map(() => '');
    photoPreviews.value = form.photos.map((photo) => URL.createObjectURL(photo));
    localPhotoError.value = oversized.length
        ? `Foto ${oversized.length === 1 ? `"${oversized[0].name}"` : 'yang dipilih'} melebihi batas ukuran. Maksimal 2 MB per foto.`
        : selectedFiles.length > maxNewPhotos
            ? `Hanya ${maxNewPhotos} foto baru yang dapat ditambahkan pada berita ini.`
        : '';
    form.clearErrors('photos', 'photos.0', 'photos.1', 'photos.2', 'photos.3', 'photos.4');
};
const removeSelectedPhoto = (index: number) => {
    if (photoPreviews.value[index]) URL.revokeObjectURL(photoPreviews.value[index]);
    form.photos.splice(index, 1);
    form.photo_captions.splice(index, 1);
    photoPreviews.value.splice(index, 1);
};
const save = () => {
    const oversized = form.photos.filter((photo) => photo.size > maxPhotoBytes);
    if (oversized.length) {
        localPhotoError.value = `Foto ${oversized.length === 1 ? `"${oversized[0].name}"` : 'yang dipilih'} melebihi batas ukuran. Maksimal 2 MB per foto.`;
        return;
    }

    const url = editingId.value ? `/pengaturan/berita/${editingId.value}` : '/pengaturan/berita';
    form.transform((data) => ({ ...data, _method: editingId.value ? 'put' : undefined }))
        .post(url, { forceFormData: true, preserveScroll: true, onSuccess: reset });
};
const remove = (item: NewsItem) => {
    if (window.confirm(`Hapus berita “${item.title}”?`)) router.delete(`/pengaturan/berita/${item.id}`, { preserveScroll: true });
};
const removePhoto = (photo: Photo) => {
    if (!editingId.value || !window.confirm('Hapus foto ini dari berita?')) return;
    router.delete(`/pengaturan/berita/${editingId.value}/foto/${photo.id}`, { preserveScroll: true });
};
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'Belum terbit';
</script>

<template>
    <Head title="Berita Sekolah" />
    <AppLayout title="Berita Sekolah">
        <main class="view news-page">
            <section class="hero-card"><div><p class="eyebrow">Website sekolah</p><h1>Berita sekolah</h1><p>Tulis informasi kegiatan dan pencapaian sekolah untuk ditampilkan pada halaman publik.</p></div><AppButton variant="primary" v-if="can_manage" type="button" class="primary-button" @click="openCreate">+ Buat berita</AppButton></section>
            <div v-if="page.props.flash?.message" class="notice success" role="status">{{ page.props.flash.message }}</div>

            <div v-if="showForm" role="dialog" aria-modal="true" aria-labelledby="news-modal-title" class="modal-backdrop" @click.self="reset" @keydown.esc="reset">
                <form class="modal-card news-editor" @submit.prevent="save">
                    <div class="section-heading"><div><p class="eyebrow">{{ editing ? 'Edit berita' : 'Berita baru' }}</p><h2 id="news-modal-title">{{ editing ? editing.title : 'Buat berita sekolah' }}</h2><p>Berita dapat disimpan sebagai draf atau langsung diterbitkan.</p></div><AppButton variant="ghost" type="button" class="modal-close" aria-label="Tutup formulir berita" @click="reset">×</AppButton></div>
                <label><span>Judul berita <small class="char-count">{{ form.title.length }}/180</small></span><input v-model="form.title" required maxlength="180" placeholder="Contoh: Siswa meraih juara lomba sains" /><em v-if="form.errors.title">{{ form.errors.title }}</em></label>
                <label><span>Ringkasan <small class="char-count">{{ form.excerpt.length }}/500</small></span><textarea v-model="form.excerpt" rows="2" maxlength="500" placeholder="Ringkasan singkat yang tampil di kartu berita."></textarea><em v-if="form.errors.excerpt">{{ form.errors.excerpt }}</em></label>
                <label><span>Isi berita <small class="char-count">{{ form.body.length }}/20.000</small></span><textarea v-model="form.body" required rows="8" maxlength="20000" placeholder="Tuliskan isi berita secara lengkap."></textarea><em v-if="form.errors.body">{{ form.errors.body }}</em></label>
                <div class="form-grid"><label>Status <select v-model="form.status"><option value="draft">Draf</option><option value="published">Terbitkan</option></select><em v-if="form.errors.status">{{ form.errors.status }}</em></label><label v-if="form.status === 'published'">Waktu terbit <input v-model="form.published_at" type="datetime-local" /><em v-if="form.errors.published_at">{{ form.errors.published_at }}</em></label></div>
                <div class="photo-field"><span class="field-label">Foto berita</span><span class="photo-picker"><input id="news-photos" class="sr-only-file-input" type="file" accept="image/jpeg,image/png,image/webp" multiple @change="selectPhotos" /><label class="photo-picker-button" for="news-photos">Pilih foto</label><span class="photo-picker-name">{{ form.photos.length ? `${form.photos.length} foto dipilih` : 'Belum ada foto dipilih' }}</span></span><small>Pilih sampai {{ Math.max(0, 5 - (editing?.photos.length ?? 0)) }} foto baru; maksimal 5 foto total per berita dan 2 MB per foto.</small><em v-if="photoError" role="alert">{{ photoError }}</em><em v-if="form.errors.photo_captions" role="alert">{{ form.errors.photo_captions }}</em></div>
                <div v-if="form.photos.length" class="selected-photos"><span class="field-label">Foto baru dan keterangannya</span><div class="photo-grid"><figure v-for="(photo, index) in form.photos" :key="`${photo.name}-${index}`"><img :src="photoPreviews[index]" :alt="photo.name" /><figcaption><strong>{{ photo.name }}</strong><input v-model="form.photo_captions[index]" maxlength="255" placeholder="Keterangan foto (opsional)" /><AppButton variant="danger" size="sm" type="button" class="text-button danger" @click="removeSelectedPhoto(index)">Batalkan</AppButton></figcaption></figure></div></div>
                <div v-if="editing?.photos.length" class="existing-photos"><span class="field-label">Foto tersimpan</span><div class="photo-grid"><figure v-for="photo in editing.photos" :key="photo.id"><img :src="photo.url" :alt="photo.caption || photo.name || 'Foto berita'" /><figcaption><strong>{{ photo.name || 'Foto berita' }}</strong><small v-if="photo.caption">{{ photo.caption }}</small><AppButton variant="danger" size="sm" type="button" class="text-button danger" @click="removePhoto(photo)">Hapus</AppButton></figcaption></figure></div></div>
                <div class="form-actions"><AppButton variant="secondary"  type="button" class="secondary-button" :disabled="form.processing" @click="reset">Batal</AppButton><AppButton variant="primary" type="submit" class="primary-button" :disabled="form.processing || !form.title || !form.body">{{ form.processing ? 'Menyimpan…' : editing ? 'Simpan perubahan' : 'Simpan berita' }}</AppButton></div>
                </form>
            </div>

            <section class="panel news-list"><div class="section-heading"><div><h2>Daftar berita</h2><p>{{ filteredNews.length }} dari {{ news.length }} berita ditampilkan.</p></div><AppButton variant="secondary" as="a" class="secondary-button" href="/berita" target="_blank" rel="noreferrer">Lihat halaman publik</AppButton></div><div class="news-summary"><div><span>Terbit</span><strong>{{ publishedCount }}</strong></div><div><span>Draf</span><strong>{{ draftCount }}</strong></div><div><span>Total foto</span><strong>{{ news.reduce((total, item) => total + item.photos.length, 0) }}</strong></div></div><div class="news-toolbar"><label class="search-field search-control-shell"><span aria-hidden="true">⌕</span><input v-model="searchTerm" type="search" placeholder="Cari judul atau isi berita" /></label><select v-model="statusFilter" aria-label="Filter status berita"><option value="all">Semua status</option><option value="published">Terbit</option><option value="draft">Draf</option></select></div><article v-for="item in filteredNews" :key="item.id" class="news-row"><div class="news-thumb"><img v-if="item.photos[0]" :src="item.photos[0].url" :alt="item.title" /><span v-else>SS</span></div><div class="news-copy"><div class="news-meta"><span :class="['status-badge', item.status]">{{ item.status === 'published' ? 'Terbit' : 'Draf' }}</span><span>{{ formatDate(item.published_at) }}</span></div><h3>{{ item.title }}</h3><p>{{ item.excerpt || item.body }}</p><small>{{ item.author_name || 'Sekolah' }} · {{ item.photos.length }} foto</small></div><div class="row-actions"><AppButton variant="secondary" size="sm" icon-only icon="edit" type="button" class="secondary-button row-action-icon" aria-label="Edit berita" title="Edit berita" @click="edit(item)"></AppButton><AppButton variant="danger" size="sm" icon-only icon="delete" type="button" class="text-button danger row-action-icon row-action-delete" aria-label="Hapus berita" title="Hapus berita" @click="remove(item)"></AppButton></div></article><div v-if="!filteredNews.length" class="empty-state"><strong>{{ news.length ? 'Berita tidak ditemukan' : 'Belum ada berita' }}</strong><p>{{ news.length ? 'Coba ubah kata kunci atau filter status.' : 'Berita sekolah yang diterbitkan akan tampil di halaman publik.' }}</p></div></section>
        </main>
    </AppLayout>
</template>

<style scoped>
.news-page,.news-editor,.news-list{display:grid;gap:1.25rem;min-width:0}.news-page{max-width:1100px;margin:auto}.news-editor,.news-list{padding:1.25rem}.section-heading,.form-actions,.row-actions,.news-meta{display:flex;align-items:center;justify-content:space-between;gap:.75rem}.section-heading h2{margin:0;font-size:1.05rem}.section-heading p{margin:.35rem 0 0;color:var(--ink-soft);font-size:var(--type-meta);line-height:1.5}.news-editor>label{display:grid;gap:.45rem;color:var(--ink-soft);font-size:var(--type-meta);font-weight:var(--weight-semibold)}.news-editor input,.news-editor select,.news-editor textarea{width:100%;padding:.7rem .8rem;color:var(--ink);background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem;font:inherit;font-weight:var(--weight-regular)}.news-editor textarea{resize:vertical;line-height:1.6}.news-editor em{color:var(--red);font-size:var(--type-meta);font-style:normal}.news-editor small{font-weight:var(--weight-regular);font-size:var(--type-meta);line-height:1.5}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.photo-selection{font-size:var(--type-control);font-weight:var(--weight-medium);color:var(--primary)}.existing-photos,.photo-grid{display:grid;gap:.6rem}.field-label{font-size:var(--type-meta);font-weight:var(--weight-semibold)}.photo-grid{grid-template-columns:repeat(auto-fill,minmax(130px,1fr))}.photo-grid figure{margin:0;overflow:hidden;border:1px solid var(--line);border-radius:.7rem;background:var(--surface-subtle)}.photo-grid img{display:block;width:100%;height:100px;object-fit:cover}.photo-grid figcaption{display:flex;align-items:center;justify-content:space-between;gap:.4rem;padding:.45rem;font-size:var(--type-meta)}.photo-grid figcaption span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.news-list{gap:0}.news-list>.section-heading{padding-bottom:1rem}.news-row{display:flex;align-items:flex-start;gap:1rem;padding:1rem 0;border-top:1px solid var(--line)}.news-thumb{width:5rem;height:4rem;display:grid;place-items:center;flex:none;overflow:hidden;border-radius:.7rem;background:var(--primary-soft);color:var(--primary);font-weight:var(--weight-bold)}.news-thumb img{width:100%;height:100%;object-fit:cover}.news-copy{min-width:0;flex:1}.news-meta{justify-content:flex-start;color:var(--ink-faint);font-size:var(--type-meta)}.status-badge{padding:.25rem .5rem;border-radius:999px;font-weight:var(--weight-semibold)}.status-badge.published{background:var(--green-soft);color:var(--green)}.status-badge.draft{background:var(--gold-soft);color:var(--gold)}.news-copy h3{margin:.35rem 0;color:var(--ink);font-size:var(--type-md)}.news-copy p{display:-webkit-box;margin:0;color:var(--ink-soft);font-size:var(--type-meta);line-height:1.5;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2}.news-copy small{display:block;margin-top:.5rem;color:var(--ink-faint);font-size:var(--type-meta)}.empty-state{padding:3rem 1rem;text-align:center;color:var(--ink-soft)}.empty-state strong{display:block;color:var(--ink)}.notice{padding:1rem;border:1px solid #cbd5e1;border-radius:.75rem;font-size:var(--type-sm)}.notice.success{background:#ecfdf5;color:#166534}@media(max-width:700px){.form-grid{grid-template-columns:1fr}.news-row{flex-wrap:wrap}.row-actions{width:100%;justify-content:flex-end}.section-heading{align-items:flex-start;flex-direction:column}.section-heading>a,.section-heading>button{align-self:stretch;text-align:center}}
.photo-field{display:grid;gap:.45rem;color:var(--ink-soft);font-size:var(--type-meta);font-weight:var(--weight-bold)}.photo-picker{display:flex;align-items:center;gap:.65rem;width:100%;padding:.25rem;background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem}.sr-only-file-input{position:absolute;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0!important}.photo-picker-button{display:inline-flex;align-items:center;justify-content:center;flex:none;padding:.55rem .8rem;background:var(--primary-action);border:0;border-radius:.5rem;color:#fff;cursor:pointer;font:inherit;font-size:var(--type-control);font-weight:var(--weight-semibold);line-height:1.2}.photo-picker-button:hover{filter:brightness(.96)}.photo-picker-name{min-width:0;overflow:hidden;color:var(--ink-soft);font-size:var(--type-meta);font-weight:var(--weight-medium);text-overflow:ellipsis;white-space:nowrap}.photo-picker:focus-within{outline:2px solid var(--primary);outline-offset:2px}
.char-count{float:right;color:var(--ink-faint);font-weight:var(--weight-medium)}.selected-photos{display:grid;gap:.6rem}.selected-photos .photo-grid figure{display:grid;grid-template-rows:140px 1fr}.selected-photos .photo-grid img{height:140px}.photo-grid figcaption{align-items:stretch;flex-direction:column}.photo-grid figcaption strong{overflow:hidden;color:var(--ink);font-size:var(--type-meta);text-overflow:ellipsis;white-space:nowrap}.photo-grid figcaption input{width:100%;padding:.45rem .5rem;color:var(--ink);background:var(--surface);border:1px solid var(--line);border-radius:.45rem;font:inherit;font-size:var(--type-control)}.photo-grid figcaption small{color:var(--ink-soft);font-size:var(--type-meta);line-height:1.4}.photo-grid figcaption .text-button{align-self:flex-start}.news-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:.65rem;padding:0 0 1rem}.news-summary div{display:grid;gap:.25rem;padding:.75rem .9rem;background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem}.news-summary span{color:var(--ink-faint);font-size:var(--type-meta)}.news-summary strong{color:var(--ink);font-size:1.35rem;line-height:1}.news-toolbar{display:flex;align-items:center;gap:.65rem;padding:0 0 1rem}.search-field{display:flex!important;align-items:center;gap:.45rem;min-height:38px;flex:1;padding:0 .7rem!important;background:var(--surface-subtle);border:1px solid var(--line);border-radius:.6rem}.search-field span{color:var(--ink-faint);font-size:1.15rem}.search-field input{min-width:0;width:100%;padding:0!important;background:transparent!important;border:0!important;outline:0;font-size:var(--type-control)}.news-toolbar>select{min-height:38px;padding:0 .7rem;color:var(--ink);background:var(--surface-subtle);border:1px solid var(--line);border-radius:.6rem;font:inherit;font-size:var(--type-control)}@media(max-width:700px){.news-toolbar{align-items:stretch;flex-direction:column}.news-summary{grid-template-columns:repeat(3,1fr)}.news-toolbar>select{width:100%}}@media(max-width:460px){.news-summary{gap:.4rem}.news-summary div{padding:.65rem .55rem}.news-summary strong{font-size:1.1rem}}
.row-actions{align-items:center;gap:.45rem;flex:0 0 auto}
.news-page .hero-card { background: var(--surface); border-radius: 14px; box-shadow: none; }
.news-page .news-editor, .news-page .news-list { padding: clamp(1rem, 2.5vw, 1.5rem); border-radius: 14px; box-shadow: none; }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .56); backdrop-filter: blur(3px); }
.modal-card { width: min(720px, 100%); max-height: 90dvh; overflow-y: auto; border: 1px solid var(--line); background: var(--surface); box-shadow: 0 24px 80px rgb(15 23 42 / .25) !important; }
.news-editor input, .news-editor select, .news-editor textarea, .news-toolbar > select, .search-field { min-height: 44px; background: var(--surface); border-color: var(--line-strong); }
.news-editor input:focus-visible, .news-editor select:focus-visible, .news-editor textarea:focus-visible, .news-toolbar select:focus-visible, .search-field:focus-within, .news-page button:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
.news-page .news-summary div { background: var(--surface); border-radius: 10px; }
@media (max-width: 600px) { .news-page .form-actions { align-items: stretch; flex-direction: column-reverse; } .news-page .form-actions > * { width: 100%; } .news-page .row-actions { justify-content: flex-start; } }
</style>
