<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface DownloadItem {
    id: number;
    category: string;
    category_label: string;
    title: string;
    description?: string | null;
    version: number;
    original_name: string;
    mime_type: string;
    size_bytes: number;
    is_published: boolean;
    download_count: number;
    updated_at?: string | null;
    download_url?: string | null;
}

const props = defineProps<{
    downloads: DownloadItem[];
    categories: Record<string, string>;
    can_manage: boolean;
}>();

const page = usePage<{ flash?: { message?: string } }>();
const showModal = ref(false);
const discardChanges = ref(false);
const editing = ref<DownloadItem | null>(null);
const pendingDelete = ref<DownloadItem | null>(null);
const deleting = ref(false);
const searchTerm = ref('');
const categoryFilter = ref('');

const form = useForm({
    category: 'formulir',
    title: '',
    description: '',
    version: 1,
    is_published: true,
    file: null as File | null,
});

const filteredDownloads = computed(() => {
    const q = searchTerm.value.trim().toLowerCase();
    return props.downloads.filter((doc) => {
        const matchesCat = !categoryFilter.value || doc.category === categoryFilter.value;
        const matchesQuery = !q || [doc.title, doc.description, doc.original_name, doc.category_label]
            .filter(Boolean)
            .some((val) => val!.toLowerCase().includes(q));
        return matchesCat && matchesQuery;
    });
});

const initialForm = JSON.parse(JSON.stringify(form.data()));
const resetForm = () => {
    showModal.value = false;
    editing.value = null;
    form.defaults(structuredClone(initialForm));
    form.reset();
    form.clearErrors();
};

const requestClose = () => {
    if (form.processing) return;
    if (form.isDirty) { discardChanges.value = true; return; }
    resetForm();
};

const openCreate = () => {
    resetForm();
    showModal.value = true;
};

const openEdit = (doc: DownloadItem) => {
    editing.value = doc;
    form.category = doc.category;
    form.title = doc.title;
    form.description = doc.description ?? '';
    form.version = doc.version;
    form.is_published = doc.is_published;
    form.file = null;
    form.defaults();
    form.clearErrors();
    showModal.value = true;
};

const onFileChange = (event: Event) => {
    form.file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.clearErrors('file');
};

const save = () => {
    const endpoint = editing.value ? `/pengaturan/pusat-unduh/${editing.value.id}` : '/pengaturan/pusat-unduh';
    form.transform((data) => editing.value ? { ...data, _method: 'put' } : data).post(endpoint, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: resetForm,
    });
};

const togglePublish = (doc: DownloadItem) => {
    router.post(`/pengaturan/pusat-unduh/${doc.id}/publikasi`, {}, { preserveScroll: true });
};

