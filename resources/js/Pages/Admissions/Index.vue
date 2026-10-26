<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

interface Applicant {
    id: number;
    registration_no: string;
    name: string;
    nisn: string | null;
    gender: 'L' | 'P';
    birth_date: string | null;
    parent_name: string | null;
    parent_phone: string | null;
    previous_school: string | null;
    status: string;
    notes: string | null;
}

interface CommitteeMember { id: number; name: string; email: string; assigned_at: string }
interface Teacher { id: number; name: string; email: string }
interface StatusOption { value: string; label: string }

const props = defineProps<{
    applicants: Applicant[];
    committee: CommitteeMember[];
    available_teachers: Teacher[];
    can_manage_committee: boolean;
    statuses: StatusOption[];
}>();

const page = usePage();
const flashMessage = () => (page.props.flash as { message?: string })?.message;
const applicantForm = useForm({
    name: '', nisn: '', gender: 'L' as 'L' | 'P', birth_date: '',
    parent_name: '', parent_phone: '', previous_school: '', notes: '',
});
const committeeForm = useForm({ user_id: '' });

function addApplicant() {
    applicantForm.post('/spmb/pendaftar', { preserveScroll: true, onSuccess: () => applicantForm.reset() });
}

function assignTeacher() {
    committeeForm.post('/spmb/panitia', { preserveScroll: true, onSuccess: () => committeeForm.reset() });
}

function updateApplicant(applicant: Applicant, event: Event) {
    const form = event.target as HTMLFormElement;
    const data = new FormData(form);
    router.put(`/spmb/pendaftar/${applicant.id}`, {
        status: String(data.get('status')),
        notes: String(data.get('notes') ?? ''),
    }, { preserveScroll: true });
}

