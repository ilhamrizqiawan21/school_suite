<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

type Tab = 'agenda' | 'history' | 'recap';
type JournalStatus = 'pending' | 'completed';

interface AttendanceSummary {
    present: number;
    sick: number;
    permitted: number;
    absent: number;
}

interface JournalData {
    id: number;
    topic: string;
    theme?: string | null;
    period_count?: number;
    learning_objectives?: string | null;
    notes?: string | null;
    opening_activity?: string | null;
    main_activity?: string | null;
    closing_activity?: string | null;
    follow_up?: string | null;
    attendance_notes?: string | null;
    observations?: string | null;
    reflection?: string | null;
}

interface SessionItem {
    id: number;
    teaching_assignment_id?: number | null;
    date: string;
    start_time: string;
    end_time: string;
    meeting_number?: number | null;
    status: string;
    journal_status: JournalStatus;
    journal: JournalData | null;
    attendance_summary: AttendanceSummary;
    class_group: { id: number | null; name: string };
    subject: { id: number | null; name: string; code: string };
}

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface RecapMonth {
    month_key: string;
    label: string;
    total_sessions: number;
    completed_journals: number;
    pending_journals: number;
    completion_rate: number;
    present: number;
    sick: number;
    permitted: number;
    absent: number;
}

interface RecapSummary {
    total_sessions: number;
    completed_journals: number;
    pending_journals: number;
    completion_rate: number;
    present: number;
    sick: number;
    permitted: number;
    absent: number;
}

interface JournalForm {
    topic: string;
    theme: string;
    period_count: number;
    learning_objectives: string;
    notes: string;
    opening_activity: string;
    main_activity: string;
    closing_activity: string;
    follow_up: string;
    attendance_notes: string;
    observations: string;
    reflection: string;
}

const props = defineProps<{
    active_term: { id: number; name: string; start_date: string; end_date: string } | null;
    agenda_date: string;
    focus_session_id?: number | null;
    agenda_sessions: SessionItem[];
    assignments: { id: number; class_group: { id: number | null; name: string }; subject: { id: number | null; name: string; code: string } }[];
    classes: { id: number; name: string; grade_level?: string }[];
    subjects: { id: number; name: string; code: string }[];
    journal_sessions: { data: SessionItem[]; links: PageLink[]; total: number };
    filters: {
        date: string;
        history_date?: string | null;
        class_group_id?: number | string | null;
        subject_id?: number | string | null;
        journal_status: 'all' | JournalStatus;
        history_search?: string;
        sort_order?: 'latest' | 'oldest';
    };
    recap: { summary: RecapSummary; months: RecapMonth[] };
}>();

const activeTab = ref<Tab>('agenda');
const agendaDate = ref(props.agenda_date);
const historyDate = ref(props.filters.history_date ?? '');
const historySearch = ref(props.filters.history_search ?? '');
const historySort = ref(props.filters.sort_order ?? 'latest');
const selectedClass = ref(String(props.filters.class_group_id ?? ''));
const selectedSubject = ref(String(props.filters.subject_id ?? ''));
const journalStatus = ref(props.filters.journal_status ?? 'all');
const expandedSessionId = ref<number | null>(null);
const processingSessionId = ref<number | null>(null);
const showSessionModal = ref(false);
const duplicateTemplate = ref<JournalData | null>(null);
const drafts = reactive<Record<number, JournalForm>>({});
const newSessionForm = useForm({
    teaching_assignment_id: props.assignments[0] ? String(props.assignments[0].id) : '',
    date: props.agenda_date,
    start_time: '07:00',
    end_time: '08:30',
});

const parseLocalDate = (value: string | null | undefined) => {
    if (!value) return null;

    const datePart = value.slice(0, 10);
    const parsed = new Date(`${datePart}T00:00:00`);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const formatDate = (value: string | null | undefined) => {
    const date = parseLocalDate(value);
    if (!date) return 'Tanggal tidak tersedia';

    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
};

const formatShortDate = (value: string | null | undefined) => {
    const date = parseLocalDate(value);
    if (!date) return '—';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date);
};

const ensureDraft = (session: SessionItem): JournalForm => {
    if (!drafts[session.id]) {
        drafts[session.id] = {
            topic: session.journal?.topic ?? '',
            theme: session.journal?.theme ?? '',
            period_count: session.journal?.period_count ?? 2,
            learning_objectives: session.journal?.learning_objectives ?? '',
            notes: session.journal?.notes ?? '',
            opening_activity: session.journal?.opening_activity ?? '',
            main_activity: session.journal?.main_activity ?? '',
            closing_activity: session.journal?.closing_activity ?? '',
            follow_up: session.journal?.follow_up ?? '',
            attendance_notes: session.journal?.attendance_notes ?? '',
            observations: session.journal?.observations ?? '',
            reflection: session.journal?.reflection ?? '',
        };
    }
    return drafts[session.id];
};

