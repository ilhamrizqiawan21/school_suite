<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    token: string;
    rows: { row: number; valid: boolean; errors: string[]; prompt: string; type: string; correct_answer: string }[];
    valid_count: number;
    invalid_count: number;
}>();

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
                <article v-for="row in rows" :key="row.row" :class="row.valid ? 'valid-row' : 'invalid-row'">
                    <div class="row-head"><strong>Baris {{ row.row }}</strong><span>{{ row.valid ? 'Valid' : 'Perlu diperbaiki' }}</span></div>
                    <h2>{{ row.prompt || '(Teks soal kosong)' }}</h2>
                    <p>{{ row.type }}<span v-if="row.correct_answer"> · Kunci: {{ row.correct_answer }}</span></p>
                    <ul v-if="row.errors.length"><li v-for="error in row.errors" :key="error">{{ error }}</li></ul>
                </article>
            </section>
            <footer class="actions">
                <Link href="/penilaian/bank-soal" class="secondary-button">Batal</Link>
                <button class="primary-button" :disabled="invalid_count > 0 || valid_count === 0" @click="confirmImport">Konfirmasi impor {{ valid_count }} soal</button>
            </footer>
        </main>
    </AppLayout>
</template>

<style scoped>
.import-page{display:grid;gap:1rem}.preview-list{display:grid;gap:.75rem;padding:1rem}.preview-list article{padding:1rem;border:1px solid #cbd5e1;border-radius:.75rem}.preview-list .valid-row{border-left:4px solid #16a34a}.preview-list .invalid-row{border-left:4px solid #dc2626}.row-head,.actions{display:flex;align-items:center;justify-content:space-between;gap:.75rem}.row-head span{font-size:.78rem;font-weight:750}.preview-list h2{margin-top:.4rem;font-weight:750;overflow-wrap:anywhere}.preview-list p{color:#64748b;font-size:.82rem}.preview-list ul{margin:.5rem 0 0 1.1rem;color:#b91c1c;font-size:.82rem;list-style:disc}.actions{justify-content:flex-end}
</style>
