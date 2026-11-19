<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    id: string;
    label: string;
    required?: boolean;
    hint?: string;
    error?: string | null;
}>(), {
    required: false,
    hint: undefined,
    error: undefined,
});

const hintId = computed(() => (props.hint ? `${props.id}-hint` : undefined));
const errorId = computed(() => (props.error ? `${props.id}-error` : undefined));
const describedBy = computed(() => [errorId.value, hintId.value].filter(Boolean).join(' ') || undefined);
</script>

<template>
    <div class="ss-form-field" :class="{ 'has-error': Boolean(error) }">
        <label class="ss-form-field__label" :for="id">
            <span>{{ label }}</span>
            <span v-if="required" class="ss-form-field__required" aria-hidden="true">*</span>
            <span v-if="required" class="sr-only">wajib diisi</span>
        </label>

        <div class="ss-form-field__control">
            <slot :input-id="id" :described-by="describedBy" :invalid="Boolean(error)" />
        </div>

        <p v-if="hint && !error" :id="hintId" class="ss-form-field__hint">
            {{ hint }}
        </p>

        <p v-if="error" :id="errorId" class="ss-form-field__error" role="alert">
            {{ error }}
        </p>
    </div>
</template>

<style scoped>
.ss-form-field {
    display: grid;
    gap: .4rem;
    min-width: 0;
}

.ss-form-field__label {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    color: var(--ink, #172033);
    font-size: var(--type-label, .875rem);
    font-weight: var(--weight-semibold, 600);
    line-height: 1.35;
}

.ss-form-field__required {
    color: var(--red, #b13f38);
    font-weight: var(--weight-bold, 700);
}

.ss-form-field__control :deep(input:not([type='checkbox']):not([type='radio'])),
.ss-form-field__control :deep(select),
.ss-form-field__control :deep(textarea) {
    width: 100%;
    min-height: var(--control-height-lg, 44px);
    padding: .55rem .8rem;
    color: var(--ink, #172033);
    background: var(--surface, #ffffff);
    border: 1px solid var(--line-strong, #c8d1df);
    border-radius: var(--radius-sm, 8px);
    font: inherit;
    font-size: var(--type-sm, .875rem);
    transition: border-color .16s ease, box-shadow .16s ease;
}

.ss-form-field__control :deep(input:focus-visible),
.ss-form-field__control :deep(select:focus-visible),
.ss-form-field__control :deep(textarea:focus-visible) {
    outline: 0;
    border-color: var(--primary, #3157a4);
    box-shadow: var(--shadow-focus, 0 0 0 3px color-mix(in srgb, #3157a4 16%, transparent));
}

.ss-form-field.has-error .ss-form-field__control :deep(input),
.ss-form-field.has-error .ss-form-field__control :deep(select),
.ss-form-field.has-error .ss-form-field__control :deep(textarea) {
    border-color: var(--red, #b13f38);
}

.ss-form-field__hint {
    margin: 0;
    color: var(--ink-soft, #536174);
    font-size: var(--type-caption, .75rem);
    line-height: 1.45;
}

.ss-form-field__error {
    margin: 0;
    color: var(--red, #b13f38);
    font-size: var(--type-caption, .75rem);
    font-weight: var(--weight-semibold, 600);
    line-height: 1.45;
}
</style>
