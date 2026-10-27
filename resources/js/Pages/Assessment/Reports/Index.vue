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
            <section v-if="is_student" class="student-report-card panel" aria-labelledby="student-report-title">
                <div class="student-report-intro">
                    <div class="report-document-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3.75h8.25L19 8.5v11.75H6a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3.75V9h5M8 13h8M8 16.5h6"/></svg>
                    </div>
                    <div>
                        <span class="report-eyebrow">DOKUMEN HASIL BELAJAR</span>
                        <h2 id="student-report-title">Rapor {{ active_term?.name ?? 'semester aktif' }}</h2>
                        <p>{{ classes[0]?.name ?? 'Kelas belum tersedia' }} · {{ active_term?.name ?? 'Belum ada semester aktif' }}</p>
                    </div>
                    <span v-if="reports[0]" class="report-status" :class="reports[0].status">{{ statusLabel(reports[0].status) }}</span>
                </div>

                <div v-if="reports[0]" class="student-report-details">
                    <div><span>Peserta didik</span><strong>{{ reports[0].student_name }}</strong><small>NIS {{ reports[0].nis }}</small></div>
                    <div><span>Tanggal terbit</span><strong>{{ dateLabel(reports[0].published_at) }}</strong><small v-if="reports[0].scheme_snapshot?.version">Skema penilaian v{{ reports[0].scheme_snapshot.version }}</small></div>
                </div>

                <div v-if="reports[0]" class="student-report-actions">
                    <Link :href="`/penilaian/rapor/${reports[0].id}`" class="primary-button">Lihat rapor</Link>
                    <a :href="`/penilaian/rapor/${reports[0].id}/pdf`" class="secondary-button">Unduh PDF</a>
                </div>
                <div v-else class="student-report-empty">
                    <strong>Rapor belum tersedia</strong>
                    <p>Rapor akan muncul di sini setelah diterbitkan oleh sekolah untuk semester aktif.</p>
                </div>
                <p class="student-report-help">Rapor yang sudah terbit dikunci untuk menjaga riwayat hasil belajar. Pemeriksaan data tersedia di halaman detail rapor.</p>
            </section>

            <section v-else class="report-register panel">
                <div class="register-context">
                    <label for="report-class">Kelas / rombel<select id="report-class" v-model="selectedClassId" @change="changeClass"><option value="" disabled>Pilih kelas</option><option v-for="cls in classes" :key="cls.id" :value="String(cls.id)">{{ cls.name }}</option></select></label>
                    <div><span>Semester aktif</span><strong>{{ active_term?.name ?? 'Belum ada semester aktif' }}</strong></div>
                    <div class="report-count"><strong>{{ published }} <small>/ {{ reports.length }}</small></strong><span>rapor sudah terbit</span></div>
                </div>
                <div class="register-toolbar"><div><h2>Daftar rapor kelas</h2><p>Periksa isi rapor sebelum menerbitkannya.</p></div><div class="register-filters"><input v-model="search" type="search" aria-label="Cari siswa" placeholder="Cari nama atau NIS…"><select v-model="status" aria-label="Status rapor"><option value="">Semua status</option><option value="draft">Draf</option><option value="reviewed">Ditinjau</option><option value="published">Terbit</option></select></div></div>
                <div class="register-scroll"><table class="report-table"><thead><tr><th>Peserta didik</th><th>Status</th><th>Tanggal terbit</th><th class="action-column">Dokumen</th></tr></thead><tbody>
                    <tr v-for="report in visible" :key="report.id"><td><strong>{{ report.student_name }}</strong><small>NIS {{ report.nis }} <span v-if="report.scheme_snapshot?.version">· Skema v{{ report.scheme_snapshot.version }}</span></small></td><td><span class="report-status" :class="report.status">{{ statusLabel(report.status) }}</span></td><td>{{ dateLabel(report.published_at) }}</td><td><div class="report-actions"><Link :href="`/penilaian/rapor/${report.id}`" class="secondary-button">Lihat rapor</Link><button v-if="can_publish && report.status !== 'published'" class="primary-button" :disabled="publishingId !== null" @click="publishReport(report)">{{ publishingId === report.id ? 'Menerbitkan…' : 'Terbitkan' }}</button></div></td></tr>
                    <tr v-if="!visible.length"><td colspan="4" class="register-empty"><strong>{{ search || status ? 'Tidak ada rapor yang cocok' : 'Belum ada rapor' }}</strong><p>{{ !active_term ? 'Aktifkan semester di Pengaturan Akademik terlebih dahulu.' : search || status ? 'Ubah pencarian atau status untuk melihat hasil lainnya.' : 'Pilih kelas, lalu susun draf rapor untuk memulai pemeriksaan.' }}</p></td></tr>
                </tbody></table></div>
                <footer class="register-footer"><span>{{ filtered.length ? (page - 1) * 15 + 1 : 0 }}–{{ Math.min(page * 15, filtered.length) }} dari {{ filtered.length }} rapor</span><nav v-if="pages > 1" aria-label="Halaman rapor"><button class="secondary-button" :disabled="page <= 1" @click="page--">Sebelumnya</button><span>{{ page }} / {{ pages }}</span><button class="secondary-button" :disabled="page >= pages" @click="page++">Berikutnya</button></nav></footer>
            </section>
            <p v-if="!is_student" class="report-help">Rapor terbit dikunci untuk menjaga riwayat hasil belajar. Kode pemeriksaan data tersedia di dalam dokumen rapor.</p>
        </main>
    </AppLayout>
