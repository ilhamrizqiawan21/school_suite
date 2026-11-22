<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicSectionLayout from '@/components/PublicSectionLayout.vue';
const publicText = usePublicContentText();
const page = usePage();
const hideDemo = computed(() => Boolean(((page.props.publicContentBlocks ?? {}) as Record<string, any>)['shell.options']?.hide_demo_fallbacks));

interface School {
    name: string;
    logo_url?: string | null;
    level?: string | null;
    accreditation?: string | null;
    about?: string | null;
}

interface NewsItem {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
}

interface GalleryItem {
    id: number;
    title?: string | null;
    caption?: string | null;
    url: string;
}

interface ProgramItem { id?: string; title: string; description?: string; image_url?: string; link?: string }
interface ContentBlocks { 'school.programs'?: { visible?: boolean; title?: string; subtitle?: string; items?: ProgramItem[] } }

const props = defineProps<{ school: School; news: NewsItem[]; gallery: GalleryItem[]; contentBlocks?: ContentBlocks }>();
const programsBlock = computed(() => props.contentBlocks?.['school.programs']);
const programItems = computed(() => Array.isArray(programsBlock.value?.items) ? programsBlock.value.items.filter((item) => item?.title?.trim()) : []);
const safeLink = (value?: string) => {
    const link = (value || '').trim();
    return link.startsWith('/') || link.startsWith('https://') ? link : '';
};

const facilityPhotos = [
    {
        title: publicText("copy.vue_school_programs", "text_12001", "Laboratorium sains"),
        description: publicText("copy.vue_school_programs", "text_12002", "Ruang eksperimen yang terang dan tertata untuk belajar melalui praktik."),
        url: publicText('copy.vue_school_programs', 'laboratory_image_url', '/images/facilities/science-laboratory.png'),
    },
    {
        title: publicText("copy.vue_school_programs", "text_12003", "Perpustakaan sekolah"),
        description: publicText("copy.vue_school_programs", "text_12004", "Ruang baca yang tenang untuk menemukan referensi dan gagasan baru."),
        url: publicText('copy.vue_school_programs', 'library_image_url', '/images/facilities/school-library.png'),
    },
    {
        title: publicText("copy.vue_school_programs", "text_12005", "Ruang aktivitas siswa"),
        description: publicText("copy.vue_school_programs", "text_12006", "Ruang kolaborasi untuk karya, komunitas, dan kegiatan siswa."),
        url: publicText('copy.vue_school_programs', 'activity_image_url', '/images/facilities/student-activity-room.png'),
    },
];
const facilityGallery = computed(() => props.gallery.length
    ? props.gallery.slice(0, 3).map((photo) => ({
        title: photo.title || photo.caption || 'Kegiatan sekolah',
        description: photo.caption || 'Dokumentasi resmi sekolah.',
        url: photo.url,
    }))
    : hideDemo.value ? [] : facilityPhotos);
</script>

