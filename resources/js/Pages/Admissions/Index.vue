<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

interface Requirement { id: number; code: string; label: string; is_required: boolean }
interface Criterion { id: number; code: string; label: string; weight: number; max_score: number }
interface Track { id: number; code: string; name: string; quota: number; requirements: Requirement[]; criteria: Criterion[] }
interface Period { id: number; name: string; academic_year: string; level: string | null; status: string; tracks: Track[] }
interface Document { id: number; original_name: string; status: string; requirement?: Requirement }
interface Applicant { id: number; registration_no: string; name: string; nisn: string | null; gender: 'L' | 'P'; birth_date: string | null; parent_name: string | null; parent_phone: string | null; previous_school: string | null; status: string; notes: string | null; track?: Track; selection_result?: { total_score: number; rank: number | null; decision: string | null }; documents: Document[]; re_registration?: { status: string; student_id: number | null } }
interface CommitteeMember { id: number; name: string; email: string; assigned_at: string }
interface Teacher { id: number; name: string; email: string }
interface StatusOption { value: string; label: string }

const props = defineProps<{
    section: string;
    periods: Period[];
    selected_period_id: number | null;
    tracks: Track[];
    applicants: Applicant[];
    committee: CommitteeMember[];
    available_teachers: Teacher[];
    can_manage_committee: boolean;
    can_manage_applicants: boolean;
    statuses: StatusOption[];
    summary: { periods: number; applicants: number; accepted: number; re_registered: number };
}>();

const page = usePage();
const flashMessage = () => (page.props.flash as { message?: string })?.message;
const periodForm = useForm({ name: '', academic_year: '', level: '', registration_start: '', registration_end: '', verification_start: '', verification_end: '', selection_start: '', selection_end: '', announcement_at: '', re_registration_start: '', re_registration_end: '', guideline_file: null as File | null });
const trackForm = useForm({ period_id: props.selected_period_id ? String(props.selected_period_id) : '', code: '', name: '', quota: 1 });
const requirementForm = useForm({ track_id: '', code: '', label: '', is_required: true });
const criterionForm = useForm({ track_id: '', code: '', label: '', weight: 1, max_score: 100 });
const applicantForm = useForm({ period_id: props.selected_period_id ? String(props.selected_period_id) : '', track_id: '', name: '', nisn: '', gender: 'L' as 'L' | 'P', birth_date: '', parent_name: '', parent_phone: '', previous_school: '', notes: '' });
const committeeForm = useForm({ user_id: '' });
const documentForms: Record<number, ReturnType<typeof useForm>> = {};
const scoreForms: Record<string, ReturnType<typeof useForm>> = {};
const decisionForms: Record<number, ReturnType<typeof useForm>> = {};
const reRegistrationForms: Record<number, ReturnType<typeof useForm>> = {};

