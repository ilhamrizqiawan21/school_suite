<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { SharedProps } from '@/types';

defineProps<{
    title: string;
    mode?: 'scan' | 'kiosk' | 'gate';
}>();

const page = usePage<SharedProps>();
const schoolName = computed(() => page.props.auth?.school?.name ?? page.props.school?.name ?? 'School Suite');
const userName = computed(() => page.props.auth?.user?.name ?? 'Petugas');
const flashMessage = computed(() => page.props.flash?.message);
const flashError = computed(() => page.props.flash?.error);
</script>

<template>
    <div class="ops-shell" :class="mode ? 'ops-mode-' + mode : undefined">
        <header class="ops-topbar">
            <div class="ops-topbar-inner">
                <div class="ops-brand">
                    <span class="ops-badge">Operasional</span>
                    <div>
                        <strong>{{ title }}</strong>
                        <small>{{ schoolName }} · {{ userName }}</small>
                    </div>
                </div>
                <nav class="ops-nav" aria-label="Navigasi operasional">
                    <Link href="/dashboard" class="ops-link">Dashboard</Link>
                    <a href="/e-layanan" class="ops-link ops-link--muted">Portal Layanan</a>
                </nav>
            </div>
        </header>

        <main id="main-content" class="ops-main" tabindex="-1">
            <div v-if="flashMessage" class="ops-flash ops-flash--success" role="status">{{ flashMessage }}</div>
            <div v-if="flashError" class="ops-flash ops-flash--error" role="alert">{{ flashError }}</div>
            <slot />
        </main>
    </div>
</template>

<style scoped>
.ops-shell {
    min-height: 100vh;
    background: #f1f5f9;
    color: #0f172a;
    display: flex;
    flex-direction: column;
}

.ops-topbar {
    background: #022c22;
    color: #fff;
    border-bottom: 1px solid #065f46;
}

.ops-topbar-inner {
    width: min(1360px, calc(100% - 2rem));
    margin-inline: auto;
    padding: .75rem 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
}

.ops-brand {
    display: flex;
    align-items: center;
    gap: .75rem;
}

.ops-badge {
    padding: .25rem .6rem;
    border-radius: 999px;
    background: #047857;
    color: #fde68a;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.ops-brand strong {
    display: block;
    font-size: .95rem;
    line-height: 1.25;
}

.ops-brand small {
    display: block;
    font-size: .75rem;
    color: #a7f3d0;
}

.ops-nav {
    display: flex;
    align-items: center;
    gap: .5rem;
}

.ops-link {
    padding: .45rem .85rem;
    border-radius: .6rem;
    background: rgb(255 255 255 / 12%);
    color: #fff;
    font-size: .8rem;
    font-weight: 700;
    text-decoration: none;
}

.ops-link:hover {
    background: rgb(255 255 255 / 22%);
}

.ops-link--muted {
    background: transparent;
    border: 1px solid rgb(167 243 208 / 35%);
    color: #d1fae5;
}

.ops-main {
    width: min(1360px, calc(100% - 2rem));
    margin-inline: auto;
    padding: 1.25rem 0 2.5rem;
    flex: 1;
}

.ops-flash {
    margin-bottom: 1rem;
    padding: .8rem 1rem;
    border-radius: .75rem;
    font-size: .875rem;
    font-weight: 600;
}

.ops-flash--success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}

.ops-flash--error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.ops-mode-scan .ops-topbar, .ops-mode-kiosk .ops-topbar, .ops-mode-gate .ops-topbar { display: none; }
.ops-mode-scan .ops-main { display: flex; flex-direction: column; justify-content: center; min-height: 100svh; width: 100%; padding: 32px 0; gap: 24px; }
.ops-mode-scan :deep(.operations-workflow) { width: min(1100px,calc(100% - 32px)); margin-inline: auto; }
.ops-mode-scan :deep(.operations-workflow > summary) { cursor: pointer; text-align: center; color: #64748b; font-size: 12px; padding: 12px; }
.ops-mode-scan :deep(.operations-workflow[open] > summary) { margin-bottom: 16px; }
.ops-mode-scan :deep(.operations-scan-hero) { display: none; }
.ops-mode-gate { background: radial-gradient(ellipse at center,#064e3b,#022c22); }
.ops-mode-gate .ops-main { display: grid; align-content: center; min-height: 100svh; padding-block: 48px; }
.ops-mode-gate :deep(.security-post-layout.is-locked) { display: flex; flex-direction: column; align-items: center; }
.ops-mode-gate :deep(.is-locked > .operations-scan-hero) { display: none; }
.ops-mode-gate :deep(.is-locked .operations-pin-panel) { width: min(360px, 100%); margin: auto; padding: 32px; border-top: 0; order: 1; }
.ops-mode-gate :deep(.security-pin-configuration) { width: min(360px, 100%); margin: 16px auto 0; order: 2; background: rgb(255 255 255 / 92%); }
.ops-mode-gate :deep(.security-pin-configuration > summary) { cursor: pointer; font-size: 12px; font-weight: 700; text-align: center; }
.ops-mode-kiosk .ops-main { width: 100%; max-width: none; padding: 0; }
.ops-mode-kiosk :deep(.kiosk-display) { min-height: 100svh; padding: 0; border-radius: 0; background: radial-gradient(ellipse at center,#022c22,#0f172a 70%); display: flex; flex-direction: column; }
.ops-mode-kiosk :deep(.kiosk-head) { padding: 16px 24px; border-bottom: 1px solid #1e293b; background: #0f172a; }
.ops-mode-kiosk :deep(.kiosk-head h1) { font-size: 18px; }
.ops-mode-kiosk :deep(.kiosk-head p) { font-size: 11px; }
.ops-mode-kiosk :deep(.scanner-card) { width: min(540px,calc(100% - 32px)); margin: auto; background: #0f172a; border: 1px solid #1e293b; border-radius: 24px; color: #e2e8f0; padding: 32px; }
.ops-mode-kiosk :deep(.kiosk-scan-prompt) { background: transparent; color: #e2e8f0; }
.ops-mode-kiosk :deep(.kiosk-scan-prompt)::before { content: ''; display: block; width: 130px; height: 130px; margin: 0 auto 32px; border: 5px solid #065f46; border-top-color: #34d399; border-radius: 50%; box-shadow: 0 0 40px #05966922; }
.ops-mode-kiosk :deep(.kiosk-scan-prompt h2) { font-size: 28px; }
.ops-mode-kiosk :deep(.panel-card) { border-radius: 0; background: #0f172a; color: #cbd5e1; padding: 14px 24px; border-color: #1e293b; }
.ops-mode-kiosk :deep(.panel-card h2) { font-size: 12px; }
.ops-mode-kiosk :deep(.scan-form input) { background: #1e293b; border-color: #334155; color: #fff; }
@media (max-width: 600px) {
    .ops-mode-kiosk :deep(.kiosk-head) { padding: 16px; }
    .ops-mode-kiosk :deep(.kiosk-controls) { gap: 6px; }
    .ops-mode-kiosk :deep(.scanner-card) { padding: 20px; margin-block: 24px; }
}
</style>
