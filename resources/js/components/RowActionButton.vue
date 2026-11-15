<script setup lang="ts">
import AppButton from './AppButton.vue';
withDefaults(defineProps<{
    action: 'edit' | 'delete' | 'archive';
    label: string;
    ariaLabel?: string;
    disabled?: boolean;
    iconOnly?: boolean;
}>(), {
    disabled: false,
    iconOnly: true,
});
</script>

<template>
    <AppButton
        type="button"
        class="row-action-button"
        :class="`row-action-button--${action}`"
        :aria-label="ariaLabel ?? label"
        :title="ariaLabel ?? label"
        :disabled="disabled"
        :variant="action === 'delete' ? 'danger' : action === 'archive' ? 'warning' : 'secondary'"
        :icon="action"
        :icon-only="iconOnly"
        size="sm"
    >
        <span>{{ label }}</span>
    </AppButton>
</template>
