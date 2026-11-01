<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface School {
    name: string;
    level?: string | null;
    school_status?: string | null;
    accreditation?: string | null;
    address?: string | null;
    phone?: string | null;
    email?: string | null;
    about?: string | null;
    logo_url?: string | null;
}

interface NewsItem {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    published_at?: string | null;
    photos: { id: number; url: string; name?: string | null }[];
}

interface GalleryItem {
    id: number;
    title?: string | null;
    caption?: string | null;
    url: string;
}

const props = defineProps<{ school: School; news: NewsItem[]; gallery: GalleryItem[] }>();
const galleryPreview = computed(() => props.gallery.slice(0, 4));
const initial = computed(() => props.school.name.trim().charAt(0).toUpperCase() || 'S');
const formatDate = (value?: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(value))
    : '';
</script>

<template>
    <Head :title="school.name" />
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
    <main id="main-content" class="public-site public-home">
        <div class="identity-watermark" aria-hidden="true"><img v-if="school.logo_url" :src="school.logo_url" alt="" /><span v-else>{{ initial }}</span></div>
        <header class="public-header">
            <Link href="/" class="brand" aria-label="Beranda sekolah">
                <span class="brand-mark"><img v-if="school.logo_url" :src="school.logo_url" :alt="'Logo ' + school.name" /><span v-else>{{ initial }}</span></span>
                <span>{{ school.name }}</span>
            </Link>
            <nav aria-label="Navigasi publik">
                <Link href="/" aria-current="page">Beranda</Link>
                <Link href="/profil-sekolah">Profil sekolah</Link>
                <Link href="/struktur-organisasi">Organisasi</Link>
                <Link href="/berita">Berita</Link>
                <Link href="/galeri">Galeri</Link>
                <Link href="/ppdb" class="header-action">PPDB</Link>
                <Link href="/login" class="header-action">Masuk</Link>
            </nav>
        </header>

        <section class="home-hero" aria-labelledby="home-title">
            <div class="hero-copy">
                <p class="eyebrow">{{ school.level || 'Sekolah' }} · Situs resmi</p>
                <h1 id="home-title">Tempat belajar yang memberi ruang untuk <span>tumbuh bersama.</span></h1>
                <p class="hero-lead">{{ school.about || 'Selamat datang di ruang informasi resmi sekolah kami.' }}</p>
                <div class="hero-actions">
                    <Link href="/profil-sekolah" class="primary-button">Kenali sekolah</Link>
                    <Link href="/berita" class="text-link">Baca kabar terbaru</Link>
                </div>
                <div class="trust-line">
                    <span v-if="school.school_status">{{ school.school_status }}</span>
                    <span v-if="school.accreditation">Akreditasi {{ school.accreditation }}</span>
                    <span v-if="school.level">{{ school.level }}</span>
                </div>
            </div>

            <div class="hero-visual" aria-label="Portofolio sekolah">
                <div v-if="galleryPreview.length" class="hero-photo-grid">
                    <img v-for="(photo, index) in galleryPreview" :key="photo.id" :class="'hero-photo hero-photo-' + (index + 1)" :src="photo.url" :alt="photo.title || photo.caption || ('Kegiatan ' + school.name)" />
                </div>
                <div v-else class="hero-placeholder"><span>{{ initial }}</span><small>Portofolio sekolah</small></div>
                <div class="hero-caption"><small>{{ school.school_status || 'Ruang belajar bersama' }}</small><strong>Belajar. Berkarya. Berbagi.</strong></div>
            </div>
        </section>

        <section class="explore-section" aria-labelledby="explore-title">
            <div class="section-heading">
                <div><p class="eyebrow">Jelajahi sekolah</p><h2 id="explore-title">Informasi yang Anda cari, berada di tempatnya.</h2></div>
                <p>Pilih halaman yang ingin Anda kunjungi.</p>
            </div>
            <div class="explore-grid">
                <Link href="/profil-sekolah" class="explore-item"><strong>Profil sekolah</strong><span>Tentang, sambutan, visi, dan misi.</span><b aria-hidden="true">↗</b></Link>
                <Link href="/struktur-organisasi" class="explore-item"><strong>Struktur organisasi</strong><span>Kenali pengelola dan tim sekolah.</span><b aria-hidden="true">↗</b></Link>
                <Link href="/berita" class="explore-item"><strong>Berita & kegiatan</strong><span>Kabar terbaru dari komunitas sekolah.</span><b aria-hidden="true">↗</b></Link>
                <Link href="/galeri" class="explore-item"><strong>Galeri sekolah</strong><span>Momen, karya, dan suasana belajar.</span><b aria-hidden="true">↗</b></Link>
            </div>
        </section>

        <section class="content-section" aria-labelledby="news-title">
            <div class="section-heading">
                <div><p class="eyebrow">Kabar terbaru</p><h2 id="news-title">Cerita yang sedang berlangsung.</h2></div>
                <Link href="/berita" class="text-link">Lihat semua berita</Link>
            </div>
            <div v-if="news.length" class="news-grid">
                <Link v-for="item in news" :key="item.id" :href="'/berita/' + item.slug" class="news-card">
                    <div class="news-image"><img v-if="item.photos[0]" :src="item.photos[0].url" :alt="item.title" /><span v-else>{{ initial }}</span></div>
                    <div class="news-body"><small>{{ formatDate(item.published_at) }}</small><h3>{{ item.title }}</h3><p>{{ item.excerpt || 'Baca informasi lengkap dari sekolah.' }}</p><span class="read-link">Baca berita ↗</span></div>
                </Link>
            </div>
            <p v-else class="empty-state">Berita sekolah akan tampil di halaman ini.</p>
        </section>

        <section v-if="galleryPreview.length" class="content-section gallery-section" aria-labelledby="gallery-title">
            <div class="section-heading">
                <div><p class="eyebrow">Portofolio sekolah</p><h2 id="gallery-title">Momen yang ingin dibagikan.</h2></div>
                <Link href="/galeri" class="text-link">Buka galeri</Link>
            </div>
            <div class="gallery-preview">
                <Link v-for="photo in galleryPreview" :key="photo.id" href="/galeri" class="gallery-preview-item"><img :src="photo.url" :alt="photo.title || photo.caption || 'Foto sekolah'" /><span>{{ photo.title || photo.caption || 'Kegiatan sekolah' }}</span></Link>
            </div>
        </section>

        <footer class="public-footer">
            <div><p class="eyebrow">Hubungi sekolah</p><h2>Mari terhubung.</h2></div>
            <div class="contact-list"><p v-if="school.address">{{ school.address }}</p><a v-if="school.phone" :href="'tel:' + school.phone">{{ school.phone }}</a><a v-if="school.email" :href="'mailto:' + school.email">{{ school.email }}</a></div>
        </footer>
    </main>
