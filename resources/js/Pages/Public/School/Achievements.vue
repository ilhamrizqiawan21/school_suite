<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    items: { id: number; title: string; slug: string; category: string; excerpt?: string | null; published_at?: string | null }[];
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));
</script>

<template>
    <Head :title="`Prestasi & Agenda — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" active="student-life" />
        <section class="portal-container">
            <header class="portal-hero">
                <p class="eyebrow">Prestasi &amp; Agenda Sekolah</p>
                <h1>Prestasi &amp; Agenda {{ school.name }}</h1>
                <p>Dokumentasi capaian siswa/guru serta agenda kegiatan resmi sekolah yang telah dipublikasikan.</p>
            </header>

            <div v-if="items.length" class="achievement-grid">
                <Link v-for="item in items" :key="item.id" :href="`/berita/${item.slug}`" class="achievement-card">
                    <span class="achievement-cat">{{ item.category }}</span>
                    <h2>{{ item.title }}</h2>
                    <p v-if="item.excerpt">{{ item.excerpt }}</p>
                </Link>
            </div>

            <StateFeedback v-else state="empty" title="Belum ada prestasi atau agenda terbit" description="Publikasi prestasi dan agenda resmi akan tampil di sini setelah diterbitkan admin." />
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(1180px, calc(100% - 2rem)); margin-inline: auto; padding: 2.5rem 0 4rem; display: grid; gap: 1.5rem; }
.portal-hero h1 { margin: .4rem 0 .65rem; font-size: clamp(2rem, 4vw, 3rem); }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); }
.achievement-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
.achievement-card { display: grid; gap: .45rem; padding: 1.15rem; background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 14px; text-decoration: none; color: inherit; }
.achievement-card h2 { margin: 0; font-size: 1.05rem; }
.achievement-card p { margin: 0; color: var(--public-muted, #526673); font-size: .875rem; }
.achievement-cat { width: fit-content; padding: .15rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 700; text-transform: uppercase; background: color-mix(in srgb, var(--public-primary, #3157a4) 12%, transparent); color: var(--public-primary, #3157a4); }
</style>