props.agenda_sessions.forEach((session) => ensureDraft(session));

const agendaPending = computed(() => props.agenda_sessions.filter((session) => session.journal_status === 'pending').length);
const agendaCompleted = computed(() => props.agenda_sessions.filter((session) => session.journal_status === 'completed').length);
const recapHasData = computed(() => props.recap.months.some((month) => month.total_sessions > 0));

const attendanceTotal = (session: SessionItem) => Object.values(session.attendance_summary).reduce((sum, value) => sum + value, 0);

const statusLabel = (session: SessionItem) => {
    const draft = drafts[session.id];
    if (expandedSessionId.value === session.id && draft && draft.topic.trim() && session.journal_status === 'pending') return 'Draft';
    return session.journal_status === 'completed' ? 'Selesai' : 'Belum diisi';
};

const statusClass = (session: SessionItem) => {
    if (session.status === 'cancelled') return 'status-cancelled';
    if (statusLabel(session) === 'Draft') return 'status-draft';
    return session.journal_status === 'completed' ? 'status-completed' : 'status-pending';
};

const navigate = (data: Record<string, string | number | null | undefined>) => {
    router.get('/akademik/jurnal', data, { preserveState: true, preserveScroll: true });
};

const navigateAgenda = (value = agendaDate.value) => navigate({
    date: value,
    history_date: historyDate.value || null,
    class_group_id: selectedClass.value || null,
    subject_id: selectedSubject.value || null,
    journal_status: journalStatus.value,
    history_search: historySearch.value,
    sort_order: historySort.value,
});

const shiftDate = (offset: number) => {
    const date = new Date(agendaDate.value + 'T00:00:00');
    date.setDate(date.getDate() + offset);
    agendaDate.value = date.toISOString().slice(0, 10);
    navigateAgenda();
};

const goToToday = () => {
    agendaDate.value = new Date().toISOString().slice(0, 10);
    navigateAgenda();
};

const applyHistoryFilters = () => {
    activeTab.value = 'history';
    navigateAgenda();
};

const resetHistoryFilters = () => {
    historyDate.value = '';
    selectedClass.value = '';
    selectedSubject.value = '';
    journalStatus.value = 'all';
    historySearch.value = '';
    historySort.value = 'latest';
    applyHistoryFilters();
};

const toggleJournal = (session: SessionItem) => {
    ensureDraft(session);
    expandedSessionId.value = expandedSessionId.value === session.id ? null : session.id;
};

const saveJournal = (session: SessionItem) => {
    const form = ensureDraft(session);
    processingSessionId.value = session.id;
    router.post('/akademik/jurnal', {
        lesson_session_id: session.id,
        topic: form.topic,
        theme: form.theme || null,
        period_count: form.period_count,
        learning_objectives: form.learning_objectives || null,
        notes: form.notes || null,
        opening_activity: form.opening_activity || null,
        main_activity: form.main_activity || null,
        closing_activity: form.closing_activity || null,
        follow_up: form.follow_up || null,
        attendance_notes: form.attendance_notes || null,
        observations: form.observations || null,
        reflection: form.reflection || null,
    }, {
        preserveScroll: true,
        onSuccess: () => { expandedSessionId.value = null; },
        onFinish: () => { processingSessionId.value = null; },
    });
};

const openHistorySession = (session: SessionItem) => {
    activeTab.value = 'agenda';
    agendaDate.value = session.date;
    navigateAgenda(session.date);
};

const openNewSession = (source?: SessionItem) => {
    duplicateTemplate.value = source?.journal ?? null;
    const matchingAssignment = source
        ? props.assignments.find((assignment) => assignment.id === source.teaching_assignment_id)
            ?? props.assignments.find((assignment) => assignment.class_group.id === source.class_group.id && assignment.subject.id === source.subject.id)
            ?? props.assignments[0]
        : props.assignments[0];
    newSessionForm.reset();
    newSessionForm.teaching_assignment_id = matchingAssignment ? String(matchingAssignment.id) : '';
    newSessionForm.date = source ? source.date : agendaDate.value;
    newSessionForm.start_time = source?.start_time ?? '07:00';
    newSessionForm.end_time = source?.end_time ?? '08:30';
    if (source) {
        const nextDate = new Date(source.date + 'T00:00:00');
        nextDate.setDate(nextDate.getDate() + 7);
        newSessionForm.date = nextDate.toISOString().slice(0, 10);
    }
    showSessionModal.value = true;
};

