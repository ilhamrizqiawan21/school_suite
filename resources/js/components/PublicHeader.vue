<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
const initial = props.school.name.trim().charAt(0).toUpperCase() || 'S';
const menus: { label: string; href: string; key: ActiveMenu }[] = [
    { label: 'Beranda', href: '/', key: 'home' },
    { label: 'Akademik', href: '/akademik-fasilitas', key: 'programs' },
    { label: 'Kesiswaan', href: '/kesiswaan-prestasi', key: 'student-life' },
    { label: 'Berita', href: '/berita', key: 'news' },
    { label: 'Galeri', href: '/galeri', key: 'gallery' },
    { label: 'Kontak', href: '/kontak', key: 'contact' },
];
const menuOpen = ref(false);
const profileMenuOpen = ref(false);
const profileActive = computed(() => props.active === 'profile' || props.active === 'organization');
const portalHref = computed(() => page.props.auth?.user ? '/dashboard' : '/login');
const portalLabel = computed(() => page.props.auth?.user ? 'Dashboard' : 'Portal sekolah');
const closeMenu = () => {
    menuOpen.value = false;
    profileMenuOpen.value = false;
};
const toggleProfileMenu = () => {
    profileMenuOpen.value = !profileMenuOpen.value;
};
</script>

<template>
    <header class="public-site-header" :class="{ 'is-home': active === 'home', 'is-menu-open': menuOpen }" @keydown.esc="closeMenu">
        <div class="public-site-header-top">
            <Link href="/" class="brand" aria-label="Beranda sekolah">
                <span class="brand-mark">
                    <img v-if="school.logo_url" :src="school.logo_url" :alt="`Logo ${school.name}`" />
                    <span v-else>{{ initial }}</span>
                </span>
                <span class="brand-name">{{ school.name }}</span>
            </Link>

            <div class="public-site-header-actions" aria-label="Aksi sekolah">
                <Link :href="portalHref" class="portal-link">{{ portalLabel }}</Link>
                <Link
                    href="/ppdb"
                    class="admissions-action ui-button ui-button--primary ui-button--md"
                    :aria-current="props.active === 'admissions' ? 'page' : undefined"
                >
                    PPDB
                </Link>
                <button
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
                    :aria-current="props.active === menus[0].key ? 'page' : undefined"
                    @click="closeMenu"
                >
                    {{ menus[0].label }}
                </Link>

                <div class="profile-menu" :class="{ 'is-active': profileActive }">
                    <button
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
                        <Link
                            href="/profil-sekolah"
                            :aria-current="props.active === 'profile' ? 'page' : undefined"
                            @click="closeMenu"
                        >
                            Profil sekolah
                        </Link>
                        <Link
                            href="/struktur-organisasi"
                            :aria-current="props.active === 'organization' ? 'page' : undefined"
                            @click="closeMenu"
                        >
                            Struktur organisasi
                        </Link>
                    </div>
                </div>

                <Link
                    v-for="menu in menus.slice(1)"
                    :key="menu.key"
                    :href="menu.href"
                    :aria-current="props.active === menu.key ? 'page' : undefined"
                    @click="closeMenu"
                >
                    {{ menu.label }}
                </Link>

                <Link :href="portalHref" class="mobile-portal-link" @click="closeMenu">{{ portalLabel }}</Link>
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
@media (max-width: 1040px) {
    .nav-toggle { display: inline-flex; }
}
</style>
