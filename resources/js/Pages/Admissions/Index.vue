<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Requirement { id: number; code: string; label: string; is_required: boolean }
interface Criterion { id: number; code: string; label: string; weight: number; max_score: number }
interface Track { id: number; code: string; name: string; quota: number; requirements: Requirement[]; criteria: Criterion[] }
interface Period { id: number; name: string; academic_year: string; level: string | null; status: string; registration_start?: string | null; registration_end?: string | null; verification_start?: string | null; verification_end?: string | null; selection_start?: string | null; selection_end?: string | null; announcement_at?: string | null; re_registration_start?: string | null; re_registration_end?: string | null; tracks: Track[] }
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

const selectedPeriod = computed(() => props.periods.find((period) => period.id === props.selected_period_id) ?? null);
const overviewApplicants = computed(() => props.applicants);
const periodStatusLabels: Record<string, string> = {
    draft: 'Persiapan', registration_open: 'Pendaftaran dibuka', verification: 'Verifikasi berkas',
    selection: 'Seleksi', announcement: 'Pengumuman', re_registration: 'Daftar ulang',
    completed: 'Selesai', archived: 'Diarsipkan',
};
const phaseSteps = [
    { status: 'draft', label: 'Persiapan' }, { status: 'registration_open', label: 'Pendaftaran' },
    { status: 'verification', label: 'Verifikasi' }, { status: 'selection', label: 'Seleksi' },
    { status: 'announcement', label: 'Pengumuman' }, { status: 're_registration', label: 'Daftar ulang' },
];
const currentPhaseIndex = computed(() => {
    const status = selectedPeriod.value?.status;
    if (status === 'completed') return phaseSteps.length;
    if (status === 'archived') return -1;
    return phaseSteps.findIndex((step) => step.status === status);
});
const overviewCounts = computed(() => {
    const applicants = overviewApplicants.value;
    return {
        applicants: applicants.length,
        accepted: applicants.filter((applicant) => applicant.status === 'accepted' || applicant.status === 're_registered').length,
        waitlisted: applicants.filter((applicant) => applicant.status === 'waitlisted').length,
        reRegistered: applicants.filter((applicant) => applicant.status === 're_registered').length,
        documents: applicants.filter((applicant) => applicant.status === 'registered' || applicant.status === 'needs_revision').length,
        decisions: applicants.filter((applicant) => applicant.status === 'selection_review').length,
        reRegistration: applicants.filter((applicant) => applicant.status === 'accepted' && applicant.re_registration?.status !== 'confirmed').length,
    };
});
const trackOverview = computed(() => (selectedPeriod.value?.tracks ?? []).map((track) => {
    const trackApplicants = overviewApplicants.value.filter((applicant) => applicant.track?.id === track.id);
    const accepted = trackApplicants.filter((applicant) => applicant.status === 'accepted' || applicant.status === 're_registered').length;
    return { ...track, applicants: trackApplicants.length, accepted, fillRate: track.quota > 0 ? Math.min(100, Math.round((accepted / track.quota) * 100)) : 0 };
}));
const statusReport = computed(() => props.statuses.map((status) => ({
    ...status,
    count: props.applicants.filter((applicant) => applicant.status === status.value).length,
})));
const totalQuota = computed(() => props.tracks.reduce((total, track) => total + track.quota, 0));
const periodStatusLabel = computed(() => selectedPeriod.value ? (periodStatusLabels[selectedPeriod.value.status] ?? selectedPeriod.value.status) : 'Belum ada periode');
const workflowStatusLabels: Record<string, string> = {
    pending: 'Menunggu verifikasi', valid: 'Valid', needs_revision: 'Perlu perbaikan', confirmed: 'Selesai',
    accepted: 'Diterima', waitlisted: 'Cadangan', rejected: 'Tidak diterima',
};
const applicantStatusLabel = (value: string) => props.statuses.find((status) => status.value === value)?.label ?? workflowStatusLabels[value] ?? value;
const documentStatusLabel = (value: string) => workflowStatusLabels[value] ?? value;
const formatDate = (value?: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(`${value.slice(0, 10)}T00:00:00`))
    : null;
const formatDateRange = (start?: string | null, end?: string | null) => {
    const from = formatDate(start);
    const to = formatDate(end);
    if (from && to) return `${from} – ${to}`;
    return from ?? to ?? 'Tanggal belum diatur';
};
function periodHref(path: string) {
    return props.selected_period_id ? `${path}?period_id=${props.selected_period_id}` : path;
}
function periodSectionPath() {
    const sections: Record<string, string> = {
        periods: 'periode', tracks: 'jalur', committee: 'panitia', applicants: 'pendaftar',
        documents: 'berkas', selection: 'seleksi', announcement: 'pengumuman',
        're-registration': 'daftar-ulang', reports: 'laporan',
    };

    return sections[props.section] ? `/spmb/${sections[props.section]}` : '/spmb';
}
function changePeriod(event: Event) {
    const periodId = (event.target as HTMLSelectElement).value;
    router.get(periodSectionPath(), periodId ? { period_id: periodId } : {}, { preserveState: true, preserveScroll: true });
}