</template>
<style scoped>
.reports-page{display:grid;gap:1.4rem}.reports-heading,.report-actions,.register-toolbar,.register-footer,.register-footer nav{display:flex;align-items:center;justify-content:space-between;gap:.75rem}.reports-heading h1{font-size:1.65rem;font-weight:800;color:var(--ink)}.reports-heading p,.register-toolbar p,.report-help,.student-report-help{color:var(--ink-soft);font-size:.84rem;margin-top:.35rem}.report-actions{justify-content:flex-end;flex-wrap:wrap}.report-register,.student-report-card{overflow:hidden}.student-report-card{padding:0}.student-report-intro{display:flex;align-items:center;gap:1rem;padding:1.5rem;background:linear-gradient(135deg,var(--surface-soft,var(--surface)),var(--surface));border-bottom:1px solid var(--line)}.report-document-icon{display:grid;place-items:center;width:3rem;height:3rem;flex:0 0 3rem;border-radius:.85rem;color:#fff;background:var(--primary);box-shadow:0 8px 18px color-mix(in srgb,var(--primary) 22%,transparent)}.report-document-icon svg{width:1.45rem;height:1.45rem}.report-eyebrow{display:block;color:var(--primary);font-size:.68rem;font-weight:800;letter-spacing:.09em}.student-report-intro h2{margin-top:.2rem;color:var(--ink);font-size:1.25rem;font-weight:800}.student-report-intro p{margin-top:.2rem;color:var(--ink-soft);font-size:.82rem}.student-report-intro .report-status{margin-left:auto}.student-report-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;padding:1.4rem 1.5rem}.student-report-details>div{display:grid;gap:.28rem;padding:.9rem 1rem;border:1px solid var(--line);border-radius:.75rem;background:var(--surface)}.student-report-details span{color:var(--ink-soft);font-size:.72rem}.student-report-details strong{color:var(--ink);font-size:.95rem}.student-report-details small{color:var(--ink-soft);font-size:.72rem}.student-report-actions{display:flex;gap:.65rem;flex-wrap:wrap;padding:0 1.5rem 1.25rem}.student-report-help{padding:0 1.5rem 1.3rem;margin:0}.student-report-empty{padding:1.5rem;color:var(--ink);border-top:1px solid var(--line)}.student-report-empty p{margin-top:.35rem;color:var(--ink-soft);font-size:.82rem}.register-context{display:grid;grid-template-columns:1fr 1fr auto;gap:1.5rem;padding:1.5rem;background:var(--surface-soft,var(--surface));border-bottom:1px solid var(--line)}.register-context label,.register-context>div{display:grid;gap:.35rem;font-size:.75rem;color:var(--ink-soft)}.register-context strong{font-size:.95rem;color:var(--ink)}.register-context .report-count{text-align:right}.report-count>strong{font-size:1.7rem;font-variant-numeric:tabular-nums}.report-count small{font-size:1rem;color:var(--ink-soft)}.register-toolbar{padding:1.25rem 1.5rem;flex-wrap:wrap}.register-toolbar h2{font-weight:750;color:var(--ink)}.register-filters{display:flex;gap:.6rem;flex-wrap:wrap}input,select{min-height:42px;padding:.55rem .7rem;border:1px solid var(--line-strong);border-radius:.6rem;background:var(--surface);color:var(--ink);font-size:.82rem}.register-context select{width:100%}.register-scroll{overflow-x:auto}.report-table{width:100%;min-width:640px;border-collapse:collapse;text-align:left;font-size:.82rem;color:var(--ink)}.report-table th{padding:.8rem 1.5rem;background:var(--surface-soft,var(--surface));color:var(--ink-soft);font-size:.7rem;font-weight:650;border-block:1px solid var(--line)}.report-table td{padding:1rem 1.5rem;border-bottom:1px solid var(--line)}.report-table td>strong,.report-table small{display:block}.report-table small{margin-top:.3rem;color:var(--ink-soft);font-size:.72rem}.action-column{text-align:right}.report-status{display:inline-flex;padding:.3rem .65rem;border-radius:2rem;font-size:.7rem;font-weight:750;background:var(--surface);border:1px solid var(--line);color:var(--ink-soft)}.report-status.published{color:#176746;background:#e9f6ee;border-color:#c8e6d3}.report-status.draft{color:#855b16;background:#fff7e5;border-color:#f1dfb3}.register-empty{text-align:center;padding:3rem!important}.register-empty p{margin-top:.5rem;color:var(--ink-soft)}.register-footer{padding:1rem 1.5rem;color:var(--ink-soft);font-size:.75rem}.report-error{padding:1rem;color:#9f2525;background:#fff1f0;border:1px solid #fecaca;border-radius:.75rem}.report-error a{text-decoration:underline}.report-help{margin:0}.primary-button:disabled,.secondary-button:disabled{opacity:.5;cursor:not-allowed}@media(max-width:700px){.reports-heading{align-items:stretch;flex-direction:column}.reports-heading>.report-actions{justify-content:flex-start}.student-report-intro{align-items:flex-start;flex-wrap:wrap}.student-report-intro .report-status{margin-left:4rem}.student-report-details{grid-template-columns:1fr;padding:1rem}.student-report-actions,.student-report-help{padding-left:1rem;padding-right:1rem}.register-context{grid-template-columns:1fr 1fr;padding:1rem;gap:1rem}.register-context .report-count{grid-column:1/-1;text-align:left}.register-toolbar{padding:1rem}.register-filters{width:100%}.register-filters input{min-width:0;flex:1}.register-footer{align-items:flex-start;flex-direction:column}.register-footer nav{flex-wrap:wrap}}
</style>