<template>
    <PublicSectionLayout
        :school="school"
        :title="publicText('copy.vue_school_programs', 'title_015', 'Akademik dan fasilitas')"
        :eyebrow="publicText('copy.vue_school_programs', 'eyebrow_016', 'Program sekolah')"
        :description="`Kenali jenjang pendidikan, ruang belajar, dan informasi pembelajaran yang tersedia di ${school.name}.`"
        active="programs"
    >
        <section class="section-content" :aria-label="publicText('copy.vue_school_programs', 'aria_label_017', 'Informasi akademik dan fasilitas')">
            <section v-if="facilityGallery.length" class="facility-gallery" aria-labelledby="facility-gallery-title">
                <div class="gallery-heading">
                    <div>
                        <span class="section-label">{{ publicText("copy.vue_school_programs", "text_001", "Fasilitas unggulan") }}</span>
                        <h2 id="facility-gallery-title">{{ publicText("copy.vue_school_programs", "text_002", "Sarana & Prasarana") }}</h2>
                    </div>
                    <p>{{ gallery.length ? 'Dokumentasi ruang dan kegiatan yang diterbitkan sekolah.' : 'Contoh visual fasilitas sekolah. Foto resmi dapat diterbitkan melalui galeri.' }}</p>
                </div>
                <div class="facility-grid">
                    <figure v-for="facility in facilityGallery" :key="facility.url" class="facility-card">
                        <img :src="facility.url" :alt="facility.title" loading="lazy" />
                        <figcaption>
                            <strong>{{ facility.title }}</strong>
                            <span>{{ facility.description }}</span>
                        </figcaption>
                    </figure>
                </div>
            </section>
            <section v-if="programsBlock?.visible !== false && programItems.length" class="program-list" aria-labelledby="program-list-title">
                <div class="gallery-heading">
                    <div>
                        <span class="section-label">{{ programsBlock?.title || 'Program sekolah' }}</span>
                        <h2 id="program-list-title">{{ programsBlock?.subtitle || 'Program dan fasilitas pilihan' }}</h2>
                    </div>
                </div>
                <div class="program-grid">
                    <article v-for="(item, index) in programItems" :key="item.id || `${item.title}-${index}`" class="program-card">
                        <img v-if="item.image_url" :src="item.image_url" :alt="item.title" loading="lazy" />
                        <div><h3>{{ item.title }}</h3><p v-if="item.description">{{ item.description }}</p><a v-if="safeLink(item.link)" :href="safeLink(item.link)" class="card-link">Selengkapnya →</a></div>
                    </article>
                </div>
            </section>
            <div class="info-grid">
                <article class="info-card">
                    <h2>{{ publicText("copy.vue_school_programs", "text_003", "Profil akademik") }}</h2>
                    <p>{{ school.about || 'Informasi program akademik sekolah sedang disiapkan.' }}</p>
                    <dl>
                        <div v-if="school.level"><dt>{{ publicText("copy.vue_school_programs", "text_004", "Jenjang") }}</dt><dd>{{ school.level }}</dd></div>
                        <div v-if="school.accreditation"><dt>{{ publicText("copy.vue_school_programs", "text_005", "Akreditasi") }}</dt><dd>{{ school.accreditation }}</dd></div>
                    </dl>
                </article>
                <article class="info-card">
                    <h2>{{ publicText("copy.vue_school_programs", "text_006", "Fasilitas dan ruang belajar") }}</h2>
                    <p>{{ publicText("copy.vue_school_programs", "text_007", "Dokumentasi ruang dan kegiatan sekolah dapat dilihat melalui koleksi galeri publik.") }}</p>
                    <Link href="/galeri" class="card-link">{{ publicText("copy.vue_school_programs", "text_008", "Lihat galeri →") }}</Link>
                </article>
                <article class="info-card">
                    <h2>{{ publicText("copy.vue_school_programs", "text_009", "Kabar pembelajaran") }}</h2>
                    <p v-if="news.length">{{ publicText("copy.vue_school_programs", "text_010", "Baca kabar terbaru tentang kegiatan belajar dan program sekolah.") }}</p>
                    <p v-else>{{ publicText("copy.vue_school_programs", "text_011", "Berita pembelajaran akan tampil setelah dipublikasikan oleh sekolah.") }}</p>
                    <Link href="/berita" class="card-link">{{ publicText("copy.vue_school_programs", "text_012", "Buka berita →") }}</Link>
                </article>
            </div>
            <div v-if="gallery.length" class="resource-strip">
                <span>{{ publicText("copy.vue_school_programs", "text_013", "Portofolio tersedia") }}</span>
                <strong>{{ gallery.length }} {{ publicText("copy.vue_school_programs", "text_9001", "koleksi foto sekolah") }}</strong>
                <Link href="/galeri">{{ publicText("copy.vue_school_programs", "text_014", "Jelajahi koleksi →") }}</Link>
            </div>
        </section>
    </PublicSectionLayout>
</template>

