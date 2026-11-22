<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { applyAdminBranding, type AdminTheme } from '@/lib/adminBranding';
import { useTheme } from '@/composables/useTheme';
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
    school?: { name?: string; logo_url?: string | null; favicon_url?: string | null; theme_primary?: string; admin_theme?: AdminTheme };
    activePeriod?: { label?: string };
    auth?: { user?: { id?: number; role?: string } };
    [key: string]: unknown;
}

const page = usePage<PageProps>();
const activePeriodLabel = computed(() => (page.props.activePeriod?.label ?? 'Periode belum diatur').replace(/Semester\s+Semester/gi, 'Semester'));
const appBasePath = document.querySelector<HTMLMetaElement>('meta[name="app-base-path"]')?.content?.replace(/\/+$/, '') ?? '';
const adminLogoUrl = `${appBasePath}/madrasah-theme/images/logo-kemenag.png`;
const appName = computed(() => String(page.props.appName ?? '').trim() || 'School Suite');
const currentRole = computed(() => page.props.auth?.user?.role);
watch(appName, (name) => { document.documentElement.dataset.applicationName = name; }, { immediate: true });
const isTeacherWorkspace = computed(() => ['teacher', 'homeroom'].includes(currentRole.value ?? ''));
const { theme, toggleTheme } = useTheme();
const darkTheme = computed(() => theme.value === 'dark');
interface ToastItem {
    id: number;
    message: string;
    kind: 'success' | 'error';
}
const toasts = ref<ToastItem[]>([]);
let nextToastId = 1;
const toastTimers = new Map<number, ReturnType<typeof setTimeout>>();

const dismissToast = (id: number) => {
    const timer = toastTimers.get(id);
    if (timer) {
        clearTimeout(timer);
        toastTimers.delete(id);
    }
    toasts.value = toasts.value.filter((item) => item.id !== id);
};

