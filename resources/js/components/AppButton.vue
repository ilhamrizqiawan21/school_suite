<script setup lang="ts">
import { computed, useAttrs } from 'vue';
import ButtonIcon, { type ButtonIconName } from './ButtonIcon.vue';

defineOptions({ inheritAttrs: false });
const props = withDefaults(defineProps<{
    as?: 'button' | 'a';
    type?: 'button' | 'submit' | 'reset';
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'warning';
    size?: 'sm' | 'md' | 'lg';
    icon?: ButtonIconName;
    iconOnly?: boolean;
    mobileIconOnly?: boolean;
    disabled?: boolean;
    loading?: boolean;
}>(), { as: 'button', type: 'button', variant: 'secondary', size: 'md', iconOnly: false, disabled: false, loading: false });
const attrs = useAttrs();
const unavailable = computed(() => props.disabled || props.loading);
function guardAction(event: MouseEvent) {
    if (!unavailable.value) return;
    event.preventDefault();
    event.stopImmediatePropagation();
}
</script>

<template>
    <component
        :is="as"
        v-bind="attrs"
        :href="as === 'a' && unavailable ? undefined : attrs.href"
        :type="as === 'button' ? type : undefined"
        class="ui-button"
        :class="[`ui-button--${variant}`, `ui-button--${size}`, { 'ui-button--icon-only': iconOnly, 'ui-button--mobile-icon': mobileIconOnly, 'is-loading': loading }]"
        :disabled="as === 'button' ? unavailable : undefined"
        :aria-disabled="unavailable || undefined"
        :aria-busy="loading || undefined"
        :tabindex="as === 'a' && unavailable ? -1 : attrs.tabindex"
        @click.capture="guardAction"
    >
        <svg v-if="loading" class="ui-button-icon ui-button-spinner" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /></svg>
        <slot v-else name="icon"><ButtonIcon v-if="icon" :name="icon" /></slot>
        <span class="ui-button-label"><slot /></span>
    </component>
</template>
