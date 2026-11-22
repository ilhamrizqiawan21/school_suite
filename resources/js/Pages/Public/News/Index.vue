<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import { Link } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { computed } from 'vue';
import { publicThemeStyle } from '@/composables/publicTheme';

interface Photo { id: number; url: string; name?: string | null }
interface NewsItem { id: number; title: string; slug: string; excerpt?: string | null; published_at?: string | null; photos: Photo[] }
const props = defineProps<{ school: { name: string; about?: string | null; logo_url?: string | null; theme?: { primary?: string | null } }; news: NewsItem[] }>();
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(value)) : '';
const publicText = usePublicContentText();
</script>

<template>
    <Head :title="`Berita — ${school.name}`" />
    <main class="public-site public-news" :style="themeStyle"><div class="identity-watermark" aria-hidden="true"><span>{{ school.name.slice(0, 1) }}</span></div><PublicHeader :school="school" active="news" /><section class="intro"><p>{{ publicText("copy.vue_news_index", "text_001", "Informasi terbaru sekolah") }}</p><h1>{{ publicText("copy.vue_news_index", "text_002", "Berita & kegiatan") }}</h1><span>{{ school.about || 'Ikuti kabar terbaru dan kegiatan sekolah.' }}</span></section><section class="news-grid"><Link v-for="item in news" :key="item.id" :href="`/berita/${item.slug}`" class="news-card"><div class="card-image"><img v-if="item.photos[0]" :src="item.photos[0].url" :alt="item.title" /><span v-else>{{ publicText("copy.vue_news_index", "text_003", "Berita sekolah") }}</span></div><div class="card-body"><small>{{ formatDate(item.published_at) }}</small><h2>{{ item.title }}</h2><p>{{ item.excerpt || 'Baca informasi lengkap dari sekolah.' }}</p><span class="read-more">{{ publicText("copy.vue_news_index", "text_004", "Baca berita →") }}</span></div></Link><div v-if="!news.length" class="empty"><strong>{{ publicText("copy.vue_news_index", "text_005", "Belum ada berita terbit.") }}</strong><p>{{ publicText("copy.vue_news_index", "text_006", "Informasi sekolah akan tampil di halaman ini.") }}</p></div></section></main>
</template>

<style scoped>
.public-news{min-height:100vh;padding:1.5rem clamp(1rem,4vw,4rem);color:#182838;background:#f5f8fb}.public-news .public-site-header{max-width:1180px;margin:0 auto}.public-news .public-nav,.article-page .public-nav{display:flex;align-items:center;gap:1rem}.public-news .public-nav a,.article-page .public-nav a{color:#1d5fa7;font-size:var(--type-meta);font-weight:var(--weight-bold);text-decoration:none}.brand{font-weight:var(--weight-bold);color:#1d477d}.login-link,.read-more{color:#1d5fa7;font-size:var(--type-sm);font-weight:var(--weight-bold)}.intro{max-width:1180px;margin:5rem auto 2rem}.intro p{margin:0;color:#1d5fa7;font-size:var(--type-meta);font-weight:var(--weight-bold);letter-spacing:.08em;text-transform:uppercase}.intro h1{margin:.5rem 0;font-size:clamp(2rem,5vw,4rem);letter-spacing:-.05em}.intro span{display:block;max-width:650px;color:#607286;line-height:1.7}.news-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.25rem;max-width:1180px;margin:0 auto}.news-card{overflow:hidden;color:inherit;border:1px solid #dbe5ee;border-radius:1rem;background:#fff;box-shadow:0 8px 30px #183b5d0d}.card-image{height:190px;display:grid;place-items:center;background:#e7f0f9;color:#3974aa;font-size:var(--type-sm)}.card-image img{width:100%;height:100%;object-fit:cover}.card-body{padding:1.1rem}.card-body small{color:#75879a;font-size:var(--type-meta)}.card-body h2{margin:.45rem 0;font-size:1.1rem;line-height:1.35}.card-body p{min-height:2.8rem;color:#607286;font-size:var(--type-sm);line-height:1.6}.read-more{display:block;margin-top:1rem}.empty{grid-column:1/-1;padding:4rem;text-align:center;color:#607286}@media(max-width:850px){.news-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.intro{margin-top:3rem}.news-grid{grid-template-columns:1fr}}
</style>

<style scoped>
:global(body) { margin: 0; background: #f2f6f9; }
.public-news { --ink: #173044; --muted: #6d7c82; --paper: #f5f8fb; --deep: #123247; min-height: 100vh; color: var(--ink); background: var(--paper); }
.public-news .public-site-header { padding: 1.4rem 0 0; }
.brand { color: var(--ink); font-family: var(--font-display); font-size: 1.05rem; text-decoration: none; }
.public-news .public-nav a { color: var(--muted); }
.public-news .public-nav a:hover, .public-news .public-nav a:focus-visible { color: var(--ink); }
.public-news .login-link { padding: .65rem .9rem; color: var(--paper); background: var(--deep); border-radius: .45rem; }
.intro { margin-top: 6rem; }
.intro p { color: #9a7329; }
.intro h1 { font-family: var(--font-ui); font-weight: var(--weight-regular); }
.intro span { color: var(--muted); }
.news-card { border-color: #ded7c9; border-radius: .8rem; box-shadow: 0 12px 30px #183b5d0a; }
.card-image { background: var(--deep); color: #d4a74b; }
.card-image img { display: block; width: 100%; height: 190px; max-height: 190px; object-fit: cover; }
.card-body h2 { font-family: var(--font-ui); font-weight: var(--weight-regular); }
.card-body small, .card-body p { color: var(--muted); }
.read-more { color: #9a7329; }
.empty { color: var(--muted); }
.public-news a:focus-visible { outline: 3px solid #c6953e; outline-offset: 4px; border-radius: .2rem; }
@media (max-width: 560px) { .intro { margin-top: 4rem; } }
</style>
