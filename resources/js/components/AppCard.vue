<script setup lang="ts">
withDefaults(defineProps<{
    as?: string;
    variant?: 'default' | 'compact' | 'featured' | 'metric';
    interactive?: boolean;
}>(), {
    as: 'section',
    variant: 'default',
    interactive: false,
});
</script>

<template>
    <component
        :is="as"
        class="surface-card"
        :class="{
            'surface-card--compact': variant === 'compact',
            'surface-card--featured': variant === 'featured',
            'surface-card--metric': variant === 'metric',
            'surface-card--interactive': interactive,
        }"
    >
        <slot />
    </component>
</template>
