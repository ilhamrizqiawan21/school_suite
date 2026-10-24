<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface NavItem {
    name: string;
    href: string;
    key: string;
    icon: string;
    activePrefix?: string;
}

const navItems: NavItem[] = [
    { name: 'Beranda', href: '/', key: 'home', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Data Siswa', href: '/akademik/siswa', key: 'students', activePrefix: '/akademik/siswa', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { name: 'Data Guru', href: '/akademik/guru', key: 'teachers', activePrefix: '/akademik/guru', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { name: 'Rombel / Kelas', href: '/akademik/kelas', key: 'classes', activePrefix: '/akademik/kelas', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { name: 'Pengguna', href: '/pengaturan/pengguna', key: 'users', activePrefix: '/pengaturan/pengguna', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { name: 'Profil Sekolah', href: '/pengaturan/sekolah', key: 'school', activePrefix: '/pengaturan/sekolah', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z' },
];

const page = usePage();
const currentUrl = computed(() => page.url);

const isItemActive = (item: NavItem): boolean => {
    if (item.activePrefix) {
        return currentUrl.value.startsWith(item.activePrefix);
    }
    return currentUrl.value === item.href;
};
</script>

<template>
    <nav class="flex flex-col gap-1 p-3" aria-label="Navigasi Utama">
        <Link
            v-for="item in navItems"
            :key="item.key"
            :href="item.href"
            :class="[
                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500',
                isItemActive(item)
                    ? 'bg-indigo-50 text-indigo-700 font-semibold'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
            ]"
            :aria-current="isItemActive(item) ? 'page' : undefined"
        >
            <svg
                class="w-5 h-5 shrink-0 opacity-80"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="item.icon" />
            </svg>
            <span>{{ item.name }}</span>
        </Link>
    </nav>
</template>

