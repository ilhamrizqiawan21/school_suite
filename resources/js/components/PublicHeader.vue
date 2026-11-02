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
    { label: 'Kontak', href: '/kontak', key: 'contact' },
];
</script>

<template>
    <header class="public-header">
        <Link href="/" class="brand" aria-label="Beranda sekolah">
            <span class="brand-mark">
                <img v-if="school.logo_url" :src="school.logo_url" :alt="`Logo ${school.name}`" />
                <span v-else>{{ initial }}</span>
            </span>
            <span>{{ school.name }}</span>
        </Link>

        <nav class="public-nav" aria-label="Navigasi publik">
            <Link
                v-for="menu in menus"
                :key="menu.key"
                :href="menu.href"
                :aria-current="props.active === menu.key ? 'page' : undefined"
            >
                {{ menu.label }}
            </Link>
            <Link href="/ppdb" class="header-action" :aria-current="props.active === 'admissions' ? 'page' : undefined">PPDB</Link>
            <Link href="/login" class="header-action">Masuk</Link>
        </nav>
    </header>
</template>

<style scoped>
.public-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    width: min(1180px, calc(100% - 3rem));
    margin-inline: auto;
    padding: 1.4rem 0;
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

.brand-mark {
    display: grid;
    flex: 0 0 auto;
    place-items: center;
    width: 2.4rem;
    height: 2.4rem;
    overflow: hidden;
    color: var(--public-deep, #123247);
    background: var(--public-gold, #d4a74b);
    border-radius: .75rem .75rem .75rem .2rem;
    font-family: var(--font-display);
    font-size: 1.2rem;
}

.brand-mark img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #fff;
}

.public-nav {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: .7rem 1rem;
    font-size: .85rem;
}

.public-nav a {
    color: var(--public-muted-strong, #405762);
    font-weight: 600;
    text-decoration: none;
}

.public-nav a:hover,
.public-nav a:focus-visible,
.public-nav a[aria-current='page'] {
    color: var(--public-ink, #173044);
}

.public-nav a[aria-current='page'] {
    font-weight: 800;
}

.public-nav .header-action {
    display: inline-flex;
    min-height: 42px;
    align-items: center;
    justify-content: center;
    padding: .65rem .9rem;
    color: #fff;
    background: var(--public-deep, #123247);
    border: 1px solid var(--public-deep, #123247);
    border-radius: .5rem;
    font-weight: 800;
}

.public-nav .header-action:hover,
.public-nav .header-action:focus-visible,
.public-nav .header-action[aria-current='page'] {
    color: #fff;
    background: var(--public-deep-hover, #1b4d6b);
    border-color: var(--public-deep-hover, #1b4d6b);
}

@media (max-width: 900px) {
    .public-header {
        align-items: flex-start;
    }

    .public-nav {
        justify-content: flex-end;
    }
}

@media (max-width: 640px) {
    .public-header {
        display: grid;
        width: calc(100% - 2rem);
        gap: 1rem;
    }

    .public-nav {
        justify-content: flex-start;
        gap: .7rem .85rem;
    }
}
</style>
