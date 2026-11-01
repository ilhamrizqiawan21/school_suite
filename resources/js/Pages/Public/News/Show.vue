<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
interface Photo { id: number; url: string; name?: string | null; caption?: string | null }
defineProps<{ school: { name: string }; news: { title: string; body: string; published_at?: string | null; photos: Photo[] } }>();
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(value)) : '';
</script>

<template>
    <Head :title="`${news.title} — ${school.name}`" />
    <main class="article-page"><header><Link href="/profil-sekolah" class="brand">{{ school.name }}</Link><nav><Link href="/profil-sekolah">Profil sekolah</Link><Link href="/login" class="login-link">Masuk ke School Suite</Link></nav></header><article><Link href="/berita" class="back">← Kembali ke berita</Link><small>{{ formatDate(news.published_at) }}</small><h1>{{ news.title }}</h1><div class="article-body">{{ news.body }}</div><div v-if="news.photos.length" class="article-gallery"><figure v-for="photo in news.photos" :key="photo.id"><img :src="photo.url" :alt="photo.caption || photo.name || news.title" /><figcaption v-if="photo.caption">{{ photo.caption }}</figcaption></figure></div></article></main>
</template>

<style scoped>
.article-page{min-height:100vh;padding:1.5rem clamp(1rem,4vw,4rem);color:#182838;background:#f5f8fb}.article-page header{display:flex;align-items:center;justify-content:space-between;gap:1rem;max-width:900px;margin:0 auto}.brand,.back,.login-link{color:#1d5fa7;font-weight:700}.article-page article{max-width:900px;margin:5rem auto 3rem}.article-page article>small{display:block;margin-top:2rem;color:#75879a}.article-page h1{max-width:760px;margin:.5rem 0 2rem;font-size:clamp(2rem,5vw,4rem);line-height:1.08;letter-spacing:-.05em}.article-body{white-space:pre-line;color:#465d70;font-size:1.05rem;line-height:1.85}.article-gallery{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin-top:2.5rem}.article-gallery figure{margin:0}.article-gallery img{display:block;width:100%;max-height:420px;object-fit:cover;border-radius:1rem}.article-gallery figcaption{margin-top:.4rem;color:#75879a;font-size:.8rem}@media(max-width:560px){.article-page header{align-items:flex-start;flex-direction:column;gap:.75rem}.article-page nav{flex-wrap:wrap}.article-page article{margin-top:3rem}.article-gallery{grid-template-columns:1fr}}
</style>
