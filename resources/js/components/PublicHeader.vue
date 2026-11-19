<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { filterServiceCategories } from '@/composables/usePublicServicesCatalog';
import type { SharedProps } from '@/types';

interface SchoolBrand {
    name: string;
    logo_url?: string | null;
}

type ActiveMenu = 'home' | 'profile' | 'organization' | 'programs' | 'student-life' | 'news' | 'gallery' | 'admissions' | 'contact';

const props = withDefaults(defineProps<{
    school: SchoolBrand;
    active?: ActiveMenu;
}>(), {
    active: undefined,
});

const page = usePage<SharedProps>();
const man4 = computed(() => page.props.publicPortalTheme === 'man4-banjar');
const brand = computed(() => man4.value ? {
    name: 'Portal Madrasah',
    logo_url: '/brand/favicon/favicon.svg',
} : props.school);
const initial = computed(() => brand.value.name.trim().charAt(0).toUpperCase() || 'S');
const currentPath = computed(() => page.url.split(/[?#]/, 1)[0]);
const isCurrent = (href: string) => currentPath.value === href
    || (href !== '/' && currentPath.value.startsWith(href + '/'));
const menus: { label: string; href: string; key?: ActiveMenu }[] = [
    { label: 'Beranda', href: '/', key: 'home' },
    { label: 'Akademik', href: '/akademik-fasilitas', key: 'programs' },
    { label: 'Kesiswaan', href: '/kesiswaan-prestasi', key: 'student-life' },
    { label: 'Berita', href: '/berita', key: 'news' },
    { label: 'Galeri', href: '/galeri', key: 'gallery' },
    { label: 'Kontak', href: '/kontak', key: 'contact' },
];
const man4Menus = [
    { label: 'Zona Integritas', href: '/zona-integritas' },
    { label: 'PTSP Digital', href: '/layanan/ptsp' },
    { label: 'Peminatan', href: '/akademik-fasilitas', key: 'programs' },
    { label: 'Kesiswaan', href: '/kesiswaan-prestasi', key: 'student-life' },
    { label: 'Perpustakaan', href: '/perpustakaan' },
    { label: 'Alumni', href: '/alumni' },
    { label: 'Berita', href: '/berita', key: 'news' },
    { label: 'Prestasi', href: '/prestasi' },
    { label: 'Galeri', href: '/galeri', key: 'gallery' },
    { label: 'Kontak', href: '/kontak', key: 'contact' },
    { label: 'Lacak Berkas', href: '/lacak' },
    { label: 'Cek SKL', href: '/layanan/kelulusan' },
] satisfies typeof menus;
const operationsMenus = [
    { label: 'Kios Presensi', href: '/kios-presensi' },
    { label: 'Izin Keluar-Masuk (Piket)', href: '/piket/izin-keluar' },
    { label: 'Pos Satpam', href: '/pos-jaga' },
    { label: 'Disiplin BK', href: '/bk/kasus' },
];
const visibleMenus = computed(() => man4.value ? man4Menus : menus.slice(1));
const headerRef = ref<HTMLElement | null>(null);
const navToggleRef = ref<HTMLButtonElement | null>(null);
const profileTriggerRef = ref<HTMLButtonElement | null>(null);
const servicesTriggerRef = ref<HTMLButtonElement | null>(null);
const servicesSearchRef = ref<HTMLInputElement | null>(null);

const menuOpen = ref(false);
const profileMenuOpen = ref(false);
const servicesMenuOpen = ref(false);
const serviceQuery = ref('');

const profileActive = computed(() => props.active === 'profile' || props.active === 'organization');
const portalHref = computed(() => page.props.auth?.user ? '/dashboard' : '/login');
const portalLabel = computed(() => page.props.auth?.user ? 'Dashboard' : 'Portal sekolah');
const filteredServiceCategories = computed(() => filterServiceCategories(serviceQuery.value));

const servicesPanelTop = ref(0);

const closeMenu = (restoreFocus = false) => {
    const wasServicesOpen = servicesMenuOpen.value;
    const wasProfileOpen = profileMenuOpen.value;
    const wasMobileOpen = menuOpen.value;

    menuOpen.value = false;
    profileMenuOpen.value = false;
    servicesMenuOpen.value = false;

    if (!restoreFocus) return;

    if (wasServicesOpen) {
        servicesTriggerRef.value?.focus();
    } else if (wasProfileOpen) {
        profileTriggerRef.value?.focus();
    } else if (wasMobileOpen) {
        navToggleRef.value?.focus();
    }
};

const toggleProfileMenu = () => {
    servicesMenuOpen.value = false;
    profileMenuOpen.value = !profileMenuOpen.value;
};

const toggleServicesMenu = async () => {
    profileMenuOpen.value = false;
    servicesMenuOpen.value = !servicesMenuOpen.value;
    if (servicesMenuOpen.value) {
        servicesPanelTop.value = Math.max(0, servicesTriggerRef.value?.getBoundingClientRect().bottom ?? 0);
        await nextTick();
        servicesSearchRef.value?.focus();
    }
};

const handleDocumentPointerdown = (event: PointerEvent) => {
    if (headerRef.value && !headerRef.value.contains(event.target as Node)) {
        closeMenu(false);
    }
};

onMounted(() => {
    document.addEventListener('pointerdown', handleDocumentPointerdown);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', handleDocumentPointerdown);
});
</script>

<template>
    <Head v-if="man4">
        <link head-key="man4-fonts" rel="stylesheet" href="/madrasah-theme/fonts.css" />
    </Head>
    <header
        ref="headerRef"
        class="public-site-header"
        :class="{ 'is-home': active === 'home', 'is-menu-open': menuOpen, 'is-man4': man4 }"
        @keydown.esc.stop="closeMenu(true)"
    >
        <div v-if="man4" class="man4-utility">
            <span>Portal School Suite</span>
            <div>
                <Link href="/jadwal-salat">Jadwal Salat</Link>
                <Link href="/pusat-unduh">Pusat Unduh</Link>
            </div>
        </div>
        <div class="public-site-header-top">
            <Link href="/" class="brand" aria-label="Beranda sekolah">
                <span class="brand-mark">
                    <img v-if="brand.logo_url" :src="brand.logo_url" alt="" />
                    <span v-else>{{ initial }}</span>
                </span>
                <span class="brand-name">
                    {{ brand.name }}
                    <small v-if="man4">Informasi dan layanan sekolah</small>
                </span>
            </Link>

            <div class="public-site-header-actions" aria-label="Aksi sekolah">
                <Link :href="portalHref" class="portal-link">{{ portalLabel }}</Link>
                <Link
                    href="/ppdb"
                    class="admissions-action ui-button ui-button--primary ui-button--md"
                    :aria-current="props.active === 'admissions' ? 'page' : undefined"
                >
                    {{ man4 ? 'PPDB' : 'SPMB' }}
                </Link>
                <Link v-if="man4" href="/lacak" class="man4-tracking">Lacak</Link>
                <button
                    ref="navToggleRef"
                    type="button"
                    class="nav-toggle ui-button ui-button--secondary ui-button--md"
                    aria-controls="public-navigation"
                    :aria-expanded="menuOpen"
                    @click="menuOpen = !menuOpen"
                >
                    <span>{{ menuOpen ? 'Tutup' : 'Menu' }}</span>
                    <i aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="public-site-header-nav-row">
            <nav id="public-navigation" class="public-nav" :class="{ 'is-open': menuOpen }" aria-label="Navigasi publik">
                <Link
                    :href="menus[0].href"
                    :aria-current="isCurrent('/') ? 'page' : undefined"
                    @click="closeMenu(false)"
                >
                    {{ menus[0].label }}
                </Link>

                <div class="profile-menu" :class="{ 'is-active': profileActive }">
                    <button
                        ref="profileTriggerRef"
                        type="button"
                        class="nav-menu-trigger"
                        :aria-expanded="profileMenuOpen"
                        aria-controls="profile-navigation"
                        :aria-current="profileActive ? 'page' : undefined"
                        @click.stop="toggleProfileMenu"
                    >
                        Profil
                        <span class="nav-chevron" aria-hidden="true"></span>
                    </button>
                    <div v-if="profileMenuOpen" id="profile-navigation" class="profile-dropdown">
                        <template v-if="man4">
                            <a v-for="item in page.props.publicProfileMenu" :key="item.href" :href="item.href"
                                :aria-current="page.url === item.href ? 'page' : undefined" @click="closeMenu(false)">
                                <i class="fa-solid" :class="item.icon" aria-hidden="true"></i>
                                <span>{{ item.label }}</span>
                            </a>
                        </template>
                        <template v-else>
                            <Link href="/profil-sekolah" :aria-current="active === 'profile' ? 'page' : undefined" @click="closeMenu(false)">Profil sekolah</Link>
                            <Link href="/struktur-organisasi" :aria-current="active === 'organization' ? 'page' : undefined" @click="closeMenu(false)">Struktur organisasi</Link>
                        </template>
                    </div>
                </div>

                <div class="services-menu">
                    <button
                        ref="servicesTriggerRef"
                        type="button"
                        class="nav-menu-trigger"
                        :aria-expanded="servicesMenuOpen"
                        aria-controls="services-navigation"
                        @click.stop="toggleServicesMenu"
                    >
                        E-Layanan
                        <span class="nav-chevron" aria-hidden="true"></span>
                    </button>

                    <div
                        v-if="servicesMenuOpen"
                        id="services-navigation"
                        class="services-panel"
                        :style="man4 ? { '--man4-menu-top': servicesPanelTop + 'px' } : undefined"
                        role="region"
                        aria-label="Panel E-Layanan"
                    >
                        <div class="services-panel__header">
                            <div class="services-panel__search">
                                <label class="sr-only" for="public-services-search">Cari E-Layanan</label>
                                <input
                                    id="public-services-search"
                                    ref="servicesSearchRef"
                                    v-model="serviceQuery"
                                    type="search"
                                    class="services-panel__input"
                                    placeholder="Cari layanan (mis. PTSP, izin, alumni, aduan, pustaka)..."
                                />
                            </div>
                            <Link :href="portalHref" class="services-panel__portal-cta" @click="closeMenu(false)">
                                {{ portalLabel }} →
                            </Link>
                        </div>

                        <Link v-if="man4" href="/e-layanan" class="man4-catalog-link" @click="closeMenu(false)">
                            Buka katalog E-Layanan
                        </Link>
                        <div v-if="filteredServiceCategories.length" class="services-panel__grid">
                            <section
                                v-for="category in filteredServiceCategories"
                                :key="category.key"
                                class="services-panel__category"
                            >
                                <h3 class="services-panel__cat-title">{{ category.title }}</h3>
                                <p class="services-panel__cat-subtitle">{{ category.subtitle }}</p>
                                <ul class="services-panel__list">
                                    <li v-for="item in category.items" :key="item.id">
                                        <Link
                                            :href="item.href"
                                            class="services-panel__item"
                                            @click="closeMenu(false)"
                                        >
                                            <span class="services-panel__item-top">
                                                <strong>{{ item.title }}</strong>
                                                <span
                                                    v-if="item.badge"
                                                    class="services-panel__badge"
                                                    :class="{ 'is-soon': !item.available }"
                                                >
                                                    {{ item.badge }}
                                                </span>
                                            </span>
                                            <span class="services-panel__item-desc">{{ item.description }}</span>
                                        </Link>
                                    </li>
                                </ul>
                            </section>
                        </div>

                        <div v-else class="services-panel__empty" role="status">
                            <p>Layanan tidak ditemukan untuk "{{ serviceQuery }}".</p>
                            <button type="button" class="services-panel__reset" @click="serviceQuery = ''">
                                Tampilkan semua layanan
                            </button>
                        </div>
                        <nav v-if="man4 && !serviceQuery" class="man4-operations" aria-label="Pintasan operasional">
                            <Link v-for="item in operationsMenus" :key="item.href" :href="item.href" @click="closeMenu(false)">
                                {{ item.label }}
                            </Link>
                        </nav>
                    </div>
                </div>

                <Link
                    v-for="menu in visibleMenus"
                    :key="menu.href"
                    :href="menu.href"
                    :aria-current="(man4 ? isCurrent(menu.href) : props.active === menu.key) ? 'page' : undefined"
                    @click="closeMenu(false)"
                >
                    {{ menu.label }}
                </Link>



                <Link :href="portalHref" class="mobile-portal-link" @click="closeMenu(false)">{{ portalLabel }}</Link>
            </nav>
        </div>
    </header>
</template>

<style scoped>
.public-site-header {
    position: sticky;
    top: 0;
    z-index: 20;
    width: min(1180px, calc(100% - 3rem));
    margin-inline: auto;
    padding: 1rem 0 0;
    background: transparent;
}

.public-site-header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    min-width: 0;
    padding: .1rem 0 1rem;
    border-bottom: 1px solid color-mix(in srgb, var(--public-ink, #173044) 13%, transparent);
}

.brand {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: .7rem;
    color: var(--public-ink, #173044);
    font-size: var(--type-md);
    font-weight: var(--weight-bold);
    line-height: 1.25;
    text-decoration: none;
}

.brand-name {
    max-width: 15rem;
}

.brand-mark {
    display: grid;
    flex: 0 0 auto;
    place-items: center;
    width: 2.4rem;
    height: 2.4rem;
    padding: .28rem;
    overflow: hidden;
    color: var(--public-deep, #123247);
    background: #fff;
    border: 1px solid color-mix(in srgb, var(--public-deep, #123247) 18%, transparent);
    border-radius: .75rem .75rem .75rem .2rem;
    font-family: var(--font-display);
    font-size: 1.2rem;
    box-shadow: 0 4px 10px rgb(18 50 71 / 14%);
}

.brand-mark img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: transparent;
}

.public-site-header-actions {
    display: flex;
    align-items: center;
    flex: 0 0 auto;
    gap: .35rem;
}

.portal-link {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    padding: .55rem .65rem;
    color: var(--public-muted-strong, #405762);
    font-size: var(--type-sm);
    font-weight: var(--weight-bold);
    text-decoration: none;
}

.portal-link:hover,
.portal-link:focus-visible {
    color: var(--public-ink, #173044);
}

.public-nav {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: .15rem;
    width: 100%;
    min-width: 0;
}

.public-nav > a,
.nav-menu-trigger {
    position: relative;
    display: inline-flex;
    flex: 0 0 auto;
    min-height: 48px;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    padding: .7rem .7rem .65rem;
    color: var(--public-muted-strong, #405762);
    border-radius: 0;
    font-size: var(--type-meta);
    font-weight: var(--weight-semibold);
    line-height: 1.2;
    white-space: nowrap;
    text-decoration: none;
    transition: color .16s ease, background-color .16s ease;
}

.public-nav > a:hover,
.public-nav > a:focus-visible,
.nav-menu-trigger:hover,
.nav-menu-trigger:focus-visible {
    color: var(--public-ink, #173044);
}

.public-nav > a[aria-current='page'],
.profile-menu.is-active .nav-menu-trigger,
.nav-menu-trigger[aria-expanded='true'] {
    color: var(--public-ink, #173044);
    font-weight: var(--weight-bold);
}

.public-nav > a[aria-current='page']::after,
.profile-menu.is-active .nav-menu-trigger::after {
    position: absolute;
    right: .7rem;
    bottom: 0;
    left: .7rem;
    height: 2px;
    background: var(--public-primary, #3157a4);
    border-radius: 999px;
    content: '';
}

.profile-menu {
    position: relative;
    flex: 0 0 auto;
}

.nav-menu-trigger {
    cursor: pointer;
    background: transparent;
    border: 0;
    font-family: inherit;
    font-size: inherit;
    font-weight: var(--weight-semibold);
    line-height: inherit;
}

.nav-chevron {
    width: .42rem;
    height: .42rem;
    border-right: 1.5px solid currentColor;
    border-bottom: 1.5px solid currentColor;
    transform: translateY(-.1rem) rotate(45deg);
    transition: transform .16s ease;
}

.nav-menu-trigger[aria-expanded='true'] .nav-chevron {
    transform: translateY(.1rem) rotate(225deg);
}

.profile-dropdown {
    position: absolute;
    top: calc(100% + .55rem);
    left: -.35rem;
    display: grid;
    min-width: 13rem;
    gap: .15rem;
    padding: .35rem;
    background: var(--public-surface, #fffdf8);
    border: 1px solid color-mix(in srgb, var(--public-ink, #173044) 14%, transparent);
    border-radius: .7rem;
    box-shadow: 0 14px 30px rgb(18 50 71 / 12%);
}

.profile-dropdown a {
    padding: .65rem .7rem;
    color: var(--public-muted-strong, #405762);
    border-radius: .45rem;
    font-size: var(--type-meta);
    font-weight: var(--weight-semibold);
    line-height: 1.3;
    text-decoration: none;
}

.profile-dropdown a:hover,
.profile-dropdown a:focus-visible,
.profile-dropdown a[aria-current='page'] {
    color: var(--public-ink, #173044);
    background: color-mix(in srgb, var(--public-primary, #3157a4) 10%, transparent);
}

.public-site-header-nav-row {
    display: flex;
    align-items: center;
    min-height: 3.65rem;
}

.mobile-portal-link {
    display: none !important;
}

.nav-toggle {
    display: none;
}

.nav-toggle i,
.nav-toggle i::before,
.nav-toggle i::after {
    display: block;
    width: 1rem;
    height: 2px;
    background: currentColor;
    border-radius: 999px;
    content: '';
}

.nav-toggle i { position: relative; }
.nav-toggle i::before { position: absolute; top: -.32rem; }
.nav-toggle i::after { position: absolute; top: .32rem; }

@media (max-width: 1040px) {
    .public-site-header-nav-row {
        position: absolute;
        top: 100%;
        right: 1rem;
        left: 1rem;
        display: block;
        min-height: 0;
        padding-top: .5rem;
        pointer-events: none;
    }

    .public-nav {
        display: grid;
        max-height: calc(100dvh - 6rem);
        overflow-y: auto;
        visibility: hidden;
        opacity: 0;
        transform: scale(.97);
        transform-origin: top right;
        transition: opacity .2s ease, transform .2s ease, visibility 0s linear .2s;
        order: 3;
        flex-basis: 100%;
        align-items: stretch;
        justify-content: stretch;
        padding: .35rem;
        background: var(--public-surface-alt, #fff);
        border: 1px solid var(--public-line, #d8dee8);
        border-radius: .7rem;
        box-shadow: 0 12px 30px rgb(18 50 71 / 14%);
    }

    .public-nav.is-open {
        visibility: visible;
        opacity: 1;
        transform: scale(1);
        pointer-events: auto;
        transition: opacity .2s ease, transform .2s ease, visibility 0s;
    }

    .public-nav > a,
    .nav-menu-trigger {
        position: static;
        width: 100%;
        justify-content: space-between;
        padding: .7rem;
        text-align: left;
    }

    .public-nav > a[aria-current='page']::after,
    .profile-menu.is-active .nav-menu-trigger::after {
        display: none;
    }

    .profile-dropdown {
        position: static;
        min-width: 0;
        margin: .15rem 0 .25rem;
        padding: 0 0 0 .65rem;
        background: transparent;
        border: 0;
        border-left: 1px solid var(--public-line-strong, #d8dee8);
        border-radius: 0;
        box-shadow: none;
    }

    .profile-dropdown a {
        display: block;
        padding: .55rem .7rem;
    }

    .mobile-portal-link {
        display: inline-flex !important;
        margin-top: .25rem;
        color: var(--public-ink, #173044) !important;
        border-top: 1px solid var(--public-line, #d8dee8);
        border-radius: 0 !important;
        font-weight: var(--weight-bold) !important;
    }
}

@media (max-width: 640px) {
    .public-site-header {
        width: calc(100% - 2rem);
        padding-top: .8rem;
    }

    .brand {
        align-items: flex-start;
    }

    .brand-name {
        max-width: 12rem;
    }

    .public-site-header-actions {
        margin-left: auto;
    }

    .portal-link {
        display: none;
    }

    .public-nav { width: 100%; }
}

@media (max-width: 420px) {
    .brand-name { max-width: 8.5rem; }
}

@media (prefers-reduced-motion: reduce) {
    .public-site-header *,
    .public-site-header *::before,
    .public-site-header *::after {
        transition-duration: .01ms !important;
        scroll-behavior: auto !important;
    }
}

/* Shared public navigation: compact utility chrome with a clear active state. */
.public-site-header {
    padding-top: 1.25rem;
}

.public-site-header-top {
    padding-bottom: .75rem;
    border-bottom: 0;
}

.public-site-header-nav-row {
    min-height: 3.2rem;
    border-top: 1px solid color-mix(in srgb, var(--public-ink, #173044) 13%, transparent);
    border-bottom: 1px solid color-mix(in srgb, var(--public-ink, #173044) 13%, transparent);
}

.public-nav {
    gap: .35rem;
}

.public-nav > a,
.nav-menu-trigger {
    min-height: 36px;
    padding: .45rem .8rem;
    border-radius: 999px;
}

.public-nav > a[aria-current='page'],
.profile-menu.is-active .nav-menu-trigger,
.nav-menu-trigger[aria-expanded='true'] {
    color: #fff !important;
    background: var(--public-primary, #3157a4);
}

.public-nav > a[aria-current='page']::after,
.profile-menu.is-active .nav-menu-trigger::after {
    display: none;
}

@media (max-width: 1040px) {
    .public-site-header-nav-row {
        border-bottom: 0;
    }

    .public-nav > a,
    .nav-menu-trigger {
        border-radius: .65rem;
    }
}

@media (max-width: 420px) {
    .public-site-header-top {
        gap: .75rem;
    }

    .brand {
        min-width: 0;
        flex: 1 1 auto;
        gap: .55rem;
        font-size: var(--type-sm);
    }

    .brand-name {
        max-width: 7rem;
        overflow: hidden;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
        line-height: 1.2;
    }

    .public-site-header-actions {
        gap: .2rem;
    }
}

.public-site-header.public-site-header {
    width: min(1180px, 100%) !important;
    max-width: 1180px !important;
    box-sizing: border-box;
    padding: 0 1rem !important;
    background: #fff;
    border-bottom: 1px solid var(--public-line, #dce4ee);
}

@media (min-width: 1041px) {
    .public-site-header.public-site-header {
        display: grid;
        grid-template-columns: minmax(180px, 1fr) auto minmax(180px, 1fr);
        align-items: center;
        gap: 1.25rem;
    }
    .public-site-header .public-site-header-top { display: contents; }
    .public-site-header .brand { grid-column: 1; grid-row: 1; }
    .public-site-header .public-site-header-actions { grid-column: 3; grid-row: 1; justify-self: end; }
    .public-site-header .public-site-header-nav-row { grid-column: 2; grid-row: 1; min-height: 72px; border: 0; }
    .public-site-header .public-nav > a[href='/'] { display: none; }
    .public-site-header .public-nav > a,
    .public-site-header .nav-menu-trigger { min-height: 44px; padding-inline: .55rem; border-radius: 8px; }
}

@media (max-width: 1040px) {
    .public-site-header.public-site-header { padding-inline: 1rem; }
    .public-site-header .public-site-header-top { padding: .85rem 0; }
    .public-site-header .public-site-header-nav-row { border: 0; }
}
</style>

<style scoped>
.services-menu {
    position: relative;
    flex: 0 0 auto;
}

.services-panel {
    position: fixed;
    top: 4.9rem;
    left: 50%;
    z-index: 40;
    width: min(960px, calc(100vw - 2rem));
    max-height: min(78dvh, 640px);
    overflow-y: auto;
    transform: translateX(-50%);
    padding: 1.15rem 1.25rem 1.35rem;
    background: var(--public-panel-surface, #ffffff);
    border: 1px solid var(--public-panel-border, #d8e2ee);
    border-radius: var(--radius-lg, 18px);
    box-shadow: var(--shadow-md, 0 14px 35px rgba(31, 49, 82, .12));
    color: var(--public-ink, #173044);
}

.services-panel__header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1rem;
    padding-bottom: .85rem;
    border-bottom: 1px solid var(--public-panel-border, #d8e2ee);
}

.services-panel__search {
    flex: 1 1 260px;
    min-width: 0;
}

.services-panel__input {
    width: 100%;
    min-height: 44px;
    padding: .55rem .85rem;
    color: var(--public-ink, #173044);
    background: var(--public-surface, #f5f8fb);
    border: 1px solid var(--public-panel-border, #d8e2ee);
    border-radius: var(--radius-sm, 8px);
    font: inherit;
    font-size: var(--type-sm, .875rem);
}

.services-panel__input:focus-visible {
    outline: 0;
    border-color: var(--public-primary, #3157a4);
    box-shadow: var(--shadow-focus, 0 0 0 3px color-mix(in srgb, #3157a4 16%, transparent));
}

.services-panel__portal-cta {
    display: inline-flex;
    align-items: center;
    min-height: 44px;
    padding: .45rem .9rem;
    color: var(--public-primary, #3157a4);
    background: color-mix(in srgb, var(--public-primary, #3157a4) 10%, transparent);
    border-radius: var(--radius-sm, 8px);
    font-size: var(--type-sm, .875rem);
    font-weight: var(--weight-bold, 700);
    text-decoration: none;
    white-space: nowrap;
}

.services-panel__portal-cta:focus-visible {
    outline: 2px solid var(--public-primary, #3157a4);
    outline-offset: 2px;
}

.services-panel__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.services-panel__category {
    display: grid;
    align-content: start;
    gap: .5rem;
    min-width: 0;
}

.services-panel__cat-title {
    margin: 0;
    color: var(--public-ink, #173044);
    font-size: var(--type-sm, .875rem);
    font-weight: var(--weight-bold, 700);
}

.services-panel__cat-subtitle {
    margin: 0 0 .25rem;
    color: var(--public-muted, #526673);
    font-size: var(--type-caption, .75rem);
    line-height: 1.4;
}

.services-panel__list {
    display: grid;
    gap: .35rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.services-panel__item {
    display: grid;
    gap: .2rem;
    min-height: 44px;
    padding: .6rem .7rem;
    border: 1px solid transparent;
    border-radius: var(--radius-sm, 8px);
    text-decoration: none;
    transition: background-color .15s ease, border-color .15s ease;
}

.services-panel__item:hover,
.services-panel__item:focus-visible {
    background: var(--public-surface, #f5f8fb);
    border-color: var(--public-panel-border, #d8e2ee);
    outline: 0;
}

.services-panel__item:focus-visible {
    box-shadow: var(--shadow-focus, 0 0 0 3px color-mix(in srgb, #3157a4 16%, transparent));
}

.services-panel__item-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    color: var(--public-ink, #173044);
    font-size: var(--type-sm, .875rem);
    line-height: 1.3;
}

.services-panel__badge {
    flex: 0 0 auto;
    padding: .1rem .45rem;
    border-radius: var(--radius-pill, 999px);
    font-size: .7rem;
    font-weight: var(--weight-bold, 700);
    color: var(--public-primary, #3157a4);
    background: color-mix(in srgb, var(--public-primary, #3157a4) 12%, transparent);
}

.services-panel__badge.is-soon {
    color: var(--public-muted, #526673);
    background: var(--surface-strong, #edf1f7);
}

.services-panel__item-desc {
    color: var(--public-muted, #526673);
    font-size: var(--type-caption, .75rem);
    line-height: 1.4;
}

.services-panel__empty {
    display: grid;
    justify-items: center;
    gap: .65rem;
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--public-muted, #526673);
    font-size: var(--type-sm, .875rem);
}

.services-panel__empty p {
    margin: 0;
}

.services-panel__reset {
    min-height: 44px;
    padding: .45rem .9rem;
    color: var(--public-primary, #3157a4);
    background: color-mix(in srgb, var(--public-primary, #3157a4) 10%, transparent);
    border: 0;
    border-radius: var(--radius-sm, 8px);
    font-weight: var(--weight-semibold, 600);
    cursor: pointer;
}

@media (max-width: 1040px) {
    .nav-toggle { display: inline-flex; min-height: 44px; }

    .public-nav {
        padding-bottom: 4.5rem;
    }

    .services-panel {
        position: static;
        transform: none;
        width: 100%;
        max-height: none;
        margin: .25rem 0 .35rem;
        padding: .75rem;
        border-radius: var(--radius-md, 12px);
        box-shadow: none;
    }

    .services-panel__grid {
        grid-template-columns: 1fr;
    }

    .services-panel__portal-cta {
        width: 100%;
        justify-content: center;
    }
}
</style>


<style scoped>
.public-site-header.is-man4 {
    --public-primary: #047857;
    --public-accent: #b45309;
    --public-ink: #0f172a;
    --public-muted: #475569;
    --public-line: #e2e8f0;
    --font-ui: Inter, system-ui, sans-serif;
    width: 100% !important;
    max-width: none !important;
    padding: 0 !important;
    display: block;
    border-bottom: 1px solid #e2e8f0;
    font-family: var(--font-ui);
}

.is-man4 .profile-dropdown {
    min-width: min(16rem, calc(100vw - 2rem));
    padding: .5rem;
    border-radius: 1rem;
    border-color: #e2e8f0;
}

.is-man4 .profile-dropdown a {
    display: flex;
    align-items: center;
    gap: .65rem;
    padding: .6rem .7rem;
    border-radius: .7rem;
}

.is-man4 .profile-dropdown i {
    width: 1rem;
    color: #047857;
    text-align: center;
}

.is-man4 .man4-utility {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: .55rem clamp(1rem, 3.3vw, 3rem);
    color: #ecfdf5;
    background: #064e3b;
    font-size: .75rem;
}

.man4-utility div {
    display: flex;
    gap: 1rem;
}

.man4-utility a:hover {
    color: #fcd34d;
}

.is-man4 .public-site-header-top {
    display: flex;
    min-height: 64px;
    padding: .65rem clamp(1rem, 3.3vw, 3rem);
    gap: .75rem;
    border-bottom: 1px solid #e2e8f0;
}

.is-man4 .brand {
    flex: 1 1 auto;
    min-width: 0;
    font-family: 'Plus Jakarta Sans', Inter, sans-serif;
}

.is-man4 .brand-mark {
    width: 40px;
    height: 40px;
    background: transparent;
    border: 0;
    box-shadow: none;
}

.is-man4 .brand-name {
    max-width: none;
    overflow-wrap: anywhere;
    font-size: clamp(.78rem, 2vw, 1.12rem);
    font-weight: 800;
}

.is-man4 .brand-name small {
    display: block;
    margin-top: .16rem;
    color: #475569;
    font-size: .66rem;
    font-weight: 500;
}

.is-man4 .admissions-action {
    color: #1c1917;
    background: #fbbf24;
    border-color: #f59e0b;
    box-shadow: none;
}

.man4-tracking,
.man4-catalog-link {
    color: #047857;
    font-size: .8rem;
    font-weight: 700;
}

.man4-catalog-link {
    display: inline-block;
    margin: .4rem 0 .85rem;
    text-decoration: underline;
}

.man4-operations {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-top: 1rem;
}

.man4-operations a {
    padding: .4rem .65rem;
    border-radius: .5rem;
    color: #065f46;
    background: #ecfdf5;
    font-size: .78rem;
    font-weight: 600;
}

.is-man4 .public-site-header-nav-row {
    min-height: 0;
    padding-inline: clamp(.5rem, 2.7vw, 2.4rem);
    background: #f8fafc;
}

.is-man4 .public-nav {
    flex-wrap: wrap;
    gap: .2rem;
}

.is-man4 .public-nav > a[href='/'] {
    display: inline-flex;
}

.is-man4 .public-nav > a[aria-current='page'] {
    background: #d1fae5;
    color: #065f46;
}

@media (min-width: 1041px) {
    .is-man4 .services-panel {
        top: var(--man4-menu-top, 8rem);
        max-height: min(70dvh, 640px);
    }
}

@media (max-width: 640px) {
    .is-man4 .man4-utility,
    .is-man4 .man4-tracking {
        display: none;
    }

    .is-man4 .brand-mark {
        width: 28px;
        height: 34px;
    }

    .is-man4 .brand {
        gap: .4rem;
    }
}
</style>