const createSession = () => {
    newSessionForm.post('/akademik/jurnal/sesi', {
        preserveScroll: true,
        onSuccess: () => {
            const createdSession = props.agenda_sessions.find((session) => session.id === props.focus_session_id);
            if (createdSession && duplicateTemplate.value) {
                drafts[createdSession.id] = {
                    topic: duplicateTemplate.value.topic,
                    theme: duplicateTemplate.value.theme ?? '',
                    period_count: duplicateTemplate.value.period_count ?? 2,
                    learning_objectives: duplicateTemplate.value.learning_objectives ?? '',
                    notes: duplicateTemplate.value.notes ?? '',
                    opening_activity: duplicateTemplate.value.opening_activity ?? '',
                    main_activity: duplicateTemplate.value.main_activity ?? '',
                    closing_activity: duplicateTemplate.value.closing_activity ?? '',
                    follow_up: duplicateTemplate.value.follow_up ?? '',
                    attendance_notes: '',
                    observations: '',
                    reflection: '',
                };
                expandedSessionId.value = createdSession.id;
            }
            showSessionModal.value = false;
            duplicateTemplate.value = null;
        },
    });
};

const deleteJournal = (session: SessionItem) => {
    if (!window.confirm(`Hapus jurnal pertemuan ${session.meeting_number} untuk ${session.subject.name} · ${session.class_group.name}?`)) return;

    router.delete(`/akademik/jurnal/${session.id}`, { preserveScroll: true });
};

const chart = computed(() => {
    const width = 760;
    const height = 240;
    const points = props.recap.months.map((month, index) => {
        const x = props.recap.months.length <= 1 ? width / 2 : index * (width / (props.recap.months.length - 1));
        const y = height - (month.completion_rate / 100) * height;
        return { x, y, label: month.label, value: month.completion_rate };
    });
    const line = points.map((point, index) => (index === 0 ? 'M ' : 'L ') + point.x + ' ' + point.y).join(' ');
    const area = line + ' L ' + width + ' ' + height + ' L 0 ' + height + ' Z';
    return { width, height, points, line, area };
});
</script>

