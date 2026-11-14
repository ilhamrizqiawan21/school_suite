<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    token: string;
    entity: 'siswa' | 'guru';
    index_url: string;
    confirm_url: string;
    rows: Array<{ row: number; identifier: string; name: string; gender: string; status?: string; valid: boolean; errors: string[] }>;
    warnings: string[];
    valid_count: number;
    invalid_count: number;
}>();

const form = useForm({ token: props.token });

const confirmImport = () => {
    form.post(props.confirm_url);
};
</script>

<template>
    <Head :title="`Preview impor ${entity}`" />
    <AppLayout :title="`Preview impor ${entity}`">
        <main class="view import-preview">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Impor {{ entity }} · periksa sebelum menyimpan</p>
                    <h1>Preview hasil validasi</h1>
                    <p>{{ valid_count }} baris valid · {{ invalid_count }} baris perlu diperbaiki. Data belum disimpan.</p>
                </div>
            </section>

            <section v-if="warnings.length" class="warning-panel" role="status">
                <strong>Catatan pemetaan impor</strong>
                <ul><li v-for="warning in warnings" :key="warning">{{ warning }}</li></ul>
            </section>

            <section class="panel preview-table">
                <div class="table-scroll" tabindex="0" role="region" aria-label="Tabel data">
                    <table class="app-table">
                        <thead><tr><th scope="col">Baris</th><th scope="col">{{ entity === 'siswa' ? 'NIS' : 'NIP' }}</th><th scope="col">Nama</th><th scope="col">Gender</th><th scope="col" v-if="entity === 'siswa'">Status</th><th scope="col">Status / masalah</th></tr></thead>
                        <tbody>
                            <tr v-for="row in rows" :key="row.row" :class="row.valid ? 'row-valid' : 'row-invalid'">
                                <td>{{ row.row }}</td><td>{{ row.identifier || '—' }}</td><td>{{ row.name || '—' }}</td><td>{{ row.gender || '—' }}</td><td v-if="entity === 'siswa'">{{ row.status || '—' }}</td>
                                <td><span v-if="row.valid" class="valid-label">Valid</span><ul v-else><li v-for="error in row.errors" :key="error">{{ error }}</li></ul></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="actions">
                <Link :href="index_url" class="secondary-button ui-button ui-button--secondary ui-button--md">Batal dan kembali</Link>
                <AppButton variant="primary" type="button" class="primary-button" :disabled="form.processing || invalid_count > 0 || valid_count === 0" @click="confirmImport">
                    {{ form.processing ? 'Mengimpor…' : `Konfirmasi impor ${valid_count} ${entity}` }}
                </AppButton>
            </footer>
        </main>
    </AppLayout>
</template>

<style scoped>
.import-preview{display:grid;gap:1rem}.warning-panel{padding:1rem 1.25rem;border:1px solid #f3cc75;border-radius:.75rem;background:#fffbeb;color:#854d0e}.warning-panel ul{margin:.5rem 0 0;padding-left:1.1rem;list-style:disc}.preview-table{padding:0;overflow:hidden}.table-scroll{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:var(--type-md)}th,td{padding:.8rem 1rem;text-align:left;border-bottom:1px solid #e2e8f0;vertical-align:top}th{background:#f8fafc;color:#475569;font-size:var(--type-meta);text-transform:uppercase;letter-spacing:.04em}.row-valid td:first-child{border-left:4px solid #16a34a}.row-invalid td:first-child{border-left:4px solid #dc2626}.valid-label{color:#15803d;font-weight:var(--weight-semibold)}ul{margin:0;padding-left:1.1rem;color:#b91c1c;list-style:disc}.actions{display:flex;justify-content:flex-end;align-items:center;gap:.75rem}
:global(html[data-theme='dark']) .warning-panel{border-color:color-mix(in srgb,var(--gold) 50%,var(--line));background:var(--gold-soft);color:var(--gold)}
:global(html[data-theme='dark']) th,:global(html[data-theme='dark']) td{border-bottom-color:var(--line)}
:global(html[data-theme='dark']) th{background:var(--surface-subtle);color:var(--ink-soft)}
:global(html[data-theme='dark']) .row-valid td:first-child{border-left-color:var(--green)}
:global(html[data-theme='dark']) .row-invalid td:first-child{border-left-color:var(--red)}
:global(html[data-theme='dark']) .valid-label{color:var(--green)}
:global(html[data-theme='dark']) .import-preview table ul{color:var(--red)}
</style>
