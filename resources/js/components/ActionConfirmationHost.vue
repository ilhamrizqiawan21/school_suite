<script setup lang="ts">
import { onBeforeUnmount, ref, useId, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import ConfirmDialog from './ConfirmDialog.vue';
import { useConfirmedFormNavigation } from '@/composables/useUnsavedDialog';
import { useActionConfirmation } from '@/composables/useActionConfirmation';
import { useDialogAccessibility } from '@/composables/useDialogAccessibility';

const { pending, pendingInput, answer, answerInput } = useActionConfirmation();
const value = ref('');
const error = ref('');
const inputId = useId();
watch(pendingInput, (input) => { value.value = input?.initialValue ?? ''; error.value = ''; });
const submitInput = () => {
    const input = pendingInput.value;
    if (!input) return;
    const clean = value.value.trim();
    if (input.required && !clean) { error.value = 'Isian ini wajib dilengkapi.'; return; }
    if (input.type === 'number' && (!clean || !Number.isFinite(Number(clean)) || (input.integer && !Number.isInteger(Number(clean))) || (input.min !== undefined && Number(clean) < input.min) || (input.max !== undefined && Number(clean) > input.max))) {
        error.value = `Masukkan angka${input.min !== undefined ? ` minimal ${input.min}` : ''}${input.max !== undefined ? ` dan maksimal ${input.max}` : ''}.`;
        return;
    }
    if (input.maxLength !== undefined && clean.length > input.maxLength) { error.value = `Isian maksimal ${input.maxLength} karakter.`; return; }
    answerInput(clean);
};
const cancel = () => { answer(false); answerInput(null); };
const page = usePage();
useConfirmedFormNavigation();
useDialogAccessibility(() => !page.component.startsWith('Public/') && page.component !== 'Auth/Login');
let activePage = `${page.component}|${page.url}`;
const stopNavigation = router.on('navigate', (event) => {
    const nextPage = `${event.detail.page.component}|${event.detail.page.url}`;
    if (nextPage !== activePage) cancel();
    activePage = nextPage;
});
onBeforeUnmount(() => { stopNavigation(); cancel(); });
</script>

<template>
    <ConfirmDialog :open="pending !== null" title="Konfirmasi tindakan" :message="pending?.message ?? ''" confirm-label="Lanjutkan" @confirm="answer(true)" @cancel="answer(false)" />
    <ConfirmDialog :open="pendingInput !== null" title="Lengkapi tindakan" :message="pendingInput?.message ?? ''" variant="primary" confirm-label="Lanjutkan" @confirm="submitInput" @cancel="answerInput(null)">
        <label class="action-input"><span>{{ pendingInput?.message }}</span><input :id="inputId" v-model="value" :type="pendingInput?.type ?? 'text'" :step="pendingInput?.integer ? 1 : 'any'" :maxlength="pendingInput?.maxLength" :min="pendingInput?.min" :max="pendingInput?.max" :required="pendingInput?.required" :aria-invalid="Boolean(error)" :aria-describedby="error ? `${inputId}-error` : undefined" autofocus @keydown.enter.prevent="submitInput" /><small v-if="error" :id="`${inputId}-error`" role="alert">{{ error }}</small></label>
    </ConfirmDialog>
</template>

<style scoped>
.action-input { display: grid; gap: .5rem; margin-bottom: 1rem; color: var(--ink); }
.action-input input { width: 100%; min-width: 0; padding: .6rem .75rem; border: 1px solid var(--line); border-radius: var(--radius-sm); color: var(--ink); background: var(--surface); }
.action-input small { color: var(--delete); }
</style>