</template>

<style scoped>
:global(body) { margin: 0; background: #fffdf8; }
:global(html[data-theme='dark']) .public-site { color: #173044 !important; background: #fffdf8 !important; }
.public-site { --ink: #173044; --muted: #6d7c82; --paper: #fffdf8; --deep: #123247; --gold: #d4a74b; min-height: 100vh; color: var(--ink); background: var(--paper); font-family: var(--font-ui); }
.skip-link { position: fixed; top: -4rem; left: 1rem; z-index: 10; padding: .7rem 1rem; color: var(--paper); background: var(--deep); border-radius: .4rem; font-size: .8rem; font-weight: 700; text-decoration: none; }
.skip-link:focus { top: 1rem; }
.public-header, .home-hero, .explore-section, .content-section, .public-footer { width: min(1120px, calc(100% - 3rem)); margin-inline: auto; }
.public-header { display: flex; align-items: center; justify-content: space-between; gap: 2rem; padding: 1.4rem 0; }
.brand { display: flex; align-items: center; gap: .7rem; color: var(--ink); font-size: .95rem; font-weight: 800; text-decoration: none; }
.brand-mark { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; overflow: hidden; color: var(--deep); background: var(--gold); border-radius: .75rem .75rem .75rem .2rem; font-family: var(--font-display); font-size: 1.2rem; }
.brand-mark img { width: 100%; height: 100%; object-fit: contain; background: #fff; }
.public-header nav { display: flex; align-items: center; gap: 1.1rem; font-size: .78rem; }
.public-header nav a { color: var(--muted); text-decoration: none; }
.public-header nav a:hover, .public-header nav a:focus-visible { color: var(--ink); }
.public-header .header-action { padding: .65rem .9rem; color: var(--paper); background: var(--deep); border-radius: .45rem; font-weight: 700; }
.home-hero { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(300px, .95fr); gap: clamp(2rem, 6vw, 5rem); align-items: center; padding: clamp(4rem, 10vw, 8rem) 0 5rem; }
.eyebrow { margin: 0; color: #9a7329; font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.hero-copy h1 { max-width: 650px; margin: 1rem 0 1.4rem; font-family: var(--font-display); font-size: clamp(2.9rem, 6vw, 6.1rem); font-weight: 400; letter-spacing: -.065em; line-height: .98; }
.hero-copy h1 span { display: block; color: #a2772b; }
.hero-lead { max-width: 560px; margin: 0; color: var(--muted); font-size: 1rem; line-height: 1.8; }
.hero-actions { display: flex; align-items: center; gap: 1.2rem; margin-top: 2rem; }
.primary-button { padding: .85rem 1.05rem; color: var(--paper); background: var(--deep); border-radius: .45rem; font-size: .82rem; font-weight: 700; text-decoration: none; }
.text-link, .read-link { color: #9a7329; font-size: .82rem; font-weight: 700; text-decoration: none; }
.text-link:hover, .text-link:focus-visible, .read-link:hover, .read-link:focus-visible { text-decoration: underline; }
.trust-line { display: flex; flex-wrap: wrap; gap: .5rem 1rem; margin-top: 1.8rem; color: var(--muted); font-size: .73rem; }
.trust-line span + span { padding-left: 1rem; border-left: 1px solid #cfc8ba; }
.hero-visual { position: relative; min-height: 460px; overflow: hidden; background: var(--deep); border-radius: 10rem 1.4rem 1.4rem 1.4rem; }
.hero-photo-grid { position: absolute; inset: 0; }
.hero-photo { position: absolute; display: block; object-fit: cover; filter: saturate(.84); }
.hero-photo-1 { inset: 0 24% 28% 0; width: 76%; height: 72%; border-radius: 0 0 5rem 0; }
.hero-photo-2 { right: 0; bottom: 0; width: 56%; height: 53%; border-top: 9px solid var(--deep); border-left: 9px solid var(--deep); border-radius: 4rem 0 0 0; }
.hero-photo-3 { top: 10%; right: 8%; width: 32%; height: 31%; border: 8px solid var(--deep); border-radius: 50%; }
.hero-photo-4 { right: 40%; bottom: 7%; width: 18%; height: 18%; border: 6px solid var(--deep); border-radius: 50%; }
.hero-placeholder { position: absolute; inset: 0; display: grid; place-content: center; justify-items: center; color: var(--deep); background: var(--gold); font-family: var(--font-display); }
.hero-placeholder span { font-size: 7rem; line-height: 1; }.hero-placeholder small { margin-top: .5rem; font-family: var(--font-ui); font-size: .75rem; }
.hero-caption { position: absolute; right: 10%; bottom: 10%; z-index: 2; display: grid; gap: .35rem; max-width: 220px; color: #f5efe2; }
.hero-caption small { color: var(--gold); font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }.hero-caption strong { font-family: var(--font-display); font-size: 1.8rem; font-weight: 400; line-height: 1.05; }
.explore-section { padding: 1rem 0 6rem; }.section-heading { display: flex; align-items: end; justify-content: space-between; gap: 2rem; margin-bottom: 2rem; }.section-heading h2, .public-footer h2 { max-width: 620px; margin: .7rem 0 0; font-family: var(--font-display); font-size: clamp(2rem, 4vw, 3.7rem); font-weight: 400; letter-spacing: -.05em; line-height: 1.03; }.section-heading > p { max-width: 220px; margin: 0; color: var(--muted); font-size: .82rem; }
.explore-grid { display: grid; grid-template-columns: repeat(4, 1fr); border-top: 1px solid #cfc8ba; border-bottom: 1px solid #cfc8ba; }.explore-item { display: grid; gap: .55rem; min-height: 145px; padding: 1.1rem 1rem; color: var(--ink); border-right: 1px solid #cfc8ba; text-decoration: none; }.explore-item:first-child { padding-left: 0; }.explore-item:last-child { border-right: 0; }.explore-item strong { font-family: var(--font-display); font-size: 1.25rem; font-weight: 400; }.explore-item span { color: var(--muted); font-size: .78rem; line-height: 1.5; }.explore-item b { align-self: end; color: #9a7329; font-size: 1.1rem; font-weight: 400; }.explore-item:hover strong, .explore-item:focus-visible strong { color: #9a7329; }
.content-section { padding: 5rem 0 1rem; }.news-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }.news-card { overflow: hidden; color: var(--ink); background: #fff; border: 1px solid #ded7c9; border-radius: .8rem; text-decoration: none; }.news-image { display: grid; place-items: center; height: 190px; color: var(--gold); background: var(--deep); font-family: var(--font-display); font-size: 2.4rem; }.news-image img { width: 100%; height: 100%; object-fit: cover; }.news-body { display: grid; gap: .45rem; padding: 1rem; }.news-body small { color: var(--muted); font-size: .7rem; }.news-body h3 { margin: .2rem 0; font-family: var(--font-display); font-size: 1.3rem; font-weight: 400; line-height: 1.2; }.news-body p { min-height: 2.6rem; margin: 0; color: var(--muted); font-size: .8rem; line-height: 1.55; }.read-link { margin-top: .5rem; font-size: .75rem; }.empty-state { color: var(--muted); }
.gallery-section { padding-top: 5rem; }.gallery-preview { display: grid; grid-template-columns: 1.4fr 1fr 1fr 1fr; gap: .7rem; }.gallery-preview-item { position: relative; min-height: 190px; overflow: hidden; color: #fff; background: var(--deep); text-decoration: none; }.gallery-preview-item:first-child { grid-row: span 2; min-height: 390px; }.gallery-preview-item img { width: 100%; height: 100%; object-fit: cover; transition: transform .25s ease; }.gallery-preview-item span { position: absolute; right: .7rem; bottom: .6rem; left: .7rem; z-index: 1; font-size: .75rem; text-shadow: 0 1px 8px #000; }.gallery-preview-item:hover img, .gallery-preview-item:focus-visible img { transform: scale(1.04); }
.public-footer { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-top: 6rem; padding: 4rem 0 5rem; border-top: 1px solid #cfc8ba; }.contact-list { display: grid; align-content: start; gap: .45rem; color: var(--muted); font-size: .9rem; line-height: 1.6; }.contact-list p { margin: 0; }.contact-list a { color: inherit; text-decoration: none; }.contact-list a:hover { color: var(--ink); text-decoration: underline; }
.public-site a:focus-visible { outline: 3px solid #c6953e; outline-offset: 4px; border-radius: .2rem; }
@media (max-width: 900px) { .public-header { align-items: flex-start; }.public-header nav { flex-wrap: wrap; justify-content: flex-end; }.home-hero { grid-template-columns: 1fr; padding-top: 4rem; }.hero-visual { min-height: 390px; }.explore-grid { grid-template-columns: repeat(2, 1fr); }.explore-item:nth-child(2) { border-right: 0; }.explore-item:nth-child(-n+2) { border-bottom: 1px solid #cfc8ba; }.explore-item:first-child { padding-left: 1rem; }.news-grid { grid-template-columns: repeat(2, 1fr); }.news-card:first-child { grid-column: span 2; }.gallery-preview { grid-template-columns: repeat(3, 1fr); }.gallery-preview-item:nth-child(4) { display: none; } }
@media (max-width: 600px) { .public-header, .home-hero, .explore-section, .content-section, .public-footer { width: calc(100% - 2rem); }.public-header { display: grid; gap: 1rem; }.public-header nav { justify-content: flex-start; gap: .75rem; }.public-header nav a:nth-child(-n+2) { display: none; }.home-hero { padding-top: 3rem; padding-bottom: 3rem; }.hero-copy h1 { font-size: clamp(2.8rem, 13vw, 4rem); }.hero-actions { align-items: flex-start; flex-direction: column; gap: .9rem; }.hero-visual { min-height: 320px; border-radius: 6rem 1rem 1rem 1rem; }.hero-photo-1 { inset: 0 16% 28% 0; width: 84%; }.hero-photo-2 { width: 63%; height: 50%; }.hero-photo-3 { right: 7%; width: 34%; height: 30%; }.hero-photo-4 { display: none; }.section-heading { align-items: flex-start; flex-direction: column; gap: 1rem; }.explore-section { padding-bottom: 3rem; }.explore-grid, .news-grid, .public-footer { grid-template-columns: 1fr; }.explore-item, .explore-item:first-child { min-height: 125px; padding: 1rem 0; border-right: 0; border-bottom: 1px solid #cfc8ba; }.explore-item:last-child { border-bottom: 0; }.news-card:first-child { grid-column: auto; }.gallery-preview { grid-template-columns: 1fr 1fr; }.gallery-preview-item, .gallery-preview-item:first-child { min-height: 150px; grid-row: auto; }.gallery-preview-item:first-child { grid-column: span 2; min-height: 250px; }.gallery-preview-item:nth-child(4) { display: block; }.public-footer { gap: 1.5rem; margin-top: 3rem; padding-bottom: 3rem; } }
@media (prefers-reduced-motion: reduce) { .gallery-preview-item img { transition: none; } }
</style>
