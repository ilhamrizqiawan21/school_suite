<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PublicSectionLayout from '@/components/PublicSectionLayout.vue';

interface School { name: string; logo_url?: string | null }
interface NewsItem { id: number; title: string; slug: string; excerpt?: string | null }
interface GalleryItem { id: number; title?: string | null; caption?: string | null; url: string }

defineProps<{ school: School; news: NewsItem[]; gallery: GalleryItem[] }>();
</script>

<template>
    <PublicSectionLayout
        :school="school"
        title="Kesiswaan dan prestasi"
        eyebrow="Kehidupan sekolah"
        :description="`Temukan kegiatan, organisasi, dan cerita prestasi yang membentuk pengalaman siswa di ${school.name}.`"
        active="student-life"
    >
        <section class="section-content" aria-label="Informasi kesiswaan dan prestasi">
            <div class="life-grid">
                <article class="life-card life-card-feature">
                    <span class="card-number">01</span>
                    <h2>Organisasi sekolah</h2>
                    <p>Kenali pengelola dan struktur organisasi yang mendukung kegiatan belajar serta kehidupan sekolah.</p>
                    <Link href="/struktur-organisasi" class="card-link">Lihat struktur →</Link>
                </article>
                <article class="life-card">
                    <span class="card-number">02</span>
                    <h2>Kegiatan siswa</h2>
                    <p>{{ news.length ? 'Kabar kegiatan siswa tersedia di halaman berita sekolah.' : 'Kabar kegiatan siswa akan tampil setelah dipublikasikan.' }}</p>
                    <Link href="/berita" class="card-link">Baca berita →</Link>
                </article>
                <article class="life-card">
                    <span class="card-number">03</span>
                    <h2>Prestasi dan dokumentasi</h2>
                    <p>{{ gallery.length ? 'Lihat dokumentasi kegiatan dan momen sekolah di galeri.' : 'Dokumentasi prestasi dan kegiatan akan tampil di galeri.' }}</p>
                    <Link href="/galeri" class="card-link">Buka galeri →</Link>
                </article>
            </div>
        </section>
    </PublicSectionLayout>
</template>

<style scoped>
.section-content { padding-bottom: 1rem; }
.life-grid { display: grid; grid-template-columns: 1.25fr 1fr 1fr; gap: 1rem; }
.life-card { display: grid; align-content: start; gap: .7rem; min-height: 230px; padding: 1.35rem; background: #fffdf8; border: 1px solid #ded7c9; border-radius: .8rem; }
.life-card-feature { color: #f5efe2; background: #123247; border-color: #123247; }.life-card-feature p { color: #c1d0d2; }.life-card-feature .card-number, .life-card-feature .card-link { color: #d4a74b; }
.card-number { color: #9a7329; font-size: .75rem; font-weight: 800; letter-spacing: .08em; }.life-card h2 { margin: 0; font-family: var(--font-display); font-size: 1.6rem; font-weight: 400; }.life-card p { margin: 0; color: #526673; font-size: .9rem; line-height: 1.65; }.card-link { align-self: end; margin-top: auto; color: #9a7329; font-size: .85rem; font-weight: 800; text-decoration: none; }
@media (max-width: 800px) { .life-grid { grid-template-columns: 1fr 1fr; }.life-card-feature { grid-column: 1 / -1; } }
@media (max-width: 560px) { .life-grid { grid-template-columns: 1fr; }.life-card-feature { grid-column: auto; } }
</style>
