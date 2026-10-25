<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface NavItem {
    name: string;
    href: string;
    key: string;
    icon: string;
    activePrefix?: string;
    exact?: boolean;
    roles?: string[];
}

interface NavSection {
    title: string;
    items: NavItem[];
}

const homeItem: NavItem = {
    name: 'Beranda',
    href: '/',
    key: 'home',
    exact: true,
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
};

const sections: NavSection[] = [
    {
        title: 'Akademik',
        items: [
            { name: 'Data Siswa', href: '/akademik/siswa', key: 'students', activePrefix: '/akademik/siswa', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
            { name: 'Data Guru', href: '/akademik/guru', key: 'teachers', activePrefix: '/akademik/guru', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
            { name: 'Rombel / Kelas', href: '/akademik/kelas', key: 'classes', activePrefix: '/akademik/kelas', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
            { name: 'Jadwal Pelajaran', href: '/akademik/jadwal', key: 'schedules', activePrefix: '/akademik/jadwal', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
            { name: 'Presensi Siswa', href: '/akademik/presensi', key: 'attendance', activePrefix: '/akademik/presensi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
            { name: 'Jurnal Mengajar', href: '/akademik/jurnal', key: 'journals', activePrefix: '/akademik/jurnal', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
            { name: 'Kelas Daring', href: '/akademik/kelas-daring', key: 'online-classes', activePrefix: '/akademik/kelas-daring', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
            { name: 'Siklus Siswa', href: '/akademik/siklus-siswa', key: 'student-lifecycle', activePrefix: '/akademik/siklus-siswa', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' },
            { name: 'Pengaturan Akademik', href: '/akademik/pengaturan', key: 'academic-settings', activePrefix: '/akademik/pengaturan', icon: 'M4 6h16M4 12h16M4 18h16' },
        ],
    },
    {
        title: 'Pembelajaran LMS',
        items: [
            { name: 'Ruang Belajar', href: '/pembelajaran', key: 'learning', exact: true, activePrefix: '/pembelajaran', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
            { name: 'Progress Belajar', href: '/pembelajaran/progress', key: 'learning-progress', activePrefix: '/pembelajaran/progress', icon: 'M9 17v-6m4 6V7m4 10V4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z' },
        ],
    },
    {
        title: 'Asesmen & CBT',
        items: [
            { name: 'Bank Soal', href: '/penilaian/bank-soal', key: 'question-bank', activePrefix: '/penilaian/bank-soal', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13c-1.168-.776-2.754-1.253-4.5-1.253' },
            { name: 'Paket Soal', href: '/penilaian/paket-soal', key: 'question-packs', activePrefix: '/penilaian/paket-soal', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
            { name: 'Berbagi Soal', href: '/penilaian/berbagi-soal', key: 'question-shares', activePrefix: '/penilaian/berbagi-soal', icon: 'M8 12h8m-4-4v8m8-4a4 4 0 0 1-4 4H8a4 4 0 1 1 0-8h8a4 4 0 0 1 4 4Z' },
            { name: 'Ujian / CBT', href: '/ujian', key: 'exams', activePrefix: '/ujian', icon: 'M9 12h6m-6 4h6M9 8h6m2 13H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z' },
        ],
    },
    {
        title: 'Penilaian & Rapor',
        items: [
            { name: 'Input Nilai', href: '/penilaian/skor', key: 'scores', activePrefix: '/penilaian/skor', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
            { name: 'Penilaian Sikap', href: '/penilaian/sikap', key: 'attitudes', activePrefix: '/penilaian/sikap', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
            { name: 'Skema Penilaian', href: '/penilaian/skema', key: 'schemes', activePrefix: '/penilaian/skema', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
            { name: 'Rapor Siswa', href: '/penilaian/rapor', key: 'reports', activePrefix: '/penilaian/rapor', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
            { name: 'Analisis', href: '/penilaian/analisis', key: 'assessment-analytics', activePrefix: '/penilaian/analisis', icon: 'M3 3v18h18M8 15l4-4 4 3 5-7' },
            { name: 'Pusat Laporan', href: '/laporan', key: 'reporting', activePrefix: '/laporan', icon: 'M9 17v-2m3 2v-4m3 4v-6m4 10H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2z' },
        ],
    },
    {
        title: 'Perwalian',
        items: [
            { name: 'Ruang Wali Kelas', href: '/wali-kelas', key: 'homeroom', exact: true, activePrefix: '/wali-kelas', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
            { name: 'Catatan Observasi & BK', href: '/wali-kelas/catatan', key: 'homeroom-observations', activePrefix: '/wali-kelas/catatan', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
            { name: 'Kedisiplinan & Prestasi', href: '/wali-kelas/kedisiplinan', key: 'homeroom-discipline', activePrefix: '/wali-kelas/kedisiplinan', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
            { name: 'WhatsApp Orang Tua', href: '/komunikasi/whatsapp', key: 'homeroom-whatsapp', activePrefix: '/komunikasi/whatsapp', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
        ],
    },
    {
        title: 'Komunikasi',
        items: [
            { name: 'Pengumuman & Agenda', href: '/komunikasi', key: 'communication', exact: true, activePrefix: '/komunikasi', icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z' },
            { name: 'Diskusi Chat', href: '/komunikasi/chat', key: 'communication-chat', activePrefix: '/komunikasi/chat', icon: 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z' },
            { name: 'WhatsApp Orang Tua', href: '/komunikasi/whatsapp', key: 'communication-whatsapp', activePrefix: '/komunikasi/whatsapp', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
        ],
    },
    {
        title: 'Pengaturan',
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
const currentRole = computed(() => (page.props.auth as any)?.user?.role as string | undefined);
const roleVisibility: Record<string, string[]> = {
    students: ['admin', 'operator', 'principal', 'teacher', 'homeroom'],
    teachers: ['admin', 'operator', 'principal'],
    classes: ['admin', 'operator', 'principal', 'teacher', 'homeroom'],
    schedules: ['admin', 'operator', 'principal', 'teacher', 'homeroom', 'student'],
    attendance: ['teacher', 'homeroom'],
    journals: ['teacher', 'homeroom'],
    'online-classes': ['teacher', 'homeroom', 'student'],
    'student-lifecycle': ['admin', 'operator', 'principal'],
    'academic-settings': ['admin', 'operator', 'principal'],
    learning: ['teacher', 'homeroom', 'student'],
    'learning-progress': ['admin', 'operator', 'principal', 'teacher', 'homeroom', 'student'],
    'question-bank': ['teacher', 'homeroom'],
    'question-packs': ['teacher', 'homeroom'],
    'question-shares': ['teacher', 'homeroom'],
    exams: ['admin', 'operator', 'teacher', 'homeroom', 'student'],
    scores: ['teacher', 'homeroom'],
    attitudes: ['teacher', 'homeroom'],
    schemes: ['admin', 'operator', 'principal'],
    reports: ['admin', 'operator', 'principal', 'teacher', 'homeroom', 'student'],
    'assessment-analytics': ['admin', 'operator', 'principal', 'teacher', 'homeroom'],
    reporting: ['admin', 'operator', 'principal'],
    homeroom: ['homeroom'],
    'homeroom-observations': ['homeroom'],
    'homeroom-discipline': ['homeroom'],
    'homeroom-whatsapp': ['homeroom'],
    communication: ['admin', 'operator', 'principal', 'teacher', 'homeroom', 'student'],
    'communication-chat': ['teacher', 'homeroom', 'student'],
    'communication-whatsapp': ['admin', 'operator', 'principal'],
    profile: ['admin', 'operator', 'principal', 'teacher', 'homeroom', 'student'],
    users: ['admin', 'operator'],
    school: ['admin', 'operator', 'principal'],
    security: ['admin', 'principal'],
};

const visibleSections = computed(() => {
    return sections
        .map((section) => ({
            title: section.title,
            items: section.items.filter((item) => {
                const allowedRoles = roleVisibility[item.key] ?? item.roles;
                return !allowedRoles || (currentRole.value && allowedRoles.includes(currentRole.value));
            }),
        }))
        .filter((section) => section.items.length > 0);
});

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
</script>

<template>
    <nav class="nav-stack" aria-label="Navigasi Utama">
        <Link
            :href="homeItem.href"
            :class="['nav-item', isItemActive(homeItem) ? 'is-active' : '']"
            :aria-current="isItemActive(homeItem) ? 'page' : undefined"
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
            <p class="nav-label nav-label-spaced">{{ section.title }}</p>
            <Link
                v-for="item in section.items"
                :key="item.key"
                :href="item.href"
                :class="['nav-item', isItemActive(item) ? 'is-active' : '']"
                :aria-current="isItemActive(item) ? 'page' : undefined"
            >
                <svg
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="item.icon" />
                </svg>
                <span>{{ item.name }}</span>
            </Link>
        </template>
    </nav>
</template>

