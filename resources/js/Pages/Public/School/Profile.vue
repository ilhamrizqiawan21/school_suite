<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SchoolProfile {
    name: string;
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

const props = defineProps<{ school: SchoolProfile; news: NewsItem[] }>();
const missionPoints = computed(() => (props.school.mission ?? '')
    .split(/\r?\n/)
    .map((item) => item.trim())
    .filter(Boolean));
const formatDate = (value?: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(value))
    : '';
</script>

<template>
    <Head :title="school.name" />
    <main class="school-profile">
        <header class="site-header">
            <Link href="/profil-sekolah" class="brand" aria-label="Profil sekolah">
                <span class="brand-mark"><img v-if="school.logo_url" :src="school.logo_url" :alt="`Logo ${school.name}`" /><span v-else>{{ school.name.slice(0, 1) }}</span></span>
                <span>{{ school.name }}</span>
            </Link>
            <nav class="site-nav" aria-label="Navigasi publik">
                <a href="#tentang">Tentang</a>
                <a href="#sambutan">Sambutan</a>
                <a href="#kontak">Kontak</a>
                <Link href="/berita">Berita sekolah</Link>
                <Link href="/login" class="login-link">Masuk</Link>
            </nav>
        </header>

        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-copy">
                <p class="kicker">Profil resmi sekolah</p>
                <h1 id="hero-title">Tempat bertumbuh, <em>berkarya,</em> dan menjadi diri sendiri.</h1>
                <p class="lead">{{ school.about || 'Selamat datang di ruang informasi resmi sekolah kami.' }}</p>
                <div class="hero-actions"><a href="#tentang" class="primary-button">Kenali sekolah kami</a><Link href="/berita" class="quiet-link">Lihat berita terbaru</Link></div>
            </div>
            <div class="hero-visual" aria-label="Ringkasan sekolah">
                <div class="orbit orbit-one" /><div class="orbit orbit-two" />
                <div class="hero-emblem"><img v-if="school.logo_url" :src="school.logo_url" :alt="`Logo ${school.name}`" /><span v-else>{{ school.name.slice(0, 1) }}</span></div>
                <div class="hero-note"><span>{{ school.level || 'Sekolah' }}</span><strong>{{ school.name }}</strong><small>{{ school.school_status || 'Ruang belajar bersama' }}</small></div>
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

        <section v-if="school.vision || missionPoints.length" class="content-section two-column">
            <div><p class="kicker">Arah pendidikan</p><h2>Nilai yang menjadi kompas.</h2></div>
            <div class="direction-grid">
                <article v-if="school.vision"><span>Visi</span><p>{{ school.vision }}</p></article>
                <article v-if="missionPoints.length"><span>Misi</span><ul><li v-for="mission in missionPoints" :key="mission">{{ mission }}</li></ul></article>
            </div>
        </section>

        <section class="content-section news-section">
            <div class="section-heading"><div><p class="kicker">Kabar terbaru</p><h2>Berita dan kegiatan sekolah.</h2></div><Link href="/berita" class="quiet-link">Lihat semua berita</Link></div>
            <div v-if="news.length" class="news-list">
                <Link v-for="item in news" :key="item.id" :href="`/berita/${item.slug}`" class="news-row">
                    <div class="news-image"><img v-if="item.photos[0]" :src="item.photos[0].url" :alt="item.title" /><span v-else>{{ school.name.slice(0, 1) }}</span></div>
                    <div><small>{{ formatDate(item.published_at) }}</small><h3>{{ item.title }}</h3><p>{{ item.excerpt || 'Baca informasi lengkap dari sekolah.' }}</p></div><span class="news-arrow" aria-hidden="true">↗</span>
                </Link>
            </div>
            <p v-else class="empty-news">Berita sekolah akan tampil di halaman ini.</p>
        </section>

        <footer id="kontak" class="contact-footer">
            <div><p class="kicker">Hubungi sekolah</p><h2>Mari terhubung.</h2></div>
            <div class="contact-list"><p v-if="school.address">{{ school.address }}</p><a v-if="school.phone" :href="`tel:${school.phone}`">{{ school.phone }}</a><a v-if="school.email" :href="`mailto:${school.email}`">{{ school.email }}</a><a v-if="school.website" :href="school.website" target="_blank" rel="noreferrer">{{ school.website }}</a></div>
        </footer>
    </main>
</template>

<style scoped>
:global(body){margin:0;background:#f4f0e7}.school-profile{--ink:#173044;--muted:#6d7c82;--paper:#f4f0e7;--deep:#123247;--gold:#d4a74b;min-height:100vh;color:var(--ink);background:var(--paper);font-family:Inter,ui-sans-serif,system-ui,sans-serif}.site-header,.hero,.content-section,.fact-strip,.contact-footer{width:min(1180px,calc(100% - 3rem));margin-inline:auto}.site-header{display:flex;align-items:center;justify-content:space-between;gap:2rem;padding:1.4rem 0}.brand{display:flex;align-items:center;gap:.7rem;color:var(--ink);font-size:.95rem;font-weight:800;text-decoration:none}.brand-mark{display:grid;place-items:center;width:2.4rem;height:2.4rem;overflow:hidden;color:var(--deep);background:var(--gold);border-radius:.75rem .75rem .75rem .2rem;font-family:Georgia,serif;font-size:1.2rem}.brand-mark img{width:100%;height:100%;object-fit:contain;background:#fff}.site-nav{display:flex;align-items:center;gap:1.2rem;font-size:.78rem}.site-nav a{color:var(--muted);text-decoration:none}.site-nav a:hover,.site-nav a:focus-visible{color:var(--ink)}.site-nav .login-link{padding:.65rem .9rem;color:var(--paper);background:var(--deep);border-radius:.45rem;font-weight:750}.hero{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);gap:4rem;align-items:center;padding:5rem 0 4.5rem}.kicker{margin:0;color:#9a7329;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.hero h1{max-width:720px;margin:1rem 0 1.4rem;font-family:Georgia,serif;font-size:clamp(3rem,6vw,6.4rem);font-weight:400;letter-spacing:-.065em;line-height:.98}.hero h1 em{color:#a2772b;font-style:normal}.lead{max-width:560px;margin:0;color:var(--muted);font-size:1.04rem;line-height:1.8}.hero-actions{display:flex;align-items:center;gap:1.2rem;margin-top:2rem}.primary-button{padding:.85rem 1.05rem;color:var(--paper);background:var(--deep);border-radius:.45rem;font-size:.82rem;font-weight:750;text-decoration:none}.quiet-link{color:#9a7329;font-size:.82rem;font-weight:750;text-decoration:none}.quiet-link:hover,.quiet-link:focus-visible{text-decoration:underline}.hero-visual{position:relative;min-height:450px;overflow:hidden;background:var(--deep);border-radius:12rem 1.4rem 1.4rem 1.4rem}.hero-visual:after{position:absolute;right:-8%;bottom:-16%;width:270px;height:270px;border:1px solid #d4a74b80;border-radius:50%;content:""}.orbit{position:absolute;border:1px solid #d4a74b55;border-radius:50%;transform:rotate(-24deg)}.orbit-one{inset:12% -25% 34% 8%}.orbit-two{inset:30% 8% 10% -22%}.hero-emblem{position:absolute;top:20%;left:17%;display:grid;place-items:center;width:145px;height:145px;overflow:hidden;color:var(--deep);background:var(--gold);border-radius:50%;font-family:Georgia,serif;font-size:4.5rem}.hero-emblem img{width:100%;height:100%;object-fit:contain;background:#fff}.hero-note{position:absolute;right:12%;bottom:14%;display:grid;gap:.35rem;max-width:220px;color:#f5efe2}.hero-note span{color:var(--gold);font-size:.73rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.hero-note strong{font-family:Georgia,serif;font-size:1.9rem;font-weight:400;line-height:1.05}.hero-note small{color:#b4c4c9;font-size:.76rem}.fact-strip{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid #cfc8ba;border-bottom:1px solid #cfc8ba}.fact-strip div{display:grid;gap:.3rem;padding:1.15rem 1rem;border-right:1px solid #cfc8ba}.fact-strip div:first-child{padding-left:0}.fact-strip div:last-child{border-right:0}.fact-strip span{color:var(--muted);font-size:.7rem}.fact-strip strong{font-size:.9rem}.content-section{padding:6rem 0}.two-column{display:grid;grid-template-columns:.75fr 1.25fr;gap:5rem}.two-column h2,.section-heading h2,.contact-footer h2{max-width:420px;margin:.7rem 0 0;font-family:Georgia,serif;font-size:clamp(2rem,3.8vw,3.7rem);font-weight:400;letter-spacing:-.05em;line-height:1.03}.about-copy>p:first-child{margin:0;font-family:Georgia,serif;font-size:clamp(1.55rem,2.5vw,2.35rem);line-height:1.25}.muted{margin-top:1.5rem;color:var(--muted);font-size:.8rem}.message-section{display:grid;grid-template-columns:110px 1fr;gap:2rem;align-items:start;width:min(1020px,calc(100% - 3rem));margin:0 auto;padding:3.5rem 4rem;color:#eef1e8;background:var(--deep);border-radius:1rem}.quote-mark{color:var(--gold);font-family:Georgia,serif;font-size:8rem;line-height:.55}.message-section blockquote{max-width:760px;margin:1rem 0 1.2rem;font-family:Georgia,serif;font-size:clamp(1.8rem,3.4vw,3.3rem);font-weight:400;letter-spacing:-.04em;line-height:1.12}.message-section cite{color:#b8c6c7;font-size:.8rem;font-style:normal}.direction-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.3rem}.direction-grid article{padding:1.5rem 0;border-top:2px solid var(--gold)}.direction-grid article>span{color:#9a7329;font-size:.75rem;font-weight:800}.direction-grid p,.direction-grid li{color:var(--muted);font-size:1rem;line-height:1.75}.direction-grid ul{padding-left:1.1rem}.section-heading{display:flex;align-items:end;justify-content:space-between;gap:2rem;margin-bottom:2rem}.news-list{border-top:1px solid #cfc8ba}.news-row{display:grid;grid-template-columns:120px minmax(0,1fr) auto;gap:1.2rem;align-items:center;padding:1rem 0;color:inherit;border-bottom:1px solid #cfc8ba;text-decoration:none}.news-image{display:grid;place-items:center;width:120px;height:88px;overflow:hidden;color:var(--gold);background:var(--deep);font-family:Georgia,serif;font-size:2rem}.news-image img{width:100%;height:100%;object-fit:cover}.news-row small{color:var(--muted);font-size:.7rem}.news-row h3{margin:.35rem 0;font-family:Georgia,serif;font-size:1.45rem;font-weight:400}.news-row p{margin:0;color:var(--muted);font-size:.82rem;line-height:1.5}.news-arrow{color:#9a7329;font-size:1.4rem}.news-row:hover h3,.news-row:focus-visible h3{color:#9a7329}.empty-news{padding:2rem 0;color:var(--muted)}.contact-footer{display:grid;grid-template-columns:1fr 1fr;gap:3rem;padding:4rem 0 5rem;border-top:1px solid #cfc8ba}.contact-list{display:grid;align-content:start;gap:.45rem;color:var(--muted);font-size:.9rem;line-height:1.6}.contact-list p{margin:0}.contact-list a{color:inherit;text-decoration:none}.contact-list a:hover,.contact-list a:focus-visible{color:var(--ink);text-decoration:underline}.site-nav a:focus-visible,.primary-button:focus-visible,.quiet-link:focus-visible,.news-row:focus-visible,.brand:focus-visible,.contact-list a:focus-visible{outline:3px solid #c6953e;outline-offset:4px;border-radius:.2rem}@media(max-width:800px){.site-header{align-items:flex-start}.site-nav{flex-wrap:wrap;justify-content:flex-end}.hero{grid-template-columns:1fr;gap:2.5rem;padding-top:3.5rem}.hero-visual{min-height:350px}.two-column{grid-template-columns:1fr;gap:2rem}.message-section{padding:2.5rem 2rem}.fact-strip{grid-template-columns:repeat(2,1fr)}.fact-strip div:nth-child(2){border-right:0}.fact-strip div:nth-child(-n+2){border-bottom:1px solid #cfc8ba}.fact-strip div:first-child{padding-left:1rem}}@media(max-width:560px){.site-header,.hero,.content-section,.fact-strip,.contact-footer,.message-section{width:calc(100% - 2rem)}.site-header{display:grid;gap:1rem}.site-nav{justify-content:flex-start;gap:.8rem}.site-nav a:nth-child(-n+3){display:none}.hero h1{font-size:3.2rem}.hero-actions{align-items:flex-start;flex-direction:column;gap:1rem}.hero-visual{min-height:320px}.hero-emblem{left:10%;width:110px;height:110px;font-size:3.5rem}.hero-note{right:8%;bottom:12%;max-width:175px}.hero-note strong{font-size:1.55rem}.message-section{grid-template-columns:1fr;gap:1rem;padding:2rem 1.2rem}.quote-mark{font-size:5rem}.direction-grid,.contact-footer{grid-template-columns:1fr}.section-heading{align-items:flex-start;flex-direction:column}.news-row{grid-template-columns:72px minmax(0,1fr) auto;gap:.7rem}.news-image{width:72px;height:70px}.news-row h3{font-size:1.05rem}.news-row p{display:none}.news-arrow{font-size:1.1rem}}
</style>
