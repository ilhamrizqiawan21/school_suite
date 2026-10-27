<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';
interface ReportItem { id: number; student_name: string; nis: string; status: 'draft' | 'reviewed' | 'published'; published_at: string | null; scheme_snapshot: { version?: number }; }
const props = defineProps<{ active_term?: { id: number; name: string } | null; classes: { id: number; name: string; grade_level: number }[]; selected_class_id?: string; reports: ReportItem[]; is_student: boolean; can_publish: boolean }>();
const selectedClassId = ref(props.selected_class_id ?? '');
const search = ref('');
const status = ref('');
const page = ref(1);
const publishingId = ref<number | null>(null);
const filtered = computed(() => props.reports.filter(r => (!status.value || r.status === status.value) && `${r.student_name} ${r.nis}`.toLocaleLowerCase('id-ID').includes(search.value.trim().toLocaleLowerCase('id-ID'))));
const pages = computed(() => Math.max(1, Math.ceil(filtered.value.length / 15)));
const visible = computed(() => filtered.value.slice((page.value - 1) * 15, page.value * 15));
const published = computed(() => props.reports.filter(r => r.status === 'published').length);
watch([search, status, () => props.reports], () => { page.value = 1; });
watch(() => props.selected_class_id, value => { selectedClassId.value = value ?? ''; });
const changeClass = () => router.get('/penilaian/rapor', { class_group_id: selectedClassId.value || undefined }, { preserveState: true, preserveScroll: true });
const generateForm = useForm({ class_group_id: selectedClassId.value, term_id: props.active_term?.id ?? '' });
const generateBatch = () => {
    if (!props.active_term || !selectedClassId.value) return;
    generateForm.class_group_id = selectedClassId.value;
    generateForm.term_id = props.active_term.id;
    generateForm.post('/penilaian/rapor/generate-batch', { preserveScroll: true });
};
const publishReport = (report: ReportItem) => {
    if (publishingId.value || !confirm(`Terbitkan rapor ${report.student_name}? Rapor yang sudah terbit akan dikunci dan tidak dapat diubah.`)) return;
    publishingId.value = report.id;
    router.post(`/penilaian/rapor/${report.id}/publish`, {}, { preserveScroll: true, onFinish: () => { publishingId.value = null; } });
};
const dateLabel = (date: string | null) => date ? new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Belum diterbitkan';
const statusLabel = (value: string) => ({ draft: 'Draf', reviewed: 'Ditinjau', published: 'Terbit' })[value] ?? value;
</script>

<template>
    <Head title="Rapor siswa" />
    <AppLayout>
        <main class="view reports-page">
            <header class="reports-heading">
                <div><h1>{{ is_student ? 'Rapor saya' : 'Rapor siswa' }}</h1><p>{{ is_student ? 'Hasil belajar dan catatan perkembangan selama satu semester.' : 'Susun draf, periksa hasil belajar, lalu terbitkan rapor kelas.' }}</p></div>
                <div class="report-actions">
                    <Link v-if="!is_student && selectedClassId && active_term" :href="`/penilaian/ledger?class_group_id=${selectedClassId}&term_id=${active_term.id}`" class="secondary-button">Ledger nilai</Link>
                    <button v-if="can_publish" class="primary-button" :disabled="generateForm.processing || !selectedClassId || !active_term" @click="generateBatch">{{ generateForm.processing ? 'Menyusun draf…' : 'Susun draf kelas' }}</button>
                </div>
            </header>
            <div v-if="Object.keys(generateForm.errors).length" class="report-error" role="alert"><p v-for="(error, key) in generateForm.errors" :key="key">{{ error }}</p><Link href="/penilaian/skema">Periksa skema penilaian</Link></div>
            <section class="report-register panel">
                <div class="register-context">
                    <label v-if="!is_student" for="report-class">Kelas / rombel<select id="report-class" v-model="selectedClassId" @change="changeClass"><option value="" disabled>Pilih kelas</option><option v-for="cls in classes" :key="cls.id" :value="String(cls.id)">{{ cls.name }}</option></select></label>
                    <div v-else><span>Kelas</span><strong>{{ classes[0]?.name ?? 'Belum terdaftar' }}</strong></div>
                    <div><span>Semester aktif</span><strong>{{ active_term?.name ?? 'Belum ada semester aktif' }}</strong></div>
                    <div class="report-count"><strong>{{ published }} <small>/ {{ reports.length }}</small></strong><span>rapor sudah terbit</span></div>
                </div>
                <div class="register-toolbar"><div><h2>{{ is_student ? 'Dokumen rapor' : 'Daftar rapor kelas' }}</h2><p>Periksa isi rapor sebelum menerbitkannya.</p></div><div v-if="!is_student" class="register-filters"><input v-model="search" type="search" aria-label="Cari siswa" placeholder="Cari nama atau NIS…"><select v-model="status" aria-label="Status rapor"><option value="">Semua status</option><option value="draft">Draf</option><option value="reviewed">Ditinjau</option><option value="published">Terbit</option></select></div></div>
                <div class="register-scroll"><table class="report-table"><thead><tr><th>Peserta didik</th><th>Status</th><th>Tanggal terbit</th><th class="action-column">Dokumen</th></tr></thead><tbody>
                    <tr v-for="report in visible" :key="report.id"><td><strong>{{ report.student_name }}</strong><small>NIS {{ report.nis }} <span v-if="report.scheme_snapshot?.version">· Skema v{{ report.scheme_snapshot.version }}</span></small></td><td><span class="report-status" :class="report.status">{{ statusLabel(report.status) }}</span></td><td>{{ dateLabel(report.published_at) }}</td><td><div class="report-actions"><Link :href="`/penilaian/rapor/${report.id}`" class="secondary-button">Lihat rapor</Link><button v-if="can_publish && report.status !== 'published'" class="primary-button" :disabled="publishingId !== null" @click="publishReport(report)">{{ publishingId === report.id ? 'Menerbitkan…' : 'Terbitkan' }}</button></div></td></tr>
                    <tr v-if="!visible.length"><td colspan="4" class="register-empty"><strong>{{ search || status ? 'Tidak ada rapor yang cocok' : 'Belum ada rapor' }}</strong><p>{{ !active_term ? 'Aktifkan semester di Pengaturan Akademik terlebih dahulu.' : search || status ? 'Ubah pencarian atau status untuk melihat hasil lainnya.' : is_student ? 'Rapor akan tersedia setelah diterbitkan oleh sekolah.' : 'Pilih kelas, lalu susun draf rapor untuk memulai pemeriksaan.' }}</p></td></tr>
                </tbody></table></div>
                <footer class="register-footer"><span>{{ filtered.length ? (page - 1) * 15 + 1 : 0 }}–{{ Math.min(page * 15, filtered.length) }} dari {{ filtered.length }} rapor</span><nav v-if="pages > 1" aria-label="Halaman rapor"><button class="secondary-button" :disabled="page <= 1" @click="page--">Sebelumnya</button><span>{{ page }} / {{ pages }}</span><button class="secondary-button" :disabled="page >= pages" @click="page++">Berikutnya</button></nav></footer>
            </section>
            <p class="report-help">Rapor terbit dikunci untuk menjaga riwayat hasil belajar. Kode pemeriksaan data tersedia di dalam dokumen rapor.</p>
        </main>
    </AppLayout>
