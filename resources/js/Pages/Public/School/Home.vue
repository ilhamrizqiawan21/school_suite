<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

interface School {
    name: string;
    theme?: {
        primary?: string | null;
        accent?: string | null;
        surface?: string | null;
        ink?: string | null;
        muted?: string | null;
        hero_variant?: 'collage' | 'single' | 'minimal';
        sections?: { explore?: boolean; news?: boolean; gallery?: boolean };
        hero?: { title?: string | null; accent?: string | null; primary_label?: string | null; secondary_label?: string | null };
    };
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
const lifePhotos = computed(() => galleryPreview.value.length > 1 ? galleryPreview.value.slice(1) : galleryPreview.value);
const initial = computed(() => props.school.name.trim().charAt(0).toUpperCase() || 'S');
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const heroTitle = computed(() => props.school.theme?.hero?.title || 'Tempat belajar yang memberi ruang untuk');
const heroAccent = computed(() => props.school.theme?.hero?.accent || 'tumbuh bersama.');
const heroPrimaryLabel = computed(() => props.school.theme?.hero?.primary_label || 'Daftar PPDB');
const heroSecondaryLabel = computed(() => props.school.theme?.hero?.secondary_label || 'Kenali sekolah');
const heroVariant = computed(() => props.school.theme?.hero_variant || 'collage');
const formatDate = (value?: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(value))
    : '';
</script>

<template>
    <Head :title="school.name" />
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
    <main id="main-content" class="public-site public-home" :style="themeStyle">
        <div class="identity-watermark" aria-hidden="true"><img v-if="school.logo_url" :src="school.logo_url" alt="" /><span v-else>{{ initial }}</span></div>
        <PublicHeader :school="school" active="home" />

        <section class="home-hero" :class="'hero-variant-' + heroVariant" aria-labelledby="home-title">
            <div class="hero-copy">
                <p class="eyebrow">{{ school.level || 'Sekolah' }} · Situs resmi</p>
                <h1 id="home-title">{{ heroTitle }} <span>{{ heroAccent }}</span></h1>
                <p class="hero-lead">{{ school.about || 'Selamat datang di ruang informasi resmi sekolah kami.' }}</p>
                <div class="hero-actions">
                    <Link href="/ppdb" class="primary-button ui-button ui-button--primary ui-button--md">{{ heroPrimaryLabel }}</Link>
                    <Link href="/profil-sekolah" class="secondary-button ui-button ui-button--secondary ui-button--md">{{ heroSecondaryLabel }}</Link>
                    <Link href="/login" class="text-link">Portal Siswa &amp; Guru ↗</Link>
                </div>
                <div class="trust-line">
                    <span v-if="school.school_status">{{ school.school_status }}</span>
                    <span v-if="school.accreditation">Akreditasi {{ school.accreditation }}</span>
                    <span v-if="school.level">{{ school.level }}</span>
                </div>
            </div>

            <div class="hero-visual" aria-label="Portofolio sekolah">
                <div v-if="galleryPreview.length" class="hero-photo-grid">
                    <img class="hero-photo hero-photo-1" :src="galleryPreview[0].url" :alt="galleryPreview[0].title || galleryPreview[0].caption || ('Kegiatan ' + school.name)" />
                    <img v-if="galleryPreview[1]" class="hero-photo hero-photo-2" :src="galleryPreview[1].url" :alt="galleryPreview[1].title || galleryPreview[1].caption || ('Suasana ' + school.name)" />
                </div>
                <div v-else class="hero-placeholder"><span>{{ initial }}</span><small>Portofolio sekolah</small></div>
                <div class="hero-caption"><small>{{ school.school_status || 'Ruang belajar bersama' }}</small><strong>Belajar. Berkarya. Berbagi.</strong></div>
            </div>
        </section>

        <section class="audience-pathways" aria-label="Jalur cepat pengunjung">
            <Link href="/ppdb" class="pathway-card">
                <span class="pathway-tag">Calon Siswa &amp; Orang Tua</span>
                <strong>Informasi &amp; Pendaftaran SPMB</strong>
                <small>Lihat jalur masuk, syarat, dan jadwal pendaftaran.</small>
            </Link>
            <Link href="/program" class="pathway-card">
                <span class="pathway-tag">Akademik &amp; Minat</span>
                <strong>Program Unggulan</strong>
                <small>Kurikulum, ekstrakurikuler, dan pembinaan prestasi.</small>
            </Link>
            <Link href="/kehidupan-siswa" class="pathway-card">
                <span class="pathway-tag">Lingkungan Belajar</span>
                <strong>Kehidupan Siswa</strong>
                <small>Kegiatan harian, karya, dan budaya sekolah.</small>
            </Link>
            <Link href="/login" class="pathway-card pathway-portal">
                <span class="pathway-tag">Warga Sekolah</span>
                <strong>Masuk Portal Akademik</strong>
                <small>Akses materi, tugas, jadwal, rapor, dan layanan guru/siswa.</small>
            </Link>
        </section>

