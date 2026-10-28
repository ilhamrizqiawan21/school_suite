<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

interface NavItem {
    name: string;
    href: string;
    key: string;
    icon: string;
    activePrefix?: string;
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
    href: '/',
    principalHref: '/kepsek/dashboard',
    principalActivePrefix: '/kepsek/dashboard',
    key: 'home',
    exact: true,
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
};

const sections: NavSection[] = [
    {
        title: 'Penerimaan Siswa',
        icon: 'M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12zm4-6h2m-2 3h4',
        items: [
            { name: 'SPMB', href: '/spmb', key: 'admissions', activePrefix: '/spmb', icon: 'M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zm3-6h3m-3 4h5' },
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
            {
                name: 'Perangkat Pembelajaran',
                href: '/akademik/jurnal',
                key: 'learning-resources',
                icon: 'M4 5.5A2.5 2.5 0 016.5 3H20v16H6.5A2.5 2.5 0 014 16.5v-11zM4 16.5A2.5 2.5 0 016.5 14H20M8 7h8M8 10h6',
                children: [
                    { name: 'Jurnal Guru', href: '/akademik/jurnal', key: 'journals', activePrefix: '/akademik/jurnal', icon: 'M5 3h14v18H5zM8 7h8M8 11h8M8 15h5M3 6v15h14' },
                    { name: 'ATP / Silabus', href: '/akademik/perangkat/atp', key: 'learning-atp', activePrefix: '/akademik/perangkat/atp', icon: 'M8 4h8m-8 4h8m-8 4h5m-8 8h14a2 2 0 002-2V6a2 2 0 00-2-2h-3l-2-2H8L6 4H3a2 2 0 00-2 2v12a2 2 0 002 2z' },
                    { name: 'Dokumen Perencanaan', href: '/akademik/perangkat/dokumen', key: 'learning-documents', activePrefix: '/akademik/perangkat/dokumen', icon: 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01' },
                    { name: 'Modul Ajar', href: '/akademik/perangkat/modul-ajar', key: 'learning-module-ajar', activePrefix: '/akademik/perangkat/modul-ajar', icon: 'M4 4h16v16H4zM8 8h8M8 12h8M8 16h5' },
                ],
            },
            { name: 'Kelas Daring', href: '/akademik/kelas-daring', key: 'online-classes', activePrefix: '/akademik/kelas-daring', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
            { name: 'Siklus Siswa', href: '/akademik/siklus-siswa', key: 'student-lifecycle', activePrefix: '/akademik/siklus-siswa', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' },
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
            { name: 'Rapor Siswa', href: '/penilaian/rapor', principalHref: '/kepsek/laporan/nilai', key: 'reports', activePrefix: '/penilaian/rapor', principalActivePrefix: '/kepsek/laporan/nilai', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
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
            { name: 'Persuratan & Arsip', href: '/administrasi/persuratan', key: 'correspondence', activePrefix: '/administrasi/persuratan', icon: 'M7 3h7l5 5v13H5V3h2zm7 0v5h5M8 12h8m-8 4h8' },
            { name: 'Tugas Staf', href: '/administrasi/tugas', key: 'staff-tasks', activePrefix: '/administrasi/tugas', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5h6m-6 7h6m-6 4h6' },
            { name: 'Sarana & Prasarana', href: '/administrasi/sarpras', key: 'facilities', activePrefix: '/administrasi/sarpras', icon: 'M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01M9 18v.01' },
        ],
    },
    {
        title: 'Pengaturan',
        icon: 'M12 8a4 4 0 100 8 4 4 0 000-8zm0-5v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64',
        items: [
            { name: 'Profil Saya', href: '/profil', key: 'profile', activePrefix: '/profil', icon: 'M5.121 17.804A9 9 0 1118.88 17.804M15 11a3 3 0 11-6 0 3 3 0 016 0z' },
            { name: 'Pengguna', href: '/pengaturan/pengguna', key: 'users', activePrefix: '/pengaturan/pengguna', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
            { name: 'Profil Sekolah', href: '/pengaturan/sekolah', key: 'school', activePrefix: '/pengaturan/sekolah', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z' },
            { name: 'Keamanan', href: '/pengaturan/keamanan', key: 'security', activePrefix: '/pengaturan/keamanan', icon: 'M12 11c1.657 0 3-1.343 3-3V5a3 3 0 00-6 0v3c0 1.657 1.343 3 3 3zm0 0v2m-7 8h14a2 2 0 002-2v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2z' },
        ],
    },
];

const page = usePage();
const currentUrl = computed(() => page.url);
const userId = computed(() => (page.props.auth as any)?.user?.id as number | undefined);
const currentRole = computed(() => (page.props.auth as any)?.user?.role as string | undefined);
const displayName = (item: NavItem) => {
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
        items: section.items.map((item) => ({
            ...resolvedItem(item),
            children: item.children?.filter((child) => visibleKeys.value.has(child.key)).map(resolvedItem),
        })).filter((item) => item.children ? visibleKeys.value.has(item.key) || item.children.length > 0 : visibleKeys.value.has(item.key)),
    }))
    .filter((section) => section.items.length > 0));

const activeSectionKey = computed(() => visibleSections.value.find((section) =>
    section.items.some((item) => isItemActive(item) || item.children?.some((child) => isItemActive(child))),
)?.title ?? null);
const expandedGroups = ref<Record<string, boolean>>({});
const groupsLoaded = ref(false);
const isExpanded = (key: string, active = false) => Object.hasOwn(expandedGroups.value, key)
    ? expandedGroups.value[key] === true
    : active;
const isSectionActive = (section: NavSection) => section.items.some((item) =>
    isItemActive(item) || item.children?.some((child) => isItemActive(child)),
);
const isNestedGroupActive = (item: NavItem) => item.children?.some((child) => isItemActive(child)) ?? false;
const toggleGroup = (key: string, active: boolean) => {
    // Replace the record so every toggle is observed consistently, including
    // after an Inertia navigation or when the stored preference was just read.
    expandedGroups.value = {
        ...expandedGroups.value,
        [key]: !isExpanded(key, active),
    };
    persistExpandedGroups();
};
const groupId = (key: string) => `nav-group-${key.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;
const openActiveGroups = () => {
    if (!groupsLoaded.value) return;

    const nextGroups = { ...expandedGroups.value };
    if (activeSectionKey.value && !Object.hasOwn(expandedGroups.value, activeSectionKey.value)) {
        nextGroups[activeSectionKey.value] = true;
    }
    const activeNested = visibleSections.value.flatMap((section) => section.items).find((item) => isNestedGroupActive(item));
    if (activeNested && !Object.hasOwn(expandedGroups.value, activeNested.key)) {
        nextGroups[activeNested.key] = true;
    }
    if (Object.keys(nextGroups).length !== Object.keys(expandedGroups.value).length) {
        expandedGroups.value = nextGroups;
    }
};
const loadExpandedGroups = () => {
    groupsLoaded.value = false;
    expandedGroups.value = {};
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
    try {
        localStorage.setItem(`school-suite-nav-groups:${userId.value ?? 'guest'}`, JSON.stringify(expandedGroups.value));
    } catch { /* Storage may be unavailable in private browsing. */ }
};

onMounted(() => {
    loadExpandedGroups();
});

watch(userId, (nextUserId, previousUserId) => {
    if (nextUserId !== previousUserId) loadExpandedGroups();
});

watch([currentUrl, activeSectionKey, visibleSections], () => {
    if (!groupsLoaded.value) return;
    openActiveGroups();
    persistExpandedGroups();
});
</script>

<template>
    <nav class="nav-stack" aria-label="Navigasi Utama">
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
            </section>
        </template>
    </nav>
</template>

<style scoped>
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
    color: #b4c3dc;
    background: transparent;
    font: inherit;
    text-align: left;
    cursor: pointer;
}

.nav-section-trigger:hover { color: #fff; background: rgba(255,255,255,.07); }
.nav-section-trigger:focus-visible { outline: 2px solid #f2bc55; outline-offset: 2px; }
.nav-section-trigger > svg:first-child { width: 18px; height: 18px; flex: 0 0 18px; }
.nav-section-trigger .nav-label { flex: 1; margin: 0; color: inherit; font-size: 12px; font-weight: 750; letter-spacing: .025em; }
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
    border-left: 1px solid currentColor;
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
    border-left: 1px solid currentColor;
}
.nav-nested-block {
    position: relative;
    z-index: 1;
    margin-left: -0.55rem;
    padding-left: 0.55rem;
    background: var(--sidebar-bg);
}
.nav-section-trigger .nav-group-chevron { width: 14px; height: 14px; flex: 0 0 14px; margin-left: auto; }

@media (prefers-reduced-motion: reduce) {
    .nav-group-chevron {
        transition: none;
    }
}
</style>

