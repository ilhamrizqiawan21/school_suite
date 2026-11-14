<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

withDefaults(defineProps<{
    label: string;
    value: string | number;
    note?: string;
    icon: string;
    href?: string;
    tone?: 'blue' | 'green' | 'amber' | 'violet' | 'rose' | 'slate';
}>(), {
    tone: 'blue',
});
</script>

<template>
    <component
        :is="href ? Link : 'article'"
        :href="href"
        class="ss-metric-card"
        :class="[`ss-metric-card--${tone}`, { 'ss-metric-card--link': Boolean(href) }]"
    >
        <div class="ss-metric-head">
            <span class="ss-metric-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path :d="icon" /></svg>
            </span>
            <span class="ss-metric-label">{{ label }}</span>
        </div>
        <strong class="ss-metric-value">{{ value }}</strong>
        <small v-if="note" class="ss-metric-note">{{ note }}</small>
        <slot />
    </component>
</template>

<style scoped>
.ss-metric-card {
    --metric-accent: #2563eb;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    min-width: 0;
    min-height: 128px;
    padding: 1.15rem 1rem;
    overflow: hidden;
    text-align: center;
    background: color-mix(in srgb, var(--metric-accent) 17%, var(--surface));
    border: 1px solid color-mix(in srgb, var(--metric-accent) 36%, var(--line));
    border-radius: 14px;
    box-shadow: var(--shadow-sm);
}

.ss-metric-card--link {
    text-decoration: none;
    cursor: pointer;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
}

.ss-metric-card--link:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--metric-accent) 55%, var(--line));
    box-shadow: var(--shadow-md, 0 8px 20px -12px rgba(15, 23, 42, .28));
}

.ss-metric-card--blue { --metric-accent: #2563eb; }
.ss-metric-card--green { --metric-accent: #16a34a; }
.ss-metric-card--amber { --metric-accent: #d97706; }
.ss-metric-card--violet { --metric-accent: #7c3aed; }
.ss-metric-card--rose { --metric-accent: #e11d48; }
.ss-metric-card--slate { --metric-accent: #475569; }

.ss-metric-head {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    max-width: 100%;
}

.ss-metric-icon {
    display: grid;
    place-items: center;
    flex: 0 0 28px;
    width: 28px;
    height: 28px;
    color: var(--ink);
    background: color-mix(in srgb, var(--surface) 88%, transparent);
    border: 1px solid color-mix(in srgb, var(--metric-accent) 22%, var(--line));
    border-radius: 8px;
}

.ss-metric-icon svg {
    width: 16px;
    height: 16px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.ss-metric-label {
    color: color-mix(in srgb, var(--metric-accent) 68%, var(--ink));
    font-size: var(--type-sm);
    font-weight: var(--weight-bold);
    letter-spacing: -.015em;
    line-height: 1.25;
}

.ss-metric-value {
    display: block;
    margin: .15rem 0 0;
    color: color-mix(in srgb, var(--metric-accent) 76%, var(--ink));
    font-size: clamp(1.55rem, 2.3vw, 2rem);
    font-weight: var(--weight-bold);
    letter-spacing: -.04em;
    line-height: 1.05;
}

.ss-metric-note {
    display: block;
    color: var(--ink-soft);
    font-size: var(--type-meta);
    line-height: 1.4;
}
</style>