        <section class="home-pillars" aria-labelledby="pillars-title">
            <div class="section-heading">
                <h2 id="pillars-title">Pendidikan yang utuh, untuk setiap langkah.</h2>
                <p>Akademik yang kuat bertemu karakter, kreativitas, dan kepedulian terhadap sesama.</p>
            </div>
            <div class="home-pillar-grid">
                <article><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M12 5.5C9.5 3.7 6.8 3.3 3 4v14c3.8-.7 6.5-.3 9 1.5 2.5-1.8 5.2-2.2 9-1.5V4c-3.8-.7-6.5-.3-9 1.5ZM12 5.5v14" /></svg><h3>Belajar dengan rasa ingin tahu</h3><p>Pembelajaran aktif yang menghubungkan pengetahuan dengan kehidupan sehari-hari.</p></article>
                <article><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="9" cy="8" r="3" /><path d="M3.5 20v-2a5.5 5.5 0 0 1 11 0v2M16 5.3a3 3 0 0 1 0 5.4M17 14a5.5 5.5 0 0 1 3.5 5.1V20" /></svg><h3>Tumbuh dalam komunitas</h3><p>Pendampingan yang menghargai keunikan dan perkembangan setiap siswa.</p></article>
                <article><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="m4 12 5 5L20 5" /></svg><h3>Berkarya dengan percaya diri</h3><p>Ruang untuk olahraga, seni, sains, dan ide yang memberi dampak.</p></article>
            </div>
        </section>

        <section v-if="school.theme?.sections?.gallery !== false && galleryPreview.length" class="home-life" aria-labelledby="life-title">
            <div class="section-heading"><div><h2 id="life-title">Hari-hari yang penuh penemuan.</h2><p>Kehidupan di sekolah</p></div><Link href="/galeri" class="text-link">Lihat galeri</Link></div>
            <div class="home-life-grid">
                <Link v-for="photo in lifePhotos.slice(0, 3)" :key="photo.id" href="/galeri"><img :src="photo.url" :alt="photo.title || photo.caption || 'Kegiatan sekolah'" /><span>{{ photo.title || photo.caption || 'Kegiatan sekolah' }}</span></Link>
            </div>
        </section>

        <section v-if="school.theme?.sections?.explore !== false" class="explore-section" aria-labelledby="explore-title">
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

        <section v-if="school.theme?.sections?.news !== false" class="content-section" aria-labelledby="news-title">
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

        <section class="home-admissions" aria-labelledby="admissions-title"><div><h2 id="admissions-title">Langkah awal masa depan dimulai di sini.</h2><p>Pelajari jalur, persyaratan, dan jadwal pendaftaran peserta didik baru.</p></div><Link href="/ppdb" aria-label="Lihat informasi SPMB">Lihat SPMB <span aria-hidden="true">→</span></Link></section>

