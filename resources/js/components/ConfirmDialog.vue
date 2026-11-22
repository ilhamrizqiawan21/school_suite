<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';
import AppButton from './AppButton.vue';
import { lockBodyScroll, unlockBodyScroll } from '@/lib/bodyScrollLock';

const props = withDefaults(defineProps<{
    open: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'primary' | 'danger' | 'warning';
    loading?: boolean;
}>(), {
    confirmLabel: 'Ya, lanjutkan',
    cancelLabel: 'Batal',
    variant: 'danger',
    loading: false,
});

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();

const dialogRef = ref<HTMLElement | null>(null);
const dialogId = useId();
const scrollOwner = Symbol('confirmation');
const cancelButtonRef = ref<HTMLElement | null>(null);
let previouslyFocused: HTMLElement | null = null;

const close = () => {
    if (!props.loading) {
        emit('cancel');
    }
};

const handleKeydown = (event: KeyboardEvent) => {
    if (!props.open || event.defaultPrevented) return;
    if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        close();
        return;
    }
    if (event.key !== 'Tab' || !dialogRef.value) return;
    const focusable = Array.from(dialogRef.value.querySelectorAll<HTMLElement>('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'));
    const first = focusable[0];
    const last = focusable.at(-1);
    if (first && last && ((event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last))) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
    }
};

const restoreFocus = () => {
    unlockBodyScroll(scrollOwner);
    previouslyFocused?.focus();
    previouslyFocused = null;
};

watch(() => props.open, async (isOpen) => {
    if (isOpen) {
        previouslyFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        lockBodyScroll(scrollOwner);
        await nextTick();
        dialogRef.value?.querySelector<HTMLElement>('[autofocus], button:not([disabled])')?.focus();
        return;
    }

    restoreFocus();
}, { immediate: true });

onBeforeUnmount(() => {
    restoreFocus();
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="ss-confirm-overlay"
            @click.self="close"
            @keydown="handleKeydown"
        >
            <div
                ref="dialogRef"
                class="ss-confirm-dialog"
                :class="`ss-confirm-dialog--${variant}`"
                :aria-busy="loading"
                role="alertdialog"
                aria-modal="true"
                :aria-labelledby="`${dialogId}-title`"
                :aria-describedby="`${dialogId}-message`"
            >
                <h2 :id="`${dialogId}-title`" class="ss-confirm-dialog__title">{{ title }}</h2>
                <p :id="`${dialogId}-message`" class="ss-confirm-dialog__message">{{ message }}</p>
                <slot />

                <div class="ss-confirm-dialog__actions">
                    <span ref="cancelButtonRef" tabindex="-1">
                        <AppButton variant="secondary" :disabled="loading" @click="close">
                            {{ cancelLabel }}
                        </AppButton>
                    </span>
                    <AppButton :variant="variant" :loading="loading" @click="emit('confirm')">
                        {{ confirmLabel }}
                    </AppButton>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.ss-confirm-overlay {
    position: fixed;
    inset: 0;
    z-index: 120;
    display: grid;
    place-items: center;
    padding: 1rem;
    background: rgb(15 23 42 / 52%);
    backdrop-filter: blur(2px);
}

.ss-confirm-dialog {
    width: min(440px, 100%);
    padding: 1.5rem;
    background: var(--surface, #ffffff);
    border: 1px solid var(--line, #dee4ee);
    border-radius: var(--admin-radius, 8px);
    max-height: calc(100dvh - 2rem);
    overflow-y: auto;
    box-shadow: var(--shadow-md);
}

.ss-confirm-dialog__title {
    margin: 0 0 .5rem;
    color: var(--ink, #172033);
    font-size: var(--type-lg, 1.0625rem);
    font-weight: var(--weight-bold, 700);
}

.ss-confirm-dialog--danger .ss-confirm-dialog__title { color: var(--red); }
.ss-confirm-dialog--warning .ss-confirm-dialog__title { color: var(--gold); }

.ss-confirm-dialog__message {
    margin: 0 0 1.25rem;
    color: var(--ink-soft, #536174);
    font-size: var(--type-sm, .875rem);
    line-height: 1.55;
}

.ss-confirm-dialog__actions {
    display: flex;
    justify-content: flex-end;
    gap: .65rem;
}

.ss-confirm-dialog__actions :deep(.ui-button) {
    min-height: 36px;
}
</style>