function removeMember(member: CommitteeMember) {
    if (window.confirm(`Cabut penugasan ${member.name} dari panitia SPMB?`)) {
        router.delete(`/spmb/panitia/${member.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="SPMB — Penerimaan Siswa Baru" />
    <AppLayout title="SPMB">
        <main class="view space-y-6">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Penerimaan siswa baru</p>
                    <h1>Manajemen SPMB</h1>
                    <p>Kelola pendaftar, verifikasi berkas, dan proses hasil seleksi sekolah.</p>
                </div>
                <div class="grade-summary admissions-summary">
                    <div><span>Total pendaftar</span><strong>{{ applicants.length }}</strong></div>
                    <div><span>Sudah diterima</span><strong>{{ applicants.filter((item) => item.status === 'accepted').length }}</strong></div>
                    <div><span>Panitia aktif</span><strong>{{ committee.length }}</strong></div>
                </div>
            </section>

            <p v-if="flashMessage()" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                {{ flashMessage() }}
            </p>

            <section v-if="can_manage_committee" class="panel admissions-panel">
                <div class="table-title">
                    <div><h2>Panitia SPMB</h2><p>Penugasan berlaku untuk guru aktif di sekolah ini.</p></div>
                </div>
                <form class="admissions-assign-form" @submit.prevent="assignTeacher">
                    <label class="sr-only" for="committee-teacher">Pilih guru</label>
                    <select id="committee-teacher" v-model="committeeForm.user_id" required>
                        <option value="">Pilih guru untuk ditugaskan</option>
                        <option v-for="teacher in available_teachers" :key="teacher.id" :value="String(teacher.id)">
                            {{ teacher.name }} · {{ teacher.email }}
                        </option>
                    </select>
                    <button class="primary-button" type="submit" :disabled="committeeForm.processing || !committeeForm.user_id">
                        Tugaskan guru
                    </button>
                </form>
                <p v-if="committeeForm.errors.user_id" class="field-error">{{ committeeForm.errors.user_id }}</p>
                <ul v-if="committee.length" class="committee-list">
                    <li v-for="member in committee" :key="member.id">
                        <div><strong>{{ member.name }}</strong><span>{{ member.email }}</span></div>
                        <button class="row-action-button row-action-button--danger" type="button" @click="removeMember(member)">Cabut</button>
                    </li>
                </ul>
                <p v-else class="empty-state">Belum ada guru yang ditugaskan sebagai panitia.</p>
            </section>

            <section class="panel admissions-panel">
                <div class="table-title"><div><h2>Tambah pendaftar</h2><p>Masukkan data awal calon siswa yang diterima sekolah.</p></div></div>
                <form class="admissions-form" @submit.prevent="addApplicant">
                    <label>Nama calon siswa<input v-model="applicantForm.name" required maxlength="150" autocomplete="off" /></label>
                    <label>NISN<input v-model="applicantForm.nisn" maxlength="20" inputmode="numeric" /></label>
                    <label>Jenis kelamin<select v-model="applicantForm.gender"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></label>
                    <label>Tanggal lahir<input v-model="applicantForm.birth_date" type="date" /></label>
                    <label>Nama orang tua/wali<input v-model="applicantForm.parent_name" maxlength="150" /></label>
                    <label>Kontak orang tua<input v-model="applicantForm.parent_phone" maxlength="30" inputmode="tel" /></label>
                    <label class="admissions-form-wide">Asal sekolah<input v-model="applicantForm.previous_school" maxlength="180" /></label>
                    <label class="admissions-form-wide">Catatan awal<textarea v-model="applicantForm.notes" rows="2" maxlength="3000" /></label>
                    <div class="admissions-form-wide admissions-form-actions">
                        <span v-if="Object.keys(applicantForm.errors).length" class="field-error">Periksa kembali data pendaftar.</span>
                        <button class="primary-button" type="submit" :disabled="applicantForm.processing">Simpan pendaftar</button>
                    </div>
                </form>
            </section>

            <section class="panel admissions-panel">
                <div class="table-title"><div><h2>Daftar pendaftar</h2><p>Panitia dapat memperbarui status dan catatan seleksi.</p></div></div>
                <div v-if="applicants.length" class="admissions-applicants">
                    <article v-for="applicant in applicants" :key="applicant.id" class="admissions-applicant">
                        <div class="admissions-applicant-heading">
                            <div><span class="admissions-registration">{{ applicant.registration_no }}</span><h3>{{ applicant.name }}</h3></div>
                            <span class="admissions-applicant-demographic">NISN {{ applicant.nisn || '—' }} · {{ applicant.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                        </div>
                        <div class="admissions-applicant-details">
                            <span>Orang tua: {{ applicant.parent_name || '—' }}</span>
                            <span>Kontak: {{ applicant.parent_phone || '—' }}</span>
                            <span>Asal sekolah: {{ applicant.previous_school || '—' }}</span>
                        </div>
                        <form class="admissions-status-form" @submit.prevent="updateApplicant(applicant, $event)">
                            <label>Status seleksi
                                <select name="status" :value="applicant.status">
                                    <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                                </select>
                            </label>
                            <label>Catatan panitia<textarea name="notes" rows="2" maxlength="3000">{{ applicant.notes }}</textarea></label>
                            <button class="secondary-button" type="submit">Simpan perubahan</button>
                        </form>
                    </article>
                </div>
                <p v-else class="empty-state">Belum ada pendaftar. Tambahkan calon siswa untuk memulai proses SPMB.</p>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.admissions-summary { margin: 0; min-width: min(100%, 28rem); }
.admissions-panel { padding: 1.25rem; }
.admissions-assign-form, .admissions-form-actions { display: flex; align-items: end; gap: .75rem; }
.admissions-assign-form select, .admissions-form input, .admissions-form select, .admissions-form textarea, .admissions-status-form select, .admissions-status-form textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: .65rem; background: white; padding: .65rem .75rem; color: #172b4d; }
.admissions-assign-form select { max-width: 34rem; }
.admissions-form { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
.admissions-form label, .admissions-status-form label { display: grid; gap: .4rem; color: #42526b; font-size: .85rem; font-weight: 600; }
.admissions-form-wide { grid-column: 1 / -1; }
.admissions-form-actions { justify-content: space-between; }
.primary-button, .secondary-button { border: 1px solid #3157a4; border-radius: .65rem; background: #3157a4; padding: .65rem 1rem; color: white; font-weight: 700; }
.secondary-button { background: white; color: #3157a4; white-space: nowrap; }
button:disabled { cursor: wait; opacity: .6; }
.committee-list { display: grid; gap: .5rem; margin: 1rem 0 0; padding: 0; list-style: none; }
.committee-list li { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border: 1px solid #dbe3ef; border-radius: .75rem; padding: .75rem 1rem; }
.committee-list li div { display: grid; gap: .15rem; }
.committee-list li span, .admissions-applicant-demographic, .admissions-applicant-details { color: #64748b; font-size: .82rem; }
.empty-state { padding: 1.25rem 0; color: #64748b; }
.field-error { color: #b42318; font-size: .82rem; }
.admissions-applicants { display: grid; gap: .85rem; }
.admissions-applicant { border: 1px solid #dbe3ef; border-radius: .85rem; padding: 1rem; }
.admissions-applicant-heading { display: flex; justify-content: space-between; gap: 1rem; align-items: start; }
.admissions-registration { color: #3157a4; font-size: .75rem; font-weight: 700; letter-spacing: .04em; }
.admissions-applicant h3 { margin: .2rem 0 0; color: #172b4d; font-size: 1.05rem; font-weight: 700; }
.admissions-applicant-details { display: flex; flex-wrap: wrap; gap: .5rem 1.5rem; padding: .75rem 0; }
.admissions-status-form { display: grid; grid-template-columns: minmax(12rem, .65fr) minmax(14rem, 1.5fr) auto; align-items: end; gap: .75rem; border-top: 1px solid #e2e8f0; padding-top: .75rem; }
@media (max-width: 760px) { .admissions-summary { width: 100%; min-width: 0; } .admissions-form { grid-template-columns: 1fr; } .admissions-form-wide { grid-column: auto; } .admissions-assign-form, .admissions-status-form { display: grid; grid-template-columns: 1fr; } .admissions-assign-form select { max-width: none; } .admissions-applicant-heading { display: grid; } }
</style>
