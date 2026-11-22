<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import { Link } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed, ref } from 'vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { filterServiceCategories } from '@/composables/usePublicServicesCatalog';

const props = withDefaults(defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    initialQuery?: string;
    serviceOverrides?: Record<string, { available: boolean; badge: string; maintenance_message?: string | null }>;
}>(), {
    initialQuery: '',
    serviceOverrides: () => ({}),
});

const query = ref(props.initialQuery);
const selectedCategory = ref<string>('all');
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const categories = computed(() => {
    const filtered = filterServiceCategories(query.value).map((category) => ({
        ...category,
        items: category.items.map((item) => {
            const override = props.serviceOverrides?.[item.id];
            if (!override) return item;
            return {
                ...item,
                available: override.available,
                badge: override.badge || (override.available ? 'Aktif' : 'Pemeliharaan'),
                description: override.maintenance_message
                    ? `${item.description} (${override.maintenance_message})`
                    : item.description,
            };
        }),
    }));
    if (selectedCategory.value === 'all') return filtered;
    return filtered.filter((category) => category.key === selectedCategory.value);
});
const publicText = usePublicContentText();
</script>

<template>
    <Head :title="`Katalog E-Layanan — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="portal-container">
            <header class="portal-hero madrasah-profile-hero">
                <p class="eyebrow">{{ publicText("copy.vue_services_index", "text_001", "Katalog E-Layanan") }}</p>
                <h1>{{ publicText("copy.vue_services_index", "text_9001", "Layanan Digital") }} {{ school.name }}</h1>
                <p>{{ publicText("copy.vue_services_index", "text_002", "Cari dan buka layanan persuratan, kesiswaan, aduan, perpustakaan, pusat unduh, dan zona integritas.") }}</p>
            </header>

            <div class="portal-toolbar">
                <label class="sr-only" for="catalog-search">{{ publicText("copy.vue_services_index", "text_003", "Cari layanan") }}</label>
                <input
                    id="catalog-search"
                    v-model="query"
                    type="search"
                    class="portal-search-input"
                    :placeholder="publicText('copy.vue_services_index', 'placeholder_009', 'Cari nama layanan atau kata kunci (mis. PTSP, izin, legalisir, pustaka)...')"
                />
                <div class="portal-chips" role="group" :aria-label="publicText('copy.vue_services_index', 'aria_label_010', 'Filter kategori layanan')">
                    <button type="button" class="portal-chip" :class="{ 'is-active': selectedCategory === 'all' }" @click="selectedCategory = 'all'">{{ publicText("copy.vue_services_index", "text_004", "Semua") }}</button>
                    <button type="button" class="portal-chip" :class="{ 'is-active': selectedCategory === 'correspondence' }" @click="selectedCategory = 'correspondence'">{{ publicText("copy.vue_services_index", "text_005", "Persuratan & Tamu") }}</button>
                    <button type="button" class="portal-chip" :class="{ 'is-active': selectedCategory === 'students' }" @click="selectedCategory = 'students'">{{ publicText("copy.vue_services_index", "text_006", "Siswa & Alumni") }}</button>
                    <button type="button" class="portal-chip" :class="{ 'is-active': selectedCategory === 'order_facilities' }" @click="selectedCategory = 'order_facilities'">{{ publicText("copy.vue_services_index", "text_007", "Ketertiban & Pustaka") }}</button>
                </div>
            </div>

            <div v-if="categories.length" class="catalog-sections">
                <section v-for="category in categories" :key="category.key" class="catalog-section">
                    <div class="catalog-section__head">
                        <h2>{{ category.title }}</h2>
                        <p>{{ category.subtitle }}</p>
                    </div>
                    <div class="catalog-grid">
                        <component :is="item.available ? Link : 'div'" v-for="item in category.items" :key="item.id" :href="item.available ? item.href : undefined" :aria-disabled="!item.available || undefined" class="catalog-card">
                            <div class="catalog-card__top">
                                <strong>{{ item.title }}</strong>
                                <span v-if="item.badge" class="catalog-badge" :class="{ 'is-soon': !item.available }">{{ item.badge }}</span>
                            </div>
                            <p>{{ item.description }}</p>
                        </component>
                    </div>
                </section>
            </div>

            <StateFeedback
                v-else
                state="empty"
                :title="publicText('copy.vue_services_index', 'title_011', 'Layanan tidak ditemukan')"
                :description="`Tidak ada layanan yang cocok dengan kata kunci '${query}'.`"
            >
                <template #actions>
                    <button type="button" class="ui-button ui-button--secondary ui-button--md" @click="query = ''; selectedCategory = 'all'">
                        {{ publicText("copy.vue_services_index", "text_008", "Tampilkan semua layanan") }}
                    </button>
                </template>
            </StateFeedback>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(1180px, calc(100% - 2rem)); margin-inline: auto; padding: 2.5rem 0 4rem; display: grid; gap: 1.75rem; }
.portal-hero h1 { margin: .4rem 0 .65rem; font-size: clamp(2rem, 4vw, 3rem); line-height: 1.1; }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); max-width: 65ch; }
.portal-toolbar { display: grid; gap: .85rem; }
.portal-search-input { width: 100%; min-height: 44px; padding: .65rem .95rem; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 10px; background: #fff; font: inherit; }
.portal-chips { display: flex; flex-wrap: wrap; gap: .5rem; }
.portal-chip { min-height: 40px; padding: .4rem .85rem; border-radius: 999px; border: 1px solid var(--public-panel-border, #d8e2ee); background: #fff; color: var(--public-ink, #173044); font-weight: 600; cursor: pointer; }
.portal-chip.is-active { background: var(--public-primary, #3157a4); border-color: var(--public-primary, #3157a4); color: #fff; }
.catalog-sections { display: grid; gap: 2rem; }
.catalog-section__head h2 { margin: 0 0 .25rem; font-size: 1.25rem; }
.catalog-section__head p { margin: 0 0 1rem; color: var(--public-muted, #526673); font-size: .9rem; }
.catalog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; }
.catalog-card { display: grid; gap: .45rem; padding: 1.1rem; background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 14px; text-decoration: none; color: inherit; transition: border-color .16s ease, box-shadow .16s ease; }
.catalog-card:hover, .catalog-card:focus-visible { border-color: var(--public-primary, #3157a4); box-shadow: 0 8px 20px rgb(18 50 71 / 8%); outline: none; }
.catalog-card__top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
.catalog-card p { margin: 0; color: var(--public-muted, #526673); font-size: .875rem; line-height: 1.45; }
.catalog-badge { padding: .15rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 700; background: color-mix(in srgb, var(--public-primary, #3157a4) 12%, transparent); color: var(--public-primary, #3157a4); }
.catalog-badge.is-soon { background: #edf1f7; color: #526673; }
</style>
