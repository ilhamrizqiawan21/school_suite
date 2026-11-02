<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, onMounted, onBeforeUnmount, watch } from 'vue';
import TopNavbar from '../components/TopNavbar.vue';
import SidebarNavigation from '../components/SidebarNavigation.vue';
import Breadcrumbs from '../components/Breadcrumbs.vue';

const layoutProps = defineProps<{
    title?: string;
}>();

interface PageProps {
    appName?: string;
    school?: { name?: string; logo_url?: string | null; favicon_url?: string | null; theme_primary?: string };
    activePeriod?: { label?: string };
    auth?: { user?: { id?: number; role?: string } };
    [key: string]: unknown;
}

const page = usePage<PageProps>();
const appBasePath = document.querySelector<HTMLMetaElement>('meta[name="app-base-path"]')?.content?.replace(/\/+$/, '') ?? '';
const fallbackLogoUrl = `${appBasePath}/icon-192.png`;
const appName = computed(() => !page.props.appName || page.props.appName === 'Laravel' ? 'School Suite' : page.props.appName);
const currentRole = computed(() => page.props.auth?.user?.role);
const isTeacherWorkspace = computed(() => ['teacher', 'homeroom'].includes(currentRole.value ?? ''));
const sidebarOpen = ref(false);
const desktopSidebarRail = ref(false);
const isMobileViewport = ref(false);
const sidebarExpanded = computed(() => isMobileViewport.value ? sidebarOpen.value : !desktopSidebarRail.value);
const sidebarToggleLabel = computed(() => isMobileViewport.value
    ? (sidebarOpen.value ? 'Tutup navigasi' : 'Buka navigasi')
    : (desktopSidebarRail.value ? 'Perlebar sidebar' : 'Perkecil sidebar'));
const darkTheme = ref(false);
const searchInput = ref<HTMLInputElement | null>(null);
const searchRoot = ref<HTMLElement | null>(null);
const searchQuery = ref('');
const searchFocused = ref(false);
const activeSearchIndex = ref(0);
const menuEntries = ref<Array<{ label: string; href: string; section: string }>>([]);
let removeNavigateListener: VoidFunction | null = null;
let removeBeforeNavigateListener: VoidFunction | null = null;
let sidebarViewportQuery: MediaQueryList | null = null;
let syncSidebarViewport: (() => void) | null = null;
const sidebarScrollStorageKey = 'school-suite-sidebar-scroll-top';
const sidebarNavScroll = ref<HTMLElement | null>(null);

const searchResults = computed(() => {
    const query = searchQuery.value.trim().toLocaleLowerCase('id-ID');
    if (!query) return [];

    return menuEntries.value
        .filter((entry) => `${entry.label} ${entry.section}`.toLocaleLowerCase('id-ID').includes(query))
        .sort((a, b) => {
            const aStarts = a.label.toLocaleLowerCase('id-ID').startsWith(query);
            const bStarts = b.label.toLocaleLowerCase('id-ID').startsWith(query);
            return Number(bStarts) - Number(aStarts) || a.label.localeCompare(b.label, 'id');
        })
        .slice(0, 8);
});

const searchOpen = computed(() => searchFocused.value && searchQuery.value.trim().length > 0);

const collectMenuEntries = () => {
    const entries = Array.from(document.querySelectorAll<HTMLAnchorElement>('#sidebar a.nav-item'))
        .map((link) => ({
            label: link.querySelector('span')?.textContent?.trim() || link.textContent?.trim() || '',
            href: link.getAttribute('href') || '',
            section: link.closest('.nav-section')?.querySelector('.nav-section-trigger .nav-label')?.textContent?.trim() || 'Menu',
        }))
        .filter((entry) => entry.label && entry.href && entry.href !== '#');

    menuEntries.value = Array.from(new Map(entries.map((entry) => [`${entry.href}|${entry.label}`, entry])).values());
};

const refreshMenuSearch = () => {
    collectMenuEntries();
    activeSearchIndex.value = 0;
};

const closeMenuSearch = () => {
    searchFocused.value = false;
    searchQuery.value = '';
    activeSearchIndex.value = 0;
};

const selectSearchResult = () => {
    const result = searchResults.value[activeSearchIndex.value];
    if (!result) return;

    closeMenuSearch();
    router.visit(result.href);
};

