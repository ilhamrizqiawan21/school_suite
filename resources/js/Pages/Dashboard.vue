<script setup lang="ts">
import AppLayout from '../layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface DashboardStats {
    modules_ready: number;
    active_period: string;
    framework_version: string;
    role_metrics?: Record<string, number>;
    latest_announcements?: Array<{ id: number; title: string; author_name?: string | null; published_at?: string | null }>;
    latest_login_logs?: Array<{ id: number; user_name: string; status: string; reason?: string | null; ip_address?: string | null; created_at?: string | null }>;
    attendance_trend?: Array<{ month_key: string; label: string; rate: number; present: number; total: number }>;
    principal_overview?: PrincipalOverview;
}

interface PrincipalOverview {
    period: 'term' | '30d';
    summary: {
        attendance_rate: number | null;
        attendance_present: number;
        attendance_total: number;
        tasks_submitted: number;
        tasks_total: number;
        tasks_rate: number | null;
        reports_published: number;
        reports_total: number;
        exams_live: number;
        students: number;
        teachers: number;
    };
    attendance_trend: Array<{ month_key: string; label: string; rate: number | null; present: number; total: number }>;
    learning_by_grade: Array<{ grade: string; label: string; submitted: number; total: number; rate: number | null }>;
    activity: {
        admin: { active_classes: number; missing_homeroom: number };
        teacher: { sessions_total: number; journals_completed: number };
        homeroom: { reports_total: number; reports_published: number };
        student: { tasks_total: number; tasks_submitted: number };
    };
    attention: Array<{ key: string; title: string; detail: string; href: string; level: 'high' | 'medium' | 'info' }>;
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
const latestAnnouncements = computed(() => props.stats?.latest_announcements ?? []);
const latestLoginLogs = computed(() => props.stats?.latest_login_logs ?? []);
const attendanceTrend = computed(() => props.stats?.attendance_trend ?? []);
const principalOverview = computed(() => props.stats?.principal_overview);
const principalSummary = computed(() => principalOverview.value?.summary);
const principalPeriod = computed(() => principalOverview.value?.period ?? 'term');
const principalAttention = computed(() => principalOverview.value?.attention ?? []);
const principalTrend = computed(() => principalOverview.value?.attendance_trend ?? []);
const principalGrades = computed(() => principalOverview.value?.learning_by_grade ?? []);
const percentLabel = (rate: number | null | undefined) => rate === null || rate === undefined ? '—' : `${Math.round(rate * 10) / 10}%`;
const completionRate = (done: number, total: number) => total > 0 ? Math.min(100, Math.round((done / total) * 100)) : null;
const principalActivity = computed(() => {
    const activity = principalOverview.value?.activity;
    if (!activity) return [];

    return [
        { role: 'Admin', title: 'Kesiapan rombel', done: Math.max(0, activity.admin.active_classes - activity.admin.missing_homeroom), total: activity.admin.active_classes, detail: `${activity.admin.missing_homeroom} rombel belum memiliki wali kelas`, href: '/akademik/kelas' },
        { role: 'Guru', title: 'Jurnal selesai', done: activity.teacher.journals_completed, total: activity.teacher.sessions_total, detail: `${activity.teacher.journals_completed} dari ${activity.teacher.sessions_total} sesi mengajar`, href: '/laporan' },
        { role: 'Wali Kelas', title: 'Rapor terbit', done: activity.homeroom.reports_published, total: activity.homeroom.reports_total, detail: `${activity.homeroom.reports_published} dari ${activity.homeroom.reports_total} rapor tersedia`, href: '/penilaian/rapor' },
        { role: 'Siswa', title: 'Tugas dikumpulkan', done: activity.student.tasks_submitted, total: activity.student.tasks_total, detail: `${activity.student.tasks_submitted} dari ${activity.student.tasks_total} tugas kelas`, href: '/pembelajaran/progress' },
    ].map((item) => ({ ...item, rate: completionRate(item.done, item.total) }));
});
const studentAttendancePercent = computed(() => {
    const total = roleMetrics.value.attendance_total ?? 0;
    return total ? Math.round(((roleMetrics.value.attendance_present ?? 0) / total) * 100) : 0;
});
const isAdmin = computed(() => role.value === 'admin');
const isOperator = computed(() => role.value === 'operator');
const isPrincipal = computed(() => role.value === 'principal');
const isHomeroom = computed(() => role.value === 'homeroom');
const isTeacher = computed(() => role.value === 'teacher');
const isStudent = computed(() => role.value === 'student');
const today = computed(() => {
    const parts = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).formatToParts(new Date());
    return {
        day: parts.find((part) => part.type === 'day')?.value ?? '—',
        month: parts.find((part) => part.type === 'month')?.value ?? '',
        year: parts.find((part) => part.type === 'year')?.value ?? '',
    };
});
const formatDateTime = (value?: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(value))
    : '—';

