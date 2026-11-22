<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface VisitRow {
    photo_url?: string | null;
    id: number;
    visit_code: string;
    visitor_name: string;
    visitor_organization?: string | null;
    visitor_phone?: string | null;
    category: string;
    category_label: string;
    purpose: string;
    host_name?: string | null;
    host_unit?: string | null;
    status: string;
    checked_in_at?: string | null;
    checked_out_at?: string | null;
    notes?: string | null;
}

const props = defineProps<{
    visits: VisitRow[];
    pagination: { current: number; last: number; total: number; previous: string | null; next: string | null };
    summary: { today_total: number; active_inside: number; checked_out_today: number; month_total: number };
    filters: { date_from: string; date_to: string; category: string; status: string; search: string };
    categories: { value: string; label: string }[];
    hosts: { id: number; name: string }[];
}>();

const showCreateModal = ref(false);
const filterState = ref({ ...props.filters });

const applyFilters = () => {
    router.get('/administrasi/buku-tamu', {
        search: filterState.value.search || undefined,
        category: filterState.value.category || undefined,
        status: filterState.value.status || undefined,
        date_from: filterState.value.date_from || undefined,
        date_to: filterState.value.date_to || undefined,
    }, { preserveState: true, replace: true });
};

const form = useForm({
    visitor_name: '',
    visitor_organization: '',
    visitor_phone: '',
    visitor_email: '',
    category: 'tamu_dinas',
    purpose: '',
    host_teacher_id: '' as string | number,
    host_name: '',
    host_unit: '',
    notes: '',
});

const submitVisit = () => {
    form.post('/administrasi/buku-tamu', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showCreateModal.value = false;
        },
    });
};

const checkOut = (visit: VisitRow) => {
    router.post(`/administrasi/buku-tamu/${visit.id}/pulang`, {}, { preserveScroll: true });
};

const formatDate = (iso?: string | null) => iso ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso)) : '-';
</script>

<template>
    <Head title="Buku Tamu Digital — Tata Usaha" />
    <AppLayout title="Buku Tamu Digital">
        <div class="guestbook-workspace">
            <header class="workspace-head">
                <div>
                    <h1>Buku Tamu Digital</h1>
                    <p>Registrasi pengunjung, pencatatan pegawai tujuan, waktu datang/pulang, dan rekap resepsionis.</p>
                </div>
                <div class="head-actions">
                    <a :href="`/administrasi/buku-tamu/ekspor?category=${filterState.category || ''}&status=${filterState.status || ''}&date_from=${filterState.date_from || ''}&date_to=${filterState.date_to || ''}`" class="ui-button ui-button--secondary ui-button--md">
                        Ekspor CSV
                    </a>
                    <AppButton @click="showCreateModal = true">Catat Tamu Baru</AppButton>
                </div>
            </header>

            <div class="kpi-grid">
                <MetricSummaryCard label="Kunjungan Hari Ini" :value="summary.today_total" tone="blue" icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 20a8 8 0 0116 0" />
                <MetricSummaryCard label="Tamu Masih Di Sekolah" :value="summary.active_inside" tone="amber" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                <MetricSummaryCard label="Sudah Pulang Hari Ini" :value="summary.checked_out_today" tone="green" icon="M5 13l4 4L19 7" />
                <MetricSummaryCard label="Total Bulan Ini" :value="summary.month_total" tone="slate" icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </div>

            <section class="panel-card">
                <form class="filter-bar" @submit.prevent="applyFilters">
                    <input v-model="filterState.search" type="search" placeholder="Cari nama tamu, instansi, tujuan, kode..." aria-label="Cari tamu" />
                    <select v-model="filterState.category" aria-label="Filter kategori tamu">
                        <option value="">Semua Kategori</option>
                        <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                    </select>
                    <select v-model="filterState.status" aria-label="Filter status kunjungan">
                        <option value="">Semua Status</option>
                        <option value="checked_in">Sedang Berkunjung</option>
                        <option value="checked_out">Sudah Pulang</option>
                    </select>
                    <input v-model="filterState.date_from" type="date" aria-label="Tanggal mulai" />
                    <input v-model="filterState.date_to" type="date" aria-label="Tanggal akhir" />
                    <AppButton type="submit" variant="secondary">Terapkan</AppButton>
                </form>

                <div v-if="visits.length" class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode &amp; Pengunjung</th>
                                <th>Kategori &amp; Kontak</th>
                                <th>Tujuan &amp; Keperluan</th>
                                <th>Datang / Pulang</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="v in visits" :key="v.id">
                                <td>
                                    <strong>{{ v.visitor_name }}</strong>
                                    <a v-if="v.photo_url" :href="v.photo_url" target="_blank" rel="noopener noreferrer" class="block text-xs text-emerald-700 underline">Lihat foto kunjungan</a>
                                    <div class="sub-text">{{ v.visit_code }} · {{ v.visitor_organization || 'Perorangan' }}</div>
                                </td>
                                <td>
                                    <span>{{ v.category_label }}</span>
                                    <div class="sub-text">{{ v.visitor_phone || '-' }}</div>
                                </td>
                                <td>
                                    <strong>{{ v.host_name }}</strong>
                                    <div class="sub-text">{{ v.purpose }}</div>
                                </td>
                                <td>
                                    <div>Masuk: {{ formatDate(v.checked_in_at) }}</div>
                                    <div v-if="v.checked_out_at" class="sub-text">Pulang: {{ formatDate(v.checked_out_at) }}</div>
                                </td>
                                <td>
                                    <StatusBadge :status="v.status === 'checked_out' ? 'completed' : 'in_review'" :label="v.status === 'checked_out' ? 'Pulang' : 'Berkunjung'" size="sm" />
                                </td>
                                <td>
                                    <AppButton v-if="v.status === 'checked_in'" size="sm" variant="secondary" @click="checkOut(v)">
                                        Catat Pulang
                                    </AppButton>
                                    <span v-else class="sub-text">Selesai</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <StateFeedback v-else state="empty" title="Belum ada catatan tamu" description="Catatan kunjungan buku tamu yang sesuai filter akan tampil di sini." />

                <div v-if="pagination.last > 1" class="pager">
                    <span>Halaman {{ pagination.current }} dari {{ pagination.last }}</span>
                    <div class="pager-links">
                        <Link v-if="pagination.previous" :href="pagination.previous" class="ui-button ui-button--secondary ui-button--sm">Sebelumnya</Link>
                        <Link v-if="pagination.next" :href="pagination.next" class="ui-button ui-button--secondary ui-button--sm">Berikutnya</Link>
                    </div>
                </div>
            </section>

            <div v-if="showCreateModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="gb-modal-title" @click.self="showCreateModal = false" @keydown.esc="showCreateModal = false">
                <div class="modal-card">
                    <header class="modal-head">
                        <h2 id="gb-modal-title">Catat Kunjungan Tamu</h2>
                        <button type="button" class="close-btn" @click="showCreateModal = false" aria-label="Tutup">×</button>
                    </header>
                    <form class="modal-form" @submit.prevent="submitVisit">
                        <FormField id="adm-gb-name" label="Nama Pengunjung" required :error="form.errors.visitor_name" v-slot="{ inputId }">
                            <input :id="inputId" v-model="form.visitor_name" type="text" required />
                        </FormField>
                        <FormField id="adm-gb-org" label="Instansi / Asal" :error="form.errors.visitor_organization" v-slot="{ inputId }">
                            <input :id="inputId" v-model="form.visitor_organization" type="text" />
                        </FormField>
                        <FormField id="adm-gb-phone" label="Nomor Kontak" :error="form.errors.visitor_phone" v-slot="{ inputId }">
                            <input :id="inputId" v-model="form.visitor_phone" type="text" />
                        </FormField>
                        <FormField id="adm-gb-cat" label="Kategori" required :error="form.errors.category" v-slot="{ inputId }">
                            <select :id="inputId" v-model="form.category">
                                <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                            </select>
                        </FormField>
                        <FormField id="adm-gb-host-sel" label="Pegawai Tujuan" v-slot="{ inputId }">
                            <select :id="inputId" v-model="form.host_teacher_id">
                                <option value="">-- Pilih Pegawai atau Isi Manual --</option>
                                <option v-for="h in hosts" :key="h.id" :value="h.id">{{ h.name }}</option>
                            </select>
                        </FormField>
                        <FormField id="adm-gb-host-man" label="Nama Tujuan Lain / Unit" v-slot="{ inputId }">
                            <input :id="inputId" v-model="form.host_name" type="text" />
                        </FormField>
                        <FormField id="adm-gb-purpose" label="Keperluan" required :error="form.errors.purpose" v-slot="{ inputId }">
                            <input :id="inputId" v-model="form.purpose" type="text" required />
                        </FormField>
                        <FormField id="adm-gb-notes" label="Catatan" v-slot="{ inputId }">
                            <textarea :id="inputId" v-model="form.notes" rows="2" />
                        </FormField>
                        <div class="modal-footer">
                            <AppButton type="submit" :disabled="form.processing">Simpan Kunjungan</AppButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.guestbook-workspace { display: grid; gap: 1.25rem; }