function documentForm(applicant: Applicant) { return documentForms[applicant.id] ??= useForm({ requirement_id: '', file: null as File | null }); }
function scoreForm(applicant: Applicant, criterion: Criterion) { const key = `${applicant.id}-${criterion.id}`; return scoreForms[key] ??= useForm({ criterion_id: String(criterion.id), score: '', notes: '' }); }
function decisionForm(applicant: Applicant) { return decisionForms[applicant.id] ??= useForm({ decision: applicant.selection_result?.decision ?? 'accepted', override_reason: '' }); }
function reRegistrationForm(applicant: Applicant) { return reRegistrationForms[applicant.id] ??= useForm({ nis: '' }); }
function addPeriod() { periodForm.post('/spmb/periode', { forceFormData: true, preserveScroll: true, onSuccess: () => periodForm.reset() }); }
function addTrack() { trackForm.post('/spmb/jalur', { preserveScroll: true, onSuccess: () => trackForm.reset('code', 'name') }); }
function addRequirement() { requirementForm.post('/spmb/persyaratan', { preserveScroll: true, onSuccess: () => requirementForm.reset('code', 'label') }); }
function addCriterion() { criterionForm.post('/spmb/kriteria', { preserveScroll: true, onSuccess: () => criterionForm.reset('code', 'label') }); }
function addApplicant() { applicantForm.post('/spmb/pendaftar', { preserveScroll: true, onSuccess: () => applicantForm.reset('name', 'nisn', 'birth_date', 'parent_name', 'parent_phone', 'previous_school', 'notes') }); }
function assignTeacher() { committeeForm.post('/spmb/panitia', { preserveScroll: true, onSuccess: () => committeeForm.reset() }); }
function openPeriod(period: Period) { router.post(`/spmb/periode/${period.id}/status`, { status: 'registration_open' }, { preserveScroll: true }); }
function rankPeriod() { if (props.selected_period_id) router.post(`/spmb/periode/${props.selected_period_id}/ranking`, {}, { preserveScroll: true }); }
function uploadDocument(applicant: Applicant) { const form = documentForm(applicant); form.post(`/spmb/pendaftar/${applicant.id}/berkas`, { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() }); }
function verifyDocument(document: Document, status: string) { router.put(`/spmb/berkas/${document.id}/verifikasi`, { status, notes: '' }, { preserveScroll: true }); }
function saveScore(applicant: Applicant, criterion: Criterion) { scoreForm(applicant, criterion).post(`/spmb/pendaftar/${applicant.id}/nilai`, { preserveScroll: true }); }
function finalizeApplicant(applicant: Applicant) { decisionForm(applicant).put(`/spmb/pendaftar/${applicant.id}/keputusan`, { preserveScroll: true }); }
function confirmReRegistration(applicant: Applicant) { reRegistrationForm(applicant).post(`/spmb/pendaftar/${applicant.id}/daftar-ulang`, { preserveScroll: true }); }
function updateApplicant(applicant: Applicant, event: Event) { const data = new FormData(event.target as HTMLFormElement); router.put(`/spmb/pendaftar/${applicant.id}`, { status: String(data.get('status')), notes: String(data.get('notes') ?? '') }, { preserveScroll: true }); }
function removeMember(member: CommitteeMember) { if (window.confirm(`Cabut penugasan ${member.name} dari panitia SPMB?`)) router.delete(`/spmb/panitia/${member.id}`, { preserveScroll: true }); }
</script>

