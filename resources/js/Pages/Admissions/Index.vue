<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Requirement { id: number; code: string; label: string; is_required: boolean }
interface Criterion { id: number; code: string; label: string; weight: number; max_score: number }
interface Track { id: number; code: string; name: string; quota: number; requirements: Requirement[]; criteria: Criterion[] }
interface Period { id: number; name: string; academic_year: string; level: string | null; status: string; registration_start?: string | null; registration_end?: string | null; verification_start?: string | null; verification_end?: string | null; selection_start?: string | null; selection_end?: string | null; announcement_at?: string | null; re_registration_start?: string | null; re_registration_end?: string | null; tracks: Track[] }
interface Document { id: number; original_name: string; status: string; requirement?: Requirement }
interface ApplicantScore { id: number; criterion_id: number; score: number | string; notes?: string | null; criterion?: Criterion }
interface Applicant { id: number; period_id?: number | null; track_id?: number | null; registration_no: string; name: string; nisn: string | null; gender: 'L' | 'P'; birth_date: string | null; parent_name: string | null; parent_phone: string | null; previous_school: string | null; status: string; notes: string | null; created_at?: string | null; track?: Track; selection_result?: { total_score: number; rank: number | null; decision: string | null }; documents: Document[]; scores?: ApplicantScore[]; re_registration?: { status: string; student_id: number | null } }
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
const workspaceMode = computed<'overview' | 'intake' | 'decision' | 'settings'>(() => {
    if (props.section === 'dashboard' || props.section === 'reports') return 'overview';
    if (props.section === 'applicants' || props.section === 'documents') return 'intake';
    if (props.section === 'selection' || props.section === 'announcement' || props.section === 're-registration') return 'decision';
    return 'settings';
});
const activeOverviewTab = ref<'summary' | 'reports'>(props.section === 'reports' ? 'reports' : 'summary');
const activeIntakeTab = ref<'all' | 'documents'>(props.section === 'documents' ? 'documents' : 'all');
const activeDecisionTab = ref<'selection' | 'announcement' | 're-registration'>(
    props.section === 'announcement' ? 'announcement' : (props.section === 're-registration' ? 're-registration' : 'selection'),
);
const activeSettingsTab = ref<'periods' | 'tracks' | 'committee'>(
    props.section === 'tracks' ? 'tracks' : (props.section === 'committee' ? 'committee' : 'periods'),
);
const selectedApplicantId = ref<number | null>(null);
const selectedApplicant = computed(() => props.applicants.find((applicant) => applicant.id === selectedApplicantId.value) ?? null);
function openApplicantDrawer(applicant: Applicant) {
    selectedApplicantId.value = applicant.id;
}
function closeApplicantDrawer() {
    selectedApplicantId.value = null;
}

watch(() => props.section, (nextSection) => {
    if (nextSection === 'reports') activeOverviewTab.value = 'reports';
    else if (nextSection === 'dashboard') activeOverviewTab.value = 'summary';
    else if (nextSection === 'documents') activeIntakeTab.value = 'documents';
    else if (nextSection === 'applicants') activeIntakeTab.value = 'all';
    else if (nextSection === 'announcement') activeDecisionTab.value = 'announcement';
    else if (nextSection === 're-registration') activeDecisionTab.value = 're-registration';
    else if (nextSection === 'selection') activeDecisionTab.value = 'selection';
    else if (nextSection === 'tracks') activeSettingsTab.value = 'tracks';
    else if (nextSection === 'committee') activeSettingsTab.value = 'committee';
    else if (nextSection === 'periods' || nextSection === 'settings') activeSettingsTab.value = 'periods';
});

const periodReadiness = computed(() => {
    const period = selectedPeriod.value;
    const hasPeriod = Boolean(period);
    const activeTracks = period?.tracks ?? [];
    const hasTracks = activeTracks.length > 0 && activeTracks.every((track) => track.quota >= 1);
    const hasRequirements = activeTracks.length > 0 && activeTracks.every((track) => track.requirements.length >= 1);
    const hasCriteria = activeTracks.length > 0 && activeTracks.every((track) => track.criteria.length >= 1);
    const hasCommittee = props.committee.length > 0;

    return {
        hasPeriod,
        hasTracks,
        hasRequirements,
        hasCriteria,
        hasCommittee,
        canOpen: hasPeriod && hasTracks && hasRequirements && hasCriteria,
    };
});

const sectionTitle = computed(() => ({
    dashboard: 'Dashboard & Laporan SPMB',
    reports: 'Dashboard & Laporan SPMB',
    applicants: 'Pendaftar & Verifikasi',
    documents: 'Pendaftar & Verifikasi',
    selection: 'Seleksi & Daftar Ulang',
    announcement: 'Seleksi & Daftar Ulang',
    're-registration': 'Seleksi & Daftar Ulang',
    settings: 'Pengaturan SPMB',
    periods: 'Pengaturan SPMB',
    tracks: 'Pengaturan SPMB',
    committee: 'Pengaturan SPMB',
} as Record<string, string>)[props.section] ?? 'Manajemen SPMB');
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
const applicantSearch = ref('');
const applicantStatusFilter = ref('');
const applicantTrackFilter = ref('');
const showApplicantForm = ref(false);
const applicantStatusSummary = computed(() => [
    { value: 'needs_revision', label: applicantStatusLabel('needs_revision'), hint: 'Perlu ditindaklanjuti' },
    { value: 'selection_review', label: applicantStatusLabel('selection_review'), hint: 'Menunggu penilaian' },
    { value: 'accepted', label: applicantStatusLabel('accepted'), hint: 'Hasil seleksi' },
]);
const filteredApplicants = computed(() => {
    const search = applicantSearch.value.trim().toLowerCase();

    return props.applicants.filter((applicant) => {
        const searchable = [applicant.registration_no, applicant.name, applicant.nisn, applicant.parent_name, applicant.previous_school]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();
        const matchesSearch = !search || searchable.includes(search);
        const matchesStatus = !applicantStatusFilter.value || applicant.status === applicantStatusFilter.value;
        const matchesTrack = !applicantTrackFilter.value || String(applicant.track?.id ?? '') === applicantTrackFilter.value;

        return matchesSearch && matchesStatus && matchesTrack;
    });
});
const hasApplicantFilters = computed(() => Boolean(applicantSearch.value || applicantStatusFilter.value || applicantTrackFilter.value));
function resetApplicantFilters() {
    applicantSearch.value = '';
    applicantStatusFilter.value = '';
    applicantTrackFilter.value = '';
}
const totalQuota = computed(() => props.tracks.reduce((total, track) => total + track.quota, 0));
const periodStatusLabel = computed(() => selectedPeriod.value ? (periodStatusLabels[selectedPeriod.value.status] ?? selectedPeriod.value.status) : 'Belum ada periode');
const workflowStatusLabels: Record<string, string> = {
    pending: 'Menunggu verifikasi', valid: 'Valid', needs_revision: 'Perlu perbaikan', confirmed: 'Selesai',
    accepted: 'Diterima', waitlisted: 'Cadangan', rejected: 'Tidak diterima',
};
const applicantStatusLabel = (value: string) => props.statuses.find((status) => status.value === value)?.label ?? workflowStatusLabels[value] ?? value;
const documentStatusLabel = (value: string) => workflowStatusLabels[value] ?? value;
const documentSearch = ref('');
const documentStatusFilter = ref('');
const documentSummary = computed(() => {
    const documents = props.applicants.flatMap((applicant) => applicant.documents);

    return {
        applicants: props.applicants.length,
        total: documents.length,
        valid: documents.filter((document) => document.status === 'valid').length,
        pending: documents.filter((document) => document.status === 'pending').length,
        needsRevision: documents.filter((document) => document.status === 'needs_revision').length,
    };
});
const filteredDocumentApplicants = computed(() => {
    const search = documentSearch.value.trim().toLowerCase();

    return props.applicants.filter((applicant) => {
        const searchable = [applicant.registration_no, applicant.name, applicant.nisn, applicant.previous_school]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();
        const matchesSearch = !search || searchable.includes(search);
        const matchesStatus = !documentStatusFilter.value || applicant.documents.some((document) => document.status === documentStatusFilter.value);

        return matchesSearch && matchesStatus;
    });
});
const hasDocumentFilters = computed(() => Boolean(documentSearch.value || documentStatusFilter.value));
const applicantDocumentSummary = (applicant: Applicant) => {
    const documents = applicant.documents;
    if (!documents.length) return { label: 'Belum ada berkas', className: 'document-summary-empty' };
    if (documents.some((document) => document.status === 'needs_revision')) return { label: 'Perlu perbaikan', className: 'document-summary-revision' };
    if (documents.every((document) => document.status === 'valid')) return { label: 'Lengkap & valid', className: 'document-summary-valid' };
    return { label: 'Menunggu verifikasi', className: 'document-summary-pending' };
};
function resetDocumentFilters() {
    documentSearch.value = '';
    documentStatusFilter.value = '';
}
const selectionSearch = ref('');
const selectionTrackFilter = ref('');
const selectionDecisionFilter = ref('');
const selectionSummary = computed(() => ({
    applicants: props.applicants.length,
    ranked: props.applicants.filter((applicant) => applicant.selection_result?.rank !== null && applicant.selection_result?.rank !== undefined).length,
    evaluated: props.applicants.filter((applicant) => (applicant.selection_result?.total_score ?? 0) > 0).length,
    decided: props.applicants.filter((applicant) => Boolean(applicant.selection_result?.decision)).length,
}));
const filteredSelectionApplicants = computed(() => {
    const search = selectionSearch.value.trim().toLowerCase();

    return props.applicants
        .filter((applicant) => {
            const searchable = [applicant.registration_no, applicant.name, applicant.nisn, applicant.previous_school]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();
            const decision = applicant.selection_result?.decision ?? 'pending';

            return (!search || searchable.includes(search))
                && (!selectionTrackFilter.value || String(applicant.track?.id ?? '') === selectionTrackFilter.value)
                && (!selectionDecisionFilter.value || decision === selectionDecisionFilter.value);
        })
        .sort((first, second) => (first.selection_result?.rank ?? Number.MAX_SAFE_INTEGER) - (second.selection_result?.rank ?? Number.MAX_SAFE_INTEGER));
});
const hasSelectionFilters = computed(() => Boolean(selectionSearch.value || selectionTrackFilter.value || selectionDecisionFilter.value));
const selectionDecisionLabel = (value?: string | null) => value ? (workflowStatusLabels[value] ?? value) : 'Belum diputuskan';
const selectionDecisionClass = (value?: string | null) => value ? `selection-decision-${value}` : 'selection-decision-pending';
function resetSelectionFilters() {
    selectionSearch.value = '';
    selectionTrackFilter.value = '';
    selectionDecisionFilter.value = '';
}
const announcementSearch = ref('');
const announcementTrackFilter = ref('');
const announcementDecisionFilter = ref('');
const announcementSummary = computed(() => ({
    accepted: props.applicants.filter((applicant) => applicant.selection_result?.decision === 'accepted').length,
    waitlisted: props.applicants.filter((applicant) => applicant.selection_result?.decision === 'waitlisted').length,
    rejected: props.applicants.filter((applicant) => applicant.selection_result?.decision === 'rejected').length,
    pending: props.applicants.filter((applicant) => !applicant.selection_result?.decision).length,
}));
const filteredAnnouncementApplicants = computed(() => {
    const search = announcementSearch.value.trim().toLowerCase();

    return props.applicants
        .filter((applicant) => {
            const searchable = [applicant.registration_no, applicant.name, applicant.nisn, applicant.previous_school]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();
            const decision = applicant.selection_result?.decision ?? 'pending';

            return (!search || searchable.includes(search))
                && (!announcementTrackFilter.value || String(applicant.track?.id ?? '') === announcementTrackFilter.value)
                && (!announcementDecisionFilter.value || decision === announcementDecisionFilter.value);
        })
        .sort((first, second) => (first.selection_result?.rank ?? Number.MAX_SAFE_INTEGER) - (second.selection_result?.rank ?? Number.MAX_SAFE_INTEGER));
});
const hasAnnouncementFilters = computed(() => Boolean(announcementSearch.value || announcementTrackFilter.value || announcementDecisionFilter.value));
function resetAnnouncementFilters() {
    announcementSearch.value = '';
    announcementTrackFilter.value = '';
    announcementDecisionFilter.value = '';
}
const reRegistrationSearch = ref('');
const reRegistrationStatusFilter = ref('');
const reRegistrationApplicants = computed(() => props.applicants.filter((applicant) => applicant.status === 'accepted' || applicant.status === 're_registered'));
const reRegistrationStatusKey = (applicant: Applicant) => applicant.status === 're_registered' || Boolean(applicant.re_registration?.student_id) || applicant.re_registration?.status === 'confirmed' ? 'completed' : 'pending';
const reRegistrationStatusLabel = (applicant: Applicant) => reRegistrationStatusKey(applicant) === 'completed' ? 'Selesai' : 'Menunggu konfirmasi';
const reRegistrationSummary = computed(() => ({
    total: reRegistrationApplicants.value.length,
    completed: reRegistrationApplicants.value.filter((applicant) => reRegistrationStatusKey(applicant) === 'completed').length,
    pending: reRegistrationApplicants.value.filter((applicant) => reRegistrationStatusKey(applicant) === 'pending').length,
}));
const filteredReRegistrationApplicants = computed(() => {
    const search = reRegistrationSearch.value.trim().toLowerCase();

    return reRegistrationApplicants.value.filter((applicant) => {
        const searchable = [applicant.registration_no, applicant.name, applicant.nisn, applicant.parent_name, applicant.previous_school]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return (!search || searchable.includes(search))
            && (!reRegistrationStatusFilter.value || reRegistrationStatusKey(applicant) === reRegistrationStatusFilter.value);
    });
});
const hasReRegistrationFilters = computed(() => Boolean(reRegistrationSearch.value || reRegistrationStatusFilter.value));
function resetReRegistrationFilters() {
    reRegistrationSearch.value = '';
    reRegistrationStatusFilter.value = '';
}
const formatDate = (value?: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(`${value.slice(0, 10)}T00:00:00`))
    : null;
const formatDateRange = (start?: string | null, end?: string | null) => {
    const from = formatDate(start);
    const to = formatDate(end);
    if (from && to) return `${from} – ${to}`;
    return from ?? to ?? 'Tanggal belum diatur';
};
const memberInitials = (name: string) => name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
function periodHref(path: string) {
    return props.selected_period_id ? `${path}?period_id=${props.selected_period_id}` : path;
}
function periodSectionPath() {
    const sections: Record<string, string> = {
        settings: 'pengaturan', periods: 'periode', tracks: 'jalur', committee: 'panitia', applicants: 'pendaftar',
        documents: 'berkas', selection: 'seleksi', announcement: 'pengumuman',
        're-registration': 'daftar-ulang', reports: 'laporan',
    };

    return sections[props.section] ? `/spmb/${sections[props.section]}` : '/spmb';
}
function changePeriod(event: Event) {
    const periodId = (event.target as HTMLSelectElement).value;
    router.get(periodSectionPath(), periodId ? { period_id: periodId } : {}, { preserveState: true, preserveScroll: true });
}

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
const editingApplicant = ref<Applicant | null>(null);
const editApplicantForm = useForm({
    period_id: '',
    track_id: '',
    name: '',
    nisn: '',
    gender: 'L' as 'L' | 'P',
    birth_date: '',
    parent_name: '',
    parent_phone: '',
    previous_school: '',
    status: 'registered',
    notes: '',
});
const editApplicantTracks = computed(() => props.periods.find((period) => String(period.id) === editApplicantForm.period_id)?.tracks ?? []);
function changeEditApplicantPeriod(event: Event) {
    editApplicantForm.period_id = (event.target as HTMLSelectElement).value;
    editApplicantForm.track_id = '';
}
function openEditApplicantModal(applicant: Applicant) {
    editingApplicant.value = applicant;
    editApplicantForm.period_id = applicant.period_id ? String(applicant.period_id) : (props.selected_period_id ? String(props.selected_period_id) : '');
    editApplicantForm.track_id = applicant.track_id ? String(applicant.track_id) : (applicant.track?.id ? String(applicant.track.id) : '');
    editApplicantForm.name = applicant.name;
    editApplicantForm.nisn = applicant.nisn ?? '';
    editApplicantForm.gender = applicant.gender;
    editApplicantForm.birth_date = applicant.birth_date ? applicant.birth_date.slice(0, 10) : '';
    editApplicantForm.parent_name = applicant.parent_name ?? '';
    editApplicantForm.parent_phone = applicant.parent_phone ?? '';
    editApplicantForm.previous_school = applicant.previous_school ?? '';
    editApplicantForm.status = applicant.status;
    editApplicantForm.notes = applicant.notes ?? '';
}
function closeEditApplicantModal() {
    editingApplicant.value = null;
}
function submitEditApplicant() {
    if (!editingApplicant.value) return;
    editApplicantForm.put(`/spmb/pendaftar/${editingApplicant.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingApplicant.value = null;
        },
    });
}
const canDeleteApplicant = (applicant: Applicant) => applicant.status !== 're_registered' && applicant.re_registration?.status !== 'confirmed';
function deleteApplicant(applicant: Applicant) {
    if (!canDeleteApplicant(applicant)) return;
    if (window.confirm(`Hapus data pendaftar ${applicant.name} (${applicant.registration_no}) beserta berkasnya?`)) {
        router.delete(`/spmb/pendaftar/${applicant.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedApplicantId.value === applicant.id) {
                    selectedApplicantId.value = null;
                }
            },
        });
    }
}
const committeeForm = useForm({ user_id: '' });
const documentForms: Record<number, ReturnType<typeof useForm>> = {};
const scoreForms: Record<string, ReturnType<typeof useForm>> = {};
const decisionForms: Record<number, ReturnType<typeof useForm>> = {};
const reRegistrationForms: Record<number, ReturnType<typeof useForm>> = {};

