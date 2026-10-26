<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import TopNavbar from '../components/TopNavbar.vue';
import SidebarNavigation from '../components/SidebarNavigation.vue';

defineProps<{
    title?: string;
}>();

interface PageProps {
    appName?: string;
    school?: { name?: string; logo_url?: string | null; favicon_url?: string | null; theme_primary?: string };
    activePeriod?: { label?: string };
    auth?: { user?: { role?: string } };
    [key: string]: unknown;
}

const page = usePage<PageProps>();
const appName = computed(() => !page.props.appName || page.props.appName === 'Laravel' ? 'School Suite' : page.props.appName);
const currentRole = computed(() => page.props.auth?.user?.role);
const isTeacherWorkspace = computed(() => ['teacher', 'homeroom'].includes(currentRole.value ?? ''));
const sidebarOpen = ref(false);
const darkTheme = ref(false);
const searchInput = ref<HTMLInputElement | null>(null);

const toggleTheme = () => {
    darkTheme.value = !darkTheme.value;
    document.documentElement.dataset.theme = darkTheme.value ? 'dark' : 'light';
    localStorage.setItem('school-suite-theme', darkTheme.value ? 'dark' : 'light');
};

const closeOnEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape') sidebarOpen.value = false;
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        searchInput.value?.focus();
    }
};

onMounted(() => {
    if (page.props.school?.theme_primary) document.documentElement.style.setProperty('--primary', page.props.school.theme_primary);
    if (page.props.school?.favicon_url) {
        let link = document.querySelector<HTMLLinkElement>('link[rel="icon"]');
        if (!link) { link = document.createElement('link'); link.rel = 'icon'; document.head.appendChild(link); }
        link.href = page.props.school.favicon_url;
    }
    darkTheme.value = localStorage.getItem('school-suite-theme') === 'dark';
    document.documentElement.dataset.theme = darkTheme.value ? 'dark' : 'light';
    document.addEventListener('keydown', closeOnEscape);
});

onBeforeUnmount(() => document.removeEventListener('keydown', closeOnEscape));
</script>

<template>
    <div class="app-shell" :class="{ 'role-guru-shell': isTeacherWorkspace }">
        <Head :title="title" />

        <!-- Accessibility: Skip Link -->
        <a
            href="#main-content"
            class="skip-link"
        >
            Lewati ke konten utama
        </a>

        <aside id="sidebar" class="sidebar" :class="{ 'is-open': sidebarOpen }" aria-label="Navigasi utama">
            <div class="brand">
                <div class="brand-mark" aria-hidden="true"><img v-if="page.props.school?.logo_url" :src="page.props.school.logo_url" alt=""><span v-else>S</span></div>
                <div><strong>{{ appName }}</strong><small>{{ page.props.school?.name ?? 'Ruang kerja sekolah' }}</small></div>
            </div>
            <div class="sidebar-nav-scroll flex-1 overflow-y-auto py-2">
                <SidebarNavigation />
            </div>
            <div class="sidebar-foot">
                <div class="completion-ring" aria-hidden="true"><span>✓</span></div>
                <div><strong>Periode aktif</strong><small>{{ page.props.activePeriod?.label ?? 'Data akademik' }}</small></div>
            </div>
        </aside>
        <button v-if="sidebarOpen" class="sidebar-backdrop is-visible" aria-label="Tutup navigasi" @click="sidebarOpen = false" />
        <div class="workspace">
            <header class="topbar">
                <button class="icon-button mobile-menu" :aria-expanded="sidebarOpen" aria-controls="sidebar" aria-label="Buka navigasi" @click="sidebarOpen = !sidebarOpen">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <label class="global-search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <span class="sr-only">Cari di School Suite</span>
                    <input ref="searchInput" type="search" placeholder="Cari siswa, kelas, atau menu…" />
                </label>
                <div class="context-switcher" aria-label="Periode aktif">
                    <span class="context-dot" />
                    <div><small>Tahun ajaran aktif</small><strong>{{ page.props.activePeriod?.label ?? 'Periode belum diatur' }}</strong></div>
                </div>
                <span class="topbar-spacer" />
                <button class="icon-button" :aria-label="darkTheme ? 'Gunakan tema terang' : 'Gunakan tema gelap'" @click="toggleTheme">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                </button>
                <TopNavbar />
            </header>
            <main id="main-content" tabindex="-1" :class="{ 'role-guru-main': isTeacherWorkspace }">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.brand-mark { overflow: hidden; }
.brand-mark img { width: 100%; height: 100%; object-fit: contain; background: white; }
.topbar-spacer { flex: 1; }
.topbar-user { display: flex; flex: 0 0 auto; align-items: center; }
@media (max-width: 960px) { .topbar-user :deep(.profile-copy) { display: none; } }
@media (max-width: 720px) { .topbar-user :deep(.profile-button) { display: none; } }
</style>

