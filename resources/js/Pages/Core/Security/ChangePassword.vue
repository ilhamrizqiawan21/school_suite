<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ current_password: '', password: '', password_confirmation: '' });
const submit = () => form.put('/ganti-password', { onFinish: () => form.reset() });
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
                <input v-model="form.current_password" type="password" autocomplete="current-password" required />
                <small v-if="form.errors.current_password">{{ form.errors.current_password }}</small>
            </label>
            <label>Kata sandi baru
                <input v-model="form.password" type="password" autocomplete="new-password" minlength="10" required />
                <small v-if="form.errors.password">{{ form.errors.password }}</small>
            </label>
            <label>Konfirmasi kata sandi
                <input v-model="form.password_confirmation" type="password" autocomplete="new-password" minlength="10" required />
                <small v-if="form.errors.password_confirmation">{{ form.errors.password_confirmation }}</small>
            </label>
            <button type="submit" class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : 'Simpan dan lanjutkan' }}</button>
        </form>
    </main>
</template>

<style scoped>
.password-page { min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; background: var(--canvas); }
.password-card { width: min(480px, 100%); display: grid; gap: 1.1rem; padding: clamp(1.5rem, 4vw, 2.5rem); border: 1px solid var(--line); border-radius: 1.25rem; background: var(--surface); box-shadow: 0 20px 60px rgb(15 23 42 / .08); }
.shield { width: 3.25rem; height: 3.25rem; display: grid; place-items: center; border-radius: 1rem; background: var(--primary-soft); color: var(--primary-strong); }
.shield svg { width: 1.75rem; height: 1.75rem; }
.password-intro { display: grid; gap: .35rem; }
.password-card h1 { font-size: clamp(1.6rem, 4vw, 2rem); line-height: 1.15; font-weight: 800; color: var(--ink); }
.password-card p { color: var(--ink-soft); line-height: 1.6; }
.password-card .eyebrow { color: var(--primary-strong); }
.password-card label { display: grid; gap: .45rem; color: var(--ink); font-size: .85rem; font-weight: 700; }
.password-card input { min-height: 48px; padding: .7rem .85rem; border: 1px solid var(--line-strong); border-radius: .7rem; color: var(--ink); background: var(--surface); }
.password-card input:focus-visible, .password-card button:focus-visible { outline: 3px solid color-mix(in srgb, var(--primary) 42%, transparent); outline-offset: 2px; }
.password-card small { color: var(--delete); }
.password-card button { min-height: 48px; background: var(--primary-action); }
.password-card button:hover:not(:disabled) { background: var(--primary-action-strong); }
.password-card button:disabled { opacity: .55; cursor: not-allowed; }
</style>
