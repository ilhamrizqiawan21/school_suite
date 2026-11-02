<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicHeader from '@/components/PublicHeader.vue';
import { computed } from 'vue';
import { publicThemeStyle } from '@/composables/publicTheme';
interface Photo { id: number; url: string; name?: string | null; caption?: string | null }
const props = defineProps<{ school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } }; news: { title: string; body: string; published_at?: string | null; photos: Photo[] } }>();
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(value)) : '';
</script>

<template>
    <Head :title="`${news.title} — ${school.name}`" />
    <main class="public-site article-page" :style="themeStyle"><div class="identity-watermark" aria-hidden="true"><span>{{ school.name.slice(0, 1) }}</span></div><PublicHeader :school="school" active="news" /><article><Link href="/berita" class="back">← Kembali ke berita</Link><small>{{ formatDate(news.published_at) }}</small><h1>{{ news.title }}</h1><div class="article-body">{{ news.body }}</div><div v-if="news.photos.length" class="article-gallery"><figure v-for="photo in news.photos" :key="photo.id"><img :src="photo.url" :alt="photo.caption || photo.name || news.title" /><figcaption v-if="photo.caption">{{ photo.caption }}</figcaption></figure></div></article></main>
</template>

<style scoped>
.article-page{min-height:100vh;padding:1.5rem clamp(1rem,4vw,4rem);color:#182838;background:#f5f8fb}.article-page .public-site-header{max-width:900px;margin:0 auto}.brand,.back,.login-link{color:#1d5fa7;font-weight:700}.article-page article{max-width:900px;margin:5rem auto 3rem}.article-page article>small{display:block;margin-top:2rem;color:#75879a}.article-page h1{max-width:760px;margin:.5rem 0 2rem;font-size:clamp(2rem,5vw,4rem);line-height:1.08;letter-spacing:-.05em}.article-body{white-space:pre-line;color:#465d70;font-size:1.05rem;line-height:1.85}.article-gallery{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin-top:2.5rem}.article-gallery figure{margin:0}.article-gallery img{display:block;width:100%;max-height:420px;object-fit:cover;border-radius:1rem}.article-gallery figcaption{margin-top:.4rem;color:#75879a;font-size:.8rem}@media(max-width:560px){.article-page article{margin-top:3rem}.article-gallery{grid-template-columns:1fr}}
</style>

<style scoped>
:global(body) { margin: 0; background: #f2f6f9; }
.article-page { --ink: #173044; --muted: #6d7c82; --paper: #f5f8fb; min-height: 100vh; color: var(--ink); background: var(--paper); }
.article-page .public-site-header { padding: 1.4rem 0 0; }
.brand { color: var(--ink); font-family: var(--font-display); font-size: 1.05rem; text-decoration: none; }
.article-page .public-nav a { color: var(--muted); }
.article-page .public-nav a:hover, .article-page .public-nav a:focus-visible { color: var(--ink); }
.article-page .login-link { padding: .65rem .9rem; color: var(--paper); background: #123247; border-radius: .45rem; }
.article-page article { margin-top: 6rem; }
.article-page .back { color: #9a7329; text-decoration: none; }
.article-page article > small { color: var(--muted); }
.article-page h1 { font-family: var(--font-display); font-weight: 400; }
.article-body { color: var(--muted); }
.article-gallery figcaption { color: var(--muted); }
.article-page a:focus-visible { outline: 3px solid #c6953e; outline-offset: 4px; border-radius: .2rem; }
</style>
