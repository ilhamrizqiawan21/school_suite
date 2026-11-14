<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Loan {
    id: number;
    asset_id: number;
    borrower_name: string;
    borrower_reference?: string | null;
    borrowed_at: string;
    due_at?: string | null;
    returned_at?: string | null;
    return_condition?: string | null;
    asset?: { id: number; inventory_code: string; name: string };
}

interface Asset {
    id: number;
    inventory_code: string;
    name: string;
    category: string;
    location?: string | null;
    condition: string;
    status: string;
    notes?: string | null;
    active_loan: Loan[];
}

const props = defineProps<{
    assets: Asset[];
    loans: Loan[];
    filters: { search: string; status: string };
    available_assets: { id: number; inventory_code: string; name: string }[];
    asset_pages: { current: number; last: number; total: number; previous: string | null; next: string | null };
    loan_pages: { current: number; last: number; total: number; previous: string | null; next: string | null };
    summary: { total: number; available: number; borrowed: number; maintenance: number };
}>();

const search = ref(props.filters.search);
const statusFilter = ref(props.filters.status);
const editingAssetId = ref<number | null>(null);
const returnErrors = ref<Record<number, string>>({});
const returning = ref<number | null>(null);

const blankAsset = () => ({ inventory_code: '', name: '', category: '', location: '', condition: 'good', status: 'available', notes: '' });
const showAssetModal = ref(false);
const showLoanModal = ref(false);
const assetForm = useForm(blankAsset());
const assetLevelError = computed(() => (assetForm.errors as unknown as Record<string, string>).asset ?? '');
const loanForm = useForm({ asset_id: '', borrower_name: '', borrower_reference: '', borrowed_at: new Date().toISOString().slice(0, 10), due_at: '', notes: '' });
const returnForms = ref<Record<number, { return_condition: string; notes: string }>>(
    Object.fromEntries(props.loans.map((loan) => [loan.id, { return_condition: 'good', notes: '' }])) as Record<number, { return_condition: string; notes: string }>,
);

watch(() => props.loans, (loans) => {
    for (const loan of loans) returnForms.value[loan.id] ??= { return_condition: 'good', notes: '' };
});
const availableAssets = computed(() => props.available_assets);

function applyFilters() {
    router.get('/administrasi/sarpras', { search: search.value || undefined, status: statusFilter.value || undefined }, { preserveState: true, replace: true });
}

function openCreateAsset() {
    editingAssetId.value = null;
    assetForm.defaults(blankAsset());
    assetForm.reset();
    assetForm.clearErrors();
    showAssetModal.value = true;
}

function editAsset(asset: Asset) {
    editingAssetId.value = asset.id;
    assetForm.clearErrors();
    assetForm.defaults({
        inventory_code: asset.inventory_code,
        name: asset.name,
        category: asset.category,
        location: asset.location ?? '',
        condition: asset.condition,
        status: asset.status === 'borrowed' ? 'available' : asset.status,
        notes: asset.notes ?? '',
    });
    assetForm.reset();
    showAssetModal.value = true;
}

function cancelEdit() {
    if (assetForm.isDirty && !window.confirm('Batalkan perubahan data aset yang belum disimpan?')) return;
    showAssetModal.value = false;
    editingAssetId.value = null;
    assetForm.defaults(blankAsset());
    assetForm.reset();
    assetForm.clearErrors();
}

function saveAsset() {
    const url = editingAssetId.value ? `/administrasi/sarpras/aset/${editingAssetId.value}` : '/administrasi/sarpras/aset';
    const method = editingAssetId.value ? 'put' : 'post';
    assetForm.submit(method, url, {
        preserveScroll: true,
        onSuccess: () => {
            showAssetModal.value = false;
            editingAssetId.value = null;
            assetForm.defaults(blankAsset());
            assetForm.reset();
            assetForm.clearErrors();
        },
    });
}

function borrowAsset() {
    loanForm.post('/administrasi/sarpras/peminjaman', {
        preserveScroll: true,
        onSuccess: () => {
            showLoanModal.value = false;
            loanForm.reset('asset_id', 'borrower_name', 'borrower_reference', 'due_at', 'notes');
        },
    });
}