</template>
<style scoped>
.reports-page{display:grid;gap:1.4rem}.reports-heading,.report-actions,.register-toolbar,.register-footer,.register-footer nav{display:flex;align-items:center;justify-content:space-between;gap:.75rem}.reports-heading h1{font-size:1.65rem;font-weight:800;color:var(--ink)}.reports-heading p,.register-toolbar p,.report-help{color:var(--ink-soft);font-size:.84rem;margin-top:.35rem}.report-actions{justify-content:flex-end;flex-wrap:wrap}.report-register{overflow:hidden}.register-context{display:grid;grid-template-columns:1fr 1fr auto;gap:1.5rem;padding:1.5rem;background:var(--surface-soft,var(--surface));border-bottom:1px solid var(--line)}.register-context label,.register-context>div{display:grid;gap:.35rem;font-size:.75rem;color:var(--ink-soft)}.register-context strong{font-size:.95rem;color:var(--ink)}.register-context .report-count{text-align:right}.report-count>strong{font-size:1.7rem;font-variant-numeric:tabular-nums}.report-count small{font-size:1rem;color:var(--ink-soft)}.register-toolbar{padding:1.25rem 1.5rem;flex-wrap:wrap}.register-toolbar h2{font-weight:750;color:var(--ink)}.register-filters{display:flex;gap:.6rem;flex-wrap:wrap}input,select{min-height:42px;padding:.55rem .7rem;border:1px solid var(--line-strong);border-radius:.6rem;background:var(--surface);color:var(--ink);font-size:.82rem}.register-context select{width:100%}.register-scroll{overflow-x:auto}.report-table{width:100%;min-width:640px;border-collapse:collapse;text-align:left;font-size:.82rem;color:var(--ink)}.report-table th{padding:.8rem 1.5rem;background:var(--surface-soft,var(--surface));color:var(--ink-soft);font-size:.7rem;font-weight:650;border-block:1px solid var(--line)}.report-table td{padding:1rem 1.5rem;border-bottom:1px solid var(--line)}.report-table td>strong,.report-table small{display:block}.report-table small{margin-top:.3rem;color:var(--ink-soft);font-size:.72rem}.action-column{text-align:right}.report-status{display:inline-flex;padding:.3rem .65rem;border-radius:2rem;font-size:.7rem;font-weight:750;background:var(--surface);border:1px solid var(--line);color:var(--ink-soft)}.report-status.published{color:#176746;background:#e9f6ee;border-color:#c8e6d3}.report-status.draft{color:#855b16;background:#fff7e5;border-color:#f1dfb3}.register-empty{text-align:center;padding:3rem!important}.register-empty p{margin-top:.5rem;color:var(--ink-soft)}.register-footer{padding:1rem 1.5rem;color:var(--ink-soft);font-size:.75rem}.report-error{padding:1rem;color:#9f2525;background:#fff1f0;border:1px solid #fecaca;border-radius:.75rem}.report-error a{text-decoration:underline}.report-help{margin:0}.primary-button:disabled,.secondary-button:disabled{opacity:.5;cursor:not-allowed}@media(max-width:700px){.reports-heading{align-items:stretch;flex-direction:column}.reports-heading>.report-actions{justify-content:flex-start}.register-context{grid-template-columns:1fr 1fr;padding:1rem;gap:1rem}.register-context .report-count{grid-column:1/-1;text-align:left}.register-toolbar{padding:1rem}.register-filters{width:100%}.register-filters input{min-width:0;flex:1}.register-footer{align-items:flex-start;flex-direction:column}.register-footer nav{flex-wrap:wrap}}
</style>
