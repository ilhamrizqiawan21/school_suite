import { readonly, shallowRef } from 'vue';

interface Confirmation {
    message: string;
    resolve: (accepted: boolean) => void;
}

const pending = shallowRef<Confirmation | null>(null);
interface InputOptions {
    initialValue?: string;
    required?: boolean;
    type?: 'text' | 'number';
    min?: number;
    max?: number;
    integer?: boolean;
    maxLength?: number;
}
const pendingInput = shallowRef<(InputOptions & { message: string; resolve: (value: string | null) => void }) | null>(null);

export function requestConfirmation(message: string): Promise<boolean> {
    if (pending.value || pendingInput.value) return Promise.resolve(false);
    return new Promise((resolve) => { pending.value = { message, resolve }; });
}

export function requestActionInput(message: string, options: InputOptions = {}): Promise<string | null> {
    if (pending.value || pendingInput.value) return Promise.resolve(null);
    return new Promise((resolve) => { pendingInput.value = { message, ...options, resolve }; });
}

export function useActionConfirmation() {
    const answer = (accepted: boolean) => {
        const current = pending.value;
        pending.value = null;
        current?.resolve(accepted);
    };
    const answerInput = (value: string | null) => {
        const current = pendingInput.value;
        pendingInput.value = null;
        current?.resolve(value);
    };
    return { pending: readonly(pending), pendingInput: readonly(pendingInput), answer, answerInput };
}
