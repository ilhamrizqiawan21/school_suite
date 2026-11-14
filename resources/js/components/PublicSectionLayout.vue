<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

interface SchoolBrand {
    name: string;
    logo_url?: string | null;
    theme?: { primary?: string | null };
}

const props = defineProps<{
    school: SchoolBrand;
    title: string;
    eyebrow: string;
    description: string;
    active: 'programs' | 'student-life' | 'contact';
}>();
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
</script>

<template>
    <Head :title="`${title} — ${school.name}`" />
    <main class="public-site public-section-page" :style="themeStyle">
        <PublicHeader :school="school" :active="active" />
        <section class="section-hero" aria-labelledby="section-title">
            <p class="eyebrow">{{ eyebrow }}</p>
            <h1 id="section-title">{{ title }}</h1>
            <p>{{ description }}</p>
        </section>
        <slot />
        <footer class="section-footer">
            <span>{{ school.name }}</span>
            <span>Informasi resmi sekolah</span>
        </footer>
    </main>
</template>

<style scoped>
:global(body) {
    margin: 0;
    background: #f2f6f9;
}

.public-section-page {
    --ink: var(--public-ink, #173044);
    --muted: var(--public-muted, #526673);
    --paper: var(--public-paper, #f5f8fb);
    --deep: var(--public-primary, #3157a4);
    --gold: var(--public-accent, #8a661f);
    min-height: 100vh;
    color: var(--ink);
    background: var(--paper);
    font-family: var(--font-ui);
}

.section-hero,
.section-content,
.section-footer {
    width: min(1180px, calc(100% - 3rem));
    margin-inline: auto;
}

.section-hero {
    padding: clamp(4rem, 9vw, 8rem) 0 4rem;
}

.eyebrow {
    margin: 0;
    color: var(--gold);
    font-size: var(--type-meta);
    font-weight: var(--weight-bold);
    letter-spacing: .09em;
    text-transform: uppercase;
}

.section-hero h1 {
    max-width: 820px;
    margin: .8rem 0 1.25rem;
    font-family: var(--font-ui);
    font-size: clamp(3rem, 7vw, 6.5rem);
    font-weight: var(--weight-regular);
    letter-spacing: -.065em;
    line-height: .98;
}

.section-hero > p:last-child {
    max-width: 680px;
    margin: 0;
    color: var(--muted);
    font-size: 1.05rem;
    line-height: 1.8;
}

.section-footer {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 6rem;
    padding: 1.3rem 0 3rem;
    color: var(--muted);
    border-top: 1px solid var(--public-line-strong);
    font-size: var(--type-caption);
}

@media (max-width: 560px) {
    .section-hero,
    .section-content,
    .section-footer {
        width: calc(100% - 2rem);
    }

    .section-footer {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