const page = usePage();
const flashMessage = () => (page.props.flash as { message?: string })?.message;
const periodForm = useForm({ name: '', academic_year: '', level: '', registration_start: '', registration_end: '', verification_start: '', verification_end: '', selection_start: '', selection_end: '', announcement_at: '', re_registration_start: '', re_registration_end: '', guideline_file: null as File | null });
const trackForm = useForm({ period_id: props.selected_period_id ? String(props.selected_period_id) : '', code: '', name: '', quota: 1 });
const requirementForm = useForm({ track_id: '', code: '', label: '', is_required: true });
const criterionForm = useForm({ track_id: '', code: '', label: '', weight: 1, max_score: 100 });
const applicantForm = useForm({ period_id: props.selected_period_id ? String(props.selected_period_id) : '', track_id: '', name: '', nisn: '', gender: 'L' as 'L' | 'P', birth_date: '', parent_name: '', parent_phone: '', previous_school: '', notes: '' });
const applicantTracks = computed(() => props.periods.find((period) => String(period.id) === applicantForm.period_id)?.tracks ?? []);
function changeApplicantPeriod(event: Event) {
    applicantForm.period_id = (event.target as HTMLSelectElement).value;
    applicantForm.track_id = '';
}
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
    <Head :title="section === 'dashboard' ? 'Dashboard SPMB' : 'Manajemen SPMB'" />
    <AppLayout :title="section === 'dashboard' ? 'Dashboard SPMB' : 'SPMB'">
        <div class="view space-y-6 spmb-page">
            <section v-if="section === 'dashboard'" class="spmb-overview">
                <header class="spmb-overview-heading">
                    <div><p class="spmb-kicker">Sistem Penerimaan Murid Baru</p><h1>Dashboard SPMB</h1><p>Pantau posisi penerimaan dan tindak lanjut untuk periode yang dipilih.</p></div>
                    <div class="overview-heading-actions"><Link href="/spmb/periode" class="overview-period-link">Kelola periode <span aria-hidden="true">↗</span></Link></div>
                </header>

                <section class="overview-period-picker" aria-label="Pilih periode untuk ditinjau">
                    <label for="overview-period">Periode yang ditinjau</label>
                    <select id="overview-period" :value="selected_period_id ?? ''" @change="changePeriod">
                        <option value="">Pilih periode</option><option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }} · {{ period.academic_year }} · {{ periodStatusLabels[period.status] ?? period.status }}</option>
                    </select>
                </section>

                <template v-if="selectedPeriod">
                    <section class="overview-period-banner">
                        <div><span class="period-overline">{{ selectedPeriod.academic_year }}<template v-if="selectedPeriod.level"> · {{ selectedPeriod.level }}</template></span><h2>{{ selectedPeriod.name }}</h2></div>
                        <span class="period-phase-pill" :class="`phase-${selectedPeriod.status}`"><i aria-hidden="true" />{{ periodStatusLabel }}</span>
                    </section>

                    <section class="overview-metrics" aria-label="Ringkasan periode terpilih">
                        <Link :href="periodHref('/spmb/pendaftar')" class="overview-metric metric-total"><span>Total pendaftar</span><strong>{{ overviewCounts.applicants }}</strong><small>Semua pendaftar pada periode ini</small></Link>
                        <Link :href="periodHref('/spmb/pengumuman')" class="overview-metric"><span>Diterima</span><strong>{{ overviewCounts.accepted }}</strong><small>Termasuk yang sudah daftar ulang</small></Link>
                        <Link :href="periodHref('/spmb/pengumuman')" class="overview-metric"><span>Cadangan</span><strong>{{ overviewCounts.waitlisted }}</strong><small>Menunggu perubahan kuota</small></Link>
                        <Link :href="periodHref('/spmb/daftar-ulang')" class="overview-metric"><span>Daftar ulang selesai</span><strong>{{ overviewCounts.reRegistered }}</strong><small>Sudah tercatat sebagai siswa</small></Link>
                    </section>

                    <div class="overview-columns">
                        <section class="overview-panel phase-panel">
                            <header class="overview-panel-heading"><div><h3>Posisi tahapan</h3><p>Alur penerimaan periode ini</p></div><span>{{ periodStatusLabel }}</span></header>
                            <ol class="phase-track" :class="{ 'phase-archived': selectedPeriod.status === 'archived' }">
                                <li v-for="(step, index) in phaseSteps" :key="step.status" :class="{ 'is-complete': currentPhaseIndex > index || selectedPeriod.status === 'completed', 'is-current': currentPhaseIndex === index }">
                                    <i aria-hidden="true" /><span>{{ step.label }}</span>
                                </li>
                            </ol>
                            <dl class="period-date-list">
                                <div><dt>Pendaftaran</dt><dd>{{ formatDateRange(selectedPeriod.registration_start, selectedPeriod.registration_end) }}</dd></div>
                                <div><dt>Verifikasi</dt><dd>{{ formatDateRange(selectedPeriod.verification_start, selectedPeriod.verification_end) }}</dd></div>
                                <div><dt>Seleksi</dt><dd>{{ formatDateRange(selectedPeriod.selection_start, selectedPeriod.selection_end) }}</dd></div>
                                <div><dt>Pengumuman</dt><dd>{{ formatDate(selectedPeriod.announcement_at) ?? 'Tanggal belum diatur' }}</dd></div>
                                <div><dt>Daftar ulang</dt><dd>{{ formatDateRange(selectedPeriod.re_registration_start, selectedPeriod.re_registration_end) }}</dd></div>
                            </dl>
                        </section>

                        <section class="overview-panel attention-panel">
                            <header class="overview-panel-heading"><div><h3>Ringkasan tindak lanjut</h3><p>Jumlah yang masih perlu diproses</p></div></header>
                            <Link :href="periodHref('/spmb/berkas')" class="attention-row"><span class="attention-marker marker-document" /><span><strong>Verifikasi berkas</strong><small>Pendaftar menunggu pemeriksaan dokumen</small></span><b>{{ overviewCounts.documents }}</b></Link>
                            <Link :href="periodHref('/spmb/seleksi')" class="attention-row"><span class="attention-marker marker-selection" /><span><strong>Keputusan seleksi</strong><small>Pendaftar menunggu keputusan akhir</small></span><b>{{ overviewCounts.decisions }}</b></Link>
                            <Link :href="periodHref('/spmb/daftar-ulang')" class="attention-row"><span class="attention-marker marker-reregistration" /><span><strong>Daftar ulang</strong><small>Pendaftar diterima belum menyelesaikan daftar ulang</small></span><b>{{ overviewCounts.reRegistration }}</b></Link>
                        </section>
                    </div>

                    <section class="overview-panel track-overview-panel">
                        <header class="overview-panel-heading"><div><h3>Peserta per jalur</h3><p>Pendaftar dan kuota penerimaan pada periode terpilih</p></div><Link :href="periodHref('/spmb/jalur')">Lihat jalur</Link></header>
                        <div v-if="trackOverview.length" class="track-overview-list">
                            <article v-for="track in trackOverview" :key="track.id" class="track-overview-row">
                                <div class="track-overview-name"><strong>{{ track.name }}</strong><span>{{ track.applicants }} pendaftar</span></div>
                                <div class="quota-progress" role="progressbar" :aria-label="`Kuota terisi jalur ${track.name}`" :aria-valuenow="track.accepted" :aria-valuemin="0" :aria-valuemax="track.quota"><i :style="{ width: `${track.fillRate}%` }" /></div>
                                <span class="track-overview-quota">{{ track.accepted }} / {{ track.quota }} kuota</span>
                            </article>
                        </div>
                        <p v-else class="overview-empty-inline">Belum ada jalur untuk periode ini. Atur jalur dan kuota melalui halaman Jalur.</p>
                    </section>
                </template>
                <section v-else class="overview-empty-state">
                    <span class="empty-state-mark" aria-hidden="true">＋</span><h2>Belum ada periode untuk ditinjau</h2><p>Buat periode SPMB terlebih dahulu. Jadwal dan tanggal penerimaan dikelola di halaman Periode.</p><Link href="/spmb/periode" class="overview-period-link">Buka halaman Periode <span aria-hidden="true">↗</span></Link>
                </section>
            </section>

            <section v-else class="hero-card">
                <div><p class="eyebrow">Sistem Penerimaan Murid Baru</p><h1>Manajemen SPMB</h1><p>Kelola periode, jalur, verifikasi, seleksi, pengumuman, dan daftar ulang dalam satu alur.</p></div>
                <div class="grade-summary admissions-summary"><div><span>Periode</span><strong>{{ summary.periods }}</strong></div><div><span>Pendaftar</span><strong>{{ summary.applicants }}</strong></div><div><span>Diterima</span><strong>{{ summary.accepted }}</strong></div><div><span>Daftar ulang</span><strong>{{ summary.re_registered }}</strong></div></div>
            </section>
            <p v-if="flashMessage()" class="notice-success" role="status">{{ flashMessage() }}</p>
            <section v-if="section !== 'dashboard'" class="panel admissions-panel period-context">
                <label>Periode yang ditinjau
                    <select :value="selected_period_id ?? ''" @change="changePeriod">
                        <option value="">Belum ada periode</option><option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }} · {{ periodStatusLabels[period.status] ?? period.status }}</option>
                    </select>
                </label>
                <div class="spmb-quick-links"><Link :href="periodHref('/spmb/periode')">Periode</Link><Link :href="periodHref('/spmb/jalur')">Jalur & kuota</Link><Link :href="periodHref('/spmb/panitia')">Panitia</Link><Link :href="periodHref('/spmb/pendaftar')">Pendaftar</Link><Link :href="periodHref('/spmb/berkas')">Berkas</Link><Link :href="periodHref('/spmb/seleksi')">Seleksi</Link><Link :href="periodHref('/spmb/pengumuman')">Pengumuman</Link><Link :href="periodHref('/spmb/daftar-ulang')">Daftar ulang</Link><Link :href="periodHref('/spmb/laporan')">Laporan</Link></div>
            </section>

            <section v-if="section === 'periods'" class="panel admissions-panel">
                <div class="table-title"><div><h2>Periode & Juknis</h2><p>Buat siklus SPMB yang dapat dikonfigurasi per tahun ajaran dan jenjang.</p></div></div>
                <form v-if="can_manage_committee" class="admissions-form" @submit.prevent="addPeriod"><label>Nama periode<input v-model="periodForm.name" required placeholder="SPMB 2027/2028" /></label><label>Tahun ajaran<input v-model="periodForm.academic_year" required placeholder="2027/2028" /></label><label>Jenjang<input v-model="periodForm.level" placeholder="SMP" /></label><label>Mulai pendaftaran<input v-model="periodForm.registration_start" type="date" /></label><label>Akhir pendaftaran<input v-model="periodForm.registration_end" type="date" /></label><label>Mulai verifikasi<input v-model="periodForm.verification_start" type="date" /></label><label>Akhir verifikasi<input v-model="periodForm.verification_end" type="date" /></label><label>Mulai seleksi<input v-model="periodForm.selection_start" type="date" /></label><label>Akhir seleksi<input v-model="periodForm.selection_end" type="date" /></label><label>Tanggal pengumuman<input v-model="periodForm.announcement_at" type="date" /></label><label>Mulai daftar ulang<input v-model="periodForm.re_registration_start" type="date" /></label><label>Akhir daftar ulang<input v-model="periodForm.re_registration_end" type="date" /></label><label>Juknis<input type="file" accept=".pdf,.doc,.docx" @change="periodForm.guideline_file = ($event.target as HTMLInputElement).files?.[0] || null" /></label><div class="admissions-form-actions"><button class="primary-button" type="submit" :disabled="periodForm.processing">Tambah periode</button></div></form>
                <div v-for="period in periods" :key="period.id" class="period-row"><div><strong>{{ period.name }}</strong><span>{{ period.academic_year }} · {{ period.level || 'Semua jenjang' }} · {{ period.tracks.length }} jalur</span></div><button v-if="can_manage_committee && period.status === 'draft'" class="secondary-button" type="button" @click="openPeriod(period)">Buka pendaftaran</button><span class="status-pill">{{ periodStatusLabels[period.status] ?? period.status }}</span></div>
            </section>

            <section v-if="section === 'tracks'" class="panel admissions-panel"><div class="table-title"><div><h2>Jalur, kuota, berkas & kriteria</h2><p>Konfigurasi mengikuti juknis periode, bukan angka hardcode.</p></div></div><form v-if="can_manage_committee && selected_period_id" class="admissions-form" @submit.prevent="addTrack"><label>Jalur<select v-model="trackForm.code"><option value="">Pilih jalur</option><option value="DOMISILI">Domisili</option><option value="AFIRMASI">Afirmasi</option><option value="PRESTASI">Prestasi</option><option value="MUTASI">Mutasi</option></select></label><label>Nama jalur<input v-model="trackForm.name" required /></label><label>Kuota<input v-model.number="trackForm.quota" type="number" min="1" required /></label><div class="admissions-form-actions"><button class="primary-button" type="submit">Tambah jalur</button></div></form><article v-for="track in tracks" :key="track.id" class="track-card"><div><strong>{{ track.name }}</strong><span>{{ track.code }} · kuota {{ track.quota }}</span></div><small>{{ track.requirements.length }} persyaratan · {{ track.criteria.length }} kriteria</small></article><form v-if="can_manage_committee && tracks.length" class="admissions-form compact-form" @submit.prevent="addRequirement"><label>Jalur<select v-model="requirementForm.track_id" required><option value="">Pilih jalur</option><option v-for="track in tracks" :key="track.id" :value="track.id">{{ track.name }}</option></select></label><label>Kode berkas<input v-model="requirementForm.code" required placeholder="KK" /></label><label>Nama berkas<input v-model="requirementForm.label" required placeholder="Kartu Keluarga" /></label><button class="secondary-button" type="submit">Tambah berkas</button></form><form v-if="can_manage_committee && tracks.length" class="admissions-form compact-form" @submit.prevent="addCriterion"><label>Jalur<select v-model="criterionForm.track_id" required><option value="">Pilih jalur</option><option v-for="track in tracks" :key="track.id" :value="track.id">{{ track.name }}</option></select></label><label>Kode kriteria<input v-model="criterionForm.code" required placeholder="NILAI" /></label><label>Nama kriteria<input v-model="criterionForm.label" required placeholder="Nilai seleksi" /></label><label>Bobot<input v-model.number="criterionForm.weight" type="number" min="0.01" step="0.01" /></label><button class="secondary-button" type="submit">Tambah kriteria</button></form></section>

            <section v-if="section === 'committee'" class="panel admissions-panel"><div class="table-title"><div><h2>Panitia & jadwal</h2><p>Panitia guru dapat memverifikasi dan menilai pendaftar yang ditugaskan.</p></div></div><form v-if="can_manage_committee" class="admissions-assign-form" @submit.prevent="assignTeacher"><select v-model="committeeForm.user_id" required><option value="">Pilih guru</option><option v-for="teacher in available_teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }} · {{ teacher.email }}</option></select><button class="primary-button" type="submit">Tugaskan guru</button></form><ul class="committee-list"><li v-for="member in committee" :key="member.id"><div><strong>{{ member.name }}</strong><span>{{ member.email }}</span></div><button v-if="can_manage_committee" class="secondary-button" type="button" @click="removeMember(member)">Cabut</button></li></ul></section>

            <section v-if="section === 'applicants'" class="panel admissions-panel"><div class="table-title"><div><h2>Pendaftar</h2><p>Satu pendaftaran aktif memilih satu jalur dalam satu periode.</p></div></div><form v-if="can_manage_applicants" class="admissions-form" @submit.prevent="addApplicant"><label>Periode<select v-model="applicantForm.period_id" required><option value="">Pilih periode</option><option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }}</option></select></label><label>Jalur<select v-model="applicantForm.track_id" required><option value="">Pilih jalur</option><option v-for="track in tracks" :key="track.id" :value="track.id">{{ track.name }}</option></select></label><label>Nama calon siswa<input v-model="applicantForm.name" required /></label><label>NISN<input v-model="applicantForm.nisn" inputmode="numeric" /></label><label>Jenis kelamin<select v-model="applicantForm.gender"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></label><label>Tanggal lahir<input v-model="applicantForm.birth_date" type="date" /></label><label>Nama orang tua/wali<input v-model="applicantForm.parent_name" /></label><label>Kontak orang tua<input v-model="applicantForm.parent_phone" /></label><label>Asal sekolah<input v-model="applicantForm.previous_school" /></label><label class="admissions-form-wide">Catatan<textarea v-model="applicantForm.notes" rows="2" /></label><div class="admissions-form-actions admissions-form-wide"><button class="primary-button" type="submit">Simpan pendaftar</button></div></form><div class="admissions-applicants"><article v-for="applicant in applicants" :key="applicant.id" class="admissions-applicant"><div class="admissions-applicant-heading"><div><span class="admissions-registration">{{ applicant.registration_no }}</span><h3>{{ applicant.name }}</h3></div><span class="status-pill">{{ applicantStatusLabel(applicant.status) }}</span></div><div class="admissions-applicant-details"><span>{{ applicant.track?.name || 'Legacy' }}</span><span>NISN {{ applicant.nisn || '—' }}</span><span>Asal {{ applicant.previous_school || '—' }}</span></div><form v-if="can_manage_applicants" class="admissions-status-form" @submit.prevent="updateApplicant(applicant, $event)"><label>Status<select name="status" :value="applicant.status"><option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option></select></label><label>Catatan<textarea name="notes" rows="2">{{ applicant.notes }}</textarea></label><button class="secondary-button" type="submit">Simpan</button></form></article><p v-if="!applicants.length" class="empty-state">Belum ada pendaftar pada periode ini.</p></div></section>

            <section v-if="section === 'documents'" class="panel admissions-panel"><div class="table-title"><div><h2>Verifikasi berkas</h2><p>Unggah dan periksa persyaratan setiap pendaftar.</p></div></div><article v-for="applicant in applicants" :key="applicant.id" class="admissions-applicant"><div class="admissions-applicant-heading"><div><span class="admissions-registration">{{ applicant.registration_no }}</span><h3>{{ applicant.name }}</h3></div></div><div class="document-list"><div v-for="document in applicant.documents" :key="document.id" class="document-row"><span>{{ document.requirement?.label || document.original_name }}</span><span class="status-pill">{{ document.status }}</span><a :href="`/spmb/berkas/${document.id}/unduh`">Unduh</a><button v-if="can_manage_applicants" class="secondary-button" type="button" @click="verifyDocument(document, 'valid')">Valid</button><button v-if="can_manage_applicants" class="link-danger" type="button" @click="verifyDocument(document, 'needs_revision')">Perlu perbaikan</button></div></div><form v-if="can_manage_applicants && applicant.track" class="upload-form" @submit.prevent="uploadDocument(applicant)"><select v-model="documentForm(applicant).requirement_id"><option value="">Berkas umum</option><option v-for="requirement in applicant.track.requirements" :key="requirement.id" :value="requirement.id">{{ requirement.label }}</option></select><input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="documentForm(applicant).file = ($event.target as HTMLInputElement).files?.[0] || null" required /><button class="secondary-button" type="submit">Unggah</button></form></article><p v-if="!applicants.length" class="empty-state">Belum ada pendaftar untuk diverifikasi.</p></section>

            <section v-if="section === 'selection' || section === 'announcement'" class="panel admissions-panel"><div class="table-title"><div><h2>{{ section === 'selection' ? 'Seleksi & ranking' : 'Pengumuman' }}</h2><p>Hasil dihitung dari kriteria periode dan keputusan akhir memiliki jejak audit.</p></div><button v-if="section === 'selection' && can_manage_committee" class="primary-button" type="button" @click="rankPeriod">Hitung ranking</button></div><article v-for="applicant in applicants" :key="applicant.id" class="admissions-applicant"><div class="admissions-applicant-heading"><div><span class="admissions-registration">{{ applicant.registration_no }}</span><h3>{{ applicant.name }}</h3></div><span class="status-pill">{{ applicant.selection_result?.decision || 'Belum diputuskan' }} · #{{ applicant.selection_result?.rank || '—' }}</span></div><p class="score-line">Total skor: {{ applicant.selection_result?.total_score || 0 }}</p><div v-if="section === 'selection' && can_manage_applicants" v-for="criterion in applicant.track?.criteria || []" :key="criterion.id" class="score-row"><span>{{ criterion.label }}</span><input v-model="scoreForm(applicant, criterion).score" type="number" min="0" :max="criterion.max_score" /><button class="secondary-button" type="button" @click="saveScore(applicant, criterion)">Simpan nilai</button></div><form v-if="section === 'selection' && can_manage_applicants" class="decision-form" @submit.prevent="finalizeApplicant(applicant)"><select v-model="decisionForm(applicant).decision"><option value="accepted">Diterima</option><option value="waitlisted">Cadangan</option><option value="rejected">Tidak diterima</option></select><input v-model="decisionForm(applicant).override_reason" placeholder="Alasan keputusan / override" required /><button class="secondary-button" type="submit">Finalisasi</button></form></article><p v-if="!applicants.length" class="empty-state">Belum ada data seleksi.</p></section>

            <section v-if="section === 'reports'" class="report-grid">
                <article class="panel admissions-panel report-panel">
                    <div class="table-title"><div><h2>Rekapitulasi per jalur</h2><p>Jumlah pendaftar dan penggunaan kuota periode yang dipilih.</p></div></div>
                    <div class="report-table-wrap"><table class="report-table"><thead><tr><th>Jalur</th><th>Pendaftar</th><th>Diterima</th><th>Kuota</th></tr></thead><tbody><tr v-for="track in trackOverview" :key="track.id"><th scope="row">{{ track.name }}</th><td>{{ track.applicants }}</td><td>{{ track.accepted }}</td><td>{{ track.accepted }} / {{ track.quota }}</td></tr><tr v-if="!trackOverview.length"><td colspan="4" class="report-empty">Belum ada jalur pada periode ini.</td></tr></tbody><tfoot><tr><th scope="row">Total</th><td>{{ applicants.length }}</td><td>{{ summary.accepted + summary.re_registered }}</td><td>{{ totalQuota }}</td></tr></tfoot></table></div>
                </article>
                <article class="panel admissions-panel report-panel">
                    <div class="table-title"><div><h2>Rekapitulasi status</h2><p>Posisi setiap pendaftar dalam alur penerimaan.</p></div></div>
                    <div class="status-report-list"><div v-for="status in statusReport" :key="status.value"><span>{{ status.label }}</span><strong>{{ status.count }}</strong></div></div>
                </article>
            </section>

            <section v-if="section === 're-registration'" class="panel admissions-panel"><div class="table-title"><div><h2>Daftar ulang</h2><p>Pendaftar diterima dibuat menjadi data siswa setelah NIS dikonfirmasi.</p></div></div><article v-for="applicant in applicants.filter((item) => item.status === 'accepted' || item.status === 're_registered')" :key="applicant.id" class="admissions-applicant"><div class="admissions-applicant-heading"><div><span class="admissions-registration">{{ applicant.registration_no }}</span><h3>{{ applicant.name }}</h3></div><span class="status-pill">{{ applicant.re_registration?.status || 'pending' }}</span></div><form v-if="can_manage_applicants && !applicant.re_registration?.student_id" class="upload-form" @submit.prevent="confirmReRegistration(applicant)"><input v-model="reRegistrationForm(applicant).nis" placeholder="NIS siswa" required /><button class="primary-button" type="submit">Konfirmasi daftar ulang</button></form><p v-else class="score-line">Terhubung ke data siswa #{{ applicant.re_registration?.student_id }}</p></article></section>
        </div>
    </AppLayout>
