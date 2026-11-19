<script setup lang="ts">
import AppButton from './AppButton.vue';

const props = withDefaults(defineProps<{
    state: 'loading' | 'empty' | 'error';
    title?: string;
    description?: string;
    retryLabel?: string;
}>(), {
    title: undefined,
    description: undefined,
    retryLabel: 'Coba lagi',
});

const emit = defineEmits<{
    retry: [];
}>();
</script>

<template>
    <div
        class="ss-state-feedback"
        :class="`ss-state-feedback--${state}`"
        :role="state === 'error' ? 'alert' : 'status'"
        aria-live="polite"
    >
        <template v-if="state === 'loading'">
            <div class="ss-state-feedback__skeleton" aria-hidden="true">
                <span class="ss-skeleton-line ss-skeleton-line--title" />
                <span class="ss-skeleton-line" />
                <span class="ss-skeleton-line ss-skeleton-line--short" />
            </div>
            <p class="ss-state-feedback__desc">{{ title || 'Memuat data...' }}</p>
        </template>

        <template v-else-if="state === 'empty'">
            <div class="ss-state-feedback__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4m16 0h-4l-2 2h-4l-2-2H4" />
                </svg>
            </div>
            <h3 class="ss-state-feedback__title">{{ title || 'Belum ada data' }}</h3>
            <p v-if="description" class="ss-state-feedback__desc">{{ description }}</p>
            <div v-if="$slots.actions" class="ss-state-feedback__actions">
                <slot name="actions" />
            </div>
        </template>

        <template v-else>
            <div class="ss-state-feedback__icon ss-state-feedback__icon--error" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
            </div>
            <h3 class="ss-state-feedback__title">{{ title || 'Gagal memuat data' }}</h3>
            <p v-if="description" class="ss-state-feedback__desc">{{ description }}</p>
            <div class="ss-state-feedback__actions">
                <AppButton variant="secondary" size="md" @click="emit('retry')">
                    {{ retryLabel }}
                </AppButton>
                <slot name="actions" />
            </div>
        </template>
    </div>
</template>

<style scoped>
.ss-state-feedback {
    display: grid;
    justify-items: center;
    gap: .65rem;
    padding: 2rem 1.25rem;
    text-align: center;
    background: var(--surface, #ffffff);
    border: 1px dashed var(--line-strong, #c8d1df);
    border-radius: var(--radius-md, 12px);
}

.ss-state-feedback--error {
    background: var(--red-soft, #fbe9e7);
    border-style: solid;
    border-color: var(--delete-line, #f4c6cd);
}

.ss-state-feedback__icon {
    display: grid;
    place-items: center;
    width: 2.75rem;
    height: 2.75rem;
    color: var(--ink-soft, #536174);
    background: var(--surface-strong, #edf1f7);
    border-radius: var(--radius-pill, 999px);
}

.ss-state-feedback__icon svg {
    width: 1.35rem;
    height: 1.35rem;
}

.ss-state-feedback__icon--error {
    color: var(--red, #b13f38);
    background: color-mix(in srgb, var(--red, #b13f38) 12%, white);
}

.ss-state-feedback__title {
    margin: 0;
    color: var(--ink, #172033);
    font-size: var(--type-md, 1rem);
    font-weight: var(--weight-bold, 700);
}

.ss-state-feedback__desc {
    max-width: 46ch;
    margin: 0;
    color: var(--ink-soft, #536174);
    font-size: var(--type-sm, .875rem);
    line-height: 1.5;
}

.ss-state-feedback__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .5rem;
    margin-top: .35rem;
}

.ss-state-feedback__skeleton {
    display: grid;
    gap: .5rem;
    width: min(320px, 100%);
}

.ss-skeleton-line {
    display: block;
    height: .75rem;
    background: var(--surface-strong, #edf1f7);
    border-radius: 6px;
}

.ss-skeleton-line--title {
    width: 60%;
    height: 1rem;
    margin-inline: auto;
}

.ss-skeleton-line--short {
    width: 75%;
    margin-inline: auto;
}
</style>
