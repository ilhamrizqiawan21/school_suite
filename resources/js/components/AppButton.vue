<script setup lang="ts">
import { computed, useAttrs, useSlots } from 'vue';
import ButtonIcon, { type ButtonIconName } from './ButtonIcon.vue';

defineOptions({ inheritAttrs: false });
const props = withDefaults(defineProps<{
    as?: 'button' | 'a';
    type?: 'button' | 'submit' | 'reset';
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'warning';
    size?: 'sm' | 'md' | 'lg';
    icon?: ButtonIconName;
    iconOnly?: boolean;
    keepLabel?: boolean;
    mobileIconOnly?: boolean;
    disabled?: boolean;
    loading?: boolean;
}>(), { as: 'button', type: 'button', variant: 'secondary', size: 'md', iconOnly: false, keepLabel: false, disabled: false, loading: false });
const attrs = useAttrs();
const slots = useSlots();
const isSemanticIconOnly = computed(() =>
    props.icon === 'excel' || props.icon === 'pdf' || props.icon === 'word' || props.icon === 'view' || props.icon === 'edit' || props.icon === 'delete',
);
const effectiveIconOnly = computed(() => props.iconOnly || (isSemanticIconOnly.value && !props.keepLabel));
const slotText = computed(() => {
    const nodes = slots.default?.() ?? [];
    const extract = (items: any[]): string =>
        items
            .map((node) => {
                if (typeof node.children === 'string') return node.children;
                if (Array.isArray(node.children)) return extract(node.children);
                return '';
            })
            .join(' ');
    const text = extract(nodes).replace(/\s+/g, ' ').trim();
    return text || undefined;
});
const computedTitle = computed(() => (attrs.title as string | undefined) ?? (attrs['aria-label'] as string | undefined) ?? (effectiveIconOnly.value ? slotText.value : undefined));
const computedAriaLabel = computed(() => (attrs['aria-label'] as string | undefined) ?? (attrs.title as string | undefined) ?? (effectiveIconOnly.value ? slotText.value : undefined));
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
        :title="computedTitle"
        :aria-label="computedAriaLabel"
        class="ui-button"
        :class="[
            `ui-button--${variant}`,
            `ui-button--${size}`,
            props.icon ? `ui-button--icon-${props.icon}` : undefined,
            { 'ui-button--icon-only': effectiveIconOnly, 'ui-button--mobile-icon': mobileIconOnly, 'is-loading': loading },
        ]"
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
