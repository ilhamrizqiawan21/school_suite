<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import PublicHeader from '@/components/PublicHeader.vue';

interface SchoolProfile {
    name: string;
    theme?: { primary?: string | null };
    legal_name?: string | null;
    level?: string | null;
    school_status?: string | null;
    accreditation?: string | null;
    address?: string | null;
    phone?: string | null;
    email?: string | null;
    principal_name?: string | null;
    website?: string | null;
    about?: string | null;
    principal_message?: string | null;
    vision?: string | null;
    mission?: string | null;
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

interface GalleryPhoto {
    id: number;
    title?: string | null;
    caption?: string | null;
    url: string;
}

interface OrganizationMember {
    id: number;
    parent_id?: number | null;
    name: string;
    position: string;
    department?: string | null;
    photo_url?: string | null;
    depth: number;
}

const props = defineProps<{ school: SchoolProfile; news: NewsItem[]; gallery: GalleryPhoto[]; organization: OrganizationMember[] }>();
const missionPoints = computed(() => (props.school.mission ?? '')
    .split(/\r?\n/)
    .map((item) => item.trim())
    .filter(Boolean));
const initial = computed(() => props.school.name.trim().charAt(0).toUpperCase() || 'S');
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
</script>

<template>
    <Head :title="school.name" />
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
    <main id="main-content" class="public-site school-profile" :style="themeStyle">
        <div class="identity-watermark" aria-hidden="true"><img v-if="school.logo_url" :src="school.logo_url" alt="" /><span v-else>{{ initial }}</span></div>
        <PublicHeader :school="school" active="profile" />

        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-copy">
                <p class="kicker">{{ school.level || 'Sekolah' }} · Profil resmi</p>
                <h1 id="hero-title">{{ school.name }}<span class="hero-title-accent">ruang untuk tumbuh bersama.</span></h1>
                <p class="lead">{{ school.about || 'Selamat datang di ruang informasi resmi sekolah kami.' }}</p>
                <div class="hero-actions"><a href="#tentang" class="primary-button">Kenali sekolah kami</a><Link href="/galeri" class="quiet-link">Lihat galeri sekolah</Link></div>
            </div>
            <div class="hero-visual" aria-label="Identitas sekolah">
                <div class="hero-empty-visual"><div class="orbit orbit-one" /><div class="orbit orbit-two" /><div class="hero-emblem"><img v-if="school.logo_url" :src="school.logo_url" :alt="'Logo ' + school.name" /><span v-else>{{ initial }}</span></div></div>
                <div class="hero-note"><span>{{ school.school_status || 'Ruang belajar bersama' }}</span><strong>Belajar. Berkarya. Berbagi.</strong><small>Portofolio resmi {{ school.name }}</small></div>
            </div>
        </section>

        <section class="fact-strip" aria-label="Informasi singkat sekolah">
            <div v-if="school.level"><span>Jenjang</span><strong>{{ school.level }}</strong></div>
            <div v-if="school.school_status"><span>Status</span><strong>{{ school.school_status }}</strong></div>
            <div v-if="school.accreditation"><span>Akreditasi</span><strong>{{ school.accreditation }}</strong></div>
            <div v-if="school.principal_name"><span>Kepala sekolah</span><strong>{{ school.principal_name }}</strong></div>
        </section>

        <section id="tentang" class="content-section two-column">
            <div><p class="kicker">Tentang sekolah</p><h2>Karakter yang dibangun setiap hari.</h2></div>
            <div class="about-copy"><p>{{ school.about || 'Profil sekolah sedang disiapkan.' }}</p><p v-if="school.legal_name" class="muted">{{ school.legal_name }}</p></div>
        </section>

        <section v-if="school.principal_message" id="sambutan" class="message-section">
            <div class="quote-mark" aria-hidden="true">“</div>
            <div><p class="kicker">Sambutan kepala sekolah</p><blockquote>{{ school.principal_message }}</blockquote><cite>{{ school.principal_name || 'Kepala Sekolah' }}</cite></div>
        </section>