<style scoped>
.section-content { padding-bottom: 1rem; }
.info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.info-card { display: grid; align-content: start; gap: .7rem; min-height: 240px; padding: 1.35rem; background: #fffdf8; border: 1px solid #ded7c9; border-radius: .8rem; }
.info-card h2 { margin: 0; font-family: var(--font-ui); font-size: 1.6rem; font-weight: var(--weight-regular); }
.info-card p { margin: 0; color: #526673; font-size: var(--type-md); line-height: 1.65; }
.info-card dl { display: grid; gap: .45rem; margin: auto 0 0; }
.info-card dl div { display: flex; justify-content: space-between; gap: 1rem; padding-top: .45rem; border-top: 1px solid #ded7c9; }
.info-card dt { color: #526673; font-size: var(--type-meta); }.info-card dd { margin: 0; font-size: var(--type-sm); font-weight: var(--weight-bold); }
.card-link, .resource-strip a { color: #9a7329; font-size: var(--type-sm); font-weight: var(--weight-bold); text-decoration: none; }
.card-link { align-self: end; margin-top: auto; }
.facility-gallery { margin-bottom: 2rem; }
.gallery-heading { display: flex; align-items: end; justify-content: space-between; gap: 2rem; margin-bottom: 1.5rem; }
.section-label { color: #9a7329; font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .09em; text-transform: uppercase; }
.gallery-heading h2 { max-width: 620px; margin: .6rem 0 0; font-family: var(--font-ui); font-size: clamp(2rem, 4vw, 3.7rem); font-weight: var(--weight-regular); letter-spacing: -.05em; line-height: 1.03; }
.gallery-heading p { max-width: 300px; margin: 0; color: #526673; font-size: var(--type-md); line-height: 1.6; }
.facility-grid { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 1rem; }
.facility-card { display: grid; grid-template-rows: 250px auto; overflow: hidden; margin: 0; background: #fffdf8; border: 1px solid #ded7c9; border-radius: .8rem; }
.facility-card img { width: 100%; height: 100%; min-height: 0; object-fit: cover; }
.facility-card figcaption { display: grid; gap: .35rem; padding: 1rem; }.facility-card strong { font-family: var(--font-ui); font-size: 1.35rem; font-weight: var(--weight-regular); }.facility-card span { color: #526673; font-size: var(--type-meta); line-height: 1.55; }
.resource-strip { display: flex; align-items: center; gap: 1rem; margin-top: 1.5rem; padding: 1rem 1.2rem; border-top: 1px solid #cfc8ba; border-bottom: 1px solid #cfc8ba; }
.resource-strip span { color: #526673; font-size: var(--type-meta); }.resource-strip strong { margin-right: auto; font-size: var(--type-md); }
.program-list { margin-bottom: 2rem; }.program-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }.program-card { overflow: hidden; background: #fffdf8; border: 1px solid #ded7c9; border-radius: .8rem; }.program-card img { width: 100%; aspect-ratio: 1.7; object-fit: cover; }.program-card > div { display: grid; gap: .5rem; padding: 1rem; }.program-card h3 { margin: 0; font-family: var(--font-ui); font-size: 1.25rem; font-weight: var(--weight-regular); }.program-card p { margin: 0; color: #526673; font-size: var(--type-sm); line-height: 1.55; }.program-card .card-link { margin-top: .25rem; }
@media (max-width: 800px) { .info-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 800px) { .facility-grid { grid-template-columns: 1fr 1fr; }.facility-card:first-child { grid-row: auto; grid-column: 1 / -1; grid-template-rows: 300px auto; } }
@media (max-width: 800px) { .program-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 560px) { .info-grid { grid-template-columns: 1fr; }.gallery-heading { align-items: flex-start; flex-direction: column; gap: .8rem; }.facility-grid { grid-template-columns: 1fr; }.facility-card:first-child { grid-column: auto; grid-template-rows: 220px auto; }.facility-card { grid-template-rows: 220px auto; }.resource-strip { align-items: flex-start; flex-direction: column; gap: .4rem; }.resource-strip strong { margin-right: 0; } }
@media (max-width: 560px) { .program-grid { grid-template-columns: 1fr; } }
</style>