<template>
    <AppLayout title="Jurnal Harian Guru">
        <Head title="Jurnal Harian Guru" />

        <div class="journal-page">
            <header class="journal-hero">
                <div>
                    <p class="eyebrow">Aktivitas mengajar</p>
                    <h1>Jurnal Harian Guru</h1>
                    <p class="hero-copy">Kelola agenda mengajar, presensi sesi, dan catatan pembelajaran dari satu ruang kerja.</p>
                </div>
                <div v-if="active_term" class="term-badge">
                    <span>Periode aktif</span>
                    <strong>{{ active_term.name }}</strong>
                    <small>{{ formatShortDate(active_term.start_date) }} — {{ formatShortDate(active_term.end_date) }}</small>
                </div>
                <div class="hero-actions">
                    <button class="primary-button" type="button" :disabled="!assignments.length" @click="openNewSession()">
                        + Catat pertemuan
                    </button>
                </div>
            </header>

            <nav class="journal-tabs" aria-label="Bagian jurnal">
                <button :class="{ active: activeTab === 'agenda' }" @click="activeTab = 'agenda'">Agenda hari ini</button>
                <button :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'">Riwayat jurnal</button>
                <button :class="{ active: activeTab === 'recap' }" @click="activeTab = 'recap'">Rekap aktivitas</button>
            </nav>

            <section v-if="activeTab === 'agenda'" class="journal-section">
                <div class="section-toolbar">
                    <div>
                        <p class="eyebrow">Agenda mengajar</p>
                        <h2>{{ formatDate(agendaDate) }}</h2>
                    </div>
                    <div class="date-controls">
                        <button class="icon-button" aria-label="Hari sebelumnya" @click="shiftDate(-1)">‹</button>
                        <button class="secondary-button" @click="goToToday">Hari ini</button>
                        <input v-model="agendaDate" type="date" aria-label="Pilih tanggal" @change="navigateAgenda()" />
                        <button class="icon-button" aria-label="Hari berikutnya" @click="shiftDate(1)">›</button>
                    </div>
                </div>

                <div class="agenda-summary">
                    <div><span>Sesi hari ini</span><strong>{{ agenda_sessions.length }}</strong></div>
                    <div><span>Belum diisi</span><strong class="accent-gold">{{ agendaPending }}</strong></div>
                    <div><span>Selesai</span><strong class="accent-green">{{ agendaCompleted }}</strong></div>
                </div>

                <div v-if="agenda_sessions.length" class="agenda-grid">
                    <article v-for="session in agenda_sessions" :key="session.id" class="session-card">
                        <div class="session-topline">
                            <span class="session-time">{{ session.start_time }}–{{ session.end_time }}</span>
                            <span class="status-badge" :class="statusClass(session)">{{ statusLabel(session) }}</span>
                        </div>
                        <h3>{{ session.subject.name }}</h3>
                        <p class="session-code">{{ session.subject.code }} · {{ session.class_group.name }}</p>
                        <p class="session-meta">Pertemuan {{ session.meeting_number ?? '—' }} · {{ session.journal?.period_count ?? 2 }} JP · {{ attendanceTotal(session) }} catatan presensi</p>
                        <div class="attendance-strip">
                            <span><b>{{ session.attendance_summary.present }}</b> hadir</span>
                            <span><b>{{ session.attendance_summary.sick }}</b> sakit</span>
                            <span><b>{{ session.attendance_summary.permitted }}</b> izin</span>
                            <span><b>{{ session.attendance_summary.absent }}</b> alpa</span>
                        </div>
                        <div class="session-actions">
                            <Link class="secondary-button" :href="'/akademik/presensi?session_id=' + session.id + '&date=' + session.date">Presensi sesi</Link>
                            <button class="primary-button" @click="toggleJournal(session)">
                                {{ session.journal_status === 'completed' ? 'Edit jurnal' : 'Isi jurnal' }}
                            </button>
                        </div>
                        <form v-if="expandedSessionId === session.id" class="journal-form" @submit.prevent="saveJournal(session)">
                            <fieldset class="form-section">
                                <legend>Materi dan tujuan</legend>
                                <label>Materi pokok
                                    <textarea v-model="ensureDraft(session).topic" required rows="2" placeholder="Materi yang dibahas pada pertemuan ini" />
                                </label>
                                <div class="form-grid">
                                    <label>Topik / tema (opsional)
                                        <input v-model="ensureDraft(session).theme" placeholder="Contoh: Panca Cinta" />
                                    </label>
                                    <label>Alokasi waktu (JP)
                                        <input v-model.number="ensureDraft(session).period_count" type="number" min="1" max="10" required />
                                    </label>
                                </div>
                                <label>Tujuan pembelajaran
                                    <textarea v-model="ensureDraft(session).learning_objectives" rows="3" placeholder="Kompetensi yang diharapkan dicapai siswa" />
                                </label>
                            </fieldset>
                            <fieldset class="form-section">
                                <legend>Kegiatan pembelajaran</legend>
                                <label>Kegiatan pendahuluan
                                    <textarea v-model="ensureDraft(session).opening_activity" rows="2" placeholder="Pembukaan, apersepsi, dan persiapan belajar" />
                                </label>
                                <label>Kegiatan inti
                                    <textarea v-model="ensureDraft(session).main_activity" rows="3" placeholder="Aktivitas utama dan metode pembelajaran" />
                                </label>
                                <label>Kegiatan penutup
                                    <textarea v-model="ensureDraft(session).closing_activity" rows="2" placeholder="Rangkuman, refleksi bersama, dan penutup" />
                                </label>
                            </fieldset>
                            <fieldset class="form-section">
                                <legend>Kehadiran dan refleksi</legend>
                                <div class="attendance-strip">
                                    <span><b>{{ session.attendance_summary.present }}</b> hadir</span>
                                    <span><b>{{ session.attendance_summary.sick }}</b> sakit</span>
                                    <span><b>{{ session.attendance_summary.permitted }}</b> izin</span>
                                    <span><b>{{ session.attendance_summary.absent }}</b> alpa</span>
                                    <Link class="secondary-button" :href="'/akademik/presensi?session_id=' + session.id + '&date=' + session.date">Kelola presensi</Link>
                                </div>
                                <label>Keterangan presensi (opsional)
                                    <textarea v-model="ensureDraft(session).attendance_notes" rows="2" placeholder="Catatan siswa sakit, izin, atau alpa" />
                                </label>
                                <label>Hasil pengamatan
                                    <textarea v-model="ensureDraft(session).observations" rows="2" placeholder="Partisipasi dan pemahaman siswa selama belajar" />
                                </label>
                                <label>Refleksi guru
                                    <textarea v-model="ensureDraft(session).reflection" rows="2" placeholder="Evaluasi jalannya pembelajaran" />
                                </label>
                                <label>Tindak lanjut
                                    <textarea v-model="ensureDraft(session).follow_up" rows="2" placeholder="Penguatan, remedial, atau rencana pertemuan berikutnya" />
                                </label>
                                <label>Catatan tambahan
                                    <textarea v-model="ensureDraft(session).notes" rows="2" placeholder="Catatan lain yang perlu disimpan" />
                                </label>
                            </fieldset>
                            <div class="form-footer">
                                <span class="draft-note">Simpan sebagai jurnal sesi ini</span>
                                <button class="primary-button" type="submit" :disabled="processingSessionId === session.id">
                                    {{ processingSessionId === session.id ? 'Menyimpan…' : 'Simpan jurnal' }}
                                </button>
                            </div>
                        </form>
                    </article>
                </div>
                <div v-else class="empty-state">
                    <div class="empty-icon">—</div>
                    <h3>Belum ada pertemuan di tanggal ini</h3>
                    <p>Pilih tanggal lain, atau catat pertemuan dari penugasan mapel dan rombel Anda.</p>
                    <div class="empty-actions">
                        <button class="primary-button" type="button" :disabled="!assignments.length" @click="openNewSession()">Catat pertemuan</button>
                        <Link class="secondary-button" href="/akademik/jadwal">Lihat jadwal pelajaran</Link>
                    </div>
                </div>
            </section>

            <section v-else-if="activeTab === 'history'" class="journal-section">
                <div class="section-toolbar">
                    <div>
                        <p class="eyebrow">Catatan tersimpan</p>
                        <h2>Riwayat jurnal <span class="count-label">{{ journal_sessions.total }} sesi</span></h2>
                    </div>
                </div>
                <div class="filter-panel">
                    <label>Cari jurnal<input v-model="historySearch" type="search" placeholder="Materi, mapel, rombel..." @keyup.enter="applyHistoryFilters" /></label>
                    <label>Tanggal<input v-model="historyDate" type="date" /></label>
                    <label>Rombel<select v-model="selectedClass"><option value="">Semua rombel</option><option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
                    <label>Mata pelajaran<select v-model="selectedSubject"><option value="">Semua mata pelajaran</option><option v-for="item in subjects" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
                    <label>Status<select v-model="journalStatus"><option value="all">Semua status</option><option value="completed">Selesai</option><option value="pending">Belum diisi</option></select></label>
                    <label>Urutan<select v-model="historySort"><option value="latest">Terbaru</option><option value="oldest">Terlama</option></select></label>
                    <div class="filter-actions"><button class="secondary-button" @click="resetHistoryFilters">Reset</button><button class="primary-button" @click="applyHistoryFilters">Terapkan</button></div>
                </div>
                <div v-if="journal_sessions.data.length" class="history-list">
                    <article v-for="session in journal_sessions.data" :key="session.id" class="history-row">
                        <div class="history-date"><strong>{{ formatShortDate(session.date) }}</strong><span>{{ session.start_time }}–{{ session.end_time }}</span></div>
                        <div class="history-main"><h3>{{ session.subject.name }}</h3><p>{{ session.class_group.name }} · {{ session.subject.code }} · Pertemuan {{ session.meeting_number ?? '—' }}</p><p v-if="session.journal" class="topic">{{ session.journal.topic }}</p><p v-else class="muted">Jurnal belum diisi</p></div>
                        <span class="status-badge" :class="statusClass(session)">{{ session.journal_status === 'completed' ? 'Selesai' : 'Belum diisi' }}</span>
                        <div class="history-actions">
                            <button class="secondary-button" @click="openHistorySession(session)">{{ session.journal ? 'Buka / edit' : 'Isi jurnal' }}</button>
                            <button v-if="session.journal" class="secondary-button" @click="openNewSession(session)">Duplikat</button>
                            <a v-if="session.journal" class="secondary-button" :href="`/akademik/jurnal/${session.id}/word`" :download="`Jurnal_${session.id}.docx`">Unduh Word</a>
                            <a v-if="session.journal" class="secondary-button" :href="`/akademik/jurnal/${session.id}/pdf`" :download="`Jurnal_${session.id}.pdf`">Unduh PDF</a>
                            <button v-if="session.journal" class="secondary-button danger-action" type="button" @click="deleteJournal(session)">Hapus</button>
                        </div>
                    </article>
                </div>
                <div v-else class="empty-state compact"><h3>Riwayat belum tersedia</h3><p>Gunakan filter lain atau isi jurnal dari agenda hari ini.</p></div>
                <div v-if="journal_sessions.links.length > 3" class="pagination">
                    <Link v-for="link in journal_sessions.links" :key="link.label" :href="link.url || '#'" :class="{ active: link.active, disabled: !link.url }" v-html="link.label" />
                </div>
            </section>

            <section v-else class="journal-section">
                <div class="section-toolbar">
                    <div><p class="eyebrow">Performa periode aktif</p><h2>Rekap aktivitas</h2></div>
                </div>
                <div class="recap-metrics">
                    <div><span>Total sesi mengajar</span><strong>{{ recap.summary.total_sessions }}</strong><small>Periode aktif</small></div>
                    <div><span>Jurnal selesai</span><strong>{{ recap.summary.completed_journals }}</strong><small>{{ recap.summary.completion_rate }}% kelengkapan</small></div>
                    <div><span>Belum diisi</span><strong class="accent-gold">{{ recap.summary.pending_journals }}</strong><small>Perlu ditindaklanjuti</small></div>
                    <div><span>Total hadir</span><strong class="accent-green">{{ recap.summary.present }}</strong><small>{{ recap.summary.sick }} sakit · {{ recap.summary.absent }} alpa</small></div>
                </div>
                <div class="chart-card">
                    <div class="card-heading"><div><p class="eyebrow">Tren bulanan</p><h3>Kelengkapan jurnal</h3></div><strong>{{ recap.summary.completion_rate }}%</strong></div>
                    <div v-if="recapHasData" class="chart-wrap">
                        <svg :viewBox="'0 0 ' + chart.width + ' ' + chart.height" role="img" aria-label="Grafik kelengkapan jurnal per bulan">
                            <path class="chart-area" :d="chart.area" />
                            <path class="chart-line" :d="chart.line" />
                            <circle v-for="point in chart.points" :key="point.label" class="chart-point" :cx="point.x" :cy="point.y" r="4" />
                        </svg>
                        <div class="chart-labels"><span v-for="month in recap.months" :key="month.month_key">{{ month.label }}</span></div>
                    </div>
                    <div v-else class="empty-state compact"><h3>Belum ada data rekap</h3><p>Data akan muncul setelah ada sesi mengajar dan jurnal.</p></div>
                </div>
                <div class="recap-table-wrap">
                    <table><thead><tr><th>Bulan</th><th>Sesi</th><th>Selesai</th><th>Belum diisi</th><th>Kelengkapan</th><th>Hadir</th></tr></thead><tbody><tr v-for="month in recap.months" :key="month.month_key"><td><strong>{{ month.label }}</strong></td><td>{{ month.total_sessions }}</td><td>{{ month.completed_journals }}</td><td>{{ month.pending_journals }}</td><td><span class="progress-value">{{ month.completion_rate }}%</span></td><td>{{ month.present }}</td></tr></tbody></table>
                </div>
            </section>

            <div v-if="showSessionModal" class="session-modal-backdrop" @click.self="showSessionModal = false">
                <form class="session-modal" @submit.prevent="createSession">
                    <div>
                        <p class="eyebrow">Pertemuan baru</p>
                        <h2>{{ duplicateTemplate ? 'Duplikat jurnal ke sesi baru' : 'Catat pertemuan' }}</h2>
                        <p class="hero-copy">Pilih penugasan dan waktu pertemuan. Sesi ini akan masuk agenda dan dapat diisi jurnal lengkap.</p>
                    </div>
                    <label>Mapel dan rombel
                        <select v-model="newSessionForm.teaching_assignment_id" required>
                            <option v-for="assignment in assignments" :key="assignment.id" :value="String(assignment.id)">
                                {{ assignment.subject.name }} · {{ assignment.class_group.name }}
                            </option>
                        </select>
                    </label>
                    <div class="form-grid">
                        <label>Tanggal
                            <input v-model="newSessionForm.date" type="date" required />
                        </label>
                        <label>Mulai
                            <input v-model="newSessionForm.start_time" type="time" required />
                        </label>
                        <label>Selesai
                            <input v-model="newSessionForm.end_time" type="time" required />
                        </label>
                    </div>
                    <p v-if="newSessionForm.errors.teaching_assignment_id || newSessionForm.errors.date" class="form-error">
                        {{ newSessionForm.errors.teaching_assignment_id || newSessionForm.errors.date }}
                    </p>
                    <div class="form-footer">
                        <button class="secondary-button" type="button" @click="showSessionModal = false">Batal</button>
                        <button class="primary-button" type="submit" :disabled="newSessionForm.processing || !assignments.length">
                            {{ newSessionForm.processing ? 'Menyimpan…' : 'Buat sesi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.journal-page { max-width: 1440px; margin: 0 auto; padding: 32px clamp(20px, 4vw, 56px) 64px; color: var(--ink); }
.journal-hero, .journal-section, .chart-card, .recap-table-wrap { background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-sm); }
.journal-hero { display: flex; justify-content: space-between; gap: 28px; align-items: flex-end; padding: clamp(24px, 4vw, 42px); border-radius: 24px; background: linear-gradient(135deg, var(--surface), var(--primary-soft)); }
.eyebrow { margin: 0 0 8px; color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
h1, h2, h3, p { margin-top: 0; }
h1 { margin-bottom: 12px; font-size: clamp(30px, 4vw, 48px); letter-spacing: -.04em; }
h2 { margin-bottom: 0; font-size: clamp(22px, 3vw, 32px); letter-spacing: -.03em; }
h3 { margin-bottom: 6px; font-size: 17px; }
.hero-copy, .session-meta, .history-main p, .empty-state p { color: var(--ink-soft); }
.hero-copy { max-width: 580px; margin-bottom: 0; line-height: 1.7; }
.term-badge { min-width: 190px; padding: 16px 18px; border: 1px solid var(--line); border-radius: 16px; background: var(--surface); }
.term-badge span, .term-badge small { display: block; color: var(--ink-soft); font-size: 12px; }
.term-badge strong { display: block; margin: 4px 0; }
.journal-tabs { display: flex; gap: 8px; margin: 24px 0; padding: 6px; border-bottom: 1px solid var(--line); }
.journal-tabs button { border: 0; padding: 12px 16px; border-radius: 10px; color: var(--ink-soft); background: transparent; font: inherit; font-weight: 700; cursor: pointer; }
.journal-tabs button.active { color: var(--primary); background: var(--primary-soft); }
.journal-section { padding: clamp(20px, 3vw, 32px); border-radius: 20px; }
.journal-hero { align-items: center; flex-wrap: wrap; }
.hero-actions, .empty-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
.form-section { display: grid; gap: 13px; min-width: 0; margin: 0; padding: 16px; border: 1px solid var(--line); border-radius: 14px; }
.form-section legend { padding: 0 7px; color: var(--ink); font-size: 13px; font-weight: 800; }
.session-modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 18px; background: color-mix(in srgb, var(--canvas) 64%, #07111f 36%); backdrop-filter: blur(5px); }
.session-modal { display: grid; gap: 16px; width: min(620px, 100%); max-height: min(90vh, 760px); overflow: auto; padding: 24px; border: 1px solid var(--line); border-radius: 18px; color: var(--ink); background: var(--surface); box-shadow: var(--shadow-md); }
.session-modal h2 { margin: 0 0 8px; color: var(--ink); }
.session-modal > label, .session-modal .form-grid label { display: grid; gap: 6px; color: var(--ink-soft); font-size: 12px; font-weight: 700; }
.session-modal .form-footer { justify-content: flex-end; }
.session-modal .hero-copy { margin-bottom: 0; }
.section-toolbar { display: flex; justify-content: space-between; gap: 20px; align-items: center; margin-bottom: 24px; }
.date-controls, .session-actions, .form-footer, .filter-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
button, input, select, textarea { font: inherit; }
button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible, a:focus-visible { outline: 3px solid color-mix(in srgb, var(--primary) 40%, transparent); outline-offset: 2px; }
.icon-button, .secondary-button, .primary-button { border: 1px solid var(--line-strong); border-radius: 10px; padding: 10px 14px; font-weight: 700; cursor: pointer; text-decoration: none; }
.icon-button { width: 40px; padding: 9px; font-size: 18px; color: var(--ink); background: var(--surface-subtle); }
.secondary-button { color: var(--ink); background: var(--surface-subtle); }
.primary-button { color: #fff; border-color: var(--primary); background: var(--primary); }
.primary-button:disabled { opacity: .6; cursor: wait; }
input, select, textarea { width: 100%; border: 1px solid var(--line-strong); border-radius: 10px; padding: 10px 12px; color: var(--ink); background: var(--surface); }
.date-controls input { width: auto; }
.agenda-summary, .recap-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px; }
.agenda-summary > div, .recap-metrics > div { padding: 16px; border: 1px solid var(--line); border-radius: 14px; background: var(--surface-subtle); }
.agenda-summary span, .recap-metrics span, .recap-metrics small { display: block; color: var(--ink-soft); font-size: 12px; }
.agenda-summary strong, .recap-metrics strong { display: block; margin-top: 5px; font-size: 26px; }
.accent-gold { color: var(--gold); }.accent-green { color: var(--green); }
.agenda-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.session-card { padding: 20px; border: 1px solid var(--line); border-radius: 16px; background: var(--surface-subtle); }
.session-topline, .card-heading { display: flex; justify-content: space-between; gap: 10px; align-items: center; }
.session-time { color: var(--primary); font-weight: 800; }
.session-card h3 { margin-top: 18px; font-size: 21px; }
.session-code { margin-bottom: 12px; color: var(--primary); font-weight: 700; }
.session-meta { font-size: 13px; }
.status-badge { display: inline-flex; border-radius: 999px; padding: 5px 10px; font-size: 11px; font-weight: 800; }
.status-completed { color: var(--green); background: var(--green-soft); }.status-pending { color: var(--gold); background: var(--gold-soft); }.status-draft { color: var(--violet); background: color-mix(in srgb, var(--violet) 14%, transparent); }.status-cancelled { color: var(--red); background: var(--red-soft); }
.attendance-strip { display: flex; gap: 12px; flex-wrap: wrap; margin: 18px 0; padding: 10px 0; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); color: var(--ink-soft); font-size: 12px; }
.attendance-strip b { color: var(--ink); }
.journal-form { display: grid; gap: 13px; margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--line); }
.journal-form label, .filter-panel label { display: grid; gap: 6px; color: var(--ink-soft); font-size: 12px; font-weight: 700; }
.form-footer { justify-content: space-between; }.draft-note { color: var(--ink-faint); font-size: 12px; }
.empty-state { display: grid; justify-items: center; padding: 56px 20px; text-align: center; }.empty-state.compact { padding: 30px 20px; }.empty-icon { display: grid; place-items: center; width: 48px; height: 48px; margin-bottom: 16px; border-radius: 50%; color: var(--primary); background: var(--primary-soft); font-size: 28px; }
.filter-panel { display: grid; grid-template-columns: 1.1fr 1fr 1fr 1fr auto; gap: 10px; align-items: end; margin-bottom: 20px; padding: 16px; border-radius: 14px; background: var(--surface-subtle); }
.history-list { display: grid; gap: 10px; }.history-row { display: grid; grid-template-columns: 130px minmax(0, 1fr) auto; gap: 12px 18px; align-items: center; padding: 16px; border: 1px solid var(--line); border-radius: 14px; }.history-date { display: grid; gap: 4px; }.history-date span, .history-main p { margin: 0; color: var(--ink-soft); font-size: 12px; }.history-main h3 { margin-bottom: 3px; }.history-main .topic { margin-top: 8px; color: var(--ink); font-weight: 600; }.muted { color: var(--ink-faint) !important; }.history-actions { grid-column: 2 / -1; display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 7px; }.history-actions .secondary-button { padding: 8px 10px; font-size: 11px; }
.danger-action { color: var(--red); border-color: color-mix(in srgb, var(--red) 28%, var(--line)); }
.pagination { display: flex; gap: 6px; justify-content: center; margin-top: 22px; }.pagination a { padding: 8px 11px; border: 1px solid var(--line); border-radius: 8px; color: var(--ink); text-decoration: none; }.pagination a.active { color: #fff; border-color: var(--primary); background: var(--primary); }.pagination a.disabled { opacity: .45; pointer-events: none; }
.recap-metrics { grid-template-columns: repeat(4, 1fr); }.chart-card { margin-bottom: 20px; padding: 22px; border-radius: 16px; }.card-heading strong { color: var(--primary); font-size: 24px; }.chart-wrap { margin-top: 20px; overflow-x: auto; }.chart-wrap svg { display: block; min-width: 620px; width: 100%; height: 240px; overflow: visible; }.chart-area { fill: var(--primary-soft); }.chart-line { fill: none; stroke: var(--primary); stroke-width: 4; stroke-linecap: round; stroke-linejoin: round; }.chart-point { fill: var(--surface); stroke: var(--primary); stroke-width: 3; }.chart-labels { display: flex; justify-content: space-between; min-width: 620px; margin-top: 8px; color: var(--ink-soft); font-size: 11px; }
.recap-table-wrap { overflow-x: auto; border-radius: 16px; }table { width: 100%; border-collapse: collapse; }th, td { padding: 15px 16px; border-bottom: 1px solid var(--line); text-align: left; white-space: nowrap; }th { color: var(--ink-soft); background: var(--surface-subtle); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; }td { color: var(--ink); }.progress-value { color: var(--primary); font-weight: 800; }
@media (max-width: 900px) { .agenda-grid, .filter-panel { grid-template-columns: 1fr 1fr; }.filter-actions { grid-column: 1 / -1; justify-content: flex-end; }.recap-metrics { grid-template-columns: repeat(2, 1fr); }.history-row { grid-template-columns: 110px 1fr auto; }.history-actions { grid-column: 2 / -1; justify-content: flex-start; } }
@media (max-width: 600px) { .journal-page { padding: 20px 14px 40px; }.journal-hero, .section-toolbar { align-items: flex-start; flex-direction: column; }.term-badge { width: 100%; }.journal-tabs { overflow-x: auto; }.journal-tabs button { white-space: nowrap; }.date-controls { width: 100%; }.date-controls input { flex: 1; }.agenda-summary, .recap-metrics, .agenda-grid, .filter-panel, .form-section .form-grid { grid-template-columns: 1fr; }.history-row { grid-template-columns: 1fr; gap: 10px; }.history-actions { grid-column: auto; justify-content: flex-start; }.session-actions .secondary-button, .session-actions .primary-button { flex: 1; text-align: center; }.form-footer { align-items: stretch; flex-direction: column; }.form-footer .primary-button { width: 100%; }.empty-actions, .hero-actions { width: 100%; }.empty-actions > *, .hero-actions > * { flex: 1 1 100%; } }
</style>
