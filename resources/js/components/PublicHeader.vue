<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

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

const initial = props.school.name.trim().charAt(0).toUpperCase() || 'S';
const menus: { label: string; href: string; key: ActiveMenu }[] = [
    { label: 'Beranda', href: '/', key: 'home' },
    { label: 'Profil', href: '/profil-sekolah', key: 'profile' },
    { label: 'Organisasi', href: '/struktur-organisasi', key: 'organization' },
    { label: 'Akademik', href: '/akademik-fasilitas', key: 'programs' },
    { label: 'Kesiswaan', href: '/kesiswaan-prestasi', key: 'student-life' },
    { label: 'Berita', href: '/berita', key: 'news' },
    { label: 'Galeri', href: '/galeri', key: 'gallery' },
    { label: 'PPDB', href: '/ppdb', key: 'admissions' },
    { label: 'Kontak', href: '/kontak', key: 'contact' },
];
const primaryMenus = menus.filter((menu) => menu.key !== 'contact');
</script>

<template>
    <header class="public-header">
        <div class="public-header-top">
            <Link href="/" class="brand" aria-label="Beranda sekolah">
                <span class="brand-mark">
                    <img v-if="school.logo_url" :src="school.logo_url" :alt="`Logo ${school.name}`" />
                    <span v-else>{{ initial }}</span>
                </span>
                <span class="brand-name">{{ school.name }}</span>
            </Link>

            <div class="public-header-actions" aria-label="Aksi sekolah">
                <Link
                    href="/kontak"
                    class="header-utility-link"
                    :aria-current="props.active === 'contact' ? 'page' : undefined"
                >
                    Kontak
                </Link>
                <Link href="/login" class="header-action">Masuk</Link>
            </div>
        </div>

        <div class="public-header-nav-row">
            <nav class="public-nav" aria-label="Navigasi publik">
                <Link
                    v-for="menu in primaryMenus"
                    :key="menu.key"
                    :href="menu.href"
                    :aria-current="props.active === menu.key ? 'page' : undefined"
                >
                    {{ menu.label }}
                </Link>
            </nav>
        </div>
    </header>
</template>

<style scoped>
.public-header {
    display: grid;
    gap: 0;
    width: min(1180px, calc(100% - 3rem));
    margin-inline: auto;
    padding: 1rem 0 0;
}

.public-header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    min-width: 0;
    padding-bottom: 1rem;
    border-bottom: 1px solid color-mix(in srgb, var(--public-ink, #173044) 12%, transparent);
}

.brand {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: .7rem;
    color: var(--public-ink, #173044);
    font-size: .95rem;
    font-weight: 800;
    line-height: 1.25;
    text-decoration: none;
}

.brand-name {
    max-width: 17rem;
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

.public-header-actions {
    display: flex;
    align-items: center;
    flex: 0 0 auto;
    gap: .5rem;
}

.header-utility-link {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    padding: .55rem .7rem;
    color: var(--public-muted-strong, #405762);
    font-size: .82rem;
    font-weight: 700;
    text-decoration: none;
}

.header-utility-link:hover,
.header-utility-link:focus-visible,
.header-utility-link[aria-current='page'] {
    color: var(--public-ink, #173044);
}

.public-header-actions .header-action {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    padding: .6rem .85rem;
    color: #fff !important;
    background: var(--public-deep, #123247) !important;
    border: 1px solid var(--public-deep, #123247);
    border-radius: .55rem;
    font-size: .8rem;
    font-weight: 800;
    line-height: 1;
    text-decoration: none;
    transition: color .16s ease, background-color .16s ease, border-color .16s ease, transform .16s ease;
}

.public-header-actions .header-action:hover,
.public-header-actions .header-action:focus-visible {
    transform: translateY(-1px);
}

.public-header-nav-row {
    display: flex;
    align-items: center;
    min-width: 0;
    padding: .65rem 0 .75rem;
}

.public-nav {
    display: flex;
    flex: 1;
    align-items: center;
    gap: .2rem;
    min-width: 0;
    overflow-x: auto;
    scrollbar-width: none;
}

.public-nav::-webkit-scrollbar {
    display: none;
}

.public-nav a {
    flex: 0 0 auto;
    padding: .45rem .7rem;
    border-radius: .55rem;
    color: var(--public-muted-strong, #405762);
    font-size: .82rem;
    font-weight: 600;
    white-space: nowrap;
    text-decoration: none;
    transition: color .16s ease, background-color .16s ease;
}

.public-nav a:hover,
.public-nav a:focus-visible,
.public-nav a[aria-current='page'] {
    color: var(--public-ink, #173044);
}

.public-nav a[aria-current='page'] {
    color: var(--public-ink, #173044);
    background: color-mix(in srgb, var(--public-primary, #3157a4) 10%, transparent);
    font-weight: 800;
}

@media (max-width: 640px) {
    .public-header {
        display: grid;
        width: calc(100% - 2rem);
        padding-top: .8rem;
    }

    .public-header-top {
        align-items: flex-start;
        flex-wrap: wrap;
        gap: .75rem;
    }

    .brand {
        align-items: flex-start;
    }

    .brand-name {
        max-width: 10.5rem;
    }

    .public-header-actions {
        flex-basis: 100%;
        justify-content: flex-start;
        gap: .25rem;
        padding-top: .1rem;
    }

    .header-utility-link,
    .public-header-actions .header-action {
        min-height: 38px;
        padding-inline: .55rem;
        font-size: .75rem;
    }

    .public-header-nav-row { padding-top: .6rem; }

    .public-nav {
        width: 100%;
    }
}
</style>
