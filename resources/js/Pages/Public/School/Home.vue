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

interface ShortcutItem {
    key: string;
    label: string;
    description: string;
    href: string;
}

const props = defineProps<{
    school: School;
    news: NewsItem[];
    gallery: GalleryItem[];
    shortcuts?: ShortcutItem[];
    visitStats?: { today: number; month: number; total: number };
    prayerSchedule?: { next_prayer: { name: string; time: string }; timezone: string };
    socialLinks?: Record<string, string>;
}>();
const galleryPreview = computed(() => props.gallery.slice(0, 4));
const lifePhotos = computed(() => galleryPreview.value.length > 1 ? galleryPreview.value.slice(1) : galleryPreview.value);
const initial = computed(() => props.school.name.trim().charAt(0).toUpperCase() || 'S');
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const heroTitle = computed(() => props.school.theme?.hero?.title || 'Tempat belajar yang memberi ruang untuk');
const heroAccent = computed(() => props.school.theme?.hero?.accent || 'tumbuh bersama.');
const heroPrimaryLabel = computed(() => props.school.theme?.hero?.primary_label || 'Daftar SPMB');
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

        <section v-if="shortcuts?.length" class="audience-pathways" aria-label="Pintasan layanan utama">
            <Link v-for="item in shortcuts" :key="item.key" :href="item.href" class="pathway-card">
                <span class="pathway-tag">Layanan Utama</span>
                <strong>{{ item.label }}</strong>
                <small>{{ item.description }}</small>
            </Link>
        </section>

        <section v-if="prayerSchedule || visitStats" class="audience-pathways" aria-label="Informasi harian dan direktori">
            <Link v-if="prayerSchedule" href="/jadwal-salat" class="pathway-card">
                <span class="pathway-tag">Jadwal Salat ({{ prayerSchedule.timezone }})</span>
                <strong>{{ prayerSchedule.next_prayer.name }} · {{ prayerSchedule.next_prayer.time }}</strong>
                <small v-if="visitStats">Kunjungan portal hari ini: {{ visitStats.today }} · Total: {{ visitStats.total }}</small>
            </Link>
            <Link href="/direktori-gtk" class="pathway-card">
                <span class="pathway-tag">Direktori</span>
                <strong>Direktori GTK</strong>
                <small>Lihat daftar guru dan tenaga kependidikan aktif.</small>
            </Link>
            <Link href="/pusat-unduh" class="pathway-card">
                <span class="pathway-tag">Dokumen</span>
                <strong>Pusat Unduh</strong>
                <small>Formulir resmi, kalender akademik, dan SOP.</small>
            </Link>
            <Link href="/zona-integritas" class="pathway-card">
                <span class="pathway-tag">Mutu Layanan</span>
                <strong>Zona Integritas &amp; IKM</strong>
                <small>Maklumat pelayanan dan penilaian kepuasan masyarakat.</small>
            </Link>
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
/* Calm Academic — single consolidated layer. */
:global(body) { margin: 0; background: #f2f6f9; }
:global(html[data-theme='dark']) .public-site { color: #173044 !important; background: #f2f6f9 !important; }
:global(body .public-site.public-home .hero-copy h1 span) { color: var(--ink) !important; }
:global(body .public-site.public-home .home-hero .eyebrow) { color: var(--deep) !important; }

.public-site { --ink: var(--public-ink, #173044); --muted: var(--public-muted, #526673); --paper: var(--public-surface, #f5f8fb); --deep: var(--public-primary, #3157a4); --gold: var(--public-accent, #8a661f); --line: #dde5ef; min-height: 100vh; color: var(--ink); background: #f4f7fb; font-family: var(--font-ui); }
.public-home > section,
.public-home > .public-footer { width: min(1180px, calc(100% - 3rem)); box-sizing: border-box; margin-inline: auto; }

.skip-link { position: fixed; top: -4rem; left: 1rem; z-index: 10; padding: .7rem 1rem; color: var(--paper); background: var(--deep); border-radius: .4rem; font-size: var(--type-meta); font-weight: var(--weight-bold); text-decoration: none; }
.skip-link:focus { top: 1rem; }

.eyebrow { margin: 0; color: #9a7329; font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .08em; text-transform: uppercase; }
.text-link { flex-shrink: 0; color: var(--deep); font-size: var(--type-sm); font-weight: var(--weight-bold); text-decoration: none; }
.read-link { margin-top: .5rem; color: var(--gold); font-size: var(--type-meta); font-weight: var(--weight-bold); text-decoration: none; }
.text-link:hover, .text-link:focus-visible, .read-link:hover, .read-link:focus-visible { text-decoration: underline; }

/* Section headings */
.section-heading { display: flex; align-items: end; justify-content: space-between; gap: 2rem; margin-bottom: 2rem; }
.section-heading h2,
.home-admissions h2 { max-width: 620px; margin: .7rem 0 0; color: var(--ink); font-family: var(--font-ui); font-size: clamp(1.8rem, 2.7vw, 2.7rem); font-weight: var(--weight-bold); letter-spacing: -.035em; line-height: 1.2; }
.section-heading > p { max-width: 220px; margin: 0; color: var(--muted); font-size: var(--type-sm); }
.home-pillars h2, .home-life h2, .home-admissions h2 { max-width: 540px; margin: 0; }

/* Hero */
.home-hero { position: relative; display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(300px, .95fr); gap: clamp(2rem, 5vw, 5rem); align-items: center; padding: clamp(3rem, 5vw, 5rem) 0; isolation: isolate; }
.hero-copy, .hero-visual { position: relative; z-index: 1; }
.hero-copy .eyebrow { margin-bottom: 1rem; color: var(--deep); }
.hero-copy h1 { max-width: 680px; margin: 0 0 1.5rem; color: var(--ink); font-family: var(--font-ui); font-size: clamp(2.2rem, 4.4vw, 3.85rem); font-weight: var(--weight-bold); letter-spacing: -.04em; line-height: 1.08; }
.hero-copy h1 span { color: var(--ink); }
.hero-lead { display: -webkit-box; max-width: 510px; margin: 0; overflow: hidden; color: var(--muted); font-size: var(--type-md); line-height: 1.65; -webkit-box-orient: vertical; -webkit-line-clamp: 4; }
.hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .85rem 1.15rem; margin-top: 2.1rem; }
.trust-line { display: flex; flex-wrap: wrap; margin-top: 1.8rem; padding: 1rem 0; color: var(--muted); background: #fff; border: 1px solid var(--line); border-radius: 12px; font-size: var(--type-meta); }
.trust-line span { padding: 0 1rem; }
.trust-line span + span { border-left: 1px solid var(--line); }

.hero-visual { min-height: clamp(320px, 33vw, 455px); overflow: hidden; background: #e9eff7; border-radius: 18px; }
.hero-photo-grid { position: absolute; inset: 0; }
.hero-photo { position: absolute; display: block; object-fit: cover; filter: saturate(.84); }
.hero-photo-1 { inset: 0; width: 100%; height: 100%; border-radius: inherit; }
.hero-photo-2, .hero-caption { display: none; }
.hero-variant-minimal .hero-photo-1 { opacity: .68; filter: saturate(.55); }
.hero-placeholder { position: absolute; inset: 0; display: grid; place-content: center; justify-items: center; overflow: hidden; color: var(--deep); background: var(--gold); font-family: var(--font-ui); }
.hero-placeholder::before,
.hero-placeholder::after { position: absolute; width: 18rem; height: 18rem; border: 1px solid color-mix(in srgb, var(--deep) 40%, transparent); border-radius: 50%; content: ''; }
.hero-placeholder::before { top: -7rem; right: -5rem; }
.hero-placeholder::after { bottom: -11rem; left: -6rem; }
.hero-placeholder span { position: relative; z-index: 1; font-size: clamp(7rem, 13vw, 11rem); letter-spacing: -.08em; line-height: 1; }
.hero-placeholder small { position: relative; z-index: 1; margin-top: .5rem; color: color-mix(in srgb, var(--deep) 82%, #fff 18%); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .08em; text-transform: uppercase; }

/* Audience pathways */
.audience-pathways { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .9rem; margin-bottom: 3.5rem; }
.pathway-card { display: grid; align-content: start; gap: .35rem; padding: 1.1rem 1.15rem; color: var(--ink); background: #fff; border: 1px solid color-mix(in srgb, var(--ink) 12%, transparent); border-radius: .85rem; text-decoration: none; transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease; }
.pathway-card:hover, .pathway-card:focus-visible { border-color: var(--deep); box-shadow: 0 10px 24px rgb(15 23 42 / .07); transform: translateY(-2px); }
.pathway-tag { color: var(--gold); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .04em; text-transform: uppercase; }
.pathway-card strong { font-size: var(--type-md); line-height: 1.3; }
.pathway-card small { color: var(--muted); font-size: var(--type-meta); line-height: 1.45; }
.pathway-portal { background: color-mix(in srgb, var(--deep) 5%, #fff); border-color: color-mix(in srgb, var(--deep) 22%, transparent); }

/* Pillars */
.home-pillars { padding: 1rem 0 4.5rem; }
.home-pillars .section-heading { align-items: start; }
.home-pillars .section-heading p { max-width: 360px; font-size: var(--type-md); line-height: 1.6; }
.home-pillar-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: clamp(1rem, 4vw, 4rem); padding-top: 1rem; }
.home-pillar-grid svg { width: 30px; height: 30px; color: var(--deep); stroke-width: 1.65; stroke-linecap: round; stroke-linejoin: round; }
.home-pillar-grid h3 { margin: .75rem 0 .5rem; color: var(--ink); font-size: 1.1rem; }
.home-pillar-grid p { max-width: 300px; margin: 0; color: var(--muted); font-size: var(--type-md); line-height: 1.6; }

/* School life */
.home-life { padding: 2rem; background: #fff; border: 1px solid var(--public-line-strong); border-radius: 18px; }
.home-life .section-heading p { margin: .4rem 0 0; color: var(--muted); }
.home-life-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.25rem; }
.home-life-grid a { display: grid; gap: .8rem; color: var(--ink); font-size: var(--type-md); font-weight: var(--weight-semibold); text-decoration: none; }
.home-life-grid a:hover span, .home-life-grid a:focus-visible span { color: var(--deep); text-decoration: underline; }
.home-life-grid img { width: 100%; aspect-ratio: 1.5; object-fit: cover; border-radius: 12px; }

/* Explore */
.explore-section, .content-section { padding-top: 4rem; }
.explore-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .85rem; }
.explore-item { display: grid; gap: .55rem; min-height: 180px; padding: 1.25rem; color: var(--ink); background: var(--public-surface); border: 1px solid var(--public-line-strong); border-radius: var(--public-radius-md); text-decoration: none; transition: transform .2s var(--public-transition), border-color .2s var(--public-transition), box-shadow .2s var(--public-transition); }
.explore-item:hover, .explore-item:focus-visible { border-color: var(--deep); box-shadow: var(--public-shadow-deep); transform: translateY(-4px); }
.explore-item strong { color: var(--ink); font-family: var(--font-ui); font-size: 1.1rem; font-weight: var(--weight-bold); letter-spacing: -.02em; }
.explore-item span { color: var(--muted); font-size: var(--type-meta); line-height: 1.5; }
.explore-item b { align-self: end; color: var(--gold); font-size: 1.1rem; font-weight: var(--weight-regular); }

/* News */
.news-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.2rem; }
.news-card { display: flex; flex-direction: column; overflow: hidden; color: var(--ink); background: var(--public-surface); border: 1px solid var(--public-line-strong); border-radius: var(--public-radius-md); text-decoration: none; transition: transform .2s var(--public-transition), box-shadow .2s var(--public-transition); }
.news-card:hover, .news-card:focus-visible { box-shadow: var(--public-shadow-deep); transform: translateY(-4px); }
.news-image { display: grid; flex-shrink: 0; place-items: center; height: 210px; color: var(--gold); background: var(--deep); font-family: var(--font-ui); font-size: 2.4rem; }
.news-image img { width: 100%; height: 100%; object-fit: cover; }
.news-body { display: grid; flex: 1; grid-template-rows: auto auto 1fr auto; gap: .45rem; padding: 1.25rem; }
.news-body small { color: var(--muted); font-size: var(--type-meta); }
.news-body h3 { margin: .2rem 0; font-family: var(--font-ui); font-size: 1.1rem; font-weight: var(--weight-bold); letter-spacing: -.02em; line-height: 1.4; }
.news-body p { min-height: 2.6rem; margin: 0; color: var(--muted); font-size: var(--type-meta); line-height: 1.55; }
.empty-state { color: var(--muted); }

/* Admissions banner */
.home-admissions { display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; margin-top: 5rem; padding: clamp(1.5rem, 3vw, 2.5rem); background: #e9eff7; border-radius: 18px; }
.home-admissions p { margin: .65rem 0 0; color: var(--muted); line-height: 1.5; }
.home-admissions a { display: inline-flex; flex: 0 0 auto; align-items: center; gap: .65rem; padding: .75rem 1rem; color: #fff; background: var(--deep); border-radius: 10px; font-weight: var(--weight-bold); text-decoration: none; }

/* Footer */
.public-footer { display: grid; grid-template-columns: 1.2fr .9fr 1fr; align-items: start; gap: 2rem; margin-top: 2rem; padding: 3rem 0; color: var(--ink); border-top: 1px solid var(--line); }
.public-footer .eyebrow { color: var(--ink); }
.footer-brand h2 { margin: .35rem 0 .5rem; color: var(--ink); font-family: var(--font-ui); font-size: 1.35rem; font-weight: var(--weight-bold); letter-spacing: -.02em; }
.footer-desc { max-width: 38ch; margin: 0; color: var(--muted); font-size: var(--type-caption); line-height: 1.6; }
.footer-heading { margin: 0 0 .65rem; color: var(--muted); font-size: var(--type-caption); font-weight: var(--weight-bold); letter-spacing: .06em; text-transform: uppercase; }
.footer-links nav { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .45rem .8rem; }
.footer-links a { color: var(--ink); font-size: var(--type-caption); font-weight: var(--weight-semibold); text-decoration: none; }
.footer-links a:hover, .footer-links a:focus-visible { color: var(--deep); text-decoration: underline; }
.contact-list { display: grid; align-content: start; gap: .45rem; min-width: 0; color: var(--muted); font-size: var(--type-md); line-height: 1.6; overflow-wrap: anywhere; }
.contact-list p { margin: 0; }
.contact-list a { color: inherit; text-decoration: none; }
.contact-list a:hover, .contact-list a:focus-visible { color: var(--ink); text-decoration: underline; }

@media (max-width: 960px) {
    .audience-pathways { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .public-footer { grid-template-columns: 1fr; gap: 1.5rem; }
}
@media (max-width: 900px) {
    .home-hero { grid-template-columns: 1fr; }
    .home-pillar-grid { gap: 1.5rem; }
    .explore-grid { grid-template-columns: repeat(2, 1fr); }
    .news-grid { grid-template-columns: repeat(2, 1fr); }
    .news-card:first-child { grid-column: span 2; }
}
@media (max-width: 600px) {
    .public-home > section,
    .public-home > .public-footer { width: calc(100% - 2rem); }
    .home-hero { padding: 1.6rem 0 3rem; }
    .hero-copy .eyebrow, .hero-actions .text-link, .trust-line { display: none; }
    .hero-copy h1 { font-size: clamp(2rem, 8vw, 2.7rem); line-height: 1.15; }
    .hero-lead { -webkit-line-clamp: 3; }
    .hero-actions { gap: .75rem; margin-top: 1.5rem; }
    .hero-visual { min-height: 260px; }
    .section-heading { flex-direction: column; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem; }
    .home-pillars { padding-bottom: 3rem; }
    .home-pillar-grid, .home-life-grid, .explore-grid, .news-grid { grid-template-columns: 1fr; }
    .home-life { padding: 1.25rem; }
    .home-life-grid a:nth-child(n+2) { display: none; }
    .explore-section, .content-section { padding-top: 2.5rem; }
    .news-card:first-child { grid-column: auto; }
    .home-admissions { flex-direction: column; align-items: flex-start; margin-top: 3rem; }
}
@media (max-width: 560px) {
    .audience-pathways { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
    .pathway-card, .explore-item, .news-card { transition: none; }
    .pathway-card:hover, .explore-item:hover, .news-card:hover { transform: none; }
}
</style>
