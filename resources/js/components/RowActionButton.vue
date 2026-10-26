<script setup lang="ts">
withDefaults(defineProps<{
    action: 'edit' | 'delete' | 'archive';
    label: string;
    ariaLabel?: string;
    disabled?: boolean;
}>(), {
    disabled: false,
});
</script>

<template>
    <button
        type="button"
        class="row-action-button"
        :class="`row-action-button--${action}`"
        :aria-label="ariaLabel ?? label"
        :title="ariaLabel ?? label"
        :disabled="disabled"
    >
        <svg v-if="action === 'edit'" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
        </svg>
        <svg v-else-if="action === 'archive'" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M3 4h18v5H3z" />
            <path d="M5 9v11h14V9m-9 4h4" />
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M3 6h18" />
            <path d="M8 6V4h8v2m3 0-1 14H6L5 6" />
            <path d="M10 11v5m4-5v5" />
        </svg>
        <span>{{ label }}</span>
    </button>
</template>