const adminActions = [
    { title: 'Kelola pengguna', description: 'Atur akun, role, dan status akses warga sekolah.', href: '/pengaturan/pengguna', icon: 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m6-10a4 4 0 100-8 4 4 0 000 8zm14 10v-2a3 3 0 00-3-3.87M16 3.13a4 4 0 010 7.75' },
    { title: 'Kelola siswa', description: 'Atur data siswa dan penempatan kelas periode aktif.', href: '/akademik/siswa', icon: 'M4 5.5A2.5 2.5 0 016.5 3h11A2.5 2.5 0 0120 5.5v13a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 18.5v-13zM8 7h8M8 11h8M8 15h5' },
    { title: 'Kelola guru', description: 'Perbarui data guru dan penugasan pengajar.', href: '/akademik/guru', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857' },
    { title: 'Kelola kelas', description: 'Atur rombel, kapasitas, dan wali kelas.', href: '/akademik/kelas', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1' },
    { title: 'Jadwal pelajaran', description: 'Tinjau jadwal aktif dan kesiapan pembelajaran.', href: '/akademik/jadwal', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { title: 'Pusat laporan', description: 'Lihat rekapan akademik dan ekspor data sekolah.', href: '/laporan', icon: 'M9 17v-2m3 2v-4m3 4v-6m4 10H5a2 2 0 01-2-2V5a2 2 0 012-2h14v14z' },
    { title: 'Komunikasi sekolah', description: 'Terbitkan pengumuman dan agenda untuk warga sekolah.', href: '/komunikasi', icon: 'M4 5h16v11H8l-4 4V5zm4 4h8m-8 3h5' },
    { title: 'Log login', description: 'Pantau percobaan login dan aktivitas keamanan akun.', href: '/pengaturan/keamanan', icon: 'M12 11c1.657 0 3-1.343 3-3V5a3 3 0 00-6 0v3c0 1.657 1.343 3 3 3zm0 0v2m-7 8h14a2 2 0 002-2v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2z' },
    { title: 'Pengaturan sekolah', description: 'Periksa identitas dan konfigurasi sekolah.', href: '/pengaturan/sekolah', icon: 'M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM19.4 15a1.7 1.7 0 00.34 1.88l.06.06-1.9 1.9-.06-.06a1.7 1.7 0 00-1.88-.34 1.7 1.7 0 00-1.03 1.56V20h-2.68v-.09a1.7 1.7 0 00-1.03-1.56 1.7 1.7 0 00-1.88.34l-.06.06-1.9-1.9.06-.06A1.7 1.7 0 007.8 15a1.7 1.7 0 00-1.56-1.03H6v-2.68h.24A1.7 1.7 0 007.8 10a1.7 1.7 0 00-.34-1.88L7.4 8.06l1.9-1.9.06.06a1.7 1.7 0 001.88.34 1.7 1.7 0 001.88-.34l.06-.06 1.9 1.9-.06.06A1.7 1.7 0 0019.4 15z' },
];
const operatorActions = [
    { title: 'Data siswa & rombel', description: 'Kelola data pokok siswa, import, dan penempatan kelas.', href: '/akademik/siswa', icon: 'M4 5.5A2.5 2.5 0 016.5 3h11A2.5 2.5 0 0120 5.5v13a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 18.5v-13zM8 7h8M8 11h8M8 15h5' },
    { title: 'Penugasan guru', description: 'Atur data guru aktif dan penugasan mengajar semester.', href: '/akademik/guru', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857' },
    { title: 'Rombongan belajar', description: 'Kelola rombel, kuota kelas, dan penetapan wali kelas.', href: '/akademik/kelas', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1' },
    { title: 'Jadwal pelajaran', description: 'Susun jadwal mingguan, cek bentrok, dan terbitkan jadwal.', href: '/akademik/jadwal', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { title: 'Skema penilaian', description: 'Atur komponen nilai, bobot evaluasi, dan format rapor.', href: '/penilaian/skema', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
    { title: 'Kelola pengguna', description: 'Atur akun siswa, guru, dan staf sekolah.', href: '/pengaturan/pengguna', icon: 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m6-10a4 4 0 100-8 4 4 0 000 8zm14 10v-2a3 3 0 00-3-3.87M16 3.13a4 4 0 010 7.75' },
    { title: 'Identitas sekolah', description: 'Periksa profil kelembagaan dan konfigurasi dasar.', href: '/pengaturan/sekolah', icon: 'M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM19.4 15a1.7 1.7 0 00.34 1.88l.06.06-1.9 1.9-.06-.06a1.7 1.7 0 00-1.88-.34 1.7 1.7 0 00-1.03 1.56V20h-2.68v-.09a1.7 1.7 0 00-1.03-1.56 1.7 1.7 0 00-1.88.34l-.06.06-1.9-1.9.06-.06A1.7 1.7 0 007.8 15a1.7 1.7 0 00-1.56-1.03H6v-2.68h.24A1.7 1.7 0 007.8 10a1.7 1.7 0 00-.34-1.88L7.4 8.06l1.9-1.9.06.06a1.7 1.7 0 001.88.34 1.7 1.7 0 001.88-.34l.06-.06 1.9 1.9-.06.06A1.7 1.7 0 0019.4 15z' },
];
const homeroomActions = [
    { title: 'Dashboard perwalian', description: 'Pantau ringkasan kelas binaan, absensi, dan profil siswa.', href: '/wali-kelas', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { title: 'Catatan observasi & BK', description: 'Catat pembinaan karakter dan koordinasi guru bimbingan konseling.', href: '/wali-kelas/catatan', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
    { title: 'Kedisiplinan & prestasi', description: 'Rekam poin prestasi, catatan dispensasi, dan penanganan insiden.', href: '/wali-kelas/kedisiplinan', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { title: 'WhatsApp orang tua', description: 'Kirim notifikasi presensi dan pengumuman ke kontak orang tua.', href: '/komunikasi/whatsapp', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
    { title: 'Input nilai pelajaran', description: 'Lengkapi capaian belajar dan tugas pada kelas yang Anda ampu.', href: '/penilaian/skor', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-2-2a2 2 0 00-2 2v2h6V5a2 2 0 00-2-2h-2zm-1 9h4m-4 4h4' },
    { title: 'Penilaian sikap', description: 'Evaluasi catatan karakter dan sikap kelas binaan.', href: '/penilaian/sikap', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { title: 'Rapor kelas', description: 'Tinjau rekapan nilai rapor dan kesiapan cetak hasil belajar.', href: '/penilaian/rapor', icon: 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h5' },
    { title: 'Presensi kelas', description: 'Catat dan verifikasi kehadiran harian siswa.', href: '/akademik/presensi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0a2 2 0 104 0 2 2 0 00-4 0zm-1 9 2 2 4-4' },
];
const teacherActions = [
    { title: 'Ruang belajar LMS', description: 'Kelola materi pembelajaran, modul, dan penugasan kelas.', href: '/pembelajaran', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
    { title: 'Presensi siswa', description: 'Catat kehadiran tatap muka dan tinjau rekap pertemuan.', href: '/akademik/presensi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0a2 2 0 104 0 2 2 0 00-4 0zm-1 9 2 2 4-4' },
    { title: 'Jurnal mengajar', description: 'Dokumentasikan agenda pembelajaran dan catatan kelas harian.', href: '/akademik/jurnal', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18 5.754 18 7.5 18s3.332.477 4.5 1.253' },
    { title: 'Input nilai', description: 'Lengkapi capaian belajar, tugas harian, dan remedial siswa.', href: '/penilaian/skor', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-2-2a2 2 0 00-2 2v2h6V5a2 2 0 00-2-2h-2zm-1 9h4m-4 4h4' },
    { title: 'Penilaian sikap', description: 'Catat observasi sikap dan capaian profil pelajar.', href: '/penilaian/sikap', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { title: 'Bank soal cerdas', description: 'Susun butir soal, paket asesmen, dan berbagi bersama rekan guru.', href: '/penilaian/bank-soal', icon: 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h5' },
    { title: 'Ujian CBT', description: 'Jadwalkan ujian online dan pantau langsung pengerjaan siswa.', href: '/ujian', icon: 'M6 3h12v18H6zM9 7h6M9 11h6M9 15h4' },
    { title: 'Kelas daring', description: 'Buka sesi tatap muka interaktif dan catat presensi digital.', href: '/akademik/kelas-daring', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
];
const principalActions = [
    { title: 'Pusat laporan sekolah', description: 'Pantau rekapitulasi nilai, presensi, dan ringkasan akademik.', href: '/laporan', icon: 'M9 17v-2m3 2v-4m3 4v-6m4 10H5a2 2 0 01-2-2V5a2 2 0 012-2h14v14z' },
    { title: 'Analisis asesmen', description: 'Tinjau analisis butir soal, sebaran nilai, dan ketuntasan ujian.', href: '/penilaian/analisis', icon: 'M3 3v18h18M8 15l4-4 4 3 5-7' },
    { title: 'Monitoring siswa', description: 'Pantau sebaran peserta didik aktif dan riwayat siklus siswa.', href: '/akademik/siswa', icon: 'M4 5.5A2.5 2.5 0 016.5 3h11A2.5 2.5 0 0120 5.5v13a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 18.5v-13zM8 7h8M8 11h8M8 15h5' },
    { title: 'Tenaga pendidik', description: 'Tinjau data guru, beban mengajar, dan penugasan akademik.', href: '/akademik/guru', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857' },
    { title: 'Jadwal pelajaran', description: 'Periksa distribusi jadwal belajar dan keterisian rombel.', href: '/akademik/jadwal', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { title: 'Rekap presensi', description: 'Tinjau kehadiran siswa per rombongan belajar.', href: '/akademik/presensi/rekap', icon: 'M9 17v-2m3 2v-4m3 4v-6m4 10H5a2 2 0 01-2-2V5a2 2 0 012-2h14v14z' },
];
const studentActions = [
    { title: 'Ruang belajar LMS', description: 'Akses materi pelajaran, modul ajar, dan kumpulkan tugas kelas.', href: '/pembelajaran', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
    { title: 'Jadwal pelajaran', description: 'Lihat agenda belajar harian, waktu sesi, dan ruang kelas.', href: '/akademik/jadwal', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { title: 'Ujian CBT', description: 'Kerjakan asesmen online yang siap diikuti dan tinjau hasilnya.', href: '/ujian', icon: 'M6 3h12v18H6zM9 7h6M9 11h6M9 15h4' },
    { title: 'Rapor & nilai saya', description: 'Tinjau capaian hasil belajar dan nilai semester aktif.', href: '/penilaian/rapor', icon: 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h5' },
    { title: 'Progress belajar mandiri', description: 'Pantau ketuntasan materi, tugas terkirim, dan persentase kehadiran.', href: '/pembelajaran/progress', icon: 'M9 17v-6m4 6V7m4 10V4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z' },
    { title: 'Diskusi & chat kelas', description: 'Berinteraksi dengan guru dan teman dalam ruang kelas.', href: '/komunikasi/chat', icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z' },
    { title: 'Kelas daring', description: 'Masuk ke ruang pertemuan virtual interaktif.', href: '/akademik/kelas-daring', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
];
const fallbackActions = [
    { title: 'Data siswa', description: 'Kelola identitas peserta didik dan penempatan kelas.', href: '/akademik/siswa', icon: 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m6-10a4 4 0 100-8 4 4 0 000 8' },
    { title: 'Presensi siswa', description: 'Catat kehadiran dan tinjau rekap kelas.', href: '/akademik/presensi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0a2 2 0 104 0' },
    { title: 'Input nilai', description: 'Isi capaian belajar sesuai skema penilaian aktif.', href: '/penilaian/skor', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-2-2a2 2 0 00-2 2v2h6V5a2 2 0 00-2-2h-2' },
];

const actions = computed(() => {
    if (isAdmin.value) return adminActions;
    if (isOperator.value) return operatorActions;
    if (isPrincipal.value) return principalActions;
    if (isHomeroom.value) return homeroomActions;
    if (isTeacher.value) return teacherActions;
    if (isStudent.value) return studentActions;
    return fallbackActions;
});
const primaryAction = computed(() => actions.value[0]);
const nextAction = computed(() => actions.value[1] ?? actions.value[0]);
const secondaryActions = computed(() => actions.value.slice(1));
const focusLabel = computed(() => {
    if (isAdmin.value) return 'Operasional sekolah';
    if (isOperator.value) return 'Administrasi akademik';
    if (isPrincipal.value) return 'Kinerja sekolah';
    if (isHomeroom.value) return 'Ruang perwalian';
    if (isTeacher.value) return 'Ruang mengajar';
    if (isStudent.value) return 'Perjalanan belajar';
    return 'Ruang kerja sekolah';
});
const focusTitle = computed(() => {
    if (isAdmin.value) return 'Pusat kendali sekolah.';
    if (isOperator.value) return 'Pusat pengelolaan data dan konfigurasi.';
    if (isPrincipal.value) return 'Lihat sekolah dari gambaran utuh.';
    if (isHomeroom.value) return 'Dampingi tumbuh kembang kelas binaan.';
    if (isTeacher.value) return 'Mulai dari pekerjaan yang paling penting.';
    if (isStudent.value) return 'Belajar dengan arah yang lebih jelas.';
    return 'Semua pekerjaan sekolah, dari satu tempat.';
});
const focusCopy = computed(() => {
    if (isAdmin.value) return 'Pantau warga sekolah, komunikasi, dan data akademik tanpa berpindah-pindah konteks.';
    if (isOperator.value) return 'Kelola master data siswa, guru, rombel, jadwal, dan skema penilaian secara terpadu.';
    if (isPrincipal.value) return 'Pantau denyut akademik, laporan capaian, dan kesiapan operasional dari satu ruang pimpinan.';
    if (isHomeroom.value) return 'Pantau kehadiran, catatan pembinaan, kedisiplinan, serta komunikasi langsung dengan orang tua siswa.';
    if (isTeacher.value) return 'Kelola materi pembelajaran, presensi, bank soal, dan evaluasi hasil belajar siswa.';
    if (isStudent.value) return 'Akses materi pelajaran, jadwal kelas, ujian CBT, dan pantau capaian belajarmu.';
    return 'Akses modul yang tersedia sesuai hak akses akun Anda.';
});
const focusMetric = computed(() => {
    if (isAdmin.value || isPrincipal.value) return roleMetrics.value.students;
    if (isOperator.value) return roleMetrics.value.active_classes;
    if (isTeacher.value || isHomeroom.value) return roleMetrics.value.teaching_assignments;
    if (isStudent.value) return roleMetrics.value.active_enrollments;
    return props.stats?.modules_ready;
});
const focusMetricLabel = computed(() => {
    if (isAdmin.value || isPrincipal.value) return 'siswa aktif';
    if (isOperator.value) return 'kelas aktif';
    if (isHomeroom.value) return 'tugas kelas';
    if (isTeacher.value) return 'kelas diampu';
    if (isStudent.value) return 'kelas aktif';
    return 'modul tersedia';
});
const metricItems = computed(() => {
    if (isAdmin.value) return [
        { label: 'Siswa aktif', value: roleMetrics.value.students, note: 'terdaftar di sekolah' },
        { label: 'Guru aktif', value: roleMetrics.value.teachers, note: 'tenaga pengajar' },
        { label: 'Kelas aktif', value: roleMetrics.value.active_classes, note: 'periode akademik aktif' },
        { label: 'Mata pelajaran', value: roleMetrics.value.active_subjects, note: 'aktif di sekolah' },
    ];
    if (isOperator.value) return [
        { label: 'Siswa aktif', value: roleMetrics.value.students, note: 'terdaftar di sekolah' },
        { label: 'Guru aktif', value: roleMetrics.value.teachers, note: 'tenaga pendidik' },
        { label: 'Kelas aktif', value: roleMetrics.value.active_classes, note: 'periode berjalan' },
        { label: 'Pengguna aktif', value: roleMetrics.value.active_users, note: 'akun warga sekolah' },
    ];
    if (isPrincipal.value) return [
        { label: 'Tugas terkumpul', value: percentLabel(principalSummary.value?.tasks_rate), note: `${principalSummary.value?.tasks_submitted ?? 0} dari ${principalSummary.value?.tasks_total ?? 0} tugas` },
        { label: 'Ujian berlangsung', value: principalSummary.value?.exams_live, note: 'sesuai jadwal saat ini' },
        { label: 'Rapor terbit', value: principalSummary.value?.reports_published, note: `dari ${principalSummary.value?.reports_total ?? 0} rapor periode aktif` },
        { label: 'Siswa / guru aktif', value: principalSummary.value ? `${principalSummary.value.students} / ${principalSummary.value.teachers}` : '—', note: 'jumlah warga sekolah' },
    ];
    if (isHomeroom.value) return [
        { label: 'Kelas binaan/diampu', value: roleMetrics.value.teaching_assignments, note: 'periode aktif' },
        { label: 'Soal aktif', value: roleMetrics.value.active_questions, note: 'di bank soal' },
        { label: 'Ujian mendatang', value: roleMetrics.value.upcoming_exams, note: 'draft dan terbit' },
        { label: 'Nilai tersimpan', value: roleMetrics.value.recorded_scores, note: 'rekaman Anda' },
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
const attendanceChart = computed(() => {
    const width = 760;
    const height = 280;
    const padding = { top: 20, right: 20, bottom: 46, left: 42 };
    const plotWidth = width - padding.left - padding.right;
    const plotHeight = height - padding.top - padding.bottom;
    const points = attendanceTrend.value.map((item, index) => {
        const x = attendanceTrend.value.length === 1
            ? padding.left + plotWidth / 2
            : padding.left + (plotWidth / (attendanceTrend.value.length - 1)) * index;
        const y = padding.top + plotHeight - (Math.max(0, Math.min(100, item.rate)) / 100) * plotHeight;

        return { ...item, x, y };
    });

    return {
        points,
        line: points.map((point) => point.x + ',' + point.y).join(' '),
        area: points.length > 1
            ? 'M ' + points[0].x + ' ' + (height - padding.bottom) + ' L ' + points.map((point) => point.x + ' ' + point.y).join(' L ') + ' L ' + points[points.length - 1].x + ' ' + (height - padding.bottom) + ' Z'
            : '',
        grid: [0, 25, 50, 75, 100].map((value) => ({
            value,
            y: padding.top + plotHeight - (value / 100) * plotHeight,
        })),
        width,
        height,
    };
});
const attendanceHasData = computed(() => attendanceTrend.value.some((item) => item.total > 0));
const attendanceAverage = computed(() => {
    const monthsWithData = attendanceTrend.value.filter((item) => item.total > 0);
    if (!monthsWithData.length) return 0;
    return Math.round((monthsWithData.reduce((sum, item) => sum + item.rate, 0) / monthsWithData.length) * 10) / 10;
});
const principalAttendanceChart = computed(() => {
    const width = 760;
    const height = 264;
    const padding = { top: 18, right: 24, bottom: 42, left: 42 };
    const plotWidth = width - padding.left - padding.right;
    const plotHeight = height - padding.top - padding.bottom;
    const points = principalTrend.value.map((item, index) => ({
        ...item,
        x: principalTrend.value.length === 1 ? padding.left + plotWidth / 2 : padding.left + (plotWidth / (principalTrend.value.length - 1)) * index,
        y: padding.top + plotHeight - (Math.max(0, Math.min(100, item.rate ?? 0)) / 100) * plotHeight,
    }));
    const segments: string[] = [];
    let currentSegment: string[] = [];
    points.forEach((point) => {
        if (point.total > 0) {
            currentSegment.push(`${point.x},${point.y}`);
        } else {
            if (currentSegment.length > 1) segments.push(currentSegment.join(' '));
            currentSegment = [];
        }
    });
    if (currentSegment.length > 1) segments.push(currentSegment.join(' '));

    return {
        width,
        height,
        points,
        segments,
        grid: [0, 25, 50, 75, 100].map((value) => ({ value, y: padding.top + plotHeight - (value / 100) * plotHeight })),
        hasData: points.some((point) => point.total > 0),
    };
});
</script>

<template>
    <AppLayout title="Beranda">
        <section class="view dashboard-view">
            <header class="dashboard-intro">
                <div class="intro-kicker"><span class="live-pip" />{{ roleLabel }} <span class="slash">/</span> {{ activePeriod }}</div>
                <div class="intro-row">
                    <div><h1>{{ isPrincipal ? 'Gambaran sekolah hari ini.' : `${firstName}, ini pusat kerjamu.` }}</h1></div>
                    <div class="date-stamp"><strong>{{ today.day }}</strong><span>{{ today.month }}<br>{{ today.year }}</span></div>
                </div>
            </header>

            <div v-if="isPrincipal" class="principal-toolbar">
                <span>Periode aktif <strong>{{ activePeriod }}</strong></span>
                <nav class="principal-periods" aria-label="Rentang waktu ikhtisar">
                    <Link href="/?period=30d" :class="{ active: principalPeriod === '30d' }" :aria-current="principalPeriod === '30d' ? 'page' : undefined" preserve-scroll>30 hari</Link>
                    <Link href="/?period=term" :class="{ active: principalPeriod === 'term' }" :aria-current="principalPeriod === 'term' ? 'page' : undefined" preserve-scroll>Semester</Link>
                </nav>
            </div>

            <div v-if="isPrincipal" class="principal-command">
                <article class="principal-lead">
                    <div class="principal-lead-top"><span>Kehadiran sekolah</span><span>Seluruh rombel</span></div>
                    <div class="principal-lead-number">{{ percentLabel(principalSummary?.attendance_rate) }}</div>
                    <h2>Seberapa konsisten siswa hadir?</h2>
                    <p v-if="principalSummary?.attendance_total">{{ principalSummary.attendance_present }} hadir dari {{ principalSummary.attendance_total }} catatan presensi pada rentang terpilih.</p>
                    <p v-else>Belum ada catatan presensi pada rentang terpilih.</p>
                    <Link href="/akademik/presensi/rekap" class="principal-lead-link">Buka rekap presensi <span aria-hidden="true">↗</span></Link>
                </article>
                <aside class="principal-attention">
                    <div class="principal-attention-title"><div><span>Perlu perhatian</span><h2>Prioritas pimpinan</h2></div><strong>{{ principalAttention.length }}</strong></div>
                    <ul v-if="principalAttention.length" class="principal-alert-list">
                        <li v-for="item in principalAttention.slice(0, 3)" :key="item.key" :class="`alert-${item.level}`">
                            <span class="principal-alert-dot" aria-hidden="true" />
                            <div><strong>{{ item.title }}</strong><p>{{ item.detail }}</p></div>
                            <Link :href="item.href" :aria-label="`Lihat ${item.title}`">↗</Link>
                        </li>
                    </ul>
                    <p v-else class="principal-attention-empty">Belum ada hal yang perlu ditindaklanjuti dari data periode ini.</p>
                    <span v-if="principalAttention.length > 3" class="principal-alert-more">{{ principalAttention.length - 3 }} perhatian lain tercatat</span>
                </aside>
            </div>

            <section v-if="isAdmin" class="metrics-strip metrics-first" aria-label="Ringkasan metrik">
                <article v-for="(metric, index) in metricItems" :key="metric.label" class="metric-item" :class="`metric-tone-${index + 1}`">
                    <div class="metric-head"><span>{{ metric.label }}</span><span class="metric-arrow" aria-hidden="true">↗</span></div>
                    <strong>{{ metric.value ?? '—' }}</strong>
                    <small>{{ metric.note }}</small>
                </article>
            </section>

            <div v-if="!isPrincipal" class="command-layout" :class="{ 'admin-command': isAdmin }">
                <article class="focus-panel" :class="{ 'teacher-focus': isTeacher || isHomeroom, 'principal-focus': isPrincipal, 'student-focus': isStudent, 'operator-focus': isOperator }">
                    <div class="focus-meta"><span>{{ focusLabel }}</span><span class="focus-status"><i /> Sistem aktif</span></div>
                    <div class="focus-content" :class="{ 'admin-focus-content': isAdmin }"><template v-if="!isAdmin"><span class="focus-number">{{ focusMetric ?? '—' }}</span><span class="focus-number-label">{{ focusMetricLabel }}</span></template><h2>{{ focusTitle }}</h2><p>{{ focusCopy }}</p><Link :href="primaryAction.href" class="focus-action">{{ primaryAction.title }} <span aria-hidden="true">↗</span></Link></div>
                    <div class="focus-orbit orbit-one" /><div class="focus-orbit orbit-two" />
                </article>
                <aside class="insight-panel">
                    <div class="insight-heading"><div><span class="eyebrow">Jalan pintas</span><h2>Pekerjaan berikutnya</h2></div></div>
                    <Link :href="nextAction.href" class="signal-line"><span class="signal-dot" /><span><strong>{{ nextAction.title }}</strong><span class="signal-description">{{ nextAction.description }}</span></span><span class="signal-arrow" aria-hidden="true">↗</span></Link>
                    <div class="insight-rule" />
                    <div class="insight-footer"><span>Periode</span><strong>{{ activePeriod }}</strong></div>
                </aside>
            </div>

            <section v-if="!isAdmin" class="metrics-strip" aria-label="Ringkasan metrik">
                <article v-for="(metric, index) in metricItems" :key="metric.label" class="metric-item" :class="`metric-tone-${index + 1}`">
                    <div class="metric-head"><span>{{ metric.label }}</span><span class="metric-arrow" aria-hidden="true">↗</span></div>
                    <strong>{{ metric.value ?? '—' }}</strong>
                    <small>{{ metric.note }}</small>
                </article>
            </section>

            <section v-if="isPrincipal" class="principal-overview" aria-label="Ikhtisar sekolah">
                <div class="principal-charts">
                    <section class="principal-chart-panel" aria-labelledby="principal-attendance-title">
                        <div class="principal-panel-heading"><div><span>Tren sekolah</span><h2 id="principal-attendance-title">Kehadiran siswa</h2><p>Persentase siswa hadir dari catatan presensi tiap rentang waktu.</p></div><Link href="/akademik/presensi/rekap">Lihat rekap ↗</Link></div>
                        <div v-if="principalAttendanceChart.hasData" class="principal-chart-wrap">
                            <svg class="principal-chart" :viewBox="`0 0 ${principalAttendanceChart.width} ${principalAttendanceChart.height}`" role="img" aria-labelledby="principal-chart-label">
                                <title id="principal-chart-label">Tren kehadiran sekolah: {{ principalTrend.filter((point) => point.total > 0).map((point) => `${point.label} ${percentLabel(point.rate)}`).join(', ') }}</title>
                                <g class="chart-grid"><line v-for="grid in principalAttendanceChart.grid" :key="grid.value" x1="42" x2="736" :y1="grid.y" :y2="grid.y" /><text v-for="grid in principalAttendanceChart.grid" :key="`principal-${grid.value}`" x="0" :y="grid.y + 4">{{ grid.value }}%</text></g>
                                <polyline v-for="(segment, index) in principalAttendanceChart.segments" :key="index" class="chart-line" :points="segment" />
                                <g v-for="point in principalAttendanceChart.points" :key="point.month_key" class="chart-point">
                                    <circle v-if="point.total > 0" :cx="point.x" :cy="point.y" r="5"><title>{{ point.label }}: {{ percentLabel(point.rate) }} ({{ point.present }}/{{ point.total }})</title></circle>
                                    <text :x="point.x" y="252" text-anchor="middle">{{ point.label }}</text>
                                </g>
                            </svg>
                        </div>
                        <div v-else class="principal-empty">Belum ada data kehadiran untuk dibuat grafik.</div>
                    </section>
                    <section class="principal-grade-panel" aria-labelledby="principal-learning-title">
                        <div class="principal-panel-heading"><div><span>Progres belajar</span><h2 id="principal-learning-title">Tugas per jenjang</h2><p>Bagian tugas yang sudah dikumpulkan siswa.</p></div><Link href="/pembelajaran/progress">Lihat progres ↗</Link></div>
                        <div v-if="principalGrades.length" class="principal-grade-list">
                            <div v-for="grade in principalGrades" :key="grade.grade" class="principal-grade-row">
                                <div class="principal-grade-meta"><strong>{{ grade.label }}</strong><span>{{ grade.submitted }}/{{ grade.total }} tugas</span><b>{{ percentLabel(grade.rate) }}</b></div>
                                <div v-if="grade.total" class="principal-grade-track" role="progressbar" :aria-label="`Tugas ${grade.label} terkumpul`" :aria-valuenow="grade.rate ?? 0" aria-valuemin="0" aria-valuemax="100"><span :style="{ width: `${grade.rate ?? 0}%` }" /></div>
                                <p v-else>Belum ada tugas terbit pada jenjang ini.</p>
                            </div>
                        </div>
                        <div v-else class="principal-empty">Belum ada tugas terbit pada periode aktif.</div>
                    </section>
                </div>

                <section class="principal-activity" aria-labelledby="principal-activity-title">
                    <div class="principal-section-heading"><div><span>Aktivitas lintas peran</span><h2 id="principal-activity-title">Dari data sekolah sampai hasil belajar</h2></div><span>Ringkasan periode aktif</span></div>
                    <div class="principal-activity-grid">
                        <Link v-for="item in principalActivity" :key="item.role" :href="item.href" class="principal-activity-item">
                            <span>{{ item.role }}</span><strong>{{ percentLabel(item.rate) }}</strong><h3>{{ item.title }}</h3><p>{{ item.detail }}</p>
                            <div v-if="item.total" class="principal-activity-track" aria-hidden="true"><i :style="{ width: `${item.rate ?? 0}%` }" /></div>
                            <small>Lihat rincian ↗</small>
                        </Link>
                        <p v-if="!principalActivity.length" class="principal-empty">Belum ada aktivitas untuk ditampilkan.</p>
                    </div>
                </section>

                <section class="principal-news" aria-labelledby="principal-news-title">
                    <div class="principal-section-heading"><div><span>Komunikasi sekolah</span><h2 id="principal-news-title">Pengumuman terbaru</h2></div><Link href="/komunikasi">Lihat semua ↗</Link></div>
                    <ol v-if="latestAnnouncements.length"><li v-for="item in latestAnnouncements" :key="item.id"><strong>{{ item.title }}</strong><span>{{ item.author_name ?? 'Sekolah' }}</span><time>{{ formatDateTime(item.published_at) }}</time></li></ol>
                    <p v-else class="principal-empty">Belum ada pengumuman terbit.</p>
                </section>
            </section>

            <section v-if="!isTeacher && !isHomeroom && !isPrincipal" class="workflow-section">
                <div v-if="isAdmin" class="section-heading"><div><span class="eyebrow">Pantauan harian</span><h2>Pengumuman dan log login</h2></div><span class="workflow-count">2 area penting</span></div>
                <div v-if="isAdmin" class="admin-overview-grid">
                    <section class="admin-overview-card announcement-card">
                        <header class="admin-card-header"><div class="admin-card-title"><span class="admin-card-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v11H8l-4 4V5zm4 4h8m-8 3h5" /></svg></span><span class="admin-card-copy"><strong>Pengumuman</strong><span>5 pengumuman terakhir</span></span></div><Link href="/komunikasi" class="admin-card-link">Lihat semua ↗</Link></header>
                        <ol class="admin-list"><li v-for="item in latestAnnouncements" :key="item.id"><span><strong>{{ item.title }}</strong><small>{{ item.author_name ?? 'Sekolah' }}</small></span><time>{{ formatDateTime(item.published_at) }}</time></li><li v-if="!latestAnnouncements.length" class="admin-empty">Belum ada pengumuman.</li></ol>
                    </section>
                    <section class="admin-overview-card login-card">
                        <header class="admin-card-header"><div class="admin-card-title"><span class="admin-card-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 11c1.657 0 3-1.343 3-3V5a3 3 0 00-6 0v3c0 1.657 1.343 3 3 3zm0 0v2m-7 8h14a2 2 0 002-2v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg></span><span class="admin-card-copy"><strong>Log login</strong><span>10 aktivitas login terakhir</span></span></div><Link href="/pengaturan/keamanan" class="admin-card-link">Lihat semua ↗</Link></header>
                        <ol class="admin-list"><li v-for="item in latestLoginLogs" :key="item.id"><span><strong>{{ item.user_name }}</strong><small>{{ item.ip_address ?? 'IP tidak tersedia' }}</small></span><time :class="item.status === 'success' ? 'login-success' : 'login-failed'">{{ item.status === 'success' ? 'Berhasil' : 'Gagal' }} · {{ formatDateTime(item.created_at) }}</time></li><li v-if="!latestLoginLogs.length" class="admin-empty">Belum ada log login.</li></ol>
                    </section>
                </div>
                <div v-else-if="isStudent" class="student-overview-grid">
                    <article class="student-overview-card attendance-card">
                        <header><span class="student-card-kicker">Kehadiran</span><Link href="/pembelajaran/progress">Lihat progres ↗</Link></header>
                        <div class="attendance-rate"><strong>{{ studentAttendancePercent }}%</strong><span>hadir</span></div>
                        <div class="attendance-meter" role="progressbar" :aria-valuenow="studentAttendancePercent" aria-valuemin="0" aria-valuemax="100" :aria-label="`Kehadiran ${studentAttendancePercent}%`"><span :style="{ width: `${studentAttendancePercent}%` }" /></div>
                        <p>{{ roleMetrics.attendance_present ?? 0 }} dari {{ roleMetrics.attendance_total ?? 0 }} catatan presensi berstatus hadir.</p>
                    </article>

                    <article class="student-overview-card assignment-card">
                        <header><span class="student-card-kicker">Tugas</span><Link href="/pembelajaran">Buka ruang belajar ↗</Link></header>
                        <div class="assignment-counts">
                            <div><strong>{{ roleMetrics.assignments_submitted ?? 0 }}</strong><span>Sudah dikumpulkan</span></div>
                            <div><strong>{{ roleMetrics.assignments_pending ?? 0 }}</strong><span>Belum dikumpulkan</span></div>
                        </div>
                        <p>{{ roleMetrics.assignments_total ?? 0 }} tugas diterbitkan untuk kelas aktif.</p>
                    </article>

                    <article class="student-overview-card student-announcements-card">
                        <header><span class="student-card-kicker">Pengumuman</span><Link href="/komunikasi">Lihat semua ↗</Link></header>
                        <ol v-if="latestAnnouncements.length" class="student-announcement-list">
                            <li v-for="item in latestAnnouncements" :key="item.id"><strong>{{ item.title }}</strong><span>{{ item.author_name ?? 'Sekolah' }} · {{ formatDateTime(item.published_at) }}</span></li>
                        </ol>
                        <p v-else class="student-announcement-empty">Belum ada pengumuman terbaru untuk kelasmu.</p>
                    </article>
                </div>
                <div v-else class="section-heading"><div><span class="eyebrow">Alur kerja</span><h2>{{ isPrincipal ? 'Pantauan pimpinan' : isOperator ? 'Administrasi sekolah' : isHomeroom ? 'Alur kerja perwalian' : isTeacher ? 'Pekerjaan mengajar' : 'Akses cepat' }}</h2></div><span class="workflow-count">{{ secondaryActions.length }} langkah lanjutan</span></div>
                <div v-if="!isAdmin && !isStudent" class="workflow-list">
                    <Link v-for="(action, index) in secondaryActions" :key="action.href" :href="action.href" class="workflow-row">
                        <span class="workflow-index">{{ String(index + 2).padStart(2, '0') }}</span><span class="workflow-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="action.icon" /></svg></span><span class="workflow-copy"><strong>{{ action.title }}</strong><span>{{ action.description }}</span></span><span class="workflow-arrow" aria-hidden="true">↗</span>
                    </Link>
                </div>
            </section>

            <section v-if="isTeacher || isHomeroom" class="attendance-trend-section" aria-labelledby="attendance-trend-title">
                <div class="section-heading">
                    <div><span class="eyebrow">Rekap kelas yang Anda ampu</span><h2 id="attendance-trend-title">Kehadiran siswa per bulan</h2></div>
                    <span class="workflow-count">Rata-rata {{ attendanceAverage }}%</span>
                </div>
                <div class="attendance-chart-card">
                    <div class="attendance-chart-meta">
                        <div><strong>Tren kehadiran</strong><span>Persentase hadir dari seluruh presensi sesi pada periode aktif.</span></div>
                        <span class="chart-legend"><i /> Hadir</span>
                    </div>
                    <div v-if="attendanceHasData" class="attendance-chart-wrap">
                        <svg class="attendance-chart" :viewBox="'0 0 ' + attendanceChart.width + ' ' + attendanceChart.height" role="img" aria-labelledby="attendance-chart-label">
                            <title id="attendance-chart-label">Grafik garis kehadiran siswa per bulan</title>
                            <g class="chart-grid">
                                <line v-for="grid in attendanceChart.grid" :key="grid.value" :x1="42" :x2="740" :y1="grid.y" :y2="grid.y" />
                                <text v-for="grid in attendanceChart.grid" :key="'label-' + grid.value" x="0" :y="grid.y + 4">{{ grid.value }}%</text>
                            </g>
                            <path v-if="attendanceChart.area" class="chart-area" :d="attendanceChart.area" />
                            <polyline class="chart-line" :points="attendanceChart.line" />
                            <g v-for="point in attendanceChart.points" :key="point.month_key" class="chart-point">
                                <circle :cx="point.x" :cy="point.y" r="5" />
                                <title>{{ point.label }}: {{ point.rate }}% hadir ({{ point.present }}/{{ point.total }})</title>
                                <text :x="point.x" y="266" text-anchor="middle">{{ point.label }}</text>
                            </g>
                        </svg>
                    </div>
                    <div v-else class="attendance-empty">Belum ada presensi siswa pada periode aktif untuk ditampilkan.</div>
                </div>
            </section>

            <footer class="dashboard-footer"><span class="footer-mark">S</span><span>School Suite</span><span class="footer-line" /><span>Ruang kerja yang mengikuti peranmu.</span></footer>
        </section>
    </AppLayout>
</template>

<style scoped>
.dashboard-view { width: 100%; max-width: 1320px; margin: 0 auto; color: var(--ink); }
.dashboard-intro { padding: 8px 2px 27px; }
.intro-kicker { display: flex; align-items: center; gap: 8px; color: var(--ink-soft); font-size: 11px; font-weight: 700; letter-spacing: .02em; }
.live-pip { width: 7px; height: 7px; background: var(--green); border-radius: 50%; box-shadow: 0 0 0 4px var(--green-soft); }.slash { color: var(--ink-faint); }
.intro-row { display: flex; align-items: end; justify-content: space-between; gap: 24px; margin-top: 18px; }.intro-row h1 { margin: 0; color: var(--ink); font-size: clamp(28px, 4vw, 44px); font-weight: 700; letter-spacing: -.045em; line-height: 1.03; }.intro-row p { max-width: 540px; margin: 12px 0 0; color: var(--ink-soft); font-size: 13px; line-height: 1.7; }
.date-stamp { display: flex; align-items: center; gap: 9px; padding: 9px 12px 9px 10px; color: var(--ink); border: 1px solid var(--line); border-radius: 13px; background: var(--surface); }.date-stamp strong { font-size: 27px; letter-spacing: -.06em; line-height: 1; }.date-stamp span { color: var(--ink-soft); font-size: 10px; font-weight: 700; line-height: 1.25; }
.command-layout { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(250px, .8fr); gap: 15px; }.focus-panel { min-height: 330px; padding: 27px 30px; color: #f7fbff; background: #172d4c; border-radius: 25px 25px 25px 7px; overflow: hidden; position: relative; box-shadow: 0 20px 38px rgba(24, 48, 78, .16); }.focus-panel.teacher-focus { background: #1d4d56; }.focus-meta { display: flex; align-items: center; justify-content: space-between; gap: 15px; color: #a7bdd5; font-size: 10px; font-weight: 700; letter-spacing: .04em; position: relative; z-index: 1; }.focus-status { display: inline-flex; align-items: center; gap: 6px; color: #c8e9d7; }.focus-status i { width: 6px; height: 6px; background: #72d29c; border-radius: 50%; }.focus-content { position: relative; z-index: 1; margin-top: 38px; max-width: 510px; }.focus-number { display: block; color: #f4c86c; font: 700 58px Georgia, serif; letter-spacing: -.06em; line-height: .9; }.focus-number-label { display: block; margin-top: 9px; color: #adc0d4; font-size: 11px; }.focus-content h2 { max-width: 390px; margin: 22px 0 8px; color: #fff; font-size: clamp(22px, 3vw, 31px); letter-spacing: -.035em; line-height: 1.1; }.focus-content p { max-width: 430px; margin: 0; color: #b6c7d8; font-size: 12px; line-height: 1.7; }.focus-action { display: inline-flex; align-items: center; gap: 13px; margin-top: 22px; padding: 11px 15px; color: #1c304b; background: #f5c86d; border-radius: 9px; font-size: 11px; font-weight: 800; text-decoration: none; }.focus-action:hover { background: #ffda89; }.focus-action span { font-size: 17px; line-height: .7; }.focus-orbit { position: absolute; border: 1px solid rgba(255,255,255,.13); border-radius: 50%; }.orbit-one { width: 390px; height: 390px; right: -145px; bottom: -190px; }.orbit-two { width: 290px; height: 290px; right: -95px; bottom: -140px; }
.insight-panel { display: flex; flex-direction: column; justify-content: space-between; min-height: 330px; padding: 24px; background: var(--surface-subtle); border: 1px solid var(--line); border-radius: 7px 25px 25px 25px; }.insight-heading { display: flex; justify-content: space-between; gap: 12px; }.insight-heading h2 { margin: 7px 0 0; color: var(--ink); font-size: 18px; letter-spacing: -.025em; }.signal { color: var(--gold); font: 700 21px Georgia, serif; }.signal-line { display: flex; align-items: start; gap: 12px; margin-top: 55px; }.signal-dot { width: 12px; height: 12px; flex: 0 0 auto; margin-top: 4px; border: 3px solid var(--gold); border-radius: 50%; box-shadow: 0 0 0 5px var(--gold-soft); }.signal-line strong { color: var(--ink); font-size: 13px; }.signal-line p { margin: 6px 0 0; color: var(--ink-soft); font-size: 11px; line-height: 1.65; }.insight-rule { height: 1px; margin: 25px 0 14px; background: var(--line); }.insight-footer { display: flex; justify-content: space-between; gap: 12px; color: var(--ink-faint); font-size: 10px; }.insight-footer strong { color: var(--ink-soft); font-size: 10px; text-align: right; }
.metrics-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 18px; }
.metric-item { position: relative; min-width: 0; min-height: 118px; padding: 17px 18px 16px; overflow: hidden; background: var(--surface); border: 1px solid var(--line); border-radius: 16px 16px 16px 5px; box-shadow: var(--shadow-sm); }
.metric-item::before { position: absolute; top: 0; left: 0; width: 52px; height: 3px; content: ''; background: #3269bc; }
.metric-tone-2::before { background: #3d9b78; }.metric-tone-3::before { background: #ad7c2b; }.metric-tone-4::before { background: #8264b5; }
.metric-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }.metric-item span, .metric-item small { display: block; color: var(--ink-soft); font-size: 10px; }.metric-head > span:first-child { color: var(--ink); font-size: 13px; font-weight: 800; letter-spacing: -.015em; }.metric-arrow { color: var(--ink-faint) !important; font-size: 14px !important; line-height: 1; }.metric-item strong { display: block; margin: 15px 0 5px; color: var(--ink); font-size: 27px; letter-spacing: -.05em; line-height: 1; }.metric-item small { color: var(--ink-faint); font-size: 10px; }
.workflow-section { margin-top: 34px; }.section-heading { display: flex; align-items: end; justify-content: space-between; gap: 15px; margin-bottom: 12px; }.section-heading h2 { margin: 6px 0 0; color: var(--ink); font-size: 20px; letter-spacing: -.035em; }.workflow-count { color: var(--ink-faint); font-size: 10px; }.workflow-list { border-top: 1px solid var(--line); }.workflow-row { display: flex; align-items: center; gap: 16px; min-height: 76px; padding: 12px 4px; color: inherit; text-decoration: none; border-bottom: 1px solid var(--line); transition: padding .18s ease, background .18s ease; }.workflow-row:hover { padding-left: 12px; padding-right: 12px; background: var(--surface-subtle); }.workflow-index { width: 23px; color: var(--ink-faint); font: 700 11px Georgia, serif; }.workflow-icon { display: grid; place-items: center; width: 37px; height: 37px; flex: 0 0 auto; color: var(--blue); background: var(--blue-soft); border-radius: 10px 10px 10px 4px; }.workflow-icon svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }.workflow-copy { display: grid; gap: 4px; flex: 1; }.workflow-copy strong { color: var(--ink); font-size: 12px; }.workflow-copy span { color: var(--ink-soft); font-size: 11px; line-height: 1.4; }.workflow-arrow { color: var(--ink-faint); font-size: 18px; }.dashboard-footer { display: flex; align-items: center; gap: 8px; margin-top: 38px; color: var(--ink-faint); font-size: 10px; }.footer-mark { display: grid; place-items: center; width: 20px; height: 20px; color: #1b304d; background: #edc36c; border-radius: 6px 6px 6px 2px; font: 800 12px Georgia, serif; }.footer-line { width: 30px; height: 1px; margin-left: 5px; background: var(--line); }
.attendance-trend-section { margin-top: 34px; }.attendance-chart-card { padding: 20px 22px 12px; background: var(--surface); border: 1px solid var(--line); border-radius: 18px 18px 18px 5px; box-shadow: var(--shadow-sm); }.attendance-chart-meta { display: flex; align-items: start; justify-content: space-between; gap: 16px; }.attendance-chart-meta strong { display: block; color: var(--ink); font-size: 14px; }.attendance-chart-meta span:not(.chart-legend) { display: block; margin-top: 5px; color: var(--ink-soft); font-size: 11px; line-height: 1.5; }.chart-legend { display: inline-flex; align-items: center; gap: 7px; color: var(--ink-soft); font-size: 10px; white-space: nowrap; }.chart-legend i { width: 9px; height: 9px; background: var(--blue); border-radius: 50%; }.attendance-chart-wrap { width: 100%; margin-top: 14px; overflow-x: auto; }.attendance-chart { display: block; width: 100%; min-width: 540px; height: auto; overflow: visible; }.chart-grid line { stroke: var(--line); stroke-dasharray: 3 5; }.chart-grid text { fill: var(--ink-faint); font-size: 10px; }.chart-area { fill: color-mix(in srgb, var(--blue) 12%, transparent); }.chart-line { fill: none; stroke: var(--blue); stroke-linecap: round; stroke-linejoin: round; stroke-width: 3; }.chart-point circle { fill: var(--surface); stroke: var(--blue); stroke-width: 3; }.chart-point text { fill: var(--ink-soft); font-size: 10px; }.attendance-empty { display: grid; min-height: 180px; place-items: center; color: var(--ink-faint); font-size: 11px; text-align: center; }
.admin-overview-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }
.student-overview-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 15px; }
.student-overview-card { min-width: 0; min-height: 230px; padding: 19px; color: var(--ink); background: var(--surface); border: 1px solid var(--line); border-radius: 18px 18px 18px 5px; box-shadow: var(--shadow-sm); }
.student-overview-card > header { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.student-card-kicker { color: var(--ink); font-size: 14px; font-weight: 800; }
.student-overview-card > header a { color: var(--blue); font-size: 10px; font-weight: 700; text-decoration: none; white-space: nowrap; }
.student-overview-card > header a:hover { text-decoration: underline; }
.attendance-rate { display: flex; align-items: baseline; gap: 8px; margin-top: 26px; }
.attendance-rate strong { color: var(--ink); font-size: 38px; letter-spacing: -.06em; line-height: 1; }
.attendance-rate span, .student-overview-card > p { color: var(--ink-soft); font-size: 11px; }
.attendance-meter { height: 8px; margin-top: 14px; overflow: hidden; background: var(--surface-subtle); border-radius: 999px; }
.attendance-meter span { display: block; height: 100%; background: var(--green); border-radius: inherit; transition: width .2s ease; }
.student-overview-card > p { margin: 13px 0 0; line-height: 1.5; }
.assignment-counts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 25px; }
.assignment-counts > div { display: grid; gap: 7px; padding: 13px; background: var(--surface-subtle); border-radius: 12px 12px 12px 4px; }
.assignment-counts strong { color: var(--ink); font-size: 27px; line-height: 1; }
.assignment-counts span { color: var(--ink-soft); font-size: 10px; line-height: 1.35; }
.student-announcement-list { display: grid; gap: 0; margin: 15px 0 0; padding: 0; list-style: none; }
.student-announcement-list li { display: grid; gap: 5px; min-width: 0; padding: 10px 0; border-top: 1px solid var(--line); }
.student-announcement-list strong { overflow: hidden; color: var(--ink); font-size: 11px; text-overflow: ellipsis; white-space: nowrap; }
.student-announcement-list span, .student-announcement-empty { color: var(--ink-faint); font-size: 10px; }
.student-announcement-empty { margin-top: 25px !important; }
.admin-overview-card { min-height: 0; padding: 18px; color: inherit; border: 1px solid var(--line); border-radius: 18px 18px 18px 5px; }
.announcement-card { background: var(--gold-soft); border-color: var(--line); }.login-card { background: var(--blue-soft); border-color: var(--line); }
.admin-card-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; }.admin-card-title { display: flex; align-items: center; gap: 12px; min-width: 0; }.admin-card-icon { display: grid; place-items: center; width: 42px; height: 42px; flex: 0 0 auto; border-radius: 12px 12px 12px 4px; }.announcement-card .admin-card-icon { color: var(--gold); background: color-mix(in srgb, var(--gold) 18%, var(--surface)); }.login-card .admin-card-icon { color: var(--blue); background: color-mix(in srgb, var(--blue) 18%, var(--surface)); }
.admin-card-icon svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }.admin-card-copy { display: grid; gap: 4px; min-width: 0; }.admin-card-copy strong { color: var(--ink); font-size: 14px; }.admin-card-copy span { color: var(--ink-soft); font-size: 11px; line-height: 1.45; }.admin-card-link { color: var(--blue); font-size: 10px; font-weight: 800; text-decoration: none; white-space: nowrap; }.admin-card-link:hover { text-decoration: underline; }
.admin-focus-content { display: flex; min-height: 205px; margin-top: 38px; flex-direction: column; justify-content: center; }.admin-list { display: grid; gap: 0; margin: 16px 0 0; padding: 0 4px 0 0; list-style: none; border-top: 1px solid var(--line); }.admin-list li { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--line); }.admin-list li > span { display: grid; gap: 3px; min-width: 0; }.admin-list strong { overflow: hidden; color: var(--ink); font-size: 11px; text-overflow: ellipsis; white-space: nowrap; }.admin-list small, .admin-list time { color: var(--ink-faint); font-size: 9px; }.admin-list time { flex: 0 0 auto; text-align: right; }.login-success { color: var(--green) !important; }.login-failed { color: var(--red) !important; }.admin-empty { color: var(--ink-faint); font-size: 11px; }
.focus-panel.principal-focus { background: #3d3158; }
.focus-panel.student-focus { background: #375342; }
.focus-panel.operator-focus { background: #1c3d5a; }
.eyebrow { color: var(--primary); font-size: 10px; font-weight: 800; letter-spacing: .045em; }
.principal-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin: 0 0 15px; color: var(--ink-soft); font-size: 12px; }
.principal-toolbar strong { margin-left: 7px; color: var(--ink); font-weight: 750; }
.principal-periods { display: inline-flex; gap: 3px; padding: 4px; background: var(--surface); border: 1px solid var(--line); border-radius: 11px; }
.principal-periods a { display: inline-flex; align-items: center; min-height: 36px; padding: 0 14px; color: var(--ink-soft); border-radius: 7px; font-size: 12px; font-weight: 700; text-decoration: none; }
.principal-periods a.active { color: var(--primary-strong); background: var(--primary-soft); }
.principal-command { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(300px, .9fr); gap: 16px; }
.principal-lead { position: relative; min-height: 316px; padding: 28px 30px; overflow: hidden; color: #fff; background: #3d3158; border-radius: var(--radius-lg); }
.principal-lead::after { position: absolute; right: -120px; bottom: -210px; width: 360px; height: 360px; content: ''; border: 1px solid rgba(255,255,255,.19); border-radius: 50%; box-shadow: 0 0 0 45px rgba(255,255,255,.045), 0 0 0 100px rgba(255,255,255,.035); pointer-events: none; }
.principal-lead-top { display: flex; justify-content: space-between; gap: 15px; color: #e9e0f4; font-size: 12px; font-weight: 700; }
.principal-lead-number { position: relative; z-index: 1; margin-top: 26px; color: #f4ca74; font: 700 clamp(56px, 6vw, 76px)/.95 Georgia, serif; letter-spacing: -.055em; }
.principal-lead h2 { position: relative; z-index: 1; margin: 18px 0 6px; font-size: clamp(21px, 2.4vw, 28px); line-height: 1.15; letter-spacing: -.035em; }
.principal-lead p { position: relative; z-index: 1; max-width: 460px; margin: 0; color: #e3dceb; font-size: 13px; line-height: 1.6; }
.principal-lead-link { position: relative; z-index: 1; display: inline-flex; align-items: center; justify-content: space-between; gap: 14px; min-height: 42px; margin-top: 21px; padding: 0 15px; color: #302347; background: #f4ca74; border-radius: 9px; font-size: 12px; font-weight: 800; text-decoration: none; }
.principal-lead-link:hover { background: #ffda91; }
.principal-attention { display: flex; min-width: 0; min-height: 316px; flex-direction: column; padding: 22px; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); }
.principal-attention-title { display: flex; align-items: start; justify-content: space-between; gap: 14px; }
.principal-attention-title span, .principal-panel-heading > div > span, .principal-section-heading > div > span { color: var(--ink-soft); font-size: 11px; font-weight: 700; }
.principal-attention-title h2 { margin: 5px 0 0; color: var(--ink); font-size: 20px; letter-spacing: -.035em; }
.principal-attention-title > strong { display: grid; place-items: center; min-width: 32px; height: 32px; padding: 0 5px; color: var(--gold); background: var(--gold-soft); border-radius: 10px; font-size: 15px; }
.principal-alert-list { display: grid; gap: 0; margin: 16px 0 0; padding: 0; list-style: none; }
.principal-alert-list li { display: grid; grid-template-columns: 9px minmax(0, 1fr) 32px; align-items: start; gap: 10px; padding: 11px 0; border-top: 1px solid var(--line); }
.principal-alert-dot { width: 8px; height: 8px; margin-top: 5px; background: var(--gold); border-radius: 50%; }
.principal-alert-list .alert-high .principal-alert-dot { background: var(--red); }
.principal-alert-list .alert-info .principal-alert-dot { background: var(--blue); }
.principal-alert-list strong { color: var(--ink); font-size: 12px; line-height: 1.4; }
.principal-alert-list p { margin: 4px 0 0; color: var(--ink-soft); font-size: 11px; line-height: 1.5; }
.principal-alert-list a { display: grid; place-items: center; min-height: 32px; color: var(--blue); font-size: 17px; text-decoration: none; }
.principal-attention-empty { margin: auto 0; color: var(--ink-soft); font-size: 13px; line-height: 1.5; }
.principal-alert-more { margin-top: auto; padding-top: 8px; color: var(--ink-soft); font-size: 11px; }
.principal-overview { margin-top: 34px; }
.principal-charts { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(300px, .85fr); gap: 16px; }
.principal-chart-panel, .principal-grade-panel, .principal-news { min-width: 0; padding: 22px; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); }
.principal-panel-heading, .principal-section-heading { display: flex; align-items: start; justify-content: space-between; gap: 18px; }
.principal-panel-heading h2, .principal-section-heading h2 { margin: 5px 0 0; color: var(--ink); font-size: 21px; letter-spacing: -.035em; }
.principal-panel-heading p { margin: 6px 0 0; color: var(--ink-soft); font-size: 12px; line-height: 1.5; }
.principal-panel-heading > a, .principal-section-heading > a { flex: 0 0 auto; min-height: 32px; padding: 7px 0; color: var(--blue); font-size: 11px; font-weight: 750; text-decoration: none; white-space: nowrap; }
.principal-panel-heading > a:hover, .principal-section-heading > a:hover { text-decoration: underline; }
.principal-chart-wrap { width: 100%; margin-top: 18px; overflow-x: auto; }
.principal-chart { display: block; width: 100%; min-width: 500px; height: auto; }
.principal-grade-list { display: grid; gap: 21px; margin-top: 28px; }
.principal-grade-meta { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; align-items: center; gap: 9px; }
.principal-grade-meta strong { color: var(--ink); font-size: 13px; }
.principal-grade-meta span { color: var(--ink-soft); font-size: 11px; }
.principal-grade-meta b { min-width: 45px; color: var(--ink); font-size: 13px; text-align: right; }
.principal-grade-track, .principal-activity-track { height: 8px; margin-top: 10px; overflow: hidden; background: var(--surface-strong); border-radius: 999px; }
.principal-grade-track > span, .principal-activity-track > i { display: block; height: 100%; background: var(--blue); border-radius: inherit; }
.principal-grade-row p { margin: 7px 0 0; color: var(--ink-faint); font-size: 11px; }
.principal-empty { margin: 22px 0; color: var(--ink-soft); font-size: 12px; line-height: 1.5; }
.principal-activity { margin-top: 30px; }
.principal-section-heading { align-items: end; margin-bottom: 14px; }
.principal-section-heading > span { color: var(--ink-faint); font-size: 11px; }
.principal-activity-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); overflow: hidden; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); }
.principal-activity-item { display: flex; min-width: 0; min-height: 206px; flex-direction: column; padding: 19px; color: var(--ink); border-right: 1px solid var(--line); text-decoration: none; }
.principal-activity-item:last-child { border-right: 0; }
.principal-activity-item:hover { background: var(--surface-subtle); }
.principal-activity-item > span { color: var(--ink-soft); font-size: 11px; font-weight: 750; }
.principal-activity-item > strong { margin-top: 12px; color: var(--ink); font: 700 34px/.95 Georgia, serif; }
.principal-activity-item h3 { margin: 13px 0 3px; color: var(--ink); font-size: 13px; }
.principal-activity-item p { min-height: 33px; margin: 0; color: var(--ink-soft); font-size: 11px; line-height: 1.5; }
.principal-activity-item .principal-activity-track { width: 100%; height: 6px; margin-top: auto; }
.principal-activity-item small { margin-top: 12px; color: var(--blue); font-size: 11px; font-weight: 750; }
.principal-news { margin-top: 30px; }
.principal-news .principal-section-heading { margin-bottom: 5px; }
.principal-news ol { margin: 0; padding: 0; list-style: none; }
.principal-news li { display: grid; grid-template-columns: minmax(0, 1fr) minmax(120px, .32fr) auto; align-items: center; gap: 16px; min-height: 49px; border-top: 1px solid var(--line); }
.principal-news li strong { overflow: hidden; color: var(--ink); font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
.principal-news li span, .principal-news li time { color: var(--ink-soft); font-size: 11px; }
.dashboard-view a:focus-visible { outline: 3px solid var(--blue); outline-offset: 3px; }
.metrics-first { margin: 0 0 18px; }
.admin-command .focus-panel, .admin-command .insight-panel { min-height: 220px; }
.admin-command .admin-focus-content { min-height: 0; margin-top: 20px; justify-content: flex-start; }
.admin-command .focus-content h2 { margin-top: 0; }
.insight-panel .signal-line { align-items: center; margin-top: 20px; padding: 12px; color: inherit; text-decoration: none; background: var(--surface); border: 1px solid var(--line); border-radius: 12px; }
.insight-panel .signal-line:hover { border-color: var(--primary); }
.signal-line > span:nth-child(2) { display: grid; gap: 4px; min-width: 0; flex: 1; }
.signal-line .signal-description { color: var(--ink-soft); font-size: 12px; line-height: 1.45; }
.signal-arrow { color: var(--blue); font-size: 18px; }
@media (max-width: 560px) {
    .dashboard-intro { padding-bottom: 17px; }
    .admin-command .focus-panel { min-height: 245px; }
    .admin-command .insight-panel { min-height: 175px; }
    .admin-command .focus-content { margin-top: 20px; }
    .admin-command .focus-content h2 { font-size: 24px; }
    .admin-command .focus-action { min-height: 44px; }
    .insight-panel .signal-line { margin-top: 14px; }
}
@media (max-width: 1080px) { .principal-charts { grid-template-columns: 1fr; }.principal-activity-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }.principal-activity-item:nth-child(2) { border-right: 0; }.principal-activity-item:nth-child(-n+2) { border-bottom: 1px solid var(--line); } }
@media (max-width: 1000px) { .student-overview-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }.student-announcements-card { grid-column: 1 / -1; } }
@media (max-width: 800px) { .command-layout, .principal-command { grid-template-columns: 1fr; }.insight-panel { min-height: 225px; }.signal-line { margin-top: 30px; }.metrics-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }.admin-overview-grid, .student-overview-grid { grid-template-columns: 1fr; }.student-announcements-card { grid-column: auto; }.attendance-chart-meta { flex-direction: column; gap: 10px; } }
@media (max-width: 560px) { .intro-row { align-items: start; }.intro-row h1 { font-size: 30px; }.date-stamp { display: none; }.focus-panel { min-height: 360px; padding: 23px; }.focus-content { margin-top: 32px; }.metrics-strip { grid-template-columns: 1fr 1fr; gap: 9px; }.metric-item { min-height: 105px; padding: 14px 12px; }.metric-item strong { font-size: 24px; }.workflow-row { gap: 10px; }.workflow-index { display: none; }.workflow-copy span { max-width: 235px; }.dashboard-footer { margin-top: 28px; }.principal-toolbar { align-items: start; flex-direction: column; }.principal-lead, .principal-attention, .principal-chart-panel, .principal-grade-panel, .principal-news { padding: 19px; }.principal-lead { min-height: 300px; }.principal-panel-heading { align-items: start; flex-direction: column; gap: 6px; }.principal-grade-meta { grid-template-columns: minmax(0, 1fr) auto; }.principal-grade-meta span { grid-column: 1; grid-row: 2; }.principal-grade-meta b { grid-column: 2; grid-row: 1 / 3; }.principal-activity-grid { grid-template-columns: 1fr; }.principal-activity-item { min-height: 182px; border-right: 0; border-bottom: 1px solid var(--line); }.principal-activity-item:last-child { border-bottom: 0; }.principal-news li { grid-template-columns: 1fr auto; gap: 2px 10px; padding: 10px 0; }.principal-news li strong { grid-column: 1 / -1; white-space: normal; }.principal-news li time { text-align: right; } }
@media (prefers-reduced-motion: reduce) { .workflow-row { transition: none; } }
</style>