        <footer class="public-footer">
            <div class="footer-brand">
                <p class="eyebrow">Hubungi sekolah</p>
                <h2>{{ school.name }}</h2>
                <p class="footer-desc">{{ school.about || 'Ruang belajar yang aman, kolaboratif, dan berorientasi pada masa depan siswa.' }}</p>
            </div>
            <div class="footer-links">
                <p class="footer-heading">Tautan cepat</p>
                <nav aria-label="Navigasi footer">
                    <Link href="/profil-sekolah">Profil Sekolah</Link>
                    <Link href="/program">Program</Link>
                    <Link href="/ppdb">SPMB</Link>
                    <Link href="/berita">Berita</Link>
                    <Link href="/galeri">Galeri</Link>
                    <Link href="/login">Portal Login</Link>
                </nav>
            </div>
            <div class="contact-list">
                <p class="footer-heading">Kontak resmi</p>
                <p v-if="school.address">{{ school.address }}</p>
                <a v-if="school.phone" :href="'tel:' + school.phone">{{ school.phone }}</a>
                <a v-if="school.email" :href="'mailto:' + school.email">{{ school.email }}</a>
            </div>
        </footer>
    </main>
</template>

<style scoped>
:global(body) { margin: 0; background: #f2f6f9; }
:global(html[data-theme='dark']) .public-site { color: #173044 !important; background: #f2f6f9 !important; }
.public-site { --ink: var(--public-ink, #173044); --muted: var(--public-muted, #526673); --paper: var(--public-surface, #f5f8fb); --deep: var(--public-primary, #3157a4); --gold: var(--public-accent, #8a661f); min-height: 100vh; color: var(--ink); background: var(--paper); font-family: var(--font-ui); }
.skip-link { position: fixed; top: -4rem; left: 1rem; z-index: 10; padding: .7rem 1rem; color: var(--paper); background: var(--deep); border-radius: .4rem; font-size: var(--type-meta); font-weight: var(--weight-bold); text-decoration: none; }
.skip-link:focus { top: 1rem; }
.home-hero, .explore-section, .content-section, .public-footer { width: min(1120px, calc(100% - 3rem)); margin-inline: auto; }
.home-hero { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(300px, .95fr); gap: clamp(2rem, 6vw, 5rem); align-items: center; padding: clamp(3.25rem, 6vw, 5.25rem) 0 5rem; }
.eyebrow { margin: 0; color: #9a7329; font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .08em; text-transform: uppercase; }
.hero-copy h1 { max-width: 650px; margin: 1rem 0 1.4rem; font-family: var(--font-ui); font-size: clamp(2.9rem, 6vw, 6.1rem); font-weight: var(--weight-regular); letter-spacing: -.065em; line-height: .98; }
.hero-copy h1 span { display: block; color: var(--gold); }
.hero-lead { max-width: 560px; margin: 0; color: var(--muted); font-size: var(--type-md); line-height: 1.8; }
.hero-actions { display: flex; align-items: center; gap: 1.2rem; margin-top: 2rem; }
.text-link, .read-link { color: var(--gold); font-size: var(--type-sm); font-weight: var(--weight-bold); text-decoration: none; }
.text-link:hover, .text-link:focus-visible, .read-link:hover, .read-link:focus-visible { text-decoration: underline; }
.trust-line { display: flex; flex-wrap: wrap; gap: .5rem 1rem; margin-top: 1.8rem; color: var(--muted); font-size: var(--type-meta); }
.trust-line span + span { padding-left: 1rem; border-left: 1px solid var(--public-line-strong); }
.hero-visual { position: relative; min-height: 460px; overflow: hidden; background: var(--deep); border-radius: 8rem 1.4rem 1.4rem 1.4rem; }
.hero-photo-grid { position: absolute; inset: 0; }
.hero-photo { position: absolute; display: block; object-fit: cover; filter: saturate(.84); }
.hero-photo-1 { inset: 0 24% 28% 0; width: 76%; height: 72%; border-radius: 0 0 5rem 0; }
.hero-photo-2 { right: 0; bottom: 0; width: 56%; height: 53%; border-top: 10px solid var(--deep); border-left: 10px solid var(--deep); border-radius: 3.5rem 0 0 0; }
.hero-photo-3, .hero-photo-4 { display: none; }
.hero-variant-single .hero-photo-1, .hero-variant-minimal .hero-photo-1 { inset: 0; width: 100%; height: 100%; border: 0; border-radius: inherit; }
.hero-variant-single .hero-photo-2, .hero-variant-minimal .hero-photo-2 { display: none; }
.hero-variant-minimal .hero-photo-1 { opacity: .68; filter: saturate(.55); }
.hero-variant-single .hero-caption, .hero-variant-minimal .hero-caption { left: auto; right: 7%; bottom: 8%; width: min(58%, 230px); max-width: none; padding: 1rem; background: color-mix(in srgb, var(--deep) 90%, transparent); border-radius: .8rem; }
.hero-placeholder { position: absolute; inset: 0; display: grid; place-content: center; justify-items: center; color: var(--deep); background: var(--gold); font-family: var(--font-ui); }
.hero-placeholder span { font-size: 7rem; line-height: 1; }.hero-placeholder small { margin-top: .5rem; font-family: var(--font-ui); font-size: var(--type-meta); }
.hero-caption { position: absolute; left: 7%; bottom: 8%; z-index: 2; display: grid; gap: .4rem; width: 34%; max-width: 145px; color: #f5efe2; }
.hero-caption small { color: var(--gold); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .08em; text-transform: uppercase; }.hero-caption strong { font-family: var(--font-ui); font-size: 1.65rem; font-weight: var(--weight-regular); line-height: 1.04; }
.explore-section { padding: 1rem 0 6rem; }.section-heading { display: flex; align-items: end; justify-content: space-between; gap: 2rem; margin-bottom: 2rem; }.section-heading h2, .public-footer h2 { max-width: 620px; margin: .7rem 0 0; font-family: var(--font-ui); font-size: clamp(2rem, 4vw, 3.7rem); font-weight: var(--weight-regular); letter-spacing: -.05em; line-height: 1.03; }.section-heading > p { max-width: 220px; margin: 0; color: var(--muted); font-size: var(--type-sm); }
.explore-grid { display: grid; grid-template-columns: repeat(4, 1fr); border-top: 1px solid var(--public-line-strong); border-bottom: 1px solid var(--public-line-strong); }.explore-item { display: grid; gap: .55rem; min-height: 145px; padding: 1.1rem 1rem; color: var(--ink); border-right: 1px solid var(--public-line-strong); text-decoration: none; }.explore-item:first-child { padding-left: 0; }.explore-item:last-child { border-right: 0; }.explore-item strong { font-family: var(--font-ui); font-size: var(--type-xl); font-weight: var(--weight-regular); }.explore-item span { color: var(--muted); font-size: var(--type-meta); line-height: 1.5; }.explore-item b { align-self: end; color: var(--gold); font-size: 1.1rem; font-weight: var(--weight-regular); }.explore-item:hover strong, .explore-item:focus-visible strong { color: var(--gold); }
.content-section { padding: 5rem 0 1rem; }.news-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }.news-card { overflow: hidden; color: var(--ink); background: var(--public-surface); border: 1px solid var(--public-line-strong); border-radius: .8rem; text-decoration: none; }.news-image { display: grid; place-items: center; height: 190px; color: var(--gold); background: var(--deep); font-family: var(--font-ui); font-size: 2.4rem; }.news-image img { width: 100%; height: 100%; object-fit: cover; }.news-body { display: grid; gap: .45rem; padding: 1rem; }.news-body small { color: var(--muted); font-size: var(--type-meta); }.news-body h3 { margin: .2rem 0; font-family: var(--font-ui); font-size: 1.3rem; font-weight: var(--weight-regular); line-height: 1.2; }.news-body p { min-height: 2.6rem; margin: 0; color: var(--muted); font-size: var(--type-meta); line-height: 1.55; }.read-link { margin-top: .5rem; font-size: var(--type-meta); }.empty-state { color: var(--muted); }
.gallery-section { padding-top: 5rem; }.gallery-preview { display: grid; grid-template-columns: 1.4fr 1fr 1fr 1fr; gap: .7rem; }.gallery-preview-item { position: relative; min-height: 190px; overflow: hidden; color: #fff; background: var(--deep); text-decoration: none; }.gallery-preview-item:first-child { grid-row: span 2; min-height: 390px; }.gallery-preview-item img { width: 100%; height: 100%; object-fit: cover; transition: transform .25s ease; }.gallery-preview-item span { position: absolute; right: .7rem; bottom: .6rem; left: .7rem; z-index: 1; font-size: var(--type-meta); text-shadow: 0 1px 8px #000; }.gallery-preview-item:hover img, .gallery-preview-item:focus-visible img { transform: scale(1.04); }
.public-footer { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-top: 6rem; padding: 4rem 0 5rem; border-top: 1px solid var(--public-line-strong); }.contact-list { display: grid; align-content: start; gap: .45rem; color: var(--muted); font-size: var(--type-md); line-height: 1.6; }.contact-list p { margin: 0; }.contact-list a { color: inherit; text-decoration: none; }.contact-list a:hover { color: var(--ink); text-decoration: underline; }
@media (max-width: 900px) { .home-hero { grid-template-columns: 1fr; padding-top: 4rem; }.hero-visual { min-height: 390px; }.explore-grid { grid-template-columns: repeat(2, 1fr); }.explore-item:nth-child(2) { border-right: 0; }.explore-item:nth-child(-n+2) { border-bottom: 1px solid var(--public-line-strong); }.explore-item:first-child { padding-left: 1rem; }.news-grid { grid-template-columns: repeat(2, 1fr); }.news-card:first-child { grid-column: span 2; }.gallery-preview { grid-template-columns: repeat(3, 1fr); }.gallery-preview-item:nth-child(4) { display: none; } }
@media (max-width: 600px) { .home-hero, .explore-section, .content-section, .public-footer { width: calc(100% - 2rem); }.home-hero { padding-top: 3rem; padding-bottom: 3rem; }.hero-copy h1 { font-size: clamp(2.8rem, 13vw, 4rem); }.hero-actions { align-items: flex-start; flex-direction: column; gap: .9rem; }.hero-visual { min-height: 320px; border-radius: 6rem 1rem 1rem 1rem; }.hero-photo-1 { inset: 0 16% 28% 0; width: 84%; }.hero-photo-2 { width: 60%; height: 50%; }.hero-caption { left: 7%; bottom: 8%; width: 32%; max-width: 120px; }.hero-caption small { font-size: var(--type-meta); }.hero-caption strong { font-size: 1.3rem; }.section-heading { align-items: flex-start; flex-direction: column; gap: 1rem; }.explore-section { padding-bottom: 3rem; }.explore-grid, .news-grid, .public-footer { grid-template-columns: 1fr; }.explore-item, .explore-item:first-child { min-height: 125px; padding: 1rem 0; border-right: 0; border-bottom: 1px solid var(--public-line-strong); }.explore-item:last-child { border-bottom: 0; }.news-card:first-child { grid-column: auto; }.gallery-preview { grid-template-columns: 1fr 1fr; }.gallery-preview-item, .gallery-preview-item:first-child { min-height: 150px; grid-row: auto; }.gallery-preview-item:first-child { grid-column: span 2; min-height: 250px; }.gallery-preview-item:nth-child(4) { display: block; }.public-footer { gap: 1.5rem; margin-top: 3rem; padding-bottom: 3rem; } }
@media (prefers-reduced-motion: reduce) { .gallery-preview-item img { transition: none; } }

/* Editorial school portal direction: clear action, strong school identity, calm data surfaces. */
.public-home .home-hero {
    position: relative;
    width: min(1180px, calc(100% - 3rem));
    margin-top: 2rem;
    padding: clamp(2rem, 4vw, 4rem);
    color: #f7f5ef;
    background: var(--deep);
    border-radius: var(--public-radius-lg);
    box-shadow: var(--public-shadow-deep);
    overflow: hidden;
    isolation: isolate;
}

.public-home .home-hero::before {
    position: absolute;
    inset: auto -5rem -8rem auto;
    width: 24rem;
    height: 24rem;
    border: 1px solid color-mix(in srgb, var(--gold) 52%, transparent);
    border-radius: 50%;
    content: '';
    opacity: .65;
}

.public-home .home-hero::after {
    position: absolute;
    inset: 0 48% 0 0;
    background: color-mix(in srgb, var(--deep) 86%, #fff 14%);
    clip-path: polygon(0 0, 78% 0, 100% 100%, 0 100%);
    content: '';
    opacity: .32;
    pointer-events: none;
}

.public-home .hero-copy,
.public-home .hero-visual {
    position: relative;
    z-index: 1;
}

.public-home .hero-copy h1 {
    max-width: 680px;
    margin-top: .85rem;
    color: #f7f5ef;
    font-size: clamp(3.25rem, 6vw, 6.25rem);
}

.public-home .hero-copy h1 span {
    color: var(--gold);
}

.public-home .hero-lead {
    max-width: 510px;
    color: color-mix(in srgb, #f7f5ef 75%, transparent);
}

.public-home .hero-actions {
    gap: 1rem;
    margin-top: 2.1rem;
}

.public-home .text-link {
    color: #f7f5ef;
}

.public-home .trust-line {
    color: color-mix(in srgb, #f7f5ef 62%, transparent);
}

.public-home .trust-line span + span {
    border-color: color-mix(in srgb, #f7f5ef 28%, transparent);
}

.public-home .hero-visual {
    min-height: clamp(360px, 42vw, 530px);
    background: var(--gold);
    border-radius: var(--public-radius-md);
    box-shadow: 0 16px 0 color-mix(in srgb, var(--gold) 62%, #000 38%);
}

.public-home .hero-photo-1 {
    border-radius: 0 0 4rem 0;
}

.public-home .hero-photo-2 {
    border-color: var(--gold);
}

.public-home .hero-placeholder {
    overflow: hidden;
    color: var(--deep);
    background: var(--gold);
}

.public-home .hero-placeholder::before,
.public-home .hero-placeholder::after {
    position: absolute;
    width: 18rem;
    height: 18rem;
    border: 1px solid color-mix(in srgb, var(--deep) 40%, transparent);
    border-radius: 50%;
    content: '';
}

.public-home .hero-placeholder::before {
    top: -7rem;
    right: -5rem;
}

.public-home .hero-placeholder::after {
    bottom: -11rem;
    left: -6rem;
}

.public-home .hero-placeholder span,
.public-home .hero-placeholder small {
    position: relative;
    z-index: 1;
}

.public-home .hero-placeholder span {
    font-size: clamp(7rem, 13vw, 11rem);
    letter-spacing: -.08em;
}

.public-home .hero-placeholder small {
    color: color-mix(in srgb, var(--deep) 82%, #fff 18%);
    font-weight: var(--weight-bold);
    letter-spacing: .08em;
    text-transform: uppercase;
}

.public-home .hero-caption {
    left: 7%;
    bottom: 7%;
    width: auto;
    max-width: 220px;
    padding: .85rem 1rem;
    background: var(--deep);
    border-radius: var(--public-radius-sm);
}

.public-home .hero-caption strong {
    font-size: 1.45rem;
}

.public-home .explore-section {
    padding-top: clamp(4rem, 8vw, 7rem);
}

.public-home .section-heading h2,
.public-home .public-footer h2 {
    color: var(--ink);
}

.public-home .explore-grid {
    gap: .85rem;
    border: 0;
}

.public-home .explore-item,
.public-home .news-card {
    border: 1px solid var(--public-line-strong);
    border-radius: var(--public-radius-md);
    box-shadow: var(--public-shadow-soft);
}

.public-home .explore-item,
.public-home .explore-item:first-child {
    min-height: 180px;
    padding: 1.25rem;
    background: var(--public-surface);
    border-right: 1px solid var(--public-line-strong);
    transition: transform .2s var(--public-transition), border-color .2s var(--public-transition), box-shadow .2s var(--public-transition);
}

.public-home .explore-item:hover,
.public-home .explore-item:focus-visible {
    border-color: var(--deep);
    box-shadow: var(--public-shadow-deep);
    transform: translateY(-4px);
}

.public-home .explore-item strong {
    color: var(--ink);
    font-size: 1.45rem;
}

.public-home .content-section {
    padding-top: clamp(4rem, 8vw, 7rem);
}

.public-home .news-grid {
    gap: 1.2rem;
}

.public-home .news-card {
    background: var(--public-surface);
    transition: transform .2s var(--public-transition), box-shadow .2s var(--public-transition);
}

.public-home .news-card:hover,
.public-home .news-card:focus-visible {
    box-shadow: var(--public-shadow-deep);
    transform: translateY(-4px);
}

.public-home .news-image {
    height: 210px;
}

.public-home .gallery-preview-item {
    border-radius: var(--public-radius-md);
}

.public-home .public-footer {
    width: min(1180px, calc(100% - 3rem));
    margin-top: clamp(4rem, 8vw, 7rem);
    padding: 3rem clamp(1.4rem, 4vw, 3rem);
    color: #f7f5ef;
    background: var(--deep);
    border: 0;
    border-radius: var(--public-radius-lg) var(--public-radius-lg) 0 0;
}

.public-home .public-footer h2,
.public-home .public-footer .eyebrow {
    color: #f7f5ef;
}

.public-home .public-footer .contact-list {
    color: color-mix(in srgb, #f7f5ef 72%, transparent);
}

@media (max-width: 900px) {
    .public-home .home-hero {
        grid-template-columns: 1fr;
    }

    .public-home .hero-visual {
        min-height: 390px;
    }
}

@media (max-width: 600px) {
    .public-home .home-hero,
    .public-home .public-footer {
        width: calc(100% - 2rem);
    }

    .public-home .home-hero {
        margin-top: 1rem;
        padding: 1.25rem;
        border-radius: var(--public-radius-md);
    }

    .public-home .hero-copy h1 {
        font-size: clamp(2.8rem, 13vw, 4.2rem);
    }

    .public-home .hero-visual {
        min-height: 320px;
        box-shadow: 0 10px 0 color-mix(in srgb, var(--gold) 62%, #000 38%);
    }

    .public-home .explore-item,
    .public-home .explore-item:first-child {
        border-right: 0;
    }

    .public-home .public-footer {
        padding: 2rem 1.25rem 2.5rem;
        border-radius: var(--public-radius-md) var(--public-radius-md) 0 0;
    }
}

/* Calm Academic mockup adaptation. */
.public-home { background: #f4f7fb; }
.public-home .home-hero,
.public-home .home-pillars,
.public-home .home-life,
.public-home .home-admissions { width: min(1180px, calc(100% - 3rem)); margin-inline: auto; }
.public-home .home-hero { gap: clamp(2rem, 5vw, 5rem); margin-top: 0; padding: clamp(3rem, 5vw, 5rem) 0; color: var(--ink); background: transparent; border-radius: 0; box-shadow: none; overflow: visible; }
.public-home .home-hero::before,
.public-home .home-hero::after { display: none; }
.public-home .hero-copy h1 { margin: 0 0 1.5rem; color: var(--ink); font-family: var(--font-ui); font-size: clamp(2.7rem, 4vw, 4.2rem); font-weight: var(--weight-bold); letter-spacing: -.045em; line-height: 1.15; }
.public-home .hero-copy h1 span { display: inline; color: var(--ink); }
:global(body .public-site.public-home .hero-copy h1 span) { color: var(--ink) !important; }
.public-home .hero-copy .eyebrow { margin-bottom: 1rem; color: var(--deep); }
:global(body .public-site.public-home .home-hero .eyebrow) { color: var(--deep) !important; }
.public-home .hero-lead { color: var(--muted); line-height: 1.65; }
.public-home .hero-lead { display: -webkit-box; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 4; }
.public-home .hero-actions { flex-wrap: wrap; gap: .75rem 1.5rem; }
.public-home .hero-actions .text-link { color: var(--deep); }
.public-home .trust-line { gap: 0; padding: 1rem 0; color: var(--muted); background: #fff; border: 1px solid #dde5ef; border-radius: 12px; }
.public-home .trust-line span { padding: 0 1rem; }
.public-home .trust-line span + span { padding-left: 1rem; border-color: #dde5ef; }
.public-home .hero-visual { min-height: clamp(320px, 33vw, 455px); background: #e9eff7; border-radius: 18px; box-shadow: none; }
.public-home .hero-photo-1 { inset: 0; width: 100%; height: 100%; border-radius: inherit; }
.public-home .hero-photo-2,
.public-home .hero-caption { display: none; }
.public-home .home-pillars { padding: 1rem 0 4.5rem; }
.public-home .home-pillars .section-heading { align-items: start; }
.public-home .home-pillars .section-heading h2,
.public-home .home-life .section-heading h2,
.public-home .home-admissions h2 { max-width: 540px; margin: 0; color: var(--ink); font-family: var(--font-ui); font-size: clamp(1.8rem, 2.7vw, 2.7rem); font-weight: var(--weight-bold); letter-spacing: -.035em; line-height: 1.2; }
.public-home .home-pillars .section-heading p { max-width: 360px; color: var(--muted); font-size: var(--type-md); line-height: 1.6; }
.public-home .home-pillar-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: clamp(1rem, 4vw, 4rem); padding-top: 1rem; }
.public-home .home-pillar-grid article > svg { width: 30px; height: 30px; color: var(--deep); stroke-width: 1.65; stroke-linecap: round; stroke-linejoin: round; }
.public-home .home-pillar-grid h3 { margin: .75rem 0 .5rem; color: var(--ink); font-size: 1.1rem; }
.public-home .home-pillar-grid p { max-width: 300px; margin: 0; color: var(--muted); font-size: var(--type-md); line-height: 1.6; }
.public-home .home-life { padding: 3.5rem 0 5rem; background: #fff; }
.public-home .home-life .section-heading p { margin: .4rem 0 0; color: var(--muted); }
.public-home .home-life-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.25rem; }
.public-home .home-life-grid a { display: grid; gap: .8rem; color: var(--ink); font-size: var(--type-md); font-weight: var(--weight-semibold); text-decoration: none; }
.public-home .home-life-grid a:hover span,
.public-home .home-life-grid a:focus-visible span { color: var(--deep); text-decoration: underline; }
.public-home .home-life-grid img { width: 100%; aspect-ratio: 1.5; object-fit: cover; border-radius: 12px; }
.public-home .home-admissions { display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; margin-top: 5rem; padding: clamp(1.5rem, 3vw, 2.5rem); background: #e9eff7; border-radius: 18px; }
.public-home .home-admissions p { margin: .65rem 0 0; color: var(--muted); line-height: 1.5; }
.public-home .home-admissions a { display: inline-flex; align-items: center; gap: .65rem; flex: 0 0 auto; padding: .75rem 1rem; color: #fff; background: var(--deep); border-radius: 10px; font-weight: var(--weight-bold); text-decoration: none; }
.public-home .public-footer { margin-top: 2rem; color: var(--ink); background: transparent; border-top: 1px solid #dde5ef; border-radius: 0; }
.public-home .public-footer h2,
.public-home .public-footer .eyebrow { color: var(--ink); }
.public-home .public-footer .contact-list { color: var(--muted); }
@media (max-width: 900px) { .public-home .home-pillar-grid { gap: 1.5rem; } }
@media (max-width: 600px) {
    .public-home .home-hero,
    .public-home .home-pillars,
    .public-home .home-life,
    .public-home .home-admissions { width: calc(100% - 2rem); }
    .public-home .home-hero { padding: 1.6rem 0 3rem; }
    .public-home .hero-copy .eyebrow { display: none; }
    .public-home .hero-copy h1 { font-size: clamp(1.85rem, 7.5vw, 2.5rem); }
    .public-home .hero-lead { font-size: var(--type-md); -webkit-line-clamp: 3; }
    .public-home .hero-actions { margin-top: 1.5rem; }
    .public-home .hero-actions .text-link,
    .public-home .trust-line { display: none; }
    .public-home .hero-visual { min-height: 260px; }
    .public-home .home-pillar-grid,
    .public-home .home-life-grid { grid-template-columns: 1fr; }
    .public-home .home-pillars { padding-bottom: 3rem; }
    .public-home .home-life { padding: 2.5rem 0; }
    .public-home .home-life-grid a:nth-child(n+2) { display: none; }
    .public-home .home-admissions { align-items: flex-start; flex-direction: column; margin-top: 3rem; }
}
.public-home .hero-copy h1 { font-size: clamp(2.2rem, 4.4vw, 3.85rem); line-height: 1.08; letter-spacing: -.04em; }
.public-home .hero-actions { flex-wrap: wrap; gap: .85rem 1.15rem; }
.audience-pathways { width: min(1120px, calc(100% - 3rem)); margin: 0 auto 3.5rem; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .9rem; }
.pathway-card { display: grid; gap: .35rem; align-content: start; padding: 1.1rem 1.15rem; border: 1px solid color-mix(in srgb, var(--ink) 12%, transparent); border-radius: .85rem; background: #fff; text-decoration: none; color: var(--ink); transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease; }
.pathway-card:hover { transform: translateY(-2px); border-color: var(--deep); box-shadow: 0 10px 24px rgb(15 23 42 / .07); }
.pathway-tag { color: var(--gold); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .04em; text-transform: uppercase; }
.pathway-card strong { font-size: var(--type-md); line-height: 1.3; }
.pathway-card small { color: var(--muted); font-size: var(--type-meta); line-height: 1.45; }
.pathway-portal { background: color-mix(in srgb, var(--deep) 5%, #fff); border-color: color-mix(in srgb, var(--deep) 22%, transparent); }
.public-home .public-footer { display: grid; grid-template-columns: 1.2fr .9fr 1fr; gap: 2rem; align-items: start; }
.footer-brand h2 { margin: .35rem 0 .5rem; font-size: 1.35rem; }
.footer-desc { margin: 0; color: var(--muted); font-size: var(--type-caption); line-height: 1.6; max-width: 38ch; }
.footer-heading { margin: 0 0 .65rem; font-size: var(--type-caption); font-weight: var(--weight-bold); letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
.footer-links nav { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .45rem .8rem; }
.footer-links a { color: var(--ink); font-size: var(--type-caption); font-weight: var(--weight-semibold); text-decoration: none; }
.footer-links a:hover { color: var(--deep); text-decoration: underline; }
@media (max-width: 960px) {
    .audience-pathways { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .public-home .public-footer { grid-template-columns: 1fr; gap: 1.5rem; }
}
@media (max-width: 560px) {
    .audience-pathways { grid-template-columns: 1fr; width: min(1120px, calc(100% - 2rem)); }
}
.public-home > section,
.public-home > .public-footer { width: min(1180px, calc(100% - 3rem)); box-sizing: border-box; }
.public-home .section-heading h2 { font-family: var(--font-ui); font-size: clamp(1.8rem, 2.7vw, 2.7rem); font-weight: var(--weight-bold); letter-spacing: -.035em; line-height: 1.2; }
.public-home .section-heading .text-link { flex-shrink: 0; color: var(--deep); }
.public-home .home-life { padding: 2rem; border: 1px solid var(--public-line-strong); border-radius: 18px; }
.public-home .explore-section,
.public-home .content-section { padding-top: 4rem; padding-bottom: 0; }
.public-home .explore-item strong,
.public-home .news-body h3,
.public-home .public-footer h2 { font-family: var(--font-ui); font-weight: var(--weight-bold); letter-spacing: -.02em; }
.public-home .explore-item strong { font-size: 1.1rem; }
.public-home .explore-item { box-shadow: none; }
.public-home .news-card { display: flex; flex-direction: column; box-shadow: none; }
.public-home .news-body { flex: 1; grid-template-rows: auto auto 1fr auto; padding: 1.25rem; }
.public-home .news-body h3 { font-size: 1.1rem; line-height: 1.4; }
.public-home .news-image { flex-shrink: 0; }
.public-home .public-footer { padding-inline: 0; }
.public-home .contact-list { min-width: 0; overflow-wrap: anywhere; }
@media (max-width: 600px) {
    .public-home > section,
    .public-home > .public-footer { width: calc(100% - 2rem); }
    .public-home .hero-copy h1 { font-size: clamp(2rem, 8vw, 2.7rem); line-height: 1.15; }
    .public-home .hero-actions { flex-direction: row; align-items: center; gap: .75rem; }
    .public-home .home-life { padding: 1.25rem; }
    .public-home .explore-section,
    .public-home .content-section { padding-top: 2.5rem; }
    .public-home .section-heading { margin-bottom: 1.25rem; }
}
</style>
