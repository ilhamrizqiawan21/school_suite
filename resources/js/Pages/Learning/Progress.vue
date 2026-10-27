<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface ProgressRow {
    row_key: string;
    enrollment_id: number;
    subject_id: number;
    nis: string;
    student_name: string;
    class_name: string;
    subject_name: string;
    materials_read: number;
    materials_total: number;
    assignments_submitted: number;
    assignments_total: number;
    late_count: number;
    revision_count: number;
    graded_count: number;
    average_score: number | null;
}

const props = defineProps<{
    active_term?: { name: string } | null;
    rows: ProgressRow[];
    classes: { id: number; name: string }[];
    subjects: { id: number; name: string }[];
    pagination: { links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number };
    summary: { students: number; materials_read: number; materials_total: number; assignments_submitted: number; assignments_total: number; late: number; revisions: number; average_score: number | null };
    filters: { class: string; subject: string; search: string };
    is_student: boolean;
    personal_attendance?: { present: number; sick: number; permitted: number; absent: number; total: number; percentage: number } | null;
}>();

const filterForm = reactive({ ...props.filters });
const totals = props.summary;

const ratio = (done: number, total: number) => total > 0 ? Math.min(100, Math.round(done / total * 100)) : 0;

const applyFilters = () => {
    router.get('/pembelajaran/progress', {
        class: filterForm.class || undefined,
        subject: filterForm.subject || undefined,
        search: filterForm.search.trim() || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const clearFilters = () => {
    filterForm.class = '';
    filterForm.subject = '';
    filterForm.search = '';
    applyFilters();
};
</script>

<template>
    <Head title="Progress Belajar" />
    <AppLayout>
        <main class="view space-y-6 learning-progress-page">
            <section class="hero-card compact-hero">
                <div>
                    <p class="eyebrow">{{ is_student ? 'Ringkasan belajar saya' : 'Monitoring pembelajaran' }} · {{ active_term?.name ?? 'Belum ada semester aktif' }}</p>
                    <h1>{{ is_student ? 'Progress Belajar Saya' : 'Progress Belajar Siswa' }}</h1>
                    <p>{{ is_student ? 'Pantau kehadiran, materi yang sudah dipelajari, dan tugas yang telah dikumpulkan.' : 'Pantau materi dibaca, tugas dikumpulkan, keterlambatan, revisi, dan capaian nilai.' }}</p>
                </div>
                <Link class="secondary-button progress-back-link" href="/pembelajaran">{{ is_student ? 'Buka materi & tugas' : 'Kembali ke pembelajaran' }}</Link>
            </section>

            <section v-if="!is_student" class="grade-summary" aria-label="Ringkasan progres hasil filter">
                <article><span>Siswa</span><strong>{{ totals.students }}</strong><small>pada hasil filter</small></article>
                <article><span>Materi dibaca</span><strong>{{ totals.materials_read }} / {{ totals.materials_total }}</strong><small>{{ totals.materials_total ? `${ratio(totals.materials_read, totals.materials_total)}% dari aktivitas tersedia` : 'belum ada materi terbit' }}</small></article>
                <article><span>Tugas dikumpulkan</span><strong>{{ totals.assignments_submitted }} / {{ totals.assignments_total }}</strong><small>{{ totals.assignments_total ? `${ratio(totals.assignments_submitted, totals.assignments_total)}% dari tugas terbit` : 'belum ada tugas terbit' }}</small></article>
                <article><span>Terlambat</span><strong>{{ totals.late }}</strong><small>{{ totals.revisions }} perlu revisi</small></article>
            </section>

            <section v-else class="grade-summary student-summary" aria-label="Ringkasan progress belajar saya">
                <article><span>Kehadiran</span><strong>{{ personal_attendance ? `${personal_attendance.percentage}%` : '—' }}</strong><small>{{ personal_attendance ? `${personal_attendance.present} kali hadir dari ${personal_attendance.total} catatan` : 'belum ada catatan presensi' }}</small></article>
                <article><span>Materi dipelajari</span><strong>{{ totals.materials_read }} / {{ totals.materials_total }}</strong><small>{{ totals.materials_total ? `${ratio(totals.materials_read, totals.materials_total)}% materi terbit` : 'belum ada materi terbit' }}</small></article>
                <article><span>Tugas dikumpulkan</span><strong>{{ totals.assignments_submitted }} / {{ totals.assignments_total }}</strong><small>{{ totals.assignments_total ? `${ratio(totals.assignments_submitted, totals.assignments_total)}% tugas terbit` : 'belum ada tugas terbit' }}</small></article>
                <article><span>Rerata nilai</span><strong>{{ totals.average_score === null ? '—' : `${totals.average_score}%` }}</strong><small>berdasarkan tugas yang dinilai</small></article>
            </section>

            <section v-if="is_student && personal_attendance" class="panel personal-attendance" aria-label="Rincian kehadiran saya">
                <div><span>Rincian kehadiran</span><strong>{{ personal_attendance.percentage }}%</strong></div>
                <p>Hadir {{ personal_attendance.present }} · Sakit {{ personal_attendance.sick }} · Izin {{ personal_attendance.permitted }} · Alpa {{ personal_attendance.absent }}</p>
            </section>

            <section class="panel progress-panel">
                <form v-if="!is_student" class="toolbar progress-toolbar" @submit.prevent="applyFilters">
                    <label class="progress-search"><span class="sr-only">Cari siswa atau mata pelajaran</span><input v-model="filterForm.search" type="search" placeholder="Cari nama, NIS, atau mata pelajaran…" /></label>
                    <label class="progress-class-filter"><span class="sr-only">Filter kelas</span><select v-model="filterForm.class"><option value="">Semua kelas</option><option v-for="classGroup in classes" :key="classGroup.id" :value="String(classGroup.id)">{{ classGroup.name }}</option></select></label>
                    <label class="progress-subject-filter"><span class="sr-only">Filter mata pelajaran</span><select v-model="filterForm.subject"><option value="">Semua mata pelajaran</option><option v-for="subject in subjects" :key="subject.id" :value="String(subject.id)">{{ subject.name }}</option></select></label>
                    <button class="primary-button progress-apply" type="submit">Terapkan</button>
                    <button v-if="filterForm.search || filterForm.class || filterForm.subject" class="secondary-button progress-reset" type="button" @click="clearFilters">Reset</button>
                    <span class="progress-result-count">{{ pagination.from ?? 0 }}–{{ pagination.to ?? 0 }} dari {{ pagination.total }} baris</span>
                </form>

                <div v-if="!active_term" class="progress-empty" role="status">
                    <strong>Belum ada semester aktif</strong>
                    <p>Progress akan tampil setelah admin mengaktifkan semester pada Pengaturan Akademik.</p>
                </div>
                <template v-else>
                    <div v-if="!rows.length && !filterForm.search && !filterForm.class && !filterForm.subject" class="progress-empty" role="status">
                        <strong>{{ is_student ? 'Belum ada aktivitas belajar' : 'Belum ada progress untuk periode ini' }}</strong>
                        <p>{{ is_student ? 'Materi dan tugas akan muncul di sini setelah guru menerbitkannya untuk kelasmu.' : 'Progress siswa akan muncul setelah materi atau tugas diterbitkan pada semester aktif.' }}</p>
                    </div>

                    <div v-else-if="!rows.length" class="progress-empty" role="status">
                        <strong>{{ filterForm.search || filterForm.class || filterForm.subject ? 'Tidak ada hasil yang cocok' : 'Belum ada progress untuk periode ini' }}</strong>
                        <p>{{ filterForm.search || filterForm.class || filterForm.subject ? 'Ubah filter kelas, mata pelajaran, atau kata pencarian.' : 'Progress siswa akan muncul setelah materi atau tugas diterbitkan pada semester aktif.' }}</p>
                    </div>

                    <div v-else class="table-wrap progress-desktop-table">
                        <table class="data-table">
                            <thead><tr><th>{{ is_student ? 'Mata pelajaran' : 'Siswa' }}</th><th v-if="!is_student">Kelas / Mapel</th><th>Materi dibaca</th><th>Tugas dikumpulkan</th><th v-if="!is_student">Terlambat</th><th v-if="!is_student">Revisi</th><th>Rerata nilai</th></tr></thead>
                            <tbody>
                                <tr v-for="row in rows" :key="row.row_key">
                                    <td><template v-if="is_student"><strong>{{ row.subject_name }}</strong><small>{{ row.class_name }}</small></template><template v-else><strong>{{ row.student_name }}</strong><small>{{ row.nis }}</small></template></td>
                                    <td v-if="!is_student"><strong>{{ row.class_name }}</strong><small>{{ row.subject_name }}</small></td>
                                    <td><strong>{{ row.materials_read }} / {{ row.materials_total }}</strong><small>{{ row.materials_total ? `${ratio(row.materials_read, row.materials_total)}% selesai` : 'Belum ada materi terbit' }}</small></td>
                                    <td><strong>{{ row.assignments_submitted }} / {{ row.assignments_total }}</strong><small>{{ row.assignments_total ? `${ratio(row.assignments_submitted, row.assignments_total)}% terkumpul` : 'Belum ada tugas terbit' }}</small></td>
                                    <td v-if="!is_student"><span :class="{ 'progress-attention': row.late_count > 0 }">{{ row.late_count }}</span></td>
                                    <td v-if="!is_student"><span :class="{ 'progress-attention': row.revision_count > 0 }">{{ row.revision_count }}</span></td>
                                    <td><strong>{{ row.average_score === null ? '—' : `${row.average_score}%` }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="rows.length" class="progress-mobile-cards">
                        <article v-for="row in rows" :key="row.row_key" class="progress-row-card">
                            <div class="progress-row-heading">
                                <div><strong>{{ is_student ? row.subject_name : row.student_name }}</strong><small>{{ is_student ? row.class_name : `${row.nis} · ${row.class_name} · ${row.subject_name}` }}</small></div>
                                <span v-if="row.average_score !== null" class="progress-score">{{ row.average_score }}%</span>
                            </div>
                            <div class="progress-row-metrics">
                                <div><span>{{ is_student ? 'Materi dipelajari' : 'Materi dibaca' }}</span><strong>{{ row.materials_read }} / {{ row.materials_total }}</strong></div>
                                <div><span>Tugas dikumpulkan</span><strong>{{ row.assignments_submitted }} / {{ row.assignments_total }}</strong></div>
                                <div v-if="!is_student"><span>Terlambat</span><strong :class="{ 'progress-attention': row.late_count > 0 }">{{ row.late_count }}</strong></div>
                                <div v-if="!is_student"><span>Perlu revisi</span><strong :class="{ 'progress-attention': row.revision_count > 0 }">{{ row.revision_count }}</strong></div>
                            </div>
                        </article>
                    </div>
                </template>

                <nav v-if="pagination.total > 15" class="progress-pagination" aria-label="Halaman progress belajar">
                    <Link v-for="(link, index) in pagination.links" :key="index" :href="link.url ?? '#progress'" :class="{ current: link.active, disabled: !link.url }" preserve-scroll v-html="link.label" />
                </nav>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.compact-hero{align-items:center}
.progress-back-link{white-space:nowrap}
.progress-toolbar{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap}
.progress-search{min-width:220px;flex:1}
.progress-toolbar input,.progress-toolbar select{width:100%;min-height:40px;padding:.6rem .75rem;color:var(--ink);background:var(--surface);border:1px solid var(--line-strong);border-radius:.65rem}
.progress-toolbar input::placeholder{color:var(--ink-faint)}
.progress-class-filter{width:min(220px,100%)}
.progress-subject-filter{width:min(240px,100%)}
.progress-reset{min-height:40px}
.progress-apply{min-height:40px}
.progress-result-count{color:var(--ink-faint);font-size:.72rem;white-space:nowrap}
.progress-pagination{display:flex;justify-content:center;gap:.35rem;padding:1rem;border-top:1px solid var(--line)}
.progress-pagination :deep(a){padding:.4rem .65rem;color:var(--ink);border:1px solid var(--line);border-radius:.4rem;font-size:.8rem;text-decoration:none}
.progress-pagination :deep(a.current){color:#fff;background:var(--primary);border-color:var(--primary)}
.progress-pagination :deep(a.disabled){opacity:.45;pointer-events:none}
.grade-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem;margin:0}
.grade-summary>article{display:grid;gap:.25rem;min-width:0;padding:1rem;color:var(--ink);background:var(--surface);border:1px solid var(--line);border-radius:.85rem}
.grade-summary>article span{color:var(--ink-faint);font-size:.72rem}
.grade-summary>article strong{overflow-wrap:anywhere;color:var(--ink);font-size:1.35rem;letter-spacing:-.03em}
.student-summary{grid-template-columns:repeat(4,minmax(0,1fr))}
.grade-summary small{display:block;margin-top:.25rem}
.personal-attendance{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;color:var(--ink);background:linear-gradient(135deg,var(--surface) 0%,var(--green-soft) 170%);border-color:color-mix(in srgb,var(--green) 26%,var(--line))}
.personal-attendance span{display:block;color:var(--ink-soft);font-size:.78rem}
.personal-attendance strong{color:var(--green);font-size:1.5rem}
.personal-attendance p{margin:0;color:var(--ink-soft);font-size:.78rem}
.progress-panel{overflow:hidden}
.table-wrap{overflow-x:auto}
.progress-desktop-table .data-table{width:100%;min-width:760px}
.progress-desktop-table .data-table td small{display:block;margin-top:.2rem;color:var(--ink-faint)}
.progress-attention{color:var(--red);font-weight:800}
.progress-empty{display:grid;justify-items:center;gap:.35rem;padding:3rem 1.2rem;text-align:center}
.progress-empty strong{color:var(--ink);font-size:.95rem}
.progress-empty p{max-width:480px;margin:0;color:var(--ink-soft);font-size:.8rem;line-height:1.55}
.progress-mobile-cards{display:none}
.progress-row-card{padding:1rem;color:var(--ink);background:var(--surface);border:1px solid var(--line);border-radius:.85rem}
.progress-row-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;padding-bottom:.7rem;border-bottom:1px solid var(--line)}
.progress-row-heading strong,.progress-row-heading small{display:block}
.progress-row-heading strong{font-size:.88rem}
.progress-row-heading small{margin-top:.2rem;color:var(--ink-faint);font-size:.68rem;line-height:1.4}
.progress-score{flex:0 0 auto;padding:.3rem .55rem;color:var(--green);background:var(--green-soft);border-radius:999px;font-size:.7rem;font-weight:800}
.progress-row-metrics{display:grid;grid-template-columns:1fr 1fr;gap:.65rem;padding-top:.75rem}
.progress-row-metrics div{display:grid;gap:.15rem}
.progress-row-metrics span{color:var(--ink-faint);font-size:.65rem}
.progress-row-metrics strong{color:var(--ink);font-size:.78rem}
@media(max-width:900px){.student-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){.compact-hero{align-items:flex-start}.progress-toolbar{align-items:stretch;flex-direction:column}.progress-search,.progress-class-filter,.progress-subject-filter{width:100%;min-width:0}.progress-result-count{align-self:flex-start}.personal-attendance{align-items:flex-start;flex-direction:column}.progress-desktop-table{display:none}.progress-mobile-cards{display:grid;gap:.7rem;padding:1rem}}
@media(max-width:480px){.grade-summary{grid-template-columns:1fr 1fr}.progress-row-metrics{gap:.55rem}}
</style>