const confirmDelete = () => {
    if (!pendingDelete.value) return;
    deleting.value = true;
    router.delete(`/pengaturan/pusat-unduh/${pendingDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            pendingDelete.value = null;
        },
    });
};

const formatSize = (bytes: number) => `${Math.max(1, Math.round(bytes / 1024))} KB`;
</script>

<template>
    <Head title="Pusat Unduh" />
    <AppLayout title="Pusat Unduh">
        <main class="view downloads-admin-page">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Publikasi dokumen</p>
                    <h1>Pusat unduh dokumen resmi</h1>
                    <p>Kelola formulir, kalender akademik, tata tertib, brosur, dan SOP layanan untuk halaman `/pusat-unduh`.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <AppButton variant="secondary" as="a" href="/pusat-unduh" target="_blank" rel="noreferrer">Lihat halaman publik</AppButton>
                    <AppButton v-if="can_manage" variant="primary" icon="plus" type="button" @click="openCreate">Unggah dokumen</AppButton>
                </div>
            </section>

            <div v-if="page.props.flash?.message" class="notice success" role="status">{{ page.props.flash.message }}</div>

            <section class="panel downloads-panel">
                <div class="toolbar">
                    <input v-model="searchTerm" type="search" placeholder="Cari judul atau nama berkas..." aria-label="Cari dokumen" />
                    <select v-model="categoryFilter" aria-label="Filter kategori dokumen">
                        <option value="">Semua kategori</option>
                        <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>

                <div class="table-scroll">
                    <table v-if="filteredDownloads.length" class="admin-table">
                        <thead>
                            <tr>
                                <th class="text-center">Judul & Berkas</th>
                                <th class="text-center">Kategori</th>
                                <th class="text-center">Versi & Ukuran</th>
                                <th class="text-center">Unduhan</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="doc in filteredDownloads" :key="doc.id">
                                <td>
                                    <strong>{{ doc.title }}</strong>
                                    <small class="block text-xs text-[var(--ink-faint)]">{{ doc.original_name }}</small>
                                    <p v-if="doc.description" class="text-xs text-[var(--ink-soft)] mt-0.5">{{ doc.description }}</p>
                                </td>
                                <td class="text-center">{{ doc.category_label }}</td>
                                <td class="text-center">v{{ doc.version }} · {{ formatSize(doc.size_bytes) }}</td>
                                <td class="text-center">{{ doc.download_count }}</td>
                                <td class="text-center">
                                    <span :class="['status-pill', doc.is_published ? 'published' : 'draft']">
                                        {{ doc.is_published ? 'Terbit' : 'Draf' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="row-actions">
                                        <AppButton
                                            variant="secondary"
                                            size="sm"
                                            icon-only
                                            :icon="doc.is_published ? 'archive' : 'upload'"
                                            type="button"
                                            :aria-label="doc.is_published ? 'Sembunyikan dokumen' : 'Terbitkan dokumen'"
                                            :title="doc.is_published ? 'Sembunyikan dokumen' : 'Terbitkan dokumen'"
                                            @click="togglePublish(doc)"
                                        />
                                        <AppButton
                                            variant="secondary"
                                            size="sm"
                                            icon-only
                                            icon="edit"
                                            type="button"
                                            aria-label="Edit dokumen"
                                            title="Edit dokumen"
                                            @click="openEdit(doc)"
                                        />
                                        <AppButton
                                            variant="danger"
                                            size="sm"
                                            icon-only
                                            icon="delete"
                                            type="button"
                                            aria-label="Hapus dokumen"
                                            title="Hapus dokumen"
                                            @click="pendingDelete = doc"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="empty-state">
                        <strong>Belum ada dokumen pusat unduh</strong>
                        <p>Unggah berkas resmi agar pengunjung dapat mengunduhnya dari halaman Pusat Unduh.</p>
                    </div>
                </div>
            </section>

            <div v-if="showModal" role="dialog" aria-modal="true" aria-labelledby="download-modal-title" class="modal-backdrop" @click.self="requestClose" @keydown.esc="requestClose">
                <form class="modal-card" @submit.prevent="save">
                    <div class="modal-head">
                        <div>
                            <p class="eyebrow">{{ editing ? 'Edit dokumen' : 'Dokumen baru' }}</p>
                            <h2 id="download-modal-title">{{ editing ? editing.title : 'Unggah dokumen pusat unduh' }}</h2>
                        </div>
                        <AppButton variant="ghost" type="button" aria-label="Tutup modal" @click="requestClose">×</AppButton>
                    </div>

                    <div class="form-grid">
                        <label class="wide">
                            <span>Judul dokumen *</span>
                            <input v-model="form.title" maxlength="180" required placeholder="Kalender Akademik 2026/2027" />
                            <em v-if="form.errors.title">{{ form.errors.title }}</em>
                        </label>
                        <label>
                            <span>Kategori *</span>
                            <select v-model="form.category" required>
                                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <em v-if="form.errors.category">{{ form.errors.category }}</em>
                        </label>
                        <label>
                            <span>Nomor versi</span>
                            <input v-model.number="form.version" type="number" min="1" required />
                            <em v-if="form.errors.version">{{ form.errors.version }}</em>
                        </label>
                        <label class="wide">
                            <span>Deskripsi singkat</span>
                            <textarea v-model="form.description" rows="2" maxlength="500" placeholder="Keterangan isi atau peruntukan dokumen"></textarea>
                            <em v-if="form.errors.description">{{ form.errors.description }}</em>
                        </label>
                        <label class="wide">
                            <span>Berkas dokumen {{ editing ? '(opsional jika tidak diganti)' : '*' }}</span>
                            <input type="file" :required="!editing" @change="onFileChange" />
                            <small>Format PDF, gambar, atau dokumen resmi; ukuran maksimal 10 MB.</small>
                            <em v-if="form.errors.file">{{ form.errors.file }}</em>
                        </label>
                        <label class="wide checkbox-row">
                            <input v-model="form.is_published" type="checkbox" />
                            <span>Terbitkan langsung di halaman Pusat Unduh publik</span>
                        </label>
                    </div>

                    <div class="modal-actions">
                        <AppButton variant="secondary" type="button" :disabled="form.processing" @click="requestClose">Batal</AppButton>
                        <AppButton variant="primary" type="submit" :loading="form.processing" :disabled="form.processing">
                            {{ editing ? 'Simpan perubahan' : 'Unggah dokumen' }}
                        </AppButton>
                    </div>
                </form>
            </div>

            <ConfirmDialog :open="discardChanges" title="Buang perubahan dokumen?" message="Perubahan yang belum disimpan akan hilang." confirm-label="Buang perubahan" @confirm="discardChanges = false; resetForm()" @cancel="discardChanges = false" />

            <ConfirmDialog
                :open="pendingDelete !== null"
                title="Hapus dokumen pusat unduh?"
                :message="pendingDelete ? `Hapus dokumen &quot;${pendingDelete.title}&quot; beserta berkasnya dari Pusat Unduh?` : ''"
                confirm-label="Ya, hapus dokumen"
                variant="danger"
                :loading="deleting"
                @confirm="confirmDelete"
                @cancel="pendingDelete = null"
            />
        </main>
    </AppLayout>
</template>

<style scoped>
.downloads-admin-page { display: grid; gap: 1.25rem; max-width: 1100px; margin: auto; }
.hero-card, .downloads-panel { padding: 1.25rem; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; }
.hero-card { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.toolbar { display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: 1rem; }
.toolbar input, .toolbar select { min-height: 42px; padding: .55rem .8rem; border: 1px solid var(--line-strong); border-radius: 10px; background: var(--surface); color: var(--ink); font: inherit; }
.toolbar input { flex: 1; min-width: 220px; }
.table-scroll { overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; font-size: var(--type-sm); }
.admin-table th, .admin-table td { padding: .75rem; border-bottom: 1px solid var(--line); vertical-align: middle; }
.admin-table th { font-weight: 700; text-align: center; background: var(--surface-subtle); }
.row-actions { display: inline-flex; align-items: center; justify-content: flex-end; gap: .4rem; }
.status-pill { display: inline-block; padding: .2rem .55rem; border-radius: 999px; font-size: var(--type-meta); font-weight: 700; }
.status-pill.published { background: var(--green-soft); color: var(--green); }
.status-pill.draft { background: var(--gold-soft); color: var(--gold); }
.empty-state { padding: 2.5rem 1rem; text-align: center; color: var(--ink-soft); }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .56); backdrop-filter: blur(3px); }
.modal-card { width: min(640px, 100%); max-height: 90dvh; overflow-y: auto; display: grid; gap: 1rem; padding: 1.35rem; background: var(--surface); border: 1px solid var(--line); border-radius: 16px; }
.modal-head, .modal-actions { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
.modal-actions { justify-content: flex-end; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
.form-grid label { display: grid; gap: .35rem; font-size: var(--type-meta); font-weight: 600; color: var(--ink-soft); }
.form-grid label.wide { grid-column: 1 / -1; }
.form-grid input:not([type=checkbox]), .form-grid select, .form-grid textarea { min-height: 42px; padding: .6rem .75rem; border: 1px solid var(--line-strong); border-radius: 10px; background: var(--surface); color: var(--ink); font: inherit; }
.checkbox-row { display: flex !important; align-items: center; gap: .55rem; }
.notice.success { padding: .85rem 1rem; border-radius: 10px; background: var(--green-soft); color: var(--green); border: 1px solid var(--line); }

.downloads-admin-page { width: 100%; min-width: 0; }
.downloads-panel, .hero-card > *, .toolbar > *, .form-grid label { min-width: 0; max-width: 100%; }
.hero-card { overflow-wrap: anywhere; }
.form-grid input:not([type=checkbox]), .form-grid select, .form-grid textarea { width: 100%; min-width: 0; box-sizing: border-box; }
@media (max-width: 640px) { .form-grid { grid-template-columns: minmax(0, 1fr); } .modal-head, .modal-actions { flex-wrap: wrap; } }
</style>
