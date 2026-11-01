<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface Photo { id: number; name?: string | null; caption?: string | null; url: string }
interface NewsItem { id: number; title: string; slug: string; excerpt?: string | null; body?: string; status: 'draft' | 'published'; published_at?: string | null; author_name?: string | null; photos: Photo[] }

const props = defineProps<{ news: NewsItem[]; can_manage: boolean }>();
const page = usePage<{ flash?: { message?: string } }>();
const maxPhotoBytes = 2 * 1024 * 1024;
const editingId = ref<number | null>(null);
const localPhotoError = ref('');
const form = useForm({ title: '', excerpt: '', body: '', status: 'draft' as 'draft' | 'published', published_at: '', photos: [] as File[] });
const editing = computed(() => editingId.value ? props.news.find((item) => item.id === editingId.value) : null);
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

const reset = () => { editingId.value = null; localPhotoError.value = ''; form.reset(); form.clearErrors(); };
const edit = (item: NewsItem) => {
    editingId.value = item.id;
    form.title = item.title;
    form.excerpt = item.excerpt ?? '';
    form.body = item.body ?? '';
    form.status = item.status;
    form.published_at = item.published_at ? new Date(item.published_at).toISOString().slice(0, 16) : '';
    form.photos = [];
    localPhotoError.value = '';
    form.clearErrors();
    window.scrollTo({ top: 0, behavior: 'smooth' });
};
const selectPhotos = (event: Event) => {
    const maxNewPhotos = Math.max(0, 5 - (editing.value?.photos.length ?? 0));
    const selected = Array.from((event.target as HTMLInputElement).files ?? []).slice(0, maxNewPhotos);
    const oversized = selected.filter((photo) => photo.size > maxPhotoBytes);

    form.photos = selected.filter((photo) => photo.size <= maxPhotoBytes);
    localPhotoError.value = oversized.length
        ? `Foto ${oversized.length === 1 ? `"${oversized[0].name}"` : 'yang dipilih'} melebihi batas ukuran. Maksimal 2 MB per foto.`
        : '';
    form.clearErrors('photos', 'photos.0', 'photos.1', 'photos.2', 'photos.3', 'photos.4');
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
            <section class="hero-card"><div><p class="eyebrow">Website sekolah</p><h1>Berita sekolah</h1><p>Tulis informasi kegiatan dan pencapaian sekolah untuk ditampilkan pada halaman publik.</p></div></section>
            <div v-if="page.props.flash?.message" class="notice success" role="status">{{ page.props.flash.message }}</div>

            <form class="panel news-editor" @submit.prevent="save">
                <div class="section-heading"><div><p class="eyebrow">{{ editing ? 'Edit berita' : 'Berita baru' }}</p><h2>{{ editing ? editing.title : 'Buat berita sekolah' }}</h2><p>Berita dapat disimpan sebagai draf atau langsung diterbitkan.</p></div><button v-if="editing" type="button" class="secondary-button" @click="reset">Berita baru</button></div>
                <label>Judul berita <input v-model="form.title" required maxlength="180" placeholder="Contoh: Siswa meraih juara lomba sains" /><em v-if="form.errors.title">{{ form.errors.title }}</em></label>
                <label>Ringkasan <textarea v-model="form.excerpt" rows="2" maxlength="500" placeholder="Ringkasan singkat yang tampil di kartu berita."></textarea><em v-if="form.errors.excerpt">{{ form.errors.excerpt }}</em></label>
                <label>Isi berita <textarea v-model="form.body" required rows="8" maxlength="20000" placeholder="Tuliskan isi berita secara lengkap."></textarea><em v-if="form.errors.body">{{ form.errors.body }}</em></label>
                <div class="form-grid"><label>Status <select v-model="form.status"><option value="draft">Draf</option><option value="published">Terbitkan</option></select><em v-if="form.errors.status">{{ form.errors.status }}</em></label><label v-if="form.status === 'published'">Waktu terbit <input v-model="form.published_at" type="datetime-local" /><em v-if="form.errors.published_at">{{ form.errors.published_at }}</em></label></div>
                <div class="photo-field"><span class="field-label">Foto berita</span><span class="photo-picker"><input id="news-photos" class="sr-only-file-input" type="file" accept="image/jpeg,image/png,image/webp" multiple @change="selectPhotos" /><label class="photo-picker-button" for="news-photos">Pilih foto</label><span class="photo-picker-name">{{ form.photos.length ? `${form.photos.length} foto dipilih` : 'Belum ada foto dipilih' }}</span></span><small>Pilih sampai {{ Math.max(0, 5 - (editing?.photos.length ?? 0)) }} foto baru; maksimal 5 foto total per berita dan 2 MB per foto.</small><span v-if="form.photos.length" class="photo-selection">{{ form.photos.map((photo) => photo.name).join(', ') }}</span><em v-if="photoError" role="alert">{{ photoError }}</em></div>
                <div v-if="editing?.photos.length" class="existing-photos"><span class="field-label">Foto tersimpan</span><div class="photo-grid"><figure v-for="photo in editing.photos" :key="photo.id"><img :src="photo.url" :alt="photo.caption || photo.name || 'Foto berita'" /><figcaption><span>{{ photo.name || 'Foto berita' }}</span><button type="button" class="text-button danger" @click="removePhoto(photo)">Hapus</button></figcaption></figure></div></div>
                <div class="form-actions"><button type="button" class="secondary-button" :disabled="form.processing" @click="reset">Batal</button><button class="primary-button" :disabled="form.processing || !form.title || !form.body">{{ form.processing ? 'Menyimpan…' : editing ? 'Simpan perubahan' : 'Simpan berita' }}</button></div>
            </form>

            <section class="panel news-list"><div class="section-heading"><div><h2>Daftar berita</h2><p>{{ news.length }} berita tersimpan di sekolah ini.</p></div><a class="secondary-button" href="/berita" target="_blank" rel="noreferrer">Lihat halaman publik</a></div><article v-for="item in news" :key="item.id" class="news-row"><div class="news-thumb"><img v-if="item.photos[0]" :src="item.photos[0].url" :alt="item.title" /><span v-else>SS</span></div><div class="news-copy"><div class="news-meta"><span :class="['status-badge', item.status]">{{ item.status === 'published' ? 'Terbit' : 'Draf' }}</span><span>{{ formatDate(item.published_at) }}</span></div><h3>{{ item.title }}</h3><p>{{ item.excerpt || item.body }}</p><small>{{ item.author_name || 'Sekolah' }} · {{ item.photos.length }} foto</small></div><div class="row-actions"><button type="button" class="secondary-button" @click="edit(item)">Edit</button><button type="button" class="text-button danger" @click="remove(item)">Hapus</button></div></article><div v-if="!news.length" class="empty-state"><strong>Belum ada berita</strong><p>Berita sekolah yang diterbitkan akan tampil di halaman publik.</p></div></section>
        </main>
    </AppLayout>
</template>

<style scoped>
.news-page,.news-editor,.news-list{display:grid;gap:1.25rem;min-width:0}.news-page{max-width:1100px;margin:auto}.news-editor,.news-list{padding:1.25rem}.section-heading,.form-actions,.row-actions,.news-meta{display:flex;align-items:center;justify-content:space-between;gap:.75rem}.section-heading h2{margin:0;font-size:1.05rem}.section-heading p{margin:.35rem 0 0;color:var(--ink-soft);font-size:.8rem;line-height:1.5}.news-editor>label{display:grid;gap:.45rem;color:var(--ink-soft);font-size:.8rem;font-weight:700}.news-editor input,.news-editor select,.news-editor textarea{width:100%;padding:.7rem .8rem;color:var(--ink);background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem;font:inherit;font-weight:400}.news-editor textarea{resize:vertical;line-height:1.6}.news-editor em{color:var(--red);font-size:.75rem;font-style:normal}.news-editor small{font-weight:400;font-size:.72rem;line-height:1.5}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.photo-selection{font-size:.75rem;font-weight:500;color:var(--primary)}.existing-photos,.photo-grid{display:grid;gap:.6rem}.field-label{font-size:.8rem;font-weight:700}.photo-grid{grid-template-columns:repeat(auto-fill,minmax(130px,1fr))}.photo-grid figure{margin:0;overflow:hidden;border:1px solid var(--line);border-radius:.7rem;background:var(--surface-subtle)}.photo-grid img{display:block;width:100%;height:100px;object-fit:cover}.photo-grid figcaption{display:flex;align-items:center;justify-content:space-between;gap:.4rem;padding:.45rem;font-size:.68rem}.photo-grid figcaption span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.news-list{gap:0}.news-list>.section-heading{padding-bottom:1rem}.news-row{display:flex;align-items:flex-start;gap:1rem;padding:1rem 0;border-top:1px solid var(--line)}.news-thumb{width:5rem;height:4rem;display:grid;place-items:center;flex:none;overflow:hidden;border-radius:.7rem;background:var(--primary-soft);color:var(--primary);font-weight:800}.news-thumb img{width:100%;height:100%;object-fit:cover}.news-copy{min-width:0;flex:1}.news-meta{justify-content:flex-start;color:var(--ink-faint);font-size:.72rem}.status-badge{padding:.25rem .5rem;border-radius:999px;font-weight:700}.status-badge.published{background:var(--green-soft);color:var(--green)}.status-badge.draft{background:var(--gold-soft);color:var(--gold)}.news-copy h3{margin:.35rem 0;color:var(--ink);font-size:.95rem}.news-copy p{display:-webkit-box;margin:0;color:var(--ink-soft);font-size:.8rem;line-height:1.5;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2}.news-copy small{display:block;margin-top:.5rem;color:var(--ink-faint);font-size:.7rem}.danger{color:var(--red)}.empty-state{padding:3rem 1rem;text-align:center;color:var(--ink-soft)}.empty-state strong{display:block;color:var(--ink)}.notice{padding:1rem;border:1px solid #cbd5e1;border-radius:.75rem;font-size:.85rem}.notice.success{background:#ecfdf5;color:#166534}.text-button{font-size:.75rem;font-weight:700}@media(max-width:700px){.form-grid{grid-template-columns:1fr}.news-row{flex-wrap:wrap}.row-actions{width:100%;justify-content:flex-end}.section-heading{align-items:flex-start;flex-direction:column}.section-heading>a,.section-heading>button{align-self:stretch;text-align:center}}
.photo-field{display:grid;gap:.45rem;color:var(--ink-soft);font-size:.8rem;font-weight:700}.photo-picker{display:flex;align-items:center;gap:.65rem;width:100%;padding:.25rem;background:var(--surface-subtle);border:1px solid var(--line);border-radius:.7rem}.sr-only-file-input{position:absolute;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0!important}.photo-picker-button{display:inline-flex;align-items:center;justify-content:center;flex:none;padding:.55rem .8rem;background:var(--primary);border-radius:.5rem;color:var(--on-primary);cursor:pointer;font-size:.78rem;font-weight:700}.photo-picker-button:hover{filter:brightness(.96)}.photo-picker-name{min-width:0;overflow:hidden;color:var(--ink-soft);font-size:.78rem;font-weight:500;text-overflow:ellipsis;white-space:nowrap}.photo-picker:focus-within{outline:2px solid var(--primary);outline-offset:2px}
</style>