.workspace-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1rem; }
.workspace-head h1 { margin: 0; font-size: 1.5rem; }
.workspace-head p { margin: .25rem 0 0; color: var(--ink-soft, #536174); }
.head-actions { display: flex; flex-wrap: wrap; gap: .6rem; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .85rem; }
.panel-card { background: var(--surface, #fff); border: 1px solid var(--line, #dee4ee); border-radius: 14px; padding: 1.15rem; display: grid; gap: 1rem; }
.filter-bar { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }
.filter-bar input, .filter-bar select { min-height: 40px; padding: .4rem .75rem; border: 1px solid var(--line-strong, #c8d1df); border-radius: 8px; font: inherit; background: var(--surface, #fff); }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.data-table th, .data-table td { padding: .7rem .65rem; border-bottom: 1px solid var(--line, #dee4ee); text-align: left; vertical-align: top; }
.data-table th { text-align: center; }
.sub-text { font-size: .78rem; color: var(--ink-soft, #536174); margin-top: .15rem; }
.pager { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .75rem; font-size: .85rem; }
.pager-links { display: flex; gap: .5rem; }
.modal-backdrop { position: fixed; inset: 0; background: rgb(15 23 42 / 55%); display: grid; place-items: center; padding: 1rem; z-index: 60; }
.modal-card { width: min(620px, 100%); max-height: min(90dvh, 800px); overflow-y: auto; background: var(--surface, #fff); border-radius: 16px; padding: 1.35rem; display: grid; gap: 1rem; }
.modal-head { display: flex; justify-content: space-between; align-items: center; }
.modal-head h2 { margin: 0; font-size: 1.15rem; }
.close-btn { min-width: 40px; min-height: 40px; border: 0; background: transparent; font-size: 1.5rem; cursor: pointer; line-height: 1; }
.modal-form { display: grid; gap: .85rem; }
.modal-footer { display: flex; justify-content: flex-end; }
</style>
