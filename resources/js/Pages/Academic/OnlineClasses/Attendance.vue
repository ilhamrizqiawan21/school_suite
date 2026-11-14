<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
const props = defineProps<{ session: any; students: any[] }>();
const form = useForm({ records: props.students.map(s => ({ enrollment_id: s.enrollment_id, status: s.status, notes: s.notes ?? '' })) });
const submit = () => form.put(`/akademik/kelas-daring/${props.session.id}/presensi`);
</script>
<template>
    <AppLayout title="Presensi daring">
        <Head title="Presensi daring" />
        <div class="view online-attendance-page space-y-5">
            <header class="page-heading online-attendance-heading">
                <div><p class="eyebrow">Kelas daring · Presensi</p><h1>{{ session.title }}</h1><p>{{ session.class_group?.name }} · {{ new Date(session.starts_at).toLocaleString('id-ID') }}</p></div>
                <Link class="secondary-button ui-button ui-button--secondary ui-button--md" href="/akademik/kelas-daring">Kembali ke kelas daring</Link>
            </header>
            <form @submit.prevent="submit">
                <section class="panel online-attendance-panel">
                    <div class="online-attendance-panel-heading"><h2>Daftar peserta</h2><span>{{ students.length }} siswa</span></div>
                    <div class="online-attendance-table table-scroll" tabindex="0" role="region" aria-label="Tabel presensi daring">
                        <table class="app-table data-table"><thead><tr><th scope="col">Siswa</th><th scope="col">Status</th><th scope="col">Catatan</th></tr></thead><tbody>
                            <tr v-for="(student, index) in students" :key="student.enrollment_id"><td><strong>{{ student.name }}</strong><br><small>{{ student.nis }}</small></td><td><select v-model="form.records[index].status" :aria-label="`Status ${student.name}`"><option value="present">Hadir</option><option value="late">Terlambat</option><option value="excused">Izin</option><option value="absent">Tidak hadir</option></select></td><td><input v-model="form.records[index].notes" :aria-label="`Catatan ${student.name}`" placeholder="Opsional" /></td></tr>
                            <tr v-if="!students.length"><td colspan="3" class="empty-state">Tidak ada siswa aktif.</td></tr>
                        </tbody></table>
                    </div>
                    <div class="online-attendance-cards" aria-label="Daftar presensi daring">
                        <article v-for="(student, index) in students" :key="student.enrollment_id">
                            <div><strong>{{ student.name }}</strong><small>NIS {{ student.nis }}</small></div>
                            <label>Status<select v-model="form.records[index].status"><option value="present">Hadir</option><option value="late">Terlambat</option><option value="excused">Izin</option><option value="absent">Tidak hadir</option></select></label>
                            <label>Catatan<input v-model="form.records[index].notes" placeholder="Opsional" /></label>
                        </article>
                        <p v-if="!students.length">Tidak ada siswa aktif.</p>
                    </div>
                    <div class="online-attendance-actions"><span v-if="form.recentlySuccessful" role="status">Presensi tersimpan</span><AppButton variant="primary" type="submit" class="primary-button" :disabled="form.processing || !students.length">{{ form.processing ? 'Menyimpan…' : 'Simpan presensi' }}</AppButton></div>
                </section>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.online-attendance-heading h1 { margin: .3rem 0; color: var(--ink); font-size: var(--type-3xl); }
.online-attendance-heading p { color: var(--ink-soft); }
.online-attendance-heading .eyebrow { color: var(--primary); font-size: var(--type-meta); font-weight: var(--weight-semibold); }
.online-attendance-panel { overflow: hidden; border-radius: 14px; }
.online-attendance-panel-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--line); }
.online-attendance-panel-heading h2 { margin: 0; color: var(--ink); font-size: var(--type-md); }
.online-attendance-panel-heading span { padding: .25rem .65rem; color: var(--primary); background: var(--primary-soft); border-radius: 8px; font-size: var(--type-meta); }
.online-attendance-table select, .online-attendance-table input, .online-attendance-cards select, .online-attendance-cards input { width: 100%; min-height: 42px; padding: .5rem .65rem; color: var(--ink); background: var(--surface); border: 1px solid var(--line-strong); border-radius: 8px; }
.online-attendance-table td { padding: .8rem 1rem; }
.online-attendance-table small { color: var(--ink-soft); }
.online-attendance-cards { display: none; }
.online-attendance-actions { display: flex; align-items: center; justify-content: flex-end; gap: 1rem; padding: 1rem 1.25rem; border-top: 1px solid var(--line); }
.online-attendance-actions span { color: var(--green); font-size: var(--type-meta); }
@media (max-width: 700px) { .online-attendance-heading { align-items: flex-start; flex-direction: column; }.online-attendance-table, .online-attendance-panel-heading { display: none; }.online-attendance-panel { background: transparent; border: 0; box-shadow: none; }.online-attendance-cards { display: grid; gap: 12px; }.online-attendance-cards article { display: grid; gap: .8rem; padding: 1rem; background: var(--surface); border: 1px solid var(--line); border-radius: 12px; }.online-attendance-cards strong, .online-attendance-cards small { display: block; }.online-attendance-cards strong { color: var(--ink); }.online-attendance-cards small { margin-top: .2rem; color: var(--ink-soft); font-size: var(--type-meta); }.online-attendance-cards label { display: grid; gap: .3rem; color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-semibold); }.online-attendance-cards > p { color: var(--ink-soft); }.online-attendance-actions { padding: 1rem 0; border: 0; }.online-attendance-actions button { width: 100%; } }
/* Center attendance status controls while leaving student names and notes left aligned. */
.data-table th:nth-child(2), .data-table td:nth-child(2) { text-align: center; }
.data-table td:nth-child(2) select { text-align: center; text-align-last: center; }
</style>
