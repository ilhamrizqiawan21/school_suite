<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface GalleryPhoto {
    id: number;
    name?: string | null;
    url: string;
}

interface GalleryAlbum {
    id: number;
    title: string;
    caption?: string | null;
    photos: GalleryPhoto[];
}

defineProps<{
    school: { name: string; logo_url?: string | null };
    albums: GalleryAlbum[];
}>();
</script>

<template>
    <Head :title="`Galeri — ${school.name}`" />
    <main class="public-gallery">
        <header class="site-header">
            <Link href="/profil-sekolah" class="brand" aria-label="Kembali ke profil sekolah"><span class="brand-mark"><img v-if="school.logo_url" :src="school.logo_url" :alt="`Logo ${school.name}`" /><span v-else>{{ school.name.slice(0, 1) }}</span></span><span>{{ school.name }}</span></Link>
            <nav aria-label="Navigasi publik"><Link href="/profil-sekolah">Profil sekolah</Link><Link href="/berita">Berita sekolah</Link><Link href="/login" class="login-link">Masuk</Link></nav>
        </header>

        <section class="gallery-intro"><p class="kicker">Portofolio sekolah</p><h1>Ruang untuk momen yang berarti.</h1><p>Lihat kegiatan, ruang belajar, dan cerita kecil yang membentuk keseharian {{ school.name }}.</p></section>

        <section class="album-grid" aria-label="Galeri foto sekolah">
            <article v-for="album in albums" :key="album.id" class="album-card"><div class="album-images"><img v-for="photo in album.photos" :key="photo.id" :src="photo.url" :alt="photo.name || album.title" /></div><div class="album-copy"><h2>{{ album.title }}</h2><p v-if="album.caption">{{ album.caption }}</p><small>{{ album.photos.length }} foto</small></div></article>
            <div v-if="!albums.length" class="empty-gallery"><strong>Galeri sedang disiapkan.</strong><p>Foto kegiatan dan suasana sekolah akan tampil di sini.</p></div>
        </section>

        <footer><Link href="/profil-sekolah" class="back-link">← Kembali ke profil sekolah</Link></footer>
    </main>
</template>

<style scoped>
:global(body){margin:0;background:#f4f0e7}.public-gallery{min-height:100vh;padding:1.5rem clamp(1rem,4vw,4rem);color:#173044;background:#f4f0e7;font-family:Inter,ui-sans-serif,system-ui,sans-serif}.site-header{display:flex;align-items:center;justify-content:space-between;gap:2rem;width:min(1180px,100%);margin:0 auto}.brand{display:flex;align-items:center;gap:.7rem;color:#173044;font-size:.95rem;font-weight:800;text-decoration:none}.brand-mark{display:grid;place-items:center;width:2.4rem;height:2.4rem;overflow:hidden;color:#123247;background:#d4a74b;border-radius:.75rem .75rem .75rem .2rem;font-family:Georgia,serif;font-size:1.2rem}.brand-mark img{width:100%;height:100%;object-fit:contain;background:#fff}.site-header nav{display:flex;align-items:center;gap:1.1rem;font-size:.78rem}.site-header nav a{color:#6d7c82;text-decoration:none}.site-header nav a:hover,.site-header nav a:focus-visible{color:#173044}.site-header nav .login-link{padding:.65rem .9rem;color:#f4f0e7;background:#123247;border-radius:.45rem;font-weight:750}.gallery-intro{width:min(760px,100%);margin:6rem auto 3.5rem;text-align:center}.kicker{margin:0;color:#9a7329;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.gallery-intro h1{margin:.9rem 0 1rem;font-family:Georgia,serif;font-size:clamp(2.8rem,6vw,5.8rem);font-weight:400;letter-spacing:-.065em;line-height:.98}.gallery-intro>p:last-child{max-width:560px;margin:0 auto;color:#6d7c82;font-size:1rem;line-height:1.8}.album-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.2rem;width:min(1180px,100%);margin:0 auto}.album-card{overflow:hidden;background:#fff;border-radius:.9rem;box-shadow:0 8px 30px #183b5d0d}.album-card:nth-child(3n+2){transform:translateY(2.2rem)}.album-images{display:grid;grid-template-columns:repeat(2,1fr);grid-template-rows:repeat(2,1fr);gap:2px;height:250px;background:#f4f0e7}.album-images img{display:block;width:100%;height:100%;object-fit:cover}.album-copy{display:grid;gap:.35rem;padding:1rem 1.1rem}.album-copy h2{margin:0;font-family:Georgia,serif;font-size:1.15rem;font-weight:400}.album-copy p{margin:0;color:#6d7c82;font-size:.78rem;line-height:1.5}.album-copy small{color:#9a7329;font-size:.7rem;font-weight:800}.empty-gallery{grid-column:1/-1;padding:5rem 1rem;text-align:center;color:#6d7c82}.empty-gallery strong{display:block;color:#173044;font-family:Georgia,serif;font-size:1.5rem;font-weight:400}.empty-gallery p{margin:.5rem 0 0}footer{width:min(1180px,100%);margin:6rem auto 2rem;padding-top:1.4rem;border-top:1px solid #cfc8ba}.back-link{color:#9a7329;font-size:.8rem;font-weight:750;text-decoration:none}.back-link:hover{text-decoration:underline}@media(max-width:800px){.album-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.album-card:nth-child(3n+2){transform:none}.album-card:nth-child(2n){transform:translateY(1.5rem)}}@media(max-width:560px){.site-header{align-items:flex-start;flex-direction:column;gap:1rem}.site-header nav{flex-wrap:wrap}.gallery-intro{margin-top:4rem}.album-grid{grid-template-columns:1fr}.album-card:nth-child(2n){transform:none}.album-images{height:220px}footer{margin-top:4rem}}
</style>