const handleSearchKeydown = (event: KeyboardEvent) => {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        if (searchResults.value.length) {
            activeSearchIndex.value = (activeSearchIndex.value + 1) % searchResults.value.length;
        }
    }
    if (event.key === 'ArrowUp') {
        event.preventDefault();
        if (searchResults.value.length) {
            activeSearchIndex.value = (activeSearchIndex.value - 1 + searchResults.value.length) % searchResults.value.length;
        }
    }
    if (event.key === 'Enter' && searchResults.value.length) {
        event.preventDefault();
        selectSearchResult();
    }
}

const handleDocumentPointerdown = (event: PointerEvent) => {
    if (searchRoot.value && !searchRoot.value.contains(event.target as Node)) closeMenuSearch();
};

const applySchoolTheme = (primary?: string) => {
    if (!primary) return;

    document.documentElement.style.setProperty('--school-primary', primary);
};

const toggleSidebar = () => {
    if (isMobileViewport.value) {
        sidebarOpen.value = !sidebarOpen.value;
        return;
    }

    desktopSidebarRail.value = !desktopSidebarRail.value;
    try {
        localStorage.setItem('school-suite-sidebar-mode', desktopSidebarRail.value ? 'rail' : 'full');
    } catch { /* Storage may be unavailable in private browsing. */ }
};

const readStoredTheme = () => {
    try {
        return localStorage.getItem('school-suite-theme') === 'dark';
    } catch { /* Storage may be unavailable in private browsing. */
        return false;
    }
};

const persistTheme = (isDark: boolean) => {
    try {
        localStorage.setItem('school-suite-theme', isDark ? 'dark' : 'light');
    } catch { /* Storage may be unavailable in private browsing. */ }
};

const toggleTheme = () => {
    darkTheme.value = !darkTheme.value;
    document.documentElement.dataset.theme = darkTheme.value ? 'dark' : 'light';
    persistTheme(darkTheme.value);
};

const closeOnEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        if (searchOpen.value) {
            closeMenuSearch();
            searchInput.value?.focus();
            return;
        }
        sidebarOpen.value = false;
    }
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        searchFocused.value = true;
        collectMenuEntries();
        searchInput.value?.focus();
    }
};

const saveSidebarScroll = () => {
    const scrollTop = sidebarNavScroll.value?.scrollTop;
    if (typeof scrollTop !== 'number') return;

    try {
        sessionStorage.setItem(sidebarScrollStorageKey, String(scrollTop));
    } catch { /* Storage may be unavailable in private browsing. */ }
};

const restoreSidebarScroll = () => {
    let savedScrollTop: number;
    try {
        savedScrollTop = Number(sessionStorage.getItem(sidebarScrollStorageKey));
    } catch {
        return;
    }

    if (!Number.isFinite(savedScrollTop) || savedScrollTop <= 0) return;

    nextTick(() => {
        window.requestAnimationFrame(() => {
            if (sidebarNavScroll.value) sidebarNavScroll.value.scrollTop = savedScrollTop;
        });
    });
};

onMounted(() => {
    sidebarViewportQuery = window.matchMedia('(max-width: 960px)');
    syncSidebarViewport = () => {
        isMobileViewport.value = sidebarViewportQuery?.matches ?? false;
        if (isMobileViewport.value) sidebarOpen.value = false;
    };
    syncSidebarViewport();
    sidebarViewportQuery.addEventListener('change', syncSidebarViewport);
    try {
        desktopSidebarRail.value = localStorage.getItem('school-suite-sidebar-mode') === 'rail';
    } catch { desktopSidebarRail.value = false; }

    applySchoolTheme(page.props.school?.theme_primary);
    // Let the active light/dark token decide --primary. Login branding may
    // leave an inline value behind, which would otherwise override dark mode.
    document.documentElement.style.removeProperty('--primary');
    if (page.props.school?.favicon_url) {
        let link = document.querySelector<HTMLLinkElement>('link[rel="icon"]');
        if (!link) { link = document.createElement('link'); link.rel = 'icon'; document.head.appendChild(link); }
        link.href = page.props.school.favicon_url;
    }
    darkTheme.value = readStoredTheme();
    document.documentElement.dataset.theme = darkTheme.value ? 'dark' : 'light';
    document.addEventListener('keydown', closeOnEscape);
    document.addEventListener('pointerdown', handleDocumentPointerdown);
    nextTick(collectMenuEntries);
    removeBeforeNavigateListener = router.on('before', saveSidebarScroll);
    removeNavigateListener = router.on('navigate', () => {
        sidebarOpen.value = false;
        closeMenuSearch();
    });
    restoreSidebarScroll();
});

