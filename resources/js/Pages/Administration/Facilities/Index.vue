<script setup lang="ts">
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
    document.getElementById('asset-form')?.scrollIntoView({ behavior: 'smooth' });
}

function cancelEdit() {
    if (assetForm.isDirty && !window.confirm('Batalkan perubahan data aset yang belum disimpan?')) return;
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
        onSuccess: () => { cancelEdit(); },
    });
}

function borrowAsset() {
    loanForm.post('/administrasi/sarpras/peminjaman', {
        preserveScroll: true,
        onSuccess: () => loanForm.reset('asset_id', 'borrower_name', 'borrower_reference', 'due_at', 'notes'),
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
</script>

<template>
    <Head title="Sarana & Prasarana" />
    <AppLayout title="Sarana & Prasarana">
        <main class="facilities-page">
            <header class="hero-card">
                <div><p class="eyebrow">Tata Usaha</p><h1>Inventaris sarana & prasarana</h1><p>Kelola aset sekolah dan catat peminjaman serta pengembaliannya.</p></div>
            </header>

            <section class="panel workflow"><h2>Alur inventaris</h2><p>1. Catat setiap unit aset dengan kode inventaris sendiri. 2. Pilih aset tersedia untuk dipinjam. 3. Catat pengembalian dan kondisi aset.</p><nav aria-label="Pekerjaan inventaris"><a href="#asset-form" class="button secondary">Catat aset</a><a href="#loan-form" class="button secondary">Catat peminjaman</a><a href="#loan-history" class="button secondary">Pengembalian & riwayat</a></nav></section>

            <section class="summary-grid" aria-label="Ringkasan inventaris">
                <article><span>Total aset</span><strong>{{ summary.total }}</strong></article>
                <article><span>Tersedia</span><strong>{{ summary.available }}</strong></article>
                <article><span>Sedang dipinjam</span><strong>{{ summary.borrowed }}</strong></article>
                <article><span>Perawatan</span><strong>{{ summary.maintenance }}</strong></article>
            </section>

            <section id="asset-form" class="panel">
                <div class="section-heading"><div><h2>{{ editingAssetId ? 'Ubah data aset' : 'Tambah aset' }}</h2><p>Kode inventaris harus unik di sekolah ini.</p></div><button v-if="editingAssetId" class="button secondary" type="button" @click="cancelEdit">Batal ubah</button></div>
                <form class="asset-form" @submit.prevent="saveAsset">
                    <label><span>Kode inventaris</span><input v-model="assetForm.inventory_code" required maxlength="80"><small v-if="assetForm.errors.inventory_code">{{ assetForm.errors.inventory_code }}</small></label>
                    <label><span>Nama aset</span><input v-model="assetForm.name" required maxlength="180"><small v-if="assetForm.errors.name">{{ assetForm.errors.name }}</small></label>
                    <label><span>Kategori</span><input v-model="assetForm.category" required maxlength="100" placeholder="Elektronik, mebel, ..."><small v-if="assetForm.errors.category">{{ assetForm.errors.category }}</small></label>
                    <label><span>Lokasi</span><input v-model="assetForm.location" maxlength="180" placeholder="Ruang / gedung"></label>
                    <label><span>Kondisi</span><select v-model="assetForm.condition"><option value="good">Baik</option><option value="fair">Cukup</option><option value="poor">Kurang baik</option><option value="damaged">Rusak</option></select></label>
                    <label><span>Status</span><select v-model="assetForm.status"><option value="available">Tersedia</option><option value="maintenance">Perawatan</option><option value="inactive">Nonaktif</option></select></label>
                    <label class="wide"><span>Catatan</span><textarea v-model="assetForm.notes" rows="2" maxlength="5000" /></label>
                    <div class="form-actions wide"><small v-if="assetLevelError">{{ assetLevelError }}</small><button class="button primary" :disabled="assetForm.processing">{{ assetForm.processing ? 'Menyimpan…' : editingAssetId ? 'Simpan perubahan' : 'Simpan aset' }}</button></div>
                    <p v-if="assetForm.hasErrors" class="form-error wide" role="alert">{{ Object.values(assetForm.errors).join(' ') }}</p>
                </form>
            </section>

            <section class="panel">
                <div class="section-heading"><div><h2>Daftar aset</h2><p>Daftar hanya menampilkan aset milik sekolah Anda.</p></div></div>
                <form class="filters" @submit.prevent="applyFilters">
                    <input v-model="search" aria-label="Cari aset" placeholder="Cari kode, nama, kategori…">
                    <select v-model="statusFilter" aria-label="Filter status"><option value="">Semua status</option><option value="available">Tersedia</option><option value="borrowed">Dipinjam</option><option value="maintenance">Perawatan</option><option value="inactive">Nonaktif</option></select>
                    <button class="button secondary">Terapkan</button>
                    <Link href="/administrasi/sarpras" class="button secondary">Reset</Link>
                </form>
                <div class="table-wrap"><table><thead><tr><th>Kode / aset</th><th>Kategori & lokasi</th><th>Kondisi</th><th>Status / peminjam</th><th>Aksi</th></tr></thead>
                    <tbody><tr v-for="asset in assets" :key="asset.id"><td><strong>{{ asset.name }}</strong><small>{{ asset.inventory_code }}</small></td><td>{{ asset.category }}<small>{{ asset.location || 'Lokasi belum diisi' }}</small></td><td>{{ conditionLabel(asset.condition) }}</td><td><span class="status" :class="asset.status">{{ statusLabel(asset.status) }}</span><small v-if="asset.active_loan?.[0]">{{ asset.active_loan[0].borrower_name }} · jatuh tempo {{ dateLabel(asset.active_loan[0].due_at) }}</small></td><td><button class="text-action" :disabled="asset.status === 'borrowed'" @click="editAsset(asset)">Ubah</button></td></tr>
                    <tr v-if="assets.length === 0"><td colspan="5" class="empty">Belum ada aset yang cocok dengan filter.</td></tr></tbody>
                </table></div>
                <nav class="page-controls" aria-label="Halaman aset"><Link v-if="asset_pages.previous" :href="asset_pages.previous" preserve-scroll class="button secondary">Sebelumnya</Link><span>{{ asset_pages.total }} aset · halaman {{ asset_pages.current }} / {{ asset_pages.last }}</span><Link v-if="asset_pages.next" :href="asset_pages.next" preserve-scroll class="button secondary">Berikutnya</Link></nav>
            </section>

            <section class="panel loan-grid">
                <div id="loan-form">
                    <div class="section-heading"><div><h2>Catat peminjaman</h2><p>Peminjaman hanya dapat dibuat untuk aset yang tersedia.</p></div></div>
                    <form class="loan-form" @submit.prevent="borrowAsset">
                        <label><span>Aset</span><select v-model="loanForm.asset_id" required><option value="" disabled>Pilih aset</option><option v-for="asset in availableAssets" :key="asset.id" :value="String(asset.id)">{{ asset.inventory_code }} · {{ asset.name }}</option></select><small v-if="loanForm.errors.asset_id">{{ loanForm.errors.asset_id }}</small></label>
                        <label><span>Nama peminjam</span><input v-model="loanForm.borrower_name" required maxlength="180"><small v-if="loanForm.errors.borrower_name">{{ loanForm.errors.borrower_name }}</small></label>
                        <label><span>NIS / NIP / referensi (opsional)</span><input v-model="loanForm.borrower_reference" maxlength="100"></label>
                        <label><span>Tanggal pinjam</span><input v-model="loanForm.borrowed_at" type="date" required></label>
                        <label><span>Batas pengembalian</span><input v-model="loanForm.due_at" type="date"></label>
                        <label class="wide"><span>Catatan</span><textarea v-model="loanForm.notes" rows="2" maxlength="5000" /></label>
                        <div class="wide"><button class="button primary" :disabled="loanForm.processing || availableAssets.length === 0">Catat peminjaman</button></div>
                        <p v-if="!availableAssets.length" class="wide">Belum ada aset tersedia. Tambahkan aset atau catat pengembalian terlebih dahulu.</p>
                        <p v-if="loanForm.hasErrors" class="form-error wide" role="alert">{{ Object.values(loanForm.errors).join(' ') }}</p>
                    </form>
                </div>
                <div id="loan-history">
                    <div class="section-heading"><div><h2>Pengembalian & riwayat</h2><p>Peminjaman yang belum kembali ditampilkan lebih dahulu.</p></div></div>
                    <div class="loan-list">
                        <article v-for="loan in loans" :key="loan.id" class="loan-item">
                            <div><strong>{{ loan.asset?.name || 'Aset' }}</strong><small>{{ loan.asset?.inventory_code }} · {{ loan.borrower_name }}</small><small>Pinjam {{ dateLabel(loan.borrowed_at) }} · {{ loan.returned_at ? 'dikembalikan ' + dateLabel(loan.returned_at) : 'batas kembali ' + dateLabel(loan.due_at) }}</small></div>
                            <div v-if="!loan.returned_at" class="return-form"><select v-model="returnForms[loan.id].return_condition" aria-label="Kondisi aset saat dikembalikan"><option value="good">Kembali baik</option><option value="damaged">Rusak / perlu perawatan</option></select><button class="text-action" :disabled="returning !== null" @click="returnAsset(loan)">Catat kembali</button><p v-if="returnErrors[loan.id]" class="form-error" role="alert">{{ returnErrors[loan.id] }}</p></div>
                            <span v-else class="status available">Sudah kembali</span>
                        </article>
                        <p v-if="loans.length === 0" class="empty">Belum ada riwayat peminjaman.</p>
                    </div>
                    <nav class="page-controls" aria-label="Halaman peminjaman"><Link v-if="loan_pages.previous" :href="loan_pages.previous" preserve-scroll class="button secondary">Sebelumnya</Link><span>{{ loan_pages.total }} transaksi · halaman {{ loan_pages.current }} / {{ loan_pages.last }}</span><Link v-if="loan_pages.next" :href="loan_pages.next" preserve-scroll class="button secondary">Berikutnya</Link></nav>
                </div>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.workflow h2{font-weight:750}.workflow p{margin:.5rem 0;color:#52637c;line-height:1.6}.workflow nav,.page-controls{display:flex;flex-wrap:wrap;align-items:center;gap:.65rem}.page-controls{margin-top:1rem;font-size:.8rem;color:#52637c}.form-error{color:#b42332;font-size:.8rem}.facilities-page>*,.loan-grid>*,.loan-item>div{min-width:0;overflow-wrap:anywhere}.hero-card{flex-wrap:wrap}#asset-form,#loan-form,#loan-history{scroll-margin-top:6rem}

.facilities-page{display:grid;gap:1rem}.hero-card,.panel{padding:1.2rem;border:1px solid var(--line,#d9e2ef);border-radius:1rem;background:var(--surface,#fff)}.hero-card{background:linear-gradient(120deg,#f7faff,#fff)}.eyebrow{margin:0;color:#3b5fa8;font-size:.73rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.hero-card h1{margin:.35rem 0;font-size:clamp(1.45rem,3vw,2rem);font-weight:820;letter-spacing:-.035em}.hero-card p:last-child,.section-heading p{margin:0;color:#66758b;font-size:.86rem}.summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}.summary-grid article{display:grid;gap:.3rem;padding:1rem;border:1px solid var(--line,#d9e2ef);border-radius:.85rem;background:#fff}.summary-grid span{color:#69788d;font-size:.76rem}.summary-grid strong{font-size:1.35rem}.section-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem}.section-heading h2{margin:0 0 .25rem;font-size:1rem;font-weight:800}.asset-form,.loan-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem}.asset-form label,.loan-form label{display:grid;gap:.35rem;min-width:0}.asset-form label span,.loan-form label span{font-size:.74rem;font-weight:700;color:#4d5d73}.asset-form input,.asset-form select,.asset-form textarea,.loan-form input,.loan-form select,.loan-form textarea,.filters input,.filters select,.return-form select{width:100%;min-height:40px;padding:.55rem .65rem;border:1px solid #cfd9e8;border-radius:.55rem;background:#fff;color:#1e293b;font:inherit;font-size:.82rem}.asset-form textarea,.loan-form textarea{resize:vertical}.asset-form small,.loan-form small{color:#b42332;font-size:.7rem}.wide{grid-column:1/-1}.form-actions{display:flex;align-items:center;justify-content:flex-end;gap:.75rem}.button{min-height:38px;padding:.5rem .8rem;border:1px solid #ccd8ea;border-radius:.55rem;font-size:.78rem;font-weight:750;cursor:pointer}.button.primary{border-color:#315db3;background:#315db3;color:white}.button.secondary{background:white;color:#244b90}.button:disabled{opacity:.55;cursor:not-allowed}.filters{display:flex;flex-wrap:wrap;gap:.55rem;margin-bottom:.75rem}.filters input{flex:1;min-width:220px}.filters select{max-width:180px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;text-align:left;font-size:.8rem}th,td{padding:.75rem .65rem;border-bottom:1px solid #e6ebf2;vertical-align:middle}th{color:#64748b;font-size:.7rem;text-transform:uppercase;letter-spacing:.035em}td strong,td small,.loan-item strong,.loan-item small{display:block}td small,.loan-item small{margin-top:.2rem;color:#77859a;font-size:.7rem}.text-action{border:0;background:none;color:#315db3;font-size:.76rem;font-weight:750;cursor:pointer}.text-action:disabled{opacity:.45;cursor:not-allowed}.status{display:inline-flex;padding:.24rem .5rem;border-radius:999px;background:#edf2f9;color:#475569;font-size:.68rem;font-weight:750}.status.available{background:#eaf8f0;color:#14734e}.status.borrowed{background:#fff6df;color:#9a6400}.status.maintenance,.status.inactive{background:#fff0ef;color:#a43b37}.empty{padding:1rem;text-align:center;color:#7c899b}.loan-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:1.5rem}.loan-list{display:grid;gap:.55rem}.loan-item{display:flex;align-items:center;justify-content:space-between;gap:.7rem;padding:.7rem;border:1px solid #e1e8f1;border-radius:.7rem}.loan-item strong{font-size:.78rem}.return-form{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;min-width:0}.return-form select{min-width:155px}@media(max-width:850px){.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.asset-form,.loan-form{grid-template-columns:repeat(2,minmax(0,1fr))}.loan-grid{grid-template-columns:1fr}}@media(max-width:560px){.summary-grid{grid-template-columns:1fr 1fr}.asset-form,.loan-form{grid-template-columns:1fr}.wide{grid-column:auto}.loan-item{align-items:flex-start;flex-direction:column}.return-form{width:100%}.return-form select{flex:1}}
</style>
