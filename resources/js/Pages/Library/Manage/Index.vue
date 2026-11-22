<script setup lang="ts">
import { useConfirmedDialogClose } from '@/composables/useUnsavedDialog';
import { ref, watch } from 'vue';
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

interface LoanItem {
    id: number;
    loan_code: string;
    collection_title: string;
    borrower_name: string;
    borrowed_at?: string | null;
    due_at?: string | null;
    status: string;
}

interface ProcurementItem {
    id: number;
    requester_name: string;
    title: string;
    reason: string;
    status: string;
}

const props = defineProps<{
    collections: { id: number; code: string; title: string; available_copies: number; total_copies: number; access_level: string }[];
    categories: { value: string; label: string }[];
    loans: LoanItem[];
    procurements: ProcurementItem[];
    visits: { id: number; visitor_name: string; check_in_method: string; visited_at: string }[];
    students: { student_id: number; name: string; nis: string }[];
    stats: { collections_count: number; visits_today: number; active_loans: number; pending_procurements: number };
}>();

const collectionForm = useForm({
    code: '',
    title: '',
    author: '',
    category: props.categories[0]?.value ?? 'buku_teks',
    format: 'physical',
    total_copies: 0,
    shelf_location: '',
    access_level: 'public',
    ebook_url: '',
    ebook_file: null as File | null,
});

const loanForm = useForm({
    collection_id: props.collections[0]?.id ?? ('' as string | number),
    student_id: props.students[0]?.student_id ?? ('' as string | number),
    due_at: new Date(Date.now() + 7 * 86400 * 1000).toISOString().slice(0, 10),
});

const pickEbook = (event: Event) => {
    collectionForm.ebook_file = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const saveCollection = () => {
    collectionForm.post('/perpustakaan/kelola/koleksi', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => collectionForm.reset(),
    });
};

const borrow = () => {
    loanForm.post('/perpustakaan/kelola/pinjam', { preserveScroll: true });
};

const confirmReservation = (id: number) => {
    router.post(`/perpustakaan/kelola/pinjam/${id}/konfirmasi`, {}, { preserveScroll: true });
};

const returnLoan = (id: number) => {
    router.post(`/perpustakaan/kelola/pinjam/${id}/kembali`, {}, { preserveScroll: true });
};

const decideProcurement = (id: number, status: 'approved' | 'rejected' | 'acquired') => {
    router.post(`/perpustakaan/kelola/usulan/${id}/putuskan`, { status }, { preserveScroll: true });
};

const showCollectionEditor = ref(false);
const requestCollectionEditorClose = useConfirmedDialogClose(collectionForm, () => { showCollectionEditor.value = false; collectionForm.reset(); collectionForm.clearErrors(); });
watch(() => collectionForm.wasSuccessful, (success) => { if (success) collectionForm.defaults(); });

const showLoanEditor = ref(false);
const requestLoanEditorClose = useConfirmedDialogClose(loanForm, () => { showLoanEditor.value = false; loanForm.reset(); loanForm.clearErrors(); });
watch(() => loanForm.wasSuccessful, (success) => { if (success) loanForm.defaults(); });
</script>

<template>
    <AppLayout title="Kelola Perpustakaan">
        <Head title="Kelola Perpustakaan" />

        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-[var(--ink)]">Ruang Kerja Pustakawan</h1>
                    <p class="text-sm text-[var(--ink-soft)]">Kelola katalog buku dan e-book, peminjaman, pengembalian, serta usulan pengadaan.</p>
                </div>
                <div class="flex gap-3 text-xs font-semibold">
                    <span class="rounded-lg border bg-[var(--surface)] px-3 py-2">Koleksi: {{ stats.collections_count }}</span>
                    <span class="rounded-lg border bg-[var(--surface)] px-3 py-2">Pinjaman Aktif: {{ stats.active_loans }}</span>
                    <span class="rounded-lg border bg-[var(--surface)] px-3 py-2">Usulan Pending: {{ stats.pending_procurements }}</span>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <AppButton variant="primary" type="button" @click="showCollectionEditor = true">Tambah koleksi</AppButton>
<Teleport to="body"><div v-if="showCollectionEditor" class="admin-form-overlay" role="dialog" aria-modal="true" aria-label="Tambah koleksi" @click.self="requestCollectionEditorClose" @keydown.esc.stop.prevent="requestCollectionEditorClose"><div class="admin-form-dialog"><header class="admin-form-header"><h2>Tambah koleksi</h2><AppButton type="button" icon="close" icon-only aria-label="Tutup tambah koleksi" title="Tutup formulir" :disabled="collectionForm.processing" @click="requestCollectionEditorClose" /></header>
<form @submit.prevent="saveCollection" class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-5 space-y-3">

                    <div class="grid gap-3 sm:grid-cols-2">
                        <FormField id="lib-col-code" label="Kode / ISBN" :error="collectionForm.errors.code" required>
                            <input id="lib-col-code" v-model="collectionForm.code" type="text" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                        </FormField>
                        <FormField id="lib-col-copies" label="Jumlah Eksemplar" :error="collectionForm.errors.total_copies" required>
                            <input id="lib-col-copies" v-model.number="collectionForm.total_copies" type="number" min="0" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                        </FormField>
                    </div>
                    <FormField id="lib-col-title" label="Judul Koleksi" :error="collectionForm.errors.title" required>
                        <input id="lib-col-title" v-model="collectionForm.title" type="text" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                    </FormField>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <FormField id="lib-col-cat" label="Kategori" :error="collectionForm.errors.category">
                            <select id="lib-col-cat" v-model="collectionForm.category" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                                <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                            </select>
                        </FormField>
                        <FormField id="lib-col-fmt" label="Format" :error="collectionForm.errors.format">
                            <select id="lib-col-fmt" v-model="collectionForm.format" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                                <option value="physical">Fisik</option>
                                <option value="ebook">E-Book</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </FormField>
                        <FormField id="lib-col-access" label="Hak Akses" :error="collectionForm.errors.access_level">
                            <select id="lib-col-access" v-model="collectionForm.access_level" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                                <option value="public">Publik</option>
                                <option value="members_only">Anggota</option>
                                <option value="staff_only">Staf Saja</option>
                            </select>
                        </FormField>
                    </div>
                    <FormField id="lib-col-ebook" label="Unggah File E-Book PDF (Opsional)" :error="collectionForm.errors.ebook_file">
                        <input id="lib-col-ebook" type="file" accept=".pdf" class="text-xs" @change="pickEbook" />
                    </FormField>
                    <AppButton type="submit" :disabled="collectionForm.processing">Simpan Koleksi</AppButton>
                </form>