const pushToast = (message: string, kind: 'success' | 'error') => {
    const clean = message.trim();
    if (!clean) return;
    const existing = toasts.value.find((item) => item.message === clean && item.kind === kind);
    if (existing) {
        dismissToast(existing.id);
    }
    const id = nextToastId++;
    toasts.value = [...toasts.value.slice(-3), { id, message: clean, kind }];
    toastTimers.set(id, setTimeout(() => dismissToast(id), 5000));
};
const searchInput = ref<HTMLInputElement | null>(null);
const searchRoot = ref<HTMLElement | null>(null);
const searchQuery = ref('');
const searchFocused = ref(false);
const activeSearchIndex = ref(0);
const menuEntries = ref<Array<{ label: string; href: string; section: string }>>([]);
const sidebarOpen = ref(false);
const sidebarCollapsed = ref(false);
const sidebarPreferenceKey = computed(() => `school-sidebar:${page.props.auth?.user?.id ?? 'guest'}`);
const toggleDesktopSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    try { localStorage.setItem(sidebarPreferenceKey.value, String(sidebarCollapsed.value)); } catch { /* Storage may be unavailable. */ }
};
const restoreSidebarPreference = () => {
    try { sidebarCollapsed.value = localStorage.getItem(sidebarPreferenceKey.value) === 'true'; } catch { sidebarCollapsed.value = false; }
};
watch(sidebarPreferenceKey, restoreSidebarPreference);
const offline = ref(!navigator.onLine);
const updateConnection = () => { offline.value = !navigator.onLine; };
const removeNetworkErrorListener = router.on('networkError', (event) => {
    event.preventDefault();
    pushToast('Koneksi terputus. Perubahan belum dikonfirmasi tersimpan. Periksa koneksi lalu coba lagi.', 'error');
});
const isMobileViewport = ref(false);
let removeNavigateListener: VoidFunction | null = null;
let mobileMediaQuery: MediaQueryList | null = null;

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
    const entries = Array.from(document.querySelectorAll<HTMLAnchorElement>('.topbar-navigation a.nav-item, .sidebar a.nav-item'))
        .map((link) => ({
            label: link.getAttribute('aria-label') || link.querySelector('span')?.textContent?.trim() || link.textContent?.trim() || '',
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
    primary = page.props.school?.admin_theme?.primary ?? primary;
    if (primary && CSS.supports('color', primary)) document.documentElement.style.setProperty('--school-primary', primary);
    else document.documentElement.style.removeProperty('--school-primary');
};

const showFlashToast = () => {
    const flash = page.props.flash as { message?: string; error?: string } | undefined;
    if (flash?.error) pushToast(flash.error, 'error');
    if (flash?.message) pushToast(flash.message, 'success');
};

const closeSidebar = () => {
    sidebarOpen.value = false;
};

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const handleViewportMediaChange = (event: MediaQueryListEvent | MediaQueryList) => {
    isMobileViewport.value = event.matches;
    if (!event.matches) {
        closeSidebar();
    }
    nextTick(collectMenuEntries);
};

const closeOnEscape = (event: KeyboardEvent) => {
    if (event.defaultPrevented) return;
    if (event.key === 'Escape') {
        if (sidebarOpen.value) {
            closeSidebar();
            return;
        }
        if (searchOpen.value) {
            closeMenuSearch();
            searchInput.value?.focus();
            return;
        }
    }
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        searchFocused.value = true;
        collectMenuEntries();
        searchInput.value?.focus();
    }
};

onMounted(() => {
    restoreSidebarPreference();
    window.addEventListener('online', updateConnection);
    window.addEventListener('offline', updateConnection);
    applySchoolTheme(page.props.school?.theme_primary);
    showFlashToast();
    // Let the active light/dark token decide --primary. Login branding may
    // leave an inline value behind, which would otherwise override dark mode.
    document.documentElement.style.removeProperty('--primary');
    if (page.props.school?.favicon_url) {
        let link = document.querySelector<HTMLLinkElement>('link[rel="icon"]');
        if (!link) { link = document.createElement('link'); link.rel = 'icon'; document.head.appendChild(link); }
        link.href = page.props.school.favicon_url;
    }
    document.addEventListener('keydown', closeOnEscape);
    document.addEventListener('pointerdown', handleDocumentPointerdown);
    mobileMediaQuery = window.matchMedia('(max-width: 960px)');
    handleViewportMediaChange(mobileMediaQuery);
    mobileMediaQuery.addEventListener('change', handleViewportMediaChange);
    nextTick(collectMenuEntries);
    removeNavigateListener = router.on('navigate', () => {
        closeMenuSearch();
        closeSidebar();
    });
});

watch(() => page.props.school?.theme_primary, applySchoolTheme);
watch(() => page.props.school?.favicon_url, (url) => {
    const icons = Array.from(document.querySelectorAll<HTMLLinkElement>('link[rel="icon"]'));
    const icon = icons.shift() ?? document.createElement('link');
    icon.rel = 'icon';
    icon.removeAttribute('sizes');
    icon.removeAttribute('type');
    icon.href = url || `${appBasePath}/brand/favicon/favicon.ico`;
    icons.forEach((extra) => extra.remove());
    if (!icon.isConnected) document.head.appendChild(icon);
}, { immediate: true });
watch(() => page.props.school?.admin_theme, applyAdminBranding, { immediate: true, deep: true });
watch(() => page.props.flash, showFlashToast, { deep: true });

onBeforeUnmount(() => {
    window.removeEventListener('online', updateConnection);
    window.removeEventListener('offline', updateConnection);
    removeNetworkErrorListener();
    applyAdminBranding(null);
    toastTimers.forEach((timer) => clearTimeout(timer));
    toastTimers.clear();
    document.removeEventListener('keydown', closeOnEscape);
    document.removeEventListener('pointerdown', handleDocumentPointerdown);
    mobileMediaQuery?.removeEventListener('change', handleViewportMediaChange);
    removeNavigateListener?.();
});
</script>

<template>
    <div class="app-shell blueprint-shell" :class="{ 'role-guru-shell': isTeacherWorkspace, 'is-sidebar-rail': sidebarCollapsed && !isMobileViewport }">
        <Head :title="title" />

        <!-- Accessibility: Skip Link -->
        <a
            href="#main-content"
            class="skip-link"
        >
            Lewati ke konten utama
        </a>

        <template v-if="isMobileViewport">
            <div class="sidebar-backdrop" :class="{ 'is-visible': sidebarOpen }" aria-hidden="true" @click="closeSidebar" />
            <aside id="mobile-sidebar" class="sidebar" :class="{ 'is-open': sidebarOpen }" :role="sidebarOpen ? 'dialog' : undefined" :aria-modal="sidebarOpen ? true : undefined" :inert="!sidebarOpen" aria-label="Menu navigasi">
                <div class="sidebar-header">
                    <Link href="/dashboard" class="brand" :aria-label="`Buka beranda ${appName}`" @click="closeSidebar">
                        <div class="brand-mark" aria-hidden="true">
                            <img :src="adminLogoUrl" alt="Logo Kementerian Agama">
                        </div>
                        <div class="brand-copy">
                            <strong>{{ appName }}</strong>
                            <small>{{ page.props.school?.name ?? 'Portal sekolah' }}</small>
                        </div>
                    </Link>
                    <AppButton variant="secondary" icon-only type="button" class="icon-button sidebar-close" aria-label="Tutup menu" @click="closeSidebar">
                        <template #icon>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                        </template>
                    </AppButton>
                </div>
                <div class="sidebar-nav-scroll">
                    <SidebarNavigation mode="sidebar" />
                </div>
            </aside>
        </template>

        <aside v-else id="desktop-sidebar" class="sidebar desktop-sidebar" aria-label="Navigasi utama">
            <div class="sidebar-nav-scroll">
                <SidebarNavigation mode="sidebar" :collapsed="sidebarCollapsed" />
            </div>
            <AppButton variant="ghost" type="button" class="sidebar-collapse" :aria-expanded="!sidebarCollapsed" aria-controls="desktop-sidebar" :aria-label="sidebarCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'" :title="sidebarCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'" @click="toggleDesktopSidebar">
                <span aria-hidden="true">{{ sidebarCollapsed ? '»' : '«' }}</span><span v-if="!sidebarCollapsed">Ciutkan sidebar</span>
            </AppButton>
        </aside>

        <div class="workspace">
            <header class="topbar">
                <div class="topbar-main">
                    <AppButton
                        variant="secondary"
                        icon-only
                        type="button"
                        class="icon-button sidebar-toggle"
                        :aria-controls="isMobileViewport ? 'mobile-sidebar' : 'desktop-sidebar'"
                        :aria-expanded="isMobileViewport ? sidebarOpen : !sidebarCollapsed"
                        aria-label="Buka atau tutup menu navigasi"
                        @click="isMobileViewport ? toggleSidebar() : toggleDesktopSidebar()"
                    >
                        <template #icon>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </template>
                    </AppButton>
                    <Link href="/dashboard" class="brand topbar-brand" :aria-label="`Buka dashboard ${appName}`">
                        <div class="brand-mark" aria-hidden="true">
                            <img :src="adminLogoUrl" alt="Logo Kementerian Agama">
                        </div>
                        <div class="brand-copy">
                            <strong>{{ appName }}</strong>
                            <small>{{ page.props.school?.name ?? 'Ruang kerja sekolah' }}</small>
                        </div>
                    </Link>
                    <div ref="searchRoot" class="global-search" :class="{ 'is-focused': searchFocused }">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <span class="sr-only">Cari di {{ appName }}</span>
                        <input
                            ref="searchInput"
                            v-model="searchQuery"
                            type="search"
                            role="combobox"
                            aria-autocomplete="list"
                            aria-label="Cari menu sekolah"
                            aria-controls="global-search-results"
                            :aria-expanded="searchOpen"
                            :aria-activedescendant="searchOpen && searchResults.length ? `menu-result-${activeSearchIndex}` : undefined"
                            placeholder="Cari menu…"
                            @focus="searchFocused = true; refreshMenuSearch()"
                            @input="refreshMenuSearch"
                            @keydown="handleSearchKeydown"
                        />
                        <div v-if="searchOpen" id="global-search-results" class="global-search-results" role="listbox" aria-label="Hasil pencarian menu">
                            <template v-if="searchResults.length">
                                <Link
                                    v-for="(entry, index) in searchResults"
                                    :key="entry.href + '-' + entry.label"
                                    :id="`menu-result-${index}`"
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
                        <div><small>Tahun ajaran aktif</small><strong>{{ activePeriodLabel }}</strong></div>
                    </div>
                    <span class="topbar-spacer" />
                    <AppButton variant="secondary" icon-only type="button" class="icon-button theme-toggle" :aria-pressed="darkTheme" :aria-label="darkTheme ? 'Gunakan tema terang' : 'Gunakan tema gelap'" @click="toggleTheme"><template #icon><svg viewBox="0 0 24 24" aria-hidden="true"><template v-if="darkTheme"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></template><path v-else d="M20.9 13.2A9 9 0 0 1 10.8 3.1a9 9 0 1 0 10.1 10.1Z"/></svg></template></AppButton>
                    <TopNavbar />
                </div>
            </header>
            <main id="main-content" tabindex="-1" :class="{ 'role-guru-main': isTeacherWorkspace }">
                <Breadcrumbs :title="layoutProps.title" />
                <p v-if="offline" class="connection-notice" role="status">Koneksi terputus. Perubahan yang belum dikirim tetap ada di formulir.</p>
                <slot />
            </main>
            <footer class="workspace-footer">© {{ new Date().getFullYear() }} {{ appName }}<span>{{ page.props.school?.name }}</span></footer>
        </div>
        <div v-if="toasts.length" class="app-toast-stack" role="region" aria-label="Notifikasi sistem" aria-live="polite">
            <div v-for="item in toasts" :key="item.id" class="app-toast" :class="`app-toast--${item.kind}`" :role="item.kind === 'error' ? 'alert' : 'status'">
                <span class="app-toast-mark" aria-hidden="true">{{ item.kind === 'success' ? '✓' : '!' }}</span>
                <span>{{ item.message }}</span>
                <AppButton variant="secondary" size="sm" icon-only type="button" aria-label="Tutup notifikasi" title="Tutup notifikasi" @click="dismissToast(item.id)">×</AppButton>
            </div>
        </div>
    </div>
</template>

<style scoped>
.blueprint-shell { --sidebar: 240px; --topbar: 56px; }
.blueprint-shell.is-sidebar-rail { display: block; }
.blueprint-shell .workspace { display: flex; flex-direction: column; padding-top: 56px; }
.blueprint-shell .topbar { position: fixed; inset: 0 0 auto; min-height: 56px; padding-inline: 20px; background: var(--surface); backdrop-filter: none; }
.blueprint-shell .topbar-main { min-height: 56px; }
.blueprint-shell main { flex: 1; min-width: 0; }
.blueprint-shell .brand-mark { width: 32px; height: 32px; flex-basis: 32px; border-radius: 6px; }
.blueprint-shell .sidebar-toggle { display: grid; }
.workspace-footer { display: flex; justify-content: space-between; gap: 16px; padding: 16px 24px; border-top: 1px solid var(--line); background: var(--surface); color: var(--ink-soft); font-size: var(--type-meta); }
.sidebar-collapse { flex-shrink: 0; justify-content: flex-start; gap: 8px; width: 100%; }
@media (min-width: 961px) {
    .blueprint-shell .desktop-sidebar { display: flex; top: 56px; width: 240px; padding: 10px 12px; }
    .blueprint-shell .workspace { margin-left: 240px; }
    .blueprint-shell.is-sidebar-rail .desktop-sidebar { width: 64px; padding-inline: 8px; }
    .blueprint-shell.is-sidebar-rail .workspace { margin-left: 64px; }
    .blueprint-shell.is-sidebar-rail .sidebar-collapse { justify-content: center; }
}
@media (max-width: 960px) {
    .blueprint-shell .workspace { margin-left: 0; }
    .workspace-footer { justify-content: center; padding: 14px 16px; }
    .workspace-footer > span { display: none; }
}
.app-toast-stack { top: 72px; bottom: auto; }
.brand-mark { overflow: hidden; }
.connection-notice { padding: .75rem 1rem; margin: 0 0 1rem; border: 1px solid var(--gold); border-radius: var(--radius-sm); background: var(--gold-soft); color: var(--gold); }
.brand-mark img { display: block; width: 100%; height: 100%; object-fit: contain; }
.brand-copy { min-width: 0; }
.topbar-spacer { flex: 1; }
.topbar-user { display: flex; flex: 0 0 auto; align-items: center; }
.app-toast-stack { position: fixed; right: 20px; bottom: max(20px, env(safe-area-inset-bottom)); z-index: 130; display: grid; gap: 8px; width: min(380px, calc(100vw - 24px)); pointer-events: none; }
.app-toast-stack .app-toast { pointer-events: auto; position: static; }
@media (max-width: 960px) { .topbar-user :deep(.profile-copy) { display: none; } }
@media (max-width: 720px) { .app-toast-stack { right: 12px; left: 12px; width: auto; } }
</style>

