<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicHeader from '@/components/PublicHeader.vue';
import { computed } from 'vue';

interface Photo { id: number; url: string; name?: string | null }
interface NewsItem { id: number; title: string; slug: string; excerpt?: string | null; published_at?: string | null; photos: Photo[] }
const props = defineProps<{ school: { name: string; about?: string | null; logo_url?: string | null; theme?: { primary?: string | null } }; news: NewsItem[] }>();
const themeStyle = computed(() => ({ '--public-primary': props.school.theme?.primary || '#3157a4' }));
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(value)) : '';
</script>

<template>
    <Head :title="`Berita — ${school.name}`" />
    <main class="public-site public-news" :style="themeStyle"><div class="identity-watermark" aria-hidden="true"><span>{{ school.name.slice(0, 1) }}</span></div><PublicHeader :school="school" active="news" /><section class="intro"><p>Informasi terbaru sekolah</p><h1>Berita & kegiatan</h1><span>{{ school.about || 'Ikuti kabar terbaru dan kegiatan sekolah.' }}</span></section><section class="news-grid"><Link v-for="item in news" :key="item.id" :href="`/berita/${item.slug}`" class="news-card"><div class="card-image"><img v-if="item.photos[0]" :src="item.photos[0].url" :alt="item.title" /><span v-else>Berita sekolah</span></div><div class="card-body"><small>{{ formatDate(item.published_at) }}</small><h2>{{ item.title }}</h2><p>{{ item.excerpt || 'Baca informasi lengkap dari sekolah.' }}</p><span class="read-more">Baca berita →</span></div></Link><div v-if="!news.length" class="empty"><strong>Belum ada berita terbit.</strong><p>Informasi sekolah akan tampil di halaman ini.</p></div></section></main>
</template>

<style scoped>
.public-news{min-height:100vh;padding:1.5rem clamp(1rem,4vw,4rem);color:#182838;background:#f5f8fb}.public-news header{display:flex;align-items:center;justify-content:space-between;gap:1rem;max-width:1180px;margin:0 auto}.public-news nav,.article-page nav{display:flex;align-items:center;gap:1rem}.public-news nav a,.article-page nav a{color:#1d5fa7;font-size:.8rem;font-weight:700;text-decoration:none}.brand{font-weight:800;color:#1d477d}.login-link,.read-more{color:#1d5fa7;font-size:.85rem;font-weight:700}.intro{max-width:1180px;margin:5rem auto 2rem}.intro p{margin:0;color:#1d5fa7;font-size:.8rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.intro h1{margin:.5rem 0;font-size:clamp(2rem,5vw,4rem);letter-spacing:-.05em}.intro span{display:block;max-width:650px;color:#607286;line-height:1.7}.news-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.25rem;max-width:1180px;margin:0 auto}.news-card{overflow:hidden;color:inherit;border:1px solid #dbe5ee;border-radius:1rem;background:#fff;box-shadow:0 8px 30px #183b5d0d}.card-image{height:190px;display:grid;place-items:center;background:#e7f0f9;color:#3974aa;font-size:.85rem}.card-image img{width:100%;height:100%;object-fit:cover}.card-body{padding:1.1rem}.card-body small{color:#75879a;font-size:.72rem}.card-body h2{margin:.45rem 0;font-size:1.1rem;line-height:1.35}.card-body p{min-height:2.8rem;color:#607286;font-size:.85rem;line-height:1.6}.read-more{display:block;margin-top:1rem}.empty{grid-column:1/-1;padding:4rem;text-align:center;color:#607286}@media(max-width:850px){.news-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.public-news header{align-items:flex-start;flex-direction:column}.public-news nav{flex-wrap:wrap}.intro{margin-top:3rem}.news-grid{grid-template-columns:1fr}}
</style>

<style scoped>
:global(body) { margin: 0; background: #f4f0e7; }
.public-news { --ink: #173044; --muted: #6d7c82; --paper: #f4f0e7; --deep: #123247; min-height: 100vh; color: var(--ink); background: var(--paper); }
.public-news header { padding: 1.4rem 0; }
.brand { color: var(--ink); font-family: var(--font-display); font-size: 1.05rem; text-decoration: none; }
.public-news nav a { color: var(--muted); }
.public-news nav a:hover, .public-news nav a:focus-visible { color: var(--ink); }
.public-news .login-link { padding: .65rem .9rem; color: var(--paper); background: var(--deep); border-radius: .45rem; }
.intro { margin-top: 6rem; }
.intro p { color: #9a7329; }
.intro h1 { font-family: var(--font-display); font-weight: 400; }
.intro span { color: var(--muted); }
.news-card { border-color: #ded7c9; border-radius: .8rem; box-shadow: 0 12px 30px #183b5d0a; }
.card-image { background: var(--deep); color: #d4a74b; }
.card-image img { display: block; width: 100%; height: 190px; max-height: 190px; object-fit: cover; }
.card-body h2 { font-family: var(--font-display); font-weight: 400; }
.card-body small, .card-body p { color: var(--muted); }
.read-more { color: #9a7329; }
.empty { color: var(--muted); }
.public-news a:focus-visible { outline: 3px solid #c6953e; outline-offset: 4px; border-radius: .2rem; }
@media (max-width: 560px) { .intro { margin-top: 4rem; } }
</style>
