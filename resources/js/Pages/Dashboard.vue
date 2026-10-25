<script setup lang="ts">
import AppLayout from '../layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface DashboardStats {
    modules_ready: number;
    active_period: string;
    framework_version: string;
    role_metrics?: Record<string, number>;
}

interface DashboardPageProps {
    stats?: DashboardStats;
    auth?: { user?: { name: string; role?: string; role_label?: string } };
    activePeriod?: { label?: string };
    [key: string]: unknown;
}

const props = defineProps<{ stats?: DashboardStats }>();
const page = usePage<DashboardPageProps>();
const user = computed(() => page.props.auth?.user);
const userName = computed(() => user.value?.name ?? 'Rekan Sekolah');
const firstName = computed(() => userName.value.split(' ')[0]);
const role = computed(() => user.value?.role ?? 'guest');
const roleLabel = computed(() => user.value?.role_label ?? 'Pengguna');
const activePeriod = computed(() => page.props.activePeriod?.label ?? props.stats?.active_period ?? 'Belum ada periode aktif');
const roleMetrics = computed(() => props.stats?.role_metrics ?? {});
const isAdmin = computed(() => ['admin', 'operator'].includes(role.value));
const isPrincipal = computed(() => role.value === 'principal');
const isTeacher = computed(() => ['teacher', 'homeroom'].includes(role.value));
const isStudent = computed(() => role.value === 'student');