<template>
    <Head title="SPMB" />
    <AppLayout title="SPMB">
        <main class="view space-y-6">
            <section class="hero-card">
                <div><p class="eyebrow">Sistem Penerimaan Murid Baru</p><h1>{{ section === 'dashboard' ? 'Dashboard SPMB' : 'Manajemen SPMB' }}</h1><p>Kelola periode, jalur, verifikasi, seleksi, pengumuman, dan daftar ulang dalam satu alur.</p></div>
                <div class="grade-summary admissions-summary"><div><span>Periode</span><strong>{{ summary.periods }}</strong></div><div><span>Pendaftar</span><strong>{{ summary.applicants }}</strong></div><div><span>Diterima</span><strong>{{ summary.accepted }}</strong></div><div><span>Daftar ulang</span><strong>{{ summary.re_registered }}</strong></div></div>
            </section>
            <p v-if="flashMessage()" class="notice-success" role="status">{{ flashMessage() }}</p>
            <section class="panel admissions-panel period-context">
                <label>Periode aktif
                    <select :value="selected_period_id ?? ''" @change="router.get('/spmb', { period_id: ($event.target as HTMLSelectElement).value }, { preserveState: true })">
                        <option value="">Belum ada periode</option><option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }} · {{ period.status }}</option>
                    </select>
                </label>
                <div class="spmb-quick-links"><a href="/spmb/periode">Periode</a><a href="/spmb/jalur">Jalur & kuota</a><a href="/spmb/pendaftar">Pendaftar</a><a href="/spmb/berkas">Berkas</a><a href="/spmb/seleksi">Seleksi</a><a href="/spmb/daftar-ulang">Daftar ulang</a></div>
            </section>

            <section v-if="section === 'dashboard'" class="panel admissions-panel"><div class="table-title"><div><h2>Alur kerja SPMB</h2><p>Ikuti urutan ini agar setiap periode memiliki data dan keputusan yang terdokumentasi.</p></div></div><div class="workflow-grid"><div><strong>1</strong><span>Persiapan periode, jalur, kuota, berkas, dan kriteria.</span></div><div><strong>2</strong><span>Input pendaftar dan verifikasi dokumen.</span></div><div><strong>3</strong><span>Hitung ranking dan finalisasi keputusan.</span></div><div><strong>4</strong><span>Konfirmasi daftar ulang dan buat data siswa.</span></div></div></section>

            <section v-if="section === 'periods' || section === 'dashboard'" class="panel admissions-panel">
                <div class="table-title"><div><h2>Periode & Juknis</h2><p>Buat siklus SPMB yang dapat dikonfigurasi per tahun ajaran dan jenjang.</p></div></div>
                <form v-if="can_manage_committee" class="admissions-form" @submit.prevent="addPeriod"><label>Nama periode<input v-model="periodForm.name" required placeholder="SPMB 2027/2028" /></label><label>Tahun ajaran<input v-model="periodForm.academic_year" required placeholder="2027/2028" /></label><label>Jenjang<input v-model="periodForm.level" placeholder="SMP" /></label><label>Mulai pendaftaran<input v-model="periodForm.registration_start" type="date" /></label><label>Akhir pendaftaran<input v-model="periodForm.registration_end" type="date" /></label><label>Mulai verifikasi<input v-model="periodForm.verification_start" type="date" /></label><label>Akhir verifikasi<input v-model="periodForm.verification_end" type="date" /></label><label>Mulai seleksi<input v-model="periodForm.selection_start" type="date" /></label><label>Akhir seleksi<input v-model="periodForm.selection_end" type="date" /></label><label>Tanggal pengumuman<input v-model="periodForm.announcement_at" type="date" /></label><label>Mulai daftar ulang<input v-model="periodForm.re_registration_start" type="date" /></label><label>Akhir daftar ulang<input v-model="periodForm.re_registration_end" type="date" /></label><label>Juknis<input type="file" accept=".pdf,.doc,.docx" @change="periodForm.guideline_file = ($event.target as HTMLInputElement).files?.[0] || null" /></label><div class="admissions-form-actions"><button class="primary-button" type="submit" :disabled="periodForm.processing">Tambah periode</button></div></form>
                <div v-for="period in periods" :key="period.id" class="period-row"><div><strong>{{ period.name }}</strong><span>{{ period.academic_year }} · {{ period.level || 'Semua jenjang' }} · {{ period.tracks.length }} jalur</span></div><button v-if="can_manage_committee && period.status === 'draft'" class="secondary-button" type="button" @click="openPeriod(period)">Buka pendaftaran</button><span class="status-pill">{{ period.status }}</span></div>
            </section>

            <section v-if="section === 'tracks'" class="panel admissions-panel"><div class="table-title"><div><h2>Jalur, kuota, berkas & kriteria</h2><p>Konfigurasi mengikuti juknis periode, bukan angka hardcode.</p></div></div><form v-if="can_manage_committee && selected_period_id" class="admissions-form" @submit.prevent="addTrack"><label>Jalur<select v-model="trackForm.code"><option value="">Pilih jalur</option><option value="DOMISILI">Domisili</option><option value="AFIRMASI">Afirmasi</option><option value="PRESTASI">Prestasi</option><option value="MUTASI">Mutasi</option></select></label><label>Nama jalur<input v-model="trackForm.name" required /></label><label>Kuota<input v-model.number="trackForm.quota" type="number" min="1" required /></label><div class="admissions-form-actions"><button class="primary-button" type="submit">Tambah jalur</button></div></form><article v-for="track in tracks" :key="track.id" class="track-card"><div><strong>{{ track.name }}</strong><span>{{ track.code }} · kuota {{ track.quota }}</span></div><small>{{ track.requirements.length }} persyaratan · {{ track.criteria.length }} kriteria</small></article><form v-if="can_manage_committee && tracks.length" class="admissions-form compact-form" @submit.prevent="addRequirement"><label>Jalur<select v-model="requirementForm.track_id" required><option value="">Pilih jalur</option><option v-for="track in tracks" :key="track.id" :value="track.id">{{ track.name }}</option></select></label><label>Kode berkas<input v-model="requirementForm.code" required placeholder="KK" /></label><label>Nama berkas<input v-model="requirementForm.label" required placeholder="Kartu Keluarga" /></label><button class="secondary-button" type="submit">Tambah berkas</button></form><form v-if="can_manage_committee && tracks.length" class="admissions-form compact-form" @submit.prevent="addCriterion"><label>Jalur<select v-model="criterionForm.track_id" required><option value="">Pilih jalur</option><option v-for="track in tracks" :key="track.id" :value="track.id">{{ track.name }}</option></select></label><label>Kode kriteria<input v-model="criterionForm.code" required placeholder="NILAI" /></label><label>Nama kriteria<input v-model="criterionForm.label" required placeholder="Nilai seleksi" /></label><label>Bobot<input v-model.number="criterionForm.weight" type="number" min="0.01" step="0.01" /></label><button class="secondary-button" type="submit">Tambah kriteria</button></form></section>

            <section v-if="section === 'committee'" class="panel admissions-panel"><div class="table-title"><div><h2>Panitia & jadwal</h2><p>Panitia guru dapat memverifikasi dan menilai pendaftar yang ditugaskan.</p></div></div><form v-if="can_manage_committee" class="admissions-assign-form" @submit.prevent="assignTeacher"><select v-model="committeeForm.user_id" required><option value="">Pilih guru</option><option v-for="teacher in available_teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }} · {{ teacher.email }}</option></select><button class="primary-button" type="submit">Tugaskan guru</button></form><ul class="committee-list"><li v-for="member in committee" :key="member.id"><div><strong>{{ member.name }}</strong><span>{{ member.email }}</span></div><button v-if="can_manage_committee" class="secondary-button" type="button" @click="removeMember(member)">Cabut</button></li></ul></section>

            <section v-if="section === 'applicants'" class="panel admissions-panel"><div class="table-title"><div><h2>Pendaftar</h2><p>Satu pendaftaran aktif memilih satu jalur dalam satu periode.</p></div></div><form v-if="can_manage_applicants" class="admissions-form" @submit.prevent="addApplicant"><label>Periode<select v-model="applicantForm.period_id" required><option value="">Pilih periode</option><option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }}</option></select></label><label>Jalur<select v-model="applicantForm.track_id" required><option value="">Pilih jalur</option><option v-for="track in tracks" :key="track.id" :value="track.id">{{ track.name }}</option></select></label><label>Nama calon siswa<input v-model="applicantForm.name" required /></label><label>NISN<input v-model="applicantForm.nisn" inputmode="numeric" /></label><label>Jenis kelamin<select v-model="applicantForm.gender"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></label><label>Tanggal lahir<input v-model="applicantForm.birth_date" type="date" /></label><label>Nama orang tua/wali<input v-model="applicantForm.parent_name" /></label><label>Kontak orang tua<input v-model="applicantForm.parent_phone" /></label><label>Asal sekolah<input v-model="applicantForm.previous_school" /></label><label class="admissions-form-wide">Catatan<textarea v-model="applicantForm.notes" rows="2" /></label><div class="admissions-form-actions admissions-form-wide"><button class="primary-button" type="submit">Simpan pendaftar</button></div></form><div class="admissions-applicants"><article v-for="applicant in applicants" :key="applicant.id" class="admissions-applicant"><div class="admissions-applicant-heading"><div><span class="admissions-registration">{{ applicant.registration_no }}</span><h3>{{ applicant.name }}</h3></div><span class="status-pill">{{ applicant.status }}</span></div><div class="admissions-applicant-details"><span>{{ applicant.track?.name || 'Legacy' }}</span><span>NISN {{ applicant.nisn || '—' }}</span><span>Asal {{ applicant.previous_school || '—' }}</span></div><form v-if="can_manage_applicants" class="admissions-status-form" @submit.prevent="updateApplicant(applicant, $event)"><label>Status<select name="status" :value="applicant.status"><option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option></select></label><label>Catatan<textarea name="notes" rows="2">{{ applicant.notes }}</textarea></label><button class="secondary-button" type="submit">Simpan</button></form></article><p v-if="!applicants.length" class="empty-state">Belum ada pendaftar pada periode ini.</p></div></section>

            <section v-if="section === 'documents'" class="panel admissions-panel"><div class="table-title"><div><h2>Verifikasi berkas</h2><p>Unggah dan periksa persyaratan setiap pendaftar.</p></div></div><article v-for="applicant in applicants" :key="applicant.id" class="admissions-applicant"><div class="admissions-applicant-heading"><div><span class="admissions-registration">{{ applicant.registration_no }}</span><h3>{{ applicant.name }}</h3></div></div><div class="document-list"><div v-for="document in applicant.documents" :key="document.id" class="document-row"><span>{{ document.requirement?.label || document.original_name }}</span><span class="status-pill">{{ document.status }}</span><a :href="`/spmb/berkas/${document.id}/unduh`">Unduh</a><button v-if="can_manage_applicants" class="secondary-button" type="button" @click="verifyDocument(document, 'valid')">Valid</button><button v-if="can_manage_applicants" class="link-danger" type="button" @click="verifyDocument(document, 'needs_revision')">Perlu perbaikan</button></div></div><form v-if="can_manage_applicants && applicant.track" class="upload-form" @submit.prevent="uploadDocument(applicant)"><select v-model="documentForm(applicant).requirement_id"><option value="">Berkas umum</option><option v-for="requirement in applicant.track.requirements" :key="requirement.id" :value="requirement.id">{{ requirement.label }}</option></select><input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="documentForm(applicant).file = ($event.target as HTMLInputElement).files?.[0] || null" required /><button class="secondary-button" type="submit">Unggah</button></form></article><p v-if="!applicants.length" class="empty-state">Belum ada pendaftar untuk diverifikasi.</p></section>

            <section v-if="section === 'selection' || section === 'announcement'" class="panel admissions-panel"><div class="table-title"><div><h2>{{ section === 'selection' ? 'Seleksi & ranking' : 'Pengumuman' }}</h2><p>Hasil dihitung dari kriteria periode dan keputusan akhir memiliki jejak audit.</p></div><button v-if="section === 'selection' && can_manage_committee" class="primary-button" type="button" @click="rankPeriod">Hitung ranking</button></div><article v-for="applicant in applicants" :key="applicant.id" class="admissions-applicant"><div class="admissions-applicant-heading"><div><span class="admissions-registration">{{ applicant.registration_no }}</span><h3>{{ applicant.name }}</h3></div><span class="status-pill">{{ applicant.selection_result?.decision || 'Belum diputuskan' }} · #{{ applicant.selection_result?.rank || '—' }}</span></div><p class="score-line">Total skor: {{ applicant.selection_result?.total_score || 0 }}</p><div v-if="section === 'selection' && can_manage_applicants" v-for="criterion in applicant.track?.criteria || []" :key="criterion.id" class="score-row"><span>{{ criterion.label }}</span><input v-model="scoreForm(applicant, criterion).score" type="number" min="0" :max="criterion.max_score" /><button class="secondary-button" type="button" @click="saveScore(applicant, criterion)">Simpan nilai</button></div><form v-if="section === 'selection' && can_manage_applicants" class="decision-form" @submit.prevent="finalizeApplicant(applicant)"><select v-model="decisionForm(applicant).decision"><option value="accepted">Diterima</option><option value="waitlisted">Cadangan</option><option value="rejected">Tidak diterima</option></select><input v-model="decisionForm(applicant).override_reason" placeholder="Alasan keputusan / override" required /><button class="secondary-button" type="submit">Finalisasi</button></form></article><p v-if="!applicants.length" class="empty-state">Belum ada data seleksi.</p></section>

            <section v-if="section === 're-registration'" class="panel admissions-panel"><div class="table-title"><div><h2>Daftar ulang</h2><p>Pendaftar diterima dibuat menjadi data siswa setelah NIS dikonfirmasi.</p></div></div><article v-for="applicant in applicants.filter((item) => item.status === 'accepted' || item.status === 're_registered')" :key="applicant.id" class="admissions-applicant"><div class="admissions-applicant-heading"><div><span class="admissions-registration">{{ applicant.registration_no }}</span><h3>{{ applicant.name }}</h3></div><span class="status-pill">{{ applicant.re_registration?.status || 'pending' }}</span></div><form v-if="can_manage_applicants && !applicant.re_registration?.student_id" class="upload-form" @submit.prevent="confirmReRegistration(applicant)"><input v-model="reRegistrationForm(applicant).nis" placeholder="NIS siswa" required /><button class="primary-button" type="submit">Konfirmasi daftar ulang</button></form><p v-else class="score-line">Terhubung ke data siswa #{{ applicant.re_registration?.student_id }}</p></article></section>
        </main>
    </AppLayout>