function returnAsset(loan: Loan) {
    if (returning.value !== null || !window.confirm(`Catat pengembalian ${loan.asset?.name || 'aset'} dari ${loan.borrower_name}?`)) return;
    const fields = returnForms.value[loan.id] ?? { return_condition: 'good', notes: '' };
    returning.value = loan.id;
    router.post(`/administrasi/sarpras/peminjaman/${loan.id}/kembali`, fields, {
        preserveScroll: true,
        onError: (errors) => { returnErrors.value[loan.id] = Object.values(errors)[0] || 'Pengembalian gagal dicatat.'; },
        onSuccess: () => { delete returnErrors.value[loan.id]; },
        onFinish: () => { returning.value = null; },
    });
}

const dateLabel = (value?: string | null) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(value)) : '—';
const statusLabel = (status: string) => ({ available: 'Tersedia', borrowed: 'Dipinjam', maintenance: 'Perawatan', inactive: 'Nonaktif' }[status] ?? status);
const conditionLabel = (condition: string) => ({ good: 'Baik', fair: 'Cukup', poor: 'Kurang baik', damaged: 'Rusak' }[condition] ?? condition);
const conditionClass = (condition: string) => `condition-${condition}`;
</script>

<template>
    <Head title="Sarana & Prasarana" />
    <AppLayout title="Sarana & Prasarana">
        <main class="facilities-page">
            <header class="hero-card facilities-hero">
                <div><p class="eyebrow">Tata Usaha</p><h1>Inventaris sarana & prasarana</h1></div>
                <nav class="hero-actions" aria-label="Aksi inventaris"><AppButton variant="primary" icon="plus" as="a" href="#asset-form" class="button primary">Tambah aset</AppButton><AppButton variant="secondary" as="a" href="#loan-form" class="button secondary">Catat peminjaman</AppButton></nav>
            </header>



            <section class="summary-grid" aria-label="Ringkasan inventaris">
                <article class="summary-total"><span>Total aset</span><strong>{{ summary.total }}</strong><small>Seluruh inventaris sekolah</small></article>
                <article class="summary-available"><span>Tersedia</span><strong>{{ summary.available }}</strong><small>Siap digunakan atau dipinjam</small></article>
                <article class="summary-borrowed"><span>Sedang dipinjam</span><strong>{{ summary.borrowed }}</strong><small>Perlu dipantau batas kembali</small></article>
                <article class="summary-maintenance"><span>Perawatan</span><strong>{{ summary.maintenance }}</strong><small>Tidak tersedia sementara</small></article>
            </section>

            <div v-if="showAssetModal" role="dialog" aria-modal="true" aria-labelledby="asset-modal-title" class="modal-backdrop" @click.self="cancelEdit" @keydown.esc="cancelEdit">
                <section id="asset-form" class="modal-card">
                    <div class="section-heading"><div><h2 id="asset-modal-title">{{ editingAssetId ? 'Ubah data aset' : 'Tambah aset' }}</h2><p>Kode inventaris harus unik di sekolah ini.</p></div><AppButton variant="ghost" class="modal-close" type="button" aria-label="Tutup formulir aset" @click="cancelEdit">×</AppButton></div>
                    <form class="asset-form" @submit.prevent="saveAsset">
                        <label><span>Kode inventaris</span><input v-model="assetForm.inventory_code" required maxlength="80"><small v-if="assetForm.errors.inventory_code">{{ assetForm.errors.inventory_code }}</small></label>
                        <label><span>Nama aset</span><input v-model="assetForm.name" required maxlength="180"><small v-if="assetForm.errors.name">{{ assetForm.errors.name }}</small></label>
                        <label><span>Kategori</span><input v-model="assetForm.category" required maxlength="100" placeholder="Elektronik, mebel, ..."><small v-if="assetForm.errors.category">{{ assetForm.errors.category }}</small></label>
                        <label><span>Lokasi</span><input v-model="assetForm.location" maxlength="180" placeholder="Ruang / gedung"></label>
                        <label><span>Kondisi</span><select v-model="assetForm.condition"><option value="good">Baik</option><option value="fair">Cukup</option><option value="poor">Kurang baik</option><option value="damaged">Rusak</option></select></label>
                        <label><span>Status</span><select v-model="assetForm.status"><option value="available">Tersedia</option><option value="maintenance">Perawatan</option><option value="inactive">Nonaktif</option></select></label>
                        <label class="wide"><span>Catatan</span><textarea v-model="assetForm.notes" rows="2" maxlength="5000" /></label>
                        <div class="form-actions wide"><small v-if="assetLevelError">{{ assetLevelError }}</small><AppButton variant="secondary" type="button" class="button secondary" @click="cancelEdit">Batal</AppButton><AppButton variant="primary" :loading="assetForm.processing" type="submit" class="button primary" :disabled="assetForm.processing">{{ assetForm.processing ? 'Menyimpan…' : editingAssetId ? 'Simpan perubahan' : 'Simpan aset' }}</AppButton></div>
                        <p v-if="assetForm.hasErrors" class="form-error wide" role="alert">{{ Object.values(assetForm.errors).join(' ') }}</p>
                    </form>
                </section>
            </div>

            <section class="panel">
                <div class="section-heading"><div><h2>Daftar aset</h2><p>Daftar hanya menampilkan aset milik sekolah Anda.</p></div><AppButton variant="primary" icon="plus" type="button" class="button primary" @click="openCreateAsset"> Tambah aset</AppButton></div>
                <form class="filters" @submit.prevent="applyFilters">
                    <input v-model="search" class="search-control" type="search" aria-label="Cari aset" placeholder="Cari kode, nama, kategori…">
                    <select v-model="statusFilter" aria-label="Filter status"><option value="">Semua status</option><option value="available">Tersedia</option><option value="borrowed">Dipinjam</option><option value="maintenance">Perawatan</option><option value="inactive">Nonaktif</option></select>
                    <AppButton variant="primary" type="submit" class="button secondary">Terapkan</AppButton>
                    <Link href="/administrasi/sarpras" class="button secondary ui-button ui-button--secondary ui-button--md">Reset</Link>
                </form>
                <div class="table-wrap table-scroll" tabindex="0" role="region" aria-label="Daftar aset sekolah"><table class="app-table asset-table"><thead><tr><th scope="col">Kode / aset</th><th scope="col">Kategori & lokasi</th><th scope="col">Kondisi</th><th scope="col">Status / peminjam</th><th scope="col">Aksi</th></tr></thead>
                    <tbody><tr v-for="asset in assets" :key="asset.id"><td><strong>{{ asset.name }}</strong><small>{{ asset.inventory_code }}</small></td><td><strong>{{ asset.category }}</strong><small>{{ asset.location || 'Lokasi belum diisi' }}</small></td><td><span class="condition" :class="conditionClass(asset.condition)">{{ conditionLabel(asset.condition) }}</span></td><td><span class="status" :class="asset.status">{{ statusLabel(asset.status) }}</span><small v-if="asset.active_loan?.[0]">{{ asset.active_loan[0].borrower_name }} · jatuh tempo {{ dateLabel(asset.active_loan[0].due_at) }}</small></td><td><AppButton variant="ghost" size="sm" type="button" class="text-action" :disabled="asset.status === 'borrowed'" @click="editAsset(asset)">Ubah</AppButton></td></tr>
                    <tr v-if="assets.length === 0"><td colspan="5" class="empty">Belum ada aset yang cocok dengan filter.</td></tr></tbody>
                </table></div>
                <nav class="page-controls" aria-label="Halaman aset"><Link v-if="asset_pages.previous" :href="asset_pages.previous" preserve-scroll class="button secondary ui-button ui-button--secondary ui-button--md">Sebelumnya</Link><span>{{ asset_pages.total }} aset · halaman {{ asset_pages.current }} / {{ asset_pages.last }}</span><Link v-if="asset_pages.next" :href="asset_pages.next" preserve-scroll class="button secondary ui-button ui-button--secondary ui-button--md">Berikutnya</Link></nav>
            </section>

            <div v-if="showLoanModal" role="dialog" aria-modal="true" aria-labelledby="loan-modal-title" class="modal-backdrop" @click.self="showLoanModal = false" @keydown.esc="showLoanModal = false">
                <section id="loan-form" class="modal-card">
                    <div class="section-heading"><div><p class="eyebrow">Transaksi aset</p><h2 id="loan-modal-title">Catat peminjaman</h2><p>Peminjaman hanya dapat dibuat untuk aset yang tersedia.</p></div><AppButton variant="ghost" type="button" class="modal-close" aria-label="Tutup formulir peminjaman" @click="showLoanModal = false">×</AppButton></div>
                    <form class="loan-form" @submit.prevent="borrowAsset">
                        <label><span>Aset</span><select v-model="loanForm.asset_id" required><option value="" disabled>Pilih aset</option><option v-for="asset in availableAssets" :key="asset.id" :value="String(asset.id)">{{ asset.inventory_code }} · {{ asset.name }}</option></select><small v-if="loanForm.errors.asset_id">{{ loanForm.errors.asset_id }}</small></label>
                        <label><span>Nama peminjam</span><input v-model="loanForm.borrower_name" required maxlength="180"><small v-if="loanForm.errors.borrower_name">{{ loanForm.errors.borrower_name }}</small></label>
                        <label><span>NIS / NIP / referensi (opsional)</span><input v-model="loanForm.borrower_reference" maxlength="100"></label>
                        <label><span>Tanggal pinjam</span><input v-model="loanForm.borrowed_at" type="date" required></label>
                        <label><span>Batas pengembalian</span><input v-model="loanForm.due_at" type="date"></label>
                        <label class="wide"><span>Catatan</span><textarea v-model="loanForm.notes" rows="2" maxlength="5000" /></label>
                        <div class="form-actions wide"><AppButton variant="secondary" type="button" class="button secondary" @click="showLoanModal = false">Batal</AppButton><AppButton variant="primary" type="submit" class="button primary" :disabled="loanForm.processing || availableAssets.length === 0">Catat peminjaman</AppButton></div>
                        <p v-if="!availableAssets.length" class="wide">Belum ada aset tersedia. Tambahkan aset atau catat pengembalian terlebih dahulu.</p>
                        <p v-if="loanForm.hasErrors" class="form-error wide" role="alert">{{ Object.values(loanForm.errors).join(' ') }}</p>
                    </form>
                </section>
            </div>

            <section class="panel">
                <div id="loan-history">
                    <div class="section-heading"><div><p class="eyebrow">Kontrol pengembalian</p><h2>Pengembalian & riwayat</h2><p>Peminjaman yang belum kembali ditampilkan lebih dahulu.</p></div><AppButton variant="primary" type="button" class="button primary" @click="showLoanModal = true">+ Catat peminjaman</AppButton></div>
                    <div class="loan-list">
                        <article v-for="loan in loans" :key="loan.id" class="loan-item">
                            <div><strong>{{ loan.asset?.name || 'Aset' }}</strong><small>{{ loan.asset?.inventory_code }} · {{ loan.borrower_name }}</small><small>Pinjam {{ dateLabel(loan.borrowed_at) }} · {{ loan.returned_at ? 'dikembalikan ' + dateLabel(loan.returned_at) : 'batas kembali ' + dateLabel(loan.due_at) }}</small></div>
                            <div v-if="!loan.returned_at" class="return-form"><select v-model="returnForms[loan.id].return_condition" aria-label="Kondisi aset saat dikembalikan"><option value="good">Kembali baik</option><option value="damaged">Rusak / perlu perawatan</option></select><AppButton variant="ghost" size="sm" type="button" class="text-action" :disabled="returning !== null" @click="returnAsset(loan)">Catat kembali</AppButton><p v-if="returnErrors[loan.id]" class="form-error" role="alert">{{ returnErrors[loan.id] }}</p></div>
                            <span v-else class="status available">Sudah kembali</span>
                        </article>
                        <p v-if="loans.length === 0" class="empty">Belum ada riwayat peminjaman.</p>
                    </div>
                    <nav class="page-controls" aria-label="Halaman peminjaman"><Link v-if="loan_pages.previous" :href="loan_pages.previous" preserve-scroll class="button secondary ui-button ui-button--secondary ui-button--md">Sebelumnya</Link><span>{{ loan_pages.total }} transaksi · halaman {{ loan_pages.current }} / {{ loan_pages.last }}</span><Link v-if="loan_pages.next" :href="loan_pages.next" preserve-scroll class="button secondary ui-button ui-button--secondary ui-button--md">Berikutnya</Link></nav>
                </div>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.workflow h2{font-weight: var(--weight-bold)}.workflow p{margin:.5rem 0;color:#52637c;line-height:1.6}.workflow nav,.page-controls{display:flex;flex-wrap:wrap;align-items:center;gap:.65rem}.page-controls{margin-top:1rem;font-size:var(--type-meta);color:#52637c}.form-error{color:#b42332;font-size:var(--type-meta)}.facilities-page>*,.loan-grid>*,.loan-item>div{min-width:0;overflow-wrap:anywhere}.hero-card{flex-wrap:wrap}#asset-form,#loan-form,#loan-history{scroll-margin-top:6rem}

.facilities-page{display:grid;gap:1rem}.hero-card,.panel{padding:1.2rem;border:1px solid var(--line,#d9e2ef);border-radius:1rem;background:var(--surface,#fff)}.hero-card{background:linear-gradient(120deg,#f7faff,#fff)}.eyebrow{margin:0;color:#3b5fa8;font-size:var(--type-meta);font-weight:var(--weight-bold);text-transform:uppercase;letter-spacing:.08em}.hero-card h1{margin:.35rem 0;font-size:var(--type-3xl);font-weight: var(--weight-bold);letter-spacing:-.035em}.hero-card p:last-child,.section-heading p{margin:0;color:#66758b;font-size:var(--type-sm)}.summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}.summary-grid article{display:grid;gap:.3rem;padding:1rem;border:1px solid var(--line,#d9e2ef);border-radius:.85rem;background: var(--surface)}.summary-grid span{color:#69788d;font-size:var(--type-meta)}.summary-grid strong{font-size:1.35rem}.section-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem}.section-heading h2{margin:0 0 .25rem;font-size:var(--type-md);font-weight:var(--weight-bold)}.asset-form,.loan-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem}.asset-form label,.loan-form label{display:grid;gap:.35rem;min-width:0}.asset-form label span,.loan-form label span{font-size:var(--type-meta);font-weight:var(--weight-semibold);color:#4d5d73}.asset-form input,.asset-form select,.asset-form textarea,.loan-form input,.loan-form select,.loan-form textarea,.filters input,.filters select,.return-form select{width:100%;min-height:40px;padding:.55rem .65rem;border:1px solid #cfd9e8;border-radius:.55rem;background: var(--surface);color:#1e293b;font:inherit;font-size:var(--type-control)}.asset-form textarea,.loan-form textarea{resize:vertical}.asset-form small,.loan-form small{color:#b42332;font-size:var(--type-meta)}.wide{grid-column:1/-1}.form-actions{display:flex;align-items:center;justify-content:flex-end;gap:.75rem}.filters{display:flex;flex-wrap:wrap;gap:.55rem;margin-bottom:.75rem}.filters input{flex:1;min-width:220px}.filters select{max-width:180px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;text-align:left;font-size:var(--type-meta)}th,td{padding:.75rem .65rem;border-bottom:1px solid #e6ebf2;vertical-align:middle}th{color:#64748b;font-size:var(--type-meta);text-transform:uppercase;letter-spacing:.035em}td strong,td small,.loan-item strong,.loan-item small{display:block}td small,.loan-item small{margin-top:.2rem;color:var(--ink-soft);font-size:var(--type-meta)}.status{display:inline-flex;padding:.24rem .5rem;border-radius:999px;background:#edf2f9;color:#475569;font-size:var(--type-meta);font-weight: var(--weight-semibold)}.status.available{background:#eaf8f0;color:#14734e}.status.borrowed{background:#fff6df;color:#9a6400}.status.maintenance,.status.inactive{background:#fff0ef;color:#a43b37}.empty{padding:1rem;text-align:center;color:var(--ink-soft)}.loan-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:1.5rem}.loan-list{display:grid;gap:.55rem}.loan-item{display:flex;align-items:center;justify-content:space-between;gap:.7rem;padding:.7rem;border:1px solid #e1e8f1;border-radius:.7rem}.loan-item strong{font-size:var(--type-meta)}.return-form{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;min-width:0}.return-form select{min-width:155px}@media(max-width:850px){.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.asset-form,.loan-form{grid-template-columns:repeat(2,minmax(0,1fr))}.loan-grid{grid-template-columns:1fr}}@media(max-width:560px){.summary-grid{grid-template-columns:1fr 1fr}.asset-form,.loan-form{grid-template-columns:1fr}.wide{grid-column:auto}.loan-item{align-items:flex-start;flex-direction:column}.return-form{width:100%}.return-form select{flex:1}}
/* Sarpras page refinements */
.facilities-page { --facility-ink: #172b4d; --facility-muted: #64748b; --facility-line: #dbe3ef; }
.facilities-hero { align-items: center; }
.hero-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: .55rem; }
.workflow { display: grid; gap: 1rem; }
.workflow .section-heading { margin-bottom: 0; }
.workflow-steps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .7rem; margin: 0; padding: 0; list-style: none; }
.workflow-steps li { display: grid; grid-template-columns: 2rem minmax(0, 1fr); align-items: start; gap: .5rem; border-top: 3px solid #dbe3ef; padding: .75rem .2rem 0; }
.workflow-steps li > span { display: grid; width: 1.8rem; height: 1.8rem; place-items: center; border-radius: 50%; background: #315db3; color: #fff; font-size: var(--type-meta); font-weight: var(--weight-bold); }
.workflow-steps strong, .workflow-steps small { display: block; }
.workflow-steps strong { color: var(--facility-ink); font-size: var(--type-sm); }
.workflow-steps small { margin-top: .2rem; color: var(--facility-muted); font-size: var(--type-meta); line-height: 1.4; }
.workflow-steps a { grid-column: 2; justify-self: start; margin-top: .35rem; color: #315db3; font-size: var(--type-meta); font-weight: var(--weight-bold); text-decoration: none; }
.workflow-steps a:hover { text-decoration: underline; }
.summary-grid article { min-height: 6.8rem; }
.summary-grid article small { color: #64748b; font-size: var(--type-meta); line-height: 1.35; }
.summary-total { background: #f7faff !important; border-color: #c9d7f3 !important; }
.summary-available { background: #f2fcf6 !important; border-color: #b9dec8 !important; }
.summary-available strong { color: #067647; }
.summary-borrowed { background: #fffaf0 !important; border-color: #ead7aa !important; }
.summary-borrowed strong { color: #9a6400; }
.summary-maintenance { background: #fff8f7 !important; border-color: #f2c9c5 !important; }
.summary-maintenance strong { color: #b42318; }
.asset-table td:nth-child(1) { width: 23%; }
.asset-table td:nth-child(2) { width: 20%; }
.asset-table td:nth-child(3) { width: 15%; }
.asset-table td:nth-child(4) { width: 27%; }
.asset-table td:nth-child(5) { width: 15%; }
.asset-table td strong { color: var(--facility-ink); }
.condition { display: inline-flex; border-radius: 999px; padding: .24rem .5rem; font-size: var(--type-meta); font-weight: var(--weight-bold); }
.condition-good { background: #ecfdf3; color: #067647; }
.condition-fair { background: #fff7e6; color: #9a6400; }
.condition-poor { background: #fff0ef; color: #a43b37; }
.condition-damaged { background: #fef3f2; color: #b42318; }
.loan-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); align-items: start; gap: 1.25rem; }
.loan-grid > div { min-width: 0; }
.loan-grid > div + div { border-left: 1px solid #e2e8f0; padding-left: 1.25rem; }
.loan-item { align-items: flex-start; background: #fbfdff; }
.loan-item strong { color: var(--facility-ink); }
.loan-item .status { flex: 0 0 auto; }
.return-form { justify-content: flex-end; }
.return-form .form-error { flex-basis: 100%; margin: 0; }
@media (max-width: 850px) {
    .workflow-steps { grid-template-columns: 1fr; }
    .loan-grid { grid-template-columns: 1fr; }
    .loan-grid > div + div { border-top: 1px solid #e2e8f0; border-left: 0; padding-top: 1.25rem; padding-left: 0; }
}
@media (max-width: 560px) {
    .facilities-hero { align-items: stretch; }
    .hero-actions { justify-content: flex-start; }
    .hero-actions .button { flex: 1 1 10rem; }
    .asset-table { min-width: 720px; }
}
/* Shared card rhythm: summary cards use one neutral surface and semantic accents. */
.facilities-page > .hero-card { border-radius: var(--card-radius-featured); }
.facilities-page > .panel { border-radius: var(--card-radius); }
.summary-grid article { border-radius: var(--card-radius-compact); background: var(--surface) !important; }
.summary-total { border-top: 3px solid var(--primary) !important; }
.summary-available { border-top: 3px solid var(--green) !important; }
.summary-borrowed { border-top: 3px solid var(--gold) !important; }
.summary-maintenance { border-top: 3px solid var(--red) !important; }
.asset-table .text-action::before { color: var(--edit); content: '✎'; font-size: var(--type-md); line-height: 1; }
:global(html[data-theme='dark'] .facilities-page) { --facility-ink: var(--ink); --facility-muted: var(--ink-soft); --facility-line: var(--line); }
:global(html[data-theme='dark'] .facilities-page .hero-card) ,
:global(html[data-theme='dark'] .facilities-page .panel) { background: var(--surface) !important; border-color: var(--line) !important; color: var(--ink); }
:global(html[data-theme='dark'] .facilities-page .hero-card) { background: linear-gradient(120deg, var(--surface-subtle), var(--surface)) !important; }
:global(html[data-theme='dark'] .facilities-page .workflow p) ,
:global(html[data-theme='dark'] .facilities-page .hero-card p:last-child) ,
:global(html[data-theme='dark'] .facilities-page .section-heading p) ,
:global(html[data-theme='dark'] .facilities-page .page-controls) ,
:global(html[data-theme='dark'] .facilities-page .summary-grid span) ,
:global(html[data-theme='dark'] .facilities-page .summary-grid article small) ,
:global(html[data-theme='dark'] .facilities-page td small) ,
:global(html[data-theme='dark'] .facilities-page .loan-item small) ,
:global(html[data-theme='dark'] .facilities-page .empty) { color: var(--ink-soft) !important; }
:global(html[data-theme='dark'] .facilities-page .asset-form input) ,
:global(html[data-theme='dark'] .facilities-page .asset-form select) ,
:global(html[data-theme='dark'] .facilities-page .asset-form textarea) ,
:global(html[data-theme='dark'] .facilities-page .loan-form input) ,
:global(html[data-theme='dark'] .facilities-page .loan-form select) ,
:global(html[data-theme='dark'] .facilities-page .loan-form textarea) ,
:global(html[data-theme='dark'] .facilities-page .filters input) ,
:global(html[data-theme='dark'] .facilities-page .filters select) ,
:global(html[data-theme='dark'] .facilities-page .return-form select) { background: var(--surface) !important; border-color: var(--line) !important; color: var(--ink) !important; }
:global(html[data-theme='dark'] .facilities-page .asset-form label span) ,
:global(html[data-theme='dark'] .facilities-page .loan-form label span) ,
:global(html[data-theme='dark'] .facilities-page th) { color: var(--ink-soft) !important; }
:global(html[data-theme='dark'] .facilities-page th) ,
:global(html[data-theme='dark'] .facilities-page td) { border-bottom-color: var(--line) !important; }
:global(html[data-theme='dark'] .facilities-page th) { background: var(--surface-subtle); }
:global(html[data-theme='dark'] .facilities-page .workflow-steps li) ,
:global(html[data-theme='dark'] .facilities-page .loan-grid > div + div) { border-color: var(--line) !important; }
:global(html[data-theme='dark'] .facilities-page .loan-item) { background: var(--surface-subtle) !important; border-color: var(--line) !important; }
:global(html[data-theme='dark'] .facilities-page .status.available) ,
:global(html[data-theme='dark'] .facilities-page .condition-good) { background: var(--green-soft); color: var(--green); }
:global(html[data-theme='dark'] .facilities-page .status.borrowed) ,
:global(html[data-theme='dark'] .facilities-page .condition-fair) { background: var(--gold-soft); color: var(--gold); }
:global(html[data-theme='dark'] .facilities-page .status.maintenance) ,
:global(html[data-theme='dark'] .facilities-page .status.inactive) ,
:global(html[data-theme='dark'] .facilities-page .condition-poor) ,
:global(html[data-theme='dark'] .facilities-page .condition-damaged) { background: var(--red-soft); color: var(--red); }
.facilities-page .hero-card, .facilities-page .panel { background: var(--surface); border-color: var(--line); border-radius: 14px; box-shadow: none; }
.facilities-page .hero-card { padding: clamp(1rem, 2.5vw, 1.5rem); }
.facilities-page .summary-grid article { color: var(--ink); background: var(--surface); border-color: var(--line); border-radius: 12px; }
.facilities-page .summary-grid span, .facilities-page .section-heading p { color: var(--ink-soft); }
.facilities-page .asset-form label span, .facilities-page .loan-form label span { color: var(--ink-soft); }
.facilities-page .asset-form input, .facilities-page .asset-form select, .facilities-page .asset-form textarea, .facilities-page .loan-form input, .facilities-page .loan-form select, .facilities-page .loan-form textarea, .facilities-page .filters input, .facilities-page .filters select, .facilities-page .return-form select { min-height: 44px; color: var(--ink); background: var(--surface); border-color: var(--line-strong); border-radius: 10px; }
.facilities-page input:focus-visible, .facilities-page select:focus-visible, .facilities-page textarea:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
@media (max-width: 560px) { .facilities-page .hero-actions, .facilities-page .filters { align-items: stretch; flex-direction: column; width: 100%; } .facilities-page .hero-actions > *, .facilities-page .filters > * { width: 100%; max-width: none; } .facilities-page .summary-grid article { min-width: 0; } }
/* Center categorical status and action columns while keeping asset descriptions left aligned. */
.asset-table th:nth-child(n+3), .asset-table td:nth-child(n+3) { text-align: center; }
:global(html[data-theme='dark'] .facilities-page .summary-grid article) { background: var(--surface) !important; border-color: var(--line) !important; color: var(--ink); }
:global(html[data-theme='dark'] .facilities-page .summary-available strong) { color: var(--green); }
:global(html[data-theme='dark'] .facilities-page .summary-borrowed strong) { color: var(--gold); }
:global(html[data-theme='dark'] .facilities-page .summary-maintenance strong),
:global(html[data-theme='dark'] .facilities-page .form-error) { color: var(--red); }
:global(html[data-theme='dark'] .facilities-page .workflow-steps a), :global(html[data-theme='dark'] .facilities-page .eyebrow) { color: var(--primary); }
:global(html[data-theme='dark'] .facilities-page .status:not(.available):not(.borrowed):not(.maintenance):not(.inactive)) { background: var(--surface-strong); color: var(--ink-soft); }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .56); backdrop-filter: blur(3px); }
.modal-card { width: min(720px, 100%); max-height: 90dvh; overflow-y: auto; display: grid; gap: 1rem; padding: 1.35rem; border: 1px solid var(--line); border-radius: 16px; background: var(--surface); box-shadow: 0 24px 80px rgb(15 23 42 / .25); }
</style>
