<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    token: string;
    rows: { row: number; valid: boolean; errors: string[]; prompt: string; type: string; correct_answer: string }[];
    valid_count: number;
    invalid_count: number;
}>();

const onlyErrors = ref(false);
const page = ref(1);
const pageSize = 25;
const visibleRows = computed(() => props.rows.filter((row) => !onlyErrors.value || !row.valid));
const pageCount = computed(() => Math.max(1, Math.ceil(visibleRows.value.length / pageSize)));
const pagedRows = computed(() => visibleRows.value.slice((page.value - 1) * pageSize, page.value * pageSize));
function toggleErrors(value: boolean) { onlyErrors.value = value; page.value = 1; }

function confirmImport() {
    router.post(`/penilaian/bank-soal/import/${props.token}/confirm`);
}
</script>

<template>
    <Head title="Preview impor bank soal" />
    <AppLayout title="Preview impor bank soal">
        <main class="view import-page">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Impor XLSX · langkah 2 dari 2</p>
                    <h1>Periksa hasil validasi</h1>
                    <p>{{ valid_count }} baris valid · {{ invalid_count }} baris perlu diperbaiki. Impor hanya dilanjutkan bila semua baris valid.</p>
                </div>
            </section>
            <section class="panel preview-list">
                <div class="preview-toolbar"><span>Menampilkan {{ visibleRows.length }} dari {{ rows.length }} baris</span><label><input type="checkbox" :checked="onlyErrors" @change="toggleErrors(($event.target as HTMLInputElement).checked)" /> Hanya baris bermasalah</label></div>
                <article v-for="row in pagedRows" :key="row.row" :class="row.valid ? 'valid-row' : 'invalid-row'">
                    <div class="row-head"><strong>Baris {{ row.row }}</strong><span>{{ row.valid ? 'Valid' : 'Perlu diperbaiki' }}</span></div>
                    <h2>{{ row.prompt || '(Teks soal kosong)' }}</h2>
                    <p>{{ row.type }}<span v-if="row.correct_answer"> · Kunci: {{ row.correct_answer }}</span></p>
                    <ul v-if="row.errors.length"><li v-for="error in row.errors" :key="error">{{ error }}</li></ul>
                </article>
                <p v-if="!pagedRows.length" class="empty-state">Tidak ada baris pada filter ini.</p>
                <nav v-if="pageCount > 1" class="pagination" aria-label="Halaman preview impor"><button type="button" :disabled="page === 1" @click="page--">← Sebelumnya</button><span>Halaman {{ page }} / {{ pageCount }}</span><button type="button" :disabled="page === pageCount" @click="page++">Berikutnya →</button></nav>
            </section>
            <footer class="actions">
                <Link href="/penilaian/bank-soal" class="secondary-button">Batal</Link>
                <button class="primary-button" :disabled="invalid_count > 0 || valid_count === 0" @click="confirmImport">Konfirmasi impor {{ valid_count }} soal</button>
            </footer>
        </main>
    </AppLayout>
</template>

<style scoped>
.import-page{display:grid;gap:1rem}.preview-list{display:grid;gap:.75rem;padding:1rem}.preview-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.7rem;padding:.25rem 0 .75rem;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:.82rem}.preview-toolbar label{display:flex;align-items:center;gap:.4rem;color:#334155;font-weight:650}.preview-list article{padding:1rem;border:1px solid #cbd5e1;border-radius:.75rem}.preview-list .valid-row{border-left:4px solid #16a34a}.preview-list .invalid-row{border-left:4px solid #dc2626}.row-head,.actions{display:flex;align-items:center;justify-content:space-between;gap:.75rem}.row-head span{font-size:.78rem;font-weight:750}.preview-list h2{margin-top:.4rem;font-weight:750;overflow-wrap:anywhere}.preview-list p{color:#64748b;font-size:.82rem}.preview-list ul{margin:.5rem 0 0 1.1rem;color:#b91c1c;font-size:.82rem;list-style:disc}.actions{justify-content:flex-end}.pagination{display:flex;align-items:center;justify-content:center;gap:.7rem;padding-top:.5rem;color:#64748b;font-size:.8rem}.pagination button{border:1px solid #cbd5e1;border-radius:.5rem;background:#fff;padding:.45rem .7rem;color:#334155}.pagination button:disabled{opacity:.45}
@media(max-width:560px){.preview-toolbar{align-items:flex-start;flex-direction:column}}
</style>
