<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import AppButton from './AppButton.vue';

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
const cancelButtonRef = ref<HTMLElement | null>(null);
let previouslyFocused: HTMLElement | null = null;

const close = () => {
    if (!props.loading) {
        emit('cancel');
    }
};

const restoreFocus = () => {
    previouslyFocused?.focus();
    previouslyFocused = null;
};

watch(() => props.open, async (isOpen) => {
    if (isOpen) {
        previouslyFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        await nextTick();
        cancelButtonRef.value?.focus();
        return;
    }

    restoreFocus();
});

onBeforeUnmount(() => {
    previouslyFocused = null;
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="ss-confirm-overlay"
            @click.self="close"
            @keydown.esc.prevent="close"
        >
            <div
                ref="dialogRef"
                class="ss-confirm-dialog"
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="ss-confirm-title"
                aria-describedby="ss-confirm-message"
            >
                <h2 id="ss-confirm-title" class="ss-confirm-dialog__title">{{ title }}</h2>
                <p id="ss-confirm-message" class="ss-confirm-dialog__message">{{ message }}</p>

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
    border-radius: var(--radius-lg, 18px);
    box-shadow: var(--shadow-md);
}

.ss-confirm-dialog__title {
    margin: 0 0 .5rem;
    color: var(--ink, #172033);
    font-size: var(--type-lg, 1.0625rem);
    font-weight: var(--weight-bold, 700);
}

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
    min-height: var(--control-height-lg, 44px);
}
</style>
