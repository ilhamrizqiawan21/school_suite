<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import { Link } from '@inertiajs/vue3';
import PublicSectionLayout from '@/components/PublicSectionLayout.vue';
import { computed } from 'vue';

interface School { name: string; logo_url?: string | null }
interface NewsItem { id: number; title: string; slug: string; excerpt?: string | null }
interface GalleryItem { id: number; title?: string | null; caption?: string | null; url: string }
interface StudentLifeItem { id?: string; title: string; description?: string; image_url?: string; link?: string }
interface ContentBlocks { 'school.student_life'?: { visible?: boolean; title?: string; subtitle?: string; show_student_names?: boolean; items?: StudentLifeItem[] } }

const props = defineProps<{ school: School; news: NewsItem[]; gallery: GalleryItem[]; contentBlocks?: ContentBlocks }>();
const studentLifeBlock = computed(() => props.contentBlocks?.['school.student_life']);
const studentLifeItems = computed(() => Array.isArray(studentLifeBlock.value?.items) ? studentLifeBlock.value.items.filter((item) => item?.title?.trim()) : []);
const safeLink = (value?: string) => {
    const link = (value || '').trim();
    return link.startsWith('/') || link.startsWith('https://') ? link : '';
};
const publicText = usePublicContentText();
</script>

<template>
    <PublicSectionLayout
        :school="school"
        :title="publicText('copy.vue_school_studentlife', 'title_011', 'Kesiswaan dan prestasi')"
        :eyebrow="publicText('copy.vue_school_studentlife', 'eyebrow_012', 'Kehidupan sekolah')"
        :description="`Temukan kegiatan, organisasi, dan cerita prestasi yang membentuk pengalaman siswa di ${school.name}.`"
        active="student-life"
    >
        <section class="section-content" :aria-label="publicText('copy.vue_school_studentlife', 'aria_label_013', 'Informasi kesiswaan dan prestasi')">
            <div class="life-grid">
                <article class="life-card life-card-feature">
                    <span class="card-number">01</span>
                    <h2>{{ publicText("copy.vue_school_studentlife", "text_001", "Organisasi sekolah") }}</h2>
                    <p>{{ publicText("copy.vue_school_studentlife", "text_002", "Kenali pengelola dan struktur organisasi yang mendukung kegiatan belajar serta kehidupan sekolah.") }}</p>
                    <Link href="/struktur-organisasi" class="card-link">{{ publicText("copy.vue_school_studentlife", "text_003", "Lihat struktur →") }}</Link>
                </article>
                <article class="life-card">
                    <span class="card-number">02</span>
                    <h2>{{ publicText("copy.vue_school_studentlife", "text_004", "Kegiatan siswa") }}</h2>
                    <p>{{ news.length ? 'Kabar kegiatan siswa tersedia di halaman berita sekolah.' : 'Kabar kegiatan siswa akan tampil setelah dipublikasikan.' }}</p>
                    <Link href="/berita" class="card-link">{{ publicText("copy.vue_school_studentlife", "text_005", "Baca berita →") }}</Link>
                </article>
                <article class="life-card">
                    <span class="card-number">03</span>
                    <h2>{{ publicText("copy.vue_school_studentlife", "text_006", "Prestasi dan dokumentasi") }}</h2>
                    <p>{{ gallery.length ? 'Lihat dokumentasi kegiatan dan momen sekolah di galeri.' : 'Dokumentasi prestasi dan kegiatan akan tampil di galeri.' }}</p>
                    <Link href="/galeri" class="card-link">{{ publicText("copy.vue_school_studentlife", "text_007", "Buka galeri →") }}</Link>
                </article>
            </div>
            <section v-if="studentLifeBlock?.visible !== false && studentLifeItems.length" class="student-life-list" aria-labelledby="student-life-list-title">
                <div class="life-gallery-heading"><div><span>{{ studentLifeBlock?.title || 'Kehidupan siswa' }}</span><h2 id="student-life-list-title">{{ studentLifeBlock?.subtitle || 'Kegiatan dan pembinaan siswa' }}</h2></div></div>
                <div class="student-life-grid">
                    <article v-for="(item, index) in studentLifeItems" :key="item.id || `${item.title}-${index}`" class="student-life-card">
                        <img v-if="item.image_url" :src="item.image_url" :alt="item.title" loading="lazy" />
                        <div><h3>{{ item.title }}</h3><p v-if="item.description">{{ item.description }}</p><a v-if="safeLink(item.link)" :href="safeLink(item.link)" class="card-link">Selengkapnya →</a></div>
                    </article>
                </div>
            </section>
            <section v-if="gallery.length" class="life-gallery" aria-labelledby="life-gallery-title">
                <div class="life-gallery-heading"><div><span>{{ publicText("copy.vue_school_studentlife", "text_008", "Kehidupan di sekolah") }}</span><h2 id="life-gallery-title">{{ publicText("copy.vue_school_studentlife", "text_009", "Momen dari komunitas sekolah.") }}</h2></div><Link href="/galeri">{{ publicText("copy.vue_school_studentlife", "text_010", "Lihat semua foto →") }}</Link></div>
                <div class="life-gallery-grid"><figure v-for="photo in gallery.slice(0, 3)" :key="photo.id"><img :src="photo.url" :alt="photo.title || photo.caption || 'Kegiatan sekolah'" loading="lazy" /><figcaption>{{ photo.title || photo.caption || 'Kegiatan sekolah' }}</figcaption></figure></div>
            </section>
        </section>
    </PublicSectionLayout>
