<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const form = useForm({ current_password: '', password: '', password_confirmation: '' });
const showCurrent = ref(false);
const showNew = ref(false);
const showConfirmation = ref(false);
const currentStatus = ref<'idle' | 'checking' | 'valid' | 'invalid' | 'error'>('idle');
const criteria = computed(() => [
    { label: 'Minimal 10 karakter', met: Array.from(form.password).length >= 10 },
    { label: 'Huruf besar', met: /\p{Lu}/u.test(form.password) },
    { label: 'Huruf kecil', met: /\p{Ll}/u.test(form.password) },
    { label: 'Angka', met: /[0-9]/.test(form.password) },
]);
let checkTimer: ReturnType<typeof setTimeout> | undefined;
let checkSequence = 0;
watch(() => form.current_password, (password) => {
    form.clearErrors('current_password');
    if (checkTimer) clearTimeout(checkTimer);
    const sequence = ++checkSequence;
    if (!password) {
        currentStatus.value = 'idle';
        return;
    }
    currentStatus.value = 'checking';
    checkTimer = setTimeout(() => { void checkCurrent(password, sequence); }, 500);
});
const checkCurrent = async (password: string, sequence: number) => {
    try {
        const response = await fetch('/ganti-password/periksa', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' },
            body: JSON.stringify({ current_password: password }),
        });
        if (!response.ok) throw new Error('Pemeriksaan gagal');
        const result = await response.json();
        if (sequence === checkSequence && form.current_password === password) currentStatus.value = result.valid ? 'valid' : 'invalid';
    } catch {
        if (sequence === checkSequence && form.current_password === password) currentStatus.value = 'error';
    }
};
const submit = () => form.put('/ganti-password', { onSuccess: () => form.reset() });
</script>

<template>
    <Head title="Ganti Kata Sandi" />
    <main class="password-page">
        <form class="password-card" @submit.prevent="submit">
            <div class="shield" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4" />
                </svg>
            </div>
            <div class="password-intro">
                <p class="eyebrow">Keamanan akun</p>
                <h1>Ganti kata sandi</h1>
                <p>Gunakan minimal 10 karakter dengan huruf besar, huruf kecil, dan angka.</p>
            </div>
            <label>Kata sandi saat ini
                <span class="password-input-wrap"><input v-model="form.current_password" :type="showCurrent ? 'text' : 'password'" autocomplete="current-password" required /><button type="button" class="eye-toggle" :aria-label="showCurrent ? 'Sembunyikan kata sandi saat ini' : 'Tampilkan kata sandi saat ini'" :aria-pressed="showCurrent" @click="showCurrent = !showCurrent"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/><path v-if="showCurrent" stroke-linecap="round" d="m4 4 16 16"/></svg></button></span>
                <span v-if="currentStatus === 'checking'" class="criterion" role="status">Memeriksa kata sandi…</span>
                <span v-if="currentStatus === 'valid'" class="feedback-valid" role="status">Kata sandi saat ini benar.</span>
                <small v-else-if="currentStatus === 'invalid'" role="status">Kata sandi saat ini salah.</small>
                <small v-else-if="currentStatus === 'error'" role="status">Pemeriksaan gagal. Coba lagi nanti.</small>
                <small v-if="form.errors.current_password">{{ form.errors.current_password }}</small>
            </label>
            <label>Kata sandi baru
                <span class="password-input-wrap"><input v-model="form.password" :type="showNew ? 'text' : 'password'" autocomplete="new-password" minlength="10" required /><button type="button" class="eye-toggle" :aria-label="showNew ? 'Sembunyikan kata sandi baru' : 'Tampilkan kata sandi baru'" :aria-pressed="showNew" @click="showNew = !showNew"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/><path v-if="showNew" stroke-linecap="round" d="m4 4 16 16"/></svg></button></span>
                <span v-for="criterion in criteria" :key="criterion.label" :class="criterion.met ? 'feedback-valid' : 'criterion'">{{ criterion.met ? '✓' : '○' }} {{ criterion.label }}</span>
                <small v-if="form.errors.password">{{ form.errors.password }}</small>
            </label>
            <label>Konfirmasi kata sandi
                <span class="password-input-wrap"><input v-model="form.password_confirmation" :type="showConfirmation ? 'text' : 'password'" autocomplete="new-password" minlength="10" required /><button type="button" class="eye-toggle" :aria-label="showConfirmation ? 'Sembunyikan konfirmasi kata sandi' : 'Tampilkan konfirmasi kata sandi'" :aria-pressed="showConfirmation" @click="showConfirmation = !showConfirmation"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/><path v-if="showConfirmation" stroke-linecap="round" d="m4 4 16 16"/></svg></button></span>
                <span v-if="form.password_confirmation" :class="form.password === form.password_confirmation ? 'feedback-valid' : 'criterion'">{{ form.password === form.password_confirmation ? 'Kata sandi cocok.' : 'Kata sandi belum cocok.' }}</span>
                <small v-if="form.errors.password_confirmation">{{ form.errors.password_confirmation }}</small>
            </label>
            <AppButton variant="primary" :loading="form.processing" type="submit" class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : 'Simpan dan lanjutkan' }}</AppButton>
        </form>
    </main>
</template>

<style scoped>
.password-page { min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; background: var(--canvas); }
.password-card { width: min(480px, 100%); display: grid; gap: 1.1rem; padding: clamp(1.5rem, 4vw, 2.5rem); border: 1px solid var(--line); border-radius: 1.25rem; background: var(--surface); box-shadow: 0 20px 60px rgb(15 23 42 / .08); }
.shield { width: 3.25rem; height: 3.25rem; display: grid; place-items: center; border-radius: 1rem; background: var(--primary-soft); color: var(--primary-strong); }
.shield svg { width: 1.75rem; height: 1.75rem; }
.password-intro { display: grid; gap: .35rem; }
.password-card h1 { font-size: var(--type-3xl); line-height: 1.15; font-weight: var(--weight-bold); color: var(--ink); }
.password-card p { color: var(--ink-soft); line-height: 1.6; }
.password-card .eyebrow { color: var(--primary-strong); }
.password-card label { display: grid; gap: .45rem; color: var(--ink); font-size: var(--type-sm); font-weight: var(--weight-semibold); }
.password-card input { min-height: 48px; padding: .7rem .85rem; border: 1px solid var(--line-strong); border-radius: .7rem; color: var(--ink); background: var(--surface); }
.password-input-wrap { display: flex; align-items: center; position: relative; }
.password-input-wrap input { width: 100%; padding-right: 3rem; }
.password-card .eye-toggle { position: absolute; right: .35rem; display: grid; place-items: center; width: 2.35rem; height: 2.35rem; min-height: 0; padding: .45rem; color: var(--ink-soft); background: transparent; border-radius: .5rem; }
.password-card .eye-toggle:hover { color: var(--ink); background: var(--primary-soft); }
.eye-toggle svg { width: 1.25rem; height: 1.25rem; }
.password-card input:focus-visible, .password-card button:focus-visible { outline: 3px solid color-mix(in srgb, var(--primary) 42%, transparent); outline-offset: 2px; }
.password-card small { color: var(--delete); }
.feedback-valid { color: #16803c; font-size: var(--type-meta); }
.criterion { color: var(--ink-soft); font-size: var(--type-meta); }
.password-card button { min-height: 48px; background: var(--primary-action); }
.password-card button:hover:not(:disabled) { background: var(--primary-action-strong); }
.password-card button:disabled { opacity: .55; cursor: not-allowed; }
</style>