</template>

<style scoped>
.spmb-overview { display: grid; gap: 1.15rem; }
.spmb-overview-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; }
.overview-heading-actions { display: flex; align-items: center; gap: .6rem; }
.spmb-kicker { margin: 0 0 .4rem; color: var(--primary); font-size: .78rem; font-weight: 700; }
.spmb-overview-heading h1 { margin: 0; color: var(--ink); font-size: clamp(1.7rem, 3vw, 2.25rem); letter-spacing: -.045em; }
.spmb-overview-heading p:not(.spmb-kicker) { margin: .45rem 0 0; color: var(--ink-soft); font-size: .92rem; }
.overview-period-link { display: inline-flex; align-items: center; justify-content: center; gap: .65rem; min-height: 2.75rem; border: 1px solid var(--line-strong); border-radius: .7rem; padding: .65rem .9rem; color: var(--primary); background: var(--surface); font-size: .85rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
.overview-period-link:hover { border-color: var(--primary); background: var(--primary-soft); }
.overview-period-picker { display: flex; align-items: center; gap: 1rem; padding: .85rem 1rem; border: 1px solid var(--line); border-radius: .85rem; background: var(--surface); }
.overview-period-picker label { color: var(--ink-soft); font-size: .83rem; font-weight: 700; }
.overview-period-picker select { min-width: min(24rem, 100%); border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); padding: .62rem .75rem; color: var(--ink); }
.overview-period-banner { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.3rem; border-left: 4px solid var(--primary); border-radius: .25rem .85rem .85rem .25rem; background: color-mix(in srgb, var(--primary-soft) 62%, var(--surface)); }
.period-overline { color: var(--ink-soft); font-size: .8rem; font-weight: 650; }
.overview-period-banner h2 { margin: .2rem 0 0; color: var(--ink); font-size: 1.35rem; letter-spacing: -.025em; }
.period-phase-pill { display: inline-flex; align-items: center; gap: .5rem; border: 1px solid var(--line); border-radius: 999px; padding: .45rem .7rem; color: var(--ink); background: var(--surface); font-size: .8rem; font-weight: 700; white-space: nowrap; }
.period-phase-pill i { width: .5rem; height: .5rem; border-radius: 50%; background: var(--primary); }
.phase-registration_open i, .phase-verification i, .phase-selection i, .phase-announcement i, .phase-re_registration i { background: #138a62; }
.phase-completed i, .phase-archived i { background: #7a8798; }
.overview-metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .7rem; }
.overview-metric { display: grid; align-content: start; min-height: 7.6rem; padding: .95rem 1rem; border: 1px solid var(--line); border-radius: .8rem; color: inherit; background: var(--surface); text-decoration: none; }
.overview-metric:hover { border-color: var(--primary); }
.overview-metric > span { color: var(--ink-soft); font-size: .78rem; font-weight: 650; }
.overview-metric strong { margin-top: .3rem; color: var(--ink); font-size: 1.8rem; line-height: 1.15; font-variant-numeric: tabular-nums; }
.overview-metric small { margin-top: .3rem; color: var(--ink-soft); font-size: .72rem; line-height: 1.4; }
.overview-metric.metric-total { border-top: 3px solid var(--primary); }
.overview-columns { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(19rem, .9fr); gap: .9rem; }
.overview-panel { min-width: 0; padding: 1.15rem 1.2rem; border: 1px solid var(--line); border-radius: .85rem; background: var(--surface); }
.overview-panel-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; margin-bottom: .9rem; }
.overview-panel-heading h3 { margin: 0; color: var(--ink); font-size: 1rem; letter-spacing: -.015em; }
.overview-panel-heading p { margin: .25rem 0 0; color: var(--ink-soft); font-size: .78rem; }
.overview-panel-heading > span { color: var(--primary); font-size: .75rem; font-weight: 700; }
.overview-panel-heading > a { color: var(--primary); font-size: .8rem; font-weight: 700; text-decoration: none; }
.phase-track { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); margin: .7rem 0 1.1rem; padding: 0; list-style: none; }
.phase-track li { position: relative; display: grid; justify-items: center; gap: .4rem; color: var(--ink-faint); font-size: .66rem; text-align: center; }
.phase-track li::before { position: absolute; top: .4rem; left: calc(-50% + .42rem); width: calc(100% - .84rem); height: 2px; content: ''; background: var(--line); }
.phase-track li:first-child::before { display: none; }
.phase-track li i { z-index: 1; width: .85rem; height: .85rem; border: 2px solid var(--line-strong); border-radius: 50%; background: var(--surface); }
.phase-track li.is-complete { color: var(--ink-soft); }
.phase-track li.is-complete i { border-color: var(--primary); background: var(--primary); }
.phase-track li.is-complete::before { background: var(--primary); }
.phase-track li.is-current { color: var(--primary); font-weight: 750; }
.phase-track li.is-current i { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
.period-date-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 1rem; margin: 0; border-top: 1px solid var(--line); }
.period-date-list div { display: flex; justify-content: space-between; gap: .6rem; padding: .62rem 0; border-bottom: 1px solid var(--line); }
.period-date-list dt { color: var(--ink-soft); font-size: .75rem; }
.period-date-list dd { margin: 0; color: var(--ink); font-size: .75rem; font-weight: 650; text-align: right; }
.attention-panel { padding-bottom: .55rem; }
.attention-row { display: grid; grid-template-columns: .65rem minmax(0, 1fr) auto; align-items: center; gap: .7rem; min-height: 4.1rem; border-top: 1px solid var(--line); color: inherit; text-decoration: none; }
.attention-row:hover strong { color: var(--primary); }
.attention-marker { width: .55rem; height: .55rem; border-radius: 50%; background: #d89b32; }
.marker-selection { background: #596fb4; }
.marker-reregistration { background: #138a62; }
.attention-row > span:nth-child(2) { display: grid; gap: .2rem; }
.attention-row strong { color: var(--ink); font-size: .8rem; }
.attention-row small { color: var(--ink-soft); font-size: .7rem; line-height: 1.35; }
.attention-row b { min-width: 1.8rem; color: var(--ink); font-size: 1.05rem; text-align: right; font-variant-numeric: tabular-nums; }
.track-overview-list { border-top: 1px solid var(--line); }
.track-overview-row { display: grid; grid-template-columns: minmax(9rem, .8fr) minmax(5rem, 1.4fr) minmax(6.5rem, auto); align-items: center; gap: 1rem; min-height: 3.6rem; border-bottom: 1px solid var(--line); }
.track-overview-name { display: grid; gap: .2rem; }
.track-overview-name strong { color: var(--ink); font-size: .8rem; }
.track-overview-name span, .track-overview-quota { color: var(--ink-soft); font-size: .72rem; }
.track-overview-quota { text-align: right; }
.quota-progress { height: .38rem; overflow: hidden; border-radius: 999px; background: var(--primary-soft); }
.quota-progress i { display: block; height: 100%; border-radius: inherit; background: var(--primary); }
.overview-empty-inline { margin: 0; padding: .8rem 0 .25rem; color: var(--ink-soft); font-size: .82rem; }
.overview-empty-state { display: grid; justify-items: center; padding: 3.2rem 1rem; border: 1px dashed var(--line-strong); border-radius: .9rem; background: var(--surface); text-align: center; }
.empty-state-mark { display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border: 1px solid var(--line); border-radius: .75rem; color: var(--primary); font-size: 1.5rem; }
.overview-empty-state h2 { margin: .9rem 0 .35rem; color: var(--ink); font-size: 1.2rem; }
.overview-empty-state p { max-width: 30rem; margin: 0 0 1rem; color: var(--ink-soft); font-size: .86rem; line-height: 1.55; }
.admissions-summary { margin: 0; min-width: min(100%, 32rem); }
.admissions-panel { padding: 1.25rem; color: var(--ink); background: var(--surface); border-color: var(--line); }
.period-context { display: flex; align-items: end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.period-context label, .admissions-form label, .admissions-status-form label { display: grid; gap: .4rem; color: var(--ink-soft); font-size: .85rem; font-weight: 600; }
.period-context select, .admissions-form input, .admissions-form select, .admissions-form textarea, .admissions-status-form select, .admissions-status-form textarea, .upload-form input, .upload-form select, .decision-form input, .decision-form select, .score-row input { width: 100%; border: 1px solid var(--line-strong); border-radius: .65rem; background: var(--surface); padding: .65rem .75rem; color: var(--ink); }
.spmb-quick-links { display: flex; flex-wrap: wrap; gap: .5rem; }
.spmb-quick-links a { color: var(--primary); font-size: .85rem; font-weight: 700; }
.admissions-form { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
.compact-form { grid-template-columns: repeat(5, minmax(0, 1fr)); border-top: 1px solid #e2e8f0; padding-top: 1rem; }
.admissions-form-wide { grid-column: 1 / -1; }
.admissions-form-actions { display: flex; align-items: end; justify-content: end; gap: .75rem; }
.primary-button, .secondary-button { border: 1px solid var(--primary); border-radius: .65rem; background: var(--primary); padding: .65rem 1rem; color: #fff; font-weight: 700; white-space: nowrap; }
.secondary-button { background: var(--surface); color: var(--primary); }
.notice-success { border: 1px solid color-mix(in srgb, var(--green) 25%, var(--line)); border-radius: .65rem; background: var(--green-soft); padding: .75rem 1rem; color: var(--green); font-size: .9rem; }
.workflow-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
.workflow-grid div, .period-row, .track-card, .committee-list li, .document-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border: 1px solid var(--line); border-radius: .75rem; padding: .85rem 1rem; }
.workflow-grid strong { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 50%; background: var(--primary-soft); color: var(--primary); }
.workflow-grid span, .period-row span, .track-card small, .committee-list li span, .score-line { color: var(--ink-soft); font-size: .82rem; }
.period-row, .track-card, .committee-list { margin-top: .65rem; }
.period-row div, .track-card div, .committee-list li div { display: grid; gap: .15rem; }
.status-pill { border-radius: 999px; background: var(--primary-soft); padding: .25rem .55rem; color: var(--primary); font-size: .75rem; font-weight: 700; white-space: nowrap; }
.committee-list { display: grid; padding: 0; list-style: none; }
.admissions-applicants { display: grid; gap: .85rem; }
.admissions-applicant { border: 1px solid var(--line); border-radius: .85rem; padding: 1rem; background: var(--surface); }
.admissions-applicant-heading { display: flex; justify-content: space-between; gap: 1rem; align-items: start; }
.admissions-registration { color: var(--primary); font-size: .75rem; font-weight: 700; letter-spacing: .04em; }
.admissions-applicant h3 { margin: .2rem 0 0; color: var(--ink); font-size: 1.05rem; font-weight: 700; }
.admissions-applicant-details { display: flex; flex-wrap: wrap; gap: .5rem 1.5rem; padding: .75rem 0; color: var(--ink-soft); font-size: .82rem; }
.admissions-status-form, .decision-form, .upload-form { display: flex; align-items: end; gap: .75rem; border-top: 1px solid var(--line); padding-top: .75rem; }
.admissions-status-form label { flex: 1; }
.document-list { display: grid; gap: .5rem; margin: .75rem 0; }
.document-row { padding: .6rem .75rem; font-size: .85rem; }
.document-row a, .link-danger { color: #3157a4; font-size: .8rem; font-weight: 700; }
.link-danger { border: 0; background: transparent; color: #b42318; cursor: pointer; }
.score-row { display: grid; grid-template-columns: 1fr 8rem auto; align-items: center; gap: .75rem; margin-top: .5rem; color: #42526b; font-size: .85rem; }
.decision-form { margin-top: .75rem; }
.decision-form select { max-width: 12rem; }
.empty-state { padding: 1.25rem 0; color: var(--ink-soft); }
.report-grid { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(17rem, .75fr); gap: .9rem; }
.report-panel { min-width: 0; }
.report-table-wrap { overflow-x: auto; }
.report-table { width: 100%; border-collapse: collapse; font-size: .82rem; text-align: left; }
.report-table th, .report-table td { padding: .7rem .55rem; border-bottom: 1px solid var(--line); }
.report-table th { color: var(--ink); font-weight: 700; }
.report-table td { color: var(--ink-soft); }
.report-table thead th { color: var(--ink-soft); font-size: .74rem; font-weight: 650; }
.report-table tfoot th, .report-table tfoot td { border-bottom: 0; color: var(--ink); font-weight: 750; }
.report-empty { padding: 1.2rem .55rem !important; text-align: center; }
.status-report-list { border-top: 1px solid var(--line); }
.status-report-list div { display: flex; justify-content: space-between; gap: 1rem; padding: .62rem 0; border-bottom: 1px solid var(--line); color: var(--ink-soft); font-size: .8rem; }
.status-report-list strong { color: var(--ink); font-variant-numeric: tabular-nums; }
@media (max-width: 980px) { .overview-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }.overview-columns { grid-template-columns: 1fr; } }
@media (max-width: 820px) { .workflow-grid, .admissions-form, .compact-form { grid-template-columns: 1fr; } .admissions-form-wide { grid-column: auto; } .admissions-status-form, .decision-form, .upload-form, .score-row { display: grid; grid-template-columns: 1fr; align-items: stretch; } .admissions-applicant-heading { display: grid; }.spmb-overview-heading { align-items: start; flex-direction: column; }.overview-heading-actions { width: 100%; flex-wrap: wrap; }.overview-period-picker { align-items: stretch; flex-direction: column; gap: .45rem; }.overview-period-picker select { min-width: 0; }.overview-period-banner { align-items: start; flex-direction: column; }.track-overview-row { grid-template-columns: minmax(0, 1fr) auto; gap: .55rem .8rem; padding: .65rem 0; }.quota-progress { grid-column: 1 / -1; grid-row: 2; }.track-overview-quota { grid-column: 2; grid-row: 1; } }
@media (max-width: 560px) { .overview-metrics { gap: .55rem; }.overview-metric { min-height: 7.2rem; padding: .8rem; }.overview-metric strong { font-size: 1.55rem; }.overview-metric small { font-size: .68rem; }.phase-track li { font-size: .58rem; }.phase-track { margin-inline: -.2rem; }.period-date-list { grid-template-columns: 1fr; }.overview-panel { padding: 1rem; }.overview-period-link { width: 100%; }.overview-period-picker select { width: 100%; }.report-grid { grid-template-columns: 1fr; } }
</style>
