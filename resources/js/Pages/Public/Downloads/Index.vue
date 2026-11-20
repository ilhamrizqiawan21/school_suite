<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    categories: Record<string, string>;
    selectedCategory: string;
    downloads: {
        id: number;
        category: string;
        category_label: string;
        title: string;
        description?: string | null;
        version: number;
        original_name: string;
        size_bytes: number;
        download_count: number;
        download_url: string;
    }[];
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const selectCategory = (category: string) => {
    router.get('/pusat-unduh', { category: category || undefined }, { preserveState: true, replace: true });
};

const formatSize = (bytes: number) => `${Math.max(1, Math.round(bytes / 1024))} KB`;
</script>

<template>
    <Head :title="`Pusat Unduh — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />
        <section class="portal-container">
            <header class="portal-hero madrasah-profile-hero">
                <p class="eyebrow">Pusat Unduh</p>
                <h1>Dokumen &amp; Formulir Resmi {{ school.name }}</h1>
                <p>Unduh versi terbit terbaru formulir, kalender akademik, tata tertib, brosur, dan SOP layanan.</p>
            </header>

            <div class="portal-chips" role="group" aria-label="Kategori unduhan">
                <button type="button" class="portal-chip" :class="{ 'is-active': !selectedCategory }" @click="selectCategory('')">Semua</button>
                <button
                    v-for="(label, key) in categories"
                    :key="key"
                    type="button"
                    class="portal-chip"
                    :class="{ 'is-active': selectedCategory === key }"
                    @click="selectCategory(key)"
                >
                    {{ label }}
                </button>
            </div>

            <div v-if="downloads.length" class="downloads-list">
                <article v-for="doc in downloads" :key="doc.id" class="download-row">
                    <div class="download-row__info">
                        <div class="download-row__meta">
                            <StatusBadge status="published" :label="doc.category_label" size="sm" />
                            <span>Versi {{ doc.version }}</span>
                            <span>{{ formatSize(doc.size_bytes) }}</span>
                            <span>{{ doc.download_count }} unduhan</span>
                        </div>
                        <h2>{{ doc.title }}</h2>
                        <p v-if="doc.description">{{ doc.description }}</p>
                    </div>
                    <a :href="doc.download_url" class="ui-button ui-button--primary ui-button--md">Unduh Berkas</a>
                </article>
            </div>

            <StateFeedback v-else state="empty" title="Belum ada dokumen terbit" description="Dokumen resmi pada kategori ini belum tersedia." />
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(1180px, calc(100% - 2rem)); margin-inline: auto; padding: 2.5rem 0 4rem; display: grid; gap: 1.5rem; }
.portal-hero h1 { margin: .4rem 0 .65rem; font-size: clamp(2rem, 4vw, 3rem); }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); }
.portal-chips { display: flex; flex-wrap: wrap; gap: .5rem; }
.portal-chip { min-height: 40px; padding: .4rem .85rem; border-radius: 999px; border: 1px solid var(--public-panel-border, #d8e2ee); background: #fff; color: var(--public-ink, #173044); font-weight: 600; cursor: pointer; }
.portal-chip.is-active { background: var(--public-primary, #3157a4); border-color: var(--public-primary, #3157a4); color: #fff; }
.downloads-list { display: grid; gap: .85rem; }
.download-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.25rem; background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 14px; }
.download-row__meta { display: flex; flex-wrap: wrap; align-items: center; gap: .65rem; font-size: .78rem; color: var(--public-muted, #526673); margin-bottom: .35rem; }
.download-row h2 { margin: 0; font-size: 1.05rem; }
.download-row p { margin: .25rem 0 0; color: var(--public-muted, #526673); font-size: .875rem; }
</style>
