<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface GalleryPhoto {
    id: number;
    title?: string | null;
    caption?: string | null;
    name?: string | null;
    url: string;
}

defineProps<{
    school: { name: string; logo_url?: string | null };
    photos: GalleryPhoto[];
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

        <section class="gallery-grid" aria-label="Galeri foto sekolah">
            <figure v-for="photo in photos" :key="photo.id" class="gallery-item"><img :src="photo.url" :alt="photo.title || photo.caption || photo.name || 'Foto sekolah'" /><figcaption><strong v-if="photo.title">{{ photo.title }}</strong><span v-if="photo.caption">{{ photo.caption }}</span></figcaption></figure>
            <div v-if="!photos.length" class="empty-gallery"><strong>Galeri sedang disiapkan.</strong><p>Foto kegiatan dan suasana sekolah akan tampil di sini.</p></div>
        </section>

        <footer><Link href="/profil-sekolah" class="back-link">← Kembali ke profil sekolah</Link></footer>
    </main>
</template>

<style scoped>
:global(body){margin:0;background:#f4f0e7}.public-gallery{min-height:100vh;padding:1.5rem clamp(1rem,4vw,4rem);color:#173044;background:#f4f0e7;font-family:Inter,ui-sans-serif,system-ui,sans-serif}.site-header{display:flex;align-items:center;justify-content:space-between;gap:2rem;width:min(1180px,100%);margin:0 auto}.brand{display:flex;align-items:center;gap:.7rem;color:#173044;font-size:.95rem;font-weight:800;text-decoration:none}.brand-mark{display:grid;place-items:center;width:2.4rem;height:2.4rem;overflow:hidden;color:#123247;background:#d4a74b;border-radius:.75rem .75rem .75rem .2rem;font-family:Georgia,serif;font-size:1.2rem}.brand-mark img{width:100%;height:100%;object-fit:contain;background:#fff}.site-header nav{display:flex;align-items:center;gap:1.1rem;font-size:.78rem}.site-header nav a{color:#6d7c82;text-decoration:none}.site-header nav a:hover,.site-header nav a:focus-visible{color:#173044}.site-header nav .login-link{padding:.65rem .9rem;color:#f4f0e7;background:#123247;border-radius:.45rem;font-weight:750}.gallery-intro{width:min(760px,100%);margin:6rem auto 3.5rem;text-align:center}.kicker{margin:0;color:#9a7329;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.gallery-intro h1{margin:.9rem 0 1rem;font-family:Georgia,serif;font-size:clamp(2.8rem,6vw,5.8rem);font-weight:400;letter-spacing:-.065em;line-height:.98}.gallery-intro>p:last-child{max-width:560px;margin:0 auto;color:#6d7c82;font-size:1rem;line-height:1.8}.gallery-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.2rem;width:min(1180px,100%);margin:0 auto}.gallery-item{margin:0;overflow:hidden;background:#fff;border-radius:.9rem;box-shadow:0 8px 30px #183b5d0d}.gallery-item:nth-child(3n+2){transform:translateY(2.2rem)}.gallery-item img{display:block;width:100%;height:250px;object-fit:cover}.gallery-item figcaption{display:grid;gap:.35rem;padding:1rem 1.1rem}.gallery-item strong{font-family:Georgia,serif;font-size:1.1rem;font-weight:400}.gallery-item span{color:#6d7c82;font-size:.78rem;line-height:1.5}.empty-gallery{grid-column:1/-1;padding:5rem 1rem;text-align:center;color:#6d7c82}.empty-gallery strong{display:block;color:#173044;font-family:Georgia,serif;font-size:1.5rem;font-weight:400}.empty-gallery p{margin:.5rem 0 0}footer{width:min(1180px,100%);margin:6rem auto 2rem;padding-top:1.4rem;border-top:1px solid #cfc8ba}.back-link{color:#9a7329;font-size:.8rem;font-weight:750;text-decoration:none}.back-link:hover{text-decoration:underline}@media(max-width:800px){.gallery-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.gallery-item:nth-child(3n+2){transform:none}.gallery-item:nth-child(2n){transform:translateY(1.5rem)}}@media(max-width:560px){.site-header{align-items:flex-start;flex-direction:column;gap:1rem}.site-header nav{flex-wrap:wrap}.gallery-intro{margin-top:4rem}.gallery-grid{grid-template-columns:1fr}.gallery-item:nth-child(2n){transform:none}.gallery-item img{height:220px}footer{margin-top:4rem}}
</style>