        <section v-if="school.vision || missionPoints.length" id="arah" class="content-section two-column">
            <div><p class="kicker">Arah pendidikan</p><h2>Nilai yang menjadi kompas.</h2></div>
            <div class="direction-grid">
                <article v-if="school.vision"><span>Visi</span><p>{{ school.vision }}</p></article>
                <article v-if="missionPoints.length"><span>Misi</span><ul><li v-for="mission in missionPoints" :key="mission">{{ mission }}</li></ul></article>
            </div>
        </section>

        <footer id="kontak" class="contact-footer">
            <div><p class="kicker">Hubungi sekolah</p><h2>Mari terhubung.</h2></div>
            <div class="contact-list"><p v-if="school.address">{{ school.address }}</p><a v-if="school.phone" :href="'tel:' + school.phone">{{ school.phone }}</a><a v-if="school.email" :href="'mailto:' + school.email">{{ school.email }}</a><a v-if="school.website" :href="school.website" target="_blank" rel="noreferrer">{{ school.website }}</a></div>
        </footer>
    </main>
</template>

<style scoped>
:global(body){margin:0;background:#fffdf8}.school-profile{--ink:var(--public-ink,#173044);--muted:var(--public-muted,#526673);--paper:var(--public-paper,#fffdf8);--deep:var(--public-primary,#3157a4);--gold:var(--public-accent,#8a661f);min-height:100vh;color:var(--ink);background:var(--paper);font-family:var(--font-ui)}.site-header,.hero,.content-section,.fact-strip,.contact-footer{width:min(1180px,calc(100% - 3rem));margin-inline:auto}.site-header{display:flex;align-items:center;justify-content:space-between;gap:2rem;padding:1.4rem 0}.brand{display:flex;align-items:center;gap:.7rem;color:var(--ink);font-size:.95rem;font-weight:800;text-decoration:none}.brand-mark{display:grid;place-items:center;width:2.4rem;height:2.4rem;overflow:hidden;color:var(--deep);background:var(--gold);border-radius:.75rem .75rem .75rem .2rem;font-family:var(--font-display);font-size:1.2rem}.brand-mark img{width:100%;height:100%;object-fit:contain;background:#fff}.site-nav{display:flex;align-items:center;gap:1.2rem;font-size:.78rem}.site-nav a{color:var(--muted);text-decoration:none}.site-nav a:hover,.site-nav a:focus-visible{color:var(--ink)}.site-nav .login-link{padding:.65rem .9rem;color:var(--paper);background:var(--deep);border-radius:.45rem;font-weight: 700}.hero{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);gap:4rem;align-items:center;padding:5rem 0 4.5rem}.kicker{margin:0;color:#9a7329;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.hero h1{max-width:720px;margin:1rem 0 1.4rem;font-family:var(--font-display);font-size:clamp(3rem,6vw,6.4rem);font-weight:400;letter-spacing:-.065em;line-height:.98}.hero h1 em{color:#a2772b;font-style:normal}.lead{max-width:560px;margin:0;color:var(--muted);font-size:1.04rem;line-height:1.8}.hero-actions{display:flex;align-items:center;gap:1.2rem;margin-top:2rem}.primary-button{padding:.85rem 1.05rem;color:var(--paper);background:var(--deep);border-radius:.45rem;font-size:.82rem;font-weight: 700;text-decoration:none}.quiet-link{color:#9a7329;font-size:.82rem;font-weight: 700;text-decoration:none}.quiet-link:hover,.quiet-link:focus-visible{text-decoration:underline}.hero-visual{position:relative;min-height:450px;overflow:hidden;background:var(--deep);border-radius:12rem 1.4rem 1.4rem 1.4rem}.hero-visual:after{position:absolute;right:-8%;bottom:-16%;width:270px;height:270px;border:1px solid #d4a74b80;border-radius:50%;content:""}.orbit{position:absolute;border:1px solid #d4a74b55;border-radius:50%;transform:rotate(-24deg)}.orbit-one{inset:12% -25% 34% 8%}.orbit-two{inset:30% 8% 10% -22%}.hero-emblem{position:absolute;top:20%;left:17%;display:grid;place-items:center;width:145px;height:145px;overflow:hidden;color:var(--deep);background:var(--gold);border-radius:50%;font-family:var(--font-display);font-size:4.5rem}.hero-emblem img{width:100%;height:100%;object-fit:contain;background:#fff}.hero-note{position:absolute;right:12%;bottom:14%;display:grid;gap:.35rem;max-width:220px;color:#f5efe2}.hero-note span{color:var(--gold);font-size:.73rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.hero-note strong{font-family:var(--font-display);font-size:1.9rem;font-weight:400;line-height:1.05}.hero-note small{color:#b4c4c9;font-size:.76rem}.fact-strip{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid #cfc8ba;border-bottom:1px solid #cfc8ba}.fact-strip div{display:grid;gap:.3rem;padding:1.15rem 1rem;border-right:1px solid #cfc8ba}.fact-strip div:first-child{padding-left:0}.fact-strip div:last-child{border-right:0}.fact-strip span{color:var(--muted);font-size:.7rem}.fact-strip strong{font-size:.9rem}.content-section{padding:6rem 0}.two-column{display:grid;grid-template-columns:.75fr 1.25fr;gap:5rem}.two-column h2,.section-heading h2,.contact-footer h2{max-width:420px;margin:.7rem 0 0;font-family:var(--font-display);font-size:clamp(2rem,3.8vw,3.7rem);font-weight:400;letter-spacing:-.05em;line-height:1.03}.about-copy>p:first-child{margin:0;font-family:var(--font-display);font-size:clamp(1.55rem,2.5vw,2.35rem);line-height:1.25}.muted{margin-top:1.5rem;color:var(--muted);font-size:.8rem}.message-section{display:grid;grid-template-columns:110px 1fr;gap:2rem;align-items:start;width:min(1020px,calc(100% - 3rem));margin:0 auto;padding:3.5rem 4rem;color:#eef1e8;background:var(--deep);border-radius:1rem}.quote-mark{color:var(--gold);font-family:var(--font-display);font-size:8rem;line-height:.55}.message-section blockquote{max-width:760px;margin:1rem 0 1.2rem;font-family:var(--font-display);font-size:clamp(1.8rem,3.4vw,3.3rem);font-weight:400;letter-spacing:-.04em;line-height:1.12}.message-section cite{color:#b8c6c7;font-size:.8rem;font-style:normal}.direction-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.3rem}.direction-grid article{padding:1.5rem 0;border-top:2px solid var(--gold)}.direction-grid article>span{color:#9a7329;font-size:.75rem;font-weight:800}.direction-grid p,.direction-grid li{color:var(--muted);font-size:1rem;line-height:1.75}.direction-grid ul{padding-left:1.1rem}.section-heading{display:flex;align-items:end;justify-content:space-between;gap:2rem;margin-bottom:2rem}.news-list{border-top:1px solid #cfc8ba}.news-row{display:grid;grid-template-columns:120px minmax(0,1fr) auto;gap:1.2rem;align-items:center;padding:1rem 0;color:inherit;border-bottom:1px solid #cfc8ba;text-decoration:none}.news-image{display:grid;place-items:center;width:120px;height:88px;overflow:hidden;color:var(--gold);background:var(--deep);font-family:var(--font-display);font-size:2rem}.news-image img{width:100%;height:100%;object-fit:cover}.news-row small{color:var(--muted);font-size:.7rem}.news-row h3{margin:.35rem 0;font-family:var(--font-display);font-size:1.45rem;font-weight:400}.news-row p{margin:0;color:var(--muted);font-size:.82rem;line-height:1.5}.news-arrow{color:#9a7329;font-size:1.4rem}.news-row:hover h3,.news-row:focus-visible h3{color:#9a7329}.empty-news{padding:2rem 0;color:var(--muted)}.contact-footer{display:grid;grid-template-columns:1fr 1fr;gap:3rem;padding:4rem 0 5rem;border-top:1px solid #cfc8ba}.contact-list{display:grid;align-content:start;gap:.45rem;color:var(--muted);font-size:.9rem;line-height:1.6}.contact-list p{margin:0}.contact-list a{color:inherit;text-decoration:none}.contact-list a:hover,.contact-list a:focus-visible{color:var(--ink);text-decoration:underline}.site-nav a:focus-visible,.primary-button:focus-visible,.quiet-link:focus-visible,.news-row:focus-visible,.brand:focus-visible,.contact-list a:focus-visible{outline:3px solid #c6953e;outline-offset:4px;border-radius:.2rem}@media(max-width:800px){.site-header{align-items:flex-start}.site-nav{flex-wrap:wrap;justify-content:flex-end}.hero{grid-template-columns:1fr;gap:2.5rem;padding-top:3.5rem}.hero-visual{min-height:350px}.two-column{grid-template-columns:1fr;gap:2rem}.message-section{padding:2.5rem 2rem}.fact-strip{grid-template-columns:repeat(2,1fr)}.fact-strip div:nth-child(2){border-right:0}.fact-strip div:nth-child(-n+2){border-bottom:1px solid #cfc8ba}.fact-strip div:first-child{padding-left:1rem}}@media(max-width:560px){.site-header,.hero,.content-section,.fact-strip,.contact-footer,.message-section{width:calc(100% - 2rem)}.site-header{display:grid;gap:1rem}.site-nav{justify-content:flex-start;gap:.8rem}.site-nav a:nth-child(-n+3){display:none}.hero h1{font-size:3.2rem}.hero-actions{align-items:flex-start;flex-direction:column;gap:1rem}.hero-visual{min-height:320px}.hero-emblem{left:10%;width:110px;height:110px;font-size:3.5rem}.hero-note{right:8%;bottom:12%;max-width:175px}.hero-note strong{font-size:1.55rem}.message-section{grid-template-columns:1fr;gap:1rem;padding:2rem 1.2rem}.quote-mark{font-size:5rem}.direction-grid,.contact-footer{grid-template-columns:1fr}.section-heading{align-items:flex-start;flex-direction:column}.news-row{grid-template-columns:72px minmax(0,1fr) auto;gap:.7rem}.news-image{width:72px;height:70px}.news-row h3{font-size:1.05rem}.news-row p{display:none}.news-arrow{font-size:1.1rem}}
.portfolio-gallery{padding-top:0}.portfolio-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}.portfolio-card{display:block;overflow:hidden;color:inherit;background:#fff;border-radius:.8rem;text-decoration:none}.portfolio-card:nth-child(2){transform:translateY(1.5rem)}.portfolio-card img{display:block;width:100%;height:230px;object-fit:cover;transition:transform .25s ease}.portfolio-card span{display:block;padding:.7rem .8rem;color:var(--ink);font-family:var(--font-display);font-size:.95rem}.portfolio-card:hover img,.portfolio-card:focus-visible img{transform:scale(1.03)}@media(max-width:800px){.portfolio-grid{grid-template-columns:repeat(2,1fr)}.portfolio-card:nth-child(2){transform:none}.portfolio-card:nth-child(3){transform:translateY(1rem)}}@media(max-width:560px){.portfolio-grid{grid-template-columns:1fr}.portfolio-card:nth-child(3){transform:none}.portfolio-card img{height:220px}}
.organization-tree{display:grid;gap:.7rem}.organization-node{display:grid;grid-template-columns:5px 48px minmax(0,1fr);gap:.85rem;align-items:center;margin-left:calc(var(--depth) * 1.5rem);padding:1rem 1.1rem;background:#fff;border:1px solid #ded7c9;border-radius:.7rem;box-shadow:0 8px 24px #183b5d0a}.node-line{height:100%;min-height:52px;background:var(--gold);border-radius:999px}.node-avatar{display:grid;place-items:center;width:48px;height:48px;overflow:hidden;color:var(--deep);background:#e7d39e;border-radius:50%;font-family:var(--font-display);font-size:1.35rem}.node-avatar img{width:100%;height:100%;object-fit:cover}.node-position{color:#9a7329;font-size:.68rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase}.organization-node h3{margin:.25rem 0 0;font-family:var(--font-display);font-size:1.25rem;font-weight:400}.organization-node p{margin:.25rem 0 0;color:var(--muted);font-size:.78rem}@media(max-width:560px){.organization-node{margin-left:calc(var(--depth) * .65rem)}}
</style>

<style scoped>
.skip-link {
    position: fixed;
    top: -4rem;
    left: 1rem;
    z-index: 10;
    padding: .7rem 1rem;
    color: #f4f0e7;
    background: #123247;
    border-radius: .4rem;
    font-size: .8rem;
    font-weight: 700;
    text-decoration: none;
}

.skip-link:focus { top: 1rem; }
.site-header, .hero, .content-section, .fact-strip, .contact-footer, .service-section { width: min(1180px, calc(100% - 3rem)); }
.hero-title-accent { display: block; color: #a2772b; }
.hero-meta { display: flex; flex-wrap: wrap; gap: .55rem 1rem; margin-top: 1.8rem; color: var(--muted); font-size: .73rem; }
.hero-meta span { display: flex; align-items: center; gap: .35rem; }
.hero-meta strong { color: var(--ink); font-size: .95rem; }
.hero-visual:after { z-index: 2; pointer-events: none; }
.hero-collage { position: absolute; inset: 0; background: var(--deep); }
.hero-photo { position: absolute; display: block; object-fit: cover; filter: saturate(.82); box-shadow: 0 18px 40px #0a1d2e66; }
.hero-photo-1 { inset: 0 23% 30% 0; width: 77%; height: 70%; border-radius: 0 0 5rem 0; }
.hero-photo-2 { right: 0; bottom: 0; width: 53%; height: 52%; border-top: 10px solid var(--deep); border-left: 10px solid var(--deep); border-radius: 5rem 0 0 0; }
.hero-photo-3 { top: 13%; right: 10%; width: 31%; height: 31%; border: 8px solid var(--deep); border-radius: 50%; object-fit: cover; }
.hero-empty-visual { position: absolute; inset: 0; }
.hero-note { z-index: 3; }
.service-section { padding: 6rem 0 2rem; }
.service-intro { display: grid; grid-template-columns: .75fr 1.25fr; gap: 5rem; margin-bottom: 2rem; }
.service-intro h2 { max-width: 620px; margin: .7rem 0 0; font-family: var(--font-display); font-size: clamp(2rem, 3.8vw, 3.7rem); font-weight: 400; letter-spacing: -.05em; line-height: 1.03; }
.service-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.service-card { display: grid; align-content: start; gap: .7rem; min-height: 205px; padding: 1.35rem; color: var(--ink); background: #fffdf8; border: 1px solid #ded7c9; border-radius: .8rem; text-decoration: none; box-shadow: 0 12px 30px #183b5d0a; transition: transform .2s ease, box-shadow .2s ease; }
.service-card:hover, .service-card:focus-visible { transform: translateY(-4px); box-shadow: 0 16px 32px #183b5d17; }
.service-number { color: #9a7329; font-size: .7rem; font-weight: 800; letter-spacing: .08em; }
.service-card strong { font-family: var(--font-display); font-size: 1.45rem; font-weight: 400; }
.service-card p { margin: 0; color: var(--muted); font-size: .8rem; line-height: 1.6; }
.service-link { align-self: end; margin-top: auto; color: #9a7329; font-size: .78rem; font-weight: 800; }
.site-nav a:focus-visible, .primary-button:focus-visible, .quiet-link:focus-visible, .news-row:focus-visible, .brand:focus-visible, .contact-list a:focus-visible, .service-card:focus-visible { outline: 3px solid #c6953e; outline-offset: 4px; border-radius: .2rem; }

@media (max-width: 800px) {
    .service-intro { grid-template-columns: 1fr; gap: 2rem; }
    .service-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 560px) {
    .site-header, .hero, .content-section, .fact-strip, .contact-footer, .message-section, .service-section { width: calc(100% - 2rem); }
    .hero-photo-1 { inset: 0 15% 28% 0; width: 85%; }
    .hero-photo-2 { width: 62%; height: 50%; }
    .hero-photo-3 { top: 12%; right: 8%; width: 34%; height: 30%; }
    .service-section { padding-top: 4rem; }
    .service-grid { grid-template-columns: 1fr; }
    .service-card { min-height: 170px; }
}

@media (prefers-reduced-motion: reduce) {
    .service-card, .portfolio-card img { transition: none; }
}
</style>