</template>

<style scoped>
.section-content { padding-bottom: 1rem; }
.life-grid { display: grid; grid-template-columns: 1.25fr 1fr 1fr; gap: 1rem; }
.life-card { display: grid; align-content: start; gap: .7rem; min-height: 230px; padding: 1.35rem; background: #fffdf8; border: 1px solid #ded7c9; border-radius: .8rem; }
.life-card-feature { color: #f5efe2; background: #123247; border-color: #123247; }.life-card-feature p { color: #c1d0d2; }.life-card-feature .card-number, .life-card-feature .card-link { color: #d4a74b; }
.card-number { color: #9a7329; font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .08em; }.life-card h2 { margin: 0; font-family: var(--font-ui); font-size: 1.6rem; font-weight: var(--weight-regular); }.life-card p { margin: 0; color: #526673; font-size: var(--type-md); line-height: 1.65; }.card-link { align-self: end; margin-top: auto; color: #9a7329; font-size: var(--type-sm); font-weight: var(--weight-bold); text-decoration: none; }
@media (max-width: 800px) { .life-grid { grid-template-columns: 1fr 1fr; }.life-card-feature { grid-column: 1 / -1; } }
@media (max-width: 560px) { .life-grid { grid-template-columns: 1fr; }.life-card-feature { grid-column: auto; } }
.life-gallery { margin-top: clamp(2.5rem, 6vw, 5rem); }
.student-life-list { margin-top: clamp(2.5rem, 6vw, 5rem); }.student-life-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }.student-life-card { overflow: hidden; background: #fffdf8; border: 1px solid #ded7c9; border-radius: .8rem; }.student-life-card img { width: 100%; aspect-ratio: 1.7; object-fit: cover; }.student-life-card > div { display: grid; gap: .5rem; padding: 1rem; }.student-life-card h3 { margin: 0; font-family: var(--font-ui); font-size: 1.25rem; font-weight: var(--weight-regular); }.student-life-card p { margin: 0; color: #526673; font-size: var(--type-sm); line-height: 1.55; }.student-life-card .card-link { margin-top: .25rem; }
.life-gallery-heading { display: flex; justify-content: space-between; align-items: end; gap: 1rem; margin-bottom: 1.5rem; }
.life-gallery-heading span { color: var(--public-primary); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.life-gallery-heading h2 { margin: .5rem 0 0; color: var(--public-ink); font-size: clamp(1.5rem, 2.6vw, 2.4rem); letter-spacing: -.035em; }
.life-gallery-heading a { color: var(--public-primary); font-size: var(--type-sm); font-weight: var(--weight-bold); }
.life-gallery-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.2rem; }
.life-gallery-grid figure { min-width: 0; margin: 0; }
.life-gallery-grid img { width: 100%; aspect-ratio: 1.5; object-fit: cover; border-radius: 12px; }
.life-gallery-grid figcaption { margin-top: .6rem; color: var(--public-ink); font-size: var(--type-sm); font-weight: var(--weight-semibold); }
@media (max-width: 800px) { .life-gallery-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 800px) { .student-life-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 560px) { .life-gallery-heading { align-items: start; flex-direction: column; }.life-gallery-grid { grid-template-columns: 1fr; } }
@media (max-width: 560px) { .student-life-grid { grid-template-columns: 1fr; } }
</style>
