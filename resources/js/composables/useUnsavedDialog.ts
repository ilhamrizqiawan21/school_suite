import { onBeforeUnmount, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { requestConfirmation } from './useActionConfirmation';

type GuardedForm = { isDirty: boolean; processing: boolean };
const guardedForms = new Set<GuardedForm>();

export function useConfirmedFormNavigation() {
    let approved = false;
    let pending = false;
    const hasChanges = () => [...guardedForms].some(form => form.isDirty && !form.processing);
    const beforeUnload = (event: BeforeUnloadEvent) => {
        if (hasChanges()) { event.preventDefault(); event.returnValue = ''; }
    };
    const stop = router.on('before', event => {
        if (event.defaultPrevented || approved || event.detail.visit.method !== 'get' || !hasChanges()) return;
        event.preventDefault();
        if (pending) return;
        pending = true;
        const visit = event.detail.visit;
        void requestConfirmation('Perubahan formulir belum disimpan. Tinggalkan halaman ini?').then(accepted => {
            pending = false;
            if (!accepted) return;
            approved = true;
            router.visit(visit.url, { ...visit, onFinish: () => { approved = false; } });
        });
    });
    onMounted(() => window.addEventListener('beforeunload', beforeUnload));
    onBeforeUnmount(() => { stop(); window.removeEventListener('beforeunload', beforeUnload); });
}

export function useConfirmedDialogClose(form: GuardedForm, close: () => void) {
    onMounted(() => guardedForms.add(form));
    onBeforeUnmount(() => guardedForms.delete(form));
    return async () => {
        if (form.processing) return;
        if (form.isDirty && !await requestConfirmation('Perubahan yang belum disimpan akan hilang. Tutup formulir?')) return;
        if (!form.processing) close();
    };
}

export function useUnsavedDialog(form: { isDirty: boolean; processing: boolean }, close: () => void) {
    const discardOpen = ref(false);
    const requestClose = () => {
        if (form.processing) return;
        if (form.isDirty) discardOpen.value = true;
        else close();
    };
    const discard = () => {
        if (form.processing) return;
        discardOpen.value = false;
        close();
    };
    return { discardOpen, requestClose, discard };
}