</div></div></Teleport>

                <AppButton variant="primary" type="button" @click="showLoanEditor = true">Catat peminjaman</AppButton>
<Teleport to="body"><div v-if="showLoanEditor" class="admin-form-overlay" role="dialog" aria-modal="true" aria-label="Catat peminjaman" @click.self="requestLoanEditorClose" @keydown.esc.stop.prevent="requestLoanEditorClose"><div class="admin-form-dialog"><header class="admin-form-header"><h2>Catat peminjaman</h2><AppButton type="button" icon="close" icon-only aria-label="Tutup catat peminjaman" title="Tutup formulir" :disabled="loanForm.processing" @click="requestLoanEditorClose" /></header>
<form @submit.prevent="borrow" class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-5 space-y-3">

                    <FormField id="lib-loan-col" label="Pilih Koleksi" :error="loanForm.errors.collection_id" required>
                        <select id="lib-loan-col" v-model="loanForm.collection_id" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                            <option v-for="col in collections" :key="col.id" :value="col.id">{{ col.title }} (Tersedia: {{ col.available_copies }})</option>
                        </select>
                    </FormField>
                    <FormField id="lib-loan-student" label="Siswa Peminjam" :error="loanForm.errors.student_id" required>
                        <select id="lib-loan-student" v-model="loanForm.student_id" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                            <option v-for="s in students" :key="s.student_id" :value="s.student_id">{{ s.name }} ({{ s.nis }})</option>
                        </select>
                    </FormField>
                    <FormField id="lib-loan-due" label="Jatuh Tempo Kembali" required>
                        <input id="lib-loan-due" v-model="loanForm.due_at" type="date" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                    </FormField>
                    <AppButton type="submit" :disabled="loanForm.processing">Catat Pinjam</AppButton>
                </form>
</div></div></Teleport>
            </div>

            <div class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-5 space-y-4">
                <h2 class="text-base font-semibold text-[var(--ink)]">Daftar Sirkulasi Peminjaman</h2>
                <StateFeedback v-if="loans.length === 0" state="empty" title="Belum ada peminjaman" description="Transaksi peminjaman koleksi perpustakaan akan muncul di sini." />
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-[var(--surface-subtle)] text-center text-xs font-semibold uppercase text-[var(--ink-faint)]">
                            <tr>
                                <th class="px-3 py-2 text-center">Kode &amp; Koleksi</th>
                                <th class="px-3 py-2 text-center">Peminjam</th>
                                <th class="px-3 py-2 text-center">Jatuh Tempo</th>
                                <th class="px-3 py-2 text-center">Status</th>
                                <th class="px-3 py-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="l in loans" :key="l.id">
                                <td class="px-3 py-2.5">
                                    <p class="font-mono text-xs font-bold">{{ l.loan_code }}</p>
                                    <p class="text-xs text-[var(--ink-soft)]">{{ l.collection_title }}</p>
                                </td>
                                <td class="px-3 py-2.5 text-xs font-medium">{{ l.borrower_name }}</td>
                                <td class="px-3 py-2.5 text-xs">{{ l.due_at }}</td>
                                <td class="px-3 py-2.5"><StatusBadge :status="l.status" :tone="l.status === 'returned' ? 'success' : 'warning'" size="sm" /></td>
                                <td class="px-3 py-2.5 text-right">
                                    <AppButton v-if="l.status === 'reserved'" type="button" @click="confirmReservation(l.id)">Serahkan Buku</AppButton>
                                    <AppButton v-if="l.status !== 'returned'" type="button" variant="secondary" @click="returnLoan(l.id)">{{ l.status === 'reserved' ? 'Batalkan Reservasi' : 'Kembalikan' }}</AppButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-5 space-y-4">
                <h2 class="text-base font-semibold text-[var(--ink)]">Usulan Pengadaan Koleksi</h2>
                <StateFeedback v-if="procurements.length === 0" state="empty" title="Belum ada usulan buku" description="Usulan pengadaan dari guru dan siswa akan tampil di sini." />
                <div v-else class="space-y-2">
                    <div v-for="p in procurements" :key="p.id" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-[var(--line)] p-3 text-sm">
                        <div>
                            <p class="font-semibold">{{ p.title }} — <span class="text-xs text-[var(--ink-soft)]">{{ p.requester_name }}</span></p>
                            <p class="text-xs text-[var(--ink-faint)]">{{ p.reason }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <StatusBadge :status="p.status" :tone="p.status === 'approved' || p.status === 'acquired' ? 'success' : 'info'" size="sm" />
                            <AppButton v-if="p.status === 'submitted'" type="button" @click="decideProcurement(p.id, 'approved')">Setujui</AppButton>
                            <AppButton v-if="p.status === 'submitted'" type="button" variant="secondary" @click="decideProcurement(p.id, 'rejected')">Tolak</AppButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