const adminActions = [
    { title: 'Kelola pengguna', description: 'Atur akun, role, dan status akses warga sekolah.', href: '/pengaturan/pengguna', icon: 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m6-10a4 4 0 100-8 4 4 0 000 8zm14 10v-2a3 3 0 00-3-3.87M16 3.13a4 4 0 010 7.75' },
    { title: 'Data akademik', description: 'Kelola siswa, kelas, mata pelajaran, dan periode.', href: '/akademik/siswa', icon: 'M4 5.5A2.5 2.5 0 016.5 3h11A2.5 2.5 0 0120 5.5v13a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 18.5v-13zM8 7h8M8 11h8M8 15h5' },
    { title: 'Komunikasi sekolah', description: 'Terbitkan pengumuman dan agenda untuk warga sekolah.', href: '/komunikasi', icon: 'M4 5h16v11H8l-4 4V5zm4 4h8m-8 3h5' },
    { title: 'Pengaturan sekolah', description: 'Periksa identitas dan konfigurasi sekolah.', href: '/pengaturan/sekolah', icon: 'M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM19.4 15a1.7 1.7 0 00.34 1.88l.06.06-1.9 1.9-.06-.06a1.7 1.7 0 00-1.88-.34 1.7 1.7 0 00-1.03 1.56V20h-2.68v-.09a1.7 1.7 0 00-1.03-1.56 1.7 1.7 0 00-1.88.34l-.06.06-1.9-1.9.06-.06A1.7 1.7 0 007.8 15a1.7 1.7 0 00-1.56-1.03H6v-2.68h.24A1.7 1.7 0 007.8 10a1.7 1.7 0 00-.34-1.88L7.4 8.06l1.9-1.9.06.06a1.7 1.7 0 001.88.34 1.7 1.7 0 001.88-.34l.06-.06 1.9 1.9-.06.06A1.7 1.7 0 0019.4 15z' },
];
const teacherActions = [
    { title: 'Input nilai', description: 'Lengkapi capaian belajar dan remedial kelas Anda.', href: '/penilaian/skor', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-2-2a2 2 0 00-2 2v2h6V5a2 2 0 00-2-2h-2zm-1 9h4m-4 4h4' },
    { title: 'Presensi siswa', description: 'Catat kehadiran dan lihat rekap pertemuan.', href: '/akademik/presensi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0a2 2 0 104 0 2 2 0 00-4 0zm-1 9 2 2 4-4' },
    { title: 'Bank soal', description: 'Susun soal, paket ujian, dan berbagi dengan rekan guru.', href: '/penilaian/bank-soal', icon: 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h5' },
    { title: 'Ujian', description: 'Buat ujian dan pantau hasil pengerjaan siswa.', href: '/ujian', icon: 'M6 3h12v18H6zM9 7h6M9 11h6M9 15h4' },
];
const principalActions = [
    { title: 'Lihat laporan sekolah', description: 'Pantau kehadiran, capaian, dan ringkasan akademik sekolah.', href: '/penilaian/laporan', icon: 'M4 19V5m0 14h16M8 16v-5m4 5V7m4 9v-8' },
    { title: 'Komunikasi sekolah', description: 'Tinjau pengumuman dan agenda yang diterima warga sekolah.', href: '/komunikasi', icon: 'M4 5h16v11H8l-4 4V5zm4 4h8m-8 3h5' },
    { title: 'Data akademik', description: 'Buka data siswa, kelas, dan periode akademik.', href: '/akademik/siswa', icon: 'M4 5.5A2.5 2.5 0 016.5 3h11A2.5 2.5 0 0120 5.5v13a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 18.5v-13zM8 7h8M8 11h8M8 15h5' },
];
const studentActions = [
    { title: 'Lihat ujian', description: 'Periksa ujian yang akan datang dan hasil pengerjaan.', href: '/ujian', icon: 'M6 3h12v18H6zM9 7h6M9 11h6M9 15h4' },
    { title: 'Lihat nilai', description: 'Tinjau nilai yang sudah tercatat pada periode aktif.', href: '/penilaian/rapor', icon: 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h5' },
    { title: 'Presensi saya', description: 'Periksa riwayat kehadiran dan catatan presensi.', href: '/akademik/presensi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0a2 2 0 104 0 2 2 0 00-4 0zm-1 9 2 2 4-4' },
];
const fallbackActions = [
    { title: 'Data siswa', description: 'Kelola identitas peserta didik dan penempatan kelas.', href: '/akademik/siswa', icon: 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m6-10a4 4 0 100-8 4 4 0 000 8' },
    { title: 'Presensi siswa', description: 'Catat kehadiran dan tinjau rekap kelas.', href: '/akademik/presensi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0a2 2 0 104 0' },
    { title: 'Input nilai', description: 'Isi capaian belajar sesuai skema penilaian aktif.', href: '/penilaian/skor', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-2-2a2 2 0 00-2 2v2h6V5a2 2 0 00-2-2h-2' },
];

const actions = computed(() => isAdmin.value ? adminActions : isPrincipal.value ? principalActions : isTeacher.value ? teacherActions : isStudent.value ? studentActions : fallbackActions);
const primaryAction = computed(() => actions.value[0]);
const secondaryActions = computed(() => actions.value.slice(1));
const focusLabel = computed(() => isAdmin.value ? 'Operasional sekolah' : isPrincipal.value ? 'Kinerja sekolah' : isTeacher.value ? 'Ruang mengajar' : isStudent.value ? 'Perjalanan belajar' : 'Ruang kerja sekolah');
const focusTitle = computed(() => isAdmin.value ? 'Jaga sekolah tetap bergerak.' : isPrincipal.value ? 'Lihat sekolah dari gambaran utuh.' : isTeacher.value ? 'Mulai dari pekerjaan yang paling penting.' : isStudent.value ? 'Belajar dengan arah yang lebih jelas.' : 'Semua pekerjaan sekolah, dari satu tempat.');
const focusCopy = computed(() => isAdmin.value ? 'Pantau warga sekolah, komunikasi, dan data akademik tanpa berpindah-pindah konteks.' : isPrincipal.value ? 'Pantau denyut akademik, komunikasi, dan kesiapan sekolah dari satu ruang pengambilan keputusan.' : isTeacher.value ? 'Kelola penilaian, presensi, bank soal, dan ujian dari alur kerja yang lebih singkat.' : isStudent.value ? 'Lihat ujian, nilai, dan presensi yang membentuk perjalanan belajar Anda.' : 'Akses modul yang tersedia sesuai hak akses akun Anda.');
const focusMetric = computed(() => isAdmin.value || isPrincipal.value ? roleMetrics.value.students : isTeacher.value ? roleMetrics.value.teaching_assignments : isStudent.value ? roleMetrics.value.active_enrollments : props.stats?.modules_ready);
const focusMetricLabel = computed(() => isAdmin.value || isPrincipal.value ? 'siswa aktif' : isTeacher.value ? 'kelas diampu' : isStudent.value ? 'kelas aktif' : 'modul tersedia');
const metricItems = computed(() => {
    if (isAdmin.value) return [
        { label: 'Siswa aktif', value: roleMetrics.value.students, note: 'terdaftar di sekolah' },
        { label: 'Pengguna aktif', value: roleMetrics.value.active_users, note: 'akun dengan akses' },
        { label: 'Pengumuman', value: roleMetrics.value.recent_announcements, note: '30 hari terakhir' },
        { label: 'Ujian aktif', value: roleMetrics.value.active_exams, note: 'sedang berjalan' },
    ];
    if (isPrincipal.value) return [
        { label: 'Siswa aktif', value: roleMetrics.value.students, note: 'terdaftar di sekolah' },
        { label: 'Guru aktif', value: roleMetrics.value.teachers, note: 'tenaga pengajar' },
        { label: 'Ujian aktif', value: roleMetrics.value.active_exams, note: 'sedang berjalan' },
        { label: 'Pengumuman', value: roleMetrics.value.recent_announcements, note: '30 hari terakhir' },
    ];
    if (isTeacher.value) return [
        { label: 'Kelas diampu', value: roleMetrics.value.teaching_assignments, note: 'periode aktif' },
        { label: 'Soal aktif', value: roleMetrics.value.active_questions, note: 'di bank soal' },
        { label: 'Ujian mendatang', value: roleMetrics.value.upcoming_exams, note: 'draft dan terbit' },
        { label: 'Nilai tersimpan', value: roleMetrics.value.recorded_scores, note: 'rekaman Anda' },
    ];
    if (isStudent.value) return [
        { label: 'Kelas aktif', value: roleMetrics.value.active_enrollments, note: 'periode berjalan' },
        { label: 'Ujian mendatang', value: roleMetrics.value.upcoming_exams, note: 'siap dikerjakan' },
        { label: 'Ujian selesai', value: roleMetrics.value.completed_exams, note: 'sudah dikumpulkan' },
        { label: 'Nilai tercatat', value: roleMetrics.value.recorded_scores, note: 'periode aktif' },
    ];
    return [
        { label: 'Periode aktif', value: activePeriod.value, note: 'konteks kerja saat ini' },
        { label: 'Modul tersedia', value: props.stats?.modules_ready, note: 'siap digunakan' },
        { label: 'Status akses', value: 'Aktif', note: roleLabel.value },
    ];
});
</script>

<template>
    <AppLayout title="Beranda">
        <section class="view dashboard-view">
            <header class="dashboard-intro">
                <div class="intro-kicker"><span class="live-pip" />{{ roleLabel }} <span class="slash">/</span> {{ activePeriod }}</div>
                <div class="intro-row">
                    <div><h1>{{ firstName }}, ini pusat kerjamu.</h1><p>{{ focusCopy }}</p></div>
                    <div class="date-stamp"><strong>25</strong><span>Okt<br>2026</span></div>
                </div>
            </header>

            <div class="command-layout">
                <article class="focus-panel" :class="{ 'teacher-focus': isTeacher, 'principal-focus': isPrincipal, 'student-focus': isStudent }">
                    <div class="focus-meta"><span>{{ focusLabel }}</span><span class="focus-status"><i /> Sistem aktif</span></div>
                    <div class="focus-content"><span class="focus-number">{{ focusMetric ?? '—' }}</span><span class="focus-number-label">{{ focusMetricLabel }}</span><h2>{{ focusTitle }}</h2><p>{{ focusCopy }}</p><Link :href="primaryAction.href" class="focus-action">{{ primaryAction.title }} <span aria-hidden="true">↗</span></Link></div>
                    <div class="focus-orbit orbit-one" /><div class="focus-orbit orbit-two" />
                </article>
                <aside class="insight-panel">
                    <div class="insight-heading"><div><span class="eyebrow">Fokus hari ini</span><h2>{{ isAdmin ? 'Kesiapan operasional' : isPrincipal ? 'Kinerja sekolah' : isTeacher ? 'Ruang mengajar' : isStudent ? 'Agenda belajar' : 'Ringkasan akses' }}</h2></div><span class="signal">01</span></div>
                    <div class="signal-line"><span class="signal-dot" /><div><strong>{{ primaryAction.title }}</strong><p>{{ primaryAction.description }}</p></div></div>
                    <div class="insight-rule" />
                    <div class="insight-footer"><span>Periode</span><strong>{{ activePeriod }}</strong></div>
                </aside>
            </div>

            <section class="metrics-strip" aria-label="Ringkasan metrik">
                <div v-for="metric in metricItems" :key="metric.label" class="metric-item"><span>{{ metric.label }}</span><strong>{{ metric.value ?? '—' }}</strong><small>{{ metric.note }}</small></div>
            </section>

            <section class="workflow-section">
                <div class="section-heading"><div><span class="eyebrow">Alur kerja</span><h2>{{ isAdmin ? 'Pekerjaan administrasi' : isPrincipal ? 'Pantauan pimpinan' : isTeacher ? 'Pekerjaan mengajar' : isStudent ? 'Perjalanan belajar' : 'Akses cepat' }}</h2></div><span class="workflow-count">{{ secondaryActions.length }} langkah lanjutan</span></div>
                <div class="workflow-list">
                    <Link v-for="(action, index) in secondaryActions" :key="action.href" :href="action.href" class="workflow-row">
                        <span class="workflow-index">{{ String(index + 2).padStart(2, '0') }}</span><span class="workflow-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="action.icon" /></svg></span><span class="workflow-copy"><strong>{{ action.title }}</strong><span>{{ action.description }}</span></span><span class="workflow-arrow" aria-hidden="true">↗</span>
                    </Link>
                </div>
            </section>

            <footer class="dashboard-footer"><span class="footer-mark">S</span><span>School Suite</span><span class="footer-line" /><span>Ruang kerja yang mengikuti peranmu.</span></footer>
        </section>
    </AppLayout>
</template>

<style scoped>
.dashboard-view { max-width: 1180px; color: #1a2a3e; }
.dashboard-intro { padding: 8px 2px 27px; }
.intro-kicker { display: flex; align-items: center; gap: 8px; color: #63738a; font-size: 11px; font-weight: 700; letter-spacing: .02em; }
.live-pip { width: 7px; height: 7px; background: #4eaf81; border-radius: 50%; box-shadow: 0 0 0 4px #e5f6ec; }.slash { color: #b0bac7; }
.intro-row { display: flex; align-items: end; justify-content: space-between; gap: 24px; margin-top: 18px; }.intro-row h1 { margin: 0; color: #14243a; font-size: clamp(28px, 4vw, 44px); font-weight: 700; letter-spacing: -.045em; line-height: 1.03; }.intro-row p { max-width: 540px; margin: 12px 0 0; color: #718096; font-size: 13px; line-height: 1.7; }
.date-stamp { display: flex; align-items: center; gap: 9px; padding: 9px 12px 9px 10px; color: #203b61; border: 1px solid #dce4ec; border-radius: 13px; background: #fff; }.date-stamp strong { font-size: 27px; letter-spacing: -.06em; line-height: 1; }.date-stamp span { color: #7b8998; font-size: 10px; font-weight: 700; line-height: 1.25; }
.command-layout { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(250px, .8fr); gap: 15px; }.focus-panel { min-height: 330px; padding: 27px 30px; color: #f7fbff; background: #172d4c; border-radius: 25px 25px 25px 7px; overflow: hidden; position: relative; box-shadow: 0 20px 38px rgba(24, 48, 78, .16); }.focus-panel.teacher-focus { background: #1d4d56; }.focus-meta { display: flex; align-items: center; justify-content: space-between; gap: 15px; color: #a7bdd5; font-size: 10px; font-weight: 700; letter-spacing: .04em; position: relative; z-index: 1; }.focus-status { display: inline-flex; align-items: center; gap: 6px; color: #c8e9d7; }.focus-status i { width: 6px; height: 6px; background: #72d29c; border-radius: 50%; }.focus-content { position: relative; z-index: 1; margin-top: 38px; max-width: 510px; }.focus-number { display: block; color: #f4c86c; font: 700 58px Georgia, serif; letter-spacing: -.06em; line-height: .9; }.focus-number-label { display: block; margin-top: 9px; color: #adc0d4; font-size: 11px; }.focus-content h2 { max-width: 390px; margin: 22px 0 8px; color: #fff; font-size: clamp(22px, 3vw, 31px); letter-spacing: -.035em; line-height: 1.1; }.focus-content p { max-width: 430px; margin: 0; color: #b6c7d8; font-size: 12px; line-height: 1.7; }.focus-action { display: inline-flex; align-items: center; gap: 13px; margin-top: 22px; padding: 11px 15px; color: #1c304b; background: #f5c86d; border-radius: 9px; font-size: 11px; font-weight: 800; text-decoration: none; }.focus-action:hover { background: #ffda89; }.focus-action span { font-size: 17px; line-height: .7; }.focus-orbit { position: absolute; border: 1px solid rgba(255,255,255,.13); border-radius: 50%; }.orbit-one { width: 390px; height: 390px; right: -145px; bottom: -190px; }.orbit-two { width: 290px; height: 290px; right: -95px; bottom: -140px; }
.insight-panel { display: flex; flex-direction: column; justify-content: space-between; min-height: 330px; padding: 24px; background: #f7f3e9; border: 1px solid #e9e1d3; border-radius: 7px 25px 25px 25px; }.insight-heading { display: flex; justify-content: space-between; gap: 12px; }.insight-heading h2 { margin: 7px 0 0; color: #23344c; font-size: 18px; letter-spacing: -.025em; }.signal { color: #ad7c2b; font: 700 21px Georgia, serif; }.signal-line { display: flex; align-items: start; gap: 12px; margin-top: 55px; }.signal-dot { width: 12px; height: 12px; flex: 0 0 auto; margin-top: 4px; border: 3px solid #dfb45b; border-radius: 50%; box-shadow: 0 0 0 5px #f0e6d1; }.signal-line strong { color: #273b55; font-size: 13px; }.signal-line p { margin: 6px 0 0; color: #7b8490; font-size: 11px; line-height: 1.65; }.insight-rule { height: 1px; margin: 25px 0 14px; background: #e5dccb; }.insight-footer { display: flex; justify-content: space-between; gap: 12px; color: #8b8d8c; font-size: 10px; }.insight-footer strong { color: #43536a; font-size: 10px; text-align: right; }
.metrics-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-top: 15px; border-top: 1px solid #dfe6ed; border-bottom: 1px solid #dfe6ed; }.metric-item { min-height: 107px; padding: 19px 20px 17px 0; border-right: 1px solid #dfe6ed; }.metric-item:not(:first-child) { padding-left: 20px; }.metric-item:last-child { border-right: 0; }.metric-item span, .metric-item small { display: block; color: #7d8998; font-size: 10px; }.metric-item strong { display: block; margin: 8px 0 4px; color: #1d3554; font-size: 24px; letter-spacing: -.045em; }.metric-item small { color: #a0aab5; }
.workflow-section { margin-top: 34px; }.section-heading { display: flex; align-items: end; justify-content: space-between; gap: 15px; margin-bottom: 12px; }.section-heading h2 { margin: 6px 0 0; color: #1a2a3e; font-size: 20px; letter-spacing: -.035em; }.workflow-count { color: #9aa6b4; font-size: 10px; }.workflow-list { border-top: 1px solid #dfe6ed; }.workflow-row { display: flex; align-items: center; gap: 16px; min-height: 76px; padding: 12px 4px; color: inherit; text-decoration: none; border-bottom: 1px solid #dfe6ed; transition: padding .18s ease, background .18s ease; }.workflow-row:hover { padding-left: 12px; padding-right: 12px; background: #f7fafc; }.workflow-index { width: 23px; color: #a5b0bd; font: 700 11px Georgia, serif; }.workflow-icon { display: grid; place-items: center; width: 37px; height: 37px; flex: 0 0 auto; color: #3269bc; background: #eaf1fc; border-radius: 10px 10px 10px 4px; }.workflow-icon svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }.workflow-copy { display: grid; gap: 4px; flex: 1; }.workflow-copy strong { color: #2a3d55; font-size: 12px; }.workflow-copy span { color: #7d8a9a; font-size: 11px; line-height: 1.4; }.workflow-arrow { color: #98a5b3; font-size: 18px; }.dashboard-footer { display: flex; align-items: center; gap: 8px; margin-top: 38px; color: #a1aab4; font-size: 10px; }.footer-mark { display: grid; place-items: center; width: 20px; height: 20px; color: #1b304d; background: #edc36c; border-radius: 6px 6px 6px 2px; font: 800 12px Georgia, serif; }.footer-line { width: 30px; height: 1px; margin-left: 5px; background: #d7dfe7; }
.focus-panel.principal-focus { background: #3d3158; }
.focus-panel.student-focus { background: #375342; }
.eyebrow { color: #8d99a8; font-size: 10px; font-weight: 800; letter-spacing: .045em; }
@media (max-width: 800px) { .command-layout { grid-template-columns: 1fr; }.insight-panel { min-height: 225px; }.signal-line { margin-top: 30px; }.metrics-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }.metric-item:nth-child(2) { border-right: 0; }.metric-item:nth-child(n+3) { border-top: 1px solid #dfe6ed; } }
@media (max-width: 560px) { .intro-row { align-items: start; }.intro-row h1 { font-size: 30px; }.date-stamp { display: none; }.focus-panel { min-height: 360px; padding: 23px; }.focus-content { margin-top: 32px; }.metrics-strip { grid-template-columns: 1fr 1fr; }.metric-item, .metric-item:not(:first-child) { padding-left: 12px; padding-right: 12px; }.metric-item:nth-child(odd) { padding-left: 0; }.workflow-row { gap: 10px; }.workflow-index { display: none; }.workflow-copy span { max-width: 235px; }.dashboard-footer { margin-top: 28px; } }
@media (prefers-reduced-motion: reduce) { .workflow-row { transition: none; } }
</style>