watch(() => page.props.school?.theme_primary, applySchoolTheme);

onBeforeUnmount(() => {
    if (sidebarViewportQuery && syncSidebarViewport) {
        sidebarViewportQuery.removeEventListener('change', syncSidebarViewport);
    }
    document.removeEventListener('keydown', closeOnEscape);
    document.removeEventListener('pointerdown', handleDocumentPointerdown);
    saveSidebarScroll();
    removeBeforeNavigateListener?.();
    removeNavigateListener?.();
});
</script>

<template>
    <div class="app-shell" :class="{ 'role-guru-shell': isTeacherWorkspace, 'is-sidebar-rail': desktopSidebarRail && !isMobileViewport }">
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
                <div class="brand-mark" aria-hidden="true">
                    <img :src="page.props.school?.logo_url || fallbackLogoUrl" alt="">
                </div>
                <div class="brand-copy">
                    <strong>{{ appName }}</strong>
                    <small>{{ page.props.school?.name ?? 'Ruang kerja sekolah' }}</small>
                </div>
            </div>
            <div ref="sidebarNavScroll" class="sidebar-nav-scroll flex-1 overflow-y-auto">
                <SidebarNavigation />
            </div>
        </aside>
            <button v-if="sidebarOpen" type="button" class="sidebar-backdrop is-visible" aria-label="Tutup navigasi" @click="sidebarOpen = false" />
        <div class="workspace">
            <header class="topbar">
                <button type="button" class="icon-button sidebar-toggle" :aria-expanded="sidebarExpanded" aria-controls="sidebar" :aria-label="sidebarToggleLabel" :title="sidebarToggleLabel" @click="toggleSidebar">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div ref="searchRoot" class="global-search" :class="{ 'is-focused': searchFocused }">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <span class="sr-only">Cari di School Suite</span>
                    <input
                        ref="searchInput"
                        v-model="searchQuery"
                        type="search"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-controls="global-search-results"
                        :aria-expanded="searchOpen"
                        placeholder="Cari menu…"
                        @focus="searchFocused = true; refreshMenuSearch()"
                        @input="refreshMenuSearch"
                        @keydown="handleSearchKeydown"
                    />
                    <kbd v-if="!searchQuery">Ctrl K</kbd>
                    <div v-if="searchOpen" id="global-search-results" class="global-search-results" role="listbox" aria-label="Hasil pencarian menu">
                        <template v-if="searchResults.length">
                            <Link
                                v-for="(entry, index) in searchResults"
                                :key="`${entry.href}-${entry.label}`"
                                :href="entry.href"
                                class="global-search-result"
                                :class="{ 'is-highlighted': index === activeSearchIndex }"
                                role="option"
                                :aria-selected="index === activeSearchIndex"
                                @click="closeMenuSearch"
                            >
                                <span>{{ entry.label }}</span>
                                <small>{{ entry.section }}</small>
                            </Link>
                        </template>
                        <p v-else class="global-search-empty">Menu tidak ditemukan.</p>
                    </div>
                </div>
                <div class="context-switcher" aria-label="Periode aktif">
                    <span class="context-dot" />
                    <div><small>Tahun ajaran aktif</small><strong>{{ page.props.activePeriod?.label ?? 'Periode belum diatur' }}</strong></div>
                </div>
                <span class="topbar-spacer" />
                <button type="button" class="icon-button" :aria-label="darkTheme ? 'Gunakan tema terang' : 'Gunakan tema gelap'" @click="toggleTheme">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                </button>
                <TopNavbar />
            </header>
            <main id="main-content" tabindex="-1" :class="{ 'role-guru-main': isTeacherWorkspace }">
                <Breadcrumbs :title="layoutProps.title" />
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.brand-mark { overflow: hidden; }
.brand-mark img { display: block; width: 100%; height: 100%; object-fit: contain; }
.brand-copy { min-width: 0; }
.topbar-spacer { flex: 1; }
.topbar-user { display: flex; flex: 0 0 auto; align-items: center; }
@media (max-width: 960px) { .topbar-user :deep(.profile-copy) { display: none; } }
</style>