</template>

<style scoped>
.admissions-summary { margin: 0; min-width: min(100%, 32rem); }
.admissions-panel { padding: 1.25rem; }
.period-context { display: flex; align-items: end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.period-context label, .admissions-form label, .admissions-status-form label { display: grid; gap: .4rem; color: #42526b; font-size: .85rem; font-weight: 600; }
.period-context select, .admissions-form input, .admissions-form select, .admissions-form textarea, .admissions-status-form select, .admissions-status-form textarea, .upload-form input, .upload-form select, .decision-form input, .decision-form select, .score-row input { width: 100%; border: 1px solid #cbd5e1; border-radius: .65rem; background: white; padding: .65rem .75rem; color: #172b4d; }
.spmb-quick-links { display: flex; flex-wrap: wrap; gap: .5rem; }
.spmb-quick-links a { color: #3157a4; font-size: .85rem; font-weight: 700; }
.admissions-form { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
.compact-form { grid-template-columns: repeat(5, minmax(0, 1fr)); border-top: 1px solid #e2e8f0; padding-top: 1rem; }
.admissions-form-wide { grid-column: 1 / -1; }
.admissions-form-actions { display: flex; align-items: end; justify-content: end; gap: .75rem; }
.primary-button, .secondary-button { border: 1px solid #3157a4; border-radius: .65rem; background: #3157a4; padding: .65rem 1rem; color: white; font-weight: 700; white-space: nowrap; }
.secondary-button { background: white; color: #3157a4; }
.notice-success { border: 1px solid #bbf7d0; border-radius: .65rem; background: #f0fdf4; padding: .75rem 1rem; color: #166534; font-size: .9rem; }
.workflow-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
.workflow-grid div, .period-row, .track-card, .committee-list li, .document-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border: 1px solid #dbe3ef; border-radius: .75rem; padding: .85rem 1rem; }
.workflow-grid strong { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 50%; background: #eef3ff; color: #3157a4; }
.workflow-grid span, .period-row span, .track-card small, .committee-list li span, .score-line { color: #64748b; font-size: .82rem; }
.period-row, .track-card, .committee-list { margin-top: .65rem; }
.period-row div, .track-card div, .committee-list li div { display: grid; gap: .15rem; }
.status-pill { border-radius: 999px; background: #eef3ff; padding: .25rem .55rem; color: #3157a4; font-size: .75rem; font-weight: 700; white-space: nowrap; }
.committee-list { display: grid; padding: 0; list-style: none; }
.admissions-applicants { display: grid; gap: .85rem; }
.admissions-applicant { border: 1px solid #dbe3ef; border-radius: .85rem; padding: 1rem; }
.admissions-applicant-heading { display: flex; justify-content: space-between; gap: 1rem; align-items: start; }
.admissions-registration { color: #3157a4; font-size: .75rem; font-weight: 700; letter-spacing: .04em; }
.admissions-applicant h3 { margin: .2rem 0 0; color: #172b4d; font-size: 1.05rem; font-weight: 700; }
.admissions-applicant-details { display: flex; flex-wrap: wrap; gap: .5rem 1.5rem; padding: .75rem 0; color: #64748b; font-size: .82rem; }
.admissions-status-form, .decision-form, .upload-form { display: flex; align-items: end; gap: .75rem; border-top: 1px solid #e2e8f0; padding-top: .75rem; }
.admissions-status-form label { flex: 1; }
.document-list { display: grid; gap: .5rem; margin: .75rem 0; }
.document-row { padding: .6rem .75rem; font-size: .85rem; }
.document-row a, .link-danger { color: #3157a4; font-size: .8rem; font-weight: 700; }
.link-danger { border: 0; background: transparent; color: #b42318; cursor: pointer; }
.score-row { display: grid; grid-template-columns: 1fr 8rem auto; align-items: center; gap: .75rem; margin-top: .5rem; color: #42526b; font-size: .85rem; }
.decision-form { margin-top: .75rem; }
.decision-form select { max-width: 12rem; }
.empty-state { padding: 1.25rem 0; color: #64748b; }
@media (max-width: 820px) { .workflow-grid, .admissions-form, .compact-form { grid-template-columns: 1fr; } .admissions-form-wide { grid-column: auto; } .admissions-status-form, .decision-form, .upload-form, .score-row { display: grid; grid-template-columns: 1fr; align-items: stretch; } .admissions-applicant-heading { display: grid; } }
</style>