function documentForm(applicant: Applicant) { return documentForms[applicant.id] ??= useForm({ requirement_id: '', file: null as File | null }); }
function scoreForm(applicant: Applicant, criterion: Criterion) {
    const key = `${applicant.id}-${criterion.id}`;
    const existing = applicant.scores?.find((item) => Number(item.criterion_id) === Number(criterion.id));
    return scoreForms[key] ??= useForm({ criterion_id: String(criterion.id), score: existing ? String(existing.score) : '', notes: existing?.notes ?? '' });
}
function decisionForm(applicant: Applicant) { return decisionForms[applicant.id] ??= useForm({ decision: applicant.selection_result?.decision ?? 'accepted', override_reason: '' }); }
function reRegistrationForm(applicant: Applicant) { return reRegistrationForms[applicant.id] ??= useForm({ nis: '' }); }
function suggestNis(applicant: Applicant) {
    const yearPrefix = (selectedPeriod.value?.academic_year ?? String(new Date().getFullYear())).slice(0, 4).replace(/\D/g, '') || String(new Date().getFullYear());
    const rankOrId = applicant.selection_result?.rank ?? applicant.id;
    reRegistrationForm(applicant).nis = `${yearPrefix}${String(rankOrId).padStart(4, '0')}`;
}
function applySuggestedDecision(applicant: Applicant) {
    const quota = applicant.track?.quota ?? 0;
    const rank = applicant.selection_result?.rank ?? null;
    const form = decisionForm(applicant);
    if (rank !== null && quota > 0) {
        form.decision = rank <= quota ? 'accepted' : 'waitlisted';
        if (!form.override_reason) {
            form.override_reason = rank <= quota ? `Masuk kuota jalur (${rank}/${quota}).` : `Melebihi kuota jalur (${rank}/${quota}).`;
        }
    }
}
function addPeriod() { periodForm.post('/spmb/periode', { forceFormData: true, preserveScroll: true, onSuccess: () => periodForm.reset() }); }
function addTrack() { trackForm.post('/spmb/jalur', { preserveScroll: true, onSuccess: () => trackForm.reset('code', 'name') }); }
function addRequirement() { requirementForm.post('/spmb/persyaratan', { preserveScroll: true, onSuccess: () => requirementForm.reset('code', 'label') }); }
function addCriterion() { criterionForm.post('/spmb/kriteria', { preserveScroll: true, onSuccess: () => criterionForm.reset('code', 'label') }); }
function addApplicant() { applicantForm.post('/spmb/pendaftar', { preserveScroll: true, onSuccess: () => { applicantForm.reset('name', 'nisn', 'birth_date', 'parent_name', 'parent_phone', 'previous_school', 'notes'); showApplicantForm.value = false; } }); }
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
    <Head :title="workspaceMode === 'overview' ? 'Dashboard & Laporan SPMB' : sectionTitle" />
    <AppLayout :title="workspaceMode === 'overview' ? 'Dashboard & Laporan SPMB' : sectionTitle">
        <div class="view space-y-6 spmb-page">
            <!-- 1. WORKSPACE: OVERVIEW & REPORTS -->
            <section v-if="workspaceMode === 'overview'" class="spmb-overview">
                <header class="spmb-overview-heading">
                    <div>
                        <p class="spmb-kicker">Sistem Penerimaan Murid Baru</p>
                        <h1>Dashboard & Laporan SPMB</h1>
                        <p>Pantau posisi penerimaan, kuota per jalur, dan rekapitulasi laporan untuk periode yang dipilih.</p>
                    </div>
                    <div class="overview-heading-actions">
                        <Link :href="periodHref('/spmb/pendaftar')" class="secondary-button">Meja Pendaftar</Link>
                        <Link :href="periodHref('/spmb/pengaturan')" class="overview-period-link">Pengaturan SPMB <span aria-hidden="true">↗</span></Link>
                    </div>
                </header>

                <div class="spmb-top-bar">
                    <section class="overview-period-picker" aria-label="Pilih periode untuk ditinjau">
                        <label for="overview-period">Periode aktif</label>
                        <select id="overview-period" :value="selected_period_id ?? ''" @change="changePeriod">
                            <option value="">Pilih periode</option>
                            <option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }} · {{ period.academic_year }} · {{ periodStatusLabels[period.status] ?? period.status }}</option>
                        </select>
                    </section>
                    <nav class="spmb-workspace-tabs" aria-label="Tampilan Dashboard SPMB">
                        <button type="button" class="spmb-tab-btn" :class="{ 'is-active': activeOverviewTab === 'summary' }" @click="activeOverviewTab = 'summary'">Ringkasan & Tindak Lanjut</button>
                        <button type="button" class="spmb-tab-btn" :class="{ 'is-active': activeOverviewTab === 'reports' }" @click="activeOverviewTab = 'reports'">Rekapitulasi & Laporan</button>
                    </nav>
                </div>

                <template v-if="selectedPeriod">
                    <section class="overview-period-banner">
                        <div>
                            <span class="period-overline">{{ selectedPeriod.academic_year }}<template v-if="selectedPeriod.level"> · {{ selectedPeriod.level }}</template></span>
                            <h2>{{ selectedPeriod.name }}</h2>
                        </div>
                        <span class="period-phase-pill" :class="`phase-${selectedPeriod.status}`"><i aria-hidden="true" />{{ periodStatusLabel }}</span>
                    </section>

                    <section class="overview-metrics" aria-label="Ringkasan periode terpilih">
                        <Link :href="periodHref('/spmb/pendaftar')" class="overview-metric metric-total"><span>Total pendaftar</span><strong>{{ overviewCounts.applicants }}</strong><small>Semua pendaftar pada periode ini</small></Link>
                        <Link :href="periodHref('/spmb/seleksi')" class="overview-metric is-accepted"><span>Diterima</span><strong>{{ overviewCounts.accepted }}</strong><small>Termasuk yang sudah daftar ulang</small></Link>
                        <Link :href="periodHref('/spmb/seleksi')" class="overview-metric is-waitlisted"><span>Cadangan</span><strong>{{ overviewCounts.waitlisted }}</strong><small>Menunggu perubahan kuota</small></Link>
                        <Link :href="periodHref('/spmb/daftar-ulang')" class="overview-metric is-completed"><span>Daftar ulang selesai</span><strong>{{ overviewCounts.reRegistered }}</strong><small>Sudah tercatat sebagai siswa</small></Link>
                    </section>

                    <template v-if="activeOverviewTab === 'summary'">
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
                                <header class="overview-panel-heading"><div><h3>Antrean tindak lanjut</h3><p>Klik untuk langsung memproses</p></div></header>
                                <Link :href="periodHref('/spmb/berkas')" class="attention-row"><span class="attention-marker marker-document" /><span><strong>Verifikasi berkas</strong><small>Pendaftar menunggu pemeriksaan dokumen</small></span><b>{{ overviewCounts.documents }}</b></Link>
                                <Link :href="periodHref('/spmb/seleksi')" class="attention-row"><span class="attention-marker marker-selection" /><span><strong>Keputusan seleksi</strong><small>Pendaftar menunggu penilaian / keputusan akhir</small></span><b>{{ overviewCounts.decisions }}</b></Link>
                                <Link :href="periodHref('/spmb/daftar-ulang')" class="attention-row"><span class="attention-marker marker-reregistration" /><span><strong>Daftar ulang</strong><small>Pendaftar diterima belum menyelesaikan daftar ulang</small></span><b>{{ overviewCounts.reRegistration }}</b></Link>
                            </section>
                        </div>

                        <section class="overview-panel track-overview-panel">
                            <header class="overview-panel-heading"><div><h3>Peserta per jalur</h3><p>Pendaftar dan keterisian kuota penerimaan pada periode terpilih</p></div><Link :href="periodHref('/spmb/jalur')">Atur jalur & kuota</Link></header>
                            <div v-if="trackOverview.length" class="track-overview-list">
                                <article v-for="track in trackOverview" :key="track.id" class="track-overview-row">
                                    <div class="track-overview-name"><strong>{{ track.name }}</strong><span>{{ track.applicants }} pendaftar</span></div>
                                    <div class="quota-progress" role="progressbar" :aria-label="`Kuota terisi jalur ${track.name}`" :aria-valuenow="track.accepted" :aria-valuemin="0" :aria-valuemax="track.quota"><i :style="{ width: `${track.fillRate}%` }" /></div>
                                    <span class="track-overview-quota">{{ track.accepted }} / {{ track.quota }} kuota</span>
                                </article>
                            </div>
                            <p v-else class="overview-empty-inline">Belum ada jalur untuk periode ini. Atur jalur dan kuota melalui halaman Pengaturan SPMB.</p>
                        </section>
                    </template>

                    <div v-if="activeOverviewTab === 'reports'" class="report-grid">
                        <article class="panel admissions-panel report-panel">
                            <div class="table-title"><div><h2>Rekapitulasi per jalur</h2><p>Jumlah pendaftar dan penggunaan kuota periode yang dipilih.</p></div></div>
                            <div class="report-table-wrap table-scroll" tabindex="0" role="region" aria-label="Rekapitulasi pendaftar per jalur">
                                <table class="report-table">
                                    <thead><tr><th scope="col">Jalur</th><th scope="col">Pendaftar</th><th scope="col">Diterima</th><th scope="col">Kuota</th></tr></thead>
                                    <tbody>
                                        <tr v-for="track in trackOverview" :key="track.id"><th scope="row">{{ track.name }}</th><td>{{ track.applicants }}</td><td>{{ track.accepted }}</td><td>{{ track.accepted }} / {{ track.quota }}</td></tr>
                                        <tr v-if="!trackOverview.length"><td colspan="4" class="report-empty">Belum ada jalur pada periode ini.</td></tr>
                                    </tbody>
                                    <tfoot><tr><th scope="row">Total</th><td>{{ applicants.length }}</td><td>{{ summary.accepted + summary.re_registered }}</td><td>{{ totalQuota }}</td></tr></tfoot>
                                </table>
                            </div>
                        </article>
                        <article class="panel admissions-panel report-panel">
                            <div class="table-title"><div><h2>Rekapitulasi status alur</h2><p>Distribusi posisi setiap pendaftar dalam alur penerimaan.</p></div></div>
                            <div class="status-report-list"><div v-for="status in statusReport" :key="status.value"><span>{{ status.label }}</span><strong>{{ status.count }}</strong></div></div>
                        </article>
                    </div>
                </template>
                <section v-else class="overview-empty-state">
                    <span class="empty-state-mark" aria-hidden="true">＋</span>
                    <h2>Belum ada periode untuk ditinjau</h2>
                    <p>Buat periode SPMB terlebih dahulu melalui Pengaturan SPMB untuk membuka pendaftaran.</p>
                    <Link href="/spmb/pengaturan" class="overview-period-link">Buka Pengaturan SPMB <span aria-hidden="true">↗</span></Link>
                </section>
            </section>

            <!-- HEADER FOR OTHER WORKSPACES -->
            <template v-else>
                <section class="admissions-page-header">
                    <div class="admissions-header-top">
                        <div>
                            <p class="eyebrow">Sistem Penerimaan Murid Baru</p>
                            <h1>{{ sectionTitle }}</h1>
                            <p v-if="workspaceMode === 'intake'">Meja kerja pendaftar terpadu: tambah calon siswa, periksa kelengkapan berkas, dan perbarui status verifikasi dari satu layar.</p>
                            <p v-else-if="workspaceMode === 'decision'">Kelola penilaian kriteria seleksi, hitung peringkat otomatis, tetapkan kelulusan, hingga konfirmasi NIS daftar ulang.</p>
                            <p v-else>Konfigurasi siklus periode, jalur & kuota, syarat berkas, bobot kriteria, serta penugasan tim panitia SPMB.</p>
                        </div>
                        <section class="period-context-inline">
                            <label>Periode aktif
                                <select :value="selected_period_id ?? ''" @change="changePeriod">
                                    <option value="">Belum ada periode</option>
                                    <option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }} · {{ periodStatusLabels[period.status] ?? period.status }}</option>
                                </select>
                            </label>
                        </section>
                    </div>
                    <div class="grade-summary admissions-summary">
                        <div><span>Periode</span><strong>{{ summary.periods }}</strong></div>
                        <div><span>Pendaftar</span><strong>{{ summary.applicants }}</strong></div>
                        <div><span>Diterima</span><strong>{{ summary.accepted }}</strong></div>
                        <div><span>Daftar ulang</span><strong>{{ summary.re_registered }}</strong></div>
                    </div>
                </section>
            </template>

            <!-- 2. WORKSPACE: INTAKE (PENDAFTAR & VERIFIKASI BERKAS + SLIDE-OVER DRAWER) -->
            <template v-if="workspaceMode === 'intake'">
                <div class="spmb-subnav-bar">
                    <nav class="spmb-workspace-tabs" aria-label="Mode tampilan pendaftar dan verifikasi">
                        <button type="button" class="spmb-tab-btn" :class="{ 'is-active': activeIntakeTab === 'all' }" @click="activeIntakeTab = 'all'">
                            Daftar Pendaftar & Meja Kerja ({{ applicants.length }})
                        </button>
                        <button type="button" class="spmb-tab-btn" :class="{ 'is-active': activeIntakeTab === 'documents' }" @click="activeIntakeTab = 'documents'">
                            Antrean Verifikasi Berkas ({{ documentSummary.pending + documentSummary.needsRevision }} perlu cek)
                        </button>
                    </nav>
                    <button v-if="can_manage_applicants" class="primary-button" type="button" :aria-expanded="showApplicantForm" aria-controls="applicant-create-form" @click="showApplicantForm = !showApplicantForm">
                        {{ showApplicantForm ? 'Tutup form pendaftar' : '+ Tambah pendaftar' }}
                    </button>
                </div>

                <form v-if="can_manage_applicants && showApplicantForm" id="applicant-create-form" class="panel admissions-panel admissions-form applicant-create-form" @submit.prevent="addApplicant">
                    <div class="form-section-intro">
                        <span class="section-overline">Data baru</span>
                        <h3>Tambah pendaftar</h3>
                        <p>Lengkapi identitas dasar calon siswa, lalu simpan untuk masuk ke alur verifikasi berkas.</p>
                    </div>
                    <label>Periode<select v-model="applicantForm.period_id" required @change="changeApplicantPeriod"><option value="">Pilih periode</option><option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }}</option></select></label>
                    <label>Jalur<select v-model="applicantForm.track_id" required><option value="">Pilih jalur</option><option v-for="track in applicantTracks.length ? applicantTracks : tracks" :key="track.id" :value="track.id">{{ track.name }}</option></select></label>
                    <label>Nama calon siswa<input v-model="applicantForm.name" required /></label>
                    <label>NISN<input v-model="applicantForm.nisn" inputmode="numeric" /></label>
                    <label>Jenis kelamin<select v-model="applicantForm.gender"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></label>
                    <label>Tanggal lahir<input v-model="applicantForm.birth_date" type="date" /></label>
                    <label>Nama orang tua/wali<input v-model="applicantForm.parent_name" /></label>
                    <label>Kontak orang tua<input v-model="applicantForm.parent_phone" /></label>
                    <label>Asal sekolah<input v-model="applicantForm.previous_school" /></label>
                    <label class="admissions-form-wide">Catatan<textarea v-model="applicantForm.notes" rows="2" /></label>
                    <div class="admissions-form-actions admissions-form-wide"><button class="primary-button" type="submit">Simpan pendaftar</button></div>
                </form>

                <!-- SUB-TAB 2A: DAFTAR PENDAFTAR TERPADU -->
                <section v-if="activeIntakeTab === 'all'" class="panel admissions-panel applicants-panel">
                    <div class="applicant-insights" aria-label="Ringkasan pendaftar">
                        <div class="applicant-insight is-total"><span>Total pendaftar</span><strong>{{ applicants.length }}</strong><small>Pada periode terpilih</small></div>
                        <div class="applicant-insight is-attention"><span>Berkas perlu cek</span><strong>{{ documentSummary.pending + documentSummary.needsRevision }}</strong><small>{{ documentSummary.needsRevision }} perlu perbaikan</small></div>
                        <div v-for="status in applicantStatusSummary.slice(1)" :key="status.value" class="applicant-insight" :class="`is-${status.value}`"><span>{{ status.label }}</span><strong>{{ applicants.filter((applicant) => applicant.status === status.value).length }}</strong><small>{{ status.hint }}</small></div>
                    </div>

                    <div class="applicant-list-panel">
                        <div class="applicant-list-heading">
                            <div><h3>Meja kerja pendaftar</h3><p>{{ filteredApplicants.length }} dari {{ applicants.length }} data ditampilkan · Klik "Detail" untuk memeriksa berkas & status</p></div>
                            <span v-if="hasApplicantFilters" class="filter-result-label">Filter aktif</span>
                        </div>
                        <div class="applicant-filters">
                            <label class="applicant-search-field"><span>Cari pendaftar</span><input v-model="applicantSearch" type="search" placeholder="Nama, nomor pendaftaran, NISN, asal sekolah..." /></label>
                            <label><span>Status alur</span><select v-model="applicantStatusFilter"><option value="">Semua status</option><option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option></select></label>
                            <label><span>Jalur</span><select v-model="applicantTrackFilter"><option value="">Semua jalur</option><option v-for="track in tracks" :key="track.id" :value="track.id">{{ track.name }}</option></select></label>
                            <button v-if="hasApplicantFilters" class="filter-reset-button" type="button" @click="resetApplicantFilters">Reset filter</button>
                        </div>

                        <div v-if="filteredApplicants.length" class="intake-table-wrap table-scroll" tabindex="0" role="region" aria-label="Tabel daftar pendaftar">
                            <table class="intake-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="col-no">No</th>
                                        <th scope="col">Nama Pendaftar</th>
                                        <th scope="col">Tanggal Daftar</th>
                                        <th scope="col">Jalur & Status</th>
                                        <th scope="col" class="col-actions">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(applicant, index) in filteredApplicants" :key="applicant.id">
                                        <td class="col-no">{{ index + 1 }}</td>
                                        <td>
                                            <div class="intake-applicant-cell">
                                                <strong>{{ applicant.name }}</strong>
                                                <small>{{ applicant.registration_no }}<template v-if="applicant.nisn"> · NISN {{ applicant.nisn }}</template><template v-if="applicant.previous_school"> · {{ applicant.previous_school }}</template></small>
                                            </div>
                                        </td>
                                        <td class="col-date">{{ formatDate(applicant.created_at) || '—' }}</td>
                                        <td>
                                            <div class="applicant-badge-cluster">
                                                <span class="meta-chip">{{ applicant.track?.name || 'Belum pilih jalur' }}</span>
                                                <span class="status-pill" :class="applicantDocumentSummary(applicant).className">{{ applicantDocumentSummary(applicant).label }} ({{ applicant.documents.length }})</span>
                                                <span class="status-pill" :class="`status-${applicant.status}`">{{ applicantStatusLabel(applicant.status) }}</span>
                                            </div>
                                        </td>
                                        <td class="col-actions">
                                            <div class="intake-action-group">
                                                <button type="button" class="secondary-button btn-compact" @click="openApplicantDrawer(applicant)">
                                                    Detail
                                                </button>
                                                <template v-if="can_manage_applicants">
                                                    <button
                                                        type="button"
                                                        class="icon-action-btn is-edit"
                                                        :title="`Edit data ${applicant.name}`"
                                                        :aria-label="`Edit pendaftar ${applicant.name}`"
                                                        @click="openEditApplicantModal(applicant)"
                                                    >
                                                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.586 3.586a2 2 0 112.828 2.828l-8.793 8.793-3.536.707.707-3.536 8.794-8.792z" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="icon-action-btn is-delete"
                                                        :disabled="!canDeleteApplicant(applicant)"
                                                        :title="canDeleteApplicant(applicant) ? `Hapus ${applicant.name}` : 'Pendaftar sudah daftar ulang tidak dapat dihapus'"
                                                        :aria-label="`Hapus pendaftar ${applicant.name}`"
                                                        @click="deleteApplicant(applicant)"
                                                    >
                                                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h8m-6 0V4.5A1.5 1.5 0 019.5 3h1A1.5 1.5 0 0112 4.5V6m2.5 0l-.58 9.28A1.5 1.5 0 0112.423 16.7H7.577a1.5 1.5 0 01-1.497-1.42L5.5 6h9zM8.5 9v4.5m3-4.5v4.5" />
                                                        </svg>
                                                    </button>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="empty-state">{{ hasApplicantFilters ? 'Tidak ada pendaftar yang cocok dengan filter ini.' : 'Belum ada pendaftar pada periode ini.' }}</p>
                    </div>
                </section>

                <!-- SUB-TAB 2B: ANTREAN VERIFIKASI BERKAS -->
                <section v-if="activeIntakeTab === 'documents'" class="panel admissions-panel documents-panel">
                    <div class="documents-page-heading"><div class="table-title"><div><span class="section-overline">Pemeriksaan dokumen</span><h2>Verifikasi berkas</h2><p>Periksa kelengkapan dokumen setiap pendaftar secara berurutan melalui panel detail.</p></div></div></div>
                    <div class="document-insights" aria-label="Ringkasan verifikasi">
                        <div class="document-insight is-total"><span>Pendaftar</span><strong>{{ documentSummary.applicants }}</strong><small>Dalam periode ini</small></div>
                        <div class="document-insight"><span>Total berkas</span><strong>{{ documentSummary.total }}</strong><small>Dokumen yang diunggah</small></div>
                        <div class="document-insight is-valid"><span>Valid</span><strong>{{ documentSummary.valid }}</strong><small>Sudah terverifikasi</small></div>
                        <div class="document-insight is-attention"><span>Perlu perhatian</span><strong>{{ documentSummary.pending + documentSummary.needsRevision }}</strong><small>{{ documentSummary.needsRevision }} perlu perbaikan</small></div>
                    </div>
                    <div class="document-list-panel">
                        <div class="document-list-heading"><div><h3>Daftar berkas pendaftar</h3><p>{{ filteredDocumentApplicants.length }} dari {{ applicants.length }} pendaftar ditampilkan · Klik "Detail" untuk memeriksa atau mengunggah berkas</p></div><span v-if="hasDocumentFilters" class="filter-result-label">Filter aktif</span></div>
                        <div class="document-filters">
                            <label class="document-search-field"><span>Cari pendaftar</span><input v-model="documentSearch" type="search" placeholder="Nama atau nomor pendaftaran..." /></label>
                            <label><span>Status berkas</span><select v-model="documentStatusFilter"><option value="">Semua status</option><option value="pending">Menunggu verifikasi</option><option value="valid">Valid</option><option value="needs_revision">Perlu perbaikan</option></select></label>
                            <button v-if="hasDocumentFilters" class="filter-reset-button" type="button" @click="resetDocumentFilters">Reset filter</button>
                        </div>

                        <div v-if="filteredDocumentApplicants.length" class="intake-table-wrap table-scroll" tabindex="0" role="region" aria-label="Tabel verifikasi berkas pendaftar">
                            <table class="intake-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="col-no">No</th>
                                        <th scope="col">Nama Pendaftar</th>
                                        <th scope="col">Tanggal Daftar</th>
                                        <th scope="col">Ringkasan Berkas</th>
                                        <th scope="col" class="col-actions">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(applicant, index) in filteredDocumentApplicants" :key="applicant.id">
                                        <td class="col-no">{{ index + 1 }}</td>
                                        <td>
                                            <div class="intake-applicant-cell">
                                                <strong>{{ applicant.name }}</strong>
                                                <small>{{ applicant.registration_no }} · {{ applicant.track?.name || 'Belum memilih jalur' }}</small>
                                            </div>
                                        </td>
                                        <td class="col-date">{{ formatDate(applicant.created_at) || '—' }}</td>
                                        <td>
                                            <div class="applicant-badge-cluster">
                                                <span class="status-pill" :class="applicantDocumentSummary(applicant).className">{{ applicantDocumentSummary(applicant).label }}</span>
                                                <span class="meta-chip">{{ applicant.documents.filter((d) => d.status === 'valid').length }}/{{ applicant.documents.length }} berkas valid</span>
                                            </div>
                                        </td>
                                        <td class="col-actions">
                                            <div class="intake-action-group">
                                                <button type="button" class="secondary-button btn-compact" @click="openApplicantDrawer(applicant)">
                                                    Detail
                                                </button>
                                                <template v-if="can_manage_applicants">
                                                    <button
                                                        type="button"
                                                        class="icon-action-btn is-edit"
                                                        :title="`Edit data ${applicant.name}`"
                                                        :aria-label="`Edit pendaftar ${applicant.name}`"
                                                        @click="openEditApplicantModal(applicant)"
                                                    >
                                                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.586 3.586a2 2 0 112.828 2.828l-8.793 8.793-3.536.707.707-3.536 8.794-8.792z" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="icon-action-btn is-delete"
                                                        :disabled="!canDeleteApplicant(applicant)"
                                                        :title="canDeleteApplicant(applicant) ? `Hapus ${applicant.name}` : 'Pendaftar sudah daftar ulang tidak dapat dihapus'"
                                                        :aria-label="`Hapus pendaftar ${applicant.name}`"
                                                        @click="deleteApplicant(applicant)"
                                                    >
                                                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h8m-6 0V4.5A1.5 1.5 0 019.5 3h1A1.5 1.5 0 0112 4.5V6m2.5 0l-.58 9.28A1.5 1.5 0 0112.423 16.7H7.577a1.5 1.5 0 01-1.497-1.42L5.5 6h9zM8.5 9v4.5m3-4.5v4.5" />
                                                        </svg>
                                                    </button>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="empty-state">{{ hasDocumentFilters ? 'Tidak ada pendaftar yang cocok dengan filter ini.' : 'Belum ada pendaftar untuk diverifikasi.' }}</p>
                    </div>
                </section>
            </template>

            <!-- 3. WORKSPACE: DECISION (SELEKSI & RANKING + PENGUMUMAN + DAFTAR ULANG) -->
            <template v-if="workspaceMode === 'decision'">
                <div class="spmb-subnav-bar">
                    <nav class="spmb-workspace-tabs" aria-label="Tahapan Seleksi dan Daftar Ulang">
                        <button type="button" class="spmb-tab-btn" :class="{ 'is-active': activeDecisionTab === 'selection' }" @click="activeDecisionTab = 'selection'">
                            1. Penilaian & Ranking ({{ selectionSummary.evaluated }}/{{ selectionSummary.applicants }})
                        </button>
                        <button type="button" class="spmb-tab-btn" :class="{ 'is-active': activeDecisionTab === 'announcement' }" @click="activeDecisionTab = 'announcement'">
                            2. Pengumuman Kelulusan ({{ announcementSummary.accepted }} diterima)
                        </button>
                        <button type="button" class="spmb-tab-btn" :class="{ 'is-active': activeDecisionTab === 're-registration' }" @click="activeDecisionTab = 're-registration'">
                            3. Daftar Ulang Siswa ({{ reRegistrationSummary.completed }}/{{ reRegistrationSummary.total }})
                        </button>
                    </nav>
                    <button v-if="can_manage_committee && activeDecisionTab === 'selection'" class="primary-button" type="button" @click="rankPeriod">
                        Hitung ranking
                    </button>
                </div>

                <!-- SUB-TAB 3A: SELEKSI & RANKING -->
                <section v-if="activeDecisionTab === 'selection'" class="panel admissions-panel selection-panel">
                    <div class="selection-page-heading">
                        <div class="table-title"><div><span class="section-overline">Penilaian kandidat</span><h2>Seleksi & ranking</h2><p>Nilai setiap kriteria, hitung peringkat otomatis, lalu tetapkan keputusan akhir dengan jejak audit yang jelas.</p></div></div>
                    </div>
                    <div class="selection-insights" aria-label="Ringkasan seleksi">
                        <div class="selection-insight is-total"><span>Pendaftar</span><strong>{{ selectionSummary.applicants }}</strong><small>Dalam periode ini</small></div>
                        <div class="selection-insight"><span>Sudah dinilai</span><strong>{{ selectionSummary.evaluated }}</strong><small>Memiliki total skor</small></div>
                        <div class="selection-insight is-ranked"><span>Sudah diranking</span><strong>{{ selectionSummary.ranked }}</strong><small>Memiliki peringkat</small></div>
                        <div class="selection-insight is-decided"><span>Keputusan</span><strong>{{ selectionSummary.decided }}</strong><small>Sudah difinalisasi</small></div>
                    </div>
                    <div class="selection-list-panel">
                        <div class="selection-list-heading"><div><h3>Daftar kandidat</h3><p>{{ filteredSelectionApplicants.length }} dari {{ applicants.length }} pendaftar ditampilkan · Urut berdasarkan peringkat</p></div><span v-if="hasSelectionFilters" class="filter-result-label">Filter aktif</span></div>
                        <div class="selection-filters">
                            <label class="selection-search-field"><span>Cari kandidat</span><input v-model="selectionSearch" type="search" placeholder="Nama, nomor pendaftaran, NISN..." /></label>
                            <label><span>Jalur</span><select v-model="selectionTrackFilter"><option value="">Semua jalur</option><option v-for="track in tracks" :key="track.id" :value="track.id">{{ track.name }}</option></select></label>
                            <label><span>Keputusan</span><select v-model="selectionDecisionFilter"><option value="">Semua keputusan</option><option value="pending">Belum diputuskan</option><option value="accepted">Diterima</option><option value="waitlisted">Cadangan</option><option value="rejected">Tidak diterima</option></select></label>
                            <button v-if="hasSelectionFilters" class="filter-reset-button" type="button" @click="resetSelectionFilters">Reset filter</button>
                        </div>
                        <div class="selection-applicants">
                            <article v-for="applicant in filteredSelectionApplicants" :key="applicant.id" class="selection-applicant">
                                <div class="selection-applicant-heading">
                                    <div>
                                        <span class="admissions-registration">{{ applicant.registration_no }}</span>
                                        <h3>{{ applicant.name }}</h3>
                                        <p>{{ applicant.track?.name || 'Belum memilih jalur' }} (Kuota {{ applicant.track?.quota ?? '—' }}) · {{ applicant.previous_school || 'Asal sekolah belum diisi' }}</p>
                                    </div>
                                    <div class="applicant-badge-cluster">
                                        <button type="button" class="secondary-button btn-compact" @click="openApplicantDrawer(applicant)">Lihat Berkas</button>
                                        <div class="selection-rank"><span>Peringkat</span><strong>#{{ applicant.selection_result?.rank || '—' }}</strong></div>
                                    </div>
                                </div>
                                <div class="selection-score-summary">
                                    <span>Total skor</span>
                                    <strong>{{ Number(applicant.selection_result?.total_score || 0).toFixed(2) }}</strong>
                                    <span class="status-pill" :class="applicantDocumentSummary(applicant).className">{{ applicantDocumentSummary(applicant).label }}</span>
                                    <span class="status-pill" :class="selectionDecisionClass(applicant.selection_result?.decision)">{{ selectionDecisionLabel(applicant.selection_result?.decision) }}</span>
                                </div>
                                <div v-if="can_manage_applicants && applicant.track?.criteria?.length" class="selection-criteria">
                                    <div v-for="criterion in applicant.track.criteria" :key="criterion.id" class="selection-criterion">
                                        <div><strong>{{ criterion.label }}</strong><small>Bobot {{ criterion.weight }} · Maks. {{ criterion.max_score }} poin</small></div>
                                        <div class="selection-score-action">
                                            <input v-model="scoreForm(applicant, criterion).score" type="number" min="0" :max="criterion.max_score" :aria-label="`Nilai ${criterion.label} untuk ${applicant.name}`" />
                                            <button class="secondary-button" type="button" @click="saveScore(applicant, criterion)">Simpan nilai</button>
                                        </div>
                                    </div>
                                </div>
                                <p v-else-if="!applicant.track?.criteria?.length" class="selection-empty-note">Kriteria penilaian belum diatur untuk jalur ini.</p>
                                <form v-if="can_manage_applicants" class="selection-decision-form" @submit.prevent="finalizeApplicant(applicant)">
                                    <div class="selection-decision-heading">
                                        <div>
                                            <strong>Keputusan akhir</strong>
                                            <span>Tetapkan hasil setelah penilaian selesai.</span>
                                        </div>
                                        <div class="applicant-badge-cluster">
                                            <button v-if="applicant.selection_result?.rank" type="button" class="filter-reset-button" @click="applySuggestedDecision(applicant)">Isi sesuai kuota</button>
                                            <span class="selection-decision-state" :class="selectionDecisionClass(applicant.selection_result?.decision)">{{ selectionDecisionLabel(applicant.selection_result?.decision) }}</span>
                                        </div>
                                    </div>
                                    <div class="selection-decision-fields">
                                        <label>Keputusan<select v-model="decisionForm(applicant).decision"><option value="accepted">Diterima</option><option value="waitlisted">Cadangan</option><option value="rejected">Tidak diterima</option></select></label>
                                        <label class="selection-reason-field">Alasan keputusan<input v-model="decisionForm(applicant).override_reason" placeholder="Tuliskan alasan atau catatan keputusan" required /></label>
                                        <button class="primary-button" type="submit">Simpan keputusan</button>
                                    </div>
                                </form>
                            </article>
                            <p v-if="!filteredSelectionApplicants.length" class="empty-state">{{ hasSelectionFilters ? 'Tidak ada kandidat yang cocok dengan filter ini.' : 'Belum ada data seleksi.' }}</p>
                        </div>
                    </div>
                </section>

                <!-- SUB-TAB 3B: PENGUMUMAN -->
                <section v-if="activeDecisionTab === 'announcement'" class="panel admissions-panel announcement-panel">
                    <div class="announcement-page-heading"><div class="table-title"><div><span class="section-overline">Hasil penerimaan</span><h2>Pengumuman</h2><p>Lihat hasil seleksi berdasarkan peringkat dan keputusan akhir setiap pendaftar.</p></div></div><div class="announcement-date"><span>Tanggal pengumuman</span><strong>{{ selectedPeriod?.announcement_at ? formatDate(selectedPeriod.announcement_at) : 'Belum diatur' }}</strong></div></div>
                    <div class="announcement-notice"><span class="guidance-mark" aria-hidden="true">i</span><div><strong>Hasil seleksi periode ini</strong><p>Pendaftar dengan keputusan "Diterima" otomatis masuk ke tab Daftar Ulang Siswa untuk pembuatan data induk Akademik.</p></div></div>
                    <div class="announcement-insights" aria-label="Ringkasan pengumuman">
                        <div class="announcement-insight is-accepted"><span>Diterima</span><strong>{{ announcementSummary.accepted }}</strong><small>Masuk tahap daftar ulang</small></div>
                        <div class="announcement-insight is-waitlisted"><span>Cadangan</span><strong>{{ announcementSummary.waitlisted }}</strong><small>Menunggu perubahan kuota</small></div>
                        <div class="announcement-insight is-rejected"><span>Tidak diterima</span><strong>{{ announcementSummary.rejected }}</strong><small>Keputusan akhir</small></div>
                        <div class="announcement-insight is-pending"><span>Belum diputuskan</span><strong>{{ announcementSummary.pending }}</strong><small>Perlu ditinjau</small></div>
                    </div>
                    <div class="announcement-list-panel">
                        <div class="announcement-list-heading"><div><h3>Daftar hasil seleksi</h3><p>{{ filteredAnnouncementApplicants.length }} dari {{ applicants.length }} pendaftar ditampilkan · Urut berdasarkan peringkat</p></div><span v-if="hasAnnouncementFilters" class="filter-result-label">Filter aktif</span></div>
                        <div class="announcement-filters">
                            <label class="announcement-search-field"><span>Cari pendaftar</span><input v-model="announcementSearch" type="search" placeholder="Nama, nomor pendaftaran, NISN..." /></label>
                            <label><span>Jalur</span><select v-model="announcementTrackFilter"><option value="">Semua jalur</option><option v-for="track in tracks" :key="track.id" :value="track.id">{{ track.name }}</option></select></label>
                            <label><span>Hasil</span><select v-model="announcementDecisionFilter"><option value="">Semua hasil</option><option value="accepted">Diterima</option><option value="waitlisted">Cadangan</option><option value="rejected">Tidak diterima</option><option value="pending">Belum diputuskan</option></select></label>
                            <button v-if="hasAnnouncementFilters" class="filter-reset-button" type="button" @click="resetAnnouncementFilters">Reset filter</button>
                        </div>
                        <div class="announcement-applicants">
                            <article v-for="applicant in filteredAnnouncementApplicants" :key="applicant.id" class="announcement-applicant">
                                <div class="announcement-applicant-heading">
                                    <div><span class="admissions-registration">{{ applicant.registration_no }}</span><h3>{{ applicant.name }}</h3><p>{{ applicant.track?.name || 'Belum memilih jalur' }} · {{ applicant.previous_school || 'Asal sekolah belum diisi' }}</p></div>
                                    <span class="status-pill" :class="selectionDecisionClass(applicant.selection_result?.decision)">{{ selectionDecisionLabel(applicant.selection_result?.decision) }}</span>
                                </div>
                                <div class="announcement-result">
                                    <div><span>Hasil seleksi</span><strong :class="selectionDecisionClass(applicant.selection_result?.decision)">{{ selectionDecisionLabel(applicant.selection_result?.decision) }}</strong></div>
                                    <div><span>Peringkat</span><strong>#{{ applicant.selection_result?.rank || '—' }}</strong></div>
                                    <div><span>Total skor</span><strong>{{ Number(applicant.selection_result?.total_score || 0).toFixed(2) }}</strong></div>
                                </div>
                            </article>
                            <p v-if="!filteredAnnouncementApplicants.length" class="empty-state">{{ hasAnnouncementFilters ? 'Tidak ada hasil yang cocok dengan filter ini.' : 'Belum ada data pengumuman.' }}</p>
                        </div>
                    </div>
                </section>

                <!-- SUB-TAB 3C: DAFTAR ULANG -->
                <section v-if="activeDecisionTab === 're-registration'" class="panel admissions-panel re-registration-panel">
                    <div class="re-registration-page-heading"><div class="table-title"><div><span class="section-overline">Tahap akhir penerimaan</span><h2>Daftar ulang</h2><p>Konfirmasi pendaftar yang diterima menjadi data siswa dengan mencatat NIS mereka.</p></div></div><div class="re-registration-date"><span>Periode daftar ulang</span><strong>{{ selectedPeriod ? formatDateRange(selectedPeriod.re_registration_start, selectedPeriod.re_registration_end) : 'Belum diatur' }}</strong></div></div>
                    <div class="re-registration-notice"><span class="guidance-mark" aria-hidden="true">i</span><div><strong>Alur konfirmasi siswa</strong><p>Masukkan NIS (atau gunakan tombol Buat NIS Otomatis) untuk membuat hubungan antara pendaftar yang diterima dan data siswa Akademik.</p></div></div>
                    <div class="re-registration-insights" aria-label="Ringkasan daftar ulang">
                        <div class="re-registration-insight is-total"><span>Diterima</span><strong>{{ reRegistrationSummary.total }}</strong><small>Masuk tahap daftar ulang</small></div>
                        <div class="re-registration-insight is-pending"><span>Menunggu konfirmasi</span><strong>{{ reRegistrationSummary.pending }}</strong><small>Memerlukan NIS</small></div>
                        <div class="re-registration-insight is-completed"><span>Selesai</span><strong>{{ reRegistrationSummary.completed }}</strong><small>Sudah terhubung ke siswa</small></div>
                    </div>
                    <div class="re-registration-list-panel">
                        <div class="re-registration-list-heading"><div><h3>Daftar calon siswa</h3><p>{{ filteredReRegistrationApplicants.length }} dari {{ reRegistrationApplicants.length }} pendaftar ditampilkan</p></div><span v-if="hasReRegistrationFilters" class="filter-result-label">Filter aktif</span></div>
                        <div class="re-registration-filters">
                            <label class="re-registration-search-field"><span>Cari pendaftar</span><input v-model="reRegistrationSearch" type="search" placeholder="Nama, nomor pendaftaran, NISN..." /></label>
                            <label><span>Status</span><select v-model="reRegistrationStatusFilter"><option value="">Semua status</option><option value="pending">Menunggu konfirmasi</option><option value="completed">Selesai</option></select></label>
                            <button v-if="hasReRegistrationFilters" class="filter-reset-button" type="button" @click="resetReRegistrationFilters">Reset filter</button>
                        </div>
                        <div class="re-registration-applicants">
                            <article v-for="applicant in filteredReRegistrationApplicants" :key="applicant.id" class="re-registration-applicant">
                                <div class="re-registration-applicant-heading">
                                    <div><span class="admissions-registration">{{ applicant.registration_no }}</span><h3>{{ applicant.name }}</h3><p>{{ applicant.track?.name || 'Belum memilih jalur' }} · {{ applicant.previous_school || 'Asal sekolah belum diisi' }}</p></div>
                                    <span class="status-pill" :class="reRegistrationStatusKey(applicant) === 'completed' ? 're-registration-status-completed' : 're-registration-status-pending'">{{ reRegistrationStatusLabel(applicant) }}</span>
                                </div>
                                <div class="re-registration-details">
                                    <div><span>Nomor induk</span><strong>{{ applicant.re_registration?.student_id ? `Data siswa #${applicant.re_registration.student_id}` : 'Belum terhubung' }}</strong></div>
                                    <div><span>Jalur penerimaan</span><strong>{{ applicant.track?.name || '—' }}</strong></div>
                                    <div><span>Status pendaftar</span><strong>Diterima</strong></div>
                                </div>
                                <form v-if="can_manage_applicants && reRegistrationStatusKey(applicant) === 'pending'" class="re-registration-form" @submit.prevent="confirmReRegistration(applicant)">
                                    <label>Masukkan NIS siswa
                                        <div class="nis-input-group">
                                            <input v-model="reRegistrationForm(applicant).nis" inputmode="numeric" placeholder="Contoh: 20270001" required />
                                            <button type="button" class="secondary-button btn-compact" @click="suggestNis(applicant)">Saran NIS</button>
                                        </div>
                                    </label>
                                    <button class="primary-button" type="submit">Konfirmasi daftar ulang</button>
                                </form>
                                <div v-else class="re-registration-complete"><span class="guidance-mark" aria-hidden="true">✓</span><p><strong>Sudah terhubung ke data siswa</strong><span>Nomor data siswa #{{ applicant.re_registration?.student_id || '—' }}</span></p></div>
                            </article>
                            <p v-if="!filteredReRegistrationApplicants.length" class="empty-state">{{ hasReRegistrationFilters ? 'Tidak ada pendaftar yang cocok dengan filter ini.' : 'Belum ada pendaftar yang masuk tahap daftar ulang.' }}</p>
                        </div>
                    </div>
                </section>
            </template>

            <!-- 4. WORKSPACE: SETTINGS (PENGATURAN SPMB TERPADU + SETUP CHECKLIST) -->
            <template v-if="workspaceMode === 'settings'">
                <section v-if="selectedPeriod" class="panel admissions-panel setup-checklist-panel" aria-label="Kesiapan pembukaan periode">
                    <div class="setup-checklist-header">
                        <div>
                            <span class="section-overline">Checklist kesiapan periode</span>
                            <h2>{{ selectedPeriod.name }} ({{ selectedPeriod.academic_year }})</h2>
                            <p>Pastikan keempat komponen di bawah ini lengkap sebelum membuka pendaftaran periode.</p>
                        </div>
                        <div class="setup-checklist-actions">
                            <span class="status-pill">{{ periodStatusLabels[selectedPeriod.status] ?? selectedPeriod.status }}</span>
                            <button v-if="can_manage_committee && selectedPeriod.status === 'draft'" class="primary-button" type="button" @click="openPeriod(selectedPeriod)">
                                Buka pendaftaran
                            </button>
                        </div>
                    </div>
                    <div class="setup-checklist-grid">
                        <button type="button" class="setup-check-item" :class="{ 'is-done': periodReadiness.hasPeriod }" @click="activeSettingsTab = 'periods'">
                            <span class="check-icon">{{ periodReadiness.hasPeriod ? '✓' : '1' }}</span>
                            <div><strong>Periode & Jadwal</strong><small>Siklus tahun ajaran & tanggal</small></div>
                        </button>
                        <button type="button" class="setup-check-item" :class="{ 'is-done': periodReadiness.hasTracks }" @click="activeSettingsTab = 'tracks'">
                            <span class="check-icon">{{ periodReadiness.hasTracks ? '✓' : '2' }}</span>
                            <div><strong>Jalur & Kuota</strong><small>{{ tracks.length }} jalur aktif ({{ totalQuota }} kuota)</small></div>
                        </button>
                        <button type="button" class="setup-check-item" :class="{ 'is-done': periodReadiness.hasRequirements && periodReadiness.hasCriteria }" @click="activeSettingsTab = 'tracks'">
                            <span class="check-icon">{{ periodReadiness.hasRequirements && periodReadiness.hasCriteria ? '✓' : '3' }}</span>
                            <div><strong>Syarat Berkas & Kriteria</strong><small>{{ periodReadiness.hasRequirements && periodReadiness.hasCriteria ? 'Semua jalur lengkap' : 'Wajib ada di tiap jalur' }}</small></div>
                        </button>
                        <button type="button" class="setup-check-item" :class="{ 'is-done': periodReadiness.hasCommittee }" @click="activeSettingsTab = 'committee'">
                            <span class="check-icon">{{ periodReadiness.hasCommittee ? '✓' : '4' }}</span>
                            <div><strong>Tim Panitia</strong><small>{{ committee.length }} guru ditugaskan</small></div>
                        </button>
                    </div>
                </section>

                <div class="spmb-subnav-bar">
                    <nav class="spmb-workspace-tabs" aria-label="Bagian Pengaturan SPMB">
                        <button type="button" class="spmb-tab-btn" :class="{ 'is-active': activeSettingsTab === 'periods' }" @click="activeSettingsTab = 'periods'">
                            1. Periode & Juknis ({{ periods.length }})
                        </button>
                        <button type="button" class="spmb-tab-btn" :class="{ 'is-active': activeSettingsTab === 'tracks' }" @click="activeSettingsTab = 'tracks'">
                            2. Jalur, Kuota, Berkas & Kriteria ({{ tracks.length }})
                        </button>
                        <button type="button" class="spmb-tab-btn" :class="{ 'is-active': activeSettingsTab === 'committee' }" @click="activeSettingsTab = 'committee'">
                            3. Panitia & Jadwal ({{ committee.length }})
                        </button>
                    </nav>
                </div>

                <!-- SUB-TAB 4A: PERIODE & JUKNIS -->
                <section v-if="activeSettingsTab === 'periods'" class="panel admissions-panel">
                    <div class="table-title"><div><h2>Periode & Juknis</h2><p>Buat siklus SPMB yang dapat dikonfigurasi per tahun ajaran dan jenjang.</p></div></div>
                    <form v-if="can_manage_committee" class="admissions-form" @submit.prevent="addPeriod">
                        <label>Nama periode<input v-model="periodForm.name" required placeholder="SPMB 2027/2028" /></label>
                        <label>Tahun ajaran<input v-model="periodForm.academic_year" required placeholder="2027/2028" /></label>
                        <label>Jenjang<input v-model="periodForm.level" placeholder="SMP" /></label>
                        <label>Mulai pendaftaran<input v-model="periodForm.registration_start" type="date" /></label>
                        <label>Akhir pendaftaran<input v-model="periodForm.registration_end" type="date" /></label>
                        <label>Mulai verifikasi<input v-model="periodForm.verification_start" type="date" /></label>
                        <label>Akhir verifikasi<input v-model="periodForm.verification_end" type="date" /></label>
                        <label>Mulai seleksi<input v-model="periodForm.selection_start" type="date" /></label>
                        <label>Akhir seleksi<input v-model="periodForm.selection_end" type="date" /></label>
                        <label>Tanggal pengumuman<input v-model="periodForm.announcement_at" type="date" /></label>
                        <label>Mulai daftar ulang<input v-model="periodForm.re_registration_start" type="date" /></label>
                        <label>Akhir daftar ulang<input v-model="periodForm.re_registration_end" type="date" /></label>
                        <label>Juknis<input type="file" accept=".pdf,.doc,.docx" @change="periodForm.guideline_file = ($event.target as HTMLInputElement).files?.[0] || null" /></label>
                        <div class="admissions-form-actions"><button class="primary-button" type="submit" :disabled="periodForm.processing">Tambah periode</button></div>
                    </form>
                    <div v-for="period in periods" :key="period.id" class="period-row">
                        <div><strong>{{ period.name }}</strong><span>{{ period.academic_year }} · {{ period.level || 'Semua jenjang' }} · {{ period.tracks.length }} jalur</span></div>
                        <button v-if="can_manage_committee && period.status === 'draft'" class="secondary-button" type="button" @click="openPeriod(period)">Buka pendaftaran</button>
                        <span class="status-pill">{{ periodStatusLabels[period.status] ?? period.status }}</span>
                    </div>
                </section>

                <!-- SUB-TAB 4B: JALUR, KUOTA, BERKAS & KRITERIA -->
                <section v-if="activeSettingsTab === 'tracks'" class="panel admissions-panel">
                    <div class="table-title"><div><h2>Jalur, kuota, berkas & kriteria</h2><p>Konfigurasi mengikuti juknis periode, bukan angka hardcode.</p></div></div>
                    <form v-if="can_manage_committee && selected_period_id" class="admissions-form" @submit.prevent="addTrack">
                        <label>Jalur<select v-model="trackForm.code"><option value="">Pilih jalur</option><option value="DOMISILI">Domisili</option><option value="AFIRMASI">Afirmasi</option><option value="PRESTASI">Prestasi</option><option value="MUTASI">Mutasi</option></select></label>
                        <label>Nama jalur<input v-model="trackForm.name" required /></label>
                        <label>Kuota<input v-model.number="trackForm.quota" type="number" min="1" required /></label>
                        <div class="admissions-form-actions"><button class="primary-button" type="submit">Tambah jalur</button></div>
                    </form>
                    <article v-for="track in tracks" :key="track.id" class="track-card track-card-detailed">
                        <div class="track-card-top">
                            <div><strong>{{ track.name }}</strong><span>{{ track.code }} · kuota {{ track.quota }}</span></div>
                            <small>{{ track.requirements.length }} persyaratan · {{ track.criteria.length }} kriteria</small>
                        </div>
                        <div v-if="track.requirements.length || track.criteria.length" class="track-meta-pills">
                            <span v-for="req in track.requirements" :key="`req-${req.id}`" class="meta-chip">Berkas: {{ req.label }}</span>
                            <span v-for="crit in track.criteria" :key="`crit-${crit.id}`" class="meta-chip is-criterion">Kriteria: {{ crit.label }} (bobot {{ crit.weight }})</span>
                        </div>
                    </article>
                    <form v-if="can_manage_committee && tracks.length" class="admissions-form compact-form" @submit.prevent="addRequirement">
                        <label>Jalur<select v-model="requirementForm.track_id" required><option value="">Pilih jalur</option><option v-for="track in tracks" :key="track.id" :value="track.id">{{ track.name }}</option></select></label>
                        <label>Kode berkas<input v-model="requirementForm.code" required placeholder="KK" /></label>
                        <label>Nama berkas<input v-model="requirementForm.label" required placeholder="Kartu Keluarga" /></label>
                        <button class="secondary-button" type="submit">Tambah berkas</button>
                    </form>
                    <form v-if="can_manage_committee && tracks.length" class="admissions-form compact-form" @submit.prevent="addCriterion">
                        <label>Jalur<select v-model="criterionForm.track_id" required><option value="">Pilih jalur</option><option v-for="track in tracks" :key="track.id" :value="track.id">{{ track.name }}</option></select></label>
                        <label>Kode kriteria<input v-model="criterionForm.code" required placeholder="NILAI" /></label>
                        <label>Nama kriteria<input v-model="criterionForm.label" required placeholder="Nilai seleksi" /></label>
                        <label>Bobot<input v-model.number="criterionForm.weight" type="number" min="0.01" step="0.01" /></label>
                        <button class="secondary-button" type="submit">Tambah kriteria</button>
                    </form>
                </section>

                <!-- SUB-TAB 4C: PANITIA & JADWAL -->
                <template v-if="activeSettingsTab === 'committee'">
                    <section class="panel admissions-panel committee-panel">
                        <div class="table-title committee-heading"><div><span class="section-overline">Penugasan tim</span><h2>Panitia & jadwal</h2><p>Atur guru yang bertugas memverifikasi berkas dan menilai pendaftar pada periode ini.</p></div><span class="committee-count"><strong>{{ committee.length }}</strong> guru ditugaskan</span></div>
                        <div class="committee-management">
                            <form v-if="can_manage_committee" class="admissions-assign-form" @submit.prevent="assignTeacher">
                                <label for="committee-teacher">Tambah anggota panitia<select id="committee-teacher" v-model="committeeForm.user_id" required><option value="">Pilih guru</option><option v-for="teacher in available_teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }} · {{ teacher.email }}</option></select></label>
                                <button class="primary-button" type="submit">Tugaskan guru</button>
                            </form>
                            <div class="committee-guidance"><span class="guidance-mark" aria-hidden="true">✓</span><div><strong>Peran panitia</strong><p>Anggota yang ditugaskan dapat membantu proses verifikasi dan penilaian pendaftar.</p></div></div>
                        </div>
                        <div class="committee-list-heading"><div><h3>Panitia aktif</h3><p>Daftar guru yang saat ini menerima penugasan.</p></div><span>{{ committee.length }} anggota</span></div>
                        <ul class="committee-list">
                            <li v-for="member in committee" :key="member.id">
                                <div class="committee-member-identity"><span class="member-avatar" aria-hidden="true">{{ memberInitials(member.name) }}</span><span class="committee-member-copy"><strong>{{ member.name }}</strong><span>{{ member.email }}</span></span></div>
                                <div class="committee-member-actions"><span class="member-status">Aktif</span><button v-if="can_manage_committee" class="secondary-button" type="button" @click="removeMember(member)">Cabut</button></div>
                            </li>
                            <li v-if="!committee.length" class="committee-empty"><span class="member-avatar" aria-hidden="true">＋</span><div><strong>Belum ada panitia</strong><span>Tugaskan guru untuk memulai proses penerimaan.</span></div></li>
                        </ul>
                    </section>
                    <section v-if="selectedPeriod" class="panel admissions-panel committee-schedule-panel">
                        <div class="table-title committee-heading"><div><span class="section-overline">Kalender kerja</span><h2>Jadwal penerimaan</h2><p>Gunakan tanggal ini sebagai acuan pembagian tugas dan tindak lanjut panitia.</p></div><span class="status-pill">{{ periodStatusLabel }}</span></div>
                        <div class="schedule-grid">
                            <article class="schedule-item"><span class="schedule-index">01</span><div><strong>Verifikasi berkas</strong><span>{{ formatDateRange(selectedPeriod.verification_start, selectedPeriod.verification_end) }}</span><small>Pemeriksaan kelengkapan dan validitas dokumen.</small></div></article>
                            <article class="schedule-item"><span class="schedule-index">02</span><div><strong>Seleksi & penilaian</strong><span>{{ formatDateRange(selectedPeriod.selection_start, selectedPeriod.selection_end) }}</span><small>Penilaian pendaftar dan penetapan peringkat.</small></div></article>
                            <article class="schedule-item"><span class="schedule-index">03</span><div><strong>Pengumuman</strong><span>{{ formatDate(selectedPeriod.announcement_at) ?? 'Tanggal belum diatur' }}</span><small>Publikasi hasil seleksi kepada pendaftar.</small></div></article>
                            <article class="schedule-item"><span class="schedule-index">04</span><div><strong>Daftar ulang</strong><span>{{ formatDateRange(selectedPeriod.re_registration_start, selectedPeriod.re_registration_end) }}</span><small>Konfirmasi pendaftar diterima menjadi siswa.</small></div></article>
                        </div>
                    </section>
                </template>
            </template>

            <!-- 360° APPLICANT SLIDE-OVER DRAWER -->
            <div v-if="selectedApplicant" class="applicant-drawer-overlay" role="dialog" aria-modal="true" :aria-label="`Detail pendaftar ${selectedApplicant.name}`" @click.self="closeApplicantDrawer">
                <aside class="applicant-drawer">
                    <header class="applicant-drawer-header">
                        <div>
                            <span class="admissions-registration">{{ selectedApplicant.registration_no }}</span>
                            <h2>{{ selectedApplicant.name }}</h2>
                            <p>{{ selectedApplicant.track?.name || 'Belum memilih jalur' }} · NISN {{ selectedApplicant.nisn || '—' }}</p>
                        </div>
                        <button type="button" class="secondary-button btn-compact" aria-label="Tutup panel detail" @click="closeApplicantDrawer">Tutup ×</button>
                    </header>

                    <div class="applicant-drawer-body">
                        <!-- 1. Identitas & Status -->
                        <section class="drawer-section">
                            <div class="drawer-section-head">
                                <h3>Identitas & Status Pendaftar</h3>
                                <div v-if="can_manage_applicants" class="intake-action-group">
                                    <button
                                        type="button"
                                        class="icon-action-btn is-edit"
                                        :title="`Edit data ${selectedApplicant.name}`"
                                        :aria-label="`Edit pendaftar ${selectedApplicant.name}`"
                                        @click="openEditApplicantModal(selectedApplicant)"
                                    >
                                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.586 3.586a2 2 0 112.828 2.828l-8.793 8.793-3.536.707.707-3.536 8.794-8.792z" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        class="icon-action-btn is-delete"
                                        :disabled="!canDeleteApplicant(selectedApplicant)"
                                        :title="canDeleteApplicant(selectedApplicant) ? `Hapus ${selectedApplicant.name}` : 'Pendaftar sudah daftar ulang tidak dapat dihapus'"
                                        :aria-label="`Hapus pendaftar ${selectedApplicant.name}`"
                                        @click="deleteApplicant(selectedApplicant)"
                                    >
                                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h8m-6 0V4.5A1.5 1.5 0 019.5 3h1A1.5 1.5 0 0112 4.5V6m2.5 0l-.58 9.28A1.5 1.5 0 0112.423 16.7H7.577a1.5 1.5 0 01-1.497-1.42L5.5 6h9zM8.5 9v4.5m3-4.5v4.5" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <dl class="drawer-meta-grid">
                                <div><dt>Tanggal Daftar</dt><dd>{{ formatDate(selectedApplicant.created_at) || '—' }}</dd></div>
                                <div><dt>Jenis Kelamin</dt><dd>{{ selectedApplicant.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd></div>
                                <div><dt>Tanggal Lahir</dt><dd>{{ formatDate(selectedApplicant.birth_date) || '—' }}</dd></div>
                                <div><dt>Asal Sekolah</dt><dd>{{ selectedApplicant.previous_school || '—' }}</dd></div>
                                <div><dt>Orang Tua / Wali</dt><dd>{{ selectedApplicant.parent_name || '—' }} ({{ selectedApplicant.parent_phone || '—' }})</dd></div>
                            </dl>
                            <form v-if="can_manage_applicants" class="admissions-status-form drawer-status-form" @submit.prevent="updateApplicant(selectedApplicant, $event)">
                                <label>Status alur<select name="status" :value="selectedApplicant.status"><option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option></select></label>
                                <label>Catatan panitia<textarea name="notes" rows="2">{{ selectedApplicant.notes }}</textarea></label>
                                <button class="primary-button" type="submit">Perbarui status</button>
                            </form>
                        </section>

                        <!-- 2. Berkas & Verifikasi -->
                        <section class="drawer-section">
                            <div class="drawer-section-head">
                                <h3>Kelengkapan & Verifikasi Berkas</h3>
                                <span class="status-pill" :class="applicantDocumentSummary(selectedApplicant).className">{{ applicantDocumentSummary(selectedApplicant).label }}</span>
                            </div>
                            <div v-if="selectedApplicant.documents.length" class="document-list">
                                <div v-for="document in selectedApplicant.documents" :key="document.id" class="document-row drawer-document-row">
                                    <div class="document-main"><span class="document-mark" aria-hidden="true">▤</span><div><strong>{{ document.requirement?.label || document.original_name }}</strong><small>{{ document.original_name }}</small></div></div>
                                    <span class="status-pill document-status-pill" :class="`document-status-${document.status}`">{{ documentStatusLabel(document.status) }}</span>
                                    <div class="drawer-doc-actions">
                                        <a :href="`/spmb/berkas/${document.id}/unduh`" class="secondary-button btn-compact">Unduh</a>
                                        <template v-if="can_manage_applicants">
                                            <button class="secondary-button btn-compact" type="button" @click="verifyDocument(document, 'valid')">Valid</button>
                                            <button class="secondary-button btn-compact revision-button" type="button" @click="verifyDocument(document, 'needs_revision')">Revisi</button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="empty-state">Belum ada berkas yang diunggah untuk pendaftar ini.</p>

                            <form v-if="can_manage_applicants && selectedApplicant.track" class="upload-form drawer-upload-form" @submit.prevent="uploadDocument(selectedApplicant)">
                                <select v-model="documentForm(selectedApplicant).requirement_id">
                                    <option value="">Berkas umum</option>
                                    <option v-for="requirement in selectedApplicant.track.requirements" :key="requirement.id" :value="requirement.id">{{ requirement.label }}</option>
                                </select>
                                <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="documentForm(selectedApplicant).file = ($event.target as HTMLInputElement).files?.[0] || null" required />
                                <button class="secondary-button" type="submit">Unggah berkas</button>
                            </form>
                        </section>

                        <!-- 3. Ringkasan Seleksi -->
                        <section class="drawer-section">
                            <div class="drawer-section-head">
                                <h3>Posisi Seleksi & Nilai</h3>
                                <span class="status-pill" :class="selectionDecisionClass(selectedApplicant.selection_result?.decision)">
                                    Peringkat #{{ selectedApplicant.selection_result?.rank || '—' }} · {{ selectionDecisionLabel(selectedApplicant.selection_result?.decision) }}
                                </span>
                            </div>
                            <div v-if="can_manage_applicants && selectedApplicant.track?.criteria?.length" class="selection-criteria drawer-criteria">
                                <div v-for="criterion in selectedApplicant.track.criteria" :key="criterion.id" class="selection-criterion">
                                    <div><strong>{{ criterion.label }}</strong><small>Maks. {{ criterion.max_score }} poin</small></div>
                                    <div class="selection-score-action">
                                        <input v-model="scoreForm(selectedApplicant, criterion).score" type="number" min="0" :max="criterion.max_score" :aria-label="`Nilai ${criterion.label} untuk ${selectedApplicant.name}`" />
                                        <button class="secondary-button" type="button" @click="saveScore(selectedApplicant, criterion)">Simpan</button>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </aside>
            </div>

            <!-- EDIT APPLICANT MODAL -->
            <div v-if="editingApplicant" class="applicant-modal-overlay" role="dialog" aria-modal="true" :aria-label="`Edit data pendaftar ${editingApplicant.name}`" @click.self="closeEditApplicantModal">
                <section class="applicant-modal panel admissions-panel">
                    <header class="applicant-modal-header">
                        <div>
                            <span class="admissions-registration">{{ editingApplicant.registration_no }}</span>
                            <h2>Edit Data Pendaftar</h2>
                            <p>Perbarui identitas calon siswa, jalur pendaftaran, atau status verifikasi.</p>
                        </div>
                        <button type="button" class="secondary-button btn-compact" aria-label="Tutup form edit" @click="closeEditApplicantModal">Tutup ×</button>
                    </header>
                    <form class="admissions-form applicant-edit-form" @submit.prevent="submitEditApplicant">
                        <label>Periode<select v-model="editApplicantForm.period_id" required @change="changeEditApplicantPeriod"><option value="">Pilih periode</option><option v-for="period in periods" :key="period.id" :value="String(period.id)">{{ period.name }}</option></select></label>
                        <label>Jalur<select v-model="editApplicantForm.track_id" required><option value="">Pilih jalur</option><option v-for="track in editApplicantTracks.length ? editApplicantTracks : tracks" :key="track.id" :value="String(track.id)">{{ track.name }}</option></select></label>
                        <label>Status alur<select v-model="editApplicantForm.status"><option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option></select></label>
                        <label>Nama calon siswa<input v-model="editApplicantForm.name" required /></label>
                        <label>NISN<input v-model="editApplicantForm.nisn" inputmode="numeric" /></label>
                        <label>Jenis kelamin<select v-model="editApplicantForm.gender"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></label>
                        <label>Tanggal lahir<input v-model="editApplicantForm.birth_date" type="date" /></label>
                        <label>Nama orang tua/wali<input v-model="editApplicantForm.parent_name" /></label>
                        <label>Kontak orang tua<input v-model="editApplicantForm.parent_phone" /></label>
                        <label class="admissions-form-wide">Asal sekolah<input v-model="editApplicantForm.previous_school" /></label>
                        <label class="admissions-form-wide">Catatan verifikasi<textarea v-model="editApplicantForm.notes" rows="2" /></label>
                        <div class="admissions-form-actions admissions-form-wide">
                            <button type="button" class="secondary-button" @click="closeEditApplicantModal">Batal</button>
                            <button type="submit" class="primary-button" :disabled="editApplicantForm.processing">Simpan perubahan</button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.spmb-overview { display: grid; gap: 1.15rem; }
.spmb-overview-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; }
.overview-heading-actions { display: flex; align-items: center; gap: .6rem; }
.spmb-kicker { margin: 0 0 .4rem; color: var(--primary); font-size: .78rem; font-weight: 700; }
.spmb-overview-heading h1 { margin: 0; color: var(--ink); font-size: clamp(1.8rem, 3vw, 2.3rem); font-weight: 700; letter-spacing: -.04em; line-height: 1.2; }
.spmb-overview-heading p:not(.spmb-kicker) { margin: .45rem 0 0; color: var(--ink-soft); font-size: .92rem; }
.overview-period-link { display: inline-flex; align-items: center; justify-content: center; gap: .65rem; min-height: 2.75rem; border: 1px solid var(--line-strong); border-radius: .7rem; padding: .65rem .9rem; color: var(--primary); background: var(--surface); font-size: .85rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
.overview-period-link:hover { border-color: var(--primary); background: var(--primary-soft); }
.overview-period-picker { display: flex; align-items: center; gap: 1rem; padding: .85rem 1rem; border: 1px solid var(--line); border-radius: .85rem; background: var(--surface); }
.overview-period-picker label { color: var(--ink-soft); font-size: .83rem; font-weight: 700; }
.overview-period-picker select { min-width: min(24rem, 100%); border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); padding: .62rem .75rem; color: var(--ink); }
.overview-period-banner { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.3rem; border-left: 4px solid var(--primary); border-radius: .25rem .85rem .85rem .25rem; background: color-mix(in srgb, var(--primary-soft) 62%, var(--surface)); }
.period-overline { color: var(--ink-soft); font-size: var(--type-sm); font-weight: var(--weight-semibold); }
.overview-period-banner h2 { margin: .2rem 0 0; color: var(--ink); font-size: 1.35rem; letter-spacing: -.025em; }
.period-phase-pill { display: inline-flex; align-items: center; gap: .5rem; border: 1px solid var(--line); border-radius: 999px; padding: .45rem .7rem; color: var(--ink); background: var(--surface); font-size: .8rem; font-weight: 700; white-space: nowrap; }
.period-phase-pill i { width: .5rem; height: .5rem; border-radius: 50%; background: var(--primary); }
.phase-registration_open i, .phase-verification i, .phase-selection i, .phase-announcement i, .phase-re_registration i { background: #138a62; }
.phase-completed i, .phase-archived i { background: #7a8798; }
.overview-metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .7rem; }
.overview-metric { display: grid; align-content: start; min-height: 7.6rem; padding: .95rem 1rem; border: 1px solid var(--line); border-radius: .8rem; color: inherit; background: var(--surface); text-decoration: none; }
.overview-metric:hover { border-color: var(--primary); }
.overview-metric > span { color: var(--ink-soft); font-size: var(--type-xs); font-weight: var(--weight-semibold); }
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
.phase-track li.is-current { color: var(--primary); font-weight: var(--weight-bold); }
.phase-track li.is-current i { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
.period-date-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 1rem; margin: 0; border-top: 1px solid var(--line); }
.period-date-list div { display: flex; justify-content: space-between; gap: .6rem; padding: .62rem 0; border-bottom: 1px solid var(--line); }
.period-date-list dt { color: var(--ink-soft); font-size: .75rem; }
.period-date-list dd { margin: 0; color: var(--ink); font-size: var(--type-xs); font-weight: var(--weight-semibold); text-align: right; }
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
.admissions-page-header { display: grid; gap: 1.5rem; padding: .5rem 0 .25rem; }
.admissions-page-header > div:first-child { min-width: 0; }
.admissions-page-header .eyebrow { margin: 0 0 .5rem; color: var(--primary); font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: none; }
.admissions-page-header h1 { margin: 0; color: var(--ink); font-size: clamp(1.8rem, 3vw, 2.3rem); font-weight: 700; letter-spacing: -.04em; line-height: 1.2; }
.admissions-page-header p:not(.eyebrow) { max-width: 640px; margin: .5rem 0 0; color: var(--ink-soft); font-size: .9rem; line-height: 1.55; }
.admissions-page-header .admissions-summary { width: 100%; min-width: 0; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; overflow: visible; background: transparent; border: 0; border-radius: 0; box-shadow: none; }
.admissions-page-header .admissions-summary > div { min-height: 100px; padding: 1rem; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; }
.admissions-page-header .admissions-summary span { color: var(--ink-soft); }
.admissions-page-header .admissions-summary strong { color: var(--ink); }

.admissions-panel { padding: 1.25rem; color: var(--ink); background: var(--surface); border-color: var(--line); }
.applicants-panel { padding: clamp(1.1rem, 2vw, 1.5rem); }
.applicants-page-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.applicants-page-heading .table-title { margin: 0; }
.applicants-page-heading .primary-button { flex: 0 0 auto; }
.applicant-insights { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .7rem; margin-top: 1.25rem; }
.applicant-insight { display: grid; gap: .2rem; min-height: 5.9rem; padding: .85rem .95rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.applicant-insight.is-total { border-top: 3px solid var(--primary); background: var(--primary-soft); }
.applicant-insight span { color: var(--ink-soft); font-size: .73rem; font-weight: 700; }
.applicant-insight strong { color: var(--ink); font-size: 1.45rem; line-height: 1.1; font-variant-numeric: tabular-nums; }
.applicant-insight small { color: var(--ink-faint); font-size: .68rem; }
.applicant-create-form { grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 1.1rem; margin-bottom: 0; padding: 1.1rem; border: 1px solid color-mix(in srgb, var(--primary) 18%, var(--line)); border-radius: .85rem; background: color-mix(in srgb, var(--primary-soft) 35%, var(--surface)); }
.form-section-intro { grid-column: 1 / -1; padding-bottom: .25rem; border-bottom: 1px solid color-mix(in srgb, var(--primary) 14%, var(--line)); }
.form-section-intro h3 { margin: 0; color: var(--ink); font-size: 1rem; }
.form-section-intro p { margin: .3rem 0 0; color: var(--ink-soft); font-size: .78rem; }
.applicant-list-panel { margin-top: 1.35rem; padding-top: 1.15rem; border-top: 1px solid var(--line); }
.applicant-list-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; }
.applicant-list-heading h3 { margin: 0; color: var(--ink); font-size: 1rem; }
.applicant-list-heading p { margin: .25rem 0 0; color: var(--ink-soft); font-size: .76rem; }
.filter-result-label { border-radius: 999px; padding: .28rem .55rem; color: var(--primary); background: var(--primary-soft); font-size: .7rem; font-weight: 800; }
.applicant-filters { display: grid; grid-template-columns: minmax(15rem, 2fr) minmax(9rem, 1fr) minmax(9rem, 1fr) auto; align-items: end; gap: .7rem; margin: .9rem 0 1rem; padding: .8rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.applicant-filters label { display: grid; gap: .35rem; color: var(--ink-soft); font-size: .72rem; font-weight: 700; }
.applicant-filters input, .applicant-filters select { width: 100%; min-height: 2.45rem; border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); padding: .58rem .7rem; color: var(--ink); font-size: .8rem; }
.applicant-search-field input { min-width: 0; }
.filter-reset-button { min-height: 2.45rem; border: 0; border-radius: .6rem; padding: .58rem .7rem; color: var(--primary); background: transparent; font-size: .75rem; font-weight: 800; white-space: nowrap; }
.filter-reset-button:hover { background: var(--primary-soft); }
.applicant-list-panel .admissions-applicant { padding: 1rem 1.05rem; }
.applicant-list-panel .admissions-status-form { margin-top: .15rem; }
.status-needs_revision { color: #a15c00; background: #fff4d8; }
.status-accepted, .status-re_registered { color: #138a62; background: #e5f5ee; }
.status-rejected { color: #b42318; background: #fdebea; }
.documents-panel { padding: clamp(1.1rem, 2vw, 1.5rem); }
.document-insights { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .7rem; margin-top: 1.25rem; }
.document-insight { display: grid; gap: .2rem; min-height: 5.9rem; padding: .85rem .95rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.document-insight.is-total { border-top: 3px solid var(--primary); background: var(--primary-soft); }
.document-insight.is-valid { border-top: 3px solid #138a62; }
.document-insight.is-attention { border-top: 3px solid #d89b32; }
.document-insight span { color: var(--ink-soft); font-size: .73rem; font-weight: 700; }
.document-insight strong { color: var(--ink); font-size: 1.45rem; line-height: 1.1; font-variant-numeric: tabular-nums; }
.document-insight small { color: var(--ink-faint); font-size: .68rem; }
.document-list-panel { margin-top: 1.35rem; padding-top: 1.15rem; border-top: 1px solid var(--line); }
.document-list-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; }
.document-list-heading h3 { margin: 0; color: var(--ink); font-size: 1rem; }
.document-list-heading p { margin: .25rem 0 0; color: var(--ink-soft); font-size: .76rem; }
.document-filters { display: grid; grid-template-columns: minmax(15rem, 2fr) minmax(12rem, 1fr) auto; align-items: end; gap: .7rem; margin: .9rem 0 1rem; padding: .8rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.document-filters label { display: grid; gap: .35rem; color: var(--ink-soft); font-size: .72rem; font-weight: 700; }
.document-filters input, .document-filters select { width: 100%; min-height: 2.45rem; border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); padding: .58rem .7rem; color: var(--ink); font-size: .8rem; }
.document-applicant { padding: 1rem 1.05rem; }
.document-applicant-meta { margin: .28rem 0 0; color: var(--ink-soft); font-size: .76rem; }
.document-main { display: flex; align-items: center; gap: .7rem; min-width: 0; }
.document-mark { display: grid; flex: 0 0 2rem; place-items: center; width: 2rem; height: 2rem; border-radius: .6rem; color: var(--primary); background: var(--primary-soft); font-size: .9rem; }
.document-main > div { display: grid; min-width: 0; gap: .18rem; }
.document-main strong { overflow: hidden; color: var(--ink); font-size: .82rem; text-overflow: ellipsis; white-space: nowrap; }
.document-main small { overflow: hidden; color: var(--ink-soft); font-size: .7rem; text-overflow: ellipsis; white-space: nowrap; }
.document-row { display: grid; grid-template-columns: minmax(0, 1fr) 10rem 6rem 15rem; align-items: center; gap: .8rem; padding: .7rem .8rem; }
.document-row > a { display: inline-flex; align-items: center; justify-content: center; justify-self: stretch; min-width: 4.4rem; min-height: 2.3rem; border: 1px solid var(--line); border-radius: .55rem; padding: .5rem .65rem; color: var(--primary); background: var(--surface-subtle); font-size: .72rem; font-weight: 800; text-decoration: none; white-space: nowrap; }
.document-row > a:hover { border-color: var(--primary); background: var(--primary-soft); }
.document-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: center; gap: .45rem; min-width: 0; padding-left: .7rem; border-left: 1px solid var(--line); }
.document-actions .secondary-button, .document-actions .link-danger { display: inline-flex; align-items: center; justify-content: center; min-height: 2.3rem; border-radius: .55rem; padding: .5rem .7rem; font-size: .72rem; font-weight: 800; white-space: nowrap; }
.document-actions .secondary-button { min-width: 5.6rem; }
.document-actions .link-danger { min-width: 7.7rem; border: 1px solid #f1c9c5; background: #fff8f7; color: #b42318; }
.document-actions .link-danger:hover { background: #fdebea; }
.document-status-pill { justify-self: center; text-align: center; }
.document-status-valid, .document-summary-valid { color: #138a62; background: #e5f5ee; }
.document-status-needs_revision, .document-summary-revision { color: #a15c00; background: #fff4d8; }
.document-status-pending, .document-summary-pending { color: #3157a4; background: var(--primary-soft); }
.document-summary-empty { color: var(--ink-soft); background: var(--surface-subtle); }
.document-applicant .upload-form { display: grid; grid-template-columns: minmax(12rem, 1fr) minmax(18rem, 1.4fr) auto; align-items: end; gap: .7rem; }
.document-upload-file { display: grid; flex: 1; gap: .35rem; color: var(--ink-soft); font-size: .72rem; font-weight: 700; }
.document-file-picker { position: relative; display: flex; align-items: center; min-width: 0; min-height: 2.6rem; overflow: hidden; border: 1px solid var(--line-strong); border-radius: .65rem; background: var(--surface); }
.document-file-input { position: absolute; inset: 0; z-index: 1; width: 100% !important; height: 100%; min-height: 0 !important; opacity: 0; cursor: pointer; }
.document-file-button { flex: 0 0 auto; margin: .25rem; border: 1px solid var(--line-strong); border-radius: .45rem; padding: .42rem .6rem; color: var(--primary); background: var(--primary-soft); font-size: .72rem; font-weight: 800; }
.document-file-name { min-width: 0; overflow: hidden; padding: 0 .6rem; color: var(--ink-soft); font-size: .75rem; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
.document-applicant .upload-form > .secondary-button { min-height: 2.6rem; }
.selection-panel { padding: clamp(1.1rem, 2vw, 1.5rem); }
.selection-page-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.selection-page-heading .table-title { margin: 0; }
.selection-page-heading .primary-button { flex: 0 0 auto; }
.selection-insights { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .7rem; margin-top: 1.25rem; }
.selection-insight { display: grid; gap: .2rem; min-height: 5.9rem; padding: .85rem .95rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.selection-insight.is-total { border-top: 3px solid var(--primary); background: var(--primary-soft); }
.selection-insight.is-ranked { border-top: 3px solid #3157a4; }
.selection-insight.is-decided { border-top: 3px solid #138a62; }
.selection-insight span { color: var(--ink-soft); font-size: .73rem; font-weight: 700; }
.selection-insight strong { color: var(--ink); font-size: 1.45rem; line-height: 1.1; font-variant-numeric: tabular-nums; }
.selection-insight small { color: var(--ink-faint); font-size: .68rem; }
.selection-list-panel { margin-top: 1.35rem; padding-top: 1.15rem; border-top: 1px solid var(--line); }
.selection-list-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; }
.selection-list-heading h3 { margin: 0; color: var(--ink); font-size: 1rem; }
.selection-list-heading p { margin: .25rem 0 0; color: var(--ink-soft); font-size: .76rem; }
.selection-filters { display: grid; grid-template-columns: minmax(15rem, 2fr) minmax(10rem, 1fr) minmax(12rem, 1fr) auto; align-items: end; gap: .7rem; margin: .9rem 0 1rem; padding: .8rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.selection-filters label { display: grid; gap: .35rem; color: var(--ink-soft); font-size: .72rem; font-weight: 700; }
.selection-filters input, .selection-filters select { width: 100%; min-height: 2.45rem; border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); padding: .58rem .7rem; color: var(--ink); font-size: .8rem; }
.selection-applicants { display: grid; gap: .85rem; }
.selection-applicant { border: 1px solid var(--line); border-radius: .85rem; padding: 1rem 1.05rem; background: var(--surface); }
.selection-applicant-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.selection-applicant-heading p { margin: .3rem 0 0; color: var(--ink-soft); font-size: .76rem; }
.selection-rank { display: grid; justify-items: center; gap: .15rem; min-width: 5.5rem; padding: .45rem .6rem; border: 1px solid var(--line); border-radius: .7rem; background: var(--surface-subtle); text-align: center; }
.selection-rank span { color: var(--ink-soft); font-size: .68rem; font-weight: 700; }
.selection-rank strong { color: var(--primary); font-size: 1.1rem; line-height: 1; }
.selection-score-summary { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: .55rem; margin-top: .9rem; padding: .7rem .8rem; border: 1px solid var(--line); border-radius: .7rem; background: var(--surface-subtle); color: var(--ink-soft); font-size: .76rem; text-align: center; }
.selection-score-summary strong { color: var(--ink); font-size: 1.05rem; font-variant-numeric: tabular-nums; }
.selection-score-summary .status-pill { margin-left: 0; }
.selection-decision-accepted { color: #138a62; background: #e5f5ee; }
.selection-decision-waitlisted { color: #a15c00; background: #fff4d8; }
.selection-decision-rejected { color: #b42318; background: #fdebea; }
.selection-decision-pending { color: var(--primary); background: var(--primary-soft); }
.selection-criteria { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; margin-top: .75rem; }
.selection-criterion { display: flex; align-items: center; justify-content: space-between; gap: .7rem; min-width: 0; padding: .7rem .75rem; border: 1px solid var(--line); border-radius: .7rem; background: var(--surface); }
.selection-criterion > div:first-child { display: grid; min-width: 0; gap: .2rem; }
.selection-criterion strong { overflow: hidden; color: var(--ink); font-size: .78rem; text-overflow: ellipsis; white-space: nowrap; }
.selection-criterion small { color: var(--ink-faint); font-size: .68rem; }
.selection-score-action { display: flex; align-items: center; flex: 0 0 auto; gap: .45rem; }
.selection-score-action input { width: 4.5rem; min-height: 2.35rem; border: 1px solid var(--line-strong); border-radius: .55rem; padding: .5rem .55rem; color: var(--ink); background: var(--surface); text-align: center; }
.selection-score-action .secondary-button { min-height: 2.35rem; padding: .5rem .65rem; font-size: .72rem; }
.selection-empty-note { margin: .75rem 0 0; padding: .7rem .8rem; border: 1px dashed var(--line-strong); border-radius: .7rem; color: var(--ink-soft); font-size: .76rem; }
.selection-decision-form { display: grid; gap: .8rem; margin-top: .9rem; padding: .85rem; border: 1px solid color-mix(in srgb, var(--primary) 18%, var(--line)); border-radius: .8rem; background: color-mix(in srgb, var(--primary-soft) 35%, var(--surface)); }
.selection-decision-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.selection-decision-heading > div { display: grid; gap: .2rem; }
.selection-decision-heading strong { color: var(--ink); font-size: .82rem; }
.selection-decision-heading span:not(.selection-decision-state) { color: var(--ink-soft); font-size: .72rem; }
.selection-decision-state { border-radius: 999px; padding: .3rem .55rem; font-size: .7rem; font-weight: 800; white-space: nowrap; }
.selection-decision-state, .selection-score-summary .status-pill { text-align: center; justify-content: center; }
.selection-decision-fields { display: grid; grid-template-columns: minmax(11rem, .35fr) minmax(16rem, 1fr) auto; align-items: end; gap: .7rem; }
.selection-decision-fields label { display: grid; gap: .35rem; color: var(--ink-soft); font-size: .72rem; font-weight: 700; }
.selection-decision-fields select, .selection-decision-fields input { width: 100%; min-height: 2.55rem; border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); padding: .6rem .7rem; color: var(--ink); }
.selection-decision-fields select { text-align: center; text-align-last: center; }
.selection-decision-fields .primary-button { min-height: 2.55rem; }
.announcement-panel { padding: clamp(1.1rem, 2vw, 1.5rem); }
.announcement-page-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.announcement-page-heading .table-title { margin: 0; }
.announcement-date { display: grid; min-width: 11rem; gap: .25rem; padding: .7rem .8rem; border: 1px solid var(--line); border-radius: .75rem; background: var(--surface-subtle); text-align: center; }
.announcement-date span { color: var(--ink-soft); font-size: .7rem; font-weight: 700; }
.announcement-date strong { color: var(--primary); font-size: .82rem; }
.announcement-notice { display: flex; align-items: start; gap: .7rem; margin-top: 1.15rem; padding: .85rem .95rem; border: 1px solid color-mix(in srgb, var(--primary) 18%, var(--line)); border-radius: .8rem; background: var(--primary-soft); }
.announcement-notice .guidance-mark { flex: 0 0 1.6rem; }
.announcement-notice div { display: grid; gap: .2rem; }
.announcement-notice strong { color: var(--ink); font-size: .82rem; }
.announcement-notice p { margin: 0; color: var(--ink-soft); font-size: .76rem; line-height: 1.45; }
.announcement-insights { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .7rem; margin-top: 1.15rem; }
.announcement-insight { display: grid; gap: .2rem; min-height: 5.9rem; padding: .85rem .95rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.announcement-insight.is-accepted { border-top: 3px solid #138a62; }
.announcement-insight.is-waitlisted { border-top: 3px solid #d89b32; }
.announcement-insight.is-rejected { border-top: 3px solid #b42318; }
.announcement-insight.is-pending { border-top: 3px solid var(--primary); background: var(--primary-soft); }
.announcement-insight span { color: var(--ink-soft); font-size: .73rem; font-weight: 700; }
.announcement-insight strong { color: var(--ink); font-size: 1.45rem; line-height: 1.1; font-variant-numeric: tabular-nums; }
.announcement-insight small { color: var(--ink-faint); font-size: .68rem; }
.announcement-list-panel { margin-top: 1.35rem; padding-top: 1.15rem; border-top: 1px solid var(--line); }
.announcement-list-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; }
.announcement-list-heading h3 { margin: 0; color: var(--ink); font-size: 1rem; }
.announcement-list-heading p { margin: .25rem 0 0; color: var(--ink-soft); font-size: .76rem; }
.announcement-filters { display: grid; grid-template-columns: minmax(15rem, 2fr) minmax(10rem, 1fr) minmax(12rem, 1fr) auto; align-items: end; gap: .7rem; margin: .9rem 0 1rem; padding: .8rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.announcement-filters label { display: grid; gap: .35rem; color: var(--ink-soft); font-size: .72rem; font-weight: 700; }
.announcement-filters input, .announcement-filters select { width: 100%; min-height: 2.45rem; border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); padding: .58rem .7rem; color: var(--ink); font-size: .8rem; }
.announcement-applicants { display: grid; gap: .85rem; }
.announcement-applicant { border: 1px solid var(--line); border-radius: .85rem; padding: 1rem 1.05rem; background: var(--surface); }
.announcement-applicant-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.announcement-applicant-heading p { margin: .3rem 0 0; color: var(--ink-soft); font-size: .76rem; }
.announcement-result { display: grid; grid-template-columns: minmax(12rem, 1.4fr) minmax(8rem, .7fr) minmax(8rem, .7fr); gap: .6rem; margin-top: .9rem; }
.announcement-result div { display: grid; align-content: center; gap: .2rem; min-height: 3.8rem; padding: .65rem .75rem; border: 1px solid var(--line); border-radius: .7rem; background: var(--surface-subtle); text-align: center; }
.announcement-result span { color: var(--ink-soft); font-size: .7rem; font-weight: 700; }
.announcement-result strong { color: var(--ink); font-size: .9rem; }
.announcement-result strong.selection-decision-accepted { color: #138a62; }
.announcement-result strong.selection-decision-waitlisted { color: #a15c00; }
.announcement-result strong.selection-decision-rejected { color: #b42318; }
.announcement-result strong.selection-decision-pending { color: var(--primary); }
.re-registration-panel { padding: clamp(1.1rem, 2vw, 1.5rem); }
.re-registration-page-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.re-registration-page-heading .table-title { margin: 0; }
.re-registration-date { display: grid; min-width: 13rem; gap: .25rem; padding: .7rem .8rem; border: 1px solid var(--line); border-radius: .75rem; background: var(--surface-subtle); text-align: center; }
.re-registration-date span { color: var(--ink-soft); font-size: .7rem; font-weight: 700; }
.re-registration-date strong { color: var(--primary); font-size: .82rem; }
.re-registration-notice { display: flex; align-items: start; gap: .7rem; margin-top: 1.15rem; padding: .85rem .95rem; border: 1px solid color-mix(in srgb, var(--primary) 18%, var(--line)); border-radius: .8rem; background: var(--primary-soft); }
.re-registration-notice .guidance-mark { flex: 0 0 1.6rem; }
.re-registration-notice div { display: grid; gap: .2rem; }
.re-registration-notice strong { color: var(--ink); font-size: .82rem; }
.re-registration-notice p { margin: 0; color: var(--ink-soft); font-size: .76rem; line-height: 1.45; }
.re-registration-insights { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .7rem; margin-top: 1.15rem; }
.re-registration-insight { display: grid; gap: .2rem; min-height: 5.9rem; padding: .85rem .95rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.re-registration-insight.is-total { border-top: 3px solid var(--primary); background: var(--primary-soft); }
.re-registration-insight.is-pending { border-top: 3px solid #d89b32; }
.re-registration-insight.is-completed { border-top: 3px solid #138a62; }
.re-registration-insight span { color: var(--ink-soft); font-size: .73rem; font-weight: 700; }
.re-registration-insight strong { color: var(--ink); font-size: 1.45rem; line-height: 1.1; font-variant-numeric: tabular-nums; }
.re-registration-insight small { color: var(--ink-faint); font-size: .68rem; }
.re-registration-list-panel { margin-top: 1.35rem; padding-top: 1.15rem; border-top: 1px solid var(--line); }
.re-registration-list-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; }
.re-registration-list-heading h3 { margin: 0; color: var(--ink); font-size: 1rem; }
.re-registration-list-heading p { margin: .25rem 0 0; color: var(--ink-soft); font-size: .76rem; }
.re-registration-filters { display: grid; grid-template-columns: minmax(15rem, 2fr) minmax(12rem, 1fr) auto; align-items: end; gap: .7rem; margin: .9rem 0 1rem; padding: .8rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.re-registration-filters label { display: grid; gap: .35rem; color: var(--ink-soft); font-size: .72rem; font-weight: 700; }
.re-registration-filters input, .re-registration-filters select { width: 100%; min-height: 2.45rem; border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); padding: .58rem .7rem; color: var(--ink); font-size: .8rem; }
.re-registration-applicants { display: grid; gap: .85rem; }
.re-registration-applicant { border: 1px solid var(--line); border-radius: .85rem; padding: 1rem 1.05rem; background: var(--surface); }
.re-registration-applicant-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.re-registration-applicant-heading p { margin: .3rem 0 0; color: var(--ink-soft); font-size: .76rem; }
.re-registration-status-completed { color: #138a62; background: #e5f5ee; }
.re-registration-status-pending { color: #a15c00; background: #fff4d8; }
.re-registration-details { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .6rem; margin-top: .9rem; }
.re-registration-details div { display: grid; gap: .2rem; min-height: 3.6rem; padding: .65rem .75rem; border: 1px solid var(--line); border-radius: .7rem; background: var(--surface-subtle); text-align: center; }
.re-registration-details span { color: var(--ink-soft); font-size: .7rem; font-weight: 700; }
.re-registration-details strong { color: var(--ink); font-size: .8rem; }
.re-registration-form { display: grid; grid-template-columns: minmax(14rem, 1fr) auto; align-items: end; gap: .7rem; margin-top: .8rem; padding-top: .8rem; border-top: 1px solid var(--line); }
.re-registration-form label { display: grid; gap: .35rem; color: var(--ink-soft); font-size: .72rem; font-weight: 700; }
.re-registration-form input { width: 100%; min-height: 2.55rem; border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); padding: .6rem .7rem; color: var(--ink); }
.re-registration-form .primary-button { min-height: 2.55rem; }
.re-registration-complete { display: flex; align-items: center; gap: .65rem; margin-top: .8rem; padding: .7rem .8rem; border: 1px solid #bce5d4; border-radius: .7rem; background: #f0faf5; }
.re-registration-complete .guidance-mark { flex: 0 0 1.6rem; background: #138a62; }
.re-registration-complete p { display: grid; gap: .15rem; margin: 0; }
.re-registration-complete strong { color: #116b4d; font-size: .78rem; }
.re-registration-complete span:not(.guidance-mark) { color: #4d7665; font-size: .72rem; }
.committee-panel, .committee-schedule-panel { padding: clamp(1.1rem, 2vw, 1.5rem); }
.committee-heading { align-items: start; }
.section-overline { display: block; margin-bottom: .35rem; color: var(--primary); font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.committee-heading h2 { margin: 0; color: var(--ink); font-size: 1.25rem; letter-spacing: -.025em; }
.committee-heading p { max-width: 40rem; margin: .35rem 0 0; color: var(--ink-soft); font-size: .84rem; line-height: 1.5; }
.committee-count { display: inline-flex; align-items: center; gap: .4rem; border: 1px solid var(--line); border-radius: 999px; padding: .45rem .65rem; color: var(--ink-soft); background: var(--surface-subtle); font-size: .75rem; white-space: nowrap; }
.committee-count strong { color: var(--primary); font-size: .95rem; }
.committee-management { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(16rem, .65fr); gap: 1rem; align-items: stretch; margin-top: 1.35rem; }
.admissions-assign-form { display: flex; align-items: end; gap: .7rem; padding: .95rem; border: 1px solid var(--line); border-radius: .85rem; background: var(--surface-subtle); }
.admissions-assign-form label { display: grid; flex: 1; gap: .4rem; color: var(--ink-soft); font-size: .78rem; font-weight: 700; }
.admissions-assign-form select { width: 100%; min-height: 2.6rem; border: 1px solid var(--line-strong); border-radius: .65rem; background: var(--surface); padding: .65rem .75rem; color: var(--ink); }
.committee-guidance { display: flex; align-items: start; gap: .7rem; padding: .95rem; border: 1px solid color-mix(in srgb, var(--primary) 18%, var(--line)); border-radius: .85rem; background: var(--primary-soft); }
.guidance-mark { display: grid; flex: 0 0 1.6rem; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: 50%; color: #fff; background: var(--primary); font-size: .8rem; font-weight: 800; }
.committee-guidance strong { color: var(--ink); font-size: .82rem; }
.committee-guidance p { margin: .25rem 0 0; color: var(--ink-soft); font-size: .76rem; line-height: 1.45; }
.committee-list-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin-top: 1.5rem; padding-bottom: .7rem; border-bottom: 1px solid var(--line); }
.committee-list-heading h3 { margin: 0; color: var(--ink); font-size: .95rem; }
.committee-list-heading p { margin: .2rem 0 0; color: var(--ink-soft); font-size: .76rem; }
.committee-list-heading > span { color: var(--ink-faint); font-size: .75rem; font-weight: 700; white-space: nowrap; }
.committee-list { gap: .6rem; margin-top: .75rem; }
.committee-list li { min-height: 4.25rem; padding: .75rem .85rem; background: var(--surface); }
.committee-member-identity { display: flex; align-items: center; gap: .75rem; min-width: 0; }
.member-avatar { display: grid; flex: 0 0 2.25rem; place-items: center; width: 2.25rem; height: 2.25rem; border: 1px solid color-mix(in srgb, var(--primary) 18%, var(--line)); border-radius: .7rem; color: var(--primary); background: var(--primary-soft); font-size: .73rem; font-weight: 800; }
.committee-member-copy { display: grid; min-width: 0; gap: .2rem; }
.committee-member-copy strong { overflow: hidden; color: var(--ink); font-size: .85rem; text-overflow: ellipsis; white-space: nowrap; }
.committee-member-copy span { overflow: hidden; color: var(--ink-soft); font-size: .76rem; text-overflow: ellipsis; white-space: nowrap; }
.committee-member-actions { display: flex; align-items: center; gap: .75rem; }
.member-status { color: #138a62; font-size: .72rem; font-weight: 800; }
.committee-empty { justify-content: start !important; color: var(--ink-soft); }
.committee-empty > div { display: grid; gap: .2rem; }
.committee-empty strong { color: var(--ink); font-size: .82rem; }
.committee-empty > div span { font-size: .76rem; }
.committee-schedule-panel { margin-top: 0; }
.schedule-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .7rem; margin-top: 1.25rem; }
.schedule-item { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .7rem; min-width: 0; padding: .9rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.schedule-index { display: grid; place-items: center; width: 1.75rem; height: 1.75rem; border-radius: .55rem; color: var(--primary); background: var(--primary-soft); font-size: .68rem; font-weight: 800; }
.schedule-item div { display: grid; align-content: start; gap: .22rem; min-width: 0; }
.schedule-item strong { color: var(--ink); font-size: .8rem; }
.schedule-item span:not(.schedule-index) { color: var(--primary); font-size: var(--type-xs); font-weight: var(--weight-bold); line-height: var(--leading-snug); }
.schedule-item small { color: var(--ink-soft); font-size: .7rem; line-height: 1.4; }
.period-context { display: flex; align-items: end; gap: 1rem; flex-wrap: wrap; }
.period-context > label { width: min(100%, 28rem); }
.period-context label, .admissions-form label, .admissions-status-form label { display: grid; gap: .4rem; color: var(--ink-soft); font-size: .85rem; font-weight: 600; }
.period-context select, .admissions-form input, .admissions-form select, .admissions-form textarea, .admissions-status-form select, .admissions-status-form textarea, .upload-form input, .upload-form select, .decision-form input, .decision-form select, .score-row input { width: 100%; border: 1px solid var(--line-strong); border-radius: .65rem; background: var(--surface); padding: .65rem .75rem; color: var(--ink); }
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
.document-row { display: grid; grid-template-columns: minmax(0, 1fr) 10rem 6rem 15rem; align-items: center; justify-content: normal; gap: .8rem; padding: .7rem .8rem; }
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
.report-table thead th { color: var(--ink-soft); font-size: var(--type-xs); font-weight: var(--weight-semibold); }
.report-table tfoot th, .report-table tfoot td { border-bottom: 0; color: var(--ink); font-weight: var(--weight-bold); }
.report-empty { padding: 1.2rem .55rem !important; text-align: center; }
.status-report-list { border-top: 1px solid var(--line); }
.status-report-list div { display: flex; justify-content: space-between; gap: 1rem; padding: .62rem 0; border-bottom: 1px solid var(--line); color: var(--ink-soft); font-size: .8rem; }
.status-report-list strong { color: var(--ink); font-variant-numeric: tabular-nums; }
@media (max-width: 980px) { .overview-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }.overview-columns { grid-template-columns: 1fr; }.committee-management { grid-template-columns: 1fr; }.schedule-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }.applicant-insights, .document-insights { grid-template-columns: repeat(2, minmax(0, 1fr)); }.applicant-filters { grid-template-columns: minmax(0, 1fr) minmax(9rem, 1fr) minmax(9rem, 1fr); }.document-filters { grid-template-columns: minmax(0, 1fr) minmax(12rem, 1fr); }.filter-reset-button { justify-self: start; } }
@media (max-width: 820px) { .workflow-grid, .admissions-form, .compact-form { grid-template-columns: 1fr; } .admissions-form-wide, .form-section-intro { grid-column: auto; } .admissions-status-form, .decision-form, .upload-form, .score-row { display: grid; grid-template-columns: 1fr; align-items: stretch; } .document-applicant .upload-form { grid-template-columns: 1fr; }.admissions-applicant-heading { display: grid; }.spmb-overview-heading { align-items: start; flex-direction: column; }.overview-heading-actions { width: 100%; flex-wrap: wrap; }.overview-period-picker { align-items: stretch; flex-direction: column; gap: .45rem; }.overview-period-picker select { min-width: 0; }.overview-period-banner { align-items: start; flex-direction: column; }.track-overview-row { grid-template-columns: minmax(0, 1fr) auto; gap: .55rem .8rem; padding: .65rem 0; }.quota-progress { grid-column: 1 / -1; grid-row: 2; }.track-overview-quota { grid-column: 2; grid-row: 1; }.period-context { align-items: stretch; }.committee-heading { flex-direction: column; }.committee-count { align-self: flex-start; }.applicants-page-heading { align-items: stretch; flex-direction: column; }.applicants-page-heading .primary-button { width: 100%; }.applicant-filters { grid-template-columns: 1fr 1fr; }.applicant-search-field { grid-column: 1 / -1; }.document-filters { grid-template-columns: 1fr 1fr; }.document-search-field { grid-column: 1 / -1; }.document-row { grid-template-columns: minmax(0, 1fr) auto; }.document-status-pill { justify-self: end; }.document-row > a { grid-column: 1; }.document-actions { grid-column: 2; grid-row: 2; padding-left: 0; border-left: 0; } }
@media (max-width: 560px) { .overview-metrics { gap: .55rem; }.overview-metric { min-height: 7.2rem; padding: .8rem; }.overview-metric strong { font-size: 1.55rem; }.overview-metric small { font-size: .68rem; }.phase-track li { font-size: .58rem; }.phase-track { margin-inline: -.2rem; }.period-date-list { grid-template-columns: 1fr; }.overview-panel { padding: 1rem; }.overview-period-link { width: 100%; }.overview-period-picker select { width: 100%; }.report-grid { grid-template-columns: 1fr; }.schedule-grid { grid-template-columns: 1fr; }.admissions-assign-form { align-items: stretch; flex-direction: column; }.admissions-assign-form .primary-button { width: 100%; }.committee-list li { align-items: start; flex-direction: column; }.committee-member-actions { justify-content: space-between; width: 100%; }.committee-list-heading { align-items: start; flex-direction: column; gap: .35rem; }.applicant-insights, .document-insights { grid-template-columns: 1fr 1fr; }.applicant-insight, .document-insight { min-height: 5.4rem; padding: .75rem; }.applicant-filters, .document-filters { grid-template-columns: 1fr; }.applicant-search-field, .document-search-field { grid-column: auto; }.filter-reset-button { justify-self: stretch; }.applicant-list-heading, .document-list-heading { align-items: start; flex-direction: column; gap: .4rem; }.document-row { grid-template-columns: 1fr; align-items: start; }.document-status-pill, .document-row > a, .document-actions { grid-column: auto; justify-self: start; }.document-row > a { width: 100%; }.document-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); width: 100%; padding-left: 0; border-left: 0; }.upload-form { gap: .6rem; } }
@media (max-width: 980px) { .selection-insights { grid-template-columns: repeat(2, minmax(0, 1fr)); } .selection-filters { grid-template-columns: minmax(0, 1fr) minmax(10rem, 1fr); } .selection-filters .selection-search-field { grid-column: 1 / -1; } .selection-filters .filter-reset-button { justify-self: start; } }
@media (max-width: 820px) { .selection-page-heading { align-items: stretch; flex-direction: column; }.selection-page-heading .primary-button { width: 100%; }.selection-applicant-heading { display: grid; }.selection-rank { justify-items: center; text-align: center; }.selection-score-summary .status-pill { margin-left: 0; }.selection-criteria { grid-template-columns: 1fr; }.selection-decision-fields { grid-template-columns: 1fr 1fr; }.selection-reason-field { grid-column: 1 / -1; }.selection-decision-fields .primary-button { width: 100%; } }
@media (max-width: 560px) { .selection-insights { grid-template-columns: 1fr 1fr; }.selection-insight { min-height: 5.4rem; padding: .75rem; }.selection-list-heading { align-items: start; flex-direction: column; gap: .4rem; }.selection-filters { grid-template-columns: 1fr; }.selection-filters .selection-search-field { grid-column: auto; }.selection-filters .filter-reset-button { justify-self: stretch; }.selection-score-summary { align-items: start; flex-wrap: wrap; }.selection-score-summary .status-pill { margin-left: 0; }.selection-criterion { align-items: start; flex-direction: column; }.selection-score-action { width: 100%; }.selection-score-action input { flex: 1; width: auto; }.selection-score-action .secondary-button { flex: 0 0 auto; }.selection-decision-heading { align-items: start; flex-direction: column; }.selection-decision-fields { grid-template-columns: 1fr; }.selection-reason-field { grid-column: auto; } }
@media (max-width: 980px) { .announcement-insights { grid-template-columns: repeat(2, minmax(0, 1fr)); }.announcement-filters { grid-template-columns: minmax(0, 1fr) minmax(10rem, 1fr); }.announcement-filters .announcement-search-field { grid-column: 1 / -1; }.announcement-filters .filter-reset-button { justify-self: start; } }
@media (max-width: 820px) { .announcement-page-heading { align-items: stretch; flex-direction: column; }.announcement-date { text-align: center; }.announcement-applicant-heading { display: grid; }.announcement-result { grid-template-columns: repeat(2, minmax(0, 1fr)); }.announcement-result div:first-child { grid-column: 1 / -1; } }
@media (max-width: 560px) { .announcement-insights { grid-template-columns: 1fr 1fr; }.announcement-insight { min-height: 5.4rem; padding: .75rem; }.announcement-list-heading { align-items: start; flex-direction: column; gap: .4rem; }.announcement-filters { grid-template-columns: 1fr; }.announcement-filters .announcement-search-field { grid-column: auto; }.announcement-filters .filter-reset-button { justify-self: stretch; }.announcement-result { grid-template-columns: 1fr; }.announcement-result div:first-child { grid-column: auto; } }
@media (max-width: 980px) { .re-registration-insights { grid-template-columns: repeat(3, minmax(0, 1fr)); }.re-registration-filters { grid-template-columns: minmax(0, 1fr) minmax(12rem, 1fr); }.re-registration-filters .re-registration-search-field { grid-column: 1 / -1; }.re-registration-filters .filter-reset-button { justify-self: start; } }
@media (max-width: 820px) { .re-registration-page-heading { align-items: stretch; flex-direction: column; }.re-registration-date { text-align: center; }.re-registration-applicant-heading { display: grid; }.re-registration-details { grid-template-columns: 1fr 1fr; }.re-registration-details div:last-child { grid-column: 1 / -1; }.re-registration-form { grid-template-columns: 1fr; }.re-registration-form .primary-button { width: 100%; } }
@media (max-width: 560px) { .re-registration-insights { grid-template-columns: 1fr 1fr; }.re-registration-insight { min-height: 5.4rem; padding: .75rem; }.re-registration-list-heading { align-items: start; flex-direction: column; gap: .4rem; }.re-registration-filters { grid-template-columns: 1fr; }.re-registration-filters .re-registration-search-field { grid-column: auto; }.re-registration-filters .filter-reset-button { justify-self: stretch; }.re-registration-details { grid-template-columns: 1fr; }.re-registration-details div:last-child { grid-column: auto; } }
@media (max-width: 600px) { .admissions-page-header .admissions-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.spmb-top-bar, .spmb-subnav-bar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.admissions-header-top { display: flex; align-items: end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.period-context-inline label { display: grid; gap: .35rem; color: var(--ink-soft); font-size: .78rem; font-weight: 700; }
.period-context-inline select { min-width: min(22rem, 100%); min-height: 2.6rem; border: 1px solid var(--line-strong); border-radius: .65rem; background: var(--surface); padding: .55rem .75rem; color: var(--ink); font-size: .84rem; }
.spmb-workspace-tabs { display: inline-flex; flex-wrap: wrap; gap: .35rem; padding: .3rem; border: 1px solid var(--line); border-radius: .8rem; background: var(--surface-subtle); }
.spmb-tab-btn { border: 0; border-radius: .55rem; padding: .52rem .85rem; color: var(--ink-soft); background: transparent; font-size: .8rem; font-weight: 700; cursor: pointer; transition: background 140ms ease, color 140ms ease; }
.spmb-tab-btn:hover { color: var(--ink); background: color-mix(in srgb, var(--surface) 75%, transparent); }
.spmb-tab-btn.is-active { color: var(--primary); background: var(--surface); box-shadow: 0 1px 2px rgba(15, 23, 42, .06); }
.applicant-badge-cluster { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
.btn-compact { min-height: 2.15rem !important; padding: .38rem .7rem !important; font-size: .74rem !important; }
.nis-input-group { display: flex; align-items: center; gap: .5rem; }
.nis-input-group input { flex: 1; }
.setup-checklist-panel { display: grid; gap: 1rem; border-left: 4px solid var(--primary); }
.setup-checklist-header { display: flex; align-items: start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.setup-checklist-header h2 { margin: 0; color: var(--ink); font-size: 1.15rem; }
.setup-checklist-header p { margin: .25rem 0 0; color: var(--ink-soft); font-size: .8rem; }
.setup-checklist-actions { display: flex; align-items: center; gap: .6rem; }
.setup-checklist-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .7rem; }
.setup-check-item { display: flex; align-items: center; gap: .7rem; padding: .75rem .85rem; border: 1px solid var(--line); border-radius: .75rem; background: var(--surface-subtle); text-align: left; cursor: pointer; }
.setup-check-item:hover { border-color: var(--primary); }
.setup-check-item.is-done { border-color: color-mix(in srgb, #138a62 35%, var(--line)); background: #f0faf5; }
.setup-check-item .check-icon { display: grid; flex: 0 0 1.75rem; place-items: center; width: 1.75rem; height: 1.75rem; border-radius: 50%; color: var(--primary); background: var(--primary-soft); font-size: .74rem; font-weight: 800; }
.setup-check-item.is-done .check-icon { color: #fff; background: #138a62; }
.setup-check-item div { display: grid; min-width: 0; gap: .12rem; }
.setup-check-item strong { color: var(--ink); font-size: .78rem; }
.setup-check-item small { color: var(--ink-soft); font-size: .68rem; }
.track-card-detailed { display: grid; gap: .6rem; align-items: stretch; }
.track-card-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.track-meta-pills { display: flex; flex-wrap: wrap; gap: .4rem; padding-top: .5rem; border-top: 1px solid var(--line); }
.meta-chip { border: 1px solid var(--line); border-radius: 999px; padding: .2rem .55rem; color: var(--ink-soft); background: var(--surface-subtle); font-size: .7rem; font-weight: 600; }
.meta-chip.is-criterion { color: var(--primary); background: var(--primary-soft); border-color: color-mix(in srgb, var(--primary) 22%, var(--line)); }
.applicant-drawer-overlay { position: fixed; inset: 0; z-index: 60; display: grid; place-items: center; padding: 1rem; background: rgba(15, 23, 42, .45); backdrop-filter: blur(2px); }
.applicant-drawer { display: flex; flex-direction: column; width: min(42rem, 100%); max-height: calc(100dvh - 2rem); overflow: hidden; background: var(--surface); border: 1px solid var(--line); border-radius: .85rem; box-shadow: 0 16px 40px rgba(15, 23, 42, .2); }
.applicant-drawer-header { display: flex; align-items: start; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.35rem; border-bottom: 1px solid var(--line); background: var(--surface-subtle); }
.applicant-drawer-header h2 { margin: .15rem 0 0; color: var(--ink); font-size: 1.2rem; }
.applicant-drawer-header p { margin: .25rem 0 0; color: var(--ink-soft); font-size: .78rem; }
.applicant-drawer-body { flex: 1; min-height: 0; overflow-y: auto; display: grid; align-content: start; gap: 1.1rem; padding: 1.25rem 1.35rem; }
.drawer-section { display: grid; gap: .75rem; padding: 1rem; border: 1px solid var(--line); border-radius: .85rem; background: var(--surface); }
.drawer-section h3 { margin: 0; color: var(--ink); font-size: .92rem; }
.drawer-section-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
.drawer-meta-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem .9rem; margin: 0; }
.drawer-meta-grid div { display: grid; gap: .12rem; }
.drawer-meta-grid dt { color: var(--ink-soft); font-size: .7rem; font-weight: 700; }
.drawer-meta-grid dd { margin: 0; color: var(--ink); font-size: .82rem; font-weight: 600; }
.drawer-status-form { flex-direction: column; align-items: stretch; }
.drawer-document-row { grid-template-columns: minmax(0, 1fr) auto auto; }
.drawer-doc-actions { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
.drawer-doc-actions .revision-button { color: #b42318; }
.drawer-upload-form { flex-wrap: wrap; }
.drawer-criteria { grid-template-columns: 1fr; }
.intake-table-wrap { overflow-x: auto; border: 1px solid var(--line); border-radius: .85rem; background: var(--surface); }
.intake-table { width: 100%; border-collapse: collapse; font-size: .82rem; text-align: left; }
.intake-table th, .intake-table td { padding: .75rem .9rem; border-bottom: 1px solid var(--line); vertical-align: middle; }
.intake-table thead th { background: var(--surface-subtle); color: var(--ink-soft); font-size: .74rem; font-weight: 700; white-space: nowrap; }
.intake-table tbody tr:last-child td { border-bottom: 0; }
.intake-table tbody tr:hover { background: color-mix(in srgb, var(--primary-soft) 25%, var(--surface)); }
.intake-table .col-no { width: 3.2rem; color: var(--ink-soft); font-weight: 700; text-align: center; font-variant-numeric: tabular-nums; }
.intake-table .col-date { white-space: nowrap; color: var(--ink-soft); font-size: .78rem; font-weight: 600; text-align: center; }
.intake-table th:nth-child(4), .intake-table td:nth-child(4) { text-align: center; }
.intake-table td:nth-child(4) .applicant-badge-cluster { justify-content: center; }
.intake-table .col-actions { width: 1%; white-space: nowrap; text-align: center; }
.intake-applicant-cell { display: grid; gap: .18rem; min-width: 12rem; }
.intake-applicant-cell strong { color: var(--ink); font-size: .86rem; font-weight: 700; }
.intake-applicant-cell small { color: var(--ink-soft); font-size: .73rem; }
.intake-action-group { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; }
.icon-action-btn { display: inline-grid; place-items: center; width: 2.15rem; height: 2.15rem; border: 1px solid var(--line-strong); border-radius: .55rem; background: var(--surface); color: var(--ink-soft); cursor: pointer; transition: border-color 140ms ease, background 140ms ease, color 140ms ease; }
.icon-action-btn svg { width: 1rem; height: 1rem; }
.icon-action-btn.is-edit:hover { border-color: var(--primary); background: var(--primary-soft); color: var(--primary); }
.icon-action-btn.is-delete { border-color: #f1c9c5; background: #fff8f7; color: #b42318; }
.icon-action-btn.is-delete:hover:not(:disabled) { border-color: #b42318; background: #fdebea; }
.icon-action-btn:disabled { opacity: .45; cursor: not-allowed; }
.applicant-modal-overlay { position: fixed; inset: 0; z-index: 70; display: grid; place-items: center; padding: 1rem; background: rgba(15, 23, 42, .45); backdrop-filter: blur(2px); }
.applicant-modal { width: min(46rem, 100%); max-height: calc(100vh - 2rem); overflow-y: auto; border-radius: .95rem; box-shadow: 0 20px 45px rgba(15, 23, 42, .2); }
.applicant-modal-header { display: flex; align-items: start; justify-content: space-between; gap: 1rem; padding-bottom: .9rem; margin-bottom: 1rem; border-bottom: 1px solid var(--line); }
.applicant-modal-header h2 { margin: .15rem 0 0; color: var(--ink); font-size: 1.2rem; }
.applicant-modal-header p { margin: .25rem 0 0; color: var(--ink-soft); font-size: .78rem; }
.applicant-edit-form { margin-bottom: 0; }
@media (max-width: 900px) { .setup-checklist-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 600px) { .setup-checklist-grid, .drawer-meta-grid, .drawer-document-row { grid-template-columns: 1fr; } }
/* Consistent, restrained summary cards across SPMB workspaces. */
.admissions-page-header .admissions-summary > div,
.overview-metric, .applicant-insight, .document-insight, .selection-insight, .announcement-insight, .re-registration-insight {
    text-align: center;
    align-content: center;
    background: var(--surface);
    border: 1px solid var(--line);
    border-top: 3px solid #7952a3;
}
.admissions-page-header .admissions-summary > div:nth-child(1) { border-top-color: #3157a4; }
.admissions-page-header .admissions-summary > div:nth-child(2) { border-top-color: #7952a3; }
.admissions-page-header .admissions-summary > div:nth-child(3) { border-top-color: #138a62; }
.admissions-page-header .admissions-summary > div:nth-child(4) { border-top-color: #168782; }
.applicant-insight.is-total, .document-insight.is-total, .selection-insight.is-total,
.re-registration-insight.is-total, .announcement-insight.is-pending { background: var(--surface); }
.applicant-insight.is-total, .document-insight.is-total, .selection-insight.is-total,
.overview-metric.metric-total { border-top-color: #7952a3; }
.re-registration-insight.is-total { border-top-color: #138a62; }
.overview-metric.is-accepted, .applicant-insight.is-accepted, .document-insight.is-valid,
.selection-insight.is-decided, .announcement-insight.is-accepted { border-top-color: #138a62; }
.overview-metric.is-completed, .re-registration-insight.is-completed { border-top-color: #168782; }
.overview-metric.is-waitlisted, .applicant-insight.is-attention, .applicant-insight.is-selection_review,
.document-insight.is-attention, .announcement-insight.is-waitlisted, .announcement-insight.is-pending,
.re-registration-insight.is-pending { border-top-color: #c28a27; }
.announcement-insight.is-rejected { border-top-color: #b42318; }
</style>
