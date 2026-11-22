<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = withDefaults(defineProps<{ mode?: 'sidebar' | 'topbar' }>(), { mode: 'sidebar' });

interface NavItem {
    name: string;
    href: string;
    key: string;
    icon: string;
    activePrefix?: string;
    activePrefixes?: string[];
    exact?: boolean;
    studentHref?: string;
    studentActivePrefix?: string;
    teacherHref?: string;
    teacherActivePrefix?: string;
    principalHref?: string;
    principalActivePrefix?: string;
    children?: NavItem[];
}

interface NavSection {
    title: string;
    icon: string;
    items: NavItem[];
}

const homeItem: NavItem = {
    name: 'Beranda',
    href: '/dashboard',
    principalHref: '/dashboard',
    principalActivePrefix: '/dashboard',
    key: 'home',
    exact: true,
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
};

const sections: NavSection[] = [
    {
        title: 'SPMB',
        icon: 'M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12zm4-6h2m-2 3h4',
        items: [
            { name: 'Dashboard & Laporan', href: '/spmb', key: 'admissions', exact: true, activePrefixes: ['/spmb/laporan'], icon: 'M3 13h4V3H3v10zm7 8h4V3h-4v18zm7-5h4V3h-4v13z' },
            { name: 'Pendaftar & Verifikasi', href: '/spmb/pendaftar', key: 'admissions-applicants', activePrefixes: ['/spmb/pendaftar', '/spmb/berkas'], icon: 'M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0H5z' },
            { name: 'Seleksi & Daftar Ulang', href: '/spmb/seleksi', key: 'admissions-selection', activePrefixes: ['/spmb/seleksi', '/spmb/pengumuman', '/spmb/daftar-ulang'], icon: 'M4 19h16M7 16V9m5 7V5m5 11v-3' },
            { name: 'Pengaturan', href: '/spmb/pengaturan', key: 'admissions-settings', activePrefixes: ['/spmb/pengaturan', '/spmb/periode', '/spmb/jalur', '/spmb/panitia'], icon: 'M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12' },
        ],
    },
    {
        title: 'Akademik',
        icon: 'M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 12h.01M9 15h.01M15 9h.01M15 12h.01M15 15h.01',
        items: [
            { name: 'Data Siswa', href: '/akademik/siswa', key: 'students', activePrefix: '/akademik/siswa', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
            { name: 'Buku Induk', href: '/akademik/buku-induk', key: 'student-ledger', activePrefix: '/akademik/buku-induk', icon: 'M5 4.75A2.25 2.25 0 017.25 2.5H19v18H7.25A2.25 2.25 0 015 18.25v-13.5ZM5 18.25A2.25 2.25 0 017.25 16H19M9 6h6m-6 3h6m-6 3h4' },
            { name: 'Data Guru', href: '/akademik/guru', key: 'teachers', activePrefix: '/akademik/guru', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
            { name: 'Rombel / Kelas', href: '/akademik/kelas', key: 'classes', activePrefix: '/akademik/kelas', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
            { name: 'Jadwal Pelajaran', href: '/akademik/jadwal', key: 'schedules', activePrefix: '/akademik/jadwal', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
            { name: 'Absensi Guru', href: '/akademik/absensi-guru', key: 'teacher-attendance', activePrefix: '/akademik/absensi-guru', icon: 'M8 7V3m8 4V3M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2zm3 8 2 2 4-4' },
            { name: 'Izin & Cuti Guru', href: '/akademik/izin-guru', key: 'teacher-leave', activePrefix: '/akademik/izin-guru', icon: 'M8 2v4m8-4v4M3 10h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2zm4 9h3m-3 4h6' },
            { name: 'Absensi Siswa', href: '/akademik/presensi', key: 'attendance', activePrefix: '/akademik/presensi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
            { name: 'Rekap Absensi', href: '/akademik/presensi/rekap', principalHref: '/kepsek/laporan/rekap-absensi', key: 'attendance-report', activePrefix: '/akademik/presensi/rekap', principalActivePrefix: '/kepsek/laporan/rekap-absensi', icon: 'M9 17v-2m3 2v-4m3 4v-6m4 10H5a2 2 0 01-2-2V5a2 2 0 012 2v14z' },
            { name: 'Kartu RFID / QR Siswa', href: '/akademik/kartu-siswa', key: 'student-cards', activePrefix: '/akademik/kartu-siswa', icon: 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zm4 4h4m-4 3h8' },
            { name: 'Kios Presensi', href: '/kios-presensi', key: 'attendance-kiosk', activePrefix: '/kios-presensi', icon: 'M4 7V4h3M20 7V4h-3M4 17v3h3m13-3v3h-3M7 12h10' },
            { name: 'Jurnal Guru', href: '/akademik/jurnal', key: 'journals', activePrefix: '/akademik/jurnal', icon: 'M5 3h14v18H5zM8 7h8M8 11h8M8 15h5M3 6v15h14' },
            { name: 'Kelas Daring', href: '/akademik/kelas-daring', key: 'online-classes', activePrefix: '/akademik/kelas-daring', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
            { name: 'Siklus Siswa', href: '/akademik/siklus-siswa', key: 'student-lifecycle', activePrefix: '/akademik/siklus-siswa', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' },
            { name: 'Mutasi Siswa', href: '/akademik/mutasi', key: 'student-mutations', activePrefix: '/akademik/mutasi', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
            { name: 'Kelulusan & SKL', href: '/akademik/kelulusan', key: 'student-graduation', activePrefix: '/akademik/kelulusan', icon: 'M12 14l9-5-9-5-9 5 9 5zm0 0v6m-4-2.5V20l4 2 4-2v-2.5' },
            { name: 'Alumni & Tracer', href: '/akademik/alumni', key: 'alumni-moderation', activePrefix: '/akademik/alumni', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2' },
            { name: 'Pengaturan Akademik', href: '/akademik/pengaturan', key: 'academic-settings', activePrefix: '/akademik/pengaturan', icon: 'M4 6h16M4 12h16M4 18h16' },
        ],
    },
    {
        title: 'Pembelajaran LMS',
        icon: 'M4 5.5A2.5 2.5 0 016.5 3H20v16H6.5A2.5 2.5 0 014 16.5v-11zM4 16.5A2.5 2.5 0 016.5 14H20M8 7h8M8 10h6',
        items: [
            { name: 'Materi', href: '/pembelajaran/materi', studentHref: '/siswa/materi', key: 'learning-materials', activePrefix: '/pembelajaran/materi', studentActivePrefix: '/siswa/materi', icon: 'M4 4h13a3 3 0 013 3v13H7a3 3 0 01-3-3V4zm3 0v16m3-12h7m-7 4h7m-7 4h5' },
            { name: 'Tugas', href: '/pembelajaran/tugas', studentHref: '/siswa/tugas', key: 'learning-assignments', activePrefix: '/pembelajaran/tugas', studentActivePrefix: '/siswa/tugas', icon: 'M9 5h6m-8 4h10m-10 4h10m-8 4h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z' },
            { name: 'Progress Belajar', href: '/pembelajaran/progress', studentHref: '/siswa/progress', key: 'learning-progress', activePrefix: '/pembelajaran/progress', studentActivePrefix: '/siswa/progress', icon: 'M9 17v-6m4 6V7m4 10V4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2v14a2 2 0 002 2z' },
        ],
    },
    {
        title: 'Asesmen & CBT',
        icon: 'M8 5h8m-8 4h8m-8 4h4m-8 8h16V3H4v18zm2-6 2 2 4-4',
        items: [
            { name: 'Bank Soal', href: '/penilaian/bank-soal', key: 'question-bank', activePrefix: '/penilaian/bank-soal', icon: 'M4 5h16M4 10h16M4 15h16M4 20h16M7 3v4m5 1v4m5 1v4' },
            { name: 'Paket Soal', href: '/penilaian/paket-soal', key: 'question-packs', activePrefix: '/penilaian/paket-soal', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
            { name: 'Berbagi Soal', href: '/penilaian/berbagi-soal', key: 'question-shares', activePrefix: '/penilaian/berbagi-soal', icon: 'M8 12h8m-4-4v8m8-4a4 4 0 0 1-4 4H8a4 4 0 1 1 0-8h8a4 4 0 0 1 4 4Z' },
            { name: 'Ujian / CBT', href: '/ujian', key: 'exams', activePrefix: '/ujian', icon: 'M9 12h6m-6 4h6M9 8h6m2 13H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z' },
        ],
    },
    {
        title: 'Penilaian & Rapor',
        icon: 'M4 19V5m0 14h16M8 15l3-4 3 2 5-7',
        items: [
            { name: 'Input Nilai', href: '/penilaian/skor', key: 'scores', activePrefix: '/penilaian/skor', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
            { name: 'Penilaian Sikap', href: '/penilaian/sikap', key: 'attitudes', activePrefix: '/penilaian/sikap', icon: 'M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 00-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 00-.1-7.8z' },
            { name: 'Skema Penilaian', href: '/penilaian/skema', key: 'schemes', activePrefix: '/penilaian/skema', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
            { name: 'Rapor Siswa', href: '/penilaian/rapor', principalHref: '/kepsek/laporan/nilai', key: 'reports', activePrefix: '/penilaian/rapor', activePrefixes: ['/penilaian/rapor', '/penilaian/ledger', '/kepsek/laporan/nilai'], principalActivePrefix: '/kepsek/laporan/nilai', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
            { name: 'Analisis', href: '/penilaian/analisis', principalHref: '/kepsek/statistik', key: 'assessment-analytics', activePrefix: '/penilaian/analisis', principalActivePrefix: '/kepsek/statistik', icon: 'M3 3v18h18M8 15l4-4 4 3 5-7' },
            { name: 'Pusat Laporan', href: '/laporan', key: 'reporting', activePrefix: '/laporan', icon: 'M9 17v-2m3 2v-4m3 4v-6m4 10H5a2 2 0 01-2-2V5a2 2 0 012 2h14a2 2 0 012 2v14z' },
            { name: 'Kinerja Guru', href: '/laporan', principalHref: '/kepsek/performa-guru', key: 'teacher-performance', activePrefix: '/laporan', principalActivePrefix: '/kepsek/performa-guru', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0' },
        ],
    },
    {
        title: 'Perwalian',
        icon: 'M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m6-10a4 4 0 110-8 4 4 0 010 8zm8-1a3 3 0 100-6m4 17v-2a4 4 0 00-3-3.87',
        items: [
            { name: 'Ruang Wali Kelas', href: '/wali-kelas', key: 'homeroom', exact: true, activePrefix: '/wali-kelas', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
            { name: 'Catatan Observasi & BK', href: '/wali-kelas/catatan', key: 'homeroom-observations', activePrefix: '/wali-kelas/catatan', icon: 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7zm10-3a3 3 0 100 6 3 3 0 000-6z' },
            { name: 'Kedisiplinan & Prestasi', href: '/wali-kelas/kedisiplinan', key: 'homeroom-discipline', activePrefix: '/wali-kelas/kedisiplinan', icon: 'M12 3l8 3v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3zm-3 9 2 2 4-4' },
            { name: 'Verifikasi Izin Siswa', href: '/wali-kelas/izin', key: 'homeroom-permits', activePrefix: '/wali-kelas/izin', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
        ],
    },
    {
        title: 'Piket, Pos & BK',
        icon: 'M12 3l8 3v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3z',
        items: [
            { name: 'Izin Keluar Piket', href: '/piket/izin-keluar', key: 'picket-passes', activePrefix: '/piket/izin-keluar', icon: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1' },
            { name: 'Pos Jaga Satpam', href: '/pos-jaga', key: 'security-gate', activePrefix: '/pos-jaga', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
            { name: 'Kasus Disiplin BK', href: '/bk/kasus', key: 'bk-cases', activePrefix: '/bk/kasus', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
            { name: 'Antrean Satgas Aduan', href: '/satgas/aduan', key: 'task-force-complaints', activePrefix: '/satgas/aduan', icon: 'M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z' },
            { name: 'Kelola Perpustakaan', href: '/perpustakaan/kelola', key: 'library-manage', activePrefix: '/perpustakaan/kelola', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
        ],
    },
    {
        title: 'Komunikasi',
        icon: 'M7 10h.01M12 10h.01M17 10h.01M9 16H5l-2 4v-4a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z',
        items: [
            { name: 'Pengumuman & Agenda', href: '/komunikasi', teacherHref: '/guru/pengumuman', principalHref: '/kepsek/pengumuman', key: 'communication', exact: true, activePrefix: '/komunikasi', teacherActivePrefix: '/guru/pengumuman', principalActivePrefix: '/kepsek/pengumuman', icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z' },
            { name: 'Diskusi Chat', href: '/komunikasi/chat', teacherHref: '/guru/chat', principalHref: '/kepsek/kalender', key: 'communication-chat', activePrefix: '/komunikasi/chat', teacherActivePrefix: '/guru/chat', principalActivePrefix: '/kepsek/kalender', icon: 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 002 2v6a2 2 0 002 2h2v4l.586-.586z' },
        ],
    },
    {
        title: 'Tata Usaha',
        icon: 'M3 8h18v13H3zM7 8V5a2 2 0 012-2h6a2 2 0 012 2v3m-8 5h8m-8 4h5',
        items: [
            {
                name: 'Persuratan',
                href: '/administrasi/persuratan',
                key: 'correspondence',
                activePrefix: '/administrasi/persuratan',
                icon: 'M7 3h7l5 5v13H5V3h2zm7 0v5h5M8 12h8m-8 4h8',
                children: [
                    { name: 'Dashboard Persuratan', href: '/administrasi/persuratan', key: 'correspondence-dashboard', exact: true, icon: 'M3 13h4V3H3v10zm7 8h4V3h-4v18zm7-5h4V3h-4v13' },
                    { name: 'Surat Masuk', href: '/administrasi/persuratan/surat-masuk', key: 'correspondence-incoming', activePrefix: '/administrasi/persuratan/surat-masuk', icon: 'M4 4h16v16H4zM7 8h10M7 12h7M7 16h5' },
                    { name: 'Surat Keluar', href: '/administrasi/persuratan/surat-keluar', key: 'correspondence-outgoing', activePrefix: '/administrasi/persuratan/surat-keluar', icon: 'M4 4h16v16H4zM7 8h10M7 12h6M7 16h4' },
                    { name: 'Arsip', href: '/administrasi/persuratan/arsip', key: 'correspondence-archive', activePrefix: '/administrasi/persuratan/arsip', icon: 'M4 5h16v14H4zM8 9h8m-7 4h6' },
                ],
            },
            { name: 'Pelayanan PTSP', href: '/administrasi/ptsp', key: 'ptsp', activePrefix: '/administrasi/ptsp', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 12h6m-6 4h4' },
            { name: 'Legalisir Ijazah', href: '/administrasi/legalisir', key: 'legalization', activePrefix: '/administrasi/legalisir', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622' },
            { name: 'Buku Tamu Digital', href: '/administrasi/buku-tamu', key: 'guestbook', activePrefix: '/administrasi/buku-tamu', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 20a8 8 0 0116 0M19 8v6m-3-3h6' },
            { name: 'Tugas Staf', href: '/administrasi/tugas', key: 'staff-tasks', activePrefix: '/administrasi/tugas', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5h6m-6 7h6m-6 4h6' },
            { name: 'Proposal Kegiatan Guru', href: '/guru/proposal', key: 'teacher-proposals', activePrefix: '/guru/proposal', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
            { name: 'Proposal & LPJ Mandiri', href: '/layanan/proposal', key: 'independent-proposals', activePrefix: '/layanan/proposal', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
            { name: 'Telaah Proposal & LPJ', href: '/administrasi/proposal', key: 'proposal-review', activePrefix: '/administrasi/proposal', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
            { name: 'Sarana & Prasarana', href: '/administrasi/sarpras', key: 'facilities', activePrefix: '/administrasi/sarpras', icon: 'M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01M9 18v.01' },
        ],
    },
    {
        title: 'Pengaturan',
        icon: 'M12 8a4 4 0 100 8 4 4 0 000-8zm0-5v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64',
        items: [
            {
                name: 'Akun & Keamanan',
                href: '/profil',
                key: 'account-settings',
                activePrefix: '/profil',
                icon: 'M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0M19 8v4m-2-2h4',
                children: [
                    { name: 'Profil Saya', href: '/profil', key: 'profile', activePrefix: '/profil', icon: 'M5.121 17.804A9 9 0 1118.88 17.804M15 11a3 3 0 11-6 0 3 3 0 016 0z' },
                    { name: 'Keamanan', href: '/pengaturan/keamanan', key: 'security', activePrefix: '/pengaturan/keamanan', icon: 'M12 11c1.657 0 3-1.343 3-3V5a3 3 0 00-6 0v3c0 1.657 1.343 3 3 3zm0 0v2m-7 8h14a2 2 0 002-2v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2z' },
                ],
            },
            { name: 'Pengguna', href: '/pengaturan/pengguna', key: 'users', activePrefix: '/pengaturan/pengguna', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
            {
                name: 'Sekolah',
                href: '/pengaturan/sekolah',
                key: 'school-settings',
                activePrefix: '/pengaturan/sekolah',
                icon: 'M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 12h.01M9 15h.01M15 9h.01M15 12h.01M15 15h.01',
                children: [
                    { name: 'Profil Sekolah', href: '/pengaturan/sekolah', key: 'school', activePrefix: '/pengaturan/sekolah', icon: 'M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 12h.01M9 15h.01M15 9h.01M15 12h.01M15 15h.01' },
                    { name: 'Struktur Organisasi', href: '/pengaturan/struktur-organisasi', key: 'organization', activePrefix: '/pengaturan/struktur-organisasi', icon: 'M7 20h10M9 20v-4h6v4M12 16V4m0 0L8 7m4-3 4 3M5 11h4m6 0h4' },
                ],
            },
            {
                name: 'Publikasi',
                href: '/pengaturan/berita',
                key: 'publication-settings',
                activePrefix: '/pengaturan/berita',
                icon: 'M4 5h16v14H4zM7 9h10M7 13h7M7 17h4',
                children: [
                    { name: 'Berita Sekolah', href: '/pengaturan/berita', key: 'news', activePrefix: '/pengaturan/berita', icon: 'M4 5h16M4 9h16M4 13h10M4 17h8M3 3h18v18H3z' },
                    { name: 'Galeri Sekolah', href: '/pengaturan/galeri', key: 'gallery', activePrefix: '/pengaturan/galeri', icon: 'M4 5h16v14H4zM4 16l4-4 3 3 3-4 6 5M8 9h.01' },
                    { name: 'Pusat Unduh', href: '/pengaturan/pusat-unduh', key: 'downloads', activePrefix: '/pengaturan/pusat-unduh', icon: 'M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3' },
                    { name: 'Zona Integritas & IKM', href: '/pengaturan/zona-integritas', key: 'integrity-zone', activePrefix: '/pengaturan/zona-integritas', icon: 'M12 3l8 3v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3zm-3 9 2 2 4-4' },
                ],
            },
            { name: 'Integrasi & Kanal', href: '/pengaturan/integrasi', key: 'integration-settings', activePrefix: '/pengaturan/integrasi', icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' },
        ],
    },
];

const page = usePage();
const currentUrl = computed(() => page.url);
const userId = computed(() => (page.props.auth as any)?.user?.id as number | undefined);
const currentRole = computed(() => (page.props.auth as any)?.user?.role as string | undefined);
const displayName = (item: NavItem) => {
    if (currentRole.value === 'student') {
        return ({
            schedules: 'Jadwal Saya',
            'online-classes': 'Kelas Daring',
            'learning-materials': 'Materi Pelajaran',
            'learning-assignments': 'Tugas Saya',
            'learning-progress': 'Progress Belajar',
            exams: 'Ujian CBT',
            reports: 'Rapor Saya',
            communication: 'Pengumuman & Agenda',
            'communication-chat': 'Diskusi Kelas',
            profile: 'Profil Saya',
        } as Record<string, string>)[item.key] ?? item.name;
    }

    if (currentRole.value !== 'principal') return item.name;

    return ({
        reports: 'Rapor Siswa',
        'assessment-analytics': 'Statistik',
        reporting: 'Pusat Laporan',
        'teacher-performance': 'Kinerja Guru',
        communication: 'Pengumuman',
        'communication-chat': 'Kalender',
        'attendance-report': 'Rekap Absensi',
    } as Record<string, string>)[item.key] ?? item.name;
};

const resolvedItem = (item: NavItem): NavItem => {
    if (currentRole.value === 'principal' && item.principalHref) {
        return { ...item, href: item.principalHref, activePrefix: item.principalActivePrefix ?? item.principalHref };
    }

    if (['teacher', 'homeroom'].includes(currentRole.value ?? '') && item.teacherHref) {
        return { ...item, href: item.teacherHref, activePrefix: item.teacherActivePrefix ?? item.teacherHref };
    }

    if (currentRole.value !== 'student' || !item.studentHref) return item;

    return {
        ...item,
        href: item.studentHref,
        activePrefix: item.studentActivePrefix ?? item.studentHref,
    };
};

const isItemActive = (item: NavItem): boolean => {
    const rawPath = currentUrl.value.split('#')[0].split('?')[0];
    const currentPath = rawPath.length > 1 && rawPath.endsWith('/') ? rawPath.slice(0, -1) : rawPath;
    const targetHref = item.href.length > 1 && item.href.endsWith('/') ? item.href.slice(0, -1) : item.href;

    if (item.activePrefixes?.length) {
        const matchesAnyPrefix = item.activePrefixes.some((rawPrefix) => {
            const prefix = rawPrefix.length > 1 && rawPrefix.endsWith('/') ? rawPrefix.slice(0, -1) : rawPrefix;
            return currentPath === prefix || currentPath.startsWith(prefix + '/');
        });
        if (matchesAnyPrefix) return true;
    }

    if (item.exact || targetHref === '/') {
        return currentPath === targetHref;
    }
    if (item.activePrefix) {
        const prefix = item.activePrefix.length > 1 && item.activePrefix.endsWith('/')
            ? item.activePrefix.slice(0, -1)
            : item.activePrefix;

        return currentPath === prefix || currentPath.startsWith(prefix + '/');
    }
    return currentPath === targetHref;
};

const visibleKeys = computed(() => new Set<string>((page.props as any).navigation?.visible_keys ?? []));
const visibleSections = computed(() => sections
    .map((section) => ({
        ...section,
        items: section.items
            .map((item) => {
                const base = resolvedItem(item);
                if (!item.children) {
                    return visibleKeys.value.has(item.key) ? base : null;
                }

                const visibleChildren = item.children
                    .filter((child) => visibleKeys.value.has(child.key))
                    .map(resolvedItem);

                if (visibleChildren.length === 1) {
                    const singleChild = visibleChildren[0];
                    return {
                        ...singleChild,
                        name: displayName(singleChild),
                    };
                }

                if (visibleChildren.length > 1 || visibleKeys.value.has(item.key)) {
                    return {
                        ...base,
                        children: visibleChildren,
                    };
                }

                return null;
            })
            .filter((item): item is NavItem => item !== null),
    }))
    .filter((section) => section.items.length > 0));

const activeSectionKey = computed(() => visibleSections.value.find((section) =>
    section.items.some((item) => isItemActive(item) || item.children?.some((child) => isItemActive(child))),
)?.title ?? null);
const expandedGroups = ref<Record<string, boolean>>({});
const groupsLoaded = ref(false);
const navRoot = ref<HTMLElement | null>(null);
const prefersReducedMotion = () => typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const onAccordionEnter = (el: Element) => {
    const htmlEl = el as HTMLElement;
    if (prefersReducedMotion()) return;
    htmlEl.style.overflow = 'hidden';
    htmlEl.style.height = '0px';
    htmlEl.style.opacity = '0';
    // Force reflow so the browser registers the starting height.
    void htmlEl.offsetHeight;
    htmlEl.style.transition = 'height 220ms var(--ease, cubic-bezier(0.2, 0.8, 0.2, 1)), opacity 180ms ease';
    htmlEl.style.height = `${htmlEl.scrollHeight}px`;
    htmlEl.style.opacity = '1';
};

const onAccordionAfterEnter = (el: Element) => {
    const htmlEl = el as HTMLElement;
    htmlEl.style.height = '';
    htmlEl.style.overflow = '';
    htmlEl.style.opacity = '';
    htmlEl.style.transition = '';
};

const onAccordionLeave = (el: Element) => {
    const htmlEl = el as HTMLElement;
    if (prefersReducedMotion()) return;
    htmlEl.style.overflow = 'hidden';
    htmlEl.style.height = `${htmlEl.scrollHeight}px`;
    htmlEl.style.opacity = '1';
    void htmlEl.offsetHeight;
    htmlEl.style.transition = 'height 190ms var(--ease, cubic-bezier(0.2, 0.8, 0.2, 1)), opacity 150ms ease';
    htmlEl.style.height = '0px';
    htmlEl.style.opacity = '0';
};

const onAccordionAfterLeave = (el: Element) => {
    const htmlEl = el as HTMLElement;
    htmlEl.style.height = '';
    htmlEl.style.overflow = '';
    htmlEl.style.opacity = '';
    htmlEl.style.transition = '';
};
const isExpanded = (key: string, active = false) => Object.hasOwn(expandedGroups.value, key)
    ? expandedGroups.value[key] === true
    : props.mode === 'topbar' ? false : active;
const isSectionActive = (section: NavSection) => section.items.some((item) =>
    isItemActive(item) || item.children?.some((child) => isItemActive(child)),
);
const isNestedGroupActive = (item: NavItem) => item.children?.some((child) => isItemActive(child)) ?? false;
const toggleGroup = (key: string, active: boolean) => {
    const nextOpen = !isExpanded(key, active);
    const nextGroups = {
        ...expandedGroups.value,
        [key]: nextOpen,
    };

    if (props.mode === 'topbar' && nextOpen) {
        const ownerSection = visibleSections.value.find((section) =>
            section.title === key || section.items.some((item) => item.key === key),
        );

        for (const section of visibleSections.value) {
            if (section.title !== ownerSection?.title) delete nextGroups[section.title];
        }
        for (const item of ownerSection?.items ?? []) {
            if (item.key !== key) delete nextGroups[item.key];
        }
    }

    expandedGroups.value = nextGroups;
    persistExpandedGroups();
};
const closeTopbarGroups = () => {
    if (props.mode !== 'topbar' || Object.keys(expandedGroups.value).length === 0) return;
    expandedGroups.value = {};
};
const handleTopbarPointerdown = (event: PointerEvent) => {
    if (props.mode !== 'topbar') return;
    if (navRoot.value && !navRoot.value.contains(event.target as Node)) {
        closeTopbarGroups();
    }
};
const handleTopbarKeydown = (event: KeyboardEvent) => {
    if (props.mode === 'topbar' && event.key === 'Escape') {
        closeTopbarGroups();
    }
};
const groupId = (key: string) => `nav-group-${key.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;
const openActiveGroups = () => {
    if (!groupsLoaded.value || props.mode === 'topbar') return;

    const nextGroups = { ...expandedGroups.value };
    if (activeSectionKey.value) {
        nextGroups[activeSectionKey.value] = true;
    }
    const activeNested = visibleSections.value.flatMap((section) => section.items).find((item) => isNestedGroupActive(item));
    if (activeNested) {
        nextGroups[activeNested.key] = true;
    }
    if (JSON.stringify(nextGroups) !== JSON.stringify(expandedGroups.value)) {
        expandedGroups.value = nextGroups;
    }
};
const loadExpandedGroups = () => {
    groupsLoaded.value = false;
    expandedGroups.value = {};
    if (props.mode === 'topbar') {
        groupsLoaded.value = true;
        return;
    }
    try {
        const saved = localStorage.getItem(`school-suite-nav-groups:${userId.value ?? 'guest'}`);
        const parsed = saved ? JSON.parse(saved) : null;
        if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
            expandedGroups.value = Object.fromEntries(
                Object.entries(parsed).filter(([, value]) => typeof value === 'boolean'),
            ) as Record<string, boolean>;
        }
    } catch { /* Storage may be unavailable or contain invalid data. */ }
    groupsLoaded.value = true;
    openActiveGroups();
    persistExpandedGroups();
};
const persistExpandedGroups = () => {
    if (props.mode !== 'sidebar') return;
    try {
        localStorage.setItem(`school-suite-nav-groups:${userId.value ?? 'guest'}`, JSON.stringify(expandedGroups.value));
    } catch { /* Storage may be unavailable in private browsing. */ }
};

onMounted(() => {
    loadExpandedGroups();
    if (props.mode === 'topbar') {
        document.addEventListener('pointerdown', handleTopbarPointerdown);
        document.addEventListener('keydown', handleTopbarKeydown);
    }
});

onBeforeUnmount(() => {
    if (props.mode === 'topbar') {
        document.removeEventListener('pointerdown', handleTopbarPointerdown);
        document.removeEventListener('keydown', handleTopbarKeydown);
    }
});

watch(userId, (nextUserId, previousUserId) => {
    if (nextUserId !== previousUserId) loadExpandedGroups();
});

watch(currentUrl, () => {
    if (props.mode === 'topbar') {
        closeTopbarGroups();
    }
});

watch([currentUrl, activeSectionKey, visibleSections], () => {
    if (!groupsLoaded.value) return;
    openActiveGroups();
    persistExpandedGroups();
});
</script>

<template>
    <nav ref="navRoot" class="nav-stack" :class="{ 'is-topbar': props.mode === 'topbar' }" aria-label="Navigasi Utama">
        <Link
            :href="resolvedItem(homeItem).href"
            :class="['nav-item', isItemActive(resolvedItem(homeItem)) ? 'is-active' : '']"
            :aria-current="isItemActive(resolvedItem(homeItem)) ? 'page' : undefined"
            :aria-label="homeItem.name"
            :title="homeItem.name"
        >
            <svg
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="homeItem.icon" />
            </svg>
            <span>{{ homeItem.name }}</span>
        </Link>

        <template v-for="section in visibleSections" :key="section.title">
            <section class="nav-section">
                <button
                    type="button"
                    class="nav-section-trigger"
                    :class="{ 'is-active': isSectionActive(section) }"
                    :aria-expanded="isExpanded(section.title, isSectionActive(section))"
                    :aria-controls="groupId(section.title)"
                    :aria-label="section.title"
                    :title="section.title"
                    @click="toggleGroup(section.title, isSectionActive(section))"
                >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="section.icon" />
                    </svg>
                    <span class="nav-label">{{ section.title }}</span>
                    <svg class="nav-group-chevron" :class="{ 'is-expanded': isExpanded(section.title, isSectionActive(section)) }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" />
                    </svg>
                </button>
                <Transition
                    :name="props.mode === 'topbar' ? 'nav-dropdown' : undefined"
                    :css="props.mode === 'topbar'"
                    @enter="props.mode === 'sidebar' ? onAccordionEnter($event) : undefined"
                    @after-enter="props.mode === 'sidebar' ? onAccordionAfterEnter($event) : undefined"
                    @leave="props.mode === 'sidebar' ? onAccordionLeave($event) : undefined"
                    @after-leave="props.mode === 'sidebar' ? onAccordionAfterLeave($event) : undefined"
                >
                <div :id="groupId(section.title)" v-show="isExpanded(section.title, isSectionActive(section))" class="nav-section-content">
                    <template v-for="item in section.items" :key="item.key">
                        <template v-if="item.children?.length">
                            <div class="nav-nested-block">
                                <button
                                    type="button"
                                    class="nav-item nav-group-trigger"
                                    :aria-expanded="isExpanded(item.key, isNestedGroupActive(item))"
                                    :aria-controls="groupId(item.key)"
                                    :aria-label="displayName(item)"
                                    :title="displayName(item)"
                                    @click="toggleGroup(item.key, isNestedGroupActive(item))"
                                >
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="item.icon" />
                                    </svg>
                                    <span>{{ displayName(item) }}</span>
                                    <svg class="nav-group-chevron" :class="{ 'is-expanded': isExpanded(item.key, isNestedGroupActive(item)) }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>
                                <Transition
                                    :css="false"
                                    @enter="onAccordionEnter"
                                    @after-enter="onAccordionAfterEnter"
                                    @leave="onAccordionLeave"
                                    @after-leave="onAccordionAfterLeave"
                                >
                                <div :id="groupId(item.key)" v-show="isExpanded(item.key, isNestedGroupActive(item))" class="nav-submenu" role="group" :aria-label="displayName(item)">
                                    <Link
                                        v-for="child in item.children"
                                        :key="child.key"
                                        :href="child.href"
                                        :class="['nav-item', 'nav-subitem', isItemActive(child) ? 'is-active' : '']"
                                        :aria-current="isItemActive(child) ? 'page' : undefined"
                                        :aria-label="child.name"
                                        :title="child.name"
                                    >
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="child.icon" />
                                        </svg>
                                        <span>{{ child.name }}</span>
                                    </Link>
                                </div>
                                </Transition>
                            </div>
                        </template>
                        <Link
                            v-else
                            :href="item.href"
                            :class="['nav-item', isItemActive(item) ? 'is-active' : '']"
                            :aria-current="isItemActive(item) ? 'page' : undefined"
                            :aria-label="displayName(item)"
                            :title="displayName(item)"
                        >
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="item.icon" />
                            </svg>
                            <span>{{ displayName(item) }}</span>
                        </Link>
                    </template>
                </div>
                </Transition>
            </section>
        </template>
    </nav>
</template>

<style scoped>
.sidebar-workspace-label { display: block; margin: 12px 11px 7px; color: var(--ink-faint); font-size: var(--type-meta); }
:global(.is-sidebar-rail) .sidebar-workspace-label { display: none; }
.nav-group-trigger {
    width: 100%;
    border: 0;
    background: transparent;
    color: inherit;
    font: inherit;
    text-align: left;
    cursor: pointer;
}

.nav-section-trigger {
    display: flex;
    width: 100%;
    min-height: 38px;
    align-items: center;
    gap: 11px;
    padding: 7px 11px;
    border: 0;
    border-radius: 9px;
    color: var(--ink);
    background: transparent;
    font: inherit;
    text-align: left;
    cursor: pointer;
}

.nav-section-trigger:hover { color: var(--primary-strong); background: var(--primary-soft); }
.nav-section-trigger.is-active { color: var(--primary-strong); background: var(--primary-soft); box-shadow: inset 3px 0 0 var(--primary); }
.nav-section-trigger:focus-visible { outline: 2px solid var(--sidebar-focus-token); outline-offset: 2px; }
.nav-section-trigger > svg:first-child { width: 18px; height: 18px; flex: 0 0 18px; }
.nav-section-trigger .nav-label { flex: 1; margin: 0; color: inherit; font-size: var(--type-control); font-weight: var(--weight-semibold); letter-spacing: .025em; }
.nav-section-trigger .nav-label,
.nav-group-trigger > span,
.nav-subitem > span { min-width: 0; }

.nav-group-trigger:focus-visible,
.nav-subitem:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

.nav-group-chevron {
    width: 0.9rem;
    height: 0.9rem;
    margin-left: auto;
    transition: transform 160ms ease;
}

.nav-group-chevron.is-expanded {
    transform: rotate(180deg);
}

.nav-group-trigger .nav-group-chevron { flex: 0 0 0.9rem; }

.nav-submenu {
    display: grid;
    gap: 0.2rem;
    margin: 0.2rem 0 0.4rem 0.85rem;
    padding-left: 0.55rem;
    border-left: 1px solid var(--line);
}

.nav-subitem {
    min-height: 2.5rem;
    padding-left: 0.65rem;
}

.nav-section-content {
    display: grid;
    gap: 3px;
    margin: 0.2rem 0 0.4rem 0.85rem;
    padding: 2px 0 5px 0.55rem;
    border-left: 1px solid var(--line);
}
.nav-nested-block {
    position: relative;
    z-index: 1;
    margin-left: -0.55rem;
    padding-left: 0.55rem;
    background: var(--sidebar-bg);
}
.nav-section-trigger .nav-group-chevron { width: 14px; height: 14px; flex: 0 0 14px; margin-left: auto; }

.nav-stack.is-topbar {
    flex-direction: row;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 0;
    flex-wrap: wrap;
    overflow: visible;
}
.nav-stack.is-topbar > .sidebar-workspace-label { display: none; }
.nav-stack.is-topbar > .nav-item,
.nav-stack.is-topbar > .nav-section { flex: 0 0 auto; }
.nav-stack.is-topbar > .nav-item {
    width: auto;
    min-height: 42px;
    padding: 8px 12px;
    border-radius: 9px 9px 0 0;
    white-space: nowrap;
}
.nav-stack.is-topbar > .nav-item.is-active { box-shadow: inset 0 -3px 0 var(--primary); }
.nav-stack.is-topbar > .nav-section { position: relative; }
.nav-stack.is-topbar .nav-section-trigger {
    width: auto;
    min-height: 42px;
    padding: 7px 12px;
    border-radius: 9px 9px 0 0;
    white-space: nowrap;
}
.nav-stack.is-topbar .nav-section-trigger.is-active { box-shadow: inset 0 -3px 0 var(--primary); }
.nav-stack.is-topbar .nav-section-content {
    position: absolute;
    top: calc(100% + 1px);
    left: 0;
    z-index: 70;
    display: grid;
    min-width: 245px;
    max-height: min(70vh, 560px);
    overflow-y: auto;
    gap: 3px;
    margin: 0;
    padding: 7px;
    border: 1px solid var(--line);
    border-radius: 0 10px 10px 10px;
    background: var(--surface);
    box-shadow: var(--shadow-md);
}
.nav-stack.is-topbar .nav-section-content .nav-item {
    min-height: 38px;
    padding: 7px 9px;
    border-radius: 7px;
}
.nav-stack.is-topbar .nav-section-content .nav-item.is-active { box-shadow: inset 3px 0 0 var(--primary); }
.nav-stack.is-topbar .nav-nested-block {
    margin-left: 0;
    padding-left: 0;
    background: transparent;
}
.nav-stack.is-topbar .nav-submenu {
    margin-left: .5rem;
    padding-left: .45rem;
}
@media (max-width: 960px) {
    .nav-stack.is-topbar {
        flex-wrap: wrap;
        width: auto;
        min-width: 0;
    }
    .nav-stack.is-topbar .nav-section-content {
        position: fixed;
        top: calc(var(--topbar) + 43px);
        left: 12px;
        min-width: min(290px, calc(100vw - 24px));
        max-height: calc(100vh - var(--topbar) - 24px);
        border-radius: 10px;
    }
}
.nav-dropdown-enter-active {
    transform-origin: top left;
    transition: opacity 240ms cubic-bezier(0.16, 1, 0.3, 1), transform 240ms cubic-bezier(0.16, 1, 0.3, 1);
    will-change: opacity, transform;
}
.nav-dropdown-leave-active {
    transform-origin: top left;
    transition: opacity 170ms cubic-bezier(0.4, 0, 1, 1), transform 170ms cubic-bezier(0.4, 0, 1, 1);
    will-change: opacity, transform;
}
.nav-dropdown-enter-from,
.nav-dropdown-leave-to {
    opacity: 0;
    transform: translateY(-10px) scale(0.97);
}
@media (prefers-reduced-motion: reduce) {
    .nav-group-chevron,
    .nav-dropdown-enter-active,
    .nav-dropdown-leave-active {
        transition: none;
    }
}
</style>

